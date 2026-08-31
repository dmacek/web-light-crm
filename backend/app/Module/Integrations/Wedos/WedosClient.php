<?php

declare(strict_types=1);

namespace App\Module\Integrations\Wedos;

final class WedosClient
{
    private const string WAPI_URL = 'https://api.wedos.com/wapi/json';
    private string $user;
    private string $auth;
    private bool $isTest;

    public function __construct(
        ?string $user = null,
        ?string $auth = null,
        ?bool $isTest = null,
    ) {
        $this->user = $user ?? (getenv('WEDOS_WAPI_USER') ?: '');
        $this->auth = $auth ?? (getenv('WEDOS_WAPI_AUTH') ?: '');
        $this->isTest = $isTest ?? ((getenv('WEDOS_WAPI_TEST') ?: '1') === '1');
    }

    /**
     * Check if a .cz domain is available for registration
     */
    public function checkDomainAvailability(string $domain): bool
    {
        $domain = strtolower(trim($domain));
        if (!str_ends_with($domain, '.cz')) {
            $domain .= '.cz';
        }

        // In test mode or when no credentials are provided, simulate response
        if ($this->isTest || $this->user === '' || $this->auth === '') {
            return !in_array($domain, ['google.cz', 'seznam.cz', 'alza.cz', 'idnes.cz'], true);
        }

        $requestPayload = [
            'request' => [
                'user' => $this->user,
                'auth' => sha1($this->user . sha1($this->auth) . date('H')),
                'command' => 'domain-check',
                'data' => [
                    'name' => $domain,
                ],
            ],
        ];

        $response = $this->callWapi($requestPayload);
        $code = (int) ($response['response']['code'] ?? 0);

        // 1000 = Domain is available
        return $code === 1000;
    }

    /**
     * Register .cz domain (included in Annual plan)
     *
     * @return array{status: 'ACTIVE' | 'PENDING' | 'ERROR', message: string}
     */
    public function registerCzDomain(string $domain, string $companyName, string $email, string $phone): array
    {
        $domain = strtolower(trim($domain));
        if (!str_ends_with($domain, '.cz')) {
            $domain .= '.cz';
        }

        // In test/mock mode:
        if ($this->isTest || $this->user === '' || $this->auth === '') {
            return [
                'status' => 'ACTIVE',
                'message' => "Doména {$domain} byla úspěšně zaregistrována v testovacím režimu.",
            ];
        }

        $requestPayload = [
            'request' => [
                'user' => $this->user,
                'auth' => sha1($this->user . sha1($this->auth) . date('H')),
                'command' => 'domain-create',
                'data' => [
                    'name' => $domain,
                    'period' => 1,
                    'owner_c' => 'CONTACT_AUTO',
                ],
            ],
        ];

        try {
            $response = $this->callWapi($requestPayload);
            $code = (int) ($response['response']['code'] ?? 0);

            if ($code === 1000 || $code === 1001) {
                return [
                    'status' => 'ACTIVE',
                    'message' => "Doména {$domain} byla úspěšně zaregistrována přes WEDOS WAPI.",
                ];
            }

            return [
                'status' => 'PENDING',
                'message' => $response['response']['result'] ?? 'Objednávka domény čeká na zpracování.',
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'ERROR',
                'message' => 'Chyba při registraci domény: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function callWapi(array $payload): array
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);

        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query(['request' => $json]),
                'timeout' => 5,
            ],
        ]);

        $res = @file_get_contents(self::WAPI_URL, false, $ctx);
        if ($res === false) {
            throw new \RuntimeException('Failed to connect to Wedos WAPI');
        }

        return json_decode($res, true, 512, JSON_THROW_ON_ERROR);
    }
}
