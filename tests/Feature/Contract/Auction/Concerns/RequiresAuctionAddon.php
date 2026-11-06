<?php

namespace Tests\Feature\Contract\Auction\Concerns;

trait RequiresAuctionAddon
{
    protected function setUpRequiresAuctionAddon(): void
    {
        if (! addon_published_status(moduleName: 'Auction')) {
            $this->markTestSkipped('Auction addon is not published.');
        }
    }
}
