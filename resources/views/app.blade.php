<!DOCTYPE html>
<html lang="fr" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    @routes
    <script>window.__SENTRY__ = {{ \Illuminate\Support\Js::from(['dsn' => config('sentry.js_dsn'), 'environment' => config('sentry.environment') ?? app()->environment(), 'release' => config('sentry.release'), 'tracesSampleRate' => config('sentry.traces_sample_rate')]) }};</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full">
    @inertia
</body>
</html>
