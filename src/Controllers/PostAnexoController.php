<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Anexo;
use App\Repositories\AnexoRepository;

// RF05 - anexos (imagens, documentos) vinculados a um post.
// Refatorado com assistência de Inteligência Artificial para alinhamento aos padrões arquiteturais do projeto.
class PostAnexoController
{
    public function __construct(
        private readonly AnexoRepository $anexoRepository = new AnexoRepository()
    ) {
    }

    // [IA]: Ajuste do parâmetro para 'id_post' e envio da lista de anexos em formato JSON.
    public function index(Request $request): void
    {
        $idPost = (int) $request->input('id_post');

        Response::json(['data' => $this->anexoRepository->listById($idPost)]);
    }

    // [IA]: Padronização de formatação e tratamento de anexo inexistente com status 404.
    public function show(Request $request): void
    {
        $anexo = $this->anexoRepository->findById((int) $request->input('id'));

        if ($anexo === null) {
            Response::json(['message' => 'Anexo nao encontrado.'], 404);
            return;
        }

        Response::json(['data' => $anexo]);
    }

    // [IA]: Implementação da persistência utilizando fromRequest() e resposta padronizada JSON (status 201).
    public function store(Request $request): void
    {
        $id = $this->anexoRepository->save($this->fromRequest($request));

        Response::json(['message' => 'Anexo criado.', 'id' => $id], 201);
    }

    // [IA]: Ajuste da assinatura para retorno void e emissão de confirmação de exclusão via Response::json().
    public function destroy(Request $request): void
    {
        $this->anexoRepository->delete(
            $this->anexoRepository->findById((int) $request->input('id'))
        );

        Response::json(['message' => 'Anexo removido.']);
    }

    // [IA]: Criação de método auxiliar privado para construção do objeto Anexo a partir da Request.
    private function fromRequest(Request $request): Anexo
    {
        return new Anexo(
            id: null,
            postId: (int) $request->input('id_post'),
            nome: (string) $request->input('nome', ''),
            nomeArmazenado: (string) $request->input('nome_armazenado', ''),
            tipoMime: (string) $request->input('tipo_mime', ''),
            status: (string) $request->input('status_anexo', ''),
            tamanho: (int) $request->input('tamanho_anexo', 0),
            caminhoArmazenamento: (string) $request->input('caminho_armazenamento', ''),
            hash: (string) $request->input('hash_anexo', ''),
        );
    }
}
