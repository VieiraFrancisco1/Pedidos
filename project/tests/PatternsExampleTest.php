<?php
declare(strict_types=1);

namespace App\Tests;

use App\Factories\PedidoFactory;
use App\Models\ItemPedido;
use App\Models\Pedido;
use App\Models\Produto;
use App\Observers\PedidoLogObserver;
use App\Repositories\PedidoRepository;
use App\Services\PedidoEventDispatcher;
use App\Services\PedidoService;
use App\Strategies\DescontoPercentualStrategy;
use App\Strategies\DescontoProgressivoStrategy;
use App\Strategies\DiscountStrategyFactory;
use App\Strategies\SemDescontoStrategy;

class PatternsExampleTest extends TestCase
{
    public function testFactoryPattern(): void
    {
        // FACTORY PATTERN: Criação centralizada de pedidos
        // O factory encapsula a lógica de construção complexa
        $factory = new PedidoFactory();

        $dadosPedido = [
            'itens' => [
                [
                    'produto' => ['id' => 1, 'nome' => 'Notebook', 'preco' => 2000.00],
                    'quantidade' => 2,
                ],
                [
                    'produto' => ['id' => 2, 'nome' => 'Mouse', 'preco' => 50.00],
                    'quantidade' => 1,
                ],
            ],
        ];

        // O factory valida a entrada e cria o objeto corretamente
        $pedido = $factory->criar($dadosPedido);

        $this->assertEquals(2, count($pedido->getItens()), 'Factory criou pedido com 2 itens');
        $this->assertFloatEquals(4050.00, $pedido->calcularTotalBruto(), 0.01, 'Factory calculou total correto');
    }

    public function testStrategyPattern(): void
    {
        // STRATEGY PATTERN: Diferentes estratégias de desconto sem modificar o pedido
        $produto = new Produto(1, 'Produto', 1000.00);
        $item = new ItemPedido($produto, 1);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);

        // Strategy 1: Sem desconto
        $semDesconto = new SemDescontoStrategy();
        $this->assertEquals(0.0, $semDesconto->calcularPercentual($pedido), 'Strategy SemDesconto retorna 0%');

        // Strategy 2: Desconto percentual fixo
        $descontoFixo = new DescontoPercentualStrategy(15.0);
        $this->assertEquals(15.0, $descontoFixo->calcularPercentual($pedido), 'Strategy DescontoPercentual retorna 15%');

        // Strategy 3: Desconto progressivo (baseado no total)
        $descontoProgressivo = new DescontoProgressivoStrategy();
        $this->assertEquals(0.0, $descontoProgressivo->calcularPercentual($pedido), 'Sem desconto para total < 200');

