<?php

declare(strict_types=1);

namespace App\Module\Integrations\Ares\Controller;

use App\Http\ApiResponse;
use App\Module\Integrations\Ares\AresClient;

final class AresController
{
    private AresClient $client;

    public function __construct(?AresClient $client = null)
    {
        $this->client = $client ?? new AresClient();
    }

    public function lookup(string $ico): void
    {
        try {
            $data = $this->client->lookup($ico);
            if ($data === null) {
                ApiResponse::error("Entity with IČO {$ico} not found in ARES registry", 'NOT_FOUND', 404);
                return;
            }

            ApiResponse::json($data);
        } catch (\InvalidArgumentException $e) {
            ApiResponse::error($e->getMessage(), 'INVALID_ICO', 422);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to communicate with ARES service: ' . $e->getMessage(), 'ARES_ERROR', 503);
        }
    }
}
