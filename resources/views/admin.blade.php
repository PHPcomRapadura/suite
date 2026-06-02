<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>PHP com Rapadura — Admin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/admin.js'])
</head>
<body class="bg-(--color-bg) antialiased">
    <div id="admin-app"></div>
</body>
</html>
