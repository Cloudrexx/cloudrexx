<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2015
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Cloudrexx" is a registered trademark of Cloudrexx AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * Date and time helper functions
 *
 * Add more methods and formats as needed.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @since       3.0.0
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */

/**
 * Date and time helper functions
 *
 * Add more methods and formats as needed.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @since       3.0.0
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class DateTimeTools
{
    /**
     * Months of the year in the current frontend language
     *
     * See {@see init()}
     * @var   array
     */
    private static $arrMoy = null;

    /**
     * Days of the week in the current frontend language
     *
     * See {@see init()}
     * @var   array
     */
    private static $arrDow = null;


    /**
     * Initializes internal data, called on demand by the methods that
     * depend on it
     */
    static function init()
    {
        global $_CORELANG;

        if (!self::$arrMoy) {
            self::$arrMoy = explode(',', $_CORELANG['TXT_CORE_MONTH_ARRAY']);
            unset(self::$arrMoy[0]);
        }
        if (!self::$arrDow) {
            self::$arrDow = explode(',', $_CORELANG['TXT_CORE_DAY_ARRAY']);
        }
    }


    /**
     * Registers the JavaScript code for jQueryUi.Datepicker
     *
     * Also activates jQueryUi and tries to load the current language and use
     * that as the default.
     * Add element specific defaults and code in your method.
     */
    static function addDatepickerJs()
    {
        static $language_code = null;

        // Only run once
        if ($language_code) return;
        JS::activate('jqueryui');
        $language_code = FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID);
//DBG::log("Language ID ".FRONTEND_LANG_ID.", code $language_code");
        // Must load timepicker as well, because the region file accesses it
        JS::registerJS(
            'lib/javascript/jquery/ui/jquery-ui-timepicker-addon.js');
