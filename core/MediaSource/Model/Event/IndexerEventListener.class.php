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

        $indexer->clearIndex((string) $file);
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
    public function index($fileInfo)
    {
        $fullPath = $fileInfo['path'];
        $fullOldPath = $fileInfo['oldPath'];
        $path = $fullPath;

        if (!empty($fullOldPath)) {
            $path = $fullOldPath;
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        // This is a workaround, we should use the new path instead
        if ($extension == 'part') {
            return;
        }

        // Get the indexer for this extension (if any)
        $indexer = $this->cx->getComponent('MediaSource')->getIndexer(
            $extension
        );
        if (!$indexer) {
            return;
        }

        // make new path absolute
        $filePath = str_replace($fullOldPath, $fullPath, $path);

        // let the indexer do his job
        $indexer->index($filePath, $path);
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
        parent::onEvent($eventName, array($eventArgs));
    }
}
