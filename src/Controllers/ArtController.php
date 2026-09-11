<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Art;
use App\Repositories\ArtRepository;

// RF02/RF03 - Anotacoes de Responsabilidade Tecnica vinculadas ao portfolio.
// A validacao (validar) fica restrita ao ADMIN_CREA via RoleMiddleware.
class ArtController
{
    public function __construct(
        private readonly ArtRepository $arts = new ArtRepository()
    ) {
    }

    public function index(Request $request): void
    {
        $idPortfolio = (int) $request->input('id_portfolio');

        Response::json(['data' => $this->arts->listByPortfolio($idPortfolio)]);
    }

    public function show(Request $request): void
    {
        $art = $this->arts->findById((int) $request->input('id'));

        if ($art === null) {
            Response::json(['message' => 'ART nao encontrada.'], 404);
            return;
        }

        Response::json(['data' => $art]);
    }

    public function store(Request $request): void
    {
        if ($this->arts->findByNumero((string) $request->input('numero_art', '')) !== null) {
            Response::json(['message' => 'Ja existe uma ART com este numero.'], 409);
            return;
        }

        $id = $this->arts->save($this->fromRequest($request));

        Response::json(['message' => 'ART registrada.', 'id' => $id], 201);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->input('id');

        if ($this->arts->findById($id) === null) {
            Response::json(['message' => 'ART nao encontrada.'], 404);
            return;
        }

        $art = $this->fromRequest($request);
        $art->id = $id;
        $this->arts->save($art);

        Response::json(['message' => 'ART atualizada.']);
    }

    // RF02 - ADMIN_CREA aprova/rejeita a ART; marca validada_por_crea e data_validacao.
    public function validar(Request $request): void
    {
        $id = (int) $request->input('id');
        $status = (string) $request->input('status_art', Art::STATUS_APROVADA);

        if ($this->arts->findById($id) === null) {
            Response::json(['message' => 'ART nao encontrada.'], 404);
            return;
        }

        $this->arts->validar($id, $status);

        Response::json(['message' => 'ART validada pelo CREA.', 'status' => $status]);
    }

    public function destroy(Request $request): void
    {
        $this->arts->delete((int) $request->input('id'));

        Response::json(['message' => 'ART removida.']);
    }

    private function fromRequest(Request $request): Art
    {
        return new Art(
            idPortfolio: (int) $request->input('id_portfolio'),
            idProfissionalResponsavel: (int) $request->input('id_profissional_responsavel'),
            numeroArt: (string) $request->input('numero_art', ''),
            tipoArt: $request->input('tipo_art'),
            statusArt: (string) $request->input('status_art', Art::STATUS_PENDENTE),
            validadaPorCrea: (bool) $request->input('validada_por_crea', false),
            dataEmissao: $request->input('data_emissao'),
            dataValidacao: $request->input('data_validacao'),
            documentoArt: $request->input('documento_art'),
            observacoes: $request->input('observacoes'),
        );
    }
}
