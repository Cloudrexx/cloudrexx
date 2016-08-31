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
 * Compressed list
 *
 * Provides methods to operate on compressed lists that are internally
 * represented as strings.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 */

require_once(ASCMS_CORE_PATH.'/validator.inc.php');

/**
 * Compressed list
 *
 * Values are single scalar values.
 * Continuous ranges of values from X to Y (inclusive) are represented
 * as "X-Y".
 * The list is composed of values and/or ranges.  They are separated by comma.
 *
 * Definitions:
 *  Value       Any single scalar value, either numeric or string,
 *              that *MUST* consist of digits [0-9] and/or letters [A-Za-z].
 *              In particular, it *MUST NOT* contain spaces, commas (,),
 *              or dashes (-).
 *  Range       Any single value, or two values separated by a single dash (-).
 *              For two value ranges, the first value *SHOULD* be less or equal
 *              than the second, otherwise results may be either unpredictable
 *              or plain wrong.
 *  List        Any combination of values and ranges, separated by single
 *              commas (,).  Also called "compressed list".
 *
 * Notes:
 *  - Ranges may be regarded as a special case of a list with only one element.
 *    Thus, wherever a list parameter is expected, a range can be used.
 *    The reverse will generally not work.
 *  - Values may be regarded as a special case of a range with only one element.
 *    Thus, wherever a range parameter is expected, a value can be used.
 *    The reverse will generally not work.
 *  - When using string values, mind that string comparison is case sensitive.
 *    So, 'b' is contained in the range 'a-c', but not in 'A-C'.
 *  - However, mind your step when mixing cases or, even worse, numeric and
 *    non-numeric strings in one list!  E.g. the string "0-99,a-z" will
 *    happily be compressed to "0-99" as soon as you create an object from it.
 *    When comparing mixed values, "99" is considered bigger than both "a"
 *    and "z" because it's longer, so the second range is indeed part of the
 *    first to start with.
 *
 * Examples:
 *  - The array (1, 2, 3, 5) will be compressed to the list (string) "1-3,5".
 *  - The list (string) "aa-zz" contains all combinations of two lower case
 *    letters when interpreted as a list: ('aa', 'ab', 'ac', ... 'zy', 'zz').
 *    That's 2x26x26 = 1352 characters packed to just 5 (not to mention the
 *    overhead when you put the strings into an array)!
 * @todo        Add better Examples
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 */
class CompressedList
{
    /**
     * This regex limits the characters allowed in values
     *
     * See {@see range_valid()}
     */
    const RE_VALUE = '[0-9A-Za-z]';

    /**
     * The string representation of the list
     * @var   string
     */
    private $list = null;


    /**
     * Returns this list, in its compressed string representation
     * @return    string          The list as a string
     */
    function as_string()
    {
        return $this->list;
    }


    /**
     * Constructs a list from the given string or array
     * @param   mixed           $mixed      The string or array
     * @return  CompressedList              The list
     */
    function __construct($mixed)
    {
        if (!is_array($mixed)) {
            $mixed = self::_range_array($mixed);
//echo("CompressedList::__construct(): Made array: ".join(',', $mixed)."\n");
        }
        $this->list = self::array_to_string($mixed);
    }


