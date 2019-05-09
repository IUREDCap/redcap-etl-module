<?php

namespace IU\RedCapEtlModule;

class AdminConfig implements \JsonSerializable
{
    const KEY = 'admin-config';
    const DAY_LABELS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    
    # Property constants
    const SSL_VERIFY     = 'sslVerify';
    
    #const ALLOW_EMBEDDED_SERVER              = 'allowEmbeddedServer';
    #const EMBEDDED_SERVER_EMAIL_FROM_ADDRESS = 'embeddedServerEmailFromAddress';
    #const EMBEDDED_SERVER_LOG_FILE           = 'embeddedServerLogFile';
    
    const ALLOW_ON_DEMAND    = 'allowOnDemand';
    const ALLOW_CRON         = 'allowCron';
    const ALLOWED_CRON_TIMES = 'allowedCronTimes';
    
    /** @var boolean indicates if SSL verification should be done for local REDCap */
    private $sslVerify;
    
    # private $allowEmbeddedServer;  // Allow embedded REDCap-ETL server to be used
    
    /** @var string log file (if any) on REDCap server to use for the embedded ETL server. */
    # private $embeddedServerLogFile;
    
    /** @var string E-mail from address to use for embedded server
     *     (must be set for e-mail logging to work for embedded server). */
    #private $embeddedServerEmailFromAddress;
    
    private $allowOnDemand;  // Allow the ETL process to be run on demand
    
    private $allowCron;
    private $allowedCronTimes;
    
    private $maxJobsPerTime;


    public function __construct()
    {
        $this->allowOnDemandRuns = true;
        $this->allowCron         = true;
        
        $this->maxJobsPerTime = 10;

        $this->allowedCronTimes = array();
        foreach (range(0, 6) as $day) {
            $this->allowedCronTimes[$day] = array();
            foreach (range(0, 23) as $hour) {
                if ($day === 0 || $day === 6 || $hour < 8 || $hour > 17) {
                    $this->allowedCronTimes[$day][$hour] = '1';
                } else {
                    $this->allowedCronTimes[$day][$hour] = '0';
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
                foreach ($times as $rownum => $row) {
                    if (is_array($row)) {
                        foreach ($row as $colnum => $value) {
                            $times[$rownum][$colnum] = Filter::sanitizeLabel($value);
                        }
                    }
                }
            } else {
                $times = array();
            }
            $this->allowedCronTimes = $times;
        } else {
            $this->allowedCronTimes = array();
        }
        
        # Set allow embedded server (false will return no value)
        #if (array_key_exists(self::ALLOW_EMBEDDED_SERVER, $properties)) {
        #    $this->allowEmbeddedServer = true;
        #} else {
        #    $this->allowEmbeddedServer = false;
        #}
    
        # Set the e-mail from address for the embedded server
        #if (array_key_exists(self::EMBEDDED_SERVER_EMAIL_FROM_ADDRESS, $properties)) {
        #    $this->embeddedServerEmailFromAddress =
        #        trim(strip_tags($properties[self::EMBEDDED_SERVER_EMAIL_FROM_ADDRESS]));
        #} else {
        #    $this->embeddedServerEmailFromAddress = '';
        #}

        # Set the log file for the embedded server
        #if (array_key_exists(self::EMBEDDED_SERVER_LOG_FILE, $properties)) {
        #    $this->embeddedServerLogFile = trim(strip_tags($properties[self::EMBEDDED_SERVER_LOG_FILE]));
        #} else {
        #    $this->embeddedServerLogFile = '';
        #}

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
    }


    public function jsonSerialize()
    {
        return (object) get_object_vars($this);
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
            $startTime = "&nbsp;".$startTime;
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
            $endTime = "&nbsp;".$endTime;
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

            $labels[$i] = $start.' - '.$end;
        }
        return $labels;
    }

    public function getLongTimeLabels()
    {
        $labels = array();
        $labelNumbers = range(0, 23);
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

    #public function getAllowEmbeddedServer()
    #{
    #    return $this->allowEmbeddedServer;
    #}
    
    #public function setAllowEmbeddedServer($allowEmbeddedServer)
    #{
    #    $this->allowEmbeddedServer = $allowEmbeddedServer;
    #}
 

    #public function getEmbeddedServerLogFile()
    #{
    #    return $this->embeddedServerLogFile;
    #}
    
    #public function setEmbeddedServerLogFile($logFile)
    #{
    #    $this->embeddedServerLogFile = $logFile;
    #}
 

    #public function getEmbeddedServerEmailFromAddress()
    #{
    #    return $this->embeddedServerEmailFromAddress;
    #}
    
    #public function setEmbeddedServerEmailFromAddress($fromEmail)
    #{
    #    $this->embeddedServerEmailFromAddress = $fromEmail;
    #}
 

    public function getAllowOnDemand()
    {
        return $this->allowOnDemand;
    }
    
    public function setAllowOnDemand($allowOnDemand)
    {
        $this->allowOnDemand = $allowOnDemand;
    }
    
    public function getAllowCron()
    {
        return $this->allowCron;
    }
    
    public function setAllowCron($allowCron)
    {
        $this->allowCron = $allowCron;
    }

    public function isAllowedCronTime($day, $time)
    {
        $isAllowed = $this->allowedCronTimes[$day][$time];
        return $isAllowed;
    }
     
    public function getAllowedCronTimes()
    {
        return $this->allowedCronTimes;
    }
    
    public function setAllowedCronTimes($times)
    {
        $this->allowedCronTimes = array();
        foreach (range(0, 6) as $day) {
            $this->allowedCronTimes[$day] = array();
            
            foreach (range(0, 23) as $hour) {
                if (array_key_exists($day, $times) && array_key_exists($hour, $times[$day])) {
                    $this->allowedCronTimes[$day][$hour] = 1;
                } else {
                    $this->allowedCronTimes[$day][$hour] = 0;
                }
            }
        }
    }
    
    public function getSslVerify()
    {
        return $this->sslVerify;
    }
    
    public function setSslVerify($sslVerify)
    {
        $this->sslVerify = $sslVerify;
    }
}
