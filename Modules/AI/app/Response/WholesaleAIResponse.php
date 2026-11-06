<?php

namespace Modules\AI\app\Response;

use InvalidArgumentException;
use Modules\AI\app\Exceptions\ValidationException;
use Modules\AI\app\Services\Wholesale\WholesaleResourceService;

class WholesaleAIResponse
{
    public const FAQ_QUESTION_MAX_LENGTH = 50;

    public const FAQ_ANSWER_MAX_LENGTH = 150;

    private const PRICE_SETUP_QUANTITY = 'quantity';

    private const PRICE_SETUP_VARIATION = 'variation';

    private const PRICE_SETUP_MODES = ['quantity', 'variation', 'both'];

    private const REORDER_LEVEL_FALLBACK_TENS = [5, 50];

    private const SHIPPING_COST_FALLBACK_RANGE = [5, 50];

    private const SEO_FIELDS = [
        'meta_title',
        'meta_description',
        'meta_index',
        'meta_no_follow',
        'meta_no_image_index',
        'meta_no_archive',
        'meta_no_snippet',
        'meta_max_snippet',
        'meta_max_snippet_value',
        'meta_max_video_preview',
        'meta_max_video_preview_value',
        'meta_max_image_preview',
        'meta_max_image_preview_value',
    ];

    public function __construct(
        private readonly WholesaleResourceService $resourceService = new WholesaleResourceService
    ) {}

    public function generalSetup(string $result): array
    {
        $data = $this->decode($result);
        $resource = $this->resourceService->generalSetupData();

        if (empty($data['category_name']) || ! is_string($data['category_name'])) {
            throw new InvalidArgumentException('The "category_name" field is required and must be a non-empty string.');
        }

        if (empty($data['unit_name']) || ! is_string($data['unit_name'])) {
            throw new InvalidArgumentException('The "unit_name" field is required and must be a non-empty string.');
        }

        $data = $this->resolveIds($data, $resource);

        if (! array_key_exists('search_tags', $data)) {
            $data['search_tags'] = null;
        }

        $data['reorder_level'] = $this->reorderLevel($data['reorder_level'] ?? null);
        $data['shipping_cost'] = $this->shippingCost($data['shipping_cost'] ?? null);

        return $data;
    }

    private function reorderLevel(mixed $value): int
    {
        $level = is_numeric($value) ? (int) $value : 0;

        return $level > 0 ? $level : random_int(...self::REORDER_LEVEL_FALLBACK_TENS) * 10;
    }

    private function shippingCost(mixed $value): float
    {
        $cost = is_numeric($value) ? round((float) $value, 2) : 0.0;

        return $cost > 0 ? $cost : (float) random_int(...self::SHIPPING_COST_FALLBACK_RANGE);
    }

    public function variationSetup(string $result): array
    {
        $data = $this->decode($result);
        $errors = [];

        if (empty($data['choice_attributes']) || ! is_array($data['choice_attributes'])) {
            $errors[] = 'the product attributes. Please provide a more specific product name and a clear description.';
        }

        if (($data['colors_active'] ?? 0) == 1 && (empty($data['colors']) || ! is_array($data['colors']))) {
            $errors[] = 'the color variation. Please provide a more specific product name and a clear description.';
        }

        if ($errors !== []) {
            throw new ValidationException($this->validationMessage($errors));
        }

        if (isset($data['genereate_variation']) && is_array($data['genereate_variation'])) {
            foreach ($data['genereate_variation'] as &$variation) {
                unset($variation['price']);
                $variation['stock'] = (int) ($variation['stock'] ?? 0);
            }
            unset($variation);
        }

        return ['data' => $data, 'status' => 'success'];
    }

