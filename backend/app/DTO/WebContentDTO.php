<?php

declare(strict_types=1);

namespace App\DTO;

use App\Domain\Mood;

final readonly class WebContentDTO
{
    /**
     * @param array{
     *     mood: Mood,
     *     color_palette: array{primary: string, secondary: string, background: string},
     *     block_variants: array{hero: string, pricing: string, gallery: string}
     * } $design
     * @param array{
     *     vacation_banner: array{active: bool, text: string},
     *     services: list<array{id: string, title: string, description: string, price_text: string, order: int}>,
     *     gallery: list<array{id: string, image_url: string, thumbnail_url: string, caption: string}>,
     *     opening_hours: string,
     *     contact: array{phone: string, email: string, address_visible: bool}
     * } $content
     */
    public function __construct(
        public BusinessId $businessId,
        public int $version,
        public array $design,
        public array $content,
        public ?\DateTimeImmutable $updatedAt = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'business_id' => $this->businessId->value,
            'version' => $this->version,
            'design' => [
                'mood' => $this->design['mood']->value,
                'color_palette' => $this->design['color_palette'],
                'block_variants' => $this->design['block_variants'],
            ],
            'content' => $this->content,
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }
}
