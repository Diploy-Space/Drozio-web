<?php

namespace Tests\Unit\AI;

use Modules\AI\app\Exceptions\ValidationException;
use Modules\AI\app\PromptTemplates\Wholesale\FaqTemplate;
use Modules\AI\app\PromptTemplates\Wholesale\PricingSetupTemplate;
use Modules\AI\app\Response\WholesaleAIResponse;
use Modules\AI\app\Services\Wholesale\WholesaleResourceService;
use PHPUnit\Framework\TestCase;

class WholesaleFaqAndPricingResponseTest extends TestCase
{
    private function resourceService(): WholesaleResourceService
    {
        return new class extends WholesaleResourceService
        {
            public function __construct() {}

            public function getTaxOptions(): array
            {
                return [];
            }
        };
    }

    private function response(): WholesaleAIResponse
    {
        return new WholesaleAIResponse($this->resourceService());
    }

    private function pricingResult(string $priceSetup): string
    {
        return json_encode([
            'price_setup' => $priceSetup,
            'minimum_order_qty' => 50,
            'discount' => 0,
            'discount_type' => 'flat',
            'variation_discount' => 5,
            'tiers' => [
                ['min_qty' => 50, 'max_qty' => 500, 'unit_price' => 45],
                ['min_qty' => 501, 'max_qty' => 1000, 'unit_price' => 40],
            ],
        ]);
    }

    public function test_variation_price_setup_selects_only_variation_based_pricing(): void
    {
        $pricing = $this->response()->pricingSetup($this->pricingResult('variation'), hasVariation: true);

        $this->assertSame('variation', $pricing['price_setup']);
        $this->assertTrue($pricing['variation_based_price']);
        $this->assertFalse($pricing['quantity_based_price']);
    }

    public function test_current_stock_is_kept_when_it_covers_the_minimum_order(): void
    {
        $result = json_decode($this->pricingResult('quantity'), true);
        $result['current_stock'] = 4200;

        $pricing = $this->response()->pricingSetup(json_encode($result), hasVariation: false);

        $this->assertSame(4200, $pricing['current_stock']);
    }

    public function test_missing_or_too_small_current_stock_falls_back_to_the_top_tier(): void
    {
        $result = json_decode($this->pricingResult('quantity'), true);
        $result['current_stock'] = 10;

        $this->assertSame(1000, $this->response()->pricingSetup(json_encode($result))['current_stock']);
        $this->assertSame(1000, $this->response()->pricingSetup($this->pricingResult('quantity'))['current_stock']);
    }

    public function test_both_price_setup_selects_variation_and_quantity_based_pricing(): void
    {
        $pricing = $this->response()->pricingSetup($this->pricingResult('both'));

        $this->assertSame('both', $pricing['price_setup']);
        $this->assertTrue($pricing['variation_based_price']);
        $this->assertTrue($pricing['quantity_based_price']);
    }

    public function test_product_without_variations_is_always_priced_by_quantity(): void
    {
        $pricing = $this->response()->pricingSetup($this->pricingResult('variation'), hasVariation: false);

        $this->assertSame('quantity', $pricing['price_setup']);
        $this->assertTrue($pricing['quantity_based_price']);
        $this->assertFalse($pricing['variation_based_price']);
    }

    public function test_unknown_price_setup_falls_back_to_quantity_based_pricing(): void
    {
        $pricing = $this->response()->pricingSetup($this->pricingResult('per-colour'));

        $this->assertSame('quantity', $pricing['price_setup']);
        $this->assertFalse($pricing['variation_based_price']);
    }

    public function test_pricing_prompt_lists_the_variations_and_asks_for_variation_pricing(): void
    {
        $prompt = (new PricingSetupTemplate($this->resourceService()))->build(
            context: 'Cotton tee',
            description: 'Bulk cotton t-shirts',
            options: ['has_variation' => true, 'variations' => ['Size: S, M, XL', 'Color: Black, White']],
        );

        $this->assertStringContainsString('Variations: Size: S, M, XL; Color: Black, White', $prompt);
        $this->assertStringContainsString('sold in the variations listed above', $prompt);
    }

