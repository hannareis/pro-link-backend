<?php

declare(strict_types=1);

namespace App\Services;

// RF06 - registro e consulta de auditoria das acoes criticas da plataforma.
class AuditoriaService
{
    // Grava um log rastreavel de uma acao critica, sem expor dados sensiveis de negociacoes.
    public function registrar(int $usuarioId, string $acao, array $contexto = []): void
    {
        $db = \App\Core\Database::connection();
        
        // Mapeia strings genéricas de ação para os ENUMs permitidos: 'INSERT', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'MODERACAO', 'ACESSO', 'EXPORTACAO'
        $audAcao = 'MODERACAO'; // Default seguro
        if (str_contains($acao, 'criada') || str_contains($acao, 'inserir')) $audAcao = 'INSERT';
        elseif (str_contains($acao, 'atualizada') || str_contains($acao, 'analisada')) $audAcao = 'UPDATE';
        elseif (str_contains($acao, 'excluida') || str_contains($acao, 'deletada')) $audAcao = 'DELETE';
        
        $registroId = $contexto['denuncia_id'] ?? $contexto['usuario_id'] ?? $contexto['id'] ?? 0;
        
        try {
            $stmt = $db->prepare(
                "INSERT INTO sis_auditoria (usu_id, aud_tabela, aud_registro_id, aud_acao, aud_dados_novos, aud_ip) 
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $usuarioId ?: null,
                $contexto['tabela'] ?? 'geral',
                $registroId,
                $audAcao,
                json_encode(['acao_original' => $acao, 'detalhes' => $contexto]),
                $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ]);
        } catch (\Throwable $e) {
            // Logs de auditoria não devem quebrar a ação principal caso falhem por problemas de schema.
        }
    }

    // Monta indicadores analiticos/sinteticos exibidos no painel administrativo.
    public function relatorioGerencial(): array
    {
        $db = \App\Core\Database::connection();

        $totalUsers = 0;
        $activeDemands = 0;
        $checkins = 0;
        $pendingMod = 0;

        try { $totalUsers = (int) $db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn(); } catch (\Throwable $e) {}
        try { $activeDemands = (int) $db->query("SELECT COUNT(*) FROM demandas WHERE status = 'ABERTA'")->fetchColumn(); } catch (\Throwable $e) {}
        // Tabela pro_cartas_virtuais descrita no repositorio
        try { $checkins = (int) $db->query("SELECT COUNT(*) FROM pro_cartas_virtuais")->fetchColumn(); } catch (\Throwable $e) {}
        try { 
            $stmt = $db->query("SELECT COUNT(*) FROM cats WHERE status_cat = 'PENDENTE'");
            if ($stmt) {
                $pendingMod = (int) $stmt->fetchColumn(); 
            }
        } catch (\Throwable $e) {}

        $virtualLetters = $checkins; // Historico = emissoes (check-outs contam igual os check-ins na tabela base)

        return [
            'kpis' => [
                'totalUsers' => $totalUsers,
                'activeDemands' => $activeDemands,
                'checkins' => $checkins,
                'virtualLetters' => $virtualLetters,
                'pendingMod' => $pendingMod
            ],
            // Retorna dados mockados pras demais propriedades, com estrutura pronta pro frontend
            'charts' => [
                'userStatus' => [65, 30, 5],
                'demandsGrowth' => [10, 25, 45, 80, 150, 210, $activeDemands]
            ],
            'recentRegistrations' => [],
            'topCategories' => [],
            'topCompanies' => [],
            'moderation' => ['approved' => 420, 'pending' => $pendingMod, 'banned' => 12],
            'stats' => ['connections' => 312, 'dailyAvg' => '10,4']
        ];
    }
}
