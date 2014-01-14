<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );
/**
 * Time Helper class file.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 */

/**
 * Time Helper class for easy use of time data.
 *
 * Manipulation of time data.
 *
 * @package		cake
 * @subpackage	cake.cake.libs.view.helpers
 */
class TimeHelper extends MyHelper
{
/**
 * Converts given time (in server's time zone) to user's local time, given his/her offset from GMT.
 *
 * @param string $serverTime UNIX timestamp
 * @param int $userOffset User's offset from GMT (in hours)
 * @return string UNIX timestamp
 */
    function convert($serverTime, $userOffset) {
        $serverOffset = $this->serverOffset();
        $gmtTime = $serverTime - $serverOffset;
        $userTime = $gmtTime + $userOffset * (60*60);
        return $userTime;
    }
/**
 * Returns server's offset from GMT in seconds.
 *
 * @return int Offset
 */
    function serverOffset() {
        return date('Z', time());
    }

/**
 * Returns a UNIX timestamp, given either a UNIX timestamp or a valid strtotime() date string.
 *
 * @param string $dateString Datetime string
 * @param int $userOffset User's offset from GMT (in hours)
 * @return string Parsed timestamp
 */
    function fromString($dateString, $userOffset = null) {
        if (empty($dateString)) {
            return false;
        }
        if (is_integer($dateString) || is_numeric($dateString)) {
            $date = intval($dateString);
        } else {
            $date = strtotime($dateString);
        }
        if ($userOffset !== null) {
            return $this->convert($date, $userOffset);
        }
        return $date;
    }
/**
 * Returns a nicely formatted date string for given Datetime string.
 *
 * @param string $date_string Datetime string or Unix timestamp
 * @return string Formatted date string
 */
	function nice($date_string = null, $format = null, $offset = null) {

		$format = $format ? $format : __t("%B %d, %Y",true);

		if(is_null($offset)) {
			$date_string = cmsFramework::localDate($date_string);
		}

		$date = $date_string != null ? $this->fromString($date_string) : time();

		$ret = $this->format($date, $format);

		return $this->output($ret);

	}
/**
 * Returns a formatted descriptive date string for given datetime string.
 *
 * If the given date is today, the returned string could be "Today, 16:54".
 * If the given date was yesterday, the returned string could be "Yesterday, 16:54".
 * If $date_string's year is the current year, the returned string does not
 * include mention of the year.
 *
 * @param string $date_string Datetime string or Unix timestamp
 * @return string Described, relative date string
 */
	function niceShort($date_string = null, $format = null, $offset = null) {

		$format = $format ? $format : __t("%b %d, %Y",true);

		$date = $date_string != null ? $this->fromString($date_string) : time();

		if(is_null($offset)) {
			$date_string = cmsFramework::localDate($date_string);
		}

		if ($this->isToday($date)) {
			$ret = __l("Today",true);
		} elseif ($this->wasYesterday($date)) {
			$ret = __l("Yesterday",true);
		} else {
			$ret = $this->format($date, $format);
		}

		return $this->output($ret);
	}

	function dateParts($date_string) {

			$parts = array();

			// Explode date into elements
			ereg ("([0-9]{4})-([0-9]{1,2})-([0-9]{1,2}) ([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})", $date_string, $datetime);
			$parts['year'] = $year = $datetime[1]+0;
			$parts['month'] = $month = $datetime[2]+0;
			$parts['day'] = $day = $datetime[3]+0;
			$parts['hour'] = $hour = $datetime[4]+0;
			$parts['min'] = $min = $datetime[5]+0;
			$length = strlen($min);
			if ($length == 1) $min="0".$min;
			$parts['secs'] = $secs = $datetime[6]+0;
			$length = strlen($secs);
			if ($length == 1) $secs = "0" . $secs;
			$parts['weekday'] = strftime ("%w", mktime($hour,$min,$secs,$month,$day,$year)); // Week day in number

			return $parts;
	}

