<?php

namespace Kadonix\Routebook\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Parameter
{
    public function __construct(
        public readonly string $name,
        public readonly string $in = 'query',
        public readonly string $type = 'string',
        public readonly ?string $description = null,
        public readonly bool $required = false,
        public readonly mixed $example = null,
    ) {
    }
}
