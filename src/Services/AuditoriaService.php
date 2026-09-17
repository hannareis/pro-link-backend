<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Denuncia;
use App\Repositories\AuditoriaRepository;
use App\Repositories\DemandaRepository;
use App\Repositories\DenunciaRepository;
use App\Repositories\ProfissionalRepository;
use App\Repositories\UserRepository;

// RF06 - registro e consulta de auditoria das acoes criticas da plataforma.
class AuditoriaService
{
    public function __construct(
        private readonly AuditoriaRepository $auditoria = new AuditoriaRepository(),
        private readonly UserRepository $usuarios = new UserRepository(),
        private readonly ProfissionalRepository $profissionais = new ProfissionalRepository(),
        private readonly DenunciaRepository $denuncias = new DenunciaRepository(),
        private readonly DemandaRepository $demandas = new DemandaRepository()
    ) {
    }

    // Grava um log rastreavel de uma acao critica, sem expor dados sensiveis de negociacoes.
    // $acao segue o padrao "dominio.evento" (ex: "denuncia.criada"); o dominio vira aud_tabela
    // e o evento e mapeado para o ENUM aud_acao (INSERT/UPDATE/DELETE/EXCLUSAO_LOGICA) do estrutura.sql.
    public function registrar(int $usuarioId, string $acao, array $contexto = []): void
    {
        [$dominio, $evento] = array_pad(explode('.', $acao, 2), 2, $acao);

        $this->auditoria->registrar(new Auditoria(
            usuId: $usuarioId ?: null,
            audTabela: (string) $dominio,
            audRegistroId: $this->extrairRegistroId($contexto),
            audAcao: $this->mapearAcao((string) $evento),
            audDadosNovos: $contexto !== [] ? $contexto : null,
            audIp: $_SERVER['REMOTE_ADDR'] ?? null,
            audUserAgent: $_SERVER['HTTP_USER_AGENT'] ?? null,
        ));
    }

    // Monta indicadores analiticos/sinteticos exibidos no painel administrativo.
    public function relatorioGerencial(): array
    {
        $usuarios = $this->usuarios->all();
        $profissionais = $this->profissionais->all();

        return [
            'usuarios_total' => count($usuarios),
            'usuarios_ativos' => count(array_filter($usuarios, fn ($u) => $u->contaAtiva)),
            'usuarios_por_perfil' => array_count_values(array_map(fn ($u) => $u->perfilAcesso, $usuarios)),
            'profissionais_pendentes_validacao' => count(array_filter(
                $profissionais,
                fn ($p) => !$p->registroValidado
            )),
            'denuncias_pendentes' => count(array_filter(
                $this->denuncias->all(),
                fn ($d) => in_array($d->statusDenuncia, [Denuncia::STATUS_PENDENTE, Denuncia::STATUS_EM_ANALISE], true)
            )),
            'demandas_abertas' => count($this->demandas->all()),
        ];
    }

    // Usa o primeiro valor numerico de uma chave "id" ou terminada em "_id" encontrado no contexto.
    private function extrairRegistroId(array $contexto): int
    {
        foreach ($contexto as $chave => $valor) {
            if (is_numeric($valor) && ($chave === 'id' || str_ends_with((string) $chave, '_id'))) {
                return (int) $valor;
            }
        }

        return 0;
    }

    private function mapearAcao(string $evento): string
    {
        return match (true) {
            str_contains($evento, 'criad') => Auditoria::ACAO_INSERT,
            str_contains($evento, 'remov'), str_contains($evento, 'exclu'), str_contains($evento, 'desativ')
                => Auditoria::ACAO_EXCLUSAO_LOGICA,
            str_contains($evento, 'delet') => Auditoria::ACAO_DELETE,
            default => Auditoria::ACAO_UPDATE,
        };
    }
}
