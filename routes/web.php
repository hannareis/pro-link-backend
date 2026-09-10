<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\CartaVirtualController;
use App\Controllers\DemandaController;
use App\Controllers\FeedController;
use App\Controllers\PortfolioController;
use App\Controllers\UserController;
use App\Controllers\PostController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\SanitizeInputMiddleware;
use App\Models\User;

/** @var \App\Core\Router $router */

// RF01 - autenticacao e cadastro. POSTs passam por sanitizacao e protecao CSRF.
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'], [SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('/register', [AuthController::class, 'register'], [SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);

// RF04 - feed curado pelo Agente de Recomendacao; exige usuario autenticado.
$router->get('/feed', [FeedController::class, 'index'], [AuthMiddleware::class]);
$router->post('/feed', [FeedController::class, 'store'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);

// RF01/RF03 - visualizacao publica de perfil; edicao exige autenticacao.
$router->get('/perfil/{id}', [UserController::class, 'show']);
$router->post('/perfil', [UserController::class, 'update'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);

// RF03 - portfolio profissional/academico/empresarial.
$router->get('/portfolio/{id}', [PortfolioController::class, 'show']);
$router->post('/portfolio', [PortfolioController::class, 'store'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);

// RF04 - cadastro e listagem de demandas (aciona o NLP de recomendacao de area).
$router->get('/demandas', [DemandaController::class, 'index'], [AuthMiddleware::class]);
$router->post('/demandas', [DemandaController::class, 'store'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);

// RF05 - fluxo de Cartas Virtuais: check-in (redige e valida) e check-out (envia por e-mail).
$router->post('/cartas-virtuais/checkin', [CartaVirtualController::class, 'checkin'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('/cartas-virtuais/{id}/checkout', [CartaVirtualController::class, 'checkout'], [AuthMiddleware::class, CsrfMiddleware::class]);

// RF05 - comunicação inicial entre empresas, instituições e profissionais através da criação de posts.
$router->post('/posts', [PostController::class, 'store'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);

// RF06 - painel administrativo, restrito ao perfil admin via RoleMiddleware.
$router->get('/admin', [AdminController::class, 'dashboard'], [AuthMiddleware::class, new RoleMiddleware([User::PERFIL_ADMIN])]);
