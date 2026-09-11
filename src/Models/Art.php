<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `arts`: Anotacao de Responsabilidade Tecnica vinculada a um portfolio
// e a um profissional responsavel, validada pelo CREA.
class Art
{
    public const STATUS_PENDENTE = 'PENDENTE';
    public const STATUS_APROVADA = 'APROVADA';
    public const STATUS_REJEITADA = 'REJEITADA';
    public const STATUS_CANCELADA = 'CANCELADA';

    public function __construct(
        public ?int $id = null,
        public int $idPortfolio = 0,
        public int $idProfissionalResponsavel = 0,
        public string $numeroArt = '',
        public ?string $tipoArt = null,
        public string $statusArt = self::STATUS_PENDENTE,
        public bool $validadaPorCrea = false,
        public ?string $dataEmissao = null,
        public ?string $dataValidacao = null,
        public ?string $documentoArt = null,
        public ?string $observacoes = null,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
    ) {
    }
}
