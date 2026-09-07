# Dependências de Terceiros — CREA Pro-Link (Backend)

Exigida pelo Anexo I, item 8.3.2-e do Edital Desafio CREA Pro-Link nº 03/2026.

## Back-end (PHP / Composer)

| Biblioteca | Versão | Uso | Licença |
|---|---|---|---|
| PHP | 8.2+ | Linguagem estipulada pelo edital (item 8.1.1-a) | PHP License |
| vlucas/phpdotenv | ^5.6 | Carrega variáveis do `.env` para `$_ENV` | BSD-3-Clause |
| phpmailer/phpmailer | ^6.9 | Envio de e-mail via SMTP (`NotificacaoService`, RF07) | LGPL-2.1 |
| phpunit/phpunit (dev) | ^11.0 | Testes automatizados (`tests/`) | BSD-3-Clause |

Todas com licença compatível com uso institucional (item 8.1.3), sem
componentes proprietários ou com restrição a uso comercial/institucional.

## Extensões PHP nativas

| Extensão | Uso |
|---|---|
| `ext-pdo` / `ext-pdo_mysql` | Acesso ao MariaDB via prepared statements |
| `ext-curl` | Consumo da API oficial do CREA-AM (`App\Core\HttpClient`, RF02) — optamos por cURL nativo em vez de `guzzlehttp/guzzle` para reduzir dependências de terceiros nessa única integração |

Habilitadas no `docker/php/Dockerfile` via `docker-php-ext-install`.

## Front-end

| Biblioteca | Versão | Uso |
|---|---|---|
| Bootstrap | 5.x (CDN) | Grid e componentes responsivos |
| jQuery | 3.x (CDN) | Manipulação de DOM e requisições AJAX |

> As tags `<link>`/`<script>` do Bootstrap e jQuery ainda precisam ser
> adicionadas em `views/layouts/app.php` apontando para o CDN oficial (ou
> vendorizadas em `public/assets/`) antes da entrega final.

## Infraestrutura

| Ferramenta | Versão | Uso |
|---|---|---|
| MariaDB | 10.11+ | Banco de dados relacional (item 8.1.2) |
| nginx | 1.27-alpine | Proxy reverso (container `nginx`) |
| Docker / Docker Compose | — | Conteinerização e execução padronizada (item 8.8) |
