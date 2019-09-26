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
 *              Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 * @subpackage  coremodule_mediabrowser
 * @version     1.0.0
 */

namespace Cx\Core_Modules\MediaBrowser\Controller;

use Cx\Core\Core\Model\Entity\SystemComponentController;
use Cx\Core\Html\Sigma;
use Cx\Core_Modules\MediaBrowser\Model\Event\MediaBrowserEventListener;
use Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser;

/**
 * Class ComponentController
 *
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Tobias Schmoker <tobias.schmoker@comvation.com>
 *              Robin Glauser <robin.glauser@comvation.com>
 * @version     1.0.0
 */
class ComponentController extends
    SystemComponentController
{

    /**
     * List with initialised mediabrowser instances.
     * @var array
     */
    protected $mediaBrowserInstances = array();

    /**
     * List of additional JavaScript files, that will be loaded before the
     * the main MediaBrowser.js.
     */
    protected $customJsFiles = array();

    /**
     * {@inheritdoc }
     */
    public function getControllerClasses() {
        if (in_array(
            'Workbench',
            \Cx\Core\ModuleChecker::getInstance(
                $this->cx->getDb()->getEntityManager(),
                $this->cx->getDb()->getAdoDb(),
                $this->cx->getClassLoader()
            )->getCoreModules()
        )) {
            return array('Backend');
        }
        return array();
    }

    /**
     * Register a mediabrowser instance
     * @param MediaBrowser $mediaBrowser
     */
    public function addMediaBrowser(MediaBrowser $mediaBrowser) {
        $this->mediaBrowserInstances[] = $mediaBrowser;
    }

    /**
     * {@inheritdoc }
     */
    public function getControllersAccessableByJson() {
        return array(
            'JsonMediaBrowser',
        );
    }

    /**
     * Register your events here
     *
     * Do not do anything else here than list statements like
     * $this->cx->getEvents()->addEvent($eventName);
     */
    public function registerEvents()
    {
        $eventHandlerInstance = $this->cx->getEvents();
        $eventHandlerInstance->addEvent('MediaBrowser.Plugin:initialize');
    }

    /**
     * Register your event listeners here
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE.
     * Keep in mind, that you can also register your events later.
     * Do not do anything else here than initializing your event listeners and
     * list statements like
     * $this->cx->getEvents()->addEventListener($eventName, $listener);
     */
    public function registerEventListeners() {
        $this->cx->getEvents()->addEventListener(
            'mediasource.load', new MediaBrowserEventListener($this->cx)
        );
    }

    /**
     * @param Sigma $template
     */
    public function preFinalize(Sigma $template) {
        if (count($this->mediaBrowserInstances) == 0) {
            return;
        }
        global $_ARRAYLANG;
        /**
         * @var $init \InitCMS
         */
        $init = \Env::get('init');
        $init->loadLanguageData('MediaBrowser');
        foreach ($_ARRAYLANG as $key => $value) {
            if (preg_match("/TXT_FILEBROWSER_[A-Za-z0-9]+/", $key)) {
                \ContrexxJavascript::getInstance()->setVariable(
                    $key, $value, 'mediabrowser'
                );
            }
        }

        $thumbnailsTemplate = new Sigma();
        $thumbnailsTemplate->loadTemplateFile(
            $this->cx->getCoreModuleFolderName()
            . '/MediaBrowser/View/Template/Thumbnails.html'
        );
        $thumbnailsTemplate->setVariable(
            'TXT_FILEBROWSER_THUMBNAIL_ORIGINAL_SIZE', sprintf(
                $_ARRAYLANG['TXT_FILEBROWSER_THUMBNAIL_ORIGINAL_SIZE']
            )
        );
        foreach (
            $this->cx->getMediaSourceManager()
                ->getThumbnailGenerator()
                ->getThumbnails() as
            $thumbnail
        ) {
            $thumbnailsTemplate->setVariable(
                array(
                    'THUMBNAIL_NAME' => sprintf(
                        $_ARRAYLANG['TXT_FILEBROWSER_THUMBNAIL_' . strtoupper(
                            $thumbnail['name']
                        ) . '_SIZE'], $thumbnail['size']
                    ),
                    'THUMBNAIL_ID' => $thumbnail['id'],
                    'THUMBNAIL_SIZE' => $thumbnail['size']
                )
            );
            $thumbnailsTemplate->parse('thumbnails');
        }
        \ContrexxJavascript::getInstance()->setVariable(
            'thumbnails_template', $thumbnailsTemplate->get(),
            'mediabrowser'
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'chunk_size', min(floor((\FWSystem::getMaxUploadFileSize() - 1000000) / 1000000), 20) . 'mb', 'mediabrowser'
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'languages', \FWLanguage::getActiveFrontendLanguages(), 'mediabrowser'
        );
        \ContrexxJavascript::getInstance()->setVariable(
            'language', \FWLanguage::getLanguageCodeById(\FWLanguage::getDefaultLangId()), 'mediabrowser'
        );
        \JS::activate('mediabrowser');
        // Define the module
        \JS::registerJS('core_modules/MediaBrowser/View/Script/module.js');
        // Dependencies must be loaded first
        \JS::registerJS('core_modules/MediaBrowser/View/Script/service/dataTabs.js');
        // Enable extensions after the dataTabs service, where they plug into
        $this->cx->getEvents()->triggerEvent(
            'MediaBrowser.Plugin:initialize'
        );

        // load custom js files (registered through 'MediaBrowser.Plugin:initialize')
        foreach ($this->customJsFiles as $jsFile) {
            \JS::registerJS($jsFile);
        }

        // Load the dependant main part after extensions have been connected
        \JS::registerJS('core_modules/MediaBrowser/View/Script/MediaBrowser.js');
    }

    /**
     * Register custom JavaScript file to be loaded before the main
     * MediaBrowser.js
     *
     * @param   string  $file   Relative path to a JavaScript file
     */
    public function registerCustomJs($file) {
        $this->customJsFiles[] = $file;
    }
}
