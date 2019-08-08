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
 * Class FormTemplate
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_contact
 */

namespace Cx\Core_Modules\Contact\Model\Entity;

/**
 * Class FormTemplateException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_contact
 */
class FormTemplateException extends \Exception {}

/**
 * Class FormTemplate
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_contact
 */
class FormTemplate extends \Cx\Model\Base\EntityBase {
    /**
     * @var string
     */
    const USER_PROFILE_REGEXP = '/\{([A-Z_]+)\}/';

    /**
     * @var Cx\Core\ContentManager\Model\Entity\Page
     */
    protected $page;

    /**
     * @var Cx\Core_Modules\Contact\Model\Entity\Form
     */
    protected $form;

    /**
     * @var Cx\Core\View\Model\Entity\Theme
     */
    protected $theme;

    /**
     * @var Cx\Core\Html\Sigma
     */
    protected $template;

    /**
     * @var integer
     */
    protected $langId;

    /**
     * @var array
     */
    protected $fieldTemplates = array();

    /**
     * @var boolean
     */
    protected $hasFileField = false;

    /**
     * @var string
     */
    protected $uploaderCode = '';

    /**
     * @var string
     */
    protected $blockPrefix = 'contact_form_field_';

    /**
     * @var \Cx\Core_Modules\Contact\Controller\ContactLib
     */
    protected $contactLib;

    /**
     * Constructor
     *
     * @param Form                                      $form  Form instance
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page  Resolved page instance
     * @param \Cx\Core\View\Model\Entity\Theme          $theme Theme instance
     */
    public function __construct(
        Form $form,
        \Cx\Core\ContentManager\Model\Entity\Page $page = null,
        \Cx\Core\View\Model\Entity\Theme $theme = null
    ) {
        if (
            !$theme &&
            $this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND
        ) {
            throw new FormTemplateException(
                'Failed to initialize the FormTemplate: Theme is not set.'
            );
        }
        global $_LANGID;

        // Initialize the class variables and Form template
        $this->form   = $form;
        $this->page   = $page;
        $this->theme  = $theme;
        $this->langId = $_LANGID;
        $this->initContactForm();
        $this->initTemplate();
    }

    /**
     * Initialize the Contact Form
     */
    protected function initContactForm()
    {
        if (!$this->contactLib) {
            $this->contactLib = new \Cx\Core_Modules\Contact\Controller\ContactLib();
        }

        $this->contactLib->initContactForms($this->form->getId());
    }

    /**
     * Initialize the submission form and form field templates
     */
    public function initTemplate()
    {
        $this->template = new \Cx\Core\Html\Sigma(
            $this->getDirectory() . '/View/Template/Frontend'
        );
        $this->template->setErrorHandling(PEAR_ERROR_DIE);

        if ($this->page) {
            $this->template->setTemplate($this->page->getContent());
        } else {
            $this->template->setTemplate($this->getTemplateContent('Form'));
        }

        if ($this->template->placeholderExists('APPLICATION_DATA')) {
            // Load the Form template from 'theme specific form template' for eg:
            // (/themes/<theme>/core_modules/Contact/Template/Frontend/Form.html)
            // or 'default form template' for eg:
            // (/core_modules/Contact/View/Template/Frontend/Form.html)
            $formTemplate = $this->getTemplateContent('Form');
            \LinkGenerator::parseTemplate($formTemplate);
            $this->template->addBlock(
                'APPLICATION_DATA',
                'application_data',
                $formTemplate
            );
        }

        // Check if the loaded form has form fields otherwise return.
        $formFields = $this->contactLib->getFormFields($this->form->getId());
        if (!$formFields) {
            return;
        }

        // Load form fields template content
        foreach ($formFields as $arrField) {
            $fieldType = $arrField['type'];
            if ($fieldType == 'special') {
                $fieldType = $arrField['special_type'];
            }
            $fieldTemplateContent = $this->getFormFieldContent(
                $arrField['type'],
                $arrField['special_type']
            );
            $fieldTemplate = new FormFieldTemplate();
            $fieldTemplate->setContent($fieldTemplateContent);
            $this->fieldTemplates[$fieldType] = $fieldTemplate;
        }
    }

