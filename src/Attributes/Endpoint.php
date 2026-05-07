<?php

namespace Kadonix\Routebook\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Endpoint
{
    /**
     * @param array<int, string> $tags
     * @param array<string, array<int, string>> $security
     */
    public function __construct(
        public readonly ?string $method = null,
        public readonly ?string $path = null,
        public readonly ?string $summary = null,
        public readonly ?string $description = null,
        public readonly array $tags = [],
        public readonly ?string $group = null,
        public readonly ?string $operationId = null,
        public readonly bool $deprecated = false,
        public readonly bool $auth = false,
        public readonly array $security = [],
    ) {
    }
}
