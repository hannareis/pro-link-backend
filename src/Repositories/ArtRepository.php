<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Art;

// SQL sobre a tabela `arts`.
class ArtRepository
{
    public function findById(int $id): ?Art
    {
        $stmt = Database::connection()->prepare('SELECT * FROM arts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByNumero(string $numeroArt): ?Art
    {
        $stmt = Database::connection()->prepare('SELECT * FROM arts WHERE numero_art = :n LIMIT 1');
        $stmt->execute(['n' => $numeroArt]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listByPortfolio(int $idPortfolio): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM arts WHERE id_portfolio = :id ORDER BY data_emissao DESC, id DESC'
        );
        $stmt->execute(['id' => $idPortfolio]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(Art $a): int
    {
        $pdo = Database::connection();
        $params = [
            'id_portfolio' => $a->idPortfolio,
            'id_profissional_responsavel' => $a->idProfissionalResponsavel,
            'numero_art' => $a->numeroArt,
            'tipo_art' => $a->tipoArt,
            'status_art' => $a->statusArt,
            'validada_por_crea' => $a->validadaPorCrea ? 1 : 0,
            'data_emissao' => $a->dataEmissao,
            'data_validacao' => $a->dataValidacao,
            'documento_art' => $a->documentoArt,
            'observacoes' => $a->observacoes,
        ];

        if ($a->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO arts
                    (id_portfolio, id_profissional_responsavel, numero_art, tipo_art, status_art,
                     validada_por_crea, data_emissao, data_validacao, documento_art, observacoes)
                 VALUES
                    (:id_portfolio, :id_profissional_responsavel, :numero_art, :tipo_art, :status_art,
                     :validada_por_crea, :data_emissao, :data_validacao, :documento_art, :observacoes)'
            );
            $stmt->execute($params);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE arts SET
                id_portfolio = :id_portfolio,
                id_profissional_responsavel = :id_profissional_responsavel,
                numero_art = :numero_art,
                tipo_art = :tipo_art,
                status_art = :status_art,
                validada_por_crea = :validada_por_crea,
                data_emissao = :data_emissao,
                data_validacao = :data_validacao,
                documento_art = :documento_art,
                observacoes = :observacoes
             WHERE id = :id'
        );
        $stmt->execute($params + ['id' => $a->id]);

        return $a->id;
    }

    // Validacao pelo CREA (RF02): muda status e marca data_validacao.
    public function validar(int $id, string $novoStatus = Art::STATUS_APROVADA): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE arts SET
                status_art = :status,
                validada_por_crea = 1,
                data_validacao = CURRENT_TIMESTAMP
             WHERE id = :id'
        );

        return $stmt->execute(['status' => $novoStatus, 'id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM arts WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Art
    {
        return new Art(
            id: (int) $row['id'],
            idPortfolio: (int) $row['id_portfolio'],
            idProfissionalResponsavel: (int) $row['id_profissional_responsavel'],
            numeroArt: (string) $row['numero_art'],
            tipoArt: $row['tipo_art'] ?? null,
            statusArt: (string) $row['status_art'],
            validadaPorCrea: (bool) $row['validada_por_crea'],
            dataEmissao: $row['data_emissao'] ?? null,
            dataValidacao: $row['data_validacao'] ?? null,
            documentoArt: $row['documento_art'] ?? null,
            observacoes: $row['observacoes'] ?? null,
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}
