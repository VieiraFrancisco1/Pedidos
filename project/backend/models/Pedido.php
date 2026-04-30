<?php
declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;
use JsonSerializable;

final class Pedido implements JsonSerializable
{
    /**
     * @param ItemPedido[] $itens
     */
    public function __construct(
        private ?int $id = null,
        private array $itens = [],
        private float $descontoPercentual = 0.0
    ) {
        $this->validarItens($this->itens);
    }

    public static function fromArray(array $dados): self
    {
        $itens = [];

        foreach (($dados['itens'] ?? []) as $item) {
            $itens[] = ItemPedido::fromArray($item);
        }

        return new self(
            isset($dados['id']) ? (int) $dados['id'] : null,
            $itens,
            (float) ($dados['desconto_percentual'] ?? 0)
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return ItemPedido[]
     */
    public function getItens(): array
    {
        return $this->itens;
    }

    /**
     * @param ItemPedido[] $itens
     */
    public function setItens(array $itens): void
    {
        $this->validarItens($itens);
        $this->itens = array_values($itens);
    }

    public function adicionarItem(ItemPedido $item): void
    {
        $this->itens[] = $item;
    }

    public function getDescontoPercentual(): float
    {
        return $this->descontoPercentual;
    }

    public function aplicarDesconto(float $percentual): void
    {
        $this->descontoPercentual = max(0.0, min(100.0, $percentual));
    }

    public function calcularTotalBruto(): float
    {
        $total = 0.0;

        foreach ($this->itens as $item) {
            $total += $item->getSubtotal();
        }

        return round($total, 2);
    }

    public function calcularTotal(): float
    {
        $totalBruto = $this->calcularTotalBruto();
        $desconto = $totalBruto * ($this->descontoPercentual / 100);

        return round(max(0, $totalBruto - $desconto), 2);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'itens' => array_map(static fn (ItemPedido $item): array => $item->toArray(), $this->itens),
            'desconto_percentual' => $this->descontoPercentual,
            'total_bruto' => $this->calcularTotalBruto(),
            'total' => $this->calcularTotal(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param ItemPedido[] $itens
     */
    private function validarItens(array $itens): void
    {
        foreach ($itens as $item) {
            if (!$item instanceof ItemPedido) {
                throw new InvalidArgumentException('A lista de itens precisa conter apenas ItemPedido.');
            }
        }
    }
}
