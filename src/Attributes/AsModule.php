<?php

namespace Nikogin\Framework\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsModule
{
    public function __construct(
        public string $name,
        public bool $enabled = true,
    ) {}
}
