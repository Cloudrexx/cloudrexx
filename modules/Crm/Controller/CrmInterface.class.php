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
 * This is the crmInterface class file for handling the all functionalities under interface menu.
 *
 * PHP version 5.3 or >
 *
 * @category   CrmInterface
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     ss4ugroup <ss4ugroup@softsolutions4u.com>
 * @license    BSD Licence
 * @version    1.0.0
 * @link       www.cloudrexx.com
 */

namespace Cx\Modules\Crm\Controller;

/**
 * This is the crmInterface class file for handling the all functionalities under interface menu.
 *
 * @category   CrmInterface
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     ss4ugroup <ss4ugroup@softsolutions4u.com>
 * @license    BSD Licence
 * @version    1.0.0
 * @link       www.cloudrexx.com
 */

class CrmInterface extends CrmLibrary
{
    /**
     * constant MiB2 2megabytes
     */
    const MiB2 = 2097152;

    /**
     * delimiter
     *
     * @access private
     * @var array
     */
    private $_delimiter = array(
                            array(
                                'title'=>'Semicolon',
                                'value' => ';',
                                'placeholder' => 'TXT_CRM_SEMICOLON'
                            ),
                            array(
                                'title'=>'Comma',
                                'value' => ',',
                                'placeholder' => 'TXT_CRM_COMMA'
                            ),
                            array(
                                'title'=>'Colon',
                                'value' => ':',
                                'placeholder' => 'TXT_CRM_COLON'
                            ),
                          );
    /**
     * enclosure
     *
     * @access private
     * @var array
     */
    private $_enclosure = array(
                            array(
                                'title'=>'Double quote',
                                'value' => '"',
                                'placeholder' => 'TXT_CRM_DOUBLE_QUOTE'
                            ),
                            array(
                                'title'=>'Single quote',
                                'value' => "'",
                                'placeholder' => 'TXT_CRM_SINGLE_QUOTE'
                            ),
                          );
    /**
     * media path
     *
     * @access private
     * @var string
     */
    private $_mediaPath = '';

    /**
     * Template object
     *
     * @access private
     * @var object
     */
    public $_objTpl;

    /**
     * @var integer
     */
    protected $memoryLimit;

    /**
     * php 5.3 contructor
     *
     * @param object $objTpl template object
     */
    function __construct($objTpl, $name)
    {
        $this->_objTpl = $objTpl;
        $this->_mediaPath = \Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteMediaCrmPath();
        parent::__construct($name);
    }

    /**
     * It displayes the import menu
     *
     * @return customer import screen
     */
    function showImport()
    {
        global $_ARRAYLANG, $objDatabase;

        \JS::activate('cx');
        \JS::activate('jqueryui');
        \JS::registerCSS('modules/Crm/View/Style/main.css');
        \JS::registerJS('modules/Crm/View/Script/contactexport.js');
        \JS::registerJS('lib/javascript/jquery.form.js');
        \JS::registerJS('lib/javascript/jquery.tmpl.min.js');
        \JS::registerJS('lib/javascript/jquery.base64.js');
        \JS::registerJS('lib/javascript/jquery.format.js');

        $objTpl = $this->_objTpl;

        $objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', "module_{$this->moduleNameLC}_interface_import_options.html");
        $objTpl->setGlobalVariable(array('MODULE_NAME' => $this->moduleName));

        foreach ($this->_delimiter as $key => $value) {
            $objTpl->setVariable(array(
                'CRM_DELIMITER_VALUE' => $key,
                'CRM_DELIMITER_TITLE' => $_ARRAYLANG[$value['placeholder']]
            ));
            $objTpl->parse('crm_delimiter');
        }
        foreach ($this->_enclosure as $key => $value) {
            $objTpl->setVariable(array(
                'CRM_ENCLOSURE_VALUE' => $key,
                'CRM_ENCLOSURE_TITLE' => $_ARRAYLANG[$value['placeholder']]
            ));
            $objTpl->parse('crm_enclosure');
        }
        $options = array('upload-limit' => 1, 'id' => 'importUploader', 'style' => 'display:none;', 'allowed-extensions' => array('csv'));
        $uploaderCode = $this->initUploader('uploadFinished', 'importCallbackJs', '', '', $options);
        $redirectUrl = \Cx\Core\Csrf\Controller\Csrf::enhanceURI('index.php?cmd=Crm&act=getImportFilename');
        $this->_objTpl->setVariable(array(
            'COMBO_UPLOADER_CODE' => $uploaderCode,
        'REDIRECT_URL'        => $redirectUrl
        ));

        $objTpl->setVariable(array(
            'TXT_CRM_TITLE_IMPORT_CONTACTS'         => $_ARRAYLANG['TXT_CRM_TITLE_IMPORT_CONTACTS'],
            'TXT_CRM_IMPORT_HEADER'                 => $_ARRAYLANG['TXT_CRM_IMPORT_HEADER'],
            'TXT_CRM_IMPORT_NOTE'                   => $_ARRAYLANG['TXT_CRM_IMPORT_NOTE'],
            'TXT_CRM_IMPORT_NOTE_DESCRIPTION'       => $_ARRAYLANG['TXT_CRM_IMPORT_NOTE_DESCRIPTION'],
            'TXT_CRM_CSV_SETTINGS'                  => $_ARRAYLANG['TXT_CRM_CSV_SETTINGS'],
            'TXT_CRM_SKIP'                          => $_ARRAYLANG['TXT_CRM_SKIP'],
            'TXT_CRM_OVERWRITE'                     => $_ARRAYLANG['TXT_CRM_OVERWRITE'],
            'TXT_CRM_DUPLICATE'                     => $_ARRAYLANG['TXT_CRM_DUPLICATE'],
            'TXT_CRM_CHOOSE_FILE'                   => $_ARRAYLANG['TXT_CRM_CHOOSE_FILE'],
            'TXT_CRM_CSV_SEPARATOR'                 => $_ARRAYLANG['TXT_CRM_CSV_SEPARATOR'],
            'TXT_CRM_CSV_ENCLOSURE'                 => $_ARRAYLANG['TXT_CRM_CSV_ENCLOSURE'],
            'TXT_CRM_ON_DUPLICATES'                 => $_ARRAYLANG['TXT_CRM_ON_DUPLICATES'],
            'TXT_CRM_CHOOSE_CSV'                    => $_ARRAYLANG['TXT_CRM_CHOOSE_CSV'],
            'TXT_CRM_ON_DUPLICATES_INFO'            => $_ARRAYLANG['TXT_CRM_ON_DUPLICATES_INFO'],
            'TXT_CRM_ON_DUPLICATE_SKIP_INFO'        => $_ARRAYLANG['TXT_CRM_ON_DUPLICATE_SKIP_INFO'],
            'TXT_CRM_ON_DUPLICATE_OVERWRITE_INFO'   => $_ARRAYLANG['TXT_CRM_ON_DUPLICATE_OVERWRITE_INFO'],
            'TXT_CRM_ON_DUPLICATE_INFO'             => $_ARRAYLANG['TXT_CRM_ON_DUPLICATE_INFO'],
            'TXT_CRM_IGNORE_FIRST_ROW'              => $_ARRAYLANG['TXT_CRM_IGNORE_FIRST_ROW'],
            'TXT_CRM_CONTINUE'                      => $_ARRAYLANG['TXT_CRM_CONTINUE'],
            'TXT_CRM_CANCEL'                        => $_ARRAYLANG['TXT_CRM_CANCEL'],
            'TXT_CRM_VERIFY_FIELDS'                 => $_ARRAYLANG['TXT_CRM_VERIFY_FIELDS'],
            'TXT_CRM_VERIFY_INFO'                   => $_ARRAYLANG['TXT_CRM_VERIFY_INFO'],
            'TXT_CRM_FILE_COLUMN'                   => $_ARRAYLANG['TXT_CRM_FILE_COLUMN'],
            'TXT_CRM_CORRESPONDING_FIELD'           => $_ARRAYLANG['TXT_CRM_CORRESPONDING_FIELD'],
            'TXT_CRM_CSV_VALUE'                     => $_ARRAYLANG['TXT_CRM_CSV_VALUE'],
            'TXT_CRM_CHANGE'                        => $_ARRAYLANG['TXT_CRM_CHANGE'],
            'TXT_CRM_LOADING'                       => $_ARRAYLANG['TXT_CRM_LOADING'],
            'TXT_CRM_PREVIOUS_RECORD'               => $_ARRAYLANG['TXT_CRM_PREVIOUS_RECORD'],
            'TXT_CRM_NEXT_RECORD'                   => $_ARRAYLANG['TXT_CRM_NEXT_RECORD'],
            'TXT_CRM_TITLE_SAVING_CONTACTS'         => $_ARRAYLANG['TXT_CRM_TITLE_SAVING_CONTACTS'],
            'TXT_CRM_INTERFACE_FINAL_INFO'          => $_ARRAYLANG['TXT_CRM_INTERFACE_FINAL_INFO'],

            'TXT_CRM_RECORD_DONE'                   => $_ARRAYLANG['TXT_CRM_RECORD_DONE'],
            'TXT_CRM_RECORD_SKIPPED'                => $_ARRAYLANG['TXT_CRM_RECORD_SKIPPED'],
            'TXT_CRM_RECORD_IMPORT'                 => $_ARRAYLANG['TXT_CRM_RECORD_IMPORT'],
            'TXT_CRM_RECORD_PROCESS'                => $_ARRAYLANG['TXT_CRM_RECORD_PROCESS'],
            'TXT_CRM_IMPORT_NAME'                   => $_ARRAYLANG['TXT_CRM_IMPORT_NAME'],
            'TXT_CRM_EXPORT_NAME'                   => $_ARRAYLANG['TXT_CRM_EXPORT_NAME']
        ));
    }

