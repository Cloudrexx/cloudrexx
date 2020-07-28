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


namespace Cx\Core_Modules\TemplateEditor\Model\Entity;

use Cx\Core\Core\Controller\Cx;
use Cx\Core\View\Model\Entity\Theme;
use Cx\Core_Modules\TemplateEditor\Model\PresetRepositoryException;
use Cx\Core_Modules\TemplateEditor\Model\Repository\PresetRepository;
use Cx\Core_Modules\TemplateEditor\Model\YamlSerializable;
use Cx\Core\Html\Sigma;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class ThemeOptionNotFoundException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class ThemeOptionNotFoundException extends \Exception
{
}

/**
 * Class ThemeOptions
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class OptionSet extends \Cx\Model\Base\EntityBase implements YamlSerializable
{

    /**
     * @var Option[]
     */
    protected $options;

    /**
     * @var $name
     */
    protected $name;

    /**
     * Unmodified data of options.yml
     *
     * @var array
     */
    protected $data;

    /**
     * The associated theme to the option set.
     *
     * @var Theme
     */
    protected $theme;

    /**
     * @var PresetRepository
     */
    protected $presetRepository;

    /**
     * Active preset for frontend
     *
     * @var Preset
     */
    protected $activePreset;

    /**
     * @var Preset
     */
    protected $appliedPreset;

    /**
     * @param Theme $theme
     * @param       $data
     */
    public function __construct($theme, $data)
    {
        $this->name             = $theme->getFoldername();
        $this->data             = $data;
        $this->theme            = $theme;
        $presetStorage
                                = new \Cx\Core_Modules\TemplateEditor\Model\PresetFileStorage(
            $this->cx->getWebsiteThemesPath() . '/' . $theme->getFoldername()
        );
        $this->presetRepository = new PresetRepository($presetStorage);

        if (!isset($data['activePreset'])) {
            $data['activePreset'] = 'Default';
        }
        $activePreset = $data['activePreset'];
        try {
            $this->activePreset = $this->presetRepository->getByName(
                $activePreset
            );
        } catch (PresetRepositoryException $e) {
            $this->activePreset = $this->presetRepository->getByName('Default');
        } catch (ParseException $e) {
            $this->activePreset = $this->presetRepository->getByName('Default');
        }

        $this->applyPreset($this->activePreset);
    }


    /**
     * Pass the changes to the option directly to handle it.
     * It returns the data which should be saved into the session.
     *
     * @param $name
     * @param $data
     *
     * @return array
     * @throws ThemeOptionNotFoundException
     */
    public function handleChanges($name, $data)
    {
        if (!array_key_exists($name, $this->options)) {
            throw new ThemeOptionNotFoundException();
        }
        return $this->options[$name]->handleChange($data);
    }

    /**
     * Call the renderBackend method on all child options.
     *
     * @param Sigma $template
     */
    public function renderOptions($template)
    {
        foreach ($this->options as $option) {
            $option->renderOptionField($template);
        }
    }

    /**
     * Call the renderFrontend method on all child options.
     *
     * @param Sigma $template
     */
    public function renderTheme($template)
    {
        foreach ($this->options as $option) {
            $option->renderTheme($template);
            \ContrexxJavascript::getInstance()->setVariable(
                    $option->getName(),
                    $option->getValue(),
                    'TemplateEditor'
            );
        }
    }

    /**
     * Serialize a class to use in a .yml file.
     * This should return a array which will be serialized by the caller.
     *
     * @return array
     */
    public function yamlSerialize()
    {
        $yaml    = array('activePreset' => $this->activePreset->getName());
        $options = array();
        foreach ($this->options as $option) {
            $options[] = $option->yamlSerialize();
        }
        $yaml['options'] = $options;
        return $yaml;
    }

    /**
     * Get a option
     *
     * @param $name
     *
     * @return Option
     */
    public function getOption($name)
    {
        return array_key_exists($name, $this->options) ? $this->options[$name]
            : null;
    }

    /**
     * Set a option
     *
     * @param $name
     * @param $value
     */
    public function setOption($name, $value)
    {
        $this->options[$name] = $value;
    }

    /**
     * Get a name
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set a name
     *
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get option count
     *
     * @return int
     */
    public function getOptionCount()
    {
        return count($this->options);
    }

    /**
     * Get list of presets
     *
     * @return array
     */
    public function getPresets()
    {
        return $this->presetRepository->findAll();
    }

    /**
     * Get the active preset
     *
     * @return Preset
     */
    public function getActivePreset()
    {
        return $this->activePreset;
    }

    /**
     * Get the presetrepository
     *
     * @return PresetRepository
     */
    public function getPresetRepository()
    {
        return $this->presetRepository;
    }

    /**
     * Get the changed preset.
     *
     * @return Preset
     */
    public function getChangedPreset()
    {
        $preset = $this->appliedPreset;
        foreach ($this->options as $name => $option) {
            $preset->setOption($name, $option->getValue());
        }
        return $preset;
    }

    /**
     * Apply a preset
     *
     * @param Preset $preset
     */
    public function applyPreset(Preset $preset)
    {
        $this->appliedPreset = $preset;
        /* Saving data so later on we can reuse it
           without worrying about older applied presets */
        $data = $this->data;
        foreach ($data['options'] as &$emptyOption) {
            if ($presetOption = $preset->getOption($emptyOption['name'])) {
                if (!isset($emptyOption['specific']) || !is_array($emptyOption['specific'])) {
                    $emptyOption['specific'] = array();
                }
                $emptyOption['specific'] = array_merge(
                    $emptyOption['specific'], $presetOption->getValue()
                );
            }
        }

//        $this->options = array();
        foreach ($data['options'] as $option) {
            $optionReflection = new \ReflectionClass($option['type']);
            if ($optionReflection->isSubclassOf('Cx\Core_Modules\TemplateEditor\Model\Entity\Option')
            ) {
                if ($this->cx->getMode() == Cx::MODE_BACKEND
                    || (($this->cx->getUser()->getFWUserObject(
                        )->objUser->login())
                        && isset($_GET['templateEditor']))
                ) {
                    if (isset($_SESSION['TemplateEditor'][$this->theme->getId(
                        )][$option['name']])) {
                        $option['specific'] = array_merge(
                            $option['specific'],
                            $_SESSION['TemplateEditor']
                            [$this->theme->getId()]
                            [$option['name']]->toArray()
                        );
                    }
                }
                $this->options[$option['name']]
                    = $optionReflection->newInstance(
                    $option['name'], $option['translation'], $option['specific']
                );
            }
        }
    }

    /**
     * Set the active preset.
     *
     * @param Preset $preset
     */
    public function setActivePreset(Preset $preset)
    {
        $this->activePreset = $preset;
    }
}
