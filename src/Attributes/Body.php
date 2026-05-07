<?php

namespace Kadonix\Routebook\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final class Body extends RequestBody
{
}
