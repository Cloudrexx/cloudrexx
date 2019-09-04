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
 * @version       1.0
 * @package     cloudrexx
 * @subpackage  coremodule_media
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Core_Modules\Media\Controller;

/**
 * Media Manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version       1.0
 * @access        public
 * @package     cloudrexx
 * @subpackage  coremodule_media
 */
class Media extends MediaLibrary
{
    public $_objTpl;                       // var for the template object
    public $pageTitle;                     // var for the title of the active page
    public $statusMessage;                 // var for the status message

    public $arrPaths;                      // array paths
    public $arrWebPaths;                   // array web paths

    public $getCmd;                        // $_GET['cmd']
    public $getAct;                        // $_GET['act']
    public $getPath;                       // $_GET['path']
    public $getFile;                       // $_GET['file']

    public $path;                          // current path
    public $webPath;                       // current web path
    public $docRoot;                       // document root
    public $archive;

    var $highlightName     = array();   // highlight added name
    var $highlightColor    = '#d8ffca'; // highlight color for added name [#d8ffca]

    /**
     * PHP5 constructor
     * @param  string  $template
     * @param  array   $_ARRAYLANG
     * @access public
     */
    function __construct($pageContent, $archive)
    {
        $this->_arrSettings =$this->createSettingsArray();

        $this->archive = (intval(substr($archive,-1,1)) == 0) ? 'Media1' : $archive;

        $this->arrPaths = array(ASCMS_MEDIA1_PATH . '/',
                                    ASCMS_MEDIA2_PATH . '/',
                                    ASCMS_MEDIA3_PATH . '/',
                                    ASCMS_MEDIA4_PATH . '/');

        $this->arrWebPaths = array('Media1' => ASCMS_MEDIA1_WEB_PATH . '/',
                                    'Media2' => ASCMS_MEDIA2_WEB_PATH . '/',
                                    'Media3' => ASCMS_MEDIA3_WEB_PATH . '/',
                                    'Media4' => ASCMS_MEDIA4_WEB_PATH . '/');
        $this->docRoot = \Env::get('cx')->getWebsitePath();

        // sigma template
        $this->pageContent = $pageContent;
        $this->_objTpl     = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_objTpl->setTemplate($this->pageContent, true, true);

        // get variables
        $this->getAct  = (isset($_GET['act']) and !empty($_GET['act']))   ? trim($_GET['act'])  : '';
        $this->getFile = (isset($_GET['file']) and !empty($_GET['file'])) ? \Cx\Lib\FileSystem\FileSystem::sanitizeFile(trim($_GET['file'])) : '';
        if ($this->getFile === false) $this->getFile = '';
        $this->sortBy = !empty($_GET['sort']) ? trim($_GET['sort']) : 'name';
        $this->sortDesc = !empty($_GET['sort_desc']);
    }


    /**
    * checks and cleans the web path
    *
    * @param  string default web path
    * @return string  cleaned web path
    */
    function getWebPath($defaultWebPath)
    {
        $webPath = $defaultWebPath;
        if (isset($_GET['path']) AND !empty($_GET['path']) AND !stristr($_GET['path'],'..')) {
            $webPath = rawurldecode(trim($_GET['path']));
        }
        if (substr($webPath, 0, strlen($defaultWebPath)) != $defaultWebPath || !file_exists($this->docRoot.$webPath)) {
            $webPath = $defaultWebPath;
        }
        return $webPath;
    }


    /**
     * Gets the requested page
     * @global     array     $_ARRAYLANG,$_CONFIG
     * @return    string    parsed content
     */
    function getMediaPage()
    {
        global $_ARRAYLANG, $template;
        $this->webPath = $this->getWebPath($this->arrWebPaths[$this->archive]);
        $this->path = \Env::get('cx')->getWebsitePath().$this->webPath;
        $this->getCmd = !empty($_GET['cmd']) ? '&amp;cmd='.htmlentities($_GET['cmd'], ENT_QUOTES, CONTREXX_CHARSET) : '';

        $this->_overviewMedia();
        \Message::show($this->_objTpl);

        return $this->_objTpl->get();
    }


