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
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_contact
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core_Modules\Contact\Controller;

/**
 * ContactManager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_contact
 */
class ContactManager extends \Cx\Core_Modules\Contact\Controller\ContactLib
{
    var $_objTpl;

    var $_statusMessageOk;
    var $_statusMessageErr;

    var $_arrFormFieldTypes;
    var $_arrUserAccountData;

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

    private $act = '';

    /**
     * PHP5 constructor
     * @global \Cx\Core\Html\Sigma
     * @global array
     * @global array
     */
    function __construct()
    {
        global $objTemplate, $_ARRAYLANG, $_CONFIG;

        $this->em = \Env::get('em');

        $this->_objTpl = new \Cx\Core\Html\Sigma(\Env::get('cx')->getCodeBaseCoreModulePath().'/Contact/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->_objTpl->setGlobalVariable('CSRF_PARAM', \Cx\Core\Csrf\Controller\Csrf::param());

        $this->_arrFormFieldTypes = array(
            'text'          => $_ARRAYLANG['TXT_CONTACT_TEXTBOX'],
            'label'         => $_ARRAYLANG['TXT_CONTACT_TEXT'],
            'checkbox'      => $_ARRAYLANG['TXT_CONTACT_CHECKBOX'],
            'checkboxGroup' => $_ARRAYLANG['TXT_CONTACT_CHECKBOX_GROUP'],
            'country'       => $_ARRAYLANG['TXT_CONTACT_COUNTRY'],
            'date'          => $_ARRAYLANG['TXT_CONTACT_DATE'],
            'datetime'      => $_ARRAYLANG['TXT_CONTACT_DATETIME'],
            'file'          => $_ARRAYLANG['TXT_CONTACT_FILE_UPLOAD_SINGLE'],
            'multi_file'    => $_ARRAYLANG['TXT_CONTACT_FILE_UPLOAD_MULTI'],
            'fieldset'      => $_ARRAYLANG['TXT_CONTACT_FIELDSET'],
            'hidden'        => $_ARRAYLANG['TXT_CONTACT_HIDDEN_FIELD'],
            'horizontalLine'=> $_ARRAYLANG['TXT_CONTACT_HORIZONTAL_LINE'],
            'password'      => $_ARRAYLANG['TXT_CONTACT_PASSWORD_FIELD'],
            'radio'         => $_ARRAYLANG['TXT_CONTACT_RADIO_BOXES'],
            'select'        => $_ARRAYLANG['TXT_CONTACT_SELECTBOX'],
            'textarea'      => $_ARRAYLANG['TXT_CONTACT_TEXTAREA'],
            'recipient'     => $_ARRAYLANG['TXT_CONTACT_RECEIVER_ADDRESSES_SELECTION'],
        );

        $objUserAttribute = \FWUser::getFWUserObject()->objUser->objAttribute;
        $this->_arrUserAccountData = array(
            'access_email'         => $_ARRAYLANG['TXT_CONTACT_EMAIL'],
            'access_gender'        => contrexx_raw2xhtml($objUserAttribute->getById('gender')->getName()),
            'access_title'         => contrexx_raw2xhtml($objUserAttribute->getById('title')->getName()),
            'access_firstname'     => contrexx_raw2xhtml($objUserAttribute->getById('firstname')->getName()),
            'access_lastname'      => contrexx_raw2xhtml($objUserAttribute->getById('lastname')->getName()),
            'access_company'       => contrexx_raw2xhtml($objUserAttribute->getById('company')->getName()),
            'access_address'       => contrexx_raw2xhtml($objUserAttribute->getById('address')->getName()),
            'access_city'          => contrexx_raw2xhtml($objUserAttribute->getById('city')->getName()),
            'access_zip'           => contrexx_raw2xhtml($objUserAttribute->getById('zip')->getName()),
            'access_country'       => contrexx_raw2xhtml($objUserAttribute->getById('country')->getName()),
            'access_phone_office'  => contrexx_raw2xhtml($objUserAttribute->getById('phone_office')->getName()),
            'access_phone_private' => contrexx_raw2xhtml($objUserAttribute->getById('phone_private')->getName()),
            'access_phone_mobile'  => contrexx_raw2xhtml($objUserAttribute->getById('phone_mobile')->getName()),
            'access_phone_fax'     => contrexx_raw2xhtml($objUserAttribute->getById('phone_fax')->getName()),
            'access_birthday'      => contrexx_raw2xhtml($objUserAttribute->getById('birthday')->getName()),
            'access_website'       => contrexx_raw2xhtml($objUserAttribute->getById('website')->getName()),
            'access_profession'    => contrexx_raw2xhtml($objUserAttribute->getById('profession')->getName()),
            'access_interests'     => contrexx_raw2xhtml($objUserAttribute->getById('interests')->getName()),
            'access_signature'     => contrexx_raw2xhtml($objUserAttribute->getById('signature')->getName()),
        );

        $this->initContactForms();
        $this->initCheckTypes();
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable("CONTENT_NAVIGATION", '
            <a href="index.php?cmd=Contact" title="'.$_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS'].'" class="'.($this->act == '' ? 'active' : '').'">'.$_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS'].'</a>
            <a hreF="index.php?cmd=Media&amp;archive=Contact" title="'.$_ARRAYLANG['TXT_FILE_UPLOADS'].'">'.$_ARRAYLANG['TXT_FILE_UPLOADS'].'</a>
            <a href="index.php?cmd=Contact&amp;act=settings" title="'.$_ARRAYLANG['TXT_CONTACT_SETTINGS'].'" class="'.($this->act == 'settings' ? 'active' : '').'">'.$_ARRAYLANG['TXT_CONTACT_SETTINGS'].'</a>
        ');
    }

    /**
     * Get page
     *
     * Get the development page
     *
     * @access public
     * @global \Cx\Core\Html\Sigma
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
                \Permission::checkAccess(85, 'static');
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

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }

    function _getEntriesPage()
    {
        global $_ARRAYLANG;

        $entryId = isset($_REQUEST['entryId']) ? intval($_REQUEST['entryId']) : 0;
        $formId = isset($_REQUEST['formId']) ? intval($_REQUEST['formId']) : 0;

        $arrEntry = $this->getFormEntry($entryId);
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

        $arrSettings = $this->getSettings();

        $this->_objTpl->setVariable(array(
                'TXT_CONTACT_SETTINGS'                          => $_ARRAYLANG['TXT_CONTACT_SETTINGS'],
                'TXT_CONTACT_SAVE'                              => $_ARRAYLANG['TXT_CONTACT_SAVE'],
                'TXT_CONTACT_FILE_UPLOAD_DEPOSITION_PATH'       => $_ARRAYLANG['TXT_CONTACT_FILE_UPLOAD_DEPOSITION_PATH'],
                'TXT_CONTACT_SPAM_PROTECTION_WORD_LIST'         => $_ARRAYLANG['TXT_CONTACT_SPAM_PROTECTION_WORD_LIST'],
                'TXT_CONTACT_SPAM_PROTECTION_WW_DESCRIPTION'    => $_ARRAYLANG['TXT_CONTACT_SPAM_PROTECTION_WW_DESCRIPTION'],
                'TXT_CONTACT_DATE'                              => $_ARRAYLANG['TXT_CONTACT_DATE'],
                'TXT_CONTACT_HOSTNAME'                          => $_ARRAYLANG['TXT_CONTACT_HOSTNAME'],
                'TXT_CONTACT_BROWSER_LANGUAGE'                  => $_ARRAYLANG['TXT_CONTACT_BROWSER_LANGUAGE'],
                'TXT_CONTACT_BROWSER'                           => $_ARRAYLANG['TXT_CONTACT_BROWSER'],
                'TXT_CONTACT_IP_ADDRESS'                        => $_ARRAYLANG['TXT_CONTACT_IP_ADDRESS'],
                'TXT_CONTACT_META_DATE_BY_EXPORT'               => $_ARRAYLANG['TXT_CONTACT_META_DATE_BY_EXPORT'],
                'TXT_CONTACT_PERSONAL_DATA_NOTE'                => $_ARRAYLANG['TXT_CONTACT_PERSONAL_DATA_NOTE'],
                'TXT_CONTACT_STORE_SUBMISSIONS'                 => $_ARRAYLANG['TXT_CONTACT_STORE_SUBMISSIONS'],
                'TXT_CONTACT_STORE_SUBMISSION_DATA_IN_DB'       => $_ARRAYLANG['TXT_CONTACT_STORE_SUBMISSION_DATA_IN_DB'],
                'TXT_CONTACT_STORE_SUBMISSIONS_NOTE'            => $_ARRAYLANG['TXT_CONTACT_STORE_SUBMISSIONS_NOTE'],
        ));

        $this->_objTpl->setVariable(array(
                'CONTACT_FILE_UPLOAD_DEPOSITION_PATH'   => $arrSettings['fileUploadDepositionPath'],
                'CONTACT_SPAM_PROTECTION_WORD_LIST'     => $arrSettings['spamProtectionWordList'],
                'CONTACT_FIELD_META_DATE'               => $arrSettings['fieldMetaDate'] == '1' ? 'checked="checked"' : '',
                'CONTACT_FIELD_META_LANG'               => $arrSettings['fieldMetaLang'] == '1' ? 'checked="checked"' : '',
                'CONTACT_FIELD_META_HOST'               => $arrSettings['fieldMetaHost'] == '1' ? 'checked="checked"' : '',
                'CONTACT_FIELD_META_BROWSER'            => $arrSettings['fieldMetaBrowser'] == '1' ? 'checked="checked"' : '',
                'CONTACT_FIELD_META_IP'                 => $arrSettings['fieldMetaIP'] == '1' ? 'checked="checked"' : '',
                'CONTACT_STORE_SUBMISSIONS'             => $arrSettings['storeFormSubmissions'] == '1' ? 'checked="checked"' : '',
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
                    'fieldMetaBrowser'          => isset($_POST['contactFieldMetaBrowser']) ? intval($_POST['contactFieldMetaBrowser']) : 0,
                    'fieldMetaLang'             => isset($_POST['contactFieldMetaLang']) ? intval($_POST['contactFieldMetaLang']) : 0,
                    'fieldMetaIP'               => isset($_POST['contactFieldMetaIP']) ? intval($_POST['contactFieldMetaIP']) : 0,
                    'storeFormSubmissions'      => isset($_POST['contactStoreSubmissions']) ? intval($_POST['contactStoreSubmissions']) : 0,
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
                $this->_contactForms();
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
            } elseif (isset($this->arrForms[$formId]['lang'][\FWLanguage::getDefaultLangId()])) {
                $selectedInterfaceLanguage = \FWLanguage::getDefaultLangId();
            } else {
                $selectedInterfaceLanguage = key($this->arrForms[$formId]['lang']);
            }

            if (isset($_GET['pos'])) {
                $pos = intval($_GET['pos']);
            }

            $arrCols = array();
            $arrEntries = $this->getFormEntries($formId, $arrCols, $pos, $paging);
            if (count($arrEntries) > 0) {
                $arrFormFields = $this->getFormFields($formId);
                $arrFormFieldNames = $this->getFormFieldNames($formId);

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

                    $colName = 'N/A';
                    if (isset($arrFormFields[$col]['lang'][$selectedInterfaceLanguage]['name'])) {
                        $colName = $arrFormFields[$col]['lang'][$selectedInterfaceLanguage]['name'];
                    } elseif (!empty($arrFormFields[$col]['lang'])) {
                        $colName = current($arrFormFields[$col]['lang'])['name'];
                    }
                    $this->_objTpl->setVariable('CONTACT_COL_NAME', contrexx_raw2xhtml($colName));
                    $this->_objTpl->parse('contact_col_names');
                    $colNr++;
                }

                $rowNr = 0;
                foreach ($arrEntries as $entryId => $arrEntry) {
                    $this->_objTpl->setVariable('CONTACT_FORM_ENTRIES_ROW_CLASS', $rowNr % 2 == 0 ? 'row2' : 'row1');

                    $date = date(ASCMS_DATE_FORMAT_DATE, $arrEntry['time']);
                    $now  = date(ASCMS_DATE_FORMAT_DATE, strtotime('now'));
                    if ($date == $now) {
                        $date = $_ARRAYLANG['TXT_CORE_TODAY'];
                    }
                    $dateLink = '<a href="index.php?cmd=Contact&amp;act=entries&amp;formId='.$formId.'&amp;entryId='.$entryId.'" title="'.$_ARRAYLANG['TXT_CONTACT_DETAILS'].'">'.$date.'</a>';
                    $this->_objTpl->setVariable(array(
                        'CONTACT_FORM_DATE'     => $dateLink,
                        'CONTACT_FORM_ENTRY_ID' => $entryId
                    ));

                    $this->_objTpl->parse('contact_form_entry_data');

                    $colNr  = 0;
                    $langId = $arrEntry['langId'];
                    foreach ($arrCols as $col) {
                        $value = '';
                        if ($colNr == $maxFields) {
                            // check if field is an upload-fields that contains uploaded data
                            if (   isset($arrEntry['data'][$col])
                                && isset($arrFormFields[$col])
                                && in_array($arrFormFields[$col]['type'], array('file', 'multi_file'))
                                && !empty($arrEntry['data'][$col])
                            ) {
                                //show attach icon if any files have been submitted
                                $this->_objTpl->touchBlock('contact_form_entry_attach');
                            }
                            continue;
                        }

                        if (isset($arrEntry['data'][$col])) {
                            if (
                                isset($arrFormFields[$col]) &&
                                in_array($arrFormFields[$col]['type'], array('file', 'multi_file'))
                            ) {
                                $fileData = $arrEntry['data'][$col];
                                if ($fileData) {
                                    //show attach icon
                                    $this->_objTpl->touchBlock('contact_form_entry_attach');
                                    //new style entry; multiple files and links
                                    $arrFiles = explode('*', $fileData);
                                    foreach($arrFiles as $file) {
                                        $file = contrexx_raw2xhtml($file);
                                        $img  = $this->getFileIcon($file);
                                        $value .= '<a href="'.\Env::get('cx')->getWebsiteOffsetPath().$file.'" style="white-space:nowrap;" target="_blank" onclick="return confirm(\''.str_replace("\n", '\n', $_ARRAYLANG['TXT_CONTACT_CONFIRM_OPEN_UPLOADED_FILE']).'\')">'.$img.basename($file).'</a><br />';
                                    }
                                }
                            } elseif (
                                isset($arrFormFields[$col]) &&
                                $arrFormFields[$col]['type'] == 'recipient'
                            ) {
                                $recipient = $this->getRecipients($formId, false);
                                $value = htmlentities($recipient[$arrEntry['data'][$col]]['lang'][$langId], ENT_QUOTES, CONTREXX_CHARSET);
                            } elseif (
                                isset($arrFormFields[$col]) &&
                                $arrFormFields[$col]['type'] == 'checkbox'
                            ) {
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
                        if (
                            isset($arrFormFields[$col]) &&
                            $arrFormFields[$col]['type'] == 'checkbox' &&
                            empty($arrEntry['data'][$col])
                        ) {
                            $value = $_ARRAYLANG['TXT_CONTACT_NO'];
                        }

                        $this->_objTpl->setVariable('CONTACT_FORM_ENTRIES_CELL_CONTENT', $value);
                        $this->_objTpl->parse('contact_form_entry_data');

                        $value = '';
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
        global $_CORELANG, $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('module_contact_forms_overview.html');
        $this->_pageTitle = $_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS'];

        $uri = \Html::getRelativeUri_entities();
        $objSorting = new \Sorting($uri,
            array(
                'latestEntry' => $_ARRAYLANG['TXT_CONTACT_LATEST_ENTRY'],
                'id' => $_ARRAYLANG['TXT_CONTACT_ID'],
                'name' => $_ARRAYLANG['TXT_CONTACT_NAME'],
                'numberOfEntries' => $_ARRAYLANG['TXT_CONTACT_NUMBER_OF_ENTRIES'],
            ), false, 'order_forms');

        $this->_objTpl->setVariable(array(
                'TXT_CONTACT_CONFIRM_DELETE_FORM'           => $_ARRAYLANG['TXT_CONTACT_CONFIRM_DELETE_FORM'],
                'TXT_CONTACT_FORM_ENTRIES_WILL_BE_DELETED'  => $_ARRAYLANG['TXT_CONTACT_FORM_ENTRIES_WILL_BE_DELETED'],
                'TXT_CONTACT_ACTION_IS_IRREVERSIBLE'        => $_ARRAYLANG['TXT_CONTACT_ACTION_IS_IRREVERSIBLE'],
                'TXT_CONTACT_CONTACT_FORMS'                 => $_ARRAYLANG['TXT_CONTACT_CONTACT_FORMS'],
                'TXT_CONTACT_LANGUAGE'                      => $_ARRAYLANG['TXT_CONTACT_LANGUAGE'],
                'TXT_CONTACT_FUNCTIONS'                     => $_ARRAYLANG['TXT_CONTACT_FUNCTIONS'],
                'TXT_CONTACT_ADD_NEW_CONTACT_FORM'          => $_ARRAYLANG['TXT_CONTACT_ADD_NEW_CONTACT_FORM'],
                'TXT_CONTACT_CONFIRM_DELETE_CONTENT_SITE'   => $_ARRAYLANG['TXT_CONTACT_CONFIRM_DELETE_CONTENT_SITE'],
                'TXT_CONTACT_ID'                            => $objSorting->getHeaderForField('id'),
                'TXT_CONTACT_NAME'                          => $objSorting->getHeaderForField('name'),
                'TXT_CONTACT_LATEST_ENTRY'                  => $objSorting->getHeaderForField('latestEntry'),
                'TXT_CONTACT_NUMBER_OF_ENTRIES'             => $objSorting->getHeaderForField('numberOfEntries'),
        ));

        $this->_objTpl->setGlobalVariable(array(
                'TXT_CONTACT_MODIFY'                        => $_ARRAYLANG['TXT_CONTACT_MODIFY'],
                'TXT_CONTACT_DELETE'                        => $_ARRAYLANG['TXT_CONTACT_DELETE'],
                'TXT_CONTACT_SHOW_SOURCECODE'               => $_ARRAYLANG['TXT_CONTACT_SHOW_SOURCECODE'],
                'TXT_CONTACT_USE_AS_TEMPLATE'               => $_ARRAYLANG['TXT_CONTACT_USE_AS_TEMPLATE'],
                'TXT_CONTACT_GET_CSV'                       => $_ARRAYLANG['TXT_CONTACT_GET_CSV'],
                'TXT_CONTACT_CSV_TOOLTIP'                   => $_ARRAYLANG['TXT_CONTACT_CSV_TOOLTIP'],
                'TXT_CONTACT_XLS_TOOLTIP'                   => $_ARRAYLANG['TXT_CONTACT_XLS_TOOLTIP'],
                'TXT_CONTACT_DOWNLOAD'                      => $_ARRAYLANG['TXT_CONTACT_DOWNLOAD']
        ));

        $this->initContactForms(0, $objSorting->getOrder());

        $rowNr = 0;
        if (is_array($this->arrForms)) {
            foreach ($this->arrForms as $formId => $arrForm) {
                $formName = '';
                $entryCount = '-';

                $pageRepo = $this->em->getRepository('\Cx\Core\ContentManager\Model\Entity\Page');
                $page = $pageRepo->findOneBy(array(
                    'module' => 'Contact',
                    'cmd' => $formId,
                    'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
                ));

                $pageExists = $page !== null;

                $this->_objTpl->setGlobalVariable('CONTACT_FORM_ID', $formId);

                if (isset($arrForm['lang'][FRONTEND_LANG_ID])) {
                    $selectedInterfaceLanguage = FRONTEND_LANG_ID;
                } elseif (isset($arrForm['lang'][\FWLanguage::getDefaultLangId()])) {
                    $selectedInterfaceLanguage = \FWLanguage::getDefaultLangId();
                } else {
                    $selectedInterfaceLanguage = key($arrForm['lang']);
                }

                $pages = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Page')->getFromModuleCmdByLang('Contact', $formId);
                $lang = array();
                $activeLanguages = \FWLanguage::getActiveFrontendLanguages();
                foreach ($arrForm['lang'] as $langId => $value) {
                    if (!array_key_exists($langId, $activeLanguages)) {
                        continue;
                    }
                    $links = '';
                    if (isset($pages[$langId])) {
                        $previewURL = '../'.\FWLanguage::getLanguageCodeById($langId).$pages[$langId]->getPath();
                        $links = \FWLanguage::getLanguageCodeById($langId).
                                '<a title="'.$_CORELANG['TXT_CORE_EDIT_PAGE_LAYOUT'].'" href="index.php?cmd=ContentManager&page='.$pages[$langId]->getId().'&tab=content" class="view">'.
                                    '<img src="../core/Core/View/Media/icons/green_arrow_down.png" />'.
                                '</a>'.
                                '<a title="'.$_CORELANG['TXT_CORE_SHOW_FRONTEND_VIEW'].'" target="_blank" href="'.$previewURL.'" class="view">'.
                                    '<img src="../core/Core/View/Media/icons/blue_arrow_up.png" />'.
                                '</a>';
                    } else {
                        $links = \FWLanguage::getLanguageCodeById($langId);
                    }
                    $lang[] = '<span class="lang">'.$links.'</span>';
                }

                $langString = implode($lang);

                $formName = contrexx_raw2xhtml($arrForm['lang'][$selectedInterfaceLanguage]['name']);
                $useCrm   = !empty($arrForm['saveDataInCRM']) ? "&nbsp;&nbsp;<img style='margin-top: 2px;' src='../core/Core/View/Media/navigation_level_1_189.png' class='tooltip-trigger' alt='crm' /><span class='tooltip-message'>".$_ARRAYLANG['TXT_CONTACT_FORM_USED_IN_CRM']."</span>" : '';

                // check if the form contains submitted data
                $arrFormNumber = isset($arrForm['number']) ? $arrForm['number'] : 0;
                if ($arrFormNumber > 0) {
                    $entryCount = $arrFormNumber;
                    $formName = "<a href='index.php?cmd=Contact&amp;act=forms&amp;tpl=entries&amp;formId=".$formId."' title='".$_ARRAYLANG['TXT_CONTACT_SHOW_ENTRIES']."'>".$formName."</a>";

                    $this->_objTpl->touchBlock('contact_export');
                } else {
                    $this->_objTpl->hideBlock('contact_export');
                }

                $this->_objTpl->setVariable(array(
                        'TXT_CONTACT_VIEW'                  => $_CORELANG['TXT_CORE_CM_VIEW'],
                        'CONTACT_FORM_ROW_CLASS'            => $rowNr % 2 == 1 ? 'row1' : 'row2',
                        'CONTACT_FORM_NAME'                 => $formName,
                        'CONTACT_CRM_IMG'                   => $useCrm,
                        'CONTACT_FORM_LAST_ENTRY'           => !empty($arrForm['last']) ? date(ASCMS_DATE_FORMAT, $arrForm['last']) : '-',
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
        $arrActiveSystemFrontendLanguages = \FWLanguage::getActiveFrontendLanguages();
        $counter = 0;

        if (!$formId) {
            $selectedInterfaceLanguage = FRONTEND_LANG_ID;
        } elseif (isset($this->arrForms[$formId]['lang'][FRONTEND_LANG_ID])) {
            $selectedInterfaceLanguage = FRONTEND_LANG_ID;
        } elseif (isset($this->arrForms[$formId]['lang'][\FWLanguage::getDefaultLangId()])) {
            $selectedInterfaceLanguage = \FWLanguage::getDefaultLangId();
        } elseif (count($this->arrForms[$formId]['lang'])) {
            $selectedInterfaceLanguage = key($this->arrForms[$formId]['lang']);
        }

        //Get the fallback languages array
        $fallBackArr = \FWLanguage::getFallbackLanguageArray();

        foreach ($arrRecipients as $arrRecipient) {
            foreach ($arrActiveSystemFrontendLanguages as $langId => $lang) {
                $isSelectedInterfaceLanguage = $langId == $selectedInterfaceLanguage;

                if (isset($arrRecipient['lang'][$fallBackArr[$lang['id']]])) {
                    $optionalLanguageId = $fallBackArr[$lang['id']];
                } elseif (isset($arrRecipient['lang'][\FWLanguage::getDefaultLangId()])) {
                    $optionalLanguageId = \FWLanguage::getDefaultLangId();
                } else {
                    $optionalLanguageId = key($arrRecipient['lang']);
                }

                $this->_objTpl->setVariable(array(
                    'CONTACT_FORM_RECIPIENT_LANG_ID'    => $langId,
                    'RECIPIENT_NAME_DISPLAY'            => $isSelectedInterfaceLanguage ? 'block' : 'none',
                    'CONTACT_FORM_RECIPIENT_NAME'       => (!empty($arrRecipient['lang'][$langId])) ? contrexx_raw2xhtml($arrRecipient['lang'][$langId]) : contrexx_raw2xhtml($arrRecipient['lang'][$optionalLanguageId])
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

        \JS::activate('cx');

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
        $saveDataInCRM  = 0;
        $useCustomStyle = 0;
        $sendCopy       = 0;
        $useEmailOfSender = 0;
        $sendHtmlMail   = 1;
        $sendAttachment = 0;
        $emails         = $_CONFIG['contactFormEmail'];
        $crmCustomerGroups = array();
        $sendMultipleReply = 0;

        $arrActiveSystemFrontendLanguages = \FWLanguage::getActiveFrontendLanguages();

        if (count($arrActiveSystemFrontendLanguages) == 1) {
            $this->_objTpl->setVariable('CONTACT_LANGUAGE_TABS_DISPLAY', 'display: none');
        }

        if (isset($this->arrForms[$formId])) {
            // editing
            $actionTitle = $_ARRAYLANG['TXT_CONTACT_MODIFY_CONTACT_FORM'];
            $showForm       = $this->arrForms[$formId]['showForm'];
            $useCaptcha     = $this->arrForms[$formId]['useCaptcha'];
            $saveDataInCRM  = $this->arrForms[$formId]['saveDataInCRM'];
            $useCustomStyle = $this->arrForms[$formId]['useCustomStyle'];
            $sendCopy       = $this->arrForms[$formId]['sendCopy'];
            $useEmailOfSender = $this->arrForms[$formId]['useEmailOfSender'];
            $sendHtmlMail   = $this->arrForms[$formId]['htmlMail'];
            $sendAttachment = $this->arrForms[$formId]['sendAttachment'];
            $emails         = $this->arrForms[$formId]['emails'];
            $crmCustomerGroups = !empty($this->arrForms[$formId]['crmCustomerGroups']) ? $this->arrForms[$formId]['crmCustomerGroups'] : array();
            $sendMultipleReply = $this->arrForms[$formId]['sendMultipleReply'];
        }

        if (count($arrActiveSystemFrontendLanguages) > 0) {
            $intLanguageCounter = 0;
            $arrLanguages       = array(0 => '', 1 => '', 2 => '');
            $strJsTabToDiv      = '';

            foreach($arrActiveSystemFrontendLanguages as $langId => $arrLanguage) {
                // Bugfix: if only one language is activated, it must be true, so the fields can be saved
                if(count($arrActiveSystemFrontendLanguages) == 1){
                    $boolLanguageIsActive = true;
                }elseif ($formId) {
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

        $objCrmLibrary = new \Cx\Modules\Crm\Controller\CrmLibrary('Crm');
        $memberships   = array_keys($objCrmLibrary->getMemberships());
        $objCrmLibrary->getMembershipDropdown($this->_objTpl, $memberships, "contactMembership", $crmCustomerGroups);

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
        } elseif (isset($this->arrForms[$formId]['lang'][\FWLanguage::getDefaultLangId()])) {
            $selectedInterfaceLanguage = \FWLanguage::getDefaultLangId();
        } elseif (count($this->arrForms[$formId]['lang'])) {
            $selectedInterfaceLanguage = key($this->arrForms[$formId]['lang']);
        }
        foreach(\FWLanguage::getLanguageArray() as $language){
            if($language['id'] == $selectedInterfaceLanguage && $language["frontend"] == 0){
                $selectedInterfaceLanguage = \FWLanguage::getDefaultLangId();
            }
        }
        //Get the fallback languages array
        $fallBackArr      = \FWLanguage::getFallbackLanguageArray();
        $strJsFallBackArr = '';
        foreach ($fallBackArr as $languageId => $fallBackLanguageId) {
            $strJsFallBackArr .= 'arrFallBackLang['.$languageId.'] = "'.$fallBackLanguageId.'";'."\n";
        }
        $this->_objTpl->setVariable(array(
            'FALL_BACK_LANGUAGES' => $strJsFallBackArr,
            'DEFAULT_LANGUAGE'    => \FWLanguage::getDefaultLangId()
        ));


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

            if (isset($this->arrForms[$formId]['lang'][$fallBackArr[$lang['id']]])) {
                $optionalLanguageId = $fallBackArr[$lang['id']];
            } elseif (isset($this->arrForms[$formId]['lang'][\FWLanguage::getDefaultLangId()])) {
                $optionalLanguageId = \FWLanguage::getDefaultLangId();
            } else {
                $optionalLanguageId = key($this->arrForms[$formId]['lang']);
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
                'CONTACT_FORM_MAIL_TEMPLATE_HIDDEN'             => !empty ($langVars['mailTemplate']) ? contrexx_raw2xhtml($langVars['mailTemplate']) : contrexx_raw2xhtml($this->arrForms[$formId]['lang'][$optionalLanguageId]['mailTemplate']),
                'CONTACT_FORM_SUBJECT'                          => !empty ($langVars['subject']) ? contrexx_raw2xhtml($langVars['subject']) : contrexx_raw2xhtml($this->arrForms[$formId]['lang'][$optionalLanguageId]['subject']),
            ));
            $this->_objTpl->parse('notificationLanguageForm');

            $this->_objTpl->setVariable(array(
                'CONTACT_FORM_ID'                               => $formId,
                'LANG_ID'                                       => $lang['id'],
                'LANG_FORM_DISPLAY'                             => $isSelectedInterfaceLanguage ? 'block' : 'none',

                'CONTACT_FORM_NAME'                             => !empty ($langVars['name']) ? contrexx_raw2xhtml($langVars['name']) : contrexx_raw2xhtml($this->arrForms[$formId]['lang'][$optionalLanguageId]['name']),//$this->arrForms[$formId]['lang'][$lang['id']]['name'],
                'CONTACT_FORM_FIELD_NEXT_ID'                    => $lastFieldId+1,
                'CONTACT_FORM_TEXT_HIDDEN'                      => !empty ($langVars['text']) ? contrexx_raw2xhtml($langVars['text']) : contrexx_raw2xhtml($this->arrForms[$formId]['lang'][$optionalLanguageId]['text']),
                'CONTACT_FORM_FEEDBACK_HIDDEN'                  => !empty ($langVars['feedback']) ? contrexx_raw2xhtml($langVars['feedback']) : contrexx_raw2xhtml($this->arrForms[$formId]['lang'][$optionalLanguageId]['feedback']),
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
                'CONTACT_FORM_FIELD_DATETIME_TPL'               => $this->_getFormFieldAttribute(0, 'datetime', '', false),
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

            foreach ($arrActiveSystemFrontendLanguages as $langId => $lang) {
                if ($formId) {
                    $isActive = $langId == $selectedInterfaceLanguage;
                } else {
                    // when creating a new form, the form shall be created for the currently selected frontend language
                    $isActive = $lang['id'] == FRONTEND_LANG_ID;
                }
                $show     = ($first && $isActive);

                if (isset($field['lang'][$fallBackArr[$lang['id']]])) {
                    $optionalLanguageId = $fallBackArr[$lang['id']];
                } elseif (isset($field['lang'][\FWLanguage::getDefaultLangId()])) {
                    $optionalLanguageId = \FWLanguage::getDefaultLangId();
                } else {
                    $optionalLanguageId = key($field['lang']);
                }

                $this->_objTpl->setVariable(array(
                    'LANG_ID'                   => $lang['id'],
                    'LANG_NAME_DISPLAY'         => $show ? 'block' : 'none',
                    'LANG_VALUE_DISPLAY'        => $show ? 'block' : 'none',
                    'FORM_FIELD_NAME'           => isset($field['lang'][$lang['id']]) ? contrexx_raw2xhtml($field['lang'][$lang['id']]['name']) : contrexx_raw2xhtml($field['lang'][$optionalLanguageId]['name']),
                    'CONTACT_FORM_FIELD_VALUE'  => $this->_getFormFieldAttribute($realFieldID,
                                                                                 $fieldType,
                                                                                 isset($field['lang'][$lang['id']]) ? contrexx_raw2xhtml($field['lang'][$lang['id']]['value']) : contrexx_raw2xhtml($field['lang'][$optionalLanguageId]['value']),
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
            'CONTACT_FORM_USE_CRMMODULE_YES'                => $saveDataInCRM   ? 'checked="checked"' : '',
            'CONTACT_FORM_USE_CRMMODULE_NO'                 => $saveDataInCRM    ? '' : 'checked="checked"',
            'CONTACT_FORM_USE_CUSTOM_STYLE_YES'             => $useCustomStyle  ? 'checked="checked"' : '',
            'CONTACT_FORM_USE_CUSTOM_STYLE_NO'              => $useCustomStyle  ? '' : 'checked="checked"',
            'CONTACT_FORM_SEND_HTML_MAIL'                   => $sendHtmlMail    ? 'checked="checked"' : '',
            'CONTACT_MAIL_TEMPLATE_STYLE'                   => $sendHtmlMail    ? 'table-row' : 'none',
            'CONTACT_FORM_SEND_COPY_YES'                    => $sendCopy        ? 'checked="checked"' : '',
            'CONTACT_FORM_SEND_COPY_NO'                     => $sendCopy        ? '' : 'checked="checked"',
            'CONTACT_FORM_SEND_MULTIPLE_REPLY_YES'          => $sendMultipleReply   ? 'checked="checked"' : '',
            'CONTACT_FORM_SEND_MULTIPLE_REPLY_NO'           => $sendMultipleReply   ? '' : 'checked="checked"',
            'CONTACT_FORM_USE_EMAIL_OF_SENDER_YES'          => $useEmailOfSender? 'checked="checked"' : '',
            'CONTACT_FORM_USE_EMAIL_OF_SENDER_NO'           => $useEmailOfSender? '' : 'checked="checked"',
            'CONTACT_FORM_SEND_ATTACHMENT'                  => $sendAttachment  ? 'checked="checked"' : '',
            'CONTACT_FORM_EMAIL'                            => contrexx_raw2xhtml($emails),
            'CONTACT_JS_SUBMIT_FUNCTION'                    => $jsSubmitFunction,
            'FORM_COPY'                                     => intval($copy),
            'CONTACT_FORM_TEXT'                             => new \Cx\Core\Wysiwyg\Wysiwyg('contactFormTextEditor'),
            'CONTACT_FORM_FEEDBACK'                         => new \Cx\Core\Wysiwyg\Wysiwyg('contactFormFeedbackEditor'),
            'CONTACT_MAIL_TEMPLATE'                         => new \Cx\Core\Wysiwyg\Wysiwyg('contactMailTemplateEditor', '', 'fullpage'),

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
            'TXT_CONTACT_SAVE_DATA_IN_CRM'                  => $_ARRAYLANG['TXT_CONTACT_SAVE_DATA_IN_CRM'],
            'TXT_CONTACT_CAPTCHA'                           => $_ARRAYLANG['TXT_CONTACT_CAPTCHA'],
            'TXT_CONTACT_CAPTCHA_DESCRIPTION'               => $_ARRAYLANG['TXT_CONTACT_CAPTCHA_DESCRIPTION'],
            'TXT_CONTACT_SEND_COPY_DESCRIPTION'             => $_ARRAYLANG['TXT_CONTACT_SEND_COPY_DESCRIPTION'],
            'TXT_CONTACT_SEND_COPY'                         => $_ARRAYLANG['TXT_CONTACT_SEND_COPY'],
            'TXT_CONTACT_SEND_MULTIPLE_REPLY_DESCRIPTION'   => $_ARRAYLANG['TXT_CONTACT_SEND_MULTIPLE_REPLY_DESCRIPTION'],
            'TXT_CONTACT_SEND_MULTIPLE_REPLY'               => $_ARRAYLANG['TXT_CONTACT_SEND_MULTIPLE_REPLY'],
            'TXT_CONTACT_USE_EMAIL_OF_SENDER_DESCRIPTION'   => $_ARRAYLANG['TXT_CONTACT_USE_EMAIL_OF_SENDER_DESCRIPTION'],
            'TXT_CONTACT_USE_EMAIL_OF_SENDER'               => $_ARRAYLANG['TXT_CONTACT_USE_EMAIL_OF_SENDER'],
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
            'TXT_CONTACT_CHOOSE_MEMBERSHIPS'                => $_ARRAYLANG['TXT_CONTACT_CHOOSE_MEMBERSHIPS'],
            'TXT_CONTACT_ASSIGN_TO_CRM_CUSTOMER_GROUP'      => $_ARRAYLANG['TXT_CONTACT_ASSIGN_TO_CRM_CUSTOMER_GROUP'],
            'TXT_CONTACT_ASSIGN_CRM_CUSTOMER_GROUP_DESCRIPTION'  => $_ARRAYLANG['TXT_CONTACT_ASSIGN_CRM_CUSTOMER_GROUP_DESCRIPTION']

        ));

        if (empty($recipients)) {
            // make an empty one so there's at least one
            $recipients[0] = array(
                'id'       => 1,
                'email'    => '',
                'editType' => 'new',
                'lang'     => array(),
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

        \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Contact');

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
        case 'special':
            $field .= "<div style=\"display: ".$display.";\"  id=\"fieldValueTab_".$id."_".$langid."\" class=\"fieldValueTabs_".$id."\">";
            $field .= "<input style=\"width:308px;background: #FFFFFF;\" type=\"text\" name=\"contactFormFieldValue[".$id."][".$langid."]\" value=\"".$attr."\" />\n";
            $field .= "</div>";
            return $field;
            break;

        case 'label':
            $field .= "<div style=\"display: ".$display.";\"  id=\"fieldValueTab_".$id."_".$langid."\" class=\"fieldValueTabs_".$id."\">";
            $field .= "<textarea style=\"width: 90%;background: #FFFFFF\" name=\"contactFormFieldValue[".$id."][".$langid."]\">".$attr."</textarea>\n";
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
                $field ="<select style=\"width:331px;\" name=\"contactFormFieldValue[".$id."]\">\n";
                $field .= "<option value=\"".$_ARRAYLANG['TXT_CONTACT_PLEASE_SELECT']."\" >".$_ARRAYLANG['TXT_CONTACT_PLEASE_SELECT']."</option>\n";
                $country = \Cx\Core\Country\Controller\Country::getNameArray(true, $langid);
                foreach ($country as $id => $name) {
                    $sourcecode[] = "<option value=\"" . $name . "\" " . ($attr == $name ? 'selected="selected"' : '') . ">" . $name . "</option>";
                }
                $field .= "</select>";
                return $field;
            }
            break;
        case 'checkboxGroup':
        case 'select':
        case 'radio':
            $field .= "<div style=\"display: ".$display.";\"  id=\"fieldValueTab_".$id."_".$langid."\" class=\"fieldValueTabs_".$id."\">";
            $field .= "<input style=\"width:308px;background: #FFFFFF;\" type=\"text\" name=\"contactFormFieldValue[".$id."][".$langid."]\" value=\"".$attr."\" />&nbsp;<span class=\"tooltip-trigger icon-info\"></span><span class=\"tooltip-message multiple-values-by-comma\">{TXT_CONTACT_SEPARATE_MULTIPLE_VALUES_BY_COMMA}</span>\n";
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
        $adding  = isset($_POST['copy']) ? $_POST['copy'] || !$formId : !$formId;
        $content = isset($_POST['contentSiteAction']) ? $_POST['contentSiteAction'] : '';

        if (isset($_POST['saveForm'])) {
            $crmCustomerGroups = array();
            $emails         = $this->getPostRecipients();
            $showForm       = !empty($_POST['contactFormShowForm']) ? 1 : 0;
            $useCaptcha     = !empty($_POST['contactFormUseCaptcha']) ? 1 : 0;
            $saveDataInCrm  = !empty($_POST['contactFormUseCrmModule']) ? 1 : 0;
            $useCustomStyle = !empty($_POST['contactFormUseCustomStyle']) ? 1 : 0;
            $sendCopy       = !empty($_POST['contactFormSendCopy']) ? 1 : 0;
            $sendHtmlMail   = !empty($_POST['contactFormHtmlMail']) ? 1 : 0;
            $sendAttachment = !empty($_POST['contactFormSendAttachment']) ? 1 : 0;
            $sendMultipleReply = $sendCopy && !empty($_POST['contactFormSendMultipleReply']) ? 1 : 0;
            $useEmailOfSender = !$sendMultipleReply && !empty($_POST['contactFormUseEmailOfSender']) ? 1 : 0;

            // do the fields
            $fields = $this->_getFormFieldsFromPost();

            if ($saveDataInCrm) {
                $mandatory_crm_fields = array('access_firstname', 'access_lastname', 'access_email');

                foreach ($mandatory_crm_fields as $crm_field) {
                    $field_found = false;
                    foreach ($fields as $field_key => $field) {
                        if ($field['special_type'] == $crm_field) {
                            $field_found = true;
                            break;
                        }
                    }
                    if (!$field_found) {
                        $this->_statusMessageErr .= $_ARRAYLANG['TXT_CONTACT_FORM_CRM_FIELDS_MISSING'];
                        $this->_modifyForm();
                        return;
                    }
                }

                $crmCustomerGroups = !empty($_POST['assigned_memberships']) ? $_POST['assigned_memberships'] : array();
            }

            if (!$adding) {
                // This updates the database
                $this->updateForm(
                        $formId,
                        $emails,
                        $showForm,
                        $useCaptcha,
                        $useCustomStyle,
                        $sendCopy,
                        $useEmailOfSender,
                        $sendHtmlMail,
                        $sendAttachment,
                        $saveDataInCrm,
                        $crmCustomerGroups,
                        $sendMultipleReply
                );
            } else {
                $formId = $this->addForm(
                        $emails,
                        $showForm,
                        $useCaptcha,
                        $useCustomStyle,
                        $sendCopy,
                        $useEmailOfSender,
                        $sendHtmlMail,
                        $sendAttachment,
                        $saveDataInCrm,
                        $crmCustomerGroups,
                        $sendMultipleReply
                );
            }

            foreach (\FWLanguage::getActiveFrontendLanguages() as $lang) {
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
                        ? contrexx_input2raw($_POST['contactFormText'][$langID])
                        : '';

                $formFeedback =
                        isset($_POST['contactFormFeedback'][$langID])
                        ? contrexx_input2raw($_POST['contactFormFeedback'][$langID])
                        : '';

                $formMailTemplate =
                        isset($_POST['contactMailTemplate'][$langID])
                        ? preg_replace('/\[\[([A-Z0-9_]*?)\]\]/', '{\\1}', contrexx_input2raw($_POST['contactMailTemplate'][$langID]))
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

            $formFieldIDs = array();
            foreach ($fields as $field) {
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
            $recipientIDs = array();
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

            \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Contact');
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
     * @author      Cloudrexx AG <info@cloudrexx.com>
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
     * @author      Cloudrexx AG <info@cloudrexx.com>
     */
    private function _deleteForm()
    {
        global $_ARRAYLANG;

        if (isset($_GET['formId'])) {
            $formId = intval($_GET['formId']);

            if ($formId > 0) {
                if ($this->deleteForm($formId)) {
                    $this->_statusMessageOk = $_ARRAYLANG['TXT_CONTACT_CONTACT_FORM_SUCCESSFULLY_DELETED'];

                    \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->deleteComponentFiles('Contact');

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
     * @todo implement
     */
    function _deleteContentSite($formId)
    {
        \Permission::checkAccess(26, 'static');

        $formId = intval($_REQUEST['formId']);
        try {
            $pageRepo = $this->em->getRepository('\Cx\Core\ContentManager\Model\Entity\Page');
            $pages = $pageRepo->findBy(array('module' => 'Contact', 'cmd' => $formId, 'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION));

            $nodes = array();
            foreach($pages as $page) {
                $this->em->remove($page);
                $nodes[] = $page->getNode();
            }
            $this->em->flush();

            // delete empty nodes
            foreach ($nodes as $node) {
                $this->em->refresh($node);
                if (count($node->getPages()) == 0) {
                    $this->em->remove($node);
                }
            }
            $this->em->flush();

            // The following line is a hacky work-around for translation
            // caching problem
            // See http://atlantic18.github.io/DoctrineExtensions/doc/translatable.html
            // 2017-06-19, MRi
            $this->em->getConfiguration()->getResultCacheImpl()->deleteAll();
        } catch(\Exception $e) {}
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
                'multi_file',
                'textarea',
                'hidden',
                'radio',
                'checkboxGroup',
                'password',
                'select',
                'special'
        );

        // shorten the variables
        $fieldNames      = isset($_POST['contactFormFieldName']) ? $_POST['contactFormFieldName'] : array();
        $fieldValues     = isset($_POST['contactFormFieldValue']) ? $_POST['contactFormFieldValue'] : array();
        $fieldTypes      = isset($_POST['contactFormFieldType']) ? $_POST['contactFormFieldType'] : array();
        $fieldRequireds  = isset($_POST['contactFormFieldRequired']) ? $_POST['contactFormFieldRequired'] : array();
        $fieldCheckTypes = isset($_POST['contactFormFieldCheckType']) ? $_POST['contactFormFieldCheckType'] : array();
        $fieldEditType   = isset($_POST['contactFormFieldEditType']) ? $_POST['contactFormFieldEditType'] : array();

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
                'editType'      => $editType,
                'lang'          => array(),
            );
            $orderId++;

            $arrActiveSystemFrontendLanguageIds = array_keys(\FWLanguage::getActiveFrontendLanguages());
            foreach ($arrActiveSystemFrontendLanguageIds as $langId) {
                if (!empty($_POST['contactFormLanguages'][$langId])) {
                    switch ($fieldType) {
                        case 'checkbox':
                        case 'country':
                        case 'access_country':
                            $fieldValue = $fieldValues[$id];
                            break;

                        //case 'label':
                        case 'date':
                        case 'datetime':
                        case 'file':
                        case 'multi_file':
                        case 'fieldset':
                        case 'horizontalLine':
                        case 'password':
                        case 'textarea':
                        case 'recipient':
                            $fieldValue = '';
                            break;

                        default:
                            $fieldValue = !empty($fieldValues[$id][$langId]) ? contrexx_input2raw($fieldValues[$id][$langId]) : '';
                            break;
                    }

                    $arrFields[$id]['lang'][$langId] = array(
                        'name'  => contrexx_input2raw($fieldNames[$id][$langId]),
                        'value' => $fieldValue,
                    );
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
        $mails      = contrexx_input2raw($_POST['contactFormRecipientEmail']);
        $names      = contrexx_input2raw($_POST['contactFormRecipientName']);
        $editTypes  = contrexx_input2raw($_POST['contactFormRecipientEditType']);
        $recipients = array();

        if (count($mails) == 0) {
            return $recipients;
        }

        $sortCounter = 0;
        foreach ($mails as $key => $mail) {
            $mail = trim($mail);

            if (empty($mail)) {
                continue;
            }

            $recipients[$key] = array(
                'id'       => $key,
                'email'    => $mail,
                'sort'     => $sortCounter++,
                'editType' => $editTypes[$key],
                'lang'     => array(),
            );

            foreach (array_keys(\FWLanguage::getActiveFrontendLanguages()) as $langId) {
                $name = isset($names[$key][$langId])
                            ? $names[$key][$langId]
                            : '';
                $recipients[$key]['lang'][$langId] = $name;
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
            $menu .= "<option value=\"".$type."\"".($selectedType == $type ? 'selected="selected"' : '')."  style=\"padding-left:10px;\">&nbsp;&nbsp;".$desc."</option>\n";
        }
        $menu .= "<option disabled=\"disabled\" style=\"color:#000;font-weight:bold;\">".$_ARRAYLANG['TXT_CONTACT_USER_DATA']."</option>\n";
        foreach ($this->_arrUserAccountData as $type => $desc) {
            $menu .= "<option value=\"".$type."\"".($selectedType == $type ? 'selected="selected"' : '')."  style=\"padding-left:10px;\">&nbsp;&nbsp;".$desc."</option>\n";
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
        case 'access_title':
        case 'access_gender':
        case 'access_country':
        case 'checkbox':
        case 'checkboxGroup':
        case 'country':
        case 'date':
        case 'datetime':
        case 'fieldset':
        case 'hidden':
        case 'radio':
        case 'select':
        case 'label':
        case 'recipient':
        case 'horizontalLine':
        case 'file':
        case 'multi_file':
            $menu = '';
            break;

        case 'text':
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
            } elseif (isset($this->arrForms[$formId]['lang'][\FWLanguage::getDefaultLangId()])) {
                $selectedInterfaceLanguage = \FWLanguage::getDefaultLangId();
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

            $pageRepo = $this->em->getRepository('\Cx\Core\ContentManager\Model\Entity\Page');
            $page = $pageRepo->findOneBy(array(
                'module' => 'Contact',
                'cmd' => $formId,
                'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION,
                'lang' => $selectedInterfaceLanguage,
            ));
            $contentSiteExists = $page !== null;

            $formRepo = $this->em->getRepository('Cx\Core_Modules\Contact\Model\Entity\Form');
            $form     = $formRepo->find($formId);
            $formTemplate = new \Cx\Core_Modules\Contact\Model\Entity\FormTemplate(
                $form,
                $page,
                null
            );
            $sourceCode = $formTemplate->getHtml(true);
            $formTemplate->parseFormTemplate();

            $this->_objTpl->setVariable(array(
                'CONTACT_CONTENT_SITE_ACTION_TXT'   => $contentSiteExists > 0 ? $_ARRAYLANG['TXT_CONTACT_UPDATE_CONTENT_SITE'] : $_ARRAYLANG['TXT_CONTACT_NEW_PAGE'],
                'CONTACT_CONTENT_SITE_ACTION'       => $contentSiteExists > 0 ? 'updateContent' : 'newContent',
                'CONTACT_SOURCECODE_OF'             => str_replace('%NAME%', contrexx_raw2xhtml($this->arrForms[$formId]['lang'][$selectedInterfaceLanguage]['name']), $_ARRAYLANG['TXT_CONTACT_SOURCECODE_OF_NAME']),
                'CONTACT_PREVIEW_OF'                => str_replace('%NAME%', contrexx_raw2xhtml($this->arrForms[$formId]['lang'][$selectedInterfaceLanguage]['name']), $_ARRAYLANG['TXT_CONTACT_PREVIEW_OF_NAME']),
                'CONTACT_FORM_SOURCECODE'           => contrexx_raw2xhtml($sourceCode),
                'CONTACT_FORM_PREVIEW'              => $formTemplate->getHtml(),
                'FORM_ID'                           => $formId
            ));
        } else {
            $this->_contactForms();
        }
    }

    function _getEntryDetails($arrEntry, $formId)
    {
        global $_ARRAYLANG;

        $arrFormFields = $this->getFormFields($formId);
        $recipient     = $this->getRecipients($formId);
        $rowNr         = 0;
        $langId        = $arrEntry['langId'];

        $sourcecode = '<table border="0" class="adminlist" cellpadding="3" cellspacing="0" width="100%">';

        foreach ($arrEntry['data'] as $fieldId => $arrData) {

            if (isset($arrFormFields[$fieldId])) {
                //fieldset and horizontal field type need not be displayed in the detail page
                if (!in_array($arrFormFields[$fieldId]['type'], $this->nonValueFormFieldTypes)) {
                    $label = contrexx_raw2xhtml($arrFormFields[$fieldId]['lang'][FRONTEND_LANG_ID]['name']).($arrFormFields[$fieldId]['type'] == 'hidden' ? ' (hidden)' : '');

                    switch ($arrFormFields[$fieldId]['type']) {
                        case 'checkbox':
                            $value = !empty($arrData['value']) ? ' '.$_ARRAYLANG['TXT_CONTACT_YES'] : ' '.$_ARRAYLANG['TXT_CONTACT_NO'];
                            break;
                        case 'file':
                        case 'multi_file':
                            $arrFiles  = explode('*', $arrData['value']);
                            $value = '';
                            foreach ($arrFiles as $file) {
                                $file = contrexx_raw2xhtml($file);
                                $img  = $this->getFileIcon($file);
                                $value .= '<a href="'.\Env::get('cx')->getWebsiteOffsetPath().$file.'" target="_blank" onclick="return confirm(\''.str_replace("\n", '\n', $_ARRAYLANG['TXT_CONTACT_CONFIRM_OPEN_UPLOADED_FILE']).'\')">'.$img.basename($file).'</a><br />';
                            }
                            break;
                        case 'recipient':
                            $value = isset($recipient[$arrData['value']]['lang'][$langId]) ? htmlentities($recipient[$arrData['value']]['lang'][$langId], ENT_QUOTES, CONTREXX_CHARSET) : '&nbsp;';
                            break;
                        case 'text':
                        case 'checkboxGroup':
                        case 'country':
                        case 'date':
                        case 'datetime':
                        case 'hidden':
                        case 'password':
                        case 'radio':
                        case 'select':
                        case 'textarea':
                        case 'special':
                            $value = nl2br(htmlentities($arrData['value'], ENT_QUOTES, CONTREXX_CHARSET));
                            break;
                    }
                }
            } else {
                $label = $arrData['label'];
                $value = $arrData['value'];
            }

            $sourcecode .= '
                <tr class='.($rowNr % 2 == 0 ? 'row1' : 'row2').'>
                    <td style="vertical-align: top;" width="15%"><strong>'.$label.'</strong></td>
                    <td width="85%">'.$value.'</td>
                </tr>
            ';

            $rowNr++;
        }

        $sourcecode .= '</table>';

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
            \Cx\Core\Csrf\Controller\Csrf::header("Location: index.php?cmd=Contact");
            return;
        }

        if (isset($_GET['format']) && isset($csvFormat[$_GET['format']])) {
            $format = $_GET['format'];
        }

        if (isset($this->arrForms[$formId]['lang'][FRONTEND_LANG_ID])) {
            $selectedInterfaceLanguage = FRONTEND_LANG_ID;
        } elseif (isset($this->arrForms[$formId]['lang'][\FWLanguage::getDefaultLangId()])) {
            $selectedInterfaceLanguage = \FWLanguage::getDefaultLangId();
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
                .($arrSettings['fieldMetaBrowser'] == '1' ? $this->_escapeCsvValue($_ARRAYLANG['TXT_CONTACT_BROWSER']).$this->_csvSeparator : '')
                .($arrSettings['fieldMetaIP'] == '1' ? $this->_escapeCsvValue($_ARRAYLANG['TXT_CONTACT_IP_ADDRESS']) : '')
                .$this->_csvLFB;

        $query    = "SELECT `id` FROM ".DBPREFIX."module_contact_form_data WHERE id_form=".$formId." ORDER BY `time` DESC";
        $objEntry = $objDatabase->Execute($query);
        if ($objEntry !== false) {
            while (!$objEntry->EOF) {
                $formEntriesValues = $this->getFormEntry($objEntry->fields['id']);

                foreach ($arrFormFields as $fieldId => $value) {

                    if (!in_array($arrFormFields[$fieldId]['type'], $this->nonValueFormFieldTypes)) {
                        if (!empty ($formEntriesValues['data'][$fieldId]['value'])) {
                            switch ($arrFormFields[$fieldId]['type']) {
                            case 'checkbox':
                                print $this->_escapeCsvValue(isset($formEntriesValues['data'][$fieldId]['value']) && $formEntriesValues['data'][$fieldId]['value'] ? ' '.$_ARRAYLANG['TXT_CONTACT_YES'] : ' '.$_ARRAYLANG['TXT_CONTACT_NO']);
                                break;

                            case 'file':
                            case 'multi_file':
                                print $this->_escapeCsvValue(isset($formEntriesValues['data'][$fieldId]['value']) ? ASCMS_PROTOCOL.'://'.$_CONFIG['domainUrl'].\Env::get('cx')->getWebsiteOffsetPath().$formEntriesValues['data'][$fieldId]['value'] : '');
                                break;

                            case 'text':
                            case 'checkboxGroup':
                            case 'hidden':
                            case 'password':
                            case 'radio':
                            case 'select':
                            case 'textarea':
                            default:
                                print isset($formEntriesValues['data'][$fieldId]['value']) ? $this->_escapeCsvValue($formEntriesValues['data'][$fieldId]['value']) : '';
                                break;
                            }
                        }
                        print $this->_csvSeparator;
                    }
                }

                print ($arrSettings['fieldMetaDate'] == '1' ? $this->_escapeCsvValue(date(ASCMS_DATE_FORMAT, $formEntriesValues['time'])).$this->_csvSeparator : '')
                        .($arrSettings['fieldMetaHost'] == '1' ? $this->_escapeCsvValue($formEntriesValues['host']).$this->_csvSeparator : '')
                        .($arrSettings['fieldMetaLang'] == '1' ? $this->_escapeCsvValue($formEntriesValues['langId']).$this->_csvSeparator : '')
                        .($arrSettings['fieldMetaBrowser'] == '1' ? $this->_escapeCsvValue($formEntriesValues['browser']).$this->_csvSeparator : '')
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

        \Permission::checkAccess(5, 'static');

        $formId = intval($_REQUEST['formId']);;
        $this->_handleContentPage($formId);
    }

    function _updateContentSite()
    {
        global $_ARRAYLANG;

        \Permission::checkAccess(35, 'static');

        $formId = intval($_REQUEST['formId']);;
        $this->_handleContentPage($formId);
    }

    function _handleContentPage($formId = 0)
    {
        if (!$formId) {
            return;
        }

        $objDatabase = \Env::get('db');
        $objFWUser   = \FWUser::getFWUserObject();
        $pageRepo    = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

        $pages = array();
        foreach ($pageRepo->findBy(array('module' => 'Contact', 'cmd' => $formId)) as $page) {
            if ($page) {
                $pages[$page->getLang()] = $page;
            }
        }

        $frontendLangIds = array_keys(\FWLanguage::getActiveFrontendLanguages());
        $selectedLangIds = array_keys($this->arrForms[$formId]['lang']);

        // filter out only those languages that are active in the frontent.
        // we do want to create/update only the content pages of those languages.
        $selectedLangIds = array_intersect($selectedLangIds, $frontendLangIds);

        // Maybe there will already be a fallback page if we activate a new language.
        // So we use the fallback page to prevent the creation of a new page.
        foreach ($selectedLangIds as $selectedLangId) {
            if (!isset($pages[$selectedLangId])) {
                $fallbackPage = $pageRepo->findOneByModuleCmdLang('Contact', $formId, $selectedLangId);
                if (!empty($fallbackPage)) {
                    $pages[$selectedLangId] = $fallbackPage;
                }
            }
        }

        $presentLangIds = array_keys($pages);

        // define which languages of the content pages have to be updated
//        $updateLangIds = array_intersect($selectedLangIds, $presentLangIds);

        // define which languages of the content pages have to be created
//        $newLangIds = array_diff($selectedLangIds, $updateLangIds);

        // define which languages of the content pages have to be removed
        $deleteLangIds = array_diff($presentLangIds, $selectedLangIds);

        $langIdsOfAssociatedPagesOfPageNode = array();
        foreach ($presentLangIds as $langId) {
            $langIdsOfAssociatedPagesOfPageNode[$pages[$langId]->getId()] = array_keys($pages[$langId]->getNode()->getPagesByLang());
        }

        foreach ($selectedLangIds as $langId) {
            $page = null;
            $node = null;

            if (isset($pages[$langId])) {
                // Content page already exists, so we will update this very page
                $page = $pages[$langId];
                \DBG::msg("Page in lang $langId exists -> Update()");
            } else {
                // Content page of the frontend language $langId doesn't exist yet.
                // Therefore we will either have to create a new node or we will have to
                // find a node to which we can attach our new page.
                // We will prefer the latter.
                \DBG::msg("Page doesn't exist in lang $langId -> Create()");

                // Check if there exists already a content page to who's node we could attach our new page
                if (count($pages)) {
                    if (   // In case the language in which we are going to create a new page isn't the default frontend language,
                           $langId != \FWLanguage::getDefaultLangId()
                           // check if there exists a content page in the default language,
                        && isset($pages[\FWLanguage::getDefaultLangId()])
                           // if so, check if its node doesn't have a page in the specific language yet
                        && !in_array($langId, $langIdsOfAssociatedPagesOfPageNode[$pages[\FWLanguage::getDefaultLangId()]->getId()])
                    ) {
                        // if that is the case, we will attach our new page to this node
                        $node = $pages[\FWLanguage::getDefaultLangId()]->getNode();
                        \DBG::msg("Page does exists in the default language");
                        \DBG::msg("Attach page to node {$node->getId()} of the page of the lang ".\FWLanguage::getDefaultLangId());
                    } else {
                        foreach ($pages as $langIdOfPage => $page) {
                            // Skip the page of the default frontend language - we just checked this page's node before
                            if ($langIdOfPage == \FWLanguage::getDefaultLangId()) {
                                continue;
                            }

                            // Check if there exists a node of the pages in the other languages that don't
                            // have an associated page in the language we're going to create a new page
                            if (!in_array($langId, $langIdsOfAssociatedPagesOfPageNode[$pages[$langIdOfPage]->getId()])) {
                                $node = $pages[$langIdOfPage]->getNode();
                                \DBG::msg("Attach page to node {$node->getId()} of the page of the lang $langIdOfPage");
                                break;
                            }
                        }
                    }
                }

                if ($node === null) {
                    \DBG::msg("No node found -> Create()");
                    $root = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Node')->getRoot();

                    // Create a new node
                    $node = new \Cx\Core\ContentManager\Model\Entity\Node();
                    $node->setParent($root);
                    $this->em->persist($node);
                }

                // Create a new Page
                $page = new \Cx\Core\ContentManager\Model\Entity\Page();
                $page->setNode($node);

                // Set the following attributes only on new pages
                $page->setTitle($this->arrForms[$formId]['lang'][$langId]['name']);
                $page->setDisplay(false);
                $page->setActive(true);
                $page->setLang($langId);
            }

            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
            $page->setModule('Contact');
            $page->setCmd($formId);

            $content = $page->getContent();
            //if the content is missing the placeholder {APPLICATION_DATA}, append the placeholder to the page's content.
            if (!preg_match_all('/\{APPLICATION_DATA\}/xi', $content, $matches)) {
                $content .= '{APPLICATION_DATA}';
            }
            $page->setContent($content);
            $page->setSourceMode(true);

            $this->em->persist($page);

            // Remember newly created pages. We will need this for the creating of other new pages above in this method.
            if (!isset($pages[$langId])) {
                $pages[$langId] = $page;
                $langIdsOfAssociatedPagesOfPageNode[$pages[$langId]->getId()] = array($langId);
            }
        }
        //$this->em->flush();

        // Delete those content pages of those languages that had been deactivated
        foreach ($deleteLangIds as $langId) {
            $page = $pages[$langId];
            $node = $page->getNode();

            \DBG::msg("Delete page: $langId");
            //DBG::dump(count($node->getPages()));
            $this->em->remove($page);
            //$this->em->persist($page);
            //$this->em->flush();
            //DBG::dump(count($node->getPages()));
// TODO: Delete empty nodes
        }

        $this->em->flush();

        // The following line is a hacky work-around for translation
        // caching problem
        // See http://atlantic18.github.io/DoctrineExtensions/doc/translatable.html
        // 2017-06-19, MRi
        $this->em->getConfiguration()->getResultCacheImpl()->deleteAll();
    }


    /**
     * Returns the appropriate icon of the given file.
     *
     * @param   string  $file
     * @return  string  $img
     */
    private function getFileIcon($file)
    {
        $icon = \Cx\Core_Modules\Media\Controller\MediaLibrary::_getIcon(\Env::get('cx')->getWebsiteDocumentRootPath().$file);

        $img = '<img src="'.\Cx\Core_Modules\Media\Controller\MediaLibrary::_getIconWebPath().$icon.'.png" alt="Attach" border="0" style="position: relative; top: 3px;" />&nbsp;';
        return $img;
    }
}
