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
 * Member directory manager
 * @copyright   CLOUDREXX CMS - 2005 CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     v1.0.0
 * @uses        ImportExport
 * @package     cloudrexx
 * @subpackage  module_memberdir
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\MemberDir\Controller;

$_ARRAYLANG['TXT_ALL_LANGUAGES'] = "Alle Sprachen";


/**
 * Member directory manager
 *
 * For managing the member directory
 * @copyright   CLOUDREXX CMS - 2005 CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     v1.0.0
 * @uses        ImportExport
 * @package     cloudrexx
 * @subpackage  module_memberdir
 */
class MemberDirManager extends MemberDirLibrary
{
    var $_objTpl;
    var $pageTitle='';
    var $statusMessage='';
    var $imagePath;
    var $langId;
    var $okMessage='';
    //var $arrSettings = array();

    private $act = '';

    /**
     * Constructor
     *
     * Initializes the template system and other variables and
     * sets the CONTENT_NAVIGATION
     * @global ADONewConnection
     * @global array
     * @global \Cx\Core\Html\Sigma
     * @global InitCMS
     * @see MemberDirLibrary::__construct(), \Cx\Core\Html\Sigma::setErrorHandling(), imagePath, langId
     */
    function __construct()
    {
        global $objInit;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/MemberDir/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);

        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->imagePath = ASCMS_MODULE_WEB_PATH.'/MemberDir/View/Media';
        $this->langId=$objInit->userFrontendLangId;

