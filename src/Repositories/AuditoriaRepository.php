<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Auditoria;

// SQL sobre a tabela `sis_auditoria` (append-only, imutavel).
class AuditoriaRepository
{
    // Grava um registro de auditoria e retorna o aud_id gerado.
    public function registrar(Auditoria $a): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO sis_auditoria
                (usu_id, aud_tabela, aud_registro_id, aud_acao, aud_dados_antigos, aud_dados_novos,
                 aud_ip, aud_user_agent)
             VALUES
                (:usu_id, :tabela, :registro_id, :acao, :antigos, :novos, :ip, :user_agent)'
        );
        $stmt->execute([
            'usu_id' => $a->usuId,
            'tabela' => $a->audTabela,
            'registro_id' => $a->audRegistroId,
            'acao' => $a->audAcao,
            'antigos' => $a->audDadosAntigos !== null
                ? json_encode($a->audDadosAntigos, JSON_UNESCAPED_UNICODE)
                : null,
            'novos' => $a->audDadosNovos !== null
                ? json_encode($a->audDadosNovos, JSON_UNESCAPED_UNICODE)
                : null,
            'ip' => $a->audIp,
            'user_agent' => $a->audUserAgent,
        ]);

        return (int) $pdo->lastInsertId();
    }

    // Historico de alteracoes de um registro especifico.
    public function historico(string $tabela, int $registroId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM sis_auditoria
              WHERE aud_tabela = :tabela AND aud_registro_id = :registro_id
           ORDER BY aud_dt_registro DESC'
        );
        $stmt->execute(['tabela' => $tabela, 'registro_id' => $registroId]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    // Feed global de auditoria (mais recentes primeiro), usado pelo painel administrativo.
    public function listAll(int $limite = 200): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM sis_auditoria ORDER BY aud_dt_registro DESC LIMIT :limite'
        );
        $stmt->bindValue('limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function listByUsuario(int $usuId, int $limite = 100): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM sis_auditoria WHERE usu_id = :id ORDER BY aud_dt_registro DESC LIMIT :limite'
        );
        $stmt->bindValue('id', $usuId, \PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    private function hydrate(array $row): Auditoria
    {
        return new Auditoria(
            audId: (int) $row['aud_id'],
            usuId: isset($row['usu_id']) ? (int) $row['usu_id'] : null,
            audTabela: (string) $row['aud_tabela'],
            audRegistroId: (int) $row['aud_registro_id'],
            audAcao: (string) $row['aud_acao'],
            audDadosAntigos: isset($row['aud_dados_antigos']) && $row['aud_dados_antigos'] !== null
                ? json_decode((string) $row['aud_dados_antigos'], true)
                : null,
            audDadosNovos: isset($row['aud_dados_novos']) && $row['aud_dados_novos'] !== null
                ? json_decode((string) $row['aud_dados_novos'], true)
                : null,
            audIp: $row['aud_ip'] ?? null,
            audUserAgent: $row['aud_user_agent'] ?? null,
            audDtRegistro: $row['aud_dt_registro'] ?? null,
        );
    }
}
