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
 * Initialize the CMS
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 * @todo        Edit PHP DocBlocks!
 */

/**
 * InitCMSException
 *
 * @copyright   Cloudrexx AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 * @version     5.0.0
 */
class InitCMSException extends \Exception {}

/**
 * Initialize the CMS
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 * @todo        Any methods handling content or language should be moved
 *              away from here to a distinct class!
 * @todo        Most if not all of the properties and methods are potentially
 *              static.
 */
class InitCMS
{
    public $defaultBackendLangId;
    public $backendLangId;

    public $defaultFrontendLangId;
    public $frontendLangId;
    public $frontendLangName;
    public $userFrontendLangId;

    public $currentThemesId;
    public $channelThemeId;
    /**
     * ID of the theme that has been used for generating the response
     */
    public $pageThemeId;
    public $customContentTemplate = null;
    public $arrLang = array();
    public $arrBackendLang = array();
    public $arrLangNames = array();
    public $arrBackendLangNames = array();
    public $templates = array();
    public $arrModulePath = array();

    /**
     * Current view type(web, app, mobile, etc)
     *
     * @var string
     */
    protected $currentChannel;

    /**
    * int $isMobileDevice
    * whether we're dealing with a mobile device.
    * values 1 or 0.
    * @see InitCMS::checkForMobileDevice()
    * @see InitCMS::setCustomizedTheme()
    * @access private
    */
    private $isMobileDevice = 0;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em = null;

    private $themesPath;

    /**
     * Either "frontend" or "backend"
     * @var   string
     */
    public $mode;

    protected $themeRepository;

    /**
     * @var array Language var cache
     */
    protected $moduleSpecificLanguageData = array();

    /**
     * Constructor
     */
    function __construct(
        $mode = \Cx\Core\Core\Controller\Cx::MODE_FRONTEND,
        $entityManager = null
    ) {
        // TODO: what is this used for?
        $this->em = $entityManager;
        $this->mode=$mode;

        // frontend
        $this->arrLang = \FWLanguage::getActiveFrontendLanguages();
        $this->defaultFrontendLangId = \FWLanguage::getDefaultLangId();
        $this->arrLangNames = \FWLanguage::getNameArray('frontend');
        // backend
        $this->arrBackendLang = \FWLanguage::getActiveBackendLanguages();
        $this->defaultBackendLangId = \FWLanguage::getDefaultBackendLangId();
        $this->arrBackendLangNames = \FWLanguage::getNameArray('backend');

        if ($mode == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            //$this->_initBackendLanguage();
            $this->getUserFrontendLangId();
        }

        $this->loadModulePaths();
        $this->themeRepository = new \Cx\Core\View\Model\Repository\ThemeRepository();
    }


    /**
     * Backend language initialization
     */
    function _initBackendLanguage()
    {
        $objFWUser = FWUser::getFWUserObject();

        // defaults
        $backendLangId = $this->defaultBackendLangId;
        $setUserLanguage = false;

        // if the user is logged in, take the users backend language
        if ($objFWUser->objUser->login(true)) {
            $backendLangId = $objFWUser->objUser->getBackendLanguage();
        }

        // the user want to change the language
        if (!empty($_REQUEST['setLang'])) {
            $backendLangId = intval($_REQUEST['setLang']);
            $setUserLanguage = true;
        } elseif (!empty($_COOKIE['backendLangId'])) {
            // the language already has changed for the backend, but he hasn't been logged in at this time
            // (perhaps on login page)
            $setUserLanguage = true;
            $backendLangId = intval($_COOKIE['backendLangId']);
        }

        // the language is activated for the backend
        if (empty($this->arrBackendLang[$backendLangId]['backend'])) {
            $backendLangId = $this->defaultBackendLangId;
        }

        // set the users default backend language and store it into the db if he has changed the language manually
        if ($setUserLanguage && $objFWUser->objUser->login(true) && $objFWUser->objUser->getBackendLanguage() != $backendLangId) {
            $objFWUser->objUser->setBackendLanguage($backendLangId);
            $objFWUser->objUser->store();

            // delete cookie for authenticated users
            setcookie('backendLangId', '', time() - 3600, ASCMS_PATH_OFFSET.'/');
        }

        $this->backendLangId = $this->arrBackendLang[$backendLangId]['id'];
        // TODO: this is obsolete, isn't it?
        //$this->currentThemesId = $this->arrBackendLang[$backendLangId]['themesid'];

        // Set a COOKIE to remember the selected backend language.
        // But do this only for non-authenticated users, as authenticated
        // users have the selected backend language being stored in their profile.
        if (!$objFWUser->objUser->login(true)) {
            setcookie('backendLangId', $backendLangId, time()+3600*24*30, ASCMS_PATH_OFFSET.'/');
        }
    }


    function getFallbackFrontendLangId()
    {
        // Frontend language initialization
        $setCookie = false;

        if (!empty($_REQUEST['setLang'])) {
            $langId = intval($_REQUEST['setLang']);
            $setCookie = true;
        } elseif (!empty($_GET['langId'])) {
            $langId = intval($_GET['langId']);
        } elseif (!empty($_POST['langId'])) {
            $langId = intval($_POST['langId']);
        } elseif (!empty($_COOKIE['langId'])) {
            $langId = intval($_COOKIE['langId']);
            $setCookie = true;
        } else {
            $langId = $this->_selectBestLanguage();
        }

        if ($this->arrLang[$langId]['frontend'] != 1) {
            $langId = $this->defaultFrontendLangId;
        }

        if ($setCookie) {
            setcookie("langId", $langId, time()+3600*24*30, ASCMS_PATH_OFFSET.'/');
        }

        return $langId;
    }


