<?php

namespace IU\RedCapEtlModule;


class AdminConfig implements \JsonSerializable
{
    const KEY = 'admin-config';
    const DAY_LABELS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    private $allowCron;
    private $allowDailyCron;  # if cron is allowed, but not daily, then weekly is allowed
    private $allowedCronTimes;
    private $maxJobsPerTime;

    public function __construct()
    {
        $this->allowCron      = true;
        $this->allowDailyCron = true;
        $this->maxJobsPerTime = 10;

        $this->allowedCronTimes = array();
        foreach (range(0,6) as $day) {
            foreach (range(0, 23) as $hour) {
                if ($day === 0 || $day === 6 || $hour < 8 || $hour > 17) {
                    $this->allowedCronTimes[$day][$hour] = '1';
                } else {
                    $this->allowedCronTimes[$day][$hour] = '0';
                }
            }
        }
    }

    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    public function isAllowedCronTime($day, $time)
    {
        $isAllowed = $this->allowedCronTimes[$day][$time];
        return $isAllowed;
    }

    public function getDayLabel($dayNumber)
    {

        $label = '';
        if (array_key_exists($dayNumber, self::DAY_LABELS)) {
            $label = self::DAY_LABELS[$dayNumber];
        }
        return $label;
    }


    public function getTimes()
    {
        return range(0,23);
    }

    public function getTimeLabel($time)
    {
        $label = '';
        $startTime = $time;
        $endTime   = $time + 1;

        if ($startTime < 12) {
            $startTimeSuffix = 'am';
            if ($startTime == 0) {
                $startTime = 12;
            }
        } else {
            $startTimeSuffix = 'pm';
        }

        if ($startTime > 12) {
            $startTime -= 12;
        }

        if ($endTime < 12) {
            $endTimeSuffix = 'am';
        } else {
            $endTimeSuffix = 'pm';
        }

        if ($endTime > 12) {
            $endTime -= 12;
        }

        $label = "{$startTime}{$startTimeSuffix} - {$endTime}{$endTimeSuffix}";

        return $label;
    }

    public function getTimeLabels()
    {
        $labels = array();
        $labelNumbers = range(0,23);
        for ($i = 0; $i < count($labelNumbers); $i++) {
            $start = $i;
            $end   = $i + 1;

            $startSuffix = 'am';
            if ($start >= 12) {
                $startSuffix = 'pm';
                if ($start > 12) {
                    $start -= 12;
                }
            }
            if ($start === 0) {
                $start = 12;
            }

            $endSuffix = 'am';
            if ($end >= 12) {
                $endSuffix = 'pm';
                if ($end > 12) {
                    $end -= 12;
                }
            }

            $start .= $startSuffix;

            $end .= $endSuffix;

            $labels[$i] = $start.'-'.$end;
        }
        return $labels;
    }

    public function getLongTimeLabels()
    {
        $labels = array();
        $labelNumbers = range(0,23);
        for ($i = 0; $i < count($labelNumbers); $i++) {
            $start = $i;
            $end   = $i + 1;

            $startSuffix = 'am';
            if ($start >= 12) {
                $startSuffix = 'pm';
                $start -= 12;
            }
            if ($start === 0) {
                $start = 12;
            }

            $endSuffix = 'am';
            if ($end >= 12) {
                $endSuffix = 'pm';
                $end -= 12;
            }

            if ($start < 10) {
                $start = '0'.$start;
            }
            $start .= ':00'.$startSuffix;

            if ($end < 10) {
                $end = '0'.$end;
            }
            $end .= ':00'.$endSuffix;

            $labels[$i] = $start.'-'.$end;
        }
        return $labels;
    }

    public function fromJson($json)
    {
        if (!empty($json)) {
            $object = json_decode($json);
            foreach (get_object_vars($this) as $var => $value) {
                $this->$var = $object->$var;
            }
        }
    }

    public function toJson()
    {
        $json = json_encode($this);
        return $json;
    }

    public function getAllowCron()
    {
        return $this->allowCron;
    }

    public function getAllowDailyCron()
    {
        return $this->allowDailyCron;
    }

}
