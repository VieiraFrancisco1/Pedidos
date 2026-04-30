<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Pedido;

final class DescontoPercentualStrategy implements DescontoStrategy
{
    public function __construct(private float $percentual)
    {
    }

    public function calcularPercentual(Pedido $pedido): float
    {
        return max(0.0, min(100.0, $this->percentual));
    }
}
