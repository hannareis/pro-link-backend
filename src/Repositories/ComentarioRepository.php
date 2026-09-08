<?php

namespace App\Repositories;

use App\Models\Comentario;

class ComentarioRepository
{
    public function save(Comentario $comentario): int
    {
        return 0;
    }

    private function convertDatabaseToObject(array $row): Comentario
    {
        return new Comentario(
            id: (int) $row['usu_id'],
            userId: (int) $row['usu_user_id'],
            dataDePostagem: $row['usu_data_de_postagem'],
            conteudo: $row['usu_conteudo'],
            publicavelId: $row['usu_publicabel_id'],
        );
    }
}