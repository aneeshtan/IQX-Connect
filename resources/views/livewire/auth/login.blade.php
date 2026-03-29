<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function useDemoCredentials(): void
    {
        $this->email = 'demo@iqxconnect.demo';
        $this->password = 'demo123';
        $this->remember = false;
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->ensureIsNotRateLimited();

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header title="Log in to your account" description="Enter your email and password below to log in" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <a
        href="{{ route('auth.google.redirect') }}"
        class="inline-flex w-full items-center justify-center gap-3 rounded-2xl border border-zinc-200 bg-white px-4 py-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50"
    >
        <svg class="h-5 w-5" viewBox="0 0 24 24" aria-hidden="true">
            <path fill="#EA4335" d="M12 10.2v3.9h5.5c-.2 1.3-1.5 3.9-5.5 3.9-3.3 0-6-2.7-6-6s2.7-6 6-6c1.9 0 3.2.8 3.9 1.5l2.7-2.6C16.9 3.3 14.7 2.4 12 2.4A9.6 9.6 0 0 0 2.4 12 9.6 9.6 0 0 0 12 21.6c5.5 0 9.2-3.8 9.2-9.2 0-.6-.1-1.1-.2-1.6H12Z"/>
            <path fill="#34A853" d="M2.4 12c0 3.8 2.2 7 5.4 8.6l3-2.4c-.8-.2-2.4-.8-3.6-2.2-.9-1-1.4-2.4-1.4-4s.5-3 1.4-4L4.2 5.7A9.5 9.5 0 0 0 2.4 12Z"/>
            <path fill="#4285F4" d="M12 21.6c2.6 0 4.8-.9 6.4-2.5l-3.1-2.4c-.8.6-1.9 1.3-3.3 1.3-1.2 0-2.8-.4-3.9-1.8l-3.1 2.4c1.7 1.8 4.1 3 7 3Z"/>
            <path fill="#FBBC05" d="M5 7.9c.8-1.5 2.4-2.5 4-2.9V2.6C6 3.2 3.5 5.2 2.4 8.1L5 7.9Z"/>
        </svg>
        Continue with Google
    </a>

    <div class="relative text-center text-xs uppercase tracking-[0.28em] text-zinc-400">
        <span class="relative z-10 bg-white px-3">Or use email</span>
        <div class="absolute inset-x-0 top-1/2 -z-0 border-t border-zinc-200"></div>
    </div>

    <form wire:submit="login" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input wire:model="email" label="{{ __('Email address') }}" type="email" name="email" required autofocus autocomplete="email" placeholder="email@example.com" />

        <!-- Password -->
        <div class="relative">
            <flux:input
                wire:model="password"
                label="{{ __('Password') }}"
                type="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Password"
            />

            @if (Route::has('password.request'))
                <x-text-link class="absolute right-0 top-0" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </x-text-link>
            @endif
        </div>

        <!-- Remember Me -->
        <flux:checkbox wire:model="remember" label="{{ __('Remember me') }}" />

        <div class="flex items-center justify-end">
            <flux:button variant="primary" type="submit" class="w-full">{{ __('Log in') }}</flux:button>
        </div>
    </form>

    <div class="text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span class="space-x-1">
            <span>Don't have an account?</span>
            <x-text-link href="{{ route('register') }}">Sign up</x-text-link>
        </span>
        <div class="mt-2">
            <button
                type="button"
                wire:click="useDemoCredentials"
                class="font-medium text-emerald-700 transition hover:text-emerald-800"
            >
                Use demo account
            </button>
        </div>
    </div>
</div>
