<?php

namespace Tests\Feature\Product;

use App\Models\Admin;
use App\Models\Attribute;
use App\Models\Color;
use App\Models\Product;
use App\Models\Seller;
use App\Services\New\ProductRetailService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class RetailProductFormTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    protected function setUp(): void
    {
        $this->skipWhenApiDatabaseUnavailable();

        parent::setUp();

        $this->rememberLanguageFiles();
    }

    protected function tearDown(): void
    {
        $this->restoreLanguageFiles();

        parent::tearDown();
    }

    public function test_admin_adds_a_retail_product_and_the_row_matches_the_payload(): void
    {
        $payload = $this->addPayload('ADMINADD1');

        $response = $this->actingAs($this->admin(), 'admin')->post('admin/retail/products/add', $payload);

        $stored = $this->assertStoredFromPayload($response, $payload, 'admin');

        $this->assertSame((float) $payload['shipping_cost'], (float) $stored->shipping_cost);
    }

    public function test_vendor_adds_a_retail_product_and_the_row_matches_the_payload(): void
    {
        $payload = $this->addPayload('VENDORADD1');

        $response = $this->actingAs($this->seller(), 'seller')->post('vendor/retail/products/add', $payload);

        $stored = $this->assertStoredFromPayload($response, $payload, 'seller');

        $this->assertSame((float) $payload['shipping_cost'], (float) $stored->shipping_cost);
    }

    public function test_admin_adding_a_digital_product_links_the_submitted_authors_and_publishing_houses(): void
    {
        $payload = $this->digitalAddPayload('ADMINDIGITAL1', ['Author One ADMINDIGITAL1', 'Author Two ADMINDIGITAL1'], ['House One ADMINDIGITAL1']);

        $this->actingAs($this->admin(), 'admin')
            ->post('admin/retail/products/add', $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $product = Product::query()->where('code', 'ADMINDIGITAL1')->firstOrFail();

        $this->assertEqualsCanonicalizing(
            ['Author One ADMINDIGITAL1', 'Author Two ADMINDIGITAL1'],
            $this->linkedAuthorNames($product->id),
        );
        $this->assertSame(['House One ADMINDIGITAL1'], $this->linkedPublishingHouseNames($product->id));
    }

    public function test_admin_updating_a_digital_product_replaces_its_linked_authors_and_publishing_houses(): void
    {
        $admin = $this->admin();
        $addPayload = $this->digitalAddPayload('ADMINDIGITAL2', ['Original author ADMINDIGITAL2'], ['Original house ADMINDIGITAL2']);

        $this->actingAs($admin, 'admin')->post('admin/retail/products/add', $addPayload)->assertSessionHasNoErrors();

        $product = Product::query()->where('code', 'ADMINDIGITAL2')->firstOrFail();

        $updatePayload = $this->digitalAddPayload('ADMINDIGITAL2', ['Replacement author ADMINDIGITAL2'], []);

        $this->actingAs($admin, 'admin')
            ->post('admin/retail/products/update/'.$product->id, $updatePayload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertSame(['Replacement author ADMINDIGITAL2'], $this->linkedAuthorNames($product->id));
        $this->assertSame([], $this->linkedPublishingHouseNames($product->id));
    }

    public function test_admin_adding_a_product_writes_translation_rows_for_a_second_locale(): void
    {
        $locale = $this->secondLocaleOrSkip();

        $payload = $this->addPayload('ADMINLANG1');
        $payload['lang'] = ['en', $locale];
        $payload['name'] = [$payload['name'][0], 'Translated name ADMINLANG1'];
        $payload['description'] = [$payload['description'][0], '<p>Translated description ADMINLANG1</p>'];

        $response = $this->actingAs($this->admin(), 'admin')->post('admin/retail/products/add', $payload);

        $stored = $this->assertStoredFromPayload($response, $payload, 'admin');

        $translations = $this->productTranslations($stored->id, $locale);

        $this->assertNotEmpty($translations, 'no translation rows were written for the second locale');
        $this->assertEqualsCanonicalizing(['description', 'name'], $translations->pluck('key')->unique()->sort()->values()->all());
        $this->assertSame('Translated name ADMINLANG1', $translations->firstWhere('key', 'name')->value);
        $this->assertSame('<p>Translated description ADMINLANG1</p>', $translations->firstWhere('key', 'description')->value);

        $this->assertSame(0, $this->productTranslations($stored->id, 'en')->count(), 'the english locale should never be written');
    }

    public function test_admin_updating_a_product_overwrites_translation_rows_instead_of_duplicating_them(): void
    {
        $locale = $this->secondLocaleOrSkip();

        $admin = $this->admin();
        $addPayload = $this->addPayload('ADMINLANG2');
        $addPayload['lang'] = ['en', $locale];
        $addPayload['name'] = [$addPayload['name'][0], 'Original translated name'];
        $addPayload['description'] = [$addPayload['description'][0], '<p>Original translated description</p>'];

        $this->actingAs($admin, 'admin')->post('admin/retail/products/add', $addPayload)->assertSessionHasNoErrors();

        $product = Product::query()->where('code', 'ADMINLANG2')->firstOrFail();

        $countBeforeUpdate = $this->productTranslations($product->id, $locale)->count();

        $updatePayload = $this->updatePayload($product);
        $updatePayload['lang'] = ['en', $locale];
        $updatePayload['name'] = [$updatePayload['name'][0], 'Updated translated name'];
        $updatePayload['description'] = [$updatePayload['description'][0], '<p>Updated translated description</p>'];

        $this->actingAs($admin, 'admin')
            ->post('admin/retail/products/update/'.$product->id, $updatePayload)
            ->assertSessionHasNoErrors();

        $translationsAfterUpdate = $this->productTranslations($product->id, $locale);

        $this->assertSame($countBeforeUpdate, $translationsAfterUpdate->count(), 'the update duplicated translation rows instead of overwriting them');
        $this->assertSame('Updated translated name', $translationsAfterUpdate->firstWhere('key', 'name')->value);
        $this->assertSame('<p>Updated translated description</p>', $translationsAfterUpdate->firstWhere('key', 'description')->value);
    }

    public function test_admin_updates_a_retail_product_and_the_row_matches_the_payload(): void
    {
        $admin = $this->admin();
        $product = $this->addProductAs($admin, 'admin', 'admin/retail/products/add', 'ADMINEDIT1');
        $payload = $this->updatePayload($product);

        $response = $this->actingAs($admin, 'admin')
            ->post('admin/retail/products/update/'.$product->id, $payload);

        $stored = $this->assertUpdatedFromPayload($response, $payload, $product->id);

        $this->assertSame((float) $payload['shipping_cost'], (float) $stored->shipping_cost);
    }

    public function test_vendor_updates_a_retail_product_and_the_row_matches_the_payload(): void
    {
        $seller = $this->seller();
        $product = $this->addProductAs($seller, 'seller', 'vendor/retail/products/add', 'VENDOREDIT1');
        $payload = $this->updatePayload($product);

        $response = $this->actingAs($seller, 'seller')
            ->post('vendor/retail/products/update/'.$product->id, $payload);

        $stored = $this->assertUpdatedFromPayload($response, $payload, $product->id);

        $this->assertContains(
            (float) $payload['shipping_cost'],
            [(float) $stored->shipping_cost, (float) $stored->temp_shipping_cost],
            'the posted shipping cost reached neither the live nor the pending column',
        );
    }

    public function test_admin_keeps_colours_variations_and_colour_images_on_a_colour_variation_product(): void
    {
        $this->assertColourVariationSurvives($this->admin(), 'admin', 'admin/retail/products/add', 'ADMINCOLOR1');
    }

    public function test_vendor_keeps_colours_variations_and_colour_images_on_a_colour_variation_product(): void
    {
        $this->assertColourVariationSurvives($this->seller(), 'seller', 'vendor/retail/products/add', 'VENDORCOLOR1');
    }

    private function assertColourVariationSurvives(object $user, string $guard, string $uri, string $code): void
    {
        Storage::fake('public');

        [$payload, $colourNames] = $this->colourVariationPayload($code);

        $this->actingAs($user, $guard)->post($uri, $payload)->assertSessionHasNoErrors();

        $product = Product::query()->where('code', $code)->firstOrFail();
        $variations = json_decode($product->retailDetail?->variation ?? '[]', true);
        $colourImages = json_decode($product->retailDetail?->color_image ?? '[]', true) ?: [];

        $this->assertSame($payload['colors'], json_decode($product->getRawOriginal('colors'), true));

        $this->assertSame(
            [$colourNames[0].'-S', $colourNames[0].'-M', $colourNames[1].'-S', $colourNames[1].'-M'],
            array_column($variations, 'type'),
        );

        foreach ($variations as $variation) {
            $this->assertSame(300.0, (float) $variation['price']);
            $this->assertSame(5, (int) $variation['qty']);
            $this->assertNotEmpty($variation['sku']);
        }

        $withColour = array_values(array_filter(
            $colourImages,
            static fn (array $image): bool => $image['color'] !== null,
        ));

        $this->assertCount(2, $withColour);
        $this->assertSame(
            array_map(static fn (string $code): string => ltrim($code, '#'), $payload['colors']),
            array_column($withColour, 'color'),
        );

        $this->assertEditScreenShowsTheVariationSetup($guard, $product);

        $this->assertColourVariationSurvivesAnEdit($user, $guard, $product, $payload, $colourNames, $withColour);
    }

    private function assertEditScreenShowsTheVariationSetup(string $guard, Product $product): void
    {
        $panel = $guard === 'admin' ? 'admin-views' : 'vendor-views';

        $loaded = app(ProductRetailService::class)->findForEdit($product->id, ['digitalVariation', 'translations', 'seoInfo']);
        $loaded['colors'] = json_decode($loaded['colors']);

        $html = view($panel.'.product.update._product-variation-setup', [
            'product' => $loaded,
            'colors' => Color::query()->orderBy('name')->get(),
            'attributes' => Attribute::query()->orderBy('name')->get(),
            'digitalProductFileTypes' => ['audio', 'video', 'document', 'software'],
        ])->render();

        $this->assertMatchesRegularExpression('/product_variation_toggle[^>]*checked/', $html, 'the variation toggle renders off');
        $this->assertMatchesRegularExpression('/product-color-switcher[^>]*checked|checked[^>]*product-color-switcher/', $html, 'the colour switcher renders off');
        $this->assertSame(2, preg_match_all('/<option[^>]*value="?#[0-9A-Fa-f]{6}"?[^>]*selected/', $html), 'the saved colours are not preselected');
        $this->assertSame(4, substr_count($html, 'name="sku_'), 'the variation pricing and stock rows are missing');

        $colourImages = json_decode($loaded->retailDetail?->color_image ?? '[]', true) ?: [];

        foreach (array_filter(array_column($colourImages, 'color')) as $colour) {
            $this->assertStringContainsString($colour, $html === '' ? '' : json_encode($loaded->color_images_full_url), 'a colour image is missing from the edit screen data');
        }
    }

    private function assertColourVariationSurvivesAnEdit(
        object $user,
        string $guard,
        Product $product,
        array $payload,
        array $colourNames,
        array $colourImages,
    ): void {
        $editPayload = $payload;
        $editPayload['colors_active'] = '1';
        $editPayload['existing_thumbnail'] = $product->thumbnail;
        $editPayload['existing_images'] = [];

        foreach ($colourImages as $image) {
            $editPayload['color_image_'.$image['color']] = [$image['image_name']];
        }

        $uri = $guard === 'admin'
            ? 'admin/retail/products/update/'.$product->id
            : 'vendor/retail/products/update/'.$product->id;

        $this->actingAs($user, $guard)->post($uri, $editPayload)->assertSessionHasNoErrors();

        $edited = Product::query()->find($product->id);
        $editedVariations = json_decode($edited->retailDetail?->variation ?? '[]', true);
        $editedColourImages = json_decode($edited->retailDetail?->color_image ?? '[]', true) ?: [];

        $this->assertSame($payload['colors'], json_decode($edited->getRawOriginal('colors'), true));
        $this->assertSame(
            [$colourNames[0].'-S', $colourNames[0].'-M', $colourNames[1].'-S', $colourNames[1].'-M'],
            array_column($editedVariations, 'type'),
        );
        $this->assertSame(
            array_column($colourImages, 'color'),
            array_values(array_filter(array_column($editedColourImages, 'color'))),
        );
    }

    private function colourVariationPayload(string $code): array
    {
        $colours = Color::query()->orderBy('id')->limit(2)->get();
        $attribute = Attribute::query()->orderBy('id')->firstOrFail();

        $payload = $this->addPayload($code) + [
            'colors_active' => '',
            'colors' => [$colours[0]->code, $colours[1]->code],
            'choice_no' => [(string) $attribute->id],
            'choice' => ['Size'],
            'choice_options_'.$attribute->id => ['S,M'],
        ];

        $payload['choice_attributes'] = [(string) $attribute->id];
        $payload['current_stock'] = 0;
        $payload['unit_price'] = 300;

        foreach ($colours as $colour) {
            foreach (['S', 'M'] as $size) {
                $key = str_replace([' ', '.'], ['', '_'], $colour->name.'-'.$size);
                $payload['price_'.$key] = 300;
                $payload['sku_'.$key] = strtolower($key);
                $payload['qty_'.$key] = 5;
            }

            $payload['color_image_'.ltrim($colour->code, '#')] = UploadedFile::fake()->image($colour->name.'.png');
        }

        return [$payload, [$colours[0]->name, $colours[1]->name]];
    }

    private function assertStoredFromPayload(TestResponse $response, array $payload, string $addedBy): Product
    {
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $stored = Product::query()->where('code', $payload['code'])->first();

        $this->assertNotNull($stored, 'the product was not written to the database');
        $this->assertSame($addedBy, $stored->added_by);
        $this->assertPayloadColumns($payload, $stored);
        $this->assertNotNull($stored->tax_type);
        $this->assertNotNull($stored->tax_model);

        return $stored;
    }

    private function assertUpdatedFromPayload(TestResponse $response, array $payload, int $id): Product
    {
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $stored = Product::query()->find($id);

        $this->assertNotNull($stored);
        $this->assertPayloadColumns($payload, $stored);
        $this->assertNotNull($stored->tax_type);
        $this->assertNotNull($stored->tax_model);

        return $stored;
    }

    public function test_a_vendor_product_waits_for_admin_approval_while_new_product_approval_is_required(): void
    {
        $this->requireNewProductApproval();

        $payload = $this->addPayload('VENDORAPPROVAL1');

        $this->actingAs($this->seller(), 'seller')
            ->post('vendor/retail/products/add', $payload)
            ->assertRedirect();

        $stored = Product::query()->where('code', $payload['code'])->firstOrFail();

        $this->assertSame(ProductRetailService::REQUEST_STATUS_PENDING, (int) $stored->request_status);
        $this->assertSame(0, (int) $stored->status);
    }

    public function test_an_admin_product_is_approved_even_while_new_product_approval_is_required(): void
    {
        $this->requireNewProductApproval();

        $payload = $this->addPayload('ADMINAPPROVAL1');

        $this->actingAs($this->admin(), 'admin')
            ->post('admin/retail/products/add', $payload)
            ->assertRedirect();

        $stored = Product::query()->where('code', $payload['code'])->firstOrFail();

        $this->assertSame(ProductRetailService::REQUEST_STATUS_APPROVED, (int) $stored->request_status);
        $this->assertSame(1, (int) $stored->status);
    }

    public function test_the_ajax_pre_flight_answers_a_valid_product_form_with_an_empty_body(): void
    {
        [$payload] = $this->colourVariationPayload('VENDORPREFLIGHT1');

        $response = $this->actingAs($this->seller(), 'seller')
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('vendor/retail/products/add', $payload);

        $response->assertOk();
        $this->assertSame([], $response->json());
        $this->assertNull(Product::query()->where('code', $payload['code'])->first());
    }

    public function test_the_ajax_pre_flight_answers_an_invalid_product_form_with_readable_messages(): void
    {
        [$payload] = $this->colourVariationPayload('VENDORPREFLIGHT2');
        $payload['code'] = 'AB';

        $response = $this->actingAs($this->seller(), 'seller')
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->post('vendor/retail/products/add', $payload);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => [['error_code', 'message']]]);
        $this->assertNotEmpty($response->json('errors.0.message'));
    }

    private function requireNewProductApproval(): void
    {
        $this->assertSame(
            '1',
            (string) getWebConfig(name: 'new_product_approval'),
            'the new_product_approval setting is missing — run php artisan migrate',
        );
    }

    private function assertPayloadColumns(array $payload, Product $stored): void
    {
        $this->assertSame($payload['name'][0], $stored->name);
        $this->assertSame($payload['code'], $stored->code);
        $this->assertSame((int) $payload['category_id'], (int) $stored->category_id);
        $this->assertSame($payload['product_type'], $stored->product_type);
        $this->assertSame($payload['unit'], $stored->unit);
        $this->assertSame((float) $payload['unit_price'], (float) $stored->unit_price);
        $this->assertSame((float) $payload['discount'], (float) $stored->discount);
        $this->assertSame($payload['discount_type'], $stored->discount_type);
        $this->assertSame((int) $payload['minimum_order_qty'], (int) $stored->minimum_order_qty);
        $this->assertSame((int) $payload['current_stock'], (int) $stored->current_stock);
        $this->assertSame($payload['meta_title'], $stored->meta_title);
        $this->assertSame($payload['meta_description'], $stored->meta_description);
        $this->assertStringContainsString(strip_tags($payload['description'][0]), (string) $stored->details);
    }

    public function test_a_product_is_added_when_the_form_omits_every_optional_field(): void
    {
        foreach (['admin' => 'admin/retail/products/add', 'seller' => 'vendor/retail/products/add'] as $addedBy => $url) {
            $code = strtoupper($addedBy).'MINIMAL1';
            $actor = $addedBy === 'admin' ? $this->admin() : $this->seller();

            $this->actingAs($actor, $addedBy === 'admin' ? 'admin' : 'seller')
                ->post($url, $this->minimalAddPayload($code))
                ->assertSessionHasNoErrors()
                ->assertRedirect();

            $stored = Product::query()->where('code', $code)->first();

            $this->assertNotNull($stored, "the {$addedBy} product was not stored from a payload without optional fields");
            $this->assertNull($stored->sub_category_id);
            $this->assertNull($stored->sub_sub_category_id);
            $this->assertNull($stored->brand_id);
            $this->assertSame(ProductRetailService::DISCOUNT_TYPE_FLAT, $stored->discount_type);
            $this->assertNull($stored->video_url);
            $this->assertNull($stored->meta_title);
            $this->assertSame([['id' => (string) $this->categoryId(), 'position' => 1]], json_decode($stored->category_ids, true));
        }
    }

    public function test_a_product_is_updated_when_the_form_omits_every_optional_field(): void
    {
        $seller = $this->seller();

        $this->actingAs($seller, 'seller')
            ->post('vendor/retail/products/add', $this->minimalAddPayload('SELLERMINIMAL2'))
            ->assertSessionHasNoErrors();

        $product = Product::query()->where('code', 'SELLERMINIMAL2')->firstOrFail();

        $payload = $this->minimalAddPayload('SELLERMINIMAL2');
        $payload['name'] = ['Minimal product renamed'];
        $payload['unit_price'] = 300;

        $this->actingAs($seller, 'seller')
            ->post('vendor/retail/products/update/'.$product->id, $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $updated = $product->fresh();

        $this->assertSame('Minimal product renamed', $updated->getRawOriginal('name'));
        $this->assertNull($updated->sub_category_id);
        $this->assertNull($updated->sub_sub_category_id);
        $this->assertSame(ProductRetailService::DISCOUNT_TYPE_FLAT, $updated->discount_type);
    }

    private function minimalAddPayload(string $code): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Minimal product '.$code],
            'description' => ['<p>Minimal description for '.$code.'</p>'],
            'code' => $code,
            'category_id' => $this->categoryId(),
            'unit' => 'pc',
            'product_type' => 'physical',
            'unit_price' => 250,
            'discount' => 0,
            'minimum_order_qty' => 1,
            'shipping_cost' => 0,
            'existing_thumbnail' => 'def.png',
            'existing_images' => ['def.png'],
            'tax_ids' => $this->taxIds(),
        ];
    }

    private function addPayload(string $code): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Verification product '.$code],
            'description' => ['<p>Verification description for '.$code.'</p>'],
            'code' => $code,
            'category_id' => $this->categoryId(),
            'sub_category_id' => null,
            'sub_sub_category_id' => null,
            'brand_id' => null,
            'unit' => 'pc',
            'product_type' => 'physical',
            'digital_product_type' => null,
            'unit_price' => 250,
            'discount' => 10,
            'discount_type' => 'flat',
            'current_stock' => 40,
            'minimum_order_qty' => 2,
            'shipping_cost' => 15,
            'video_url' => null,
            'meta_title' => 'Meta title '.$code,
            'meta_description' => 'Meta description '.$code,
            'existing_thumbnail' => 'def.png',
            'existing_images' => ['def.png'],
            'choice_attributes' => [],
            'tags' => '',
            'tax_ids' => $this->taxIds(),
        ];
    }

    private function digitalAddPayload(string $code, array $authors, array $publishingHouses): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Verification product '.$code],
            'description' => ['<p>Verification description for '.$code.'</p>'],
            'code' => $code,
            'category_id' => $this->categoryId(),
            'sub_category_id' => null,
            'sub_sub_category_id' => null,
            'brand_id' => null,
            'product_type' => 'digital',
            'digital_product_type' => 'ready_after_sell',
            'unit_price' => 250,
            'discount' => 10,
            'discount_type' => 'flat',
            'minimum_order_qty' => 2,
            'shipping_cost' => 0,
            'video_url' => null,
            'meta_title' => 'Meta title '.$code,
            'meta_description' => 'Meta description '.$code,
            'existing_thumbnail' => 'def.png',
            'existing_images' => ['def.png'],
            'choice_attributes' => [],
            'tags' => '',
            'tax_ids' => $this->taxIds(),
            'authors' => $authors,
            'publishing_house' => $publishingHouses,
        ];
    }

    private function linkedAuthorNames(int $productId): array
    {
        return DB::table('digital_product_authors')
            ->join('authors', 'authors.id', '=', 'digital_product_authors.author_id')
            ->where('digital_product_authors.product_id', $productId)
            ->pluck('authors.name')
            ->all();
    }

    private function linkedPublishingHouseNames(int $productId): array
    {
        return DB::table('digital_product_publishing_houses')
            ->join('publishing_houses', 'publishing_houses.id', '=', 'digital_product_publishing_houses.publishing_house_id')
            ->where('digital_product_publishing_houses.product_id', $productId)
            ->pluck('publishing_houses.name')
            ->all();
    }

    private function updatePayload(Product $product): array
    {
        return [
            'lang' => ['en'],
            'name' => ['Updated '.$product->code],
            'description' => ['<p>Updated description for '.$product->code.'</p>'],
            'code' => $product->code,
            'category_id' => $this->categoryId(),
            'sub_category_id' => null,
            'sub_sub_category_id' => null,
            'brand_id' => null,
            'unit' => 'kg',
            'product_type' => 'physical',
            'digital_product_type' => null,
            'unit_price' => 399,
            'discount' => 20,
            'discount_type' => 'flat',
            'current_stock' => 77,
            'minimum_order_qty' => 3,
            'shipping_cost' => 25,
            'video_url' => null,
            'meta_title' => 'Updated meta title',
            'meta_description' => 'Updated meta description',
            'existing_thumbnail' => $product->thumbnail,
            'existing_images' => [],
            'choice_attributes' => [],
            'tags' => '',
            'tax_ids' => $this->taxIds(),
        ];
    }

    private function secondLocaleOrSkip(): string
    {
        $languages = getWebConfig(name: 'pnc_language') ?? [];
        $locale = collect($languages)->first(static fn (string $code): bool => $code !== 'en');

        if ($locale === null) {
            $this->markTestSkipped('Test database has no second language configured.');
        }

        return $locale;
    }

    private function productTranslations(int $productId, string $locale): Collection
    {
        return DB::table('translations')
            ->where('translationable_type', Product::class)
            ->where('translationable_id', $productId)
            ->where('locale', $locale)
            ->get();
    }

    private function taxIds(): array
    {
        return DB::table('taxes')->where('is_active', 1)->where('is_default', 1)->limit(1)->pluck('id')->all();
    }

    private function categoryId(): int
    {
        return (int) DB::table('categories')->where('position', 0)->value('id');
    }

    private function admin(): Admin
    {
        return Admin::query()->orderBy('id')->firstOrFail();
    }

    private function seller(): Seller
    {
        return Seller::query()->where('status', 'approved')->firstOrFail();
    }

    private function addProductAs(object $user, string $guard, string $uri, string $code): Product
    {
        $this->actingAs($user, $guard)->post($uri, $this->addPayload($code))->assertRedirect();

        return Product::query()->where('code', $code)->firstOrFail();
    }
}
