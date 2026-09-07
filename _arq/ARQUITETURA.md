# Documentação Técnica — Arquitetura do CREA Pro-Link (Backend)

Exigida pelo Anexo I, item 8.3.2-d do Edital Desafio CREA Pro-Link nº 03/2026.
Complementa o documento "Arquitetura de Software" entregue na proposta.

## Estilo arquitetural

Arquitetura em camadas (Layered Architecture) combinada ao padrão MVC, com
Front Controller único e Service Layer isolando regra de negócio dos
Controllers — conforme exigido no item 8.2 do Anexo I ("Não deverá haver
mistura de código PHP diretamente nas páginas HTML").

```
Navegador
   -> public/index.php (Front Controller)
   -> Router (routes/web.php)
   -> Middlewares (Sanitizacao -> CSRF -> Auth -> Role)
   -> Controller
   -> Service (regra de negocio / integracao CREA-AM / SMTP)
   -> Repository (SQL) -> Model (objeto de dominio)
   -> View (template PHP puro, sem logica de negocio)
```

Os Controllers nunca acessam o banco de dados ou a API do CREA-AM
diretamente — essa responsabilidade é sempre delegada aos `Services` e
`Repositories` (item 8.1.1-b: separação da persistência via padrão
Repository).

## Módulos e responsabilidades

| Camada | Diretório | Responsabilidade |
|---|---|---|
| Apresentação | `views/` | Templates PHP puro por módulo (auth, feed, portfolio, demanda, carta-virtual, admin) |
| Aplicação/Controle | `src/Core`, `src/Middleware`, `routes/` | Roteamento, requisição/resposta, autenticação, CSRF, sanitização |
| Domínio/Negócio | `src/Controllers`, `src/Services` | Orquestração da requisição e regras de negócio (RF02–RF07) |
| Persistência | `src/Models`, `src/Repositories` | Entidades de domínio e acesso ao MariaDB, com exclusão lógica |
| Integração/Infraestrutura | `docker/`, `docker-compose.yml`, `_config.php` | API do CREA-AM, SMTP, conteinerização |

## Integrações externas

- **API oficial do CREA-AM** (`src/Services/CreaApiService.php`, via
  `Core\HttpClient` — cURL nativo): consulta e validação de profissionais
  (CPF), empresas (CNPJ), ARTs e CATs vinculadas ao RNP, mediante Token de
  Acesso Individual (RF02). É vedada a criação de base própria simulando
  esses dados (item 8.4) — todos os dados fictícios vêm exclusivamente da
  API do desafio.
- **SMTP** (`src/Services/NotificacaoService.php`): disparo de e-mails para
  eventos da plataforma (cadastro, recuperação de senha, atualização de
  demandas, manifestação de interesse) — RF07.

## Regras de negócio principais

- **RF01**: 6 perfis de usuário (`sis_usuarios.usu_perfil`), autenticação,
  gestão de consentimento LGPD e Selo de Verificação condicionado à API do
  CREA-AM.
- **RF02**: `CreaApiService` é o único ponto de consumo da API oficial.
- **RF03**: portfólio (`pro_portfolios`) com validação de Responsável
  Técnico para vínculo de universitários/pesquisadores a projetos.
- **RF04**: `RecomendacaoService` (NLP) sugere áreas de atuação a partir do
  escopo textual da demanda — apenas indicativo, sem ranking de
  profissionais (vedado pelo Edital, item 10.1).
- **RF05**: `CartaVirtualService` implementa o fluxo de check-in
  (redação + validação de credenciais) e check-out (persistência + envio).
- **RF06**: `AuditoriaService` e a tabela `sis_auditoria` registram ações
  críticas para trilha de auditoria administrativa.
- **RF07**: notificações internas e por e-mail via `NotificacaoService`.

## Segurança (item 8.5 do Anexo I)

- Autenticação via sessão com cookie `httpOnly`/`SameSite=Lax` (`Core\Auth::configureSessionCookie`).
- Senhas com `password_hash()`/`password_verify()` (`Core\Auth`, usado pelo `AuthController`).
- Proteção CSRF (`CsrfMiddleware` + `csrf_token()`).
- Sanitização de entrada contra XSS (`SanitizeInputMiddleware` + `Sanitizer`).
- Consultas parametrizadas via PDO (proteção contra SQL Injection).
- Controle de perfis de acesso (`RoleMiddleware`, instanciado por rota com os perfis permitidos).
- Registro de auditoria (`AuditoriaService` / `sis_auditoria`).

## Banco de dados

Ver `_arq/estrutura.sql` (schema completo) e `_arq/MER.md` (diagrama). Todas
as tabelas seguem a convenção de nomenclatura do item 8.6 do Anexo I e usam
exclusão lógica (`{prefixo}_status = 'X'`) em vez de `DELETE` físico.
