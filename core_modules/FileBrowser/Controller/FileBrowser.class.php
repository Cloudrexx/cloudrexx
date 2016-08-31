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
 * File browser
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_filebrowser
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core_Modules\FileBrowser\Controller;

/**
 * File browser
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_filebrowser
 */
class FileBrowser {

    public $_objTpl;
    public $_pageTitle;
    public $_okMessage = array();
    public $_errMessage = array();
    public $_arrFiles = array();
    public $_arrDirectories = array();
    public $_path = '';
    public $_iconWebPath = '';
    public $_frontendLanguageId = null;
    public $_mediaType = '';
    public $_mediaMode = '';
    public $_arrWebpages = array();
    public $_arrMediaTypes = array(
        'files'     => 'TXT_FILEBROWSER_FILES',
        'webpages'  => 'TXT_FILEBROWSER_WEBPAGES',
        'Media1'    => 'TXT_FILEBROWSER_MEDIA_1',
        'Media2'    => 'TXT_FILEBROWSER_MEDIA_2',
        'Media3'    => 'TXT_FILEBROWSER_MEDIA_3',
        'Media4'    => 'TXT_FILEBROWSER_MEDIA_4',
        'attach'    => 'TXT_FILE_UPLOADS',
        'Shop'      => 'TXT_FILEBROWSER_SHOP',
        'Gallery'   => 'TXT_THUMBNAIL_GALLERY',
        'Access'    => 'TXT_USER_ADMINISTRATION',
        'MediaDir'  => 'TXT_MEDIADIR_MODULE',
        'Downloads' => 'TXT_DOWNLOADS',
        'Calendar'  => 'TXT_CALENDAR',
        'Podcast'   => 'TXT_FILEBROWSER_PODCAST',
        'Blog'      => 'TXT_FILEBROWSER_BLOG',
        'Wysiwyg'   => 'TXT_FILEBROWSER_WYSIWYG',
    );
    private $mediaTypePaths = array(
        'files' => array(
            ASCMS_CONTENT_IMAGE_PATH, ASCMS_CONTENT_IMAGE_WEB_PATH,
        ),
        'Media1' => array(
            ASCMS_MEDIA1_PATH, ASCMS_MEDIA1_WEB_PATH,
        ),
        'Media2' => array(
            ASCMS_MEDIA2_PATH, ASCMS_MEDIA2_WEB_PATH,
        ),
        'Media3' => array(
            ASCMS_MEDIA3_PATH, ASCMS_MEDIA3_WEB_PATH,
        ),
        'Media4' => array(
            ASCMS_MEDIA4_PATH, ASCMS_MEDIA4_WEB_PATH,
        ),
        'attach' => array(
            ASCMS_ATTACH_PATH, ASCMS_ATTACH_WEB_PATH,
        ),
        'Shop' => array(
            ASCMS_SHOP_IMAGES_PATH, ASCMS_SHOP_IMAGES_WEB_PATH,
        ),
        'Gallery' => array(
            ASCMS_GALLERY_PATH, ASCMS_GALLERY_WEB_PATH,
        ),
        'Access' => array(
            ASCMS_ACCESS_PATH, ASCMS_ACCESS_WEB_PATH,
        ),
        'MediaDir' => array(
            ASCMS_MEDIADIR_IMAGES_PATH, ASCMS_MEDIADIR_IMAGES_WEB_PATH,
        ),
        'Downloads' => array(
            ASCMS_DOWNLOADS_IMAGES_PATH, ASCMS_DOWNLOADS_IMAGES_WEB_PATH,
        ),
        'Calendar' => array(
            ASCMS_CALENDAR_IMAGE_PATH, ASCMS_CALENDAR_IMAGE_WEB_PATH,
        ),
        'Podcast' => array(
            ASCMS_PODCAST_IMAGES_PATH, ASCMS_PODCAST_IMAGES_WEB_PATH,
        ),
        'Blog' => array(
            ASCMS_BLOG_IMAGES_PATH, ASCMS_BLOG_IMAGES_WEB_PATH,
        ),
        'Wysiwyg' => array(
            ASCMS_WYSIWYG_IMAGES_PATH, ASCMS_WYSIWYG_IMAGES_WEB_PATH,
        ),
    );
    public $highlightedFiles     = array(); // added files
    public $highlightColor    = '#D8FFCA'; // highlight added files [#d8ffca]