    /**
     * Get Form field Content by using template-block contact_form_field_<TYPE> or
     * 'theme specific form field template' or 'default form field template'
     *
     * @param string $fieldType   Form Field type
     * @param string $specialType Form Field special type
     * @return string Content of formfield
     */
    protected function getFormFieldContent($fieldType, $specialType)
    {
        $customBlock = $this->blockPrefix . $fieldType;
        if ($fieldType == 'special') {
            $customBlock = $this->blockPrefix . $specialType;
        }

        // Check if the template block 'contact_form_field_<TYPE>' exists
        if ($this->template->blockExists($customBlock)) {
            return $this->template->getUnparsedBlock($customBlock);
        }

        $fileName = 'Field' . ucfirst($fieldType);
        if ($fieldType == 'multi_file') {
            $fileName = 'FieldMultiFile';
        }

        // Get form field content from theme specific form field template
        // if the form field template exists in theme
        // (/themes/<theme>/core_modules/Contact/Template/Frontend/FieldText.html),
        // otherwise from default form field template(core_modules/Contact/View/Template/Frontend/)
        $fieldTemplateContent = $this->getTemplateContent($fileName);
        if ($fieldType != 'special') {
            return $fieldTemplateContent;
        }

        // The special form field template(FieldSpecial.html) have two blocks:
        // 'contact_form_special_field_select' and 'contact_form_special_field_input'
        // The block 'contact_form_special_field_select' content for the following
        // special fields: 'access_title', 'access_gender', 'access_country'.
        // The remaining special fields using the block
        // 'contact_form_special_field_input' content.
        $specialBlock = $this->blockPrefix . 'special_input';
        if (
            in_array(
                $specialType,
                array('access_title', 'access_gender', 'access_country')
            )
        ) {
            $specialBlock = $this->blockPrefix . 'special_select';
        }

        $matches = array();
        preg_match(
            '#(?:<!--\s*BEGIN\s+(' . $specialBlock . ')\s*-->(.*?)<!--\s*END\s+\1\s*-->)#s',
            $fieldTemplateContent,
            $matches
        );

        if (isset($matches[2])) {
            return $matches[2];
        }

        return '';
    }

    /**
     * Get Form/Form Field template content
     *
     * @param string $fileName Name of the file
     * @return string Template content
     */
    protected function getTemplateContent($fileName)
    {
        if (empty($fileName)) {
            return '';
        }

        if ($this->theme) {
            // Check and return file content if the file exists in the theme.
            $themePath = $this->cx->getClassLoader()->getFilePath(
                $this->cx->getWebsiteThemesPath() . '/' . $this->theme->getFoldername() .
                '/' . $this->getDirectory(false, true) . '/Template/Frontend/'
                . $fileName . '.html'
            );
            if ($themePath) {
                return file_get_contents($themePath);
            }
        }

        // Check and return file content if the file exists in the Contact Component.
        $defaultPath = $this->cx->getClassLoader()->getFilePath(
            $this->getDirectory(false) . '/View/Template/Frontend/'
            . $fileName . '.html'
        );
        return file_get_contents($defaultPath);
    }

    /**
     * To hide the block formText
     */
    public function hideFormText()
    {
        if ($this->template->blockExists('formText')) {
            $this->template->hideBlock('formText');
        }
    }

    /**
     * To show the block formText
     */
    public function showFormText()
    {
        if ($this->template->blockExists('formText')) {
            $this->template->touchBlock('formText');
        }
    }

    /**
     * To hide the submission form
     */
    public function hideForm()
    {
        if ($this->template->blockExists('contact_form')) {
            $this->template->hideBlock('contact_form');
        }
    }

    /**
     * Set Captcha content
     *
     * @param boolean $useCaptcha If it is true then captcha will be shown
     *                            otherwise not
     */
    public function setCaptcha($useCaptcha)
    {
        global $_CORELANG;

        if (!$this->template->blockExists('contact_form_captcha')) {
            return;
        }

        if (!$useCaptcha) {
            $this->template->hideBlock('contact_form_captcha');
            return;
        }
        $this->template->setVariable(array(
            'TXT_CONTACT_CAPTCHA'  => $_CORELANG['TXT_CORE_CAPTCHA'],
            'CONTACT_CAPTCHA_CODE' =>
            \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
        ));
        $this->template->parse('contact_form_captcha');
    }

    /**
     * Get the submission form Content
     *
     * @param boolean $show If it is true then source code of form will be return
     *                      otherwise form content will be return
     * @return string Form content
     */
    public function getHtml($show = false)
    {
        if ($show) {
            return preg_replace(
                '/\{([A-Z0-9_-]+)\}/',
                '[[\\1]]',
                $this->getSourceCode()
            );
        }
        return $this->template->get();
    }

