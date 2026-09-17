<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Profissional;

// SQL sobre a tabela `profissionais` (1:1 com `pessoa_fisica`) e suas tabelas
// de vinculo `profissional_competencias` / `profissional_especialidades`.
class ProfissionalRepository
{
    public function findByUsuarioId(int $idUsuario): ?Profissional
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM profissionais WHERE id_usuario = :id LIMIT 1'
        );
        $stmt->execute(['id' => $idUsuario]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Busca o CPF ou CNPJ (dependendo do tipo de pessoa) associado ao usuário.
    public function findCpfCnpjByUsuarioId(int $idUsuario): ?string
    {
        $db = Database::connection();
        
        // Verifica se é pessoa física
        $stmt = $db->prepare('SELECT cpf FROM pessoa_fisica WHERE id_usuario = :id LIMIT 1');
        $stmt->execute(['id' => $idUsuario]);
        $cpf = $stmt->fetchColumn();
        if ($cpf) {
            return (string) $cpf;
        }

        // Verifica se é pessoa jurídica
        $stmt = $db->prepare('SELECT cnpj FROM pessoa_juridica WHERE id_usuario = :id LIMIT 1');
        $stmt->execute(['id' => $idUsuario]);
        $cnpj = $stmt->fetchColumn();
        if ($cnpj) {
            return (string) $cnpj;
        }

        return null;
    }

    public function findByRegistro(string $numeroRegistro): ?Profissional
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM profissionais WHERE numero_registro_confea_crea = :n LIMIT 1'
        );
        $stmt->execute(['n' => $numeroRegistro]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Lista profissionais ativos, opcionalmente filtrando por categoria.
    public function all(?string $categoria = null): array
    {
        $sql = 'SELECT * FROM profissionais WHERE registro_ativo = 1';
        $params = [];

        if ($categoria !== null) {
            $sql .= ' AND categoria_profissional = :categoria';
            $params['categoria'] = $categoria;
        }

        $sql .= ' ORDER BY criado_em DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(Profissional $p): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO profissionais
                (id_usuario, numero_registro_confea_crea, categoria_profissional, anos_experiencia,
                 empresa_atual_id, grau_academico, registro_validado, registro_validado_em, registro_ativo)
             VALUES
                (:id_usuario, :numero, :categoria, :anos, :empresa, :grau, :validado, :validado_em, :ativo)
             ON DUPLICATE KEY UPDATE
                numero_registro_confea_crea = VALUES(numero_registro_confea_crea),
                categoria_profissional = VALUES(categoria_profissional),
                anos_experiencia = VALUES(anos_experiencia),
                empresa_atual_id = VALUES(empresa_atual_id),
                grau_academico = VALUES(grau_academico),
                registro_validado = VALUES(registro_validado),
                registro_validado_em = VALUES(registro_validado_em),
                registro_ativo = VALUES(registro_ativo)'
        );
        $stmt->execute([
            'id_usuario' => $p->idUsuario,
            'numero' => $p->numeroRegistroConfeaCrea,
            'categoria' => $p->categoriaProfissional,
            'anos' => $p->anosExperiencia,
            'empresa' => $p->empresaAtualId,
            'grau' => $p->grauAcademico,
            'validado' => $p->registroValidado ? 1 : 0,
            'validado_em' => $p->registroValidadoEm,
            'ativo' => $p->registroAtivo ? 1 : 0,
        ]);

        return $p->idUsuario;
    }

    // Marca o registro como validado pelo CREA (RF02), com carimbo de data/hora.
    public function marcarValidado(int $idUsuario): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE profissionais
                SET registro_validado = 1, registro_validado_em = CURRENT_TIMESTAMP
             WHERE id_usuario = :id'
        );

        return $stmt->execute(['id' => $idUsuario]);
    }

    // ----- profissional_competencias -------------------------------------------------

    public function competenciasDoProfissional(int $idProfissional): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT pc.id_competencia, c.nome, pc.nivel, pc.anos_experiencia
               FROM profissional_competencias pc
               JOIN competencias c ON c.id = pc.id_competencia
              WHERE pc.id_profissional = :id'
        );
        $stmt->execute(['id' => $idProfissional]);

        return $stmt->fetchAll();
    }

    // Substitui todo o conjunto de competencias do profissional numa unica transacao.
    // $competencias: [ ['id' => 1, 'nivel' => 'AVANCADO', 'anos' => 3], ... ]
    public function sincronizarCompetencias(int $idProfissional, array $competencias): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $del = $pdo->prepare('DELETE FROM profissional_competencias WHERE id_profissional = :id');
            $del->execute(['id' => $idProfissional]);

            $ins = $pdo->prepare(
                'INSERT INTO profissional_competencias
                    (id_profissional, id_competencia, nivel, anos_experiencia)
                 VALUES (:prof, :comp, :nivel, :anos)'
            );

            foreach ($competencias as $c) {
                $ins->execute([
                    'prof' => $idProfissional,
                    'comp' => (int) $c['id'],
                    'nivel' => $c['nivel'] ?? 'INTERMEDIARIO',
                    'anos' => $c['anos'] ?? null,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // ----- profissional_especialidades ---------------------------------------------

    public function especialidadesDoProfissional(int $idProfissional): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT e.id, e.nome
               FROM profissional_especialidades pe
               JOIN especialidades e ON e.id = pe.id_especialidade
              WHERE pe.id_profissional = :id'
        );
        $stmt->execute(['id' => $idProfissional]);

        return $stmt->fetchAll();
    }

    // $especialidadeIds: lista de inteiros.
    public function sincronizarEspecialidades(int $idProfissional, array $especialidadeIds): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $del = $pdo->prepare('DELETE FROM profissional_especialidades WHERE id_profissional = :id');
            $del->execute(['id' => $idProfissional]);

            $ins = $pdo->prepare(
                'INSERT INTO profissional_especialidades (id_profissional, id_especialidade)
                 VALUES (:prof, :esp)'
            );

            foreach ($especialidadeIds as $espId) {
                $ins->execute(['prof' => $idProfissional, 'esp' => (int) $espId]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function hydrate(array $row): Profissional
    {
        return new Profissional(
            idUsuario: (int) $row['id_usuario'],
            numeroRegistroConfeaCrea: (string) $row['numero_registro_confea_crea'],
            categoriaProfissional: (string) $row['categoria_profissional'],
            anosExperiencia: isset($row['anos_experiencia']) ? (int) $row['anos_experiencia'] : null,
            empresaAtualId: isset($row['empresa_atual_id']) ? (int) $row['empresa_atual_id'] : null,
            grauAcademico: (string) $row['grau_academico'],
            registroValidado: (bool) $row['registro_validado'],
            registroValidadoEm: $row['registro_validado_em'] ?? null,
            registroAtivo: (bool) $row['registro_ativo'],
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}