    public function pricingSetup(string $result, ?bool $hasVariation = null): array
    {
        $data = $this->decode($result);
        $errors = [];

        $minimumOrderQty = max(1, (int) ($data['minimum_order_qty'] ?? 1));
        $tiers = [];
        $previousMaximum = null;
        $previousSpan = null;

        foreach ($data['tiers'] ?? [] as $index => $tier) {
            $minimum = (int) ($tier['min_qty'] ?? 0);
            $price = (float) ($tier['unit_price'] ?? 0);
            $expectedMinimum = $previousMaximum === null ? $minimumOrderQty : $previousMaximum + 1;

            if ($minimum !== $expectedMinimum) {
                $errors[] = "a gapless quantity ladder — tier {$index} should start at {$expectedMinimum}.";
                break;
            }

            $maximum = ($tier['max_qty'] ?? null) === null
                ? $this->closingMaximum($minimum, $previousSpan)
                : (int) $tier['max_qty'];

            if ($maximum < $minimum) {
                $errors[] = "a valid quantity ladder — tier {$index} ends below where it starts.";
                break;
            }

            if ($price <= 0) {
                $errors[] = "a price above zero for tier {$index}.";
                break;
            }

            $tiers[] = ['min_qty' => $minimum, 'max_qty' => $maximum, 'unit_price' => $price];
            $previousMaximum = $maximum;
            $previousSpan = $maximum - $minimum + 1;
        }

        if ($tiers === [] && $errors === []) {
            $errors[] = 'a quantity tier ladder. Please provide a more specific product name and a clear description.';
        }

        if ($errors !== []) {
            throw new ValidationException($this->validationMessage($errors));
        }

        $lowestPrice = min(array_column($tiers, 'unit_price'));
        $discountType = ($data['discount_type'] ?? 'flat') === 'percent' ? 'percent' : 'flat';
        $discount = max(0.0, (float) ($data['discount'] ?? 0));

        if ($discountType === 'percent' && $discount >= 100) {
            $discount = 0.0;
        }

        if ($discountType === 'flat' && $discount >= $lowestPrice) {
            $discount = 0.0;
        }

        $priceSetup = $hasVariation === false ? self::PRICE_SETUP_QUANTITY : $this->priceSetupMode($data['price_setup'] ?? null);

        return [
            'price_setup' => $priceSetup,
            'quantity_based_price' => $priceSetup !== self::PRICE_SETUP_VARIATION,
            'variation_based_price' => $priceSetup !== self::PRICE_SETUP_QUANTITY,
            'minimum_order_qty' => $minimumOrderQty,
            'current_stock' => $this->currentStock($data['current_stock'] ?? null, $minimumOrderQty, $tiers),
            'discount' => $discount,
            'discount_type' => $discountType,
            'variation_discount' => $this->percentage($data['variation_discount'] ?? null),
            'tax_ids' => $this->getSuggestedTaxIds($data['tax_names'] ?? []),
            'tiers' => $tiers,
        ];
    }

