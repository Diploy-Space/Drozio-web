<?php

namespace Modules\AI\app\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
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
use Modules\AI\app\Http\Requests\ProductPricingRequest;
use Modules\AI\app\Http\Requests\ProductSeoSectionAutoFillRequest;
use Modules\AI\app\Http\Requests\ProductTitleAutoFillRequest;
use Modules\AI\app\Http\Requests\ProductVariationSetupAutoFillRequest;
use Modules\AI\app\Http\Requests\Shared\Product\TitleSuggestionRequest;
use Modules\AI\app\Response\ProductResponse;
use Modules\AI\app\Services\AIContentGeneratorService;
use Modules\AI\app\Services\AIUsageManagerService;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class RetailProductController extends BaseApiController
{
    private const TAG = 'Retail Product AI';

    private const DEFAULT_LANG_CODE = 'en';

    private const ERROR_STATUS = [403, 422, 500];

    public function __construct(
        private readonly AIContentGeneratorService $contentGeneratorService,
        private readonly ProductResponse $productResponse,
        private readonly AIUsageManagerService $usageManagerService,
    ) {}

    #[ApiDoc(
        summary: 'Generate a retail product name',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function titleAutoFill(ProductTitleAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): string => $this->contentGeneratorService->generateContent(
            contentType: 'product_name',
            context: $request['name'],
            langCode: $request['langCode'] ?? self::DEFAULT_LANG_CODE,
        ));
    }

    #[ApiDoc(
        summary: 'Generate a retail product description',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function descriptionAutoFill(ProductDescriptionAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): string => $this->contentGeneratorService->generateContent(
            contentType: 'product_description',
            context: $request['name'],
            langCode: $request['langCode'] ?? self::DEFAULT_LANG_CODE,
        ));
    }

    #[ApiDoc(
        summary: 'Generate the general setup section of a retail product',
        description: 'Answers the resolved category, sub-category, brand and unit ids alongside the search tags.',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function generalSetupAutoFill(GeneralSetupRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => $this->productResponse->productGeneralSetupAutoFillFormat(
            result: $this->sectionContent('general_setup', $request),
        ));
    }

    #[ApiDoc(
        summary: 'Generate the pricing and others section of a retail product',
        description: 'Answers the unit price, minimum order quantity, current stock, discount, shipping cost and the tax or VAT options the store offers.',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function pricingAndOthersAutoFill(ProductPricingRequest $request): JsonResponse
    {
        return $this->generated(function () use ($request): array {
            $pricing = $this->productResponse->productPriceAndOthersAutoFill(
                result: $this->sectionContent('pricing_and_others', $request),
            );

            if ($pricing instanceof JsonResponse) {
                throw new InvalidArgumentException((string) $pricing->getData());
            }

            return $pricing;
        });
    }

    #[ApiDoc(
        summary: 'Generate the variation setup section of a retail product',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function variationSetupAutoFill(ProductVariationSetupAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => $this->productResponse->variationSetupAutoFill(
            result: $this->sectionContent('variation_setup', $request),
        )['data']);
    }

    #[ApiDoc(
        summary: 'Generate the SEO section of a retail product',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function seoSectionAutoFill(ProductSeoSectionAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => $this->productResponse->productSeoAutoFill(
            result: $this->sectionContent('seo_section', $request),
        ));
    }

    #[ApiDoc(
        summary: 'Suggest retail product names from keywords',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function generateProductTitleSuggestion(TitleSuggestionRequest $request): JsonResponse
    {
        return $this->generated(fn (): mixed => $this->productResponse->generateTitleSuggestions(
            result: $this->contentGeneratorService->generateContent(
                contentType: 'generate_product_title_suggestion',
                context: $request['keywords'],
                description: $request['description'],
            ),
        ));
    }

    #[ApiDoc(
        summary: 'Generate a retail product name from an uploaded image',
        tag: self::TAG,
        status: self::ERROR_STATUS,
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
        summary: 'Generations still available to the authenticated account',
        tag: self::TAG,
        status: [403],
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
