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
 * @subpackage  coremodule_mediabrowser
 */

namespace Cx\Core\MediaSource\Model\Entity;

use Cx\Core\DataSource\Model\Entity\DataSource;

/**
 * Class MediaSource
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_mediabrowser
 */
class MediaSource extends DataSource {

    /**
     * List of operations supported by this DataSource
     * @var array List of operations
     */
    protected $supportedOperations = array();

    /**
     * Name of the mediatype e.g. files, shop, media1
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $position;

    /**
     * Human readable name
     * @var string
     */
    protected $humanName;

    /**
     * Array with the web and normal path to the directory.
     *
     * e.g:
     * array(
     *      $this->cx->getWebsiteImagesContentPath(),
     *      $this->cx->getWebsiteImagesContentWebPath(),
     * )
     *
     * @var array
     */
    protected $directory = array();

    /**
     * Array with access ids to use with \Permission::checkAccess($id, 'static', true)
     * @var array
     */
    protected $accessIds = array();

    /**
     * @var FileSystem
     */
    protected $fileSystem;

    /**
     * @var \Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController
     */
    protected $systemComponentController;

    public function __construct($name,$humanName, $directory, $accessIds = array(), $position = '',FileSystem $fileSystem = null, \Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController = null) {
        $this->fileSystem = $fileSystem ? $fileSystem : LocalFileSystem::createFromPath($directory[0]);
        $this->name      = $name;
        $this->position  = $position;
        $this->humanName = $humanName;
        $this->directory = $directory;
        $this->accessIds = $accessIds;

        // Sets provided SystemComponentController
        $this->systemComponentController = $systemComponentController;
        if (!$this->systemComponentController) {
            // Searches a SystemComponentController intelligently by RegEx on backtrace stack frame
            $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $trace = end($traces);
            if (empty($trace['class'])) {
                throw new MediaBrowserException('No SystemComponentController for ' . __CLASS__ . ' can be found');
            }
            $matches = array();
            preg_match(
                '/Cx\\\\(?:Core|Core_Modules|Modules)\\\\([^\\\\]*)\\\\/',
                $trace['class'],
                $matches
            );
            $this->systemComponentController = $this->getComponent($matches[1]);
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return array
     */
    public function getDirectory()
    {
        return $this->directory;
    }


    /**
     * @return array
     */
    public function getAccessIds()
    {
        return $this->accessIds;
    }

    /**
     * @param array $accessIds
     */
    public function setAccessIds($accessIds)
    {
        $this->accessIds = $accessIds;
    }

    /**
     * @return bool
     */
    public function checkAccess(){
        foreach ($this->accessIds as $id){
            if (!\Permission::checkAccess($id, 'static', true)){
                return false;
            }
        }
        return true;
    }

    /**
     * @return string
     */
    public function getHumanName()
    {
        return $this->humanName;
    }

    /**
     * @param string $humanName
     */
    public function setHumanName($humanName)
    {
        $this->humanName = $humanName;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * @return FileSystem
     */
    public function getFileSystem() {
        return $this->fileSystem;
    }

    /**
     * @return \Cx\Core\Core\Model\Entity\SystemComponentController
     */
    public function getSystemComponentController() {
        return $this->systemComponentController;
    }

    /**
     * Returns a list of field names this DataSource consists of
     * @return array List of field names
     */
    public function listFields() {
        throw new \Exception('Not yet implemented');
    }

    /**
     * @inheritdoc
     */
    public function getIdentifierFieldNames() {
        return array('filename');
    }

    /**
     * Gets one or more entries from this DataSource
     *
     * If an argument is not provided, no restriction is made for this argument.
     * So if this is called without any arguments, all entries of this
     * DataSource are returned.
     * If no entry is found, an empty array is returned.
     * @param array $elementId (optional) field=>value-type condition array identifying an entry
     * @param array $filter (optional) field=>value-type condition array, only supports = for now
     * @param array $order (optional) field=>order-type array, order is either "ASC" or "DESC"
     * @param int $limit (optional) If set, no more than $limit results are returned
     * @param int $offset (optional) Entry to start with
     * @param array $fieldList (optional) Limits the result to the values for the fields in this list
     * @throws \Exception If something did not go as planned
     * @return array Two dimensional array (/table) of results (array($row=>array($fieldName=>$value)))
     */
    public function get(
        $elementId = array(),
        $filter = array(),
        $order = array(),
        $limit = 0,
        $offset = 0,
        $fieldList = array()
    ) {
        throw new \Exception('Not yet implemented');

        // The following code is beta. We need to define what MediaSource
        // returns: Binary file data or Metadata/file lists or both
        /*$fileList = $this->getMediaSource()->getFileSystem()->getFileList('');
        if (count($elementId) && $fileList[current($elementId)]) {
            return array(current($elementId) => $fileList[current($elementId)]);
        }
        return $fileList;*/
    }

    /**
     * Returns the real instance of this MediaSource
     *
     * DataSources are loaded from DB, MediaSources are loaded via event hooks,
     * MediaSource is a DataSource  -->  MediaSources cannot be loaded from
     * DB yet. As soon as this is possible this can be removed.
     * @return MediaSource Real instance of this MediaSource
     */
    protected function getMediaSource() {
        // force access
        try {
            $mediaSource = $this->cx->getMediaSourceManager()->getMediaType(
                $this->getIdentifier()
            );
        } catch (\Cx\Core\MediaSource\Model\Entity\MediaSourceManagerException $e) {
            $mediaSource = $this->cx->getMediaSourceManager()->getLockedMediaType(
                $this->getIdentifier()
            );
        }
        if (!$mediaSource) {
            throw new \Exception('MediaSource not found');
        }
        return $mediaSource;
    }

    /**
     * Adds a new entry to this DataSource
     * @todo Chunked upload is untested and will most likely not work
     * @param array $data Field=>value-type array. Not all fields may be required.
     * @throws \Exception If something did not go as planned
     */
    public function add($data) {
        $mediaSource = $this->getMediaSource();
        // $data['path'] is not the file system path to the file, but a
        // combination of MediaSource identifier and a file system path.
        // Therefore using $this->getIdentifier() is intended and correct.
        // See JsonUploader::upload()
        $data['path'] = $this->getIdentifier() . '/';
        $jd = new \Cx\Core\Json\JsonData();
        $res = $jd->data(
            'Uploader',
            'upload',
            array('get' => '', 'post' => $data, 'mediaSource' => $mediaSource)
        );
        if ($res['status'] != 'success' || $res['data']['OK'] !== 1) {
            throw new \Exception('Upload failed: ' . $res['message']);
        }
        return true;
    }

    /**
     * Updates an existing entry of this DataSource
     * @param array $elementId field=>value-type condition array identifying an entry
     * @param array $data Field=>value-type array. Not all fields are required.
     * @throws \Exception If something did not go as planned
     */
    public function update($elementId, $data) {
        $this->remove($elementId);
        return $this->add($data);
    }

    /**
     * Drops an entry from this DataSource
     * @param array $elementId field=>value-type condition array identifying an entry
     * @throws \Exception If something did not go as planned
     */
    public function remove($elementId) {
        $mediaSource = $this->getMediaSource();
        $fs = $mediaSource->getFileSystem();
        $filename = '/' . implode('/', $elementId);
        $file = $fs->getFileFromPath($filename);
        if (!$file) {
            throw new \Exception('File "' . $filename . '" not found!');
        }
        return $fs->removeFile($file);
    }
}
