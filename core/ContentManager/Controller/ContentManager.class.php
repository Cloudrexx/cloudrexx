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
 * ContentManager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */

namespace Cx\Core\ContentManager\Controller;
use Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser;
use Doctrine\Common\Util\Debug as DoctrineDebug;

/**
 * ContentManager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */
class ContentManagerException extends \ModuleException
{

}

/**
 * ContentManager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */
class ContentManager extends \Module
{
    /**
     * Name of the cookie to be used by the jstree javascript
     * library to save the loaded nodes
     */
    const JSTREE_COOKIE_LOAD = 'jstree_load_ContentManager_node';

    /**
     * Name of the cookie to be used by the jstree javascript
     * library to save the opened nodes
     */
    const JSTREE_COOKIE_OPEN = 'jstree_open_ContentManager_node';

    //doctrine entity manager
    protected $em = null;
    //the mysql connection
    protected $db = null;
    //the init object
    protected $init = null;
    protected $pageRepository = null;
    protected $nodeRepository = null;
    //renderCM access state
    protected $backendGroups = array();
    protected $frontendGroups = array();
    protected $assignedBackendGroups = array();
    protected $assignedFrontendGroups = array();

    /**
     * @param string $act
     * @param        $template
     * @param        $db   the ADODB db object
     * @param        $init the Init object
     */
    public function __construct($act, $template, $db, $init)
    {
        parent::__construct($act, $template);

        if ($this->act == 'new') {
            $this->act = ''; //default action;
        }

        $this->em             = \Env::get('em');
        $this->db             = $db;
        $this->init           = $init;
        $this->pageRepository = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $this->nodeRepository = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');
        $this->defaultAct     = 'actRenderCM';
    }

