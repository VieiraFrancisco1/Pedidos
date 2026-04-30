<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Pedido;

/**
 * FACTORY PATTERN - Strategy Resolution
 * 
 * Responsabilidade: Decidir qual estratégia de desconto usar baseado nos dados
 * 
 * Lógica:
 * 1. Se desconto_percentual for informado -> DescontoPercentualStrategy
 * 2. Se sem_desconto for true -> SemDescontoStrategy
 * 3. Senão -> DescontoProgressivoStrategy (padrão)
 * 
 * Benefícios:
 * - Centraliza a lógica de resolução de estratégias
 * - PedidoService não precisa conhecer todas as estratégias
 * - Fácil adicionar novas regras de resolução
 * 
 * Uso:
 *   $strategy = DiscountStrategyFactory::resolve($dados);
 *   $pedido->aplicarDesconto($strategy->calcularPercentual($pedido));
 */
final class DiscountStrategyFactory
{
    public static function resolve(array $dados): DescontoStrategy
    {
        if (isset($dados['desconto_percentual']) && is_numeric($dados['desconto_percentual'])) {
            return new DescontoPercentualStrategy((float) $dados['desconto_percentual']);
        }

        if (isset($dados['sem_desconto']) && (bool) $dados['sem_desconto'] === true) {
            return new SemDescontoStrategy();
        }

        return new DescontoProgressivoStrategy();
    }
}