    /**
     * Parse Submission form and its form field values
     */
    public function parseFormTemplate()
    {
        global $_ARRAYLANG;

        \JS::activate('cx');
        $formId      = $this->form->getId();
        $formFields  = $this->contactLib->getFormFields($formId);
        $profileData = $this->getProfileData();
        $this->handleUniqueId();

        // Check if the loaded form has form fields and
        // the template block 'contact_form' is exists otherwise return empty.
        if (!$formFields || !$this->template->blockExists('contact_form')) {
            return;
        }

        // Parse Form related values
        $formName = $this->contactLib->arrForms[$formId]['lang'][$this->langId]['name'];
        $formText = $this->contactLib->arrForms[$formId]['lang'][$this->langId]['text'];
        $actionUrl = \Cx\Core\Routing\Url::fromModuleAndCmd(
            'Contact',
            $formId,
            $this->langId
        );
        $customStyleId = '';
        if ($this->contactLib->arrForms[$formId]['useCustomStyle'] > 0) {
            $customStyleId = '_' . $formId;
        }
        $this->template->setGlobalVariable($profileData);
        $this->template->setVariable(array(
            'CONTACT_FORM_ACTION' => $actionUrl->toString(false),
            'CONTACT_FORM_CUSTOM_STYLE_ID' => $customStyleId,
        ));

        // Parse FormField related values
        foreach ($formFields as $fieldId => $arrField) {
            // Set Form name and its description
            $this->template->setVariable(array(
                $formId . '_FORM_TEXT' => $formText,
                $formId . '_FORM_NAME' => contrexx_raw2xhtml($formName),
                'CONTACT_FORM_NAME'    => contrexx_raw2xhtml($formName),
                'CONTACT_FORM_TEXT'    => $formText,
            ));
            // Set values for special field types
            $this->setSpecialFieldValue($arrField, $fieldId);

            $fieldLabel = '&nbsp;';
            if (!empty($arrField['lang'][$this->langId]['name'])) {
                $fieldLabel = $arrField['lang'][$this->langId]['name'];
            }

            // Check if the placeholder {<ID>_VALUE} or {<ID>_LABEL} exists,
            // if so, parse its content directly
            if (
                $this->template->placeholderExists($fieldId . '_VALUE') ||
                $this->template->placeholderExists($fieldId . '_LABEL')
            ) {
                $this->parseFormField($this->template, $fieldId, $arrField, $profileData);
                continue;
            } elseif ($this->template->blockExists($this->blockPrefix . $fieldId)) {
                // check if the template-block contact_form_field_<ID> exists
                // if so, parse that block content
                $content = $this->template->getUnparsedBlock($this->blockPrefix . $fieldId);
            } else {
                // Use the content of the associated object of
                // $this->fieldTemplates for parsing
                $fieldType = $arrField['type'];
                if ($fieldType == 'special') {
                    $fieldType = $arrField['special_type'];
                }
                $content = $this->fieldTemplates[$fieldType]->getContent();
            }
            if ($content) {
                $template = new \Cx\Core\Html\Sigma('.');
                $template->setErrorHandling(PEAR_ERROR_DIE);
                $template->setTemplate($content);
                $template->setGlobalVariable($profileData);
                $this->template->setVariable(
                    'CONTACT_FORM_FIELD',
                    $this->parseFormField($template, $fieldId, $arrField, $profileData, true)
                );
                $this->template->parse($this->blockPrefix . 'list');
            }
        }

        // Use stylesheet 'form.css' if the form is loaded for the preview in backend
        if (
            $this->template->blockExists('contact_form_css_link') &&
            $this->cx->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND
        ) {
            $this->template->setVariable(
                'CONTACT_FORM_CSS_HREF',
                $this->cx->getCodeBaseCoreModuleWebPath() .
                '/Contact/View/Style/form.css'
            );
            $this->template->parse('contact_form_css_link');
        } else {
            if ($this->template->blockExists('contact_form_css_link')) {
                $this->template->hideBlock('contact_form_css_link');
            }
        }

        // Parse language text and JS source code for form validation, uploader code.
        $jsSourceCode =
            $this->contactLib->_getJsSourceCode($formId, $formFields) . $this->uploaderCode;
        $this->template->setVariable(array(
            'CONTACT_JAVASCRIPT'  => $jsSourceCode,
            'TXT_NEW_ENTRY_ERORR' => $_ARRAYLANG['TXT_NEW_ENTRY_ERORR'],
            'TXT_CONTACT_SUBMIT'  => $_ARRAYLANG['TXT_CONTACT_SUBMIT'],
            'TXT_CONTACT_RESET'   => $_ARRAYLANG['TXT_CONTACT_RESET'],
        ));
    }

    /**
     * Generates the HTML Source code of the Submission form designed in backend
     *
     * @return string Source code of the submission form
     */
    protected function getSourceCode()
    {
        $formContent        = $this->getTemplateContent('Form');
        $formFieldContent   = array();
        $formFieldContent[] =
            "<!-- BEGIN " . $this->blockPrefix . "list -->
            [[CONTACT_FORM_FIELD]]
            <!-- END " . $this->blockPrefix . "list -->
            ";
        foreach ($this->fieldTemplates as $type => $formField) {
            $content = $formField->getContent();
            foreach (
                array(
                    $this->blockPrefix . 'required',
                    $this->blockPrefix . 'options'
                ) as $block
            ) {
                $content = str_replace($block, $block . '_' . $type, $content);
            }
            $formFieldContent[] =
                "<!-- BEGIN " . $this->blockPrefix . $type . " -->\n" . $content .
                "\n<!-- END " . $this->blockPrefix . $type . " -->\n";
        }

        return preg_replace(
            '#(<!--\s*BEGIN\s+(' . $this->blockPrefix . 'list)\s*-->.*?<!--\s*END\s+\2\s*-->)#s',
            implode("\n", $formFieldContent),
            $formContent
        );
    }

