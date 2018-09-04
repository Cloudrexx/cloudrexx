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
     * Include all registered indexers
     */
    protected $indexers = array();

    /**
     * All file events. The indexer reacts to the events of a file
     *
     * @var array
     */
    protected $fileEvents = array('Remove', 'Add', 'Edit');

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
        foreach ($this->fileEvents as $fileEvent) {
            $eventHandlerInstance->addEvent(
                'MediaSourceFile:' . $fileEvent
            );
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
        $eventHandlerInstance = $this->cx->getEvents();
        $indexerEventListener = new \Cx\Core\MediaSource\Model\Event\IndexerEventListener($this->cx);

        foreach ($this->fileEvents as $fileEvent) {
            $eventHandlerInstance->addEventListener(
                'MediaSourceFile:' . $fileEvent,
                $indexerEventListener
            );
        }
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
     * @throws  \Cx\Core\MediaSource\Model\Entity\IndexerException if an index
     *          already exists with this extension type
     * @return void
     */
    public function registerIndexer($indexer)
    {
        global $_ARRAYLANG;

        $extensions = $indexer->getExtensions();
        foreach ($extensions as $extension) {
            if (!empty($this->indexers[$extension])) {
                throw new \Cx\Core\MediaSource\Model\Entity\IndexerException(
                    $_ARRAYLANG['TXT_INDEXER_ALREADY_EXISTS']
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
     * Get indexer by type
     *
     * @param $type string type of indexer
     *
     * @return \Cx\Core\MediaSource\Model\Entity\Indexer
     */
    public function getIndexer($type)
    {
        return $this->indexers[$type];
    }
}