    public function setFrontendLangId($langId)
    {
        $this->frontendLangId = $langId;

        // This must not be called before setting $this->frontendLangId
        $this->checkForMobileDevice();

        // Load print template
        if (isset($_GET['printview']) && $_GET['printview'] == 1) {
            $this->currentThemesId = $this->arrLang[$this->frontendLangId]['print_themes_id'];
            $this->currentChannel  = \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_PRINT;
        }
        // Load PDF template
        elseif (isset($_GET['pdfview']) && $_GET['pdfview'] == 1){
            $this->currentThemesId = $this->arrLang[$this->frontendLangId]['pdf_themes_id'];
            $this->currentChannel  = \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_PDF;
        }
        // Load app template
        elseif (isset($_GET['appview']) && $_GET['appview'] == 1) {
            $this->currentThemesId = $this->arrLang[$this->frontendLangId]['app_themes_id'];
            $this->currentChannel  = \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_APP;
        }
        // Load mobile template
        elseif ($this->isMobileDevice and $this->arrLang[$this->frontendLangId]['mobile_themes_id']) {
            $this->currentThemesId = $this->arrLang[$this->frontendLangId]['mobile_themes_id'];
            $this->currentChannel  = \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_MOBILE;
        }
        // Load regular content template
        else {
            $this->currentThemesId = $this->arrLang[$this->frontendLangId]['themesid'];
            $this->currentChannel  = \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB;
        }

        $this->channelThemeId = $this->currentThemesId;
    }


    function checkForMobileDevice()
    {
        // small screen view (mobile etc). use index.php?smallscreen=1 to
        // enable, ?smallscreen=0 to disable.
        $this->isMobileDevice = 0;
        // only set the smallscreen environment if there's actually a mobile theme defined.
        if (isset($_GET['smallscreen']) ) {
            // user wants to enable/disable smallscreen mode.
            if ($_GET['smallscreen'] && $this->arrLang[$this->frontendLangId]['mobile_themes_id']) {
                // enable
                setcookie('smallscreen', 1, 0, ASCMS_PATH_OFFSET.'/');
                $this->isMobileDevice = 1;
            }
            else {
                // now: either smallscreen=1 requested, but no smallscreen theme
                // available, or disabling requested. Both cases require the
                // cookie to be set to zero, so the javascript doesn't redirect
                // all the time!
                setcookie('smallscreen', 0, 0, ASCMS_PATH_OFFSET.'/');
                $this->isMobileDevice = 0;
            }
        }
        elseif(isset($_COOKIE['smallscreen'])) {
            // no need to check mobile_themes_id here: it's been checked
            // when the cookie was set.
            $this->isMobileDevice =intval($_COOKIE['smallscreen']);
        }
        else {
            // auto detection
            if (self::_is_mobile_phone() && !self::_is_tablet() && $this->arrLang[$this->frontendLangId]['mobile_themes_id']) {
                // same here: only set smallscreen mode if there IS a smallscreen theme
                setcookie('smallscreen', 1, 0, ASCMS_PATH_OFFSET.'/');
                $this->isMobileDevice = 1;
            }
            else {
                // Don't even think about setting the cookie
                // to 0 in this case: 0 means the user disabled
                // smallscreen mode INTENTIONALLY! The friendly javascript
                // detector only enables smallscreen mode if the user
                // didn't decide by himself.
            }
        }
    }


    /**
     * Returns the locale ID best matching the client's request
     *
     * If no match can be found, returns the default locale ID.
     *
     * @return int The locale ID
     */
    function _selectBestLanguage()
    {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        return \Cx\Core\Locale\Controller\ComponentController::selectBestLocale(
            $cx,
            $cx->getComponent('Locale')->getLocaleData()
        );
    }

    /**
     * Returns the selected User Frontend Language id
     *
     * Backend use only!
     * @return   string $this->userFrontendLangId
     */
    function getUserFrontendLangId()
    {
        // check if session has been initialized yet
        $session = \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Session')->isInitialized();

// Mind: Changed from $_POST to $_REQUEST, so it can be changed by
// clicking a link (used in the Shop, and for MailTemplates)
        if (!empty($_REQUEST['userFrontendLangId'])) {
            if (preg_match('/[0-9]/', $_REQUEST['userFrontendLangId'])) {
                $id = intval($_REQUEST['userFrontendLangId']);
            } else {
                $id = FWLanguage::getLanguageIdByCode($_REQUEST['userFrontendLangId']);
            }
        } elseif (!empty($_COOKIE['userFrontendLangId'])) {
            $id = FWLanguage::getLanguageIdByCode($_COOKIE['userFrontendLangId']);
        } elseif ($session && !empty($_SESSION['userFrontendLangId'])) {
            $id = intval($_SESSION['userFrontendLangId']);
        } else {
            $id = $this->defaultFrontendLangId;
        }
        if (empty($this->arrLang[$id]['frontend'])) {
            $id = $this->defaultFrontendLangId;
        }
        $this->userFrontendLangId = $id;

        if ($session) {
            $_SESSION['userFrontendLangId'] = $id;
            // unset cookie as option is now stored in session
            setcookie("userFrontendLangId", "", time() - 3600);
        }

        return $this->userFrontendLangId;
    }


    function getDefaultFrontendLangId()
    {
          return $this->defaultFrontendLangId;
    }


    function getDefaultBackendLangId()
    {
          return $this->defaultFrontendLangId;
    }


    function getFrontendLangId()
    {
          return $this->frontendLangId;
    }


    function getFrontendLangName()
    {
        return $this->arrLang[$this->frontendLangId]['lang'];
    }


    function getBackendLangId()
    {
          return $this->backendLangId;
    }


    /**
     * Returns an array of all languages
     * @access  public
     * @return  array $arrLang
     */
    function getLanguageArray()
    {
        return $this->arrLang;
    }


