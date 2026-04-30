<?php
declare(strict_types=1);

namespace App\Controllers;

use App\JsonRequest;
use App\JsonResponse;
use App\Services\PedidoService;
use InvalidArgumentException;
use Throwable;

final class PedidoController
{
    public function __construct(private PedidoService $service)
    {
    }

    public function index(): void
    {
        $pedidos = array_map(
            static fn ($pedido): array => $pedido->toArray(),
            $this->service->listarPedidos()
        );

        JsonResponse::success($pedidos);
    }

    public function store(): void
    {
        try {
            $request = new JsonRequest();
            $pedido = $this->service->criarPedido($request->all());
            JsonResponse::created($pedido->toArray(), 'Pedido criado com sucesso.');
        } catch (InvalidArgumentException $exception) {
            JsonResponse::unprocessableEntity($exception->getMessage());
        } catch (Throwable $exception) {
            JsonResponse::internalServerError('Erro interno ao criar pedido.');
        }
    }

    public function destroy(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id) {
            try {
                $request = new JsonRequest();
                $id = $request->has('id') ? (int) $request->get('id') : null;
            } catch (Throwable) {
                // Continua sem ID do corpo
            }
        }

        if (!$id) {
            JsonResponse::unprocessableEntity('Informe o id do pedido para exclusão.');
            return;
        }

        if (!$this->service->removerPedido($id)) {
            JsonResponse::notFound('Pedido não encontrado.');
            return;
        }

        JsonResponse::success([], 'Pedido removido com sucesso.');
    }
}
