<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\CartaVirtual;

// SQL sobre a tabela `cartas_virtuais`.
class CartaVirtualRepository
{
    public function findById(int $id): ?CartaVirtual
    {
        $stmt = Database::connection()->prepare('SELECT * FROM cartas_virtuais WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Lista as cartas virtuais criadas pelo usuario, mais recentes primeiro.
    public function listByUsuario(int $idUsuario): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM cartas_virtuais WHERE id_usuario = :id ORDER BY criado_em DESC'
        );
        $stmt->execute(['id' => $idUsuario]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    // Insere (id nulo) ou atualiza uma carta virtual e retorna o id persistido.
    public function save(CartaVirtual $c): int
    {
        $pdo = Database::connection();

        if ($c->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO cartas_virtuais
                    (id_usuario, id_demanda, titulo, legenda, remetente_email, destinatario_email,
                     nome_arquivo, nome_armazenado, tipo_mime, tamanho_arquivo, caminho_armazenamento)
                 VALUES
                    (:id_usuario, :id_demanda, :titulo, :legenda, :remetente_email, :destinatario_email,
                     :nome_arquivo, :nome_armazenado, :tipo_mime, :tamanho_arquivo, :caminho_armazenamento)'
            );
            $stmt->execute([
                'id_usuario' => $c->idUsuario,
                'id_demanda' => $c->idDemanda,
                'titulo' => $c->titulo,
                'legenda' => $c->legenda,
                'remetente_email' => $c->remetenteEmail,
                'destinatario_email' => $c->destinatarioEmail,
                'nome_arquivo' => $c->nomeArquivo,
                'nome_armazenado' => $c->nomeArmazenado,
                'tipo_mime' => $c->tipoMime,
                'tamanho_arquivo' => $c->tamanhoArquivo,
                'caminho_armazenamento' => $c->caminhoArmazenamento,
            ]);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE cartas_virtuais SET
                id_demanda = :id_demanda,
                titulo = :titulo,
                legenda = :legenda,
                remetente_email = :remetente_email,
                destinatario_email = :destinatario_email,
                nome_arquivo = :nome_arquivo,
                nome_armazenado = :nome_armazenado,
                tipo_mime = :tipo_mime,
                tamanho_arquivo = :tamanho_arquivo,
                caminho_armazenamento = :caminho_armazenamento
             WHERE id = :id'
        );
        $stmt->execute([
            'id_demanda' => $c->idDemanda,
            'titulo' => $c->titulo,
            'legenda' => $c->legenda,
            'remetente_email' => $c->remetenteEmail,
            'destinatario_email' => $c->destinatarioEmail,
            'nome_arquivo' => $c->nomeArquivo,
            'nome_armazenado' => $c->nomeArmazenado,
            'tipo_mime' => $c->tipoMime,
            'tamanho_arquivo' => $c->tamanhoArquivo,
            'caminho_armazenamento' => $c->caminhoArmazenamento,
            'id' => $c->id,
        ]);

        return $c->id;
    }

    public function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM cartas_virtuais WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): CartaVirtual
    {
        return new CartaVirtual(
            id: (int) $row['id'],
            idUsuario: (int) $row['id_usuario'],
            idDemanda: isset($row['id_demanda']) ? (int) $row['id_demanda'] : null,
            titulo: (string) $row['titulo'],
            legenda: $row['legenda'] ?? null,
            remetenteEmail: (string) $row['remetente_email'],
            destinatarioEmail: (string) $row['destinatario_email'],
            nomeArquivo: $row['nome_arquivo'] ?? null,
            nomeArmazenado: $row['nome_armazenado'] ?? null,
            tipoMime: $row['tipo_mime'] ?? null,
            tamanhoArquivo: isset($row['tamanho_arquivo']) ? (int) $row['tamanho_arquivo'] : null,
            caminhoArmazenamento: $row['caminho_armazenamento'] ?? null,
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}
