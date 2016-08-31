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
 * File System
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework_file
 */

class FileSystemException extends \Exception {};

/**
 * File System
 * Collection of file system (direct or through FTP) manipulation tools
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Janik Tschanz <janik.tschanz@comvation.com>
 * @author      Reto Kohli <reto.kohli@comvation.com>
 *              (new static methods, error system)
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework_file
 */
class FileSystem
{
    /**
     * chmod mode for folders (rwxrwxrwx)
     *
     * Note that 0775 is *not* sufficient for moving uploaded files
     * (in many cases anyway).
     */
    const CHMOD_FOLDER = 0777;
    /**
     * chmod mode for files (rw-rw-r--)
     *
     * Note that 0664 is sufficient in *most* cases.
     * (if it's not, it's not safe).
     */
    const CHMOD_FILE   = 0664;

    const CHMOD_OTHER_EXECUTE   = 1;
    const CHMOD_OTHER_WRITE     = 2;
    const CHMOD_OTHER_READ      = 4;
    const CHMOD_GROUP_EXECUTE   = 8;
    const CHMOD_GROUP_WRITE     = 16;
    const CHMOD_GROUP_READ      = 32;
    const CHMOD_USER_EXECUTE    = 64;
    const CHMOD_USER_WRITE      = 128;
    const CHMOD_USER_READ       = 256;

    private static $connection = false; // current connection

    private static $ftpPath = null;

    private static $ftpAuth = false; // ftp authentification status

    /**
     * Internal error numbers, stored in $error
     */
    const ERROR_NONE                  = 0;
    const ERROR_FILE_NOT_FOUND        = 1;
    const ERROR_FOLDER_NOT_FOUND      = 2;
    const ERROR_CANNOT_CREATE_FILE    = 101;
    const ERROR_CANNOT_CREATE_FOLDER  = 102;
    const ERROR_CANNOT_MOVE_FILE      = 111;
    const ERROR_INVALID_FILETYPE      = 201;
    const ERROR_FILESIZE_TOO_BIG      = 202;
    const ERROR_MISSING_ARGUMENT      = 301;
    // Add more as needed.  Don't forget to add core language entries, like
    // $_ARRAYLANG['TXT_CORE_FILE_ERROR_#'] = "Oh my, an error!";
    // where # is the error number.

    /**
     * Internal error number
     *
     * See {@see getError()}.
     * @var   integer
     */
    private static $error = self::ERROR_NONE;


    /**
     * Returns the current error number, if any, or zero.
     *
     * Note that the internal $error variable is cleared,
     * so you *SHOULD* call this once and get a sensible result.
     * @return  integer           The error number, or zero
     */
    static function getError()
    {
        $error = self::$error;
        self::$error = self::ERROR_NONE;
        return $error;
    }


    /**
     * Returns the current error string, if any, or the empty string.
     *
     * Calls {@see getError()}, thus clearing the error number in
     * the $error class variable.
     * @return  integer           The error number, or zero
     */
    static function getErrorString()
    {
        global $_CORELANG;

        return $_CORELANG['TXT_CORE_FILE_ERROR_'.self::getError()];
    }


    /**
     * Creates a new File helper object. Uses FTP if configured,
     * direct file access otherwise.
     */
    function __construct()
    {
        self::init();
// Pointless
//        $this->checkConnection();
    }


    /**
     * Sets up internal stuff and tries to connect to FTP
     * @return    boolean       True if FTP is connected, false otherwise
     */
    static function init()
    {
        global $_FTPCONFIG;

        if (self::$ftpAuth) return true;

        if (!$_FTPCONFIG['is_activated']) return false;
        if (!self::$connection)
            self::$connection = ftp_connect($_FTPCONFIG['host']);
        if (self::$connection) {
            self::$ftpPath = $_FTPCONFIG['path'].ASCMS_PATH_OFFSET;
            if (@ftp_login(
                  self::$connection,
                  $_FTPCONFIG['username'],
                  $_FTPCONFIG['password'])
            ) {
                self::$ftpAuth = true;
                return true;
            }

            @ftp_close(self::$connection);
        }
        // Shut down FTP completely
        self::$connection = null;
        $_FTPCONFIG['is_activated'] = false;
        return false;
    }


    function checkConnection()
    {
        return
            'FTP: '.(self::$connection ? 'enabled' : 'disabled').
            ' - '.
            'Safe Mode: '.(@ini_get('safe_mode') ? 'on' : 'off');
    }


