<?php

namespace Bottleneck;

class ServerStats
{
    /**
     * Gets the disk usage percentage for the app's directory partition.
     * 
     * @return integer
     */
    function getDiskUsagePercentage()
    {
        $output = shell_exec('df -P .');
        preg_match('/(\d?\d)%/', $output, $matches);
        return (int) $matches[1];
    }

    /**
     * Reads memory usage (total and used).
     * 
     * @return array
     */
    function getMemoryUsageMb()
    {
        $output = shell_exec('free -m');
        preg_match('/Mem:\s+(\d+)\s+(\d+)/', $output, $matches);
        return ['total' => (int) $matches[1], 'used' => (int) $matches[2]];
    }

    /**
     * Reads the load average from /proc/loadavg and returns an array of the averages in
     * the format of [1-minute, 5-minutes, 15-minutes].
     * 
     * @return array
     */
    function getLoadAverage()
    {
        $output = shell_exec('cat /proc/loadavg');
        preg_match('/([\d\.]+)\s*([\d\.]+)\s*([\d\.]+)/', $output, $matches);
        return [(float) $matches[1], (float) $matches[2], (float) $matches[3]];
    }
}
