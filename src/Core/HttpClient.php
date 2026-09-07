<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

// Cliente HTTP minimo baseado em cURL nativo, usado para consumir APIs externas
// (ex: API REST oficial do CREA-AM, RF02). Evita depender de bibliotecas externas
// como Guzzle para uma unica integracao.
class HttpClient
{
    public function __construct(
        private readonly string $baseUrl = '',
        private readonly array $defaultHeaders = [],
        private readonly int $timeoutSeconds = 10,
    ) {
    }

    // Executa um GET e decodifica a resposta JSON.
    public function get(string $endpoint, array $query = []): array
    {
        $url = $this->baseUrl . $endpoint;

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $this->request('GET', $url);
    }

    // Executa um POST com corpo JSON e decodifica a resposta.
    public function post(string $endpoint, array $body = []): array
    {
        return $this->request('POST', $this->baseUrl . $endpoint, $body);
    }

    // Monta e executa a requisicao cURL, retornando status HTTP + corpo decodificado.
    private function request(string $method, string $url, ?array $body = null): array
    {
        $ch = curl_init($url);

        $headers = array_merge(['Accept: application/json'], $this->defaultHeaders);

        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => $headers,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
            $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException("Falha ao consumir {$url}: {$error}");
        }

        $decoded = json_decode($response, true);

        return [
            'status' => $status,
            'body' => is_array($decoded) ? $decoded : [],
        ];
    }
}
