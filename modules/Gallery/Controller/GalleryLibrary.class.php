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
 * Gallery library
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_gallery
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\Gallery\Controller;
/**
 * Gallery library
 *
 * Library for the Gallery
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_gallery
 */
class GalleryLibrary
{
    protected $sql;

    public function __construct()
    {
        $this->sql = new GallerySql();
    }

    /**
    * Gets the gallery settings
    *
    * @global  ADONewConnection
    */
    public function getSettings()
    {
        global $objDatabase;
        $objResult = $objDatabase->Execute("SELECT name,value FROM ".DBPREFIX."module_gallery_settings");
        while (!$objResult->EOF) {
            $this->arrSettings[$objResult->fields['name']] = $objResult->fields['value'];
            $objResult->MoveNext();
        }
    }

    /**
     * Get File object by the given path
     *
     * @param string $filePath File path
     * @return \Cx\Core\MediaSource\Model\Entity\LocalFile LocalFile object
     */
    public function getFileByPath($filePath)
    {
        if (empty($filePath)) {
            return;
        }

        try {
            $cx          = \Cx\Core\Core\Controller\Cx::instanciate();
            $mediaSource = $cx->getMediaSourceManager()->getMediaSourceByPath($filePath);
            $filePath    = substr($filePath, strlen($mediaSource->getDirectory()[1]));
        } catch (\Cx\Core\MediaSource\Model\Entity\MediaSourceManagerException $e) {
            \DBG::log($e->getMessage());
            return;
        }

        return new \Cx\Core\MediaSource\Model\Entity\LocalFile(
            $filePath,
            $mediaSource->getFileSystem()
        );
    }
}

