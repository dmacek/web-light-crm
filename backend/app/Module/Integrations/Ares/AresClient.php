<?php

declare(strict_types=1);

namespace App\Module\Integrations\Ares;

use App\Bootstrap;

final class AresClient
{
    private const string ARES_API_URL = 'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/';
    private const int CACHE_TTL_SECONDS = 86400; // 24 hours
    private \Predis\Client $redis;

    public function __construct(?\Predis\Client $redis = null)
    {
        $this->redis = $redis ?? Bootstrap::getRedis();
    }

    /**
     * @return array{
     *     ico: string,
     *     company_name: string,
     *     street: string,
     *     city: string,
     *     zip: string,
     *     formatted_address: string
     * }|null
     */
    public function lookup(string $ico): ?array
    {
        $ico = trim($ico);
        if (!preg_match('/^[0-9]{8}$/', $ico)) {
            throw new \InvalidArgumentException('IČO must be exactly 8 digits');
        }

        // Check Redis cache first (Acceptance Criteria §2: response time < 1.5s)
        try {
            $cached = $this->redis->get("ares:{$ico}");
            if ($cached !== null) {
                return json_decode($cached, true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (\Throwable) {
            // If Redis is down, proceed to live request
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\nUser-Agent: Web-Light-CRM/1.0\r\n",
                'timeout' => 1.5, // 1.5s timeout per PRD
                'ignore_errors' => true,
            ],
        ]);

        $url = self::ARES_API_URL . $ico;
        $response = @file_get_contents($url, false, $ctx);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['obchodniJmeno'])) {
            return null;
        }

        $sidlo = $data['sidlo'] ?? [];
        $street = trim(($sidlo['nazevUlice'] ?? '') . ' ' . ($sidlo['cisloDomovni'] ?? '') . ($sidlo['cisloOrientacni'] ? '/' . $sidlo['cisloOrientacni'] : ''));
        if ($street === '') {
            $street = (string) ($sidlo['nazevObce'] ?? '');
        }

        $city = (string) ($sidlo['nazevObce'] ?? '');
        $zip = str_replace(' ', '', (string) ($sidlo['psc'] ?? ''));
        $companyName = (string) $data['obchodniJmeno'];
        $formattedAddress = (string) ($sidlo['textovaAdresa'] ?? "{$street}, {$zip} {$city}");

        $result = [
            'ico' => $ico,
            'company_name' => $companyName,
            'street' => $street,
            'city' => $city,
            'zip' => $zip,
            'formatted_address' => $formattedAddress,
        ];

        // Cache result in Redis
        try {
            $this->redis->setex("ares:{$ico}", self::CACHE_TTL_SECONDS, json_encode($result, JSON_THROW_ON_ERROR));
        } catch (\Throwable) {
        }

        return $result;
    }
}