    public function test_pricing_prompt_forces_quantity_pricing_without_variations(): void
    {
        $prompt = (new PricingSetupTemplate($this->resourceService()))->build(
            context: 'Cotton tee',
            description: 'Bulk cotton t-shirts',
            options: ['has_variation' => false],
        );

        $this->assertStringContainsString('"price_setup" must be "quantity"', $prompt);
        $this->assertStringNotContainsString('Variations:', $prompt);
    }

    public function test_faq_returns_one_entry_prioritised_after_the_existing_questions(): void
    {
        $faq = $this->response()->faq(
            json_encode(['question' => '<b>Is bulk packaging available?</b>', 'answer' => 'Yes, it ships in cartons sized for wholesale orders.']),
            existingQuestions: ['What is the minimum order?', 'Is it washable?'],
        );

        $this->assertSame([
            'question' => 'Is bulk packaging available?',
            'answer' => 'Yes, it ships in cartons sized for wholesale orders.',
            'priority' => 3,
            'translations' => [],
        ], $faq);
    }

    public function test_faq_returns_a_translation_for_every_requested_language(): void
    {
        $faq = $this->response()->faq(
            json_encode([
                'question' => 'Is bulk packaging available?',
                'answer' => 'Yes, it ships in cartons.',
                'translations' => [
                    'bn' => ['question' => '<i>বাল্ক প্যাকেজিং আছে?</i>', 'answer' => 'হ্যাঁ, কার্টনে পাঠানো হয়।'],
                    'ar' => ['question' => 'هل التغليف متاح؟', 'answer' => 'نعم.'],
                    'es' => ['question' => '¿Hay embalaje?', 'answer' => 'Sí.'],
                ],
            ]),
            translationLanguages: ['bn', 'ar'],
        );

        $this->assertSame([
            ['locale' => 'bn', 'question' => 'বাল্ক প্যাকেজিং আছে?', 'answer' => 'হ্যাঁ, কার্টনে পাঠানো হয়।'],
            ['locale' => 'ar', 'question' => 'هل التغليف متاح؟', 'answer' => 'نعم.'],
        ], $faq['translations']);
    }

    public function test_faq_leaves_out_a_missing_or_oversized_translation(): void
    {
        $faq = $this->response()->faq(
            json_encode([
                'question' => 'Is bulk packaging available?',
                'answer' => 'Yes, it ships in cartons.',
                'translations' => [
                    'bn' => ['question' => str_repeat('প', WholesaleAIResponse::FAQ_QUESTION_MAX_LENGTH + 1), 'answer' => 'হ্যাঁ।'],
                ],
            ]),
            translationLanguages: ['bn', 'ar'],
        );

        $this->assertSame('Is bulk packaging available?', $faq['question']);
        $this->assertSame([], $faq['translations']);
    }

    public function test_faq_prompt_asks_for_each_translation_language_by_code(): void
    {
        $prompt = (new FaqTemplate)->build(
            context: 'Cotton t-shirt',
            language: 'English',
            description: 'Plain cotton t-shirt.',
            options: ['translation_languages' => ['bn' => 'Bangla', 'ar' => 'Arabic']],
        );

        $this->assertStringContainsString('"bn" = Bangla, "ar" = Arabic', $prompt);
        $this->assertStringContainsString('"translations": {"bn": {"question": "…", "answer": "…"}, "ar":', $prompt);
    }

    public function test_faq_prompt_without_translation_languages_asks_for_no_translations(): void
    {
        $prompt = (new FaqTemplate)->build(context: 'Cotton t-shirt', language: 'English', description: 'Plain cotton t-shirt.');

        $this->assertStringNotContainsString('translations', $prompt);
    }

    public function test_faq_repeating_an_existing_question_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        $this->response()->faq(
            json_encode(['question' => 'what is the minimum order? ', 'answer' => 'Orders start at the minimum quantity.']),
            existingQuestions: ['What is the minimum order?'],
        );
    }

    public function test_faq_over_the_answer_limit_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        $this->response()->faq(json_encode([
            'question' => 'Is it washable?',
            'answer' => str_repeat('a', WholesaleAIResponse::FAQ_ANSWER_MAX_LENGTH + 1),
        ]));
    }

    public function test_faq_without_a_question_is_refused(): void
    {
        $this->expectException(ValidationException::class);

        $this->response()->faq(json_encode(['question' => '', 'answer' => 'Missing question.']));
    }
}
