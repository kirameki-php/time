<?php declare(strict_types=1);

namespace Kirameki\Time;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonSerializable;
use Stringable;
use function is_float;
use function is_int;
use function is_null;
use function microtime;

/**
 * @phpstan-consistent-constructor
 */
class Instant extends DateTimeImmutable implements JsonSerializable, Stringable
{
    use Helpers;

    public const string MIN = '0001-01-01 00:00:00.000000+00:00';
    public const string MAX = '9999-12-31 23:59:59.999999+00:00';

    /**
     * @param int|float|null $time
     */
    public function __construct(int|float|DateTimeInterface|null $time = null)
    {
        parent::__construct(match (true) {
            is_null($time) => '@' . microtime(true),
            is_int($time) || is_float($time) => '@' . $time,
            $time instanceof DateTimeInterface => '@' . $time->format('U.u'),
        });
    }

    /**
     * @return static
     */
    public static function min(): static
    {
        return new static(new DateTimeImmutable(self::MIN));
    }

    /**
     * @return static
     */
    public static function max(): static
    {
        return new static(new DateTimeImmutable(self::MAX));
    }

    /**
     * @inheritDoc
     */
    public static function createFromFormat(string $format, string $datetime, ?DateTimeZone $timezone = null): static
    {
        $instance = static::createFromFormatCompat($format, $datetime, $timezone);

        return $instance->format('p') !== 'Z'
            ? new static((float) $instance->format('U.u'))
            : $instance;
    }
}
