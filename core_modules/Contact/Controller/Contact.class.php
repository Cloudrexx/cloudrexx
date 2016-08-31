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
 * Contact
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_contact
 */

namespace Cx\Core_Modules\Contact\Controller;

/**
 * ContactException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @version     1.1.0
 * @package     cloudrexx
 * @subpackage  coremodule_contact
 * @todo        Edit PHP DocBlocks!
 */
class ContactException extends \Exception {}

/**
 * Contact
 *
 * This module handles all HTML FORMs with action tags to the contact section.
 * It sends the contact email(s) and uploads data (optional)
 * Ex. <FORM name="form1" action="index.php?section=Contact&cmd=thanks" method="post">
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.1.0
 * @package     cloudrexx
 * @subpackage  coremodule_contact
 */
class Contact extends \Cx\Core_Modules\Contact\Controller\ContactLib
{

    /**
     * List with the names of the formular fields
     *
     * @var array
     */
    var $arrFormFields = array();

    /**
     * Template object
     *
     * This object contains an instance of the \Cx\Core\Html\Sigma class
     * which is used as the template system.
     * @var unknown_type
     */
    var $objTemplate;

    /**
     * Contains the error message if an error occurs
     *
     * This variable will contain a message that describes
     * the error that happend.
     */
    var $errorMsg = '';

    /**
     * An id unique per form submission and user.
     * This means an user can submit the same form twice at the same time,
     * and the form gets a different submission id for each submit.
     * @var integer
     */
    protected $submissionId = 0;

    /**
     * we're in legacy mode if true.
     * this means file uploads are coming directly from inputs, rather than being
     * handled by the cloudrexx upload core-module.
     * Q: What is the legacyMode for?
     * A: With legacyMode we support the old submission forms that hadn't
     *    been migrated to the new fileUploader structure.
     * @var boolean
     */
    protected $legacyMode;

    /**
     * used by @link Contact::_uploadFiles() .
     * remembers the directory made in the first call to _uploadFiles.
     * @var string
     */
    protected $depositionTarget;

    /**
     * Determines whether this form has a file upload field.
     * @var boolean
     */
    protected $hasFileField;