    /**
    * PHP5 constructor
    *
    * @global array
    */
    function __construct() {
        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH.'/FileBrowser/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->_iconPath = ASCMS_CORE_MODULE_WEB_PATH.'/FileBrowser/View/Media/';
        $this->_setFrontendLanguageId();
        $this->_mediaType = $this->_getMediaType();
        $this->_mediaMode = $this->_getMediaMode();
        $this->_path      = $this->_getPath();

        $this->checkMakeDir();
        $this->_initFiles();
    }

    /**
     * checks whether a module is available and active
     *
     * @return bool
     */
    function _checkForModule($strModuleName) {
        global $objDatabase;
        if (($objRS = $objDatabase->SelectLimit("SELECT `status` FROM ".DBPREFIX."modules WHERE name = '".$strModuleName."' AND `is_active` = '1' AND `is_licensed` = '1'", 1)) != false) {
            if ($objRS->RecordCount() > 0) {
                if ($objRS->fields['status'] == 'n') {
                    return false;
                }
                return true;
            }
        }
        return true;
    }

    function _getMediaType() {
        if (isset($_REQUEST['type']) && isset($this->_arrMediaTypes[$_REQUEST['type']])) {
            return $_REQUEST['type'];
        } else {
            return 'files';
        }
    }

    private function _getMediaMode()
    {
        return !empty($_GET['mode']) ? contrexx_input2raw($_GET['mode']) : '';
    }

    function _getPath() {

        if (!isset($_SESSION['fileBrowser'])) {
            $_SESSION['fileBrowser'] = array();
            $_SESSION['fileBrowser']['path'] = array();
        }

        $path =    $this->_mediaType != 'webpages'
                && array_key_exists($this->_mediaType, $this->mediaTypePaths)
                && isset($_SESSION['fileBrowser']['path'][$this->_mediaType])
                ?  $_SESSION['fileBrowser']['path'][$this->_mediaType]
                :  "";

        if (isset($_REQUEST['path']) && !stristr($_REQUEST['path'], '..')) {
            $path = $_REQUEST['path'];
        }
        $pos = strrpos($path, '/');
        if ($pos === false || $pos != (strlen($path)-1)) {
            $path .= "/";
        }
        // update path in session if type equals to files
        if (    $this->_mediaType != 'webpages'
             && array_key_exists($this->_mediaType, $this->mediaTypePaths)
           ) {
            $_SESSION['fileBrowser']['path'][$this->_mediaType] = $path;
        }

        return $path;
    }

    function _setFrontendLanguageId() {
        global $_FRONTEND_LANGID;

        if (!empty($_GET['langId']) || !empty($_POST['langId'])) {
            $this->_frontendLanguageId = intval(!empty($_GET['langId']) ? $_GET['langId'] : $_POST['langId']);
        } else {
            $this->_frontendLanguageId = $_FRONTEND_LANGID;
        }
    }

    function getPage() {
        $this->_showFileBrowser();
    }

    /**
     * Show the file browser
     * @access private
     * @global array
     */
    function _showFileBrowser() {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_fileBrowser_frame.html');

        switch($this->_mediaType) {
            case 'webpages':
                $strWebPath = 'Webpages (DB)';
                break;
            default:
                if (array_key_exists($this->_mediaType, $this->mediaTypePaths)) {
                    $strWebPath = $this->mediaTypePaths[$this->_mediaType][1].$this->_path;
                } else {
                    $strWebPath = ASCMS_CONTENT_IMAGE_WEB_PATH.$this->_path;
                }
        }

        $this->_objTpl->setVariable(array(
            'CONTREXX_CHARSET'      => CONTREXX_CHARSET,
            'FILEBROWSER_WEB_PATH'  => $strWebPath,
            'TXT_CLOSE'             => $_ARRAYLANG['TXT_CLOSE']
        ));

        $this->_setNavigation();
        $this->_setUploadForm();
        $this->_setContent();
        $this->_showStatus();
        $this->_objTpl->show();
    }

