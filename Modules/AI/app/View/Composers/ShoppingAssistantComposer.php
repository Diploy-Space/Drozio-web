<?php

namespace Modules\AI\app\View\Composers;

use Illuminate\View\View;
use Modules\AI\app\Services\New\AISettingService;

class ShoppingAssistantComposer
{
    private const ASSET_CSS_PATH = 'assets/front-end/ai/ai-shopping-assistant.css';

    private const ASSET_JS_PATH = 'assets/front-end/ai/ai-shopping-assistant.js';

    private const MAX_INPUT_LENGTH = 300;

    public function __construct(protected readonly AISettingService $aiSettingService) {}

    public function compose(View $view): void
    {
        $view->with('shoppingAssistant', $this->build());
    }

    /** @return array<string, mixed> */
    protected function build(): array
    {
        if (! $this->isVisible()) {
            return ['isVisible' => false];
        }

        $customer = auth('customer')->user();

        return [
            'isVisible' => true,
            'cartMode' => $this->cartMode(),
            'cssVersion' => $this->assetVersion(self::ASSET_CSS_PATH),
            'jsVersion' => $this->assetVersion(self::ASSET_JS_PATH),
            'routeSessions' => route($this->routePrefix().'sessions.start'),
            'routeSessionsList' => route($this->routePrefix().'sessions.list'),
            'routeSessionBase' => $this->sessionBaseUrl(),
            'routeUploadImage' => route($this->routePrefix().'upload-image'),
            'routeCartAdd' => route($this->cartAddRouteName()),
            'routeCheckout' => route($this->checkoutRouteName()),
            'loginUrl' => route('customer.auth.login'),
            'productUrlBase' => $this->productUrlBase(),
            'auth' => auth('customer')->check(),
            'customerId' => $customer?->id,
            'userAvatar' => $customer ? getStorageImages(path: $customer->image_full_url, type: 'avatar') : '',
            'direction' => session('direction', 'ltr'),
            'locale' => app()->getLocale(),
            'introMessage' => $this->introMessage(),
            'suggestedPrompts' => $this->suggestedPrompts(),
            'maxInputLength' => self::MAX_INPUT_LENGTH,
            'text' => $this->text(),
        ];
    }

    protected function isVisible(): bool
    {
        return (bool) getCheckAddonPublishedStatus(moduleName: $this->addonName())
            && getWebConfig(name: $this->statusConfigKey()) == 1
            && $this->aiSettingService->hasActiveProvider();
    }

    protected function addonName(): string
    {
        return 'AI';
    }

    protected function statusConfigKey(): string
    {
        return 'ai_shopping_assistant_status';
    }

    protected function routePrefix(): string
    {
        return 'ai.assistant.';
    }

    protected function sessionBaseUrl(): string
    {
        return url('ai/assistant/sessions');
    }

    protected function cartAddRouteName(): string
    {
        return 'cart.add';
    }

    protected function checkoutRouteName(): string
    {
        return 'checkout-details';
    }

    protected function productUrlBase(): string
    {
        return url('product');
    }

    protected function cartMode(): string
    {
        return 'retail';
    }

    protected function introMessage(): string
    {
        return translate('Hi I am your shopping assistant at').' '
            .(getWebConfig(name: 'company_name') ?? config('app.name')).'. '
            .translate('I find products and fill your cart. What are you looking for?');
    }

    /** @return array<int, string> */
    protected function suggestedPrompts(): array
    {
        return [
            translate('Show me todays best deals'),
            translate('Suggest a good phone under 1000'),
            translate('What brands do you have'),
            translate('Show me trending products'),
        ];
    }

    /** @return array<string, string> */
    protected function text(): array
    {
        return [
            'addToCart' => translate('Add To Cart'),
            'buyNow' => translate('Buy Now'),
            'addedCart' => translate('Added to your cart'),
            'cartFailed' => translate('Could not add this item Please try again'),
            'checkoutConfirm' => translate('Ready to place your order'),
            'proceedCheckout' => translate('Proceed to Checkout'),
            'keepShopping' => translate('Keep shopping'),
            'addingCart' => translate('Adding to your cart'),
            'redirecting' => translate('Redirecting to checkout'),
            'color' => translate('Color'),
            'format' => translate('Format'),
            'qty' => translate('Quantity'),
            'quantity' => translate('Quantity'),
            'close' => translate('Close'),
            'conversation' => translate('Conversation'),
            'only' => translate('Only'),
            'left' => translate('Left'),
            'add' => translate('Add'),
            'more' => translate('More'),
            'justNow' => translate('Just now'),
            'minutesAgo' => translate('Minutes ago'),
            'hoursAgo' => translate('Hours ago'),
            'daysAgo' => translate('Days ago'),
            'loginBuy' => translate('Please log in to buy'),
            'loginCart' => translate('Please log in to add to cart'),
            'noConvos' => translate('No conversations yet'),
            'gibberish' => translate('I did not catch that. Could you describe the product?'),
            'writeMessage' => translate('Please write a message before sending'),
            'imageInvalid' => translate('Only JPG JPEG PNG or WEBP images are allowed'),
            'imageTooLarge' => translate('Image must be 5 MB or smaller'),
            'rateLimited' => translate('You are sending messages too fast. Please wait and try again.'),
            'sessionGone' => translate('This conversation is no longer available. Starting a new chat.'),
            'sessionExpired' => translate('Your session has expired. Please refresh the page and try again'),
            'genericError' => translate('Something went wrong Please try again'),
            'imageDropped' => translate('I couldn\'t attach your image, so I\'ll answer based on your message'),
            'outOfStock' => translate('Out of Stock'),
            'minNotMet' => translate('Minimum order not met'),
            'delete' => translate('Delete conversation'),
            'suggestedHeading' => translate('Try asking'),
            'productHint' => translate('Tip: click a product\'s name on its card to view full details'),
            'inputLimit' => translate('You have reached the 300 character limit'),
            'deleteConfirmTitle' => translate('Delete conversation'),
            'deleteConfirmText' => translate('This chat will be permanently removed. This action cannot be undone.'),
            'deleteConfirmYes' => translate('Yes, Delete it'),
            'cancel' => translate('Cancel'),
        ];
    }

    private function assetVersion(string $relativePath): ?int
    {
        $path = public_path($relativePath);

        return is_file($path) ? filemtime($path) : null;
    }
}
