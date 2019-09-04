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
 * Media Manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version       1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_media
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core_Modules\Media\Controller;
/**
 * Media Manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version       1.0.0
 * @access        public
 * @package     cloudrexx
 * @subpackage  coremodule_media
 */
class MediaManager extends MediaLibrary
{
    public $_objTpl;                          // var for the template object
    public $pageTitle;                        // var for the title of the active page

    public $arrPaths;                         // array paths
    public $arrWebPaths;                      // array web paths

    public $getAct;                           // $_GET['act']
    public $getPath;                          // $_GET['path']
    public $getFile;                          // $_GET['file']
    public $getData;                          // $_GET['data']

    public $chmodFolder       = 0777;         // chmod for folder 0777
    public $chmodFile         = 0644;         // chmod for files  0644
    public $thumbHeight       = 80;           // max height for thumbnail
    public $thumbQuality      = 80;           // max quality for thumbnail

    public $docRoot;                          // ASCMS_DOCUMENT_ROOT
    public $path;                             // current path
    public $webPath;                          // current web path
    public $highlightName     = array();      // highlight added name
    public $highlightColor    = '#d8ffca';    // highlight color for added name [#d8ffca]
    public $highlightCCColor  = '#ffe7e7';    // highlight color for cuted or copied media [#ffe7e7]

    public $tmpPath           = array();      // dir tree path
    public $tmpPathName       = array();      // dir tree path name

    public $_objImage;                        // object from ImageManager class

    public $dirLog;                           // Dir Log
    public $fileLog;                          // File Log
    public $archive;

    public $shopEnabled;
    public $_strOkMessage = '';

    public $arrImageQualityValues = array(5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100);


    /**
     * PHP5 constructor
     * @param  string  $objTemplate
     * @param  array   $_ARRAYLANG
     * @access public
     */
    function __construct(){
        global  $_ARRAYLANG, $_FTPCONFIG, $objTemplate, $objDatabase;

        // sigma template
        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH.'/Media/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->arrPaths     = array(ASCMS_MEDIA1_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_MEDIA2_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_MEDIA3_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_MEDIA4_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_FILESHARING_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_CONTENT_IMAGE_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_SHOP_IMAGES_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_THEMES_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_ATTACH_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_ACCESS_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_BLOG_IMAGES_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_CALENDAR_IMAGE_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_DOWNLOADS_IMAGES_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_GALLERY_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_MEDIADIR_IMAGES_PATH.DIRECTORY_SEPARATOR,
                                    ASCMS_PODCAST_IMAGES_PATH.DIRECTORY_SEPARATOR);

        $this->arrWebPaths  = array('archive1'     => ASCMS_MEDIA1_WEB_PATH . '/',
                                    'archive2'     => ASCMS_MEDIA2_WEB_PATH . '/',
                                    'archive3'     => ASCMS_MEDIA3_WEB_PATH . '/',
                                    'archive4'     => ASCMS_MEDIA4_WEB_PATH . '/',
                                    'FileSharing'  => ASCMS_FILESHARING_WEB_PATH . '/',
                                    'content'      => ASCMS_CONTENT_IMAGE_WEB_PATH . '/',
                                    'Contact'      => ASCMS_ATTACH_WEB_PATH. '/',
                                    'Shop'         => ASCMS_SHOP_IMAGES_WEB_PATH . '/',
                                    'themes'       => ASCMS_THEMES_WEB_PATH . '/',
                                    'attach'       => ASCMS_ATTACH_WEB_PATH. '/',
                                    'Access'       => ASCMS_ACCESS_WEB_PATH . '/',
                                    'Blog'         => ASCMS_BLOG_IMAGES_WEB_PATH . '/',
                                    'Calendar'     => ASCMS_CALENDAR_IMAGE_WEB_PATH . '/',
                                    'Downloads'    => ASCMS_DOWNLOADS_IMAGES_WEB_PATH . '/',
                                    'Gallery'      => ASCMS_GALLERY_WEB_PATH . '/',
                                    'MediaDir'     => ASCMS_MEDIADIR_IMAGES_WEB_PATH . '/',
                                    'Podcast'      => ASCMS_PODCAST_IMAGES_WEB_PATH . '/');
        $moduleMatchTable = array(
                                    'archive1'     => 'Media1',
                                    'archive2'     => 'Media2',
                                    'archive3'     => 'Media3',
                                    'archive4'     => 'Media4',
                                    'content'      => 'core',
                                    'themes'       => 'core',
                                    'attach'       => 'core',
        );
        $license = \Cx\Core_Modules\License\License::getCached($_CONFIG, $objDatabase);
        $license->check();
        foreach ($this->arrWebPaths as $module=>$path) {
            $moduleName = $module;
            if (isset($moduleMatchTable[$module])) {
                $moduleName = $moduleMatchTable[$module];
            }
            if (!$license->isInLegalComponents($moduleName)) {
                \DBG::msg('Module "' . $module . '" is deactivated');
                unset($this->arrWebPaths[$module]);
            }
        }

        if (empty($this->arrWebPaths)) {
            \Permission::noAccess();
        }

        if (isset($_REQUEST['archive']) && array_key_exists($_REQUEST['archive'], $this->arrWebPaths)) {
            $this->archive = $_REQUEST['archive'];
        } else {
            $this->archive = 'content';
        }

        // get variables
        $this->getAct      = isset($_POST['deleteMedia']) && $_POST['deleteMedia'] ? 'delete' : (!empty($_GET['act']) ? trim($_GET['act']) : '');
        $this->getPath     = isset($_GET['path']) ? \Cx\Lib\FileSystem\FileSystem::sanitizePath($_GET['path']) : false;
        if ($this->getPath === false) $this->getPath = $this->arrWebPaths[$this->archive];
        $this->getFile     = isset($_REQUEST['file']) ? \Cx\Lib\FileSystem\FileSystem::sanitizeFile($_REQUEST['file']) : false;
        if ($this->getFile === false) $this->getFile = '';
        $this->getData     = !empty($_GET['data']) ? $_GET['data']       : '';
        $this->sortBy      = !empty($_GET['sort']) ? trim($_GET['sort']) : 'name';
        $this->sortDesc    = !empty($_GET['sort_desc']);
        $this->shopEnabled = $this->checkModule('Shop');

        if($this->archive == 'themes') {
            $_SESSION["skins"] = true;
        } else {
            $_SESSION["skins"] = false;
        }

