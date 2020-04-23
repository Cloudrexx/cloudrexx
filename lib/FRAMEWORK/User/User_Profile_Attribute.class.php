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
 * User Profile Attribute Object
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Daeppen <thomas.daeppen@comvation.com>
 * @version     2.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
/**
 * User Profile Attribute Object
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Daeppen <thomas.daeppen@comvation.com>
 * @version     2.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */

class User_Profile_Attribute
{
    public $EOF;

    private $id;
    private $type;
    private $sort_type;
    private $order_id;
    private $access_special;
    private $access_id;
    private $access_group_ids;
    private $protected;
    private $mandatory;
    private $parent_id;
    private $children;
    private $multiline;
    private $customized;
    private $modifiable;
    private $arrName;
    private $arrAttributes;
    private $langId;

    /**
     * Read Access id
     *
     * @var integer
     */
    protected $readAccessId;

    /**
     * Read Group access ids
     *
     * @var array
     */
    protected $readAccessGroupIds;

    /**
     * Read protection status(1 or 0)
     *
     * @var boolean
     */
    protected $readProtected;

    private $arrAttributeTree;
    private $arrAttributeRelations;
    private $arrDefaultAttributeIds;
    private $arrCustomAttributes;
    private $arrMandatoryAttributes = array();
    private $arrDefaultAttributeNames = array();

