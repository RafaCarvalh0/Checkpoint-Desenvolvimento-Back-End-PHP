# CI/CD e persistência poliglota

## Pipeline

O workflow `.github/workflows/checkpoint2-ci.yml` é executado em pushes e pull requests que alterem o Checkpoint 2. Ele valida o Composer, executa linters, cria o schema exclusivamente por migrations, roda a suíte e testa Redis/MongoDB em serviços isolados.

Após a qualidade, o job de build instala somente dependências de produção, aquece o cache e publica por 14 dias o artefato `checkpoint2-<commit>.tar.gz`. Pushes na branch `staging` promovem o build e executam migrations usando os secrets `APP_SECRET` e `DATABASE_URL` protegidos pelo environment `staging` do GitHub.

## Redis

Redis armazena caches de listagem, detalhes e relatórios com TTL e invalidação nas mutações. A fila pode ser promovida do transporte Doctrine para Redis configurando `MESSENGER_TRANSPORT_DSN=redis://redis:6379/messages` em um ambiente com `ext-redis`; nenhuma classe de domínio depende desse transporte.

## MongoDB

MongoDB é um índice de leitura opcional para busca textual. Para ativá-lo, instale `ext-mongodb` e `composer require mongodb/mongodb:^2.1`, configure `MONGODB_ENABLED=1` e execute:

```bash
docker compose up -d mongodb redis
php bin/console app:search:reindex-products
```

O endpoint `GET /api/v1/products/search?q=termo` informa o driver usado em `meta.driver`. Sem MongoDB, ele recua para Doctrine; com MongoDB, as alterações são sincronizadas por `SyncProductSearchIndex` no Messenger.

Doctrine permanece como fonte da verdade. MongoDB aceita consistência eventual: falhas permanecem na fila para nova tentativa e uma reconstrução completa corrige divergências; Redis nunca recebe dados autoritativos e é invalidado após mutações.
