<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo de Produtos</title>
</head>
<body>
    <main>
        <h1>Catálogo de Produtos</h1>

        @forelse ($products as $product)
            <article>
                <h2>
                    <a href="{{ route('products.show', $product) }}">
                        {{ $product->name }}
                    </a>
                </h2>
                <p>{{ $product->description }}</p>
                <p>Preço: R$ {{ number_format((float) $product->price, 2, ',', '.') }}</p>
                <p>Estoque: {{ $product->stock }}</p>
            </article>
        @empty
            <p>Nenhum produto cadastrado.</p>
        @endforelse

        {{ $products->links() }}
    </main>
</body>
</html>
