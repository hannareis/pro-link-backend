<?php

declare(strict_types=1);

// Funcoes globais de conveniencia, carregadas via "files" no composer.json.

// Gera (uma unica vez por sessao) e retorna o token usado pelo CsrfMiddleware.
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}

// Le uma chave de configuracao no formato "secao.opcao" (ex: "app.name"), a
// partir do _config.php da raiz (Anexo I, item 8.3.1), carregado uma unica
// vez por requisicao.
if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $config = null;

        if ($config === null) {
            $config = require dirname(__DIR__, 2) . '/_config.php';
        }

        [$section, $option] = array_pad(explode('.', $key, 2), 2, null);

        return $option !== null ? ($config[$section][$option] ?? $default) : ($config[$section] ?? $default);
    }
}