    /**
     * Cleans up this compressed list
     *
     * Note that afterwards, the list is normalized, meaning that it is
     * ordered and recompressed to its minimal form.
     * That implies that duplicates will be removed, too.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function clean()
    {
        $arrRange = $this->range_array();
        usort($arrRange, array('self', 'cmp_range'));
//echo("CompressedList::clean(): Sorted list: ".join(',', $arrRange).")\n");
        $i = 0;
        while (1) {
//echo("CompressedList::clean(): Index $i, range {$arrRange[$i]}\n");
            if (!isset($arrRange[$i+1])) {
                break;
            }
            // See if this range is compatible with the next
            if (self::ranges_compatible($arrRange[$i], $arrRange[$i+1])) {
//echo("CompressedList::clean(): Range $arrRange[$i] extended with successor {$arrRange[$i+1]} -> ");
                $arrRange[$i+1] = self::range_extend($arrRange[$i], $arrRange[$i+1]);
                unset($arrRange[$i]);
//echo("{$arrRange[$i]} ");
//echo("(list: ".join(',', $arrRange).")\n");
            }
            ++$i;
        }
        $this->list = self::array_to_string($arrRange);
    }


    /**
     * Returns true if $value is included in this list
     * @param   integer   $value        The value to search for
     * @return  boolean                 True if found, false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function contains($value)
    {
//DBG::log("CompressedList::contains($value): Entered");
        return self::_contains($this->list, $value);
    }


    /**
     * Returns true if $value is included in the $list
     * @param   string    $list         The list string to search
     * @param   integer   $value        The value to search for
     * @return  boolean                 True if found, false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    private static function _contains($list, $value)
    {
//DBG::log("CompressedList::_contains($list, $value): Entered");
        $arrRanges = self::_range_array($list);
        foreach ($arrRanges as $range) {
            $arrRange = self::range_limits($range);
//DBG::log("CompressedList::_contains(): Range $range is from {$arrRange[0]} to {$arrRange[1]}");
            // Numeric comparison is only valid if all of the values
            // are numeric
            if (   is_numeric($value)
                && is_numeric($arrRange[0])
                && is_numeric($arrRange[1])) {
                if ($value >= $arrRange[0] && $value <= $arrRange[1]) {
//DBG::log("CompressedList::_contains(): NUMERIC $value is in range {$arrRange[0]} ... {$arrRange[1]}");
                    return true;
                }
            } else {
                // strcmp(): -1: first is smaller, 0: equal, 1: second is smaller
                if (   strlen($value) >= strlen($arrRange[0])
                    && strlen($value) <= strlen($arrRange[1])
                    && strcmp($value, $arrRange[0]) > -1     // first is not smaller
                    && strcmp($value, $arrRange[1]) < +1) {  // second is not smaller
//DBG::log("CompressedList::_contains(): STRING $value is in range {$arrRange[0]} ... {$arrRange[1]}");
                    return true;
                }
            }
        }
//DBG::log("CompressedList::_contains(): $value is NOT in $this->list");
        return false;
    }


    /**
     * Returns true if the two ranges are compatible
     *
     * Two ranges are compatible iff their union can be represented by
     * a single range.
     * In other words, the list formed by both ranges must not contain
     * any gaps.
     * @param   string  $range1   The first range
     * @param   string  $range2   The second range
     * @return  boolean           True if the two ranges are compatible
     * @static
     */
    static function ranges_compatible($range1, $range2)
    {
        list($range1_start, $range1_end) = self::range_limits($range1);
        list($range2_start, $range2_end) = self::range_limits($range2);

        // Swap the ranges if $range2's start is smaller
        if (self::cmp($range1_start, $range2_start) == +1) {
            $tmp_start = $range1_start;
            $tmp_end = $range1_end;
            $range1_start = $range2_start;
            $range1_end= $range2_end;
            $range2_start = $tmp_start;
            $range2_end = $tmp_end;
        }
        // Now $range2_start is not smaller than $range1_start!

        $range1_end_inc = $range1_end;
        ++$range1_end_inc;
        if (self::cmp($range1_end_inc, $range2_start) == -1) {
            // The two neither overlap, nor are they consecutive.
            // Return a list of two ranges
            return false;
        }
        return true;
    }


    /**
     * Returns an array with the start and end values of a range (or value)
     *
     * The range string must be of the form "X" or "X-Y".
     * For the first form, returns
     *  array(X, X)
     * and for the second form
     *  array(X, Y)
     * If the range string does not match either form, returns null.
     * @param   string    $range      The range string
     * @return  array                 The start and end value, or null
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function range_limits($range)
    {
        $match = array();
        if (!preg_match(
            '/^('.self::RE_VALUE.'+)(?:\s*\-\s*('.self::RE_VALUE.'+))?$/',
            $range, $match)) {
// DBG::log("CompressedList::range_limits(): $range did not match");
            return null;
        }
        if (isset($match[2])) {
// DBG::log("CompressedList::range_limits(): $range matched {$match[1]}, {$match[2]}");
            return array($match[1], $match[2]);
        }
// DBG::log("CompressedList::range_limits(): $range matched {$match[1]}");
        return array($match[1], $match[1]);
    }


    /* UNUSED
     * Returns the range that contains the given value, if any
     *
     * If there is no such range (or value), returns null.
     * @param   string  $value      The value
     * @return  string              The containing range on success,
     *                              null otherwise
    function range_contains($value)
    {
        foreach (self::_range_array($this->list) as $range) {
            if (self::_contains($range, $value)) {
                return $range;
            }
        }
        return null;
    }
     */


