<?php
declare(strict_types=1);

namespace App\Services;

use App\Factories\PedidoFactory;
use App\Models\Pedido;
use App\Repositories\PedidoRepository;
use App\Strategies\DescontoProgressivoStrategy;
use App\Strategies\DescontoStrategy;
use App\Strategies\DiscountStrategyFactory;
use InvalidArgumentException;

final class PedidoService
{
    public function __construct(
        private PedidoRepository $repository,
        private PedidoEventDispatcher $dispatcher,
        private DescontoStrategy $defaultStrategy = new DescontoProgressivoStrategy()
    ) {
    }

    /**
     * @return Pedido[]
     */
    public function listarPedidos(): array
    {
        return $this->repository->listar();
    }

    public function criarPedido(array $dados): Pedido
    {
        $factory = new PedidoFactory();
        $pedido = $factory->criar($dados);
        $strategy = DiscountStrategyFactory::resolve($dados);

        $this->aplicarDesconto($pedido, $strategy);
        $pedido = $this->repository->salvar($pedido);
        $this->dispatcher->notify('criado', $pedido);

        return $pedido;
    }

    public function removerPedido(int $id): bool
    {
        $pedido = $this->repository->buscarPorId($id);

        if ($pedido === null) {
            return false;
        }

        $removido = $this->repository->deletar($id);

        if ($removido) {
            $this->dispatcher->notify('removido', $pedido);
        }

        return $removido;
    }

    public function calcularTotal(Pedido $pedido): float
    {
        return $pedido->calcularTotal();
    }

    public function aplicarDesconto(Pedido $pedido, ?DescontoStrategy $strategy = null): Pedido
    {
        $strategy ??= $this->defaultStrategy;
        $pedido->aplicarDesconto($strategy->calcularPercentual($pedido));

        return $pedido;
    }
}
