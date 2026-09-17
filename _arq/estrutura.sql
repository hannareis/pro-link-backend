-- ============================================================
-- PRO-LINK
-- Banco de Dados: MariaDB
-- ============================================================
-- CRIAÇÃO DO BANCO
-- ============================================================

CREATE DATABASE IF NOT EXISTS pro_link_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE pro_link_db;

-- ============================================================
-- USUÁRIOS
-- ============================================================

CREATE TABLE usuarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    tipo_pessoa ENUM(
        'FISICA',
        'JURIDICA'
    ) NOT NULL,

    perfil_acesso ENUM(
        'USUARIO',
        'ADMIN_CREA'
    ) NOT NULL DEFAULT 'USUARIO',

    tipo_conta ENUM(
        'COMUM',
        'ESTUDANTE',
        'PROFISSIONAL',
        'EMPRESA'
    ) NOT NULL DEFAULT 'COMUM',

    nome VARCHAR(150) NOT NULL,

    senha_hash VARCHAR(255) NOT NULL,

    telefone VARCHAR(25) NOT NULL,

    estado VARCHAR(25) NOT NULL,

    cidade VARCHAR(25) NOT NULL,

    email VARCHAR(254) NOT NULL UNIQUE,

    conta_ativa BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    ultimo_login_em DATETIME NULL,

    tentativas_login SMALLINT UNSIGNED NOT NULL DEFAULT 0,

    bloqueado_ate DATETIME NULL,

    INDEX idx_usuarios_tipo_pessoa (tipo_pessoa),
    INDEX idx_usuarios_perfil_acesso (perfil_acesso),
    INDEX idx_usuarios_tipo_conta (tipo_conta),
    INDEX idx_usuarios_conta_ativa (conta_ativa),
    INDEX idx_usuarios_nome (nome)
) ENGINE=InnoDB;


-- ============================================================
-- TOKENS DE REDEFINIÇÃO DE SENHA
-- ============================================================

CREATE TABLE tokens_redefinicao_senha (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_usuario INT UNSIGNED NOT NULL,

    token_hash CHAR(64) NOT NULL UNIQUE,

    expira_em DATETIME NOT NULL,

    usado_em DATETIME NULL,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_token_redefinicao_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_token_redefinicao_usuario (id_usuario),
    INDEX idx_token_redefinicao_expira (expira_em)
) ENGINE=InnoDB;


-- ============================================================
-- PESSOA FÍSICA
-- ============================================================

