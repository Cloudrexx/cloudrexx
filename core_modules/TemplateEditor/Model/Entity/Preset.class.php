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
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 */

namespace Cx\Core_Modules\TemplateEditor\Model\Entity;


use Cx\Core_Modules\TemplateEditor\Model\YamlSerializable;

/**
 * Class Preset
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@cloudrexx.com>
 * @package     contrexx
 * @subpackage  core_module_templateeditor
 */
class Preset implements YamlSerializable
{
    /**
     * Name of the preset
     *
     * @var string
     */
    protected $name;

    /**
     * @var Value[]
     */
    protected $optionValues = [];

    /**
     * Create a preset form a raw options array.
     *
     * @param $name
     * @param $options
     *
     * @return Preset
     */
    public static function createFromArray($name,$options)
    {
        $preset = new Preset();
        $preset->setName($name);
        foreach ($options['options'] as $option) {
            $preset->setOption($option['name'], $option['specific']);
        }
        return $preset;
    }

    /**
     * Serialize a class to use in a .yml file.
     * This should return a array which will be serialized by the caller.
     *
     * @return array
     */
    public function yamlSerialize()
    {
        $yml = array('options' => array());
        foreach ($this->optionValues as $option) {
            $yml['options'][] = array('name' => $option->getName(),
               'specific' => $option->getValue());
        }
        return $yml;
    }

    /**
     * Get the name
     *
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name
     *
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Set optionvalues.
     *
     * @param mixed $optionValues
     */
    public function setOptionValues($optionValues)
    {
        $this->optionValues = $optionValues;
    }

    /**
     * Set a option
     *
     * @param $name
     * @param $value
     */
    public function setOption($name, $value)
    {
        $this->optionValues[$name] = new Value($name, $value);
    }

    /**
     * Get a option
     *
     * @param $name
     *
     * @return Value
     */
    public function getOption($name)
    {
        if (!isset($this->optionValues[$name])) {
            return '';
        }
        return $this->optionValues[$name];
    }

    /**
     * Checks if given name is a valid preset name.
     *
     * @param $presetName
     *
     * @return bool
     */
    public static function isValidPresetName($presetName) {
        if (empty($presetName) || !preg_match("/^[a-z0-9]+$/i",$presetName)){
            return false;
        }
        return true;
    }

}
