<?php

declare(strict_types=1);

namespace App\Models;

// RF04 - projeto/oportunidade cadastrado por uma empresa ou terceiro.
class Demanda
{
    public function __construct(
        public ?int $id = null,
        public int $autorId = 0,
        public string $titulo = '',
        public string $escopo = '',
        // Areas de atuacao sugeridas pelo Agente de Recomendacao (NLP), sujeitas a aprovacao humana.
        public array $areasSugeridas = [],
        // Exclusao logica: 'A' (ativo) ou 'X' (excluido, preserva auditoria).
        public string $status = 'A',
    ) {
    }
}
