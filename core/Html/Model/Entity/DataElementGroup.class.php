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
 * DataElement consisting of multiple form elements
 * 
 * DataElements like radiobutton and checkbox make no sense alone.
 * This class allows management of a group of such elements as one
 * DataElement.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_html
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */

namespace Cx\Core\Html\Model\Entity;

/**
 * DataElement consisting of multiple form elements
 * 
 * DataElements like radiobutton and checkbox make no sense alone.
 * This class allows management of a group of such elements as one
 * DataElement.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_html
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */
class DataElementGroup extends DataElement {
    protected static $instanceCount = 0;
    const TYPE_RADIO = 'radio';
    const TYPE_CHECKBOX = 'checkbox';
    
    public function __construct($name, $options, $selectedValue = '', $type = self::TYPE_RADIO, $validator = null) {
        static::$instanceCount++;
        HtmlElement::__construct('div');
        $this->validator = $validator;
        $this->type = $type;
        $this->setAttribute('name', $name);
        $selectedValues = array();
        if ($type == self::TYPE_RADIO) {
            $selectedValues = array($selectedValue);
        } else {
            $selectedValues = explode(',', $selectedValue);
        }
        $i = 0;
        foreach ($options as $key=>$value) {
            $optionId = 'data-element-group-' . static::$instanceCount . '-' . $i;
            $option = new HtmlElement('input');
            $option->setAttribute('type', $this->type);
            $option->setAttribute('name', $name);
            $option->setAttribute('id', $optionId);
            $option->setAttribute('value', $value);
            if (in_array($key, $selectedValues)) {
                $option->setAttribute('checked');
            }
            $label = new HtmlElement('label');
            $label->setAttribute('for', $optionId);
            $label->addChild(new TextElement($value));
            $this->addChild($option);
            $this->addChild($label);
            $i++;
        }
    }
    
    public function getIdentifier() {
        return $this->getAttribute('name');
    }
    
    public function getData() {
        foreach ($this->getChildren() as $child) {
            if ($child->getName() != 'input') {
                continue;
            }
            if ($child->getAttribute('checked') !== null) {
                return $child->getAttribute('value');
            }
        }
        return null;
    }
    
    public function setData($data) {
        foreach ($this->getChildren() as $child) {
            if ($child->getName() != 'input') {
                continue;
            }
            if ($child->getAttribute('value') == $data) {
                $child->setAttribute('checked');
            } else {
                $child->unsetAttribute('checked');
            }
        }
    }
}
