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
 * User Group Object
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */

/**
 * User Group Object
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class UserGroup
{
    private $id;
    private $name;
    private $description;
    private $is_active;
    private $type;
    private $homepage;

    private $arrLoadedGroups = array();
    private $arrCache = array();

    private $arrAttributes = array(
        'group_id',
        'group_name',
        'group_description',
        'is_active',
        'type',
        'homepage',
    );

    private $arrTypes = array(
        'frontend',
        'backend'
    );

    private $arrUsers;
    private $arrStaticPermissions;
    private $arrDynamicPermissions;

    private $defaultType = 'frontend';

    public $EOF;

    /**
     * Contains the message if an error occurs
     *
     * @var unknown_type
     */
    private $error_msg;


    function __construct()
    {
        $this->clean();
    }


    public function getGroups(
        $filter=null, $arrSort=null, $arrAttributes=null,
        $limit=null, $offset=0)
    {
        $objGroup = clone $this;
        $objGroup->arrCache = &$this->arrCache;
        $objGroup->loadGroups($filter, $arrSort, $arrAttributes, $limit, $offset);
        return $objGroup;
    }


    private function loadGroups(
        $filter=null, $arrSort=null, $arrAttributes=null,
        $limit=null, $offset=0)
    {
        global $objDatabase;

        $this->arrLoadedGroups = array();
        $arrWhereExpressions = array('conditions' => array(), 'joins' => array());
        $arrSortExpressions = array();
        $arrSelectExpressions = array();

        // set filter
        if (is_array($filter)) {
            $arrWhereExpressions = $this->parseFilterConditions($filter);
        } elseif (!empty($filter)) {
            $arrWhereExpressions['conditions'][] =
                '`tblG`.`group_id`='.intval($filter);
        }

        // set sort order
        if (is_array($arrSort)) {
            foreach ($arrSort as $attribute => $direction) {
                if (   in_array($attribute, $this->arrAttributes)
                    && in_array(strtolower($direction), array('asc', 'desc'))) {
                    $arrSortExpressions[] = 'tblG.`'.$attribute.'` '.$direction;
                }
            }
        }

        // set field list
        if (!is_array($arrAttributes)) {
            $arrAttributes = $this->arrAttributes;
        }
        foreach ($arrAttributes as $attribute) {
            if (   in_array($attribute, $this->arrAttributes)
                && !in_array($attribute, $arrSelectExpressions)) {
                $arrSelectExpressions[] = '`tblG`.`'.$attribute.'`';
            }
        }
        if (!in_array('`tblG`.`group_id`', $arrSelectExpressions)) {
            $arrSelectExpressions[] = '`tblG`.`group_id`';
        }

        $query = '
            SELECT '.implode(', ', $arrSelectExpressions).'
              FROM `'.DBPREFIX.'access_user_groups` AS tblG'
            .(count($arrWhereExpressions['joins'])
                ? implode(' ', $arrWhereExpressions['joins']) : '')
            .(count($arrWhereExpressions['conditions'])
                ? ' WHERE '.implode(' AND ', $arrWhereExpressions['conditions'])
                : '')
            .(count($arrSortExpressions)
                ? ' ORDER BY '.implode(', ', $arrSortExpressions) : '');

        if (empty($limit)) {
            $objGroup = $objDatabase->Execute($query);
        } else {
            $objGroup = $objDatabase->SelectLimit($query, $limit, intval($offset));
        };

        if ($objGroup !== false && $objGroup->RecordCount() > 0) {
            while (!$objGroup->EOF) {
                $this->arrCache[$objGroup->fields['group_id']] = $this->arrLoadedGroups[$objGroup->fields['group_id']] = $objGroup->fields;
                $objGroup->MoveNext();
            }
            $this->first();
            return true;
        } else {
            $this->clean();
            return false;
        }
    }


    private function parseFilterConditions($arrFilter)
    {
        $arrConditions = array('conditions' => array(), 'joins' => array());
        foreach ($arrFilter as $attribute => $condition) {
            switch ($attribute) {
                case 'group_name':
                case 'group_description':
                    $arrConditions['conditions'][] = "tblG.`".$attribute."` LIKE '%".addslashes($condition)."%'";
                    break;

                case 'is_active':
                    $arrConditions['conditions'][] = 'tblG.`'.$attribute.'` = '.intval($condition);
                    break;

                case 'type':
                    $arrConditions['conditions'][] = "tblG.`".$attribute."` = '".addslashes($condition)."'";
                   break;

                case 'static':
                case 'dynamic':
                    $arrConditions['conditions'][] = 'tbl'.$attribute.'.`access_id` = '.intval($condition);
                    $arrConditions['joins'][] = ' INNER JOIN `'.DBPREFIX.'access_group_'.$attribute.'_ids` as tbl'.$attribute.' USING (`group_id`)';
                    break;
            }
        }

        return $arrConditions;
    }


    /**
     * Returns the UserGroup for the given ID
     * @param   integer   $id     The UserGroup ID
     * @return  UserGroup         The Group on success, false(?) otherwise
     */
    public function getGroup($id)
    {
        $objGroup = clone $this;
        $objGroup->arrCache = &$this->arrCache;
        $objGroup->load($id);
        return $objGroup;
    }


    private function load($id)
    {
        if ($id) {
            if (!isset($this->arrCache[$id])) {
                return $this->loadGroups($id);
            }
            $this->id = $this->arrCache[$id]['group_id'];
            $this->name = isset($this->arrCache[$id]['group_name']) ? $this->arrCache[$id]['group_name'] : '';
            $this->description = isset($this->arrCache[$id]['group_description']) ? $this->arrCache[$id]['group_description'] : '';
            $this->is_active = isset($this->arrCache[$id]['is_active']) ? (bool)$this->arrCache[$id]['is_active'] : false;
            $this->type = isset($this->arrCache[$id]['type']) ? $this->arrCache[$id]['type'] : $this->defaultType;
            $this->homepage = isset($this->arrCache[$id]['homepage']) ? $this->arrCache[$id]['homepage'] : '';
            $this->arrDynamicPermissions = null;
            $this->arrStaticPermissions = null;
            $this->arrUsers = null;
            $this->EOF = false;
            return true;
        }
        $this->clean();
        return false;
    }


    /**
     * Returns an array of IDs of Users associated with this group
     * @global ADOConnection    $objDatabase
     * @return array                            The array of User IDs on
     *                                          success, false otherwise
     */
    private function loadUsers()
    {
        global $objDatabase;

        $arrUsers = array();

        $objUser = $objDatabase->Execute('
            SELECT
                tblRel.`user_id`
            FROM
                `'.DBPREFIX.'access_rel_user_group` AS tblRel
            INNER JOIN `'.DBPREFIX.'access_users` AS tblUser
            ON tblUser.`id` = tblRel.`user_id`
            WHERE tblRel.`group_id` = '.$this->id.'
            ORDER BY tblUser.`username`'
        );
        if ($objUser) {
            while (!$objUser->EOF) {
                array_push($arrUsers, $objUser->fields['user_id']);
                $objUser->MoveNext();
            }

            return $arrUsers;
        } else {
            return false;
        }
    }


    private function loadPermissions($type)
    {
        global $objDatabase;

        $arrRightIds = array();
        $objResult = $objDatabase->Execute('SELECT `access_id` FROM `'.DBPREFIX.'access_group_'.$type.'_ids` WHERE `group_id`='.$this->id);
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                array_push($arrRightIds, $objResult->fields['access_id']);
                $objResult->MoveNext();
            }
            return $arrRightIds;
        } else {
            return false;
        }
    }


    private function loadDynamicPermissions()
    {
        return $this->loadPermissions('dynamic');
    }


    private function loadStaticPermissions()
    {
        return $this->loadPermissions('static');
    }


    /**
     * Store user account
     *
     * This stores the metadata of the user, which includes the username,
     * password, email, language ID, activ status and the administration status,
     * to the database.
     * If it is a new user, it also sets the registration time to the current time.
     * @global ADONewConnection
     * @global array
     * @return boolean
     */
    public function store()
    {
        global $objDatabase, $_CORELANG;

        if (!$this->isUniqueGroupName() || !$this->isValidGroupName()) {
            $this->error_msg = $_CORELANG['TXT_ACCESS_GROUP_NAME_INVALID'];
            return false;
        }
        if ($this->id) {
            if (!$objDatabase->Execute("
                UPDATE `".DBPREFIX."access_user_groups`
                SET
                    `group_name` = '".addslashes($this->name)."',
                    `group_description` = '".addslashes($this->description)."',
                    `is_active` = ".intval($this->is_active).",
                    `homepage` = '".addslashes($this->homepage)."'
                WHERE `group_id`=".$this->id
            )) {
                $this->error_msg = $_CORELANG['TXT_ACCESS_FAILED_TO_UPDATE_GROUP'];
                return false;
            }
        } else {
            if ($objDatabase->Execute("
                INSERT INTO `".DBPREFIX."access_user_groups` (
                    `group_name`,
                    `group_description`,
                    `is_active`,
                    `type`,
                    `homepage`
                ) VALUES (
                    '".addslashes($this->name)."',
                    '".addslashes($this->description)."',
                    ".intval($this->is_active).",
                    '".$this->type."',
                    '".addslashes($this->homepage)."'
                )"
            )) {
                $this->id = $objDatabase->Insert_ID();
            } else {
                $this->error_msg = $_CORELANG['TXT_ACCESS_FAILED_TO_CREATE_GROUP'];
                return false;
            }
        }

        if (!$this->storeUserAssociations()) {
            $this->error_msg = $_CORELANG['TXT_ACCESS_COULD_NOT_SET_USER_ASSOCIATIONS'];
            return false;
        }

        if (!$this->storePermissions()) {
            $this->error_msg = $_CORELANG['TXT_ACCESS_COULD_NOT_SET_PERMISSIONS'];
            return false;
        }

        // flush all user based cache to ensure new permissions are enforced
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $cache = $cx->getComponent('Cache');
        $cache->clearUserBasedPageCache();
        $cache->clearUserBasedEsiCache();

        return true;
    }


    /**
     * Store user associations
     *
     * Stores the user associations of the loaded group.
     * Returns TRUE no success, FALSE on failure.
     * @global ADONewConnection
     * @return boolean
     */
    private function storeUserAssociations()
    {
        global $objDatabase;

        $status = true;
        $arrCurrentUsers = $this->loadUsers();
        $arrAddedUsers = array_diff($this->getAssociatedUserIds(), $arrCurrentUsers);
        $arrRemovedUsers = array_diff($arrCurrentUsers, $this->getAssociatedUserIds());

        foreach ($arrRemovedUsers as $userId) {
            if ($objDatabase->Execute('DELETE FROM `'.DBPREFIX.'access_rel_user_group` WHERE `group_id` = '.$this->id.' AND `user_id` = '.$userId) === false) {
                $status = false;
            }
        }

        foreach ($arrAddedUsers as $userId) {
            if ($objDatabase->Execute('INSERT INTO `'.DBPREFIX.'access_rel_user_group` (`user_id`, `group_id`) VALUES ('.$userId.', '.$this->id.')') === false) {
                $status = false;
            }
        }

        return $status;
    }


    private function storePermissions()
    {
        global $objDatabase;
        static $arrType = array('Static', 'Dynamic');

        $status = true;
        foreach ($arrType as $type) {
            $arrCurrentIds = $this->{'load'.$type.'Permissions'}();
            $ids = 'arr'.$type.'Permissions';
            if (!is_array($this->$ids)) continue;
            $arrAddedRightIds = array_diff($this->$ids, $arrCurrentIds);
            $arrRemovedRightIds = array_diff($arrCurrentIds, $this->$ids);
            $table = DBPREFIX.'access_group_'.strtolower($type).'_ids';
            foreach ($arrRemovedRightIds as $rightId) {
                if (!$objDatabase->Execute('DELETE FROM `'.$table.'` WHERE `access_id`='.$rightId.' AND `group_id`='.$this->id)) {
                    $status = false;
                }
            }
            foreach ($arrAddedRightIds as $rightId) {
                if (!$objDatabase->Execute('INSERT INTO `'.$table.'` (`access_id` , `group_id`) VALUES ('.$rightId.','.$this->id.')')) {
                    $status = false;
                }
            }
        }
        return $status;
    }


    private function clean()
    {
        $this->id = 0;
        $this->name = '';
        $this->description = '';
        $this->is_active = false;
        $this->type = $this->defaultType;
        $this->homepage = '';
        $this->arrDynamicPermissions = null;
        $this->arrStaticPermissions = null;
        $this->arrUsers = null;
        $this->EOF = true;
    }


    public function delete()
    {
        global $objDatabase, $_CORELANG;

        if ($objDatabase->Execute('DELETE FROM `'.DBPREFIX.'access_rel_user_group` WHERE `group_id` = '.$this->id) !== false && $objDatabase->Execute('DELETE FROM `'.DBPREFIX.'access_user_groups` WHERE `group_id` = '.$this->id) !== false) {
            return true;
        } else {
            $this->error_msg = sprintf($_CORELANG['TXT_ACCESS_GROUP_DELETE_FAILED'], $this->name);
            return false;
        }
    }


    /**
     * Load first group
     */
    function first()
    {
        if (reset($this->arrLoadedGroups) === false || !$this->load(key($this->arrLoadedGroups))) {
            $this->EOF = true;
        } else {
            $this->EOF = false;
        }
    }


    /**
     * Load next group
     */
    public function next()
    {
        if (next($this->arrLoadedGroups) === false || !$this->load(key($this->arrLoadedGroups))) {
            $this->EOF = true;
        }
    }


    public function setName($name)
    {
        $this->name = $name;
    }


    public function setDescription($description)
    {
        $this->description = $description;
    }


    public function setActiveStatus($status)
    {
        $this->is_active = (bool)$status;
    }


    public function setType($type)
    {
        $this->type = in_array($type, $this->arrTypes) ? $type : $this->defaultType;
    }

    public function setHomepage($homepage)
    {
        $this->homepage = $homepage;
    }

    /**
     * Set ID's of users which should belong to this group
     * @param array $arrUsers
     * @see User, User::getUser()
     * @return void
     */
    public function setUsers($arrUsers)
    {
//        $objFWUser = FWUser::getFWUserObject();
        $this->arrUsers = array();
        foreach ($arrUsers as $userId)
        {
            //if ($objFWUser->objUser->getUser($userId)) {
                $this->arrUsers[] = $userId;
            //}
        }
    }


    public function setDynamicPermissionIds($arrPermissionIds)
    {
        $this->arrDynamicPermissions = array_map('intval', $arrPermissionIds);
    }


    public function setStaticPermissionIds($arrPermissionIds)
    {
        $this->arrStaticPermissions = array_map('intval', $arrPermissionIds);
    }


    public function getLoadedGroupCount()
    {
        return count($this->arrLoadedGroups);
    }


    public function getLoadedGroupIds()
    {
        return array_keys($this->arrLoadedGroups);
    }


    public function getGroupCount($arrFilter = null)
    {
        global $objDatabase;

        $arrWhereExpressions = is_array($arrFilter) ? $this->parseFilterConditions($arrFilter) : array('joins' => array(), 'conditions' => array());

        $objGroupCount = $objDatabase->SelectLimit('
            SELECT SUM(1) AS `group_count`
            FROM `'.DBPREFIX.'access_user_groups` AS tblG'
            .(count($arrWhereExpressions['joins']) ? implode(' ', $arrWhereExpressions['joins']) : '')
            .(count($arrWhereExpressions['conditions']) ? ' WHERE '.implode(' AND ', $arrWhereExpressions['conditions']) : ''),
            1
        );

        if ($objGroupCount !== false) {
            return $objGroupCount->fields['group_count'];
        } else {
            return false;
        }
    }


    public function getUserCount($onlyActive = false)
    {
        global $objDatabase;

        $objCount = $objDatabase->SelectLimit('SELECT COUNT(1) AS `user_count` FROM `'.DBPREFIX.'access_users` AS tblUser'
            .($this->id ? ' INNER JOIN `'.DBPREFIX.'access_rel_user_group` AS tblRel ON tblRel.`user_id` = tblUser.`id` WHERE tblRel.`group_id` = '.$this->id : '')
            .($onlyActive ? (!$this->id ? ' WHERE' : ' AND').' tblUser.`active` = 1 ' : ''), 1);
        if ($objCount) {
            return $objCount->fields['user_count'];
        } else {
            return false;
        }
    }


    public function getAssociatedUserIds()
    {
        if (!isset($this->arrUsers)) {
            $this->arrUsers = $this->loadUsers();
        }
        return $this->arrUsers;
    }


    public function getDynamicPermissionIds()
    {
        if (!isset($this->arrDynamicPermissions)) {
            $this->arrDynamicPermissions = $this->loadDynamicPermissions();
        }
        return $this->arrDynamicPermissions;
    }


    public function getStaticPermissionIds()
    {
        if (!isset($this->arrStaticPermissions)) {
            $this->arrStaticPermissions = $this->loadStaticPermissions();
        }
        return $this->arrStaticPermissions;
    }


    public function getId()
    {
        return $this->id;
    }


    public function getName()
    {
        return $this->name;
    }


    public function getDescription()
    {
        return $this->description;
    }


    public function getActiveStatus()
    {
        return $this->is_active;
    }


    public function getType()
    {
        return $this->type;
    }

    public function getHomepage()
    {
        return $this->homepage;
    }

    public function getTypes()
    {
        return $this->arrTypes;
    }


    public function getErrorMsg()
    {
        return $this->error_msg;
    }


    /**
     * Is unique group name
     *
     * Checks if the group name specified by $name is unique in the system.
     *
     * @param string $name
     * @param integer $id
     * @return boolean
     */
    function isUniqueGroupName()
    {
        global $objDatabase, $_CORELANG;

        $objResult = $objDatabase->SelectLimit("SELECT 1 FROM ".DBPREFIX."access_user_groups WHERE `group_name`='".addslashes($this->name)."' AND `group_id` != ".$this->id, 1);

        if ($objResult && $objResult->RecordCount() == 0) {
            return true;
        } else {
            $this->error_msg = $_CORELANG['TXT_ACCESS_DUPLICATE_GROUP_NAME'];
            return false;
        }
    }


    function isValidGroupName()
    {
        global $_CORELANG;

        if (!empty($this->name)) {
            return true;
        } else {
            $this->error_msg = $_CORELANG['TXT_ACCESS_EMPTY_GROUP_NAME'];
            return false;
        }
    }


    /**
     * Returns an array of all available user group names, indexed by their ID
     * @return    array                 The user group name array
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    static function getNameArray()
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("
            SELECT `group_id`, `group_name`
              FROM ".DBPREFIX."access_user_groups");
        if (!$objResult) return false;
        $arrGroupName = array();
        while (!$objResult->EOF) {
            $arrGroupName[$objResult->fields['group_id']] =
                $objResult->fields['group_name'];
            $objResult->MoveNext();
        }
        return $arrGroupName;
    }

}
