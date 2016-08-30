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
 * ExposedComboUploader
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */

namespace Cx\Core_Modules\Upload\Controller;

/**
 * ExposedComboUploader - ComboUploader with JQuery expose
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_upload
 */
class ExposedComboUploader extends ComboUploader
{
    public function __construct($backend) {
        parent::__construct($backend);
    }

    public function getXHtml(){
        global $_CORELANG;

        \JS::registerCSS('core_modules/Upload/css/uploaders/exposedCombo/exposedCombo.css');
        \JS::registerJS('core_modules/Upload/js/uploaders/exposedCombo/exposedCombo.js');

        //back up instance name, we're going to set a temporary name for the combo uploader
        $instanceNameBak = $this->jsInstanceName;
        $this->jsInstanceName = 'exposedCombo_comboUploader_'.$this->uploadId;
        $comboXHtml = parent::getXHtml();
        $this->jsInstanceName = $instanceNameBak;

        $tpl = new \Cx\Core\Html\Sigma(ASCMS_CORE_MODULE_PATH.'/Upload/template/uploaders');
        $tpl->setErrorHandling(PEAR_ERROR_DIE);

        $tpl->loadTemplateFile('exposedCombo.html');

        $tpl->setVariable(array(
            'COMBO_CODE' => $comboXHtml,
            'DIALOG_TITLE' => $_CORELANG['UPLOAD_EXPOSED_DIALOG_TITLE']
        ));

        //see Uploader::handleInstanceBusiness
        $this->handleInstanceBusiness($tpl,'exposedCombo');

        return $tpl->get();
    }
}
