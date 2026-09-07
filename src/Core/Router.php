<?php

declare(strict_types=1);

namespace App\Core;

// Mapeia metodo HTTP + caminho para um Controller/acao, passando antes pelos Middlewares.
class Router
{
    private array $routes = [];

    // Registra uma rota GET.
    public function get(string $path, array $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
    }

    // Registra uma rota POST.
    public function post(string $path, array $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    // Guarda o handler (Controller + acao) e a lista de middlewares da rota.
    private function add(string $method, string $path, array $handler, array $middlewares): void
    {
        $this->routes[$method][$path] = compact('handler', 'middlewares');
    }

    // Resolve a rota da requisicao atual e executa middlewares + Controller na ordem correta.
    public function dispatch(string $method, string $path): void
    {
        $route = $this->routes[$method][$path] ?? null;

        // Nenhuma rota corresponde: responde 404 com a view de erro.
        if ($route === null) {
            http_response_code(404);
            (new View())->render('errors/404');
            return;
        }

        $request = new Request();

        // Pipeline de middlewares (ex: sanitizacao -> CSRF -> autenticacao -> perfil).
        // Cada item pode ser um class-string (instanciado sem argumentos) ou uma
        // instancia ja configurada (ex: new RoleMiddleware(['admin'])).
        foreach ($route['middlewares'] as $middleware) {
            $instance = is_string($middleware) ? new $middleware() : $middleware;
            $instance->handle($request);
        }

        // Chama o Controller/acao correspondente, ja com a requisicao validada.
        [$controllerClass, $action] = $route['handler'];
        (new $controllerClass())->$action($request);
    }
}