    /**
     * set the error/ok messages in the template
     * @return void
     */
    function _showStatus() {
        $okMessage  = implode('<br />', $this->_okMessage);
        $errMessage = implode('<br />', $this->_errMessage);

        if (!empty($errMessage)) {
           $this->_objTpl->setVariable('FILEBROWSER_ERROR_MESSAGE', $errMessage);
        } else {
           $this->_objTpl->hideBlock('errormsg');
        }

        if (!empty($okMessage)) {
            $this->_objTpl->setVariable('FILEBROWSER_OK_MESSAGE', $okMessage);
        } else {
           $this->_objTpl->hideBlock('okmsg');
        }
    }

    /**
     * put $message in the array specified by type
     * for later use of $this->_showStatus();
     * @param string $message
     * @param string $type ('ok' or 'error')
     * @return void
     * @see $this->_showStatus();
     */
    function _pushStatusMessage($message, $type = 'ok') {
       switch ($type) {
           case 'ok':
               array_push($this->_okMessage, $message);
               break;
           case 'error':
               array_push($this->_errMessage, $message);
               break;
           default:
               $this->_pushStatusMessage('invalid errortype, check FileBrowser.class.php', 'error');
               break;
       }
    }

    private function checkMakeDir() {
        if (isset($_POST['createDir']) && !empty($_POST['newDir'])) {
            $this->makeDir($_POST['newDir']);
        }
    }

    private function makeDir($dir) {
        global $_ARRAYLANG;

        if (array_key_exists($this->_mediaType, $this->mediaTypePaths)) {
            $strPath    = $this->mediaTypePaths[$this->_mediaType][0].$this->_path;
            $strWebPath = $this->mediaTypePaths[$this->_mediaType][1].$this->_path;
        } else {
            $strPath    = ASCMS_CONTENT_IMAGE_PATH.$this->_path;
            $strWebPath = ASCMS_CONTENT_IMAGE_WEB_PATH.$this->_path;
        }

        if (preg_match('#^[0-9a-zA-Z_\-]+$#', $dir)) {
            \Cx\Core\Csrf\Controller\Csrf::check_code();
            $objFile = new \File();
            if (!$objFile->mkDir($strPath, $strWebPath, $dir)) {
                $this->_pushStatusMessage(sprintf($_ARRAYLANG['TXT_FILEBROWSER_UNABLE_TO_CREATE_FOLDER'], $dir), 'error');
            } else {
                $this->_pushStatusMessage(sprintf($_ARRAYLANG['TXT_FILEBROWSER_DIRECTORY_SUCCESSFULLY_CREATED'], $dir));
            }
        } else if (!empty($dir)) {
            $this->_pushStatusMessage($_ARRAYLANG['TXT_FILEBROWSER_INVALID_CHARACTERS'], 'error');
        }
    }

    /**
     * Set the navigation with the media type drop-down menu in the file browser
     * @access private
     * @see FileBrowser::_getMediaTypeMenu, _objTpl, _mediaType, _arrDirectories
     */
    function _setNavigation()
    {
        global $_ARRAYLANG;

        $ckEditorFuncNum = isset($_GET['CKEditorFuncNum']) ? '&amp;CKEditorFuncNum='.contrexx_raw2xhtml($_GET['CKEditorFuncNum']) : '';
        $ckEditor = isset($_GET['CKEditor']) ? '&amp;CKEditor='.contrexx_raw2xhtml($_GET['CKEditor']) : '';

        $this->_objTpl->addBlockfile('FILEBROWSER_NAVIGATION', 'fileBrowser_navigation', 'module_fileBrowser_navigation.html');
        $this->_objTpl->setVariable(array(
            'FILEBROWSER_MEDIA_TYPE_MENU'   => $this->_getMediaTypeMenu('fileBrowserType', $this->_mediaType, 'onchange="window.location.replace(\''.  \Cx\Core\Csrf\Controller\Csrf::enhanceURI('index.php?cmd=FileBrowser').'&amp;standalone=true&amp;langId='.$this->_frontendLanguageId.'&amp;type=\'+this.value+\''.$ckEditorFuncNum.'\')"'),
            'TXT_FILEBROWSER_PREVIEW'       => $_ARRAYLANG['TXT_FILEBROWSER_PREVIEW']
        ));

        if ($this->_mediaType != 'webpages') {
            // only show directories if the files should be displayed
            if (count($this->_arrDirectories) > 0) {
                foreach ($this->_arrDirectories as $arrDirectory) {
                    $this->_objTpl->setVariable(array(
                        'FILEBROWSER_FILE_PATH' => "index.php?cmd=FileBrowser&amp;standalone=true&amp;langId={$this->_frontendLanguageId}&amp;type={$this->_mediaType}&amp;path={$arrDirectory['path']}" . $ckEditor . $ckEditorFuncNum,
                        'FILEBROWSER_FILE_NAME' => $arrDirectory['name'],
                        'FILEBROWSER_FILE_ICON' => $arrDirectory['icon']
                    ));
                    $this->_objTpl->parse('navigation_directories');
                }
            }
        }
        $this->_objTpl->parse('fileBrowser_navigation');
    }


