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
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */

namespace Cx\Core_Modules\TemplateEditor\Controller;

use Cx\Core\Core\Controller\Cx;
use Cx\Core\Core\Model\Entity\SystemComponentController;
use Cx\Core\View\Model\Repository\ThemeRepository;
use Cx\Core_Modules\TemplateEditor\Model\OptionSetFileStorage;
use Cx\Core_Modules\TemplateEditor\Model\PresetRepositoryException;
use Cx\Core_Modules\TemplateEditor\Model\Repository\OptionSetRepository;

/**
 * Class BackendController
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class ComponentController extends SystemComponentController
{

    /**
     * Returns all Controller class names for this component (except this)
     *
     * Be sure to return all your controller classes if you add your own
     * @return array List of Controller class names (without namespace)
     */
    public function getControllerClasses() {
        return array('Backend','Json');
    }

    /**
     * Do something before main template gets parsed
     *
     * This creates the frontend placeholders for the preview and the normal view.
     *
     * USE CAREFULLY, DO NOT DO ANYTHING COSTLY HERE!
     * CALCULATE YOUR STUFF AS LATE AS POSSIBLE
     *
     * @param \Cx\Core\Html\Sigma $template The main template
     */
    public function preFinalize(\Cx\Core\Html\Sigma $template) {
        if ($this->cx->getMode() != Cx::MODE_FRONTEND) {
            return;
        }
        try {
            $fileStorage = new OptionSetFileStorage(
                $this->cx->getWebsiteThemesPath()
            );
            $themeOptionRepository = new OptionSetRepository($fileStorage);
            $themeRepository = new ThemeRepository();
            $themeID = isset($_GET['preview']) ? $_GET['preview']
                : null;
            // load preview theme or page's custom theme
            $theme = $themeID ? $themeRepository->findById(
                (int)$themeID
            ) : $themeRepository->findById($this->cx->getPage()->getSkin());
            // fallback: load default theme of active language
            if (!$theme) {
                $theme = $themeRepository->getDefaultTheme(\Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB, FRONTEND_LANG_ID);
            }
            // final fallback: try to load any existing default theme (independent of the language)
            if (!$theme) {
                $theme = $themeRepository->getDefaultTheme(\Cx\Core\View\Model\Entity\Theme::THEME_TYPE_WEB);
            }
            $themeOptions = $themeOptionRepository->get(
                $theme
            );

            if (isset($_GET['templateEditor'])) {
                $themeOptions->applyPreset(
                    $themeOptions->getPresetRepository()->getByName(
                        $_SESSION['TemplateEditor'][$themeID]['activePreset']
                    )
                );
            }
            $themeOptions->renderTheme($template);
        } catch (PresetRepositoryException $e) {

        }
        catch (\Symfony\Component\Yaml\Exception\ParseException $e) {

        }
    }

    /**
     * @return array
     */
    public function getControllersAccessableByJson() {
        return array('JsonController');
    }

    /**
     * {@inheritDoc}
     */
    public function postContentLoad(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        global $_ARRAYLANG;

        if ($this->cx->getMode() != Cx::MODE_BACKEND) {
            return;
        }
        if ($page->getModule() != $this->getName()) {
            return;
        }

        $this->getComponent('View')->addIntroSteps(
            array(
                array(
                    'element' => '.option.layout',
                    'intro' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_LAYOUT_OPTION'],
                ),
                array(
                    'element' => '.option.preset',
                    'intro' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_OPTION'],
                ),
                array(
                    'element' => '.activate-preset',
                    'intro' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_ACTIVATE'],
                ),
                array(
                    'element' => '.add-preset',
                    'intro' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_ADD'],
                ),
                array(
                    'element' => '.reset-preset',
                    'intro' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PRESET_RESET'],
                ),
                array(
                    'element' => '.option.view',
                    'intro' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_VIEW_OPTION'],
                ),
                array(
                    'element' => '.option-list > .option',
                    'intro' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_VIEW_OPTION_LIST'],
                ),
                array(
                    'element' => '#preview-template-editor',
                    'intro' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_PREVIEW'],
                ),
                array(
                    'element' => 'button.save',
                    'intro' => $_ARRAYLANG['TXT_CORE_MODULE_TEMPLATEEDITOR_INTRO_SAVE'],
                )
            ),
            'TemplateEditor'
        );
    }
}