    /**
     * used to fetch the csv data
     *
     * @return csvdata
     */
    function csvImport()
    {
        global $_ARRAYLANG, $objDatabase;

        $json = array();

        $csvSeprator    = isset ($_POST['csv_delimiter']) && in_array($_POST['csv_delimiter'], array_keys($this->_delimiter)) ? $this->_delimiter[$_POST['csv_delimiter']]['value'] : $this->_delimiter[0]['value'];
        $csvDelimiter   = isset ($_POST['csv_enclosure']) && in_array($_POST['csv_enclosure'], array_keys($this->_enclosure)) ? $this->_enclosure[$_POST['csv_enclosure']]['value'] : $this->_enclosure[0]['value'];
        $csvIgnoreFirst = isset ($_POST['ignore_first']) && contrexx_input2int($_POST['ignore_first']);
        $fileName       = isset ($_POST['fileName']) ? \FWValidator::getCleanFileName(contrexx_input2raw($_POST['fileName'])) : '';

        if (!empty ($fileName)) {
            $json['fileUri']     = $fileName;
            $rowIndex            = 1;
            $importedLines       = 0;
            $first               = true;
            $objCsv              = new CrmCsv(
                $this->_mediaPath.'/'.$fileName, $csvSeprator, $csvDelimiter
            );
            $line                = $objCsv->NextLine();
            $json['data']        = array();
            $json['contactData'] = array();
            while ($line) {
                if ($first) {
                    $json['data']['contactHeader'] = $line;
                    $first = false;
                }
                if ($importedLines == $rowIndex) {
                    $json['data']['contactFields'] = $line;
                    $json['contactData'][$importedLines] = $line;
                }

                ++$importedLines;
                $line = $objCsv->NextLine();
            }
            $json['data']       = base64_encode(json_encode($json['data']));
            $json['contactData']= base64_encode(json_encode($json['contactData']));
            $json['totalRows']  = $importedLines - 1;
        } else {
            $json['error'] = 'Error in file';
        }

        echo json_encode($json);
        exit();
    }

    /**
     * Get the CSV Record
     *
     * @return null
     */
    function getCsvRecord()
    {
        global $_ARRAYLANG, $objDatabase;

        $json = array();

        $csvSeprator    = isset ($_POST['csv_delimiter']) && in_array($_POST['csv_delimiter'], array_keys($this->_delimiter)) ? $this->_delimiter[$_POST['csv_delimiter']]['value'] : $this->_delimiter[0]['value'];
        $csvDelimiter   = isset ($_POST['csv_enclosure']) && in_array($_POST['csv_enclosure'], array_keys($this->_enclosure)) ? $this->_enclosure[$_POST['csv_enclosure']]['value'] : $this->_enclosure[0]['value'];
        $fileName       = isset ($_POST['fileUri']) ? \FWValidator::getCleanFileName(contrexx_input2raw($_POST['fileUri'])) : '';
        $currentRow     = isset ($_GET['currentRow']) ? contrexx_input2int($_GET['currentRow']) : '';

        $importedLines = 0;
        $objCsv        = new CrmCsv($this->_mediaPath.'/'.$fileName, $csvSeprator, $csvDelimiter);
        $line          = $objCsv->NextLine();
        $json['contactData'] = array();
        while ($line) {
            if ($importedLines == $currentRow) {
                $json['contactData'][$importedLines] = $line;
                break;
            }
            ++$importedLines;
            $line = $objCsv->NextLine();
        }
        $json['contactData']= base64_encode(json_encode($json['contactData']));
        echo json_encode($json);
        exit();
    }

    /**
     * It displayes the import menu
     *
     * @return customer import screen
     */
    function showExport()
    {
        global $_ARRAYLANG, $objDatabase;

        $objTpl = $this->_objTpl;

        $objTpl->addBlockfile('CRM_SETTINGS_FILE', 'settings_block', "module_{$this->moduleNameLC}_interface_export_options.html");
        $objTpl->setGlobalVariable(array('MODULE_NAME' => $this->moduleName));

        $objTpl->setVariable(array(
            'TXT_CRM_EXPORT_INFO'         => $_ARRAYLANG['TXT_CRM_EXPORT_INFO'],
            'TXT_CRM_FUNCTIONS'           => $_ARRAYLANG['TXT_CRM_FUNCTIONS'],
            'TXT_CRM_EXPORT_CUSTOMER_CSV' => $_ARRAYLANG['TXT_CRM_EXPORT_CUSTOMER_CSV'],
            'TXT_CRM_EXPORT_COMPANY'  => $_ARRAYLANG['TXT_CRM_EXPORT_COMPANY'],
            'TXT_CRM_EXPORT_PERSON'   => $_ARRAYLANG['TXT_CRM_EXPORT_PERSON'],
            'TXT_CRM_EXPORT_ACTIVE_CUSTOMER' => $_ARRAYLANG['TXT_CRM_EXPORT_ACTIVE_CUSTOMER'],

            'TXT_CRM_IMPORT_NAME'         => $_ARRAYLANG['TXT_CRM_IMPORT_NAME'],
            'TXT_CRM_EXPORT_NAME'         => $_ARRAYLANG['TXT_CRM_EXPORT_NAME']
        ));
    }

