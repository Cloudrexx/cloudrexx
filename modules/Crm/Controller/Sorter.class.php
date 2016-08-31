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
 * Sorter Class CRM
 *
 * PHP version 5.3 or >
 *
 * @category   Sorter
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     ss4ugroup <ss4ugroup@softsolutions4u.com>
 * @license    BSD Licence
 * @version    1.0.0
 * @link       www.cloudrexx.com
 */

namespace Cx\Modules\Crm\Controller;

/**
 * Sorter Class CRM
 *
 * PHP version 5.3 or >
 *
 * @category   Sorter
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     ss4ugroup <ss4ugroup@softsolutions4u.com>
 * @license    BSD Licence
 * @version    1.0.0
 * @link       www.cloudrexx.com
 */

class Sorter
{
    /**
     * Sort fields
     *
     * @access private
     * @var string
     */
    var $sort_fields;

    /**
     * Backwards
     *
     * @access private
     * @var boolean
     */
    var $backwards = false;

    /**
     * Numeric
     *
     * @access private
     * @var boolean
     */
    var $numeric = false;

    /**
     * sort function
     *
     * @return array
     */
    function sort()
    {
        $args = func_get_args();
        $array = $args[0];
        if (!$array) return array();
        $this->sort_fields = array_slice($args, 1);
        if (!$this->sort_fields) return $array();

        if ($this->numeric) {
            usort($array, array($this, 'numericCompare'));
        } else {
            usort($array, array($this, 'stringCompare'));
        }
    return $array;
    }

    /**
     * compare the numeric values
     *
     * @param array $a
     * @param array $b
     *
     * @return Integer
     */
    function numericCompare($a, $b)
    {
        foreach($this->sort_fields as $sort_field) {
            if ($a[$sort_field] == $b[$sort_field]) {
                continue;
            }
            return ($a[$sort_field] < $b[$sort_field]) ? ($this->backwards ? 1 : -1) : ($this->backwards ? -1 : 1);
        }
    return 0;
    }

    /**
     * Compare the String
     *
     * @param array $a
     * @param array $b
     *
     * @return Integer
     */
    function stringCompare($a, $b)
    {
        foreach($this->sort_fields as $sort_field) {
            $cmp_result = strcasecmp($a[$sort_field], $b[$sort_field]);
            if ($cmp_result == 0) continue;

            return ($this->backwards ? -$cmp_result : $cmp_result);
        }
    return 0;
    }
}
