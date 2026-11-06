<?php

namespace Modules\AI\app\Services\New;

use Modules\AI\app\Contracts\AuctionAIContract;

class AuctionAIContentService implements AuctionAIContract
{
    private const DEFAULT_LANG_CODE = 'en';

    public function __construct(
        private readonly AIContentGeneratorService $aiContentGeneratorService,
    ) {}

    public function generateProductName(string $title, string $langCode = self::DEFAULT_LANG_CODE): string
    {
        return $this->generateAuctionContent(
            contentType: AIContentGeneratorService::CONTENT_AUCTION_TITLE,
            context: $title,
            langCode: $langCode,
        );
    }

    public function generateProductNameFromImage(string $imageUrl, string $langCode = self::DEFAULT_LANG_CODE): string
    {
        return $this->aiContentGeneratorService->generateContent(
            contentType: AIContentGeneratorService::CONTENT_TITLE_FROM_IMAGE,
            langCode: $langCode,
            imageUrl: $imageUrl,
        );
    }

    public function generateProductDescription(string $title, string $langCode = self::DEFAULT_LANG_CODE): string
    {
        return $this->generateAuctionContent(
            contentType: AIContentGeneratorService::CONTENT_AUCTION_DESCRIPTION,
            context: $title,
            langCode: $langCode,
        );
    }

    public function generateGeneralSetup(string $title, ?string $description = null): string
    {
        return $this->generateAuctionContent(
            contentType: AIContentGeneratorService::CONTENT_AUCTION_GENERAL_SETUP,
            context: $title,
            description: $description,
        );
    }

    public function generateShippingPolicy(string $title, ?string $description = null): string
    {
        return $this->generateAuctionContent(
            contentType: AIContentGeneratorService::CONTENT_AUCTION_SHIPPING_POLICY,
            context: $title,
            description: $description,
        );
    }

    public function generateAuctionInfo(string $title, ?string $description = null): string
    {
        return $this->generateAuctionContent(
            contentType: AIContentGeneratorService::CONTENT_AUCTION_INFO,
            context: $title,
            description: $description,
        );
    }

    public function generateSeoContent(string $title, ?string $description = null): string
    {
        return $this->generateAuctionContent(
            contentType: AIContentGeneratorService::CONTENT_AUCTION_SEO_SECTION,
            context: $title,
            description: $description,
        );
    }

    public function generateSetupSuggestion(string $title, ?string $description = null): string
    {
        return $this->generateAuctionInfo(title: $title, description: $description);
    }

    private function generateAuctionContent(
        string $contentType,
        ?string $context = null,
        string $langCode = self::DEFAULT_LANG_CODE,
        ?string $description = null,
    ): string {
        return $this->aiContentGeneratorService->generateContent(
            contentType: $contentType,
            context: $context,
            langCode: $langCode,
            description: $description,
        );
    }
}
