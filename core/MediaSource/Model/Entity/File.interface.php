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
 * Base class for files (excluding folders)
 *
 * @copyright   Cloudrexx AG
 * @author Robin Glauser <robin.glauser@comvation.com>
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */

namespace Cx\Core\MediaSource\Model\Entity;

/**
 * Base class for files (exclusing folders)
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_mediasource
 */
interface File {

    /**
     * Returns the FileSystem for this file
     *
     * @return FileSystem This file's FileSystem
     */
    public function getFileSystem();

    /**
     * Returns the path (without filename) for this file
     *
     * @return string Path without filename
     */
    public function getPath();

    /**
     * Returns the filename (without path and extension) for this file
     *
     * @return string Filename without path and extension
     */
    public function getName();

    /**
     * Returns the filename (without path including extension) for this file
     *
     * @return string Filename without path including extension
     */
    public function getFullName();

    /**
     * Returns this file's extension
     *
     * @return string File extension
     */
    public function getExtension();

    /**
     * Returns the MIME type of this file
     *
     * @return string MIME type
     */
    public function getMimeType();

    /**
     * Returns the full file path (path and filename including extension)
     */
    public function __toString();
}
