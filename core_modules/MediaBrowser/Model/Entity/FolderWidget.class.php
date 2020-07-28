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
 * Class FolderWidget
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_mediabrowser
 */

namespace Cx\Core_Modules\MediaBrowser\Model\Entity;

/**
 * Class FolderWidget
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_mediabrowser
 */
class FolderWidget extends \Cx\Model\Base\EntityBase
{
    /**
     * mediabrowser mode - view only
     */
    const MODE_VIEW_ONLY = 1;

    /**
     * The folder we are monitoring
     */
    protected $folder;

    /**
     * The unique widget identifier
     */
    protected $id;

    /**
     * The Curren mode of folder widget
     */
    protected $mode;

    /**
     * Init the folder widget
     *
     * @param string $folder
     * @param boolean $viewOnly
     */
    public function __construct($folder, $viewOnly = false)
    {
        if (!isset($_SESSION['MediaBrowser'])) {
            $_SESSION['MediaBrowser'] = array();
        }
        if (!isset($_SESSION['MediaBrowser']['FolderWidget'])) {
            $_SESSION['MediaBrowser']['FolderWidget'] = array();
        }
        $lastKey = count($_SESSION['MediaBrowser']['FolderWidget']);
        $widgetId = ++$lastKey;

        $this->id = $widgetId;

        $this->folder = $folder;

        if ($viewOnly) {
            $this->mode = self::MODE_VIEW_ONLY;
        }

        $_SESSION['MediaBrowser']['FolderWidget'][$this->id] = array(
            'folder' => $this->folder,
            'mode' => $this->mode
        );
    }

    /**
     * Set the folder widget id
     *
     * @param integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Get the folder widget id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set the folder path
     *
     * @param string $folder
     */
    public function setFolder($folder) {
        $this->folder = $folder;
    }

    /**
     * Get the XHTML to display the widget.
     */
    public function getXhtml()
    {
        \JS::activate('mediabrowser');
        \JS::registerJS('core_modules/MediaBrowser/View/Script/FolderWidget.js');
        \JS::registerCSS('core_modules/MediaBrowser/View/Style/FolderWidget.css');

        $tpl = new \Cx\Core\Html\Sigma(\Cx\Core\Core\Controller\Cx::instanciate()->getCoreModuleFolderName() . '/MediaBrowser/View/Template/');

        $tpl->loadTemplateFile('FolderWidget.html');
        $tpl->setVariable(array(
            'MEDIABROWSER_FOLDER_WIDGET_ID' => $this->id,
            'MEDIABROWSER_FOLDER_WIDGET_IS_EDITABLE' => ($this->mode != self::MODE_VIEW_ONLY) ? 'true' : 'false',
        ));

        return $tpl->get();
    }

    /**
     * Php magic method. calls the $this->getXhtml()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getXhtml();
    }
}
