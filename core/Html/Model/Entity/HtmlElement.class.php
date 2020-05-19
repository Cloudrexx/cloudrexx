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
class HtmlElement extends \Cx\Model\Base\EntityBase {

    /**
     * @var array List of element names without content
     * https://www.w3.org/TR/html5/syntax.html#void-elements
     */
    protected static $contentModelVoidTags = array(
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
        'track',
        'wbr',
    );

    private $name;
    private $classes = array();
    private $attributes = array();
    private $children = array();
    private $output = null;
    private $allowDirectClose = false;

    public function __construct($elementName) {
        $this->setName($elementName);
    }

    public function allowDirectClose($allow = null) {
        if ($allow === null) {
            // These tags are not allowed to have content
            if (in_array($this->name, static::$contentModelVoidTags)) {
                return true;
            }
            return $this->allowDirectClose;
        }
        $this->allowDirectClose = $allow;
    }

    public function getName() {
        return $this->name;
    }

    protected function setName($elementName) {
        $this->output = null;
        $this->name = $elementName;
    }

    /**
     * Sets an attribute
     *
     * If value is not specified, value will be the same as $name (for cases
     * like checked="checked"). If you want to unset an attribute, use
     * unsetAttribute() or setAttributes(..., true)
     * @param string $name Name of the attribute
     * @param string $value (optional) Value of the attribute
     */
    public function setAttribute($name, $value = null) {
        if ($name == 'class') {
            return $this->setClass($value);
        }
        if ($value === null) {
            $value = $name;
        }
        $this->output = null;
        $this->attributes[$name] = $value;
    }

    /**
     * Unsets an attribute
     * @param string $name Name of the attribute
     */
    public function unsetAttribute($name) {
        if (!isset($this->attributes[$name])) {
            return;
        }
        unset($this->attributes[$name]);
    }

    /**
     * Sets a list of attributes
     *
     * Provide an array with attribute name as key and attribute value as value
     * (see setAttribute() for possibilities).
     * @param array $attributes List of attributes to set
     * @param boolean $removeOthers Wheter to remove all not specified attributes or not
     */
    public function setAttributes($attributes, $removeOthers = false) {
        $presentAttributes = $this->attributes;
        foreach ($attributes as $name=>$value) {
            $this->setAttribute($name, $value);
            if (isset($presentAttributes[$name])) {
                unset($presentAttributes[$name]);
            }
        }
        $this->output = null;
        if (!$removeOthers) {
            return;
        }
        foreach ($presentAttributes as $name=>$value) {
            $this->unsetAttribute($name);
        }
    }

    public function getAttribute($name) {
        if (!isset($this->attributes[$name])) {
            return null;
        }
        return $this->attributes[$name];
    }

    public function getAttributes() {
        return $this->attributes;
    }

    public function setClass($string) {
        if (!is_array($string)) {
            $string = explode(' ', $string);
        }
        $this->classes = $string;
    }

    public function getClasses(&$classes = array()) {
        $classes = $this->classes;
        return implode(' ', $this->classes);
    }

    public function hasClass($className) {
        return in_array($className, $this->classes);
    }

    public function addClass($className) {
        if ($this->hasClass($className)) {
            return;
        }
        $this->classes[] = $className;
    }

    public function removeClass($className) {
        $key = array_search($className, $this->classes);
        if ($key !== false) {
            unset($this->classes[$key]);
        }
    }

    public function getChildren() {
        return $this->children;
    }

    public function addChild(HtmlElement $element, HtmlElement $reference = null, $before = false) {
        $this->output = null;
        if (!$reference) {
            $this->children[] = $element;
            return true;
        }

        $key = array_search($reference, $this->children);
        if ($key === false) {
            return false;
        }

        if (!$before) {
            $key++;
        }
        array_splice($this->children, $key, 0, array($element));
        return true;
    }

    public function addChildren(array $elements, HtmlElement $reference = null, $before = false) {
        $this->output = null;
        if (!$reference) {
            $this->children += $elements;
            return true;
        }
        foreach ($elements as $element) {
            if (!$this->addChild($element, $reference, $before)) {
                return false;
            }
            $before = false;
            $reference = $element;
        }
        return true;
    }

    /* addChildAfter, removeChild, getNthChild */

    public function render() {
        if ($this->output) {
            return $this->output;
        }
        $template = new \Cx\Core\Html\Sigma(\Env::get('cx')->getCodeBaseCorePath() . '/Html/View/Template/Generic');
        $template->loadTemplateFile('HtmlElement.html');
        $parsedChildren = null;
        foreach ($this->getChildren() as $child) {
            $parsedChildren .= $child->render();
        }
        $template->setVariable(array(
            'ELEMENT_NAME' => $this->name,
        ));
        if ($parsedChildren === null && $this->allowDirectClose()) {
            $template->hideBlock('children');
            $template->touchBlock('nochildren');
        } else {
            $template->hideBlock('nochildren');
            $template->touchBlock('children');
            $template->setVariable(array(
                'CHILDREN' => $parsedChildren,
            ));
        }
        foreach ($this->getAttributes() as $name=>$value) {
            $template->setVariable(array(
                'ATTRIBUTE_NAME' => $name,
                'ATTRIBUTE_VALUE' =>  preg_replace(array("/{/","/}/"), array("&#123;","&#125;"), contrexx_raw2xhtml((string) $value), -1), //replaces curly brackets, so they get not parsed with the sigma engine
            ));
            $template->parse('attribute');
        }
        if (count($this->classes)) {
            $template->setVariable(array(
                'ATTRIBUTE_NAME' => 'class',
                'ATTRIBUTE_VALUE' => contrexx_raw2xhtml($this->getClasses()),
            ));
            $template->parse('attribute');
        }
        $this->output = $template->get();
        return $this->output;
    }

    public function __toString() {
        return $this->render();
    }
}
