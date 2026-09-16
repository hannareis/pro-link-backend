<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\ArtController;
use App\Controllers\AuthController;
use App\Controllers\CartaVirtualController;
use App\Controllers\CatController;
use App\Controllers\CompetenciaController;
use App\Controllers\DemandaController;
use App\Controllers\DemonstracaoInteresseController;
use App\Controllers\DenunciaController;
use App\Controllers\EmpresaController;
use App\Controllers\EspecialidadeController;
use App\Controllers\ExperienciaController;
use App\Controllers\FeedController;
use App\Controllers\NotificacaoController;
use App\Controllers\PortfolioController;
use App\Controllers\PostAnexoController;
use App\Controllers\ProfissionalController;
use App\Controllers\ProjetoController;
use App\Controllers\TosController;
use App\Controllers\UniversidadeController;
use App\Controllers\UniversitarioController;
use App\Controllers\UserController;
use App\Controllers\PostController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\RoleMiddleware;
use App\Middleware\SanitizeInputMiddleware;
use App\Models\User;

/** @var \App\Core\Router $router */

// RF01 - autenticacao e cadastro. POSTs passam por sanitizacao e protecao CSRF.
$router->get('auth/login', [AuthController::class, 'showLogin']);
$router->post('auth/login', [AuthController::class, 'login'], [SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('auth/register', [AuthController::class, 'register'], [SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('auth/logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
$router->post('/recover-password', [AuthController::class, 'recoverPassword'], [SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('/reset-password', [AuthController::class, 'resetPassword'], [SanitizeInputMiddleware::class, CsrfMiddleware::class]);

// RF04 - feed curado pelo Agente de Recomendacao; exige usuario autenticado.
$router->get('/feed', [FeedController::class, 'index'], [AuthMiddleware::class]);
$router->post('/feed', [FeedController::class, 'store'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);

// RF07 - notificacoes internas do painel do usuario autenticado.
$router->get('/notificacoes', [NotificacaoController::class, 'index'], [AuthMiddleware::class]);
$router->post('/notificacoes/marcar-lida', [NotificacaoController::class, 'markAsRead'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);

// RF01/RF03 - visualizacao publica de perfil; edicao exige autenticacao.
$router->get('/perfil/{id}', [UserController::class, 'show']);
$router->post('/perfil', [UserController::class, 'update'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('/perfil/remover', [UserController::class, 'destroy'], [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/perfil/privacidade', [UserController::class, 'privacySettings'], [AuthMiddleware::class]);
$router->post('/perfil/privacidade', [UserController::class, 'updatePrivacySettings'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);

// RF03 - portfolio profissional/academico/empresarial.
$router->get('/portfolio/{id}', [PortfolioController::class, 'show']);
$router->post('/portfolio', [PortfolioController::class, 'store'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('/portfolio/{id}', [PortfolioController::class, 'update'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('/portfolio/{id}/remover', [PortfolioController::class, 'destroy'], [AuthMiddleware::class, CsrfMiddleware::class]);

// RF04 - cadastro e listagem de demandas (aciona o NLP de recomendacao de area).
$router->get('/demandas', [DemandaController::class, 'index'], [AuthMiddleware::class]);
$router->post('/demandas', [DemandaController::class, 'store'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->get('/demandas/{id}', [DemandaController::class, 'show'], [AuthMiddleware::class]);
$router->post('/demandas/{id}/editar', [DemandaController::class, 'update'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('/demandas/{id}/remover', [DemandaController::class, 'destroy'], [AuthMiddleware::class, CsrfMiddleware::class]);

// RF05 - comunicação inicial entre empresas, instituições e profissionais através da criação de posts.
$router->post('/posts', [PostController::class, 'store'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('/posts/edit', [PostController::class, 'update'], [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('/posts/{id}/anexos', [PostAnexoController::class, 'store'], [SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->post('/posts/{id}/like', [PostController::class, 'likePost'], [AuthMiddleware::class]);

// RF06 - painel administrativo, restrito ao perfil admin via RoleMiddleware.
$router->get('/admin', [AdminController::class, 'dashboard'], [AuthMiddleware::class, new RoleMiddleware([User::PERFIL_ADMIN])]);
$router->get('/admin/usuarios', [AdminController::class, 'manageUsers'], [AuthMiddleware::class, new RoleMiddleware([User::PERFIL_ADMIN_CREA])]);
$router->post('/admin/usuarios/status', [AdminController::class, 'toggleUserStatus'], [AuthMiddleware::class, new RoleMiddleware([User::PERFIL_ADMIN_CREA]), SanitizeInputMiddleware::class, CsrfMiddleware::class]);
$router->get('/admin/moderacao', [AdminController::class, 'moderation'], [AuthMiddleware::class, new RoleMiddleware([User::PERFIL_ADMIN_CREA])]);
$router->get('/admin/auditoria', [AdminController::class, 'auditLogs'], [AuthMiddleware::class, new RoleMiddleware([User::PERFIL_ADMIN_CREA])]);
$router->get('/admin/dados-publicos', [AdminController::class, 'dadosPublicos'], [AuthMiddleware::class, new RoleMiddleware([User::PERFIL_ADMIN_CREA])]);

// ============================================================
// Rotas das entidades do estrutura.sql
// Convencoes: escrita passa por SanitizeInput + CSRF; acoes de validacao/
// moderacao exigem o perfil ADMIN_CREA (RoleMiddleware).
// ============================================================

$auth = [AuthMiddleware::class];
$write = [AuthMiddleware::class, SanitizeInputMiddleware::class, CsrfMiddleware::class];
$adminWrite = [AuthMiddleware::class, new RoleMiddleware([User::PERFIL_ADMIN_CREA]), SanitizeInputMiddleware::class, CsrfMiddleware::class];

// RF02/RF04 - busca na Tabela de Obras e Servicos (TOS) da API oficial do CREA-AM.
$router->get('/tos', [TosController::class, 'search']);

// RF01/RF03 - catalogo de universidades (leitura publica, escrita ADMIN_CREA).
$router->get('/universidades', [UniversidadeController::class, 'index']);
$router->get('/universidades/{id}', [UniversidadeController::class, 'show']);
$router->post('/universidades', [UniversidadeController::class, 'store'], $adminWrite);
$router->post('/universidades/{id}', [UniversidadeController::class, 'update'], $adminWrite);
$router->post('/universidades/{id}/desativar', [UniversidadeController::class, 'destroy'], $adminWrite);

// RF03 - catalogos de competencias e especialidades.
$router->get('/competencias', [CompetenciaController::class, 'index']);
$router->post('/competencias', [CompetenciaController::class, 'store'], $adminWrite);
$router->post('/competencias/{id}', [CompetenciaController::class, 'update'], $adminWrite);
$router->post('/competencias/{id}/desativar', [CompetenciaController::class, 'destroy'], $adminWrite);

$router->get('/especialidades', [EspecialidadeController::class, 'index']);
$router->post('/especialidades', [EspecialidadeController::class, 'store'], $adminWrite);
$router->post('/especialidades/{id}', [EspecialidadeController::class, 'update'], $adminWrite);
$router->post('/especialidades/{id}/desativar', [EspecialidadeController::class, 'destroy'], $adminWrite);

// RF02/RF03 - perfil profissional e seus vinculos.
$router->get('/profissionais/{id}', [ProfissionalController::class, 'show']);
$router->post('/profissionais', [ProfissionalController::class, 'store'], $write);
$router->post('/profissionais/competencias', [ProfissionalController::class, 'syncCompetencias'], $write);
$router->post('/profissionais/especialidades', [ProfissionalController::class, 'syncEspecialidades'], $write);
$router->post('/profissionais/{id}/validar', [ProfissionalController::class, 'validar'], $adminWrite);

// RF01/RF03 - perfil academico do universitario.
$router->get('/universitarios/{id}', [UniversitarioController::class, 'show']);
$router->post('/universitarios', [UniversitarioController::class, 'store'], $write);
$router->post('/universitarios/remover', [UniversitarioController::class, 'destroy'], $write);

// RF02 - consulta (somente leitura) dos dados oficiais da empresa no CREA-AM.
$router->get('/empresas/crea', [EmpresaController::class, 'show'], $auth);
$router->get('/empresas/crea/quadro-tecnico', [EmpresaController::class, 'quadroTecnico'], $auth);
$router->get('/empresas/crea/cao', [EmpresaController::class, 'cao'], $auth);

// RF03 - projetos do portfolio.
$router->get('/projetos', [ProjetoController::class, 'index'], $auth);
$router->get('/projetos/{id}', [ProjetoController::class, 'show']);
$router->post('/projetos', [ProjetoController::class, 'store'], $write);
$router->post('/projetos/{id}', [ProjetoController::class, 'update'], $write);
$router->post('/projetos/{id}/remover', [ProjetoController::class, 'destroy'], $write);

// RF03 - experiencias do portfolio.
$router->get('/experiencias', [ExperienciaController::class, 'index'], $auth);
$router->get('/experiencias/{id}', [ExperienciaController::class, 'show']);
$router->post('/experiencias', [ExperienciaController::class, 'store'], $write);
$router->post('/experiencias/{id}', [ExperienciaController::class, 'update'], $write);
$router->post('/experiencias/{id}/remover', [ExperienciaController::class, 'destroy'], $write);
$router->post('/experiencias/vincular-projeto', [ExperienciaController::class, 'linkProjeto'], $write);
$router->post('/experiencias/desvincular-projeto', [ExperienciaController::class, 'unlinkProjeto'], $write);

// RF02/RF03 - ARTs do portfolio (validacao restrita ao ADMIN_CREA).
$router->get('/arts', [ArtController::class, 'index'], $auth);
$router->get('/arts/{id}', [ArtController::class, 'show']);
$router->post('/arts', [ArtController::class, 'store'], $write);
$router->post('/arts/{id}', [ArtController::class, 'update'], $write);
$router->post('/arts/{id}/validar', [ArtController::class, 'validar'], $adminWrite);
$router->post('/arts/{id}/remover', [ArtController::class, 'destroy'], $write);

// RF02/RF03 - CATs do portfolio + verificacao publica de autenticidade.
$router->get('/cats', [CatController::class, 'index'], $auth);
$router->get('/cats/verificar', [CatController::class, 'verificar']);
$router->get('/cats/{id}', [CatController::class, 'show']);
$router->post('/cats', [CatController::class, 'store'], $write);
$router->post('/cats/{id}', [CatController::class, 'update'], $write);
$router->post('/cats/{id}/status', [CatController::class, 'atualizarStatus'], $adminWrite);
$router->post('/cats/{id}/remover', [CatController::class, 'destroy'], $write);

// RF04/RF05 - demonstracoes de interesse sobre demandas.
$router->get('/demandas/{id}/interesses', [DemonstracaoInteresseController::class, 'porDemanda'], $auth);
$router->get('/meus-interesses', [DemonstracaoInteresseController::class, 'meusInteresses'], $auth);
$router->post('/interesses', [DemonstracaoInteresseController::class, 'store'], $write);
$router->post('/interesses/{id}/status', [DemonstracaoInteresseController::class, 'atualizarStatus'], $write);
$router->post('/interesses/{id}/cancelar', [DemonstracaoInteresseController::class, 'cancelar'], $write);

// RF06 - denuncias (abertura por qualquer usuario; analise pelo ADMIN_CREA).
$router->get('/denuncias', [DenunciaController::class, 'index'], [AuthMiddleware::class, new RoleMiddleware([User::PERFIL_ADMIN_CREA])]);
$router->get('/denuncias/{id}', [DenunciaController::class, 'show'], [AuthMiddleware::class, new RoleMiddleware([User::PERFIL_ADMIN_CREA])]);
$router->post('/denuncias', [DenunciaController::class, 'store'], $write);
$router->post('/denuncias/{id}/analisar', [DenunciaController::class, 'analisar'], $adminWrite);

// Cartas virtuais: criadas e visiveis apenas para o autor (contem e-mails pessoais).
$router->get('/cartas-virtuais', [CartaVirtualController::class, 'index'], $auth);
$router->get('/cartas-virtuais/{id}', [CartaVirtualController::class, 'show'], $auth);
$router->post('/cartas-virtuais', [CartaVirtualController::class, 'store'], $write);
$router->post('/cartas-virtuais/{id}/editar', [CartaVirtualController::class, 'update'], $write);
$router->post('/cartas-virtuais/{id}/remover', [CartaVirtualController::class, 'destroy'], $write);
