<?php

namespace Tests\Feature\Product;

use App\Models\Seller;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Wholesale\app\Utils\ProductWizardFormState;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\Feature\Wholesale\Concerns\RequiresWholesaleAddon;
use Tests\TestCase;

class WholesaleProductSeoValidationTest extends TestCase
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

    public static function offeredImagePreviewSizes(): array
    {
        return [
            'large' => ['large'],
            'medium' => ['medium'],
            'small' => ['small'],
        ];
    }

    #[DataProvider('offeredImagePreviewSizes')]
    public function test_every_max_image_preview_size_the_form_offers_is_accepted(string $size): void
    {
        $this->postFaqSeoStep([
            'meta_max_image_preview' => '1',
            'meta_max_image_preview_value' => $size,
        ])->assertSessionHasNoErrors();
    }

    public function test_a_max_image_preview_size_outside_the_form_is_rejected(): void
    {
        $this->postFaqSeoStep([
            'meta_max_image_preview' => '1',
            'meta_max_image_preview_value' => 'standard',
        ])->assertSessionHasErrors('meta_max_image_preview_value');
    }

    private function postFaqSeoStep(array $fields)
    {
        $seller = $this->seller();
        $token = $this->mintedToken($seller);

        return $this->actingAs($seller, 'seller')->post('vendor/wholesale/products/step', [
            ...$fields,
            'meta_index' => 'index',
            'step' => ProductWizardFormState::STEP_FAQ_SEO,
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
