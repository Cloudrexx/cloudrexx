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
 * Member manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_member
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Member manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_member
 */
class MemberManager
{
    var $_objTpl;

        private $act = '';

    /**
     * PHP 5 Constructor
     */
    function __construct()
    {
        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH.'/member/View/Template/Backend');
                \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
                $this->act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
                $this->setNavigation();
    }
        private function setNavigation()
        {
                global $objTemplate, $_ARRAYLANG;

                $objTemplate->setVariable("CONTENT_NAVIGATION", "
                    <a href='index.php?cmd=Contact' title=".$_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS']." class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS']."</a>
                    <a href='index.php?cmd=Contact&amp;act=settings' title=".$_ARRAYLANG['TXT_CONTACT_SETTINGS']." class='".($this->act == 'settings' ? 'active' : '')."'>".$_ARRAYLANG['TXT_CONTACT_SETTINGS']."</a>");
        }

    function MemberManager()
    {
        $this->__construct();
    }


}


?>