    protected function actRenderCM()
    {
        global $_ARRAYLANG, $_CORELANG, $_CONFIG;

        \JS::activate('jqueryui');
        \JS::activate('cx');
        \JS::activate('ckeditor');
        \JS::activate('cx-form');
        \JS::activate('jstree');
        \JS::registerJS('lib/javascript/lock.js');
        \JS::registerJS('lib/javascript/jquery/jquery.history.max.js');

        $objCx = \ContrexxJavascript::getInstance();
        $objCx->setVariable('save_loaded', static::JSTREE_COOKIE_LOAD, 'contentmanager/jstree');
        $objCx->setVariable('save_opened', static::JSTREE_COOKIE_OPEN, 'contentmanager/jstree');

// this can be used to debug the tree, just add &tree=verify or &tree=fix
        $tree = null;
        if (isset($_GET['tree'])) {
            $tree = contrexx_input2raw($_GET['tree']);
        }
        if ($tree == 'verify') {
            echo '<pre>';
            print_r($this->nodeRepository->verify());
            echo '</pre>';
        } else if ($tree == 'fix') {
            // this should print "bool(true)"
            var_dump($this->nodeRepository->recover());
        }

        $themeRepo = new \Cx\Core\View\Model\Repository\ThemeRepository();
        $defaultTheme = $themeRepo->getDefaultTheme();
        $objCx->setVariable('themeId', $defaultTheme->getId(), 'contentmanager/theme');
        foreach ($themeRepo->findAll() as $theme) {
            if ($theme == $defaultTheme) {
                $objCx->setVariable('themeName', $theme->getFoldername(), 'contentmanager/theme');
            }
        }

        $this->template->addBlockfile('ADMIN_CONTENT', 'content_manager', 'Skeleton.html');

        // user has no permission to create new page, hide navigation item in admin navigation
        if (!\Permission::checkAccess(127, 'static', true)) {
            $this->template->hideBlock('content_manager_create_new_page_navigation_item');
        }

        $this->template->touchBlock('content_manager');
        $this->template->addBlockfile('CONTENT_MANAGER_MEAT', 'content_manager_meat', 'Page.html');
        $this->template->touchBlock('content_manager_meat');

        if (\Permission::checkAccess(78, 'static', true)) {
            \JS::registerCode("var publishAllowed = true;");
        } else {
            \JS::registerCode("var publishAllowed = false;");
        }
        if (
            \Permission::checkAccess(78, 'static', true) &&
            \Permission::checkAccess(115, 'static', true)
        ) {
            \JS::registerCode("var aliasManagementAllowed = true;");
            $alias_permission = "block";
            $alias_denial     = "none !important";
        } else {
            \JS::registerCode("var aliasManagementAllowed = false;");
            $alias_permission = "none !important";
            $alias_denial     = "block";
        }

        $this->template->setVariable(array(
            'CORE_CM_METAIMAGE_BUTTON' => static::showMediaBrowserButton('Metaimage')
        ));

        // MediaBrowser used by the WYSIWYG-editor
        $mediaBrowserCkeditor = new MediaBrowser();
        $mediaBrowserCkeditor->setCallback('ckeditor_image_callback');
        $mediaBrowserCkeditor->setOptions(array(
            'id' => 'ckeditor_image_button',
            'type' => 'button',
            'style' => 'display:none'
        ));

        $this->template->setVariable(array(
            'MEDIABROWSER_BUTTON_CKEDITOR' => $mediaBrowserCkeditor->getXHtml($_ARRAYLANG['TXT_CORE_CM_BROWSE']),
            'ALIAS_PERMISSION'  => $alias_permission,
            'ALIAS_DENIAL'      => $alias_denial,
            'CONTREXX_BASE_URL' => ASCMS_PROTOCOL . '://' . $_CONFIG['domainUrl'] . ASCMS_PATH_OFFSET . '/',
            'CONTREXX_LANG'     => \FWLanguage::getLanguageCodeById(BACKEND_LANG_ID),
        ));

        global $_CORELANG;
        $this->template->setVariable($_CORELANG);

        $objCx->setVariable('TXT_CORE_CM_VIEW', $_CORELANG['TXT_CORE_CM_VIEW'], 'contentmanager/lang');
        $objCx->setVariable('TXT_CORE_CM_ACTIONS', $_CORELANG['TXT_CORE_CM_ACTIONS'], 'contentmanager/lang');
        $objCx->setVariable('TXT_CORE_CM_TRANSLATIONS', $_CORELANG['TXT_CORE_CM_TRANSLATIONS'], 'contentmanager/lang');
        $objCx->setVariable('TXT_CORE_CM_VALIDATION_FAIL', $_CORELANG['TXT_CORE_CM_VALIDATION_FAIL'], 'contentmanager/lang');
        $objCx->setVariable('TXT_CORE_CM_HOME_FAIL', $_CORELANG['TXT_CORE_CM_HOME_FAIL'], 'contentmanager/lang');

        $arrLangVars = array(
            'actions' => array(
                'new'               => 'TXT_CORE_CM_ACTION_NEW',
                'copy'              => 'TXT_CORE_CM_ACTION_COPY',
                'activate'          => 'TXT_CORE_CM_ACTION_PUBLISH',
                'deactivate'        => 'TXT_CORE_CM_ACTION_UNPUBLISH',
                'publish'           => 'TXT_CORE_CM_ACTION_PUBLISH_DRAFT',
                'show'              => 'TXT_CORE_CM_ACTION_SHOW',
                'hide'              => 'TXT_CORE_CM_ACTION_HIDE',
                'delete'            => 'TXT_CORE_CM_ACTION_DELETE',
                'recursiveQuestion' => 'TXT_CORE_CM_RECURSIVE_QUESTION',
            ),
            'tooltip' => array(
                'TXT_CORE_CM_LAST_MODIFIED'                     => 'TXT_CORE_CM_LAST_MODIFIED',
                'TXT_CORE_CM_PUBLISHING_INFO_STATUSES'          => 'TXT_CORE_CM_PUBLISHING_INFO_STATUSES',
                'TXT_CORE_CM_PUBLISHING_INFO_ACTION_ACTIVATE'   => 'TXT_CORE_CM_PUBLISHING_INFO_ACTION_ACTIVATE',
                'TXT_CORE_CM_PUBLISHING_INFO_ACTION_DEACTIVATE' => 'TXT_CORE_CM_PUBLISHING_INFO_ACTION_DEACTIVATE',
                'TXT_CORE_CM_PUBLISHING_DRAFT'                  => 'TXT_CORE_CM_PUBLISHING_DRAFT',
                'TXT_CORE_CM_PUBLISHING_DRAFT_WAITING'          => 'TXT_CORE_CM_PUBLISHING_DRAFT_WAITING',
                'TXT_CORE_CM_PUBLISHING_LOCKED'                 => 'TXT_CORE_CM_PUBLISHING_LOCKED',
                'TXT_CORE_CM_PUBLISHING_PUBLISHED'              => 'TXT_CORE_CM_PUBLISHING_PUBLISHED',
                'TXT_CORE_CM_PUBLISHING_UNPUBLISHED'            => 'TXT_CORE_CM_PUBLISHING_UNPUBLISHED',
                'TXT_CORE_CM_PAGE_INFO_STATUSES'                => 'TXT_CORE_CM_PAGE_INFO_STATUSES',
                'TXT_CORE_CM_PUBLISHING_INFO_TYPES'             => 'TXT_CORE_CM_PUBLISHING_INFO_TYPES',
                'TXT_CORE_CM_PAGE_INFO_ACTION_SHOW'             => 'TXT_CORE_CM_PAGE_INFO_ACTION_SHOW',
                'TXT_CORE_CM_PAGE_INFO_ACTION_HIDE'             => 'TXT_CORE_CM_PAGE_INFO_ACTION_HIDE',
                'TXT_CORE_CM_PAGE_STATUS_BROKEN'                => 'TXT_CORE_CM_PAGE_STATUS_BROKEN',
                'TXT_CORE_CM_PAGE_STATUS_VISIBLE'               => 'TXT_CORE_CM_PAGE_STATUS_VISIBLE',
                'TXT_CORE_CM_PAGE_STATUS_INVISIBLE'             => 'TXT_CORE_CM_PAGE_STATUS_INVISIBLE',
                'TXT_CORE_CM_PAGE_STATUS_PROTECTED'             => 'TXT_CORE_CM_PAGE_STATUS_PROTECTED',
                'TXT_CORE_CM_PAGE_TYPE_HOME'                    => 'TXT_CORE_CM_PAGE_TYPE_HOME',
                'TXT_CORE_CM_PAGE_TYPE_CONTENT_SITE'            => 'TXT_CORE_CM_PAGE_TYPE_CONTENT_SITE',
                'TXT_CORE_CM_PAGE_TYPE_APPLICATION'             => 'TXT_CORE_CM_PAGE_TYPE_APPLICATION',
                'TXT_CORE_CM_PAGE_TYPE_REDIRECTION'             => 'TXT_CORE_CM_PAGE_TYPE_REDIRECTION',
                'TXT_CORE_CM_PAGE_TYPE_SYMLINK'                 => 'TXT_CORE_CM_PAGE_TYPE_SYMLINK',
                'TXT_CORE_CM_PAGE_TYPE_FALLBACK'                => 'TXT_CORE_CM_PAGE_TYPE_FALLBACK',
                'TXT_CORE_CM_PAGE_MOVE_INFO'                    => 'TXT_CORE_CM_PAGE_MOVE_INFO',
                'TXT_CORE_CM_TRANSLATION_INFO'                  => 'TXT_CORE_CM_TRANSLATION_INFO',
                'TXT_CORE_CM_PREVIEW_INFO'                      => 'TXT_CORE_CM_PREVIEW_INFO',
            ),
        );
        foreach ($arrLangVars as $subscope => $arrLang) {
            foreach ($arrLang as $name => $value) {
                $objCx->setVariable($name, $_CORELANG[$value], 'contentmanager/lang/' . $subscope);
            }
        }

        // MediaBrowser for redirect selection
        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setOptions(array('type' => 'button'));
        $mediaBrowser->setCallback('setWebPageUrlCallback');
        $mediaBrowser->setOptions(array(
            'startview' => 'sitestructure',
            'views' => 'uploader,filebrowser,sitestructure',
            'id' => 'page_target_browse'
        ));
        $this->template->setVariable(array(
            'CM_MEDIABROWSER_BUTTON' => $mediaBrowser->getXHtml($_ARRAYLANG['TXT_CORE_CM_BROWSE'])
        ));

        // MediaBrowser for symlink selection
        $mediaBrowser->setOptions(array(
            'views' => 'sitestructure',
        ));
        $this->template->setVariable(array(
            'CM_MEDIABROWSER_BUTTON_SYMLINK' => $mediaBrowser->getXHtml($_ARRAYLANG['TXT_CORE_CM_BROWSE'])
        ));

        $toggleTitles      = !empty($_SESSION['contentManager']['toggleStatuses']['toggleTitles']) ? $_SESSION['contentManager']['toggleStatuses']['toggleTitles'] : 'block';
        $toggleType        = !empty($_SESSION['contentManager']['toggleStatuses']['toggleType']) ? $_SESSION['contentManager']['toggleStatuses']['toggleType'] : 'block';
        $toggleNavigation  = !empty($_SESSION['contentManager']['toggleStatuses']['toggleNavigation']) ? $_SESSION['contentManager']['toggleStatuses']['toggleNavigation'] : 'block';
        $toggleBlocks      = !empty($_SESSION['contentManager']['toggleStatuses']['toggleBlocks']) ? $_SESSION['contentManager']['toggleStatuses']['toggleBlocks'] : 'block';
        $toggleThemes      = !empty($_SESSION['contentManager']['toggleStatuses']['toggleThemes']) ? $_SESSION['contentManager']['toggleStatuses']['toggleThemes'] : 'block';
        $toggleApplication = !empty($_SESSION['contentManager']['toggleStatuses']['toggleApplication']) ? $_SESSION['contentManager']['toggleStatuses']['toggleApplication'] : 'block';
        $toggleSidebar     = !empty($_SESSION['contentManager']['toggleStatuses']['sidebar']) ? $_SESSION['contentManager']['toggleStatuses']['sidebar'] : 'block';
        $objCx->setVariable('toggleTitles', $toggleTitles, 'contentmanager/toggle');
        $objCx->setVariable('toggleType', $toggleType, 'contentmanager/toggle');
        $objCx->setVariable('toggleNavigation', $toggleNavigation, 'contentmanager/toggle');
        $objCx->setVariable('toggleBlocks', $toggleBlocks, 'contentmanager/toggle');
        $objCx->setVariable('toggleThemes', $toggleThemes, 'contentmanager/toggle');
        $objCx->setVariable('toggleApplication', $toggleApplication, 'contentmanager/toggle');
        $objCx->setVariable('sidebar', $toggleSidebar, 'contentmanager/toggle');

        // get initial tree data
        $objJsonData = new \Cx\Core\Json\JsonData();
        $treeData = $objJsonData->jsondata(
            'node',
            'getTree',
            array(
                'get' => $_GET,
                'response' => new \Cx\Core\Routing\Model\Entity\Response(null),
            ),
            false
        );
        $objCx->setVariable('tree-data', $treeData, 'contentmanager/tree');

        if (!empty($_GET['act']) && ($_GET['act'] == 'new')) {
            $this->template->setVariable(array(
                'TITLES_DISPLAY_STYLE'          => 'display: block;',
                'TITLES_TOGGLE_CLASS'           => 'open',
                'TYPE_DISPLAY_STYLE'            => 'display: block;',
                'TYPE_TOGGLE_CLASS'             => 'open',
                'NAVIGATION_DISPLAY_STYLE'      => 'display: block;',
                'NAVIGATION_TOGGLE_CLASS'       => 'open',
                'BLOCKS_DISPLAY_STYLE'          => 'display: block;',
                'BLOCKS_TOGGLE_CLASS'           => 'open',
                'THEMES_DISPLAY_STYLE'          => 'display: block;',
                'THEMES_TOGGLE_CLASS'           => 'open',
                'APPLICATION_DISPLAY_STYLE'     => 'display: block;',
                'APPLICATION_TOGGLE_CLASS'      => 'open',
                'MULTIPLE_ACTIONS_STRIKE_STYLE' => 'display: none;',
            ));
        } else {
            $this->template->setVariable(array(
                'TITLES_DISPLAY_STYLE'      => $toggleTitles == 'none' ? 'display: none;' : 'display: block;',
                'TITLES_TOGGLE_CLASS'       => $toggleTitles == 'none' ? 'closed' : 'open',
                'TYPE_DISPLAY_STYLE'        => $toggleType == 'none' ? 'display: none;' : 'display: block;',
                'TYPE_TOGGLE_CLASS'         => $toggleType == 'none' ? 'closed' : 'open',
                'NAVIGATION_DISPLAY_STYLE'  => $toggleNavigation == 'none' ? 'display: none;' : 'display: block;',
                'NAVIGATION_TOGGLE_CLASS'   => $toggleNavigation == 'none' ? 'closed' : 'open',
                'BLOCKS_DISPLAY_STYLE'      => $toggleBlocks == 'none' ? 'display: none;' : 'display: block;',
                'BLOCKS_TOGGLE_CLASS'       => $toggleBlocks == 'none' ? 'closed' : 'open',
                'THEMES_DISPLAY_STYLE'      => $toggleThemes == 'none' ? 'display: none;' : 'display: block;',
                'THEMES_TOGGLE_CLASS'       => $toggleThemes == 'none' ? 'closed' : 'open',
                'APPLICATION_DISPLAY_STYLE' => $toggleApplication == 'none' ? 'display: none;' : 'display: block;',
                'APPLICATION_TOGGLE_CLASS'  => $toggleApplication == 'none' ? 'closed' : 'open',
            ));
        }

        $modules = $this->db->Execute("SELECT * FROM " . DBPREFIX . "modules WHERE `status` = 'y' ORDER BY `name`");
        while (!$modules->EOF) {
            $this->template->setVariable('MODULE_KEY', $modules->fields['name']);
//            $this->template->setVariable('MODULE_TITLE', $_CORELANG[$modules->fields['description_variable']]);
            $this->template->setVariable('MODULE_TITLE', ucwords($modules->fields['name']));
            $this->template->parse('module_option');
            $modules->MoveNext();
        }

        $newPageFirstLevel = isset($_GET['act']) && $_GET['act'] == 'new';

        if (\Permission::checkAccess(36, 'static', true)) {
            $this->template->touchBlock('page_permissions_tab');
            $this->template->touchBlock('page_permissions');
        } else {
            $this->template->hideBlock('page_permissions_tab');
            $this->template->hideBlock('page_permissions');
        }

        //show the caching options only if the caching system is actually active
        if ($_CONFIG['cacheEnabled'] == 'on') {
            $this->template->touchBlock('show_caching_option');
        } else {
            $this->template->hideBlock('show_caching_option');
        }

        if (\Permission::checkAccess(78, 'static', true)) {
            $this->template->hideBlock('release_button');
        } else {
            $this->template->hideBlock('publish_button');
            $this->template->hideBlock('refuse_button');
        }

        // show no access page if the user wants to create new page in first level but he does not have enough permissions
        if ($newPageFirstLevel) {
            \Permission::checkAccess(127, 'static');
        }

        $editViewCssClass = '';
        if ($newPageFirstLevel) {
            $editViewCssClass = 'edit_view';
            $this->template->hideBlock('refuse_button');
        }

        $cxjs = \ContrexxJavascript::getInstance();
        $cxjs->setVariable('confirmDeleteQuestion', $_ARRAYLANG['TXT_CORE_CM_CONFIRM_DELETE'], 'contentmanager/lang');
        $cxjs->setVariable(
            'cleanAccessData',
            $objJsonData->jsondata(
                'page',
                'getAccessData',
                array(
                    'response' => new \Cx\Core\Routing\Model\Entity\Response(null),
                ),
                false
            ),
            'contentmanager'
        );
        $cxjs->setVariable('contentTemplates', $this->getCustomContentTemplates(), 'contentmanager');
        $cxjs->setVariable('defaultTemplates', $this->getDefaultTemplates(), 'contentmanager/themes');
        $cxjs->setVariable('templateFolders', $this->getTemplateFolders(), 'contentmanager/themes');
        $cxjs->setVariable(
            'availableBlocks',
            $objJsonData->jsondata(
                'Block',
                'getBlocks',
                array(
                    'response' => new \Cx\Core\Routing\Model\Entity\Response(null),
                ),
                false
            ),
            'contentmanager'
        );

        // TODO: move including of add'l JS dependencies to cx obj from /cadmin/index.html
        $getLangOptions=$this->getLangOptions();
        $multiLocaleMode ='';
        if (!$getLangOptions) {
            $multiLocaleMode ='cm-single-locale';
        }

        $this->template->setVariable('CONTENTMANAGER_LOCALE_CSS_CLASS', $statusPageLayout);

        // TODO: move including of add'l JS dependencies to cx obj from /cadmin/index.html
        $this->template->setVariable('SKIN_OPTIONS', $this->getSkinOptions());
        $this->template->setVariable('LANGSWITCH_OPTIONS', $this->getLangOptions());
        $this->template->setVariable('LANGUAGE_ARRAY', json_encode($this->getLangArray()));
        $this->template->setVariable('FALLBACK_ARRAY', json_encode($this->getFallbackArray()));
        $this->template->setVariable('LANGUAGE_LABELS', json_encode($this->getLangLabels()));
        $this->template->setVariable('EDIT_VIEW_CSS_CLASS', $editViewCssClass);

        $this->template->touchBlock('content_manager_language_selection');

        $editmodeTemplate = new \Cx\Core\Html\Sigma(ASCMS_CORE_PATH . '/ContentManager/View/Template/Backend');
        $editmodeTemplate->loadTemplateFile('content_editmode.html');
        $editmodeTemplate->setVariable(array(
            'TXT_EDITMODE_TEXT'    => $_CORELANG['TXT_FRONTEND_EDITING_SELECTION_TEXT'],
            'TXT_EDITMODE_CODE'    => $_CORELANG['TXT_FRONTEND_EDITING_SELECTION_MODE_PAGE'],
            'TXT_EDITMODE_CONTENT' => $_CORELANG['TXT_FRONTEND_EDITING_SELECTION_MODE_CONTENT'],
        ));
        $cxjs->setVariable(array(
            'editmodetitle'      => $_CORELANG['TXT_FRONTEND_EDITING_SELECTION_TITLE'],
            'editmodecontent'    => $editmodeTemplate->get(),
            'ckeditorconfigpath' => \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Wysiwyg')->getConfigPath(),
            'regExpUriProtocol'  =>  \FWValidator::REGEX_URI_PROTO,
            'contrexxBaseUrl'    => ASCMS_PROTOCOL . '://' . $_CONFIG['domainUrl'] . ASCMS_PATH_OFFSET . '/',
            'contrexxPathOffset' => ASCMS_PATH_OFFSET,
            'showLocaleTagsByDefault'  => \Cx\Core\Setting\Controller\Setting::getValue(
                'showLocaleTagsByDefault',
                'Config'
            ),
        ), 'contentmanager');

        // manually set Wysiwyg variables as the Ckeditor will be
        // loaded manually through JavaScript (and not properly through the
        // component interface)
        $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
        $mediaSourceManager = \Cx\Core\Core\Controller\Cx::instanciate()
            ->getMediaSourceManager();
        $mediaSource        = current($mediaSourceManager->getMediaTypes());
        $mediaSourceDir     = $mediaSource->getDirectory();
        $cxjs->setVariable(array(
            'ckeditorUploaderId'   => $uploader->getId(),
            'ckeditorUploaderPath' => $mediaSourceDir[1] . '/'
        ), 'wysiwyg');
    }

