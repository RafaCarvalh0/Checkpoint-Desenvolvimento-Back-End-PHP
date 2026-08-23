# Checkpoint 2 — Catálogo de produtos

API REST em Symfony 7.4 com Doctrine ORM, migrations e testes de integração.

## Executar

Instale as dependências com `composer install`. Use o PHP do XAMPP caso `php` não esteja no `PATH`:

```bash
/Applications/XAMPP/xamppfiles/bin/php bin/console doctrine:migrations:migrate
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8000 -t public
```

A configuração padrão usa SQLite em `var/data_dev.db`. Para MySQL/MariaDB, sobrescreva `DATABASE_URL` em `.env.local`.

## Endpoints

- `GET /api/products` — lista e filtra por `name`, `minPrice`, `maxPrice` e `active`.
- `POST /api/products` — cria um produto.
- `GET /api/products/{id}` — detalha um produto.
- `PUT|PATCH /api/products/{id}` — atualiza um produto.
- `DELETE /api/products/{id}` — exclui um produto.

Campos aceitos: `name`, `description`, `price` e `active`.

## Testes

```bash
/Applications/XAMPP/xamppfiles/bin/php bin/phpunit
```
