<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Universidade;

// SQL sobre a tabela `universidades`.
class UniversidadeRepository
{
    public function findById(int $id): ?Universidade
    {
        $stmt = Database::connection()->prepare('SELECT * FROM universidades WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByNome(string $nome): ?Universidade
    {
        $stmt = Database::connection()->prepare('SELECT * FROM universidades WHERE nome = :nome LIMIT 1');
        $stmt->execute(['nome' => strtolower($nome)]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findBySigla(string $sigla): ?Universidade
    {
        $stmt = Database::connection()->prepare('SELECT * FROM universidades WHERE sigla = :sigla LIMIT 1');
        $stmt->execute(['sigla' => strtolower($sigla)]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Lista universidades; por padrao apenas as ativas (catalogo publico).
    public function all(bool $somenteAtivas = true): array
    {
        $sql = 'SELECT * FROM universidades';
        if ($somenteAtivas) {
            $sql .= ' WHERE ativa = 1';
        }
        $sql .= ' ORDER BY nome';

        return array_map($this->hydrate(...), Database::connection()->query($sql)->fetchAll());
    }

    public function search(string $termo): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM universidades
              WHERE ativa = 1 AND (nome LIKE :termo OR sigla LIKE :termo)
           ORDER BY nome
              LIMIT 50'
        );
        $stmt->execute(['termo' => '%' . $termo . '%']);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(Universidade $u): int
    {
        $pdo = Database::connection();
        $params = [
            'nome' => $u->nome,
            'sigla' => $u->sigla,
            'cnpj' => $u->cnpj,
            'tipo' => $u->tipo,
            'cidade' => $u->cidade,
            'estado' => $u->estado,
            'site' => $u->site,
            'ativa' => $u->ativa ? 1 : 0,
        ];

        if ($u->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO universidades (nome, sigla, cnpj, tipo, cidade, estado, site, ativa)
                 VALUES (:nome, :sigla, :cnpj, :tipo, :cidade, :estado, :site, :ativa)'
            );
            $stmt->execute($params);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE universidades SET
                nome = :nome, sigla = :sigla, cnpj = :cnpj, tipo = :tipo,
                cidade = :cidade, estado = :estado, site = :site, ativa = :ativa
             WHERE id = :id'
        );
        $stmt->execute($params + ['id' => $u->id]);

        return $u->id;
    }

    // Exclusao logica: universidade com universitarios vinculados nao pode ser apagada (FK RESTRICT).
    public function desativar(int $id): bool
    {
        $stmt = Database::connection()->prepare('UPDATE universidades SET ativa = 0 WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Universidade
    {
        return new Universidade(
            id: (int) $row['id'],
            nome: (string) $row['nome'],
            sigla: $row['sigla'] ?? null,
            cnpj: $row['cnpj'] ?? null,
            tipo: (string) $row['tipo'],
            cidade: $row['cidade'] ?? null,
            estado: $row['estado'] ?? null,
            site: $row['site'] ?? null,
            ativa: (bool) $row['ativa'],
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}
