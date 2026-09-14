<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Portfolio;

// SQL sobre a tabela `portfolio` do estrutura.sql (1:1 com `pessoa_fisica`).
class PortfolioRepository
{
    public function findById(int $id): ?Portfolio
    {
        $stmt = Database::connection()->prepare('SELECT * FROM portfolio WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Busca o portfolio de um usuario pelo id do usuario.
    public function findByUsuarioId(int $idUsuario): ?Portfolio
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM portfolio WHERE id_usuario = :id_usuario LIMIT 1'
        );
        $stmt->execute(['id_usuario' => $idUsuario]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Insere ou atualiza um portfolio e retorna o id persistido.
    public function save(Portfolio $portfolio): int
    {
        $pdo = Database::connection();
        $linksContato = json_encode($portfolio->linksContato, JSON_UNESCAPED_UNICODE);

        if ($portfolio->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO portfolio (id_usuario, resumo_profissional, documento_identificacao, links_contato)
                 VALUES (:id_usuario, :resumo_profissional, :documento_identificacao, :links_contato)'
            );
            $stmt->execute([
                'id_usuario' => $portfolio->idUsuario,
                'resumo_profissional' => $portfolio->resumoProfissional,
                'documento_identificacao' => $portfolio->documentoIdentificacao,
                'links_contato' => $linksContato,
            ]);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE portfolio SET
                resumo_profissional = :resumo_profissional,
                documento_identificacao = :documento_identificacao,
                links_contato = :links_contato
             WHERE id = :id'
        );
        $stmt->execute([
            'resumo_profissional' => $portfolio->resumoProfissional,
            'documento_identificacao' => $portfolio->documentoIdentificacao,
            'links_contato' => $linksContato,
            'id' => $portfolio->id,
        ]);

        return $portfolio->id;
    }

    public function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM portfolio WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Portfolio
    {
        return new Portfolio(
            id: (int) $row['id'],
            idUsuario: (int) $row['id_usuario'],
            resumoProfissional: $row['resumo_profissional'] ?? null,
            documentoIdentificacao: $row['documento_identificacao'] ?? null,
            linksContato: $this->decodeJson($row['links_contato'] ?? null),
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }

    private function decodeJson(?string $json): array
    {
        if ($json === null || $json === '') {
            return [];
        }

        return json_decode($json, true) ?? [];
    }
}
