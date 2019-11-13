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
 * class MediaSourceManager
 *
 * @copyright   Cloudrexx AG
 * @author      Tobias Schmoker <tobias.schmoker@comvation.com>
 *              Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */

namespace Cx\Core\MediaSource\Model\Entity;

use Cx\Core\Core\Controller\Cx;
use Cx\Model\Base\EntityBase;

/**
 * Class MediaSourceManagerException
 *
 * @copyright   Cloudrexx AG
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */
class MediaSourceManagerException extends \Exception {}

/**
 * Class MediaSourceManager
 *
 * @copyright   Cloudrexx AG
 * @author      Tobias Schmoker <tobias.schmoker@comvation.com>
 *              Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */
class MediaSourceManager extends EntityBase
{

    /**
     * @var \Cx\Core\Core\Controller\Cx
     */
    protected $cx;

    protected $mediaTypes = array();

    /**
     * @var array
     */
    protected $mediaTypePaths;

    /**
     * @var MediaSource[]
     */
    protected $allMediaTypePaths = array();

    /**
     * @var ThumbnailGenerator
     */
    protected $thumbnailGenerator;

    /**
     * @param $cx Cx
     *
     * @throws \Cx\Core\Event\Controller\EventManagerException
     */
    public function __construct($cx) {
        $this->cx             = $cx;
        $eventHandlerInstance = $this->cx->getEvents();

        /**
         * Loads all mediatypes into $this->allMediaTypePaths
         */
        $eventHandlerInstance->triggerEvent('mediasource.load', array($this));

        ksort($this->allMediaTypePaths);
        $this->lockedMediaTypes = array();
        foreach ($this->allMediaTypePaths as $mediaSource) {
            /**
             * @var $mediaSource MediaSource
             */
            if ($mediaSource->checkAccess()) {
                $this->mediaTypePaths[$mediaSource->getName()] = $mediaSource->getDirectory();
                $this->mediaTypes[$mediaSource->getName()] = $mediaSource;
            } else {
                $this->lockedMediaTypes[$mediaSource->getName()] = $mediaSource;
            }
        }
    }

    /**
     * Returns all MediaSources the current user does not have access to
     * @todo This is a dirty hack to allow the API control over permissions.
     *      As soon as DataSource can load the correct MediaSource instance
     *      directly, this should be removed.
     * @deprecated This method must not be used outside MediaSource itself!
     * @param string $name Name of the MediaSource to load
     * @return MediaSource Requested MediaSource or null
     */
    public function getLockedMediaType($name) {
        if (!isset($this->lockedMediaTypes[$name])) {
            return null;
        }
        return $this->lockedMediaTypes[$name];
    }

    /**
     * Get the absolute path from the virtual path.
     * If the path is already absolute nothing will happen to it.
     *
     * @param $virtualPath string The virtual Path
     *
     * @return string The absolute Path
     */
    public static function getAbsolutePath($virtualPath) {
        if (self::isVirtualPath(
            $virtualPath
        )
        ) {
            $pathArray = explode('/', $virtualPath);
            return realpath(Cx::instanciate()->getMediaSourceManager()
                ->getMediaTypePathsbyNameAndOffset(array_shift($pathArray), 0)
            . '/' . join(
                '/', $pathArray
            ));
        }
        return $virtualPath;
    }

    /**
     * Checks if $subdirectory is a subdirectory of $path.
     * You can use a virtual path as a parameter.
     *
     * @param $path
     * @param $subdirectory
     *
     * @return boolean
     */
    public static function isSubdirectory($path, $subdirectory) {
        $absolutePath = self::getAbsolutePath($path);
        $absoluteSubdirectory = self::getAbsolutePath($subdirectory);
        return (boolean)preg_match(
            '#^' . preg_quote($absolutePath, '#') . '#', $absoluteSubdirectory
        );
    }

