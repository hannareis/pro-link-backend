<?php

declare(strict_types= 1);

namespace App\Repositories;

use App\Models\Post;
use App\Core\Database;
use DateTime;

class PostRepository
{
    public function save(Post $post): int
    {
        $pdo = Database::connection();

        if ($post->id === null)
        {
            $stmt = $pdo->prepare
            (
                'INSERT INTO posts (id_autor, titulo, conteudo, status_post, data_postagem) VALUES (:idAutor, :titulo, :conteudo, :statusPost, :dataPostagem)'
            );
            $stmt->execute
            (
                [
                    'idAutor' => $post->userId,
                    'titulo' => $post->titulo,
                    'conteudo' => $post->conteudo,
                    'statusPost' => $post->status,
                    'dataPostagem' => $post->dataDePostagem->format('Y-m-d H:i:s')
                ]
            );
            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare
        (
            'UPDATE posts SET titulo = :titulo, conteudo = :conteudo, status_post = :statusPost WHERE id = :id'
        );
        $stmt->execute
        (
            [
                'titulo' => $post->titulo,
                'conteudo' => $post->conteudo,
                'statusPost' => $post->status,
                'id' => $post->id
            ]
        );
        return $post->id;
    }

    public function findById(int $id): ?Post
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare
        (
            'SELECT * FROM posts WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function delete(?Post $post): bool
    {
        if ($post === null) return false;

        $pdo = Database::connection();

        $stmt = $pdo->prepare
        (
            'DELETE FROM posts WHERE id = :id'
        );
        $stmt->execute(['id' => $post->id]);

        return $stmt->rowCount() > 0;
    }
    
    public function hydrate(array $row): Post
    {
        return new Post(
            id: (int) $row['id'],
            userId: (int) $row['id_autor'],
            dataDePostagem: new DateTime($row['data_postagem']),
            dataEdicao: new DateTime($row['atualizado_em']),
            conteudo: $row['conteudo'],
            titulo: $row['titulo'] ?? '',
            anexos: [],
            status: (string) $row['status_post']
        );
    }
    
}