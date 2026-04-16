<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastrar usuario</title>
</head>
<body>
    <main>
        <h1>Cadastrar usuario</h1>

        @if ($errors->any())
            <div>
                <p>Corrija os campos abaixo.</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}">
            @csrf

            <label>
                Nome
                <input type="text" name="name" value="{{ old('name') }}" required maxlength="255">
            </label>

            <label>
                E-mail
                <input type="email" name="email" value="{{ old('email') }}" required>
            </label>

            <label>
                Senha
                <input type="password" name="password" required minlength="8">
            </label>

            <label>
                Confirmar senha
                <input type="password" name="password_confirmation" required minlength="8">
            </label>

            <button type="submit">Cadastrar</button>
        </form>

        <p><a href="{{ route('login') }}">Ja tenho cadastro</a></p>
    </main>
</body>
</html>
