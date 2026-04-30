<?php
declare(strict_types=1);

namespace App;

use App\Controllers\PedidoController;
use App\Observers\PedidoLogObserver;
use App\Repositories\PedidoRepository;
use App\Services\PedidoEventDispatcher;
use App\Services\PedidoService;
use App\Strategies\DescontoProgressivoStrategy;

/**
 * DEPENDENCY INJECTION CONTAINER
 * 
 * Responsabilidade: Gerenciar a criação e resolução de dependências
 * 
 * Padrão Singleton garante:
 * - Uma única instância do container
 * - Uma única instância de cada serviço
 * 
 * Benefícios:
 * - Centraliza configuração de injeção
 * - Facilita testes (pode-se criar container com mocks)
 * - Reduz acoplamento entre camadas
 * 
 * Uso:
 *   $container = Container::getInstance();
 *   $service = $container->getPedidoService();
 *   // Todas as dependências foram resolvidas automaticamente
 */
final class Container
{
    private static ?self $instance = null;
    private array $resolved = [];

    private function __construct()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function getPedidoRepository(): PedidoRepository
    {
        if (!isset($this->resolved['repository'])) {
            $this->resolved['repository'] = PedidoRepository::getInstance(__DIR__ . '/data/pedidos.json');
        }

        return $this->resolved['repository'];
    }

    public function getPedidoEventDispatcher(): PedidoEventDispatcher
    {
        if (!isset($this->resolved['dispatcher'])) {
            $dispatcher = new PedidoEventDispatcher();
            $dispatcher->attach(new PedidoLogObserver(__DIR__ . '/data/pedidos.log'));
            $this->resolved['dispatcher'] = $dispatcher;
        }

        return $this->resolved['dispatcher'];
    }

    public function getPedidoService(): PedidoService
    {
        if (!isset($this->resolved['service'])) {
            $this->resolved['service'] = new PedidoService(
                $this->getPedidoRepository(),
                $this->getPedidoEventDispatcher(),
                new DescontoProgressivoStrategy()
            );
        }

        return $this->resolved['service'];
    }

    public function getPedidoController(): PedidoController
    {
        return new PedidoController($this->getPedidoService());
    }
}
