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
 */

namespace Cx\Core\MediaSource\Model\Entity;


use Cx\Model\Base\EntityBase;

class LocalFileSystem extends EntityBase implements FileSystem
{

    /**
     * The path of the file system.
     * Without ending directory separator.
     */
    private $rootPath;
    protected $fileListCache;

    function __construct($path) {
        if (!$path) {
            throw new \InvalidArgumentException(
                "Path shouldn't be empty: Given: " . $path
            );
        }
        $this->rootPath = rtrim($path, '/');
    }

    /**
     * @param $path
     *
     * @return LocalFileSystem
     */
    public static function createFromPath($path) {
        return new self($path);
    }

    /**
     * @todo    Option $recursive does not work. It always acts as recursive is set to TRUE
     */
    public function getFileList($directory, $recursive = true, $readonly = false) {
        if (isset($this->fileListCache[$directory][$recursive][$readonly])) {
            return $this->fileListCache[$directory][$recursive][$readonly];
        }

        $dirPath = rtrim($this->rootPath . '/' . $directory,'/');
        if (!file_exists($dirPath)) {
            return array();
        }

        $regex = '/^((?!thumb(_[a-z]+)?).)*$/';
        if ($recursive) {
            $iteratorIterator = new \RegexIterator(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator(
                        $dirPath
                    ), \RecursiveIteratorIterator::SELF_FIRST
                ), $regex
            );
        } else {
            $iteratorIterator = new \RegexIterator(
                new \IteratorIterator(
                    new \DirectoryIterator(
                        $dirPath
                    )
                ), $regex
            );
        }

        $jsonFileArray = array();

        $thumbnailList = $this->cx->getMediaSourceManager()
            ->getThumbnailGenerator()
            ->getThumbnails();