    /**
     * Set values for special field types if the user is authenticated and
     * there is no value set through GET and POST
     *
     * @param array   $arrField Array of field values
     * @param integer $fieldId  Field ID
     */
    protected function setSpecialFieldValue(&$arrField, $fieldId)
    {
        if (
            !\FWUser::getFWUserObject()->objUser->login() ||
            $arrField['type'] != 'special' ||
            !empty($_GET[$fieldId]) ||
            !empty($_POST['contactFormField_' . $fieldId])
        ) {
            return;
        }

        if ($arrField['special_type'] == 'access_email') {
            $arrField['lang'][$this->langId]['value'] = '[[ACCESS_USER_EMAIL]]';
        } else {
            $value = str_replace('access_', '', $arrField['special_type']);
            $arrField['lang'][$this->langId]['value'] =
                '[[ACCESS_PROFILE_ATTRIBUTE_' . strtoupper($value) . ']]';
        }
    }

    /**
     * Parse Form Field
     *
     * @param \Cx\Core\Html\Sigma $template    Template object
     * @param integer             $fieldId     FormField ID
     * @param array               $arrField    Array of FormField values
     * @param array               $profileData Array of profile data
     * @param boolean             $return      If true return the template content
     *                                         otherwise not
     * @return string Parsed content of Form field
     */
    protected function parseFormField(
        \Cx\Core\Html\Sigma $template,
        $fieldId,
        $arrField,
        $profileData,
        $return = false
    ) {
        global $_ARRAYLANG;

        $regex = '/\[\[([A-Z0-9_]+)\]\]/';
        $fieldValue = preg_replace(
            $regex,
            '{$1}',
            $arrField['lang'][$this->langId]['value']
        );
        $fieldLabel = '&nbsp;';
        if (!empty($arrField['lang'][$this->langId]['name'])) {
            $fieldLabel = $arrField['lang'][$this->langId]['name'];
        }
        $fieldType = $arrField['type'];
        if ($fieldType == 'special') {
            $fieldType = $arrField['special_type'];
        }

        $parseLegacyPlaceholder =
            $template->placeholderExists($fieldId . '_VALUE') ||
            $template->placeholderExists($fieldId . '_LABEL');

        // Check if the template does not have the placeholders {<ID>_VALUE} and {<ID>_LABEL}
        // then check template have block like any one of the following formats:
        // 'contact_form_field_required' or 'contact_form_field_required_<Type>'
        // or 'contact_form_field_required_<ID> for required'
        if (!$parseLegacyPlaceholder) {
            $requiedBlock = $this->blockPrefix . 'required';
            if ($template->blockExists($requiedBlock . '_' . $fieldId)) {
                $requiedBlockName = $requiedBlock . '_' . $fieldId;
            } elseif ($template->blockExists($requiedBlock . '_' . $fieldType)) {
                $requiedBlockName = $requiedBlock . '_' . $fieldType;
            } else {
                $requiedBlockName = $requiedBlock;
            }

            if ($arrField['is_required']) {
                $template->touchBlock($requiedBlockName);
                $template->setVariable('CONTACT_FORM_FIELD_REQUIRED', 'required="required"');
            } else {
                $template->hideBlock($requiedBlockName);
            }
        }

        // Parse Form field Id and Label values.
        if ($parseLegacyPlaceholder) {
            $template->setVariable($fieldId . '_LABEL', $fieldLabel);
        } else {
            $template->setVariable(array(
                'CONTACT_FORM_FIELD_ID'    => $fieldId,
                'CONTACT_FORM_FIELD_LABEL' => $fieldLabel,
            ));
        }

        // Parse form field value based on its type.
        switch ($fieldType) {
            case 'checkbox':
                $checkboxSelected = '';
                if (
                    $fieldValue == 1 ||
                    !empty($_POST['contactFormField_' . $fieldId])
                ) {
                    $checkboxSelected = 'checked="checked"';
                }

                if ($parseLegacyPlaceholder) {
                    $selectPlaceholder = 'SELECTED_' . $fieldId;
                } else {
                    $selectPlaceholder = 'CONTACT_FORM_FIELD_CHECKBOX_SELECTED';
                }
                $template->setVariable($selectPlaceholder, $checkboxSelected);
                break;
            case 'checkboxGroup':
            case 'radio':
                $options = explode(',', $fieldValue);
                $this->parseFormFieldSelectOptions(
                    $template,
                    $fieldId,
                    $fieldType,
                    $options,
                    $parseLegacyPlaceholder,
                    $profileData
                );
                break;
            case 'access_title':
            case 'access_gender':
                // Collect user attribute options
                $arrOptions   = array();
                $objUser      = \FWUser::getFWUserObject()->objUser;
                $accessAttrId = str_replace('access_', '', $fieldType);
                $objAttribute = $objUser->objAttribute->getById($accessAttrId);

                // get options
                $arrAttribute = $objAttribute->getChildren();
                foreach ($arrAttribute as $attributeId) {
                    // In case the selection of the field is mandatory,
                    // we shall skip the unknown option of the user profile attribute
                    if (
                        $arrField['is_required'] &&
                        strpos($attributeId, '_undefined')
                    ) {
                        continue;
                    }
                    $objAttribute = $objUser->objAttribute->getById($attributeId);
                    $arrOptions[] = $objAttribute->getName($this->langId);
                }
                // Options will be used for select input generation
                $fieldValue = implode(',', contrexx_raw2xhtml($arrOptions));
            case 'select':
                $options = explode(',', $fieldValue);
                if ($arrField['is_required']) {
                    $options = array_merge(
                        array($_ARRAYLANG['TXT_CONTACT_PLEASE_SELECT']),
                        $options
                    );
                }
                $this->parseFormFieldSelectOptions(
                    $template,
                    $fieldId,
                    $fieldType,
                    $options,
                    $parseLegacyPlaceholder,
                    $profileData
                );
                break;
            case 'recipient':
                $recipients = $this->contactLib->getRecipients($this->form->getId());
                $options    = array();
                foreach ($recipients as $index => $recipient) {
                    $options[$index] = preg_replace($regex, '{$1}', $recipient['lang'][$this->langId]);
                }
                $this->parseFormFieldSelectOptions(
                    $template,
                    $fieldId,
                    $fieldType,
                    $options,
                    $parseLegacyPlaceholder,
                    $profileData
                );
                break;
            case 'access_country':
            case 'country':
                $matches = array();
                if (preg_match(static::USER_PROFILE_REGEXP, $fieldValue, $matches)) {
                    $fieldValue = $template->_globalVariables[$matches[1]];
                }
                $arrCountry = \Cx\Core\Country\Controller\Country::getNameArray(
                    true,
                    $this->langId
                );
                if (
                    $template->placeholderExists('TXT_CONTACT_NOT_SPECIFIED') ||
                    $template->placeholderExists('TXT_CONTACT_PLEASE_SELECT')
                ) {
                    // legacy
                    $template->setVariable(array(
                        'TXT_CONTACT_PLEASE_SELECT' => $_ARRAYLANG['TXT_CONTACT_PLEASE_SELECT'],
                        'TXT_CONTACT_NOT_SPECIFIED' => $_ARRAYLANG['TXT_CONTACT_NOT_SPECIFIED'],
                    ));
                    $options = $arrCountry;
                } else {
                    $defaultOption = $_ARRAYLANG['TXT_CONTACT_NOT_SPECIFIED'];
                    if ($arrField['is_required']) {
                        $defaultOption = $_ARRAYLANG['TXT_CONTACT_PLEASE_SELECT'];
                    }
                    $options = array_merge(array($defaultOption), $arrCountry);
                }
                $this->parseFormFieldSelectOptions(
                    $template,
                    $fieldId,
                    $fieldType,
                    $options,
                    $parseLegacyPlaceholder,
                    $profileData,
                    $fieldValue
                );
                break;
            case 'file':
                $this->hasFileField  = true;
                $this->uploaderCode .= $this->initUploader(
                    $template,
                    $fieldId,
                    $parseLegacyPlaceholder
                );
                break;
            case 'multi_file':
                $this->hasFileField  = true;
                $this->uploaderCode .= $this->initUploader(
                    $template,
                    $fieldId,
                    $parseLegacyPlaceholder,
                    false
                );
                break;
            case 'access_birthday':
                if (!$parseLegacyPlaceholder) {
                    $template->setVariable(
                        'CONTACT_FORM_FIELD_ADDITIONAL_CLASS',
                        'date'
                    );
                }
            default:
                $this->parseInputFieldValue(
                    $template,
                    $fieldId,
                    $fieldValue,
                    $parseLegacyPlaceholder,
                    $fieldType
                );
                break;
        }

        // Parse form field html5 type 
        $html5Type = '';
        switch ($fieldType) {
            case 'label':
            case 'country':
            case 'fieldset':
            case 'horizontalLine':
            case 'select':
            case 'textarea':
            case 'recipient':
            case 'access_gender':
            case 'access_country':
                $html5Type = '';
                break;

            case 'checkboxGroup':
                $html5Type = 'checkbox';
                break;

            case 'multi_file':
                $html5Type = 'file';
                break;

            case 'access_email':
                $html5Type = 'email';
                break;

            case 'access_title':
            case 'access_firstname':
            case 'access_lastname':
            case 'access_company':
            case 'access_address':
            case 'access_city':
            case 'access_zip':
            case 'access_profession':
            case 'access_interests':
            case 'access_signature':
            case 'datetime':
                $html5Type = 'text';
                break;
            
            case 'access_phone_office':
            case 'access_phone_private':
            case 'access_phone_mobile':
            case 'access_phone_fax':
                $html5Type = 'tel';
                break;

            case 'access_birthday':
                $html5Type = 'date';
                break;

            case 'access_website':
                $html5Type = 'url';
                break;

            case 'text':
                switch ($arrField['check_type']) {
                    case \Cx\Core_Modules\Contact\Controller\ContactLib::CHECK_TYPE_EMAIL:
                        $html5Type = 'email';
                        break 2;

                    case \Cx\Core_Modules\Contact\Controller\ContactLib::CHECK_TYPE_URL:
                        $html5Type = 'url';
                        break 2;

                    case \Cx\Core_Modules\Contact\Controller\ContactLib::CHECK_TYPE_INTEGER:
                        $html5Type = 'number';
                        break 2;

                    default:
                        break;
                }
                // intentionally no break here
                // if the check_type is non of the above,
                // then the HTML5 input type is 'text'

            default:
                $html5Type = $fieldType;
                break;
        }

        $template->setVariable(
            'CONTACT_FORM_FIELD_TYPE',
            $html5Type 
        );

        if ($return) {
            return $template->get();
        }
    }

