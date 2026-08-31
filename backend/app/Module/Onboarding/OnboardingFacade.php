<?php

declare(strict_types=1);

namespace App\Module\Onboarding;

use App\DTO\BusinessDTO;
use App\DTO\BusinessId;
use App\DTO\BusinessProfileDTO;
use App\DTO\WebContentDTO;
use App\Domain\Archetype;
use App\Domain\CustomDomainStatus;
use App\Domain\Mood;
use App\Module\Domain\BusinessRepository;
use App\Module\Website\WebContentRepository;

final class OnboardingFacade
{
    private OnboardingDraftRepository $draftRepository;
    private BusinessRepository $businessRepository;
    private WebContentRepository $webContentRepository;

    public function __construct(
        ?OnboardingDraftRepository $draftRepository = null,
        ?BusinessRepository $businessRepository = null,
        ?WebContentRepository $webContentRepository = null,
    ) {
        $this->draftRepository = $draftRepository ?? new OnboardingDraftRepository();
        $this->businessRepository = $businessRepository ?? new BusinessRepository();
        $this->webContentRepository = $webContentRepository ?? new WebContentRepository();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getDraft(string $sessionDraftId): ?array
    {
        return $this->draftRepository->get($sessionDraftId);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function saveDraft(string $sessionDraftId, array $data): void
    {
        $existing = $this->draftRepository->get($sessionDraftId) ?? [];
        $merged = array_merge($existing, $data);
        $this->draftRepository->save($sessionDraftId, $merged);
    }

    /**
     * Claim draft and create initial business and web content
     */
    public function claimDraft(string $sessionDraftId, BusinessId $businessId, ?string $authEmail = null): BusinessDTO
    {
        $draft = $this->draftRepository->get($sessionDraftId);
        if ($draft === null) {
            throw new \InvalidArgumentException("Draft session {$sessionDraftId} not found or expired");
        }

        $ico = (string) ($draft['ico'] ?? '00000000');
        $companyName = (string) ($draft['company_name'] ?? 'Moje Provozovna');
        $street = (string) ($draft['street'] ?? 'Hlavní 1');
        $city = (string) ($draft['city'] ?? 'Praha');
        $zip = (string) ($draft['zip'] ?? '11000');
        $archetypeStr = (string) ($draft['archetype'] ?? 'PROVOZOVNA');
        $archetype = Archetype::tryFrom($archetypeStr) ?? Archetype::PROVOZOVNA;
        $mainTradeName = (string) ($draft['main_trade_name'] ?? 'Služby');
        $phone = (string) ($draft['phone'] ?? '+420 777 000 000');
        $email = (string) ($draft['email'] ?? ($authEmail ?? 'info@' . self::slugify($companyName) . '.cz'));

        // Generate unique subdomain from company name
        $subdomain = $this->generateUniqueSubdomain($companyName);

        $profile = new BusinessProfileDTO(
            ico: $ico,
            companyName: $companyName,
            street: $street,
            city: $city,
            zip: $zip,
            archetype: $archetype,
            mainTradeName: $mainTradeName,
            subdomain: $subdomain,
            customDomain: null,
            customDomainStatus: CustomDomainStatus::NONE,
        );

        // 1. Create Business (creates trial subscription)
        $business = $this->businessRepository->create($profile, $email, $phone, $businessId);

        // 2. Initialize default WebContent with sample services and design
        $sampleServices = $draft['services'] ?? $this->getDefaultServicesForTrade($mainTradeName);
        $webContent = new WebContentDTO(
            businessId: $businessId,
            version: 1,
            design: [
                'mood' => Mood::MODERN,
                'color_palette' => [
                    'primary' => '#2563eb',
                    'secondary' => '#1e40af',
                    'background' => '#ffffff',
                ],
                'block_variants' => [
                    'hero' => 'FULL_IMAGE_OVERLAY',
                    'pricing' => 'LIST_DOTS',
                    'gallery' => 'GRID_2X2',
                ],
            ],
            content: [
                'vacation_banner' => ['active' => false, 'text' => ''],
                'services' => $sampleServices,
                'gallery' => $draft['photos'] ?? [],
                'opening_hours' => $draft['opening_hours'] ?? 'Po-Pá: 8:00 - 17:00',
                'contact' => [
                    'phone' => $phone,
                    'email' => $email,
                    'address_visible' => true,
                ],
            ],
        );

        $this->webContentRepository->save($webContent);

        // 3. Cleanup draft
        $this->draftRepository->delete($sessionDraftId);

        return $business;
    }

    private function generateUniqueSubdomain(string $name): string
    {
        $base = self::slugify($name);
        if ($base === '') {
            $base = 'web';
        }

        // Add 4 hex characters for uniqueness per PRD
        $suffix = substr(bin2hex(random_bytes(2)), 0, 4);
        $candidate = "{$base}-{$suffix}";

        // Ensure <= 60 chars
        if (strlen($candidate) > 60) {
            $candidate = substr($candidate, 0, 55) . "-{$suffix}";
        }

        return $candidate;
    }

    public static function slugify(string $text): string
    {
        // Transliterate Czech characters
        $translits = [
            'á' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e',
            'í' => 'i', 'ň' => 'n', 'ó' => 'o', 'ř' => 'r', 'š' => 's',
            'ť' => 't', 'ú' => 'u', 'ů' => 'u', 'ý' => 'y', 'ž' => 'z',
            'Á' => 'a', 'Č' => 'c', 'Ď' => 'd', 'É' => 'e', 'Ě' => 'e',
            'Í' => 'i', 'Ň' => 'n', 'Ó' => 'o', 'Ř' => 'r', 'Š' => 's',
            'Ť' => 't', 'Ú' => 'u', 'Ů' => 'u', 'Ý' => 'y', 'Ž' => 'z',
        ];
        $text = strtr($text, $translits);
        $text = preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($text));
        return trim((string) $text, '-');
    }

    /**
     * @return list<array{id: string, title: string, description: string, price_text: string, order: int}>
     */
    private function getDefaultServicesForTrade(string $trade): array
    {
        return [
            [
                'id' => BusinessId::generate()->value,
                'title' => 'Standardní konzultace a diagnostika',
                'description' => 'Osobní prohlídka, zhodnocení stavu a návrh optimálního řešení na míru.',
                'price_text' => 'od 500 Kč',
                'order' => 1,
            ],
            [
                'id' => BusinessId::generate()->value,
                'title' => 'Kompletní realizace služby',
                'description' => 'Profesionální provedení s důrazem na detail a kvalitní materiály.',
                'price_text' => 'Dle domluvy',
                'order' => 2,
            ],
            [
                'id' => BusinessId::generate()->value,
                'title' => 'Pohotovostní servis a opravy',
                'description' => 'Rychlý výjezd a řešení urgentních závad do 24 hodin.',
                'price_text' => 'od 1 200 Kč',
                'order' => 3,
            ],
        ];
    }
}
