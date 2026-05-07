<?php

namespace Kadonix\Routebook\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Response
{
    /**
     * @param array<string, mixed> $schema
     * @param class-string|null $from
     */
    public function __construct(
        public readonly int|string $status = 200,
        public readonly string $description = 'OK',
        public readonly ?array $schema = null,
        public readonly ?string $from = null,
        public readonly bool $collection = false,
        public readonly string $contentType = 'application/json',
    ) {
    }
}
