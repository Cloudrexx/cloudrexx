<?php

/**
 * Contact
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Comvation Development Team <info@comvation.com>
 * @version     1.0.0
 * @package     contrexx
 * @subpackage  core_module_contact
 * @todo        Edit PHP DocBlocks!
 */

/**
 * @ignore
 */
require_once ASCMS_CORE_MODULE_PATH.'/contact/lib/ContactLib.class.php';

/**
 * Contact manager
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author      Comvation Development Team <info@comvation.com>
 * @access      public
 * @version     1.0.0
 * @package     contrexx
 * @subpackage  core_module_contact
 */
class ContactManager extends ContactLib
{
    var $_objTpl;

    var $_statusMessageOk;
    var $_statusMessageErr;

    var $_arrFormFieldTypes;
    var $_arrUserAccountData;

    var $boolHistoryEnabled = false;
    var $boolHistoryActivate = false;

    var $_csvSeparator = null;
    var $_csvEnclosure = null;
    var $_csvCharset = null;
    var $_csvLFB = null;

    var $_pageTitle = '';

    var $_invalidRecipients = false;

    //Doctrine Entity Manager
    var $em = null;

    private $nonValueFormFieldTypes = array(
                'horizontalLine',
                'fieldset',
                'label',
            );    
    
    const formMailTemplate = '<table>
    <tbody>
        <!-- BEGIN form_field -->
        <tr>
            <td>[[FIELD_LABEL]]</td>
            <td>[[FIELD_VALUE]]</td>
        </tr>
        <!-- END form_field -->
    </tbody>
</table>';


    /**
     * PHP5 constructor
     * @global HTML_Template_Sigma
     * @global array
     * @global array
     */
    function __construct()
    {
        global $objTemplate, $_ARRAYLANG, $_CONFIG;

        $this->em = Env::em();

        $this->_objTpl = new HTML_Template_Sigma(ASCMS_CORE_MODULE_PATH.'/contact/template');
        CSRF::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $objTemplate->setVariable("CONTENT_NAVIGATION", "   <a href='index.php?cmd=contact' title=".$_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS'].">".$_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS']."</a>
                                                            <a href='index.php?cmd=contact&amp;act=settings' title=".$_ARRAYLANG['TXT_CONTACT_SETTINGS'].">".$_ARRAYLANG['TXT_CONTACT_SETTINGS']."</a>");

        $this->_arrFormFieldTypes = array(
            'text'          => $_ARRAYLANG['TXT_CONTACT_TEXTBOX'],
            'label'         => $_ARRAYLANG['TXT_CONTACT_TEXT'],
            'checkbox'      => $_ARRAYLANG['TXT_CONTACT_CHECKBOX'],
            'checkboxGroup' => $_ARRAYLANG['TXT_CONTACT_CHECKBOX_GROUP'],
            'country'       => $_ARRAYLANG['TXT_CONTACT_COUNTRY'],
            'date'          => $_ARRAYLANG['TXT_CONTACT_DATE'],
            'file'          => $_ARRAYLANG['TXT_CONTACT_FILE_UPLOAD'],
            'fieldset'      => $_ARRAYLANG['TXT_CONTACT_FIELDSET'],
            'hidden'        => $_ARRAYLANG['TXT_CONTACT_HIDDEN_FIELD'],
            'horizontalLine'=> $_ARRAYLANG['TXT_CONTACT_HORIZONTAL_LINE'],
            'password'      => $_ARRAYLANG['TXT_CONTACT_PASSWORD_FIELD'],
            'radio'         => $_ARRAYLANG['TXT_CONTACT_RADIO_BOXES'],
            'select'        => $_ARRAYLANG['TXT_CONTACT_SELECTBOX'],
            'textarea'      => $_ARRAYLANG['TXT_CONTACT_TEXTAREA'],
            'recipient'     => $_ARRAYLANG['TXT_CONTACT_RECEIVER_ADDRESSES_SELECTION'],
        );

	$this->_arrUserAccountData = array(
            'access_picture'       => $_ARRAYLANG['TXT_CONTACT_PICTURE'],
            'access_gender'        => $_ARRAYLANG['TXT_CONTACT_GENDER'],
            'access_title'         => $_ARRAYLANG['TXT_CONTACT_TITLE'],
            'access_firstname'     => $_ARRAYLANG['TXT_CONTACT_FIRST_NAME'],
            'access_lastname'      => $_ARRAYLANG['TXT_CONTACT_LAST_NAME'],
            'access_company'       => $_ARRAYLANG['TXT_CONTACT_COMPANY'],
            'access_address'       => $_ARRAYLANG['TXT_CONTACT_ADDRESS'],
            'access_city'          => $_ARRAYLANG['TXT_CONTACT_CITY'],
            'access_zip'           => $_ARRAYLANG['TXT_CONTACT_ZIP'],
            'access_country'       => $_ARRAYLANG['TXT_CONTACT_COUNTRY'],
            'access_phone_office'  => $_ARRAYLANG['TXT_CONTACT_PHONE_OFFICE'],
            'access_phone_private' => $_ARRAYLANG['TXT_CONTACT_PHONE_PRIVATE'],
            'access_phone_mobile'  => $_ARRAYLANG['TXT_CONTACT_PHONE_MOBILE'],
            'access_phone_fax'     => $_ARRAYLANG['TXT_CONTACT_PHONE_FAX'],
            'access_birthday'      => $_ARRAYLANG['TXT_CONTACT_BIRTHDAY'],
            'access_website'       => $_ARRAYLANG['TXT_CONTACT_WEBSITE'],
            'access_profession'    => $_ARRAYLANG['TXT_CONTACT_PROFESSION'],
            'access_interests'     => $_ARRAYLANG['TXT_CONTACT_INTERESTS'],
            'access_signature'     => $_ARRAYLANG['TXT_CONTACT_SIGNATURE']
	);

        $this->initContactForms();
        $this->initCheckTypes();

        $this->boolHistoryEnabled = ($_CONFIG['contentHistoryStatus'] == 'on') ? true : false;

        if (Permission::checkAccess(78, 'static', true)) {
            $this->boolHistoryActivate = true;
        }
    }

    /**
     * Get page
     *
     * Get the development page
     *
     * @access public
     * @global HTML_Template_Sigma
     */
    function getPage()
    {
        global $objTemplate;

        if (!isset($_REQUEST['act'])) {
            $_REQUEST['act'] = '';
        }

        if (!isset($_REQUEST['tpl'])) {
            $_REQUEST['tpl'] = '';
        }

        switch ($_REQUEST['act']) {
            case 'settings':
                Permission::checkAccess(85, 'static');
                $this->_getSettingsPage();
                break;

            case 'entries':
                $this->_getEntriesPage();
                break;

            default:
                $this->_getContactFormPage();
                break;
        }

        $objTemplate->setVariable(array(
                'CONTENT_TITLE'             => $this->_pageTitle,
                'CONTENT_OK_MESSAGE'        => $this->_statusMessageOk,
                'CONTENT_STATUS_MESSAGE'    => $this->_statusMessageErr,
                'ADMIN_CONTENT'             => $this->_objTpl->get()
        ));
    }

    function _getEntriesPage()
    {
        global $_ARRAYLANG;

        $entryId = isset($_REQUEST['entryId']) ? intval($_REQUEST['entryId']) : 0;
        $formId = isset($_REQUEST['formId']) ? intval($_REQUEST['formId']) : 0;

        $arrEntry = &$this->getFormEntry($entryId);
        if (is_array($arrEntry)) {

            $this->_objTpl->loadTemplateFile('module_contact_entries_details.html');
            $this->_pageTitle = $_ARRAYLANG['TXT_CONTACT_ENTRIE_DETAILS'];

            $this->_objTpl->setVariable(array(
                    'CONTACT_FORM_ENTRY_ID'                 => $entryId,
                    'CONTACT_ENTRY_TITLE'                   => str_replace('%DATE%', date(ASCMS_DATE_FORMAT, $arrEntry['time']), $_ARRAYLANG['TXT_CONTACT_ENTRY_OF_DATE']),
                    'CONTACT_ENTRY'                         => $this->_getEntryDetails($arrEntry, $formId),
                    'CONTACT_FORM_ID'                       => $formId
            ));

            $this->_objTpl->setVariable(array(
                    'TXT_CONTACT_BACK'                      => $_ARRAYLANG['TXT_CONTACT_BACK'],
                    'TXT_CONTACT_DELETE'                    => $_ARRAYLANG['TXT_CONTACT_DELETE'],
                    'TXT_CONTACT_CONFIRM_DELETE_ENTRY'      => $_ARRAYLANG['TXT_CONTACT_CONFIRM_DELETE_ENTRY'],
                    'TXT_CONTACT_ACTION_IS_IRREVERSIBLE'    => $_ARRAYLANG['TXT_CONTACT_ACTION_IS_IRREVERSIBLE'],
                    'TXT_CONTACT_CONFIRM_DELETE_ENTRIES'    => $_ARRAYLANG['TXT_CONTACT_CONFIRM_DELETE_ENTRIES']
            ));
        } else {
            $this->_contactFormEntries();
        }
    }

    function _getSettingsPage()
    {
        switch ($_REQUEST['tpl']) {
            case 'save':
                $this->_saveSettings();

            default:
                $this->_settings();
                break;
        }
    }

    function _settings()
    {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_contact_settings.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CONTACT_SETTINGS'];

        $arrSettings = &$this->getSettings();

        $this->_objTpl->setVariable(array(
                'TXT_CONTACT_SETTINGS'                          => $_ARRAYLANG['TXT_CONTACT_SETTINGS'],
                'TXT_CONTACT_SAVE'                              => $_ARRAYLANG['TXT_CONTACT_SAVE'],
                'TXT_CONTACT_FILE_UPLOAD_DEPOSITION_PATH'       => $_ARRAYLANG['TXT_CONTACT_FILE_UPLOAD_DEPOSITION_PATH'],
                'TXT_CONTACT_SPAM_PROTECTION_WORD_LIST'         => $_ARRAYLANG['TXT_CONTACT_SPAM_PROTECTION_WORD_LIST'],
                'TXT_CONTACT_SPAM_PROTECTION_WW_DESCRIPTION'    => $_ARRAYLANG['TXT_CONTACT_SPAM_PROTECTION_WW_DESCRIPTION'],
                'TXT_CONTACT_DATE'                              => $_ARRAYLANG['TXT_CONTACT_DATE'],
                'TXT_CONTACT_HOSTNAME'                          => $_ARRAYLANG['TXT_CONTACT_HOSTNAME'],
                'TXT_CONTACT_BROWSER_LANGUAGE'                  => $_ARRAYLANG['TXT_CONTACT_BROWSER_LANGUAGE'],
                'TXT_CONTACT_IP_ADDRESS'                        => $_ARRAYLANG['TXT_CONTACT_IP_ADDRESS'],
                'TXT_CONTACT_META_DATE_BY_EXPORT'               => $_ARRAYLANG['TXT_CONTACT_META_DATE_BY_EXPORT']
        ));

        $this->_objTpl->setVariable(array(
                'CONTACT_FILE_UPLOAD_DEPOSITION_PATH'   => $arrSettings['fileUploadDepositionPath'],
                'CONTACT_SPAM_PROTECTION_WORD_LIST'     => $arrSettings['spamProtectionWordList'],
                'CONTACT_FIELD_META_DATE'               => $arrSettings['fieldMetaDate'] == '1' ? 'checked="checked"' : '',
                'CONTACT_FIELD_META_LANG'               => $arrSettings['fieldMetaLang'] == '1' ? 'checked="checked"' : '',
                'CONTACT_FIELD_META_HOST'               => $arrSettings['fieldMetaHost'] == '1' ? 'checked="checked"' : '',
                'CONTACT_FIELD_META_IP'                 => $arrSettings['fieldMetaIP'] == '1' ? 'checked="checked"' : '',
        ));
    }

    function _saveSettings()
    {
        global $objDatabase, $_ARRAYLANG;

        $saveStatus = true;

        if (isset($_REQUEST['save'])) {
            $arrSettings = &$this->getSettings();

            $arrNewSettings = array(
                    'fileUploadDepositionPath'  => isset($_POST['contactFileUploadDepositionPath']) ? trim(contrexx_stripslashes($_POST['contactFileUploadDepositionPath'])) : '',
                    'spamProtectionWordList'    => isset($_POST['contactSpamProtectionWordList']) ? explode(',', $_POST['contactSpamProtectionWordList']) : '',
                    'fieldMetaDate'             => isset($_POST['contactFieldMetaDate']) ? intval($_POST['contactFieldMetaDate']) : 0,
                    'fieldMetaHost'             => isset($_POST['contactFieldMetaHost']) ? intval($_POST['contactFieldMetaHost']) : 0,
                    'fieldMetaLang'             => isset($_POST['contactFieldMetaLang']) ? intval($_POST['contactFieldMetaLang']) : 0,
                    'fieldMetaIP'               => isset($_POST['contactFieldMetaIP']) ? intval($_POST['contactFieldMetaIP']) : 0
            );

            if (strpos($arrNewSettings['fileUploadDepositionPath'], '..') || empty($arrNewSettings['fileUploadDepositionPath'])) {
                $arrNewSettings['fileUploadDepositionPath'] = $arrSettings['fileUploadDepositionPath'];
            }

            if (!empty($arrNewSettings['spamProtectionWordList'])) {
                $arrTmpWordList = array();
                foreach ($arrNewSettings['spamProtectionWordList'] as $word) {
                    array_push($arrTmpWordList, contrexx_stripslashes(trim($word)));
                }
                $arrNewSettings['spamProtectionWordList'] = implode(',', $arrTmpWordList);
            } else {
                $arrNewSettings['spamProtectionWordList'] = $arrSettings['spamProtectionWordList'];
            }

            foreach ($arrNewSettings as $field => $status) {
                if ($status != $arrSettings[$field]) {
                    if ($objDatabase->Execute("UPDATE ".DBPREFIX."module_contact_settings SET setvalue='".$status."' WHERE setname='".$field."'") === false) {
                        $saveStatus = false;
                    }
                }
            }

            if ($saveStatus) {
                $this->_statusMessageOk = $_ARRAYLANG['TXT_CONTACT_SETTINGS_UPDATED'];
            } else {
                $this->_statusMessageErr = $_ARRAYLANG['TXT_CONTACT_DATABASE_QUERY_ERROR'];
            }

            $this->initSettings();
        }
    }

