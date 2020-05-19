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
 * Main controller for Access
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_access
 */

namespace Cx\Core_Modules\Access\Controller;

/**
 * Main controller for Access
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_access
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * Returns all Controller class names for this component (except this)
     *
     * Be sure to return all your controller classes if you add your own
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses() {
        return array('EsiWidget', 'RandomEsiWidget');
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
        return array('EsiWidgetController', 'RandomEsiWidgetController');
    }

    /**
     * Returns a list of command mode commands provided by this component
     * @return array List of command names
     */
    public function getCommandsForCommandMode() {
        return array('Access');
    }

    /**
     * Returns the description for a command provided by this component
     * @param string $command The name of the command to fetch the description from
     * @param boolean $short Wheter to return short or long description
     * @return string Command description
     */
    public function getCommandDescription($command, $short = false) {
        switch ($command) {
            case 'Access':
                if ($short) {
                    return 'Provides cleanup functions for user profiles';
                }
                return './cx Access removeUselessProfileImages

Drops all no-longer required profile images';
                break;
        }
        return '';
    }

    /**
     * Execute one of the commands listed in getCommandsForCommandMode()
     * @see getCommandsForCommandMode()
     * @param string $command Name of command to execute
     * @param array $arguments List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     * @return void
     */
    public function executeCommand($command, $arguments, $dataArguments = array()) {
        switch ($command) {
            case 'Access':
                switch (current($arguments)) {
                    case 'removeUselessProfileImages':
                        \Cx\Core_Modules\Access\Controller\AccessLib::removeUselessImages();
                        break;
                }
                break;
        }
    }

    /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_CORELANG, $subMenuTitle, $objTemplate;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objAccess = new Access(\Env::get('cx')->getPage()->getContent());
                $pageTitle = \Env::get('cx')->getPage()->getTitle();
                $pageMetaTitle = \Env::get('cx')->getPage()->getMetatitle();
                \Env::get('cx')->getPage()->setContent($objAccess->getPage($pageMetaTitle, $pageTitle));
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                $subMenuTitle = $_CORELANG['TXT_COMMUNITY'];
                $objAccessManager = new AccessManager();
                $objAccessManager->getPage();
                break;

            default:
                break;
        }
    }

    /**
     * Do something after resolving is done
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postResolve(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:

                global $plainCmd;

                $objTemplate = $this->cx->getTemplate();
                $objFWUser = \FWUser::getFWUserObject();

                /* authentification */
                $loggedIn = $objFWUser->objUser->login(true); //check if the user is already logged in
                if (   !$loggedIn
                    && (   (!empty($_POST['USERNAME']) && !empty($_POST['PASSWORD']))
                        || (!empty($_GET['auth-token']) && !empty($_GET['user-id'])))
                    && (!isset($_GET['cmd']) || $_GET['cmd'] !== 'Login')
                    && (!isset($_GET['act']) || $_GET['act'] !== 'resetpw')
                ) {
                    //not logged in already - do captcha and password checks
                    if ($objFWUser->checkAuth()) {
                        //Clear cache
                        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
                        $cx->getEvents()->triggerEvent(
                            'clearEsiCache',
                            array(
                                'Widget',
                                $this->getSessionBasedWidgetNames(),
                            )
                        );
                    }
                }

                // User only gets the backend if he's logged in.
                // Exception: If it is a JsonData request, then the request will be
                //            processed. In that case, JsonData will take over the
                //            required access/permission check.
                //            Default permission rule by JsonData is set to
                //            only allow the execution of requests where the
                //            requester is signed-in.
                if (!$objFWUser->objUser->login(true) && $plainCmd != 'JsonData') {
                    $plainCmd = 'Login';
                    // If the user isn't logged in, the login mask will be showed.
                    // abort further processing
                    break;
                } else {
                    $userData = array(
                        'id' => \FWUser::getFWUserObject()->objUser->getId(),
                        'name' => \FWUser::getFWUserObject()->objUser->getUsername(),
                    );
                    \Env::get('cx')->getDb()->setUsername(json_encode($userData));
                }

                $objUser = \FWUser::getFWUserObject()->objUser;
                $firstname = $objUser->getProfileAttribute('firstname');
                $lastname = $objUser->getProfileAttribute('lastname');

                if (!empty($firstname) && !empty($lastname)) {
                    $txtProfile = $firstname . ' ' . $lastname;
                } else {
                    $txtProfile = $objUser->getUsername();
                }

                $objTemplate->setVariable(array(
                    'TXT_PROFILE' => contrexx_raw2xhtml($txtProfile),
                    'USER_ID' => $objFWUser->objUser->getId(),
                ));

                if ($loggedIn) {
                    break;
                }

                if (isset($_POST['redirect'])) {
                    $redirect = \FWUser::getRedirectUrl(urlencode($_POST['redirect']));
                    \Cx\Core\Csrf\Controller\Csrf::header('location: ' . $redirect);
                } elseif (!empty($_GET['auth-token'])) {
                    \Cx\Core\Csrf\Controller\Csrf::header('location: ' . \Env::get('cx')->getWebsiteBackendPath() . '/');
                }
                break;

            default:
                break;
        }
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
        $eventListener = new \Cx\Core_Modules\Access\Model\Event\AccessEventListener($this->cx);
        $evm->addEventListener('mediasource.load', $eventListener);
        // locale event listener
        $localeLocaleEventListener = new \Cx\Core_Modules\Access\Model\Event\LocaleLocaleEventListener($this->cx);
        $evm->addModelListener('postPersist', 'Cx\\Core\\Locale\\Model\\Entity\\Locale', $localeLocaleEventListener);
        $evm->addModelListener('preRemove', 'Cx\\Core\\Locale\\Model\\Entity\\Locale', $localeLocaleEventListener);
    }

    /**
     * Do something after system initialization
     *
     * This event must be registered in the postInit-Hook definition
     * file config/postInitHooks.yml.
     * @param \Cx\Core\Core\Controller\Cx   $cx The instance of \Cx\Core\Core\Controller\Cx
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx)
    {
        $userSettings     = \User_Setting::getSettings();
        $widgetController = $this->getComponent('Widget');
        foreach (
            array(
                'logged_in',
                'logged_out',
            ) as $widgetNamePrefix
        ) {
            for ($i = 0; $i <= 10; $i++) {
                $widgetName = 'access_' . $widgetNamePrefix;
                if ($i > 0) {
                    $widgetName .= $i;
                }
                $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                    $this,
                    $widgetName,
                    \Cx\Core_Modules\Widget\Model\Entity\Widget::TYPE_BLOCK
                );
                $widget->setEsiVariable(
                    \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER
                );
                $widgetController->registerWidget(
                    $widget
                );
            }
        }

        $widgetNames     = array(
            'access_currently_online_member_list',
            'access_last_active_member_list',
            'access_latest_registered_member_list',
            'access_birthday_member_list',
            'access_next_birthday_member_list',
        );
        foreach ($widgetNames as $widgetName) {
            $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
                $this,
                $widgetName,
                \Cx\Core_Modules\Widget\Model\Entity\Widget::TYPE_BLOCK
            );
            $widget->setEsiVariable(
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER
            );
            $widgetController->registerWidget(
                $widget
            );
        }

        if ($userSettings['block_random_access_users']['status']) {
            $widget = new \Cx\Core_Modules\Widget\Model\Entity\RandomEsiWidget(
                $this,
                'access_random_users',
                \Cx\Core_Modules\Widget\Model\Entity\Widget::TYPE_BLOCK
            );
            $widget->setEsiVariable(
                \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER
            );
            $widget->setUniqueRepetitionCount(
                $userSettings['block_random_access_users']['value']
            );
            $widgetController->registerWidget(
                $widget
            );
        }

        $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
            $this,
            'access_user',
            \Cx\Core_Modules\Widget\Model\Entity\Widget::TYPE_PLACEHOLDER
        );
        $widget->setEsiVariable(
            \Cx\Core_Modules\Widget\Model\Entity\EsiWidget::ESI_VAR_ID_USER
        );
        $widgetController->registerWidget(
            $widget
        );

        $widget = new \Cx\Core_Modules\Widget\Model\Entity\EsiWidget(
            $this,
            'ACCESS_USER_COUNT'
        );
        $widgetController->registerWidget(
            $widget
        );
    }
    
    /**
     * Do something before main template gets parsed
     *
     * @param \Cx\Core\Html\Sigma                       $template   The main template
     */
    public function preFinalize(\Cx\Core\Html\Sigma $template) {
        if ($this->cx->getMode() != \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            return;
        }
        // make all language data of Access component globally available
        $template->setVariable(\Env::get('init')->getComponentSpecificLanguageData($this->getName()));
    }

    /**
     * Returns a list of widget names based on the given base name
     * @param string $baseName Widget base name
     * @return array List of widget names
     */
    protected function getRepeatableWidgetNames($baseName) {
        $widgets = array();
        for ($i = 0; $i <= 10; $i++) {
            $widgetName = 'access_' . $baseName;
            if ($i > 0) {
                $widgetName .= $i;
            }
            $widgets[] = $widgetName;
        }
        return $widgets;
    }

    /**
     * Get all session based widget names
     * @return array List of widget names
     */
    public function getSessionBasedWidgetNames()
    {
        return array_merge(
            array(
                'access_currently_online_member_list',
                'access_last_active_member_list',
            ),
            $this->getRepeatableWidgetNames('logged_in'),
            $this->getRepeatableWidgetNames('logged_out')
        );
    }

    /**
     * Get all user data based widget names
     * @return array List of widget names
     */
    public function getUserDataBasedWidgetNames() {
        return array_merge(
            array(
                'ACCESS_USER_COUNT',
                'access_currently_online_member_list',
                'access_last_active_member_list',
                'access_latest_registered_member_list',
                'access_birthday_member_list',
                'access_next_birthday_member_list',
                'access_random_users',
                'access_user',
            ),
            $this->getRepeatableWidgetNames('logged_in')
        );
    }
}
