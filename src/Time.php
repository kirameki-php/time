<?php declare(strict_types=1);

namespace Kirameki\Time;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Time\Exceptions\InvalidFormatException;
use Stringable;
use function assert;
use function date_default_timezone_get;
use function explode;
use function floor;
use function implode;
use function in_array;
use function is_float;
use function str_pad;
use const STR_PAD_LEFT;

/**
 * @phpstan-consistent-constructor
 */
class Time extends DateTimeImmutable implements JsonSerializable, Stringable
{
    public const RFC3339_HUMAN = 'Y-m-d H:i:s.up';
    public const MIN = '0001-01-01 00:00:00.000000';
    public const MAX = '9999-12-31 23:59:59.999999';

    # region Creation --------------------------------------------------------------------------------------------------

    /**
     * @param string|null $datetime
     */
    public function __construct(?string $datetime = null)
    {
        $datetime ??= 'now';

        parent::__construct($datetime);

        $errors = DateTime::getLastErrors();
        if ($errors !== false) {
            static::throwLastError($errors, $datetime);
        }
    }

    /**
     * @param string $datetime
     * @return static
     */
    public static function parse(string $datetime): static
    {
        return new static($datetime);
    }

    /**
     * @inheritDoc
     */
    public static function createFromFormat(string $format, string $datetime, ?DateTimeZone $timezone = null): static
    {
        if ($timezone !== null) {
            throw new InvalidArgumentException('Timezones are not supported as arguments and exists only for compatibility with base class.', [
                'format' => $format,
                'datetime' => $datetime,
                'timezone' => $timezone,
            ]);
        }

        $instance = parent::createFromFormat($format, $datetime);

        // NOTE: Invalid dates (ex: Feb 30th) can slip through, so we handle that here
        if ($instance === false) {
            $errors = DateTime::getLastErrors();
            assert($errors !== false);
            static::throwLastError($errors, $datetime);
        }

        return $instance;
    }

    /**
     * @inheritDoc
     */
    public static function createFromMutable(DateTime $object): static
    {
        return static::createFromInterface($object);
    }

    /**
     * @inheritDoc
     */
    public static function createFromInterface(DateTimeInterface $object): static
    {
        return parent::createFromInterface($object);
    }

    /**
     * @param int|float $timestamp
     * @return static
     */
    public static function createFromTimestamp(int|float $timestamp): static
    {
        return self::parse('@' . $timestamp)->setTimezone(static::getCurrentTimeZone());
    }

    /**
     * @return static
     */
    public static function now(): static
    {
        return new static();
    }

    /**
     * @return static
     */
    public static function today(): static
    {
        return static::parse('today');
    }

    /**
     * @return static
     */
    public static function yesterday(): static
    {
        return static::parse('yesterday');
    }

    /**
     * @return static
     */
    public static function tomorrow(): static
    {
        return static::parse('tomorrow');
    }

    /**
     * @return static
     */
    public static function min(): static
    {
        return static::parse(self::MIN);
    }

    /**
     * @return static
     */
    public static function max(): static
    {
        return static::parse(self::MAX);
    }

    protected static function getCurrentTimeZone(): DateTimeZone
    {
        return new DateTimeZone(date_default_timezone_get());
    }

    /**
     * @param array{errors: list<string>, warnings: list<string>} $errors
     * @param string $datetime
     * @return never
     */
    protected static function throwLastError(array $errors, string $datetime): never
    {
        throw new InvalidFormatException($errors, [
            'datetime' => $datetime,
        ]);
    }

    # endregion Creation -----------------------------------------------------------------------------------------------

    # region Mutation --------------------------------------------------------------------------------------------------

