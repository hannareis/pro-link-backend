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
        $this->routes[$method][] = compact('path', 'handler', 'middlewares');
    }

    // Testa se $path corresponde ao padrao da rota (ex: "/posts/{id}/like"), convertendo
    // os segmentos "{nome}" em grupos nomeados de regex. Preenche $params por referencia.
    private function matches(string $pattern, string $path, array &$params): bool
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);

        if (!preg_match('#^' . $regex . '$#', $path, $matches)) {
            return false;
        }

        $params = array_filter($matches, fn($key) => !is_int($key), ARRAY_FILTER_USE_KEY);
        return true;
    }

    // Resolve a rota da requisicao atual e executa middlewares + Controller na ordem correta.
    // Duas passagens: primeiro rotas literais (sem "{}"), depois rotas dinamicas - assim
    // "/perfil/privacidade" nunca e capturada por "/perfil/{id}" registrada antes dela.
    public function dispatch(string $method, string $path): void
    {
        $candidatas = $this->routes[$method] ?? [];
        $params = [];
        $route = null;

        foreach ([false, true] as $dinamica) {
            foreach ($candidatas as $candidata) {
                if (str_contains($candidata['path'], '{') !== $dinamica) {
                    continue;
                }
                if ($this->matches($candidata['path'], $path, $params)) {
                    $route = $candidata;
                    break 2;
                }
            }
        }

        // Nenhuma rota corresponde: responde 404 com a view de erro.
        if ($route === null) {
            http_response_code(404);
            (new View())->render('errors/404');
            return;
        }

        $request = new Request($params);

        // Pipeline de middlewares (ex: sanitizacao -> CSRF -> autenticacao -> perfil).
        // Cada item pode ser um class-string (instanciado sem argumentos) ou uma
        // instancia ja configurada (ex: new RoleMiddleware(['admin'])).
        foreach ($matchedRoute['middlewares'] as $middleware) {
            $instance = is_string($middleware) ? new $middleware() : $middleware;
            $instance->handle($request);
        }

        // Chama o Controller/acao correspondente, ja com a requisicao validada.
        [$controllerClass, $action] = $matchedRoute['handler'];
        (new $controllerClass())->$action($request);
    }
}
