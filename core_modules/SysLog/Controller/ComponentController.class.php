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
 * System log
 * @copyright    CLOUDREXX CMS - CLOUDREXX AG
 * @author       Michael Ritter <michael.ritter@comvation.com>
 * @package      cloudrexx
 * @subpackage   coremodule_syslog
 * @version      5.0.0
 */

namespace Cx\Core_Modules\SysLog\Controller;

/**
 * ComponentController for the system log
 * @copyright    CLOUDREXX CMS - CLOUDREXX AG
 * @author       Michael Ritter <michael.ritter@comvation.com>
 * @package      cloudrexx
 * @subpackage   coremodule_syslog
 * @version      5.0.0
 */
class ComponentController extends \Cx\Core\Core\Model\Entity\SystemComponentController implements \Cx\Core\Event\Model\Entity\EventListener {
    
    /**
     * Event name to register
     * @var string
     */
    const EVENT_NAME = 'SysLog/Add';
    
    /**
     * Registers the event
     * 
     * New logs can be added using:
     * $this->cx->getEvents()->triggerEvent('SysLog/Add', array(
     *     'severity' => 'INFO',
     *     'message' => 'my log message',
     *     'data' => 'additional debugging data',
     * ));
     */
    public function registerEvents() {
        $this->cx->getEvents()->addEvent(static::EVENT_NAME);
    }
    
    public function registerEventListeners() {
        $this->cx->getEvents()->addEventListener(static::EVENT_NAME, $this);
    }
    
    /**
     * Event handler to add logs
     * 
     * We need to do this with an event handler so there's no dependency to this component
     * @param string $eventName Name of triggered event, should always be static::EVENT_NAME
     * @param array $eventArgs Supplied arguments, should be an array (see DBG message below)
     */
    public function onEvent($eventName, array $eventArgs) {
        if ($eventName != static::EVENT_NAME) {
            return;
        }
        if (
            empty($eventArgs['severity']) ||
            empty($eventArgs['message']) ||
            empty($eventArgs['data'])
        ) {
            \DBG::msg('Triggered event "SysLog/Add" with wrong arguments. I need an array with non-empty values for the keys "severity", "message" and "data"');
            return;
        }
        $this->addSysLog(new \Cx\Core_Modules\SysLog\Model\Entity\Log(
            $eventArgs['severity'],
            $eventArgs['message'],
            $eventArgs['data']
        ));
    }
    
    /**
     * Persists a system log
     * @param \Cx\Core_Modules\SysLog\Model\Entity\Log $sysLog Log to persist
     */
    protected function addSysLog($sysLog) {
        $em = $this->cx->getDb()->getEntityManager();
        $em->persist($sysLog);
        $em->flush();
    }
    
    /**
     * This component only has a backend
     */
    public function getControllerClasses() {
        return array('Backend');
    }
}