        // Aumentar total para testar estratégia progressiva
        $pedido->adicionarItem(new ItemPedido(new Produto(2, 'Item2', 500.00), 1));
        $this->assertEquals(5.0, $descontoProgressivo->calcularPercentual($pedido), 'Desconto progressivo 5% para total >= 200');
    }

    public function testStrategyFactoryPattern(): void
    {
        // FACTORY PATTERN aplicado a STRATEGY
        // O DiscountStrategyFactory resolve a estratégia correta baseado nos dados
        $produto = new Produto(1, 'Produto', 1000.00);
        $item = new ItemPedido($produto, 1);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);

        // Dados com desconto percentual explícito
        $dados1 = ['desconto_percentual' => 20.0, 'itens' => []];
        $strategy1 = DiscountStrategyFactory::resolve($dados1);
        $this->assertFloatEquals(20.0, $strategy1->calcularPercentual($pedido), 0.01, 'Factory resolve para DescontoPercentual');

        // Dados sem desconto
        $dados2 = ['sem_desconto' => true, 'itens' => []];
        $strategy2 = DiscountStrategyFactory::resolve($dados2);
        $this->assertEquals(0.0, $strategy2->calcularPercentual($pedido), 'Factory resolve para SemDesconto');

        // Dados com estratégia padrão (progressiva)
        $dados3 = ['itens' => []];
        $strategy3 = DiscountStrategyFactory::resolve($dados3);
        $this->assertEquals(0.0, $strategy3->calcularPercentual($pedido), 'Factory resolve para DescontoProgressivo');
    }

    public function testSingletonPattern(): void
    {
        // SINGLETON PATTERN: Uma única instância do repositório
        $repo1 = PedidoRepository::getInstance(__DIR__ . '/../../backend/data/pedidos.json');
        $repo2 = PedidoRepository::getInstance(__DIR__ . '/../../backend/data/pedidos.json');

        // Ambas as chamadas retornam a mesma instância
        $this->assertTrue($repo1 === $repo2, 'Singleton garante mesma instância');
    }

    public function testRepositoryPattern(): void
    {
        // REPOSITORY PATTERN: Abstração da persistência (JSON)
        // O repositório esconde os detalhes de como os dados são armazenados
        $repository = PedidoRepository::getInstance(__DIR__ . '/test-pedidos.json');

        // Interface do repositório é agnóstica ao armazenamento
        $produto = new Produto(1, 'Produto', 100.00);
        $item = new ItemPedido($produto, 1);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);

        // CRIAR
        $pedidoSalvo = $repository->salvar($pedido);
        $this->assertNotNull($pedidoSalvo->getId(), 'Repository atribuiu ID ao salvar');

        // LISTAR
        $pedidos = $repository->listar();
        $this->assertTrue(count($pedidos) > 0, 'Repository lista pedidos armazenados');

        // BUSCAR POR ID
        $pedidoBuscado = $repository->buscarPorId($pedidoSalvo->getId());
        $this->assertNotNull($pedidoBuscado, 'Repository buscou pedido por ID');
        $this->assertEquals($pedidoSalvo->getId(), $pedidoBuscado->getId(), 'IDs coincidem');

        // DELETAR
        $deletado = $repository->deletar($pedidoSalvo->getId());
        $this->assertTrue($deletado, 'Repository deletou pedido');
        $this->assertNull($repository->buscarPorId($pedidoSalvo->getId()), 'Pedido já não existe após delete');

        // Limpeza
        if (is_file(__DIR__ . '/test-pedidos.json')) {
            unlink(__DIR__ . '/test-pedidos.json');
        }
    }

    public function testObserverPattern(): void
    {
        // OBSERVER PATTERN: Notificação de eventos sem acoplamento
        // O dispatcher notifica observers sobre mudanças no pedido
        $dispatcher = new PedidoEventDispatcher();
        $logFile = __DIR__ . '/test-observer.log';
        $observer = new PedidoLogObserver($logFile);

        // Registrar o observer
        $dispatcher->attach($observer);

        // Criar um pedido e notificar
        $produto = new Produto(1, 'Produto', 100.00);
        $item = new ItemPedido($produto, 1);
        $pedido = new Pedido();
        $pedido->adicionarItem($item);
        $pedido->setId(1);

        // Disparar eventos
        $dispatcher->notify('criado', $pedido);
        $dispatcher->notify('removido', $pedido);

        // Verificar que o observer escreveu no arquivo
        $this->assertTrue(is_file($logFile), 'Observer criou arquivo de log');
        $conteudo = file_get_contents($logFile);
        $this->assertTrue(str_contains($conteudo, 'criado'), 'Log contém evento "criado"');
        $this->assertTrue(str_contains($conteudo, 'removido'), 'Log contém evento "removido"');

        // Limpeza
        if (is_file($logFile)) {
            unlink($logFile);
        }
    }

    public function testServiceWithAllPatterns(): void
    {
        // INTEGRAÇÃO DE TODOS OS PADRÕES no PedidoService
        $repository = PedidoRepository::getInstance(__DIR__ . '/test-service-pedidos.json');
        $dispatcher = new PedidoEventDispatcher();
        $dispatcher->attach(new PedidoLogObserver(__DIR__ . '/test-service.log'));

        // O Service usa:
        // - REPOSITORY para persistência
        // - OBSERVER/DISPATCHER para notificações
        // - FACTORY para criação de pedidos
        // - STRATEGY para cálculo de descontos
        $service = new PedidoService(
            $repository,
            $dispatcher,
            new DescontoProgressivoStrategy()
        );

        $dados = [
            'desconto_percentual' => 10.0,
            'itens' => [
                [
                    'produto' => ['id' => 1, 'nome' => 'Notebook', 'preco' => 1000.00],
                    'quantidade' => 1,
                ],
            ],
        ];

        // Criar pedido (factory + strategy + repository + observer)
        $pedido = $service->criarPedido($dados);

        $this->assertNotNull($pedido->getId(), 'Service criou pedido com ID');
        $this->assertFloatEquals(900.00, $pedido->calcularTotal(), 0.01, 'Service aplicou desconto');

        // Listar (repository)
        $pedidos = $service->listarPedidos();
        $this->assertTrue(count($pedidos) > 0, 'Service lista pedidos');

        // Remover (repository + observer)
        $removido = $service->removerPedido($pedido->getId());
        $this->assertTrue($removido, 'Service removeu pedido');

        // Limpeza
        foreach ([__DIR__ . '/test-service-pedidos.json', __DIR__ . '/test-service.log'] as $arquivo) {
            if (is_file($arquivo)) {
                unlink($arquivo);
            }
        }
    }
}
