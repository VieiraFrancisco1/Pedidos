<?php
declare(strict_types=1);

namespace App\Observers;

use App\Models\Pedido;

final class PedidoLogObserver implements PedidoObserverInterface
{
    public function __construct(private string $arquivoLog)
    {
    }

    public function update(string $evento, Pedido $pedido): void
    {
        $diretorio = dirname($this->arquivoLog);

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0777, true);
        }

        $linha = sprintf(
            "[%s] Evento: %s | Pedido: %s | Total: %.2f\n",
            date('c'),
            $evento,
            (string) $pedido->getId(),
            $pedido->calcularTotal()
        );

        file_put_contents($this->arquivoLog, $linha, FILE_APPEND | LOCK_EX);
    }
}