CREATE TABLE pessoa_fisica (
    id_usuario INT UNSIGNED PRIMARY KEY,

    cpf VARCHAR(14) NOT NULL UNIQUE,

    visibilidade_publica BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_pessoa_fisica_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- PESSOA JURÍDICA
-- ============================================================

CREATE TABLE pessoa_juridica (
    id_usuario INT UNSIGNED PRIMARY KEY,

    cnpj VARCHAR(18) NOT NULL UNIQUE,

    nome_fantasia VARCHAR(150),

    razao_social VARCHAR(150) NOT NULL,

    -- Selo de verificacao da empresa (contrato social + comprovante cadastral
    -- enviados pelo proprio usuario, analisados manualmente pelo ADMIN_CREA).
    status_verificacao ENUM('NAO_SOLICITADA', 'PENDENTE', 'APROVADA', 'REJEITADA')
        NOT NULL DEFAULT 'NAO_SOLICITADA',

    data_solicitacao_verificacao DATETIME,

    doc_contrato_social VARCHAR(255),

    doc_comprovante_cadastral VARCHAR(255),

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_pessoa_juridica_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- ADMINISTRADORES
-- ============================================================

CREATE TABLE administradores (
    id_usuario INT UNSIGNED PRIMARY KEY,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_administrador_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- UNIVERSIDADES/FACULDADES
-- ============================================================

CREATE TABLE universidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(200) NOT NULL,

    sigla VARCHAR(30),

    cnpj VARCHAR(18) UNIQUE,

    tipo ENUM(
        'PUBLICA_FEDERAL',
        'PUBLICA_ESTADUAL',
        'PUBLICA_MUNICIPAL',
        'PRIVADA',
        'COMUNITARIA',
        'CONFESSIONAL',
        'OUTRA'
    ) NOT NULL DEFAULT 'OUTRA',

    cidade VARCHAR(100),

    estado CHAR(2),

    site VARCHAR(255),

    ativa BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_universidades_nome (nome),
    INDEX idx_universidades_estado (estado),
    INDEX idx_universidades_ativa (ativa)
) ENGINE=InnoDB;

-- ============================================================
-- UNIVERSITÁRIOS
-- ============================================================

CREATE TABLE universitarios (
    id_usuario INT UNSIGNED PRIMARY KEY,

    universidade_id INT UNSIGNED NOT NULL,

    curso VARCHAR(150) NOT NULL,

    matricula VARCHAR(50),

    semestre_atual TINYINT UNSIGNED,

    previsao_formatura DATE,

    comprovante_matricula VARCHAR(255),

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    grau_academico ENUM(
        'TECNOLOGO',
        'GRADUACAO',
        'POS_GRADUACAO',
        'MESTRADO',
        'DOUTORADO',
        'POS_DOUTORADO'
    ) NOT NULL DEFAULT 'GRADUACAO',

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_universitario_semestre
        CHECK (
            semestre_atual IS NULL
            OR semestre_atual BETWEEN 1 AND 30
        ),

    CONSTRAINT fk_universitario_pessoa_fisica
        FOREIGN KEY (id_usuario)
        REFERENCES pessoa_fisica(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_universitario_universidade
        FOREIGN KEY (universidade_id)
        REFERENCES universidades(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    INDEX idx_universitarios_universidade (
        universidade_id
    )
) ENGINE=InnoDB;

-- ============================================================
-- PROFISSIONAIS
-- ============================================================

CREATE TABLE profissionais (
    id_usuario INT UNSIGNED PRIMARY KEY,

    numero_registro_confea_crea VARCHAR(50) NOT NULL UNIQUE,

    categoria_profissional VARCHAR(100) NOT NULL,

    anos_experiencia SMALLINT UNSIGNED,

    empresa_atual_id INT UNSIGNED NULL,

    grau_academico ENUM(
        'TECNOLOGO',
        'GRADUACAO',
        'POS_GRADUACAO',
        'MESTRADO',
        'DOUTORADO',
        'POS_DOUTORADO'
    ) NOT NULL DEFAULT 'GRADUACAO',

    registro_validado BOOLEAN NOT NULL DEFAULT FALSE,

    registro_validado_em DATETIME NULL,

    registro_ativo BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_profissional_experiencia
        CHECK (
            anos_experiencia IS NULL
            OR anos_experiencia >= 0
        ),

    CONSTRAINT fk_profissional_pessoa_fisica
        FOREIGN KEY (id_usuario)
        REFERENCES pessoa_fisica(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_profissional_empresa
        FOREIGN KEY (empresa_atual_id)
        REFERENCES pessoa_juridica(id_usuario)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_profissionais_categoria (categoria_profissional),
    INDEX idx_profissionais_empresa (empresa_atual_id),
    INDEX idx_profissionais_validacao (registro_validado),
    INDEX idx_profissionais_ativo (registro_ativo)
) ENGINE=InnoDB;


-- ============================================================
-- COMPETÊNCIAS
-- ============================================================

CREATE TABLE competencias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(100) NOT NULL UNIQUE,

    descricao VARCHAR(255),

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_competencias_ativo (ativo)
) ENGINE=InnoDB;

-- ============================================================
-- COMPETÊNCIAS DO PROFISSIONAL
-- ============================================================

CREATE TABLE profissional_competencias (
    id_profissional INT UNSIGNED NOT NULL,

    id_competencia INT UNSIGNED NOT NULL,

    nivel ENUM(
        'BASICO',
        'INTERMEDIARIO',
        'AVANCADO',
        'ESPECIALISTA'
    ) NOT NULL DEFAULT 'INTERMEDIARIO',

    anos_experiencia SMALLINT UNSIGNED,

    PRIMARY KEY (
        id_profissional,
        id_competencia
    ),

    CONSTRAINT chk_prof_comp_experiencia
        CHECK (
            anos_experiencia IS NULL
            OR anos_experiencia >= 0
        ),

    CONSTRAINT fk_prof_comp_profissional
        FOREIGN KEY (id_profissional)
        REFERENCES profissionais(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_prof_comp_competencia
        FOREIGN KEY (id_competencia)
        REFERENCES competencias(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- ESPECIALIDADES
-- ============================================================

CREATE TABLE especialidades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    nome VARCHAR(100) NOT NULL UNIQUE,

    descricao VARCHAR(255),

    ativo BOOLEAN NOT NULL DEFAULT TRUE,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_especialidades_ativo (ativo)
) ENGINE=InnoDB;


-- ============================================================
-- ESPECIALIDADES DO PROFISSIONAL
-- ============================================================

CREATE TABLE profissional_especialidades (
    id_profissional INT UNSIGNED NOT NULL,

    id_especialidade INT UNSIGNED NOT NULL,

    PRIMARY KEY (
        id_profissional,
        id_especialidade
    ),

    CONSTRAINT fk_prof_esp_profissional
        FOREIGN KEY (id_profissional)
        REFERENCES profissionais(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_prof_esp_especialidade
        FOREIGN KEY (id_especialidade)
        REFERENCES especialidades(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- PORTFÓLIO
-- Regra: uma pessoa física possui no máximo um portfólio.
-- ============================================================

CREATE TABLE portfolio (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_usuario INT UNSIGNED NOT NULL UNIQUE,

    resumo_profissional TEXT,

    documento_identificacao VARCHAR(255),

    links_contato JSON,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_portfolio_pessoa_fisica
        FOREIGN KEY (id_usuario)
        REFERENCES pessoa_fisica(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- PROJETOS
-- ============================================================

CREATE TABLE projetos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_portfolio INT UNSIGNED NOT NULL,

    titulo VARCHAR(150) NOT NULL,

    descricao TEXT,

    links_referencia JSON,

    data_inicio DATE,

    data_fim DATE,

    resultados_mencionaveis JSON,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_projeto_datas
        CHECK (
            data_fim IS NULL
            OR data_inicio IS NULL
            OR data_fim >= data_inicio
        ),

    CONSTRAINT fk_projeto_portfolio
        FOREIGN KEY (id_portfolio)
        REFERENCES portfolio(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_projetos_portfolio (id_portfolio),
    INDEX idx_projetos_data_inicio (data_inicio),
    INDEX idx_projetos_data_fim (data_fim)
) ENGINE=InnoDB;


-- ============================================================
-- COMPETÊNCIAS UTILIZADAS NOS PROJETOS
-- ============================================================

CREATE TABLE projeto_competencias (
    id_projeto INT UNSIGNED NOT NULL,

    id_competencia INT UNSIGNED NOT NULL,

    PRIMARY KEY (
        id_projeto,
        id_competencia
    ),

    CONSTRAINT fk_projeto_comp_projeto
        FOREIGN KEY (id_projeto)
        REFERENCES projetos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_projeto_comp_competencia
        FOREIGN KEY (id_competencia)
        REFERENCES competencias(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- EXPERIÊNCIAS
-- ============================================================

CREATE TABLE experiencias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_portfolio INT UNSIGNED NOT NULL,

    titulo_posicao_servico VARCHAR(150) NOT NULL,

    organizacao_cliente VARCHAR(150),

    organizacao_id INT UNSIGNED NULL,

    descricao_atividades TEXT,

    data_inicio DATE,

    data_fim DATE,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_experiencia_datas
        CHECK (
            data_fim IS NULL
            OR data_inicio IS NULL
            OR data_fim >= data_inicio
        ),

    CONSTRAINT fk_experiencia_portfolio
        FOREIGN KEY (id_portfolio)
        REFERENCES portfolio(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_experiencia_organizacao
        FOREIGN KEY (organizacao_id)
        REFERENCES pessoa_juridica(id_usuario)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_experiencias_portfolio (id_portfolio),
    INDEX idx_experiencias_organizacao (organizacao_id),
    INDEX idx_experiencias_data_inicio (data_inicio)
) ENGINE=InnoDB;


-- ============================================================
-- RELACIONAMENTO PROJETO x EXPERIÊNCIA
-- ============================================================

CREATE TABLE projeto_experiencia (
    id_projeto INT UNSIGNED NOT NULL,

    id_experiencia INT UNSIGNED NOT NULL,

    PRIMARY KEY (
        id_projeto,
        id_experiencia
    ),

    CONSTRAINT fk_proj_exp_projeto
        FOREIGN KEY (id_projeto)
        REFERENCES projetos(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_proj_exp_experiencia
        FOREIGN KEY (id_experiencia)
        REFERENCES experiencias(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- ART
-- ============================================================

CREATE TABLE arts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_portfolio INT UNSIGNED NOT NULL,

    id_profissional_responsavel INT UNSIGNED NOT NULL,

    numero_art VARCHAR(50) NOT NULL UNIQUE,

    tipo_art VARCHAR(100),

    status_art ENUM(
        'PENDENTE',
        'APROVADA',
        'REJEITADA',
        'CANCELADA'
    ) NOT NULL DEFAULT 'PENDENTE',

    validada_por_crea BOOLEAN NOT NULL DEFAULT FALSE,

    data_emissao DATE,

    data_validacao DATETIME NULL,

    documento_art VARCHAR(255),

    observacoes TEXT,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_art_portfolio
        FOREIGN KEY (id_portfolio)
        REFERENCES portfolio(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_art_profissional
        FOREIGN KEY (id_profissional_responsavel)
        REFERENCES profissionais(id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    INDEX idx_arts_portfolio (id_portfolio),
    INDEX idx_arts_profissional (id_profissional_responsavel),
    INDEX idx_arts_status (status_art),
    INDEX idx_arts_validacao (validada_por_crea)
) ENGINE=InnoDB;


-- ============================================================
-- CAT
-- ============================================================

CREATE TABLE cats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_portfolio INT UNSIGNED NOT NULL,

    numero_certidao VARCHAR(50) NOT NULL UNIQUE,

    codigo_autenticidade VARCHAR(100) NOT NULL UNIQUE,

    data_emissao DATE,

    validade DATE,

    status_cat ENUM(
        'PENDENTE',
        'VALIDA',
        'EXPIRADA',
        'CANCELADA',
        'REJEITADA'
    ) NOT NULL DEFAULT 'PENDENTE',

    id_profissional_responsavel INT UNSIGNED NOT NULL,

    id_contratante INT UNSIGNED NULL,

    id_proprietario INT UNSIGNED NULL,

    documento_cat VARCHAR(255),

    observacoes TEXT,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_cat_validade
        CHECK (
            validade IS NULL
            OR data_emissao IS NULL
            OR validade >= data_emissao
        ),

    CONSTRAINT fk_cat_portfolio
        FOREIGN KEY (id_portfolio)
        REFERENCES portfolio(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_cat_profissional
        FOREIGN KEY (id_profissional_responsavel)
        REFERENCES profissionais(id_usuario)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_cat_contratante
        FOREIGN KEY (id_contratante)
        REFERENCES usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_cat_proprietario
        FOREIGN KEY (id_proprietario)
        REFERENCES usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_cats_portfolio (id_portfolio),
    INDEX idx_cats_profissional (id_profissional_responsavel),
    INDEX idx_cats_status (status_cat),
    INDEX idx_cats_validade (validade)
) ENGINE=InnoDB;


-- ============================================================
-- DEMANDAS
-- ============================================================

CREATE TABLE demandas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_empresa INT UNSIGNED NOT NULL,

    titulo VARCHAR(150) NOT NULL,

    descricao TEXT NOT NULL,

    -- Area/tipo/modalidade/localizacao do escopo (RF04, item 7 - "Empresa publica demanda
    -- com escopo, localizacao e requisitos"). Granularidade fina de habilidades exigidas
    -- fica em demanda_competencias; estes campos sao a categorizacao ampla usada na busca.
    area VARCHAR(100) NOT NULL DEFAULT '',

    tipo ENUM(
        'ESTAGIO',
        'PROJETO',
        'CONSULTORIA',
        'ART',
        'PERICIA',
        'MENTORIA',
        'PESQUISA',
        'VOLUNTARIADO'
    ) NOT NULL DEFAULT 'PROJETO',

    cidade VARCHAR(100) NOT NULL DEFAULT '',

    uf CHAR(2) NOT NULL DEFAULT '',

    modalidade ENUM(
        'PRESENCIAL',
        'REMOTO',
        'HIBRIDO'
    ) NOT NULL DEFAULT 'PRESENCIAL',

    status ENUM(
        'ABERTA',
        'FECHADA',
        'CANCELADA',
        'SUSPENSA_PELO_CREA'
    ) NOT NULL DEFAULT 'ABERTA',

    data_publicacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    data_fechamento DATETIME NULL,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_demanda_datas
        CHECK (
            data_fechamento IS NULL
            OR data_fechamento >= data_publicacao
        ),

    CONSTRAINT fk_demanda_empresa
        FOREIGN KEY (id_empresa)
        REFERENCES pessoa_juridica(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_demandas_empresa (id_empresa),
    INDEX idx_demandas_status_data (
        status,
        data_publicacao
    ),
    INDEX idx_demandas_data_publicacao (data_publicacao)
) ENGINE=InnoDB;


-- ============================================================
-- REQUISITOS DAS DEMANDAS
-- ============================================================

CREATE TABLE demanda_competencias (
    id_demanda INT UNSIGNED NOT NULL,

    id_competencia INT UNSIGNED NOT NULL,

    obrigatoria BOOLEAN NOT NULL DEFAULT TRUE,

    nivel_minimo ENUM(
        'BASICO',
        'INTERMEDIARIO',
        'AVANCADO',
        'ESPECIALISTA'
    ) NOT NULL DEFAULT 'BASICO',

    PRIMARY KEY (
        id_demanda,
        id_competencia
    ),

    CONSTRAINT fk_demanda_comp_demanda
        FOREIGN KEY (id_demanda)
        REFERENCES demandas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_demanda_comp_competencia
        FOREIGN KEY (id_competencia)
        REFERENCES competencias(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
) ENGINE=InnoDB;


-- ============================================================
-- DEMONSTRAÇÕES DE INTERESSE
-- ============================================================

CREATE TABLE demonstracoes_interesse (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_demanda INT UNSIGNED NOT NULL,

    id_usuario INT UNSIGNED NOT NULL,

    titulo VARCHAR(150) NOT NULL,

    mensagem TEXT NOT NULL,

    status ENUM(
        'ENVIADA',
        'EM_ANALISE',
        'ACEITA',
        'RECUSADA',
        'CANCELADA'
    ) NOT NULL DEFAULT 'ENVIADA',

    data_interesse DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uk_interesse_demanda_usuario
        UNIQUE (
            id_demanda,
            id_usuario
        ),

    CONSTRAINT fk_interesse_demanda
        FOREIGN KEY (id_demanda)
        REFERENCES demandas(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_interesse_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES pessoa_fisica(id_usuario)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_interesse_demanda (id_demanda),
    INDEX idx_interesse_usuario (id_usuario),
    INDEX idx_interesse_status (status)
) ENGINE=InnoDB;


-- ============================================================
-- POSTS
-- ============================================================

CREATE TABLE posts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_autor INT UNSIGNED NOT NULL,

    titulo VARCHAR(150),

    conteudo TEXT NOT NULL,

    status_post ENUM(
        'PUBLICO',
        'PRIVADO',
        'ARQUIVADO',
        'SUSPENSO_PELO_CREA'
    ) NOT NULL DEFAULT 'PUBLICO',

    data_postagem DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    -- Mantido como JSON porque um post pode pertencer
    -- a várias áreas e o conteúdo pode evoluir.
    area_confea_crea JSON,

    CONSTRAINT fk_post_autor
        FOREIGN KEY (id_autor)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_posts_autor_data (
        id_autor,
        data_postagem
    ),

    INDEX idx_posts_status_data (
        status_post,
        data_postagem
    )
) ENGINE=InnoDB;


-- ============================================================
-- ANEXOS
-- ============================================================

CREATE TABLE anexos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_post INT UNSIGNED NOT NULL,

    nome_arquivo VARCHAR(255) NOT NULL,

    nome_armazenado VARCHAR(255),

    tipo_mime VARCHAR(100),

    status_anexo ENUM(
        'PUBLICO',
        'PRIVADO',
        'ARQUIVADO',
        'SUSPENSO_PELO_CREA'
    ) NOT NULL DEFAULT 'PUBLICO',

    tamanho BIGINT UNSIGNED,

    caminho_armazenamento VARCHAR(500) NOT NULL,

    hash_arquivo VARCHAR(128),

    data_upload DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_anexo_post
        FOREIGN KEY (id_post)
        REFERENCES posts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_anexos_post (id_post),
    INDEX idx_anexos_hash (hash_arquivo)
) ENGINE=InnoDB;


-- ============================================================
-- COMENTÁRIOS
-- ============================================================

CREATE TABLE comentarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_post INT UNSIGNED NOT NULL,

    id_autor INT UNSIGNED NOT NULL,

    id_comentario_pai INT UNSIGNED NULL,

    conteudo TEXT NOT NULL,

    status_comentario ENUM(
        'PUBLICO',
        'PRIVADO',
        'ARQUIVADO',
        'SUSPENSO_PELO_CREA'
    ) NOT NULL DEFAULT 'PUBLICO',

    data_comentario DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_comentario_post
        FOREIGN KEY (id_post)
        REFERENCES posts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_comentario_autor
        FOREIGN KEY (id_autor)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    -- Se o comentário pai for removido, a resposta permanece como comentário raiz.
    CONSTRAINT fk_comentario_pai
        FOREIGN KEY (id_comentario_pai)
        REFERENCES comentarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_comentarios_post_data (
        id_post,
        data_comentario
    ),

    INDEX idx_comentarios_pai (
        id_comentario_pai
    ),

    INDEX idx_comentarios_autor (
        id_autor
    )
) ENGINE=InnoDB;


-- ============================================================
-- LIKES EM POSTS
-- ============================================================

CREATE TABLE likes_posts (
    id_usuario INT UNSIGNED NOT NULL,

    id_post INT UNSIGNED NOT NULL,

    data_curtida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (
        id_usuario,
        id_post
    ),

    CONSTRAINT fk_like_post_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_like_post_post
        FOREIGN KEY (id_post)
        REFERENCES posts(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_likes_posts_post (
        id_post
    )
) ENGINE=InnoDB;


-- ============================================================
-- LIKES EM COMENTÁRIOS
-- ============================================================

CREATE TABLE likes_comentarios (
    id_usuario INT UNSIGNED NOT NULL,

    id_comentario INT UNSIGNED NOT NULL,

    data_curtida DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (
        id_usuario,
        id_comentario
    ),

    CONSTRAINT fk_like_comentario_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_like_comentario_comentario
        FOREIGN KEY (id_comentario)
        REFERENCES comentarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_likes_comentarios_comentario (
        id_comentario
    )
) ENGINE=InnoDB;


-- ============================================================
-- DENÚNCIAS
-- ============================================================

CREATE TABLE denuncias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_denunciante INT UNSIGNED NULL,

    id_denunciado INT UNSIGNED NULL,

    motivo TEXT NOT NULL,

    status_denuncia ENUM(
        'PENDENTE',
        'EM_ANALISE',
        'APROVADA',
        'REJEITADA',
        'ARQUIVADA'
    ) NOT NULL DEFAULT 'PENDENTE',

    observacao_moderador TEXT,

    id_moderador INT UNSIGNED NULL,

    data_denuncia DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    data_analise DATETIME NULL,

    CONSTRAINT fk_denuncia_denunciante
        FOREIGN KEY (id_denunciante)
        REFERENCES usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_denuncia_denunciado
        FOREIGN KEY (id_denunciado)
        REFERENCES usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_denuncia_moderador
        FOREIGN KEY (id_moderador)
        REFERENCES administradores(id_usuario)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_denuncias_status_data (
        status_denuncia,
        data_denuncia
    ),

    INDEX idx_denuncias_denunciado (
        id_denunciado
    ),

    INDEX idx_denuncias_denunciante (
        id_denunciante
    )
) ENGINE=InnoDB;


-- ============================================================
-- LOGS DE ACESSO
-- ============================================================

CREATE TABLE sis_logs_acesso (
    log_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    usu_id INT UNSIGNED NULL,

    log_acao ENUM(
        'LOGIN_SUCESSO',
        'LOGIN_FALHA',
        'LOGOUT'
    ) NOT NULL,

    log_ip VARCHAR(45) NOT NULL,

    log_user_agent VARCHAR(500),

    log_dt_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_log_acesso_usuario
        FOREIGN KEY (usu_id)
        REFERENCES usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_logs_usuario_data (
        usu_id,
        log_dt_registro
    ),

    INDEX idx_logs_acao_data (
        log_acao,
        log_dt_registro
    ),

    INDEX idx_logs_ip (
        log_ip
    )
) ENGINE=InnoDB;


-- ============================================================
-- AUDITORIA
-- ============================================================

CREATE TABLE sis_auditoria (
    aud_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    usu_id INT UNSIGNED NULL,

    aud_tabela VARCHAR(100) NOT NULL,

    aud_registro_id BIGINT UNSIGNED NOT NULL,

    aud_acao ENUM(
        'INSERT',
        'UPDATE',
        'DELETE',
        'EXCLUSAO_LOGICA'
    ) NOT NULL,

    aud_dados_antigos JSON,

    aud_dados_novos JSON,

    aud_ip VARCHAR(45),

    aud_user_agent VARCHAR(500),

    aud_dt_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_auditoria_usuario
        FOREIGN KEY (usu_id)
        REFERENCES usuarios(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_auditoria_usuario_data (
        usu_id,
        aud_dt_registro
    ),

    INDEX idx_auditoria_tabela_registro (
        aud_tabela,
        aud_registro_id
    ),

    INDEX idx_auditoria_acao_data (
        aud_acao,
        aud_dt_registro
    )
) ENGINE=InnoDB;


-- ============================================================
-- NOTIFICACOES
-- ============================================================

CREATE TABLE notificacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_usuario INT UNSIGNED NOT NULL,

    mensagem TEXT NOT NULL,

    lida BOOLEAN NOT NULL DEFAULT FALSE,

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notificacao_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    INDEX idx_notificacoes_usuario_lida (
        id_usuario,
        lida
    )
) ENGINE=InnoDB;


-- ============================================================
-- CARTAS VIRTUAIS
-- ============================================================

CREATE TABLE cartas_virtuais (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    id_usuario INT UNSIGNED NOT NULL,

    id_demanda INT UNSIGNED NULL,

    titulo VARCHAR(150) NOT NULL,

    legenda TEXT,

    remetente_email VARCHAR(254) NOT NULL,

    destinatario_email VARCHAR(254) NOT NULL,

    -- Arquivo externo (anexo) opcional vinculado a carta.
    nome_arquivo VARCHAR(255),

    nome_armazenado VARCHAR(255),

    tipo_mime VARCHAR(100),

    tamanho_arquivo BIGINT UNSIGNED,

    caminho_armazenamento VARCHAR(500),

    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    atualizado_em DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_carta_virtual_usuario
        FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_carta_virtual_demanda
        FOREIGN KEY (id_demanda)
        REFERENCES demandas(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    INDEX idx_cartas_virtuais_usuario (id_usuario),
    INDEX idx_cartas_virtuais_demanda (id_demanda)
) ENGINE=InnoDB;