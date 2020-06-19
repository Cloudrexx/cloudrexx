<?php

/**
 * ContactFormEventListener

 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */

namespace Cx\Core_Modules\MultiSite\Model\Event;

/**
 * Class ContactFormEventListenerException
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class ContactFormEventListenerException extends \Exception {}

/**
 * Class ContactFormEventListener
 *
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Project Team SS4U <info@comvation.com>
 * @package     contrexx
 * @subpackage  coremodule_multisite
 */
class ContactFormEventListener implements \Cx\Core\Event\Model\Entity\EventListener {
    /**
     * PrePersist Event
     * 
     * @param type $eventArgs
     * @throws \Cx\Core\Error\Model\Entity\ShinyException
     */
    public function prePersist($eventArgs) {
        \DBG::msg('Multisite (ContactFormEventListener): prePersist');
        
        try {
            \Cx\Core\Setting\Controller\Setting::init('MultiSite', '','FileSystem');
            switch (\Cx\Core\Setting\Controller\Setting::getValue('mode','MultiSite')) {
                case \Cx\Core_Modules\MultiSite\Controller\ComponentController::MODE_WEBSITE:
                    $options = \Cx\Core_Modules\MultiSite\Controller\ComponentController::getModuleAdditionalDataByType('Contact');
                    if (!empty($options['Form']) && $options['Form'] > 0) {
                        $forms = \Env::get('em')->getRepository('Cx\Core_Modules\Contact\Model\Entity\Form')->findAll();
                        $formCount = $forms ? count($forms) : 0;
                        if ($formCount >= $options['Form']) {
                            $_ARRAYLANG = \Env::get('init')->getComponentSpecificLanguageData('MultiSite', false);
                            throw new \Cx\Core\Error\Model\Entity\ShinyException(sprintf($_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_MAXIMUM_FORMS_REACHED'], $options['Form']).' <a href="index.php?cmd=Contact">'.$_ARRAYLANG['TXT_CORE_MODULE_MULTISITE_GO_TO_OVERVIEW'].'</a>');
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