    /**
     * Parse FormField's value
     *
     * @param \Cx\Core\Html\Sigma $template               Template object
     * @param integer             $fieldId                Field ID
     * @param string              $fieldType              Field type
     * @param array               $options                Field option values
     * @param boolean             $parseLegacyPlaceholder If true form has direct {<ID>_VALUE} and
     *                                                    {<ID>_LABEL} placeholders otherwise false
*      @param array               $profileData            Array of profile data
     * @param string              $fieldValue             Field value
     */
    protected function parseFormFieldSelectOptions(
        \Cx\Core\Html\Sigma $template,
        $fieldId,
        $fieldType,
        $options,
        $parseLegacyPlaceholder,
        $profileData,
        $fieldValue = ''
    ) {
        global $_ARRAYLANG;

        if (empty($options)) {
            return;
        }

        $isSpecialType = in_array($fieldType, array('access_title', 'access_gender'));
        $accessAttrId  = '';
        if ($isSpecialType) {
            $accessAttrId  =
                'ACCESS_PROFILE_ATTRIBUTE_' . str_replace('access_', '', $fieldType);
        }

        $selectedText = 'selected = "selected"';
        if (in_array($fieldType, array('radio', 'checkboxGroup'))) {
            $selectedText = 'checked = "checked"';
        }

        // Check if the template have the following placeholder {<ID>_VALUE} and {<ID>_LABEL}
        // then check the block 'field_<ID>' exists or not otherwise
        // check the template have block like any one of the following formats:
        // 'contact_form_field_options' or 'contact_form_field_options_<Type>'
        // or 'contact_form_field_options_<ID> or 'field_<ID>' for parsing option values'
        $blockName = $this->blockPrefix . 'options';
        if ($parseLegacyPlaceholder) {
            $optionBlockName = 'field_' . $fieldId;
        } else {
            if ($template->blockExists($blockName . '_' . $fieldId)) {
                $optionBlockName = $blockName . '_' . $fieldId;
            } elseif ($template->blockExists($blockName . '_' . $fieldType)) {
                $optionBlockName = $blockName . '_' . $fieldType;
            } else {
                $optionBlockName = $blockName;
            }
        }

        if (
            !$template->blockExists($optionBlockName) &&
            !in_array($fieldType, array('radio', 'checkboxGroup'))
        ) {
            return;
        }

        // Initialize the value and selected placeholder name
        if ($parseLegacyPlaceholder) {
            $valuePlaceholder  = $fieldId . '_VALUE';
            $selectPlaceholder = 'SELECTED_' . $fieldId;
        } else {
            $valuePlaceholder  = 'CONTACT_FORM_FIELD_VALUE';
            $selectPlaceholder = 'CONTACT_FORM_FIELD_SELECTED';
        }

        foreach ($options as $index => $option) {
            // legacy
            if ($template->placeholderExists($fieldId . '_' . $index . '_VALUE')) {
                $valuePlaceholder = $fieldId . '_' . $index . '_VALUE';
            }
            // legacy
            if ($template->placeholderExists('SELECTED_' . $fieldId . '_' . $index)) {
                $selectPlaceholder = 'SELECTED_' . $fieldId . '_' . $index;
            }

            // set value key to empty for the 'please select'-option
            $optionKey = $option;
            if ($index === 0 &&
                $optionKey === $_ARRAYLANG['TXT_CONTACT_PLEASE_SELECT']
            ) {
                $optionKey = '';
            }

            // Parse form field value
            if (preg_match(static::USER_PROFILE_REGEXP, $option)) {
                // Set form field value through User profile attribute
                $valuePlaceholderBlock =
                    'contact_value_placeholder_block_' . $fieldId . '_' . $index;
                $template->addBlock(
                    $valuePlaceholder,
                    $valuePlaceholderBlock,
                    $option
                );
                $template->touchBlock($valuePlaceholderBlock);
            } else {
                $template->setVariable($valuePlaceholder, $option);
            }

            if ($parseLegacyPlaceholder) {
                $template->setVariable($fieldId . '_VALUE_ID', $index);
            } else {
                $template->setVariable(array(
                    'CONTACT_FORM_FIELD_OPTION_KEY'      => $index,
                    'CONTACT_FORM_FIELD_OPTION_FIELD_ID' => $fieldId,
                    'CONTACT_FORM_FIELD_VALUE_KEY'       => $optionKey,
                ));
            }
            // Set selected or checked attribute to the form field based on
            // post, get and default value of that form field
            $valueFromPost = '';
            if (isset($_POST['contactFormField_' . $fieldId])) {
                $valueFromPost = contrexx_input2raw(
                    $_POST['contactFormField_' . $fieldId]
                );
            }
            $valueFromGet = '';
            if (isset($_GET[$fieldId])) {
                $valueFromGet = contrexx_input2raw($_GET[$fieldId]);
            }

            $isOptionInPost = !empty($valueFromPost) && (
                (
                    is_array($valueFromPost) &&
                    in_array($option, $valueFromPost)
                ) || (
                    !is_array($valueFromPost) && (
                        $option == $valueFromPost ||
                        strcasecmp($option, $valueFromPost) == 0
                    )
                )
            );
            $isOptionInGet =
                !empty($valueFromGet) &&
                (
                    $option == $valueFromGet ||
                    strcasecmp($option, $valueFromGet) == 0
                );
            $isOptionInAccessAttr =
                $isSpecialType &&
                isset($profileData[strtoupper($accessAttrId)]) &&
                $option == $profileData[strtoupper($accessAttrId)];
            if (
                $isOptionInPost ||
                $isOptionInGet ||
                $isOptionInAccessAttr ||
                $option == $fieldValue
            ) {
                $template->setVariable($selectPlaceholder, $selectedText);
            }
            if ($template->blockExists($optionBlockName)) {
                $template->parse($optionBlockName);
            }
        }
    }

