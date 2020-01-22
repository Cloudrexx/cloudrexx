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
        $arrSettings = \User_Setting::getSettings();

        $groupFilter = static::fetchGroupFilter(
            $this->_objTpl, 'access_currently_online_member_list'
        );

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $qb = $cx->getDb()->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from('Cx\Core\User\Model\Entity\User', 'u')
            ->where(
                $qb->expr()->eq('u.active', ':active')
            )->andWhere(
                $qb->expr()->gt('u.lastActivity', ':lastActivity')
            )->setParameters(
                array(
                    'active' => true,
                    'lastActivity' => (time()-3600)
                )
            );

        $this->addGenderToQueryBuilder($qb, $gender);
        $this->addPicToQueryBuilder(
            $qb,
            $arrSettings['block_currently_online_users_pic']['status']
        );
        $this->addGroupToQueryBuilder($qb, $groupFilter);

        $qb->orderBy('u.lastActivity', 'DESC')
            ->addOrderBy('u.username', 'ASC')
            ->setMaxResults(
                $arrSettings['block_currently_online_users']['value']
            );

        $users = $qb->getQuery()->getResult();

        if (!empty($users)) {
            foreach ($users as $user) {
                $this->parseBasePlaceholders($user);
                $this->_objTpl->parse('access_currently_online_'.(!empty($gender) ? $gender.'_' : '').'members');
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

        $groupFilter = static::fetchGroupFilter(
            $this->_objTpl, 'access_last_active_member_list'
        );

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $qb = $cx->getDb()->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from('Cx\Core\User\Model\Entity\User', 'u')
            ->where(
                $qb->expr()->eq('u.active', ':active')
            )->setParameter('active', true);

        $this->addGenderToQueryBuilder($qb, $gender);
        $this->addPicToQueryBuilder(
            $qb,
            $arrSettings['block_last_active_users_pic']['status']
        );
        $this->addGroupToQueryBuilder($qb, $groupFilter);

        $users = $qb->getQuery()->getResult();

        if (!empty($users)) {
            foreach ($users as $user) {
                $this->parseBasePlaceholders($user);
                $this->_objTpl->parse('access_last_active_'.(!empty($gender) ? $gender.'_' : '').'members');
            }
        } else {
            $this->_objTpl->hideBlock('access_last_active_'.(!empty($gender) ? $gender.'_' : '').'members');
        }
    }

    protected function addGenderToQueryBuilder(&$qb, $gender)
    {
        $objFWUser = \FWUser::getFWUserObject();
        $attr = $objFWUser->objUser->objAttribute;

        if (!empty($gender)) {
            $qb->join(
                'u.userAttributeValue', 'vGen'
            )->andWhere(
                $qb->expr()->eq('vGen.attributeId', ':vGenId')
            )->andWhere(
                $qb->expr()->eq('vGen.value', ':vGenValue')
            )->setParametes(
                array(
                    'vGenId' => $attr->getAttributeIdByProfileAttributeId(
                        'gender'
                    ),
                    'vGenValue' => 'gender_'.$gender
                )
            );
        }
    }

    protected function addPicToQueryBuilder(&$qb, $onlyWithPic)
    {
        $objFWUser = \FWUser::getFWUserObject();
        $attr = $objFWUser->objUser->objAttribute;

        if ($onlyWithPic) {
            $qb->join(
                'u.userAttributeValue', 'vPic'
            )->andWhere(
                $qb->expr()->eq('vPic.attributeId', ':vPicId')
            )->andWhere(
                $qb->expr()->not(
                    $qb->expr()->eq('vPic.value', ':vPicValue')
                )
            )->setParametes(
                array(
                    'vPicId' => $attr->getAttributeIdByProfileAttributeId(
                        'picture'
                    ),
                    'vPicValue' => ''
                )
            );
        }
    }

    protected function addGroupToQueryBuilder(&$qb, $groupIds)
    {
        // filter users by group association
        if ($groupIds) {
            $qb->andWhere(
                $qb->expr()->in('u.groupId', ':groupIds')
            )->setParameter('groupIds', $groupIds);
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

        $groupFilter = static::fetchGroupFilter(
            $this->_objTpl, 'access_latest_registered_member_list'
        );

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $qb = $cx->getDb()->getEntityManager()->createQueryBuilder();
        $qb->select('u')
            ->from('Cx\Core\User\Model\Entity\User', 'u')
            ->where(
                $qb->expr()->eq('u.active', ':active')
            )->setParameter('active', true);

        $this->addGenderToQueryBuilder($qb, $gender);
        $this->addPicToQueryBuilder(
            $qb,
            $arrSettings['block_latest_reg_users_pic']['status']
        );
        $this->addGroupToQueryBuilder($qb, $groupFilter);

        $qb->orderBy('u.regdate', 'DESC')
            ->addOrderBy('u.username', 'ASC')
            ->setMaxResults($arrSettings['block_latest_reg_users']['value']);

        $users = $qb->getQuery()->getResult();

        if (!empty($users)) {
            foreach ($users as $user) {
                $this->parseBasePlaceholders($user);

                $this->_objTpl->parse('access_latest_registered_'.(!empty($gender) ? $gender.'_' : '').'members');
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
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $userRepo = $cx->getDb()->getEntityManager()->getRepository(
                'Cx\Core\User\Model\Entity\User'
            );
            while (!$objUser->EOF) {
                $user = $userRepo->find($objUser->getId());
                $this->parseBasePlaceholders($user);

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
            'ACCESS_USER_REGDATE'     => date(ASCMS_DATE_FORMAT_DATE, $objUser->getRegdate()),
        ));

        $objAttr = \FWUser::getFWUserObject()->objUser->objAttribute;

        foreach ($objUser->getUserAttributeValue() as $value) {
            $objAttr->load($value->getUserAttribute()->getId());
            if ($objAttr->checkReadPermission()) {
                $this->parseAttribute(
                    $objUser,
                    $objAttr->getId(),
                    0,
                    false,
                    false,
                    false,
                    false,
                    false
                );
            }
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

        $filter = array(
            'AND' => array(
                0 => array(
                    'active' => true,
                ),
                1 => array(
                    'OR' => $days
                )
            )
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
            // sort users by their anniversary
            array(
                'birthday'   => 'asc'
            ),
            null,
            $arrSettings['block_birthday_users']['value']
        );
        if ($objUser) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $userRepo = $cx->getDb()->getEntityManager()->getRepository(
                'Cx\Core\User\Model\Entity\User'
            );
            while (!$objUser->EOF) {
                $user = $userRepo->find($objUser->getId());

                $this->parseBasePlaceholders($user);

                $this->_objTpl->parse('access_next_birthday_' . (!empty($gender) ? $gender . '_' : '') . 'members');

                $objUser->next();
            }
        } else {
            $this->_objTpl->hideBlock('access_next_birthday_' . (!empty($gender) ? $gender . '_' : '') . 'members');
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

