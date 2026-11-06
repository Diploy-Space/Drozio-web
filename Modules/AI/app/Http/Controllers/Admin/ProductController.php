<?php

namespace Modules\AI\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Modules\AI\app\Http\Requests\GeneralSetupRequest;
use Modules\AI\app\Http\Requests\GenerateProductTitleSuggestionRequest;
use Modules\AI\app\Http\Requests\GenerateTitleFromImageRequest;
use Modules\AI\app\Http\Requests\ProductDescriptionAutoFillRequest;
use Modules\AI\app\Http\Requests\ProductPricingRequest;
use Modules\AI\app\Http\Requests\ProductSeoSectionAutoFillRequest;
use Modules\AI\app\Http\Requests\ProductTitleAutoFillRequest;
use Modules\AI\app\Http\Requests\ProductVariationSetupAutoFillRequest;
use Modules\AI\app\Response\ProductResponse;
use Modules\AI\app\Services\New\AIContentGeneratorService;

class ProductController extends Controller
{
    private const FALLBACK_ERROR_STATUS = 500;

    public function __construct(
        private readonly AIContentGeneratorService $aiContentGeneratorService,
        private readonly ProductResponse $productResponse,
    ) {}

    public function titleAutoFill(ProductTitleAutoFillRequest $request): JsonResponse
    {
        return $this->respondToGeneration(fn (): string => $this->aiContentGeneratorService->generateContent(
            contentType: AIContentGeneratorService::CONTENT_PRODUCT_NAME,
            context: $request['name'],
            langCode: $request['langCode'],
        ));
    }

    public function descriptionAutoFill(ProductDescriptionAutoFillRequest $request): JsonResponse
    {
        return $this->respondToGeneration(fn (): string => $this->aiContentGeneratorService->generateContent(
            contentType: AIContentGeneratorService::CONTENT_PRODUCT_DESCRIPTION,
            context: $request['name'],
            langCode: $request['langCode'],
        ));
    }

    public function generalSetupAutoFill(GeneralSetupRequest $request): JsonResponse
    {
        return $this->respondToGeneration(fn (): array => $this->productResponse->productGeneralSetupAutoFillFormat(
            result: $this->generate(
                contentType: AIContentGeneratorService::CONTENT_GENERAL_SETUP,
                name: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    public function pricingAndOthersAutoFill(ProductPricingRequest $request): JsonResponse
    {
        return $this->respondToGeneration(fn (): mixed => $this->productResponse->productPriceAndOthersAutoFill(
            result: $this->generate(
                contentType: AIContentGeneratorService::CONTENT_PRICING_AND_OTHERS,
                name: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    public function productVariationSetupAutoFill(ProductVariationSetupAutoFillRequest $request): JsonResponse
    {
        return $this->respondToGeneration(fn (): array => $this->productResponse->variationSetupAutoFill(
            result: $this->generate(
                contentType: AIContentGeneratorService::CONTENT_VARIATION_SETUP,
                name: $request['name'],
                description: $request['description'],
            ),
        )['data']);
    }

    public function productSeoSectionAutoFill(ProductSeoSectionAutoFillRequest $request): JsonResponse
    {
        return $this->respondToGeneration(fn (): array => $this->productResponse->productSeoAutoFill(
            result: $this->generate(
                contentType: AIContentGeneratorService::CONTENT_SEO_SECTION,
                name: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    public function generateProductTitleSuggestion(GenerateProductTitleSuggestionRequest $request): JsonResponse
    {
        return $this->respondToGeneration(fn (): mixed => $this->productResponse->generateTitleSuggestions(
            result: $this->generate(
                contentType: AIContentGeneratorService::CONTENT_PRODUCT_TITLE_SUGGESTION,
                name: $request['keywords'],
                description: $request['description'],
            ),
        ));
    }

    public function generateTitleFromImages(GenerateTitleFromImageRequest $request): JsonResponse
    {
        return $this->respondToGeneration(function () use ($request): string {
            $image = $this->aiContentGeneratorService->getProductImagePath(image: $request->file('image'));

            $result = $this->aiContentGeneratorService->generateContent(
                contentType: AIContentGeneratorService::CONTENT_TITLE_FROM_IMAGE,
                imageUrl: $image['imageFullPath'],
            );

            $this->aiContentGeneratorService->deleteAiImage(
                imageName: $image['imageName'],
                type: AIContentGeneratorService::TYPE_PRODUCT,
            );

            return $result;
        });
    }

    private function generate(string $contentType, mixed $name, ?string $description): string
    {
        return $this->aiContentGeneratorService->generateContent(
            contentType: $contentType,
            context: $name,
            description: $description,
        );
    }

    private function respondToGeneration(callable $generate): JsonResponse
    {
        try {
            return $this->successResponse(data: $generate(), status: 200);
        } catch (Exception $exception) {
            return $this->errorResponse(
                message: $exception->getMessage(),
                status: $exception->getCode() > 0 ? $exception->getCode() : self::FALLBACK_ERROR_STATUS,
            );
        }
    }
}
