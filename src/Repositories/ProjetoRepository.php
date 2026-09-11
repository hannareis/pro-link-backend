<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Projeto;

// SQL sobre a tabela `projetos` e o vinculo `projeto_competencias`.
class ProjetoRepository
{
    public function findById(int $id): ?Projeto
    {
        $stmt = Database::connection()->prepare('SELECT * FROM projetos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $projeto = $this->hydrate($row);
        $projeto->competencias = $this->competenciasDoProjeto($id);

        return $projeto;
    }

    public function listByPortfolio(int $idPortfolio): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM projetos WHERE id_portfolio = :id ORDER BY data_inicio DESC, id DESC'
        );
        $stmt->execute(['id' => $idPortfolio]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(Projeto $p): int
    {
        $pdo = Database::connection();
        $params = [
            'id_portfolio' => $p->idPortfolio,
            'titulo' => $p->titulo,
            'descricao' => $p->descricao,
            'links_referencia' => json_encode($p->linksReferencia, JSON_UNESCAPED_UNICODE),
            'data_inicio' => $p->dataInicio,
            'data_fim' => $p->dataFim,
            'resultados_mencionaveis' => json_encode($p->resultadosMencionaveis, JSON_UNESCAPED_UNICODE),
        ];

        if ($p->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO projetos
                    (id_portfolio, titulo, descricao, links_referencia, data_inicio, data_fim, resultados_mencionaveis)
                 VALUES
                    (:id_portfolio, :titulo, :descricao, :links_referencia, :data_inicio, :data_fim, :resultados_mencionaveis)'
            );
            $stmt->execute($params);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE projetos SET
                id_portfolio = :id_portfolio,
                titulo = :titulo,
                descricao = :descricao,
                links_referencia = :links_referencia,
                data_inicio = :data_inicio,
                data_fim = :data_fim,
                resultados_mencionaveis = :resultados_mencionaveis
             WHERE id = :id'
        );
        $stmt->execute($params + ['id' => $p->id]);

        return $p->id;
    }

    public function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM projetos WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    // ----- projeto_competencias ---------------------------------------------------

    public function competenciasDoProjeto(int $idProjeto): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT c.id, c.nome
               FROM projeto_competencias pc
               JOIN competencias c ON c.id = pc.id_competencia
              WHERE pc.id_projeto = :id'
        );
        $stmt->execute(['id' => $idProjeto]);

        return $stmt->fetchAll();
    }

    public function sincronizarCompetencias(int $idProjeto, array $competenciaIds): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $del = $pdo->prepare('DELETE FROM projeto_competencias WHERE id_projeto = :id');
            $del->execute(['id' => $idProjeto]);

            $ins = $pdo->prepare(
                'INSERT INTO projeto_competencias (id_projeto, id_competencia) VALUES (:proj, :comp)'
            );

            foreach ($competenciaIds as $compId) {
                $ins->execute(['proj' => $idProjeto, 'comp' => (int) $compId]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function hydrate(array $row): Projeto
    {
        return new Projeto(
            id: (int) $row['id'],
            idPortfolio: (int) $row['id_portfolio'],
            titulo: (string) $row['titulo'],
            descricao: $row['descricao'] ?? null,
            linksReferencia: $this->decodeJson($row['links_referencia'] ?? null),
            dataInicio: $row['data_inicio'] ?? null,
            dataFim: $row['data_fim'] ?? null,
            resultadosMencionaveis: $this->decodeJson($row['resultados_mencionaveis'] ?? null),
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
