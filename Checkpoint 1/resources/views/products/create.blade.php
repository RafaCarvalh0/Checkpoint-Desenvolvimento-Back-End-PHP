<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Novo Produto</title>
</head>
<body>
    <main>
        <p><a href="{{ route('products.index') }}">Voltar ao catalogo</a></p>
        <h1>Novo Produto</h1>

        <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
            @include('products._form', [
                'product' => null,
                'buttonLabel' => 'Criar produto',
            ])
        </form>
    </main>
</body>
</html>
