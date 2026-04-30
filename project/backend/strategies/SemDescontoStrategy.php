<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Pedido;

final class SemDescontoStrategy implements DescontoStrategy
{
    public function calcularPercentual(Pedido $pedido): float
    {
        return 0.0;
    }
}
