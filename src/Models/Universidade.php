<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `universidades`: instituicoes de ensino as quais os universitarios
// se vinculam.
class Universidade
{
    public const TIPO_PUBLICA_FEDERAL = 'PUBLICA_FEDERAL';
    public const TIPO_PUBLICA_ESTADUAL = 'PUBLICA_ESTADUAL';
    public const TIPO_PUBLICA_MUNICIPAL = 'PUBLICA_MUNICIPAL';
    public const TIPO_PRIVADA = 'PRIVADA';
    public const TIPO_COMUNITARIA = 'COMUNITARIA';
    public const TIPO_CONFESSIONAL = 'CONFESSIONAL';
    public const TIPO_OUTRA = 'OUTRA';

    public function __construct(
        public ?int $id = null,
        public string $nome = '',
        public ?string $sigla = null,
        public ?string $cnpj = null,
        public string $tipo = self::TIPO_OUTRA,
        public ?string $cidade = null,
        public ?string $estado = null,
        public ?string $site = null,
        public bool $ativa = true,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
    ) {
    }
}
