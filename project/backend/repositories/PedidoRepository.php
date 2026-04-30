<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Models\Pedido;

/**
 * REPOSITORY PATTERN + SINGLETON PATTERN
 * 
 * Responsabilidade: Abstrair a persistência de Pedidos (JSON, banco de dados, etc)
 * 
 * Padrão Singleton garante:
 * - Uma única instância em toda a aplicação
 * - Evita múltiplas conexões ou leituras redundantes
 * 
 * Benefícios do Repository:
 * - Trocar JSON por MySQL em um único lugar
 * - Facilita testes com mock repository
 * - Lógica de persistência isolada
 * 
 * Uso:
 *   $repo = PedidoRepository::getInstance(__DIR__ . '/data/pedidos.json');
 *   $repo->salvar($pedido);
 *   $repo->listar();
 */
final class PedidoRepository
{
    private static ?self $instance = null;

    private function __construct(private string $arquivoDados)
    {
        $diretorio = dirname($this->arquivoDados);

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0777, true);
        }

        if (!is_file($this->arquivoDados)) {
            file_put_contents($this->arquivoDados, "[]\n");
        }
    }

    public static function getInstance(?string $arquivoDados = null): self
    {
        if (self::$instance === null) {
            $arquivo = $arquivoDados ?? __DIR__ . '/../data/pedidos.json';
            self::$instance = new self($arquivo);
        }

        return self::$instance;
    }

    /**
     * @return Pedido[]
     */
    public function listar(): array
    {
        $dados = $this->lerDados();
        $pedidos = array_map(static fn (array $pedido): Pedido => Pedido::fromArray($pedido), $dados);

        usort($pedidos, static fn (Pedido $a, Pedido $b): int => ($a->getId() ?? 0) <=> ($b->getId() ?? 0));

        return $pedidos;
    }

    public function buscarPorId(int $id): ?Pedido
    {
        foreach ($this->listar() as $pedido) {
            if ($pedido->getId() === $id) {
                return $pedido;
            }
        }

        return null;
    }

    public function salvar(Pedido $pedido): Pedido
    {
        $dados = $this->lerDados();
        $pedido->setId($pedido->getId() ?? $this->proximoId($dados));

        $substituido = false;

        foreach ($dados as $indice => $item) {
            if ((int) ($item['id'] ?? 0) === $pedido->getId()) {
                $dados[$indice] = $pedido->toArray();
                $substituido = true;
                break;
            }
        }

        if (!$substituido) {
            $dados[] = $pedido->toArray();
        }

        $this->gravarDados($dados);

        return $pedido;
    }

    public function deletar(int $id): bool
    {
        $dados = $this->lerDados();
        $novaLista = array_values(array_filter(
            $dados,
            static fn (array $item): bool => (int) ($item['id'] ?? 0) !== $id
        ));

        if (count($novaLista) === count($dados)) {
            return false;
        }

        $this->gravarDados($novaLista);

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function lerDados(): array
    {
        $conteudo = file_get_contents($this->arquivoDados);
        $dados = json_decode($conteudo === false ? '[]' : $conteudo, true);

        return is_array($dados) ? $dados : [];
    }

    /**
     * @param array<int, array<string, mixed>> $dados
     */
    private function gravarDados(array $dados): void
    {
        file_put_contents(
            $this->arquivoDados,
            json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL,
            LOCK_EX
        );
    }

    /**
     * @param array<int, array<string, mixed>> $dados
     */
    private function proximoId(array $dados): int
    {
        $maiorId = 0;

        foreach ($dados as $pedido) {
            $maiorId = max($maiorId, (int) ($pedido['id'] ?? 0));
        }

        return $maiorId + 1;
    }
}
