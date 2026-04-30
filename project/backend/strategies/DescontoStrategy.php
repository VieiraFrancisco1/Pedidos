<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Pedido;

/**
 * STRATEGY PATTERN
 * 
 * Interface que define o contrato para diferentes estratégias de desconto
 * 
 * Implementações:
 * - SemDescontoStrategy - sem desconto (0%)
 * - DescontoPercentualStrategy - desconto fixo (ex: 15%)
 * - DescontoProgressivoStrategy - desconto progressivo baseado no total
 * 
 * Benefícios:
 * - Fácil adicionar novos tipos de desconto
 * - Pedido não conhece a estratégia, está desacoplado
 * - Trocar comportamento em tempo de execução
 * 
 * Uso:
 *   $strategy = new DescontoPercentualStrategy(10.0);
 *   $pedido->aplicarDesconto($strategy->calcularPercentual($pedido));
 */
interface DescontoStrategy
{
    public function calcularPercentual(Pedido $pedido): float;
}