    function copyDir($orgPath, $orgWebPath, $orgDirName, $newPath, $newWebPath, $newDirName, $ignoreExists = false)
    {
        $orgWebPath=$this->checkWebPath($orgWebPath);
        $newWebPath=$this->checkWebPath($newWebPath);

        if (file_exists($newPath.$newDirName) && !$ignoreExists) {
            $newDirName = $newDirName.'_'.time();
        }
        $status = $this->mkDir($newPath, $newWebPath, $newDirName);
        if ($status!= 'error') {
            $directory = @opendir($orgPath.$orgDirName);
            $file = @readdir($directory);
            while ($file) {
                if ($file!='.' && $file!='..') {
                    if (!is_dir($orgPath.$orgDirName.'/'.$file)) {
                            $this->copyFile($orgPath, $orgDirName.'/'.$file, $newPath, $newDirName.'/'.$file);
                    } else {
                        $this->copyDir($orgPath, $orgWebPath, $orgDirName.'/'.$file, $newPath, $newWebPath, $newDirName.'/'.$file);
                    }
                }
                $file = @readdir($directory);
            }
            closedir($directory);
        }
        return $status;
    }


    function copyFile($orgPath, $orgFileName, $newPath, $newFileName, $ignoreExists = false)
    {
        if (file_exists($newPath.$newFileName)) {
            $info   = pathinfo($newFileName);
            $exte   = $info['extension'];
            $exte   = (!empty($exte)) ? '.' . $exte : '';
            $part   = substr($newFileName, 0, strlen($newFileName) - strlen($exte));
            if (!$ignoreExists) {
                $newFileName  = $part . '_' . (time()) . $exte;
            }
        }
        if (copy($orgPath.$orgFileName, $newPath.$newFileName)) {
            \Cx\Lib\FileSystem\FileSystem::makeWritable($newPath.$newFileName);
        } else {
            $newFileName = 'error';
        }
        return $newFileName;
    }


    function mkDir($path, $webPath, $dirName)
    {
        global $_FTPCONFIG;

        $webPath=$this->checkWebPath($webPath);
        if (file_exists($path.$dirName)) {
            $dirName = $dirName.'_'.time();
        }
        $newDir = $_FTPCONFIG['path'].$webPath.$dirName;
        if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
            self::init();
        if ($_FTPCONFIG['is_activated']) {
            if (!ftp_mkdir(self::$connection, $newDir)) {
                $dirName = 'error';
            }
        } else {
            if (!@mkdir($path.$dirName)) {
                $dirName = 'error';
            }
        }
        if ($dirName != 'error') {
            \Cx\Lib\FileSystem\FileSystem::makeWritable($path.$dirName);
        }
        return $dirName;
    }


    function delDir($path, $webPath, $dirName)
    {
        global $_FTPCONFIG;

        if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
            self::init();
        $webPath=$this->checkWebPath($webPath);
        $directory = @opendir($path.$dirName);
        $file = @readdir($directory);
        while ($file) {
            if ($file!='.' && $file!='..') {
                if ($_FTPCONFIG['is_activated']) {
                    if (!is_dir($path.$dirName.'/'.$file)) {
                        @ftp_delete(self::$connection, $_FTPCONFIG['path'].$webPath.$dirName.'/'.$file);
                    } else {
                        $this->delDir($path, $webPath, $dirName.'/'.$file);
                    }
                } else {
                    if (!is_dir($path.$dirName.'/'.$file)) {
                        $this->delFile($path, $webPath, $dirName.'/'.$file);
                    } else {
                        $this->delDir($path, $webPath, $dirName.'/'.$file);
                    }
                }
            }
            $file = @readdir($directory);
        }
        closedir($directory);
        if ($_FTPCONFIG['is_activated']) {
            if (!@ftp_rmdir(self::$connection,  $_FTPCONFIG['path'].$webPath.$dirName)) {
                return 'error';
            }
        } else {
            if (!@rmdir($path.$dirName.'/'.$file)) {
                return 'error';
            }
        }
        return '';
    }


