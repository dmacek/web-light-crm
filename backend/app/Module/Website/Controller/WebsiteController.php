<?php

declare(strict_types=1);

namespace App\Module\Website\Controller;

use App\Http\ApiResponse;
use App\Http\JsonRequest;
use App\Module\Website\WebsiteFacade;
use App\Security\AuthContext;

final class WebsiteController
{
    private WebsiteFacade $facade;

    public function __construct(?WebsiteFacade $facade = null)
    {
        $this->facade = $facade ?? new WebsiteFacade();
    }

    public function getConfig(): void
    {
        $businessId = AuthContext::requireAuth();
        $dto = $this->facade->getConfig($businessId);
        ApiResponse::json($dto->toArray());
    }

    public function updateConfig(): void
    {
        $businessId = AuthContext::requireAuth();
        $body = JsonRequest::getJsonBody();

        try {
            $updated = $this->facade->updateConfig($businessId, $body);
            ApiResponse::json($updated->toArray());
        } catch (\InvalidArgumentException $e) {
            ApiResponse::error($e->getMessage(), 'VALIDATION_ERROR', 422);
        } catch (\Throwable $e) {
            ApiResponse::error('Failed to update website configuration: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }

    public function uploadMedia(): void
    {
        $businessId = AuthContext::requireAuth();

        $body = JsonRequest::getJsonBody();
        $dataUrl = $body['image_base64'] ?? null;
        $caption = $body['caption'] ?? 'Fotografie práce';

        if (!$dataUrl || !is_string($dataUrl)) {
            ApiResponse::error('image_base64 parameter is required', 'MISSING_PARAM', 422);
            return;
        }

        // Return formatted media object (can be stored in local volume / var/uploads)
        $id = bin2hex(random_bytes(8));
        ApiResponse::created([
            'id' => $id,
            'image_url' => $dataUrl,
            'thumbnail_url' => $dataUrl,
            'caption' => $caption,
        ]);
    }
}
