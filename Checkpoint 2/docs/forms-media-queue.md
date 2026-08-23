# Formulários, mídia, e-mail e fila

O cadastro e a edição usam `ProductType` e `ProductFormData`. As constraints validam os dados no servidor, incluindo SKU único, preço positivo, estoque não negativo e imagem JPEG, PNG ou WEBP de até 2 MB; erros e confirmações são exibidos com flash messages da sessão.

As imagens recebem nomes aleatórios e são armazenadas em `public/uploads/products/AAAA/MM`. Após o cadastro, o Messenger enfileira a geração de uma miniatura de até 320 px (WEBP, PNG ou JPEG conforme o suporte do GD) e o envio de uma notificação para `ADMIN_EMAIL` pelo transporte configurado em `MAILER_DSN`.

O transporte padrão usa a tabela `messenger_messages`, criada por migration. Em produção, processe a fila continuamente:

```bash
/Applications/XAMPP/xamppfiles/bin/php bin/console messenger:consume async --time-limit=3600
```

A exclusão de produto enfileira a limpeza de arquivos órfãos. A mesma rotina pode ser disparada manualmente com:

```bash
/Applications/XAMPP/xamppfiles/bin/php bin/console app:media:cleanup
```

Nos testes, o transporte `sync://` executa os handlers imediatamente, permitindo verificar e-mail, upload e miniatura sem um worker externo.
