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
 * @copyright   Cloudrexx AG
 * @author Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 */

namespace Cx\Core\Event\Model\Entity;


use Cx\Core\Core\Controller\Cx;

class DefaultEventListener implements EventListener {

    /**
     * @var Cx
     */
    protected $cx;

    /**
     * @param Cx $cx
     */
    public function __construct(Cx $cx)
    {
        $this->cx = $cx;
    }

    /**
     * Get a component controller object
     *
     * @param string $name  component name
     * @return \Cx\Core\Core\Model\Entity\SystemComponentController
     * The requested component controller or null if no such component exists
     */
    public function getComponent($name)
    {
        if (empty($name)) {
            return null;
        }
        $componentRepo = $this->cx->getDb()->getEntityManager()->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $component     = $componentRepo->findOneBy(array('name' => $name));
        if (!$component) {
            return null;
        }
        return $component->getSystemComponentController();
    }

    public function onEvent($eventName, array $eventArgs) {
        $methodName = $eventName;
        if (!method_exists($this, $eventName)) {
            $eventNameParts = preg_split('/[.:\/]/', $eventName);
            $methodName = lcfirst(implode('', array_map('ucfirst',$eventNameParts)));
        }
        $this->$methodName(current($eventArgs));
    }
}
