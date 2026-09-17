<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Demanda;
use App\Core\Database;

// Acesso ao MariaDB para a entidade Demanda (RF04), tabela `demandas`.
class DemandaRepository
{
    public function findById(int $id): ?Demanda
    {
        $stmt = Database::connection()->prepare(
            'SELECT d.*, ' . self::SELECT_CALCULADOS . '
             FROM demandas d
             LEFT JOIN pessoa_juridica pj ON pj.id_usuario = d.id_empresa
             WHERE d.id = :id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Lista todas as demandas abertas (sem filtros) - mantido para os poucos usos internos
    // que so precisam do essencial. A busca publica usa buscarComFiltros().
    public function all(): array
    {
        return $this->buscarComFiltros();
    }

    // Colunas calculadas via JOIN/subquery, reaproveitadas por findById() e buscarComFiltros().
    private const SELECT_CALCULADOS = "
        COALESCE(pj.nome_fantasia, pj.razao_social) AS company,
        (SELECT COUNT(*) FROM demonstracoes_interesse di
            WHERE di.id_demanda = d.id AND di.status != 'CANCELADA') AS interessados
    ";

    // Busca publica de demandas (RF04): texto livre (titulo/descricao/empresa), area,
    // tipo, modalidade, status e uma faixa de prazo derivada de data_fechamento (sem
    // coluna propria - "curto"/"medio"/"longo" sao calculados a partir de hoje).
    public function buscarComFiltros(
        ?string $busca = null,
        ?string $area = null,
        ?string $tipo = null,
        ?string $modalidade = null,
        ?string $prazo = null,
        ?string $status = null,
        int $limit = 50
    ): array {
        $condicoes = [];
        $params = ['limit' => $limit];

        if ($status !== null && $status !== '') {
            $condicoes[] = 'd.status = :status';
            $params['status'] = $status;
        } else {
            // Busca publica: por padrao so mostra o que ainda esta aberto.
            $condicoes[] = "d.status = 'ABERTA'";
        }

        if ($busca !== null && $busca !== '') {
            $condicoes[] = '(d.titulo LIKE :busca OR d.descricao LIKE :busca OR COALESCE(pj.nome_fantasia, pj.razao_social) LIKE :busca)';
            $params['busca'] = '%' . $busca . '%';
        }

        if ($area !== null && $area !== '') {
            $condicoes[] = 'd.area = :area';
            $params['area'] = $area;
        }

        if ($tipo !== null && $tipo !== '') {
            $condicoes[] = 'd.tipo = :tipo';
            $params['tipo'] = $tipo;
        }

        if ($modalidade !== null && $modalidade !== '') {
            $condicoes[] = 'd.modalidade = :modalidade';
            $params['modalidade'] = $modalidade;
        }

        if ($prazo !== null && $prazo !== '') {
            $condicoes[] = match ($prazo) {
                'curto' => 'd.data_fechamento IS NOT NULL AND DATEDIFF(d.data_fechamento, NOW()) BETWEEN 0 AND 7',
                'medio' => 'd.data_fechamento IS NOT NULL AND DATEDIFF(d.data_fechamento, NOW()) BETWEEN 8 AND 30',
                'longo' => '(d.data_fechamento IS NULL OR DATEDIFF(d.data_fechamento, NOW()) > 30)',
                default => '1 = 1',
            };
        }

        $stmt = Database::connection()->prepare(
            'SELECT d.*, ' . self::SELECT_CALCULADOS . '
             FROM demandas d
             LEFT JOIN pessoa_juridica pj ON pj.id_usuario = d.id_empresa
             WHERE ' . implode(' AND ', $condicoes) . '
             ORDER BY d.data_publicacao DESC
             LIMIT :limit'
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === 'limit' ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->execute();

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    // Insere (id nulo) ou atualiza uma demanda e retorna o id persistido.
    public function save(Demanda $demanda): int
    {
        $pdo = Database::connection();

        if ($demanda->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO demandas
                    (id_empresa, titulo, descricao, area, tipo, cidade, uf, modalidade, status, data_fechamento)
                 VALUES
                    (:id_empresa, :titulo, :descricao, :area, :tipo, :cidade, :uf, :modalidade, :status, :data_fechamento)'
            );
            $stmt->execute([
                'id_empresa' => $demanda->empresaId,
                'titulo' => $demanda->titulo,
                'descricao' => $demanda->descricao,
                'area' => $demanda->area,
                'tipo' => $demanda->tipo,
                'cidade' => $demanda->cidade,
                'uf' => $demanda->uf,
                'modalidade' => $demanda->modalidade,
                'status' => $demanda->status,
                'data_fechamento' => $demanda->dataFechamento,
            ]);

            return (int) $pdo->lastInsertId();
        }

        // atualizado_em nao e setado aqui de proposito: a coluna ja tem
        // ON UPDATE CURRENT_TIMESTAMP no schema.
        $stmt = $pdo->prepare(
            'UPDATE demandas SET
                titulo = :titulo,
                descricao = :descricao,
                area = :area,
                tipo = :tipo,
                cidade = :cidade,
                uf = :uf,
                modalidade = :modalidade,
                status = :status,
                data_fechamento = :data_fechamento
             WHERE id = :id'
        );
        $stmt->execute([
            'titulo' => $demanda->titulo,
            'descricao' => $demanda->descricao,
            'area' => $demanda->area,
            'tipo' => $demanda->tipo,
            'cidade' => $demanda->cidade,
            'uf' => $demanda->uf,
            'modalidade' => $demanda->modalidade,
            'status' => $demanda->status,
            'data_fechamento' => $demanda->dataFechamento,
            'id' => $demanda->id,
        ]);

        return $demanda->id;
    }

    public function delete(Demanda|int|null $target): bool
    {
        $id = $target instanceof Demanda ? $target->id : $target;
        if ($id === null) {
            return false;
        }
        $stmt = Database::connection()->prepare('DELETE FROM demandas WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Demanda
    {
        return new Demanda(
            id: (int) $row['id'],
            empresaId: isset($row['id_empresa']) ? (int) $row['id_empresa'] : null,
            titulo: (string) $row['titulo'],
            descricao: (string) $row['descricao'],
            area: (string) ($row['area'] ?? ''),
            tipo: (string) ($row['tipo'] ?? Demanda::TIPO_PROJETO),
            cidade: (string) ($row['cidade'] ?? ''),
            uf: (string) ($row['uf'] ?? ''),
            modalidade: (string) ($row['modalidade'] ?? Demanda::MODALIDADE_PRESENCIAL),
            status: (string) $row['status'],
            dataPublicacao: $row['data_publicacao'] ?? null,
            dataFechamento: $row['data_fechamento'] ?? null,
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
            company: $row['company'] ?? null,
            interessados: (int) ($row['interessados'] ?? 0),
        );
    }
}
