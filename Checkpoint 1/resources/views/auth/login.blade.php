<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
</head>
<body>
    <main>
        <h1>Login</h1>

        @if (session('success'))
            <p>{{ session('success') }}</p>
        @endif

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

        <form method="POST" action="{{ route('login.store') }}">
            @csrf

            <label>
                E-mail
                <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            </label>

            <label>
                Senha
                <input type="password" name="password" required>
            </label>

            <label>
                <input type="checkbox" name="remember" value="1">
                Manter conectado
            </label>

            <button type="submit">Entrar</button>
        </form>

        <p><a href="{{ route('register') }}">Criar cadastro</a></p>
    </main>
</body>
</html>
