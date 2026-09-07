<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Router;

// Front Controller: unico ponto de entrada de toda a aplicacao web.
require dirname(__DIR__) . '/vendor/autoload.php';

// Carrega variaveis do .env para $_ENV (safeLoad nao quebra se o arquivo nao existir).
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

// Dispara o carregamento do _config.php (timezone, charset, PATHs e config geral).
config('app');

// Sessao necessaria para autenticacao (Request::user()) e token CSRF.
// Cookie httpOnly configurado antes de abrir a sessao (Anexo I, item 8.5-a).
Auth::configureSessionCookie();
session_start();

// Registra as rotas definidas em routes/web.php no Router.
$router = new Router();
require dirname(__DIR__) . '/routes/web.php';

// Despacha a requisicao atual: Router -> Middlewares -> Controller.
$router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);
