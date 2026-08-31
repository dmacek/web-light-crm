<?php

declare(strict_types=1);

namespace App\Module\Public\Controller;

use App\Http\ApiResponse;
use App\Http\JsonRequest;
use App\Module\Public\LeadIntakeService;

final class PublicLeadController
{
    private LeadIntakeService $service;

    public function __construct(?LeadIntakeService $service = null)
    {
        $this->service = $service ?? new LeadIntakeService();
    }

    public function submit(string $subdomainOrDomain): void
    {
        $body = JsonRequest::getJsonBody();

        $name = (string) ($body['sender_name'] ?? '');
        $phone = (string) ($body['sender_phone'] ?? '');
        $email = isset($body['sender_email']) ? (string) $body['sender_email'] : null;
        $message = (string) ($body['message'] ?? '');

        $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        try {
            $lead = $this->service->submitLead(
                hostOrSubdomain: $subdomainOrDomain,
                senderName: $name,
                senderPhone: $phone,
                senderEmail: $email,
                message: $message,
                clientIp: $clientIp,
            );

            ApiResponse::created([
                'status' => 'ok',
                'lead_id' => $lead->leadId,
                'message' => 'Poptávka byla úspěšně odeslána. Živnostník se vám brzy ozve.',
            ]);
        } catch (\OverflowException $e) {
            ApiResponse::error($e->getMessage(), 'RATE_LIMIT_EXCEEDED', 429);
        } catch (\InvalidArgumentException $e) {
            ApiResponse::error($e->getMessage(), 'VALIDATION_ERROR', 422);
        } catch (\Throwable $e) {
            ApiResponse::error('Chyba při odesílání poptávky: ' . $e->getMessage(), 'SERVER_ERROR', 500);
        }
    }
}