    /**
     * Checks permission
     *
     * @param $path
     *
     * @return bool
     */
    public static function checkPermissions($path) {
        foreach (
            Cx::instanciate()->getMediaSourceManager()->getMediaTypePaths() as
            $virtualPathName => $mediatype
        ) {
            if (self::isSubdirectory($virtualPathName, $path)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a path is virtual or real.
     *
     * ``` php
     * \Cx\Core_Modules\MediaBrowser\Model\FileSystem::isVirtualPath('files/Movies'); // Returns true
     * ```
     *
     * @param $path
     *
     * @return bool
     */
    public static function isVirtualPath($path) {
        return !(strpos($path, '/') === 0);
    }

    public function addMediaType(MediaSource $mediaType) {
        $this->allMediaTypePaths[$mediaType->getPosition()
        . $mediaType->getName()] = $mediaType;
    }



    /**
     * @return MediaSource[]
     */
    public function getMediaTypes() {
        return $this->mediaTypes;
    }


    /**
     * @param $name string
     *
     * @return MediaSource
     * @throws MediaSourceManagerException
     */
    public function getMediaType($name) {
        if(!isset($this->mediaTypes[$name])){
            throw new MediaSourceManagerException("No such mediatype available");
        }
        return $this->mediaTypes[$name];
    }

    /**
     * @return array
     */
    public function getMediaTypePaths() {
        return $this->mediaTypePaths;
    }

    /**
     * @param $name
     *
     * @return array
     */
    public function getMediaTypePathsbyName($name) {
        return $this->mediaTypePaths[$name];
    }

    /**
     * @param $name
     * @param $offset
     *
     * @return array
     */
    public function getMediaTypePathsbyNameAndOffset($name, $offset) {
        return $this->mediaTypePaths[$name][$offset];
    }

    public function getAllMediaTypePaths() {
        return $this->allMediaTypePaths;
    }

    /**
     * @return ThumbnailGenerator
     */
    public function getThumbnailGenerator(){
        if (!$this->thumbnailGenerator){
            $this->thumbnailGenerator = new ThumbnailGenerator($this->cx,$this);
        }
        return $this->thumbnailGenerator;
    }

    /**
     * Get MediaSourceFile from the given path
     *
     * @param string $path File path
     * @return LocalFile
     */
    public function getMediaSourceFileFromPath($path)
    {
        // If the path does not have leading backslash then add it
        if (strpos($path, '/') !== 0) {
            $path = '/' . $path;
        }

        try {
            // Get MediaSource and MediaSourceFile object
            $mediaSource     = $this->getMediaSourceByPath($path);
            $mediaSourcePath = $mediaSource->getDirectory();
            $mediaSourceFile = $mediaSource->getFileSystem()
                ->getFileFromPath(substr($path, strlen($mediaSourcePath[1])));
        } catch (MediaSourceManagerException $e) {
            \DBG::log($e->getMessage());
            return;
        }

        return $mediaSourceFile;
    }

    /**
     * Get MediaSource by given component
     *
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $component Component to look up for a MediaSource
     *
     * @return MediaSource  if a MediaSource of the given Component does exist
     *                              returns MediaSource, otherwise NULL 
     */
    public function getMediaSourceByComponent($component)
    {
        foreach ($this->mediaTypes as $mediaSource) {
            $mediaSourceComponent = $mediaSource->getSystemComponentController();
            if ($component == $mediaSourceComponent) {
                return $mediaSource;
            }
        }
        return null;
    }

    /**
     * Get MediaSource by the given path
     *
     * @param  string $path File path
     * @param boolean $ignorePermissions (optional) Defaults to false
     * @return \Cx\Core\MediaSource\Model\Entity\MediaSource MediaSource object
     * @throws MediaSourceManagerException
     */
    public function getMediaSourceByPath($path, $ignorePermissions = false)
    {
        $mediaSources = $this->mediaTypes;
        if ($ignorePermissions) {
            $this->allMediaTypePaths;
        }
        foreach ($mediaSources as $mediaSource) {
            $mediaSourcePath = $mediaSource->getDirectory();
            if (strpos($path, $mediaSourcePath[1]) === 0) {
                return $mediaSource;
            }
        }
        throw new MediaSourceManagerException(
            'No MediaSource found for: '. $path
        );
    }
}
