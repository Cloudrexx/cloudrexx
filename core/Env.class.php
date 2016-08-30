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
 * Env
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core
 */

/**
 * A global environment repository.
 *
 * In old code, use this instead of global variables - allows central tracking
 * of dependencies.
 * Do *NOT* use this in new code, inject dependencies instead.
 * Example:
 * WRONG:
 * public function __construct() {
 *     $this->entityManager = Env::get('em');
 * }
 * RIGHT:
 * public function __construct($em) {
 *     $this->entityManager = $em;
 * }
 * Reason: Global state is untestable and leads to inflexible code.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core
 */
class Env {
    protected static $props = array();
    protected static $em;

    public static function set($prop, &$val) {
        switch ($prop) {
            case 'cx':
                // set is only used for installerCx. Normal cx class will load with \Env::get('cx')
                self::$props[$prop] = $val;
                \DBG::msg(__METHOD__.": Setting '$prop' is deprecated. Use only for installer, otherwise use \\Env::('$prop')");
                \DBG::stack();
                break;
            case 'em':
                self::$props[$prop] = $val;
                \DBG::msg(__METHOD__.": Setting '$prop' is deprecated. Env::get($prop) always returns the active/preferred instance of $prop.");
                \DBG::stack();
                break;

            default:
                self::$props[$prop] = $val;
                break;
        }
    }

    public static function get($prop) {
        switch ($prop) {
            case 'em':
                return \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
                break;
            case 'cx':
                if (!isset(self::$props[$prop]) && class_exists('\Cx\Core\Core\Controller\Cx')) {
                    return \Cx\Core\Core\Controller\Cx::instanciate();
                }
            default:
                if(isset(self::$props[$prop])) {
                    return self::$props[$prop];
                }
                break;
        }
        return null;
    }

    /**
     * Clear the value of a prop
     *
     * @access public
     * @param $prop indexname we want to unset
     * @return void
     */
    public static function clear($prop) {
        if (isset(self::$props[$prop])) {
            unset(self::$props[$prop]);
        }
    }
}