    function delFile($path, $webPath, $fileName)
    {
        global $_FTPCONFIG;

        if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
            self::init();
        $webPath = $this->checkWebPath($webPath);
        $delFile = $_FTPCONFIG['path'].$webPath.$fileName;
        if ($_FTPCONFIG['is_activated']) {
            if (@ftp_delete(self::$connection, $delFile)) return $delFile;
            return 'error';
        } else {
            @unlink($path.$fileName);
            clearstatcache();
            if (@file_exists($path.$fileName)) {
                $filesys = eregi_replace('/', '\\', $path.$fileName);
//                @system("del $filesys");
//                clearstatcache();
//                // Doesn't work in safe mode
//                if (@file_exists($path.$fileName)) {
                    @chmod ($path.$fileName, 0775);
                    @unlink($path.$fileName);
                    @system("del $filesys");
//                }
            }
            clearstatcache();
            if (@file_exists($path.$fileName)) return 'error';
        }
        return $fileName;
    }


    function checkWebPath($webPath)
    {
        global $_FTPCONFIG;

        if ($_FTPCONFIG['path'] == '') {
            if (substr($webPath, 0, 1) == '/') {
                $webPath = substr($webPath, 1);
            } else {
                $webPath = $webPath;
            }
        } else {
            $webPath = $webPath;
        }

        return $webPath;
    }


    // replaces some characters
    public static function replaceCharacters($string) {
        // replace $change with ''
        $change = array('+', '¦', '"', '@', '*', '#', '°', '%', '§', '&', '¬', '/', '|', '(', '¢', ')', '=', '?', '\'', '´', '`', '^', '~', '!', '¨', '[', ']', '{', '}', '£', '$', '-', '<', '>', '\\', ';', ',', ':');
        // replace $signs1 with $signs
        $signs1 = array(' ', 'ä', 'ö', 'ü', 'ç');
        $signs2 = array('_', 'ae', 'oe', 'ue', 'c');

        $string = strtolower($string);
        foreach ($change as $str) {
            $string = str_replace($str, '', $string);
        }
        for ($x = 0; $x < count($signs1); $x++) {
            $string = str_replace($signs1[$x], $signs2[$x], $string);
        }
        $string = str_replace('__', '_', $string);

        if (strlen($string) > 40) {
            $info       = pathinfo($string);
            $stringExt  = $info['extension'];

            $stringName = substr($string, 0, strlen($string) - (strlen($stringExt) + 1));
            $stringName = substr($stringName, 0, 40 - (strlen($stringExt) + 1));
            $string     = $stringName . '.' . $stringExt;
        }

        return $string;
    }


    function renameFile($path, $webPath, $oldFileName, $newFileName)
    {
        global $_FTPCONFIG;

        if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
            self::init();
        $webPath = $this->checkWebPath($webPath);
        $status = 'error';
        if ($oldFileName != $newFileName) {
            if (file_exists($path.$newFileName)) {
                $info   = pathinfo($newFileName);
                $exte   = $info['extension'];
                $exte   = (!empty($exte)) ? '.' . $exte : '';
                $part   = substr($newFileName, 0, strlen($newFileName) - strlen($exte));
                $newFileName  = $part . '_' . (time()) . $exte;
            }
            if ($_FTPCONFIG['is_activated']) {
                if (ftp_rename(self::$connection, $_FTPCONFIG['path'].$webPath.$oldFileName, $_FTPCONFIG['path'].$webPath.$newFileName)) {
                    $status = $newFileName;
                }
            } else {
                if (rename($path.$oldFileName, $path.$newFileName)) {
                    $status = $newFileName;
                }
            }
        } else {
            $status = $oldFileName;
        }
        return $status;
    }


    function renameDir($path, $webPath, $oldDirName, $newDirName)
    {
        global $_FTPCONFIG;

        if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
            self::init();
        $webPath = $this->checkWebPath($webPath);
        $status = 'error';
        if ($oldDirName != $newDirName) {
            if (file_exists($path.$newDirName)) {
                $newDirName = $newDirName;
            }
            if ($_FTPCONFIG['is_activated']) {
                if (ftp_rename(self::$connection, $_FTPCONFIG['path'].$webPath.$oldDirName, $_FTPCONFIG['path'].$webPath.$newDirName)) {
                    $status = $newDirName;
                }
            } else {
                if (rename($path.$oldDirName, $path.$newDirName)) {
                    $status = $newDirName;
                }
            }
        } else {
            $status = $oldDirName;
        }
        return $status;
    }


