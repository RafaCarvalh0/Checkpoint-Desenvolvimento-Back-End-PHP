# Baseline de performance

## Instrumentação

O ambiente `dev` mantém o logging e o backtrace do Doctrine ativos e inclui o Symfony Web Profiler. A aba Doctrine em `/_profiler` apresenta quantidade, parâmetros, duração e origem das queries executadas.

Para profiling de CPU, copie as opções de [`xdebug.ini.example`](xdebug.ini.example) para o `php.ini`, reinicie o PHP e execute a rota com o gatilho:

```bash
XDEBUG_TRIGGER=1 curl -sS 'http://localhost:8000/api/v1/products?status=active'
```

O arquivo `cachegrind` será criado em `/tmp/xdebug` e pode ser comparado no QCacheGrind/KCacheGrind. O PHP 8.2 do XAMPP usado durante esta implementação não possui a extensão XDebug instalada; por isso os números abaixo foram coletados com `hrtime`, Doctrine Profiler e `EXPLAIN QUERY PLAN`.

## Índices e medição

Base reproduzível: SQLite, PHP 8.2, 1.000 produtos gerados por `AppFixtures` e 1.000 execuções do relatório por categoria.

| Cenário | Tempo total |
|---|---:|
| Baseline, leitura integral sem índice | 346,526 ms |
| Otimizado, índice composto | 237,541 ms |

Redução observada: aproximadamente 31,5%. Os planos após a otimização foram:

```text
SEARCH products USING INDEX idx_products_status_category (status=?)
SEARCH products USING INDEX idx_products_status_stock (status=? AND stock<?)
```

Os índices cobrem nome, slug, preço, status, `(status, estoque)` e `(status, categoria)`. As duas últimas combinações correspondem diretamente aos predicados e à ordenação/agrupamento dos relatórios.

## Redis

- Pool dedicado `cache.products`, configurado por `REDIS_URL` e com TTL de 300 segundos.
- Chaves são separadas por namespace e SHA-256 dos filtros, paginação ou identificador.
- Listagens, detalhes e relatórios retornam `X-Cache: MISS` ou `X-Cache: HIT`.
- Listeners `postPersist`, `postUpdate` e `postRemove` limpam o pool depois do `flush`, cobrindo API e páginas Twig.
- O ambiente de teste usa filesystem para não depender de um Redis externo, mantendo os mesmos contratos PSR-6.

Verificação manual com Redis:

```bash
docker compose up -d redis
curl -sS -o /dev/null -D - -w 'total=%{time_total}\n' 'http://localhost:8000/api/v1/products?status=active'
curl -sS -o /dev/null -D - -w 'total=%{time_total}\n' 'http://localhost:8000/api/v1/products?status=active'
```

A primeira chamada deve ser `MISS`, a segunda `HIT`; após criar, editar ou remover produto, a próxima leitura deve voltar a `MISS`.
