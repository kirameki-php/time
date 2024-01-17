<?php declare(strict_types=1);

namespace Tests\Kirameki\Time;

use DateMalformedStringException;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Testing\TestCase;
use Kirameki\Time\Clock;
use Kirameki\Time\DayOfWeek;
use Kirameki\Time\Exceptions\InvalidFormatException;
use Kirameki\Time\Time;
use Kirameki\Time\Unit;
use PHPUnit\Framework\Attributes\DataProvider;
use function date_default_timezone_get;
use function date_default_timezone_set;
use function json_encode;
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
        $this->assertTrue((new Clock(fixed: new Time('2000-01-01')))->isFixed());
    }

    public function test_now(): void
    {
        $this->assertInstanceOf(Time::class, (new Clock())->now());
        $this->assertEqualsWithDelta(microtime(true), (new Clock())->now()->toFloat(), 0.001);

        $now = new Time('2000-01-01');
        $this->assertSame($now, (new Clock(fixed: $now))->now());
    }
}
