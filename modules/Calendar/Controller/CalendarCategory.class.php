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
 * Calendar
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
namespace Cx\Modules\Calendar\Controller;

/**
 * Calendar Class Host Manager
 *
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class CalendarCategory extends CalendarLibrary
{
    /**
     * category id
     *
     * @access public
     * @var integer
     */
    public $id;

    /**
     * category name
     *
     * @access public
     * @var string
     */
    public $name;

    /**
     * position
     *
     * @access public
     * @var integer
     */
    public $pos;

    /**
     * status
     *
     * @access public
     * @var boolean
     */
    public $status;

    /**
     * Category data
     *
     * @access public
     * @var array
     * @see getData();
     */
    public $arrData = array();

    /**
     * Load the Category with the given ID, if any
     * @param   integer     $id
     */
    function __construct($id = null)
    {
        if ($id) {
            self::get($id);
        }
        $this->init();
    }

    /**
     * Load the Category with the given ID
     * @param   integer     $catId
     */
    function get($catId)
    {
        global $objDatabase, $_LANGID;
        $catId = intval($catId);
        $query = "
            SELECT category.`id`,
                category.`pos`,
                category.`status`,
                name.`name`
            FROM ".DBPREFIX."module_".self::TABLE_PREFIX."_category AS category
            JOIN ".DBPREFIX."module_".self::TABLE_PREFIX."_category_name AS name
            ON category.id=name.cat_id
            WHERE category.id=?
            AND name.lang_id=?";
        $objResult = $objDatabase->SelectLimit($query, 1, -1,
            array($catId, $_LANGID));
        if ($objResult) {
        	$this->id = $catId;
	        $this->name = $objResult->fields['name'];
	        $this->pos = intval($objResult->fields['pos']);
	        $this->status = intval($objResult->fields['status']);
        }
    }

    /**
     * Load additional Category data
     */
    function getData()
    {
        global $objDatabase, $_LANGID;
        //get category name(s)
        $query = "
            SELECT `name`,`lang_id`
            FROM ".DBPREFIX."module_".self::TABLE_PREFIX."_category_name
            WHERE cat_id=?";
        $objResult = $objDatabase->Execute($query, array($this->id));
        while ($objResult && !$objResult->EOF) {
            if($objResult->fields['lang_id'] == $_LANGID) {
                $this->arrData['name'][0] = htmlentities(
                    $objResult->fields['name'], ENT_QUOTES, CONTREXX_CHARSET);
            }
            $this->arrData['name'][intval($objResult->fields['lang_id'])] =
                htmlentities($objResult->fields['name'], ENT_QUOTES, CONTREXX_CHARSET);
            $objResult->MoveNext();
        }
        //get category host(s)
        $query = "
            SELECT `title`,`id`
            FROM ".DBPREFIX."module_".self::TABLE_PREFIX."_host
            WHERE confirmed='1'
            AND cat_id=?";
        $objResult = $objDatabase->Execute($query, array($this->id));
        while ($objResult && !$objResult->EOF) {
            $this->arrData['hosts'][intval($objResult->fields['id'])] =
                htmlentities($objResult->fields['title'],
                    ENT_QUOTES, CONTREXX_CHARSET);
            $objResult->MoveNext();
        }
    }

    /**
     * Toggle the status of the Category
     * @return  boolean                 True on success, false otherwise
     */
    function switchStatus()
    {
        global $objDatabase;
        $categoryStatus = ($this->status == 1) ? 0 : 1;
        $category = $this->getCategoryEntity(
            $this->id, array('status' => $categoryStatus)
        );
        //Trigger preUpdate event for Category Entity
        $this->triggerEvent(
            'model/preUpdate', $category,
            array('relations' => array('oneToMany' => 'getCategoryNames')), true
        );
        $query = "
            UPDATE ".DBPREFIX."module_".self::TABLE_PREFIX."_category
            SET status=?
            WHERE id=?";
        $objResult = $objDatabase->Execute($query,
            array($categoryStatus, $this->id));
        if ($objResult) {
            //Trigger postUpdate event for Category Entity
            $this->triggerEvent('model/postUpdate', $category);
            $this->triggerEvent('model/postFlush');
            //Clear cache
            $this->triggerEvent('clearEsiCache');
            return true;
        }
        return false;
    }

    /**
     * Update the Category order
     * @param   integer     $order      Position ordinal
     * @return  boolean                 True on success, false otherwise
     */
    function saveOrder($order)
    {
        global $objDatabase;
        $category = $this->getCategoryEntity($this->id, array('pos' => $order));
        //Trigger preUpdate event for Category Entity
        $this->triggerEvent(
            'model/preUpdate', $category,
            array('relations' => array('oneToMany' => 'getCategoryNames')), true
        );
        $query = "
            UPDATE ".DBPREFIX."module_".self::TABLE_PREFIX."_category
            SET `pos`=?
            WHERE id=?";
        $objResult = $objDatabase->Execute($query, array($order, $this->id));
        if ($objResult) {
            //Trigger postUpdate event for Category Entity
            $this->triggerEvent('model/postUpdate', $category);
            $this->triggerEvent('model/postFlush');
            //Clear cache
            $this->triggerEvent('clearEsiCache');
            return true;
        }
        return false;
    }

    /**
     * Store the Category
     *
     * Triggers various model events along the way in order to synchronize
     * the Event with other hosts.
     * @param   array   $data           Event data
     * @return  boolean                 True on success, false otherwise
     */
    function save($data)
    {
        global $objDatabase, $_LANGID;
        $arrHosts = $data['selectedHosts'];
        $arrNames = $data['name'];
        $id = $this->id;
        $formData = array('categoryNames' => $arrNames);
        $category = $this->getCategoryEntity($this->id, $formData);
        if (intval($this->id) == 0) {
            //Trigger event prePersist for Category Entity
            $this->triggerEvent(
                'model/prePersist', $category,
                array(
                    'relations' => array('oneToMany' => 'getCategoryNames')
                ), true
            );
            $query = "
                INSERT INTO ".DBPREFIX."module_".self::TABLE_PREFIX."_category
                (`pos`,`status`)
                 VALUES ('0','0')";
            $objResult = $objDatabase->Execute($query);
            if (!$objResult) {
                return false;
            }
            $this->id = intval($objDatabase->Insert_ID());
        } else {
            //Trigger event preUpdate for Category Entity
            $this->triggerEvent(
                'model/preUpdate', $category,
                array(
                    'relations' => array('oneToMany' => 'getCategoryNames')
                ), true
            );
        }
        $categoryNames = $category->getCategoryNames();
        foreach ($categoryNames as $categoryName) {
            //Trigger event preRemove for CategoryName Entity
            $this->triggerEvent('model/preRemove', $categoryName);
        }
        //names
        $query = "
            DELETE FROM ".DBPREFIX."module_".self::TABLE_PREFIX."_category_name
            WHERE cat_id=?";
        $objResult = $objDatabase->Execute($query, array($this->id));
        if (!$objResult) {
            return false;
        }
        foreach ($categoryNames as $categoryName) {
            //Trigger event postRemove for CategoryName Entity
            $this->triggerEvent('model/postRemove', $categoryName);
        }
        $category = $this->getCategoryEntity($this->id);
        foreach ($arrNames as $langId => $categoryName) {
            if ($langId) {
                $categoryName = ($categoryName ? $categoryName : $arrNames[0]);
                if ($_LANGID == $langId) {
                    $categoryName = $arrNames[0] != $this->name
                        ? $arrNames[0] : $categoryName;
                }
                $formData = array(
                    'catId'  => intval($this->id),
                    'name'   => contrexx_addslashes(contrexx_strip_tags($categoryName)),
                    'langId' => intval($langId)
                );
                $categoryNameEntity = $this->getCategoryNameEntity(
                    $category, $formData
                );
                //Trigger event prePersist for CategoryName Entity
                $this->triggerEvent(
                    'model/prePersist', $categoryNameEntity,
                    array(
                        'relations' => array('manyToOne' => 'getCategory')
                    ), true
                );
                $query = "
                    INSERT INTO ".DBPREFIX."module_".self::TABLE_PREFIX."_category_name
                    (`cat_id`,`lang_id`,`name`)
                    VALUES (?, ?, ?)";

                $objResult = $objDatabase->Execute($query,
                    array($this->id, $formData['langId'], $formData['name']));
                if ($objResult) {
                    //Trigger event postPersist for CategoryName Entity
                    $this->triggerEvent('model/postPersist', $categoryNameEntity);
                }
            }
        }
        $this->triggerEvent('model/postFlush');
        if (!$objResult) {
            return false;
        }
        if ($id == 0) {
            //Trigger event postPersist for Category Entity
            $this->triggerEvent('model/postPersist', $category, null, true);
        } else {
            //Trigger event postUpdate for Category Entity
            $this->triggerEvent('model/postUpdate', $category);
        }
        $this->triggerEvent('model/postFlush');
        //hosts
        foreach ($arrHosts as $hostId) {
            $query = "
                UPDATE ".DBPREFIX."module_".self::TABLE_PREFIX."_host
                SET cat_id=?
                WHERE id=?";
            $objResult = $objDatabase->Execute($query,
                array($this->id, $hostId));
        }
        if ($objResult) {
            //Clear cache
            $this->triggerEvent('clearEsiCache');
            return true;
        }
        return false;
    }

    /**
     * Delete the Category
     * @return  boolean                 True on success, false otherwise
     */
    function delete()
    {
        global $objDatabase;
        $category = $this->getCategoryEntity($this->id);
        //Trigger preRemove event for Category Entity
        $this->triggerEvent(
            'model/preRemove', $category,
            array(
                'relations' => array('oneToMany' => 'getCategoryNames')
            ), true
        );
        $query = "
            DELETE FROM ".DBPREFIX."module_".self::TABLE_PREFIX."_category
            WHERE id=?";
        $objResult = $objDatabase->Execute($query, array($this->id));
        if (!$objResult) {
            return false;
        }
        $categoryNames = $category->getCategoryNames();
        foreach ($categoryNames as $categoryName) {
            //Trigger preRemove event for CategoryName Entity
            $this->triggerEvent('model/preRemove', $categoryName);
        }
        $query = "
            DELETE FROM ".DBPREFIX."module_".self::TABLE_PREFIX."_category_name
            WHERE cat_id=?";
        $objResult = $objDatabase->Execute($query, array($this->id));
        if (!$objResult) {
            return false;
        }
        foreach ($categoryNames as $categoryName) {
            //Trigger postRemove event for CategoryName Entity
            $this->triggerEvent('model/postRemove', $categoryName);
        }
        //Trigger postRemove event for Category Entity
        $this->triggerEvent('model/postRemove', $category);
        $this->triggerEvent('model/postFlush');
        $query = "
            UPDATE ".DBPREFIX."module_".self::TABLE_PREFIX."_host
            SET cat_id='0'
            WHERE cat_id=?";
        $objResult = $objDatabase->Execute($query, array($this->id));
        if ($objResult) {
            // Clear Cache
            $this->triggerEvent('clearEsiCache');
            return true;
        }
        return false;
    }

    /**
     * Return the count of Events in the Category
     * @return  integer                 The Event count
     */
    function countEntries($getAll = false, $onlyActive = false)
    {
        // get startdate
        if (!empty($_GET['from'])) {
            $startDate = $this->getDateTime($_GET['from']);
        } else if ($_GET['cmd'] == 'archive') {
            $startDate = null;
        } else {
            $startDate = new \DateTime();
            $startDay   = isset($_GET['day']) ? $_GET['day'] : $startDate->format('d');
            $startDay   = $_GET['cmd'] == 'boxes' ? 1 : $startDay;
            $startMonth = isset($_GET['month']) ? $_GET['month'] : $startDate->format('m');
            $startYear  = isset($_GET['year']) ? $_GET['year'] : $startDate->format('Y');
            $startDate->setDate($startYear, $startMonth, $startDay);
            $startDate->setTime(0, 0, 0);
        }
        // get enddate
        if (!empty($_GET['till'])) {
            $endDate = $this->getDateTime($_GET['till']);
        } else if ($_GET['cmd'] == 'archive') {
            $endDate = new \DateTime();
        } else {
            $endDate = new \DateTime();
            $endDay   = isset($_GET['endDay']) ? $_GET['endDay'] : $endDate->format('d');
            $endMonth = isset($_GET['endMonth']) ? $_GET['endMonth'] : $endDate->format('m');
            $endYear  = isset($_GET['endYear']) ? $_GET['endYear'] : $endDate->format('Y');
            $endYear = empty($_GET['endYear']) && empty($_GET['endMonth']) ? $endYear + 10 : $endYear;
            $endDate->setDate($endYear, $endMonth, $endDay);
            $endDate->setTime(23, 59, 59);
        }
        $searchTerm = !empty($_GET['term']) ? contrexx_addslashes($_GET['term']) : null;
        // set the start date as null if $getAll is true
        if ($getAll) {
            $startDate = null;
        }
        $objEventManager =
            new \Cx\Modules\Calendar\Controller\CalendarEventManager(
                $startDate, $endDate, $this->id, $searchTerm,
                true, false, $onlyActive);
        $objEventManager->getEventList();
        $count = count($objEventManager->eventList);
        return $count;
    }

    /**
     * Return the Category entity
     * @param   integer     $id         The Category id
     * @param   array       $formData   The Category field values
     * @return  \Cx\Modules\Calendar\Model\Entity\Category
     */
    public function getCategoryEntity($id, $formData = array())
    {
        global $_LANGID;
        if (empty($id)) {
            $category = new \Cx\Modules\Calendar\Model\Entity\Category();
        } else {
            $category = $this
                ->em
                ->getRepository('Cx\Modules\Calendar\Model\Entity\Category')
                ->findOneById($id);
        }
        $category->setVirtual(true);
        if (!$category) {
            return null;
        }
        if (!$formData) {
            return $category;
        }
        foreach ($formData as $fieldName => $fieldValue) {
            if ($fieldName == 'categoryNames' && is_array($fieldValue)) {
                foreach ($fieldValue as $langId => $value) {
                    if ($langId == 0) {
                        continue;
                    }
                    $value = ($value == '') ? $fieldValue[0] : $value;
                    if ($langId == $_LANGID) {
                        $value = ($fieldValue[0] != $this->name)
                            ? $fieldValue[0] : $value;
                    }
                    $formData = array(
                        'catId'  => $id,
                        'name'   => $value,
                        'langId' => $langId
                    );
                    $this->getCategoryNameEntity($category, $formData);
                }
            } else {
                $category->{'set'.ucfirst($fieldName)}($fieldValue);
            }
        }
        return $category;
    }

    /**
     * Return the CategoryName entity
     * @param   \Cx\Modules\Calendar\Model\Entity\Category
     *                      $category    The Category entity
     * @param   array       $fieldValues The CategoryName field values
     * @return  \Cx\Modules\Calendar\Model\Entity\CategoryName
     */
    public function getCategoryNameEntity(
        \Cx\Modules\Calendar\Model\Entity\Category $category,
        $fieldValues
    ){
        $isNewEntity  = false;
        $categoryName = $category->getCategoryNameByLangId($fieldValues['langId']);
        if (!$categoryName) {
            $isNewEntity  = true;
            $categoryName = new \Cx\Modules\Calendar\Model\Entity\CategoryName();
        }
        $categoryName->setVirtual(true);
        foreach ($fieldValues as $fieldName => $fieldValue) {
            $methodName = 'set'.ucfirst($fieldName);
            if (method_exists($categoryName, $methodName)) {
                $categoryName->{$methodName}($fieldValue);
            }
        }
        if ($isNewEntity) {
            $category->addCategoryName($categoryName);
            $categoryName->setCategory($category);
        }
        return $categoryName;
    }

    /**
     * Return all Category IDs associated with the given Event ID
     *
     * Returns false on error.
     * If the return value is the empty array, there was no error per se,
     * but no Category is associated with the Event.
     * This should not happen, however, and indicates that the Event
     * was not properly verified before storing.
     * @param   integer         $event_id   The Event ID
     * @return  array|boolean               The Category IDs on success,
     *                                      false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    public static function getIdsByEventId($event_id)
    {
        global $objDatabase;
        $query = '
            SELECT `category_id`
            FROM `'.DBPREFIX.'module_'.self::TABLE_PREFIX.'_events_categories`
            WHERE `event_id`=?';
        $objResult = $objDatabase->Execute($query, array($event_id));
        if (!$objResult) {
            return false;
        }
        $category_ids = [];
        while (!$objResult->EOF) {
            $category_ids[] = $objResult->fields['category_id'];
            $objResult->MoveNext();
        }
        return $category_ids;
    }

    /**
     * Return the current Category
     *
     * The Event may have multiple Categories associated with it.
     * If an active $category_id is given, use that.
     * Otherwise, pick the first Category associated with the Event instead.
     * @param   integer         $category_id
     * @param   CalendarEvent   $event
     * @return  \Cx\Modules\Calendar\Controller\CalendarCategory
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    public static function getCurrentCategory($category_id,
        CalendarEvent $event)
    {
        if (!$category_id) {
            $category_ids = $event->category_ids;
            if ($category_ids) {
                $category_id = current($category_ids);
            }
        }
        return
            new \Cx\Modules\Calendar\Controller\CalendarCategory($category_id);
    }

    /**
     * Update the Category-Event relation
     * @global  \ADOConnection  $objDatabase
     * @param   integer         $event_id
     * @param   array           $category_ids
     * @return  boolean                     True on success, false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    public static function updateEventRelation($event_id, $category_ids)
    {
        global $objDatabase;
        $query = '
            DELETE FROM `'.DBPREFIX.'module_'.self::TABLE_PREFIX.'_events_categories`
            WHERE `event_id`=?';
        $objResult = $objDatabase->Execute($query, array($event_id));
        if (!$objResult) {
            return false;
        }
        $query = '
            INSERT INTO `'.DBPREFIX.'module_'.self::TABLE_PREFIX.'_events_categories` (
                `event_id`, `category_id`
            ) VALUES ('
            . join('), (', array_map(function($category_id) use ($event_id) {
                return $event_id.', '.$category_id;
            }, $category_ids))
            . ')';
        $objResult = $objDatabase->Execute($query, array($event_id));
        if (!$objResult) {
            return false;
        }
        return true;
    }

}
