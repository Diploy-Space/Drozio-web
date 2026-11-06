<?php

namespace App\Http\Requests\Shared\Product;

use App\Http\Requests\BaseRequest;
use App\Services\New\ProductRetailService;
use App\Traits\CalculatorTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Validator;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class UpdateRequest extends BaseRequest
{
    use CalculatorTrait;
    use VatTaxManagement;

    protected $stopOnFirstFailure = true;

    private ?Model $product = null;

    public function __construct(
        private readonly ProductRetailService $productService
    ) {}

    protected function prepareForValidation(): void
    {
        $this->decodeJsonEncodedArrays(AddRequest::ARRAY_FIELDS);
        $this->joinJsonEncodedList('tags');
    }

    private function product(): ?Model
    {
        if ($this->product === null) {
            $seller = $this->attributes->get('seller');

            $this->product = $seller !== null
                ? $this->productService->findBy(
                    filters: [
                        'id' => $this->route('id'),
                        'user_id' => $seller->id,
                        'added_by' => ProductRetailService::ADDED_BY_SELLER,
                    ],
                    relations: ['translations', 'reviews', 'digitalVariation'],
                )
                : $this->productService->findUnscoped(id: $this->route('id'), relations: ['digitalVariation']);

            abort_if($this->product === null && $this->isApiRequest(), 404);
        }

        return $this->product;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'array'],
            'name.*' => ['nullable', 'string'],
            'lang' => ['required', 'array'],
            'lang.*' => ['string'],
            'description' => ['nullable', 'array'],
            'category_id' => 'required',
            'product_type' => 'required',
            'digital_product_type' => 'required_if:product_type,==,digital',
            'unit' => 'required_if:product_type,==,physical',
            'unit_price' => 'required|numeric|gt:0',
            'discount' => 'required|numeric|gt:-1',
            'shipping_cost' => 'required_if:product_type,==,physical|gt:-1',
            'minimum_order_qty' => 'required|numeric|min:1',
            'code' => [
                'required',
                'regex:/^[a-zA-Z0-9]+$/',
                'min:6',
                'max:20',
                function (string $attribute, mixed $value, callable $fail): void {
                    if ($this->productService->isCodeTaken((string) $value, $this->route('id'))) {
                        $fail(translate('This code is already taken'));
                    }
                },
            ],
            'tags' => ['nullable', 'string'],
            'colors' => ['nullable', 'array'],
            'colors.*' => ['string'],
            'tax_ids' => ['nullable', 'array'],
            'tax_ids.*' => ['integer'],
            'choice_attributes' => ['nullable', 'array'],
            'existing_images' => ['nullable', 'array'],
            'removed_images' => ['nullable', 'array'],
            'removed_images.*' => ['string'],
            'extensions_type' => ['nullable', 'array'],
            'digital_product_sku' => ['nullable', 'array'],
            'digital_product_price' => ['nullable', 'array'],
            'digital_files' => ['nullable', 'array'],
            'video_url' => 'nullable|url',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => translate(':field is required', ['field' => translate('Product Name')]),
            'name.array' => translate('Please enter a product name.'),
            'name.*.string' => translate('Please enter a valid product name.'),
            'lang.required' => translate('Please enter the product name in at least one language.'),
            'lang.array' => translate('Please enter the product name in at least one language.'),
            'lang.*.string' => translate('Please enter the product name in at least one language.'),
            'description.array' => translate('Please enter a product description.'),
            'category_id.required' => translate(':field is required', ['field' => translate('Category')]),
            'unit.required_if' => translate(':field is required', ['field' => translate('Unit')]),
            'discount.numeric' => translate('The discount amount must be a number.'),
            'code.max' => translate('Please ensure your code does not exceed 20 characters'),
            'code.min' => translate('Code with a minimum length requirement of 6 characters'),
            'minimum_order_qty.required' => translate(':field is required', ['field' => translate('Minimum Order Quantity')]),
            'minimum_order_qty.min' => translate('Minimum order quantity must be positive!'),
            'digital_product_type.required_if' => translate(':field is required', ['field' => translate('Digital product type')]),
            'shipping_cost.required_if' => translate(':field is required', ['field' => translate('Shipping Cost')]),
            'tags.string' => translate('Please enter the search tags as a comma separated list.'),
            'colors.array' => translate('Please choose the colors from the color list.'),
            'colors.*.string' => translate('Please choose the colors from the color list.'),
            'tax_ids.array' => translate('Please choose the tax rates from the vat and tax list.'),
            'tax_ids.*.integer' => translate('Please choose the tax rates from the vat and tax list.'),
            'choice_attributes.array' => translate('Please choose the attributes from the attribute list.'),
            'existing_images.array' => translate('Please upload at least one additional product image.'),
            'removed_images.array' => translate('Please choose the images to remove from the product images.'),
            'removed_images.*.string' => translate('Please choose the images to remove from the product images.'),
            'extensions_type.array' => translate('Please choose the file types from the file type list.'),
            'digital_product_sku.array' => translate('Digital SKU are required'),
            'digital_product_price.array' => translate('Digital variation price are required'),
            'digital_files.array' => translate('Digital files are required'),
            'video_url.url' => translate('Please provide a valid video url'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {

                $disallowedExtensions = getDisallowedExtensionsListArray();

                $description = $this->input('description');

                if (is_array($description)) {
                    $first = reset($description);
                    $cleanedDescription = is_string($first) ? trim(strip_tags($first)) : null;
                } else {
                    $cleanedDescription = is_string($description) ? trim(strip_tags($description)) : null;
                }

                if (empty($cleanedDescription)) {
                    $validator->errors()->add(
                        'description', translate(':field is required', ['field' => translate('Product Description')]).'!'
                    );
                }

                if (! isset($this['existing_thumbnail']) && $this->file('image')) {
                    $file = $this->file('image');
                    $extension = strtolower($file->getClientOriginalExtension());

                    if (in_array($extension, $disallowedExtensions)) {
                        $validator->errors()->add(
                            'image',
                            translate('The uploaded image file type is not supported').'!'
                        );
                    }
                }

                $taxData = $this->getTaxSystemType();
                $productWiseTax = $taxData['productWiseTax'] && ! $taxData['is_included'];
                if ($productWiseTax && (! isset($this['tax_ids']) || empty($this['tax_ids']))) {
                    $validator->errors()->add(
                        'tax', translate('Please add your product tax').'!'
                    );
                }

                $product = $this->product();
                $productImages = $this->getRemainingImageNames(json_decode($product['images'], true) ?: []);

                if (! $this->hasActiveColors() && ! $this->file('images') && empty($productImages)) {
                    $validator->errors()->add(
                        'images', translate(':field is required', ['field' => translate('Product images')]).'!'
                    );
                }

                if ($this['product_type'] == 'physical' && $this->isPriceCoveredByDiscount($this['unit_price'])) {
                    $validator->errors()->add(
                        'unit_price', translate('Discount cannot be more than or equal to the price!').'!'
                    );
                }

                if (is_array($this['name']) && is_array($this['lang']) && is_null($this->defaultLanguageName())) {
                    $validator->errors()->add(
                        'name', translate('Name field is required').'!'
                    );
                }

                if ($this->hasActiveColors()) {
                    $databaseColorImages = $product->retailDetail?->color_image ? json_decode($product->retailDetail->color_image, true) : [];

                    $databaseColorImages = collect($databaseColorImages)
                        ->filter(fn ($item) => ! is_null($item['color']) && ! in_array($item['image_name'], $this->removedImageNames(), true))
                        ->unique('color')
                        ->values()->toArray();

                    if (! $databaseColorImages) {
                        foreach ($productImages ?: [] as $image) {
                            $databaseColorImages[] = ['color' => null, 'image_name' => $image];
                        }
                    }
                    $databaseColorImagesFinal = [];
                    if ($databaseColorImages) {
                        foreach ($databaseColorImages as $colorImage) {
                            if ($colorImage['color']) {
                                $databaseColorImagesFinal[] = $colorImage['color'];
                            }
                        }
                    }
                    $inputColors = [];
                    foreach ($this->listInput('colors') as $color) {
                        $inputColors[] = str_replace('#', '', (string) $color);
                    }
                    $differentColor = array_diff($databaseColorImagesFinal, $inputColors);
                    $colorImageRequired = [];
                    if ($databaseColorImages) {
                        foreach ($databaseColorImages as $colorImage) {
                            if ($colorImage['color'] != null && ! in_array($colorImage['color'], $differentColor)) {
                                $colorImageRequired[] = [
                                    'color' => $colorImage['color'],
                                    'image_name' => $colorImage['image_name'],
                                ];
                            }
                        }
                    }

                    foreach ($inputColors as $color) {
                        if (! in_array($color, $databaseColorImagesFinal)) {
                            $colorImageIndex = 'color_image_'.$color;
                            if ($this->file($colorImageIndex)) {
                                $colorImageRequired[] = ['color' => $color, 'image_name' => rand(11111, 99999)];
                            }
                        }
                    }

                    if (count($colorImageRequired) != count($this->listInput('colors'))) {
                        $validator->errors()->add(
                            'images', translate(':field is required', ['field' => translate('Color images')])
                        );
                    }
                }

                if ($this['product_type'] == 'physical' && ($this->has('colors') || count($this->listInput('choice_attributes')) > 0)) {
                    foreach ($this->all() as $requestKey => $requestValue) {
                        if (str_contains($requestKey, 'sku_')) {
                            if (empty($this[$requestKey])) {
                                $validator->errors()->add(
                                    'sku_error', translate('Variation SKU are required').'!'
                                );
                            }
                        }

                        if (str_contains($requestKey, 'choice_attributes') && ! empty($this['choice_attributes']) && is_array($this['choice_attributes'])) {
                            foreach ($this['choice_attributes'] as $key => $value) {
                                if (isset($this['choice_options_'.$value]) && empty($this['choice_options_'.$value])) {
                                    $validator->errors()->add(
                                        'choice_attributes_error', translate('Attributes data are required').'!'
                                    );
                                }

                                if (isset($this['choice_options_'.$value]) && ! empty($this['choice_options_'.$value])) {

                                    if (is_array($this['choice_options_'.$value])) {
                                        $choiceOptions = collect($this['choice_options_'.$value])
                                            ->filter(fn ($value) => ! is_null($value))
                                            ->values()
                                            ->all();

                                        if (count($choiceOptions) <= 0) {
                                            $validator->errors()->add(
                                                'choice_attributes_error', translate('Attributes data are required').'!'
                                            );
                                        }
                                    }
                                }
                            }
                        }

                        if (str_contains($requestKey, 'price_')) {
                            if (empty($this[$requestKey]) || $this[$requestKey] < 0) {
                                $validator->errors()->add(
                                    'variation_price', translate('Variation price are required').'!'
                                );
                            } elseif ($this->isPriceCoveredByDiscount($this[$requestKey])) {
                                $validator->errors()->add(
                                    'variation_price', translate('Discount cannot be more than or equal to the variation price').'!'
                                );
                            }
                        }
                    }
                }

                if ($this['product_type'] == 'digital') {
                    $allDigitalVariation = $product->digitalVariation->pluck('variant_key')->toArray();

                    $digitalProductVariationCount = 0;
                    $fileTypeOptions = [];
                    $digitalVariationCombinations = [];
                    if (count($this->listInput('extensions_type')) > 0) {
                        foreach ($this->listInput('extensions_type') as $type) {
                            $name = 'extensions_options_'.$type;
                            $my_str = implode('|', $this->listInput($name));
                            $fileTypeOptions[$type] = explode(',', $my_str);
                        }

                        foreach ($fileTypeOptions as $arrayKey => $array) {
                            foreach ($array as $key => $value) {
                                if ($value) {
                                    $digitalVariationCombinations[] = trim($arrayKey.'-'.preg_replace('/\s+/', '-', $value));
                                    $digitalProductVariationCount++;
                                }
                            }
                        }
                        $differenceFromDB = array_diff($allDigitalVariation, $digitalVariationCombinations);
                        $differenceFromRequest = array_diff($digitalVariationCombinations, $allDigitalVariation);
                        $newCombinations = array_merge($differenceFromDB, $differenceFromRequest);

                        if ($this['digital_product_type'] == 'ready_product') {
                            foreach ($newCombinations as $newCombination) {
                                if (in_array($newCombination, $digitalVariationCombinations) && empty($this->listInput('digital_files')[str_replace('-', '_', $newCombination)])) {
                                    $validator->errors()->add(
                                        'files', translate('Digital files are required for').' '.str_replace(' ', '-', ucwords(str_replace('-', ' ', $newCombination)))
                                    );
                                }
                            }
                        }

                        if (
                            $this['product_type'] === 'digital' &&
                            $this->hasFile('digital_files')
                        ) {
                            $maxFileSize = 10 * 1024 * 1024;

                            foreach ($this->file('digital_files') as $index => $file) {
                                if (! $file) {
                                    continue;
                                }

                                $extension = strtolower($file->getClientOriginalExtension());
                                $fileSize = $file->getSize();

                                if ($fileSize > $maxFileSize) {
                                    $validator->errors()->add(
                                        "digital_files.$index",
                                        translate('File size exceeds the maximum limit of 10MB').'!'
                                    );
                                }

                                if (in_array($extension, $disallowedExtensions)) {
                                    $validator->errors()->add("digital_files.$index", $extension.translate('File type is not supported').'!');
                                }
                            }
                        }

                        if ($digitalProductVariationCount == 0) {
                            $validator->errors()->add(
                                'variation_error', translate('Digital Product variations are required').'!'
                            );
                        }

                        if ($this->has('digital_product_sku') && empty($this['digital_product_sku'])) {
                            $validator->errors()->add(
                                'sku_error', translate('Digital SKU are required').'!'
                            );
                        } elseif ($this->has('digital_product_sku') && ! empty($this['digital_product_sku'])) {
                            foreach ($this->listInput('digital_product_sku') as $digitalSKU) {
                                if (empty($digitalSKU)) {
                                    $validator->errors()->add(
                                        'sku_error', translate('Digital SKU are required').'!'
                                    );
                                }
                            }
                        }
                    }

                    if ($this['digital_product_type'] == 'ready_product') {
                        foreach ($product->digitalVariation as $variationItem) {
                            if ((empty($variationItem['file'])) && in_array($variationItem['variant_key'], $digitalVariationCombinations) && empty($this->listInput('digital_files')[str_replace('-', '_', $variationItem['variant_key'])])) {
                                $validator->errors()->add(
                                    'files', translate('Digital files are required for').' '.str_replace(' ', '-', ucwords(str_replace('-', ' ', $variationItem['variant_key'])))
                                );
                            }
                        }

                        if (count($product?->digitalVariation) <= 0 && empty($product->retailDetail?->digital_file_ready) && empty($this['digital_file_ready']) && empty($this['digital_files'])) {
                            $validator->errors()->add(
                                'files', translate('Digital files are required').'!'
                            );
                        }

                        if (empty($this['digital_file_ready']) && empty($product->retailDetail?->digital_file_ready) && empty($this['extensions_type'])) {
                            $validator->errors()->add(
                                'files', translate('Digital files are required').'!'
                            );
                        }
                    }

                    if ($this['product_type'] === 'digital' && $this['digital_product_type'] === 'ready_product' && $this->hasFile('digital_file_ready')) {

                        $maxFileSize = getFileUploadMaxSize(type: 'file', unit: 'kb');
                        $file = $this->file('digital_file_ready');
                        $extension = strtolower($file->getClientOriginalExtension());
                        $fileSize = $file->getSize() / 1024;

                        if ($fileSize > $maxFileSize) {
                            $validator->errors()->add(
                                'digital_file_ready',
                                translate('File size exceeds the maximum limit of').getFileUploadMaxSize(type: 'file').'!'
                            );
                        }

                        if (in_array($extension, $disallowedExtensions)) {
                            $validator->errors()->add(
                                'digital_file_ready',
                                translate('The uploaded file type is not supported').'!'
                            );
                        }
                    }

                    if ($this->has('digital_product_price') && ! empty($this['digital_product_price'])) {
                        foreach ($this->listInput('digital_product_price') as $digitalPrice) {
                            if (empty($digitalPrice) || $digitalPrice < 0) {
                                $validator->errors()->add(
                                    'variation_price', translate('Digital variation price are required').'!'
                                );
                            } elseif ($this->isPriceCoveredByDiscount($digitalPrice)) {
                                $validator->errors()->add(
                                    'variation_price', translate('Discount cannot be more than or equal to the digital variation price').'!'
                                );
                            }
                        }
                    }
                }

                $previewFile = $this->file('preview_file');
                if ($previewFile) {
                    $maxFileSize = 10 * 1024 * 1024;
                    $extension = strtolower($previewFile->getClientOriginalExtension());
                    $fileSize = $previewFile->getSize();

                    if ($fileSize > $maxFileSize) {
                        $validator->errors()->add(
                            'files',
                            translate('File size exceeds the maximum limit of 10MB').'!'
                        );
                    } elseif (in_array($extension, $disallowedExtensions)) {
                        $validator->errors()->add(
                            'files',
                            translate('Files with extensions like').
                            ' ('.implode(', ', array_map(fn ($ext) => '.'.$ext, $disallowedExtensions)).') '.
                            translate('Are not supported').'!'
                        );
                    }
                }

                $videoUrl = $this['video_url'];
                if (is_string($videoUrl) && $videoUrl !== '' && ! str_contains($videoUrl, 'youtube.com/embed/')) {
                    $validator->errors()->add(
                        'video_url',
                        translate('Please provide valid youtube embed link')
                    );
                }
            },
            fn (Validator $validator) => $this->validateUniqueVariationSkus($validator),
        ];
    }

    private function validateUniqueVariationSkus(Validator $validator): void
    {
        $variationSkus = [];

        foreach ($this->getVariationSkuInputs() as $field => $sku) {
            $sku = trim((string) $sku);

            if ($sku === '') {
                continue;
            }

            if (in_array(mb_strtolower($sku), $variationSkus, true)) {
                $validator->errors()->add($field, translate('The SKU :sku is used by more than one variation. Please give every variation its own SKU.', ['sku' => $sku]));
            } else {
                $variationSkus[] = mb_strtolower($sku);
            }
        }
    }

    private function getVariationSkuInputs(): array
    {
        if ($this['product_type'] == 'digital') {
            $digitalSkus = [];

            foreach ($this->listInput('digital_product_sku') as $index => $sku) {
                $digitalSkus['digital_product_sku.'.$index] = $sku;
            }

            return $digitalSkus;
        }

        if (! $this->has('colors') && $this->listInput('choice_attributes') === []) {
            return [];
        }

        return array_filter($this->all(), static fn (mixed $value, string|int $key): bool => str_starts_with((string) $key, 'sku_') && ! is_array($value), ARRAY_FILTER_USE_BOTH);
    }

    private function removedImageNames(): array
    {
        return array_values(array_filter($this->listInput('removed_images'), 'is_string'));
    }

    private function getRemainingImageNames(array $storedImages): array
    {
        $imageNames = array_map(
            static fn (mixed $image): string => (string) (is_array($image) ? ($image['image_name'] ?? '') : $image),
            $storedImages,
        );

        return array_values(array_diff($imageNames, $this->removedImageNames()));
    }

    private function hasActiveColors(): bool
    {
        return $this->boolean('colors_active') && $this->listInput('colors') !== [];
    }

    private function listInput(string $field): array
    {
        $value = $this[$field];

        return is_array($value) ? $value : [];
    }

    private function defaultLanguageName(): mixed
    {
        $index = array_search(ProductRetailService::BASE_LANGUAGE, $this->listInput('lang'), true);

        return $this->listInput('name')[$index === false ? 0 : $index] ?? null;
    }

    private function isPriceCoveredByDiscount(mixed $price): bool
    {
        $discount = $this['discount'] ?? 0;
        $discountType = $this['discount_type'];

        if (! is_numeric($price) || ! is_numeric($discount)) {
            return false;
        }

        return $price <= $this->getDiscountAmount(
            price: (float) $price,
            discount: (float) $discount,
            discountType: is_string($discountType) ? $discountType : ProductRetailService::DISCOUNT_TYPE_FLAT,
        );
    }
}
