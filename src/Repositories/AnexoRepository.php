<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Anexo;

class AnexoRepository
{
    public function findById(int $id): ?Anexo
    {
        $stmt = Database::connection()->prepare('SELECT * FROM anexos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listByPost(int $idPost): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM anexos WHERE id_post = :id ORDER BY id DESC'
        );
        $stmt->execute(['id' => $idPost]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function listById(int $id): array
    {
        return $this->listByPost($id);
    }

    public function save(Anexo $anexo): int
    {
        $pdo = Database::connection();

        if ($anexo->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO anexos
                    (id_post, nome_arquivo, nome_armazenado, tipo_mime, status_anexo, tamanho, caminho_armazenamento, hash_arquivo)
                 VALUES
                    (:postId, :nome, :nomeArmazenado, :tipoMime, :statusAnexo, :tamanho, :caminhoArmazenamento, :hashAnexo)'
            );
            $stmt->execute([
                'postId' => $anexo->postId,
                'nome' => $anexo->nome,
                'nomeArmazenado' => $anexo->nomeArmazenado,
                'tipoMime' => $anexo->tipoMime,
                'statusAnexo' => $anexo->status,
                'tamanho' => $anexo->tamanho,
                'caminhoArmazenamento' => $anexo->caminhoArmazenamento,
                'hashAnexo' => $anexo->hash,
            ]);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE anexos SET
                id_post = :postId,
                nome_arquivo = :nome,
                nome_armazenado = :nomeArmazenado,
                tipo_mime = :tipoMime,
                status_anexo = :statusAnexo,
                tamanho = :tamanho,
                caminho_armazenamento = :caminhoArmazenamento,
                hash_arquivo = :hashAnexo
             WHERE id = :id'
        );
        $stmt->execute([
            'postId' => $anexo->postId,
            'nome' => $anexo->nome,
            'nomeArmazenado' => $anexo->nomeArmazenado,
            'tipoMime' => $anexo->tipoMime,
            'statusAnexo' => $anexo->status,
            'tamanho' => $anexo->tamanho,
            'caminhoArmazenamento' => $anexo->caminhoArmazenamento,
            'hashAnexo' => $anexo->hash,
            'id' => $anexo->id,
        ]);

        return $anexo->id;
    }

    public function delete(int|Anexo $target): bool
    {
        $id = $target instanceof Anexo ? $target->id : $target;
        if ($id === null) {
            return false;
        }

        $stmt = Database::connection()->prepare('DELETE FROM anexos WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Anexo
    {
        return new Anexo(
            id: (int) $row['id'],
            postId: (int) $row['id_post'],
            nome: (string) $row['nome_arquivo'],
            nomeArmazenado: (string) ($row['nome_armazenado'] ?? ''),
            tipoMime: (string) ($row['tipo_mime'] ?? ''),
            status: (string) $row['status_anexo'],
            tamanho: (int) ($row['tamanho'] ?? 0),
            caminhoArmazenamento: (string) $row['caminho_armazenamento'],
            hash: (string) ($row['hash_arquivo'] ?? ''),
            dataUpload: $row['data_upload'] ?? null
        );
    }
}
