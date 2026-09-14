<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `portfolio` do estrutura.sql: vitrine profissional/academica de uma
// pessoa fisica (1:1). Projetos, experiencias, ARTs e CATs sao entidades
// separadas, vinculadas pela coluna `id_portfolio`.
class Portfolio
{
    public function __construct(
        public ?int $id = null,
        public int $idUsuario = 0,
        public ?string $resumoProfissional = null,
        public ?string $documentoIdentificacao = null,
        // Coluna JSON no banco (ex: {"linkedin": "...", "github": "..."}).
        public array $linksContato = [],
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
    ) {
    }
}