    private function getSuggestedTaxIds(mixed $taxNames): array
    {
        $taxOptions = $this->resourceService->getTaxOptions();

        if ($taxOptions === []) {
            return [];
        }

        $taxIds = collect(is_array($taxNames) ? $taxNames : [])
            ->map(fn ($name): ?int => $taxOptions[strtolower(trim((string) $name))] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $taxIds === [] ? [array_values($taxOptions)[0]] : $taxIds;
    }

    private function currentStock(mixed $value, int $minimumOrderQty, array $tiers): int
    {
        $stock = is_numeric($value) ? (int) $value : 0;

        return $stock >= $minimumOrderQty
            ? $stock
            : max($minimumOrderQty, $tiers[array_key_last($tiers)]['max_qty']);
    }

    private function closingMaximum(int $minimum, ?int $previousSpan): int
    {
        return $minimum + ($previousSpan ?? $minimum * 10) - 1;
    }

    private function priceSetupMode(mixed $mode): string
    {
        return in_array($mode, self::PRICE_SETUP_MODES, true) ? $mode : self::PRICE_SETUP_QUANTITY;
    }

    private function percentage(mixed $value): float
    {
        $percentage = (float) $value;

        return $percentage > 0 && $percentage < 100 ? $percentage : 0.0;
    }

    private function resolveIds(array $data, array $resource): array
    {
        $categoryId = $resource['categories'][$this->lookupKey($data['category_name'])] ?? null;

        if ($categoryId === null) {
            throw new InvalidArgumentException($this->validationMessage(["a matching wholesale category for: {$data['category_name']}"]));
        }

        $subCategoryId = $this->childCategoryId($resource['sub_categories'], $categoryId, $data['sub_category_name'] ?? null);

        $subSubCategoryId = $subCategoryId === null
            ? null
            : $this->childCategoryId($resource['sub_sub_categories'], $subCategoryId, $data['sub_sub_category_name'] ?? null);

        $brandId = $resource['brands'][$this->lookupKey($data['brand_name'] ?? null)] ?? null;

        $data['category_id'] = $categoryId;
        $data['sub_category_id'] = $subCategoryId;
        $data['sub_sub_category_id'] = $subSubCategoryId;
        $data['brand_id'] = $brandId;
        $data['sub_category_name'] = $subCategoryId === null ? null : $data['sub_category_name'];
        $data['sub_sub_category_name'] = $subSubCategoryId === null ? null : $data['sub_sub_category_name'];
        $data['brand_name'] = $brandId === null ? null : $data['brand_name'];

        return $data;
    }

    private function childCategoryId(array $children, int $parentId, mixed $name): ?int
    {
        $key = $this->lookupKey($name);

        return $key === null ? null : ($children[$parentId][$key] ?? null);
    }

    private function lookupKey(mixed $name): ?string
    {
        if (! is_string($name) || trim($name) === '') {
            return null;
        }

        return strtolower(trim($name));
    }

    public function seoSection(string $result): array
    {
        $data = $this->decode($result);
        $missing = [];

        foreach (self::SEO_FIELDS as $field) {
            if (blank($data[$field] ?? null)) {
                $missing[] = "a {$field}.";
            }
        }

        if ($missing !== []) {
            throw new ValidationException($this->validationMessage($missing));
        }

        return array_intersect_key($data, array_flip(self::SEO_FIELDS));
    }

    public function faq(string $result, array $existingQuestions = [], array $translationLanguages = []): array
    {
        $data = $this->decode($result);
        $question = $this->plainText($data['question'] ?? null);
        $answer = $this->plainText($data['answer'] ?? null);
        $askedQuestions = array_map(fn (string $asked): string => mb_strtolower(trim($asked)), $existingQuestions);

        if (! $this->isFaqWithinLimits($question, $answer)) {
            throw new ValidationException($this->validationMessage(['a product FAQ within the question and answer limits. Please generate again.']));
        }

        if (in_array(mb_strtolower($question), $askedQuestions, true)) {
            throw new ValidationException($this->validationMessage(['a new product FAQ — this question is already added. Please generate again.']));
        }

        return [
            'question' => $question,
            'answer' => $answer,
            'priority' => count($existingQuestions) + 1,
            'translations' => $this->faqTranslations(
                translations: is_array($data['translations'] ?? null) ? $data['translations'] : [],
                languages: $translationLanguages,
            ),
        ];
    }

    private function faqTranslations(array $translations, array $languages): array
    {
        $entries = [];

        foreach ($languages as $locale) {
            $translation = is_array($translations[$locale] ?? null) ? $translations[$locale] : [];
            $question = $this->plainText($translation['question'] ?? null);
            $answer = $this->plainText($translation['answer'] ?? null);

            if ($this->isFaqWithinLimits($question, $answer)) {
                $entries[] = ['locale' => $locale, 'question' => $question, 'answer' => $answer];
            }
        }

        return $entries;
    }

    private function plainText(mixed $value): string
    {
        return is_string($value) ? trim(strip_tags($value)) : '';
    }

    private function isFaqWithinLimits(string $question, string $answer): bool
    {
        return $question !== ''
            && $answer !== ''
            && mb_strlen($question) <= self::FAQ_QUESTION_MAX_LENGTH
            && mb_strlen($answer) <= self::FAQ_ANSWER_MAX_LENGTH;
    }

    private function decode(string $result): array
    {
        $data = json_decode($result, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON: '.json_last_error_msg());
        }

        return $data;
    }

    private function validationMessage(array $errors): string
    {
        return 'AI couldn’t generate '.implode(' ', $errors);
    }
}