        switch ($this->archive) {
            case 'themes':
                \Permission::checkAccess(\Cx\Core\ViewManager\Controller\ViewManager::VIEW_MANAGER_ACCESS_ID, 'static');
                $objTemplate->setVariable("CONTENT_NAVIGATION",
                   "<a href='index.php?cmd=Media&amp;archive=content'>". $_ARRAYLANG['TXT_IMAGE_CONTENT'] ."</a>
                    <a href='index.php?cmd=Media&amp;archive=attach'>". $_ARRAYLANG['TXT_MODULE'] ."</a>
                    <a href='index.php?cmd=Media&amp;archive=themes' class='active'>".$_ARRAYLANG['TXT_MEDIA_LAYOUT']."</a>");
                break;

            case 'content':
                \Permission::checkAccess(32, 'static');
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Media&amp;archive=content" class="active">'. $_ARRAYLANG['TXT_IMAGE_CONTENT'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=attach">'. $_ARRAYLANG['TXT_MODULE'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=themes">'. $_ARRAYLANG['TXT_MEDIA_LAYOUT'] .'</a>'
                );
                break;
            case 'Contact':
                \Permission::checkAccess(84, 'static');
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Contact" title="'.$_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS'].'">'.$_ARRAYLANG['TXT_FORMS'].'</a>
                    <a hreF="index.php?cmd=Media&amp;archive=Contact" title="'.$_ARRAYLANG['TXT_FILE_UPLOADS'].'" class="active">'.$_ARRAYLANG['TXT_FILE_UPLOADS'].'</a>
                    <a href="index.php?cmd=Contact&amp;act=settings" title="'.$_ARRAYLANG['TXT_CONTACT_SETTINGS'].'">'.$_ARRAYLANG['TXT_CONTACT_SETTINGS'].'</a>
                ');
                break;
            case 'FileSharing':
                \Permission::checkAccess(8, 'static');
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Media&amp;archive=FileSharing"' . (!isset($_GET['act']) || $_GET['act'] == 'filesharing' ? ' class="active"' : '') . '>' . $_ARRAYLANG['TXT_FILESHARING_MODULE'] . '</a>
                    <a href="index.php?cmd=Media&amp;archive=FileSharing&amp;act=settings"' . (isset($_GET['act']) && $_GET['act'] == 'settings' ? ' class="active"' : '') . '>' . $_ARRAYLANG['TXT_MEDIA_SETTINGS'] . '</a>
                ');
                break;
            case 'attach':
                \Permission::checkAccess(84, 'static');
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Media&amp;archive=content">'. $_ARRAYLANG['TXT_IMAGE_CONTENT'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=attach" class="active">'. $_ARRAYLANG['TXT_MODULE'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=themes">'. $_ARRAYLANG['TXT_MEDIA_LAYOUT'] .'</a>
                ');
                break;
            case 'Access':
                \Permission::checkAccess(18, 'static');
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Media&amp;archive=content">'. $_ARRAYLANG['TXT_IMAGE_CONTENT'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=attach" class="active">'. $_ARRAYLANG['TXT_MODULE'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=themes">'. $_ARRAYLANG['TXT_MEDIA_LAYOUT'] .'</a>
                ');
                break;
            case 'Blog':
                \Permission::checkAccess(119, 'static');
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Media&amp;archive=content">'. $_ARRAYLANG['TXT_IMAGE_CONTENT'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=attach" class="active">'. $_ARRAYLANG['TXT_MODULE'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=themes">'. $_ARRAYLANG['TXT_MEDIA_LAYOUT'] .'</a>
                ');
                break;
            case 'Calendar':
                \Permission::checkAccess(16, 'static');
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Media&amp;archive=content">'. $_ARRAYLANG['TXT_IMAGE_CONTENT'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=attach" class="active">'. $_ARRAYLANG['TXT_MODULE'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=themes">'. $_ARRAYLANG['TXT_MEDIA_LAYOUT'] .'</a>
                ');
                break;
            case 'Downloads':
                \Permission::checkAccess(141, 'static');
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Media&amp;archive=content">'. $_ARRAYLANG['TXT_IMAGE_CONTENT'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=attach" class="active">'. $_ARRAYLANG['TXT_MODULE'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=themes">'. $_ARRAYLANG['TXT_MEDIA_LAYOUT'] .'</a>
                ');
                break;
            case 'Gallery':
                \Permission::checkAccess(12, 'static');
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Media&amp;archive=content">'. $_ARRAYLANG['TXT_IMAGE_CONTENT'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=attach" class="active">'. $_ARRAYLANG['TXT_MODULE'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=themes">'. $_ARRAYLANG['TXT_MEDIA_LAYOUT'] .'</a>
                ');
                break;
            case 'MediaDir':
                \Permission::checkAccess(153, 'static');
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Media&amp;archive=content">'. $_ARRAYLANG['TXT_IMAGE_CONTENT'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=attach" class="active">'. $_ARRAYLANG['TXT_MODULE'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=themes">'. $_ARRAYLANG['TXT_MEDIA_LAYOUT'] .'</a>
                ');
                break;
            case 'Podcast':
                \Permission::checkAccess(87, 'static');
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Media&amp;archive=content">'. $_ARRAYLANG['TXT_IMAGE_CONTENT'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=attach" class="active">'. $_ARRAYLANG['TXT_MODULE'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=themes">'. $_ARRAYLANG['TXT_MEDIA_LAYOUT'] .'</a>
                ');
                break;
            case 'Shop':
                \Permission::checkAccess(13, 'static');
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Media&amp;archive=content">'. $_ARRAYLANG['TXT_IMAGE_CONTENT'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=attach" class="active">'. $_ARRAYLANG['TXT_MODULE'] .'</a>
                    <a href="index.php?cmd=Media&amp;archive=themes">'. $_ARRAYLANG['TXT_MEDIA_LAYOUT'] .'</a>
                ');
                break;
            default:
                \Permission::checkAccess(7, 'static');
                $act = isset($_GET['act']) ? $_GET['act'] : '';
                $objTemplate->setVariable('CONTENT_NAVIGATION', '
                    <a href="index.php?cmd=Media&amp;archive=archive1" ' . ($this->archive == 'archive1' && empty($act) ? ' class="active"' : '') . '>'. $_ARRAYLANG['TXT_MEDIA_ARCHIVE'] .' #1</a>
                    <a href="index.php?cmd=Media&amp;archive=archive2" ' . ($this->archive == 'archive2' ? ' class="active"' : '') . '>'. $_ARRAYLANG['TXT_MEDIA_ARCHIVE'] .' #2</a>
                    <a href="index.php?cmd=Media&amp;archive=archive3" ' . ($this->archive == 'archive3' ? ' class="active"' : '') . '>'. $_ARRAYLANG['TXT_MEDIA_ARCHIVE'] .' #3</a>
                    <a href="index.php?cmd=Media&amp;archive=archive4" ' . ($this->archive == 'archive4' ? ' class="active"' : '') . '>'. $_ARRAYLANG['TXT_MEDIA_ARCHIVE'] .' #4</a>
                    <a href="index.php?cmd=Media&amp;archive=archive1&amp;act=settings" ' . ($this->archive == 'archive1' && $act == 'settings' ? ' class="active"' : '') . '>' . $_ARRAYLANG['TXT_MEDIA_SETTINGS'] . '</a>
                ');
                break;
        }

        $this->docRoot = \Env::get('cx')->getWebsiteDocumentRootPath(); // with path offset
        $this->docRoot = \Env::get('cx')->getWebsitePath(); // without path offset

        //paths
        $this->webPath = $this->_pathCheck($this->getPath);
        $this->path    = $this->docRoot.$this->webPath;