        foreach ($iteratorIterator as $file) {
            /**
             * @var $file \SplFileInfo
             */
            $extension = 'Dir';
            if (!$file->isDir()) {
                $extension = strtolower(
                    pathinfo($file->getFilename(), PATHINFO_EXTENSION)
                );
            }

            // filters
            if (
                $file->getFilename() == '.'
                || $file->getFilename() == 'index.php'
                || (0 === strpos($file->getFilename(), '.'))
            ) {
                continue;
            }

            // set preview if image
            $preview = 'none';


            $hasPreview = false;
            $thumbnails = array();
            if ($this->isImage($extension)) {
                $hasPreview = true;
                $thumbnails = $this->getThumbnails(
                    $thumbnailList, $extension, $file, $thumbnails
                );
                $preview = current($thumbnails);
                if (!file_exists($this->cx->getWebsitePath() . $preview)) {
                    $hasPreview = false;
                }
            }

            $size = \FWSystem::getLiteralSizeFormat($file->getSize());
            $fileInfos = array(
                'filepath' => mb_strcut(
                    $file->getPath() . '/' . $file->getFilename(),
                    mb_strlen($this->cx->getWebsitePath())
                ),
                // preselect in mediabrowser or mark a folder
                'name' => $file->getFilename(),
                'size' => $size ? $size : '0 B',
                'cleansize' => $file->getSize(),
                'extension' => ucfirst(mb_strtolower($extension)),
                'preview' => $preview,
                'hasPreview' => $hasPreview,
                'active' => false, // preselect in mediabrowser or mark a folder
                'type' => $file->getType(),
                'thumbnail' => $thumbnails
            );

            if ($readonly){
                $fileInfos['readonly'] = true;
            }

            // filters
            if (
                $fileInfos['name'] == '.'
                || preg_match(
                    '/\.thumb/', $fileInfos['name']
                )
                || $fileInfos['name'] == 'index.php'
                || (0 === strpos($fileInfos['name'], '.'))
            ) {
                continue;
            }

            $path = array(
                $file->getFilename() => array('datainfo' => $fileInfos)
            );

            if ($recursive) {
                for (
                    $depth = $iteratorIterator->getDepth() - 1;
                    $depth >= 0; $depth--
                ) {
                    $path = array(
                        $iteratorIterator->getSubIterator($depth)->current()->getFilename() => $path
                    );
                }
            }
            $jsonFileArray = $this->array_merge_recursive($jsonFileArray, $path);
        }
        $jsonFileArray = $this->utf8EncodeArray($jsonFileArray);
        $this->fileListCache[$directory][$recursive][$readonly] = $jsonFileArray;
        return $jsonFileArray;
    }

    /**
     * Applies utf8_encode() to keys and values of an array
     * From: http://stackoverflow.com/questions/7490105/array-walk-recursive-modify-both-keys-and-values
     * @param array $array Array to encode
     * @return array UTF8 encoded array
     */
    public function utf8EncodeArray($array) {
        $helper = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->utf8EncodeArray($value);
            } else {
                $value = utf8_encode($value);
            }
            $helper[utf8_encode($key)] = $value;
        }
        return $helper;
    }

    /**
     * \array_merge_recursive() behaves unexpected with numerical indexes
     * Fix from http://php.net/array_merge_recursive (array_merge_recursive_new)
     *
     * This method behaves differently than the original since it overwrites
     * already present keys
     * @return array Recursively merged array
     */
    protected function array_merge_recursive() {
        $arrays = func_get_args();
        $base = array_shift($arrays);

        foreach ($arrays as $array) {
            reset($base); //important
            foreach ($array as $key => $value) {
                if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                    $base[$key] = $this->array_merge_recursive($base[$key], $value);
                } else {
                    $base[$key] = $value;
                }
            }
        }

        return $base;
    }

    /**
     * @param $extension
     *
     * @return int
     */
    public function isImage(
        $extension
    ) {
        return preg_match("/(jpg|jpeg|gif|png)/i", $extension);
    }

    /**
     * @param $thumbnailList
     * @param $extension
     * @param $file
     * @param $thumbnails
     *
     * @return mixed
     */
    public function getThumbnails(
        $thumbnailList, $extension, $file, $thumbnails
    ) {
        foreach (
            $thumbnailList as
            $thumbnail
        ) {
            $thumbnails[$thumbnail['size']] = preg_replace(
                '/\.' . $extension . '$/i',
                $thumbnail['value'] . '.' . strtolower($extension),
                 str_replace(
                    $this->cx->getWebsitePath(), '',
                    $file->getRealPath()
                )
            );
        }
        return $thumbnails;
    }

    public function removeFile(File $file) {
        global $_ARRAYLANG;
        $filename = $file->getFullName();
        $strPath = $file->getPath();
        if (
            !empty($filename)
            && !empty($strPath)
        ) {
            if (
                is_dir(
                    $this->getFullPath($file)
                    . $filename
                )
            ) {
                if (
                    \Cx\Lib\FileSystem\FileSystem::delete_folder(
                        $this->getFullPath($file) . $filename, true
                    )
                ) {
                    return (
                        sprintf(
                            $_ARRAYLANG['TXT_FILEBROWSER_DIRECTORY_SUCCESSFULLY_REMOVED'],
                            $filename
                        )
                    );
                } else {
                    return (
                        sprintf(
                            $_ARRAYLANG['TXT_FILEBROWSER_DIRECTORY_UNSUCCESSFULLY_REMOVED'],
                            $filename
                        )
                    );
                }
            } else {
                if (
                    \Cx\Lib\FileSystem\FileSystem::delete_file(
                        $this->getFullPath($file)  . $filename
                    )
                ) {
                    $this->removeThumbnails($file);
                    return (
                        sprintf(
                            $_ARRAYLANG['TXT_FILEBROWSER_FILE_SUCCESSFULLY_REMOVED'],
                            $filename
                        )
                    );
                } else {
                    return (
                        sprintf(
                            $_ARRAYLANG['TXT_FILEBROWSER_FILE_UNSUCCESSFULLY_REMOVED'],
                            $filename
                        )
                    );
                }
            }
        }
        return (
            sprintf(
                $_ARRAYLANG['TXT_FILEBROWSER_FILE_UNSUCCESSFULLY_REMOVED'],
                $filename
            )
        );
    }

    public function moveFile(
        File $file, $destination
    ) {
        global $_ARRAYLANG;
        if (!empty($destination) || !\FWValidator::is_file_ending_harmless($destination)) {
            if (is_dir(
                    $this->getFullPath($file)
                    . $file->getFullName()
                )
            ) {
                $fileName            =
                    $this->getFullPath($file)
                    . $file->getFullName();
                $destinationFileName =
                    $this->getFullPath($file)
                    . $destination;
            } else {
                $fileName            =
                    $this->getFullPath($file)
                    . $file->getFullName();
                $destinationFileName =
                    $this->getFullPath($file)
                    . $destination
                    . '.'
                    . $file->getExtension();
            }
            if ($fileName == $destinationFileName){
                return sprintf(
                    $_ARRAYLANG['TXT_FILEBROWSER_FILE_SUCCESSFULLY_RENAMED'],
                    $file->getName()
                );
            }
            $destinationFolder = realpath(pathinfo($this->getFullPath($file) . $destination, PATHINFO_DIRNAME));
            if (!MediaSourceManager::isSubdirectory($this->rootPath,
                $destinationFolder))
            {
                return sprintf(
                    $_ARRAYLANG['TXT_FILEBROWSER_FILE_UNSUCCESSFULLY_RENAMED'],
                    $file->getName()
                );
            }
            $this->removeThumbnails($file);


            if (!\Cx\Lib\FileSystem\FileSystem::move(
                $fileName, $destinationFileName
                , false
            )
            ) {

                return sprintf(
                    $_ARRAYLANG['TXT_FILEBROWSER_FILE_UNSUCCESSFULLY_RENAMED'],
                    $file->getName()
                );
            }
            return sprintf(
                $_ARRAYLANG['TXT_FILEBROWSER_FILE_SUCCESSFULLY_RENAMED'],
                $file->getName()
            );
        }
        else {
            return sprintf(
                $_ARRAYLANG['TXT_FILEBROWSER_FILE_UNSUCCESSFULLY_RENAMED'],
                $file->getName()
            );
        }
    }

    public function writeFile(
        File $file, $content
    ) {
        file_put_contents(
            $this->rootPath . '/' . $file->__toString(), $content
        );
    }

    public function readFile(
        File $file
    ) {
        return file_get_contents($this->rootPath . '/' . $file->__toString());
    }

    public function isDirectory(
        File $file
    ) {
        return is_dir($this->rootPath . '/' . $file->__toString());
    }

    public function isFile(
        File $file
    ) {
        return is_file($this->rootPath . '/' . $file->__toString());
    }

    public function getLink(
        File $file
    ) {
        // TODO: Implement getLink() method.
    }

    public function createDirectory(
        $path, $directory
    ) {
        global $_ARRAYLANG;
        \Env::get('init')->loadLanguageData('MediaBrowser');
        if (
            !\Cx\Lib\FileSystem\FileSystem::make_folder(
                $this->rootPath . $path . '/' . $directory
            )
        ) {
            return sprintf(
                $_ARRAYLANG['TXT_FILEBROWSER_UNABLE_TO_CREATE_FOLDER'],
                $directory
            );
        } else {
            return
                sprintf(
                    $_ARRAYLANG['TXT_FILEBROWSER_DIRECTORY_SUCCESSFULLY_CREATED'],
                    $directory
                );
        }
    }

    /**
     * @param File $file
     *
     * @return string
     */
    public function getFullPath(File $file) {
        return $this->rootPath . ltrim($file->getPath(), '.') . '/';
    }

    /**
     * @param File $file
     *
     * @return array
     */
    public function removeThumbnails(File $file) {
        if (!$this->isImage($file->getExtension())) {
            return;
        }
        $iterator = new \RegexIterator(
            new \DirectoryIterator(
                $this->getFullPath($file)
            ),
            '/' . preg_quote($file->getName(), '/') . '.thumb_[a-z]+\.' . $file->getExtension() . '/'
        );
        foreach ($iterator as $thumbnail){
            \Cx\Lib\FileSystem\FileSystem::delete_file(
                $thumbnail->getPathName()
            );
        }
    }

    /**
     * Get Root path of the filesystem
     *
     * @return string
     */
    public function getRootPath()
    {
        return $this->rootPath;
    }

    /**
     * Set root path of the filesystem
     *
     * @param string $rootPath
     */
    public function setRootPath($rootPath)
    {
        $this->rootPath = $rootPath;
    }

    public function getFileFromPath($filepath) {
        $fileinfo = pathinfo($filepath);
        $path = dirname($filepath);
        $files = $this->getFileList($fileinfo['dirname'], false);
        if (!isset($files[$fileinfo['basename']])) {
            return false;
        }
        return new LocalFile($filepath, $this);
    }
}
