<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\Notificacao;

// SQL sobre a tabela `notificacoes`.
class NotificacaoRepository
{
    public function findById(int $id): ?Notificacao
    {
        $stmt = Database::connection()->prepare('SELECT * FROM notificacoes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Lista as notificacoes do usuario, mais recentes primeiro. $apenasNaoLidas filtra lida = 0.
    public function listByUsuario(int $idUsuario, bool $apenasNaoLidas = false): array
    {
        $sql = 'SELECT * FROM notificacoes WHERE id_usuario = :id';

        if ($apenasNaoLidas) {
            $sql .= ' AND lida = 0';
        }

        $sql .= ' ORDER BY criado_em DESC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute(['id' => $idUsuario]);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    // Insere (id nulo) ou atualiza uma notificacao e retorna o id persistido.
    public function save(Notificacao $n): int
    {
        $pdo = Database::connection();

        if ($n->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO notificacoes (id_usuario, mensagem, lida) VALUES (:id_usuario, :mensagem, :lida)'
            );
            $stmt->execute([
                'id_usuario' => $n->idUsuario,
                'mensagem' => $n->mensagem,
                'lida' => $n->lida ? 1 : 0,
            ]);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare('UPDATE notificacoes SET mensagem = :mensagem, lida = :lida WHERE id = :id');
        $stmt->execute([
            'mensagem' => $n->mensagem,
            'lida' => $n->lida ? 1 : 0,
            'id' => $n->id,
        ]);

        return $n->id;
    }

    // Marca uma notificacao como lida. Confere o dono para evitar que um usuario marque a de outro.
    public function marcarComoLida(int $id, int $idUsuario): bool
    {
        $stmt = Database::connection()->prepare(
            'UPDATE notificacoes SET lida = 1 WHERE id = :id AND id_usuario = :id_usuario'
        );

        return $stmt->execute(['id' => $id, 'id_usuario' => $idUsuario]) && $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('DELETE FROM notificacoes WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    private function hydrate(array $row): Notificacao
    {
        return new Notificacao(
            id: (int) $row['id'],
            idUsuario: (int) $row['id_usuario'],
            mensagem: (string) $row['mensagem'],
            lida: (bool) $row['lida'],
            criadoEm: $row['criado_em'] ?? null,
        );
    }
}
