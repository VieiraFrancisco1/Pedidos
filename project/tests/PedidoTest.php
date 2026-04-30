<?php
declare(strict_types=1);

namespace App\Tests;

use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;

class PedidoTest extends TestCase
{
    public function testCalcularTotalComUmItem(): void
    {
        $produto = new Produto(1, 'Notebook', 1000.00);
        $item = new ItemPedido($produto, 2);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);

        $this->assertFloatEquals(2000.00, $pedido->calcularTotal(), 0.01, 'Total com 1 item x2');
    }

    public function testCalcularSubtotalDoItem(): void
    {
        $produto = new Produto(1, 'Mouse', 50.00);
        $item = new ItemPedido($produto, 3);

        $this->assertFloatEquals(150.00, $item->getSubtotal(), 0.01, 'Subtotal do item');
    }

    public function testCalcularTotalComMultiplosItens(): void
    {
        $p1 = new Produto(1, 'Teclado', 250.00);
        $i1 = new ItemPedido($p1, 1);

        $p2 = new Produto(2, 'Mouse', 75.00);
        $i2 = new ItemPedido($p2, 2);

        $p3 = new Produto(3, 'Monitor', 500.00);
        $i3 = new ItemPedido($p3, 1);

        $pedido = new Pedido();
        $pedido->adicionarItem($i1);
        $pedido->adicionarItem($i2);
        $pedido->adicionarItem($i3);

        $totalEsperado = 250.00 + 150.00 + 500.00; // 900.00
        $this->assertFloatEquals($totalEsperado, $pedido->calcularTotal(), 0.01, 'Total com múltiplos itens');
    }

    public function testAplicarDescontoPercentual(): void
    {
        $produto = new Produto(1, 'Produto', 1000.00);
        $item = new ItemPedido($produto, 1);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);

        $pedido->aplicarDesconto(10.0);

        $this->assertFloatEquals(900.00, $pedido->calcularTotal(), 0.01, 'Total com 10% desconto');
    }

    public function testAplicarDesconto20Porcento(): void
    {
        $produto = new Produto(1, 'Produto', 500.00);
        $item = new ItemPedido($produto, 2);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);

        $pedido->aplicarDesconto(20.0);

        $totalBruto = 1000.00;
        $desconto = 200.00;
        $totalEsperado = 800.00;

        $this->assertFloatEquals($totalBruto, $pedido->calcularTotalBruto(), 0.01, 'Total bruto');
        $this->assertFloatEquals($totalEsperado, $pedido->calcularTotal(), 0.01, 'Total com 20% desconto');
    }

    public function testDescontoNaoNegativo(): void
    {
        $produto = new Produto(1, 'Produto', 100.00);
        $item = new ItemPedido($produto, 1);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);

        $pedido->aplicarDesconto(-5.0);

        $this->assertFloatEquals(100.00, $pedido->calcularTotal(), 0.01, 'Desconto negativo vira 0%');
    }

    public function testDescontoNaoExcede100Porcento(): void
    {
        $produto = new Produto(1, 'Produto', 100.00);
        $item = new ItemPedido($produto, 1);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);

        $pedido->aplicarDesconto(150.0);

        $this->assertFloatEquals(0.00, $pedido->calcularTotal(), 0.01, 'Desconto acima de 100% capping em 100%');
    }

    public function testCalcularTotalBrutoSemDesconto(): void
    {
        $p1 = new Produto(1, 'Item1', 100.00);
        $i1 = new ItemPedido($p1, 2);

        $p2 = new Produto(2, 'Item2', 50.00);
        $i2 = new ItemPedido($p2, 4);

        $pedido = new Pedido();
        $pedido->adicionarItem($i1);
        $pedido->adicionarItem($i2);

        $totalEsperado = 200.00 + 200.00; // 400.00
        $this->assertFloatEquals($totalEsperado, $pedido->calcularTotalBruto(), 0.01, 'Total bruto sem desconto');
        $this->assertFloatEquals($totalEsperado, $pedido->calcularTotal(), 0.01, 'Total = total bruto sem desconto');
    }

    public function testPedidoComDescontoPercentualDados(): void
    {
        $dadosPedido = [
            'id' => 1,
            'desconto_percentual' => 15.0,
            'itens' => [
                [
                    'produto' => ['id' => 1, 'nome' => 'Headphone', 'preco' => 200.00],
                    'quantidade' => 1,
                ],
            ],
        ];

        $pedido = Pedido::fromArray($dadosPedido);

        $this->assertFloatEquals(200.00, $pedido->calcularTotalBruto(), 0.01, 'Total bruto');
        $this->assertFloatEquals(170.00, $pedido->calcularTotal(), 0.01, 'Total com 15% desconto');
        $this->assertEquals(15.0, $pedido->getDescontoPercentual(), 'Desconto percentual armazenado');
    }

    public function testPedidoComZeroDesconto(): void
    {
        $produto = new Produto(1, 'Produto', 500.00);
        $item = new ItemPedido($produto, 1);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);

        $pedido->aplicarDesconto(0.0);

        $this->assertFloatEquals(500.00, $pedido->calcularTotal(), 0.01, 'Total sem desconto');
        $this->assertEquals(0.0, $pedido->getDescontoPercentual(), 'Desconto zerado');
    }

    public function testCalculoComValoresEmCentavos(): void
    {
        $produto = new Produto(1, 'Produto', 19.99);
        $item = new ItemPedido($produto, 3);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);

        $totalEsperado = 59.97;
        $this->assertFloatEquals($totalEsperado, $pedido->calcularTotal(), 0.01, 'Total com centavos');
    }

    public function testDescontoEmPedidoComMultiplosItensEmCentavos(): void
    {
        $p1 = new Produto(1, 'Item1', 10.50);
        $i1 = new ItemPedido($p1, 2);

        $p2 = new Produto(2, 'Item2', 25.75);
        $i2 = new ItemPedido($p2, 1);

        $pedido = new Pedido();
        $pedido->adicionarItem($i1);
        $pedido->adicionarItem($i2);

        $pedido->aplicarDesconto(5.0);

        $totalBruto = 21.00 + 25.75; // 46.75
        $desconto = 46.75 * 0.05;     // 2.3375
        $total = 46.75 - $desconto;   // 44.4125

        $this->assertFloatEquals($totalBruto, $pedido->calcularTotalBruto(), 0.01, 'Total bruto');
        $this->assertFloatEquals($total, $pedido->calcularTotal(), 0.01, 'Total com 5% desconto');
    }
}
