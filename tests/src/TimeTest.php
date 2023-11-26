<?php declare(strict_types=1);

namespace Tests\Kirameki\Time;

use DateTimeImmutable;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Testing\TestCase;
use Kirameki\Time\DayOfWeek;
use Kirameki\Time\Time;
use function date_default_timezone_set;
use function dump;
use function json_encode;

final class TimeTest extends TestCase
{
    public function test___construct_no_args(): void
    {
        $this->assertSame('UTC', (new Time())->getTimezone()->getName());
    }

    public function test___construct_with_time_args(): void
    {
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}.\d{6}Z$/', (new Time('now'))->toString());
        $this->assertSame('UTC', (new Time('2000-01-01 12:34:56'))->getTimezone()->getName());
        $this->assertSame('2000-01-01 12:34:00.000000Z', (new Time('2000-01-01 12:34:00Z'))->toString());
        $this->assertSame('2000-01-01 12:34:56.000000+09:00', (new Time('2000-01-01 12:34:56+09:00'))->toString());
        $this->assertSame('2000-01-01 12:34:56.111000+09:00', (new Time('2000-01-01 12:34:56.111+09:00'))->toString());
        $this->assertSame('2000-01-01 12:34:56.111111+09:00', (new Time('2000-01-01 12:34:56.111111+09:00'))->toString());
    }

    public function test_toTimezone(): void
    {
        $source = new Time('2000-01-01 12:34:56Z');
        $zoned = $source->toTimezone('Asia/Tokyo');
        $this->assertSame('2000-01-01 21:34:56.000000+09:00', $zoned->toString());
        $this->assertNotSame($source, $zoned);
    }

    public function test_toLocal(): void
    {
        $tz = 'America/Los_Angeles';
        date_default_timezone_set($tz);
        $this->runAfterTearDown(fn() => date_default_timezone_set('UTC'));

        $source =new Time('2000-01-01 12:34:56+09:00');
        $local = $source->toLocal();
        $this->assertSame('1999-12-31 19:34:56.000000-08:00', $local->toString());
        $this->assertNotSame($source, $local);
    }

    public function test_toUtc(): void
    {
        $source = new Time('2000-01-01 12:34:56+09:00');
        $utc = $source->toUtc();
        $this->assertSame('2000-01-01 03:34:56.000000Z', $utc->toString());
        $this->assertNotSame($source, $utc);
    }

    public function test_clamp(): void
    {
        // clamp exact
        $lower = new Time('2000-01-01 12:34:56.000000Z');
        $upper = clone $lower;
        $clamped = (new Time('2000-01-01 12:34:56.000002Z'))->clamp($lower, $upper);
        $this->assertSame($lower->toString(), $clamped->toString(), 'clamp exact');
        $this->assertSame($upper->toString(), $clamped->toString(), 'clamp exact');

        // in range
        $source = new Time('2000-01-01 12:34:56.000001Z');
        $lower = new Time('2000-01-01 12:34:56.000000Z');
        $upper = new Time('2000-01-01 12:34:56.000002Z');
        $clamped = $source->clamp($lower, $upper);
        $this->assertSame($source->toString(), $clamped->toString(), 'in range');

        // clamp upper
        $lower = new Time('2000-01-01 12:34:56.000000Z');
        $upper = new Time('2000-01-01 12:34:56.000001Z');
        $clamped = (new Time('2000-01-01 12:34:56.000002Z'))->clamp($lower, $upper);
        $this->assertSame($upper->toString(), $clamped->toString(), 'clamp upper');

        // clamp lower
        $lower = new Time('2000-01-01 12:34:56.000001Z');
        $upper = new Time('2000-01-01 12:34:56.000002Z');
        $clamped = (new Time('2000-01-01 12:34:56.000000Z'))->clamp($lower, $upper);
        $this->assertSame($lower->toString(), $clamped->toString(), 'clamp lower');

        // no upper
        $source = new Time('2100-01-01 00:00:00Z');
        $lower = new Time('2000-01-01 12:34:56.000001Z');
        $clamped = $source->clamp($lower);
        $this->assertSame($source->toString(), $clamped->toString(), 'clamp lower');

        // no lower
        $source = new Time('0000-01-01 00:00:00Z');
        $upper = new Time('2000-01-01 12:34:56.000001Z');
        $clamped = $source->clamp(null, $upper);
        $this->assertSame($source->toString(), $clamped->toString(), 'clamp lower');
    }

    public function test_clamp_no_args(): void
    {
        $this->expectExceptionMessage('At least one of $lower or $upper must be specified.');
        $this->expectException(InvalidArgumentException::class);
        Time::now()->clamp();
    }

    public function test_between(): void
    {
        $ymdhis = '2000-01-01 12:34:56';
        $this->assertTrue((new Time($ymdhis . '.000001Z'))->between(new Time($ymdhis . '.000001Z'), new Time($ymdhis . '.000001Z')), 'all same');
        $this->assertTrue((new Time($ymdhis . '.000001Z'))->between(new Time($ymdhis . '.000000Z'), new Time($ymdhis . '.000001Z')), 'match right');
        $this->assertTrue((new Time($ymdhis . '.000001Z'))->between(new Time($ymdhis . '.000001Z'), new Time($ymdhis . '.000002Z')), 'match left');
        $this->assertTrue((new Time($ymdhis . '.000001Z'))->between(new Time($ymdhis . '.000000Z'), new Time($ymdhis . '.000002Z')), 'contained');
        $this->assertFalse((new Time($ymdhis . '.000001Z'))->between(new Time($ymdhis . '.000002Z'), new Time($ymdhis . '.000000Z')), 'reversed');
    }

    public function test_isPast(): void
    {
        $now = new Time();
        $this->assertFalse($now->isPast($now));

        $now = new Time();
        $this->assertFalse($now->addSeconds(0.1)->isPast($now));
        $this->assertTrue($now->subtractSeconds(0.1)->isPast($now));
    }

    public function test_isFuture(): void
    {
        $now = new Time();
        $this->assertFalse($now->isFuture($now));

        $now = new Time();
        $this->assertTrue($now->addSeconds(0.1)->isFuture($now));
        $this->assertFalse($now->subtractSeconds(0.1)->isFuture($now));
    }

    public function test_jsonSerialize(): void
    {
        $this->assertSame('"2000-01-01 12:34:56.000000Z"', json_encode(new Time('2000-01-01 12:34:56Z')));
        $this->assertSame('2000-01-01 12:34:56.000000Z', (new Time('2000-01-01 12:34:56Z'))->jsonSerialize());
    }

    public function test_toBase(): void
    {
        $base = (new Time('1970-01-01 00:00:01Z'))->toBase();
        $this->assertSame(DateTimeImmutable::class, $base::class);
        $this->assertSame(1, $base->getTimestamp());
    }

    public function test_toInt(): void
    {
        $this->assertSame(0, (new Time('1970-01-01 00:00:00Z'))->toInt(), 'zero');
        $this->assertSame(1, (new Time('1970-01-01 00:00:01.123456Z'))->toInt(), 'microseconds cutoff');
        $this->assertSame(946730096, (new Time('2000-01-01 12:34:56Z'))->toInt(), 'current');
        $this->assertSame(-2208988800, (new Time('1900-01-01 00:00:00Z'))->toInt(), 'before');
        $this->assertSame(4102444800, (new Time('2100-01-01 00:00:00Z'))->toInt(), 'future');
    }

    public function test_toFloat(): void
    {
        $this->assertSame(0.0, (new Time('1970-01-01 00:00:00Z'))->toFloat(), 'zero');
        $this->assertSame(1.123456, (new Time('1970-01-01 00:00:01.123456Z'))->toFloat(), 'with microseconds');
        $this->assertSame(946730096.0, (new Time('2000-01-01 12:34:56Z'))->toFloat(), 'current');
        $this->assertSame(-2208988800.0, (new Time('1900-01-01 00:00:00Z'))->toFloat(), 'before');
        $this->assertSame(4102444800.0, (new Time('2100-01-01 00:00:00Z'))->toFloat(), 'future');
    }

    public function test_toString(): void
    {
        $this->assertSame('2000-01-01 12:34:56.000000Z', (new Time('2000-01-01 12:34:56Z'))->toString());
        $this->assertSame('2000-01-01 12:34:56.000000+09:00', (new Time('2000-01-01 12:34:56+09:00'))->toString());
    }

    public function test___toString(): void
    {
        $this->assertSame('2000-01-01 12:34:56.000000Z', (new Time('2000-01-01 12:34:56Z'))->__toString());
    }

    public function test___toString_cast(): void
    {
        $this->assertSame('2000-01-01 12:34:56.000000Z', (string) new Time('2000-01-01 12:34:56Z'));
    }

    public function test_getYear(): void
    {
        $this->assertSame(2000, (new Time('2000-01-01 12:34:56Z'))->getYear());
    }

    public function test_getMonth(): void
    {
        $this->assertSame(2, (new Time('2000-02-29 12:34:56Z'))->getMonth());
    }

    public function test_getDay(): void
    {
        $this->assertSame(29, (new Time('2000-02-29 12:34:56Z'))->getDay());
    }

    public function test_getHours(): void
    {
        $this->assertSame(12, (new Time('2000-01-01 12:34:56Z'))->getHours());
    }

    public function test_getMinutes(): void
    {
        $this->assertSame(34, (new Time('2000-01-01 12:34:56Z'))->getMinutes());
    }

    public function test_getSeconds(): void
    {
        $this->assertSame(56.0, (new Time('2000-01-01 12:34:56Z'))->getSeconds());
    }

    public function test_getDaysInMonth(): void
    {
        $this->assertSame(31, (new Time('2001-01-01 00:00:00Z'))->getDaysInMonth());
        $this->assertSame(28, (new Time('2001-02-01 00:00:00Z'))->getDaysInMonth());
        $this->assertSame(31, (new Time('2001-03-01 00:00:00Z'))->getDaysInMonth());
        $this->assertSame(30, (new Time('2001-04-01 00:00:00Z'))->getDaysInMonth());
        $this->assertSame(31, (new Time('2001-05-01 00:00:00Z'))->getDaysInMonth());
        $this->assertSame(30, (new Time('2001-06-01 00:00:00Z'))->getDaysInMonth());
        $this->assertSame(31, (new Time('2001-07-01 00:00:00Z'))->getDaysInMonth());
        $this->assertSame(31, (new Time('2001-08-01 00:00:00Z'))->getDaysInMonth());
        $this->assertSame(30, (new Time('2001-09-01 00:00:00Z'))->getDaysInMonth());
        $this->assertSame(31, (new Time('2001-10-01 00:00:00Z'))->getDaysInMonth());
        $this->assertSame(30, (new Time('2001-11-01 00:00:00Z'))->getDaysInMonth());
        $this->assertSame(31, (new Time('2001-12-31 00:00:00Z'))->getDaysInMonth());
        $this->assertSame(29, (new Time('2000-02-01 00:00:00Z'))->getDaysInMonth(), '2000 is leap year');
    }

    public function test_getDayOfWeek(): void
    {
        $this->assertSame(DayOfWeek::Saturday, (new Time('2000-01-01 12:34:56Z'))->getDayOfWeek(), 'Saturday');
        $this->assertSame(DayOfWeek::Sunday, (new Time('2000-01-02 12:34:56Z'))->getDayOfWeek(), 'Sunday');
        $this->assertSame(DayOfWeek::Monday, (new Time('2000-01-03 12:34:56Z'))->getDayOfWeek(), 'Monday');
        $this->assertSame(DayOfWeek::Tuesday, (new Time('2000-01-04 12:34:56Z'))->getDayOfWeek(), 'Tuesday');
        $this->assertSame(DayOfWeek::Wednesday, (new Time('2000-01-05 12:34:56Z'))->getDayOfWeek(), 'Wednesday');
        $this->assertSame(DayOfWeek::Thursday, (new Time('2000-01-06 12:34:56Z'))->getDayOfWeek(), 'Thursday');
        $this->assertSame(DayOfWeek::Friday, (new Time('2000-01-07 12:34:56Z'))->getDayOfWeek(), 'Friday');
    }

    public function test_getDayOfYear(): void
    {
        $this->assertSame(1, (new Time('2000-01-01 00:00:00Z'))->getDayOfYear());
        $this->assertSame(32, (new Time('2000-02-01 00:00:00Z'))->getDayOfYear());
        $this->assertSame(365, (new Time('1999-12-31 00:00:00Z'))->getDayOfYear());
        $this->assertSame(366, (new Time('2000-12-31 00:00:00Z'))->getDayOfYear());
    }

    public function test_isMonday(): void
    {
        $this->assertFalse((new Time('2000-01-01 12:34:56Z'))->isMonday(), 'Saturday');
        $this->assertFalse((new Time('2000-01-02 12:34:56Z'))->isMonday(), 'Sunday');
        $this->assertTrue((new Time('2000-01-03 12:34:56Z'))->isMonday(), 'Monday');
        $this->assertFalse((new Time('2000-01-04 12:34:56Z'))->isMonday(), 'Tuesday');
        $this->assertFalse((new Time('2000-01-05 12:34:56Z'))->isMonday(), 'Wednesday');
        $this->assertFalse((new Time('2000-01-06 12:34:56Z'))->isMonday(), 'Thursday');
        $this->assertFalse((new Time('2000-01-07 12:34:56Z'))->isMonday(), 'Friday');
    }

    public function test_isTuesday(): void
    {
        $this->assertFalse((new Time('2000-01-01 12:34:56Z'))->isTuesday(), 'Saturday');
        $this->assertFalse((new Time('2000-01-02 12:34:56Z'))->isTuesday(), 'Sunday');
        $this->assertFalse((new Time('2000-01-03 12:34:56Z'))->isTuesday(), 'Monday');
        $this->assertTrue((new Time('2000-01-04 12:34:56Z'))->isTuesday(), 'Tuesday');
        $this->assertFalse((new Time('2000-01-05 12:34:56Z'))->isTuesday(), 'Wednesday');
        $this->assertFalse((new Time('2000-01-06 12:34:56Z'))->isTuesday(), 'Thursday');
        $this->assertFalse((new Time('2000-01-07 12:34:56Z'))->isTuesday(), 'Friday');
    }

    public function test_isWednesday(): void
    {
        $this->assertFalse((new Time('2000-01-01 12:34:56Z'))->isWednesday(), 'Saturday');
        $this->assertFalse((new Time('2000-01-02 12:34:56Z'))->isWednesday(), 'Sunday');
        $this->assertFalse((new Time('2000-01-03 12:34:56Z'))->isWednesday(), 'Monday');
        $this->assertFalse((new Time('2000-01-04 12:34:56Z'))->isWednesday(), 'Tuesday');
        $this->assertTrue((new Time('2000-01-05 12:34:56Z'))->isWednesday(), 'Wednesday');
        $this->assertFalse((new Time('2000-01-06 12:34:56Z'))->isWednesday(), 'Thursday');
        $this->assertFalse((new Time('2000-01-07 12:34:56Z'))->isWednesday(), 'Friday');
    }

    public function test_isThursday(): void
    {
        $this->assertFalse((new Time('2000-01-01 12:34:56Z'))->isThursday(), 'Saturday');
        $this->assertFalse((new Time('2000-01-02 12:34:56Z'))->isThursday(), 'Sunday');
        $this->assertFalse((new Time('2000-01-03 12:34:56Z'))->isThursday(), 'Monday');
        $this->assertFalse((new Time('2000-01-04 12:34:56Z'))->isThursday(), 'Tuesday');
        $this->assertFalse((new Time('2000-01-05 12:34:56Z'))->isThursday(), 'Wednesday');
        $this->assertTrue((new Time('2000-01-06 12:34:56Z'))->isThursday(), 'Thursday');
        $this->assertFalse((new Time('2000-01-07 12:34:56Z'))->isThursday(), 'Friday');
    }

    public function test_isFriday(): void
    {
        $this->assertFalse((new Time('2000-01-01 12:34:56Z'))->isFriday(), 'Saturday');
        $this->assertFalse((new Time('2000-01-02 12:34:56Z'))->isFriday(), 'Sunday');
        $this->assertFalse((new Time('2000-01-03 12:34:56Z'))->isFriday(), 'Monday');
        $this->assertFalse((new Time('2000-01-04 12:34:56Z'))->isFriday(), 'Tuesday');
        $this->assertFalse((new Time('2000-01-05 12:34:56Z'))->isFriday(), 'Wednesday');
        $this->assertFalse((new Time('2000-01-06 12:34:56Z'))->isFriday(), 'Thursday');
        $this->assertTrue((new Time('2000-01-07 12:34:56Z'))->isFriday(), 'Friday');
    }

    public function test_isSaturday(): void
    {
        $this->assertTrue((new Time('2000-01-01 12:34:56Z'))->isSaturday(), 'Saturday');
        $this->assertFalse((new Time('2000-01-02 12:34:56Z'))->isSaturday(), 'Sunday');
        $this->assertFalse((new Time('2000-01-03 12:34:56Z'))->isSaturday(), 'Monday');
        $this->assertFalse((new Time('2000-01-04 12:34:56Z'))->isSaturday(), 'Tuesday');
        $this->assertFalse((new Time('2000-01-05 12:34:56Z'))->isSaturday(), 'Wednesday');
        $this->assertFalse((new Time('2000-01-06 12:34:56Z'))->isSaturday(), 'Thursday');
        $this->assertFalse((new Time('2000-01-07 12:34:56Z'))->isSaturday(), 'Friday');
    }

    public function test_isSunday(): void
    {
        $this->assertFalse((new Time('2000-01-01 12:34:56Z'))->isSunday(), 'Saturday');
        $this->assertTrue((new Time('2000-01-02 12:34:56Z'))->isSunday(), 'Sunday');
        $this->assertFalse((new Time('2000-01-03 12:34:56Z'))->isSunday(), 'Monday');
        $this->assertFalse((new Time('2000-01-04 12:34:56Z'))->isSunday(), 'Tuesday');
        $this->assertFalse((new Time('2000-01-05 12:34:56Z'))->isSunday(), 'Wednesday');
        $this->assertFalse((new Time('2000-01-06 12:34:56Z'))->isSunday(), 'Thursday');
        $this->assertFalse((new Time('2000-01-07 12:34:56Z'))->isSunday(), 'Friday');
    }

    public function test_isWeekday(): void
    {
        $this->assertFalse((new Time('2000-01-01 12:34:56Z'))->isWeekday(), 'Saturday');
        $this->assertFalse((new Time('2000-01-02 12:34:56Z'))->isWeekday(), 'Sunday');
        $this->assertTrue((new Time('2000-01-03 12:34:56Z'))->isWeekday(), 'Monday');
        $this->assertTrue((new Time('2000-01-04 12:34:56Z'))->isWeekday(), 'Tuesday');
        $this->assertTrue((new Time('2000-01-05 12:34:56Z'))->isWeekday(), 'Wednesday');
        $this->assertTrue((new Time('2000-01-06 12:34:56Z'))->isWeekday(), 'Thursday');
        $this->assertTrue((new Time('2000-01-07 12:34:56Z'))->isWeekday(), 'Friday');
    }

    public function test_isWeekend(): void
    {
        $this->assertTrue((new Time('2000-01-01 12:34:56Z'))->isWeekend(), 'Saturday');
        $this->assertTrue((new Time('2000-01-02 12:34:56Z'))->isWeekend(), 'Sunday');
        $this->assertFalse((new Time('2000-01-03 12:34:56Z'))->isWeekend(), 'Monday');
        $this->assertFalse((new Time('2000-01-04 12:34:56Z'))->isWeekend(), 'Tuesday');
        $this->assertFalse((new Time('2000-01-05 12:34:56Z'))->isWeekend(), 'Wednesday');
        $this->assertFalse((new Time('2000-01-06 12:34:56Z'))->isWeekend(), 'Thursday');
        $this->assertFalse((new Time('2000-01-07 12:34:56Z'))->isWeekend(), 'Friday');
    }

    public function test_isLeapYear(): void
    {
        $this->assertFalse((new Time('1999-01-01 12:34:56Z'))->isLeapYear());
        $this->assertTrue((new Time('2000-01-01 12:34:56Z'))->isLeapYear());
        $this->assertFalse((new Time('2001-01-01 12:34:56Z'))->isLeapYear());
    }

    public function test_isUtc(): void
    {
        $this->assertTrue((new Time('2000-01-01 12:34:56Z'))->isUtc());
        $this->assertFalse((new Time('2000-01-01 12:34:56+09:00'))->isUtc());
    }

    public function test_isDst(): void
    {
        $this->assertFalse((new Time('2000-01-01 12:34:56Z'))->isDst());
        $this->assertTrue((new Time('2000-08-01 12:34:56 America/Los_Angeles'))->isDst());
    }
}
