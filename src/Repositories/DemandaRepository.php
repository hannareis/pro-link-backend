<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Demanda;

// Acesso ao MariaDB para a entidade Demanda (RF04), tabela pro_demandas.
class DemandaRepository
{
    // Lista todas as demandas ativas.
    public function all(): array
    {
        return [];
    }

    // Insere ou atualiza uma demanda e retorna o id persistido.
    public function save(Demanda $demanda): int
    {
        return 0;
    }
}
