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

    public function __construct($entity, $actionUrl = null, $entityClass = '', $title = '', $options = array(), $entityId=0, $componentOptions) {
        $this->componentOptions = $componentOptions;
        $this->formId = static::$formIncrement;
        static::$formIncrement++;
        $this->options = $options;
        $this->entity = $entity;
        // Remove the virtual element from array
        unset($entity['virtual']);
        if (empty($entityClass) && is_object($entity)) {
            $entityClass = get_class($entity);
        }
        $this->entityClass = $entityClass;
        if (empty($title)) {
            $title = $entityClass;
        }
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
        $this->form->setAttribute('id', 'form-' . $this->formId);
        $this->form->setAttribute('class', 'cx-ui');
        $titleElement = new \Cx\Core\Html\Model\Entity\HtmlElement('legend');
        $titleElement->addChild(new \Cx\Core\Html\Model\Entity\TextElement($title));
        $this->form->addChild($titleElement);
        // @todo replace this by auto-find editid
        if (isset($_REQUEST['editid'])) {
            $editIdField = new \Cx\Core\Html\Model\Entity\DataElement('editid', contrexx_input2raw($_REQUEST['editid']), 'input');
            $editIdField->setAttribute('type', 'hidden');
            $this->form->addChild($editIdField);
        }
        // foreach entity field
        foreach ($entity as $field=>$value) {
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
            $dataElement = $this->getDataElement($field, $type, $length, $value, $fieldOptions, $entityId);
            if (empty($dataElement)) {
                continue;
            }
            $dataElement->setAttribute('id', 'form-' . $this->formId . '-' . $field);
            if ($type == 'hidden') {
                $element = $dataElement;
            } else {
                $element = $this->getDataElementGroup($field, $dataElement, $fieldOptions);
            }
            $this->form->addChild($element);
        }
        if (isset($options['cancelUrl'])) {
            $this->form->cancelUrl = $options['cancelUrl'];
        }
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

        $group = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
        $group->setAttribute('class', 'group');
        $label = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
        $label->setAttribute('for', 'form-' . $this->formId . '-' . $field);
        $fieldHeader = $field;
        if (isset($fieldOptions['formtext'])){
            $fieldHeader = FormGenerator::getFormLabel($fieldOptions, 'formtext');
        } else if (isset($fieldOptions['header'])) {
            $fieldHeader = FormGenerator::getFormLabel($fieldOptions, 'header');
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
     * @param string $type type of the DataElement
     * @param int $length length of the DataElement
     * @param mixed $value value of the DataElement
     * @param array $options options for the DataElement
     * @param int $entityId id of the DataElement
     * @return \Cx\Core\Html\Model\Entity\DataElement
     */
    public function getDataElement($name, $type, $length, $value, &$options, $entityId) {
        global $_ARRAYLANG, $_CORELANG;
        if (isset($options['formfield'])) {
            $formFieldGenerator = $options['formfield'];
            $formField = '';
            /* We use json to do the callback. The 'else if' is for backwards compatibility so you can declare
             * the function directly without using json. This is not recommended and not working over session */
            if (
                is_array($formFieldGenerator) &&
                isset($formFieldGenerator['adapter']) &&
                isset($formFieldGenerator['method'])
            ) {
                $json = new \Cx\Core\Json\JsonData();
                $jsonResult = $json->data(
                    $formFieldGenerator['adapter'],
                    $formFieldGenerator['method'],
                    array(
                        'name' => $name,
                        'type' => $type,
                        'length' => $length,
                        'value' => $value,
                        'options' => $options,
                    )
                );
                if ($jsonResult['status'] == 'success') {
                    $formField = $jsonResult["data"];
                }
            } else if (is_callable($formFieldGenerator)){
                $formField = $formFieldGenerator($name, $type, $length, $value, $options);
            }

            if (is_a($formField, 'Cx\Core\Html\Model\Entity\HtmlElement')) {
                return $formField;
            } else {
                $value = $formField;
            }
        }
        if (isset($options['showDetail']) && $options['showDetail'] === false) {
            return '';
        }
        switch ($type) {
            case 'bool':
            case 'boolean':
                // yes/no checkboxes
                $fieldset = new \Cx\Core\Html\Model\Entity\HtmlElement('div');
                $inputYes = new \Cx\Core\Html\Model\Entity\DataElement($name, 'yes');
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
                $inputNo = new \Cx\Core\Html\Model\Entity\DataElement($name, 'no');
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
                    $name,
                    $value,
                    \Cx\Core\Html\Model\Entity\DataElement::TYPE_INPUT,
                    new \Cx\Core\Validate\Model\Entity\RegexValidator(
                        '/-?[0-9]*/'
                    )
                );
                if (isset($options['attributes'])) {
                    $inputNumber->setAttributes($options['attributes']);
                }
                $inputNumber->setAttribute('type', 'number');
                return $inputNumber;
                break;
            case 'Cx\Model\Base\EntityBase':
                $associatedClass = get_class($value);
                \JS::registerJS('core/Html/View/Script/Backend.js');
                \ContrexxJavascript::getInstance()->setVariable(
                    'Form/Error',
                    $_ARRAYLANG['TXT_CORE_HTML_FORM_VALIDATION_ERROR'],
                    'core/Html/lang'
                );
                if (\Env::get('em')->getClassMetadata($this->entityClass)->isSingleValuedAssociation($name)) {
                    // this case is used to create a select field for 1 to 1 associations
                    $entities = \Env::get('em')->getRepository($associatedClass)->findAll();
                    $foreignMetaData = \Env::get('em')->getClassMetadata($associatedClass);
                    $primaryKeyName = $foreignMetaData->getSingleIdentifierFieldName();
                    $selected = $foreignMetaData->getFieldValue($value, $primaryKeyName);
                    $arrEntities = array();
                    $closeMetaData = \Env::get('em')->getClassMetadata($this->entityClass);
                    $assocMapping = $closeMetaData->getAssociationMapping($name);
                    $validator = null;
                    if (!isset($assocMapping['joinColumns'][0]['nullable']) || $assocMapping['joinColumns'][0]['nullable']) {
                        $arrEntities['NULL'] = $_ARRAYLANG['TXT_CORE_NONE'];
                    } else {
                        $validator = new \Cx\Core\Validate\Model\Entity\RegexValidator('/^(?!null$|$)/');
                    }
                    foreach ($entities as $entity) {
                        $arrEntities[\Env::get('em')->getClassMetadata($associatedClass)->getFieldValue($entity, $primaryKeyName)] = $entity;
                    }
                    $select = new \Cx\Core\Html\Model\Entity\DataElement(
                        $name,
                        \Html::getOptions($arrEntities, $selected),
                        \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT,
                        $validator
                    );
                    if (isset($options['attributes'])) {
                        $select->setAttributes($options['attributes']);
                    }
                    return $select;
                } else {
                    // this case is used to list all existing values and show an add button for 1 to many associations
                    $closeMetaData = \Env::get('em')->getClassMetadata($this->entityClass);
                    $assocMapping = $closeMetaData->getAssociationMapping($name);
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
                    if (!isset($_SESSION['vgOptions'])) {
                        $_SESSION['vgOptions'] = array();
                    }
                    $_SESSION['vgOptions'][$this->entityClass] = $this->componentOptions;
                    if ($entityId != 0) {
                        // if we edit the main form, we also want to show the existing associated values we already have
                        $existingValues = $this->getIdentifyingDisplayValue($assocMapping, $associatedClass, $entityId);
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
                break;
            case 'Country':
                // this is for customizing only:
                $data = \Cx\Core\Country\Controller\Country::getNameById($value);
                if (empty($data)) {
                    $value = 204;
                }
                $options = \Cx\Core\Country\Controller\Country::getMenuoptions($value);
                $select = new \Cx\Core\Html\Model\Entity\DataElement(
                    $name,
                    $options,
                    \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT
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
                $input = new \Cx\Core\Html\Model\Entity\DataElement($name, $value);
                $input->setAttribute('type', 'text');
                $input->setAttribute('class', 'datepicker');
                if (isset($options['readonly']) && $options['readonly']) {
                    $input->setAttribute('disabled');
                }
                if (isset($options['attributes'])) {
                    $input->setAttributes($options['attributes']);
                }
                \DateTimeTools::addDatepickerJs();
                \JS::registerCode('
                        cx.jQuery(function() {
                          cx.jQuery(".datepicker").datetimepicker();
                        });
                        ');
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
                $selectOptions = \Html::getOptions($values, $value);
                $select = new \Cx\Core\Html\Model\Entity\DataElement(
                    $name,
                    $selectOptions,
                    \Cx\Core\Html\Model\Entity\DataElement::TYPE_SELECT
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
                    $name,
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
                    cx.jQuery("#form-' . $this->formId . '-' . $name . ' .slider").slider({
                        value: ' . ($value+0) . ',
                        min: ' . ($min+0) . ',
                        max: ' . ($max+0) . ',
                        slide: function( event, ui ) {
                            cx.jQuery("input[name=' . $name . ']").val(ui.value);
                            cx.jQuery("input[name=' . $name . ']").change();
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
                    $name,
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
                $textarea->setAttribute('name', $name);
                if (isset($options['readonly']) && $options['readonly']) {
                    $textarea->setAttribute('disabled');
                }
                $textarea->addChild(new \Cx\Core\Html\Model\Entity\TextElement($value));
                if (isset($options['attributes'])) {
                    $textarea->setAttributes($options['attributes']);
                }
                return $textarea;
                break;
            case 'phone':
                // input field with type phone
                $input = new \Cx\Core\Html\Model\Entity\DataElement($name, $value);
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
                $input = new \Cx\Core\Html\Model\Entity\DataElement($name, $value, 'input', $emailValidator);
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
                                cx.jQuery("#'.$name.'").val(data.data[0].datainfo.filepath);
                        }
                    }

                ');
                $mediaBrowser = new \Cx\Core_Modules\MediaBrowser\Model\Entity\MediaBrowser();
                $mediaBrowser->setOptions(array('type' => 'button'));
                $mediaBrowser->setCallback('javascript_callback_function');
                $mediaBrowser->setOptions(
                    array(
                        'data-cx-mb-views' => 'filebrowser,uploader',
                        'id' => 'page_target_browse',
                        'cxMbStartview' => 'MediaBrowserList'
                    )
                );

                $input = new \Cx\Core\Html\Model\Entity\DataElement($name, $value);
                $input->setAttribute('type', 'text');
                $input->setAttribute('id', $name);

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
                            cx.jQuery("#'.$name.'").attr(\'value\', data.data[0].datainfo.filepath);
                            cx.jQuery("#'.$name.'").prevAll(\'.deletePreviewImage\').first().css(\'display\', \'inline-block\');
                            cx.jQuery("#'.$name.'").prevAll(\'.previewImage\').first().attr(\'src\', data.data[0].datainfo.filepath);
                        }
                    }

                    jQuery(document).ready(function(){
                        jQuery(\'.deletePreviewImage\').click(function(){
                            cx.jQuery("#'.$name.'").attr(\'value\', \'\');
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
                    'data-cx-mb-views' => 'filebrowser,uploader',
                    'id' => 'page_target_browse',
                    'cxMbStartview' => 'MediaBrowserList'
                );

                $mediaBrowser->setOptions(
                    is_array($options['options'])?array_merge($defaultOptions,$options['options']):$defaultOptions
                );

                // create hidden input to save image
                $input = new \Cx\Core\Html\Model\Entity\DataElement($name, $value);
                $input->setAttribute('type', 'hidden');
                $input->setAttribute('id', $name);

                $div = new \Cx\Core\Html\Model\Entity\HtmlElement('div');

                if((isset($value) && in_array(pathinfo($value, PATHINFO_EXTENSION), Array('gif', 'jpg', 'png'))) || $name == 'imagePath'){

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
                $textarea->setAttribute('name', $name);
                $textarea->setAttribute('id', $name);
                $textarea->setAttribute('style', 'display:none;');
                $textarea->addChild(new \Cx\Core\Html\Model\Entity\TextElement($value));

                //define pre
                $pre = new \Cx\Core\Html\Model\Entity\HtmlElement('pre');
                $pre->setAttribute('id','editor-'.$name);
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
if (\$J("#editor-$name").length) {
    editor = ace.edit("editor-$name");
    editor.getSession().setMode("ace/mode/$mode");
    editor.setShowPrintMargin(false);
    editor.focus();
    editor.gotoLine(1);
    $readonly
}

\$J('form').submit(function(){
    \$J('#$name').val(editor.getSession().getValue());
});

});
CODE;
                \JS::activate('ace');
                \JS::registerCode($jsCode);

                return $div;
                break;
            case 'string':
            case 'hidden':
            default:
                // convert NULL to empty string
                if (is_null($value)) {
                    $value = '';
                }
                // input field with type text
                $input = new \Cx\Core\Html\Model\Entity\DataElement($name, $value);
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
                return $input;
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
     * This function returns the HtmlElements to display for 1:n relations
     *
     * @todo this only works with single valued identifiers
     * @param array $assocMapping Mapping information for this relation
     * @param string $entityClass FQCN of the foreign entity
     * @param int $entityId ID of the local entity
     * @return array Set of \Cx\Core\Html\Model\Entity\HtmlElement instances
     */
    protected function getIdentifyingDisplayValue($assocMapping, $entityClass, $entityId) {
        global $_CORELANG;

        $localEntityMetadata = \Env::get('em')->getClassMetadata($this->entityClass);
        $localEntityIdentifierField = $localEntityMetadata->getSingleIdentifierFieldName();
        $localEntityRepo = \Env::get('em')->getRepository($this->entityClass);
        $localEntity = $localEntityRepo->find($entityId);
        if (!$localEntity) {
            throw new \Exception('Entity not found');
        }

        $foreignEntityGetter = 'get'.preg_replace('/_([a-z])/', '\1', ucfirst($assocMapping["fieldName"]));
        $foreignEntities = $localEntity->$foreignEntityGetter();

        $htmlElements = array();
        foreach ($foreignEntities as $index=>$foreignEntity) {
            // entity base implements __toString()
            $displayValue = (string) $foreignEntity;

            $foreignEntityMetadata = \Env::get('em')->getClassMetadata(get_class($foreignEntity));
            $foreignEntityIdentifierField = $foreignEntityMetadata->getSingleIdentifierFieldName();
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

                $foreignForeignEntity = $foreignEntityMetadata->getFieldValue(
                    $foreignEntity,
                    $foreignAssocMapping['fieldName']
                );
                if (!$foreignForeignEntity) {
                    continue;
                }
                $foreignEntityIdentifierGetter = 'get'.preg_replace('/_([a-z])/', '\1', ucfirst($foreignEntityIdentifierField));
                $entityValueSerialized .= '&' . $foreignAssocMapping['fieldName'] . '=' . $foreignForeignEntity->$foreignEntityIdentifierGetter();
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
