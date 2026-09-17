<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `projeto_imagens`: uma imagem da galeria de um projeto do portfolio (1:N).
class ProjetoImagem
{
    public function __construct(
        public ?int $id = null,
        public int $projetoId = 0,
        public string $nome = '',
        public string $nomeArmazenado = '',
        public string $tipoMime = '',
        public int $tamanho = 0,
        public string $caminhoArmazenamento = '',
        public int $ordem = 0,
        public ?string $criadoEm = null,
    ) {
    }
}
