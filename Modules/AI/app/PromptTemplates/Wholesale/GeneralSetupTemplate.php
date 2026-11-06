<?php

namespace Modules\AI\app\PromptTemplates\Wholesale;

use Modules\AI\app\Contracts\PromptTemplateInterface;
use Modules\AI\app\Services\Wholesale\WholesaleResourceService;

class GeneralSetupTemplate implements PromptTemplateInterface
{
    protected WholesaleResourceService $resourceService;

    public function __construct()
    {
        $this->resourceService = new WholesaleResourceService;
    }

    public function build(?string $context = null, ?string $language = null, ?string $description = null, ?array $options = null): string
    {
        $resource = $this->resourceService->generalSetupData();

        $categoryChains = $this->categoryChains($resource);
        $brands = implode("', '", array_keys($resource['brands']));
        $units = implode("', '", $resource['units']);

        return <<<PROMPT
            Analyze the wholesale product with these details:
            - Name: '{$context}'
            - Description: '{$description}'

            Generate ONLY valid JSON with these exact fields:

            {
              "category_name": "Category name",
              "sub_category_name": "Sub-category name",
              "sub_sub_category_name": "Sub-sub-category name",
              "brand_name": "Brand name",
              "unit_name": "Unit name",
              "reorder_level": 100,
              "shipping_cost": 25,
              "search_tags": ["tag1", "tag2"]
            }

            === INSTRUCTIONS ===
            1. SELECT the best matching category chain, brand and unit from the provided options.
            2. IF multiple options are possible, choose the most specific.
            3. Extract 3-5 relevant search tags from the name and description, and write every search tag in {$language} — even when the name or description is in another language.
            4. Pick ONE line from [CATEGORY CHAINS] and answer with exactly the names on that line: the first name is category_name, the second (if the line has one) is sub_category_name, the third (if the line has one) is sub_sub_category_name.
            5. NEVER pair a sub-category or a sub-sub-category with a category it is not listed under. If the chosen line has no second or third name, return null for sub_category_name, sub_sub_category_name or both — never borrow one from another line.
            6. brand_name is optional; include it only if it is in [BRANDS].
            7. reorder_level is the bulk restock threshold as a whole number, sized for wholesale order volumes.
            8. shipping_cost is the per-unit shipping charge as a number, judged from the product's size and weight.
            9. reorder_level and shipping_cost are both REQUIRED, must be greater than zero, and must never be null, empty or omitted.
            10. This is a wholesale catalogue: every product is physical, so never return a product type or a delivery type.
            11. DO NOT include comments, explanations, or any text outside the JSON.
            12. JSON must be valid for json_decode in PHP.

            === AVAILABLE OPTIONS ===
            [CATEGORY CHAINS] each line is one complete, valid category path — category > sub-category > sub-sub-category
            {$categoryChains}

            [BRANDS] '{$brands}'
            [UNITS] '{$units}'

            === OUTPUT FORMAT RULE ===
             - Return ONLY the raw JSON object — no code blocks, no markdown, no explanation, no labels, no timestamps, no extra text,(do not include ```json```).
             - The response must start with "{" and end with "}".
             - Only respond with "INVALID_INPUT" if the name or description is completely irrelevant, nonsensical, or empty.
             - Do not return generic explanations, fallback messages, or apologies.
        PROMPT;
    }

    public function getType(): string
    {
        return 'wholesale_general_setup';
    }

    private function categoryChains(array $resource): string
    {
        $lines = [];

        foreach ($resource['categories'] as $categoryName => $categoryId) {
            $subCategories = $resource['sub_categories'][$categoryId] ?? [];

            if ($subCategories === []) {
                $lines[] = $categoryName;

                continue;
            }

            foreach ($subCategories as $subCategoryName => $subCategoryId) {
                $subSubCategories = $resource['sub_sub_categories'][$subCategoryId] ?? [];

                if ($subSubCategories === []) {
                    $lines[] = "{$categoryName} > {$subCategoryName}";

                    continue;
                }

                foreach (array_keys($subSubCategories) as $subSubCategoryName) {
                    $lines[] = "{$categoryName} > {$subCategoryName} > {$subSubCategoryName}";
                }
            }
        }

        return implode("\n    ", $lines);
    }
}
