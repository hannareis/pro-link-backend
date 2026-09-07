<?php

declare(strict_types=1);

namespace App\Services;

// RF06 - registro e consulta de auditoria das acoes criticas da plataforma.
class AuditoriaService
{
    // Grava um log rastreavel de uma acao critica, sem expor dados sensiveis de negociacoes.
    public function registrar(int $usuarioId, string $acao, array $contexto = []): void
    {
        // RF06 - grava log rastreavel para acoes criticas (exclui dados sensiveis de negociacoes)
    }

    // Monta indicadores analiticos/sinteticos exibidos no painel administrativo.
    public function relatorioGerencial(): array
    {
        // RF06 - indicadores analiticos/sinteticos da plataforma
        return [];
    }
}
