<?php declare(strict_types=1);

namespace Kirameki\Time\Clock;

use DateTimeZone;
use Kirameki\Time\Time;
use Psr\Clock\ClockInterface as PsrClockInterface;

interface ClockInterface extends PsrClockInterface
{
    /**
     * Return type changed to Time from DateTimeImmutable
     *
     * @return Time
     */
    public function now(): Time;

    /**
     * @return DateTimeZone
     */
    public function getTimezone(): DateTimeZone;
}
