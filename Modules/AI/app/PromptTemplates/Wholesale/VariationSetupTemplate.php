<?php

namespace Modules\AI\app\PromptTemplates\Wholesale;

use Modules\AI\app\Contracts\PromptTemplateInterface;
use Modules\AI\app\Services\Wholesale\WholesaleResourceService;

class VariationSetupTemplate implements PromptTemplateInterface
{
    protected WholesaleResourceService $resourceService;

    public function __construct()
    {
        $this->resourceService = new WholesaleResourceService;
    }

    public function build(?string $context = null, ?string $language = null, ?string $description = null, ?array $options = null): string
    {
        $resource = $this->resourceService->variationData();

        $attributesList = [];
        foreach ($resource['attributes'] as $name => $id) {
            $attributesList[] = "{$name} (ID:{$id})";
        }
        $attributesString = implode(', ', $attributesList);

        $colorOptions = [];
        foreach ($resource['color'] as $color) {
            $colorOptions[] = "{$color['name']} ({$color['code']})";
        }
        $colorsString = implode(', ', $colorOptions);

        return <<<PROMPT
            You are an expert wholesale catalogue specialist with deep knowledge of product variations and attributes.
            Given the following wholesale product:
            - Name: {$context}
            - Description: {$description}

            Available configuration options from the system:
            - Attributes: {$attributesString}
            - Colors: {$colorsString}

            Generate ONLY a JSON object with the following structure for the product variation setup:
            {
              "colors_active": 0,
              "colors": [ { "code": "#000000", "name": "Black" } ],
              "choice_attributes": [ { "id": "1", "name": "Size", "options": ["30", "32"] } ],
              "genereate_variation": [ { "option": "", "sku": "", "stock": 0 } ]
            }

            Rules:
            1. Use ONLY the attributes listed above, with their exact ID and name. Never invent an attribute.
            2. Use ONLY the colors listed above, with their exact code and name. Never invent a color.
            3. Set "colors_active" to 1 only when the product genuinely varies by color, otherwise 0 and an empty "colors" array.
            4. Give every selected attribute 2-5 realistic values in its "options" array, drawn from the product name and description.
            5. Inside "genereate_variation", create one object per unique combination of the selected colors and attribute values.
               Each object must include:
                  - "option": the combination joined by hyphens (e.g. "Black-30")
                  - "sku": a unique identifier for that combination (e.g. "SKU-BLACK-30")
                  - "stock": a whole number sized for wholesale order volumes
            6. Never return a price. Wholesale pricing is configured separately as quantity tiers and variation prices.
            7. Only enable variations when they are clearly relevant to the product.

            === OUTPUT FORMAT RULE ===
             - Return ONLY the raw JSON object — no code blocks, no markdown, no explanation, no labels, no timestamps, no extra text,(do not include ```json```).
             - The response must start with "{" and end with "}".
             - Only respond with "INVALID_INPUT" if the name or description is completely irrelevant, nonsensical, or empty.
             - Do not return generic explanations, fallback messages, or apologies.
        PROMPT;
    }

    public function getType(): string
    {
        return 'wholesale_variation_setup';
    }
}
