<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpClient;

// Item 4.3 da proposta - complementa dados que nao estao disponiveis na API oficial do desafio.
class DadosPublicosService
{
    private readonly HttpClient $http;

    // Monta o cliente HTTP do portal de dados abertos do governo federal (CKAN). Sem
    // token: e uma API publica, nao faz parte da API oficial do desafio (item 8.4).
    public function __construct()
    {
        $this->http = new HttpClient(
            baseUrl: (string) config('dados_publicos.base_url'),
        );
    }

    // Busca dados complementares em bases publicas (dados.gov.br), com auditoria humana e
    // respeito a LGPD: o resultado e sempre indicativo, sujeito a revisao pelo ADMIN_CREA
    // antes de qualquer uso, nunca gravado automaticamente no cadastro do usuario.
    public function buscarComplementar(string $termo): array
    {
        $termo = trim($termo);

        if ($termo === '') {
            return [];
        }

        $resposta = $this->http->get('/action/package_search', ['q' => $termo, 'rows' => 10]);

        if ($resposta['status'] !== 200 || !($resposta['body']['success'] ?? false)) {
            return [];
        }

        $resultados = $resposta['body']['result']['results'] ?? [];

        return array_map(fn (array $item) => [
            'titulo' => $item['title'] ?? null,
            'organizacao' => $item['organization']['title'] ?? null,
            'notas' => $item['notes'] ?? null,
            'url' => isset($item['name']) ? 'https://dados.gov.br/dataset/' . $item['name'] : null,
        ], $resultados);
    }
}
