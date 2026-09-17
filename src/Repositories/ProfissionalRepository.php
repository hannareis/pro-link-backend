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

    // Busca unificada de "talentos" (RF02/RF03): profissionais registrados no CREA-AM
    // e universitarios, num unico resultado ordenado por nome. Estudantes nao tem
    // numero_registro_confea_crea (sempre null) nem grau_academico validado pelo CREA -
    // o filtro "validado" (registro_validado), quando usado, exclui a parte de
    // universitarios da uniao, pois esse conceito nao existe para eles.
    public function buscarTalentos(
        ?string $nome = null,
        ?string $area = null,
        ?string $grauAcademico = null,
        ?bool $registroValidado = null,
        int $limit = 50
    ): array {
        $condicoesProf = ['pr.registro_ativo = 1'];
        $condicoesUni = [];
        $params = ['limitProf' => $limit, 'limitUni' => $limit];
        $incluirEstudantes = $registroValidado === null;

        if ($nome !== null && $nome !== '') {
            $condicoesProf[] = 'u.nome LIKE :nomeProf';
            $condicoesUni[] = 'u.nome LIKE :nomeUni';
            $params['nomeProf'] = $params['nomeUni'] = '%' . $nome . '%';
        }

        if ($area !== null && $area !== '') {
            $condicoesProf[] = 'pr.categoria_profissional = :areaProf';
            $condicoesUni[] = 'uni.curso = :areaUni';
            $params['areaProf'] = $params['areaUni'] = $area;
        }

        if ($grauAcademico !== null && $grauAcademico !== '') {
            $condicoesProf[] = 'pr.grau_academico = :grauProf';
            $condicoesUni[] = 'uni.grau_academico = :grauUni';
            $params['grauProf'] = $params['grauUni'] = $grauAcademico;
        }

        if ($registroValidado !== null) {
            $condicoesProf[] = 'pr.registro_validado = :validado';
            $params['validado'] = $registroValidado ? 1 : 0;
        }

        // Cada SELECT do UNION precisa dos proprios parenteses para poder ter ORDER
        // BY/LIMIT individuais - sem isso o MySQL rejeita a sintaxe (so aceita um unico
        // ORDER BY/LIMIT ao final de toda a uniao).
        $sql = '(SELECT
                    u.id AS id,
                    u.nome AS nome,
                    pr.categoria_profissional AS categoria_profissional,
                    pr.numero_registro_confea_crea AS numero_registro_confea_crea,
                    pf.resumo_profissional AS resumo_profissional
                FROM profissionais pr
                JOIN usuarios u ON u.id = pr.id_usuario
                LEFT JOIN portfolio pf ON pf.id_usuario = pr.id_usuario
                WHERE ' . implode(' AND ', $condicoesProf) . '
                ORDER BY u.nome
                LIMIT :limitProf)';

        if ($incluirEstudantes) {
            $sql .= ' UNION ALL (SELECT
                    u.id AS id,
                    u.nome AS nome,
                    uni.curso AS categoria_profissional,
                    NULL AS numero_registro_confea_crea,
                    pf.resumo_profissional AS resumo_profissional
                FROM universitarios uni
                JOIN usuarios u ON u.id = uni.id_usuario
                LEFT JOIN portfolio pf ON pf.id_usuario = uni.id_usuario'
                . ($condicoesUni !== [] ? ' WHERE ' . implode(' AND ', $condicoesUni) : '') . '
                ORDER BY u.nome
                LIMIT :limitUni)';
        }

        if (!$incluirEstudantes) {
            // :limitUni so existe no SQL quando o branch de universitarios e incluido -
            // manter esse valor no array faria bindValue apontar para um placeholder
            // inexistente.
            unset($params['limitUni']);
        }

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(
                $key,
                $value,
                (str_starts_with($key, 'limit') || $key === 'validado') ? \PDO::PARAM_INT : \PDO::PARAM_STR
            );
        }
        $stmt->execute();

        $linhas = $stmt->fetchAll();
        usort($linhas, fn($a, $b) => strcmp($a['nome'], $b['nome']));

        foreach ($linhas as &$linha) {
            $linha['id'] = (int) $linha['id'];
            $linha['competencias'] = $linha['numero_registro_confea_crea'] !== null
                ? array_map(
                    fn($c) => ['nome' => $c['nome'], 'nivel' => $c['nivel']],
                    $this->competenciasDoProfissional($linha['id'])
                )
                : [];
        }

        return $linhas;
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