    protected function getThemes()
    {
        $query = "SELECT id,themesname FROM " . DBPREFIX . "skins ORDER BY id";
        $rs    = $this->db->Execute($query);

        $themes = array();
        while (!$rs->EOF) {
            $themes[$rs->fields['id']] = $rs->fields['themesname'];
            $rs->MoveNext();
        }

        return $themes;
    }

    protected function getSkinOptions()
    {
        $options = '';
        foreach ($this->getThemes() as $id => $name) {
            $options .= '<option value="' . $id . '">' . $name . '</option>' . "\n";
        }

        return $options;
    }

    protected function getLangOptions()
    {
        global $_CORELANG;

        $output = '';
        $language=\FWLanguage::getActiveFrontendLanguages();
        if (count($language)>1) {
            $output .= '<select id="language" class="chzn-select" data-disable_search="false" data-no_results_text="' . $_CORELANG['TXT_CORE_LOCALE_DOESNT_EXIST'] . '">';
            foreach ($language as $lang) {
                $selected = $lang['id'] == FRONTEND_LANG_ID ? ' selected="selected"' : '';
                $output .= '<option value="' . \FWLanguage::getLanguageCodeById($lang['id']) . '"' . $selected . '>' . $lang['name'] . '</option>';
            }
            $output .='</select>';
        }
        $output .= '<input type="hidden"  name="languageCount" id="languageCount" value="'.count($language).'">';
        return $output;
    }


