<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Anexo;
use App\Models\Curtida;
use App\Models\Post;
use App\Repositories\AnexoRepository;
use App\Repositories\CurtidaPostRepository;
use App\Repositories\PostRepository;
use App\Services\FileUploadService;

// RF05 - criacao, edicao e interacao com posts (curtidas).
// Refatorado com assistência de Inteligência Artificial para alinhamento aos padrões arquiteturais do projeto.
class PostController
{
    // MIME real (via fileinfo) -> extensao aceita para a midia do post (campo "midia").
    private const MIDIA_MIMES_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    private const MIDIA_TAMANHO_MAXIMO_BYTES = 5 * 1024 * 1024;

    public function __construct(
        private readonly PostRepository $postRepository = new PostRepository(),
        private readonly CurtidaPostRepository $curtidaPostRepository = new CurtidaPostRepository(),
        private readonly AnexoRepository $anexoRepository = new AnexoRepository(),
        private readonly FileUploadService $fileUploadService = new FileUploadService(
            self::MIDIA_MIMES_PERMITIDOS,
            self::MIDIA_TAMANHO_MAXIMO_BYTES
        ),
    ) {
    }

    public function index(Request $request): void
    {
        $userId = $request->input('usuario_id');
        
        if ($userId) {
            $posts = $this->postRepository->findByUserId((int) $userId);
        } else {
            $posts = $this->postRepository->all();
        }

        Response::json(['data' => $posts]);
    }

    // [IA]: Adequação para obtenção do usuário via auth_id() e envio de resposta JSON com status 201.
    // Upload de midia (campo "midia", enviado por postCriar.js) segue o mesmo padrao de
    // CartaVirtualController: valida/salva o arquivo antes de criar o registro principal,
    // e so entao vincula o Anexo ao post recem-criado.
    public function store(Request $request): void
    {
        $userId = auth_id();

        try {
            $midia = $this->fileUploadService->store($request->file('midia'), 'posts');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage()], 400);
            return;
        } catch (\RuntimeException $e) {
            Response::json(['message' => $e->getMessage()], 500);
            return;
        }

        $post = new Post(
            userId: $userId,
            conteudo: (string) $request->input('conteudo', ''),
            titulo: (string) $request->input('titulo', ''),
            status: (string) $request->input('status', Post::STATUS_PUBLICO),
        );

        $id = $this->postRepository->save($post);

        if ($midia !== null) {
            $this->anexoRepository->save(new Anexo(
                postId: $id,
                nome: $midia['nome_arquivo'],
                nomeArmazenado: $midia['nome_armazenado'],
                tipoMime: $midia['tipo_mime'],
                tamanho: $midia['tamanho'],
                caminhoArmazenamento: $midia['caminho_armazenamento'],
            ));
        }

        Response::json(['message' => 'Post criado.', 'id' => $id], 201);
    }

    // [IA]: Implementação da consulta de post por ID com retorno dos dados ou código de erro 404.
    public function show(Request $request): void
    {
        $post = $this->postRepository->findById((int) $request->input('id'));

        if ($post === null) {
            Response::json(['message' => 'Post nao encontrado.'], 404);
            return;
        }

        Response::json(['data' => $post]);
    }

    // [IA]: Padronização do método para update, validação de autor (403), registro (404) e resposta em JSON.
    // Midia (campo "midia", postEditar.js): um novo arquivo substitui o anexo atual do
    // post; "remover_midia=1" sem arquivo novo apenas remove o anexo atual.
    public function update(Request $request): void
    {
        $id = (int) $request->input('id');
        $userId = auth_id();

        $post = $this->postRepository->findById($id);

        if ($post === null) {
            Response::json(['message' => 'Post nao encontrado.'], 404);
            return;
        }

        // Validação de permissão: assegura que apenas o autor da publicação pode alterá-la.
        if ($userId !== $post->userId) {
            Response::json(['message' => 'Sem permissao para editar este post.'], 403);
            return;
        }

        try {
            $midia = $this->fileUploadService->store($request->file('midia'), 'posts');
        } catch (\InvalidArgumentException $e) {
            Response::json(['message' => $e->getMessage()], 400);
            return;
        } catch (\RuntimeException $e) {
            Response::json(['message' => $e->getMessage()], 500);
            return;
        }

        $post->titulo = (string) $request->input('titulo', $post->titulo);
        $post->conteudo = (string) $request->input('conteudo', $post->conteudo);
        $post->status = (string) $request->input('status', $post->status);
        $this->postRepository->save($post);

        $removerMidia = (string) $request->input('remover_midia', '') === '1';

        if ($midia !== null || $removerMidia) {
            foreach ($this->anexoRepository->listByPost($id) as $anexoExistente) {
                $this->anexoRepository->delete($anexoExistente);
            }
        }

        if ($midia !== null) {
            $this->anexoRepository->save(new Anexo(
                postId: $id,
                nome: $midia['nome_arquivo'],
                nomeArmazenado: $midia['nome_armazenado'],
                tipoMime: $midia['tipo_mime'],
                tamanho: $midia['tamanho'],
                caminhoArmazenamento: $midia['caminho_armazenamento'],
            ));
        }

        Response::json(['message' => 'Post atualizado.']);
    }

    // [IA]: Ajuste da assinatura para retorno void e envio de confirmação de exclusão em formato JSON.
    public function destroy(Request $request): void
    {
        $post = $this->postRepository->findById((int) $request->input('id'));
        if ($post === null) {
            Response::json(['message' => 'Post nao encontrado.'], 404);
            return;
        }

        if (auth_id() !== $post->userId) {
            Response::json(['message' => 'Sem permissao para deletar este post.'], 403);
            return;
        }

        $this->postRepository->delete($post);

        Response::json(['message' => 'Post removido.']);
    }

    // [IA]: Adequação da lógica de curtida/descurtida com auth_id() e retorno formalizado via Response::json().
    public function likePost(Request $request): void
    {
        $userId = auth_id();
        $postId = (int) $request->input('id');

        $curtida = $this->curtidaPostRepository->findByUsuarioEPost($userId, $postId);

        if ($curtida === null) {
            $curtida = new Curtida(
                userId: $userId,
                publicavelId: $postId,
            );
            $this->curtidaPostRepository->save($curtida);

            Response::json(['message' => 'Post curtido.']);
            return;
        }

        $this->curtidaPostRepository->delete($curtida);

        Response::json(['message' => 'Curtida removida.']);
    }
}
