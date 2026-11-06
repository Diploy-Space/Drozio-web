<?php

namespace Modules\AI\app\Services\New;

use App\Services\New\LanguageService;
use App\Traits\FileManagerTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\AI\AIProviders\AIProviderManager;
use Modules\AI\AIProviders\ClaudeProvider;
use Modules\AI\AIProviders\OpenAIProvider;
use Modules\AI\app\PromptTemplates\Auction\AuctionDescriptionTemplate;
use Modules\AI\app\PromptTemplates\Auction\AuctionGeneralSetupTemplate;
use Modules\AI\app\PromptTemplates\Auction\AuctionInfoTemplate;
use Modules\AI\app\PromptTemplates\Auction\AuctionSeoSectionTemplate;
use Modules\AI\app\PromptTemplates\Auction\AuctionSetupTemplate;
use Modules\AI\app\PromptTemplates\Auction\AuctionShippingPolicyTemplate;
use Modules\AI\app\PromptTemplates\Auction\AuctionTitleTemplate;
use Modules\AI\app\PromptTemplates\Blog\BlogSeoSectionTemplate;
use Modules\AI\app\PromptTemplates\Blog\DescriptionTemplate;
use Modules\AI\app\PromptTemplates\Blog\GenerateBlogTitleFromImageTemplate;
use Modules\AI\app\PromptTemplates\Blog\TitleSuggestionTemplate;
use Modules\AI\app\PromptTemplates\Blog\TitleTemplate;
use Modules\AI\app\PromptTemplates\GeneralSetupTemplates;
use Modules\AI\app\PromptTemplates\GenerateProductTitleSuggestionTemplate;
use Modules\AI\app\PromptTemplates\GenerateTitleFromImageTemplate;
use Modules\AI\app\PromptTemplates\PricingTemplate;
use Modules\AI\app\PromptTemplates\ProductDescriptionTemplate;
use Modules\AI\app\PromptTemplates\ProductNameTemplate;
use Modules\AI\app\PromptTemplates\ProductVariationSetup;
use Modules\AI\app\PromptTemplates\SeoSectionTemplate;

class AIContentGeneratorService
{
    use FileManagerTrait {
        delete as deleteFile;
    }

    public const CONTENT_PRODUCT_NAME = 'product_name';

    public const CONTENT_PRODUCT_DESCRIPTION = 'product_description';

    public const CONTENT_GENERAL_SETUP = 'general_setup';

    public const CONTENT_PRICING_AND_OTHERS = 'pricing_and_others';

    public const CONTENT_VARIATION_SETUP = 'variation_setup';

    public const CONTENT_SEO_SECTION = 'seo_section';

    public const CONTENT_PRODUCT_TITLE_SUGGESTION = 'generate_product_title_suggestion';

    public const CONTENT_TITLE_FROM_IMAGE = 'generate_title_from_image';

    public const CONTENT_BLOG_TITLE = 'blog_title';

    public const CONTENT_BLOG_DESCRIPTION = 'blog_description';

    public const CONTENT_BLOG_TITLE_SUGGESTION = 'blog_title_suggestion';

    public const CONTENT_BLOG_SEO_SECTION = 'blog_seo_section';

    public const CONTENT_BLOG_TITLE_FROM_IMAGE = 'generate_blog_title_from_image';

    public const CONTENT_AUCTION_TITLE = 'auction_title';

    public const CONTENT_AUCTION_DESCRIPTION = 'auction_description';

    public const CONTENT_AUCTION_GENERAL_SETUP = 'auction_general_setup';

    public const CONTENT_AUCTION_SHIPPING_POLICY = 'auction_shipping_policy';

    public const CONTENT_AUCTION_INFO = 'auction_info';

    public const CONTENT_AUCTION_SEO_SECTION = 'auction_seo_section';

    public const CONTENT_AUCTION_SETUP = 'auction_setup';

    public const TYPE_PRODUCT = 'product';

    public const TYPE_AUCTION = 'auction';

    public const TYPE_BLOG = 'blog';

    private const STORAGE_PUBLIC = 'public';

    private const STORAGE_S3 = 's3';

    /** @var array<int, string> */
    private const LOCAL_ADDRESSES = ['127.0.0.1', '::1'];

