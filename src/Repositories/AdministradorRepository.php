<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Administrador;

// SQL sobre a tabela `administradores` (1:1 com `usuarios`).
class AdministradorRepository
{
    public function findByUsuarioId(int $idUsuario): ?Administrador
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM administradores WHERE id_usuario = :id LIMIT 1'
        );
        $stmt->execute(['id' => $idUsuario]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function all(): array
    {
        $stmt = Database::connection()->query(
            'SELECT a.*, u.nome, u.email
               FROM administradores a
               JOIN usuarios u ON u.id = a.id_usuario
           ORDER BY u.nome'
        );

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function isAdmin(int $idUsuario): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT 1 FROM administradores WHERE id_usuario = :id LIMIT 1'
        );
        $stmt->execute(['id' => $idUsuario]);

        return $stmt->fetchColumn() !== false;
    }

    public function save(Administrador $admin): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO administradores (id_usuario) VALUES (:id_usuario)'
        );
        $stmt->execute(['id_usuario' => $admin->idUsuario]);

        return $admin->idUsuario;
    }

    public function delete(int $idUsuario): bool
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM administradores WHERE id_usuario = :id'
        );

        return $stmt->execute(['id' => $idUsuario]);
    }

    private function hydrate(array $row): Administrador
    {
        return new Administrador(
            idUsuario: (int) $row['id_usuario'],
            criadoEm: $row['criado_em'] ?? null,
        );
    }
}
