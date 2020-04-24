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
 * User Profile
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Daeppen <thomas.daeppen@comvation.com>
 * @version     2.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */

/**
 * User Profile
 *
 * The User object is used for all user related operations.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Daeppen <thomas.daeppen@comvation.com>
 * @version     2.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 * @uses        /lib/FRAMEWORK/User/User_Profile_Attribute.class.php
 */
class User_Profile
{
    /**
     * @var User_Profile_Attribute
     */
    public $objAttribute;
    public $arrAttributeHistories;
    public $arrUpdatedAttributeHistories;

    /**
     * @access private
     * @var array
     */
    public static $arrNoAvatar = array(
        'src'        => '0_noavatar.gif',
        'width'        => 121,
        'height'    => 160
    );

    public static $arrNoPicture = array(
        'src'        => '0_no_picture.gif',
        'width'        => 80,
        'height'    => 84
    );


    public function __construct()
    {
        $this->initAttributes();
    }


    private function initAttributes()
    {
        $this->objAttribute = new User_Profile_Attribute();
    }


    public function setProfile($arrProfile, $ignoreAccessPermissions = false)
    {
        $arrDate = array();
        $arrDateFormat = array();
        foreach ($arrProfile as $attributeId => $arrValue) {
            if (!is_array($arrValue)) {
                continue;
            }

            $objAttribute = $this->objAttribute->getById($attributeId);
            if (in_array($objAttribute->getType(), array('menu_option', 'group', 'frame', 'history'))) {
                continue;
            }

            if (isset($this->arrLoadedUsers[$this->id]['profile'][$attributeId])) {
                $arrStoredAttributeData = $this->arrLoadedUsers[$this->id]['profile'][$attributeId];
            } else {
                $arrStoredAttributeData = array();
            }
            $this->arrLoadedUsers[$this->id]['profile'][$attributeId] = array();
            foreach ($arrValue as $historyId => $value) {
                if ($this->objAttribute->isHistoryChild($attributeId) && !$historyId) {
                    continue;
                }

                if ($this->objAttribute->isHistoryChild($attributeId) && $historyId === 'new') {
                    $historyId = 0;
                    $arrValues = $value;
                } else {
                    $arrValues = array($value);
                }

                foreach ($arrValues as $nr => $value) {
                    $value = trim(contrexx_stripslashes($value));

                    if ($objAttribute->getType() === 'date') {
                        if (is_array($value)) {
                            $objDateTime = new DateTime("${value['month']}/${value['day']}/${value['year']}");
                            $value = $objDateTime->format(ASCMS_DATE_FORMAT_DATE);
                        }

                        if (preg_match_all('#([djmnYy])+#', ASCMS_DATE_FORMAT_DATE, $arrDateFormat, PREG_PATTERN_ORDER) && preg_match_all('#([0-9]+)#', $value, $arrDate)) {
                            foreach ($arrDateFormat[1] as $charNr => $char) {
                                $arrDateCombined[$char] = $arrDate[1][$charNr];
                            }

                            $value = gmmktime(1, 0, 0,
                                (isset($arrDateCombined['m']) ? $arrDateCombined['m'] : $arrDateCombined['n']), // month
                                (isset($arrDateCombined['d']) ? $arrDateCombined['d'] : $arrDateCombined['j']), // day
                                (isset($arrDateCombined['Y']) ? $arrDateCombined['Y'] : ($arrDateCombined['y'] + ($arrDateCombined['y'] < 70 ? 2000 : 1900))) // year
                            );
                        } elseif ($this->objAttribute->isCoreAttribute($attributeId)) {
                            $value = '';
                        } else {
                            continue;
                        }
                    }

                    if ($objAttribute->getId() &&
                        (
                            $ignoreAccessPermissions ||
                            !$objAttribute->isProtected() ||
                            (
                                Permission::checkAccess($objAttribute->getAccessId(), 'dynamic', true) ||
                                $objAttribute->checkModifyPermission(
                                    (in_array($attributeId, array('title', 'country')) ? $attributeId.'_' : '').(isset($arrStoredAttributeData[$historyId]) ? $arrStoredAttributeData[$historyId] : null),
                                    (in_array($attributeId, array('title', 'country')) ? $attributeId.'_' : '').$value)
                            )
                        )
                    ) {
                        if ($this->objAttribute->isHistoryChild($attributeId) && !$historyId) {
                            $historyId = (isset($this->arrAttributeHistories[$this->id][$this->objAttribute->getHistoryAttributeId($attributeId)]) ? max($this->arrAttributeHistories[$this->id][$this->objAttribute->getHistoryAttributeId($attributeId)]) : 0)+1;
                        }

                        $this->arrLoadedUsers[$this->id]['profile'][$attributeId][$historyId+$nr] = $value;
                        if ($historyId+$nr &&
                            (!isset($this->arrUpdatedAttributeHistories[$this->id][$this->objAttribute->getHistoryAttributeId($attributeId)]) ||
                            !in_array($historyId+$nr, $this->arrUpdatedAttributeHistories[$this->id][$this->objAttribute->getHistoryAttributeId($attributeId)]))
                        ) {
                            $this->arrUpdatedAttributeHistories[$this->id][$this->objAttribute->getHistoryAttributeId($attributeId)][] = $historyId+$nr;
                        }
                    } else {
                        $this->arrLoadedUsers[$this->id]['profile'][$attributeId] = $arrStoredAttributeData;
                        continue;
                    }
                }
            }
        }

        // synchronize history-ID's
        $this->arrAttributeHistories[$this->id] = $this->arrUpdatedAttributeHistories[$this->id];

        return true;
    }


