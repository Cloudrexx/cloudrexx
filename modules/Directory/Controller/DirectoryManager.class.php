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
 * Directory
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Janik Tschanz <janik.tschanz@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_directory
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Directory\Controller;

/**
 * Includes
 */
\Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH . '/PEAR/XML/RSS.class.php');

/**
 * Directory
 *
 * Class to manage CMS RSS news feeds
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Janik Tschanz <janik.tschanz@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_directory
 */
class DirectoryManager extends DirectoryLibrary
{
    var $_objTpl;
    var $pageContent;
    var $strErrMessage = '';
    var $strOkMessage = '';
    var $_selectedLang;
    var $langId;
    var $getPlatforms = array();
    var $getLanguages = array();
    var $getCantons = array();
    var $users = array();
    var $settings = array();
    var $path;
    var $webPath;
    var $imagePath;
    var $imageWebPath;
    var $fileSize;
    var $dirLog;
    var $mediaPath;
    var $mediaWebPath;
    var $rssPath;
    var $rssWebPath;
    var $countFeeds;

    private $act = '';

    /**
    * Constructor
    *
    * @access   public
    * @global    array
    * @global    InitCMS
    * @global    \Cx\Core\Html\Sigma
    * @global    ADONewConnection
    */
    function __construct()
    {
        global $objInit;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Directory/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->langId=$objInit->userFrontendLangId;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $this->path = ASCMS_DIR_PATH . '/';
        $this->webPath = ASCMS_DIR_WEB_PATH . '/';
        $this->imagePath = ASCMS_DIR_PATH . '/View/Media';
        $this->imageWebPath = ASCMS_DIR_WEB_PATH . '/View/Media';
        $this->mediaPath = $cx->getWebsiteMediaDirectoryPath() . '/';
        $this->mediaWebPath = $cx->getWebsiteMediaDirectoryWebPath() . '/';
        $this->rssPath = \Env::get('cx')->getWebsiteFeedPath() . '/';
        $this->rssWebPath = ASCMS_FEED_WEB_PATH . '/';

        //check chmod
        $obj_file = new \File();
        $obj_file->setChmod(mediaPath, $this->mediaWebPath, "");

        //get settings
        $this->settings = $this->getSettings();

    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable("CONTENT_NAVIGATION","
            <a href='index.php?cmd=Directory' class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_DIR_CATEGORIES']."</a>
            ".($this->settings['levels']['value'] == '1' ? "<a href='index.php?cmd=Directory&amp;act=levels' class='".($this->act == 'levels' ? 'active' : '')."'>".$_ARRAYLANG['TXT_LEVELS']."</a>" : "")."
            <a href='index.php?cmd=Directory&amp;act=new' class='".($this->act == 'new' ? 'active' : '')."'>".$_ARRAYLANG['TXT_DIR_CREATE_ENTREE']."</a>
            <a href='index.php?cmd=Directory&amp;act=confirm' class='".($this->act == 'confirm' ? 'active' : '')."'>".$_ARRAYLANG['TXT_DIR_CONFIRM_ENTREE']."</a>
            <a href='index.php?cmd=Directory&amp;act=files' class='".($this->act == 'files' ? 'active' : '')."'>".$_ARRAYLANG['TXT_DIR_FILE_MANAGEMENT']."</a>
            <a href='index.php?cmd=Directory&amp;act=settings' class='".($this->act == 'settings' ? 'active' : '')."'>".$_ARRAYLANG['TXT_DIR_SETTINGS']."</a>");
    }


    /**
    * Do the requested newsaction
    *
    * @global    ADONewConnection
    * @global    \Cx\Core\Html\Sigma
    * @return    string    parsed content
    */
    function getPage()
    {
        global $objDatabase, $objTemplate;

        // general module access check
        \Permission::checkAccess(59, 'static');

        $this->act = isset($_GET['act']) ? $_GET['act'] : '';

        switch($this->act) {
            case "add":
                \Permission::checkAccess(97, 'static');
                $this->addCategorie();
                $this->showCategories();
                break;
            case "del":
                \Permission::checkAccess(97, 'static');
                $this->delete();
                $this->showCategories();
                break;
            case "move":
                \Permission::checkAccess(97, 'static');
                $this->move();
                $this->showCategories();
                break;
            case "edit":
                \Permission::checkAccess(97, 'static');
                $this->editCategorie();
                break;
            case "catOrder":
                \Permission::checkAccess(97, 'static');
                $this->catOrder();
                $this->showCategories();
                break;
            case "confirm":
                \Permission::checkAccess(94, 'static');
                $this->showConfirm();
                break;
            case "detailfile":
                \Permission::checkAccess(94, 'static');
                $this->detailEntry(intval($_GET['id']));
                break;
            case "confirmfile":
                \Permission::checkAccess(96, 'static');
                $this->confirmEntry_step1();
                $this->showConfirm();
                break;
            case "files":
                \Permission::checkAccess(96, 'static');
                $catId   = isset($_GET['cat']) ? contrexx_input2int($_GET['cat']) : 0;
                $levelId = isset($_GET['level']) ? contrexx_input2int($_GET['level']) : 0;
                $this->showFiles($catId, $levelId);
                break;
            case "delfile":
                \Permission::checkAccess(94, 'static');
                $this->delete();
                $this->showFiles('', '');
                break;
            case "editfile":
                \Permission::checkAccess(94, 'static');
                $this->editFile(intval($_GET['id']));
                break;
            case "movefile":
                \Permission::checkAccess(94, 'static');
                $this->move();
                break;
            case "restorevoting":
                \Permission::checkAccess(94, 'static');
                $this->restoreVoting(intval($_GET['id']));
                break;
            case "new":
                \Permission::checkAccess(96, 'static');
                $this->newEntry();
                break;
            case "settings":
                \Permission::checkAccess(92, 'static');
                $this->updateSettings();
                $this->showSettings();
                break;
            case "levels":
                \Permission::checkAccess(97, 'static');
                $this->showLevels();
                break;
            case "addlevel":
                \Permission::checkAccess(97, 'static');
                $this->addLevel();
                $this->showLevels();
                break;
            case "editlevel":
                \Permission::checkAccess(97, 'static');
                $this->editLevel();
                break;
            case "dellevel":
                \Permission::checkAccess(97, 'static');
                $this->delete();
                $this->showLevels();
                break;
            case "levelOrder":
                \Permission::checkAccess(97, 'static');
                $this->levelOrder();
                $this->showLevels();
                break;
            case "moveLevel":
                \Permission::checkAccess(97, 'static');
                $this->move();
                $this->showLevels();
                break;
            default:
                //check confirm feeds
                if ($this->settings['showConfirm']['value'] == 1) {
                    $query = "SELECT id FROM ".DBPREFIX."module_directory_dir WHERE status ='0' LIMIT 1";
                    $objResult = $objDatabase->Execute($query);

                    if ($objResult !== false && $objResult->RecordCount()==1) {
                        \Permission::checkAccess(96, 'static');
                        $this->showConfirm();
                    } else {
                        $this->showCategories();
                    }
                } else {
                    $this->showCategories();
                }
        }

        $objTemplate->setVariable(array(
            'CONTENT_OK_MESSAGE'        => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->strErrMessage,
            'ADMIN_CONTENT'             => $this->_objTpl->get(),
            'CONTENT_TITLE'             => $this->pageTitle,
        ));

        $this->setNavigation();
        return $this->_objTpl->get();
    }


    /**
    * shows all levels
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function showLevels()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

         // initialize variables
        $this->_objTpl->loadTemplateFile('module_directory_levels.html',true,true);
        $this->pageTitle = "Ebenen";

        //get select content
        $levelId = 0;
        $levels = $this->getSearchLevels($levelId);

        $this->_objTpl->setVariable(array(
            'TXT_NAME'                          => $_ARRAYLANG['TXT_NAME'],
            'TXT_DESCRIPTION'                   => $_ARRAYLANG['TXT_DIR_DESCRIPTION'],
            'TXT_LEVEL'                         => $_ARRAYLANG['TXT_LEVEL'],
            'TXT_ADD_LEVEL'                     => $_ARRAYLANG['TXT_ADD_LEVEL'],
            'TXT_NEW_LEVEL'                     => $_ARRAYLANG['TXT_NEW_LEVEL'],
            'TXT_LEVEL_CATEGORIES'              => $_ARRAYLANG['TXT_SHOW_CATEGORIES'],
            'TXT_METADESC'                      => $_ARRAYLANG['TXT_DIR_META_DESC'],
            'TXT_METAKEYS'                      => $_ARRAYLANG['TXT_DIR_META_KEYS'],
            'TXT_REQUIRED_FIELDS'               => $_ARRAYLANG['TXT_DIR_REQUIRED_FIELDS'],
            'TXT_ADD'                           => $_ARRAYLANG['TXT_DIR_ADD'],
            'TXT_DELETE'                        => $_ARRAYLANG['TXT_DIR_DEL'],
            'TXT_EDIT'                          => $_ARRAYLANG['TXT_DIR_EDIT'],
            'TXT_LIST'                          => $_ARRAYLANG['TXT_DIR_LIST'],
            'TXT_COLLAPS_ALL'                   => $_ARRAYLANG['TXT_DIR_COLLAPS'],
            'TXT_EXPAND_ALL'                    => $_ARRAYLANG['TXT_DIR_EXPAND'],
            'TXT_ACTION'                        => $_ARRAYLANG['TXT_DIR_ACTION'],
            'TXT_CONFIRM_DELETE_DATA'           => $_ARRAYLANG['TXT_DIR_CONFIRM_DEL'],
            'TXT_ACTION_IS_IRREVERSIBLE'        => $_ARRAYLANG['TXT_DIR_DEL_ALL'],
            'TXT_SAVE_CHANGES'                  => $_ARRAYLANG['TXT_DIR_CHANGES_SAVE'],
            'TXT_FIELDS_REQUIRED'               => $_ARRAYLANG['TXT_DIR_FILL_ALL'],
            'LEVELS'                            => $levels,
            'TXT_SHOW_ENTRIES'                  => $_ARRAYLANG['TXT_SHOW_ENTRIES'],
            'TXT_YES'                           => $_ARRAYLANG['TXT_YES'],
            'TXT_NO'                            => $_ARRAYLANG['TXT_NO'],
            'TXT_SHOW_TYPE'                     => $_ARRAYLANG['TXT_SHOW_TYPE'],
            'TXT_KIND_LEVEL_AND_CATEGORIES'     => $_ARRAYLANG['TXT_SHOW_CATEGORIES_N_LEVES'],
            'TXT_ONLY_KIND_LEVEL'               => $_ARRAYLANG['TXT_SHOW_ONLY_LEVELS'],
            'TXT_ONLY_CATEGORIES'               => $_ARRAYLANG['TXT_SHOW_ONLY_CATEGORIES'],
            'TXT_ONLY_ENTRIES'                  => $_ARRAYLANG['TXT_SHOW_ONLY_ENTRIES'],
            'TXT_FILE_SEARCH'                   => $_ARRAYLANG['TXT_DIR_FILE_SEARCH'],
            'TXT_SEARCH'                        => $_ARRAYLANG['TXT_DIR_SEARCH'],
            'TXT_OPTIONS'                       => $_ARRAYLANG['TXT_DIR_OPTIONS'],
            'TXT_MAKE_SELECTION'                => $_ARRAYLANG['TXT_MAKE_SELECTION'],
        ));

        //get all levels
        $objResult = $objDatabase->Execute("SELECT id, name, parentid, metadesc, metakeys, description, displayorder, status, showcategories FROM ".DBPREFIX."module_directory_levels ORDER BY displayorder");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $this->levels['name'][$objResult->fields['id']] =$objResult->fields['name'];
                $this->levels['parentid'][$objResult->fields['id']] =$objResult->fields['parentid'];
                $this->levels['metadesc'][$objResult->fields['id']] =$objResult->fields['metadesc'];
                $this->levels['metakeys'][$objResult->fields['id']] =$objResult->fields['metakeys'];
                $this->levels['description'][$objResult->fields['id']]=$objResult->fields['description'];
                $this->levels['displayorder'][$objResult->fields['id']]=$objResult->fields['displayorder'];
                $this->levels['status'][$objResult->fields['id']] =$objResult->fields['status'];
                $this->levels['showcategories'][$objResult->fields['id']] =$objResult->fields['showcategories'];

                $objResult->MoveNext();
            }
        }

        //call collaps/expand/status function
        $this->expand();
        $this->collaps();
        $this->status();

        $i= 0;
        $parentId= 0;

        $this->_objTpl->setCurrentBlock('levelsRow');

        //shows all level 1 categories
        if (in_array(0, $this->levels['parentid'])) {
            foreach($this->levels['name'] as $levelKey => $levelName) {
                if ($this->levels['parentid'][$levelKey] == $parentId) {
                    //set categorie icon
                    if ($_SESSION['expLevel'][$levelKey] == 1) {
                        $icon = "<a href='index.php?cmd=Directory&amp;act=levels&amp;collaps=$levelKey'><img src='".$this->imageWebPath."/minuslink.gif' border='0' alt='' /></a>";
                    } elseif (!in_array($levelKey, $this->levels['parentid'])) {
                        $icon = "<img src='".$this->imageWebPath."/pixel.gif' width='11' height='1' border='0' alt='' />";
                    } else {
                        $icon = "<a href='index.php?cmd=Directory&amp;act=levels&amp;expand=$levelKey'><img src='".$this->imageWebPath."/pluslink.gif' border='0' alt='' /></a>";
                    }

                    //set folderimage (active/inactive)
                    if ($this->levels['status'][$levelKey] == 1) {
                        $folder = "<a href='javascript:statusLevel(".$levelKey.", 0)'><img src='".$this->imageWebPath."/_folder.gif' border='0' alt='' /></a>";
                    } else {
                        $folder = "<a href='javascript:statusLevel(".$levelKey.", 1)'><img src='".$this->imageWebPath."/_folder_off.gif' border='0' alt='' /></a>";
                    }

                    //set showcategories (active/inactive)
                    if ($this->levels['showcategories'][$levelKey] == 1) {
                        $showCategories = "<img src='".$this->imageWebPath."/led_green.gif' border='0' alt='' />";
                    } else {
                        $showCategories = "<img src='".$this->imageWebPath."/led_red.gif' border='0' alt='' />";
                    }

                    //count feeds
                    $count = $this->count($levelKey, '');

                    $class = ($i % 2) ? 'row2' : 'row1';
                    $this->_objTpl->setVariable(array(
                        'LEVEL_ROW'             => $class,
                        'LEVEL_ID'              => $levelKey,
                        'LEVEL_NAME'            => "<a href='index.php?cmd=Directory&amp;act=files&amp;level=$levelKey'>".$levelName."</a>",
                        'LEVEL_DESCRIPTION'     => $this->levels['description'][$levelKey],
                        'LEVEL_DISPLAYORDER'    => $this->levels['displayorder'][$levelKey],
                        'LEVEL_METADESC'        => $this->levels['metadesc'][$levelKey],
                        'LEVEL_METAKEYS'        => $this->levels['metakeys'][$levelKey],
                        'LEVEL_PADDING'         => $padding,
                        'LEVEL_ICON'            => $icon,
                        'LEVEL_FOLDER'          => $folder,
                        'LEVEL_COUNTENTRIES'    => $count,
                        'LEVEL_CATEGORIES'      => $showCategories,
                        'LEVEL_CHECKBOX'        =>
                            "<input type=\"checkbox\" title=\"Select ".
                            $levelName."\" name=\"formSelected[]\" value=\"".
                            $levelKey."\" />",
                    ));
                    $i++;
                    $this->_objTpl->parseCurrentBlock("levelsRow");

                    //show subcategories
                    $this->showSublevels($levelKey, $padding, $i);
                }
            }

            $this->_objTpl->setVariable(array(
                'TXT_SELECT_ACTION'     => $_ARRAYLANG['TXT_DIR_CHOOSE_ACTION'],
                'TXT_DELETE'            => $_ARRAYLANG['TXT_DIR_DEL'],
                'TXT_SELECT_ALL'        => $_ARRAYLANG['TXT_DIRECTORY_SELECT_ALL'],
                'TXT_DESELECT_ALL'      => $_ARRAYLANG['TXT_DIRECTORY_DESELECT_ALL'],
                'LEVELS_MOVE'           => $levels,
                'TXT_MOVE'              => $_ARRAYLANG['TXT_MOVE'],
                'TXT_SELECT_LEVEL'      => $_ARRAYLANG['TXT_CHOOSE_LEVEL'],
                'TXT_MAIN_LEVEL'        => $_ARRAYLANG['TXT_MOVE_TO_FIRST_LEVEL'],
            ));

            $this->_objTpl->parse('importSelectAction');
        } else {
            //no levels found
            $this->_objTpl->setVariable(array(
                'NO_LEVEL_FOUND'    => $_ARRAYLANG['TXT_NO_LEVELS_FOUND'],
            ));

            $this->_objTpl->hideBlock('levelsRow');
            $this->_objTpl->parse('nolevelsRow');
            $this->_objTpl->hideBlock('importSelectAction');
        }

    }


    /**
    * show sublevels
    * @access   public
    * @param    string  $parentId
    * @param    string  $padding
    * @param    string  $i
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function showSublevels($parentId, $padding, $i)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        $padding= $padding + 20;

        //shows all subcategories
        foreach($this->levels['name'] as $levelKey => $levelName) {
            if ($this->levels['parentid'][$levelKey] == $parentId) {
                if ($_SESSION['expLevel'][$parentId] == 1) {
                    //set subcategorie icon
                    if ($_SESSION['expLevel'][$levelKey] == 1) {
                        $link = "<a href='index.php?cmd=Directory&amp;act=levels&amp;collaps=$levelKey'><img src='".$this->imageWebPath."/minuslink.gif' border='0' alt='' /></a>";
                    } elseif (!in_array($levelKey, $this->levels['parentid'])) {
                        $link = "<img src='".$this->imageWebPath."/pixel.gif' width='11' height='1' border='0' alt='' />";
                    } else {
                        $link = "<a href='index.php?cmd=Directory&amp;act=levels&amp;expand=$levelKey'><img src='".$this->imageWebPath."/pluslink.gif' border='0' alt='' /></a>";
                    }

                    //set folderimage (active/inactive)
                    if ($this->levels['status'][$levelKey] == 1) {
                        $folder = "<a href='javascript:statusLevel(".$levelKey.", 0)'><img src='".$this->imageWebPath."/_folder.gif' border='0' alt='' /></a>";
                    } else {
                        $folder = "<a href='javascript:statusLevel(".$levelKey.", 1)'><img src='".$this->imageWebPath."/_folder_off.gif' border='0' alt='' /></a>";
                    }

                    //set showcategories (active/inactive)
                    if ($this->levels['showcategories'][$levelKey] == 1) {
                        $showCategories = "<img src='".$this->imageWebPath."/led_green.gif' border='0' alt='' />";
                    } else {
                        $showCategories = "<img src='".$this->imageWebPath."/led_red.gif' border='0' alt='' />";
                    }

                    //count feeds
                    $count = $this->count($levelKey, '');

                    $class = ($i % 2) ? 'row2' : 'row1';
                    $this->_objTpl->setVariable(array(
                        'LEVEL_ROW'             => $class,
                        'LEVEL_ID'              => $levelKey,
                        'LEVEL_NAME'            => "<a href='index.php?cmd=Directory&amp;act=files&amp;level=$levelKey'>".$levelName."</a>",
                        'LEVEL_DESCRIPTION'     => $this->levels['description'][$levelKey],
                        'LEVEL_DISPLAYORDER'    => $this->levels['displayorder'][$levelKey],
                        'LEVEL_METADESC'        => $this->levels['metadesc'][$levelKey],
                        'LEVEL_METAKEYS'        => $this->levels['metakeys'][$levelKey],
                        'LEVEL_PADDING'         => $padding,
                        'LEVEL_ICON'            => $link,
                        'LEVEL_FOLDER'          => $folder,
                        'LEVEL_COUNTENTRIES'    => $count,
                        'LEVEL_CATEGORIES'      => $showCategories,
                        'LEVEL_CHECKBOX'        =>
                            "<input type=\"checkbox\" title=\"Select ".
                            $levelName."\" name=\"formSelected[]\" value=\"".
                            $levelKey."\" />",
                    ));
                    $i++;
                    $this->_objTpl->parseCurrentBlock("levelsRow");

                    //get more subcategories
                    $this->showSublevels($levelKey, $padding, $i);
                }
            }
        }
    }


    /**
    * add a new level
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function addLevel()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //get post data
        $levLevel           = intval($_POST['level']);
        $levName            = contrexx_strip_tags($_POST['name']);
        $levDescription     = contrexx_strip_tags($_POST['description']);
        $levMetadesc        = contrexx_strip_tags($_POST['metadesc']);
        $levMetakeys        = contrexx_strip_tags($_POST['metakeys']);
        $levShowtype        = contrexx_strip_tags($_POST['showtype']);

        switch ($levShowtype) {
            case '1':
                $levShowLevels      = '1';
                $levShowCategories  = '1';
                $levOnlyEntries     = '0';
                break;
            case '2':
                $levShowLevels      = '1';
                $levShowCategories  = '0';
                $levOnlyEntries     = '0';
                break;
            case '3':
                $levShowLevels      = '0';
                $levShowCategories  = '1';
                $levOnlyEntries     = '0';
                break;
            case '4':
                $levShowLevels      = '0';
                $levShowCategories  = '0';
                $levOnlyEntries     = '1';
                break;
        }

        //insert into database
        $objResult = $objDatabase->Execute("INSERT INTO ".DBPREFIX."module_directory_levels SET
                                parentid=".$levLevel.",
                                name='".$levName."',
                                description='".$levDescription."',
                                displayorder='0',
                                status='1',
                                metadesc='".$levMetadesc."',
                                metakeys='".$levMetakeys."',
                                showlevels='".$levShowLevels."',
                                showcategories='".$levShowCategories."',
                                onlyentries='".$levOnlyEntries."'");
        if ($objResult !== false) {
            $this->strOkMessage = $_ARRAYLANG['TXT_LEVEL_SUCCESSULL_ADDED'];
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_ADD_LEVEL_ERROR'];
        }
    }


    /**
    * edit selected level
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function editLevel()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        // initialize variables
        $this->_objTpl->loadTemplateFile('module_directory_levels_edit.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_EDIT_LEVEL'];

        //get categories
        $levelId = intval($_GET['id']);
        $levels = $this->getSearchLevels($levelId);

        $this->_objTpl->setVariable(array(
            'TXT_NAME'                          => $_ARRAYLANG['TXT_NAME'],
            'TXT_DESCRIPTION'                   => $_ARRAYLANG['TXT_DIR_DESCRIPTION'],
            'TXT_LEVEL'                         => $_ARRAYLANG['TXT_LEVEL'],
            'TXT_EDIT_LEVEL'                    => $_ARRAYLANG['TXT_EDIT_LEVEL'],
            'TXT_NEW_LEVEL'                     => $_ARRAYLANG['TXT_NEW_LEVEL'],
            'TXT_METADESC'                      => $_ARRAYLANG['TXT_DIR_META_DESC'],
            'TXT_METAKEYS'                      => $_ARRAYLANG['TXT_DIR_META_KEYS'],
            'TXT_REQUIRED_FIELDS'               => $_ARRAYLANG['TXT_DIR_REQUIRED_FIELDS'],
            'TXT_SAVE_CHANGES'                  => $_ARRAYLANG['TXT_DIR_CHANGES_SAVE'],
            'TXT_FIELDS_REQUIRED'               => $_ARRAYLANG['TXT_DIR_FILL_ALL'],
            'LEVELS'                            => $levels,
            'TXT_SHOW_ENTRIES'                  => $_ARRAYLANG['TXT_SHOW_ENTRIES'],
            'TXT_SHOW_TYPE'                     => $_ARRAYLANG['TXT_SHOW_TYPE'],
            'TXT_KIND_LEVEL_AND_CATEGORIES'     => $_ARRAYLANG['TXT_SHOW_CATEGORIES_N_LEVES'],
            'TXT_ONLY_KIND_LEVEL'               => $_ARRAYLANG['TXT_SHOW_ONLY_LEVELS'],
            'TXT_ONLY_CATEGORIES'               => $_ARRAYLANG['TXT_SHOW_ONLY_CATEGORIES'],
            'TXT_ONLY_ENTRIES'                  => $_ARRAYLANG['TXT_SHOW_ONLY_ENTRIES'],
        ));


        //get categorie data
        $objResult = $objDatabase->Execute("SELECT name, description, metakeys, metadesc, parentid, showlevels, showcategories, onlyentries FROM ".DBPREFIX."module_directory_levels WHERE id = '$levelId'");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $levelName              = $objResult->fields['name'];
                $levelDescription       = $objResult->fields['description'];
                $levelMetadesc          = $objResult->fields['metadesc'];
                $levelMetakeys          = $objResult->fields['metakeys'];
                $levelParentid          = $objResult->fields['parentid'];
                $levelShowLevels        = $objResult->fields['showlevels'];
                $levelShowCategories    = $objResult->fields['showcategories'];
                $levelOnlyEntries       = $objResult->fields['onlyentries'];
                $objResult->MoveNext();
            }
        }

        if ($levelOnlyEntries == '1') {
            $checkedEntries = 'checked';
        }

        if ($levelShowLevels == '1') {
            $checkedLevels  = 'checked';
        }

        if ($levelShowCategories == '1') {
            $checkedCategories  = 'checked';
        }

        if ($levelShowCategories == '1' && $levelShowLevels == '1') {
            $checkedCategories  = '';
            $checkedLevels  = '';
            $checkedBoth    = 'checked';
        }

        $this->_objTpl->setVariable(array(
            'LEVEL_PARENTID'            => $levelParentid,
            'LEVEL_ID'                  => $levelId,
            'LEVEL_NAME'                => $levelName,
            'LEVEL_DESCRIPTION'         => $levelDescription,
            'LEVEL_METADESC'            => $levelMetadesc,
            'LEVEL_METAKEYS'            => $levelMetakeys,
            'LEVEL_SHOW_BOTH'           => $checkedBoth,
            'LEVEL_SHOW_LEVELS'         => $checkedLevels,
            'LEVEL_SHOW_CATEGORIES'     => $checkedCategories,
            'CHECKED_YES'               => $checkedYes,
            'CHECKED_NO'                => $checkedNo,
            'LEVEL_SHOW_ENTRIES'        => $checkedEntries,
        ));

        //save changes
        $this->updateLevel();
    }


    /**
    * update level
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function updateLevel()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        if (isset($_POST['edit_submit'])) {
        //get post data
            $levLevel           = intval($_POST['edit_level']);
            $levId              = intval($_POST['edit_id']);
            $levParentId        = intval($_POST['edit_parentid']);
            $levName            = contrexx_strip_tags($_POST['edit_name']);
            $levDescription     = contrexx_strip_tags($_POST['edit_description']);
            $levMetadesc        = contrexx_strip_tags($_POST['edit_metadesc']);
            $levMetakeys        = contrexx_strip_tags($_POST['edit_metakeys']);
            $levShowtype        = contrexx_strip_tags($_POST['edit_showtype']);

            //check shotype
            switch ($levShowtype) {
                case '1':
                    $levShowLevels      = '1';
                    $levShowCategories  = '1';
                    $levOnlyEntries     = '0';
                    break;
                case '2':
                    $levShowLevels      = '1';
                    $levShowCategories  = '0';
                    $levOnlyEntries     = '0';
                    break;
                case '3':
                    $levShowLevels      = '0';
                    $levShowCategories  = '1';
                    $levOnlyEntries     = '0';
                    break;
                case '4':
                    $levShowLevels      = '0';
                    $levShowCategories  = '0';
                    $levOnlyEntries     = '1';
                    break;
            }

            //check parent id
            if ($levLevel == $levId) {
                $levParentId = $levParentId;
            } else {
                $levParentId = $levLevel;
            }

            //insert into database
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_levels SET
                                    parentid=".$levParentId.",
                                    name='".$levName."',
                                    description='".$levDescription."',
                                    status='1',
                                    metadesc='".$levMetadesc."',
                                    metakeys='".$levMetakeys."',
                                    showlevels='".$levShowLevels."',
                                    showcategories='".$levShowCategories."',
                                    onlyentries='".$levOnlyEntries."' WHERE id='".$levId."'");

            if ($objResult !== false) {
                $this->showLevels();
                $this->strOkMessage = $_ARRAYLANG['TXT_LEVEL_SUCCESSFULL_EDIT'];
            } else {
                $this->strErrMessage = $_ARRAYLANG['TXT_LEVEL_EDIT_ERROR'];
            }
        }
    }

    /**
    * show all categories
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function showCategories()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //get select content
        $catId = 0;
        $categories = $this->getSearchCategories($catId);

        // initialize variables
        $this->_objTpl->loadTemplateFile('module_directory_categories.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_DIR_CATEGORIES'];
        $this->_objTpl->setVariable(array(
            'TXT_NAME'                  => $_ARRAYLANG['TXT_DIRECTORY_NAME'],
            'TXT_DESCRIPTION'           => $_ARRAYLANG['TXT_DIR_DESCRIPTION'],
            'TXT_CATEGORY'              => $_ARRAYLANG['TXT_DIR_CATEGORIE'],
            'TXT_ADD_CATEGORY'          => $_ARRAYLANG['TXT_DIR_ADD_CATEGORIE'],
            'TXT_NEW_CATEGORY'          => $_ARRAYLANG['TXT_DIR_NEW_CATEGORIE'],
            'TXT_METADESC'              => $_ARRAYLANG['TXT_DIR_META_DESC'],
            'TXT_METAKEYS'              => $_ARRAYLANG['TXT_DIR_META_KEYS'],
            'TXT_REQUIRED_FIELDS'       => $_ARRAYLANG['TXT_DIR_REQUIRED_FIELDS'],
            'TXT_ADD'                   => $_ARRAYLANG['TXT_DIR_ADD'],
            'TXT_DELETE'                => $_ARRAYLANG['TXT_DIR_DEL'],
            'TXT_EDIT'                  => $_ARRAYLANG['TXT_DIR_EDIT'],
            'TXT_LIST'                  => $_ARRAYLANG['TXT_DIR_LIST'],
            'TXT_COLLAPS_ALL'           => $_ARRAYLANG['TXT_DIR_COLLAPS'],
            'TXT_EXPAND_ALL'            => $_ARRAYLANG['TXT_DIR_EXPAND'],
            'TXT_ACTION'                => $_ARRAYLANG['TXT_DIR_ACTION'],
            'TXT_CONFIRM_DELETE_DATA'   => $_ARRAYLANG['TXT_DIR_CONFIRM_DEL'],
            'TXT_ACTION_IS_IRREVERSIBLE' => $_ARRAYLANG['TXT_DIR_DEL_ALL'],
            'TXT_SAVE_CHANGES'          => $_ARRAYLANG['TXT_DIR_CHANGES_SAVE'],
            'TXT_FIELDS_REQUIRED'       => $_ARRAYLANG['TXT_DIR_FILL_ALL'],
            'CATEGORY'                  => $categories,
            'TXT_SHOW_ENTRIES'          => $_ARRAYLANG['TXT_SHOW_ENTRIES'],
            'TXT_YES'                   => $_ARRAYLANG['TXT_YES'],
            'TXT_NO'                    => $_ARRAYLANG['TXT_NO'],
            'TXT_FILE_SEARCH'           => $_ARRAYLANG['TXT_DIR_FILE_SEARCH'],
            'TXT_SEARCH'                => $_ARRAYLANG['TXT_DIR_SEARCH'],
            'TXT_OPTIONS'               => $_ARRAYLANG['TXT_DIR_OPTIONS'],
            'TXT_MAKE_SELECTION'        => $_ARRAYLANG['TXT_MAKE_SELECTION'],
        ));

        //get all categories
        $objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_categories ORDER BY displayorder");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $this->categories['name'][$objResult->fields['id']] =$objResult->fields['name'];
                $this->categories['parentid'][$objResult->fields['id']] =$objResult->fields['parentid'];
                $this->categories['metadesc'][$objResult->fields['id']] =$objResult->fields['metadesc'];
                $this->categories['metakeys'][$objResult->fields['id']] =$objResult->fields['metakeys'];
                $this->categories['description'][$objResult->fields['id']]=$objResult->fields['description'];
                $this->categories['displayorder'][$objResult->fields['id']]=$objResult->fields['displayorder'];
                $this->categories['status'][$objResult->fields['id']] =$objResult->fields['status'];
                $this->categories['showentries'][$objResult->fields['id']] =$objResult->fields['showentries'];

                $objResult->MoveNext();
            }
        }

        //call collaps/expand/status function
        $this->expand();
        $this->collaps();
        $this->status();

        $this->_objTpl->setCurrentBlock('categoriesRow');

        $i= 0;
        $parentId= 0;

        $padding = 0;
        //shows all level 1 categories
        if (in_array(0, $this->categories['parentid'])) {
            foreach($this->categories['name'] as $catKey => $catName) {
                if ($this->categories['parentid'][$catKey] == $parentId) {
                    //set categorie icon
                    if ($_SESSION['expCat'][$catKey] == 1) {
                        $icon = "<a href='index.php?cmd=Directory&amp;collaps=$catKey'><img src='".$this->imageWebPath."/minuslink.gif' border='0' alt='' /></a>";
                    } elseif (!in_array($catKey, $this->categories['parentid'])) {
                        $icon = "<img src='".$this->imageWebPath."/pixel.gif' width='11' height='1' border='0' alt='' />";
                    } else {
                        $icon = "<a href='index.php?cmd=Directory&amp;expand=$catKey'><img src='".$this->imageWebPath."/pluslink.gif' border='0' alt='' /></a>";
                    }

                    //set folderimage (active/inactive)
                    if ($this->categories['status'][$catKey] == 1) {
                        $folder = "<a href='javascript:statusCategory(".$catKey.",0)'><img src='".$this->imageWebPath."/_folder.gif' border='0' alt='' /></a>";
                    } else {
                        $folder = "<a href='javascript:statusCategory(".$catKey.",1)'><img src='".$this->imageWebPath."/_folder_off.gif' border='0' alt='' /></a>";
                    }

                    //set showcategories (active/inactive)
                    if ($this->categories['showentries'][$catKey] == 1) {
                        $showEntries = "<img src='".$this->imageWebPath."/led_green.gif' border='0' alt='' />";
                    } else {
                        $showEntries = "<img src='".$this->imageWebPath."/led_red.gif' border='0' alt='' />";
                    }

                    //count feeds
                    $count = $this->count('', $catKey);

                    $class = ($i % 2) ? 'row2' : 'row1';
                    $this->_objTpl->setVariable(array(
                        'CATEGORIES_ROW'            => $class,
                        'CATEGORIES_ID'             => $catKey,
                        'CATEGORIES_NAME'           => "<a href='index.php?cmd=Directory&amp;act=files&amp;cat=$catKey'>".$catName."</a>",
                        'CATEGORIES_DESCRIPTION'    => $this->categories['description'][$catKey],
                        'CATEGORIES_DISPLAYORDER'   => $this->categories['displayorder'][$catKey],
                        'CATEGORIES_METADESC'       => $this->categories['metadesc'][$catKey],
                        'CATEGORIES_METAKEYS'       => $this->categories['metakeys'][$catKey],
                        'CATEGORIES_PADDING'        => $padding,
                        'CATEGORIES_ICON'           => $icon,
                        'CATEGORIES_FOLDER'         => $folder,
                        'CATEGORIES_COUNTENTREES'   => $count,
                        'CATEGORIES_SHOW_ENTRIES'   => $showEntries,
                        'CATEGORIES_CHECKBOX'       =>
                            "<input type=\"checkbox\" title=\"Select ".
                            $catName."\" name=\"formSelected[]\" value=\"".
                            $catKey."\" />",
                    ));
                    $i++;
                    $this->_objTpl->parseCurrentBlock("categoriesRow");

                    //show subcategories
                    $this->showSubcategories($catKey, $padding, $i);
                }
            }

            $this->_objTpl->setVariable(array(
                'TXT_SELECT_ACTION'     => $_ARRAYLANG['TXT_DIR_CHOOSE_ACTION'],
                'TXT_DELETE'            => $_ARRAYLANG['TXT_DIR_DEL'],
                'TXT_SELECT_ALL'        => $_ARRAYLANG['TXT_DIRECTORY_SELECT_ALL'],
                'TXT_DESELECT_ALL'      => $_ARRAYLANG['TXT_DIRECTORY_DESELECT_ALL'],
                'CATEGORY_MOVE'         => $categories,
                'TXT_MOVE'              => $_ARRAYLANG['TXT_MOVE'],
                'TXT_SELECT_CATEGORY'   => $_ARRAYLANG['TXT_CHOOSE_CATEGORIE'],
                'TXT_MAIN_CATEGORY'     => $_ARRAYLANG['TXT_MOVE_TO_FIRST_LEVEL'],
            ));

            $this->_objTpl->parse('importSelectAction');
        } else {
            //no categories found
            $this->_objTpl->setVariable(array(
                'NO_CAT_FOUND'      => $_ARRAYLANG['TXT_DIR_NO_CATEGORIE_FOUND'],
            ));

            $this->_objTpl->hideBlock('categoriesRow');
            $this->_objTpl->parse('nocatRow');
            $this->_objTpl->hideBlock('importSelectAction');
        }
    }



    /**
    * shows all subcategories of any category
    * @access   public
    * @param    string  $parentId
    * @param    string  $padding
    * @param    string  $i
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function showSubcategories($parentId, $padding, $i)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        $padding= $padding + 20;

        //shows all subcategories
        foreach($this->categories['name'] as $catKey => $catName) {
            if ($this->categories['parentid'][$catKey] == $parentId) {
                if ($_SESSION['expCat'][$parentId] == 1) {
                    //set subcategorie icon
                    if ($_SESSION['expCat'][$catKey] == 1) {
                        $link = "<a href='index.php?cmd=Directory&amp;collaps=$catKey'><img src='".$this->imageWebPath."/minuslink.gif' border='0' alt='' /></a>";
                    } elseif (!in_array($catKey, $this->categories['parentid'])) {
                        $link = "<img src='".$this->imageWebPath."/pixel.gif' width='11' height='1' border='0' alt='' />";
                    } else {
                        $link = "<a href='index.php?cmd=Directory&amp;expand=$catKey'><img src='".$this->imageWebPath."/pluslink.gif' border='0' alt='' /></a>";
                    }

                    //set folderimage (active/inactive)
                    if ($this->categories['status'][$catKey] == 1) {
                        $folder = "<a href='javascript:statusCategory(".$catKey.",0)'><img src='".$this->imageWebPath."/_folder.gif' border='0' alt='' /></a>";
                    } else {
                        $folder = "<a href='javascript:statusCategory(".$catKey.",1)'><img src='".$this->imageWebPath."/_folder_off.gif' border='0' alt='' /></a>";
                    }

                    //count feeds
                    $count = $this->count('', $catKey);

                    $class = ($i % 2) ? 'row2' : 'row1';
                    $this->_objTpl->setVariable(array(
                        'CATEGORIES_ROW'            => $class,
                        'CATEGORIES_ID'             => $catKey,
                        'CATEGORIES_NAME'           => "<a href='index.php?cmd=Directory&amp;act=files&amp;cat=$catKey'>".$catName."</a>",
                        'CATEGORIES_DESCRIPTION'    => $this->categories['description'][$catKey],
                        'CATEGORIES_DISPLAYORDER'   => $this->categories['displayorder'][$catKey],
                        'CATEGORIES_METADESC'       => $this->categories['metadesc'][$catKey],
                        'CATEGORIES_METAKEYS'       => $this->categories['metakeys'][$catKey],
                        'CATEGORIES_PADDING'        => $padding,
                        'CATEGORIES_ICON'           => $link,
                        'CATEGORIES_FOLDER'         => $folder,
                        'CATEGORIES_COUNTENTREES'   => $count,
                        'CATEGORIES_CHECKBOX'       =>
                            "<input type=\"checkbox\" title=\"Select ".
                            $catName."\" name=\"formSelected[]\" value=\"".
                            $catKey."\" />",
                    ));
                    $i++;
                    $this->_objTpl->parseCurrentBlock("categoriesRow");

                    //get more subcategories
                    $this->showSubcategories($catKey, $padding, $i);
                }
            }
        }
    }


    /**
    * expand selected folder tree
    * @access   public
    */
    function expand()
    {
        if (isset($_GET['expand'])) {
            $this->initExpandCollapsSessionVariable();
            if ($_GET['expand'] == "all") {
                if ($_GET['act'] == "levels") {
                    foreach($this->levels['name'] as $levelKey => $levelName) {
                        $_SESSION['expLevel'][$levelKey] = 1;
                    }
                } else {
                    foreach($this->categories['name'] as $catKey => $catName) {
                        $_SESSION['expCat'][$catKey] = 1;
                    }
                }
            } else {
                if ($_GET['act'] == "levels") {
                    $_SESSION['expLevel'][$_GET['expand']] = "1";
                } else {
                    $_SESSION['expCat'][$_GET['expand']] = "1";
                }
            }
        }

    }

    /**
     * Initialize the session variable for expand/collaps
     * Check the $_GET[expand/collaps] available before call this method
     */
    public function initExpandCollapsSessionVariable()
    {
        $sessionVar = ($_GET['act'] == 'levels') ? 'expLevel' : 'expCat';
        if (empty($_SESSION[$sessionVar])) {
            $_SESSION[$sessionVar] = array();
        }
    }

    /**
    * collapse selected folder tree
    * @access   public
    */
    function collaps()
    {
        if (isset($_GET['collaps'])) {
            $this->initExpandCollapsSessionVariable();
            if ($_GET['collaps'] == "all") {
                if ($_GET['act'] == "levels") {
                    $_SESSION['expLevel'] = "";
                } else {
                    $_SESSION['expCat'] = "";
                }
            } else {
                if ($_GET['act'] == "levels") {
                    $_SESSION['expLevel'][$_GET['collaps']] = "";
                } else {
                    $_SESSION['expCat'][$_GET['collaps']] = "";
                }
            }
        }

    }


    /**
    * add a new category
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function addCategorie()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //get post data
        $catCategorie       = intval($_POST['category']);
        $catName            = contrexx_strip_tags($_POST['name']);
        $catDescription     = contrexx_strip_tags($_POST['description']);
        $catMetadesc        = isset($_POST['metadesc']) ? contrexx_strip_tags($_POST['metadesc']) : '';
        $catMetakeys        = isset($_POST['metakeys']) ? contrexx_strip_tags($_POST['metakeys']) : '';
        $catShowEntries     = contrexx_strip_tags($_POST['showentries']);

        //insert into database
        $objResult = $objDatabase->Execute("INSERT INTO ".DBPREFIX."module_directory_categories SET
                                parentid=".$catCategorie.",
                                name='".$catName."',
                                description='".$catDescription."',
                                displayorder='0',
                                metadesc='".$catMetadesc."',
                                metakeys='".$catMetakeys."',
                                showentries='".$catShowEntries."'");

        //status
        $this->strOkMessage = $_ARRAYLANG['TXT_DIR_CAT_SUCCESSFULL_ADDED'];
    }


    /**
    * Move categories, levels, and files
    * @access   public
    * @global    ADONewConnection
    * @global    array
    */
    function move()
    {
        global $objDatabase, $_ARRAYLANG;

        switch ($_GET['act']) {
            case'move':
                foreach($_POST["formSelected"] as $catName => $catKey) {
                    $parentId = intval($_POST['selectCat']);
                    if ($parentId != $catKey) {
                        $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_categories SET parentid=".$parentId." WHERE id='".$catKey."'");
                    }
                }
                break;
            case'movelevel':
                foreach($_POST["formSelected"] as $levelName => $levelKey) {
                    $parentId = intval($_POST['selectLevel']);
                    if ($parentId != $levelKey) {
                        $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_levels SET parentid=".$parentId." WHERE id='".$levelKey."'");
                    }
                }
                break;
            case'movefile':
                // initialize variables
                $this->_objTpl->loadTemplateFile('module_directory_entry_move.html',true,true);
                $this->pageTitle =  "Eintr�ge verschieben";

                //get categories/levels
                $categories = $this->getCategories('', 1);
                $levels = $this->getLevels('', 1);

                if (isset($_POST["formSelected"])) {
                    $_SESSION['formSelected'] = $_POST["formSelected"];
                }

                $java = <<< EOF
<script language="JavaScript" type="text/javascript">
/* <![CDATA[ */
function move(from, dest, add, remove)
{
  if (from.selectedIndex < 0) {
    if (from.options[0] != null) from.options[0].selected = true;
    from.focus();
    return false;
  } else {
    for (i = 0; i < from.length; ++i) {
      if (from.options[i].selected) {
        dest.options[dest.options.length] = new Option(from.options[i].text, from.options[i].value, false, false);
      }
    }
    for (i = from.options.length-1; i >= 0; --i) {
      if (from.options[i].selected) {
        from.options[i] = null;
      }
    }
  }
  disableButtons(from, dest, add, remove);
}

function disableButtons(from, dest, add, remove)
{
  if (from.options.length > 0) {
    add.disabled = 0;
  } else {
    add.disabled = 1;
  }
  if (dest.options.length > 0) {
    remove.disabled = 0;
  } else {
    remove.disabled = 1;
  }
}

function selectAll(control)
{
  for (i = 0; i < control.length; ++i) {
    control.options[i].selected = true;
  }
}

function deselectAll(control)
{
  for (i = 0; i < control.length; ++i) {
    control.options[i].selected = false;
  }
}

EOF;

                if ($this->settings['levels']['value']=='1') {
                    $this->_objTpl->parse('levels');
                    $java .=    'function CheckFields() {
                                    var errorMsg = "";
                                    with( document.moveForm ) {
                                        if (document.getElementsByName(\'selectedCat[]\')[0].value == "" && document.getElementsByName(\'selectedLevel[]\')[0].value == "") {
                                            errorMsg = errorMsg + "- '.$_ARRAYLANG['TXT_DIR_CATEGORIE'].'\n";
                                        }
                                    }

                                    if (errorMsg != "") {
                                        alert ("'.$_ARRAYLANG['TXT_DIR_FILL_ALL'].'\n\n");
                                        return false;
                                    } else {
                                        return true;
                                    }
                                }
                                ';
                    $action = 'selectAll(document.moveForm.elements[\'selectedCat[]\']); selectAll(document.moveForm.elements[\'selectedLevel[]\']); return CheckFields();';
                } else {
                    $this->_objTpl->hideBlock('levels');
                    $java .=    'function CheckFields() {
                                    var errorMsg = "";
                                    with( document.moveForm ) {
                                        if (document.getElementsByName(\'selectedCat[]\')[0].value == "") {
                                            errorMsg = errorMsg + "- '.$_ARRAYLANG['TXT_DIR_CATEGORIE'].'\n";
                                        }
                                    }

                                    if (errorMsg != "") {
                                        alert ("'.$_ARRAYLANG['TXT_DIR_FILL_ALL'].'\n\n" + errorMsg);
                                        return false;
                                    } else {
                                        return true;
                                    }
                                }
                                ';
                    $action = 'selectAll(document.moveForm.elements[\'selectedCat[]\']); return CheckFields();';
                }
                $java .= <<< EOF
/* ]]> */
</script>
EOF;
                $this->_objTpl->setVariable(array(
                    'TXT_MOVE_ENTRY'            => $_ARRAYLANG['TXT_MOVE_ENTRIES'],
                    'TXT_CATEGORY'              => $_ARRAYLANG['TXT_DIR_CATEGORIE'],
                    'TXT_FIELDS_REQUIRED'       => $_ARRAYLANG['TXT_DIR_FILL_ALL'],
                    'TXT_REQUIRED_FIELDS'       => $_ARRAYLANG['TXT_DIR_REQUIRED_FIELDS'],
                    'TXT_LEVEL'                 => $_ARRAYLANG['TXT_LEVEL'],
                    'TXT_MOVE'                  => $_ARRAYLANG['TXT_MOVE'],
                    'CATEGORY'                  => $categories,
                    'LEVELS'                    => $levels,
                    'DIRECTORY_JAVASCRIPT'      => $java,
                    'DIRECTORY_FORM_ACTION'     => $action,
                ));

                if (isset($_POST['move_submit'])) {
                    foreach($_SESSION['formSelected'] as $fileName => $fileKey) {
                        //save categories
                        if (!empty($_POST["selectedCat"])) {
                            $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_rel_dir_cat WHERE dir_id='".$fileKey."'");

                            foreach($_POST["selectedCat"] as $catName => $catKey) {
                                $query = "INSERT INTO ".DBPREFIX."module_directory_rel_dir_cat SET dir_id='".$fileKey."', cat_id='".$catKey."'";
                                $objDatabase->query($query);
                            }
                        }

                        //save levels
                        if (!empty($_POST["selectedLevel"])) {
                            $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_rel_dir_level WHERE dir_id='".$fileKey."'");

                            foreach($_POST["selectedLevel"] as $levelName => $levelKey) {
                                $query = "INSERT INTO ".DBPREFIX."module_directory_rel_dir_level SET dir_id='".$fileKey."', level_id='".$levelKey."'";
                                $objDatabase->query($query);
                            }
                        }
                    }
                    $_SESSION['formSelected'] = null;
                    $this->showFiles('', '');
                }
                break;
        }
    }


    /**
    * Deletes categories, levels, and files
    */
    function delete()
    {
        switch ($_GET['act'])
        {
            case'delfile':
                if (!isset($_GET['id'])) {
                    foreach($_POST["formSelected"] as $feedName => $feedKey) {
                        $this->delFile(intval($feedKey));
                    }
                } else {
                    $this->delFile(intval($_GET['id']));
                }
                break;
            case'dellevel':
                if (!isset($_GET['id'])) {
                    foreach($_POST["formSelected"] as $levelName => $levelKey) {
                        $this->delLevel(intval($levelKey));
                    }
                } else {
                    $this->delLevel(intval($_GET['id']));
                }
                break;
            case'del':
                if (!isset($_GET['id'])) {
                    foreach($_POST["formSelected"] as $catName => $catKey) {
                        $this->delCategorie(intval($catKey));
                    }
                } else {
                    $this->delCategorie(intval($_GET['id']));
                }
                break;
        }
    }


    /**
    * delete selected file
    * @access   public
    * @param    string  $id
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function delFile($id)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        $arrImages = array();
        $arrUploads = array();
        $arrRSS = array();

        //get file data
        $objResult = $objDatabase->Execute("SELECT  title,
                                                    attachment,
                                                    logo,
                                                    rss_file,
                                                    map,
                                                    lokal,
                                                    spez_field_11,
                                                    spez_field_12,
                                                    spez_field_13,
                                                    spez_field_14,
                                                    spez_field_15,
                                                    spez_field_16,
                                                    spez_field_17,
                                                    spez_field_18,
                                                    spez_field_19,
                                                    spez_field_20,
                                                    spez_field_25,
                                                    spez_field_26,
                                                    spez_field_27,
                                                    spez_field_28,
                                                    spez_field_29 FROM ".DBPREFIX."module_directory_dir WHERE id='$id'");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $name = $objResult->fields['title'];
                $file = $objResult->fields['filename'];
                $typ = $objResult->fields['typ'];

                array_push($arrImages,$objResult->fields['logo']);
                array_push($arrImages,$objResult->fields['map']);
                array_push($arrImages,$objResult->fields['lokal']);
                array_push($arrImages,$objResult->fields['spez_field_11']);
                array_push($arrImages,$objResult->fields['spez_field_12']);
                array_push($arrImages,$objResult->fields['spez_field_13']);
                array_push($arrImages,$objResult->fields['spez_field_14']);
                array_push($arrImages,$objResult->fields['spez_field_15']);
                array_push($arrImages,$objResult->fields['spez_field_16']);
                array_push($arrImages,$objResult->fields['spez_field_17']);
                array_push($arrImages,$objResult->fields['spez_field_18']);
                array_push($arrImages,$objResult->fields['spez_field_19']);
                array_push($arrImages,$objResult->fields['spez_field_20']);

                array_push($arrUploads,$objResult->fields['attachment']);
                array_push($arrUploads,$objResult->fields['spez_field_25']);
                array_push($arrUploads,$objResult->fields['spez_field_26']);
                array_push($arrUploads,$objResult->fields['spez_field_27']);
                array_push($arrUploads,$objResult->fields['spez_field_28']);
                array_push($arrUploads,$objResult->fields['spez_field_29']);

                array_push($arrRSS,$objResult->fields['rss_file']);
                $objResult->MoveNext();
            }
        }

        $obj_file = new \File();

        //del images
        foreach($arrImages as $arrKey => $arrFile) {
            //thumb
            if (file_exists($this->mediaPath."thumbs/".$arrFile) && !empty($arrFile)) {
                $this->dirLog = $obj_file->delFile($this->mediaPath, $this->mediaWebPath, "thumbs/".$arrFile);
            }

            //picture
            if (file_exists($this->mediaPath."images/".$arrFile) && !empty($arrFile)) {
                $this->dirLog = $obj_file->delFile($this->mediaPath, $this->mediaWebPath, "images/".$arrFile);
            }
        }

        //del uploads
        foreach($arrUploads as $arrKey => $arrFile) {
            //file
            if (file_exists($this->mediaPath."uploads/".$arrFile) && !empty($arrFile)) {
                $this->dirLog = $obj_file->delFile($this->mediaPath, $this->mediaWebPath, "uploads/".$arrFile);
            }
        }

        //del rss
        foreach($arrRSS as $arrKey => $arrFile) {
            //file
            if (file_exists($this->mediaPath."ext_feeds/".$arrFile) && !empty($arrFile)) {
                $this->dirLog = $obj_file->delFile($this->mediaPath, $this->mediaWebPath, "ext_feeds/".$arrFile);
            }
        }

        if ($this->dirLog != "error") {
            $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_dir WHERE id='$id'");
            $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_rel_dir_cat WHERE dir_id='".$id."'");
            $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_rel_dir_level WHERE dir_id='".$id."'");
            $this->restoreVoting($id);
            $this->strOkMessage = $_ARRAYLANG['TXT_DIR_FEED_SUCCESSFULL_DEL'];
            $this->dirLog ="";
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_DIR_CORRUPT_DEL'];
        }
    }


    /**
    * delete level
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function delLevel($levelId)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //del Level
        $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_levels WHERE id='$levelId'");
        $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_rel_dir_level WHERE level_id='".$levelId."'");

        if ($objResult !== false) {
            //del subLevel
            $status = $this->delSublevel($levelId);
        } else {
            $status =  $_ARRAYLANG['TXT_LEVEL_CORRUPT_DEL'];
        }

        if ($status == '') {
            $this->strOkMessage = $_ARRAYLANG['TXT_LEVEL_SUCCESSFULL_DEL'];;
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_LEVEL_CORRUPT_DEL'];
        }
    }


    /**
    * delete sublevel
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function delSublevel($levelId)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //get sublevel ids
        $objResult = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_directory_levels WHERE parentid='".$levelId."'");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $sublevelId[]   = $objResult->fields['id'];
                $objResult->MoveNext();
            };
        }

        //search sublevels
        if (!empty($sublevelId)) {
            foreach($sublevelId as $i => $sublevelId2) {
                //check next subLevel id
                $objResult = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_directory_levels WHERE parentid='".$sublevelId2."'");

                if ($objResult !== false) {
                    //del subLevel
                    $this->delSublevel($sublevelId2);
                }
            }
        }

        //del sublevels
        if (!empty($sublevelId)) {
            foreach($sublevelId as $i => $sublevelId2) {
                //del level
                $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_levels WHERE id='".$sublevelId2."'");

                if ($objResult === false) {
                    $status .=  "error";
                }
            }
        }
    }

    /**
    * delete selected category
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function delCategorie($catId)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //del categorie
        $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_categories WHERE id='$catId'");
        $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_rel_dir_cat WHERE cat_id='".$catId."'");

        //del subcategories
        $this->deleteSubcategorie($catId);

        $this->strOkMessage = $_ARRAYLANG['TXT_DIR_CAT_SUCCESSFULL_DEL'];
    }


    function deleteSubcategorie($id)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //get subcat id
        $objResult = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_directory_categories WHERE parentid='".$id."'");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $subcatId[] = $objResult->fields['id'];
                $objResult->MoveNext();
            };
        }


        //search subcats
        if (!empty($subcatId)) {
            foreach($subcatId as $i => $subcatId2) {
                //check next subcat id
                $objResult = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_directory_categories WHERE parentid='$subcatId2'");

                if ($objResult !== false) {
                    //del subcategories
                    $this->deleteSubcategorie($subcatId2);
                }
            }
        }

        //del subcats and files
        if (!empty($subcatId)) {
            foreach($subcatId as $i => $subcatId2) {
                //del categorie
                $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_categories WHERE id='$subcatId2'");
                $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_rel_dir_cat WHERE cat_id='".$subcatId2."'");
            }
        }
    }


    /**
    * change status of category or level
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function status()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        if (isset($_GET['status'])) {
            if ($_GET['act'] == "levels") {
                //get id and status
                $levelId        = intval($_GET['id']);
                $levelStatus    = intval($_GET['status']);

                //change status
                $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_levels SET status='".$levelStatus."' WHERE id='".$levelId."'");

                \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?cmd=Directory&act=levels');
                exit;
            } else {
                //get id and status
                $catId      = intval($_GET['id']);
                $catStatus  = intval($_GET['status']);

                //change status
                $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_categories SET status='".$catStatus."' WHERE id='".$catId."'");

                \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?cmd=Directory');
                exit;
            }
        }
    }


    /**
    * Save changes to the display order of categories
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function catOrder()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //get values
        foreach ($_POST['displayorder'] as $catKey => $value)
        {
            //update changes
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_categories SET displayorder=".intval($value)." WHERE id=".intval($catKey));
        }
        //status
        $this->strOkMessage = $_ARRAYLANG['TXT_DIR_CHANGES_SAVED'];
    }


    /**
    * Save changes to the display order of levels
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function levelOrder()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //get values
        foreach ($_POST['displayorder'] as $levelKey => $value)
        {
            //update changes
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_levels SET displayorder=".intval($value)." WHERE id=".intval($levelKey));
        }

        //status
        if ($objResult !== false) {
            $this->strOkMessage = $_ARRAYLANG['TXT_DIR_CHANGES_SAVED'];
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_ORDER_CORRUPT_EDIT'];
        }
    }


    /**
    * edit selected category
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function editCategorie()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //get categories
        $catId = intval($_GET['id']);
        $categories = $this->getSearchCategories($catId);

        //get categorie data
        $objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_categories WHERE id = '$catId'");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $catName            = $objResult->fields['name'];
                $catDescription     = $objResult->fields['description'];
                $catMetadesc        = $objResult->fields['metadesc'];
                $catMetakeys        = $objResult->fields['metakeys'];
                $catParentid        = $objResult->fields['parentid'];
                $catShowEntries     = $objResult->fields['showentries'];
                $objResult->MoveNext();
            }
        }

        if ($catShowEntries == '1') {
            $chechedNo  = '';
            $chechedYes = 'checked';
        } else {
            $chechedNo  = 'checked';
            $chechedYes = '';
        }

        // initialize variables
        $this->_objTpl->loadTemplateFile('module_directory_categories_edit.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_DIR_EDIT_CATEGORIE'];
        $this->_objTpl->setVariable(array(
            'TXT_EDIT_NAME'                 => $_ARRAYLANG['TXT_DIR_TITLE'],
            'TXT_EDIT_DESCRIPTION'          => $_ARRAYLANG['TXT_DIR_DESCRIPTION'],
            'TXT_CATEGORY'                  => $_ARRAYLANG['TXT_DIR_CATEGORIE'],
            'TXT_EDIT_CATEGORY'             => $_ARRAYLANG['TXT_DIR_EDIT_CATEGORIE'],
            'TXT_NEW_CATEGORY'              => $_ARRAYLANG['TXT_DIR_NEW_CATEGORIE'],
            'TXT_EDIT_METADESC'             => $_ARRAYLANG['TXT_DIR_META_DESC'],
            'TXT_EDIT_METAKEYS'             => $_ARRAYLANG['TXT_DIR_META_KEYS'],
            'TXT_EDIT_REQUIRED_FIELDS'      => $_ARRAYLANG['TXT_DIR_REQUIRED_FIELDS'],
            'TXT_EDIT_DELETE'               => $_ARRAYLANG['TXT_DIR_DEL'],
            'TXT_EDIT_SAVE_CHANGES'         => $_ARRAYLANG['TXT_DIR_CHANGES_SAVE'],
            'TXT_EDIT_FIELDS_REQUIRED'      => $_ARRAYLANG['TXT_DIR_FILL_ALL'],
            'CATEGORY'                      => $categories,
            'CATEGORY_NAME'                 => $catName,
            'CATEGORY_DESCRIPTION'          => $catDescription,
            'CATEGORY_METADESC'             => $catMetadesc,
            'CATEGORY_METAKEYS'             => $catMetakeys,
            'CATEGORY_PARENTID'             => $catParentid,
            'CATEGORY_ID'                   => $catId,
            'TXT_SHOW_ENTRIES'              => $_ARRAYLANG['TXT_SHOW_ENTRIES'],
            'TXT_YES'                       => $_ARRAYLANG['TXT_YES'],
            'TXT_NO'                        => $_ARRAYLANG['TXT_NO'],
            'CHECKED_NO'                    => $chechedNo,
            'CHECKED_YES'                   => $chechedYes,
        ));
        //save changes
        $this->updateCategorie();
    }


    /**
    * update category
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function updateCategorie()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //get post data
        if (isset($_POST['edit_submit'])) {
            $catCategorie       = intval($_POST['edit_category']);
            $catParentid        = intval($_POST['edit_parentid']);
            $catName            = contrexx_strip_tags($_POST['edit_name']);
            $catDescription     = contrexx_strip_tags($_POST['edit_description']);
            $catMetadesc        = contrexx_strip_tags($_POST['edit_metadesc']);
            $catMetakeys        = contrexx_strip_tags($_POST['edit_metakeys']);
            $catShowEntries     = contrexx_strip_tags($_POST['edit_showentries']);
            $catId              = intval($_POST['edit_id']);

            //check parent id
            if ($catCategorie == $catId) {
                $catParentid = $catParentid;
            } else {
                $catParentid = $catCategorie;
            }

            //update categorie
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_categories SET
                                                          name='".$catName."',
                                                          description='".$catDescription."',
                                                          parentid=".$catParentid.",
                                                          metadesc='".$catMetadesc."',
                                                          metakeys='".$catMetakeys."',
                                                          showentries='".$catShowEntries."' WHERE id='".$catId."'");
            //status and back to ooverview
            if ($objResult !== false) {
                $this->showCategories();
                $this->strOkMessage = $_ARRAYLANG['TXT_CAT_SUCCESSFULL_EDIT'];
            } else {
                $this->strErrMessage = $_ARRAYLANG['TXT_CAT_CORRUPT_EDIT'];;
            }
        }
    }


    /**
    * Create a new entry
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function newEntry()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //get categories/levels
        $categories = $this->getCategories('', 1);
        $levels = $this->getLevels('', 1);
        $platforms = $this->getPlatforms('', 1);

        $this->_objTpl->loadTemplateFile('module_directory_entry_add.html',true,true);

        if ($this->_isGoogleMapEnabled()) {
            $this->_objTpl->addBlockFile('DIRECTORY_GOOGLEMAP_JAVASCRIPT_BLOCK', 'direcoryGoogleMapJavascript', 'module_directory_googlemap_include.html');
        }

        $this->pageTitle = $_ARRAYLANG['TXT_DIR_ADD_ENTREE'];

        //get inputfields
        $objFWUser = \FWUser::getFWUserObject();
        $this->getInputfields($objFWUser->objUser->getId(), "add", "", "backend");

        // initialize variables
        $this->_objTpl->setVariable(array(
            'TXT_NEW_ENTRY'             => $_ARRAYLANG['TXT_DIR_NEW_ENTREE'],
            'TXT_NAME'                  => $_ARRAYLANG['TXT_DIR_TITLE'],
            'TXT_DESCRIPTION'           => $_ARRAYLANG['TXT_DIR_DESCRIPTION'],
            'TXT_CATEGORY'              => $_ARRAYLANG['TXT_DIR_CATEGORIE'],
            'TXT_LEVEL'                 => $_ARRAYLANG['TXT_LEVEL'],
            'TXT_OS'                    => $_ARRAYLANG['TXT_DIR_PLATFORM'],
            'TXT_LANGUAGE'              => $_ARRAYLANG['TXT_DIR_LANG'],
            'TXT_ADDEDBY'               => $_ARRAYLANG['TXT_DIR_ADDED_BY'],
            'TXT_LINKS'                 => $_ARRAYLANG['TXT_DIR_RELATED_LINKS'],
            'TXT_REQUIRED_FIELDS'       => $_ARRAYLANG['TXT_DIR_REQUIRED_FIELDS'],
            'TXT_ADD'                   => $_ARRAYLANG['TXT_DIR_ADD'],
            'TXT_FIELDS_REQUIRED'       => $_ARRAYLANG['TXT_DIR_FILL_ALL'],
            'TXT_ATTACHMENT'            => $_ARRAYLANG['TXT_DIRECTORY_ATTACHMENT'],
            'TXT_PLEASE_SELECT'         => $_ARRAYLANG['TXT_DIRECTORY_PLEASE_CHOSE'],
            'TXT_SELECT'                => $_ARRAYLANG['TXT_DIRECTORY_SELECT_ALL'],
            'TXT_DESELECT'              => $_ARRAYLANG['TXT_DIRECTORY_DESELECT_ALL'],
            'ADDED_BY'                  => $userName,
            'CATEGORY'                  => $categories,
            'LEVELS'                    => $levels,
            'LANGUAGE'                  => $languages,
            'OS'                        => $platforms,
            'TXT_DIR_GEO_SPECIFY_ADDRESS_OR_CHOOSE_MANUALLY'   => $_ARRAYLANG['TXT_DIR_GEO_SPECIFY_ADDRESS_OR_CHOOSE_MANUALLY'],
            'TXT_DIR_GEO_TOO_MANY_QUERIES'   => $_ARRAYLANG['TXT_DIR_GEO_TOO_MANY_QUERIES'],
            'TXT_DIR_GEO_SERVER_ERROR'   => $_ARRAYLANG['TXT_DIR_GEO_SERVER_ERROR'],
            'TXT_DIR_GEO_NOT_FOUND'     => $_ARRAYLANG['TXT_DIR_GEO_NOT_FOUND'],
            'TXT_DIR_GEO_SUCCESS'       => $_ARRAYLANG['TXT_DIR_GEO_SUCCESS'],
            'TXT_DIR_GEO_MISSING'       => $_ARRAYLANG['TXT_DIR_GEO_MISSING'],
            'TXT_DIR_GEO_UNKNOWN'       => $_ARRAYLANG['TXT_DIR_GEO_UNKNOWN'],
            'TXT_DIR_GEO_UNAVAILABLE'   => $_ARRAYLANG['TXT_DIR_GEO_UNAVAILABLE'],
            'TXT_DIR_GEO_BAD_KEY'       => $_ARRAYLANG['TXT_DIR_GEO_BAD_KEY'],
            'DIRECTORY_GOOGLE_API_KEY'  => $_CONFIG["googleMapsAPIKey"],
            'DIRECTORY_START_X'         => 'null',
            'DIRECTORY_START_Y'         => 'null',
            'DIRECTORY_START_ZOOM'      => 'null',
            'DIRECTORY_ENTRY_NAME'      => 'null',
            'DIRECTORY_ENTRY_COMPANY'   => 'null',
            'DIRECTORY_ENTRY_STREET'    => 'null',
            'DIRECTORY_ENTRY_ZIP'       => 'null',
            'DIRECTORY_ENTRY_LOCATION'  => 'null',
            'DIRECTORY_MAP_LON_BACKEND'     => $this->googleMapStartPoint['lon'],
            'DIRECTORY_MAP_LAT_BACKEND'     => $this->googleMapStartPoint['lat'],
            'DIRECTORY_MAP_ZOOM_BACKEND'    => $this->googleMapStartPoint['zoom'],
            'IS_BACKEND'                => 'true',
        ));

        if ($this->settings['levels']['value']=='1') {
            $this->_objTpl->parse('levels');
        } else {
            $this->_objTpl->hideBlock('levels');
        }

        if (isset($_POST['new_submit'])) {
            //add entry
            $status = $this->addFeed();

            if ($status == "ok") {
                $this->createRSS();

                //back to categories
                if ($this->settings['showConfirm']['value'] == 1) {
                    if ($this->settings['status']['value'] == 0) {
                        $this->showConfirm();
                    } else {
                        $this->showFiles('','');
                    }
                } else {
                    $this->showCategories();
                }
            }
        }
    }


    /**
    * Show all added files
    * @access   public
    * @param    string  $catId
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function showFiles($catId, $levelId)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        if (isset($catId)) {
            $catIdSort = "&cat=".$catId;
        }

        if (isset($levelId)) {
            $levelIdSort = "&level=".$levelId;
        }

        // initialize variables
        $this->_objTpl->loadTemplateFile('module_directory_files.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_DIR_FILE_MANAGEMENT'];
        $this->_objTpl->setVariable(array(
            'TXT_NAME'                      => $_ARRAYLANG['TXT_DIRECTORY_NAME'],
            'TXT_DESCRIPTION'               => $_ARRAYLANG['TXT_DIR_DESCRIPTION'],
            'TXT_DATE'                      => $_ARRAYLANG['TXT_DIR_DATE'],
            'TXT_OPTIONS'                   => $_ARRAYLANG['TXT_DIR_OPTIONS'],
            'TXT_SPEZ_SORT'                 => $_ARRAYLANG['TXT_DIRECTORY_SPEZ_SORT'],
            'TXT_AUTHOR'                    => $_ARRAYLANG['TXT_DIRECTORY_AUTHOR'],
            'TXT_VOTING'                    => $_ARRAYLANG['TXT_DIRECTORY_VOTING'],
            'TXT_EDIT'                      => $_ARRAYLANG['TXT_DIR_EDIT'],
            'TXT_DELETE'                    => $_ARRAYLANG['TXT_DIR_DEL'],
            'TXT_RESTORE_VOTING'            => $_ARRAYLANG['TXT_DIR_RESTORE_VOTING'],
            'TXT_FILE_SEARCH'               => $_ARRAYLANG['TXT_DIR_FILE_SEARCH'],
            'TXT_SEARCH'                    => $_ARRAYLANG['TXT_DIR_SEARCH'],
            'TXT_ACTION'                    => $_ARRAYLANG['TXT_DIR_ACTION'],
            'TXT_HITS'                      => $_ARRAYLANG['TXT_DIR_HITS'],
            'TXT_LIST'                      => $_ARRAYLANG['TXT_DIR_LIST'],
            'TXT_CONFIRM_DELETE_DATA'       => $_ARRAYLANG['TXT_DIR_CONFIRM_DEL'],
            'TXT_ACTION_IS_IRREVERSIBLE'    => $_ARRAYLANG['TXT_DIR_ACTION_IS_IRREVERSIBLE'],
            'CATID_SORT'                    => $catIdSort,
        ));

        // Sort
        if (isset($_GET['sort']) || empty($_SESSION['order'])) {
            switch ($_GET['sort'])
            {
                case 'date':
                $_SESSION['order']=($_SESSION['order']=="files.date desc")? "files.date asc" : "files.date desc";
                break;
                case 'name':
                $_SESSION['order']=($_SESSION['order']=="files.title desc")? "files.title asc" : "files.title desc";
                break;
                case 'hits':
                $_SESSION['order']=($_SESSION['order']=="files.hits desc")? "files.hits asc" : "files.hits desc";
                break;
                case 'spez':
                $_SESSION['order']=($_SESSION['order']=="files.spezial desc")? "files.spezial asc" : "files.spezial desc";
                break;
                case 'addedby':
                $_SESSION['order']=($_SESSION['order']=="files.addedby desc")? "files.addedby asc" : "files.addedby desc";
                break;
                default:
                $_SESSION['order'] = "files.id desc";
                break;
            }
        }

        $db   = '';
        $term = isset($_REQUEST['term']) ? htmlspecialchars($_REQUEST['term'], ENT_QUOTES, CONTREXX_CHARSET) : '';
        if ($catId != '') {
            $where  = " AND files.id = rel_cat.dir_id AND rel_cat.cat_id = '".$catId."'";
            $db     = DBPREFIX."module_directory_rel_dir_cat AS rel_cat,";
        } elseif ($levelId != '') {
            $where=" AND files.id = rel_level.dir_id AND rel_level.level_id = '".$levelId."'";
            $db     = DBPREFIX."module_directory_rel_dir_level AS rel_level,";
        } elseif (!empty($term)) {
            //search term
            $where.=" AND (files.title LIKE '%".$term."%' OR files.searchkeys LIKE '%".$term."%' OR files.description LIKE '%".$term."%') ";
        }
        else {
            $where='';
        }

        //create query
        $query="SELECT   files.title AS title,
                         files.id AS id,
                         files.description AS description,
                         files.hits as hits,
                         files.spezial AS spezial,
                         files.date AS date,
                         files.addedby AS addedby
                    FROM ".$db." ".DBPREFIX."module_directory_dir AS files
                   WHERE files.status = 1 ".$where."
                   GROUP BY files.id
                   ORDER BY ".$_SESSION['order']."";

        ////// paging start /////////
        $pagingLimit    = intval($this->settings['pagingLimit']['value']);
        $objResult      = $objDatabase->Execute($query);
        $count          = $objResult->RecordCount();
        $pos            = isset($_GET['pos']) ? contrexx_input2int($_GET['pos']) : 0;
        $paging         = getPaging($count, $pos, "&cmd=Directory&act=files&term=".$term.$catIdSort.$levelIdSort, "<b>".$_ARRAYLANG['TXT_DIRECTORY_FEEDS']."</b>", true, $pagingLimit);
        ////// paging end /////////

        $objResult = $objDatabase->SelectLimit($query, $pagingLimit, $pos);
        $count = $objResult->RecordCount();

        $i=0;
        if ($objResult !== false) {
            $file_array = array();
            while (!$objResult->EOF) {
                $file_array[$i] = array(
                    'filename' => $objResult->fields['filename'],
                    'title'    => $objResult->fields['title'],
                    'id'       => $objResult->fields['id'],
                    'description' => $objResult->fields['description'],
                    'hits'     => $objResult->fields['hits'],
                    'spez'     => $objResult->fields['spezial'],
                    'date'     => $objResult->fields['date'],
                    'addedby'  => $objResult->fields['addedby'],
                );
                $i++;
                $objResult->MoveNext();
            }
        }

        $i=0;
        $this->_objTpl->setCurrentBlock('filesRow');
        if (!empty($file_array))
        {
            $catName = '';
            //show files
            foreach ($file_array as $file)
            {

                //get categorie name
                $catId          = isset($file['catid']) ? $file['catid'] : '';

                $objResult_Name = $objDatabase->Execute("SELECT id, name FROM ".DBPREFIX."module_directory_categories WHERE id='".$catId."'");
                if ($objResult_Name !== false) {
                    while (!$objResult_Name->EOF) {
                        $catName    = $objResult_Name->fields['name'];
                        $objResult_Name->MoveNext();
                    };
                }

                //check paging
                if (!$count>$pagingLimit) {
                    $paging = "";
                }

                //get votes
                $this->getVotes($file['id']);



                //initialize variables
                ($i % 2)? $class = "row2" : $class = "row1";
                $this->_objTpl->setVariable(array(
                    'FILES_ROW'                 => $class,
                    'FILES_ID'                  => $file['id'],
                    'FILES_NAME'                => $file['title'],
                    'FILES_AUTHOR'              => $this->getAuthor($file['addedby']),
                    'FILES_DESCRIPTION'         => substr($file['description'], 0, 200),
                    'FILES_SPEZ_SORT'           => $file['spez'],
                    'FILES_DATE'                => date("d.m.Y H:i:s",$file['date']),
                    'FILES_HITS'                => $file['hits'],
                    'FILES_PAGING'              => $paging,
                    'FILES_CAT'                 => $catName,
                    'FILES_CAT_ID'              => $catId,
                    'FILES_CHECKBOX'            =>
                        "<input type=\"checkbox\" title=\"Select ".
                        $file['filename']."\" name=\"formSelected[]\" value=\"".
                        $file['id']."\" />",
                ));

                $this->_objTpl->parseCurrentBlock('filesRow');
                $i++;
            }

            $this->_objTpl->setVariable(array(
                'TXT_SELECT_ACTION'     => $_ARRAYLANG['TXT_DIR_CHOOSE_ACTION'],
                'TXT_DELETE'            => $_ARRAYLANG['TXT_DIR_DEL'],
                'TXT_SELECT_ALL'        => $_ARRAYLANG['TXT_DIRECTORY_SELECT_ALL'],
                'TXT_DESELECT_ALL'      => $_ARRAYLANG['TXT_DIRECTORY_DESELECT_ALL'],
                'TXT_MAKE_SELECTION'    => $_ARRAYLANG['TXT_DIR_MAKE_SELECTION'],
                'TXT_MOVE'              => $_ARRAYLANG['TXT_MOVE'],
            ));
            $this->_objTpl->parse('importSelectAction');
        }
        else {
            // initialize variables
            $this->_objTpl->setVariable(array(
                'NO_FILES_FOUND'                => $_ARRAYLANG['TXT_DIR_NO_FILES_FOUND'],
            ));

            $this->_objTpl->hideBlock('filesRow');
            $this->_objTpl->parseCurrentBlock('nofilesRow');
        }
    }


    /**
    * get fileextension
    * @access   public
    * @param    string  $file
    * @return   string  $icon
    */
    function getExtension($file)
    {
        //get fileinfo
        $info   = pathinfo($file);
        $exte   = $info['extension'];

        //check extension
        if (empty($exte)) {
            $icon = "";
        } else {
            $icon = $exte.".gif";
            if (!file_exists( $this->imagePath."/".$icon)) {
                $icon = $this->imageWebPath."/_blank.gif";
            } else {
                $icon = $this->imageWebPath."/".$exte.".gif";;
            }
        }

        return $icon;
    }


    /**
    * edit selected file
    * @access   public
    * @param    string  $id
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function editFile($id)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        $objResult = $objDatabase->Execute("SELECT  spezial, premium FROM ".DBPREFIX."module_directory_dir WHERE id = '$id'");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $spezSort       = $objResult->fields['spezial'];
                $premium        = $objResult->fields['premium'];
                $objResult->MoveNext();
            }
        }

        if ($premium == 1) {
            $premium = "checked";
        } else {
            $premium = "";
        }

        //get categorie/levels
        $categorieDe    = $this->getCategories($id, 1);
        $categorieSe    = $this->getCategories($id, 2);
        $levelDe        = $this->getLevels($id, 1);
        $levelSe        = $this->getLevels($id, 2);

        // initialize variables
        $this->_objTpl->loadTemplateFile('module_directory_entry_edit.html',true,true);
        $this->pageTitle =  $_ARRAYLANG['TXT_DIR_EDIT_FILE'];

        //get inputfields
        $this->getInputfields("", "edit", $id, "backend");

        if ($this->_isGoogleMapEnabled()) {
            $this->_objTpl->addBlockfile('DIRECTORY_GOOGLEMAP_JAVASCRIPT_BLOCK', 'direcoryGoogleMapJavascript', 'module_directory_googlemap_include.html');
        }

        $this->_objTpl->setVariable(array(
            'TXT_NAME'                  => $_ARRAYLANG['TXT_DIR_NEW_ENTREE'],
            'TXT_EDIT_ENTREE'           => $_ARRAYLANG['TXT_DIRECTORY_EDIT_FEED'],
            'TXT_CATEGORY'              => $_ARRAYLANG['TXT_DIR_CATEGORIE'],
            'TXT_REQUIRED_FIELDS'       => $_ARRAYLANG['TXT_DIR_REQUIRED_FIELDS'],
            'TXT_UPDATE'                => $_ARRAYLANG['TXT_DIR_CHANGES_SAVE'],
            'TXT_FIELDS_REQUIRED'       => $_ARRAYLANG['TXT_DIR_FILL_ALL'],
            'CATEGORY_DESELECTED'       => $categorieDe,
            'CATEGORY_SELECTED'         => $categorieSe,
            'LEVEL_DESELECTED'          => $levelDe,
            'LEVEL_SELECTED'            => $levelSe,
            'ID'                        => $id,
            'TXT_DIRECTORY_SPEZ_SORT'   => $_ARRAYLANG['TXT_DIRECTORY_SPEZ_SORT'],
            'TXT_DIRECTORY_SORT'        => $_ARRAYLANG['TXT_DIRECTORY_SORT'],
            'DIRECTORY_SORT'            => $spezSort,
            'DIRECTORY_PREMIUM'         => $premium,
            'TXT_DIRECTORY_PREMIUM'     => $_ARRAYLANG['TXT_PREMIUM'],
            'TXT_LEVEL'                 => $_ARRAYLANG['TXT_LEVEL'],
            'DIRECTORY_GOOGLE_API_KEY'  => $_CONFIG["googleMapsAPIKey"],
            'DIRECTORY_START_X'         => 'null',
            'DIRECTORY_START_Y'         => 'null',
            'DIRECTORY_START_ZOOM'      => 'null',
            'DIRECTORY_ENTRY_NAME'      => 'null',
            'DIRECTORY_ENTRY_COMPANY'   => 'null',
            'DIRECTORY_ENTRY_STREET'    => 'null',
            'DIRECTORY_ENTRY_ZIP'       => 'null',
            'DIRECTORY_ENTRY_LOCATION'  => 'null',
            'DIRECTORY_MAP_LON_BACKEND' => $this->googleMapStartPoint['lon'],
            'DIRECTORY_MAP_LAT_BACKEND' => $this->googleMapStartPoint['lat'],
            'DIRECTORY_MAP_ZOOM_BACKEND'=> $this->googleMapStartPoint['zoom'],
            'IS_BACKEND'                => 'true',
        ));

        if ($this->settings['levels']['value']=='1') {
            $this->_objTpl->parse('levels');
        } else {
            $this->_objTpl->hideBlock('levels');
        }

        //update file
        $this->updateFile('');

        if (isset($_POST['edit_submit'])) {
            $this->showFiles('','');
            $this->strOkMessage = $_ARRAYLANG['TXT_DIR_FEED_SUCCESSFULL_EDIT'];
        }
    }


    /**
    * show confirm form
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function showConfirm()
    {
        global $_CONFIG, $objDatabase,  $_ARRAYLANG;

        //check paging position
        if (!isset($_GET['pos'])) {
            $_GET['pos']='';
        }

        // initialize variables
        $this->_objTpl->loadTemplateFile('module_directory_entry_confirm.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_DIR_CONFIRM_ENTREE'];

        $this->_objTpl->setVariable(array(
            'TXT_CONFIRMLIST'               => $_ARRAYLANG['TXT_DIR_CONFIRM_LIST'],
            'TXT_CONFIRMFILE'               => $_ARRAYLANG['TXT_DIR_CONFIRM'],
            'TXT_NAME'                      => $_ARRAYLANG['TXT_DIRECTORY_NAME'],
            'TXT_DESCRIPTION'               => $_ARRAYLANG['TXT_DIR_DESCRIPTION'],
            'TXT_DATE'                      => $_ARRAYLANG['TXT_DIR_DATE'],
            'TXT_OPTIONS'                   => $_ARRAYLANG['TXT_DIR_OPTIONS'],
            'TXT_PLATFORM'                  => $_ARRAYLANG['TXT_DIR_PLATFORM'],
            'TXT_LANGUAGE'                  => $_ARRAYLANG['TXT_DIR_LANG'],
            'TXT_EDIT'                      => $_ARRAYLANG['TXT_DIR_EDIT'],
            'TXT_DELETE'                    => $_ARRAYLANG['TXT_DIR_DEL'],
            'TXT_FILE_SEARCH'               => $_ARRAYLANG['TXT_DIR_FILE_SEARCH'],
            'TXT_SEARCH'                    => $_ARRAYLANG['TXT_DIR_SEARCH'],
            'TXT_ACTION'                    => $_ARRAYLANG['TXT_DIR_ACTION'],
            'TXT_ADDEDBY'                   => $_ARRAYLANG['TXT_DIRECTORY_AUTHOR'],
            'TXT_CONFIRM_DELETE_DATA'       => $_ARRAYLANG['TXT_DIR_CONFIRM_DEL'],
            'TXT_ACTION_IS_IRREVERSIBLE'    => $_ARRAYLANG['TXT_DIR_ACTION_IS_IRREVERSIBLE'],
            'CATID_SORT'                    => $catIdSort,
            'TXT_IMPORT_MAKE_SELECTION'     => $_ARRAYLANG['TXT_DIR_MAKE_SELECTION']
        ));


        // Sort
        if (isset($_GET['sort']) || empty($_SESSION['order'])) {
            $getSort = isset($_GET['sort']) ? $_GET['sort'] : '';
            switch ($getSort)
            {
                case 'date':
                $_SESSION['order']=($_SESSION['order']=="files.date desc")? "files.date asc" : "files.date desc";
                break;
                case 'name':
                $_SESSION['order']=($_SESSION['order']=="files.title desc")? "files.title asc" : "files.title desc";
                break;
                case 'addedby':
                $_SESSION['order']=($_SESSION['order']=="files.addedby desc")? "files.addedby asc" : "files.addedby desc";
                break;
                default:
                $_SESSION['order'] = "files.id desc";
                break;
            }
        }

        $pos= intval($_GET['pos']);

        if (isset($catId)) {
            $where=" AND catid=".$catId;
        } elseif (isset($_POST['term'])) {
            //check search term
            $term= htmlspecialchars($_POST['term'], ENT_QUOTES, CONTREXX_CHARSET);
            $where.=" AND (title LIKE '%".$term."%' OR filename LIKE '%".$term."%' OR description LIKE '%".$term."%') ";
        }
        else {
            $where=$term='';
        }

        //create query
        $query="SELECT * FROM ".DBPREFIX."module_directory_dir AS files WHERE status='0' ".$where." ORDER BY ".$_SESSION['order'];

        ////// paging start /////////
        $objResult = $objDatabase->Execute($query);
        $count = $objResult->RecordCount();
        if (!is_numeric($pos)) {
          $pos = 0;
        }
        if ($count>intval($_CONFIG['corePagingLimit'])) {
            $paging = getPaging($count, $pos, "&cmd=Directory&act=confirm", "<b>".$_ARRAYLANG['TXT_DIRECTORY_FEEDS']."</b>", true);
        }
        ////// paging end /////////


        $pagingLimit = intval($_CONFIG['corePagingLimit']);
        $objResult = $objDatabase->SelectLimit($query, $pagingLimit, $pos);
        $count = $objResult->RecordCount();

        //get files
        $i=0;
        if ($objResult !== false) {
            $file_array = array();
            while (!$objResult->EOF) {
                $file_array[$i] = array(
                    'title'       => $objResult->fields['title'],
                    'id'          => $objResult->fields['id'],
                    'description' => $objResult->fields['description'],
                    'catid'       => $objResult->fields['catid'],
                    'addedby'     => $objResult->fields['addedby'],
                    'language'    => $objResult->fields['language'],
                    'platform'    => $objResult->fields['platform'],
                    'date'        => $objResult->fields['date'],
                );
                $i++;
                $objResult->MoveNext();
            }
        }


        //show files
        $i=0;
        $this->_objTpl->setCurrentBlock('filesRow');
        if (!empty($file_array)) {
            foreach ($file_array as $file) {
                //check paging
                if (!$count>intval($_CONFIG['corePagingLimit'])) {
                    $paging = "";
                }

                //get author
                $addedBy = $this->getAuthor($file['addedby']);

                ($i % 2)? $class = "row2" : $class = "row1";

                // initialize variables
                $this->_objTpl->setVariable(array(
                    'FILES_ROW'                 => $class,
                    'FILES_ID'                  => $file['id'],
                    'FILES_NAME'                => stripslashes($file['title']),
                    'FILES_DESCRIPTION'         => substr(stripslashes($file['description']), 0, 200),
                    'FILES_LANGUAGE'            => $file['language'],
                    'FILES_PLATFORM'            => $file['platform'],
                    'FILES_DATE'                => date("d.m.Y H:i:s",$file['date']),
                    'FILES_ADDEDBY'             => $addedBy,
                    'FILES_PAGING'              => $paging,
                    'FILES_CHECKBOX'            =>
                        "<input type=\"checkbox\" title=\"Select ".
                        $file['filename']."\" name=\"formSelected[]\" value=\"".
                        $file['id']."\" />",
                ));

                $this->_objTpl->parseCurrentBlock('filesRow');
                $i++;
            }

            $this->_objTpl->setVariable(array(
                'TXT_IMPORT'            => $_ARRAYLANG['TXT_DIR_CONFIRM'],
                'TXT_SELECT_ACTION'     => $_ARRAYLANG['TXT_DIR_CHOOSE_ACTION'],
                'TXT_DELETE'            => $_ARRAYLANG['TXT_DIR_DEL'],
                'TXT_SELECT_ALL'        => $_ARRAYLANG['TXT_DIRECTORY_SELECT_ALL'],
                'TXT_DESELECT_ALL'      => $_ARRAYLANG['TXT_DIRECTORY_DESELECT_ALL'],

            ));
            $this->_objTpl->parse('importSelectAction');
        } else {
            // initialize variables
            $this->_objTpl->setVariable(array(
                'NO_FILES_FOUND'                => $_ARRAYLANG['TXT_DIRECTORY_EMPTY_CONFIRMLIST'],
            ));

            $this->_objTpl->hideBlock('filesRow');
            $this->_objTpl->parseCurrentBlock('nofilesRow');
        }
    }


    /**
    * detail entry
    *
    * show confirm form
    * @access   public
    * @param    string    $id
    * @global    array
    * @global    ADONewConnection
    * @global    array
    */
    function detailEntry($id)
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        //get categories/levels
        $categories = $this->getCategories($id, 2);
        $levels     = $this->getLevels($id, 2);

        // initialize variables
        $this->_objTpl->loadTemplateFile('module_directory_confirm_details.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_DIR_EDIT_FILE'];

        //get inputfields
        $objFWUser = \FWUser::getFWUserObject();
        $this->getInputfields(htmlentities($objFWUser->objUser->getProfileAttribute('firstname')." ".$objFWUser->objUser->getProfileAttribute('lastname'), ENT_QUOTES, CONTREXX_CHARSET), "confirm", intval($id), "backend");

        $this->_objTpl->setVariable(array(
            'TXT_NAME'                  => $_ARRAYLANG['TXT_DIRECTORY_NAME'],
            'TXT_DESCRIPTION'           => $_ARRAYLANG['TXT_DIR_DESCRIPTION'],
            'TXT_CATEGORY'              => $_ARRAYLANG['TXT_DIR_CATEGORIE'],
            'TXT_LEVEL'                 => $_ARRAYLANG['TXT_LEVEL'],
            'TXT_BACK'                  => $_ARRAYLANG['TXT_DIR_BACK'],
            'TXT_CONFIRM'               => $_ARRAYLANG['TXT_DIR_CONFIRM'],
            'CATEGORY'                  => $categories,
            'LEVELS'                    => $levels,
            'ID'                        => $id,
            'FILE'                      => $file,
        ));

        if ($this->settings['levels']['value']=='1') {
            $this->_objTpl->parse('levels');
        } else {
            $this->_objTpl->hideBlock('levels');
        }
    }


