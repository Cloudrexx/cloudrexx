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
 * Class BackendController
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */

namespace Cx\Core_Modules\TemplateEditor\Controller;


use Cx\Core\Html\Sigma;
use Cx\Core\View\Model\Entity\Theme;
use Cx\Core_Modules\TemplateEditor\Model\Entity\OptionSet;
use Cx\Core_Modules\TemplateEditor\Model\Entity\Preset;
use Cx\Core_Modules\TemplateEditor\Model\OptionSetFileStorage;
use Cx\Core_Modules\TemplateEditor\Model\PresetFileStorage;
use Cx\Core_Modules\TemplateEditor\Model\PresetRepositoryException;
use Cx\Core_Modules\TemplateEditor\Model\Repository\OptionSetRepository;
use Cx\Core\Core\Model\Entity\SystemComponentBackendController;
use Cx\Core\Routing\Url;
use Cx\Core\View\Model\Repository\ThemeRepository;
use Cx\Core_Modules\TemplateEditor\Model\Repository\PresetRepository;

/**
 * Class BackendController
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class BackendController extends SystemComponentBackendController
{
    /**
     * @var ThemeRepository
     */
    protected $themeRepository;
    /**
     * @var OptionSetRepository
     */
    protected $themeOptionRepository;

    /**
     * @var OptionSet
     */
    protected $themeOptions;

    /**
     * @var Theme
     */
    protected $theme;

    /**
     * @var PresetRepository
     */
    protected $presetRepository;

    /**
     * Returns a list of available commands (?act=XY)
     *
     * @return array List of acts
     */
    public function getCommands()
    {
        return array();
    }

    /**
     * This renders the backend overview.
     *
     * @param \Cx\Core\Html\Sigma $template Template for current CMD
     * @param array               $cmd      CMD separated by slashes
     */
    public function parsePage(\Cx\Core\Html\Sigma $template, array $cmd, &$isSingle = false)
    {
        \Permission::checkAccess(\Cx\Core\ViewManager\Controller\ViewManager::TEMPLATE_EDITOR_ACCESS_ID, 'static');
        $fileStorage                 = new OptionSetFileStorage(
            $this->cx->getWebsiteThemesPath()
        );
        $themeOptionRepository       = new OptionSetRepository($fileStorage);
        $this->themeOptionRepository = $themeOptionRepository;
        $this->themeRepository       = new ThemeRepository();
        $themeID                     = isset($_GET['tid']) ? $_GET['tid'] : 1;
        $this->theme                 = $this->themeRepository->findById(
            $themeID
        );

        if (!$_SESSION['TemplateEditor']) {
            $_SESSION['TemplateEditor'] = array();
        }
        if (!$_SESSION['TemplateEditor'][$this->theme->getId()]) {
            $_SESSION['TemplateEditor'][$this->theme->getId()] = array();
        }
        if (isset($_GET['preset'])
            && Preset::isValidPresetName(
                $_GET['preset']
            )
        ) {
            if ($_SESSION['TemplateEditor'][$this->theme->getId(
                )]['activePreset'] != $_GET['preset']
            ) {
                // If the preset has changed remove all saved options
                $_SESSION['TemplateEditor'][$this->theme->getId()] = array();
            }
            $_SESSION['TemplateEditor'][$this->theme->getId()]['activePreset']
                = isset($_GET['preset']) ? $_GET['preset'] : 'Default';
        }


        $this->presetRepository = new PresetRepository(
            new PresetFileStorage(
                $this->cx->getWebsiteThemesPath() . '/'
                . $this->theme->getFoldername()
            )
        );
        try {
            $this->themeOptions = $this->themeOptionRepository->get(
                $this->theme
            );
            // If user opens editor use active preset as active preset.
            if (
                !isset($_SESSION['TemplateEditor'][$this->theme->getId()]
                    ['activePreset'])
                || !isset($_GET['preset'])) {
                $_SESSION['TemplateEditor'][$this->theme->getId()]['activePreset']
                    = $this->themeOptions->getActivePreset()->getName();
            }
            try {
                $this->themeOptions->applyPreset(
                    $this->presetRepository->getByName(
                        $_SESSION['TemplateEditor']
                        [$this->theme->getId()]
                        ['activePreset']
                    )
                );
            } catch (PresetRepositoryException $e) {
                // If something fails fallback to the default preset.
                $_SESSION['TemplateEditor']
                    [$this->theme->getId()]['activePreset'] = 'Default';
                $this->themeOptions->applyPreset(
                    $this->presetRepository->getByName(
                        'Default'
                    )
                );
            }
        } catch (\Symfony\Component\Yaml\Exception\ParseException $e) {

        }

        $this->showOverview($template);
    }

    /**
     * Creates the main overview for this component.
     *
     * @param $template
     *
     * @throws \Cx\Core\Routing\UrlException
     */
    public function showOverview(Sigma $template)
    {
        global $_ARRAYLANG, $_CONFIG;
        \JS::registerJS('core_modules/TemplateEditor/View/Script/spectrum.js');
        \JS::activate('intro.js');
        $template->loadTemplateFile(
            $this->cx->getCodeBaseCoreModulePath()
            . '/TemplateEditor/View/Template/Backend/Default.html'
        );
        /**
         * @var $themes Theme[]
         */
        $themes = $this->themeRepository->findAll();
        foreach ($themes as $theme) {
            $template->setVariable(
                array(
                    'TEMPLATEEDITOR_LAYOUT_NAME' => $theme->getThemesname(),
                    'TEMPLATEEDITOR_LAYOUT_ID' => $theme->getId()
                )
            );
            if ($this->theme->getId() == $theme->getId()) {
                $template->setVariable(
                    array(
                        'TEMPLATEEDITOR_LAYOUT_ACTIVE' => 'selected'
                    )
                );
            }
            $template->parse('layouts');
        }
        if ($this->themeOptions) {
            $presets = $this->themeOptions->getPresetRepository()->findAll();
            foreach ($presets as $preset) {
                $template->setVariable(
                    array(
                        'TEMPLATEEDITOR_PRESET_NAME' => $this->themeOptions->getActivePreset(
                        )->getName() == $preset ? $preset . ' ('
                            . $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_PRESET_ACTIVE']
                            . ')' : $preset,
                        'TEMPLATEEDITOR_PRESET_ID' => $preset
                    )
                );
                if ($_SESSION['TemplateEditor'][$this->theme->getId()]
                    ['activePreset'] == $preset
                ) {
                    $template->setVariable(
                        array(
                            'TEMPLATEEDITOR_PRESET_ACTIVE' => 'selected'
                        )
                    );
                }
                $template->parse('presets');
            }
            if ($_SESSION['TemplateEditor'][$this->theme->getId(
                )]['activePreset']
                == $this->themeOptions->getActivePreset()->getName()
            ) {
                $template->setVariable(
                    array(
                        'TEMPLATEDITOR_PRESET_IS_ALREADY_ACTIVE' => 'disabled'
                    )
                );

                $template->setVariable(
                    array(
                        'TXT_CORE_MODULE_TEMPLATEEDITOR_REMOVE_PRESET_TEXT_IS_ACTIVE'
                        => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_REMOVE_PRESET_TEXT_IS_ACTIVE']
                    )
                );
                $template->show('presetTextActive');
            }
            if ($_SESSION['TemplateEditor'][$this->theme->getId(
                )]['activePreset']
                == 'Default'
            ) {
                $template->setVariable(
                    array(
                        'TEMPLATEDITOR_PRESET_IS_DEFAULT' => 'disabled'
                    )
                );

            }
            foreach ($presets as $preset) {
                $template->setVariable(
                    array(
                        'TEMPLATEEDITOR_PRESET_FOR_PRESETS_NAME' => $preset,
                        'TEMPLATEEDITOR_PRESET_FOR_PRESETS_ID' => $preset
                    )
                );
                $template->parse('presetsForPresets');
            }

            $this->themeOptions->renderOptions($template);


            if ($this->themeOptions->getOptionCount() != 0) {
                $template->parse('presetBlock');
                $template->setVariable('TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE',  $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE']);
                $template->parse('save_button');
            }
        } else {
            $template->setVariable(
                array(
                    'TEMPLATEOPTION_NO_OPTIONS_TEXT' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_NO_OPTIONS_HELP'],
                    'TEMPLATEOPTION_NO_OPTIONS_LINKNAME' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_NO_OPTIONS_LINKNAME']
                )
            );
            $template->parse('no_options');
        }
        $template->setVariable(
            array(
                'TEMPLATEEDITOR_IFRAME_URL' => Url::fromModuleAndCmd(
                    'home', '', null,
                    array(
                        'preview' => $this->theme->getId(),
                        'templateEditor' => 1
                    )
                ),
                'TEMPLATEEDITOR_BACKURL' => Url::fromBackend('ViewManager')
            )
        );
        $template->setGlobalVariable($_ARRAYLANG);
        \ContrexxJavascript::getInstance()->setVariable(
            array(
                'newPresetTemplate' => '',
                'TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_CANCEL' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_CANCEL'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE_CONTENT' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE_CONTENT'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE_TITLE' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_SAVE_TITLE'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_YES' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_YES'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_NO' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_NO'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_ADD_PRESET' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_ADD_PRESET'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_REMOVE_PRESET_TEXT' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_REMOVE_PRESET_TEXT'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_ACTIVATE_PRESET_TITLE' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_ACTIVATE_PRESET_TITLE'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_ADD_PRESET_TITLE' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_ADD_PRESET_TITLE'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_LAYOUT_OPTION' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_LAYOUT_OPTION'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_OPTION' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_OPTION'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_ACTIVATE' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_ACTIVATE'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_ADD' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_ADD'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_RESET' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_RESET'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_VIEW_OPTION' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_VIEW_OPTION'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_VIEW_OPTION_LIST' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_VIEW_OPTION_LIST'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PREVIEW' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PREVIEW'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_SAVE' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_SAVE'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_NEXT' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_NEXT'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_BACK' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_BACK'],
                'TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_STOP' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_STOP'],
                'themeid' => $this->theme->getId(),
                'iframeUrl' => Url::fromModuleAndCmd(
                    'home', '', null,
                    array(
                        'preview' => $this->theme->getId(),
                        'templateEditor' => 1
                    )
                )->toString(),
                'domainUrl' => $_CONFIG['domainUrl']
            ),
            'TemplateEditor'
        );
    }

}
