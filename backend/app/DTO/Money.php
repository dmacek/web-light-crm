<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class Money
{
    public function __construct(
        public int $amount, // in cents (e.g. 25000 = 250 CZK)
        public string $currency = 'CZK',
    ) {
        if ($this->amount < 0) {
            throw new \InvalidArgumentException('Money amount cannot be negative');
        }
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException("Currency mismatch: {$this->currency} vs {$other->currency}");
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function format(): string
    {
        $units = $this->amount / 100;
        return number_format($units, 2, ',', ' ') . ' ' . $this->currency;
    }

    /**
     * @return array{amount: int, currency: string}
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
        ];
    }
}
