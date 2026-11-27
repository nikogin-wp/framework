<?php

namespace Nikogin\Framework\Tests;

use Nikogin\Framework\Support\Router;
use PHPUnit\Framework\TestCase as BaseTestCase;
use WpSpy;

abstract class TestCase extends BaseTestCase
{
    protected const FIXTURES = __DIR__ . '/Fixtures/App';
    protected const FIXTURE_NS = 'Fixture\\App';

    protected function setUp(): void
    {
        parent::setUp();
        WpSpy::reset();
        Router::setNamespace('ng/v1');
    }
}
