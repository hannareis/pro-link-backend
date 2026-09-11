<?php

declare(strict_types= 1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Models\Curtida;
use App\Repositories\AnexoRepository;
use App\Repositories\CurtidaPostRepository;
use App\Repositories\PostRepository;
use App\Models\Post;

use DateTime;

class PostController
{
    public function __construct(
        private readonly CurtidaPostRepository $curtidaPostRepository = new CurtidaPostRepository(),
        private readonly PostRepository $postRepository = new PostRepository(),
        private readonly AnexoRepository $anexoRepository = new AnexoRepository()
        ) {}

    public function store(Request $request): void
    {
        $userId = (int) $request->user()['id'];
           
        $titulo = (string) $request->input('titulo', '');
        $conteudo = (string) $request->input('conteudo', '');
        $statusPost = (string) $request->input('status', '');
        
        $post = new Post
        (
            id: null,
            userId: $userId,
            dataDePostagem: new DateTime(),
            dataEdicao: new DateTime(),
            conteudo: $conteudo,
            titulo: $titulo,
            status: $statusPost
        );
            
        $this->postRepository->save($post);
            
        Response::redirect("/posts/{id}");
        return;
    }
            
    public function delete(Request $request): bool
    {
        $postId = (int) $request->input('id_post', '');
            
        return $this->postRepository->delete($this->postRepository->findById($postId));
    }
            
    // Adiciona a curtida ao repositório de curtidas atreladas ao post. Ou remove ela, caso o post já tenha sido curtido.
    public function likePost(Request $request): void
    {
        $userId = (int)$request->user()['id'];
        $postId = (int)$request->input('postId', '');

        $curtida = $this->curtidaPostRepository->findByUsuarioEPost($userId, $postId);

        if ($curtida === null)
            {
                $curtida = new Curtida
                (
                    userId: $userId,
                    publicavelId: $postId
                );

                $this->curtidaPostRepository->save($curtida);
            }
        else
            {
                $this->curtidaPostRepository->delete($curtida);
            }
        return;
    }

    // Edita o post atrelado ao usuário.
    public function editPost(Request $request): void
    {
        $user = $request->user();
        
        $titulo = (string) $request->input('titulo', '');
        $conteudo = (string) $request->input('conteudo', '');
        $statusPost = (string) $request->input('status', '');

        $post = $this->postRepository->findById((int)$request->input('id', ''));

        // Caso não exista um post com o ID fornecido, redireciona.
        if ($post === null)
            {
                Response::redirect("/posts");
                return;
            }
        
        // Caso o post exista, mas o usuário logado não é o autor do post (possível quebra de segurança), redireciona
        if ((int) $user['id'] != $post->userId)
            {
                // Indicação: bloquear o usuário pela possível quebra de segurança, ou retornar com uma mensagem de erro.
                Response::redirect("/posts/edit");
                return;
            }

        $post->titulo = $titulo;
        $post->conteudo = $conteudo;
        $post->status = $statusPost;

        $this->postRepository->save($post);

        Response::redirect('/posts/{id}');
        return;
    }


}