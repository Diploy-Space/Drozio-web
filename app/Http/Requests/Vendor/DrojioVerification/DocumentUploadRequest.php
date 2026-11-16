<?php

namespace App\Http\Requests\Vendor\DrojioVerification;

use App\Http\Requests\BaseRequest;

class DocumentUploadRequest extends BaseRequest
{
    public function rules(): array
    {
        $max = getFileUploadMaxSize(unit: 'kb');
        return [
            'document_type' => ['required', 'string', 'max:50'],
            'document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:'.$max],
        ];
    }

    public function messages(): array
    {
        return [
            'document_type.required' => translate('Document type is required'),
            'document.required' => translate('Document file is required'),
            'document.mimes' => translate('Document must be a valid file type'),
        ];
    }
}