    /**
     * Parse Input Field's value
     *
     * @param \Cx\Core\Html\Sigma $template               Template object
     * @param integer             $fieldId                Form Field ID
     * @param string              $fieldValue             Form Field value
     * @param boolean             $parseLegacyPlaceholder If true form has direct {<ID>_VALUE} and
     *                                                    {<ID>_LABEL} placeholders otherwise false
     */
    protected function parseInputFieldValue(
        \Cx\Core\Html\Sigma $template,
        $fieldId,
        $fieldValue,
        $parseLegacyPlaceholder,
        $fieldType
    ) {
        global $_ARRAYLANG;

        if ($parseLegacyPlaceholder) {
            $valuePlaceholder = $fieldId . '_VALUE';
        } else {
            $valuePlaceholder = 'CONTACT_FORM_FIELD_VALUE';
        }

        $fieldPlaceholder = $fieldValue;
        if (strpos($fieldType, 'access_') === 0) {
            $objUserAttribute = \FWUser::getFWUserObject()->objUser->objAttribute;
            $attributeId = str_replace('access_', '', $fieldType);
            if ($attributeId == 'email') {
                $fieldPlaceholder = $_ARRAYLANG['TXT_CONTACT_EMAIL'];
            } else {
                $fieldPlaceholder = $objUserAttribute->getById($attributeId)->getName();
            }
        }

        $template->setVariable(
            'CONTACT_FORM_FIELD_PLACEHOLDER',
            contrexx_raw2xhtml($fieldPlaceholder)
        );

        // Set default field value through User profile attribute
        if (preg_match(static::USER_PROFILE_REGEXP, $fieldValue)) {
            $valuePlaceholderBlock =
                'contact_value_placeholder_block_' . $fieldId;
            $template->addBlock(
                $valuePlaceholder,
                $valuePlaceholderBlock,
                contrexx_raw2xhtml($fieldValue)
            );
            $template->touchBlock($valuePlaceholderBlock);
            return;
        }

        if (!empty($_POST['contactFormField_' . $fieldId])) {
            $fieldValue = $_POST['contactFormField_' . $fieldId];
        } elseif (!empty($_GET[$fieldId])) {
            $fieldValue = $_GET[$fieldId];
        } elseif ($template->placeholderExists('CONTACT_FORM_FIELD_PLACEHOLDER')) {
            return;
        }

        $template->setVariable(
            $valuePlaceholder,
            contrexx_raw2xhtml($fieldValue)
        );
    }

