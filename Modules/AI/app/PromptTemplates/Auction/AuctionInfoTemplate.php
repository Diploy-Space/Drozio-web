<?php

namespace Modules\AI\app\PromptTemplates\Auction;

use Modules\AI\app\Contracts\PromptTemplateInterface;
use Modules\AI\app\Services\Auction\AuctionResourceService;

class AuctionInfoTemplate implements PromptTemplateInterface
{
    public function __construct(
        private readonly AuctionResourceService $auctionResourceService = new AuctionResourceService
    ) {}

    public function build(?string $context = null, ?string $language = null, ?string $description = null, ?array $options = null): string
    {
        $currency = getCurrencySymbol();
        $taxOptions = implode("', '", array_keys($this->auctionResourceService->taxOptions()));
        $currentDateTime = now()->format('Y-m-d H:i:s');

        return <<<PROMPT
            You are generating auction setup suggestions for this listing:
            - Title: "{$context}"
            - Description: "{$description}"

            Generate ONLY valid JSON with these exact fields:
            {
              "entry_fee": <number>,
              "starting_price": <number>,
              "minimum_increment_amount": <number>,
              "maximum_decrement_amount": <number>,
              "tax_names": ["<pick one from the available tax options below>"],
              "start_time": "<Y-m-d H:i:s>",
              "end_time": "<Y-m-d H:i:s>",
              "tags": ["<short search keyword>", "<short search keyword>", "<short search keyword>"]
            }

            Instructions:
            - Every amount is a plain JSON number in currency "{$currency}", without a currency symbol or quotes.
            - Estimate the realistic current market value of this exact item from its title and description (brand, model, age, condition, rarity) and derive every amount from that estimate. Never reuse the same amounts for different items.
            - "starting_price" is an attractive opening bid, typically 30% to 70% of the estimated market value.
            - "minimum_increment_amount" is the smallest step a new bid must add, typically 1% to 5% of "starting_price", rounded to a sensible figure.
            - "maximum_decrement_amount" is the largest amount the price may be lowered by in one step, typically 5% to 15% of "starting_price", rounded to a sensible figure.
            - "entry_fee" is a small participation fee, typically 0% to 2% of "starting_price".
            - "minimum_increment_amount" MUST be greater than 0. Never return 0.
            - "maximum_decrement_amount" MUST be greater than 0. Never return 0.
            - "tax_names" MUST contain at least one name from the provided tax options. Select the most applicable tax for this type of product. Never return an empty array for "tax_names".
            - "tax_names" must contain only names that exist exactly in the provided tax options list. Do NOT invent or guess tax names.
            - The current date and time is "{$currentDateTime}".
            - "start_time" must be at least 1 hour after the current date and time, on a whole hour or half hour. NEVER use a past/backdated time.
            - "end_time" must be later than "start_time", typically 3 to 7 days after it, longer for rare or high-value items.
            - Both "start_time" and "end_time" must be valid datetime strings in "Y-m-d H:i:s" format.
            - "tags" MUST be an array of 3 to 8 short search keywords customers would type to discover this listing (e.g. brand, type, material, era, use case). Each keyword: lowercase, 1-3 words, no punctuation, no duplicates, no hashtags.
            - Return ONLY a valid raw JSON object that can be decoded directly with PHP/Laravel `json_decode()`.
            - Do NOT wrap the JSON in triple backticks.
            - Do NOT add ```json, markdown, explanation, notes, labels, headings, or extra text before or after the JSON.
            - The response must start with "{" and end with "}".
            - Use double quotes for all JSON keys and string values.
            - If the input is invalid or meaningless, return only "INVALID_INPUT".

            Available tax options:
            - '{$taxOptions}'
        PROMPT;
    }

    public function getType(): string
    {
        return 'auction_info';
    }
}
