<?php

namespace Modules\AI\app\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Modules\AI\app\Http\Requests\Blog\BlogDescriptionRequest;
use Modules\AI\app\Http\Requests\Blog\BlogSeoSectionRequest;
use Modules\AI\app\Http\Requests\Blog\BlogTitleRequest;
use Modules\AI\app\Http\Requests\Blog\BlogTitleSuggestionRequest;
use Modules\AI\app\Http\Requests\Blog\GenerateBlogTitleFromImageRequest;
use Modules\AI\app\Services\New\AIContentGeneratorService;

class BlogController extends Controller
{
    private const FALLBACK_ERROR_STATUS = 500;

    public function __construct(
        private readonly AIContentGeneratorService $aiContentGeneratorService,
    ) {
        parent::__construct();
    }

    public function titleAutoFill(BlogTitleRequest $request): JsonResponse
    {
        return $this->generated(fn (): mixed => json_decode($this->aiContentGeneratorService->generateContent(
            contentType: AIContentGeneratorService::CONTENT_BLOG_TITLE,
            context: $request['title'],
            langCode: $request['langCode'],
        ), true));
    }

    public function descriptionAutoFill(BlogDescriptionRequest $request): JsonResponse
    {
        return $this->generated(fn (): mixed => $this->aiContentGeneratorService->generateContent(
            contentType: AIContentGeneratorService::CONTENT_BLOG_DESCRIPTION,
            context: $request['title'],
            langCode: $request['langCode'],
        ));
    }

    public function seoSectionAutoFill(BlogSeoSectionRequest $request): JsonResponse
    {
        return $this->generated(fn (): mixed => json_decode($this->aiContentGeneratorService->generateContent(
            contentType: AIContentGeneratorService::CONTENT_BLOG_SEO_SECTION,
            context: $request['title'],
            description: $request['description'],
        ), true));
    }

    public function generateBlogTitleSuggestion(BlogTitleSuggestionRequest $request): JsonResponse
    {
        return $this->generated(fn (): mixed => json_decode($this->aiContentGeneratorService->generateContent(
            contentType: AIContentGeneratorService::CONTENT_BLOG_TITLE_SUGGESTION,
            context: $request['keywords'],
            description: $request['description'],
        ), true));
    }

    public function generateBlogTitleFromImages(GenerateBlogTitleFromImageRequest $request): JsonResponse
    {
        return $this->generated(function () use ($request): mixed {
            $image = $this->aiContentGeneratorService->getBlogImagePath($request->file('image'));

            $title = $this->aiContentGeneratorService->generateContent(
                contentType: AIContentGeneratorService::CONTENT_BLOG_TITLE_FROM_IMAGE,
                description: $request['description'],
                imageUrl: $image['imageFullPath'],
            );

            $this->aiContentGeneratorService->deleteAiImage($image['imageName'], AIContentGeneratorService::TYPE_BLOG);

            return $title;
        });
    }

    private function generated(callable $generate): JsonResponse
    {
        try {
            return $this->successResponse(data: $generate());
        } catch (Exception $exception) {
            return $this->errorResponse(
                message: $exception->getMessage(),
                status: $exception->getCode() > 0 ? $exception->getCode() : self::FALLBACK_ERROR_STATUS,
            );
        }
    }
}
