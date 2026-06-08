<?php

declare(strict_types=1);

// Servidor embutido do PHP (php -S): serve arquivos estáticos diretamente.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Auth;
use App\Router;
use App\Session;
use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\MatchController;
use App\Controllers\PredictionController;
use App\Controllers\RankingController;

Session::start();

$r = new Router();

$r->map('GET', '/', fn() => Auth::check() ? redirect('/ranking') : redirect('/login'));

// Autenticação / convites
$r->map('GET',  '/login',           [AuthController::class, 'showLogin']);
$r->map('POST', '/login',           [AuthController::class, 'login']);
$r->map('POST', '/logout',          [AuthController::class, 'logout']);
$r->map('GET',  '/convite/{token}', [AuthController::class, 'showRegister']);
$r->map('POST', '/convite/{token}', [AuthController::class, 'register']);

// Jogos e palpites
$r->map('GET',  '/jogos',                [MatchController::class, 'index']);
$r->map('POST', '/jogos/{id}/palpite',   [MatchController::class, 'submit']);

// Ranking / minhas apostas
$r->map('GET',  '/ranking',         [RankingController::class, 'index']);
$r->map('GET',  '/minhas-apostas',  [PredictionController::class, 'mine']);

// Admin
$r->map('GET',  '/admin/convites',              [AdminController::class, 'invites']);
$r->map('POST', '/admin/convites',              [AdminController::class, 'createInvite']);
$r->map('POST', '/admin/convites/{id}/revogar', [AdminController::class, 'revokeInvite']);
$r->map('GET',  '/admin/config',                [AdminController::class, 'settings']);
$r->map('POST', '/admin/config',                [AdminController::class, 'saveSettings']);
$r->map('POST', '/admin/sync',                  [AdminController::class, 'sync']);

$r->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
