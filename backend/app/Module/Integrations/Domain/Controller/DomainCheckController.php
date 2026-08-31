<?php

declare(strict_types=1);

namespace App\Module\Integrations\Domain\Controller;

use App\Bootstrap;
use App\Http\ApiResponse;
use App\Http\JsonRequest;
use App\Module\Domain\BusinessRepository;

final class DomainCheckController
{
    private BusinessRepository $businessRepository;

    public function __construct(?BusinessRepository $businessRepository = null)
    {
        $this->businessRepository = $businessRepository ?? new BusinessRepository();
    }

    /**
     * Endpoint for Caddy On-Demand TLS check:
     * GET /api/v1/domains/check?domain=...
     */
    public function check(): void
    {
        $query = JsonRequest::getQueryParams();
        $domain = strtolower(trim((string) ($query['domain'] ?? '')));

        if ($domain === '') {
            ApiResponse::error('Parameter "domain" is required', 'MISSING_DOMAIN', 400);
            return;
        }

        $baseDomain = getenv('APP_DOMAIN') ?: 'tvojeaplikace.cz';

        // Allow app and api subdomains of the base platform domain
        if ($domain === $baseDomain || $domain === "app.{$baseDomain}" || $domain === "api.{$baseDomain}") {
            http_response_code(200);
            echo 'OK';
            exit;
        }

        // Check if it matches a tenant (subdomain or active custom .cz domain)
        $business = $this->businessRepository->findByHost($domain);

        if ($business !== null) {
            http_response_code(200);
            echo 'OK';
            exit;
        }

        // Domain not authorized for TLS issuance
        http_response_code(404);
        echo 'Domain not authorized';
        exit;
    }
}
