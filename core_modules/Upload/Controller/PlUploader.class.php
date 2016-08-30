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
 * PlUploader
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */

namespace Cx\Core_Modules\Upload\Controller;

/**
 * PlUploader - Flash uploader class.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */
class PlUploader extends Uploader
{
    /**
     * @override
     */
    public function handleRequest()
    {
        // HTTP headers for no cache etc
        header('Content-type: text/plain; charset=UTF-8');
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: no-store, no-cache, must-revalidate");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        // Get parameters
        $chunk = isset($_REQUEST["chunk"]) ? $_REQUEST["chunk"] : 0;
        $chunks = isset($_REQUEST["chunks"]) ? $_REQUEST["chunks"] : 0;
        $fileName = isset($_REQUEST["name"]) ? $_REQUEST["name"] : '';
        $fileCount = $_GET['files'];


        if (\FWValidator::is_file_ending_harmless($fileName)) {
            try {
                $this->addChunk($fileName, $chunk, $chunks);
            }
            catch (UploaderException $e) {
                die('{"jsonrpc" : "2.0", "error" : {"code": 101, "message": "'.$e->getMessage().'"}, "id" : "id"}');
            }
        }
        else {
            if ($chunk == 0) {
                // only count first chunk
// TODO: there must be a way to cancel the upload process on the client side
                $this->addHarmfulFileToResponse($fileName);
            }
        }

        if($chunk == $chunks-1) //upload finished
            $this->handleCallback($fileCount);

        die('{"jsonrpc" : "2.0", "result" : null, "id" : "id"}');
    }

    /**
     * @override
     */
    public function getXHtml()
    {
      global $_CORELANG;
      // CSS dependencies
      \JS::activate('cx');

      $uploadPath = $this->getUploadPath('pl');

      $tpl = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH.'/Upload/template/uploaders');
      $tpl->setErrorHandling(PEAR_ERROR_DIE);

      $tpl->loadTemplateFile('pl.html');
      $tpl->setVariable('UPLOAD_FLASH_URL', ASCMS_CORE_MODULE_WEB_PATH.'/Upload/ressources/uploaders/pl/plupload.flash.swf');
      $tpl->setVariable('UPLOAD_CHUNK_LENGTH', \FWSystem::getLiteralSizeFormat(\FWSystem::getMaxUploadFileSize()-1000));
      $tpl->setVariable('UPLOAD_URL', $uploadPath);
      $tpl->setVariable('UPLOAD_ID', $this->uploadId);

      //I18N
      $tpl->setVariable(array(
          'UPLOAD' => $_CORELANG['UPLOAD'],
          'OTHER_UPLOADERS' => $_CORELANG['OTHER_UPLOADERS'],
          'FORM_UPLOADER' => $_CORELANG['FORM_UPLOADER'],
          'PL_UPLOADER' => $_CORELANG['PL_UPLOADER'],
          'JUMP_UPLOADER' => $_CORELANG['JUMP_UPLOADER'],

          'SELECT_FILES' => $_CORELANG['SELECT_FILES'],
          'ADD_INSTRUCTIONS' => $_CORELANG['ADD_INSTRUCTIONS'],
          'FILENAME' => $_CORELANG['FILENAME'],
          'STATUS' => $_CORELANG['STATUS'],
          'SIZE' => $_CORELANG['SIZE'],
          'ADD_FILES' => $_CORELANG['ADD_FILES'],

          'STOP_CURRENT_UPLOAD' => $_CORELANG['STOP_CURRENT_UPLOAD'],
          'DRAG_FILES_HERE' => $_CORELANG['DRAG_FILES_HERE']
      ));

      return $tpl->get();
    }
}
