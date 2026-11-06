<?php

namespace Modules\Courier\app\Services\New\Wholesale;

use Modules\Courier\app\Services\New\CourierConfigService as RetailCourierConfigService;
use Modules\Courier\app\Services\New\ProviderRegistry;
use Modules\Courier\app\ValueObjects\CourierOwner;

class CourierConfigService extends RetailCourierConfigService
{
    protected function registry(): ProviderRegistry
    {
        return parent::registry()->forBusinessModule(CourierOwner::BUSINESS_MODULE_WHOLESALE);
    }
}
