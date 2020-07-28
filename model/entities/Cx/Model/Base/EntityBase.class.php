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
     * List of fields that should be available in the string representation
     *
     * @see getStringRepresentationFields()
     * @var array List of field names
     */
    protected $stringRepresentationFields = array();

    /**
     * Sprintf format for the string representation
     *
     * @see getStringRepresentationFormat()
     * @var string Sprintf format string
     */
    protected $stringRepresentationFormat = '';

    /**
     * Counts the nesting level of __call()
     * @var int
     */
    protected static $nestingCount = 0;

    /**
     * List of locale codes ordered by translation fallback order per Cx instance
     *
     * @see getFallbackLocaleCodes()
     * @var array Array in the form: array(<instanceId> => array(<localeCode>, ...))
     */
    protected static $localeCodes = array();

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
            if (!$validator->isValid($val)) {
                 $errors[$field] = $validator->getMessages();
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
     * @throws \Exception If __call() nesting level reaches 20
     * @return mixed Return value of the method to call
     */
    public function __call($methodName, $arguments) {
        if (static::$nestingCount >= 20) {
            throw new \Exception('Stopped nesting at method ' . $methodName . '()');
        }
        static::$nestingCount++;
        $res = call_user_func_array(array($this->getComponentController(), $methodName), $arguments);
        static::$nestingCount--;
        return $res;
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
     * Returns a list of fields available in the string representation
     *
     * @return array List of field names
     */
    protected function getStringRepresentationFields() {
        return $this->stringRepresentationFields;
    }

    /**
     * Returns the sprintf() format for the string representation
     *
     * @return string sprintf() format string
     */
    protected function getStringRepresentationFormat() {
        return $this->stringRepresentationFormat;
    }

    /**
     * Returns a list of all locale codes ordered by fallback order
     *
     * - First entry is the current locale
     * - Second entry is the default locale (if different from current, it's omitted otherwise)
     * - Then all other locales follow (in no particular order)
     * @todo Order the array by the fallback order (same as in ContentManager)
     * @return array List of locale codes
     */
    protected function getFallbackLocaleCodes() {
        if (isset(static::$localeCodes[$this->cx->getId()])) {
            return static::$localeCodes[$this->cx->getId()];
        }
        $em = $this->cx->getDb()->getEntityManager();
        $currentLocaleId = \Env::get('init')->userFrontendLangId;
        $defaultLocaleId = \Env::get('init')->defaultFrontendLangId;
        $localeRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Locale');
        $locales = $localeRepo->findAll();

        // create an array with all locale codes except current language
        // with default language (if different) as first entry
        static::$localeCodes[$this->cx->getId()] = array();
        foreach ($locales as $locale) {
            if (
                $locale->getId() == $defaultLocaleId ||
                $locale->getId() == $currentLocaleId
            ) {
                continue;
            }
            static::$localeCodes[$this->cx->getId()][] = $locale->getShortForm();
        }
        // if current locale is different from default, add default
        if ($defaultLocaleId != $currentLocaleId) {
            array_unshift(
                static::$localeCodes[$this->cx->getId()],
                \FWLanguage::getLanguageCodeById($defaultLocaleId)
            );
        }
        // add the current locale as first entry
        array_unshift(
            static::$localeCodes[$this->cx->getId()],
            \FWLanguage::getLanguageCodeById($currentLocaleId)
        );
        return static::$localeCodes[$this->cx->getId()];
    }

    /**
     * Returns the value of a translatable field using fallback mechanisms
     *
     * If the field is not translatable its value is returned anyway.
     * Tries to return the value in the following locales (if non-empty):
     * - Current locale
     * - Default locale
     * - All other locales
     * @param string $fieldName Name of a translatable field
     */
    public function getTranslatedFieldValue($fieldName) {
        $em = $this->cx->getDb()->getEntityManager();
        $entityClassMetadata = $em->getClassMetadata(get_class($this));

        // field has non-empty value in current locale
        if ($entityClassMetadata->getFieldValue($this, $fieldName) != '') {
            return $entityClassMetadata->getFieldValue($this, $fieldName);
        }

        $translationListener = $this->cx->getDb()->getTranslationListener();
        $config = $translationListener->getConfiguration(
            $em,
            get_class($this)
        );

        // field is not translatable
        if (!in_array($fieldName, $config['fields'])) {
            return '';
        }

        // load all translations of field
        $translationRepo = $em->getRepository('Cx\Core\Locale\Model\Entity\Translation');
        $translations = $translationRepo->findBy(array(
            'objectClass' => get_class($this),
            'field' => $fieldName,
            'foreignKey' => \Gedmo\Tool\Wrapper\AbstractWrapper::wrap($this, $em)->getIdentifier(),
        ));

        // if there's but one translation we don't need to calculate which
        // one is the right one
        if (count($translations) == 1) {
            return current($translations)->getContent();
        }

        $indexedTranslations = array();
        foreach ($translations as $translation) {
            $indexedTranslations[$translation->getLocale()] = $translation->getContent();
        }

        $localeCodes = $this->getFallbackLocaleCodes();
        foreach ($localeCodes as $localeCode) {
            if (isset($indexedTranslations[$localeCode])) {
                return $indexedTranslations[$localeCode];
            }
        }

        // giving up, no translation found
        return '';
    }

    /**
     * Returns this entity's identifying value
     *
     * By default this returns the same as getKeyAsString(), but this method
     * might get overridden by subclasses.
     * @return string Identifying value for this entity
     */
    public function __toString() {
        if ($this->getStringRepresentationFormat() == '') {
            return $this->getKeyAsString();
        }
        if (!count($this->getStringRepresentationFields())) {
            $stringRepresentation = sprintf($this->getStringRepresentationFormat());
            if ($stringRepresentation == '') {
                return $this->getKeyAsString();
            }
        }
        $fieldValues = array();
        foreach ($this->getStringRepresentationFields() as $fieldName) {
            $fieldValues[] = $this->getTranslatedFieldValue($fieldName);
        }
        $stringRepresentation = vsprintf(
            $this->getStringRepresentationFormat(),
            $fieldValues
        );
        if ($stringRepresentation == '') {
            return $this->getKeyAsString();
        }
        return $stringRepresentation;
    }
}
