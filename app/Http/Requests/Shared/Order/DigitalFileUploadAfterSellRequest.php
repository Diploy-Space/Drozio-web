<?php

namespace App\Http\Requests\Shared\Order;

use App\Http\Requests\BaseRequest;
use Illuminate\Validation\Validator;

class DigitalFileUploadAfterSellRequest extends BaseRequest
{
    public function rules(): array
    {
        if (! $this->isApiRequest()) {
            return [
                'digital_file_after_sell' => [
                    'required',
                    'file',
                    'max:'.getFileUploadMaxSize(unit: 'kb'),
                    'mimes:jpg,jpeg,png,gif,zip,pdf,xlsx',
                ],
            ];
        }

        return [
            'order_detail_id' => ['required', 'integer', 'min:1'],
            'digital_file_after_sell' => [
                'required',
                'file',
                'max:'.getFileUploadMaxSize(unit: 'kb'),
                'mimes:jpg,jpeg,png,gif,zip,pdf',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'digital_file_after_sell.required' => translate('Digital file upload after sell is required'),
            'digital_file_after_sell.file' => translate('Upload the digital file as a file'),
            'digital_file_after_sell.max' => translate('The digital file is too large'),
            'digital_file_after_sell.mimes' => $this->isApiRequest()
                ? translate('Unsupported file format. Allowed: jpg, jpeg, png, gif, zip, pdf.')
                : translate('The uploaded file type is not supported'),
            'order_detail_id.required' => translate('Choose the order item to upload the file for'),
            'order_detail_id.integer' => translate('Choose a valid order item'),
            'order_detail_id.min' => translate('Choose a valid order item'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $disallowedExtensions = getDisallowedExtensionsListArray();

                if ($this->hasFile('digital_file_after_sell')) {
                    $extension = strtolower($this->file('digital_file_after_sell')->getClientOriginalExtension());
                    if (in_array($extension, $disallowedExtensions)) {
                        $validator->errors()->add(
                            'digital_file_after_sell',
                            translate('The uploaded file type is not supported').'!'
                        );
                    }
                }
            },
        ];
    }
}
