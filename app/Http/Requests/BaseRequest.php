<?php

namespace App\Http\Requests;

use App\Traits\ApiResponseTrait;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator as ValidatorFactory;
use Illuminate\Support\Str;

abstract class BaseRequest extends FormRequest
{
    use ApiResponseTrait;

    protected const MAX_NUMERIC_VALUE = 9999999999999999.9999;

    protected bool $useLegacyApiErrorFormat = false;

    protected bool $useLegacyRequestErrorFormat = false;

    public function authorize(): bool
    {
        return true;
    }

    protected function decodeJsonEncodedArrays(array $fieldPatterns): void
    {
        $decoded = [];

        foreach ($this->input() as $field => $value) {
            if (! is_string($value) || ! Str::is($fieldPatterns, (string) $field)) {
                continue;
            }

            $array = json_decode($value, true);

            if (is_array($array)) {
                $decoded[$field] = $array;
            }
        }

        $this->merge($decoded);
    }

    protected function joinJsonEncodedList(string $field, string $separator = ','): void
    {
        $value = $this->input($field);
        $list = is_string($value) ? json_decode($value, true) : $value;

        if (is_array($list)) {
            $this->merge([$field => implode($separator, array_filter($list, 'is_scalar'))]);
        }
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->useLegacyApiErrorFormat && $this->isApiRequest()) {
            throw new HttpResponseException(
                response()->json(['errors' => $this->formatLegacyValidationErrors($validator)], 403)
            );
        }

        if ($this->useLegacyRequestErrorFormat && $this->isApiRequest()) {
            throw new HttpResponseException(
                response()->json(['errors' => $this->formatValidationErrors($validator)], 403)
            );
        }

        if ($this->isApiRequest()) {
            $this->throwEnvelopeValidationResponse($validator);
        }

        if ($this->wantsJsonErrorResponse()) {
            throw new HttpResponseException(
                response()->json(['errors' => $this->formatValidationErrors($validator)], 422)
            );
        }

        parent::failedValidation($validator);
    }

    /**
     * @return array<int, array{code: string, message: string}>
     */
    protected function formatLegacyValidationErrors(Validator $validator): array
    {
        $errors = [];
        foreach ($validator->errors()->getMessages() as $index => $error) {
            $errors[] = ['code' => $index, 'message' => $error[0]];
        }

        return $errors;
    }

    protected function throwEnvelopeValidationResponse(Validator $validator): void
    {
        $discardedUploadMessage = $this->discardedUploadMessage();

        if ($discardedUploadMessage !== null) {
            $validator = $this->uploadFailureValidator($discardedUploadMessage);
        }

        $response = $this->responseFormatter(
            config: config('response.unprocessable_entity_422'),
            errors: $this->formatValidationErrors($validator)
        );

        $payload = $response->getData(true);
        $payload['message'] = $validator->errors()->first() ?: $payload['message'];

        throw new HttpResponseException($response->setData($payload));
    }

    public function isApiRequest(): bool
    {
        return $this->is('api/*');
    }

    protected function wantsJsonErrorResponse(): bool
    {
        return $this->ajax() || $this->expectsJson();
    }

    protected function formatValidationErrors(Validator $validator): array
    {
        $errors = [];
        foreach ($validator->errors()->getMessages() as $index => $error) {
            $errors[] = ['error_code' => $index, 'message' => $error[0]];
        }

        return $errors;
    }

    protected function numericRule(float $min = 0): array
    {
        return ['numeric', "min:{$min}", 'max:'.self::MAX_NUMERIC_VALUE];
    }

    protected function iniBytes(string $directive): int
    {
        $value = trim((string) ini_get($directive));

        if ($value === '' || (int) $value <= 0) {
            return 0;
        }

        $bytes = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $bytes * 1024 * 1024 * 1024,
            'm' => $bytes * 1024 * 1024,
            'k' => $bytes * 1024,
            default => $bytes,
        };
    }

    protected function exceedsPostMaxSize(): bool
    {
        $limit = $this->iniBytes('post_max_size');

        if ($limit === 0) {
            return false;
        }

        return (int) $this->server('CONTENT_LENGTH', 0) > $limit;
    }

    protected function isMultipartRequest(): bool
    {
        return str_contains(strtolower((string) $this->header('content-type')), 'multipart/form-data');
    }

    protected function discardedUploadMessage(): ?string
    {
        if (! $this->isMultipartRequest()) {
            return null;
        }

        $postLimit = (string) ini_get('post_max_size');

        if ($this->exceedsPostMaxSize()) {
            $this->logUploadDiagnostics('Upload exceeded post_max_size');

            return translate('Upload size exceeded the server limit of').' '.$postLimit;
        }

        if ($this->isUnparsedMultipartBody()) {
            $this->logUploadDiagnostics('Multipart body sent with a method PHP does not parse');

            return translate('Send file uploads as a POST request with :field set to :method', [
                'field' => '_method',
                'method' => $this->getRealMethod(),
            ]);
        }

        return null;
    }

    protected function logUploadDiagnostics(string $reason): void
    {
        Log::warning($reason, [
            'path' => $this->path(),
            'content_length' => (int) $this->server('CONTENT_LENGTH', 0),
            'parsed_fields' => array_keys($this->request->all()),
            'parsed_files' => array_keys($this->files->all()),
            'post_max_size' => (string) ini_get('post_max_size'),
            'upload_max_filesize' => (string) ini_get('upload_max_filesize'),
            'max_file_uploads' => (string) ini_get('max_file_uploads'),
            'file_uploads' => (string) ini_get('file_uploads'),
            'upload_tmp_dir' => (string) ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
        ]);
    }

    private function isUnparsedMultipartBody(): bool
    {
        return $this->getRealMethod() !== self::METHOD_POST
            && (int) $this->server('CONTENT_LENGTH', 0) > 0
            && $this->request->count() === 0
            && $this->files->count() === 0;
    }

    protected function uploadFailureValidator(string $message): Validator
    {
        $validator = ValidatorFactory::make([], []);

        $validator->errors()->add('upload', $message);

        return $validator;
    }
}
