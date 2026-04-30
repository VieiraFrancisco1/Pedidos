<?php
declare(strict_types=1);

namespace App\Factories;

use App\Models\ItemPedido;
use App\Models\Pedido;
use InvalidArgumentException;

/**
 * FACTORY PATTERN
 * 
 * Responsabilidade: Encapsular a lógica complexa de criação de Pedidos
 * 
 * Benefícios:
 * - Centraliza validação de entrada
 * - Garante objetos sempre no estado válido
 * - Facilita mudanças na construção sem afetar o resto do sistema
 * 
 * Uso:
 *   $factory = new PedidoFactory();
 *   $pedido = $factory->criar(['itens' => [...]]);
 */
final class PedidoFactory
{
    public function criar(array $dados): Pedido
    {
        if (!isset($dados['itens']) || !is_array($dados['itens']) || $dados['itens'] === []) {
            throw new InvalidArgumentException('O pedido precisa ter ao menos um item.');
        }

        $pedido = new Pedido(
            isset($dados['id']) ? (int) $dados['id'] : null
        );

        foreach ($dados['itens'] as $itemDados) {
            $pedido->adicionarItem(ItemPedido::fromArray($itemDados));
        }

        return $pedido;
    }
}
