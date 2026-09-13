<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Comentario;

class ComentarioRepository
{
    public function findById(int $id): ?Comentario
    {
        $stmt = Database::connection()->prepare('SELECT * FROM comentarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listByPost(int $idPost): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM comentarios WHERE id_post = :id ORDER BY data_comentario DESC, id DESC'
        );
        $stmt->execute(['id' => $idPost]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function listById(int $id): array
    {
        return $this->listByPost($id);
    }

    public function save(Comentario $comentario): int
    {
        $pdo = Database::connection();

        if ($comentario->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO comentarios
                    (id_post, id_autor, id_comentario_pai, conteudo, status_comentario)
                 VALUES
                    (:postId, :userId, :comentarioPaiId, :conteudo, :statusComentario)'
            );
            $stmt->execute([
                'postId' => $comentario->postId,
                'userId' => $comentario->userId,
                'comentarioPaiId' => $comentario->comentarioPaiId,
                'conteudo' => $comentario->conteudo,
                'statusComentario' => $comentario->status,
            ]);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE comentarios SET
                conteudo = :conteudo,
                status_comentario = :statusComentario
             WHERE id = :id'
        );
        $stmt->execute([
            'conteudo' => $comentario->conteudo,
            'statusComentario' => $comentario->status,
            'id' => $comentario->id,
        ]);

        return (int) $comentario->id;
    }

    public function delete(int|Comentario $target): bool
    {
        $id = $target instanceof Comentario ? $target->id : $target;
        if ($id === null) {
            return false;
        }

        $stmt = Database::connection()->prepare('DELETE FROM comentarios WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Comentario
    {
        return new Comentario(
            id: (int) $row['id'],
            userId: (int) $row['id_autor'],
            dataDePostagem: $row['data_comentario'] ?? null,
            dataEdicao: $row['atualizado_em'] ?? null,
            conteudo: (string) $row['conteudo'],
            status: (string) $row['status_comentario'],
            postId: isset($row['id_post']) ? (int) $row['id_post'] : null,
            comentarioPaiId: isset($row['id_comentario_pai']) ? (int) $row['id_comentario_pai'] : null,
        );
    }
}
