<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Universitario;

// SQL sobre a tabela `universitarios` (1:1 com `pessoa_fisica`).
class UniversitarioRepository
{
    public function findByUsuarioId(int $idUsuario): ?Universitario
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM universitarios WHERE id_usuario = :id LIMIT 1'
        );
        $stmt->execute(['id' => $idUsuario]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listByUniversidade(int $universidadeId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM universitarios WHERE universidade_id = :uid ORDER BY curso'
        );
        $stmt->execute(['uid' => $universidadeId]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(Universitario $u): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO universitarios
                (id_usuario, universidade_id, curso, matricula, grau_academico, semestre_atual,
                 previsao_formatura, comprovante_matricula)
             VALUES
                (:id_usuario, :universidade_id, :curso, :matricula, :grau_academico, :semestre_atual,
                 :previsao_formatura, :comprovante_matricula)
             ON DUPLICATE KEY UPDATE
                universidade_id = VALUES(universidade_id),
                curso = VALUES(curso),
                matricula = VALUES(matricula),
                grau_academico = VALUES(grau_academico),
                semestre_atual = VALUES(semestre_atual),
                previsao_formatura = VALUES(previsao_formatura),
                comprovante_matricula = VALUES(comprovante_matricula)'
        );
        $stmt->execute([
            'id_usuario' => $u->idUsuario,
            'universidade_id' => $u->universidadeId,
            'curso' => $u->curso,
            'matricula' => $u->matricula,
            'grau_academico' => $u->grau_academico,
            'semestre_atual' => $u->semestreAtual,
            'previsao_formatura' => $u->previsaoFormatura,
            'comprovante_matricula' => $u->comprovanteMatricula,
        ]);

        return $u->idUsuario;
    }

    public function delete(int $idUsuario): bool
    {
        $stmt = Database::connection()->prepare(
            'DELETE FROM universitarios WHERE id_usuario = :id'
        );

        return $stmt->execute(['id' => $idUsuario]);
    }

    private function hydrate(array $row): Universitario
    {
        return new Universitario(
            idUsuario: (int) $row['id_usuario'],
            universidadeId: (int) $row['universidade_id'],
            curso: (string) $row['curso'],
            matricula: $row['matricula'] ?? null,
            grau-academico: isset($row['grau_academico']) ? (string) $row['grau_academico']: null,
            semestreAtual: isset($row['semestre_atual']) ? (int) $row['semestre_atual'] : null,
            previsaoFormatura: $row['previsao_formatura'] ?? null,
            comprovanteMatricula: $row['comprovante_matricula'] ?? null,
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}