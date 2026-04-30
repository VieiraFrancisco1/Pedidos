<?php
declare(strict_types=1);

namespace App\Models;

use InvalidArgumentException;
use JsonSerializable;

final class Produto implements JsonSerializable
{
    public function __construct(
        private ?int $id,
        private string $nome,
        private float $preco
    ) {
        if (trim($this->nome) === '') {
            throw new InvalidArgumentException('O nome do produto nao pode ficar vazio.');
        }

        if ($this->preco < 0) {
            throw new InvalidArgumentException('O preco do produto nao pode ser negativo.');
        }
    }

    public static function fromArray(array $dados): self
    {
        return new self(
            isset($dados['id']) ? (int) $dados['id'] : null,
            (string) ($dados['nome'] ?? ''),
            (float) ($dados['preco'] ?? 0)
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

    public function getNome(): string
    {
        return $this->nome;
    }

    public function getPreco(): float
    {
        return $this->preco;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'preco' => $this->preco,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