    /** @var array<string, class-string> */
    private const TEMPLATE_CLASSES = [
        self::CONTENT_PRODUCT_NAME => ProductNameTemplate::class,
        self::CONTENT_PRODUCT_DESCRIPTION => ProductDescriptionTemplate::class,
        self::CONTENT_GENERAL_SETUP => GeneralSetupTemplates::class,
        self::CONTENT_PRICING_AND_OTHERS => PricingTemplate::class,
        self::CONTENT_VARIATION_SETUP => ProductVariationSetup::class,
        self::CONTENT_SEO_SECTION => SeoSectionTemplate::class,
        self::CONTENT_PRODUCT_TITLE_SUGGESTION => GenerateProductTitleSuggestionTemplate::class,
        self::CONTENT_TITLE_FROM_IMAGE => GenerateTitleFromImageTemplate::class,
        self::CONTENT_BLOG_TITLE => TitleTemplate::class,
        self::CONTENT_BLOG_DESCRIPTION => DescriptionTemplate::class,
        self::CONTENT_BLOG_TITLE_SUGGESTION => TitleSuggestionTemplate::class,
        self::CONTENT_BLOG_SEO_SECTION => BlogSeoSectionTemplate::class,
        self::CONTENT_BLOG_TITLE_FROM_IMAGE => GenerateBlogTitleFromImageTemplate::class,
        self::CONTENT_AUCTION_TITLE => AuctionTitleTemplate::class,
        self::CONTENT_AUCTION_DESCRIPTION => AuctionDescriptionTemplate::class,
        self::CONTENT_AUCTION_GENERAL_SETUP => AuctionGeneralSetupTemplate::class,
        self::CONTENT_AUCTION_SHIPPING_POLICY => AuctionShippingPolicyTemplate::class,
        self::CONTENT_AUCTION_INFO => AuctionInfoTemplate::class,
        self::CONTENT_AUCTION_SEO_SECTION => AuctionSeoSectionTemplate::class,
        self::CONTENT_AUCTION_SETUP => AuctionSetupTemplate::class,
    ];

    public function __construct(private readonly LanguageService $languageService) {}

    public function generateContent(
        string $contentType,
        mixed $context = null,
        string $langCode = 'en',
        ?string $description = null,
        ?string $imageUrl = null,
    ): string {
        $prompt = $this->template(contentType: $contentType)->build(
            context: $context,
            language: $this->promptLanguage(langCode: $langCode),
            description: $description,
            options: ['image' => $imageUrl],
        );

        return (new AIProviderManager([new OpenAIProvider, new ClaudeProvider]))->generate(
            prompt: $prompt,
            imageUrl: $imageUrl,
            options: ['section' => $contentType, 'context' => $context, 'description' => $description],
        );
    }

    /** @return array{imageName: string, imageFullPath: string} */
    public function getProductImagePath(UploadedFile $image): array
    {
        return $this->getAiImagePath(image: $image, type: self::TYPE_PRODUCT);
    }

    /** @return array{imageName: string, imageFullPath: string} */
    public function getAuctionImagePath(UploadedFile $image): array
    {
        return $this->getAiImagePath(image: $image, type: self::TYPE_AUCTION);
    }

    /** @return array{imageName: string, imageFullPath: string} */
    public function getBlogImagePath(UploadedFile $image): array
    {
        return $this->getAiImagePath(image: $image, type: self::TYPE_BLOG);
    }

    /**
     * @return array{imageName: string, imageFullPath: string}
     */
    public function getAiImagePath(UploadedFile $image, string $type): array
    {
        $storageConnectionType = $this->storageConnectionType();
        $extension = $image->getClientOriginalExtension();

        if ($storageConnectionType === self::STORAGE_PUBLIC) {
            return $this->getAiImageFullPath(
                imageName: $this->fileUpload(dir: $this->imageDirectory(type: $type), format: $extension, file: $image),
                type: $type,
            );
        }

        if ($storageConnectionType === self::STORAGE_S3) {
            $imageName = $this->imageDirectory(type: $type).Carbon::now()->toDateString().'-'.uniqid().'.'.$extension;
            Storage::disk(self::STORAGE_S3)->put($imageName, file_get_contents($image), self::STORAGE_PUBLIC);

            return $this->getAiImageFullPath(imageName: $imageName, type: $type);
        }

        throw new Exception('Invalid storage connection type');
    }

    /** @return array{imageName: string, imageFullPath: string} */
    public function getAiImageFullPath(string $imageName, string $type): array
    {
        if (in_array(request()->ip(), self::LOCAL_ADDRESSES)) {
            return ['imageName' => $imageName, 'imageFullPath' => config('ai.dev_sample_image_url')];
        }

        if ($this->storageConnectionType() === self::STORAGE_S3) {
            return ['imageName' => $imageName, 'imageFullPath' => Storage::disk(self::STORAGE_S3)->url($imageName)];
        }

        return [
            'imageName' => $imageName,
            'imageFullPath' => dynamicStorage(path: 'storage/app/public/'.$this->imageDirectory(type: $type).$imageName),
        ];
    }

    public function deleteAiImage(string $imageName, string $type): void
    {
        $this->deleteFile($this->imageDirectory(type: $type).$imageName);
    }

    private function promptLanguage(string $langCode): string
    {
        return $this->languageService->getLanguageName(code: $langCode)
            ?? 'language code "'.strtoupper($langCode).'"';
    }

    private function template(string $contentType): object
    {
        $templateClass = self::TEMPLATE_CLASSES[$contentType];

        return new $templateClass;
    }

    private function imageDirectory(string $type): string
    {
        return "{$type}/ai_{$type}_image/";
    }

    private function storageConnectionType(): string
    {
        return getWebConfig(name: 'storage_connection_type') ?? self::STORAGE_PUBLIC;
    }
}
