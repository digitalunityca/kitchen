<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>@yield('title', 'Settings')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
<style>
  body { overflow: auto; -webkit-user-select: auto; user-select: auto; }
  @media (prefers-color-scheme: dark) { body { --bg:#000; --card:#1C1C1E; --text:#F5F5F7; --sub:#A1A1A6; --line:#2C2C2E; --fill:#2C2C2E; --accent:#0A84FF; --shadow:none; } }
  .page { max-width: 640px; margin: 0 auto; padding: 32px 16px 48px; display: flex; flex-direction: column; gap: 20px; }
  .card { padding: 20px 24px; gap: 4px; justify-content: flex-start; }
  h1 { font-size: 30px; margin: 0; }
  a { color: var(--accent); }
</style>
@stack('head')
</head>
<body>
<div class="page">
  @yield('content')
</div>
@include('settings.close-script')
</body>
</html>
