<?php

namespace Kadonix\Routebook\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class RequestBody
{
    /**
     * @param array<string, mixed> $schema
     * @param class-string|null $from
     */
    public function __construct(
        public readonly ?array $schema = null,
        public readonly ?string $from = null,
        public readonly bool $required = true,
        public readonly string $contentType = 'application/json',
        public readonly ?string $description = null,
    ) {
    }
}
