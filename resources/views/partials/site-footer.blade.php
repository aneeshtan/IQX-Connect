<footer class="{{ $class ?? '' }}">
    <div class="text-sm text-zinc-500">
        &copy; {{ now()->year }} {{ config('app.name', 'IQX Connect') }}. All rights reserved.
        <span class="mx-2 text-zinc-300">|</span>
        <span>Version {{ config('app.version', '1.0.0') }}</span>
    </div>
</footer>
