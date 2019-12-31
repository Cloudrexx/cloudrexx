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
 * File
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_filesystem
 */
namespace Cx\Lib\FileSystem;

/**
 * FileException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_filesystem
 */
class FileException extends \Exception {};

/**
 * File
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_filesystem
 */
class File implements FileInterface
{
    const UNKNOWN_ACCESS  = 0;
    const PHP_ACCESS      = 1;
    const FTP_ACCESS      = 2;

    private $file = null;
    private $accessMode = null;

    public function __construct($file)
    {
        $this->file = str_replace('\\', '/', $file);
        $this->setAccessMode();
    }

    private function setAccessMode()
    {
        // get the user-ID of the user who owns the loaded file
        try {
            $fsFile = new FileSystemFile($this->file);
            $fileOwnerUserId = $fsFile->getFileOwner();
        } catch (FileSystemFileException $e) {
            \DBG::msg('FileSystemFile: '.$e->getMessage());
            \DBG::msg('File: CAUTION: '.$this->file.' is owned by an unknown user!');
            return false;
        }

        // get the user-ID of the user running the PHP-instance
        if (function_exists('posix_getuid')) {
            $phpUserId = posix_getuid();
        } else {
            $phpUserId = getmyuid();
        }

        // check if the file we're going to work with is owned by the PHP user
        if ($fileOwnerUserId == $phpUserId) {
            $this->accessMode = self::PHP_ACCESS;
            \DBG::msg('File: Using FileSystem access');
            return true;
        }

        // fetch FTP user-ID
        $ftpConfig = \Env::get('ftpConfig');
        $ftpUsername = $ftpConfig['username'];
        if (function_exists('posix_getpwnam')) {
            $ftpUserInfo = posix_getpwnam($ftpUsername);
            $ftpUserId = $ftpUserInfo['uid'];
        } else {
            $ftpUserId = null;
        }


        // check if the file we're going to work with is owned by the FTP user
        if ($fileOwnerUserId == $ftpUserId) {
            $this->accessMode = self::FTP_ACCESS;
            \DBG::msg('File: Using FTP access');
            return true;
        }

        // the file to work on is neither owned by the PHP user nor the FTP user
        \DBG::msg('File: CAUTION: '.$this->file.' is owned by an unknown user!');
        $this->accessMode = self::UNKNOWN_ACCESS;
        return false;
    }

    public function getAccessMode()
    {
        return $this->accessMode;
    }
    
    public function forceAccessMode($mode)
    {
        $this->accessMode = $mode;
    }

    public function getData()
    {
        $data = file_get_contents($this->file);
        if ($data === false) {
            throw new FileSystemException('Unable to read data from file '.$this->file.'!');
        }

        return $data;
    }

    /**
     * Write data specified by $data to file
     * @param   string
     * @throws  FileSystemException if writing to file fails
     * @return  TRUE on sucess
     */
    public function write($data)
    {
        // use PHP
        if (   $this->accessMode == self::PHP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                // try regular file access first
                $fsFile = new FileSystemFile($this->file);
                $fsFile->write($data);
                return true;
            } catch (FileSystemFileException $e) {
                \DBG::msg('FileSystemFile: '.$e->getMessage());
            }
        }

