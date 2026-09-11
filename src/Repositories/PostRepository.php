<?php

declare(strict_types= 1);

namespace App\Repositories;

use App\Models\Post;

class PostRepository
{
    public function save(Post $post): int
    {
        return 0;
    }

    public function convertDatabaseToObject(array $row): Post
    {
        return new Post(
            id: (int) $row['usu_id'],
            userId: (int) $row['usu_user_id'],
            dataDePostagem: $row['usu_data_de_postasgem'],
            conteudo: $row['usu_conteudo'],
            titulo: $row['usu_titulo'],
            anexos: (array) $row['usu_anexos'],
        );
    }
}