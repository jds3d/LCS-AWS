<?php
/* OPM Calendar Calculator */
ini_set('display_errors',1);
// for this class, use UTC times (no daylight savings)
date_default_timezone_set('UTC');

class OPMCalc {
    public static function isDateOPMHoliday($date) {
        // $date must be a unix time string
        $year = date("Y",$date);
        $month = date("n",$date);
        $day = date("d",$date);

        // January
        if ($month == 1 || ($month == 12 && $day > 30)) {
            if (($month == 12 && $day > 30) || $day <= 3) {
                // New Year's Day (Jan 1)
                $actual = strtotime("1/1/" . $year);
                if ($month == 12) {
                    $actual = strtotime("1/1/" . ($year + 1));
                }
                $observed = OPMCalc::opmHolidayRounder($actual);
                if ($observed == $date) {
                    return true;
                }
            }

            // MLK Day third Monday of Jan
            elseif ($day >= 15 && $day <= 21) {
                if (OPMCalc::multipleWeekday(1,3,1,$year) == $date) {
                    return true;
                }
            }
        }
        // February
        elseif ($month == 2) {
            // Washington's birthday third Monday of Feb
            if (OPMCalc::multipleWeekday(1,3,2,$year) == $date) {
                return true;
            }
        }
        // May
        elseif ($month == 5) {
            // Memorial Day last Monday of May

            // get the first Monday of June
            $juneMonday = OPMCalc::multipleWeekday(1,1,6,$year);
            // go back a week
            $observed = $juneMonday - (7 * 86400);
            if ($observed == $date) {
                return true;
            }
        }
        // July
        elseif ($month == 7) {
            // Independence Day July 4
            $actual = strtotime("7/4/" . $year);
            $observed = OPMCalc::opmHolidayRounder($actual);
            if ($observed == $date) {
                return true;
            }
        }
        // September
        elseif ($month == 9) {
            // Labor Day first Monday
            if (OPMCalc::multipleWeekday(1,1,9,$year) == $date) {
                return true;
            }
        }
        // October
        elseif ($month == 10) {
            // Columbus Day second Monday
            if (OPMCalc::multipleWeekday(1,2,10,$year) == $date) {
                return true;
            }
        }
        // November
        elseif ($month == 11) {
            if ($day >= 10 && $day <= 12) {
                // Veteran's Day November 11
                $actual = strtotime("11/11/" . $year);
                $observed = OPMCalc::opmHolidayRounder($actual);
                if ($observed == $date) {
                    return true;
                }
            }
            if ($day >= 22 && $day <= 28) {
                // Thanksgiving fourth Thursday
                if (OPMCalc::multipleWeekday(4,4,11,$year) == $date) {
                    return true;
                }
            }
        }
        // December
        elseif ($month == 12) {
            // Christmas December 25
            $actual = strtotime("12/25/" . $year);
            $observed = OPMCalc::opmHolidayRounder($actual);
            if ($observed == $date) {
                return true;
            }
        }

        else {
            // not a holiday
            return false;
        }


    }

    public static function opmHolidayRounder($date) {
        $weekday = date("w",$date);
        if ($weekday == 6) {
            // if the holiday falls on a Saturday, it is observed on Friday
            return ($date - 86400);
        }
        else if ($weekday == 0) {
            // if the holiday falls on a Sunday, it is observed on Monday
            return ($date + 86400);
        }
        else {
            // otherwise it is observed on its actual date
            return $date;
        }
    }

    public static function multipleWeekday($weekday, $instance, $month, $year) {
        // find the X instance of a weekday in a month
        $monthStart = strtotime($month . "/1/" . $year);

        $startDay = date("w",$monthStart);
        $startDate = $monthStart;

        if ($weekday >= $startDay) {
            // the weekday we are looking for is on the first of the month or later
            // set start day to the first day of the weekday in the month
            $startDate = $startDate + (($weekday - $startDay) * 86400);
        }
        else {
            // the weekday we are looking for is before the first of the month or later
            // set start day by going forward a week, then subtracting the difference
            $startDate = $startDate + ((7 - ($startDay - $weekday)) * 86400);
        }

        // multiply it by the instance
        $instanceDate = (($instance - 1) * 7 * 86400) + $startDate;
        return $instanceDate;
    }

    public static function businessDayDuration($startDate, $days) {
        // get the date after X business days
        $startDate = strtotime($startDate);
        // strip time to midnight
        $startDateStr = date("m/d/Y",$startDate);
        $startDate = strtotime($startDateStr);

        $currentDay = $startDate;

        // start moving forward X days
        for ($i = 1; $i <= $days; $i++) {
            // add 24 hours to the last day
            $currentDay = $currentDay + 86400;

            $skipday = false;

            // is the new day a weekend?
            $weekday = date("w",$currentDay);
            if ($weekday == 0 || $weekday == 6) {
                // weekend
                $skipday = true;
            }

            if ($skipday == false) {
                // weekday, now we check for holidays
                $opmHoliday = OPMCalc::isDateOPMHoliday($currentDay);
                if ($opmHoliday == true) {
                    $skipday = true;
                }
            }


            if ($skipday == true) {
                // this is not a business day, unincrement (so we stay at same for
                // loop position)
                $i--;
            }
        }
        return $currentDay;
    }


    public static function businessDayDurationNegative($startDate, $days) {
        // get the date after X business days
        $startDate = strtotime($startDate);
        // strip time to midnight
        $startDateStr = date("m/d/Y",$startDate);
        $startDate = strtotime($startDateStr);

        $currentDay = $startDate;

        // start moving forward X days
        for ($i = 1; $i <= $days; $i++) {
            // add 24 hours to the last day
            $currentDay = $currentDay - 86400;

            $skipday = false;

            // is the new day a weekend?
            $weekday = date("w",$currentDay);
            if ($weekday == 0 || $weekday == 6) {
                // weekend
                $skipday = true;
            }

            if ($skipday == false) {
                // weekday, now we check for holidays
                $opmHoliday = OPMCalc::isDateOPMHoliday($currentDay);
                if ($opmHoliday == true) {
                    $skipday = true;
                }
            }


            if ($skipday == true) {
                // this is not a business day, unincrement (so we stay at same for
                // loop position)
                $i--;
            }
        }
        return $currentDay;
    }
}

?>