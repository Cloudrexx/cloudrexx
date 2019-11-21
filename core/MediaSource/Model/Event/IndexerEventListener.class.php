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
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 */

namespace Cx\Core\MediaSource\Model\Event;

/**
 * Event Listener for Media Source Events
 *
 * Handle all events that affect the MediaSource module.
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */
class IndexerEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener
{
    /**
     *  Add event - add new index
     *
     * @param $info array information from file/ directory
     */
    protected function mediaSourceFileAdd($info)
    {
        $this->index($info);
    }

    /**
     * Update event - update an index
     *
     * @param $info array information from file/ directory
     */
    protected function mediaSourceFileUpdate($info)
    {
        $this->index($info);
    }

    /**
     * Remove event - remove an index
     * Todo: Use file as param when FileSystem work smart
     * @param $fileInfo array information from file/ directory
     */
    protected function mediaSourceFileRemove($fileInfo)
    {
        // Can be deleted when file are params.
        $fullPath = $fileInfo['path'] . $fileInfo['name'];
        $file = new \Cx\Core\MediaSource\Model\Entity\LocalFile(
            $fullPath, null
        );
        // End

        $indexer = $this->cx->getComponent('MediaSource')->getIndexer(
            $file->getExtension()
        );

        if (empty($indexer)) {
            return;
        }

        $indexer->clearIndex((string) $file, false);
    }

    /**
     * Get all file paths and get the appropriate index for each file to be able
     * to index the file
     *
     * This indexes the file identified by $fileInfo. $fileInfo is an array
     * with the indexes "path" and "oldPath" set.
     * To index new files or re-index existing ones "oldPath" needs to be an
     * empty string. If a file has moved "oldPath" can be set to update the
     * path in the index.
     * @todo: Move this method so it can be called from ComponentController
     * @param $fileInfo array Information about a file
     */
    public function index($fileInfo) {
        $indexer = $this->getIndexer($fileInfo);
        if (!$indexer) {
            return;
        }

        // we never index files moved to tmp
        // TODO: Should we remove files that are moved to tmp?
        if (strpos($fileInfo['path'], $this->cx->getWebsiteTempPath()) === 0) {
            return null;
        }

        // let the indexer do his job
        $indexer->index($fileInfo['path'], $fileInfo['oldPath'], true, false);
    }

    /**
     * Returns the matching indexer (if any) for the supplied file info
     *
     * $fileInfo must either have the indexes "path" and "oldPath" set (for
     * add or update actions) or "path" and "name" (for delete actions)
     * @param array $fileInfo Info about the file to handle
     * @return \Cx\Core\MediaSource\Model\Entity\Indexer|null Matching Indexer or null if none
     */
    protected function getIndexer($fileInfo) {
        if (isset($fileInfo['path']) && isset($fileInfo['oldPath'])) {
            $fullPath = $fileInfo['path'];
        } else if (isset($fileInfo['path']) && isset($fileInfo['name'])) {
            $fullPath = $fileInfo['path'] . $fileInfo['name'];
        }

        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        // This is a workaround, we should use the new path instead
        if ($extension == 'part') {
            return null;
        }

        // Get the indexer for this extension (if any)
        $indexer = $this->cx->getComponent('MediaSource')->getIndexer(
            $extension
        );
        if (!$indexer) {
            return null;
        }
        return $indexer;
    }

    /**
     * Call event method. This method is overwritten to get the whole array
     * of $eventArgs as parameter in the event method, not only the first
     * element.
     *
     * @param $eventName
     * @param array $eventArgs
     */
    public function onEvent($eventName, array $eventArgs)
    {
        $indexer = $this->getIndexer($eventArgs);

        if (!$indexer) {
            return;
        }

        $prefix = \Cx\Core\MediaSource\Controller\ComponentController::FILE_EVENT_PREFIX;
        if (strpos($eventName, $prefix . 'Pre') === 0) {
            parent::onEvent(
                $prefix . substr($eventName, strlen($prefix . 'Pre')),
                array($eventArgs)
            );
        } else if (strpos($eventName, $prefix . 'PostSuccessful') === 0) {
            $indexer->commitIndex();
        } else if (strpos($eventName, $prefix . 'PostFailed') === 0) {
            $indexer->rollbackIndex();
        }
    }
}
