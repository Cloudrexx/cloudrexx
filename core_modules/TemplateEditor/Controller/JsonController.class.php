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


namespace Cx\Core_Modules\TemplateEditor\Controller;

use Cx\Core\Core\Controller\Cx;
use Cx\Core\Json\JsonAdapter;
use Cx\Core\View\Model\Repository\ThemeRepository;
use Cx\Core_Modules\TemplateEditor\Model\Entity\Preset;
use Cx\Core_Modules\TemplateEditor\Model\OptionSetFileStorage;
use Cx\Core_Modules\TemplateEditor\Model\Repository\OptionSetRepository;

/**
 * Class JsonController
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class JsonController extends \Cx\Core\Core\Model\Entity\Controller implements JsonAdapter
{


    /**
     * Returns the internal name used as identifier for this adapter
     *
     * @return String Name of this adapter
     */
    public function getName()
    {
        return 'TemplateEditor';
    }

    /**
     * Returns an array of method names accessable from a JSON request
     *
     * @return array List of method names
     */
    public function getAccessableMethods()
    {
        if (!\Permission::checkAccess(\Cx\Core\ViewManager\Controller\ViewManager::TEMPLATE_EDITOR_ACCESS_ID, 'static', true)) {
            return array();
        }
        return array(
            'updateOption', 'saveOptions', 'activatePreset', 'addPreset',
            'removePreset', 'resetPreset'
        );
    }

    /**
     * Returns all messages as string
     *
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString()
    {
        return '';
    }

    /**
     * Returns default permission as object
     *
     * @return Object
     */
    public function getDefaultPermissions()
    {
        return null;
    }


    /**
     * Save the options to the component.yml file.
     *
     * @param array $params List of get and post parameters which were sent to
     *                      the json adapter.
     */
    public function saveOptions($params)
    {
        $themeID         = isset($params['get']['tid']) ? $params['get']['tid'] : 1;
        $themeRepository = new ThemeRepository();
        $theme           = $themeRepository->findById($themeID);
        if (!isset($_SESSION['TemplateEditor'])) {
            $_SESSION['TemplateEditor'] = array();
        }
        if (!isset($_SESSION['TemplateEditor'][$themeID])) {
            $_SESSION['TemplateEditor'][$themeID] = array();
        }
        $fileStorage           = new OptionSetFileStorage(
            $this->cx->getWebsiteThemesPath()
        );
        $themeOptionRepository = new OptionSetRepository($fileStorage);

        $themeOptions     = $themeOptionRepository->get(
            $theme
        );
        $themeOptions->applyPreset(
            $themeOptions->getPresetRepository()->getByName(
                $_SESSION['TemplateEditor'][$themeID]['activePreset']
            )
        );
        $presetRepository = $themeOptions->getPresetRepository();
        $preset           = $themeOptions->getChangedPreset();
        $presetRepository->save($preset);
        $this->clearCache();
    }

    /**
     * Update the value of a option for a specific template.
     *
     * @param array $params List of get and post parameters which were sent to
     *                      the json adapter.
     *
     * @return array Modified Data
     */
    public function updateOption($params)
    {
        global $_ARRAYLANG;

        \Env::get('init')->loadLanguageData('TemplateEditor');
        $themeID         = isset($params['get']['tid']) ? $params['get']['tid'] : 1;
        $themeRepository = new ThemeRepository();
        $theme           = $themeRepository->findById($themeID);
        if (!isset($_SESSION['TemplateEditor'])) {
            $_SESSION['TemplateEditor'] = array();
        }
        if (!isset($_SESSION['TemplateEditor'][$themeID])) {
            $_SESSION['TemplateEditor'][$themeID] = array();
        }
        $fileStorage           = new OptionSetFileStorage(
            $this->cx->getWebsiteThemesPath()
        );
        $themeOptionRepository = new OptionSetRepository($fileStorage);

        $themeOptions = $themeOptionRepository->get(
            $theme
        );
        $themeOptions->applyPreset(
            $themeOptions->getPresetRepository()->getByName(
                $_SESSION['TemplateEditor'][$themeID]['activePreset']
            )
        );
        if (empty($params['post']['optionName'])
            && !preg_match(
                '/^[a-z_]+$/i', $params['post']['optionName']
            )
        ) {
            throw new \LogicException(
                'This method needs a valid name to work.'
            );
        }
        if (empty($params['post']['optionData'])) {
            throw new \LogicException(
                $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_VALUE_EMPTY']
            );
        }
        $data = $themeOptions->handleChanges(
            $params['post']['optionName'], $params['post']['optionData']
        );
        $_SESSION['TemplateEditor'][$themeID][$params['post']['optionName']]
              = $data;
        return $data;
    }

    /**
     * Activate a preset
     *
     * @param array $params List of get and post parameters which were sent to
     *                      the json adapter.
     */
    public function activatePreset($params)
    {
        if (!Preset::isValidPresetName( $params['post']['preset'])) {
            return;
        }
        $presetName            =  $params['post']['preset'];
        $themeID               = isset($params['post']['tid']) ?
            intval($params['post']['tid']) : 1;
        $themeRepository       = new ThemeRepository();
        $theme                 = $themeRepository->findById($themeID);
        $fileStorage           = new OptionSetFileStorage(
            $this->cx->getWebsiteThemesPath()
        );
        $themeOptionRepository = new OptionSetRepository($fileStorage);

        $themeOptions = $themeOptionRepository->get(
            $theme
        );
        $preset       = $themeOptions->getPresetRepository()->getByName(
            $presetName
        );
        $themeOptions->setActivePreset($preset);
        $themeOptionRepository->save($themeOptions);
    }

    /**
     * Add a new preset
     *
     * @param array $params List of get and post parameters which were sent to
     *                      the json adapter.
     *
     * @return array Preset name
     */
    public function addPreset($params)
    {
        global $_ARRAYLANG;

        \Env::get('init')->loadLanguageData('TemplateEditor');
        $presetName = $params['post']['preset'];
        if (!Preset::isValidPresetName($presetName)) {
            throw new \LogicException(
                $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_NEW_PRESET_TEXT_NOT_ALLOWED_CHARACTERS']
            );
        }

        $presetPresetName = 'Default';
        if (isset($params['post']['presetpreset']) && Preset::isValidPresetName($params['post']['presetpreset'])) {
            $presetPresetName =  $params['post']['presetpreset'];
        }
        $themeID               = isset($params['post']['tid']) ?
            intval($params['post']['tid']) : 1;
        $themeRepository       = new ThemeRepository();
        $theme                 = $themeRepository->findById($themeID);
        $fileStorage           = new OptionSetFileStorage(
            $this->cx->getWebsiteThemesPath()
        );
        $themeOptionRepository = new OptionSetRepository($fileStorage);
        $optionSet = $themeOptionRepository->get(
            $theme
        );
        $preset = $optionSet->getPresetRepository()->getByName(
            $presetPresetName
        );
        $preset->setName($presetName);
        $_SESSION['TemplateEditor'][$themeID]['activePreset'] = $presetName;
        $optionSet->getPresetRepository()->save($preset);
        return array('preset' => $presetName);
    }

    /**
     * Remove a preset
     *
     * @param array $params List of get and post parameters which were sent to
     *                      the json adapter.
     */
    public function removePreset($params)
    {
        global $_ARRAYLANG;

        \Env::get('init')->loadLanguageData('TemplateEditor');

        if (!Preset::isValidPresetName($params['post']['preset'])) {
            return;
        }

        $presetName = $params['post']['preset'];
        /**
         * Default shouldn't be deletable
         */
        if ($presetName == 'Default') {
            throw new \LogicException($_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_REMOVE_PRESET_DEFAULT_WARNING']);
        }
        $themeID               = isset($params['post']['tid']) ?
            intval($params['post']['tid']) : 1;
        $themeRepository       = new ThemeRepository();
        $theme                 = $themeRepository->findById($themeID);
        $fileStorage           = new OptionSetFileStorage(
            $this->cx->getWebsiteThemesPath()
        );
        $themeOptionRepository = new OptionSetRepository($fileStorage);
        $themeOptions = $themeOptionRepository->get(
            $theme
        );
        $preset = $themeOptions->getPresetRepository()->getByName(
            $presetName
        );
        if ($themeOptions->getActivePreset()->getName() == $preset->getName()) {
            $themeOptions->setActivePreset($themeOptions->getPresetRepository()->getByName(
                'Default'
            ));
            $themeOptionRepository->save($themeOptions);
        }
        $themeOptions->getPresetRepository()->remove($preset);
    }

    /**
     * Reset a preset
     *
     * @param array $params List of get and post parameters which were sent to
     *                      the json adapter.
     */
    public function resetPreset($params) {
        $themeID               = isset($params['post']['tid']) ?
            intval($params['post']['tid']) : 1;
        $activePreset = $_SESSION['TemplateEditor'][$themeID]['activePreset'];
        $_SESSION['TemplateEditor'][$themeID] = array();
        $_SESSION['TemplateEditor'][$themeID]['activePreset'] = $activePreset;
    }
    
    protected function clearCache() {
        $this->getComponent('Cache')->clearCache();
    }
}
