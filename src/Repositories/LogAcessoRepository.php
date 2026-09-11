<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\LogAcesso;

// SQL sobre a tabela `sis_logs_acesso` (append-only).
class LogAcessoRepository
{
    // Registra um evento de autenticacao e retorna o log_id gerado.
    public function registrar(LogAcesso $log): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO sis_logs_acesso (usu_id, log_acao, log_ip, log_user_agent)
             VALUES (:usu_id, :log_acao, :log_ip, :log_user_agent)'
        );
        $stmt->execute([
            'usu_id' => $log->usuId,
            'log_acao' => $log->logAcao,
            'log_ip' => $log->logIp,
            'log_user_agent' => $log->logUserAgent,
        ]);

        return (int) $pdo->lastInsertId();
    }

    public function listByUsuario(int $usuId, int $limite = 100): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM sis_logs_acesso
              WHERE usu_id = :id
           ORDER BY log_dt_registro DESC
              LIMIT :limite'
        );
        $stmt->bindValue('id', $usuId, \PDO::PARAM_INT);
        $stmt->bindValue('limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    // Tentativas de login que falharam a partir de um IP nas ultimas N horas.
    public function falhasRecentesPorIp(string $ip, int $horas = 1): int
    {
        $stmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM sis_logs_acesso
              WHERE log_ip = :ip
                AND log_acao = 'LOGIN_FALHA'
                AND log_dt_registro >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL :horas HOUR)"
        );
        $stmt->bindValue('ip', $ip);
        $stmt->bindValue('horas', $horas, \PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    private function hydrate(array $row): LogAcesso
    {
        return new LogAcesso(
            logId: (int) $row['log_id'],
            usuId: isset($row['usu_id']) ? (int) $row['usu_id'] : null,
            logAcao: (string) $row['log_acao'],
            logIp: (string) $row['log_ip'],
            logUserAgent: $row['log_user_agent'] ?? null,
            logDtRegistro: $row['log_dt_registro'] ?? null,
        );
    }
}