    private $arrDefaultAttributeTemplates = array(
        'picture' => array(
            'type'         => 'image',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_PROFILE_PIC',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'gender' => array(
            'type'         => 'menu',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'custom',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_GENDER',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'gender_undefined' => array(
            'type'         => 'menu_option',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 'gender',
            'desc'         => 'TXT_ACCESS_NOT_SPECIFIED',
            'unknown'      => true,
            'order_id'     => 0,
        ),
        'gender_female' => array(
            'type'         => 'menu_option',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 'gender',
            'desc'         => 'TXT_ACCESS_FEMALE',
            'order_id'     => 1
        ),
        'gender_male' => array(
            'type'         => 'menu_option',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 'gender',
            'desc'         => 'TXT_ACCESS_MALE',
            'order_id'     => 2,
        ),
        'title' => array(
            'type'         => 'menu',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'desc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_TITLE',
            'modifiable'   => array('mandatory', 'sort_order', 'access', 'children'),
        ),
        'title_undefined' => array(
            'type'         => 'menu_option',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 'title',
            'desc'         => 'TXT_ACCESS_NOT_SPECIFIED',
            'value'        => '0',
            'unknown'      => true,
            'order_id'     => 0,
        ),
        'designation' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'desc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_DESIGNATION',
        ),
        'firstname' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_FIRSTNAME',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'lastname' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_LASTNAME',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'company' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_COMPANY',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'address' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_ADDRESS',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'city' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_CITY',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'zip' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_ZIP',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'country' => array(
            'type'         => 'menu',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_COUNTRY',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'country_undefined' => array(
            'type'         => 'menu_option',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 'country',
            'desc'         => 'TXT_ACCESS_NOT_SPECIFIED',
            'value'        => '0',
            'unknown'      => true,
            'order_id'     => 0,
        ),
        'phone_office' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_PHONE_OFFICE',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'phone_private' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_PHONE_PRIVATE',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'phone_mobile' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_PHONE_MOBILE',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'phone_fax' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_PHONE_FAX',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'birthday' => array(
            'type'         => 'date',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_BIRTHDAY',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'website' => array(
            'type'         => 'uri',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_WEBSITE',
            'modifiable'   => array('mandatory', 'access'),
        ),
        /*'skype' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_SKYPE_NAME',
            'modifiable'   => array('mandatory', 'access'),
        ),*/
        'profession' => array(
            'type'         => 'text',
            'multiline'    => false,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_PREFESSION',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'interests' => array(
            'type'         => 'text',
            'multiline'    => true,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_INTERESTS',
            'modifiable'   => array('mandatory', 'access'),
        ),
        'signature' => array(
            'type'         => 'text',
            'multiline'    => true,
            'mandatory'    => false,
            'sort_type'    => 'asc',
            'parent_id'    => 0,
            'desc'         => 'TXT_ACCESS_SIGNATURE',
            'modifiable'   => array('mandatory', 'access'),
        ),
    );

    private $arrTypes = array(
        'text' => array(
            'desc'         => 'TXT_ACCESS_TEXT_FIELD',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => false,
            'multiline'    => true,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'string',
        ),
        'mail' => array(
            'desc'         => 'TXT_ACCESS_EMAIL_ADDRESS',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => false,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'string',
        ),
        'uri' => array(
            'desc'         => 'TXT_ACCESS_WEB_ADDRESS',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => false,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'string',
        ),
        'date' => array(
            'desc'         => 'TXT_ACCESS_DATE',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => false,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'int',
        ),
        'image' => array(
            'desc'         => 'TXT_ACCESS_IMAGE',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => false,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'string',
        ),
        'checkbox' => array(
            'desc'         => 'TXT_ACCESS_CHECKBOX',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => false,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'int',
        ),
        'menu' => array(
            'desc'         => 'TXT_ACCESS_MENU',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => true,
            'children'     => true,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array('', 'menu_select_higher', 'menu_select_lower'),
            'data_type'    => 'int',
        ),
        'menu_option' => array(
            'desc'         => 'TXT_ACCESS_MENU_OPTION',
            'parent'       => 'TXT_ACCESS_MENU',
            'mandatory'    => false,
            'children'     => false,
            'multiline'    => false,
            'movable'      => false,
            'protection'   => false,
            'special'      => array(),
            'data_type'    => 'null',
        ),
        'group' => array(
            'desc'         => 'TXT_ACCESS_GROUP',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => false,
            'children'     => true,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => false,
            'special'      => array(),
            'data_type'    => 'array',
        ),
        'frame' => array(
            'desc'         => 'TXT_ACCESS_FRAME',
            'parent'       => 'TXT_ACCESS_GROUP',
            'mandatory'    => false,
            'children'     => true,
            'multiline'    => false,
            'movable'      => false,
            'protection'   => false,
            'special'      => array(),
            'data_type'    => 'array',
        ),
        'history' => array(
            'desc'         => 'TXT_ACCESS_HISTORY',
            'parent'       => 'TXT_ACCESS_PARENT_ATTRIBUTE',
            'mandatory'    => false,
            'children'     => true,
            'multiline'    => false,
            'movable'      => true,
            'protection'   => true,
            'special'      => array(),
            'data_type'    => 'array',
        ),
    );

    private $arrTypeAssociation = array(
        '' => array(
            'text',
            'mail',
            'uri',
            'date',
            'image',
            'checkbox',
            'menu',
            'group',
            'history',
        ),
        'text' => array(),
        'mail' => array(),
        'uri' => array(),
        'date' => array(),
        'image' => array(),
        'checkbox' => array(),
        'menu' => array(
            'menu_option', ),
        'group' => array(
            'frame', ),
        'frame' => array(
            'text',
            'mail',
            'uri',
            'date',
            'image',
            'checkbox',
            'menu',
            'group',
            'history',
        ),
        'history' => array(
            'text',
            'mail',
            'uri',
            'date',
            'image',
            'checkbox',
            'menu',
            'group',
            'history',
        ),
    );

    private $arrSortTypes = array(
        'asc' => 'TXT_ACCESS_ASCENDING',
        'desc' => 'TXT_ACCESS_DESCENDING',
        'custom' => 'TXT_ACCESS_CUSTOM',
    );

    private $defaultAttributeType = 'text';
    private $defaultSortType = 'asc';

    private $errorMsg = '';


    function __construct()
    {
        global $_LANGID, $objInit;

        // this is a crapy solution! but the problem is, that this class gets initialized before the backend language ID is loaded.
        $this->langId = $_LANGID ? $_LANGID : (isset($objInit) ? ($objInit->mode == 'frontend' ? $objInit->defaultFrontendLangId : $objInit->defaultBackendLangId) : 1);

        $this->init();
        $this->first();
    }


    function init()
    {
        $this->arrAttributes = null;
        $this->arrAttributeRelations = null;
        $this->arrAttributeTree = null;

        $this->loadAttributes();
        $this->loadCoreAttributeCountry();
        $this->generateAttributeRelations();
        $this->sortChildren();
    }

    function loadCoreAttributeCountry()
    {
        global $objDatabase;

        $countries = \Cx\Core\Country\Controller\Country::getArray($count, $this->langId);
        foreach($countries as $country) {
            $this->arrAttributes['country_'.$country['id']] = array(
                'type' => 'menu_option',
                'multiline' => false,
                'mandatory' => false,
                'sort_type' => 'asc',
                'parent_id' => $this->getAttributeIdByDefaultAttributeId('country'),
                'desc' => $country['name'],
                'names' => array($this->langId => $country['name']),
                'value' => $country['id'],
                'order_id' => 0,
            );
        }
    }

    /**
     * Find all default user attributes and store it in an
     * array
     */
    function loadAttributes()
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $frontend = $cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND;
        $_CORELANG = \Env::get('init')->getComponentSpecificLanguageData('Core', $frontend);
        $attributeRepo = $cx->getDb()->getEntityManager()->getRepository('Cx\Core\User\Model\Entity\UserAttribute');

        $this->arrCustomAttributes = array();
        $attributes = $attributeRepo->findBy(array(), array('orderId' => 'asc', 'id' => 'asc'));

        if (empty($attributes)) {
            return;
        }

        foreach ($attributes as $attribute) {
            $attribute->setTranslatableLocale($this->langId);
            $cx->getDb()->getEntityManager()->refresh($attribute);
            $attributeId = $attribute->getId();
            $this->arrAttributes[$attributeId]['type'] = $attribute->getType() == 'textarea' ? 'text' : $attribute->getType();
            $this->arrAttributes[$attributeId]['multiline'] = $attribute->getType() == 'textarea' ? true : false;
            $this->arrAttributes[$attributeId]['sort_type'] = $attribute->getSortType();
            $this->arrAttributes[$attributeId]['order_id'] = $attribute->getOrderId();
            $this->arrAttributes[$attributeId]['mandatory'] = $attribute->getMandatory();
            $parent = $attribute->getParent();
            if ($parent === null) {
                $parentId = 0;
            } else {
                $parentId = $parent->getId();
            }
            $this->arrAttributes[$attributeId]['parent_id'] = $parentId;

            $name = $attribute->getName();
            if (!$attribute->isDefault()) {
                $this->arrAttributes[$attributeId]['modifiable'] = array('type', 'sort_order', 'mandatory', 'parent_id', 'access', 'children');
                $this->arrCustomAttributes[] = $attributeId;

            } else {
                if (!$parentId) {
                    $this->arrDefaultAttributeIds[] = $attributeId;
                }
                $this->arrDefaultAttributeNames[$attributeId] = $attribute->getName();

                if (isset($this->arrDefaultAttributeTemplates[$attribute->getName()])) {
                    $arrTemplate = $this->arrDefaultAttributeTemplates[$attribute->getName()];
                    $desc = $arrTemplate['desc'];
                    $name = isset($_CORELANG[$desc]) ? $_CORELANG[$desc] : null;
                    if (!isset($arrTemplate['modifiable'])) {
                        $this->arrAttributes[$attributeId]['modifiable'] = array();
                    } else {
                        $this->arrAttributes[$attributeId]['modifiable'] = $arrTemplate['modifiable'];
                    }
                } else if ($parentId == $this->getAttributeIdByDefaultAttributeId('title')) {
                    $this->arrAttributes[$attributeId]['modifiable'] = array('names');
                } else {
                    $this->arrAttributes[$attributeId]['modifiable'] = array();
                }
            }
            $this->arrAttributes[$attributeId]['access_special'] = $attribute->getAccessSpecial();
            $this->arrAttributes[$attributeId]['access_id'] = $attribute->getAccessId();
            $this->arrAttributes[$attributeId]['read_access_id'] = $attribute->getReadAccessId();
            $this->arrCustomAttributes[] = $attributeId;
            if ($attribute->getMandatory()) {
                $this->arrMandatoryAttributes[] = $attributeId;
            }
            $this->arrAttributes[$attributeId]['names'][$this->langId] = $name;
        }
    }

    /**
     * In the system, the ID of a core attribute is the name of the attribute
     * (e.g. 'title'). But if we want to interact with the database, the core
     * attribute is handled like other user attributes. This means we have an
     * integer ID (e.g. 2) and had to track this ID (2) with the system-intern
     * core attribute ID ('title')
     *
     * This method returns an array with the user attribute ID (e.g 2) as array
     * key profile attribute ID (e.g. 'title') as value
     *
     * @return array value with all profile attributes
     */
    public function getDefaultAttributeNames()
    {
        return $this->arrDefaultAttributeNames;
    }

    function getTree()
    {
        if (empty($this->arrAttributeTree)) {
            $this->arrAttributeTree = $this->generateAttributeTree();
        }
        return $this->arrAttributeTree;
    }


    function generateAttributeRelations()
    {
        foreach ($this->arrAttributes as $attribute => $arrAttribute) {
            $this->arrAttributeRelations[$arrAttribute['parent_id']][] = $attribute;
        }
    }


    function generateAttributeTree($parentId = 0)
    {
        $arrTree = array();
        if (isset($this->arrAttributeRelations[$parentId])) {
            foreach ($this->arrAttributeRelations[$parentId] as $attributeId) {
                $arrTree[$attributeId] = array(
                    'type' => $this->arrAttributes[$attributeId]['type'],
                    'mandatory' => $this->arrAttributes[$attributeId]['mandatory'],
                );
                if (count($arrChildren = $this->generateAttributeTree($attributeId))) {
                    $arrTree[$attributeId]['children'] = $arrChildren;
                }
            }
        }
        return $arrTree;
    }


    public function getById($id)
    {
        $objAttribute = clone $this;
        $objAttribute->arrAttributes = &$this->arrAttributes;
        $objAttribute->arrAttributeTree = &$this->arrAttributeTree;
        $objAttribute->arrAttributeRelations = &$this->arrAttributeRelations;
        $objAttribute->arrMandatoryAttributes = &$this->arrMandatoryAttributes;
        $objAttribute->arrDefaultAttributeIds = &$this->arrDefaultAttributeIds;
        $objAttribute->arrCustomAttributes = &$this->arrCustomAttributes;

        if ($objAttribute->load($id)) {
            return $objAttribute;
        }

        // reset attribute (ID=0)
        $objAttribute->clean();
        return $objAttribute;
    }

    /**
     * Get all images from all image attributes. Empty values are excluded
     *
     * @param int   $limit  limit of database query
     * @param int   $offset offset of database query
     * @param int   $count  count of entries
     * @param bool  $profilePics limit to profile pictures
     * @return array images names
     */
    public function getImages($limit, $offset, &$count, $profilePics)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $attributeRepo = $cx->getDb()->getEntityManager()->getRepository('Cx\Core\User\Model\Entity\UserAttribute');
        $qb = $attributeRepo->createQueryBuilder('a');
        $qb->select('SUM(1) AS entryCount')
           ->innerJoin('a.userAttributeValues', 'v')
           ->where($qb->expr()->eq('a.type', ':type'))
           ->andWhere($qb->expr()->not($qb->expr()->eq('v.value', ':value')))
           ->setParameters(array('type' => 'image', 'value' => ''));

        if ($profilePics) {
            $expression = $qb->expr()->eq('a.id', ':picId');
        } else {
            $expression = $qb->expr()->not($qb->expr()->eq('a.id', ':picId'));
        }
        $picId = $this->getAttributeIdByDefaultAttributeId('picture');
        $qb->andWhere($expression);
        $qb->setParameter('picId', $picId);
        
 	    $count = $qb->getQuery()->getSingleScalarResult();
        if (!$count) {
            return array();
        }

        $qb->select('v.value AS picture');
        $qb->setMaxResults($limit);
        $qb->setFirstResult($offset);

        $resultImages = $qb->getQuery()->getArrayResult();
        if (empty($resultImages)) {
            return array();
        }

        return array_map(function($image) {
            return $image['picture'];
        }, $resultImages);
    }

    /**
     * get attribute id by attribut name
     *
     * @param string $name
     * @global $objDatabase
     * @return int id or false if not found
     */
    function getAttributeIdByName($name)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $attributeRepo = $cx->getDb()->getEntityManager()->getRepository('Cx\Core\User\Model\Entity\UserAttribute');
        $attribute = $attributeRepo->findOneBy(array('name' => $name));

        if ($attribute) {
            return $attribute->getId();
        }
        return false;
    }

    /**
     * Load attribute
     *
     * @param integer $id
     */
    function load($id)
    {
        if ($this->isDefaultAttribute($id)) {
            $id = $this->getAttributeIdByDefaultAttributeId($id);
        }

        if (isset($this->arrAttributes[$id])) {
            $this->id = $id;
            $this->type = isset($this->arrAttributes[$id]['type']) ? $this->arrAttributes[$id]['type'] : $this->defaultAttributeType;
            $this->sort_type = isset($this->arrAttributes[$id]['sort_type']) ? $this->arrAttributes[$id]['sort_type'] : $this->defaultSortType;
            $this->order_id = isset($this->arrAttributes[$id]['order_id']) ? $this->arrAttributes[$id]['order_id'] : 0;
            $this->mandatory = isset($this->arrAttributes[$id]['mandatory']) ? $this->arrAttributes[$id]['mandatory'] : 0;
            $this->parent_id = isset($this->arrAttributes[$id]['parent_id']) ? $this->arrAttributes[$id]['parent_id'] : 0;
            $this->access_special = isset($this->arrAttributes[$id]['access_special']) ? $this->arrAttributes[$id]['access_special'] : '';
            $this->access_id = isset($this->arrAttributes[$id]['access_id']) ? $this->arrAttributes[$id]['access_id'] : 0;
            $this->readAccessId = isset($this->arrAttributes[$id]['read_access_id']) ? $this->arrAttributes[$id]['read_access_id'] : 0;
            $this->children = isset($this->arrAttributeRelations[$id]) ? $this->arrAttributeRelations[$id] : array();
            $this->arrName = isset($this->arrAttributes[$id]['names']) ? $this->arrAttributes[$id]['names'] : array();
            $this->multiline = isset($this->arrAttributes[$id]['multiline']) ? $this->arrAttributes[$id]['multiline'] : false;
            $this->protected = (bool)$this->access_id;
            $this->readProtected = (bool)$this->readAccessId;
            $this->customized = isset($this->arrAttributes[$id]['customizing']) && (bool)$this->arrAttributes[$id]['customizing'];
            $this->modifiable = isset($this->arrAttributes[$id]['modifiable']) ? $this->arrAttributes[$id]['modifiable'] : array();
            return true;
        }
        $this->clean();
        return false;
    }


    function sortChildren()
    {
        foreach ($this->arrAttributeRelations as $attributeId => $arrAttributeIds)
        {
            // do not sort the attributes of the first level. this may be changed some time, as soon as it will be posible to define the sort order of the first level.
            if (!$attributeId) {
                continue;
            }

            $arrChildren = array();
            if (isset($this->arrAttributes[$attributeId]['sort_type']) && $this->arrAttributes[$attributeId]['sort_type'] == 'custom') {
                foreach ($arrAttributeIds as $childAttributeId) {
                    $arrChildren[$childAttributeId] = $this->arrAttributes[$childAttributeId]['order_id'];
                }
                asort($arrChildren, SORT_NUMERIC);
            } else {
                $unknownMenuOption = null;
                foreach ($arrAttributeIds as $childAttributeId) {
                    if (empty($unknownMenuOption)
                        && $this->arrAttributes[$childAttributeId]['type'] == 'menu_option'
                        && !empty($this->arrAttributes[$childAttributeId]['unknown'])
                    ) {
                        $unknownMenuOption = array($childAttributeId => $this->arrAttributes[$childAttributeId]['names'][$this->langId]);
                    } else {
                        $arrChildren[$childAttributeId] = $this->arrAttributes[$childAttributeId]['names'][$this->langId];
                    }
                }

                if (isset($this->arrAttributes[$attributeId]['sort_type']) && $this->arrAttributes[$attributeId]['sort_type'] == 'desc') {
                    arsort($arrChildren, SORT_STRING);
                } else {
                    asort($arrChildren, SORT_STRING);
                }

                if (!empty($unknownMenuOption)) {
                    $arrChildren = array_merge($unknownMenuOption, $arrChildren);
                }
            }
            $this->arrAttributeRelations[$attributeId] = array_keys($arrChildren);
        }
    }


    /**
     * Clean attribute
     *
     */
    public function clean()
    {
        $this->id = 0;
        $this->type = $this->defaultAttributeType;
        $this->sort_type = $this->defaultSortType;
        $this->order_id = 0;
        $this->mandatory = 0;
        $this->parent_id = 0;
        $this->access_special = '';
        $this->access_id = 0;
        $this->readAccessId = 0;
        $this->children = array();
        $this->arrName = array();
        $this->multiline = false;
        $this->protected = false;
        $this->readProtected = false;
        $this->modifiable = array('mandatory', 'access', 'type');
        $this->EOF = true;
    }


    function createChild($parentId)
    {
        $this->clean();
        $this->parent_id = $parentId;
        $this->type = $this->arrTypeAssociation[isset($this->arrAttributes[$this->parent_id]['type']) ? $this->arrAttributes[$this->parent_id]['type'] : ''][0];
    }


    function reset()
    {
        $this->load($this->id);
    }


    /**
     * Load first attribute
     *
     */
    function first()
    {
        if (reset($this->arrAttributeRelations[0]) === false || !$this->load(current($this->arrAttributeRelations[0]))) {
            $this->EOF = true;
        } else {
            $this->EOF = false;
        }
    }


    /**
     * Load next attribute
     *
     */
    function next()
    {
        if (next($this->arrAttributeRelations[0]) === false || !$this->load(current($this->arrAttributeRelations[0]))) {
            $this->EOF = true;
        }
    }


    /**
     * Store attribute to database
     *
     */
    function store()
    {
        global $_ARRAYLANG;

        if ($this->checkIntegrity()) {
            if ($this->storeCustomAttribute()) {
                if (($this->isDefaultAttribute($this->id) || $this->storeNames()) &&
                    $this->storeChildrenOrder() &&
                    $this->storeProtection($this->protected, $this->access_id, 'access_id', $this->access_group_ids) &&
                    $this->storeProtection($this->readProtected, $this->readAccessId, 'read_access_id', $this->readAccessGroupIds, 'read')
                ) {
                    $this->init();
                    return true;
                }
                $this->errorMsg = $this->type == 'menu_option' ? $_ARRAYLANG['TXT_ACCESS_FAILED_STORE_MENU_OPTION_DESC'] : ($this->type == 'frame' ? $_ARRAYLANG['TXT_ACCESS_FAILED_STORE_FRAME_DESC'] : $_ARRAYLANG['TXT_ACCESS_FAILED_STORE_ATTRIBUTE_DESC']);
                return false;
            }
            $this->errorMsg = $this->type == 'menu_option' ? $_ARRAYLANG['TXT_ACCESS_FAILED_STORE_MENU_OPTION'] : ($this->type == 'frame' ? $_ARRAYLANG['TXT_ACCESS_FAILED_STORE_FRAME'] : $_ARRAYLANG['TXT_ACCESS_FAILED_STORE_ATTRIBUTE']);
            return false;
        }
// TODO: Hmmmm...  No error message here?  We just failed an "integrity check"!?
        return false;
    }


    function storeCustomAttribute()
    {
        $type =
            ($this->arrTypes[$this->type]['multiline'] && $this->multiline
              ? 'textarea' : $this->type);
        $parentId = $this->parent_id;
        if ($parentId == 0) {
            $parentId = 'NULL';
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $attrRepo = $em->getRepository(
            'Cx\Core\User\Model\Entity\UserAttribute'
        );
        $attr = $attrRepo->find($this->id);

        if (empty($attr)) {
            $attr = new \Cx\Core\User\Model\Entity\UserAttribute();
            $isDefault = 0;
        } else {
            $isDefault = $attr->isDefault();
        }

        $parent = $attrRepo->find($parentId);

        $attr->setType($type);
        $attr->setSortType($this->sort_type);
        $attr->setOrderId($this->order_id);
        $attr->setMandatory($this->mandatory);
        $attr->setParent($parent);
        $attr->setAccessId(0);
        $attr->setReadAccessId(0);
        $attr->setDefault($isDefault);
        $em->persist($attr);

        try {
            $em->flush();
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return false;
        }

        $this->id = $attr->getId();

        return true;
    }


    function storeCoreAttributeTitle()
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $attributeRepo = $em->getRepository('Cx\Core\User\Model\Entity\UserAttribute');

        try {
            $attribute = $attributeRepo->find($this->id);
            if (!$this->id || !preg_match('#([0-9]+)#', $this->id, $pattern)) {
                $titleId = $this->getAttributeIdByDefaultAttributeId('title');
                $titleAttr = $attributeRepo->find($titleId);
                $attribute = new \Cx\Core\User\Model\Entity\UserAttribute();
                $attribute->setAccessId(0);
                $attribute->setReadAccessId(0);

                if ($titleAttr) {
                    $attribute->setParent($titleAttr);
                }
            }

            $attribute->setName($this->arrName[0]);

            $em->persist($attribute);
            $em->flush();

            return true;
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            return false;
        }
    }

    function storeChildrenOrder()
    {
        if ($this->sort_type == 'custom') {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $em = $cx->getDb()->getEntityManager();
            $attributeRepo = $em->getRepository('Cx\Core\User\Model\Entity\UserAttribute');
            $offset = 0;

            foreach ($this->children as $orderId => $childAttributeId)
            {
                $attribute = $attributeRepo->find($childAttributeId);
                if (empty($attribute)) {
                    continue;
                }
                $attribute->setOrderId($orderId+$offset);
                $em->persist($attribute);
            }
            try {
                $em->flush();
            } catch (\Doctrine\ORM\OptimisticLockException $e) {
                return false;
            }
        }
        return true;
    }


    /**
     * Store attribute names
     *
     * @global ADONewConnection
     * @return boolean TRUE on success, otherwise FALSE
     */
    function storeNames()
    {
        $status = true;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $attributeRepo = $em->getRepository('Cx\Core\User\Model\Entity\UserAttribute');

        try {
            foreach ($this->arrName as $langId=>$name) {
                $attribute = $attributeRepo->find($this->id);
                $attribute->setTranslatableLocale($langId);
                $attribute->setName($name);
                $em->persist($attribute);
                $em->flush();
            }
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            $status = false;
        }
        return $status;
    }


    /**
     * Store read/write protection
     *
     * @param boolean $protected Is read/write protected
     * @param integer $accessId  Access Id of read/write
     * @param string  $fieldName Field name
     * @param array   $groupIds  Assigned read/write group ids
     * @param string  $access    Is read/write
     *
     * @return boolean
     */
    function storeProtection($protected, $accessId, $fieldName, $groupIds, $access = 'write')
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $objDatabase = $cx->getDb()->getAdoDb();
        $attributeRepo = $em->getRepository('Cx\Core\User\Model\Entity\UserAttribute');
        $classMeta = $em->getClassMetadata('Cx\Core\User\Model\Entity\UserAttribute');

        $attributeId = $this->id;
        if ($this->isDefaultAttribute($this->id)) {
            $attributeId = $this->getAttributeIdByDefaultAttributeId($this->id);
        }

        $attribute = $attributeRepo->find($attributeId);
        if (!$attribute) {
            return false;
        }

        $setter = '';
        $mappedFieldName = $classMeta->getFieldName($fieldName);
        if ($mappedFieldName != $fieldName) {
            $setter = 'set' . \Doctrine\Common\Inflector\Inflector::classify($mappedFieldName);
        }

        if (!$protected) {
            // remove protection
            if (!$setter) {
                return false;
            }
            $attribute->$setter(0);
            if ($access == 'write') {
                $attribute->setAccessSpecial('');
            }

            try {
                $em->persist($attribute);
                $em->flush();
                if (
                    $objDatabase->Execute(
                        'DELETE FROM `' . DBPREFIX . 'access_group_dynamic_ids`
                            WHERE `access_id` = ' . $this->arrAttributes[$this->id][$fieldName]
                    ) !== false
                ) {
                    return true;
                }

            } catch (\Doctrine\ORM\OptimisticLockException $e) {
                return false;
            }
        }

        $arrOldGroups = array();
        $status = true;
        if ($accessId) {
            $objResult = $objDatabase->Execute('
                SELECT `group_id`
                    FROM `' . DBPREFIX . 'access_group_dynamic_ids`
                    WHERE `access_id` = ' . $accessId
            );
            if ($objResult) {
                while (!$objResult->EOF) {
                    $arrOldGroups[] = $objResult->fields['group_id'];
                    $objResult->MoveNext();
                }
            }
        } else {
            $accessId = \Permission::createNewDynamicAccessId();
            if (!$accessId || !$setter) {
                return false;
            }

            try {
                $attribute->$setter($accessId);
                $em->persist($attribute);
                $em->flush();
            } catch (\Doctrine\ORM\OptimisticLockException $e) {
                return false;
            }

            $this->arrAttributes[$this->id][$fieldName] = $accessId;
            if ($access == 'write') {
                $this->access_id = $accessId;
            } else {
                $this->readAccessId = $accessId;
            }
        }

        try {
            $attribute->setAccessSpecial($accessId);
            $em->persist($attribute);
            $em->flush();
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            if ($access == 'write') {
                return false;
            }
        }

        $arrNewGroups = array_diff($groupIds, $arrOldGroups);
        $arrRemovedGroups = array_diff($arrOldGroups, $groupIds);
        foreach ($arrNewGroups as $groupId) {
            if (
                $objDatabase->Execute(
                    'INSERT INTO `' . DBPREFIX . 'access_group_dynamic_ids`
                        SET `access_id` = "' . contrexx_raw2db($accessId) . '",
                            `group_id`  = "' . contrexx_raw2db($groupId) . '" '
                ) === false
            ) {
                $status = false;
            }
        }
        foreach ($arrRemovedGroups as $groupId) {
            if (
                $objDatabase->Execute('
                    DELETE FROM `' . DBPREFIX . 'access_group_dynamic_ids`
                        WHERE `access_id` = ' . $accessId . '
                            AND `group_id` = ' . $groupId
                ) === false
            ) {
                $status = false;
            }
        }
        return $status;
    }

    function delete()
    {
        $status = $this->deleteAttribute($this->id);
        $this->init();
        return $status;
    }


    function deleteAttribute($attributeId)
    {
        if (isset($this->arrAttributeRelations[$attributeId])) {
            foreach ($this->arrAttributeRelations[$attributeId] as $childAttributeId) {
                if (!$this->deleteAttribute($childAttributeId)) {
                    return false;
                }
            }
        }
// TODO: I suppose the precedence is okay like this.
//        return ($this->isCoreAttribute($attributeId) || $this->deleteAttributeContent($attributeId)) && ($this->isCoreAttribute($attributeId) || $this->deleteAttributeNames($attributeId)) && $this->deleteAttributeEntity($attributeId);
// However, it would be clearer with a few parentheses.
        return
            (   $this->isDefaultAttribute($attributeId)
             ||    $this->deleteAttributeContent($attributeId))
                && ($this->isDefaultAttribute($attributeId)
             ||    $this->deleteAttributeNames($attributeId))
                && $this->deleteAttributeEntity($attributeId);
    }


    function deleteAttributeEntity($attributeId)
    {
        global $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $attrRepo = $em->getRepository(
            'Cx\Core\User\Model\Entity\UserAttribute'
        );

        $attr = $attrRepo->find($attributeId);

        if (empty($attr)) {
            return false;
        }

        try {
            $em->remove($attr);
            $em->flush();

            return true;
        } catch (Doctrine\ORM\OptimisticLockException $e) {
            $this->errorMsg = sprintf(
                $_ARRAYLANG['TXT_ACCESS_UNABLE_DEL_ATTRIBUTE'],
                htmlentities(
                    $this->arrAttributes[$attributeId]['names'][$this->langId],
                    ENT_QUOTES,
                    CONTREXX_CHARSET
                )
            );

            return false;
        }
    }


    function deleteAttributeContent($attributeId)
    {
        global $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $attrNames = $em->getRepository(
            'Cx\Core\User\Model\Entity\UserAttributeValue'
        )->findBy(array('userAttribute' => $attributeId));

        foreach ($attrNames as $attrName) {
            $em->remove($attrName);
        }
        try {
            $em->flush();
            return true;
        } catch (\Doctrine\ORM\OptimisticLockException $e) {
            $this->errorMsg = sprintf($_ARRAYLANG['TXT_ACCESS_UNABLE_DEL_ATTRIBUTE_CONTENT'], htmlentities($this->arrAttributes[$attributeId]['names'][$this->langId], ENT_QUOTES, CONTREXX_CHARSET));
            return false;
        }
    }

    function checkIntegrity()
    {
        global $_ARRAYLANG;

        if ($this->parent_id) {
            if (isset($this->arrAttributes[$this->parent_id]['type'])) {
                if (!in_array($this->type, $this->arrTypeAssociation[$this->arrAttributes[$this->parent_id]['type']])) {
                    $this->errorMsg = $_ARRAYLANG['TXT_ACCESS_INVALID_CHILD_ATTRIBUTE'];
                    return false;
                }
            } else {
                $this->errorMsg = $_ARRAYLANG['TXT_ACCESS_INVALID_PARENT_ATTRIBUTE'];
                return false;
            }
        } else {
            if (!in_array($this->type, $this->arrTypeAssociation[''])) {
                $this->errorMsg = $_ARRAYLANG['TXT_ACCESS_INVALID_CHILD_ATTRIBUTE'];
                return false;
            }
        }
        return true;
    }


    public function checkModifyPermission($currentValue=null, $newValue=null)
    {
        switch ($this->getType()) {
          case 'menu':
            $currentPos = array_search($currentValue, $this->getChildren());
            $newPos = array_search($newValue, $this->getChildren());
            switch ($this->getSpecialProtection()) {
              case 'menu_select_higher':
                if ($newPos >= $currentPos || empty($currentValue) && empty($newValue)) {
                    return true;
                }
                break;
              case 'menu_select_lower':
                if ($newPos <= $currentPos || empty($currentValue) && empty($newValue)) {
                    return true;
                }
                break;
            }
            break;
        }
        return false;
    }


    /**
     * Check the read permission of profile attribute
     *
     * @return boolean
     */
    public function checkReadPermission()
    {
        if (!$this->isReadProtected()) {
            return true;
        }

        return \Permission::checkAccess(
            $this->getReadAccessId(),
            'dynamic',
            true
        );
    }


    public function hasMandatoryOption()
    {
        return !empty($this->arrTypes[$this->type]['mandatory']);
    }


    public function hasChildOption()
    {
        return !empty($this->arrTypes[$this->type]['children']);
    }


    public function hasMovableOption()
    {
        return !empty($this->arrTypes[$this->type]['movable']);
    }


    public function hasSortableOption()
    {
        return !empty($this->arrTypes[$this->type]['children']);
    }


    private function hasSameParentHistoryAttribute($attributeId)
    {
        $tmpAttributeId = $this->id;
        $parentHistoryId = 0;
        // get parent history attribute of the loaded attribute ($this-id)
        while (!empty($this->arrAttributes[$tmpAttributeId]['parent_id'])) {
            if ($this->arrAttributes[$this->arrAttributes[$tmpAttributeId]['parent_id']]['type'] == 'history') {
                $parentHistoryId = $this->arrAttributes[$tmpAttributeId]['parent_id'];
                break;
            } else {
                $tmpAttributeId = $this->arrAttributes[$tmpAttributeId]['parent_id'];
            }
        }

        // check if the next parent history attribute of $attributeId is the same as of the loaded attribute ($this->id)
        while (!empty($this->arrAttributes[$attributeId]['parent_id'])) {
            if ($this->arrAttributes[$attributeId]['parent_id'] == $parentHistoryId) {
                return true;
            }
            if ($this->arrAttributes[$this->arrAttributes[$attributeId]['parent_id']]['type'] == 'history') {
                return false;
            }
            $attributeId = $this->arrAttributes[$attributeId]['parent_id'];
        }
        return false;
    }


    public function hasProtectionOption()
    {
        return !empty($this->arrTypes[$this->type]['protection']);
    }


    function hasChildren($attributeId = null)
    {
        if (empty($attributeId)) {
            $attributeId = $this->id;
        }
        return isset($this->arrAttributeRelations[$attributeId]) && count($this->arrAttributeRelations[$attributeId]);
    }


    function isMultiline()
    {
        return $this->multiline;
    }


    function isMandatory()
    {
        return $this->mandatory;
    }


    public function isUnknownOption($attributeId = null)
    {
        if (is_null($attributeId)) {
            $attributeId = $this->id;
        }
        return isset($this->arrAttributes[$attributeId]['unknown']) && $this->arrAttributes[$attributeId]['unknown'];
    }


    function isSubChild($attributeId)
    {
        while ($this->arrAttributes[$attributeId]['parent_id'] != 0) {
            if ($this->arrAttributes[$attributeId]['parent_id'] == $this->id) {
                return true;
            }
            $attributeId = $this->arrAttributes[$attributeId]['parent_id'];
        }
        return false;
    }


    function isHistoryChild($attributeId)
    {
        while (!empty($this->arrAttributes[$attributeId]['parent_id'])) {
            if ($this->arrAttributes[$this->arrAttributes[$attributeId]['parent_id']]['type'] == 'history') {
                return true;
            }
            $attributeId = $this->arrAttributes[$attributeId]['parent_id'];
        }
        return false;
    }


    function isModifiable()
    {
        return (bool)count($this->modifiable);
    }


    function isChildrenModifiable()
    {
        return in_array('children', $this->modifiable);
    }


    function isRemovable()
    {
        return !$this->isDefaultAttribute($this->id);
    }


    function isNamesModifiable()
    {
        return in_array('names', $this->modifiable);
    }


    function isTypeModifiable()
    {
        return in_array('type', $this->modifiable);
    }


    function isSortOrderModifiable()
    {
        return in_array('sort_order', $this->modifiable);
    }


    function getHistoryAttributeId($attributeId)
    {
        while (!empty($this->arrAttributes[$attributeId]['parent_id'])) {
            if ($this->arrAttributes[$this->arrAttributes[$attributeId]['parent_id']]['type'] == 'history') {
                return $this->arrAttributes[$attributeId]['parent_id'];
            }
            $attributeId = $this->arrAttributes[$attributeId]['parent_id'];
        }
        return false;
    }


    function isParentHistoryAttribute($attributeId)
    {
        $tmpAttributeId = $this->id;

        while (!empty($this->arrAttributes[$tmpAttributeId]['parent_id'])) {
            if ($this->arrAttributes[$tmpAttributeId]['parent_id'] == $attributeId) {
                return true;
            }
            if ($this->arrAttributes[$this->arrAttributes[$tmpAttributeId]['parent_id']]['type'] == 'history') {
                return false;
            }
            $tmpAttributeId = $this->arrAttributes[$tmpAttributeId]['parent_id'];
        }
        return false;
    }


    function isAllowedParentType($attributeId)
    {
        if (empty($attributeId) && !$this->isHistoryChild($this->id) && in_array($this->type, $this->arrTypeAssociation['']) ||
            !empty($attributeId) && $attributeId !== $this->id && // could not be its own father
            isset($this->arrTypes[$this->arrAttributes[$attributeId]['type']]) &&
            $this->arrTypes[$this->arrAttributes[$attributeId]['type']]['children'] && // attribute could be a father?
            in_array($this->type, $this->arrTypeAssociation[$this->arrAttributes[$attributeId]['type']]) && // loaded attribute could be a child of attribute
            !$this->isSubChild($attributeId) // attribute is not a subchild of the loaded attibute?
        ) {
            return true;
        }
        return false;
    }


    function isAllowedParentAttribute($attributeId)
    {
        if (!$this->id || $this->parent_id == $attributeId ||
            isset($this->arrAttributes[$attributeId]['type']) && // type of attribute is defined?
            ($this->arrAttributes[$attributeId]['type'] == 'history' && $this->isParentHistoryAttribute($attributeId) || // attribute must be the parent history attribute if it is of type history
            $this->arrAttributes[$attributeId]['type'] != 'history' && (!$this->isHistoryChild($attributeId) && !$this->isHistoryChild($this->id) || $this->hasSameParentHistoryAttribute($attributeId))) // attribute must have the same parent history attribute
        ) {
            return true;
        }
        return false;
    }


    public function isProtected()
    {
        return $this->protected;
    }

    /**
     * Return read protection status
     *
     * @return boolean
     */
    public function isReadProtected()
    {
        return $this->readProtected;
    }

    public function isDefaultAttribute($attributeId=null)
    {
        if (is_null($attributeId)) {
            $attributeId = $this->id;
        }

        if (is_numeric($attributeId)) {
            return !empty($this->getDefaultAttributeNames()[$attributeId]);
        }

        return isset($this->arrDefaultAttributeTemplates[$attributeId]);
    }

    public function isCustomAttribute($attributeId = null)
    {
        if (is_null($attributeId)) {
            $attributeId = $this->id;
        }
        return in_array($attributeId, $this->arrCustomAttributes);
    }


    public function setNames($arrNames)
    {
        $this->arrName = array();
        foreach ($arrNames as $langId => $name) {
            $this->arrName[intval($langId)] = $name;
        }

        // add text for inactive languages too
        $defaultLangId = \FWLanguage::getDefaultLangId();
        foreach (\FWLanguage::getLanguageArray() as $langId => $langInfo) {
            if (!isset($arrNames[$langId])) {
                $this->arrName[$langId] = $this->arrName[$defaultLangId];
            }
        }

        $this->arrAttributes[$this->id]['names'] = $this->arrName;
    }


    public function setType($type)
    {
        if (in_array($type, array_keys($this->arrTypes))) {
            $this->type = $type;
            return true;
        }
        return false;
    }


    public function setMultiline($multiline=false)
    {
        $this->multiline = $multiline;
    }


    public function setMandatory($mandatory = 0)
    {
        $this->mandatory = intval($mandatory);
    }


    public function setParent($parentId = 0)
    {
        global $_ARRAYLANG;
        if (($parentId == 0 || isset($this->arrAttributes[$parentId])) && $this->isAllowedParentType($parentId) && $this->isAllowedParentAttribute($parentId)) {
            $this->parent_id = $parentId;
            return true;
        }
        $this->errorMsg = $_ARRAYLANG['TXT_ACCESS_INVALID_PARENT_ATTRIBUTE'];
        return false;
    }


    public function setSortType($type)
    {
        if (in_array($type, array_keys($this->arrSortTypes))) {
            $this->sort_type = $type;
        } else {
            $this->sort_type = $this->defaultSortType;
        }
    }


    public function setChildOrder($arrChildOrder)
    {
        $pattern = array();
        foreach ($arrChildOrder as $childId => $orderId) {
            $this->arrAttributeRelations[$this->id][intval($orderId)] = $this->isDefaultAttribute($this->id) && preg_match('#([0-9]+)#', $childId, $pattern) ? $pattern[0] : intval($childId);
        }
        $this->children = $this->arrAttributeRelations[$this->id];
    }


    public function setProtection($arrGroups)
    {
        $this->access_group_ids = array();
        foreach ($arrGroups as $groupId) {
            $this->access_group_ids[] = intval($groupId);
        }
        $this->protected = true;
    }

    /**
     * Set a read protection
     *
     * @param array $arrReadGroups array of Group id's
     */
    public function setReadProtection($arrReadGroups)
    {
        $this->readAccessGroupIds = array();
        foreach ($arrReadGroups as $groupId) {
            $this->readAccessGroupIds[] = $groupId;
        }
        $this->readProtected = true;
    }

    public function setSpecialProtection($special)
    {
        if (in_array($special, $this->arrTypes[$this->type]['special'])) {
            $this->access_special = $special;
        }
    }


    public function removeProtection()
    {
        $this->access_id = 0;
        $this->protected = false;
    }

    /**
     * Remove read protection
     */
    public function removeReadProtection()
    {
        $this->readAccessId   = 0;
        $this->readProtected  = false;
    }

    /**
     * Load attribute name in each language
     * @return mixed Array with names, which may also contains no elements, or FALSE on failure.
     */
    function getAttributeName($id)
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $attribute = $cx->getDb()->getEntityManager()->getRepository(
            'Cx\Core\User\Model\Entity\UserAttribute'
        )->findOneBy(array('id' => $id));
        if (empty($attribute)) {
            return '';
        }
        return $attribute->getName();
    }


    function loadName($langId)
    {
        global $_CORELANG;

        if ($this->isDefaultAttribute($this->id)) {
            $this->arrName[$langId] = (string)$_CORELANG[$this->arrAttributes[$this->id]['desc']];
        } else {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $attr = $cx->getDb()->getEntityManager()->getRepository(
                'Cx\Core\User\Model\Entity\UserAttribute'
            )->findOneBy(array('id' => $this->id));

            if (!empty($attr)) {
                $attr->setTranslatableLocale($langId);
                $cx->getDb()->getEntityManager()->refresh($attr);

                $this->arrName[$langId] = $attr->getName();
            } else {
                $this->arrName[$langId] = '';
            }
        }

        $this->arrAttributes[$this->id]['names'][$langId] = $this->arrName[$langId];
    }


    /*function getTree($parentId = 0)
    {
        $arrTree = array();
        if (isset($this->arrAttributes[$parentId]['children'])) {
            foreach ($this->arrAttributes[$parentId]['children'] as $attributeId) {
                $arrTree[$attributeId] = array(
                    'type' => $this->arrAttributes[$attributeId]['type'],
                    'mandatory' => $this->arrAttributes[$attributeId]['mandatory'],
                    'children' => $this->getTree($attributeId),
                );
            }
        }
        return $arrTree;
    }*/


    function getSortedAttributeIds($parentId = 0)
    {
        $arrAttributes = array();
        if (isset($this->arrAttributeRelations[$parentId])) {
            foreach ($this->arrAttributeRelations[$parentId] as $attributeId) {
                $arrAttributes[] = $attributeId;
                $arrAttributes = array_merge($arrAttributes, $this->getSortedAttributeIds($attributeId));
            }
        }
        return $arrAttributes;
    }


    /**
     * In the system, the ID of a default attribute is the name of the attribute
     * (e.g. 'title'). But if we want to interact with the database, the default
     * attribute is handled like other user attributes. This means we have an
     * integer ID (e.g. 2) and had to track this ID (2) with the system-intern
     * default attribute ID ('title')
     *
     * This method gives the default attribute ID (e.g. 'title') by the given
     * user attribute ID (e.g. 2)
     *
     * @param int $attributeId user attribute ID to identify default attribute ID
     * @return string default attribute ID
     */
    public function getDefaultAttributeIdByAttributeId($attributeId)
    {
        $defaultAttributes = $this->getDefaultAttributeNames();

        if (!empty($defaultAttributes[$attributeId])) {
            return $defaultAttributes[$attributeId];
        }

        return $attributeId;
    }

    /**
     * In the system, the ID of a default attribute is the name of the attribute
     * (e.g. 'title'). But if we want to interact with the database, the default
     * attribute is handled like other user attributes. This means we have an
     * integer ID (e.g. 2) and had to track this ID (2) with the system-intern
     * default attribute ID ('title')
     *
     * This method gives the user attribute ID (e.g. 2) by the given default
     * attribute ID (e.g. 'title')
     *
     * @param int $defaultId default attribute ID to identify user attribute ID
     * @return string user attribute ID
     */
    public function getAttributeIdByDefaultAttributeId($defaultId)
    {
        $defaultAttributes = $this->getDefaultAttributeNames();

        if (in_array($defaultId, $defaultAttributes)) {
            return array_search($defaultId, $defaultAttributes);
        }

        return $defaultId;
    }

    function getId()
    {
        return $this->id;
    }


    function getName($langId = null)
    {
        global $_LANGID, $objInit;

        if (empty($this->langId)) {
            $this->langId = $_LANGID;
        }
        if (empty($langId)) {
            $langId = $this->langId;
        }
        if (empty($this->arrName[$langId])) {
            $this->loadName($langId);
        }
        if (empty($this->arrName[$langId])) {
            $langId = $objInit->mode == 'frontend' ? $objInit->defaultFrontendLangId : $objInit->defaultBackendLangId;
            $this->loadName($langId);
        }
        return $this->arrName[$langId];
    }


    function getParentType()
    {
        global $_ARRAYLANG;

        return htmlentities($this->arrAttributes[$this->parent_id]['names'][$this->langId], ENT_QUOTES, CONTREXX_CHARSET).' ['.(isset($this->arrAttributes[$this->parent_id]['type']) ? $_ARRAYLANG[$this->arrTypes[$this->arrAttributes[$this->parent_id]['type']]['desc']] : '').']';
    }


    function getParentTypeDescription()
    {
        global $_ARRAYLANG;

        return isset($this->arrAttributes[$this->parent_id]['type']) ? $_ARRAYLANG[$this->arrTypes[$this->arrAttributes[$this->parent_id]['type']]['parent']] : $_ARRAYLANG['TXT_ACCESS_PARENT_ATTRIBUTE'];
    }


    function getParentMenu($attrs = null)
    {
        global $_ARRAYLANG;

        $menu = '<select'.(!empty($attrs) ? ' '.$attrs : '').'>';
        $menu .= '<option value="'.($this->isAllowedParentType(0) ? '0" style="color:#000;"'.($this->parent_id == 0 ? ' selected="selected"' : '') : '-1" style="color:#ccc;"').'>'.$_ARRAYLANG['TXT_ACCESS_NEW_ATTRIBUTE'].'</option>';
        foreach ($this->getSortedAttributeIds() as $attributeId) {
            if (isset($this->arrAttributes[$attributeId]['type'])) {
                if ($this->isAllowedParentType($attributeId)) {
                    $menu .= '<option '.(
                        $this->isAllowedParentAttribute($attributeId)
                            ? 'value="'.$attributeId.'"'.($this->parent_id == $attributeId ? ' selected="selected"' :    '').' style="color:#000;"'
                            : 'value="-1" style="color:#ccc;"'
                        ).'>';
                    $menu .= str_pad('', $this->getLevel($attributeId)*2, '..').htmlentities($this->arrAttributes[$attributeId]['names'][$this->langId], ENT_QUOTES, CONTREXX_CHARSET).' ['.$_ARRAYLANG[$this->arrTypes[$this->arrAttributes[$attributeId]['type']]['desc']].']';
                    $menu .= '</option>';
                }
            }
        }
        $menu .= '</select>';
        return $menu;
    }


    function getLevel($attributeId)
    {
        $level = 0;
        while ($this->arrAttributes[$attributeId]['parent_id'] != 0) {
            $attributeId = $this->arrAttributes[$attributeId]['parent_id'];
            if ($this->arrAttributes[$attributeId]['type'] != 'group') {
                $level++;
            }
        }
        return $level;
    }


    function getChildren()
    {
        return isset($this->children) ? $this->children : array();
    }


    function getParent()
    {
        return $this->parent_id;
    }


    function getErrorMsg()
    {
        return $this->errorMsg;
    }


    function getTypeMenu($attrs = null)
    {
        global $_ARRAYLANG;

        if (count($this->arrTypeAssociation[isset($this->arrAttributes[$this->parent_id]['type']) ? $this->arrAttributes[$this->parent_id]['type'] : '']) > 1) {
            $menu = '<select'.(!empty($attrs) ? ' '.$attrs : '').'>';
            foreach ($this->arrTypeAssociation[isset($this->arrAttributes[$this->parent_id]['type']) ? $this->arrAttributes[$this->parent_id]['type'] : ''] as $type) {
                $menu .= '<option value="'.$type.'"'.($this->type == $type ? ' selected="selected"' : '').'>'.$_ARRAYLANG[$this->arrTypes[$type]['desc']].'</option>';
            }
            $menu .= '</select>';
            return $menu;
        }
        return $this->getTypeDescription().'<input type="hidden" name="access_attribute_type" value="'.$this->arrTypeAssociation[isset($this->arrAttributes[$this->parent_id]['type']) ? $this->arrAttributes[$this->parent_id]['type'] : ''][0].'" />';
    }


    /**
     * Get element type of the attribute
     * @return string Element typ
     */
    function getType()
    {
        return $this->type;
    }


    function getTypeDescription()
    {
        global $_ARRAYLANG;

        return $_ARRAYLANG[$this->arrTypes[$this->type]['desc']];
    }


    function getDataType()
    {
        return $this->arrTypes[$this->type]['data_type'];
    }


    /**
     * Get an array containing all types that can be set to mandatory.
     *
     * @return array
     */
    public function getMandatoryTypes()
    {
        static $arrTypes = null;

        if (empty($arrTypes)) {
            $arrTypes = array();
            foreach ($this->arrTypes as $type => $arrType) {
                if ($arrType['mandatory']) {
                    $arrTypes[] = $type;
                }
            }
        }
        return $arrTypes;
    }


    /**
     * Get an array containing all types that can be sorted.
     *
     * @return array
     */
    public function getSortableTypes()
    {
        static $arrTypes = null;

        if (empty($arrTypes)) {
            $arrTypes = array();
            foreach ($this->arrTypes as $type => $arrType) {
                if ($arrType['children']) {
                    $arrTypes[] = $type;
                }
            }
        }
        return $arrTypes;
    }


    function getSortType()
    {
        return $this->sort_type;
    }


    function getSortTypeMenu($attrs = null)
    {
        global $_ARRAYLANG;

        $menu = '<select'.(!empty($attrs) ? ' '.$attrs : '').'>';
        foreach ($this->arrSortTypes as $type => $desc) {
            $menu .= '<option value="'.$type.'"'.($this->sort_type == $type ? ' selected="selected"' : '').'>'.$_ARRAYLANG[$desc].'</option>';
        }
        $menu .= '</select>';
        return $menu;
    }


    function getSortTypeDescription()
    {
        global $_ARRAYLANG;

        return $_ARRAYLANG[$this->arrSortTypes[$this->sort_type]];
    }


    function getAccessId()
    {
        return $this->access_id;
    }

    /**
     * Get read access ID
     *
     * @return integer
     */
    public function getReadAccessId()
    {
        return $this->readAccessId;
    }

    function getSpecialProtection()
    {
        return $this->access_special;
    }


    function getMandatoryAttributeIds()
    {
        return $this->arrMandatoryAttributes;
    }


    function getDefaultAttributeIds()
    {
        return $this->arrDefaultAttributeIds;
    }


    function getCustomAttributeIds()
    {
        return $this->arrCustomAttributes;
    }


    function getMenuOptionValue()
    {
        return (isset($this->arrAttributes[$this->id]['value'])
            ? $this->arrAttributes[$this->id]['value'] : $this->id);
    }


    /**
     * Returns an array of all custom attribute names in the selected language
     *
     * If the $langId parameter is empty, the language is taken from the
     * global LANG_ID constant.
     * Used by {@see \Cx\Core\Setting\Controller\Setting::show()},
     * {@see Shopmanager::view_settings_general()}
     * @param   integer     $langId         The optional language ID
     * @return  array                       An array with attribute names
     *                                      indexed by their IDs on success,
     *                                      false otherwise
     * @global    mixed     $objDatabase    Database connection object
     * @static
     * @author    Reto Kohli <reto.kohli@comvation.com>
     */
    public static function getCustomAttributeNameArray($langId=0)
    {
        if (empty($langId)) $langId = LANG_ID;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $qb = $em->createQueryBuilder();
        // Get only custom attributes
        $qb->select('a')
            ->from('Cx\Core\User\Model\Entity\UserAttribute', 'a')
            ->andWhere($qb->expr()->eq('a.default', ':isDefault'))
            ->setParameter('isDefault', 0)
            ->orderBy('a.orderId', 'ASC');

        $attrs = $qb->getQuery()->getResult();

        $arrNames = array();
        foreach ($attrs as $attr) {
            $attr->setTranslatableLocale($langId);
            $em->refresh($attr);
            $arrNames[$attr->getId()] = $attr->getName();
        }

        return $arrNames;

// TODO: check if this methods logic could be replaced by the following code
        $arrNames = array();
        foreach ($this->getSortedAttributeIds() as $attributeId) {
            if ($this->isCustomAttribute($attributeId)) {
                $arrNames[$attributeId] = str_pad('', $this->getLevel($attributeId)*2, '..').htmlentities($this->arrAttributes[$attributeId]['names'][$this->langId], ENT_QUOTES, CONTREXX_CHARSET);
            }
        }
        return $arrNames;
    }

}
