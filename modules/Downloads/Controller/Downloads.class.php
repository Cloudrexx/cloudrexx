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
 * Downloads
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_downloads
 * @version     1.0.0
 */
namespace Cx\Modules\Downloads\Controller;
/**
* Downloads
* @copyright    CLOUDREXX CMS - CLOUDREXX AG
* @author       CLOUDREXX Development Team <info@cloudrexx.com>
* @package      cloudrexx
* @subpackage   module_downloads
* @version      1.0.0
*/
class Downloads extends DownloadsLibrary
{
    private $htmlLinkTemplate = '<a href="%s" title="%s">%s</a>';
    private $htmlImgTemplate = '<img src="%s" alt="%s" />';
    protected $moduleParamsHtml = '?section=Downloads';
    private $moduleParamsJs = '?section=Downloads';
    private $userId;
    private $categoryId;
    private $cmd = '';
    private $pageTitle;

    /**
     * @var string $metaKeys The metakeys is used to set page metakeys
     */
    private $metaKeys;

    /**
     * @var \Cx\Core\Html\Sigma
     */
    private $objTemplate;
    /**
     * Contains the info messages about done operations
     * @var array
     * @access private
     */
    private $arrStatusMsg = array('ok' => array(), 'error' => array());

    /**
     * The reuqested page this component will be parsed into
     * @var \Cx\Core\ContentManager\Model\Entity\Page
     */
    protected $requestedPage = null;

    /**
     * Whether or not this instance is used for parsing the frontend
     * application section or for a widget.
     *
     * If instance is used for processing a regular frontend request to this
     * components application section, then $isRegularMode is set TRUE.
     * Otherwise, if this instance is being used for parsing a Widget of this
     * component, then $isRegularMode is set to FALSE.
     *
     * @var boolean
     */
    protected $isRegularMode = true;

