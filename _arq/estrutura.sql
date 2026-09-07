-- ============================================================================
-- CREA Pro-Link - Estrutura do banco de dados (MariaDB 10.11+)
-- Anexo I - Termo de Referencia para Desenvolvimento e Arquitetura de Software
-- Edital Desafio CREA Pro-Link no 03/2026 (CREA-AM), item 8.3.2-a.
--
-- Convencao de nomenclatura adotada (item 8.6, para quem nao usa framework):
--   - Tabelas:  {prefixo_modulo}_{entidade}, ex.: sis_usuarios, pro_demandas
--   - Colunas:  {prefixo_tabela}_{atributo}, ex.: usu_id, usu_nome
--   - Chaves:   pk_{prefixo}_id (Primary Key) / fk_{prefixo}_{referencia} (Foreign Key)
--   - Controle: {prefixo}_dt_registro, {prefixo}_log, {prefixo}_status
--   - Exclusao logica: {prefixo}_status CHAR(1) - 'A' = Ativo, 'X' = Excluido
--     (registros com status = 'X' nao aparecem nas consultas operacionais,
--     preservando historico de auditoria conforme item 8.6-j)
-- ============================================================================

SET NAMES utf8mb4;

-- RF01 - usuarios da plataforma e seus perfis (publico, profissional, empresa,
-- terceiro, universitario/pesquisador - adicional aos 5 perfis minimos do Anexo I -
-- e administrador).
CREATE TABLE IF NOT EXISTS sis_usuarios (
    usu_id INT UNSIGNED AUTO_INCREMENT,
    usu_nome VARCHAR(180) NOT NULL,
    usu_email VARCHAR(180) NOT NULL,
    usu_senha_hash VARCHAR(255) NOT NULL,
    usu_perfil ENUM('publico', 'profissional', 'empresa', 'universitario', 'terceiro', 'admin') NOT NULL,
    -- Concedido apos validacao positiva na API oficial do CREA-AM (RF01/RF02).
    usu_selo_verificacao TINYINT(1) NOT NULL DEFAULT 0,
    usu_status CHAR(1) NOT NULL DEFAULT 'A',
    usu_dt_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usu_log TEXT NULL,
    CONSTRAINT pk_usu_id PRIMARY KEY (usu_id),
    UNIQUE KEY uq_usu_email (usu_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RF03 - portfolio profissional/academico/institucional do usuario.
CREATE TABLE IF NOT EXISTS pro_portfolios (
    por_id INT UNSIGNED AUTO_INCREMENT,
    por_usu_id INT UNSIGNED NOT NULL,
    por_resumo TEXT NULL,
    por_competencias JSON NULL,
    por_senioridade VARCHAR(60) NULL,
    por_experiencias JSON NULL,
    por_projetos JSON NULL,
    -- Obrigatorio para universitarios/pesquisadores (RF03): profissional que validou a vinculacao.
    por_responsavel_tecnico_usu_id INT UNSIGNED NULL,
    por_status CHAR(1) NOT NULL DEFAULT 'A',
    por_dt_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    por_log TEXT NULL,
    CONSTRAINT pk_por_id PRIMARY KEY (por_id),
    CONSTRAINT fk_por_usu_id FOREIGN KEY (por_usu_id) REFERENCES sis_usuarios (usu_id),
    CONSTRAINT fk_por_responsavel_tecnico FOREIGN KEY (por_responsavel_tecnico_usu_id) REFERENCES sis_usuarios (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RF04 - demandas de servicos tecnicos cadastradas por empresas/terceiros.
CREATE TABLE IF NOT EXISTS pro_demandas (
    dem_id INT UNSIGNED AUTO_INCREMENT,
    dem_usu_id INT UNSIGNED NOT NULL,
    dem_titulo VARCHAR(180) NOT NULL,
    dem_escopo TEXT NOT NULL,
    -- Areas sugeridas pelo Agente de Recomendacao (NLP), indicativas e sujeitas a aprovacao humana.
    dem_areas_sugeridas JSON NULL,
    dem_status CHAR(1) NOT NULL DEFAULT 'A',
    dem_dt_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    dem_log TEXT NULL,
    CONSTRAINT pk_dem_id PRIMARY KEY (dem_id),
    CONSTRAINT fk_dem_usu_id FOREIGN KEY (dem_usu_id) REFERENCES sis_usuarios (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RF05 - manifestacao de interesse formal (Cartas Virtuais), do check-in ao check-out.
CREATE TABLE IF NOT EXISTS pro_cartas_virtuais (
    car_id INT UNSIGNED AUTO_INCREMENT,
    car_remetente_usu_id INT UNSIGNED NOT NULL,
    car_destinatario_usu_id INT UNSIGNED NOT NULL,
    car_conteudo TEXT NOT NULL,
    car_publica TINYINT(1) NOT NULL DEFAULT 0,
    car_status_envio ENUM('criada', 'enviada') NOT NULL DEFAULT 'criada',
    car_status CHAR(1) NOT NULL DEFAULT 'A',
    car_dt_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    car_log TEXT NULL,
    CONSTRAINT pk_car_id PRIMARY KEY (car_id),
    CONSTRAINT fk_car_remetente FOREIGN KEY (car_remetente_usu_id) REFERENCES sis_usuarios (usu_id),
    CONSTRAINT fk_car_destinatario FOREIGN KEY (car_destinatario_usu_id) REFERENCES sis_usuarios (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RF02/RF03 - ARTs e CATs consultadas/validadas via API oficial do CREA-AM e
-- exibidas no portfolio do profissional.
CREATE TABLE IF NOT EXISTS pro_arts_cats (
    art_id INT UNSIGNED AUTO_INCREMENT,
    art_usu_id INT UNSIGNED NOT NULL,
    art_tipo ENUM('ART', 'CAT') NOT NULL,
    art_numero_rnp VARCHAR(40) NOT NULL,
    art_dados_validados JSON NULL,
    art_status CHAR(1) NOT NULL DEFAULT 'A',
    art_dt_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    art_log TEXT NULL,
    CONSTRAINT pk_art_id PRIMARY KEY (art_id),
    CONSTRAINT fk_art_usu_id FOREIGN KEY (art_usu_id) REFERENCES sis_usuarios (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RF06 - trilha de auditoria das acoes criticas da plataforma.
CREATE TABLE IF NOT EXISTS sis_auditoria (
    aud_id INT UNSIGNED AUTO_INCREMENT,
    aud_usu_id INT UNSIGNED NOT NULL,
    aud_acao VARCHAR(120) NOT NULL,
    aud_contexto JSON NULL,
    aud_status CHAR(1) NOT NULL DEFAULT 'A',
    aud_dt_registro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT pk_aud_id PRIMARY KEY (aud_id),
    CONSTRAINT fk_aud_usu_id FOREIGN KEY (aud_usu_id) REFERENCES sis_usuarios (usu_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
