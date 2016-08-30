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
 * User Management
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Daeppen <thomas.daeppen@comvation.com>
 * @version     2.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_access
 */

namespace Cx\Core_Modules\Access\Controller;

/**
 * Info Blocks about Community used in the layout
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Daeppen <thomas.daeppen@comvation.com>
 * @version     2.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_access
 */
class AccessBlocks extends \Cx\Core_Modules\Access\Controller\AccessLib
{
    public function __construct()
    {
        global $objTemplate;

        parent::__construct();

        $this->_objTpl = &$objTemplate;
    }


    function setCurrentlyOnlineUsers($gender=null)
    {
        $objFWUser = \FWUser::getFWUserObject();
        $arrSettings = \User_Setting::getSettings();

        $filter = array(
            'active'    => true,
            'last_activity' => array(
                '>' => (time()-3600)
            )
        );
        if ($arrSettings['block_currently_online_users_pic']['status']) {
            $filter['picture'] = array('!=' => '');
        }

        if (!empty($gender)) {
            $filter['gender'] = 'gender_'.$gender;
        }

        $objUser = $objFWUser->objUser->getUsers(
            $filter,
            null,
            array(
                'last_activity'    => 'desc',
                'username'        => 'asc'
            ),
            null,
            $arrSettings['block_currently_online_users']['value']
        );
        if ($objUser) {
            while (!$objUser->EOF) {
                $this->_objTpl->setVariable(array(
                    'ACCESS_USER_ID'    => $objUser->getId(),
                    'ACCESS_USER_USERNAME'    => htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET)
                ));

                $objUser->objAttribute->first();
                while (!$objUser->objAttribute->EOF) {
                    $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());
                    $this->parseAttribute($objUser, $objAttribute->getId(), 0, false, false, false, false, false);
                    $objUser->objAttribute->next();
                }

                $this->_objTpl->parse('access_currently_online_'.(!empty($gender) ? $gender.'_' : '').'members');

                $objUser->next();
            }
        } else {
            $this->_objTpl->hideBlock('access_currently_online_'.(!empty($gender) ? $gender.'_' : '').'members');
        }
    }


    function setLastActiveUsers($gender = null)
    {
        $arrSettings = \User_Setting::getSettings();

        $filter['active'] = true;
        if ($arrSettings['block_last_active_users_pic']['status']) {
            $filter['picture'] = array('!=' => '');
        }

        if (!empty($gender)) {
            $filter['gender'] = 'gender_'.$gender;
        }

        $objFWUser = \FWUser::getFWUserObject();
        $objUser = $objFWUser->objUser->getUsers(
            $filter,
            null,
            array(
                'last_activity'    => 'desc',
                'username'        => 'asc'
            ),
            null,
            $arrSettings['block_last_active_users']['value']
        );
        if ($objUser) {
            while (!$objUser->EOF) {
                $this->_objTpl->setVariable(array(
                    'ACCESS_USER_ID'    => $objUser->getId(),
                    'ACCESS_USER_USERNAME'    => htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET)
                ));

                $objUser->objAttribute->first();
                while (!$objUser->objAttribute->EOF) {
                    $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());
                    $this->parseAttribute($objUser, $objAttribute->getId(), 0, false, false, false, false, false);
                    $objUser->objAttribute->next();
                }

                $this->_objTpl->parse('access_last_active_'.(!empty($gender) ? $gender.'_' : '').'members');

                $objUser->next();
            }
        } else {
            $this->_objTpl->hideBlock('access_last_active_'.(!empty($gender) ? $gender.'_' : '').'members');
        }
    }


    function setLatestRegisteredUsers($gender = null)
    {
        $arrSettings = \User_Setting::getSettings();

        $filter['active'] = true;
        if ($arrSettings['block_latest_reg_users_pic']['status']) {
            $filter['picture'] = array('!=' => '');
        }

        if (!empty($gender)) {
            $filter['gender'] = 'gender_'.$gender;
        }

        $objFWUser = \FWUser::getFWUserObject();
        $objUser = $objFWUser->objUser->getUsers(
            $filter,
            null,
            array(
                'regdate'    => 'desc',
                'username'    => 'asc'
            ),
            null,
            $arrSettings['block_latest_reg_users']['value']
        );
        if ($objUser) {
            while (!$objUser->EOF) {
                $this->_objTpl->setVariable(array(
                    'ACCESS_USER_ID'    => $objUser->getId(),
                    'ACCESS_USER_USERNAME'    => htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET)
                ));

                $objUser->objAttribute->first();
                while (!$objUser->objAttribute->EOF) {
                    $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());
                    $this->parseAttribute($objUser, $objAttribute->getId(), 0, false, false, false, false, false);
                    $objUser->objAttribute->next();
                }

                $this->_objTpl->parse('access_latest_registered_'.(!empty($gender) ? $gender.'_' : '').'members');

                $objUser->next();
            }
        } else {
            $this->_objTpl->hideBlock('access_latest_registered_'.(!empty($gender) ? $gender.'_' : '').'members');
        }
    }


    function setBirthdayUsers($gender = null)
    {
        $arrSettings = \User_Setting::getSettings();

        $filter = array(
            'active'    => true,
            'birthday_day'      => date('j'),
            'birthday_month'    => date('n')
        );
        if ($arrSettings['block_birthday_users_pic']['status']) {
            $filter['picture'] = array('!=' => '');
        }

        if (!empty($gender)) {
            $filter['gender'] = 'gender_'.$gender;
        }

        $objFWUser = \FWUser::getFWUserObject();
        $objUser = $objFWUser->objUser->getUsers(
            $filter,
            null,
            array(
                'regdate'    => 'desc',
                'username'    => 'asc'
            ),
            null,
            $arrSettings['block_birthday_users']['value']
        );
        if ($objUser) {
            while (!$objUser->EOF) {
                $this->_objTpl->setVariable(array(
                    'ACCESS_USER_ID'    => $objUser->getId(),
                    'ACCESS_USER_USERNAME'    => htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET)
                ));

                $objUser->objAttribute->first();
                while (!$objUser->objAttribute->EOF) {
                    $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());
                    $this->parseAttribute($objUser, $objAttribute->getId(), 0, false, false, false, false, false);
                    $objUser->objAttribute->next();
                }

                $this->_objTpl->parse('access_birthday_'.(!empty($gender) ? $gender.'_' : '').'members');

                $objUser->next();
            }
        } else {
            $this->_objTpl->hideBlock('access_birthday_'.(!empty($gender) ? $gender.'_' : '').'members');
        }
    }


    function isSomeonesBirthdayToday()
    {
        $arrSettings = \User_Setting::getSettings();

        $filter = array(
            'active'            => true,
            'birthday_day'      => date('j'),
            'birthday_month'    => date('n')
        );
        if ($arrSettings['block_birthday_users_pic']['status']) {
            $filter['picture'] = array('!=' => '');
        }

        $objFWUser = \FWUser::getFWUserObject();
        if ($objFWUser->objUser->getUsers($filter, null, null, null, 1))
            return true;
        return false;
    }

}

?>
