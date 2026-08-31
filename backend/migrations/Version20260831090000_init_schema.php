<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260831090000_init_schema extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initialize multi-tenant schema with businesses, auth, subscriptions, web_contents, leads, drafts and media';
    }

    public function up(Schema $schema): void
    {
        // Extension for UUID generation if needed
        $this->addSql('CREATE EXTENSION IF NOT EXISTS "pgcrypto";');

        // Businesses table
        $this->addSql(<<<'SQL'
            CREATE TABLE businesses (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                email VARCHAR(255) NOT NULL,
                phone VARCHAR(50) NOT NULL,
                ico VARCHAR(20) NOT NULL,
                company_name VARCHAR(255) NOT NULL,
                street VARCHAR(255) NOT NULL,
                city VARCHAR(255) NOT NULL,
                zip VARCHAR(20) NOT NULL,
                archetype VARCHAR(50) NOT NULL,
                main_trade_name VARCHAR(255) NOT NULL,
                subdomain VARCHAR(100) NOT NULL UNIQUE,
                custom_domain VARCHAR(255) NULL UNIQUE,
                custom_domain_status VARCHAR(20) NOT NULL DEFAULT 'NONE',
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX idx_businesses_subdomain ON businesses(subdomain);
            CREATE INDEX idx_businesses_custom_domain ON businesses(custom_domain);
        SQL);

        // Auth providers table
        $this->addSql(<<<'SQL'
            CREATE TABLE auth_providers (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
                provider VARCHAR(50) NOT NULL,
                provider_user_id VARCHAR(255) NOT NULL,
                linked_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT uq_auth_provider_user UNIQUE (provider, provider_user_id)
            );
            CREATE INDEX idx_auth_providers_business_id ON auth_providers(business_id);
        SQL);

        // Subscriptions table
        $this->addSql(<<<'SQL'
            CREATE TABLE subscriptions (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                business_id UUID NOT NULL UNIQUE REFERENCES businesses(id) ON DELETE CASCADE,
                status VARCHAR(20) NOT NULL DEFAULT 'TRIAL',
                plan VARCHAR(20) NULL,
                trial_ends_at TIMESTAMPTZ NOT NULL,
                current_period_ends_at TIMESTAMPTZ NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX idx_subscriptions_business_id ON subscriptions(business_id);
            CREATE INDEX idx_subscriptions_status ON subscriptions(status);
        SQL);

        // Web contents (1:1 with business, stores JSONB Chameleon design & content)
        $this->addSql(<<<'SQL'
            CREATE TABLE web_contents (
                business_id UUID PRIMARY KEY REFERENCES businesses(id) ON DELETE CASCADE,
                version INT NOT NULL DEFAULT 1,
                design JSONB NOT NULL,
                content JSONB NOT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX idx_web_contents_business_id ON web_contents(business_id);
        SQL);

        // Leads (CRM)
        $this->addSql(<<<'SQL'
            CREATE TABLE leads (
                lead_id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
                sender_name VARCHAR(255) NOT NULL,
                sender_phone VARCHAR(50) NOT NULL,
                sender_email VARCHAR(255) NULL,
                message TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'NEW',
                reminder_at TIMESTAMPTZ NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX idx_leads_business_id ON leads(business_id);
            CREATE INDEX idx_leads_business_status ON leads(business_id, status);
            CREATE INDEX idx_leads_business_created ON leads(business_id, created_at DESC);
        SQL);

        // Onboarding Drafts (anonymous session drafts before claiming)
        $this->addSql(<<<'SQL'
            CREATE TABLE onboarding_drafts (
                session_draft_id VARCHAR(128) PRIMARY KEY,
                step INT NOT NULL DEFAULT 1,
                data JSONB NOT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMPTZ NOT NULL
            );
            CREATE INDEX idx_onboarding_drafts_expires ON onboarding_drafts(expires_at);
        SQL);

        // Media table
        $this->addSql(<<<'SQL'
            CREATE TABLE media (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                business_id UUID NOT NULL REFERENCES businesses(id) ON DELETE CASCADE,
                image_url VARCHAR(500) NOT NULL,
                thumbnail_url VARCHAR(500) NOT NULL,
                caption VARCHAR(255) NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
            CREATE INDEX idx_media_business_id ON media(business_id);
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS media;');
        $this->addSql('DROP TABLE IF EXISTS onboarding_drafts;');
        $this->addSql('DROP TABLE IF EXISTS leads;');
        $this->addSql('DROP TABLE IF EXISTS web_contents;');
        $this->addSql('DROP TABLE IF EXISTS subscriptions;');
        $this->addSql('DROP TABLE IF EXISTS auth_providers;');
        $this->addSql('DROP TABLE IF EXISTS businesses;');
    }
}
