<?php declare(strict_types=1);

namespace Kirameki\Time;

use DateTimeZone;
use Psr\Clock\ClockInterface;

class Clock implements ClockInterface
{
    public function __construct(
        protected ?DateTimeZone $timezone = null,
        protected ?Time $fixed = null,
    )
    {
    }

    /**
     * @return Time
     */
    public function now(): Time
    {
        return $this->fixed ?? new Time(null, $this->timezone);
    }

    /**
     * @return DateTimeZone|null
     */
    public function getTimezone(): ?DateTimeZone
    {
        return $this->timezone;
    }

    /**
     * @return bool
     */
    public function isFixed(): bool
    {
        return $this->fixed !== null;
    }
}
