<?php

namespace Nikogin\Framework\Tests\Traits;

use Nikogin\Framework\Tests\TestCase;
use Nikogin\Framework\Traits\ResolvesAppNamespace;

class NamespaceProbe
{
    use ResolvesAppNamespace;

    public function resolve(): string
    {
        return $this->appNamespace();
    }
}

class ResolvesAppNamespaceTest extends TestCase
{
    public function test_returns_root_namespace_segment(): void
    {
        $this->assertSame('Nikogin', (new NamespaceProbe())->resolve());
    }
}
