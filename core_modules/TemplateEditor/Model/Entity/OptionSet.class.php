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
class OptionSet extends \Cx\Model\Base\EntityBase
    implements \Cx\Core_Modules\TemplateEditor\Model\Entity\YamlSerializable
{

    /**
     * @var Option[]
     */
    protected $options;

    /**
     * @var Group[]
     */
    protected $groups;

    /**
     * @var $name
     */
    protected $name;

    /**
     * Unmodified data of Options.yml
     *
     * @var array
     */
    protected $data;

    /**
     * The associated theme to the option set.
     *
     * @var \Cx\Core\View\Model\Entity\Theme
     */
    protected $theme;

    /**
     * @var \Cx\Core_Modules\TemplateEditor\Model\Repository\PresetRepository
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
     * @param \Cx\Core\View\Model\Entity\Theme $theme
     * @param       $data
     */
    public function __construct($theme, $data)
    {
        $this->name             = $theme->getFoldername();
        $this->data             = $data;
        // try to initialize the options, so we do not need to load the from
        // $this->data every time
        if (isset($data['DlcInfo']) && isset($data['DlcInfo']['options'])) {
            $this->initializeGroups($data['DlcInfo']['groups']);
            foreach ($data['DlcInfo']['options'] as $optionArray) {
                if (
                    isset($optionArray['type'])
                    && isset($optionArray['name'])
                    && isset($optionArray['specific'])
                ) {
                    $groupName = isset($optionArray['group']) ? $optionArray['group'] : 'others_group';
                    $group = isset($this->groups[$groupName]) ?
                        $this->groups[$groupName] : $this->groups['others_group'];
                    $type = $optionArray['type'];
                    $option = new $type(
                        $optionArray['name'],
                        ($optionArray['translations']) ?
                            $optionArray['translations'] : array(),
                        $optionArray['specific'],
                        $optionArray['type'],
                        $group,
                        ($optionArray['series']) ?
                            $optionArray['series'] : false
                    );
                    $this->setOption($optionArray['name'], $option);
                    $group->addOption($option);
                }
            }
        }
        $this->theme            = $theme;
        $presetStorage =
            new \Cx\Core_Modules\TemplateEditor\Model\Entity\PresetFileStorage(
                $theme->getPath()
            );
        $this->presetRepository =
            new \Cx\Core_Modules\TemplateEditor\Model\Repository\PresetRepository(
                $presetStorage
            );

        if (!isset($data['activePreset'])) {
            $data['activePreset'] = 'Default';
        }
        $activePreset = $data['activePreset'];
        try {
            $this->activePreset = $this->presetRepository->getByName(
                $activePreset
            );
        } catch (
            \Cx\Core_Modules\TemplateEditor\Model\PresetRepositoryException $e
        ) {
            $this->activePreset = $this->presetRepository->getByName('Default');
        } catch (\Symfony\Component\Yaml\ParserException $e) {
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
     * @param \Cx\Core\Html\Sigma $template
     */
    public function renderOptions($template)
    {
        global $_LANGID;
        foreach ($this->getOptionsOrderedByGroups() as $key => $group) {
            foreach ($group as $option) {
                // empty groups should not be parsed
                if (!$group) {
                    continue;
                }
                $subTemplate = $option->renderOptionField();
                $optionName = str_replace( // replace 'Option' so we get the net name
                    'Option',
                    '',
                    end( // last value of the array is the class name
                        explode('\\', $option->getType()) // get array for class namespace
                    )
                );
                $seriesClass = ($option->isSeries()) ? 'series' : '';
                $template->setVariable(array(
                    'TEMPLATEEDITOR_OPTION' => $subTemplate->get(),
                    'TEMPLATEEDITOR_OPTION_TYPE' => strtolower($optionName),
                    'TEMPLATEEDITOR_OPTION_SERIES_CLASS' => $seriesClass,
                ));
                $template->parse('option');
            }
            // find the groupTranslation
            $group = $this->groups[$key];
            // if no translations exists, groupName will be shown
            $groupTranslation = $group->getName();
            $translations = $group->getTranslations();
            if (isset($translations[$_LANGID])) {
                $groupTranslation = $translations[$_LANGID];
            } else if (isset($translations[2])) {
                // if name is not defined in the wished language, try to load
                // it in english
                $groupTranslation = $translations[2];
            }
            $template->setVariable(array(
                'TEMPLATEEDITOR_GROUP_NAME' => $this->groups[$key]->getName(),
                'TEMPLATEEDITOR_GROUP_COLOR' => $this->groups[$key]->getColor(),
                'TEMPLATEEDITOR_GROUP_TRANSLATION' =>
                    contrexx_raw2xhtml($groupTranslation),
            ));
            $template->parse('group');
        }
    }

    /**
     * Call the renderFrontend method on all child options.
     *
     * @param \Cx\Core\Html\Sigma $template
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
     * @return \Cx\Core_Modules\TemplateEditor\Model\Repository\PresetRepository
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
        $specifiedGroups = isset($data['groups']['groups'])
            ? $data['groups']['groups'] : array();
        $this->initializeGroups($specifiedGroups);
        foreach ($data['options'] as $option) {
            $optionType = $option['type'];
            if ($option['series']) {
                // these options are not allowed to be parsed as series
                $optionsWithoutSeries = array(
                    'Cx\Core_Modules\TemplateEditor\Model\Entity\AreaOption',
                    'Cx\Core_Modules\TemplateEditor\Model\Entity\SelectOption',
                );
                if (in_array($optionType, $optionsWithoutSeries)) {
                    \DBG::msg($optionType . ' can not be parsed as series');
                    continue;
                }
                // set type to series, so the fields are parsed as such
                $optionType =
                    'Cx\Core_Modules\TemplateEditor\Model\Entity\SeriesOption';
            }
            if (
                !is_a(
                    $optionType,
                    'Cx\Core_Modules\TemplateEditor\Model\Entity\Option',
                    true
                )
            ) {
                continue;
            }
            if (
                $this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND
                ||
                (
                    $this->cx->getUser()->getFWUserObject()->objUser->login()
                    && isset($_GET['templateEditor'])
                )
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
            $groupName = isset($option['group']) ? $option['group'] : 'others_group';
            $group = isset($this->groups[$groupName]) ?
                $this->groups[$groupName] : $this->groups['others_group'];
            $this->options[$option['name']]
                = new $optionType(
                $option['name'],
                $option['translation'],
                $option['specific'],
                $option['type'],
                $group,
                $option['series']
            );
            $group->addOption($this->options[$option['name']]);
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

    /**
     * Get the options ordered by the groups
     *
     * @access public
     * @return array the options groupedBy Groups
     */
    public function getOptionsOrderedByGroups() {
        $groups = array();
        foreach ($this->groups as $group) {
            $groups[$group->getName()] = $group->getOptions();
        }
        return $groups;
    }

    /**
     * Initialize the groups from Groups.yml
     *
     * @access protected
     * @param array $groups groups from Groups.yml
     */
    protected function initializeGroups($groups) {
        global $_LANGID, $_ARRAYLANG;
        if (!isset($groups)) {
            $groups = array();
        }
        if (empty($groups)) {
            $placeholder = 'TXT_CORE_MODULE_TEMPLATEEDITOR_OPTIONS';
        } else {
            $placeholder = 'TXT_CORE_MODULE_TEMPLATEEDITOR_FURTHER_OPTIONS';
        }

        $translation = array(
            $_LANGID => $_ARRAYLANG[$placeholder]
        );
        $groups[] = array(
            "name" => "others_group",
            "color" => '#fff',
            "translation" => $translation,
        );
        foreach ($groups as $key => $group) {
            $this->groups[$group['name']] = new Group(
                $group['name'],
                $group['color'],
                $group['translation'],
                $key
            );
        }
    }
}
