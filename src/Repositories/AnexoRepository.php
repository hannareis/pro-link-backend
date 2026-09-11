<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Anexo;
use App\Core\Database;

use DateTime;

class AnexoRepository
{
    public function save(Anexo $anexo): int
    {
        $pdo = Database::connection();
        
        if ($anexo->id === null)
            {
                $stmt = $pdo->prepare
                (
                    'INSERT INTO anexos (id_post, nome_arquivo, nome_armazenado, tipo_mime, status_anexo, tamanho, caminho_armazenamento, hash_arquivo, data_upload) VALUES (:postId, :nome, :nomeArmazenado, :tipoMime, :statusAnexo, :tamanho, :caminhoArmazenamento, :hashAnexo, :dataUpload)'
                );
                $stmt->execute
                (
                    [
                        'postId' => $anexo->postId,
                        'nome' => $anexo->nome,
                        'nomeArmazenado' => $anexo->nomeArmazenado,
                        'tipoMime' => $anexo->tipoMime,
                        'statusAnexo' => $anexo->status,
                        'tamanho' => $anexo->tamanho,
                        'caminhoArmazenamento' => $anexo->caminhoArmazenamento,
                        'hashAnexo' => $anexo->hash,
                        'dataUpload' => $anexo->dataUpload
                    ]
                );
                return (int) $pdo->lastInsertId();
            }
        $stmt = $pdo->prepare
        (
            'UPDATE anexos SET nome_arquivo = :nomeArquivo, status_anexo = :statusAnexo WHERE id = :id'
        );
        $stmt->execute(['id' => $anexo->id]);

        return $anexo->id;
    }

    public function delete(?Anexo $anexo): bool
    {
        if ($anexo === null) return false;

        $pdo = Database::connection();

        $stmt = $pdo->prepare
        (
            'DELETE FROM anexos WHERE id = :id'
        );
        $stmt->execute(['id' => $anexo->id]);

        return $stmt->rowCount() > 0;
    }

    public function findById(int $id): ?Anexo
    {
        $pdo = Database::connection();

        $stmt = $pdo->prepare
        (
            'SELECT * FROM anexos WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    public function hydrate(array $row): Anexo
    {
        return new Anexo
        (
            id: (int) $row['id'],
            postId: (int) $row['id_post'],
            nome: $row['nome_arquivo'],
            nomeArmazenado: $row['nome_armazenado'],
            tipoMime: $row['tipo_mime'],
            status: $row['status_anexo'],
            tamanho: (int) $row['tamanho'],
            caminhoArmazenamento: $row['caminho_armazenamento'],
            hash: $row['hash_arquivo'],
            dataUpload: new DateTime($row['data_upload'])
        );
    }
}