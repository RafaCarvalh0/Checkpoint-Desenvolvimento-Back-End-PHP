# language: pt
Funcionalidade: Cadastro e primeiro acesso ao catalogo
  Para gerenciar produtos com responsabilidade
  Como visitante
  Quero criar uma conta, validar meus dados e acessar o catalogo

  Cenario: Visitante realiza cadastro com dados validos
    Dado que nao existe usuario com e-mail "cliente@example.com"
    Quando eu cadastro o usuario "Cliente Teste" com e-mail "cliente@example.com" e senha "password123"
    Entao o cadastro deve ser concluido com sucesso
    E o usuario "cliente@example.com" deve existir com perfil "user"
    E devo estar autenticado como "cliente@example.com"

  Cenario: Visitante nao pode cadastrar e-mail duplicado
    Dado que existe usuario "Cliente Existente" com e-mail "cliente@example.com"
    Quando eu cadastro o usuario "Cliente Novo" com e-mail "cliente@example.com" e senha "password123"
    Entao o cadastro deve ser recusado no campo "email"
    E deve existir apenas 1 usuario com e-mail "cliente@example.com"

  Cenario: Visitante nao pode cadastrar senha sem confirmacao correta
    Dado que nao existe usuario com e-mail "senha@example.com"
    Quando eu tento cadastrar o usuario "Cliente Senha" com e-mail "senha@example.com", senha "password123" e confirmacao "password456"
    Entao o cadastro deve ser recusado no campo "password"
    E o usuario "senha@example.com" nao deve existir

  Cenario: Usuario cadastrado consegue criar seu primeiro produto
    Dado que nao existe usuario com e-mail "lojista@example.com"
    Quando eu cadastro o usuario "Lojista Teste" com e-mail "lojista@example.com" e senha "password123"
    E cadastro o produto "Mouse Vertical" com SKU "MOU-VERT-001", preco 149.90 e estoque 12
    Entao o produto "Mouse Vertical" deve existir no catalogo com SKU "MOU-VERT-001"
    E devo estar autenticado como "lojista@example.com"
