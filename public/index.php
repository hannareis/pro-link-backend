<?php

declare(strict_types=1);

use App\Core\Auth;
use App\Core\Router;

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Front Controller: unico ponto de entrada de toda a aplicacao web.
require dirname(__DIR__) . '/vendor/autoload.php';

// Configuração de CORS (Cross-Origin Resource Sharing) para SPA
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header("Access-Control-Allow-Origin: $origin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Trata requisições preflight (OPTIONS) do navegador
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