    function setChmod($path, $webPath, $fileName)
    {
        global $_FTPCONFIG;

        if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
            self::init();
        if (!file_exists($path.$fileName)) return false;
        if (is_dir($path.$fileName)) {
            if (@chmod($path.$fileName, self::CHMOD_FOLDER)) return true;
            if ($_FTPCONFIG['is_activated']) {
                return @ftp_chmod(self::$connection, self::CHMOD_FOLDER, $_FTPCONFIG['path'].$webPath.$fileName);
            }
        } else {
            if (@chmod($path.$fileName, self::CHMOD_FILE )) return true;
            if ($_FTPCONFIG['is_activated']) {
                return @ftp_chmod(self::$connection, self::CHMOD_FILE, $_FTPCONFIG['path'].$webPath.$fileName);
            }
        }
        return false;
    }


////////////////////////////////////////////////////////////////////////////////
// New static methods replacing the old object methods
////////////////////////////////////////////////////////////////////////////////
// These are simplified to use a single path argument only.
// Any of these use paths that are *ALWAYS* relative to the
// ASCMS_DOCUMENT_ROOT constant defined in config/set_constants.php.
// Other arguments *MUST NOT* contain "path" in their name in any case;
// rather call them "folder_name" or "file_name".
////////////////////////////////////////////////////////////////////////////////

    /**
     * Moves an uploaded file to a specified path
     *
     * Returns true if the file name is valid, the file type matches one of
     * the accepted file types, if specified, is not too large, and can be
     * moved successfully to its target folder.
     * Missing folders are created.  If this fails, returns false.
     * Mind that the target path *MUST NOT* include ASCMS_PATH, and *SHOULD*
     * not include ASCMS_PATH_OFFSET.  The latter will be cut off, however.
     * The $target_path argument, given by reference, is fixed accordingly.
     * If the file name found in $upload_field_name is empty, returns the
     * empty string.
     * Non-positive values for $maximum_size are ignored, as are empty
     * values for $accepted_types.
     * @param   string  $upload_field_name  File input field name
     * @param   string  $target_path        Target path, relative to the
     *                                      document root, including the file
     *                                      name, by reference.
     * @param   integer $maximum_size       The optional maximum allowed file size
     * @param   string  $accepted_types     The optional allowed MIME type
     * @return  boolean                     True on success, the empty string
     *                                      if there is nothing to do, or
     *                                      false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com> (Parts, fixed path handling)
     * @since   2.2.0
     */
    static function upload_file_http(
        $upload_field_name, &$target_path,
        $maximum_size=0, $accepted_types=false)
    {
        // Skip files that are not uploaded at all
        if (empty($_FILES[$upload_field_name])) {
//DBG::log("File::upload_file_http($upload_field_name, $target_path, $maximum_size, $accepted_types): No file for index $upload_field_name<br />");
            return '';
        }

        self::path_relative_to_root($target_path);
//DBG::log("File::upload_file_http($upload_field_name, $target_path, $maximum_size, $accepted_types): Fixed target path $target_path<br />");
        if (   empty($upload_field_name)
            || empty($target_path)) {
//DBG::log("File::upload_file_http($upload_field_name, $target_path, $maximum_size, $accepted_types): Missing mandatory argument<br />");
            self::$error = self::ERROR_MISSING_ARGUMENT;
            return false;
        }
        $tmp_path = $_FILES[$upload_field_name]['tmp_name'];
        $file_name = $_FILES[$upload_field_name]['name'];
        if (   $accepted_types
            && !Filetype::matchMimetypes($file_name, $accepted_types)) {
//DBG::log("File::upload_file_http(): Error: Found no matching MIME type for extension ($file_name)<br />");
            self::$error = self::ERROR_INVALID_FILETYPE;
            return false;
        }
        if ($maximum_size > 0 && filesize($tmp_path) > $maximum_size) {
//DBG::log("File::upload_file_http($upload_field_name, $target_path, $maximum_size, $accepted_types): Size greater than $maximum_size<br />");
            self::$error = self::ERROR_FILESIZE_TOO_BIG;
            return false;
        }
        // Create the target folder if it doesn't exist
        if (!File::make_folder(dirname($target_path))) {
//DBG::log("File::upload_file_http(): Failed to create folder ".dirname($target_path)." for $target_path<br />");
            self::$error = self::ERROR_CANNOT_CREATE_FOLDER;
            return false;
        }
        if (move_uploaded_file(
            $tmp_path, ASCMS_DOCUMENT_ROOT.'/'.$target_path)) {
//DBG::log("File::upload_file_http($upload_field_name, $target_path, $maximum_size, $accepted_types): File successfully moved to $target_path<br />");
            return true;
        }
//DBG::log("File::upload_file_http($upload_field_name, $target_path, $maximum_size, $accepted_types): move_uploaded_file failed<br />");
        self::$error = self::ERROR_CANNOT_MOVE_FILE;
        return false;
    }


