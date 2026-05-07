<?php

namespace Kadonix\Routebook\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
final class Schema
{
    /**
     * @param array<string, mixed> $properties
     * @param array<int, string> $required
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?string $description = null,
        public readonly mixed $example = null,
        public readonly array $properties = [],
        public readonly array $required = [],
    ) {
    }
}
