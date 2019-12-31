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
 * SettingsMails
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_checkout
 */

namespace Cx\Modules\Checkout\Controller;

/**
 * SettingsMails
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_checkout
 */
class SettingsMails {

    /**
     * Database object.
     *
     * @access      private
     * @var         ADONewConnection
     */
    private $objDatabase;

    /**
     * Initialize the database object.
     *
     * @access      public
     * @param       ADONewConnection    $objDatabase
     */
    public function __construct($objDatabase)
    {
        $this->objDatabase = $objDatabase;
    }

    /**
     * Get admin mail.
     *
     * @access      public
     */
    public function getAdminMail()
    {
        $objResult = $this->objDatabase->Execute('SELECT `title`, `content` FROM `'.DBPREFIX.'module_checkout_settings_mails` WHERE `id`=1');

        if ($objResult) {
            return array(
                'title'   => $objResult->fields['title'],
                'content' => $objResult->fields['content'],
            );
        } else {
            return false;
        }
    }

    /**
     * Get customer mail.
     *
     * @access      public
     */
    public function getCustomerMail()
    {
        $objResult = $this->objDatabase->Execute('SELECT `title`, `content` FROM `'.DBPREFIX.'module_checkout_settings_mails` WHERE `id`=2');

        if ($objResult) {
            return array(
                'title'   => $objResult->fields['title'],
                'content' => $objResult->fields['content'],
            );
        } else {
            return false;
        }
    }

    /**
     * Update administrator mail.
     *
     * @access      public
     * @param       array       $arrAdminMail
     */
    public function updateAdminMail($arrAdminMail)
    {
        $objResult = $this->objDatabase->Execute('
            UPDATE `'.DBPREFIX.'module_checkout_settings_mails`
            SET `title`="'.contrexx_raw2db($arrAdminMail['title']).'",
                `content`="'.contrexx_raw2db($arrAdminMail['content']).'"
            WHERE `id`=1
        ');

        if ($objResult) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update customer mail.
     *
     * @access      public
     * @param       array       $arrCustomerMail
     */
    public function updateCustomerMail($arrCustomerMail)
    {
        $objResult = $this->objDatabase->Execute('
            UPDATE `'.DBPREFIX.'module_checkout_settings_mails`
            SET `title`="'.contrexx_raw2db($arrCustomerMail['title']).'",
                `content`="'.contrexx_raw2db($arrCustomerMail['content']).'"
            WHERE `id`=2
        ');

        if ($objResult) {
            return true;
        } else {
            return false;
        }
    }
}
