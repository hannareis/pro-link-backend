<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Post;

class PostRepository
{
    public function findById(int $id): ?Post
    {
        $stmt = Database::connection()->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listByAutor(int $idAutor): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM posts WHERE id_autor = :id ORDER BY data_postagem DESC, id DESC'
        );
        $stmt->execute(['id' => $idAutor]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function listById(int $id): array
    {
        return $this->listByAutor($id);
    }

    public function all(int $limit = 50): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM posts WHERE status_post = :status ORDER BY data_postagem DESC, id DESC LIMIT :limit'
        );
        $stmt->bindValue(':status', Post::STATUS_PUBLICO);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(Post $post): int
    {
        $pdo = Database::connection();

        if ($post->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO posts
                    (id_autor, titulo, conteudo, status_post)
                 VALUES
                    (:idAutor, :titulo, :conteudo, :statusPost)'
            );
            $stmt->execute([
                'idAutor' => $post->userId,
                'titulo' => $post->titulo,
                'conteudo' => $post->conteudo,
                'statusPost' => $post->status,
            ]);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE posts SET
                titulo = :titulo,
                conteudo = :conteudo,
                status_post = :statusPost
             WHERE id = :id'
        );
        $stmt->execute([
            'titulo' => $post->titulo,
            'conteudo' => $post->conteudo,
            'statusPost' => $post->status,
            'id' => $post->id,
        ]);

        return $post->id;
    }

    public function delete(int|Post $target): bool
    {
        $id = $target instanceof Post ? $target->id : $target;
        if ($id === null) {
            return false;
        }

        $stmt = Database::connection()->prepare('DELETE FROM posts WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Post
    {
        return new Post(
            id: (int) $row['id'],
            userId: (int) $row['id_autor'],
            dataDePostagem: $row['data_postagem'] ?? null,
            dataEdicao: $row['atualizado_em'] ?? null,
            conteudo: (string) $row['conteudo'],
            status: (string) $row['status_post'],
            titulo: (string) ($row['titulo'] ?? ''),
        );
    }
}
