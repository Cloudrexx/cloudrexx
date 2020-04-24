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
 * Main controller for Contact
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_contact
 */

namespace Cx\Core_Modules\Contact\Controller;

/**
 * Main controller for Contact
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_contact
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {
    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    /**
     * Do something after system initialization
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * This event must be registered in the postInit-Hook definition
     * file config/postInitHooks.yml.
     * @param \Cx\Core\Core\Controller\Cx   $cx The instance of \Cx\Core\Core\Controller\Cx
     */
    public function postInit(\Cx\Core\Core\Controller\Cx $cx) {
        $globalPlaceholders = array(
            'contactFormEmail' => 'Email',
            'Company',
            'Address',
            'Zip',
            'Place',
            'Country',
            'Phone',
            'Fax',
            'coreAdminName' => 'Name',
        );
        $widgetController = $this->getComponent('Widget');
        foreach ($globalPlaceholders as $configIndex=>$placeholder) {
            if (is_int($configIndex)) {
                $configIndex = 'contact' . $placeholder;
            }
            $widgetController->registerWidget(
                new \Cx\Core_Modules\Widget\Model\Entity\FinalStringWidget(
                    $this,
                    'CONTACT_' . strtoupper($placeholder),
                    \Cx\Core\Setting\Controller\Setting::getValue(
                        $configIndex,
                        'Config'
                    )
                )
            );
        }
    }

    /**
     * Load your component.
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $moduleStyleFile, $objTemplate, $_CORELANG, $subMenuTitle;
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $contact = new \Cx\Core_Modules\Contact\Controller\Contact();
                $contact->getContactPage($this->cx->getPage());
                $moduleStyleFile = $this->cx->getCodeBaseOffsetPath() . self::getPathForType($this->getType()) . '/' . $this->getName() . '/View/Style/frontend_style.css';
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                $this->cx->getTemplate()->addBlockfile('CONTENT_OUTPUT', 'content_master', 'LegacyContentMaster.html');
                $objTemplate = $this->cx->getTemplate();

                \Permission::checkAccess(84, 'static');
                $subMenuTitle = $_CORELANG['TXT_CONTACTS'];
                $objContact = new \Cx\Core_Modules\Contact\Controller\ContactManager();
                $objContact->getPage();
                break;

            default:
                break;
        }
    }

    /**
     * Called for additional, component specific resolving
     * 
     * If /en/Path/to/Page is the path to a page for this component
     * a request like /en/Path/to/Page/with/some/parameters will
     * give an array like array('with', 'some', 'parameters') for $parts
     * 
     * This may be used to redirect to another page
     * @param array $parts List of additional path parts
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Resolved virtual page
     */
    public function resolve($parts, $page) {
        $params = $this->cx->getRequest()->getUrl()->getParamArray();
        unset($params['section']);
        unset($params['cmd']);
        $canonicalUrl = \Cx\Core\Routing\Url::fromPage($page, $params);
        header('Link: <' . $canonicalUrl->toString() . '>; rel="canonical"');
    }

    /**
     * Do something with a Response object
     * You may do page alterations here (like changing the metatitle)
     * You may do response alterations here (like set headers)
     * PLEASE MAKE SURE THIS METHOD IS MOCKABLE. IT MAY ONLY INTERACT WITH
     * resolve() HOOK.
     *
     * @param \Cx\Core\Routing\Model\Entity\Response $response Response object to adjust
     */
    public function adjustResponse(\Cx\Core\Routing\Model\Entity\Response $response) {
        // in case of an ESI request, the request URL will be set through Referer-header
        $headers = $response->getRequest()->getHeaders();
        if (isset($headers['Referer'])) {
            $refUrl = new \Cx\Lib\Net\Model\Entity\Url($headers['Referer']);
        } else {
            $refUrl = new \Cx\Lib\Net\Model\Entity\Url($response->getRequest()->getUrl()->toString());
        }

        $page   = $response->getPage();
        $params = $refUrl->getParamArray();
        unset($params['section']);
        unset($params['cmd']);
        $canonicalUrl = \Cx\Core\Routing\Url::fromPage($page, $params);
        $response->setHeader(
            'Link',
            '<' . $canonicalUrl->toString() . '>; rel="canonical"'
        );
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
        $this->cx->getEvents()->addEventListener('mediasource.load', new \Cx\Core_Modules\Contact\Model\Event\ContactEventListener($this->cx));
    }
}
