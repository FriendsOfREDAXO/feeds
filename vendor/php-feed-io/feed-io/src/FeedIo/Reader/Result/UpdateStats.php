<?php

declare(strict_types=1);

namespace FeedIo\Reader\Result;

use FeedIo\Feed\ItemInterface;
use FeedIo\FeedInterface;

class UpdateStats
{
    /**
     * default update delay applied when average or median intervals are outdated
     */
    public const DEFAULT_MIN_DELAY = 3600;

    /**
     * default update delay applied when the feed is considered sleepy
     */
    public const DEFAULT_SLEEPY_DELAY = 86400;

    /**
     * default duration after which the feed is considered sleepy
     */
    public const DEFAULT_DURATION_BEFORE_BEING_SLEEPY = 7 * 86400;

    /**
     * default margin ratio applied to update time calculation
     */
    public const DEFAULT_MARGIN_RATIO = 0.1;

    protected array $intervals = [];

    protected int $newestItemDate = 0;

    /**
     * UpdateStats constructor.
     * @param FeedInterface $feed
     */
    public function __construct(
        protected FeedInterface $feed
    ) {
        $dates = $this->extractDates($feed);
        if (count($dates) > 0) {
            // get the most recent item date that is not in the future
            $this->newestItemDate = min(max($dates), time());
        } else {
            $this->newestItemDate = $this->getFeedTimestamp();
        }
        $this->intervals = $this->computeIntervals($dates);
    }

    /**
     * @param int $minDelay
     * @param int $sleepyDelay
     * @param int $sleepyDuration
     * @param float $marginRatio
     * @return \DateTime
     */
    public function computeNextUpdate(
        int $minDelay = self::DEFAULT_MIN_DELAY,
        int $sleepyDelay = self::DEFAULT_SLEEPY_DELAY,
        int $sleepyDuration = self::DEFAULT_DURATION_BEFORE_BEING_SLEEPY,
        float $marginRatio = self::DEFAULT_MARGIN_RATIO
    ): \DateTime {
        if ($this->isSleepy($sleepyDuration, $marginRatio)) {
            return (new \DateTime())->setTimestamp(time() + $sleepyDelay);
        }
        $now = time();
        $intervals = [
            $this->getAverageInterval(),
            $this->getMedianInterval(),
        ];
        sort($intervals);
        $newTimestamp = $now + $minDelay;
        foreach ($intervals as $interval) {
            $computedTimestamp = $this->addInterval($this->newestItemDate, $interval, $marginRatio);
            if ($computedTimestamp > $now) {
                $newTimestamp = $computedTimestamp;
                break;
            }
        }
        return (new \DateTime())->setTimestamp($newTimestamp);
    }

    /**
     * @param int $sleepyDuration
     * @param float $marginRatio
     * @return bool
     */
    public function isSleepy(int $sleepyDuration, float $marginRatio): bool
    {
        return time() > $this->addInterval($this->newestItemDate, $sleepyDuration, $marginRatio);
    }

    /**
     * @param int $ts
     * @param int $interval
     * @param float $marginRatio
     * @return int
     */
    public function addInterval(int $ts, int $interval, float $marginRatio): int
    {
        return $ts + intval($interval + $marginRatio * $interval);
    }

    /**
     * @return array
     */
    public function getIntervals(): array
    {
        return $this->intervals;
    }

    /**
     * @return int
     */
    public function getMinInterval(): int
    {
        return count($this->intervals) ? min($this->intervals) : 0;
    }

    /**
     * @return int
     */
    public function getMaxInterval(): int
    {
        return count($this->intervals) ? max($this->intervals) : 0;
    }

    /**
     * @return int
     */
    public function getAverageInterval(): int
    {
        $intervals = $this->intervals;
        sort($intervals);

        $count = count($intervals);
        if ($count === 0) {
            return 0;
        }

        // some feeds could have very old historic
        // articles so eliminate them using statistical methods
        $q1 = $intervals[(int) floor($count * 0.25)];
        $q3 = $intervals[(int) floor($count * 0.75)];
        $iqr = $q3 - $q1;

        $lower_bound = $q1 - 1.5 * $iqr;
        $upper_bound = $q3 + 1.5 * $iqr;

        $result = array_filter($intervals, function(int $value) use ($lower_bound, $upper_bound) {
            return $value >= $lower_bound && $value <= $upper_bound;
        });

        $total = array_sum($result);

        return count($result) ? intval(floor($total / count($result))) : 0;
    }

    /**
     * @return int
     */
    public function getMedianInterval(): int
    {
        $intervals = $this->intervals;
        sort($intervals);

        $count = count($intervals);
        if ($count === 0) {
            return 0;
        }

        $num = (int) floor($count / 2);

        if ($count % 2 === 0) {
            return intval(floor(($intervals[$num - 1] + $intervals[$num]) / 2));
        } else {
            return $intervals[$num];
        }
    }

    /**
     * Returns the timestamp of the newest item in the feed.
     * The value is capped at the current time to prevent future dates from being used.
     * If the feed has no items with valid timestamps, returns the feed's last modified timestamp.
     * 
     * @return int Unix timestamp of the newest item (in seconds since epoch)
     */
    public function getNewestItemDate(): int
    {
        return $this->newestItemDate;
    }

    private function computeIntervals(array $dates): array
    {
        rsort($dates);
        $intervals = [];
        $current = 0;
        foreach ($dates as $date) {
            if ($current > 0) {
                $intervals[] = $current - $date;
            }
            $current = $date;
        }
        return $intervals;
    }

    private function extractDates(FeedInterface $feed): array
    {
        $dates = [];
        foreach ($feed as $item) {
            $dates[] = $this->getTimestamp($item) ?? $this->getFeedTimestamp();
        }
        return $dates;
    }

    private function getTimestamp(ItemInterface $item): ?int
    {
        if (! is_null($item->getLastModified())) {
            return $item->getLastModified()->getTimestamp();
        }
        return null;
    }

    private function getFeedTimestamp(): int
    {
        return !! $this->feed->getLastModified() ? $this->feed->getLastModified()->getTimestamp() : time();
    }
}
