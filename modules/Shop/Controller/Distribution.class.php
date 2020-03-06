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
 * Distribution class
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Provides methods for handling different distribution types
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Reto Kohli <reto.kohli@comvation.com>
 * @access      public
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class Distribution
{
    const TYPE_DELIVERY = 'delivery';  // Needs shipping, default
    const TYPE_DOWNLOAD = 'download';  // Creates a User account
    const TYPE_NONE = 'none';          // Why would you buy this, anyway?
    const TYPE_COUPON = 'coupon';      // Creates a new Coupon
    /**
     * The types of distribution
     * @static
     * @access  private
     * @var     array
     */
    private static $arrDistributionTypes = array(
        self::TYPE_DELIVERY,
        self::TYPE_DOWNLOAD,
        self::TYPE_NONE,
        self::TYPE_COUPON,
    );

    /**
     * Get an array with all distribution types
     *
     * @return array all distribution types
     */
    static function getArrDistributionTypes()
    {
        return self::$arrDistributionTypes;
    }

    /**
     * The default distribution type
     *
     * Must be set to one of the values of {@link $arrDistributionTypes}.
     * @static
     * @access  private
     * @var     string
     */
    private static $defaultDistributionType = 'delivery';


    /**
     * Verifies whether the string argument is the name of a valid
     * Distribution type.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @param   string      $string
     * @return  boolean                 True for valid distribution types,
     *                                  false otherwise
     * @static
     */
    static function isDistributionType($string)
    {
        if (array_search($string, self::$arrDistributionTypes) !== false)
            return true;
        return false;
    }


    /**
     * Returns the default distribution type as string
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @return  string                  The default distribution type
     * @static
     */
    static function getDefault()
    {
        return self::$defaultDistributionType;
    }

    /**
     * Returns a string containing the HTML code for the distribution type
     * dropdown menu options.
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @param   string  $selected   The distribution type to preselect
     * @return  string              The HTML dropdown menu options code
     * @static
     */
    static function getDistributionMenuoptions($selected='')
    {
        global $_ARRAYLANG;

        $menuoptions = ($selected == ''
            ? '<option value="" selected="selected">'.
              $_ARRAYLANG['TXT_SHOP_PLEASE_SELECT'].
              "</option>\n"
            : ''
        );
        foreach (self::$arrDistributionTypes as $type) {
            $menuoptions .=
                '<option value="'.$type.'"'.
                ($selected == $type
                    ? ' selected="selected"' : ''
                ).'>'.$_ARRAYLANG['TXT_DISTRIBUTION_'.strtoupper($type)].
                "</option>\n";
        }
        return $menuoptions;
    }

}
