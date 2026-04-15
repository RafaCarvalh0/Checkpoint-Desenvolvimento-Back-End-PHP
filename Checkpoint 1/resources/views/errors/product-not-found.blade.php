<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produto não encontrado</title>
</head>
<body>
    <main>
        <h1>{{ $message }}</h1>
        <p>Confira o código informado ou volte para o catálogo.</p>
        <p><a href="{{ route('products.index') }}">Ver catálogo</a></p>
    </main>
</body>
</html>
