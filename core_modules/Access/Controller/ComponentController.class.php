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
use Cx\Core_Modules\Access\Model\Event\AccessEventListener;

/**
 * Main controller for Access
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_access
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
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
     * Do something before content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function preContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $content = \Env::get('cx')->getPage()->getContent();
                \FWUser::parseLoggedInOutBlocks($content);
                \Env::get('cx')->getPage()->setContent($content);
                break;

            default:
                break;
        }
    }


    /**
     * Do something after content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $objTemplate = $this->cx->getTemplate();

                // ACCESS: parse access_logged_in[1-9] and access_logged_out[1-9] blocks
                \FWUser::parseLoggedInOutBlocks($objTemplate);

                // currently online users
                $objAccessBlocks = false;
                if ($objTemplate->blockExists('access_currently_online_member_list')) {
                    if (\FWUser::showCurrentlyOnlineUsers() && ( $objTemplate->blockExists('access_currently_online_female_members') || $objTemplate->blockExists('access_currently_online_male_members') || $objTemplate->blockExists('access_currently_online_members'))) {
                            $objAccessBlocks = new AccessBlocks();
                        if ($objTemplate->blockExists('access_currently_online_female_members'))
                            $objAccessBlocks->setCurrentlyOnlineUsers('female');
                        if ($objTemplate->blockExists('access_currently_online_male_members'))
                            $objAccessBlocks->setCurrentlyOnlineUsers('male');
                        if ($objTemplate->blockExists('access_currently_online_members'))
                            $objAccessBlocks->setCurrentlyOnlineUsers();
                    } else {
                        $objTemplate->hideBlock('access_currently_online_member_list');
                    }
                }

                // last active users
                if ($objTemplate->blockExists('access_last_active_member_list')) {
                    if (\FWUser::showLastActivUsers() && ( $objTemplate->blockExists('access_last_active_female_members') || $objTemplate->blockExists('access_last_active_male_members') || $objTemplate->blockExists('access_last_active_members'))) {
                        if (!$objAccessBlocks)
                            $objAccessBlocks = new AccessBlocks();
                        if ($objTemplate->blockExists('access_last_active_female_members'))
                            $objAccessBlocks->setLastActiveUsers('female');
                        if ($objTemplate->blockExists('access_last_active_male_members'))
                            $objAccessBlocks->setLastActiveUsers('male');
                        if ($objTemplate->blockExists('access_last_active_members'))
                            $objAccessBlocks->setLastActiveUsers();
                    } else {
                        $objTemplate->hideBlock('access_last_active_member_list');
                    }
                }

                // latest registered users
                if ($objTemplate->blockExists('access_latest_registered_member_list')) {
                    if (\FWUser::showLatestRegisteredUsers() && ( $objTemplate->blockExists('access_latest_registered_female_members') || $objTemplate->blockExists('access_latest_registered_male_members') || $objTemplate->blockExists('access_latest_registered_members'))) {
                        if (!$objAccessBlocks)
                            $objAccessBlocks = new AccessBlocks();
                        if ($objTemplate->blockExists('access_latest_registered_female_members'))
                            $objAccessBlocks->setLatestRegisteredUsers('female');
                        if ($objTemplate->blockExists('access_latest_registered_male_members'))
                            $objAccessBlocks->setLatestRegisteredUsers('male');
                        if ($objTemplate->blockExists('access_latest_registered_members'))
                            $objAccessBlocks->setLatestRegisteredUsers();
                    } else {
                        $objTemplate->hideBlock('access_latest_registered_member_list');
                    }
                }

                // birthday users
                if ($objTemplate->blockExists('access_birthday_member_list')) {
                    if (\FWUser::showBirthdayUsers() && ( $objTemplate->blockExists('access_birthday_female_members') || $objTemplate->blockExists('access_birthday_male_members') || $objTemplate->blockExists('access_birthday_members'))) {
                        if (!$objAccessBlocks)
                            $objAccessBlocks = new AccessBlocks();
                        if ($objAccessBlocks->isSomeonesBirthdayToday()) {
                            if ($objTemplate->blockExists('access_birthday_female_members'))
                                $objAccessBlocks->setBirthdayUsers('female');
                            if ($objTemplate->blockExists('access_birthday_male_members'))
                                $objAccessBlocks->setBirthdayUsers('male');
                            if ($objTemplate->blockExists('access_birthday_members'))
                                $objAccessBlocks->setBirthdayUsers();
                            $objTemplate->touchBlock('access_birthday_member_list');
                        } else {
                            $objTemplate->hideBlock('access_birthday_member_list');
                        }
                    } else {
                        $objTemplate->hideBlock('access_birthday_member_list');
                    }
                }
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

                global $plainCmd, $isRegularPageRequest;
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
                    $objFWUser->checkAuth();
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
                    // This mask has its own template handling.
                    // So we don't need to load any templates in the index.php.
                    $isRegularPageRequest = false;
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
                    'TXT_PROFILE' => $txtProfile,
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
        $eventListener = new AccessEventListener($this->cx);
        $this->cx->getEvents()->addEventListener('mediasource.load', $eventListener);
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
}