    /**
    * Overview Media Data
    *
    * @global     array     $_ARRAYLANG
    * @return    string    parsed content
    */
    function _overviewMedia()
    {
        global $_ARRAYLANG, $_CORELANG;

        $searchTerm = $this->isSearchActivated() && !empty($_GET['term'])
                        ? \FWValidator::getCleanFileName(contrexx_input2raw($_GET['term']))
                        : '';

        switch ($this->getAct) {
            case 'download':
                $this->_downloadMedia();
                break;
            case 'newDir':
                $this->_createDirectory($_POST['media_directory_name']);
                break;
            case 'upload':
                $this->_uploadFiles();
                break;
            case 'rename':
                $this->_renameFiles();
                break;
            case 'delete':
                $this->_deleteFiles();
                break;
            default:
        }

        // tree navigation
        $tmp = $this->arrWebPaths[$this->archive];
        if (substr($this->webPath, 0, strlen($tmp)) == $tmp) {
            $this->_objTpl->setVariable(array(  // navigation #1
                'MEDIA_TREE_NAV_MAIN'      => "Home /", //$this->arrWebPaths[$x],
                'MEDIA_TREE_NAV_MAIN_HREF' => CONTREXX_SCRIPT_PATH.'?section='.$this->archive.$this->getCmd.'&amp;path=' . rawurlencode($this->arrWebPaths[$this->archive])
            ));

            if (strlen($this->webPath) != strlen($tmp)) {
                $tmpPath = substr($this->webPath, -(strlen($this->webPath) - strlen($tmp)));
                $tmpPath = explode('/', $tmpPath);
                $tmpLink = '';
                foreach ($tmpPath as $path) {
                    if (!empty($path)) {
                        $tmpLink .= $path.'/';
                        $this->_objTpl->setVariable(array(  // navigation #2
                            'MEDIA_TREE_NAV_DIR'      => $path,
                            'MEDIA_TREE_NAV_DIR_HREF' => CONTREXX_SCRIPT_PATH.'?section=' . $this->archive . $this->getCmd . '&amp;path=' . rawurlencode($this->arrWebPaths[$this->archive] . $tmpLink)
                        ));
                        $this->_objTpl->parse('mediaTreeNavigation');
                    }
                }
            }
        }

        if (!empty($_GET['highlightFiles'])) {
            $this->highlightName = array_merge($this->highlightName, array_map('basename', json_decode(contrexx_stripslashes(urldecode($_GET['highlightFiles'])))));
        }

        // media directory tree
        $dirTree = array();
        $this->getDirectoryTree($this->path, $searchTerm, $dirTree, !empty($searchTerm));
        $dirTree = $this->_sortDirTree($dirTree);

        $deleteUrl  = clone \Cx\Core\Core\Controller\Cx::instanciate()->getRequest()->getUrl();
        $deleteUrl->setParam('act', null);

        $previewUrl = clone $deleteUrl;
        $renameUrl  = clone $deleteUrl;

        $redirect = urlencode(base64_encode($deleteUrl->toString(false)));
        $renameUrl->setParam('redirect', $redirect);
        $deleteUrl->setParam('redirect', $redirect);
        $renameUrl->setParam('act', 'rename');
        $deleteUrl->setParam('act', 'delete');

        // we'll parse image specific functionality only,
        // if related placeholder used for its output is present
        // in the application template
        if ($this->_objTpl->blockExists('mediaDirectoryTreeFile')) {
            // check in file specific template block
            $parseImagePreview = $this->_objTpl->placeholderExists('MEDIA_FILE_NAME_HREF', 'mediaDirectoryTreeFile');
        } else {
            // check in generic template block
            $parseImagePreview = $this->_objTpl->placeholderExists('MEDIA_FILE_NAME_HREF', 'mediaDirectoryTree');
        }

        $i = 0;
        foreach (array_keys($dirTree) as $key) {
            if (!is_array($dirTree[$key]['icon'])) {
                continue;
            }
            $mediaCount = count($dirTree[$key]['icon']);
            for ($x = 0; $x < $mediaCount; $x++) {
                $fileName = $dirTree[$key]['name'][$x];
                if (MediaLibrary::isIllegalFileName($fileName)) {
                    continue;
                }
                $class = ($i % 2) ? 'row2' : 'row1';
                 // highlight
                if (in_array($fileName, $this->highlightName)) {
                    $class .= '" style="background-color: ' . $this->highlightColor . ';';
                }

                if (!$this->manageAccessGranted()) {
                    //if the user is not allowed to delete or rename files -- hide those blocks
                    if ($this->_objTpl->blockExists('manage_access_option')) {
                        $this->_objTpl->hideBlock('manage_access_option');
                    }
                }

                $this->_objTpl->setVariable(array(  // file
                    'MEDIA_DIR_TREE_ROW'  => $class,
                    'MEDIA_FILE_ICON'     => $dirTree[$key]['icon'][$x],
                    'MEDIA_FILE_NAME'     => $this->prettyFormatFilename($fileName),
                    'MEDIA_FILE_SIZE'     => $this->_formatSize($dirTree[$key]['size'][$x]),
                    'MEDIA_FILE_TYPE'     => $this->_formatType($dirTree[$key]['type'][$x]),
                    'MEDIA_FILE_DATE'     => $this->_formatDate($dirTree[$key]['date'][$x]),
                    'MEDIA_RENAME_TITLE'  => $_ARRAYLANG['TXT_MEDIA_RENAME'],
                    'MEDIA_DELETE_TITLE'  => $_ARRAYLANG['TXT_MEDIA_DELETE'],
                ));

                $image        = false;
                $imagePreview = '';
                $mediaPath    = $this->path;
                $mediaWebPath = $this->webPath;
                if (!empty($searchTerm)) {
                    $mediaPath    = $dirTree[$key]['path'][$x] .'/';
                    $mediaWebPath = $mediaPath;
                    \Cx\Lib\FileSystem\FileSystem::path_relative_to_root($mediaWebPath);
                    $mediaWebPath = '/'. $mediaWebPath; // Filesysystem removes the beginning slash(/)
                }

                $file = $fileName;
                switch ($key) {
                    case 'dir':
                        // build directory traversal url
                        $path = $mediaWebPath . $fileName . '/';
                        $previewUrl->setParam('act', null);
                        $previewUrl->setParam('file', null);
                        $previewUrl->setParam('path', $path);

                        // show directory specific template block
                        if ($this->_objTpl->blockExists('mediaDirectoryTreeDir')) {
                            $this->_objTpl->touchBlock('mediaDirectoryTreeDir');
                        }

                        // hide file specific template block
                        if ($this->_objTpl->blockExists('mediaDirectoryTreeFile')) {
                            $this->_objTpl->hideBlock('mediaDirectoryTreeFile');
                        }
                        break;

                    case 'file':
                    default:
                        // build file download url
                        $path = $mediaWebPath;
                        $previewUrl->setParam('act', 'download');
                        $previewUrl->setParam('path', $path);
                        $previewUrl->setParam('file', $file);

                        // build image preview url
                        $filePath = $mediaPath . $fileName;
                        if ($parseImagePreview && $this->_isImage($filePath)) {
                            $image        = true;
                            $tmpSize      = getimagesize($filePath);
                            $imagePreview = 'javascript: preview(\'' . $mediaWebPath . $fileName . '\', ' . $tmpSize[0] . ', ' . $tmpSize[1] . ');';
                        }

                        // hide directory specific template block
                        if ($this->_objTpl->blockExists('mediaDirectoryTreeDir')) {
                            $this->_objTpl->hideBlock('mediaDirectoryTreeDir');
                        }

                        // show file specific template block
                        if ($this->_objTpl->blockExists('mediaDirectoryTreeFile')) {
                            $this->_objTpl->touchBlock('mediaDirectoryTreeFile');
                        }
                    break;
                }

                $deleteUrl->setParam('path', $path);
                $deleteUrl->setParam('file', $key == 'dir' ? null : $file);

                $renameUrl->setParam('path', $mediaWebPath);
                $renameUrl->setParam('file', $file);

                $this->_objTpl->setVariable(array(
                    'MEDIA_FILE_NAME_HREF'   => $image ? $imagePreview : $previewUrl->toString(false),
                    'MEDIA_FILE_NAME_SRC'    => $previewUrl->toString(false),
                    'MEDIA_FILE_RENAME_HREF' => $renameUrl->toString(false),
                    'MEDIA_FILE_DELETE_HREF' => $deleteUrl->toString(false),
                ));
                $this->_objTpl->parse('mediaDirectoryTree');
                $i++;
            }
        }

        // empty dir or php safe mode restriction
        if ($i == 0 && !@opendir($this->path)) {
            $tmpMessage = (!@opendir($this->path)) ? 'PHP Safe Mode Restriction or wrong path' : $_ARRAYLANG['TXT_MEDIA_DIR_EMPTY'];

            $this->_objTpl->setVariable(array(
                'TXT_MEDIA_DIR_EMPTY' => $tmpMessage,
                'MEDIA_SELECT_STATUS' => ' disabled'
            ));
            $this->_objTpl->parse('mediaEmptyDirectory');
        }

        // parse variables
        $tmpHref = CONTREXX_SCRIPT_PATH.'?section=' . $this->archive . $this->getCmd . (!empty($searchTerm) ? '&amp;term='. contrexx_raw2xhtml($searchTerm) : '') . '&amp;path=' . rawurlencode($this->webPath);
        $tmpIcon = $this->_sortingIcons();

        if ($this->_objTpl->blockExists('manage_access_header')) {
            if ($this->manageAccessGranted()) {
                $this->_objTpl->touchBlock('manage_access_header');
            } else {
                $this->_objTpl->hideBlock('manage_access_header');
            }
        }
        $this->_objTpl->setVariable(array(  // parse dir content
            'MEDIA_NAME_HREF'     => $tmpHref.'&amp;sort=name&amp;sort_desc='.($this->sortBy == 'name' && !$this->sortDesc),
            'MEDIA_SIZE_HREF'     => $tmpHref.'&amp;sort=size&amp;sort_desc='.($this->sortBy == 'size' && !$this->sortDesc),
            'MEDIA_TYPE_HREF'     => $tmpHref.'&amp;sort=type&amp;sort_desc='.($this->sortBy == 'type' && !$this->sortDesc),
            'MEDIA_DATE_HREF'     => $tmpHref.'&amp;sort=date&amp;sort_desc='.($this->sortBy == 'date' && !$this->sortDesc),
            'MEDIA_PERM_HREF'     => $tmpHref.'&amp;sort=perm&amp;sort_desc='.($this->sortBy == 'perm' && !$this->sortDesc),
            'TXT_MEDIA_FILE_NAME' => $_ARRAYLANG['TXT_MEDIA_FILE_NAME'],
            'TXT_MEDIA_FILE_SIZE' => $_ARRAYLANG['TXT_MEDIA_FILE_SIZE'],
            'TXT_MEDIA_FILE_TYPE' => $_ARRAYLANG['TXT_MEDIA_FILE_TYPE'],
            'TXT_MEDIA_FILE_DATE' => $_ARRAYLANG['TXT_MEDIA_FILE_DATE'],
            'TXT_MEDIA_FILE_PERM' => $_ARRAYLANG['TXT_MEDIA_FILE_PERM'],
            'MEDIA_NAME_ICON'     => $tmpIcon['name'],
            'MEDIA_SIZE_ICON'     => $tmpIcon['size'],
            'MEDIA_TYPE_ICON'     => $tmpIcon['type'],
            'MEDIA_DATE_ICON'     => $tmpIcon['date'],
            'MEDIA_PERM_ICON'     => $tmpIcon['perm'],
            'MEDIA_ARCHIVE_NAME'    => $this->archive,
            'MEDIA_ARCHIVE_PATH'    => rawurlencode($this->webPath),
            'MEDIA_JAVASCRIPT'      => $this->_getJavaScriptCodePreview(),
            'MEDIA_SEARCH_TERM'     => contrexx_raw2xhtml(rawurldecode($searchTerm)),
            'TXT_MEDIA_SEARCH'      => $_CORELANG['TXT_SEARCH'],
            'TXT_MEDIA_SEARCH_TERM' => $_ARRAYLANG['TXT_MEDIA_SEARCH_TERM'],
        ));

        if (   $this->_objTpl->blockExists('media_archive_search_form')
            && !$this->isSearchActivated()
        ) {
            $this->_objTpl->hideBlock('media_archive_search_form');
        }

        // Hide folder creation and file upload functionalies,
        // when permission denied and on search mode
        if (!$this->uploadAccessGranted() || !empty($searchTerm)) {
            // if user not allowed to upload files and creating folders -- hide that blocks
            if ($this->_objTpl->blockExists('media_simple_file_upload')) {
                $this->_objTpl->hideBlock('media_simple_file_upload');
            }
            if ($this->_objTpl->blockExists('media_advanced_file_upload')) {
                $this->_objTpl->hideBlock('media_advanced_file_upload');
            }
            if ($this->_objTpl->blockExists('media_create_directory')) {
                $this->_objTpl->hideBlock('media_create_directory');
            }
        } else {
            // forms for uploading files and creating folders
            if ($this->_objTpl->blockExists('media_simple_file_upload')) {
                //data we want to remember for handling the uploaded files
                $data = array(
                    'path' => $this->path,
                    'webPath' => $this->webPath
                );

                //new uploader
                $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
                $uploader->setData($data);
                $uploader->setCallback('mediaCallbackJs');
                $uploader->setFinishedCallback(array(
                    \Cx\Core\Core\Controller\Cx::instanciate()->getCodeBaseCoreModulePath().'/Media/Controller/MediaLibrary.class.php',
                    '\Cx\Core_Modules\Media\Controller\MediaLibrary',
                    'uploadFinished'
                ));

                $this->_objTpl->setVariable(array(
                    'TXT_MEDIA_ADD_NEW_FILE'    => $_ARRAYLANG['TXT_MEDIA_ADD_NEW_FILE'],
                    'MEDIA_UPLOADER_CODE'       => $uploader->getXHtml($_ARRAYLANG['TXT_MEDIA_BROWSE']),
                    'REDIRECT_URL'              => '?section='.$_REQUEST['section'].'&path='.contrexx_raw2encodedUrl($this->webPath)
                ));
                $this->_objTpl->parse('media_simple_file_upload');
            }

            if ($this->_objTpl->blockExists('media_advanced_file_upload')) {
                $this->_objTpl->hideBlock('media_advanced_file_upload');
            }
            // create directory
            $this->_objTpl->setVariable(array(
                'TXT_MEDIA_CREATE_DIRECTORY'        => $_ARRAYLANG['TXT_MEDIA_CREATE_DIRECTORY'],
                'TXT_MEDIA_CREATE_NEW_DIRECTORY'    => $_ARRAYLANG['TXT_MEDIA_CREATE_NEW_DIRECTORY'],
                'MEDIA_CREATE_DIRECTORY_URL'        => CONTREXX_SCRIPT_PATH . '?section=' . $this->archive . $this->getCmd . '&amp;act=newDir&amp;path=' . rawurlencode($this->webPath)
            ));
            $this->_objTpl->parse('media_create_directory');

            //custom uploader
            \JS::activate('cx'); // the uploader needs the framework

            $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader(); //create an uploader
            $uploadId = $uploader->getId();
            $uploader->setCallback('customUploader');
            $uploader->setOptions(array(
                'id'    => 'custom_'.$uploadId,
            ));

            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $folderWidget   = new \Cx\Core_Modules\MediaBrowser\Model\Entity\FolderWidget(
                $cx->getComponent('Session')->getSession()->getTempPath() . '/' . $uploadId,
                true
            );
            $folderWidgetId = $folderWidget->getId();
            $extendedFileInputCode = <<<CODE
    <script type="text/javascript">

        //uploader javascript callback function
        function customUploader(callback) {
                angular.element('#mediaBrowserfolderWidget_$folderWidgetId').scope().refreshBrowser();
        }
    </script>
CODE;

            $this->_objTpl->setVariable(array(
                'UPLOADER_CODE'      => $uploader->getXHtml(),
                'UPLOADER_ID'        => $uploadId,
                'FILE_INPUT_CODE'    => $extendedFileInputCode,
                'FOLDER_WIDGET_CODE' => $folderWidget->getXHtml()
            ));
        }
    }