    /**
     * Removes the range from the list
     *
     * If the range is not present at all, no change is made.
     * If the range only partially overlaps, the overlapping part is removed.
     * if the range overlaps with more than one range (or value), all
     * of them are reduced or removed as necessary.
     * @param   string          $value    The value
     * @return  CompressedList            The modified list
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function range_remove($range)
    {
        $arrRange = self::_range_array($this->list);
        $this->list = '';
        foreach ($arrRange as $_range) {
            if (self::ranges_compatible($_range, $range)) {
                $_range = self::range_reduce($_range, $range);
            }
            if ($_range) {
                $this->list .=
                    ($this->list ? ',' : '').$_range;
            }
        }
        return $this->clean();
    }


    /**
     * Returns the given range by the range given in $reduce
     *
     * This literally cuts the range in $reduce out of the given $range
     * and returns the remainder, which may consist of two ranges instead!
     * If the two ranges do not overlap at all, the original range is returned
     * without change.
     * If $reduce contains the entire $range, returns the empty string.
     * If only a single value is left over, that is returned.
     * @param   string  $range    The original range
     * @param   string  $reduce   The range to remove
     * @return  string            The remainder of the original range
     */
    private static function range_reduce($range, $reduce)
    {
        list($range_start, $range_end) = self::range_limits($range);
        list($reduce_start, $reduce_end) = self::range_limits($reduce);
//echo("CompressedList::range_reduce($range, $reduce): range $range_start..$range_end, reduce $reduce_start..$reduce_end\n");
        if (   self::cmp($range_start, $reduce_end) == +1
            || self::cmp($range_end, $reduce_start) == -1) {
            // Range start is bigger than reduce end
            // or range end is smaller than reduce start, so:
            // No overlap
//echo("CompressedList::range_reduce($range, $reduce): No overlap -> /$range/\n");
            return $range;
        }
        if (   self::cmp($range_start, $reduce_start) > -1
            && self::cmp($range_end,   $reduce_end)   < +1) {
            // Reduce completely covers range:
            // Drop the range
//echo("CompressedList::range_reduce($range, $reduce): Complete overlap -> //\n");
            return '';
        }
        if (   self::cmp($range_start, $reduce_start) >  -1    // Rs >= Ds
            && self::cmp($range_start, $reduce_end)   <  +1    // Rs <= De
            && self::cmp($range_end,   $reduce_end)   == +1) { // Re >  De
            // Reduce overlaps with the start of range:
            // Cut the start of range
            ++$reduce_end;
            $range_start = $reduce_end;
//echo("CompressedList::range_reduce($range, $reduce): Start overlap -> /$range_start-$range_end/\n");
            return "$range_start-$range_end";
        }
        if (   self::cmp($range_start, $reduce_start) == -1    // Rs <  Ds
            && self::cmp($range_end,   $reduce_start) >  -1    // Re >= Ds
            && self::cmp($range_end,   $reduce_end)   <  +1) { // Re <= De
            // Reduce overlaps with the end of range:
            // Cut the end of range
            self::dec($reduce_start);
            $range_end = $reduce_start;
//echo("CompressedList::range_reduce($range, $reduce): End overlap -> /$range_start-$range_end/\n");
            return "$range_start-$range_end";
        }
        if (   self::cmp($range_start, $reduce_start) == -1
            && self::cmp($range_end,   $reduce_end)   == +1) {
            // Reduce overlaps with the inner of range:
            // Split the range
            self::dec($reduce_start);
            ++$reduce_end;
//echo("CompressedList::range_reduce($range, $reduce): Inner overlap -> /$range_start-$reduce_start,$reduce_end-$range_end/\n");
            return "$range_start-$reduce_start,$reduce_end-$range_end";
        }
        return "CompressedList::range_reduce(): ERROR -- unexpected outcome...";
    }


