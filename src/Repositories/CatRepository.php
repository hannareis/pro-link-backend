<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Cat;

// SQL sobre a tabela `cats`.
class CatRepository
{
    public function findById(int $id): ?Cat
    {
        $stmt = Database::connection()->prepare('SELECT * FROM cats WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByCodigoAutenticidade(string $codigo): ?Cat
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM cats WHERE codigo_autenticidade = :c LIMIT 1'
        );
        $stmt->execute(['c' => $codigo]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listByPortfolio(int $idPortfolio): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM cats WHERE id_portfolio = :id ORDER BY data_emissao DESC, id DESC'
        );
        $stmt->execute(['id' => $idPortfolio]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(Cat $c): int
    {
        $pdo = Database::connection();
        $params = [
            'id_portfolio' => $c->idPortfolio,
            'numero_certidao' => $c->numeroCertidao,
            'codigo_autenticidade' => $c->codigoAutenticidade,
            'data_emissao' => $c->dataEmissao,
            'validade' => $c->validade,
            'status_cat' => $c->statusCat,
            'id_profissional_responsavel' => $c->idProfissionalResponsavel,
            'id_contratante' => $c->idContratante,
            'id_proprietario' => $c->idProprietario,
            'documento_cat' => $c->documentoCat,
            'observacoes' => $c->observacoes,
        ];

        if ($c->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO cats
                    (id_portfolio, numero_certidao, codigo_autenticidade, data_emissao, validade,
                     status_cat, id_profissional_responsavel, id_contratante, id_proprietario,
                     documento_cat, observacoes)
                 VALUES
                    (:id_portfolio, :numero_certidao, :codigo_autenticidade, :data_emissao, :validade,
                     :status_cat, :id_profissional_responsavel, :id_contratante, :id_proprietario,
                     :documento_cat, :observacoes)'
            );
            $stmt->execute($params);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE cats SET
                id_portfolio = :id_portfolio,
                numero_certidao = :numero_certidao,
                codigo_autenticidade = :codigo_autenticidade,
                data_emissao = :data_emissao,
                validade = :validade,
                status_cat = :status_cat,
                id_profissional_responsavel = :id_profissional_responsavel,
                id_contratante = :id_contratante,
                id_proprietario = :id_proprietario,
                documento_cat = :documento_cat,
                observacoes = :observacoes
             WHERE id = :id'
        );
        $stmt->execute($params + ['id' => $c->id]);

        return $c->id;
    }

    public function atualizarStatus(int $id, string $status): bool
    {
        $stmt = Database::connection()->prepare('UPDATE cats SET status_cat = :s WHERE id = :id');

        return $stmt->execute(['s' => $status, 'id' => $id]);
    }

    // Marca como EXPIRADA toda CAT valida cuja data de validade ja passou.
    public function expirarVencidas(): int
    {
        $stmt = Database::connection()->query(
            "UPDATE cats
                SET status_cat = 'EXPIRADA'
              WHERE status_cat = 'VALIDA' AND validade IS NOT NULL AND validade < CURDATE()"
        );

        return $stmt->rowCount();
    }

    public function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM cats WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Cat
    {
        return new Cat(
            id: (int) $row['id'],
            idPortfolio: (int) $row['id_portfolio'],
            numeroCertidao: (string) $row['numero_certidao'],
            codigoAutenticidade: (string) $row['codigo_autenticidade'],
            dataEmissao: $row['data_emissao'] ?? null,
            validade: $row['validade'] ?? null,
            statusCat: (string) $row['status_cat'],
            idProfissionalResponsavel: (int) $row['id_profissional_responsavel'],
            idContratante: isset($row['id_contratante']) ? (int) $row['id_contratante'] : null,
            idProprietario: isset($row['id_proprietario']) ? (int) $row['id_proprietario'] : null,
            documentoCat: $row['documento_cat'] ?? null,
            observacoes: $row['observacoes'] ?? null,
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}