    /**
     * Shows all files / pages in filebrowser
     */
    function _setContent()
    {
        global $_FRONTEND_LANGID;

        $this->_objTpl->addBlockfile('FILEBROWSER_CONTENT', 'fileBrowser_content', 'module_fileBrowser_content.html');

        $ckEditorFuncNum = isset($_GET['CKEditorFuncNum']) ? '&amp;CKEditorFuncNum='.contrexx_raw2xhtml($_GET['CKEditorFuncNum']) : '';
        $ckEditor = isset($_GET['CKEditor']) ? '&amp;CKEditor='.contrexx_raw2xhtml($_GET['CKEditor']) : '';
        $rowNr = 0;

        switch ($this->_mediaType) {
            case 'webpages':
                $jd = new \Cx\Core\Json\JsonData();
                $data = $jd->data('node', 'getTree', array('get' => array('recursive' => 'true')));
                $pageStack = array();
                $ref = 0;
                $data['data']['tree'] = array_reverse($data['data']['tree']);
                foreach ($data['data']['tree'] as &$entry) {
                    $entry['attr']['level'] = 0;
                    array_push($pageStack, $entry);
                }
                while (count($pageStack)) {
                    $entry = array_pop($pageStack);
                    $page = $entry['data'][0];
                    $arrPage['level'] = $entry['attr']['level'];
                    $arrPage['node_id'] = $entry['attr']['rel_id'];
                    $children = $entry['children'];
                    $children = array_reverse($children);
                    foreach ($children as &$entry) {
                        $entry['attr']['level'] = $arrPage['level'] + 1;
                        array_push($pageStack, $entry);
                    }
                    $arrPage['catname'] = $page['title'];
                    $arrPage['catid'] = $page['attr']['id'];
                    $arrPage['lang'] = BACKEND_LANG_ID;
                    $arrPage['protected'] = $page['attr']['protected'];
                    $arrPage['type'] = \Cx\Core\ContentManager\Model\Entity\Page::TYPE_CONTENT;
                    $arrPage['alias'] = $page['title'];
                    $arrPage['frontend_access_id'] = $page['attr']['frontend_access_id'];
                    $arrPage['backend_access_id'] = $page['attr']['backend_access_id'];

                    // JsonNode does not provide those
                    //$arrPage['level'] = ;
                    //$arrPage['type'] = ;
                    //$arrPage['parcat'] = ;
                    //$arrPage['displaystatus'] = ;
                    //$arrPage['moduleid'] = ;
                    //$arrPage['startdate'] = ;
                    //$arrPage['enddate'] = ;

                    // But we can simulate level and type for our purposes: (level above)
                    $jsondata = json_decode($page['attr']['data-href']);
                    $path     = $jsondata->path;
                    if (trim($jsondata->module) != '') {
                        $arrPage['type'] = \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION;
                        $module = explode(' ', $jsondata->module, 2);
                        $arrPage['modulename'] = $module[0];
                        if (count($module) > 1) {
                            $arrPage['cmd'] = $module[1];
                        }
                    }

                    $url = "'" . '[[' . \Cx\Core\ContentManager\Model\Entity\Page::PLACEHOLDER_PREFIX;

// TODO: This only works for regular application pages. Pages of type fallback that are linked to an application
//       will be parsed using their node-id ({NODE_<ID>})
                    if (($arrPage['type'] == \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION) && ($this->_mediaMode !== 'alias')) {
                        $url .= $arrPage['modulename'];
                        if (!empty($arrPage['cmd'])) {
                            $url .= '_' . $arrPage['cmd'];
                        }

                        $url = strtoupper($url);
                    } else {
                        $url .= $arrPage['node_id'];
                    }

                    // if language != current language or $alwaysReturnLanguage
                    if ($this->_frontendLanguageId != $_FRONTEND_LANGID ||
                            (isset($_GET['alwaysReturnLanguage']) &&
                            $_GET['alwaysReturnLanguage'] == 'true')) {
                        $url .= '_' . $this->_frontendLanguageId;
                    }
                    $url .= "]]'";

                    $this->_objTpl->setVariable(array(
                        'FILEBROWSER_ROW_CLASS'         => $rowNr%2 == 0 ? "row1" : "row2",
                        'FILEBROWSER_FILE_PATH_CLICK'   => "javascript:{setUrl($url,null,null,'".\FWLanguage::getLanguageCodeById($this->_frontendLanguageId).$path."','page')}",
                        'FILEBROWSER_FILE_NAME'         => $arrPage['catname'],
                        'FILEBROWSER_FILESIZE'          => '&nbsp;',
                        'FILEBROWSER_FILE_ICON'         => $this->_iconPath.'htm.png',
                        'FILEBROWSER_FILE_DIMENSION'    => '&nbsp;',
                        'FILEBROWSER_SPACING_STYLE'     => 'style="margin-left: '.($arrPage['level'] * 15).'px;"',
                    ));
                    $this->_objTpl->parse('content_files');

                    $rowNr++;
                }
                break;
            case 'Media1':
            case 'Media2':
            case 'Media3':
            case 'Media4':
                \Permission::checkAccess(7, 'static');       //Access Media-Archive
                \Permission::checkAccess(38, 'static');  //Edit Media-Files
                \Permission::checkAccess(39, 'static');  //Upload Media-Files

            //Hier soll wirklich kein break stehen! Beabsichtig!


            default:
                if (count($this->_arrDirectories) > 0) {
                    foreach ($this->_arrDirectories as $arrDirectory) {
                        $this->_objTpl->setVariable(array(
                            'FILEBROWSER_ROW_CLASS'         => $rowNr%2 == 0 ? "row1" : "row2",
                            'FILEBROWSER_FILE_PATH_CLICK'   => "index.php?cmd=FileBrowser&amp;standalone=true&amp;langId={$this->_frontendLanguageId}&amp;type={$this->_mediaType}&amp;path={$arrDirectory['path']}". $ckEditor . $ckEditorFuncNum,
                            'FILEBROWSER_FILE_NAME'         => $arrDirectory['name'],
                            'FILEBROWSER_FILESIZE'          => '&nbsp;',
                            'FILEBROWSER_FILE_ICON'         => $arrDirectory['icon'],
                            'FILEBROWSER_FILE_DIMENSION'    => '&nbsp;',
                        ));
                        $this->_objTpl->parse('content_files');
                        $rowNr++;
                    }
                }

                if (count($this->_arrFiles) > 0) {
                    $arrEscapedPaths = array();
                    foreach ($this->_arrFiles as $arrFile) {
                        $arrEscapedPaths[] = contrexx_raw2encodedUrl($arrFile['path']);
                            $this->_objTpl->setVariable(array(
                                'FILEBROWSER_ROW_CLASS'             => $rowNr%2 == 0 ? "row1" : "row2",
                                'FILEBROWSER_ROW_STYLE'                => in_array($arrFile['name'], $this->highlightedFiles) ? ' style="background: '.$this->highlightColor.';"' : '',
                                'FILEBROWSER_FILE_PATH_DBLCLICK'    => "setUrl('".contrexx_raw2xhtml($arrFile['path'])."',".$arrFile['width'].",".$arrFile['height'].",'')",
                                'FILEBROWSER_FILE_PATH_CLICK'       => "javascript:{showPreview(".(count($arrEscapedPaths)-1).",".$arrFile['width'].",".$arrFile['height'].")}",
                                'FILEBROWSER_FILE_NAME'             => contrexx_stripslashes($arrFile['name']),
                                'FILEBROWSER_FILESIZE'              => $arrFile['size'].' KB',
                                'FILEBROWSER_FILE_ICON'             => $arrFile['icon'],
                                'FILEBROWSER_FILE_DIMENSION'        => (empty($arrFile['width']) && empty($arrFile['height'])) ? '' : intval($arrFile['width']).'x'.intval($arrFile['height'])
                            ));
                            $this->_objTpl->parse('content_files');
                            $rowNr++;
                    }

                    $this->_objTpl->setVariable('FILEBROWSER_FILES_JS', "'".implode("','",$arrEscapedPaths)."'");
                }
                if (array_key_exists($this->_mediaType, $this->mediaTypePaths)) {
                    $this->_objTpl->setVariable('FILEBROWSER_IMAGE_PATH', $this->mediaTypePaths[$this->_mediaType][1]);
                } else {
                    $this->_objTpl->setVariable('FILEBROWSER_IMAGE_PATH', ASCMS_CONTENT_IMAGE_WEB_PATH);
                }
            break;
        }
        $this->_objTpl->parse('fileBrowser_content');
    }


