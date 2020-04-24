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
 * Base class for filesystems
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */

namespace Cx\Core\MediaSource\Model\Entity;

/**
 * Base class for filesystems
 *
 * @todo        Implement a driver structure to allow efficient cross FS operations
 * @todo        Create a path helper class to sanitize paths
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */
interface FileSystem {

    /**
     * Returns all files withing a given folder (recursively by default)
     *
     * For each file within $directory the following structure is returned:
     * <filename> => array(
     *     'datainfo' => array(
     *         'filepath' => <path>,
     *         'name' => <filename>,
     *         'size' => <human_readable_size>,
     *         'cleansize' => <size_in_bytes>,
     *         'extension' => <extension>,
     *         'preview' => <preview_link>,
     *         'hasPreview' => <has_preview>,
     *         'active' => <active>,
     *         'type' => <file_or_dir>,
     *         'thumbnail' => array(
     *             <size> => <path>,
     *             ...
     *         ),
     *     ),
     *     <nesting>
     * )
     * <filename> Name of the file without path but including file ending
     * <path> is relative to this FS' root
     * <extension> is "Dir" for directories
     * <preview_link> is relative to Cx root or "none" if no preview is available
     * <has_preview> is "1" if a preview is available, "" otherwise
     * <active> is used to highlight a file or folder to the user. Always set to false
     * <file_or_dir> is either "file" or "dir"
     * <size> and <path> contain list all available thumbnail sizes
     * <nesting> is only set for directories and if $recursive is true. It
     *          contains the same structure again.
     * @todo Extension "Dir" could be ambiguous
     * @todo Sanitize files named "datainfo"
     * @param string $directory Path relative to this FS' root
     * @param boolean $recursive (optional) If set to false, recursion is skipped
     * @return array UTF8 encoded list of file names, see description
     */
    public function getFileList($directory, $recursive = true);

    /**
     * Removes the given file from the OS FS
     *
     * @param File $file File to remove
     * @return string Status message
     */
    public function removeFile(File $file);

    /**
     * Moves a file to a new location
     *
     * @todo Specify whether moving accross FS should be supported by this method
     * @param File $file File to move
     * @param string $destination Destination path (absolute or relative to this FS' root)
     * @return string Status message
     */
    public function moveFile(File $file, $destination);

    /**
     * Writes $content to $file, erases all existing content
     *
     * @param File $file File to write to
     * @param string $content Content to write
     */
    public function writeFile(File $file, $content);

    /**
     * Reads content from $file
     *
     * @param File $file File to write to
     * @return string File contents
     */
    public function readFile(File $file);

    /**
     * Tells whether $file is a directory or not
     *
     * @param File $file File to check
     * @return boolean True if $file is a directory, false otherwise
     */
    public function isDirectory(File $file);

    /**
     * Tells whether $file is not a directory
     *
     * @param File $file File to check
     * @return boolean True if $file is not a directory, false otherwise
     */
    public function isFile(File $file);

    /**
     * @todo Reverse engineer or remove, seems to be unused
     */
    public function getLink(File $file);

    /**
     * Creates a new directory
     *
     * @param string $path Path relative to this FS' root
     * @param string $directory Directory name
     * @return string Status message
     */
    public function createDirectory($path, $directory);

    /**
     * Returns the File instance for a given path
     *
     * $path needs to be within this FS' root, otherwise this method will
     * return false.
     * @param string $path Path relative to this FS' root
     * @return File|false File instance for $path of false
     */
    public function getFileFromPath($path);
}
