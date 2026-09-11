# CREA Pro-Link — Backend (instalação, configuração e execução)

Instruções mínimas exigidas pelo Anexo I, item 8.3.2-c do Edital Desafio CREA
Pro-Link nº 03/2026.

## Requisitos mínimos

- Docker e Docker Compose (execução recomendada e única forma homologada pela banca)
- Alternativa manual: PHP 8.2+, Composer, MariaDB 10.11+

## Instalação e execução (Docker — recomendado)

```bash
cp .env.example .env
docker compose up -d --build
```

A aplicação sobe em `http://localhost:8080`. O schema (`_arq/estrutura.sql`) é
executado automaticamente na primeira subida do container `mariadb`.

## Instalação manual (sem Docker)

```bash
cp .env.example .env
composer install
mysql -u root -p < _arq/estrutura.sql   # o proprio script cria e usa o banco pro_link_db
php -S localhost:8080 -t public
```

## Configuração

- Todas as variáveis sensíveis (banco, Token da API do CREA-AM, SMTP) ficam no
  `.env` (nunca commitado — ver `.env.example` para o modelo).
- Parâmetros gerais da aplicação (URLs, PATHs, timezone `America/Manaus`,
  charset UTF-8) ficam centralizados em `_config.php`, na raiz do projeto
  (Anexo I, item 8.3.1).

## Atualização

1. `git pull` para trazer o código mais recente.
2. `composer install` caso `composer.json` tenha mudado.
3. Rodar manualmente qualquer novo script em `_arq/estrutura.sql` (ou aplicar
   apenas o trecho novo) contra o banco existente — não há migrations
   incrementais neste protótipo, o `estrutura.sql` é a fonte única do schema.
4. `docker compose up -d --build` para reconstruir a imagem PHP.

## Estrutura do projeto

Ver `_arq/ESTRUTURA_DIRETORIOS.md` para a descrição de cada pasta e
`_arq/ARQUITETURA.md` para a documentação técnica da arquitetura.
