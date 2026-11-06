<?php

namespace Modules\AI\app\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Services\New\ProductRetailService;
use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Modules\AI\app\Exceptions\ImageValidationException;
use Modules\AI\app\Exceptions\UsageLimitException;
use Modules\AI\app\Exceptions\ValidationException;
use Modules\AI\app\Http\Requests\GeneralSetupRequest;
use Modules\AI\app\Http\Requests\GenerateTitleFromImageRequest;
use Modules\AI\app\Http\Requests\ProductDescriptionAutoFillRequest;
use Modules\AI\app\Http\Requests\ProductSeoSectionAutoFillRequest;
use Modules\AI\app\Http\Requests\ProductTitleAutoFillRequest;
use Modules\AI\app\Http\Requests\ProductVariationSetupAutoFillRequest;
use Modules\AI\app\Http\Requests\Shared\Product\TitleSuggestionRequest;
use Modules\AI\app\Http\Requests\Shared\WholesaleProduct\FaqAutoFillRequest;
use Modules\AI\app\Http\Requests\Shared\WholesaleProduct\PricingSetupAutoFillRequest;
use Modules\AI\app\Response\WholesaleAIResponse;
use Modules\AI\app\Services\AIContentGeneratorService;
use Modules\AI\app\Services\AIUsageManagerService;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class WholesaleProductController extends BaseApiController
{
    private const TAG = 'Product AI (Seller)';

    public function __construct(
        private readonly AIContentGeneratorService $contentGeneratorService,
        private readonly WholesaleAIResponse $wholesaleResponse,
        private readonly AIUsageManagerService $usageManagerService,
        private readonly ProductRetailService $productService,
    ) {}

    #[ApiDoc(
        summary: 'Generate a wholesale product name',
        tag: self::TAG,
        status: [403, 422, 500],
        guard: 'seller',
    )]
    public function titleAutoFill(ProductTitleAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): string => $this->contentGeneratorService->generateContent(
            contentType: 'product_name',
            context: $request['name'],
            langCode: $request['langCode'] ?? 'en',
        ));
    }

    #[ApiDoc(
        summary: 'Generate a wholesale product description',
        tag: self::TAG,
        status: [403, 422, 500],
        guard: 'seller',
    )]
    public function descriptionAutoFill(ProductDescriptionAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): string => $this->contentGeneratorService->generateContent(
            contentType: 'product_description',
            context: $request['name'],
            langCode: $request['langCode'] ?? 'en',
        ));
    }

    #[ApiDoc(
        summary: 'Generate the general setup section of a wholesale product',
        description: 'Answers the resolved category, sub-category, brand and unit ids alongside a unique product SKU (code), the reorder level, shipping cost and search tags. Search tags are always written in the default product language, whatever language the name and description are in.',
        tag: self::TAG,
        status: [403, 422, 500],
        guard: 'seller',
    )]
    public function generalSetupAutoFill(GeneralSetupRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => [
            ...$this->wholesaleResponse->generalSetup(
                result: $this->contentGeneratorService->generateContent(
                    contentType: 'wholesale_general_setup',
                    context: $request['name'],
                    langCode: $this->productService->getDefaultContentLanguage(),
                    description: $request['description'],
                ),
            ),
            'code' => $this->productService->getUniqueCode(),
        ]);
    }

    #[ApiDoc(
        summary: 'Generate the variation setup section of a wholesale product',
        tag: self::TAG,
        status: [403, 422, 500],
        guard: 'seller',
    )]
    public function variationSetupAutoFill(ProductVariationSetupAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => $this->wholesaleResponse->variationSetup(
            result: $this->sectionContent('wholesale_variation_setup', $request),
        )['data']);
    }

    #[ApiDoc(
        summary: 'Generate the pricing setup section of a wholesale product',
        description: 'Answers a gapless quantity tier ladder with the minimum order quantity, current stock and discount the tiers were built against, plus the price setup mode and its quantity_based_price / variation_based_price flags. Send has_variation and variations (e.g. "Size: S, M, L") from the variation setup so variation-based pricing can be chosen; has_variation=0 forces quantity-based pricing.',
        tag: self::TAG,
        status: [403, 422, 500],
        guard: 'seller',
    )]
    public function pricingSetupAutoFill(PricingSetupAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => $this->wholesaleResponse->pricingSetup(
            result: $this->contentGeneratorService->generateContent(
                contentType: 'wholesale_pricing_setup',
                context: $request['name'],
                description: $request['description'],
                options: ['has_variation' => $request->hasVariation(), 'variations' => $request->variations()],
            ),
            hasVariation: $request->hasVariation(),
        ));
    }

    #[ApiDoc(
        summary: 'Generate one FAQ for a wholesale product',
        description: 'Answers a single question, answer and priority entry sized to the wholesale FAQ limits, written in langCode. Send existing_questions[] with the questions already added so the new FAQ never repeats one; priority follows their count. Send translation_languages[] with the other language codes of the product form to also get translations[] — one {locale, question, answer} per language, ready for faq[i][lang][] / question[] / answer[]; a translation that comes back empty or over the limits is left out.',
        tag: self::TAG,
        status: [403, 422, 500],
        guard: 'seller',
    )]
    public function faqAutoFill(FaqAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => $this->wholesaleResponse->faq(
            result: $this->contentGeneratorService->generateContent(
                contentType: 'wholesale_faq',
                context: $request['name'],
                langCode: $request['langCode'] ?? 'en',
                description: $request['description'],
                options: ['existing_questions' => $request->existingQuestions(), 'translation_languages' => $request->translationLanguages()],
            ),
            existingQuestions: $request->existingQuestions(),
            translationLanguages: $request->translationLanguages(),
        ));
    }

    #[ApiDoc(
        summary: 'Generate the SEO section of a wholesale product',
        tag: self::TAG,
        status: [403, 422, 500],
        guard: 'seller',
    )]
    public function seoSectionAutoFill(ProductSeoSectionAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => $this->wholesaleResponse->seoSection(
            result: $this->sectionContent('seo_section', $request),
        ));
    }

    #[ApiDoc(
        summary: 'Suggest wholesale product names from keywords',
        tag: self::TAG,
        status: [403, 422, 500],
        guard: 'seller',
    )]
    public function generateProductTitleSuggestion(TitleSuggestionRequest $request): JsonResponse
    {
        return $this->generated(fn (): mixed => json_decode($this->contentGeneratorService->generateContent(
            contentType: 'generate_product_title_suggestion',
            context: $request['keywords'],
            description: $request['description'],
        ), true));
    }

    #[ApiDoc(
        summary: 'Generate a wholesale product name from an uploaded image',
        tag: self::TAG,
        status: [403, 422, 500],
        guard: 'seller',
    )]
    public function generateTitleFromImages(GenerateTitleFromImageRequest $request): JsonResponse
    {
        return $this->generated(function () use ($request): string {
            $imagePath = $this->contentGeneratorService->getAnalyizeImagePath($request->file('image'));

            $content = $this->contentGeneratorService->generateContent(
                contentType: 'generate_title_from_image',
                imageUrl: $imagePath['imageFullPath'],
            );

            $this->contentGeneratorService->deleteAiImage($imagePath['imageName'], 'product');

            return $content;
        });
    }

    #[ApiDoc(
        summary: 'Generations still available to the authenticated seller',
        tag: self::TAG,
        status: [403],
        guard: 'seller',
    )]
    public function generateLimitCheck(): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['remaining_count' => $this->usageManagerService->getGenerateRemainingCount()],
        );
    }

    private function sectionContent(string $contentType, mixed $request): string
    {
        return $this->contentGeneratorService->generateContent(
            contentType: $contentType,
            context: $request['name'],
            description: $request['description'],
        );
    }

    private function generated(Closure $generate): JsonResponse
    {
        try {
            return $this->responseFormatter(
                config: config('response.data_fetched'),
                content: [
                    'data' => $generate(),
                    'remaining_count' => $this->usageManagerService->getGenerateRemainingCount(),
                ],
            );
        } catch (Exception $exception) {
            return $this->generationFailure($exception);
        }
    }

    private function generationFailure(Exception $exception): JsonResponse
    {
        $config = match (true) {
            $exception instanceof UsageLimitException => config('response.ai_generate_limit_reached'),
            $exception instanceof ValidationException,
            $exception instanceof ImageValidationException,
            $exception instanceof InvalidArgumentException => config('response.ai_generation_incomplete'),
            default => config('response.ai_generation_failed'),
        };

        $response = $this->responseFormatter(
            config: $config,
            errors: [['error_code' => $config['identical_code'], 'message' => $exception->getMessage()]],
        );

        return $response->setData([...$response->getData(true), 'message' => $exception->getMessage()]);
    }
}
