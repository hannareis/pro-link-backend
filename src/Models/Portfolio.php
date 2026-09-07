<?php

declare(strict_types=1);

namespace App\Models;

// RF03 - vitrine profissional/academica/institucional do usuario.
class Portfolio
{
    public function __construct(
        public ?int $id = null,
        public int $userId = 0,
        public string $resumo = '',
        public array $competencias = [],
        public string $senioridade = '',
        public array $experiencias = [],
        public array $projetos = [],
        // Obrigatorio para universitarios/pesquisadores: quem aprovou a vinculacao ao projeto.
        public ?int $responsavelTecnicoId = null,
        // Exclusao logica: 'A' (ativo) ou 'X' (excluido, preserva auditoria).
        public string $status = 'A',
    ) {
    }
}
