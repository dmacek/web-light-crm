<?php

declare(strict_types=1);

namespace Renderer\Presenters;

use Latte\Engine;
use Renderer\Bootstrap;
use Renderer\Design\ChameleonEngine;

final class PublicSitePresenter
{
    private \PDO $pdo;
    private \Predis\Client $redis;
    private Engine $latte;

    public function __construct(?\PDO $pdo = null, ?\Predis\Client $redis = null)
    {
        $this->pdo = $pdo ?? Bootstrap::getDatabase();
        $this->redis = $redis ?? Bootstrap::getRedis();

        $this->latte = new Engine();
        $tempDir = __DIR__ . '/../../var/cache';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        $this->latte->setTempDirectory($tempDir);
    }

    public function render(): void
    {
        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $host = strtolower(trim($host));
        $host = (string) preg_replace('/:\d+$/', '', $host);

        // 1. Resolve business from DB
        $business = $this->resolveBusiness($host);

        if ($business === null) {
            $this->render404($host);
            return;
        }

        $businessId = (string) $business['id'];
        $cacheKey = "html_cache:{$businessId}";

        // 2. Check Redis HTML cache (TTL 60s)
        try {
            $cachedHtml = $this->redis->get($cacheKey);
            if ($cachedHtml !== null && is_string($cachedHtml)) {
                header('Content-Type: text/html; charset=utf-8');
                header('X-Cache: HIT');
                echo $cachedHtml;
                return;
            }
        } catch (\Throwable) {
        }

        // 3. Load WebContent
        $webContent = $this->loadWebContent($businessId);
        $design = $webContent['design'] ?? [];
        $content = $webContent['content'] ?? [];

        $mood = (string) ($design['mood'] ?? 'MODERN');
        $colorPalette = $design['color_palette'] ?? [];

        $tokens = ChameleonEngine::resolveTokens($mood, $colorPalette);

        // 4. Render with Latte
        $params = [
            'business' => $business,
            'webContent' => $webContent,
            'design' => $design,
            'content' => $content,
            'tokens' => $tokens,
        ];

        $templatePath = __DIR__ . '/../Template/layout.latte';
        $html = $this->latte->renderToString($templatePath, $params);

        // 5. Save in Redis HTML cache
        try {
            $this->redis->setex($cacheKey, 60, $html);
        } catch (\Throwable) {
        }

        header('Content-Type: text/html; charset=utf-8');
        header('X-Cache: MISS');
        echo $html;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveBusiness(string $host): ?array
    {
        $baseDomain = getenv('APP_DOMAIN') ?: 'tvojeaplikace.cz';

        // Check if subdomain of base domain
        if (str_ends_with($host, '.' . $baseDomain)) {
            $subdomain = substr($host, 0, -strlen('.' . $baseDomain));
            $stmt = $this->pdo->prepare('SELECT * FROM businesses WHERE subdomain = :subdomain');
            $stmt->execute(['subdomain' => $subdomain]);
            $row = $stmt->fetch();
            return $row ?: null;
        }

        // Check custom domain
        $stmt = $this->pdo->prepare("SELECT * FROM businesses WHERE custom_domain = :domain AND custom_domain_status = 'ACTIVE'");
        $stmt->execute(['domain' => $host]);
        $row = $stmt->fetch();

        if ($row) {
            return $row;
        }

        // Fallback for local testing (matches first business or specific subdomain)
        $stmt = $this->pdo->query('SELECT * FROM businesses ORDER BY created_at DESC LIMIT 1');
        $row = $stmt ? $stmt->fetch() : null;
        return $row ?: null;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadWebContent(string $businessId): array
    {
        $stmt = $this->pdo->prepare('SELECT design, content, version FROM web_contents WHERE business_id = :id');
        $stmt->execute(['id' => $businessId]);
        $row = $stmt->fetch();

        if (!$row) {
            return [
                'design' => [
                    'mood' => 'MODERN',
                    'color_palette' => ['primary' => '#2563eb', 'secondary' => '#1e40af', 'background' => '#ffffff'],
                    'block_variants' => ['hero' => 'FULL_IMAGE_OVERLAY', 'pricing' => 'LIST_DOTS', 'gallery' => 'GRID_2X2'],
                ],
                'content' => [
                    'vacation_banner' => ['active' => false, 'text' => ''],
                    'services' => [],
                    'gallery' => [],
                    'opening_hours' => 'Po-Pá: 8:00 - 17:00',
                    'contact' => ['phone' => '', 'email' => '', 'address_visible' => true],
                ],
            ];
        }

        return [
            'design' => is_string($row['design']) ? json_decode($row['design'], true) : $row['design'],
            'content' => is_string($row['content']) ? json_decode($row['content'], true) : $row['content'],
            'version' => (int) ($row['version'] ?? 1),
        ];
    }

    private function render404(string $host): void
    {
        http_response_code(404);
        header('Content-Type: text/html; charset=utf-8');
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="cs">
        <head>
            <meta charset="UTF-8">
            <title>Web nebyl nalezen</title>
            <style>
                body { font-family: system-ui, sans-serif; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #f8fafc; color: #1e293b; text-align: center; }
                .box { background: white; padding: 3rem; border-radius: 1rem; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 450px; }
                h1 { font-size: 1.75rem; margin-bottom: 0.5rem; }
                p { color: #64748b; font-size: 0.95rem; margin-bottom: 1.5rem; }
                a { display: inline-block; background: #2563eb; color: white; padding: 0.75rem 1.5rem; border-radius: 0.5rem; text-decoration: none; font-weight: 600; }
            </style>
        </head>
        <body>
            <div class="box">
                <h1>Web nebyl nalezen</h1>
                <p>Pro doménu <strong>{$host}</strong> nebyl nalezen žádný aktivní web živnostníka.</p>
                <a href="https://app.tvojeaplikace.cz">Vytvořit si vlastní web zdarma</a>
            </div>
        </body>
        </html>
        HTML;
    }
}
