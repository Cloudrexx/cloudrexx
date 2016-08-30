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
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_uploader
 */

namespace Cx\Core_Modules\Uploader\Model;


class DefaultUploadCallback implements UploadCallbackInterface
{

    /**
     * @var \Cx\Core\Core\Controller\Cx
     */
    protected $cx;

    /**
     * @param $cx \Cx\Core\Core\Controller\Cx
     */
    public function __construct($cx)
    {
        $this->cx = $cx;
    }

    /**
     * @param $tempPath    String Path to the temporary directory containing the files at this moment.
     * @param $tempWebPath String Points to the same folder as tempPath, but relative to the webroot.
     * @param $data        String Data given to setData() when creating the uploader.
     * @param $uploadId    integer Per-session unique id for the current upload.
     * @param $fileInfos   array('originalFileNames' => array( 'theCurrentAndCleanedFilename.txt' => 'raw!Source#Filename.txt' ) )
     *
     *
     * @return mixed The return value can be an array as shown in the example or null.
     *               When returning an array, all files left in the temporary directory are moved accordingly.
     *               When returning null, all left files are deleted.
     */
    function uploadFinished(
        $tempPath, $tempWebPath, $data, $uploadId, $fileInfos, $response
    )
    {
        return array(
            $this->cx->getWebsiteImagesContentPath(),
            $this->cx->getWebsiteImagesContentWebPath()
        );
    }
}
