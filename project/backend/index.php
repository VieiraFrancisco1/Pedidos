<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use App\Container;
use App\ResponseManager;
use App\Router;

// Gerenciar CORS e headers
$responseManager = new ResponseManager();
$responseManager->setCORSHeaders();

// Lidar com preflight do CORS
if ($responseManager->handlePreflight()) {
    exit;
}

// Rotear requisição
$container = Container::getInstance();
$router = new Router($container);
$router->route();