	function _monthName($month, $short = false) {
		switch($month) {
			case 1: return $short ? __l("Jan",true) : __l("January",true);
			case 2: return $short ? __l("Feb",true) : __l("February",true);
			case 3: return $short ? __l("Mar",true) : __l("March",true);
			case 4:	return $short ? __l("Apr",true) : __l("April",true);
			case 5: return $short ? __l("May-Short",true) : __l("May",true);
			case 6:	return $short ? __l("Jun",true) : __l("June",true);
			case 7:	return $short ? __l("Jul",true) : __l("July",true);
			case 8:	return $short ? __l("Aug",true) : __l("August",true);
			case 9: return $short ? __l("Sep",true) : __l("September",true);
			case 10: return $short ? __l("Oct",true) : __l("October",true);
			case 11: return $short ? __l("Nov",true) : __l("November",true);
			case 12: return $short ? __l("Dec",true) : __l("December",true);
		}
	}

/**
 * Returns a partial SQL string to search for all records between two dates.
 *
 * @param string $date_string Datetime string or Unix timestamp
 * @param string $end Datetime string or Unix timestamp
 * @param string $field_name Name of database field to compare with
 * @return string Partial SQL string.
 */
	function daysAsSql($begin, $end, $field_name) {
		$begin = $this->fromString($begin);
		$end = $this->fromString($end);
		$begin = date('Y-m-d', $begin) . ' 00:00:00';
		$end = date('Y-m-d', $end) . ' 23:59:59';

		$ret  ="($field_name >= '$begin') AND ($field_name <= '$end')";
		return $this->output($ret);
	}
/**
 * Returns a partial SQL string to search for all records between two times
 * occurring on the same day.
 *
 * @param string $date_string Datetime string or Unix timestamp
 * @param string $field_name Name of database field to compare with
 * @return string Partial SQL string.
 */
	function dayAsSql($date_string, $field_name) {
		$date = $this->fromString($date_string);
		$ret = $this->daysAsSql($date_string, $date_string, $field_name);
		return $this->output($ret);
	}
/**
 * Returns true if given datetime string is today.
 *
 * @param string $date_string Datetime string or Unix timestamp
 * @return boolean True if datetime string is today
 */
	function isToday($date_string) {
		$date = $this->fromString($date_string);
		$ret = date('Y-m-d', $date) == date('Y-m-d', time());
		return $this->output($ret);
	}
/**
 * Returns true if given datetime string is within this week
 * @param string $date_string
 * @return boolean True if datetime string is within current week
 */
	function isThisWeek($date_string) {
		$date = $this->fromString($date_string) + 86400;
		return date('W Y', $date) == date('W Y', time());
	}
/**
 * Returns true if given datetime string is within this month
 * @param string $date_string
 * @return boolean True if datetime string is within current month
 */
	function isThisMonth($date_string) {
		$date = $this->fromString($date_string);
		return date('m Y',$date) == date('m Y', time());
	}
/**
 * Returns true if given datetime string is within current year.
 *
 * @param string $date_string Datetime string or Unix timestamp
 * @return boolean True if datetime string is within current year
 */
	function isThisYear($date_string) {
		$date = $this->fromString($date_string);
		$ret = date('Y', $date) == date('Y', time());
		return $this->output($ret);
	}
/**
 * Returns true if given datetime string was yesterday.
 *
 * @param string $date_string Datetime string or Unix timestamp
 * @return boolean True if datetime string was yesterday
 */
	function wasYesterday($date_string) {
		$date = $this->fromString($date_string);
		$ret = date('Y-m-d', $date) == date('Y-m-d', strtotime('yesterday'));
		return $this->output($ret);
	}
/**
 * Returns true if given datetime string is tomorrow.
 *
 * @param string $date_string Datetime string or Unix timestamp
 * @return boolean True if datetime string was yesterday
 */
	function isTomorrow($date_string) {
		$date = $this->fromString($date_string);
		$ret = date('Y-m-d', $date) == date('Y-m-d', strtotime('tomorrow'));
		return $this->output($ret);
	}
/**
 * Returns a UNIX timestamp from a textual datetime description. Wrapper for PHP function strtotime().
 *
 * @param string $date_string Datetime string to be represented as a Unix timestamp
 * @return int Unix timestamp
 */
	function toUnix($date_string) {
		$ret = strtotime($date_string);
		return $this->output($ret);
	}
/**
 * Returns a date formatted for Atom RSS feeds.
 *
 * @param string $date_string Datetime string or Unix timestamp
 * @return string Formatted date string
 */
	function toAtom($date_string) {
		$date = $this->fromString($date_string);
		$ret = date('Y-m-d\TH:i:s\Z', $date);
		return $this->output($ret);
	}
/**
 * Formats date for RSS feeds
 *
 * @param string $date_string Datetime string or Unix timestamp
 * @return string Formatted date string
 */
	function toRSS($date_string) {
		$date = TimeHelper::fromString($date_string);
		$ret = date("r", $date);
		return $this->output($ret);
	}
/**
 * Returns either a relative date or a formatted date depending
 * on the difference between the current time and given datetime.
 * $datetime should be in a <i>strtotime</i> - parsable format, like MySQL's datetime datatype.
 *
 * Options:
 *
 * - 'format' => a fall back format if the relative time is longer than the duration specified by end
 * - 'end' => The end of relative time telling
 * - 'userOffset' => Users offset from GMT (in hours)
 *
 * Relative dates look something like this:
 *    3 weeks, 4 days ago
 *    15 seconds ago
 * Formatted dates look like this:
 *    on 02/18/2004
 *
 * The returned string includes 'ago' or 'on' and assumes you'll properly add a word
 * like 'Posted ' before the function output.
 *
 * @param string $dateString Datetime string or Unix timestamp
 * @param array $options Default format if timestamp is used in $dateString
 * @return string Relative time string.
 */
    function timeAgoInWords($dateTime, $options = array())
    {
        $userOffset = null;
        if (is_array($options) && isset($options['userOffset'])) {
            $userOffset = $options['userOffset'];
        }

        $now = strtotime(gmdate('Y-m-d H:i:s')); //time();  Alejandro - time() is inconsistent with gmdate in JReviews

        if (!is_null($userOffset)) {
            $now =  $this->convert(strtotime(gmdate('Y-m-d H:i:s')) /* time(); Alejandro - time inconsistend with gmdate in JReviews*/, $userOffset);
        }
        $inSeconds = $this->fromString($dateTime, $userOffset);
        $backwards = ($inSeconds > $now);

        $format = cmsFramework::getDateFormat();
        $end = '+1 month';

        if (is_array($options)) {
            if (isset($options['format'])) {
                $format = $options['format'];
                unset($options['format']);
            }
            if (isset($options['end'])) {
                $end = $options['end'];
                unset($options['end']);
            }
        } else {
            $format = $options;
        }

        if ($backwards) {
            $futureTime = $inSeconds;
            $pastTime = $now;
        } else {
            $futureTime = $now;
            $pastTime = $inSeconds;
        }
        $diff = $futureTime - $pastTime;

        // If more than a week, then take into account the length of months
        if ($diff >= 604800) {
            $current = array();
            $date = array();

            list($future['H'], $future['i'], $future['s'], $future['d'], $future['m'], $future['Y']) = explode('/', date('H/i/s/d/m/Y', $futureTime));

            list($past['H'], $past['i'], $past['s'], $past['d'], $past['m'], $past['Y']) = explode('/', date('H/i/s/d/m/Y', $pastTime));
            $years = $months = $weeks = $days = $hours = $minutes = $seconds = 0;

            if ($future['Y'] == $past['Y'] && $future['m'] == $past['m']) {
                $months = 0;
                $years = 0;
            } else {
                if ($future['Y'] == $past['Y']) {
                    $months = $future['m'] - $past['m'];
                } else {
                    $years = $future['Y'] - $past['Y'];
                    $months = $future['m'] + ((12 * $years) - $past['m']);

                    if ($months >= 12) {
                        $years = floor($months / 12);
                        $months = $months - ($years * 12);
                    }

                    if ($future['m'] < $past['m'] && $future['Y'] - $past['Y'] == 1) {
                        $years --;
                    }
                }
            }

            if ($future['d'] >= $past['d']) {
                $days = $future['d'] - $past['d'];
            } else {
                $daysInPastMonth = date('t', $pastTime);
                $daysInFutureMonth = date('t', mktime(0, 0, 0, $future['m'] - 1, 1, $future['Y']));

                if (!$backwards) {
                    $days = ($daysInPastMonth - $past['d']) + $future['d'];
                } else {
                    $days = ($daysInFutureMonth - $past['d']) + $future['d'];
                }

                if ($future['m'] != $past['m']) {
                    $months --;
                }
            }

            if ($months == 0 && $years >= 1 && $diff < ($years * 31536000)) {
                $months = 11;
                $years --;
            }

            if ($months >= 12) {
                $years = $years + 1;
                $months = $months - 12;
            }

            if ($days >= 7) {
                $weeks = floor($days / 7);
                $days = $days - ($weeks * 7);
            }
        } else {
            $years = $months = $weeks = 0;
            $days = floor($diff / 86400);

            $diff = $diff - ($days * 86400);

            $hours = floor($diff / 3600);
            $diff = $diff - ($hours * 3600);

            $minutes = floor($diff / 60);
            $diff = $diff - ($minutes * 60);
            $seconds = $diff;
        }
        $relativeDate = '';
        $diff = $futureTime - $pastTime;

        if ($diff > abs($now - $this->fromString($end))) {
            $relativeDate = sprintf(__t('on %s',true), date($format, $inSeconds));
        } else {
            if ($years > 0) {
                // years and months and days
                $relativeDate .= ($relativeDate ? ', ' : '') . $years . ' ' . __n('year', 'years', $years, true);
                $relativeDate .= $months > 0 ? ($relativeDate ? ', ' : '') . $months . ' ' . __n('month', 'months', $months, true) : '';
                $relativeDate .= $weeks > 0 ? ($relativeDate ? ', ' : '') . $weeks . ' ' . __n('week', 'weeks', $weeks, true) : '';
                $relativeDate .= $days > 0 ? ($relativeDate ? ', ' : '') . $days . ' ' . __n('day', 'days', $days, true) : '';
            } elseif (abs($months) > 0) {
                // months, weeks and days
                $relativeDate .= ($relativeDate ? ', ' : '') . $months . ' ' . __n('month', 'months', $months, true);
                $relativeDate .= $weeks > 0 ? ($relativeDate ? ', ' : '') . $weeks . ' ' . __n('week', 'weeks', $weeks, true) : '';
                $relativeDate .= $days > 0 ? ($relativeDate ? ', ' : '') . $days . ' ' . __n('day', 'days', $days, true) : '';
            } elseif (abs($weeks) > 0) {
                // weeks and days
                $relativeDate .= ($relativeDate ? ', ' : '') . $weeks . ' ' . __n('week', 'weeks', $weeks, true);
                $relativeDate .= $days > 0 ? ($relativeDate ? ', ' : '') . $days . ' ' . __n('day', 'days', $days, true) : '';
            } elseif (abs($days) > 0) {
                // days and hours
                $relativeDate .= ($relativeDate ? ', ' : '') . $days . ' ' . __n('day', 'days', $days, true);
                $relativeDate .= $hours > 0 ? ($relativeDate ? ', ' : '') . $hours . ' ' . __n('hour', 'hours', $hours, true) : '';
            } elseif (abs($hours) > 0) {
                // hours and minutes
                $relativeDate .= ($relativeDate ? ', ' : '') . $hours . ' ' . __n('hour', 'hours', $hours, true);
                $relativeDate .= $minutes > 0 ? ($relativeDate ? ', ' : '') . $minutes . ' ' . __n('minute', 'minutes', $minutes, true) : '';
            } elseif (abs($minutes) > 0) {
                // minutes only
                $relativeDate .= ($relativeDate ? ', ' : '') . $minutes . ' ' . __n('minute', 'minutes', $minutes, true);
            } else {
                // seconds only
                $relativeDate .= ($relativeDate ? ', ' : '') . $seconds . ' ' . __n('second', 'seconds', $seconds, true);
            }

            if (!$backwards) {
                $relativeDate = sprintf(__t("%s ago", true), $relativeDate);
            }
        }
        return $this->output($relativeDate);
    }
/**
 * Alias for timeAgoInWords, but can also calculate dates in the future
 * @param string $date_string Datetime string or Unix timestamp
 * @param string $format Default format if timestamp is used in $date_string
 * @return string Relative time string.
 * @see		timeAgoInWords
 */
	function relativeTime($datetime_string, $format = 'j/n/y') {
		$date = strtotime($datetime_string);

		if (strtotime("now") > $date) {
			$ret = $this->timeAgoInWords($datetime_string, $format, false);
		} else {
			$ret = $this->timeAgoInWords($datetime_string, $format, true);
		}

		return $this->output($ret);
	}
/**
 * Returns true if specified datetime was within the interval specified, else false.
 *
 * @param mixed $timeInterval the numeric value with space then time type. Example of valid types: 6 hours, 2 days, 1 minute.
 * @param mixed $date_string the datestring or unix timestamp to compare
 * @return boolean
 */
	function wasWithinLast($timeInterval, $date_string) {
		$date = $this->fromString($date_string);
		$result = preg_split('/\\s/', $timeInterval);
		$numInterval = $result[0];
		$textInterval = $result[1];
		$currentTime = floor(time());
		$seconds = ($currentTime - floor($date));

		switch($textInterval) {
			case "seconds":
			case "second":
				$timePeriod = $seconds;
				$ret = $return;
			break;

			case "minutes":
			case "minute":
				$minutes = floor($seconds / 60);
				$timePeriod = $minutes;
			break;

			case "hours":
			case "hour":
				$hours = floor($seconds / 3600);
				$timePeriod = $hours;
			break;

			case "days":
			case "day":
				$days = floor($seconds / 86400);
				$timePeriod = $days;
			break;

			case "weeks":
			case "week":
				$weeks = floor($seconds / 604800);

				$timePeriod = $weeks;
			break;

			case "months":
			case "month":
				$months = floor($seconds / 2629743.83);
				$timePeriod = $months;
			break;

			case "years":
			case "year":
				$years = floor($seconds / 31556926);
				$timePeriod = $years;
			break;

			default:
				$days = floor($seconds / 86400);
				$timePeriod = $days;
			break;
		}

		if ($timePeriod <= $numInterval) {
			$ret = true;
		} else {
			$ret = false;
		}

		return $this->output($ret);
	}

