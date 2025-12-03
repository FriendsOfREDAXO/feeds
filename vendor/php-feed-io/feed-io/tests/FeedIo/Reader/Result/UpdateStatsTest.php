<?php

declare(strict_types=1);
/*
 * This file is part of the feed-io package.
 *
 * (c) Alexandre Debril <alex.debril@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FeedIo\Reader\Result;

use FeedIo\Feed;
use PHPUnit\Framework\TestCase;

class UpdateStatsTest extends TestCase
{
    public function testIntervals()
    {
        $feed = new Feed();
        $feed->setLastModified(new \DateTime('-1 day'));
        foreach ($this->getDates() as $date) {
            $item = new Feed\Item();
            $item->setLastModified(new \DateTime($date));
            $feed->add($item);
        }

        $stats = new UpdateStats($feed);
        $intervals = $stats->getIntervals();

        $this->assertCount(4, $intervals);

        $this->assertEquals(86400, $stats->getMinInterval());
        $nextUpdate = $stats->computeNextUpdate();
        $averageInterval = $stats->getAverageInterval();
        $medianInterval = $stats->getMedianInterval();
        $computedInterval = ($medianInterval < $averageInterval ? $medianInterval : $averageInterval);
        $this->assertEquals($stats->getNewestItemDate() + intval($computedInterval + 0.1 * $computedInterval), $nextUpdate->getTimestamp());
    }

    public function testSleepyFeed()
    {
        $feed = new Feed();
        $feed->setLastModified(new \DateTime('-10 days'));
        foreach (['-10 days', '-12 days'] as $date) {
            $item = new Feed\Item();
            $item->setLastModified(new \DateTime($date));
            $feed->add($item);
        }

        $stats = new UpdateStats($feed);
        $intervals = $stats->getIntervals();

        $this->assertCount(1, $intervals);

        $this->assertTrue($stats->isSleepy(
            UpdateStats::DEFAULT_DURATION_BEFORE_BEING_SLEEPY,
            UpdateStats::DEFAULT_MARGIN_RATIO
        ));
        $nextUpdate = $stats->computeNextUpdate();

        $this->assertEquals(time() + 86400, $nextUpdate->getTimestamp());
    }


    /**
     * Test getAverageInterval with a single interval
     * Edge case: count = 1
     */
    public function testAverageIntervalWithSingleInterval()
    {
        $feed = new Feed();
        $feed->setLastModified(new \DateTime('-1 day'));
        
        // Two items = 1 interval
        $item1 = new Feed\Item();
        $item1->setLastModified(new \DateTime('-1 day'));
        $feed->add($item1);
        
        $item2 = new Feed\Item();
        $item2->setLastModified(new \DateTime('-2 days'));
        $feed->add($item2);

        $stats = new UpdateStats($feed);
        $intervals = $stats->getIntervals();
        
        $this->assertCount(1, $intervals);
        $this->assertIsInt($stats->getAverageInterval());
        $this->assertGreaterThanOrEqual(0, $stats->getAverageInterval());
    }

    /**
     * Test getAverageInterval with two intervals
     * Edge case: count = 2
     */
    public function testAverageIntervalWithTwoIntervals()
    {
        $feed = new Feed();
        $feed->setLastModified(new \DateTime('-1 day'));
        
        // Three items = 2 intervals
        $item1 = new Feed\Item();
        $item1->setLastModified(new \DateTime('-1 day'));
        $feed->add($item1);
        
        $item2 = new Feed\Item();
        $item2->setLastModified(new \DateTime('-2 days'));
        $feed->add($item2);
        
        $item3 = new Feed\Item();
        $item3->setLastModified(new \DateTime('-3 days'));
        $feed->add($item3);

        $stats = new UpdateStats($feed);
        $intervals = $stats->getIntervals();
        
        $this->assertCount(2, $intervals);
        $this->assertIsInt($stats->getAverageInterval());
        $this->assertGreaterThanOrEqual(0, $stats->getAverageInterval());
    }

    /**
     * Test getAverageInterval with three intervals
     * Edge case: count = 3
     */
    public function testAverageIntervalWithThreeIntervals()
    {
        $feed = new Feed();
        $feed->setLastModified(new \DateTime('-1 day'));
        
        // Four items = 3 intervals
        $item1 = new Feed\Item();
        $item1->setLastModified(new \DateTime('-1 day'));
        $feed->add($item1);
        
        $item2 = new Feed\Item();
        $item2->setLastModified(new \DateTime('-2 days'));
        $feed->add($item2);
        
        $item3 = new Feed\Item();
        $item3->setLastModified(new \DateTime('-3 days'));
        $feed->add($item3);
        
        $item4 = new Feed\Item();
        $item4->setLastModified(new \DateTime('-4 days'));
        $feed->add($item4);

        $stats = new UpdateStats($feed);
        $intervals = $stats->getIntervals();
        
        $this->assertCount(3, $intervals);
        $this->assertIsInt($stats->getAverageInterval());
        $this->assertGreaterThanOrEqual(0, $stats->getAverageInterval());
    }

    /**
     * Test getAverageInterval with four intervals
     * Edge case: count = 4 (minimum for proper quartile calculation)
     */
    public function testAverageIntervalWithFourIntervals()
    {
        $feed = new Feed();
        $feed->setLastModified(new \DateTime('-1 day'));
        
        // Five items = 4 intervals
        $item1 = new Feed\Item();
        $item1->setLastModified(new \DateTime('-1 day'));
        $feed->add($item1);
        
        $item2 = new Feed\Item();
        $item2->setLastModified(new \DateTime('-2 days'));
        $feed->add($item2);
        
        $item3 = new Feed\Item();
        $item3->setLastModified(new \DateTime('-3 days'));
        $feed->add($item3);
        
        $item4 = new Feed\Item();
        $item4->setLastModified(new \DateTime('-4 days'));
        $feed->add($item4);
        
        $item5 = new Feed\Item();
        $item5->setLastModified(new \DateTime('-5 days'));
        $feed->add($item5);

        $stats = new UpdateStats($feed);
        $intervals = $stats->getIntervals();
        
        $this->assertCount(4, $intervals);
        $this->assertIsInt($stats->getAverageInterval());
        $this->assertGreaterThanOrEqual(0, $stats->getAverageInterval());
    }

    /**
     * Test getAverageInterval with empty intervals
     * Edge case: count = 0
     */
    public function testAverageIntervalWithNoIntervals()
    {
        $feed = new Feed();
        $feed->setLastModified(new \DateTime('-1 day'));
        
        // Single item = 0 intervals
        $item = new Feed\Item();
        $item->setLastModified(new \DateTime('-1 day'));
        $feed->add($item);

        $stats = new UpdateStats($feed);
        $intervals = $stats->getIntervals();
        
        $this->assertCount(0, $intervals);
        $this->assertEquals(0, $stats->getAverageInterval());
    }

    /**
     * Test getAverageInterval with outliers that should be filtered
     */
    public function testAverageIntervalWithOutliers()
    {
        $feed = new Feed();
        $feed->setLastModified(new \DateTime('-1 day'));
        
        // Create items with one extreme outlier
        $dates = [
            '-1 day',
            '-2 days',
            '-3 days',
            '-4 days',
            '-5 days',
            '-100 days', // Outlier
        ];
        
        foreach ($dates as $date) {
            $item = new Feed\Item();
            $item->setLastModified(new \DateTime($date));
            $feed->add($item);
        }

        $stats = new UpdateStats($feed);
        $intervals = $stats->getIntervals();
        
        $this->assertCount(5, $intervals);
        
        // The average should be reasonable and not heavily skewed by the outlier
        $average = $stats->getAverageInterval();
        $this->assertIsInt($average);
        $this->assertGreaterThanOrEqual(0, $average);
        
        // Verify the outlier filtering works by checking that average is close to 86400 (1 day)
        // and not close to the raw average which would be much higher
        $rawAverage = array_sum($intervals) / count($intervals);
        $this->assertLessThan($rawAverage, $average);
    }

    /**
     * Test that Q1 and Q3 indices don't go out of bounds
     */
    public function testQuartileIndicesWithinBounds()
    {
        // Test with various array sizes
        $testCases = [1, 2, 3, 4, 5, 10, 100];
        
        foreach ($testCases as $itemCount) {
            $feed = new Feed();
            $feed->setLastModified(new \DateTime('-1 day'));
            
            // Add items
            for ($i = 0; $i < $itemCount + 1; $i++) {
                $item = new Feed\Item();
                $item->setLastModified(new \DateTime("-{$i} days"));
                $feed->add($item);
            }

            $stats = new UpdateStats($feed);
            
            // This should not throw any errors
            $average = $stats->getAverageInterval();
            $this->assertIsInt($average);
            $this->assertGreaterThanOrEqual(0, $average);
        }
    }

    /**
     * Test getAverageInterval with uniform intervals
     */
    public function testAverageIntervalWithUniformIntervals()
    {
        $feed = new Feed();
        $feed->setLastModified(new \DateTime('-1 hour'));
        
        // Create items with exactly 1 hour intervals
        for ($i = 1; $i <= 10; $i++) {
            $item = new Feed\Item();
            $item->setLastModified(new \DateTime("-{$i} hours"));
            $feed->add($item);
        }

        $stats = new UpdateStats($feed);
        $intervals = $stats->getIntervals();
        
        $this->assertCount(9, $intervals);
        
        // All intervals should be 3600 seconds (1 hour)
        foreach ($intervals as $interval) {
            $this->assertEquals(3600, $interval);
        }
        
        // Average should be 3600
        $this->assertEquals(3600, $stats->getAverageInterval());
    }

    /**
     * Test that the implementation handles edge cases safely
     */
    public function testBothImplementationsAreSafe()
    {
        for ($itemCount = 1; $itemCount <= 20; $itemCount++) {
            $feed = new Feed();
            $feed->setLastModified(new \DateTime('-1 day'));
            
            // Add items with varied intervals
            for ($i = 0; $i <= $itemCount; $i++) {
                $item = new Feed\Item();
                $item->setLastModified(new \DateTime("-" . ($i * 2) . " hours"));
                $feed->add($item);
            }

            $stats = new UpdateStats($feed);
            
            // This should never throw an error
            try {
                $average = $stats->getAverageInterval();
                $this->assertIsInt($average);
                $this->assertGreaterThanOrEqual(0, $average);
            } catch (\Throwable $e) {
                $this->fail("getAverageInterval() threw an exception with {$itemCount} intervals: " . $e->getMessage());
            }
        }
    }

    /**
     * Test that average is calculated using filtered count, not original count
     * This ensures the average is based only on non-outlier values
     */
    public function testAverageIntervalUsesFilteredCount()
    {
        $feed = new Feed();
        $feed->setLastModified(new \DateTime('-1 day'));
        
        // Create a dataset with clear outliers
        // Regular intervals: ~1 day (86400 seconds)
        // Outlier: ~100 days (8640000 seconds)
        $dates = [
            '-1 day',    // Most recent
            '-2 days',   // 1 day interval
            '-3 days',   // 1 day interval
            '-4 days',   // 1 day interval
            '-5 days',   // 1 day interval
            '-105 days', // 100 day interval (OUTLIER - should be filtered out)
        ];
        
        foreach ($dates as $date) {
            $item = new Feed\Item();
            $item->setLastModified(new \DateTime($date));
            $feed->add($item);
        }

        $stats = new UpdateStats($feed);
        $intervals = $stats->getIntervals();
        
        // We should have 5 intervals total
        $this->assertCount(5, $intervals);
        
        // Get the average
        $average = $stats->getAverageInterval();
        
        // The average should be close to 86400 (1 day)
        // If it was incorrectly dividing by the original count (5),
        // it would be much lower because it would include the large outlier
        // in the sum but divide by all 5 values.
        
        // With outlier filtering:
        // - Filtered values: [86400, 86400, 86400, 86400] (4 values)
        // - Sum: 345600
        // - Average: 345600 / 4 = 86400
        
        // Without correct filtering (old bug):
        // - Sum of filtered: 345600
        // - Divided by original count: 345600 / 5 = 69120 (WRONG!)
        
        $this->assertEquals(86400, $average, 'Average should be 86400 (1 day) when calculated with filtered count');
        
        // Verify it's not the incorrect value
        $this->assertNotEquals(69120, $average, 'Average should not use original count as divisor');
    }

    /**
     * Test average calculation with all values filtered out
     * Edge case: what if IQR filtering removes everything?
     */
    public function testAverageIntervalWhenAllValuesFiltered()
    {
        $feed = new Feed();
        $feed->setLastModified(new \DateTime('-1 day'));
        
        // Create items that might all be considered outliers
        // This is a pathological case but we should handle it gracefully
        $dates = [
            '-1 day',
            '-2 days',
        ];
        
        foreach ($dates as $date) {
            $item = new Feed\Item();
            $item->setLastModified(new \DateTime($date));
            $feed->add($item);
        }

        $stats = new UpdateStats($feed);
        
        // Should return 0 if no values pass the filter, not crash
        $average = $stats->getAverageInterval();
        $this->assertIsInt($average);
        $this->assertGreaterThanOrEqual(0, $average);
    }

    /**
     * Test that getAverageInterval and getMedianInterval don't mutate $this->intervals
     * This ensures calling these methods doesn't have side effects on other methods
     */
    public function testIntervalMethodsDoNotMutateState()
    {
        $feed = new Feed();
        $feed->setLastModified(new \DateTime('-1 day'));
        
        $dates = [
            '-5 days',
            '-1 day',
            '-3 days',
            '-2 days',
            '-4 days',
        ];
        
        foreach ($dates as $date) {
            $item = new Feed\Item();
            $item->setLastModified(new \DateTime($date));
            $feed->add($item);
        }

        $stats = new UpdateStats($feed);
        
        // Get the original intervals (should be in chronological order based on how they're computed)
        $originalIntervals = $stats->getIntervals();
        
        // Call methods that need to sort the intervals
        $average = $stats->getAverageInterval();
        $median = $stats->getMedianInterval();
        
        // Get intervals again - they should still be in the original order
        $intervalsAfter = $stats->getIntervals();
        
        $this->assertEquals($originalIntervals, $intervalsAfter, 
            'Calling getAverageInterval() and getMedianInterval() should not mutate the intervals array');
        
        // Call again to verify no mutation on subsequent calls
        $average2 = $stats->getAverageInterval();
        $median2 = $stats->getMedianInterval();
        $intervalsAfter2 = $stats->getIntervals();
        
        $this->assertEquals($originalIntervals, $intervalsAfter2,
            'Multiple calls to statistical methods should not mutate the intervals array');
        
        // Results should be consistent
        $this->assertEquals($average, $average2);
        $this->assertEquals($median, $median2);
    }

    private function getDates(): array
    {
        return [
            '-1 day',
            '-3 days',
            '-10 days',
            '-20 days',
            '-21 days',
        ];
    }
}
