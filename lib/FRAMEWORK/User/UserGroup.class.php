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
    protected $toolbar;

    private $arrLoadedGroups = array();
    private $arrCache = array();

    private $arrAttributes = array(
        'group_id',
        'group_name',
        'group_description',
        'is_active',
        'type',
        'homepage',
        'toolbar',
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

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $classMeta = $em->getClassMetadata('Cx\Core\User\Model\Entity\Group');
        $groupRepo = $em->getRepository('Cx\Core\User\Model\Entity\Group');
        $qb = $groupRepo->createQueryBuilder('tblG');
        $qb->select(null);

        // set filter
        if (is_array($filter)) {
            $this->parseFilterConditions($qb, $filter);
        } elseif (!empty($filter)) {
            $qb->andWhere($qb->expr()->eq('tblG.id', ':groupId'));
            $qb->setParameter('groupId', intval($filter));
        }

        // set sort order
        if (is_array($arrSort)) {
            foreach ($arrSort as $attribute => $direction) {
                if (   in_array($attribute, $this->arrAttributes)
                    && in_array(strtolower($direction), array('asc', 'desc'))) {
                    $attribute = $classMeta->getFieldName($attribute);
                    $qb->addOrderBy('tblG.'.$attribute, $direction);
                    $arrSortExpressions[] = 'tblG.`'.$attribute.'` '.$direction;
                }
            }
        }

        // set field list
        if (!is_array($arrAttributes)) {
            $arrAttributes = $this->arrAttributes;
        }
        foreach ($arrAttributes as $attribute) {
            if (in_array($attribute, $this->arrAttributes)) {
                $fieldName = $classMeta->getFieldName($attribute);
                $arrSelectExpressions[$fieldName] = $attribute;
            }
        }

        $arrSelectExpressions['id'] = 'group_id';
        foreach ($arrSelectExpressions as $fieldName=>$attribute) {
            $qb->addSelect('tblG.'.$fieldName.' AS '.$attribute);
        }

        if ($limit) {
            $qb->setMaxResults($limit);
            $qb->setFirstResult(intval($offset));
        }

        $groups = $qb->getQuery()->getArrayResult();


        if (count($groups) > 0) {
            foreach ($groups as $group) {
                $this->arrCache[$group['group_id']] = $this->arrLoadedGroups[$group['group_id']] = $group;
            }
            $this->first();
            return true;
        } else {
            $this->clean();
            return false;
        }
    }


    private function parseFilterConditions($qb, $arrFilter)
    {
        global $objDatabase;
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $metaData = $cx->getDb()->getEntityManager()->getClassMetadata('Cx\Core\User\Model\Entity\Group');

        $arrConditions = array('conditions' => array(), 'joins' => array());
        foreach ($arrFilter as $attribute => $condition) {
            switch ($attribute) {
                case 'group_name':
                case 'group_description':
                    $attribute = $metaData->getFieldName($attribute);
                    $qb->andWhere($qb->expr()->like('tblG.'.$attribute, ':'.$attribute));
                    $qb->setParameter($attribute, '%'.addslashes($condition).'%');
                    break;

                case 'is_active':
                    $qb->andWhere($qb->expr()->eq('tblG.active', ':isActive'));
                    $qb->setParameter('isActive', intval($condition));
                    break;

                case 'type':
                    $qb->andWhere($qb->expr()->eq('tblG.type', ':type'));
                    $qb->setParameter('type', addslashes($condition));
                   break;

                case 'static':
                case 'dynamic':
                    $result = $objDatabase->Execute(
                        'SELECT `group_id` FROM `'.DBPREFIX.'access_group_'.$attribute.'_ids`'
                    );
                    $groupIds = array();
                    if ($result) {
                        while (!$result->EOF) {
                            $groupIds[] = $result->fields['group_id'];
                            $result->MoveNext();
                        }
                    }
                    $qb->andWhere($qb->expr()->in('tblG.id', ':groupIds'));
                    $qb->setParameter('groupIds', $groupIds);
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
            $this->toolbar = isset($this->arrCache[$id]['toolbar']) ? $this->arrCache[$id]['toolbar'] : '';
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
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $groupRepo = $em->getRepository('Cx\Core\User\Model\Entity\Group');
        $qb = $groupRepo->createQueryBuilder('g');
        $qb->select('u.id AS userId')
           ->join('g.users', 'u')
           ->where($qb->expr()->eq('g.id', ':groupId'))
           ->orderBy('u.username')
           ->setParameter('groupId', $this->id);

        $users = $qb->getQuery()->getArrayResult();

        $arrUsers = array();
        foreach($users as $user) {
            array_push($arrUsers, $user['userId']);
        }

        return $arrUsers;
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
        global $_CORELANG;

        if (!$this->isUniqueGroupName() || !$this->isValidGroupName()) {
            $this->error_msg = $_CORELANG['TXT_ACCESS_GROUP_NAME_INVALID'];
            return false;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $groupRepo = $em->getRepository('Cx\Core\User\Model\Entity\Group');
        $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
        $group = $groupRepo->findOneBy(array('id' => $this->id));

        if (empty($group)) {
            $group = new \Cx\Core\User\Model\Entity\Group();
            $group->setType($this->type);
        }

        $group->setName(addslashes($this->name));
        $group->setDescription(addslashes($this->description));
        $group->setActive(intval($this->is_active));
        $group->setHomepage(addslashes($this->homepage));
        $group->setToolbar(intval($this->toolbar));

        // Store permissions
        $arrCurrentUsers = $this->loadUsers();
        $arrAddedUsers = array_diff($this->getAssociatedUserIds(), $arrCurrentUsers);
        $arrRemovedUsers = array_diff($arrCurrentUsers, $this->getAssociatedUserIds());

        foreach ($arrRemovedUsers as $userId) {
            $user = $userRepo->find($userId);
            $group->removeUser($user);
            $user->removeGroup($group);
            $em->persist($user);
        }

        foreach ($arrAddedUsers as $userId) {
            $user = $userRepo->find($userId);
            $group->addUser($user);
            $user->addGroup($group);
            $em->persist($user);
        }

        try {
            $em->persist($group);
            $em->flush();
            $this->id = $group->getId();
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            $this->error_msg = $_CORELANG['TXT_ACCESS_FAILED_TO_UPDATE_GROUP'];
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
        global $_CORELANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $groupRepo = $em->getRepository('Cx\Core\User\Model\Entity\Group');
        $group = $groupRepo->findOneBy(array('groupId' => $this->id));

        try {
            $em->remove($group);
            $em->flush();
            return true;
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
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

    /**
     * Set toolbar
     *
     * @param int $toolbar toolbar id
     */
    public function setToolbar($toolbar)
    {
        $this->toolbar = $toolbar;
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
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $groupRepo = $cx->getDb()->getEntityManager()->getRepository('Cx\Core\User\Model\Entity\Group');
        $qb = $groupRepo->createQueryBuilder('tblG');
        $qb->select('COUNT(tblG.id) as group_count');

        if (is_array($arrFilter)) {
            $this->parseFilterConditions($qb,$arrFilter);
        }

        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Doctrine\ORM\Query\QueryException $e) {
            return false;
        }
    }


    public function getUserCount($onlyActive = false)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $userRepo = $cx->getDb()->getEntityManager()->getRepository('Cx\Core\User\Model\Entity\User');
        $qb = $userRepo->createQueryBuilder('u');
        $qb->select('COUNT(u.id) as user_count');

        if ($this->id) {
            $qb->innerJoin('u.groups', 'g')
               ->where($qb->expr()->eq('g.id', ':groupId'))
               ->setParameter('groupId', $this->id);
        }

        if ($onlyActive) {
            $qb->andWhere($qb->expr()->eq('u.active', ':active'))->setParameter('active', true);
        }
        return $qb->getQuery()->getSingleScalarResult();
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

    public function getToolbar()
    {
        return $this->toolbar;
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
        global $_CORELANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $groupRepo = $cx->getDb()->getEntityManager()->getRepository('Cx\Core\User\Model\Entity\Group');
        $group = $groupRepo->findOneBy(array('name' => addslashes($this->name)));

        if (empty($group) || $group->getGroupId() == $this->id) {
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
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $groupRepo = $cx->getDb()->getEntityManager()->getRepository('Cx\Core\User\Model\Entity\Group');
        $groups = $groupRepo->findAll();

        $arrGroupName = array();
        foreach ($groups as $group) {
            $arrGroupName[$group->getId()] = $group->getName();
        }

        return $arrGroupName;
    }

}