    function _getContactFormPage()
    {
        switch ($_REQUEST['tpl']) {
            case 'edit':
                $this->_modifyForm();
                break;

            case 'copy':
                $this->_modifyForm(true);
                break;

            case 'save':
                $this->_saveForm();
                break;

            case 'deleteForm':
                $this->_deleteForm();
                break;

            case 'deleteEntry':
                $this->_deleteFormEntry();
                break;

            case 'code':
                $this->_sourceCode();
                break;

            case 'entries':
                $this->_contactFormEntries();
                break;

            case 'csv':
                $this->_getCsv();
                break;

            case 'newContent':
                $this->_createContentPage();
                break;

            case 'updateContent':
                $this->_updateContentSite();
                $this->_contactForms();
                break;

            default:
                $this->_contactForms();
                break;
        }
    }

    function _contactFormEntries()
    {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_contact_form_entries.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CONTACT_FORM_ENTRIES'];

        $paging = '';
        $pos = 0;
        $maxFields = 3;
        $formId = isset($_GET['formId']) ? intval($_GET['formId']) : 0;

        if ($formId > 0) {
            if (isset($this->arrForms[$formId]['lang'][FRONTEND_LANG_ID])) {
                $selectedInterfaceLanguage = FRONTEND_LANG_ID;
            } elseif (isset($this->arrForms[$formId]['lang'][FWLanguage::getDefaultLangId()])) {
                $selectedInterfaceLanguage = FWLanguage::getDefaultLangId();
            } else {
                $selectedInterfaceLanguage = key($this->arrForms[$formId]['lang']);
            }

            if (isset($_GET['pos'])) {
                $pos = intval($_GET['pos']);
            }

            $arrCols = array();
            $arrEntries = &$this->getFormEntries($formId, $arrCols, $pos, $paging);
            if (count($arrEntries) > 0) {
                $arrFormFields = &$this->getFormFields($formId);
                $arrFormFieldNames = &$this->getFormFieldNames($formId);
                
                $this->_objTpl->setGlobalVariable(array(
                        'TXT_CONTACT_DELETE_ENTRY'              => $_ARRAYLANG['TXT_CONTACT_DELETE_ENTRY'],
                        'TXT_CONTACT_DETAILS'                   => $_ARRAYLANG['TXT_CONTACT_DETAILS'],
                        'CONTACT_FORM_ID'                       => $formId
                ));

                $this->_objTpl->setVariable(array(
                        'TXT_CONTACT_BACK'                      => $_ARRAYLANG['TXT_CONTACT_BACK'],
                        'TXT_CONTACT_CONFIRM_DELETE_ENTRY'      => $_ARRAYLANG['TXT_CONTACT_CONFIRM_DELETE_ENTRY'],
                        'TXT_CONTACT_ACTION_IS_IRREVERSIBLE'    => $_ARRAYLANG['TXT_CONTACT_ACTION_IS_IRREVERSIBLE'],
                        'TXT_CONTACT_DATE'                      => $_ARRAYLANG['TXT_CONTACT_DATE'],
                        'TXT_CONTACT_FUNCTIONS'                 => $_ARRAYLANG['TXT_CONTACT_FUNCTIONS'],
                        'TXT_CONTACT_SELECT_ALL'                => $_ARRAYLANG['TXT_CONTACT_SELECT_ALL'],
                        'TXT_CONTACT_DESELECT_ALL'              => $_ARRAYLANG['TXT_CONTACT_DESELECT_ALL'],
                        'TXT_CONTACT_SUBMIT_SELECT'             => $_ARRAYLANG['TXT_CONTACT_SUBMIT_SELECT'],
                        'TXT_CONTACT_SUBMIT_DELETE'             => $_ARRAYLANG['TXT_CONTACT_SUBMIT_DELETE'],
                        'CONTACT_FORM_COL_NUMBER'               => (count($arrCols) > $maxFields ? $maxFields+1 : count($arrCols)) + 3,
                        'CONTACT_FORM_ENTRIES_TITLE'            => str_replace('%NAME%', contrexx_raw2xhtml($this->arrForms[$formId]['lang'][$selectedInterfaceLanguage]['name']), $_ARRAYLANG['TXT_CONTACT_ENTRIES_OF_NAME']),
                        'CONTACT_FORM_PAGING'                   => $paging
                ));
                
                $colNr = 0;
                foreach ($arrCols as $col) {
                    if ($colNr == $maxFields) {
                        break;
                    }
                    $this->_objTpl->setVariable('CONTACT_COL_NAME', contrexx_raw2xhtml($arrFormFields[$col]['lang'][$selectedInterfaceLanguage]['name']));
                    $this->_objTpl->parse('contact_col_names');
                    $colNr++;
                }

                $rowNr = 0;
                foreach ($arrEntries as $entryId => $arrEntry) {
                    $this->_objTpl->setVariable('CONTACT_FORM_ENTRIES_ROW_CLASS', $rowNr % 2 == 0 ? 'row2' : 'row1');

                    $this->_objTpl->setVariable(array(
                            'CONTACT_FORM_DATE'     => '<a href="index.php?cmd=contact&amp;act=entries&amp;formId='.$formId.'&amp;entryId='.$entryId.'" title="'.$_ARRAYLANG['TXT_CONTACT_DETAILS'].'">'.date(ASCMS_DATE_FORMAT, $arrEntry['time']).'</a>',
                            'CONTACT_FORM_ENTRY_ID' => $entryId
                    ));

                    $this->_objTpl->parse('contact_form_entry_data');

                    $colNr  = 0;
                    $langId = $arrEntry['langId']; 
                    foreach ($arrCols as $col) {
                        if ($colNr == $maxFields) {
                            break;
                        }

                        if (isset($arrEntry['data'][$col])) {
                            if (isset($arrFormFields[$col]) && $arrFormFields[$col]['type'] == 'file') {
                                $fileData = $arrEntry['data'][$col];
                                if($fileData) { 
                                    //new style entry; multiple files and links
                                    $arrFiles = explode('*', $fileData);
                                    foreach($arrFiles as $file) {
                                        $value .= '<a href="'.ASCMS_PATH_OFFSET.contrexx_raw2xhtml($file).'" target="_blank" onclick="return confirm(\''.$_ARRAYLANG['TXT_CONTACT_CONFIRM_OPEN_UPLOADED_FILE'].'\')">'.ASCMS_PATH_OFFSET.contrexx_raw2xhtml($file).'</a>&nbsp;';
                                    }
                                }
                            } elseif (isset($arrFormFields[$col]) && $arrFormFields[$col]['type'] == 'recipient') {
                                $recipient = $this->getRecipients($formId, false);
                                $value = htmlentities($recipient[$arrEntry['data'][$col]]['lang'][$langId], ENT_QUOTES, CONTREXX_CHARSET);
                            } elseif ($arrFormFields[$col]['type'] == 'checkbox') {
                                $value = $_ARRAYLANG['TXT_CONTACT_YES'];
                            } else {
                                $value = htmlentities($arrEntry['data'][$col], ENT_QUOTES, CONTREXX_CHARSET);
                            }
                        } else {
                            $value = '&nbsp;';
                        }
                        if (empty($value)) {
                            $value = '&nbsp;';
                        }

                        /*
                         * Sets value if checkbox is not selected
                         */
                        if ($arrFormFields[$arrFormFieldNames[$col]]['type'] == 'checkbox' && $arrEntry['data'][$col] == null) {
                            $value = $_ARRAYLANG['TXT_CONTACT_NO'];
                        }

                        $this->_objTpl->setVariable('CONTACT_FORM_ENTRIES_CELL_CONTENT', $value);
                        $this->_objTpl->parse('contact_form_entry_data');

                        $colNr++;
                    }
                    $this->_objTpl->parse('contact_form_entries');

                    $rowNr++;
                }
            } else {
                $this->_contactForms();
            }
        } else {
            $this->_contactForms();
        }
    }

    function _contactForms()
    {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_contact_forms_overview.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS'];

        $this->_objTpl->setVariable(array(
                'TXT_CONTACT_CONFIRM_DELETE_FORM'           => $_ARRAYLANG['TXT_CONTACT_CONFIRM_DELETE_FORM'],
                'TXT_CONTACT_FORM_ENTRIES_WILL_BE_DELETED'  => $_ARRAYLANG['TXT_CONTACT_FORM_ENTRIES_WILL_BE_DELETED'],
                'TXT_CONTACT_ACTION_IS_IRREVERSIBLE'        => $_ARRAYLANG['TXT_CONTACT_ACTION_IS_IRREVERSIBLE'],
                'TXT_CONTACT_LATEST_ENTRY'                  => $_ARRAYLANG['TXT_CONTACT_LATEST_ENTRY'],
                'TXT_CONTACT_NUMBER_OF_ENTRIES'             => $_ARRAYLANG['TXT_CONTACT_NUMBER_OF_ENTRIES'],
                'TXT_CONTACT_CONTACT_FORMS'                 => $_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS'],
                'TXT_CONTACT_ID'                            => $_ARRAYLANG['TXT_CONTACT_ID'],
                'TXT_CONTACT_NAME'                          => $_ARRAYLANG['TXT_CONTACT_NAME'],
                'TXT_CONTACT_FUNCTIONS'                     => $_ARRAYLANG['TXT_CONTACT_FUNCTIONS'],
                'TXT_CONTACT_ADD_NEW_CONTACT_FORM'          => $_ARRAYLANG['TXT_CONTACT_ADD_NEW_CONTACT_FORM'],
                'TXT_CONTACT_CONFIRM_DELETE_CONTENT_SITE'   => $_ARRAYLANG['TXT_CONTACT_CONFIRM_DELETE_CONTENT_SITE'],
                'TXT_CONTACT_LANGUAGE'                      => $_ARRAYLANG['TXT_CONTACT_LANGUAGE']
        ));

        $this->_objTpl->setGlobalVariable(array(
                'TXT_CONTACT_MODIFY'                        => $_ARRAYLANG['TXT_CONTACT_MODIFY'],
                'TXT_CONTACT_DELETE'                        => $_ARRAYLANG['TXT_CONTACT_DELETE'],
                'TXT_CONTACT_SHOW_SOURCECODE'               => $_ARRAYLANG['TXT_CONTACT_SHOW_SOURCECODE'],
                'TXT_CONTACT_USE_AS_TEMPLATE'               => $_ARRAYLANG['TXT_CONTACT_USE_AS_TEMPLATE'],
                'TXT_CONTACT_GET_CSV'                       => $_ARRAYLANG['TXT_CONTACT_GET_CSV'],
                'TXT_CONTACT_DOWNLOAD'                      => $_ARRAYLANG['TXT_CONTACT_DOWNLOAD']
        ));
        
        $rowNr = 0;
        if (is_array($this->arrForms)) {
            foreach ($this->arrForms as $formId => $arrForm) {
                $formName = '';
                $entryCount = '-';

                $pageRepo = $this->em->getRepository('\Cx\Model\ContentManager\Page');
                $page = $pageRepo->findOneBy(array('module' => 'contact', 'cmd' => $formId));

                $pageExists = $page !== null;
                
                $this->_objTpl->setGlobalVariable('CONTACT_FORM_ID', $formId);

                if (isset($arrForm['lang'][FRONTEND_LANG_ID])) {
                    $selectedInterfaceLanguage = FRONTEND_LANG_ID;
                } elseif (isset($arrForm['lang'][FWLanguage::getDefaultLangId()])) {
                    $selectedInterfaceLanguage = FWLanguage::getDefaultLangId();
                } else {
                    $selectedInterfaceLanguage = key($arrForm['lang']);
                }

                $lang = array();
                foreach ($arrForm['lang'] as $langId => $value) {
                    $lang[] = FWLanguage::getLanguageCodeById($langId);
                }
                $langString = implode(', ',$lang);
                
                $formName = contrexx_raw2xhtml($arrForm['lang'][$selectedInterfaceLanguage]['name']);

                // check if the form contains submitted data
                if ($arrForm['number'] > 0) {
                    $entryCount = $arrForm['number'];
                    $formName = "<a href='index.php?cmd=contact&amp;act=forms&amp;tpl=entries&amp;formId=".$formId."' title='".$_ARRAYLANG['TXT_CONTACT_SHOW_ENTRIES']."'>".$formName."</a>";

                    $this->_objTpl->touchBlock('contact_export');
                } else {
                    $this->_objTpl->hideBlock('contact_export');
                }

                $this->_objTpl->setVariable(array(
                        'CONTACT_FORM_ROW_CLASS'            => $rowNr % 2 == 1 ? 'row1' : 'row2',
                        'CONTACT_FORM_NAME'                 => $formName,
                        'CONTACT_FORM_LAST_ENTRY'           => $arrForm['last'] ? date(ASCMS_DATE_FORMAT, $arrForm['last']) : '-',
                        'CONTACT_FORM_NUMBER_OF_ENTRIES'    => $entryCount,
                        'CONTACT_DELETE_CONTENT'            => $pageExists ? 'true' : 'false',
                        'CONTACT_FORM_LANGUAGES'            => $langString
                ));

                $this->_objTpl->parse('contact_contact_forms');

                $rowNr++;
            }
        }
    }


