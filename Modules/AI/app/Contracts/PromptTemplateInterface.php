<?php

namespace Modules\AI\app\Contracts;

interface PromptTemplateInterface
{
    public function build(?string $context = null, ?string $language = null, ?string $description = null, ?array $options = null): string;

    public function getType(): string;
}
