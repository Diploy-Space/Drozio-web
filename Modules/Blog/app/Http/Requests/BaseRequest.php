<?php

namespace Modules\Blog\app\Http\Requests;

use App\Utils\Helpers;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Single base for every request class — Web and API alike.
 *
 * One request class serves both perspectives; the perspective-specific part is
 * a branch, not a second class. `failedValidation()` answers JSON to an API or
 * AJAX caller and falls through to Laravel's redirect-back for a web form.
 *
 * The error body is built by `formatValidationErrors()`, which defaults to a
 * list of `{'error_code', 'message'}` entries with the message taken verbatim
 * from the validator (a class translates its own text in `messages()`). A
 * request reproducing a frozen v1/v2/v3 endpoint sets
 * `$useLegacyApiErrorFormat = true` to fall back to that source's
 * `Helpers::validationErrorProcessor()` shape instead. Status is 403 under
 * `api/*` and 422 elsewhere.
 */
abstract class BaseRequest extends FormRequest
{
    protected const MAX_NUMERIC_VALUE = 9999999999999999.9999;

    protected string $errorResponseKey = 'errors';

    /** Opts a request reproducing a frozen v1/v2/v3 endpoint into that source's untranslated `{'code','message'}` error shape. */
    protected bool $useLegacyApiErrorFormat = false;

    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($this->wantsJsonErrorResponse()) {
            throw new HttpResponseException(
                response()->json([$this->errorResponseKey => $this->formatValidationErrors($validator)], $this->validationErrorStatus())
            );
        }

        parent::failedValidation($validator);
    }

    public function isApiRequest(): bool
    {
        return $this->is('api/*');
    }

    protected function wantsJsonErrorResponse(): bool
    {
        return $this->isApiRequest() || $this->ajax() || $this->expectsJson();
    }

    protected function validationErrorStatus(): int
    {
        return $this->isApiRequest() ? 403 : 422;
    }

    protected function formatValidationErrors(Validator $validator): array
    {
        if ($this->useLegacyApiErrorFormat) {
            return Helpers::validationErrorProcessor($validator);
        }

        $errors = [];
        foreach ($validator->errors()->getMessages() as $index => $error) {
            $errors[] = ['error_code' => $index, 'message' => $error[0]];
        }
        return $errors;
    }
}