    /**
     * Takes the path given by reference and removes any leading
     * folders up to and including the ASCMS_PATH_OFFSET, including
     * path separators (\ and /).
     *
     * Important note: The regex used to cut away the excess path
     * is non-greedy and works fine in most cases.  However, there
     * is a small risk that it may go wrong if two things occur at the
     * same time, namely:
     * - the ASCMS_PATH_OFFSET is not part of the path provided, and
     * - the path contains a folder or file with the same name.
     * If this is the case, you can either change the offset or the
     * name of the subfolder or file, whichever is acceptable.
     * @param   string    $path       Any absolute or relative path
     * @return  void
     * @author  Reto Kohli <reto.kohli@comvation.com>
     * @since   2.2.0
     */
    static function path_relative_to_root(&$path)
    {
        if (strpos($path, ASCMS_DOCUMENT_ROOT) === 0) {
            $path = substr($path, strlen(ASCMS_DOCUMENT_ROOT) + 1);
        } elseif (ASCMS_PATH_OFFSET && strpos($path, ASCMS_PATH_OFFSET) === 0) {
            $path = substr($path, strlen(ASCMS_PATH_OFFSET) + 1);
        } elseif (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }
    }


    /**
     * Creates the folder for the given path
     *
     * If the path already exists, returns true if it's a folder, or
     * false if it's a file.
     * @param   string    $folder_path    The path of the folder
     * @return  boolean                   True on success, false otherwise
     */
    static function make_folder($folder_path)
    {
        self::path_relative_to_root($folder_path);
        if (self::exists($folder_path)) {
            if (is_dir(ASCMS_DOCUMENT_ROOT.'/'.$folder_path)) {
//DBG::log("File::make_folder($folder_path): OK, folder $folder_path exists already<br />");
                return true;
            }
//DBG::log("File::make_folder($folder_path): FAIL, a file of the name $folder_path exists already<br />");
            return false;
        }

        \Cx\Lib\FileSystem\FileSystem::makeWritable(dirname(ASCMS_DOCUMENT_ROOT.'/'.$folder_path));
        @mkdir(ASCMS_DOCUMENT_ROOT.'/'.$folder_path);
        if (!self::exists($folder_path)) {
//DBG::log("File::make_folder($folder_path): FAIL, cannot create folder ".ASCMS_DOCUMENT_ROOT."/$folder_path<br />");
            if (!self::make_folder_ftp($folder_path)) {
//DBG::log("File::make_folder($folder_path): FAIL, cannot create folder FTP ".ASCMS_DOCUMENT_ROOT."/$folder_path<br />");
                return false;
            }
//DBG::log("File::make_folder($folder_path): OK created folder FTP ".ASCMS_DOCUMENT_ROOT."/$folder_path<br />");
        }
        $flags = self::chmod($folder_path, self::CHMOD_FOLDER);
        if ($flags == self::CHMOD_FOLDER) {
//DBG::log("File::make_folder($folder_path): OK, folder $folder_path created and chmodded<br />");
            return true;
        }
//        else {
//DBG::log("File::make_folder($folder_path): FAILED to chmod $folder_path, returned flags: ".decoct($flags)."<br />");
//        }
        if (!self::exists($folder_path)) {
//DBG::log("File::make_folder($folder_path): FAIL, folder $folder_path does still not exist<br />");
            return false;
        }
//DBG::log("File::make_folder($folder_path): created folder $folder_path, but FAILED to chmod!<br />");
        return true;
    }


    /**
     * Creates the folder for the given path by means of FTP
     * @param   string    $folder_path    The path of the folder
     * @return  boolean                   True on success, false otherwise
     * @access  private
     */
    private static function make_folder_ftp($folder_path)
    {
        global $_FTPCONFIG;

        if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
            self::init();
        if (!self::$connection) {
//DBG::log("File::make_folder_ftp($folder_path): ERROR: No FTP connection<br />");
            return false;
        }
        @ftp_mkdir(self::$connection, self::$ftpPath.'/'.$folder_path);
        if (!self::exists($folder_path)) {
//DBG::log("File::make_folder_ftp($folder_path): Failed to create folder ".self::$ftpPath."/$folder_path<br />");
            return false;
        }
        return true;
    }


