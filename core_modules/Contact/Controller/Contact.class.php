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
    protected $hasFileField = false;

    /**
     * Parse contact form page
     *
     * @param \Cx\Core\ContentManager\Model\Entity\Page $page Page object
     */
    public function getContactPage(
        \Cx\Core\ContentManager\Model\Entity\Page $page
    ) {
        $formId   = isset($_GET['cmd']) ? contrexx_input2int($_GET['cmd']) : 0;
        $cx       = \Cx\Core\Core\Controller\Cx::instanciate();
        $em       = $cx->getDb()->getEntityManager();
        $formRepo = $em->getRepository('Cx\Core_Modules\Contact\Model\Entity\Form');
        $themeRepo = new \Cx\Core\View\Model\Repository\ThemeRepository();
        $form  = $formRepo->find($formId);
        if (!$form) {
            $page->setContent('');
            return;
        }

        // we must force user based cache as the form might contain
        // user attribute fields data
        $cx->getComponent('Cache')->forceUserbasedPageCache();

        $theme = $themeRepo->findById(\Env::get('init')->getCurrentThemeId());
        $useCaptcha =
            !\FWUser::getFWUserObject()->objUser->login() &&
            $this->getContactFormCaptchaStatus($formId);
        $this->initContactForms($formId);
        // Create object for FormTemplate to initialize the Form and FormField Templates
        $formTemplate = new \Cx\Core_Modules\Contact\Model\Entity\FormTemplate(
            $form,
            $page,
            $theme
        );
        // Parse Form and FormField values
        $formTemplate->parseFormTemplate();
        $this->hasFileField = $formTemplate->hasFileField();

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
                    $this->dropUploads($arrFormData);
                    if (isset($arrFormData['showForm']) && !$arrFormData['showForm']) {
                        $formTemplate->hideFormText();
                        $formTemplate->hideForm();
                    }
                } else { //found errors while validating
                    $formTemplate->setCaptcha($useCaptcha);
                    $page->setContent($this->showError($formTemplate->getTemplate()));
                    return;
                }

                if (!$showThanks) {
                    $this->showFeedback($arrFormData, $formTemplate->getTemplate());
                } else {
                    $formTemplate->hideFormText();
                }
            }
        } else { //fresh display
            $formTemplate->showFormText();
            $formTemplate->setCaptcha($useCaptcha);
        }
        // Set the parsed submission form content as resolved page content
        $page->setContent($formTemplate->getHtml());
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
            $arrSettings = $this->getSettings();
            $arrFormData = array();
            $arrFormData['id'] = isset($_GET['cmd']) ? intval($_GET['cmd']) : 0;
            if ($this->getContactFormDetails($arrFormData['id'], $arrFormData['emails'], $arrFormData['subject'], $arrFormData['feedback'], $arrFormData['mailTemplate'], $arrFormData['showForm'], $arrFormData['useCaptcha'], $arrFormData['sendCopy'], $arrFormData['useEmailOfSender'], $arrFormData['htmlMail'], $arrFormData['sendAttachment'], $arrFormData['saveDataInCRM'], $arrFormData['crmCustomerGroups'], $arrFormData['sendMultipleReply'])) {
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

            $arrFormData['data'] = array();
            $arrFormData['meta'] = array();
            foreach ($_POST as $key => $value) {
                if ((($value === '0') || !empty($value)) && !in_array($key, array('Submit', 'submitContactForm', 'contactFormCaptcha'))) {
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
                $ipAddress = contrexx_input2raw($_SERVER["HTTP_X_FORWARDED_FOR"]);
            } else {
                $ipAddress = contrexx_input2raw($_SERVER["REMOTE_ADDR"]);
            }

            $arrFormData['meta'] = array(
                'time'      => time(),
                'host'      => '',
                'lang'      => '',
                'ipaddress' => '',
                'browser'   => '',
            );

            if ($arrSettings['fieldMetaHost']) {
                $net = \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Net');
                $arrFormData['meta']['host'] = contrexx_input2raw(
                    $net->getHostByAddr($ipAddress)
                );
            }

            if ($arrSettings['fieldMetaLang']) {
                $arrFormData['meta']['lang'] = contrexx_input2raw($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
            }

            if ($arrSettings['fieldMetaIP']) {
                $arrFormData['meta']['ipaddress'] = $ipAddress;
            }

            // TODO: implement browser option
            if ($arrSettings['fieldMetaBrowser']) {
                $arrFormData['meta']['browser'] = contrexx_input2raw($_SERVER["HTTP_USER_AGENT"]);
            }

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
     * @see getSettings(), errorMsg, FWSystem::getMaxUploadFileSize()
     * @return array A list of files that have been stored successfully in the system
     */
    function _uploadFilesLegacy($arrFields)
    {
        global $_ARRAYLANG;

        $arrSettings = $this->getSettings();

        $arrFiles = array();
        if (isset($_FILES) && is_array($_FILES)) {
            foreach (array_keys($_FILES) as $file) {
                $fileName    =  !empty($_FILES[$file]['name'])
                              ? \Cx\Lib\FileSystem\FileSystem::replaceCharacters($_FILES[$file]['name'])
                              : '';
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

                if ($isRequired && ($value !== '0') && empty($value)) {
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

        $arrSettings = $this->getSettings();
        if (!$arrSettings['storeFormSubmissions']) {
            return true;
        }

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
        $replyAddresses = array();
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
                // fetch first- and lastname from user attributes
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

                // in case notification email shall only be sent to one (the
                // first) recipient, we can stop looking for additional
                // recipient emails
                if (count($replyAddresses) == 1 && !$arrFormData['sendMultipleReply']) {
                    continue;
                }

                // if the input field validation is set to 'e-mail' (2)
                // then the field might contain a potential recipient email
                if ($arrField['check_type'] != '2') {
                    continue;
                }

                // check if the input data is a valid email address
                $mail = trim($arrFormData['data'][$fieldId]);
                if (!\FWValidator::isEmail($mail)) {
                    continue;
                }

                // add email address from submitted form data to list of
                // recipients that shall receive the notification mail
                $replyAddresses[] = $mail;
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

        // fetch settings
        $arrSettings = $this->getSettings();

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
                            // only add uploaded files as links if form
                            // submission storage is enabled
                            if ($arrSettings['storeFormSubmissions']) {
                                $htmlValue .= "<li><a href='".ASCMS_PROTOCOL."://".$_CONFIG['domainUrl'].\Env::get('cx')->getWebsiteOffsetPath().contrexx_raw2xhtml($file['path'])."' >".contrexx_raw2xhtml($file['name'])."</a></li>";
                                $plaintextValue  .= ASCMS_PROTOCOL."://".$_CONFIG['domainUrl'].\Env::get('cx')->getWebsiteOffsetPath().$file['path']."\r\n";
                            } else {
                                $htmlValue .= "<li>".contrexx_raw2xhtml($file['name'])."</li>";
                                $plaintextValue  .= $file['name']."\r\n";
                            }
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

            // try to fetch an e-mail address from submitted form data in case
            // we were unable to fetch one from an input type with e-mail
            // validation
            if (empty($replyAddresses)) {
                $mail = $this->_getEmailAdressOfString($plaintextValue);
                if (\FWValidator::isEmail($mail)) {
                    $replyAddresses[] = $mail;
                }
            }

            // parse html body
            if ($isHtml) {
                if (!empty($htmlValue)) {
                    if ($objTemplate->blockExists('field_'.$fieldId)) {
                        // parse field specific template block
                        $objTemplate->setVariable(array(
                            'FIELD_'.$fieldId.'_LABEL' => strip_tags($fieldLabel),
                            'FIELD_'.$fieldId.'_VALUE' => $htmlValue,
                        ));
                        $objTemplate->parse('field_'.$fieldId);
                    } elseif ($objTemplate->blockExists('form_field')) {
                        // parse regular field template block
                        $objTemplate->setVariable(array(
                            'FIELD_LABEL'   => strip_tags($fieldLabel),
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

            if (in_array($fieldId, $textAreaKeys)) {
                // we're dealing with a textarea, don't indent value
                $plaintextBody .= $fieldLabel.":\n".$plaintextValue."\n";
            } else {
                $plaintextBody .= $fieldLabel.str_repeat(" ", $tabs).": ".$plaintextValue."\n";
            }

        }

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
        if ($arrSettings['fieldMetaBrowser']) {
            $message .= $_ARRAYLANG['TXT_CONTACT_BROWSER_VERSION']." : ".contrexx_raw2xhtml($arrFormData['meta']['browser'])."\n";
        }

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        $objMail->SetFrom($_CONFIG['coreAdminEmail'], $senderName);

        foreach ($replyAddresses as $replyAddress) {
            if ($arrFormData['sendCopy'] == 1) {
                $objMail->AddAddress($replyAddress);
            }

            if ($arrFormData['sendMultipleReply']) {
                continue;
            }

            // if option sendMultipleReply is not set,
            // then $replyAddresses does only contain one address
            // therefore, the following statement will only be called once
            $objMail->AddReplyTo($replyAddress);

            if (!$arrFormData['useEmailOfSender']) {
                break;
            }

            $objMail->SetFrom(
                $replyAddress,
                ($senderName !== $_CONFIG['coreGlobalPageTitle']) ? $senderName : ''
            );
            break;
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

        return true;
    }

    /**
     * Drop any submitted files in case the storage of form submission
     * is not allowed
     *
     * @param   array   $arrFormData    Details of the contact request
     */
    protected function dropUploads($arrFormData) {
        $arrSettings = $this->getSettings();

        // abort in case storage of form submission is allowed
        if ($arrSettings['storeFormSubmissions']) {
            return;
        }

        // abort in case no files have been submitted
        if (!count($arrFormData['uploadedFiles'])) {
            return;
        }

        // drop any uploaded files
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        foreach (array_keys($arrFormData['uploadedFiles']) as $fieldId) {
            if (!isset($this->depositionTarget[$fieldId])) {
                continue;
            }
            $path = $cx->getWebsiteDocumentRootPath() .
                $this->depositionTarget[$fieldId];
            \Cx\Lib\FileSystem\FileSystem::delete_folder($path, true);
        }
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
        return current(\FWValidator::getEmailAsArray($string));
    }

    /**
     * Show the feedback message
     *
     * @param array               $arrFormData Details of the requested form
     * @param \Cx\Core\Html\Sigma $template    Template object
     */
    protected function showFeedback($arrFormData, \Cx\Core\Html\Sigma $template)
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

        $template->setVariable(
            'CONTACT_FEEDBACK_TEXT',
            $this->_getError() . stripslashes($feedback) . '<br /><br />'
        );
    }

    /**
     * Show Error
     *
     * @param \Cx\Core\Html\Sigma $template Template object
     * @return string Form content with Error message
     */
    protected function showError(\Cx\Core\Html\Sigma $template)
    {
        $template->setVariable('CONTACT_FEEDBACK_TEXT', $this->_getError());
        return $template->get();
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
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $session = $cx->getComponent('Session')->getSession();

        $tempPath = $session->getTempPath();
        $tempWebPath = $session->getWebTempPath();
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
