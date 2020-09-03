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
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */

namespace Cx\Modules\Shop\Controller;

/**
 * The Weight class provides static conversion functions for weights.
 *
 * This class is used to properly convert weights between the format used
 * in the database (grams, integer) and a format for displaying and editing
 * in the user interface (string, with units).
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @access      public
 * @version     5.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class Weight
{
    /**
     * The weight units in an array
     * @static
     * @access  private
     * @var     array
     */
    protected static $arrUnits = array(
        'g',    //$_ARRAYLANG['TXT_WEIGHT_UNIT_GRAM'],
        'kg',   //$_ARRAYLANG['TXT_WEIGHT_UNIT_KILOGRAM'],
        't',    //$_ARRAYLANG['TXT_WEIGHT_UNIT_TONNE'],
    );


    /**
     * Return a string with the weight converted from grams to an appropriate
     * unit.
     *
     * The weight is converted, and the unit chosen as follows:
     * - weight in  [        0 ..         1'000[ -> 0 .. 999     grams,
     * - weight in  [    1'000 ..     1'000'000[ -> 0 .. 999.999 kilograms,
     * - weight in  [1'000'000 .. 1'000'000'000[ -> 0 .. 999.999 tonnes.
     * If the weight argument is outside of the valid range as specified above,
     * '' (the empty string) is returned.
     * @static
     * @access  public
     * @param   integer $grams  The weight in grams
     * @return  string          The weight in another unit, or ''
     */
    static function getWeightString($grams)
    {
        // weight too small, too big, or no integer
        if ($grams < 1 || $grams >= 1000000000 || $grams != intval($grams))
            return '0 g';
        $unitIndex = intval(log10($grams)/3);
        // unit_index shouldn't be out of range, as the weight range
        // is verified above
        if ($unitIndex < 0 || $unitIndex > count(static::$arrUnits))
            return '';
        // scale weight and append unit
        $weight = $grams/pow(1000, $unitIndex);
        $unit = static::$arrUnits[$unitIndex];
        return "$weight $unit";
    }


    /**
     * Return the weight found in the string argument converted back to grams
     *
     * Takes a string as created by {@link getWeightString()}
     * and returns the value converted to grams, with the unit
     * removed, as an integer value ready to be written to the
     * database.
     * The unit, if missing, defaults to 'g' (grams).
     * If no float value is found at the beginning of the string,
     * if it is out of range, or if the unit is set but unknown,
     * 'NULL' will be returned.
     * Note that, as weights are stored as integers, they are
     * rounded *down* to whole grams.
     *
     * @param   string  $weightString The weight with any or no unit
     * @return  integer               The weight in grams, or null on error
     */
    static function getWeight($weightString)
    {
        // store regex matches here
        $arrMatch = array();
        // numeric result value
        $grams = 0;
        if (!preg_match('/^(\d*\.?\d+)\s*(\w*)$/', $weightString, $arrMatch)) {
            return null;
        }
        $weight = $arrMatch[1];
        $unit = $arrMatch[2];
        // if the number is missing, return NULL
        if ($weight == '') {
            return null;
        }
        // if the unit is missing, default to 'g' (grams)
        if (empty($unit)) {
            $grams = intval($weight+1e-8);
        } else {
            // unit is set, look if it's known
            $unitIndex = array_search($unit, static::$arrUnits);
            // if the unit is set, but unknown, return NULL
            if ($unitIndex === false) {
                return null;
            }
            // have to correct and cast to integer here, because there are
            // precision issues for some numbers otherwise (i.e. "1.001 kg"
            // yields 1000 instead of 1001 grams)!
            $grams = intval($weight*pow(1000, $unitIndex)+1e-8);
        }
        // $grams is set to an integer now, in any case.
        // check whether the weight is too small, or too big
        if ($grams < 0 || $grams >= 1e9) {
            return null;
        }
        // return weight in grams
        return $grams;
    }

}