    /**
     * Get Profile data
     *
     * @return array Array of Profile data
     */
    protected function getProfileData()
    {
        if (!\FWUser::getFWUserObject()->objUser->login()) {
            return array();
        }

        $objUser     = \FWUser::getFWUserObject()->objUser;
        $profileData = array(
            'ACCESS_USER_EMAIL' => contrexx_raw2xhtml($objUser->getEmail())
        );

        $objUser->objAttribute->reset();
        while (!$objUser->objAttribute->EOF) {
            $value = '';
            $objAttribute = $objUser->objAttribute->getById(
                $objUser->objAttribute->getId()
            );

            switch ($objAttribute->getType()) {
                case 'menu':
                    if ($objAttribute->isCoreAttribute()) {
                        foreach ($objAttribute->getChildren() as $childAttributeId) {
                            $objChildAtrribute = $objAttribute->getById($childAttributeId);
                            if (!$objChildAtrribute->getId()) {
                                continue;
                            }
                            $profileAttribute = $objUser->getProfileAttribute(
                                $objAttribute->getId()
                            );
                            if ($objChildAtrribute->getMenuOptionValue() == $profileAttribute) {
                                $value = $objChildAtrribute->getName();
                                break;
                            }
                        }
                    } else {
                        if (!$objUser->getProfileAttribute($objAttribute->getId())) {
                            break;
                        }
                        $objSelectedAttribute = $objAttribute->getById(
                            $objUser->getProfileAttribute($objAttribute->getId())
                        );
                        if (!$objSelectedAttribute->getId()) {
                            break;
                        }
                        $value = $objSelectedAttribute->getName();
                    }
                    break;

                case 'date':
                    $value = $objUser->getProfileAttribute($objAttribute->getId());
                    if ($value !== false && $value !== '') {
                        $value = date(ASCMS_DATE_FORMAT_DATE, intval($value));
                    } else {
                        $value = '';
                    }
                    break;

                default:
                    $value = $objUser->getProfileAttribute($objAttribute->getId());
                    break;
            }

            $attrPlaceholder =
                'ACCESS_PROFILE_ATTRIBUTE_' . strtoupper($objAttribute->getId());
            $profileData[$attrPlaceholder] = contrexx_raw2xhtml($value);
            $objUser->objAttribute->next();
        }

        return $profileData;
    }

