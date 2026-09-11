<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Curtida;
use App\Core\Database;
use DateTime;

class CurtidaComentarioRepository
{
    public function save(Curtida $curtida): int
    {
        $pdo = Database::connection();

        if($curtida->userId === null || $curtida->publicavelId === null)
            {
                $stmt = $pdo->prepare
                (
                    'INSERT INTO likes_comentarios (id_usuario, id_comentario, data_curtida) VALUES (:userId, :publicavelId, :dataCurtida)'
                );
                $stmt->execute
                (
                    [
                        'userId' => $curtida->userId,
                        'publicavelId' => $curtida->publicavelId,
                        'dataCurtida' => $curtida->data
                    ]
                );
                return (int) $pdo->lastInsertId();
            }
            return $curtida->publicavelId;
    }

    public function findByUsuarioEComentario(int $userId, int $publicavelId): ?Curtida
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare
        (
            'SELECT * FROM likes_comentarios WHERE id_usuario = :userId AND id_comentario = :publicavelId'
        );
        $stmt->execute
        (
            [
                'userId' => $userId,
                'publicavelId' => $publicavelId
            ]
        );
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function delete(?Curtida $curtida): bool
    {
        if ($curtida === null) return false;

        $pdo = Database::connection();

        $stmt = $pdo->prepare
        (
            'DELETE FROM likes_comentarios WHERE id_usuario = :userId AND id_comentario = :comentarioId'
        );
        $stmt->execute
        (
            [
                'userId' => $curtida->userId,
                'comentarioId' => $curtida->publicavelId
            ]
        );
        return $stmt->rowCount() > 0;
    }

    public function hydrate(array $row): Curtida
    {
        return new Curtida
        (
            userId: (int) $row['id_usuario'],
            publicavelId: (int) $row['id_comentario'],
            data: new DateTime($row['data_curtida'])
        );
    }
}