    function confirmEntry_step1()
    {
        if (!isset($_GET['id'])) {
            foreach($_POST["formSelected"] as $feedName => $feedKey) {
                $this->confirmEntry_step2(intval($feedKey));
            }
        } else {
            $this->confirmEntry_step2(intval($_GET['id']));
        }
    }


    /**
    * show settings
    * @access   public
    * @global    array
    */
    function showSettings()
    {
        global $_ARRAYLANG;
        $this->_objTpl->loadTemplateFile('module_directory_settings.html',true,true);
        $this->pageTitle = $_ARRAYLANG['TXT_DIR_SETTINGS'];

        $this->_objTpl->setGlobalVariable(array(
            'TXT_SYSTEM'        => $_ARRAYLANG['TXT_DIR_SYSTEM'],
            'TXT_EMAIL'         => $_ARRAYLANG['TXT_DIRECTORY_MAIL_TEMLATES'],
            'TXT_GOOGLE'        => $_ARRAYLANG['TXT_DIRECTORY_GOOGLE_SEARCH'],
            'TXT_INPUTS'        => $_ARRAYLANG['TXT_DIR_INPUTS'],
            'TXT_HEADLINES'     => $_ARRAYLANG['TXT_DIRECTORY_HEADLINES'],
            'TXT_HOMECONTENT'   => $_ARRAYLANG['TXT_DIRECTORY_HOME_CONTENT'],

        ));

        if (!isset($_GET['tpl'])) {
            $_GET['tpl'] = "";
        }

        switch ($_GET['tpl']) {
            case 'system':
                $this->showSettings_system();
                break;

            case 'email':
                $this->showSettings_mail();
                break;

            case 'inputs':
                $this->showSettings_inputs();
                break;

            case 'headlines':
                $this->showSettings_headlines();
                break;
            case 'homecontent':
                $this->showSettings_homecontent();
                break;

            default:
                $this->showSettings_system();
                break;
        }

        $this->_objTpl->parse('requests_block');
    }