    /**
     * Format a filename according to configuration option 'Pretty format'
     * of currently loaded media archive.
     *
     * @param   string  $filename The filename to pretty format
     * @return  string  The pretty formatted filename. In case of any error
     *                  or if the function to pretty format is disabled,
     *                  then the original $filename is being returned.
     */
    protected function prettyFormatFilename($filename) {
        // return original filename in case pretty format function is disabled
        if ($this->_arrSettings[strtolower($this->archive) . '_pretty_file_names'] == 'off') {
            return $filename;
        }

        // check if a regexp is set
        $regexpConf = $this->_arrSettings[strtolower($this->archive) . '_pretty_file_name_regexp'];

        // generate pretty formatted filename
        try {
            $regularExpression = new \Cx\Lib\Helpers\RegularExpression($regexpConf);
            $prettyFilename = $regularExpression->replace($filename);

            // return pretty filename if conversion was successful
            if (!is_null($prettyFilename)) {
                return $prettyFilename;
            }
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
        }

        // return original filename in case anything
        // didn't work out as expected
        return $filename;
    }

    /**
     * Chaeck access from settings.
     * If setting value is number then check access using Permission
     * If setting value is 'on' then return true
     * Else return false
     *
     * @return     boolean  true if access gented and false if access denied
     */
    private function uploadAccessGranted()
    {
        $uploadAccessSetting = isset($this->_arrSettings[strtolower($this->archive) . '_frontend_changable'])
                                ? $this->_arrSettings[strtolower($this->archive) . '_frontend_changable']
                                : '';
        if (is_numeric($uploadAccessSetting)
           && \Permission::checkAccess(intval($uploadAccessSetting), 'dynamic', true)) { // access group
            return true;
        } else if ($uploadAccessSetting == 'on') {
            return true;
        }
        return false;
    }