    /**
     * Copies a folder recursively from the source to the target path
     *
     * If $force is true, the folder and its contents are copied even if
     * a folder of the same name exists in the target path already.
     * Otherwise, false is returned.
     * @param   string    $source_path    The path of the source folder
     * @param   string    $target_path    The path of the target folder
     * @param   string    $force          Force copying if true
     * @return  boolean                   True on success, false otherwise
     */
    public static function copy_folder($source_path, $target_path, $force=false)
    {
        self::path_relative_to_root($source_path);
        self::path_relative_to_root($target_path);
        if (self::exists($target_path)) {
            if (!$force)
                return false;
        } else {
            if (!self::make_folder($target_path)) {
                return false;
            }
        }
        $directory = @opendir(ASCMS_DOCUMENT_ROOT.'/'.$source_path);
        $file = @readdir($directory);
        while ($file) {
            if (preg_match('/^\.\.?$/', $file)) {
                $file = @readdir($directory);
                continue;
            }
            if (is_file(ASCMS_DOCUMENT_ROOT.'/'.$source_path.'/'.$file)) {
                if (!self::copy_file(
                    $source_path.'/'.$file, $target_path.'/'.$file, $force)) {
                    return false;
                }
            } else {
                if (!self::copy_folder(
                    $source_path.'/'.$file, $target_path.'/'.$file, $force)) {
                    return false;
                }
            }
            $file = @readdir($directory);
        }
        closedir($directory);
        return true;
    }


    /**
     * Copies a file from the source to the target path
     *
     * If $force is true, any destination file will be overwritten.
     * @param   string    $source_path    The path of the source file
     * @param   string    $target_path    The path of the target file
     * @param   string    $force          Overwrite if true
     * @return  boolean                   True on success, false otherwise
     */
    static function copy_file($source_path, $target_path, $force=false)
    {
        self::path_relative_to_root($source_path);
        self::path_relative_to_root($target_path);
        if (self::exists($target_path) && !$force)
            return false;
        if (!copy(ASCMS_DOCUMENT_ROOT.'/'.$source_path,
                ASCMS_DOCUMENT_ROOT.'/'.$target_path)) {
            if (!self::copy_file_ftp($source_path, $target_path))
                return false;
        }
        return self::chmod($target_path, self::CHMOD_FILE);
    }


    /**
     * Copies a file from the source to the target path by means of FTP
     *
     * If $force is true, any destination file will be overwritten.
     * @param   string    $source_path    The path of the source file
     * @param   string    $target_path    The path of the target file
     * @param   string    $force          Overwrite if true
     * @return  boolean                   True on success, false otherwise
     * @access  private
     */
    private static function copy_file_ftp($source_path, $target_path)
    {
        global $_FTPCONFIG;

        if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
            self::init();
        if (!self::$connection) return false;
        $resource = fopen(ASCMS_DOCUMENT_ROOT.'/'.$source_path, 'r');
        if (!$resource) return false;
        $result = ftp_fput(
            self::$connection, self::$ftpPath.'/'.$target_path,
            $resource, FTP_BINARY);
        fclose($resource);
        return $result;
    }


    /**
     * Creates a new empty file with the given file path
     *
     * Returns false both if the file exists already, or if it
     * couldn't be created.
     * @param     string    $file_path    Path of the file to create
     * @return    boolean                 True on success, false otherwise
     */
    /*
// TODO: this method is nowhere in use // 02/26/12 thomas.daeppen@comvation.com
    static function create_file($file_path)
    {
        global $_FTPCONFIG;

        self::path_relative_to_root($file_path);
        if (self::exists($file_path)) return false;
        if (!file_put_contents(ASCMS_DOCUMENT_ROOT.'/'.$file_path, '')) {
            if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
                self::init();
            if (!self::$connection) return false;
            $resource = fopen('php://memory', 'w+');
            if (!$resource) return false;
            $result = ftp_fput(
                self::$connection, self::$ftpPath.'/'.$file_path,
                $resource, FTP_ASCII);
            fclose($resource);
            if (!$result) return false;
        }
        return self::chmod($file_path, self::CHMOD_FILE);
    }*/


