<?php declare(strict_types=1);

namespace Tests\Kirameki\Time;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Core\Testing\TestCase;
use Kirameki\Time\Instant;
use Kirameki\Time\Unit;
use PHPUnit\Framework\Attributes\DataProvider;
use function json_encode;
use function strlen;

final class
InstantTest extends TestCase
{
    /**
     * @param string $time
     * @return Instant
     */
    protected function parse(string $time): Instant
    {
        return match (strlen($time)) {
            19 => Instant::createFromFormat('Y-m-d H:i:s', $time),
            20 => Instant::createFromFormat('Y-m-d H:i:sP', $time),
            23 => Instant::createFromFormat('Y-m-d H:i:s.v', $time),
            24 => Instant::createFromFormat('Y-m-d H:i:s.vP', $time),
            26 => Instant::createFromFormat('Y-m-d H:i:s.u', $time),
            27 => Instant::createFromFormat('Y-m-d H:i:s.uP', $time),
            default => throw new NotSupportedException(),
        };
    }

    public function test___construct_no_args(): void
    {
        $now = new DateTimeImmutable('now');
        $instant = new Instant();
        $this->assertGreaterThanOrEqual($now->getTimestamp(), $instant->getTimestamp());
        $this->assertSame('+00:00', $instant->getTimezone()->getName());
    }

    public function test___construct_with_time_args(): void
    {
        $time = new Instant(946730096); // 2000-01-01 12:34:56 UTC
        $this->assertSame('2000-01-01 12:34:56.000000Z', $time->toString());
        $this->assertSame('+00:00', $time->getTimezone()->getName());

    }

    public function test_createFromFormat(): void
    {
        $this->assertSame('2000-01-01 12:34:56.000000Z', Instant::createFromFormat('Y m d H i s', '2000 01 01 12 34 56')->toString());
        $this->assertSame('2000-01-01 12:34:56.111000Z', Instant::createFromFormat('Y-m-d H:i:s.u', '2000-01-01 12:34:56.111')->toString());
        $this->assertSame('2000-01-01 12:34:56.111111Z', Instant::createFromFormat('Y-m-d H:i:s.u', '2000-01-01 12:34:56.111111')->toString());
        $this->assertSame('2000-01-01 11:34:56.000000Z', Instant::createFromFormat('Y-m-d H:i:sP', '2000-01-01 12:34:56+01:00')->toString());
        $this->assertSame('2000-01-01 20:34:56.000000Z', Instant::createFromFormat('Y-m-d H:i:s P', '2000-01-01 12:34:56 America/Los_Angeles')->toString());
    }

    public function test_createFromFormat_no_timezone(): void
    {
        $this->expectExceptionMessage('Timezones are not supported as arguments and exists only for compatibility with base class');
        $this->expectException(InvalidArgumentException::class);
        Instant::createFromFormat('Y-m-d H:i:s', '2000-01-01 12:34:56', new DateTimeZone('America/Los_Angeles'));
    }

    public function test_createFromFormat_invalid_format(): void
    {
        $this->expectExceptionMessage('["A four digit year could not be found","Not enough data available to satisfy format"]');
        $this->expectException(InvalidArgumentException::class);
        Instant::createFromFormat('Y-m-d H:i:s.u', 'abc')->toString();
    }

    public function test_createFromFormat_invalid_format_trailing_data(): void
    {
        $this->expectExceptionMessage('["Trailing data"]');
        $this->expectException(InvalidArgumentException::class);
        Instant::createFromFormat('Y', '2001-')->toString();
    }

    public function test_createFromMutable(): void
    {
        $timeUtc = Instant::createFromMutable(new DateTime('2000-01-01 12:34:56Z'));
        $this->assertInstanceOf(Instant::class, $timeUtc);
        $this->assertSame('2000-01-01 12:34:56.000000Z', $timeUtc->toString());

        $timeLocal = Instant::createFromMutable(new DateTime('2000-01-01 12:34:56+09:00'));
        $this->assertSame('2000-01-01 12:34:56.000000+09:00', $timeLocal->toString());
    }

    public function test_createFromInterface(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Kirameki\Time\Instant::createFromInterface() is not supported.');
        Instant::createFromInterface(new DateTimeImmutable('2000-01-01 12:34:56Z'));
    }

    public function test_createFromTimestamp(): void
    {
        $this->assertSame('1970-01-01 00:00:00.000000Z', Instant::createFromTimestamp(0)->toString());
        $this->assertSame('1970-01-01 00:00:01.234567Z', Instant::createFromTimestamp(1.234567)->toString());
        $this->assertSame('2000-01-01 12:34:56.000000Z', Instant::createFromTimestamp(946730096)->toString());
        $this->assertSame('1900-01-01 00:00:00.000000Z', Instant::createFromTimestamp(-2208988800)->toString());
        $this->assertSame('2100-01-01 00:00:00.000000Z', Instant::createFromTimestamp(4102444800)->toString());
    }

    public function test_now(): void
    {
        $now = Instant::now();
        $this->assertInstanceOf(Instant::class, $now);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}.\d{6}Z$/', $now->toString());
    }

    public function test_min(): void
    {
        $this->assertSame('0001-01-01 00:00:00.000000Z', Instant::min()->toString());
    }

    public function test_max(): void
    {
        $this->assertSame('9999-12-31 23:59:59.999999Z', Instant::max()->toString());
    }

    public function test_set(): void
    {
        $now = new Instant(new DateTime('2000-01-01 12:34:56Z'));
        $this->assertSame('2000-01-01 12:34:56.000000Z', $now->set(years: 2000)->toString());
        $this->assertSame('2000-02-01 12:34:56.000000Z', $now->set(months: 2)->toString());
        $this->assertSame('2000-01-02 12:34:56.000000Z', $now->set(days: 2)->toString());
        $this->assertSame('2000-01-01 11:34:56.000000Z', $now->set(hours: 11)->toString());
        $this->assertSame('2000-01-01 12:11:56.000000Z', $now->set(minutes: 11)->toString());
        $this->assertSame('2000-01-01 12:34:12.345678Z', $now->set(seconds: 12.345678)->toString());
        $this->assertSame('2000-01-01 12:35:01.000000Z', $now->set(seconds: 61)->toString());
        $this->assertSame('2000-01-01 12:35:01.123000Z', $now->set(seconds: 61.123)->toString());
        $this->assertSame('2000-01-01 12:35:01.123456Z', $now->set(seconds: 61.123456)->toString());
        $this->assertSame('0001-01-01 00:00:00.000000Z', $now->set(1, 1, 1, 0, 0, 0)->toString(), 'all');
    }

    public function test_shift(): void
    {
        $now = new Instant(new DateTime('2000-01-01 12:34:56Z'));
        $this->assertSame('2000-01-01 12:34:56.000000Z', $now->shift(years: 0)->toString());
        $this->assertSame('2001-01-01 12:34:56.000000Z', $now->shift(years: 1)->toString());
        $this->assertSame('1999-01-01 12:34:56.000000Z', $now->shift(years: -1)->toString());
        $this->assertSame('2000-01-01 12:34:56.000000Z', $now->shift(months: 0)->toString());
        $this->assertSame('2000-02-01 12:34:56.000000Z', $now->shift(months: 1)->toString());
        $this->assertSame('1999-12-01 12:34:56.000000Z', $now->shift(months: -1)->toString());
        $this->assertSame('2000-01-01 12:34:56.000000Z', $now->shift(days: 0)->toString());
        $this->assertSame('2000-01-02 12:34:56.000000Z', $now->shift(days: 1)->toString());
        $this->assertSame('1999-12-31 12:34:56.000000Z', $now->shift(days: -1)->toString());
        $this->assertSame('2000-01-01 12:34:56.000000Z', $now->shift(hours: 0)->toString());
        $this->assertSame('2000-01-01 13:34:56.000000Z', $now->shift(hours: 1)->toString());
        $this->assertSame('2000-01-01 11:34:56.000000Z', $now->shift(hours: -1)->toString());
        $this->assertSame('2000-01-01 12:34:56.000000Z', $now->shift(minutes: 0)->toString());
        $this->assertSame('2000-01-01 12:35:56.000000Z', $now->shift(minutes: 1)->toString());
        $this->assertSame('2000-01-01 12:33:56.000000Z', $now->shift(minutes: -1)->toString());
        $this->assertSame('2000-01-01 12:34:56.000000Z', $now->shift(seconds: 0)->toString());
        $this->assertSame('2000-01-01 12:34:57.000000Z', $now->shift(seconds: 1)->toString());
        $this->assertSame('2000-01-01 12:34:55.000000Z', $now->shift(seconds: -1)->toString());
        $this->assertSame('2000-01-01 12:34:55.999999Z', $now->shift(seconds: -0.000001)->toString());
        $this->assertSame('2000-01-01 12:36:57.000000Z', $now->shift(minutes: 1, seconds: 61)->toString());
    }

    public function test_addUnit(): void
    {
        $this->assertSame('2000-01-01 12:34:56.000000Z', $this->parse('2000-01-01 12:34:56Z')->addUnit(Unit::Second, 0)->toString());
        $this->assertSame('2000-01-01 00:00:00.123000Z', $this->parse('2000-01-01 00:00:00Z')->addUnit(Unit::Second, 0.123)->toString());
        $this->assertSame('2000-01-01 00:00:59.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnit(Unit::Second, 59)->toString());
        $this->assertSame('2000-01-01 00:01:01.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnit(Unit::Second, 61)->toString());
        $this->assertSame('2000-01-01 00:59:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnit(Unit::Minute, 59)->toString());
        $this->assertSame('2000-01-01 01:01:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnit(Unit::Minute, 61)->toString());
        $this->assertSame('2000-01-01 23:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnit(Unit::Hour, 23)->toString());
        $this->assertSame('2000-01-02 01:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnit(Unit::Hour, 25)->toString());
        $this->assertSame('2000-01-31 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnit(Unit::Day, 30)->toString());
        $this->assertSame('2000-02-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnit(Unit::Day, 31)->toString());
        $this->assertSame('2000-12-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnit(Unit::Month, 11)->toString());
        $this->assertSame('2001-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnit(Unit::Month, 12)->toString());
    }

    /**
     * @return list<list<Unit::*>>
     */
    public static function nonFractionalUnitsDataProvider(): array
    {
        return [
            [Unit::Minute],
            [Unit::Hour],
            [Unit::Day],
            [Unit::Month],
            [Unit::Year],
        ];
    }

    #[DataProvider('nonFractionalUnitsDataProvider')]
    public function test_addUnit_float_for_non_second(Unit $unit): void
    {
        $this->expectExceptionMessage('Only seconds can be fractional.');
        $this->expectException(InvalidArgumentException::class);
        new Instant()->addUnit($unit, 0.123)->toString();
    }

    public function test_addUnitWithClamping(): void
    {
        $this->assertSame('2000-01-01 12:34:56.000000Z', $this->parse('2000-01-01 12:34:56Z')->addUnitWithClamping(Unit::Second, 0, Unit::Second)->toString());
        $this->assertSame('2000-01-01 00:00:00.999999Z', $this->parse('2000-01-01 00:00:00Z')->addUnitWithClamping(Unit::Second, 61, Unit::Second)->toString());
        $this->assertSame('2000-01-01 00:00:59.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnitWithClamping(Unit::Second, 59, Unit::Minute)->toString());
        $this->assertSame('2000-01-01 00:00:59.999999Z', $this->parse('2000-01-01 00:00:00Z')->addUnitWithClamping(Unit::Second, 61, Unit::Minute)->toString());
        $this->assertSame('2000-01-01 00:59:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnitWithClamping(Unit::Minute, 59, Unit::Hour)->toString());
        $this->assertSame('2000-01-01 00:59:59.999999Z', $this->parse('2000-01-01 00:00:00Z')->addUnitWithClamping(Unit::Minute, 61, Unit::Hour)->toString());
        $this->assertSame('2000-01-01 23:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnitWithClamping(Unit::Hour, 23, Unit::Day)->toString());
        $this->assertSame('2000-01-01 23:59:59.999999Z', $this->parse('2000-01-01 00:00:00Z')->addUnitWithClamping(Unit::Hour, 25, Unit::Day)->toString());
        $this->assertSame('2000-01-31 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnitWithClamping(Unit::Day, 30, Unit::Month)->toString());
        $this->assertSame('2000-01-31 23:59:59.999999Z', $this->parse('2000-01-01 00:00:00Z')->addUnitWithClamping(Unit::Day, 31, Unit::Month)->toString());
        $this->assertSame('2000-12-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addUnitWithClamping(Unit::Month, 11, Unit::Year)->toString());
        $this->assertSame('2000-12-31 23:59:59.999999Z', $this->parse('2000-01-01 00:00:00Z')->addUnitWithClamping(Unit::Month, 12, Unit::Year)->toString());
        $this->assertSame('2000-01-31 23:59:59.999999Z', $this->parse('2000-01-01 00:00:00Z')->addUnitWithClamping(Unit::Year, 2, Unit::Month)->toString());
    }

    #[DataProvider('nonFractionalUnitsDataProvider')]
    public function test_addUnitWithClamping_float_for_non_second(Unit $unit): void
    {
        $this->expectExceptionMessage('Only seconds can be fractional.');
        $this->expectException(InvalidArgumentException::class);
        new Instant()->addUnitWithClamping($unit, 0.123, Unit::Year)->toString();
    }

    public function test_addYears(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addYears(0)->toString());
        $this->assertSame('2001-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addYears(1)->toString());
    }

    public function test_addYears_with_negative_value(): void
    {
        $this->expectExceptionMessage('$amount must be positive.');
        $this->expectException(InvalidArgumentException::class);
        $this->parse('2000-01-01 00:00:00Z')->addYears(-1);
    }

    public function test_addMonths(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addMonths(0)->toString());
        $this->assertSame('2000-02-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addMonths(1)->toString());
        $this->assertSame('2000-02-29 00:00:00.000000Z', $this->parse('2000-01-31 00:00:00Z')->addMonths(1)->toString());
        $this->assertSame('2000-03-02 00:00:00.000000Z', $this->parse('2000-01-31 00:00:00Z')->addMonths(1, true)->toString());
        $this->assertSame('2000-03-31 00:00:00.000000Z', $this->parse('2000-01-31 00:00:00Z')->addMonths(2, true)->toString());
        $this->assertSame('2000-05-01 00:00:00.000000Z', $this->parse('2000-03-31 00:00:00Z')->addMonths(1, true)->toString());
    }

    public function test_addMonths_with_negative_value(): void
    {
        $this->expectExceptionMessage('$amount must be positive.');
        $this->expectException(InvalidArgumentException::class);
        $this->parse('2000-01-01 00:00:00Z')->addMonths(-1);
    }

    public function test_addDays(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addDays(0)->toString());
        $this->assertSame('2000-01-02 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addDays(1)->toString());
        $this->assertSame('2000-02-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addDays(31)->toString());
        $this->assertSame('2000-02-02 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addDays(32)->toString());
    }

    public function test_addDays_with_negative_value(): void
    {
        $this->expectExceptionMessage('$amount must be positive.');
        $this->expectException(InvalidArgumentException::class);
        $this->parse('2000-01-01 00:00:00Z')->addDays(-1);
    }

    public function test_addHours(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addHours(0)->toString());
        $this->assertSame('2000-01-01 01:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addHours(1)->toString());
        $this->assertSame('2000-01-02 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addHours(24)->toString());
        $this->assertSame('2000-01-02 01:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addHours(25)->toString());
    }

    public function test_addHours_with_negative_value(): void
    {
        $this->expectExceptionMessage('$amount must be positive.');
        $this->expectException(InvalidArgumentException::class);
        $this->parse('2000-01-01 00:00:00Z')->addHours(-1);
    }

    public function test_addMinutes(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addMinutes(0)->toString());
        $this->assertSame('2000-01-01 00:01:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addMinutes(1)->toString());
        $this->assertSame('2000-01-01 01:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addMinutes(60)->toString());
    }

    public function test_addMinutes_with_negative_value(): void
    {
        $this->expectExceptionMessage('$amount must be positive.');
        $this->expectException(InvalidArgumentException::class);
        $this->parse('2000-01-01 00:00:00Z')->addMinutes(-1);
    }

    public function test_addSeconds(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addSeconds(0)->toString());
        $this->assertSame('2000-01-01 00:00:01.000000Z', $this->parse('2000-01-01 00:00:00Z')->addSeconds(1)->toString());
        $this->assertSame('2000-01-01 00:00:00.900000Z', $this->parse('2000-01-01 00:00:00Z')->addSeconds(0.9)->toString());
        $this->assertSame('2000-01-01 00:01:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->addSeconds(60)->toString());
    }

    public function test_addSeconds_with_negative_value(): void
    {
        $this->expectExceptionMessage('$amount must be positive.');
        $this->expectException(InvalidArgumentException::class);
        $this->parse('2000-01-01 00:00:00Z')->addSeconds(-1);
    }

    public function test_subtractUnit(): void
    {
        $this->assertSame('2000-01-01 12:34:56.000000Z', $this->parse('2000-01-01 12:34:56Z')->subtractUnit(Unit::Second, 0)->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00.123Z')->subtractUnit(Unit::Second, 0.123)->toString());
        $this->assertSame('2000-01-01 00:00:01.000000Z', $this->parse('2000-01-01 00:01:00Z')->subtractUnit(Unit::Second, 59)->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:01:01Z')->subtractUnit(Unit::Second, 61)->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:59:00Z')->subtractUnit(Unit::Minute, 59)->toString());
        $this->assertSame('1999-12-31 23:59:00.000000Z', $this->parse('2000-01-01 01:00:00Z')->subtractUnit(Unit::Minute, 61)->toString());
        $this->assertSame('2000-01-01 01:00:00.000000Z', $this->parse('2000-01-02 00:00:00Z')->subtractUnit(Unit::Hour, 23)->toString());
        $this->assertSame('1999-12-31 23:00:00.000000Z', $this->parse('2000-01-02 00:00:00Z')->subtractUnit(Unit::Hour, 25)->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-31 00:00:00Z')->subtractUnit(Unit::Day, 30)->toString());
        $this->assertSame('1999-12-31 00:00:00.000000Z', $this->parse('2000-01-31 00:00:00Z')->subtractUnit(Unit::Day, 31)->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-12-01 00:00:00Z')->subtractUnit(Unit::Month, 11)->toString());
        $this->assertSame('1999-12-01 00:00:00.000000Z', $this->parse('2000-12-01 00:00:00Z')->subtractUnit(Unit::Month, 12)->toString());
    }

    #[DataProvider('nonFractionalUnitsDataProvider')]
    public function test_subtractUnit_float_for_non_second(Unit $unit): void
    {
        $this->expectExceptionMessage('Only seconds can be fractional.');
        $this->expectException(InvalidArgumentException::class);
        Instant::now()->subtractUnit($unit, 0.123)->toString();
    }

    public function test_subtractUnitWithClamping(): void
    {
        $this->assertSame('2000-01-01 12:34:56.000000Z', $this->parse('2000-01-01 12:34:56Z')->subtractUnitWithClamping(Unit::Second, 0, Unit::Second)->toString());
        $this->assertSame('2000-01-01 01:00:00.000000Z', $this->parse('2000-01-01 01:00:00.999999Z')->subtractUnitWithClamping(Unit::Second, 61, Unit::Second)->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:58.123Z')->subtractUnitWithClamping(Unit::Second, 59, Unit::Minute)->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:59.999999Z')->subtractUnitWithClamping(Unit::Second, 61, Unit::Minute)->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:58:59.999999Z')->subtractUnitWithClamping(Unit::Minute, 59, Unit::Hour)->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:59:59.999999Z')->subtractUnitWithClamping(Unit::Minute, 61, Unit::Hour)->toString());
        $this->assertSame('2000-01-01 00:59:59.999999Z', $this->parse('2000-01-01 23:59:59.999999Z')->subtractUnitWithClamping(Unit::Hour, 23, Unit::Day)->toString());
        $this->assertSame('2000-01-02 00:00:00.000000Z', $this->parse('2000-01-02 01:00:00.999999Z')->subtractUnitWithClamping(Unit::Hour, 25, Unit::Day)->toString());
        $this->assertSame('2000-02-01 00:00:00.000000Z', $this->parse('2000-02-01 00:00:00.999999Z')->subtractUnitWithClamping(Unit::Day, 30, Unit::Month)->toString());
        $this->assertSame('2000-02-01 00:00:00.000000Z', $this->parse('2000-02-01 23:59:59.999999Z')->subtractUnitWithClamping(Unit::Day, 31, Unit::Month)->toString());
        $this->assertSame('2001-01-01 00:00:00.000000Z', $this->parse('2001-01-01 00:00:00.999999Z')->subtractUnitWithClamping(Unit::Month, 11, Unit::Year)->toString());
        $this->assertSame('2001-01-01 00:00:00.000000Z', $this->parse('2001-01-01 23:59:59.999999Z')->subtractUnitWithClamping(Unit::Month, 12, Unit::Year)->toString());
    }

    #[DataProvider('nonFractionalUnitsDataProvider')]
    public function test_subtractUnitWithClamping_float_for_non_second(Unit $unit): void
    {
        $this->expectExceptionMessage('Only seconds can be fractional.');
        $this->expectException(InvalidArgumentException::class);
        Instant::now()->subtractUnitWithClamping($unit, 0.123, Unit::Year)->toString();
    }

    public function test_subtractYears(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractYears(0)->toString());
        $this->assertSame('1999-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractYears(1)->toString());
    }

    public function test_subtractYears_with_negative_value(): void
    {
        $this->expectExceptionMessage('$amount must be positive.');
        $this->expectException(InvalidArgumentException::class);
        $this->parse('2000-01-01 00:00:00Z')->subtractYears(-1);
    }

    public function test_subtractMonths(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractMonths(0)->toString());
        $this->assertSame('1999-12-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractMonths(1)->toString());
        $this->assertSame('1999-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractMonths(12)->toString());
        $this->assertSame('1998-12-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractMonths(13)->toString());
        $this->assertSame('2000-02-29 00:00:00.000000Z', $this->parse('2000-03-31 00:00:00Z')->subtractMonths(1)->toString());
        $this->assertSame('2000-02-29 00:00:00.000000Z', $this->parse('2000-04-30 00:00:00Z')->subtractMonths(2)->toString());
        $this->assertSame('2000-03-02 00:00:00.000000Z', $this->parse('2000-03-31 00:00:00Z')->subtractMonths(1, true)->toString());
        $this->assertSame('2000-03-01 00:00:00.000000Z', $this->parse('2000-04-30 00:00:00Z')->subtractMonths(2, true)->toString());
    }

    public function test_subtractMonths_with_negative_value(): void
    {
        $this->expectExceptionMessage('$amount must be positive.');
        $this->expectException(InvalidArgumentException::class);
        $this->parse('2000-01-01 00:00:00Z')->subtractMonths(-1);
    }

    public function test_subtractDays(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractDays(0)->toString());
        $this->assertSame('1999-12-31 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractDays(1)->toString());
        $this->assertSame('1999-12-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractDays(31)->toString());
        $this->assertSame('1999-11-30 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractDays(32)->toString());
    }

    public function test_subtractDays_with_negative_value(): void
    {
        $this->expectExceptionMessage('$amount must be positive.');
        $this->expectException(InvalidArgumentException::class);
        $this->parse('2000-01-01 00:00:00Z')->subtractDays(-1);
    }

    public function test_subtractHours(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractHours(0)->toString());
        $this->assertSame('1999-12-31 23:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractHours(1)->toString());
        $this->assertSame('1999-12-31 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractHours(24)->toString());
        $this->assertSame('1999-12-30 23:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractHours(25)->toString());
    }

    public function test_subtractHours_with_negative_value(): void
    {
        $this->expectExceptionMessage('$amount must be positive.');
        $this->expectException(InvalidArgumentException::class);
        $this->parse('2000-01-01 00:00:00Z')->subtractHours(-1);
    }

    public function test_subtractMinutes(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractMinutes(0)->toString());
        $this->assertSame('1999-12-31 23:59:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractMinutes(1)->toString());
        $this->assertSame('1999-12-31 23:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractMinutes(60)->toString());
    }

    public function test_subtractMinutes_with_negative_value(): void
    {
        $this->expectExceptionMessage('$amount must be positive.');
        $this->expectException(InvalidArgumentException::class);
        $this->parse('2000-01-01 00:00:00Z')->subtractMinutes(-1);
    }

    public function test_subtractSeconds(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractSeconds(0)->toString());
        $this->assertSame('1999-12-31 23:59:59.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractSeconds(1)->toString());
        $this->assertSame('1999-12-31 23:59:59.100000Z', $this->parse('2000-01-01 00:00:00Z')->subtractSeconds(0.9)->toString());
        $this->assertSame('1999-12-31 23:59:00.000000Z', $this->parse('2000-01-01 00:00:00Z')->subtractSeconds(60)->toString());
    }

    public function test_subtractSeconds_with_negative_value(): void
    {
        $this->expectExceptionMessage('$amount must be positive.');
        $this->expectException(InvalidArgumentException::class);
        $this->parse('2000-01-01 00:00:00Z')->subtractSeconds(-1);
    }

    public function test_toStartOfUnit(): void
    {
        $this->assertSame('2000-01-01 12:34:56.000000Z', $this->parse('2000-01-01 12:34:56Z')->toStartOfUnit(Unit::Second)->toString());
        $this->assertSame('2000-01-01 12:34:00.000000Z', $this->parse('2000-01-01 12:34:56Z')->toStartOfUnit(Unit::Minute)->toString());
        $this->assertSame('2000-01-01 12:00:00.000000Z', $this->parse('2000-01-01 12:34:56Z')->toStartOfUnit(Unit::Hour)->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 12:34:56Z')->toStartOfUnit(Unit::Day)->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 12:34:56Z')->toStartOfUnit(Unit::Month)->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 12:34:56Z')->toStartOfUnit(Unit::Year)->toString());
    }

    public function test_toEndOfUnit(): void
    {
        $this->assertSame('2000-01-01 12:34:56.999999Z', $this->parse('2000-01-01 12:34:56Z')->toEndOfUnit(Unit::Second)->toString());
        $this->assertSame('2000-01-01 12:34:59.999999Z', $this->parse('2000-01-01 12:34:56Z')->toEndOfUnit(Unit::Minute)->toString());
        $this->assertSame('2000-01-01 12:59:59.999999Z', $this->parse('2000-01-01 12:34:56Z')->toEndOfUnit(Unit::Hour)->toString());
        $this->assertSame('2000-01-01 23:59:59.999999Z', $this->parse('2000-01-01 12:34:56Z')->toEndOfUnit(Unit::Day)->toString());
        $this->assertSame('2000-01-31 23:59:59.999999Z', $this->parse('2000-01-01 12:34:56Z')->toEndOfUnit(Unit::Month)->toString());
        $this->assertSame('2000-12-31 23:59:59.999999Z', $this->parse('2000-01-01 12:34:56Z')->toEndOfUnit(Unit::Year)->toString());
    }

    public function test_toStartOfYear(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00.000000Z')->toStartOfYear()->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 23:59:59.999999Z')->toStartOfYear()->toString());
    }

    public function test_toEndOfYear(): void
    {
        $this->assertSame('2000-12-31 23:59:59.999999Z', $this->parse('2000-01-01 00:00:00.000000Z')->toEndOfYear()->toString());
        $this->assertSame('2001-12-31 23:59:59.999999Z', $this->parse('2001-01-01 00:00:00.000000Z')->toEndOfYear()->toString());
    }

    public function test_toStartOfMonth(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00.000000Z')->toStartOfMonth()->toString());
        $this->assertSame('2000-02-01 00:00:00.000000Z', $this->parse('2000-02-29 00:00:00.000000Z')->toStartOfMonth()->toString());
        $this->assertSame('2001-12-01 00:00:00.000000Z', $this->parse('2001-12-01 00:00:00.000000Z')->toStartOfMonth()->toString());
        $this->assertSame('2000-12-01 00:00:00.000000Z', $this->parse('2000-12-31 23:59:59.999999Z')->toStartOfMonth()->toString());
    }

    public function test_toEndOfMonth(): void
    {
        $this->assertSame('2000-01-31 23:59:59.999999Z', $this->parse('2000-01-01 00:00:00.000000Z')->toEndOfMonth()->toString());
        $this->assertSame('2000-02-29 23:59:59.999999Z', $this->parse('2000-02-01 00:00:00.000000Z')->toEndOfMonth()->toString());
        $this->assertSame('2001-02-28 23:59:59.999999Z', $this->parse('2001-02-01 00:00:00.000000Z')->toEndOfMonth()->toString());
        $this->assertSame('2001-03-31 23:59:59.999999Z', $this->parse('2001-03-01 00:00:00.000000Z')->toEndOfMonth()->toString());
        $this->assertSame('2001-12-31 23:59:59.999999Z', $this->parse('2001-12-01 00:00:00.000000Z')->toEndOfMonth()->toString());
        $this->assertSame('2000-12-31 23:59:59.999999Z', $this->parse('2000-12-31 23:59:59.999999Z')->toEndOfMonth()->toString());
    }

    public function test_toStartOfDay(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00.000000Z')->toStartOfDay()->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 23:59:59.999999Z')->toStartOfDay()->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 12:34:56Z')->toStartOfDay()->toString());
    }

    public function test_toEndOfDay(): void
    {
        $this->assertSame('2000-01-01 23:59:59.999999Z', $this->parse('2000-01-01 00:00:00.000000Z')->toEndOfDay()->toString());
        $this->assertSame('2000-01-01 23:59:59.999999Z', $this->parse('2000-01-01 23:59:59.999999Z')->toEndOfDay()->toString());
        $this->assertSame('2000-01-01 23:59:59.999999Z', $this->parse('2000-01-01 12:34:56Z')->toEndOfDay()->toString());
    }

    public function test_toStartOfHour(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00.000000Z')->toStartOfHour()->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:59:59.999999Z')->toStartOfHour()->toString());
        $this->assertSame('2000-01-01 12:00:00.000000Z', $this->parse('2000-01-01 12:34:56Z')->toStartOfHour()->toString());
    }

    public function test_toEndOfHour(): void
    {
        $this->assertSame('2000-01-01 00:59:59.999999Z', $this->parse('2000-01-01 00:00:00.000000Z')->toEndOfHour()->toString());
        $this->assertSame('2000-01-01 00:59:59.999999Z', $this->parse('2000-01-01 00:59:59.999999Z')->toEndOfHour()->toString());
        $this->assertSame('2000-01-01 12:59:59.999999Z', $this->parse('2000-01-01 12:34:56Z')->toEndOfHour()->toString());
    }

    public function test_toStartOfMinute(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00.000000Z')->toStartOfMinute()->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:59.999999Z')->toStartOfMinute()->toString());
        $this->assertSame('2000-01-01 12:34:00.000000Z', $this->parse('2000-01-01 12:34:56Z')->toStartOfMinute()->toString());
    }

    public function test_toEndOfMinute(): void
    {
        $this->assertSame('2000-01-01 00:00:59.999999Z', $this->parse('2000-01-01 00:00:00.000000Z')->toEndOfMinute()->toString());
        $this->assertSame('2000-01-01 00:00:59.999999Z', $this->parse('2000-01-01 00:00:59.999999Z')->toEndOfMinute()->toString());
        $this->assertSame('2000-01-01 12:34:59.999999Z', $this->parse('2000-01-01 12:34:56Z')->toEndOfMinute()->toString());
    }

    public function test_toStartOfSecond(): void
    {
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00.000000Z')->toStartOfSecond()->toString());
        $this->assertSame('2000-01-01 00:00:00.000000Z', $this->parse('2000-01-01 00:00:00.999999Z')->toStartOfSecond()->toString());
        $this->assertSame('2000-01-01 12:34:56.000000Z', $this->parse('2000-01-01 12:34:56Z')->toStartOfSecond()->toString());
    }

    public function test_toEndOfSecond(): void
    {
        $this->assertSame('2000-01-01 00:00:00.999999Z', $this->parse('2000-01-01 00:00:00.000000Z')->toEndOfSecond()->toString());
        $this->assertSame('2000-01-01 00:00:00.999999Z', $this->parse('2000-01-01 00:00:00.999999Z')->toEndOfSecond()->toString());
        $this->assertSame('2000-01-01 12:34:56.999999Z', $this->parse('2000-01-01 12:34:56Z')->toEndOfSecond()->toString());
    }

    public function test_clamp(): void
    {
        // clamp exact
        $lower = $this->parse('2000-01-01 12:34:56.000000Z');
        $upper = clone $lower;
        $clamped = $this->parse('2000-01-01 12:34:56.000002Z')->clamp($lower, $upper);
        $this->assertSame($lower->toString(), $clamped->toString(), 'clamp exact');
        $this->assertSame($upper->toString(), $clamped->toString(), 'clamp exact');

        // in range
        $source = $this->parse('2000-01-01 12:34:56.000001Z');
        $lower = $this->parse('2000-01-01 12:34:56.000000Z');
        $upper = $this->parse('2000-01-01 12:34:56.000002Z');
        $clamped = $source->clamp($lower, $upper);
        $this->assertSame($source->toString(), $clamped->toString(), 'in range');

        // clamp upper
        $lower = $this->parse('2000-01-01 12:34:56.000000Z');
        $upper = $this->parse('2000-01-01 12:34:56.000001Z');
        $clamped = $this->parse('2000-01-01 12:34:56.000002Z')->clamp($lower, $upper);
        $this->assertSame($upper->toString(), $clamped->toString(), 'clamp upper');

        // clamp lower
        $lower = $this->parse('2000-01-01 12:34:56.000001Z');
        $upper = $this->parse('2000-01-01 12:34:56.000002Z');
        $clamped = $this->parse('2000-01-01 12:34:56.000000Z')->clamp($lower, $upper);
        $this->assertSame($lower->toString(), $clamped->toString(), 'clamp lower');

        // no upper
        $source = $this->parse('2100-01-01 00:00:00Z');
        $lower = $this->parse('2000-01-01 12:34:56.000001Z');
        $clamped = $source->clamp($lower);
        $this->assertSame($source->toString(), $clamped->toString(), 'clamp lower');

        // no lower
        $source = $this->parse('0000-01-01 00:00:00Z');
        $upper = $this->parse('2000-01-01 12:34:56.000001Z');
        $clamped = $source->clamp(null, $upper);
        $this->assertSame($source->toString(), $clamped->toString(), 'clamp lower');
    }

    public function test_clamp_no_args(): void
    {
        $this->expectExceptionMessage('At least one of $lower or $upper must be specified.');
        $this->expectException(InvalidArgumentException::class);
        Instant::now()->clamp();
    }

    public function test_between(): void
    {
        $ymdhis = '2000-01-01 12:34:56';
        $this->assertTrue($this->parse($ymdhis . '.000001Z')->between($this->parse($ymdhis . '.000001Z'), $this->parse($ymdhis . '.000001Z')), 'all same');
        $this->assertTrue($this->parse($ymdhis . '.000001Z')->between($this->parse($ymdhis . '.000000Z'), $this->parse($ymdhis . '.000001Z')), 'match right');
        $this->assertTrue($this->parse($ymdhis . '.000001Z')->between($this->parse($ymdhis . '.000001Z'), $this->parse($ymdhis . '.000002Z')), 'match left');
        $this->assertTrue($this->parse($ymdhis . '.000001Z')->between($this->parse($ymdhis . '.000000Z'), $this->parse($ymdhis . '.000002Z')), 'contained');
        $this->assertFalse($this->parse($ymdhis . '.000001Z')->between($this->parse($ymdhis . '.000002Z'), $this->parse($ymdhis . '.000000Z')), 'reversed');
    }

    public function test_isPast(): void
    {
        $now = new Instant();
        $this->assertFalse($now->isPast($now));

        $now = new Instant();
        $this->assertFalse($now->addSeconds(0.1)->isPast($now));
        $this->assertTrue($now->subtractSeconds(0.1)->isPast($now));
    }

    public function test_isFuture(): void
    {
        $now = new Instant();
        $this->assertFalse($now->isFuture($now));

        $now = new Instant();
        $this->assertTrue($now->addSeconds(0.1)->isFuture($now));
        $this->assertFalse($now->subtractSeconds(0.1)->isFuture($now));
    }

    public function test_jsonSerialize(): void
    {
        $this->assertSame('"2000-01-01 12:34:56.000000Z"', json_encode($this->parse('2000-01-01 12:34:56Z')));
        $this->assertSame('2000-01-01 12:34:56.000000Z', $this->parse('2000-01-01 12:34:56Z')->jsonSerialize());
    }

    public function test_toDateTimeImmutable(): void
    {
        $base = $this->parse('1970-01-01 00:00:01Z')->toDateTimeImmutable();
        $this->assertSame(DateTimeImmutable::class, $base::class);
        $this->assertSame(1, $base->getTimestamp());
    }

    public function test_toInt(): void
    {
        $this->assertSame(0, $this->parse('1970-01-01 00:00:00Z')->toInt(), 'zero');
        $this->assertSame(1, $this->parse('1970-01-01 00:00:01.123456Z')->toInt(), 'microseconds cutoff');
        $this->assertSame(946730096, $this->parse('2000-01-01 12:34:56Z')->toInt(), 'current');
        $this->assertSame(-2208988800, $this->parse('1900-01-01 00:00:00Z')->toInt(), 'before');
        $this->assertSame(4102444800, $this->parse('2100-01-01 00:00:00Z')->toInt(), 'future');
    }

    public function test_toFloat(): void
    {
        $this->assertSame(0.0, $this->parse('1970-01-01 00:00:00Z')->toFloat(), 'zero');
        $this->assertSame(1.123456, $this->parse('1970-01-01 00:00:01.123456Z')->toFloat(), 'with microseconds');
        $this->assertSame(946730096.0, $this->parse('2000-01-01 12:34:56Z')->toFloat(), 'current');
        $this->assertSame(-2208988800.0, $this->parse('1900-01-01 00:00:00Z')->toFloat(), 'before');
        $this->assertSame(4102444800.0, $this->parse('2100-01-01 00:00:00Z')->toFloat(), 'future');
    }

    public function test_toString(): void
    {
        $this->assertSame('2000-01-01 12:34:56.000000Z', $this->parse('2000-01-01 12:34:56Z')->toString());
    }

    public function test___toString(): void
    {
        $this->assertSame('2000-01-01 12:34:56.000000Z', $this->parse('2000-01-01 12:34:56Z')->__toString());
    }

    public function test___toString_cast(): void
    {
        $this->assertSame('2000-01-01 12:34:56.000000Z', (string) $this->parse('2000-01-01 12:34:56Z'));
    }

    public function test_getYear(): void
    {
        $this->assertSame(2000, $this->parse('2000-01-01 12:34:56Z')->getYear());
    }

    public function test_getMonth(): void
    {
        $this->assertSame(2, $this->parse('2000-02-29 12:34:56Z')->getMonth());
    }

    public function test_getDay(): void
    {
        $this->assertSame(29, $this->parse('2000-02-29 12:34:56Z')->getDay());
    }

    public function test_getHours(): void
    {
        $this->assertSame(12, $this->parse('2000-01-01 12:34:56Z')->getHours());
    }

    public function test_getMinutes(): void
    {
        $this->assertSame(34, $this->parse('2000-01-01 12:34:56Z')->getMinutes());
    }

    public function test_getSeconds(): void
    {
        $this->assertSame(56.0, $this->parse('2000-01-01 12:34:56Z')->getSeconds());
    }
}
