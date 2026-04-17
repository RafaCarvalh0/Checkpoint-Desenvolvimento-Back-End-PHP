<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Catálogo de Produtos' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app"></div>
    <script>
        window.__APP_PROPS__ = {{ \Illuminate\Support\Js::from(array_merge([
            'csrfToken' => csrf_token(),
            'success' => session('success'),
            'auth' => [
                'user' => auth()->check() ? [
                    'name' => auth()->user()->name,
                    'email' => auth()->user()->email,
                ] : null,
                'isAdmin' => auth()->check() && auth()->user()->isAdmin(),
            ],
        ], $appProps ?? [])) }};
    </script>
</body>
</html>
