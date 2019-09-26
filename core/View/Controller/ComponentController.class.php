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
 * This is the main Controller for View
 * @copyright   Cloudrexx AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_view
 * @version     5.0.0
 */

namespace Cx\Core\View\Controller;

/**
 * This is the main Controller for View
 * @copyright   Cloudrexx AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_view
 * @version     5.0.0
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * Returns all Controller class names for this component (except this)
     *
     * Be sure to return all your controller classes if you add your own
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses() {
        return array('EsiWidget');
    }

    /**
     * Returns a list of JsonAdapter class names
     *
     * The array values might be a class name without namespace. In that case
     * the namespace \Cx\{component_type}\{component_name}\Controller is used.
     * If the array value starts with a backslash, no namespace is added.
     *
     * Avoid calculation of anything, just return an array!
     * @return array List of ComponentController classes
     */
    public function getControllersAccessableByJson() {
        return array('EsiWidgetController');
    }

    /**
     * Do something after system initialization
     *
     * This event must be registered in the postInit-Hook definition
     * file config/postInitHooks.yml.
     * @param \Cx\Core\Core\Controller\Cx   $cx The instance of \Cx\Core\Core\Controller\Cx
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx) {
        $widgetController = $this->getComponent('Widget');
        $widgetNames = array(
            'STANDARD_URL',
            'MOBILE_URL',
            'PRINT_URL',
            'PDF_URL',
            'APP_URL',
        );
        foreach ($widgetNames as $widgetName) {
            $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                $this,
                $widgetName,
                \Cx\Core_Modules\Widget\Model\Entity\Widget::TYPE_PLACEHOLDER,
                '',
                '',
                array(
                    'ref' => '$(HTTP_REFERER)',
                )
            );
            $widget->setEsiVariable(0);
            $widgetController->registerWidget(
                $widget
            );
        }
    }

    /**
     * @{inheritdoc}
     */
    public function registerEvents() {
        $evm = $this->cx->getEvents();
        $evm->addEvent($this->getName() . '.Sigma:loadContent');
        $evm->addEvent($this->getName() . '.Sigma:setVariable');
    }

    /**
     * Register your event listeners here
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * Keep in mind, that you can also register your events later.
     * Do not do anything else here than initializing your event listeners and
     * list statements like
     * $this->cx->getEvents()->addEventListener($eventName, $listener);
     */
    public function registerEventListeners() {
        $evm = $this->cx->getEvents();
        // locale event listener
        $localeLocaleEventListener = new \Cx\Core\View\Model\Event\LocaleLocaleEventListener($this->cx);
        $evm->addModelListener('postPersist', 'Cx\\Core\\Locale\\Model\\Entity\\Locale', $localeLocaleEventListener);
        $evm->addModelListener('preRemove', 'Cx\\Core\\Locale\\Model\\Entity\\Locale', $localeLocaleEventListener);
    }

    /**
     * Returns the channel for the given request
     * @todo Replace Request and Page by Response once it's used in the system
     * @param \Cx\Core\Routing\Model\Entity\Request $request Cx request
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page for request
     * @return string Channel name (see Theme entity for constants)
     */
    public function getChannel($request, $page) {
        if (
            $request->hasParam('printview') &&
            $request->getParam('printview') == 1
        ) {
            return \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_PRINT;
        }
        if (
            $request->hasParam('pdfview') &&
            $request->getParam('pdfview') == 1
        ) {
            return \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_PDF;
        }
        if (
            $request->hasParam('appview') &&
            $request->getParam('appview') == 1
        ) {
            return \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_APP;
        }
        if (
            (
                $request->hasParam('smallscreen') &&
                $request->getParam('smallscreen') >= 1
            ) ||
            (
                $request->hasCookie('smallscreen') &&
                $request->getCookie('smallscreen') >= 1
            ) ||
            (
                $request->isMobilePhone() &&
                !$request->isTablet()
            )
        ) {
            $em = $this->cx->getDb()->getEntityManager();
            $themeRepo = $em->getRepository('Cx\Core\View\Model\Entity\Theme');
            $theme = $themeRepo->getDefaultTheme(
                \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_MOBILE,
                $page->getLang()
            );
            if ($theme) {
                return \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_MOBILE;
            }
        }
        return \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB;
    }

    /**
     * Returns the theme for the given request
     * @todo Replace Request and Page by Response once it's used in the system
     * @param \Cx\Core\Routing\Model\Entity\Request $request Cx request
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page for request
     * @return \Cx\Core\View\Model\Entity\Theme Theme for given request
     */
    public function getTheme($request, $page) {
        return $this->getThemeFromChannel(
            $this->getChannel($request, $page),
            $page
        );
    }

    /**
     * Returns the theme for the given request
     * @param string $channel Channel identifier
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page
     * @return \Cx\Core\View\Model\Entity\Theme Theme for given request
     */
    public function getThemeFromChannel($channel, $page) {
        $themeRepo = new \Cx\Core\View\Model\Repository\ThemeRepository();
        if (
            $page->getSkin() &&
            (
                $channel == \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB ||
                $page->getUseSkinForAllChannels()
            )
        ) {
            $theme = $themeRepo->findById($page->getSkin());
            if ($theme) {
                return $theme;
            }
        }
        return $themeRepo->getDefaultTheme(
            $channel,
            $page->getLang()
        );
    }

    /**
     * Returns the content template file for the given request
     * @todo Replace Request and Page by Response once it's used in the system
     * @param \Cx\Core\Routing\Model\Entity\Request $request Cx request
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page for request
     * @return \Cx\Core\ViewManager\Model\Entity\ViewManagerFile Content template file
     */
    public function getContentTemplateFile($request, $page) {
        return $this->getContentTemplateFileFromChannel(
            $this->getChannel($request, $page),
            $this->getTheme($request, $page),
            $page
        );
    }

    /**
     * Returns the content template file for the given theme and channel
     * @param string $channel Channel identifier
     * @param \Cx\Core\Viewg\Model\Entity\Theme $theme Theme
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page
     * @return string Absolute path to content template file
     */
    public function getContentTemplateFileFromChannel($channel, $theme, $page) {
        $contentFileName = 'content.html';
        if ($page->getModule() == 'Home') {
            $contentFileName = 'home.html';
        }
        $currentTheme = $theme;
        if (empty($page->getCustomContent())) {
            return $currentTheme->getFilePath(
                $currentTheme->getFoldername() . '/' . $contentFileName
            );
        }
        $em = $this->cx->getDb()->getEntityManager();
        $themeRepo = new \Cx\Core\View\Model\Repository\ThemeRepository();
        $defaultTheme = $themeRepo->getDefaultTheme(
            $channel,
            $page->getLang()
        );
        $fileInCurrentTheme = $currentTheme->getFilePath(
            $currentTheme->getFoldername() . $page->getCustomContent()
        );
        $fileInDefaultTheme = $defaultTheme->getFilePath(
            $defaultTheme->getFoldername() . $page->getCustomContent()
        );
        if (
            $channel == \Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB ||
            $page->getUseCustomContentForAllChannels()
        ) {
            // get file from current theme
            if ($fileInCurrentTheme) {
                return $fileInCurrentTheme;
            }
            // get file from default theme
            if ($fileInDefaultTheme) {
                return $fileInDefaultTheme;
            }
        } else {
            // get file from default theme
            if ($fileInDefaultTheme) {
                return $fileInDefaultTheme;
            }
            // get file from current theme
            if ($fileInCurrentTheme) {
                return $fileInCurrentTheme;
            }
        }
    }

    /**
     * Returns HTML-Code to show an "experimental" flag
     *
     * @param string $tooltip (optional) Tooltip to show when hovering over the flag
     */
    public function flagExperimental($tooltip = '') {
        $template = new \Cx\Core\Html\Sigma(
            $this->getDirectory() . '/View/Template/Backend'
        );
        $template->loadTemplateFile('Experimental.html');
        if (empty($tooltip)) {
            $template->hideBlock('with-tooltip');
            $template->touchBlock('without-tooltip');
        } else {
            $template->setVariable('TOOLTIP', $tooltip);
            $template->touchBlock('with-tooltip');
            $template->hideBlock('without-tooltip');
        }
        return $template->get();
    }
}
