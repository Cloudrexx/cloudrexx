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
 * Media Directory Export Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/**
 * Media Directory Export Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryExport extends MediaDirectoryLibrary
{
    private $csvSeparator = ';';
    private $elementSeparator = ',';
    /**
     * Constructor
     */
    function __construct($name)
    {
        parent::__construct('.', $name);
    }

    function exportCSV($intFormId, $arrCategoryIds=null, $arrLevelIds=null, $intMaskId=null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        if($intFormId != null) {
            $objValidator = new \FWValidator();
            $arrEntries = array();
            $arrEntriesData = array();
            $arrInputfields = array();
            $arrMask = array();

            if($intMaskId != null && $intMaskId != 0) {
                $objResultMask = $objDatabase->Execute("SELECT
                                                    fields, form_id
                                                FROM
                                                    ".DBPREFIX."module_".$this->moduleTablePrefix."_masks
                                                WHERE id = '".$intMaskId."'
                                               ");
                if ($objResultMask !== false) {
                    $arrMask = explode(',', $objResultMask->fields['fields']);
                    $intFormId = $objResultMask->fields['form_id'];
                }
            }

            $objForm = new MediaDirectoryForm($intFormId, $this->moduleName);
            $objInputfields = new MediaDirectoryInputfield($intFormId, false, null, $this->moduleName);
            $strFilename = contrexx_raw2encodedUrl($objForm->arrForms[$intFormId]['formName'][0])."_".time().".csv";

            if($arrCategoryIds != null) {
                foreach($arrCategoryIds as $intKey => $intCategoryId) {
                    if($arrLevelIds != null) {
                        $strDatabaseLevel = ",".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_levels AS level";
                        $strLevels = join(',', $arrLevelIds);
                        $strWhereLevel = " AND ((cat.entry_id = level.entry_id) AND (level.level_id IN (".$strLevels.")))";
                    }

                    $objResultCategories = $objDatabase->Execute("SELECT
                                                                        cat.entry_id AS entryId
                                                                    FROM
                                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories AS cat
                                                                        ".$strDatabaseLevel."
                                                                    WHERE
                                                                        cat.category_id ='".$intCategoryId."'
                                                                        ".$strWhereLevel."
                                                                   ");
                    if ($objResultCategories !== false) {
                        while (!$objResultCategories->EOF) {
                            $arrEntries[$objResultCategories->fields['entryId']] = $objResultCategories->fields['entryId'];
                            $objResultCategories->MoveNext();
                        }
                    }
                }
            } else if($arrLevelIds != null) {
                foreach($arrLevelIds as $intKey => $intLevelId) {
                    $objResultLevels = $objDatabase->Execute("SELECT
                                                                        level.entry_id AS entryId
                                                                    FROM
                                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_levels AS level
                                                                    WHERE
                                                                        level.level_id ='".$intLevelId."'
                                                                   ");
                    if ($objResultLevels !== false) {
                        while (!$objResultLevels->EOF) {
                            $arrEntries[$objResultLevels->fields['entryId']] = $objResultLevels->fields['entryId'];
                            $objResultLevels->MoveNext();
                        }
                    }
                }
            } else {
                $objEntry = new MediaDirectoryEntry($this->moduleName);
                $objEntry->getEntries(null, null, null, null, null, null, true, null, 'n', null, null, $intFormId);

                foreach($objEntry->arrEntries as $intEntryId => $arrEntry) {
                    $arrEntries[$intEntryId] = $intEntryId;
                }
            }

            foreach($arrEntries as $intKey => $intEntryId) {
                $objResultEntry = $objDatabase->Execute("SELECT
                                                                entry.value AS value, entry.form_id AS formId, entry.field_id AS fieldId
                                                            FROM
                                                                ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields AS entry
                                                            WHERE
                                                                entry.entry_id ='".$intEntryId."'
                                                            AND
                                                                entry.lang_id ='" . static::getOutputLocale()->getId() . "'
                                                           ");
                if ($objResultEntry !== false) {
                    while (!$objResultEntry->EOF) {
                        if($objResultEntry->fields['formId'] == $intFormId) {
                            $arrEntriesData[$intEntryId][$objResultEntry->fields['fieldId']] = $objResultEntry->fields['value'];
                        }
                        $objResultEntry->MoveNext();
                    }
                }
            }

            foreach($objInputfields->arrInputfields as $intFieldId => $arrField) {
                $arrInputfields[$arrField['order']]['id'] = $intFieldId;
                $arrInputfields[$arrField['order']]['name'] = $arrField['name'][0];
            }

            ksort($arrInputfields);

            header("Content-Type: text/comma-separated-values; charset=".CONTREXX_CHARSET, true);
            header("Content-Disposition: attachment; filename=\"$strFilename\"", true);

            foreach($arrInputfields as $intKey => $arrField) {
                if($intMaskId == null || ($arrMask != null && in_array($arrField['id'], $arrMask))) {
                    print self::escapeCsvValue($arrField['name']).$this->csvSeparator;
                }
            }

            print "\r\n";

            foreach($arrEntriesData as $intEntryId => $arrEntry) {
                foreach($arrInputfields as $intFieldOrder => $arrField) {
                    if($intMaskId == null || ($arrMask != null && in_array($arrField['id'], $arrMask))) {
                        switch($arrField['id']) {
                            case 1:
                                $arrCategories = self::getCategoriesLevels(1, $intEntryId);
                                $strFieldValue = join($this->elementSeparator, $arrCategories);
                                break;
                            case 2:
                                $arrLevels = self::getCategoriesLevels(2, $intEntryId);
                                $strFieldValue = join($this->elementSeparator, $arrLevels);
                                break;
                            default:
                                $strFieldValue = isset($arrEntriesData[$intEntryId][$arrField['id']]) ? $arrEntriesData[$intEntryId][$arrField['id']] : '';
                                $strFieldValue = strip_tags($strFieldValue);
                                $strFieldValue = self::escapeCsvValue($strFieldValue);
                                $strFieldValue = html_entity_decode($strFieldValue, ENT_QUOTES, CONTREXX_CHARSET);
                                break;
                        }
                        if(CONTREXX_CHARSET == 'UTF-8') {
                            $strFieldValue = utf8_decode($strFieldValue);
                        }
                        print $strFieldValue.$this->csvSeparator;
                    }
                }

                print "\r\n";
            }
            exit();
        } else {
            return false;
        }
    }


    function escapeCsvValue($value)
    {
        $valueModified = stripslashes($value);
        $valueModified = preg_replace('/\r\n/', " ", $valueModified);
        $valueModified = str_replace('"', '""', $valueModified);

        if ($valueModified != $value || preg_match('/['.$this->csvSeparator.'\n]+/', $value)) {
            $value = '"'.$valueModified.'"';
        }

        return $value;
    }

    function getCategoriesLevels($intType, $intEntryId=null)
    {
        global $objDatabase;

        $arrList = array();

        if($intType == 1) {
            //categories
            $query = "SELECT
                    cat_rel.`category_id` AS `elm_id`,
                    cat_name.`category_name` AS `elm_name`
                  FROM
                    ".DBPREFIX."module_mediadir_rel_entry_categories AS cat_rel,
                    ".DBPREFIX."module_mediadir_categories_names AS cat_name
                  WHERE
                    cat_rel.`category_id` = cat_name.`category_id`
                  AND
                    cat_rel.`entry_id` = '".intval($intEntryId)."'
                  AND
                    cat_name.`lang_id` = '" . static::getOutputLocale()->getId() . "'
                  ORDER BY
                    cat_name.`category_name` ASC
                  ";
        } else {
            //levels
            $query = "SELECT
                    level_rel.`level_id` AS `elm_id`,
                    level_name.`level_name` AS `elm_name`
                  FROM
                    ".DBPREFIX."module_mediadir_rel_entry_levels AS level_rel,
                    ".DBPREFIX."module_mediadir_level_names AS level_name
                  WHERE
                    level_rel.`level_id` = level_name.`level_id`
                  AND
                    level_rel.`entry_id` = '".intval($intEntryId)."'
                  AND
                    level_name.`lang_id` = '" . static::getOutputLocale()->getId() . "'
                  ORDER BY
                    level_name.`level_name` ASC
                  ";
        }

        $objEntryCategoriesLevels = $objDatabase->Execute($query);

        if ($objEntryCategoriesLevels !== false) {
            while (!$objEntryCategoriesLevels->EOF) {
                $strValue = strip_tags($objEntryCategoriesLevels->fields['elm_name']);
                $strValue = self::escapeCsvValue($strValue);
                $strValue = html_entity_decode($strValue, ENT_QUOTES, CONTREXX_CHARSET);

                if(CONTREXX_CHARSET == 'UTF-8') {
                    $strValue = utf8_decode($strValue);
                }
                $arrList[] = $objEntryCategoriesLevels->fields['elm_name'];
                $objEntryCategoriesLevels->MoveNext();
            }
        }

        return $arrList;
    }
}
?>
