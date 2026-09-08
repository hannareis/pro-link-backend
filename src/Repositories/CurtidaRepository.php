<?php

declare(strict_types= 1);

namespace App\Repositories;

use App\Models\Curtida;

class CurtidaRepository
{
    public function save(Curtida $curtida): int
    {
        return 0;
    }

    public function convertDatabaseToObject(array $row): Curtida
    {
        return new Curtida(
            id: (int) $row['usu_id'],
            userId: (int) $row['usu_user_id'],
            publicavelId: (int) $row['usu_publicavel_id'],
        );
    }
}