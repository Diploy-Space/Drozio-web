<?php

namespace Modules\AI\app\Http\Requests;

use App\Http\Requests\BaseRequest;

abstract class ProductGenerationRequest extends BaseRequest
{
    protected function wantsJsonErrorResponse(): bool
    {
        return false;
    }
}
