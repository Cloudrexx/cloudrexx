<?php
/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2019
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
 * JsonController for DataAccess
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 * @version     5.0.0
 */
namespace Cx\Core\User\Controller;


class JsonUserController
    extends \Cx\Core\Core\Model\Entity\Controller
    implements \Cx\Core\Json\JsonAdapter
{
    /**
     * @var array messages from this controller
     */
    protected $messages;

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName()
    {
        return 'User';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     *
     * @return array List of method names
     */
    public function getAccessableMethods()
    {
        return array(
            'getAttributeValues',
            'storeUserAttributeValue',
            'getRoleIcon',
            'filterCallback',
            'searchCallback',
            'storeNewsletter',
            'storeDownloadExtension',
            'storeOnlyNewsletterLists',
            'matchWithConfirmedPassword',
            'setRegDate'
        );
    }

    /**
     * Returns all messages as string
     *
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString()
    {
        return implode('<br />', $this->messages);
    }

    /**
     * Returns default permission as object
     *
     * @return \Cx\Core_Modules\Access\Model\Entity\Permission
     */
    public function getDefaultPermissions()
    {
        $permission = new \Cx\Core_Modules\Access\Model\Entity\Permission(
            array('http', 'https'),
            array('get', 'post'),
            true,
            array()
        );

        return $permission;
    }

    /**
     * Store UserAttribute manually because these are custom options
     *
     * @param $param array parameters for callback
     * @throws \Cx\Core\Error\Model\Entity\ShinyException
     */
    public function storeUserAttributeValue($param)
    {
        global $_ARRAYLANG;

        if (empty($param['fieldName']) || empty($param['entity'])) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG['TXT_CORE_USER_NOT_FOUND']
            );
        }
        $fieldName = $param['fieldName'];
        $user = $param['entity'];
        $attrId = explode('-', $fieldName)[1];

        if (empty($attrId)) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG['TXT_CORE_USER_ATTRIBUTE_NOT_FOUND']
            );
        }

        $em = $this->cx->getDb()->getEntityManager();
        $attr = $em->getRepository(
            'Cx\Core\User\Model\Entity\UserAttribute'
        )->findOneBy(array('id' => $attrId));
        $attrValue = $em->getRepository(
            'Cx\Core\User\Model\Entity\UserAttributeValue'
        )->findOneBy(
            array(
                'userId' => $user->getId(),
                'attributeId' => $attrId
            )
        );

        if (empty($attrValue)) {
            $attrValue = new \Cx\Core\User\Model\Entity\UserAttributeValue();
            $attrValue->setUserId($user->getId());
            $attrValue->setUser($user);
            $attrValue->setAttributeId($attrId);
            $attrValue->setUserAttribute($attr);
            $attrValue->setHistory(0);
        }
        $attrValue->setValue($param['postedValue']);
        $em->persist($attrValue);
        $user->addUserAttributeValue($attrValue);
    }

    /**
     * Get the appropriate role icon to display the roles graphically
     *
     * @param $params array params for table->parse callback
     * @return \Cx\Core\Html\Model\Entity\HtmlElement image with role icon
     */
    public function getRoleIcon($params)
    {
        global $_ARRAYLANG;

        if (empty($params['data'])) {
            $source = $this->cx->getCodeBaseCoreWebPath()
                . '/Core/View/Media/icons/no_admin.png';
            $title = $_ARRAYLANG['TXT_CORE_USER_NO_ADMINISTRATOR'];
        } else {
            $source = $this->cx->getCodeBaseCoreWebPath()
                . '/Core/View/Media/icons/admin.png';
            $title = $_ARRAYLANG['TXT_CORE_USER_ADMINISTRATOR'];
        }

        $img = new \Cx\Core\Html\Model\Entity\HtmlElement('img');
        $img->setAttributes(
            array(
                'title' => $title,
                'src' => $source,
                'class' => 'user-is-admin'
            )
        );

        return $img;
    }

    public function getAttributeValues($par)
    {
        // Todo: Übernehmen der ValueCallback Funktion
    }

    /**
     * Custom filter callback function to filter the users by user groups and
     * account type
     *
     * @param $params array contains all params for filter callback
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function filterCallback($params)
    {
        $qb = $params['qb'];
        $crit = $params['crit'];

        $i = 1;
        foreach ($crit as $field=>$value) {
            if ($field == 'group') {
                $qb->andWhere('?'. $i .' MEMBER OF x.group');
            } else if ($field == 'accountType') {
                continue;
                //$arrCustomJoins[] = 'INNER JOIN `'.DBPREFIX.'module_crm_contacts` AS tblCrm ON tblCrm.`user_account` = tblU.`id`';
                $qb->join(
                    'Cx\Modules\Crm\Model\Entity\CrmContact',
                    'c',
                    'WITH',
                    'c.userAccount = u.id'
                );
                continue;
            } else {
                $qb->andWhere(
                    $qb->expr()->eq('x.' . $field, '?' . $i)
                );
            }

            $qb->setParameter($i, $value);
            $i++;
        }

        return $qb;
    }

    /**
     * Custom filter callback function to filter the users by user groups and
     * account type
     *
     * @param $params array contains all params for filter callback
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function searchCallback($params)
    {
        $qb = $params['qb'];
        $fields = $params['fields'];
        $crit = $params['crit'];

        // Default
        $orX = new \Doctrine\DBAL\Query\Expression\CompositeExpression(
            \Doctrine\DBAL\Query\Expression\CompositeExpression::TYPE_OR
        );
        $i = 1;
        $joinTable = true;
        foreach ($fields as $field) {
            if (preg_match('/userAttr-\d+/', $field)) {
                if ($joinTable) {
                    $qb->leftJoin(
                        'Cx\Core\User\Model\Entity\UserAttributeValue',
                        'av',
                        'WITH',
                        'av.userId = x.id'
                    );
                    $joinTable = false;
                }
                $orX->add($qb->expr()->like('av.value', ':search'));
            } else {
                $orX->add($qb->expr()->like('x.' . $field, ':search'));
            }
        }
        $qb->andWhere($orX);
        $qb->setParameter('search', '%' . $crit . '%');

        return $qb;
    }

    public function storeNewsletter($params)
    {
        global $_ARRAYLANG, $objDatabase;

        if (!isset($params) || empty($params['entity'])) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG['TXT_CORE_USER_NOT_FOUND']
            );
        }
        $user = $params['entity'];
        $values = array();
        if ($this->cx->getRequest()->hasParam('newsletter', false)) {
            $values = $this->cx->getRequest()->getParam('newsletter', false);
        }

        // Original FWUSer storeNewsletterSubscriptions
        if (count($values)) {
            foreach ($values as $key) {
                $query = sprintf(
                    'INSERT IGNORE INTO `%smodule_newsletter_access_user`
                    (
                        `accessUserId`, `newsletterCategoryID`, `code`
                    ) VALUES (
                        %s, %s, \'%s\'
                    )',
                    DBPREFIX,
                    $user->getId(),
                    intval($key),
                    \Cx\Modules\Newsletter\Controller\NewsletterLib::_emailCode()
                );
                $objDatabase->Execute($query);
            }
            $delString = implode(',', $values);
            $query = sprintf(
                'DELETE FROM `%smodule_newsletter_access_user`
                WHERE `newsletterCategoryID` NOT IN (%s)
                AND `accessUserId`=%s',
                DBPREFIX,
                $delString,
                $user->getId()
            );
        } else {
            $query = sprintf(
                'DELETE FROM `%smodule_newsletter_access_user`
                WHERE `accessUserId`=%s',
                DBPREFIX,
                $user->getId()
            );
        }

        if ($objDatabase->Execute($query) === false) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG['TXT_CORE_USER_NOT_BE_SAVED']
            );
        }
    }

    /**
     * Creates a new user group and assigns it to the user. A new category is
     * created in the DAM, which is named after the user and assigned to the
     * created user group
     *
     * @param $params array contains the user and the entity manager
     * @return bool if the category could be created
     */
    public function storeDownloadExtension($params)
    {
        global $_ARRAYLANG;

        if (
            !isset($params) ||
            empty($params['entity']) ||
            empty($params['postedValue'])
        ) {
            return null;
        }
        $user = $params['entity'];
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $objUser = \FWUser::getFWUserObject()->objUser->getUser($user->getId());
        $objDownloadLib = new \Cx\Modules\Downloads\Controller\DownloadsLibrary();
        $arrDownloadSettings = $objDownloadLib->getSettings();

        // Set associated download groups
        $groupIds = array();
        if ($objUser) {
            $groupIds = array_merge(
                $objUser->getAssociatedGroupIds(),
                array_map(
                    'trim',
                    explode(
                        ',',
                        $arrDownloadSettings['associate_user_to_groups']
                    )
                )
            );
        };
        if (!empty($groupIds) && !empty($groupIds[0])) {
            $groupRepo = $em->getRepository(
                'Cx\Core\Model\Entity\Group'
            );
            foreach ($groupRepo->findBy($groupIds) as $group) {
                $user->addGroup($group);
            }
            $em->persist($user);
        }

        $userName = $user->__toString();

        $group = new \Cx\Core\User\Model\Entity\Group();
        $group->setGroupName(
            sprintf($_ARRAYLANG['TXT_CORE_USER_CUSTOMER_TITLE'], $userName)
        );
        $group->setGroupDescription(
            sprintf($_ARRAYLANG['TXT_CORE_USER_ACCOUNT_GROUP_DESC'], $userName)
        );
        $group->setIsActive(true);
        $group->setType('frontend');
        $group->addUser($user);
        $em->persist($group);
        $em->flush();
        $user->addGroup($group);

        $arrLanguageIds = array_keys(\FWLanguage::getLanguageArray());
        $arrNames = array();
        $arrDescription = array();
        foreach ($arrLanguageIds as $langId) {
            $arrNames[$langId] = sprintf(
                $_ARRAYLANG['TXT_CORE_USER_CUSTOMER_TITLE'],
                $userName
            );
            $arrDescription[$langId] = '';
        }

        $objCategory = new \Cx\Modules\Downloads\Controller\Category();
        $objCategory->setActiveStatus(true);
        $objCategory->setVisibility(false);
        $objCategory->setNames($arrNames);
        $objCategory->setDescriptions($arrDescription);
        $objCategory->setOwner($user->getId());
        $objCategory->setDeletableByOwner(false);
        $objCategory->setModifyAccessByOwner(false);
        $objCategory->setPermissions(
            array(
                'read'  => array(
                    'protected' => true,
                    'groups'    => array($group->getGroupId())
                ),
                'add_subcategories' => array(
                    'protected' => true,
                    'groups'    => array($group->getGroupId())
                ),
                'manage_subcategories' => array(
                    'protected' => true,
                    'groups'    => array($group->getGroupId())
                ),
                'add_files' => array(
                    'protected' => true,
                    'groups'    => array($group->getGroupId())
                ),
                'manage_files' => array(
                    'protected' => true,
                    'groups'    => array($group->getGroupId())
                )
            )
        );

        if (!$objCategory->store()) {
            return $user;
        }

        $damCategoryUrl = \Cx\Core\Routing\Url::fromBackend('Downloads');
        $damCategoryUrl->setParams(
            array(
                'act' => 'categories',
                'parent_id' => $objCategory->getId()
            )
        );
        $damCategoryAnchor = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
        $damCategoryAnchor->setAttribute('href', $damCategoryUrl);
        $damCategoryAnchorText = new \Cx\Core\Html\Model\Entity\TextElement(
            htmlentities(
                $objCategory->getName(LANG_ID), ENT_QUOTES, CONTREXX_CHARSET
            )
        );
        $damCategoryAnchor->addChild($damCategoryAnchorText);

        $message = sprintf(
            $_ARRAYLANG['TXT_CORE_USER_NEW_DAM_CATEGORY_CREATED_TXT'],
            $user->__toString(),
            $damCategoryAnchor
        );

        \Message::add($message);
        return $user;
    }

    public function matchWithConfirmedPassword($params)
    {
        global $_CORELANG;

        $newPassword = $params['entity']->getPassword();
        $confirmedPassword = $params['postedValue'];

        if (!empty($params['entityData']['password'])) {
            if (
                empty($confirmedPassword) ||
                !password_verify($confirmedPassword, $newPassword)
            ) {
                throw new \Cx\Core\Error\Model\Entity\ShinyException(
                    $_CORELANG['TXT_ACCESS_PASSWORD_NOT_CONFIRMED']
                );
            }
            return;
        }
    }

    /**
     * Set the date of registration, but only if the entity is created
     *
     * @param $params array information for storecallback includes the entity
     */
    public function setRegDate($params)
    {
        if (
            empty($params['entity']) ||
            empty($params['entity']->getRegDate())
        ) {
            $date = new \DateTime();
            return $date->getTimestamp();
        }
    }
}
