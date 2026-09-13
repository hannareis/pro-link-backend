<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Curtida;

class CurtidaComentarioRepository
{
    public function findByUsuarioEComentario(int $userId, int $comentarioId): ?Curtida
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM likes_comentarios WHERE id_usuario = :userId AND id_comentario = :comentarioId LIMIT 1'
        );
        $stmt->execute([
            'userId' => $userId,
            'comentarioId' => $comentarioId,
        ]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listByComentario(int $comentarioId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM likes_comentarios WHERE id_comentario = :comentarioId ORDER BY data_curtida DESC'
        );
        $stmt->execute(['comentarioId' => $comentarioId]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function listByUsuario(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM likes_comentarios WHERE id_usuario = :userId ORDER BY data_curtida DESC'
        );
        $stmt->execute(['userId' => $userId]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(Curtida $curtida): int
    {
        if ($curtida->userId === null || $curtida->publicavelId === null) {
            return 0;
        }

        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO likes_comentarios (id_usuario, id_comentario)
             VALUES (:userId, :comentarioId)'
        );
        $stmt->execute([
            'userId' => $curtida->userId,
            'comentarioId' => $curtida->publicavelId,
        ]);

        return $curtida->publicavelId;
    }

    public function delete(?Curtida $curtida): bool
    {
        if ($curtida === null || $curtida->userId === null || $curtida->publicavelId === null) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'DELETE FROM likes_comentarios WHERE id_usuario = :userId AND id_comentario = :comentarioId'
        );

        return $stmt->execute([
            'userId' => $curtida->userId,
            'comentarioId' => $curtida->publicavelId,
        ]);
    }

    private function hydrate(array $row): Curtida
    {
        return new Curtida(
            userId: (int) $row['id_usuario'],
            publicavelId: (int) $row['id_comentario'],
            data: $row['data_curtida'] ?? null
        );
    }
}
