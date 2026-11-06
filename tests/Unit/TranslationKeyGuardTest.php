<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Locks in what translate() may write into resources/lang.
 *
 * Every case here came from a real key found in the language files, so a change to
 * isPersistableTranslationKey() that reintroduces one of these shapes fails the build rather than
 * quietly refilling the admin Language table.
 */
class TranslationKeyGuardTest extends TestCase
{
    #[DataProvider('rejectedKeys')]
    public function test_it_rejects_untranslatable_shapes(string $key): void
    {
        $this->assertFalse(
            isPersistableTranslationKey($key),
            "Expected [$key] to be rejected, but the guard would persist it."
        );
    }

    #[DataProvider('acceptedKeys')]
    public function test_it_keeps_real_copy(string $key): void
    {
        $this->assertTrue(
            isPersistableTranslationKey($key),
            "Expected [$key] to be kept, but the guard rejected it."
        );
    }

    public static function rejectedKeys(): array
    {
        return self::named([
            // markup, code and escape leakage
            '{{ $product->name }}', '<div class="row">', 'document.getElementById', '\\s Free Trial',
            // machine data shapes
            'index.blade.php', 'ws://178.128.117.0', '127.0.0.1:8000', 'Trip Started (EN)',
            '23 Jul, 2023 3:34 am', '2. 3USB Head Phone x 1',
            // navigation through another product
            'Google Analytics → Admin → Data streams',
            // calendar, protocol, brand and gateway tokens
            'Tuesday', 'Jan', 'smtp', 'json', 'LTR', 'VIN', 'ios', 'android', 'Apple', 'stripe',
            'paystack', 'twilio', 'nexmo', 'msg91', 'ssl commerz', 'razor pay', 'flutterwave',
            // rendered validation output
            'The email field is required.', 'The selected zone id is invalid.',
            'The is default field must be either 0 or 1.', 'field is required',
            'The :field field is required', 'The password must be at least :min characters',
            // masks, mash, generated identifiers and placeholders
            'xxxxx xxxxxx', 'GNR5QQ7', '6valley-admin-demo-jhisdfhisufjifjfijqw5467',
            'ABC', 'XYZ', 'abcd', 'asdf', 'lorem ipsum', 'dummy',
            // fragments and notation
            '(Ratio 2:1)', '(or press Enter).', '(Ex: abc company)', 'A', 'A - Z', '1',
            '1st', '2nd', '404 Logs', '24/7', '100%',
            // figure-led marketing badges — the figure is the subject, only the words are copy
            '100% Authentic Products', '24/7 Support', '2 Factor',
            // placeholder-led stems — the injected subject is the copy, the frame is not
            ':field is required', ':item added successfully', ':item updated successfully',
            ':item deleted successfully', ':item copied successfully', ':item',
            // figures, units, quantities and prices
            'Ratio 1:1', 'Image Size Min 615 x 350 px', 'At least 8 characters', 'Maximum 2 MB',
            '587 for TLS, 465 for SSL.', '100 - 1000 pcs', '500 gm', '2 - 5 units',
            '$10 (Multiply with quantity)', '$5,465', '24 hours', '4 to 6 days', 'Size : 30',
            'total file size no more than2MB',
            // media size constraints
            'Image Size : Max 2 MB', 'Logo Size : Max 1 MB', 'Icon size : max 500 KB',
            'The image may not be greater than 2 MB', 'Category image must not exceed 2mb',
            'Image must be 5 MB or smaller', 'Thumbnail max 2 MB',
            // file format hints
            'jpeg, jpg, png', 'Supported video formats MP4 and WEBM',
            'pdf, doc, jpg. File size : max 2 MB',
            // demo person names shipped in the component gallery and sample forms
            'John Doe', 'Jhon Doe', 'Jhone', 'Jane Doe', 'Alex',
            // a URL is the subject of the hint it sits in, in or out of a sentence
            'https://api-hermes.pathao.com.np',
            'Leave blank to use https://api-hermes.pathao.com.np. Set this if Pathao issued a different host.',
            // credential fields name the value a gateway issued you, not a label anyone translates
            'Client Secret', 'Client ID', 'API Key', 'API Secret', 'Access Token', 'Template ID',
            'Secret key', 'Store Client Secret Key', 'Webhook Secret', 'sid',
            // country names, addresses and phone numbers are data, not copy
            'Egypt', 'Saudi Arabia', 'UAE', 'M\u{e9}xico', 'Brasil', 'Bangladesh',
            '221B Baker Street', '+880 1234 567890',
            // sample values and equations
            'Ex: XYZ store', 'Ex: John Doe', '4517 Washington Ave. Manchester, Kentucky 39495',
            'Gross Sales Amount = Product Price + Shipping Fee + VAT/Tax',
            'Grocery, eCommerce, Pharmacy, etc.',
        ]);
    }

    public static function acceptedKeys(): array
    {
        return self::named([
            // ordinary copy
            'Add new unit', 'Order details', 'Third party', 'Pending', 'Delivery Man',
            // ordinal terms are not badges — they translate, unlike a leading percent or ratio
            '3rd Party', '3rd Party Setup', '3rd Party SMS Gateways',
            '100% of the order amount will be refunded to the customer wallet',
            'Your order has been placed successfully', 'Minimum order amount', 'No orders found',
            // acronyms and labels that merely resemble a rejected shape
            'VAT', 'GST', 'SKU', 'Test', 'Demo', 'Placeholder', 'Size', 'Days', 'Address', 'Image',
            'John', 'Johnson', 'Jane', 'Alexander', 'Banner', 'Logo', 'Upload your product image',
            'Country', 'Select country', 'Country name', 'Street address',
            // ordinary password and identifier copy is not credential vocabulary end to end
            'Password', 'Forgot Password', 'New Password', 'Confirm Password', 'Change password',
            'Order ID', 'Transaction ID', 'Product key features',
            'Discount (%)', 'Ex',
            // prose that mentions something untranslatable
            'Create an html file named index.blade.php', 'We offer 24/7 support',
            '404 error page not found for this route', 'Example of a good product description',
            'Delivery within 3 days', 'Valid for 30 days', 'Upgrade to 10 GB storage for your shop',
            'Add the ?ref=123 parameter to your link', 'Go to Settings -> Save',
            'the maximum character limit is 200', 'mark the check box if the field is required',
            'Shown above the footer. One banner only. Max 2 MB, ratio 4:1.',
            // placeholders survive substitution, and one inside a sentence is not a led stem
            'Turn ON :method payment method', 'Refund window of :days days',
            'You have :count new orders',
        ]);
    }

    /** @param  array<int,string>  $keys */
    private static function named(array $keys): array
    {
        $cases = [];
        foreach ($keys as $key) {
            $cases[$key] = [$key];
        }

        return $cases;
    }
}
