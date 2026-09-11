<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Especialidade;

// SQL sobre a tabela `especialidades` (catalogo).
class EspecialidadeRepository
{
    public function findById(int $id): ?Especialidade
    {
        $stmt = Database::connection()->prepare('SELECT * FROM especialidades WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function all(bool $somenteAtivas = true): array
    {
        $sql = 'SELECT * FROM especialidades';
        if ($somenteAtivas) {
            $sql .= ' WHERE ativo = 1';
        }
        $sql .= ' ORDER BY nome';

        return array_map($this->hydrate(...), Database::connection()->query($sql)->fetchAll());
    }

    public function save(Especialidade $e): int
    {
        $pdo = Database::connection();

        if ($e->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO especialidades (nome, descricao, ativo) VALUES (:nome, :descricao, :ativo)'
            );
            $stmt->execute([
                'nome' => $e->nome,
                'descricao' => $e->descricao,
                'ativo' => $e->ativo ? 1 : 0,
            ]);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE especialidades SET nome = :nome, descricao = :descricao, ativo = :ativo WHERE id = :id'
        );
        $stmt->execute([
            'nome' => $e->nome,
            'descricao' => $e->descricao,
            'ativo' => $e->ativo ? 1 : 0,
            'id' => $e->id,
        ]);

        return $e->id;
    }

    public function desativar(int $id): bool
    {
        $stmt = Database::connection()->prepare('UPDATE especialidades SET ativo = 0 WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Especialidade
    {
        return new Especialidade(
            id: (int) $row['id'],
            nome: (string) $row['nome'],
            descricao: $row['descricao'] ?? null,
            ativo: (bool) $row['ativo'],
            criadoEm: $row['criado_em'] ?? null,
        );
    }
}