    /**
     * Returns the default frontend language ID
     * @access  public
     */
    function getFrontendDefaultLangId()
    {
        return $this->defaultFrontendLangId;
    }


    /**
     * Returns the default backend language ID
     * @access  public
     */
    function getBackendDefaultLangId()
    {
        return $this->defaultBackendLangId;
    }


    /**
     * Returns an array of all basic templates for the active theme
     * @return  array           The array of template strings
     * @access  public
     */
    function getTemplates($page)
    {
        global $objDatabase;

        if (isset($_GET['custom_content']) && preg_match('/^[a-zA-Z0-9_]+$/', $_GET['custom_content'])) {
            $this->customContentTemplate=$_GET['custom_content'];
        }

        if (isset($_GET['preview']) && intval($_GET['preview'])){
            $id = intval($_GET['preview']);
            $theme = $this->themeRepository->findById($id);
            if ($theme){
                $this->currentThemesId = $id;
            }
        }

        // get theme object so we get the configured libraries
        $theme = $this->getFrontendTemplate();
        $this->pageThemeId = $this->currentThemesId;
        $themesPath = $theme->getFoldername();
        if ($theme && $theme->isComponent()) {
            $libraries = JS::getConfigurableLibraries();
            foreach ($theme->getDependencies() as $libraryName => $libraryVersions) {
                if (!isset($libraries[$libraryName])) continue;
                $version = $libraryVersions[0];
                $libraryData = isset($libraries[$libraryName]['versions'][$version]) ? $libraries[$libraryName]['versions'][$version] : array();
                if (isset($libraryData['jsfiles'])) {
                    foreach ($libraryData['jsfiles'] as $file) {
                        \JS::registerJS($file, true);
                    }
                }
                if (isset($libraryData['cssfiles'])) {
                    foreach ($libraryData['cssfiles'] as $file) {
                        \JS::registerCSS($file);
                    }
                }
            }
        }

        $this->themesPath = $themesPath;

        $this->templates['index']                   = $this->getThemeFileContent($themesPath, 'index.html');
        $this->templates['home']                    = $this->getThemeFileContent($themesPath, 'home.html');
        $this->templates['navbar']                  = $this->getThemeFileContent($themesPath, 'navbar.html');
        $this->templates['navbar2']                 = $this->getThemeFileContent($themesPath, 'navbar2.html');
        $this->templates['navbar3']                 = $this->getThemeFileContent($themesPath, 'navbar3.html');
        $this->templates['subnavbar']               = $this->getThemeFileContent($themesPath, 'subnavbar.html');
        $this->templates['subnavbar2']              = $this->getThemeFileContent($themesPath, 'subnavbar2.html');
        $this->templates['subnavbar3']              = $this->getThemeFileContent($themesPath, 'subnavbar3.html');
        $this->templates['sidebar']                 = $this->getThemeFileContent($themesPath, 'sidebar.html');
        $this->templates['top_news']                = $this->getThemeFileContent($themesPath, 'top_news.html');
        $this->templates['shopnavbar']              = $this->getThemeFileContent($themesPath, 'shopnavbar.html');
        $this->templates['shopnavbar2']             = $this->getThemeFileContent($themesPath, 'shopnavbar2.html');
        $this->templates['shopnavbar3']             = $this->getThemeFileContent($themesPath, 'shopnavbar3.html');
        $this->templates['headlines']               = $this->getThemeFileContent($themesPath, 'headlines.html');
        $this->templates['headlines2']              = $this->getThemeFileContent($themesPath, 'headlines2.html');
        $this->templates['headlines3']              = $this->getThemeFileContent($themesPath, 'headlines3.html');
        $this->templates['headlines4']              = $this->getThemeFileContent($themesPath, 'headlines4.html');
        $this->templates['headlines5']              = $this->getThemeFileContent($themesPath, 'headlines5.html');
        $this->templates['headlines6']              = $this->getThemeFileContent($themesPath, 'headlines6.html');
        $this->templates['headlines7']              = $this->getThemeFileContent($themesPath, 'headlines7.html');
        $this->templates['headlines8']              = $this->getThemeFileContent($themesPath, 'headlines8.html');
        $this->templates['headlines9']              = $this->getThemeFileContent($themesPath, 'headlines9.html');
        $this->templates['headlines10']             = $this->getThemeFileContent($themesPath, 'headlines10.html');
        $this->templates['headlines11']             = $this->getThemeFileContent($themesPath, 'headlines11.html');
        $this->templates['headlines12']             = $this->getThemeFileContent($themesPath, 'headlines12.html');
        $this->templates['headlines13']             = $this->getThemeFileContent($themesPath, 'headlines13.html');
        $this->templates['headlines14']             = $this->getThemeFileContent($themesPath, 'headlines14.html');
        $this->templates['headlines15']             = $this->getThemeFileContent($themesPath, 'headlines15.html');
        $this->templates['headlines16']             = $this->getThemeFileContent($themesPath, 'headlines16.html');
        $this->templates['headlines17']             = $this->getThemeFileContent($themesPath, 'headlines17.html');
        $this->templates['headlines18']             = $this->getThemeFileContent($themesPath, 'headlines18.html');
        $this->templates['headlines19']             = $this->getThemeFileContent($themesPath, 'headlines19.html');
        $this->templates['headlines20']             = $this->getThemeFileContent($themesPath, 'headlines20.html');
        $this->templates['news_recent_comments']    = $this->getThemeFileContent($themesPath, 'news_recent_comments.html');
        $this->templates['javascript']              = $this->getThemeFileContent($themesPath, 'javascript.js');
        //$this->templates['style']                 = $this->getThemeFileContent($themesPath, 'style.css');
        $this->templates['buildin_style']           = $this->getThemeFileContent($themesPath, 'buildin_style.css');
        $this->templates['calendar_headlines']      = $this->getThemeFileContent($themesPath, 'events.html');
        $this->templates['calendar_headlines2']     = $this->getThemeFileContent($themesPath, 'events2.html');
        $this->templates['calendar_headlines3']     = $this->getThemeFileContent($themesPath, 'events3.html');
        $this->templates['calendar_headlines4']     = $this->getThemeFileContent($themesPath, 'events4.html');
        $this->templates['calendar_headlines5']     = $this->getThemeFileContent($themesPath, 'events5.html');
        $this->templates['calendar_headlines6']     = $this->getThemeFileContent($themesPath, 'events6.html');
        $this->templates['calendar_headlines7']     = $this->getThemeFileContent($themesPath, 'events7.html');
        $this->templates['calendar_headlines8']     = $this->getThemeFileContent($themesPath, 'events8.html');
        $this->templates['calendar_headlines9']     = $this->getThemeFileContent($themesPath, 'events9.html');
        $this->templates['calendar_headlines10']    = $this->getThemeFileContent($themesPath, 'events10.html');
        $this->templates['directory_content']       = $this->getThemeFileContent($themesPath, 'directory.html');
        $this->templates['forum_content']           = $this->getThemeFileContent($themesPath, 'forum.html');
        $this->templates['podcast_content']         = $this->getThemeFileContent($themesPath, 'podcast.html');
        $this->templates['blog_content']            = $this->getThemeFileContent($themesPath, 'blog.html');
        $this->templates['immo']                    = $this->getThemeFileContent($themesPath, 'immo.html');

        if (!$this->hasCustomContent() || !$this->loadCustomContent($page)) {
            // load default content layout if page doesn't have a custom content
            // layout or if it failed to be loaded
            $this->templates['content']             = $this->getThemeFileContent($themesPath, 'content.html');
        }

        return $this->templates;
    }

