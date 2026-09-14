<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\PasswordResetToken;

// Unico ponto de SQL sobre a tabela `tokens_redefinicao_senha` do estrutura.sql.
class PasswordResetTokenRepository
{
    // Insere um novo token de redefinicao e retorna o id persistido.
    public function save(PasswordResetToken $token): int
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'INSERT INTO tokens_redefinicao_senha (id_usuario, token_hash, expira_em)
             VALUES (:id_usuario, :token_hash, :expira_em)'
        );
        $stmt->execute([
            'id_usuario' => $token->idUsuario,
            'token_hash' => $token->tokenHash,
            'expira_em' => $token->expiraEm,
        ]);

        return (int) $pdo->lastInsertId();
    }

    // Busca um token ainda valido (nao usado e nao expirado) pelo hash recebido.
    public function findValidoByHash(string $tokenHash): ?PasswordResetToken
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM tokens_redefinicao_senha
             WHERE token_hash = :token_hash AND usado_em IS NULL AND expira_em > NOW()
             LIMIT 1'
        );
        $stmt->execute(['token_hash' => $tokenHash]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Marca o token como usado, impedindo que o mesmo link seja reaproveitado.
    public function marcarUsado(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE tokens_redefinicao_senha SET usado_em = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): PasswordResetToken
    {
        return new PasswordResetToken(
            id: (int) $row['id'],
            idUsuario: (int) $row['id_usuario'],
            tokenHash: (string) $row['token_hash'],
            expiraEm: (string) $row['expira_em'],
            usadoEm: $row['usado_em'] ?? null,
            criadoEm: $row['criado_em'] ?? null,
        );
    }
}
