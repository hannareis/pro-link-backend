<?php

declare(strict_types=1);

namespace App\Models;

// RF05 - Comunicação inicial entre empresas, instituições e profissionais. Além de perfis universitários.
class Post extends Publicavel
{
    public function __construct(
        ?int $id = null, int $userId = 0, ?string $dataDePostagem = null, ?string $dataEdicao = null, string $conteudo = "", string $status = self::STATUS_PUBLICO,
        public string $titulo = "",
        // Campos agregados por PostRepository::all() para exibicao no feed (Anexo I, RF04).
        public ?string $autorNome = null,
        public ?string $autorTipoConta = null,
        public int $curtidas = 0,
        public int $comentarios = 0,
        public bool $curtidoPorMim = false,
    ) {
        parent::__construct($id, $userId, $dataDePostagem, $dataEdicao, $conteudo, $status);
    }
}