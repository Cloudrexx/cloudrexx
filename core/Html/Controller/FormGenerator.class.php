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

namespace Cx\Core\Html\Controller;

/**
 *
 */
class FormGenerator {

    /**
     * @var int $formIncrement This ID is used to store the next free $formId
     */
    public static $formIncrement = 0;

    /**
     * @var int $formId This ID is used as html id for the form so we can load more than one form
     */
    protected $formId;

    /**
     * @var \Cx\Core\Html\Model\Entity\FormElement $form used to store the form data
     */
    protected $form = null;

    /**
     * @var array $options form options
     */
    protected $options;

    /**
     * @var array $componentOptions component options
     */
    protected $componentOptions;

    /**
     * @var string $entityClass class to create form for
     */
    protected $entityClass;

    /**
     * @var bool $noView to set if a view should be created
     */
    protected $noView;

    /**
     * @var \Cx\Core\Html\Controller\ViewGenerator $viewGenerator instance of
     * ViewGenerator
     */
    protected $viewGenerator;

    /**
     * FormGenerator constructor.
     *
     * @param array $entity       entity to display
     * @param null $actionUrl     action url
     * @param string $entityClass entity class name
     * @param string $title       title for view
     * @param array $options      options from ViewGenerator
     * @param int $entityId       id of a specific entity
     * @param $componentOptions   options of the component
     * @param \Cx\Core\Html\Controller\ $viewGenerator instance of ViewGenerator
     * @param bool $noView        to set if a view should be created
     */
    public function __construct($entity, $actionUrl = null, $entityClass = '', $title = '', $options = array(), $entityId=0, $componentOptions, $viewGenerator = null, $noView = false)
    {
        $this->componentOptions = $componentOptions;
        $this->formId = static::$formIncrement;
        static::$formIncrement++;
        $this->options = $options;
        $this->entity = $entity;
        $this->entityClass = $entityClass;
        $this->noView = $noView;
        $this->viewGenerator = $viewGenerator;

        if ($this->noView) {
            return;
        }
        // Remove the virtual element from array
        unset($entity['virtual']);
        if (empty($entityClass) && is_object($entity)) {
            $this->entityClass = get_class($entity);
        }
        $this->constructView(
            $entity,
            $actionUrl,
            $entityClass,
            $title,
            $options,
            $entityId
        );
    }

    /**
     * Generate a view with a form.
     *
     * @param array $entity       entity to display
     * @param null $actionUrl     action url
     * @param string $entityClass entity class name
     * @param string $title       title for view
     * @param array $options      options from ViewGenerator
     * @param int $entityId       id of a specific entity
     */
    protected function constructView($entity, $actionUrl, $entityClass, $title, $options, $entityId)
    {
        global $_ARRAYLANG;

        if (empty($title)) {
            $title = $entityClass;
        }
        \JS::registerJS('core/Html/View/Script/Backend.js');
        \ContrexxJavascript::getInstance()->setVariable(
            'Form/Error',
            $_ARRAYLANG['TXT_CORE_HTML_FORM_VALIDATION_ERROR'],
            'core/Html/lang'
        );
        \JS::registerCSS(\Env::get('cx')->getCoreFolderName() . '/Html/View/Style/Backend.css');
        $this->form = new \Cx\Core\Html\Model\Entity\FormElement(
            $actionUrl,
            'post',
            \Cx\Core\Html\Model\Entity\FormElement::ENCTYPE_MULTIPART_FORMDATA,
            (
                !isset($options['functions']) ||
                !isset($options['functions']['formButtons']) ||
                $options['functions']['formButtons'] == true
            )
        );
        $hasTabs = false;

        $tabs = isset($options['tabs']) ? $options['tabs'] : array();

        $this->form->setAttribute('id', 'form-' . $this->formId);
        $this->form->setAttribute('class', 'cx-ui');
        $titleElement = new \Cx\Core\Html\Model\Entity\HtmlElement('legend');
        $titleElement->addChild(new \Cx\Core\Html\Model\Entity\TextElement($title));

        if (!empty($tabs)) {
            $hasTabs = true;
            $tabMenu = new \Cx\Core\Html\Model\Entity\HtmlElement('ul');
            $tabMenu->setAttribute('id', 'form-'.$this->formId.'-tabmenu');
            $tabMenu->addClass('tabmenu');
            $this->form->addChild($tabMenu);
            $titleElement->setAttribute(
                'id',
                'form-' . $this->formId . '-tab-legend'
            );
        }

        $this->form->addChild($titleElement);
        // @todo replace this by auto-find editid
        if (isset($_REQUEST['editid'])) {
            $editIdField = new \Cx\Core\Html\Model\Entity\DataElement('editid', contrexx_input2raw($_REQUEST['editid']), 'input');
            $editIdField->setAttribute('type', 'hidden');
            $this->form->addChild($editIdField);
        }

        try {
            $entityObject = \Env::get('em')->getClassMetadata($this->entityClass);
            $primaryKeyNames = $entityObject->getIdentifierFieldNames();
        } catch (\Doctrine\Common\Persistence\Mapping\MappingException $e) {
            $primaryKeyNames = array();
        }

        $overviewFields = array_keys($entity);
        foreach ($tabs as $tabName=>$tabData) {
            foreach ($entity as $field => $value) {
                if (in_array($field, $tabData['fields'])) {
                    $overviewKey = array_search($field, $overviewFields);
                    // Remove field from overview tab when it is in another tab
                    if ($overviewKey !== false) {
                        unset($overviewFields[$overviewKey]);
                    } else {
                        // Search duplicated keys and delete them
                        $tabKey = array_search($field, $tabData['fields']);
                        unset($tabs[$tabName]['fields'][$tabKey]);
                    }
                }
            }
        }

        // add list with all unsigned fields to overview tab
        if (!isset($tabs['overview'])) {
            $tabs['overview'] = array();
        }
        $tabs['overview']['fields'] = $overviewFields;
        // move overview tab to first place
        $tabs = array('overview' => $tabs['overview']) + $tabs;

        // Set header for default
        if (empty($tabs['overview']['header'])) {
            $tabs['overview']['header'] = $_ARRAYLANG['TXT_CORE_OVERVIEW'];
        }

        // foreach entity field
        foreach ($tabs as $tabName=>$tabData) {

            if ($hasTabs) {
                if (isset($tabData['header'])) {
                    $tabText = FormGenerator::getFormLabel($tabData, 'header');
                } else if (isset($_ARRAYLANG[$tabName])) {
                    $tabText = $_ARRAYLANG[$tabName];
                }

                $tabItem = new \Cx\Core\Html\Model\Entity\HtmlElement('li');
                $tabLink = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
                $tabHeader = new \Cx\Core\Html\Model\Entity\TextElement($tabText);

                $tabLink->setAttribute(
                    'id',
                    'vg-tabs_form-'.$this->formId . '-' . $tabName
                );
                $tabLink->addClass('vg-tab-links');

                $tabLink->addChild($tabHeader);
                $tabItem->addChild($tabLink);
                $tabMenu->addChild($tabItem);
            }

            $tab = new \Cx\Core\Html\Model\Entity\HtmlElement('div');

            $tab->setAttributes(
                array(
                    'id' => 'form-'. $this->formId . '-' . $tabName,
                    'class' => 'vg-tabs'
                )
            );

            // foreach entity field
            foreach ($entity as $field => $value) {
                if (!in_array($field, $tabData['fields'])) {
                    continue;
                }

                $type = null;

                if (!empty($options[$field]['type'])) {
                    $type = $options[$field]['type'];
                }

                if (is_object($value)) {
                    if ($value instanceof \Cx\Model\Base\EntityBase) {
                        $type = 'Cx\Model\Base\EntityBase';
                    } elseif ($value instanceof \Doctrine\Common\Collections\Collection) {
                        continue;
                    } else {
                        $type = get_class($value);
                    }
                }
                $length = 0;
                $value = $entity[$field];
                $fieldOptions = array();
                if (isset($options['fields']) && isset($options['fields'][$field])) {
                    $fieldOptions = $options['fields'][$field];
                }
                if (!empty($fieldOptions['type'])) {
                    $type = $fieldOptions['type'];
                }
                if (in_array($field, $primaryKeyNames)) {
                    $dataElement = new \Cx\Core\Html\Model\Entity\TextElement(
                        $value
                    );
                } else {
                    $dataElement = $this->getDataElement($field, $field, $type, $length, $value, $fieldOptions, $entityId);
                }

                if (empty($dataElement)) {
                    continue;
                }
                $dataElement->setAttribute('id', 'form-' . $this->formId . '-' . $field);
                if ($type == 'hidden') {
                    $element = $dataElement;
                } else {
                    $element = $this->getDataElementGroup($field, $dataElement, $fieldOptions);
                }
                if (empty($element)) {
                    continue;
                }
                $tab->addChild($element);
            }
            if (isset($options['cancelUrl'])) {
                $this->form->cancelUrl = $options['cancelUrl'];
            }
            $this->form->addChild($tab);
        }
    }

