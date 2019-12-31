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
 * Class ComponentController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Tobias Schmoker <tobias.schmoker@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_uploader
 * @version     1.0.0
 */

namespace Cx\Core_Modules\Uploader\Controller;

// don't load Frontend and BackendController for this core_module
use Cx\Core\Core\Controller\Cx;
use Cx\Core\Core\Model\Entity\SystemComponent;
use Cx\Core\Core\Model\Entity\SystemComponentController;
use Cx\Core_Modules\Uploader\Model\Entity\Uploader;

class ComponentController extends SystemComponentController
{
    /**
     * @var Uploader[]
     */
    protected $uploaderInstances = array();

    public function addUploader(Uploader $uploader) {
        $this->uploaderInstances[] = $uploader;
    }

    public function getControllerClasses() {
        return array();
    }

    public function getControllersAccessableByJson() {
        return array(
            'JsonUploader',
        );
    }

    public function isActive() {
        return (boolean) count($this->uploaderInstances);
    }

    public function preFinalize(\Cx\Core\Html\Sigma $template) {
        if (count($this->uploaderInstances) == 0) {
            return;
        }
        global $_ARRAYLANG;

        \Env::get('init')->loadLanguageData('Uploader');
        foreach ($_ARRAYLANG as $key => $value) {
            if (preg_match("/UPLOADER(_[A-Za-z0-9]+)?/", $key)) {
                \ContrexxJavascript::getInstance()->setVariable(
                    $key, $value, 'mediabrowser'
                );
            }
        }
        $appendix = "";
        foreach ($this->uploaderInstances as $uploader){
            if ($uploader->getType() == Uploader::UPLOADER_TYPE_MODAL){
                $appendix .= $uploader->getContainer();
            }
        }

        $template->_blocks["__global__"] = preg_replace("/<\/body>/", $appendix.'</body>', $template->_blocks["__global__"]);

        \ContrexxJavascript::getInstance()->setVariable(
            'chunk_size', min(floor((\FWSystem::getMaxUploadFileSize()-1000000)/1000000), 20) .'mb', 'uploader'
        );

        \JS::activate('mediabrowser');
        \JS::registerJS('core_modules/Uploader/View/Script/Uploader.js');
    }

}
