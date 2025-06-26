<?php declare(strict_types=1);

namespace Kirameki\Time;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;
use Kirameki\Core\Exceptions\InvalidArgumentException;
use Stringable;
use function assert;
use function date_default_timezone_get;
use function in_array;

/**
 * @phpstan-consistent-constructor
 */
class Time extends DateTimeImmutable implements JsonSerializable, Stringable
{
    use Helpers;

    public const string RFC3339_HUMAN = 'Y-m-d H:i:s.up';
    public const string MIN = '0001-01-01 00:00:00.000000';
    public const string MAX = '9999-12-31 23:59:59.999999';

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
     * @param int|float $timestamp
     * @return static
     */
    public static function createFromTimestamp(int|float $timestamp): static
    {
        return parent::createFromTimestamp($timestamp)->toLocal();
    }

    /**
     * @inheritDoc
     */
    public static function createFromFormat(string $format, string $datetime, ?DateTimeZone $timezone = null): static
    {
        return static::createFromFormatCompat($format, $datetime, $timezone);
    }

    /**
     * @return static
     */
    public static function today(): static
    {
        return new static('today');
    }

    /**
     * @return static
     */
    public static function yesterday(): static
    {
        return new static('yesterday');
    }

    /**
     * @return static
     */
    public static function tomorrow(): static
    {
        return new static('tomorrow');
    }

    /**
     * @return static
     */
    public static function min(): static
    {
        return new static(self::MIN);
    }

    /**
     * @return static
     */
    public static function max(): static
    {
        return new static(self::MAX);
    }

    /**
     * @return DateTimeZone
     */
    protected static function getCurrentTimeZone(): DateTimeZone
    {
        return new DateTimeZone(date_default_timezone_get());
    }

    # endregion Creation -----------------------------------------------------------------------------------------------

    # region Mutation --------------------------------------------------------------------------------------------------

    /**
     * @return static
     */
    public function toLocal(): static
    {
        return $this->setTimezone(static::getCurrentTimeZone());
    }

    /**
     * @return static
     */
    public function toUtc(): static
    {
        return $this->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * @return Instant
     */
    public function toInstant(): Instant
    {
        return new Instant($this);
    }

    # endregion Mutation -----------------------------------------------------------------------------------------------

    # region Calendar --------------------------------------------------------------------------------------------------

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
