<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\CartaVirtual;

// Acesso ao MariaDB para a entidade CartaVirtual (RF05), tabela pro_cartas_virtuais.
class CartaVirtualRepository
{
    // Busca uma carta virtual pelo id.
    public function findById(int $id): ?CartaVirtual
    {
        return null;
    }

    // Insere ou atualiza uma carta virtual e retorna o id persistido.
    public function save(CartaVirtual $carta): int
    {
        return 0;
    }
}
