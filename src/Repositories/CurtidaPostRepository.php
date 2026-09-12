<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Curtida;

class CurtidaPostRepository
{
    public function findByUsuarioEPost(int $userId, int $postId): ?Curtida
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM likes_posts WHERE id_usuario = :userId AND id_post = :postId LIMIT 1'
        );
        $stmt->execute([
            'userId' => $userId,
            'postId' => $postId,
        ]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listByPost(int $postId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM likes_posts WHERE id_post = :postId ORDER BY data_curtida DESC'
        );
        $stmt->execute(['postId' => $postId]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function listByUsuario(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM likes_posts WHERE id_usuario = :userId ORDER BY data_curtida DESC'
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
            'INSERT IGNORE INTO likes_posts (id_usuario, id_post)
             VALUES (:userId, :postId)'
        );
        $stmt->execute([
            'userId' => $curtida->userId,
            'postId' => $curtida->publicavelId,
        ]);

        return $curtida->publicavelId;
    }

    public function delete(?Curtida $curtida): bool
    {
        if ($curtida === null || $curtida->userId === null || $curtida->publicavelId === null) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'DELETE FROM likes_posts WHERE id_usuario = :userId AND id_post = :postId'
        );

        return $stmt->execute([
            'userId' => $curtida->userId,
            'postId' => $curtida->publicavelId,
        ]);
    }

    private function hydrate(array $row): Curtida
    {
        return new Curtida(
            userId: (int) $row['id_usuario'],
            publicavelId: (int) $row['id_post'],
            data: $row['data_curtida'] ?? null
        );
    }
}
