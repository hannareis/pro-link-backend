<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Portfolio;

// Acesso ao MariaDB para a entidade Portfolio (RF03), tabela pro_portfolios.
class PortfolioRepository
{
    // Busca o portfolio de um usuario pelo id do usuario.
    public function findByUserId(int $userId): ?Portfolio
    {
        return null;
    }

    // Insere ou atualiza um portfolio e retorna o id persistido.
    public function save(Portfolio $portfolio): int
    {
        return 0;
    }
}