    /**
     * @param int|null $years
     * @param int|null $months
     * @param int|null $days
     * @param int|null $hours
     * @param int|null $minutes
     * @param float|null $seconds
     * @return static
     */
    public function set(
        ?int $years = null,
        ?int $months = null,
        ?int $days = null,
        ?int $hours = null,
        ?int $minutes = null,
        ?float $seconds = null,
    ): static
    {
        $parts = explode(' ', $this->format('Y m d H i s u P'));

        if ($years !== null) {
            $parts[0] = (string) $years;
        }
        if ($months !== null) {
            $parts[1] = str_pad((string) $months, 2, '0', STR_PAD_LEFT);
        }
        if ($days !== null) {
            $parts[2] = str_pad((string) $days, 2, '0', STR_PAD_LEFT);
        }
        if ($hours !== null) {
            $parts[3] = str_pad((string) $hours, 2, '0', STR_PAD_LEFT);
        }
        if ($minutes !== null) {
            $parts[4] = str_pad((string) $minutes, 2, '0', STR_PAD_LEFT);
        }
        if ($seconds !== null) {
            $intSeconds = floor($seconds);
            $parts[5] = str_pad((string) $intSeconds, 2, '0', STR_PAD_LEFT);
            $parts[6] = (string) (($seconds - $intSeconds) * 1e6);
        }

        return static::createFromFormat('Y n j G i s u P', implode(' ', $parts));
    }

    /**
     * @param int|null $years
     * @param int|null $months
     * @param int|null $days
     * @param int|null $hours
     * @param int|null $minutes
     * @param float|null $seconds
     * @return static
     */
    public function shift(
        ?int $years = null,
        ?int $months = null,
        ?int $days = null,
        ?int $hours = null,
        ?int $minutes = null,
        int|float|null $seconds = null,
    ): static
    {
        $mods = [];
        if ($years !== null) {
            $mods[]= ($years >= 0 ? '+' : '') . "{$years} year";
        }
        if ($months !== null) {
            $mods[]= ($months >= 0 ? '+' : '') . "{$months} month";
        }
        if ($days !== null) {
            $mods[]= ($days >= 0 ? '+' : '') . "{$days} day";
        }
        if ($hours !== null) {
            $mods[]= ($hours >= 0 ? '+' : '') . "{$hours} hour";
        }
        if ($minutes !== null) {
            $mods[]= ($minutes >= 0 ? '+' : '') . "{$minutes} minute";
        }
        if ($seconds !== null) {
            $microseconds = (int) ($seconds * 1000000);
            $mods[]= ($microseconds >= 0 ? '+' : '') . "{$microseconds} microseconds";
        }
        return $this->modify(implode(' ', $mods));
    }

    /**
     * @param Unit $unit
     * @param int|float $amount
     * @return static
     */
    public function addUnit(Unit $unit, int|float $amount): static
    {
        if (is_float($amount)) {
            return match ($unit) {
                Unit::Second => $this->addSeconds($amount),
                default => throw new InvalidArgumentException('Only seconds can be fractional.', [
                    'unit' => $unit,
                    'amount' => $amount,
                ]),
            };
        }

        return match ($unit) {
            Unit::Year => $this->addYears($amount),
            Unit::Month => $this->addMonths($amount),
            Unit::Day => $this->addDays($amount),
            Unit::Hour => $this->addHours($amount),
            Unit::Minute => $this->addMinutes($amount),
            Unit::Second => $this->addSeconds($amount),
        };
    }

    /**
     * @param Unit $unit
     * @param int|float $amount
     * @param Unit $clamp
     * @return static
     */
    public function addUnitWithClamping(Unit $unit, int|float $amount, Unit $clamp): static
    {
        $end = $this->toEndOfUnit($clamp);
        $added = $this->addUnit($unit, $amount);

        return ($added > $end)
            ? $end
            : $added;
    }

    /**
     * @param int $amount
     * @return static
     */
    public function addYears(int $amount): static
    {
        return $this->shift(years: static::ensurePositive($amount));
    }

    /**
     * @param int $amount
     * @param bool $overflow
     * @return static
     */
    public function addMonths(int $amount, bool $overflow = false): static
    {
        $added = $this->shift(months: static::ensurePositive($amount));

        if (!$overflow) {
            if ($added->getDay() === $this->getDay()) {
                return $added;
            }

            $fix = $added->set(days: 1)->subtractMonths(1);
            return $fix->set(days: $fix->getDaysInMonth());
        }

        return $added;
    }

