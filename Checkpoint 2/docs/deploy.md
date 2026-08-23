# Deploy

## Requisitos

- PHP 8.2 ou superior com as extensões exigidas pelo Composer, GD para miniaturas e Intl para tradução otimizada.
- Banco compatível com Doctrine, Redis, servidor web apontando para `public/` e um processo para o Messenger.
- Variáveis `APP_ENV=prod`, `APP_DEBUG=0`, `APP_SECRET`, `DATABASE_URL`, `REDIS_URL`, `MAILER_DSN`, `ADMIN_EMAIL` e `MESSENGER_TRANSPORT_DSN`.

## Publicação

Faça o checkout da versão desejada, configure `.env.local` fora do controle de versão e execute:

```bash
sh bin/deploy.sh
```

O script instala dependências otimizadas, aplica migrations e aquece o cache. Depois, recarregue PHP-FPM e reinicie o worker `php bin/console messenger:consume async --time-limit=3600`; use Supervisor ou systemd para mantê-lo ativo.

## Verificação e retorno

Confira `GET /products`, `GET /api/v1/products` e os logs JSON enviados para `stderr`. Em falha, volte o código para a versão anterior; migrations destrutivas devem possuir um plano de restauração do backup, pois o rollback automático do schema não é seguro.
