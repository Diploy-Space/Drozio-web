<?php

namespace Modules\AI\app\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\New\ProductRetailService;
use Exception;
use Illuminate\Http\JsonResponse;
use Modules\AI\app\Http\Requests\GeneralSetupRequest;
use Modules\AI\app\Http\Requests\GenerateProductTitleSuggestionRequest;
use Modules\AI\app\Http\Requests\GenerateTitleFromImageRequest;
use Modules\AI\app\Http\Requests\ProductDescriptionAutoFillRequest;
use Modules\AI\app\Http\Requests\ProductSeoSectionAutoFillRequest;
use Modules\AI\app\Http\Requests\ProductTitleAutoFillRequest;
use Modules\AI\app\Http\Requests\ProductVariationSetupAutoFillRequest;
use Modules\AI\app\Http\Requests\Shared\WholesaleProduct\FaqAutoFillRequest;
use Modules\AI\app\Http\Requests\Shared\WholesaleProduct\PricingSetupAutoFillRequest;
use Modules\AI\app\Response\WholesaleAIResponse;
use Modules\AI\app\Services\AIContentGeneratorService;

class WholesaleProductController extends Controller
{
    public function __construct(
        protected AIContentGeneratorService $aiContentGeneratorService,
        protected WholesaleAIResponse $wholesaleAIResponse,
        protected ProductRetailService $productService,
    ) {
        parent::__construct();
    }

    public function titleAutoFill(ProductTitleAutoFillRequest $request): JsonResponse
    {
        try {
            $result = $this->aiContentGeneratorService->generateContent(contentType: 'product_name', context: $request['name'], langCode: $request['langCode']);

            return $this->successResponse(data: $result, status: 200);
        } catch (Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), status: $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }

    public function descriptionAutoFill(ProductDescriptionAutoFillRequest $request): JsonResponse
    {
        try {
            $result = $this->aiContentGeneratorService->generateContent(contentType: 'product_description', context: $request['name'], langCode: $request['langCode']);

            return $this->successResponse(data: $result, status: 200);
        } catch (Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), status: $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }

    public function generalSetupAutoFill(GeneralSetupRequest $request): JsonResponse
    {
        try {
            $result = $this->aiContentGeneratorService->generateContent(contentType: 'wholesale_general_setup', context: $request['name'], langCode: $this->productService->getDefaultContentLanguage(), description: $request['description']);

            return $this->successResponse(data: [
                ...$this->wholesaleAIResponse->generalSetup(result: $result),
                'code' => $this->productService->getUniqueCode(),
            ], status: 200);
        } catch (Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), status: $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }

    public function variationSetupAutoFill(ProductVariationSetupAutoFillRequest $request): JsonResponse
    {
        try {
            $result = $this->aiContentGeneratorService->generateContent(contentType: 'wholesale_variation_setup', context: $request['name'], description: $request['description']);

            return $this->successResponse(data: $this->wholesaleAIResponse->variationSetup(result: $result)['data'], status: 200);
        } catch (Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), status: $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }

    public function pricingSetupAutoFill(PricingSetupAutoFillRequest $request): JsonResponse
    {
        try {
            $result = $this->aiContentGeneratorService->generateContent(
                contentType: 'wholesale_pricing_setup',
                context: $request['name'],
                description: $request['description'],
                options: ['has_variation' => $request->hasVariation(), 'variations' => $request->variations()],
            );

            return $this->successResponse(data: $this->wholesaleAIResponse->pricingSetup(result: $result, hasVariation: $request->hasVariation()), status: 200);
        } catch (Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), status: $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }

    public function faqAutoFill(FaqAutoFillRequest $request): JsonResponse
    {
        try {
            $result = $this->aiContentGeneratorService->generateContent(
                contentType: 'wholesale_faq',
                context: $request['name'],
                langCode: $request['langCode'] ?? 'en',
                description: $request['description'],
                options: ['existing_questions' => $request->existingQuestions(), 'translation_languages' => $request->translationLanguages()],
            );

            return $this->successResponse(data: $this->wholesaleAIResponse->faq(result: $result, existingQuestions: $request->existingQuestions(), translationLanguages: $request->translationLanguages()), status: 200);
        } catch (Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), status: $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }

    public function seoSectionAutoFill(ProductSeoSectionAutoFillRequest $request): JsonResponse
    {
        try {
            $result = $this->aiContentGeneratorService->generateContent(contentType: 'seo_section', context: $request['name'], description: $request['description']);

            return $this->successResponse(data: $this->wholesaleAIResponse->seoSection(result: $result), status: 200);
        } catch (Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), status: $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }

    public function generateProductTitleSuggestion(GenerateProductTitleSuggestionRequest $request): JsonResponse
    {
        try {
            $result = $this->aiContentGeneratorService->generateContent(contentType: 'generate_product_title_suggestion', context: $request['keywords'], description: $request['description']);

            return $this->successResponse(data: json_decode($result, true), status: 200);
        } catch (Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), status: $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }

    public function generateTitleFromImages(GenerateTitleFromImageRequest $request): JsonResponse
    {
        try {
            $imagePath = $this->aiContentGeneratorService->getAnalyizeImagePath($request->file('image'));
            $result = $this->aiContentGeneratorService->generateContent(contentType: 'generate_title_from_image', imageUrl: $imagePath['imageFullPath']);
            $this->aiContentGeneratorService->deleteAiImage($imagePath['imageName'], 'product');

            return $this->successResponse(data: $result, status: 200);
        } catch (Exception $e) {
            return $this->errorResponse(message: $e->getMessage(), status: $e->getCode() > 0 ? $e->getCode() : 500);
        }
    }
}
