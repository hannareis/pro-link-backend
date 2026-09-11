<?php

declare(strict_types= 1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Anexo;
use App\Repositories\AnexoRepository;

use DateTime;

class PostAnexoController
{
    public function __construct(
        private readonly AnexoRepository $anexoRepository = new AnexoRepository()
    ) {
    }

    public function store(Request $request): void
    {
        $postId = (int) $request->input('id_post', '');
        $nome = (string) $request->input('nome', '');
        $nomeArmazenado = (string) $request->input('nome_armazenado', '');
        $tipoMime = (string) $request->input('tipo_mime', '');
        $status = (string) $request->input('status_anexo', '');
        $tamanho = (int) $request->input('tamano_anexo', '');
        $caminho = (string) $request->input('caminho_armazenamento', '');
        $hash = (string) $request->input('hash_anexo', '');

        $anexo = new Anexo
        (
            id: null,
            postId: $postId,
            nome: $nome,
            nomeArmazenado: $nomeArmazenado,
            tipoMime: $tipoMime,
            status: $status,
            tamanho: $tamanho,
            caminhoArmazenamento: $caminho,
            hash: $hash,
            dataUpload: new DateTime(),
        );

        $id = $this->anexoRepository->save($anexo);
    }

    public function delete(Request $request): bool
    {
        $id = (int) $request->input('id_anexo', '');

        return $this->anexoRepository->delete($this->anexoRepository->findById($id));
    }
}