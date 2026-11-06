<?php

namespace App\Http\Requests\Shared\Product;

use App\Http\Requests\BaseRequest;
use App\Services\New\ProductRetailService;
use App\Traits\CalculatorTrait;
use Illuminate\Validation\Validator;
use Modules\TaxModule\app\Traits\VatTaxManagement;

class AddRequest extends BaseRequest
{
    use CalculatorTrait;
    use VatTaxManagement;

    public const ARRAY_FIELDS = [
        'name',
        'description',
        'lang',
        'colors',
        'tax_ids',
        'choice_attributes',
        'choice_no',
        'choice',
        'choice_options_*',
        'existing_images',
        'removed_images',
        'extensions_type',
        'extensions_options_*',
        'digital_product_sku',
        'digital_product_price',
        'digital_product_variant_key',
        'authors',
        'publishing_house',
    ];

    protected $stopOnFirstFailure = false;

    private const SAFE_FILENAME_REGEX = '/^(?!.*\.\.)[A-Za-z0-9._-]+$/';

    public function __construct(
        private readonly ProductRetailService $productService
    ) {}

    protected function prepareForValidation(): void
    {
        $this->decodeJsonEncodedArrays(self::ARRAY_FIELDS);
        $this->joinJsonEncodedList('tags');
    }

