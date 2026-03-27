<?php

namespace App\Services;

use App\Models\Company;
use App\Models\GoogleAccount;
use App\Models\User;
use Carbon\Carbon;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Sheets;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class GoogleOAuthService
{
    public const SESSION_STATE_KEY = 'google_oauth_state';

    public const SESSION_COMPANY_KEY = 'google_oauth_company_id';

    public const SCOPES = [
        Sheets::SPREADSHEETS,
        Drive::DRIVE_METADATA_READONLY,
        'openid',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://www.googleapis.com/auth/userinfo.profile',
    ];

    public function hasClientConfig(?Company $company): bool
    {
        if (! $company) {
            return false;
        }

        return filled(data_get($company->settings, 'google_oauth.client_id'))
            && filled(data_get($company->settings, 'google_oauth.client_secret'));
    }

    public function clientConfig(?Company $company): array
    {
        if (! $company || ! $this->hasClientConfig($company)) {
            return [
                'client_id' => '',
                'client_secret' => '',
            ];
        }

        $encryptedSecret = (string) data_get($company->settings, 'google_oauth.client_secret', '');

        return [
            'client_id' => (string) data_get($company->settings, 'google_oauth.client_id', ''),
            'client_secret' => $this->decryptSecret($encryptedSecret),
        ];
    }

    public function saveClientConfig(Company $company, string $clientId, string $clientSecret): void
    {
        $settings = $company->settings ?? [];

        Arr::set($settings, 'google_oauth.client_id', trim($clientId));
        Arr::set($settings, 'google_oauth.client_secret', Crypt::encryptString(trim($clientSecret)));

        $company->forceFill(['settings' => $settings])->save();
    }

    public function connectedAccount(?Company $company): ?GoogleAccount
    {
        if (! $company) {
            return null;
        }

        return $company->googleAccount;
    }

    public function createAuthUrl(Company $company): string
    {
        if (! $this->hasClientConfig($company)) {
            throw new RuntimeException('Google OAuth client ID and secret must be saved first.');
        }

        $state = Str::random(40);

        session([
            self::SESSION_STATE_KEY => $state,
            self::SESSION_COMPANY_KEY => $company->id,
        ]);

        $client = $this->oauthClient($company);
        $client->setState($state);

        return $client->createAuthUrl();
    }

    public function handleCallback(User $user, string $code, ?string $state): GoogleAccount
    {
        $expectedState = session()->pull(self::SESSION_STATE_KEY);
        $companyId = session()->pull(self::SESSION_COMPANY_KEY);

        if (! $expectedState || ! $state || ! hash_equals($expectedState, $state)) {
            throw new RuntimeException('Google OAuth state mismatch. Please try connecting again.');
        }

        $company = Company::query()->findOrFail($companyId);
        $client = $this->oauthClient($company);
        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new RuntimeException((string) ($token['error_description'] ?? $token['error']));
        }

        $client->setAccessToken($token);

        $profile = [];

        try {
            $response = $client->authorize()->get('https://www.googleapis.com/oauth2/v2/userinfo');
            $profile = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $profile = [];
        }

        $account = GoogleAccount::query()->firstOrNew(['company_id' => $company->id]);

        $account->fill([
            'user_id' => $user->id,
            'google_email' => data_get($profile, 'email'),
            'google_name' => data_get($profile, 'name'),
            'access_token' => (string) ($token['access_token'] ?? ''),
            'refresh_token' => (string) ($token['refresh_token'] ?? $account->refresh_token),
            'expires_at' => $this->expiresAt($token),
            'scopes' => isset($token['scope']) ? preg_split('/\s+/', trim((string) $token['scope'])) : self::SCOPES,
            'metadata' => [
                'token_type' => $token['token_type'] ?? null,
                'id_token' => $token['id_token'] ?? null,
                'connected_at' => now()->toIso8601String(),
            ],
        ]);

        $account->save();

        return $account;
    }

    public function disconnect(Company $company): void
    {
        $company->googleAccount()?->delete();
    }

    public function listSpreadsheets(Company $company): array
    {
        $drive = new Drive($this->authorizedClient($company));

        $files = $drive->files->listFiles([
            'q' => "mimeType='application/vnd.google-apps.spreadsheet' and trashed=false",
            'fields' => 'files(id,name,modifiedTime,webViewLink)',
            'orderBy' => 'modifiedTime desc',
            'pageSize' => 100,
        ])->getFiles() ?? [];

        return collect($files)
            ->map(fn ($file) => [
                'id' => $file->getId(),
                'name' => $file->getName(),
                'modified_time' => $file->getModifiedTime(),
                'url' => $file->getWebViewLink() ?: "https://docs.google.com/spreadsheets/d/{$file->getId()}/edit",
            ])
            ->all();
    }

    public function listSheets(Company $company, string $spreadsheetId): array
    {
        $service = new Sheets($this->authorizedClient($company));

        $spreadsheet = $service->spreadsheets->get($spreadsheetId, [
            'fields' => 'properties.title,sheets.properties(sheetId,title,index)',
        ]);

        return collect($spreadsheet->getSheets() ?? [])
            ->map(fn ($sheet) => [
                'gid' => (int) $sheet->getProperties()->getSheetId(),
                'title' => (string) $sheet->getProperties()->getTitle(),
                'index' => (int) $sheet->getProperties()->getIndex(),
            ])
            ->sortBy('index')
            ->values()
            ->all();
    }

    public function authorizedClient(Company $company): Client
    {
        if (! $this->hasClientConfig($company)) {
            throw new RuntimeException('Google OAuth client ID and secret must be saved first.');
        }

        $account = $this->connectedAccount($company);

        if (! $account || ! $account->access_token) {
            throw new RuntimeException('Google is not connected for this company.');
        }

        $client = $this->oauthClient($company);

        if ($account->expires_at && $account->expires_at->lte(now()->addMinute())) {
            if (! $account->refresh_token) {
                throw new RuntimeException('Google access expired and no refresh token is available. Reconnect Google.');
            }

            $client->refreshToken($account->refresh_token);
            $token = $client->getAccessToken();

            $account->forceFill([
                'access_token' => (string) ($token['access_token'] ?? $account->access_token),
                'refresh_token' => (string) ($token['refresh_token'] ?? $account->refresh_token),
                'expires_at' => $this->expiresAt($token, now()),
                'scopes' => isset($token['scope']) ? preg_split('/\s+/', trim((string) $token['scope'])) : $account->scopes,
                'metadata' => array_merge($account->metadata ?? [], [
                    'token_type' => $token['token_type'] ?? data_get($account->metadata, 'token_type'),
                    'refreshed_at' => now()->toIso8601String(),
                ]),
            ])->save();
        }

        $client->setAccessToken([
            'access_token' => $account->fresh()->access_token,
            'refresh_token' => $account->refresh_token,
        ]);

        return $client;
    }

    protected function oauthClient(Company $company): Client
    {
        $config = $this->clientConfig($company);

        $client = new Client;
        $client->setClientId($config['client_id']);
        $client->setClientSecret($config['client_secret']);
        $client->setApplicationName(config('app.name', 'IQX Connect'));
        $client->setRedirectUri(route('admin.google.callback'));
        $client->setAccessType('offline');
        $client->setPrompt('consent select_account');
        $client->setIncludeGrantedScopes(true);
        $client->setScopes(self::SCOPES);

        return $client;
    }

    protected function expiresAt(array $token, ?Carbon $from = null): ?Carbon
    {
        if (! isset($token['expires_in'])) {
            return null;
        }

        return ($from ?? now())->copy()->addSeconds((int) $token['expires_in']);
    }

    protected function decryptSecret(string $value): string
    {
        if ($value === '') {
            return '';
        }

        try {
            return Crypt::decryptString($value);
        } catch (Throwable) {
            return $value;
        }
    }
}
