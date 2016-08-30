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

namespace Cx\Lib\FileSystem;
/**
 * File System File
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  lib_filesystem
 */

class FileSystemFileException extends \Exception {};

/**
 * File System File
 *
 * This class provides an object based interface to a file that resides
 * on the local file system.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  lib_filesystem
 */
class FileSystemFile implements FileInterface
{
    private $filePath = null;

    /**
     * Create a new FileSystemFile object that acts as an interface to
     * a file located on the local file system.
     *
     * @param   string Path to file on local file system.
     */
    public function __construct($file)
    {
        if (empty($file)) {
            throw new FileSystemFileException('No file path specified!');
        }

        if (strpos($file, ASCMS_DOCUMENT_ROOT) === 0) {
            $this->filePath = $file;
        } elseif (ASCMS_PATH_OFFSET && strpos($file, ASCMS_PATH_OFFSET) === 0) {
            $this->filePath = ASCMS_PATH.$file;
        } elseif (strpos($file, '/') === 0) {
            $this->filePath = ASCMS_DOCUMENT_ROOT.$file;
        } else {
            $this->filePath = ASCMS_DOCUMENT_ROOT.'/'.$file;
        }
    }

    public function getFileOwner()
    {
        // get the user-ID of the user who owns the loaded file
        $fileOwnerId = fileowner($this->filePath);
        if (!$fileOwnerId) {
            throw new FileSystemFileException('Unable to fetch file owner of '.$this->filePath);
        }

        return $fileOwnerId;
    }

    public function isWritable() {
        return is_writable($this->filePath);
    }

    public function write($data)
    {
        // first try
        $fp = @fopen($this->filePath, 'w');
        if (!$fp) {
            // try to set write access
            $this->makeWritable($this->filePath);
        }

        // second try
        $fp = @fopen($this->filePath, 'w');
        if (!$fp) {
            throw new FileSystemFileException('Unable to open file '.$this->filePath.' for writting!');
        }

        // acquire exclusive file lock
        flock($fp, LOCK_EX);

        // write data to file
        $writeStatus = fwrite($fp, $data);

        // release exclusive file lock
        flock($fp, LOCK_UN);
        if ($writeStatus === false) {
            throw new FileSystemFileException('Unable to write data to file '.$this->filePath.'!');
        }
    }

    public function append($data)
    {
        // first try
        $fp = @fopen($this->filePath, 'a');
        if (!$fp) {
            // try to set write access
            $this->makeWritable($this->filePath);
        }

        // second try
        $fp = @fopen($this->filePath, 'a');
        if (!$fp) {
            throw new FileSystemFileException('Unable to open file '.$this->filePath.' for writting!');
        }

        // acquire exclusive file lock
        flock($fp, LOCK_EX);

        // write data to file
        $writeStatus = fwrite($fp, $data);

        // release exclusive file lock
        flock($fp, LOCK_UN);
        if ($writeStatus === false) {
            throw new FileSystemFileException('Unable to append data to file '.$this->filePath.'!');
        }
    }

    public function touch()
    {
        \Cx\Lib\FileSystem\FileSystem::makeWritable($this->filePath);
        if (!touch($this->filePath)) {
            throw new FileSystemFileException('Unable to touch file in file system!');
        }
    }

    public function copy($dst)
    {
        if (!copy($this->filePath, $dst)) {
            throw new FileSystemFileException('Unable to copy ' . $this->filePath . ' to ' . $dst . '!');
        }
        \Cx\Lib\FileSystem\FileSystem::makeWritable($dst);
    }

    public function getFilePermissions()
    {
        // fetch current permissions on loaded file
        $filePerms = fileperms($this->filePath);
        if ($filePerms === false) {
            throw new FileSystemFileException('Unable to fetch file permissions of file '.$this->filePath.'!');
        }

        // Strip BITs that are not related to the file permissions.
        // Only the first 9 BITs are related (i.e: rwxrwxrwx) -> bindec(111111111) = 511
        $filePerms = $filePerms & 511;

        return $filePerms;
    }

    public function makeWritable()
    {
        // abort process in case the file is already writable
        if (is_writable($this->filePath)) {
            return true;
        }

        $parentDirectory = dirname($this->filePath);
        if (!is_writable($parentDirectory)) {
            if (strpos($parentDirectory, ASCMS_DOCUMENT_ROOT) === 0) {
                // parent directory lies within the Cloudrexx installation directory,
                // therefore, we shall try to make it writable
                \Cx\Lib\FileSystem\FileSystem::makeWritable($parentDirectory);
            } else {
                throw new FileSystemFileException('Parent directory '.$parentDirectory.' lies outside of Cloudrexx installation and can therefore not be made writable!');
            }
        }

        // fetch current permissions on loaded file
        $filePerms = $this->getFilePermissions();

        // set write access to file owner
        $filePerms |= \Cx\Lib\FileSystem\FileSystem::CHMOD_USER_WRITE;

        // log file permissions into the humand readable chmod() format
        \DBG::msg('CHMOD: '.substr(sprintf('%o', $filePerms), -4));

        if (!@chmod($this->filePath, $filePerms)) {
            throw new FileSystemFileException('Unable to set write access to file '.$this->filePath.'!');
        }
    }

    public function delete()
    {
        \Cx\Lib\FileSystem\FileSystem::makeWritable(dirname($this->filePath));
        if (!unlink($this->filePath)) {
            throw new FileSystemFileException('Unable to delete file '.$this->filePath.'!');
        }
    }
}