    /**
     * Return a DataElement without previously defining the type.
     *
     * @param string $name     name of the DataElement
     * @param string $title    used title instead of name if html tag should not
     *                         be called like the attribute
     * @param int    $length   length of the DataElement
     * @param mixed  $value    value of the DataElement
     * @param int    $entityId id of the DataElement
     * @return \Cx\Core\Html\Model\Entity\DataElement
     */
    public function getDataElementWithoutType($name, $title, $length, $value, $entityId)
    {
        $type = null;

        if (!empty($this->options[$name]['type'])) {
            $type = $this->options[$name]['type'];
        }

        if (is_object($value)) {
            if ($value instanceof \Cx\Model\Base\EntityBase) {
                $type = 'Cx\Model\Base\EntityBase';
            } elseif ($value instanceof \Doctrine\Common\Collections\Collection) {
                return null;
            } else {
                $type = get_class($value);
            }
        }

        $fieldOptions = array();
        if (isset($this->options['fields']) && isset($this->options['fields'][$name])) {
            $fieldOptions = $this->options['fields'][$name];
        }

        if (!empty($fieldOptions['type'])) {
            $type = $fieldOptions['type'];
        }

        $dataElement = $this->getDataElement($name, $title, $type, $length, $value, $fieldOptions, $entityId);
        if (empty($dataElement)) {
            return null;
        }
        $dataElement->setAttribute('id', 'form-' . $this->formId . '-' . $name);
        if ($type == 'hidden' || $this->noView) {
            $element = $dataElement;
        } else {
            $element = $this->getDataElementGroup($name, $dataElement, $fieldOptions);
        }

        return $element;
    }

    /**
     * This function returns the elementGroup for a DataElement
     *
     * @param string $field name of the field
     * @param object $dataElement the element of the field
     * @param array $fieldOptions options for the field
     * @return \Cx\Core\Html\Model\Entity\HtmlElement
     */
    public function getDataElementGroup($field, $dataElement, $fieldOptions = array()) {
        global $_ARRAYLANG;

        $group = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $group->setAttribute('class', 'group');
        $label = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
        $label->setAttribute('for', 'form-' . $this->formId . '-' . $field);
        $fieldHeader = $field;
        if (isset($fieldOptions['formtext'])){
            $fieldHeader = FormGenerator::getFormLabel($fieldOptions, 'formtext');
        } else if (isset($fieldOptions['header'])) {
            $fieldHeader = FormGenerator::getFormLabel($fieldOptions, 'header');
        } else if (isset($_ARRAYLANG[$fieldHeader])) {
            $fieldHeader = $_ARRAYLANG[$fieldHeader];
        }
        $label->addChild(new \Cx\Core\Html\Model\Entity\TextElement($fieldHeader . ' '));
        $group->addChild($label);
        $controls = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $controls->setAttribute('class', 'controls');
        $controls->addChild($dataElement);
        if (isset($fieldOptions['tooltip'])) {
            $tooltipTrigger = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
            $tooltipTrigger->setAttribute('class', 'icon-info tooltip-trigger');
            $tooltipTrigger->allowDirectClose(false);
            $tooltipMessage = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
            $tooltipMessage->setAttribute('class', 'tooltip-message');
            $tooltipMessage->addChild(new \Cx\Core\Html\Model\Entity\TextElement($fieldOptions['tooltip']));
            $controls->addChild($tooltipTrigger);
            $controls->addChild($tooltipMessage);
        }
        $group->addChild($controls);
        return $group;
    }

