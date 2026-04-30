<?php
declare(strict_types=1);

namespace App\Strategies;

use App\Models\Pedido;

final class DescontoProgressivoStrategy implements DescontoStrategy
{
    public function calcularPercentual(Pedido $pedido): float
    {
        $total = $pedido->calcularTotalBruto();

        if ($total >= 500) {
            return 10.0;
        }

        if ($total >= 200) {
            return 5.0;
        }

        return 0.0;
    }
}
