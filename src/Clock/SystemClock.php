<?php declare(strict_types=1);

namespace Kirameki\Time\Clock;

use DateTimeZone;
use Kirameki\Time\Time;
use function date_default_timezone_get;

class SystemClock implements ClockInterface
{
    /**
     * @var DateTimeZone
     */
    protected DateTimeZone $timezone;

    /**
     * @param DateTimeZone|null $timezone
     */
    public function __construct(
        ?DateTimeZone $timezone = null,
    )
    {
        $this->timezone = $timezone ?? new DateTimeZone(date_default_timezone_get());
    }

    /**
     * @return Time
     */
    public function now(): Time
    {
        return new Time()->setTimezone($this->timezone);
    }

    /**
     * @return DateTimeZone
     */
    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }
}
