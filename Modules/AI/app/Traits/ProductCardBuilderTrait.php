<?php

namespace Modules\AI\app\Traits;

use Illuminate\Database\Eloquent\Model;
use Modules\AI\app\Http\Resources\ShoppingAssistant\ProductCardResource;

trait ProductCardBuilderTrait
{
    /** @param array<int, Model> $products @return array<int, array> */
    protected function buildCards(array $products): array
    {
        ProductCardResource::loadDigitalVariations($products);

        $colorNameMap = $this->colorService->getNameMapForCodes(ProductCardResource::collectColorCodes($products));

        return array_map(fn (Model $product) => $this->buildCard($product, $colorNameMap), $products);
    }

    protected function buildCard(Model $product, array $colorNameMap): array
    {
        return (new ProductCardResource($product, $colorNameMap))->resolve();
    }
}
