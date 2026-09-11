<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `especialidades`: catalogo de especialidades vinculado ao profissional
// via `profissional_especialidades`.
class Especialidade
{
    public function __construct(
        public ?int $id = null,
        public string $nome = '',
        public ?string $descricao = null,
        public bool $ativo = true,
        public ?string $criadoEm = null,
    ) {
    }
}