    /**
     * Deletes the given folder name from the path
     *
     * If $force is true, recursively deletes any content of the folder
     * first.  Otherwise, if the folder is not empty, false is returned.
     * Returns true if the folder was deleted.
     * @param   string    $folder_path  The folder path
     * @param   boolean   $force        If true, deletes contents of the folder
     * @return  boolean                 True on success, false otherwise
     */
    public static function delete_folder($folder_path, $force=false)
    {
        self::path_relative_to_root($folder_path);
        $resource = @opendir(ASCMS_DOCUMENT_ROOT.'/'.$folder_path);
        if (!$resource) return false;
        $file = @readdir($resource);
        while ($file !== false) {
            if (preg_match('/^\.\.?/', $file)) {
                $file = @readdir($resource);
                continue;
            }
            if (!$force) {
                closedir($resource);
                return false;
            }
            $file_path = $folder_path.'/'.$file;
            if (is_file(ASCMS_DOCUMENT_ROOT.'/'.$file_path)) {
                if (!self::delete_file($file_path)) return false;
            } else {
                if (!self::delete_folder($file_path, $force)) return false;
            }
            $file = @readdir($resource);
        }
        closedir($resource);

        \Cx\Lib\FileSystem\FileSystem::makeWritable(dirname(ASCMS_DOCUMENT_ROOT.'/'.$folder_path));
        if (@rmdir(ASCMS_DOCUMENT_ROOT.'/'.$folder_path))
            return true;
        return self::delete_folder_ftp($folder_path);
    }


    /**
     * Delete the folder from the given path by means of FTP
     *
     * This will of course fail if the FTP connection has not been set up
     * by calling {@see init()} first.
     * @param   string    $folder_path    The folder path
     * @return  boolean                   True on success, false otherwise
     * @access  private
     */
    private static function delete_folder_ftp($folder_path)
    {
        global $_FTPCONFIG;

        if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
            self::init();
        if (!self::$connection) return false;
        return @ftp_rmdir(
            self::$connection, self::$ftpPath.'/'.$folder_path);
    }


    /**
     * Deletes the file from the path specified
     *
     * Returns true if the file doesn't exist in the first place.
     * @param   string    $file_path      The path of the file
     * @return  boolean                   True on success, false otherwise
     */
    public static function delete_file($file_path)
    {
        try {
            $objFile = new \Cx\Lib\FileSystem\File($file_path);
            $objFile->delete();
            return true;
        } catch (FileSystemException $e) {
            \DBG::msg($e->getMessage());
        }

        return false;
    }


    /**
     * Deletes the file from the path specified by means of FTP
     * @param   string    $file_path      The path of the file
     * @return  boolean                   True on success, false otherwise
     * @access  private
     */
    private static function delete_file_ftp($file_path)
    {
        global $_FTPCONFIG;

        if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
            self::init();
        if (!self::$connection) {
//DBG::log("File::delete_file_ftp($file_path): No FTP connection<br />");
            return false;
        }
        if (!@ftp_delete(self::$connection, self::$ftpPath.'/'.$file_path)) {
//DBG::log("File::delete_file_ftp($file_path): Failed to delete file ".self::$ftpPath.'/'.$file_path);
            return false;
        }
        clearstatcache();
        if (self::exists($file_path)) {
//DBG::log("File::delete_file_ftp($file_path): File still exists: ".self::$ftpPath.'/'.$file_path);
            return false;
        }
        return true;
    }



    /**
     * Makes a valid file path
     *
     * Replaces non-ASCII and some other characters in the string
     * given by reference with underscores.
     * @param   string    $path       The path (or any other string)
     * @return  void
     * @todo    Test!
     * @todo    Replace non-ASCII charactes with octal values
     */
    static function clean_path(&$path)
    {
        $path = preg_replace(
            '/[¦"@*#°%§&¬|¢?\'´`^~¨£$<>\200-\377]/', '_', $path
        );
//        $path = preg_replace(
//            '/^(.+?){0,40}.*?(\.[^.]+)?$/', '\1\2', $path);
    }


