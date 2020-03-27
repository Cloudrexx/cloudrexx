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
 * Main controller for Shop
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */

namespace Cx\Modules\Shop\Controller;

/**
 * Main controller for Shop
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_shop
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController {

    /**
     * Return a list of Controller Classes.
     * @return array
     */
    public function getControllerClasses()
    {
        return array(
            'Backend', 'Manufacturer', 'Category', 'Pdf', 'Pricelist',
            'JsonPriceList', 'Currency', 'JsonCurrency', 'DiscountCoupon',
            'JsonDiscountCoupon', 'Order', 'JsonOrder', 'DiscountgroupCountName',
            'DiscountGroup', 'JsonDiscountGroup', 'Payment', 'PaymentProcessor'
        );
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
    public function getControllersAccessableByJson()
    {
        return array(
            'JsonPriceListController', 'JsonCurrencyController',
            'JsonDiscountCouponController', 'JsonOrderController',
            'JsonDiscountGroupController'
        );
    }

    /**
     * Load your component. It is needed for this request.
     *
     * This loads your Frontend or BackendController depending on the
     * mode Cx runs in. For modes other than frontend and backend, nothing is
     * done. This method is overwritten because the frontend view is loaded
     * without frontend controller and directly with the ShopManager
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page The resolved page
     */
    public function load(\Cx\Core\ContentManager\Model\Entity\Page $page)
    {
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                $page->setContent(Shop::getPage($page->getContent()));

                // show product title if the user is on the product details page
                $metaTitle = Shop::getPageTitle();
                if ($metaTitle) {
                    $page->setTitle($metaTitle);
                    $page->setContentTitle($metaTitle);
                    $page->setMetaTitle($metaTitle);
                }
                $metaDesc = Shop::getPageMetaDesc();
                if ($metaDesc) {
                    $page->setMetadesc($metaDesc);
                }
                $metaImage = Shop::getPageMetaImage();
                if ($metaImage) {
                    $page->setMetaimage($metaImage);
                }
                $metaKeys = Shop::getPageMetaKeys();
                if ($metaKeys) {
                    $page->setMetakeys($metaKeys);
                }
                break;

            case \Cx\Core\Core\Controller\Cx::MODE_BACKEND:
                parent::load($page);
                break;
        }
    }

    /**
     * Do something after content is loaded from DB
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page       The resolved page
     */
    public function postContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) 
    {
        switch ($this->cx->getMode()) {
            case \Cx\Core\Core\Controller\Cx::MODE_FRONTEND:
                // Show the Shop navbar in the Shop, or on every page if configured to do so
                if (!Shop::isInitialized()
                // Optionally limit to the first instance
                // && MODULE_INDEX == ''
                ) {
                    \Cx\Core\Setting\Controller\Setting::init('Shop', 'config');
                    if (\Cx\Core\Setting\Controller\Setting::getValue('shopnavbar_on_all_pages', 'Shop')) {
                        Shop::init();
                        Shop::setNavbar();
                    }

                    // replace global product blocks
                    $page->setContent(
                        preg_replace_callback(
                            '/<!--\s+BEGIN\s+(block_shop_products_category_(?:\d+)\s+-->).*<!--\s+END\s+\1/s',
                            function ($matches) {
                                $blockTemplate = new \Cx\Core\Html\Sigma();
                                $blockTemplate->setTemplate($matches[0]);
                                Shop::parse_products_blocks($blockTemplate);
                                return $blockTemplate->get();
                            },
                            $page->getContent()
                        )
                    );
                }
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
    public function resolve($parts, $page) 
    {
        $canonicalUrl = \Cx\Core\Routing\Url::fromPage($page, $this->cx->getRequest()->getUrl()->getParamArray());
        header('Link: <' . $canonicalUrl->toString() . '>; rel="canonical"');
    }

    /**
     * {@inheritdoc}
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

        if (
            !$page ||
            $page->getModule() !== $this->getName() ||
            !in_array(
                $page->getCmd(),
                array('', 'details', 'lastFive', 'products')
            )
        ) {
            return;
        }

        Shop::getPage('');
        // show product title if the user is on the product details page
        $page_metatitle = Shop::getPageTitle();
        if ($page_metatitle) {
            $page->setTitle($page_metatitle);
            $page->setContentTitle($page_metatitle);
            $page->setMetaTitle($page_metatitle);
        }

        $metaImage = Shop::getPageMetaImage();
        if ($metaImage) {
            $page->setMetaimage($metaImage);
        }
    }

    /**
     * Returns a list of command mode commands provided by this component
     *
     * @return array List of command names
     */
    public function getCommandsForCommandMode()
    {
        return array(
            'generatePdfPricelist' => new
            \Cx\Core_Modules\Access\Model\Entity\Permission(
                array('http', 'https'), // allowed protocols
                array(
                    'get',
                    'post',
                    'put',
                    'delete',
                    'trace',
                    'options',
                    'head',
                ),   // allowed methods
                false,  // requires login
                array(),
                array()
            ),
        );
    }

    /**
     * Returns the description for a command provided by this component
     *
     * @param string  $command The name of the command to fetch the description from
     * @param boolean $short   Wheter to return short or long description
     *
     * @return string Command description
     */
    public function getCommandDescription($command, $short = false)
    {
        switch ($command) {
            case 'generatePdfPricelist':
                if ($short) {
                    return 'Generates Pdf for a pricelist';
                }
                return 'Generates Pdf for a pricelist with all related 
                    categories and their products.';
            default:
                return '';
        }
    }

    /**
     * Execute one of the commands listed in getCommandsForCommandMode()
     *
     * @param string $command       Name of command to execute
     * @param array  $arguments     List of arguments for the command
     * @param array  $dataArguments (optional) List of data arguments for the command
     *
     * @see getCommandsForCommandMode()
     */
    public function executeCommand($command, $arguments, $dataArguments = array())
    {
        try {
            switch ($command) {
                case 'generatePdfPricelist':
                    if (empty($arguments['id'])) {
                        return;
                    }
                    $this->getController('Pdf')->generatePdfPricelist(
                        intval($arguments['id']),1
                    );
                    break;
            }
        } catch (\Exception $e) {
            http_response_code(400); // BAD REQUEST
            echo 'Exception of type "' . get_class($e) . '" with message "' .
                $e->getMessage() . '"';
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
    public function registerEventListeners() 
    {
        $eventListener = new \Cx\Modules\Shop\Model\Event\ShopEventListener($this->cx);
        $eventListenerTemp = new \Cx\Modules\Shop\Model\Event\RolloutTextSyncListener($this->cx);
        $this->cx->getEvents()->addEventListener('SearchFindContent',$eventListener);
        $this->cx->getEvents()->addEventListener('mediasource.load', $eventListener);
        $this->cx->getEvents()->addEventListener('TmpShopText:Replace', $eventListenerTemp);
        $this->cx->getEvents()->addEventListener('TmpShopText:Delete', $eventListenerTemp);

        $modelEvents = array(
            \Doctrine\ORM\Events::prePersist => array(
                'Currency',
                'DiscountCoupon'
            ),
            \Doctrine\ORM\Events::preUpdate => array(
                'Currency',
                'DiscountCoupon'
            ),
            \Doctrine\ORM\Events::postUpdate => array(
                'Order'
            )
        );

        foreach ($modelEvents as $eventName => $entities) {
            foreach ($entities as $entity) {
                $modelEventListener = '\\Cx\\Modules\\Shop\\Model\\Event\\' .
                    $entity . 'EventListener';

                $this->cx->getEvents()->addModelListener(
                    $eventName,
                    'Cx\\Modules\\Shop\\Model\\Entity\\' . $entity,
                    new $modelEventListener($this->cx)
                );
            }
        }
    }

    /**
     * Register your events here
     *
     * Do not do anything else here than list statements like
     * $this->cx->getEvents()->addEvent($eventName);
     */
    public function registerEvents()
    {
        $this->cx->getEvents()->addEvent('TmpShopText:Replace');
        $this->cx->getEvents()->addEvent('TmpShopText:Delete');
    }

    public function preFinalize(\Cx\Core\Html\Sigma $template)
    {
        if (    $this->cx->getMode()
            !== \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            return;
        }
        Shop::parse_products_blocks($template);
    }

}