    /**
     * generates an unique id for each form and user.
     */
    protected function handleUniqueId()
    {
        $this->cx->getComponent('Session')->getSession();

        $id = 0;
        if (isset($_REQUEST['unique_id'])) {
            // an id is specified - we're handling a page reload
            $id = intval($_REQUEST['unique_id']);
        } else { // generate a new id
            if (!isset($_SESSION['contact_last_id'])) {
                $_SESSION['contact_last_id'] = 1;
            } else {
                $_SESSION['contact_last_id'] += 1;
            }

            $id = $_SESSION['contact_last_id'];
        }
        $this->template->setVariable('CONTACT_UNIQUE_ID', $id);
    }

    /**
     * Initialize the Uploader
     *
     * @param \Cx\Core\Html\Sigma $template                  Template object
     * @param integer             $fieldId                   Field ID
     * @param boolean             $parseLegacyPlaceholder    If true form has direct {<ID>_VALUE} an
     *                                                       {<ID>_LABEL} placeholders otherwise false
     * @param boolean             $restrictUpload2SingleFile If true Uploader accept only SingleFile
     *                                                       otherwise Uploader handle Multiple Files
     * @return string Uploader code
     */
    protected function initUploader(
        \Cx\Core\Html\Sigma $template,
        $fieldId,
        $parseLegacyPlaceholder,
        $restrictUpload2SingleFile = true
    ) {
        try {
            $session = $this->cx->getComponent('Session')->getSession();

            $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
            // set instance name so we are able to catch the instance with js
            $uploader->setCallback('contactFormUploader_' . $fieldId);

            // specifies the function to call when upload is finished.
            // must be a static function
            $uploader->setFinishedCallback(array(
                $this->cx->getCodeBaseCoreModulePath() .
                '/Contact/Controller/Contact.class.php',
                '\Cx\Core_Modules\Contact\Controller\Contact',
                'uploadFinished'
            ));

            if ($restrictUpload2SingleFile) {
                $uploader->setData(array(
                    'singleFile'   => $restrictUpload2SingleFile
                ));
                $uploader->setUploadLimit(1);
            }
            $uploaderId = $uploader->getId();
            $uploader->setOptions(array(
                'id'     => 'contactUploader_' . $uploaderId,
                'style'  => 'display: none'
            ));

            // initialize the widget displaying the folder contents
            $folderWidget = new \Cx\Core_Modules\MediaBrowser\Model\Entity\FolderWidget(
                $session->getTempPath() . '/'. $uploaderId
            );

            if ($parseLegacyPlaceholder) {
                $placeholders = array(
                    'CONTACT_UPLOADER_FOLDER_WIDGET_' . $fieldId => $folderWidget->getXhtml(),
                    'CONTACT_UPLOADER_ID_' . $fieldId => $uploaderId,
                );
            } else {
                $placeholders = array(
                    'CONTACT_UPLOADER_FOLDER_WIDGET' => $folderWidget->getXhtml(),
                    'CONTACT_FORM_FIELD_VALUE'       => $uploaderId,
                );
            }
            $template->setVariable($placeholders);

            $folderWidgetId = $folderWidget->getId();
            return $uploader->getXHtml() . <<<CODE
            <script type="text/javascript">
            cx.ready(function() {
                    jQuery('#contactFormFieldId_$fieldId').bind('click', function() {
                        jQuery('#contactUploader_$uploaderId').trigger('click');
                        return false;
                    }).removeAttr('disabled');
            });

            // uploader javascript callback function
            function contactFormUploader_$fieldId(callback) {
                    angular.element('#mediaBrowserfolderWidget_$folderWidgetId').scope().refreshBrowser();
            }
            </script>
CODE;
        } catch (\Exception $e) {
            return '<!-- failed initializing uploader, exception '. get_class($e)
                . ' with message "' . $e->getMessage() . '" -->';
        }
    }

    /**
     * Get Template object
     *
     * @return \Cx\Core\Html\Sigma
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * hasFileField
     *
     * @return boolean
     */
    public function hasFileField()
    {
        return $this->hasFileField;
    }
}
