<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\ProjetoImagem;

// SQL sobre a tabela `projeto_imagens` (galeria de um projeto do portfolio).
class ProjetoImagemRepository
{
    public function listByProjeto(int $idProjeto): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM projeto_imagens WHERE id_projeto = :id ORDER BY ordem ASC, id ASC'
        );
        $stmt->execute(['id' => $idProjeto]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(ProjetoImagem $imagem): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO projeto_imagens
                (id_projeto, nome_arquivo, nome_armazenado, tipo_mime, tamanho, caminho_armazenamento, ordem)
             VALUES
                (:projetoId, :nome, :nomeArmazenado, :tipoMime, :tamanho, :caminhoArmazenamento, :ordem)'
        );
        $stmt->execute([
            'projetoId' => $imagem->projetoId,
            'nome' => $imagem->nome,
            'nomeArmazenado' => $imagem->nomeArmazenado,
            'tipoMime' => $imagem->tipoMime,
            'tamanho' => $imagem->tamanho,
            'caminhoArmazenamento' => $imagem->caminhoArmazenamento,
            'ordem' => $imagem->ordem,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function delete(int|ProjetoImagem $target): bool
    {
        $id = $target instanceof ProjetoImagem ? $target->id : $target;
        if ($id === null) {
            return false;
        }

        $stmt = Database::connection()->prepare('DELETE FROM projeto_imagens WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    // Remove todas as imagens de um projeto (usado ao substituir a galeria inteira).
    public function deleteAllByProjeto(int $idProjeto): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM projeto_imagens WHERE id_projeto = :id');

        return $stmt->execute(['id' => $idProjeto]);
    }

    private function hydrate(array $row): ProjetoImagem
    {
        return new ProjetoImagem(
            id: (int) $row['id'],
            projetoId: (int) $row['id_projeto'],
            nome: (string) $row['nome_arquivo'],
            nomeArmazenado: (string) ($row['nome_armazenado'] ?? ''),
            tipoMime: (string) ($row['tipo_mime'] ?? ''),
            tamanho: (int) ($row['tamanho'] ?? 0),
            caminhoArmazenamento: (string) $row['caminho_armazenamento'],
            ordem: (int) ($row['ordem'] ?? 0),
            criadoEm: $row['criado_em'] ?? null,
        );
    }
}
