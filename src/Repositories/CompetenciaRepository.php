<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Competencia;

// SQL sobre a tabela `competencias` (catalogo).
class CompetenciaRepository
{
    public function findById(int $id): ?Competencia
    {
        $stmt = Database::connection()->prepare('SELECT * FROM competencias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function all(bool $somenteAtivas = true): array
    {
        $sql = 'SELECT * FROM competencias';
        if ($somenteAtivas) {
            $sql .= ' WHERE ativo = 1';
        }
        $sql .= ' ORDER BY nome';

        return array_map($this->hydrate(...), Database::connection()->query($sql)->fetchAll());
    }

    public function save(Competencia $c): int
    {
        $pdo = Database::connection();

        if ($c->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO competencias (nome, descricao, ativo) VALUES (:nome, :descricao, :ativo)'
            );
            $stmt->execute([
                'nome' => $c->nome,
                'descricao' => $c->descricao,
                'ativo' => $c->ativo ? 1 : 0,
            ]);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE competencias SET nome = :nome, descricao = :descricao, ativo = :ativo WHERE id = :id'
        );
        $stmt->execute([
            'nome' => $c->nome,
            'descricao' => $c->descricao,
            'ativo' => $c->ativo ? 1 : 0,
            'id' => $c->id,
        ]);

        return $c->id;
    }

    // Competencia e referenciada por FK RESTRICT: desativa em vez de apagar.
    public function desativar(int $id): bool
    {
        $stmt = Database::connection()->prepare('UPDATE competencias SET ativo = 0 WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Competencia
    {
        return new Competencia(
            id: (int) $row['id'],
            nome: (string) $row['nome'],
            descricao: $row['descricao'] ?? null,
            ativo: (bool) $row['ativo'],
            criadoEm: $row['criado_em'] ?? null,
        );
    }
}
