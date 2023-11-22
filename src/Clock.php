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

    public function now(): Time
    {
        return $this->fixed ?? new Time(null, $this->timezone);
    }
}
