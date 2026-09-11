<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Cat;
use App\Repositories\CatRepository;

// RF02/RF03 - Certidoes de Acervo Tecnico vinculadas ao portfolio.
class CatController
{
    public function __construct(
        private readonly CatRepository $cats = new CatRepository()
    ) {
    }

    public function index(Request $request): void
    {
        $idPortfolio = (int) $request->input('id_portfolio');

        Response::json(['data' => $this->cats->listByPortfolio($idPortfolio)]);
    }

    public function show(Request $request): void
    {
        $cat = $this->cats->findById((int) $request->input('id'));

        if ($cat === null) {
            Response::json(['message' => 'CAT nao encontrada.'], 404);
            return;
        }

        Response::json(['data' => $cat]);
    }

    // Verificacao publica de autenticidade pelo codigo impresso na certidao.
    public function verificar(Request $request): void
    {
        $cat = $this->cats->findByCodigoAutenticidade((string) $request->input('codigo', ''));

        Response::json([
            'autentica' => $cat !== null,
            'data' => $cat,
        ]);
    }

    public function store(Request $request): void
    {
        $codigo = (string) $request->input('codigo_autenticidade', '');

        if ($codigo !== '' && $this->cats->findByCodigoAutenticidade($codigo) !== null) {
            Response::json(['message' => 'Ja existe uma CAT com este codigo de autenticidade.'], 409);
            return;
        }

        $id = $this->cats->save($this->fromRequest($request));

        Response::json(['message' => 'CAT registrada.', 'id' => $id], 201);
    }

    public function update(Request $request): void
    {
        $id = (int) $request->input('id');

        if ($this->cats->findById($id) === null) {
            Response::json(['message' => 'CAT nao encontrada.'], 404);
            return;
        }

        $cat = $this->fromRequest($request);
        $cat->id = $id;
        $this->cats->save($cat);

        Response::json(['message' => 'CAT atualizada.']);
    }

    // RF02 - ADMIN_CREA altera o status da certidao (VALIDA, REJEITADA, CANCELADA...).
    public function atualizarStatus(Request $request): void
    {
        $id = (int) $request->input('id');
        $status = (string) $request->input('status_cat', Cat::STATUS_VALIDA);

        if ($this->cats->findById($id) === null) {
            Response::json(['message' => 'CAT nao encontrada.'], 404);
            return;
        }

        $this->cats->atualizarStatus($id, $status);

        Response::json(['message' => 'Status da CAT atualizado.', 'status' => $status]);
    }

    public function destroy(Request $request): void
    {
        $this->cats->delete((int) $request->input('id'));

        Response::json(['message' => 'CAT removida.']);
    }

    private function fromRequest(Request $request): Cat
    {
        return new Cat(
            idPortfolio: (int) $request->input('id_portfolio'),
            numeroCertidao: (string) $request->input('numero_certidao', ''),
            codigoAutenticidade: (string) $request->input('codigo_autenticidade', ''),
            dataEmissao: $request->input('data_emissao'),
            validade: $request->input('validade'),
            statusCat: (string) $request->input('status_cat', Cat::STATUS_PENDENTE),
            idProfissionalResponsavel: (int) $request->input('id_profissional_responsavel'),
            idContratante: $request->input('id_contratante') !== null
                ? (int) $request->input('id_contratante')
                : null,
            idProprietario: $request->input('id_proprietario') !== null
                ? (int) $request->input('id_proprietario')
                : null,
            documentoCat: $request->input('documento_cat'),
            observacoes: $request->input('observacoes'),
        );
    }
}