    /**
     * Display recipients in backend
     *
     * @param array $arrRecipients
     */
    function _showRecipients($arrRecipients = array())
    {
        global $_ARRAYLANG;

        $formId = isset($_REQUEST['formId']) ? intval($_REQUEST['formId']) : 0;
        $arrActiveSystemFrontendLanguages = FWLanguage::getActiveFrontendLanguages();
        $counter = 0;

        if (!$formId) {
            $selectedInterfaceLanguage = FRONTEND_LANG_ID;
        } elseif (isset($this->arrForms[$formId]['lang'][FRONTEND_LANG_ID])) {
            $selectedInterfaceLanguage = FRONTEND_LANG_ID;
        } elseif (isset($this->arrForms[$formId]['lang'][FWLanguage::getDefaultLangId()])) {
            $selectedInterfaceLanguage = FWLanguage::getDefaultLangId();
        } elseif (count($this->arrForms[$formId]['lang'])) {
            $selectedInterfaceLanguage = key($this->arrForms[$formId]['lang']);
        }

        foreach ($arrRecipients as $arrRecipient) {
            foreach ($arrActiveSystemFrontendLanguages as $langId => $lang) {
                $isSelectedInterfaceLanguage = $langId == $selectedInterfaceLanguage;

                $this->_objTpl->setVariable(array(
                    'CONTACT_FORM_RECIPIENT_LANG_ID'    => $langId,
                    'RECIPIENT_NAME_DISPLAY'            => $isSelectedInterfaceLanguage ? 'block' : 'none',
                    'CONTACT_FORM_RECIPIENT_NAME'       => contrexx_raw2xhtml($arrRecipient['lang'][$langId])
                ));
                $this->_objTpl->parse('recipient_name');
            }

            $this->_objTpl->setVariable(array(
                'ROW_CLASS_NAME'                => 'row'.(($counter++%2 == 0)?'1':'2'),
                'CONTACT_FORM_RECIPIENT_ID'     => $arrRecipient['id'],
                'CONTACT_FROM_RECIPIENT_EMAIL'  => contrexx_raw2xhtml($arrRecipient['email']),
                //'CONTACT_FORM_RECIPIENT_NAME'   => $arrRecipient['lang'][FRONTEND_LANG_ID], // take the active frontend language
                'CONTACT_FORM_RECIPIENT_TYPE'   => $arrRecipient['editType']
            ));
            $this->_objTpl->parse('contact_form_recipient_list');
        }
    }