    /**
     * Renames or moves a file or folder
     *
     * If a file or folder with the $to_path already exists, and $force
     * is false, returns false.
     * @param   string    $from_path    The original path
     * @param   string    $to_path      The destination path
     * @param   boolean   $force        Overwrites the destination if true
     * @return  boolean                 True on success, false otherwise
     */
    static function move($from_path, $to_path, $force)
    {
        self::path_relative_to_root($from_path);
        self::path_relative_to_root($to_path);
        if (self::exists($to_path) && !$force)
            return false;
        if (!rename(
            ASCMS_DOCUMENT_ROOT.'/'.$from_path,
            ASCMS_DOCUMENT_ROOT.'/'.$to_path)) {
            if (!self::move_ftp($from_path, $to_path, $force)) return false;
        }
        return self::chmod(
            $to_path,
            (is_file(ASCMS_DOCUMENT_ROOT.'/'.$from_path)
              ? self::CHMOD_FILE : self::CHMOD_FOLDER));
    }


    /**
     * Renames or moves a file or folder by means of the FTP
     *
     * If a file or folder with the $to_path already exists, and $force
     * is false, returns false.
     * @param   string    $from_path    The original path
     * @param   string    $to_path      The destination path
     * @param   boolean   $force        Overwrites the destination if true
     * @return  boolean                 True on success, false otherwise
     * @access  private
     */
    private static function move_ftp($from_path, $to_path)
    {
        global $_FTPCONFIG;

        if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
            self::init();
        if (!self::$connection) return false;
// TODO:
// Perhaps the destination has to be removed first if it exists?
        if (!ftp_rename(
            self::$connection,
            self::$ftpPath.'/'.$from_path,
            self::$ftpPath.'/'.$to_path))
            return false;
        return true;
    }


    /**
     * Applies the flags to the given path
     *
     * The path may be a file or a folder.  The flags are considered to be
     * octal values, as required by chmod().
     * @param   string    $path       The path to be chmodded
     * @param   integer   $flags      The flags to apply
     * @return  boolean               True on success, false otherwise
     */
    static function chmod($path, $flags)
    {
        global $_FTPCONFIG;

        self::path_relative_to_root($path);
        if (!self::exists($path)) {
//DBG::log("File::chmod($path, ".decoct($flags)."): FAIL, folder $path does not exist<br />");
            return false;
        }
        if (@chmod(ASCMS_DOCUMENT_ROOT.'/'.$path, $flags)) {
//DBG::log("File::chmod($path, ".decoct($flags)."): OK, folder $path chmodded<br />");
            return true;
        }
//DBG::log("File::chmod($path, ".decoct($flags)."): FAILED to chmod folder $path<br />");
        if ($_FTPCONFIG['is_activated'] && empty(self::$connection))
            self::init();
        if (self::$connection) {
            $result = @ftp_chmod(
                self::$connection, $flags, self::$ftpPath.'/'.$path);
            if ($result) {
//DBG::log("File::chmod($path, ".decoct($flags)."): chmodded folder FTP $path, flags ".decoct($result)."<br />");
                return true;
            }
//DBG::log("File::chmod($path, ".decoct($flags)."): FAILED to chmod folder FTP ".self::$ftpPath."/$path<br />");
        }
//DBG::log("File::chmod($path, ".decoct($flags)."): FAILED to chmod folder $path<br />");
        return false;
    }

    public static function touch($path)
    {
        try {
            $objFile = new \Cx\Lib\FileSystem\File($path);
            $objFile->touch();
            return true;
        } catch (FileSystemException $e) {
            \DBG::msg($e->getMessage());
        }

        return false;
    }

    public static function makeWritable($path)
    {
        try {
            $objFile = new \Cx\Lib\FileSystem\File($path);
            $objFile->makeWritable();
            return true;
        } catch (FileSystemException $e) {
            \DBG::msg($e->getMessage());
        }

        return false;
    }


    /**
     * Wrapper for file_exists()
     *
     * Prepends ASCMS_DOCUMENT_ROOT to the path.
     * The file is stat()ed before calling file_exists() in order to
     * update a potentially outdated cache.
     * @param   string    $path     The file or folder path
     * @return  boolean             True if the file exists, false otherwise
     */
    static function exists($path)
    {
        // Clear the file cache.  file_exists() relies on that too much
        clearstatcache();
        self::path_relative_to_root($path);
        $result = file_exists(ASCMS_DOCUMENT_ROOT.'/'.$path);
//if ($result) {
//DBG::log("File::exists($path): file ".ASCMS_DOCUMENT_ROOT."/$path exists<br />");
//} else {
//DBG::log("File::exists($path): file ".ASCMS_DOCUMENT_ROOT."/$path does not exist<br />");
//}
        return $result;
    }

}
