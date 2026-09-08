<?php

declare(strict_types= 1);

namespace App\Models;

use DateTime;

// Classe abstrata para evitar repetição de propriedades iguais.
abstract class Publicavel
{
    public const STATUS_ATIVO = 'A';
    PUBLIC CONST STATUS_EXCLUIDO = 'X'; // Deletado pelo próprio usuário ou administração.
    // RF06
    public const STATUS_BLOQUEADO = 'B'; // On Hold para revisão pela administração. Omitido sem apagar.

    public function __construct(
        public ?int $id = null,
        public ?int $userId = null,
        public ?DateTime $dataDePostagem = null,
        public string $conteudo = "",
    ) {  
    }
}