        // use FTP
        if (   $this->accessMode == self::FTP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                $ftpFile = new FTPFile($this->file);
                $ftpFile->write($data);
                return true;
            } catch (FTPFileException $e) {
                \DBG::msg('FTPFile: '.$e->getMessage());
            }
        }

        throw new FileSystemException('File: Unable to write data to file '.$this->file.'!');
    }

    public function append($data) {
        // use PHP
        if (   $this->accessMode == self::PHP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                // try regular file access first
                $fsFile = new FileSystemFile($this->file);
                $fsFile->append($data);
                return true;
            } catch (FileSystemFileException $e) {
                \DBG::msg('FileSystemFile: '.$e->getMessage());
            }
        }

        // use FTP
        if (   $this->accessMode == self::FTP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                $ftpFile = new FTPFile($this->file);
                $ftpFile->append($data);
                return true;
            } catch (FTPFileException $e) {
                \DBG::msg('FTPFile: '.$e->getMessage());
            }
        }

        throw new FileSystemException('File: Unable to append data to file '.$this->file.'!');
    }

    /**
     * Creates files if it doesn't exists yet
     *
     * @throws FileSystemException if file does not exist and creating fails
     * @return TRUE on success
     */
    public function touch()
    {
        // use PHP
        if (   $this->accessMode == self::PHP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                // try regular file access first
                $fsFile = new FileSystemFile($this->file);
                $fsFile->touch();
                return true;
            } catch (FileSystemFileException $e) {
                \DBG::msg('FileSystemFile: '.$e->getMessage());
            }
        }

        // use FTP
        if (   $this->accessMode == self::FTP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                $ftpFile = new FTPFile($this->file);
                $ftpFile->touch();
                return true;
            } catch (FTPFileException $e) {
                \DBG::msg('FTPFile: '.$e->getMessage());
            }
        }

        throw new FileSystemException('File: Unable to touch file '.$this->file.'!');
    }

    public function copy($dst, $force = false)
    {
        if (!$force && file_exists($dst)) {
            return true;
        }

        $path       = ASCMS_PATH.ASCMS_PATH_OFFSET;
        $relPath    = str_replace($path, '', $dst);
        $pathInfo   = pathinfo($relPath);
        $arrFolders = explode('/', $pathInfo['dirname']);

        foreach ($arrFolders as $folder) {
            if (empty($folder)) continue;
            $path .= '/' . $folder;
            if (!is_dir($path)) {
                \Cx\Lib\FileSystem\FileSystem::make_folder($path);
            }
        }

        // use PHP
        if (   $this->accessMode == self::PHP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                // try regular file access first
                $fsFile = new FileSystemFile($this->file);
                $fsFile->copy($dst);
                return true;
            } catch (FileSystemFileException $e) {
                \DBG::msg('FileSystemFile: '.$e->getMessage());
            }
        }

        // use FTP
        if (   $this->accessMode == self::FTP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                $ftpFile = new FTPFile($this->file);
                $ftpFile->copy($dst);
                return true;
            } catch (FTPFileException $e) {
                \DBG::msg('FTPFile: '.$e->getMessage());
            }
        }

        throw new FileSystemException('File: Unable to copy file '.$this->file.'!');
    }

    public function rename($dst, $force = false)
    {
        return $this->move($dst, $force);
    }

    public function move($dst, $force = false)
    {
        if (!$force && file_exists($dst)) {
            return true;
        }

        $path       = ASCMS_PATH.ASCMS_PATH_OFFSET;
        $relPath    = str_replace($path, '', $dst);
        $pathInfo   = pathinfo($relPath);
        $arrFolders = explode('/', $pathInfo['dirname']);

        foreach ($arrFolders as $folder) {
            if (empty($folder)) continue;
            $path .= '/' . $folder;
            if (!is_dir($path)) {
                \Cx\Lib\FileSystem\FileSystem::make_folder($path);
            }
        }

        // use PHP
        if (   $this->accessMode == self::PHP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                // try regular file access first
                $fsFile = new FileSystemFile($this->file);
                $fsFile->move($dst);
                return true;
            } catch (FileSystemFileException $e) {
                \DBG::msg('FileSystemFile: '.$e->getMessage());
            }
        }

        // use FTP
        if (   $this->accessMode == self::FTP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                $ftpFile = new FTPFile($this->file);
                $ftpFile->move($dst);
                return true;
            } catch (FTPFileException $e) {
                \DBG::msg('FTPFile: '.$e->getMessage());
            }
        }

        throw new FileSystemException('File: Unable to copy file '.$this->file.'!');
    }

    /**
     * Sets write access to file's owner
     *
     * @throws FileSystemException if setting write access fails
     * @return  TRUE if file is already writable or setting write access was successful
     */
    public function makeWritable()
    {
        // use PHP
        if (   $this->accessMode == self::PHP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                $fsFile = new FileSystemFile($this->file);
                $fsFile->makeWritable();
                return true;
            } catch (FileSystemFileException $e) {
                \DBG::msg('FileSystemFile: '.$e->getMessage());
            }
        }

        // use FTP
        if (   $this->accessMode == self::FTP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                $ftpFile = new FTPFile($this->file);
                $ftpFile->makeWritable();
                return true;
            } catch (FTPFileException $e) {
                \DBG::msg('FTPFile: '.$e->getMessage());
            }
        }

        throw new FileSystemException('File: Unable to set write access to file '.$this->file.'!');
    }

    /**
     * Removes file
     *
     * @throws FileSystemException if removing of file fails
     * @return TRUE if file has successfully been removed
     */
    public function delete()
    {
        // use PHP
        if (   $this->accessMode == self::PHP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                $fsFile = new FileSystemFile($this->file);
                $fsFile->delete();
            } catch (FileSystemFileException $e) {
                \DBG::msg('FileSystemFile: '.$e->getMessage());
            }
        }

        // use FTP
        if (   $this->accessMode == self::FTP_ACCESS
            || $this->accessMode == self::UNKNOWN_ACCESS
        ) {
            try {
                $ftpFile = new FTPFile($this->file);
                $ftpFile->delete();
            } catch (FTPFileException $e) {
                \DBG::msg('FTPFile: '.$e->getMessage());
            }
        }

        clearstatcache();
        if (file_exists($this->file)) {
            throw new FileSystemException('File: Unable to delete file '.$this->file.'!');
        }

        return true;
    }
}