// TODO: Add more languages to the i18n folder!
        JS::registerJS(
            'lib/javascript/jquery/ui/i18n/'.
// TODO: Append the locale code ("-GB", "-US") as well!
            'jquery.ui.datepicker-'.$language_code.'.js');
        JS::registerCode('
cx.jQuery(function() {
  cx.jQuery.datepicker.setDefaults(cx.jQuery.datepicker.regional["'.$language_code.'"]);
});
');
    }


    /**
     * Returns the date for the given timestamp in a format like
     * "32. Januamber 2010"
     *
     * Set the core language variable TXT_CORE_DATE_D_MMM_YYYY
     * accordingly for other locales.
     * $time defaults to the current time if unset.
     * @param   integer   $time   The optional timestamp
     * @return  string            The formatted date
     */
    static function date_D_MMM_YYY($time=null)
    {
        global $_CORELANG;

        if (!self::$arrMoy) self::init();
        if (!isset($time)) $time = time();
        return sprintf(
            $_CORELANG['TXT_CORE_DATE_D_MMM_YYYY'],
            date('d', $time),
            self::$arrMoy[date('n', $time)],
            date('Y', $time));
    }


    /**
     * Returns the date for the given timestamp in a format like
     * "Sondertag, 32. Januamber 2010"
     *
     * Calls {@see date_D_MMM_YYY()} to format the date and preprends
     * the weekday name.
     * Set the core language variable TXT_CORE_DATE_WWW_DATE
     * accordingly for other locales.
     * $time defaults to the current time if unset.
     * @param   integer   $time   The optional timestamp
     * @return  string            The formatted date
     */
    static function date_WWW_D_MMM_YYYY($time=null)
    {
        global $_CORELANG;

        if (!self::$arrDow) self::init();
        if (!isset($time)) $time = time();
        return sprintf(
            $_CORELANG['TXT_CORE_DATE_WWW_DATE'],
            self::$arrDow[date('w', $time)],
            self::date_D_MMM_YYY($time));
    }


    /**
     * Returns the date for the given timestamp in a format like
     * "Januamber 2010"
     *
     * $time defaults to the current time if unset.
     * @param   integer   $time   The optional timestamp
     * @return  string            The formatted date
     */
    static function date_MMM_YYYY($time=null)
    {
        global $_CORELANG;


        if (!self::$arrMoy) self::init();
        if (!isset($time)) $time = time();
        return sprintf(
            $_CORELANG['TXT_CORE_DATE_MMM_YYYY'],
            self::$arrMoy[date('n', $time)],
            date('Y', $time));
    }


    /**
     * Returns an array of the names of the months of the year
     * in the current frontend language
     *
     * Indexed by the ordinal value, one-based
     * @return  array             The month array
     */
    static function month_names()
    {
        if (!self::$arrMoy) self::init();
        return self::$arrMoy;
    }


    /**
     * Returns an array of day numbers formatted according to locale and
     * indexed by corresponding integer values
     *
     * The number of entries will always be 31 ([1..31]).
     * @return  array               The array of day numbers
     */
    static function day_numbers()
    {
        global $_CORELANG;
        static $arrDay = null;

        if (!$arrDay) {
            $arrDay = array();
            foreach (range(1, 31) as $day) {
                $arrDay[$day] =
                    sprintf($_CORELANG['TXT_CORE_DATE_D'], $day);
            }
        }
        return $arrDay;
    }


    /**
     * Returns a regular expression string matching the given
     * {@see date()} format string
     *
     * Note that only the following options are implemented:
     *  djmnYyaABgGhHis
     * Any other character is considered to be a separator, and is thus
     * quoted, if necessary, then inserted into the regular expression as-is.
     * Notes:
     *  - No delimiter is added to the regular expression.  Do that yourself.
     *  - The regex produced does match leading zeros if present in any case.
     * @param   string  $format         The date format
     * @param   string  $delimiter      The optional regex delimiter
     * @return  string                  The regex matching the format
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function getRegexForDateFormat($format, $delimiter='/')
    {
        $re = '';
        $chars = preg_split('//', $format, null, PREG_SPLIT_NO_EMPTY);
//DBG::log("Chars: ".var_export($chars, true));
        foreach ($chars as $char) {
            switch ($char) {
                //d     Day of the month, 2 digits with leading zeros     01 to 31
                case 'd':
                    $re .= '(?:[0-2][0-9]|3[01])';
                    break;
                //j     Day of the month without leading zeros     1 to 31
                // Note: Matches the optional leading zero anyway.
                case 'j':
                    $re .= '(?:[0-2]?[0-9]|3[01])';
                    break;
                //m     Numeric representation of a month, with leading zeros     01 through 12
                case 'm':
                    $re .= '(?:0[1-9]|1[012])';
                    break;
                //n     Numeric representation of a month, without leading zeros     1 through 12
                // Note: Matches the optional leading zero anyway.
                case 'n':
                    $re .= '(?:0?[1-9]|1[012])';
                    break;
                //Y     A full numeric representation of a year, 4 digits     Examples: 1999 or 2003
                // Note: Does not limit the age in any way.  "0000" is a
                // perfectly valid year according to the specifications.
                case 'Y':
                    $re .= '\d\d\d\d';
                    break;
                //y     A two digit representation of a year     Examples: 99 or 03
                // Note: Proper interpretation of the century is up to you.
                case 'y':
                    $re .= '\d\d';
                    break;
                //a     Lowercase Ante meridiem and Post meridiem     am or pm
                // Note: If you use the /i switch, this is equal to 'A'.
                case 'a':
                    $re .= '[ap]m';
                    break;
                //A     Uppercase Ante meridiem and Post meridiem     AM or PM
                // Note: If you use the /i switch, this is equal to 'a'.
                case 'A':
                    $re .= '[AP]M';
                    break;
                //B     Swatch Internet time     000 through 999
                case 'B':
                    $re .= '\d\d\d';
                    break;
                //g     12-hour format of an hour without leading zeros     1 through 12
                // Note: Matches the optional leading zero anyway.
                case 'g':
                    $re .= '(?:0?[1-9]|1[012])';
                    break;
                //h     12-hour format of an hour with leading zeros     01 through 12
                case 'h':
                    $re .= '(?:0[1-9]|1[012])';
                    break;
                //G     24-hour format of an hour without leading zeros     0 through 23
                // Note: Matches the optional leading zero anyway.
                case 'G':
                    $re .= '(?:0?[1-9]|1\d|2[0-3])';
                    break;
                //H     24-hour format of an hour with leading zeros     00 through 23
                case 'H':
                    $re .= '(?:0[1-9]|1\d|2[0-3])';
                    break;
                //i     Minutes with leading zeros     00 to 59
                case 'i':
                    $re .= '[0-5]\d';
                    break;
                //s     Seconds, with leading zeros     00 through 59
                case 's':
                    $re .= '[0-5]\d';
                    break;

                // TODO: Extend here

                // Anything else is considered a separator
                default:
                    $re .= preg_quote($char, $delimiter);

// Not Implemented -- mind your step:
//Day
//D     A textual representation of a day, three letters     Mon through Sun
//l (lowercase 'L')     A full textual representation of the day of the week     Sunday through Saturday
//N     ISO-8601 numeric representation of the day of the week (added in PHP 5.1.0)     1 (for Monday) through 7 (for Sunday)
//S     English ordinal suffix for the day of the month, 2 characters     st, nd, rd or th. Works well with j
//w     Numeric representation of the day of the week     0 (for Sunday) through 6 (for Saturday)
//z     The day of the year (starting from 0)     0 through 365
//Week     ---     ---
//W     ISO-8601 week number of year, weeks starting on Monday (added in PHP 4.1.0)     Example: 42 (the 42nd week in the year)
//Month     ---     ---
//F     A full textual representation of a month, such as January or March     January through December
//M     A short textual representation of a month, three letters     Jan through Dec
//t     Number of days in the given month     28 through 31
//Year     ---     ---
//L     Whether it's a leap year     1 if it is a leap year, 0 otherwise.
//o     ISO-8601 year number. This has the same value as Y, except that if the ISO week number (W) belongs to the previous or next year, that year is used instead. (added in PHP 5.1.0)     Examples: 1999 or 2003
//Time     ---     ---
//u     Microseconds (added in PHP 5.2.2)     Example: 654321
//Timezone     ---     ---
//e     Timezone identifier (added in PHP 5.1.0)     Examples: UTC, GMT, Atlantic/Azores
//I (capital i)     Whether or not the date is in daylight saving time     1 if Daylight Saving Time, 0 otherwise.
//O     Difference to Greenwich time (GMT) in hours     Example: +0200
//P     Difference to Greenwich time (GMT) with colon between hours and minutes (added in PHP 5.1.3)     Example: +02:00
//T     Timezone abbreviation     Examples: EST, MDT ...
//Z     Timezone offset in seconds. The offset for timezones west of UTC is always negative, and for those east of UTC is always positive.     -43200 through 50400
//Full Date/Time     ---     ---
//c     ISO 8601 date (added in PHP 5)     2004-02-12T15:19:21+00:00
//r     » RFC 2822 formatted date     Example: Thu, 21 Dec 2000 16:01:07 +0200
//U     Seconds since the Unix Epoch (January 1 1970 00:00:00 GMT)     See also time()
            }
        }
//DBG::log("Made date RE for format /$format/: /$re/");
        return $re;
    }


    /**
     * Return the string representation of a DateInterval object
     *
     * @param   $dateInterval   DateInterval    The DateInterval object to fetch the string representation from.
     * @return  string  An interval specification that can be interpreted by DateInterval
     */
    public static function getDateIntervalAsString($dateInterval) {
        $sReturn = 'P';

        if ($dateInterval->y) {
            $sReturn .= $dateInterval->y . 'Y';
        }

        if ($dateInterval->m) {
            $sReturn .= $dateInterval->m . 'M';
        }

        if ($dateInterval->d) {
            $sReturn .= $dateInterval->d . 'D';
        }

        if ($dateInterval->h || $dateInterval->i || $dateInterval->s) {
            $sReturn .= 'T';

            if ($dateInterval->h) {
                $sReturn .= $dateInterval->h . 'H';
            }

            if ($dateInterval->i) {
                $sReturn .= $dateInterval->i . 'M';
            }

            if ($dateInterval->s) {
                $sReturn .= $dateInterval->s . 'S';
            }
        }

        return $sReturn;
    }

    /**
     * Check the given value is in valid date format(d.M.Y H:i:s) or not
     *
     * @param string $value input value
     *
     * @return boolean true|false
     */
    public function isValidDate($value) {
        if (empty($value)) {
            return false;
        }
        return preg_match('/\d{2}\.\D{3}.\d{4}\s\d{2}:\d{2}:\d{2}/i', $value);
    }

}
