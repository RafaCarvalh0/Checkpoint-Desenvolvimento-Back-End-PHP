<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product->name }}</title>
</head>
<body>
    <main>
        <p><a href="{{ route('products.index') }}">Voltar ao catálogo</a></p>

        <h1>{{ $product->name }}</h1>
        <p>{{ $product->description }}</p>
        <p>Preço: R$ {{ number_format((float) $product->price, 2, ',', '.') }}</p>
        <p>Estoque: {{ $product->stock }}</p>
    </main>
</body>
</html>