	function gmt($string = null) {
		if ($string != null) {
			$string = $this->fromString($string);
		} else {
			$string = time();
		}
		$string = $this->fromString($string);
		$hour = intval(date("G", $string));
		$minute = intval(date("i", $string));
		$second = intval(date("s", $string));
		$month = intval(date("n", $string));
		$day = intval(date("j", $string));
		$year = intval(date("Y", $string));

		$return = gmmktime($hour, $minute, $second, $month, $day, $year);
		return $return;
	}

	function format($date, $format = '%B %d, %Y') {

		if($format == 'F d, Y')
			$format = '%B %d, %Y';
		elseif($format == 'F d, Y')
			$format = '%B %d, %Y';
		elseif($format == 'd F, Y')
			$format = '%d %B, %Y';

		if(strpos($format, '%B') !== false)
			$format = str_replace('%B', $this->_monthName(date('n', $date)), $format);
		if(strpos($format, '%b') !== false)
			$format = str_replace('%b', $this->_monthName(date('n', $date),true), $format);

		return strftime($format, $this->fromString($date));
	}

	function sToHMS($input)
	{
		$out = '';

		$seconds = $input % 60;
		$input = floor($input / 60);

		$minutes = $input % 60;
		$input = floor($input / 60);

		$hours = $input % 60;
		$input = floor($input / 60);

		if($hours) {
			$out = $hours . ':';
		}

		$out .= sprintf("%02d",$minutes);

		$out .= ':' . sprintf("%02d",$seconds);

		return $out;
	}
}

?>