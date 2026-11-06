<?php

namespace Tests\Feature\Wholesale\Concerns;

trait RequiresWholesaleAddon
{
    use InteractsWithWholesaleAddon;

    protected function setUpRequiresWholesaleAddon(): void
    {
        $this->skipUnlessWholesaleAddonPublished();
    }
}
