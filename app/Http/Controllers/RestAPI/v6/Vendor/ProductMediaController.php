<?php

namespace App\Http\Controllers\RestAPI\v6\Vendor;

use App\Http\Controllers\RestAPI\v6\BaseApiController;
use App\Http\Requests\Vendor\Product\DigitalProductDeleteRequest;
use App\Http\Requests\Vendor\Product\DigitalProductUploadRequest;
use App\Http\Requests\Vendor\Product\UploadImageRequest;
use App\Http\Resources\V6\Vendor\AuthorResource;
use App\Http\Resources\V6\Vendor\PublishingHouseResource;
use App\Services\New\AuthorService;
use App\Services\New\ProductRetailService;
use App\Services\New\PublishingHouseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\ApiPlayground\app\Attributes\ApiDoc;

class ProductMediaController extends BaseApiController
{
    private const DEFAULT_STORAGE = 'public';

    public function __construct(
        private readonly ProductRetailService $productService,
        private readonly AuthorService $authorService,
        private readonly PublishingHouseService $publishingHouseService,
    ) {}

    #[ApiDoc(
        summary: 'Upload a product, thumbnail or meta image before adding or updating a product',
        description: 'Returns the stored image name that the product add and update endpoints accept. With colors_active=true the name is also returned as a color image for the given color.',
        tag: 'Product Media (Seller)',
        status: [401, 422],
        guard: 'seller',
    )]
    public function uploadImages(UploadImageRequest $request): JsonResponse
    {
        $type = $request->validated('type');
        $imageName = $this->productService->storeImage(image: $request->file('image'), type: $type);

        return $this->responseFormatter(
            config: config('response.product_image_uploaded'),
            content: [
                'data' => [
                    'image_name' => $imageName,
                    'type' => $type,
                    'color_image' => $this->isColorImage($request) ? [
                        'color' => $request->filled('color') ? str_replace('#', '', (string) $request->input('color')) : null,
                        'image_name' => $imageName,
                    ] : null,
                    'storage' => config('filesystems.disks.default') ?? self::DEFAULT_STORAGE,
                ],
            ],
        );
    }

    #[ApiDoc(
        summary: 'Upload a ready digital product file before adding or updating a product',
        description: 'Returns the stored file name that the product add and update endpoints accept as digital_file_ready.',
        tag: 'Product Media (Seller)',
        status: [401, 422],
        guard: 'seller',
    )]
    public function uploadDigitalFile(DigitalProductUploadRequest $request): JsonResponse
    {
        $fileName = $this->productService->storeDigitalFile(file: $request->file('digital_file_ready'));

        return $this->responseFormatter(
            config: config('response.product_digital_file_uploaded'),
            content: ['data' => ['digital_file_ready_name' => $fileName]],
        );
    }

    #[ApiDoc(
        summary: 'Clear the file of one digital variation of a product owned by the authenticated seller',
        tag: 'Product Media (Seller)',
        status: [401, 404, 422],
        guard: 'seller',
    )]
    public function deleteDigitalFile(DigitalProductDeleteRequest $request, int $id): JsonResponse
    {
        $product = $this->sellerProduct(request: $request, id: $id);

        if ($product === null) {
            return $this->notFoundResponse(errorCode: 'product', message: translate('Product not found'));
        }

        $isCleared = $this->productService->clearDigitalVariationFile(
            productId: $product->id,
            variantKey: $request->validated('variant_key'),
        );

        if (! $isCleared) {
            return $this->notFoundResponse(errorCode: 'variant_key', message: translate('Delete unsuccessful'));
        }

        return $this->responseFormatter(config: config('response.product_digital_file_deleted'));
    }

    #[ApiDoc(
        summary: 'Delete the preview file of a digital product owned by the authenticated seller',
        tag: 'Product Media (Seller)',
        status: [401, 404],
        guard: 'seller',
    )]
    public function deletePreviewFile(Request $request, int $id): JsonResponse
    {
        $product = $this->sellerProduct(request: $request, id: $id);

        if ($product === null) {
            return $this->notFoundResponse(errorCode: 'product', message: translate('Product not found'));
        }

        $this->productService->deletePreviewFile(productId: $product->id);

        return $this->responseFormatter(config: config('response.product_preview_file_deleted'));
    }

    #[ApiDoc(
        summary: 'Authors of active digital products, ranked by product count, for the digital product form',
        description: 'An Unknown author with id 0 is appended when active digital products have no author.',
        tag: 'Product Media (Seller)',
        status: [401],
        guard: 'seller',
    )]
    public function getAuthors(): JsonResponse
    {
        $authors = $this->authorService->getListRankedByProducts(
            unattachedProductCount: $this->getUnattachedDigitalCount(attachedProductIds: $this->authorService->getAttachedProductIds()),
            includeWithoutProducts: true,
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => AuthorResource::collection($authors->values())->resolve()],
        );
    }

    #[ApiDoc(
        summary: 'Publishing houses of active digital products, ranked by product count, for the digital product form',
        description: 'An Unknown publishing house with id 0 is appended when active digital products have no publishing house.',
        tag: 'Product Media (Seller)',
        status: [401],
        guard: 'seller',
    )]
    public function getPublishingHouses(): JsonResponse
    {
        $publishingHouses = $this->publishingHouseService->getListRankedByProducts(
            unattachedProductCount: $this->getUnattachedDigitalCount(attachedProductIds: $this->publishingHouseService->getAttachedProductIds()),
        );

        return $this->responseFormatter(
            config: config('response.data_fetched'),
            content: ['data' => PublishingHouseResource::collection($publishingHouses->values())->resolve()],
        );
    }

    private function isColorImage(Request $request): bool
    {
        return in_array($request->input('colors_active'), ['true', true], true);
    }

    private function getUnattachedDigitalCount(array $attachedProductIds): int
    {
        return $this->productService->count(filters: [
            ProductRetailService::FILTER_ACTIVE => true,
            ProductRetailService::FILTER_EXCLUDE_IDS => $attachedProductIds,
            'product_type' => ProductRetailService::PRODUCT_TYPE_DIGITAL,
        ]);
    }

    private function sellerProduct(Request $request, int $id): ?Model
    {
        return $this->productService->findSellerProductBy(
            sellerId: (int) $request->attributes->get('seller')->id,
            filters: ['id' => $id],
        );
    }

    private function notFoundResponse(string $errorCode, string $message): JsonResponse
    {
        return $this->responseFormatter(
            config: config('response.data_not_found'),
            errors: [['error_code' => $errorCode, 'message' => $message]],
        );
    }
}
