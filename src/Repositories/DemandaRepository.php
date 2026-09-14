<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Demanda;
use App\Core\Database;

// Acesso ao MariaDB para a entidade Demanda (RF04), tabela pro_demandas.
class DemandaRepository
{
    public function findById(int $id): ?Demanda
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM demandas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Lista todas as demandas ativas.
    public function all(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM demandas WHERE status = :status ORDER BY data_publicacao DESC');
        $stmt->execute(['status' => 'ABERTA']);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    // Insere ou atualiza uma demanda e retorna o id persistido.
    public function save(Demanda $demanda): int
    {
        $pdo = Database::connection();
        if ($demanda->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO demandas (id_empresa, titulo, descricao, area, tipo, cidade, uf, modalidade, status, data_publicacao, data_fechamento, criado_em) 
                VALUES (:id_empresa, :titulo, :descricao, :area, :tipo, :cidade, :uf, :modalidade, :status, :data_publicacao, :data_fechamento, :criado_em)'
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
                'data_publicacao' => $demanda->dataPublicacao,
                'data_fechamento' => $demanda->dataFechamento,
                'criado_em' => $demanda->criadoEm,
            ]);

            return (int) $pdo->lastInsertId();
        }
        $stmt = $pdo->prepare(
            'UPDATE demandas SET 
            titulo = :titulo, 
            descricao = :descricao,
            tipo = :tipo, 
            modalidade = :modalidade, 
            status = :status,
            data_fechamento = :data_fechamento, 
            atualizado_em = :atualizado_em 
            WHERE id = :id'
        );
        $stmt->execute([
            'titulo' => $demanda->titulo,
            'descricao' => $demanda->descricao,
            'tipo' => $demanda->tipo,
            'modalidade' => $demanda->modalidade,
            'status' => $demanda->status,
            'data_fechamento' => $demanda->dataFechamento,
            'atualizado_em' => $demanda->atualizadoEm,
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
        $pdo = Database::connection();
        $stmt = $pdo->prepare('DELETE FROM demandas WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function hydrate(array $row): Demanda
    {
        return new Demanda(
            id: (int) $row['id'],
            empresaId: isset($row['id_empresa']) ? (int) $row['id_empresa'] : null,
            titulo: (string) $row['titulo'],
            descricao: (string) $row['descricao'],
            area: (string) $row['area_demanda'],
            tipo: (string) $row['tipo_demanda'],
            cidade: (string) $row['cidade_demanda'],
            uf: (string) $row['uf_demanda'],
            modalidade: (string) $row['modalidade'],
            status: (string) $row['status'],
            dataPublicacao: $row['data_publicacao'] ?? null,
            dataFechamento: $row['data_fechamento'] ?? null,
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}
