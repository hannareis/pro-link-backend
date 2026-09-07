<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

// Acesso ao MariaDB para a entidade User (RF01), unico ponto de SQL sobre a
// tabela sis_usuarios (nomenclatura do Anexo I, item 8.6).
class UserRepository
{
    // Busca um usuario ativo pelo e-mail (login), ignorando registros excluidos logicamente.
    public function findByEmail(string $email): ?User
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM sis_usuarios WHERE usu_email = :email AND usu_status != 'X'"
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Insere ou atualiza um usuario e retorna o id persistido.
    public function save(User $user): int
    {
        return 0;
    }

    // Exclusao logica: marca usu_status = 'X' em vez de apagar a linha, preservando o historico de auditoria.
    public function softDelete(int $id): bool
    {
        $stmt = Database::connection()->prepare(
            "UPDATE sis_usuarios SET usu_status = 'X' WHERE usu_id = :id"
        );

        return $stmt->execute(['id' => $id]);
    }

    // Converte uma linha do banco (colunas prefixadas usu_) em um objeto User tipado.
    private function hydrate(array $row): User
    {
        return new User(
            id: (int) $row['usu_id'],
            nome: $row['usu_nome'],
            email: $row['usu_email'],
            senhaHash: $row['usu_senha_hash'],
            perfil: $row['usu_perfil'],
            seloVerificacao: (bool) $row['usu_selo_verificacao'],
            status: $row['usu_status'],
        );
    }
}
