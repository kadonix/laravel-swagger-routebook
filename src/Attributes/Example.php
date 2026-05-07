<?php

namespace Kadonix\Routebook\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Example
{
    public function __construct(
        public readonly string $name = 'Example',
        public readonly string $type = 'response',
        public readonly int|string $status = 200,
        public readonly mixed $value = null,
    ) {
    }
}