    /**
     * update recipient list
     *
     * @param integer $formId
     * @param boolean $refresh
     * @return array
     */
    public function setRecipients($arrRecipients)
    {
        global $objDatabase;

        $objDatabase->Execute("
            DELETE FROM `".DBPREFIX."module_contact_recipient`
            WHERE `id_form` = ". intval($_REQUEST['formId'])
        );

        foreach ($arrRecipients as $id => $arrRecipient) {
            // this is a bit radical, but it works.
            $objDatabase->Execute("
                INSERT INTO `".DBPREFIX."module_contact_recipient`
                SET `id`  = $id,
                `id_form` = ".$arrRecipient['id_form'].",
                `name`      = '".$arrRecipient['name']."',
                `email`      = '".$arrRecipient['email']."',
                `sort`      = ".$arrRecipient['sort']);
        }
    }

    /**
     * Modify Form
     *
     * Shows the modifying page.
     * @access private
     * @param bool $copy If the form should be copied or not
     */
    function _modifyForm($copy = false)
    {
        global $_ARRAYLANG, $_CONFIG, $objDatabase;

        JS::activate('cx');

        if ($copy) {
            $this->initContactForms();
        }

        $this->_objTpl->loadTemplateFile('module_contact_form_modify.html');
        $formId = isset($_REQUEST['formId']) ? intval($_REQUEST['formId']) : 0;

        $this->_pageTitle = (!$copy && $formId != 0)    ? $_ARRAYLANG['TXT_CONTACT_MODIFY_CONTACT_FORM']
                                                        : $_ARRAYLANG['TXT_CONTACT_ADD_NEW_CONTACT_FORM'];

        $actionTitle    = $_ARRAYLANG['TXT_CONTACT_ADD_NEW_CONTACT_FORM'];
        $showForm       = 0;
        $useCaptcha     = 1;
        $useCustomStyle = 0;
        $sendCopy       = 0;
        $sendHtmlMail   = 1;
        $sendAttachment = 0;
        $emails         = '';

        $arrActiveSystemFrontendLanguages = FWLanguage::getActiveFrontendLanguages();

        if (isset($this->arrForms[$formId])) {
            // editing
            $actionTitle = $_ARRAYLANG['TXT_CONTACT_MODIFY_CONTACT_FORM'];
            $showForm       = $this->arrForms[$formId]['showForm'];
            $useCaptcha     = $this->arrForms[$formId]['useCaptcha'];
            $useCustomStyle = $this->arrForms[$formId]['useCustomStyle'];
            $sendCopy       = $this->arrForms[$formId]['sendCopy'];
            $sendHtmlMail   = $this->arrForms[$formId]['htmlMail'];
            $sendAttachment = $this->arrForms[$formId]['sendAttachment'];
            $emails         = $this->arrForms[$formId]['emails'];
        }

        if (count($arrActiveSystemFrontendLanguages) > 0) {
            $intLanguageCounter = 0;
            $boolFirstLanguage  = true;
            $arrLanguages       = array(0 => '', 1 => '', 2 => '');
            $strJsTabToDiv      = '';

            foreach($arrActiveSystemFrontendLanguages as $langId => $arrLanguage) {
                if ($formId) {
                    $boolLanguageIsActive = isset($this->arrForms[$formId]['lang'][$langId]) && $this->arrForms[$formId]['lang'][$langId]['is_active'];
                } else {
                    $boolLanguageIsActive = $langId == FRONTEND_LANG_ID;
                }

                $arrLanguages[$intLanguageCounter%3] .= '<input id="languagebar_'.$langId.'" '.(($boolLanguageIsActive) ? 'checked="checked"' : '').' type="checkbox" name="contactFormLanguages['.$langId.']" value="1" onclick="switchBoxAndTab(this, \'addFrom_'.$langId.'\');" /><label for="languagebar_'.$langId.'">'.contrexx_raw2xhtml($arrLanguage['name']).' ['.$arrLanguage['lang'].']</label><br />';
                $strJsTabToDiv .= 'arrTabToDiv["addFrom_'.$langId.'"] = "langTab_'.$langId.'";'."\n";
                ++$intLanguageCounter;
            }

            $this->_objTpl->setVariable(array(
                'TXT_CONTACT_LANGUAGE'      => $_ARRAYLANG['TXT_CONTACT_LANGUAGE'],
                'EDIT_LANGUAGES_1'          => $arrLanguages[0],
                'EDIT_LANGUAGES_2'          => $arrLanguages[1],
                'EDIT_LANGUAGES_3'          => $arrLanguages[2],
                'EDIT_JS_TAB_TO_DIV'        => $strJsTabToDiv
            ));
        }

// TODO: this might be a bug. Shouldn't this be the MAX(of used IDs) when modifying a form
        $lastFieldId = 0;
        if (empty($_POST['saveForm'])) {
            // get the saved fields
            $fields = $this->getFormFields($formId);
            $recipients = $this->getRecipients($formId);
        } else {
            $fields = $this->_getFormFieldsFromPost();
            $recipients = $this->getRecipientsFromPost();
        }

        // make an empty one so at least one is parsed
        if (empty($fields)) {
            foreach ($arrActiveSystemFrontendLanguages as $lang) {
                $fields[0] = array (
                    'type'          => 'text',
                    'order_id'      => 0,
                    'is_required'   => false,
                    'check_type'    => 1,
                    'editType'      => 'new'
                );
                $fields[0]['lang'][$lang['id']] = array(
                    'name'          => '',
                    'value'         => ''
                );
            }
        }

        if (!$formId) {
            $selectedInterfaceLanguage = FRONTEND_LANG_ID;
        } elseif (isset($this->arrForms[$formId]['lang'][FRONTEND_LANG_ID])) {
            $selectedInterfaceLanguage = FRONTEND_LANG_ID;
        } elseif (isset($this->arrForms[$formId]['lang'][FWLanguage::getDefaultLangId()])) {
            $selectedInterfaceLanguage = FWLanguage::getDefaultLangId();
        } elseif (count($this->arrForms[$formId]['lang'])) {
            $selectedInterfaceLanguage = key($this->arrForms[$formId]['lang']);
        }

        foreach ($arrActiveSystemFrontendLanguages as $langId => $lang) {
            $isSelectedInterfaceLanguage = $langId == $selectedInterfaceLanguage;
            $langVars = array(
                'is_active'     => $isSelectedInterfaceLanguage,
                'name'          => '',
                'text'          => '',
                'feedback'      => '',
                'subject'       => '',
                'mailTemplate'  => self::formMailTemplate
            );
            
            if (isset($this->arrForms[$formId]['lang'][$langId])) {
                $langVars = $this->arrForms[$formId]['lang'][$langId];
                $langVars['mailTemplate'] = preg_replace('/\{([A-Z0-9_]*?)\}/', '[[\\1]]', $langVars['mailTemplate']);
            }
            
            $this->_objTpl->setVariable(array(
                'LANG_ID'                   => $langId,
                'LANG_NAME'                 => contrexx_raw2xhtml($lang['name']),
                'TAB_CLASS_NAME'            => $isSelectedInterfaceLanguage ? 'active' :'inactive',
                'CONTACT_LANGTAB_DISPLAY'   => $langVars['is_active'] ? 'display:inline;' : 'display:none;'
            ));
            $this->_objTpl->parse('languageTabs');
             
            $this->_objTpl->setVariable(array(
                'LANG_ID'                                       => $lang['id'],
                'LANG_NAME'                                     => contrexx_raw2xhtml($lang['name']),
                'LANG_FORM_DISPLAY'                             => $isSelectedInterfaceLanguage ? 'block' : 'none',
                'CONTACT_FORM_MAIL_TEMPLATE_HIDDEN'             => contrexx_raw2xhtml($langVars['mailTemplate']),
                'CONTACT_FORM_SUBJECT'                          => contrexx_raw2xhtml($langVars['subject']),
            ));
            $this->_objTpl->parse('notificationLanguageForm');

            $this->_objTpl->setVariable(array(
                'CONTACT_FORM_ID'                               => $formId,
                'LANG_ID'                                       => $lang['id'],
                'LANG_FORM_DISPLAY'                             => $isSelectedInterfaceLanguage ? 'block' : 'none',

                'CONTACT_FORM_MAIL_TEMPLATE_HIDDEN'             => contrexx_raw2xhtml($langVars['mailTemplate']),
                'CONTACT_FORM_SUBJECT'                          => contrexx_raw2xhtml($langVars['subject']),

                'CONTACT_FORM_NAME'                             => contrexx_raw2xhtml($langVars['name']),//$this->arrForms[$formId]['lang'][$lang['id']]['name'],
                'CONTACT_FORM_FIELD_NEXT_ID'                    => $lastFieldId+1,
                'CONTACT_FORM_TEXT_HIDDEN'                      => contrexx_raw2xhtml($langVars['text']),
                'CONTACT_FORM_FEEDBACK_HIDDEN'                  => contrexx_raw2xhtml($langVars['feedback']),
                'CONTACT_FORM_RECIPIENT_NEXT_SORT'              => $this->getHighestSortValue($formId)+2,
                'CONTACT_FORM_RECIPIENT_NEXT_ID'                => $this->getLastRecipientId(true)+2,
                'CONTACT_FORM_FIELD_NEXT_TEXT_TPL'              => $this->_getFormFieldAttribute($lastFieldId+1, 'text', '', $isSelectedInterfaceLanguage, $lang['id']),
                'CONTACT_FORM_FIELD_LABEL_NEXT_TPL'             => $this->_getFormFieldAttribute($lastFieldId+1, 'label', '', $isSelectedInterfaceLanguage, $lang['id']),
                'CONTACT_FORM_FIELD_CHECK_MENU_NEXT_TPL'        => $this->_getFormFieldCheckTypesMenu('contactFormFieldCheckType['.($lastFieldId+1).']', 'contactFormFieldCheckType_'.($lastFieldId+1), 'text', 1),
                'CONTACT_FORM_FIELD_CHECK_MENU_TPL'             => $this->_getFormFieldCheckTypesMenu('contactFormFieldCheckType[0]', 'contactFormFieldCheckType_0', 'text', 1),
                'CONTACT_FORM_FIELD_CHECK_BOX_NEXT_TPL'         => $this->_getFormFieldRequiredCheckBox('contactFormFieldRequired['.($lastFieldId+1).']', 'contactFormFieldRequired_'.($lastFieldId+1), 'text', false),
                'CONTACT_FORM_FIELD_CHECK_BOX_TPL'              => $this->_getFormFieldRequiredCheckBox('contactFormFieldRequired[0]', 'contactFormFieldRequired_0', 'text', false),
                'CONTACT_ACTION_TITLE'                          => $actionTitle,                    
                'CONTACT_FORM_FIELD_TEXT_TPL'                   => $this->_getFormFieldAttribute(0, 'text', '', false),
                'CONTACT_FORM_FIELD_LABEL_TPL'                  => $this->_getFormFieldAttribute(0, 'label', '', false),
                'CONTACT_FORM_FIELD_CHECKBOX_TPL'               => $this->_getFormFieldAttribute(0, 'checkbox', 0),
                'CONTACT_FORM_FIELD_COUNTRY_TPL'                => $this->_getFormFieldAttribute(0, 'country','',true, 0),
                'CONTACT_FORM_FIELD_ACCESS_COUNTRY_TPL'         => $this->_getFormFieldAttribute(0, 'access_country','',true, 0),
                'CONTACT_FORM_FIELD_CHECKBOX_GROUP_TPL'         => $this->_getFormFieldAttribute(0, 'checkboxGroup', '', false),
                'CONTACT_FORM_FIELD_DATE_TPL'                   => $this->_getFormFieldAttribute(0, 'date', '', false),
                'CONTACT_FORM_FIELD_HIDDEN_TPL'                 => $this->_getFormFieldAttribute(0, 'hidden', '', false),
                'CONTACT_FORM_FIELD_RADIO_TPL'                  => $this->_getFormFieldAttribute(0, 'radio', '', false),
                'CONTACT_FORM_FIELD_SELECT_TPL'                 => $this->_getFormFieldAttribute(0, 'select', '', false)
            ));
            $this->_objTpl->parse('languageForm');
        }

        $this->_objTpl->setVariable('CONTACT_ACTIVE_LANG_NAME', contrexx_raw2xhtml($arrActiveSystemFrontendLanguages[$selectedInterfaceLanguage]['name']));

        $counter = 1;
        foreach ($fields as $fieldID => $field) {
            $realFieldID = ($formId > 0) ? $fieldID : $counter;
            $fieldType   = ($field['type'] == 'special') ? $field['special_type'] : $field['type'];
            $first       = true;

            /**
             While copying a template, the edittype of the field must be 'new'
             */
            if ($copy) {
                $field['editType'] = 'new';
            }

            foreach ($arrActiveSystemFrontendLanguages as $lang) {
                if ($formId) {
                    $isActive = isset($this->arrForms[$formId]['lang'][$lang['id']]) && $this->arrForms[$formId]['lang'][$lang['id']]['is_active'];
                } else {
                    // when creating a new form, the form shall be created for the currently selected frontend language
                    $isActive = $lang['id'] == FRONTEND_LANG_ID;
                }
                $show     = ($first && $isActive);
                
                $this->_objTpl->setVariable(array(
                    'LANG_ID'                   => $lang['id'],
                    'LANG_NAME_DISPLAY'         => $show ? 'block' : 'none',
                    'LANG_VALUE_DISPLAY'        => $show ? 'block' : 'none',
                    'FORM_FIELD_NAME'           => isset($field['lang'][$lang['id']]) ? contrexx_raw2xhtml($field['lang'][$lang['id']]['name']) : '',
                    'CONTACT_FORM_FIELD_VALUE'  => $this->_getFormFieldAttribute($realFieldID,
                                                                                 $fieldType,
                                                                                 isset($field['lang'][$lang['id']]) ? contrexx_raw2xhtml($field['lang'][$lang['id']]['value']) : '',
                                                                                 $show,
                                                                                 $lang['id'])
                ));
                $this->_objTpl->parse('formFieldName');
                $this->_objTpl->parse('formFieldValue');

                if ($isActive) {
                    $first = false;
                }
            }
            
            $this->_objTpl->setVariable(array(
                'CONTACT_FORM_FIELD_TYPE_MENU'  => $this->_getFormFieldTypesMenu('contactFormFieldType['.$realFieldID.']',
                                                                                 $fieldType,
                                                                                 'id="contactFormFieldType_'.$realFieldID.'" style="width:110px;" '.
                                                                                 'class="contactFormFieldType" onchange="setFormFieldAttributeBox(this.getAttribute(\'id\'),this.value)"'),
                'FORM_FIELD_CHECK_BOX'          => $this->_getFormFieldRequiredCheckBox('contactFormFieldRequired['.$realFieldID.']',
                                                                                        'contactFormFieldRequired_'.$realFieldID,
                                                                                        $fieldType,
                                                                                        $field['is_required']),
                'FORM_FIELD_CHECK_MENU'         => $this->_getFormFieldCheckTypesMenu('contactFormFieldCheckType['.$realFieldID.']',
                                                                                      'contactFormFieldCheckType_'.$realFieldID,
                                                                                      $fieldType,
                                                                                      $field['check_type']),
                'FORM_FIELD_ID'                 => $realFieldID,
                'FORM_FIELD_TYPE'               => $field['editType'],
                'ROW_CLASS_NAME'                => 'row'.(($counter%2 == 0)?'1':'2')
            ));
            $counter++;
            $this->_objTpl->parse('formField');
        }

        if (!$copy && $formId > 0) {
            $jsSubmitFunction = "updateContentSite()";
        } else {
            $jsSubmitFunction = "createContentSite()";
        }

        $this->_objTpl->setVariable(array(
            'CONTACT_FORM_SHOW_FORM_YES'                    => $showForm        ? 'checked="checked"' : '',
            'CONTACT_FORM_SHOW_FORM_NO'                     => $showForm        ? '' : 'checked="checked"',
            'CONTACT_FORM_USE_CAPTCHA_YES'                  => $useCaptcha      ? 'checked="checked"' : '',
            'CONTACT_FORM_USE_CAPTCHA_NO'                   => $useCaptcha      ? '' : 'checked="checked"',
            'CONTACT_FORM_USE_CUSTOM_STYLE_YES'             => $useCustomStyle  ? 'checked="checked"' : '',
            'CONTACT_FORM_USE_CUSTOM_STYLE_NO'              => $useCustomStyle  ? '' : 'checked="checked"',
            'CONTACT_FORM_SEND_HTML_MAIL'                   => $sendHtmlMail    ? 'checked="checked"' : '',
            'CONTACT_MAIL_TEMPLATE_STYLE'                   => $sendHtmlMail    ? 'table-row' : 'none',
            'CONTACT_FORM_SEND_COPY_YES'                    => $sendCopy        ? 'checked="checked"' : '',
            'CONTACT_FORM_SEND_COPY_NO'                     => $sendCopy        ? '' : 'checked="checked"',
            'CONTACT_FORM_SEND_ATTACHMENT'                  => $sendAttachment  ? 'checked="checked"' : '',
            'CONTACT_FORM_EMAIL'                            => contrexx_raw2xhtml($emails),
            'CONTACT_JS_SUBMIT_FUNCTION'                    => $jsSubmitFunction,
            'FORM_COPY'                                     => intval($copy),
            'CONTACT_FORM_TEXT'                             => get_wysiwyg_editor('contactFormTextEditor', '', 'shop'),
            'CONTACT_FORM_FEEDBACK'                         => get_wysiwyg_editor('contactFormFeedbackEditor', '', 'shop'),
            'CONTACT_MAIL_TEMPLATE'                         => get_wysiwyg_editor('contactMailTemplateEditor', '', 'shop'),

            'TXT_CONTACT_FORM_FIELDS'                       => $_ARRAYLANG['TXT_CONTACT_FORM_FIELDS'],
            'TXT_CONTACT_DELETE'                            => $_ARRAYLANG['TXT_CONTACT_DELETE'],
            'TXT_CONTACT_MOVE_UP'                           => $_ARRAYLANG['TXT_CONTACT_MOVE_UP'],
            'TXT_CONTACT_MOVE_DOWN'                         => $_ARRAYLANG['TXT_CONTACT_MOVE_DOWN'],
            'TXT_CONTACT_NAME'                              => $_ARRAYLANG['TXT_CONTACT_NAME'],
            'TXT_CONTACT_REGEX_EMAIL'                       => $_ARRAYLANG['TXT_CONTACT_REGEX_EMAIL'],
            'TXT_CONTACT_ADD_OTHER_FIELD'                   => $_ARRAYLANG['TXT_CONTACT_ADD_OTHER_FIELD'],
            'TXT_CONTACT_ADD_RECIPIENT'                     => $_ARRAYLANG['TXT_CONTACT_ADD_RECIPIENT'],
            'TXT_CONTACT_FORM_VALUES'                       => $_ARRAYLANG['TXT_CONTACT_FORM_VALUES'],
            'TXT_FORM_FIELDS'                               => $_ARRAYLANG['TXT_FORM_FIELDS'],
            'TXT_FORM_RECIPIENTS'                           => $_ARRAYLANG['TXT_FORM_RECIPIENTS'],
            'TXT_ADVANCED_SETTINGS'                         => $_ARRAYLANG['TXT_ADVANCED_SETTINGS'],
            'TXT_CONTACT_FORM_NOTIFICATION'                 => $_ARRAYLANG['TXT_CONTACT_FORM_NOTIFICATION'],
            'TXT_CONTACT_ID'                                => $_ARRAYLANG['TXT_CONTACT_ID'],
            'TXT_CONTACT_NAME'                              => $_ARRAYLANG['TXT_CONTACT_NAME'],
            'TXT_CONTACT_RECEIVER_ADDRESSES'                => $_ARRAYLANG['TXT_CONTACT_RECEIVER_ADDRESSES'],
            'TXT_CONTACT_RECEIVER_ADDRESSES_SELECTION'      => $_ARRAYLANG['TXT_CONTACT_RECEIVER_ADDRESSES_SELECTION'],
            'TXT_CONTACT_SAVE'                              => $_ARRAYLANG['TXT_CONTACT_SAVE'],
            'TXT_CONTACT_SEPARATE_MULTIPLE_VALUES_BY_COMMA' => $_ARRAYLANG['TXT_CONTACT_SEPARATE_MULTIPLE_VALUES_BY_COMMA'],
            'TXT_CONTACT_SEND_ATTACHMENT_DESCRIPTION'       => $_ARRAYLANG['TXT_CONTACT_SEND_ATTACHMENT_DESCRIPTION'],
            'TXT_CONTACT_FORM_DESC'                         => $_ARRAYLANG['TXT_CONTACT_FORM_DESC'],
            'TXT_CONTACT_FEEDBACK'                          => $_ARRAYLANG['TXT_CONTACT_FEEDBACK'],
            'TXT_CONTACT_VALUE_S'                           => $_ARRAYLANG['TXT_CONTACT_VALUE_S'],
            'TXT_CONTACT_FIELD_NAME'                        => $_ARRAYLANG['TXT_CONTACT_FIELD_NAME'],
            'TXT_CONTACT_TYPE'                              => $_ARRAYLANG['TXT_CONTACT_TYPE'],
            'TXT_CONTACT_MANDATORY_FIELD'                   => $_ARRAYLANG['TXT_CONTACT_MANDATORY_FIELD'],
            'TXT_CONTACT_FEEDBACK_EXPLANATION'              => $_ARRAYLANG['TXT_CONTACT_FEEDBACK_EXPLANATION'],
            'TXT_CONTACT_CONFIRM_CREATE_CONTENT_SITE'       => $_ARRAYLANG['TXT_CONTACT_CONFIRM_CREATE_CONTENT_SITE'],
            'TXT_CONTACT_CONFIRM_UPDATE_CONTENT_SITE'       => $_ARRAYLANG['TXT_CONTACT_CONFIRM_UPDATE_CONTENT_SITE'],
            'TXT_CONTACT_SHOW_FORM_AFTER_SUBMIT'            => $_ARRAYLANG['TXT_CONTACT_SHOW_FORM_AFTER_SUBMIT'],
            'TXT_CONTACT_YES'                               => $_ARRAYLANG['TXT_CONTACT_YES'],
            'TXT_CONTACT_NO'                                => $_ARRAYLANG['TXT_CONTACT_NO'],
            'TXT_CONTACT_CAPTCHA_PROTECTION'                => $_ARRAYLANG['TXT_CONTACT_CAPTCHA_PROTECTION'],
            'TXT_CONTACT_CAPTCHA'                           => $_ARRAYLANG['TXT_CONTACT_CAPTCHA'],
            'TXT_CONTACT_CAPTCHA_DESCRIPTION'               => $_ARRAYLANG['TXT_CONTACT_CAPTCHA_DESCRIPTION'],
            'TXT_CONTACT_SEND_COPY_DESCRIPTION'             => $_ARRAYLANG['TXT_CONTACT_SEND_COPY_DESCRIPTION'],
            'TXT_CONTACT_SEND_COPY'                         => $_ARRAYLANG['TXT_CONTACT_SEND_COPY'],
            'TXT_CONTACT_SEND_ATTACHMENT'                   => $_ARRAYLANG['TXT_CONTACT_SEND_ATTACHMENT'],
            'TXT_CONTACT_SEND_HTML_MAIL'                    => $_ARRAYLANG['TXT_CONTACT_SEND_HTML_MAIL'],
            'TXT_CONTACT_CUSTOM_STYLE_DESCRIPTION'          => $_ARRAYLANG['TXT_CONTACT_CUSTOM_STYLE_DESCRIPTION'],
            'TXT_CONTACT_CUSTOM_STYLE'                      => $_ARRAYLANG['TXT_CONTACT_CUSTOM_STYLE'],
            'TXT_CONTACT_SET_MANDATORY_FIELD'               => $_ARRAYLANG['TXT_CONTACT_SET_MANDATORY_FIELD'],
            'TXT_CONTACT_RECIPIENT_ALREADY_SET'             => $_ARRAYLANG['TXT_CONTACT_RECIPIENT_ALREADY_SET'],
            'TXT_CONTACT_EMAIL'                             => $_ARRAYLANG['TXT_CONTACT_EMAIL'],
            'TXT_CONTACT_NAME'                              => $_ARRAYLANG['TXT_CONTACT_NAME'],
            'TXT_CONTACT_SUBJECT'                           => $_ARRAYLANG['TXT_CONTACT_SUBJECT'],
            'TXT_CONTACT_MAIL_TEMPLATE'                     => $_ARRAYLANG['TXT_CONTACT_MAIL_TEMPLATE'],
            'TXT_NAME'                                      => $_ARRAYLANG['TXT_CONTACT_FORM_NAME'],
            'TXT_VALUES'                                    => $_ARRAYLANG['TXT_CONTACT_FORM_VALUES'],
            'TXT_TYPE'                                      => $_ARRAYLANG['TXT_CONTACT_TYPE'],
            'TXT_MANDATORY_FIELD'                           => $_ARRAYLANG['TXT_CONTACT_MANDATORY_FIELD'],
            'TXT_CONTACT_VALIDATION'                        => $_ARRAYLANG['TXT_CONTACT_VALIDATION'],
            'TXT_ADVANCED_VIEW'                             => $_ARRAYLANG['TXT_ADVANCED_VIEW'],
            'TXT_SIMPLIFIED_VIEW'                           => $_ARRAYLANG['TXT_SIMPLIFIED_VIEW'],
            'CONTACT_FORM_FIELDS_TITLE'                     => $_ARRAYLANG['TXT_CONTACT_FORM_FIELD_TITLE'],
            'CONTACT_FORM_RECIPIENTS_TITLE'                 => $_ARRAYLANG['CONTACT_FORM_RECIPIENTS_TITLE'],
            'CONTACT_FORM_SETTINGS'                         => $_ARRAYLANG['CONTACT_FORM_SETTINGS'],
        ));

        if (empty($recipients)) {
            // make an empty one so there's at least one
            $recipients[0] = array(
                    'id'    => 1,
                    'email' => '',
                    'editType' => 'new'
            );

            foreach ($arrActiveSystemFrontendLanguages as $langID => $lang) {
                $recipients[0]['lang'][$langID] = '';
            }
        }

        foreach ($recipients as $recipientID => $recipientField) {
            if ($copy) {
                $recipients[$recipientID]['editType'] = 'new';
            }
        }

        // parse the recipients
        $this->_showRecipients($recipients);
    }

    // added langid as new parameter to support multi-lang
    function _getFormFieldAttribute($id, $type, $attr, $show=true, $langid = 0)
    {
        global $_ARRAYLANG, $objDatabase;
        $field   = "";
        $display = $show ? "block" : "none";
        
        switch ($type) {
        case 'text':
        case 'hidden':
        case 'label':
        case 'special':
            $field .= "<div style=\"display: ".$display.";\"  id=\"fieldValueTab_".$id."_".$langid."\" class=\"fieldValueTabs_".$id."\">";
            $field .= "<input style=\"width:308px;background: #FFFFFF;\" type=\"text\" name=\"contactFormFieldValue[".$id."][".$langid."]\" value=\"".$attr."\" />\n";
            $field .= "</div>";
            return $field;
            break;

        case 'checkbox':
            /* Only one instance of checkbox is allowed for any number of active language */
            if ($show) {
                return "<select style=\"width:331px;\" name=\"contactFormFieldValue[".$id."]\">\n
                                    <option value=\"0\"".($attr == 0 ? ' selected="selected"' : '').">".$_ARRAYLANG['TXT_CONTACT_NOT_SELECTED']."</option>\n
                                    <option value=\"1\"".($attr == 1 ? ' selected="selected"' : '').">".$_ARRAYLANG['TXT_CONTACT_SELECTED']."</option>\n
                                </select>";
            }
            break;
        case 'country':
        case 'access_country':
            /* Only one instance of country select is allowed for any number of active language */
            if ($show) {
                $objResult = $objDatabase->Execute("SELECT `name` FROM ".DBPREFIX."lib_country");
                $field ="<select style=\"width:331px;\" name=\"contactFormFieldValue[".$id."]\">\n";
                $field .= "<option value=\"".$_ARRAYLANG['TXT_CONTACT_PLEASE_SELECT']."\" >".$_ARRAYLANG['TXT_CONTACT_PLEASE_SELECT']."</option>\n";
                while (!$objResult->EOF) {
                    $field .= "<option value=\"".$objResult->fields['name']."\" ".(($attr == $objResult->fields['name'])?'selected="selected"':'')." >".$objResult->fields['name']."</option>\n";
                    $objResult->MoveNext();
                }
                $field .= "</select>";
                return $field;
            }
            break;
        case 'checkboxGroup':
        case 'select':
        case 'radio':
            $field .= "<div style=\"display: ".$display.";\"  id=\"fieldValueTab_".$id."_".$langid."\" class=\"fieldValueTabs_".$id."\">";
            $field .= "<input style=\"width:308px;background: #FFFFFF;\" type=\"text\" name=\"contactFormFieldValue[".$id."][".$langid."]\" value=\"".$attr."\" /> &nbsp;<img src=\"images/icons/note.gif\" width=\"12\" height=\"12\" onmouseout=\"htm()\" onmouseover=\"stm(Text[4],Style[0])\" />\n";
            $field .= "</div>";
            return $field;
            break;

        default:
            return '';
            break;
        }
    }

    /**
     * Save Form
     *
     * Saves the form data
     *
     * @access private
     */
    function _saveForm()
    {
        global $_ARRAYLANG, $_CONFIG, $objDatabase;
        
        $formId  = isset($_REQUEST['formId']) ? intval($_REQUEST['formId']) : 0;
        $adding  = $_POST['copy'] || !$formId;
        $content = $_POST['contentSiteAction'];

        if (isset($_POST['saveForm'])) {
            $emails         = $this->getPostRecipients();
            $showForm       = !empty($_POST['contactFormShowForm']) ? 1 : 0;
            $useCaptcha     = !empty($_POST['contactFormUseCaptcha']) ? 1 : 0;
            $useCustomStyle = !empty($_POST['contactFormUseCustomStyle']) ? 1 : 0;
            $sendCopy       = !empty($_POST['contactFormSendCopy']) ? 1 : 0;
            $sendHtmlMail   = !empty($_POST['contactFormHtmlMail']) ? 1 : 0;
            $sendAttachment = !empty($_POST['contactFormSendAttachment']) ? 1 : 0;
            
            if (!$adding) {
                // This updates the database
                $this->updateForm(
                        $formId,
                        $emails,
                        $showForm,
                        $useCaptcha,
                        $useCustomStyle,
                        $sendCopy,
                        $sendHtmlMail,
                        $sendAttachment
                );
            } else {
                $formId = $this->addForm(
                        $emails,
                        $showForm,
                        $useCaptcha,
                        $useCustomStyle,
                        $sendCopy,
                        $sendHtmlMail,
                        $sendAttachment
                );
            }

            foreach (FWLanguage::getActiveFrontendLanguages() as $lang) {
                $langID = $lang['id'];

                $formName =
                        isset($_POST['contactFormName'][$langID])
                        ? contrexx_input2raw($_POST['contactFormName'][$langID])
                        : '';

                $formSubject =
                        isset($_POST['contactFormSubject'][$langID])
                        ? contrexx_input2raw($_POST['contactFormSubject'][$langID])
                        : '';

                $isActive =
                        isset($_POST['contactFormLanguages'][$langID])
                        ? 1 : 0;

                $formText =
                        isset($_POST['contactFormText'][$langID])
                        ? contrexx_input2raw(html_entity_decode($_POST['contactFormText'][$langID], ENT_QUOTES, CONTREXX_CHARSET))
                        : '';

                $formFeedback =
                        isset($_POST['contactFormFeedback'][$langID])
                        ? contrexx_input2raw(html_entity_decode($_POST['contactFormFeedback'][$langID], ENT_QUOTES, CONTREXX_CHARSET))
                        : '';

                $formMailTemplate =
                        isset($_POST['contactMailTemplate'][$langID])
                        ? preg_replace('/\[\[([A-Z0-9_]*?)\]\]/', '{\\1}', contrexx_input2raw(html_entity_decode($_POST['contactMailTemplate'][$langID], ENT_QUOTES, CONTREXX_CHARSET)))
                        :'';

                $this->insertFormLangValues(
                        $formId,
                        $langID,
                        $isActive,
                        $formName,
                        $formText,
                        $formFeedback,
                        $formMailTemplate,
                        $formSubject
                );
            }
            
            // do the fields
            $fields = $this->_getFormFieldsFromPost();
            $fileFieldFound = false;

            $formFieldIDs = array();
            foreach ($fields as $field) {
                if($arrField['type'] == 'file') {
                    if(!$fileFieldFound) { //first time running into a file field
                        $fileFieldFound = true;
                    }
                    else { //multiple file fields in this form - we do not want this
                        $this->_statusMessageErr .= $_ARRAYLANG['TXT_CONTACT_FORM_MULTIPLE_UPLOAD_FIELDS'];
                        $this->_modifyForm();
                        return;
                    }
                }

                if ($field['editType'] == 'new') {
                    $formFieldIDs[] = $this->addFormField($formId, $field);
                } else {
                    $this->updateFormField($field);
                    $formFieldIDs[] = $field['id'];
                }
            }

            if (!$adding) {
                $this->cleanFormFields($formId, $formFieldIDs);
            }

            $recipients = $this->getRecipientsFromPost();
            foreach ($recipients as $recipient) {
                if ($recipient['editType'] == 'new') {
                    $recipientIDs[] = $this->addRecipient($formId, $recipient);
                } else {
                    $this->updateRecipient($recipient);
                    $recipientIDs[] = $recipient['id'];
                }
            }

            if (!$adding) {
                $this->cleanRecipients($formId, $recipientIDs);
            }

        }

        //$this->_modifyForm();
        $this->initContactForms();

        /*
         * Update/Create Frontend Form
         */
        if ($content == 'create') {
            $this->_createContentPage();
        } else if ($content == 'update') {
            $this->_updateContentSite();
        }

        $this->_contactForms();
    }

    /**
     * Get the recipient addresses from the post
     *
     * @author      Comvation AG <info@comvation.com>
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @return      string
     */
    private function getPostRecipients()
    {
        global $_CONFIG;

        $formEmailsTmp = isset($_POST['contactFormEmail'])
                ? explode(
                ',',
                strip_tags(contrexx_stripslashes($_POST['contactFormEmail']))
                )
                : '';

        if (empty($formEmails)) {
            $formEmails = $_CONFIG['contactFormEmail'];
        }
        if (is_array($formEmailsTmp)) {
            $formEmails = array();
            foreach ($formEmailsTmp as $email) {
                $email = trim(contrexx_strip_tags($email));
                if (!empty($email)) {
                    array_push($formEmails, $email);
                }
            }
            $formEmails = implode(',', $formEmails);
        } else {
            $formEmails = '';
        }

        return $formEmails;
    }


    function _deleteFormEntry()
    {
        global $_ARRAYLANG;

        if (isset($_GET['entryId'])) {
            $entryId = intval($_GET['entryId']);
            $this->deleteFormEntry($entryId);
        } elseif (isset($_POST['selectedEntries']) && count($_POST['selectedEntries']) > 0) {
            foreach ($_POST['selectedEntries'] as $entryId) {
                $this->deleteFormEntry(intval($entryId));
            }
        }
        $this->_statusMessageOk = $_ARRAYLANG['TXT_CONTACT_FORM_ENTRY_DELETED'];

        $this->initContactForms();
        $this->_contactFormEntries();
    }

    /**
     * Delete a form
     *
     * @author      Comvation AG <info@comvation.com>
     */
    private function _deleteForm()
    {
        global $_ARRAYLANG;

        if (isset($_GET['formId'])) {
            $formId = intval($_GET['formId']);

            if ($formId > 0) {
                if ($this->deleteForm($formId)) {
                    $this->_statusMessageOk = $_ARRAYLANG['TXT_CONTACT_CONTACT_FORM_SUCCESSFULLY_DELETED'];

                    if (isset($_GET['deleteContent']) && $_GET['deleteContent'] == 'true') {
                        $this->_deleteContentSite($formId);
                    }
                } else {
                    $this->_statusMessageErr = $_ARRAYLANG['TXT_CONTACT_FAILED_DELETE_CONTACT_FORM'];
                }
            }
        }
        $this->_contactForms();
    }

    /*
     * Delete Site content for all languages even though language is not active
     */
    function _deleteContentSite($formId)
    {
        global $objDatabase, $_ARRAYLANG;

        Permission::checkAccess(26, 'static');

        $formId = intval($_REQUEST['formId']);

        $pageRepo = $this->em->getRepository('\Cx\Model\ContentManager\Page');
        $pages = $pageRepo->findBy(array('module' => 'contact', 'cmd' => $formId));
        foreach($pages as $page) {
            $this->em->remove($page);
        }

        $this->em->flush();
    }

    /**
     * Get the form fields from the post variables
     *
     * This is only used when an error on saving occurs, to
     * reparse the form fields.
     */
    private function _getFormFieldsFromPost()
    {
        $arrFields = array();
        $orderId = 0;
        $types = array(
                'text',
                'label',
                'file',
                'textarea',
                'hidden',
                'radio',
                'checkboxGroup',
                'password',
                'select',
                'special'
        );
        
        // shorten the variables
        $fieldNames      = $_POST['contactFormFieldName'];
        $fieldValues     = $_POST['contactFormFieldValue'];
        $fieldTypes      = $_POST['contactFormFieldType'];
        $fieldRequireds  = $_POST['contactFormFieldRequired'];
        $fieldCheckTypes = $_POST['contactFormFieldCheckType'];
        $fieldEditType   = $_POST['contactFormFieldEditType'];

        foreach ($fieldTypes as $id => $fieldType) {
            $id = intval($id);
            $special_type = '';

            if (isset($this->_arrFormFieldTypes[$fieldType])) {
                $type = $fieldType;
            } elseif (isset($this->_arrUserAccountData[$fieldType])) {
                $type         = 'special';
                $special_type = $fieldType;
            } else {
                $type = key($this->_arrFormFieldTypes);
            }

            $is_required = !empty($fieldRequireds[$id]);
            $checkType = !empty($fieldCheckTypes[$id]) ? intval($fieldCheckTypes[$id]) : 0;
            $editType = $fieldEditType[$id];

            $arrFields[$id] = array(
                'id'            => $id, // in case we're editing this should be the real id
                'type'          => $type,
                'special_type'  => $special_type,
                'order_id'      => $orderId,
                'is_required'   => $is_required,
                'check_type'    => $checkType,
                'editType'      => $editType
            );
            $orderId++;
            
            $arrActiveSystemFrontendLanguageIds = array_keys(FWLanguage::getActiveFrontendLanguages());
            foreach ($arrActiveSystemFrontendLanguageIds as $langId) {
                if (!empty($_POST['contactFormLanguages'][$langId])) {
                    $arrFields[$id]['lang'][$langId] = array(
                        'name'	=> contrexx_input2raw($fieldNames[$id][$langId]),
                        'value'	=>    $fieldType != 'checkbox'
                                   && $fieldType != 'country'
                                   && $fieldType != 'access_country'
                                    ? contrexx_input2raw($fieldValues[$id][$langId])
                                    : $fieldValue = $fieldValues[$id]);
                }
            }
        }

        return $arrFields;
    }

    /**
     * Parse the post values and return a list of recipients
     *
     * @author      Stefan Heinemann <sh@adfinis.com>
     * @return      array
     */
    private function getRecipientsFromPost()
    {
        $mails      = $_POST['contactFormRecipientEmail'];
        $names      = $_POST['contactFormRecipientName'];
        $editTypes  = $_POST['contactFormRecipientEditType'];
        $recipients = array();

        if (count($mails) == 0) {
            return $recipients;
        }

        $sortCounter = 0;
        foreach ($mails as $key => $mail) {
            $recipients[$key] = array(
                    'id'    => $key,
                    'email' => $mail,
                    'sort'  => $sortCounter++,
                    'editType' => $editTypes[$key]
            );
            foreach (FWLanguage::getActiveFrontendLanguages() as $langID => $lang) {
                $name = ($names[$key][$langID])
                        ? $names[$key][$langID]
                        : $names[$key][0]
                ;
                $recipients[$key]['lang'][$langID] = $name;
            }
        }

        return $recipients;
    }

    /**
     * Field Types Menu
     *
     * Generates a xhtml selection list with all the field types
     * @access private
     */
    function _getFormFieldTypesMenu($name, $selectedType, $attrs = '')
    {
        global $_ARRAYLANG;

        $menu = "<select name=\"".$name."\" ".$attrs.">\n";
        $menu .= "<option disabled=\"disabled\" style=\"color:#000;font-weight:bold;\">".$_ARRAYLANG['TXT_CONTACT_FIELDS']."</option>\n";
        foreach ($this->_arrFormFieldTypes as $type => $desc) {
            $menu .= "<option value=\"".$type."\"".($selectedType == $type ? 'selected="selected"' : '')."  style=\"padding-left:10px;\"><!--[if IE]>&nbsp;&nbsp;&nbsp;&nbsp;<![endif]-->".$desc."</option>\n";
        }
        $menu .= "<option disabled=\"disabled\" style=\"color:#000;font-weight:bold;\">".$_ARRAYLANG['TXT_CONTACT_USER_DATA']."</option>\n";
        foreach ($this->_arrUserAccountData as $type => $desc) {
            $menu .= "<option value=\"".$type."\"".($selectedType == $type ? 'selected="selected"' : '')."  style=\"padding-left:10px;\"><!--[if IE]>&nbsp;&nbsp;&nbsp;&nbsp;<![endif]-->".$desc."</option>\n";
        }
        $menu .= "</select>\n";
        return  $menu;
    }

    /**
     * Check Types Menu
     *
     * Generates a selection list with all possible types which can be checked
     * @access private
     * @param string $name Name of the selection list
     * @param array $list List with all of the possible types (email, url, text, numbers...)
     * @param int $selected Which option has to be selected
     */
    function _getFormFieldCheckTypesMenu($name, $id,  $type, $selected)
    {
        global $_ARRAYLANG;

        switch ($type) {
        case 'access_country':
        case 'checkbox':
        case 'checkboxGroup':
        case 'country':
        case 'date':
        case 'fieldset':
        case 'hidden':
        case 'radio':
        case 'select':
        case 'label':
        case 'recipient':
        case 'horizontalLine':
            $menu = '';
            break;

        case 'text':
        case 'file':
        case 'password':
        case 'textarea':
        default:
            $menu = "<select name=\"".$name."\" id=\"".$id."\">\n";
            foreach ($this->arrCheckTypes as $typeId => $type) {
                if ($selected == $typeId) {
                    $select = "selected=\"selected\"";
                } else {
                    $select = "";
                }

                $menu .= "<option value=\"".$typeId."\" $select>".$_ARRAYLANG[$type['name']]."</option>\n";
            }

            $menu .= "</select>\n";
            break;
        }
        return  $menu;
    }

    function _getFormFieldRequiredCheckBox($name, $id, $type, $selected)
    {
        global $_ARRAYLANG;

        switch ($type) {
        case 'hidden':
        case 'label':
        case 'recipient':
        case 'fieldset':
        case 'horizontalLine':
            return '';
            break;

        default:
            return '<input type="checkbox" name="'.$name.'" id="'.$id.'" '.($selected ? 'checked="checked"' : '').' />';
            break;
        }
    }

    /**
     * Source Code page
     *
     * Gets the page for showing the source code
     * @access public
     * @global array
     */
    function _sourceCode($formId = null)
    {
        global $_ARRAYLANG;

        if (!isset($formId)) {
            $formId = isset($_REQUEST['formId']) ? intval($_REQUEST['formId']) : 0;
        }

        if ($formId > 0 && isset($this->arrForms[$formId])) {
            if (isset($this->arrForms[$formId]['lang'][FRONTEND_LANG_ID])) {
                $selectedInterfaceLanguage = FRONTEND_LANG_ID;
            } elseif (isset($this->arrForms[$formId]['lang'][FWLanguage::getDefaultLangId()])) {
                $selectedInterfaceLanguage = FWLanguage::getDefaultLangId();
            } else {
                $selectedInterfaceLanguage = key($this->arrForms[$formId]['lang']);
            }

            $this->_objTpl->loadTemplateFile('module_contact_form_code.html');
            $this->_pageTitle = $_ARRAYLANG['TXT_CONTACT_SOURCECODE'];

            $this->_objTpl->setVariable(array(
                'TXT_CONTACT_SOURCECODE'            => $_ARRAYLANG['TXT_CONTACT_SOURCECODE'],
                'TXT_CONTACT_PREVIEW'               => $_ARRAYLANG['TXT_CONTACT_PREVIEW'],
                'TXT_CONTACT_COPY_SOURCECODE_MSG'   => $_ARRAYLANG['TXT_CONTACT_COPY_SOURCECODE_MSG'],
                'TXT_CONTACT_SELECT_ALL'            => $_ARRAYLANG['TXT_CONTACT_SELECT_ALL'],
                'TXT_CONTACT_BACK'                  => $_ARRAYLANG['TXT_CONTACT_BACK']
            ));

            $pageRepo = $this->em->getRepository('\Cx\Model\ContentManager\Page');
            $page = $pageRepo->findOneBy(array('module' => 'contact', 'cmd' => $formId, 'lang' => $selectedInterfaceLanguage));
            $contentSiteExists = $page !== null;

            $this->_objTpl->setVariable(array(
                'CONTACT_CONTENT_SITE_ACTION_TXT'   => $contentSiteExists > 0 ? $_ARRAYLANG['TXT_CONTACT_UPDATE_CONTENT_SITE'] : $_ARRAYLANG['TXT_CONTACT_NEW_PAGE'],
                'CONTACT_CONTENT_SITE_ACTION'       => $contentSiteExists > 0 ? 'updateContent' : 'newContent',
                'CONTACT_SOURCECODE_OF'             => str_replace('%NAME%', contrexx_raw2xhtml($this->arrForms[$formId]['lang'][$selectedInterfaceLanguage]['name']), $_ARRAYLANG['TXT_CONTACT_SOURCECODE_OF_NAME']),
                'CONTACT_PREVIEW_OF'                => str_replace('%NAME%', contrexx_raw2xhtml($this->arrForms[$formId]['lang'][$selectedInterfaceLanguage]['name']), $_ARRAYLANG['TXT_CONTACT_PREVIEW_OF_NAME']),
                'CONTACT_FORM_SOURCECODE'           => contrexx_raw2xhtml($this->_getSourceCode($formId, 0, false, true)),
                'CONTACT_FORM_PREVIEW'              => $this->_getSourceCode($formId, 0, true),
                'FORM_ID'                           => $formId
            ));
        } else {
            $this->_contactForms();
        }
    }

    /*
     * Generates the HTML Source code of the Submission form designed in backend
     * @id      Submission form id
     * @lang    Language for which source code to be generated
     * @preview Boolean, generated preview source or raw source
     * @show    Boolean, generated frontend code
     */
    function _getSourceCode($id, $lang, $preview = false, $show = false)
    {
        global $_ARRAYLANG, $objInit, $objDatabase;

        $hasFileInput = false; //remember if we added a file input -> this would need the uploader to be initialized

        $arrFields = $this->getFormFields($id);
        $sourcecode = array();
        $this->initContactForms();

// TODO: replace FRONTEND_LANG_ID with selectedInterfaceLanguage

        $sourcecode[] = "{CONTACT_FEEDBACK_TEXT}";
        $sourcecode[] = "<!-- BEGIN formText -->". ($preview ? $this->arrForms[$id]['lang'][FRONTEND_LANG_ID]['text'] : "{".$id."_FORM_TEXT}") ."<!-- END formText -->";
        $sourcecode[] = '<div id="contactFormError" style="color: red; display: none;">';
        $sourcecode[] = $preview ? $_ARRAYLANG['TXT_NEW_ENTRY_ERORR'] : '{TXT_NEW_ENTRY_ERORR}';
        $sourcecode[] = "</div>";
        $sourcecode[] = "<!-- BEGIN contact_form -->";
        $sourcecode[] = '<form action="'.($preview ? '../' : '')."index.php?section=contact&amp;cmd=".$id.'" ';
        $sourcecode[] = 'method="post" enctype="multipart/form-data" onsubmit="return checkAllFields();" id="contactForm'.(($this->arrForms[$id]['useCustomStyle'] > 0) ? '_'.$id : '').'" class="contactForm'.(($this->arrForms[$id]['useCustomStyle'] > 0) ? '_'.$id : '').'">';
        $sourcecode[] = '<fieldset id="contactFrame">';
        $sourcecode[] = "<legend>". ($preview ? $this->arrForms[$id]['lang'][FRONTEND_LANG_ID]['name'] : "{".$id."_FORM_NAME}")."</legend>";
       
        foreach ($arrFields as $fieldId => $arrField) {
            if ($arrField['is_required']) {
                $required = '<strong class="is_required">*</strong>';
            } else {
                $required = "";
            }

            switch ($arrField['type']) {
            case 'hidden':
            case 'horizontalLine':
                $sourcecode[] = '&nbsp;';
                break;
            case 'label':
                $sourcecode[] = '<label for="contactFormFieldId_'.$fieldId.'">&nbsp;</label>';
                break;
            case 'fieldset':
                $sourcecode[] = '</fieldset>';
                $sourcecode[] = '<fieldset id="contactFormFieldId_'.$fieldId.'">';
                $sourcecode[] = "<legend>".($preview ? contrexx_raw2xhtml($arrField['lang'][FRONTEND_LANG_ID]['name']) : "{".$fieldId."_LABEL}")."</legend>";
                break;
            case 'checkboxGroup':
            case 'radio':
                $sourcecode[] = '<label>'.
                                ($preview ? contrexx_raw2xhtml($arrField['lang'][FRONTEND_LANG_ID]['name']) : "{".$fieldId."_LABEL}")
                                .$required.'</label>';
                break;
            case 'date':
                $sourcecode[] = '<label for="DPC_date'.$fieldId.'_YYYY-MM-DD">'.
                                ($preview ? contrexx_raw2xhtml($arrField['lang'][FRONTEND_LANG_ID]['name']) : "{".$fieldId."_LABEL}")
                                .$required.'</label>';
                break;
            default:
                $sourcecode[] = '<label for="contactFormFieldId_'.$fieldId.'">'.
                                ($preview ? contrexx_raw2xhtml($arrField['lang'][FRONTEND_LANG_ID]['name']) : "{".$fieldId."_LABEL}")
                                .$required.'</label>';
            }

            $arrField['lang'][FRONTEND_LANG_ID]['value'] = preg_replace('/\[\[([A-Z0-9_]+)\]\]/', '{$1}', $arrField['lang'][FRONTEND_LANG_ID]['value']);
            $fieldType                                = ($arrField['type'] != 'special') ? $arrField['type'] : $arrField['special_type'];
            switch ($fieldType) {
            case 'label':
                $sourcecode[] = $preview ? contrexx_raw2xhtml($arrField['lang'][FRONTEND_LANG_ID]['value']) : '<label class="noCaption">{'.$fieldId.'_VALUE}</label>';
                break;

            case 'checkbox':
                $sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" id="contactFormFieldId_'.$fieldId.'" type="checkbox" name="contactFormField_'.$fieldId.'" value="1" {SELECTED_'.$fieldId.'} />';
                break;

            case 'checkboxGroup':
                $selectedLang = $preview ? FRONTEND_LANG_ID : $lang;
                $sourcecode[] = '<div class="contactFormGroup" id="contactFormFieldId_'.$fieldId.'">';
                $options      = explode(',', $arrField['lang'][$selectedLang]['value']);
                foreach ($options as $index => $option) {
                    $sourcecode[] = '<input type="checkbox" class="contactFormClass_'.$arrField['type'].'" name="contactFormField_'.$fieldId.'[]" id="contactFormField_'.$index.'_'.$fieldId.'" value="'.contrexx_raw2xhtml($option).'" {SELECTED_'.$fieldId.'_'.$index.'}/><label class="noCaption" for="contactFormField_'.$index.'_'.$fieldId.'">'.($preview ? contrexx_raw2xhtml($option) : '{'.$fieldId.'_'.$index.'_VALUE}').'</label>';
                }
                $sourcecode[] = '</div>';
                break;

            case 'country':
            case 'access_country':
                $objResult    = $objDatabase->Execute("SELECT * FROM " . DBPREFIX . "lib_country");
                $sourcecode[] = '<select class="contactFormClass_'.$arrField['type'].'" name="contactFormField_'.$fieldId.'" id="contactFormFieldId_'.$fieldId.'">';
                if ($arrField['is_required'] == 1) {
                    $sourcecode[] = "<option value=\"".($preview ? $_ARRAYLANG['TXT_CONTACT_PLEASE_SELECT'] : '{TXT_CONTACT_PLEASE_SELECT}')."\">".($preview ? $_ARRAYLANG['TXT_CONTACT_PLEASE_SELECT'] : '{TXT_CONTACT_PLEASE_SELECT}')."</option>";
                } else {
                    $sourcecode[] = "<option value=\"".($preview ? $_ARRAYLANG['TXT_CONTACT_NOT_SPECIFIED'] : '{TXT_CONTACT_NOT_SPECIFIED}')."\">".($preview ? $_ARRAYLANG['TXT_CONTACT_NOT_SPECIFIED'] : '{TXT_CONTACT_NOT_SPECIFIED}')."</option>";
                }
                if ($preview) {
                    while (!$objResult->EOF) {
                        $sourcecode[] = "<option value=\"".$objResult->fields['name']."\" >".$objResult->fields['name']."</option>";
                        $objResult->MoveNext();
                    }
                } else {
                    $sourcecode[] = "<!-- BEGIN field_".$fieldId." -->";
                    $sourcecode[] = "<option value=\"{".$fieldId."_VALUE}\" {SELECTED_".$fieldId."} >{".$fieldId."_VALUE}</option>";
                    $sourcecode[] = "<!-- END field_".$fieldId." -->";
                }
                $sourcecode[] = "</select>";
                break;

            case 'date':
                $sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" type="text" name="contactFormField_'.$fieldId.'" id="DPC_date'.$fieldId.'_YYYY-MM-DD" />';
                break;

            case 'file':
                $sourcecode[] = '<div class="contactFormUpload"><div class="contactFormClass_uploadWidget" id="contactFormField_uploadWidget"></div>';
                $sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" id="contactFormField_upload" type="file" name="contactFormField_upload" disabled="disabled"/></div>';
                $hasFileInput = true;
                //$sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" id="contactFormFieldId_'.$fieldId.'" type="file" name="contactFormField_'.$fieldId.'" />';
                break;
            
            case 'hidden':
                $sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" id="contactFormFieldId_'.$fieldId.'" type="hidden" name="contactFormField_'.$fieldId.'" value="'.($preview ? contrexx_raw2xhtml($arrField['lang'][FRONTEND_LANG_ID]['value']) : "{".$fieldId."_VALUE}").'" />';
                break;

            case 'horizontalLine':
                $sourcecode[] = '<hr />';
                break;
            
            case 'password':
                $sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" id="contactFormFieldId_'.$fieldId.'" type="password" name="contactFormField_'.$fieldId.'" value="" />';
                break;

            case 'radio':
                $selectedLang = $preview ? FRONTEND_LANG_ID : $lang;
                $sourcecode[] = '<div class="contactFormGroup" id="contactFormFieldId_'.$fieldId.'">';
                $options      = explode(',', $arrField['lang'][$selectedLang]['value']);
                foreach ($options as $index => $option) {
                    $sourcecode[] .= '<input class="contactFormClass_'.$arrField['type'].'" type="radio" name="contactFormField_'.$fieldId.'" id="contactFormField_'.$index.'_'.$fieldId.'" value="'.($preview ? contrexx_raw2xhtml($option) : '{'.$fieldId.'_'.$index.'_VALUE}').'" {SELECTED_'.$fieldId.'_'.$index.'} /><label class="noCaption" for="contactFormField_'.$index.'_'.$fieldId.'">'.($preview ? contrexx_raw2xhtml($option) : '{'.$fieldId.'_'.$index.'_VALUE}').'</label><br />';
                }
                $sourcecode[] = '</div>';
                break;

            case 'select':
                $selectedLang = $preview ? FRONTEND_LANG_ID : $lang;
                $sourcecode[] = '<select class="contactFormClass_'.$arrField['type'].'" name="contactFormField_'.$fieldId.'" id="contactFormFieldId_'.$fieldId.'">';
                if ($preview) {
                    $options = explode(',', $arrField['lang'][$selectedLang]['value']);
                    foreach ($options as $index => $option) {
                        $sourcecode[] = "<option value='".contrexx_raw2xhtml($option)."'>". contrexx_raw2xhtml($option) ."</option>";
                    }
                } else {
                    $sourcecode[] = "<!-- BEGIN field_".$fieldId." -->";
                    $sourcecode[] = "<option value='{".$fieldId."_VALUE}' {SELECTED_".$fieldId."}>". '{'.$fieldId.'_VALUE}'."</option>";
                    $sourcecode[] = "<!-- END field_".$fieldId." -->";
                }
                $sourcecode[] = "</select>";
                break;

            case 'textarea':
                $sourcecode[] = '<textarea class="contactFormClass_'.$arrField['type'].'" name="contactFormField_'.$fieldId.'" id="contactFormFieldId_'.$fieldId.'" rows="5" cols="20">{'.$fieldId.'_VALUE}</textarea>';
                break;
            case 'recipient':
                $sourcecode[] = '<select class="contactFormClass_'.$arrField['type'].'" name="contactFormField_'.$fieldId.'" id="contactFormFieldId_'.$fieldId.'">';
                if ($preview) {
                    foreach ($this->arrForms[$id]['recipients'] as $index => $arrRecipient) {
                        $sourcecode[] = "<option value='".$index."'>". $arrRecipient['lang'][FRONTEND_LANG_ID] ."</option>";
                    }
                } else {
                    $sourcecode[] = "<!-- BEGIN field_".$fieldId." -->";
                    $sourcecode[] = "<option value='{".$fieldId."_VALUE_ID}' {SELECTED_".$fieldId."} >". '{'.$fieldId.'_VALUE}'."</option>";
                    $sourcecode[] = "<!-- END field_".$fieldId." -->";
                }
                $sourcecode[] = "</select>";
                break;
            default:
                $sourcecode[] = '<input class="contactFormClass_'.$arrField['type'].'" id="contactFormFieldId_'.$fieldId.'" type="text" name="contactFormField_'.$fieldId.'" value="'.($preview ? contrexx_raw2xhtml($arrField['lang'][FRONTEND_LANG_ID]['value']) : '{'.$fieldId.'_VALUE}').'" />';
                break;
            }
        }

        if ($preview) {
            $themeId = $objInit->arrLang[FRONTEND_LANG_ID]['themesid'];
            if (($objRS = $objDatabase->SelectLimit("SELECT `foldername` FROM `".DBPREFIX."skins` WHERE `id` = ".$themeId, 1)) !== false) {
                $themePath = $objRS->fields['foldername'];
            }
            $sourcecode[] = '<link href="../core_modules/contact/css/form.css" rel="stylesheet" type="text/css" />';

            if ($this->arrForms[$id]['useCaptcha']) {
                include_once ASCMS_LIBRARY_PATH.'/spamprotection/captcha.class.php';
                $captcha = new Captcha();
                $alt     = $captcha->getAlt();
                $url     = $captcha->getUrl();

                $sourcecode[] = '<div style="color: red;"></div>';
                $sourcecode[] = '<label>&nbsp;</label>';
                $sourcecode[] = '<label class="noCaption">';
                $sourcecode[] = $_ARRAYLANG['TXT_CONTACT_CAPTCHA_DESCRIPTION'];
                $sourcecode[] = '</label>';
                $sourcecode[] = '<span>'.$_ARRAYLANG["TXT_CONTACT_CAPTCHA"].'</span><img class="captcha" src="'.$url.'" alt="'.$alt.'" />';
                $sourcecode[] = '<label>&nbsp;</label>';
                $sourcecode[] = '<input id="contactFormCaptcha" type="text" name="contactFormCaptcha" /><br />';
                $sourcecode[] = '<input type="hidden" name="contactFormCaptchaOffset" value="'.$offset.'" />';
            }
        } else {
            $sourcecode[] = "<!-- BEGIN contact_form_captcha -->";
            $sourcecode[] = '<div style="color: red;">{CONTACT_CAPTCHA_ERROR}</div>';
            $sourcecode[] = '<label>&nbsp;</label>';
            $sourcecode[] = '<label class="noCaption">';
            $sourcecode[] = "{TXT_CONTACT_CAPTCHA_DESCRIPTION}<br />";
            $sourcecode[] = '</label>';
            $sourcecode[] = '<span>{TXT_CONTACT_CAPTCHA}</span><img class="captcha" src="{CONTACT_CAPTCHA_URL}" alt="{CONTACT_CAPTCHA_ALT}" />';
            $sourcecode[] = '<label>&nbsp;</label>';
            $sourcecode[] = '<input id="contactFormCaptcha" type="text" name="contactFormCaptcha" /><br />';
            $sourcecode[] = '<input type="hidden" name="contactFormCaptchaOffset" value="{CONTACT_CAPTCHA_OFFSET}" />';
            $sourcecode[] = "<!-- END contact_form_captcha -->";
        }

        $sourcecode[] = '<label>&nbsp;</label><input class="contactFormClass_button" type="submit" name="submitContactForm" value="'.($preview ? $_ARRAYLANG['TXT_CONTACT_SUBMIT'] : '{TXT_CONTACT_SUBMIT}').'" /><input class="contactFormClass_button" type="reset" value="'.($preview ? $_ARRAYLANG['TXT_CONTACT_RESET'] : '{TXT_CONTACT_RESET}').'" />';
        $sourcecode[] = '<input type="hidden" name="unique_id" value="{CONTACT_UNIQUE_ID}" />';
        $sourcecode[] = "</fieldset>";
        $sourcecode[] = "</form>";
        $sourcecode[] = "<!-- END contact_form -->";

        $sourcecode[] = $preview ? $this->_getJsSourceCode($id, $arrFields, $preview, $show) : "{CONTACT_JAVASCRIPT}";

        if($hasFileInput)
            $sourcecode[] = $this->getUploaderSourceCode();

        if ($show) {
            $sourcecode = preg_replace('/\{([A-Z0-9_-]+)\}/', '[[\\1]]', $sourcecode);
        }
        
        return implode("\n", $sourcecode);
    }


    function _getEntryDetails($arrEntry, $formId)
    {
        global $_ARRAYLANG;
        
        $arrFormFields = $this->getFormFields($formId);
        $recipient     = $this->getRecipients($formId);
        $rowNr         = 0;
        $langId        = $arrEntry['langId'];
        
        $sourcecode .= "<table border=\"0\" class=\"adminlist\" cellpadding=\"3\" cellspacing=\"0\" width=\"100%\">\n";
        foreach ($arrFormFields as $key => $arrField) {
            /*
             * Fieldset and Horizontal Field Type need not be displayed in the details page
             */
            if (!in_array($arrField['type'], $this->nonValueFormFieldTypes)) {
                $sourcecode .= "<tr class=".($rowNr % 2 == 0 ? 'row1' : 'row2').">\n";
                $sourcecode .= "<td style=\"vertical-align:top;\" width=\"15%\">".
                                contrexx_raw2xhtml($arrField['lang'][FRONTEND_LANG_ID]['name']).
                                ($arrField['type'] == 'hidden' ? ' (hidden)' : '').                                
                                "</td>\n";
                $sourcecode .= "<td width=\"85%\">";
                
                switch ($arrField['type']) {
                case 'checkbox':
                    $sourcecode .= isset($arrEntry['data'][$key]) && $arrEntry['data'][$key] ? ' '.$_ARRAYLANG['TXT_CONTACT_YES'] : ' '.$_ARRAYLANG['TXT_CONTACT_NO'];
                    break;

                case 'file':
                    if(isset($arrEntry['data'][$key])) {
                        $fieldData = $arrEntry['data'][$key];
                        if(substr($fieldData,0,1) == '*') {
                            $arrFiles = explode('*', substr($fieldData,1)); //the substr kills the leading '*';
                            foreach($arrFiles as $file) {
                                $sourcecode .= '<a href="'.ASCMS_PATH_OFFSET.htmlentities($file, ENT_QUOTES, CONTREXX_CHARSET).'" target="_blank" onclick="return confirm(\''.$_ARRAYLANG['TXT_CONTACT_CONFIRM_OPEN_UPLOADED_FILE'].'\')">'.ASCMS_PATH_OFFSET.htmlentities($file, ENT_QUOTES, CONTREXX_CHARSET).'</a>';
                                $sourcecode .= '&nbsp;';
                            }
                        }
                        else {
                            $sourcecode .= '<a href="'.ASCMS_PATH_OFFSET.htmlentities($fieldData, ENT_QUOTES, CONTREXX_CHARSET).'" target="_blank" onclick="return confirm(\''.$_ARRAYLANG['TXT_CONTACT_CONFIRM_OPEN_UPLOADED_FILE'].'\')">'.ASCMS_PATH_OFFSET.htmlentities($fieldData, ENT_QUOTES, CONTREXX_CHARSET).'</a>';
                        }
                    }
                    else {
                        $sourcecode .= '&nbsp;';
                    }
                    break;
                case 'recipient':
                    $recipientId = $arrEntry['data'][$key];
                    $sourcecode .= isset($recipient[$recipientId]['lang'][$langId]) ? htmlentities($recipient[$recipientId]['lang'][$langId], ENT_QUOTES, CONTREXX_CHARSET) : '&nbsp;';
                    break;
                case 'text':
                case 'checkboxGroup':
                case 'country':
                case 'date':
                case 'hidden':
                case 'password':
                case 'radio':
                case 'select':
                case 'textarea':
                case 'special':
                    $sourcecode .= isset($arrEntry['data'][$key]) ? nl2br(htmlentities($arrEntry['data'][$key], ENT_QUOTES, CONTREXX_CHARSET)) : '&nbsp;';
                    break;
                }

                $sourcecode .= "</td>\n";
                $sourcecode .= "</tr>\n";

                $rowNr++;
            }
        }
        $sourcecode .= "</table>\n";

        return $sourcecode;
    }

    function csv_mb_convert_encoding($data)
    {
        static $doConvert;
    
        if (!isset($doConvert)) {
            if (function_exists("mb_detect_encoding")
                && $this->_csvCharset != CONTREXX_CHARSET
            ) {
                $doConvert = true;
            } else {
                $doConvert = false;
            }
        }

        if ($doConvert) {
            return mb_convert_encoding($data, $this->_csvCharset, CONTREXX_CHARSET);;
        } else {
            return $data;
        }
    }

    /**
     * Get CSV File
     *
     * @access private
     * @global ADONewConnection
     * @global array
     * @global array
     */
    function _getCsv()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $formId = intval($_GET['formId']);

        $format = 'default';
        $csvFormat = array(
            'default' => array(
                'charset'       => CONTREXX_CHARSET,
                'delimiter'     => ';',
                'enclosure'     => '"',
                'content-type'  => 'text/comma-separated-values',
                'BOM'           => null,
                'LFB'           => "\r\n"
            ),
            'excel' => array(
                'charset'       => 'UTF-16LE',
                'delimiter'     => "\t",
                'enclosure'     => '"',
                'content-type'  => 'application/vnd.ms-excel',
                'BOM'           => chr(255).chr(254),
                'LFB'           => "\r\n"
            )
        );
        
        if (empty($formId)) {
            CSRF::header("Location: index.php?cmd=contact");
            return;
        }

        if (isset($_GET['format']) && isset($csvFormat[$_GET['format']])) {
            $format = $_GET['format'];
        }

        if (isset($this->arrForms[$formId]['lang'][FRONTEND_LANG_ID])) {
            $selectedInterfaceLanguage = FRONTEND_LANG_ID;
        } elseif (isset($this->arrForms[$formId]['lang'][FWLanguage::getDefaultLangId()])) {
            $selectedInterfaceLanguage = FWLanguage::getDefaultLangId();
        } else {
            $selectedInterfaceLanguage = key($this->arrForms[$formId]['lang']);
        }

        // $this->_csvCharset must be set first, because the methode $this->csv_mb_convert_encoding depends on this variable
        $this->_csvCharset = $csvFormat[$format]['charset'];
        $this->_csvEnclosure = $this->csv_mb_convert_encoding($csvFormat[$format]['enclosure'], $csvFormat[$format]['charset'], CONTREXX_CHARSET);
        $this->_csvSeparator = $this->csv_mb_convert_encoding($csvFormat[$format]['delimiter'], $csvFormat[$format]['charset'], CONTREXX_CHARSET);
        $this->_csvLFB = $this->csv_mb_convert_encoding($csvFormat[$format]['LFB'], $csvFormat[$format]['charset'], CONTREXX_CHARSET);

        $filename = $this->_replaceFilename($this->arrForms[$formId]['lang'][$selectedInterfaceLanguage]['name']. ".csv");
        $arrFormFields = $this->getFormFields($formId);

        // Because we return a csv, we need to set the correct header
        header("Content-Type: ".$csvFormat[$format]['content-type']."; charset=".$csvFormat[$format]['charset'], true);
        header("Content-Disposition: attachment; filename=\"$filename\"", true);

        // Print BOM
        print $csvFormat[$format]['BOM'];

        foreach ($arrFormFields as $arrField) {
            
            // Fieldset and Horizontal Field Type need not be displayed in the details page
            if (!in_array($arrField['type'], $this->nonValueFormFieldTypes)) {
                print $this->_escapeCsvValue($arrField['lang'][$selectedInterfaceLanguage]['name']).$this->_csvSeparator;
            }                        
        }

        $arrSettings = $this->getSettings();

        print ($arrSettings['fieldMetaDate'] == '1' ? $this->_escapeCsvValue($_ARRAYLANG['TXT_CONTACT_DATE']).$this->_csvSeparator : '')
                .($arrSettings['fieldMetaHost'] == '1' ? $this->_escapeCsvValue($_ARRAYLANG['TXT_CONTACT_HOSTNAME']).$this->_csvSeparator : '')
                .($arrSettings['fieldMetaLang'] == '1' ? $this->_escapeCsvValue($_ARRAYLANG['TXT_CONTACT_BROWSER_LANGUAGE']).$this->_csvSeparator : '')
                .($arrSettings['fieldMetaIP'] == '1' ? $this->_escapeCsvValue($_ARRAYLANG['TXT_CONTACT_IP_ADDRESS']) : '')
                .$this->_csvLFB;

        $query    = "SELECT `id` FROM ".DBPREFIX."module_contact_form_data WHERE id_form=".$formId." ORDER BY `time` DESC";
        $objEntry = $objDatabase->Execute($query);
        if ($objEntry !== false) {
            while (!$objEntry->EOF) {
                $formEntriesValues = $this->getFormEntry($objEntry->fields['id']);

                foreach ($arrFormFields as $fieldId => $value) {

                    if (!in_array($arrFormFields[$fieldId]['type'], $this->nonValueFormFieldTypes)) {
                        if (!empty ($formEntriesValues['data'][$fieldId])) {
                            switch ($arrFormFields[$fieldId]['type']) {
                            case 'checkbox':
                                print $this->_escapeCsvValue(isset($formEntriesValues['data'][$fieldId]) && $formEntriesValues['data'][$fieldId] ? ' '.$_ARRAYLANG['TXT_CONTACT_YES'] : ' '.$_ARRAYLANG['TXT_CONTACT_NO']);
                                break;

                            case 'file':
                                print $this->_escapeCsvValue(isset($formEntriesValues['data'][$fieldId]) ? ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET.$formEntriesValues['data'][$fieldId] : '');
                                break;

                            case 'text':
                            case 'checkboxGroup':
                            case 'hidden':
                            case 'password':
                            case 'radio':
                            case 'select':
                            case 'textarea':
                                print isset($formEntriesValues['data'][$fieldId]) ? $this->_escapeCsvValue($formEntriesValues['data'][$fieldId]) : '';
                                break;
                            }                             
                        } 
                        print $this->_csvSeparator;                            
                    }
                }
                    
                print ($arrSettings['fieldMetaDate'] == '1' ? $this->_escapeCsvValue(date(ASCMS_DATE_FORMAT, $formEntriesValues['time'])).$this->_csvSeparator : '')
                        .($arrSettings['fieldMetaHost'] == '1' ? $this->_escapeCsvValue($formEntriesValues['host']).$this->_csvSeparator : '')
                        .($arrSettings['fieldMetaLang'] == '1' ? $this->_escapeCsvValue($formEntriesValues['langId']).$this->_csvSeparator : '')
                    .($arrSettings['fieldMetaIP'] == '1' ? $this->_escapeCsvValue($formEntriesValues['ipaddress']) : '')
                    .$this->_csvLFB;
                
                $objEntry->MoveNext();
            }
        }

        exit();
    }

