<?php

declare(strict_types=1);

namespace App\Core;

// Encapsula os dados da requisicao HTTP atual, para os Controllers nao acessarem
// $_GET/$_POST/$_SERVER diretamente.
class Request
{
    public readonly array $query;
    public readonly array $body;
    public readonly array $server;
    public readonly array $files;

    // Captura o estado das superglobais no momento em que a Request e criada.
    public function __construct()
    {
        $this->query = $_GET;
        $this->files = $_FILES;

        $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) { 
            $rawInput = file_get_contents('php://input');
            $this->body = json_decode($rawInput, true) ?? [];
        } else {
            $this->body = $_POST;
        }
        $this->server = $_SERVER;
    }

    // Busca um valor no corpo da requisicao, com fallback para a query string.
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    // Retorna os dados do usuario autenticado na sessao, ou null se nao houver login.
    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    // Retorna os dados de um arquivo enviado (multipart/form-data), ou null se o
    // campo nao foi enviado ou nenhum arquivo foi selecionado.
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $file;
    }
}
