<?php declare(strict_types=1);

namespace Tests\Kirameki\Time\Clock;

use DateTimeZone;
use Kirameki\Core\Testing\TestCase;
use Kirameki\Time\Clock\SystemClock;
use Kirameki\Time\Time;
use function microtime;

final class SystemClockTest extends TestCase
{
    public function test___construct_no_args(): void
    {
        $this->assertSame('UTC', (new SystemClock())->getTimezone()->getName());
    }

    public function test___construct_with_timezone_arg(): void
    {
        $zone = new DateTimeZone('Asia/Tokyo');
        $this->assertSame($zone, (new SystemClock($zone))->getTimezone());
    }

    public function test_now(): void
    {
        $this->assertInstanceOf(Time::class, (new SystemClock())->now());
        $this->assertEqualsWithDelta(microtime(true), (new SystemClock())->now()->toFloat(), 0.001);
    }
}
