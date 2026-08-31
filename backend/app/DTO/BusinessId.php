<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class BusinessId
{
    public function __construct(
        public string $value,
    ) {
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            throw new \InvalidArgumentException("Invalid UUID format for BusinessId: {$value}");
        }
    }

    public static function generate(): self
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40); // set version to 0100
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80); // set bits 6-7 to 10
        $uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        return new self($uuid);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