    /**
     * Load the frontend template
     *
     * @return Cx\Core\View\Model\Entity\Theme Template instance
     * @throws \Exception Throws exception when no template was found
     */
    protected function getFrontendTemplate()
    {
        // fetch and return the configured frontend template
        $theme = $this->themeRepository->findById($this->currentThemesId);
        if ($theme) {
            return $theme;
        }

        // The configured frontend template does not exist
        \DBG::msg('Template width ID '.$this->currentThemesId.' does not exist!');

        // We will try to load the frontend template of a fallback-language therefore
        $langId = $this->frontendLangId;
        while ($langId = \FWLanguage::getFallbackLanguageIdById($langId)) {
            // fetch and return default template of fallback language
            $theme = $this->themeRepository->getDefaultTheme($this->currentChannel, $langId);
            if ($theme) {
                // reset local variables based on the loaded fallback frontend template
                $this->channelThemeId = $this->currentThemesId = $theme->getId();
                return $theme;
            }

            // template of fallback language does not exist
            \DBG::msg('Default template of language '.$langId.' does not exist!');
        }

        // None of the fallback-languages did have an existing frontend template.
        // Therefore, we will abort the system execution now
        throw new \Exception('Unable to load a webdesign template!');
    }

    /**
     * Fetches the content of a themes file.
     *
     * The content is first fetched from the website's data directory:
     *      Cx\Core\Core\Controller\Cx::getWebsiteThemesPath()
     * If the file is not present in the website's data directory,
     * then the content is fetch from the file in the code base directory:
     *      Cx\Core\Core\Controller\Cx::getCodeBaseThemesPath()
     * @param   string  $themesPath Path to the themes folder
     * @param   string  $file   Name of the file to fetch the content from
     * @return  string  The content of the file specified by $themesPath and $file
     */
    private function getThemeFileContent($themesPath, $file)
    {
        $filePath = '/' . $themesPath . '/' . $file;
        $content = '';

        $theme       = new \Cx\Core\View\Model\Entity\Theme();
        $contentPath = $theme->getFilePath($filePath);
        if (file_exists($contentPath)) {
            $content = file_get_contents($contentPath);
        }

        return $content;
    }

    private function loadCustomContent($page)
    {
        global $objDatabase;

        // OPTION USE FOR OUTPUT CHANNEL
        $themeFolder = '';
        $themeRepository   = new \Cx\Core\View\Model\Repository\ThemeRepository();
        if ($page->getUseCustomContentForAllChannels()) {
            $theme = $themeRepository->findById($page->getSkin());
            if (!$theme) {
                $theme = $themeRepository->getDefaultTheme($page->getLang());
            }
            $themeFolder = $theme->getFoldername();
        } elseif (!empty($this->customContentTemplate)) {
            $themeFolder = $themeRepository->findById($this->channelThemeId)->getFoldername();
        }
        if ($themeFolder) {
            $content = $this->getThemeFileContent($themeFolder, $page->getCustomContent());
            if ($content) {
                $this->templates['content'] = $content;
                return true;
            }
        }

        //only include the custom template if it really exists.
        //if the user selected custom_x.html as a page's custom template, a print-view request will
        //try to get the file "themes/<printtheme>/custom_x.html" - we do not know if this file
        //exists. trying to read a non-existant file would lead to an empty content-template.
        //to omit this, we read the standard print content template instead.
        //another possible behaviour would be to read the standard theme's custom content template instead.
        //this is not done, because customcontent files are mostly used for sidebars etc. -
        //stuff that should not change the print representation of the content.
        $content = $this->getThemeFileContent($this->themesPath, $this->customContentTemplate);
        if ($content) {
            $this->templates['content'] = $content;
            return true;
        }

        return false;
    }

