<?php

namespace Tests\Feature\Product;

use App\Models\Admin;
use App\Models\Color;
use App\Models\Product;
use App\Models\Seller;
use App\Services\New\ColorService;
use App\Services\New\ProductRetailService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class RetailProductImageTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private ProductRetailService $service;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        Storage::fake('public');

        $this->service = app(ProductRetailService::class);
    }

    public function test_a_product_without_colours_stores_the_gallery_and_leaves_colour_images_empty(): void
    {
        $product = $this->createProduct('IMGPLAIN1', colours: [], galleryFiles: 2);

        $this->assertCount(2, $this->gallery($product));
        $this->assertSame([], $this->colourImages($product));
    }

    public function test_a_colour_product_tags_each_colour_and_repeats_the_whole_gallery(): void
    {
        [$product, $colours] = $this->createColourProduct('IMGCOLOR1', galleryFiles: 1);

        $colourImages = $this->colourImages($product);
        $tagged = array_values(array_filter($colourImages, static fn (array $i): bool => $i['color'] !== null));

        $this->assertCount(2, $tagged);
        $this->assertSame(
            array_map(static fn (Color $c): string => ltrim($c->code, '#'), $colours),
            array_column($tagged, 'color'),
        );

        $this->assertSame(
            array_column($this->gallery($product), 'image_name'),
            array_column($colourImages, 'image_name'),
            'every gallery image must appear in color_image',
        );
    }

    public function test_replacing_a_colour_image_swaps_it_in_both_columns_and_drops_the_old_file(): void
    {
        [$product, $colours] = $this->createColourProduct('IMGSWAP1', galleryFiles: 1);

        $code = ltrim($colours[0]->code, '#');
        $before = $this->imageNameFor($product, $code);

        $this->service->update(
            id: $product->id,
            data: $this->colourUpdateData($product, $colours),
            files: ['color_image_'.$code => UploadedFile::fake()->image('swapped.png')],
            updateBy: ProductRetailService::ADDED_BY_ADMIN,
            colorNames: app(ColorService::class)->getNameMap(),
        );

        $product->refresh();
        $after = $this->imageNameFor($product, $code);

        $this->assertNotSame($before, $after);
        $this->assertNotContains($before, array_column($this->gallery($product), 'image_name'));
        $this->assertContains($after, array_column($this->gallery($product), 'image_name'));
        Storage::disk('public')->assertMissing(ProductRetailService::IMAGE_DIRECTORY.$before);
    }

    public function test_deselecting_a_colour_keeps_the_image_and_untags_it(): void
    {
        [$product, $colours] = $this->createColourProduct('IMGUNTAG1', galleryFiles: 1);

        $keptName = $this->imageNameFor($product, ltrim($colours[1]->code, '#'));

        $data = $this->colourUpdateData($product, $colours);
        $data['colors'] = [$colours[0]->code];

        $this->service->update(
            id: $product->id,
            data: $data,
            files: [],
            updateBy: ProductRetailService::ADDED_BY_ADMIN,
            colorNames: app(ColorService::class)->getNameMap(),
        );

        $product->refresh();
        $colourImages = $this->colourImages($product);

        $this->assertContains($keptName, array_column($colourImages, 'image_name'));
        $this->assertContains($keptName, array_column($this->gallery($product), 'image_name'));
        $this->assertSame(
            [ltrim($colours[0]->code, '#')],
            array_values(array_filter(array_column($colourImages, 'color'))),
        );
    }

    public function test_turning_colours_off_clears_colour_images_and_keeps_every_image_in_the_gallery(): void
    {
        [$product, $colours] = $this->createColourProduct('IMGOFF1', galleryFiles: 1);

        $names = array_column($this->gallery($product), 'image_name');

        $data = $this->colourUpdateData($product, $colours);
        unset($data['colors_active'], $data['colors']);

        $this->service->update(
            id: $product->id,
            data: $data,
            files: [],
            updateBy: ProductRetailService::ADDED_BY_ADMIN,
            colorNames: app(ColorService::class)->getNameMap(),
        );

        $product->refresh();

        $this->assertSame([], $this->colourImages($product));

        foreach ($names as $name) {
            $this->assertContains($name, array_column($this->gallery($product), 'image_name'));
        }
    }

    public function test_additional_images_append_to_both_columns(): void
    {
        [$product] = $this->createColourProduct('IMGADD1', galleryFiles: 1);

        $before = count($this->gallery($product));

        $files = ['images' => [UploadedFile::fake()->image('extra.png')]];

        $this->service->updateImages(id: $product->id, files: $files);

        $product->refresh();

        $this->assertCount($before + 1, $this->gallery($product));
        $this->assertSame(
            array_column($this->gallery($product), 'image_name'),
            array_column($this->colourImages($product), 'image_name'),
        );
    }

    public function test_deleting_an_image_removes_it_from_both_columns(): void
    {
        [$product] = $this->createColourProduct('IMGDEL1', galleryFiles: 2);

        $names = array_column($this->gallery($product), 'image_name');
        $target = end($names);

        $this->assertTrue($this->service->deleteImage(id: $product->id, imageName: $target));

        $product->refresh();

        $this->assertNotContains($target, array_column($this->gallery($product), 'image_name'));
        $this->assertNotContains($target, array_column($this->colourImages($product), 'image_name'));
    }

    public function test_deleting_an_image_on_a_colourless_product_clears_stored_colour_images(): void
    {
        $product = $this->createProduct('IMGNOCOL1', colours: [], galleryFiles: 2);

        $names = array_column($this->gallery($product), 'image_name');

        $this->service->updateImages(id: $product->id, images: [
            'image_names' => [
                ['image_name' => $names[0], 'storage' => 'public'],
                ['image_name' => $names[1], 'storage' => 'public'],
            ],
            'colored_image_names' => [
                ['color' => null, 'image_name' => $names[0], 'storage' => 'public'],
                ['color' => null, 'image_name' => $names[1], 'storage' => 'public'],
            ],
        ]);

        $product->refresh();

        $this->assertNotSame([], $this->colourImages($product));

        $this->assertTrue($this->service->deleteImage(id: $product->id, imageName: $names[1]));

        $product->refresh();

        $this->assertSame([], $this->colourImages($product));
        $this->assertNotContains($names[1], array_column($this->gallery($product), 'image_name'));
        $this->assertContains($names[0], array_column($this->gallery($product), 'image_name'));
    }

    public function test_adding_a_gallery_image_to_a_colourless_product_backfills_colour_image_with_the_whole_gallery(): void
    {
        $product = $this->createProduct('IMGBACKFILL1', colours: [], galleryFiles: 2);

        $originalNames = array_column($this->gallery($product), 'image_name');
        $this->assertSame([], $this->colourImages($product));

        $files = ['images' => [UploadedFile::fake()->image('extra.png')]];
        $loaded = $this->service->findForEdit(id: $product->id, relations: []);
        $images = $this->service->processAdditionalImages(files: $files, product: $loaded);

        $this->service->updateImages(id: $product->id, files: $files, images: $images);

        $product->refresh();

        $galleryNames = array_column($this->gallery($product), 'image_name');
        $newNames = array_values(array_diff($galleryNames, $originalNames));

        $this->assertCount(1, $newNames);
        $this->assertSame(array_merge($originalNames, $newNames), $galleryNames);

        $colourImages = $this->colourImages($product);
        $this->assertSame($galleryNames, array_column($colourImages, 'image_name'));
        $this->assertSame([null, null, null], array_column($colourImages, 'color'));
    }

    public function test_a_seller_cannot_remove_an_image_on_a_product_they_do_not_own(): void
    {
        $seller = Seller::query()->where('status', 'approved')->firstOrFail();

        [$product] = $this->createColourProduct('IMGSCOPE1', galleryFiles: 2);

        $names = array_column($this->gallery($product), 'image_name');

        $this->actingAs($seller, 'seller')
            ->post('vendor/retail/products/update-product-images/'.$product->id, ['removed_images' => [end($names)]])
            ->assertNotFound();

        $product->refresh();

        $this->assertContains(end($names), array_column($this->gallery($product), 'image_name'));
        Storage::disk('public')->assertExists(ProductRetailService::IMAGE_DIRECTORY.end($names));
    }

    public function test_the_image_delete_endpoints_no_longer_exist(): void
    {
        $this->assertFalse(app('router')->has('admin.retail.products.delete-image'));
        $this->assertFalse(app('router')->has('vendor.retail.products.delete-image'));
    }

    public function test_an_image_marked_for_removal_is_dropped_from_both_columns_and_storage_on_update(): void
    {
        [$product, $colours] = $this->createColourProduct('IMGPEND1', galleryFiles: 2);

        $uncolouredNames = array_column(
            array_filter($this->colourImages($product), static fn (array $image): bool => $image['color'] === null),
            'image_name',
        );
        $target = reset($uncolouredNames);
        $kept = end($uncolouredNames);

        Storage::disk('public')->assertExists(ProductRetailService::IMAGE_DIRECTORY.$target);

        $this->service->update(
            id: $product->id,
            data: $this->colourUpdateData($product, $colours) + ['removed_images' => [$target]],
            files: [],
            updateBy: ProductRetailService::ADDED_BY_ADMIN,
            colorNames: app(ColorService::class)->getNameMap(),
        );

        $product->refresh();

        $this->assertNotContains($target, array_column($this->gallery($product), 'image_name'));
        $this->assertNotContains($target, array_column($this->colourImages($product), 'image_name'));
        $this->assertContains($kept, array_column($this->gallery($product), 'image_name'));
        Storage::disk('public')->assertMissing(ProductRetailService::IMAGE_DIRECTORY.$target);
        Storage::disk('public')->assertExists(ProductRetailService::IMAGE_DIRECTORY.$kept);
    }

    public function test_a_removed_colour_image_is_replaced_by_the_uploaded_one_on_update(): void
    {
        [$product, $colours] = $this->createColourProduct('IMGPEND2', galleryFiles: 1);

        $code = ltrim($colours[0]->code, '#');
        $before = $this->imageNameFor($product, $code);

        $this->service->update(
            id: $product->id,
            data: $this->colourUpdateData($product, $colours) + ['removed_images' => [$before]],
            files: ['color_image_'.$code => UploadedFile::fake()->image('replacement.png')],
            updateBy: ProductRetailService::ADDED_BY_ADMIN,
            colorNames: app(ColorService::class)->getNameMap(),
        );

        $product->refresh();
        $after = $this->imageNameFor($product, $code);

        $this->assertNotNull($after);
        $this->assertNotSame($before, $after);
        $this->assertNotContains($before, array_column($this->gallery($product), 'image_name'));
        Storage::disk('public')->assertMissing(ProductRetailService::IMAGE_DIRECTORY.$before);
        Storage::disk('public')->assertExists(ProductRetailService::IMAGE_DIRECTORY.$after);
    }

    public function test_removal_names_outside_the_gallery_touch_nothing(): void
    {
        $product = $this->createProduct('IMGPEND3', colours: [], galleryFiles: 2);

        $names = array_column($this->gallery($product), 'image_name');
        Storage::disk('public')->put(ProductRetailService::THUMBNAIL_DIRECTORY.'other.webp', 'not-this-file');

        $this->service->update(
            id: $product->id,
            data: $this->baseData($product->code) + [
                'existing_thumbnail' => $product->thumbnail,
                'removed_images' => ['thumbnail/other.webp', '../other.webp'],
            ],
            files: [],
            updateBy: ProductRetailService::ADDED_BY_ADMIN,
        );

        $product->refresh();

        $this->assertSame($names, array_column($this->gallery($product), 'image_name'));
        Storage::disk('public')->assertExists(ProductRetailService::THUMBNAIL_DIRECTORY.'other.webp');
    }

    public function test_the_edit_images_panel_removes_marked_images_only_when_saved(): void
    {
        $product = $this->createProduct('IMGPEND4', colours: [], galleryFiles: 2);

        $names = array_column($this->gallery($product), 'image_name');

        $this->service->updateImages(id: $product->id, removedImageNames: [$names[0]]);

        $product->refresh();

        $this->assertSame([$names[1]], array_column($this->gallery($product), 'image_name'));
        Storage::disk('public')->assertMissing(ProductRetailService::IMAGE_DIRECTORY.$names[0]);
        Storage::disk('public')->assertExists(ProductRetailService::IMAGE_DIRECTORY.$names[1]);
    }

    public function test_the_edit_images_panel_refuses_to_remove_every_image(): void
    {
        $product = $this->createProduct('IMGPEND5', colours: [], galleryFiles: 2);

        $names = array_column($this->gallery($product), 'image_name');

        $this->actingAs(Admin::query()->orderBy('id')->firstOrFail(), 'admin')
            ->post('admin/retail/products/update-product-images/'.$product->id, ['removed_images' => $names])
            ->assertSessionHasErrors('removed_images');

        $product->refresh();

        $this->assertSame($names, array_column($this->gallery($product), 'image_name'));

        foreach ($names as $name) {
            Storage::disk('public')->assertExists(ProductRetailService::IMAGE_DIRECTORY.$name);
        }
    }

    public function test_the_colour_tile_endpoint_leaves_a_tile_empty_while_its_image_is_pending_removal(): void
    {
        [$product, $colours] = $this->createColourProduct('IMGPEND6', galleryFiles: 1);

        $removedCode = ltrim($colours[0]->code, '#');
        $keptCode = ltrim($colours[1]->code, '#');
        $removedName = $this->imageNameFor($product, $removedCode);

        $view = $this->actingAs(Admin::query()->orderBy('id')->firstOrFail(), 'admin')
            ->post('admin/retail/products/color-wise-images', [
                'product_id' => $product->id,
                'colors' => array_map(static fn (Color $c): string => $c->code, $colours),
                'removed_images' => [$removedName],
            ])
            ->assertOk()
            ->json('view');

        $this->assertStringContainsString('color-img-upload-'.$removedCode, $view);
        $this->assertStringNotContainsString('data-name="'.$removedName.'"', $view);
        $this->assertStringContainsString('data-name="'.$this->imageNameFor($product, $keptCode).'"', $view);
        Storage::disk('public')->assertExists(ProductRetailService::IMAGE_DIRECTORY.$removedName);
    }

    public function test_the_admin_colour_tile_endpoint_renders_one_tile_per_colour_with_a_delete_control(): void
    {
        [$product, $colours] = $this->createColourProduct('IMGTILE1', galleryFiles: 1);

        $response = $this->actingAs(Admin::query()->orderBy('id')->firstOrFail(), 'admin')
            ->post('admin/retail/products/color-wise-images', [
                'product_id' => $product->id,
                'colors' => array_map(static fn (Color $c): string => $c->code, $colours),
            ])
            ->assertOk();

        $view = $response->json('view');

        foreach ($colours as $colour) {
            $code = ltrim($colour->code, '#');

            $this->assertStringContainsString('color-img-upload-'.$code, $view);
            $this->assertStringContainsString('name="color_image_'.$code.'"', $view);
            $this->assertStringContainsString('data-name="'.$this->imageNameFor($product, $code).'"', $view);
        }

        $this->assertStringContainsString('action-delete-product-image', $view);
    }

    public function test_the_vendor_colour_tile_endpoint_never_reveals_another_sellers_stored_image(): void
    {
        [$product, $colours] = $this->createColourProduct('IMGTILE2', galleryFiles: 1);

        $view = $this->actingAs(Seller::query()->where('status', 'approved')->firstOrFail(), 'seller')
            ->post('vendor/retail/products/color-wise-images', [
                'product_id' => $product->id,
                'colors' => array_map(static fn (Color $c): string => $c->code, $colours),
            ])
            ->assertOk()
            ->json('view');

        $code = ltrim($colours[0]->code, '#');

        $this->assertStringContainsString('color-img-upload-'.$code, $view);
        $this->assertStringNotContainsString($this->imageNameFor($product, $code), $view);
        $this->assertStringNotContainsString('action-delete-product-image', $view);
    }

    private function gallery(Product $product): array
    {
        return collect(json_decode($product->getRawOriginal('images'), true) ?: [])
            ->map(static fn ($image): array => is_array($image) ? $image : ['image_name' => $image, 'storage' => 'public'])
            ->values()
            ->all();
    }

    private function colourImages(Product $product): array
    {
        return json_decode($product->retailDetail?->color_image ?: '[]', true) ?: [];
    }

    private function imageNameFor(Product $product, string $colourCode): ?string
    {
        foreach ($this->colourImages($product) as $image) {
            if (($image['color'] ?? null) === $colourCode) {
                return $image['image_name'];
            }
        }

        return null;
    }

    private function createProduct(string $code, array $colours, int $galleryFiles): Product
    {
        $data = $this->baseData($code);
        $files = [];

        if ($colours !== []) {
            $data['colors_active'] = 'on';
            $data['colors'] = array_map(static fn (Color $c): string => $c->code, $colours);

            foreach ($colours as $colour) {
                $files['color_image_'.ltrim($colour->code, '#')] = UploadedFile::fake()->image($colour->name.'.png');
            }
        }

        if ($galleryFiles > 0) {
            $files['images'] = [];

            for ($index = 0; $index < $galleryFiles; $index++) {
                $files['images'][] = UploadedFile::fake()->image('gallery'.$index.'.png');
            }
        }

        $this->service->create(
            data: $data,
            files: $files,
            addedBy: ProductRetailService::ADDED_BY_ADMIN,
            userId: null,
            shopId: 0,
            colorNames: app(ColorService::class)->getNameMap(),
        );

        return Product::query()->where('code', $code)->firstOrFail();
    }

    private function createColourProduct(string $code, int $galleryFiles): array
    {
        $colours = Color::query()->orderBy('id')->limit(2)->get()->all();

        if (count($colours) < 2) {
            $this->markTestSkipped('Test database has fewer than two colours.');
        }

        return [$this->createProduct($code, $colours, $galleryFiles), $colours];
    }

    private function colourUpdateData(Product $product, array $colours): array
    {
        return $this->baseData($product->code) + [
            'colors_active' => 'on',
            'colors' => array_map(static fn (Color $c): string => $c->code, $colours),
            'existing_thumbnail' => $product->thumbnail,
        ];
    }

    private function baseData(string $code): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Image product '.$code],
            'description' => ['<p>Image description '.$code.'</p>'],
            'code' => $code,
            'category_id' => (int) \DB::table('categories')->where('position', 0)->value('id'),
            'sub_category_id' => null,
            'sub_sub_category_id' => null,
            'brand_id' => null,
            'unit' => 'pc',
            'product_type' => 'physical',
            'unit_price' => 200,
            'discount' => 0,
            'discount_type' => 'flat',
            'current_stock' => 10,
            'minimum_order_qty' => 1,
            'shipping_cost' => 0,
            'video_url' => null,
            'meta_title' => 'Image product '.$code,
            'meta_description' => 'Image description '.$code,
            'choice_attributes' => [],
        ];
    }
}
