<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\GoogleOAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AdminGoogleOAuthController extends Controller
{
    public function redirect(Company $company, GoogleOAuthService $googleOAuth): RedirectResponse
    {
        if (! $googleOAuth->hasClientConfig($company)) {
            return redirect()
                ->route('admin', ['tab' => 'sources'])
                ->with('status', 'Google OAuth app must be saved in Admin > Data Sources before connecting.');
        }

        try {
            return redirect()->away($googleOAuth->createAuthUrl($company));
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('admin', ['tab' => 'sources'])
                ->with('status', $exception->getMessage());
        }
    }

    public function callback(Request $request, GoogleOAuthService $googleOAuth): RedirectResponse
    {
        if ($request->filled('error')) {
            return redirect()
                ->route('admin', ['tab' => 'sources'])
                ->with('status', 'Google authorization was cancelled or rejected.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
            'state' => ['nullable', 'string'],
        ]);

        try {
            $account = $googleOAuth->handleCallback(
                $request->user(),
                $validated['code'],
                $validated['state'] ?? null,
            );

            $message = filled($account->google_email)
                ? "Google connected for {$account->google_email}."
                : 'Google connected successfully.';

            return redirect()->route('admin', ['tab' => 'sources'])->with('status', $message);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('admin', ['tab' => 'sources'])
                ->with('status', $exception->getMessage());
        }
    }

    public function disconnect(Company $company, GoogleOAuthService $googleOAuth): RedirectResponse
    {
        $googleOAuth->disconnect($company);

        return redirect()
            ->route('admin', ['tab' => 'sources'])
            ->with('status', "Google disconnected for {$company->name}.");
    }
}