    /**
     * Collects all custom content templates available for the theme specified
     *
     * Used by @link ContentManager::ajaxGetCustomContentTemplate().
     * On failure, returns the empty array.
     * The array returned looks like
     *  array(
     *    'content_xy.html',
     *    'home_xy.html' ,
     *    [... more ...]
     *  )
     * @param   integer   $themeId    The theme ID
     * @return  array                 The custom content template filename array
     */
    public function getCustomContentTemplatesForTheme($themeId)
    {
        global $objDatabase;

        if ($themeId == 0)
            $themeId = $this->currentThemesId;

        $customTemplateForTheme = $this->themeRepository->findOneBy(array('id' => $themeId));
        if (!$customTemplateForTheme)
            return array();

        $result = array();
        $templateFiles = array();
        $folder = $customTemplateForTheme->getFoldername();
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $templateFiles = $cx->getMediaSourceManager()->getMediaType('themes')->getFileSystem()->getFileList($folder, false);

        foreach ($templateFiles as $fileName => $fileInfo){
            $match = null;
            // skip subdirectories
            if ($fileInfo['datainfo']['type'] != 'file') {
                continue;
            }

            if (preg_match('/^(content|home)_(.+).html$/', $fileName, $match)) {
                array_push($result, $fileName);
            }
        }

        return $result;
    }

    /**
     * Return the current themes path
     *
     * @access public
     * @author Stefan Heinemann
     * @return string
     */
    public function getCurrentThemesPath()
    {
        return $this->themesPath;
    }


