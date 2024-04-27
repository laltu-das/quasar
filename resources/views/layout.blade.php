<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="robots" content="noindex, nofollow">
    <link rel="shortcut icon" href="{{ asset('/vendor/quasar/favicon.ico') }}">

    <title>Laravel Envato{{ config('app.name') ? ' - ' . config('app.name') : '' }}</title>

    <!-- Style sheets-->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600" rel="stylesheet"/>
    <link href="{{ asset(mix('quasar.css', 'vendor/quasar')) }}" rel="stylesheet" type="text/css" onerror="alert('quasar.css failed to load. Please refresh the page, re-publish Log Viewer assets, or fix routing for vendor assets.')">
</head>
<body>
<div id="quasar">
    <router-view></router-view>
</div>

<!-- Global LogViewer Object -->
<script>
    window.Quasar = @json($quasarScriptVariables);

    // Add additional headers for LogViewer requests like so:
    // window.LogViewer.headers['Authorization'] = 'Bearer xxxxxxx';
</script>
<script src="{{ asset(mix('quasar.js', 'vendor/quasar')) }}" onerror="alert('quasar.js failed to load. Please refresh the page, re-publish Log Viewer assets, or fix routing for vendor assets.')"></script>
</body>
</html>
