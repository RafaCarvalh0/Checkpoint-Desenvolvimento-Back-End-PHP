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

        @if (session('success'))
            <p>{{ session('success') }}</p>
        @endif

        @auth
            <p>
                <a href="{{ route('products.create') }}">Novo produto</a>
            </p>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Sair</button>
            </form>
        @else
            <p>
                <a href="{{ route('login') }}">Entrar</a>
                <a href="{{ route('register') }}">Cadastrar usuario</a>
            </p>
        @endauth

        <form method="GET" action="{{ route('products.index') }}">
            <label>
                Nome
                <input type="text" name="name" value="{{ $filters['name'] ?? '' }}">
            </label>

            <label>
                SKU
                <input type="text" name="sku" value="{{ $filters['sku'] ?? '' }}">
            </label>

            <label>
                Status
                <select name="status">
                    <option value="">Todos</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Ativo</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inativo</option>
                </select>
            </label>

            <button type="submit">Filtrar</button>
        </form>

        @forelse ($products as $product)
            <article>
                <h2>
                    <a href="{{ route('products.show', $product->getId()) }}">
                        {{ $product->getName() }}
                    </a>
                </h2>
                <p>{{ $product->getDescription() }}</p>
                <p>SKU: {{ $product->getSku() }}</p>
                <p>Slug: {{ $product->getSlug() }}</p>
                <p>Preço: R$ {{ number_format($product->getPrice(), 2, ',', '.') }}</p>
                <p>Estoque: {{ $product->getStock() }}</p>
                <p>Status: {{ $product->getStatus()->value }}</p>
                @auth
                    <p><a href="{{ route('products.edit', $product->getId()) }}">Editar</a></p>

                    @if (auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('products.destroy', $product->getId()) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Remover</button>
                        </form>
                    @endif
                @endauth
            </article>
        @empty
            <p>Nenhum produto cadastrado.</p>
        @endforelse
    </main>
</body>
</html>
