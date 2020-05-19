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
 *
 */

namespace Cx\Core\Html\Model\Entity;

/**
 *
 */
class DataElement extends HtmlElement {
    const TYPE_INPUT = 'input';
    const TYPE_SELECT = 'select';
    protected $validator;
    protected $type;


    public function __construct($name, $value = '', $type = self::TYPE_INPUT, $validator = null, $validData = array()) {
        parent::__construct($type);
        $this->validator = $validator;
        $this->type = $type;
        $this->setAttribute('name', $name);
        switch ($type) {
            case self::TYPE_INPUT:
                $this->setAttribute('value', $value);
            break;
            case self::TYPE_SELECT:
                foreach ($validData as $key=>$val) {
                    $option = new \Cx\Core\Html\Model\Entity\HtmlElement('option');
                    $option->setAttribute('value', $key);
                    $option->addChild(
                        new \Cx\Core\Html\Model\Entity\TextElement($val)
                    );
                    // Numeric array keys are automatically converted to int by
                    // PHP http://ch1.php.net/manual/en/language.types.array.php#example-58
                    if (is_numeric($value) || is_bool($value)) {
                        $value = (int)$value;
                    }
                    if ($key === $value) {
                        $option->setAttribute('selected');
                    }
                    $this->addChild($option);
                }
            break;
        }
    }

    public function isValid() {
        return $this->getValidator()->isValid($this->getData());
    }

    public function getValidator() {
        if (!$this->validator) {
            return new \Cx\Core\Validate\Model\Entity\DummyValidator();
        }
        return $this->validator;
    }

    public function setValidator($validator) {
        $this->validator = $validator;
    }

    public function getIdentifier() {
        switch ($this->type) {
            case self::TYPE_INPUT:
            case self::TYPE_SELECT:
                return $this->getAttribute('name');
                break;
            default:
                return null;
                break;
        }
    }

    public function getData() {
        switch ($this->type) {
            case self::TYPE_INPUT:
                return $this->getAttribute('value');
                break;
            default:
                return null;
                break;
        }
    }

    public function setData($data) {
        switch ($this->type) {
            case self::TYPE_INPUT:
                $this->setAttribute('value', $data);
                break;
            default:
                // error handling
                break;
        }
    }

    public function render() {
        $this->setAttribute(
            'onkeyup',
            $this->getAttribute('onkeyup') . ';' .
                $this->getValidator()->getJavaScriptCode()
        );
        return parent::render();
    }
}