    public function checkMandatoryCompliance()
    {
        global $_CORELANG;

        foreach ($this->objAttribute->getMandatoryAttributeIds() as $attributeId) {
            $arrHistoryIds = array();
            $historyAttributeId = $this->objAttribute->getHistoryAttributeId($attributeId);
            if (!$historyAttributeId) {
                $arrHistoryIds[] = 0;
            } elseif (isset($this->arrUpdatedAttributeHistories[$this->id][$historyAttributeId])) {
                $arrHistoryIds = $this->arrUpdatedAttributeHistories[$this->id][$historyAttributeId];
            }

            foreach ($arrHistoryIds as $historyId) {
                if (
                       empty($this->arrLoadedUsers[$this->id]['profile'][$attributeId][$historyId])
                    || $this->objAttribute->isCoreAttribute($attributeId)
                       && ($objAttribute = $this->objAttribute->getById($attributeId))
                       && $objAttribute->getType() == 'menu'
                       && $objAttribute->isUnknownOption($this->arrLoadedUsers[$this->id]['profile'][$attributeId][$historyId])
                ) {
                    $this->error_msg[] = $_CORELANG['TXT_ACCESS_FILL_OUT_ALL_REQUIRED_FIELDS'];
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  mixed    $profileUpdated    If $profileUpdated is provided, then in case any profile
     *                                  changes are being flushed to the database, $profileUpdated
     *                                  will be set to TRUE, otherwise it'll be left untouched.
     */
    protected function storeProfile(&$profileUpdated = null)
    {
        global $objDatabase, $_CORELANG;

        $error = false;

        foreach ($this->arrLoadedUsers[$this->id]['profile'] as $attributeId => $arrValue)
        {
            foreach ($arrValue as $historyId => $value)
            {
                $newValue = !isset($this->arrCachedUsers[$this->id]['profile'][$attributeId][$historyId]);
                if ($newValue || $value != $this->arrCachedUsers[$this->id]['profile'][$attributeId][$historyId]) {
                    $value = '"' . contrexx_raw2db($value) . '"';
                    if (
                        $attributeId == 'title' && (
                            $value == '""' ||
                            $value == '"0"'
                        )
                    ) {
                        $value = 'NULL';
                    }
                    $query = $this->objAttribute->isCoreAttribute($attributeId) ?
                        "UPDATE `".DBPREFIX."access_user_profile` SET `".$attributeId."` = " . $value . " WHERE `user_id` = ".$this->id :
                        ($newValue ?
                            "INSERT INTO `".DBPREFIX."access_user_attribute_value` (`user_id`, `attribute_id`, `history_id`, `value`) VALUES (".$this->id.", ".$attributeId.", ".$historyId.", " . $value . ")" :
                            "UPDATE `".DBPREFIX."access_user_attribute_value` SET `value` = " . $value . " WHERE `user_id` = ".$this->id." AND `attribute_id` = ".$attributeId." AND `history_id` = ".$historyId
                        );

                    if ($objDatabase->Execute($query) === false) {
                        $objAttribute = $this->objAttribute->getById($attributeId);
                        $error = true;
                        $this->error_msg[] = sprintf($_CORELANG['TXT_ACCESS_UNABLE_STORE_PROFILE_ATTIRBUTE'], htmlentities($objAttribute->getName(), ENT_QUOTES, CONTREXX_CHARSET));
                    } elseif ($objDatabase->Affected_Rows()) {
                        // track flushed db change
                        $profileUpdated = true;
                    }
                }
            }

            if ($this->objAttribute->isCustomAttribute($attributeId) && isset($this->arrCachedUsers[$this->id]['profile'][$attributeId])) {
                foreach (array_diff(array_keys($this->arrCachedUsers[$this->id]['profile'][$attributeId]), array_keys($arrValue)) as $historyId) {
                    if ($objDatabase->Execute('DELETE FROM `'.DBPREFIX.'access_user_attribute_value` WHERE `attribute_id` = '.$attributeId.' AND `user_id` = '.$this->id.' AND `history_id` = '.$historyId) === false) {
                        $objAttribute = $this->objAttribute->getById($attributeId);
                        $error = true;
                        $this->error_msg[] = sprintf($_CORELANG['TXT_ACCESS_UNABLE_STORE_PROFILE_ATTIRBUTE'], htmlentities($objAttribute->getName(), ENT_QUOTES, CONTREXX_CHARSET));
                    } elseif ($objDatabase->Affected_Rows()) {
                        // track flushed db change
                        $profileUpdated = true;
                    }
                }
            }
        }

        return !$error;
    }


    /**
     * Create a profile for the loaded user
     *
     * This creates an entry in the dabase table contrexx_access_user_profile which is related to the entry in the table cotnrexx_access_users of the same user.
     * This methode will be obsolete as soon as we're using InnoDB as storage engine.
     *
     * @return boolean
     */
    protected function createProfile()
    {
        global $objDatabase;
        if ($objDatabase->Execute('INSERT INTO `'.DBPREFIX.'access_user_profile` SET `user_id` = '.$this->id . ', `title` = NULL') !== false) {
            $this->arrLoadedUsers[$this->id]['profile'] = isset($this->arrLoadedUsers[0]['profile']) ? $this->arrLoadedUsers[0]['profile'] : array();
            return true;
        } else {
            $objDatabase->Execute('DELETE FROM `'.DBPREFIX.'access_user_profile` WHERE `user_id` = '.$this->id);
            return false;
        }
    }


    /**
     * Load custom attribute profile data
     *
     * Gets the data of the custom profile attributes from the database an puts it into the class variables $this->arrLoadedUsers and $this->arrCachedUsers.
     * On the other hand it fills the class variables $this->arrAttributeHistories and $this->arrUpdataedAttributeHistories with the history IDs of each attribute.
     * Returns FALSE if a database error had occurred, otherwise TRUE.
     *
     * @param array $arrAttributes
     * @return boolean
     */
    protected function loadCustomAttributeProfileData($arrAttributes = array())
    {
        global $objDatabase;

        $query = 'SELECT `attribute_id`, `user_id`, `history_id`, `value`
            FROM `'.DBPREFIX.'access_user_attribute_value`
            WHERE (`user_id` = '.implode(' OR `user_id` = ', array_keys($this->arrLoadedUsers)).')'
            .(count($arrAttributes) ? ' AND (`attribute_id` = '.implode(' OR `attribute_id` = ', $arrAttributes).')' : '');

        $objAttributeValue = $objDatabase->Execute($query);

        if ($objAttributeValue !== false && $objAttributeValue->RecordCount() > 0) {
            while (!$objAttributeValue->EOF) {
                $this->arrCachedUsers[$objAttributeValue->fields['user_id']]['profile'][$objAttributeValue->fields['attribute_id']][$objAttributeValue->fields['history_id']] =
                    $this->arrLoadedUsers[$objAttributeValue->fields['user_id']]['profile'][$objAttributeValue->fields['attribute_id']][$objAttributeValue->fields['history_id']] =
                        $objAttributeValue->fields['value'];
                if ($objAttributeValue->fields['history_id'] &&
                    ($historyAttributeId = $this->objAttribute->getHistoryAttributeId($objAttributeValue->fields['attribute_id'])) !== false &&
                    (
                        !isset($this->arrAttributeHistories[$objAttributeValue->fields['user_id']][$historyAttributeId]) ||
                        !in_array($objAttributeValue->fields['history_id'], $this->arrAttributeHistories[$objAttributeValue->fields['user_id']][$historyAttributeId])
                    )
                ) {
                    $this->arrAttributeHistories[$objAttributeValue->fields['user_id']][$historyAttributeId][] = $objAttributeValue->fields['history_id'];
                    $this->arrUpdatedAttributeHistories[$objAttributeValue->fields['user_id']][$historyAttributeId][] = $objAttributeValue->fields['history_id'];
                }
                $objAttributeValue->MoveNext();
            }
            return true;
        } else {
            return false;
        }
    }


    /**
     * Parse core attribute filter conditions
     *
     * Generate conditions of the core attributes for the SQL WHERE statement.
     * The filter conditions are defined through the two dimensional array $arrFilter.
     * Each key-value pair represents an attribute and its associated condition to which it must fit to.
     * The condition could either be a integer or string depending on the attributes type, or it could be
     * a collection of integers or strings represented in an array.
     *
     *
     * @param array $arrFilter
     * @return array
     */
    protected function parseCoreAttributeFilterConditions($arrFilter)
    {
        if (empty($this->objAttribute)) {
            $this->initAttributes();
        }

        $arrConditions = array();
        $pattern = array();
        foreach ($arrFilter as $attribute => $condition) {
            /**
             * $attribute is the account profile attribute like 'firstname' or 'lastname'
             * $condition is either a simple condition (integer or string) or an condition matrix (array)
             */
            if ($this->objAttribute->load($attribute) && $this->objAttribute->isCoreAttribute($attribute)) {
                switch ($attribute) {
                    case 'gender':
                        $arrConditions[] = "(tblP.`{$attribute}` = '".(is_array($condition) ? implode("' OR tblP.`{$attribute}` = '", array_map('addslashes', $condition)) : addslashes($condition))."')";
                        break;

                    case 'title':
                    case 'country':
                        $arrConditions[] = '(tblP.`' . $attribute . '` = ' . (
                            is_array($condition)
                                ? implode(
                                    ' OR tblP.`' . $attribute . '` = ',
                                    array_map(
                                        function ($condition) {
                                            if (preg_match('#([0-9]+)#', $condition, $pattern)) {
                                                return $pattern[0];
                                            }

                                            return 0;
                                        },
                                        $condition
                                    )
                                )
                                : (preg_match('#([0-9]+)#', $condition, $pattern) ? $pattern[0] : 0)
                        ) . ')';
                        break;

                    default:
                        $arrComparisonOperators = array(
                            'int'       => array('=','<','>'),
                            'string'    => array('!=','<','>', 'REGEXP')
                        );
                        $arrDefaultComparisonOperator = array(
                            'int'       => '=',
                            'string'    => 'LIKE'
                        );
                        $arrEscapeFunction = array(
                            'int'       => 'intval',
                            'string'    => 'addslashes'
                        );

                        if (is_array($condition)) {
                            $arrRestrictions = array();
                            foreach ($condition as $operator => $restriction) {
                                /**
                                 * $operator is either a comparison operator ( =, LIKE, <, >) if $restriction is an array or if $restriction is just an integer or a string then its an index which would be useless
                                 * $restriction is either a integer or a string or an array which represents a restriction matrix
                                 */
                                if (is_array($restriction)) {
                                    $arrConditionRestriction = array();
                                    foreach ($restriction as $restrictionOperator => $restrictionValue) {
                                        /**
                                         * $restrictionOperator is a comparison operator ( =, <, >)
                                         * $restrictionValue represents the condition
                                         */
                                        $arrConditionRestriction[] = "tblP.`{$attribute}` ".(
                                            in_array($restrictionOperator, $arrComparisonOperators[$this->objAttribute->getDataType()], true) ?
                                                $restrictionOperator
                                            :   $arrDefaultComparisonOperator[$this->objAttribute->getDataType()]
                                        )." '".$arrEscapeFunction[$this->objAttribute->getDataType()]($restrictionValue)."'";
                                    }
                                    $arrRestrictions[] = implode(' AND ', $arrConditionRestriction);
                                } else {
                                    $arrRestrictions[] = "tblP.`{$attribute}` ".(
                                        in_array($operator, $arrComparisonOperators[$this->objAttribute->getDataType()], true) ?
                                            $operator
                                        :   $arrDefaultComparisonOperator[$this->objAttribute->getDataType()]
                                    )." '".$arrEscapeFunction[$this->objAttribute->getDataType()]($restriction)."'";
                                }
                            }
                            $arrConditions[] = '(('.implode(') OR (', $arrRestrictions).'))';
                        } else {
                            $arrConditions[] = "(tblP.`".$attribute."` ".$arrDefaultComparisonOperator[$this->objAttribute->getDataType()]." '".$arrEscapeFunction[$this->objAttribute->getDataType()]($condition)."')";
                        }
                        break;
                }


            } elseif ($attribute === 'birthday_day') {
                $arrConditions[] = "(DATE_FORMAT(DATE_ADD(FROM_UNIXTIME(0), interval `tblP`.`birthday` second), '%e') = '".intval($condition)."')";
            } elseif ($attribute === 'birthday_month') {
                $arrConditions[] = "(DATE_FORMAT(DATE_ADD(FROM_UNIXTIME(0), interval `tblP`.`birthday` second), '%c') = '".intval($condition)."')";
            }
        }

        return $arrConditions;
    }


    /**
     * Parse custom attribute filter conditions
     *
     * Generate conditions of the custom attributes for the SQL WHERE statement.
     * The filter conditions are defined through the two dimensional array $arrFilter.
     * Each key-value pair represents an attribute and its associated condition to which it must fit to.
     * The condition could either be a integer or string depending on the attributes type, or it could be
     * a collection of integers or strings represented in an array.
     *
     * Matches single (scalar) or multiple (array) search terms against a
     * number of fields.  Generally, the term(s) are enclosed in percent
     * signs ("%term%"), so any fields that contain them will match.
     * However, if the search parameter is a string and does contain a percent
     * sign already, none will be added to the query.
     * This allows searches using custom patterns, like "fields beginning
     * with "a" ("a%").
     * (You might even want to extend this to work with arrays, too.
     * Currently, only the shop module uses this feature.) -- RK 20100910
     * @param   mixed     $arrFilter    The term or array of terms
     * @param   string    $forceTableIdx    Argument must not be used
     *                                      directly. Will internally be used
     *                                      for recursion (parsing child
     *                                      attributes of complex profile
     *                                      attributes like gender or
     *                                      country). If $forceTableIdx is set
     *                                      then its value will be used as
     *                                      prefix for the table alias of the
     *                                      SQL statement.
     * @param   boolean $uniqueJoins Whether the filter arguments shall be
     *                               joined by separate unique JOINs or by
     *                               a single common JOIN statement.
     * @param   integer $joinIdx     The current index used for separate
     *                               unique JOINs.
     * @return  array                   The array of SQL snippets
     */
    protected function parseCustomAttributeFilterConditions(
        $arrFilter,
        $forceTableIdx = null,
        $uniqueJoins = true,
        &$joinIdx = 0
    )
    {
        if (empty($this->objAttribute)) {
            $this->initAttributes();
        }

        $arrConditions = array();
        foreach ($arrFilter as $attribute => $condition) {
            if (!$this->objAttribute->load($attribute) || $this->objAttribute->isCoreAttribute($attribute)) {
                continue;
            }

            if ($forceTableIdx) {
                $tableIdx = $forceTableIdx;
            } else {
                $tableIdx = 'tblExp' . $joinIdx;
            }

            switch ($this->objAttribute->getDataType()) {
                case 'string':
                    $percent = '%';
                    if (   !is_array($condition)
                        && strpos('%', $condition) !== false) $percent = '';
                    $arrConditions[$tableIdx] =
                        $tableIdx.".`attribute_id` = ".$attribute.
                        " AND (".$tableIdx.".`value` LIKE '$percent".
                        (is_array($condition)
                          ? implode("$percent' OR ".$tableIdx.".`value` LIKE '$percent",
                                array_map('addslashes', $condition))
                          : addslashes($condition))."$percent')";
                    break;

                case 'int':
                    $arrConditions[$tableIdx] = $tableIdx.".`attribute_id` = ".$attribute." AND (".$tableIdx.".`value` = '".(is_array($condition) ? implode("' OR ".$tableIdx.".`value` = '", array_map('intval', $condition)) : intval($condition))."')";
                    break;
                case 'array':
                    if (count($this->objAttribute->getChildren())) {
                        foreach ($this->objAttribute->getChildren() as $childAttributeId) {
                            $arrSubFilter[$childAttributeId] = $condition;
                        }
                        $arrConditions[$tableIdx] = implode(
                            ' OR ',
                            $this->parseCustomAttributeFilterConditions(
                                $arrSubFilter,
                                $tableIdx,
                                $uniqueJoins,
                                $joinIdx
                            )
                        );
                    }
                    break;
            }

            if ($uniqueJoins) {
                $joinIdx++;
            }
        }

        return $arrConditions;
    }


    /**
     * Enter description here...
     *

     * Matches single (scalar) or multiple (array) search terms against a
     * number of fields.  Generally, the term(s) are enclosed in percent
     * signs ("%term%"), so any fields that contain them will match.
     * However, if the search parameter is a string and does contain a percent
     * sign already, none will be added to the query.
     * This allows searches using custom patterns, like "fields beginning
     * with "a" ("a%").
     * (You might even want to extend this to work with arrays, too.
     * Currently, only the shop module uses this feature.) -- RK 20100910
     * @param   mixed     $search       The term or array of terms
     * @param unknown_type $core
     * @param unknown_type $attributeId
     * @return  array                   The array of SQL snippets
     */
    protected function parseAttributeSearchConditions($search, $core = false, $attributeId = 0)
    {
        $arrConditions = array();
        $pattern = array();

        if (empty($this->objAttribute)) {
            $this->initAttributes();
        }
        $objParentAttribute = $this->objAttribute->getById($attributeId);

        if ($core) {
            $attributeKeyClausePrefix = '';
            $attributeKeyClauseSuffix = '';
        } else {
            $attributeValueColumn = 'tblA.`value`';
        }

        foreach ($objParentAttribute->{'get'.($core ? 'Core' : 'Custom').'AttributeIds'}() as $attributeId) {
            $objAttribute = $objParentAttribute->getById($attributeId);

            // do not allow lookup on attributes the user has no read access to
            if (!$objAttribute->checkReadPermission()) {
                continue;
            }

            if ($core) {
                $attributeValueColumn = 'tblP.`'.$objAttribute->getId().'`';
            } else {
                $attributeKeyClausePrefix = '(tblA.`attribute_id` = '.$objAttribute->getId().' AND ';
                $attributeKeyClauseSuffix = ')';
            }

            switch ($objAttribute->getType()) {
                case 'text':
                case 'mail':
                case 'uri':
                case 'image':
                    switch ($objAttribute->getDataType()) {
                        case 'int':
                            $arrConditions[] = $attributeKeyClausePrefix.'('.$attributeValueColumn.' = '.(is_array($search) ? implode(' OR '.$attributeValueColumn.' = ', array_map('intval', $search)) : intval($search)).')'.$attributeKeyClauseSuffix;
                            break;

                        case 'string':
                            $percent = '%';
                            if (   !is_array($search)
                                && strpos('%', $search) !== false) $percent = '';
                            $arrConditions[] =
                                $attributeKeyClausePrefix."(".$attributeValueColumn." LIKE '$percent".
                                (is_array($search)
                                    ? implode("$percent' OR ".$attributeValueColumn." LIKE '$percent",
                                          array_map('addslashes', $search))
                                    : addslashes($search))."$percent')".$attributeKeyClauseSuffix;
                            break;

                        default:
                            break 2;
                    }
                    break;

                case 'menu':
                    $arrMatchedChildren = array();
                    foreach ($objAttribute->getChildren() as $childAttributeId) {
                        $objChildAttribute = $objAttribute->getById($childAttributeId);
                        if (is_array($search)) {
                            foreach ($search as $name) {
                                if (stristr($objChildAttribute->getName(), $name)) {
                                    $arrMatchedChildren[] = in_array($attributeId, array('title', 'country')) ? (preg_match('#([0-9]+)#', $childAttributeId, $pattern) ? $pattern[0] : 0) : $childAttributeId;
                                    break;
                                }
                            }
                        } elseif (stristr($objChildAttribute->getName(), $search)) {
                            $arrMatchedChildren[] = in_array($attributeId, array('title', 'country')) ? (preg_match('#([0-9]+)#', $childAttributeId, $pattern) ? $pattern[0] : 0) : $childAttributeId;
                        }
                    }
                    if (count($arrMatchedChildren)) {
                        $arrConditions[] = $attributeKeyClausePrefix."(".$attributeValueColumn." = '".implode("' OR ".$attributeValueColumn." = '", $arrMatchedChildren)."')".$attributeKeyClauseSuffix;
                    }
                    break;

                /*case 'frame':
                case 'history':
                    foreach ($objAttribute->getChildren() as $childAttributeId) {
                        $arrConditions = array_merge($arrConditions, $this->parseAttributeSearchConditions($search, $core, $childAttributeId));
                    }
                    break;

                case 'group':
                    foreach ($objAttribute->getChildren() as $childAttributeId) {
                        $arrConditions = array_merge($arrConditions, $this->parseAttributeSearchConditions($search, $core, $childAttributeId));
                    }
                    break;*/

                default:
// TODO: What is this good for?
                    continue 2;
                    break;
            }
        }
        return $arrConditions;
    }

}
