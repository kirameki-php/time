<?php declare(strict_types=1);

namespace Tests\Kirameki\Time\Clock;

use DateTimeZone;
use Kirameki\Core\Testing\TestCase;
use Kirameki\Time\Clock\FixedClock;
use Kirameki\Time\Time;
use function microtime;

final class FixedClockTest extends TestCase
{
    public function test_now(): void
    {
        $now = new Time('2000-01-01 00:00:00.000000+09:00');
        $fixed = (new FixedClock($now))->now();

        $this->assertInstanceOf(Time::class, $fixed);
        $this->assertSame('2000-01-01 00:00:00.000000+09:00', $fixed->toString());
        $this->assertSame('+09:00', $fixed->getTimezone()->getName());
        $this->assertSame($now, (new FixedClock(fixed: $now))->now());
    }
}
