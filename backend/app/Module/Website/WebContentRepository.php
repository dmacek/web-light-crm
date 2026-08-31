<?php

declare(strict_types=1);

namespace App\Module\Website;

use App\Bootstrap;
use App\DTO\BusinessId;
use App\DTO\WebContentDTO;
use App\Domain\Mood;

final class WebContentRepository
{
    private \PDO $pdo;
    private \Predis\Client $redis;

    public function __construct(?\PDO $pdo = null, ?\Predis\Client $redis = null)
    {
        $this->pdo = $pdo ?? Bootstrap::getDatabase();
        $this->redis = $redis ?? Bootstrap::getRedis();
    }

    public function findByBusinessId(BusinessId $businessId): ?WebContentDTO
    {
        $stmt = $this->pdo->prepare(<<<'SQL'
            SELECT business_id, version, design, content, updated_at
            FROM web_contents
            WHERE business_id = :business_id
        SQL);
        $stmt->execute(['business_id' => $businessId->value]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $design = is_string($row['design']) ? json_decode($row['design'], true) : $row['design'];
        $content = is_string($row['content']) ? json_decode($row['content'], true) : $row['content'];

        return new WebContentDTO(
            businessId: $businessId,
            version: (int) $row['version'],
            design: [
                'mood' => Mood::from((string) ($design['mood'] ?? 'MODERN')),
                'color_palette' => $design['color_palette'] ?? [
                    'primary' => '#2563eb',
                    'secondary' => '#1e40af',
                    'background' => '#ffffff',
                ],
                'block_variants' => $design['block_variants'] ?? [
                    'hero' => 'FULL_IMAGE_OVERLAY',
                    'pricing' => 'LIST_DOTS',
                    'gallery' => 'GRID_2X2',
                ],
            ],
            content: [
                'vacation_banner' => $content['vacation_banner'] ?? ['active' => false, 'text' => ''],
                'services' => $content['services'] ?? [],
                'gallery' => $content['gallery'] ?? [],
                'opening_hours' => $content['opening_hours'] ?? '',
                'contact' => $content['contact'] ?? ['phone' => '', 'email' => '', 'address_visible' => true],
            ],
            updatedAt: !empty($row['updated_at']) ? new \DateTimeImmutable((string) $row['updated_at']) : null,
        );
    }

    public function save(WebContentDTO $dto): void
    {
        $designJson = json_encode([
            'mood' => $dto->design['mood']->value,
            'color_palette' => $dto->design['color_palette'],
            'block_variants' => $dto->design['block_variants'],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $contentJson = json_encode($dto->content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $stmt = $this->pdo->prepare(<<<'SQL'
            INSERT INTO web_contents (business_id, version, design, content, updated_at)
            VALUES (:business_id, :version, :design::jsonb, :content::jsonb, CURRENT_TIMESTAMP)
            ON CONFLICT (business_id) DO UPDATE
            SET version = web_contents.version + 1,
                design = EXCLUDED.design,
                content = EXCLUDED.content,
                updated_at = CURRENT_TIMESTAMP
        SQL);

        $stmt->execute([
            'business_id' => $dto->businessId->value,
            'version' => $dto->version,
            'design' => $designJson,
            'content' => $contentJson,
        ]);

        // Bust Redis SSR HTML cache for this business
        try {
            $this->redis->del(["html_cache:{$dto->businessId->value}"]);
        } catch (\Throwable) {
            // Non-blocking if Redis is temporarily unreachable
        }
    }
}