    /**
     * @param int $amount
     * @return static
     */
    public function addDays(int $amount): static
    {
        return $this->shift(days: static::ensurePositive($amount));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function addHours(int $amount): static
    {
        return $this->shift(hours: static::ensurePositive($amount));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function addMinutes(int $amount): static
    {
        return $this->shift(minutes: static::ensurePositive($amount));
    }

    /**
     * @param int|float $amount
     * @return static
     */
    public function addSeconds(int|float $amount): static
    {
        return $this->shift(seconds: static::ensurePositive($amount));
    }

    /**
     * @param Unit $unit
     * @param int|float $amount
     * @return static
     */
    public function subtractUnit(Unit $unit, int|float $amount): static
    {
        if (is_float($amount)) {
            return match ($unit) {
                Unit::Second => $this->subtractSeconds($amount),
                default => throw new InvalidArgumentException('Only seconds can be fractional.', [
                    'unit' => $unit,
                    'amount' => $amount,
                ]),
            };
        }

        return match ($unit) {
            Unit::Year => $this->subtractYears($amount),
            Unit::Month => $this->subtractMonths($amount),
            Unit::Day => $this->subtractDays($amount),
            Unit::Hour => $this->subtractHours($amount),
            Unit::Minute => $this->subtractMinutes($amount),
            Unit::Second => $this->subtractSeconds($amount),
        };
    }

    /**
     * @param Unit $unit
     * @param int|float $amount
     * @param Unit $clamp
     * @return static
     */
    public function subtractUnitWithClamping(Unit $unit, int|float $amount, Unit $clamp): static
    {
        $start = $this->toStartOfUnit($clamp);
        $subtracted = $this->subtractUnit($unit, $amount);

        return ($subtracted < $start)
            ? $start
            : $subtracted;
    }

    /**
     * @param int $amount
     * @return static
     */
    public function subtractYears(int $amount): static
    {
        return $this->shift(years: -static::ensurePositive($amount));
    }

    /**
     * @param int $amount
     * @param bool $overflow
     * @return static
     */
    public function subtractMonths(int $amount, bool $overflow = false): static
    {
        $subtracted = $this->shift(months: -static::ensurePositive($amount));

        if (!$overflow) {
            if ($subtracted->getDay() === $this->getDay()) {
                return $subtracted;
            }

            $fix = $subtracted->set(days: 1)->subtractMonths(1);
            return $fix->set(days: $fix->getDaysInMonth());
        }

        return $subtracted;
    }

    /**
     * @param int $amount
     * @return static
     */
    public function subtractDays(int $amount): static
    {
        return $this->shift(days: -static::ensurePositive($amount));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function subtractHours(int $amount): static
    {
        return $this->shift(hours: -static::ensurePositive($amount));
    }

    /**
     * @param int $amount
     * @return static
     */
    public function subtractMinutes(int $amount): static
    {
        return $this->shift(minutes: -static::ensurePositive($amount));
    }

    /**
     * @param int|float $amount
     * @return static
     */
    public function subtractSeconds(int|float $amount): static
    {
        return $this->shift(seconds: -static::ensurePositive($amount));
    }

    /**
     * @param Unit $unit
     * @return static
     */
    public function toStartOfUnit(Unit $unit): static
    {
        return match ($unit) {
            Unit::Year => $this->toStartOfYear(),
            Unit::Month => $this->toStartOfMonth(),
            Unit::Day => $this->toStartOfDay(),
            Unit::Hour => $this->toStartOfHour(),
            Unit::Minute => $this->toStartOfMinute(),
            Unit::Second => $this->toStartOfSecond(),
        };
    }

    /**
     * @param Unit $unit
     * @return static
     */
    public function toEndOfUnit(Unit $unit): static
    {
        return match ($unit) {
            Unit::Year => $this->toEndOfYear(),
            Unit::Month => $this->toEndOfMonth(),
            Unit::Day => $this->toEndOfDay(),
            Unit::Hour => $this->toEndOfHour(),
            Unit::Minute => $this->toEndOfMinute(),
            Unit::Second => $this->toEndOfSecond(),
        };
    }

    /**
     * @return static
     */
    public function toStartOfYear(): static
    {
        return $this->set(months: 1, days: 1, hours: 0, minutes: 0, seconds: 0);
    }

    /**
     * @return static
     */
    public function toEndOfYear(): static
    {
        return $this->set(months: 12, days: $this->getDaysInMonth(), hours: 23, minutes: 59, seconds: 59.999999);
    }

    /**
     * @return static
     */
    public function toStartOfMonth(): static
    {
        return $this->set(days: 1, hours: 0, minutes: 0, seconds: 0);
    }

    /**
     * @return static
     */
    public function toEndOfMonth(): static
    {
        return $this->set(days: $this->getDaysInMonth(), hours: 23, minutes: 59, seconds: 59.999999);
    }

    /**
     * @return static
     */
    public function toStartOfDay(): static
    {
        return $this->setTime(0, 0);
    }

    /**
     * @return static
     */
    public function toEndOfDay(): static
    {
        return $this->setTime(23, 59, 59, 999999);
    }

    /**
     * @return static
     */
    public function toStartOfHour(): static
    {
        return $this->set(minutes: 0, seconds: 0);
    }

    /**
     * @return static
     */
    public function toEndOfHour(): static
    {
        return $this->set(minutes: 59, seconds: 59.999999);
    }

    /**
     * @return static
     */
    public function toStartOfMinute(): static
    {
        return $this->set(seconds: 0);
    }

    /**
     * @return static
     */
    public function toEndOfMinute(): static
    {
        return $this->set(seconds: 59.999999);
    }

    /**
     * @return static
     */
    public function toStartOfSecond(): static
    {
        return $this->set(seconds: (int) $this->getSeconds());
    }

    /**
     * @return static
     */
    public function toEndOfSecond(): static
    {
        return $this->set(seconds: ((int) $this->getSeconds()) + 0.999999);
    }

    /**
     * @param string $zone
     * @return static
     */
    public function toTimeZone(string $zone): static
    {
        return $this->setTimezone(new DateTimeZone($zone));
    }

    public function toLocal(): static
    {
        return $this->toTimeZone(date_default_timezone_get());
    }

    /**
     * @return static
     */
    public function toUtc(): static
    {
        return $this->toTimeZone('UTC');
    }

    /**
     * @param DateTimeInterface|null $lower
     * @param DateTimeInterface|null $upper
     * @return static
     */
    public function clamp(?DateTimeInterface $lower = null, ?DateTimeInterface $upper = null): static
    {
        if ($lower === null && $upper === null) {
            throw new InvalidArgumentException('At least one of $lower or $upper must be specified.', [
                'this' => $this,
            ]);
        }

        if ($lower !== null && $this < $lower) {
            return static::createFromInterface($lower);
        }

        if ($upper !== null && $this > $upper) {
            return static::createFromInterface($upper);
        }

        return clone $this;
    }

    # endregion Mutation -----------------------------------------------------------------------------------------------

    # region Comparison ------------------------------------------------------------------------------------------------

    /**
     * @param DateTimeInterface $min
     * @param DateTimeInterface $max
     * @return bool
     */
    public function between(DateTimeInterface $min, DateTimeInterface $max): bool
    {
        return $min <= $this && $this <= $max;
    }

    /**
     * @param DateTimeInterface|null $context
     * @return bool
     */
    public function isPast(?DateTimeInterface $context = null): bool
    {
        return $this < ($context ?? static::now());
    }

    /**
     * @param DateTimeInterface|null $context
     * @return bool
     */
    public function isFuture(?DateTimeInterface $context = null): bool
    {
        return $this > ($context ?? static::now());
    }

    # endregion Comparison ---------------------------------------------------------------------------------------------

    # region Conversion ------------------------------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    /**
     * @return DateTimeImmutable
     */
    public function toBase(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromInterface($this);
    }

    /**
     * @return int
     */
    public function toInt(): int
    {
        return $this->getTimestamp();
    }

    /**
     * @return float
     */
    public function toFloat(): float
    {
        return (float) $this->format('U.u');
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        return $this->format(self::RFC3339_HUMAN);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    # endregion Conversion ---------------------------------------------------------------------------------------------

    # region Calendar --------------------------------------------------------------------------------------------------

    /**
     * @return int
     */
    public function getYear(): int
    {
        return (int) $this->format('Y');
    }

    /**
     * @return int<1,12>
     */
    public function getMonth(): int
    {
        return (int) $this->format('n');
    }

    /**
     * @return int<1,31>
     */
    public function getDay(): int
    {
        return (int) $this->format('j');
    }

    /**
     * @return int<0,23>
     */
    public function getHours(): int
    {
        return (int) $this->format('G');
    }

    /**
     * Returns minutes (0-59)
     *
     * @return int
     */
    public function getMinutes(): int
    {
        return (int) $this->format('i');
    }

    /**
     * @return float
     */
    public function getSeconds(): float
    {
        return (float) $this->format('s.u');
    }

    /**
     * @return int
     */
    public function getDaysInMonth(): int
    {
        return (int) $this->format('t');
    }

    /**
     * @return DayOfWeek
     */
    public function getDayOfWeek(): DayOfWeek
    {
        return match ((int) $this->format('N')) {
            1 => DayOfWeek::Monday,
            2 => DayOfWeek::Tuesday,
            3 => DayOfWeek::Wednesday,
            4 => DayOfWeek::Thursday,
            5 => DayOfWeek::Friday,
            6 => DayOfWeek::Saturday,
            7 => DayOfWeek::Sunday,
        };
    }

    /**
     * Returns the day of the year (1-366)
     *
     * @return int
     */
    public function getDayOfYear(): int
    {
        return 1 + (int) $this->format('z');
    }

    /**
     * @return bool
     */
    public function isMonday(): bool
    {
        return $this->getDayOfWeek() === DayOfWeek::Monday;
    }

    /**
     * @return bool
     */
    public function isTuesday(): bool
    {
        return $this->getDayOfWeek() === DayOfWeek::Tuesday;
    }

    /**
     * @return bool
     */
    public function isWednesday(): bool
    {
        return $this->getDayOfWeek() === DayOfWeek::Wednesday;
    }

    /**
     * @return bool
     */
    public function isThursday(): bool
    {
        return $this->getDayOfWeek() === DayOfWeek::Thursday;
    }

    /**
     * @return bool
     */
    public function isFriday(): bool
    {
        return $this->getDayOfWeek() === DayOfWeek::Friday;
    }

    /**
     * @return bool
     */
    public function isSaturday(): bool
    {
        return $this->getDayOfWeek() === DayOfWeek::Saturday;
    }

    /**
     * @return bool
     */
    public function isSunday(): bool
    {
        return $this->getDayOfWeek() === DayOfWeek::Sunday;
    }

    /**
     * @return bool
     */
    public function isWeekday(): bool
    {
        return !$this->isWeekend();
    }

    /**
     * @return bool
     */
    public function isWeekend(): bool
    {
        return in_array($this->getDayOfWeek(), [DayOfWeek::Saturday, DayOfWeek::Sunday], true);
    }

    /**
     * @return bool
     */
    public function isLeapYear(): bool
    {
        return (bool) $this->format('L');
    }

    #endregion Calendar ------------------------------------------------------------------------------------------------

    # region Zone ------------------------------------------------------------------------------------------------------

    /**
     * @return bool
     */
    public function isUtc(): bool
    {
        return $this->getOffset() === 0;
    }

    /**
     * @return bool
     */
    public function isDst(): bool
    {
        return (bool) $this->format('I');
    }

    # endregion Zone ---------------------------------------------------------------------------------------------------

    /**
     * @template TNum of int|float
     * @param TNum $amount
     * @return TNum
     */
    protected static function ensurePositive(int|float $amount): int|float
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('$amount must be positive.', [
                'amount' => $amount,
            ]);
        }
        return $amount;
    }
}