    function showSettings_system()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        // initialize variables
        $this->_objTpl->addBlockfile('SYSTEM_REQUESTS_CONTENT', 'requests_block', 'module_directory_settings_system.html');
        $this->_objTpl->addBlockFile('DIRECTORY_GOOGLEMAP_JAVASCRIPT_BLOCK', 'direcoryGoogleMapJavascript', 'module_directory_googlemap_include.html');

        $this->_objTpl->setVariable(array(
            'TXT_SYSTEM_VARIABLES_OVERVIEW'     => $_ARRAYLANG['TXT_DIR_SYSTEM_VARIABLES'],
            'TXT_DESCRIPTION'                   => $_ARRAYLANG['TXT_DIR_DESCRIPTION'],
            'TXT_VALUE'                         => $_ARRAYLANG['TXT_DIR_SYSTEM_VAlUE'],
            'TXT_SAVE_CHANGES'                  => $_ARRAYLANG['TXT_DIR_CHANGES_SAVE'],
        ));

        //get settings
        $i=0;
        $objResult = $objDatabase->Execute("SELECT setid,setname,setvalue,settyp FROM ".DBPREFIX."module_directory_settings WHERE settyp != '0' AND setid != '30' AND setname != 'googlemap_start_location' ORDER BY settyp DESC");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $allow_url_fopen = '';
                $this->_objTpl->setCurrentBlock('settingsOutput');
                if ($objResult->fields['settyp']== 1) {
                    $setValueField =
                        "<input type=\"text\" name=\"setvalue[".
                        $objResult->fields['setid']."]\" value=\"".
                        $objResult->fields['setvalue'].
                        "\" size='50' maxlength='250' />";
                } elseif ($objResult->fields['settyp']== 2) {
                    $true = "";
                    $false = "";
                    if ($objResult->fields['setvalue'] == 1) {
                        $true = "checked";
                    } else {
                        $false = "checked";
                    }
                    if (ini_get('allow_url_fopen') == false && $objResult->fields['setid'] == 19) {
                        $allow_url_fopen = "<font color='#ff0000'><i>(Please set the variable 'allow_url_fopen' to the value 1)</i></font>";
                    }
                    $setValueField =
                        "<input type=\"radio\" name=\"setvalue[".
                        $objResult->fields['setid']."]\" value=\"1\" ".
                        $true." />&nbsp;".
// TODO:  Use language variable here
                        "Aktiviert&nbsp;<input type=\"radio\" name=\"setvalue[".
                        $objResult->fields['setid']."]\" value=\"0\"".$false." />&nbsp;".
// TODO:  Use language variable here
                        "Deaktiviert&nbsp;&nbsp;&nbsp;".$allow_url_fopen;
                }

