<?php

declare(strict_types=1);

namespace App\Module\Onboarding;

use App\Bootstrap;

final class OnboardingDraftRepository
{
    private \PDO $pdo;
    private \Predis\Client $redis;

    public function __construct(?\PDO $pdo = null, ?\Predis\Client $redis = null)
    {
        $this->pdo = $pdo ?? Bootstrap::getDatabase();
        $this->redis = $redis ?? Bootstrap::getRedis();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $sessionDraftId): ?array
    {
        // Try Redis first
        try {
            $cached = $this->redis->get("draft:{$sessionDraftId}");
            if ($cached !== null) {
                return json_decode($cached, true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (\Throwable) {
            // Fallback to PostgreSQL
        }

        $stmt = $this->pdo->prepare(<<<'SQL'
            SELECT data FROM onboarding_drafts
            WHERE session_draft_id = :id AND expires_at > CURRENT_TIMESTAMP
        SQL);
        $stmt->execute(['id' => $sessionDraftId]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return is_string($row['data']) ? json_decode($row['data'], true) : $row['data'];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(string $sessionDraftId, array $data, int $ttlSeconds = 604800): void
    {
        $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        // Save to Redis
        try {
            $this->redis->setex("draft:{$sessionDraftId}", $ttlSeconds, $json);
        } catch (\Throwable) {
            // Non-blocking
        }

        // Save to PostgreSQL
        $expiresAt = (new \DateTimeImmutable("+{$ttlSeconds} seconds"))->format(\DateTimeInterface::ATOM);
        $stmt = $this->pdo->prepare(<<<'SQL'
            INSERT INTO onboarding_drafts (session_draft_id, step, data, expires_at)
            VALUES (:id, :step, :data::jsonb, :expires_at)
            ON CONFLICT (session_draft_id) DO UPDATE
            SET data = EXCLUDED.data,
                step = EXCLUDED.step,
                expires_at = EXCLUDED.expires_at
        SQL);

        $stmt->execute([
            'id' => $sessionDraftId,
            'step' => $data['step'] ?? 1,
            'data' => $json,
            'expires_at' => $expiresAt,
        ]);
    }

    public function delete(string $sessionDraftId): void
    {
        try {
            $this->redis->del(["draft:{$sessionDraftId}"]);
        } catch (\Throwable) {
        }

        $stmt = $this->pdo->prepare(<<<'SQL'
            DELETE FROM onboarding_drafts WHERE session_draft_id = :id
        SQL);
        $stmt->execute(['id' => $sessionDraftId]);
    }
}
