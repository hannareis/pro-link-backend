<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `competencias`: catalogo de competencias tecnicas reutilizadas por
// profissionais, projetos e demandas.
class Competencia
{
    // Niveis usados nas tabelas de vinculo (profissional_competencias,
    // demanda_competencias).
    public const NIVEL_BASICO = 'BASICO';
    public const NIVEL_INTERMEDIARIO = 'INTERMEDIARIO';
    public const NIVEL_AVANCADO = 'AVANCADO';
    public const NIVEL_ESPECIALISTA = 'ESPECIALISTA';

    public function __construct(
        public ?int $id = null,
        public string $nome = '',
        public ?string $descricao = null,
        public bool $ativo = true,
        public ?string $criadoEm = null,
    ) {
    }
}
