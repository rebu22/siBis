<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'siBis') }}</title>

        <!-- Font di-bundle lewat @fontsource-variable/plus-jakarta-sans (self-hosted, patuh CSP). -->

        <!-- Scripts -->
        {!! app(\Tighten\Ziggy\BladeRouteGenerator::class)->generate(nonce: Vite::cspNonce()) !!}
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="bg-slate-50 font-sans antialiased">
        @inertia
    </body>
</html>