    /**
     * Returns the union of the given range with the range given in $reduce
     *
     * The ranges may be provided in any order.
     * If the union of the two ranges contains no gap, returns a single
     * range that contains the values of both.
     * Otherwise, returns a string with the two ranges concatenated to a list.
     * @param   string  $range    The original range
     * @param   string  $extend   The range to extend with
     * @return  string            The union of both ranges
     */
    private static function range_extend($range, $extend)
    {
        list($range_start, $range_end) = self::range_limits($range);
        list($extend_start, $extend_end) = self::range_limits($extend);

        // Swap the ranges if the $extend's start is smaller
        if (self::cmp($range_start, $extend_start) == +1) {
            $tmp_start = $range_start;
            $tmp_end = $range_end;
            $range_start = $extend_start;
            $range_end= $extend_end;
            $extend_start = $tmp_start;
            $extend_end = $tmp_end;
        }
        // Now $extend_start is not smaller than $range_start!

        if (self::ranges_compatible($range, $extend)) {
            // If they either overlap or are consecutive, simply use
            // $range_start (which is not the bigger one of the two starts),
            // and the bigger of both end values to form the union
            if (self::cmp($range_end, $extend_end) == -1) {
                // $extend_end is bigger
                $range_end = $extend_end;
            }
            if ($range_start === $range_end) return $range_start;
            return "$range_start-$range_end";
        }

        // The two neither overlap, nor are they consecutive.
        // Return a list of two ranges
        return "$range_start-$range_end,$extend_start-$extend_end";
    }