    /**
     * Check Rename/Delete permission from settings.
     * If setting value is number then check access using Permission
     * If setting value is 'on' then return true
     * Else return false
     *
     * @return     boolean  true if access gented and false if access denied
     */
    private function manageAccessGranted()
    {
        $accessSettingKey    = strtolower($this->archive) . '_frontend_managable';
        $manageAccessSetting = isset($this->_arrSettings[$accessSettingKey])
                                ? $this->_arrSettings[$accessSettingKey]
                                : '';
        if (is_numeric($manageAccessSetting)
           && \Permission::checkAccess(intval($manageAccessSetting), 'dynamic', true)) { // access group
            return true;
        } else if ($manageAccessSetting == 'on') {
            return true;
        }
        return false;
    }

    /**
     * Check whether the search setting activated
     *
     * @return boolean  True when frontend search setting active, false otherwise
     */
    public function isSearchActivated()
    {
        $settingKey    = strtolower($this->archive) . '_frontend_search';
        $searchSetting = isset($this->_arrSettings[$settingKey])
                            ? $this->_arrSettings[$settingKey]
                            : '';
        if ($searchSetting == 'on') {
            return true;
        }
        return false;
    }

    /**
     * Format file size
     *
     * @global     array    $_ARRAYLANG
     * @param      int      $bytes
     * @return     string   formated size
     */
    private function getFormatedFileSize($bytes)
    {
        global $_ARRAYLANG;

        if (!$bytes) {
            return $_ARRAYLANG['TXT_MEDIA_UNKNOWN'];
        }

        $exp = log($bytes, 1024);

        if ($exp < 1) {
            return $bytes.' '.$_ARRAYLANG['TXT_MEDIA_BYTES'];
        } elseif ($exp < 2) {
            return round($bytes/1024, 2).' '.$_ARRAYLANG['TXT_MEDIA_KBYTE'];
        } elseif ($exp < 3) {
            return round($bytes/pow(1024, 2), 2).' '.$_ARRAYLANG['TXT_MEDIA_MBYTE'];
        } else {
            return round($bytes/pow(1024, 3), 2).' '.$_ARRAYLANG['TXT_MEDIA_GBYTE'];
        }
    }

