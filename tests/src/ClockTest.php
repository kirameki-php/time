<?php declare(strict_types=1);

namespace Tests\Kirameki\Time;

use DateTimeZone;
use Kirameki\Core\Testing\TestCase;
use Kirameki\Time\Clock;
use Kirameki\Time\Time;
use function microtime;

final class ClockTest extends TestCase
{
    public function test___construct_no_args(): void
    {
        $this->assertSame('UTC', (new Clock())->getTimezone()->getName());
    }

    public function test___construct_with_timezone_arg(): void
    {
        $zone = new DateTimeZone('Asia/Tokyo');
        $this->assertSame($zone, (new Clock($zone))->getTimezone());
    }

    public function test_isFixed(): void
    {
        $this->assertFalse((new Clock())->isFixed());

        $now = new Time('2000-01-01');
        $this->assertTrue((new Clock(fixed: $now))->isFixed());
    }

    public function test_now(): void
    {
        $this->assertInstanceOf(Time::class, (new Clock())->now());
        $this->assertEqualsWithDelta(microtime(true), (new Clock())->now()->toFloat(), 0.001);

        $now = new Time('2000-01-01');
        $this->assertSame($now, (new Clock(fixed: $now))->now());
    }
}
