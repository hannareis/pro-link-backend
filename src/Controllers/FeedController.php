<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\RecomendacaoService;

// RF04 - feed interativo de compartilhamento de experiencias, curado por recomendacao.
class FeedController
{
    public function __construct(
        private readonly RecomendacaoService $recomendacaoService = new RecomendacaoService()
    ) {
    }

    // Lista publicacoes do feed, priorizando as aderentes ao interesse do usuario.
    public function index(Request $request): void
    {
        // RF04 - feed curado pelo algoritmo de recomendacao
    }

    // Publica uma nova experiencia/post no feed.
    public function store(Request $request): void
    {
        // Publicacao de experiencias/posts
    }
}
