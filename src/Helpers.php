<?php declare(strict_types=1);

namespace Kirameki\Time;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Exceptions\NotSupportedException;
use Kirameki\Time\Exceptions\InvalidFormatException;
use function assert;
use function explode;
use function floor;
use function implode;
use function is_float;
use function str_pad;
use const STR_PAD_LEFT;

trait Helpers
{
    /**
     * @return static
     */
    public static function now(): static
    {
        return new static();
    }

    /**
     * @inheritDoc
     */
    public static function createFromInterface(DateTimeInterface $object): DateTimeImmutable
    {
        throw new NotSupportedException(static::class . '::createFromInterface() is not supported.', [
            'object' => $object,
        ]);
    }

    /**
     * @param string $format
     * @param string $datetime
     * @param DateTimeZone|null $timezone
     * @return static
     * @throws InvalidFormatException
     */
    public static function createFromFormatCompat(string $format, string $datetime, ?DateTimeZone $timezone = null): static
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

    # region Getters ---------------------------------------------------------------------------------------------------

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

    # endregion Getters ------------------------------------------------------------------------------------------------

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
        return $this->setTime($this->getHours(), 0);
    }

    /**
     * @return static
     */
    public function toEndOfHour(): static
    {
        return $this->setTime($this->getHours(), 59, 59, 999999);
    }

    /**
     * @return static
     */
    public function toStartOfMinute(): static
    {
        return $this->setTime($this->getHours(), $this->getMinutes());
    }

    /**
     * @return static
     */
    public function toEndOfMinute(): static
    {
        return $this->setTime($this->getHours(), $this->getMinutes(), 59, 999999);
    }

    /**
     * @return static
     */
    public function toStartOfSecond(): static
    {
        return $this->setTime($this->getHours(), $this->getMinutes(), (int) $this->getSeconds());
    }

    /**
     * @return static
     */
    public function toEndOfSecond(): static
    {
        return $this->setTime($this->getHours(), $this->getMinutes(), (int) $this->getSeconds(), 999999);
    }

    /**
     * @param static|null $lower
     * @param static|null $upper
     * @return static
     */
    public function clamp(?self $lower = null, ?self $upper = null): static
    {
        if ($lower === null && $upper === null) {
            throw new InvalidArgumentException('At least one of $lower or $upper must be specified.', [
                'this' => $this,
            ]);
        }

        if ($lower !== null && $this < $lower) {
            return $lower;
        }

        if ($upper !== null && $this > $upper) {
            return $upper;
        }

        return clone $this;
    }

    # endregion Mutation -----------------------------------------------------------------------------------------------

    # region Comparison ------------------------------------------------------------------------------------------------

    /**
     * @param static $min
     * @param static $max
     * @return bool
     */
    public function between(self $min, self $max): bool
    {
        return $min <= $this && $this <= $max;
    }

    /**
     * @param static|null $context
     * @return bool
     */
    public function isPast(?self $context = null): bool
    {
        return $this < ($context ?? static::now());
    }

    /**
     * @param static|null $context
     * @return bool
     */
    public function isFuture(?self $context = null): bool
    {
        return $this > ($context ?? static::now());
    }

    # endregion Comparison ---------------------------------------------------------------------------------------------

    # region Calendar --------------------------------------------------------------------------------------------------

    /**
     * @return int
     */
    public function getDaysInMonth(): int
    {
        return (int) $this->format('t');
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
    public function isLeapYear(): bool
    {
        return (bool) $this->format('L');
    }

    # endregion Calendar -----------------------------------------------------------------------------------------------

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
    public function toDateTimeImmutable(): DateTimeImmutable
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
        return $this->format(Time::RFC3339_HUMAN);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    # endregion Conversion ---------------------------------------------------------------------------------------------

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
