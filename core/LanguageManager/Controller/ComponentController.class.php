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
 * Main controller for Language Manager
 * 
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_languagemanager
 */

namespace Cx\Core\LanguageManager\Controller;

/**
 * Main controller for Language Manager
 * 
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_languagemanager
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController implements \Cx\Core\Event\Model\Entity\EventListener {
    
    /**
     * @var array List of components who's language already is in $_ARRAYLANG
     */
    protected $componentsWithLoadedLang = array();
    
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }
    
    public function registerEventListeners() {
        $this->cx->getEvents()->addEventListener('preComponent', $this);
    }
    
    /**
     * Event handler to load component language
     * @param string $eventName Name of triggered event, should always be static::EVENT_NAME
     * @param array $eventArgs Supplied arguments, should be an array (see DBG message below)
     */
    public function onEvent($eventName, array $eventArgs) {
        global $_ARRAYLANG;
        
        // we might be in a hook where lang is not yet initialized (before resolve)
        if (!count($_ARRAYLANG)) {
            $_ARRAYLANG = array();
        }
        
        $frontend = $this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND;
        $objInit = \Env::get('init');
        switch ($eventName) {
            case 'preComponent':
                // Skip if this component's lang already is in $_ARRAYLANG
                if (
                    in_array(
                        $eventArgs['componentName'],
                        $this->componentsWithLoadedLang
                    )
                ) {
                    return;
                }
                
                $_ARRAYLANG = array_merge(
                    $_ARRAYLANG,
                    $objInit->getComponentSpecificLanguageData(
                        $eventArgs['componentName'],
                        $frontend
                    )
                );
                $this->componentsWithLoadedLang[] = $eventArgs['componentName'];
                break;
        }
    }

     /**
     * Load your component.
     * 
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $subMenuTitle, $_ARRAYLANG;
        $subMenuTitle = $_ARRAYLANG['TXT_LANGUAGE_SETTINGS'];

        $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
        $cachedRoot = $this->cx->getTemplate()->getRoot();

        \Permission::checkAccess(22, 'static');
        $objLanguageManager = new \Cx\Core\LanguageManager\Controller\LanguageManager();
        $objLanguageManager->getLanguagePage();
                
        $this->cx->getTemplate()->setRoot($cachedRoot);        
    }
    
    /**
     * Do something after resolving is done
     * 
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postResolve(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                global $objInit, $_LANGID, $_FRONTEND_LANGID, $_CORELANG, $_ARRAYLANG, $plainCmd;

                $objInit->_initBackendLanguage();
                $objInit->getUserFrontendLangId();

                $_LANGID = $objInit->getBackendLangId();
                $_FRONTEND_LANGID = $objInit->userFrontendLangId;
                /**
                 * Language constants
                 *
                 * Defined as follows:
                 * - BACKEND_LANG_ID is set to the visible backend language
                 *   in the backend *only*.  In the frontend, it is *NOT* defined!
                 *   It indicates a backend user and her currently selected language.
                 *   Use this in methods that are intended *for backend use only*.
                 *   It *MUST NOT* be used to determine the language for any kind of content!
                 * - FRONTEND_LANG_ID is set to the selected frontend or content language
                 *   both in the back- and frontend.
                 *   It *always* represents the language of content being viewed or edited.
                 *   Use FRONTEND_LANG_ID for that purpose *only*!
                 * - LANG_ID is set to the same value as BACKEND_LANG_ID in the backend,
                 *   and to the same value as FRONTEND_LANG_ID in the frontend.
                 *   It *always* represents the current users' selected language.
                 *   It *MUST NOT* be used to determine the language for any kind of content!
                 * @since 2.2.0
                 */
                define('FRONTEND_LANG_ID', $_FRONTEND_LANGID);
                define('BACKEND_LANG_ID', $_LANGID);
                define('LANG_ID', $_LANGID);

                /**
                 * Core language data
                 * @ignore
                 */
                // Corelang might be initialized by CSRF already...
                if (!is_array($_CORELANG) || !count($_CORELANG)) {
                    $_CORELANG = $objInit->loadLanguageData('core');
                }

                /**
                 * Module specific language data
                 * @ignore
                 */
                $_ARRAYLANG = $objInit->loadLanguageData($plainCmd);
                $_ARRAYLANG = array_merge($_ARRAYLANG, $_CORELANG);
                \Env::set('lang', $_ARRAYLANG);
                break;

            default:
                break;
        }
    }

    /**
     * Register the events
     */
    public function registerEvents()
    {
        $this->cx->getEvents()->addEvent('languageStatusUpdate');
    }
}