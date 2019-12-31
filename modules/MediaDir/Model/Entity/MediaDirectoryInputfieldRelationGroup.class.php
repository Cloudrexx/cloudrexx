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
 * Media Directory Inputfield Relation Group Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Model\Entity;

/**
 * Media Directory Inputfield Relation Group Exception
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryInputfieldRelationGroupException extends \Exception {}

/**
 * Media Directory Inputfield Relation Group Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
class MediaDirectoryInputfieldRelationGroup extends \Cx\Modules\MediaDir\Controller\MediaDirectoryLibrary implements Inputfield
{
    public $arrPlaceholders = array(
        'TXT_MEDIADIR_INPUTFIELD_NAME',
        'MEDIADIR_INPUTFIELD_VALUE',
        'MEDIADIR_INPUTFIELD_CUSTOM'
    );


    /**
     * Constructor
     */
    function __construct($name)
    {
        parent::__construct('.', $name);
        parent::getFrontendLanguages();
        parent::getSettings();
    }



    function getInputfield($intView, $arrInputfield, $intEntryId=null)
    {
        global $objDatabase, $objInit, $_ARRAYLANG;

        $intId = intval($arrInputfield['id']);
        $langId = static::getOutputLocale()->getId();

        switch ($intView) {
            default:
            case 1:
                //modify (add/edit) View
                if(isset($intEntryId) && $intEntryId != 0) {
                    $objInputfieldValue = $objDatabase->Execute("
                        SELECT
                            `value`
                        FROM
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                        WHERE
                            field_id=".$intId."
                        AND
                            entry_id=".$intEntryId."
                        LIMIT 1
                    ");
                    $strValue = htmlspecialchars($objInputfieldValue->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
                } else {
                    $strValue = null;
                }

                $intFormType = empty($arrInputfield['default_value'][$langId]) ? $arrInputfield['default_value'][0] : $arrInputfield['default_value'][$langId];
                $arrSelectorOptions = array();
                $arrValue = explode(",",$strValue);

                $objEntries    = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry();
                $intFormType   = intval($intFormType) == 0 ? null : $intFormType;

                $objFWUser     = \FWUser::getFWUserObject();
                $objUser       = $objFWUser->objUser;
                //$intUserId     = intval($objUser->getId());

                //OSEC COSTUMIZING
                if($intFormType == 6) {
                    $objEntries->getEntries(null,null,null,null,null,null,true,null,'n',null,null,$intFormType);
                } else {
                    if ($intEntryId) {
                        $objEntry = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry();
                        $objEntry->getEntries($intEntryId, null, null, null, null, false,true);
                        $intUserId = $objEntry->arrEntries[$intEntryId]['entryAddedBy'];
                        if (!$intUserId) {
                            $objEntry->getEntries($intEntryId, null, null, null, null, true,true);
                            $intUserId = $objEntry->arrEntries[$intEntryId]['entryAddedBy'];
                        }
                        if ($intUserId) {
                            $objEntries->getEntries(null,null,null,null,null,null,true,null,'n',$intUserId,null,$intFormType);
                        }
                    } else {
                        $objEntries->getEntries(null,null,null,null,null,null,true,null,'n', \FWUser::getFWUserObject()->objUser->getId(), null, $intFormType);
                    }
                }

                foreach($objEntries->arrEntries as $intKey => $arrEntry) {
                    if(in_array($intKey,$arrValue)) {
                        $arrSelectorOptions['selected'][$arrEntry['entryFields'][0]] = '<option value="'.$arrEntry['entryId'].'" style="cursor: pointer;">'.$arrEntry['entryFields'][0].'</option>';
                    } else {
                        $arrSelectorOptions['not_selected'][$arrEntry['entryFields'][0]] = '<option value="'.$arrEntry['entryId'].'" style="cursor: pointer;">'.$arrEntry['entryFields'][0].'</option>';
                    }
                }
                /*$arrSelectedList = array();
                foreach($objEntries->arrEntries as $intKey => $arrEntry) {
                    $arrSelectedList[$arrEntry['entryId']] = array(
                        'name'      => $arrEntry['entryFields'][0],
                        'class'     => "childCategory_".$intId."_".intval($arrEntry['entryId']),
                        'id'        => (in_array($intKey, $arrValue) ? '' : 'o')."childCategory".$intId."_".intval($arrEntry['entryId']),
                        'selected'  => intval(in_array($intKey, $arrValue)),
                        'edit'      => $intFormType == 13 ? 'javascript:editSelectedElement_'.$intId.'('.$arrEntry['entryId'].')' : ''
                    );
                }*/

                /*print_r("<pre>");
                print_r($arrSelectedList);
                print_r("</pre>");*/

                ksort($arrSelectorOptions['selected']);
                ksort($arrSelectorOptions['not_selected']);

                $strSelectorSelected = join("", $arrSelectorOptions['selected']);
                $strSelectorNotSelected = join("", $arrSelectorOptions['not_selected']);

                $strAddNewCmd = '';
                try {
                    $strAddNewCmd = $this->getAddUrl(intval($intFormType));
                } catch (MediaDirectoryInputfieldRelationGroupException $e) {}

                $strEditCmd = '';
                try {
                    $strEditCmd = $this->getEditUrl(intval($intFormType));
                } catch (MediaDirectoryInputfieldRelationGroupException $e) {}

                if($objInit->mode == 'backend') {
                    $strAddNewButton = '';
                    //$strEditButton = '';
                    //$strStyle = 'style="overflow: auto; border: 1px solid #0A50A1; background-color: #ffffff; width: 298px; height: 200px; float: left; list-style: none; padding: 0px; margin: 0px 5px 0px 0px;"';
                    //$strRefreshNewButton = '';
                } else {
                    //get user attributes
                    /*$objFWUser      = \FWUser::getFWUserObject();
                    $objUser        = $objFWUser->objUser;
                    $intUserId      = intval($objUser->getId());*/
                    $intUserIsAdmin = $objUser->getAdminStatus();

                    $arrUserGroups  = array();
                    $objGroup = $objFWUser->objGroup->getGroups(array('is_active' => true, 'type' => 'frontend'));

                    while (!$objGroup->EOF) {
                        if(in_array($objGroup->getId(), $objUser->getAssociatedGroupIds())) {
                            $arrUserGroups[] = $objGroup->getId();
                        }
                        $objGroup->next();
                    }


                    self::getCommunityGroups();
                    $bolAddNewAlowed = false;

                    foreach ($arrUserGroups as $intKey => $intGroupId) {
                        if(!empty($this->arrCommunityGroups[$intGroupId]['status_group'][$intFormType]) && !$bolAddNewAlowed) {
                            $bolAddNewAlowed = true;
                        }
                    }

                    if($bolAddNewAlowed || (intval($intFormType) == 0) || $intUserIsAdmin == 1) {
                        $strAddNewButton = '<a class="addEntryLink" rel="shadowbox[add'.$intId.'];height=750;width=730;options={onClose:new Function(\'refreshSelector_'.$intId.'(\\\''.$intId.'\\\', \\\''.$this->moduleNameLC.'Inputfield_deselected_'.$intId.'\\\', \\\''.$this->moduleNameLC.'Inputfield_'.$intId.'\\\', \\\''.$_GET['section'].'\\\', \\\''.$_GET['cmd'].'\\\', \\\''.$intEntryId.'\\\')\')}" href="'.$strAddNewCmd.'" ><img src="../core/Core/View/Media/icons/icon-user-add.png" style="cursor: pointer;  border: 0px;" />&nbsp;'.$_ARRAYLANG['TXT_MEDIADIR_ADD_ENTRY'].'</a>';
                        $strEditFunction = 'ondblclick="editSelectedElement_'.$intId.'(this);"';
                    } else {
                        $strAddNewButton = '';
                        $strEditFunction = '';
                    }

                    //$strRefreshNewButton = '<br /><a href="javascript:refreshSelector_'.$intId.'(\''.$intId.'\', \''.$this->moduleNameLC.'Inputfield_deselected_'.$intId.'\', \''.$this->moduleNameLC.'Inputfield_'.$intId.'\', \''.$_GET['section'].'\', \''.$_GET['cmd'].'\', \''.$intEntryId.'\');"><img src="../core/Core/View/Media/icons/refresh.gif" style="cursor: pointer;  border: 0px;" />&nbsp;'.$_ARRAYLANG['TXT_MEDIADIR_REFRESH'].'</a>';
                    //$strStyle = 'style="overflow: auto; float: left; list-style: none; padding: 0px; margin: 0px 5px 0px 0px;"';
                }

                //$moduleName = $this->moduleName;
                $moduleNameDeselected = $this->moduleNameLC.'Inputfield_deselected_'.$intId;
                $moduleNameSelected = $this->moduleNameLC.'Inputfield_'.$intId;
                $pageSection = $_GET['section'];
                $pageCmd = $_GET['cmd'];

                /*$strSelectorWrapperClass = $this->moduleName.'Selector';
                $strSelectorListClass = $this->moduleName.'SelectorList_'.$intId;
                $strSelectorId = $this->moduleName.'Inputfield_'.$intId.'_Selector';
                $strSelectorFunction = $this->moduleName.'Inputfield_'.$intId.'_Selector';
                $strSerializeFunction = $this->moduleName.'Inputfield_'.$intId.'_SelectorSerialize';
                $strSelectedId = 'selectedInputfield_'.$intId.'_List';
                $strNotSelectedId = 'deselectedInputfield_'.$intId.'_List';
                $strInpufieldId = $this->moduleName.'Inputfield_'.$intId;
                $strInpufieldName = $this->moduleName.'Inputfield['.$intId.']';
                $strParentLeftName = 'oParent'.$intId.'_';
                $strParentRightName = 'Parent'.$intId.'_';
                $strChildName = 'child'.$intId.'_';

                $listElementsJSON = json_encode($arrSelectedList);

                $editPageCmd = 'edit'.(empty($arrInputfield['default_value'][$langId]) ? $arrInputfield['default_value'][0] : $arrInputfield['default_value'][$langId]);*/
                /*$strInputfield = '<div class="'.$strSelectorWrapperClass.'" style="overflow: hidden;">';
                $strInputfield .= '<ul id="'.$strNotSelectedId.'" class="'.$strSelectorListClass.'" '.$strStyle.'>';
                $strInputfield .= '</ul>';
                $strInputfield .= '<ul id="'.$strSelectedId.'" class="'.$strSelectorListClass.'"  '.$strStyle.'>';
                $strInputfield .= '</ul><br />';
                $strInputfield .= '<input type="hidden" value="" id="'.$strInpufieldId.'" name="'.$strInpufieldName.'" />';
                $strInputfield .= '</div>';
                $strInputfield .= '<div class="'.$strSelectorWrapperClass.'Add">'.$strAddNewButton.'</div>';*/

                /*$strInputfield = '
                    <div class="marketplaceSelector">
                    <div id="selectedInputfield_'.$intId.'_Left" class="ListBoxForm drop"></div>
                    <div id="selectedInputfield_'.$intId.'_Right" class="ListBoxForm drop"></div>
                    </div>
                    <input type="hidden" value="" id="'.$strInpufieldId.'" name="'.$strInpufieldName.'" />
                ';
                $strInputfield .= '<div class="'.$strSelectorWrapperClass.'Add">'.$strAddNewButton.'</div>';*/

                $strInputfield .= <<< EOF
<script type="text/javascript">
/* <![CDATA[ */
/*    \$J(document).ready(function() {
        JSONData['marketplaceData_$intId']     = $listElementsJSON;
        InsertJSONdataIntoElement(JSONData['marketplaceData_$intId'], 'selectedInputfield_{$intId}_Left', 'selectedInputfield_{$intId}_Right', 0, 0, 'marketplaceData_$intId');
        InsertJSONdataIntoElement(JSONData['marketplaceData_$intId'], 'selectedInputfield_{$intId}_Right', 'selectedInputfield_{$intId}_Left', 1, 0, 'marketplaceData_$intId');
        InitDrag();
    });

    var $strSerializeFunction = function() {
        \$J('#$strInpufieldId').val(marketplaceGetElList(\$J('#selectedInputfield_{$intId}_Right')));
    }


    function refreshSelector_$intId(fieldId,elementDeselectedId,elementSelectedId,pageSection,pageCmd,entryId){
        \$J.ajax({
            url: 'index.php?section=' + pageSection + '&cmd=' + pageCmd + '&inputfield=refresh&field=' + fieldId + '&eid=' +  entryId,
            success:function(data){
                eval("JSONData['marketplaceData_$intId'] " + data);
                InsertJSONdataIntoElement(JSONData['marketplaceData_$intId'], 'selectedInputfield_{$intId}_Left', 'selectedInputfield_{$intId}_Right', 0, 0, 'marketplaceData_$intId');
                InsertJSONdataIntoElement(JSONData['marketplaceData_$intId'], 'selectedInputfield_{$intId}_Right', 'selectedInputfield_{$intId}_Left', 1, 0, 'marketplaceData_$intId');
                InitDrag();
            }
        });
    }


    function editSelectedElement_$intId(eid){
        var editLink = 'index.php?section=mediadir&cmd=$editPageCmd&eid=' + eid;

        Shadowbox.open({
            content:    editLink,
            player:     "iframe",
            height:     625,
            width:      700,
            options:    {onClose:function(){refreshSelector_$intId('$intId', '$moduleNameDeselected', '$moduleNameSelected', '$pageSection', '$pageCmd', '$intEntryId')}}

        });
    }*/


function searchElement(elementId, term){
    elmSelector = document.getElementById(elementId);

    var pattern = term.toLowerCase()
    var reg = new RegExp(pattern);

    for (i = 0; i < elmSelector.length; ++i) {
        var text = elmSelector.options[i].text.toLowerCase()

        if (text.match(reg)) {
            elmSelector.options[i].selected = true;
        } else {
            elmSelector.options[i].selected = false;
        }
    }
}

function refreshSelector_$intId(fieldId,elementDeselectedId,elementSelectedId,pageSection,pageCmd,entryId){
    cx.jQuery.get('index.php', {section : pageSection, cmd : pageCmd,  inputfield : 'refresh', field : fieldId, eid : entryId}).success(function(response) {
        var arrResponse = response.split(",");
        cx.jQuery('#'+elementDeselectedId).html(arrResponse[0]);
        cx.jQuery('#'+elementSelectedId).html(arrResponse[1]);
    });
}

function editSelectedElement_$intId(elmSelector){
    var editLink = '$strEditCmd?eid=' + elmSelector.value;

    Shadowbox.open({
        content:    editLink,
        player:     "iframe",
        height:     625,
        width:      700,
        options:    {onClose:function(){refreshSelector_$intId('$intId', '$moduleNameDeselected', '$moduleNameSelected', '$pageSection', '$pageCmd', '$intEntryId')}}

    });
}
/* ]]> */
</script>
EOF;

                $strInputfield .= '<div id="'.$this->moduleNameLC.'Selector_'.$intId.'" class="'.$this->moduleNameLC.'Selector" style="float: left; height: auto !important;">';
                $strInputfield .= '<div id="'.$this->moduleNameLC.'Selector_'.$intId.'_Left" class="'.$this->moduleNameLC.'SelectorLeft" style="float: left; height: auto !important;"><select '.$strEditFunction.' id="'.$this->moduleNameLC.'Inputfield_deselected_'.$intId.'"  name="'.$this->moduleNameLC.'Inputfield[deselected_'.$intId.'][]" size="12" multiple="multiple" style="width: 240px;">';
                $strInputfield .= $strSelectorNotSelected;
                $strInputfield .= '</select><br />';
                $strInputfield .= $strAddNewButton;
                $strInputfield .= '</div>';
                //$strInputfield .= '<br /><input class="'.$this->moduleNameLC.'SelectorSearch" type="text" onclick="this.value=\'\';" onkeyup="searchElement(\''.$this->moduleNameLC.'Inputfield_deselected_'.$intId.'\', this.value);" value="Suchbegriff..."  style="width: 150px;"/></div>';
                $strInputfield .= '<div class="'.$this->moduleNameLC.'SelectorCenter" style="float: left; height: 100px; padding: 60px 10px 0px 10px;">';
                $strInputfield .= '<input style="width: 40px; min-width: 40px;" value=" &gt;&gt; " name="addElement" onclick="moveElement(document.entryModfyForm.elements[\''.$this->moduleNameLC.'Inputfield_deselected_'.$intId.'\'],document.entryModfyForm.elements[\''.$this->moduleNameLC.'Inputfield_'.$intId.'\'],addElement,removeElement);" type="button" />';
                $strInputfield .= '<br />';
                $strInputfield .= '<input style="width: 40px; min-width: 40px;" value=" &lt;&lt; " name="removeElement" onclick="moveElement(document.entryModfyForm.elements[\''.$this->moduleNameLC.'Inputfield_'.$intId.'\'],document.entryModfyForm.elements[\''.$this->moduleNameLC.'Inputfield_deselected_'.$intId.'\'],removeElement,addElement);" type="button" />';
                $strInputfield .= '</div>';
                $strInputfield .= '<div id="'.$this->moduleNameLC.'Selector_'.$intId.'_Right" class="'.$this->moduleNameLC.'SelectorRight" style="float: left; height: auto !important;"><select '.$strEditFunction.' id="'.$this->moduleNameLC.'Inputfield_'.$intId.'"  name="'.$this->moduleNameLC.'Inputfield['.$intId.'][]" size="12" multiple="multiple" style="width: 240px;">';
                $strInputfield .= $strSelectorSelected;
                $strInputfield .= '</select><br />';
                $strInputfield .= '</div>';
                $strInputfield .= '</div>';

                return $strInputfield;

                break;
            case 2:
                //search View

               return $strInputfield;

               break;

            case 3:
                // OSEC CUSTOMIZING
                //ajax relaod
                if(isset($intEntryId) && $intEntryId != 0) {
                    $objInputfieldValue = $objDatabase->Execute("
                        SELECT
                            `value`
                        FROM
                            ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                        WHERE
                            field_id=".$intId."
                        AND
                            entry_id=".$intEntryId."
                        LIMIT 1
                    ");
                    $strValue = htmlspecialchars($objInputfieldValue->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
                } else {
                    $strValue = null;
                }

                $intFormType = empty($arrInputfield['default_value'][$langId]) ? $arrInputfield['default_value'][0] : $arrInputfield['default_value'][$langId];
                $arrValue = explode(",",$strValue);
                $arrSelectorOptions = array();

                $objEntries    = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry();
                $intFormType   = intval($intFormType) == 0 ? null : $intFormType;

                $objFWUser     = \FWUser::getFWUserObject();
                $objUser       = $objFWUser->objUser;
                //$intUserId     = intval($objUser->getId());

                //OSEC COSTUMIZING
                if($intFormType == 6) {
                    $objEntries->getEntries(null,null,null,null,null,null,true,null,'n',null,null,$intFormType);
                } else {
                    if ($intEntryId) {
                        $objEntry = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry();
                        $objEntry->getEntries($intEntryId, null, null, null, null, false,true);
                        $intUserId = $objEntry->arrEntries[$intEntryId]['entryAddedBy'];
                        if (!$intUserId) {
                            $objEntry->getEntries($intEntryId, null, null, null, null, true,true);
                            $intUserId = $objEntry->arrEntries[$intEntryId]['entryAddedBy'];
                        }
                        if ($intUserId) {
                            $objEntries->getEntries(null,null,null,null,null,null,true,null,'n',$intUserId,null,$intFormType);
                        }
                    } else {
                        $objEntries->getEntries(null,null,null,null,null,null,true,null,'n', \FWUser::getFWUserObject()->objUser->getId(), null, $intFormType);
                    }
                }

                foreach($objEntries->arrEntries as $intKey => $arrEntry) {
                    if(!in_array($intKey,$arrValue)) {
                         $arrSelectorOptions['not_selected'][$arrEntry['entryFields'][0]] = '<option  style="cursor: pointer;" value="'.$arrEntry['entryId'].'">'.$arrEntry['entryFields'][0].'</option>';
                    } else {
                         $arrSelectorOptions['selected'][$arrEntry['entryFields'][0]] = '<option  style="cursor: pointer;" value="'.$arrEntry['entryId'].'">'.$arrEntry['entryFields'][0].'</option>';
                    }
                }
                /*$arrSelectedList = array();
                foreach($objEntries->arrEntries as $intKey => $arrEntry) {
                    $arrSelectedList[$arrEntry['entryId']] = array(
                        'name'      => $arrEntry['entryFields'][0],
                        'class'     => "childCategory_".$intId."_".intval($arrEntry['entryId']),
                        'id'        => (in_array($intKey, $arrValue) ? '' : 'o')."childCategory".$intId."_".intval($arrEntry['entryId']),
                        'selected'  => intval(in_array($intKey, $arrValue)),
                        'edit'      => $intFormType == 13 ? 'javascript:editSelectedElement_'.$intId.'('.$arrEntry['entryId'].')' : ''
                    );
                }*/

                ksort($arrSelectorOptions['not_selected']);
                ksort($arrSelectorOptions['selected']);


                $strSelectorOptions = join("", $arrSelectorOptions['not_selected']).",".join("", $arrSelectorOptions['selected']);


                echo $strSelectorOptions;

                /*if($objInit->mode == 'frontend') {
                    //get user attributes
                    $intUserIsAdmin = $objUser->getAdminStatus();

                    $arrUserGroups  = array();
                    $objGroup = $objFWUser->objGroup->getGroups($filter = array('is_active' => true, 'type' => 'frontend'));

                    while (!$objGroup->EOF) {
                        if(in_array($objGroup->getId(), $objUser->getAssociatedGroupIds())) {
                            $arrUserGroups[] = $objGroup->getId();
                        }
                        $objGroup->next();
                    }

                    self::getCommunityGroups();
                    $bolAddNewAlowed = false;

                    foreach ($arrUserGroups as $intKey => $intGroupId) {
                        if($this->arrCommunityGroups[$intGroupId]['status_group'][$intFormType] == 1 && !$bolAddNewAlowed) {
                            $bolAddNewAlowed = true;
                        }
                    }

                    if($bolAddNewAlowed || (intval($intFormType) == 0) || $intUserIsAdmin == 1) {
                        $strEditFunction = 'ondblclick="editSelectedElement_'.$intId.'(this);"';
                    } else {
                        $strEditFunction = '';
                    }
                }

                echo $listElementsJSON = json_encode($arrSelectedList);*/

                die();

                break;
        }
    }



    //function saveInputfield($intInputfieldId, $strValue)
    function saveInputfield($intInputfieldId, $arrValue, $langId = 0)
    {
        //$strValue = join(',', array_map('intval', explode(',', $strValue)));
        $strValue = contrexx_strip_tags(contrexx_input2raw(join(",", $arrValue)));
        return $strValue;
    }


    function deleteContent($intEntryId, $intIputfieldId)
    {
        global $objDatabase;

        return (boolean)$objDatabase->Execute("
            DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
             WHERE `entry_id`='".intval($intEntryId)."'
               AND `field_id`='".intval($intIputfieldId)."'");
    }



    function getContent($intEntryId, $arrInputfield, $arrTranslationStatus)
    {
        global $_ARRAYLANG;

        $strValue = static::getRawData($intEntryId, $arrInputfield, $arrTranslationStatus);
        $langId = static::getOutputLocale()->getId();

        if(!empty($strValue)) {
            $strValue = strip_tags(htmlspecialchars($strValue, ENT_QUOTES, CONTREXX_CHARSET));

            //get seperator
            $strSeperator = ',';

            //explode links
            $arrRelationGroup = explode($strSeperator, $strValue);

            //open relation <ul> list
            $strList = '<ul class="mediadirInputfieldRellation_group">';

            //make relation elements
            foreach ($arrRelationGroup as $intRelationId) {
                $objEntry = new \Cx\Modules\MediaDir\Controller\MediaDirectoryEntry;
                $objEntry->getEntries($intRelationId);

                $strRelationValue = $objEntry->arrEntries[$intRelationId]['entryFields'][0];
                //$strRelationFormId = $objEntry->arrEntries[$intRelationId]['entryFormId'];

                //make hyperlink with <a> and <li> tag
// TODO: use MediaDirectoryEntry::getDetailUrl()
                //$strList .= '<li><a href="index.php?section='.$this->moduleName.'&amp;cmd='.$strDetailCmd.'&amp;eid='.$intRelationId.'" target="_blank">'.$strRelationValue.'</a></li>';
                $strList .= '<li>'.$strRelationValue.'</li>';


                ///////// CUSTOM ///////////
                $objRelationDefaultLang = $objDatabase->Execute("SELECT `lang_id` FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_entries WHERE id=".intval($intRelationId)." LIMIT 1");
                $intRelationDefaultLang = intval($objRelationDefaultLang->fields['lang_id']);

                if($this->arrSettings['settingsTranslationStatus'] == 1) {
		            if(in_array($langId, $arrTranslationStatus)) {
		                $intRelationLangId = $langId;
                    } else {
                        $intRelationLangId = $intRelationDefaultLang;
                    }
                } else {
		            $intRelationLangId = $langId;
                }
                $objRelationValues = $objDatabase->Execute("
                    SELECT
                        `value`, `field_id`
                    FROM
                        ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
                    WHERE
                        lang_id=".$intRelationLangId."
                    AND
                        entry_id=".$intRelationId."
                ");

                $arrRelationValues = array();
                if ($objRelationValues !== false) {
                    while (!$objRelationValues->EOF) {
                        $arrRelationValues[$objRelationValues->fields['field_id']] = htmlspecialchars($objRelationValues->fields['value'], ENT_QUOTES, CONTREXX_CHARSET);
                        $objRelationValues->MoveNext();
                    }
                }

                $strWeb = !empty($arrRelationValues[56]) ? '<a href="'.$arrRelationValues[56].'" target="_blank">'.$arrRelationValues[56].'</a>' : '';
                $arrUsers = array();
                $arrUsers = explode(',',$arrRelationValues[57]);

                $strUsers = '';
                foreach($arrUsers as $intUserId) {
                    $objUser = \FWUser::getFWUserObject()->objUser->getUser($intUserId);
                    if ($objUser) {
                      if($objUser->getProfileAttribute('firstname') != "" && $objUser->getProfileAttribute('lastname') != "") {
                         $strUsers .= htmlentities($objUser->getProfileAttribute('firstname').' '.$objUser->getProfileAttribute('lastname'), ENT_QUOTES, CONTREXX_CHARSET).'<br />';
                         $strUsers .= $objUser->getProfileAttribute('1') ? htmlentities($objUser->objAttribute->getById($objUser->getProfileAttribute('1'))->getName(), ENT_QUOTES, CONTREXX_CHARSET).'<br />' : '';
                         //$strUsers .= ($objUser->getProfileAttribute('address') != "") ? $objUser->getProfileAttribute('address').'<br />' : '';
                         //$strUsers .= ($objUser->getProfileAttribute('zip') != "" && $objUser->getProfileAttribute('city') != "") ? $objUser->getProfileAttribute('zip').' '.$objUser->getProfileAttribute('city').'<br />' : '';
                         //$strUsers .= ($objUser->getProfileAttribute('phone_office') != "") ? $objUser->getProfileAttribute('phone_office').'<br />' : '';
                         $strUsers .= '<a rel="shadowbox;player=iframe;width=700;height=650" href="teilnehmer_kontakt?13='.$objUser->getId().'&amp;14='.urlencode($objUser->getProfileAttribute('company').', '.$objUser->getProfileAttribute('firstname').' '.$objUser->getProfileAttribute('lastname')).'">'.$_ARRAYLANG['TXT_MEDIADIR_GET_IN_CONTACT'].'</a><br />';
                         $strUsers .= '<br />';
                      }
                   }
                }

                $strCustom .= '<table border="0" cellpadding="2">
                   <tr>
                        <td colspan="2"><h3>'.\FWUser::getFWUserObject()->objUser->objAttribute->getById('country_'.$arrRelationValues[54])->getName().'</h3></td>
                   <tr>
                       <td width="50%">'.$arrRelationValues[61].'<br />'.(!empty($arrRelationValues[49]) ? $arrRelationValues[49].'<br />' : '').$arrRelationValues[50].'<br />'.$arrRelationValues[51].' '.$arrRelationValues[53].'<br />'.\FWUser::getFWUserObject()->objUser->objAttribute->getById('country_'.$arrRelationValues[54])->getName().'<br />'.$strWeb.'<br /></td>
                       <td width="50%">'.$strUsers.'</td>
                   </tr>
                </table>';
                ///////// CUSTOM ///////////
            }

            //close relation </ul> list
            $strList .= '</ul>';
        }

        if(!empty($strValue)) {
            $arrContent['TXT_'.$this->moduleLangVar.'_INPUTFIELD_NAME'] = htmlspecialchars($arrInputfield['name'][0], ENT_QUOTES, CONTREXX_CHARSET);
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_VALUE'] = $strList;
            $arrContent[$this->moduleLangVar.'_INPUTFIELD_CUSTOM'] = $strCustom.'<br />';

        } else {
            $arrContent = null;
        }

        return $arrContent;
    }

    function getRawData($intEntryId, $arrInputfield, $arrTranslationStatus) {
        global $objDatabase;

        $intId = intval($arrInputfield['id']);
        $objInputfieldValue = $objDatabase->Execute("
            SELECT
                `value`
            FROM
                ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields
            WHERE
                field_id=".$intId."
            AND
                entry_id=".$intEntryId."
            LIMIT 1
        ");

        return $objInputfieldValue->fields['value'];
    }


    protected function getAddUrl($formId) {
        return $this->getSectionUrl('add', $formId);
    }

    protected function getEditUrl($formId) {
        return $this->getSectionUrl('edit', $formId);
    }

    protected function getSectionUrl($mode, $formId) {
        $langId = static::getOutputLocale()->getId();

        $pageRepo = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager()->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        // fetch form specific page (i.e. section=MediaDir&cmd=add3 / section=MediaDir&cmd=edit3)
        $page = $pageRepo->findOneByModuleCmdLang($this->moduleName, $mode.$formId, $langId);

        // fetch regular page (section=MediaDir&cmd=add / section=MediaDir&cmd=edit)
        if (!$page || !$page->isActive()) {
            $page = $pageRepo->findOneByModuleCmdLang($this->moduleName, $mode, $langId);
        }

        // abort in case the entry can't be linked to an existing page
        if (!$page || !$page->isActive()) {
            throw new MediaDirectoryInputfieldRelationGroupException();
        }

        // create url to the target page and add the entry's ID as argument
        $url = \Cx\Core\Routing\Url::fromPage($page);

        return $url;
    }


    function getJavascriptCheck()
    {
        $fieldName = $this->moduleNameLC."Inputfield_";
        $strJavascriptCheck = <<<EOF

            case 'relation_group':
                name =  inputFields[field][0];
                value = document.getElementById('$fieldName' + field).value;
                if (value == "" && isRequiredGlobal(inputFields[field][1], value)) {
                    isOk = false;
                    document.getElementById('$fieldName' + field).style.border = "#ff0000 1px solid";
                } else {
                    document.getElementById('$fieldName' + field).style.borderColor = '';
                }
                break;

EOF;
        return $strJavascriptCheck;
    }



    function getFormOnSubmit($intInputfieldId)
    {
        //return $this->moduleName.'Inputfield_'.$intInputfieldId.'_SelectorSerialize(); ';
        return "selectAll(document.entryModfyForm.elements['".$this->moduleNameLC."Inputfield[".$intInputfieldId."][]']); ";
    }
}
