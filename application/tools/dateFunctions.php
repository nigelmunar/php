<?php
    declare(strict_types = 1);

    function time_elapsed_string($ago, $full = false) {
        $now = new DateTime;
        $diff = $now->diff($ago);
    
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;
    
        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }
    
        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    function validateDate(string $date, string $format = 'Y-m-d') : bool
    {
        $d = DateTime::createFromFormat($format, $date);
        
        return $d && $d->format($format) === $date;
    }

    function datetimeFromDB(string $date) : \DateTime
    {
        global $dbTimezone, $displayTimezone;

        $newDate = new \DateTime($date, $dbTimezone);
        $newDate->setTimezone($displayTimezone);

        return $newDate;
    }

    function datetimeToDB(\DateTime $date) : \DateTime
    {
        global $dbTimezone;

        $date->setTimezone($dbTimezone);

        return $date;
    }

    function forumDate(datetime $date) : string
    {
        return $date->format('d M Y');
    }

    function profileDate(\DateTime $date) : string
    {
        global $displayTimezone;

        $dateCopy = clone $date;
        $dateCopy->setTime(0, 0, 0);
        $today = new \DateTime('now', $displayTimezone);
        $today->setTime(0, 0, 0);

        $interval = $dateCopy->diff($today, true);
       
        $days = (int)$interval->format('%a');
        $months = (int)$interval->format('%m');
        $years = (int)$interval->format('%y');

        switch($days)
        {
            case 0:
                return htmlspecialchars(trim($date->format("h:i a")) . ' today');
                
                break;
            case 1:
                return htmlspecialchars(trim($date->format("h:i a")) . ' yesterday');
                
                break;
            default:
                if($days <= 7)
                {
                    return htmlspecialchars($days . ' days ago');
                }
                elseif($days < 30)
                {
                    return htmlspecialchars((int)($days / 7) . ' week' . ((int)($days / 7) != 1 ? 's' : '') . ' ago');
                }
                elseif($years === 0)
                {
                    return htmlspecialchars($months . ' month' . ($months != 1 ? 's' : '') . ' ago');
                }
                else
                {
                    return htmlspecialchars($years . ' year' . ($years != 1 ? 's' : '') . ' ago');
                }

                break;
        }

        return '';
    }

    function pretty12HourTime(datetime $date) : string
    {
        return $date->format("h:i a");
    }

    function niceDateNoSuffix(datetime $date) : string
    {
        return $date->format("d F Y");
    }