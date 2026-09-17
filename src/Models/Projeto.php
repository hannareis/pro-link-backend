<?php

declare(strict_types=1);

namespace App\Models;

// Tabela `projetos`: trabalho concreto exibido dentro de um portfolio.
class Projeto
{
    public function __construct(
        public ?int $id = null,
        public int $idPortfolio = 0,
        public string $titulo = '',
        public ?string $descricao = null,
        // Colunas JSON no banco.
        public array $linksReferencia = [],
        public ?string $dataInicio = null,
        public ?string $dataFim = null,
        public array $resultadosMencionaveis = [],
        public ?string $criadoEm = null,
        public ?string $atualizadoEm = null,
        // Competencias utilizadas (tabela `projeto_competencias`), carregadas sob demanda.
        public array $competencias = [],
        // URLs absolutas da galeria (tabela `projeto_imagens`), carregadas sob demanda
        // por ProjetoController (ver ProjetoImagemRepository::listByProjeto).
        public array $imagens = [],
    ) {
    }
}