    /**
     * Create directory
     *
     * @global     array    $_ARRAYLANG
     * @param      string   $dir_name
     */
    function _createDirectory($dir_name)
    {
        global $_ARRAYLANG;

        if (empty($dir_name)) {
            if (!isset($_GET['highlightFiles'])) {
                \Message::error($_ARRAYLANG['TXT_MEDIA_EMPTY_DIR_NAME']);
            }
            return;
        } else {
            $dir_name = contrexx_stripslashes($dir_name);
        }

        if (!$this->uploadAccessGranted()) {
            \Message::error($_ARRAYLANG['TXT_MEDIA_DIRCREATION_NOT_ALLOWED']);
            return;
        }

        $obj_file = new \File();
        $dir_name = \Cx\Lib\FileSystem\FileSystem::replaceCharacters($dir_name);
        $creationStatus = $obj_file->mkDir($this->path, $this->webPath, $dir_name);
        if ($creationStatus != "error") {
            $this->highlightName[] = $dir_name;
            \Message::ok($_ARRAYLANG['TXT_MEDIA_MSG_NEW_DIR']);
        } else {
            \Message::error($_ARRAYLANG['TXT_MEDIA_MSG_ERROR_NEW_DIR']);
        }
    }

    /**
     * Upload files
     */
    function _uploadFiles()
    {
        global $_ARRAYLANG;

        // check permissions
        if (!$this->uploadAccessGranted()) {
            \Message::error($_ARRAYLANG['TXT_MEDIA_DIRCREATION_NOT_ALLOWED']);
            return;
        }
        $this->processFormUpload();
    }

