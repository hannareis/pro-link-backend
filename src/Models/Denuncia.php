<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `denuncias`: report de conteudo/usuario para moderacao do CREA.
class Denuncia
{
    public const STATUS_PENDENTE = 'PENDENTE';
    public const STATUS_EM_ANALISE = 'EM_ANALISE';
    public const STATUS_APROVADA = 'APROVADA';
    public const STATUS_REJEITADA = 'REJEITADA';
    public const STATUS_ARQUIVADA = 'ARQUIVADA';

    public function __construct(
        public ?int $id = null,
        public ?int $idDenunciante = null,
        public ?int $idDenunciado = null,
        public string $motivo = '',
        public string $statusDenuncia = self::STATUS_PENDENTE,
        public ?string $observacaoModerador = null,
        public ?int $idModerador = null,
        public ?string $dataDenuncia = null,
        public ?string $dataAnalise = null,
    ) {
    }
}
