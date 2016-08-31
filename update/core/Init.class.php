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
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Initialize the CMS
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
    public $backendLangCharset;
    public $backendLangId;

    public $defaultFrontendLangId;
    public $frontendLangCharset;
    public $frontendLangId;
    public $frontendLangName;
    public $userFrontendLangId;

    public $currentThemesId;
    public $customContentTemplate = null;
    public $arrLang = array();
    public $arrLangNames = array();
    public $templates = array();
    public $arrModulePath = array();

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


    /**
     * Constructor
     */
    function __construct($mode='frontend', $entityManager = null)
    {
        global $objDatabase;

// TODO: what is this used for?
        $this->em = $entityManager;
        $this->mode=$mode;

        $objResult = $objDatabase->Execute("
            SELECT id, themesid, print_themes_id, pdf_themes_id,
                   lang, name, charset, backend, frontend, is_default
              FROM ".DBPREFIX."languages
        ");
        if ($objResult) {
            while (!$objResult->EOF) {
                $this->arrLang[$objResult->fields['id']]= array(
                    'id'               => $objResult->fields['id'],
                    'themesid'         => $objResult->fields['themesid'],
                    'print_themes_id'  => $objResult->fields['print_themes_id'],
                    'pdf_themes_id'    => $objResult->fields['pdf_themes_id'],
                    'mobile_themes_id' => 0,//$objResult->fields['mobile_themes_id'],
                    'app_themes_id'    => 0,//$objResult->fields['app_themes_id'],
                    'lang'             => $objResult->fields['lang'],
                    'name'             => $objResult->fields['name'],
                    'charset'          => $objResult->fields['charset'],
                    'backend'          => $objResult->fields['backend'],
                    'frontend'         => $objResult->fields['frontend'],
                    'is_default'       => $objResult->fields['is_default']);
                $this->arrLangNames[$objResult->fields['lang']] = $objResult->fields['id'];
                if ($objResult->fields['is_default']=="true") {
                    $this->defaultBackendLangId = $objResult->fields['id'];
                    $this->defaultFrontendLangId = $objResult->fields['id'];
                }
                $objResult->MoveNext();
            }
        }
        if ($mode == 'frontend') {
            //$this->_initBackendLanguage();
            $this->getUserFrontendLangId();
        }

        $this->loadModulePaths();
    }


    /**
     * Backend language initialization
     */
    function _initBackendLanguage()
    {
        $objFWUser = FWUser::getFWUserObject();
        if ($objFWUser->objUser->login(true)) {
            $backendLangId = $objFWUser->objUser->getBackendLanguage();
        } elseif (!empty($_COOKIE['backendLangId'])) {
            $backendLangId = intval($_COOKIE['backendLangId']);
        } else {
            $backendLangId = $this->defaultBackendLangId;
        }
        if (empty($this->arrLang[$backendLangId]['backend'])) {
            $backendLangId = $this->defaultBackendLangId;
        }
        $this->backendLangId = $this->arrLang[$backendLangId]['id'];
        $this->currentThemesId = $this->arrLang[$backendLangId]['themesid'];
        $this->backendLangCharset = $this->arrLang[$backendLangId]['charset'];
        setcookie('backendLangId', $backendLangId, time()+3600*24*30, ASCMS_PATH_OFFSET.'/');
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
        }
        // Load PDF template
        elseif (isset($_GET['pdfview']) && $_GET['pdfview'] == 1){
            $this->currentThemesId = $this->arrLang[$this->frontendLangId]['pdf_themes_id'];
        }
        // Load mobile template
        elseif ($this->isMobileDevice and $this->arrLang[$this->frontendLangId]['mobile_themes_id']) {
            $this->currentThemesId = $this->arrLang[$this->frontendLangId]['mobile_themes_id'];
        }
        // Load app template
        elseif (isset($_GET['appview']) && $_GET['appview'] == 1) {
            $this->currentThemesId = $this->arrLang[$this->frontendLangId]['app_themes_id'];
        }
        // Load regular content template
        else {
            $this->currentThemesId = $this->arrLang[$this->frontendLangId]['themesid'];
        }

        // Set charset of frontend language
        $this->frontendLangCharset = $this->arrLang[$this->frontendLangId]['charset'];
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
     * Returns the language ID best matching the client's request
     *
     * If no match can be found, returns the default frontend language.
     */
    function _selectBestLanguage()
    {
        global $_CONFIG;

        if (   isset($_CONFIG['languageDetection'])
            && $_CONFIG['languageDetection'] == 'on') {
            $arrAcceptedLanguages = $this->_getClientAcceptedLanguages();
        foreach (array_keys($arrAcceptedLanguages) as $language) {
            if (in_array($language, array_keys($this->arrLangNames))) {
                return $this->arrLangNames[$language];
            } elseif (in_array($strippedLanguage = substr($language, 0, strpos($language, '-')), array_keys($this->arrLangNames))) {
                return $this->arrLangNames[$strippedLanguage];
            }
        }
        }
        return $this->defaultFrontendLangId;
    }


    /**
     * Returns an array with the accepted languages as keys and their
     * quality as values
     * @access  private
     * @return  array
     */
    function _getClientAcceptedLanguages()
    {
        $arrLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
        $arrAcceptedLanguages = array();
        foreach ($arrLanguages as $languageString) {
            $arrLanguage = explode(';q=', trim($languageString));
            $language = trim($arrLanguage[0]);
            $quality = isset($arrLanguage[1]) ? trim($arrLanguage[1]) : 1;
            $arrAcceptedLanguages[$language] = (float) $quality;
        }
        arsort($arrAcceptedLanguages, SORT_NUMERIC);
        return $arrAcceptedLanguages;
    }


    /**
     * Returns the selected User Frontend Language id
     *
     * Backend use only!
     * @return   string $this->userFrontendLangId
     */
    function getUserFrontendLangId()
    {
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
        } elseif (!empty($_SESSION['userFrontendLangId'])) {
            $id = intval($_SESSION['userFrontendLangId']);
        } else {
            $id = $this->defaultFrontendLangId;
        }
        if (empty($this->arrLang[$id]['frontend'])) {
            $id = $this->defaultFrontendLangId;
        }
        $this->userFrontendLangId = $id;
        $_SESSION['userFrontendLangId'] = $id;
        setcookie("userFrontendLangId", "", time() - 3600);
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
     * Returns the current frontend language charset string
     * for the HTML header
     * @return  string               The charset string
     * @access  public
     */
    function getFrontendLangCharset()
    {
        if (empty($this->frontendLangCharset)){
            return CONTREXX_CHARSET;
        } else {
            return $this->frontendLangCharset;
        }
    }


    /**
     * Returns the current backend language charset string
     * for the html header
     * @return  string               The charset string
     * @access  public
     */
    function getBackendLangCharset()
    {
        if (empty($this->backendLangCharset)){
            return CONTREXX_CHARSET;
        } else {
            return $this->backendLangCharset;
        }
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
    function getTemplates()
    {
        global $objDatabase;

        if (isset($_GET['custom_content']) && preg_match('/^[a-zA-Z0-9_]+$/', $_GET['custom_content'])) {
            $this->customContentTemplate=$_GET['custom_content'];
        }

        if (isset($_GET['preview']) && intval($_GET['preview'])){
            $objRS = $objDatabase->SelectLimit("
                SELECT id
                                        FROM ".DBPREFIX."skins
                 WHERE id=".intval($_GET['preview']), 1
            );
            if ($objRS->RecordCount()==1){
                $this->currentThemesId=intval($_GET['preview']);
            }
        }

        $objResult = $objDatabase->SelectLimit("
            SELECT  id,
                                    themesname,
                                    foldername
                               FROM ".DBPREFIX."skins
                              WHERE id = '$this->currentThemesId'",1);
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $themesPath = $objResult->fields['foldername'];
                $objResult->MoveNext();
            }
        }

        $this->themesPath = $themesPath;

        $this->templates['index'] = file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/index.html');
        $this->templates['home'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/home.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/home.html') : '';
        $this->templates['navbar'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/navbar.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/navbar.html') : '';
        $this->templates['navbar2'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/navbar2.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/navbar2.html') : '';
        $this->templates['navbar3'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/navbar3.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/navbar3.html') : '';
        $this->templates['subnavbar'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/subnavbar.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/subnavbar.html') : '';
        $this->templates['subnavbar2'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/subnavbar2.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/subnavbar2.html') : '';
        $this->templates['subnavbar3'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/subnavbar3.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/subnavbar3.html') : '';
        $this->templates['sidebar'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/sidebar.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/sidebar.html') : '';
        $this->templates['shopnavbar'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/shopnavbar.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/shopnavbar.html') : '';
        $this->templates['headlines'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/headlines.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/headlines.html') : '';
        $this->templates['javascript'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/javascript.js') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/javascript.js') : '';
        //$this->templates['style'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/style.css') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/style.css') : '';
        $this->templates['buildin_style'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/buildin_style.css') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/buildin_style.css') : '';
        $this->templates['calendar_headlines'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/events.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/events.html') : '';
        $this->templates['directory_content'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/directory.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/directory.html') : '';
        $this->templates['forum_content'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/forum.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/forum.html') : '';
        $this->templates['podcast_content'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/podcast.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/podcast.html') : '';
        $this->templates['blog_content'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/blog.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/blog.html') : '';
        $this->templates['immo'] = file_exists(ASCMS_THEMES_PATH.'/'.$themesPath.'/immo.html') ? file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/immo.html') : '';

        if (!$this->hasCustomContent() || !$this->loadCustomContent()) {
            // load default content layout if page doesn't have a custom content
            // layout or if it failed to be loaded
            $this->templates['content'] = file_get_contents(ASCMS_THEMES_PATH.'/'.$themesPath.'/content.html');
        }

        return $this->templates;
    }

    private function loadCustomContent()
    {
        global $objDatabase;

        //only include the custom template if it really exists.
        //if the user selected custom_x.html as a page's custom template, a print-view request will
        //try to get the file "themes/<printtheme>/custom_x.html" - we do not know if this file
        //exists. trying to read a non-existant file would lead to an empty content-template.
        //to omit this, we read the standard print content template instead.
        //another possible behaviour would be to read the standard theme's custom content template instead.
        //this is not done, because customcontent files are mostly used for sidebars etc. -
        //stuff that should not change the print representation of the content.
        if (!file_exists(ASCMS_THEMES_PATH.'/'.$this->themesPath.'/'.$this->customContentTemplate)) {
            return false;
        }

        $this->templates['content'] = file_get_contents(ASCMS_THEMES_PATH.'/'.$this->themesPath.'/'.$this->customContentTemplate);

        return true;
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

        $objResult = $objDatabase->Execute("
            SELECT foldername
            FROM ".DBPREFIX."skins
            WHERE id=$themeId
            LIMIT 1"
        );
        if (!$objResult)
            return array();

        $result = array();
        $folder = $objResult->fields['foldername'];
        if (file_exists(ASCMS_THEMES_PATH.'/'.$folder)) {
            $templateFiles = scandir(ASCMS_THEMES_PATH.'/'.$folder);

            foreach ($templateFiles as $f){
                $match = null;
                if (preg_match('/^(content|home)_(.+).html$/', $f, $match)) {
                    array_push($result, $f);
                }
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

        // generate "module paths" array
        $query = "SELECT name, is_core FROM ".DBPREFIX."modules";
        $objResult = $objDatabase->Execute($query);
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                if (strlen($objResult->fields['name'])>0){
                    switch ($objResult->fields['name']){
                        case 'core':
                            $this->arrModulePath[$objResult->fields['name']] = ASCMS_DOCUMENT_ROOT.'/lang/';
                            break;
                        case 'home':
                            // home is not a real module
                            break;
                        default:
                        $this->arrModulePath[$objResult->fields['name']] = ($objResult->fields['is_core'] == 1 ? ASCMS_CORE_MODULE_PATH : ASCMS_MODULE_PATH).'/'.$objResult->fields['name'].'/lang/';
                    }
                }
                $objResult->MoveNext();
            }
            // add special modules
            $this->arrModulePath['media'] = ASCMS_CORE_MODULE_PATH.'/media/lang/';
        }
    }


    /**
     * Initializes the language array
     * @return    array         The language array, either local $_ARRAYLANG or
     *                          the global $_CORELANG
     */
    function loadLanguageData($module='')
    {
// NOTE: This method is called on the (global) Init object, so
// there's no need to "global" that!
//        global $objInit;
        global $_CORELANG, $_CONFIG, $objDatabase, $_ARRAYLANG;

        if(!isset($_ARRAYLANG))
            $_ARRAYLANG = array();

        if ($this->mode == 'backend') {
            $langId = $this->backendLangId;
        } else {
            $langId = $this->frontendLangId;
        }

        // check which module will be loaded
        if (empty($module)) {
            if ($this->mode == 'backend') {
                $module = isset($_REQUEST['cmd']) ? addslashes(strip_tags($_REQUEST['cmd'])) : 'core';
            } else {
                $module = isset($_REQUEST['section']) ? addslashes(strip_tags($_REQUEST['section'])) : 'core';
            }
        }
        if (preg_match('/^media\d+$/', $module)) {
            $module = 'media';
        }
        // change module for core components
        if (!array_key_exists($module, $this->arrModulePath) && $module != 'media') {
            $module = '';
        } else {
            //load english language file first...
            $path = $this->getLangFilePath($module, 2);
            if (!empty($path)) {
                $this->loadLangFile($path);
            }
            //...and overwrite with actual language where translated.
            if($langId != 2) { //don't do it for english, already loaded.
                $path = $this->getLangFilePath($module, $langId);
                if (!empty($path)) {
                    $this->loadLangFile($path);
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

    protected function getLangFilePath($module, $langId) {
        global $_CONFIG;
        // check whether the language file exists
        $mode = in_array($this->mode, array('backend', 'update')) ? 'backend' : 'frontend';

        if (!isset($this->arrLang[$langId])) {
            $langId = $mode == 'backend' ? $this->getBackendDefaultLangId() : $this->getFrontendDefaultLangId();
        }

        $path = $this->arrModulePath[$module].$this->arrLang[$langId]['lang'].'/'.$mode.'.php';

        $customizingPath = preg_replace('#'.ASCMS_PATH.ASCMS_PATH_OFFSET.'#', ASCMS_CUSTOMIZING_PATH, $path);
        if ($_CONFIG['useCustomizings'] == 'on' && file_exists($customizingPath)) {
            $path = $customizingPath;
        }

        if (!file_exists($path)) {
            $path = '';
            $langId = $mode == 'backend' ? $this->getBackendDefaultLangId() : $this->getFrontendDefaultLangId();
            $path = $this->arrModulePath[$module].$this->arrLang[$langId]['lang'].'/'.$mode.'.php';

            $customizingPath = preg_replace('#'.ASCMS_PATH.ASCMS_PATH_OFFSET.'#', ASCMS_CUSTOMIZING_PATH, $path);
            if ($_CONFIG['useCustomizings'] == 'on' && file_exists($customizingPath)) {
                $path = $customizingPath;
            }

            if (!file_exists($path)) {
                $path = '';
            }
        }
        return $path;
    }


    /**
     * Loads the language file for the given file path
     *
     * Note that no replacements are made to the entries' contents.
     * If your strings don't work as expected, fix *them*.
     */
    protected function loadLangFile($path)
    {
        global $_ARRAYLANG;
        //require_once($path);
        require($path);
        // remove escape characters
// Pointless IMHO.  If your language entry
//    $_ARRAYLANG['demo'] = "\"quoted\"";
// does not look like '"quoted"' after it has been included, fix it!  :)
//        foreach (array_keys($_ARRAYLANG) as $langTxtId) {
//            $_ARRAYLANG[$langTxtId] = preg_replace('/\\\\"/', '"', $_ARRAYLANG[$langTxtId]);
//        }
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
    public function setCustomizedTheme($themesId=0, $customContent='')
    {
        global $objDatabase;

        // set custom content template
        $this->customContentTemplate = $customContent;

        //only set customized theme if not in printview AND no mobile devic
        if (!isset($_GET['printview']) && !$this->isInMobileView()) {
            $themesId=intval($themesId);
            if ($themesId>0){
                $objResult = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."skins WHERE id = $themesId");
                if ($objResult !== false) {
                    $this->currentThemesId=intval($objResult->fields['id']);
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
     * @return  string            The HTML language dropdown menu code
     */
    function getUserFrontendLangMenu()
    {
        global $_ARRAYLANG;

        $arrLanguageName = FWLanguage::getNameArray();
        // No dropdown at all if there is a single active frontend language
        if (count($arrLanguageName) == 1) {
            return '';
        }

        $action = CONTREXX_DIRECTORY_INDEX;
        $command = isset($_REQUEST['cmd']) ? contrexx_input2raw($_REQUEST['cmd']) : '';
        switch ($command) {
            /*case 'xyzzy':
                // Variant 1:  Use selected GET parameters only
                // Currently unused, but this could be extended by a few required
                // parameters and might prove useful for some modules.
                $query_string = '';
                // Add more as needed
                $arrParameter = array('cmd', 'act', 'tpl', 'key', );
                foreach ($arrParameter as $parameter) {
                    $value = (isset($_GET[$parameter])
                      ? $_GET[$parameter] : null);
                    if (isset($value)) {
                        $query_string .= "&$parameter=".contrexx_input2raw($value);
                    }
                }
                Html::replaceUriParameter($action, $query_string);
                // The dropdown is built below
                break;*/
            case 'shop':
            case 'country':
                // Variant 2:  Use any (GET) request parameters
                // Note that this is generally unsafe, as most modules/methods do
                // not rely on posted data only!
                $arrParameter = null;
                $uri = $_SERVER['QUERY_STRING'];
                Html::stripUriParam($uri, 'userFrontendLangId');
                parse_str($uri, $arrParameter);
                $first = true;
                foreach ($arrParameter as $name => $value) {
                $action .=
                    ($first ? '?' : '&amp;').
                    $name.'='.urlencode(contrexx_input2raw($value));
                $first = false;
                }
                // The dropdown is built below
            break;
            // TODO: Add your case here if variant 1 is enabled, too
            //case 'foobar':
            case 'content':
            case 'docsys':
            case 'recommend':
            case 'jobs':
            case 'alias':
                // The old way
                $i = 0;
                $arrVars = array();
                if (isset($_SERVER['QUERY_STRING'])) {
                    parse_str($_SERVER['QUERY_STRING'], $arrVars);
                }
                $query = isset($arrVars['cmd']) ? "?cmd=".$arrVars['cmd'] : "";
                $return = "\n<form action='index.php".$query."' method='post' name='userFrontendLangIdForm'>\n";
                $return .= "<select name='userFrontendLangId' size='1' class='chzn-select' onchange=\"document.forms['userFrontendLangIdForm'].submit()\">\n";
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
                'size="1" class="chzn-select"')."\n</form>\n";
    }


    public function getUriBy($key = '', $value = '')
    {
        $url = \Env::get('Resolver')->getUrl();
        $myUrl = clone $url;
        $myUrl->setParam($key, $value);

        return $myUrl;
    }


    public function getPageUri()
    {
        return \Env::get('Resolver')->getUrl();
    }


    public function getCurrentPageUri()
    {
        return htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, CONTREXX_CHARSET);
    }


    /**
     * Returns true if the user agent is a mobile device (smart phone, PDA etc.)
     * @todo    Maybe put this in a separate class?
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
            || strpos($ua, 'a43') !== false;
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
}
