<?php
declare(strict_types=1);
use App\Controllers\SiteController as Site;
use App\Controllers\ChatController;
use App\Controllers\Admin\{AuthController, EntityController};

/** @var App\Core\Router $router */
$router->get('/', [Site::class, 'home']);
$router->get('/about', [Site::class, 'about']);
$router->get('/services', [Site::class, 'services']);
$router->get('/projects', [Site::class, 'projects']);
$router->get('/cyber-security', [Site::class, 'cyber']);
$router->post('/cyber-security/assessment', [Site::class, 'submitAssessment']);
$router->get('/contact', [Site::class, 'contact']);
$router->post('/contact', [Site::class, 'submitContact']);
$router->get('/track-order', [Site::class, 'trackOrder']);
$router->post('/track-order', [Site::class, 'submitTrack']);
$router->post('/order', [Site::class, 'submitOrder']);
$router->get('/solutions/{slug:[a-z0-9-]+}', [Site::class, 'solution']);
$router->get('/erp/{id:[A-Za-z0-9]+}', [Site::class, 'erpDetail']);
$router->get('/prefs', [Site::class, 'prefs']);
$router->post('/api/chat', [ChatController::class, 'message']);

$router->get('/admin/login', [AuthController::class, 'loginForm']);
$router->post('/admin/login', [AuthController::class, 'login']);
$router->post('/admin/logout', [AuthController::class, 'logout']);
$router->get('/admin/forgot-password', [AuthController::class, 'forgotForm']);
$router->post('/admin/forgot-password', [AuthController::class, 'forgot']);
$router->get('/admin/reset-password', [AuthController::class, 'resetForm']);
$router->post('/admin/reset-password', [AuthController::class, 'reset']);
$router->get('/admin/account', [AuthController::class, 'account']);
$router->post('/admin/account', [AuthController::class, 'updateAccount']);

$router->get('/admin', [EntityController::class, 'dashboard']);
$router->post('/admin/upload', [EntityController::class, 'upload']);
$router->get('/admin/{key:[a-z]+}', [EntityController::class, 'index']);
$router->get('/admin/{key:[a-z]+}/export.csv', [EntityController::class, 'export']);
$router->get('/admin/{key:[a-z]+}/new', [EntityController::class, 'create']);
$router->post('/admin/{key:[a-z]+}', [EntityController::class, 'store']);
$router->get('/admin/{key:[a-z]+}/{id:\d+}/edit', [EntityController::class, 'edit']);
$router->post('/admin/{key:[a-z]+}/{id:\d+}', [EntityController::class, 'update']);
$router->post('/admin/{key:[a-z]+}/{id:\d+}/delete', [EntityController::class, 'destroy']);
$router->post('/admin/{key:[a-z]+}/{id:\d+}/status', [EntityController::class, 'status']);