    /**
     * Escape a value that it could be inserted into a csv file.
     *
     * @param string $value
     * @return string
     */
    function _escapeCsvValue($value)
    {
        $value = preg_replace('/\r\n/', "\n", $value);
        $value = $this->csv_mb_convert_encoding($value, $this->_csvCharset, CONTREXX_CHARSET);;
        $valueModified = str_replace($this->_csvEnclosure, $this->_csvEnclosure.$this->_csvEnclosure, $value);
        $value = $this->_csvEnclosure.$valueModified.$this->_csvEnclosure;

        return $value;
    }

    /**
     * Replaces the special characters
     *
     * Replaces the special characters in a filename like whitespaces or
     * umlauts. Needed by the CSV generator.
     *
     * @access private
     * @param $filename string Filename where the characters have
     *                         to be replaced
     */
    function _replaceFilename($filename)
    {
        $filename = strtolower($filename);

        // replace whitespaces
        $filename = preg_replace('/\s/', '_', $filename);

        // replace umlauts
        // TODO: Use octal notation for special characters in regexes!
        $filename = preg_replace('%�%', 'oe', $filename);
        $filename = preg_replace('%�%', 'ue', $filename);
        $filename = preg_replace('%�%', 'ae', $filename);

        return $filename;
    }

    /**
     * Generates a new page in the content manager
     *
     * Adds a new page in the content manager with the source code
     * of the form the user needs.
     *
     * @access private
     * @global array
     * @global ADONewConnection
     * @global integer
     * @global array
     */
    function _createContentPage()
    {
        global $_ARRAYLANG, $_CONFIG;

        Permission::checkAccess(5, 'static');

        $formId = intval($_REQUEST['formId']);;
        $this->_handleContentPage($formId);

//TODO: needs replacement with url of new cm
        //header("Location: ".ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH."/index.php?cmd=content&act=edit&formId=".$formId."&".CSRF::param());
        header("Location: ".ASCMS_PATH_OFFSET.ASCMS_BACKEND_PATH."/index.php?cmd=contact");
        
        exit;
    }

