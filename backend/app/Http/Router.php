<?php

declare(strict_types=1);

namespace App\Http;

use App\Bootstrap;
use App\Module\Auth\Controller\AuthController;
use App\Module\Billing\Controller\SubscriptionController;
use App\Module\Crm\Controller\LeadController;
use App\Module\Integrations\Ares\Controller\AresController;
use App\Module\Integrations\Domain\Controller\DomainCheckController;
use App\Module\Onboarding\Controller\OnboardingController;
use App\Module\Public\Controller\PublicLeadController;
use App\Module\Website\Controller\WebsiteController;

final class Router
{
    public function dispatch(): void
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Strip trailing slash
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        // Health check
        if ($uri === '/api/v1/health' && $method === 'GET') {
            ApiResponse::json([
                'status' => 'ok',
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
                'service' => 'backend-api',
            ]);
            return;
        }

        // ARES integration: GET /api/v1/integrations/ares/{ico}
        if (preg_match('#^/api/v1/integrations/ares/([0-9]{8})$#', $uri, $matches) && $method === 'GET') {
            $controller = new AresController();
            $controller->lookup($matches[1]);
            return;
        }

        // Caddy On-Demand TLS check: GET /api/v1/domains/check
        if ($uri === '/api/v1/domains/check' && $method === 'GET') {
            $controller = new DomainCheckController();
            $controller->check();
            return;
        }

        // Auth routes
        if ($uri === '/api/v1/auth/provider/seznam' && $method === 'POST') {
            (new AuthController())->loginSeznam();
            return;
        }
        if ($uri === '/api/v1/auth/provider/google' && $method === 'POST') {
            (new AuthController())->loginGoogle();
            return;
        }
        if ($uri === '/api/v1/auth/provider/apple' && $method === 'POST') {
            (new AuthController())->loginApple();
            return;
        }
        if ($uri === '/api/v1/auth/magic-link/request' && $method === 'POST') {
            (new AuthController())->requestMagicLink();
            return;
        }
        if ($uri === '/api/v1/auth/magic-link/verify' && $method === 'POST') {
            (new AuthController())->verifyMagicLink();
            return;
        }
        if ($uri === '/api/v1/auth/refresh-token' && $method === 'POST') {
            (new AuthController())->refreshToken();
            return;
        }

        // Onboarding routes
        if ($uri === '/api/v1/onboarding/draft' && ($method === 'GET' || $method === 'POST')) {
            $controller = new OnboardingController();
            if ($method === 'GET') {
                $controller->getDraft();
            } else {
                $controller->updateDraft();
            }
            return;
        }
        if ($uri === '/api/v1/onboarding/claim-draft' && $method === 'POST') {
            (new OnboardingController())->claimDraft();
            return;
        }

        // CRM Leads routes
        if ($uri === '/api/v1/crm/leads' && $method === 'GET') {
            (new LeadController())->list();
            return;
        }
        if (preg_match('#^/api/v1/crm/leads/([a-f0-9\-]+)/status$#i', $uri, $matches) && $method === 'PATCH') {
            (new LeadController())->updateStatus($matches[1]);
            return;
        }
        if (preg_match('#^/api/v1/crm/leads/([a-f0-9\-]+)/reminder$#i', $uri, $matches) && $method === 'PATCH') {
            (new LeadController())->updateReminder($matches[1]);
            return;
        }

        // Website config routes
        if ($uri === '/api/v1/website/config' && ($method === 'GET' || $method === 'PUT')) {
            $controller = new WebsiteController();
            if ($method === 'GET') {
                $controller->getConfig();
            } else {
                $controller->updateConfig();
            }
            return;
        }
        if ($uri === '/api/v1/website/media' && $method === 'POST') {
            (new WebsiteController())->uploadMedia();
            return;
        }

        // Public lead submission from website: POST /api/v1/public/site/{subdomain_or_domain}/lead
        if (preg_match('#^/api/v1/public/site/([a-zA-Z0-9\.\-]+)/lead$#', $uri, $matches) && $method === 'POST') {
            (new PublicLeadController())->submit($matches[1]);
            return;
        }

        // Billing routes
        if ($uri === '/api/v1/billing/subscription' && $method === 'GET') {
            (new SubscriptionController())->getSubscription();
            return;
        }
        if ($uri === '/api/v1/billing/subscribe' && $method === 'POST') {
            (new SubscriptionController())->subscribe();
            return;
        }

        // 404 Route Not Found
        ApiResponse::error("Route not found: {$method} {$uri}", 'ROUTE_NOT_FOUND', 404);
    }
}
