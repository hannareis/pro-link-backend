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

    // Registra uma nova solicitacao de verificacao (documentos + status PENDENTE),
    // substituindo os documentos de uma solicitacao anterior se houver.
    public function salvarSolicitacaoVerificacao(
        int $idUsuario,
        string $docContratoSocial,
        string $docComprovanteCadastral
    ): void {
        $stmt = Database::connection()->prepare(
            'UPDATE pessoa_juridica
             SET status_verificacao = :status,
                 data_solicitacao_verificacao = NOW(),
                 doc_contrato_social = :contrato,
                 doc_comprovante_cadastral = :comprovante
             WHERE id_usuario = :id_usuario'
        );
        $stmt->execute([
            'status' => PessoaJuridica::STATUS_PENDENTE,
            'contrato' => $docContratoSocial,
            'comprovante' => $docComprovanteCadastral,
            'id_usuario' => $idUsuario,
        ]);
    }

    private function hydrate(array $row): PessoaJuridica
    {
        return new PessoaJuridica(
            idUsuario: (int) $row['id_usuario'],
            cnpj: (string) $row['cnpj'],
            razaoSocial: (string) $row['razao_social'],
            nomeFantasia: $row['nome_fantasia'] ?? null,
            statusVerificacao: (string) ($row['status_verificacao'] ?? PessoaJuridica::STATUS_NAO_SOLICITADA),
            dataSolicitacaoVerificacao: $row['data_solicitacao_verificacao'] ?? null,
            docContratoSocial: $row['doc_contrato_social'] ?? null,
            docComprovanteCadastral: $row['doc_comprovante_cadastral'] ?? null,
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}
