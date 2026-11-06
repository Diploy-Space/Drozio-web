<?php

namespace Tests\Feature\Checkout;

use App\Traits\Payment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
use ReflectionClass;
use Tests\Feature\Api\Support\InteractsWithApiTestEnvironment;
use Tests\TestCase;

class DigitalPaymentRedirectTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithApiTestEnvironment;

    private const CORE_GATEWAYS = [
        'ssl_commerz',
        'stripe',
        'paymob_accept',
        'flutterwave',
        'paytm',
        'paypal',
        'paytabs',
        'liqpay',
        'razor_pay',
        'senang_pay',
        'mercadopago',
        'bkash',
        'paystack',
    ];

    private const SSL_COMMERZ_CALLBACK_PATHS = [
        'payment/sslcommerz/success',
        'payment/sslcommerz/failed',
        'payment/sslcommerz/canceled',
    ];

    public function test_every_core_gateway_redirect_target_is_a_registered_route(): void
    {
        $registeredPaths = $this->registeredPaths();
        $gatewayPaths = $this->gatewayRedirectPaths();
        $unreachable = [];

        foreach (self::CORE_GATEWAYS as $gateway) {
            $path = $gatewayPaths[$gateway] ?? null;

            if ($path === null) {
                $unreachable[] = $gateway.' is missing from the redirect map';

                continue;
            }

            if (! array_key_exists($path, $registeredPaths)) {
                $unreachable[] = $gateway.' => /'.$path;
            }
        }

        $this->assertSame([], $unreachable, 'A customer sent to these gateways would land on a 404.');
    }

    public function test_the_gateway_redirect_targets_are_not_behind_the_legacy_prefix(): void
    {
        foreach ($this->gatewayRedirectPaths() as $gateway => $path) {
            $this->assertStringStartsWith('payment/', $path, $gateway.' no longer points at the payment prefix.');
        }

        foreach (array_keys($this->registeredPaths()) as $path) {
            $this->assertStringStartsNotWith('old/payment/', $path, 'A gateway route is still mounted under the legacy prefix.');
        }
    }

    public function test_the_ssl_commerz_callback_paths_the_gateway_is_given_are_registered(): void
    {
        $registeredPaths = $this->registeredPaths();

        foreach (self::SSL_COMMERZ_CALLBACK_PATHS as $path) {
            $this->assertArrayHasKey($path, $registeredPaths, 'SSLCommerz posts to /'.$path.', so it must resolve.');
            $this->assertContains('POST', $registeredPaths[$path], '/'.$path.' must accept the gateway POST.');
        }
    }

    /** @return array<string, array<int, string>> */
    private function registeredPaths(): array
    {
        $paths = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            if (! str_contains($route->uri(), 'payment/')) {
                continue;
            }

            $paths[$route->uri()] = array_merge($paths[$route->uri()] ?? [], $route->methods());
        }

        return $paths;
    }

    /** @return array<string, string> */
    private function gatewayRedirectPaths(): array
    {
        $source = (string) file_get_contents((new ReflectionClass(Payment::class))->getFileName());

        preg_match_all("/'([a-z0-9_]+)' => '(payment\/[^']+)'/", $source, $matches);

        return array_combine($matches[1], $matches[2]);
    }
}
