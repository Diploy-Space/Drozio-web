<?php

namespace Modules\AI\app\Http\Controllers\RestAPI\v6\Shared;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use Modules\AI\app\Contracts\AuctionAIContract;
use Modules\AI\app\Exceptions\ImageValidationException;
use Modules\AI\app\Exceptions\UsageLimitException;
use Modules\AI\app\Exceptions\ValidationException;
use Modules\AI\app\Http\Requests\GenerateTitleFromImageRequest;
use Modules\AI\app\Http\Requests\Shared\AuctionProduct\DescriptionAutoFillRequest;
use Modules\AI\app\Http\Requests\Shared\AuctionProduct\GeneralSetupAutoFillRequest;
use Modules\AI\app\Http\Requests\Shared\AuctionProduct\InfoAutoFillRequest;
use Modules\AI\app\Http\Requests\Shared\AuctionProduct\SeoSectionAutoFillRequest;
use Modules\AI\app\Http\Requests\Shared\AuctionProduct\SetupAutoFillRequest;
use Modules\AI\app\Http\Requests\Shared\AuctionProduct\ShippingPolicyAutoFillRequest;
use Modules\AI\app\Http\Requests\Shared\AuctionProduct\TitleAutoFillRequest;
use Modules\AI\app\Http\Requests\Shared\Product\TitleSuggestionRequest;
use Modules\AI\app\Services\AIUsageManagerService;
use Modules\AI\app\Services\New\AIContentGeneratorService;
use Modules\AI\app\Services\New\AuctionAIResponseParser;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class AuctionProductController extends BaseApiController
{
    private const TAG = 'Auction AI';

    private const DEFAULT_LANG_CODE = 'en';

    private const ERROR_STATUS = [403, 422, 500];

    public function __construct(
        private readonly AuctionAIContract $auctionAIService,
        private readonly AuctionAIResponseParser $auctionAIResponseParser,
        private readonly AIContentGeneratorService $contentGeneratorService,
        private readonly AIUsageManagerService $usageManagerService,
    ) {}

    #[ApiDoc(
        summary: 'Generate an auction product name',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function titleAutoFill(TitleAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): string => $this->auctionAIResponseParser->parseTitle(
            result: $this->auctionAIService->generateProductName(
                title: $request['name'],
                langCode: $request['langCode'] ?? self::DEFAULT_LANG_CODE,
            ),
        ));
    }

    #[ApiDoc(
        summary: 'Generate an auction product description',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function descriptionAutoFill(DescriptionAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): string => $this->auctionAIResponseParser->parseDescription(
            result: $this->auctionAIService->generateProductDescription(
                title: $request['name'],
                langCode: $request['langCode'] ?? self::DEFAULT_LANG_CODE,
            ),
        ));
    }

    #[ApiDoc(
        summary: 'Generate the general setup section of an auction product',
        description: 'Answers the resolved category, sub-category, brand and unit ids alongside the search tags.',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function generalSetupAutoFill(GeneralSetupAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => $this->auctionAIResponseParser->parseGeneralSetup(
            result: $this->auctionAIService->generateGeneralSetup(
                title: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    #[ApiDoc(
        summary: 'Generate the shipping policy section of an auction product',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function shippingPolicyAutoFill(ShippingPolicyAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => $this->auctionAIResponseParser->parseShippingPolicy(
            result: $this->auctionAIService->generateShippingPolicy(
                title: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    #[ApiDoc(
        summary: 'Generate the auction information section of an auction product',
        description: 'Answers the entry fee, starting price, bid increment and decrement bounds, the resolved tax ids and the auction window.',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function auctionInfoAutoFill(InfoAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => $this->auctionAIResponseParser->parseAuctionInfo(
            result: $this->auctionAIService->generateAuctionInfo(
                title: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    #[ApiDoc(
        summary: 'Generate the SEO section of an auction product',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function seoSectionAutoFill(SeoSectionAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => $this->auctionAIResponseParser->parseSeo(
            result: $this->auctionAIService->generateSeoContent(
                title: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    #[ApiDoc(
        summary: 'Generate an auction product name from an uploaded image',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function generateTitleFromImages(GenerateTitleFromImageRequest $request): JsonResponse
    {
        return $this->generated(function () use ($request): string {
            $imagePath = $this->contentGeneratorService->getAuctionImagePath($request->file('image'));

            $title = $this->auctionAIResponseParser->parseTitle(
                result: $this->auctionAIService->generateProductNameFromImage(
                    imageUrl: $imagePath['imageFullPath'],
                ),
            );

            $this->contentGeneratorService->deleteAiImage(
                $imagePath['imageName'],
                AIContentGeneratorService::TYPE_AUCTION,
            );

            return $title;
        });
    }

    #[ApiDoc(
        summary: 'Suggest auction product names from keywords',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function generateProductTitleSuggestion(TitleSuggestionRequest $request): JsonResponse
    {
        return $this->generated(fn (): mixed => json_decode($this->contentGeneratorService->generateContent(
            contentType: AIContentGeneratorService::CONTENT_PRODUCT_TITLE_SUGGESTION,
            context: $request['keywords'],
            description: $request['description'],
        ), true));
    }

    #[ApiDoc(
        summary: 'Generate the auction setup suggestion of an auction product',
        tag: self::TAG,
        status: self::ERROR_STATUS,
    )]
    public function setupAutoFill(SetupAutoFillRequest $request): JsonResponse
    {
        return $this->generated(fn (): array => $this->auctionAIResponseParser->parseSetup(
            result: $this->auctionAIService->generateSetupSuggestion(
                title: $request['name'],
                description: $request['description'],
            ),
        ));
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
