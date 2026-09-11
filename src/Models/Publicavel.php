<?php

declare(strict_types= 1);

namespace App\Models;

use DateTime;

// Classe abstrata para evitar repetição de propriedades iguais.
abstract class Publicavel
{
    public const STATUS_PUBLICO = 'PUBLICO';
    public const STATUS_PRIVADO = 'PRIVADO'; 
    public const STATUS_ARQUIVADO = 'ARQUIVADO'; // Deletado pelo próprio usuário. Arquivado por X dias antes de deletar.
    // RF06
    public const STATUS_SUSPENSO = 'SUSPENSO_PELO_CREA'; // On Hold para revisão pela administração. Omitido sem apagar.

    public function __construct(
        public ?int $id = null,
        public ?int $userId = null,
        public ?DateTime $dataDePostagem = null,
        public string $conteudo = "",
        public string $status = self::STATUS_PUBLICO
    ) {  
    }
}