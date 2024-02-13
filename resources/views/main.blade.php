<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{--All meta tags--}}
    <x-meta> @yield('title')</x-meta>
    {{--Css config--}}
    @vite('resources/css/app.css')
    @yield('css')
</head>
<body class="antialiased">
    @yield('main')
</body>
</html>
