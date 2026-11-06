<?php

namespace Modules\AI\app\PromptTemplates;

use Modules\AI\app\Contracts\PromptTemplateInterface;

class ProductNameTemplate implements PromptTemplateInterface
{
    public function build(?string $context = null, ?string $language = null, ?string $description = null, ?array $options = null): string
    {
        return <<<PROMPT
          You are a professional e-commerce copywriter.

          Rewrite the product name "{$context}" as a clean, concise, and professional product title for online stores.

          CRITICAL INSTRUCTION:
          - The output must be 100% in {$language} — this is mandatory.
          - If the original name is not in {$language}, fully translate it into {$language} while keeping the meaning.
          - Do not mix languages; use only {$language} characters and words.
          - Keep it short (35–70 characters), plain, and ready for listings.
          - No extra words, slogans, or punctuation.
          - Return only the translated title as plain text in {$language}.

      IMPORTANT:
        - Treat the input as an item sold in an online store, whatever its category — electronics, clothing, home goods, groceries, fresh food, beauty, books, digital goods and services are all valid.
        - A broad category name such as "Digital goods" is still a valid input; title it as the kind of item it names.
        - Respond with only the word "INVALID_INPUT" when the input carries no product meaning at all, such as random characters, a placeholder like "test", or an empty value.
        - Do not return generic explanations or fallback messages.


      PROMPT;
    }

    public function getType(): string
    {
        return 'product_name';
    }
}
