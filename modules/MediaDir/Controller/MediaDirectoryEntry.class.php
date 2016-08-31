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
 * Media  Directory Entry Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/**
 * Media Directory Entry Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryEntry extends MediaDirectoryInputfield
{
    private $intEntryId;
    private $intLevelId;
    private $intCatId;
    private $strSearchTerm;
    private $bolLatest;
    private $bolUnconfirmed;
    private $bolActive;
    private $intLimitStart;
    private $intLimitEnd;
    private $intUserId;
    private $bolPopular;
    private $intCmdFormId;
    private $bolReadyToConfirm;
    private $intLimit;
    private $intOffset;

    private $arrSubCategories = array();
    private $arrSubLevels = array();
    private $strBlockName;

    public $arrEntries = array();
    public $recordCount = 0;

    /**
     * Constructor
     */
    function __construct($name)
    {
        /*if($bolGetEnties == 1) {
            $this->arrEntries = self::getEntries();
        }*/
        parent::__construct(null, false, null, $name);
        parent::getSettings();
        parent::getFrontendLanguages();



    }

    function getEntries($intEntryId=null, $intLevelId=null, $intCatId=null, $strSearchTerm=null, $bolLatest=null, $bolUnconfirmed=null, $bolActive=null, $intLimitStart=null, $intLimitEnd='n', $intUserId=null, $bolPopular=null, $intCmdFormId=null, $bolReadyToConfirm=null, $intLimit=0, $intOffset=0)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase, $_LANGID, $objInit;
        $this->intEntryId = intval($intEntryId);
        $this->intLevelId = intval($intLevelId);
        $this->intCatId = intval($intCatId);
        $this->bolLatest = intval($bolLatest);
        $this->bolUnconfirmed = intval($bolUnconfirmed);
        $this->bolActive = intval($bolActive);
        $this->strBlockName = null;
        $this->intLimitStart = intval($intLimitStart);
        $this->intLimitEnd = $intLimitEnd;
        $this->intUserId = intval($intUserId);
        $this->bolPopular = intval($bolPopular);
        $this->intCmdFormId = intval($intCmdFormId);
        $this->bolReadyToConfirm = intval($bolReadyToConfirm);
        $this->intLimit = intval($intLimit);
        $this->intOffset = intval($intOffset);

        $strWhereEntryId = '';
        $strWhereLevel = '';
        $strFromLevel = '';
        $strWhereActive = '';
        $strWhereTerm = '';
        $strWhereLangId = '';
        $strWhereFormId = '';
        $strFromCategory = '';
        $strWhereCategory = '';
        $strOrder = "rel_inputfield.`value` ASC";

        if(($strSearchTerm != $_ARRAYLANG['TXT_MEDIADIR_ID_OR_SEARCH_TERM']) && !empty($strSearchTerm)) {
            $this->strSearchTerm = contrexx_addslashes($strSearchTerm);
        } else {
            $this->strSearchTerm = null;
        }

        if($this->intCmdFormId != 0) {
            $strWhereFormId = "AND (entry.`form_id` = ".$this->intCmdFormId.") ";
        }

        if(!empty($this->intEntryId)) {
            $strWhereEntryId = "AND (entry.`id` = ".$this->intEntryId.") ";
        }


        if(!empty($this->intUserId)) {
            $strWhereEntryId = "AND (entry.`added_by` = ".$this->intUserId.") ";
        }

        if(!empty($this->intLevelId)) {
            $strWhereLevel = "AND ((level.`level_id` = ".$this->intLevelId.") AND (level.`entry_id` = entry.`id`)) ";
            $strFromLevel = " ,".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_levels AS level";
        }

        if(!empty($this->intCatId)) {
            $strWhereCategory = "AND ((category.`category_id` = ".$this->intCatId.") AND (category.`entry_id` = entry.`id`)) ";
            $strFromCategory = " ,".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories AS category";
        }

        if(!empty($this->bolLatest)) {
            $strOrder = "entry.`validate_date` DESC";
            $this->strBlockName = $this->moduleNameLC."LatestList";
        }

        if(!empty($this->bolPopular)) {
            $strOrder = "entry.`popular_hits` DESC";
        }

        if(empty($this->bolLatest) && empty($this->bolPopular) && $this->arrSettings['settingsIndividualEntryOrder'] == 1) {
            $strOrder = "entry.`order` ASC, rel_inputfield.`value` ASC";
        }

        if(!empty($this->bolUnconfirmed)) {
            $strWhereUnconfirmed = "AND (entry.`confirmed` = 0) ";
            $this->strBlockName = $this->moduleNameLC."ConfirmList";

            if(!empty($this->bolReadyToConfirm)) {
                $strWhereReadyToConfirm = "AND (entry.`ready_to_confirm` = '1' AND entry.`confirmed` = 0) ";
            } else {
                $strWhereReadyToConfirm = '';
            }
        } else {
            if(!empty($this->bolReadyToConfirm)) {
                $strWhereReadyToConfirm = "AND ((entry.`ready_to_confirm` = '0' AND entry.`confirmed` = 0) OR (entry.`confirmed` = 1)) ";
                $strWhereUnconfirmed = "";
            } else {
                $strWhereUnconfirmed = "AND (entry.`confirmed` = 1) ";
                $strWhereReadyToConfirm = "";
            }
        }

        if(!empty($this->bolActive)) {
            $strWhereActive = "AND (entry.`active` = 1) ";
        }

        if(empty($this->intLimitStart) && $this->intLimitStart == 0) {
            $strSelectLimit = "LIMIT ".$this->intLimitEnd;
        } else {
            $strSelectLimit = "LIMIT ".$this->intLimitStart.",".$this->intLimitEnd;
        }

        if($this->intLimitEnd === 'n') {
            $strSelectLimit = '';
        }

        if(empty($this->strSearchTerm)) {
            $strWhereFirstInputfield = "AND (rel_inputfield.`form_id` = entry.`form_id`) AND (rel_inputfield.`field_id` = (".$this->getQueryToFindFirstInputFieldId().")) AND (rel_inputfield.`lang_id` = '".$_LANGID."')";
        } else {
            $strWhereTerm = "AND ((rel_inputfield.`value` LIKE '%".$this->strSearchTerm."%') OR (entry.`id` = '".$this->strSearchTerm."')) ";
            $strWhereFirstInputfield = '';
            $this->strBlockName = "";
        }

        if(empty($this->strBlockName)) {
            $this->strBlockName = $this->moduleNameLC."EntryList";
        }

        if($objInit->mode == 'frontend') {
            if(intval($this->arrSettings['settingsShowEntriesInAllLang']) == 0) {
                $strWhereLangId = "AND (entry.`lang_id` = ".$_LANGID.") ";
            }
        }

        $strLimit  = '';
        $strOffset = '';
        if ($this->intLimit > 0) {
            $strLimit = 'LIMIT ' . $this->intLimit;
        }
        if ($this->intOffset > 0) {
            $strOffset = 'OFFSET ' . $this->intOffset;
        }

        if($objInit->mode == 'frontend') {
            $strWhereDuration = "AND (`duration_type` = 1 OR (`duration_type` = 2 AND (`duration_start` < '" . time() . "' AND `duration_end` > '" . time() . "'))) ";
        } else {
            $strWhereDuration = null;
        }

        $query = "
            SELECT SQL_CALC_FOUND_ROWS
                entry.`id` AS `id`,
                entry.`order` AS `order`,
                entry.`form_id` AS `form_id`,
                entry.`create_date` AS `create_date`,
                entry.`update_date` AS `update_date`,
                entry.`validate_date` AS `validate_date`,
                entry.`added_by` AS `added_by`,
                entry.`updated_by` AS `updated_by`,
                entry.`lang_id` AS `lang_id`,
                entry.`hits` AS `hits`,
                entry.`popular_hits` AS `popular_hits`,
                entry.`popular_date` AS `popular_date`,
                entry.`last_ip` AS `last_ip`,
                entry.`confirmed` AS `confirmed`,
                entry.`active` AS `active`,
                entry.`duration_type` AS `duration_type`,
                entry.`duration_start` AS `duration_start`,
                entry.`duration_end` AS `duration_end`,
                entry.`duration_notification` AS `duration_notification`,
                entry.`translation_status` AS `translation_status`,
                entry.`ready_to_confirm` AS `ready_to_confirm`,
                rel_inputfield.`value` AS `value`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_entries AS entry,
                ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields AS rel_inputfield
                ".$strFromCategory."
                ".$strFromLevel."
            WHERE
                (rel_inputfield.`entry_id` = entry.`id`)
                ".$strWhereFirstInputfield."
                ".$strWhereTerm."
                ".$strWhereUnconfirmed."
                ".$strWhereCategory."
                ".$strWhereLevel."
                ".$strWhereEntryId."
                ".$strWhereActive."
                ".$strWhereLangId."
                ".$strWhereFormId."
                ".$strWhereReadyToConfirm."
                ".$strWhereDuration."
            GROUP BY
                entry.`id`
            ORDER BY
                ".$strOrder."
                ".$strSelectLimit."
            ".$strLimit."
            ".$strOffset."
        ";
        $objEntries = $objDatabase->Execute($query);

        $totalRecords =$objDatabase->Execute("SELECT FOUND_ROWS() AS found_rows");

        $arrEntries = array();

        if ($objEntries !== false) {
            while (!$objEntries->EOF) {
                $arrEntry = array();
                $arrEntryFields = array();

                if(array_key_exists($objEntries->fields['id'], $arrEntries)) {
                    $arrEntries[intval($objEntries->fields['id'])]['entryFields'][] = !empty($objEntries->fields['value']) ? $objEntries->fields['value'] : '-';
                } else {
                    $arrEntryFields[] = !empty($objEntries->fields['value']) ? $objEntries->fields['value'] : '-';

                    $arrEntry['entryId'] = intval($objEntries->fields['id']);
                    $arrEntry['entryOrder'] = intval($objEntries->fields['order']);
                    $arrEntry['entryFormId'] = intval($objEntries->fields['form_id']);
                    $arrEntry['entryFields'] = $arrEntryFields;
                    $arrEntry['entryCreateDate'] = intval($objEntries->fields['create_date']);
                    $arrEntry['entryValdateDate'] = intval($objEntries->fields['validate_date']);
                    $arrEntry['entryAddedBy'] = intval($objEntries->fields['added_by']);
                    $arrEntry['entryHits'] = intval($objEntries->fields['hits']);
                    $arrEntry['entryPopularHits'] = intval($objEntries->fields['popular_hits']);
                    $arrEntry['entryPopularDate'] = intval($objEntries->fields['popular_date']);
                    $arrEntry['entryLastIp'] = htmlspecialchars($objEntries->fields['last_ip'], ENT_QUOTES, CONTREXX_CHARSET);
                    $arrEntry['entryConfirmed'] = intval($objEntries->fields['confirmed']);
                    $arrEntry['entryActive'] = intval($objEntries->fields['active']);
                    $arrEntry['entryDurationType'] = intval($objEntries->fields['duration_type']);
                    $arrEntry['entryDurationStart'] = intval($objEntries->fields['duration_start']);
                    $arrEntry['entryDurationEnd'] = intval($objEntries->fields['duration_end']);
                    $arrEntry['entryDurationNotification'] = intval($objEntries->fields['duration_notification']);
                    $arrEntry['entryTranslationStatus'] = explode(",",$objEntries->fields['translation_status']);
                    $arrEntry['entryReadyToConfirm'] = intval($objEntries->fields['ready_to_confirm']);

                    $this->arrEntries[$objEntries->fields['id']] = $arrEntry;
                }

                $objEntries->MoveNext();
            }
            $this->recordCount = $totalRecords->fields['found_rows'];
        }
    }

    /**
     * Setter for $this->strBlockName
     *
     * @param string $blockName html parse block name
     */
    function setStrBlockName($blockName)
    {
        $this->strBlockName = $blockName;
    }

    /**
     * Getter for $this->strBlockName
     *
     * @return string current parse block name to list the entries
     */
    function getStrBlockName()
    {
        return $this->strBlockName;
    }

    function listEntries($objTpl, $intView)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        $objFWUser = \FWUser::getFWUserObject();
        $intToday = time();

        $i = 0;
        switch ($intView) {
            case 1:
                //Backend View
                if(!empty($this->arrEntries)){
                    foreach ($this->arrEntries as $key => $arrEntry) {
                        if(intval($arrEntry['entryAddedBy']) != 0) {
                            if ($objUser = $objFWUser->objUser->getUser(intval($arrEntry['entryAddedBy']))) {
                                $strAddedBy = $objUser->getUsername();
                            } else {
                                $strAddedBy = "unknown";
                            }
                        } else {
                            $strAddedBy = "unknown";
                        }

                        if($arrEntry['entryActive'] == 1) {
                            $strStatus = '../core/Core/View/Media/icons/status_green.gif';
                            $intStatus = 0;

                            if(($arrEntry['entryDurationStart'] > $intToday || $arrEntry['entryDurationEnd'] < $intToday) && $arrEntry['entryDurationType'] == 2) {
                                $strStatus = '../core/Core/View/Media/icons/status_yellow.gif';
                            }
                        } else {
                            $strStatus = '../core/Core/View/Media/icons/status_red.gif';
                            $intStatus = 1;
                        }

                        $objForm = new MediaDirectoryForm($arrEntry['entryFormId'], $this->moduleName);

                        //get votes
                        if($this->arrSettings['settingsAllowVotes']) {
                            $objVoting = new MediaDirectoryVoting($this->moduleName);
                            $objVoting->getVotes($objTpl, $arrEntry['entryId']);
                            if ($objTpl->blockExists('mediadirEntryVotes')) {
                                $objTpl->parse('mediadirEntryVotes');
                            }
                        } else {
                            if ($objTpl->blockExists('mediadirEntryVotes')) {
                                $objTpl->hideBlock('mediadirEntryVotes');
                            }
                        }

                        //get comments
                        if($this->arrSettings['settingsAllowComments']) {
                            $objComment = new MediaDirectoryComment($this->moduleName);
                            $objComment->getComments($objTpl, $arrEntry['entryId']);
                            if ($objTpl->blockExists('mediadirEntryComments')) {
                                $objTpl->parse('mediadirEntryComments');
                            }
                        } else {
                            if ($objTpl->blockExists('mediadirEntryComments')) {
                                $objTpl->hideBlock('mediadirEntryComments');
                            }
                        }

                        $objTpl->setVariable(array(
                            $this->moduleLangVar.'_ROW_CLASS' =>  $i%2==0 ? 'row1' : 'row2',
                            $this->moduleLangVar.'_ENTRY_ID' =>  $arrEntry['entryId'],
                            $this->moduleLangVar.'_ENTRY_STATUS' => $strStatus,
                            $this->moduleLangVar.'_ENTRY_SWITCH_STATUS' => $intStatus,
                            $this->moduleLangVar.'_ENTRY_VALIDATE_DATE' =>  date("H:i:s - d.m.Y",$arrEntry['entryValdateDate']),
                            $this->moduleLangVar.'_ENTRY_CREATE_DATE' =>  date("H:i:s - d.m.Y",$arrEntry['entryCreateDate']),
                            $this->moduleLangVar.'_ENTRY_AUTHOR' =>  htmlspecialchars($strAddedBy, ENT_QUOTES, CONTREXX_CHARSET),
                            $this->moduleLangVar.'_ENTRY_HITS' =>  $arrEntry['entryHits'],
                            $this->moduleLangVar.'_ENTRY_FORM' => $objForm->arrForms[$arrEntry['entryFormId']]['formName'][0],
                        ));

                        foreach ($arrEntry['entryFields'] as $key => $strFieldValue) {
                            $intPos = $key+1;

                            $objTpl->setVariable(array(
                                                       $this->moduleLangVar.'_ENTRY_FIELD_'.$intPos.'_POS' => contrexx_raw2xhtml(substr($strFieldValue, 0, 255)),
                            ));
                        }

                        //get order
                        if($this->arrSettings['settingsIndividualEntryOrder'] == 1) {
                            $objTpl->setVariable(array(
                                $this->moduleLangVar.'_ENTRY_ORDER' => '<input name="entriesOrder['.$arrEntry['entryId'].']" style="width: 30px; margin-right: 5px;" value="'.$arrEntry['entryOrder'].'" onfocus="this.select();" type="text">',
                            ));

                            if(intval($objTpl->blockExists($this->moduleNameLC.'EntriesSaveOrder')) != 0) {
                                $objTpl->touchBlock($this->moduleNameLC.'EntriesSaveOrder');
                            }
                        } else {
                            if(intval($objTpl->blockExists($this->moduleNameLC.'EntriesSaveOrder')) != 0) {
                                $objTpl->hideBlock($this->moduleNameLC.'EntriesSaveOrder');
                            }
                        }

                        $i++;
                        $objTpl->parse($this->strBlockName);
                        $objTpl->hideBlock('noEntriesFound');
                        $objTpl->clearVariables();
                    }
                } else {
                    $objTpl->setGlobalVariable(array(
                        'TXT_'.$this->moduleLangVar.'_NO_ENTRIES_FOUND' => $_ARRAYLANG['TXT_MEDIADIR_NO_ENTRIES_FOUND'],
                    ));

                    $objTpl->touchBlock('noEntriesFound');
                    $objTpl->clearVariables();
                }
                break;
            case 2:
                //Frontend View
                if(!empty($this->arrEntries)) {
                    foreach ($this->arrEntries as $key => $arrEntry) {
                        if(($arrEntry['entryDurationStart'] < $intToday && $arrEntry['entryDurationEnd'] > $intToday) || $arrEntry['entryDurationType'] == 1) {
                            $objInputfields = new MediaDirectoryInputfield(intval($arrEntry['entryFormId']),false,$arrEntry['entryTranslationStatus'], $this->moduleName);
                            $objInputfields->listInputfields($objTpl, 3, intval($arrEntry['entryId']));

                            if(intval($arrEntry['entryAddedBy']) != 0) {
                                if ($objUser = $objFWUser->objUser->getUser(intval($arrEntry['entryAddedBy']))) {
                                    $strAddedBy = $objUser->getUsername();
                                } else {
                                    $strAddedBy = "unknown";
                                }
                            } else {
                                $strAddedBy = "unknown";
                            }

                            $strCategoryLink = $this->intCatId != 0 ? '&amp;cid='.$this->intCatId : null;
                            $strLevelLink = $this->intLevelId != 0 ? '&amp;lid='.$this->intLevelId : null;

                            if($this->checkPageCmd('detail'.intval($arrEntry['entryFormId']))) {
                                $strDetailCmd = 'detail'.intval($arrEntry['entryFormId']);
                            } else {
                                $strDetailCmd = 'detail';
                            }

                            if($arrEntry['entryReadyToConfirm'] == 1 || $arrEntry['entryConfirmed'] == 1) {
                                $strDetailUrl = 'index.php?section='.$this->moduleName.'&amp;cmd='.$strDetailCmd.$strLevelLink.$strCategoryLink.'&amp;eid='.$arrEntry['entryId'];
                            } else {
                                $strDetailUrl = '#';
                            }

                            $objForm = new MediaDirectoryForm($arrEntry['entryFormId'], $this->moduleName);

                            $objTpl->setVariable(array(
                                    $this->moduleLangVar.'_ROW_CLASS' =>  $i%2==0 ? 'row1' : 'row2',
                                $this->moduleLangVar.'_ENTRY_ID' =>  $arrEntry['entryId'],
                                $this->moduleLangVar.'_ENTRY_VALIDATE_DATE' =>  date("H:i:s - d.m.Y",$arrEntry['entryValdateDate']),
                                $this->moduleLangVar.'_ENTRY_CREATE_DATE' =>  date("H:i:s - d.m.Y",$arrEntry['entryCreateDate']),
                                $this->moduleLangVar.'_ENTRY_AUTHOR' =>  htmlspecialchars($strAddedBy, ENT_QUOTES, CONTREXX_CHARSET),
                                $this->moduleLangVar.'_ENTRY_CATEGORIES' =>  $this->getCategoriesLevels(1, $arrEntry['entryId'], $objForm->arrForms[$arrEntry['entryFormId']]['formCmd']),
                                    $this->moduleLangVar.'_ENTRY_LEVELS' =>  $this->getCategoriesLevels(2, $arrEntry['entryId'], $objForm->arrForms[$arrEntry['entryFormId']]['formCmd']),
                                    $this->moduleLangVar.'_ENTRY_HITS' =>  $arrEntry['entryHits'],
                                $this->moduleLangVar.'_ENTRY_POPULAR_HITS' =>  $arrEntry['entryPopularHits'],
                                $this->moduleLangVar.'_ENTRY_DETAIL_URL' => $strDetailUrl,
                                $this->moduleLangVar.'_ENTRY_EDIT_URL' =>  'index.php?section='.$this->moduleName.'&amp;cmd=edit&amp;eid='.$arrEntry['entryId'],
                                $this->moduleLangVar.'_ENTRY_DELETE_URL' =>  'index.php?section='.$this->moduleName.'&amp;cmd=delete&amp;eid='.$arrEntry['entryId'],
                                'TXT_'.$this->moduleLangVar.'_ENTRY_DELETE' =>  $_ARRAYLANG['TXT_MEDIADIR_DELETE'],
                                'TXT_'.$this->moduleLangVar.'_ENTRY_EDIT' =>  $_ARRAYLANG['TXT_MEDIADIR_EDIT'],
                                'TXT_'.$this->moduleLangVar.'_ENTRY_DETAIL' =>  $_ARRAYLANG['TXT_MEDIADIR_DETAIL'],
                                'TXT_'.$this->moduleLangVar.'_ENTRY_CATEGORIES' =>  $_ARRAYLANG['TXT_MEDIADIR_CATEGORIES'],
                                'TXT_'.$this->moduleLangVar.'_ENTRY_LEVELS' =>  $_ARRAYLANG['TXT_MEDIADIR_LEVELS'],
                            ));

                            $this->parseCategoryLevels(1, $arrEntry['entryId'], $objTpl);
                            $this->parseCategoryLevels(2, $arrEntry['entryId'], $objTpl);

                            foreach ($arrEntry['entryFields'] as $key => $strFieldValue) {
                                $intPos = $key+1;

                                $objTpl->setVariable(array(
                                    'MEDIADIR_ENTRY_FIELD_'.$intPos.'_POS' => substr($strFieldValue, 0, 255),
                                ));
                                }

                            if($this->arrSettings['settingsAllowVotes']) {
                                $objVoting = new MediaDirectoryVoting($this->moduleName);

                                if(intval($objTpl->blockExists($this->moduleNameLC.'EntryVoteForm')) != 0) {
                                    $objVoting->getVoteForm($objTpl, $arrEntry['entryId']);
                                }
                                if(intval($objTpl->blockExists($this->moduleNameLC.'EntryVotes')) != 0) {
                                    $objVoting->getVotes($objTpl, $arrEntry['entryId']);
                                }
                            }

                            if($this->arrSettings['settingsAllowComments']) {
                                $objComment = new MediaDirectoryComment($this->moduleName);

                                if(intval($objTpl->blockExists($this->moduleNameLC.'EntryComments')) != 0) {
                                    $objComment->getComments($objTpl, $arrEntry['entryId']);
                                }

                                if(intval($objTpl->blockExists($this->moduleNameLC.'EntryCommentForm')) != 0) {
                                    $objComment->getCommentForm($objTpl, $arrEntry['entryId']);
                                }
                            }

                            if(!$this->arrSettings['settingsAllowEditEntries'] && intval($objTpl->blockExists($this->moduleNameLC.'EntryEditLink')) != 0) {
                                $objTpl->hideBlock($this->moduleNameLC.'EntryEditLink');
                            }

                            if(!$this->arrSettings['settingsAllowDelEntries'] && intval($objTpl->blockExists($this->moduleNameLC.'EntryDeleteLink')) != 0) {
                                $objTpl->hideBlock($this->moduleNameLC.'EntryDeleteLink');
                            }

                            $i++;
                                $objTpl->parse($this->strBlockName);

                            $objTpl->clearVariables();
                        }
                    }
                } else {
                    $objTpl->setVariable(array(
                        'TXT_'.$this->moduleLangVar.'_SEARCH_MESSAGE' => $_ARRAYLANG['TXT_MEDIADIR_NO_ENTRIES_FOUND'],
                    ));

                    $objTpl->parse($this->moduleNameLC.'NoEntriesFound');
                    $objTpl->clearVariables();
                }
                break;
            case 3:
                //Alphabetical View
                if(!empty($this->arrEntries)) {
                    $arrAlphaIndexes = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0-9','#');
                    $arrAlphaGroups = array();

                    foreach ($this->arrEntries as $key => $arrEntry) {
                        $strTitle = $arrEntry['entryFields'][0];
                        $strAlphaIndex = strtoupper(substr($strTitle, 0, 1));

                        if(!in_array($strAlphaIndex, $arrAlphaIndexes)){
                            if(is_numeric($strAlphaIndex)) {
                                $strAlphaIndex = '0-9';
                            } else {
                                $strAlphaIndex = '#';
                            }
                        }

                        $arrAlphaGroups[$strAlphaIndex][] = $arrEntry;
                    }

                    if(intval($objTpl->blockExists($this->moduleNameLC.'AlphaIndex')) != 0) {
                        $objTpl->touchBlock($this->moduleNameLC.'AlphaIndex');

                        foreach ($arrAlphaIndexes as $key => $strIndex) {
                            if(array_key_exists($strIndex, $arrAlphaGroups)) {
                                $strAlphaIndex = '<a href="#'.$strIndex.'">'.$strIndex.'</a>';
                            } else {
                                $strAlphaIndex = ''.$strIndex.'';
                            }

                            $objTpl->setVariable(array(
                                $this->moduleLangVar.'_ALPHA_INDEX_LINK' => $strAlphaIndex
                            ));

                            $objTpl->parse($this->moduleNameLC.'AlphaIndexElement');
                        }
                    }



                    foreach ($arrAlphaGroups as $strAlphaIndex => $arrEntries) {
                        if(intval($objTpl->blockExists($this->moduleNameLC.'AlphabeticalTitle')) != 0) {
                            $objTpl->setVariable(array(
                                $this->moduleLangVar.'_ALPHABETICAL_ANCHOR' => $strAlphaIndex,
                                'TXT_'.$this->moduleLangVar.'_ALPHABETICAL_TITLE' => $strAlphaIndex
                            ));

                            $objTpl->parse($this->moduleNameLC.'AlphabeticalTitle');
                        }

                        foreach ($arrEntries as $key => $arrEntry) {
                            if(($arrEntry['entryDurationStart'] < $intToday && $arrEntry['entryDurationEnd'] > $intToday) || $arrEntry['entryDurationType'] == 1) {
                                $objInputfields = new MediaDirectoryInputfield(intval($arrEntry['entryFormId']),false,$arrEntry['entryTranslationStatus'], $this->moduleName);
                                $objInputfields->listInputfields($objTpl, 3, intval($arrEntry['entryId']));
                                $strStatus = ($arrEntry['entryActive'] == 1) ? 'active' : 'inactive';

                                if(intval($arrEntry['entryAddedBy']) != 0) {
                                    if ($objUser = $objFWUser->objUser->getUser(intval($arrEntry['entryAddedBy']))) {
                                        $strAddedBy = $objUser->getUsername();
                                    } else {
                                        $strAddedBy = "unknown";
                                    }
                                } else {
                                    $strAddedBy = "unknown";
                                }

                                $strCategoryLink = $this->intCatId != 0 ? '&amp;cid='.$this->intCatId : null;
                                $strLevelLink = $this->intLevelId != 0 ? '&amp;lid='.$this->intLevelId : null;

                                if($this->checkPageCmd('detail'.intval($arrEntry['entryFormId']))) {
                                    $strDetailCmd = 'detail'.intval($arrEntry['entryFormId']);
                                } else {
                                    $strDetailCmd = 'detail';
                                }

                                if($arrEntry['entryReadyToConfirm'] == 1 || $arrEntry['entryConfirmed'] == 1) {
                                    $strDetailUrl = 'index.php?section='.$this->moduleName.'&amp;cmd='.$strDetailCmd.$strLevelLink.$strCategoryLink.'&amp;eid='.$arrEntry['entryId'];
                                } else {
                                    $strDetailUrl = '#';
                                }

                                $objForm = new MediaDirectoryForm($arrEntry['entryFormId'], $this->moduleName);

                                $objTpl->setVariable(array(
                                    $this->moduleLangVar.'_ROW_CLASS' =>  $i%2==0 ? 'row1' : 'row2',
                                    $this->moduleLangVar.'_ENTRY_ID' =>  $arrEntry['entryId'],
                                    $this->moduleLangVar.'_ENTRY_STATUS' => $strStatus,
                                    $this->moduleLangVar.'_ENTRY_VALIDATE_DATE' =>  date("H:i:s - d.m.Y",$arrEntry['entryValdateDate']),
                                    $this->moduleLangVar.'_ENTRY_CREATE_DATE' =>  date("H:i:s - d.m.Y",$arrEntry['entryCreateDate']),
                                    $this->moduleLangVar.'_ENTRY_AUTHOR' =>  htmlspecialchars($strAddedBy, ENT_QUOTES, CONTREXX_CHARSET),
                                    $this->moduleLangVar.'_ENTRY_CATEGORIES' =>  $this->getCategoriesLevels(1, $arrEntry['entryId'], $objForm->arrForms[$arrEntry['entryFormId']]['formCmd']),
                                    $this->moduleLangVar.'_ENTRY_LEVELS' =>  $this->getCategoriesLevels(2, $arrEntry['entryId'], $objForm->arrForms[$arrEntry['entryFormId']]['formCmd']),
                                    $this->moduleLangVar.'_ENTRY_HITS' =>  $arrEntry['entryHits'],
                                    $this->moduleLangVar.'_ENTRY_POPULAR_HITS' =>  $arrEntry['entryPopularHits'],
                                    $this->moduleLangVar.'_ENTRY_DETAIL_URL' => $strDetailUrl,
                                    $this->moduleLangVar.'_ENTRY_EDIT_URL' =>  'index.php?section='.$this->moduleName.'&amp;cmd=edit&amp;eid='.$arrEntry['entryId'],
                                    $this->moduleLangVar.'_ENTRY_DELETE_URL' =>  'index.php?section='.$this->moduleName.'&amp;cmd=delete&amp;eid='.$arrEntry['entryId'],
                                    'TXT_'.$this->moduleLangVar.'_ENTRY_DELETE' =>  $_ARRAYLANG['TXT_MEDIADIR_DELETE'],
                                    'TXT_'.$this->moduleLangVar.'_ENTRY_EDIT' =>  $_ARRAYLANG['TXT_MEDIADIR_EDIT'],
                                    'TXT_'.$this->moduleLangVar.'_ENTRY_DETAIL' =>  $_ARRAYLANG['TXT_MEDIADIR_DETAIL'],
                                    'TXT_'.$this->moduleLangVar.'_ENTRY_CATEGORIES' =>  $_ARRAYLANG['TXT_MEDIADIR_CATEGORIES'],
                                    'TXT_'.$this->moduleLangVar.'_ENTRY_LEVELS' =>  $_ARRAYLANG['TXT_MEDIADIR_LEVELS'],
                                ));

                                $this->parseCategoryLevels(1, $arrEntry['entryId'], $objTpl);
                                $this->parseCategoryLevels(2, $arrEntry['entryId'], $objTpl);

                                foreach ($arrEntry['entryFields'] as $key => $strFieldValue) {
                                    $intPos = $key+1;

                                    $objTpl->setVariable(array(
                                                               'MEDIADIR_ENTRY_FIELD_'.$intPos.'_POS' => contrexx_raw2xhtml(substr($strFieldValue, 0, 255)),
                                    ));
                                }

                                if($this->arrSettings['settingsAllowVotes']) {
                                    $objVoting = new MediaDirectoryVoting($this->moduleName);

                                    if(intval($objTpl->blockExists($this->moduleNameLC.'EntryVoteForm')) != 0) {
                                        $objVoting->getVoteForm($objTpl, $arrEntry['entryId']);
                                    }
                                    if(intval($objTpl->blockExists($this->moduleNameLC.'EntryVotes')) != 0) {
                                        $objVoting->getVotes($objTpl, $arrEntry['entryId']);
                                    }
                                }

                                if($this->arrSettings['settingsAllowComments']) {
                                    $objComment = new MediaDirectoryComment($this->moduleName);

                                    if(intval($objTpl->blockExists($this->moduleNameLC.'EntryComments')) != 0) {
                                        $objComment->getComments($objTpl, $arrEntry['entryId']);
                                    }

                                    if(intval($objTpl->blockExists($this->moduleNameLC.'EntryCommentForm')) != 0) {
                                        $objComment->getCommentForm($objTpl, $arrEntry['entryId']);
                                    }
                                }

                                if(!$this->arrSettings['settingsAllowEditEntries'] && intval($objTpl->blockExists($this->moduleNameLC.'EntryEditLink')) != 0) {
                                    $objTpl->hideBlock($this->moduleNameLC.'EntryEditLink');
                                }

                                if(!$this->arrSettings['settingsAllowDelEntries'] && intval($objTpl->blockExists($this->moduleNameLC.'EntryDeleteLink')) != 0) {
                                    $objTpl->hideBlock($this->moduleNameLC.'EntryDeleteLink');
                                }

                                $i++;
                                $objTpl->parse($this->moduleNameLC.'EntryList');
                                $objTpl->clearVariables();
                            }
                        }
                    }
                } else {
                    $objTpl->setVariable(array(
                        'TXT_'.$this->moduleLangVar.'_SEARCH_MESSAGE' => $_ARRAYLANG['TXT_MEDIADIR_NO_ENTRIES_FOUND'],
                    ));

                    $objTpl->parse($this->moduleNameLC.'NoEntriesFound');
                    $objTpl->clearVariables();
                }
            case 4:
                //Google Map
                $objGoogleMap = new \googleMap();
                $objGoogleMap->setMapId($this->moduleNameLC.'GoogleMap');
                $objGoogleMap->setMapStyleClass('mapLarge');
                $objGoogleMap->setMapType($this->arrSettings['settingsGoogleMapType']);

                $arrValues = explode(',', $this->arrSettings['settingsGoogleMapStartposition']);
                $objGoogleMap->setMapZoom($arrValues[2]);
                $objGoogleMap->setMapCenter($arrValues[1], $arrValues[0]);

                foreach ($this->arrEntries as $key => $arrEntry) {
                    if(($arrEntry['entryDurationStart'] < $intToday && $arrEntry['entryDurationEnd'] > $intToday) || $arrEntry['entryDurationType'] == 1) {
                        $arrValues = array();

                        if($this->checkPageCmd('detail'.intval($arrEntry['entryFormId']))) {
                            $strDetailCmd = 'detail'.intval($arrEntry['entryFormId']);
                        } else {
                            $strDetailCmd = 'detail';
                        }

                        $strEntryLink = '<a href="index.php?section='.$this->moduleName.'&amp;cmd='.$strDetailCmd.'&amp;eid='.$arrEntry['entryId'].'">'.$_ARRAYLANG['TXT_MEDIADIR_DETAIL'].'</a>';
                        $strEntryTitle = '<b>'.contrexx_raw2xhtml($arrEntry['entryFields']['0']).'</b>';
                        $intEntryId = intval($arrEntry['entryId']);
                        $intEntryFormId = intval($arrEntry['entryFormId']);

                        $query = "
                            SELECT
                                inputfield.`id` AS `id`,
                                rel_inputfield.`value` AS `value`
                            FROM
                                ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields AS inputfield,
                                ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields AS rel_inputfield
                            WHERE
                                inputfield.`form` = '".$intEntryFormId."'
                            AND
                                inputfield.`type`= '15'
                            AND
                                rel_inputfield.`field_id` = inputfield.`id`
                            AND
                                rel_inputfield.`entry_id` = '".$intEntryId."'
                            LIMIT 1
                        ";

                        $objRSMapKoordinates = $objDatabase->Execute($query);

                        if($objRSMapKoordinates !== false) {
                            $arrValues = explode(',', $objRSMapKoordinates->fields['value']);
                        }

                        $strValueLon = empty($arrValues[1]) ? 0 : $arrValues[1];
                            $strValueLat = empty($arrValues[0]) ? 0 : $arrValues[0];

                            $mapIndex      = $objGoogleMap->getMapIndex();
                            $clickFunction = "if (infowindow_$mapIndex) { infowindow_$mapIndex.close(); }
                                infowindow_$mapIndex.setContent(info$intEntryId);
                                infowindow_$mapIndex.open(map_$mapIndex, marker$intEntryId)";
                        $objGoogleMap->addMapMarker($intEntryId, $strValueLon, $strValueLat, $strEntryTitle."<br />".$strEntryLink, true, $clickFunction);
                    }
                }

                $objTpl->setVariable(array(
                    $this->moduleLangVar.'_GOOGLE_MAP' => $objGoogleMap->getMap()
                ));

                break;
        }
    }



    function checkPageCmd($strPageCmd)
    {
        global $_LANGID;

        $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $pages = $pageRepo->findBy(array(
            'cmd' => contrexx_addslashes($strPageCmd),
            'lang' => $_LANGID,
            'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
            'module' => $this->moduleName,
        ));
        return count($pages) > 0;
    }

    /**
     * Update the entries while activating the new language.
     *
     * @return null
     */
    public function updateEntries()
    {
        global $objDatabase, $_LANGID;

        $objEntries = $objDatabase->Execute('SELECT t1.* FROM `'.DBPREFIX.'module_'.$this->moduleTablePrefix.'_rel_entry_inputfields` as t1 WHERE `lang_id` = '.$_LANGID.' OR `lang_id` =  "SELECT
                                            first_rel_inputfield.`lang_id` AS `id`
                                        FROM
                                            '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_rel_entry_inputfields AS first_rel_inputfield
                                        LEFT JOIN
                                            '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_inputfields AS inputfield
                                        ON
                                            first_rel_inputfield.`field_id` = inputfield.`id`
                                        WHERE
                                            (first_rel_inputfield.`entry_id` = t1.`entry_id`)
                                        AND
                                            (first_rel_inputfield.`form_id` = t1.`form_id`)
                                        AND
                                            (first_rel_inputfield.`value` != "")
                                        LIMIT 1" GROUP BY `field_id`, `entry_id`, `form_id`  ORDER BY `entry_id`'
                        );

        if ($objEntries !== false) {
            while (!$objEntries->EOF) {
                foreach ($this->arrFrontendLanguages as $lang) {
                    $objDatabase->Execute('
                        INSERT IGNORE INTO '.DBPREFIX.'module_'.$this->moduleTablePrefix.'_rel_entry_inputfields
                            SET `entry_id`="' . contrexx_raw2db($objEntries->fields['entry_id']) . '",
                                `lang_id`="' . contrexx_raw2db($lang['id']) . '",
                                `form_id`="' . contrexx_raw2db($objEntries->fields['form_id']) . '",
                                `field_id`="' . contrexx_raw2db($objEntries->fields['field_id']) . '",
                                `value`="' . contrexx_raw2db($objEntries->fields['value']) . '"'
                    );
                }
                $objEntries->MoveNext();
            }
        }
    }

    function saveEntry($arrData, $intEntryId=null)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase, $_LANGID, $objInit;

        $objFWUser = \FWUser::getFWUserObject();
        $translationStatus = isset($arrData['translationStatus']) ? $arrData['translationStatus'] : array();

        //get data
        $intId = intval($intEntryId);
        $intFormId = intval($arrData['formId']);
        $strCreateDate = mktime();
        $strUpdateDate = mktime();
        $intUserId = intval($objFWUser->objUser->getId());
        $strLastIp = contrexx_addslashes($_SERVER['REMOTE_ADDR']);
        $strTransStatus = contrexx_addslashes(join(",", $translationStatus));


        //$arrCategories = explode(",",$arrData['selectedCategories']);
        //$arrLevels= explode("&",$arrData['selectedLevels']);


        if($objInit->mode == 'backend') {
            $intReadyToConfirm = 1;
        } else {
            if($this->arrSettings['settingsReadyToConfirm'] == 1) {
                $intReadyToConfirm = intval($arrData['readyToConfirm']);
            } else {
                $intReadyToConfirm = 1;
            }
        }

        switch($this->arrSettings['settingsEntryDisplaydurationValueType']) {
            case 1:
                $intDiffDay = $this->arrSettings['settingsEntryDisplaydurationValue'];
                $intDiffMonth = 0;
                $intDiffYear = 0;
                break;
            case 2:
                $intDiffDay = 0;
                $intDiffMonth = $this->arrSettings['settingsEntryDisplaydurationValue'];
                $intDiffYear = 0;
                break;
            case 3:
                $intDiffDay = 0;
                $intDiffMonth = 0;
                $intDiffYear = $this->arrSettings['settingsEntryDisplaydurationValue'];
                break;
        }

        if(empty($intId)) {
            if($objInit->mode == 'backend') {
                $intConfirmed = 1;
                $intActive = intval($arrData['status']) ? 1 : 0;
                $intShowIn = 3;
                $intDurationType =  intval($arrData['durationType']);
                $intDurationStart = $this->dateFromInput($arrData['durationStart']);
                $intDurationEnd = $this->dateFromInput($arrData['durationEnd']);
            } else {
                $intConfirmed = $this->arrSettings['settingsConfirmNewEntries'] == 1 ? 0 : 1;
                $intActive = 1;
                $intShowIn = 2;
                $intDurationType = $this->arrSettings['settingsEntryDisplaydurationType'];
                $intDurationStart = mktime();
                $intDurationEnd = mktime(0,0,0,date("m")+$intDiffMonth,date("d")+$intDiffDay,date("Y")+$intDiffYear);
            }

            $strValidateDate = $intConfirmed == 1 ? mktime() : 0;

            //insert new entry
            $objResult = $objDatabase->Execute("
                INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_entries
                   SET `form_id`='".$intFormId."',
                       `create_date`='".$strCreateDate."',
                       `validate_date`='".$strValidateDate."',
                       `update_date`='".$strValidateDate."',
                       `added_by`='".$intUserId."',
                       `lang_id`='".$_LANGID."',
                       `hits`='0',
                       `last_ip`='".$strLastIp."',
                       `confirmed`='".$intConfirmed."',
                       `active`='".$intActive."',
                       `duration_type`='".$intDurationType."',
                       `duration_start`='".$intDurationStart."',
                       `duration_end`='".$intDurationEnd."',
                       `duration_notification`='0',
                       `translation_status`='".$strTransStatus."',
                       `ready_to_confirm`='".$intReadyToConfirm."',
                       `updated_by`=".$intUserId.",
                       `popular_hits`=0,
                       `popular_date`='".$strValidateDate."'");
            if (!$objResult) {
                return false;
            }
            $intId = $objDatabase->Insert_ID();
        } else {
            self::getEntries($intId);
            $intOldReadyToConfirm = $this->arrEntries[$intId]['entryReadyToConfirm'];

            if($objInit->mode == 'backend') {
                $intConfirmed = 1;
                $intShowIn = 3;

                $intDurationStart = $this->dateFromInput($arrData['durationStart']);
                $intDurationEnd = $this->dateFromInput($arrData['durationEnd']);

                $arrAdditionalQuery[] = "`duration_type`='". intval($arrData['durationType'])."', `duration_start`='". intval($intDurationStart)."',  `duration_end`='". intval($intDurationEnd)."'";

                $arrAdditionalQuery[] = "`active`='". (intval($arrData['status']) ? 1 : 0)."'";
            } else {
                $intConfirmed = $this->arrSettings['settingsConfirmUpdatedEntries'] == 1 ? 0 : 1;
                $intShowIn = 2;
                $arrAdditionalQuery = null;
            }

            $arrAdditionalQuery[] = " `updated_by`='".$intUserId."'";

            if(intval($arrData['userId']) != 0) {
                $arrAdditionalQuery[] = "`added_by`='".intval($arrData['userId'])."'";
            }

            if (!empty($arrData['durationResetNotification'])) {
                $arrAdditionalQuery[] = "`duration_notification`='0'";
            }

            $strAdditionalQuery = join(",", $arrAdditionalQuery);
            $strValidateDate = $intConfirmed == 1 ? mktime() : 0;

            $objUpdateEntry = $objDatabase->Execute("
                UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_entries
                   SET `update_date`='".$strUpdateDate."',
                       `translation_status`='".$strTransStatus."',
                       `ready_to_confirm`='".$intReadyToConfirm."',
                       $strAdditionalQuery
                 WHERE `id`='$intId'");

            if (!$objUpdateEntry) {
                return false;
            }
            $objDeleteCategories = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories WHERE entry_id='".$intId."'");
            $objDeleteLevels = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_levels WHERE entry_id='".$intId."'");
        }


        //////////////////////
        // STORE ATTRIBUTES //
        //////////////////////

        $error = false;

        foreach ($this->getInputfields() as $arrInputfield) {
            // store selected category (field = category)
            if ($arrInputfield['id'] == 1) {
                $selectedCategories = isset($arrData['selectedCategories']) ? $arrData['selectedCategories'] : array();
                foreach ($selectedCategories as $intCategoryId) {
                    $objResult = $objDatabase->Execute("
                    INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories
                       SET `entry_id`='".intval($intId)."',
                           `category_id`='".intval($intCategoryId)."'");
                    if (!$objResult) {
                        \Message::error($objDatabase->ErrorMsg());
                        $error = true;
                    }
                }

                continue;
            }

            // store selected level (field = level)
            if ($arrInputfield['id'] == 2) {
                if ($this->arrSettings['settingsShowLevels'] == 1) {
                    $selectedLevels = isset($arrData['selectedLevels']) ? $arrData['selectedLevels'] : array();
                    foreach ($selectedLevels as $intLevelId) {
                        $objResult = $objDatabase->Execute("
                        INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_levels
                           SET `entry_id`='".intval($intId)."',
                               `level_id`='".intval($intLevelId)."'");
                        if (!$objResult) {
                            \Message::error($objDatabase->ErrorMsg());
                            $error = true;
                        }
                    }
                }

                continue;
            }

            // skip meta attributes or ones that are out of scope (frontend/backend)
            if (   // type = 'add_step'
                   $arrInputfield['type'] == 16
                   // type = 'label'
                || $arrInputfield['type'] == 18
                   // type = 'title'
                || $arrInputfield['type'] == 30
                   // show_in is neither FRONTEND or BACKEND ($intShowIn = 2|3) nor FRONTEND AND BACKEND (show_in=1)
                || ($arrInputfield['show_in'] != $intShowIn && $arrInputfield['show_in'] != 1)
            ) {
                continue;
            }

            // truncate attribute's data ($arrInputfield) from database if it's VALUE is not set (empty) or set to it's default value
            if (   empty($arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']])
                || $arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']] == $arrInputfield['default_value'][$_LANGID]
            ) {
                $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields WHERE entry_id='".$intId."' AND field_id='".intval($arrInputfield['id'])."'");
                if (!$objResult) {
                    \Message::error($objDatabase->ErrorMsg());
                    $error = true;
                }

                continue;
            }

            // initialize attribute
            $strType = $arrInputfield['type_name'];
            $strInputfieldClass = "\Cx\Modules\MediaDir\Model\Entity\MediaDirectoryInputfield".ucfirst($strType);
            try {
                $objInputfield = safeNew($strInputfieldClass, $this->moduleName);
            } catch (Exception $e) {
                \Message::error($e->getMessage());
                $error = true;

                continue;
            }

            // attribute is non-i18n
            if ($arrInputfield['type_multi_lang'] == 0) {
                try {
                    $strInputfieldValue = $objInputfield->saveInputfield($arrInputfield['id'], $arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']]);
                    $objResult = $objDatabase->Execute("
                        INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                           SET `entry_id`='".intval($intId)."',
                               `lang_id`='".intval($_LANGID)."',
                               `form_id`='".intval($intFormId)."',
                               `field_id`='".intval($arrInputfield['id'])."',
                               `value`='".contrexx_raw2db($strInputfieldValue)."'
              ON DUPLICATE KEY
                        UPDATE `value`='".contrexx_raw2db($strInputfieldValue)."'");
                    if (!$objResult) {
                        throw new \Exception($objDatabase->ErrorMsg());
                    }
                } catch (Exception $e) {
                    \Message::error($e->getMessage());
                    $error = true;
                }

                continue;
            }

            // delete attribute's data of languages that are no longer in use
            $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields WHERE entry_id='".$intId."' AND field_id = '".intval($arrInputfield['id'])."' AND lang_id NOT IN (".join(",", array_keys($this->arrFrontendLanguages)).")");

            // attribute is i18n
            foreach ($this->arrFrontendLanguages as $arrLang) {
                try {
                    $intLangId = $arrLang['id'];

                    // if the attribute is of type dynamic (meaning it can have an unlimited set of childs (references))
                    if ($arrInputfield['type_dynamic'] == 1) {
                        $arrDefault = array();
                        foreach ($arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']][0] as $intKey => $arrValues) {
                            $arrNewDefault = $arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']][$_LANGID][$intKey];
                            $arrOldDefault = $arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']]['old'][$intKey];
                            $arrNewValues = $arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']][$intLangId][$intKey];
                            foreach ($arrValues as $strKey => $strMasterValue) {
                                if ($intLangId == $_LANGID) {
                                    if ($arrNewDefault[$strKey] != $strMasterValue) {
                                        if ($strMasterValue != $arrOldDefault[$strKey] && $arrNewDefault[$strKey] == $arrOldDefault[$strKey]) {
                                            $arrDefault[$intKey][$strKey] = $strMasterValue;
                                        } else {
                                            $arrDefault[$intKey][$strKey] = $arrNewDefault[$strKey];
                                        }
                                    } else {
                                        $arrDefault[$intKey][$strKey] = $arrNewDefault[$strKey];
                                    }
                                } else {
                                    if ($arrNewValues[$strKey] == '') {
                                        $arrDefault[$intKey][$strKey] = $strMasterValue;
                                    } else {
                                        $arrDefault = $arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']][$intLangId];
                                    }
                                }
                            }
                            $strDefault = $arrDefault;
                        }
                        $strInputfieldValue = $objInputfield->saveInputfield($arrInputfield['id'], $strDefault, $intLangId);
                    } else if (
                        // attribute's VALUE of certain frontend language ($intLangId) is empty
                        empty($arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']][$intLangId])
                        // or the process is parsing the user's current interface language
                        || $intLangId == $_LANGID
                    ) {
                            $strMaster =
                                (isset($arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']][0])
                                  ? $arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']][0]
                                  : null);
                            $strNewDefault = isset($arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']][$_LANGID])
                                                ? $arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']][$_LANGID]
                                                : '';
                            if ($strNewDefault != $strMaster) {
                                $strDefault = $strMaster;
                            } else {
                                $strDefault = isset($arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']][$intLangId])
                                                ? $arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']][$intLangId]
                                                : '';
                            }
                            $strInputfieldValue = $objInputfield->saveInputfield($arrInputfield['id'], $strDefault, $intLangId);
                    } else {
                        // regular attribute get parsed
                        $strInputfieldValue = $objInputfield->saveInputfield($arrInputfield['id'], $arrData[$this->moduleNameLC.'Inputfield'][$arrInputfield['id']][$intLangId], $intLangId);
                    }

                    $objResult = $objDatabase->Execute("
                        INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                           SET `entry_id`='".intval($intId)."',
                               `lang_id`='".intval($intLangId)."',
                               `form_id`='".intval($intFormId)."',
                               `field_id`='".intval($arrInputfield['id'])."',
                               `value`='".contrexx_raw2db($strInputfieldValue)."'
              ON DUPLICATE KEY
                        UPDATE `value`='".contrexx_raw2db($strInputfieldValue)."'");
                    if (!$objResult) {
                        throw new \Exception($objDatabase->ErrorMsg());
                    }
                } catch (Exception $e) {
                    \Message::error($e->getMessage());
                    $error = true;
                }
            }
        }

        if(empty($intEntryId)) {
            if($intReadyToConfirm == 1) {
                new MediaDirectoryMail(1, $intId, $this->moduleName);
            }
            new MediaDirectoryMail(2, $intId, $this->moduleName);
        } else {
            if($intReadyToConfirm == 1 && $intOldReadyToConfirm == 0) {
                new MediaDirectoryMail(1, $intId, $this->moduleName);
            }
            new MediaDirectoryMail(6, $intId, $this->moduleName);
        }

        return $intId;
    }



    function deleteEntry($intEntryId)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        $objMail = new MediaDirectoryMail(5, $intEntryId, $this->moduleName);

        //delete entry
        $objDeleteEntry = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_entries WHERE `id`='".intval($intEntryId)."'");

        if($objDeleteEntry !== false) {
            //delete inputfields
            foreach ($this->getInputfields() as $key => $arrInputfield) {
                if($arrInputfield['id'] != 1 && $arrInputfield['id'] != 2) {

                    $strType = $arrInputfield['type_name'];
                    $strInputfieldClass = "\Cx\Modules\MediaDir\Model\Entity\MediaDirectoryInputfield".ucfirst($strType);

                    try {
                        $objInputfield = safeNew($strInputfieldClass, $this->moduleName);

                        if(!$objInputfield->deleteContent(intval($intEntryId), intval($arrInputfield['id']))) {
                            return false;
                        }
                    } catch (Exception $e) {
                        echo "Error: ".$e->getMessage();
                    }
                }
            }

            //delete categories
            $objDeleteCategories = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_categories WHERE `entry_id`='".intval($intEntryId)."'");

            //delete levels
            $objDeleteLevels = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_levels WHERE `entry_id`='".intval($intEntryId)."'");
        } else {
            return false;
        }

        return true;
    }



    function confirmEntry($intEntryId)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        $objConfirmEntry = $objDatabase->Execute("
            UPDATE
                ".DBPREFIX."module_".$this->moduleTablePrefix."_entries
            SET
                `confirmed`='1',
                `active`='1',
                `validate_date`='".time()."'
            WHERE
                `id`='".intval($intEntryId)."'
        ");

        if($objConfirmEntry !== false) {
            $objMail = new MediaDirectoryMail(3, $intEntryId, $this->moduleName);
            return true;
        } else {
           return false;
        }
    }



    function updateHits($intEntryId)
    {
        global $_ARRAYLANG, $_CORELANG, $objDatabase;

        $intHits        = intval($this->arrEntries[intval($intEntryId)]['entryHits']);
        $intPopularHits = intval($this->arrEntries[intval($intEntryId)]['entryPopularHits']);
        $strPopularDate = $this->arrEntries[intval($intEntryId)]['entryPopularDate'];
        $intPopularDays = intval($this->arrSettings['settingsPopularNumRestore']);
        $strLastIp      = $this->arrEntries[intval($intEntryId)]['entryLastIp'];
        $strNewIp       = contrexx_addslashes($_SERVER['REMOTE_ADDR']);

        $strToday  = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
        $tempDays  = date("d",$strPopularDate);
        $tempMonth = date("m",$strPopularDate);
        $tempYear  = date("Y",$strPopularDate);

        $strPopularEndDate  = mktime(0, 0, 0, $tempMonth, $tempDays+$intPopularDays,  $tempYear);

        if ($strLastIp != $strNewIp) {
            if ($strToday >= $strPopularEndDate) {
                $strNewPopularDate  = $strToday;
                $intPopularHits     = 1;
            } else {
                $strNewPopularDate  = $strPopularDate;
                $intPopularHits++;
            }

            $intHits++;

            $objResult = $objDatabase->Execute("UPDATE
                                                    ".DBPREFIX."module_".$this->moduleTablePrefix."_entries
                                                SET
                                                    hits='".$intHits."',
                                                    popular_hits='".$intPopularHits."',
                                                    popular_date='".$strNewPopularDate."',
                                                    last_ip='".$strNewIp."'
                                                WHERE
                                                    id='".intval($intEntryId)."'
                                               ");
        }
    }



    function countEntries()
    {
        return $this->recordCount;
    }



    function getUsers($intEntryId=null)
    {
        global $objDatabase;

// TODO: replace by FWUser::getParsedUserTitle()
        $strDropdownUsers = '<select name="userId"style="width: 302px">';
        $objFWUser = \FWUser::getFWUserObject();

        if ($objUser = $objFWUser->objUser->getUsers(null,null,null,array('username'))) {
            while (!$objUser->EOF) {
                if(intval($objUser->getID()) == intval($this->arrEntries[$intEntryId]['entryAddedBy'])) {
                    $strSelected = 'selected="selected"';
                } else {
                    $strSelected = '';
                }

                $strDropdownUsers .= '<option value="'.intval($objUser->getID()).'" '.$strSelected.' >'.contrexx_raw2xhtml($objUser->getUsername()).'</option>';
                $objUser->next();
            }
        }

        $strDropdownUsers .= '</select>';

        return $strDropdownUsers;
    }

    function parseCategoryLevels($intType, $intEntryId=null, $objTpl) {
        if ($intType == 1) {
            // categories
            $objCategoriesLevels = $this->getCategories($intEntryId);
            $list = 'CATEGORY';
        } else {
            // levels
            $objCategoriesLevels = $this->getLevels($intEntryId);
            $list = 'LEVEL';
        }

        if (!$objTpl->blockExists('mediadir_' . strtolower($list))) {
            return false;
        }

        if ($objCategoriesLevels !== false && $objCategoriesLevels->RecordCount() > 0) {
            while(!$objCategoriesLevels->EOF) {
                $objTpl->setVariable(array(
                    $this->moduleLangVar . '_ENTRY_' . $list . '_ID' => $objCategoriesLevels->fields['elm_id'],
                    $this->moduleLangVar . '_ENTRY_' . $list . '_NAME' => $objCategoriesLevels->fields['elm_name'],
                ));
                $objTpl->parse('mediadir_' . strtolower($list));
                $objCategoriesLevels->MoveNext();
            }
        } else {
            $objTpl->hideBlock('mediadir_' . strtolower($list));
        }
    }


    function getCategories($intEntryId = null) {
        global $objDatabase, $_LANGID;
        $query = "SELECT
            cat_rel.`category_id` AS `elm_id`,
            cat_name.`category_name` AS `elm_name`
          FROM
            ".DBPREFIX."module_mediadir_rel_entry_categories AS cat_rel,
            ".DBPREFIX."module_mediadir_categories_names AS cat_name
          WHERE
            cat_rel.`category_id` = cat_name.`category_id`
          AND
            cat_rel.`entry_id` = ?
          AND
            cat_name.`lang_id` = ?
          ORDER BY
            cat_name.`category_name` ASC
          ";

        return $objDatabase->Execute($query, array($intEntryId, $_LANGID));
    }


    function getLevels($intEntryId = null) {
        global $objDatabase, $_LANGID;
        $query = "SELECT
            level_rel.`level_id` AS `elm_id`,
            level_name.`level_name` AS `elm_name`
          FROM
            ".DBPREFIX."module_mediadir_rel_entry_levels AS level_rel,
            ".DBPREFIX."module_mediadir_level_names AS level_name
          WHERE
            level_rel.`level_id` = level_name.`level_id`
          AND
            level_rel.`entry_id` = ?
          AND
            level_name.`lang_id` = ?
          ORDER BY
            level_name.`level_name` ASC
          ";

        return $objDatabase->Execute($query, array($intEntryId, $_LANGID));
    }


    function getCategoriesLevels($intType, $intEntryId=null, $cmdName=null)
    {
        if ($intType == 1) {//categories
            $objEntryCategoriesLevels = $this->getCategories($intEntryId);
            $paramName = 'cid';
        } else {//levels
            $objEntryCategoriesLevels = $this->getLevels($intEntryId);
            $paramName = 'lid';
        }

        $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $page = $pageRepo->findOneByModuleCmdLang($this->moduleName, $cmdName, FRONTEND_LANG_ID);

        if ($objEntryCategoriesLevels !== false) {
            $list = '<ul>';
            while (!$objEntryCategoriesLevels->EOF) {
                $paramValue = intval($objEntryCategoriesLevels->fields['elm_id']);
                $url = $page ? \Cx\Core\Routing\URL::fromPage($page, array($paramName => $paramValue)) : '';
                $name = htmlspecialchars($objEntryCategoriesLevels->fields['elm_name'], ENT_QUOTES, CONTREXX_CHARSET);
                $list .= '<li>';
                $list .= !empty($url) ? '<a href="'.$url.'">'.$name.'</a>' : $name;
                $list .= '</li>';
                $objEntryCategoriesLevels->MoveNext();
            }
            $list .= '</ul>';
        }

        return $list;
    }


    function saveOrder($arrData) {
        global $objDatabase;

        foreach($arrData['entriesOrder'] as $intEntryId => $intEntryOrder) {
            $objRSEntryOrder = $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_entries SET `order`='".intval($intEntryOrder)."' WHERE `id`='".intval($intEntryId)."'");

            if ($objRSEntryOrder === false) {
                return false;
            }
        }

        return true;
    }


    function setDisplaydurationNotificationStatus($intEntryId, $bolStatus)
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_entries SET duration_notification='".intval($bolStatus)."' WHERE id='".intval($intEntryId)."'");
    }

    /**
     * Takes a date in the format dd.mm.yyyyand returns it's representation as mktime()-timestamp.
     *
     * @param $value string
     * @return long timestamp
     */
    function dateFromInput($value) {
        if($value === null || $value === '') //not set POST-param passed, return null for the other functions to know this
            return null;
        $arrDate = array();
        if (preg_match('/^([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{1,4})/', $value, $arrDate)) {
            return mktime(0, 0, 0, intval($arrDate[2]), intval($arrDate[1]), intval($arrDate[3]));
        } else {
            return time();
        }
    }

    /**
     * Searches the content and returns an array that is built as needed by the search module.
     *
     * @param string $searchTerm
     *
     * @return array
     */
    public function searchResultsForSearchModule($searchTerm)
    {
        $em = \Env::get('cx')->getDb()->getEntityManager();
        $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        // only list results in case the associated page of the module is active
        $page = $pageRepo->findOneBy(array(
            'module' => 'MediaDir',
            'lang'   => FRONTEND_LANG_ID,
            'type'   => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
        ));

        //If page is not exists or page is inactive then return empty result
        if (!$page || !$page->isActive()) {
            return array();
        }

        //get the config site values
        \Cx\Core\Setting\Controller\Setting::init('Config', 'site','Yaml');
        $coreListProtectedPages   = \Cx\Core\Setting\Controller\Setting::getValue('coreListProtectedPages','Config');
        $searchVisibleContentOnly = \Cx\Core\Setting\Controller\Setting::getValue('searchVisibleContentOnly','Config');
        //get the config otherConfigurations value
        \Cx\Core\Setting\Controller\Setting::init('Config', 'otherConfigurations','Yaml');
        $searchDescriptionLength  = \Cx\Core\Setting\Controller\Setting::getValue('searchDescriptionLength','Config');

        $hasPageAccess = true;
        $isNotVisible = ($searchVisibleContentOnly == 'on') && !$page->isVisible();
        if ($coreListProtectedPages == 'off' && $page->isFrontendProtected()) {
            $hasPageAccess = \Permission::checkAccess($page->getFrontendAccessId(), 'dynamic', true);
        }

        //If the page is invisible and frontend access is denied then return empty result
        if ($isNotVisible || !$hasPageAccess) {
            return array();
        }

        //get the media directory entry by the search term
        $entries = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry($this->moduleName);
        $entries->getEntries(null, null, null, $searchTerm);

        //if no entries found then return empty result
        if (empty($entries->arrEntries)) {
            return array();
        }

        $results            = array();
        $formEntries        = array();
        $defaultEntries     = null;
        $objForm            = new \Cx\Modules\MediaDir\Controller\MediaDirectoryForm(null, $this->moduleName);
        $numOfEntries       = intval($entries->arrSettings['settingsPagingNumEntries']);
        foreach ($entries->arrEntries as $entry) {
            $pageUrlResult = null;
            $entryForm     = $objForm->arrForms[$entry['entryFormId']];
            //Get the entry's link url
            //check the entry's form detail view exists if not,
            //check the entry's form overview exists if not,
            //check the default overview exists if not, dont show the corresponding entry in entry
            switch (true) {
                case $entries->checkPageCmd('detail' . $entry['entryFormId']):
                    $pageUrlResult = \Cx\Core\Routing\Url::fromModuleAndCmd(
                                        $entries->moduleName,
                                        'detail' . $entry['entryFormId'],
                                        FRONTEND_LANG_ID,
                                        array('eid' => $entry['entryId']));
                    break;
                case $pageCmdExists = $entries->checkPageCmd($entryForm['formCmd']):
                case $entries->checkPageCmd(''):
                    if ($pageCmdExists && !isset($formEntries[$entryForm['formCmd']])) {
                        $formEntries[$entryForm['formCmd']] = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry($entries->moduleName);
                        $formEntries[$entryForm['formCmd']]->getEntries(null, null, null, null, null, null, 1, null, 'n', null, null, $entryForm['formId']);
                    }
                    if (!$pageCmdExists && !isset($defaultEntries)) {
                        $defaultEntries = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry($entries->moduleName);
                        $defaultEntries->getEntries();
                    }
                    //get entry's form overview / default page paging position
                    $entriesPerPage = $numOfEntries;
                    if ($pageCmdExists) {
                        $entriesPerPage = !empty($entryForm['formEntriesPerPage']) ? $entryForm['formEntriesPerPage'] : $numOfEntries;
                    }
                    $pageCmd   = $pageCmdExists ? $entryForm['formCmd'] : '';
                    $entryKeys = $pageCmdExists ? array_keys($formEntries[$entryForm['formCmd']]->arrEntries) : array_keys($defaultEntries->arrEntries);
                    $entryPos  = array_search($entry['entryId'], $entryKeys);
                    $position  = floor($entryPos / $entriesPerPage);
                    $pageUrlResult = \Cx\Core\Routing\Url::fromModuleAndCmd(
                                        $entries->moduleName,
                                        $pageCmd,
                                        FRONTEND_LANG_ID,
                                        array('pos' => $position * $entriesPerPage));
                    break;
                default:
                    break;
            }

            //If page url is empty then dont show it in the result
            if (!$pageUrlResult) {
                continue;
            }
            //Get the search results title and content from the form context field 'title' and 'content'
            $title          = current($entry['entryFields']);
            $content        = '';
            $objInputfields = new MediaDirectoryInputfield($entry['entryFormId'], false, $entry['entryTranslationStatus'], $this->moduleName);
            $inputFields    = $objInputfields->getInputfields();
            foreach ($inputFields as $arrInputfield) {
                $contextType = isset($arrInputfield['context_type']) ? $arrInputfield['context_type'] : '';
                if (!in_array($contextType, array('title', 'content'))) {
                    continue;
                }
                $strType = isset($arrInputfield['type_name']) ? $arrInputfield['type_name'] : '';
                $strInputfieldClass = "\Cx\Modules\MediaDir\Model\Entity\MediaDirectoryInputfield" . ucfirst($strType);
                try {
                    $objInputfield        = safeNew($strInputfieldClass, $this->moduleName);
                    $arrTranslationStatus = (contrexx_input2int($arrInputfield['type_multi_lang']) == 1)
                                            ? $entry['entryTranslationStatus']
                                            : null;
                    $arrInputfieldContent = $objInputfield->getContent($entry['entryId'], $arrInputfield, $arrTranslationStatus);
                    if (\Cx\Core\Core\Controller\Cx::instanciate()->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND && \Cx\Core\Setting\Controller\Setting::getValue('blockStatus', 'Config')) {
                        $arrInputfieldContent[$this->moduleLangVar.'_INPUTFIELD_VALUE'] = preg_replace('/\\[\\[(BLOCK_[A-Z0-9_-]+)\\]\\]/', '{\\1}', $arrInputfieldContent[$this->moduleLangVar.'_INPUTFIELD_VALUE']);
                        \Cx\Modules\Block\Controller\Block::setBlocks($arrInputfieldContent[$this->moduleLangVar.'_INPUTFIELD_VALUE'], \Cx\Core\Core\Controller\Cx::instanciate()->getPage());
                    }
                } catch (\Exception $e) {
                    \DBG::log($e->getMessage());
                    continue;
                }
                $inputFieldValue = $arrInputfieldContent[$this->moduleConstVar . '_INPUTFIELD_VALUE'];
                if (empty($inputFieldValue)) {
                    continue;
                }
                if ($contextType == 'title') {
                    $title = $inputFieldValue;
                } elseif ($contextType == 'content') {
                    $content = \Cx\Core_Modules\Search\Controller\Search::shortenSearchContent(
                                    $inputFieldValue,
                                    $searchDescriptionLength
                                );
                }
            }

            $results[] = array(
                'Score'   => 100,
                'Title'   => html_entity_decode(contrexx_strip_tags($title), ENT_QUOTES, CONTREXX_CHARSET),
                'Content' => $content,
                'Link'    => $pageUrlResult->toString()
            );
        }
        return $results;
    }
}
