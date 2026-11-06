<?php

namespace Tests\Feature\Product;

use App\Enums\BusinessModule;
use App\Models\Category;
use App\Utils\Helpers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Modules\Wholesale\app\Services\Product\AttributeImageService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleProductMediaUpdateTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

    private const SHARED_IMAGE = 'shared-image.webp';

    private const UPLOADED_IMAGE = 'uploaded-image.webp';

    private const YOUTUBE_LINK = 'https://www.youtube.com/embed/5R06LRdUCSE';

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Cache::flush();
        Helpers::resetMemo();

        config(['filesystems.disks.default' => 'public']);
        Storage::fake('public');
        Storage::disk('public')->put(ProductWholesaleService::IMAGE_DIRECTORY.self::SHARED_IMAGE, 'image');
        Storage::disk('public')->put(ProductWholesaleService::IMAGE_DIRECTORY.self::UPLOADED_IMAGE, 'image');
    }

    public function test_additional_image_sharing_a_file_with_an_unticked_variation_image_stays_in_the_gallery(): void
    {
        $product = $this->createProductWithGallery([
            ['image_name' => self::UPLOADED_IMAGE, 'storage' => 'public'],
            ['image_name' => self::SHARED_IMAGE, 'storage' => 'public'],
            ['image_name' => self::SHARED_IMAGE, 'storage' => 'public', 'source' => ProductWholesaleService::GALLERY_SOURCE_ATTRIBUTE],
        ]);

        $this->updateImages($product, existingImages: [self::UPLOADED_IMAGE, self::SHARED_IMAGE], ticked: false);

        $this->assertSame(
            [self::UPLOADED_IMAGE, self::SHARED_IMAGE],
            $this->service()->getUploadedGalleryImageNames($product->refresh()),
        );
        $this->assertSame([], $this->attributeGalleryNames($product));
        Storage::disk('public')->assertExists(ProductWholesaleService::IMAGE_DIRECTORY.self::SHARED_IMAGE);
    }

    public function test_removing_an_additional_image_keeps_the_file_a_variation_still_uses(): void
    {
        $product = $this->createProductWithGallery([
            ['image_name' => self::UPLOADED_IMAGE, 'storage' => 'public'],
            ['image_name' => self::SHARED_IMAGE, 'storage' => 'public'],
        ]);

        $this->updateImages($product, existingImages: [self::UPLOADED_IMAGE], ticked: false);

        $this->assertSame([self::UPLOADED_IMAGE], $this->service()->getUploadedGalleryImageNames($product->refresh()));
        Storage::disk('public')->assertExists(ProductWholesaleService::IMAGE_DIRECTORY.self::SHARED_IMAGE);
    }

    public function test_removing_a_variation_image_keeps_the_file_the_additional_gallery_still_uses(): void
    {
        $product = $this->createProductWithGallery([
            ['image_name' => self::SHARED_IMAGE, 'storage' => 'public'],
        ]);

        $this->attributeImageService()->replaceProductImageSets(
            productId: $product->id,
            sets: [$this->colorSet(existing: [self::SHARED_IMAGE], ticked: false)],
            files: [],
        );

        $this->attributeImageService()->replaceProductImageSets(
            productId: $product->id,
            sets: [$this->colorSet(existing: [], ticked: false)],
            files: [],
            retainedImageNames: $this->service()->getUploadedGalleryImageNames($product),
        );

        $this->assertSame(0, $this->attributeImageService()->getForProduct($product->id)->count());
        Storage::disk('public')->assertExists(ProductWholesaleService::IMAGE_DIRECTORY.self::SHARED_IMAGE);
    }

    public function test_product_keeps_both_video_sources_and_shows_only_the_chosen_one(): void
    {
        $product = $this->service()->create(data: [
            ...$this->productData(),
            'video_provider' => ProductWholesaleService::VIDEO_MODE_FILE,
            'youtube_video_url' => self::YOUTUBE_LINK,
        ], files: ['custom_video_url' => UploadedFile::fake()->create('product.mp4', 10, 'video/mp4')]);

        $storedFile = $product->video_file;

        $this->assertSame(ProductWholesaleService::VIDEO_PROVIDER_SELF_HOSTED, $product->video_provider);
        $this->assertSame(self::YOUTUBE_LINK, $product->video_url);
        $this->assertNotNull($storedFile);

        $updated = $this->service()->update(id: $product->id, data: [
            ...$this->productData(code: $product->code),
            'video_provider' => ProductWholesaleService::VIDEO_MODE_LINK,
            'youtube_video_url' => self::YOUTUBE_LINK,
        ]);

        $this->assertSame(ProductWholesaleService::VIDEO_PROVIDER_YOUTUBE, $updated->video_provider);
        $this->assertSame(self::YOUTUBE_LINK, $updated->video_url);
        $this->assertSame($storedFile, $updated->video_file);
        $this->assertSame(ProductWholesaleService::VIDEO_MODE_LINK, ProductWholesaleService::getVideoMode($updated->video_provider));
    }

    private function updateImages(Model $product, array $existingImages, bool $ticked): void
    {
        $set = $this->colorSet(existing: [self::SHARED_IMAGE], ticked: $ticked);

        $updated = $this->service()->updateImages(
            id: $product->id,
            data: ['existing_images' => $existingImages, 'attribute_images' => [$set]],
            files: [],
        );

        $this->service()->syncAttributeImagesIntoGallery(
            product: $updated,
            attributeImages: $this->attributeImageService()->replaceProductImageSets(
                productId: $updated->id,
                sets: [$set],
                files: [],
                retainedImageNames: $this->service()->getUploadedGalleryImageNames(product: $updated),
            ),
        );
    }

    private function colorSet(array $existing, bool $ticked): array
    {
        return [
            'type' => AttributeImageService::TYPE_COLOR,
            'attribute_id' => null,
            'value' => 'Navy',
            'existing' => $existing,
            'gallery' => $ticked ? array_fill(0, count($existing), '1') : [],
        ];
    }

    private function attributeGalleryNames(Model $product): array
    {
        return collect(json_decode($product->refresh()->images, true))
            ->where('source', ProductWholesaleService::GALLERY_SOURCE_ATTRIBUTE)
            ->pluck('image_name')
            ->all();
    }

    private function createProductWithGallery(array $gallery): Model
    {
        $product = $this->service()->create(data: $this->productData());

        $product->update(['images' => json_encode($gallery)]);

        return $product->refresh();
    }

    private function productData(?string $code = null): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Wholesale media fixture'],
            'description' => ['Wholesale media fixture'],
            'code' => $code ?? 'WSMED'.random_int(10000, 99999),
            'category_id' => $this->categoryId(),
            'product_type' => 'physical',
            'unit' => 'pc',
            'shipping_cost' => 0,
            'minimum_order_qty' => 10,
            'current_stock' => 1000,
            'discount' => 0,
            'discount_type' => ProductWholesaleService::DISCOUNT_TYPE_FLAT,
            'tax_model' => ProductWholesaleService::TAX_MODEL_EXCLUDE,
            'quantity_based_price' => 1,
            'tier_min_qty' => [10, 50],
            'tier_max_qty' => ['49', ''],
            'tier_unit_price' => [50, 40],
        ];
    }

    private function categoryId(): int
    {
        return (int) $this->requireFixture(
            Category::query()->where('business_module', BusinessModule::WHOLESALE)->where('position', 0)->value('id'),
            'wholesale category',
        );
    }

    private function service(): ProductWholesaleService
    {
        return app(ProductWholesaleService::class);
    }

    private function attributeImageService(): AttributeImageService
    {
        return app(AttributeImageService::class);
    }
}
