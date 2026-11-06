<?php

namespace Modules\AI\app\PromptTemplates\Wholesale;

use Modules\AI\app\Contracts\PromptTemplateInterface;
use Modules\AI\app\Services\Wholesale\WholesaleResourceService;

class PricingSetupTemplate implements PromptTemplateInterface
{
    public function __construct(
        private readonly WholesaleResourceService $wholesaleResourceService = new WholesaleResourceService
    ) {}

    public function build(?string $context = null, ?string $language = null, ?string $description = null, ?array $options = null): string
    {
        $taxOptions = array_keys($this->wholesaleResourceService->getTaxOptions());
        $taxField = $taxOptions === [] ? '' : "\n              \"tax_names\": [\"".implode('", "', array_slice($taxOptions, 0, 1)).'"],';
        $taxRule = $taxOptions === []
            ? ''
            : "\n            11. \"tax_names\" lists the vat and tax rates that apply to this product, chosen only from: '".implode("', '", $taxOptions)."'. Pick the ones a wholesale trader would actually be charged, and return an empty array when none apply.";

        $variationLine = $this->variationLine($options);
        $priceSetupRule = $this->priceSetupRule($options);

        return <<<PROMPT
            You are an expert wholesale pricing analyst.
            Given the following wholesale product:
            - Name: {$context}
            - Description: {$description}{$variationLine}

            Generate ONLY a JSON object with this structure:
            {
              "price_setup": "quantity",
              "minimum_order_qty": 50,
              "current_stock": 5000,
              "discount": 0,
              "discount_type": "flat",
              "variation_discount": 5,{$taxField}
              "tiers": [
                { "min_qty": 50, "max_qty": 500, "unit_price": 45 },
                { "min_qty": 501, "max_qty": 1000, "unit_price": 40 },
                { "min_qty": 1001, "max_qty": 1500, "unit_price": 35 }
              ]
            }

            Rules:
            1. "price_setup" says how this product earns its price. Use "quantity" when only the order volume moves the price, "variation" when only the variant a buyer picks (size, colour, material, grade) moves it, and "both" when the variant and the volume move it together. {$priceSetupRule}
            2. Size every quantity for wholesale trade, not single-unit retail.
            3. The first tier must start exactly at "minimum_order_qty".
            4. Each tier after the first must start exactly one above the previous tier's "max_qty" — never overlap, never leave a gap.
            5. Every tier must have a "max_qty", the last one included. Never return null: close the ladder with a realistic upper quantity.
            6. Unit price must fall as the quantity rises, and every unit price must be above zero.
            7. "discount_type" is either "flat" or "percent". A flat discount must stay below the lowest unit price and a percent discount must stay below 100. Return 0 when no discount applies.
            8. "variation_discount" is the percentage taken off each variation's own price. Keep it below 100 and return 0 when no discount applies.
            9. Return between 3 and 5 tiers.
            10. "current_stock" is the whole number of units a wholesaler would keep in stock for this product, never below "minimum_order_qty" and sized to cover several orders from the tier ladder.{$taxRule}

            === OUTPUT FORMAT RULE ===
             - Return ONLY the raw JSON object — no code blocks, no markdown, no explanation, no labels, no timestamps, no extra text,(do not include ```json```).
             - The response must start with "{" and end with "}".
             - Only respond with "INVALID_INPUT" if the name or description is completely irrelevant, nonsensical, or empty.
             - Do not return generic explanations, fallback messages, or apologies.
        PROMPT;
    }

    public function getType(): string
    {
        return 'wholesale_pricing_setup';
    }

    private function variationLine(?array $options): string
    {
        $variations = $options['variations'] ?? [];

        return $variations === [] ? '' : "\n            - Variations: ".implode('; ', $variations);
    }

    private function priceSetupRule(?array $options): string
    {
        if (($options['has_variation'] ?? null) === false) {
            return 'This product is sold without variations, so "price_setup" must be "quantity".';
        }

        if (($options['variations'] ?? []) !== []) {
            return 'This product is sold in the variations listed above. Answer "variation" or "both" whenever those variants would realistically cost a trader different amounts — a larger size, a heavier weight, a better material, a higher capacity or grade, a bigger pack — and "both" when bulk volume also lowers the price. Answer "quantity" only when every listed variant would genuinely sell at the same price.';
        }

        return 'Judge it from this product alone: when the name or description names variants that differ in cost (sizes, weights, capacities, materials, grades or pack sizes), answer "variation" or "both" — do not answer "quantity" for every product.';
    }
}
