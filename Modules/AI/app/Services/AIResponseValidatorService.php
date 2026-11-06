<?php

namespace Modules\AI\app\Services;

use Modules\AI\app\Exceptions\ImageValidationException;
use Modules\AI\app\Exceptions\ValidationException;

class AIResponseValidatorService
{
    public function validateProductTitle(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating a product title. Please provide a meaningful product name.');
        }
    }

    public function validateProductDescription(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating a product description. Please provide a meaningful name or description.');
        }
    }

    public function validateProductGeneralSetup(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for general setup. Please provide meaningful data.');
        }
    }

    public function validateProductPricingAndOthers(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating a product description. Please provide a meaningful name or description.');
        }
    }

    public function validateProductVariationSetup(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating a product description. Please provide a meaningful name or description.');
        }
    }

    public function validateProductSeoContent(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating a product seo information. Please provide a meaningful name or description.');
        }
    }

    public function validateProductKeyword(string $response, string|array|null $context = null): void
    {
        if ($this->isInvalidProductKeyword($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating a product title. Please provide a meaningful keyword.');
        }
    }

    public function validateImageResponse(string $response): void
    {
        if ($this->isInvalidImageResponse($response)) {
            throw new ImageValidationException('The uploaded image is not valid for generating product content. Please provide a meaningful image.');
        }
    }

    public function validateBlogTitle(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating a blog title. Please provide a meaningful blog title.');
        }
    }

    public function validateBlogDescription(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductDescription($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating a blog description. Please provide a meaningful blog title or description.');
        }
    }

    public function validateBlogSeoContent(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating a blog seo information. Please provide a meaningful title or description.');
        }
    }

    public function validateBlogKeyword(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating blog titles. Please provide meaningful keywords');
        }
    }

    public function validateBlogImageResponse(string $response): void
    {
        if ($this->isInvalidImageResponse($response)) {
            throw new ImageValidationException('The uploaded image is not valid for generating blog content. Please provide a meaningful image.');
        }
    }

    public function validateAuctionTitle(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating an auction title. Please provide a meaningful auction item name.');
        }
    }

    public function validateAuctionDescription(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductDescription($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating an auction description. Please provide a meaningful auction item name.');
        }
    }

    public function validateAuctionSeoContent(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating auction SEO information. Please provide meaningful auction item data.');
        }
    }

    public function validateAuctionGeneralSetup(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating auction general setup. Please provide meaningful auction item data.');
        }
    }

    public function validateAuctionShippingPolicy(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating auction shipping and policy data. Please provide meaningful auction item data.');
        }
    }

    public function validateAuctionInfo(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating auction information. Please provide meaningful auction item data.');
        }
    }

    public function validateAuctionSetup(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating auction setup suggestions. Please provide meaningful auction item data.');
        }
    }

    public function validateWholesaleGeneralSetup(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating wholesale general setup. Please provide a meaningful product name and description.');
        }
    }

    public function validateWholesaleVariationSetup(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating wholesale variation setup. Please provide a meaningful product name and description.');
        }
    }

    public function validateWholesalePricingSetup(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating wholesale pricing setup. Please provide a meaningful product name and description.');
        }
    }

    public function validateWholesaleFaq(string $response, ?string $context = null): void
    {
        if ($this->isInvalidProductTitle($response, $context)) {
            throw new ValidationException('The provided input is not valid for generating wholesale product FAQs. Please provide a meaningful product name and description.');
        }
    }

    private function isInvalidProductTitle(string $response, ?string $context = null): bool
    {
        return $this->phraseCheck($response, $context);
    }

    private function isInvalidProductDescription(string $response, ?string $context = null): bool
    {
        return $this->phraseCheck($response, $context);
    }

    private function isInvalidProductKeyword(string $response, string|array|null $context = null): bool
    {
        return $this->phraseCheck($response, is_array($context) ? implode(', ', $context) : $context);
    }

    private function isInvalidImageResponse(string $response): bool
    {
        return $this->phraseCheck($response, null);
    }

    public function phraseCheck(string $response, ?string $context): bool
    {
        $invalidPhrases = [
            'INVALID_INPUT',
        ];
        foreach ($invalidPhrases as $phrase) {
            if (stripos($response, $phrase) !== false) {
                return true;
            }
        }

        return false;
    }
}
