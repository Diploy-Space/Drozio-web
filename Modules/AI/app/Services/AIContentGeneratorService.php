<?php

namespace Modules\AI\app\Services;

use App\Services\New\LanguageService;
use App\Traits\FileManagerTrait;
use Carbon\Carbon;
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
use Modules\AI\app\PromptTemplates\Wholesale\FaqTemplate as WholesaleFaqTemplate;
use Modules\AI\app\PromptTemplates\Wholesale\GeneralSetupTemplate as WholesaleGeneralSetupTemplate;
use Modules\AI\app\PromptTemplates\Wholesale\PricingSetupTemplate as WholesalePricingSetupTemplate;
use Modules\AI\app\PromptTemplates\Wholesale\VariationSetupTemplate as WholesaleVariationSetupTemplate;

class AIContentGeneratorService
{
    use FileManagerTrait;

    protected array $templates = [];

    protected array $providers;

    public function __construct(private readonly LanguageService $languageService)
    {
        $this->loadTemplates();
        $this->providers = [new OpenAIProvider, new ClaudeProvider];
    }

    protected function loadTemplates(): void
    {
        $templateClasses = [
            'product_name' => ProductNameTemplate::class,
            'product_description' => ProductDescriptionTemplate::class,
            'general_setup' => GeneralSetupTemplates::class,
            'pricing_and_others' => PricingTemplate::class,
            'variation_setup' => ProductVariationSetup::class,
            'seo_section' => SeoSectionTemplate::class,
            'generate_product_title_suggestion' => GenerateProductTitleSuggestionTemplate::class,
            'generate_title_from_image' => GenerateTitleFromImageTemplate::class,
            'blog_title' => TitleTemplate::class,
            'blog_description' => DescriptionTemplate::class,
            'blog_title_suggestion' => TitleSuggestionTemplate::class,
            'blog_seo_section' => BlogSeoSectionTemplate::class,
            'generate_blog_title_from_image' => GenerateBlogTitleFromImageTemplate::class,
            'auction_title' => AuctionTitleTemplate::class,
            'auction_description' => AuctionDescriptionTemplate::class,
            'auction_general_setup' => AuctionGeneralSetupTemplate::class,
            'auction_shipping_policy' => AuctionShippingPolicyTemplate::class,
            'auction_info' => AuctionInfoTemplate::class,
            'auction_seo_section' => AuctionSeoSectionTemplate::class,
            'auction_setup' => AuctionSetupTemplate::class,
            'wholesale_general_setup' => WholesaleGeneralSetupTemplate::class,
            'wholesale_variation_setup' => WholesaleVariationSetupTemplate::class,
            'wholesale_pricing_setup' => WholesalePricingSetupTemplate::class,
            'wholesale_faq' => WholesaleFaqTemplate::class,
        ];
        foreach ($templateClasses as $type => $class) {
            if (class_exists($class)) {
                $this->templates[$type] = new $class;
            }
        }
    }

    public function generateContent(string $contentType, mixed $context = null, string $langCode = 'en', ?string $description = null, ?string $imageUrl = null, array $options = []): string
    {
        $template = $this->templates[$contentType];
        $options['translation_languages'] = $this->promptLanguages(langCodes: $options['translation_languages'] ?? []);
        $prompt = $template->build(context: $context, language: $this->promptLanguage(langCode: $langCode), description: $description, options: ['image' => $imageUrl, ...$options]);

        return (new AIProviderManager($this->providers))->generate(prompt: $prompt, imageUrl: $imageUrl, options: ['section' => $contentType, 'context' => $context, 'description' => $description]);
    }

    private function promptLanguages(array $langCodes): array
    {
        $languages = [];

        foreach ($langCodes as $langCode) {
            $languages[$langCode] = $this->promptLanguage(langCode: $langCode);
        }

        return $languages;
    }

    private function promptLanguage(string $langCode): string
    {
        return $this->languageService->getLanguageName(code: $langCode)
            ?? 'language code "'.strtoupper($langCode).'"';
    }

    public function getAnalyizeImagePath($image): array
    {
        return $this->getAiImagePath($image, 'product');
    }

    public function getAuctionImagePath($image): array
    {
        return $this->getAiImagePath($image, 'auction');
    }

    public function getBlogImagePath($image): array
    {
        return $this->getAiImagePath($image, 'blog');
    }

    public function deleteAiImage($imageName, $type): void
    {
        $dir = "{$type}/ai_{$type}_image/".$imageName;
        $this->delete($dir);
    }

    public function getAiImagePath($image, string $type): array
    {
        $storageConnectionType = getWebConfig(name: 'storage_connection_type') ?? 'public';
        $extension = $image->getClientOriginalExtension();

        if ($storageConnectionType === 'public') {
            $dir = "{$type}/ai_{$type}_image/";
            $imageName = $this->fileUpload(dir: $dir, format: $extension, file: $image);

            return $this->getAiImageFullPath($imageName, $type);
        }

        if ($storageConnectionType === 's3') {
            $imageName = "{$type}/ai_{$type}_image/".Carbon::now()->toDateString().'-'.uniqid().'.'.$extension;
            Storage::disk('s3')->put($imageName, file_get_contents($image), 'public');

            return $this->getAiImageFullPath($imageName, $type);
        }

        throw new \Exception('Invalid storage connection type');
    }

    public function getAiImageFullPath(string $imageName, string $type): array
    {
        if (in_array(request()->ip(), ['127.0.0.1', '::1'])) {
            return [
                'imageName' => $imageName,
                'imageFullPath' => config('ai.dev_sample_image_url'),
            ];
        }
        $storageConnectionType = getWebConfig(name: 'storage_connection_type') ?? 'public';

        if ($storageConnectionType === 's3') {
            return [
                'imageName' => $imageName,
                'imageFullPath' => Storage::disk('s3')->url($imageName),
            ];
        }

        return [
            'imageName' => $imageName,
            'imageFullPath' => dynamicStorage(path: "storage/app/public/{$type}/ai_{$type}_image/{$imageName}"),
        ];
    }
}