    function _updateContentSite()
    {
        global $_ARRAYLANG;

        Permission::checkAccess(35, 'static');

        $formId = intval($_REQUEST['formId']);;
        $this->_handleContentPage($formId);
    }

    function _handleContentPage($formId = 0) {

        $objDatabase = Env::get('db');

        if ($formId > 0) {
            $objFWUser       = FWUser::getFWUserObject();

            $pageRepo = $this->em->getRepository('Cx\Model\ContentManager\Page');           
            $nodeRepo = $this->em->getRepository('Cx\Model\ContentManager\Node');

            $pages = $pageRepo->getFromModuleCmdByLang('contact', $formId);

//TODO: what's this used for?
            if (!empty($_REQUEST['pageId']) && intval($_REQUEST['pageId']) > 0) {
                $nodeId = intval($_REQUEST['pageId']);
                $node = $pageRepo->findOneBy(array('id' => $nodeId));
            } else {
                $root = $nodeRepo->getRoot();
                $node = new \Cx\Model\ContentManager\Node();
                $node->setParent($root);                
                $this->em->persist($node);
            }
//TODO: handle $node == null ( => node not found )
           
            $activeLangIds = array_keys(FWLanguage::getActiveFrontendLanguages());
            $selectedLangIds = array_keys($_POST['contactFormLanguages']);
            //sort out inactive languages
            $selectedLangIds = array_intersect($selectedLangIds, $activeLangIds);

            $presentLangIds = array_keys($pages);
            $updateLangIds = array_intersect($selectedLangIds, $presentLangIds);
            $newLangIds = array_diff($selectedLangIds, $updateLangIds);
            $deleteLangIds = array_diff($presentLangIds, $selectedLangIds);

            $activeLangIds = array_merge($newLangIds, $updateLangIds);

            foreach ($activeLangIds as $langId) {
                $objContactForm = $objDatabase->SelectLimit("SELECT name FROM ".DBPREFIX."module_contact_form_lang
                                                            WHERE formID=".$formId." AND langID=".$langId, 1);

                $page = null;
                if(isset($pages[$langId])) { //page already exists
                    //page 
                    $page = $pages[$langId];
                }
                else { //new Page
                    $page = new \Cx\Model\ContentManager\Page();
                    $page->setNode($node);
                }
                if ($objContactForm !== false) {
                    $catname = $objContactForm->fields['name'];
                }

                $content     = $this->_getSourceCode($formId, $langId);

                $pageRepo = $this->em->getRepository('Cx\Model\ContentManager\Page');
                
                $page->setContent($content);
                $page->setTitle($catname);
                $page->setUsername($objFWUser->objUser->getUsername());
                $page->setCmd($formId);
                $page->setModule('contact');
                $page->setDisplay(true);
                $page->setLang($langId);
                
                $this->em->persist($page);
            }

            foreach ($deleteLangIds as $langId) {
                $page = $pages[$langId];
                $this->em->remove($page);
            }

            $this->em->flush();
        }
    }


    function _createContactFeedbackSite()
    {
        $db = Env::get('db');

        //check if we already have a thanks page
        $pageRepo = $this->em->getRepository('Page');
        $nodeRepo = $this->em->getRepository('Node');

        $thxPage = $pageRepo->findOneBy(array(
            'module' => 6,
            'lang' => FRONTEND_LANG_ID
        ));

        if(!$thxPage) {
            //let's create a thanks page.
            $page = new \Cx\Model\ContentManager\Page();
                                 
            // get page from the module repository
            $thxQuery  = "SELECT `content`,
                                `title`, `cmd`, `expertmode`, `parid`,
                                `displaystatus`, `displayorder`, `username`,
                                `displayorder`
                              FROM ".DBPREFIX."module_repository
                         WHERE `moduleid` = 6 AND `lang`=".FRONTEND_LANG_ID;
            $objResult = $db->Execute($thxQuery);
            if ($objResult !== false) {
                //query translated from sql, original query in r11848
                //expertmode not set anymore.

                $page->setContent($objResult->fields['content']);
                $page->setTitle($objResult->fields['title']);
                $page->setCmd($objResult->fields['cmd']);
                $page->setDisplay($objResult->fields['displaystatus']);
                $page->setUsername($objResult->fields['username']);
                $page->setLang(FRONTEND_LANG_ID);

                //attach page to top of site
                $rootNode = $nodeRepo->getRoot();
                $page->setNode($rootNode);
                
                $em->persist($page);
                $em->flush();
            }   
        }
    }
}
?>
