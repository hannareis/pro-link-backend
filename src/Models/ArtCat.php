<?php

declare(strict_types=1);

namespace App\Models;

// RF02/RF03 - Anotacao de Responsabilidade Tecnica (ART) ou Certidao de Acervo Tecnico (CAT),
// validadas via API do CREA-AM e exibidas no portfolio do profissional.
class ArtCat
{
    public const TIPO_ART = 'ART';
    public const TIPO_CAT = 'CAT';

    public function __construct(
        public ?int $id = null,
        public int $userId = 0,
        public string $tipo = self::TIPO_ART,
        public string $numeroRnp = '',
        public array $dadosValidados = [],
        // Exclusao logica: 'A' (ativo) ou 'X' (excluido, preserva auditoria).
        public string $status = 'A',
    ) {
    }
}
