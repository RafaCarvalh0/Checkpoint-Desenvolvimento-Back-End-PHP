# Checkpoint 2 — Migração Laravel para Symfony

Migração do módulo de produtos do Checkpoint 1 para Symfony 7.4 e Doctrine, sem React e sem Inertia. As páginas são renderizadas no servidor com Twig e a API preserva o formato e os comportamentos principais do nível anterior.

## Executar

```bash
composer install
docker compose up -d redis
/Applications/XAMPP/xamppfiles/bin/php bin/console doctrine:migrations:migrate
/Applications/XAMPP/xamppfiles/bin/php -S localhost:8000 -t public
```

A configuração padrão usa SQLite. Para MySQL/MariaDB, sobrescreva `DATABASE_URL` em `.env.local`.

## Catálogo

- Páginas Twig: `GET /products`, criação, visualização, edição e exclusão.
- API: `GET|POST /api/v1/products` e `GET|PUT|PATCH|DELETE /api/v1/products/{id}`.
- Consulta por ID ou slug, paginação, offset, ordenação e filtros por nome, SKU, categoria, preço, status e disponibilidade.
- Domínio rico para preço, SKU, estoque e ativação; imagens persistidas em relacionamento Doctrine com `JOIN FETCH` no detalhe.
- Redis com TTL de 300 segundos nas listagens, detalhes e relatórios; mutações invalidam o pool automaticamente.
- Relatórios em `GET /reports`, `/api/v1/reports/products-by-category` e `/api/v1/reports/low-stock`.

Configuração, medições e estratégia de performance: [`docs/performance.md`](docs/performance.md).

## Testes

```bash
/Applications/XAMPP/xamppfiles/bin/php bin/phpunit
```
