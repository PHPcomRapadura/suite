<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Call for Papers — PHP com Rapadura</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <script>
        (function () {
            var t = localStorage.getItem('cfp-theme')
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark')
            }
        })()
    </script>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/cfp.js'])
</head>
<body class="bg-(--color-bg) text-(--color-text) antialiased">
    <div id="page-loader" class="page-loader">
        <img src="{{ asset('images/favicon.png') }}" alt="" class="page-loader__icon" aria-hidden="true">
        <p class="page-loader__text">Perainda!</p>
    </div>
    <div id="cfp-app"></div>
</body>
</html>
