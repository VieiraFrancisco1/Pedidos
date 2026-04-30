<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\Pedido;

/**
 * OBSERVER PATTERN
 * 
 * Interface que define observers que reagem a eventos de Pedidos
 * 
 * Implementações:
 * - PedidoLogObserver - escreve eventos em arquivo de log
 * - (possível) PedidoEmailObserver - envia email
 * - (possível) PedidoWebhookObserver - chama webhook
 * 
 * Benefícios:
 * - Service não conhece os observers
 * - Adicionar notificações sem modificar o Service
 * - Múltiplos observers independentes
 * 
 * Fluxo:
 * 1. PedidoEventDispatcher gerencia observers
 * 2. dispatcher->attach(new PedidoLogObserver(...))
 * 3. dispatcher->notify('criado', $pedido)
 * 4. Cada observer é notificado
 */
interface PedidoObserverInterface
{
    public function update(string $evento, Pedido $pedido): void;
}