    /**
     * Appends the value to the list
     *
     * If the same value is already present, no change is made.
     * @param   string    $value    The value
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function append($value)
    {
        if ($this->contains($value)) return;
        $this->list .= ($this->list ? ',' : '').$value;
    }


    /**
     * Prepends the value to the list
     *
     * If the same value is already present, no change is made.
     * @param   string    $value    The value
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function prepend($value)
    {
        if ($this->contains($value)) return;
        $this->list = $value.($this->list ? ',' : '').$this->list;
    }


    /**
     * Adds the value to the list
     *
     * If the same value is already present, no change is made.
     * Otherwise, this method calls {@see clean()}, so mind that the order
     * of your list and even some ranges in it may be modified.
     * Note that this is a optimized version of {@see range_extend()}
     * for simple values.
     * @param   string    $value    The value
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function add($value)
    {
        if ($this->contains($value)) {
//echo("CompressedList::add($value): $this->list contains it\n");
            return;
        }
        $arrRange = $this->range_array();
//        usort($arrRange, array('self', 'cmp_range'));
        $offset = 0;
        foreach ($arrRange as &$range) {
            if (self::ranges_compatible($range, $value)) {
                $range = self::range_extend($range, $value);
//echo("CompressedList::add($value): At offset $offset, with $range, made ".self::array_to_string($arrRange)."\n");
                $offset = null;
                break;
            }
            list($min) = self::range_limits($range);
            if (self::cmp($value, $min) < 0) {
                // Insert the value before the current range
                array_splice($arrRange, $offset, 0, array($value));
//echo("CompressedList::add($value): At offset $offset, before $range, made ".self::array_to_string($arrRange)."\n");
                $offset = null;
                break;
            }
            ++$offset;
        }
//echo("CompressedList::add($value): Last max $max\n");
        if (isset($offset)) {
//echo("CompressedList::add($value): After $max\n");
            $arrRange[] = $value;
        }
        $this->list = self::array_to_string($arrRange);
//echo("CompressedList::add($value): New list $this->list\n");
        $this->clean();
    }


    /**
     * Returns the ranges (and single values) in this list as an array
     *
     * Note that spaces and surplus commas are stripped.
     * @return  array               The value array
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    function range_array()
    {
        return self::_range_array($this->list);
    }


    /**
     * Returns the ranges (and single values) in the given list string
     * as an array
     *
     * Note that spaces and surplus commas are stripped.
     * @param   string    $list     The list string
     * @return  array               The value array
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    private static function _range_array($list)
    {
        return preg_split('/\s*,+\s*/', $list);
    }


    /**
     * Returns the ranges in an array as a string representing the
     * compressed list
     *
     * This is usually only called by {@see __construct()}.
     * @param   array     $array    The value array
     * @return  string              The string representing the list
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @static
     */
    static function array_to_string($array)
    {
        foreach ($array as $index => $range) {
            if (!self::range_valid($range)) {
//echo("CompressedList::array_to_string(): Invalid range $range\n");
                unset($array[$index]);
            }
        }
        return join(',', $array);
    }


    /**
     * Returns true if the given $range is valid
     *
     * Valid ranges *MUST* have an end value that is not smaller than
     * the start value.
     * @param   string    $range    The possible range
     * @return  boolean             True if the range is valid, false otherwise
     */
    static function range_valid($range)
    {
        list($range_start, $range_end) = self::range_limits($range);
//echo("CompressedList::range_valid($range): Range [$range_start..$range_end]\n");
        if (!(isset($range_start) && isset($range_end))) return false;
        $result = (self::cmp($range_start, $range_end) < 1);
//echo("CompressedList::range_valid($range): Range $range is ".($result ? '' : 'IN')."VALID\n");
        return $result;
    }


    /**
     * Returns the result of comparing the two values
     *
     * If both values are numeric, they are compared as numbers.
     * Otherwise, the shorter string is always considered smaller.
     * Same length strings are compared by strcmp().
     * The result is -1 if the first is smaller, +1 if the first is bigger,
     * or 0 if they are equal.
     * @param   string    $value1   A numeric or string value
     * @param   string    $value2   A numeric or string value
     * @return  integer             -1, 0, or 1
     */
    private static function cmp($value1, $value2)
    {
        // Numeric comparison is only valid if both of the values are numeric
        if (   is_numeric($value1)
            && is_numeric($value2)) {
            if ($value1 < $value2) return -1;
            if ($value1 > $value2) return +1;
            return 0;
        }
        // Strings
        // The shorter string is considered to be smaller!
        if (strlen($value1) < strlen($value2)) return -1;
        if (strlen($value1) > strlen($value2)) return +1;
        // strcmp(): -1: first is smaller, 0: equal, +1: second is smaller
        return strcmp($value1, $value2);
    }


    /**
     * Returns the result of comparing the two ranges
     *
     * Order is determined by the start values in each range.  If they are
     * equal, by the end values.
     * Values are compared using {@see cmp()}.
     * The result is -1 if the first is smaller, +1 if the first is bigger,
     * or 0 if they are equal.
     * @param   string    $range1   The first range
     * @param   string    $range2   The second range
     * @return  integer             -1, 0, or 1
     */
    private static function cmp_range($range1, $range2)
    {
        list($range1_start, $range1_end) = self::range_limits($range1);
        list($range2_start, $range2_end) = self::range_limits($range2);
        $result = self::cmp($range1_start, $range2_start);
        if ($result) return $result;
        return self::cmp($range1_end, $range2_end);
    }


    /**
     * Decrement the $value by one
     *
     * Works for strings as well as integer numbers.  Works around the
     * disfunct "--" operator in PHP.
     * Note that this will leave the value unchanged for empty values like
     * null, false, or the empty string.
     * Only strings matching /[a-zA-Z0-9]+/ will work properly, other
     * characters will cause unexpected results!
     * Any single digit will never be changed into a letter or vice versa.
     * The case of letters will never change.
     * Decrementing 0 (zero) results in -1, as expected.
     * Decrementing either 'a' or 'A' results in the empty string.
     * @param   string    $value    A numeric or string value, by reference
     * @private
     */
    private static function dec(&$value)
    {
        if (is_numeric($value)) {
            --$value;
            return;
        }
        if (empty($value)) {
            return;
        }
        // Process characters in strings from right to left until there
        // is no more "underrun"
        $i = null;
        for ($i = strlen($value)-1; $i >= 0; --$i) {
            $ord = ord($value[$i]);
//echo("ord: $ord\n");
            if ($ord == 48) { // 0, zero
                $value{$i} = '9';
            } elseif ($ord == 65) { // A
                $value{$i} = 'Z';
            } elseif ($ord == 97) { // a
                $value{$i} = 'z';
            } else {
                // No underrun
                $value[$i] = chr($ord-1);
                break;
            }
            // Underrun on first character: cut it
            if ($i == 0) {
                $value = substr($value, 1);
                // Last pass anyway, no break necessary
            }
        }
    }


    static function test()
    {
/* OK
        $objList = new CompressedList(array('2', '3', '5-7', '9'));
        echo("List: ".$objList->as_string()."\n");
        for ($i = 3; $i <= 5; ++$i) {
            echo("Contains $i ? ".($objList->contains($i) ? 'yes' : 'no')."\n");
        }
        $objList->append(10);
        echo("Appended 10: ".$objList->as_string()."\n");
        $objList->prepend(1);
        echo("Prepended 1: ".$objList->as_string()."\n");
*/
/* OK
        $arrTest = array(
            // numeric
            0, 1,
            // not numeric
            "'a'", "'b'",
            "'aa'", "'bb'",
        );
        foreach ($arrTest as $str1) {
            foreach ($arrTest as $str2) {
                $eval = "return self::cmp($str1, $str2);";
                echo($eval.': '.eval($eval)."\n");
            }
        }
*/
/* OK
        $objList = new CompressedList(array('3', '5-6', '8'));
        echo("List: ".$objList->as_string()."\n");
        for ($i = 2; $i <= 9; ++$i) {
            $objList->add($i);
            echo("Added $i: ".$objList->as_string()."\n");
        }
*/
/* OK
        $objList = new CompressedList(array('c', 'e-f', 'h'));
        $arrLetter = array('b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', );
        $arrKeys = array_rand($arrLetter, count($arrLetter));
        echo("List: ".$objList->as_string()."\n");
        foreach ($arrKeys as $i) {
            $objList->add($arrLetter[$i]);
            echo("Added {$arrLetter[$i]}: ".$objList->as_string()."\n");
        }
*/
/* OK
        $objList = new CompressedList(array('a-b', 'd', 'f-g'));
        echo("List: ".$objList->as_string()."\n");
        $arrChar = array('b', 'c', 'd', 'e', 'h', 'aa', );
        foreach ($arrChar as $char) {
            $objList->add($char);
            echo("Added $char: ".$objList->as_string()."\n");
        }
*/
/* OK
        $arrString = array('', '0', '00', 'a', 'aa', 'ba', 'A', 'AA', 'BA', );
        foreach ($arrString as $string) {
            echo("dec($string) -> ");
            self::dec($string);
            echo("/$string/\n");
        }
*/
/* OK
        $range = '3-5';
        $arrReduce = array(
          // no overlap
            '2',
            '1-2',
            '2-2',
            '6',
            '6-6',
            '6-7',
          // start overlap
            '1-4',
            '2-3',
            '3',
            '3-3',
            '3-4',
          // inner overlap
            '4',
            '4-4',
          // end overlap
            '4-5',
            '4-7',
            '5',
            '5-5',
            '5-7',
          // complete overlap
            '1-6',
            '2-5',
            '3-5',
            '3-6',
        );
        foreach ($arrReduce as $reduce) {
            echo("range_reduce($range, $reduce) -> ".self::range_reduce($range, $reduce)."\n");
        }
*/
/* OK
        $objList = new CompressedList('10-90');
        $arrRange = array(
            '20-25',
            '76-76',
            '39',
            '5-16',
            '95-99',
        );
        foreach ($arrRange as $range) {
            echo($objList->as_string()." - $range -> ");
            $objList->range_remove($range);
            echo("$objList->list\n");
        }
        $objList = new CompressedList(array('aa-yy'));
        $arrRange = array(
            'dd-ee',
            'ff-ff',
            'ii',
            'a-bb',
            'xx-zz',
        );
        foreach ($arrRange as $range) {
            echo($objList->as_string()." - $range -> ");
            $objList->range_remove($range);
            echo("$objList->list\n");
        }
*/
/* OK
        $objList = new CompressedList('50-60');
        $arrRange = array(
            '20-25',
            '76-76',
            '39',
            '40-49',
            '40-55',
            '60-70',
            '61-70',
            '62-70',
        );
        foreach ($arrRange as $range) {
            echo($objList->as_string()." + $range -> ");
            $result = self::range_extend($objList->list, $range);
            echo("$result\n");
        }
*/
/* OK
        $objList = new CompressedList(
            '1,2,3-4,5-5,6,7-8,7-9,6-10,20-21,22-22,24,25-26,23,15'
        );
        echo("Original list: ".$objList->as_string()."\n");
        $objList->clean();
        echo("Cleaned list: ".$objList->as_string()."\n");
*/
/* OK
        $objList = new CompressedList(
            'hh-oo,dd-gg,xx-yy,22-66,66-33,zz-bb,nn-rr'
        );
        echo("Original list: ".$objList->as_string()."\n");
        $objList->clean();
        echo("Cleaned list: ".$objList->as_string()."\n");
*/
/* OK
        $objList = new CompressedList(
            ',4,,6-9,,g-a,.,-,[,],+,",*,%,&,/,(,'
        );
        echo("Original list: ".$objList->as_string()."\n");
        $objList->clean();
        echo("Cleaned list: ".$objList->as_string()."\n");
*/
/* OK
        $data = file_get_contents(__FILE__);
        $data = preg_split('/[^-\w]+/', $data, null, PREG_SPLIT_NO_EMPTY);
        $data = array_unique($data);
        $data = join(',', $data); //die($data);
        $objList = new CompressedList($data);
        echo("Original list: ".$objList->as_string()."\n");
        $objList->clean();
        echo("Cleaned list: ".$objList->as_string()."\n");
*/
/* OK
//        $objList = new CompressedList('23A52C1032,72A9CA1104,093376DCAB,04247C1EA6,051B6F71BB,C2772B54E4,D834FB57CD,D4DC983987,4178EB8470,D5E92451B0,805751BA55,1F8452E85A,F3627C739E,306FFF3417,E15AC349F9,E820C11AA7,1A5F7D383B,5B8532B2DB,390A739B47,5C23144BFC,7FACC95280,0221B7AD16,C81C6C5B2D,FA8D059E67,35DAEA0297,429D6A3538,7F2C9C2938,EAC12EE037,CF4FDFEBED,F4EEBF7FBE,27E5F40B20,0A84DA5FDA,8BBBB7E35E,E53C15AAE9,A866DC1AB7,39A4F0BE6A,68B2A27FE0,EEBA2926CB,458F93C12E,5A65BFAF8E,3569335E5F,F6545C399D,9F2AAC0AA6,57238E157F');
        $objList = new CompressedList('47AB5D6CA5,2276103C61,90465E8620,A922D511B6,61003D4CAC,F630CD9997,AF82456F23,09A4516635,22A047AD5F,A745F7ACC9,9366E8FA03,BCD4BC82BC,A8D1E60E83,7FD469CEF6,9D9F246119,61F533F7E7,8D3188E8F3,0CC0503C87,9C5DFE1E72,0F0429E04B,5C7A74242F,2DDB9B2EFD,97C4299187,5963719C49,BA912BFC7B,79FDACDAF1,BEC07FA42C,E1E8D8E003,C32527E712,77902B3534,826F449C0F,03EDA753CF,149E6D05F9,0A6A9AF003,FA352E3B52,CA71E83A61,5614E0A243,A5CE34C173,F673426A29,5BEB71FD67,AC34297ED7,E6A99F0D3F,F08D44CD51,7FEAA35DE1,BF741A3A1F,A9B5EB55E2,2C543E4066,98BECC6270,280B88C0F7,A99E48C8E4,0B1C501E78,60F6419831,402CF3733F,3AFD1B5CB9,0FD98F44EE,E151C6AADC,9F900B9082,5DF080737A,4A360410A9,0C5239FF71,0D43B5C019,00DAB79BC2,FCF9DBA08A,CC7217A99D,AFF456DC8E,EAC40F771C,B7AD5C98C8,7198C4B44C,2D294CB96E,8D53E938B3,A19978115D,FE0663F63F,9A9E9B443D,05A135949B,915C657340,DF80A7081F,5B3D91593F,D3AD59EBD1,DB6B6E7828,C729188844,61714BB976,BE71D05B35,F51D74B6AF,ECEB7D3DB7,DCB45053CA,5EF53FC973,8DCCCCCEDD,9F0C92A741,AB14EF1DFA,71C87AB0ED,2DC0E15716,DCA873FE39,7F8AD8A040,85FAF2E7FE,9758DD321,3DE273169B,2A4441B567,6EB7BBFA2A,945B65D445,E781946087,FBAC03C76F,6C4733F5EC,C7E719D3EF,66398EB714,5A2C4D15FE,DAA817520A,F06B71E480,4C4F21BFB1,5CD8E5E37F,7D64747642,56F2D42695,2F89E77931,7B5776265C,32F2EC8C69,9A9A81CAAD,3D530323C0,CEFB795C45,61E0523999,33AE028D7F,C44D64FA87,204223B7E5,F7E80A772E,0E7057EFA8,DDBD151F48,02C45554BD,8D27514FDA,2572B30460,561AC2DBFC,EE241589E5,1C9035E303,9D86F29AAA,D56FA36053,B2D424B6F2,CEF2EAB9FE,44CAA3E097,B5CD5B7C70,6D58A235E4,D6FE463B26,BC312CDC2E,1D1DE7E559,941C5C4ED1,4746B114C0,726958E92C,44E46CF4F9,4B2731CD41,89008F5A28,E4C57BBACB,A0BAFD5D2E,CFF46D2091,21FA971425,76CDEB95A4,251429BF13,73225B020C,07032D3944,4A56C67E02,5CF009406D,399B9F9E0C,CD6831DB64,3369B3A623,54E0EEB069,993C27BE8A,70B56EC238,B207F41B97,95147B61AD,51E84436E3,A0F495AA1F,E410A2DB31,31FA6575F5,3C22E556C5,27CCBF64B9,43462D0A91,16D6264940,6A951CCBB0,A955F460ED,73CC139B9A,662AB6B9F1,882308C612,DD5342E5E7,F3C30B9A38,8C1A2B7EF3,C44AC7E75C,01FEF421D6,C39D1F8967,C239D2C5CA,1B69994293,E0CE4AAF1A,EFCFB3B95C,6A802B942E,B92EDDDE1E,BA544FDEBE,C2B944C535,A46A4977B3');
//        $objList = new CompressedList('8437D02DE7,D2FD5FC881,D1D578D126,6E2218578A,94EC1640A0,33FEB337D1,F2EDF3BA9E,E61C75836E,5D9665BF7F,7ACD30C471,A1374A181B,1CA6A38656,B87E08063D,4C0046D6A8,A26FD2827F,46F9DE6E4A,BC84742ABC,5E1D2E051A,3E7E6810CD,EDAA86E41D,F7E75FA4B4,C5D5A4FAF3,96EEE28E62,F80E203B62,BDEA7A04DD,FAD921AEA7,E1A66A8891,92366A375A,235E836306,E2D51FCF36,DCAF0ABD15,06FE5198B2,FB117CD98A,D717ACAEA6,40E1E075E2,CD0A88E713,8CF092B08F,85D73F8D4D,48BCDF099F,6B9583ECCE,99F65CCF37,E4C0C3DD4D,41C38ACBEC,98414F90A9,B5449DD463,103552AD3A,8B24C92DB3,B4CADA924C,F6C8451A59,B38D7E87C2,C0D5DAFB03,26359939AD,D6A8D018E8,0D151F6033,C224B70B18,CE42447A99,15E0C07D04,625EA47AED,06D1ED7A60,D4FA8BC67D,474767C8E4,4E91119B5D,7B9F9EAC51,C894FB9DC8,AD7F363F65,039841D351,72ED796163,7623CA0529,B3FDC438D1,0B8FE61B30,5A0C349057,111B9A9EDF,13E9D9DFD7,BC4F899543,7636F9E5FA,2EC72FE5D2,B1AB5CDB69,5488398E2A,E29BE47872,532DAFFC9D,23F45A8A78,C1C0A02640,AC007682B4,E9519EE308,0FAF696489,0638D29B22,720BB218B2,53538E542F,E89C74E30E,F7E14B47A1,AF9F83C536,B1374EB0C2,CE6643D96A');
        echo("Original list: ".$objList->as_string()."\n");
        $objList->clean();
        echo("Cleaned list: ".$objList->as_string()."\n");
*/
    }
}

/* TEST */
CompressedList::test();

?>