    /**
     * Contact constructor
     *
     * The constructor does initialize a template system
     * which will be used to display the contact form or the
     * feedback/error message.
     * @param string Content page template
     * @see objTemplate, \Cx\Core\Html\Sigma::setErrorHandling(), \Cx\Core\Html\Sigma::setTemplate()
     */
    function __construct($pageContent)
    {
        $this->objTemplate = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->objTemplate);
        $this->objTemplate->setErrorHandling(PEAR_ERROR_DIE);
        $this->objTemplate->setTemplate($pageContent);

// TODO: This is a huge overhead. We don't really need all forms to get loaded in the frontend!
//       Solution propoal: Create a new class SubmissionForm to work with (do a Doctrine rewrite to implement a history as well)
        $this->initContactForms();
        $this->hasFileField = false;
    }

    /**
     * Show the contact page
     *
     * Parse a contact form submit request and show the contact page
     * @see _getContactFormData(), _checkValues(), _insertIntoDatabase(), sendMail(), _showError(), _showFeedback(), \Cx\Core\Html\Sigma::get(), \Cx\Core\Html\Sigma::blockExists(), \Cx\Core\Html\Sigma::hideBlock(), \Cx\Core\Html\Sigma::touchBlock()
     * @return string Parse contact form page
     */
    function getContactPage()
    {
        global $_ARRAYLANG, $_LANGID, $objDatabase;

        \JS::activate('cx');

        $formId = isset($_GET['cmd']) ? intval($_GET['cmd']) : 0;
        $arrFields  = $this->getFormFields($formId);
        $isLoggedin = $this->setProfileData();
        $useCaptcha = !$isLoggedin && $this->getContactFormCaptchaStatus($formId);
        $this->handleUniqueId();
        $uploaderCode = '';

        // load requested form's source code if required
        if ($this->objTemplate->placeholderExists('APPLICATION_DATA')) {
            // load form's source code
            $applicationTemplate = $this->getSourceCode($formId, $_LANGID);
            \LinkGenerator::parseTemplate($applicationTemplate);
            $this->objTemplate->addBlock('APPLICATION_DATA', 'application_data', $applicationTemplate);
        }

        $this->objTemplate->setVariable(array(
            'TXT_NEW_ENTRY_ERORR'   => $_ARRAYLANG['TXT_NEW_ENTRY_ERORR'],
            'TXT_CONTACT_SUBMIT'    => $_ARRAYLANG['TXT_CONTACT_SUBMIT'],
            'TXT_CONTACT_RESET'     => $_ARRAYLANG['TXT_CONTACT_RESET'],
        ));

        if ($this->objTemplate->blockExists('contact_form')) {
            $recipients = $this->getRecipients($formId);

            foreach ($arrFields as $fieldId => $arrField) {
                /*
                 * Set values for special field types if the user is authenticated
                 */
                if ($isLoggedin && empty($_GET[$fieldId]) && empty($_POST['contactFormField_'.$fieldId])) {
                    switch ($arrField['special_type']) {
                        case 'access_email':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_USER_EMAIL]]';
                            break;

                        case 'access_gender':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_GENDER]]';
                            break;

                        case 'access_title':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_TITLE]]';
                            break;

                        case 'access_firstname':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_FIRSTNAME]]';
                            break;

                        case 'access_lastname':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_LASTNAME]]';
                            break;

                        case 'access_company':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_COMPANY]]';
                            break;

                        case 'access_address':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_ADDRESS]]';
                            break;

                        case 'access_city':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_CITY]]';
                            break;

                        case 'access_zip':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_ZIP]]';
                            break;

                        case 'access_country':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_COUNTRY]]';
                            break;

                        case 'access_phone_office':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_PHONE_OFFICE]]';
                            break;

                        case 'access_phone_private':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_PHONE_PRIVATE]]';
                            break;

                        case 'access_phone_mobile':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_PHONE_MOBILE]]';
                            break;

                        case 'access_phone_fax':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_PHONE_FAX]]';
                            break;

                        case 'access_birthday':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_BIRTHDAY]]';
                            break;

                        case 'access_website':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_WEBSITE]]';
                            break;

                        case 'access_profession':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_PROFESSION]]';
                            break;

                        case 'access_interests':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_INTERESTS]]';
                            break;

                        case 'access_signature':
                            $arrField['lang'][$_LANGID]['value'] = '[[ACCESS_PROFILE_ATTRIBUTE_SIGNATURE]]';
                            break;

                        default:
                            break;
                    }
                }

                $arrField['lang'][$_LANGID]['value'] = preg_replace('/\[\[([A-Z0-9_]+)\]\]/', '{$1}', $arrField['lang'][$_LANGID]['value']);

                $this->objTemplate->setVariable(array(
                    $formId.'_FORM_NAME'    => contrexx_raw2xhtml($this->arrForms[$formId]['lang'][$_LANGID]['name']),
                    $formId.'_FORM_TEXT'    => $this->arrForms[$formId]['lang'][$_LANGID]['text'],
                    $fieldId.'_LABEL'       => ($arrField['lang'][$_LANGID]['name'] != "") ? $arrField['lang'][$_LANGID]['name'] : "&nbsp;"
                ));

                /*
                 * Generate values for dropdown checkbox and radio fields
                 */
                $userProfileRegExp = '/\{([A-Z_]+)\}/';
                $accessAttributeId = null;
                $fieldType = ($arrField['type'] != 'special') ? $arrField['type'] : $arrField['special_type'];
                switch ($fieldType) {
                    case 'checkbox':
                        if ($arrField['lang'][$_LANGID]['value'] == 1 || !empty($_POST['contactFormField_' . $fieldId])) {
                            $this->objTemplate->setVariable('SELECTED_'.$fieldId, 'checked="checked"');
                        }
                        break;

                    case 'checkboxGroup':
                    case 'radio':
                        $options = explode(',', $arrField['lang'][$_LANGID]['value']);
                        foreach ($options as $index => $option) {
                            if (preg_match($userProfileRegExp, $option)) {
                                $valuePlaceholderBlock = 'contact_value_placeholder_block_'.$fieldId.'_'.$index;
                                $this->objTemplate->addBlock($fieldId.'_'.$index.'_VALUE', $valuePlaceholderBlock, contrexx_raw2xhtml($option));
                            } else {
                                $this->objTemplate->setVariable($fieldId.'_'.$index.'_VALUE', contrexx_raw2xhtml($option));
                            }

                            if (!empty($_POST['contactFormField_'.$fieldId])) {
                                if (in_array($option, $_POST['contactFormField_'.$fieldId]) ||
                                    $option == $_POST['contactFormField_'.$fieldId]) {
                                    $this->objTemplate->setVariable('SELECTED_'.$fieldId.'_'.$index, 'checked="checked"');
                                }
                            } elseif (!empty($_GET[$fieldId])) {
                                if ($option == $_GET[$fieldId]) {
                                    $this->objTemplate->setVariable('SELECTED_'.$fieldId.'_'.$index, 'checked="checked"');
                                }
                            }
                        }
                        break;

                    case 'access_title':
                    case 'access_gender':
                        // collect user attribute options
                        $arrOptions = array();
                        $accessAttributeId = str_replace('access_', '', $fieldType);
                        $objAttribute = \FWUser::getFWUserObject()->objUser->objAttribute->getById($accessAttributeId);

                        // get options
                        $arrAttribute = $objAttribute->getChildren();
                        foreach ($arrAttribute as $attributeId) {
                            // in case the selection of the field is mandatory, we shall skip the unknown option of the user profile attribute
                            if (   $arrField['is_required']
                                && strpos($attributeId, '_undefined')
                            ) {
                                continue;
                            }
                            $objAttribute = \FWUser::getFWUserObject()->objUser->objAttribute->getById($attributeId);
                            $arrOptions[] = $objAttribute->getName(FRONTEND_LANG_ID);
                        }

                        // options will be used for select input generation
                        $arrField['lang'][FRONTEND_LANG_ID]['value'] = implode(',', $arrOptions);

                        // intentionally no break here!!

                    case 'select':
                        $options = explode(',', $arrField['lang'][$_LANGID]['value']);
                        $inexOffset = 0;
                        if ($arrField['is_required']) {
                            $options = array_merge(array($_ARRAYLANG['TXT_CONTACT_PLEASE_SELECT']), $options);
                            $inexOffset = 1;
                        }
                        foreach ($options as $index => $option) {
                            if (preg_match($userProfileRegExp, $option)) {
                                $valuePlaceholderBlock = 'contact_value_placeholder_block_'.$fieldId.'_'.$index;
                                $this->objTemplate->addBlock($fieldId.'_VALUE', $valuePlaceholderBlock, contrexx_raw2xhtml($option));
                            } else {
                                $this->objTemplate->setVariable($fieldId.'_VALUE', contrexx_raw2xhtml($option));
                            }

                            // pre-selection, based on $_POST value
                            if (!empty($_POST['contactFormField_'.$fieldId])) {
                                if ($index == array_search($_POST['contactFormField_'.$fieldId], explode(',', $arrField['lang'][$_LANGID]['value']))+$inexOffset) {
                                    $this->objTemplate->setVariable('SELECTED_'.$fieldId, 'selected = "selected"');
                                }
                            // pre-selection, based on $_GET value
                            } elseif (!empty($_GET[$fieldId])) {
                                if ($index == array_search(contrexx_input2raw($_GET[$fieldId]), explode(',' ,$arrField['lang'][$_LANGID]['value']))) {
                                    $this->objTemplate->setVariable('SELECTED_'.$fieldId, 'selected = "selected"');
                                }
                            // pre-selection, based on profile data of currently signed in user
                            } elseif (   isset($this->objTemplate->_globalVariables['ACCESS_PROFILE_ATTRIBUTE_'.strtoupper($accessAttributeId)])
                                      && $option == $this->objTemplate->_globalVariables['ACCESS_PROFILE_ATTRIBUTE_'.strtoupper($accessAttributeId)]
                            ) {
                                $this->objTemplate->setVariable('SELECTED_'.$fieldId, 'selected = "selected"');
                            }

                            $this->objTemplate->parse('field_'.$fieldId);
                        }
                        break;

                    case 'recipient':
                        foreach ($recipients as $index => $recipient) {
                            $recipient['lang'][$_LANGID] = preg_replace('/\[\[([A-Z0-9_]+)\]\]/', '{$1}', $recipient['lang'][$_LANGID]);
                            if (preg_match($userProfileRegExp, $recipient['lang'][$_LANGID])) {
                                $valuePlaceholderBlock = 'contact_value_placeholder_block_'.$fieldId.'_'.$index;
                                $this->objTemplate->addBlock($fieldId.'_VALUE', $valuePlaceholderBlock, $recipient['lang'][$_LANGID]);
                            } else {
                                $this->objTemplate->setVariable(array(
                                    $fieldId.'_VALUE'    => $recipient['lang'][$_LANGID]
                                ));
                            }
                            $this->objTemplate->setVariable(array(
                                $fieldId.'_VALUE_ID'    => $index
                            ));
                            if (!empty($_POST['contactFormField_'.$fieldId]) &&
                                $recipient['lang'][$_LANGID] == $_POST['contactFormField_'.$fieldId]) {
                                    $this->objTemplate->setVariable(array(
                                        'SELECTED_'.$fieldId => 'selected = "selected"'
                                    ));
                                } elseif (!empty($_GET[$fieldId]) &&
                                          $recipient['lang'][$_LANGID] == $_GET[$fieldId]) {
                                     $this->objTemplate->setVariable(array(
                                         'SELECTED_'.$fieldId => 'selected = "selected"'
                                     ));
                            }
                            $this->objTemplate->parse('field_'.$fieldId);
                        }
                        break;

                    case 'access_country':
                    case 'country':
                        if (preg_match($userProfileRegExp, $arrField['lang'][$_LANGID]['value'])) {
                            $arrField['lang'][$_LANGID]['value'] = $this->objTemplate->_globalVariables[trim($arrField['lang'][$_LANGID]['value'],'{}')];
                        }
                        $lang = $arrField['lang'][$_LANGID]['value'];
                        $country = \Cx\Core\Country\Controller\Country::getNameArray(true, $lang);
                        foreach ($country as $id => $name) {
                            $this->objTemplate->setVariable($fieldId.'_VALUE', $name);

                            if ((!empty($_POST['contactFormField_'.$fieldId]))) {
                              if (strcasecmp($name, $_POST['contactFormField_'.$fieldId]) == 0) {
                                  $this->objTemplate->setVariable('SELECTED_'.$fieldId, 'selected = "selected"');
                              }
                            } elseif ((!empty($_GET[$fieldId]))) {
                                if (strcasecmp($name, $_GET[$fieldId]) == 0) {
                                    $this->objTemplate->setVariable('SELECTED_'.$fieldId, 'selected = "selected"');
                                }
                            } elseif ($name == $arrField['lang'][$_LANGID]['value']) {
                                    $this->objTemplate->setVariable('SELECTED_'.$fieldId, 'selected = "selected"');
                            }
                            $this->objTemplate->parse('field_'.$fieldId);
                        }
                        $this->objTemplate->setVariable(array(
                            'TXT_CONTACT_PLEASE_SELECT' => $_ARRAYLANG['TXT_CONTACT_PLEASE_SELECT'],
                            'TXT_CONTACT_NOT_SPECIFIED' => $_ARRAYLANG['TXT_CONTACT_NOT_SPECIFIED']
                        ));
                        break;

                    case 'file':
                        $this->hasFileField = true;
                        $uploaderCode .= $this->initUploader($fieldId, true);
                        break;

                    case 'multi_file':
                        $this->hasFileField = true;
                        $uploaderCode .= $this->initUploader($fieldId, false);
                        break;

                    default:
                        /*
                         * Set default field value through User profile attribute
                         */
                        $arrField['lang'][$_LANGID]['value'] = preg_replace('/\[\[([A-Z0-9_]+)\]\]/', '{$1}', $arrField['lang'][$_LANGID]['value']);
                        if (preg_match($userProfileRegExp, $arrField['lang'][$_LANGID]['value'])) {
                            $valuePlaceholderBlock = 'contact_value_placeholder_block_'.$fieldId;
                            $this->objTemplate->addBlock($fieldId.'_VALUE', $valuePlaceholderBlock, contrexx_raw2xhtml($arrField['lang'][$_LANGID]['value']));
                        } elseif (!empty($_POST['contactFormField_'.$fieldId])) {
                            $this->objTemplate->setVariable($fieldId.'_VALUE', contrexx_raw2xhtml($_POST['contactFormField_'.$fieldId]));
                        } elseif (!empty($_GET[$fieldId])) {
                            $this->objTemplate->setVariable($fieldId.'_VALUE', contrexx_raw2xhtml($_GET[$fieldId]));
                        } else {
                            $this->objTemplate->setVariable($fieldId.'_VALUE', contrexx_raw2xhtml($arrField['lang'][$_LANGID]['value']));
                        }
                        break;
                }

                /*
                 * Parse the blocks created for parsing user profile data using addBlock()
                 */
                if(!empty($valuePlaceholderBlock) && $this->objTemplate->blockExists($valuePlaceholderBlock)){
                    $this->objTemplate->touchBlock($valuePlaceholderBlock);
                }
            }
        }

        if (isset($_POST['submitContactForm']) || isset($_POST['Submit'])) { //form submitted
            $this->checkLegacyMode();

            $showThanks = (isset($_GET['cmd']) && $_GET['cmd'] == 'thanks') ? true : false;
            $arrFormData = $this->_getContactFormData();
            if ($arrFormData) {
                if ($this->_checkValues($arrFormData, $useCaptcha) && $this->_insertIntoDatabase($arrFormData)) { //validation ok
                    if (!empty($arrFormData['saveDataInCRM'])) {
                        $objCrmLibrary = new \Cx\Modules\Crm\Controller\CrmLibrary('Crm');
                        $objCrmLibrary->addCrmContact($arrFormData);
                    }
                    $this->sendMail($arrFormData);
                    if (isset($arrFormData['showForm']) && !$arrFormData['showForm']) {
                        $this->objTemplate->hideBlock("formText");
                        $this->objTemplate->hideBlock('contact_form');
                    }
                } else { //found errors while validating
                    $this->setCaptcha($useCaptcha);
                    return $this->_showError();
                }

                if (!$showThanks) {
                    $this->_showFeedback($arrFormData);
                } else {
                    if ($this->objTemplate->blockExists("formText")) {
                        $this->objTemplate->hideBlock("formText");
                    }
                }
            }
        } else { //fresh display
            if ($this->objTemplate->blockExists('formText')) {
                $this->objTemplate->touchBlock('formText');
            }
            $this->setCaptcha($useCaptcha);
        }

        $this->objTemplate->setVariable('CONTACT_JAVASCRIPT', $this->_getJsSourceCode($formId, $arrFields) . $uploaderCode);

        return $this->objTemplate->get();
    }

    /**
     * generates an unique id for each form and user.
     * @see Contact::$submissionId
     */
    protected function handleUniqueId() {
        global $sessionObj;
        if (!isset($sessionObj)) $sessionObj = \cmsSession::getInstance();

        $id = 0;
        if(isset($_REQUEST['unique_id'])) { //an id is specified - we're handling a page reload
            $id = intval($_REQUEST['unique_id']);
        }
        else { //generate a new id
            if(!isset($_SESSION['contact_last_id'])) {
                $_SESSION['contact_last_id'] = 1;
            } else {
                $_SESSION['contact_last_id'] += 1;
            }

            $id = $_SESSION['contact_last_id'];
        }
        $this->objTemplate->setVariable('CONTACT_UNIQUE_ID', $id);
        $this->submissionId = $id;
    }

    /**
     * Inits the uploader when displaying a contact form.
     */
    protected function initUploader($fieldId, $restrictUpload2SingleFile = true) {
        global $sessionObj;

        try {
            if (!isset($sessionObj)) {
                $sessionObj = \cmsSession::getInstance();
            }

            $uploader   = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
            //set instance name so we are able to catch the instance with js
            $uploader->setCallback('contactFormUploader_'.$fieldId);
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();

            // specifies the function to call when upload is finished. must be a static function
            $uploader->setFinishedCallback(array(
                $cx->getCodeBaseCoreModulePath().'/Contact/Controller/Contact.class.php',
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
                'id'     => 'contactUploader_'.$uploaderId,
                'style'  => 'display: none'
            ));

            //initialize the widget displaying the folder contents
            $folderWidget = new \Cx\Core_Modules\MediaBrowser\Model\Entity\FolderWidget($_SESSION->getTempPath() . '/'. $uploaderId);
            $this->objTemplate->setVariable(array(
                'CONTACT_UPLOADER_FOLDER_WIDGET_'.$fieldId => $folderWidget->getXhtml(),
                'CONTACT_UPLOADER_ID_'.$fieldId            => $uploaderId
            ));

            $folderWidgetId = $folderWidget->getId();
            $strInputfield  = $uploader->getXHtml();
            $strInputfield .= <<<CODE
            <script type="text/javascript">
            cx.ready(function() {
                    jQuery('#contactFormFieldId_$fieldId').bind('click', function() {
                        jQuery('#contactUploader_$uploaderId').trigger('click');
                        return false;
                    }).removeAttr('disabled');
            });

            //uploader javascript callback function
            function contactFormUploader_$fieldId(callback) {
                    angular.element('#mediaBrowserfolderWidget_$folderWidgetId').scope().refreshBrowser();
            }
            </script>
CODE;
            return $strInputfield;
        }
        catch (Exception $e) {
            return '<!-- failed initializing uploader, exception '.get_class($e).' with message "'.$e->getMessage().'" -->';
        }
    }

    private function setProfileData()
    {
        if (!\FWUser::getFWUserObject()->objUser->login()) {
            return false;
        }

        $objUser = \FWUser::getFWUserObject()->objUser;

        $this->objTemplate->setVariable('ACCESS_USER_EMAIL', htmlentities($objUser->getEmail(), ENT_QUOTES, CONTREXX_CHARSET));

        $objUser->objAttribute->reset();
        while (!$objUser->objAttribute->EOF) {
            $objAttribute = $objUser->objAttribute->getById($objUser->objAttribute->getId());

            switch ($objAttribute->getType())
            {
                case 'menu':
                    if ($objAttribute->isCoreAttribute()) {
                        foreach ($objAttribute->getChildren() as $childAttributeId) {
                            $objChildAtrribute = $objAttribute->getById($childAttributeId);
                            if ($objChildAtrribute->getMenuOptionValue() == $objUser->getProfileAttribute($objAttribute->getId())) {
                                $value = $objChildAtrribute->getName();
                                break;
                            }
                        }
                    } else {
                        $objSelectedAttribute = $objAttribute->getById($objUser->getProfileAttribute($objAttribute->getId()));
                        $value = $objSelectedAttribute->getName();
                    }
                break;

                case 'date':
                    $value = $objUser->getProfileAttribute($objAttribute->getId());
                    $value = $value !== false && $value !== '' ? date(ASCMS_DATE_FORMAT_DATE, intval($value)) : '';
                break;

                default:
                    $value = $objUser->getProfileAttribute($objAttribute->getId());
                break;
            }

            $this->objTemplate->setGlobalVariable('ACCESS_PROFILE_ATTRIBUTE_'.strtoupper($objAttribute->getId()), htmlentities($value, ENT_QUOTES, CONTREXX_CHARSET));
            $objUser->objAttribute->next();
        }
        return true;
    }

    function setCaptcha($useCaptcha)
    {
        global $_CORELANG;

        if (!$this->objTemplate->blockExists('contact_form_captcha')) {
            return;
        }

        if ($useCaptcha) {
            $this->objTemplate->setVariable(array(
                'TXT_CONTACT_CAPTCHA'   => $_CORELANG['TXT_CORE_CAPTCHA'],
                'CONTACT_CAPTCHA_CODE'  => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
            ));

            $this->objTemplate->parse('contact_form_captcha');
        } else {
            $this->objTemplate->hideBlock('contact_form_captcha');
        }
    }

    /**
     * Get data from contact form submit
     *
     * Reads out the data that has been submited by the visitor.
     * @access private
     * @global array
     * @global array
     * @see getContactFormDetails(), getFormFields(), _uploadFiles(),
     * @return mixed An array with the contact details or FALSE if an error occurs
     */
    function _getContactFormData()
    {
        global $_ARRAYLANG, $_CONFIG, $_LANGID;

        if (isset($_POST) && !empty($_POST)) {
            $arrFormData = array();
            $arrFormData['id'] = isset($_GET['cmd']) ? intval($_GET['cmd']) : 0;
            if ($this->getContactFormDetails($arrFormData['id'], $arrFormData['emails'], $arrFormData['subject'], $arrFormData['feedback'], $arrFormData['mailTemplate'], $arrFormData['showForm'], $arrFormData['useCaptcha'], $arrFormData['sendCopy'], $arrFormData['useEmailOfSender'], $arrFormData['htmlMail'], $arrFormData['sendAttachment'], $arrFormData['saveDataInCRM'], $arrFormData['crmCustomerGroups'])) {
                $arrFormData['fields'] = $this->getFormFields($arrFormData['id']);
                foreach ($arrFormData['fields'] as $field) {
                    $this->arrFormFields[] = $field['lang'][$_LANGID]['name'];
                }
            } else {
                $arrFormData['id'] = 0;
                $arrFormData['emails'] = explode(',', $_CONFIG['contactFormEmail']);
                $arrFormData['subject'] = $_ARRAYLANG['TXT_CONTACT_FORM']." ".$_CONFIG['domainUrl'];
                $arrFormData['showForm'] = 1;
                //$arrFormData['sendCopy'] = 0;
                $arrFormData['htmlMail'] = 1;
            }
// TODO: check if _uploadFiles does something dangerous with $arrFormData['fields'] (this is raw data!)
            $arrFormData['uploadedFiles'] = $this->_uploadFiles($arrFormData['fields']);

            foreach ($_POST as $key => $value) {
                if ((($value == 0) || !empty($value)) && !in_array($key, array('Submit', 'submitContactForm', 'contactFormCaptcha'))) {
                    $id = intval(substr($key, 17));
                    if (isset($arrFormData['fields'][$id])) {
                        $key = $arrFormData['fields'][$id]['lang'][$_LANGID]['name'];
                    } else {
                        $key = contrexx_input2raw($key);
                    }
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }

                    $arrFormData['data'][$id] = contrexx_input2raw($value);
                }
            }

            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && !empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $arrFormData['meta']['ipaddress'] = contrexx_input2raw($_SERVER["HTTP_X_FORWARDED_FOR"]);
            } else {
                $arrFormData['meta']['ipaddress'] = contrexx_input2raw($_SERVER["REMOTE_ADDR"]);
            }

            $arrFormData['meta']['time'] = time();
            $arrFormData['meta']['host'] = contrexx_input2raw(@gethostbyaddr($arrFormData['meta']['ipaddress']));
            $arrFormData['meta']['lang'] = contrexx_input2raw($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
            $arrFormData['meta']['browser'] = contrexx_input2raw($_SERVER["HTTP_USER_AGENT"]);

            return $arrFormData;
        }
        return false;
    }

    /**
     * Checks whether this is an old form and sets $this->legacyMode.
     * @see Contact::$legacyMode
     */
    protected function checkLegacyMode() {
        $this->legacyMode = !isset($_REQUEST['unique_id']);
    }

    /**
     * Handle uploads
     * @see Contact::_uploadFilesLegacy()
     * @param array $arrFields
     * @param boolean move should the files be moved or
     *                do we just want an array of filenames?
     *                defaults to false. no effect in legacy mode.
     * @return array A list of files that have been stored successfully in the system
     */
    protected function _uploadFiles($arrFields, $move = false) {
        /* the field unique_id has been introduced with the new uploader.
         * it helps us to tell whether we're handling an form generated
         * before the new uploader using the classic input fields or
         * if we have to treat the files already uploaded by the uploader.
         */
        if($this->legacyMode) {
            //legacy function for old uploader
            return $this->_uploadFilesLegacy($arrFields);
        } else {
            //new uploader used
            if(!$this->hasFileField) //nothing to do for us, no files
                return array();

            $arrFiles = array(); //we'll collect name => path of all files here and return this
            $documentRootPath = \Env::get('cx')->getWebsiteDocumentRootPath();
            foreach ($arrFields as $fieldId => $arrField) {
                // skip non-upload fields
                if (!in_array($arrField['type'], array('file', 'multi_file'))) {
                    continue;
                }

                $tup = self::getTemporaryUploadPath($fieldId);
                $tmpUploadDir = !empty($tup[2]) ? $tup[1].'/'.$tup[2].'/' : ''; //all the files uploaded are in here

                $depositionTarget = ""; //target folder

                //on the first call, _uploadFiles is called with move=false.
                //this is done in order to get an array of the moved files' names, but
                //the files are left in place.
                //the second call is done with move=true - here we finally move the
                //files.
                //
                //the target folder is created in the first call, because if we can't
                //create the folder, the target path is left pointing at the path
                //specified by $arrSettings['fileUploadDepositionPath'].
                //
                //to remember the target folder for the second call, it is stored in
                //$this->depositionTarget.
                if(!$move) { //first call - create folder
                    //determine where form uploads are stored
                    $arrSettings = $this->getSettings();
                    $depositionTarget = $arrSettings['fileUploadDepositionPath'].'/';

                    //find an unique folder name for the uploaded files
                    $folderName = date("Ymd").'_'.$fieldId;
                    $suffix = "";
                    if(file_exists($documentRootPath.$depositionTarget.$folderName)) {
                        $suffix = 1;
                        while(file_exists($documentRootPath.$depositionTarget.$folderName.'-'.$suffix))
                            $suffix++;

                        $suffix = '-'.$suffix;
                    }
                    $folderName .= $suffix;

                    //try to make the folder and change target accordingly on success
                    if(\Cx\Lib\FileSystem\FileSystem::make_folder($documentRootPath.$depositionTarget.$folderName)) {
                        \Cx\Lib\FileSystem\FileSystem::makeWritable($documentRootPath.$depositionTarget.$folderName);
                        $depositionTarget .= $folderName.'/';
                    }
                    $this->depositionTarget[$fieldId] = $depositionTarget;
                }
                else //second call - restore remembered target
                {
                    $depositionTarget = $this->depositionTarget[$fieldId];
                }

                //move all files
                if (empty($tmpUploadDir) || !\Cx\Lib\FileSystem\FileSystem::exists($tmpUploadDir)) {
                   continue;
                }

                $h = opendir(\Env::get('cx')->getWebsitePath().$tmpUploadDir);
                while(false !== ($f = readdir($h))) {
                    if($f != '..' && $f != '.') {
                        //do not overwrite existing files.
                        $prefix = '';
                        while (file_exists($documentRootPath.$depositionTarget.$prefix.$f)) {
                            if (empty($prefix)) {
                                $prefix = 0;
                            }
                            $prefix ++;
                        }

                        if($move) {
                            // move file
                            try {
                                $objFile = new \Cx\Lib\FileSystem\File($tmpUploadDir.$f);
                                $objFile->move($documentRootPath.$depositionTarget.$prefix.$f, false);
                            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                                \DBG::msg($e->getMessage());
                            }
                        }

                        $arrFiles[$fieldId][] = array(
                            'name'  => $f,
                            'path'  => $depositionTarget.$prefix.$f,
                        );
                    }
                }
            }
            //cleanup
//TODO: this does not work for certain reloads - add cleanup routine
            //@rmdir($tmpUploadDir);
            return $arrFiles;
        }
    }

    /**
     * Upload submitted files
     *
     * Move all files that are allowed to be uploaded in the folder that
     * has been specified in the configuration option "File upload deposition path"
     * @access private
     * @global array
     * @param array Files that have been submited
     * @see getSettings(), _cleanFileName(), errorMsg, FWSystem::getMaxUploadFileSize()
     * @return array A list of files that have been stored successfully in the system
     */
    function _uploadFilesLegacy($arrFields)
    {
        global $_ARRAYLANG;

        $arrSettings = $this->getSettings();

        $arrFiles = array();
        if (isset($_FILES) && is_array($_FILES)) {
            foreach (array_keys($_FILES) as $file) {
                $fileName = !empty($_FILES[$file]['name']) ? $this->_cleanFileName($_FILES[$file]['name']) : '';
                $fileTmpName = !empty($_FILES[$file]['tmp_name']) ? $_FILES[$file]['tmp_name'] : '';

                switch ($_FILES[$file]['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                        //Die hochgeladene Datei überschreitet die in der Anweisung upload_max_filesize in php.ini festgelegte Grösse.
                        $this->errorMsg .= sprintf($_ARRAYLANG['TXT_CONTACT_FILE_SIZE_EXCEEDS_LIMIT'], $fileName, \FWSystem::getMaxUploadFileSize()).'<br />';
                        break;

                    case UPLOAD_ERR_FORM_SIZE:
                        //Die hochgeladene Datei überschreitet die in dem HTML Formular mittels der Anweisung MAX_FILE_SIZE angegebene maximale Dateigrösse.
                        $this->errorMsg .= sprintf($_ARRAYLANG['TXT_CONTACT_FILE_TOO_LARGE'], $fileName).'<br />';
                        break;

                    case UPLOAD_ERR_PARTIAL:
                        //Die Datei wurde nur teilweise hochgeladen.
                        $this->errorMsg .= sprintf($_ARRAYLANG['TXT_CONTACT_FILE_CORRUPT'], $fileName).'<br />';
                        break;

                    case UPLOAD_ERR_NO_FILE:
                        //Es wurde keine Datei hochgeladen.
                        continue;
                        break;

                    default:
                        if (!empty($fileTmpName)) {
                            $arrFile = pathinfo($fileName);
                            $i = '';
                            $suffix = '';
                            $documentRootPath = \Env::get('cx')->getWebsiteDocumentRootPath();
                            $filePath = $arrSettings['fileUploadDepositionPath'].'/'.$arrFile['filename'].$suffix.'.'.$arrFile['extension'];
                            while (file_exists($documentRootPath.$filePath)) {
                                $suffix = '-'.++$i;
                                $filePath = $arrSettings['fileUploadDepositionPath'].'/'.$arrFile['filename'].$suffix.'.'.$arrFile['extension'];
                            }

                            $arrMatch = array();
                            if (\FWValidator::is_file_ending_harmless($fileName)) {
                                if (@move_uploaded_file($fileTmpName, $documentRootPath.$filePath)) {
                                    $id = intval(substr($file, 17));
                                    $arrFiles[$id][] = array(
                                        'path' => $filePath,
                                        'name' => $fileName
                                    );
                                } else {
                                    $this->errorMsg .= sprintf($_ARRAYLANG['TXT_CONTACT_FILE_UPLOAD_FAILED'], htmlentities($fileName, ENT_QUOTES, CONTREXX_CHARSET)).'<br />';
                                }
                            } else {
                                $this->errorMsg .= sprintf($_ARRAYLANG['TXT_CONTACT_FILE_EXTENSION_NOT_ALLOWED'], htmlentities($fileName, ENT_QUOTES, CONTREXX_CHARSET)).'<br />';
                            }
                        }
                        break;
                }
            }
        }

        return $arrFiles;
    }

    /**
    * Format a file name to be safe
    *
    * Replace non valid filename chars with a undercore.
    * @access private
    * @param string $file   The string file name
    * @param int    $maxlen Maximun permited string length
    * @return string Formatted file name
    */
    function _cleanFileName($name, $maxlen=250){
        $noalpha = 'áéíóúàèìòùäëïöüÁÉÍÓÚÀÈÌÒÙÄËÏÖÜâêîôûÂÊÎÔÛñçÇ@';
        $alpha =   'aeiouaeiouaeiouAEIOUAEIOUAEIOUaeiouAEIOUncCa';
        $name = substr ($name, 0, $maxlen);
        $name = $this->_strtr_utf8 ($name, $noalpha, $alpha);
        $mixChars = array('Þ' => 'th', 'þ' => 'th', 'Ð' => 'dh', 'ð' => 'dh',
                          'ß' => 'ss', 'Œ' => 'oe', 'œ' => 'oe', 'Æ' => 'ae',
                          'æ' => 'ae', '$' => 's',  '¥' => 'y');
        $name = strtr($name, $mixChars);
        // not permitted chars are replaced with "_"
        return ereg_replace ('[^a-zA-Z0-9,._\+\()\-]', '_', $name);
    }

    /**
     * Workaround for 3-argument-strtr with utf8 characters
     * used like PHP's strtr() with 3 arguments
     * @access private
     * @param string $str where to search
     * @param string $from which chars to look for and...
     * @param string $to ...the chars to replace by
     * @return the strtr()ed result
     */
    function _strtr_utf8($str, $from, $to) {
        if(!isset($to))
        {
            //2-argument call. no need to change anything, just pass to strtr
            return strtr($str, $from);
        }

        $keys = array();
        $values = array();

        //let php put all the symbols into an array based on the current charset
        //(which is utf8)
        preg_match_all('/./u', $from, $keys);
        preg_match_all('/./u', $to, $values);
        //create a mapping, so strtr() doesn't get confused with the multi-byte chars
        $mapping = array_combine($keys[0], $values[0]);
        //finally strtr
        return strtr($str, $mapping);
    }

    /**
     * Checks the Values sent trough post
     *
     * Checks the Values sent trough post. Normally this is already done
     * by Javascript, but it could be possible that the client doens't run
     * JS, so this is done here again. Sadly, it is not possible to rewrite
     * the posted values again
     * @access private
     * @global array
     * @param array Submitted field values
     * @see getSettings(), initCheckTypes(), arrCheckTypes, _isSpam(), errorMsg
     * @return boolean Return FALSE if a field's value isn't valid, otherwise TRUE
     */
    function _checkValues($arrFields, $useCaptcha)
    {
        global $_ARRAYLANG;

        $error = false;
        $arrSettings = $this->getSettings();
        $arrSpamKeywords = explode(',', $arrSettings['spamProtectionWordList']);
        $this->initCheckTypes();

        if (count($arrFields['fields']) > 0) {
            foreach ($arrFields['fields'] as $fieldId => $field) {
                $value = '';
                $validationRegex = null;
                $isRequired = $field['is_required'];

                switch ($field['type']) {
                    case 'label':
                    case 'fieldset':
                    case 'horizontalLine':
                        // we need to use a 'continue 2' here to first break out of the switch and then move over to the next iteration of the foreach loop
                        continue 2;
                        break;

                    case 'select':
                        $value = $arrFields['data'][$fieldId];
                        break;

                    case 'file':
                    case 'multi_file':
                        if(!$this->legacyMode && $isRequired) {
                            //check if the user has uploaded any files
                            $tup = self::getTemporaryUploadPath($fieldId);
                            $path = !empty($tup[2]) ? $tup[0].'/'.$tup[2] : '';
                            if (   empty($path)
                               || !\Cx\Lib\FileSystem\FileSystem::exists($path)
                               || count(@scandir($path)) == 2
                            ) { //only . and .. present, directory is empty
                                //no uploaded files in a mandatory field - no good.
                                $error = true;
                            }
                            // we need to use a 'continue 2' here to first break out of the switch and then move over to the next iteration of the foreach loop
                            continue 2;
                        }

                        // this is used for legacyMode
                        $value = isset($arrFields['uploadedFiles'][$fieldId]) ? $arrFields['uploadedFiles'][$fieldId] : '';
                        break;

                    case 'text':
                    case 'checkbox':
                    case 'checkboxGroup':
                    case 'country':
                    case 'date':
                    case 'hidden':
                    case 'password':
                    case 'radio':
                    case 'textarea':
                    case 'recipient':
                    case 'special':
                    default:
                        if ($field['check_type']) {
                            $validationRegex = "#".$this->arrCheckTypes[$field['check_type']]['regex'] ."#";
                            if (!empty($this->arrCheckTypes[$field['check_type']]['modifiers'])) {
                                $validationRegex .= $this->arrCheckTypes[$field['check_type']]['modifiers'];
                            }
                        }
                        $value = isset($arrFields['data'][$fieldId]) ? $arrFields['data'][$fieldId] : '';
                        break;
                }

                if ($isRequired && ($value != 0) && empty($value)) {
                    $error = true;
                } elseif (empty($value)) {
                    continue;
                } elseif($validationRegex && !preg_match($validationRegex, $value)) {
                    $error = true;
                } elseif ($this->_isSpam($value, $arrSpamKeywords)) {
                    $error = true;
                }
            }
        }

        if ($useCaptcha) {
            if (!\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()) {
                $error = true;
            }
        }

        if ($error) {
            $this->errorMsg = $_ARRAYLANG['TXT_FEEDBACK_ERROR'].'<br />';
            return false;
        } else {
            return true;
        }
    }

    /**
    * Checks a string for spam keywords
    *
    * This method looks for forbidden words in a string that have been defined
    * in the option "Spam protection word list"
    * @access private
    * @param string String to check for forbidden words
    * @param array Forbidden word list
    * @return boolean Return TRUE if the string contains an forbidden word, otherwise FALSE
    */
    function _isSpam($string, $arrKeywords)
    {
        foreach ($arrKeywords as $keyword) {
            if (!empty($keyword)) {
                if (preg_match("#{$keyword}#i", $string)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Inserts the contact form submit into the database
     *
     * This method does store the request in the database
     * @access private
     * @global ADONewConnection
     * @global array
     * @param array Details of the contact request
     * @see errorMsg
     * @return boolean TRUE on succes, otherwise FALSE
     */
    function _insertIntoDatabase($arrFormData)
    {
        global $objDatabase, $_ARRAYLANG, $_LANGID;

        if (!empty($this->errorMsg))
            return false;

        //handle files and collect the filenames
        //for legacy mode this has already been done in the first
        //_uploadFiles() call in getContactPage().
        if(!$this->legacyMode)
            $arrFormData['uploadedFiles'] = $this->_uploadFiles($arrFormData['fields'], true);

        $objResult = $objDatabase->Execute("INSERT INTO ".DBPREFIX."module_contact_form_data
                                        (`id_form`, `id_lang`, `time`, `host`, `lang`, `browser`, `ipaddress`)
                                        VALUES
                                        (".$arrFormData['id'].",
                                         ".$_LANGID.",
                                         ".$arrFormData['meta']['time'].",
                                         '".contrexx_raw2db($arrFormData['meta']['host'])."',
                                         '".contrexx_raw2db($arrFormData['meta']['lang'])."',
                                         '".contrexx_raw2db($arrFormData['meta']['browser'])."',
                                         '".contrexx_raw2db($arrFormData['meta']['ipaddress'])."')");
        if ($objResult === false) {
            $this->errorMsg .= $_ARRAYLANG['TXT_CONTACT_FAILED_SUBMIT_REQUEST'].'<br />';
            return false;
        }

        $lastInsertId = $objDatabase->insert_id();
        foreach ($arrFormData['fields'] as $key => $arrField) {
            $value = '';

            if ($arrField['type'] == 'file' || $arrField['type'] == 'multi_file') {
                if($key === 0)
                    throw new \Cx\Core_Modules\Contact\Controller\ContactException('could not find file field for form with id ' . $arrFormData['id']);

                if (isset($arrFormData['uploadedFiles'][$key]) && count($arrFormData['uploadedFiles'][$key]) > 0) { //assign all files uploaded to the uploader fields name
                    $arrTmp = array();
                    foreach ($arrFormData['uploadedFiles'][$key] as $file) {
                        $arrTmp[] = $file['path'];
                    }
                    // a * in front of the file names marks a 'new style' entry
                    $value = implode('*', $arrTmp);
                }
            } else {
                if (isset($arrFormData['data'][$key])) {
                    $value = $arrFormData['data'][$key];
                }
            }

            if ($value != "") {
                $objDatabase->Execute("INSERT INTO ".DBPREFIX."module_contact_form_submit_data
                                        (`id_entry`, `id_field`, `formlabel`, `formvalue`)
                                        VALUES
                                        (".$lastInsertId.",
                                         ".$key.",
                                         '".contrexx_raw2db($arrField['lang'][$_LANGID]['name'])."',
                                         '".contrexx_raw2db($value)."')");
            }
        }

        return true;
    }

    /**
     * Sends an email with the contact details to the responsible persons
     *
     * This methode sends an email to all email addresses that are defined in the
     * option "Receiver address(es)" of the requested contact form.
     * @access private
     * @global array
     * @global array
     * @param array Details of the contact request
     * @see _getEmailAdressOfString(), phpmailer::From, phpmailer::FromName, phpmailer::AddReplyTo(), phpmailer::Subject, phpmailer::IsHTML(), phpmailer::Body, phpmailer::AddAddress(), phpmailer::Send(), phpmailer::ClearAddresses()
     */
    private function sendMail($arrFormData)
    {
        global $_ARRAYLANG, $_CONFIG;

        $plaintextBody = '';
        $replyAddress = '';
        $firstname = '';
        $lastname = '';
        $senderName = '';
        $isHtml = $arrFormData['htmlMail'] == 1
                  ? true : false;

        // stop send process in case no real data had been submitted
        if (!isset($arrFormData['data']) && !isset($arrFormData['uploadedFiles'])) {
            return false;
        }

        // check if we shall send the email as multipart (text/html)
        if ($isHtml) {
            // setup html mail template
            $objTemplate = new \Cx\Core\Html\Sigma('.');
            $objTemplate->setErrorHandling(PEAR_ERROR_DIE);
            $objTemplate->setTemplate($arrFormData['mailTemplate']);

            $objTemplate->setVariable(array(
                'DATE'              => date(ASCMS_DATE_FORMAT, $arrFormData['meta']['time']),
                'HOSTNAME'          => contrexx_raw2xhtml($arrFormData['meta']['host']),
                'IP_ADDRESS'        => contrexx_raw2xhtml($arrFormData['meta']['ipaddress']),
                'BROWSER_LANGUAGE'  => contrexx_raw2xhtml($arrFormData['meta']['lang']),
                'BROWSER_VERSION'   => contrexx_raw2xhtml($arrFormData['meta']['browser']),
            ));
        }

// TODO: check if we have to excape $arrRecipients later in the code
        $arrRecipients = $this->getRecipients(intval($_GET['cmd']));

        // calculate the longest field label.
        // this will be used to correctly align all user submitted data in the plaintext e-mail
// TODO: check if the label of upload-fields are taken into account as well
        $maxlength = 0;
        foreach ($arrFormData['fields'] as $arrField) {
            $length    = strlen($arrField['lang'][FRONTEND_LANG_ID]['name']);
            $maxlength = $maxlength < $length ? $length : $maxlength;
        }

        // try to fetch a user submitted e-mail address to which we will send a copy to
        if (!empty($arrFormData['fields'])) {
            foreach ($arrFormData['fields'] as $fieldId => $arrField) {
                // check if field validation is set to e-mail
                if ($arrField['check_type'] == '2') {
                    $mail = trim($arrFormData['data'][$fieldId]);
                    if (\FWValidator::isEmail($mail)) {
                        $replyAddress = $mail;
                        break;
                    }
                }
                if ($arrField['type'] == 'special') {
                    switch ($arrField['special_type']) {
                         case 'access_firstname':
                            $firstname = trim($arrFormData['data'][$fieldId]);
                            break;

                         case 'access_lastname':
                            $lastname = trim($arrFormData['data'][$fieldId]);
                            break;

                        default:
                            break;
                    }
                }
            }

        }

        if (   $arrFormData['useEmailOfSender'] == 1
            && (!empty($firstname) || !empty($lastname))
        ) {
            $senderName = trim($firstname.' '.$lastname);
        } else {
            $senderName = $_CONFIG['coreGlobalPageTitle'];
        }

        // a recipient mail address which has been picked by sender
        $chosenMailRecipient = null;

        // fill the html and plaintext body with the submitted form data
        foreach ($arrFormData['fields'] as $fieldId => $arrField) {
            if($fieldId == 'unique_id') //generated for uploader. no interesting mail content.
                continue;

            $htmlValue = '';
            $plaintextValue = '';
            $textAreaKeys = array();

            switch ($arrField['type']) {
                case 'label':
                case 'fieldset':
// TODO: parse TH row instead
                case 'horizontalLine':
// TODO: add visual horizontal line
                    // we need to use a 'continue 2' here to first break out of the switch and then move over to the next iteration of the foreach loop
                    continue 2;
                    break;

                case 'file':
                case 'multi_file':
                    $htmlValue = "";
                    $plaintextValue = "";
                    if (isset($arrFormData['uploadedFiles'][$fieldId])) {
                        $htmlValue = "<ul>";
                        foreach ($arrFormData['uploadedFiles'][$fieldId] as $file) {
                            $htmlValue .= "<li><a href='".ASCMS_PROTOCOL."://".$_CONFIG['domainUrl'].\Env::get('cx')->getWebsiteOffsetPath().contrexx_raw2xhtml($file['path'])."' >".contrexx_raw2xhtml($file['name'])."</a></li>";
                            $plaintextValue  .= ASCMS_PROTOCOL."://".$_CONFIG['domainUrl'].\Env::get('cx')->getWebsiteOffsetPath().$file['path']."\r\n";
                        }
                        $htmlValue .= "</ul>";
                    }
                    break;

                case 'checkbox':
                    $plaintextValue = !empty($arrFormData['data'][$fieldId])
                                        ? $_ARRAYLANG['TXT_CONTACT_YES']
                                        : $_ARRAYLANG['TXT_CONTACT_NO'];
                    $htmlValue = $plaintextValue;
                    break;

                case 'recipient':
// TODO: check for XSS
                    $plaintextValue = $arrRecipients[$arrFormData['data'][$fieldId]]['lang'][FRONTEND_LANG_ID];
                    $htmlValue = $plaintextValue;
                    $chosenMailRecipient = $arrRecipients[$arrFormData['data'][$fieldId]]['email'];
                    break;

                case 'textarea':
                    //we need to know all textareas - they're indented differently then the rest of the other field types
                    $textAreaKeys[] = $fieldId;
                default :
                    $plaintextValue = isset($arrFormData['data'][$fieldId]) ? $arrFormData['data'][$fieldId] : '';
                    $htmlValue = contrexx_raw2xhtml($plaintextValue);
                    break;
            }

            $fieldLabel = $arrField['lang'][FRONTEND_LANG_ID]['name'];

            // try to fetch an e-mail address from submitted form date in case we were unable to fetch one from an input type with e-mail validation
            if (empty($replyAddress)) {
                $mail = $this->_getEmailAdressOfString($plaintextValue);
                if (\FWValidator::isEmail($mail)) {
                    $replyAddress = $mail;
                }
            }

            // parse html body
            if ($isHtml) {
                if (!empty($htmlValue)) {
                    if ($objTemplate->blockExists('field_'.$fieldId)) {
                        // parse field specific template block
                        $objTemplate->setVariable(array(
                            'FIELD_'.$fieldId.'_LABEL' => contrexx_raw2xhtml($fieldLabel),
                            'FIELD_'.$fieldId.'_VALUE' => $htmlValue,
                        ));
                        $objTemplate->parse('field_'.$fieldId);
                    } elseif ($objTemplate->blockExists('form_field')) {
                        // parse regular field template block
                        $objTemplate->setVariable(array(
                            'FIELD_LABEL'   => contrexx_raw2xhtml($fieldLabel),
                            'FIELD_VALUE'   => $htmlValue,
                        ));
                        $objTemplate->parse('form_field');
                    }
                } elseif ($objTemplate->blockExists('field_'.$fieldId)) {
                    // hide field specific template block, if present
                    $objTemplate->hideBlock('field_'.$fieldId);
                }
            }

            // parse plaintext body
            $tabCount = $maxlength - strlen($fieldLabel);
            $tabs     = ($tabCount == 0) ? 1 : $tabCount +1;

// TODO: what is this all about? - $value is undefined
            if($arrFormData['fields'][$fieldId]['type'] == 'recipient'){
                $value  = $arrRecipients[$value]['lang'][FRONTEND_LANG_ID];
            }

            if (in_array($fieldId, $textAreaKeys)) {
                // we're dealing with a textarea, don't indent value
                $plaintextBody .= $fieldLabel.":\n".$plaintextValue."\n";
            } else {
                $plaintextBody .= $fieldLabel.str_repeat(" ", $tabs).": ".$plaintextValue."\n";
            }

        }

        $arrSettings = $this->getSettings();

// TODO: this is some fixed plaintext message data -> must be ported to html body
        $message  = $_ARRAYLANG['TXT_CONTACT_TRANSFERED_DATA_FROM']." ".$_CONFIG['domainUrl']."\n\n";
        if ($arrSettings['fieldMetaDate']) {
            $message .= $_ARRAYLANG['TXT_CONTACT_DATE']." ".date(ASCMS_DATE_FORMAT, $arrFormData['meta']['time'])."\n\n";
        }
        $message .= $plaintextBody."\n\n";
        if ($arrSettings['fieldMetaHost']) {
            $message .= $_ARRAYLANG['TXT_CONTACT_HOSTNAME']." : ".contrexx_raw2xhtml($arrFormData['meta']['host'])."\n";
        }
        if ($arrSettings['fieldMetaIP']) {
            $message .= $_ARRAYLANG['TXT_CONTACT_IP_ADDRESS']." : ".contrexx_raw2xhtml($arrFormData['meta']['ipaddress'])."\n";
        }
        if ($arrSettings['fieldMetaLang']) {
            $message .= $_ARRAYLANG['TXT_CONTACT_BROWSER_LANGUAGE']." : ".contrexx_raw2xhtml($arrFormData['meta']['lang'])."\n";
        }
        $message .= $_ARRAYLANG['TXT_CONTACT_BROWSER_VERSION']." : ".contrexx_raw2xhtml($arrFormData['meta']['browser'])."\n";

        if (@include_once \Env::get('cx')->getCodeBaseLibraryPath().'/phpmailer/class.phpmailer.php') {
            $objMail = new \phpmailer();

            if ($_CONFIG['coreSmtpServer'] > 0 && @include_once \Env::get('cx')->getCodeBaseCorePath().'/SmtpSettings.class.php') {
                if (($arrSmtp = \SmtpSettings::getSmtpAccount($_CONFIG['coreSmtpServer'])) !== false) {
                    $objMail->IsSMTP();
                    $objMail->Host = $arrSmtp['hostname'];
                    $objMail->Port = $arrSmtp['port'];
                    $objMail->SMTPAuth = true;
                    $objMail->Username = $arrSmtp['username'];
                    $objMail->Password = $arrSmtp['password'];
                }
            }

            $objMail->CharSet = CONTREXX_CHARSET;
            if (!empty($replyAddress)) {
                $objMail->AddReplyTo($replyAddress);

                if ($arrFormData['sendCopy'] == 1) {
                    $objMail->AddAddress($replyAddress);
                }
            }

            if (!empty($replyAddress) && $arrFormData['useEmailOfSender'] == 1) {
                $objMail->SetFrom(
                    $replyAddress,
                    ($senderName !== $_CONFIG['coreGlobalPageTitle']) ? $senderName : ''
                );
            } else {
                $objMail->SetFrom($_CONFIG['coreAdminEmail'], $senderName);
            }

            $objMail->Subject = $arrFormData['subject'];

            if ($isHtml) {
                $objMail->Body = $objTemplate->get();
                $objMail->AltBody = $message;
            } else {
                $objMail->IsHTML(false);
                $objMail->Body = $message;
            }

            // attach submitted files to email
            if (count($arrFormData['uploadedFiles']) > 0 && $arrFormData['sendAttachment'] == 1) {
                foreach ($arrFormData['uploadedFiles'] as $arrFilesOfField) {
                    foreach ($arrFilesOfField as $file) {
                        $objMail->AddAttachment(\Env::get('cx')->getWebsiteDocumentRootPath().$file['path'], $file['name']);
                    }
                }
            }

            if ($chosenMailRecipient !== null) {
                if (!empty($chosenMailRecipient)) {
                    $objMail->AddAddress($chosenMailRecipient);
                    $objMail->Send();
                    $objMail->ClearAddresses();
                }
            } else {
                foreach ($arrFormData['emails'] as $sendTo) {
                    if (!empty($sendTo)) {
                        $objMail->AddAddress($sendTo);
                        $objMail->Send();
                        $objMail->ClearAddresses();
                    }
                }
            }
        }

        return true;
    }

    /**
     * Sort the form input data
     *
     * Sorts the input data of the form according of the field's order.
     * This method is used as the comparison function of uksort.
     *
     * @param string $a
     * @param string $b
     * @return integer
     */
    function _sortFormData($a, $b)
    {
        if (array_search($a, $this->arrFormFields) < array_search($b, $this->arrFormFields)) {
            return -1;
        } elseif (array_search($a, $this->arrFormFields) > array_search($b, $this->arrFormFields)) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Searches for a valid e-mail address
     *
     * Returns the first e-mail address that occours in the given string $string
     * @access private
     * @param string $string
     * @return mixed Returns an e-mail addess as string, or a boolean false if there is no valid e-mail address in the given string
     */
    function _getEmailAdressOfString($string)
    {
        $arrMatch = array();
        if (preg_match('/'.\FWValidator::REGEX_EMAIL.'/', $string, $arrMatch)) {
            return $arrMatch[0];
        } else {
            return false;
        }
    }

    /**
     * Shows the feedback message
     *
     * This parsed the feedback message and outputs it
     * @access private
     * @param array Details of the requested form
     * @see _getError(), \Cx\Core\Html\Sigma::setVariable
     */
    function _showFeedback($arrFormData)
    {
        global $_ARRAYLANG;

        $feedback = $arrFormData['feedback'];

        $arrMatch = array();
        if (isset($arrFormData['fields']) && preg_match_all('#\[\[('.
// TODO: $this->arrFormfields contains the labels of the form fields in raw format. That means that this array might contain some special characters that might brake the regular expression. Therefore, we must add a regular expression string sanitizer here.
                implode('|', array_unique(array_merge($this->arrFormFields, array_keys($arrFormData['data']))))
            .')\]\]#',
            $feedback,
            $arrMatch)
        ) {
            foreach ($arrFormData['fields'] as $id => $field) {
                if (in_array($field['lang'][FRONTEND_LANG_ID]['name'], $arrMatch[1])) {
                    switch ($field['type']) {
                        case 'checkbox':
                            $value = isset($arrFormData['data'][$id]) ? $_ARRAYLANG['TXT_CONTACT_YES'] : $_ARRAYLANG['TXT_CONTACT_NO'];
                            break;

                        case 'textarea':
                            $value = isset($arrFormData['data'][$id]) ? nl2br(contrexx_raw2xhtml($arrFormData['data'][$id])) : '';
                            break;

                        default:
                            $value = isset($arrFormData['data'][$id]) ? contrexx_raw2xhtml($arrFormData['data'][$id]) : '';
                            break;
                    }
                    $feedback = str_replace('[['.contrexx_raw2xhtml($field['lang'][FRONTEND_LANG_ID]['name']).']]', $value, $feedback);
                }
            }
        }

        $this->objTemplate->setVariable('CONTACT_FEEDBACK_TEXT', $this->_getError().stripslashes($feedback).'<br /><br />');
    }

    /**
     * Show Error
     *
     * Set the error message
     * @access private
     * @see \Cx\Core\Html\Sigma::setVariable(), \Cx\Core\Html\Sigma::get()
     * @return string Contact page
     */
    function _showError()
    {
        $this->objTemplate->setVariable('CONTACT_FEEDBACK_TEXT', $this->_getError());
        return $this->objTemplate->get();
    }

    /**
     * Get the error message
     *
     * Returns a formatted string with error messages if there
     * happened any errors
     * @access private
     * @see errorMsg
     * @return string Error messages
     */
    function _getError()
    {
        if (!empty($this->errorMsg)) {
            return '<span style="color:red;">'.$this->errorMsg.'</span>';
        } else {
            return '';
        }
    }

    /**
     * Gets the temporary upload location for files.
     *
     * @return array('path','webpath', 'dirname')
     * @throws ContactException
     */
    protected static function getTemporaryUploadPath($fieldId)
    {
        global $sessionObj;

        if (!isset($sessionObj)) $sessionObj = \cmsSession::getInstance();

        $tempPath = $_SESSION->getTempPath();
        $tempWebPath = $_SESSION->getWebTempPath();
        if($tempPath === false || $tempWebPath === false)
            throw new \Cx\Core_Modules\Contact\Controller\ContactException('could not get temporary session folder');

        $dirname = isset($_POST['contactFormUploadId_'.$fieldId])
                   ? contrexx_input2raw($_POST['contactFormUploadId_'.$fieldId])
                   : '';
        $result = array(
            $tempPath,
            $tempWebPath,
            $dirname
        );
        return $result;
    }

    //Uploader callback
    public static function uploadFinished($tempPath, $tempWebPath, $data, $uploadId, $fileInfos)
    {
        // in case uploader has been restricted to only allow one single file to be
        // uploaded, we'll have to clean up any previously uploaded files
        if (isset($data['singleFile'])) {
            if (count($fileInfos['name'])) {
                // new files have been uploaded -> remove existing files
                if (\Cx\Lib\FileSystem\FileSystem::exists($tempPath)) {
                    foreach (glob($tempPath.'/*') as $file) {
                        if (basename($file) == $fileInfos['name']) {
                            continue;
                        }
                        \Cx\Lib\FileSystem\FileSystem::delete_file($file);
                    }
                }
            }
        }

        return array($tempPath, $tempWebPath);
    }
}
