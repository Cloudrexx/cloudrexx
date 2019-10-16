<?php

/**
 * ShopProductEventListener

 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Core_Modules\MultiSite\Model\Event;

/**
 * Class ShopProductEventListenerException
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class ShopProductEventListenerException extends \Exception {}

/**
 * Class ShopProductEventListener
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class ShopProductEventListener implements \Cx\Core\Event\Model\Entity\EventListener {
    /**
     * prePersist Event
     * 
     * @param type $eventArgs
     * @throws \Cx\Core\Error\Model\Entity\ShinyException
     */
    public function prePersist($eventArgs) {
        \DBG::msg('Multisite (ShopProductEventListener): prePersist');
        
        global $_ARRAYLANG, $_CONFIG;
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                    $options = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getModuleAdditionalDataByType('Shop');
                    if (!empty($options['Product']) && $options['Product'] > 0) {
                        $count = 0;
                        $pagingLimitBkp = $_CONFIG['corePagingLimit'];
                        $_CONFIG['corePagingLimit'] = 10000;
                        $products = \Cx\Modules\Shop\Controller\Products::getByShopParams($count, 0, null, null, null, null, false, false, null, null, true);
                        $_CONFIG['corePagingLimit'] = $pagingLimitBkp;
                        foreach ($products as $product) {
                            if ($product->active()) {
                                continue;
                            }
                            $count--;
                        }
                        if ($count >= $options['Product']) {
                            throw new \Cx\Core\Error\Model\Entity\ShinyException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAXIMUM_PRODUCTS_REACHED'], $options['Product']).' <a href="index.php?cmd=Shop&act=products">'.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GO_TO_OVERVIEW'].'</a>');
                        }
                    }
                    break;
                default:
                    break;
            }
                   
        } catch (\Exception $e) {
            \DBG::msg($e->getMessage());
            throw new \Cx\Core\Error\Model\Entity\ShinyException($e->getMessage());
        }
    }
    
    public function onEvent($eventName, array $eventArgs) {
        $this->$eventName(current($eventArgs));
    }
}
