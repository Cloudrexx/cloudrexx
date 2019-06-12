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
 * EntityBase
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  model_base
 */

namespace Cx\Model\Base;

/**
 * Thrown by @link EntityBase::validate() if validation errors occur.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  model_base
 */
class ValidationException extends \Exception {
    protected $errors;

    public function __construct(array $errors) {
        parent::__construct();
        $this->errors = $errors;
        $this->assignMessage();
    }

    private function assignMessage() {
        $str = '';
        foreach($this->errors as $field => $details) {
            $str .= $field.":\n";
            foreach($details as $id => $message) {
                $str .= "    $id: $message\n";
            }
        }
        $this->message = $str;
    }

    public function getErrors() {
        return $this->errors;
    }
}

/**
 * This class provides the magic of being validatable.
 * See EntityBase::$validators if you want to subclass it.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  model_base
 */
class EntityBase {
    /**
     * Initialize this array as follows:
     * array(
     *     'columName' => Zend_Validate
     * )
     * @var array
     */
    protected $validators = array();

    /**
     * Defines if an entity is virtual and therefore not persistable.
     * Defaults to FALSE - not virtual.
     * @var boolean
     */
    protected $virtual = false;

    /**
     * This is an ugly solution to allow $this->cx to be available in all entity classes
     * Since the entity's constructor is not called when an entity is loaded from DB this
     * cannot be assigned there.
     */
    public function __get($name) {
        if ($name == 'cx') {
            return \Cx\Core\Core\Controller\Cx::instanciate();
        }
    }

    /**
     * Returns the component controller for this component
     * @return \Cx\Core\Core\Model\Entity\SystemComponent
     */
    public function getComponentController() {
        $matches = array();
        preg_match('/Cx\\\\(?:Core|Core_Modules|Modules)\\\\([^\\\\]*)\\\\|Cx\\\\Model\\\\Proxies\\\\Cx(?:Core_Modules|Core|Modules)([^\\\\]*)ModelEntity/', get_class($this), $matches);
        if (empty($matches[1])) {
            if (empty($matches[2])) {
                throw new \Exception('Could not find component name');
            }
            $matches[1] = $matches[2];
        }
        $em = $this->cx->getDb()->getEntityManager();
        $componentRepo = $em->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $myComponent = $componentRepo->findOneBy(array(
            'name' => $matches[1],
        ));
        if (!$myComponent) {
            throw new \Cx\Core\Core\Model\Entity\SystemComponentException('Component not found: "' . $matches[1] . '"');
        }
        return $myComponent;
    }

    /**
     * Set the virtuality of the entity
     * @param   boolean $virtual    TRUE to set the entity as virtual or otherwise to FALSE
     */
    public function setVirtual($virtual) {
        $this->virtual = $virtual;
    }

    /**
     * Returns the virtuality of the entity
     * @return  boolean TRUE if the entity is virtual, otherwise FALSE
     */
    public function isVirtual() {
        return $this->virtual;
    }

    /**
     * Set $this->validators
     *
     * Validators can be found in lib/FRAMEWORK/Validator.class.php
     * These will be executed if validate() is called
     */
    public function initializeValidators() { }

    /**
     * @throws ValidationException
     * @prePersist
     */
    public function validate() {
        $this->initializeValidators();

        if (!count($this->validators)) {
            return;
        }

        $errors = array();
        foreach ($this->validators as $field => $validator) {
            $methodName = 'get'.ucfirst($field);
            $val = $this->$methodName();
            if ($val) {
                if (!$validator->isValid($val)) {
                     $errors[$field] = $validator->getMessages();
                }
            }
        }
        if (count($errors) > 0) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Route methods like getName(), getType(), getDirectory(), etc.
     * @param string $methodName Name of method to call
     * @param array $arguments List of arguments for the method to call
     * @return mixed Return value of the method to call
     */
    public function __call($methodName, $arguments) {
        return call_user_func_array(array($this->getComponentController(), $methodName), $arguments);
    }

    /**
     * Returns this entity's key
     *
     * If this entity has a composite key, the fields are separated by $separator.
     * @param string $separator (optional) Separator for composite key fields, default "/"
     * @return string Entity key as string
     */
    public final function getKeyAsString($separator = '/') {
        $em = $this->cx->getDb()->getEntityManager();
        $cmf = $em->getMetadataFactory();
        $meta = $cmf->getMetadataFor(get_class($this));
        return (string) implode($separator, $meta->getIdentifierValues($this));
    }

    /**
     * Returns this entity's identifying value
     *
     * By default this returns the same as getKeyAsString(), but this method
     * might get overridden by subclasses.
     * @return string Identifying value for this entity
     */
    public function __toString() {
        return $this->getKeyAsString();
    }
}
