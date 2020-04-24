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
 * Media Directory Search Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/**
 * Media Directory Search Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectorySearch extends MediaDirectoryLibrary
{
    public $arrFoundIds = array();

    private $arrSearchLevels = array();
    private $arrSearchCategories = array();

    /**
     * Constructor
     */
    function __construct($name)
    {
        parent::__construct('.', $name);
        parent::getSettings();
    }



    /**
     * Get HTML search form
     *
     * @param   \Cx\Core\Html\Sigma $objTpl Template object
     * @param   \Cx\Core\Routing\Url    $actionUrl  Optional Url object to be used as value of action attribute of HTML form-tag
     * @return  string  HTML search form
     */
    public function getSearchform($objTpl, $actionUrl = null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        if (isset($_GET['term'])) {
            $strSearchFormTerm = contrexx_input2xhtml($_GET['term']);
        } else {
            $strSearchFormTerm = '';
        }

        if (empty($actionUrl)) {
            $actionUrl = \Cx\Core\Routing\Url::fromPage(\Cx\Core\Core\Controller\Cx::instanciate()->getPage());
        }

        $strTextSearch = $_CORELANG['TXT_SEARCH'];
        $strTextSearchterm = $_ARRAYLANG['TXT_MEDIADIR_SEARCH_TERM'];
        $strExpandedInputfields = $this->getExpandedInputfields();
        $strSearchFormId = $this->moduleNameLC."SearchForm";
        $strInputfieldSearch = $this->moduleNameLC."InputfieldSearch";
        $strButtonSearch = $this->moduleNameLC."ButtonSearch";

        $strSearchNormalForm = <<<EOF
<div class="$strSearchFormId">
<form method="get" action="$actionUrl">
<input name="type" value="normal" type="hidden" />
<input name="term" class="$strInputfieldSearch searchbox" value="$strSearchFormTerm" onfocus="this.select();" type="text" />
<input class="$strButtonSearch" value="$strTextSearch" name="search" type="submit">
</form>
</div>
EOF;

        $strSearchExpandedForm = <<<EOF

<div class="$strSearchFormId">
<form method="get" action="$actionUrl">
<div class="normal">
<input name="type" value="exp" type="hidden" />
<p><label>$strTextSearchterm</label><input name="term" class="$strInputfieldSearch searchbox" value="$strSearchFormTerm" onfocus="this.select();" type="text" />
<input class="$strButtonSearch" value="$strTextSearch" name="search" type="submit">
</p>
</div>
<div class="expanded">
$strExpandedInputfields
</div>
</form>
</div>
EOF;

        $objTpl->setVariable(array(
            'TXT_'.$this->moduleLangVar.'_SEARCH' => $_CORELANG['TXT_SEARCH'],
            'TXT_'.$this->moduleLangVar.'_EXP_SEARCH' => $_CORELANG['TXT_EXP_SEARCH'],
            $this->moduleLangVar.'_NORMAL_SEARCH_FORM' => $strSearchNormalForm,
            $this->moduleLangVar.'_EXPANDED_SEARCH_FORM' => $strSearchExpandedForm
        ));

        $objTpl->parse($this->moduleNameLC.'Searchform');
    }



    function getExpandedInputfields()
    {
        global $_ARRAYLANG, $objDatabase;

        $formId = null;
        $strPleaseChoose = $_ARRAYLANG['TXT_MEDIADIR_PLEASE_CHOOSE'];
        $strExpandedInputfields = '';
        $bolShowLevelSelector = false;
        $bolShowCategorySelector = false;
        $formDefinition = null;

        // determine if we shall display the level and/or category selection dropdown
        if (!empty($_GET['cmd'])) {
            $arrIds = explode('-', $_GET['cmd']);

            if ($arrIds[0] == 'detail' || substr($arrIds[0],0,6) == 'detail') {
                $entryId = intval($_GET['eid']);
                $objEntry = new MediaDirectoryEntry($this->moduleName);
                $objEntry->getEntries($entryId);
                $formDefinition = $objEntry->getFormDefinition();
                $formId = $formDefinition['formId'];
            } elseif ($arrIds[0] != 'search' && $arrIds[0] != 'alphabetical'){
                $objForms = new MediaDirectoryForm(null, $this->moduleName);
                foreach ($objForms->arrForms as $id => $arrForm) {
                    // note: in a previous version of Cloudrexx, there was no check
                    // if the form was active or not. this caused unexpected
                    // behavior
                    if (
                        !$this->arrSettings['legacyBehavior'] &&
                        !$arrForm['formActive']
                    ) {
                        continue;
                    }
                    if (!empty($arrForm['formCmd']) && ($arrForm['formCmd'] == $_GET['cmd'])) {
                        $formId = intval($id);
                        $formDefinition = $objForms->arrForms[$formId];
                        break;
                    }
                }
            }

            // in case the section of a specific form has been requested, do determine
            // the usage of the level and/or category selection dropdown based on that
            // form's configuration
            //
            // note: in a previous version of Cloudrexx the following was
            //       always true. which resulted in a bug, that if a specific
            //       category was request through the page's CMD, then the
            //       level and category selection dropdowns were never used.
            //       The legacyBehavior mode does simulate this fixed bug.
            if ($formId || $this->arrSettings['legacyBehavior']) {
                if (($formDefinition['formUseLevel'] == 1) && ($this->arrSettings['levelSelectorExpSearch'][$formId] == 1)) {
                    $bolShowLevelSelector = true;
                }
                if (($formDefinition['formUseCategory'] == 1) && ($this->arrSettings['categorySelectorExpSearch'][$formId] == 1)) {
                    $bolShowCategorySelector = true;
                }
            } else {
                // on search (section=mediadir&cmd=search) and alphabetical section (section=mediadir&cmd=alphabetical)
                //
                // activate level and category selection in case they are active in any forms
                $bolShowLevelSelector = in_array(1, $this->arrSettings['levelSelectorExpSearch']);
                $bolShowCategorySelector = in_array(1, $this->arrSettings['categorySelectorExpSearch']);
            }
        } else {
            // on main application page (section=mediadir):
            //
            // activate level and category selection in case they are active in any forms
            $bolShowLevelSelector = in_array(1, $this->arrSettings['levelSelectorExpSearch']);
            $bolShowCategorySelector = in_array(1, $this->arrSettings['categorySelectorExpSearch']);
        }

        $requestParams = $this->cx->getRequest()->getUrl()->getParamArray();
        if ($this->arrSettings['settingsShowLevels'] && $bolShowLevelSelector) {
            if (intval($arrIds[0]) != 0) {
                $intLevelId = intval($arrIds[0]);
            } else {
                $intLevelId = 0;
            }

            if (isset($requestParams['lid'])) {
                $intLevelId = intval($requestParams['lid']);
            }

            $objLevels = new MediaDirectoryLevel(null, null, 1, $this->moduleName);
            $strLevelDropdown = $objLevels->listLevels($this->_objTpl, 3, $intLevelId);
            $strLevelName = $_ARRAYLANG['TXT_MEDIADIR_LEVEL'];
            $strInputfieldSearch = $this->moduleNameLC."InputfieldSearch";

            $strExpandedInputfields .= <<<EOF
<p><label>$strLevelName</label><select class="$strInputfieldSearch" name="lid"><option value="">$strPleaseChoose</option>$strLevelDropdown</select></p>
EOF;

            if (!empty($arrIds[1])) {
                $intCategoryCmd = $arrIds[1];
            } else {
                $intCategoryCmd = 0;
            }
        } else {
            if (intval($arrIds[0]) != 0) {
                $intCategoryCmd = $arrIds[0];
            } else {
                $intCategoryCmd = 0;
            }
        }

        if ($intCategoryCmd != 0) {
            $intCategoryId = intval($intCategoryCmd);
        } else {
            $intCategoryId = 0;
        }

        if (isset($requestParams['cid'])) {
            $intCategoryId = intval($requestParams['cid']);
        }

        if ($bolShowCategorySelector) {
            $objCategories = new MediaDirectoryCategory(null, null, 1, $this->moduleName);
            $strCategoryDropdown = $objCategories->listCategories($this->_objTpl, 3, $intCategoryId);
            $strCategoryName = $_ARRAYLANG['TXT_MEDIADIR_CATEGORY'];

            $strExpandedInputfields .= <<<EOF
<p><label>$strCategoryName</label><select class="mediadirInputfieldSearch" name="cid"><option value="">$strPleaseChoose</option>$strCategoryDropdown</select></p>
EOF;
        }

        $objInputfields = new MediaDirectoryInputfield($formId, true, null, $this->moduleName);
        $strExpandedInputfields .= $objInputfields->listInputfields(null, 4, null);

        return $strExpandedInputfields;
    }


    function searchEntries($arrData)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase, $objInit;

        $arrSelect = array();
        $arrWhere = array();
        $arrOrder = array();
        $arrJoins = array();
        $arrFoundIds = array();
        $arrFoundLevelsCategories = array();
        $arrFoundCountries = array();
        $intCmdFormId = null;
        $strTerm = '';

        //build search term query
        $arrData['term'] = trim($arrData['term']);

        if (isset($_GET['cmd']) && $_GET['cmd'] != 'search') {
            $objForms = new MediaDirectoryForm(null, $this->moduleName);
            foreach ($objForms->arrForms as $intFormId => $arrForm) {
                // note: in a previous version of Cloudrexx, there was no check
                // if the form was active or not. this caused unexpected
                // behavior
                if (
                    !$this->arrSettings['legacyBehavior'] &&
                    !$arrForm['formActive']
                ) {
                    continue;
                }
                if (!empty($arrForm['formCmd']) && ($arrForm['formCmd'] == $_GET['cmd'])) {
                    $intCmdFormId = intval($intFormId);
                }
            }

            //extract cid and lid from cmd
            if (empty($intCmdFormId)) {
                $arrLevelCategoryId = explode('-', $_GET['cmd']);
                if (count($arrLevelCategoryId) == 1) {
                    if (empty($this->arrSettings['settingsShowLevels']) && empty($arrData['cid'])) {
                        $arrData['cid'] = $arrLevelCategoryId[0];
                    } elseif (!empty($this->arrSettings['settingsShowLevels']) && empty($arrData['lid'])) {
                        $arrData['lid'] = $arrLevelCategoryId[0];
                    }
                } elseif (count($arrLevelCategoryId) == 2) {
                    if (empty($this->arrSettings['settingsShowLevels'])) {
                        $arrData['cid'] = empty($arrData['cid']) ? $arrLevelCategoryId[0] : $arrData['cid'];
                    } elseif (!empty($this->arrSettings['settingsShowLevels'])) {
                        $arrData['lid'] = empty($arrData['cid']) ? $arrLevelCategoryId[0] : $arrData['lid'];
                        $arrData['cid'] = empty($arrData['cid']) ? $arrLevelCategoryId[1] : $arrData['cid'];
                    }
                }
            }
        }

        $arrJoins[] = DBPREFIX.'module_'.$this->moduleTablePrefix.'_entries AS entry';
        $arrJoins[] = 'INNER JOIN '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_rel_entry_inputfields AS rel_inputfield ON rel_inputfield.`entry_id` = `entry`.`id`';

        //build level search query
        if (!empty($arrData['lid'])) {
            array_push($this->arrSearchLevels, intval($arrData['lid']));
            $this->getSearchLevelIds(intval($arrData['lid']));

            $arrWhere[] = 'rel_level.level_id IN ('.join(',', $this->arrSearchLevels).')';
            $arrJoins[] = 'INNER JOIN '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_rel_entry_levels AS rel_level ON rel_level.entry_id=entry.id';
        }

        //build category search query
        if (!empty($arrData['cid'])) {
            array_push($this->arrSearchCategories, intval($arrData['cid']));
            $this->getSearchCategoryIds(intval($arrData['cid']));

            $arrWhere[] = 'rel_category.category_id IN ('.join(',', $this->arrSearchCategories).')';
            $arrJoins[] = 'INNER JOIN '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_rel_entry_categories AS rel_category ON rel_category.entry_id=entry.id';
        }

        $arrSelect[]    = 'entry.id AS `entry_id`';

        if (!empty($arrData['term'])) {
            $strTerm        = contrexx_raw2db(trim($arrData['term']));
            $arrSelect[]    = 'MATCH (rel_inputfield.`value`) AGAINST ("%'.$strTerm.'%")  AS score';
            $arrJoins[]     = 'INNER JOIN '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_inputfields AS inputfield ON rel_inputfield.`field_id` = inputfield.`id`';
            $strReplace     = '%" AND rel_inputfield.`value` LIKE "%';
            $strReplace     = preg_replace('/\s+/', $strReplace, $strTerm);
            $arrWhere[]     = 'rel_inputfield.`value` LIKE "%'.$strReplace.'%"';
            $arrWhere[]     = 'inputfield.`type` NOT IN (7,8,15,16,21)';
            $arrOrder[]     = 'score DESC';
        }

        if ($this->arrSettings['settingsIndividualEntryOrder']) {
            $arrOrder[] = 'entry.`order` ASC';
        }
        $arrOrder[]     = 'rel_inputfield.`value` ASC';

        //search levels and categorie names
        if (empty($arrData['cid']) && $arrData['type'] == 'exp') {
            $arrFoundLevelsCategories = $this->searchLevelsCategories(1, $strTerm, $intCmdFormId);
        }
        $arrFoundIds = array_merge($arrFoundIds, $arrFoundLevelsCategories);

        //search countries
        $arrFoundCountries = $this->searchCountries($strTerm, $intCmdFormId);
        $arrFoundIds = array_merge($arrFoundIds, $arrFoundCountries);

        if ($intCmdFormId != 0) {
            $arrWhere[] = "rel_inputfield.`form_id` = '".$intCmdFormId."'";
        }

        if($objInit->mode == 'frontend') {
            $intToday = time();
            $arrWhere[] = "(`duration_type` = 1 OR (`duration_type` = 2 AND (`duration_start` < '$intToday' AND `duration_end` > '$intToday')))";
            $arrWhere[] = 'entry.`confirmed` = 1';
            $arrWhere[] = 'entry.`active` = 1';
        }

        if (empty($arrSelect) || empty($arrWhere) || empty($arrOrder)) {
            return;
        }

        $order = join(',', $arrOrder);

        if ($arrData['type'] == 'exp') {
            //build extended search query
            $objInputfields = null;
            $arrExternals = array('__cap', 'section', 'type', 'cmd', 'term', 'lid', 'cid', 'search', 'pos','scid','langId', 'csrf');
            foreach ($arrData as $intInputfieldId => $strExpTerm) {
                if (in_array($intInputfieldId, $arrExternals) || empty($strExpTerm)) {
                    continue;
                }

                if (!$objInputfields) {
                    $objInputfields = new MediaDirectoryInputfield(null, true, null, $this->moduleName);
                }

                if (!isset($objInputfields->arrInputfields[$intInputfieldId])) {
                    continue;
                }

                $intInputfieldType = $objInputfields->arrInputfields[$intInputfieldId]['type'];
                $inputfieldContextType = $objInputfields->arrInputfields[$intInputfieldId]['context_type'];
                $strExpTerm = is_array($strExpTerm) ? contrexx_input2db(array_map('trim', $strExpTerm)) : contrexx_input2db(trim($strExpTerm));
                $strTableName = 'rel_inputfield_'.intval($intInputfieldId);
                $arrJoins[]  = 'INNER JOIN '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_rel_entry_inputfields AS '.$strTableName.' ON '.$strTableName.'.`entry_id` = entry.id';

                switch ($inputfieldContextType) {
                    case 'zip':
                        $whereExp = $strTableName.'.`value` REGEXP "(^|[^a-z0-9])'.$strExpTerm.'([^a-z0-9]|$)"';
                        $arrWhere[] = '('.$strTableName.'.`field_id` = '.intval($intInputfieldId).' AND '.$whereExp.')';
                        continue;

                        break;

                    default:
                        break;
                }
                switch ($intInputfieldType) {
                    case '11': // 11 = classification
                        switch ($this->arrSettings['settingsClassificationSearch']) {
                            case 1:
                                $strSearchOperator = '>=';
                                break;
                            case 2:
                                $strSearchOperator = '<=';
                                break;
                            case 3:
                                $strSearchOperator = '=';
                                break;
                        }

                        $whereExp = $strTableName.'.`value` '.$strSearchOperator.' "'.$strExpTerm.'"';
                        break;

                    case '3': // 3 = dropdown
                    case '25': // 25 = country
                        $whereExp = $strTableName.'.`value` = "'.$strExpTerm.'"';
                        break;

                    case '5': // 5 = checkbox
                        $checkboxSearch = array();
                        foreach ($strExpTerm as $value) {
                            $checkboxSearch[] = ' FIND_IN_SET("'. $value .'",' . $strTableName . '.`value`) <> 0';
                        }
                        $whereExp = '('. implode(' AND ', $checkboxSearch) .')';
                        break;

                    case '31': // Range Slider
                        $intMin = (int)$strExpTerm[0];
                        $intMax = (int)$strExpTerm[1];
                        $whereExp = '('.$strTableName.'.`field_id` = '.intval($intInputfieldId).' AND '.$strTableName.'.`value` BETWEEN '.$intMin.' AND '.$intMax.')';
                    break;

                    default:
                        $whereExp = $strTableName.'.`value` LIKE "%'.$strExpTerm.'%"';
                        break;
                }
                $arrWhere[] = '('.$strTableName.'.`field_id` = '.intval($intInputfieldId).' AND '.$whereExp.')';
            }
        }

        $query = '
            SELECT
                '.join(",\n", $arrSelect).'
            FROM
                '.join("\n", $arrJoins).'
            WHERE
                '.join("\nAND ", $arrWhere).'
            GROUP BY
                entry.id
            ORDER BY
                '.$order.'
        ';

        $objRsSearchEntries = $objDatabase->Execute($query);
        if (!$objRsSearchEntries) {
            return;
        }

        while (!$objRsSearchEntries->EOF) {
            $this->arrFoundIds[] = $objRsSearchEntries->fields['entry_id'];
            $objRsSearchEntries->MoveNext();
        }
    }



    function getSearchLevelIds($intLevelId)
    {
        global $objDatabase;

        $objResultSearchLevels = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_levels WHERE parent_id='$intLevelId' ");

        if ($objResultSearchLevels) {
            while (!$objResultSearchLevels->EOF) {
                if (!empty($objResultSearchLevels->fields['id'])) {
                    array_push($this->arrSearchLevels, $objResultSearchLevels->fields['id']);
                }

                $this->getSearchLevelIds($objResultSearchLevels->fields['id']);
                $objResultSearchLevels->MoveNext();
            }
        }
    }



    function getSearchCategoryIds($intCatId)
    {
        global $objDatabase;

        $objResultSearchCategories = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_categories WHERE parent_id='$intCatId'");

        if ($objResultSearchCategories) {
            while (!$objResultSearchCategories->EOF) {
                if (!empty($objResultSearchCategories->fields['id'])) {
                    array_push($this->arrSearchCategories, $objResultSearchCategories->fields['id']);
                }

                $this->getSearchCategoryIds($objResultSearchCategories->fields['id']);
                $objResultSearchCategories->MoveNext();
            }
        }
    }

    //OSEC CUSTOMIZING (ev. �bernehmen und f�r levels ausbauen)
    function searchLevelsCategories($intType, $strTerm, $intCmdFormId)
    {
        global $objDatabase;

        $arrFoundIds = array();
        $strWhereForm = $intCmdFormId ? "AND ".DBPREFIX."module_".$this->moduleTablePrefix."_entries.form_id = '".$intCmdFormId."'" : '';

        $objResultSearchCategories = $objDatabase->Execute("
        SELECT
            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories.entry_id AS entry_id
        FROM
            ".DBPREFIX."module_".$this->moduleTablePrefix."_categories_names
        INNER JOIN
            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories
        ON
            ".DBPREFIX."module_".$this->moduleTablePrefix."_categories_names.category_id = ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories.category_id
        INNER JOIN
            ".DBPREFIX."module_".$this->moduleTablePrefix."_entries
        ON
            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories.entry_id = ".DBPREFIX."module_".$this->moduleTablePrefix."_entries.id
        WHERE
            ".DBPREFIX."module_".$this->moduleTablePrefix."_categories_names.category_name LIKE '%".$strTerm."%'
        AND
            ".DBPREFIX."module_".$this->moduleTablePrefix."_entries.active = '1'
            ".$strWhereForm."
        GROUP BY
            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories.entry_id"
        );

        if ($objResultSearchCategories) {
            while (!$objResultSearchCategories->EOF) {
                if (!empty($objResultSearchCategories->fields['entry_id'])) {
                    array_push($arrFoundIds, $objResultSearchCategories->fields['entry_id']);
                }

                $objResultSearchCategories->MoveNext();
            }
        }

        return $arrFoundIds;
    }

    //OSEC CUSTOMIZING (ev. �bernehmen)
    function searchCountries($strTerm, $intCmdFormId)
    {
        global $objDatabase;

        $arrFoundIds = array();
        $strWhereForm = $intCmdFormId ? "AND ".DBPREFIX."module_".$this->moduleTablePrefix."_entries.form_id = '".$intCmdFormId."'" : '';

        $objResultSearchCountry = $objDatabase->Execute("
        SELECT
            ".DBPREFIX."module_".$this->moduleTablePrefix."_entries.id AS entry_id
        FROM
            ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields
            INNER JOIN
                ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
            ON
                ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields.id = ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields.field_id
            INNER JOIN
                ".DBPREFIX."core_country
                        ON
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields.value = ".DBPREFIX."core_country.id
                        INNER JOIN
                        ".DBPREFIX."core_text
                        ON
                            ".DBPREFIX."core_text.id = ".DBPREFIX."core_country.id
                        AND
                            ".DBPREFIX."core_text.key = 'core_country_name'
                        INNER JOIN
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_entries
                        ON
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields.entry_id = ".DBPREFIX."module_".$this->moduleTablePrefix."_entries.id
                            WHERE
                        ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields.type = '25'
                            AND
                                ".DBPREFIX."core_text.text LIKE '%".$strTerm."%'
                            AND
                                ".DBPREFIX."module_".$this->moduleTablePrefix."_entries.active = '1'
                        ".$strWhereForm."
                            GROUP BY
                              ".DBPREFIX."module_".$this->moduleTablePrefix."_entries.id"
        );

        if ($objResultSearchCountry) {
            while (!$objResultSearchCountry->EOF) {
                if (!empty($objResultSearchCountry->fields['entry_id'])) {
                    array_push($arrFoundIds, $objResultSearchCountry->fields['entry_id']);
                }

                $objResultSearchCountry->MoveNext();
            }
        }

        return array_unique($arrFoundIds);
    }
}
