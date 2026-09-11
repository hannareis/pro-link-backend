<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\PessoaFisica;

// SQL sobre a tabela `pessoa_fisica` (1:1 com `usuarios`).
class PessoaFisicaRepository
{
    public function findByUsuarioId(int $idUsuario): ?PessoaFisica
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM pessoa_fisica WHERE id_usuario = :id LIMIT 1'
        );
        $stmt->execute(['id' => $idUsuario]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByCpf(string $cpf): ?PessoaFisica
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM pessoa_fisica WHERE cpf = :cpf LIMIT 1'
        );
        $stmt->execute(['cpf' => $cpf]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Cria ou atualiza o registro. A PK e sempre o id do usuario ja existente.
    public function save(PessoaFisica $pessoa): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO pessoa_fisica (id_usuario, cpf, visibilidade_publica)
             VALUES (:id_usuario, :cpf, :visibilidade_publica)
             ON DUPLICATE KEY UPDATE
                cpf = VALUES(cpf),
                visibilidade_publica = VALUES(visibilidade_publica)'
        );
        $stmt->execute([
            'id_usuario' => $pessoa->idUsuario,
            'cpf' => $pessoa->cpf,
            'visibilidade_publica' => $pessoa->visibilidadePublica ? 1 : 0,
        ]);

        return $pessoa->idUsuario;
    }

    private function hydrate(array $row): PessoaFisica
    {
        return new PessoaFisica(
            idUsuario: (int) $row['id_usuario'],
            cpf: (string) $row['cpf'],
            visibilidadePublica: (bool) $row['visibilidade_publica'],
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}