    /**
     * Process upload form
     *
     * @global     array    $_ARRAYLANG
     * @return     boolean  true if file uplod successfully and false if it failed
     */
    private function processFormUpload()
    {
        global $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $objSession = $cx->getComponent('Session')->getSession();
        $uploaderId = isset($_POST['media_upload_file']) ? contrexx_input2raw($_POST['media_upload_file']) : 0;
        if (empty($uploaderId)) {
            return false;
        }

        $tempPath = $objSession->getTempPath() .'/' . contrexx_input2raw($uploaderId);
        if (!\Cx\Lib\FileSystem\FileSystem::exists($tempPath)) {
            return false;
        }
        $errorMsg = array();
        foreach (glob($tempPath.'/*') as $file) {
            $i        = 0;
            $fileName = basename($file);
            $path     = $tempPath . '/' . $fileName;
            $file     = $this->path . $fileName;
            $arrFile  = pathinfo($file);
            while (file_exists($file)) {
                $suffix = '-' . (time() + (++$i));
                $file   = $this->path . $arrFile['filename'] . $suffix . '.' . $arrFile['extension'];
            }

            if (!\FWValidator::is_file_ending_harmless($path)) {
                $errorMsg[] = sprintf($_ARRAYLANG['TXT_MEDIA_FILE_EXTENSION_NOT_ALLOWED'], htmlentities($fileName, ENT_QUOTES, CONTREXX_CHARSET));;
                continue;
            }

            try {
                $objFile = new \Cx\Lib\FileSystem\File($path);
                $objFile->move($file, false);
                $fileObj = new \File();
                $fileObj->setChmod($this->path, $this->webPath, basename($file));
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
                $errorMsg[] = sprintf($_ARRAYLANG['TXT_MEDIA_FILE_UPLOAD_FAILED'], htmlentities($fileName, ENT_QUOTES, CONTREXX_CHARSET));
            }
        }

        if (!empty($errorMsg)) {
            $msgs = explode('<br>', $errorMsg);
            foreach ($msgs as $msg) {
                \Message::error($msg);
            }
            return false;
        }

        \Message::ok($_ARRAYLANG['TXT_MEDIA_FILE_UPLOADED_SUCESSFULLY']);
        return true;
    }

