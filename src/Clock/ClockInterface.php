<?php declare(strict_types=1);

namespace Kirameki\Time\Clock;

use DateTimeZone;
use Kirameki\Time\Time;
use Psr\Clock\ClockInterface as PsrClockInterface;

interface ClockInterface extends PsrClockInterface
{
    public function now(): Time;

    public function getTimezone(): DateTimeZone;
}
