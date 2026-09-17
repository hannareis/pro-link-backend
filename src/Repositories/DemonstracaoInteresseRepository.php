<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\DemonstracaoInteresse;

// SQL sobre a tabela `demonstracoes_interesse` (unica por demanda+usuario).
class DemonstracaoInteresseRepository
{
    public function findById(int $id): ?DemonstracaoInteresse
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM demonstracoes_interesse WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByDemandaUsuario(int $idDemanda, int $idUsuario): ?DemonstracaoInteresse
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM demonstracoes_interesse
              WHERE id_demanda = :d AND id_usuario = :u LIMIT 1'
        );
        $stmt->execute(['d' => $idDemanda, 'u' => $idUsuario]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listByDemanda(int $idDemanda): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT d.*, u.nome as nome_usuario, dm.titulo as titulo_demanda 
             FROM demonstracoes_interesse d
             JOIN usuarios u ON d.id_usuario = u.id
             JOIN demandas dm ON d.id_demanda = dm.id
             WHERE d.id_demanda = :d 
             ORDER BY d.data_interesse DESC'
        );
        $stmt->execute(['d' => $idDemanda]);

        $rows = $stmt->fetchAll();
        $interesses = array_map($this->hydrate(...), $rows);

        foreach ($interesses as $index => $interesse) {
            $interesse->nome_usuario = $rows[$index]['nome_usuario'];
            $interesse->titulo_demanda = $rows[$index]['titulo_demanda'];
        }

        return $interesses;
    }

    public function listByUsuario(int $idUsuario): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT d.*, u.nome as nome_usuario, dm.titulo as titulo_demanda 
             FROM demonstracoes_interesse d
             JOIN usuarios u ON d.id_usuario = u.id
             JOIN demandas dm ON d.id_demanda = dm.id
             WHERE d.id_usuario = :u 
             ORDER BY d.data_interesse DESC'
        );
        $stmt->execute(['u' => $idUsuario]);

        $rows = $stmt->fetchAll();
        $interesses = array_map($this->hydrate(...), $rows);

        foreach ($interesses as $index => $interesse) {
            $interesse->nome_usuario = $rows[$index]['nome_usuario'];
            $interesse->titulo_demanda = $rows[$index]['titulo_demanda'];
        }

        return $interesses;
    }

    public function save(DemonstracaoInteresse $d): int
    {
        $pdo = Database::connection();

        if ($d->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO demonstracoes_interesse
                    (id_demanda, id_usuario, titulo, mensagem, status)
                 VALUES (:id_demanda, :id_usuario, :titulo, :mensagem, :status)'
            );
            $stmt->execute([
                'id_demanda' => $d->idDemanda,
                'id_usuario' => $d->idUsuario,
                'titulo' => $d->titulo,
                'mensagem' => $d->mensagem,
                'status' => $d->status,
            ]);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE demonstracoes_interesse SET
                titulo = :titulo, mensagem = :mensagem, status = :status
             WHERE id = :id'
        );
        $stmt->execute([
            'titulo' => $d->titulo,
            'mensagem' => $d->mensagem,
            'status' => $d->status,
            'id' => $d->id,
        ]);

        return $d->id;
    }

    public function atualizarStatus(int $id, string $status): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE demonstracoes_interesse SET status = :s WHERE id = :id'
        );

        return $stmt->execute(['s' => $status, 'id' => $id]);
    }

    private function hydrate(array $row): DemonstracaoInteresse
    {
        return new DemonstracaoInteresse(
            id: (int) $row['id'],
            idDemanda: (int) $row['id_demanda'],
            idUsuario: (int) $row['id_usuario'],
            titulo: (string) $row['titulo'],
            mensagem: (string) $row['mensagem'],
            status: (string) $row['status'],
            dataInteresse: $row['data_interesse'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}