    protected function getLangLabels()
    {
        $output = array();
        foreach (\FWLanguage::getActiveFrontendLanguages() as $lang) {
            $output[\FWLanguage::getLanguageCodeById($lang['id'])] = $lang['name'];
        }

        return $output;
    }

    protected function getLangArray()
    {
        $output = array();
        // set selected frontend language as first language
        // jstree does display the tree of the first language
        $output[] = \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID);
        foreach (\FWLanguage::getActiveFrontendLanguages() as $lang) {
            if ($lang['id'] == FRONTEND_LANG_ID) {
                continue;
            }
            $output[] = \FWLanguage::getLanguageCodeById($lang['id']);
        }

        return $output;
    }

    protected function getFallbackArray()
    {
        $fallbacks = \FWLanguage::getFallbackLanguageArray();
        $output    = array();
        foreach ($fallbacks as $key => $value) {
            $output[\FWLanguage::getLanguageCodeById($key)] = \FWLanguage::getLanguageCodeById($value);
        }

        return $output;
    }

    protected function getCustomContentTemplates()
    {
        $templates = array();
        // foreach theme
        foreach ($this->getThemes() as $id => $name) {
            $templates[$id] = $this->init->getCustomContentTemplatesForTheme($id);
        }

        return $templates;
    }

    protected function getDefaultTemplates()
    {
        $themeRepo = new \Cx\Core\View\Model\Repository\ThemeRepository();

        $defaultThemes = array();
        foreach (\FWLanguage::getActiveFrontendLanguages() as $frontendLanguage) {
            $theme = $themeRepo->getDefaultTheme(
                \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB,
                $frontendLanguage['id']);
            if (!$theme) {
                continue;
            }
            $defaultThemes[$frontendLanguage['lang']] = $theme->getId();
        }

        return $defaultThemes;
    }

    protected function getTemplateFolders()
    {
        $query = 'SELECT `id`, `foldername` FROM `' . DBPREFIX . 'skins`';
        $rs    = $this->db->Execute($query);

        $folderNames = array();
        while (!$rs->EOF) {
            $folderNames[$rs->fields['id']] = $rs->fields['foldername'];
            $rs->MoveNext();
        }

        return $folderNames;
    }

    /**
     * Display the MediaBrowser button
     *
     * @global array $_ARRAYLANG
     *
     * @param string $name callback function name
     * @param string $type mediabrowser type
     *
     * @return string
     */
    protected function showMediaBrowserButton($name, $type = 'filebrowser')
    {
        if (empty($name)) {
            return;
        }

        global $_ARRAYLANG;

        $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
        $mediaBrowser->setOptions(array(
            'type' => 'button',
            'views' => $type
        ));
        $mediaBrowser->setCallback('cx.cm.setSelected' . ucfirst($name));

        return $mediaBrowser->getXHtml($_ARRAYLANG['TXT_CORE_CM_BROWSE']);
    }
}
