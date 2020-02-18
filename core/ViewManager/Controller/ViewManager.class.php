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
 * View Manager
 *
 * @package    cloudrexx
 * @subpackage core_viewmanager
 * @author     Cloudrexx Development Team <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @access     public
 * @version    3.1.1
 */

namespace Cx\Core\ViewManager\Controller;

/**
 * View Manager class
 * View Manager and Themes management functions
 *
 * @package    cloudrexx
 * @subpackage core_viewmanager
 * @author     Cloudrexx Development Team <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @access     public
 * @version    3.1.1
 */

class ViewManager
{
    /**
     * Title of the active page
     * @var string
     */
    public $pageTitle;

    /**
     * Error message
     * @var string
     */
    public $strErrMessage = '';

    /**
     * Success message
     * @var unknown_type
     */
    public $strOkMessage = '';

    /**
     * Temporary archive location, relative to the document root
     * @var string
     */
    public $_archiveTempWebPath;

    /**
     * Temporary archive location, absolute path
     * @var string
     */
    public $_archiveTempPath;

    /**
     * Name of the theme directory
     * @var string
     */
    public $_themeDir;

    /**
     * Required files
     * @var array
     */
    public $filenames = array(
        'index.html',
        'style.css',
        'content.html',
        'home.html',
        'navbar.html',
        'navbar2.html',
        'navbar3.html',
        'subnavbar.html',
        'subnavbar2.html',
        'subnavbar3.html',
        'sidebar.html',
        'shopnavbar.html',
        'shopnavbar2.html',
        'shopnavbar3.html',
        'headlines.html',
        'headlines2.html',
        'headlines3.html',
        'headlines4.html',
        'headlines5.html',
        'headlines6.html',
        'headlines7.html',
        'headlines8.html',
        'headlines9.html',
        'headlines10.html',
        'headlines11.html',
        'headlines12.html',
        'headlines13.html',
        'headlines14.html',
        'headlines15.html',
        'headlines16.html',
        'headlines17.html',
        'headlines18.html',
        'headlines19.html',
        'headlines20.html',
        'events.html',
        'events2.html',
        'events3.html',
        'events4.html',
        'events5.html',
        'events6.html',
        'events7.html',
        'events8.html',
        'events9.html',
        'events10.html',
        'events11.html',
        'events12.html',
        'events13.html',
        'events14.html',
        'events15.html',
        'events16.html',
        'events17.html',
        'events18.html',
        'events19.html',
        'events20.html',
        'javascript.js',
        'buildin_style.css',
        'directory.html',
        'component.yml',
        'forum.html',
        'podcast.html',
        'blog.html',
        'immo.html',
    );

    /**
     * Required directories
     * @var array
     */
    public $directories = array("images/",);

    /**
     * File extenstions to display in filelist
     * @var array
     */
    public $fileextensions = array("htm", "shtml", "html", "txt", "css", "js", "php", "java", "tpl", "xml",);

    /**
     * Subdirectores and contents of selected theme
     * @var array
     */
    public $subDirs = array();

    /**
     * Path to the parent directory of the theme
     * @var string
     */
    public $_parentPath = '';

    private $themeRepository;

    /**
     * @var \Cx\Core\Core\Controller\Cx
     */
    protected $cx;

    /**
     * @var \Cx\Core\ViewManager\Model\Entity\ViewManagerFileSystem
     */
    protected $fileSystem;

    public $arrWebPaths;                      // array web paths
    public $getAct;                           // $_GET['act']
    public $getPath;                          // $_GET['path']
    public $path;                             // current path
    public $webPath;                          // current web path
    public $websitePath;                      // website path
    public $websiteThemesPath;                // website themes path
    public $tableExists;                      // Table exists
    public $oldTable;                         // old Theme-Table name

    private $act = '';

    /**
     * The doctrine entity manager
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * Access id to view the viewManager section
     */
    const VIEW_MANAGER_ACCESS_ID = 21;

    /**
     * Access id to activate/deactivate themes
     */
    const ENABLE_THEMES_ACCESS_ID = 46;

    /**
     * Access id to add/edit themes
     */
    const EDIT_THEMES_ACCESS_ID = 47;

    /**
     * Access id to import and export themes
     */
    const THEMES_IMPORT_EXPORT_ACCESS_ID = 102;

    /**
     * Access id to use template editor
     */
    const TEMPLATE_EDITOR_ACCESS_ID = 204;

