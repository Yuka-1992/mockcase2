<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'App')</title>
  @vite(['resources/css/reset.css', 'resources/css/app.css'])
  @stack('styles')
</head>
<body>
  @include('components.header')
  <main>@yield('content')</main>
</body>
</html>