<?php

declare(strict_types=1);

namespace App\Module\Website;

use App\DTO\BusinessId;
use App\DTO\WebContentDTO;
use App\Domain\Mood;

final class WebsiteFacade
{
    private WebContentRepository $repository;

    public function __construct(?WebContentRepository $repository = null)
    {
        $this->repository = $repository ?? new WebContentRepository();
    }

    public function getConfig(BusinessId $businessId): WebContentDTO
    {
        $content = $this->repository->findByBusinessId($businessId);
        if ($content === null) {
            // Default placeholder if none exists yet
            return new WebContentDTO(
                businessId: $businessId,
                version: 1,
                design: [
                    'mood' => Mood::MODERN,
                    'color_palette' => ['primary' => '#2563eb', 'secondary' => '#1e40af', 'background' => '#ffffff'],
                    'block_variants' => ['hero' => 'FULL_IMAGE_OVERLAY', 'pricing' => 'LIST_DOTS', 'gallery' => 'GRID_2X2'],
                ],
                content: [
                    'vacation_banner' => ['active' => false, 'text' => ''],
                    'services' => [],
                    'gallery' => [],
                    'opening_hours' => 'Po-Pá: 8:00 - 17:00',
                    'contact' => ['phone' => '', 'email' => '', 'address_visible' => true],
                ],
            );
        }

        return $content;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function updateConfig(BusinessId $businessId, array $payload): WebContentDTO
    {
        $current = $this->getConfig($businessId);

        $designRaw = $payload['design'] ?? [];
        $contentRaw = $payload['content'] ?? [];

        // Validation per PRD §4 Modul 4
        $services = $contentRaw['services'] ?? $current->content['services'];
        if (count($services) > 30) {
            throw new \InvalidArgumentException('Maximální počet položek ceníku/služeb je 30');
        }

        $gallery = $contentRaw['gallery'] ?? $current->content['gallery'];
        if (count($gallery) > 20) {
            throw new \InvalidArgumentException('Maximální počet fotografií v galerii je 20');
        }

        $moodStr = (string) ($designRaw['mood'] ?? $current->design['mood']->value);
        $mood = Mood::tryFrom(strtoupper($moodStr)) ?? Mood::MODERN;

        $newDto = new WebContentDTO(
            businessId: $businessId,
            version: $current->version + 1,
            design: [
                'mood' => $mood,
                'color_palette' => $designRaw['color_palette'] ?? $current->design['color_palette'],
                'block_variants' => $designRaw['block_variants'] ?? $current->design['block_variants'],
            ],
            content: [
                'vacation_banner' => $contentRaw['vacation_banner'] ?? $current->content['vacation_banner'],
                'services' => $services,
                'gallery' => $gallery,
                'opening_hours' => (string) ($contentRaw['opening_hours'] ?? $current->content['opening_hours']),
                'contact' => $contentRaw['contact'] ?? $current->content['contact'],
            ],
        );

        $this->repository->save($newDto);

        return $newDto;
    }
}
