<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $product->getName() }}</title>
</head>
<body>
    <main>
        <p><a href="{{ route('products.index') }}">Voltar ao catalogo</a></p>

        @if (session('success'))
            <p>{{ session('success') }}</p>
        @endif

        <h1>{{ $product->getName() }}</h1>
        <p>{{ $product->getDescription() }}</p>
        <p>SKU: {{ $product->getSku() }}</p>
        <p>Slug: {{ $product->getSlug() }}</p>
        <p>Preço: R$ {{ number_format($product->getPrice(), 2, ',', '.') }}</p>
        <p>Estoque: {{ $product->getStock() }}</p>
        <p>Status: {{ $product->getStatus()->value }}</p>

        @if ($images->isNotEmpty())
            <section>
                <h2>Imagens</h2>
                @foreach ($images as $image)
                    <img src="{{ $image->thumbnail_url ?? $image->url }}" alt="Imagem de {{ $product->getName() }}" width="160">
                @endforeach
            </section>
        @endif

        @auth
            <p><a href="{{ route('products.edit', $product->getId()) }}">Editar</a></p>

            @if (auth()->user()->isAdmin())
                <form method="POST" action="{{ route('products.destroy', $product->getId()) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Remover</button>
                </form>
            @endif
        @else
            <p><a href="{{ route('login') }}">Entrar para administrar</a></p>
        @endauth
    </main>
</body>
</html>
