<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Experiencia;

// SQL sobre a tabela `experiencias` e o vinculo `projeto_experiencia`.
class ExperienciaRepository
{
    public function findById(int $id): ?Experiencia
    {
        $stmt = Database::connection()->prepare('SELECT * FROM experiencias WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listByPortfolio(int $idPortfolio): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM experiencias WHERE id_portfolio = :id ORDER BY data_inicio DESC, id DESC'
        );
        $stmt->execute(['id' => $idPortfolio]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(Experiencia $e): int
    {
        $pdo = Database::connection();
        $params = [
            'id_portfolio' => $e->idPortfolio,
            'titulo' => $e->tituloPosicaoServico,
            'organizacao_cliente' => $e->organizacaoCliente,
            'organizacao_id' => $e->organizacaoId,
            'descricao_atividades' => $e->descricaoAtividades,
            'data_inicio' => $e->dataInicio,
            'data_fim' => $e->dataFim,
        ];

        if ($e->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO experiencias
                    (id_portfolio, titulo_posicao_servico, organizacao_cliente, organizacao_id,
                     descricao_atividades, data_inicio, data_fim)
                 VALUES
                    (:id_portfolio, :titulo, :organizacao_cliente, :organizacao_id,
                     :descricao_atividades, :data_inicio, :data_fim)'
            );
            $stmt->execute($params);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE experiencias SET
                id_portfolio = :id_portfolio,
                titulo_posicao_servico = :titulo,
                organizacao_cliente = :organizacao_cliente,
                organizacao_id = :organizacao_id,
                descricao_atividades = :descricao_atividades,
                data_inicio = :data_inicio,
                data_fim = :data_fim
             WHERE id = :id'
        );
        $stmt->execute($params + ['id' => $e->id]);

        return $e->id;
    }

    public function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM experiencias WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    // ----- projeto_experiencia --------------------------------------------------

    public function vincularProjeto(int $idProjeto, int $idExperiencia): bool
    {
        $stmt = Database::connection()->prepare(
            'INSERT IGNORE INTO projeto_experiencia (id_projeto, id_experiencia)
             VALUES (:proj, :exp)'
        );

        return $stmt->execute(['proj' => $idProjeto, 'exp' => $idExperiencia]);
    }

    public function desvincularProjeto(int $idProjeto, int $idExperiencia): bool
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM projeto_experiencia WHERE id_projeto = :proj AND id_experiencia = :exp'
        );

        return $stmt->execute(['proj' => $idProjeto, 'exp' => $idExperiencia]);
    }

    public function projetosDaExperiencia(int $idExperiencia): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT p.id, p.titulo
               FROM projeto_experiencia pe
               JOIN projetos p ON p.id = pe.id_projeto
              WHERE pe.id_experiencia = :id'
        );
        $stmt->execute(['id' => $idExperiencia]);

        return $stmt->fetchAll();
    }

    private function hydrate(array $row): Experiencia
    {
        return new Experiencia(
            id: (int) $row['id'],
            idPortfolio: (int) $row['id_portfolio'],
            tituloPosicaoServico: (string) $row['titulo_posicao_servico'],
            organizacaoCliente: $row['organizacao_cliente'] ?? null,
            organizacaoId: isset($row['organizacao_id']) ? (int) $row['organizacao_id'] : null,
            descricaoAtividades: $row['descricao_atividades'] ?? null,
            dataInicio: $row['data_inicio'] ?? null,
            dataFim: $row['data_fim'] ?? null,
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}
