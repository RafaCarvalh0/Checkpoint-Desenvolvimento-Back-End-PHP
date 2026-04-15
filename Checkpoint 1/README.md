# Checkpoint 1 - Laravel MVC

Projeto Laravel com Composer, autoload PSR-4, MVC, rotas HTTP, MySQL, migration de `products`, seed de dados e tratamento global de erro com exceção de domínio.

## Requisitos

- PHP 8.2 ou superior
- Composer
- MySQL

## Configuração

```bash
cp .env.example .env
composer install
php artisan key:generate
```

Configure o banco no `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=checkpoint_1
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=file
CACHE_STORE=file
```

Crie o banco no MySQL:

```sql
CREATE DATABASE checkpoint_1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Execute as migrations e seeds:

```bash
php artisan migrate --seed
```

Se o Windows/OneDrive bloquear arquivos temporários do Blade, limpe o cache:

```bash
php artisan optimize:clear
```

## Executar

```bash
php artisan serve
```

Acesse:

- `http://127.0.0.1:8000/`
- `http://127.0.0.1:8000/products`

## Testar

```bash
php artisan test
```

## Estrutura principal

- `app/Models/Product.php`: model Eloquent do catálogo.
- `app/Http/Controllers/ProductController.php`: controller das páginas do catálogo.
- `app/Exceptions/ProductNotFoundException.php`: exceção de domínio.
- `bootstrap/app.php`: handler global para converter a exceção em resposta HTTP 404.
- `database/migrations/*create_products_table.php`: migration da tabela `products`.
- `database/seeders/ProductSeeder.php`: dados iniciais do catálogo.
- `routes/web.php`: pontos de entrada HTTP.
