# pro-link-backend

Backend PHP 8.2 da plataforma **Pro-Link** (Equipe Otho — Desafio CREA Pro-Link, Edital nº 03/2026, II CENATEC CREA-AM).

Arquitetura em camadas (Layered Architecture) + MVC + Front Controller, conforme o Anexo I —
Termo de Referência para Desenvolvimento e Arquitetura de Software do edital. Cada camada
consome apenas a camada imediatamente abaixo.

Documentação completa (arquitetura, MER, dependências, estrutura de pastas) em [`_arq/`](_arq/).

## Estrutura de pastas

```
pro-link-backend/
├── _config.php                # Configuracao principal: URLs, PATHs, timezone, DB/API/SMTP (Anexo I, 8.3.1)
├── _arq/                      # Docs obrigatorias: estrutura.sql, MER, dependencias, arquitetura (Anexo I, 8.3.2)
│
├── public/                    # Camada de Apresentacao (unico ponto de entrada web)
│   ├── index.php              # Front Controller
│   ├── assets/{css,js,img}    # Bootstrap 5 / JS / jQuery
│   └── uploads/                # arquivos publicos (avatares, capas)
│
├── routes/
│   └── web.php                 # definicao de rotas -> Controller + Middlewares
│
├── src/
│   ├── Core/                   # Router, Request, Response, Database (PDO), View
│   ├── Middleware/              # AuthMiddleware, CsrfMiddleware, SanitizeInputMiddleware, RoleMiddleware
│   ├── Controllers/             # Auth, User, Portfolio, Demanda, CartaVirtual, Feed, Admin
│   ├── Services/                 # Camada de Dominio/Negocio (RF02-RF07)
│   │   ├── CreaApiService.php          # RF02 - integracao API oficial CREA-AM
│   │   ├── RecomendacaoService.php     # RF04 - Agente de Recomendacao (NLP)
│   │   ├── CartaVirtualService.php     # RF05 - check-in / check-out
│   │   ├── NotificacaoService.php      # RF07 - SMTP / notificacoes internas
│   │   ├── AuditoriaService.php        # RF06 - logs e indicadores
│   │   └── DadosPublicosService.php    # dados.gov.br (assistido por IA, com auditoria humana)
│   ├── Models/                   # User, Portfolio, Demanda, CartaVirtual, ArtCat, AuditLog
│   ├── Repositories/             # Acesso ao MariaDB (exclusao logica status = 'X')
│   └── Helpers/                  # Sanitizer, Validator, functions.php (csrf_token, config)
│
├── views/                       # Templates HTML5/CSS3, sem logica de negocio
│   ├── layouts/  auth/  feed/  portfolio/  demanda/  carta-virtual/  admin/  errors/
│
├── database/
│   └── seeders/                  # dados sinteticos para o Demo Day
├── storage/
│   ├── logs/
│   └── uploads/arts_cats/        # documentos privados ate validacao via API CREA-AM
├── tests/{Unit,Integration}
│
├── docker/
│   ├── nginx/default.conf        # reverse proxy
│   └── php/Dockerfile            # PHP-FPM 8.2
├── docker-compose.yml            # nginx + app-php + mariadb
├── composer.json                 # PSR-4: App\ -> src/
└── .env.example
```

## Fluxo de uma requisicao

`public/index.php` (Front Controller) → `Router` → `Middlewares` (Sanitizacao → CSRF → Auth → Role)
→ `Controller` → `Service` (regra de negocio / integracao CREA-AM / SMTP) → `Repository`/`Model`
(persistencia MariaDB) → `View` (renderizacao).

Controllers nunca acessam o banco ou a API do CREA-AM diretamente — essa responsabilidade e
sempre delegada aos `Services` e `Repositories`.

## Convenção de nomenclatura no banco de dados

Tabelas e colunas seguem o padrão `{prefixo}_{entidade}` / `{prefixo}_{atributo}` exigido pelo
Anexo I, item 8.6 (ex.: `sis_usuarios.usu_email`). Ver `_arq/estrutura.sql`.

## Como rodar (Docker)

```bash
cp .env.example .env
docker compose up -d --build
```

Aplicacao disponivel em `http://localhost:8080`. Instruções detalhadas em
[`_arq/README.md`](_arq/README.md).