    function loadModulePaths()
    {
        global $objDatabase;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();

        // generate "module paths" array
        $query = "SELECT name, is_core FROM ".DBPREFIX."modules";
        $objResult = $objDatabase->Execute($query);
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                if (strlen($objResult->fields['name'])>0){
                    switch ($objResult->fields['name']){
                        case 'core':
                            $this->arrModulePath[$objResult->fields['name']] = $cx->getCodeBaseDocumentRootPath() . '/lang/';
                            $this->arrModulePath['Core'] = $cx->getCodeBaseDocumentRootPath() . '/lang/';
                            break;
                        case 'SystemInfo':
                        case 'ComponentManager':
                        case 'ViewManager':
                        case 'LanguageManager':
                        case 'ContentWorkflow':
                        case 'Config':
                        case 'SystemLog':
                        case 'NetManager':
                        case 'Wysiwyg':
                        case 'Routing':
                        case 'Html':
                        case 'Locale':
                        case 'Country':
                        case 'View':
                            $this->arrModulePath[$objResult->fields['name']] = $cx->getCodeBaseCorePath() . '/'. $objResult->fields['name'] . '/lang/';
                            break;
                        default:
                        $this->arrModulePath[$objResult->fields['name']] = ($objResult->fields['is_core'] == 1 ? $cx->getCodeBaseCoreModulePath() : $cx->getCodeBaseModulePath()).'/'.$objResult->fields['name'].'/lang/';
                    }
                }
                $objResult->MoveNext();
            }
            // add special modules
            $this->arrModulePath['Media'] = $cx->getCodeBaseCoreModulePath() . '/Media/lang/';
        }
    }


    /**
     * Initializes the language array
     *
     * @param string $module The component to load the language data from
     * @param boolean $loadFromYaml Wether to load customized placeholders from yaml or not
     * @return    array         The language array, either local $_ARRAYLANG or
     *                          the global $_CORELANG
     */
    function loadLanguageData($module='', $loadFromYaml=true, $mode = '')
    {
// NOTE: This method is called on the (global) Init object, so
// there's no need to "global" that!
//        global $objInit;
        global $_CORELANG, $_CONFIG, $objDatabase, $_ARRAYLANG;

        if (empty($mode)) {
            $mode = $this->mode;
        }

        if(!isset($_ARRAYLANG))
            $_ARRAYLANG = array();

        if(!isset($_CORELANG))
            $_CORELANG = array();
        if ($mode == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            if (isset($this->arrBackendLang[$this->backendLangId])) {
                $langCode = $this->arrBackendLang[$this->backendLangId]['lang'];
            } else {
                $langCode = $this->arrBackendLang[\FWLanguage::getDefaultBackendLangId()]['lang'];
            }
        } else {
            if (isset($this->arrLang[$this->frontendLangId])) {
                $langCode = $this->arrLang[$this->frontendLangId]['source_lang'];
            } else {
                $langCode = $this->arrLang[\FWLanguage::getDefaultLangId()]['source_lang'];
            }
        }

        // check which module will be loaded
        if (empty($module)) {
            if ($mode == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
                $module = isset($_REQUEST['cmd']) ? addslashes(strip_tags($_REQUEST['cmd'])) : 'core';
            } else {
                $module = isset($_REQUEST['section']) ? addslashes(strip_tags($_REQUEST['section'])) : 'core';
            }
        }
        if (preg_match('/^Media\d+$/', $module)) {
            $module = 'Media';
        }
        // change module for core components
        if (!array_key_exists($module, $this->arrModulePath) && $module != 'Media') {
            $module = '';
        } else {
            //load english language file first...
            $path = $this->getLangFilePathByCode($module, 'en', $mode);
            if (!empty($path)) {
                $this->loadLangFile($path, $loadFromYaml, $module, $mode);
            }
            //...and overwrite with actual language where translated.
            //...but only if $langCode is set (otherwise it will overwrite English by the default language
            if($langCode && $langCode != 'en') { //don't do it for english, already loaded.
                $path = $this->getLangFilePathByCode($module, $langCode, $mode);
                if (!empty($path)) {
                    $this->loadLangFile($path, $loadFromYaml, $module, $mode);
                }
            }
            return $_ARRAYLANG;
        }

        // load variables
        if (empty($module)) {
            return $_CORELANG;
        }
        return $_CORELANG;
    }

    /**
     * Get component specific language data
     * State of the init will be backedup and restored while loading the language
     * data
     *
     * @param string $componentName Name of the desired component
     * @param bool|true $frontend true if desired mode is frontend false otherwise
     * @param integer $languageId Id of the desired language i.e. 1 for german
     * @param boolean $loadFromYaml Wether to load customized placeholders from yaml or not
     * @return array The language data which has been loaded
     */
    public function getComponentSpecificLanguageData($componentName, $frontend = true, $languageId = 0, $loadFromYaml=true) {
        global $_ARRAYLANG;

        if ($frontend) {
            $mode = \Cx\Core\Core\Controller\Cx::MODE_FRONTEND;
        } else {
            $mode = \Cx\Core\Core\Controller\Cx::MODE_BACKEND;
        }

        if (!$languageId) {
            if ($frontend) {
                $languageId = $this->frontendLangId;
            } else {
                $languageId = $this->backendLangId;
            }
        }

        if ($componentName == 'Core') {
            $componentName = lcfirst($componentName);
        }

        if (!isset($this->moduleSpecificLanguageData[$languageId])) {
            $this->moduleSpecificLanguageData[$languageId] = array();
        }
        if (!isset($this->moduleSpecificLanguageData[$languageId][$frontend])) {
            $this->moduleSpecificLanguageData[$languageId][$frontend] = array();
        }

        if (isset($this->moduleSpecificLanguageData[$languageId][$frontend][$componentName])) {
            return $this->moduleSpecificLanguageData[$languageId][$frontend][$componentName];
        }

        // save init state
        $langBackup = $_ARRAYLANG;
        $frontentLangIdBackup = $this->frontendLangId;
        $backendLangIdBackup = $this->backendLangId;

        // set custom init state
        $_ARRAYLANG = array();
        $this->frontendLangId = $languageId;
        $this->backendLangId = $languageId;

        // load language data
        $this->moduleSpecificLanguageData[$languageId][$frontend][$componentName] = $this->loadLanguageData($componentName, $loadFromYaml, $mode);

        // restore init state
        $_ARRAYLANG = $langBackup;
        $this->frontendLangId = $frontentLangIdBackup;
        $this->backendLangId = $backendLangIdBackup;

        return $this->moduleSpecificLanguageData[$languageId][$frontend][$componentName];
    }

    /**
     * Get component specific language data directly by language code
     * State of arraylang will be backedup and restored while loading the language
     * data
     *
     * @param string $componentName Name of the desired component
     * @param bool|true $frontend true if desired mode is frontend false otherwise
     * @param string $languageCode iso1 code  of the desired language i.e. 'de' for german
     * @param boolean $loadFromYaml Wether to load customized placeholders from yaml or not
     * @return array The language data which has been loaded
     */
    public function getComponentSpecificLanguageDataByCode($componentName, $frontend = true, $langCode, $loadFromYaml = true) {
        global $_ARRAYLANG;

        $arrayLangBackup = $_ARRAYLANG;
        $_ARRAYLANG = array();

        if ($frontend) {
            $mode = \Cx\Core\Core\Controller\Cx::MODE_FRONTEND;
        } else {
            $mode = \Cx\Core\Core\Controller\Cx::MODE_BACKEND;
        }

        if ($componentName == 'Core') {
            $componentName = lcfirst($componentName);
        }

        // build path from component name, mode and code directly
        $path = \Env::get('ClassLoader')->getFilePath($this->arrModulePath[$componentName].$langCode.'/'.$mode.'.php');

        if (!$path) {
            // restore $_ARRAYLANG
            $_ARRAYLANG = $arrayLangBackup;
            throw new \InitCMSException($arrayLangBackup['TXT_CORE_LOCALE_LANGUAGEFILE_NOT_FOUND']);
        }

        $componentSpecificLanguageData = $this->loadLangFile($path, $loadFromYaml, $componentName, $mode);

        // restore $_ARRAYLANG
        $_ARRAYLANG = $arrayLangBackup;

        return $componentSpecificLanguageData;
    }

    protected function getLangFilePathByCode($module, $langCode, $mode) {
        if (
            in_array(
                $mode,
                array(\Cx\Core\Core\Controller\Cx::MODE_BACKEND, 'update')
            )
        ) {
            $mode = \Cx\Core\Core\Controller\Cx::MODE_BACKEND;
        } else {
            $mode = \Cx\Core\Core\Controller\Cx::MODE_FRONTEND;
        }

        // check whether the language file exists
        if ($mode == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            $path = \Env::get('ClassLoader')->getFilePath($this->arrModulePath[$module].$langCode.'/'.$mode.'.php');
        } else {
            $path = \Env::get('ClassLoader')->getFilePath($this->arrModulePath[$module].$langCode.'/'.$mode.'.php');
        }

        if ($path) {
            return $path;
        }
        
        // file path of default language (if not yet requested)
        if ($mode == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
            $defaultLangCode = $this->arrBackendLang[\FWLanguage::getDefaultBackendLangId()]['lang'];
        } else {
            $defaultLangCode = $this->arrLang[\FWLanguage::getDefaultLangId()]['iso1'];
        }
        if ($langCode == $defaultLangCode) {
            return '';
        }
        return $this->getLangFilePathByCode($module, $defaultLangCode, $mode);
    }

    /**
     * Loads the language file for the given file path
     *
     * Note that no replacements are made to the entries' contents.
     * If your strings don't work as expected, fix *them*.
     *
     * @param string $path The path of the language file
     * @param boolean $loadFromYaml Wether to load customized placeholders from yaml or not
     * @param string $componentName The name of the language file's component
     */
    protected function loadLangFile($path, $loadFromYaml=true, $componentName='Core', $mode = '')
    {
        global $_ARRAYLANG;

        $isCustomized = false;
        $customizedPath = \Env::get('ClassLoader')->getFilePath($path, $isCustomized);
        if (file_exists($path) || !file_exists($customizedPath)) {
            require $path;
        }
        if ($isCustomized) {
            require $customizedPath;
        }

        // if we don't want to load from yaml return language data already
        if (!$loadFromYaml) {
            return $_ARRAYLANG;
        }

        // load customized language placeholders from yaml
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        if (empty($mode)) {
            $mode = $cx->getMode();
        }
        $frontend = $mode == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND;
        try {
            if ($frontend) {
                // get language by frontend locale
                // TODO: we must load the language specified by $path
                $locale = $cx->getDb()->getEntityManager()->find(
                    'Cx\Core\Locale\Model\Entity\Locale',
                    $this->frontendLangId
                );
                $language = $locale->getSourceLanguage();
            } elseif ($mode == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
                // TODO: we must load the language specified by $path
                $backendLangId = !empty($this->backendLangId) ? $this->backendLangId : $this->defaultBackendLangId;
                $backend = $em->find(
                    'Cx\Core\Locale\Model\Entity\Backend',
                    $backendLangId
                );
                $language = $backend->getIso1();
            } else {
                return $_ARRAYLANG;
            }
        } catch (\Throwable $e) {
            \DBg::msg($e->getMessage());
            return $_ARRAYLANG;
        }

        try {
            // get the language file of the locale
            $languageFile = new \Cx\Core\Locale\Model\Entity\LanguageFile(
                $language, $componentName, $frontend
            );

        } catch (\Cx\Core\Locale\Model\Entity\LanguageFileException $e) {
            \Message::add($e->getMessage(), \Message::CLASS_ERROR);
        }

        // merge customized placeholders into $_ARRAYLANG
        $_ARRAYLANG = array_merge($_ARRAYLANG, $languageFile->getData());

        return $_ARRAYLANG;
    }


    /**
     * Sets the customized ThemesId and customContent template
     *
     * This method sets the currentThemesId if a customized themesId is set
     * in the navigation table.
     * @param   int $themesId     The optional theme ID
     * @param   string $customContent   The optional custom content template (like 'content_without_h1.html')
     */
    public function setCustomizedTheme($themesId=0, $customContent='', $useThemeForAllChannels = false)
    {
        global $objDatabase;

        // set custom content template
        $this->customContentTemplate = $customContent;

        //only set customized theme if not in printview AND no mobile devic
        if ($useThemeForAllChannels || (!isset($_GET['printview']) && !$this->isInMobileView())) {
            $themesId=intval($themesId);
            if ($themesId>0){
                $customizedTheme = $this->themeRepository->findById($themesId);
                if ($customizedTheme) {
                    $this->currentThemesId=intval($customizedTheme->getId());
                }
            }
        }
    }


    /**
     * @access private
     * @return boolean Return TRUE if the user is in "Mobile View"-mode, otherwise FALSE
     */
    private function isInMobileView()
    {
        return $this->arrLang[$this->frontendLangId]['mobile_themes_id'] && $this->isMobileDevice;
    }


    /**
     * Returns the HTML for the frontend language selection dropdown menu
     *
     * Backend use only.
     * @internal    Note to Shop (and other newish module) programmers:
     *  Registers javascript for handling the currently active tab.
     *  Set the _active_tab global index variable in your onchange handler
     *  whenever the user switches the tab.  This value is posted in the
     *  active_tab parameter when the language is changed.
     *  See {@see getJavascript_activetab()} for details, and
     *  {@see \Cx\Core\Setting\Controller\Setting::show()} and {@see \Cx\Core\Setting\Controller\Setting::show_external()}
     *  for implementations.
     * @param boolean $force (optional) Wheter to force a non-empty return value, default false
     * @return  string            The HTML language dropdown menu code
     */
    function getUserFrontendLangMenu($force = false)
    {
        global $_ARRAYLANG;

        $arrLanguageName = FWLanguage::getNameArray();
        // No dropdown at all if there is a single active frontend language
        if (count($arrLanguageName) == 1) {
            return '';
        }

        $action = CONTREXX_DIRECTORY_INDEX;
        if ($force) {
            $command = 'force';
        } else {
            $command = isset($_REQUEST['cmd']) ? contrexx_input2raw($_REQUEST['cmd']) : '';
        }
        switch ($command) {
            case 'Shop':
            case 'country':
            case 'force':
                // Variant 2:  Use any (GET) request parameters
                // Note that this is generally unsafe, as most modules/methods do
                // not rely on posted data only!
                $action = '';
                // The dropdown is built below
            break;
            // TODO: Add your case here if variant 1 is enabled, too
            //case 'foobar':
            case 'DocSys':
            case 'Recommend':
            case 'Jobs':
            case 'alias':
                // The old way
                $i = 0;
                $return = "\n<form action='' method='post' name='userFrontendLangIdForm'>\n";
                $return .= "<select name='userFrontendLangId' size='1' class='chzn-select' data-disable_search='true' onchange=\"document.forms['userFrontendLangIdForm'].submit()\">\n";
                foreach ($this->arrLang as $id=>$value){
                    if ($this->arrLang[$id]['frontend']==1) {
                        $i++;
                        if ($id==$this->userFrontendLangId) {
                            $return .= "<option value='".$id."' selected='selected'>Frontend [".htmlentities($value['name'], ENT_QUOTES, CONTREXX_CHARSET)."]</option>\n";
                        } else {
                            $return .= "<option value='".$id."'>Frontend [".htmlentities($value['name'], ENT_QUOTES, CONTREXX_CHARSET)."]</option>\n";
                        }
                    }
                }
                $return .= "</select>\n</form>\n";
                return ($i>1) ? $return : "";
            default:
                return '';
                break;
        }

        // For those views that support it, update the selected tab index
        JS::registerCode(
            'function submitUserFrontendLanguage() {'.
            ' $J("[name=active_tab]").val(_active_tab);'.
            ' document.forms.userFrontendLangIdForm.submit(); '.
            '}');
        // For variants 1 and 2:  Build the dropdown
        return
            "\n".
            '<form id="userFrontendLangIdForm" name="userFrontendLangIdForm"'.
            ' action="'.$action.'"'.
            ' method="post">'."\n".
            Html::getHidden_activetab()."\n".
            Html::getSelectCustom('userFrontendLangId',
                FWLanguage::getMenuoptions($this->userFrontendLangId),
                false,
                'submitUserFrontendLanguage();',
                'size="1" class="chzn-select" data-disable_search="true"'
            ) . "\n</form>\n";
    }


    public function getUriBy($key = '', $value = '')
    {
        $url = \Env::get('cx')->getRequest()->getUrl();
        $myUrl = clone $url;
        $myUrl->setParam($key, $value);

        return $myUrl;
    }


    public function getPageUri()
    {
        return \Env::get('cx')->getRequest()->getUrl();
    }


    public function getCurrentPageUri()
    {
        return htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, CONTREXX_CHARSET);
    }


    /**
     * Returns true if the user agent is a mobile device (smart phone, PDA etc.)
     * @deprecated Use \Cx\Core\Routing\Model\Entity\Request::isMobilePhone() instead
     */
    public static function _is_mobile_phone()
    {
        $isMobile = false;
        $op = isset($_SERVER['HTTP_X_OPERAMINI_PHONE']) ? strtolower($_SERVER['HTTP_X_OPERAMINI_PHONE']) : '';
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        $ac = isset($_SERVER['HTTP_ACCEPT']) ? strtolower($_SERVER['HTTP_ACCEPT']) : '';

        $isMobile = strpos($ac, 'application/vnd.wap.xhtml+xml') !== false
            || $op != ''
            || strpos($ua, 'htc') !== false
            || strpos($ua, 'iphone') !== false
            || strpos($ua, 'sony') !== false
            || strpos($ua, 'symbian') !== false
            || strpos($ua, 'nokia') !== false
            || strpos($ua, 'samsung') !== false
            || strpos($ua, 'mobile') !== false
            || strpos($ua, 'windows ce') !== false
            || strpos($ua, 'epoc') !== false
            || strpos($ua, 'opera mini') !== false
            || strpos($ua, 'nitro') !== false
            || strpos($ua, 'j2me') !== false
            || strpos($ua, 'midp-') !== false
            || strpos($ua, 'cldc-') !== false
            || strpos($ua, 'netfront') !== false
            || strpos($ua, 'mot') !== false
            || strpos($ua, 'up.browser') !== false
            || strpos($ua, 'up.link') !== false
            || strpos($ua, 'audiovox') !== false
            || strpos($ua, 'blackberry') !== false
            || strpos($ua, 'ericsson,') !== false
            || strpos($ua, 'panasonic') !== false
            || strpos($ua, 'philips') !== false
            || strpos($ua, 'sanyo') !== false
            || strpos($ua, 'sharp') !== false
            || strpos($ua, 'sie-') !== false
            || strpos($ua, 'portalmmm') !== false
            || strpos($ua, 'blazer') !== false
            || strpos($ua, 'avantgo') !== false
            || strpos($ua, 'danger') !== false
            || strpos($ua, 'palm') !== false
            || strpos($ua, 'series60') !== false
            || strpos($ua, 'palmsource') !== false
            || strpos($ua, 'pocketpc') !== false
            || strpos($ua, 'smartphone') !== false
            || strpos($ua, 'rover') !== false
            || strpos($ua, 'ipaq') !== false
            || strpos($ua, 'au-mic,') !== false
            || strpos($ua, 'alcatel') !== false
            || strpos($ua, 'ericy') !== false
            || strpos($ua, 'up.link') !== false
            || strpos($ua, 'vodafone/') !== false
            || strpos($ua, 'wap1.') !== false
            || strpos($ua, 'wap2.') !== false;
        return $isMobile;
    }


    /**
     * Returns true if the user agent is a tablet
     * @deprecated Use \Cx\Core\Routing\Model\Entity\Request::isTablet() instead
     */
    public static function _is_tablet()
    {
        $isTablet = false;
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);

        $isTablet = strpos($ua, 'tablet') !== false
            || strpos($ua, 'ipad') !== false
            || strpos($ua, 'sch-i800') !== false
            || strpos($ua, 'gt-p1000') !== false
            || strpos($ua, 'a500') !== false
            || strpos($ua, 'gt-p7100') !== false
            || strpos($ua, 'gt-p1000') !== false
            || strpos($ua, 'at100') !== false
            || (strpos($ua, 'a43') !== false && strpos($ua, 'iphone') === false);
        return $isTablet;
    }


    /**
     * Returns true if there is custom content for this page
     * @return  boolean       True if there is custom content,
     *                        false otherwise
     */
    public function hasCustomContent()
    {
        return !empty($this->customContentTemplate) && strlen($this->customContentTemplate) > 0 ? true : false;
    }

    /**
     * Return the current theme id
     * Note: This vaule is available only in frontend mode
     *
     * @return integer
     */
    public function getCurrentThemeId()
    {
        return $this->pageThemeId;
    }

    /**
     * Returns the current channel
     * @throws \Exception If channel is not yet set, call setFrontendLangId() to set it
     * @return string Channel
     */
    public function getCurrentChannel() {
        if (!$this->currentChannel) {
            throw new \Exception('Channel not yet set');
        }
        return $this->currentChannel;
    }
}
