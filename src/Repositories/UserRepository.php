<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

// Unico ponto de SQL sobre a tabela `usuarios` do estrutura.sql.
class UserRepository
{
    // Busca um usuario ativo pelo e-mail (usado no login).
    public function findByEmail(string $email): ?User
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM usuarios WHERE email = :email AND conta_ativa = 1 LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Busca um usuario ativo pelo username (usado no login).
    public function findByUsername(string $username): ?User
    {
        $stmt = Database::connection()->prepare(
            'SELECT * FROM usuarios WHERE nome = :username AND conta_ativa = 1 LIMIT 1'
        );
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Busca um usuario pelo id (traz inclusive contas inativas, para o admin).
    public function findById(int $id): ?User
    {
        $stmt = Database::connection()->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    // Lista usuarios, opcionalmente filtrando por perfil de acesso.
    public function all(?string $perfilAcesso = null): array
    {
        $sql = 'SELECT * FROM usuarios';
        $params = [];

        if ($perfilAcesso !== null) {
            $sql .= ' WHERE perfil_acesso = :perfil';
            $params['perfil'] = $perfilAcesso;
        }

        $sql .= ' ORDER BY nome';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return array_map($this->hydrate(...), $stmt->fetchAll());
    }

    // Insere (id nulo) ou atualiza um usuario e retorna o id persistido.
    public function save(User $user): int
    {
        $pdo = Database::connection();

        if ($user->id === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios
                    (tipo_pessoa, perfil_acesso, tipo_conta, nome, senha_hash, telefone, email, conta_ativa)
                 VALUES
                    (:tipo_pessoa, :perfil_acesso, :tipo_conta, :nome, :senha_hash, :telefone, :email, :conta_ativa)'
            );
            $stmt->execute([
                'tipo_pessoa' => $user->tipoPessoa,
                'perfil_acesso' => $user->perfilAcesso,
                'tipo_conta' => $user->tipoConta,
                'nome' => $user->nome,
                'senha_hash' => $user->senhaHash,
                'telefone' => $user->telefone,
                'email' => $user->email,
                'conta_ativa' => $user->contaAtiva ? 1 : 0,
            ]);

            return (int) $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare(
            'UPDATE usuarios SET
                tipo_pessoa = :tipo_pessoa,
                perfil_acesso = :perfil_acesso,
                tipo_conta = :tipo_conta,
                nome = :nome,
                senha_hash = :senha_hash,
                telefone = :telefone,
                email = :email,
                conta_ativa = :conta_ativa
             WHERE id = :id'
        );
        $stmt->execute([
            'tipo_pessoa' => $user->tipoPessoa,
            'perfil_acesso' => $user->perfilAcesso,
            'tipo_conta' => $user->tipoConta,
            'nome' => $user->nome,
            'senha_hash' => $user->senhaHash,
            'telefone' => $user->telefone,
            'email' => $user->email,
            'conta_ativa' => $user->contaAtiva ? 1 : 0,
            'id' => $user->id,
        ]);

        return $user->id;
    }

    // Marca a data do ultimo login e zera o contador de tentativas.
    public function registrarLoginBemSucedido(int $id): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE usuarios
                SET ultimo_login_em = CURRENT_TIMESTAMP, tentativas_login = 0, bloqueado_ate = NULL
             WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    // Incrementa o contador de tentativas; bloqueia a conta ao atingir o limite.
    public function registrarTentativaFalha(int $id, int $limite = 5, int $minutosBloqueio = 15): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE usuarios SET
                tentativas_login = tentativas_login + 1,
                bloqueado_ate = CASE
                    WHEN tentativas_login + 1 >= :limite
                    THEN DATE_ADD(CURRENT_TIMESTAMP, INTERVAL :minutos MINUTE)
                    ELSE bloqueado_ate
                END
             WHERE id = :id'
        );
        $stmt->bindValue('limite', $limite, PDO::PARAM_INT);
        $stmt->bindValue('minutos', $minutosBloqueio, PDO::PARAM_INT);
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    // Desativa a conta (exclusao logica): conta_ativa = 0 preserva a auditoria.
    public function desativar(int $id): bool
    {
        $stmt = Database::connection()->prepare('UPDATE usuarios SET conta_ativa = 0 WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    // Converte uma linha da tabela `usuarios` em um objeto User tipado.
    private function hydrate(array $row): User
    {
        return new User(
            id: (int) $row['id'],
            nome: (string) $row['nome'],
            email: (string) $row['email'],
            senhaHash: (string) $row['senha_hash'],
            telefone: (string) $row['telefone'],
            tipoPessoa: (string) $row['tipo_pessoa'],
            perfilAcesso: (string) $row['perfil_acesso'],
            tipoConta: (string) $row['tipo_conta'],
            contaAtiva: (bool) $row['conta_ativa'],
            ultimoLoginEm: $row['ultimo_login_em'] ?? null,
            tentativasLogin: (int) $row['tentativas_login'],
            bloqueadoAte: $row['bloqueado_ate'] ?? null,
            criadoEm: $row['criado_em'] ?? null,
            atualizadoEm: $row['atualizado_em'] ?? null,
        );
    }
}