    public function rules(): array
    {
        $rules = [
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
                    if ($this->productService->isCodeTaken((string) $value, null)) {
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
            'extensions_type' => ['nullable', 'array'],
            'digital_product_sku' => ['nullable', 'array'],
            'digital_product_price' => ['nullable', 'array'],
            'digital_files' => ['nullable', 'array'],
            'video_url' => 'nullable|url',
            'existing_thumbnail' => ['nullable', 'string', 'regex:'.self::SAFE_FILENAME_REGEX],
            'existing_meta_image' => ['nullable', 'string', 'regex:'.self::SAFE_FILENAME_REGEX],
            'existing_digital_file_ready' => ['nullable', 'string', 'regex:'.self::SAFE_FILENAME_REGEX],
            'existing_digital_files' => ['nullable', 'array'],
            'existing_digital_files.*' => ['nullable', 'string', 'regex:'.self::SAFE_FILENAME_REGEX],
        ];

        if (! isset($this['existing_thumbnail'])) {
            $rules['image'] = 'required';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'image.required' => translate(':field is required', ['field' => translate('Product thumbnail')]),
            'name.required' => translate('Please enter a product name.'),
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
            'extensions_type.array' => translate('Please choose the file types from the file type list.'),
            'digital_product_sku.array' => translate('Digital SKU are required'),
            'digital_product_price.array' => translate('Digital variation price are required'),
            'digital_files.array' => translate('Digital files are required'),
            'video_url.url' => translate('Please provide a valid video url'),
            'existing_thumbnail.regex' => translate(':field must be a valid file name', ['field' => translate('Product thumbnail')]),
            'existing_meta_image.regex' => translate(':field must be a valid file name', ['field' => translate('Meta image')]),
            'existing_digital_file_ready.regex' => translate(':field must be a valid file name', ['field' => translate('Digital product file')]),
            'existing_digital_files.array' => translate('Digital files are required'),
            'existing_digital_files.*.regex' => translate(':field must be a valid file name', ['field' => translate('Digital product file')]),
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

                if (! $this->hasActiveColors() && ! $this->file('images') && ! $this->has('existing_images')) {
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

                $productImagesCount = 0;
                if ($this->hasActiveColors()) {
                    foreach ($this->listInput('colors') as $color) {
                        $color_ = str_replace('#', '', (string) $color);
                        $image = 'color_image_'.$color_;
                        if ($this->file($image)) {
                            $productImagesCount++;
                        } elseif ($this->has($image)) {
                            $productImagesCount++;
                        }

                    }
                    if ($productImagesCount != count($this->listInput('colors'))) {
                        $validator->errors()->add(
                            'images', translate(':field is required', ['field' => translate('Color images')]).'!'
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
                    $digitalProductVariationCount = 0;
                    if (count($this->listInput('extensions_type')) > 0) {
                        $options = [];
                        foreach ($this->listInput('extensions_type') as $type) {
                            $name = 'extensions_options_'.$type;
                            $my_str = implode('|', $this->listInput($name));
                            $options[$type] = explode(',', $my_str);
                        }

                        foreach ($options as $arrayKey => $array) {
                            foreach ($array as $key => $value) {
                                if ($value) {
                                    $digitalProductVariationCount++;
                                }
                            }
                        }

                        if ($digitalProductVariationCount == 0) {
                            $validator->errors()->add(
                                'variation_error', translate('Digital Product variations are required').'!'
                            );
                        }

                        $digitalVariationKeysHoldingFile = $this->getDigitalVariationKeysHoldingFile();

                        if ($this['digital_product_type'] == 'ready_product' && $digitalVariationKeysHoldingFile === []) {
                            $validator->errors()->add(
                                'files', translate('Digital files are required').'!'
                            );
                        }

                        if ($digitalVariationKeysHoldingFile !== [] && $digitalProductVariationCount != count($digitalVariationKeysHoldingFile)) {
                            $validator->errors()->add(
                                'files', translate('Digital files are required').'!'
                            );
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

                    } else {
                        if ($this['digital_product_type'] == 'ready_product' && ! $this->hasDigitalFileReady()) {
                            $validator->errors()->add(
                                'files', translate('Digital files are required').'!'
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

                if ($this['product_type'] === 'digital' && $this['digital_product_type'] === 'ready_product' && $this->hasFile('digital_file_ready')) {

                    $maxFileSize = getFileUploadMaxSize(type: 'file', unit: 'kb');
                    $file = $this->file('digital_file_ready');
                    $fileSize = $file->getSize() / 1024;
                    if ($fileSize > $maxFileSize) {
                        $validator->errors()->add(
                            'digital_file_ready',
                            translate('File size exceeds the maximum limit of').getFileUploadMaxSize(type: 'file').'!'
                        );
                    }
                    $extension = strtolower($file->getClientOriginalExtension());

                    if ($fileSize > $maxFileSize) {
                        $validator->errors()->add(
                            'digital_file_ready',
                            translate('File size exceeds the maximum limit of 10MB').'!'
                        );
                    }

                    if (in_array($extension, $disallowedExtensions)) {
                        $validator->errors()->add(
                            'digital_file_ready',
                            translate('The uploaded file type is not supported').'!'
                        );
                    }
                }

                $previewFile = $this->file('preview_file');
                if ($previewFile) {
                    $maxFileSize = 10 * 1024 * 1024;
                    $extension = $previewFile->getClientOriginalExtension();
                    $fileSize = $previewFile->getSize();

                    if ($fileSize > $maxFileSize) {
                        $validator->errors()->add(
                            'files', translate('File size exceeds the maximum limit of 10MB').'!'
                        );
                    } elseif (in_array($extension, $disallowedExtensions)) {
                        $validator->errors()->add(
                            'files', translate('Files with extensions like').(' .php,.java,.js,.html,.exe,.sh ').translate('Are not supported').'!'
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

    private function hasActiveColors(): bool
    {
        return $this->boolean('colors_active') && $this->listInput('colors') !== [];
    }

    private function listInput(string $field): array
    {
        $value = $this[$field];

        return is_array($value) ? $value : [];
    }

    private function getDigitalVariationKeysHoldingFile(): array
    {
        $provided = $this->listInput('digital_files') + $this->listInput('existing_digital_files');

        return array_keys(array_filter($provided, static fn ($file): bool => ! empty($file)));
    }

    private function hasDigitalFileReady(): bool
    {
        return ! empty($this['digital_file_ready']) || ! empty($this['existing_digital_file_ready']);
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
