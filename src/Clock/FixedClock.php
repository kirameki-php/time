<?php declare(strict_types=1);

namespace Kirameki\Time\Clock;

use DateTimeZone;
use Kirameki\Time\Time;

class FixedClock implements ClockInterface
{
    /**
     * @param Time $fixed
     */
    public function __construct(
        protected Time $fixed,
    )
    {
    }

    /**
     * @return Time
     */
    public function now(): Time
    {
        return $this->fixed;
    }

    /**
     * @return DateTimeZone
     */
    public function getTimezone(): DateTimeZone
    {
        return $this->fixed->getTimezone();
    }
}
