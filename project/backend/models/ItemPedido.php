<?php
declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;
use JsonSerializable;

final class ItemPedido implements JsonSerializable
{
    public function __construct(
        private Produto $produto,
        private int $quantidade
    ) {
        if ($this->quantidade <= 0) {
            throw new InvalidArgumentException('A quantidade precisa ser maior que zero.');
        }
    }

    public static function fromArray(array $dados): self
    {
        if (!isset($dados['produto']) || !is_array($dados['produto'])) {
            throw new InvalidArgumentException('Cada item precisa conter os dados do produto.');
        }

        return new self(
            Produto::fromArray($dados['produto']),
            (int) ($dados['quantidade'] ?? 0)
        );
    }

    public function getProduto(): Produto
    {
        return $this->produto;
    }

    public function getQuantidade(): int
    {
        return $this->quantidade;
    }

    public function getSubtotal(): float
    {
        return round($this->produto->getPreco() * $this->quantidade, 2);
    }

    public function toArray(): array
    {
        return [
            'produto' => $this->produto->toArray(),
            'quantidade' => $this->quantidade,
            'subtotal' => $this->getSubtotal(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