    /**
     * Shows the upload-form in the filebrowser
     */
    function _setUploadForm()
    {
        global $_ARRAYLANG, $_CONFIG;


        //data we want to remember for handling the uploaded files
        $data = array();
        if (array_key_exists($this->_mediaType, $this->mediaTypePaths)) {
            $data['path']    = $this->mediaTypePaths[$this->_mediaType][0].$this->_path;
            $data['webPath'] = $this->mediaTypePaths[$this->_mediaType][1].$this->_path;
        } else {
            $data['path']    = ASCMS_CONTENT_IMAGE_PATH.$this->_path;
            $data['webPath'] = ASCMS_CONTENT_IMAGE_WEB_PATH.$this->_path;
        }

        $comboUp = \Cx\Core_Modules\Upload\Controller\UploadFactory::getInstance()->newUploader('exposedCombo');
        $comboUp->setFinishedCallback(array(ASCMS_CORE_MODULE_PATH.'/FileBrowser/Controller/FileBrowser.class.php','\Cx\Core_Modules\FileBrowser\Controller\FileBrowser','uploadFinished'));
        $comboUp->setData($data);
        //set instance name to combo_uploader so we are able to catch the instance with js
        $comboUp->setJsInstanceName('exposed_combo_uploader');

        $this->_objTpl->setVariable(array(
              'COMBO_UPLOADER_CODE' => $comboUp->getXHtml(true),
        ));
        //end of uploader button handling
        //check if a finished upload caused reloading of the page.
        //if yes, we know the added files and want to highlight them
        if (!empty($_GET['highlightUploadId'])) {
            $key = 'filebrowser_upload_files_'.intval($_GET['highlightUploadId']);
            if (isset($_SESSION[$key])) {
                $sessionHighlightCandidates = $_SESSION[$key]; //an array with the filenames, set in FileBrowser::uploadFinished
            }
            //clean up session; we do only highlight once
            unset($_SESSION[$key]);

            if(is_array($sessionHighlightCandidates)) //make sure we don't cause any unexpected behaviour if we lost the session data
                $this->highlightedFiles = $sessionHighlightCandidates;
        }

        $objFWSystem = new \FWSystem();

        // cannot upload or mkdir in webpages view
        if ($this->_mediaType == "webpages") {
            return;
        }
        $this->_objTpl->addBlockfile('FILEBROWSER_UPLOAD', 'fileBrowser_upload', 'module_fileBrowser_upload.html');
        $this->_objTpl->setVariable(array(
            'FILEBROWSER_UPLOAD_TYPE'   => $this->_mediaType,
            'FILEBROWSER_UPLOAD_PATH'   => $this->_path,
            'FILEBROWSER_MAX_FILE_SIZE' => $objFWSystem->getMaxUploadFileSize(),
            'TXT_CREATE_DIRECTORY'      => $_ARRAYLANG['TXT_FILEBROWSER_CREATE_DIRECTORY'],
            'TXT_UPLOAD_FILE'           => $_ARRAYLANG['TXT_FILEBROWSER_UPLOAD_FILE'],
                        'JAVASCRIPT'                => \JS::getCode(),
        ));

        $this->_objTpl->parse('fileBrowser_upload');
    }