                ($i % 2)? $class = "row2" : $class = "row1";

                // initialize variables
                $this->_objTpl->setVariable(array(
                    'SETTINGS_ROWCLASS'     => $class,
                    'SETTINGS_SETVALUE'     => $setValueField,
                    'SETTINGS_DESCRIPTION'  => $_ARRAYLANG['TXT_'.strtoupper($objResult->fields['setname'])],
                ));
                $this->_objTpl->parseCurrentBlock('settingsOutput');
                $i++;
                $objResult->MoveNext();
            }
        }

        $arrLon = explode('.', $this->googleMapStartPoint['lon']);
        $arrLat = explode('.', $this->googleMapStartPoint['lat']);

        $googleMapHTML =
            '<tr><td style="vertical-align: top;">'.
            $_ARRAYLANG['TXT_DIR_GOOGLEMAP_STARTPOINT'].
            '</td><td><table border="0" cellspacing="0" cellpadding="0"><tr><td width="120">'.
            $_ARRAYLANG['TXT_DIR_F_STREET'].
            ':</td><td> <input style="width: 148px;" type="text" name="inputValue[street]" value="" /></td></tr><tr><td>'.
            $_ARRAYLANG['TXT_DIR_F_PLZ'].
            ':</td><td> <input style="width: 148px;" type="text" name="inputValue[zip]" value="" /></td></tr><tr><td>'.
            $_ARRAYLANG['TXT_DIR_CITY'].
            ':</td><td> <input style="width: 148px;" type="text" name="inputValue[city]" value="" /></td></tr><tr><td>'.
            $_ARRAYLANG['TXT_DIR_F_COUNTRY'].
            ':</td><td> <select style="width: 148px;" name="inputValue[country]">'.
            $this->getCountryMenuoptions('').'</select></td></tr></table><br />'.
            '<input type="button" onclick="getAddress();" value="'.
            $_ARRAYLANG['TXT_DIR_SEARCH_ADDRESS'].'" /><br /><br />'.
            $_ARRAYLANG['TXT_DIR_LON'].
            ': <input type="text" name="inputValue[lon]" value="'.
            $arrLon[0].'" style="width:22px;" maxlength="3" />'.
            '.<input type="text" name="inputValue[lon_fraction]" value="'.
            $arrLon[1].'" style="width:92px;" maxlength="15" /> '.
            $_ARRAYLANG['TXT_DIR_LAT'].
            ': <input type="text" name="inputValue[lat]" value="'.
            $arrLat[0].'" style="width:22px;" maxlength="15" />'.
            '.<input type="text" name="inputValue[lat_fraction]" value="'.
            $arrLat[1].'" style="width:92px;" maxlength="15" /> '.
            $_ARRAYLANG['TXT_DIR_ZOOM'].
            ': <input type="text" name="inputValue[zoom]" value="'.
            $this->googleMapStartPoint['zoom'].
            '" style="width:15px;" maxlength="2" /><br />'.
            '<span id="geostatus"></span>'.
            '<div id="gmap" style="margin:2px; border:1px solid;width: 400px; height: 300px;"></div>'.
            '<div id="loclayer" style="-moz-opacity: 0.85; '.
            'filter: alpha(opacity=85); background-color: #dedede; '.
            'padding: 2px; border: 1px solid; width: 198px; height: 48px; '.
            'position: relative; top: -270px; left: 200px;"></div>'.
            '</td></tr>';

        $this->_objTpl->setVariable(array(
            'TXT_DIR_GEO_SPECIFY_ADDRESS_OR_CHOOSE_MANUALLY'   => $_ARRAYLANG['TXT_DIR_GEO_SPECIFY_ADDRESS_OR_CHOOSE_MANUALLY'],
            'TXT_DIRECTORY_BROWSER_NOT_SUPPORTED'   => $_ARRAYLANG['TXT_DIRECTORY_BROWSER_NOT_SUPPORTED'],
            'TXT_DIR_GEO_TOO_MANY_QUERIES'   => $_ARRAYLANG['TXT_DIR_GEO_TOO_MANY_QUERIES'],
            'TXT_DIR_GEO_SERVER_ERROR'   => $_ARRAYLANG['TXT_DIR_GEO_SERVER_ERROR'],
            'TXT_DIR_GEO_NOT_FOUND'     => $_ARRAYLANG['TXT_DIR_GEO_NOT_FOUND'],
            'TXT_DIR_GEO_SUCCESS'       => $_ARRAYLANG['TXT_DIR_GEO_SUCCESS'],
            'TXT_DIR_GEO_MISSING'       => $_ARRAYLANG['TXT_DIR_GEO_MISSING'],
            'TXT_DIR_GEO_UNKNOWN'       => $_ARRAYLANG['TXT_DIR_GEO_UNKNOWN'],
            'TXT_DIR_GEO_UNAVAILABLE'   => $_ARRAYLANG['TXT_DIR_GEO_UNAVAILABLE'],
            'TXT_DIR_GEO_BAD_KEY'       => $_ARRAYLANG['TXT_DIR_GEO_BAD_KEY'],
            'DIRECTORY_GOOGLEMAP_HTML'  => $googleMapHTML,
            'DIRECTORY_GOOGLE_API_KEY'  => $_CONFIG["googleMapsAPIKey"],
            'DIRECTORY_START_X'         => 'null',
            'DIRECTORY_START_Y'         => 'null',
            'DIRECTORY_START_ZOOM'      => 'null',
            'DIRECTORY_ENTRY_NAME'      => 'null',
            'DIRECTORY_ENTRY_COMPANY'   => 'null',
            'DIRECTORY_ENTRY_STREET'    => 'null',
            'DIRECTORY_ENTRY_ZIP'       => 'null',
            'DIRECTORY_ENTRY_LOCATION'  => 'null',
            'DIRECTORY_MAP_LON_BACKEND' => $this->googleMapStartPoint['lon'],
            'DIRECTORY_MAP_LAT_BACKEND' => $this->googleMapStartPoint['lat'],
            'DIRECTORY_MAP_ZOOM_BACKEND'=> $this->googleMapStartPoint['zoom'],
            'IS_BACKEND'                => 'true',
        ));

        $this->_objTpl->parse('requests_block');
        $this->_objTpl->parse('direcoryGoogleMapJavascript');
    }

    function showSettings_inputs()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        // initialize variables
        $this->_objTpl->addBlockfile('SYSTEM_REQUESTS_CONTENT', 'requests_block', 'module_directory_settings_inputs.html');

        $this->_objTpl->setVariable(array(
            'TXT_INPUT_FIELDS'                  => $_ARRAYLANG['TXT_DIRECTORY_INPUTFIELDS'],
            'TXT_NAME'                          => $_ARRAYLANG['TXT_DIRECTORY_NAME'],
            'TXT_TYPE'                          => "#",
            'TXT_ACTIVE'                        => $_ARRAYLANG['TXT_DIRECTORY_ACTIVATE'],
            'TXT_ORDER'                         => $_ARRAYLANG['TXT_DIRECTORY_SORT'],
            'TXT_PLACEHOLDER'                   => $_ARRAYLANG['TXT_DIRECTORY_PLACEHOLDER_CONTENT'],
            'TXT_EXP_SEARCH'                    => $_ARRAYLANG['TXT_DIRECTORY_EXP_SEARCH'],
            'TXT_PLACEHOLDER_TXT'               => $_ARRAYLANG['TXT_DIRECTORY_PLACEHOLDER_TITLE'],
            'TXT_SAVE_CHANGES'                  => $_ARRAYLANG['TXT_DIR_CHANGES_SAVE'],
            'FIELD_ATTACHMENT'                  => $_ARRAYLANG['TXT_DIRECTORY_ATTACHMENT'],
            'TXT_REQUIRED'                      => $_ARRAYLANG['TXT_DIRECTORY_REQUIERED'],
            'ATTACHMENT_PLACEHOLDER'            =>  "[[DIRECTORY_FEED_ATTACHMENT]]",
        ));

        //get inputs
        $i=1;
        $objResult = $objDatabase->Execute("SELECT * FROM ".DBPREFIX."module_directory_inputfields ORDER BY active DESC, active_backend DESC,sort, typ, name");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $checked  = "";
                $disabled = "";
                $this->_objTpl->setCurrentBlock('settingsOutput');

                //search
                if ($objResult->fields['exp_search'] == 1) {
                    if ($objResult->fields['is_search'] == 1) {
                        $checked = 'checked="checked"';
                    }
                    $setSearch =
                        "<input type=\"checkbox\" name=\"setSearch[".
                        $objResult->fields['id']."]\" value=\"1\" ".
                        $checked." />";
                } else {
                    $setSearch = "&nbsp;";
                }

                if (
                        $objResult->fields['name'] == "title" ||
                        ($objResult->fields['name'] == "description" && $this->descriptionFieldRequired())
                   ) {
                    $setSearch =
                        '<input type="checkbox" '.
                        'disabled="disabled" checked="checked">';
                    $disabled  = "disabled";
                }

                //required
                if ($objResult->fields['is_required'] == 1) {
                    $checked = 'checked="checked"';
                } else {
                    $checked = "";
                }
                $setRequired =
                    "<input type=\"checkbox\" name=\"setRequired[".
                    $objResult->fields['id']."]\" value=\"1\" ".
                    $checked." ".$disabled." />";

                //order
                $setSortField =
                    "<input type=\"text\" name=\"setSort[".
                    $objResult->fields['id']."]\" value=\"".
                    $objResult->fields['sort']."\" size='2' maxlength='4' />";

                //backgroundcolor
                if ($objResult->fields['active'] == 1) {
                    $checked    = 'checked="checked"';
                    $rowColor   = "#d8ffca";
                } else {
                    $checked    = "";
                    $rowColor   = "#ffe7e7";
                }

                //status
                $setStatusField =
                    "<input type=\"checkbox\" name=\"setStatus[".
                    $objResult->fields['id']."]\" value=\"1\" ".
                    $disabled." ".$checked." />";


                //type
                switch ($objResult->fields['typ']) {
                    case '1':
                    case '5':
                    case '13':
                        $setType = "<img src='".$this->imageWebPath ."/input.gif' border='0' title='Inputfield' alt='Inputfield' />";
                    break;
                    case '2':
                    case '6':
                        $setType = "<img src='".$this->imageWebPath ."/area.gif' border='0' title='Textarea' alt='Textarea' />";
                    break;
                    case '3':
                    case '8':
                        $setType = "<img src='".$this->imageWebPath ."/dropdown.gif' border='0' title='Dropdownmenu' alt='Dropdownmenu' />";
                    break;
                    case '4':
                    case '7':
                        $setType = "<img src='".$this->imageWebPath ."/image.gif' border='0' title='Bilddatei' alt='Bilddatei' />";
                    break;
                    case '9':
                        $setType = "<img src='".$this->imageWebPath ."/voting.gif' border='0' title='Voting' alt='Voting' />";
                    break;
                    case '10':
                    case '11':
                        $setType = "<img src='".$this->imageWebPath ."/upload.gif' border='0' title='Upload' alt='Upload' />";
                    break;
                    case '12':
                        $setType = "<img src='".$this->imageWebPath ."/rssnew.gif' border='0' title='RSS Feed' alt='RSS Feed' />";
                    break;
                }

                //status backend
                if ($objResult->fields['active_backend'] == 1) {
                    $checked = 'checked="checked"';
                } else {
                    $checked = "";
                }

                $setStatusBackendField =
                    "<input type=\"checkbox\" name=\"setStatusBackend[".
                    $objResult->fields['id']."]\" value=\"1\" ".
                    $disabled." ".$checked." />";

                //is dropdown
                if ($objResult->fields['typ'] == 3 || $objResult->fields['typ'] == 8 || $objResult->fields['typ'] == 9) {
                    $dropdown = '';
                    $objResultDropdown = $objDatabase->Execute("SELECT setid, setvalue FROM ".DBPREFIX."module_directory_settings WHERE setname = '".$objResult->fields['name']."'");
                    if ($objResultDropdown !== false) {
                        while(!$objResultDropdown->EOF) {
                            $textarea =
                                "<textarea name=\"setDropdown[".
                                $objResultDropdown->fields['setid'].
                                "]\" style=\"width:440px; overflow: auto;\" rows='5'>".
                                $objResultDropdown->fields['setvalue']."</textarea>";
                            $objResultDropdown->MoveNext();
                        }
                    }
                    $dropdown =
                        '<tr class="'.$class.'" style="background-color: '.
                        $rowColor.';"><td>&nbsp;</td><td valign="top">'.
// TODO:  Use language variable here
                        '<div align="right"><i>Auswahlfelder:</i>&nbsp;</div>'.
                        '</td><td valign="top" colspan="7">'.$textarea.'</td>'.
                        '</tr>';
                    $rowStyle = 'style="border-bottom: 0px"';
                } else {
                    $dropdown = '';
                    $rowStyle = '';
                }

                //is spez field
                if ($objResult->fields['typ'] >= 5 &&  $objResult->fields['typ'] <= 10 ) {
                    $setName =
                        "<input type=\"text\" name=\"setSpezFields[".
                        $objResult->fields['id']."]\" value=\"".
                        $objResult->fields['title'].
                        "\" style=\"width:130px;\" maxlength='250' />";
                } else {
                    $setName = $_ARRAYLANG[$objResult->fields['title']];
                }

                ($i % 2)? $class = "row2" : $class = "row1";

                // initialize variables
                $this->_objTpl->setVariable(array(
                    'FIELD_ROWCLASS'            => $class,
                    'FIELD_NAME'                => $setName,
                    'FIELD_STATUS'              => $setStatusField,
                    'FIELD_STATUS_BACKEND'      => $setStatusBackendField,
                    'FIELD_SORT'                => $setSortField,
                    'FIELD_EXP_SEARCH'          => $setSearch,
                    'FIELD_REQUIRED'            => $setRequired,
                    'FIELD_TYPE'                => $setType,
                    'ROW_COLOR'                 => $rowColor,
                    'DROPDOWN_CONTENT'          => $dropdown,
                    'ROW_STYLE'                 => $rowStyle,
                    'FIELD_PLACEHOLDER_TXT'     => "[[TXT_DIRECTORY_FEED_".strtoupper($objResult->fields['name'])."]]",
                    'FIELD_PLACEHOLDER'         => "[[DIRECTORY_FEED_".strtoupper($objResult->fields['name'])."]]",
                ));
                $this->_objTpl->parseCurrentBlock('settingsOutput');
                $i++;
                $objResult->MoveNext();
            }
        }
        $this->_objTpl->parse('requests_block');
    }


    function showSettings_mail()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        // initialize variables
        $this->_objTpl->addBlockfile('SYSTEM_REQUESTS_CONTENT', 'requests_block', 'module_directory_settings_mail.html');

        //get content
        $objResult = $objDatabase->Execute("SELECT title, content FROM ".DBPREFIX."module_directory_mail WHERE id = '1'");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $mailConfirmContent = $objResult->fields['content'];
                $mailConfirmTitle = $objResult->fields['title'];
                $objResult->MoveNext();
            }
        }

        $objResult = $objDatabase->Execute("SELECT title, content FROM ".DBPREFIX."module_directory_mail WHERE id = '2'");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $mailRememberContent = $objResult->fields['content'];
                $mailRememberTitle = $objResult->fields['title'];
                $objResult->MoveNext();
            }
        }

        $objResult = $objDatabase->Execute("SELECT setvalue FROM ".DBPREFIX."module_directory_settings WHERE setid = '30'");
        if ($objResult !== false) {
            $mailRememberAdress = $objResult->fields['setvalue'];
        }

        $this->_objTpl->setVariable(array(
            'TXT_SAVE_CHANGES'                  => $_ARRAYLANG['TXT_DIR_CHANGES_SAVE'],
            'TXT_EMAIL_TEMPLATE'                => $_ARRAYLANG['TXT_DIRECTORY_MAIL_TEMLATES'],
            'TXT_PLACEHOLDER'                   => $_ARRAYLANG['TXT_DIRECTORY_PLACEHOLDER'],
            'TXT_USERNAME'                      => $_ARRAYLANG['TXT_DIR_USERNAME'],
            'TXT_FIRSTNAME'                     => $_ARRAYLANG['TXT_DIR_FIRSTNAME'],
            'TXT_LASTNAME'                      => $_ARRAYLANG['TXT_DIR_LASTNAME'],
            'TXT_TITLE'                         => $_ARRAYLANG['TXT_DIR_TITLE'],
            'TXT_DATE'                          => $_ARRAYLANG['TXT_DIR_DATE'],
            'TXT_LINK'                          => $_ARRAYLANG['TXT_DIRECTORY_LINK'],
            'TXT_URL'                           => $_ARRAYLANG['TXT_DIRECTORY_URL'],
            'TXT_CONTENT'                       => $_ARRAYLANG['TXT_DIRECTORY_CONTENT'],
            'TXT_TEXT'                          => $_ARRAYLANG['TXT_DIRECTORY_TEXT'],
            'TXT_ADRESS'                        => $_ARRAYLANG['TXT_DIRECTORY_RECIEVER'],
            'MAIL_CONFIRM_CONTENT'              => $mailConfirmContent,
            'MAIL_CONFIRM_TITLE'                => $mailConfirmTitle,
            'MAIL_REMEMBER_CONTENT'             => $mailRememberContent,
            'MAIL_REMEMBER_TITLE'               => $mailRememberTitle,
            'MAIL_REMEMBER_ADRESS'              => $mailRememberAdress,
            'TXT_REMEMBER'                      => $_ARRAYLANG['TXT_DIRECTORY_REMEMBER'],
            'TXT_CONFIRM'                       => $_ARRAYLANG['TXT_DIRECTORY_CONFIRM_MAIL'],
        ));
    }


    function showSettings_headlines()
    {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;

        // initialize variables
        $this->_objTpl->addBlockfile('SYSTEM_REQUESTS_CONTENT', 'requests_block', 'module_directory_settings_headlines.html');

        $this->_objTpl->setVariable(array(
            'TXT_PLACEHOLDER_LIST'  => $_ARRAYLANG['TXT_DIRECTORY_PLACEHOLDER'],
            'TXT_DIRECTORY'         => $_ARRAYLANG['TXT_DIR_DIRECTORY'],
            'TXT_EXAMPLE'           => $_ARRAYLANG['TXT_EXAMPLE'],
            'TXT_ID_DESCRIPTION'    => $_ARRAYLANG['TXT_ID_DESCRIPTION'],
            'TXT_DATE_DESCRIPTION'  => $_ARRAYLANG['TXT_DATE_DESCRIPTION'],
            'TXT_TITLE_DESCRIPTION' => $_ARRAYLANG['TXT_TITLE_DESCRIPTION'],
        ));
    }


    function showSettings_homecontent()
    {
        global $_ARRAYLANG;

        // initialize variables
        $this->_objTpl->addBlockfile('SYSTEM_REQUESTS_CONTENT', 'requests_block', 'module_directory_settings_homecontent.html');

        //get settings
        $homeContent = \Cx\Core\Setting\Controller\Setting::getValue(
            'directoryHomeContent',
            'Config'
        );
        if ($homeContent == 1) {
            $selectDeactivate   = '';
            $selectActivate     = 'checked="checked"';
            $this->_objTpl->parse('showExample');
        } else {
            $selectDeactivate   = 'checked="checked"';
            $selectActivate     = '';
            $this->_objTpl->hideBlock('showExample');
        }

        $this->_objTpl->setVariable(array(
            'TXT_PLACEHOLDER_LIST'  => $_ARRAYLANG['TXT_DIRECTORY_PLACEHOLDER'],
            'TXT_DIRECTORY'         => $_ARRAYLANG['TXT_DIR_DIRECTORY'],
            'TXT_EXAMPLE'           => $_ARRAYLANG['TXT_EXAMPLE'],
            'TXT_ID_DESCRIPTION'    => $_ARRAYLANG['TXT_ID_DESCRIPTION'],
            'TXT_DATE_DESCRIPTION'  => $_ARRAYLANG['TXT_DATE_DESCRIPTION'],
            'TXT_TITLE_DESCRIPTION' => $_ARRAYLANG['TXT_TITLE_DESCRIPTION'],
            'TXT_USEMENT'           => $_ARRAYLANG['TXT_USEMENT'],
            'TXT_USEMENT_TEXT'      => $_ARRAYLANG['TXT_USEMENT_TEXT'],
            'TXT_ACTIVATE'          => $_ARRAYLANG['TXT_DIRECTORY_ACTIVATE'],
            'TXT_DEACTIVATE'        => $_ARRAYLANG['TXT_DIRECTORY_ACTIVATE'],
            'TXT_DEACTIVATE'        => $_ARRAYLANG['TXT_DIRECTORY_DEACTIVATE'],
            'TXT_SHOW_HOME_CONTENT' => $_ARRAYLANG['TXT_SHOW_DIR_HOMECONTENT'],
            'TXT_SETTINGS'          => $_ARRAYLANG['TXT_DIR_SETTINGS'],
            'SELECT_ACTIVATE'       => $selectActivate,
            'SELECT_DEACTIVATE'     => $selectDeactivate,
            'TXT_SAVE_CHANGES'      => $_ARRAYLANG['TXT_DIR_CHANGES_SAVE'],
        ));
    }


    /**
    * update settings
    * @access   public
    * @global    array
    * @global    ADONewConnection
    * @global    array
    * @global    array
    */
    function updateSettings()
    {
        global $objDatabase, $_CORELANG, $_ARRAYLANG;

        if (isset($_POST['set_sys_submit'])) {
            //get post data
            foreach ($_POST['setvalue'] as $id => $value) {
                //update settings

                // check for description field to be required
                if ($id == 13 && $value == 1) {
                    $objDatabase->Execute("UPDATE `".DBPREFIX."module_directory_inputfields` SET active='1', is_required='1', active_backend='1' WHERE name='description'");
                }

                if (ini_get('allow_url_fopen') == false && $id == 19) {
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_settings SET setvalue='0' WHERE setid=".intval($id));
                } else {
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_settings SET setvalue='".contrexx_addslashes($value)."' WHERE setid=".intval($id));
                }

            }
            $this->strOkMessage = $_ARRAYLANG['TXT_DIR_SETTINGS_SUCCESFULL_SAVE'];
        }

        if (isset($_POST['set_homecontent_submit'])) {
            //update settings
            \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');
            if (isset($_POST['setHomeContent'])) {
                if (!\Cx\Core\Setting\Controller\Setting::isDefined('directoryHomeContent')) {
                    \Cx\Core\Setting\Controller\Setting::add('directoryHomeContent', contrexx_addslashes($_POST['setHomeContent']), 1, \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:TXT_ACTIVATED,0:TXT_DEACTIVATED', 'component');
                } else {
                    \Cx\Core\Setting\Controller\Setting::set('directoryHomeContent', contrexx_addslashes($_POST['setHomeContent']));
                    \Cx\Core\Setting\Controller\Setting::update('directoryHomeContent');
                }
            }

            \Cx\Core\Csrf\Controller\Csrf::header('Location: ?cmd=Directory&act=settings&tpl=homecontent');
            exit;

            $this->strOkMessage = $_ARRAYLANG['TXT_DIR_SETTINGS_SUCCESFULL_SAVE'];
        }


        if (isset($_POST['set_mail_submit'])) {
            //update settings
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_mail SET title='".contrexx_addslashes($_POST['mailConfirmTitle'])."', content='".$_POST['mailConfirmContent']."' WHERE id='1'");
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_mail SET title='".contrexx_addslashes($_POST['mailRememberTitle'])."', content='".$_POST['mailRememberContent']."' WHERE id='2'");
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_settings SET setvalue='".contrexx_addslashes($_POST['mailRememberAdress'])."' WHERE setid='30'");
            $this->strOkMessage = $_ARRAYLANG['TXT_DIR_SETTINGS_SUCCESFULL_SAVE'];
        }

        if (isset($_POST['set_inputs_submit'])) {
            //update settings

            // title field should stay active, required and available for search
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active='0' Where id !='1'");
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET is_search='0' Where id !='1'");
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET is_required='0' Where id !='1'");
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active_backend='0' Where id !='1'");

            //get post data
            if ($_POST['setStatus'] != "") {
                $addressElements = 0;
                $googleMapIsEnabled = false;
                foreach ($_POST['setStatus'] as $id => $value) {
                    //update settings
                    $objResult = $objDatabase->Execute("SELECT `name` FROM ".DBPREFIX."module_directory_inputfields WHERE id=".intval($id));
                    $name = $objResult->fields['name'];
                    switch ($name) {
                        case 'country':
                        case 'zip':
                        case 'street':
                        case 'city':
                            $addressElements++;
                            break;
                        case 'googlemap':
                            $googleMapIsEnabled = true;
                            break;
                        default:
                    }
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active='".contrexx_addslashes($value)."' WHERE id=".intval($id));
                }

                if ($googleMapIsEnabled && $addressElements < 4) {
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active='1' WHERE name='country'");
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active='1' WHERE name='zip'");
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active='1' WHERE name='street'");
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active='1' WHERE name='city'");
                    $this->strOkMessage = $_ARRAYLANG['TXT_DIRECTORY_GOOGLEMAP_REQUIRED_FIELDS_MISSING'];
                }
            }

            //get post data
            if ($_POST['setStatusBackend'] != "") {
                $addressElements = 0;
                $googleMapIsEnabled = false;
                foreach ($_POST['setStatusBackend'] as $id => $value) {
                    //update settings
                    $objResult = $objDatabase->Execute("SELECT `name` FROM ".DBPREFIX."module_directory_inputfields WHERE id=".intval($id));
                    $name = $objResult->fields['name'];
                    switch ($name) {
                        case 'country':
                        case 'zip':
                        case 'street':
                        case 'city':
                            $addressElements++;
                            break;
                        case 'googlemap':
                            $googleMapIsEnabled = true;
                            break;
                        default:
                    }
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active_backend='".contrexx_addslashes($value)."' WHERE id=".intval($id));
                }
                if ($googleMapIsEnabled && $addressElements < 4) {
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active_backend='1' WHERE name='country'");
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active_backend='1' WHERE name='zip'");
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active_backend='1' WHERE name='street'");
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active_backend='1' WHERE name='city'");
                    $this->strOkMessage = $_ARRAYLANG['TXT_DIRECTORY_GOOGLEMAP_REQUIRED_FIELDS_MISSING'];
                }
            }

            //get post data
            if ($_POST['setSort'] != "") {
                foreach ($_POST['setSort'] as $id => $sort) {
                    $sort = $sort;

                    //update settings
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET sort=".intval($sort)." WHERE id=".intval($id));
                }
            }

            //get post data
            if ($_POST['setSearch'] != "") {
                foreach ($_POST['setSearch'] as $id => $search) {

                    //update settings
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET is_search=".$search." WHERE id=".intval($id));
                }
            }

            //get post data
            if ($_POST['setRequired'] != "") {
                foreach ($_POST['setRequired'] as $id => $required) {

                    //update settings
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET is_required=". $required." WHERE id=".intval($id));
                }
            }

            //get post data
            if ($_POST['setSpezFields'] != "") {
                foreach ($_POST['setSpezFields'] as $id => $value) {
                    //update settings
                    $objReult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET title='".contrexx_addslashes($value)."' WHERE id=".intval($id));
                }
            }

            //get dropdown data
            foreach ($_POST['setDropdown'] as $id => $value) {
                //update settings
                $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_settings SET setvalue='".contrexx_addslashes($value)."' WHERE setid=".intval($id));
            }

            //update settings
            $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active='1' WHERE name='title'");
            if ($this->descriptionFieldRequired()) {
                $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_inputfields SET active='1', is_required='1', active_backend='1' WHERE name='description'");
            }

            $this->strOkMessage = $_ARRAYLANG['TXT_DIR_SETTINGS_SUCCESFULL_SAVE'];
        }
        $zoom = isset($_POST['inputValue']['zoom']) ? $_POST['inputValue']['zoom'] : '';
        if ($zoom != "") {
            $googleStartPoint  = intval($_POST['inputValue']['lat']);
            $googleStartPoint .= '.'.intval($_POST['inputValue']['lat_fraction']);
            $googleStartPoint .= ':'.intval($_POST['inputValue']['lon']);
            $googleStartPoint .= '.'.intval($_POST['inputValue']['lon_fraction']);
            $googleStartPoint .= ':'.contrexx_input2int($zoom);
            $objDatabase->Execute("UPDATE ".DBPREFIX."module_directory_settings SET setvalue='".$googleStartPoint."' WHERE setname='googlemap_start_location'");
        }
    }


    function restoreVoting($id)
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($id)) {
            $objResult = $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_directory_vote WHERE feed_id ='$id'");
            if ($objResult !== false) {
                $this->showFiles('','');
                $this->strOkMessage = $_ARRAYLANG['TXT_DIRECTORY_VOTING_RESTORED'];
            }
        }
    }

    /**
     * check whether the description field is required or not
     * @return boolean true if the description field is required
     */
    protected function descriptionFieldRequired() {
        global $objDatabase;
        $objResultDescriptionSetting = $objDatabase->SelectLimit("SELECT `setvalue` FROM `" . DBPREFIX . "module_directory_settings` WHERE `setname` = 'description'", 1);
        return $objResultDescriptionSetting->fields['setvalue'] == 1;
    }

}

?>
