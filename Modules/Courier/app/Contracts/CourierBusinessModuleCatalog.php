<?php

namespace Modules\Courier\app\Contracts;

interface CourierBusinessModuleCatalog
{
    /** @return array<string, string> */
    public function businessModules(): array;
}
