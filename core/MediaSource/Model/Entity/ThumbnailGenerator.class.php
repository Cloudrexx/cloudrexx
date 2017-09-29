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
 * Class ThumbnailGenerator
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_mediabrowser
 */

namespace Cx\Core\MediaSource\Model\Entity;

use Cx\Core\Core\Controller\Cx;
use Cx\Model\Base\EntityBase;

/**
 * Class ThumbnailGenerator
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 */
class ThumbnailGenerator extends EntityBase
{
    /**
     * List with thumbnails
     * @var array
     */
    protected $thumbnails;

    /**
     * @var Cx
     */
    protected $cx;

    /**
     * @var MediaSourceManager
     */
    protected $mediaSourceManager;

    /**
     * ThumbnailGenerator constructor.
     *
     * @param $cx Cx
     * @param $mediaSourceManager MediaSourceManager
     */
    public function __construct($cx,$mediaSourceManager) {
        $this->cx = $cx;
        $this->mediaSourceManager = $mediaSourceManager;
    }

    /**
     * Create all the thumbnails for a picture.
     *
     * @param string        $path          Path to the file. This can be a virtual path or a absolute path.
     * @param string        $fileNamePlain Plain file name without extension
     * @param string        $fileExtension Extension of the file
     * @param \ImageManager $imageManager
     *
     * <code>
     * <?php
     * \Cx\Core_Modules\MediaBrowser\Model\FileSystem::createThumbnail(
     *      'files/',
     *      'Django,
     *      'jpg',
     *      new ImageManager() // Please recycle the instance and don't create a new anonymous instance for each call.
     *                         // This is just a simple example.
     * );
     * ?>
     * </code>
     *
     * @param bool          $generateThumbnailByRatio
     * @param bool          $force Force creation of new Thumbnails. This overwrites any existing thumbnail.
     *
     * @return array Array with the relative paths to the thumbnails.
     */
    public function createThumbnail(
        $path, $fileNamePlain, $fileExtension, \ImageManager $imageManager,
        $generateThumbnailByRatio = false, $force = false
    ) {
        $thumbnails = array();
        foreach (
            $this->getThumbnails() as $thumbnail
        ) {
            if ($force) {
                \Cx\Lib\FileSystem\FileSystem::delete_file(
                    MediaSourceManager::getAbsolutePath($path) . '/'
                    . $fileNamePlain . $thumbnail['value'] . '.'
                    . strtolower($fileExtension)
                );
            } elseif (\Cx\Lib\FileSystem\FileSystem::exists(
                MediaSourceManager::getAbsolutePath($path) . '/'
                . $fileNamePlain . $thumbnail['value'] . '.' . strtolower($fileExtension)
            )
            ) {
                $thumbnails[] = $fileNamePlain . $thumbnail['value'] . '.'
                    . strtolower($fileExtension);
                continue;
            }
            if ($imageManager->_createThumb(
                MediaSourceManager::getAbsolutePath($path) . '/',
                '',
                $fileNamePlain . '.' . $fileExtension,
                $thumbnail['size'],
                $thumbnail['quality'],
                $fileNamePlain . $thumbnail['value'] . '.' . strtolower($fileExtension),
                $generateThumbnailByRatio
            )
            ) {
                $thumbnails[] = $fileNamePlain . $thumbnail['value'] . '.'
                    . strtolower($fileExtension);
                continue;
            }
        }
        return $thumbnails;
    }

    /**
     * @param            $filePath
     * @param bool|false $force
     *
     * @return array
     */
    public function createThumbnailFromPath($filePath, $force = false) {
        if (!file_exists($filePath)) {
            $filePath = $this->cx->getWebsitePath() . $filePath;
        }
        $fileInfo = pathinfo($filePath);
        return $this->createThumbnail(
            $fileInfo['dirname'],
            preg_replace('/\.thumb_[a-z]+/i', '', $fileInfo['filename']),
            $fileInfo['extension'], new \ImageManager(), false, $force
        );
    }

    /**
     * Loads thumbnails from database
     */
    protected function loadThumbnails() {
        $pdo              = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()
            ->getPdoConnection();
        $sth              = $pdo->query(
            'SELECT id, name, size, 100 as quality,
             CONCAT(".thumb_",name) as value FROM  `'
            . DBPREFIX
            . 'settings_thumbnail`'
        );
        $this->thumbnails = $sth->fetchAll();
    }

    /**
     * Get Thumbnails from database
     * @return array
     */
    public function getThumbnails() {
        if (!$this->thumbnails) {
            $this->loadThumbnails();
        }
        return $this->thumbnails;
    }


    /**
     * Get the Thumbnails name, create new thumbnails if not exists
     *
     * @param string  $path     Directory path to the file
     * @param string  $filename Name of the file
     * @param boolean $create   TRUE|FALSE when True it creates thumbnail if thumbnail not exists
     *
     * @return array thumbnail name array
     */
    public function getThumbnailsFromFile($path, $filename, $create = false)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $filename  = pathinfo($filename, PATHINFO_FILENAME);

        $this->getThumbnails();
        $websitepath   = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsitePath();
        $thumbnails    = array();
        foreach ($this->thumbnails as $thumbnail) {
            $thumbnails[$thumbnail['size']] = preg_replace(
                '/\.' . $extension . '$/i',
                $thumbnail['value'] . '.' . strtolower($extension),
                \Cx\Core\Core\Controller\Cx::instanciate()
                    ->getWebsiteOffsetPath()
                    . str_replace(
                        $websitepath, '',
                        rtrim($path, '/') . '/' . $filename . '.' . $extension
                    )
            );
        }
        if ($create && file_exists($websitepath . str_replace($websitepath, '', rtrim($path, '/')) . '/' . $filename . '.' . $extension)) {
            $this->createThumbnailFromPath(rtrim($path, '/') . '/' . $filename . '.' . $extension);
        }
        return $thumbnails;
    }

    /**
     * Returns the smallest thumbnail for a file.
     *
     * @param $filename
     *
     * @return string Thumbnail Name
     */
    public function getThumbnailFilename($filename) {
        // legacy fallback for older calls.
        if (preg_match('/\.thumb$/', $filename)) return $filename;
        if (!file_exists($filename) && !file_exists($this->cx->getWebsitePath().'/'.ltrim($filename,'/')) ) {
            return $filename.'.thumb';
        }
        $webpath  = pathinfo($filename, PATHINFO_DIRNAME);
        if (!file_exists($filename)){
            $filename = $this->cx->getWebsitePath().$filename;
        }
        if (file_exists($filename)
            && MediaSourceManager::isSubdirectory(
                $this->cx->getWebsitePath(),
                $filename
            )
        ) {
            $this->createThumbnailFromPath($filename);
        }
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $filename  = pathinfo($filename, PATHINFO_FILENAME);
        $this->getThumbnails();
        $thumbnailType = $this->thumbnails[0];
        $thumbnail = preg_replace(
            '/\.' . $extension . '$/i',
            $thumbnailType['value'] . '.' . strtolower($extension),
           $webpath .'/'. $filename . '.' . $extension
        );
        return $thumbnail;
    }

}
