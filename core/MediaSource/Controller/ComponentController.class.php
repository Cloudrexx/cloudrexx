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
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */

namespace Cx\Core\MediaSource\Controller;

use Cx\Core\Core\Model\Entity\SystemComponentController;

/**
 * Class ComponentController
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */
class ComponentController
    extends SystemComponentController
{
    /**
     * Include all registered indexes
     */
    protected $indexers = array();

    /**
     * Register your events here
     *
     * Do not do anything else here than list statements like
     * $this->cx->getEvents()->addEvent($eventName);
     */
    public function registerEvents()
    {
        $eventHandlerInstance = $this->cx->getEvents();
        $eventHandlerInstance->addEvent('mediasource.load');
        $eventHandlerInstance->addEvent('MediaSource:Remove');
        $eventHandlerInstance->addEvent('MediaSource:Add');
        $eventHandlerInstance->addEvent('MediaSource:Edit');
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
        $eventHandlerInstance = $this->cx->getEvents();
        $mediaSourceEventListener = new \Cx\Core\MediaSource\Model\Event\MediaSourceEventListener($this->cx);
        $eventHandlerInstance->addEventListener(
            'MediaSource:Remove',
            $mediaSourceEventListener
        );
        $eventHandlerInstance->addEventListener(
            'MediaSource:Add',
            $mediaSourceEventListener
        );
        $eventHandlerInstance->addEventListener(
            'MediaSource:Edit',
            $mediaSourceEventListener
        );
    }

    public function getControllerClasses() {
        // Return an empty array here to let the component handler know that there
        // does not exist a backend, nor a frontend controller of this component.
        return array();
    }

    /**
     * Register a new indexer.
     *
     * @param $indexer \Cx\Core\MediaSource\Model\Entity\Indexer indexer
     *
     * @throws \Exception if an index already exists with this extension type
     * @return void
     */
    public function registerIndexer($indexer)
    {
        global $_ARRAYLANG;

        $extensions = $indexer->getExtensions();
        foreach ($extensions as $extension) {
            if (!empty($this->indexers[$extension])) {
                throw new \Cx\Core\MediaSource\Model\Entity\IndexerException(
                    $_ARRAYLANG['TXT_INDEX_ALREADY_EXISTS']
                );
            }
            $this->indexers[$extension] = $indexer;
        }
    }

    /**
     * List all indexer
     *
     * @return array
     */
    public function listIndexers()
    {
        return $this->indexers;
    }

    /**
     * Get indexer by id
     *
     * @param $type string type of indexer
     *
     * @return string
     */
    public function getIndexer($type)
    {
        return $this->indexers[$type];
    }
}
