<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `experiencias`: vinculo profissional/servico dentro de um portfolio.
class Experiencia
{
    public function __construct(
        public ?int $id = null,
        public int $idPortfolio = 0,
        public string $tituloPosicaoServico = '',
        public ?string $organizacaoCliente = null,
        // Preenchido quando a organizacao e uma pessoa juridica cadastrada.
        public ?int $organizacaoId = null,
        public ?string $descricaoAtividades = null,
        public ?string $dataInicio = null,
        public ?string $dataFim = null,
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
    ) {
    }
}
