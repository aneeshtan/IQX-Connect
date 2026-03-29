<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover" />
<meta name="theme-color" content="#0f172a" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
<meta name="mobile-web-app-capable" content="yes" />

<title>{{ $title ?? config('app.name', 'IQX Connect') }}</title>

<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="alternate icon" href="{{ asset('favicon.ico') }}">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="apple-touch-icon" href="{{ asset('favicon.ico') }}">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=space-grotesk:400,500,600,700" rel="stylesheet" />

@php
    $forceLight = (bool) ($forceLight ?? false);
@endphp

<script>
    (() => {
        const forceLight = @json($forceLight);
        const appearance = window.localStorage.getItem('flux.appearance') || 'system';
        const accentTheme = window.localStorage.getItem('iqx-accent-theme') || 'emerald';
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const shouldUseDark = !forceLight && (appearance === 'dark' || (appearance === 'system' && prefersDark));

        document.documentElement.dataset.iqxForceLight = forceLight ? '1' : '0';
        document.documentElement.dataset.iqxAppearance = forceLight ? 'light' : appearance;
        document.documentElement.dataset.accentTheme = forceLight ? 'emerald' : accentTheme;
        document.documentElement.classList.toggle('dark', shouldUseDark);
        document.documentElement.style.colorScheme = shouldUseDark ? 'dark' : 'light';
    })();
</script>

@vite(['resources/css/app.css', 'resources/js/app.js'])
