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
 * SMTP Settings
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core
 * @todo        Edit PHP DocBlocks!
 */

/**
 * SMTP Settings
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  core
 * @todo        Edit PHP DocBlocks!
 * @static
 */
class SmtpSettings
{
    /**
     * Get details of a specified SMTP account
     *
     * Returns the details of the SMTP account specified by $accountId.
     * If $accountId is either FALSE or not a valid account ID then this
     * method will return FALSE.
     * Only if $getPassword is set to TRUE will the password of the specified SMTP
     * account be returned.
     * @access private
     * @global ADONewConnection
     * @param integer $accountId
     * @param boolean $getPassword
     * @return mixed Array with account details if $accountId is a valid account ID, otherwise FALSE
     * @static
     */
    static function _getSmtpAccount($accountId=0, $getPassword=false)
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("
            SELECT `name`, `hostname`, `port`, `username`, ".
            ($getPassword
                ? '`password`'
                : 'CHAR_LENGTH(`password`) AS \'password\''
            )."
              FROM `".DBPREFIX."settings_smtp`
             WHERE `id`=".intval($accountId)
        );
        if ($objResult && $objResult->RecordCount() == 1) {
            return array(
                'name'        => $objResult->fields['name'],
                'hostname'    => $objResult->fields['hostname'],
                'port'        => $objResult->fields['port'],
                'username'    => $objResult->fields['username'],
                'password'    => $objResult->fields['password']
            );
        }
        return false;
    }


    /**
     * Get a list of available SMTP accounts
     *
     * Returns an array with all available SMTP accounts. This includes
     * on the one hand the self defined accounts and on the other hand the
     * local system account defined in the php.ini.
     * @global ADONewConnection
     * @see getSystemSmtpAccount()
     * @return array Array with SMTP accounts
     * @static
     */
    static function getSmtpAccounts()
    {
        global $objDatabase;

        $arrSmtp[0] = SmtpSettings::getSystemSmtpAccount();

        $objResult = $objDatabase->Execute('SELECT `id`, `name`, `hostname`, `username` FROM `'.DBPREFIX.'settings_smtp` ORDER BY `name`');
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $arrSmtp[$objResult->fields['id']] = array(
                    'name'        => $objResult->fields['name'],
                    'hostname'    => $objResult->fields['hostname'],
                    'username'    => $objResult->fields['username']
                );
                $objResult->MoveNext();
            }
        }
        return $arrSmtp;
    }


    /**
     * Returns the local configured SMTP account of the current system.
     * @global  array $_CORELANG
     * @return array Array with the SMTP account details
     * @static
     */
    static function getSystemSmtpAccount()
    {
        global $_CORELANG;

        return array(
            'name'     => $_CORELANG['TXT_SETTINGS_SERVER_CONFIGURATION'],
            'hostname' => $_CORELANG['TXT_SETTINGS_SMTP_SERVER_DAEMON'],
            'port'     => '',
            'username' => '',
            'password' => '',
            'system'   => 1,
        );
    }


    /**
     * @static
     */
    static function _updateSmtpAccount($id, $arrSmtp)
    {
        global $objDatabase;

        $arrUpdateAttributes = array();
        $arrCurrentSmtp = SmtpSettings::getSmtpAccount($id);

        if ($arrCurrentSmtp['name'] != $arrSmtp['name']) {
            array_push($arrUpdateAttributes, "`name` = '".addslashes($arrSmtp['name'])."'");
        }
        if ($arrCurrentSmtp['hostname'] != $arrSmtp['hostname']) {
            array_push($arrUpdateAttributes, "`hostname` = '".addslashes($arrSmtp['hostname'])."'");
        }
        if ($arrCurrentSmtp['port'] != $arrSmtp['port']) {
            array_push($arrUpdateAttributes, '`port`='.$arrSmtp['port']);
        }
        if ($arrCurrentSmtp['username'] != $arrSmtp['username']) {
            array_push($arrUpdateAttributes, "`username`='".addslashes($arrSmtp['username'])."'");
        }
        if (empty($arrSmtp['password']) || ($pass = trim($arrSmtp['password'])) && !empty($pass)) {
            array_push($arrUpdateAttributes, "`password`='".addslashes(trim($arrSmtp['password']))."'");
        }

        if (count($arrUpdateAttributes) > 0) {
            if ($objDatabase->Execute("UPDATE `".DBPREFIX."settings_smtp` SET ".implode(', ', $arrUpdateAttributes)." WHERE `id` = ".$id) === false) {
                return false;
            }
        }
        return true;
    }


    /**
     * @static
     */
    static function _addSmtpAccount($arrSmtp)
    {
        global $objDatabase;

        if ($objDatabase->Execute("INSERT INTO `".DBPREFIX."settings_smtp` (`name`, `hostname`, `port`, `username`, `password`) VALUES ('".addslashes($arrSmtp['name'])."', '".addslashes($arrSmtp['hostname'])."', ".$arrSmtp['port'].", '".addslashes($arrSmtp['username'])."', '".addslashes($arrSmtp['password'])."')") !== false) {
            return true;
        }
        return false;
    }


    /**
     * Check for unique SMTP account name
     *
     * This method checks if the account name specified by $name is unique within
     * the system.
     * @access private
     * @param string $name
     * @param integer $id of a SMTP account
     * @return boolean
     * @static
     */
    static function _isUniqueSmtpAccountName($name, $id = 0)
    {
        global $objDatabase;

        $objResult = $objDatabase->SelectLimit("SELECT 1 FROM `".DBPREFIX."settings_smtp` WHERE `name` = '".addslashes($name)."' AND `id` !=".$id, 1);
        if ($objResult !== false && $objResult->RecordCount() == 0) {
            return true;
        }
        return false;
    }


    /**
     * @static
     */
    static function getSmtpAccountMenu($selectedAccountId, $attrs)
    {
        $menu = '<select'.(!empty($attrs) ? ' '.$attrs : '').'>';
        foreach (SmtpSettings::getSmtpAccounts() as $id => $arrSmtp) {
            $menu .= '<option value="'.$id.'"'.($selectedAccountId == $id ? ' selected="selected"' : '').'>'.htmlentities($arrSmtp['name'], ENT_QUOTES, CONTREXX_CHARSET).'</option>';
        }
        $menu .= '</select>';
        return $menu;
    }


    /**
     * Get details of a SMTP account
     *
     * Returns the details of the SMTP account specified by $accountId.
     * If $accountId is either FALSE or not a valid account ID then FALSE will
     * be returned instead.
     * @see _getSmtpAccount()
     * @param   integer $accountId  The ID of the account settings
     * @param boolean $getPassword
     * @return  mixed           Array with the details of the requested account
     *                          on success, false otherwise
     * @static
     */
    static function getSmtpAccount($accountId=0, $getPassword=true)
    {
        global $objDatabase;

        return SmtpSettings::_getSmtpAccount($accountId, $getPassword);
    }
}

?>
