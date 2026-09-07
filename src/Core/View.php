<?php

declare(strict_types=1);

namespace App\Core;

// Renderiza um template PHP puro a partir da pasta views/, sem misturar logica de negocio.
class View
{
    // Extrai os dados para variaveis locais e inclui o arquivo do template.
    public function render(string $template, array $data = []): void
    {
        extract($data);
        require PATH_VIEWS . "/{$template}.php";
    }
}