        $this->_objImage = new \ImageManager();
    }


    /**
     * Checks whether the specified module is available and active.
     *
     * @return  boolean  True or false.
     */
    private function checkModule($module) {
        global $objDatabase;

        if (($objResult = $objDatabase->SelectLimit('SELECT `id` FROM `'.DBPREFIX.'modules` WHERE `name` = "'.$module.'" AND `status` = "y"', 1)) !== false) {
            if ($objResult->RecordCount() > 0) {
                return true;
            }
        }

        return false;
    }


    /**
     * Gets the requested page
     * @global     array     $_ARRAYLANG,$_CONFIG
     * @return    string    parsed content
     */
    function getMediaPage()
    {
        global $_ARRAYLANG, $objTemplate;

        switch($this->getAct) {
            case 'newDir':
                $this->_createNewDir($_POST['dirName']);
                $this->_overviewMedia();
                break;
            case 'download':
                $this->_downloadMedia();
                //$this->_overviewMedia();
                break;
            case 'cut':
                $this->_cutMedia();
                $this->_overviewMedia();
                break;
            case 'copy':
                $this->_copyMedia();
                $this->_overviewMedia();
                break;
            case 'paste':
                $this->_pasteMedia();
                $this->_overviewMedia();
                break;
            case 'delete':
                $this->_deleteMedia();
                if (!empty($_REQUEST['redirect'])) {
                    $this->handleRedirect();
                } else {
                    $this->_overviewMedia();
                }
                break;
            case 'rename':
                $this->_renameMedia();
                break;
            case 'edit':
                $this->editMedia();
                break;
            case 'filesharing':
                $objFilesharing = new \Cx\Modules\FileSharing\Controller\FileSharingManager($this->_objTpl);
                $objFilesharing->getDetailPage();
                $this->pageTitle = $_ARRAYLANG['TXT_FILESHARING_MODULE'];
                break;
            case 'preview':
                $this->_previewImage();
                break;
            case 'previewSize':
                $this->_previewImageSize();
                break;
            case 'ren':
                $this->renMedia();
                $this->handleRedirect();
                break;
            case 'getImage':
                try {
                    $this->getImage($_GET);
                } catch (\Exception $e) {
                    \DBG::msg('Could not get image preview: '.$e->getMessage());
                }
                die();
                break;
            case 'editImage':
                try {
                    $data = $this->editImage($_POST);
                } catch (\Exception $e) {
                    \DBG::msg('Could not edit image: '.$e->getMessage());
                }
                die($data);
                break;
            case 'settings':
                $this->_settings();
                break;
            case 'saveSettings':
                $this->_saveSettings();
                $this->_settings();
                break;
            default:
                $this->_overviewMedia();
        }
        $objTemplate->setVariable(array(
            'CONTENT_TITLE'                => $this->pageTitle,
            'CONTENT_OK_MESSAGE'           => $this->_strOkMessage,
            'ADMIN_CONTENT'                => $this->_objTpl->get()
        ));
    }


    /**
    * Overview Media Data
    *
    * @global     array     $_ARRAYLANG
    * @global     array     $_CONFIG
    * @global     array     $_CORELANG
    * @return    string    parsed content
    */
    function _overviewMedia(){
        global $_ARRAYLANG, $_CONFIG, $_CORELANG, $objDatabase;

        \JS::activate('shadowbox');

        $this->_objTpl->loadTemplateFile('module_media.html', true, true);

        switch ($this->archive) {
            case 'themes':
                $this->pageTitle = $_ARRAYLANG['TXT_MEDIA_LAYOUT'];
                break;
            case 'content':
                $this->pageTitle = $_ARRAYLANG['TXT_IMAGE_ADMINISTRATION'];
                break;
            case 'Contact':
                $this->pageTitle = $_ARRAYLANG['TXT_FILE_UPLOADS'];
                break;
            case 'attach':
            case 'Access':
            case 'Blog':
            case 'Calendar':
            case 'Downloads':
            case 'Gallery':
            case 'MediaDir':
            case 'Podcast':
            case 'Shop':

                $archives = array(
                    'attach' => 'TXT_FILE_UPLOADS',
                    'Shop' => 'TXT_IMAGE_SHOP',
                    'Gallery' => 'TXT_GALLERY_TITLE',
                    'Access' => 'TXT_USER_ADMINISTRATION',
                    'MediaDir' => 'TXT_MEDIADIR_MODULE',
                    'Downloads' => 'TXT_DOWNLOADS',
                    'Calendar' => 'TXT_CALENDAR',
                    'Podcast' => 'TXT_PODCAST',
                    'Blog' => 'TXT_BLOG_MODULE',
                );
                $moduleMatchTable = array(
                    'attach' => 'core',
                );

                $subnavigation = '
                    <div id="subnavbar_level2">
                        <ul>';
                $license = \Cx\Core_Modules\License\License::getCached($_CONFIG, $objDatabase);
                $license->check();
                foreach ($archives as $archive=>$txtKey) {
                    $moduleName = $archive;
                    if (isset($moduleMatchTable[$archive])) {
                        $moduleName = $moduleMatchTable[$archive];
                    }
                    if (!$license->isInLegalComponents($moduleName)) {
                        \DBG::msg('Module "' . $archive . '" is deactivated');
                        continue;
                    }
                    $subnavigation .= '
                            <li><a href="index.php?cmd=Media&amp;archive=' . $archive . '" class="'.($this->archive == $archive ? 'active' : '').'">'.$_ARRAYLANG[$txtKey].'</a></li>';
                }
                $subnavigation .= '
                        </ul>
                    </div>';
                $this->_objTpl->setVariable('CONTENT_SUBNAVIGATION', $subnavigation);
            default:
                $this->pageTitle = $_ARRAYLANG['TXT_MEDIA_OVERVIEW'];
                if ($this->archive == "FileSharing") {
                    \Cx\Modules\FileSharing\Controller\FileSharingLib::cleanUp();
                }
                break;
        }

        $searchTerm =  !empty($_GET['term'])
                      ? \FWValidator::getCleanFileName(contrexx_input2raw($_GET['term']))
                      : '';

        // cut, copy and paste session
        if (isset($_SESSION['mediaCutFile'])) {
            $tmpArray = array();
            foreach ($_SESSION['mediaCutFile'][2] as $tmp) {
                 if (file_exists($_SESSION['mediaCutFile'][0].$tmp)) {
                     $tmpArray[] = $tmp;
                 }
            }

            if (count($tmpArray) > 0) {
                $_SESSION['mediaCutFile'][0] = $_SESSION['mediaCutFile'][0];
                $_SESSION['mediaCutFile'][1] = $_SESSION['mediaCutFile'][1];
                $_SESSION['mediaCutFile'][2] = $tmpArray;
            } else {
                unset($_SESSION['mediaCutFile']);
            }
        }
        if (isset($_SESSION['mediaCopyFile'])) // copy
        {
            $tmpArray = array();
            foreach ($_SESSION['mediaCopyFile'][2] as $tmp) {
                 if (file_exists($_SESSION['mediaCopyFile'][0].$tmp)) {
                     $tmpArray[] = $tmp;
                 }
            }

            if (count($tmpArray) > 0)
            {
                $_SESSION['mediaCopyFile'][0] = $_SESSION['mediaCopyFile'][0];
                $_SESSION['mediaCopyFile'][1] = $_SESSION['mediaCopyFile'][1];
                $_SESSION['mediaCopyFile'][2] = $tmpArray;
            }
            else
            {
                unset($_SESSION['mediaCopyFile']);
            }
        }

        // tree navigation
        $tmp = $this->arrWebPaths[$this->archive];
        if (substr($this->webPath, 0, strlen($tmp)) == $tmp)
        {
            $this->_objTpl->setVariable(array(  // navigation #1
                'MEDIA_TREE_NAV_MAIN'      => 'http://'.$_SERVER['HTTP_HOST'].$this->arrWebPaths[$this->archive],
                'MEDIA_TREE_NAV_MAIN_HREF' => 'index.php?cmd=Media&amp;archive='.$this->archive.'&amp;path='.$this->arrWebPaths[$this->archive]
            ));

            if (strlen($this->webPath) != strlen($tmp))
            {
                $tmpPath = substr($this->webPath, -(strlen($this->webPath) - strlen($tmp)));
                $tmpPath = explode('/', $tmpPath);
                $tmpLink = '';
                foreach ($tmpPath as $path)
                {
                    if (!empty($path))
                    {
                        $tmpLink .= $path.'/';
                        $this->_objTpl->setVariable(array(  // navigation #2
                            'MEDIA_TREE_NAV_DIR'      => $path,
                            'MEDIA_TREE_NAV_DIR_HREF' => 'index.php?cmd=Media&amp;archive='.$this->archive.'&amp;path='.$this->arrWebPaths[$this->archive].$tmpLink
                        ));
                        $this->_objTpl->parse('mediaTreeNavigation');
                    }
                }
            }
        }

        //check if a finished upload caused reloading of the page.
        //if yes, we know the added files and want to highlight them
        if (!empty($_GET['highlightUploadId'])) {
            $key = 'media_upload_files_'.($_GET['highlightUploadId']);
            if (isset($_SESSION[$key])) {
                $sessionHighlightCandidates = $_SESSION[$key]->toArray(); //an array with the filenames, set in mediaLib::uploadFinished
            }
            //clean up session; we do only highlight once
            unset($_SESSION[$key]);

            if (is_array($sessionHighlightCandidates)) {
                //make sure we don't cause any unexpected behaviour if we lost the session data
                $this->highlightName = $sessionHighlightCandidates;
            }
        }

        // Check if an image has been edited.
        // If yes, we know the edited file and want to highlight them.
        if (!empty($_GET['editedImage'])) {
            \Cx\Core\Core\Controller\Cx::instanciate()
                ->getMediaSourceManager()
                ->getThumbnailGenerator()
                ->createThumbnailFromPath($this->path . $_GET['editedImage'], true);
            $this->highlightName[] = $_GET['editedImage'];
        }
        if (!empty($_SESSION['media_highlight_name'])) {
            $this->highlightName = $_SESSION['media_highlight_name']->toArray();
            unset($_SESSION['media_highlight_name']);
        }

        // media directory tree
        $i       = 0;
        $dirTree = array();
        $this->getDirectoryTree($this->path, $searchTerm, $dirTree, !empty($searchTerm));
        $dirTree = $this->_sortDirTree($dirTree);

        $deleteUrl  = clone \Cx\Core\Core\Controller\Cx::instanciate()->getRequest()->getUrl();
        $deleteUrl->setParam('act', null);

        $previewUrl = clone $deleteUrl;
        $renameUrl  = clone $deleteUrl;
        $editUrl    = clone $deleteUrl;

        $redirect = urlencode(base64_encode($deleteUrl->toString(false)));
        $renameUrl->setParam('redirect', $redirect);
        $deleteUrl->setParam('redirect', $redirect);
        $editUrl  ->setParam('redirect', $redirect);
        $renameUrl->setParam('act', 'rename');
        $deleteUrl->setParam('act', 'delete');
        $editUrl  ->setParam('act', 'edit');

        foreach (array_keys($dirTree) as $key) {
            if(!is_array($dirTree[$key]['icon'])) {
                continue;
            }
            $mediaCount = count($dirTree[$key]['icon']);
            for ($x = 0; $x < $mediaCount; $x++) {
                $fileName = $dirTree[$key]['name'][$x];
                if (MediaLibrary::isIllegalFileName($fileName)) {
                    continue;
                }
                // colors
                $class = ($i % 2) ? 'row2' : 'row1';
                if (in_array($fileName, $this->highlightName)) { // highlight
                    $class .= '" style="background-color: ' . $this->highlightColor . ';';
                }
                if (   isset($_SESSION['mediaCutFile'])
                    && !empty($_SESSION['mediaCutFile'])
                    && $this->webPath == $_SESSION['mediaCutFile'][1]
                    && in_array($fileName, $_SESSION['mediaCutFile'][2])
                ) {  // cut
                    $class .= '" style="background-color: ' . $this->highlightCCColor . ';';
                }
                if (   isset($_SESSION['mediaCopyFile'])
                    && !empty($_SESSION['mediaCopyFile'])
                    && $this->webPath == $_SESSION['mediaCopyFile'][1]
                    && in_array($fileName, $_SESSION['mediaCopyFile'][2])
                ) {  // copy
                    $class .= '" style="background-color: ' . $this->highlightCCColor . ';';
                }

                $this->_objTpl->setVariable(array(// file
                    'MEDIA_DIR_TREE_ROW'    => $class,
                    'MEDIA_FILE_ICON'       => $dirTree[$key]['icon'][$x],
                    'MEDIA_FILE_NAME'       => $fileName,
                    'MEDIA_FILE_SIZE'       => $this->_formatSize($dirTree[$key]['size'][$x]),
                    'MEDIA_FILE_TYPE'       => $this->_formatType($dirTree[$key]['type'][$x]),
                    'MEDIA_FILE_DATE'       => $this->_formatDate($dirTree[$key]['date'][$x]),
                    'MEDIA_FILE_PERM'       => $this->_formatPerm($dirTree[$key]['perm'][$x], $key)
                ));
                $image        = false;
                $imagePreview = '';
                $mediaPath    = $this->path;
                $mediaWebPath = $this->webPath;
                if (!empty($searchTerm)) {
                    $mediaPath    = $dirTree[$key]['path'][$x] .'/';
                    $mediaWebPath = $mediaPath;
                    \Cx\Lib\FileSystem\FileSystem::path_relative_to_root($mediaWebPath);
                    $mediaWebPath = '/'. $mediaWebPath; // Filesystem removes the beginning slash(/)
                }
                $file = $fileName;
                if ($key == 'dir') {
                    $path = $mediaWebPath . $fileName . '/';
                    $previewUrl->setParam('act', null);
                    $previewUrl->setParam('file', null);
                } elseif ($key == 'file') {
                    $path = $mediaWebPath;

                    $filePath = $mediaPath . $fileName;
                    if ($this->_isImage($filePath)) {
                        $image        = true;
                        $imagePreview = 'javascript:expandcontent(\'preview_' . $fileName . '\');';
                    } else {
                        $previewUrl->setParam('act', 'download');
                        $previewUrl->setParam('file', $file);
                    }
                }
                $deleteUrl->setParam('path', $mediaWebPath);
                $deleteUrl->setParam('file', $file);

                $renameUrl->setParam('path', $mediaWebPath);
                $renameUrl->setParam('file', $file);

                $editUrl->setParam('path', $mediaWebPath);
                $editUrl->setParam('file', $file);

                if (!$image) {
                    $previewUrl->setParam('path', $path);
                }

                // show thumbnail
                if ($image) {
                    // make thumbnail if it doesn't exist
                    $tmpSize    = @getimagesize($mediaPath . $fileName);
                    $thumbnails = \Cx\Core\Core\Controller\Cx::instanciate()
                                    ->getMediaSourceManager()
                                    ->getThumbnailGenerator()
                                    ->createThumbnailFromPath($mediaPath . $fileName);
                    $thumb = $mediaWebPath . $thumbnails[0];
                    if (in_array($fileName, $this->highlightName)) {
                        $thumb .= '?lastAccess=' . fileatime($mediaPath . $fileName);
                    }

                    $this->_objTpl->setVariable(array(// thumbnail
                        'MEDIA_FILE_NAME_SIZE'      => $tmpSize[0] . ' x ' . $tmpSize[1],
                        'MEDIA_FILE_NAME_PRE'       => 'preview_' . $fileName,
                        'MEDIA_FILE_NAME_IMG_HREF'  => $mediaWebPath . $fileName,
                        'MEDIA_FILE_NAME_IMG_SRC'   => $thumb,
                        // TODO: size of thumbnails not supported by ThumbnailGenerator
                        //'MEDIA_FILE_NAME_IMG_SIZE'  => $thumbnails[0]['size']
                    ));
                    $this->_objTpl->parse('mediaShowThumbnail');

                    $this->_objTpl->setVariable(array(
                        'MEDIA_FILE_EDIT_HREF' => $editUrl->toString(false),
                        'MEDIA_EDIT'           => $_ARRAYLANG['TXT_MEDIA_EDIT'],
                    ));
                    $this->_objTpl->parse('mediaImageEdit');
                }

                $this->_objTpl->setVariable(array(// action
                    'MEDIA_FILE_NAME_HREF'          => $image ? $imagePreview : $previewUrl->toString(false),
                    'MEDIA_FILE_RENAME_HREF'        => $renameUrl->toString(false),
                    'MEDIA_FILE_DELETE_HREF'        => $deleteUrl->toString(false),
                    'MEDIA_FILE_FILESHARING_HREF'   => 'index.php?cmd=Media&amp;archive=' . $this->archive . '&amp;act=filesharing&amp;path=' . $mediaWebPath . '&amp;file=' . $fileName,
                    'MEDIA_FILESHARING_STATE'       => (\Cx\Modules\FileSharing\Controller\FileSharingLib::isShared(null, (isset($_GET['path']) ? $_GET['path'] : ASCMS_FILESHARING_WEB_PATH . '/') . $fileName) ? '_green' : '_red'),
                    'MEDIA_RENAME'                  => $_ARRAYLANG['TXT_MEDIA_RENAME'],
                    'MEDIA_DELETE'                  => $_ARRAYLANG['TXT_MEDIA_DELETE'],
                    'MEDIA_FILESHARING'             => $_ARRAYLANG['TXT_FILESHARING_MODULE'],
                ));
                if ($this->archive == "FileSharing" && !is_dir($mediaPath . $fileName)) {
                    $this->_objTpl->parse('mediaFilesharing');
                } else {
                    $this->_objTpl->hideBlock('mediaFilesharing');
                }
                $this->_objTpl->parse('mediaDirectoryTree');
                $i++;
            }
        }

        // empty dir or php safe mode restriction
        if ($i == 0 || !@opendir($this->path)) {
            $tmpMessage = $_ARRAYLANG['TXT_MEDIA_DIR_EMPTY'];
            if (!@opendir($this->path)) {
                $tmpMessage = 'PHP Safe Mode Restriction!';
            }
            $this->_objTpl->setVariable(array(
                'TXT_MEDIA_DIR_EMPTY'   => $tmpMessage,
                'MEDIA_SELECT_STATUS'   => ' disabled',
            ));
            $this->_objTpl->parse('mediaEmptyDirectory');
        } elseif (empty ($searchTerm)) { // not empty dir and not search results
            $this->_objTpl->setVariable(array(
                'TXT_SELECT_ALL'           => $_CORELANG['TXT_SELECT_ALL'],
                'TXT_DESELECT_ALL'         => $_CORELANG['TXT_DESELECT_ALL'],
                'TXT_MEDIA_SELECT_ACTION'  => $_ARRAYLANG['TXT_MEDIA_SELECT_ACTION'],
                'TXT_MEDIA_CUT'            => $_ARRAYLANG['TXT_MEDIA_CUT'],
                'TXT_MEDIA_COPY'           => $_ARRAYLANG['TXT_MEDIA_COPY'],
                'TXT_MEDIA_DELETE'         => $_ARRAYLANG['TXT_MEDIA_DELETE']
            ));
            $this->_objTpl->parse('mediaSelectAction');
            $this->_objTpl->setVariable('MEDIA_ARCHIVE', $this->archive);
        }
        // paste media
        if (empty($searchTerm) && (isset($_SESSION['mediaCutFile']) || isset($_SESSION['mediaCopyFile']))) {
            $this->_objTpl->setVariable(array(
                'MEDIDA_PASTE_ACTION'      => 'index.php?cmd=Media&amp;archive='.$this->archive.'&amp;act=paste&amp;path='.$this->webPath,
                'TXT_MEDIA_PASTE'          => $_ARRAYLANG['TXT_MEDIA_PASTE']
            ));
            $this->_objTpl->parse('mediaActionPaste');
        }

        if (empty($searchTerm)) {
            //data we want to remember for handling the uploaded files
            $data = array(
                'path'    => $this->path,
                'webPath' => $this->webPath
            );

            $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
            $uploader->setCallback('mediaCallbackJs');
            $uploader->setFinishedCallback(array(
                ASCMS_CORE_MODULE_PATH . '/Media/Controller/MediaLibrary.class.php',
                '\Cx\Core_Modules\Media\Controller\MediaLibrary',
                'uploadFinished'
            ));
            $uploader->setOptions(//Set html attributes for styling or javascript.
                array(
                    'id'    => 'media_browse_button',
                    'type'  => 'button'
                )
            );
            $uploader->setData($data);
            //end of uploader button handling

            $this->_objTpl->setVariable(array(
                // upload files
                'MEDIA_UPLOADER_BUTTON'         => $uploader->getXHtml($_ARRAYLANG['TXT_MEDIA_UPLOAD_FILES']),
                'MEDIA_UPLOAD_FILES_ACTION'     => 'index.php?cmd=Media&amp;archive='.$this->archive.'&amp;act=upload&amp;path=' . $this->webPath,
                'TXT_MEDIA_UPLOAD_FILES'        => $_ARRAYLANG['TXT_MEDIA_UPLOAD_FILES'],
                'TXT_MEDIA_UPLOAD'              => $_ARRAYLANG['TXT_MEDIA_UPLOAD'],
                'TXT_MEDIA_FORCE_OVERWRITE'     => $_ARRAYLANG['TXT_MEDIA_FORCE_OVERWRITE'],
                'TXT_MEDIA_MAKE_SELECTION'      => $_ARRAYLANG['TXT_MEDIA_MAKE_SELECTION'],
                'TXT_MEDIA_SELECT_UPLOAD_FILE'  => $_ARRAYLANG['TXT_MEDIA_SELECT_UPLOAD_FILE'],
                // create new directory
                'MEDIA_CREATE_DIR_ACTION'       => 'index.php?cmd=Media&amp;archive='.$this->archive.'&amp;act=newDir&amp;path=' . $this->webPath,
                'TXT_MEDIA_NEW_DIRECTORY'       => $_ARRAYLANG['TXT_MEDIA_NEW_DIRECTORY'],
                'TXT_MEDIA_NAME'                => $_ARRAYLANG['TXT_MEDIA_NAME'],
                'TXT_MEDIA_CREATE'              => $_ARRAYLANG['TXT_MEDIA_CREATE'],
            ));
        } else {
            $this->_objTpl->hideBlock('media_archive_actions_block');
            $this->_objTpl->hideBlock('media_archive_newdir_block');
        }

        // parse variables
        $tmpHref  = 'index.php?cmd=Media&amp;archive='.$this->archive.'&amp;path=' . $this->webPath . (!empty($searchTerm) ? '&amp;term='. contrexx_raw2xhtml($searchTerm) : '');
        $tmpIcon  = $this->_sortingIcons();
        $tmpClass = $this->_sortingClass();

        $this->_objTpl->setVariable(array(  // parse dir content
            'MEDIA_NAME_HREF'          => $tmpHref . '&amp;sort=name&amp;sort_desc='. ($this->sortBy == 'name' && !$this->sortDesc),
            'MEDIA_SIZE_HREF'          => $tmpHref . '&amp;sort=size&amp;sort_desc='. ($this->sortBy == 'size' && !$this->sortDesc),
            'MEDIA_TYPE_HREF'          => $tmpHref . '&amp;sort=type&amp;sort_desc='. ($this->sortBy == 'type' && !$this->sortDesc),
            'MEDIA_DATE_HREF'          => $tmpHref . '&amp;sort=date&amp;sort_desc='. ($this->sortBy == 'date' && !$this->sortDesc),
            'MEDIA_PERM_HREF'          => $tmpHref . '&amp;sort=perm&amp;sort_desc='. ($this->sortBy == 'perm' && !$this->sortDesc),
            'TXT_MEDIA_FILE_NAME'      => $_ARRAYLANG['TXT_MEDIA_FILE_NAME'],
            'TXT_MEDIA_FILE_SIZE'      => $_ARRAYLANG['TXT_MEDIA_FILE_SIZE'],
            'TXT_MEDIA_FILE_TYPE'      => $_ARRAYLANG['TXT_MEDIA_FILE_TYPE'],
            'TXT_MEDIA_FILE_DATE'      => $_ARRAYLANG['TXT_MEDIA_FILE_DATE'],
            'TXT_MEDIA_FILE_PERM'      => $_ARRAYLANG['TXT_MEDIA_FILE_PERM'],
            'TXT_MEDIA_FILE_FUNCTIONS' => $_ARRAYLANG['TXT_FUNCTIONS'],
            'TXT_MEDIA_CHECK_NAME'          => $_ARRAYLANG['TXT_MEDIA_CHECK_NAME'],
            'TXT_MEDIA_CONFIRM_DELETE_2'    => $_ARRAYLANG['TXT_MEDIA_CONFIRM_DELETE_2'],
            'TXT_MEDIA_SEARCH'         => $_CORELANG['TXT_SEARCH'],
            'MEDIA_NAME_ICON'          => isset($tmpIcon['name']) ? $tmpIcon['name'] : '',
            'MEDIA_SIZE_ICON'          => isset($tmpIcon['size']) ? $tmpIcon['size'] : '',
            'MEDIA_TYPE_ICON'          => isset($tmpIcon['type']) ? $tmpIcon['type'] : '',
            'MEDIA_DATE_ICON'          => isset($tmpIcon['date']) ? $tmpIcon['date'] : '',
            'MEDIA_PERM_ICON'          => isset($tmpIcon['perm']) ? $tmpIcon['perm'] : '',
            'MEDIA_NAME_CLASS'         => isset($tmpClass['name']) ? $tmpIcon['name'] : '',
            'MEDIA_SIZE_CLASS'         => isset($tmpClass['size']) ? $tmpIcon['size'] : '',
            'MEDIA_TYPE_CLASS'         => isset($tmpClass['type']) ? $tmpIcon['type'] : '',
            'MEDIA_DATE_CLASS'         => isset($tmpClass['date']) ? $tmpIcon['date'] : '',
            'MEDIA_PERM_CLASS'         => isset($tmpClass['perm']) ? $tmpIcon['perm'] : '',
            'MEDIA_ARCHIVE_NAME'       => $this->archive,
            'MEDIA_ARCHIVE_PATH'       => $this->webPath,
            'MEDIA_SEARCH_TERM'        => contrexx_raw2xhtml(rawurldecode($searchTerm)),
            'CSRF'                     => \Cx\Core\Csrf\Controller\Csrf::param(),
            // java script
            'MEDIA_DO_ACTION_PATH'      => rawurlencode($this->webPath),
            'MEDIA_JAVA_SCRIPT_PREVIEW' => $this->_getJavaScriptCodePreview(),
        ));
    }


    /**
    * Rename Media Data
    *
    * @global  array   $_ARRAYLANG
    * @return  string  parsed content
    */
    function _renameMedia() {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_media_rename.html', true, true);
        $this->pageTitle = $_ARRAYLANG['TXT_MEDIA_RENAME_FILE'];

        $check = true;
        if (empty($this->getFile) || empty($this->getPath)) $check = false;
        if ($check) {
            if (!file_exists($this->path . $this->getFile)) $check = false;
        }

        if ($check == false || MediaLibrary::isIllegalFileName($this->getFile)) { // file doesn't exist
            $this->_objTpl->setVariable(array(  // ERROR
                'TXT_MEDIA_ERROR_OCCURED'    => $_ARRAYLANG['TXT_MEDIA_ERROR_OCCURED'],
                'TXT_MEDIA_FILE_DONT_EXISTS' => MediaLibrary::isIllegalFileName($this->getFile) ? $_ARRAYLANG['TXT_MEDIA_FILE_DONT_EDIT'] : $_ARRAYLANG['TXT_MEDIA_FILE_DONT_EXISTS']
            ));
            $this->_objTpl->parse('mediaErrorFile');
        } else if ($check == true) { // file exists
            $this->_objTpl->setVariable(array(  // java script
                'TXT_MEDIA_RENAME_NAME'  => $_ARRAYLANG['TXT_MEDIA_RENAME_NAME'],
                'TXT_MEDIA_RENAME_EXT'   => $_ARRAYLANG['TXT_MEDIA_RENAME_EXT']
            ));

            $redirect = !empty($_GET['redirect']) ? '&amp;redirect='. contrexx_raw2xhtml($_GET['redirect']) : '';
            $this->_objTpl->setVariable(array(  // txt
                'MEDIA_EDIT_ACTION'         => 'index.php?cmd=Media&amp;archive='.$this->archive.'&amp;act=ren&amp;path=' . $this->webPath . $redirect,
                'TXT_MEDIA_EDIT_FILE'       => $_ARRAYLANG['TXT_MEDIA_EDIT_FILE'],
                'MEDIA_DIR'                 => $this->webPath,
                'MEDIA_FILE'                => $this->getFile,
                'TXT_MEDIA_INSERT_AS_COPY'  => $_ARRAYLANG['TXT_MEDIA_INSERT_AS_COPY'],
                'TXT_MEDIA_SAVE'            => $_ARRAYLANG['TXT_MEDIA_SAVE'],
                'TXT_MEDIA_RESET'           => $_ARRAYLANG['TXT_MEDIA_RESET'],
            ));

            $icon     = $this->_getIcon($this->path . $this->getFile);
            $fileName = $this->getFile;

            // extension
            if (is_file($this->path . $this->getFile)) {
                $info     = pathinfo($this->getFile);
                $fileExt  = $info['extension'];
                $ext      = (!empty($fileExt)) ? '.' . $fileExt : '';
                $fileName = substr($this->getFile, 0, strlen($this->getFile) - strlen($ext));

                $this->_objTpl->setVariable(array(
                    'MEDIA_ORGFILE_EXT'   => $fileExt . ''
                ));
                $this->_objTpl->parse('mediaFileExt');
            }

            // edit name
            $this->_objTpl->setVariable(array(
                'MEDIA_FILE_ICON'     => self::_getIconWebPath().$icon.'.png',
                'MEDIA_ORGFILE_NAME'  => $fileName
            ));
            $this->_objTpl->parse('mediaFile');
        }
        // variables
        $this->_objTpl->setVariable(array(
            'TXT_MEDIA_BACK'     => $_ARRAYLANG['TXT_MEDIA_BACK'],
            'MEDIA_BACK_HREF'    => 'index.php?cmd=Media&amp;archive='.$this->archive.'&amp;path='.$this->webPath,
        ));
    }


    /**
     * Shows the image manipulation component.
     *
     * @global  array   $_ARRAYLANG
     * @return  string  Parsed content.
     */
    function editMedia(){
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_media_edit.html', true, true);
        $this->pageTitle = $_ARRAYLANG['TXT_MEDIA_EDIT_FILE'];

        if (isset($_GET['saveError']) && $_GET['saveError'] === 'true') {
            $this->_objTpl->setVariable(array(
                'TXT_MEDIA_ERROR_OCCURED' => $_ARRAYLANG['TXT_MEDIA_ERROR_OCCURED'],
                'TXT_MEDIA_ERROR_MESSAGE' => $_ARRAYLANG['TXT_MEDIA_CANNOT_SAVE_IMAGE']
            ));
            $this->_objTpl->parse('mediaErrorFile');
            return;
        }

        // Activate cx
        \JS::activate('cx');

        // Activate jQuery and imgAreaSelect
        \JS::activate('jquery');
        \JS::activate('jquery-imgareaselect');

        try {
            // Get quality options from the settings
            $arrImageSettings = $this->getImageSettings();
        } catch (\Exception $e) {
            \DBG::msg('Could not query image settings: '.$e->getMessage());
        }

        $check = true;
        empty($this->getFile) ? $check = false : '';
        empty($this->getPath) ? $check = false : '';
        !file_exists($this->path.$this->getFile) ? $check = false : '';


        if ($check) { // File exists
            $this->_objTpl->setVariable(array(
                'TXT_MEDIA_SAVE'       => $_ARRAYLANG['TXT_MEDIA_SAVE'],
                'TXT_MEDIA_SAVE_AS'    => $_ARRAYLANG['TXT_MEDIA_SAVE_AS'],
                'TXT_MEDIA_RESET'      => $_ARRAYLANG['TXT_MEDIA_RESET'],
                'TXT_MEDIA_PREVIEW'    => $_ARRAYLANG['TXT_PREVIEW'],
                'MEDIA_EDIT_ACTION'    => 'index.php?cmd=Media&amp;archive='.$this->archive.'&amp;act=editImage&amp;path='.$this->webPath,
                'MEDIA_DIR'            => $this->webPath,
                'MEDIA_FILE'           => $this->getFile,
            ));

            $icon     = $this->_getIcon($this->path.$this->getFile);
            $info     = pathinfo($this->getFile);
            $fileExt  = $info['extension'];
            $ext      = !empty($fileExt) ? '.'.$fileExt : '';
            $fileName = substr($this->getFile, 0, strlen($this->getFile) - strlen($ext));

            // Icon, file & extension name
            $this->_objTpl->setVariable(array(
                'MEDIA_FILE_ICON' => self::_getIconWebPath().$icon.'.png',
                'MEDIA_FILE_DIR'  => $this->webPath,
                'MEDIA_FILE_NAME' => $fileName,
                'MEDIA_FILE_EXT'  => $fileExt,
            ));

            // Edit image
            $imageSize  = @getimagesize($this->path.$this->getFile);

            $this->_objTpl->setVariable(array(
                'TXT_MEDIA_IMAGE_MANIPULATION'    => $_ARRAYLANG['TXT_MEDIA_IMAGE_MANIPULATION'],
                'TXT_MEDIA_WIDTH'                 => $_ARRAYLANG['TXT_MEDIA_WIDTH'],
                'TXT_MEDIA_HEIGHT'                => $_ARRAYLANG['TXT_MEDIA_HEIGHT'],
                'TXT_MEDIA_BALANCE'               => $_ARRAYLANG['TXT_MEDIA_BALANCE'],
                'TXT_MEDIA_QUALITY'               => $_ARRAYLANG['TXT_MEDIA_QUALITY'],
                'TXT_MEDIA_SAVE'                  => $_ARRAYLANG['TXT_MEDIA_SAVE'],
                'TXT_MEDIA_RESET'                 => $_ARRAYLANG['TXT_MEDIA_RESET'],
                'TXT_MEDIA_SET_IMAGE_NAME'        => $_ARRAYLANG['TXT_MEDIA_SET_IMAGE_NAME'],
                'TXT_MEDIA_CONFIRM_REPLACE_IMAGE' => addslashes($_ARRAYLANG['TXT_MEDIA_CONFIRM_REPLACE_IMAGE']),
                'TXT_MEDIA_REPLACE'               => $_ARRAYLANG['TXT_MEDIA_REPLACE'],
                'TXT_MEDIA_OR'                    => $_ARRAYLANG['TXT_MEDIA_OR'],
                'TXT_MEDIA_SAVE_NEW_COPY'         => $_ARRAYLANG['TXT_MEDIA_SAVE_NEW_COPY'],
                'TXT_MEDIA_CROP'                  => $_ARRAYLANG['TXT_MEDIA_CROP'],
                'TXT_MEDIA_CROP_INFO'             => $_ARRAYLANG['TXT_MEDIA_CROP_INFO'],
                'TXT_MEDIA_CANCEL'                => $_ARRAYLANG['TXT_MEDIA_CANCEL'],
                'TXT_MEDIA_ROTATE'                => $_ARRAYLANG['TXT_MEDIA_ROTATE'],
                'TXT_MEDIA_ROTATE_INFO'           => $_ARRAYLANG['TXT_MEDIA_ROTATE_INFO'],
                'TXT_MEDIA_SCALE_COMPRESS'        => $_ARRAYLANG['TXT_MEDIA_SCALE_COMPRESS'],
                'TXT_MEDIA_SCALE_INFO'            => $_ARRAYLANG['TXT_MEDIA_SCALE_INFO'],
                'TXT_MEDIA_PREVIEW'               => $_ARRAYLANG['TXT_MEDIA_PREVIEW'],
                'MEDIA_IMG_WIDTH'                 => $imageSize[0],
                'MEDIA_IMG_HEIGHT'                => $imageSize[1],
            ));

            foreach ($this->arrImageQualityValues as $value) {
                $this->_objTpl->setVariable(array(
                    'IMAGE_QUALITY_VALUE'          => $value,
                    'IMAGE_QUALITY_OPTION_CHECKED' => $value == $arrImageSettings['image_compression'] ? 'selected="selected"' : '',
                ));
                $this->_objTpl->parse('mediaEditImageQualityOptions');
            }

            $this->_objTpl->parse('mediaEditImage');
        } else { // File doesn't exist
            $this->_objTpl->setVariable(array(
                'TXT_MEDIA_ERROR_OCCURED' => $_ARRAYLANG['TXT_MEDIA_ERROR_OCCURED'],
                'TXT_MEDIA_ERROR_MESSAGE' => $_ARRAYLANG['TXT_MEDIA_FILE_DONT_EXISTS']
            ));
            $this->_objTpl->parse('mediaErrorFile');
        }
        $redirect = '';
        if (!empty($_REQUEST['redirect'])) {
            $redirect = \FWUser::getRedirectUrl(urlencode(base64_decode(urldecode($_REQUEST['redirect']))));
        }
        // Variables
        $this->_objTpl->setVariable(array(
            'CSRF'                       => \Cx\Core\Csrf\Controller\Csrf::param(),
            'MEDIA_EDIT_AJAX_ACTION'     => 'index.php?cmd=Media&archive='.$this->archive.'&act=editImage&path='.$this->webPath,
            'MEDIA_EDIT_REDIRECT'        => $redirect,
            'MEDIA_BACK_HREF'            => 'index.php?cmd=Media&amp;archive='.$this->archive.'&amp;path='.$this->webPath,
            'MEDIA_FILE_IMAGE_SRC'       => 'index.php?cmd=Media&archive='.$this->archive.'&act=getImage&path='.$this->webPath.'&file='.$this->getFile.'&'.\Cx\Core\Csrf\Controller\Csrf::param(),
            'MEDIA_IMAGE_WIDTH'          => !empty($imageSize) ? intval($imageSize[0]) : 0,
            'MEDIA_IMAGE_HEIGHT'         => !empty($imageSize) ? intval($imageSize[1]) : 0,
            'MEDIA_IMAGE_CROP_WIDTH'     => $arrImageSettings['image_cut_width'],
            'MEDIA_IMAGE_CROP_HEIGHT'    => $arrImageSettings['image_cut_height'],
            //'MEDIA_IMAGE_RESIZE_WIDTH'   => $arrImageSettings['image_scale_width'],
            //'MEDIA_IMAGE_RESIZE_HEIGHT'  => $arrImageSettings['image_scale_height'],
            'MEDIA_IMAGE_RESIZE_QUALITY' => $arrImageSettings['image_compression'],
        ));
    }

    /**
     * Display and editing Media settings
     *
     * @return    string    parsed content
     */
    function _settings()
    {
        global $_CORELANG, $_ARRAYLANG, $objDatabase;

        \JS::activate('jquery');
        $this->_arrSettings = $this->createSettingsArray();

        $objFWUser = \FWUser::getFWUserObject();

        $this->_objTpl->loadTemplateFile('module_media_settings.html', true, true);
        $archive = '';
        if (isset($_GET['archive'])) {
            $archive = contrexx_input2raw($_GET['archive']);
        }
        if ($archive == 'FileSharing') {
            $this->_objTpl->hideBlock('mediaarchive_section');
            $objFileshare = new \Cx\Modules\FileSharing\Controller\FileSharingManager($this->_objTpl);
            $objFileshare->parseSettingsPage();
        } else {
            $this->_objTpl->touchBlock('mediaarchive_section');
        }

        $this->pageTitle = $_ARRAYLANG['TXT_MEDIA_SETTINGS'];

        $this->_objTpl->setGlobalVariable(array(
            'TXT_MEDIA_ARCHIVE'                     => $_ARRAYLANG['TXT_MEDIA_ARCHIVE'],
            'TXT_FILESHARING'                       => $_ARRAYLANG['TXT_FILESHARING_MODULE'],
            'TXT_MEDIA_SETTINGS'                    => $_ARRAYLANG['TXT_MEDIA_SETTINGS'],
            'TXT_MEDIA_ADD'                         => $_ARRAYLANG['TXT_MEDIA_ADD'],
            'TXT_MEDIA_MANAGE'                      => $_ARRAYLANG['TXT_MEDIA_MANAGE'],
            'TXT_MEDIA_ACCESS_SETTINGS'             => $_ARRAYLANG['TXT_MEDIA_ACCESS_SETTINGS'],
            'TXT_MEDIA_FRONTEND_FILE_UPLOAD_DESC'   => $_ARRAYLANG['TXT_MEDIA_FRONTEND_FILE_UPLOAD_DESC'],
            'TXT_MEDIA_FRONTEND_FILE_UPLOAD'        => $_ARRAYLANG['TXT_MEDIA_FRONTEND_FILE_UPLOAD'],
            'TXT_MEDIA_ADDING_DENIED_FOR_ALL'       => $_ARRAYLANG['TXT_MEDIA_ADDING_DENIED_FOR_ALL'],
            'TXT_MEDIA_ADDING_ALLOWED_FOR_ALL'      => $_ARRAYLANG['TXT_MEDIA_ADDING_ALLOWED_FOR_ALL'],
            'TXT_MEDIA_ADDING_ALLOWED_FOR_GROUP'    => $_ARRAYLANG['TXT_MEDIA_ADDING_ALLOWED_FOR_GROUP'],
            'TXT_MEDIA_AVAILABLE_USER_GROUPS'       => $_ARRAYLANG['TXT_MEDIA_AVAILABLE_USER_GROUPS'],
            'TXT_MEDIA_ASSIGNED_USER_GROUPS'        => $_ARRAYLANG['TXT_MEDIA_ASSIGNED_USER_GROUPS'],
            'TXT_MEDIA_CHECK_ALL'                   => $_ARRAYLANG['TXT_MEDIA_CHECK_ALL'],
            'TXT_MEDIA_UNCHECK_ALL'                 => $_ARRAYLANG['TXT_MEDIA_UNCHECK_ALL'],
            'TXT_BUTTON_SAVE'                       => $_ARRAYLANG['TXT_MEDIA_SAVE'],
            'TXT_CORE_MODULE_MEDIA_SEARCH_FUNCTION' => $_ARRAYLANG['TXT_CORE_MODULE_MEDIA_SEARCH_FUNCTION'],
            'TXT_CORE_MODULE_MEDIA_ENABLE_SEARCH_FUNCTIONALITY' => $_ARRAYLANG['TXT_CORE_MODULE_MEDIA_ENABLE_SEARCH_FUNCTIONALITY'],
            'TXT_CORE_MODULE_MEDIA_PRETTY_FORMAT_FUNCTION' => $_ARRAYLANG['TXT_CORE_MODULE_MEDIA_PRETTY_FORMAT_FUNCTION'],
            'TXT_CORE_MODULE_MEDIA_ENABLE_PRETTY_FORMAT_FUNCTIONALITY' => $_ARRAYLANG['TXT_CORE_MODULE_MEDIA_ENABLE_PRETTY_FORMAT_FUNCTIONALITY'],
            'TXT_CORE_MODULE_MEDIA_SEARCH'          => $_ARRAYLANG['TXT_CORE_MODULE_MEDIA_SEARCH'],
            'TXT_CORE_MODULE_MEDIA_REPLACE'         => $_ARRAYLANG['TXT_CORE_MODULE_MEDIA_REPLACE'],
            'TXT_CORE_MODULE_MEDIA_PRETTY_FORMAT_FUNCTION_TOOLTIP'=> $_ARRAYLANG['TXT_CORE_MODULE_MEDIA_PRETTY_FORMAT_FUNCTION_TOOLTIP'],
            'TXT_CORE_MODULE_MEDIA_DISABLED'        => $_ARRAYLANG['TXT_CORE_MODULE_MEDIA_DISABLED'],
        ));

        for ($k = 1; $k <= 4; $k++)
        {
            $arrAssociatedGroupOptions          = array();
            $arrNotAssociatedGroupOptions       = array();
            $arrAssociatedGroups = array();
            $arrAssociatedGroupManageOptions    = array();
            $arrNotAssociatedGroupManageOptions = array();
            $arrAssociatedManageGroups = array();

            $mediaAccessSetting                 = $this->_arrSettings['media' . $k . '_frontend_changable'];
            $mediaManageSetting                 = $this->_arrSettings['media' . $k . '_frontend_managable'];
            if (!is_numeric($mediaAccessSetting))
            {
                // Get all groups
                $objGroup = $objFWUser->objGroup->getGroups();
            } else {
                // Get access groups
                $objGroup = $objFWUser->objGroup->getGroups(
                    array('dynamic' => $mediaAccessSetting)
                );
                $arrAssociatedGroups = $objGroup->getLoadedGroupIds();
            }


            $objGroup = $objFWUser->objGroup->getGroups();
            while (!$objGroup->EOF) {
                $option = '<option value="'.$objGroup->getId().'">'.htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET).' ['.$objGroup->getType().']</option>';

                if (in_array($objGroup->getId(), $arrAssociatedGroups)) {
                    $arrAssociatedGroupOptions[] = $option;
                } else {
                    $arrNotAssociatedGroupOptions[] = $option;
                }

                $objGroup->next();
            }

            if (!is_numeric($mediaManageSetting))
            {
                // Get all groups
                $objGroup = $objFWUser->objGroup->getGroups();
            } else {
                // Get access groups
                $objGroup = $objFWUser->objGroup->getGroups(
                    array('dynamic' => $mediaManageSetting)
                );
                $arrAssociatedManageGroups = $objGroup->getLoadedGroupIds();
            }
            $objGroup = $objFWUser->objGroup->getGroups();
            while (!$objGroup->EOF) {
                $option = '<option value="'.$objGroup->getId().'">'.htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET).' ['.$objGroup->getType().']</option>';

                if (in_array($objGroup->getId(), $arrAssociatedManageGroups)) {
                    $arrAssociatedGroupManageOptions[] = $option;
                } else {
                    $arrNotAssociatedGroupManageOptions[] = $option;
                }

                $objGroup->next();
            }

            $this->_objTpl->setVariable(array(
                    'MEDIA_ARCHIVE_NUMBER'                  => $k,
                    'MEDIA_TAB_STYLE'                       => ($k == 1) ? 'block' : 'none',
                    'MEDIA_ALLOW_USER_CHANGE_ON'            => ($this->_arrSettings['media' . $k . '_frontend_changable'] == 'on') ? 'checked="checked"' : '',
                    'MEDIA_ALLOW_USER_CHANGE_OFF'           => ($this->_arrSettings['media' . $k . '_frontend_changable'] == 'off') ? 'checked="checked"' : '',
                    'MEDIA_ALLOW_USER_CHANGE_GROUP'         => (is_numeric($this->_arrSettings['media' . $k . '_frontend_changable'])) ? 'checked="checked"' : '',
                    'MEDIA_ACCESS_DISPLAY'                  => (is_numeric($this->_arrSettings['media' . $k . '_frontend_changable'])) ? 'block' : 'none',
                    'MEDIA_ACCESS_ASSOCIATED_GROUPS'        => implode("\n", $arrAssociatedGroupOptions),
                    'MEDIA_ACCESS_NOT_ASSOCIATED_GROUPS'    => implode("\n", $arrNotAssociatedGroupOptions),
                    'MEDIA_ALLOW_USER_MANAGE_ON'            => ($this->_arrSettings['media' . $k . '_frontend_managable'] == 'on') ? 'checked="checked"' : '',
                    'MEDIA_ALLOW_USER_MANAGE_OFF'           => ($this->_arrSettings['media' . $k . '_frontend_managable'] == 'off') ? 'checked="checked"' : '',
                    'MEDIA_ALLOW_USER_MANAGE_GROUP'         => (is_numeric($this->_arrSettings['media' . $k . '_frontend_managable'])) ? 'checked="checked"' : '',
                    'MEDIA_MANAGE_DISPLAY'                  => (is_numeric($this->_arrSettings['media' . $k . '_frontend_managable'])) ? 'block' : 'none',
                    'MEDIA_MANAGE_ASSOCIATED_GROUPS'        => implode("\n", $arrAssociatedGroupManageOptions),
                    'MEDIA_MANAGE_NOT_ASSOCIATED_GROUPS'    => implode("\n", $arrNotAssociatedGroupManageOptions),
                    'MEDIA_ALLOW_USER_SEARCH_ON'            => ($this->_arrSettings['media' . $k . '_frontend_search'] == 'on') ? 'checked="checked"' : '',
                    'MEDIA_ALLOW_USER_SEARCH_OFF'           => ($this->_arrSettings['media' . $k . '_frontend_search'] == 'off') ? 'checked="checked"' : '',
                    'MEDIA_PRETTY_FORMAT_ON'                => ($this->_arrSettings['media' . $k . '_pretty_file_names'] == 'on') ? 'checked="checked"' : '',
                    'MEDIA_PRETTY_FORMAT_OFF'               => ($this->_arrSettings['media' . $k . '_pretty_file_names'] == 'off') ? 'checked="checked"' : '',
                    'MEDIA_PRETTY_FORMAT_REGEX'             => contrexx_raw2xhtml($this->_arrSettings['media' . $k . '_pretty_file_name_regexp']),
                    'MEDIA_PRETTY_FORMAT_DISPLAY'           => ($this->_arrSettings['media' . $k . '_pretty_file_names'] == 'on') ? 'block' : 'none',
            ));
            if ($this->_objTpl->blockExists("mediaAccessSection")) {
                $this->_objTpl->parse("mediaAccessSection");
            }
        }
    }

    /**
     * Validate and save settings from $_POST into the database.
     *
     * @global  ADONewConnection
     * @global  array $_ARRAYLANG
     */
    function _saveSettings() {
        global $objDatabase, $_ARRAYLANG;

        $this->_arrSettings = $this->createSettingsArray();
        for ($i = 1; $i <=4; $i++)
        {
            $frontendSearchkey     = 'mediaSettings_Media'. $i .'FrontendSearch';
            $settingFrontendSearch = !empty($_POST[$frontendSearchkey]) && $_POST[$frontendSearchkey] == 'on'
                                      ? 'on' : 'off';

            $objDatabase->Execute('
                UPDATE
                    '.DBPREFIX.'module_media_settings
                SET
                    `value` = "' . $settingFrontendSearch . '"
                WHERE
                    `name` = "media' . $i . '_frontend_search"
            ');

            $prettyFormatKey     = 'mediaSettings_Media'. $i .'PrettyFormat';
            $settingPrettyFormat = !empty($_POST[$prettyFormatKey]) && $_POST[$prettyFormatKey] == 'on'
                                      ? 'on' : 'off';
            $objDatabase->Execute('
                UPDATE
                    `'.DBPREFIX.'module_media_settings`
                SET
                    `value` = "' . $settingPrettyFormat . '"
                WHERE
                    `name` = "media' . $i . '_pretty_file_names"
            ');

            $prettyFormatRegexpKey     = 'mediaSettings_Media'. $i .'PrettyFormatRegexp';
            $settingPrettyFormatRegexp = isset($_POST[$prettyFormatRegexpKey]) ? contrexx_input2raw($_POST[$prettyFormatRegexpKey]) : '';
            $objDatabase->Execute('
                UPDATE
                    `'.DBPREFIX.'module_media_settings`
                SET
                    `value` = "' . contrexx_raw2db($settingPrettyFormatRegexp) . '"
                WHERE
                    `name` = "media' . $i . '_pretty_file_name_regexp"
            ');

            $oldMediaSetting = $this->_arrSettings['media' . $i . '_frontend_changable'];
            $newMediaSetting = '';
            if (isset($_POST['mediaSettings_Media' . $i . 'FrontendChangable'])) {
                $newMediaSetting = $_POST['mediaSettings_Media' . $i . 'FrontendChangable'];
            }

            if (!is_numeric($newMediaSetting))
            {
                if (is_numeric($oldMediaSetting))
                {
                    // remove AccessId
                    \Permission::removeAccess($oldMediaSetting, 'dynamic');
                }
                // save new setting
                $objDatabase->Execute(' UPDATE '.DBPREFIX.'module_media_settings
                                                SET `value` = "' . contrexx_addslashes($newMediaSetting) . '"
                                                WHERE `name` = "media' . $i . '_frontend_changable"
                                            ');
            } else {
                $accessGroups = '';
                if (isset($_POST['media' . $i . '_access_associated_groups'])) {
                    $accessGroups = $_POST['media' . $i . '_access_associated_groups'];
                }
                // get groups
                \Permission::removeAccess($oldMediaSetting, 'dynamic');
                if (isset($_POST['media' . $i . '_access_associated_groups'])) {
                    $accessGroups = $_POST['media' . $i . '_access_associated_groups'];
                }

                // add AccessID
                $newMediaSetting = \Permission::createNewDynamicAccessId();

                // save AccessID
                if (count($accessGroups)) {
                    \Permission::setAccess($newMediaSetting, 'dynamic', $accessGroups);
                }
                $query = 'UPDATE '.DBPREFIX.'module_media_settings
                              SET `value` = "' . intval($newMediaSetting) . '"
                              WHERE `name` = "media' . $i . '_frontend_changable"';

                $objDatabase->Execute($query);
            }

            $oldManageSetting = $this->_arrSettings['media' . $i . '_frontend_managable'];
            $newManageSetting = '';
            if (isset($_POST['mediaSettings_Media' . $i . 'FrontendManagable'])) {
                $newManageSetting = $_POST['mediaSettings_Media' . $i . 'FrontendManagable'];
            }
            if (!is_numeric($newManageSetting))
            {
                if (is_numeric($oldManageSetting))
                {
                    // remove AccessId
                    \Permission::removeAccess($oldManageSetting, 'dynamic');
                }
                // save new setting
                $objDatabase->Execute(' UPDATE '.DBPREFIX.'module_media_settings
                                                SET `value` = "' . contrexx_addslashes($newManageSetting) . '"
                                                WHERE `name` = "media' . $i . '_frontend_managable"
                                            ');
            } else {
                $accessGroups = '';
                if (isset($_POST['media' . $i . '_manage_associated_groups'])) {
                    $accessGroups = $_POST['media' . $i . '_manage_associated_groups'];
                }
                // get groups
                \Permission::removeAccess($oldManageSetting, 'dynamic');
                if (isset($_POST['media' . $i . '_manage_associated_groups'])) {
                    $accessGroups = $_POST['media' . $i . '_manage_associated_groups'];
                }
                // add AccessID
                $newManageSetting = \Permission::createNewDynamicAccessId();
                // save AccessID
                if (count($accessGroups)) {
                    \Permission::setAccess($newManageSetting, 'dynamic', $accessGroups);
                }
                $objDatabase->Execute(' UPDATE '.DBPREFIX.'module_media_settings
                                                SET `value` = "' . intval($newManageSetting) . '"
                                                WHERE `name` = "media' . $i . '_frontend_managable"
                                            ');
            }
        }

        $this->_arrSettings  = $this->createSettingsArray();
        $this->_strOkMessage = $_ARRAYLANG['TXT_MEDIA_SETTINGS_SAVE_SUCCESSFULL'];
    }
}
