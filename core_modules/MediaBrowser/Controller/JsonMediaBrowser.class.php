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
 * JSON Adapter for Uploader
 *
 * @copyright   Cloudrexx AG
 * @author      Tobias Schmoker <tobias.schmoker@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_json
 */

namespace Cx\Core_Modules\MediaBrowser\Controller;

use Cx\Core\Core\Controller\Cx;
use Cx\Core\Core\Model\Entity\SystemComponent;
use Cx\Core\Core\Model\Entity\SystemComponentController;
use Cx\Core\Json\JsonAdapter;
use Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowserPageTree;

/**
 * JSON Adapter for Uploader
 *
 * @copyright   Cloudrexx AG
 * @author      Tobias Schmoker <tobias.schmoker@comvation.com>
 */
class JsonMediaBrowser extends SystemComponentController implements JsonAdapter
{

    /**
     * Cx instance
     *
     * @var Cx
     */
    protected $cx;

    protected $message;

    /**
     * Instantiates the object
     *
     * @param SystemComponent $systemComponent
     * @param Cx              $cx
     */
    function __construct(SystemComponent $systemComponent, Cx $cx) {
        $this->cx = $cx;
    }

    /**
     * Returns the internal name used as identifier for this adapter
     *
     * @return String Name of this adapter
     */
    public function getName() {
        return 'MediaBrowser';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     *
     * @return array List of method names
     */
    public function getAccessableMethods() {
        return array(
            'getFiles', 'getSites', 'getSources', 'createThumbnails',
            'createDir', 'renameFile', 'removeFile',
            'removeFileFromFolderWidget'=> new \Cx\Core_Modules\Access\Model\Entity\Permission(array(), array(), false),
            'folderWidget' => new \Cx\Core_Modules\Access\Model\Entity\Permission(array(), array(), false)
        );
    }

    /**
     * Returns all messages as string
     *
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return $this->message;
    }

    /**
     * Get all media sources
     *
     * @return array
     */
    public function getSources() {
        $mediaSourceManager = $this->cx->getMediaSourceManager();
        $sources            = array();
        foreach (
            $mediaSourceManager->getMediaTypes() as $type =>
            $mediaSource
        ) {
            $sources[] = array(
                'name' => $mediaSource->getHumanName(),
                'value' => $type,
                'path' => array_values(
                    array_filter(
                        explode(
                            '/',
                            $mediaSourceManager->getMediaTypePathsbyNameAndOffset(
                                $type, 1
                            )
                        )
                    )
                )
            );
        }
        return $sources;
    }

    /**
     * Get filelist with all files
     *
     * @param $params
     *
     * @return array
     */
    public function getFiles($params) {
        $filePath = '/';
        if (
            isset($params['get']['path']) &&
            strlen($params['get']['path']) > 0
        ) {
            $filePath = $params['get']['path'];
        }
        $mediaType = 'files';
        if (
            isset($params['get']['mediatype']) &&
            strlen($params['get']['mediatype']) > 0
        ) {
            $mediaType = $params['get']['mediatype'];
        }
        $recursive = false;
        if (
            isset($params['get']['recursive']) &&
            strlen($params['get']['recursive']) > 0
        ) {
            $recursive = $params['get']['recursive'];
        }

        $mediaTypes = $this->cx->getMediaSourceManager()->getMediaTypes();
        return $mediaTypes[$mediaType]->getFileSystem()->getFileList($filePath, $recursive);
    }

    /**
     * Get all content pages.
     *
     * @return array
     */
    public function getSites() {
        $pageTree = new MediaBrowserPageTree(
            $this->cx->getDb()->getEntityManager(), $this->cx->getLicense(), 0, null, FRONTEND_LANG_ID,
            null, false, true, false
        );
        $pageTree->render();
        return $pageTree->getFlatTree();
    }


    /**
     * Create Thumbnails for file
     *
     * @param $params array
     *
     * @return bool
     */
    public function createThumbnails($params) {
        if (isset($params['get']['file'])) {
            $this->cx->getMediaSourceManager()
                ->getThumbnailGenerator()
                ->createThumbnailFromPath($params['get']['file']);
            return true;
        }
        return false;
    }

    /**
     * @param $params
     */
    public function createDir($params) {
        $pathArray                 = explode('/', $params['get']['path']);
        $mediaType = (strlen($params['get']['mediatype']) > 0)
            ? $params['get']['mediatype'] : 'files';
        $strPath = '/' . utf8_decode(join('/', $pathArray));
        $dir        = utf8_decode($params['post']['dir']) . '/';
        $this->setMessage(
            $this->cx->getMediaSourceManager()->getMediaType($mediaType)->getFileSystem()->createDirectory(
                $strPath, $dir
            )
        );
    }

    /**
     * @param $params
     */
    public function renameFile($params) {
        \Env::get('init')->loadLanguageData('MediaBrowser');

        $path       = !empty($params['get']['path']) ? contrexx_input2raw(utf8_decode($params['get']['path'])) : null;
        $mediaType = !empty($params['get']['mediatype']) ? $params['get']['mediatype'] : 'files';
        $oldName    = !empty($params['post']['oldName']) ? contrexx_input2raw(utf8_decode($params['post']['oldName'])) : null;
        $newName    = !empty($params['post']['newName']) ? contrexx_input2raw(utf8_decode($params['post']['newName'])) : null;

        if (!$path || !$oldName || !$newName) {
            return;
        }

        $pathArray = explode('/', $path);
        $strPath    = '/' . join('/', $pathArray);

        $fileSystem = $this->cx->getMediaSourceManager()->getMediaType($mediaType)->getFileSystem();
        $file = $fileSystem->getFileFromPath($strPath . $oldName);

        if (!$file) {
            throw new \Exception('Unknown file ' . $strPath . $oldName);
        }

        $this->setMessage(
            $fileSystem->moveFile($file, $newName)
        );
    }

    /**
     * @param $params
     */
    public function removeFile($params) {
        \Env::get('init')->loadLanguageData('MediaBrowser');
        $path     = !empty($params['get']['path']) ? contrexx_input2raw(utf8_decode($params['get']['path'])) : null;
        $mediaType = !empty($params['get']['mediatype']) ? $params['get']['mediatype'] : 'files';
        $filename = !empty($params['post']['file']['datainfo']['name']) ? contrexx_input2raw(utf8_decode($params['post']['file']['datainfo']['name'])) : null;

        if ($filename && $path) {
            $pathArray = explode('/', $path);
            $strPath    = '/' . join('/', $pathArray);

            $fileSystem = $this->cx->getMediaSourceManager()->getMediaType($mediaType)->getFileSystem();
            $file = $fileSystem->getFileFromPath($strPath . $filename);

            if (!$file) {
                throw new \Exception('Unknown file ' . $strPath . $filename);
            }

            $this->setMessage(
                $fileSystem->removeFile($file)
            );
        }
    }


    /**
     * Returns default permission as object
     *
     * @return Object
     */
    public function getDefaultPermissions() {
        // TODO: Implement getDefaultPermissions() method.
    }


    /**
     * Folder widget
     *
     * @param array $params
     *
     * @return boolean|array
     */
    public function folderWidget($params) {
        $this->getComponent('Session')->getSession();

        $folderWidgetId = isset($params['get']['id']) ? contrexx_input2int($params['get']['id']) : 0;
        if (   empty($folderWidgetId)
            || empty($_SESSION['MediaBrowser']['FolderWidget'][$folderWidgetId])
        ) {
            return false;
        }

        $folder = $_SESSION['MediaBrowser']['FolderWidget'][$folderWidgetId]['folder'];

        $arrFileNames = array();
        if (!file_exists($folder)) {
            return false;
        }
        $h = opendir($folder);
        while (false !== ($f = readdir($h))) {
            // skip folders and thumbnails
            if ($f == '.' || $f == '..'
                || preg_match(
                    "/(?:\.(?:thumb_thumbnail|thumb_medium|thumb_large)\.[^.]+$)|(?:\.thumb)$/i",
                    $f
                )
            ) {
                continue;
            }
            if (!is_dir($folder . '/' . $f)) {
                array_push($arrFileNames, $f);
            }
        }
        closedir($h);

        return $arrFileNames;
    }

    /**
     * Remove the file from folder widget
     *
     * @param array $params array from json request
     */
    public function removeFileFromFolderWidget($params)
    {
        $this->getComponent('Session')->getSession();

        $folderWidgetId = isset($params['get']['widget']) ? contrexx_input2int($params['get']['widget']) : 0;
        if (   empty($folderWidgetId)
            || empty($_SESSION['MediaBrowser']['FolderWidget'][$folderWidgetId])
            || $_SESSION['MediaBrowser']['FolderWidget'][$folderWidgetId]['mode'] == \Cx\Core_Modules\MediaBrowser\Model\Entity\FolderWidget::MODE_VIEW_ONLY
        ) {
            return false;
        }

        $path = !empty($params['get']['file']) ? contrexx_input2raw($params['get']['file']) : null;
        if (empty($path)) {
            return false;
        }
        $folder          = $_SESSION['MediaBrowser']['FolderWidget'][$folderWidgetId]['folder'];
        $localFileSystem = new \Cx\Core\MediaSource\Model\Entity\LocalFileSystem($folder);
        $file = $localFileSystem->getFileFromPath('/' . $path);

        $this->setMessage($localFileSystem->removeFile($file));

        return array();
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message) {
        $this->message = $message;
    }
}
