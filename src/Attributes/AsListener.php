<?php

namespace Nikogin\Framework\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsListener
{
    public function __construct(
        public string $name,
    public string $type,
   public int $priority = 10,
        public int $argsCount = 1
    ) {}
}