        parent::__construct();

    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable("CONTENT_NAVIGATION","
            <a href=\"index.php?cmd=MemberDir\" class='".(($this->act == '' || $this->act == 'showdir') ? 'active' : '')."'>".$_ARRAYLANG['TXT_OVERVIEW']."</a>
            <a href=\"index.php?cmd=MemberDir&amp;act=newDir\" class='".($this->act == 'newDir' ? 'active' : '')."'>".$_ARRAYLANG['TXT_NEW_DIR']."</a>
            <a href=\"index.php?cmd=MemberDir&amp;act=new\" class='".($this->act == 'new' ? 'active' : '')."'>".$_ARRAYLANG['TXT_NEW_MEMBER_SHORT']."</a>
            <a href=\"index.php?cmd=MemberDir&amp;act=import\" class='".($this->act == 'import' ? 'active' : '')."'>".$_ARRAYLANG['TXT_IMPORT']."</a>
            <a href=\"index.php?cmd=MemberDir&amp;act=export&amp;everything=1\" class='".($this->act == 'export' ? 'active' : '')."'>".$_ARRAYLANG['TXT_DOWNLOAD']."</a>
            <a href=\"index.php?cmd=MemberDir&amp;act=settings\" class='".($this->act == 'settings' ? 'active' : '')."'>".$_ARRAYLANG['TXT_SETTINGS']."</a>");
    }

    /**
    * Gets the requested methods
    *
    * @global   array
    * @global   array
    * @global   \Cx\Core\Html\Sigma
    * @access public
    */
    function getPage()
    {
        global $_ARRAYLANG, $objTemplate;

        if(!isset($_GET['act'])){
            $_GET['act']="";
        }

        switch($_GET['act']){
            case "show":
                $this->_showMember();
                break;
            case "new":
                $this->_newMember();
                break;
            case "saveNew":
                $this->_saveNew();
                break;
            case "editMember":
                $this->_editMember();
                break;
            case "saveEditedMember":
                $this->_saveEditedMember();
                break;
            case "deleteMember":
                $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
                if ($this->_deleteEntry($id)) {
                    $this->okMessage = $_ARRAYLANG['TXT_DATABASE_SUCESSFUL'];
                } else {
                    $this->statusMessage = $_ARRAYLANG['TXT_DELETE_ERROR'];
                }
                $_GET['id'] = isset($_GET['dirid']) ? $_GET['dirid'] : 0;
                $this->_overviewDir();
                break;
            case "multiMemberDelete":
                $this->_multiMemberDelete();
                $this->_overviewDir();
                break;
            case "deletePic":
                $this->_deletePic();
                $this->_showMember();
                break;
            case "deleteDir":
                $id = intval($_GET['id']);
                if ($this->_deleteDir($id)) {
                    $this->okMessage = $_ARRAYLANG['TXT_DATABASE_SUCESSFUL'];
                } else {
                    $this->statusMessage = $_ARRAYLANG['TXT_DELETE_ERROR'];
                }
                $this->setDirs();
                $this->_overview();
                break;
            case "multiDirDelete":
                $this->_multiDirDelete();
                $this->setDirs();
                $this->_overview();
                break;
            case "export":
                $this->_export();
                break;
            case "import":
                $this->_import();
                break;
            case "settings":
                $this->settings();
                break;
            case "showdir":
                $this->_overviewDir();
                break;
            case "editDir":
                $this->_editDir();
                break;
            case "saveEditedDir":
                $this->_saveEditedDir();
                $this->setDirs();
                $this->_overview();
                break;
            case "saveCopyDir":
                $this->_saveCopyDir();
                $this->setDirs();
                $this->_overview();
                break;

            case "copy":
                $this->_editDir(true);
                break;
            case "newDir":
                $this->_editDir();
                break;
            case "saveNewDir":
                $this->_saveNewDir();
                $this->setDirs();
                $this->_overview();
                break;
            case "activate":
                $this->_activate();
                $this->setDirs();
                $this->_overview();
                break;
            case 'exportvcf':
                $this->_exportVCard( intval($_GET['id']) );
                break;

            default:
                $this->_overview();
                break;
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE' => $this->pageTitle,
            'CONTENT_STATUS_MESSAGE' => $this->statusMessage,
            'CONTENT_OK_MESSAGE'     => $this->okMessage,
            'ADMIN_CONTENT' => $this->_objTpl->get()
        ));

        $this->act = isset($_REQUEST['act']) ? $_REQUEST['act'] : '';
        $this->setNavigation();
    }

    /**
     * Overview over all dirs
     *
     * @global array
     * @global ADONewConnection
     * @access private
     */
    function _overview()
    {
        global $_ARRAYLANG, $objDatabase;

        $this->_objTpl->loadTemplateFile('module_memberdir_overview.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_OVERVIEW'];

        $this->_objTpl->setGlobalVariable(array(
            "TXT_CONFIRM_DELETE_DATA"    => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
            "TXT_ACTION_IS_IRREVERSIBLE" => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            "TXT_DELETE_CATEGORY_ALL"    => $_ARRAYLANG['TXT_DELETE_CATEGORY_ALL'],
            "TXT_MANAGE_ENTRIES"         => $_ARRAYLANG['TXT_OVERVIEW'],
            "TXT_SUBMIT_SELECT"          => $_ARRAYLANG['TXT_SUBMIT_SELECT'],
            "TXT_SUBMIT_DELETE"          => $_ARRAYLANG['TXT_SUBMIT_DELETE'],
            "TXT_SUBMIT_EXPORT"          => $_ARRAYLANG['TXT_SUBMIT_EXPORT'],
            "TXT_SELECT_ALL"             => $_ARRAYLANG['TXT_SELECT_ALL'],
            "TXT_DESELECT_ALL"           => $_ARRAYLANG['TXT_DESELECT_ALL'],
            "TXT_NAME"                   => $_ARRAYLANG['TXT_NAME'],
            "TXT_ACTIONS"                => $_ARRAYLANG['TXT_ACTION'],
            "TXT_DESCRIPTION"            => $_ARRAYLANG['TXT_DESCRIPTION'],
            "TXT_ENTRIES"                => $_ARRAYLANG['TXT_ENTRIES'],
            "TXT_FILTER"                 => $_ARRAYLANG['TXT_FILTER'],
            "DIRECTORY_LIST"             => $this->dirList('id', null, 100),
            "TXT_SEARCH"                 => $_ARRAYLANG['TXT_SEARCH'],
            "TXT_KEYWORD"                => (empty($_GET['keyword'])) ? "" : $_GET['keyword'],
            "TXT_STATUS"                 => $_ARRAYLANG['TXT_STATUS'],
            "TXT_CSV_FILE"               => $_ARRAYLANG['TXT_CSV_FILE'],
            "TXT_DOWNLOAD"               => $_ARRAYLANG['TXT_DOWNLOAD'],
            "TXT_ID"                     => $_ARRAYLANG['TXT_ID'],
            "TXT_DOWNLOAD_QUESTION"      => $_ARRAYLANG['TXT_DOWNLOAD_QUESTION'],
            'TXT_MEMBERDIR_LANGUAGE'     => $_ARRAYLANG['TXT_MEMBERDIR_LANGUAGE']
        ));

        $rowid = 2;

        foreach ($this->directories as $key => $value) {
            $query = "SELECT id FROM ".DBPREFIX."module_memberdir_values
                      WHERE dirid = '".$key."'";
            $objResult = $objDatabase->Execute($query);
            $entryCount = $objResult->RecordCount();

            $margin = 0;
            for ($i = 1; $i <= $value['level']; $i++) {
                $margin+=20;
            }

            $parentdir = $value['parentdir'];
            $parentdirlist = "";
            while ($parentdir > 0)  {
                $parentdirlist = "p".$parentdir . " " . $parentdirlist;
                $parentdir = $this->directories[$parentdir]['parentdir'];
            }


            if ($value['lang'] == 0) {
                $lang = $_ARRAYLANG['TXT_ALL_LANGUAGES'];
            } else {
                $lang =  \FWLanguage::getLanguageParameter($value['lang'], 'lang');
            }
            $this->_objTpl->setVariable(array(
                "MEMBERDIR_DIRID"           => $key,
                "MEMBERDIR_DIRNAME"         => $value['name'],
                "MEMBERDIR_ROW"             => "row".$rowid,
                "MEMBERDIR_DESC"            => nl2br($value['description']),
                "MEMBERDIR_ENTRYCOUNT"      => $entryCount,
                "MEMBERDIR_ACTIVE"          => ($value['active']) ? "green" : "red",
                "MEMBERDIR_MARGIN"          => $margin,
                "MEMBERDIR_PARENTS"         => $parentdirlist,
                "MEMBERDIR_DISPLAY"         => ($value['parentdir'] == 0) ? "table-row" : "none",
                "MEMBERDIR_HAS_CHILDREN"    => $value['has_children'],
                "MEMBERDIR_LEVEL"           => $value['level'],
                'MEMBERDIR_LANGUAGE'        => htmlentities(\FWLanguage::getLanguageParameter($value['lang'], 'name'), ENT_QUOTES, CONTREXX_CHARSET).' ('.$lang.')'
            ));

            if ($value['has_children']) {
                $this->_objTpl->touchBlock("treeimg");
                $this->_objTpl->parse("treeimg");
            } else {
                $this->_objTpl->hideBlock("treeimg");
                $this->_objTpl->touchBlock("spacer");
                $this->_objTpl->parse("spacer");
            }

            $this->_objTpl->setVariable(array(
                "MEMBERDIR_DIRID"       => $key,
            ));

            $this->_objTpl->parse("dirlist_row");

            $rowid = ($rowid == 2) ? 1 : 2;
        }
    }

    /**
     * Create new directory
     *
     * Shows the dialog to create a new directory
     * @access private
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     */
    /*function _newDir()
    {
        global $_ARRAYLANG, $objDatabase;

        $this->_objTpl->loadTemplateFile('module_memberdir_modifyDir.html');
        $this->pageTitle = $_ARRAYLANG['TXT_NEW_DIR'];

        $select = '<select name="parentdir" style="width:300px;">';
        $select .= '<option value="0">'.$_ARRAYLANG['TXT_MAIN_DIR'].'</option>';

        foreach ($this->directories as $dirid => $directory) {
            $prefix = '';
            for ($i=1; $i<=$directory['level']; $i++) {
                $prefix .= '...';
            }
            $select .= '<option value="'.$dirid.'">'.$prefix.$directory['name'].'</option>';
        }
        $select .= '</select>';

        $names = explode(';', $_ARRAYLANG['TXT_FIELD_DEFAULT_NAMES']);

        $this->_objTpl->setVariable(array(
            'TXT_PIC_UPLOAD'                => $_ARRAYLANG['TXT_PIC_UPLOAD'],
            'TXT_TITLE'                     => $_ARRAYLANG['TXT_NEW_DIR'],
            'TXT_ACTIVE'                    => $_ARRAYLANG['TXT_ACTIVE'],
            'TXT_NAME'                      => $_ARRAYLANG['TXT_NAME'],
            'TXT_SAVE'                      => $_ARRAYLANG['TXT_SAVE'],
            'TXT_FIELDS'                    => $_ARRAYLANG['TXT_FIELDS'],
            'TXT_SORT'                      => $_ARRAYLANG['TXT_SORT'],
            'TXT_DIR_NAME'                  => $_ARRAYLANG['TXT_MEMBERDIR_NAME'],
            'TXT_DESCRIPTION'               => $_ARRAYLANG['TXT_DESCRIPTION'],
            'TXT_DISPLAY_MODE'              => $_ARRAYLANG['TXT_DISPLAY_MODE'],
            'TXT_MEMBERDIR_USER_DEFINED'    => $_ARRAYLANG['TXT_MEMBERDIR_USER_DEFINED'],
            'TXT_PARENT_DIR'                => $_ARRAYLANG['TXT_PARENT_DIR'],
            'TXT_DISPLAY_MODE_BOTH'         => $_ARRAYLANG['TXT_DISPLAY_MODE_BOTH'],
            'TXT_DISPLAY_MODE_DIR_ONLY'     => $_ARRAYLANG['TXT_DISPLAY_MODE_DIR_ONLY'],
            'TXT_DISPLAY_MODE_ENTRIES_ONLY' => $_ARRAYLANG['TXT_DISPLAY_MODE_ENTRIES_ONLY'],
            'TXT_ONE_ROW'                   => $_ARRAYLANG['TXT_ONE_ROW'],
            'TXT_MORE_ROW'                  => $_ARRAYLANG['TXT_MORE_ROW'],
            'TXT_TYPE'                      => $_ARRAYLANG['TXT_ROW_TYPE'],
            'TXT_MEMBERDIR_LANGUAGE'        => $_ARRAYLANG['TXT_MEMBERDIR_LANGUAGE']
        ));

        $this->_objTpl->setVariable(array(
            'MODE_0_CHECKED' => 'checked',
            'PARENT_DIRLIST' => $select,
            'MEMBERDIR_SELECTED_SORT'   => 0,
            'MEMBERDIR_FIELD_NAME_1'    => $names[0],
            'MEMBERDIR_FIELD_NAME_2'    => $names[1],
            'MEMBERDIR_FIELD_NAME_3'    => $names[2],
            'MEMBERDIR_FIELD_NAME_4'    => $names[3],
            'MEMBERDIR_FIELD_NAME_5'    => $names[4],
            'MEMBERDIR_FIELD_NAME_6'    => $names[5],
            'MEMBERDIR_FIELD_NAME_7'    => $names[6],
            'MEMBERDIR_FIELD_NAME_8'    => $names[7],
            'MEMBERDIR_FIELD_NAME_9'    => $names[8],
            'MEMBERDIR_FIELD_NAME_10'    => $names[9],
            'MEMBERDIR_FIELD_NAME_11'    => $names[10],
            'MEMBERDIR_FIELD_NAME_12'    => $names[11],
            'MEMBERDIR_FIELD_ACTIVE_1'  => 'checked="checked"',
            'MEMBERDIR_FIELD_ACTIVE_2'  => 'checked="checked"',
            'MEMBERDIR_FIELD_ACTIVE_3'  => 'checked="checked"',
            'MEMBERDIR_FIELD_ACTIVE_4'  => 'checked="checked"',
            'MEMBERDIR_FIELD_ACTIVE_5'  => 'checked="checked"',
            'MEMBERDIR_FIELD_ACTIVE_6'  => 'checked="checked"',
            'MEMBERDIR_FIELD_ACTIVE_7'  => 'checked="checked"',
            'MEMBERDIR_FIELD_ACTIVE_8'  => 'checked="checked"',
            'MEMBERDIR_FIELD_ACTIVE_9'  => 'checked="checked"',
            'MEMBERDIR_FIELD_ACTIVE_10' => 'checked="checked"',
            'MEMBERDIR_FIELD_ACTIVE_11' => 'checked="checked"',
            'MEMBERDIR_FIELD_ACTIVE_12' => 'checked="checked"',
            'MEMBERDIR_ACTION'          => '?cmd=MemberDir&amp;act=saveNewDir',
            'MEMBERDIR_LANGUAGE_MENU'   => $this->_getLanguageMenu('name="memberdirLangId" size="1"')
        ));
    }*/

    /**
     * Gets a language menu
     *
     * Generates a menu with all available languages in the system.
     * The param $attrs can be used to define some attributes of the
     * select html tag.
     * $selectedLangId specifies the option that will be pre selected.
     *
     * @param string $attrs
     * @param integer $selectedLangId
     * @return string Generated language menu
     */
    function _getLanguageMenu($attrs, $selectedLangId = 0)
    {
        global $_ARRAYLANG;

        $menu = '<select '.$attrs.'>';
        $menu .= '<option value="0">'.$_ARRAYLANG['TXT_ALL_LANGUAGES'].'</option>';
        foreach (\FWLanguage::getLanguageArray() as $langId => $arrLanguage) {
            $menu .= '<option value="'.$langId.'"'.(($langId == $selectedLangId) ? ' selected="selected"' : '').'>'.htmlentities($arrLanguage['name'], ENT_QUOTES, CONTREXX_CHARSET).' ('.$arrLanguage['lang'].')</option>';
        }
        $menu .= '</select>';

        return $menu;
    }

    /**
     * Save New Dir
     *
     * @access private
     * @global array
     * @global ADONewConnection
     * @global FWLanguage
     */
    function _saveNewDir()
    {
        global $_ARRAYLANG, $objDatabase;

        $name = (!empty($_POST['dir_name'])) ? contrexx_addslashes($_POST['dir_name']) : "directory";
        $description = contrexx_addslashes($_POST['dir_desc']);
        $parentdir = contrexx_addslashes($_POST['parentdir']);
        $displaymode = (empty($_POST['displaymode'])) ? 0 : intval($_POST['displaymode']);
        $sort = intval($_POST['sortSelection']);
        $langId = isset($_POST['memberdirLangId']) ? intval($_POST['memberdirLangId']) : 0;

        $arrLanguage = \FWLanguage::getLanguageArray();
        if (!in_array($langId, array_keys($arrLanguage)) && $langId != 0) {
            $langId = \FWLanguage::defaultLanguageId;
        }

        $query = "INSERT INTO ".DBPREFIX."module_memberdir_directories
                  (`name`, `description`, `parentdir`, `displaymode`,  `sort`, `pic1`, `pic2`, `lang_id`)
                  VALUES
                  ('$name', '$description', '$parentdir', '$displaymode', ".$sort.",
                   '" .((empty($_POST['field_active_pic1'])) ? "0" : "1") . "',
                   '" .((empty($_POST['field_active_pic2'])) ? "0" : "1") . "',
                   ".$langId.")";
        $objDatabase->Execute($query);

        $dirid = $objDatabase->Insert_ID();
        $this->_saveEditedDir($dirid);

        $this->okMessage = $_ARRAYLANG['TXT_DATABASE_SUCESSFUL'];

        return $dirid;
    }




    /**
     * Copy directory
     *
     * Copy a directory and show the modify dialog of the copied directory
     * @access private
     * @global ADONewConnection
     * @global array
     * @global FWLanguage
     */
    function _saveCopyDir()
    {
        global $objDatabase, $_ARRAYLANG;

        $dirid = $this->_saveNewDir();
        $copydirid = intval($_GET['id']);
        $langId = isset($_POST['memberdirLangId']) ? intval($_POST['memberdirLangId']) : 0;

        $arrLanguage = \FWLanguage::getLanguageArray();
        if (!in_array($langId, array_keys($arrLanguage))) {
            $langId = \FWLanguage::getDefaultLangId();
        }

        /*
         * Copy the values
         */
        $query = "SELECT `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`,
                  `10`, `11`, `12`, `13`, `14`, `15`, `16`, `17`, `18` FROM
                  ".DBPREFIX."module_memberdir_values
                  WHERE dirid='".$copydirid."'";
        $objResult = $objDatabase->Execute($query);

        if ($objResult) {
            while (!$objResult->EOF) {
                $query = "INSERT INTO ".DBPREFIX."module_memberdir_values
                         (`dirid`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`,
                          `10`, `11`, `12`, `13`, `14`, `15`, `16`, `17`, `18`, `lang_id`) VALUES
                          ('".$dirid."',
                           '".$objResult->fields['1']."',
                           '".$objResult->fields['2']."',
                           '".$objResult->fields['3']."',
                           '".$objResult->fields['4']."',
                           '".$objResult->fields['5']."',
                           '".$objResult->fields['6']."',
                           '".$objResult->fields['7']."',
                           '".$objResult->fields['8']."',
                           '".$objResult->fields['9']."',
                           '".$objResult->fields['10']."',
                           '".$objResult->fields['11']."',
                           '".$objResult->fields['12']."',
                           '".$objResult->fields['13']."',
                           '".$objResult->fields['14']."',
                           '".$objResult->fields['15']."',
                           '".$objResult->fields['16']."',
                           '".$objResult->fields['17']."',
                           '".$objResult->fields['18']."',
                           '".$langId."')";
                $objDatabase->Execute($query);

                $objResult->MoveNext();
            }
        }
    }

    function _getDirectoryMenu($attrs, $selectedDirId)
    {
        global $_ARRAYLANG;

        $menu = '<select '.$attrs.'>';
        $menu .= '<option value="0">'.$_ARRAYLANG['TXT_MAIN_DIR'].'</option>';

        foreach ($this->directories as $id => $directory) {
            $prefix = '';
            for ($i=1; $i<=$directory['level']; $i++) {
                $prefix .= '...';
            }
            $parentDir = isset($this->directories[$selectedDirId])
                ? $this->directories[$selectedDirId]['parentdir'] : 0;
            $menu .= '<option value="'.$id.'"'.($id == $parentDir ? ' selected="selected"' : '').'>'.$prefix.htmlentities($directory['name'], ENT_QUOTES, CONTREXX_CHARSET).'</option>';
        }
        $menu .= '</select>';

        return $menu;
    }

    /**
     * Modify Dir
     *
     * @access private
     * @global array
     * @global ADONewConnection
     * @global FWLanguage
     */
    function _editDir($copy = false)
    {
        global $_ARRAYLANG, $objDatabase;

        $dirid = !empty($_GET['id']) ? intval($_GET['id']) : 0;
        if (isset($this->directories[$dirid])) {
            $displayMode = $this->directories[$dirid]['displaymode'];
            $name = $this->directories[$dirid]['name'];
            $description = $this->directories[$dirid]['description'];
            $sort = $this->directories[$dirid]['sort'];
            $lang = $this->directories[$dirid]['lang'];
            $pic1 = $this->directories[$dirid]['pic1'];
            $pic2 = $this->directories[$dirid]['pic2'];
        } else {
            $dirid = 0;
            $displayMode = 0;
            $name = '';
            $description = '';
            $sort = 0;
            $lang = \FWLanguage::getDefaultLangId();
            $pic1 = '';
            $pic2 = '';
        }

        $this->_objTpl->loadTemplateFile('module_memberdir_modifyDir.html');
        $this->pageTitle = $dirid > 0 ? ($copy ? $_ARRAYLANG['TXT_MEMBERDIR_COPY_DIR'] : $_ARRAYLANG['TXT_EDIT_DIR']) : $_ARRAYLANG['TXT_NEW_DIR'];

        $this->_objTpl->setVariable(array(
            'TXT_PIC_UPLOAD'                => $_ARRAYLANG['TXT_PIC_UPLOAD'],
            'TXT_TITLE'                     => $dirid > 0 ? ($copy ? $_ARRAYLANG['TXT_MEMBERDIR_COPY_DIR'] : $_ARRAYLANG['TXT_EDIT_DIR']) : $_ARRAYLANG['TXT_NEW_DIR'],
            'TXT_ACTIVE'                    => $_ARRAYLANG['TXT_ACTIVE'],
            'TXT_NAME'                      => $_ARRAYLANG['TXT_NAME'],
            'TXT_TYPE'                      => $_ARRAYLANG['TXT_ROW_TYPE'],
            'TXT_SAVE'                      => $_ARRAYLANG['TXT_SAVE'],
            'TXT_FIELDS'                    => $_ARRAYLANG['TXT_FIELDS'],
            'TXT_DIR_NAME'                  => $_ARRAYLANG['TXT_MEMBERDIR_NAME'],
            'TXT_MORE_ROW'                  => $_ARRAYLANG['TXT_MORE_ROW'],
            'TXT_ONE_ROW'                   => $_ARRAYLANG['TXT_ONE_ROW'],
            'TXT_DESCRIPTION'               => $_ARRAYLANG['TXT_DESCRIPTION'],
            'TXT_PARENT_DIR'                => $_ARRAYLANG['TXT_PARENT_DIR'],
            'TXT_SORT'                      => $_ARRAYLANG['TXT_SORT'],
            'TXT_MEMBERDIR_USER_DEFINED'    => $_ARRAYLANG['TXT_MEMBERDIR_USER_DEFINED'],
            'TXT_DISPLAY_MODE_BOTH'         => $_ARRAYLANG['TXT_DISPLAY_MODE_BOTH'],
            'TXT_DISPLAY_MODE_DIR_ONLY'     => $_ARRAYLANG['TXT_DISPLAY_MODE_DIR_ONLY'],
            'TXT_DISPLAY_MODE_ENTRIES_ONLY' => $_ARRAYLANG['TXT_DISPLAY_MODE_ENTRIES_ONLY'],
            'TXT_DISPLAY_MODE'              => $_ARRAYLANG['TXT_DISPLAY_MODE'],
            'TXT_MEMBERDIR_LANGUAGE'        => $_ARRAYLANG['TXT_MEMBERDIR_LANGUAGE']
        ));

        $this->_objTpl->setVariable(array(
            'MODE_'.$displayMode.'_CHECKED' => 'checked="checked"',
            'PARENT_DIRLIST'                => $this->_getDirectoryMenu('name="parentdir" size="1" style="width:300px;"', $dirid),
            'MEMBERDIR_ACTION'              => $dirid ? ('?cmd=MemberDir&amp;act=save'.($copy ? 'Copy' : 'Edited').'Dir&amp;id='.$dirid) : '?cmd=MemberDir&amp;act=saveNewDir',
            'MEMBERDIR_DIR_NAME'            => htmlentities($name, ENT_QUOTES, CONTREXX_CHARSET),
            'MEMBERDIR_DESCRIPTION'         => htmlentities($description, ENT_QUOTES, CONTREXX_CHARSET),
            'MEMBERDIR_SELECTED_SORT'       => $sort,
            'MEMBERDIR_LANGUAGE_MENU'       => $this->_getLanguageMenu('name="memberdirLangId" size="1" style="width:300px;"', $lang),
            'MEMBERDIR_FIELD_PIC_1'         => $pic1 ? 'checked="checked"' : '',
            'MEMBERDIR_FIELD_PIC_2'         => $pic2 ? 'checked="checked"' : ''
        ));

        if ($dirid) {
            $query = 'SELECT `field`, `dirid`, `name`, `active` FROM `'.DBPREFIX.'module_memberdir_name` WHERE `dirid` = '.$dirid;
            $objResult = $objDatabase->Execute($query);

            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    $this->_objTpl->setVariable(array(
                        'MEMBERDIR_FIELD_ACTIVE_'.$objResult->fields['field']   => ($objResult->fields['active'] == '1') ? 'checked="checked"' : '',
                        'MEMBERDIR_FIELD_NAME_'.$objResult->fields['field']     => htmlentities($objResult->fields['name'], ENT_QUOTES, CONTREXX_CHARSET)
                    ));

                    $objResult->MoveNext();
                }
            } else {
                $this->statusMessage = $_ARRAYLANG['TXT_DATABASE_READ_ERROR'];
            }
        } else {
            $arrFieldNames = explode(';', $_ARRAYLANG['TXT_FIELD_DEFAULT_NAMES']);

            foreach ($arrFieldNames as $fieldId => $fieldName) {
                $this->_objTpl->setVariable(array(
                    'MEMBERDIR_FIELD_NAME_'.($fieldId+1)    => $fieldName,
                    'MEMBERDIR_FIELD_ACTIVE_'.($fieldId+1)  => 'checked="checked"'
                ));
            }
        }
    }

    /**
     * Save modified member
     *
     * @access private
     * @global ADONewConnection
     * @global array
     * @global FWLanguage
     * @param int $dirid Id of the directory
     */
    function _saveEditedDir($dirid = null)
    {
        global $objDatabase, $_ARRAYLANG;

        if (!isset($_GET['id'])) {
            $_GET['id'] = 0;
        }
        $dirid = (empty($dirid)) ? intval($_GET['id']) : $dirid;

        $query = "SELECT field FROM ".DBPREFIX."module_memberdir_name
                  WHERE dirid = '$dirid'";

        $objResult = $objDatabase->Execute($query);

        if ($objResult) {
            if ($objResult->RecordCount() < 18) {
                $this->initDir($dirid);
            }
        } else {
            echo $objDatabase->ErrorMsg();
        }

        $error = false;
        for ($i=1; $i<=18; $i++) {
            if (!isset($_POST['field_name_' . $i])) {
                $error = true;
                break;
            }
            $name = $_POST['field_name_'.$i];
            $active = (!empty($_POST['field_active_'.$i])) ? 1 : 0;
            $query = "UPDATE ".DBPREFIX."module_memberdir_name SET
                      name= '$name', active='$active'
                      WHERE field = '$i' AND dirid='$dirid'";
            if (!$objDatabase->Execute($query)) {
                $error = true;
            }
        }

        if (isset($_POST['dir_name'])) {
            $name = contrexx_addslashes($_POST['dir_name']);
            $description = contrexx_addslashes($_POST['dir_desc']);
            $parentdir = contrexx_addslashes($_POST['parentdir']);
            $parentdir = ($parentdir == $dirid) ? $this->directories[$dirid]['parentdir'] : $parentdir;
            $displaymode = contrexx_addslashes($_POST['displaymode']);
            $sort = intval($_POST['sortSelection']);
            $lang_keys = array_keys(\FWLanguage::getLanguageArray());
            $langId = (in_array(intval($_POST['memberdirLangId']), $lang_keys) || $_POST['memberdirLangId'] == 0)
                ? intval($_POST['memberdirLangId']) : $lang_keys[0];
            $query = "UPDATE ".DBPREFIX."module_memberdir_directories
                    SET `name` = '$name',
                    `description` = '$description',
                    `parentdir`    = '$parentdir',
                    `displaymode` = '$displaymode',
                    `sort` = ".$sort.",
                    `pic1` = '" .((empty($_POST['field_active_pic1'])) ? "0" : "1") . "',
                    `pic2` = '" .((empty($_POST['field_active_pic2'])) ? "0" : "1") . "',
                    `lang_id` = ".$langId."
                    WHERE dirid = '$dirid'";
            if (!$objDatabase->Execute($query)) {
                $error = true;
            }
        }

        if ($error) {
            $this->statusMessage = $_ARRAYLANG['TXT_DATABASE_WRITE_ERROR'];
        }
    }

    /**
     * Delete a directory
     *
     * @access private
     * @global ADONewConnection
     */
    function _deleteDir($id=null)
    {
        global $objDatabase;

        $dirid = (empty($id)) ? $_GET['id'] : $id;

        if ($this->directories[$dirid]['has_children']) {
            foreach ($this->directories as $key => $dir) {
                if ($dir['parentdir'] == $dirid) {
                    $this->_deleteDir($key);
                }
            }
        }

        $query = "DELETE FROM ".DBPREFIX."module_memberdir_directories
                  WHERE dirid = '$dirid'";
        if ($objDatabase->Execute($query)) {
            $query = "DELETE FROM ".DBPREFIX."module_memberdir_name
                  WHERE dirid = '$dirid'";
            $objDatabase->Execute($query);

            $query = "DELETE FROM ".DBPREFIX."module_memberdir_values
                WHERE dirid = '$dirid'";
            $objDatabase->Execute($query);

            return true;
        } else {
            echo $objDatabase->ErrorMsg();
            return false;
        }
    }

    /**
     * Multiple Dir Deletetion
     *
     * @global ADONewConnection
     * @global array
     * @access private
     */
    function _multiDirDelete()
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_POST['selectedId'])) {
            $error = false;
            foreach ($_POST['selectedId'] as $intCatId) {
                if (!$this->_deleteDir($intCatId)) {
                        $error = true;
                }
            }
            if ($error) {
                $this->statusMessage = $_ARRAYLANG['TXT_DELETE_ERROR'];
            } else {
                $this->okMessage = $_ARRAYLANG['TXT_DATABASE_SUCESSFUL'];
            }
        }
    }

    /**
    * Overview over a dir
    *
    * @global ADONewConnection
    * @global array
    * @global array
    * @param int $highlight The entry which shall be shown green
    * @access private
    */
    function _overviewDir($highlight = null)
    {
        global $objDatabase,  $_ARRAYLANG, $_CONFIG;

        $this->_objTpl->loadTemplateFile('module_memberdir_overviewDir.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_OVERVIEW'];

        $dirid = (isset($_GET['id'])) ? $_GET['id'] : "";

        if (isset($_POST['memberdir_update_sorting'])) {
            if (!empty($_POST['userDefinedSortNumber']) && is_array($_POST['userDefinedSortNumber'])) {
                foreach ($_POST['userDefinedSortNumber'] as $fieldId => $fieldSortNumber) {
                    $objDatabase->Execute("UPDATE ".DBPREFIX."module_memberdir_values SET `0` = ".intval($fieldSortNumber)." WHERE id = ".intval($fieldId));
                }
            }
        }

        $fieldnames = $this->getFieldData($dirid);

        $this->_objTpl->setGlobalVariable(array(
            "TXT_CONFIRM_DELETE_DATA"   => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
            'TXT_MEMBERDIR_EXPORT_CONTACT_AS_VCARD' => $_ARRAYLANG['TXT_MEMBERDIR_EXPORT_CONTACT_AS_VCARD'],
            "TXT_ACTION_IS_IRREVERSIBLE" => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            "TXT_DELETE_CATEGORY_ALL"   => $_ARRAYLANG['TXT_DELETE_CATEGORY_ALL'],
            "TXT_MANAGE_ENTRIES"        => $_ARRAYLANG['TXT_OVERVIEW'].": ".$this->directories[$dirid]['name'],
            "TXT_ID"                    => $_ARRAYLANG['TXT_MEMBERDIR_ID'],
            "TXT_ACTION"                => $_ARRAYLANG['TXT_ACTION'],
            "TXT_SELECT_ALL"            => $_ARRAYLANG['TXT_SELECT_ALL'],
            "TXT_DESELECT_ALL"          => $_ARRAYLANG['TXT_DESELECT_ALL'],
            "TXT_SUBMIT_SELECT"         => $_ARRAYLANG['TXT_SUBMIT_SELECT'],
            "TXT_SUBMIT_DELETE"         => $_ARRAYLANG['TXT_SUBMIT_DELETE'],
            "TXT_SUBMIT_EXPORT"         => $_ARRAYLANG['TXT_SUBMIT_EXPORT'],
            "TXT_LOCATION"              => $_ARRAYLANG['TXT_LOCATION'],
            "TXT_FILTER"                => $_ARRAYLANG['TXT_FILTER'],
            'TXT_MEMBERDIR_SORTING'     => $_ARRAYLANG['TXT_MEMBERDIR_SORTING'],
            "MEMBERDIR_CHARLIST"        => $this->_getCharList("?cmd=MemberDir&amp;act=showdir&amp;id=".$dirid),
            "DIRECTORY_LIST"            => $this->dirList('id', $dirid, 100),
            "TXT_SEARCH"                => $_ARRAYLANG['TXT_SEARCH'],
            "TXT_KEYWORD"               => (empty($_GET['keyword'])) ? $_ARRAYLANG['TXT_KEYWORD'] : $_GET['keyword'],
            "DIRID"                     => $dirid
        ));

        for ($i=1; $i<=3; $i++) {
            $index = $i;
            while ($fieldnames[$index]['active'] == 0 && $index<17) {
                $index++;
            }

            $this->_objTpl->setVariable(array(
                        "TXT_FIELD_".$i         => $fieldnames[$index]['name'],
            ));

            $indexed[$i] = $index;
        }

        $sort = (empty($_GET['sort'])) ? "" : contrexx_addslashes($_GET['sort']);
        $_GET['search'] = (empty($_GET['search'])) ? "" : contrexx_addslashes($_GET['search']);
        $keyword = (empty($_GET['keyword'])) ? "" : $_GET['keyword'];
        if ($sort == "sc") {
            /* Special Chars */
            $query = "SELECT *
                      FROM ".DBPREFIX."module_memberdir_values
                      WHERE `1` REGEXP '^[^a-zA-Z]'";
            if (!empty($dirid)) {
                $query .= " AND `dirid`= '$dirid'";
            }
        } elseif (preg_match("%^[a-z]$%i", $sort)) {
            /* Sort by char */
            $query = "SELECT *
                      FROM ".DBPREFIX."module_memberdir_values
                      WHERE `1` REGEXP '^".$sort."'";
            if (!empty($dirid)) {
                $query .= " AND `dirid`= '$dirid'";
            }
        } elseif ($_GET['search'] == "search") {
            /* Search */

            $query = "SELECT *
                      FROM ".DBPREFIX."module_memberdir_values
                      WHERE (
                        `1` LIKE '%$keyword%' OR
                        `2` LIKE '%$keyword%' OR
                        `3` LIKE '%$keyword%' OR
                        `4` LIKE '%$keyword%' OR
                        `5` LIKE '%$keyword%' OR
                        `6` LIKE '%$keyword%' OR
                        `7` LIKE '%$keyword%' OR
                        `8` LIKE '%$keyword%' OR
                        `9` LIKE '%$keyword%' OR
                        `10` LIKE '%$keyword%' OR
                        `11` LIKE '%$keyword%' OR
                        `12` LIKE '%$keyword%' OR
                        `13` LIKE '%$keyword%' OR
                        `14` LIKE '%$keyword%' OR
                        `15` LIKE '%$keyword%' OR
                        `16` LIKE '%$keyword%' OR
                        `17` LIKE '%$keyword%' OR
                        `18` LIKE '%$keyword%'
                        )";
            if (!empty($dirid)) {
                $query .= " AND `dirid`= '$dirid'";
            }
        } else {
            /* All */
            $query = "SELECT *
                      FROM ".DBPREFIX."module_memberdir_values
                      WHERE";
            if (!empty($dirid)) {
                $query .= " `dirid` = '$dirid'";
            }

            $query .= " ORDER BY `0` ASC, id ASC";
        }

        $pos = (empty($_GET['pos'])) ? 0 : intval($_GET['pos']);

        $objResult = $objDatabase->Execute($query);

        if ($objResult) {
            $count = $objResult->RecordCount();
            $paging = getPaging($count, $pos, "&amp;cmd=MemberDir&amp;act=showdir&amp;sort=$sort&amp;id=$dirid&amp;search={$_GET['search']}&amp;keyword=$keyword", "<b>".$_ARRAYLANG['TXT_MEMBERDIR_ENTRIES']."</b>", true);

            $this->_objTpl->setVariable("MEMBERDIR_PAGING", $paging);
        }

        $objResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'], $pos);

        if ($objResult) {
            $rowid = 2;
            while (!$objResult->EOF) {
                $this->_objTpl->setVariable(array(
                    "MEMBERDIR_ROW"     => ($highlight == $objResult->fields['id']) ? "highlightedGreen" : "row" . $rowid,
                    "MEMBERDIR_ID"      => $objResult->fields['id'],
                    "MEMBERDIR_USER_DEFINED_SORT_NUMBER"    => $objResult->fields['0'],
                    "MEMBERDIR_FIELD_1" => $objResult->fields[$indexed[1]],
                    "MEMBERDIR_FIELD_2" => $objResult->fields[$indexed[2]],
                    "MEMBERDIR_FIELD_3" => $objResult->fields[$indexed[3]],
                ));

                $rowid = ($rowid == 2) ? 1 : 2;

                $this->_objTpl->parse("memberdir_row");
                $objResult->MoveNext();
            }
        } else {
            $this->statusMessage = $_ARRAYLANG['TXT_DATABASE_READ_ERROR'];
            echo $objDatabase->ErrorMsg();
            echo $query;
        }

    }

    /**
     * Show Member Details
     *
     * A list of the members details.
     * @access private
     * @param int $id Id of the member which shall be shown
     * @global ADONewConnection
     * @global array
     */
    function _showMember($id = null)
    {
        global $objDatabase, $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_memberdir_show.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_SHOW_MEMBER'];

        if (empty($id)) {
            if (!empty($_GET['id'])) {
                $id = $_GET['id'];
            } else {
                \Cx\Core\Csrf\Controller\Csrf::header("Location: index.php?cmd=MemberDir");
            }
        }

        $this->_objTpl->setGlobalVariable(array(
            "TXT_SHOW_MEMBER"   => $_ARRAYLANG['TXT_SHOW_MEMBER'],
            "TXT_EDIT"          => $_ARRAYLANG['TXT_EDIT'],
        ));

        $query = "SELECT * FROM ".DBPREFIX."module_memberdir_values
                 WHERE id = '".$id."'";

        $objResult = $objDatabase->Execute($query);

        $error = false;

        $rowid = 2;
        if ($objResult) {
            // Show the first picture
            if ($this->directories[$objResult->fields['dirid']]['pic1'] && $objResult->fields['pic1'] != "none") {
                $size = getimagesize(ASCMS_PATH.$objResult->fields['pic1']);
                $this->_objTpl->setVariable(array(
                    "MEMBERDIR_SRC"     => $objResult->fields['pic1'],
                    "MEMBERDIR_WIDTH"   => ($size[0] < $this->options['max_width']) ? $size[0] : $this->options['max_width'],
                    "MEMBERDIR_HEIGHT"  => ($size[1] < $this->options['max_height']) ? $size[1] : $this->options['max_height'],
                    "MEMBERDIR_ROW"     => "row".$rowid,
                    "TXT_DELETE"        => $_ARRAYLANG['TXT_DELETE'],
                    "MEMBERDIR_PIC_NUMBER" => "1"
                ));
                $this->_objTpl->parse("imagerow");
                $rowid = ($rowid == 2) ? 1 : 2;
            }

            // Show the second picture
            if ($this->directories[$objResult->fields['dirid']]['pic2'] && $objResult->fields['pic2'] != "none") {
                $size = getimagesize(ASCMS_PATH.$objResult->fields['pic2']);
                $this->_objTpl->setVariable(array(
                    "MEMBERDIR_SRC"     => $objResult->fields['pic2'],
                    "MEMBERDIR_WIDTH"   => ($size[0] < $this->options['max_width']) ? $size[0] : $this->options['max_width'],
                    "MEMBERDIR_HEIGHT"  => ($size[1] < $this->options['max_height']) ? $size[1] : $this->options['max_height'],
                    "MEMBERDIR_ROW"     => "row".$rowid,
                    "TXT_DELETE"        => $_ARRAYLANG['TXT_DELETE'],
                    "MEMBERDIR_PIC_NUMBER" => "2"
                ));
                $this->_objTpl->parse("imagerow");
                $rowid = ($rowid == 2) ? 1 : 2;
            }


            $query = "SELECT * FROM ".DBPREFIX."module_memberdir_name
                     WHERE dirid = '".$objResult->fields['dirid']."'
                     ORDER BY field ASC";
            $objResult2 = $objDatabase->Execute($query);

            $this->_objTpl->setVariable(array(
                "TXT_DIRECTORY"         => $_ARRAYLANG['TXT_DIRECTORY'],
                "MEMBERDIR_DIRECTORY"   => $this->directories[$objResult->fields['dirid']]['name'],
                "MEMBERDIR_ID"          => $objResult->fields['id'],
                "MEMBERDIR_ROW"         => $rowid
            ));
            $rowid = ($rowid == 2) ? 1 : 2;

            if ($objResult2) {
                // Fill in the data
                while (!$objResult2->EOF) {
                    if ($objResult2->fields['active']) {
                        $value = ($objResult2->fields['field'] > 13) ? nl2br($objResult->fields[$objResult2->fields['field']]) : $objResult->fields[$objResult2->fields['field']];
                        $this->_objTpl->setVariable(array(
                            "MEMBERDIR_NAME"    => $objResult2->fields['name'],
                            "MEMBERDIR_VALUE"   => $value,
                            "MEMBERDIR_ROW"     => "row" . $rowid
                        ));

                        $this->_objTpl->parse("row");
                        $rowid = ($rowid == 2) ? 1 : 2;
                    }

                    $objResult2->MoveNext();
                }
            } else {
                $error = true;
            }
        } else {
            $error = true;
        }

        if ($error) {
            $this->statusMessage = $_ARRAYLANG['TXT_DATABASE_READ_ERROR'];
        }
    }

    /**
     * New Member
     *
     * Shows the form to add a new member
     * @access private
     * @global array
     */
    function _newMember()
    {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_memberdir_modify.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_NEW_MEMBER'];

        //TODO
// TODO: Unused
//        $defaultdir = 1;
        $dirid = (empty($_GET['dirid'])) ? $this->firstDir : $_GET['dirid'];

        $this->_objTpl->setGlobalVariable(array(
            "MEMBERDIR_ACTION"      => "?cmd=MemberDir&amp;act=saveNew",
            "TXT_NEW_MEMBER"        => $_ARRAYLANG['TXT_NEW_MEMBER'],
            "MEMBERDIR_DIRECTORY_LIST" => $this->dirList('directory', $dirid, 200),
            "MEMBERDIR_JS_URLPART"      => "&act=new",
            "MEMBERDIR_DIRID"       => $dirid,
            "TXT_DIRECTORY"         => $_ARRAYLANG['TXT_DIRECTORY'],
            "TXT_SAVE"              => $_ARRAYLANG['TXT_SAVE'],
            "TXT_EDIT_IMAGE"        => $_ARRAYLANG['TXT_EDIT_IMAGE']
        ));

        $fields = $this->getFieldData($dirid);

        $row = 2;
        if ($this->directories[$dirid]['pic1']) {
            $this->_objTpl->setVariable(array(
                "TXT_MEMBERDIR_FIELD"   => $_ARRAYLANG['TXT_PIC_UPLOAD']. " 1",
                "MEMBERDIR_FIELD_NAME"  => "picfield1",
                "MEMBERDIR_ROW"         => "row".$row,
                "TXT_MAX_FILE_SIZE"     => $_ARRAYLANG['TXT_MAX_FILE_SIZE'].": ".ini_get("upload_max_filesize"),
                "MEMBERDIR_IMAGE_NUMBER" => 1,
                "MEMBERDIR_IMAGE_SRC"   => "../core/Core/View/Media/icons/images.gif",
                "MEMBERDIR_IMAGE_SIZE"  => "21",
                'MEMBERDIR_MEDIABROWSER_BUTTON' => $this->getMediaBrowserButton(
                    'imageSelectionButtonOne',
                    'mbImageSelectionOne'
                ),
            ));

            $this->_objTpl->parse("pic_row");
            $row = ($row == 2) ? 1 : 2;
        }
        if ($this->directories[$dirid]['pic2']) {
            $this->_objTpl->setVariable(array(
                "TXT_MEMBERDIR_FIELD"   => $_ARRAYLANG['TXT_PIC_UPLOAD']. " 2",
                "MEMBERDIR_FIELD_NAME"  => "picfield2",
                "MEMBERDIR_ROW"         => "row".$row,
                "TXT_MAX_FILE_SIZE"     => $_ARRAYLANG['TXT_MAX_FILE_SIZE'].": ".ini_get("upload_max_filesize"),
                "MEMBERDIR_IMAGE_NUMBER" => 2,
                "MEMBERDIR_IMAGE_SRC"   => "../core/Core/View/Media/icons/images.gif",
                "MEMBERDIR_IMAGE_SIZE"  => "21",
                'MEMBERDIR_MEDIABROWSER_BUTTON' => $this->getMediaBrowserButton(
                    'imageSelectionButtonTwo',
                    'mbImageSelectionTwo'
                ),
            ));

            $this->_objTpl->parse("pic_row");
            $row = ($row == 2) ? 1 : 2;
        }

        foreach ($fields as $key => $field) {
            if ($field['active']) {
                $this->_objTpl->setVariable(array(
                    "TXT_MEMBERDIR_FIELD"    => $field['name'],
                    "MEMBERDIR_FIELD_NAME"   => "field_".$key,
                    "MEMBERDIR_FIELD_VALUE"  => (isset($_POST["field_".$key])) ? $_POST["field_".$key] : "",
                    "MEMBERDIR_ROW"          => "row".$row
                ));
                if ($key >= 13) {
                    $this->_objTpl->parse("textarea_row");
                } else {
                    $this->_objTpl->parse("input_row");
                }
            }
            $row = ($row == 2) ? 1 : 2;
        }

        //$this->_showForm($dirid);
    }

    /**
     * Save New
     *
     * If the input is right, saves it. If not, shows the form with all values.
     * @global array
     * @global ADONewConnection
     * @access private
     */
    function _saveNew()
    {
        global $_ARRAYLANG, $objDatabase;

        $dirid = intval($_POST['dirid']);

        $fields = $this->getFieldData($dirid);

        if (!$this->_validate_post($fields)) {
            $this->statusMessage = $_ARRAYLANG['ERR_FILL_AT_LEAST_ONE_OF_THE_FIRST'];
            return $this->_newMember();
        }

        foreach ($fields as $fieldid => $field) {
            if ($field['active']) {
                $values[$fieldid] = contrexx_addslashes($_POST['field_'.$fieldid]);
            } else {
                $values[$fieldid] = "";
            }
        }

        $query = "INSERT INTO ".DBPREFIX."module_memberdir_values
                  (`dirid`, `1`, `2`, `3`, `4`, `5`, `6`, `7`,
                   `8`, `9`, `10`, `11`, `12`, `13`, `14`, `15`, `16`, `17`, `18`, `lang_id`)
                   VALUES
                   ('".$dirid."',
                    '".$values[1]."', '".$values[2]."',
                    '".$values[3]."', '".$values[4]."',
                    '".$values[5]."', '".$values[6]."',
                    '".$values[7]."', '".$values[8]."',
                    '".$values[9]."', '".$values[10]."',
                    '".$values[11]."', '".$values[12]."',
                    '".$values[13]."', '".$values[14]."',
                    '".$values[15]."', '".$values[16]."',
                    '".$values[17]."', '".$values[18]."',
                    '".$this->langId."'
                   )";

        if ($objDatabase->Execute($query)) {
            $memberid = $objDatabase->Insert_ID();

            $uploadname1 = (!empty($_POST['image1'])) ? $_POST['image1'] : "none";

            $uploadname2 = (!empty($_POST['image2'])) ? $_POST['image2'] : "none";

            $query = "UPDATE ".DBPREFIX."module_memberdir_values
                      SET `pic1` = '$uploadname1', `pic2` = '$uploadname2'
                      WHERE id = '$memberid'";
            if ($objDatabase->Execute($query)) {
                 $this->okMessage = $_ARRAYLANG['TXT_DATABASE_SUCESSFUL'];
                 $_GET['id'] = $dirid;
                 $this->_overviewDir($memberid);
            } else {
                $this->statusMessage = $_ARRAYLANG['TXT_DATABASE_WRITE_ERROR'].$objDatabase->ErrorMsg();
                $this->_newMember();
            }
        } else {
            $this->statusMessage = $_ARRAYLANG['TXT_DATABASE_WRITE_ERROR'].$objDatabase->ErrorMsg();
            $this->_newMember();
        }
    }


    function _validate_post($fields) {
        $fields_to_check = 3;

        foreach ($fields as $fieldid => $field) {
            if ($field['active']) {
                // As the first 3 fields are shown in overviews,
                // we can assume that at least one of them needs to be set. So, if
                // all three are empty, the form is NOT valid.
                if ($fields_to_check-- > 0 and 0 < strlen($_POST['field_'.$fieldid])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Save Edited Member
     *
     * If the input is right, save the edited member
     * @access public
     * @global array
     * @global ADONewConnection
     */
    function _saveEditedMember()
    {
        global $_ARRAYLANG, $objDatabase;

        if (!isset($_POST['dirid'])) {
            return false;
        }
        $dirid = intval($_POST['dirid']);
        $id = $_GET['id'];

        $fields = $this->getFieldData($dirid);

        // Delete Picture if requested
        $query = "SELECT pic1, pic2 FROM ".DBPREFIX."module_memberdir_values
                  WHERE id = '$id'";
        $objDatabase->Execute($query);

        $picture1 = (!empty($_POST['image1'])) ? $_POST['image1'] : "none";
        $picture2 = (!empty($_POST['image2'])) ? $_POST['image2'] : "none";

        if (!$this->_validate_post($fields)) {
            $this->statusMessage = $_ARRAYLANG['ERR_FILL_AT_LEAST_ONE_OF_THE_FIRST'];
            return $this->_editMember();
        }

        foreach ($fields as $fieldid => $field) {
            if ($field['active']) {
                $values[$fieldid] = contrexx_addslashes($_POST['field_'.$fieldid]);
            } else {
                $values[$fieldid] = "";
            }
        }

        $query = "UPDATE ".DBPREFIX."module_memberdir_values
                  SET
                  `dirid` = '$dirid',
                  `pic1` = '$picture1',
                  `pic2` = '$picture2',
                  `1` = '{$values[1]}', `2` = '{$values[2]}',
                  `3` = '{$values[3]}', `4` = '{$values[4]}',
                  `5` = '{$values[5]}', `6` = '{$values[6]}',
                  `7` = '{$values[7]}', `8` = '{$values[8]}',
                  `9` = '{$values[9]}', `10` = '{$values[10]}',
                  `11` = '{$values[11]}', `12` = '{$values[12]}',
                  `13` = '{$values[13]}', `14` = '{$values[14]}',
                  `15` = '{$values[15]}', `16` = '{$values[16]}',
                  `17` = '{$values[17]}', `18` = '{$values[18]}'
                  WHERE id = '$id'";

        if ($objDatabase->Execute($query)) {
            $this->okMessage = $_ARRAYLANG['TXT_DATABASE_SUCESSFUL'];
            $_GET['id'] = $dirid;
            $this->_overviewDir($id);
        } else {
            $this->statusMessage = $_ARRAYLANG['TXT_DATABASE_WRITE_ERROR'].$objDatabase->ErrorMsg();
            $_POST['changeDir'] = 1;
            $this->_editMember();
        }
    }

    /**
     * Show Form
     *
     * Shows the form with the main variables
     * @access private
     * @global array
     * @param int $id Id-Number of the directory
     */
    function _showForm($id)
    {
        global $_ARRAYLANG;

        $this->_objTpl->setVariable(array(
            "TXT_DIRECTORY"     => $_ARRAYLANG['TXT_DIRECTORY'],
            "TXT_NAME"          => $_ARRAYLANG['TXT_NAME'],
            "TXT_SHOW_FORENAME" => $_ARRAYLANG['TXT_SHOW_FORENAME'],
            "TXT_COMPANY"       => $_ARRAYLANG['TXT_COMPANY'],
            "TXT_SHOW_STREET"   => $_ARRAYLANG['TXT_SHOW_STREET'],
            "TXT_SHOW_PLZ"      => $_ARRAYLANG['TXT_SHOW_PLZ'],
            "TXT_LOCATION"      => $_ARRAYLANG['TXT_LOCATION'],
            "TXT_SHOW_PHONE"    => $_ARRAYLANG['TXT_SHOW_PHONE'],
            "TXT_SHOW_FAX"      => $_ARRAYLANG['TXT_SHOW_FAX'],
            "TXT_SHOW_EMAIL"    => $_ARRAYLANG['TXT_SHOW_EMAIL'],
            "TXT_SHOW_COMMENT"  => $_ARRAYLANG['TXT_SHOW_COMMENT'],
            "TXT_SHOW_INDUSTRY" => $_ARRAYLANG['TXT_SHOW_INDUSTRY'],
            "TXT_SHOW_WIR_ACC"  => $_ARRAYLANG['TXT_SHOW_WIR_ACC'],
            "TXT_SHOW_WIR_RATE" => $_ARRAYLANG['TXT_SHOW_WIR_RATE'],
            "TXT_SHOW_ACCOUNT_TYPE" => $_ARRAYLANG['TXT_SHOW_ACCOUNT_TYPE'],
            "TXT_SHOW_DATE"     => $_ARRAYLANG['TXT_SHOW_DATE'],
            "TXT_SAVE"          => $_ARRAYLANG['TXT_SAVE'],
            "TXT_SHOW_INTERNET" => $_ARRAYLANG['TXT_SHOW_INTERNET']
        ));
    }

    /**
     * Edit member
     *
     * Shows the form to edit a member
     * @access private
     * @global array
     * @global ADONewConnection
     */
    function _editMember()
    {
        global $_ARRAYLANG, $objDatabase;

        $this->_objTpl->loadTemplateFile('module_memberdir_modify.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_EDIT_MEMBER'];

        $id = intval($_GET['id']);

        if (!isset($_POST['changeDir'])) {
            $query = "SELECT * FROM ".DBPREFIX."module_memberdir_values
                      WHERE id = '$id'";
            $objResult = $objDatabase->Execute($query);
        }

        $dirid = (empty($_POST['changeDir'])) ? $objResult->fields['dirid'] : $_POST['directory'];

        $this->_objTpl->setGlobalVariable(array(
            "MEMBERDIR_ACTION"      => "?cmd=MemberDir&amp;act=saveEditedMember&amp;id=$id",
            "TXT_NEW_MEMBER"        => $_ARRAYLANG['TXT_EDIT_MEMBER'],
            "MEMBERDIR_DIRECTORY_LIST" => $this->dirList('directory', $dirid, 200),
            "MEMBERDIR_JS_URLPART"  => "&act=editMember&id=".$id,
            "MEMBERDIR_DIRID"       => $dirid,
            "TXT_DIRECTORY"         => $_ARRAYLANG['TXT_DIRECTORY'],
            "TXT_SAVE"              => $_ARRAYLANG['TXT_SAVE'],
            "TXT_EDIT_IMAGE"        => $_ARRAYLANG['TXT_EDIT_IMAGE']
        ));

        $fields = $this->getFieldData($dirid);

        $row = 2;

        // Show Input stuff for pic1
        if ($this->directories[$dirid]['pic1']) {
            $this->_objTpl->setVariable(array(
                "TXT_MEMBERDIR_FIELD"   => $_ARRAYLANG['TXT_PIC_UPLOAD']. " 1",
                "MEMBERDIR_ROW"         => "row".$row,
                "MEMBERDIR_IMAGE_NUMBER" => 1,
                "MEMBERDIR_IMAGE_SRC"   => ($objResult->fields['pic1'] == "none") ? "../core/Core/View/Media/icons/images.gif" : $objResult->fields['pic1'],
                "MEMBERDIR_HIDDEN_VALUE"    => ($objResult->fields['pic1'] == "none") ? "" : $objResult->fields['pic1'],
                "MEMBERDIR_IMAGE_SIZE"  => ($objResult->fields['pic1'] == "none") ? "21" : "60",
                'MEMBERDIR_MEDIABROWSER_BUTTON' => $this->getMediaBrowserButton(
                    'imageSelectionButtonOne',
                    'mbImageSelectionOne'
                ),
            ));

            $this->_objTpl->parse("pic_row");
            $row = ($row == 2) ? 1 : 2;
        }
        // Show Input stuff for pic2
        if ($this->directories[$dirid]['pic2']) {
            $this->_objTpl->setVariable(array(
                "TXT_MEMBERDIR_FIELD"   => $_ARRAYLANG['TXT_PIC_UPLOAD']. " 2",
                "MEMBERDIR_ROW"         => "row".$row,
                "MEMBERDIR_IMAGE_NUMBER" => 2,
                "MEMBERDIR_IMAGE_SRC"   => ($objResult->fields['pic2'] == "none") ? "../core/Core/View/Media/icons/images.gif" : $objResult->fields['pic2'],
                "MEMBERDIR_HIDDEN_VALUE"    => ($objResult->fields['pic2'] == "none") ? "" : $objResult->fields['pic2'],
                "MEMBERDIR_IMAGE_SIZE"  => ($objResult->fields['pic2'] == "none") ? "21" : "60",
                'MEMBERDIR_MEDIABROWSER_BUTTON' => $this->getMediaBrowserButton(
                    'imageSelectionButtonTwo',
                    'mbImageSelectionTwo'
                ),
            ));

            $this->_objTpl->parse("pic_row");
            $row = ($row == 2) ? 1 : 2;
        }
        foreach ($fields as $key => $field) {
            if ($field['active']) {
                    $this->_objTpl->setVariable(array(
                        "TXT_MEMBERDIR_FIELD"   => $field['name'],
                        "MEMBERDIR_FIELD_NAME"      => "field_".$key,
                        "MEMBERDIR_FIELD_VALUE"     => (empty($_POST['changeDir'])) ? $objResult->fields["$key"] : $_POST["field_$key"],
                        "MEMBERDIR_ROW"         => "row".$row
                    ));
                if ($key >= 13) {
                    $this->_objTpl->parse("textarea_row");
                } else {
                    $this->_objTpl->parse("input_row");
                }
            }
            $row = ($row == 2) ? 1 : 2;
        }

        //$this->_showForm($dirid);
    }

    /**
     * Multiple Member Deletetion
     *
     * @global ADONewConnection
     * @global array
     * @access private
     */
    function _multiMemberDelete()
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_POST['selectedId'])) {
            $error = false;
            foreach ($_POST['selectedId'] as $intCatId) {
                if (!$this->_deleteEntry($intCatId)) {
                        $error = true;
                }
            }
            if ($error) {
                $this->statusMessage = $_ARRAYLANG['TXT_DELETE_ERROR'];
            }
        }
    }


    /**
     * Deletes a Picture
     */
    function _deletePic()
    {
        global $objDatabase;

        if (!isset($_GET['id'])) {
            return false;
        }
        $id = $_GET['id'];

        $query = "SELECT pic1, pic2 FROM ".DBPREFIX."module_memberdir_values
                  WHERE id = '$id'";
        $objResult = $objDatabase->Execute($query);

        if ($objResult) {
            if (intval($_GET['pic']) == 1) {
                $picture = 'pic1';
            } else {
                $picture = 'pic2';
            }

           if ($objResult->fields[$picture] != "none") {
                $query = "UPDATE ".DBPREFIX."module_memberdir_values
                          SET `$picture` = 'none'
                          WHERE id = '$id'";
                $objDatabase->Execute($query);
            }
        }
    }

    /**
     * Export
     *
     * @access private
     * @global ADONewConnection
     * @global array
     */
    function _export()
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_GET['everything'])) {
            // Download everything
            $filename = $_ARRAYLANG['TXT_MEMBERDIR'];
            $fieldnames = $this->getFieldData($this->firstDir);
            $data = $this->exportDir(0, $fieldnames);
        } elseif (!empty($_GET['id'])) {
            // Download a single directory and, if whished, its subdirectories
            $dirid = $_GET['id'];

            $filename = $this->directories[$dirid]['name'];
            $fieldnames = $this->getFieldData($dirid);
            $data = $this->exportDir($dirid, $fieldnames);
        } elseif (isset($_GET['singleMembers'])) {
            // Download single members
            $query = "SELECT * FROM ".DBPREFIX."module_memberdir_values";
            $first = true;

            foreach ($_POST['selectedId'] as $id) {
                if ($first) {
                    $query .= "id = '".$id."' ";
                    $fieldnames = $this->getFieldData($id);
                    $first = false;
                } else {
                    $query .= "OR id ='".$id."' ";
                }
            }

            $objResult = $objDatabase->Execute($query);

            if ($objResult) {
                $filename = $this->directories[$objResult->fields['dirid']]['name'];
                $fieldnames = $this->getFieldData($objResult->fields['dirid']);

                $data = "";
                $line = "";
                while (!$objResult->EOF) {
                    foreach ($fieldnames as $key => $field) {
                        if ($field['active']) {
                            $line .= "\"". str_replace("\r\n", "\n", $objResult->fields[$key]) . "\";";
                        }
                    }
                    // The first column is the directory name
                    $dirName = $this->directories[$objResult->fields['dirid']]['name'];

                    $line = $dirName.";".$line;

                    // The pictures
                    if ($objResult->fields['pic1'] == "none") {
                        $pic1 = "";
                    } else {
                        $pic1 = $objResult->fields['pic1'];
                    }

                    if ($objResult->fields['pic2'] == "none") {
                        $pic2 = "";
                    } else {
                        $pic2 = $objResult->fields['pic2'];
                    }

                    $line .= $pic1.";".$pic2.";";

                    $data .= $line."\n";
                    $line = "";

                    $objResult->MoveNext();
                }
            }

        } else {
            // Download certain directories
            $data = "";
            $first = true;
            foreach ($_POST['selectedId'] as $id) {
                if ($first) {
                    $fieldnames = $this->getFieldData($id);
                    $first = false;
                }
                $data .= $this->exportDir($id, $fieldnames);
            }
            $filename = $_ARRAYLANG['TXT_MEMBERDIR'];
        }

        // The first line contains the names of every column
        $headerline = "";
        foreach ($fieldnames as $name) {
            if ($name['active']) {
                $headerline .= $name['name'].";";
            }
        }

        // The first column is the directory name
        $headerline = $_ARRAYLANG['TXT_DIRECTORY'].";".$headerline;
        // The two Picture Fields
        $headerline .= $_ARRAYLANG['TXT_PICTURE']." 1;".$_ARRAYLANG['TXT_PICTURE']." 2;";

        header("Content-Type: text/comma-separated-values", true);
        header('Content-Disposition: inline; filename="'.$filename.'.csv"', true);

        echo $headerline."\n".$data;
        exit();
    }

    /**
     * Exports one directory and its subdirectories
     *
     * Needed for recursion.
     *
     * If the $dirid is 0, it exports everything (without
     * recursion)
     * @param int $dirid
     * @return string Data
     */
    function exportDir($dirid, $fieldnames) {
        global $objDatabase;

        if ($dirid == 0) {
            $query = "SELECT * FROM ".DBPREFIX."module_memberdir_values";
        } else {
            $query = "SELECT * FROM ".DBPREFIX."module_memberdir_values
                          WHERE dirid = '$dirid'";
        }

        $objResult = $objDatabase->Execute($query);

        $line = "";
        $data = "";
        while (!$objResult->EOF) {
            foreach ($fieldnames as $key => $field) {
                if ($field['active']) {
                    $line .= "\"". str_replace("\r\n", "\n", $objResult->fields[$key]) . "\";";
                }
            }
            // The first column is the directory name
            $dirName = $this->directories[$objResult->fields['dirid']]['name'];

            $line = $dirName.";".$line;

            // The pictures
            if ($objResult->fields['pic1'] == "none") {
                $pic1 = "";
            } else {
                $pic1 = $objResult->fields['pic1'];
            }

            if ($objResult->fields['pic2'] == "none") {
                $pic2 = "";
            } else {
                $pic2 = $objResult->fields['pic2'];
            }

            $line .= $pic1.";".$pic2.";";

            $data .= $line."\n";
            $line = "";
            $objResult->MoveNext();
        }


        if ($dirid != 0) {
            if (isset($_GET['with_children']) && $this->directories[$dirid]['has_children']) {
                foreach ($this->directories as $key => $directory) {
                    if ($directory['parentdir'] == $dirid) {
                        // This directory has children, and the user wants them to be exported too
                        $data .= $this->exportDir($key, $fieldnames);
                    }
                }
            }
        }

        return $data;
    }


    /**
     * Delete Entry
     *
     * @param int $id Id of the database entry
     * @return bool If the deletion has failed or not
     * @global ADONewConnection
     * @access private
     */
    function _deleteEntry($id)
    {
        global $objDatabase;

        $query = "DELETE FROM ".DBPREFIX."module_memberdir_values
                  WHERE id = '$id'";
        if ($objDatabase->Execute($query)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Import
     *
     * Shows the import dialog
     * Customer.
     * @access private
     * @global ADONewConnection
     * @global array
     */
    function _import()
    {
        global $objDatabase, $_ARRAYLANG;

        \Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH.'/importexport/import.class.php');

        $importlib = new \Import();

        if (isset($_POST['import_cancel'])) {
            $importlib->cancel();
            \Cx\Core\Csrf\Controller\Csrf::header("Location: index.php?cmd=MemberDir&act=import");
            exit;
        } elseif (isset($_POST['fieldsSelected'])) {
            $fieldnames = $this->getFieldData($_POST['directory']);

            foreach ($fieldnames as $fieldKey => $fieldValue) {
                if ($fieldValue['active']) {
                    $fields[$fieldKey] = $fieldValue['name'];
                }
            }

            $data = $importlib->getFinalData($fields);
            foreach ($data as $row) {

                $query = "INSERT INTO ".DBPREFIX."module_memberdir_values
                    (`dirid`, `pic1`, `pic2`, `1`, `2`, `3`, `4`, `5`, `6`, `7`, `8`, `9`,
                     `10`, `11`, `12`, `13`, `14`, `15`, `16`, `17`, `18`, `lang_id`) VALUES
                    ('".$_POST['directory']."',
                     'none', 'none',
                     '". $this->getDbInput($row[1]) ."',
                     '". $this->getDbInput($row[2]) ."',
                     '". $this->getDbInput($row[3]) ."',
                     '". $this->getDbInput($row[4]) ."',
                     '". $this->getDbInput($row[5]) ."',
                     '". $this->getDbInput($row[6]) ."',
                     '". $this->getDbInput($row[7]) ."',
                     '". $this->getDbInput($row[8]) ."',
                     '". $this->getDbInput($row[9]) ."',
                     '". $this->getDbInput($row[10]) ."',
                     '". $this->getDbInput($row[11]) ."',
                     '". $this->getDbInput($row[12]) ."',
                     '". $this->getDbInput($row[13]) ."',
                     '". $this->getDbInput($row[14]) ."',
                     '". $this->getDbInput($row[15]) ."',
                     '". $this->getDbInput($row[16]) ."',
                     '". $this->getDbInput($row[17]) ."',
                     '". $this->getDbInput($row[18]) ."',
                     '". $this->langId ."')
                ";


                if (!$objDatabase->Execute($query)) {
                    echo $objDatabase->ErrorMsg();
                }

                \Cx\Core\Csrf\Controller\Csrf::header("Location: index.php?cmd=MemberDir&act=showdir&id=".$_POST['directory']);
            }

        } elseif (empty($_POST['importfile'])) {
            $importlib->initFileSelectTemplate($this->_objTpl);

            /*
             * We need an additional input field for the selection
             * of the directory
             */
            $this->_objTpl->setVariable(array(
                "IMPORT_ACTION"     => "?cmd=MemberDir&amp;act=import",
                "IMPORT_ADD_NAME"   => $_ARRAYLANG['TXT_DIRECTORY'],
                "IMPORT_ADD_VALUE"  => $this->dirList('directory', $this->firstDir, 200),
                "IMPORT_ROWCLASS"   => "row1",
                "TXT_HELP"          => $_ARRAYLANG['TXT_IMPORT_HELP']
            ));
            $this->_objTpl->parse("additional");
        } else {
            $fieldnames = $this->getFieldData($_POST['directory']);

            foreach ($fieldnames as $key => $value) {
                if ($value['active']) {
                    $given_fields[$key] = $value['name'];
                }
            }
            $importlib->initFieldSelectTemplate($this->_objTpl, $given_fields);

            /*
             * We need to pass the directory value, given by the
             * file selection template to the next step of importing
             */
            $this->_objTpl->setVariable(array(
                "IMPORT_ACTION"     => "?cmd=MemberDir&amp;act=import",
                "IMPORT_HIDDEN_NAME" => "directory",
                "IMPORT_HIDDEN_VALUE" => $_POST['directory'],
            ));
        }
    }

    /**
     * Loads subnavbar level 2
     *
     * @access  private
     * @global  array   $_CORELANG
     */
    private function settings()
    {
        global $_CORELANG;

        $this->pageTitle = $_CORELANG['TXT_CORE_SETTINGS'];
        $this->_objTpl->loadTemplateFile('module_memberdir_settings.html', true, true);
        $this->_objTpl->setVariable(array(
            'TXT_CORE_GENERAL'      => $_CORELANG['TXT_CORE_GENERAL'],
            'TXT_CORE_PLACEHOLDERS' => $_CORELANG['TXT_CORE_PLACEHOLDERS'],
        ));

        switch (!empty($_GET['tpl']) ? $_GET['tpl'] : '') {
            case 'general':
                $this->settingsGeneral();
                break;
            case 'placeholders':
                $this->settingsPlaceholders();
                break;
            default:
                $this->settingsGeneral();
                break;
        }
    }

    /**
     * Loads and saves general settings
     *
     * @access  private
     * @global  array               $_ARRAYLANG
     * @global  ADONewConnection    $objDatabase
     */
    private function settingsGeneral() {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_POST['settings_general'])) {
            $error = false;

            if (isset($_POST['default_listing'])) {
                $query = "UPDATE ".DBPREFIX."module_memberdir_settings
                          SET setvalue = '".contrexx_addslashes($_POST['default_listing'])."'
                          WHERE setname = 'default_listing'";
                if (!$objDatabase->Execute($query)) {
                    $error = true;
                }
            }

            if (isset($_POST['max_width'])) {
                $query = "UPDATE ".DBPREFIX."module_memberdir_settings
                          SET setvalue = '".contrexx_addslashes($_POST['max_width'])."'
                          WHERE setname = 'max_width'";
                if (!$objDatabase->Execute($query)) {
                    $error = true;
                }
            }

            if (isset($_POST['max_height'])) {
                $query = "UPDATE ".DBPREFIX."module_memberdir_settings
                          SET setvalue = '".contrexx_addslashes($_POST['max_height'])."'
                          WHERE setname = 'max_height'";
                if (!$objDatabase->Execute($query)) {
                    $error = true;
                }
            }

            if ($error) {
                $this->statusMessage = $_ARRAYLANG['TXT_DATABASE_WRITE_ERROR'];
            } else {
                $this->okMessage = $_ARRAYLANG['TXT_DATABASE_SUCESSFUL'];
            }
        }

        parent::__construct();

        $this->_objTpl->addBlockfile('MEMBERDIR_SETTINGS_CONTENT', 'settings_content', 'module_memberdir_settings_general.html');
        $this->pageTitle = $_ARRAYLANG['TXT_SETTINGS'];

        $this->_objTpl->setVariable(array(
            "TXT_SETTINGS"      => $_ARRAYLANG['TXT_SETTINGS'],
            "TXT_SETTINGS_DEFAULT_LISTING"  => $_ARRAYLANG['TXT_SETTINGS_DEFAULT_LISTING'],
            "TXT_SAVE"          => $_ARRAYLANG['TXT_SAVE'],
            "TXT_YES"           => $_ARRAYLANG['TXT_YES'],
            "TXT_NO"            => $_ARRAYLANG['TXT_NO'],
            "TXT_SETTINGS_IMAGE_MAX_HEIGHT"  => $_ARRAYLANG['TXT_SETTINGS_IMAGE_MAX_HEIGHT'],
            "TXT_SETTINGS_IMAGE_MAX_WIDTH"   => $_ARRAYLANG['TXT_SETTINGS_IMAGE_MAX_WIDTH'],
            "MAX_HEIGHT"         => $this->options['max_height'],
            "MAX_WIDTH"         => $this->options['max_width']
        ));

        $selected = "selected=\"selected\"";

        $this->_objTpl->setVariable(array(
            "SETTINGS_DEFAULT_LISTING"  => $this->options['default_listing'],
            "YES_SELECTED"      => ($this->options['default_listing'] == 1 ? $selected : ""),
            "NO_SELECTED"       => ($this->options['default_listing'] == 0 ? $selected : "")
        ));

        $this->_objTpl->parse('settings_content');
    }

    /**
     * Shows placeholders
     *
     * @access  private
     * @global  array   $_CORELANG
     */
    private function settingsPlaceholders()
    {
        global $_CORELANG;

        $this->_objTpl->addBlockfile('MEMBERDIR_SETTINGS_CONTENT', 'settings_content', 'module_memberdir_settings_placeholders.html');
        $this->_objTpl->setVariable('TXT_MEMBERDIR_PLACEHOLDERS', $_CORELANG['TXT_CORE_PLACEHOLDERS']);
        $this->pageTitle = $_CORELANG['TXT_CORE_PLACEHOLDERS'];
        $this->_objTpl->parse('settings_content');
    }

    /**
     * GetDbInput
     */
    function getDbInput($input)
    {
        return (!empty($input)) ? contrexx_addslashes($input) : "";
    }


    function _activate()
    {
        global $objDatabase;

        if (!isset($_GET['id'])) {
            return false;
        }
        $id = $_GET['id'];

        if ($this->directories[$id]['active']) {
            $query = "UPDATE ".DBPREFIX."module_memberdir_directories
                SET active = '0' WHERE dirid = '".$id."'";
        } else {
            $query = "UPDATE ".DBPREFIX."module_memberdir_directories
                SET active = '1' WHERE dirid = '".$id."'";
        }

        if (!$objDatabase->Execute($query)) {
            echo $objDatabase->ErrorMsg();
        }
    }

}
?>
