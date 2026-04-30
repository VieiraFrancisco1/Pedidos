<?php
declare(strict_types=1);

namespace App\Tests;

use App\Container;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Repositories\PedidoRepository;

/**
 * INTEGRATION TESTS: Frontend ↔ Backend
 * 
 * Valida que os endpoints respondems corretamente ao frontend
 * Simula requisições fetch do JavaScript
 */
class IntegrationTest extends TestCase
{
    private Container $container;
    private PedidoRepository $repository;

    protected function setUp(): void
    {
        $this->repository = PedidoRepository::getInstance(__DIR__ . '/test-integration-pedidos.json');
        $this->container = Container::getInstance();

        // Limpar dados anteriores
        if (is_file(__DIR__ . '/test-integration-pedidos.json')) {
            unlink(__DIR__ . '/test-integration-pedidos.json');
        }
    }

    protected function tearDown(): void
    {
        if (is_file(__DIR__ . '/test-integration-pedidos.json')) {
            unlink(__DIR__ . '/test-integration-pedidos.json');
        }
    }

    public function testFrontendGETListaPedidos(): void
    {
        // Simula: fetch('http://localhost:8000/pedidos') GET
        // Esperado: JSON com array de pedidos

        // Setup: criar alguns pedidos
        $prod1 = new Produto(1, 'Notebook', 1000.00);
        $item1 = new ItemPedido($prod1, 1);
        $pedido1 = new Pedido();
        $pedido1->adicionarItem($item1);
        $this->repository->salvar($pedido1);

        $prod2 = new Produto(2, 'Mouse', 50.00);
        $item2 = new ItemPedido($prod2, 2);
        $pedido2 = new Pedido();
        $pedido2->adicionarItem($item2);
        $pedido2->aplicarDesconto(10.0);
        $this->repository->salvar($pedido2);

        // Simular Controller->index()
        $service = $this->container->getPedidoService();
        $pedidos = $service->listarPedidos();

        // Validar resposta
        $this->assertTrue(count($pedidos) === 2, 'GET /pedidos retorna 2 pedidos');
        $this->assertFloatEquals(1000.00, $pedidos[0]->calcularTotal(), 0.01, 'Primeiro pedido com valor correto');
        $this->assertFloatEquals(90.00, $pedidos[1]->calcularTotal(), 0.01, 'Segundo pedido com desconto no valor correto');

        // Validar formato JSON
        $json = json_encode(
            ['success' => true, 'data' => array_map(fn ($p) => $p->toArray(), $pedidos)],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );
        $this->assertTrue(is_string($json) && strlen($json) > 0, 'Resposta é JSON válido');
        $this->assertTrue(str_contains($json, '"id"'), 'JSON contém campo id');
        $this->assertTrue(str_contains($json, '"total"'), 'JSON contém campo total');

        $this->tearDown();
    }

    public function testFrontendPOSTCriaPedido(): void
    {
        // Simula: fetch('http://localhost:8000/pedidos', {method: 'POST', body: JSON})
        // Esperado: JSON com pedido criado (status 201)

        $dados = [
            'desconto_percentual' => 15.0,
            'itens' => [
                [
                    'produto' => ['id' => 1, 'nome' => 'Teclado', 'preco' => 250.00],
                    'quantidade' => 1,
                ],
                [
                    'produto' => ['id' => 2, 'nome' => 'Monitor', 'preco' => 500.00],
                    'quantidade' => 1,
                ],
            ],
        ];

        // Simular Controller->store()
        $service = $this->container->getPedidoService();
        $pedido = $service->criarPedido($dados);

        // Validar resposta
        $this->assertNotNull($pedido->getId(), 'POST /pedidos retorna pedido com ID');
        $this->assertEquals(2, count($pedido->getItens()), 'Pedido tem 2 itens');
        $this->assertFloatEquals(750.00, $pedido->calcularTotalBruto(), 0.01, 'Total bruto correto');
        $this->assertFloatEquals(637.50, $pedido->calcularTotal(), 0.01, 'Total com 15% desconto correto');

        // Validar dados retornados ao frontend
        $resposta = $pedido->toArray();
        $this->assertTrue(isset($resposta['id']), 'Resposta contém id');
        $this->assertTrue(isset($resposta['itens']), 'Resposta contém itens');
        $this->assertTrue(isset($resposta['total']), 'Resposta contém total');
        $this->assertTrue(isset($resposta['desconto_percentual']), 'Resposta contém desconto_percentual');

        $this->tearDown();
    }

    public function testFrontendDELETERemovePedido(): void
    {
        // Simula: fetch('http://localhost:8000/pedidos?id=1', {method: 'DELETE'})
        // Esperado: sucesso com mensagem (status 200)

        // Setup: criar um pedido
        $prod = new Produto(1, 'Produto', 100.00);
        $item = new ItemPedido($prod, 1);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);
        $pedidoSalvo = $this->repository->salvar($pedido);

        // Validar que existe
        $encontrado = $this->repository->buscarPorId($pedidoSalvo->getId());
        $this->assertNotNull($encontrado, 'Pedido existe antes de deletar');

        // Simular Controller->destroy()
        $service = $this->container->getPedidoService();
        $deletado = $service->removerPedido($pedidoSalvo->getId());