    /**
     * Rename files
     *
     * @global     array    $_ARRAYLANG
     * @return     boolean  true if file renamed successfully and false if it failed
     */
    function _renameFiles()
    {
        global $_ARRAYLANG;

        // check permissions
        if (!$this->manageAccessGranted()) {
            \Message::error($_ARRAYLANG['TXT_MEDIA_DIRCREATION_NOT_ALLOWED']);
            return $this->handleRedirect();
        }

        if (MediaLibrary::isIllegalFileName($this->getFile)) {
            \Message::error($_ARRAYLANG['TXT_MEDIA_FILE_DONT_EDIT']);
            return $this->handleRedirect();
        }

        if (isset($_GET['newfile']) && file_exists($this->path.$this->getFile)) {
            $newFile = trim(preg_replace('/[^a-z0-9_\-\. ]/i', '_', $_GET['newfile']));
            if ($newFile != "") {
                if (!file_exists($this->path.$newFile)) {
                    if (rename($this->path.$this->getFile, $this->path.$newFile)) {
                        \Message::ok(sprintf($_ARRAYLANG['TXT_MEDIA_FILE_RENAME_SUCESSFULLY'], '<strong>'.htmlentities($this->getFile, ENT_QUOTES, CONTREXX_CHARSET).'</strong>', '<strong>'.htmlentities($newFile, ENT_QUOTES, CONTREXX_CHARSET).'</strong>'));
                    } else {
                        \Message::error($_ARRAYLANG['TXT_MEDIA_FILE_NAME_INVALID']);
                    }
                } else {
                    \Message::error(sprintf($_ARRAYLANG['TXT_MEDIA_FILE_AREALDY_EXSIST'], '<strong>'.htmlentities($newFile, ENT_QUOTES, CONTREXX_CHARSET).'</strong>'));
                }
            } else {
                \Message::error($_ARRAYLANG['TXT_MEDIA_FILE_EMPTY_NAME']);
            }
        } else {
            \Message::error(sprintf($_ARRAYLANG['TXT_MEDIA_FILE_NOT_FOUND'], htmlentities($this->getFile, ENT_QUOTES, CONTREXX_CHARSET)));
        }

        $this->handleRedirect();
    }

