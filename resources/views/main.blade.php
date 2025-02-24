<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{--All meta tags--}}
    <x-meta> @yield('title')</x-meta>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @yield('css')
    @yield('headJS')
</head>
<body class="antialiased">

    @yield('content')

    @livewireScripts
    @stack('scripts')
</body>
</html>
