<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `cats`: Certidao de Acervo Tecnico vinculada a um portfolio, emitida
// pelo CREA a partir de uma ou mais ARTs.
class Cat
{
    public const STATUS_PENDENTE = 'PENDENTE';
    public const STATUS_VALIDA = 'VALIDA';
    public const STATUS_EXPIRADA = 'EXPIRADA';
    public const STATUS_CANCELADA = 'CANCELADA';
    public const STATUS_REJEITADA = 'REJEITADA';

    public function __construct(
        public ?int $id = null,
        public int $idPortfolio = 0,
        public string $numeroCertidao = '',
        public string $codigoAutenticidade = '',
        public ?string $dataEmissao = null,
        public ?string $validade = null,
        public string $statusCat = self::STATUS_PENDENTE,
        public int $idProfissionalResponsavel = 0,
        public ?int $idContratante = null,
        public ?int $idProprietario = null,
        public ?string $documentoCat = null,
        public ?string $observacoes = null,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
    ) {
    }
}
