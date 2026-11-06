<?php

namespace Modules\AI\app\Http\Controllers\API\V3;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Modules\AI\app\Contracts\AuctionAIContract;
use Modules\AI\app\Http\Requests\ApiRequests\AuctionDescriptionAutoFillRequest;
use Modules\AI\app\Http\Requests\ApiRequests\AuctionGeneralSetupAutoFillRequest;
use Modules\AI\app\Http\Requests\ApiRequests\AuctionInfoAutoFillRequest;
use Modules\AI\app\Http\Requests\ApiRequests\AuctionSeoSectionAutoFillRequest;
use Modules\AI\app\Http\Requests\ApiRequests\AuctionShippingPolicyAutoFillRequest;
use Modules\AI\app\Http\Requests\ApiRequests\AuctionSetupAutoFillRequest;
use Modules\AI\app\Http\Requests\ApiRequests\AuctionTitleAutoFillRequest;
use Modules\AI\app\Http\Requests\ApiRequests\GenerateTitleFromImageRequest;
use Modules\AI\app\Http\Requests\GenerateProductTitleSuggestionRequest;
use Modules\AI\app\Services\New\AuctionAIResponseParser;
use Modules\AI\app\Services\New\AIContentGeneratorService;
use Modules\AI\app\Services\New\AISettingLogService;
use Modules\AI\app\Services\New\AISettingService;
use Modules\AI\app\Utils\CurrentAuthUser;

class AuctionProductController extends Controller
{
    private const DEFAULT_LANG_CODE = 'en';

    private const SERVER_ERROR_STATUS = 500;

    private const MIN_HTTP_STATUS = 100;

    private const MAX_HTTP_STATUS = 599;

    private const DEMO_GENERATE_LIMIT = 10;

    public function __construct(
        private readonly AuctionAIContract $auctionAIService,
        private readonly AuctionAIResponseParser $auctionAIResponseParser,
        private readonly AIContentGeneratorService $aiContentGeneratorService,
        private readonly AISettingService $aiSettingService,
        private readonly AISettingLogService $aiSettingLogService,
    ) {
        parent::__construct();
    }

    public function titleAutoFill(AuctionTitleAutoFillRequest $request): JsonResponse
    {
        return $this->sectionResponse(fn (): mixed => $this->auctionAIResponseParser->parseTitle(
            $this->auctionAIService->generateProductName(
                title: $request['name'],
                langCode: $request['langCode'] ?? self::DEFAULT_LANG_CODE,
            ),
        ));
    }

    public function descriptionAutoFill(AuctionDescriptionAutoFillRequest $request): JsonResponse
    {
        return $this->sectionResponse(fn (): mixed => $this->auctionAIResponseParser->parseDescription(
            $this->auctionAIService->generateProductDescription(
                title: $request['name'],
                langCode: $request['langCode'] ?? self::DEFAULT_LANG_CODE,
            ),
        ));
    }

    public function generalSetupAutoFill(AuctionGeneralSetupAutoFillRequest $request): JsonResponse
    {
        return $this->sectionResponse(fn (): mixed => $this->auctionAIResponseParser->parseGeneralSetup(
            $this->auctionAIService->generateGeneralSetup(
                title: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    public function shippingPolicyAutoFill(AuctionShippingPolicyAutoFillRequest $request): JsonResponse
    {
        return $this->sectionResponse(fn (): mixed => $this->auctionAIResponseParser->parseShippingPolicy(
            $this->auctionAIService->generateShippingPolicy(
                title: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    public function auctionInfoAutoFill(AuctionInfoAutoFillRequest $request): JsonResponse
    {
        return $this->sectionResponse(fn (): mixed => $this->auctionInfo(
            title: $request['name'],
            description: $request['description'],
        ));
    }

    public function seoSectionAutoFill(AuctionSeoSectionAutoFillRequest $request): JsonResponse
    {
        return $this->sectionResponse(fn (): mixed => $this->auctionAIResponseParser->parseSeo(
            $this->auctionAIService->generateSeoContent(
                title: $request['name'],
                description: $request['description'],
            ),
        ));
    }

    public function generateTitleFromImages(GenerateTitleFromImageRequest $request): JsonResponse
    {
        return $this->sectionResponse(function () use ($request): mixed {
            $imagePath = $this->aiContentGeneratorService->getAuctionImagePath($request->file('image'));

            $result = $this->auctionAIService->generateProductNameFromImage(
                imageUrl: $imagePath['imageFullPath'],
            );

            $this->aiContentGeneratorService->deleteAiImage(
                $imagePath['imageName'],
                AIContentGeneratorService::TYPE_AUCTION,
            );

            return $this->auctionAIResponseParser->parseTitle($result);
        });
    }

    public function generateProductTitleSuggestion(GenerateProductTitleSuggestionRequest $request): JsonResponse
    {
        return $this->sectionResponse(fn (): mixed => json_decode($this->aiContentGeneratorService->generateContent(
            contentType: AIContentGeneratorService::CONTENT_PRODUCT_TITLE_SUGGESTION,
            context: $request['keywords'],
            description: $request['description'] ?? null,
        ), true));
    }

    public function setupAutoFill(AuctionSetupAutoFillRequest $request): JsonResponse
    {
        return $this->sectionResponse(fn (): mixed => $this->auctionInfo(
            title: $request['name'],
            description: $request['description'],
        ));
    }

    public function generateLimitCheck(): JsonResponse
    {
        try {
            return $this->successResponse(data: $this->remainingGenerateCount());
        } catch (Exception $exception) {
            return $this->errorResponse(
                message: $exception->getMessage(),
                status: $this->responseStatus($exception),
            );
        }
    }

    private function auctionInfo(string $title, ?string $description = null): mixed
    {
        return $this->auctionAIResponseParser->parseAuctionInfo(
            $this->auctionAIService->generateAuctionInfo(title: $title, description: $description),
        );
    }

    private function sectionResponse(callable $generate): JsonResponse
    {
        try {
            return $this->successResponse(data: [
                'data' => $generate(),
                'remaining_count' => $this->remainingGenerateCount(),
            ]);
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
