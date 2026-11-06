<?php

namespace Modules\AI\app\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\Model;
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
use Modules\AI\app\Services\New\AISettingLogService;
use Modules\AI\app\Services\New\AISettingService;
use Modules\AI\app\Utils\CurrentAuthUser;

class ProductController extends Controller
{
    private const SERVER_ERROR_STATUS = 500;

    private const MIN_HTTP_STATUS = 100;

    private const MAX_HTTP_STATUS = 599;

    private const DEMO_GENERATE_LIMIT = 10;

    public function __construct(
        private readonly AIContentGeneratorService $aiContentGeneratorService,
        private readonly ProductResponse $productResponse,
        private readonly AISettingService $aiSettingService,
        private readonly AISettingLogService $aiSettingLogService,
    ) {}

    public function titleAutoFill(ProductTitleAutoFillRequest $request): JsonResponse
    {
        return $this->generatedResponse(fn () => $this->aiContentGeneratorService->generateContent(
            contentType: AIContentGeneratorService::CONTENT_PRODUCT_NAME,
            context: $request['name'],
            langCode: $request['langCode'],
        ));
    }

    public function descriptionAutoFill(ProductDescriptionAutoFillRequest $request): JsonResponse
    {
        return $this->generatedResponse(fn () => $this->aiContentGeneratorService->generateContent(
            contentType: AIContentGeneratorService::CONTENT_PRODUCT_DESCRIPTION,
            context: $request['name'],
            langCode: $request['langCode'],
        ));
    }

    public function generalSetupAutoFill(GeneralSetupRequest $request): JsonResponse
    {
        return $this->generatedResponse(fn () => $this->productResponse->productGeneralSetupAutoFillFormat(
            result: $this->aiContentGeneratorService->generateContent(
                contentType: AIContentGeneratorService::CONTENT_GENERAL_SETUP,
                context: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    public function pricingAndOthersAutoFill(ProductPricingRequest $request): JsonResponse
    {
        return $this->generatedResponse(fn () => $this->productResponse->productPriceAndOthersAutoFill(
            result: $this->aiContentGeneratorService->generateContent(
                contentType: AIContentGeneratorService::CONTENT_PRICING_AND_OTHERS,
                context: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    public function productVariationSetupAutoFill(ProductVariationSetupAutoFillRequest $request): JsonResponse
    {
        return $this->generatedResponse(fn () => $this->productResponse->variationSetupAutoFill(
            result: $this->aiContentGeneratorService->generateContent(
                contentType: AIContentGeneratorService::CONTENT_VARIATION_SETUP,
                context: $request['name'],
                description: $request['description'],
            ),
        )['data']);
    }

    public function productSeoSectionAutoFill(ProductSeoSectionAutoFillRequest $request): JsonResponse
    {
        return $this->generatedResponse(fn () => $this->productResponse->productSeoAutoFill(
            result: $this->aiContentGeneratorService->generateContent(
                contentType: AIContentGeneratorService::CONTENT_SEO_SECTION,
                context: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    public function generateProductTitleSuggestion(GenerateProductTitleSuggestionRequest $request): JsonResponse
    {
        return $this->generatedResponse(fn () => $this->productResponse->generateTitleSuggestions(
            result: $this->aiContentGeneratorService->generateContent(
                contentType: AIContentGeneratorService::CONTENT_PRODUCT_TITLE_SUGGESTION,
                context: $request['keywords'],
                description: $request['description'],
            ),
        ));
    }

    public function generateTitleFromImages(GenerateTitleFromImageRequest $request): JsonResponse
    {
        return $this->generatedResponse(function () use ($request): string {
            $imagePath = $this->aiContentGeneratorService->getAnalyizeImagePath($request->file('image'));

            $content = $this->aiContentGeneratorService->generateContent(
                contentType: AIContentGeneratorService::CONTENT_TITLE_FROM_IMAGE,
                imageUrl: $imagePath['imageFullPath'],
            );

            $this->aiContentGeneratorService->deleteAiImage($imagePath['imageName'], AIContentGeneratorService::TYPE_PRODUCT);

            return $content;
        });
    }

    private function generatedResponse(callable $generate): JsonResponse
    {
        try {
            return $this->successResponse(data: [
                'data' => $generate(),
                'remaining_count' => $this->remainingGenerateCount(),
            ], status: 200);
        } catch (Exception $exception) {
            return $this->errorResponse(
                message: $exception->getMessage(),
                status: $this->responseStatus($exception),
            );
        }
    }

    private function responseStatus(Exception $exception): int
    {
        $code = (int) $exception->getCode();

        return $code >= self::MIN_HTTP_STATUS && $code <= self::MAX_HTTP_STATUS ? $code : self::SERVER_ERROR_STATUS;
    }

    private function remainingGenerateCount(): int
    {
        $actorId = CurrentAuthUser::isAdmin() ? null : CurrentAuthUser::id();
        $provider = $actorId ? $this->aiSettingService->findBy(params: []) : null;

        if ($provider === null) {
            return 0;
        }

        $generateLimit = $this->textGenerateLimit(provider: $provider);
        $log = $this->aiSettingLogService->findBy(params: ['seller_id' => $actorId]);

        if ($log === null) {
            return $generateLimit;
        }

        return $generateLimit > 0 && $log->total_generated_count < $generateLimit
            ? $generateLimit - $log->total_generated_count
            : 0;
    }

    private function textGenerateLimit(Model $provider): int
    {
        if (config('app.mode') === 'demo') {
            return self::DEMO_GENERATE_LIMIT;
        }

        return (int) (CurrentAuthUser::isCustomer()
            ? ($provider->customer_generate_limit ?? 0)
            : ($provider->generate_limit ?? 0));
    }
}
