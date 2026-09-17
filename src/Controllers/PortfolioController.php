<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Portfolio;
use App\Repositories\ArtRepository;
use App\Repositories\CatRepository;
use App\Repositories\ExperienciaRepository;
use App\Repositories\PessoaFisicaRepository;
use App\Repositories\PortfolioRepository;
use App\Repositories\ProfissionalRepository;
use App\Repositories\ProjetoRepository;
use App\Repositories\UserRepository;
use App\Services\CreaApiService;

// RF03 - portfolio profissional/academico/empresarial, vitrine principal do usuario.
class PortfolioController
{
    public function __construct(
        private readonly PortfolioRepository $portfolios = new PortfolioRepository(),
        private readonly PessoaFisicaRepository $pessoasFisicas = new PessoaFisicaRepository(),
        private readonly ProfissionalRepository $profissionais = new ProfissionalRepository(),
        private readonly ProjetoRepository $projetos = new ProjetoRepository(),
        private readonly UserRepository $usuarios = new UserRepository(),
        private readonly ExperienciaRepository $experiencias = new ExperienciaRepository(),
        private readonly ArtRepository $arts = new ArtRepository(),
        private readonly CatRepository $cats = new CatRepository(),
        private readonly CreaApiService $creaApiService = new CreaApiService()
    ) {
    }

    // Exibe o portfolio com suas abas: resumo, competencias, ARTs/CATs, projetos, experiencias.
    public function show(Request $request): void
    {
        //$portfolio = $this->portfolios->findById((int) $request->input('id'));
        $userId = (int) $request->user()['id'];
        $portfolio = $this->portfolios->findByUsuarioId($userId);

        $user = $this->usuarios->findById($userId);

        if ($portfolio === null) {
            Response::json(['message' => 'Portfolio não encontrado.' . $request->user()['id']], 404);
            return;
        }

        $idUsuario = $portfolio->idUsuario;
        $profissional = $this->profissionais->findByUsuarioId($idUsuario) ?: null;

        Response::json([
            'usuario' => [
                'nome' => $user->nome,
                'email' => $user->email
            ],
            'profissional' => [
                'categoria_profissional' => ($profissional !== null) ? $profissional->categoriaProfissional : '',
                'registro_validado' => ($profissional !== null) ? $profissional->registroValidado : false,
                'numero_registro_confrea_crea' => ($profissional !== null) ? $profissional->numeroRegistroConfeaCrea : '',
            ],
            'portfolio' => [
                'links_contato' => [
                    'linkedin' => $portfolio->linksContato[0] ?? null,
                    'github' => $portfolio->linksContato[1] ?? null
                ],
                'resumo_profissional' => $portfolio->resumoProfissional,
            ],
            'competencias' => $this->profissionais->competenciasDoProfissional($idUsuario) ?: [],
            'experiencias' => $this->experiencias->listByPortfolio((int) $portfolio->id),
            'projetos' => $this->projetos->listByPortfolio((int) $portfolio->id),
            'acervo_tecnico' => [
                'arts_aprovadas' => $this->arts->listByPortfolio((int) $portfolio->id),
                'cats_validas' => $this->cats->listByPortfolio((int) $portfolio->id),
            ],
        ]);
    }

    // Cria/atualiza (upsert) o portfolio do usuario autenticado.
    public function store(Request $request): void
    {
        $usuarioId = auth_id();

        if ($usuarioId === 0) {
            Response::json(['message' => 'Sessão inválida.'], 401);
            return;
        }

        // A tabela `portfolio` referencia `pessoa_fisica`; contas de empresa (JURIDICA) nao tem portfolio.
        if ($this->pessoasFisicas->findByUsuarioId($usuarioId) === null) {
            Response::json(['message' => 'Portfolio disponível apenas para pessoas físicas.'], 422);
            return;
        }

        $existente = $this->portfolios->findByUsuarioId($usuarioId);

        $portfolio = new Portfolio(
            id: $existente?->id,
            idUsuario: $usuarioId,
            resumoProfissional: $request->input('resumo_profissional'),
            documentoIdentificacao: $request->input('documento_identificacao'),
            linksContato: (array) $request->input('links_contato', []),
        );

        $id = $this->portfolios->save($portfolio);

        Response::json(['message' => 'Portfolio salvo.', 'id' => $id], 201);
    }

    // Atualiza um portfolio existente (identificado por ?id=).
    public function update(Request $request): void
    {
        $portfolio = $this->portfolios->findById((int) $request->input('id'));

        if ($portfolio === null) {
            Response::json(['message' => 'Portfolio não encontrado.'], 404);
            return;
        }

        $portfolio->resumoProfissional = $request->input('resumo_profissional', $portfolio->resumoProfissional);
        $portfolio->documentoIdentificacao = $request->input('documento_identificacao', $portfolio->documentoIdentificacao);
        $portfolio->linksContato = (array) $request->input('links_contato', $portfolio->linksContato);

        $this->portfolios->save($portfolio);

        Response::json(['message' => 'Portfolio atualizado.']);
    }

    // Remove um portfolio existente (identificado por ?id=). O ON DELETE CASCADE
    // do estrutura.sql remove junto projetos, experiencias, ARTs e CATs vinculados.
    public function destroy(Request $request): void
    {
        $id = (int) $request->input('id');

        if ($this->portfolios->findById($id) === null) {
            Response::json(['message' => 'Portfolio não encontrado.'], 404);
            return;
        }

        $this->portfolios->delete($id);

        Response::json(['message' => 'Portfolio removido.']);
    }
}