    function __construct()
    {
        global  $_ARRAYLANG;

        $this->cx         = \Cx\Core\Core\Controller\Cx::instanciate();
        $this->fileSystem = $this->cx->getMediaSourceManager()->getMediaType('themes')->getFileSystem();

        $this->em = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getDb()
            ->getEntityManager();
        //add preview.gif to required files
        $this->filenames[] = \Cx\Core\View\Model\Entity\Theme::THEME_PREVIEW_FILE;

        //get path variables
        $this->path                 = $this->cx->getWebsiteThemesPath() . '/';
        $this->arrWebPaths          = array($this->cx->getWebsiteThemesWebPath() . '/');
        $this->websitePath          = $this->cx->getWebsiteDocumentRootPath() . '/';
        $this->websiteThemesPath    = $this->cx->getWebsiteThemesPath() . '/';
        $this->themeZipPath         = '/themezips/';
        $this->_archiveTempWebPath  = $this->cx->getWebsiteTempWebPath() . $this->themeZipPath;
        $this->_archiveTempPath     = $this->cx->getWebsiteTempPath() . $this->themeZipPath;
        //create /tmp/zip path if it doesnt exists
        if (!file_exists($this->_archiveTempPath)) {
            if (!\Cx\Lib\FileSystem\FileSystem::make_folder($this->cx->getWebsiteTempPath() . $this->themeZipPath)) {
                $this->strErrMessage = $this->cx->getWebsiteTempPath() . $this->themeZipPath . ":" . $_ARRAYLANG['TXT_THEME_UNABLE_TO_CREATE'];
            }
        }
        $this->webPath = $this->arrWebPaths[0];
        if (substr($this->webPath, -1) != '/'){
            $this->webPath = $this->webPath . '/';
        }

        $this->oldTable = DBPREFIX."themes";

        $this->themeRepository = new \Cx\Core\View\Model\Repository\ThemeRepository();
        //\Cx\Lib\FileSystem\FileSystem::makeWritable($this->webPath);

        //define the Pclzip Temporary Directory
        if (!defined('PCLZIP_TEMPORARY_DIR')) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            define('PCLZIP_TEMPORARY_DIR', $cx->getComponent('Session')->getSession()->getTempPath() . '/');
        }
    }


    /**
     * checks whether this cloudrexx has the possibility to use multi language mode
     * @return bool is this cloudrexx in multi language mode
     */
    private function isInLanguageFullMode() {
        global $_CONFIG, $objDatabase;
        return \Cx\Core_Modules\License\License::getCached($_CONFIG, $objDatabase)->isInLegalComponents("fulllanguage");
    }

    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $navigation = '';
        if (\Permission::checkAccess(self::VIEW_MANAGER_ACCESS_ID, 'static', true)) {
            $navigation .= "<a href='index.php?cmd=ViewManager' class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_VIEWMANAGER_OVERVIEW']."</a>";
        }
        if (   \Permission::checkAccess(self::EDIT_THEMES_ACCESS_ID, 'static', true)
            || \Permission::checkAccess(self::THEMES_IMPORT_EXPORT_ACCESS_ID, 'static', true)
        ) {
            $navigation .= "<a href='index.php?cmd=ViewManager&amp;act=templates' class='".($this->act == 'templates' || $this->act == 'newDir' ? 'active' : '')."'>".$_ARRAYLANG['TXT_VIEWMANAGER_TEMPLATE_EDITOR']."</a>";
        }
        if (\Permission::checkAccess(self::ENABLE_THEMES_ACCESS_ID, 'static', true)) {
            $navigation .= "<a href='index.php?cmd=ViewManager&amp;act=settings' class='".($this->act == 'settings' ? 'active' : '')."'>".$_ARRAYLANG['TXT_DESIGN_SETTINGS']."</a>";
        }

        $objTemplate->setVariable("CONTENT_NAVIGATION", $navigation);
    }

    /**
     * Gets the requested page
     * @global    \Cx\Core\Html\Sigma
     * @return    string    parsed content
     */
    function getPage()
    {
        global $objTemplate;

        if (!isset($_GET['act'])) {
            $_GET['act']="";
        }
        switch($_GET['act']){
            case "templates":
                $this->overview();
                break;
            case "settings":
                $this->settings();
                break;
            case "upload":
                $this->upload();
                $this->overview();
                break;
            case "update":
                $this->update();
                $this->overview();
                break;
            case "newDir":
                $this->newdir();
                break;
            case "createDir":
                $this->createdir();
                break;
            case 'import':
                $this->import();
                break;
            default:
                $this->viewManager();
        }
        $objTemplate->setVariable(array(
            'CONTENT_TITLE'             => $this->pageTitle,
            'CONTENT_OK_MESSAGE'        => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->strErrMessage
        ));
        $this->act = (isset ($_REQUEST['act']) ? $_REQUEST['act'] : '');
        $this->setNavigation();
    }


    /**
     * Overview page of the view manager
     *
     * @global array $_ARRAYLANG
     * @global type $objTemplate
     * @global type $objDatabase
     */
    function viewManager() {
       global $_ARRAYLANG, $objTemplate, $objDatabase;

       \Permission::checkAccess(self::VIEW_MANAGER_ACCESS_ID, 'static');

       $objTemplate->addBlockfile('ADMIN_CONTENT', 'skins_overview', 'skins_overview.html');

       $subTypeArray = array(
          \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB,
          \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_MOBILE,
          \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_PRINT,
          \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_PDF,
          \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_APP
        );

        foreach ($subTypeArray as $subType) {
            $themes = $this->themeRepository->getThemesBySubType($subType);

            $themesCollection = array();
            foreach ($themes as $theme) {
                $themesCollection[$theme->getId()] = $theme;
            }

            //sort the themes by its release date
            uasort($themesCollection, array($this,'sortThemesByReleaseDate'));

            $frontEndActiveTemplates = array();
            foreach (\FWLanguage::getActiveFrontendLanguages() as $lang) {
                $tempTheme = $this->themeRepository->getDefaultTheme($subType, $lang['id']);
                if ($tempTheme) {
                    $frontEndActiveTemplates[$tempTheme->getId()] = $tempTheme;
                    unset($themesCollection[$tempTheme->getId()]);
                }
            }

            $themesCollection = array_merge($frontEndActiveTemplates, $themesCollection);

            foreach ($themesCollection as $theme) {
                $this->parseThemesData($theme, $subType);
            }
        }

        \ContrexxJavascript::getInstance()->setVariable(array(
            'view_manager_access'          => \Permission::checkAccess(self::VIEW_MANAGER_ACCESS_ID, 'static', true),
            'enable_theme_access'          => \Permission::checkAccess(self::ENABLE_THEMES_ACCESS_ID, 'static', true),
            'edit_theme_access'            => \Permission::checkAccess(self::EDIT_THEMES_ACCESS_ID, 'static', true),
            'theme_import_export_access'   => \Permission::checkAccess(self::THEMES_IMPORT_EXPORT_ACCESS_ID, 'static', true),
            'theme_template_editor_access' => \Permission::checkAccess(self::TEMPLATE_EDITOR_ACCESS_ID, 'static', true),
        ), 'viewManager');

       $objTemplate->setGlobalVariable(array(
            'TXT_DELETE'                         => $_ARRAYLANG['TXT_DELETE'],
            'TXT_EDIT'                           => $_ARRAYLANG['TXT_SETTINGS_MODFIY'],
            'TXT_THEME_PREVIEW'                  => $_ARRAYLANG['TXT_THEME_PREVIEW'],
            'TXT_THEME_ACTIVATE'                 => $_ARRAYLANG['TXT_THEME_ACTIVATE'],
            'TXT_THEME_CANCEL'                   => $_ARRAYLANG['TXT_THEME_CANCEL'],
            'TXT_THEME_ACTIVATE_THEME'           => $_ARRAYLANG['TXT_THEME_ACTIVATE_THEME'],
            'TXT_THEME_DELETE_DIALOG_TITLE'      => $_ARRAYLANG['TXT_THEME_DELETE_DIALOG_TITLE'],
            'TXT_THEME_ACTIVATION_LABEL'         => $_ARRAYLANG['TXT_THEME_ACTIVATION_LABEL'],
            'TXT_COPY'                           => $_ARRAYLANG['TXT_COPY'],
            'TXT_THEME_ADD_NEW'                  => $_ARRAYLANG['TXT_THEME_ADD_NEW'],
            'TXT_THEME_EXPORT'                   => $_ARRAYLANG['TXT_THEME_EXPORT'],
            'TXT_ACTIVE_TEMPLATE'                => $_ARRAYLANG['TXT_ACTIVE_TEMPLATE'],
            'TXT_ACTIVE_MOBILE_TEMPLATE'         => $_ARRAYLANG['TXT_ACTIVE_MOBILE_TEMPLATE'],
            'TXT_ACTIVE_PRINT_TEMPLATE'          => $_ARRAYLANG['TXT_ACTIVE_PRINT_TEMPLATE'],
            'TXT_ACTIVE_PDF_TEMPLATE'            => $_ARRAYLANG['TXT_ACTIVE_PDF_TEMPLATE'],
            'TXT_APP'                            => $_ARRAYLANG['TXT_APP'],
            'CONTREXX_BASE_URL'                  => \Env::get('cx')->getWebsiteOffsetPath() . '/',
            'THEMES_LANG_ACTIVE_COUNT'           => count(\FWLanguage::getActiveFrontendLanguages()),
            'CONTREXX_BACKEND_URL_LINK'          => \Env::get('cx')->getWebsiteBackendPath() . '/',
       ));
    }

    /**
     *
     * @global type $objTemplate
     * @param type $theme
     */
    private function parseThemesData($theme, $subType) {
        global $objTemplate,$_ARRAYLANG;

        $frontendLanguages = \FWLanguage::getActiveFrontendLanguages();

        $activeLanguages   = $theme->getLanguagesByType($subType);
        foreach ($activeLanguages  as $activeLanguage) {
            $objTemplate->setVariable(array(
                'THEME_ACTIVATED_LANG_CODE' => contrexx_raw2xhtml(strtoupper($activeLanguage))
            ));
            $objTemplate->parse('activatedLangCode'. ucfirst($subType));

            $objTemplate->setVariable(array(
                'TXT_THEME_STANDARD_DISPLAY'      => 'default_active'
            ));
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();

        $objTemplate->setVariable(array(
            'TXT_THEME_TEMPLATEEDITOR_USABLE'      => $_ARRAYLANG['TXT_THEME_TEMPLATEEDITOR_USABLE'],
            'TXT_THEME_TEMPLATEEDITOR_UNUSABLE'      => $_ARRAYLANG['TXT_THEME_TEMPLATEEDITOR_UNUSABLE']
        ));

        $supportForTemplateEditor = false;
        $optionsFile = new \Cx\Core\ViewManager\Model\Entity\ViewManagerFile($theme->getFoldername(). '/options/options.yml', $this->fileSystem);
        if ($this->fileSystem->fileExists($optionsFile)) {
            $supportForTemplateEditor = true;
        }

        $objTemplate->setVariable(
            array(
                'THEME_PREVIEW' => $theme->getPreviewImage(),
                'THEME_ID' => $theme->getId(),
                'THEME_ACTION' => $supportForTemplateEditor
                    ? 'cmd=TemplateEditor&tid=' . $theme->getId()
                    : 'cmd=ViewManager&act=templates&themes='
                    . $theme->getFoldername(),
                'THEME_FOLDER_NAME' => $theme->getFoldername(),
                'THEME_TEMPLATEEDITOR' => $supportForTemplateEditor
                    ? 'templateEditor' : '',
                'TXT_EDIT' => $supportForTemplateEditor
                    ? $_ARRAYLANG['TXT_THEME_TEMPLATEEDITOR_EDIT']
                    : $_ARRAYLANG['TXT_SETTINGS_MODFIY'],
                'THEME_ACTIVATE_DISABLED' => count($frontendLanguages) == count(
                    $activeLanguages
                ) ? 'disabled' : '',
                'THEME_NAME' => contrexx_raw2xhtml($theme->getThemesname()),
            )
        );

        $objTemplate->parse('themes'. ucfirst($subType));

    }

    /**
     * sorting the theme by its releaseDate
     *
     * @param string $a
     * @param string $b
     * @return string
     */
    function sortThemesByReleaseDate($a,$b) {
        $aDate = ($a->getReleasedDate()) ? $a->getReleasedDate()->getTimeStamp() : 0;
        $bDate = ($b->getReleasedDate()) ? $b->getReleasedDate()->getTimeStamp() : 0;

        return $bDate - $aDate;
    }

    /**
     * Settings section
     *
     * @global array $_ARRAYLANG
     * @global type $objTemplate
     */
    function settings() {
        global $_ARRAYLANG, $objTemplate;

        $objTemplate->addBlockfile('ADMIN_CONTENT', 'skins_settings', 'skins_settings.html');
        $tpl = isset($_REQUEST['tpl']) ? $_REQUEST['tpl'] : '';
        switch ($tpl) {
            case 'manage':
                $this->manage();
                break;
            case 'examples':
                $this->examples();
                break;
            default :
                $this->_activate();
        }

        $this->pageTitle = $_ARRAYLANG['TXT_DESIGN_SETTINGS'];
        $objTemplate->setVariable(array(
            'TXT_OVERVIEW'                         => $_ARRAYLANG['TXT_OVERVIEW'],
            'TXT_CORE_PLACEHOLDERS'                => $_ARRAYLANG['TXT_CORE_PLACEHOLDERS'],
            'TXT_THEME_IMPORT_EXPORT'              => $_ARRAYLANG['TXT_THEME_IMPORT_EXPORT']
        ));
    }

    /**
     * Show the Template Manager (advanced HTML/CSS/JS editor)
     */
    private function overview()
    {
        global $_ARRAYLANG, $objTemplate;

        if (   !\Permission::checkAccess(self::EDIT_THEMES_ACCESS_ID, 'static', true)
            && \Permission::checkAccess(self::THEMES_IMPORT_EXPORT_ACCESS_ID, 'static', true)
        ) {
            $this->import();
            return;
        }
        \Permission::checkAccess(self::EDIT_THEMES_ACCESS_ID, 'static');

        \JS::activate("cx");
        \JS::activate("jqueryui");
        \JS::activate('jstree');
        \JS::registerJS("lib/javascript/jquery.ui.tabs.js");
        \JS::registerJS('core/ViewManager/View/Script/Main.js');

        // initialize variables
        $objTemplate->addBlockfile('ADMIN_CONTENT', 'skins_content', 'skins_content.html');
        $this->pageTitle = $_ARRAYLANG['TXT_DESIGN_TEMPLATES'];

        $themes          = !empty($_REQUEST['themes']) && !stristr($_REQUEST['themes'], '..') ? contrexx_input2raw($_REQUEST['themes']) : '';
        $themeTab        = isset($_POST['selectedTab']) ? intval($_POST['selectedTab']) : 0;
        $themesPage      = isset($_POST['themesPage']) && !stristr($_REQUEST['themes'], '..') ? contrexx_input2raw($_POST['themesPage']) : '';
        $isComponentFile = ($themeTab == 1);

        $theme = null;
        if (isset($themes)) {
            $theme = $this->themeRepository->findOneBy(array('foldername' => $themes));
        }
        if (!$theme) {
            $theme = $this->themeRepository->getDefaultTheme();
        }

        if (isset($_POST['save_libraries'])) {
            // only save if the form has been fired
            $this->saveLibrarySettings($theme);
        }

        if ($theme->isComponent()) {
            // get library settings tab
            $this->getLibrarySettings($theme);
        } else {
            $objTemplate->hideBlock('theme_libraries');
            $this->strErrMessage = sprintf($_ARRAYLANG['TXT_THEME_NOT_COMPONENT'], contrexx_raw2xhtml($theme->getThemesname()));
        }

        $selectedFile = null;
        if (!empty($themesPage)) {
            $selectedFile = $this->getFileFromPath($theme, $themesPage, $isComponentFile);
        }

        if ($selectedFile == null) {
            $selectedFile     = new \Cx\Core\ViewManager\Model\Entity\ViewManagerFile($theme->getFoldername() . '/index.html', $this->fileSystem);
            $themesPage = '/index.html';
            $isComponentFile  = false;
        }

        // Get the left side file's menu
        $this->getFilesDropdown($theme, $themesPage, $isComponentFile);
        // Load the content
        $this->getFilesContent($selectedFile);

        $objTemplate->setVariable(array(
            'THEME_ID'                  => $theme->getId(),
            'THEME_SELECTED_THEME'      => $theme->getFoldername(),
            'THEMES_SELECTED_PAGENAME'  => contrexx_raw2xhtml($themesPage),
            'THEME_SELECTED_THEME_NAME' => contrexx_raw2xhtml($theme->getThemesname()),
            'THEME_IS_APPLICATION'      => $isComponentFile ? 1 : 0,
            'THEME_EDIT_PATH'           => (!$isComponentFile ? '/'.$theme->getFoldername() : '') . $themesPage,
            'THEMES_MENU'               => $this->getThemesDropdown($theme),
            'CONTREXX_BASE_URL'         => $this->cx->getWebsiteOffsetPath() . '/',
        ));


        $jsCode = <<<CODE
\$J(function(){
// Tab initialization
var tabs = \$J('#tabs').tabs({ selected: 0 });
tabs.tabs('option', 'selected', $themeTab);
\$J('#selectedTab').val($themeTab);
});
CODE;
        \JS::registerCode($jsCode);

        $cxjs = \ContrexxJavascript::getInstance();
        $cxjs->setVariable(array(
            'confirmDeleteFile'     => $_ARRAYLANG['TXT_THEME_CONFIRM_DELETE_FILE'],
            'confirmDeleteFolder'   => $_ARRAYLANG['TXT_THEME_CONFIRM_DELETE_FOLDER'],
            'confirmResetFolder'    => $_ARRAYLANG['TXT_THEME_CONFIRM_RESET_FOLDER'],
            'confirmResetFile'      => $_ARRAYLANG['TXT_THEME_CONFIRM_RESET_FILE'],
            'fileName'              => $_ARRAYLANG['TXT_THEME_FILE_NAME'],
            'txtName'               => $_ARRAYLANG['TXT_NAME'],
            'newFileOperation'      => $_ARRAYLANG['TXT_THEME_CREATE_NEW_FILE'],
            'newFolderOperation'    => $_ARRAYLANG['TXT_THEME_CREATE_NEW_FOLDER'],
            'renameFileOperation'   => $_ARRAYLANG['TXT_THEME_RENAME_FILE_OPERATION'],
            'renameFolderOperation' => $_ARRAYLANG['TXT_THEME_RENAME_FOLDER_OPERATION'],
            'cancel'                => $_ARRAYLANG['TXT_THEME_CANCEL'],
            'create'                => $_ARRAYLANG['TXT_THEME_CREATE'],
            'save'                  => $_ARRAYLANG['TXT_SAVE'],
            'rename'                => $_ARRAYLANG['TXT_THEME_RENAME'],
            'loading'               => $_ARRAYLANG['TXT_CORE_VIEWMANAGER_LOADING'],
        ), 'viewmanager/lang');

        $objTemplate->setVariable(array(
            'TXT_CHOOSE_TEMPLATE_GROUP'       => $_ARRAYLANG['TXT_CHOOSE_TEMPLATE_GROUP'],
            'TXT_SELECT_FILE'                 => $_ARRAYLANG['TXT_SELECT_FILE'],
            'TXT_DESIGN_FOLDER'               => $_ARRAYLANG['TXT_DESIGN_FOLDER'],
            'TXT_TEMPLATE_FILES'              => $_ARRAYLANG['TXT_TEMPLATE_FILES'],
            'TXT_FILE_EDITOR'                 => $_ARRAYLANG['TXT_FILE_EDITOR'],
            'TXT_UPLOAD_FILES'                => $_ARRAYLANG['TXT_UPLOAD_FILES'],
            'TXT_CREATE_FILE'                 => $_ARRAYLANG['TXT_CREATE_FILE'],
            'TXT_DELETE'                      => $_ARRAYLANG['TXT_DELETE'],
            'TXT_STORE'                       => $_ARRAYLANG['TXT_SAVE'],
            'TXT_RESET'                       => $_ARRAYLANG['TXT_RESET'],
            'TXT_SELECT_ALL'                  => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_MANAGE_FILES'                => $_ARRAYLANG['TXT_MANAGE_FILES'],
            'TXT_SELECT_THEME'                => $_ARRAYLANG['TXT_SELECT_THEME'],
            'TXT_THEME_NAME'                  => $_ARRAYLANG['TXT_THEME_NAME'],
            'TXT_VIEWMANAGER_OVERVIEW'        => $_ARRAYLANG['TXT_VIEWMANAGER_OVERVIEW'],
            'TXT_MODE'                        => $_ARRAYLANG['TXT_MODE'],
            'TXT_THEMES_EDIT'                 => $_ARRAYLANG['TXT_SETTINGS_MODFIY'],
            'TXT_THEMES_CREATE'               => $_ARRAYLANG['TXT_CREATE'],
            'TXT_THEME_IMPORT'                => $_ARRAYLANG['TXT_THEME_IMPORT'],
            'TXT_THEME_FILE_FOLDER_NAME'      => $_ARRAYLANG['TXT_THEME_FILE_FOLDER_NAME'],
            'TXT_EDIT'                        => $_ARRAYLANG['TXT_EDIT'],
            'TXT_THEME_NEW_FILE'              => $_ARRAYLANG['TXT_THEME_NEW_FILE'],
            'TXT_THEME_NEW_FOLDER'            => $_ARRAYLANG['TXT_THEME_NEW_FOLDER'],
            'TXT_THEME_NEW_THEME'             => $_ARRAYLANG['TXT_THEME_NEW_THEME'],
            'TXT_THEME_PREVIEW'               => $_ARRAYLANG['TXT_THEME_PREVIEW'],
            'TXT_THEME_FULLSCREEN'            => $_ARRAYLANG['TXT_THEME_FULLSCREEN'],
            'TXT_THEME_FULLSCREEN_INFO'       => $_ARRAYLANG['TXT_THEME_FULLSCREEN_INFO'],
            'TXT_FILES'                       => $_ARRAYLANG['TXT_FILES'],
            'TXT_DESIGN_APPLICATION_TEMPLATE' => $_ARRAYLANG['TXT_DESIGN_APPLICATION_TEMPLATE'],
            'TXT_THEMES_LIBRARIES'            => $_ARRAYLANG['TXT_THEMES_LIBRARIES'],
            'TXT_THEME_TEMPLATE'              => $_ARRAYLANG['TXT_THEME_TEMPLATE'],
            'TXT_THEME_EDIT_FILE'             => $_ARRAYLANG['TXT_THEME_EDIT_FILE'],
            'TXT_THEME_RENAME'                => $_ARRAYLANG['TXT_THEME_RENAME'],
            'TXT_THEME_REMOVE'                => $_ARRAYLANG['TXT_THEME_REMOVE'],
            'TXT_THEME_RESET'                 => $_ARRAYLANG['TXT_THEME_RESET'],
            'TXT_THEME_FILE_FOLDER_NAME_EX_CONTENT' => $_ARRAYLANG['TXT_THEME_FILE_FOLDER_NAME_EX_CONTENT'],
        ));

        if (!\Permission::checkAccess(self::THEMES_IMPORT_EXPORT_ACCESS_ID, 'static', true)) {
            $objTemplate->hideBlock('view_manager_import_navigation');
        }
    }

    /**
     * set up Import page
     * call specific function depending on $_GET
     * @access private
     */
    private function import()
    {
        global $_ARRAYLANG, $objTemplate;

        \Permission::checkAccess(self::THEMES_IMPORT_EXPORT_ACCESS_ID, 'static');

        $objTemplate->addBlockfile('ADMIN_CONTENT', 'skins_import', 'skins_import.html');
        $this->pageTitle = $_ARRAYLANG['TXT_THEME_IMPORT'];

        if (!empty($_GET['import'])) {
            $this->importFile();
        }
        // init uploader to import of themes
        $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
        $uploader->setCallback('themesZipFileUploaderCallback');
        $uploader->setOptions(array(
            'id'                 => 'local-archive-uploader',
            'allowed-extensions' => array('zip'),
            'style'              => 'display:none',
            'data-upload-limit'  => 1,
        ));

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $uploader->setFinishedCallback(array(
            $cx->getCodeBaseCorePath().'/ViewManager/Controller/ViewManager.class.php',
            '\Cx\Core\ViewManager\Controller\ViewManager',
            'uploadFinished'
        ));

        $objTemplate->setVariable(array(
            'TXT_THEMES_EDIT'       => $_ARRAYLANG['TXT_SETTINGS_MODFIY'],
            'TXT_THEMES_CREATE'     => $_ARRAYLANG['TXT_CREATE'],
            'TXT_THEME_IMPORT'      => $_ARRAYLANG['TXT_THEME_IMPORT'],
            'TXT_THEME_LOCAL_FILE'          => $_ARRAYLANG['TXT_THEME_LOCAL_FILE'],
            'TXT_THEME_SPECIFY_URL'         => $_ARRAYLANG['TXT_THEME_SPECIFY_URL'],
            'TXT_THEME_IMPORT_INFO'         => $_ARRAYLANG['TXT_THEME_IMPORT_INFO'],
            'TXT_THEME_NO_URL_SPECIFIED'    => $_ARRAYLANG['TXT_THEME_NO_URL_SPECIFIED'],
            'TXT_THEME_NO_FILE_SPECIFIED'   => $_ARRAYLANG['TXT_THEME_NO_FILE_SPECIFIED'],
            'TXT_THEME_FILESYSTEM'          => sprintf($_ARRAYLANG['TXT_THEME_FILESYSTEM'], \Cx\Core\Core\Controller\Cx::instanciate()->getThemesFolderName()),
            'THEMES_MENU'                   => $this->getDropdownNotInDb(),
            'TXT_THEME_DO_IMPORT'     => $_ARRAYLANG['TXT_THEME_DO_IMPORT'],
            'TXT_THEME_IMPORT_THEME'  => $_ARRAYLANG['TXT_THEME_IMPORT_THEME'],
            'TXT_VIEWMANAGER_THEME_SELECTION_TXT' => $_ARRAYLANG['TXT_VIEWMANAGER_THEME_SELECTION_TXT'],
            'TXT_VIEWMANAGER_THEME' => $_ARRAYLANG['TXT_VIEWMANAGER_THEME'],
            'TXT_VIEWMANAGER_SOURCE' => $_ARRAYLANG['TXT_VIEWMANAGER_SOURCE'],
            'TXT_SELECT_FILE'        => $_ARRAYLANG['TXT_SELECT_FILE'],
            'THEMES_UPLOADER_CODE'   => $uploader->getXHtml(),
            'THEMES_UPLOADER_ID'     => $uploader->getId(),
        ));

        if (!\Permission::checkAccess(self::EDIT_THEMES_ACCESS_ID, 'static', true)) {
            $objTemplate->hideBlock('view_manager_manage_theme');
        }
    }

    /**
     * Export theme as ZIP archive
     *
     * @access private
     */
    private function manage()
    {
        \Permission::checkAccess(self::THEMES_IMPORT_EXPORT_ACCESS_ID, 'static');

        //check GETs for action
        $themeId = isset($_GET['export']) ? contrexx_input2raw($_GET['export']) : 0;
        if (!empty($themeId)) {
            $theme = $this->themeRepository->findOneBy(array('id' => $themeId));

            if (!$theme) {
                throw new \Exception('Theme does not exist.');
            }

            $objHTTPDownload = new \HTTP_Download();
            $objHTTPDownload->setFile($this->getExportFilePath($theme));
            $objHTTPDownload->setContentDisposition(HTTP_DOWNLOAD_ATTACHMENT, $theme->getFoldername().'.zip');
            $objHTTPDownload->setContentType();
            $objHTTPDownload->send('application/force-download');
            exit;
        }

    }


    /**
     * clean tmp folder
     * @access private
     * @return void
     */
    function _cleantmp()
    {
        if (is_dir($this->_archiveTempPath)) {
            $dh = opendir($this->_archiveTempPath);
            if ($dh) {
                $file = readdir($dh);
                while ($file !== false) {
                    if ($file != '..' && $file != '.' && is_file($this->_archiveTempPath.$file)){
                        unlink($this->_archiveTempPath.$file);
                    }
                    $file = readdir($dh);
                }
                closedir($dh);
            }
        }
    }

    /**
     * check fileupload and move uploaded file if it's a valid archive
     *
     * @return boolean|string File path when the uploaded file copied to archieveTempPath, false otherwise
     */

    private function checkUpload()
    {
        global $_ARRAYLANG;

        $uploaderId   = isset($_POST['importUploaderId']) ? contrexx_input2raw($_POST['importUploaderId']) : '';
        $uploadedFile = $this->getUploadedFileFromUploader($uploaderId);
        if (!$uploadedFile) {
            $this->strErrMessage = $_ARRAYLANG['TXT_COULD_NOT_UPLOAD_FILE'];
            return false;
        }

        $objFile = new \Cx\Lib\FileSystem\File($uploadedFile);
        $uploadedFileBaseName = basename($uploadedFile);
        if (!$objFile->copy($this->_archiveTempPath . $uploadedFileBaseName)) {
            $this->strErrMessage = $this->_archiveTempPath . $uploadedFileBaseName . ': ' .$_ARRAYLANG['TXT_COULD_NOT_UPLOAD_FILE'];
            return false;
        }

        return $uploadedFileBaseName;
    }


    /**
     * check for valid archive structure, put directories into $arrDirectories
     * set errormessage if structure not valid
     *
     * @param array  $content                    File and directory list
     * @param string $themeDirectory             Theme directory
     * @param string $themeDirectoryFromArchive  Theme directory from archive
     * @param string $themeName                  Theme name
     * @param array  $arrDirectories             Array directories
     * @param string $archiveFile                Archive File
     *
     * @return boolean
     */
    private function validateArchiveStructure($content, &$themeDirectory, &$themeDirectoryFromArchive, &$themeName, &$arrDirectories, $archiveFile)
    {
        global $_ARRAYLANG;

        //check if archive is empty
        if (sizeof($content) == 0){
            $this->strErrMessage = $archiveFile . ': '. $_ARRAYLANG['TXT_THEME_ARCHIVE_WRONG_STRUCTURE'];
            return false;
        }

        $first_item = $content[0];
        $themeDirectoryFromArchive = substr($first_item['stored_filename'], 0, strpos($first_item['stored_filename'], '/'));
        $themeDirectory = $themeDirectoryFromArchive;

        // ensure that we're creating a new directory and not trying to overwrite an existing one
        $suffix = '';
        while (file_exists($this->path.$themeDirectory.$suffix)) {
            $suffix++;
        }
        $themeDirectory .= $suffix;

        $this->validateThemeName($themeDirectory, 'foldername');
        $themeName = !empty($_POST['theme_dbname']) ? contrexx_input2raw($_POST['theme_dbname']) : $themeDirectoryFromArchive;

        $arrDirectories[] = $themeDirectory;

        foreach ($content as $item){
            //check if current file/directory contains the base directory and abort when not true
            if (strpos($item['stored_filename'], $themeDirectoryFromArchive) !== 0) {
                $this->strErrMessage = $archiveFile . ': ' . $_ARRAYLANG['TXT_THEME_ARCHIVE_WRONG_STRUCTURE'];
                return false;
            }

            // skip files
            if (!$item['folder']) {
                continue;
            }

            //check if its the base directory
            if (basename($item['stored_filename']) != $themeDirectoryFromArchive) {
                //only take the most top directory
                $arrDirectories[] = substr($item['stored_filename'], strlen($themeDirectoryFromArchive));
            }
        }

        return true;
    }

    /**
     * Create the directory structure of the archive contents and set permissions
     * @return boolean
     */

    private function createDirectoryStructure($themeDirectory, $arrDirectories)
    {
        global $_ARRAYLANG;
        //create archive structure and set permissions
        //this is an important step on hostings where the FTP user ID differs from the PHP user ID
        foreach ($arrDirectories as $index => $directory){
            switch($index){
                //check if theme directory already exists
                case 0:
                    if (file_exists($this->path.$directory)){
                        // basically this should never happen, because the directory $themeDirectory should have been renamed
                        // automatically in case a directory with the same name is already present
                        $this->strErrMessage = $themeDirectory.': '.$_ARRAYLANG['TXT_THEME_FOLDER_ALREADY_EXISTS'].'! '.$_ARRAYLANG['TXT_THEME_FOLDER_DELETE_FIRST'].'.';
                        return false;
                    }

                    \Cx\Lib\FileSystem\FileSystem::make_folder($this->path.$directory);
                    //\Cx\Lib\FileSystem\FileSystem::makeWritable($this->path.$directory);
                    break;

                default:
                    \Cx\Lib\FileSystem\FileSystem::make_folder($this->path.$themeDirectory.'/'.$directory);
                    //\Cx\Lib\FileSystem\FileSystem::makeWritable($this->path.$themeDirectory.'/'.$directory);
                    break;
            }
        }

        return true;
    }


    /**
     * Extracts the archive to the themes path
     *
     * @param object $archive                   pclZip Object $archive
     * @param object $theme                     \Cx\Core\View\Model\Entity\Theme $theme
     * @param string $themeDirectoryFromArchive
     *
     * @return boolean
     */
    private function extractArchive(\PclZip $archive, \Cx\Core\View\Model\Entity\Theme $theme, $themeDirectoryFromArchive)
    {
        global $_ARRAYLANG;

        $valid_exts = array(
            "txt","doc","xls","pdf","ppt","gif","jpg","png","xml",
            "odt","ott","sxw","stw","dot","rtf","sdw","wpd","jtd",
            "jtt","hwp","wps","ods","ots","sxc","stc","dif","dbf",
            "xlw","xlt","sdc","vor","sdc","cvs","slk","wk1","wks",
            "123","odp","otp","sxi","sti","pps","pot","sxd","sda",
            "sdd","sdp","cgm","odg","otg","sxd","std","dxf","emf",
            "eps","met","pct","sgf","sgv","svm","wmf","bmp","jpeg",
            "jfif","jif","jpe","pbm","pcx","pgm","ppm","psd","ras",
            "tga","tif","tiff","xbm","xpm","pcd","oth","odm","sxg",
            "sgl","odb","odf","sxm","smf","mml","zip","rar","htm",
            "html","shtml","css","js","tpl","thumb","ico",
            "eot", "ttf", "woff", "otf", "yml", "yaml"
        );

        if (($files = $archive->extract(PCLZIP_OPT_PATH, $this->path . $theme->getFoldername(), PCLZIP_OPT_REMOVE_PATH, $themeDirectoryFromArchive, PCLZIP_OPT_BY_PREG, '/('.implode('|', $valid_exts).')$/')) != 0){
            foreach ($files as $file) {
                //check status for errors while extracting the archive
                if (!in_array($file['status'],array('ok','filtered','already_a_directory'))){
                    $this->strErrMessage = $_ARRAYLANG['TXT_THEME_ARCHIVE_ERROR'].': '.$archive->errorInfo(true);
                    return false;
                }
            }

            // add eventually missing required theme files
            $this->createDefaultFiles($theme);
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_THEME_ARCHIVE_ERROR'].': '.$archive->errorInfo(true);
            return false;
        }

        return true;
    }


    /**
     * import themes from archive
     * @access   private
     * @param    string   $themes
     */
    private function importFile()
    {
        global $_ARRAYLANG;

        $this->_cleantmp();

        switch($_GET['import']) {
            case 'remote':
                $archiveFile = $this->_fetchRemoteFile($_POST['importremote']);
                if ($archiveFile === false) {
                    return false;
                }
                $archive = new \PclZip($archiveFile);
                //no break
            case 'local':
                if (empty($archive)) {
                    if (($archiveFile = $this->checkUpload()) === false) {
                        return false;
                    }
                    $archive = new \PclZip($this->_archiveTempPath . $archiveFile);
                }
                $content = $archive->listContent();
                $themeName = '';
                $themeDirectory = '';
                $themeDirectoryFromArchive = '';
                $arrDirectories = array();

                // analyze theme archive
                if (!$this->validateArchiveStructure($content, $themeDirectory, $themeDirectoryFromArchive, $themeName, $arrDirectories, $archiveFile)) {
                    return false;
                }
                // prepare directory structure of new theme
                if (!$this->createDirectoryStructure($themeDirectory, $arrDirectories)) {
                    return false;
                }

                // try to get the theme name from yml
                $themeInfoContent = $archive->extract(PCLZIP_OPT_BY_NAME, $themeDirectoryFromArchive .  \Cx\Core\View\Model\Entity\Theme::THEME_COMPONENT_FILE, PCLZIP_OPT_EXTRACT_AS_STRING);
                if (!empty($themeInfoContent)) {
                    $yaml      = new \Symfony\Component\Yaml\Yaml();
                    $themeInfo = $yaml->parse($themeInfoContent[0]['content']);
                    $themeName = isset($themeInfo['DlcInfo']['name']) ? $themeInfo['DlcInfo']['name'] : $themeName;
                }

                //create database entry
                $this->validateThemeName($themeName);

                $theme = new \Cx\Core\View\Model\Entity\Theme();
                $theme->setThemesname($themeName);
                $theme->setFoldername($themeDirectory);

                //extract archive files
                $this->extractArchive($archive, $theme, $themeDirectoryFromArchive);

                $this->replaceThemeName($themeDirectoryFromArchive, $themeDirectory, $this->websiteThemesPath . $arrDirectories[0]);
                $this->insertSkinIntoDb($theme);
                \Message::add(contrexx_raw2xhtml($themeName).' ('.$themeDirectory.') '.$_ARRAYLANG['TXT_THEME_SUCCESSFULLY_IMPORTED']);
                break;
            case 'filesystem':
                $theme                     = new \Cx\Core\View\Model\Entity\Theme();
                $themeName = null;
                $existingThemeInFilesystem = !empty($_POST['existingdirName']) ? contrexx_input2raw($_POST['existingdirName']) : null;

                $themePath = $theme->getFilePath($existingThemeInFilesystem);

                if (!file_exists($themePath)) {
                    \Message::add($_ARRAYLANG['TXT_THEME_OPERATION_FAILED_FOR_EMPTY_PARAMS'], \Message::CLASS_ERROR);
                    return false;
                }

                $yamlFile = $theme->getFilePath($existingThemeInFilesystem . '/component.yml');
                if ($yamlFile) {
                    $objFile = new \Cx\Lib\FileSystem\File($yamlFile);
                    $yaml = new \Symfony\Component\Yaml\Yaml();
                    $themeInformation = $yaml->parse($objFile->getData());
                    $themeName = $themeInformation['DlcInfo']['name'];
                }

                $themeName = $themeName ?: $existingThemeInFilesystem;

                if (empty($themeName) || empty($existingThemeInFilesystem)) {
                    \Message::add($_ARRAYLANG['TXT_THEME_OPERATION_FAILED_FOR_EMPTY_PARAMS'], \Message::CLASS_ERROR);
                    return false;
                }

                $this->validateThemeName($themeName);

                $theme->setThemesname($themeName);
                $theme->setFoldername($existingThemeInFilesystem);

                if ($this->insertSkinIntoDb($theme)) {
                    \Message::add(contrexx_raw2xhtml($themeName).' '.$_ARRAYLANG['TXT_STATUS_SUCCESSFULLY_CREATE']);
                }
                break;
            default:
                //everything else should never be the case
                \Message::add("GET Request Error. 'import' should be either 'local' or 'remote'", \Message::CLASS_ERROR);
                return false;
                break;
        }

        // Theme build successfully
        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=ViewManager&act=templates&themes='. $theme->getFoldername());

    }

    /**
     * Uploader callback function
     *
     * This is called as soon as uploads have finished.
     *
     * @param string  $tempPath    Path to the temporary directory containing the files at this moment
     * @param string  $tempWebPath Points to the same folder as tempPath, but relative to the webroot
     * @param array   $data        Data given to setData() when creating the uploader
     * @param string  $uploadId    upload id
     * @param array   $fileInfos   uploaded file informations
     * @param object  $response    Upload api response object
     *
     * @return array $tempPath and $tempWebPath
     */
    public static function uploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response)
    {
        // in case uploader has been restricted to only allow one single file to be
        // uploaded, we'll have to clean up any previously uploaded files
        if (count($fileInfos['name'])) {
            // new files have been uploaded -> remove existing files
            if (\Cx\Lib\FileSystem\FileSystem::exists($tempPath)) {
                foreach (glob($tempPath.'/*') as $file) {
                    if (basename($file) == $fileInfos['name']) {
                        continue;
                    }
                    \Cx\Lib\FileSystem\FileSystem::delete_file($file);
                }
            }
        }

        return array($tempPath, $tempWebPath);
    }

    /**
    * Get uploaded zip file by using uploader id
    *
    * @param string $uploaderId Uploader id
    *
    * @return boolean|string File path when file exists, false otherwise
    */
    public function getUploadedFileFromUploader($uploaderId)
    {
        if (empty($uploaderId)) {
            \DBG::log('Uploader id is empty');
            return false;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $sessionObj = $cx->getComponent('Session')->getSession();

        $uploaderFolder = $sessionObj->getTempPath() . '/' . $uploaderId;

        if (!\Cx\Lib\FileSystem\FileSystem::exists($uploaderFolder)) {
            \DBG::log('The Uploader Folder path is invalid/not exists');
            return false;
        }

        foreach (glob($uploaderFolder.'/*.zip') as $file) {
            return $file;
        }

        return false;
    }

    function _fetchRemoteFile($URL)
    {
        global $_ARRAYLANG;

        $URL = parse_url($URL);
        $errno = $errstr = null;
        $http = @fsockopen($URL['host'], !empty($URL['port']) ? intval($URL['port']) : 80 , $errno, $errstr, 10);
        if ($http){
            $archive = '';
            fputs($http,'GET /'.$URL['path']." HTTP/1.1\r\n");
            fputs($http,'Host: '.$URL['host']."\r\n\r\n");
            while(!feof($http)){
                $archive .= fgets($http, 1024);
            }
            fclose($http);
            //cut off HTTP headers, PKZIP header = "\x50\x4B\x03\x04"
            $archive=strstr($archive,"\x50\x4B\x03\x04");
            if ($archive == ''){
                \Message::add($_ARRAYLANG['TXT_THEME_IMPORT_WRONG_MIMETYPE'], \Message::CLASS_ERROR);
                return false;
            }
            $tmpfilename = basename($URL['path']);
            if (strlen($tmpfilename) < 3) $tmpfilename = '_unknown_upload_';
// TODO: use session temp
            $tempFile = \Env::get('cx')->getWebsiteTempPath() .'/'.$tmpfilename.microtime().'.zip';
            $fh = fopen($tempFile,'w');
            fputs($fh, $archive);
            return $tempFile;
        } else {
            \Message::add($_ARRAYLANG['TXT_THEME_HTTP_CONNECTION_FAILED'], \Message::CLASS_ERROR);
            return false;
        }
    }

    /**
     * Get the export archive file path
     *
     * @param    object theme \Cx\Core\View\Model\Entity\Theme $theme
     *
     * @return   string path to the created archive
     */
    function getExportFilePath(\Cx\Core\View\Model\Entity\Theme $theme)
    {
        global $_ARRAYLANG;

        //clean up tmp folder
        $this->_cleantmp();

        $themeFolderPath = new \Cx\Core\ViewManager\Model\Entity\ViewManagerFile($theme->getFoldername(), $this->fileSystem);
        if (!$this->fileSystem->fileExists($themeFolderPath)) {
            $this->strErrMessage = $_ARRAYLANG['TXT_THEME_FOLDER_DOES_NOT_EXISTS'];
            return false;
        }

        $themeFolder = $theme->getFoldername();
        $archive     = new \PclZip($this->_archiveTempPath . $themeFolder . '.zip');
        $themeFiles  = $this->getThemesFiles($theme);

        \Cx\Lib\FileSystem\FileSystem::makeWritable($this->_archiveTempPath);
        $this->createZipFolder($archive, $themeFiles, '/' . $themeFolder);
        \Cx\Lib\FileSystem\FileSystem::makeWritable($this->_archiveTempPath . $themeFolder . '.zip');
        return $this->_archiveTempPath . $themeFolder . '.zip';
    }

    /**
     * Create the archive file to tmp folder
     *
     * @param object $archive           Contains the PclZip archive object
     * @param array  $themeFilesArray   Themes files in array
     * @param string $folder            Folder name
     */
    function createZipFolder($archive, $themeFilesArray, $folder = '/')
    {
        global $_ARRAYLANG;
        foreach ($themeFilesArray as $folderName => $fileName) {
            if (is_array($fileName)) {
                $this->createZipFolder($archive, $fileName, $folder . '/' . $folderName);
                continue;
            }
            $relativePath = $folder . '/' . $fileName;
            if (self::isFileTypeComponent($relativePath)) {
                $relativePath = self::getComponentFilePath($relativePath, false);
            }

            $localFile  = new \Cx\Core\ViewManager\Model\Entity\ViewManagerFile($relativePath, $this->fileSystem);
            $filePath   = $localFile->getFileSystem()->getFullPath($localFile) . $localFile->getFullName();
            $removePath = preg_replace('/'. preg_quote($relativePath, '/') .'$/', '', $filePath);

            if ($archive->add($filePath, PCLZIP_OPT_REMOVE_PATH, $removePath) == 0) {
                \DBG::log($_ARRAYLANG['TXT_THEME_ARCHIVE_ERROR'] .' ' . $archive->errorInfo(true));
            }
        }
    }

    /**
     * Get the themes files using viewmanager filesystem
     *
     * @return  array
     */
    function getThemesFiles(\Cx\Core\View\Model\Entity\Theme $theme) {
        $filesList     = $this->fileSystem->getFileList($theme->getFoldername());
        $formatedFiles = $this->formatFileList($filesList);
        $this->sortFilesFolders($formatedFiles);

        return $formatedFiles;
    }

    /**
     * Format the Filesystem files and folders to viewManger format
     *
     * @param array $filesList
     *
     * @return array
     */
    function formatFileList($filesList)
    {
        $result = array();

        foreach ($filesList as $fileInfo) {
            $info = $fileInfo['datainfo'];
            if ($info['type'] == 'file') {
                $result[] = $info['name'];
            } elseif ($info['type'] == 'dir') {
                $subFiles = $fileInfo;
                unset($subFiles['datainfo']);

                $name = $info['name'];
                switch (true) {
                    case $name == ltrim($this->cx->getCoreModuleFolderName() , '/'):
                        $name = 'core_module';
                        break;
                    case $name == ltrim($this->cx->getModuleFolderName(), '/'):
                        $name = 'module';
                        break;
                    case $name == ltrim($this->cx->getCoreFolderName(), '/'):
                        $name = 'core';
                        break;
                    default:
                        break;
                }

                $result[$name] = $this->formatFileList($subFiles);
            }
        }

        return $result;
    }

    /**
     * Gets the themes assigning page
     * @access   private
     * @global   ADONewConnection
     * @global   array
     * @global   \Cx\Core\Html\Sigma
     */
    function _activate()
    {
        global $objDatabase, $_ARRAYLANG, $objTemplate;

        \Permission::checkAccess(self::ENABLE_THEMES_ACCESS_ID, 'static');

        $objTemplate->addBlockfile('SETTINGS_CONTENT', 'skins_activate', 'skins_activate.html');
        $this->pageTitle = $_ARRAYLANG['TXT_OVERVIEW'];
        $objTemplate->setVariable(array(
            'TXT_ACTIVATE_DESIGN'          => $_ARRAYLANG['TXT_ACTIVATE_DESIGN'],
            'TXT_ID'                       => $_ARRAYLANG['TXT_ID'],
            'TXT_LANGUAGE'                 => $_ARRAYLANG['TXT_LANGUAGE'],
            'TXT_ACTIVE_TEMPLATE'          => $_ARRAYLANG['TXT_ACTIVE_TEMPLATE'],
            'TXT_ACTIVE_PDF_TEMPLATE'      => $_ARRAYLANG['TXT_ACTIVE_PDF_TEMPLATE'],
            'TXT_ACTIVE_PRINT_TEMPLATE'    => $_ARRAYLANG['TXT_ACTIVE_PRINT_TEMPLATE'],
            'TXT_SAVE'                     => $_ARRAYLANG['TXT_SAVE'],
            'TXT_THEME_ACTIVATE_INFO'      => $_ARRAYLANG['TXT_THEME_ACTIVATE_INFO'],
            'TXT_ACTIVE_MOBILE_TEMPLATE'   => $_ARRAYLANG['TXT_ACTIVE_MOBILE_TEMPLATE'],
            'TXT_ACTIVE_APP_TEMPLATE'      => $_ARRAYLANG['TXT_APP'],
        ));
        $i=0;

        // channels
        $channels = \Cx\Core\View\Model\Entity\Theme::$channels;

        if (isset($_POST['themesId'])) {
            foreach ($_POST['themesId'] as $langId => $themesId) {
                $this->activateFrontendTheme(
                    $langId,
                    $channels[0],
                    $themesId
                );
            }
            foreach ($_POST['mobileThemesId'] as $langId => $mobileThemesId) {
                $this->activateFrontendTheme(
                    $langId,
                    $channels[1],
                    $mobileThemesId
                );
            }
            foreach ($_POST['printThemesId'] as $langId => $printThemesId) {
                $this->activateFrontendTheme(
                    $langId,
                    $channels[2],
                    $printThemesId
                );
            }
            foreach ($_POST['pdfThemesId'] as $langId => $pdfThemesId) {
                $this->activateFrontendTheme(
                    $langId,
                    $channels[3],
                    $pdfThemesId
                );
            }
            foreach ($_POST['appThemesId'] as $langId => $appThemesId) {
                $this->activateFrontendTheme(
                    $langId,
                    $channels[4],
                    $appThemesId
                );
            }
            $this->em->flush();
            // reinit fwlanguage to show updated frontends
            \FWLanguage::init();

            $this->strOkMessage = $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'];
        }
        // parse row for every frontend locale
        foreach (\FWLanguage::getActiveFrontendLanguages() as $frontendLanguage) {
            if (!$this->isInLanguageFullMode() && !$frontendLanguage['is_default']) {
                continue;
            }

            $class = 'row' . ($i % 2 + 1);

            $objTemplate->setVariable(array(
                'THEMES_ROWCLASS'             => $class,
                'THEMES_LANG_ID'              => $frontendLanguage['id'],
                'THEMES_LANG_SHORTNAME'       => $frontendLanguage['lang'],
                'THEMES_LANG_NAME'            => $frontendLanguage['name'],
                'THEMES_TEMPLATE_MENU'        => $this->_getDropdownActivated($frontendLanguage['themesid']),
                'THEMES_MOBILE_TEMPLATE_MENU' => $this->_getDropdownActivated($frontendLanguage['mobile_themes_id']),
                'THEMES_PRINT_TEMPLATE_MENU'  => $this->_getDropdownActivated($frontendLanguage['print_themes_id']),
                'THEMES_PDF_TEMPLATE_MENU'    => $this->_getDropdownActivated($frontendLanguage['pdf_themes_id']),
                'THEMES_APP_TEMPLATE_MENU'    => $this->_getDropdownActivated($frontendLanguage['app_themes_id']),
            ));
            $objTemplate->parse('themesLangRow');
            $i++;
        }
    }

    /**
     * Gets the themes example page
     * @access   public
     */
    function examples()
    {
        global $_ARRAYLANG, $_CONFIG, $objTemplate;

        \Permission::checkAccess(self::ENABLE_THEMES_ACCESS_ID, 'static');

        // initialize variables
        $objTemplate->addBlockfile('SETTINGS_CONTENT', 'skins_examples', 'skins_examples.html');
        $this->pageTitle = $_ARRAYLANG['TXT_DESIGN_VARIABLES_LIST'];
        $objTemplate->setVariable(array(
            'TXT_STANDARD_TEMPLATE_STRUCTURE'       => $_ARRAYLANG['TXT_STANDARD_TEMPLATE_STRUCTURE'],
            'TXT_FRONTEND_EDITING_LOGIN_FRONTEND'   => $_ARRAYLANG['TXT_FRONTEND_EDITING_LOGIN_FRONTEND'],
            'TXT_STARTPAGE'                         => $_ARRAYLANG['TXT_STARTPAGE'],
            'TXT_STANDARD_PAGES'                    => $_ARRAYLANG['TXT_STANDARD_PAGES'],
            'TXT_REPLACEMENT_LIST'                  => $_ARRAYLANG['TXT_REPLACEMENT_LIST'],
            'TXT_FILES'                             => $_ARRAYLANG['TXT_FILES'],
            'TXT_CONTENTS'                          => $_ARRAYLANG['TXT_CONTENTS'],
            'TXT_PLACEHOLDER_DIRECTORY'             => $_ARRAYLANG['TXT_DESIGN_REPLACEMENTS_DIR'],
            'TXT_PLACEHOLDER_DIRECTORY_DESCRIPTION' => $_ARRAYLANG['TXT_PLACEHOLDER_DIRECTORY_DESCRIPTION'],
            'TXT_CHANNELS'                          => $_ARRAYLANG['TXT_CHANNELS'],
            'TXT_MODULE_URLS'                       => $_ARRAYLANG['TXT_MODULE_URLS'],
            'TXT_CONTACT'                           => $_ARRAYLANG['TXT_CONTACT'],
            'CONTREXX_BASE_URL'                     =>  \Env::get('cx')->getWebsiteOffsetPath() . '/',
        ));
    }

    /**
     * create skin folder page
     * @access   public
     */
    function newdir()
    {
        global $_ARRAYLANG, $objTemplate;

        \Permission::checkAccess(self::EDIT_THEMES_ACCESS_ID, 'static');

        $this->webPath = $this->arrWebPaths[0];

        $selectedTheme = null;
        if (isset($_GET['copy'])) {
            $selectedTheme = $this->themeRepository->findOneBy(array('foldername' => contrexx_input2raw($_GET['copy'])));
        }

        // initialize variables
        $objTemplate->addBlockfile('ADMIN_CONTENT', 'skins_create', 'skins_create.html');
        $this->pageTitle = $_ARRAYLANG['TXT_NEW_DIRECTORY'];
        $objTemplate->setVariable(array(
            'TXT_NEW_DIRECTORY'       => $_ARRAYLANG['TXT_NEW_DIRECTORY'],
            'CREATE_DIR_ACTION'       => '?cmd=ViewManager&amp;act=createDir&amp;path=' . $this->webPath,
            'TXT_DIR_NAME'            => $_ARRAYLANG['TXT_DIR_NAME'],
            'TXT_DB_NAME'             => $_ARRAYLANG['TXT_DB_NAME'],
            'TXT_DESCRIPTION'         => $this->webPath,
            'TXT_CREATE'              => $_ARRAYLANG['TXT_CREATE'],
            'TXT_FROM_TEMPLATE'       => $_ARRAYLANG['TXT_FROM_TEMPLATE'],
            'THEMES_TEMPLATE_MENU'    => $this->getThemesDropdown($selectedTheme, false),
            'TXT_THEMES_EDIT'         => $_ARRAYLANG['TXT_SETTINGS_MODFIY'],
            'TXT_THEMES_CREATE'       => $_ARRAYLANG['TXT_CREATE'],
            'TXT_THEME_IMPORT'        => $_ARRAYLANG['TXT_THEME_IMPORT'],
        ));

        $this->checkTable($this->oldTable);
        if (!\Permission::checkAccess(self::THEMES_IMPORT_EXPORT_ACCESS_ID, 'static', true)) {
            $objTemplate->hideBlock('view_manager_import_navigation');
        }
//      $this->newdir();
    }

    private function validateThemeName(&$themeName, $dbField = 'themesname')
    {
        $suffix = '';
        while ($this->themeRepository->findOneBy(array($dbField => $themeName.$suffix))) {
            $suffix++;
        }

        $themeName .= $suffix;
    }

    /**
     * create skin folder
     * @access   public
     */
    private function createdir()
    {
        global $_ARRAYLANG;

        \Permission::checkAccess(self::EDIT_THEMES_ACCESS_ID, 'static');

        $themeName          = !empty($_POST['dbName']) && !stristr($_POST['dbName'], '..') ? contrexx_input2raw($_POST['dbName']) : null;
        $copyFromTheme      = !empty($_POST['fromTheme']) && !stristr($_POST['fromTheme'], '..') ? contrexx_input2raw($_POST['fromTheme']) : null;
        $createFromDatabase = !empty($_POST['fromDB']) && !stristr($_POST['fromDB'], '..') ? contrexx_input2raw($_POST['fromDB']) : null;
        $dirName            = !empty($_POST['dirName']) && !stristr($_POST['dirName'], '..') ? contrexx_input2raw($_POST['dirName']) : null;
        $dirName            = \Cx\Lib\FileSystem\FileSystem::replaceCharacters($dirName);

        if (!$themeName) {
            $this->strErrMessage = $_ARRAYLANG['TXT_STATUS_CHECK_INPUTS'];
            $this->newdir();
            return;
        }

        $this->validateThemeName($themeName);

        if (!empty($dirName)) {

            // ensure that we're creating a new directory and not trying to overwrite an existing one
            $suffix = '';
            while (file_exists($this->path.$dirName.$suffix)) {
                $suffix++;
            }
            $dirName .= $suffix;

            $theme = new \Cx\Core\View\Model\Entity\Theme();
            $theme->setThemesname($themeName);
            $theme->setFoldername($dirName);

            switch (true) {
                case (empty($copyFromTheme) && empty($createFromDatabase)):
                    // Create new empty theme
                    if (\Cx\Lib\FileSystem\FileSystem::make_folder($this->path . $theme->getFoldername())) {
                        if ($this->createDefaultFiles($theme) && $this->insertSkinIntoDb($theme)) {
                            \Message::add(contrexx_raw2xhtml($themeName).' '.$_ARRAYLANG['TXT_STATUS_SUCCESSFULLY_CREATE']);
                        } else {
                            \Message::add($_ARRAYLANG['TXT_MSG_ERROR_NEW_DIR'], \Message::CLASS_ERROR);
                            $this->newdir();
                            return;
                        }
                    }
                    break;
                case (!empty($copyFromTheme) && empty($createFromDatabase)):
                    $fromThemeFolder = new \Cx\Core\ViewManager\Model\Entity\ViewManagerFile('/'. $copyFromTheme, $this->fileSystem);
                    $toThemeFolder   = new \Cx\Core\ViewManager\Model\Entity\ViewManagerFile('/'. $dirName, $this->fileSystem);
                    if (!$this->fileSystem->copyFolder($fromThemeFolder, $toThemeFolder)) {
                        \Message::add($_ARRAYLANG['TXT_MSG_ERROR_NEW_DIR'], \Message::CLASS_ERROR);
                        $this->newdir();
                        return;
                    }

                    $this->replaceThemeName($copyFromTheme, $dirName, $this->websiteThemesPath . $dirName);
                    //convert theme to component
                    try {
                        $this->themeRepository->loadComponentData($theme);
                        if (!$theme->isComponent()) {
                            // create a new one if no component.yml exists
                            try {
                                $this->themeRepository->convertThemeToComponent($theme);
                            } catch (\Exception $ex) {
                                \DBG::log($ex->getMessage());
                                \DBG::log($theme->getThemesname() .' : Unable to convert theme to component');
                            }
                            $this->themeRepository->loadComponentData($theme);
                        }
                        // change the theme name in component data
                        $themeInformation = $theme->getComponentData();
                        if ($themeInformation) {
                            $themeInformation['name'] = $theme->getThemesname();
                            $theme->setComponentData($themeInformation);

                            $this->themeRepository->saveComponentData($theme);
                        }

                    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                        \Message::add('Error in coverting component file', \Message::CLASS_ERROR);
                    }

                    if ($this->insertSkinIntoDb($theme)) {
                        \Message::add(contrexx_raw2xhtml($themeName).' '.$_ARRAYLANG['TXT_STATUS_SUCCESSFULLY_CREATE']);
                    }
                    break;
                case (empty($copyFromTheme) && !empty($createFromDatabase)):
                    // TODO: remove this function -> migrate all pending themes in the update process
                    // Create new theme from database (migrate existing theme from database to filesystem)
                    if (\Cx\Lib\FileSystem\FileSystem::make_folder($this->path . $dirName)) {
                        $this->insertIntoDb($theme, $createFromDatabase);
                        $this->createFilesFromDB($dirName, intval($createFromDatabase));
                    }
                    break;
                default :
                    break;
            }
            // Theme build successfully
            \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=ViewManager&act=templates&themes='. $theme->getFoldername());
        } else {
            $this->strErrMessage = $_ARRAYLANG['TXT_STATUS_CHECK_INPUTS'];
            $this->newdir();
        }
    }

    /**
     * Replaces remainders of the original theme in a freshly copied theme.
     * (links to stylesheets, js, images etc.)
     * CAUTION: as this is intended for subroutine-use, no validation or
     * escaping of given parameters is done.
     * @param string $org original theme name
     * @param string $copy copied theme name
     * @param string $copyPath full path to copied theme's directory
     * @return string "error" on error, else empty string
     * @see skins::createDir()
     */
    private function replaceThemeName($org, $copy, $path)
    {
        //extensions of files that could contain links still pointing to the old template
        $regexValidExtensions = '\.css|\.htm|\.html';

        $dir = opendir($path);
        $file = readdir($dir);
        while($file)
        {
            if($file!='.' && $file != '..')
            {
                $ourFile = $path.'/'.$file;
                if(!is_dir($ourFile))
                {
                    //has the file one of our extensions defined above?
                    if(preg_match('/['.$regexValidExtensions.']$/',$ourFile))
                    {
                        //replace name of old template with new template's name
                        $fileContents = file_get_contents($ourFile);
                        $fileContents = str_replace($org,$copy,$fileContents);
                        try {
                            $objFile = new \Cx\Lib\FileSystem\File($ourFile);
                            $objFile->write($fileContents);
                        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                            \DBG::msg($e->getMessage());
                        }
                    }
                }
                else //directory, call this function again to process it
                {
                    $this->replaceThemeName($org,$copy,$ourFile);
                }
            }
            $file=readdir($dir);
        }
    }


    /**
     * Gets the dropdown menu of filesystem dirs which are not in the DB
     * @access   public
     * @global   ADONewConnection
     * @return   string   $nadm
     */
    function getDropdownNotInDb()
    {
        
        $filesList     = $this->fileSystem->getFullFileList('/');

        ksort($filesList);
        $result = '';
        foreach ($filesList as $folderName => $files) {
            if (!$this->themeRepository->findOneBy(array('foldername' => $folderName))) {
                $result .= "<option value='" . $folderName . "'>" . $folderName . "</option>\n";
            }
        }

        return $result;
    }

    /**
     * reading the directories of the file sytem with the specified path
     *
     * @param type $dir
     * @return array $directory
     */
    function readFiles($dirPath) {

        $directory = array();
        foreach(glob($dirPath . '*', GLOB_ONLYDIR) as $dir) {
            $directory[] = str_replace($dirPath, '', $dir);
        }
        return $directory;
    }

    /**
     * update skin file
     * @access   public
     */
    function update()
    {
        \Permission::checkAccess(self::EDIT_THEMES_ACCESS_ID, 'static');

        $themes = !empty($_POST['themes']) && !stristr($_POST['themes'], '..') ? contrexx_input2raw($_POST['themes']) : null;
        $themesPage = !empty($_POST['themesPage']) &&  !stristr($_POST['themesPage'], '..') ? contrexx_input2raw($_POST['themesPage']) : null;
        $isComponentFile = !empty($_POST['is_application']);

        $objImageManager = new \ImageManager;
        if (empty($themes) || empty($themesPage) || $objImageManager->_isImage($this->path.$themes.$themesPage)) {
            return false;
        }

        $pageContent = contrexx_input2raw($_POST['content']);

        // Change the replacement variables from [[TITLE]] into {TITLE}
        $pageContent = preg_replace('/\[\[([A-Z0-9_]+)\]\]/', '{\\1}' ,$pageContent);

        try {
            if (self::isFileTypeComponent($themesPage)) {
                $themesPage = self::getComponentFilePath($themesPage, false);
            }
            if (!file_exists($this->websiteThemesPath.$themes.$themesPage)) {
                $dir = str_replace(basename($themesPage),"", $themesPage);
                \Cx\Lib\FileSystem\FileSystem::make_folder($this->websiteThemesPath.$themes.'/'.$dir, true);
            }
            $filePath = $this->websiteThemesPath.$themes.$themesPage;

            if ($isComponentFile && file_exists($filePath)) {
                // override from application template, rename the file if its already exists
                $pathInfo = pathinfo($filePath);
                $idx = 1;
                while (file_exists($filePath)) {
                  $filePath = $pathInfo['dirname'].'/'.$pathInfo['filename'].'_custom_'.$idx++.'.'.$pathInfo['extension'];
                }
                $_POST['themesPage'] = self::getThemeRelativePath(preg_replace('#' . $this->websiteThemesPath.$themes . '#', '', $filePath));
            }

            $objFile = new \Cx\Lib\FileSystem\File($filePath);
            if(!file_exists($filePath)){
               $objFile->touch();
            }
            $objFile->write($pageContent);

            // temporary hotfix for google chrome
            // remove in case google chrome will no longer throw an ERR_BLOCKED_BY_XSS_AUDITOR exception
            header('X-XSS-Protection: 0');
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
        }
        
        // drop cache:
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $cx->getComponent('Cache')->clearCache();
    }

    /**
     * insert Skin into db and activate the theme on content pages
     *
     * @param   object     $theme
     * @param   string     $themesFolder
     * @param   integer    $themeIdFromDatabaseBasedTheme
     */
    private function insertIntoDb(\Cx\Core\View\Model\Entity\Theme $theme, $themeIdFromDatabaseBasedTheme = null)
    {
        $themeName   = $theme->getThemesname();
        $themeFolder = $theme->getFoldername();
        if (empty($themeName) || empty($themeFolder)) {
            return;
        }

        $themeId = $this->insertSkinIntoDb($theme);

        if ($themeIdFromDatabaseBasedTheme) {
            $pageRepo = \Env::get('em')->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
            $pages = $pageRepo->findBy(array(
                'skin' => intval($themeIdFromDatabaseBasedTheme),
            ));
            foreach ($pages as $page) {
                if ($page->getSkin() != 0) {
                    $page->setSkin($themeId);
                    \Env::get('em')->persist($page);
                }
            }
            \Env::get('em')->flush();
        }
    }

    /**
     * Insert the skin into database
     *
     * @param \Cx\Core\View\Model\Entity\Theme $theme the template object
     *
     * @return  mixed integer on success | false on error
     */
    private function insertSkinIntoDb(\Cx\Core\View\Model\Entity\Theme $theme)
    {
        global $_ARRAYLANG, $objDatabase;

        $objResult = $objDatabase->Execute('INSERT INTO
                                              `'.DBPREFIX.'skins`
                                            SET
                                                `themesname` = "'. contrexx_raw2db($theme->getThemesname()) .'",
                                                `foldername` = "'. contrexx_raw2db($theme->getFoldername()) .'",
                                                `expert`     = 1');

        if ($objResult) {
            return $objDatabase->Insert_ID();
        } else {
            \Message::add($_ARRAYLANG['TXT_THEME_ERROR_IN_INSERT_THEME'], \Message::CLASS_ERROR);
            return false;
        }
    }

    /**
     * Get the file from the given path
     *
     * @param \Cx\Core\View\Model\Entity\Theme  $theme
     * @param string                            $filePath
     * @param boolean                           $isComponentFile
     *
     * @return mixed ViewManagerFile instance or null
     */
    function getFileFromPath(\Cx\Core\View\Model\Entity\Theme $theme, $filePath, $isComponentFile)
    {
        if (empty($filePath)) {
            return null;
        }
        $relativeFilePath = $filePath;
        if (self::isFileTypeComponent($filePath)) {
            $relativeFilePath = self::getComponentFilePath($filePath, $isComponentFile);
        }
        if (!$isComponentFile) {
            $relativeFilePath = '/' .$theme->getFoldername() . $relativeFilePath;
        }
        $localFile = new \Cx\Core\ViewManager\Model\Entity\ViewManagerFile($relativeFilePath, $this->fileSystem);
        $localFile->setApplicationTemplateFile($isComponentFile);

        return $this->fileSystem->isFile($localFile) ? $localFile : null;
    }

    /**
     * Save the library settings which have been done on the overview page
     * @param \Cx\Core\View\Model\Entity\Theme $theme the template object
     */
    protected function saveLibrarySettings($theme)
    {
        global $_ARRAYLANG;

        $libraries = \JS::getConfigurableLibraries();

        // create dependencies array with provided data from form
        $dependencies = array();
        foreach ($_POST['libraryVersion'] as $libraryName => $version) {
            if (empty($version)) continue;
            $dependencies[$libraryName] = array(
                'name' => $libraryName,
                'type' => 'lib',
                'minimumVersionNumber' => $version,
                'maximumVersionNumber' => $version,
            );
        }

        $automaticallyModifiedDependencySettings = array();

        // check for dependencies of configured libraries
        foreach ($dependencies as $dependency) {
            $dependencyIssue = false;
            $libraryInfo = $libraries[$dependency['name']]['versions'][$dependency['minimumVersionNumber']];
            if (isset($libraryInfo['dependencies'])) {
                // loop through dependencies which are required for the activated library
                foreach ($libraryInfo['dependencies'] as $dependencyName => $dependencyVersionRegex) {

                    // dependency not configured or needed version not matching regex
                    if (   !isset($dependencies[$dependencyName])
                        || !preg_match('/' . $dependencyVersionRegex . '/', $dependencies[$dependencyName]['minimumVersionNumber'])) {
                        $dependencyIssue = true;
                    }

                    if ($dependencyIssue) {
                        // find matching library version
                        foreach ($libraries[$dependencyName]['versions'] as $version => $files) {
                            if (preg_match('/' . $dependencyVersionRegex . '/', $version)) {
                                $dependencies[$dependencyName] = array(
                                    'name' => $dependencyName,
                                    'type' => 'lib',
                                    'minimumVersionNumber' => $version,
                                    'maximumVersionNumber' => $version,
                                );
                                $automaticallyModifiedDependencySettings[] = $dependencyName;
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($automaticallyModifiedDependencySettings)) {
            \Message::add(
                sprintf($_ARRAYLANG['TXT_THEME_LIBRARY_AUTOMATICALLY_ADJUSTED'], implode(', ', $automaticallyModifiedDependencySettings)),
                \Message::CLASS_ERROR
            );
        }

        // save component.yaml file
        $theme->setDependencies($dependencies);
        try {
            $this->themeRepository->saveComponentData($theme);
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \Message::add(
                $_ARRAYLANG['TXT_COULD_NOT_WRITE_TO_FILE'] . ': ' . '/' .$theme->getFoldername() . '/component.yml',
                \Message::CLASS_ERROR
            );
        }
    }

    /**
     * Get the library settings column on overview page
     * @param \Cx\Core\View\Model\Entity\Theme $theme the template object
     */
    protected function getLibrarySettings($theme)
    {
        global $_ARRAYLANG, $objTemplate;

        $libraries = \JS::getConfigurableLibraries();

        $objTemplate->setVariable(array(
            'TXT_TEMPLATE_USED_LIBRARIES'     => $_ARRAYLANG['TXT_TEMPLATE_USED_LIBRARIES'],
            'TXT_SAVE'                        => $_ARRAYLANG['TXT_SAVE'],
            'THEMES_SELECTED_THEME'           => $theme->getFoldername(),
        ));
        $objTemplate->setGlobalVariable('TXT_THEME_LIBRARY_NOT_USED', $_ARRAYLANG['TXT_THEME_LIBRARY_NOT_USED']);

        $usedLibraries = $theme->getDependencies();

        // parse available libraries as setting tab
        $objTemplate->setCurrentBlock('theme_library');
        foreach ($libraries as $libraryName => $libraryInfo) {
            foreach ($libraryInfo['versions'] as $version => $files) {
                $objTemplate->setVariable(array(
                    'THEME_LIBRARY_NAME' => $libraryName,
                    'THEME_LIBRARY_VERSION' => $version,
                    'THEME_LIBRARY_VERSION_SELECTED' => isset($usedLibraries[$libraryName]) && $version == $usedLibraries[$libraryName][0] ? 'selected="selected"' : '',
                ));

                $objTemplate->parse('theme_library_version');
            }

            if (array_key_exists($libraryName, $usedLibraries)) {
                $objTemplate->setVariable('THEME_LIBRARY_ACTIVE_CHECKED', 'checked="checked"');
            } else {
                $objTemplate->setVariable('THEME_LIBRARY_VERSION_DROPDOWN_HIDDEN', 'style="display: none;"');
            }

            $objTemplate->setVariable('THEME_LIBRARY_NAME', $libraryName);
            $objTemplate->parseCurrentBlock();
        }
    }

    /**
     * Gets the activated themes dropdown menu
     * @access   public
     * @param    string   $themes (optional)
     * @return   string   $atdm
     */
    function _getDropdownActivated($selectedTheme)
    {
        $activeThemes = $this->themeRepository->getActiveThemes();
        usort($activeThemes, array($this, 'sortThemesByName'));
        $activeThemeIds = array();
        foreach ($activeThemes as $theme) {
            $activeThemeIds[] = $theme->getId();
        }

        $themes = $this->themeRepository->findAll(array('themesname', 'id'));
        usort($themes, array($this, 'sortThemesByName'));
        foreach ($themes as $key => $theme) {
            if (in_array($theme->getId(), $activeThemeIds)) {
                unset($themes[$key]);
            }
        }

        $themes = array_merge($activeThemes, $themes);

        $selectedTheme = $this->themeRepository->findById($selectedTheme);
        $html = '';
        foreach ($themes as $theme) {
            $selected = '';
            if ($theme == $selectedTheme) {
                $selected = 'selected="selected"';
            }
            $html .= '<option value="'.$theme->getId().'" '.$selected.'>'.contrexx_raw2xhtml($theme->getThemesname()).'</option>';
            $html .= "\n";
        }
        return $html;
    }

    /**
     * Gets the themes dropdown menu
     * @param    string   $selectedTheme the currently selected theme
     * @param    boolean  $selectDefault pre select default theme if no theme is selected yet
     * @return string $tdm the html code for the drop down
     */
    function getThemesDropdown($selectedTheme = null, $selectDefault = true)
    {
        global $_ARRAYLANG;
        if (!$selectedTheme && $selectDefault) {
            $selectedTheme = $this->themeRepository->getDefaultTheme();
        }

        $activeThemes = $this->themeRepository->getActiveThemes();
        usort($activeThemes, array($this, 'sortThemesByName'));
        $activeThemeIds = array();
        foreach ($activeThemes as $theme) {
            $activeThemeIds[] = $theme->getId();
        }

        $themes = $this->themeRepository->findAll(array('themesname', 'id'));
        usort($themes, array($this, 'sortThemesByName'));
        foreach ($themes as $key => $theme) {
            if (in_array($theme->getId(), $activeThemeIds)) {
                unset($themes[$key]);
            }
        }

        $themes = array_merge($activeThemes, $themes);

        $tdm = '';
        foreach ($themes as $item) {
            $default = "";
            $mobilestyle = "";
            $printstyle = "";
            $pdfstyle = "";
            $appstyle = "";
            if ($item->isDefault(\Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB)){
                $default = "(".$_ARRAYLANG['TXT_DEFAULT'].")";
            }
            if ($item->isDefault(\Cx\Core\View\Model\Entity\Theme::THEME_TYPE_MOBILE)){
                $mobilestyle = "(".$_ARRAYLANG['TXT_ACTIVE_MOBILE_TEMPLATE'].")";
            }
            if ($item->isDefault(\Cx\Core\View\Model\Entity\Theme::THEME_TYPE_PRINT)){
                $printstyle = "(".$_ARRAYLANG['TXT_THEME_PRINT'].")";
            }
            if ($item->isDefault(\Cx\Core\View\Model\Entity\Theme::THEME_TYPE_PDF)){
                $pdfstyle = "(".$_ARRAYLANG['TXT_THEME_PDF'].")";
            }
            if ($item->isDefault(\Cx\Core\View\Model\Entity\Theme::THEME_TYPE_APP)){
                $appstyle = "(".$_ARRAYLANG['TXT_APP_VIEW'].")";
            }
            $selected = ($selectedTheme && $selectedTheme->getId() == $item->getId()) ? $selected = "selected" : '';
            $tdm .='<option id="'.$item->getId()."\" value='".$item->getFoldername()."' $selected>".  contrexx_raw2xhtml($item->getThemesname())." ".$default.$mobilestyle.$printstyle.$pdfstyle.$appstyle."</option>\n";
        }
        return $tdm;
    }


    /**
     * Gets the themes dropdown menu
     * @access   private
     * @global   ADONewConnection
     * @param    string   $themes (optional)
     * @return   string   $tdm
     */
    function _getThemesDropdownDelete()
    {
        $html = '';
        $themes = $this->themeRepository->findAll();
        usort($themes, array($this, 'sortThemesByDefault'));
        foreach ($themes as $theme) {
            if ($theme->isDefault()) continue;
            $html .= "<option value='".contrexx_raw2xhtml($theme->getFoldername())."'>".contrexx_raw2xhtml($theme->getThemesname())."</option>\n";
        }
        return $html;
    }

    /**
     * Sets the drop down content for the files and create overrides tab
     *
     * @param object  $theme           active theme's object (\Cx\Core\View\Model\Entity\Theme)
     * @param string  $themesPage      Currently active themes page
     * @param boolean $isComponentFile request made for type component file or theme file
     */
    function getFilesDropdown($theme, $themesPage, $isComponentFile)
    {
        global $_ARRAYLANG, $objTemplate;

        //Get the components Frontend templates
        $cx = \Env::get('cx');
        $em = $cx->getDb()->getEntityManager();
        $objSystemComponent = $em->getRepository('Cx\\Core\\Core\\Model\\Entity\\SystemComponent');
        $components = $objSystemComponent->findAll();
        $componentFiles = array();
        foreach ($components as $component) {
            foreach (array('Template/Frontend', 'Style') as $offset) {
                $componentDirectory = $cx->getClassLoader()->getFilePath(
                    $component->getDirectory(false) . '/View/' . $offset
                );
                if (file_exists($componentDirectory)) {
                    foreach (glob("$componentDirectory/*") as $componentFile) {
                        if (
                            substr($componentFile, -3, 3) == 'css' &&
                            substr($componentFile, -12, 12) != 'Frontend.css'
                        ) {
                            continue;
                        }
                        if (!isset($componentFiles[$component->getType()])) {
                            $componentFiles[$component->getType()] = array();
                        }
                        $componentFiles[$component->getType()][$component->getName()][]= basename($componentFile);
                    }
                }
            }
        }
        $this->sortFilesFolders($componentFiles);

        $mergedFiles = $this->getThemesFiles($theme);
        $objTemplate->setVariable(array(
            'THEME_FILES_TAB'                            => $this->getUlLi($mergedFiles, '', 'theme', !$isComponentFile ? $themesPage : '', $theme),
            'THEME_OVERRIDE_TAB'                         => $this->getUlLi($componentFiles, '', 'applicationTheme', $isComponentFile ? $themesPage : ''),
            'TXT_DESIGN_LAYOUT'                          => $_ARRAYLANG['TXT_DESIGN_LAYOUT'],
            'TXT_DESIGN_APPLICATION_TEMPLATE'            => $_ARRAYLANG['TXT_DESIGN_APPLICATION_TEMPLATE'],
            'TXT_DESIGN_CONTENT_TEMPLATE'                => $_ARRAYLANG['TXT_DESIGN_CONTENT_TEMPLATE'],
            'TXT_DESIGN_HOME_TEMPLATE'                   => $_ARRAYLANG['TXT_DESIGN_HOME_TEMPLATE'],
            'TXT_THEME_NEW_WITHIN'                       => $_ARRAYLANG['TXT_THEME_NEW_WITHIN'],
            'TXT_THEME_COPY'                             => $_ARRAYLANG['TXT_THEME_COPY'],
            'TXT_THEME_RENAME'                           => $_ARRAYLANG['TXT_THEME_RENAME'],
            'TXT_THEME_DELETE'                           => $_ARRAYLANG['TXT_THEME_DELETE'],
            'TXT_THEME_ACTIONS'                          => $_ARRAYLANG['TXT_THEME_ACTIONS'],
        ));
    }

    /**
     * Sorting the array recursively
     *
     * @param array $mergedFiles - merged array
     */
    function sortFilesFolders(& $mergedFiles) {
        $tmp1 = array();
        $tmp2 = array();
        foreach ($mergedFiles as $key => $value) {
            if (is_array($value)) {
                $tmp1[$key] = $value;
            } else {
                $tmp2[] = $value;
            }
        }

        if ($tmp1) {
            uksort($tmp1, 'strcasecmp');
            foreach ($tmp1 as $key => & $value) {
                $this->sortFilesFolders($value);
            }
        }
        if ($tmp2) {
            uasort($tmp2, 'strcasecmp');
        }

        $mergedFiles = array_merge($tmp1, $tmp2);
    }

    /**
     * Getting the files and folders in a ul li format for the js tree
     *
     * @param array  $folder     array of files and folders
     * @param string $path       current path of the $folder array
     * @param string $block      type of the folder array (theme or applicationTheme)
     * @param string $themesPage selected file in the ul li
     * @param mixed  $theme      Currently selected theme
     *
     * @return string formatted ul and li for the js tree
     */
    function getUlLi($folder, $path, $block, $themesPage, $theme = null) {
        $result           = '<ul>';
        $virtualFolder    = array('View', 'Template', 'Frontend');
        $isApplicationTab = $block == 'applicationTheme';
        foreach ($folder as $folderName => $fileName) {
            $resetClass   = '';
            $relativePath = $path . '/' . (is_array($fileName) ? $folderName .'/' : $fileName);

            $isComponentFile = false;
            if (self::isFileTypeComponent($relativePath)) {
                $componentFilePath = self::getComponentFilePath($relativePath, $isApplicationTab);
                if (!$componentFilePath) { // may be a folder
                    $componentFilePath = self::replaceComponentFolderByItsType($relativePath);
                }
                $isComponentFile   = true;
            }

            $filePath  = $isComponentFile ? $componentFilePath : $relativePath;
            if ($block == 'theme') {
                $filePath = $theme->getFolderName() . $filePath;
            }
            $localFile = new \Cx\Core\ViewManager\Model\Entity\ViewManagerFile($filePath, $this->fileSystem);
            $localFile->setApplicationTemplateFile($isApplicationTab);

            $permissionClass = $isComponentFile || $this->fileSystem->isReadOnly($localFile) ? 'protected' : '';
            if ($this->fileSystem->isResettable($localFile)) {
                $permissionClass = 'protected';
                $resetClass      = 'reset';
            }

            if (is_array($fileName)) {

                if (   $block == 'applicationTheme'
                    || (
                           $block == 'theme'
                        && !in_array($folderName, $virtualFolder)
                       )
                   ) {
                    $icon         = "<img height='16' width='16' alt='icon' src='" . \Cx\Core_Modules\Media\Controller\MediaLibrary::_getIconWebPath() . "Folder.png' class='icon'>";
                    $activeFolder = preg_match('#^'. $relativePath .'# i', $themesPage) ? "id='activeFolder'" : '';

                    $result .= '<li><a  href="javascript:void(0);"' .$activeFolder. ' data-rel="'. $relativePath .'" class="folder naming '. $permissionClass .' '. $resetClass .'">' . $icon . $folderName . '</a>';
                }
                $result .= $this->getUlLi($fileName, $path .(!in_array($folderName, $virtualFolder) ? '/'. $folderName : ''), $block, $themesPage, $theme);
                $result .= '</li>';
            } else {
                if (in_array($fileName, $this->filenames)) {
                    $iconSrc = '../core/ViewManager/View/Media/Config.png';
                } else {
                    $iconSrc  = \Cx\Core_Modules\Media\Controller\MediaLibrary::_getIconWebPath(); 
                    $iconSrc .= \Cx\Core_Modules\Media\Controller\MediaLibrary::_getIcon(
                        $this->fileSystem->getFullPath($localFile) . $localFile->getFullName()
                    ) . '.png';
                }

                $icon    = "<img height='16' width='16' alt='icon' src='" . $iconSrc . "' class='icon'>";
                $activeFile = ($themesPage == $relativePath) ? "id = 'activeFile'" : '';

                $result .= "<li><a  href= 'javascript:void(0);' class='loadThemesPage naming $permissionClass $resetClass' $activeFile data-rel='" . $relativePath . "'>" . $icon . $fileName . "</a></li>";
            }
        }
        $result .= '</ul>';

        return $result;
    }

    /**
     * Gets the themes pages file content
     *
     * @param \Cx\Core\ViewManager\Model\Entity\ViewManagerFile $file
     * @return null
     */
    function getFilesContent(\Cx\Core\ViewManager\Model\Entity\ViewManagerFile $file)
    {
        global $objTemplate, $_ARRAYLANG;

        if (!$this->fileSystem->fileExists($file)) {
            return;
        }

        if ($this->fileSystem->isImageFile($file)) {
            $objTemplate->setVariable(array(
                'THEMES_CONTENT_IMAGE_PATH' => $this->cx->getThemesFolderName() . $file->__toString(),
            ));
            $objTemplate->touchBlock('template_image');
            $objTemplate->hideBlock('template_content');
            $objTemplate->hideBlock('file_actions_bottom');
            $objTemplate->hideBlock('file_editor_fullscreen');
        } else {
            \JS::activate('ace');

            // fetch content from file
            $content = $this->fileSystem->readFile($file);

            // replace placeholder format
            $content = preg_replace('/\{([A-Z0-9_]+)\}/', '[[\\1]]', $content);

            // escape special characters
            $contenthtml = htmlspecialchars($content);

            // check if file contains invalid characters
            if (
                strlen($content) &&
                !strlen($contenthtml)
            ) {
                // replace invalid code unit sequences with a Unicode
                // Replacement Character U+FFFD
                $contenthtml = htmlspecialchars($content, ENT_SUBSTITUTE);


                $invalidFileMessage = sprintf(
                    $_ARRAYLANG['TXT_VIEWMANAGER_INVALID_FILE_ENCODING_MSG'],
                    contrexx_raw2xhtml($file)
                );
                $confirmFileStorage = $invalidFileMessage . sprintf(
                    $_ARRAYLANG['TXT_VIEWMANAGER_CONFIRM_INVALID_FILE_ENCODING'],
                    contrexx_raw2xhtml($file)
                );

                // add warning box regarding done replacement
                $objTemplate->setVariable(array(
                    'VIEWMANAGER_INVALID_FILE_ENCODING' => $invalidFileMessage,
                    'VIEWMANAGER_STORE_INVALID_ENCODING' => $confirmFileStorage,
                ));
                $objTemplate->parse('viewmanager_invalid_encoding');

                \ContrexxJavascript::getInstance()->setVariable(
                    'fileEncodingIsInvalid', true, 'ViewManager'
                );
            } else {
                $objTemplate->hideBlock('viewmanager_invalid_encoding');
            }

            $objTemplate->setVariable('CONTENT_HTML', $contenthtml);
            $pathInfo = pathinfo(
                $this->fileSystem->getFullPath($file) . $file->getFullName(),
                PATHINFO_EXTENSION
            );
            $mode = 'html';

            switch($pathInfo) {
                case 'html':
                case 'css':
                    $mode = $pathInfo;
                break;
                case 'js':
                    $mode = 'javascript';
                break;
                case 'yml':
                case 'yaml':
                    $mode = 'yaml';
                break;
            }

            $jsCode = <<<CODE
var editor;                        
\$J(function(){         
if (\$J("#editor").length) {
    editor = ace.edit("editor");
    editor.getSession().setMode("ace/mode/$mode");
    editor.setShowPrintMargin(false);
    editor.commands.addCommand({
            name: "fullscreen",
            bindKey: "F11",
            exec: function(editor) {
                    if (\$J('body').hasClass('fullScreen')) {
                        \$J('body').removeClass('fullScreen');
                        \$J(editor.container).removeClass('fullScreen-editor');
                        cx.tools.StatusMessage.removeAllDialogs();
                    } else {
                        \$J('body').addClass('fullScreen');
                        \$J(editor.container).addClass('fullScreen-editor');
                        cx.tools.StatusMessage.showMessage(
                            "<div style='text-align: center;'><span style='cursor: pointer;' onClick=\"editor.execCommand('fullscreen');\">{$_ARRAYLANG['TXT_THEME_EXIT_FULLSCREEN']}</span></div>",
                            null,
                            null,
                            null,
                            {closeOnEscape: false}
                        );
                    }
                    editor.resize();
                    editor.focus();
            }
    });
    editor.focus();
    editor.gotoLine(1);

    \$J('.fullscreen').click(function(){
        editor.execCommand('fullscreen');
    });
}

\$J('#theme_content').submit(function(){
    \$J('#editorContent').val(editor.getSession().getValue());
});

\$J('.select_all').click(function() {
    editor.selectAll();
});

\$J('input:reset').click(function() {
    editor.setValue(\$J('#editorContent').val(), -1);
});

});
CODE;

            \JS::registerCode($jsCode);

            $objTemplate->touchBlock('file_editor_fullscreen');
            $objTemplate->touchBlock('file_actions_bottom');
            $objTemplate->touchBlock('template_content');
            $objTemplate->hideBlock('template_image');
        }
    }

    /**
     * check the given path is component file
     *
     * @param string $path
     *
     * @return boolean
     */
    public static function isFileTypeComponent($path) {
        if (empty($path)) {
            return false;
        }
        //Check for Core Modules
        if (preg_match('#^\/'. \Cx\Core\Core\Model\Entity\SystemComponent::TYPE_CORE_MODULE .'/#i', $path)) {
            return true;
        }

        //Check for Modules
        if (preg_match('#^\/'. \Cx\Core\Core\Model\Entity\SystemComponent::TYPE_MODULE .'/#i', $path)) {
            return true;
        }

        return false;
    }

    public static function replaceComponentFolderByItsType($path) {
        if (empty($path)) {
            return false;
        }
        //Check for Core Modules
        if (preg_match('#^\/'. \Cx\Core\Core\Model\Entity\SystemComponent::TYPE_CORE_MODULE .'#i', $path)) {
            return preg_replace('#^\/'. \Cx\Core\Core\Model\Entity\SystemComponent::TYPE_CORE_MODULE .'#i', \Env::get('cx')->getCoreModuleFolderName(), $path);
        }

        //Check for Modules
        if (preg_match('#^\/'. \Cx\Core\Core\Model\Entity\SystemComponent::TYPE_MODULE .'#i', $path)) {
            return preg_replace('#^\/'. \Cx\Core\Core\Model\Entity\SystemComponent::TYPE_MODULE .'#i', \Env::get('cx')->getModuleFolderName(), $path);
        }

        return false;
    }

    /**
     * Get the component's file path
     *
     * @param string $path
     *
     * @return boolean | string
     */
    public static function getComponentFilePath($path, $loadFromComponentDir = true) {
        if (empty($path)) {
            return false;
        }
        $arrPath = explode('/', $path);
        $moduleName = $arrPath[2];

        if (count($arrPath) > 3) { // file name not exits
            $fileName   = $arrPath[count($arrPath) - 1];
        } else {
            return false;
        }

        $offset = 'Template/Frontend';
        if (substr($path, -3, 3) == 'css') {
            $offset = 'Style';
        }
        //get the Core Modules File path
        if (preg_match('#^\/'. \Cx\Core\Core\Model\Entity\SystemComponent::TYPE_CORE_MODULE .'#i', $path)) {
            return \Env::get('cx')->getCoreModuleFolderName() .'/'.$moduleName . ($loadFromComponentDir ? '/View' : '') .'/'.$offset.'/' . $fileName;
        }

        //get the Modules File path
        if (preg_match('#^\/'. \Cx\Core\Core\Model\Entity\SystemComponent::TYPE_MODULE .'#i', $path)) {
            return \Env::get('cx')->getModuleFolderName() .'/'. $moduleName . ($loadFromComponentDir ? '/View' : '') .'/'.$offset.'/' . $fileName;
        }

        return false;
    }

    /**
     * Get the relative path of a file
     *
     * @param string $path absolute path of a file
     *
     * @return string relative path to the file
     */
    public static function getThemeRelativePath($path) {
        if (empty($path)) {
            return $path;
        }
        $arrPath    = explode('/', $path);
        $moduleName = $arrPath[2];

        if (count($arrPath) > 3) { // file name not exits
            $fileName   = $arrPath[count($arrPath) - 1];
        } else {
            return $path;
        }

        //get the Core Modules File path
        if (preg_match('#^'. \Env::get('cx')->getCoreModuleFolderName() .'#i', $path)) {
            return '/'. \Cx\Core\Core\Model\Entity\SystemComponent::TYPE_CORE_MODULE .'/'. $moduleName . '/' . $fileName;
        }

        //get the Modules File path
        if (preg_match('#^'. \Env::get('cx')->getModuleFolderName() .'#i', $path)) {
            return '/'. \Cx\Core\Core\Model\Entity\SystemComponent::TYPE_MODULE .'/'. $moduleName . '/' . $fileName;
        }

        return $path;
    }

    /**
     * Create default theme files
     *
     * \Cx\Core\View\Model\Entity\Theme $theme
     */
    private function createDefaultFiles(\Cx\Core\View\Model\Entity\Theme $theme)
    {
        global $_ARRAYLANG;

        foreach ($this->directories as $dir) {
            if (!\Cx\Lib\FileSystem\FileSystem::make_folder($this->path . $theme->getFoldername() . '/' . $dir)) {
                \Message::add(
                    sprintf($_ARRAYLANG['TXT_UNABLE_TO_CREATE_FILE'], contrexx_raw2xhtml($theme->getFoldername() .'/'. $dir)),
                    \Message::CLASS_ERROR
                );
                return false;
            }
        }

        //copy "not available" preview.gif as default preview image
        $previewImage = $this->path . $theme->getFoldername() . \Cx\Core\View\Model\Entity\Theme::THEME_PREVIEW_FILE;
        if (!file_exists($previewImage)) {
            try {
                $objFile = new \Cx\Lib\FileSystem\File(\Env::get('cx')->getCodeBaseDocumentRootPath() . \Cx\Core\View\Model\Entity\Theme::THEME_DEFAULT_PREVIEW_FILE);
                $objFile->copy($previewImage);
            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                \DBG::msg($e->getMessage());
                \Message::add(
                    sprintf($_ARRAYLANG['TXT_UNABLE_TO_CREATE_FILE'], contrexx_raw2xhtml($theme->getFoldername() . \Cx\Core\View\Model\Entity\Theme::THEME_PREVIEW_FILE)),
                    \Message::CLASS_ERROR
                );
                return false;
            }
        }

        foreach ($this->filenames as $file) {
            // skip component.yml, will be created later
            if ($file == 'component.yml') continue;
            $filePath = $this->path . $theme->getFoldername() .'/'. $file;
            if (!file_exists($filePath)) {
                try {
                    $objFile = new \Cx\Lib\FileSystem\File($filePath);
                    $objFile->touch();
                } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                    \DBG::msg($e->getMessage());
                    \Message::add(
                        sprintf($_ARRAYLANG['TXT_UNABLE_TO_CREATE_FILE'], contrexx_raw2xhtml($theme->getFoldername() .'/'. $file)),
                        \Message::CLASS_ERROR
                    );
                    return false;
                }
            }
        }

        // write component.yml file
        // this line will create a default component.yml file
        try {
            $this->themeRepository->loadComponentData($theme);
            $this->themeRepository->convertThemeToComponent($theme);
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            \Message::add(
                $_ARRAYLANG['TXT_UNABLE_TO_CONVERT_THEME_TO_COMPONENT'],
                \Message::CLASS_ERROR
            );
        }

        return true;
    }

    /**
     * check iftable exists
     * @access   public
     * @param    string   $table
     */
    function checkTable($table)
    {
        global $objDatabase;
        $arrTables = array();
        // get tables in database
        $arrTables = $objDatabase->MetaTables('TABLES');
        if ($arrTables !== false) {
            if (in_array($table, $arrTables)) {
                $this->tableExists = "tblexists";
                $this->getDbDropdown($table);
                $this->dropTable($this->oldTable);
            }
        }
    }

    /**
     * create db themes dropdownmenu
     * @access   public
     */
    function getDbDropdown()
    {
        global $objDatabase, $objTemplate, $_ARRAYLANG;
        if ($this->tableExists == "tblexists") {
            $tdm = "<select name='fromDB' onchange='existingdirNameValue2()' size='1' style='WIDTH: 150px'>";
            $tdm .="<option value=''>--- Aus Datenbank ----------</option>";
            $objResult = $objDatabase->query("SELECT id,themesname FROM ".$this->oldTable." ORDER BY id");
            $defaultThemeId = $this->selectDefaultTheme();
            $default = '';
            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    if ($objResult->fields['id'] == $defaultThemeId){
                        $default = "(".$_ARRAYLANG['TXT_DEFAULT'].")";
                    }
                    $tdm .="<option value='".$objResult->fields['id']."'>".$objResult->fields['themesname']." ".$default."</option>\n";
                    $default='';
                    $objResult->MoveNext();
                }
            }
            $tdm .="</select>";
            $objTemplate->setVariable('TXT_FROM_DB',$tdm);
        }
    }

    /**
     * create files from db
     * @param string $themes
     * @param string $fromDB
     */
    function createFilesFromDB($themes, $fromDB)
    {
// TODO: remove this function -> migrate all pending themes in the update process
// TODO: migrate to new filesystem \Cx\Lib\FileSystem
        global $objDatabase, $_ARRAYLANG;

        $themePages = array();

        $objResult = $objDatabase->Execute("SELECT * FROM ".$this->oldTable." WHERE id='".$fromDB."'");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $themePages['index.html'] = stripslashes($objResult->fields['indexpage']);
                $themePages['content.html'] =  stripslashes($objResult->fields['content']);
                $themePages['home.html'] =  stripslashes($objResult->fields['home']);
                $themePages['navbar.html'] =  stripslashes($objResult->fields['navbar']);
                $themePages['subnavbar.html'] =  stripslashes($objResult->fields['subnavbar']);
                $themePages['sidebar.html'] =  stripslashes($objResult->fields['sidebar']);
                $themePages['shopnavbar.html'] =  stripslashes($objResult->fields['shopnavbar']);
                $themePages['headlines.html'] =  stripslashes($objResult->fields['headlines']);
                $themePages['javascript.js'] =  stripslashes($objResult->fields['javascript']);
                $themePages['style.css'] =  stripslashes($objResult->fields['style']);
                $objResult->MoveNext();
            }
        }

        for($x = 0; $x < count($this->filenames); $x++) {
            $fp = fopen ($this->path.$themes.'/'.$this->filenames[$x] ,"w");
            fwrite($fp,"");
            fclose($fp);

            $filename = $this->path.$themes.'/'.$this->filenames[$x];

            //check, if file exists and is writable
            if (\Cx\Lib\FileSystem\FileSystem::makeWritable($filename)) {
                //open file
                if (!$handle = fopen($filename, "a")) {
                     $this->strErrMessage = $_ARRAYLANG['TXT_STATUS_CANNOT_OPEN'];
                }
                //write file
                if (!fwrite($handle, $themePages[$this->filenames[$x]])) {
                    $this->strErrMessage =  $_ARRAYLANG['TXT_STATUS_CANNOT_WRITE'];
                }
                fclose($handle);
                $this->strOkMessage = $_ARRAYLANG['TXT_STATUS_SUCCESSFULLY_CREATE'];
                $this->overview();
            } else {
                $this->strErrMessage = $_ARRAYLANG['TXT_STATUS_CANNOT_WRITE'];
            }
        }
    }

    /**
     * Sorts the themes by default value. that means,
     * the themes which have been set as default for a theme type,
     * they are listed first.
     * @param Cx\Core\View\Model\Entity\Theme $a theme 1
     * @param Cx\Core\View\Model\Entity\Theme $b theme 2
     * @return int
     */
    public function sortThemesByDefault($a, $b) {
        if ($a->isDefault() && $b->isDefault()) {
            return 0;
        }
        if ($a->isDefault() && !$b->isDefault()) {
            return -1;
        }
        return 1;
    }

    public function sortThemesByName($a, $b) {
        return strcmp($a->getThemesname(), $b->getThemesname());
    }

    /**
     * if now rows in table -> drop $this->oldTable
     * @access   public
     */
    function dropTable()
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("SELECT id FROM ".$this->oldTable." ORDER BY id");
        if ($objResult) {
            if ($objResult->RecordCount() == 0) {
                $objDatabase->Execute("DROP TABLE ".$this->oldTable);
            }
        }
    }

    /**
     * Gets the right frontend entity by lang id and channel,
     * updates the theme id (when neccessary) and persists the updated entity
     *
     * @param int langId language id of the frontend entity
     * @param string channel used channel of the frontend entity
     * @param int themeId theme id of the frontend entity
     */
    protected function activateFrontendTheme($langId, $channel, $themeId) {
        $frontendRepo = $this->em->getRepository('\Cx\Core\View\Model\Entity\Frontend');
        // search for frontend with given language and channel
        $criteria = array(
            'language' => $langId,
            'channel' => $channel
        );
        $frontend = $frontendRepo->findOneBy($criteria);
        if ($frontend->getTheme() != $themeId) {
            $frontend->setTheme($themeId);
            $this->em->persist($frontend);
        }
    }
}
