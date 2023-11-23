<?php declare(strict_types=1);

namespace Kirameki\Time;

use DateTimeZone;
use Psr\Clock\ClockInterface;

class Clock implements ClockInterface
{
    protected DateTimeZone $timezone;

    public function __construct(
        ?DateTimeZone $timezone = null,
        protected ?Time $fixed = null,
    )
    {
        $this->timezone = $timezone ?? new DateTimeZone(date_default_timezone_get());
    }

    /**
     * @return Time
     */
    public function now(): Time
    {
        return $this->fixed ?? (new Time())->setTimezone($this->timezone);
    }

    /**
     * @return DateTimeZone
     */
    public function getTimezone(): DateTimeZone
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