    /**
    * Constructor
    *
    * Calls the parent constructor and creates a local template object
    * @param mixed $pageContent The content of the page as string or
    *                           \Cx\Core\Html\Sigma template
    * @param $queryParams array The constructor accepts an array parameter $queryParams, which will
    *                           override the request parameters cmd and/or category, if given
    * @param $requestedPage \Cx\Core\ContentManager\Model\Entity\Page The requested page this
    *                           component will be parsed into
    */
    function __construct($pageContent, array $queryParams = array(), $requestedPage = null)
    {
        parent::__construct();

        $objFWUser = \FWUser::getFWUserObject();
        $this->userId = $objFWUser->objUser->login() ? $objFWUser->objUser->getId() : 0;

        // if $requestedPage is set, then we're about to process a widget
        if ($requestedPage) {
            $this->isRegularMode = false;
            $this->requestedPage = $requestedPage;
        } else {
            $this->requestedPage = \Cx\Core\Core\Controller\Cx::instanciate()->getPage();
        }

        $this->parseURLModifiers($queryParams);
        if ($pageContent instanceof \Cx\Core\Html\Sigma) {
            $this->objTemplate = $pageContent;
        } else {
            $this->objTemplate = new \Cx\Core\Html\Sigma('.');
            $this->objTemplate->setTemplate($pageContent);
            \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->objTemplate);
            $this->objTemplate->setErrorHandling(PEAR_ERROR_DIE);
        }
    }

    private function parseURLModifiers($queryParams)
    {
        $cmd = isset($queryParams['cmd']) ? $queryParams['cmd'] : ($this->isRegularMode && isset($_REQUEST['cmd']) ? $_REQUEST['cmd'] : '');

        if ($this->isRegularMode && isset($_GET['download'])) {
            $this->cmd = 'download_file';
        } elseif ($this->isRegularMode && isset($_GET['delete_file'])) {
            $this->cmd = 'delete_file';
        } elseif ($this->isRegularMode && isset($_GET['delete_category'])) {
            $this->cmd = 'delete_category';
        } elseif ($cmd) {
            $this->cmd = $cmd;
        }

        if ($cmd) {
            $this->moduleParamsHtml .= '&cmd='.htmlentities($cmd, ENT_QUOTES, CONTREXX_CHARSET);
            $this->moduleParamsJs .= '&cmd='.htmlspecialchars($cmd, ENT_QUOTES, CONTREXX_CHARSET);
        }

        if (intval($cmd)) {
            $this->categoryId = isset($queryParams['category']) ? $queryParams['category'] : ($this->isRegularMode && !empty($_REQUEST['category']) ? intval($_REQUEST['category']) : intval($cmd));
        } else {
            $this->categoryId = isset($queryParams['category']) ? $queryParams['category'] : ($this->isRegularMode && !empty($_REQUEST['category']) ? intval($_REQUEST['category']) : 0);
        }
    }

    /**
    * Reads $this->cmd and selects (depending on the value) an action
    *
    */
    public function getPage()
    {
        \Cx\Core\Csrf\Controller\Csrf::add_code();

        // TODO: Algorithm for loading downloads has to be refactored
        //       so that it does not check the access permissions through SQL
        //       but instead using PHP. Checking the access permissions
        //       on the PHP side would allow us to determine if we need
        //       to activate user based page caching.
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $cx->getComponent('Cache')->forceUserbasedPageCache();

        switch ($this->cmd) {
            case 'download_file':
                $this->download();
                exit;
                break;

            case 'delete_file':
                $this->deleteDownload();
                $this->overview();
                break;

            case 'delete_category':
                $this->deleteCategory();
                $this->overview();
                break;

            default:
                $this->overview();
                break;
        }

        $this->parseMessages();

        return $this->objTemplate->get();
    }


    private function parseMessages()
    {
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_MSG_OK'      => count($this->arrStatusMsg['ok']) ? implode('<br />', $this->arrStatusMsg['ok']) : '',
            'DOWNLOADS_MSG_ERROR'   => count($this->arrStatusMsg['error']) ? implode('<br />', $this->arrStatusMsg['error']) : ''
        ));
    }


    private function deleteDownload()
    {
        global $_LANGID, $_ARRAYLANG;

        \Cx\Core\Csrf\Controller\Csrf::check_code();
        $id = isset($_GET['delete_file']) ? contrexx_input2int($_GET['delete_file']) : 0;
        $objDownload = new Download($this->arrConfig);
        $objDownload->load($id, $this->arrConfig['list_downloads_current_lang']);

        if (!$objDownload->EOF) {
            $name = '<strong>'.htmlentities($objDownload->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET).'</strong>';
            if ($objDownload->delete($this->categoryId)) {
                $this->arrStatusMsg['ok'][] = sprintf($_ARRAYLANG['TXT_DOWNLOADS_DOWNLOAD_DELETE_SUCCESS'], $name);
            } else {
                $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objDownload->getErrorMsg());
            }
        }
    }


    private function deleteCategory()
    {
        global $_ARRAYLANG;

        \Cx\Core\Csrf\Controller\Csrf::check_code();
        $objCategory = Category::getCategory(isset($_GET['delete_category']) ? $_GET['delete_category'] : 0);

        if (!$objCategory->EOF) {
            $name = '<strong>'.htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET).'</strong>';
            if ($objCategory->delete()) {
                $this->arrStatusMsg['ok'][] = sprintf($_ARRAYLANG['TXT_DOWNLOADS_CATEGORY_DELETE_SUCCESS'], $name);
            } else {
                $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objCategory->getErrorMsg());
            }
        }
    }


    private function overview()
    {
        // load source code if cmd value is integer
        if ($this->objTemplate->placeholderExists('APPLICATION_DATA')) {
            $page = new \Cx\Core\ContentManager\Model\Entity\Page();
            $page->setVirtual(true);
            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
            $page->setModule('Downloads');
            // load source code
            $applicationTemplate = \Cx\Core\Core\Controller\Cx::getContentTemplateOfPage($page);
            \LinkGenerator::parseTemplate($applicationTemplate);
            $this->objTemplate->addBlock('APPLICATION_DATA', 'application_data', $applicationTemplate);
        }

        $objDownload = new Download($this->arrConfig);
        $objCategory = Category::getCategory($this->categoryId);

        if (!$objCategory->getActiveStatus()) {
            return;
        }

        if ($objCategory->getId()) {
            // check access permissions to selected category
            if (!\Permission::checkAccess(143, 'static', true)
                && $objCategory->getReadAccessId()
                && !\Permission::checkAccess($objCategory->getReadAccessId(), 'dynamic', true)
                && $objCategory->getOwnerId() != $this->userId
            ) {
                // in case we're processing a widget, then we shall not
                // redirect the user to the no-access section
                if (!$this->isRegularMode) {
                    return;
                }
                \Permission::noAccess(base64_encode(CONTREXX_SCRIPT_PATH.$this->moduleParamsJs.'&category='.$objCategory->getId()));
            }

            // parse crumbtrail
            $this->parseCrumbtrail($objCategory);

            $id = $this->isRegularMode && !empty($_REQUEST['id']) ? contrexx_input2int($_REQUEST['id']) : 0;
            if (
                $objDownload->load($id, $this->arrConfig['list_downloads_current_lang']) &&
                (
                    !$objDownload->getExpirationDate() ||
                    $objDownload->getExpirationDate() > time()
                ) &&
                $objDownload->getActiveStatus()
            ) {
                /* DOWNLOAD DETAIL PAGE */
                $this->pageTitle = $objDownload->getName();

                $metakeys = $objDownload->getMetakeys();
                if ($this->arrConfig['use_attr_metakeys'] && !empty($metakeys)) {
                    $this->metaKeys = $metakeys;
                }

                $this->parseRelatedCategories($objDownload);
                $this->parseRelatedDownloads($objDownload, $objCategory->getId());

                $this->parseDownload($objDownload, $objCategory->getId());

                // hide unwanted blocks on the detail page
                if ($this->objTemplate->blockExists('downloads_category')) {
                    $this->objTemplate->hideBlock('downloads_category');
                }
                if ($this->objTemplate->blockExists('downloads_subcategory_list')) {
                    $this->objTemplate->hideBlock('downloads_subcategory_list');
                }
                if ($this->objTemplate->blockExists('downloads_file_list')) {
                    $this->objTemplate->hideBlock('downloads_file_list');
                }
                if ($this->objTemplate->blockExists('downloads_simple_file_upload')) {
                    $this->objTemplate->hideBlock('downloads_simple_file_upload');
                }
                if ($this->objTemplate->blockExists('downloads_advanced_file_upload')) {
                    $this->objTemplate->hideBlock('downloads_advanced_file_upload');
                }
            } else {
                /* CATEGORY DETAIL PAGE */
                $this->pageTitle = $objCategory->getName();

                // process create directory
                $this->processCreateDirectory($objCategory);

                // parse selected category
                $this->parseCategory($objCategory);

                // parse subcategories
                $this->parseCategories($objCategory, array('downloads_subcategory_list', 'downloads_subcategory'), null, 'SUB');

                // parse downloads of selected category
                $this->parseDownloads($objCategory);

                // parse upload form
                $this->parseUploadForm($objCategory);

                // parse create directory form
                $this->parseCreateCategoryForm($objCategory);

                // hide unwanted blocks on the category page
                if ($this->objTemplate->blockExists('downloads_download')) {
                    $this->objTemplate->hideBlock('downloads_download');
                }
                if ($this->objTemplate->blockExists('downloads_file_detail')) {
                    $this->objTemplate->hideBlock('downloads_file_detail');
                }
            }

            // hide unwanted blocks on the category/detail page
            if ($this->objTemplate->blockExists('downloads_overview')) {
                $this->objTemplate->hideBlock('downloads_overview');
            }
            if ($this->objTemplate->blockExists('downloads_most_viewed_file_list')) {
                $this->objTemplate->hideBlock('downloads_most_viewed_file_list');
            }
            if ($this->objTemplate->blockExists('downloads_most_downloaded_file_list')) {
                $this->objTemplate->hideBlock('downloads_most_downloaded_file_list');
            }
            if ($this->objTemplate->blockExists('downloads_most_popular_file_list')) {
                $this->objTemplate->hideBlock('downloads_most_popular_file_list');
            }
            if ($this->objTemplate->blockExists('downloads_newest_file_list')) {
                $this->objTemplate->hideBlock('downloads_newest_file_list');
            }
            if ($this->objTemplate->blockExists('downloads_updated_file_list')) {
                $this->objTemplate->hideBlock('downloads_updated_file_list');
            }
        } else {
            /* CATEGORY OVERVIEW PAGE */
            $this->parseCategories($objCategory, array('downloads_overview', 'downloads_overview_category'), null, null, 'downloads_overview_row', array('downloads_overview_subcategory_list', 'downloads_overview_subcategory'), $this->arrConfig['overview_max_subcats']);

            if (!empty($this->searchKeyword)) {
                $this->parseDownloads($objCategory);
            } else {
                if ($this->objTemplate->blockExists('downloads_file_list')) {
                    $this->objTemplate->hideBlock('downloads_file_list');
                }
            }

            /* PARSE MOST VIEWED DOWNLOADS */
            $this->parseSpecialDownloads(array('downloads_most_viewed_file_list', 'downloads_most_viewed_file'), array('is_active' => true, 'expiration' => array('=' => 0, '>' => time())) /* this filters purpose is only that the method Download::getFilteredIdList() gets processed */, array('views' => 'desc'), $this->arrConfig['most_viewed_file_count']);

            /* PARSE MOST DOWNLOADED DOWNLOADS */
            $this->parseSpecialDownloads(array('downloads_most_downloaded_file_list', 'downloads_most_downloaded_file'), array('is_active' => true, 'expiration' => array('=' => 0, '>' => time())) /* this filters purpose is only that the method Download::getFilteredIdList() gets processed */, array('download_count' => 'desc'), $this->arrConfig['most_downloaded_file_count']);

            /* PARSE MOST POPULAR DOWNLOADS */
            // TODO: Rating system has to be implemented first!
            //$this->parseSpecialDownloads(array('downloads_most_popular_file_list', 'downloads_most_popular_file'), null, array('rating' => 'desc'), $this->arrConfig['most_popular_file_count']);

            /* PARSE RECENTLY UPDATED DOWNLOADS */
            $filter = array(
                'ctime' => array(
                    '>=' => time() - $this->arrConfig['new_file_time_limit']
                ),
                'expiration' => array(
                    '=' => 0,
                    '>' => time()
                )
            );
            $this->parseSpecialDownloads(array('downloads_newest_file_list', 'downloads_newest_file'), $filter, array('ctime' => 'desc'), $this->arrConfig['newest_file_count']);

            // parse recently updated downloads
            $filter = array(
                'mtime' => array(
                    '>=' => time() - $this->arrConfig['updated_file_time_limit']
                ),
                // exclude newest downloads
                'ctime' => array(
                    '<' => time() - $this->arrConfig['new_file_time_limit']
                ),
                'expiration' => array(
                    '=' => 0,
                    '>' => time()
                )
            );
            $this->parseSpecialDownloads(array('downloads_updated_file_list', 'downloads_updated_file'), $filter, array('mtime' => 'desc'), $this->arrConfig['updated_file_count']);


            // hide unwanted blocks on the overview page
            if ($this->objTemplate->blockExists('downloads_category')) {
                $this->objTemplate->hideBlock('downloads_category');
            }
            if ($this->objTemplate->blockExists('downloads_crumbtrail')) {
                $this->objTemplate->hideBlock('downloads_crumbtrail');
            }
            if ($this->objTemplate->blockExists('downloads_subcategory_list')) {
                $this->objTemplate->hideBlock('downloads_subcategory_list');
            }
            if ($this->objTemplate->blockExists('downloads_file_detail')) {
                $this->objTemplate->hideBlock('downloads_file_detail');
            }
            if ($this->objTemplate->blockExists('downloads_simple_file_upload')) {
                $this->objTemplate->hideBlock('downloads_simple_file_upload');
            }
            if ($this->objTemplate->blockExists('downloads_advanced_file_upload')) {
                $this->objTemplate->hideBlock('downloads_advanced_file_upload');
            }
        }
        $this->parseGlobalStuff($objCategory);
    }

    /**
     * Upload Finished callback
     *
     * This is called as soon as uploads have finished.
     * takes care of moving them to the right folder
     *
     * @param string $tempPath    Path to the temporary directory containing the files at this moment
     * @param string $tempWebPath Points to the same folder as tempPath, but relative to the webroot
     * @param array  $data        Data given to setData() when creating the uploader
     * @param string $uploadId    unique session id for the current upload
     * @param array  $fileInfos   uploaded file informations
     * @param array  $response    uploaded status
     *
     * @return array path and webpath
     */
    public static function uploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response)
    {

        $path = $data['path'];
        $webPath = $data['webPath'];
        $objCategory = Category::getCategory($data['category_id']);

        // check for sufficient permissions
        if ($objCategory->getAddFilesAccessId() && !\Permission::checkAccess($objCategory->getAddFilesAccessId(), 'dynamic', true) && $objCategory->getOwnerId() != \FWUser::getFWUserObject()->objUser->getId()) { return; }

        //we remember the names of the uploaded files here. they are stored in the session afterwards,
        //so we can later display them highlighted.
        $arrFiles = array();
        $uploadFiles = array();
        //rename files, delete unwanted
        $arrFilesToRename = array(); //used to remember the files we need to rename
        $h = opendir($tempPath);

        if (!$h) {
            return array($path, $webPath);
        }

        while (false !== ($file = readdir($h))) {
            //skip . and ..
            if ($file == '.' || $file == '..') { continue; }

            try {
                //delete potentially malicious files
                $objTempFile = new \Cx\Lib\FileSystem\File($tempPath . '/' . $file);
                if (!\FWValidator::is_file_ending_harmless($file)) {
                    $objTempFile->delete();
                    continue;
                }

                $cleanFile = \Cx\Lib\FileSystem\FileSystem::replaceCharacters($file);
                if ($cleanFile != $file) {
                    $objTempFile->rename($tempPath . '/' . $cleanFile, false);
                    $file = $cleanFile;
                }

                $info = pathinfo($file);
                //check if file needs to be renamed
                $newName = '';
                $suffix = '';

                if (file_exists($path . '/' . $file)) {
                    $suffix = '_' . time();
                    $newName = $info['filename'] . $suffix . '.' . $info['extension'];
                    $arrFilesToRename[$file] = $newName;
                    array_push($arrFiles, $newName);
                }

                if (!isset($arrFilesToRename[$file])) {
                    array_push($uploadFiles, $file);
                }

                //rename files where needed
                foreach ($arrFilesToRename as $oldName => $newName) {
                    $objTempFile = new \Cx\Lib\FileSystem\File($tempPath . '/' . $oldName);
                    $objTempFile->rename($tempPath . '/' . $newName, false);
                    array_push($uploadFiles, $newName);
                }

                //move file from temp path into target folder
                $objImage = new \ImageManager();
                foreach ($uploadFiles as $fileName) {
                    $objFile = new \Cx\Lib\FileSystem\File(
                        $tempPath . '/' . $fileName
                    );
                    $objFile->move($path . '/' . $fileName, false);
                    \Cx\Core\Core\Controller\Cx::instanciate()
                        ->getMediaSourceManager()->getThumbnailGenerator()
                        ->createThumbnailFromPath($path . '/' . $fileName);
                }
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
            }

            $objDownloads = new downloads('');
            $objDownloads->addDownloadFromUpload($info['filename'], $info['extension'], $suffix, $objCategory, $objDownloads, $fileInfos['name'], $data);
        }

        return array($path, $webPath);
    }

    /**
     * Save upload file details to database for download
     *
     * @param string $fileName      filename it is modified name or original name
     *                              ie) what name will be mentioned for upload file in target folder
     * @param string $fileExtension file extension
     * @param mixed  $suffix        if choosen file is already exist, suffix will be created as string
     *                              otherwise empty
     * @param object $objCategory   upload file category
     * @param object $objDownloads  downdload file object from the upload informations
     * @param object $sourceName    original file name
     *
     * @return boolean true | false
     */
    public static function addDownloadFromUpload($fileName, $fileExtension, $suffix, $objCategory, $objDownloads, $sourceName, $data)
    {
        $objDownload = new Download($objDownloads->getSettings());

        // parse name and description attributres
        $arrLanguageIds = array_keys(\FWLanguage::getLanguageArray());
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $downloadName = $objDownloads->getPrettyFormatFileName($sourceName);
        foreach ($arrLanguageIds as $langId) {
            $arrNames[$langId] = $downloadName;
            $arrMetakeys[$langId] = '';
            $arrDescriptions[$langId] = '';
            $arrSourcePaths[$langId] = \Cx\Core\Core\Controller\Cx::FOLDER_NAME_IMAGES . '/Downloads/'.$fileName.$suffix.'.'.$fileExtension;
            $arrSourceNames[$langId] = $sourceName;
        }

        $fileMimeType = null;
        foreach (Download::$arrMimeTypes as $mimeType => $arrMimeType) {
            if (!count($arrMimeType['extensions'])) {
                continue;
            }
            if (in_array(strtolower($fileExtension), $arrMimeType['extensions'])) {
                $fileMimeType = $mimeType;
                break;
            }
        }

        $objDownload->setNames($arrNames);
        $objDownload->setMetakeys($arrMetakeys);
        $objDownload->setDescriptions($arrDescriptions);
        $objDownload->setType('file');
        $objDownload->setSources($arrSourcePaths, $arrSourceNames);
        $objDownload->setActiveStatus(true);
        $objDownload->setMimeType($fileMimeType);
        if ($objDownload->getMimeType() == 'image') {
            $objDownload->setImage(
                substr(
                    $cx->getWebsiteImagesDownloadsWebPath(),
                    strlen($cx->getCodeBaseOffsetPath()) + 1
                ) . '/' . $fileName . $suffix . '.' . $fileExtension
            );
        }
        $objDownloads->arrConfig['use_attr_size'] ? $objDownload->setSize(filesize($cx->getWebsiteImagesDownloadsPath().'/'.$fileName.$suffix.'.'.$fileExtension)) : null;
        $objDownload->setVisibility(true);
        $objDownload->setProtection(false);
        $objDownload->setGroups(array());
        $objDownload->setCategories(array($objCategory->getId()));
        $objDownload->setDownloads(array());

        if (!$objDownload->store($objCategory, \FWLanguage::getActiveFrontendLanguages())) {
            $objDownloads->arrStatusMsg['error'] = array_merge($objDownloads->arrStatusMsg['error'], $objDownload->getErrorMsg());
            return false;
        } else {
            $downloadUrl = \Cx\Core\Routing\Url::fromMagic('/' . $data['appCmd']);
            $downloadUrl->setParams(array(
                'download' => $objDownload->getId(),
            ));
            \Cx\Core\MailTemplate\Controller\MailTemplate::send(
                array(
                    'section' => 'Downloads',
                    'lang_id' => DownloadsLibrary::getOutputLocale()->getId(),
                    'key'     => 'new_asset_notification',
                    'substitution' => array(
                        'DOMAIN_URL'    => \Env::get('config')['domainUrl'],
                        'FILE_OWNER'    => $data['owner'],
                        'FILE_NAME'     => $objDownload->getName(),
                        'CATEGORY_NAME' => $objCategory->getName(),
                        'FILE_DOWNLOAD_LINK_SRC' => $downloadUrl->toString(),
                    )
                )
            );
            return true;
        }
    }

    private function processCreateDirectory($objCategory)
    {
        if (!$this->isRegularMode || empty($_POST['downloads_category_name'])) {
            return;
        } else {
            $name = contrexx_stripslashes($_POST['downloads_category_name']);
        }

        \Cx\Core\Csrf\Controller\Csrf::check_code();

        // check for sufficient permissiosn
        if ($objCategory->getAddSubcategoriesAccessId()
            && !\Permission::checkAccess($objCategory->getAddSubcategoriesAccessId(), 'dynamic', true)
            && $objCategory->getOwnerId() != $this->userId
        ) {
            return;
        }

        // parse name and description attributres
        $arrLanguageIds = array_keys(\FWLanguage::getLanguageArray());


        foreach ($arrLanguageIds as $langId) {
            $arrNames[$langId] = $name;
            $arrDescriptions[$langId] = '';
        }

        $objSubcategory = new Category();
        $objSubcategory->setParentId($objCategory->getId());
        $objSubcategory->setActiveStatus(true);
        $objSubcategory->setVisibility($objCategory->getVisibility());
        $objSubcategory->setNames($arrNames);
        $objSubcategory->setDescriptions($arrDescriptions);
        $objSubcategory->setPermissions(array(
            'read' => array(
                'protected' => (bool) $objCategory->getAddSubcategoriesAccessId(),
                'groups'    => array()
            ),
            'add_subcategories' => array(
                'protected' => (bool) $objCategory->getAddSubcategoriesAccessId(),
                'groups'    => array()
            ),
            'manage_subcategories' => array(
                'protected' => (bool) $objCategory->getAddSubcategoriesAccessId(),
                'groups'    => array()
            ),
            'add_files' => array(
                'protected' => (bool) $objCategory->getAddSubcategoriesAccessId(),
                'groups'    => array()
            ),
            'manage_files' => array(
                'protected' => (bool) $objCategory->getAddSubcategoriesAccessId(),
                'groups'    => array()
            )
        ));

//
//            foreach ($this->arrPermissionTypes as $protectionType) {
//                $arrCategoryPermissions[$protectionType]              = array();
//                $arrCategoryPermissions[$protectionType]['protected'] = isset($_POST['downloads_category_'.$protectionType]) && $_POST['downloads_category_'.$protectionType];
//                $arrCategoryPermissions[$protectionType]['groups'] = !empty($_POST['downloads_category_'.$protectionType.'_associated_groups']) ? array_map('intval', $_POST['downloads_category_'.$protectionType.'_associated_groups']) : array();
//            }
//
//            $objCategory->setPermissionsRecursive(!empty($_POST['downloads_category_apply_recursive']));
//            $objCategory->setPermissions($arrCategoryPermissions);

        if (!$objSubcategory->store()) {
            $this->arrStatusMsg['error'] = array_merge($this->arrStatusMsg['error'], $objSubcategory->getErrorMsg());
        }
    }


    private function parseUploadForm($objCategory)
    {
        global $_CONFIG, $_ARRAYLANG;

        if ($this->objTemplate->blockExists('downloads_advanced_file_upload')) {
            $this->objTemplate->hideBlock('downloads_advanced_file_upload');
        }

        if (!$this->objTemplate->blockExists('downloads_simple_file_upload')) {
            return;
        }

        // check for upload permissiosn
        if ($objCategory->getAddFilesAccessId()
            && !\Permission::checkAccess($objCategory->getAddFilesAccessId(), 'dynamic', true)
            && $objCategory->getOwnerId() != $this->userId
        ) {
            if ($this->objTemplate->blockExists('downloads_simple_file_upload')) {
                $this->objTemplate->hideBlock('downloads_simple_file_upload');
            }
            return;
        }

        if ($this->objTemplate->blockExists('downloads_simple_file_upload')) {
            $objFWSystem = new \FWSystem();

            //Uploader button handling
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            //paths we want to remember for handling the uploaded files
            $data = array(
                'path'          => $cx->getWebsiteImagesDownloadsPath(), //target folder
                'webPath'       => $cx->getWebsiteImagesDownloadsWebPath(),
                'category_id'   => $objCategory->getId(),
                'appCmd'        => $this->moduleParamsHtml,
                'owner'         => $this->getParsedUsername($this->userId),
            );
            $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
            $uploader->setFinishedCallback(array(
                $cx->getCodeBaseModulePath().'/Downloads/Controller/Downloads.class.php',
                '\Cx\Modules\Downloads\Controller\Downloads',
                'uploadFinished'
            ));
            $uploader->setCallback('uploadFinishedCallbackJs');
            $uploader->setData($data);
            $this->objTemplate->setVariable(array(
                'UPLOADER_CODE'                 => $uploader->getXHtml($_ARRAYLANG['TXT_DOWNLOADS_UPLOAD_FILE']),
                'DOWNLOADS_UPLOAD_REDIRECT_URL' => \Env::get('Resolver')->getURL(),
                'TXT_DOWNLOADS_BROWSE'          => $_ARRAYLANG['TXT_DOWNLOADS_BROWSE'],
                'TXT_DOWNLOADS_UPLOAD_FILE'     => $_ARRAYLANG['TXT_DOWNLOADS_UPLOAD_FILE'],
                'TXT_DOWNLOADS_MAX_FILE_SIZE'   => $_ARRAYLANG['TXT_DOWNLOADS_MAX_FILE_SIZE'],
                'TXT_DOWNLOADS_ADD_NEW_FILE'    => $_ARRAYLANG['TXT_DOWNLOADS_ADD_NEW_FILE'],
                'DOWNLOADS_MAX_FILE_SIZE'       => $this->getFormatedFileSize($objFWSystem->getMaxUploadFileSize())
            ));
            $this->objTemplate->parse('downloads_simple_file_upload');
        }
    }


    private function parseCreateCategoryForm($objCategory)
    {
        global $_ARRAYLANG;

        if (!$this->objTemplate->blockExists('downloads_create_category')) {
            return;
        }

        // check for sufficient permissiosn
        if ($objCategory->getAddSubcategoriesAccessId()
            && !\Permission::checkAccess($objCategory->getAddSubcategoriesAccessId(), 'dynamic', true)
            && $objCategory->getOwnerId() != $this->userId
        ) {
            if ($this->objTemplate->blockExists('downloads_create_category')) {
                $this->objTemplate->hideBlock('downloads_create_category');
            }
            return;
        }

        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_CREATE_DIRECTORY'        => $_ARRAYLANG['TXT_DOWNLOADS_CREATE_DIRECTORY'],
            'TXT_DOWNLOADS_CREATE_NEW_DIRECTORY'    => $_ARRAYLANG['TXT_DOWNLOADS_CREATE_NEW_DIRECTORY'],
            'DOWNLOADS_CREATE_CATEGORY_URL'         => CONTREXX_SCRIPT_PATH . $this->moduleParamsHtml . '&amp;category=' . $objCategory->getId()
        ));
        $this->objTemplate->parse('downloads_create_category');
    }


    private function parseCategory($objCategory)
    {
        if (!$this->objTemplate->blockExists('downloads_category')) {
            return;
        }

        $description = $objCategory->getDescription();
        if (strlen($description) > 100) {
            $shortDescription = substr($description, 0, 97).'...';
        } else {
            $shortDescription = $description;
        }

        $imageSrc = $objCategory->getImage();
        if (!empty($imageSrc) && file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteDocumentRootPath().$imageSrc)) {
            $thumb_name = \ImageManager::getThumbnailFilename($imageSrc);
            if (file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteDocumentRootPath().$thumb_name)) {
                $thumbnailSrc = $thumb_name;
            } else {
                $thumbnailSrc = \ImageManager::getThumbnailFilename(
                    $this->defaultCategoryImage['src']);
            }
        } else {
            $imageSrc = $this->defaultCategoryImage['src'];
            $thumbnailSrc = \ImageManager::getThumbnailFilename(
                $this->defaultCategoryImage['src']);
        }

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_CATEGORY_ID'                 =>  $objCategory->getId(),
            'DOWNLOADS_CATEGORY_NAME'               => htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET),
            'DOWNLOADS_CATEGORY_DESCRIPTION'        => nl2br(htmlentities($description, ENT_QUOTES, CONTREXX_CHARSET)),
            'DOWNLOADS_CATEGORY_SHORT_DESCRIPTION'  => htmlentities($shortDescription, ENT_QUOTES, CONTREXX_CHARSET),
            'DOWNLOADS_CATEGORY_IMAGE'              => $this->getHtmlImageTag($imageSrc, htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET)),
            'DOWNLOADS_CATEGORY_IMAGE_SRC'          => $imageSrc,
            'DOWNLOADS_CATEGORY_THUMBNAIL'          => $this->getHtmlImageTag($thumbnailSrc, htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET)),
            'DOWNLOADS_CATEGORY_THUMBNAIL_SRC'      => $thumbnailSrc,
        ));

        $this->parseGroups($objCategory);

        $this->objTemplate->parse('downloads_category');
    }


    private function parseGroups($objCategory)
    {
        if (!$this->objTemplate->blockExists('downloads_category_group_list')) {
            return;
        }

        $objGroup = Group::getGroups(array('category_id' => $objCategory->getId(), 'is_active' => true));

        if (!$objGroup->EOF) {
            while (!$objGroup->EOF) {
                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_GROUP_ID'        => $objGroup->getId(),
                    'DOWNLOADS_GROUP_NAME'      => htmlentities($objGroup->getName(), ENT_QUOTES, CONTREXX_CHARSET),
                    'DOWNLOADS_GROUP_PAGE'      => $objGroup->getInfoPage()
                ));

                $this->objTemplate->parse('downloads_category_group');
                $objGroup->next();
            }

            $this->objTemplate->parse('downloads_category_group_list');
        } else {
            $this->objTemplate->hideBlock('downloads_category_group_list');
        }
    }


    private function parseCrumbtrail($objParentCategory)
    {
        global $_ARRAYLANG;

        if (!$this->objTemplate->blockExists('downloads_crumbtrail')) {
            return;
        }

        $arrCategories = array();

        do {
            $arrCategories[] = array(
                'id'    => $objParentCategory->getId(),
                'name'  => htmlentities($objParentCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET)
            );
            $objParentCategory = Category::getCategory($objParentCategory->getParentId());
        } while ($objParentCategory->getId());

        krsort($arrCategories);

        foreach ($arrCategories as $arrCategory) {
            $this->objTemplate->setVariable(array(
                'DOWNLOADS_CRUMB_ID'    => $arrCategory['id'],
                'DOWNLOADS_CRUMB_NAME'  => $arrCategory['name']
            ));
            $this->objTemplate->parse('downloads_crumb');
        }

        $this->objTemplate->setVariable('TXT_DOWNLOADS_START', $_ARRAYLANG['TXT_DOWNLOADS_START']);

        $this->objTemplate->parse('downloads_crumbtrail');
    }


    private function parseGlobalStuff($objCategory)
    {
        $this->objTemplate->setVariable(array(
            'DOWNLOADS_JS'  => $this->getJavaScriptCode($objCategory)
        ));

        $this->parseSearchForm($objCategory);
    }


    private function getJavaScriptCode($objCategory)
    {
        global $_ARRAYLANG;

        $fileDeleteTxt = preg_replace('#\n#', '\\n', addslashes($_ARRAYLANG['TXT_DOWNLOADS_CONFIRM_DELETE_DOWNLOAD']));
        $fileDeleteLink = \Cx\Core\Csrf\Controller\Csrf::enhanceURI(CONTREXX_SCRIPT_PATH.$this->moduleParamsJs)
            .'&category='.$objCategory->getId().'&delete_file=';
        $categoryDeleteTxt = preg_replace('#\n#', '\\n', addslashes($_ARRAYLANG['TXT_DOWNLOADS_CONFIRM_DELETE_CATEGORY']));
        $categoryDeleteLink = \Cx\Core\Csrf\Controller\Csrf::enhanceURI(CONTREXX_SCRIPT_PATH.$this->moduleParamsJs)
            .'&category='.$objCategory->getId().'&delete_category=';

        $javascript = <<<JS_CODE
<script type="text/javascript">
// <![CDATA[
function downloadsDeleteFile(id,name)
{
    msg = '$fileDeleteTxt'
    if (confirm(msg.replace('%s',name))) {
        window.location.href='$fileDeleteLink'+id;
    }
}

function downloadsDeleteCategory(id,name)
{
    msg = '$categoryDeleteTxt'
    if (confirm(msg.replace('%s',name))) {
        window.location.href='$categoryDeleteLink'+id;
    }
}

// ]]>
</script>
JS_CODE;

        return $javascript;
    }


    public function getPageTitle()
    {
        return $this->pageTitle;
    }

    /**
     * Get meta keywords
     *
     * @return string
     */
    public function getMetaKeywords()
    {
        return $this->metaKeys;
    }

    private function parseCategories($objCategory, $arrCategoryBlocks, $categoryLimit = null, $variablePrefix = '', $rowBlock = null, $arrSubCategoryBlocks = null, $subCategoryLimit = null, $subPrefix = '')
    {
        global $_ARRAYLANG;

        if (!$this->objTemplate->blockExists($arrCategoryBlocks[0])) {
            return;
        }

        $allowDeleteCategories = !$objCategory->getManageSubcategoriesAccessId()
                            || \Permission::checkAccess($objCategory->getManageSubcategoriesAccessId(), 'dynamic', true)
                            || $objCategory->getOwnerId() == $this->userId;
        $sortOrder = $this->fetchSortOrderFromTemplate(
            $arrCategoryBlocks[0],
            $this->objTemplate,
            $this->arrConfig['categories_sorting_order'],
            $this->categoriesSortingOptions
        );

        $objSubcategory = Category::getCategories(array('parent_id' => $objCategory->getId(), 'is_active' => true), null, $sortOrder, null, $categoryLimit);

        if ($objSubcategory->EOF) {
            $this->objTemplate->hideBlock($arrCategoryBlocks[0]);
        } else {
            $row = 1;
            while (!$objSubcategory->EOF) {
                // set category attributes
                $this->parseCategoryAttributes($objSubcategory, $row++, $variablePrefix, $allowDeleteCategories);

                // parse subcategories
                if (isset($arrSubCategoryBlocks)) {
                    $this->parseCategories(
                        $objSubcategory,
                        array(
                            'downloads_overview_subcategory_list',
                            'downloads_overview_subcategory'
                        ),
                        $subCategoryLimit,
                        'SUB',
                        null,
                        null,
                        null,
                        'OVERVIEW_'
                    );
                }
                $this->parseDownloads(
                    $objSubcategory,
                    $subPrefix . 'SUBCATEGORY_'
                );

                // parse category
                $this->objTemplate->parse($arrCategoryBlocks[1]);

                // parse row
                if (isset($rowBlock) && $this->objTemplate->blockExists($rowBlock) && $row % $this->arrConfig['overview_cols_count'] == 0) {
                    $this->objTemplate->parse($rowBlock);
                }

                $objSubcategory->next();
            }

            $this->objTemplate->setVariable(array(
                'TXT_DOWNLOADS_CATEGORIES'  => $_ARRAYLANG['TXT_DOWNLOADS_CATEGORIES'],
                'TXT_DOWNLOADS_DIRECTORIES' => $_ARRAYLANG['TXT_DOWNLOADS_DIRECTORIES']
            ));
            $this->objTemplate->parse($arrCategoryBlocks[0]);
        }
    }


    private function parseRelatedCategories($objDownload, $variablePrefix = '')
    {
        global $_ARRAYLANG;

        if (!$this->objTemplate->blockExists('downloads_' . strtolower($variablePrefix) . 'file_category_list')) {
            return;
        }

        $arrCategoryIds = $objDownload->getAssociatedCategoryIds();
        if (count($arrCategoryIds)) {
            $row = 1;
            foreach ($arrCategoryIds as $categoryId) {
                $objCategory = Category::getCategory($categoryId);

                if (!$objCategory->EOF) {
                    // set category attributes
                    $this->parseCategoryAttributes($objCategory, $row++, $variablePrefix . 'FILE_');

                    // parse category
                    $this->objTemplate->parse('downloads_' . strtolower($variablePrefix) . 'file_category');
                }
            }

            $this->objTemplate->setVariable('TXT_DOWNLOADS_' . $variablePrefix . 'RELATED_CATEGORIES', $_ARRAYLANG['TXT_DOWNLOADS_RELATED_CATEGORIES']);
            $this->objTemplate->parse('downloads_' . strtolower($variablePrefix) . 'file_category_list');
        } else {
            $this->objTemplate->hideBlock('downloads_' . strtolower($variablePrefix) . 'file_category_list');
        }
    }


    private function parseCategoryAttributes($objCategory, $row, $variablePrefix, $allowDeleteCategory = false)
    {
        global $_ARRAYLANG;

        $description = $objCategory->getDescription();
        if (strlen($description) > 100) {
            $shortDescription = substr($description, 0, 97).'...';
        } else {
            $shortDescription = $description;
        }

        $imageSrc = $objCategory->getImage();
        if (!empty($imageSrc) && file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteDocumentRootPath().$imageSrc)) {
            $thumb_name = \ImageManager::getThumbnailFilename($imageSrc);
            if (file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteDocumentRootPath().$thumb_name)) {
                $thumbnailSrc = $thumb_name;
            } else {
                $thumbnailSrc = \ImageManager::getThumbnailFilename(
                    $this->defaultCategoryImage['src']);
            }
        } else {
            $imageSrc = $this->defaultCategoryImage['src'];
            $thumbnailSrc = \ImageManager::getThumbnailFilename(
                $this->defaultCategoryImage['src']);
        }

        // parse delete icon link
        if ($allowDeleteCategory || $objCategory->getOwnerId() == $this->userId && $objCategory->getDeletableByOwner()) {
            $deleteIcon = $this->getHtmlDeleteLinkIcon(
                $objCategory->getId(),
                htmlspecialchars(str_replace("'", "\\'", $objCategory->getName()), ENT_QUOTES, CONTREXX_CHARSET),
                'downloadsDeleteCategory'
            );
        } else {
            $deleteIcon = '';
        }

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_ID'                 => $objCategory->getId(),
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_NAME'               => htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET),
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_NAME_LINK'          => $this->getHtmlLinkTag(CONTREXX_SCRIPT_PATH . $this->moduleParamsHtml . '&amp;category=' . $objCategory->getId(), sprintf($_ARRAYLANG['TXT_DOWNLOADS_SHOW_CATEGORY_CONTENT'], htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET)), htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET)),
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_FOLDER_LINK'        => $this->getHtmlFolderLinkTag(CONTREXX_SCRIPT_PATH . $this->moduleParamsHtml . '&amp;category=' . $objCategory->getId(), sprintf($_ARRAYLANG['TXT_DOWNLOADS_SHOW_CATEGORY_CONTENT'], htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET)), htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET)),
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_DESCRIPTION'        => nl2br(htmlentities($description, ENT_QUOTES, CONTREXX_CHARSET)),
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_SHORT_DESCRIPTION'  => htmlentities($shortDescription, ENT_QUOTES, CONTREXX_CHARSET),
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_IMAGE'              => $this->getHtmlImageTag($imageSrc, htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET)),
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_IMAGE_SRC'          => $imageSrc,
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_THUMBNAIL'          => $this->getHtmlImageTag($thumbnailSrc, htmlentities($objCategory->getName(), ENT_QUOTES, CONTREXX_CHARSET)),
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_THUMBNAIL_SRC'      => $thumbnailSrc,
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_DOWNLOADS_COUNT'    => intval($objCategory->getAssociatedDownloadsCount($this->arrConfig['list_downloads_current_lang'])),
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_DELETE_ICON'        => $deleteIcon,
            'DOWNLOADS_'.$variablePrefix.'CATEGORY_ROW_CLASS'          => 'row'.($row % 2 + 1),
            'TXT_DOWNLOADS_MORE'                                       => $_ARRAYLANG['TXT_DOWNLOADS_MORE']
        ));
    }


    private function getHtmlDeleteLinkIcon($id, $name, $method)
    {
        global $_ARRAYLANG;

        return sprintf($this->htmlLinkTemplate, "javascript:void(0)\" onclick=\"$method($id,'$name')", $_ARRAYLANG['TXT_DOWNLOADS_DELETE'], sprintf($this->htmlImgTemplate, 'core/Core/View/Media/icons/delete.gif', $_ARRAYLANG['TXT_DOWNLOADS_DELETE']));
    }


    private function getHtmlLinkTag($href, $title, $value)
    {
        return sprintf($this->htmlLinkTemplate, $href, $title, $value);
    }


    private function getHtmlImageTag($src, $alt)
    {
        return sprintf($this->htmlImgTemplate, $src, $alt);
    }


    private function getHtmlFolderLinkTag($href, $title, $value)
    {
        return sprintf($this->htmlLinkTemplate, $href, $title, sprintf($this->htmlImgTemplate, 'modules/Downloads/View/Media/folder_front.gif', $title).' '.$value);
    }


    private function parseDownloads($objCategory, $variablePrefix = '')
    {
        global $_CONFIG, $_ARRAYLANG;

        if (!$this->objTemplate->blockExists('downloads_' . strtolower($variablePrefix) . 'file_list')) {
            return;
        }

        $limitOffset = $this->isRegularMode && isset($_GET['pos']) ? intval($_GET['pos']) : 0;
        $includeDownloadsOfSubcategories = false;

        // set downloads filter
        $filter = array(
            'expiration'    => array('=' => 0, '>' => time())
        );
        if ($objCategory->getId()) {
            $filter['category_id'] = $objCategory->getId();

            if (!empty($this->searchKeyword)) {
                $includeDownloadsOfSubcategories = true;
            }
        }

        $objDownload = new Download($this->arrConfig);
        $sortOrder = $this->fetchSortOrderFromTemplate(
            'downloads_' . strtolower($variablePrefix) . 'file_list',
            $this->objTemplate,
            $this->arrConfig['downloads_sorting_order'],
            $this->downloadsSortingOptions
        );
        $pagingLimit = $this->fetchPagingLimitFromTemplate(
            'downloads_' . strtolower($variablePrefix) . 'file_list',
            $this->objTemplate,
            $_CONFIG['corePagingLimit']
        );
        $objDownload->loadDownloads(
            $filter,
            $this->searchKeyword,
            $sortOrder,
            null,
            $pagingLimit,
            $limitOffset,
            $includeDownloadsOfSubcategories,
            $this->arrConfig['list_downloads_current_lang']
        );
        $categoryId = $objCategory->getId();
        $allowdDeleteFiles = false;
        if (!$objCategory->EOF) {
            $allowdDeleteFiles =    !$objCategory->getManageFilesAccessId()
                                 || \Permission::checkAccess($objCategory->getManageFilesAccessId(), 'dynamic', true)
                                 || (   $this->userId
                                     && $objCategory->getOwnerId() == $this->userId);
        } elseif (\Permission::hasAllAccess()) {
            $allowdDeleteFiles = true;
        }

        if ($objDownload->EOF) {
            $this->objTemplate->hideBlock('downloads_' . strtolower($variablePrefix) . 'file_list');
        } else {
            $row = 1;
            while (!$objDownload->EOF) {
                // select category
                if ($objCategory->EOF) {
                    $arrAssociatedCategories = $objDownload->getAssociatedCategoryIds();
                    $categoryId = $arrAssociatedCategories[0];
                }


                // parse download info
                $this->parseDownloadAttributes($objDownload, $categoryId, $allowdDeleteFiles, $variablePrefix);

                // parse associated categories (but only for search mode)
                if (!empty($this->searchKeyword)) {
                    $this->parseRelatedCategories($objDownload, $variablePrefix . 'SEARCH_');
                }

                $this->objTemplate->setVariable('DOWNLOADS_' . $variablePrefix .'FILE_ROW_CLASS', 'row'.($row++ % 2 + 1));
                $this->objTemplate->parse('downloads_' . strtolower($variablePrefix) . 'file');


                $objDownload->next();
            }

            $downloadCount = $objDownload->getFilteredSearchDownloadCount();
            if (
                $pagingLimit &&
                $downloadCount > $pagingLimit
            ) {
                if($this->requestedPage->getModule() != 'Downloads'){
                    $this->objTemplate->setVariable('DOWNLOADS_' . $variablePrefix .'FILE_PAGING', getPaging($downloadCount, $limitOffset, '', "<b>".$_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS']."</b>", false, $pagingLimit));
                }else{
                    $this->objTemplate->setVariable('DOWNLOADS_' . $variablePrefix .'FILE_PAGING', getPaging($downloadCount, $limitOffset, '&'.substr($this->moduleParamsHtml, 1).'&category='.$objCategory->getId().'&downloads_search_keyword='.htmlspecialchars($this->searchKeyword), "<b>".$_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS']."</b>", false, $pagingLimit));
                }
            }

            $this->objTemplate->setVariable(array(
                'TXT_DOWNLOADS_' . $variablePrefix .'FILES'       => $_ARRAYLANG['TXT_DOWNLOADS_FILES'],
                'TXT_DOWNLOADS_' . $variablePrefix .'DOWNLOADS'   => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADS']
            ));

            // The following language-placeholder is available in template
            // block downloads_file_list as well as in downloads_file.
            // As a result of that, we must only parse it in downloads_file_list
            // in case the placeholder is actually in use in the template.
            $downloadsTxtKey = 'TXT_DOWNLOADS_' . $variablePrefix .'DOWNLOAD';
            $placeholders = $this->objTemplate->getPlaceholderList('downloads_' . strtolower($variablePrefix) . 'file_list');
            if (in_array($downloadsTxtKey, $placeholders)) {
                $this->objTemplate->setVariable($downloadsTxtKey, $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOAD']);
            }

            $this->objTemplate->parse('downloads_' . strtolower($variablePrefix) . 'file_list');
        }
    }


    private function parseSpecialDownloads($arrBlocks, $arrFilter, $arrSort, $limit)
    {
        global $_ARRAYLANG;

        if (!$this->objTemplate->blockExists($arrBlocks[0])) {
            return;
        }

        $objDownload = new Download($this->arrConfig);
        $objDownload->loadDownloads(
            $arrFilter,
            null,
            $arrSort,
            null,
            $limit,
            null,
            false,
            $this->arrConfig['list_downloads_current_lang']
        );

        if ($objDownload->EOF) {
            $this->objTemplate->hideBlock($arrBlocks[0]);
        } else {
            $row = 1;
            while (!$objDownload->EOF) {
                // select category
                $arrAssociatedCategories = $objDownload->getAssociatedCategoryIds();
                $categoryId = $arrAssociatedCategories[0];

                // parse download info
                $this->parseDownloadAttributes($objDownload, $categoryId);
                $this->objTemplate->setVariable('DOWNLOADS_FILE_ROW_CLASS', 'row'.($row++ % 2 + 1));
                $this->objTemplate->parse($arrBlocks[1]);

                $objDownload->next();
            }

            $this->objTemplate->setVariable(array(
                'TXT_DOWNLOADS_MOST_VIEWED'         => $_ARRAYLANG['TXT_DOWNLOADS_MOST_VIEWED'],
                'TXT_DOWNLOADS_MOST_DOWNLOADED'     => $_ARRAYLANG['TXT_DOWNLOADS_MOST_DOWNLOADED'],
                'TXT_DOWNLOADS_NEW_DOWNLOADS'       => $_ARRAYLANG['TXT_DOWNLOADS_NEW_DOWNLOADS'],
                'TXT_DOWNLOADS_RECENTLY_UPDATED'    => $_ARRAYLANG['TXT_DOWNLOADS_RECENTLY_UPDATED']
            ));

            $this->objTemplate->touchBlock($arrBlocks[0]);
        }
    }


    private function parseDownloadAttributes($objDownload, $categoryId, $allowDeleteFilesFromCategory = false, $variablePrefix = '')
    {
        global $_ARRAYLANG, $_LANGID;

        $description = $objDownload->getDescription($_LANGID);
        $shortDescription = $objDownload->getTrimmedDescription($_LANGID);

        $imageSrc = $objDownload->getImage();
        if (!empty($imageSrc) && file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteDocumentRootPath().'/'.$imageSrc)) {
            $thumb_name = \ImageManager::getThumbnailFilename($imageSrc);
            if (file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteDocumentRootPath().'/'.$thumb_name)) {
                $thumbnailSrc = $thumb_name;
            } else {
                $thumbnailSrc = \ImageManager::getThumbnailFilename(
                    $this->defaultCategoryImage['src']);
            }

            $imageSrc = contrexx_raw2encodedUrl($imageSrc);
            $thumbnailSrc = contrexx_raw2encodedUrl($thumbnailSrc);
            $image = $this->getHtmlImageTag($imageSrc, htmlentities($objDownload->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET));
            $thumbnail = $this->getHtmlImageTag($thumbnailSrc, htmlentities($objDownload->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET));
        } else {
            $imageSrc = contrexx_raw2encodedUrl($this->defaultCategoryImage['src']);
            $thumbnailSrc = contrexx_raw2encodedUrl(
                \ImageManager::getThumbnailFilename(
                    $this->defaultCategoryImage['src']));
            $image = $this->getHtmlImageTag($this->defaultCategoryImage['src'], htmlentities($objDownload->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET));;
            $thumbnail = $this->getHtmlImageTag(
                \ImageManager::getThumbnailFilename(
                    $this->defaultCategoryImage['src']),
                htmlentities($objDownload->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET));
        }

        // parse delete icon link
        if ($allowDeleteFilesFromCategory || ($this->userId && $objDownload->getOwnerId() == $this->userId)) {
            $deleteIcon = $this->getHtmlDeleteLinkIcon(
                $objDownload->getId(),
                htmlspecialchars(str_replace("'", "\\'", $objDownload->getName($_LANGID)), ENT_QUOTES, CONTREXX_CHARSET),
                'downloadsDeleteFile'
            );
        } else {
            $deleteIcon = '';
        }

        $this->objTemplate->setVariable(array(
            'TXT_DOWNLOADS_'.$variablePrefix.'DOWNLOAD'            => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOAD'],
            'TXT_DOWNLOADS_'.$variablePrefix.'ADDED_BY'            => $_ARRAYLANG['TXT_DOWNLOADS_ADDED_BY'],
            'TXT_DOWNLOADS_'.$variablePrefix.'LAST_UPDATED'        => $_ARRAYLANG['TXT_DOWNLOADS_LAST_UPDATED'],
            'TXT_DOWNLOADS_'.$variablePrefix.'DOWNLOADED'          => $_ARRAYLANG['TXT_DOWNLOADS_DOWNLOADED'],
            'TXT_DOWNLOADS_'.$variablePrefix.'VIEWED'              => $_ARRAYLANG['TXT_DOWNLOADS_VIEWED'],
            'DOWNLOADS_'.$variablePrefix.'FILE_ID'                 => $objDownload->getId(),
            'DOWNLOADS_'.$variablePrefix.'FILE_DETAIL_SRC'         => CONTREXX_SCRIPT_PATH . $this->moduleParamsHtml . '&amp;category=' . $categoryId . '&amp;id=' . $objDownload->getId(),
            'DOWNLOADS_'.$variablePrefix.'FILE_NAME'               => htmlentities($objDownload->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET),
            'DOWNLOADS_'.$variablePrefix.'FILE_DESCRIPTION'        => nl2br(htmlentities($description, ENT_QUOTES, CONTREXX_CHARSET)),
            'DOWNLOADS_'.$variablePrefix.'FILE_SHORT_DESCRIPTION'  => htmlentities($shortDescription, ENT_QUOTES, CONTREXX_CHARSET),
            'DOWNLOADS_'.$variablePrefix.'FILE_IMAGE'              => $image,
            'DOWNLOADS_'.$variablePrefix.'FILE_IMAGE_SRC'          => $imageSrc,
            'DOWNLOADS_'.$variablePrefix.'FILE_THUMBNAIL'          => $thumbnail,
            'DOWNLOADS_'.$variablePrefix.'FILE_THUMBNAIL_SRC'      => $thumbnailSrc,
            'DOWNLOADS_'.$variablePrefix.'FILE_ICON'               => $this->getHtmlImageTag($objDownload->getIcon(), htmlentities($objDownload->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET)),
            'DOWNLOADS_'.$variablePrefix.'FILE_MIME_TYPE'          => $objDownload->getMimeType(),
            'DOWNLOADS_'.$variablePrefix.'FILE_FILE_TYPE_ICON'     => $this->getHtmlImageTag($objDownload->getFileIcon(), htmlentities($objDownload->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET)),
            'DOWNLOADS_'.$variablePrefix.'FILE_DELETE_ICON'        => $deleteIcon,
            'DOWNLOADS_'.$variablePrefix.'FILE_DOWNLOAD_LINK_SRC'  => CONTREXX_SCRIPT_PATH . $this->moduleParamsHtml . '&amp;download=' . $objDownload->getId(),
            'DOWNLOADS_'.$variablePrefix.'FILE_DOWNLOAD_LINK_SRC_INLINE'=> CONTREXX_SCRIPT_PATH . $this->moduleParamsHtml . '&amp;download=' . $objDownload->getId() . '&amp;disposition=' . HTTP_DOWNLOAD_INLINE,
            'DOWNLOADS_'.$variablePrefix.'FILE_OWNER'              => contrexx_raw2xhtml($this->getParsedUsername($objDownload->getOwnerId())),
            'DOWNLOADS_'.$variablePrefix.'FILE_OWNER_ID'           => $objDownload->getOwnerId(),
            'DOWNLOADS_'.$variablePrefix.'FILE_SRC'                => htmlentities($objDownload->getSourceName(), ENT_QUOTES, CONTREXX_CHARSET),
            'DOWNLOADS_'.$variablePrefix.'FILE_LAST_UPDATED'       => date(ASCMS_DATE_FORMAT, $objDownload->getMTime()),
            'DOWNLOADS_'.$variablePrefix.'FILE_VIEWS'              => $objDownload->getViewCount(),
            'DOWNLOADS_'.$variablePrefix.'FILE_DOWNLOAD_COUNT'     => $objDownload->getDownloadCount()
        ));

        // parse size
        if ($this->arrConfig['use_attr_size']) {
            $this->objTemplate->setVariable(array(
                'TXT_DOWNLOADS_'.$variablePrefix.'SIZE'                => $_ARRAYLANG['TXT_DOWNLOADS_SIZE'],
                'DOWNLOADS_'.$variablePrefix.'FILE_SIZE'               => $this->getFormatedFileSize($objDownload->getSize())
            ));
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'size_information')) {
                $this->objTemplate->touchBlock('download_' . strtolower($variablePrefix) . 'size_information');
            }
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'size_list')) {
                $this->objTemplate->touchBlock('download_' . strtolower($variablePrefix) . 'size_list');
            }
        } else {
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'size_information')) {
                $this->objTemplate->hideBlock('download_' . strtolower($variablePrefix) . 'size_information');
            }
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'size_list')) {
                $this->objTemplate->hideBlock('download_' . strtolower($variablePrefix) . 'size_list');
            }
        }

        // parse license
        if ($this->arrConfig['use_attr_license']) {
            $this->objTemplate->setVariable(array(
                'TXT_DOWNLOADS_'.$variablePrefix.'LICENSE'             => $_ARRAYLANG['TXT_DOWNLOADS_LICENSE'],
                'DOWNLOADS_'.$variablePrefix.'FILE_LICENSE'            => htmlentities($objDownload->getLicense(), ENT_QUOTES, CONTREXX_CHARSET),
            ));
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'license_information')) {
                $this->objTemplate->touchBlock('download_' . strtolower($variablePrefix) . 'license_information');
            }
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'license_list')) {
                $this->objTemplate->touchBlock('download_' . strtolower($variablePrefix) . 'license_list');
            }
        } else {
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'license_information')) {
                $this->objTemplate->hideBlock('download_' . strtolower($variablePrefix) . 'license_information');
            }
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'license_list')) {
                $this->objTemplate->hideBlock('download_' . strtolower($variablePrefix) . 'license_list');
            }
        }

        // parse version
        if ($this->arrConfig['use_attr_version']) {
            $this->objTemplate->setVariable(array(
                'TXT_DOWNLOADS_'.$variablePrefix.'VERSION'             => $_ARRAYLANG['TXT_DOWNLOADS_VERSION'],
                'DOWNLOADS_'.$variablePrefix.'FILE_VERSION'            => htmlentities($objDownload->getVersion(), ENT_QUOTES, CONTREXX_CHARSET),
            ));
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'version_information')) {
                $this->objTemplate->touchBlock('download_' . strtolower($variablePrefix) . 'version_information');
            }
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'version_list')) {
                $this->objTemplate->touchBlock('download_' . strtolower($variablePrefix) . 'version_list');
            }
        } else {
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'version_information')) {
                $this->objTemplate->hideBlock('download_' . strtolower($variablePrefix) . 'version_information');
            }
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'version_list')) {
                $this->objTemplate->hideBlock('download_' . strtolower($variablePrefix) . 'version_list');
            }
        }

        // parse author
        if ($this->arrConfig['use_attr_author']) {
            $this->objTemplate->setVariable(array(
                'TXT_DOWNLOADS_'.$variablePrefix.'AUTHOR'              => $_ARRAYLANG['TXT_DOWNLOADS_AUTHOR'],
                'DOWNLOADS_'.$variablePrefix.'FILE_AUTHOR'             => htmlentities($objDownload->getAuthor(), ENT_QUOTES, CONTREXX_CHARSET),
            ));
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'author_information')) {
                $this->objTemplate->touchBlock('download_' . strtolower($variablePrefix) . 'author_information');
            }
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'author_list')) {
                $this->objTemplate->touchBlock('download_' . strtolower($variablePrefix) . 'author_list');
            }
        } else {
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'author_information')) {
                $this->objTemplate->hideBlock('download_' . strtolower($variablePrefix) . 'author_information');
            }
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'author_list')) {
                $this->objTemplate->hideBlock('download_' . strtolower($variablePrefix) . 'author_list');
            }
        }

        // parse website
        if ($this->arrConfig['use_attr_website']) {
            $this->objTemplate->setVariable(array(
                'TXT_DOWNLOADS_'.$variablePrefix.'WEBSITE'             => $_ARRAYLANG['TXT_DOWNLOADS_WEBSITE'],
                'DOWNLOADS_'.$variablePrefix.'FILE_WEBSITE'            => $this->getHtmlLinkTag(htmlentities($objDownload->getWebsite(), ENT_QUOTES, CONTREXX_CHARSET), htmlentities($objDownload->getWebsite(), ENT_QUOTES, CONTREXX_CHARSET), htmlentities($objDownload->getWebsite(), ENT_QUOTES, CONTREXX_CHARSET)),
                'DOWNLOADS_'.$variablePrefix.'FILE_WEBSITE_SRC'        => htmlentities($objDownload->getWebsite(), ENT_QUOTES, CONTREXX_CHARSET),
            ));
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'website_information')) {
                $this->objTemplate->touchBlock('download_' . strtolower($variablePrefix) . 'website_information');
            }
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'website_list')) {
                $this->objTemplate->touchBlock('download_' . strtolower($variablePrefix) . 'website_list');
            }
        } else {
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'website_information')) {
                $this->objTemplate->hideBlock('download_' . strtolower($variablePrefix) . 'website_information');
            }
            if ($this->objTemplate->blockExists('download_' . strtolower($variablePrefix) . 'website_list')) {
                $this->objTemplate->hideBlock('download_' . strtolower($variablePrefix) . 'website_list');
            }
        }
    }

    /**
     * Parse a related downloads
     *
     * @param \Cx\Modules\Downloads\Controller\Download $objDownload        Download object
     * @param integer                                   $currentCategoryId  Category ID
     */
    private function parseRelatedDownloads($objDownload, $currentCategoryId)
    {
        global $_LANGID, $_ARRAYLANG;

        if (!$this->objTemplate->blockExists('downloads_related_file_list')) {
            return;
        }

        $sortOrder = $this->fetchSortOrderFromTemplate(
            'downloads_related_file_list',
            $this->objTemplate,
            $this->arrConfig['downloads_sorting_order'],
            $this->downloadsSortingOptions
        );
        $objRelatedDownload =
            $objDownload->getDownloads(
                array('download_id' => $objDownload->getId()),
                null,
                $sortOrder,
                null,
                null,
                null,
                $this->arrConfig['list_downloads_current_lang']
            );

        if ($objRelatedDownload) {
            $row = 1;
            while (!$objRelatedDownload->EOF) {
                $description = $objRelatedDownload->getDescription($_LANGID);
                $shortDescription = $objRelatedDownload->getTrimmedDescription($_LANGID);

                $imageSrc = $objRelatedDownload->getImage();
                if (!empty($imageSrc) && file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteDocumentRootPath().$imageSrc)) {
                    $thumb_name = \ImageManager::getThumbnailFilename($imageSrc);
                    if (file_exists(\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteDocumentRootPath().$thumb_name)) {
                        $thumbnailSrc = $thumb_name;
                    } else {
                        $thumbnailSrc = \ImageManager::getThumbnailFilename(
                            $this->defaultCategoryImage['src']);
                    }

                    $image = $this->getHtmlImageTag($imageSrc, htmlentities($objRelatedDownload->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET));
                    $thumbnail = $this->getHtmlImageTag($thumbnailSrc, htmlentities($objRelatedDownload->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET));
                } else {
                    $imageSrc = $this->defaultCategoryImage['src'];
                    $thumbnailSrc = \ImageManager::getThumbnailFilename(
                        $this->defaultCategoryImage['src']);
                    $image = '';
                    $thumbnail = '';
                }

                $arrAssociatedCategories = $objRelatedDownload->getAssociatedCategoryIds();
                if (in_array($currentCategoryId, $arrAssociatedCategories)) {
                    $categoryId = $currentCategoryId;
                } else {
                    $arrPublicCategories = array();
                    $arrProtectedCategories = array();

                    foreach ($arrAssociatedCategories as $categoryId) {
                        $objCategory = Category::getCategory($categoryId);
                        if (!$objCategory->EOF) {
                            if ($objCategory->getVisibility()
                                || \Permission::checkAccess($objCategory->getReadAccessId(), 'dynamic', true)
                                || $objCategory->getOwnerId() == $this->userId
                               ) {
                                $arrPublicCategories[] = $categoryId;
                                break;
                            } else {
                                $arrProtectedCategories[] = $categoryId;
                            }
                        }
                    }

                    if (count($arrPublicCategories)) {
                        $categoryId = $arrPublicCategories[0];
                    } elseif (count($arrProtectedCategories)) {
                        $categoryId = $arrProtectedCategories[0];
                    } else {
                        $objRelatedDownload->next();
                        continue;
                    }
                }

                $this->objTemplate->setVariable(array(
                    'DOWNLOADS_RELATED_FILE_ID'                 => $objRelatedDownload->getId(),
                    'DOWNLOADS_RELATED_FILE_DETAIL_SRC'         => CONTREXX_SCRIPT_PATH . $this->moduleParamsHtml . '&amp;category=' . $categoryId . '&amp;id=' . $objRelatedDownload->getId(),
                    'DOWNLOADS_RELATED_FILE_NAME'               => htmlentities($objRelatedDownload->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET),
                    'DOWNLOADS_RELATED_FILE_DESCRIPTION'        => nl2br(htmlentities($description, ENT_QUOTES, CONTREXX_CHARSET)),
                    'DOWNLOADS_RELATED_FILE_SHORT_DESCRIPTION'  => htmlentities($shortDescription, ENT_QUOTES, CONTREXX_CHARSET),
                    'DOWNLOADS_RELATED_FILE_IMAGE'              => $image,
                    'DOWNLOADS_RELATED_FILE_IMAGE_SRC'          => $imageSrc,
                    'DOWNLOADS_RELATED_FILE_THUMBNAIL'          => $thumbnail,
                    'DOWNLOADS_RELATED_FILE_THUMBNAIL_SRC'      => $thumbnailSrc,
                    'DOWNLOADS_RELATED_FILE_ICON'               => $this->getHtmlImageTag($objRelatedDownload->getIcon(), htmlentities($objRelatedDownload->getName($_LANGID), ENT_QUOTES, CONTREXX_CHARSET)),
                    'DOWNLOADS_RELATED_FILE_ROW_CLASS'          => 'row'.($row++ % 2 + 1)
                ));
                $this->objTemplate->parse('downloads_related_file');


                $objRelatedDownload->next();
            }

            $this->objTemplate->setVariable('TXT_DOWNLOADS_RELATED_DOWNLOADS', $_ARRAYLANG['TXT_DOWNLOADS_RELATED_DOWNLOADS']);
            $this->objTemplate->parse('downloads_related_file_list');
        } else {
            $this->objTemplate->hideBlock('downloads_related_file_list');
        }
    }


    private function parseDownload($objDownload, $categoryId)
    {
        global $_LANGID, $_ARRAYLANG;

        if (!$this->objTemplate->blockExists('downloads_file_detail')) {
            return;
        }


        $this->parseDownloadAttributes($objDownload, $categoryId);
        $this->objTemplate->parse('downloads_file_detail');

        $objDownload->incrementViewCount();
    }


    private function parseSearchForm($objCategory)
    {
        global $_ARRAYLANG;

        $this->objTemplate->setVariable(array(
            'DOWNLOADS_SEARCH_KEYWORD'  => htmlentities($this->searchKeyword, ENT_QUOTES, CONTREXX_CHARSET),
            'DOWNLOADS_SEARCH_URL'      => CONTREXX_SCRIPT_PATH,
            'DOWNLOADS_SEARCH_CATEGORY' => $objCategory->getId(),
            'TXT_DOWNLOADS_SEARCH'      => $_ARRAYLANG['TXT_DOWNLOADS_SEARCH'],
        ));
    }


    private function download()
    {
        global $objInit;

        $objDownload = new Download($this->arrConfig);
        $id = !empty($_GET['download']) ? contrexx_input2int($_GET['download']) : 0;
        $objDownload->load($id, $this->arrConfig['list_downloads_current_lang']);
        if (!$objDownload->EOF) {
            // check if the download is expired
            if (
                (
                    $objDownload->getExpirationDate() &&
                    $objDownload->getExpirationDate() < time()
                ) ||
                !$objDownload->getActiveStatus()
            ) {
                \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_DIRECTORY_INDEX."?section=Error&id=404");
                exit;
            }

            // check access to download-file
            if (!$this->hasUserAccessToCategoriesOfDownload($objDownload)) {
                \Permission::noAccess(base64_encode($objInit->getPageUri()));
            }

            // check access to download-file
            if (// download is protected
                $objDownload->getAccessId()
                // the user isn't a admin
                && !\Permission::checkAccess(143, 'static', true)
                // the user doesn't has access to this download
                && !\Permission::checkAccess($objDownload->getAccessId(), 'dynamic', true)
                // the user isn't the owner of the download
                && $objDownload->getOwnerId() != $this->userId
            ) {
                \Permission::noAccess(base64_encode($objInit->getPageUri()));
            }

            $objDownload->incrementDownloadCount();

            if ($objDownload->getType() == 'file') {
                $disposition = HTTP_DOWNLOAD_ATTACHMENT;
                if (!empty($_GET['disposition'])) {
                    $disposition = contrexx_input2raw($_GET['disposition']);
                }

                $objDownload->send(
                    DownloadsLibrary::getOutputLocale()->getId(),
                    $disposition
                );
            } else {
                // add socket -> prevent to hide the source from the customer
                \Cx\Core\Csrf\Controller\Csrf::header('Location: '.$objDownload->getSource());
            }
        }
    }

    /**
     * Check if currently authenticated user has read access to any
     * of a perticular download's associated categories.
     *
     * @param   Download The download-object of which the access to its
     *                   associated categories shall be checked.
     * @return  boolean  Returns TRUE, if the currently authenticated user
     *                   has read access to at least one of the download's
     *                   associated categories.
     */
    public function hasUserAccessToCategoriesOfDownload($objDownload)
    {
        // user is DAM admin (or superuser)
        if (\Permission::checkAccess(143, 'static', true)) {
            return true;
        }

        $arrCategoryIds = $objDownload->getAssociatedCategoryIds();
        $filter = array(
            'is_active'     => true,
            // read_access_id = 0 refers to unprotected categories
            'read_access_id'=> array(0),
        );
        if (!empty($arrCategoryIds)) {
            $filter['id'] = $arrCategoryIds;
        }
        $objUser = \FWUser::getFWUserObject()->objUser;
        if ($objUser->login()) {
            $filter['read_access_id'] = array_merge($filter['read_access_id'], $objUser->getDynamicPermissionIds());
        }
        $objCategory = Category::getCategories($filter, null, null, null, $limit = 1);

        if (!$objCategory->EOF) {
            return true;
        }

        if ($objUser->login()) {
            // In case the user is logged in, but has no access to any of the
            // download's associated categories, check if any of those categories
            // are owned by the user. If so, we will grant the access to the download anyway.
            unset($filter['read_access_id']);
            $filter['owner_id'] = $objUser->getId();
            $objCategory = Category::getCategories($filter, null, null, null, $limit = 1);
            if (!$objCategory->EOF) {
                return true;
            }
        }

        return false;
    }


    private function getFormatedFileSize($bytes)
    {
        global $_ARRAYLANG;

        if (!$bytes) {
            return $_ARRAYLANG['TXT_DOWNLOADS_UNKNOWN'];
        }

        $exp = log($bytes, 1024);

        if ($exp < 1) {
            return $bytes.' '.$_ARRAYLANG['TXT_DOWNLOADS_BYTES'];
        } elseif ($exp < 2) {
            return round($bytes/1024, 2).' '.$_ARRAYLANG['TXT_DOWNLOADS_KBYTE'];
        } elseif ($exp < 3) {
            return round($bytes/pow(1024, 2), 2).' '.$_ARRAYLANG['TXT_DOWNLOADS_MBYTE'];
        } else {
            return round($bytes/pow(1024, 3), 2).' '.$_ARRAYLANG['TXT_DOWNLOADS_GBYTE'];
        }
    }

    /**
     * Identify functional placeholder in the block $block of template
     * $template that will determine the sort order of the downloads.
     * Placeholder can have the following form:
     * - DOWNLOADS_CONFIG_LIST_CUSTOM => Order by custom order
     * - DOWNLOADS_CONFIG_LIST_ALPHABETIC => Order alphabetically
     * - DOWNLOADS_CONFIG_LIST_NEWESTTOOLDEST => Order by latest
     * - DOWNLOADS_CONFIG_LIST_OLDESTTONEWEST => Order by oldest
     *
     * @param   string  $block Name of the template block to look up for
     *                         functional placeholder
     * @param   \Cx\Core\Html\Sigma $template   Template object where the block
     *                                          $block is located in
     * @param   string  $defaultSortOrder   Fallback sort order in case no
     *                                      functional placeholder can be
     *                                      located in the supplied template.
     * @param   array   List of available sort order definitions. Format:
     *                  <code>array = (
     *                      '<order_name>' => array(
     *                          '<field>' => '<direction>',
     *                          ...
     *                      ),
     *                      ...
     *                  )
     *                  </code>
     *                  Example:
     *                  <code>array(
     *                      'custom' => array(
     *                          'order' => 'ASC',
     *                          'name'  => 'ASC',
     *                          'id'    => 'ASC'
     *                      ),
     *                      'alphabetic' => array(
     *                          'name' => 'ASC',
     *                          'id'   => 'ASC'
     *                      ),
     *                  )</code>
     * @return  array   Identified sort oder. If no functional placeholder has
     *                  been found in the supplied template, then the default
     *                  sort order (defined by $defaultSortOrder) is returned.
     *                  The returned array is an element of $orderOptions.
     */
    protected function fetchSortOrderFromTemplate($block, $template, $defaultSortOrder, $orderOptions) {
        $placeholderList = $template->getPlaceholderList($block);
        $placeholderListAsString = join("\n", $placeholderList);

        $orderKeys = array_keys($orderOptions);
        $optionsRegex = join(
            '|',
            array_map('strtoupper', $orderKeys)
        );

        // check if functional placeholder exists in template
        if (
            !preg_match(
                '/DOWNLOADS_CONFIG_LIST_(' . $optionsRegex . ')/',
                $placeholderListAsString,
                $match
            )
        ) {
            // return default sort order as no functional placeholder
            // exists in template
            return $orderOptions[$defaultSortOrder];
        }

        // fetch case-sensitive writting of option
        $options = preg_grep(
            '/' . $match[1] . '/i',
            $orderKeys
        );

        // return identified sort order from functional placeholder
        // from template
        return $orderOptions[current($options)];
    }

    /**
     * Identify a functional placeholder in the block $block of template
     * $template that will determine a custom paging limit.
     * Placeholder can have the following form:
     * - DOWNLOADS_CONFIG_LIMIT_<limit>
     * Example:
     * - DOWNLOADS_CONFIG_LIMIT_3
     *
     * @param   string  $block Name of the template block to look up for
     *                         functional placeholder
     * @param   \Cx\Core\Html\Sigma $template   Template object where the block
     *                                          $block is located in
     * @param   integer $defaultPagingLimit Fallback paging limit in case no
     *                                      functional placeholder can be
     *                                      located in the supplied template.
     * @return  integer Identified paging limit. If no functional placeholder
     *                  has been found in the supplied template, then the
     *                  default paging limit (defined by $defaultPagingLimit)
     *                  is returned.
     */
    protected function fetchPagingLimitFromTemplate($block, $template, $defaultPagingLimit) {
        // abort in case the template is invalid
        if (!$template->blockExists($block)) {
            return $defaultPagingLimit;
        }

        $placeholderList = $template->getPlaceholderList($block);
        $placeholderListAsString = implode("\n", $placeholderList);
        $match = null;

        // abort in case the functional placeholder does not exist
        if (
            !preg_match(
                '/DOWNLOADS_CONFIG_LIMIT_([0-9]+)/',
                $placeholderListAsString,
                $match
            )
        ) {
            return $defaultPagingLimit;
        }

        // set custom identified paging limit
        return $match[1];
    }
}
