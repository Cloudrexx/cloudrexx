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
 * CrmEventDispatcher Class CRM
 *
 * @category   CrmEventDispatcher
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */

namespace Cx\Modules\Crm\Controller;

/**
 * CrmEventDispatcher Class CRM
 *
 * @category   CrmEventDispatcher
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */
class CrmEventDispatcher
{
    /**
    * Class object
    *
    * @access private
    * @var object
    */
    static private $instance;

    /**
    * handler
    *
    * @access protected
    * @var EventHandler[]
    */
    protected $handlers = array();

    /**
     * Constructor
     */
    private function __construct()
    {

    }

    /**
     * Dublicate copy
     *
     * @return null
     */
    private function __clone() {}

    /**
     * Get instance of the class
     *
     * @static
     * @return EventDispatcher
     */
    static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * Add handler
     *
     * @param String       $event_name    event name
     * @param EventHandler $event_handler event handler
     *
     * @return null
     */
    function addHandler($event_name, \Cx\Modules\Crm\Model\Events\CrmEventHandler $event_handler)
    {
        $this->handlers[$event_name][] = $event_handler;
    }

    /**
     * Trigger the event
     *
     * @param String $event_name event name
     * @param String $context    event context
     * @param String $info       event info
     *
     * @return boolean
     */
    function triggerEvent($event_name, $context = null, $info = null)
    {
        if (!isset($this->handlers[$event_name])) {
            //throw new \InvalidArgumentException("The event '$event_name' has been triggered, but no event handlers have been registered.");
            return false;
        }

        $event = new \Cx\Modules\Crm\Model\Entity\CrmEvent($event_name, $context, $info);

        /** @var $handler EventHandler */
        foreach ($this->handlers[$event_name] as $handler) {
            if (!$event->isCancelled()) {
                $handler->handleEvent($event);
            } else {
                return false;
            }
        };
        return true;
    }

}