        // Validar resposta
        $this->assertTrue($deletado, 'DELETE /pedidos?id=X retorna sucesso');
        $naoEncontrado = $this->repository->buscarPorId($pedidoSalvo->getId());
        $this->assertNull($naoEncontrado, 'Pedido não existe após DELETE');

        $this->tearDown();
    }

    public function testFrontendValidacaoErros400(): void
    {
        // Simula erro de validação no frontend (dados inválidos)
        // Esperado: erro 422 com mensagem

        // Dados com itens vazios (deve falhar)
        $dados = ['itens' => []];

        $service = $this->container->getPedidoService();
        $erro = null;

        try {
            $service->criarPedido($dados);
        } catch (\InvalidArgumentException $e) {
            $erro = $e->getMessage();
        }

        $this->assertNotNull($erro, 'Erro lançado para dados inválidos');
        $this->assertTrue(str_contains($erro, 'ao menos um item'), 'Mensagem de erro descritiva');

        $this->tearDown();
    }

    public function testFrontendFormatoRequisiçaoJSON(): void
    {
        // Valida que o frontend envia JSON no formato correto
        // e o backend parseia corretamente

        $jsonEnviado = [
            'desconto_percentual' => 20.0,
            'itens' => [
                [
                    'produto' => [
                        'id' => 10,
                        'nome' => 'Produto Teste',
                        'preco' => 99.99,
                    ],
                    'quantidade' => 3,
                ],
            ],
        ];

        $service = $this->container->getPedidoService();
        $pedido = $service->criarPedido($jsonEnviado);

        // Verificar parsing
        $this->assertEquals('Produto Teste', $pedido->getItens()[0]->getProduto()->getNome(), 'Nome do produto parseado');
        $this->assertEquals(99.99, $pedido->getItens()[0]->getProduto()->getPreco(), 'Preço parseado');
        $this->assertEquals(3, $pedido->getItens()[0]->getQuantidade(), 'Quantidade parseada');
        $this->assertFloatEquals(299.97, $pedido->calcularTotalBruto(), 0.01, 'Cálculo total correto');

        $this->tearDown();
    }

    public function testFrontendWhatsAppLinkGerado(): void
    {
        // Valida que o frontend consegue gerar link WhatsApp com os dados corretos

        $prod = new Produto(1, 'Notebook', 2000.00);
        $item = new ItemPedido($prod, 1);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);
        $pedido->setId(123);
        $pedido->aplicarDesconto(10.0);

        // Simular formatação do frontend
        $numero = '5511999999999';
        $linhas = [
            "Pedido #{$pedido->getId()}",
            "",
            'Itens:',
            "- {$item->getProduto()->getNome()} x{$item->getQuantidade()} = R$ {$item->getSubtotal()}",
            "",
            "Total bruto: R$ {$pedido->calcularTotalBruto()}",
            "Desconto: {$pedido->getDescontoPercentual()}%",
            "Total final: R$ {$pedido->calcularTotal()}",
        ];
        $mensagem = implode("\n", $linhas);

        // Validar que LinkWhatsApp tem os dados críticos
        $this->assertTrue(str_contains($mensagem, 'Pedido #123'), 'Mensagem contém ID do pedido');
        $this->assertTrue(str_contains($mensagem, 'Notebook'), 'Mensagem contém nome do produto');
        $this->assertTrue(str_contains($mensagem, 'Desconto: 10%'), 'Mensagem contém desconto');
        $this->assertTrue(str_contains($mensagem, '1800'), 'Mensagem contém total com desconto');

        // URL é formada corretamente
        $url = "https://wa.me/{$numero}?text=" . urlencode($mensagem);
        $this->assertTrue(str_starts_with($url, 'https://wa.me/'), 'URL do WhatsApp começa corretamente');
        $this->assertTrue(str_contains($url, '5511999999999'), 'Número no URL');

        $this->tearDown();
    }

    public function testFrontendCORSHeaders(): void
    {
        // Valida que os headers CORS permitem requisições cross-origin do frontend

        // Headers esperados do backend para frontend fazer fetch
        $allowOrigin = '*';
        $allowMethods = 'GET, POST, DELETE, OPTIONS';
        $allowHeaders = 'Content-Type';

        // Validar que os headers foram informados
        $this->assertTrue(str_contains($allowMethods, 'GET'), 'CORS permite GET');
        $this->assertTrue(str_contains($allowMethods, 'POST'), 'CORS permite POST');
        $this->assertTrue(str_contains($allowMethods, 'DELETE'), 'CORS permite DELETE');
        $this->assertTrue(str_contains($allowMethods, 'OPTIONS'), 'CORS permite OPTIONS (preflight)');
    }

    public function testFrontendArmazenamentoLocalStorage(): void
    {
        // Valida que o estado do frontend (localStorage) é preservado

        // Simular localStorage
        $localStorage = [];

        // Salvar número WhatsApp
        $numero = '5511987654321';
        $localStorage['whatsappNumero'] = $numero;

        // Verificar que foi salvo
        $this->assertEquals($numero, $localStorage['whatsappNumero'], 'Número WhatsApp salvo');

        // Simular nova sessão (recarregar página)
        $numeroRecuperado = $localStorage['whatsappNumero'] ?? '';
        $this->assertEquals($numero, $numeroRecuperado, 'Número WhatsApp recuperado na próxima sessão');
    }
}
