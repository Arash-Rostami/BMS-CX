<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Client Portal' }}</title>

    @vite(['resources/css/app.css', 'resources/css/main-fonts.css', 'resources/js/app.js'])
    @filamentStyles
    @livewireStyles
</head>
<body class="antialiased bg-gray-50 text-gray-900 min-h-screen" style="font-family: 'Roboto', 'Kalameh', sans-serif;">
{{ $slot }}

@livewireScripts
@filamentScripts
</body>
</html>
