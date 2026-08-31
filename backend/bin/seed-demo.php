#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Bootstrap;
use App\DTO\BusinessId;
use App\DTO\BusinessProfileDTO;
use App\DTO\WebContentDTO;
use App\Domain\Archetype;
use App\Domain\CustomDomainStatus;
use App\Domain\LeadStatus;
use App\Domain\Mood;
use App\Module\Crm\LeadRepository;
use App\Module\Domain\BusinessRepository;
use App\Module\Website\WebContentRepository;

Bootstrap::boot();

echo "Seeding demo business and leads...\n";

$businessRepo = new BusinessRepository();
$webRepo = new WebContentRepository();
$leadRepo = new LeadRepository();

$demoBusinessId = new BusinessId('11111111-1111-1111-1111-111111111111');

// Check if already exists
$existing = $businessRepo->findById($demoBusinessId);
if ($existing !== null) {
    echo "Demo business already exists with subdomain: {$existing->profile->subdomain}\n";
    exit(0);
}

$profile = new BusinessProfileDTO(
    ico: '27082440',
    companyName: 'Elektro Servis Novák s.r.o.',
    street: 'Jankovcova 1522/53',
    city: 'Praha',
    zip: '17000',
    archetype: Archetype::VYJEZDOVE_REMESLO,
    mainTradeName: 'Elektroinstalace a revize',
    subdomain: 'novak-elektro',
    customDomain: 'maly-elektro.cz',
    customDomainStatus: CustomDomainStatus::ACTIVE,
);

$business = $businessRepo->create(
    profile: $profile,
    email: 'tomas.novak@elektro-novak.cz',
    phone: '+420 777 888 999',
    id: $demoBusinessId,
);

echo "✓ Created Business: {$business->profile->companyName} ({$business->id->value})\n";

// Seed Web Content
$webContent = new WebContentDTO(
    businessId: $demoBusinessId,
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
        'vacation_banner' => [
            'active' => false,
            'text' => '',
        ],
        'services' => [
            [
                'id' => 'srv-1',
                'title' => 'Kompletní elektroinstalace bytů a domů',
                'description' => 'Návrh, tahání kabelů, osazení rozvaděče a zapojení zásuvek i svítidel.',
                'price_text' => 'od 15 000 Kč',
                'order' => 1,
            ],
            [
                'id' => 'srv-2',
                'title' => 'Výchozí i pravidelné revize elektro',
                'description' => 'Odborná zkouška a vystavení platné revizní zprávy pro kolaudaci i pojišťovnu.',
                'price_text' => 'od 2 500 Kč',
                'order' => 2,
            ],
            [
                'id' => 'srv-3',
                'title' => 'Havarijní pohotovost a opravy',
                'description' => 'Rychlý výjezd při výpadku proudu, zkratu nebo poškozeném jističi do 2 hodin.',
                'price_text' => 'od 1 200 Kč / hod',
                'order' => 3,
            ],
        ],
        'gallery' => [
            [
                'id' => 'gal-1',
                'image_url' => 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=800',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1621905251189-08b45d6a269e?w=400',
                'caption' => 'Nová rozvodnice v rodinném domě',
            ],
            [
                'id' => 'gal-2',
                'image_url' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=800',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1581092160607-ee22621dd758?w=400',
                'caption' => 'Zapojení designového LED osvětlení',
            ],
        ],
        'opening_hours' => 'Po - Pá: 7:00 - 18:00 (Havarijní linka NONSTOP)',
        'contact' => [
            'phone' => '+420 777 888 999',
            'email' => 'tomas.novak@elektro-novak.cz',
            'address_visible' => true,
        ],
    ],
);

$webRepo->save($webContent);
echo "✓ Created WebContent with Chameleon Design\n";

// Seed Sample CRM Leads
$leadRepo->create(
    businessId: $demoBusinessId,
    senderName: 'Martin Dvořák',
    senderPhone: '+420 608 111 222',
    senderEmail: 'dvorak.martin@seznam.cz',
    message: 'Dobrý den, potřebuji udělat kompletní novou elektřinu v bytě 3+1 v Praze 7. Termín ideálně příští měsíc.',
);

$leadRepo->create(
    businessId: $demoBusinessId,
    senderName: 'Lucie Králová',
    senderPhone: '+420 721 333 444',
    senderEmail: 'lucie.kralova@gmail.com',
    message: 'Dobrý den, vypadly nám jističe v kuchyni a nejde zapnout trouba. Mohl byste se prosím stavit?',
);

echo "✓ Created 2 Sample Leads\n";
echo "Done! Demo data seeded successfully.\n";
