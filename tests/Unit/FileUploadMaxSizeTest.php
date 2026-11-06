<?php

namespace Tests\Unit;

use Tests\TestCase;

class FileUploadMaxSizeTest extends TestCase
{
    public function test_a_limit_narrows_the_type_ceiling(): void
    {
        $this->assertSame(2, getFileUploadMaxSize(limit: 2));
        $this->assertSame(2048, getFileUploadMaxSize(unit: 'kb', limit: 2));
    }

    public function test_a_limit_above_the_type_ceiling_never_widens_it(): void
    {
        $ceiling = getFileUploadMaxSize();

        $this->assertSame($ceiling, getFileUploadMaxSize(limit: $ceiling + 500));
    }

    public function test_omitting_the_limit_keeps_the_type_ceiling(): void
    {
        $this->assertSame(getFileUploadMaxSize(), getFileUploadMaxSize(limit: null));
        $this->assertSame(getFileUploadMaxSize(type: 'file'), getFileUploadMaxSize(type: 'file', limit: null));
    }
}
