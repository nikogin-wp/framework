<?php

namespace Nikogin\Framework\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsCron
{
    public string $hook;
    public function __construct(
        string $hook,
        public string $when,
        public bool $single = true,
        public ?string $recurrence = null
    )
    {
        $this->hook = "pt_" . $hook;
    }
}