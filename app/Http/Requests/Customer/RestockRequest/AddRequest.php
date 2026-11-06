<?php

namespace App\Http\Requests\Customer\RestockRequest;

use App\Http\Requests\BaseRequest;
use App\Services\New\ColorService;
use Closure;
use Illuminate\Database\Eloquent\Model;

class AddRequest extends BaseRequest
{
    private ?Model $color = null;

    public function __construct(private readonly ColorService $colorService)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
            'color' => ['sometimes', 'required', 'string', $this->knownColor()],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => translate('Please select a product'),
            'id.integer' => translate('The selected product is not valid'),
            'color.required' => translate('Please select a color'),
            'color.string' => translate('The selected color is not valid'),
        ];
    }

    public function getColorName(): ?string
    {
        return $this->has('color') ? $this->findColor()?->name : null;
    }

    private function knownColor(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if ($this->findColor() === null) {
                $fail(translate('The selected color is not valid'));
            }
        };
    }

    private function findColor(): ?Model
    {
        if ($this->color === null && is_string($this->input('color'))) {
            $this->color = $this->colorService->findBy(params: ['code' => $this->input('color')]);
        }

        return $this->color;
    }
}
