<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Denuncia;

// SQL sobre a tabela `denuncias`.
class DenunciaRepository
{
    public function findById(int $id): ?Denuncia
    {
        $stmt = Database::connection()->prepare('SELECT * FROM denuncias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Fila de moderacao: por padrao lista o que ainda esta pendente/em analise.
    public function all(?string $status = null): array
    {
        $sql = 'SELECT * FROM denuncias';
        $params = [];

        if ($status !== null) {
            $sql .= ' WHERE status_denuncia = :status';
            $params['status'] = $status;
        }

        $sql .= ' ORDER BY data_denuncia DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(Denuncia $d): int
    {
        $pdo = Database::connection();

        if ($d->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO denuncias (id_denunciante, id_denunciado, motivo, status_denuncia)
                 VALUES (:denunciante, :denunciado, :motivo, :status)'
            );
            $stmt->execute([
                'denunciante' => $d->idDenunciante,
                'denunciado' => $d->idDenunciado,
                'motivo' => $d->motivo,
                'status' => $d->statusDenuncia,
            ]);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE denuncias SET
                motivo = :motivo,
                status_denuncia = :status,
                observacao_moderador = :obs,
                id_moderador = :moderador,
                data_analise = :data_analise
             WHERE id = :id'
        );
        $stmt->execute([
            'motivo' => $d->motivo,
            'status' => $d->statusDenuncia,
            'obs' => $d->observacaoModerador,
            'moderador' => $d->idModerador,
            'data_analise' => $d->dataAnalise,
            'id' => $d->id,
        ]);

        return $d->id;
    }

    // Encerra a analise: grava parecer do moderador e carimba a data.
    public function analisar(int $id, int $idModerador, string $status, ?string $observacao = null): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE denuncias SET
                status_denuncia = :status,
                id_moderador = :moderador,
                observacao_moderador = :obs,
                data_analise = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        return $stmt->execute([
            'status' => $status,
            'moderador' => $idModerador,
            'obs' => $observacao,
            'id' => $id,
        ]);
    }

    private function hydrate(array $row): Denuncia
    {
        return new Denuncia(
            id: (int) $row['id'],
            idDenunciante: isset($row['id_denunciante']) ? (int) $row['id_denunciante'] : null,
            idDenunciado: isset($row['id_denunciado']) ? (int) $row['id_denunciado'] : null,
            motivo: (string) $row['motivo'],
            statusDenuncia: (string) $row['status_denuncia'],
            observacaoModerador: $row['observacao_moderador'] ?? null,
            idModerador: isset($row['id_moderador']) ? (int) $row['id_moderador'] : null,
            dataDenuncia: $row['data_denuncia'] ?? null,
            dataAnalise: $row['data_analise'] ?? null,
        );
    }
}
