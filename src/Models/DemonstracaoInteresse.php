<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `demonstracoes_interesse`: manifestacao de uma pessoa fisica a respeito
// de uma demanda publicada por uma empresa. Unica por (demanda, usuario).
class DemonstracaoInteresse
{
    public const STATUS_ENVIADA = 'ENVIADA';
    public const STATUS_EM_ANALISE = 'EM_ANALISE';
    public const STATUS_ACEITA = 'ACEITA';
    public const STATUS_RECUSADA = 'RECUSADA';
    public const STATUS_CANCELADA = 'CANCELADA';

    public function __construct(
        public ?int $id = null,
        public int $idDemanda = 0,
        public int $idUsuario = 0,
        public string $titulo = '',
        public string $mensagem = '',
        public string $status = self::STATUS_ENVIADA,
        public ?string $dataInteresse = null,
        public ?string $atualizadoEm = null,
    ) {
    }
}
