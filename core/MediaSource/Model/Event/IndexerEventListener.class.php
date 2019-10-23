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

        $indexer->clearIndex($file->__toString());
    }

    /**
     * Get all file paths and get the appropriate index for each file to be able
     * to index the file
     * Todo: Use orgFile and oldPath as param when FileSystem work smart
     *
     * @todo: Move this method so it can be called from ComponentController
     * @param $fileInfo array contains file information
     */
    public function index($fileInfo)
    {
        // Can be deleted when orgFile and fullPath are params.
        $fullPath = $fileInfo['path'];
        $fullOldPath = $fileInfo['oldPath'];
        $mediaSourcePath = $fullPath;
        if (!empty($fullOldPath)) {
            $mediaSourcePath = $fullOldPath;
        }
        $mediaSource = new \Cx\Core\MediaSource\Model\Entity\MediaSource(
            '', '', $mediaSourcePath
        );
        $orgFile = new \Cx\Core\MediaSource\Model\Entity\LocalFile(
            $mediaSourcePath,
            $mediaSource->getFileSystem()
        );
        // End

        $files = array();

        if ($orgFile->getFileSystem()->isDirectory($orgFile)) {

            // Get all files and directories
            $fileList = $orgFile->getFileSystem()->getFileList(
                $fullOldPath
            );
            // Get an array with all file paths
            $files = $this->getAllFilePaths(
                $fileList,
                $orgFile,
                array()
            );
        } else {
            array_push($files, $orgFile);
        }

        foreach ($files as $file) {
            $extension = pathinfo($file->__toString(), PATHINFO_EXTENSION);
            $indexer = $this->cx->getComponent('MediaSource')->getIndexer(
                $extension
            );

            if (!$indexer) {
                continue;
            }

            $filePath = str_replace(
                $orgFile->__toString(),
                $fullPath,
                $file->__toString()
            );

            $indexer->index($filePath, $file->__toString());
        }
    }

    /**
     * Returns an array with all file paths of all files in this directory,
     * including files located in another directory.
     *
     * @param $fileList array  all files and directorys
     * @param $file     \Cx\Core\MediaSource\Model\Entity\LocalFile file to check
     * @param $result   array  existing result
     *
     * @return array
     */
    protected function getAllFilePaths(
        $fileList, $file, $result
    ) {
        foreach ($fileList as $fileEntryKey =>$fileListEntry) {
            $newFile = new \Cx\Core\MediaSource\Model\Entity\LocalFile(
                $file->__toString() . '/' . $fileEntryKey,
                $file->getFileSystem()
            );

            if ($file->getFileSystem()->isDirectory($newFile)) {
                $result = $this->getAllFilePaths(
                    $fileListEntry, $newFile, $result
                );
            } else if ($file->getFileSystem()->isFile($newFile)) {
                array_push($result, $newFile);
            }
        }
        return $result;
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