    /**
     * Export all contacts in csv format
     *
     * @global array $_ARRAYLANG
     * @global object $objDatabase
     * @global integer $_LANGID
     *
     * @return all contacts in csv format
     */
    function csvExport()
    {
        global $_ARRAYLANG,$objDatabase, $_LANGID;

        $alphaFilter = isset($_REQUEST['companyname_filter']) ? contrexx_input2raw($_REQUEST['companyname_filter']) : '';
        if (!empty($alphaFilter)) {
            $where[] = " (c.customer_name LIKE '".contrexx_input2raw($alphaFilter)."%')";
        }
        $searchContactTypeFilter = isset($_GET['contactSearch']) ? (array) $_GET['contactSearch'] : array(1,2);
        $searchContactTypeFilter = array_map('intval', array_unique($searchContactTypeFilter));
        $where[] = " c.contact_type IN (".implode(',', $searchContactTypeFilter).")";

        if (isset($_GET['s_name']) && !empty($_GET['s_name'])) {
            $where[] = " (c.customer_name LIKE '".contrexx_input2db($_GET['s_name'])."%' OR c.contact_familyname LIKE '".contrexx_input2db($_GET['s_name'])."%')";
        }
        if (isset($_GET['s_email']) && !empty($_GET['s_email'])) {
            $where[] = " (email.email LIKE '".contrexx_input2db($_GET['s_email'])."%')";
        }
        if (isset($_GET['s_address']) && !empty($_GET['s_address'])) {
            $where[] = " (addr.address LIKE '".contrexx_input2db($_GET['s_address'])."%')";
        }
        if (isset($_GET['s_city']) && !empty($_GET['s_city'])) {
            $where[] = " (addr.city LIKE '".contrexx_input2db($_GET['s_city'])."%')";
        }
        if (isset($_GET['s_postal_code']) && !empty($_GET['s_postal_code'])) {
            $where[] = " (addr.zip LIKE '".contrexx_input2db($_GET['s_postal_code'])."%')";
        }
        if (isset($_GET['s_notes']) && !empty($_GET['s_notes'])) {
            $where[] = " (c.notes LIKE '".contrexx_input2db($_GET['s_notes'])."%')";
        }
        if (isset($_GET['customer_type']) && !empty($_GET['customer_type'])) {
            $where[] = " (c.customer_type = '".intval($_GET['customer_type'])."')";
        }
        if (isset($_GET['filter_membership']) && !empty($_GET['filter_membership'])) {
            $where[] = " mem.membership_id IN(" . 
                implode(
                    ',', 
                    contrexx_input2int($_GET['filter_membership'])
                ) . ')';
        }

        if (isset($_GET['term']) && !empty($_GET['term'])) {
            $fullTextContact = array();
            if (in_array(2, $searchContactTypeFilter))
                $fullTextContact[]  =  'c.customer_name, c.contact_familyname';
            if (in_array(1, $searchContactTypeFilter))
                $fullTextContact[]  = 'c.customer_name';
            if (empty($fullTextContact)) {
                $fullTextContact[]  =  'c.customer_name, c.contact_familyname';
            }
            $where[] = " MATCH (".implode(',', $fullTextContact).") AGAINST ('".contrexx_input2db($_GET['term'])."' IN BOOLEAN MODE)";
        }

        $process = isset ($_GET['process']) ? trim($_GET['process']) : '';
        switch ($process) {
        case '1':
                $where[] = " c.contact_type = 1";
            break;
        case '2':
                $where[] = " c.contact_type = 2";
            break;
        case 'active':
                $where[] = " c.status = 1";
            break;
        }

        //  Join where conditions
        $filter = '';
        if (!empty ($where))
            $filter = " WHERE ".implode(' AND ', $where);

        $query = "SELECT
                           DISTINCT c.id,
                           c.customer_id,
                           c.customer_name,
                           c.contact_familyname,
                           c.contact_title,
                           c.contact_type,
                           c.added_date,
                           c.updated_date,
                           c.customer_website,
                           c.contact_role,
                           c.notes,
                           c.gender,
                           c.salutation,
                           c.customer_addedby,
                           c.user_account,
                           c.contact_amount,
                           c.contact_language,
                           con.customer_name AS contactCustomer,
                           t.label AS cType,
                           Inloc.value AS industryType,
                           cur.name AS currency
                       FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS c
                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS con
                         ON c.contact_customer =con.id
                       LEFT JOIN ".DBPREFIX."module_{$this->moduleNameLC}_customer_types AS t
                         ON c.customer_type = t.id
                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_emails` as email
                         ON (c.id = email.contact_id AND email.is_primary = '1')
                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_phone` as phone
                         ON (c.id = phone.contact_id AND phone.is_primary = '1')
                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_address` as addr
                         ON (c.id = addr.contact_id AND addr.is_primary = '1')
                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_membership` as mem
                         ON (c.id = mem.contact_id)
                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_industry_types` AS Intype
                         ON c.industry_type = Intype.id
                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_industry_type_local` AS Inloc
                         ON Intype.id = Inloc.entry_id AND Inloc.lang_id = ".$_LANGID."
                       LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_currency` AS cur
                         ON cur.id = c.customer_currency
                $filter
                       ORDER BY c.id DESC";
        $objResult = $objDatabase->Execute($query);

        switch ($process){
        case '1':
            $headerCsv = array(
                $_ARRAYLANG['TXT_CRM_CONTACT_TYPE'],
                $_ARRAYLANG['TXT_CRM_TITLE_COMPANY_NAME'],
                $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERID'],
                $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERTYPE'],
                $_ARRAYLANG['TXT_CRM_INDUSTRY_TYPE'],
                $_ARRAYLANG['TXT_CRM_CUSTOMER_MEMBERSHIP'],
                $_ARRAYLANG['TXT_CRM_TITLE_CURRENCY'],
                $_ARRAYLANG['TXT_CRM_AMOUNT'],
                $_ARRAYLANG['TXT_CRM_TITLE_LANGUAGE'],
                $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMER_ADDEDBY'],
                $_ARRAYLANG['TXT_CRM_LASTUPDATE']
            );
            break;
        case '2':
            $headerCsv = array(
                $_ARRAYLANG['TXT_CRM_CONTACT_TYPE'],
                $_ARRAYLANG['TXT_CRM_CONTACT_NAME'],
                $_ARRAYLANG['TXT_CRM_FAMILY_NAME'],
                $_ARRAYLANG['TXT_CRM_TITLE'],
                $_ARRAYLANG['TXT_CRM_GENDER'],
                $_ARRAYLANG['TXT_CRM_SALUTATION'],
                $_ARRAYLANG['TXT_CRM_ROLE'],
                $_ARRAYLANG['TXT_CRM_TITLE_COMPANY_NAME'],
                $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERID'],
                $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERTYPE'],
                $_ARRAYLANG['TXT_CRM_CUSTOMER_MEMBERSHIP'],
                $_ARRAYLANG['TXT_CRM_TITLE_CURRENCY'],
                $_ARRAYLANG['TXT_CRM_AMOUNT'],
                $_ARRAYLANG['TXT_CRM_TITLE_LANGUAGE'],
                $_ARRAYLANG['TXT_CRM_ACCOUNT_EMAIL'],
                $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMER_ADDEDBY'],
                $_ARRAYLANG['TXT_CRM_LASTUPDATE']
            );
            break;
        default:
            $headerCsv = array(
                $_ARRAYLANG['TXT_CRM_CONTACT_TYPE'],
                $_ARRAYLANG['TXT_CRM_CONTACT_NAME'],
                $_ARRAYLANG['TXT_CRM_FAMILY_NAME'],
                $_ARRAYLANG['TXT_CRM_TITLE'],
                $_ARRAYLANG['TXT_CRM_GENDER'],
                $_ARRAYLANG['TXT_CRM_SALUTATION'],
                $_ARRAYLANG['TXT_CRM_ROLE'],
                $_ARRAYLANG['TXT_CRM_TITLE_COMPANY_NAME'],
                $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERID'],
                $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERTYPE'],
                $_ARRAYLANG['TXT_CRM_INDUSTRY_TYPE'],
                $_ARRAYLANG['TXT_CRM_CUSTOMER_MEMBERSHIP'],
                $_ARRAYLANG['TXT_CRM_TITLE_CURRENCY'],
                $_ARRAYLANG['TXT_CRM_AMOUNT'],
                $_ARRAYLANG['TXT_CRM_TITLE_LANGUAGE'],
                $_ARRAYLANG['TXT_CRM_ACCOUNT_EMAIL'],
                $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMER_ADDEDBY'],
                $_ARRAYLANG['TXT_CRM_LASTUPDATE']
            );
            break;
        }

        foreach ($this->emailOptions as $emailValue) {
            array_push($headerCsv, "{$_ARRAYLANG['TXT_CRM_EMAIL']} ({$_ARRAYLANG[$emailValue]})");
        }
        foreach ($this->phoneOptions as $phoneValue) {
            array_push($headerCsv, "{$_ARRAYLANG['TXT_CRM_PHONE']} ({$_ARRAYLANG[$phoneValue]})");
        }
        foreach ($this->websiteProfileOptions as $webValue) {
            array_push($headerCsv, "{$_ARRAYLANG['TXT_CRM_WEBSITE']} ({$_ARRAYLANG[$webValue]})");
        }
        foreach ($this->socialProfileOptions as $socialValue) {
            if (!empty ($socialValue)) {
                array_push($headerCsv, "{$_ARRAYLANG['TXT_CRM_SOCIAL_NETWORK']} ({$_ARRAYLANG[$socialValue]})");
            }
        }
        foreach ($this->addressTypes as $addressType) {
            foreach ($this->addressValues as $addressValue) {
                if (!empty ($addressValue) && $addressValue != 'type') {
                    array_push($headerCsv, "{$_ARRAYLANG[$addressValue['lang_variable']]} ({$_ARRAYLANG[$addressType]})");
                }
            }
        }
        $headerCsv[] = $_ARRAYLANG['TXT_CRM_DESCRIPTION'];

        $currDate = date('d_m_Y');
        header("Content-Type: text/comma-separated-values; charset:".CONTREXX_CHARSET, true);
        header("Content-Disposition: attachment; filename=\"Kundenstamm_$currDate.csv\"", true);

        foreach ($headerCsv as $field) {
            print $this->_escapeCsvValue($field).$this->_csvSeparator;
        }
        print ("\r\n");

        // preload all users at once instead of loading all one by one in the loop
        $objUsers = \FWUser::getFWUserObject()->objUser->getUsers(
            null, null, null, array('email', 'username')
        );

        if ($objResult) {
            while (!$objResult->EOF) {
            $membership = array();
                $query = "SELECT c.id,
                                 memloc.value As value
                                 FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` As c
                                LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_membership` AS mem
                                    ON c.id = mem.contact_id
                                LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_membership_local` AS memloc
                                ON (memloc.entry_id = mem.membership_id AND memloc.lang_id = {$_LANGID})
                              WHERE c.id = {$objResult->fields['id']}";
                $objMember = $objDatabase->Execute($query);
                while (!$objMember->EOF) {
                    array_push($membership, $objMember->fields['value']);
                    $objMember->MoveNext();
                }
                $membership   = implode(', ', $membership);
                $personCmyNme = $objResult->fields['contactCustomer'];
                $gender = ($objResult->fields['gender'] == 1) ? $_ARRAYLANG['TXT_CRM_GENDER_FEMALE'] : (($objResult->fields['gender'] == 2) ? $_ARRAYLANG['TXT_CRM_GENDER_MALE'] : '');
                $salutation = $objResult->fields['salutation'];
                $salutationAttributeName = '';
                if ($objResult->fields['contact_type'] == 2 && $salutation != 0) {
                    $objAttribute = \FWUser::getFWUserObject()->objUser->objAttribute->getById('title_' . $salutation);
                    if (!$objAttribute->EOF) {
                        $salutationAttributeName = $objAttribute->getName();
                    }
                }
                $langId = $objResult->fields['contact_language'];
                $langName = \FWLanguage::getLanguageParameter($langId, 'name');
                switch ($process) {
                case '1':
                        print ($objResult->fields['contact_type'] == 1 ? 'Company' : 'Person').$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 2 ? $this->_escapeCsvValue($objResult->fields['contactCustomer']) : $this->_escapeCsvValue($objResult->fields['customer_name'])).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 2 && !empty($personCmyNme) ? '' : $this->_escapeCsvValue($objResult->fields['customer_id'])).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 2 && !empty($personCmyNme) ? '' : $this->_escapeCsvValue($objResult->fields['cType'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($objResult->fields['industryType']).$this->_csvSeparator;
                        print $this->_escapeCsvValue($membership).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 2 && !empty($personCmyNme) ? '' : $this->_escapeCsvValue($objResult->fields['currency'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($objResult->fields['contact_amount']).$this->_csvSeparator;
                        print $this->_escapeCsvValue($objResult->fields['language']).$this->_csvSeparator;
                        print $this->_escapeCsvValue($this->getUserName($objResult->fields['customer_addedby'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($objResult->fields['updated_date']).$this->_csvSeparator;
                    break;
                case '2':
                        print ($objResult->fields['contact_type'] == 1 ? 'Company' : 'Person').$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 1 ? '' : $this->_escapeCsvValue($objResult->fields['customer_name'])).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 1 ? '' : $this->_escapeCsvValue($objResult->fields['contact_familyname'])).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 1 ? '' : $this->_escapeCsvValue($objResult->fields['contact_title'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($gender).$this->_csvSeparator;
                        print $salutationAttributeName . $this->_csvSeparator;
                        print $this->_escapeCsvValue($objResult->fields['contact_role']).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 2 ? $this->_escapeCsvValue($objResult->fields['contactCustomer']) : $this->_escapeCsvValue($objResult->fields['customer_name'])).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 2 && !empty($personCmyNme) ? '' : $this->_escapeCsvValue($objResult->fields['customer_id'])).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 2 && !empty($personCmyNme) ? '' : $this->_escapeCsvValue($objResult->fields['cType'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($membership).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 2 && !empty($personCmyNme) ? '' : $this->_escapeCsvValue($objResult->fields['currency'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($objResult->fields['contact_amount']).$this->_csvSeparator;
                        print $this->_escapeCsvValue($langName).$this->_csvSeparator;
                        print $this->_escapeCsvValue($this->getEmail($objResult->fields['user_account'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($this->getUserName($objResult->fields['customer_addedby'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($objResult->fields['updated_date']).$this->_csvSeparator;
                    break;
                default:
                        print ($objResult->fields['contact_type'] == 1 ? 'Company' : 'Person').$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 1 ? '' : $this->_escapeCsvValue($objResult->fields['customer_name'])).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 1 ? '' : $this->_escapeCsvValue($objResult->fields['contact_familyname'])).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 1 ? '' : $this->_escapeCsvValue($objResult->fields['contact_title'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($gender).$this->_csvSeparator;
                        print $salutationAttributeName . $this->_csvSeparator;
                        print $this->_escapeCsvValue($objResult->fields['contact_role']).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 2 ? $this->_escapeCsvValue($objResult->fields['contactCustomer']) : $this->_escapeCsvValue($objResult->fields['customer_name'])).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 2 && !empty($personCmyNme) ? '' : $this->_escapeCsvValue($objResult->fields['customer_id'])).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 2 && !empty($personCmyNme) ? '' : $this->_escapeCsvValue($objResult->fields['cType'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($objResult->fields['industryType']).$this->_csvSeparator;
                        print $this->_escapeCsvValue($membership).$this->_csvSeparator;
                        print ($objResult->fields['contact_type'] == 2 && !empty($personCmyNme) ? '' : $this->_escapeCsvValue($objResult->fields['currency'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($objResult->fields['contact_amount']).$this->_csvSeparator;
                        print $this->_escapeCsvValue($langName).$this->_csvSeparator;
                        print $this->_escapeCsvValue($this->getEmail($objResult->fields['user_account'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($this->getUserName($objResult->fields['customer_addedby'])).$this->_csvSeparator;
                        print $this->_escapeCsvValue($objResult->fields['updated_date']).$this->_csvSeparator;
                    break;
                }

                $result = array();
                // Get emails and phones
                $objEmails = $objDatabase->Execute("SELECT `email_type`, `email` FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_emails` WHERE contact_id = {$objResult->fields['id']} ORDER BY id ASC");
                if ($objEmails) {
                    while (!$objEmails->EOF) {
                        $result['contactemail'][$objEmails->fields['email_type']] = $objEmails->fields['email'];
                        $objEmails->MoveNext();
                    }
                }
                $objPhone = $objDatabase->Execute("SELECT `phone_type`, `phone` FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_phone` WHERE contact_id = {$objResult->fields['id']} ORDER BY id ASC");
                if ($objPhone) {
                    while (!$objPhone->EOF) {
                        $result['contactphone'][$objPhone->fields['phone_type']] = $objPhone->fields['phone'];
                        $objPhone->MoveNext();
                    }
                }
                $objWebsite = $objDatabase->Execute("SELECT `url_profile`, `url` FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_websites` WHERE contact_id = {$objResult->fields['id']} ORDER BY id ASC");
                if ($objWebsite) {
                    while (!$objWebsite->EOF) {
                        $result['contactwebsite'][$objWebsite->fields['url_profile']] = html_entity_decode(contrexx_raw2xhtml($objWebsite->fields['url']), ENT_QUOTES, CONTREXX_CHARSET);
                        $objWebsite->MoveNext();
                    }
                }
                $objSocial = $objDatabase->Execute("SELECT `url_profile`, `url` FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_social_network` WHERE contact_id = {$objResult->fields['id']} ORDER BY id ASC");
                if ($objSocial) {
                    while (!$objSocial->EOF) {
                        $result['contactsocial'][$objSocial->fields['url_profile']] = html_entity_decode(contrexx_raw2xhtml($objSocial->fields['url']), ENT_QUOTES, CONTREXX_CHARSET);
                        $objSocial->MoveNext();
                    }
                }
                $objAddress = $objDatabase->Execute("SELECT `Address_Type`, `address`, `city`, `state`, `zip`, `country` FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_address` WHERE contact_id = {$objResult->fields['id']} ORDER BY id ASC");
                if ($objAddress) {
                    while (!$objAddress->EOF) {
                        $result['contactAddress'][$objAddress->fields['Address_Type']] = array(
                            1 => $objAddress->fields['address'],
                            2 => $objAddress->fields['city'],
                            3 => $objAddress->fields['state'],
                            4 => $objAddress->fields['zip'],
                            5 => $objAddress->fields['country'],
                        );
                        $objAddress->MoveNext();
                    }
                }

                foreach ($this->emailOptions as $key => $emailValue) {
                    print (isset($result['contactemail'][$key]) ? $this->_escapeCsvValue($result['contactemail'][$key]) : '').$this->_csvSeparator;
                }
                foreach ($this->phoneOptions as $key => $phoneValue) {
                    print (isset($result['contactphone'][$key]) ? $this->_escapeCsvValue($result['contactphone'][$key]) : '').$this->_csvSeparator;
                }
                foreach ($this->websiteProfileOptions as $proKey => $proValue) {
                    print (isset($result['contactwebsite'][$proKey]) ? $this->_escapeCsvValue($result['contactwebsite'][$proKey]) : '').$this->_csvSeparator;
                }
                foreach ($this->socialProfileOptions as $socialKey => $socValue) {
                    if (!empty ($socValue)) {
                        print (isset($result['contactsocial'][$socialKey]) ? $this->_escapeCsvValue($result['contactsocial'][$socialKey]) : '').$this->_csvSeparator;
                    }
                }
                foreach ($this->addressTypes as $addTypeKey => $addressType) {
                    foreach ($this->addressValues as $addValKey => $addressValue) {
                        if (!empty ($addressValue) && $addressValue != 'type') {
                            print (isset($result['contactAddress'][$addTypeKey]) ? $this->_escapeCsvValue($result['contactAddress'][$addTypeKey][$addValKey]) : '').$this->_csvSeparator;
                        }
                    }
                }

                $description = str_replace("&nbsp;", " ", strip_tags(html_entity_decode($objResult->fields['notes'], ENT_QUOTES, CONTREXX_CHARSET)));
                print $this->_escapeCsvValue(html_entity_decode($description, ENT_QUOTES, CONTREXX_CHARSET)).$this->_csvSeparator;

                print ("\r\n");
                $objResult->MoveNext();
            }
        }
        exit();
    }

    /**
     * Export all fields can be imported
     *
     * @global array $_ARRAYLANG
     * @return array return all the import field options
     */
    function getImportOptions()
    {
        global $_ARRAYLANG;

        $headerCsv = array(
            array("value" => "", "title"  => $_ARRAYLANG['TXT_CRM_NO_MATCHES_FROM_LIST'], "Header" => false),
            array("value" => "", "title"  => $_ARRAYLANG['TXT_CRM_DONT_IMPORT_FIELD'], "Header" => false),
            array("value" => "", "title"    => $_ARRAYLANG['TXT_CRM_GENERAL_INFORMATION'], "Header" => true),
            array("value" => 'firstname', 'title' => $_ARRAYLANG['TXT_CRM_CONTACT_NAME'], 'Header' => false),
            array("value" => 'lastname', 'title' => $_ARRAYLANG['TXT_CRM_FAMILY_NAME'], 'Header' => false),
            array("value" => 'gender', 'title' => $_ARRAYLANG['TXT_CRM_GENDER'], 'Header' => false),
            array("value" => 'title', 'title' => $_ARRAYLANG['TXT_CRM_TITLE'], 'Header' => false),
            array("value" => 'amount', 'title' => $_ARRAYLANG['TXT_CRM_AMOUNT'], 'Header' => false),
            array("value" => 'changed_date', 'title' => $_ARRAYLANG['TXT_CRM_LASTUPDATE'], 'Header' => false),
            array("value" => 'company', 'title' => $_ARRAYLANG['TXT_CRM_TITLE_COMPANY_NAME'], 'Header' => false),
            array("value" => 'role', 'title' => $_ARRAYLANG['TXT_CRM_ROLE'], 'Header' => false),
            array("value" => 'customertype', 'title' => $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERTYPE'], 'Header' => false),
            array("value" => 'industrytype', 'title' => $_ARRAYLANG['TXT_CRM_INDUSTRY_TYPE'], 'Header' => false),
            array("value" => 'customerGroups', 'title' => $_ARRAYLANG['TXT_CRM_CUSTOMER_MEMBERSHIP'], 'Header' => false),
            array("value" => 'currency', 'title' => $_ARRAYLANG['TXT_CRM_TITLE_CURRENCY'], 'Header' => false),
            array("value" => 'customerId', 'title' => $_ARRAYLANG['TXT_CRM_TITLE_CUSTOMERID'], 'Header' => false),
            array("value" => 'language', 'title' => $_ARRAYLANG['TXT_CRM_TITLE_LANGUAGE'], 'Header' => false),
            array("value" => 'description', 'title' => $_ARRAYLANG['TXT_CRM_DESCRIPTION'], 'Header' => false),
            );

        foreach ($this->emailOptions as $key => $emailValue) {
            array_push($headerCsv, array("value" => "customer_email_$key", 'title' => "{$_ARRAYLANG['TXT_CRM_EMAIL']} ({$_ARRAYLANG[$emailValue]})", 'Header' => false));
        }
        foreach ($this->phoneOptions as $key => $phoneValue) {
            array_push($headerCsv, array("value" => "customer_phone_$key", 'title' => "{$_ARRAYLANG['TXT_CRM_PHONE']} ({$_ARRAYLANG[$phoneValue]})", 'Header' => false));
        }
        foreach ($this->websiteProfileOptions as $websiteKey => $webValues) {
            array_push($headerCsv, array("value" => "customer_website_{$websiteKey}", 'title' => "{$_ARRAYLANG['TXT_CRM_WEBSITE']} ({$_ARRAYLANG[$webValues]})", 'Header' => false));
        }
        foreach ($this->socialProfileOptions as $websiteKey => $webValues) {
            if (!empty($webValues)) {
                array_push($headerCsv, array("value" => "customer_social_{$websiteKey}", 'title' => "{$_ARRAYLANG['TXT_CRM_SOCIAL_NETWORK']} ({$_ARRAYLANG[$webValues]})", 'Header' => false));
            }
        }
        foreach ($this->addressTypes as $addrKey => $addressType) {
            foreach ($this->addressValues as $key => $addressValue) {
                if (!empty ($addressValue) && $addressValue != 'type') {
                    array_push($headerCsv, array("value" => "customer_address_{$addrKey}_{$key}", 'title' => "{$_ARRAYLANG[$addressValue['lang_variable']]} ({$_ARRAYLANG[$addressType]})", 'Header' => false));
                }
            }
        }

        return $headerCsv;
    }

    /**
     * Save the data into crm
     *
     * @global array $_ARRAYLANG
     *
     * @return Save the data into crm
     */
    function saveCsvData()
    {
        global $objDatabase, $_LANGID, $_ARRAYLANG;

        $csvSeprator    = isset ($_POST['csv_delimiter']) && in_array($_POST['csv_delimiter'], array_keys($this->_delimiter)) ? $this->_delimiter[$_POST['csv_delimiter']]['value'] : $this->_delimiter[0]['value'];
        $csvDelimiter   = isset ($_POST['csv_enclosure']) && in_array($_POST['csv_enclosure'], array_keys($this->_enclosure)) ? $this->_enclosure[$_POST['csv_enclosure']]['value'] : $this->_enclosure[0]['value'];
        $csvIgnoreFirst = isset ($_POST['ignore_first']) && contrexx_input2int($_POST['ignore_first']);
        $duplicate      = isset ($_POST['on_duplicate']) ? contrexx_input2int($_POST['on_duplicate']) : 2;
        $fileName       = isset ($_POST['fileUri']) ? \FWValidator::getCleanFileName(contrexx_input2raw($_POST['fileUri'])) : '';
        $objFWUser      = \FWUser::getFWUserObject();

        if (empty($_SESSION[$fileName])) {
            $_SESSION[$fileName] = array();
        }

        $importOptions = array_column($this->getImportOptions(), 'value');
        foreach ($_POST['crm_contact_option_base'] as $colId => $value) {
            if (!empty($value) && in_array($value, $importOptions)) {
                ${$value} = $colId;
            }
        }

        $processedLines = $_SESSION[$fileName]['processedRows'] ?? 0;
        $importedLines  = $_SESSION[$fileName]['importedRows'] ?? 0;
        $skipedLines    = $_SESSION[$fileName]['skippedRows'] ?? 0;
        if (isset($firstname) || isset($lastname) || isset($company)) {
            $this->contact = new \Cx\Modules\Crm\Model\Entity\CrmContact();

            $objCsv        = new CrmCsv($this->_mediaPath . '/' . $fileName, $csvSeprator, $csvDelimiter);
            $line          = $objCsv->NextLine();
            $first         = $_SESSION[$fileName]['ignoreFirstRow'] ?? true;
            $processRowCnt = $processedLines + 10;

            $i = 1;
            while ($line) {
                if (!$this->checkMemoryLimit()) {
                    $this->sendCsvImportResponse(
                        $fileName,
                        'error',
                        $_ARRAYLANG['TXT_CRM_SETTINGS_INTERFACE_IMPORT_MEMORY_ERROR']
                    );
                }

                if ($i <= $processedLines) {
                    $i++;
                    $line = $objCsv->NextLine();
                    continue;
                }

                if (!$first || !$csvIgnoreFirst) {
                    $this->contact->clean();
                    $this->contact->contactType = !empty($line[$firstname]) || !empty($line[$lastname])
                                                 ? 2
                                                 : (!empty($line[$company]) ? 1 : 0);
                    if (!empty($this->contact->contactType)) {
                        $this->contact->datasource       = 3;

                        $this->contact->family_name      = $this->contact->contactType == 2
                                                          ? (isset($line[$lastname]) ? contrexx_input2raw($line[$lastname]) : '')
                                                          : '';
                        $this->contact->contact_role     = $this->contact->contactType == 2
                                                          ? (isset($line[$role]) ? contrexx_input2raw($line[$role]) : '')
                                                          : '';
                        $this->contact->contact_language = $this->contact->contactType == 2
                                                          ? (isset($line[$language]) ? $this->getLanguageIdByName($line[$language]) : $_LANGID)
                                                          : '';
                        $this->contact->contact_customer = $this->contact->contactType == 2
                                                          ? (isset($line[$company]) ? $this->getCustomerIdByName($line[$company]) : 0)
                                                          : 0;
                        $this->contact->contact_gender   = $this->contact->contactType == 2
                                                          ? (isset($line[$gender]) ? (int) ($line[$gender] == 'Female') ? '1' : (($line[$gender] == 'Male') ? '2' : 0) : 0)
                                                          : 0;
                        $this->contact->customerName     = $this->contact->contactType == 2
                                                          ? (isset($line[$firstname]) ? contrexx_input2raw($line[$firstname]) : '')
                                                          : (isset($line[$company]) ? contrexx_input2raw($line[$company]) : '');

                        $this->contact->customerId   = isset($line[$customerId]) ? contrexx_input2raw($line[$customerId]) : '';
                        $this->contact->customerType = isset($line[$customertype]) ? $this->getCustomerTypeIdByName($line[$customertype]) : 0;
                        $this->contact->addedUser    = $objFWUser->objUser->getId();
                        $this->contact->currency     = isset($line[$currency]) ? $this->getCurrencyIdByName($line[$currency]) : 0;
                        $this->contact->notes        = isset($line[$description]) ? contrexx_input2raw($line[$description]) : '';
                        $this->contact->industryType = isset($line[$industrytype]) ? $this->getIndustryTypeIdByName($line[$industrytype]) : 0;

                        // unset customer type, customerId the contact have customer
                        if (($this->contact->contactType == 2) && $this->contact->contact_customer != 0) {
                            $this->contact->customerType = 0;
                            $this->contact->currency     = 0;
                            $this->contact->customerId   = '';
                        }

                        if (in_array($duplicate, array(0, 1))) {
                            $emails = array();
                            foreach ($this->emailOptions as $key => $emailValue) {
                                if (isset(${"customer_email_$key"})) {
                                    if (!empty($line[${"customer_email_$key"}]) && filter_var($line[${"customer_email_$key"}], FILTER_VALIDATE_EMAIL)) {
                                        $emails[] = $line[${"customer_email_$key"}];
                                    }
                                }
                            }
                            $existingUser = $this->checkContactExists($this->contact->customerName, $this->contact->family_name, $emails, $this->contact->contactType);
                        }

                        $skip = false;
                        switch ($duplicate) {
                        case 0:
                            if (empty ($existingUser)) {
                                $this->contact->save();
                            } else {
                                $skip = true;
                            }
                            break;
                        case 1:
                            if (!empty ($existingUser)) {
                                $this->contact->id = $existingUser;
                            }
                        case 2:
                                $this->contact->save();
                            break;
                        }

                        if (!$skip) {
                            $importedLines++;
                            $_SESSION[$fileName]['importedRows'] = $importedLines;

                            //insert customer groups
                            if (isset($customerGroups) && !empty($line[$customerGroups])) {
                                $groupIds   = array();
                                $groupNames = explode(', ', $line[$customerGroups]);
                                $query = 'SELECT
                                            `membership`.`id`
                                          FROM
                                            `'.DBPREFIX.'module_'. $this->moduleNameLC . '_memberships` AS membership
                                          LEFT JOIN
                                            `'.DBPREFIX.'module_' . $this->moduleNameLC . '_membership_local` AS memberLoc
                                          ON
                                            `membership`.`id` = `memberLoc`.`entry_id`
                                          WHERE
                                            `memberLoc`.`value` IN ("' . implode('" , "', contrexx_input2db($groupNames)) . '")
                                        ';
                                $objResult = $objDatabase->Execute($query);
                                if ($objResult && $objResult->RecordCount() > 0) {
                                    while (!$objResult->EOF) {
                                        $groupIds[] = $objResult->fields['id'];
                                        $objResult->MoveNext();
                                    }
                                }
                                if ($groupIds) {
                                    $this->updateCustomerMemberships($groupIds, $this->contact->id);
                                }
                            }

                            // insert Emails
                            $first  = true;
                            foreach ($this->emailOptions as $key => $emailValue) {
                                if (isset(${"customer_email_$key"})) {
                                    if (!empty($line[${"customer_email_$key"}]) && filter_var($line[${"customer_email_$key"}], FILTER_VALIDATE_EMAIL)) {
                                        $tableName = "module_{$this->moduleNameLC}_customer_contact_emails";
                                        $fields   = array(
                                            'email'         => contrexx_input2db($line[${"customer_email_$key"}]),
                                            'email_type'    => $key,
                                            'is_primary'    => $first ? '1' : '0',
                                            'contact_id'    => $this->contact->id
                                        );
                                        $first    = false;
                                        $values   = array('email_type', $key, $this->contact->id);
                                        $this->checkRecordStoreTODB($tableName, $values, $fields);
                                    }
                                }
                            }

                            // insert Phone
                            $first  = true;
                            foreach ($this->phoneOptions as $key => $phoneValue) {
                                if (isset(${"customer_phone_$key"})) {
                                    if (!empty($line[${"customer_phone_$key"}])) {
                                        $tableName = "module_{$this->moduleNameLC}_customer_contact_phone";
                                        $fields    = array(
                                            'phone'         => contrexx_input2db($line[${"customer_phone_$key"}]),
                                            'phone_type'    => $key,
                                            'is_primary'    => $first ? '1' : '0',
                                            'contact_id'    => $this->contact->id
                                        );
                                        $first     = false;
                                        $values    = array('phone_type', $key, $this->contact->id);
                                        $this->checkRecordStoreTODB($tableName, $values, $fields);
                                    }
                                }
                            }

                            // insert Website
                            $first = true;
                            $custWeb = array('3','4','5');
                            $conWeb  = array('0','1','2');
                            foreach ($this->websiteProfileOptions as $websiteKey => $webValues) {
                                $proceed = ($this->contact->contactType == 2 && in_array($websiteKey, $conWeb)) ? true : (($this->contact->contactType != 2 && in_array($websiteKey, $custWeb)) ? true : false);
                                if (!empty($line[${"customer_website_$websiteKey"}]) && $proceed) {
                                    $tableName = "module_{$this->moduleNameLC}_customer_contact_websites";
                                    $fields = array(
                                        'url'           => contrexx_input2raw($line[${"customer_website_$websiteKey"}]),
                                        'url_profile'   => $websiteKey,
                                        'is_primary'    => $first ? '1' : '0',
                                        'contact_id'    => $this->contact->id
                                    );
                                    $first     = false;
                                    $values    = array('url_profile', $websiteKey, $this->contact->id);
                                    $this->checkRecordStoreTODB($tableName, $values, $fields);
                                }
                            }

                            // insert Social Network
                            $first = true;
                            foreach ($this->socialProfileOptions as $websiteKey => $webValues) {
                                if (!empty($line[${"customer_social_$websiteKey"}])) {
                                    $tableName = "module_{$this->moduleNameLC}_customer_contact_social_network";
                                    $fields = array(
                                        'url'           => contrexx_input2raw($line[${"customer_social_$websiteKey"}]),
                                        'url_profile'   => $websiteKey,
                                        'is_primary'    => $first ? '1' : '0',
                                        'contact_id'    => $this->contact->id
                                    );
                                    $first     = false;
                                    $values    = array('url_profile', $websiteKey, $this->contact->id);
                                    $this->checkRecordStoreTODB($tableName, $values, $fields);
                                }
                            }

                            // insert address
                            $first = true;
                            foreach ($this->addressTypes As $addTypeKey => $addTypeValue) {
                                $fields = array();
                                $insert = false;
                                foreach ($this->addressValues As $addressKey => $addressValue) {
                                    if (!empty ($line[${"customer_address_$addTypeKey"."_$addressKey"}])) {
                                        if (!empty ($addressValue) && $addressValue != 'type') {
                                            $insert = true;
                                            $fields[$addressValue['label']] = contrexx_input2raw($line[${"customer_address_$addTypeKey"."_$addressKey"}]);
                                        }
                                    }
                                }
                                if ($insert) {
                                    $tableName = "module_{$this->moduleNameLC}_customer_contact_address";
                                    $fields['Address_Type'] = $addTypeKey;
                                    $fields['is_primary']   = $first ? '1' : '0';
                                    $fields['contact_id']   = $this->contact->id;
                                    $first     = false;
                                    $values    = array('Address_Type', $addTypeKey, $this->contact->id);
                                    $this->checkRecordStoreTODB($tableName, $values, $fields);
                                }
                            }
                        } else {
                            $skipedLines++;
                            $_SESSION[$fileName]['skippedRows'] = $skipedLines;
                        }
                    } else {
                        $skipedLines++;
                        $_SESSION[$fileName]['skippedRows'] = $skipedLines;
                    }
                } else {
                    $skipedLines++;
                    $_SESSION[$fileName]['skippedRows'] = $skipedLines;
                }
                $processedLines++;
                $_SESSION[$fileName]['processedRows'] = $processedLines;
                $first = false;
                $line  = $objCsv->NextLine();
                if ($i == $processRowCnt) {
                    $_SESSION[$fileName]['ignoreFirstRow'] = $first;
                    $this->sendCsvImportResponse(
                        $fileName,
                        'success',
                        $_ARRAYLANG['TXT_CRM_SETTINGS_INTERFACE_IMPORT_SUCCESS']
                    );
                }
                $i++;
            }
            $importStatus = 'success';
            $importMsg    = $_ARRAYLANG['TXT_CRM_SETTINGS_INTERFACE_IMPORT_SUCCESS'];
        } else {
            $importStatus = 'error';
            $importMsg    = $_ARRAYLANG['TXT_CRM_CHOOSE_NAME_ERROR'];
        }

        $this->sendCsvImportResponse($fileName, $importStatus, $importMsg);
    }

    /**
     * Check the argument and save the field values to corresponding DB
     *
     * @param String $tableName Table name
     * @param Array  $values    Conditions
     * @param Array  $fields    Field values
     *
     * @return null
     */
    function checkRecordStoreTODB($tableName = '', $values = array(), $fields = array())
    {
        global $objDatabase;

        if (!empty ($tableName) && !empty ($fields)) {
            $objRecordExist = $objDatabase->getOne("SELECT id FROM `".DBPREFIX."{$tableName}` WHERE $values[0] = '".$values[1]."' AND contact_id = {$values[2]}");
            if ($objRecordExist && !empty ($objRecordExist)) {
                $query = \SQL::update($tableName, $fields, array('escape' => true))." WHERE `id` = {$objRecordExist}";
            } else {
                $query = \SQL::insert($tableName, $fields, array('escape' => true));
            }
            $objDatabase->execute($query);
        }
    }

    /**
     * Get Language Id ny name
     *
     * @param String $language Language name
     *
     * @return Integer
     */
    function getLanguageIdByName($language)
    {
        foreach(\FWLanguage::getActiveFrontendLanguages() as $frontendLanguage) {
            if ($frontendLanguage['name'] == $language) {
                return (int) $language['id'];
            }
        }
    }

    /**
     * Get Customer Id ny name
     *
     * @param String $company Company name
     *
     * @return Integer
     */
    function getCustomerIdByName($company)
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("SELECT `id` FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` WHERE `contact_type` = '1' AND `customer_name` = '". contrexx_raw2db($company)."' LIMIT 0, 1");

        return (int) $objResult->fields['id'];
    }

    /**
     * Get Customer Type id by name
     *
     * @param String $customerType customertype name
     *
     * @return Integer
     */
    function getCustomerTypeIdByName($customerType)
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("SELECT `id` FROM `".DBPREFIX."module_{$this->moduleNameLC}_customer_types` WHERE `label` = '".contrexx_raw2db($customerType)."' LIMIT 0, 1");

        return (int) $objResult->fields['id'];
    }

    /**
     * Get currency id by name
     *
     * @param String $currency currency name
     *
     * @return Integer
     */
    function getCurrencyIdByName($currency)
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute("SELECT `id` FROM `".DBPREFIX."module_{$this->moduleNameLC}_currency` WHERE `name` = '". contrexx_raw2db($currency)."' LIMIT 0, 1");

        return (int) $objResult->fields['id'];
    }

    /**
     * Get industry type id by name
     *
     * @param String $industrytype industry type name
     *
     * @return Integer
     */
    function getIndustryTypeIdByName($industrytype)
    {
        global $objDatabase;

        $query = "SELECT `id` FROM `".DBPREFIX."module_{$this->moduleNameLC}_industry_types` As ind
                    LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_industry_type_local` As ind_loc
                        ON (ind.id = ind_loc.entry_id)
                    WHERE ind_loc.value = '". contrexx_raw2db($industrytype)."' LIMIT 0, 1";

        $objResult = $objDatabase->Execute($query);

        return (int) $objResult->fields['id'];
    }

    /**
     * Check Contact exists or not
     *
     * @param String  $customer_name customer name
     * @param String  $family_name   family name
     * @param String  $emails        email ids
     * @param Integer $contact_type  Contact type
     *
     * @return Integer
     */
    function checkContactExists($customer_name, $family_name, $emails, $contact_type)
    {
        global $objDatabase;

        $whereEmails = !empty($emails)
                      ? " AND e.email IN (".implode(' , ', array_map(function ($el){ return "'$el'"; }, contrexx_raw2db($emails))).")"
                      : '';
        $query = "SELECT
                        DISTINCT c.`id`
                        FROM `".DBPREFIX."module_{$this->moduleNameLC}_contacts` AS c
                          LEFT JOIN `".DBPREFIX."module_{$this->moduleNameLC}_customer_contact_emails` as e
                            ON (c.`id` = e.`contact_id`)
                        WHERE c.`customer_name` = '". contrexx_raw2db($customer_name) ."'
                          AND c.`contact_familyname` = '". contrexx_raw2db($family_name) ."'
                          AND c.`contact_type` = '$contact_type'
                          $whereEmails LIMIT 0, 1";
        $objResult = $objDatabase->Execute($query);

        return (int) $objResult->fields['id'];

    }

    /**
     * Send a response for csv import
     *
     * @param string $fileName     File name
     * @param string $importStatus Import status success or error
     * @param String $importMsg    Import message
     */
    protected function sendCsvImportResponse($fileName, $importStatus, $importMsg)
    {
        $json = array(
            'status'        => $importStatus,
            'message'       => $importMsg,
            'processedRows' => $_SESSION[$fileName]['processedRows'] ?? 0,
            'skippedRows'   => $_SESSION[$fileName]['skippedRows'] ?? 0,
            'importedRows'  => $_SESSION[$fileName]['importedRows'] ?? 0
        );
        echo json_encode($json);
        exit();
    }

    /**
     * This method checks the required memory is available for the import process
     * if not then it will try to increase the memory limit.
     *
     * @return boolean True if memory limit is greater than required memory,
     *                 otherwise false if unable to set a memory limit
     */
    protected function checkMemoryLimit()
    {
        if (empty($this->memoryLimit)) {
            $memoryLimit = \FWSystem::getBytesOfLiteralSizeFormat(@ini_get('memory_limit'));
            //if memory limit is empty then set default php memory limit of 8MiBytes
            $this->memoryLimit = !empty($memoryLimit) ? $memoryLimit : self::MiB2 * 4;
        }

        $potentialRequiredMemory = memory_get_usage() + self::MiB2;
        if ($potentialRequiredMemory > $this->memoryLimit) {
            // try to set a higher memory_limit
            if (!@ini_set('memory_limit', $potentialRequiredMemory)) {
                return false;
            }
            $this->memoryLimit = $potentialRequiredMemory;
        }

        return true;
    }
}
