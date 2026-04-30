<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Pedido;
use App\Observers\PedidoObserverInterface;

/**
 * OBSERVER PATTERN - DISPATCHER/EVENT AGGREGATOR
 * 
 * Responsabilidade: Gerenciar observers e notificá-los sobre eventos
 * 
 * Fluxo:
 * 1. attach() - registra um observer para ser notificado
 * 2. notify() - dispara evento para todos os observers
 * 
 * Benefícios:
 * - PedidoService não conhece quem está observando
 * - Observers não precisam estar acoplados uns aos outros
 * - Fácil adicionar/remover observers
 */
final class PedidoEventDispatcher
{
    /**
     * @var PedidoObserverInterface[]
     */
    private array $observers = [];

    public function attach(PedidoObserverInterface $observer): void
    {
        $this->observers[] = $observer;
    }

    public function notify(string $evento, Pedido $pedido): void
    {
        foreach ($this->observers as $observer) {
            $observer->update($evento, $pedido);
        }
    }
}