    /**
     * this is called as soon as uploads have finished.
     * takes care of moving them to the right folder
     *
     * @return string the directory to move to
     */
    public static function uploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos) {
        $path = $data['path'];
        $webPath = $data['webPath'];

        //we remember the names of the uploaded files here. they are stored in the session afterwards,
        //so we can later display them highlighted.
        $arrFiles = array();

        //rename files, delete unwanted
        $arrFilesToRename = array(); //used to remember the files we need to rename
        $h = opendir($tempPath);
        while(false !== ($file = readdir($h))) {
            $info = pathinfo($file);

            //skip . and ..
            if($file == '.' || $file == '..') { continue; }

            $file = \Cx\Lib\FileSystem\FileSystem::replaceCharacters($file);

            //delete potentially malicious files
            if(!\FWValidator::is_file_ending_harmless($file)) {
                @unlink($tempPath.'/'.$file);
                continue;
            }

            //check if file needs to be renamed
            $newName = '';
            $suffix = '';
            if (file_exists($path.$file)) {
                $suffix = '_'.time();
                if (empty($_REQUEST['uploadForceOverwrite']) || !intval($_REQUEST['uploadForceOverwrite'] > 0)) {
                    $newName = $info['filename'].$suffix.'.'.$info['extension'];
                    $arrFilesToRename[$file] = $newName;
                    array_push($arrFiles, $newName);
                }
            } else {
                array_push($arrFiles, $file);
            }
        }

        //rename files where needed
        foreach($arrFilesToRename as $oldName => $newName){
            rename($tempPath.'/'.$oldName, $tempPath.'/'.$newName);
        }

        //create thumbnails
//        foreach($arrFiles as $file) {
//            $fileType = pathinfo($file);
//            if ($fileType['extension'] == 'jpg' || $fileType['extension'] == 'jpeg' || $fileType['extension'] == 'png' || $fileType['extension'] == 'gif') {
//                $objFile = new File();
//                $_objImage = new ImageManager();
//                $_objImage->_createThumbWhq($tempPath.'/', $tempWebPath.'/', $file, 1e10, 80, 90);
//
//                if ($objFile->setChmod($tempPath, $tempWebPath, ImageManager::getThumbnailFilename($file)))
//                    $this->_pushStatusMessage(sprintf($_ARRAYLANG['TXT_FILEBROWSER_THUMBNAIL_SUCCESSFULLY_CREATED'], $strWebPath.$file));
//            }
//        }

        //remember the uploaded files
        if(isset($_SESSION["filebrowser_upload_files_$uploadId"])) //do not overwrite already uploaded files
            $arrFiles = array_merge($_SESSION["filebrowser_upload_files_$uploadId"], $arrFiles);
        $_SESSION["filebrowser_upload_files_$uploadId"] = $arrFiles;

        /* unwanted files have been deleted, unallowed filenames corrected.
           we can now simply return the desired target path, as only valid
           files are present in $tempPath */
        return array($path, $webPath);
    }


    /**
     * Read all files / directories of the current folder
     */
    function _initFiles()
    {
        if (array_key_exists($this->_mediaType, $this->mediaTypePaths)) {
            $strPath = $this->mediaTypePaths[$this->_mediaType][0].$this->_path;
        } else {
            $strPath = ASCMS_CONTENT_IMAGE_PATH.$this->_path;
        }

        $objDir = @opendir($strPath);

        $arrFiles = array();

        if ($objDir) {
            $path = array();
            if (   $this->_path !== "/"
                && preg_match('#(.*/).+[/]?$#', $this->_path, $path)) {
                array_push($this->_arrDirectories, array('name' => '..', 'path' => $path[1], 'icon' => $this->_iconPath.'Folder.png'));
            }

            $file = readdir($objDir);
            while ($file !== false) {
// TODO: This match won't work for arbitrary thumbnail file names as they
// may be created by the Image class!
                if ($file == '.' || $file == '..' || preg_match('/(?:\.(?:thumb_thumbnail|thumb_medium|thumb_large)\.[^.]+$)|(?:\.thumb)$/i', $file)  || $file == 'index.php') {
                    $file = readdir($objDir);
                    continue;
                }
                array_push($arrFiles, $file);
                $file = readdir($objDir);
            }
            closedir($objDir);

            sort($arrFiles, SORT_NATURAL | SORT_FLAG_CASE);

            foreach ($arrFiles as $file) {
                if (is_dir($strPath.$file)) {
                    array_push($this->_arrDirectories, array('name' => $file, 'path' => $this->_path.$file, 'icon' => $this->_getIcon($strPath.$file)));
                } else {
                    $filesize = @filesize($strPath.$file);
                    if ($filesize > 0) {
                        $filesize = round($filesize/1024);
                    } else {
                        $filesize = 0;
                    }
                    $arrDimensions = array(0 => 0, 1 => 0);
                    if (\Cx\Core_Modules\Media\Controller\MediaLibrary::_isImage($strPath.$file)) {
                        $arrDimensions = @getimagesize($strPath.$file);
                    }
                    array_push($this->_arrFiles, array('name' => $file, 'path' => $this->_path.$file, 'size' => $filesize, 'icon' => $this->_getIcon($strPath.$file), 'width' => intval($arrDimensions[0]), 'height' => intval($arrDimensions[1])));
                }
            }
        }
    }


    /**
     * Search the icon for a file
     * @param  string $file: The icon of this file will be searched
     */
    function _getIcon($file)
    {
        $icon = '';
        if (is_file($file)) {
            $info = pathinfo($file);
            $icon = strtoupper($info['extension']);
        }

        $arrImageExt        = array('JPEG', 'JPG', 'TIFF', 'GIF', 'BMP', 'PNG');
        $arrVideoExt        = array('3GP', 'AVI', 'DAT', 'FLV', 'FLA', 'M4V', 'MOV', 'MPEG', 'MPG', 'OGG', 'WMV', 'SWF');
        $arrAudioExt        = array('WAV', 'WMA', 'AMR', 'MP3', 'AAC');
        $arrPresentationExt = array('ODP', 'PPT', 'PPTX');
        $arrSpreadsheetExt  = array('CSV', 'ODS', 'XLS', 'XLSX');
        $arrDocumentsExt    = array('DOC', 'DOCX', 'ODT', 'RTF');

        switch (true) {
            case ($icon == 'TXT'):
                $icon = 'Text';
                break;
            case ($icon == 'PDF'):
                $icon = 'Pdf';
                break;
            case in_array($icon, $arrImageExt):
                $icon = 'Image';
                break;
            case in_array($icon, $arrVideoExt):
                $icon = 'Video';
                break;
            case in_array($icon, $arrAudioExt):
                $icon = 'Audio';
                break;
            case in_array($icon, $arrPresentationExt):
                $icon = 'Presentation';
                break;
            case in_array($icon, $arrSpreadsheetExt):
                $icon = 'Spreadsheet';
                break;
            case in_array($icon, $arrDocumentsExt):
                $icon = 'TextDocument';
                break;
            default :
                $icon = 'Unknown';
                break;
        }
        if (is_dir($file)) {
            $icon = 'Folder';
        }
        if (!file_exists(ASCMS_CORE_MODULE_PATH.'/FileBrowser/View/Media/'.$icon.'.png') or !isset($icon)) {
            $icon = '_blank';
        }
        return $this->_iconPath.$icon.'.png';
    }


    /**
     * Create html-source of a complete <select>-navigation
     * @param string $name: name of the <select>-tag
     * @param string $selectedType: which <option> will be "selected"?
     * @param string $attrs: further attributes of the <select>-tag
     * @return string html-source
     */
    function _getMediaTypeMenu($name, $selectedType, $attrs)
    {
        global $_ARRAYLANG, $_CORELANG;

        $menu = "<select name=\"".$name."\" ".$attrs.">";
        foreach ($this->_arrMediaTypes as $type => $text) {
            if (!$this->_checkForModule($type)) {
                continue;
            }
            $text = $_ARRAYLANG[$text];
            if (empty($text)) {
                $text = $_CORELANG[$text];
            }
            $menu .= "<option value=\"".$type."\"".($selectedType == $type ? " selected=\"selected\"" : "").">".$text."</option>\n";
        }
        $menu .= "</select>";
        return $menu;
    }

}

?>
