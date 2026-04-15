<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar Produto</title>
</head>
<body>
    <main>
        <p><a href="{{ route('products.show', $product->getId()) }}">Voltar ao produto</a></p>
        <h1>Editar Produto</h1>

        <form method="POST" action="{{ route('products.update', $product->getId()) }}">
            @method('PUT')
            @include('products._form', [
                'buttonLabel' => 'Salvar produto',
            ])
        </form>
    </main>
</body>
</html>
