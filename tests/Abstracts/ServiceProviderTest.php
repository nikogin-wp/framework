<?php

namespace Nikogin\Framework\Tests\Abstracts;

use Nikogin\Framework\Abstracts\ServiceProvider;
use Nikogin\Framework\Support\Container;
use Nikogin\Framework\Tests\TestCase;

class SpLogger {}

class SpMailer
{
    public function __construct(public SpLogger $logger) {}
}

class SpReport
{
    public function __construct(public SpLogger $logger, public SpMailer $mailer) {}
}

class TestServiceProvider extends ServiceProvider
{
    protected array $services = [
        SpLogger::class,
        SpMailer::class => SpLogger::class,
        SpReport::class => [SpLogger::class, SpMailer::class],
    ];

    public function priority(): int
    {
        return 10;
    }
}

class ServiceProviderTest extends TestCase
{
    public function test_binds_services_with_dependencies(): void
    {
        (new TestServiceProvider())->register();

        $report = Container::get(SpReport::class);

        $this->assertInstanceOf(SpReport::class, $report);
        $this->assertInstanceOf(SpLogger::class, $report->logger);
        $this->assertInstanceOf(SpLogger::class, $report->mailer->logger);
    }
}
