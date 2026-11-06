<?php

namespace Tests\Feature\Product;

use App\Models\Seller;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Modules\Wholesale\app\Services\Product\AttributeImageService;
use Modules\Wholesale\app\Services\Product\ProductWholesaleService;
use Modules\Wholesale\app\Utils\ProductWizardFormState;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleProductImageValidationTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;
    use RequiresWholesaleAddon;

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

    public function test_a_thumbnail_above_the_advertised_limit_is_rejected(): void
    {
        $this->postImageMediaStep([
            'image' => $this->fakeImage($this->limitInKilobytes() + 1),
            'images' => [$this->fakeImage(64)],
        ])->assertSessionHasErrors('image');
    }

    public function test_an_additional_image_above_the_advertised_limit_is_rejected(): void
    {
        $this->postImageMediaStep([
            'image' => $this->fakeImage(64),
            'images' => [$this->fakeImage($this->limitInKilobytes() + 1)],
        ])->assertSessionHasErrors('images.0');
    }

    public function test_an_attribute_image_above_the_advertised_limit_is_rejected(): void
    {
        $this->postImageMediaStep([
            'image' => $this->fakeImage(64),
            'images' => [$this->fakeImage(64)],
            'attribute_images' => [[
                'type' => AttributeImageService::TYPE_COLOR,
                'value' => 'IndianRed',
                'images' => [$this->fakeImage($this->limitInKilobytes() + 1)],
            ]],
        ])->assertSessionHasErrors('attribute_images.0.images.0');
    }

    public function test_images_at_the_advertised_limit_are_accepted(): void
    {
        $this->postImageMediaStep([
            'image' => $this->fakeImage($this->limitInKilobytes()),
            'images' => [$this->fakeImage($this->limitInKilobytes())],
        ])->assertSessionHasNoErrors();
    }

    private function limitInKilobytes(): int
    {
        return getFileUploadMaxSize(unit: 'kb', limit: ProductWholesaleService::IMAGE_MAX_SIZE_MB);
    }

    private function fakeImage(int $kilobytes): UploadedFile
    {
        return UploadedFile::fake()->create('wholesale-fixture.jpg', $kilobytes, 'image/jpeg');
    }

    private function postImageMediaStep(array $files)
    {
        $seller = $this->seller();
        $token = $this->mintedToken($seller);

        return $this->actingAs($seller, 'seller')->post('vendor/wholesale/products/step', [
            ...$files,
            'video_provider' => 'youtube_link',
            'step' => ProductWizardFormState::STEP_IMAGE_MEDIA,
            'token' => $token,
            'form_state' => '',
        ]);
    }

    private function mintedToken(Seller $seller): string
    {
        $response = $this->actingAs($seller, 'seller')->get('vendor/wholesale/products/add');

        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        return (string) ($query['token'] ?? '');
    }

    private function seller(): Seller
    {
        return Seller::query()->where('status', 'approved')->firstOrFail();
    }
}