    /**
     * This function returns the DataElement
     *
     * @param string $name name of the DataElement
     * @param string $title used title instead of name if html tag should not
     *                      be called like the attribute
     * @param string $type type of the DataElement
     * @param int $length length of the DataElement
     * @param mixed $value value of the DataElement
     * @param array $options options for the DataElement
     * @param int $entityId id of the DataElement
     * @return \Cx\Core\Html\Model\Entity\DataElement
     */
    public function getDataElement($name, $title, $type, $length, $value, &$options, $entityId) {
        global $_ARRAYLANG, $_CORELANG;

        try {
            if (isset($options['valueCallback'])) {
                $value = \Cx\Core\Html\Controller\ViewGenerator::callCallbackByInfo(
                    $options['valueCallback'],
                    array(
                        'fieldvalue' => $value,
                        'fieldname' => $name,
                        'rowData' => array(),
                        'fieldoption' => $options,
                        'vgId' => $this->viewGenerator->getViewId(),
                    )
                );
            }
        } catch (\Exception $e) {
            \Message::add($e->getMessage(), \Message::CLASS_ERROR);
        }
        try {
            if (isset($options['formfield'])) {
                $formFieldGenerator = $options['formfield'];

                $formField = \Cx\Core\Html\Controller\ViewGenerator::callCallbackByInfo(
                    $formFieldGenerator,
                    array(
                        'name' => $title,
                        'type' => $type,
                        'length' => $length,
                        'value' => $value,
                        'options' => $options,
                        'id' => $entityId,
                    )
                );

                if (is_a($formField, 'Cx\Core\Html\Model\Entity\HtmlElement')) {
                    return $formField;
                } else {
                    $value = $formField;
                }
            }
        } catch (\Exception $e) {
            \Message::add($e->getMessage(), \Message::CLASS_ERROR);
        }

        if (isset($options['showDetail']) && $options['showDetail'] === false) {
            return '';
        }
        switch ($type) {
            case 'bool':
            case 'boolean':
                // yes/no checkboxes
                $fieldset = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
                $inputYes = new \Cx\Core\Html\Model\Entity\DataElement($title, 'yes');
                $inputYes->setAttribute('type', 'radio');
                $inputYes->setAttribute('value', '1');
                $inputYes->setAttribute('id', 'form-' . $this->formId . '-' . $name . '_yes');
                if (isset($options['attributes'])) {
                    $inputYes->setAttributes($options['attributes']);
                }
                $fieldset->addChild($inputYes);
                $labelYes = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
                $labelYes->setAttribute('for', 'form-' . $this->formId . '-' . $name . '_yes');
                $labelYes->addChild(new \Cx\Core\Html\Model\Entity\TextElement($_ARRAYLANG['TXT_YES']));
                $fieldset->addChild($labelYes);
                $inputNo = new \Cx\Core\Html\Model\Entity\DataElement($title, 'no');
                $inputNo->setAttribute('id', 'form-' . $this->formId . '-' . $name . '_no');
                $inputNo->setAttribute('type', 'radio');
                $inputNo->setAttribute('value', '0');
                if (isset($options['attributes'])) {
                    $inputNo->setAttributes($options['attributes']);
                }
                $fieldset->addChild($inputNo);
                $labelNo = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
                $labelNo->setAttribute('for', 'form-' . $this->formId . '-' . $name . '_no');
                $labelNo->addChild(new \Cx\Core\Html\Model\Entity\TextElement($_ARRAYLANG['TXT_NO']));
                $fieldset->addChild($labelNo);
                if ($value) {
                    $inputYes->setAttribute('checked');
                } else {
                    $inputNo->setAttribute('checked');
                }
                return $fieldset;
                break;
            case 'int':
            case 'integer':
                // input field with type number
                $inputNumber = new \Cx\Core\Html\Model\Entity\DataElement(
                    $title,
                    $value,
                    \Cx\Core\Html\Model\Entity\DataElement::TYPE_INPUT,
                    new \Cx\Core\Validate\Model\Entity\RegexValidator(
                        '/-?[0-9]*/'
                    )
                );
                if (isset($options['attributes'])) {
                    $inputNumber->setAttributes($options['attributes']);
                }
                if (isset($options['readonly']) && $options['readonly']) {
                    $inputNumber->setAttribute('disabled');
                }
                $inputNumber->setAttribute('type', 'number');
                return $inputNumber;
                break;
            case 'Cx\Model\Base\EntityBase':
                $associatedClass = get_class($value);
                $cx = \Cx\Core\Core\Controller\Cx::Instanciate();
                $em = $cx->getDb()->getEntityManager();
                $localMetaData = $em->getClassMetadata($this->entityClass);
                if ($localMetaData->isSingleValuedAssociation($name)) {
                    // this case is used to create a select field for 1 to 1 associations
                    $entities = $em->getRepository($associatedClass)->findAll();
                    $foreignMetaData = $em->getClassMetadata($associatedClass);
                    $primaryKeyName = $foreignMetaData->getSingleIdentifierFieldName();
                    $selected = $foreignMetaData->getFieldValue($value, $primaryKeyName);
                    $arrEntities = array();
                    $assocMapping = $localMetaData->getAssociationMapping($name);
                    $validator = null;
                    if (!isset($assocMapping['joinColumns'][0]['nullable']) || $assocMapping['joinColumns'][0]['nullable']) {
                        $arrEntities['NULL'] = $_ARRAYLANG['TXT_CORE_NONE'];
                    } else {
                        $validator = new \Cx\Core\Validate\Model\Entity\RegexValidator('/^(?!null$|$)/');
                    }
                    foreach ($entities as $entity) {
                        $arrEntities[$em->getClassMetadata($associatedClass)->getFieldValue($entity, $primaryKeyName)] = $entity;
                    }
                    $select = new \Cx\Core\Html\Model\Entity\DataElement(
                        $title,
                        $selected,
                        \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT,
                        $validator,
                        $arrEntities
                    );
                    if (isset($options['attributes'])) {
                        $select->setAttributes($options['attributes']);
                    }
                    return $select;
                } else {
                    $mode = 'create';
                    if (isset($options['mode'])) {
                        if (in_array($options['mode'], array(
                            'create',
                            'associate',
                        ))) {
                            $mode = $options['mode'];
                        }
                    }
                    if ($mode == 'associate') {
                        // get all currently assigned entities
                        $values = array();
                        // if we edit the main form, we also want to show the existing associated values we already have
                        $assocMapping = $localMetaData->getAssociationMapping($name);
                        $data = $this->getForeignEntitySelectOptionData($assocMapping, $associatedClass, $entityId, $options, true);
                        $values = $data['all'];
                        if (!count($values)) {
                            $values = array('');
                        }
                        $_ARRAYLANG += \Env::get('init')->getComponentSpecificLanguageData('Html', false);
                        // get all assigned entities (by ajax?)
                        // add chosen
                        $select = new \Cx\Core\Html\Model\Entity\DataElement(
                            $name . '[]',
                            '',
                            \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT,
                            null,
                            $values
                        );
                        foreach ($select->getChildren() as $option) {
                            if (isset($data['selected'][$option->getAttribute('value')])) {
                                $option->setAttribute('selected');
                            }
                        }
                        $select->addClass('chzn');
                        $select->setAttribute('data-placeholder', $_ARRAYLANG['TXT_CORE_HTML_PLEASE_CHOOSE']);
                        $select->setAttribute('multiple');
                        if (isset($options['attributes'])) {
                            $select->setAttributes($options['attributes']);
                        }
                        return $select;
                    } else {
                        // this case is used to list all existing values and show an add button for 1 to many associations
                        $assocMapping = $localMetaData->getAssociationMapping($name);
                        $mainDiv = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
                        $mainDiv->setAttribute('class', 'entityList');
                        $addButton = new \Cx\Core\Html\Model\Entity\HtmlElement('input');
                        $addButton->setAttribute('type', 'button');
                        $addButton->setClass(array('form-control', 'add_'.$this->createCssClassNameFromEntity($associatedClass), 'mappedAssocciationButton'));
                        $addButton->setAttribute('value', $_CORELANG['TXT_ADD']);
                        $addButton->setAttribute('data-params',
                            'entityClass:'.$associatedClass.';'.
                            'mappedBy:'.$assocMapping['mappedBy'].';'.
                            'cssName:'.$this->createCssClassNameFromEntity($associatedClass).';'.
                            'sessionKey:'.$this->entityClass
                        );
                        $cx->getComponent('Html')->whitelistParamSet(
                            'getViewOverJson',
                            array(
                                'entityClass' => $associatedClass,
                                'mappedBy' => $assocMapping['mappedBy'],
                                'sessionKey' => $this->entityClass
                            ),
                            array()
                        );
                        if (!isset($_SESSION['vgOptions'])) {
                            $_SESSION['vgOptions'] = array();
                        }
                        // This is extremely slow as it stores the complete
                        // view-generator configuration to session. This should
                        // be added as a reference
                        $_SESSION['vgOptions'][$this->entityClass] = $this->componentOptions;
                        if ($entityId != 0) {
                            // if we edit the main form, we also want to show the existing associated values we already have
                            $existingValues = $this->getIdentifyingDisplayValue($assocMapping, $associatedClass, $entityId, $options);
                        }
                        if (!empty($existingValues)) {
                            foreach ($existingValues as $existingValue) {
                                $mainDiv->addChild($existingValue);
                            }
                        }
                        $mainDiv->addChild($addButton);

                        // if standard tooltip is not disabled, we load the one to n association text
                        if (!isset($options['showstanardtooltip']) || $options['showstanardtooltip']) {
                            if (!empty($options['tooltip'])) {
                                $options['tooltip'] = $options['tooltip'] . '<br /><br /> ' . $_ARRAYLANG['TXT_CORE_RECORD_ONE_TO_N_ASSOCIATION'];
                            } else {
                                $options['tooltip'] = $_ARRAYLANG['TXT_CORE_RECORD_ONE_TO_N_ASSOCIATION'];
                            }
                        }
                        $cxjs = \ContrexxJavascript::getInstance();
                        $cxjs->setVariable('TXT_CANCEL', $_CORELANG['TXT_CANCEL'], 'Html/lang');
                        $cxjs->setVariable('TXT_SUBMIT', $_CORELANG['TXT_SUBMIT'], 'Html/lang');
                        $cxjs->setVariable('TXT_EDIT', $_CORELANG['TXT_EDIT'], 'Html/lang');
                        $cxjs->setVariable('TXT_DELETE', $_CORELANG['TXT_DELETE'], 'Html/lang');

                        return $mainDiv;
                    }
                }
                break;
            case 'Country':
                // this is for customizing only:
                $data = \Cx\Core\Country\Controller\Country::getNameById($value);
                if (empty($data)) {
                    $value = 204;
                }
                $menuoptions = \Cx\Core\Country\Controller\Country::getMenuoptions($value);
                $select = new \Cx\Core\Html\Model\Entity\DataElement(
                    $title,
                    '',
                    \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT
                );
                $select->addChild(
                    new \Cx\Core\Html\Model\Entity\TextElement($menuoptions)
                );
                if (isset($options['attributes'])) {
                    $select->setAttributes($options['attributes']);
                }
                return $select;
                break;
            case 'DateTime':
            case 'datetime':
            case 'date':
                // input field with type text and class datepicker
                if ($value instanceof \DateTime) {
                    $value = $value->format(ASCMS_DATE_FORMAT);
                }
                if (is_null($value)) {
                    $value = '';
                }
                $input = new \Cx\Core\Html\Model\Entity\DataElement($title, $value);
                $input->setAttribute('type', 'text');
                $input->setAttribute('class', 'datepicker');
                if (isset($options['readonly']) && $options['readonly']) {
                    $input->setAttribute('disabled');
                }
                if (isset($options['attributes'])) {
                    $input->setAttributes($options['attributes']);
                }
                \DateTimeTools::addDatepickerJs();
                if ($type == 'date') {
                    \JS::registerCode('
                            cx.jQuery(function() {
                              cx.jQuery(".datepicker").datepicker();
                            });
                            ');
                } else {
                    \JS::registerCode('
                            cx.jQuery(function() {
                              cx.jQuery(".datepicker").datetimepicker();
                            });
                            ');
                }
                return $input;
                break;
            case 'multiselect':
            case 'select':
                $values = array();
                if (isset($options['validValues'])) {
                    if (is_array($options['validValues'])) {
                        $values = $options['validValues'];
                    } else {
                        $values = explode(',', $options['validValues']);
                        $values = array_combine($values, $values);
                    }
                }
                if ($type == 'multiselect') {
                    $value = explode(',', $value);
                    $value = array_combine($value, $value);
                }
                $select = new \Cx\Core\Html\Model\Entity\DataElement(
                    $title,
                    $value,
                    \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT,
                    null,
                    $values
                );
                if ($type == 'multiselect') {
                    $select->setAttribute('multiple');
                }
                if (isset($options['attributes'])) {
                    $select->setAttributes($options['attributes']);
                }
                return $select;
                break;
            case 'slider':
                // this code should not be here

                // create sorrounding div
                $element = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
                // create div for slider
                $slider = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
                $slider->setAttribute('class', 'slider');
                $element->addChild($slider);
                // create hidden input for slider value
                $input = new \Cx\Core\Html\Model\Entity\DataElement(
                    $title,
                    $value + 0,
                    \Cx\Core\Html\Model\Entity\DataElement::TYPE_INPUT
                );
                $input->setAttribute('type', 'hidden');
                if (isset($options['attributes'])) {
                    $input->setAttributes($options['attributes']);
                }
                $element->addChild($input);
                // add javascript to update input value
                $min = 0;
                $max = 10;
                if (isset($options['validValues'])) {
                    $values = explode(',', $options['validValues']);
                    $min = $values[0];
                    if (isset($values[1])) {
                        $max = $values[1];
                    }
                }
                if (!isset($value)) {
                    $value = 0;
                }
                $script = new \Cx\Core\Html\Model\Entity\HtmlElement('script');
                $script->addChild(new \Cx\Core\Html\Model\Entity\TextElement('
                    cx.jQuery("#form-' . $this->formId . '-' . $title . ' .slider").slider({
                        value: ' . ($value+0) . ',
                        min: ' . ($min+0) . ',
                        max: ' . ($max+0) . ',
                        slide: function( event, ui ) {
                            cx.jQuery("input[name=' . $title . ']").val(ui.value);
                            cx.jQuery("input[name=' . $title . ']").change();
                        }
                    });
                '));
                $element->addChild($script);
                return $element;
                break;
            case 'checkboxes':
                $dataElementGroupType = \Cx\Core\Html\Model\Entity\DataElementGroup::TYPE_CHECKBOX;
            case 'radio':
                $values = array();
                if (isset($options['validValues'])) {
                    $values = explode(',', $options['validValues']);
                    $values = array_combine($values, $values);
                }
                if (!isset($dataElementGroupType)) {
                    $dataElementGroupType = \Cx\Core\Html\Model\Entity\DataElementGroup::TYPE_RADIO;
                }
                $radio = new \Cx\Core\Html\Model\Entity\DataElementGroup(
                    $title,
                    $values,
                    $value,
                    $dataElementGroupType
                );
                if (isset($options['attributes'])) {
                    $radio->setAttributes($options['attributes']);
                }
                return $radio;
                break;
            case 'text':
                // textarea
                $textarea = new \Cx\Core\Html\Model\Entity\HtmlElement('textarea');
                $textarea->setAttribute('name', $title);
                if (isset($options['readonly']) && $options['readonly']) {
                    $textarea->setAttribute('disabled');
                }
                $textarea->addChild(new \Cx\Core\Html\Model\Entity\TextElement($value));
                if (isset($options['attributes'])) {
                    $textarea->setAttributes($options['attributes']);
                }
                return $textarea;
                break;
            case 'div':
                $wrapper = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
                $text = new \Cx\Core\Html\Model\Entity\TextElement($value);
                $wrapper->addChild($text);
                if (isset($options['attributes'])) {
                    $wrapper->setAttributes($options['attributes']);
                }
                return $wrapper;
                break;
            case 'phone':
                // input field with type phone
                $input = new \Cx\Core\Html\Model\Entity\DataElement($title, $value);
                $input->setAttribute('type', 'phone');
                if (isset($options['readonly']) && $options['readonly']) {
                    $input->setAttribute('disabled');
                }
                if (isset($options['attributes'])) {
                    $input->setAttributes($options['attributes']);
                }
                return $input;
                break;
            case 'mail':
                // input field with type mail
                $emailValidator = new \Cx\Core\Validate\Model\Entity\EmailValidator();
                $input = new \Cx\Core\Html\Model\Entity\DataElement($title, $value, 'input', $emailValidator);
                $input->setAttribute('onkeyup', $emailValidator->getJavaScriptCode());
                $input->setAttribute('type', 'mail');
                if (isset($options['attributes'])) {
                    $input->setAttributes($options['attributes']);
                }
                if (isset($options['readonly']) && $options['readonly']) {
                    $input->setAttribute('disabled');
                }
                return $input;
                break;
            case 'uploader':
                \JS::registerCode('
                    function javascript_callback_function(data) {
                        if(data.type=="file") {
                                cx.jQuery("#'.$title.'").val(data.data[0].datainfo.filepath);
                        }
                    }

                ');
                $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
                $mediaBrowser->setOptions(array('type' => 'button'));
                $mediaBrowser->setCallback('javascript_callback_function');
                $mediaBrowser->setOptions(
                    array(
                        'views' => 'filebrowser,uploader',
                        'id' => 'page_target_browse',
                        'startview' => 'filebrowser'
                    )
                );

                $input = new \Cx\Core\Html\Model\Entity\DataElement($title, $value);
                $input->setAttribute('type', 'text');
                $input->setAttribute('id', $title);

                $div = new \Cx\Core\Html\Model\Entity\HtmlElement('div');

                $div->addChild($input);
                $div->addChild(new \Cx\Core\Html\Model\Entity\TextElement(
                    $mb = $mediaBrowser->getXHtml($_ARRAYLANG['TXT_CORE_CM_BROWSE'])
                ));

                return $div;
                break;
            case 'image':
                \JS::registerCode('
                    function javascript_callback_function(data) {
                        if ( data.data[0].datainfo.extension=="Jpg"
                            || data.data[0].datainfo.extension=="Gif"
                            || data.data[0].datainfo.extension=="Png"
                        ) {
                            cx.jQuery("#'.$title.'").attr(\'value\', data.data[0].datainfo.filepath);
                            cx.jQuery("#'.$title.'").prevAll(\'.deletePreviewImage\').first().css(\'display\', \'inline-block\');
                            cx.jQuery("#'.$title.'").prevAll(\'.previewImage\').first().attr(\'src\', data.data[0].datainfo.filepath);
                        }
                    }

                    jQuery(document).ready(function(){
                        jQuery(\'.deletePreviewImage\').click(function(){
                            cx.jQuery("#'.$title.'").attr(\'value\', \'\');
                            cx.jQuery(this).prev(\'img\').attr(\'src\', \'/images/Downloads/no_picture.gif\');
                            cx.jQuery(this).css(\'display\', \'none\');
                            cx.jQuery(this).nextAll(\'input\').first().attr(\'value\', \'\');
                        });
                    });

                ');
                $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
                $mediaBrowser->setOptions(array('type' => 'button'));
                $mediaBrowser->setCallback('javascript_callback_function');
                $defaultOptions = array(
                    'views' => 'filebrowser,uploader',
                    'id' => 'page_target_browse',
                    'startview' => 'filebrowser'
                );

                $mediaBrowser->setOptions(
                    is_array($options['options'])?array_merge($defaultOptions,$options['options']):$defaultOptions
                );

                // create hidden input to save image
                $input = new \Cx\Core\Html\Model\Entity\DataElement($title, $value);
                $input->setAttribute('type', 'hidden');
                $input->setAttribute('id', $title);

                $div = new \Cx\Core\Html\Model\Entity\HtmlElement('div');

                if((isset($value) && in_array(pathinfo($value, PATHINFO_EXTENSION), Array('gif', 'jpg', 'png'))) || $title == 'imagePath'){

                    // this image is meant to be a preview of the selected image
                    $previewImage = new \Cx\Core\Html\Model\Entity\HtmlElement('img');
                    $previewImage->setAttribute('class', 'previewImage');
                    $previewImage->setAttribute('src', ($value != '') ? $value : '/images/Downloads/no_picture.gif');

                    // this image is uesd as delete function for the selected image over javascript
                    $deleteImage = new \Cx\Core\Html\Model\Entity\HtmlElement('img');
                    $deleteImage->setAttribute('class', 'deletePreviewImage');
                    $deleteImage->setAttribute('src', '/core/Core/View/Media/icons/delete.gif');

                    $div->addChild($previewImage);
                    $div->addChild($deleteImage);
                    $div->addChild(new \Cx\Core\Html\Model\Entity\HtmlElement('br'));
                }
                $div->addChild($input);
                $div->addChild(new \Cx\Core\Html\Model\Entity\TextElement(
                    $mediaBrowser->getXHtml($_ARRAYLANG['TXT_CORE_CM_BROWSE'])
                ));

                return $div;
                break;
            case 'sourcecode':
                //set mode
                $mode = 'html';
                if(isset($options['options']['mode'])) {
                    switch($options['options']['mode']) {
                        case 'js':
                            $mode = 'javascript';
                        break;
                        case 'yml':
                        case 'yaml':
                            $mode = 'yaml';
                        break;
                    }
                }

                //define textarea
                $textarea = new \Cx\Core\Html\Model\Entity\HtmlElement('textarea');
                $textarea->setAttribute('name', $title);
                $textarea->setAttribute('id', $title);
                $textarea->setAttribute('style', 'display:none;');
                $textarea->addChild(new \Cx\Core\Html\Model\Entity\TextElement($value));

                //define pre
                $pre = new \Cx\Core\Html\Model\Entity\HtmlElement('pre');
                $pre->setAttribute('id','editor-'.$title);
                $pre->addChild(new \Cx\Core\Html\Model\Entity\TextElement(contrexx_raw2xhtml($value)));

                //set readonly if necessary
                $readonly = '';
                if (isset($options['readonly']) && $options['readonly']) {
                    $readonly = 'editor.setReadOnly(true);';
                    $textarea->setAttribute('disabled');
                }

                //create div and add all stuff
                $div = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
                //     required for the Ace editor to work. Otherwise
                //     it won't be visible as the DIV does have a width of 0px.
                $div->setAttribute('style','display:block;');
                $div->addChild($textarea);
                $div->addChild($pre);

                //register js
                $jsCode = <<<CODE
var editor;
\$J(function(){
if (\$J("#editor-$title").length) {
    editor = ace.edit("editor-$title");
    editor.getSession().setMode("ace/mode/$title");
    editor.setShowPrintMargin(false);
    editor.focus();
    editor.gotoLine(1);
    $readonly
}

\$J('form').submit(function(){
    \$J('#$title').val(editor.getSession().getValue());
});

});
CODE;
                \JS::activate('ace');
                \JS::registerCode($jsCode);

                return $div;
                break;
            case 'password':
                $input = new \Cx\Core\Html\Model\Entity\DataElement($title, '');
                if (isset($options['validValues'])) {
                    $input->setValidator(
                        new \Cx\Core\Validate\Model\Entity\RegexValidator(
                            '/^' . $options['validValues'] . '$/'
                        )
                    );
                }
                $input->setAttribute('type', 'password');
                $input->setClass('form-control');
                if (isset($options['readonly']) && $options['readonly']) {
                    $input->setAttribute('disabled');
                }
                if (isset($options['attributes'])) {
                    $input->setAttributes($options['attributes']);
                }
                if (!isset($options['mode']) || $options['mode'] != 'nocomplete') {
                    return $input;
                }
                // in order to circumvent forced autocompletion by modern
                // browsers we need to add an additional field and set the type
                // "dynamically"...
                // For more info see CLX-2388
                $container = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
                $dummyInput = new \Cx\Core\Html\Model\Entity\DataElement($title, '');
                $dummyInput->setAttribute('type', 'password');
                $dummyInput->setAttribute('style', 'display:none;');
                $container->addChild($dummyInput);
                $input->setAttribute('type', 'text');
                $input->setAttribute(
                    'onkeyup',
                    'if (this.value == \'\') { this.setAttribute(\'type\', \'text\'); } else { this.setAttribute(\'type\', \'password\'); }'
                );
                $input->setAttribute(
                    'style',
                    'text-security: disc; -webkit-text-security: disc;'
                );
                $container->addChild($input);
                return $container;
                break;
            case 'string':
            case 'hidden':
            default:
                // convert NULL to empty string
                if (is_null($value)) {
                    $value = '';
                }
                // input field with type text
                $input = new \Cx\Core\Html\Model\Entity\DataElement($title, $value);
                if (isset($options['validValues'])) {
                    $input->setValidator(new \Cx\Core\Validate\Model\Entity\RegexValidator('/^' . $options['validValues'] . '$/'));
                }
                if ($type == 'hidden') {
                    $input->setAttribute('type', 'hidden');
                } else {
                    $input->setAttribute('type', 'text');
                    $input->setClass('form-control');
                }
                if (isset($options['readonly']) && $options['readonly']) {
                    $input->setAttribute('disabled');
                }
                if (isset($options['attributes'])) {
                    $input->setAttributes($options['attributes']);
                }
                if (!isset($options['mode']) || $options['mode'] != 'nocomplete') {
                    return $input;
                }
                // in order to circumvent forced autocompletion by modern
                // browsers we need to add an additional field and set the type
                // "dynamically"...
                // For more info see CLX-2388
                $container = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
                $dummyInput = new \Cx\Core\Html\Model\Entity\DataElement($title, '');
                $dummyInput->setAttribute('style', 'display:none;');
                $container->addChild($dummyInput);
                $container->addChild($input);
                $dummyInput2 = new \Cx\Core\Html\Model\Entity\DataElement(
                    'not-a-' . $title,
                    ''
                );
                $dummyInput2->setAttribute('style', 'display:none;');
                $container->addChild($dummyInput2);
                return $container;
                break;
        }
    }

    /**
     * This method returns a css valid name for a given php class
     *
     * @access protected
     * @param string $entityClass class including namespace
     * @return string css class
     */
    protected function createCssClassNameFromEntity ($entityClass) {
        return strtolower(str_replace('\\','_', $entityClass));
    }

    /**
     * This function returns the value of a passed object/array/element as a string according to the type
     * At the moment only date and time formats are supported
     *
     * @param mixed $element element of which we want to create a string
     * @access protected
     * @return string value of DataElement as string
     */
    protected function getDataElementValueAsString($element) {
        if (is_object($element)) {
            $type = get_class($element);
        } else {
            $type = 'string';
        }
        switch ($type) {
            case 'DateTime':
            case 'datetime':
            case 'date':
                $element = $element->format(ASCMS_DATE_FORMAT);
                break;
            default:
                break;
        }
        return $element;
    }

    /**
     * Returns the data needed to create a n:x multiselect
     *
     * This returns an array with two indexes, each containig an array in the
     * following form: array(<entityIndexData> => <entity>). Entity index data
     * is a string of all all identifier field values joined by "/".
     * The two main keys are "all" and "selected". "all" contains all "foreign"
     * entities, "selected" only the ones selected by the current entity.
     * @param array $assocMapping Mapping information for this relation
     * @param string $entityClass FQCN of the foreign entity
     * @param int $entityId ID of the local entity
     * @param arrray $options Options for this view
     * @return array Array with two indexes, see method description
     */
    protected function getForeignEntitySelectOptionData($assocMapping, $entityClass, $entityId, $options) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $localEntityMetadata = $em->getClassMetadata($this->entityClass);
        $localEntityRepo = $em->getRepository($this->entityClass);
        $foreignEntityRepo = $em->getRepository($assocMapping['targetEntity']);
        if ($entityId != 0) {
            $localEntity = $localEntityRepo->find($entityId);
            if (!$localEntity) {
                throw new \Exception('Entity not found');
            }
        }

        $methodBaseName = \Doctrine\Common\Inflector\Inflector::classify(
            $assocMapping['fieldName']
        );
        $foreignEntityGetter = 'get' . $methodBaseName;
        if ($assocMapping['fetch'] == \Doctrine\ORM\Mapping\ClassMetadata::FETCH_EXTRA_LAZY) {
            // TODO: Handle this case as we'll otherwise probably run out of memory
        }
        $allForeignEntities = $foreignEntityRepo->findAll();
        $data = array(
            'all' => array(),
            'selected' => array(),
        );
        foreach ($allForeignEntities as $foreignEntity) {
            $data['all'][implode('/', static::getEntityIndexData($foreignEntity))] = (string) $foreignEntity;
        }
        if ($entityId != 0) {
            foreach ($localEntity->$foreignEntityGetter() as $foreignEntity) {
                $data['selected'][implode('/', static::getEntityIndexData($foreignEntity))] = (string) $foreignEntity;
            }
        }
        return $data;
    }

    /**
     * Helper function to get a list of all values of identifier fields
     * @param \Cx\Model\Base\EntityBase $entity Entity to get index data of
     * @return array List of values
     */
    public static function getEntityIndexData($entity) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $entityMetadata = $em->getClassMetadata(get_class($entity));
        return $entityMetadata->getIdentifierValues($entity);
    }

    /**
     * Finds an entity by its index data
     *
     * @param string $entityClass Fully qualified class name
     * @param array $indexData List of index values
     * @return \Cx\Model\Base\EntityBase The matching entity (or null)
     */
    public static function findEntityByIndexData($entityClass, $indexData) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $entityMetadata = $em->getClassMetadata($entityClass);
        $fieldNames = $entityMetadata->getIdentifierFieldNames();
        $crit = array();
        foreach ($fieldNames as $index=>$fieldName) {
            $crit[$fieldName] = $indexData[$index];
        }
        $entityRepository = $em->getRepository($entityClass);
        return $entityRepository->findOneBy($crit);
    }

    /**
     * This function returns the HtmlElements to display for 1:n relations
     *
     * @todo this only works with single valued identifiers
     * @param array $assocMapping Mapping information for this relation
     * @param string $entityClass FQCN of the foreign entity
     * @param int $entityId ID of the local entity
     * @param arrray $options Options for this view
     * @return array Set of \Cx\Core\Html\Model\Entity\HtmlElement instances
     */
    protected function getIdentifyingDisplayValue($assocMapping, $entityClass, $entityId, $options) {
        global $_CORELANG;

        $localEntityMetadata = \Env::get('em')->getClassMetadata($this->entityClass);
        $localEntityIdentifierField = $localEntityMetadata->getSingleIdentifierFieldName();
        $localEntityRepo = \Env::get('em')->getRepository($this->entityClass);
        $localEntity = $localEntityRepo->find($entityId);
        if (!$localEntity) {
            throw new \Exception('Entity not found');
        }

        $methodBaseName = \Doctrine\Common\Inflector\Inflector::classify(
            $assocMapping['fieldName']
        );
        $foreignEntityGetter = 'get' . $methodBaseName;
        
        $htmlElements = array();
        $noOfRelatedEntries = 0;
        $maxEntriesPerPage = \Cx\Core\Setting\Controller\Setting::getValue(
            'corePagingLimit',
            'Config'
        );
        if (isset($options['length'])) {
            $maxEntriesPerPage = $options['length'];
        }
        $foreignEntities = $localEntity->$foreignEntityGetter();
        // if association is EXTRA_LAZY: limit! --> slice() only works with EXTRA_LAZY
        if ($assocMapping['fetch'] == \Doctrine\ORM\Mapping\ClassMetadata::FETCH_EXTRA_LAZY) {
            $page = 0; // paging is not yet implemented
            $foreignEntities = $foreignEntities->slice(
                $page * $maxEntriesPerPage,
                $maxEntriesPerPage
            );
            $noOfRelatedEntries = $localEntity->$foreignEntityGetter()->count();
        }

        foreach ($foreignEntities as $index=>$foreignEntity) {
            // entity base implements __toString()
            $displayValue = (string) $foreignEntity;

            $foreignEntityMetadata = \Env::get('em')->getClassMetadata(get_class($foreignEntity));
            $entityValueSerialized = 'vg_increment_number=' . $this->formId;
            $fieldsToParse = $foreignEntityMetadata->fieldNames;
            foreach ($fieldsToParse as $dbColName=>$fieldName) {
                $entityValueSerialized .= '&' . $fieldName . '=' . $this->getDataElementValueAsString(
                    $foreignEntityMetadata->getFieldValue(
                        $foreignEntity,
                        $fieldName
                    )
                );
            }

            // add relations
            foreach ($foreignEntityMetadata->associationMappings as $foreignAssocMapping) {
                if (!$foreignAssocMapping['isOwningSide']) {
                    continue;
                }
                $joinColumns = reset($foreignAssocMapping['joinColumns']);

                // if the association is a backreference to our main entity we skip it
                if (
                    $foreignAssocMapping['targetEntity'] == $this->entityClass &&
                    $joinColumns['referencedColumnName'] == $localEntityIdentifierField
                ) {
                    continue;
                }

                // get the second foreign entity (A->B->C)
                $foreignForeignEntity = $foreignEntityMetadata->getFieldValue(
                    $foreignEntity,
                    $foreignAssocMapping['fieldName']
                );
                if (!$foreignForeignEntity) {
                    continue;
                }

                // add C's relation to B to the data
                $joinColumns = $foreignAssocMapping['targetToSourceKeyColumns'];
                // C.$targetColumn = B.$sourceColumn
                foreach ($joinColumns as $targetColumn=>$sourceColumn) {
                    $methodBaseName = \Doctrine\Common\Inflector\Inflector::classify(
                        $targetColumn
                    );
                    $foreignEntityIdentifierGetter = 'get' . $methodBaseName;
                    // N:N relations don't have a getter with that name
                    if (!method_exists($foreignForeignEntity, $foreignEntityIdentifierGetter)) {
                        continue;
                    }
                    $entityValueSerialized .= '&' . $foreignAssocMapping['fieldName'] . '=' . $foreignForeignEntity->$foreignEntityIdentifierGetter();
                }
            }

            $sorroundingDiv = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
            $sorroundingDiv->setAttribute('class', 'oneToManyEntryRow');
            $displaySpan = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
            $displaySpan->addChild(new \Cx\Core\Html\Model\Entity\TextElement($displayValue));
            $displaySpan->allowDirectClose(false);
            $hiddenInput = new \Cx\Core\Html\Model\Entity\DataElement('input');
            $hiddenInput->setAttributes(
                array(
                    'type'        => 'hidden',
                    'name'        => $this->createCssClassNameFromEntity($entityClass).'[]',
                    'value'       => $entityValueSerialized
                )
            );
            $editLink = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
            $editLink->setAttributes(
                array(
                    'class'       => 'edit',
                    'title'       => $_CORELANG['TXT_EDIT']
                )
            );
            $editLink->allowDirectClose(false);

            $deleteLink = new \Cx\Core\Html\Model\Entity\HtmlElement('a');
            $deleteLink->setAttributes(
                array(
                    'onclick'     => 'deleteAssociationMappingEntry(this)',
                    'class'       => 'remove existing',
                    'title'       => $_CORELANG['TXT_DELETE']
                )
            );
            $deleteLink->allowDirectClose(false);

            $sorroundingDiv->addChild($displaySpan);
            $sorroundingDiv->addChild($hiddenInput);
            $sorroundingDiv->addChild($editLink);
            $sorroundingDiv->addChild($deleteLink);
            $htmlElements[] = $sorroundingDiv;
        }
        if (
            $assocMapping['fetch'] == \Doctrine\ORM\Mapping\ClassMetadata::FETCH_EXTRA_LAZY &&
            $noOfRelatedEntries > $maxEntriesPerPage
        ) {
            $tooltipTrigger = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
            $tooltipTrigger->setAttribute('class', 'icon-info tooltip-trigger');
            $tooltipTrigger->allowDirectClose(false);
            $tooltipMessage = new \Cx\Core\Html\Model\Entity\HtmlElement('span');
            $tooltipMessage->setAttribute('class', 'tooltip-message');
            $tooltipMessage->addChild(new \Cx\Core\Html\Model\Entity\TextElement(
                $_CORELANG['TXT_CORE_RECORD_RELATION_LIMITED']
            ));
            $htmlElements[0]->addChild($tooltipTrigger);
            $htmlElements[0]->addChild($tooltipMessage);
        }
        return $htmlElements;
    }


    public function getId() {
        return $this->formId;
    }

    public function getForm() {
        return $this->form;
    }

    public function isValid() {
        return $this->form->isValid();
    }

    public function getData() {
        return $this->form->getData();
    }

    public function getEntity() {
        return $this->entity;
    }

    public function render() {
        return $this->form->render();
    }

    public function __toString() {
        return $this->render();
    }

    protected static function getFormLabel($fieldOptions, $key) {
        global $_ARRAYLANG;

        if (isset($_ARRAYLANG[$fieldOptions[$key]])) {
            $fieldHeader = $_ARRAYLANG[$fieldOptions[$key]];
        } else {
            $fieldHeader = $fieldOptions[$key];
        }
        return $fieldHeader;
    }
}
