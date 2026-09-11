<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\PessoaJuridica;

// SQL sobre a tabela `pessoa_juridica` (1:1 com `usuarios`).
class PessoaJuridicaRepository
{
    public function findByUsuarioId(int $idUsuario): ?PessoaJuridica
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM pessoa_juridica WHERE id_usuario = :id LIMIT 1'
        );
        $stmt->execute(['id' => $idUsuario]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByCnpj(string $cnpj): ?PessoaJuridica
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM pessoa_juridica WHERE cnpj = :cnpj LIMIT 1'
        );
        $stmt->execute(['cnpj' => $cnpj]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function save(PessoaJuridica $pessoa): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO pessoa_juridica (id_usuario, cnpj, nome_fantasia, razao_social)
             VALUES (:id_usuario, :cnpj, :nome_fantasia, :razao_social)
             ON DUPLICATE KEY UPDATE
                cnpj = VALUES(cnpj),
                nome_fantasia = VALUES(nome_fantasia),
                razao_social = VALUES(razao_social)'
        );
        $stmt->execute([
            'id_usuario' => $pessoa->idUsuario,
            'cnpj' => $pessoa->cnpj,
            'nome_fantasia' => $pessoa->nomeFantasia,
            'razao_social' => $pessoa->razaoSocial,
        ]);

        return $pessoa->idUsuario;
    }

    private function hydrate(array $row): PessoaJuridica
    {
        return new PessoaJuridica(
            idUsuario: (int) $row['id_usuario'],
            cnpj: (string) $row['cnpj'],
            razaoSocial: (string) $row['razao_social'],
            nomeFantasia: $row['nome_fantasia'] ?? null,
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}
