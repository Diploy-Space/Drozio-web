<?php

namespace Modules\AI\app\PromptTemplates\Auction;

use Modules\AI\app\Contracts\PromptTemplateInterface;

class AuctionTitleTemplate implements PromptTemplateInterface
{
    public function build(?string $context = null, ?string $language = null, ?string $description = null, ?array $options = null): string
    {
        return <<<PROMPT
            You are a professional marketplace copywriter for auction listings.

            Rewrite the auction item title "{$context}" as a clean, concise, and trustworthy listing title.

            CRITICAL INSTRUCTION:
            - The output must be 100% in {$language}.
            - If the original title is in another language, translate it naturally into {$language}.
            - Keep it short, descriptive, and suitable for an auction product card.
            - Avoid hype, emojis, and unnecessary punctuation.
            - Return only the final title as plain text.

            IMPORTANT:
            - Only process meaningful physical auction items or collectible goods.
            - If the input is meaningless or unrelated to a valid auction product, respond with only "INVALID_INPUT".
        PROMPT;
    }

    public function getType(): string
    {
        return 'auction_title';
    }
}
