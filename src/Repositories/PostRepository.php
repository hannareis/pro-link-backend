<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Post;

class PostRepository
{
    public function findById(int $id): ?Post
    {
        $stmt = Database::connection()->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function listByAutor(int $idAutor): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM posts WHERE id_autor = :id ORDER BY data_postagem DESC, id DESC'
        );
        $stmt->execute(['id' => $idAutor]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function listById(int $id): array
    {
        return $this->listByAutor($id);
    }

    // Lista o feed publico com autor, contagens de curtidas/comentarios e se o usuario
    // atual ja curtiu cada post (Anexo I, RF04 - card do feed exibe esses dados).
    // $especialidade filtra por area de atuacao do autor (profissionais.especialidades);
    // $grauAcademico filtra pelo grau do autor (profissionais ou universitarios);
    // $ordem controla a ordenacao cronologica ('ASC' ou 'DESC').
    public function all(
        int $usuarioAtual = 0,
        int $limit = 50,
        ?string $especialidade = null,
        ?string $grauAcademico = null,
        string $ordem = 'DESC',
        ?string $tipoConta = null,
        ?string $busca = null
    ): array {
        $ordem = strtoupper($ordem) === 'ASC' ? 'ASC' : 'DESC';

        $condicoes = ['p.status_post = :status'];
        $params = [
            'status' => Post::STATUS_PUBLICO,
            'statusComentario' => Post::STATUS_PUBLICO,
            'usuarioAtual' => $usuarioAtual,
            'limit' => $limit,
        ];

        if ($tipoConta !== null && $tipoConta !== '') {
            $condicoes[] = 'u.tipo_conta = :tipoConta';
            $params['tipoConta'] = $tipoConta;
        }

        if ($especialidade !== null && $especialidade !== '') {
            $condicoes[] = 'EXISTS (
                SELECT 1 FROM profissional_especialidades pe
                JOIN especialidades esp ON esp.id = pe.id_especialidade
                WHERE pe.id_profissional = p.id_autor AND esp.nome = :especialidade
            )';
            $params['especialidade'] = $especialidade;
        }

        if ($grauAcademico !== null && $grauAcademico !== '') {
            $condicoes[] = '(
                EXISTS (SELECT 1 FROM profissionais pr WHERE pr.id_usuario = p.id_autor AND pr.grau_academico = :grau)
                OR EXISTS (SELECT 1 FROM universitarios uni WHERE uni.id_usuario = p.id_autor AND uni.grau_academico = :grau)
            )';
            $params['grau'] = $grauAcademico;
        }

        if ($busca !== null && $busca !== '') {
            $condicoes[] = '(p.titulo LIKE :busca OR p.conteudo LIKE :busca)';
            $params['busca'] = '%' . $busca . '%';
        }

        $stmt = Database::connection()->prepare(
            'SELECT
                p.*,
                u.nome AS autor_nome,
                u.tipo_conta AS autor_tipo_conta,
                (SELECT COUNT(*) FROM likes_posts lp WHERE lp.id_post = p.id) AS curtidas,
                (SELECT COUNT(*) FROM comentarios c WHERE c.id_post = p.id AND c.status_comentario = :statusComentario) AS comentarios,
                EXISTS(
                    SELECT 1 FROM likes_posts lpm WHERE lpm.id_post = p.id AND lpm.id_usuario = :usuarioAtual
                ) AS curtido_por_mim,
                (SELECT a.caminho_armazenamento FROM anexos a WHERE a.id_post = p.id ORDER BY a.id ASC LIMIT 1) AS imagem_caminho
             FROM posts p
             JOIN usuarios u ON u.id = p.id_autor
             WHERE ' . implode(' AND ', $condicoes) . "
             ORDER BY p.data_postagem {$ordem}, p.id {$ordem}
             LIMIT :limit"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, $key === 'usuarioAtual' || $key === 'limit' ? \PDO::PARAM_INT : \PDO::PARAM_STR);
        }
        $stmt->execute();

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function findByUserId(int $userId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT
                p.*,
                (SELECT a.caminho_armazenamento FROM anexos a WHERE a.id_post = p.id ORDER BY a.id ASC LIMIT 1) AS imagem_caminho
             FROM posts p
             WHERE p.id_autor = :userId
             ORDER BY p.data_postagem DESC, p.id DESC'
        );
        $stmt->bindValue(':userId', $userId, \PDO::PARAM_INT);
        $stmt->execute();

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    public function save(Post $post): int
    {
        $pdo = Database::connection();

        if ($post->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO posts
                    (id_autor, titulo, conteudo, status_post)
                 VALUES
                    (:idAutor, :titulo, :conteudo, :statusPost)'
            );
            $stmt->execute([
                'idAutor' => $post->userId,
                'titulo' => $post->titulo,
                'conteudo' => $post->conteudo,
                'statusPost' => $post->status,
            ]);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE posts SET
                titulo = :titulo,
                conteudo = :conteudo,
                status_post = :statusPost
             WHERE id = :id'
        );
        $stmt->execute([
            'titulo' => $post->titulo,
            'conteudo' => $post->conteudo,
            'statusPost' => $post->status,
            'id' => $post->id,
        ]);

        return $post->id;
    }

    public function delete(int|Post $target): bool
    {
        $id = $target instanceof Post ? $target->id : $target;
        if ($id === null) {
            return false;
        }

        $stmt = Database::connection()->prepare('DELETE FROM posts WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Post
    {
        return new Post(
            id: (int) $row['id'],
            userId: (int) $row['id_autor'],
            dataDePostagem: $row['data_postagem'] ?? null,
            dataEdicao: $row['atualizado_em'] ?? null,
            conteudo: (string) $row['conteudo'],
            status: (string) $row['status_post'],
            titulo: (string) ($row['titulo'] ?? ''),
            // Uploads ficam em public/uploads (servidos como estatico pelo nginx - ver
            // docker/nginx/default.conf) - precisa da URL absoluta do backend porque o
            // front roda em outra origem (porta 8081).
            imagemUrl: !empty($row['imagem_caminho'])
                ? rtrim((string) config('app.url'), '/') . '/' . $row['imagem_caminho']
                : null,
            autorNome: isset($row['autor_nome']) ? (string) $row['autor_nome'] : null,
            autorTipoConta: isset($row['autor_tipo_conta']) ? (string) $row['autor_tipo_conta'] : null,
            curtidas: (int) ($row['curtidas'] ?? 0),
            comentarios: (int) ($row['comentarios'] ?? 0),
            curtidoPorMim: (bool) ($row['curtido_por_mim'] ?? false),
        );
    }
}
