<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Haftalık Ders Programı') · {{ config('app.name') }}</title>
    {{-- Tailwind CSS via CDN: no NPM/Vite build step is required. --}}
    <script src="https://cdn.tailwindcss.com/3.4.17"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { 50: '#eff6ff', 600: '#1d4ed8', 700: '#1e40af' } } } } };
    </script>
    <style>
        .desen { background-size: 8px 8px; }
        .ders-hucre { text-shadow: 0 1px 2px rgba(0,0,0,.45); }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 antialiased">
    @yield('body')
</body>
</html>
