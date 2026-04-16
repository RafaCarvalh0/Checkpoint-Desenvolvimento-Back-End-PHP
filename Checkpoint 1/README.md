# Checkpoint 1 - Desenvolvimento Back-End PHP

Projeto Laravel para catálogo de produtos com estrutura MVC, dominio orientado a objetos, CRUD web, autenticação, autorização, seguranca básica, API REST versionada e documentação OpenAPI/Swagger.

## Requisitos

- PHP 8.2 ou superior
- Composer
- MySQL

## Configuração

Instale as dependências e prepare o ambiente:

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure o banco no `.env`:

```env
APP_NAME="Checkpoint 1"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=checkpoint_1
DB_USERNAME=root
DB_PASSWORD=checkpoint_1

SESSION_DRIVER=file
CACHE_STORE=file
VIEW_COMPILED_PATH=C:/Users/Rafae/AppData/Local/Temp/laravel-checkpoint-1/views
```

Crie o banco no MySQL:

```sql
CREATE DATABASE checkpoint_1 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Execute as migrations e seeds:

```bash
php artisan migrate --seed
```

O seed cria um usuário administrador para testes:

```text
Email: admin@example.com
Senha: password
```

## Executar

```bash
php artisan serve
```

URLs principais:

```text
http://127.0.0.1:8000/
http://127.0.0.1:8000/login
http://127.0.0.1:8000/register
http://127.0.0.1:8000/products
http://127.0.0.1:8000/api/docs
```

A rota `/` redireciona usuários autenticados para `/products` e usuários deslogados para `/login`.

## Funcionalidades web

O sistema possui CRUD de produtos com rotas RESTful:

```text
GET    /products                 Lista produtos
GET    /products/create          Exibe formulario de cadastro
POST   /products                 Cria produto
GET    /products/{product}       Exibe detalhe
GET    /products/{product}/edit  Exibe formulario de edicao
PUT    /products/{product}       Atualiza produto
DELETE /products/{product}       Remove produto
```

As telas usam validação de formulário, mensagens por sessão, repopulação de campos em caso de erro, proteção CSRF e escaping padrão do Blade.

## Autenticacao e autorizacao

O projeto possui cadastro, login e logout:

```text
GET  /register
POST /register
GET  /login
POST /login
POST /logout
```

Rotas administrativas exigem usuario autenticado. A remocao de produtos passa por `ProductPolicy` e fica restrita a usuarios com perfil `admin`.

## Dominio de produtos

A entidade `Product` encapsula as regras principais do catálogo:

- `name` obrigatório.
- `sku` obrigatório.
- `price` maior ou igual a zero.
- `stock` maior ou igual a zero.
- `status` controlado por enum.
- metódos específicos para alterar dados, preço, estoque e status.

O domínio também possui `ProductRepositoryInterface`, implementação Eloquent, filtros reutilizáveis e `SlugTrait` para gerar slugs normalizados.

## Persistencia e transacoes

A persistência usa Eloquent com migrations para `products`, `product_images` e usuários. Operações críticas como criar, atualizar, remover e ajustar estoque são executadas em transações, com bloqueio em atualizações sensíveis para evitar estado parcial.

## API REST

A API esta versionada em `/api/v1`:

```text
GET /api/v1/products
GET /api/v1/products/{id-ou-slug}
```

A listagem aceita filtros:

```text
name
sku
status
category
min_price
max_price
priceRange
in_stock
```

Também aceita paginação e ordenação:

```text
page
per_page
limit
offset
sort=name|price|stock|created_at
direction=asc|desc
```

As respostas seguem envelope padronizado:

```json
{
  "data": {},
  "meta": {},
  "errors": []
}
```

Erros sao convertidos para codigos HTTP adequados, incluindo `400`, `401`, `403`, `404`, `422` e `500`.

## Documentacao da API

A especificação OpenAPI fica em:

```text
docs/openapi.yaml
```

Documentação interativa Swagger:

```text
http://127.0.0.1:8000/api/docs
```

Arquivo OpenAPI servido pela aplicação:

```text
http://127.0.0.1:8000/api/docs/openapi.yaml
```

## Segurança

O projeto aplica:

- CSRF em formulários web.
- validação via Form Request.
- sanitização de entradas antes da validação.
- SKU normalizado em maiúsculas.
- email normalizado em minúsculas.
- rate limiting em login, cadastro, logout e escritas de produtos.
- autorização por policy para exclusão.
- respostas JSON seguras na API.

## Estrutura principal

```text
app/Domain/Products/Product.php
app/Domain/Products/ProductStatus.php
app/Domain/Products/ProductRepositoryInterface.php
app/Domain/Products/ProductFilters.php
app/Infrastructure/Persistence/EloquentProductRepository.php
app/Http/Controllers/ProductController.php
app/Http/Controllers/Api/V1/ProductController.php
app/Http/Controllers/SessionController.php
app/Http/Controllers/RegisterController.php
app/Http/Requests/ProductRequest.php
app/Policies/ProductPolicy.php
app/Support/Http/ApiResponse.php
app/Support/Strings/SlugTrait.php
docs/openapi.yaml
routes/web.php
routes/api.php
```

## Testes

Execute dentro da pasta `Checkpoint 1`:

```bash
php artisan test
```

Os testes unitários validam domínio e serviços com mocks de `ProductRepositoryInterface`. Os testes de integração validam API + banco usando migrations, contrato JSON, status HTTP e persistência.

O projeto também possui BDD com Behat para documentar e automatizar o fluxo principal de cadastro de usuário:

```bash
vendor/bin/behat
```

No Windows, também pode ser executado com:

```powershell
vendor\bin\behat.bat
```

O cenário fica em:

```text
features/cadastro_usuario.feature
```

Ele valida:

- cadastro de visitante com dados válidos;
- bloqueio de e-mail duplicado;
- bloqueio de senha com confirmação incorreta;
- criação do primeiro produto após cadastro e autenticação.

Por padrão, o PHPUnit usa SQLite em memória para manter a suite rápida:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

Se quiser rodar os testes de integração contra MySQL no Docker, abra o Docker Desktop e aguarde ele ficar ativo.

Entre na pasta do projeto:

```powershell
cd "{Caminho}Checkpoint-Desenvolvimento-Back-End-PHP\Checkpoint 1"
```

Teste se o comando `docker` esta disponível:

```powershell
docker --version
```

Se funcionar, suba o banco de teste:

```bash
docker compose -f docker-compose.testing.yml up -d
```

Se o PowerShell mostrar que `docker` não foi reconhecido, use o caminho completo do Docker Desktop:

```powershell
Test-Path "C:\Program Files\Docker\Docker\resources\bin\docker.exe"
```

Se retornar `True`, suba o banco assim:

```powershell
& "C:\Program Files\Docker\Docker\resources\bin\docker.exe" compose -f docker-compose.testing.yml up -d
```

Confira se o container esta rodando:

```powershell
docker ps
```

Ou, usando o caminho completo:

```powershell
& "C:\Program Files\Docker\Docker\resources\bin\docker.exe" ps
```

O container esperado e `checkpoint_1_mysql_test`.

Depois execute os testes sobrescrevendo as variaveis de banco no PowerShell:

```powershell
$env:DB_CONNECTION="mysql"
$env:DB_HOST="127.0.0.1"
$env:DB_PORT="3307"
$env:DB_DATABASE="checkpoint_1_testing"
$env:DB_USERNAME="root"
$env:DB_PASSWORD="checkpoint_1"
php artisan test
```

Resultado esperado:

```text
58 passed
```

Para parar o banco de teste:

```powershell
docker compose -f docker-compose.testing.yml down
```

Ou, usando o caminho completo:

```powershell
& "C:\Program Files\Docker\Docker\resources\bin\docker.exe" compose -f docker-compose.testing.yml down
```

## Problemas comuns

Se o PowerShell mostrar `Could not open input file: artisan`, entre na pasta correta:

```powershell
cd "Checkpoint 1"
php artisan test
```

Se o Windows/OneDrive bloquear arquivos temporários do Blade, limpe o cache:

```bash
php artisan optimize:clear
```

Se o MySQL recusar conexao, confirme se o serviço está ativo, se a porta `3306` esta correta e se usuario/senha do `.env` batem com o MySQL local.
