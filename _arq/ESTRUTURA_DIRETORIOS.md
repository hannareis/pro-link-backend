# Estrutura de Diretórios — CREA Pro-Link (Backend)

Exigida pelo Anexo I, item 8.3.2-f do Edital Desafio CREA Pro-Link nº 03/2026.

```
pro-link-backend/
├── _config.php          # Configuracao principal: URLs, PATHs, timezone, charset, DB/API/SMTP (item 8.3.1)
├── _arq/                 # Este diretorio: docs, estrutura.sql, MER, dependencias (item 8.3.2)
├── public/               # Unica pasta exposta ao navegador
│   ├── index.php         # Front Controller - ponto de entrada unico da aplicacao
│   ├── assets/           # CSS, JS e imagens estaticas
│   └── uploads/          # Arquivos publicos enviados por usuarios
├── routes/
│   └── web.php           # Mapa de rotas: URL -> Controller + Middlewares
├── src/
│   ├── Core/             # Router, Request, Response, Database (PDO), View
│   ├── Middleware/        # Auth, CSRF, sanitizacao de entrada, controle de perfil
│   ├── Controllers/       # Orquestram a requisicao, sem regra de negocio ou SQL
│   ├── Services/          # Regras de negocio e integracoes (API CREA-AM, SMTP, NLP)
│   ├── Models/            # Entidades de dominio (objetos de dados tipados)
│   ├── Repositories/       # Unico ponto de acesso SQL a cada entidade (padrao Repository)
│   └── Helpers/           # Funcoes utilitarias (sanitizacao, validacao, csrf_token, config)
├── views/                 # Templates PHP puro, sem logica de negocio
├── database/
│   └── seeders/           # Dados sinteticos/ficticios para demonstracao
├── storage/
│   ├── logs/              # Logs da aplicacao
│   └── uploads/            # Arquivos privados (ex.: documentos de ARTs/CATs)
├── tests/                 # Testes unitarios e de integracao
├── docker/                # Configuracao dos containers (nginx, PHP-FPM)
├── docker-compose.yml     # Orquestracao nginx + app-php + mariadb
├── composer.json          # Dependencias PHP (autoload PSR-4: App\ -> src/)
└── .env.example           # Modelo de variaveis de ambiente (sem segredos reais)
```

## Convenção de nomenclatura no banco de dados

Ver `_arq/estrutura.sql`: tabelas `{prefixo}_{entidade}` (ex.: `sis_usuarios`,
`pro_demandas`) e colunas `{prefixo_tabela}_{atributo}` (ex.: `usu_id`,
`usu_nome`), conforme item 8.6 do Anexo I. A tradução entre essas colunas
prefixadas e os objetos de domínio em `src/Models` (nomes limpos em
camelCase) é feita exclusivamente pelos `Repositories`.
