<?php

#-------------------------------------------------------
# Copyright (C) 2019 The Trustees of Indiana University
# SPDX-License-Identifier: BSD-3-Clause
#-------------------------------------------------------

namespace IU\RedCapEtlModule;

class AdminConfig implements \JsonSerializable
{
    public const KEY = 'admin-config';
    public const DAY_LABELS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    # Property constants
    public const SSL_VERIFY     = 'sslVerify';
    public const CA_CERT_FILE   = 'caCertFile';

    #const ALLOW_EMBEDDED_SERVER              = 'allowEmbeddedServer';
    #const EMBEDDED_SERVER_EMAIL_FROM_ADDRESS = 'embeddedServerEmailFromAddress';
    #const EMBEDDED_SERVER_LOG_FILE           = 'embeddedServerLogFile';

    public const ALLOW_ON_DEMAND    = 'allowOnDemand';
    public const ALLOW_CRON         = 'allowCron';
    public const ALLOWED_CRON_TIMES = 'allowedCronTimes';

    /** @var boolean indicates if SSL verification should be done for local REDCap */
    private $sslVerify;

    /** @var string certificate authority certificate file used for SSL verification of REDCap. */
    private $caCertFile;

    private $allowOnDemand;  // Allow the ETL process to be run on demand

    private $allowCron;
    private $allowedCronTimes;

    private $maxJobsPerTime;


    public function __construct()
    {
        $this->allowOnDemand = false;
        $this->allowCron     = true;

        $this->maxJobsPerTime = 10;

        $this->allowedCronTimes = array();
        foreach (range(0, 6) as $day) {
            $this->allowedCronTimes[$day] = array();
            foreach (range(0, 23) as $hour) {
                if ($day === 0 || $day === 6 || $hour < 8 || $hour > 17) {
                    $this->allowedCronTimes[$day][$hour] = true;
                } else {
                    $this->allowedCronTimes[$day][$hour] = false;
                }
            }
        }

        $this->sslVerify = true;
    }


    public function fromJson($json)
    {
        if (!empty($json)) {
            $object = json_decode($json, true);
            foreach (get_object_vars($this) as $var => $value) {
                $this->$var = $object[$var];
            }
        }
    }

    public function toJson()
    {
        $json = json_encode($this);
        return $json;
    }


    /**
     * Sets admin configuration properties from a map that uses the
     * property keys for this class.
     */
    public function set($properties)
    {
        # Set allowed cron times
        if (array_key_exists(self::ALLOWED_CRON_TIMES, $properties)) {
            $times = $properties[self::ALLOWED_CRON_TIMES];
            if (is_array($times)) {
                for ($row = 0; $row < count($this->allowedCronTimes); $row++) {
                    if (array_key_exists($row, $times)) {
                        $dayTimes = $times[$row];
                        if (is_array($dayTimes)) {
                            for ($col = 0; $col < count($this->allowedCronTimes[$row]); $col++) {
                                if (array_key_exists($col, $dayTimes)) {
                                    $this->allowedCronTimes[$row][$col] = true;
                                } else {
                                    $this->allowedCronTimes[$row][$col] = false;
                                }
                            }
                        }
                    }
                }
            }
        }

        # Set flag that indicates if users can run jobs on demand
        if (array_key_exists(self::ALLOW_ON_DEMAND, $properties)) {
            $this->allowOnDemand = true;
        } else {
            $this->allowOnDemand = false;
        }

        # Set flag that indicates if cron (scheduled) jobs can be run by users
        if (array_key_exists(self::ALLOW_CRON, $properties)) {
            $this->allowCron = true;
        } else {
            $this->allowCron = false;
        }

        # Set flag indicating of SSL certificate verification should be done
        if (array_key_exists(self::SSL_VERIFY, $properties)) {
            $this->sslVerify = true;
        } else {
            $this->sslVerify = false;
        }

        # Set certificate authority certificate file
        if (array_key_exists(self::CA_CERT_FILE, $properties)) {
            $this->caCertFile = $properties[self::CA_CERT_FILE];
        } else {
            $this->caCertFile = '';
        }
    }


    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
    }

    public function getTimes()
    {
        return range(0, 23);
    }

    public function getHtmlTimeLabel($time)
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

        if ($startTime < 10) {
            $startTime = "&nbsp;" . $startTime;
        }

        if ($endTime < 12 || $endTime == 24) {
            $endTimeSuffix = 'am';
        } else {
            $endTimeSuffix = 'pm';
        }

        if ($endTime > 12) {
            $endTime -= 12;
        }

        if ($endTime < 10) {
            $endTime = "&nbsp;" . $endTime;
        }

        $label = "{$startTime}{$startTimeSuffix}&nbsp;-&nbsp;{$endTime}{$endTimeSuffix}";

        return $label;
    }

    public function getTimeLabels()
    {
        $labels = array();
        $labelNumbers = range(0, 23);
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
                if ($end < 24) {
                    $endSuffix = 'pm';
                }

                if ($end > 12) {
                    $end -= 12;
                }
            }

            $start .= $startSuffix;

            $end .= $endSuffix;

            $labels[$i] = $start . ' - ' . $end;
        }
        return $labels;
    }

    public function getAllowOnDemand()
    {
        return $this->allowOnDemand;
    }

    public function getAllowCron()
    {
        return $this->allowCron;
    }

    public function isAllowedCronTime($day, $time)
    {
        $isAllowed = $this->allowedCronTimes[$day][$time];
        return $isAllowed;
    }

    public function getSslVerify()
    {
        return $this->sslVerify;
    }

    public function getCaCertFile()
    {
        return $this->caCertFile;
    }
}
