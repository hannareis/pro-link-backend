<?php

namespace App\Repositories;

use App\Models\Comentario;
use App\Core\Database;

use DateTime;

class ComentarioRepository
{
    public function save(Comentario $comentario): int
    {
        $pdo = Database::connection();

        if ($comentario->id === null)
            {
                $stmt = $pdo->prepare
                (
                    'INSERT INTO comentarios (id_post, id_autor, id_comentario_pai, conteudo, status_comentario, data_comentario, atualizado_em) VALUES (:id, :postId, :userId, :comentarioPaiId, :conteudo, :statusComent, :dataDePostagem)'
                );
                $stmt->execute
                (
                    [
                        'postId' => $comentario->postId,
                        'userId' => $comentario->userId,
                        'comentarioPaiId' => $comentario->comentarioPaiId,
                        'conteudo' => $comentario->conteudo,
                        'statusComent' => $comentario->status,
                        'dataDePostagem' => $comentario->dataDePostagem
                    ]
                );
                return (int) $pdo->lastInsertId();
            }
        $stmt = $pdo->prepare
        (
            'UPDATE comentarios SET conteudo = :conteudo, statusComent = :statusComent WHERE id = :id'
        );
        $stmt->execute
        (
            [
                'conteudo' => $comentario->conteudo,
                'statusComent' => $comentario->status,
                'id' => $comentario->id
            ]
        );
        return (int) $comentario->id;
    }

    public function delete(?Comentario $comentario): bool
    {
        if ($comentario === null) return false;

        $pdo = Database::connection();

        $stmt = $pdo->prepare
        (
            'DELETE FROM comentarios WHERE id = :id'
        );
        $stmt->execute(['id' => $comentario->id]);

        return $stmt->rowCount() > 0;

    }

    public function findById(int $id): ?Comentario
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare
        (
            'SELECT * FROM comentarios WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): Comentario
    {
        return new Comentario(
            id: (int) $row['id'],
            userId: (int) $row['id_autor'],
            dataDePostagem: new DateTime($row['data_comentario']),
            dataEdicao: new DateTime($row['atualizado_em']),
            conteudo: $row['conteudo'],
            postId: (int) $row['id_post'],
            comentarioPaiId: (int) $row['id_comentario_pai'],
            status: $row['status_comentario']
        );
    }
}