    /**
     * Delete files
     *
     * @global     array    $_ARRAYLANG
     * @return     boolean  true if file deleted successfully and false if it failed
     */
    function _deleteFiles()
    {
        global $_ARRAYLANG;

        // check permissions
        if (!$this->manageAccessGranted()) {
            \Message::error($_ARRAYLANG['TXT_MEDIA_DIRCREATION_NOT_ALLOWED']);
            return $this->handleRedirect();
        }

        if (MediaLibrary::isIllegalFileName($this->getFile)) {
            \Message::error($_ARRAYLANG['TXT_MEDIA_FILE_DONT_DELETE']);
            return $this->handleRedirect();
        }

        if (isset($_GET['path'])) {
            if (isset($_GET['file'])) {
                $filePath = $this->path . $this->getFile;
                if (unlink($filePath)) {
                    \Message::ok(sprintf($_ARRAYLANG['TXT_MEDIA_FILE_DELETED_SUCESSFULLY'], '<strong>'.htmlentities($this->getFile, ENT_QUOTES, CONTREXX_CHARSET).'</strong>'));
                } else {
                    \Message::error(sprintf($_ARRAYLANG['TXT_MEDIA_FILE_NOT_FOUND'], htmlentities($this->getFile, ENT_QUOTES, CONTREXX_CHARSET)));
                }
            } else {
                $this->deleteDirectory($this->path);
            }
        }
        return $this->handleRedirect();
    }

     /**
     * Delete Selected Folder and its contents recursively upload form
     *
     * @global     array    $_ARRAYLANG
     * @param      string   $dirName
     * @return     boolean  true if directory and its contents deleted successfully and false if it failed
     */
    private function deleteDirectory($dirName)
    {
        global $_ARRAYLANG;

        try {
            \Cx\Lib\FileSystem\FileSystem::delete_folder($dirName, true);
            \Message::ok($_ARRAYLANG['TXT_MEDIA_FOLDER_DELETED_SUCESSFULLY']);
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
            return false;
        }

        return true;
    }

}
