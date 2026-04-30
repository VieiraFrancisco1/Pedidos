<?php
declare(strict_types=1);

namespace App;

use App\Controllers\PedidoController;
use Throwable;

final class Router
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function route(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'OPTIONS') {
            http_response_code(204);
            return;
        }

        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $path = rtrim($path, '/') ?: '/';

        try {
            if ($path === '/') {
                if ($method === 'GET') {
                    JsonResponse::success([], 'API de pedidos em funcionamento.', 200);
                    return;
                }

                JsonResponse::methodNotAllowed();
                return;
            }

            if ($path === '/pedidos') {
                $controller = $this->container->getPedidoController();

                if ($method === 'GET') {
                    $controller->index();
                    return;
                }

                if ($method === 'POST') {
                    $controller->store();
                    return;
                }

                if ($method === 'DELETE') {
                    $controller->destroy();
                    return;
                }

                JsonResponse::methodNotAllowed('Método não permitido para /pedidos.');
                return;
            }

            JsonResponse::notFound('Rota não encontrada.');
        } catch (Throwable $exception) {
            JsonResponse::internalServerError($exception->getMessage());
        }
    }
}
