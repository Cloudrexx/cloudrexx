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
    /**
     * Parse a list (into the loaded template object) of those users
     * currently signed in.
     *
     * @param   string  $gender Optional set to 'female' or 'male' to filter the list by gender
     */
    public function setCurrentlyOnlineUsers($gender = '')
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

        // filter users by group association
        $groupFilter = static::fetchGroupFilter($this->_objTpl, 'access_currently_online_member_list');
        if ($groupFilter) {
            $filter['group_id'] = $groupFilter;
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
                $this->parseBasePlaceholders($objUser);

                $this->_objTpl->parse('access_currently_online_'.(!empty($gender) ? $gender.'_' : '').'members');

                $objUser->next();
            }
        } else {
            $this->_objTpl->hideBlock('access_currently_online_'.(!empty($gender) ? $gender.'_' : '').'members');
        }
    }

    /**
     * Parse a list (into the loaded template object) of those users having
     * signed in the most recent.
     *
     * @param   string  $gender Optional set to 'female' or 'male' to filter the list by gender
     */
    public function setLastActiveUsers($gender = '')
    {
        $arrSettings = \User_Setting::getSettings();

        $filter['active'] = true;
        if ($arrSettings['block_last_active_users_pic']['status']) {
            $filter['picture'] = array('!=' => '');
        }

        if (!empty($gender)) {
            $filter['gender'] = 'gender_'.$gender;
        }

        // filter users by group association
        $groupFilter = static::fetchGroupFilter($this->_objTpl, 'access_last_active_member_list');
        if ($groupFilter) {
            $filter['group_id'] = $groupFilter;
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
                $this->parseBasePlaceholders($objUser);

                $this->_objTpl->parse('access_last_active_'.(!empty($gender) ? $gender.'_' : '').'members');

                $objUser->next();
            }
        } else {
            $this->_objTpl->hideBlock('access_last_active_'.(!empty($gender) ? $gender.'_' : '').'members');
        }
    }

    /**
     * Parse a list (into the loaded template object) of those users having
     * signed up the most recent.
     *
     * @param   string  $gender Optional set to 'female' or 'male' to filter the list by gender
     */
    public function setLatestRegisteredUsers($gender = '')
    {
        $arrSettings = \User_Setting::getSettings();

        $filter['active'] = true;
        if ($arrSettings['block_latest_reg_users_pic']['status']) {
            $filter['picture'] = array('!=' => '');
        }

        if (!empty($gender)) {
            $filter['gender'] = 'gender_'.$gender;
        }

        // filter users by group association
        $groupFilter = static::fetchGroupFilter($this->_objTpl, 'access_latest_registered_member_list');
        if ($groupFilter) {
            $filter['group_id'] = $groupFilter;
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
                $this->parseBasePlaceholders($objUser);

                $this->_objTpl->parse('access_latest_registered_'.(!empty($gender) ? $gender.'_' : '').'members');

                $objUser->next();
            }
        } else {
            $this->_objTpl->hideBlock('access_latest_registered_'.(!empty($gender) ? $gender.'_' : '').'members');
        }
    }

    /**
     * Parse a list (into the loaded template object) of those users having
     * their birthday today.
     *
     * @param   string  $gender Optional set to 'female' or 'male' to filter the list by gender
     */
    public function setBirthdayUsers($gender = '')
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

        // filter users by group association
        $groupFilter = static::fetchGroupFilter($this->_objTpl, 'access_birthday_member_list');
        if ($groupFilter) {
            $filter['group_id'] = $groupFilter;
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
                $this->parseBasePlaceholders($objUser);

                $this->_objTpl->parse('access_birthday_'.(!empty($gender) ? $gender.'_' : '').'members');

                $objUser->next();
            }
        } else {
            $this->_objTpl->hideBlock('access_birthday_'.(!empty($gender) ? $gender.'_' : '').'members');
        }
    }

    /**
     * Parses ACCESS_USER_ID, -USERNAME and -REGDATE placeholders and the user's attributes
     * @param \User User object to parse placeholders for
     */
    public function parseBasePlaceholders($objUser) {
        $this->_objTpl->setVariable(array(
            'ACCESS_USER_ID'    => $objUser->getId(),
            'ACCESS_USER_USERNAME'    => htmlentities($objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET),
            'ACCESS_USER_REGDATE'     => date(ASCMS_DATE_FORMAT_DATE, $objUser->getRegistrationDate()),
        ));

        $objUser->objAttribute->first();
        while (!$objUser->objAttribute->EOF) {
            $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());
            if ($objAttribute->checkReadPermission()) {
                $this->parseAttribute($objUser, $objAttribute->getId(), 0, false, false, false, false, false);
            }
            $objUser->objAttribute->next();
        }
    }

    /**
     * Parse a list (into the loaded template object) of those users having
     * their birthday coming up.
     *
     * @param   string  $gender Optional set to 'female' or 'male' to filter the list by gender
     * @todo    Implement feature to filter by filter group placeholder (see fetchGroupFilter())
     */
    public function setNextBirthdayUsers($gender = '')
    {
        $arrSettings = \User_Setting::getSettings();

        $query = 'SELECT tblU.`id`
            FROM `' . DBPREFIX . 'access_users` AS tblU
            INNER JOIN `' . DBPREFIX . 'access_user_profile` AS tblP ON tblP.`user_id` = tblU.`id`
            WHERE tblU.`active` = true
              AND tblP.`birthday` != ""';

        if (!empty($gender)) {
            $query .= ' AND tblP.`gender` = "gender_' . $gender . '"';
        }

        if ($arrSettings['block_next_birthday_users_pic']['status']) {
            $query .= ' AND tblP.`picture` != ""';
        }

        $dayOffset = $arrSettings['block_next_birthday_users']['value'];

        $date = new \DateTime('tomorrow');
        $days = array();
        for ($i = 0; $i < $dayOffset + 1; $i++) {
            $day = array(
                'birthday_day' => $date->format('j'),
                'birthday_month' => $date->format('n'),
            );
            array_push($days, $day);
            if ($dayOffset > 0) {
                $date->modify('+1 day');
            }
        }

        $arrConditions = array();
        $arrOrder = array();
        $birthdayQuery = ' AND (';
        foreach ($days as $day) {
            $arrConditions[] = '(DATE_FORMAT(DATE_ADD(FROM_UNIXTIME(0), interval `tblP`.`birthday` second), "%e") = "' . intval($day['birthday_day']) . '")
             AND (DATE_FORMAT(DATE_ADD(FROM_UNIXTIME(0), interval `tblP`.`birthday` second), "%c") = "' . intval($day['birthday_month']) . '")';
            $arrOrder[] = intval($day['birthday_day']) . '-' . intval($day['birthday_month']);
        }
        $birthdayQuery .= implode(' OR ', $arrConditions) . ')';
        $query .= $birthdayQuery;
        $objResult = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb()->Execute($query);

        $userIds = array();
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                array_push($userIds, $objResult->fields['id']);
                $objResult->MoveNext();
            }
        }

        $users = array();
        $objFWUser = \FWUser::getFWUserObject();
        foreach ($userIds as $userId) {
            $user = $objFWUser->objUser->getUser($userId);
            array_push($users, $user);
        }

        if (empty($users)) {
            $this->_objTpl->hideBlock('access_next_birthday_' . (!empty($gender) ? $gender . '_' : '') . 'members');
            return;
        }

        $arrOrder = array_flip($arrOrder);

        // sort users by their anniversary
        usort($users, function($a, $b) use ($arrOrder) {
            $birthdayOfA = date('j-n', $a->getProfileAttribute('birthday'));
            $birthdayOfB = date('j-n', $b->getProfileAttribute('birthday'));

            $orderLocationOfA = isset($arrOrder[$birthdayOfA]) ? $arrOrder[$birthdayOfA] : count($arrOrder);
            $orderLocationOfB = isset($arrOrder[$birthdayOfB]) ? $arrOrder[$birthdayOfB] : count($arrOrder);

            return $orderLocationOfA - $orderLocationOfB;
        });

        foreach ($users as $user) {
            $this->_objTpl->setVariable(array(
                'ACCESS_USER_ID' => $user->getId(),
                'ACCESS_USER_USERNAME' => htmlentities($user->getUsername(), ENT_QUOTES, CONTREXX_CHARSET),
                'ACCESS_USER_REGDATE'  => date(ASCMS_DATE_FORMAT_DATE, $user->getRegistrationDate()),
            ));

            $user->objAttribute->first();
            while (!$user->objAttribute->EOF) {
                $objAttribute = $user->objAttribute->getById($user->objAttribute->getId());
                if ($objAttribute->checkReadPermission()) {
                    $this->parseAttribute($user, $objAttribute->getId(), 0, false, false, false, false, false);
                }
                $user->objAttribute->next();
            }

            $this->_objTpl->parse('access_next_birthday_' . (!empty($gender) ? $gender . '_' : '') . 'members');
        }
    }

    /**
     * Check if any of the active users having their birthday today.
     *
     * @return  boolean TRUE if one user's birthday is today. Otherwise FALSE
     */
    public function isSomeonesBirthdayToday()
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

    /**
     * Scan the supplied template for group-filter-placeholders and return
     * the parsed group-IDs.
     * Scheme of a group-filter-placeholder: ACCESS_FILTER_GROUP_<ID>
     *
     * @param   \Cx\Core\Html\Sigma $template   Template to look for group filter placeholders for
     * @param   string  $blockName  The template block in which to look for the placeholders for
     * @return  array Array of group-IDs
     */
    public static function fetchGroupFilter($template, $blockName) {
        // fetch all placeholders from current application template
        $placeholders = $template->getPlaceholderList($blockName);

        // filter out special placeholders that identify a group filter
        $groupFilterPlaceholderPrefix = 'ACCESS_FILTER_GROUP_';
        $groupFilterPlaceholders = preg_grep('/^' . $groupFilterPlaceholderPrefix . '/', $placeholders);
        return preg_filter('/^' . $groupFilterPlaceholderPrefix . '/', '', $groupFilterPlaceholders);
    }
}

