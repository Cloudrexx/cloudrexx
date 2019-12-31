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
 * Immo
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_immo
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Immo
 *
 * Immo backend
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_immo
 */
class Immo extends ImmoLib
{
    /**
     * Template object
     *
     * @access private
     * @var object
     */
    var $_objTpl;

    /**
     * Page title
     *
     * @access private
     * @var string
     */
    var $_pageTitle;

    /**
     * Status message
     *
     * @access private
     * @var string
     */
    var $_strOkMessage = '';
    var $_strErrMessage = '';

    /**
     * Name of the headline variable in the primary language
     *
     * @access private
     * @var string
     */
    var $_headline = 'überschrift';



    var $_defaultImage = '../core/Core/View/Media/icons/images.gif';

    /**
     * Constructor
     */
    function Immo()
    {
        $this->__construct();
    }

    private $act = '';

    /**
     * PHP5 constructor
     *
     * @global object $objTemplate
     * @global array $_ARRAYLANG
     */
    function __construct()
    {
        global $objTemplate, $_ARRAYLANG, $objDatabase;
        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/immo/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);



        $this->_objFile =new File();

        // initialise the session array
        if (!isset($_SESSION['immo'])) {
            $_SESSION['immo'] = array();
        }
        // Run parent constructor
        parent::__construct();
    }

    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable("CONTENT_NAVIGATION","
            <a href='?cmd=immo' class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_IMMO_OVERVIEW']."</a>
            <a href='?cmd=immo&amp;act=add' class='".($this->act == 'add' ? 'active' : '')."'>".$_ARRAYLANG['TXT_IMMO_ADD']."</a>
    <!--    <a href='?cmd=immo&amp;act=downloads' class='".($this->act == 'downloads' ? 'active' : '')."'>".$_ARRAYLANG['TXT_IMMO_DOWNLOADS']."</a> -->
            <a href='?cmd=immo&amp;act=stats' class='".($this->act == 'stats' ? 'active' : '')."'>".$_ARRAYLANG['TXT_IMMO_STATS']."</a>
            <a href='?cmd=immo&amp;act=settings' class='".($this->act == 'settings' ? 'active' : '')."'>".$_ARRAYLANG['TXT_IMMO_SETTINGS']."</a>
    <!--    <a href='?cmd=immo&amp;act=export' class='".($this->act == 'export' ? 'active' : '')."'>".$_ARRAYLANG['TXT_IMMO_EXPORT']."</a> -->");
    }

    /**
     * Set the backend page
     *
     * @access public
     * @global object $objTemplate
     * @global array $_ARRAYLANG
     */
    function getPage()
    {
        global $objTemplate, $_ARRAYLANG;
        if (!isset($_GET['act'])) {
            $_GET['act']="";
        }

        switch($_GET['act']) {

            case 'debug':
                print_r($_SESSION['immo']);
                unset($_SESSION['immo']);
                print_r($_SESSION['immo']);
                break;
            case 'add':
                $this->_add();
                $this->_showImmoForm();
                break;
            case 'del':
                $this->_del();
                $this->_showOverview();
                break;
            case 'delimages':
                $this->_deleteUnusedImages();
                $this->_showSettings();
                break;
            case 'copy':
                $this->_copy();
                $this->_showOverview();
                break;
            case 'interests':
                $this->_interests();
                break;
            case 'contactdetails':
                $this->_showContactDetails();
                break;
            case 'interestdetails':
                $this->_showInterestDetails();
                break;
            case 'rpcov':
                $this->_RPCOverview();
                break;
            case 'rpc':
                $this->_RPCGetSuggest();
                break;
            case 'rpcr':
                $this->_RPCSort();
                break;
            case 'rpcs':
                $this->_RPCSearch();
                break;
            case 'rpcsisort':
                $this->_RPCSortInterests();
                break;
            case 'rpcsi':
                $this->_RPCSearchInterests();
                break;
            case 'rpcdl':
                $this->_RPCDownloadStats();
                break;
            case 'stats':
                $this->_showStats();
                break;
            case 'downloads':
                $this->_showDownloads();
                break;
            case 'export':
                $this->_exportContacts();
                break;
            case 'modimmo':
                $this->_modimmo();
                break;
            case 'saveSettings':
                $this->_saveSettings();
                $this->_getSettings();
            case 'settings':
                $this->_showSettings();
                break;
            case 'map':
                $this->_showMapPopup();
                exit;
                break;
            case 'addfields':
                $this->_addfields();
                break;
            case 'modfields':
                $this->_modfields();
                break;
            case 'addlanguage':
                $this->_addlanguage();
                break;
            case 'delfield':
                $this->_delfield();
                break;
            default:
                $this->_showOverview();
                break;
        }

        $objTemplate->setVariable(array(
                'CONTENT_TITLE'                => $this->_pageTitle,
                'CONTENT_OK_MESSAGE'        => $this->_strOkMessage,
                'CONTENT_STATUS_MESSAGE'    => $this->_strErrMessage,
                'ADMIN_CONTENT'                => $this->_objTpl->get()
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }


    /**
     * remote scripting interest list sort
     *
     * @return JSON object
     */
    function _RPCSortInterests() {
        global $_CONFIG, $objDatabase;
        $output = '';
        $fieldValues = array('immo_id', 'name', 'firstname', 'street', 'zip', 'location', 'telephone', 'comment', 'time');
        $immoID = !empty($_REQUEST['immoid']) ? intval($_REQUEST['immoid']) : 0;
        $field = (!empty($_GET['field'])) ? contrexx_addslashes($_GET['field']) : 'time';
        $order = (!empty($_GET['order'])) ? contrexx_addslashes($_GET['order']) : 'asc';
        $limit = (!empty($_GET['limit'])) ? intval($_GET['limit']) : $_CONFIG['corePagingLimit'];
        if(!in_array($field, $fieldValues) && ( $order != 'asc' || $order != 'desc' )) {
            die();
        }

        $searchTerm = (!empty($_REQUEST['search']) && !empty($_REQUEST['searchField'])) ? " LIKE '%".contrexx_addslashes($_REQUEST['search'])."%'" : ' TRUE';
        $searchField = (!empty($_REQUEST['searchField']) && !empty($_REQUEST['search'])) ? ' WHERE '.contrexx_addslashes($_REQUEST['searchField']) : ' WHERE ';

        $query = "    SELECT     `interest`.`id` as contact_id , `email` , `name` , `firstname` , `street` , `zip` , `location` ,
                            `phone_home`, `comment` , `interest`.`immo_id` , `time`, content1.fieldvalue AS immo_header, content2.fieldvalue AS immo_address, content3.fieldvalue AS immo_location
                    FROM `".DBPREFIX."module_immo_interest` AS interest
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content1 ON content1.immo_id = interest.immo_id
                        AND content1.lang_id =1
                        AND content1.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = '".$this->_headline."'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content2 ON content2.immo_id = interest.immo_id
                        AND content2.lang_id =1
                        AND content2.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'adresse'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content3 ON content3.immo_id = interest.immo_id
                        AND content3.lang_id =1
                        AND content3.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'ort'
                            AND fname.lang_id =1
                        )"
                .$searchField." ".$searchTerm;
        if($_REQUEST['ignore_timespan'] !== 'on' && !empty($_SESSION['immo']['startDate'])) {
            $query .= " AND `time` BETWEEN ".strtotime($_SESSION['immo']['startDate'])." AND ".strtotime($_SESSION['immo']['endDate']);
        }
        if($immoID > 0) {
            $query .= " AND interest.immo_id = $immoID";
        }
        $query .= " ORDER BY ".$field." ".$order;

        $objRS = $objDatabase->SelectLimit($query, $limit);
        $limit = ($limit > $objRS->RecordCount()) ? $objRS->RecordCount() : $limit;
        $contacts = '';
        for($i=0; $i<$limit; $i++) {
            $contacts .= 'contacts['.$i.'] = { ';
            //escape string and replace space escape
            $contacts .= 'immo_id:"'.str_replace('+',' ', urlencode($objRS->fields['immo_id']))."\",";
            $contacts .= 'contact_id:"'.str_replace('+',' ', urlencode($objRS->fields['contact_id']))."\",";
            $contacts .= 'email:"'.str_replace('+',' ', urlencode($objRS->fields['email']))."\",";
            $contacts .= 'name:"'.str_replace('+',' ', urlencode($objRS->fields['name']))."\",";
            $contacts .= 'firstname:"'.str_replace('+',' ', urlencode($objRS->fields['firstname']))."\",";
            $contacts .= 'street:"'.str_replace('+',' ', urlencode($objRS->fields['street']))."\",";
            $contacts .= 'zip:"'.str_replace('+',' ', urlencode($objRS->fields['zip']))."\",";
            $contacts .= 'location:"'.str_replace('+',' ', urlencode($objRS->fields['location']))."\",";
            $contacts .= 'telephone:"'.str_replace('+',' ', urlencode($objRS->fields['phone_home']))."\",";
            $contacts .= 'comment:"'.str_replace('+',' ', urlencode($objRS->fields['comment']))."\",";
            $contacts .= 'timestamp:"'.str_replace('+',' ', urlencode(date(ASCMS_DATE_FORMAT, $objRS->fields['time'])))."\"};\n\n";
            $objRS->MoveNext();
        }
        die($contacts);
    }

    /**
     * show interests
     *
     * @return void
     */
    function _interests() {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;
        JS::activate('jqueryui');
        $interestID = intval($_GET['del']);
        if(!empty($interestID)) {
            if($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_immo_interest WHERE id = $interestID") !== false) {
                $this->_strOkMessage = $_ARRAYLANG['TXT_IMMO_SUCCESSFULLY_DELETED'];
                $this->_showStats();
                return true;
            }
        }

        $this->_pageTitle = $_ARRAYLANG['TXT_IMMO_INTERESTS'];
        $this->_objTpl->loadTemplateFile('module_immo_interests.html');

        $immoID = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
        $_SESSION['immo']['startDate']    = !empty($_REQUEST['inputStartDate']) ? contrexx_addslashes($_REQUEST['inputStartDate'])  : $_SESSION['immo']['startDate'];
        $_SESSION['immo']['endDate']    = !empty($_REQUEST['inputEndDate']) ? contrexx_addslashes($_REQUEST['inputEndDate'])  : $_SESSION['immo']['endDate'];
        $limit = !empty($_REQUEST['limit']) ? intval($_REQUEST['limit']) : $_CONFIG['corePagingLimit'];
        $pos = !empty($_REQUEST['pos']) ? intval($_REQUEST['pos']) : 0;
        $field = (!empty($_REQUEST['field'])) ? contrexx_addslashes($_REQUEST['field']) : 'visits';
        $order = (!empty($_REQUEST['order'])) ? contrexx_addslashes($_REQUEST['order']) : 'asc';
        $hsearchField = !empty($_REQUEST['searchField']) ? $_REQUEST['searchField'] : '' ;
        $hsearch = !empty($_REQUEST['search']) ? contrexx_addslashes($_REQUEST['search']) : '' ;

        $this->_objTpl->setGlobalVariable(array(
                'TXT_IMMO_IMMO_ID'                     => $_ARRAYLANG['TXT_IMMO_IMMO_ID'],
                'TXT_IMMO_INTERESTS'                 => $_ARRAYLANG['TXT_IMMO_INTERESTS'],
                'TXT_IMMO_INTEREST_SEARCH'             => $_ARRAYLANG['TXT_IMMO_INTEREST_SEARCH'],
                'TXT_IMMO_EXPORT'                      =>    $_ARRAYLANG['TXT_IMMO_EXPORT'],
                'TXT_IMMO_TIMESPAN'                      =>    $_ARRAYLANG['TXT_IMMO_TIMESPAN'],
                'TXT_IMMO_FROM'                          =>    $_ARRAYLANG['TXT_IMMO_FROM'],
                'TXT_IMMO_TO'                          =>    $_ARRAYLANG['TXT_IMMO_TO'],
                'TXT_IMMO_INTERESTS'                 =>    $_ARRAYLANG['TXT_IMMO_INTERESTS'],
                'TXT_IMMO_DOWNLOAD_LIST'              =>    $_ARRAYLANG['TXT_IMMO_DOWNLOAD_LIST'],
                'TXT_IMMO_INTEREST_SEARCH'              =>    $_ARRAYLANG['TXT_IMMO_INTEREST_SEARCH'],
                'TXT_IMMO_SHOW_TIMESPAN_DETAILS'     =>    $_ARRAYLANG['TXT_IMMO_SHOW_TIMESPAN_DETAILS'],
                'TXT_IMMO_IGNORE_TIMESPAN'             =>    $_ARRAYLANG['TXT_IMMO_IGNORE_TIMESPAN'],
                'TXT_IMMO_REFRESH'                     =>    $_ARRAYLANG['TXT_IMMO_REFRESH'],
                'TXT_IMMO_SEARCH'                     =>    $_ARRAYLANG['TXT_IMMO_SEARCH'],
                'TXT_IMMO_EMAIL'                     =>    $_ARRAYLANG['TXT_IMMO_EMAIL'],
                'TXT_IMMO_NAME'                         =>    $_ARRAYLANG['TXT_IMMO_NAME'],
                'TXT_IMMO_FIRSTNAME'                 =>    $_ARRAYLANG['TXT_IMMO_FIRSTNAME'],
                'TXT_IMMO_COMPANY'                     =>    $_ARRAYLANG['TXT_IMMO_COMPANY'],
                'TXT_IMMO_STREET'                     =>    $_ARRAYLANG['TXT_IMMO_STREET'],
                'TXT_IMMO_ZIP'                         =>    $_ARRAYLANG['TXT_IMMO_ZIP'],
                'TXT_IMMO_LOCATION'                     =>    $_ARRAYLANG['TXT_IMMO_LOCATION'],
                'TXT_IMMO_TELEPHONE'                 =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE'],
                'TXT_IMMO_TELEPHONE_OFFICE'             =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE_OFFICE'],
                'TXT_IMMO_TELEPHONE_MOBILE'             =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE_MOBILE'],
                'TXT_IMMO_PURCHASE'                     =>    $_ARRAYLANG['TXT_IMMO_PURCHASE'],
                'TXT_IMMO_FUNDING'                     =>    $_ARRAYLANG['TXT_IMMO_FUNDING'],
                'TXT_IMMO_COMMENT'                     =>    $_ARRAYLANG['TXT_IMMO_COMMENT'],
                'TXT_IMMO_TIMESTAMP'                 =>    $_ARRAYLANG['TXT_IMMO_TIMESTAMP'],
                'TXT_IMMO_EXPORT'                           =>    $_ARRAYLANG['TXT_IMMO_EXPORT'],
                'TXT_IMMO_FUNCTIONS'                    =>    $_ARRAYLANG['TXT_IMMO_FUNCTIONS'],
                'TXT_IMMO_CONFIRM_DELETE_CONTACT'    =>    $_ARRAYLANG['TXT_IMMO_CONFIRM_DELETE_CONTACT'],
                'TXT_IMMO_CANNOT_UNDO_OPERATION'    =>    $_ARRAYLANG['TXT_IMMO_CANNOT_UNDO_OPERATION'],
                'CALENDAR_TODAY'                     => !empty($_SESSION['immo']['startDate']) ? $_SESSION['immo']['startDate'] : date('Y-m-d', strtotime('-1 month')),
                'CALENDAR_NEXT_MONTH'                 => !empty($_SESSION['immo']['endDate']) ? $_SESSION['immo']['endDate'] : date('Y-m-d'),
                'IMMO_FORM_ACTION_ID'                 => $immoID,
                'IMMO_ID'                             => $immoID,
        ));




        $searchTerm = (!empty($_REQUEST['search'])) ? " LIKE '%".contrexx_addslashes($_REQUEST['search'])."%'" : ' TRUE';
        $searchField = (!empty($_REQUEST['searchField']) && !empty($_REQUEST['search'])) ? ' WHERE '.contrexx_addslashes($_REQUEST['searchField']) : ' WHERE ';
        $query = "    SELECT     `interest`.`id` , `email` , `name` , `firstname` , `street` , `zip` , `location` ,
                            `phone_home` , `phone_office` , `phone_mobile` , `comment` , `interest`.`immo_id` , `time` as `timestamp`,
                            content1.fieldvalue AS immo_header, content2.fieldvalue AS immo_address, content3.fieldvalue AS immo_location
                    FROM `".DBPREFIX."module_immo_interest` AS interest
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content1 ON content1.immo_id = interest.id
                        AND content1.lang_id =1
                        AND content1.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = '".$this->_headline."'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content2 ON content2.immo_id = interest.id
                        AND content2.lang_id =1
                        AND content2.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'adresse'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content3 ON content3.immo_id = interest.id
                        AND content3.lang_id =1
                        AND content3.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'ort'
                            AND fname.lang_id =1
                        )"
                .$searchField." ".$searchTerm;

        if($immoID > 0) {
            $query .= " AND interest.immo_id = $immoID ";
        }


        if(empty($_REQUEST['ignore_timespan']) && !empty($_SESSION['immo']['startDate'])) {
            $query .= " AND `time` BETWEEN ".strtotime($_SESSION['immo']['startDate'])." AND ".strtotime($_SESSION['immo']['endDate'])." ORDER BY `time` DESC";
        }
        if(($objRS = $objDatabase->Execute($query)) !== false) {
            $count = $objRS->RecordCount();
            $objRS = $objDatabase->SelectLimit($query, $limit, $pos);
            while(!$objRS->EOF) {
                $this->_objTpl->setVariable(array(
                        'IMMO_CONTACT_ID'        =>    intval($objRS->fields['id']),
                        'IMMO_IMMO_ID'            =>    htmlspecialchars($objRS->fields['immo_id']),
                        'IMMO_OBJECT_HEADER'    =>    htmlspecialchars($objRS->fields['immo_header']),
                        'IMMO_OBJECT_ADDRESS'    =>    htmlspecialchars($objRS->fields['immo_address']),
                        'IMMO_OBJECT_LOCATION'    =>    htmlspecialchars($objRS->fields['immo_location']),
                        'IMMO_EMAIL'            =>    htmlspecialchars($objRS->fields['email']),
                        'IMMO_NAME'                =>    htmlspecialchars($objRS->fields['name']),
                        'IMMO_FIRSTNAME'        =>    htmlspecialchars($objRS->fields['firstname']),
                        'IMMO_STREET'            =>    htmlspecialchars($objRS->fields['street']),
                        'IMMO_ZIP'                =>    htmlspecialchars($objRS->fields['zip']),
                        'IMMO_LOCATION'            =>    htmlspecialchars($objRS->fields['location']),
                        'IMMO_TELEPHONE'        =>    htmlspecialchars($objRS->fields['phone_home']),
                        'IMMO_TELEPHONE_OFFICE'    =>    htmlspecialchars($objRS->fields['phone_office']),
                        'IMMO_TELEPHONE_MOBILE'    =>    htmlspecialchars($objRS->fields['phone_mobile']),
                        'IMMO_COMMENT'            =>    str_replace(array("\r\n", "\n"), '<br />', htmlspecialchars($objRS->fields['comment'])),
                        'IMMO_COMMENT_TEXT'        =>    str_replace(array("\r\n", "\n"), '<br />', htmlspecialchars($objRS->fields['comment'])),
                        'IMMO_TIMESTAMP'        =>    date(ASCMS_DATE_FORMAT, $objRS->fields['timestamp']),
                        'ROW_CLASS'                =>    ($rowclass++ % 2 == 0) ? 'row1' : 'row2',
                ));
                $this->_objTpl->parse('commentsArray');
                $this->_objTpl->parse('downloads');
                $objRS->MoveNext();
            }
        }



        $this->_objTpl->setVariable(array(
                'IMMO_STATS_INTERESTS_PAGING'    => getPaging($count, $pos, '&amp;cmd=immo&amp;act=interests&amp;limit='.$limit, '', true),
        ));
    }


    /**
     * export the requested contacts to a CSV file and send it to the browser
     *
     * @return void
     */
    function _exportContacts() {
        global $objDatabase, $_ARRAYLANG;
        $separator = ';';
        switch($_REQUEST['type']) {
            case 'downloads':
                $query = "  SELECT  `email`, `name`, `firstname`, `company`, `street`, `zip`, `location`,
                                    `telephone`, `telephone_office`, `telephone_mobile`, `purchase`, `funding`,
                                    `comment`, `timestamp`
                            FROM `".DBPREFIX."module_immo_contact`";
                if(!empty($_SESSION['immo']['startDate'])) {
                    $query .= " WHERE `timestamp` BETWEEN ".strtotime($_SESSION['immo']['startDate'])." AND ".strtotime($_SESSION['immo']['endDate']);
                }
                $immoid = !empty($_REQUEST['immo_id']) ? intval($_REQUEST['immo_id']) : 0;
                if(!empty($immoid)) {
                    $query .= " AND immo_id = $immoid";
                }

                $query .=  " ORDER BY `timestamp`";
                $CSVfields = $fields = '';
                $cols = array(        'email', 'name', 'firstname', 'company', 'street', 'zip', 'location',
                        'telephone', 'telephone_office', 'telephone_mobile', 'purchase', 'funding',
                        'comment','timestamp');

                break;
            case 'interests':
                $query = "  SELECT     `immo`.`reference`, `name`, `firstname`, `street`, `zip`, `location` ,
                                    `email`, `phone_office`, `phone_home`, `phone_mobile`, `doc_via_mail`,
                                    `funding_advice`, `inspection`, `contact_via_phone`, `comment` ,`time`
                            FROM `".DBPREFIX."module_immo_interest` AS `interest`
                            LEFT JOIN `".DBPREFIX."module_immo` AS `immo` ON `interest`.`immo_id` = `immo`.`id`";
                if(!empty($_SESSION['immo']['startDate'])) {
                    $query .= " WHERE `time` BETWEEN ".strtotime($_SESSION['immo']['startDate'])." AND ".strtotime($_SESSION['immo']['endDate']);
                }
                $immoid = !empty($_REQUEST['immo_id']) ? intval($_REQUEST['immo_id']) : 0;

                if(!empty($immoid)) {
                    $query .= " AND immo_id = $immoid";
                }

                $query .= " ORDER BY `time`";
                $CSVfields = $fields = '';
                $cols = array(        'reference', 'name', 'firstname', 'street', 'zip', 'location',
                        'email', 'phone_office', 'phone_home', 'phone_mobile', 'doc_via_mail',
                        'funding_advice', 'inspection', 'contact_via_phone', 'comment', 'time' );
                break;
            default:
                $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_WRONG_TYPE_FOR_EXPORT'];
                $this->_showStats();
                return false;
                break;
        }

        foreach ($cols as $field) {
            $CSVfields .= $this->_escapeCsvValue($_ARRAYLANG['TXT_IMMO_'.strtoupper($field)]).$separator;
        }
        $CSVfields = substr($CSVfields, 0, -1).$this->_lineBreak;

        if(($objRS = $objDatabase->Execute($query)) !== false) {
            $i = 0;
            while(!$objRS->EOF) {
                foreach ($objRS->fields as $fieldName => $fieldContent) {
                    $CSVdata .= $this->_escapeCsvValue(($fieldName != 'timestamp' && $fieldName != 'time') ? $fieldContent : date(ASCMS_DATE_FORMAT, $fieldContent)).$separator;
                }
                $CSVdata .= $this->_lineBreak;
                $objRS->moveNext();
            }
        }

        header("Content-Type: text/comma-separated-values", true);
        header("Content-Length: ".strlen($CSVdata));
        header("Content-MD5: ".md5($CSVdata));
        header("Content-Disposition: inline; filename=\"".'immo_stats_contact_'.date('Y-M-D H_m_s', mktime()).".csv\"");
        die($CSVfields.$CSVdata);
    }

    /**
     * show interest details
     *
     * @return void
     */
    function _showInterestDetails() {
        global $_ARRAYLANG, $objDatabase;
        $this->_pageTitle = $_ARRAYLANG['TXT_IMMO_DOWNLOAD_DETAILS'];
        $this->_objTpl->loadTemplateFile('module_immo_interest_details.html');
        $interestID = intval($_GET['id']);
        $this->_objTpl->setVariable(array(
                'TXT_IMMO_CONTACT_DETAILS'             =>    $_ARRAYLANG['TXT_IMMO_CONTACT_DETAILS'],
                'TXT_IMMO_EMAIL'                     =>    $_ARRAYLANG['TXT_IMMO_EMAIL'],
                'TXT_IMMO_NAME'                         =>    $_ARRAYLANG['TXT_IMMO_NAME'],
                'TXT_IMMO_FIRSTNAME'                 =>    $_ARRAYLANG['TXT_IMMO_FIRSTNAME'],
                'TXT_IMMO_COMPANY'                     =>    $_ARRAYLANG['TXT_IMMO_COMPANY'],
                'TXT_IMMO_STREET'                     =>    $_ARRAYLANG['TXT_IMMO_STREET'],
                'TXT_IMMO_ZIP'                         =>    $_ARRAYLANG['TXT_IMMO_ZIP'],
                'TXT_IMMO_LOCATION'                     =>    $_ARRAYLANG['TXT_IMMO_LOCATION'],
                'TXT_IMMO_TELEPHONE'                 =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE'],
                'TXT_IMMO_TELEPHONE_OFFICE'             =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE_OFFICE'],
                'TXT_IMMO_TELEPHONE_MOBILE'             =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE_MOBILE'],
                'TXT_IMMO_DOC_VIA_MAIL'                 =>    $_ARRAYLANG['TXT_IMMO_DOC_VIA_MAIL'],
                'TXT_IMMO_FUNDING_ADVICE'             =>    $_ARRAYLANG['TXT_IMMO_FUNDING_ADVICE'],
                'TXT_IMMO_INSPECTION'                 =>    $_ARRAYLANG['TXT_IMMO_INSPECTION'],
                'TXT_IMMO_CONTACT_VIA_PHONE'         =>    $_ARRAYLANG['TXT_IMMO_CONTACT_VIA_PHONE'],
                'TXT_IMMO_COMMENT'                     =>    $_ARRAYLANG['TXT_IMMO_COMMENT'],
                'TXT_IMMO_TIMESTAMP'                 =>    $_ARRAYLANG['TXT_IMMO_TIMESTAMP'],
                'TXT_IMMO_BACK'                         =>    $_ARRAYLANG['TXT_IMMO_BACK'],
                'TXT_IMMO_OBJECT_DETAILS'             =>    $_ARRAYLANG['TXT_IMMO_OBJECT_DETAILS'],
        ));


        $query = "    SELECT     `interest`.`id`, `interest`.`immo_id`, `name`, `firstname`, `street`, `zip`, `location`, `email`,
                            `phone_office`, `phone_home`, `phone_mobile`, `doc_via_mail`, `funding_advice`,
                            `inspection`, `contact_via_phone`, `comment`, `time`, content1.fieldvalue AS immo_header,
                            content2.fieldvalue AS immo_address, content3.fieldvalue AS immo_location
                    FROM `".DBPREFIX."module_immo_interest` AS interest
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content1 ON content1.immo_id = interest.immo_id
                        AND content1.lang_id =1
                        AND content1.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = '".$this->_headline."'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content2 ON content2.immo_id = interest.immo_id
                        AND content2.lang_id =1
                        AND content2.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'adresse'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content3 ON content3.immo_id = interest.immo_id
                        AND content3.lang_id =1
                        AND content3.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'ort'
                            AND fname.lang_id =1
                        )
                    WHERE interest.id = $interestID";
        if(($objRS = $objDatabase->SelectLimit($query, 1))) {
            $this->_objTpl->setVariable(array(
                    'IMMO_OBJECT_DETAILS'        =>  htmlspecialchars($objRS->fields['immo_header']
                    .', '.htmlspecialchars($objRS->fields['immo_address'])
                    .', '.htmlspecialchars($objRS->fields['immo_location'])),
                    'IMMO_EMAIL'                =>    htmlspecialchars($objRS->fields['email']),
                    'IMMO_NAME'                    =>    htmlspecialchars($objRS->fields['name']),
                    'IMMO_FIRSTNAME'            =>    htmlspecialchars($objRS->fields['firstname']),
                    'IMMO_STREET'                =>    htmlspecialchars($objRS->fields['street']),
                    'IMMO_ZIP'                    =>    htmlspecialchars($objRS->fields['zip']),
                    'IMMO_LOCATION'                =>    htmlspecialchars($objRS->fields['location']),
                    'IMMO_TELEPHONE'            =>    htmlspecialchars($objRS->fields['phone_home']),
                    'IMMO_TELEPHONE_OFFICE'        =>    htmlspecialchars($objRS->fields['phone_office']),
                    'IMMO_TELEPHONE_MOBILE'        =>    htmlspecialchars($objRS->fields['phone_mobile']),
                    'IMMO_DOC_VIA_MAIL'            =>    $objRS->fields['doc_via_mail'] == 1 ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'],
                    'IMMO_FUNDING_ADVICE'        =>    $objRS->fields['funding_advice'] == 1 ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'],
                    'IMMO_INSPECTION'            =>    $objRS->fields['inspection'] == 1 ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'],
                    'IMMO_CONTACT_VIA_PHONE'    =>    $objRS->fields['contact_via_phone'] == 1 ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'],
                    'IMMO_COMMENT'                =>    nl2br(htmlspecialchars($objRS->fields['comment'])),
                    'IMMO_TIMESTAMP'            =>    date(ASCMS_DATE_FORMAT ,$objRS->fields['time']),
                    'ROW_CLASS'                    =>    ($rowclass++ % 2 == 0) ? 'row1' : 'row2',
            ));
        }
    }


    /**
     * show contact details
     *
     * @return void
     */
    function _showContactDetails() {
        global $_ARRAYLANG, $objDatabase;
        $this->_pageTitle = $_ARRAYLANG['TXT_IMMO_CONTACT_DETAILS'];
        $this->_objTpl->loadTemplateFile('module_immo_contact_details.html');
        $contactID = intval($_GET['id']);

        $this->_objTpl->setVariable(array(

                'TXT_IMMO_OBJECT_DETAILS'             =>    $_ARRAYLANG['TXT_IMMO_OBJECT_DETAILS'],
                'TXT_IMMO_CONTACT_DETAILS'             =>    $_ARRAYLANG['TXT_IMMO_CONTACT_DETAILS'],
                'TXT_IMMO_EMAIL'                     =>    $_ARRAYLANG['TXT_IMMO_EMAIL'],
                'TXT_IMMO_NAME'                         =>    $_ARRAYLANG['TXT_IMMO_NAME'],
                'TXT_IMMO_FIRSTNAME'                 =>    $_ARRAYLANG['TXT_IMMO_FIRSTNAME'],
                'TXT_IMMO_COMPANY'                     =>    $_ARRAYLANG['TXT_IMMO_COMPANY'],
                'TXT_IMMO_STREET'                     =>    $_ARRAYLANG['TXT_IMMO_STREET'],
                'TXT_IMMO_ZIP'                         =>    $_ARRAYLANG['TXT_IMMO_ZIP'],
                'TXT_IMMO_LOCATION'                     =>    $_ARRAYLANG['TXT_IMMO_LOCATION'],
                'TXT_IMMO_TELEPHONE'                 =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE'],
                'TXT_IMMO_TELEPHONE_OFFICE'             =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE_OFFICE'],
                'TXT_IMMO_TELEPHONE_MOBILE'             =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE_MOBILE'],
                'TXT_IMMO_PURCHASE'                     =>    $_ARRAYLANG['TXT_IMMO_PURCHASE'],
                'TXT_IMMO_FUNDING'                     =>    $_ARRAYLANG['TXT_IMMO_FUNDING'],
                'TXT_IMMO_COMMENT'                     =>    $_ARRAYLANG['TXT_IMMO_COMMENT'],
                'TXT_IMMO_TIMESTAMP'                 =>    $_ARRAYLANG['TXT_IMMO_TIMESTAMP'],
                'TXT_IMMO_BACK'                         =>    $_ARRAYLANG['TXT_IMMO_BACK'],
        ));


        $query = "    SELECT     `contact`.`id` , `email` , `name` , `firstname` , `street` , `zip` , `location` ,
                            `company` , `telephone` , `telephone_office` , `telephone_mobile` , `purchase` ,
                            `funding` ,  `comment` , `contact`.`immo_id` , `timestamp`, content1.fieldvalue AS immo_header,
                            content2.fieldvalue AS immo_address, content3.fieldvalue AS immo_location
                    FROM `".DBPREFIX."module_immo_contact` AS contact
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content1 ON content1.immo_id = contact.immo_id
                        AND content1.lang_id =1
                        AND content1.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = '".$this->_headline."'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content2 ON content2.immo_id = contact.immo_id
                        AND content2.lang_id =1
                        AND content2.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'adresse'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content3 ON content3.immo_id = contact.immo_id
                        AND content3.lang_id =1
                        AND content3.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'ort'
                            AND fname.lang_id =1
                        )
                    WHERE contact.id = $contactID";
        if(($objRS = $objDatabase->SelectLimit($query, 1))) {
            $this->_objTpl->setVariable(array(
                    'IMMO_OBJECT_DETAILS'    =>  htmlspecialchars($objRS->fields['immo_header']
                    .', '.htmlspecialchars($objRS->fields['immo_address'])
                    .', '.htmlspecialchars($objRS->fields['immo_location'])),
                    'IMMO_EMAIL'            =>    htmlspecialchars($objRS->fields['email']),
                    'IMMO_NAME'                =>    htmlspecialchars($objRS->fields['name']),
                    'IMMO_FIRSTNAME'        =>    htmlspecialchars($objRS->fields['firstname']),
                    'IMMO_COMPANY'            =>    htmlspecialchars($objRS->fields['company']),
                    'IMMO_STREET'            =>    htmlspecialchars($objRS->fields['street']),
                    'IMMO_ZIP'                =>    htmlspecialchars($objRS->fields['zip']),
                    'IMMO_LOCATION'            =>    htmlspecialchars($objRS->fields['location']),
                    'IMMO_TELEPHONE'        =>    htmlspecialchars($objRS->fields['telephone']),
                    'IMMO_TELEPHONE_OFFICE'    =>    htmlspecialchars($objRS->fields['telephone_office']),
                    'IMMO_TELEPHONE_MOBILE'    =>    htmlspecialchars($objRS->fields['telephone_mobile']),
                    'IMMO_PURCHASE'            =>    $objRS->fields['purchase'] == 1 ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'],
                    'IMMO_FUNDING'            =>    $objRS->fields['funding'] == 1 ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'],
                    'IMMO_COMMENT'            =>    htmlspecialchars($objRS->fields['comment']),
                    'IMMO_TIMESTAMP'        =>    date(ASCMS_DATE_FORMAT ,$objRS->fields['timestamp']),
                    'ROW_CLASS'                =>    ($rowclass++ % 2 == 0) ? 'row1' : 'row2',
            ));
        }
    }


    /**
     * Prepare value for insertion into a csv file.
     *
     * @param string $value
     * @return string
     */
    function _escapeCsvValue($value) {
        $value = preg_replace('/\r\n/', "\n", $value);
        $valueModified = str_replace('"', '""', $value);

        if ($valueModified != $value || preg_match('/[;\n]+/', $value)) {
            $value = '"'.$valueModified.'"';
        }
        return $value;
    }


    /**
     * show statistics
     *
     * @return void
     */
    function _showStats() {
        global $_ARRAYLANG, $objDatabase, $_CONFIG;
        JS::activate('jqueryui');
        $this->_pageTitle = $_ARRAYLANG['TXT_IMMO_STATS'];
        $this->_objTpl->loadTemplateFile('module_immo_stats.html');
        //paging data
        $limit = !empty($_REQUEST['limit']) ? intval($_REQUEST['limit']) : $_CONFIG['corePagingLimit'];
        $pos = !empty($_REQUEST['pos']) ? intval($_REQUEST['pos']) : 0;
        $field = (!empty($_REQUEST['field'])) ? contrexx_addslashes($_REQUEST['field']) : 'visits';
        $order = (!empty($_REQUEST['order'])) ? contrexx_addslashes($_REQUEST['order']) : 'asc';
        $hsearchField = !empty($_REQUEST['searchField']) ? $_REQUEST['searchField'] : '' ;
        $hsearch = !empty($_REQUEST['search']) ? contrexx_addslashes($_REQUEST['search']) : '' ;

        if(empty($_REQUEST['tab'])) {
            $_REQUEST['tab'] = 'immo_downloads';
        }

        $_SESSION['immo']['startDate']    = !empty($_REQUEST['inputStartDate']) ? contrexx_addslashes($_REQUEST['inputStartDate'])  : $_SESSION['immo']['startDate'];
        $_SESSION['immo']['endDate']    = !empty($_REQUEST['inputEndDate']) ? contrexx_addslashes($_REQUEST['inputEndDate'])  : $_SESSION['immo']['endDate'];

        $this->_objTpl->setGlobalVariable(array(
                'TXT_IMMO_PAGE_VIEWS'                =>    $_ARRAYLANG['TXT_IMMO_PAGE_VIEWS'],
                'TXT_IMMO_DOWNLOADS'                 =>    $_ARRAYLANG['TXT_IMMO_DOWNLOADS'],
                'TXT_IMMO_OBJECT'                     =>    $_ARRAYLANG['TXT_IMMO_OBJECT'],
                'TXT_IMMO_VISITS'                     =>    $_ARRAYLANG['TXT_IMMO_VISITS'],
                'TXT_IMMO_HEADER'                     =>    $_ARRAYLANG['TXT_IMMO_HEADER'],
                'TXT_IMMO_LOCATION'                     =>    $_ARRAYLANG['TXT_IMMO_LOCATION'],
                'TXT_IMMO_SEARCH'                     =>    $_ARRAYLANG['TXT_IMMO_SEARCH'],
                'TXT_IMMO_DOWNLOAD_SEARCH'             =>    $_ARRAYLANG['TXT_IMMO_DOWNLOAD_SEARCH'],
                'TXT_IMMO_SORT'                         =>    $_ARRAYLANG['TXT_IMMO_SORT'],
                'TXT_IMMO_IMMO_ID'                     =>    $_ARRAYLANG['TXT_IMMO_IMMO_ID'],
                'TXT_IMMO_EMAIL'                     =>    $_ARRAYLANG['TXT_IMMO_EMAIL'],
                'TXT_IMMO_NAME'                         =>    $_ARRAYLANG['TXT_IMMO_NAME'],
                'TXT_IMMO_FIRSTNAME'                 =>    $_ARRAYLANG['TXT_IMMO_FIRSTNAME'],
                'TXT_IMMO_COMPANY'                     =>    $_ARRAYLANG['TXT_IMMO_COMPANY'],
                'TXT_IMMO_STREET'                     =>    $_ARRAYLANG['TXT_IMMO_STREET'],
                'TXT_IMMO_ZIP'                         =>    $_ARRAYLANG['TXT_IMMO_ZIP'],
                'TXT_IMMO_LOCATION'                     =>    $_ARRAYLANG['TXT_IMMO_LOCATION'],
                'TXT_IMMO_TELEPHONE'                 =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE'],
                'TXT_IMMO_TELEPHONE_OFFICE'             =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE_OFFICE'],
                'TXT_IMMO_TELEPHONE_MOBILE'             =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE_MOBILE'],
                'TXT_IMMO_PURCHASE'                     =>    $_ARRAYLANG['TXT_IMMO_PURCHASE'],
                'TXT_IMMO_FUNDING'                     =>    $_ARRAYLANG['TXT_IMMO_FUNDING'],
                'TXT_IMMO_COMMENT'                     =>    $_ARRAYLANG['TXT_IMMO_COMMENT'],
                'TXT_IMMO_TIMESTAMP'                 =>    $_ARRAYLANG['TXT_IMMO_TIMESTAMP'],
                'TXT_IMMO_EXPORT'                           =>    $_ARRAYLANG['TXT_IMMO_EXPORT'],
                'TXT_IMMO_FUNCTIONS'                    =>    $_ARRAYLANG['TXT_IMMO_FUNCTIONS'],
                'TXT_IMMO_SEPARATOR'                    =>    $_ARRAYLANG['TXT_IMMO_SEPARATOR'],
                'TXT_IMMO_EDIT'                            =>    $_ARRAYLANG['TXT_IMMO_EDIT'],
                'TXT_IMMO_DELETE'                        =>    $_ARRAYLANG['TXT_IMMO_DELETE'],
                'TXT_IMMO_SHOW_OBJECT_IN_NEW_WINDOW' =>    $_ARRAYLANG['TXT_IMMO_SHOW_OBJECT_IN_NEW_WINDOW'],
                'TXT_IMMO_CONFIRM_DELETE_CONTACT'    =>    $_ARRAYLANG['TXT_IMMO_CONFIRM_DELETE_CONTACT'],
                'TXT_IMMO_CANNOT_UNDO_OPERATION'     =>    $_ARRAYLANG['TXT_IMMO_CANNOT_UNDO_OPERATION'],
                'TXT_IMMO_COUNT'                      =>    $_ARRAYLANG['TXT_IMMO_COUNT'],
                'TXT_IMMO_REF_NOTE'                  =>    $_ARRAYLANG['TXT_IMMO_REF_NOTE'],
                'TXT_IMMO_REFERENCE_NUMBER'              =>    $_ARRAYLANG['TXT_IMMO_REFERENCE_NUMBER'],
                'TXT_IMMO_HEADER'                      =>    $_ARRAYLANG['TXT_IMMO_HEADER'],
                'TXT_IMMO_LINKNAME'                      =>    $_ARRAYLANG['TXT_IMMO_LINKNAME'],
                'TXT_IMMO_TIMESPAN'                      =>    $_ARRAYLANG['TXT_IMMO_TIMESPAN'],
                'TXT_IMMO_FROM'                          =>    $_ARRAYLANG['TXT_IMMO_FROM'],
                'TXT_IMMO_TO'                          =>    $_ARRAYLANG['TXT_IMMO_TO'],
                'TXT_IMMO_INTERESTS'                 =>    $_ARRAYLANG['TXT_IMMO_INTERESTS'],
                'TXT_IMMO_DOWNLOAD_LIST'              =>    $_ARRAYLANG['TXT_IMMO_DOWNLOAD_LIST'],
                'TXT_IMMO_INTEREST_SEARCH'              =>    $_ARRAYLANG['TXT_IMMO_INTEREST_SEARCH'],
                'TXT_IMMO_SHOW_TIMESPAN_DETAILS'     =>    $_ARRAYLANG['TXT_IMMO_SHOW_TIMESPAN_DETAILS'],
                'TXT_IMMO_IGNORE_TIMESPAN'             =>    $_ARRAYLANG['TXT_IMMO_IGNORE_TIMESPAN'],
                'TXT_IMMO_REFRESH'                     =>    $_ARRAYLANG['TXT_IMMO_REFRESH'],
                'CALENDAR_TODAY'                     => !empty($_SESSION['immo']['startDate']) ? $_SESSION['immo']['startDate'] : date('Y-m-d', strtotime('-1 month')),
                'CALENDAR_NEXT_MONTH'                 => !empty($_SESSION['immo']['endDate']) ? $_SESSION['immo']['endDate'] : date('Y-m-d'),
                'IMMO_IGNORE_TIMESPAN_CHECKED'         => empty($_REQUEST['ignore_timespan']) ? '' : 'checked="checked"',
                'PATH_OFFSET'                         => ASCMS_PATH_OFFSET,
                'IMMO_PAGING_LIMIT'                     =>    $limit,
                'IMMO_PAGING_POS'                     =>    $pos,
                'IMMO_PAGING_FIELD'                     =>    $field,
                'IMMO_HSEARCH_FIELD'                 =>    $hsearchField,
                'IMMO_HSEARCH'                         =>    $hsearch,
                'IMMO_VISIBLE_TAB'                     =>    $_REQUEST['tab'],
                'IMMO_DOWNLOADS_VISIBLE'             =>    ($_REQUEST['tab'] == 'immo_downloads') ? 'style="display: block;"' : 'style="display: none;"',
                'IMMO_INTERESTS_VISIBLE'             =>    ($_REQUEST['tab'] == 'immo_interests') ? 'style="display: block;"' : 'style="display: none;"',
                'IMMO_PAGEVIEWS_VISIBLE'             =>    ($_REQUEST['tab'] == 'immo_pageviews') ? 'style="display: block;"' : 'style="display: none;"',
                'IMMO_DOWNLOADS_TAB_ACTIVE'             => ($_REQUEST['tab'] == 'immo_downloads') ? 'class="active"' : '',
                'IMMO_INTERESTS_TAB_ACTIVE'             => ($_REQUEST['tab'] == 'immo_interests') ? 'class="active"' : '',
                'IMMO_PAGEVIEWS_TAB_ACTIVE'             => ($_REQUEST['tab'] == 'immo_pageviews') ? 'class="active"' : '',


        ));


        $rowclass = 2;
        //get object request stats
        $query = "    SELECT page, visits, content1.fieldvalue AS header, content2.fieldvalue AS location, immo.reference as reference, immo.ref_nr_note as ref_note
                    FROM `".DBPREFIX."stats_requests`
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content1 ON content1.immo_id = CAST(MID(`page`, 40, 8 ) AS UNSIGNED)
                        AND content1.lang_id =1
                        AND content1.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = '".$this->_headline."'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content2 ON content2.immo_id = CAST(MID(`page`, 40, 8) AS UNSIGNED)
                        AND content2.lang_id =1
                        AND content2.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'ort'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo` AS immo ON immo.id = CAST(MID(`page`, 40, 8) AS UNSIGNED)
                    WHERE page LIKE '/index.php?section=immo&cmd=showObj&id=%'";
        if(empty($_REQUEST['ignore_timespan']) && !empty($_SESSION['immo']['startDate'])) {
            $query .= " AND `timestamp` BETWEEN ".strtotime($_SESSION['immo']['startDate'])." AND ".strtotime($_SESSION['immo']['endDate']);
        }
        $query .= " GROUP BY reference
                    ORDER BY ".$field." DESC";
        if(($objRS = $objDatabase->Execute($query)) !== false) {
            $count = $objRS->RecordCount();
            $objRS = $objDatabase->SelectLimit($query, $limit, $pos);
            $i = 0;
            while(!$objRS->EOF) {
                $split = explode("&", $objRS->fields['page']);
                $immoID = intval($split[0]);

                $this->_objTpl->setVariable(array(
                        'IMMO_OBJECT_NAME'        =>    htmlspecialchars($objRS->fields['page']),
                        'IMMO_VISITS'            =>    $objRS->fields['visits'],
                        'IMMO_OBJECT_HEADER'    =>    htmlspecialchars($objRS->fields['header']),
                        'IMMO_OBJECT_LOCATION'    =>    htmlspecialchars($objRS->fields['location']),
                        'IMMO_OBJECT_REFERENCE'    =>    !empty($objRS->fields['reference']) ? htmlspecialchars($objRS->fields['reference']) : 'N/A',
                        'IMMO_OBJECT_REF_NOTE'    =>    !empty($objRS->fields['ref_note']) ? htmlspecialchars($objRS->fields['ref_note']) : 'N/A',
                        'ROW_CLASS'                =>    ($rowclass++ % 2 == 0) ? 'row1' : 'row2',
                ));
                $this->_objTpl->parse('pageVisits');
                $objRS->MoveNext();
            }
        }else {
            die("db error.".$objDatabase->ErrorMsg());
        }
        $this->_objTpl->setVariable(array(
                'IMMO_STATS_PAGEVIEW_PAGING'    => getPaging($count, $pos, '&amp;cmd=immo&amp;act=stats&amp;tab=1&amp;limit='.$limit, '', true),
        ));

        $rowclass = 2;
        //get protected link donload stats
        $query = "    SELECT count( 1 ) AS cnt, immo.id as immo_id, immo.reference, immo.ref_nr_note, a.fieldvalue AS header, b.name AS linkname
                    FROM `".DBPREFIX."module_immo_contact`
                        AS contact
                    LEFT JOIN ".DBPREFIX."module_immo
                        AS immo
                        ON ( contact.immo_id = immo.id )
                    LEFT JOIN ".DBPREFIX."module_immo_content
                        AS a
                        ON ( a.immo_id = contact.immo_id )
                    LEFT JOIN ".DBPREFIX."module_immo_fieldname
                        AS fn
                        ON ( a.field_id = fn.field_id )
                    LEFT JOIN ".DBPREFIX."module_immo_fieldname
                        AS b
                        ON ( b.field_id = contact.field_id )
                    WHERE lower( fn.name ) = '".$this->_headline."'
                    AND fn.lang_id = 1
                    AND a.lang_id = 1
                    AND b.lang_id = 1";
        if(empty($_REQUEST['ignore_timespan']) && !empty($_SESSION['immo']['startDate'])) {
            $query .= " AND `timestamp` BETWEEN ".strtotime($_SESSION['immo']['startDate'])." AND ".strtotime($_SESSION['immo']['endDate']);
        }
        $query .= " GROUP BY contact.immo_id
                    ORDER BY cnt DESC";
        if(($objRS = $objDatabase->Execute($query)) !== false) {
            $count = $objRS->RecordCount();
            $objRS = $objDatabase->SelectLimit($query, $limit, $pos);
            while(!$objRS->EOF) {
                $this->_objTpl->setVariable(array(
                        'IMMO_DL_COUNT'            =>    intval($objRS->fields['cnt']),
                        'IMMO_DL_REF_NOTE'        =>    htmlspecialchars($objRS->fields['ref_nr_note']),
                        'IMMO_DL_REFERENCE'        =>    htmlspecialchars($objRS->fields['reference']),
                        'IMMO_DL_HEADER'        =>    htmlspecialchars($objRS->fields['header']),
                        'IMMO_DL_LINKNAME'        =>    htmlspecialchars($objRS->fields['linkname']),
                        'IMMO_DL_IMMO_ID'        =>    intval($objRS->fields['immo_id']),
                        'ROW_CLASS'                =>    ($rowclass++ % 2 == 0) ? 'row1' : 'row2',
                ));
                $this->_objTpl->parse('downloads');
                $i++;
                $objRS->MoveNext();
            }
        }
        $this->_objTpl->setVariable(array(
                'IMMO_STATS_DOWNLOADS_PAGING'    => getPaging($count, $pos, '&amp;cmd=immo&amp;act=stats&amp;limit='.$limit, '', true),
        ));


        $query = "    SELECT count( `interest`.`immo_id` ) AS cnt, immo.id AS immo_id, immo.reference, immo.ref_nr_note, a.fieldvalue AS header
                    FROM `".DBPREFIX."module_immo_interest` AS interest
                    LEFT JOIN ".DBPREFIX."module_immo AS immo ON ( interest.immo_id = immo.id )
                    LEFT JOIN ".DBPREFIX."module_immo_content AS a ON ( a.immo_id = interest.immo_id )
                    LEFT JOIN ".DBPREFIX."module_immo_fieldname AS fn ON ( a.field_id = fn.field_id )
                    WHERE lower( fn.name ) = '".$this->_headline."'
                    AND fn.lang_id =1
                    AND a.lang_id =1";
        if(empty($_REQUEST['ignore_timespan']) && !empty($_SESSION['immo']['startDate'])) {
            $query .= " AND `time` BETWEEN ".strtotime($_SESSION['immo']['startDate'])." AND ".strtotime($_SESSION['immo']['endDate']);
        }
        $query .= " GROUP BY `interest`.`immo_id`
                    ORDER BY cnt DESC ";
        if(($objRS = $objDatabase->Execute($query)) !== false) {
            $count = $objRS->RecordCount();
            $objRS = $objDatabase->SelectLimit($query, $limit, $pos);
            while(!$objRS->EOF) {
                $this->_objTpl->setVariable(array(
                        'IMMO_INTEREST_COUNT'        =>    intval($objRS->fields['cnt']),
                        'IMMO_INTEREST_REF_NOTE'    =>    htmlspecialchars($objRS->fields['ref_nr_note']),
                        'IMMO_INTEREST_REFERENCE'    =>    htmlspecialchars($objRS->fields['reference']),
                        'IMMO_INTEREST_HEADER'        =>    htmlspecialchars($objRS->fields['header']),
                        'IMMO_INTEREST_IMMO_ID'        =>    intval($objRS->fields['immo_id']),
                        'ROW_CLASS'                    =>    ($rowclass++ % 2 == 0) ? 'row1' : 'row2',
                ));
                $this->_objTpl->parse('interests');
                $i++;
                $objRS->MoveNext();
            }
        }
        $this->_objTpl->setVariable(array(
                'IMMO_STATS_INTERESTS_PAGING'    => getPaging($count, $pos, '&amp;cmd=immo&amp;act=stats&amp;limit='.$limit, '', true),
        ));
    }


    /**
     * show protected link downloads
     *
     * @return void
     */
    function _showDownloads() {
        global $_ARRAYLANG, $objDatabase, $_CONFIG;
        JS::activate('jqueryui');
        //delete if $_GET['del'] is set
        $contactID = intval($_GET['del']);
        if(!empty($contactID)) {
            if($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_immo_contact WHERE id = $contactID") !== false) {
                $this->_strOkMessage = $_ARRAYLANG['TXT_IMMO_SUCCESSFULLY_DELETED'];
                $this->_showStats();
                return true;
            }
        }
        $this->_pageTitle = $_ARRAYLANG['TXT_IMMO_DOWNLOADS'];
        $this->_objTpl->loadTemplateFile('module_immo_downloads.html');

        $immoID = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

        $_SESSION['immo']['startDate']    = !empty($_REQUEST['inputStartDate']) ? contrexx_addslashes($_REQUEST['inputStartDate'])  : $_SESSION['immo']['startDate'];
        $_SESSION['immo']['endDate']    = !empty($_REQUEST['inputEndDate']) ? contrexx_addslashes($_REQUEST['inputEndDate'])  : $_SESSION['immo']['endDate'];
        //paging data
        $limit = !empty($_REQUEST['limit']) ? intval($_REQUEST['limit']) : $_CONFIG['corePagingLimit'];
        $pos = !empty($_REQUEST['pos']) ? intval($_REQUEST['pos']) : 0;
        $field = (!empty($_REQUEST['field'])) ? contrexx_addslashes($_REQUEST['field']) : 'visits';
        $order = (!empty($_REQUEST['order'])) ? contrexx_addslashes($_REQUEST['order']) : 'asc';
        $hsearchField = !empty($_REQUEST['searchField']) ? $_REQUEST['searchField'] : '' ;
        $hsearch = !empty($_REQUEST['search']) ? contrexx_addslashes($_REQUEST['search']) : '' ;

        $this->_objTpl->setGlobalVariable(array(
                'TXT_IMMO_EXPORT'                      =>    $_ARRAYLANG['TXT_IMMO_EXPORT'],
                'TXT_IMMO_DOWNLOADS'                 =>    $_ARRAYLANG['TXT_IMMO_DOWNLOADS'],
                'TXT_IMMO_OBJECT'                     =>    $_ARRAYLANG['TXT_IMMO_OBJECT'],
                'TXT_IMMO_VISITS'                     =>    $_ARRAYLANG['TXT_IMMO_VISITS'],
                'TXT_IMMO_HEADER'                     =>    $_ARRAYLANG['TXT_IMMO_HEADER'],
                'TXT_IMMO_LOCATION'                     =>    $_ARRAYLANG['TXT_IMMO_LOCATION'],
                'TXT_IMMO_SEARCH'                     =>    $_ARRAYLANG['TXT_IMMO_SEARCH'],
                'TXT_IMMO_DOWNLOAD_SEARCH'             =>    $_ARRAYLANG['TXT_IMMO_DOWNLOAD_SEARCH'],
                'TXT_IMMO_SORT'                         =>    $_ARRAYLANG['TXT_IMMO_SORT'],
                'TXT_IMMO_IMMO_ID'                     =>    $_ARRAYLANG['TXT_IMMO_IMMO_ID'],
                'TXT_IMMO_EMAIL'                     =>    $_ARRAYLANG['TXT_IMMO_EMAIL'],
                'TXT_IMMO_NAME'                         =>    $_ARRAYLANG['TXT_IMMO_NAME'],
                'TXT_IMMO_FIRSTNAME'                 =>    $_ARRAYLANG['TXT_IMMO_FIRSTNAME'],
                'TXT_IMMO_COMPANY'                     =>    $_ARRAYLANG['TXT_IMMO_COMPANY'],
                'TXT_IMMO_STREET'                     =>    $_ARRAYLANG['TXT_IMMO_STREET'],
                'TXT_IMMO_ZIP'                         =>    $_ARRAYLANG['TXT_IMMO_ZIP'],
                'TXT_IMMO_LOCATION'                     =>    $_ARRAYLANG['TXT_IMMO_LOCATION'],
                'TXT_IMMO_TELEPHONE'                 =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE'],
                'TXT_IMMO_TELEPHONE_OFFICE'             =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE_OFFICE'],
                'TXT_IMMO_TELEPHONE_MOBILE'             =>    $_ARRAYLANG['TXT_IMMO_TELEPHONE_MOBILE'],
                'TXT_IMMO_PURCHASE'                     =>    $_ARRAYLANG['TXT_IMMO_PURCHASE'],
                'TXT_IMMO_FUNDING'                     =>    $_ARRAYLANG['TXT_IMMO_FUNDING'],
                'TXT_IMMO_COMMENT'                     =>    $_ARRAYLANG['TXT_IMMO_COMMENT'],
                'TXT_IMMO_TIMESTAMP'                 =>    $_ARRAYLANG['TXT_IMMO_TIMESTAMP'],
                'TXT_IMMO_EXPORT'                           =>    $_ARRAYLANG['TXT_IMMO_EXPORT'],
                'TXT_IMMO_FUNCTIONS'                    =>    $_ARRAYLANG['TXT_IMMO_FUNCTIONS'],
                'TXT_IMMO_SEPARATOR'                    =>    $_ARRAYLANG['TXT_IMMO_SEPARATOR'],
                'TXT_IMMO_EDIT'                            =>    $_ARRAYLANG['TXT_IMMO_EDIT'],
                'TXT_IMMO_DELETE'                        =>    $_ARRAYLANG['TXT_IMMO_DELETE'],
                'TXT_IMMO_CONFIRM_DELETE_CONTACT'    =>    $_ARRAYLANG['TXT_IMMO_CONFIRM_DELETE_CONTACT'],
                'TXT_IMMO_CANNOT_UNDO_OPERATION'     =>    $_ARRAYLANG['TXT_IMMO_CANNOT_UNDO_OPERATION'],
                'TXT_IMMO_DETAILS'                      =>    $_ARRAYLANG['TXT_IMMO_DETAILS'],
                'TXT_IMMO_TIMESPAN'                      =>    $_ARRAYLANG['TXT_IMMO_TIMESPAN'],
                'TXT_IMMO_FROM'                          =>    $_ARRAYLANG['TXT_IMMO_FROM'],
                'TXT_IMMO_TO'                          =>    $_ARRAYLANG['TXT_IMMO_TO'],
                'TXT_IMMO_INTERESTS'                 =>    $_ARRAYLANG['TXT_IMMO_INTERESTS'],
                'TXT_IMMO_DOWNLOAD_LIST'              =>    $_ARRAYLANG['TXT_IMMO_DOWNLOAD_LIST'],
                'TXT_IMMO_INTEREST_SEARCH'              =>    $_ARRAYLANG['TXT_IMMO_INTEREST_SEARCH'],
                'TXT_IMMO_SHOW_TIMESPAN_DETAILS'     =>    $_ARRAYLANG['TXT_IMMO_SHOW_TIMESPAN_DETAILS'],
                'TXT_IMMO_IGNORE_TIMESPAN'             =>    $_ARRAYLANG['TXT_IMMO_IGNORE_TIMESPAN'],
                'TXT_IMMO_REFRESH'                     =>    $_ARRAYLANG['TXT_IMMO_REFRESH'],
                'CALENDAR_TODAY'                     => !empty($_SESSION['immo']['startDate']) ? $_SESSION['immo']['startDate'] : date('Y-m-d', strtotime('-1 month')),
                'CALENDAR_NEXT_MONTH'                 => !empty($_SESSION['immo']['endDate']) ? $_SESSION['immo']['endDate'] : date('Y-m-d'),
                'PATH_OFFSET'                         => ASCMS_PATH_OFFSET,
                'IMMO_FORM_ACTION_ID'                 => $immoID,
                'IMMO_ID'                             => $immoID,
                'IMMO_PAGING_LIMIT'                     =>    $limit,
                'IMMO_PAGING_POS'                     =>    $pos,
                'IMMO_PAGING_FIELD'                     =>    $field,
                'IMMO_HSEARCH_FIELD'                 =>    $hsearchField,
                'IMMO_HSEARCH'                         =>    $hsearch,

        ));
        $rowclass = 2;

        //get contact and download stats
        $searchTerm = (!empty($_REQUEST['search'])) ? " LIKE '%".contrexx_addslashes($_REQUEST['search'])."%'" : ' TRUE';
        $searchField = (!empty($_REQUEST['searchField']) && !empty($_REQUEST['search'])) ? ' WHERE '.contrexx_addslashes($_REQUEST['searchField']) : ' WHERE ';
        $query = "    SELECT     `contact`.`id` , `email` , `name` , `firstname` , `street` , `zip` , `location` ,
                            `company` , `telephone` , `telephone_office` , `telephone_mobile` , `purchase` ,
                            `funding` ,  `comment` , `contact`.`immo_id` , `timestamp`, content1.fieldvalue AS immo_header, content2.fieldvalue AS immo_address, content3.fieldvalue AS immo_location
                    FROM `".DBPREFIX."module_immo_contact` AS contact
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content1 ON content1.immo_id = contact.id
                        AND content1.lang_id =1
                        AND content1.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = '".$this->_headline."'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content2 ON content2.immo_id = contact.id
                        AND content2.lang_id =1
                        AND content2.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'adresse'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content3 ON content3.immo_id = contact.id
                        AND content3.lang_id =1
                        AND content3.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'ort'
                            AND fname.lang_id =1
                        )"
                .$searchField." ".$searchTerm;

        if($immoID > 0) {
            $query .= " AND contact.immo_id = $immoID ";
        }


        if(empty($_REQUEST['ignore_timespan']) && !empty($_SESSION['immo']['startDate'])) {
            $query .= " AND `timestamp` BETWEEN ".strtotime($_SESSION['immo']['startDate'])." AND ".strtotime($_SESSION['immo']['endDate'])." ORDER BY timestamp DESC";
        }
        if(($objRS = $objDatabase->Execute($query)) !== false) {
            $count = $objRS->RecordCount();
            $objRS = $objDatabase->SelectLimit($query, $limit, $pos);
            while(!$objRS->EOF) {
                $this->_objTpl->setVariable(array(
                        'IMMO_CONTACT_ID'        =>    intval($objRS->fields['id']),
                        'IMMO_IMMO_ID'            =>    htmlspecialchars($objRS->fields['immo_id']),
                        'IMMO_OBJECT_HEADER'    =>    htmlspecialchars($objRS->fields['immo_header']),
                        'IMMO_OBJECT_ADDRESS'    =>    htmlspecialchars($objRS->fields['immo_address']),
                        'IMMO_OBJECT_LOCATION'    =>    htmlspecialchars($objRS->fields['immo_location']),
                        'IMMO_EMAIL'            =>    htmlspecialchars($objRS->fields['email']),
                        'IMMO_NAME'                =>    htmlspecialchars($objRS->fields['name']),
                        'IMMO_FIRSTNAME'        =>    htmlspecialchars($objRS->fields['firstname']),
                        'IMMO_COMPANY'            =>    htmlspecialchars($objRS->fields['company']),
                        'IMMO_STREET'            =>    htmlspecialchars($objRS->fields['street']),
                        'IMMO_ZIP'                =>    htmlspecialchars($objRS->fields['zip']),
                        'IMMO_LOCATION'            =>    htmlspecialchars($objRS->fields['location']),
                        'IMMO_TELEPHONE'        =>    htmlspecialchars($objRS->fields['telephone']),
                        'IMMO_TELEPHONE_OFFICE'    =>    htmlspecialchars($objRS->fields['telephone_office']),
                        'IMMO_TELEPHONE_MOBILE'    =>    htmlspecialchars($objRS->fields['telephone_mobile']),
                        'IMMO_PURCHASE'            =>    htmlspecialchars($objRS->fields['purchase']),
                        'IMMO_FUNDING'            =>    htmlspecialchars($objRS->fields['funding']),
                        'IMMO_COMMENT'            =>    str_replace(array("\r\n", "\n"), '<br />', htmlspecialchars($objRS->fields['comment'])),
                        'IMMO_COMMENT_TEXT'        =>    str_replace(array("\r\n", "\n"), '<br />', htmlspecialchars($objRS->fields['comment'])),
                        'IMMO_TIMESTAMP'        =>    date(ASCMS_DATE_FORMAT ,$objRS->fields['timestamp']),
                        'ROW_CLASS'                =>    ($rowclass++ % 2 == 0) ? 'row1' : 'row2',
                ));
                $this->_objTpl->parse('downloads');
                $objRS->MoveNext();
            }
        }
        $this->_objTpl->setVariable(array(
                'IMMO_STATS_DOWNLOADS_PAGING'    => getPaging($count, $pos, '&amp;cmd=immo&amp;act=downloads&amp;limit='.$limit, '', true),
        ));
    }

    /**
     * remote scripting search for interests
     *
     * @return JSON object
     */
    function _RPCSearchInterests() {
        global $_CONFIG, $objDatabase;
        $output = '';
        $fieldValues = array('int_count', 'int_reference', 'int_ref_note', 'int_header');
        $field = (!empty($_GET['field'])) ? contrexx_addslashes($_GET['field']) : 'int_count';
        $order = (!empty($_GET['order'])) ? contrexx_addslashes($_GET['order']) : 'asc';
        $limit = (!empty($_GET['limit'])) ? intval($_GET['limit']) : $_CONFIG['corePagingLimit'];
        $pos = (!empty($_GET['pos'])) ? intval($_GET['pos']) : 0;
        if(!in_array($field, $fieldValues) && ( $order != 'asc' || $order != 'desc' )) {
            die();
        }
        $query = "    SELECT count( 1 ) AS int_count, immo.reference as int_reference, immo.ref_nr_note as int_ref_note, a.fieldvalue AS int_header, immo.id as int_immoid
                    FROM `".DBPREFIX."module_immo_interest`
                        AS interest
                    LEFT JOIN ".DBPREFIX."module_immo
                        AS immo
                        ON ( interest.immo_id = immo.id )
                    LEFT JOIN ".DBPREFIX."module_immo_content
                        AS a
                        ON ( a.immo_id = interest.immo_id )
                    LEFT JOIN ".DBPREFIX."module_immo_fieldname
                        AS fn
                        ON ( a.field_id = fn.field_id )
                    WHERE lower( fn.name ) = '".$this->_headline."'";


        if(empty($_REQUEST['ignore_timespan']) && !empty($_SESSION['immo']['startDate'])) {
            $query .= " AND `time` BETWEEN ".strtotime($_SESSION['immo']['startDate'])." AND ".strtotime($_SESSION['immo']['endDate']);
        }
        $query .= " AND fn.lang_id =1
                    AND a.lang_id = 1
                    GROUP BY interest.immo_id
                    ORDER BY ".$field." ".$order;
        $objRS        = $objDatabase->SelectLimit($query, $limit, $pos);
        $limit         = ($limit > $objRS->RecordCount()) ? $objRS->RecordCount() : $limit;
        $interests    = '';
        for($i=0; $i<$limit; $i++) {
            $interests .= 'interests['.$i.'] = { ';
            //escape string and replace space escape
            $interests .= '"int_count":"'.str_replace('+',' ', urlencode($objRS->fields['int_count']))."\",";
            $interests .= '"int_reference":"'.str_replace('+',' ', urlencode($objRS->fields['int_reference']))."\",";
            $interests .= '"int_ref_note":"'.str_replace('+',' ', urlencode($objRS->fields['int_ref_note']))."\",";
            $interests .= '"int_header":"'.str_replace('+',' ', urlencode($objRS->fields['int_header']))."\",";
            $interests .= '"int_immoid":"'.str_replace('+',' ', urlencode($objRS->fields['int_immoid']))."\"};\n\n";
            $objRS->MoveNext();
        }
        die($interests);
    }


    /**
     * remote scripting search for downloads
     *
     * @return JSON object
     */

    function _RPCSearch() {
        global $_CONFIG, $objDatabase;
        $output = '';
        $fieldValues = array('immo_id', 'name', 'firstname', 'street', 'zip', 'location', 'telephone', 'comment', 'timestamp');
        $immoID = !empty($_REQUEST['immoid']) ? intval($_REQUEST['immoid']) : 0;
        $field = (!empty($_GET['field'])) ? contrexx_addslashes($_GET['field']) : 'timestamp';
        $order = (!empty($_GET['order'])) ? contrexx_addslashes($_GET['order']) : 'asc';
        $limit = (!empty($_GET['limit'])) ? intval($_GET['limit']) : $_CONFIG['corePagingLimit'];
        if(!in_array($field, $fieldValues) && ( $order != 'asc' || $order != 'desc' )) {
            die();
        }

        $searchTerm = (!empty($_REQUEST['search']) && !empty($_REQUEST['searchField'])) ? " LIKE '%".contrexx_addslashes($_REQUEST['search'])."%'" : ' TRUE';
        $searchField = (!empty($_REQUEST['searchField']) && !empty($_REQUEST['search'])) ? ' WHERE '.contrexx_addslashes($_REQUEST['searchField']) : ' WHERE ';

        $query = "    SELECT     `contact`.`id` as contact_id , `email` , `name` , `firstname` , `street` , `zip` , `location` ,
                            `company` , `telephone` , `telephone_office` , `telephone_mobile` , `purchase` ,
                            `funding` ,  `comment` , `contact`.`immo_id` , `timestamp`, content1.fieldvalue AS immo_header, content2.fieldvalue AS immo_address, content3.fieldvalue AS immo_location
                    FROM `".DBPREFIX."module_immo_contact` AS contact
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content1 ON content1.immo_id = contact.immo_id
                        AND content1.lang_id =1
                        AND content1.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = '".$this->_headline."'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content2 ON content2.immo_id = contact.immo_id
                        AND content2.lang_id =1
                        AND content2.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'adresse'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content3 ON content3.immo_id = contact.immo_id
                        AND content3.lang_id =1
                        AND content3.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'ort'
                            AND fname.lang_id =1
                        )"
                .$searchField." ".$searchTerm;
        if($_REQUEST['ignore_timespan'] !== 'on' && !empty($_SESSION['immo']['startDate'])) {
            $query .= " AND `timestamp` BETWEEN ".strtotime($_SESSION['immo']['startDate'])." AND ".strtotime($_SESSION['immo']['endDate']);
        }
        if($immoID > 0) {
            $query .= " AND contact.immo_id = $immoID";
        }
        $query .= " ORDER BY ".$field." ".$order;

        $objRS = $objDatabase->SelectLimit($query, $limit);
        $limit = ($limit > $objRS->RecordCount()) ? $objRS->RecordCount() : $limit;
        $contacts = '';
        for($i=0; $i<$limit; $i++) {
            $contacts .= 'contacts['.$i.'] = { ';
            //escape string and replace space escape
            $contacts .= 'immo_id:"'.str_replace('+',' ', urlencode($objRS->fields['immo_id']))."\",";
            $contacts .= 'contact_id:"'.str_replace('+',' ', urlencode($objRS->fields['contact_id']))."\",";
            $contacts .= 'email:"'.str_replace('+',' ', urlencode($objRS->fields['email']))."\",";
            $contacts .= 'name:"'.str_replace('+',' ', urlencode($objRS->fields['name']))."\",";
            $contacts .= 'firstname:"'.str_replace('+',' ', urlencode($objRS->fields['firstname']))."\",";
            $contacts .= 'company:"'.str_replace('+',' ', urlencode($objRS->fields['company']))."\",";
            $contacts .= 'street:"'.str_replace('+',' ', urlencode($objRS->fields['street']))."\",";
            $contacts .= 'zip:"'.str_replace('+',' ', urlencode($objRS->fields['zip']))."\",";
            $contacts .= 'location:"'.str_replace('+',' ', urlencode($objRS->fields['location']))."\",";
            $contacts .= 'telephone:"'.str_replace('+',' ', urlencode($objRS->fields['telephone']))."\",";
            $contacts .= 'telephone_office:"'.str_replace('+',' ', urlencode($objRS->fields['telephone_office']))."\",";
            $contacts .= 'telephone_mobile:"'.str_replace('+',' ', urlencode($objRS->fields['telephone_mobile']))."\",";
            $contacts .= 'purchase:"'.str_replace('+',' ', urlencode($objRS->fields['purchase']))."\",";
            $contacts .= 'funding:"'.str_replace('+',' ', urlencode($objRS->fields['funding']))."\",";
            $contacts .= 'comment:"'.str_replace('+',' ', urlencode($objRS->fields['comment']))."\",";
            $contacts .= 'timestamp:"'.str_replace('+',' ', urlencode(date(ASCMS_DATE_FORMAT, $objRS->fields['timestamp'])))."\"};\n\n";
            $objRS->MoveNext();
        }
        die($contacts);
    }

    /**
     * remote scripting overview listing
     *
     * @return JSON object
     */
    function _RPCOverview() {
        global $_CONFIG, $objDatabase, $_ARRAYLANG;
        $output = '';
        $fieldValues = array('immo_id', 'reference', 'ref_nr_note', 'visibility', 'address', 'location', 'object_type', 'new_building', 'property_type', 'foreigner_authorization', 'special_offer');
        $field = (!empty($_REQUEST['field'])) ? contrexx_addslashes($_REQUEST['field']) : 'timestamp';
        $searchterm = (!empty($_REQUEST['searchTerm'])) ? contrexx_addslashes($_REQUEST['searchTerm']) : '';
        $logo = (!empty($_REQUEST['logo'])) ? contrexx_addslashes($_REQUEST['logo']) : '';
        $order = (!empty($_REQUEST['order'])) ? contrexx_addslashes($_REQUEST['order']) : 'asc';
        $limit = (!empty($_REQUEST['limit'])) ? intval($_REQUEST['limit']) : $_CONFIG['corePagingLimit'];

        $order_fields = explode(",", $field);
        $order_order = explode(",", $order);

        //create order by query
        $orderby = '';
        foreach ($order_fields as $key => $val) {
            if(!in_array($order_fields[$key], $fieldValues) && ( $order_order[$key] != 'asc' || $order_order[$key] != 'desc' )) {
                die();
            }
            if(count($order_fields)-1 != $key) {
                $orderby .= $order_fields[$key]." ".$order_order[$key].",";
            } else {
                $orderby .= $order_fields[$key]." ".$order_order[$key];
            }
        }

        $query = "  SELECT immo.id as `immo_id`,
                        immo.reference AS `reference`,
                        immo.ref_nr_note,
                        immo.object_type AS object_type,
                        immo.new_building AS `new_building`,
                        immo.property_type AS `property_type`,
                        immo.visibility,
                        immo.special_offer,
                        a.fieldvalue AS foreigner_authorization,
                        b.fieldvalue AS location,
                        c.fieldvalue AS address
                    FROM ".DBPREFIX."module_immo AS immo
                    LEFT JOIN ".DBPREFIX."module_immo_content AS a ON ( immo.id = a.immo_id
                                                                    AND a.field_id = (
                                                                        SELECT field_id
                                                                        FROM ".DBPREFIX."module_immo_fieldname
                                                                        WHERE name = 'ausl�nder-bewilligung'
                                                                        AND lang_id = 1 )
                                                                    AND a.lang_id = 1 )
                    LEFT JOIN ".DBPREFIX."module_immo_content AS b ON ( immo.id = b.immo_id
                                                                    AND b.field_id = (
                                                                        SELECT field_id
                                                                        FROM ".DBPREFIX."module_immo_fieldname
                                                                        WHERE name = 'ort'
                                                                        AND lang_id = 1 )
                                                                    AND b.lang_id = 1 )
                       LEFT JOIN ".DBPREFIX."module_immo_content AS c ON ( immo.id = c.immo_id
                                                                    AND c.field_id = (
                                                                        SELECT field_id
                                                                        FROM ".DBPREFIX."module_immo_fieldname
                                                                        WHERE name = 'adresse'
                                                                        AND lang_id = 1 )
                                                                    AND c.lang_id = 1 )
                    ORDER BY $orderby";

        $keys1 = array_filter(array_keys($_ARRAYLANG), array(&$this,"filterImmoType"));
        foreach ($keys1 as $key) {
            $keys[$key] = $_ARRAYLANG[$key];
        }
        array_walk($keys, array(&$this, 'arrStrToLower'));
        if (!empty($searchterm)) {
            $query = "  SELECT immo.id AS `immo_id` , immo.reference AS `reference`, immo.ref_nr_note, immo.object_type AS object_type, immo.new_building AS `new_building` , immo.property_type AS property_type, immo.special_offer, immo.visibility, c.fieldvalue AS address, a.fieldvalue AS foreigner_authorization, b.fieldvalue AS location
                        FROM ".DBPREFIX."module_immo AS immo
                        LEFT JOIN ".DBPREFIX."module_immo_content AS content on ( content.immo_id = immo.id )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS a ON ( immo.id = a.immo_id
                                                                        AND a.field_id = (
                                                                            SELECT field_id
                                                                            FROM ".DBPREFIX."module_immo_fieldname
                                                                            WHERE name = 'ausl�nder-bewilligung'
                                                                            AND lang_id = 1 )
                                                                        AND a.lang_id = 1 )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS b ON ( immo.id = b.immo_id
                                                                        AND b.field_id = (
                                                                            SELECT field_id
                                                                            FROM ".DBPREFIX."module_immo_fieldname
                                                                            WHERE name = 'ort'
                                                                            AND lang_id = 1 )
                                                                        AND b.lang_id = 1 )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS c ON ( immo.id = c.immo_id
                                                                        AND c.field_id = (
                                                                            SELECT field_id
                                                                            FROM ".DBPREFIX."module_immo_fieldname
                                                                            WHERE name = 'adresse'
                                                                            AND lang_id = 1 )
                                                                        AND c.lang_id = 1 )
                        WHERE TRUE ";

            if(!empty($searchterm) && intval($searchterm) == 0) {
                $query .= " AND content.fieldvalue LIKE '%".$searchterm."%'";
            }else if (!empty($searchterm)) {
                $query .= " AND immo.reference LIKE '%".$searchterm."%'";
            }

            if(!empty($logo)) {
                $query .= " AND immo.logo = '$logo'";
            }

            $query .= " AND content.lang_id =1
                        GROUP BY immo.id
                        ORDER BY $orderby";

        }
        $objRS = $objDatabase->SelectLimit($query, $limit);
        $limit = ($limit > $objRS->RecordCount()) ? $objRS->RecordCount() : $limit;
        $objects = '';
        for($i=0; $i<$limit; $i++) {
            $objects .= 'objects['.$i.'] = { ';
            //escape string and replace space escape
            $objects .= 'immo_id:"'.str_replace('+',' ', urlencode($objRS->fields['immo_id']))."\",";
            $objects .= 'reference:"'.str_replace('+',' ', urlencode($objRS->fields['reference']))."\",";
            $objects .= 'ref_nr_note:"'.str_replace('+',' ', urlencode($objRS->fields['ref_nr_note']))."\",";
            $objects .= 'address:"'.str_replace('+',' ', urlencode($objRS->fields['address']))."\",";
            $objects .= 'visibility:"'.str_replace('+',' ', urlencode($_ARRAYLANG['TXT_IMMO_'.strtoupper($objRS->fields['visibility'])]))."\",";
            $objects .= 'otype:"'.str_replace('+',' ', urlencode($_ARRAYLANG['TXT_IMMO_OBJECTTYPE_'.strtoupper($objRS->fields['object_type'])]))."\",";
            $objects .= 'ptype:"'.str_replace('+',' ', urlencode($_ARRAYLANG['TXT_IMMO_PROPERTYTYPE_'.strtoupper($objRS->fields['property_type'])]))."\",";
            $objects .= 'newobj:"'.(($objRS->fields['new_building']) ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'])."\",";
            $objects .= 'so:"'.(($objRS->fields['special_offer']) ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'])."\",";
            $objects .= 'fa:"'.str_replace('+',' ', urlencode($objRS->fields['foreigner_authorization']))."\",";
            $objects .= 'location:"'.str_replace('+',' ', urlencode($objRS->fields['location']))."\"};\n\n";
            $objRS->MoveNext();
        }
        die($objects);
    }

    /**
     * remote scripting pageview stats listing
     *
     * @return JSON object
     */
    function _RPCSort() {
        global $_CONFIG, $objDatabase;
        $output = '';
        $fieldValues = array('visits', 'reference', 'ref_note', 'header', 'location');
        $field = (!empty($_GET['field'])) ? contrexx_addslashes($_GET['field']) : 'visits';
        $order = (!empty($_GET['order'])) ? contrexx_addslashes($_GET['order']) : 'asc';
        $limit = (!empty($_GET['limit'])) ? intval($_GET['limit']) : $_CONFIG['corePagingLimit'];
        $pos = (!empty($_GET['pos'])) ? intval($_GET['pos']) : 0;
        if(!in_array($field, $fieldValues) && ( $order != 'asc' || $order != 'desc' )) {
            die();
        }

        $query = "    SELECT page, visits, content1.fieldvalue AS header, content2.fieldvalue AS location, immo.reference as reference, immo.ref_nr_note as ref_note
                    FROM `".DBPREFIX."stats_requests`
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content1 ON content1.immo_id = CAST(MID(`page`, 40, 8 ) AS UNSIGNED)
                        AND content1.lang_id =1
                        AND content1.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = '".$this->_headline."'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo_content` AS content2 ON content2.immo_id = CAST(MID(`page`, 40, 8) AS UNSIGNED)
                        AND content2.lang_id =1
                        AND content2.field_id = (
                            SELECT field_id
                            FROM `".DBPREFIX."module_immo_fieldname` AS fname
                            WHERE lower( name ) = 'ort'
                            AND fname.lang_id =1
                        )
                    LEFT JOIN `".DBPREFIX."module_immo` AS immo ON immo.id = CAST(MID(`page`, 40, 8) AS UNSIGNED)
                    WHERE page LIKE '/index.php?section=immo&cmd=showObj&id=%'";
        if(empty($_REQUEST['ignore_timespan']) && !empty($_SESSION['immo']['startDate'])) {
            $query .= " AND `timestamp` BETWEEN ".strtotime($_SESSION['immo']['startDate'])." AND ".strtotime($_SESSION['immo']['endDate']);
        }
        $query .= "
                    GROUP BY reference
                    ORDER BY ".$field." ".$order;

        $objRS        = $objDatabase->SelectLimit($query, $limit, $pos);
        $limit         = ($limit > $objRS->RecordCount()) ? $objRS->RecordCount() : $limit;
        $requests    = '';
        for($i=0; $i<$limit; $i++) {
            $requests .= 'requests['.$i.'] = { ';
            //escape string and replace space escape
            $requests .= '"visits":"'.str_replace('+',' ', urlencode($objRS->fields['visits']))."\",";
            $requests .= '"reference":"'.str_replace('+',' ', urlencode($objRS->fields['reference']))."\",";
            $requests .= '"ref_note":"'.str_replace('+',' ', urlencode($objRS->fields['ref_note']))."\",";
            $requests .= '"header":"'.str_replace('+',' ', urlencode($objRS->fields['header']))."\",";
            $requests .= '"page":"'.str_replace('+',' ', urlencode($objRS->fields['page']))."\",";
            $requests .= '"location":"'.str_replace('+',' ', urlencode($objRS->fields['location']))."\"};\n\n";
            $objRS->MoveNext();
        }
        die($requests);
    }

    /**
     * remote scripting download stats listing
     *
     * @return JSON object
     */
    function _RPCDownloadStats() {
        global $_CONFIG, $objDatabase;
        $output = '';
        $fieldValues = array('dl_count', 'dl_reference', 'dl_ref_note', 'dl_header', 'dl_linkname');
        $field = (!empty($_GET['field'])) ? contrexx_addslashes($_GET['field']) : 'dl_count';
        $order = (!empty($_GET['order'])) ? contrexx_addslashes($_GET['order']) : 'asc';
        $limit = (!empty($_GET['limit'])) ? intval($_GET['limit']) : $_CONFIG['corePagingLimit'];
        $pos = (!empty($_GET['pos'])) ? intval($_GET['pos']) : 0;
        if(!in_array($field, $fieldValues) && ( $order != 'asc' || $order != 'desc' )) {
            die();
        }
        $query = "    SELECT count( 1 ) AS dl_count, immo.reference as dl_reference, immo.ref_nr_note as dl_ref_note, a.fieldvalue AS dl_header, b.name AS dl_linkname, immo.id as dl_immoid
                    FROM `".DBPREFIX."module_immo_contact`
                        AS contact
                    LEFT JOIN ".DBPREFIX."module_immo
                        AS immo
                        ON ( contact.immo_id = immo.id )
                    LEFT JOIN ".DBPREFIX."module_immo_content
                        AS a
                        ON ( a.immo_id = contact.immo_id )
                    LEFT JOIN ".DBPREFIX."module_immo_fieldname
                        AS fn
                        ON ( a.field_id = fn.field_id )
                    LEFT JOIN ".DBPREFIX."module_immo_fieldname
                        AS b
                        ON ( b.field_id = contact.field_id )
                    WHERE lower( fn.name ) = '".$this->_headline."'";


        if(empty($_REQUEST['ignore_timespan']) && !empty($_SESSION['immo']['startDate'])) {
            $query .= " AND `timestamp` BETWEEN ".strtotime($_SESSION['immo']['startDate'])." AND ".strtotime($_SESSION['immo']['endDate']);
        }
        $query .= " AND fn.lang_id =1
                    AND a.lang_id = 1
                    AND b.lang_id = 1
                    GROUP BY contact.immo_id
                    ORDER BY ".$field." ".$order;
        $objRS        = $objDatabase->SelectLimit($query, $limit, $pos);
        $limit         = ($limit > $objRS->RecordCount()) ? $objRS->RecordCount() : $limit;
        $requests    = '';
        for($i=0; $i<$limit; $i++) {
            $requests .= 'requests['.$i.'] = { ';
            //escape string and replace space escape
            $requests .= '"dl_count":"'.str_replace('+',' ', urlencode($objRS->fields['dl_count']))."\",";
            $requests .= '"dl_reference":"'.str_replace('+',' ', urlencode($objRS->fields['dl_reference']))."\",";
            $requests .= '"dl_ref_note":"'.str_replace('+',' ', urlencode($objRS->fields['dl_ref_note']))."\",";
            $requests .= '"dl_header":"'.str_replace('+',' ', urlencode($objRS->fields['dl_header']))."\",";
            $requests .= '"dl_immoid":"'.str_replace('+',' ', urlencode($objRS->fields['dl_immoid']))."\",";
            $requests .= '"dl_linkname":"'.str_replace('+',' ', urlencode($objRS->fields['dl_linkname']))."\"};\n\n";
            $objRS->MoveNext();
        }
        die($requests);
    }

    /**
     * shows the object overview
     *
     * @return void
     */
    function _showOverview() {
        global $_ARRAYLANG, $objDatabase;

        $this->_pageTitle = $_ARRAYLANG['TXT_IMMO_OVERVIEW'];
        $this->_objTpl->loadTemplateFile('module_immo_overview.html');

        $limit = !empty($_REQUEST['limit']) ? intval($_REQUEST['limit']) : $this->arrSettings['latest_entries_count'];
        $pos = !empty($_REQUEST['pos']) ? intval($_REQUEST['pos']) : 0;
        $field = (!empty($_REQUEST['field'])) ? contrexx_addslashes($_REQUEST['field']) : 'immo_id';
        $order = (!empty($_REQUEST['order'])) ? contrexx_addslashes($_REQUEST['order']) : 'asc';
        $hsearchField = !empty($_REQUEST['searchField']) ? $_REQUEST['searchField'] : '' ;
        $hsearch = !empty($_REQUEST['search']) ? contrexx_addslashes($_REQUEST['search']) : '' ;

        $rowclass = 2;
        $queryAll = "  SELECT immo.id as `immo_id`,
                            immo.reference AS `ref`,
                            immo.ref_nr_note,
                            immo.object_type AS otype,
                            immo.new_building AS `new`,
                            immo.property_type AS ptype,
                            immo.visibility,
                            immo.special_offer,
                            a.fieldvalue AS foreigner_authorization,
                            b.fieldvalue AS location,
                            c.fieldvalue AS address
                        FROM ".DBPREFIX."module_immo AS immo
                        LEFT JOIN ".DBPREFIX."module_immo_content AS a ON ( immo.id = a.immo_id
                                                                        AND a.field_id = (
                                                                            SELECT field_id
                                                                            FROM ".DBPREFIX."module_immo_fieldname
                                                                            WHERE name = 'ausl�nder-bewilligung'
                                                                            AND lang_id = 1 )
                                                                        AND a.lang_id = 1 )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS b ON ( immo.id = b.immo_id
                                                                        AND b.field_id = (
                                                                            SELECT field_id
                                                                            FROM ".DBPREFIX."module_immo_fieldname
                                                                            WHERE name = 'ort'
                                                                            AND lang_id = 1 )
                                                                        AND b.lang_id = 1 )
                           LEFT JOIN ".DBPREFIX."module_immo_content AS c ON ( immo.id = c.immo_id
                                                                        AND c.field_id = (
                                                                            SELECT field_id
                                                                            FROM ".DBPREFIX."module_immo_fieldname
                                                                            WHERE name = 'adresse'
                                                                            AND lang_id = 1 )
                                                                        AND c.lang_id = 1 )

                        ORDER BY immo.id DESC";

        $keys1 = array_filter(array_keys($_ARRAYLANG), array(&$this,"filterImmoType"));
        foreach ($keys1 as $key) {
            $keys[$key] = $_ARRAYLANG[$key];
        }
        array_walk($keys, array(&$this, 'arrStrToLower'));
        if (!empty($_REQUEST['search'])) {
            $searchterm = contrexx_addslashes(strip_tags($_POST['searchterm']));
            $logo = contrexx_addslashes(strip_tags($_POST['logo']));
            $query = "  SELECT immo.id AS `immo_id`, immo.reference AS `ref`, immo.ref_nr_note, immo.object_type AS otype, immo.new_building AS `new` , immo.property_type AS ptype, immo.special_offer, immo.visibility, c.fieldvalue AS address, a.fieldvalue AS foreigner_authorization, b.fieldvalue AS location
                        FROM ".DBPREFIX."module_immo AS immo
                        LEFT JOIN ".DBPREFIX."module_immo_content AS content on ( content.immo_id = immo.id )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS a ON ( immo.id = a.immo_id
                                                                        AND a.field_id = (
                                                                            SELECT field_id
                                                                            FROM ".DBPREFIX."module_immo_fieldname
                                                                            WHERE name = 'ausl�nder-bewilligung'
                                                                            AND lang_id = 1 )
                                                                        AND a.lang_id = 1 )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS b ON ( immo.id = b.immo_id
                                                                        AND b.field_id = (
                                                                            SELECT field_id
                                                                            FROM ".DBPREFIX."module_immo_fieldname
                                                                            WHERE name = 'ort'
                                                                            AND lang_id = 1 )
                                                                        AND b.lang_id = 1 )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS c ON ( immo.id = c.immo_id
                                                                        AND c.field_id = (
                                                                            SELECT field_id
                                                                            FROM ".DBPREFIX."module_immo_fieldname
                                                                            WHERE name = 'adresse'
                                                                            AND lang_id = 1 )
                                                                        AND c.lang_id = 1 )
                        WHERE TRUE ";

            if(intval($searchterm) == 0) {
                $query .= " AND content.fieldvalue LIKE '%".$searchterm."%'";
            }else {
                $query .= " AND  immo.reference LIKE '%".$searchterm."%'";
            }

            $query .= " AND immo.logo = '$logo'
                        AND content.lang_id =1
                        GROUP BY immo.id
                        ORDER BY immo.id DESC";

            $objResult = $objDatabase->Execute($query);



            if ($objResult->RecordCount() == 0) {
                $objResult = $objDatabase->SelectLimit($queryAll, $this->arrSettings['latest_entries_count']);
                $listTitle = $_ARRAYLANG['TXT_IMMO_LATEST_ENTRIES'];
                $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_NO_RESULTS'];
                $searchterm = $logo = "";
            } else {
                $listTitle = $_ARRAYLANG['TXT_IMMO_SEARCH_RESULTS'];
            }
        }else {
            $objResult = $objDatabase->SelectLimit($queryAll, $this->arrSettings['latest_entries_count']);
            $listTitle = $_ARRAYLANG['TXT_IMMO_LATEST_ENTRIES'];
        }

        $this->_objTpl->setVariable(array(
                'TXT_IMMO_SEARCH'        => $_ARRAYLANG['TXT_IMMO_SEARCH'],
                'TXT_IMMO_LIST_TITLE'    => $listTitle,
        ));

        // If entries should be shown
        if ($objResult->RecordCount() > 0) {
            $this->_objTpl->setVariable(array(
                    'TXT_IMMO_REF_ID'                       =>    $_ARRAYLANG['TXT_IMMO_REFERENCE_NUMBER_SHORT'],
                    'TXT_IMMO_OBJECT_TYPE'                  =>    $_ARRAYLANG['TXT_IMMO_OBJECT_TYPE'],
                    'TXT_IMMO_NEW_BUILDING'                 =>    $_ARRAYLANG['TXT_IMMO_NEW_BUILDING_SHORT'],
                    'TXT_IMMO_PROPERTY_TYPE'               =>    $_ARRAYLANG['TXT_IMMO_PROPERTY_TYPE_SHORT'],
                    'TXT_IMMO_REF_NOTE'                   =>    $_ARRAYLANG['TXT_IMMO_REF_NOTE'],
                    'TXT_IMMO_ADDRESS'                    =>    $_ARRAYLANG['TXT_IMMO_ADDRESS'],
                    'TXT_IMMO_FUNCTIONS'                  =>    $_ARRAYLANG['TXT_IMMO_FUNCTIONS'],
                    'TXT_IMMO_DELETE'                    =>    $_ARRAYLANG['TXT_IMMO_DELETE'],
                    'TXT_IMMO_EDIT'                        =>    $_ARRAYLANG['TXT_IMMO_EDIT'],
                    'TXT_IMMO_COPY'                        =>    $_ARRAYLANG['TXT_IMMO_COPY'],
                    'TXT_IMMO_CONFIRM_DELETE_OBJECT'    =>    $_ARRAYLANG['TXT_IMMO_CONFIRM_DELETE_OBJECT'],
                    'TXT_IMMO_CANNOT_UNDO_OPERATION'    =>    $_ARRAYLANG['TXT_IMMO_CANNOT_UNDO_OPERATION'],
                    'TXT_IMMO_LOCATION'                    =>    $_ARRAYLANG['TXT_IMMO_LOCATION'],
                    'TXT_IMMO_VISIBLE'                    =>    $_ARRAYLANG['TXT_IMMO_VISIBLE'],
                    'TXT_IMMO_FOREIGNER_AUTHORIZATION'    =>    $_ARRAYLANG['TXT_IMMO_FOREIGNER_AUTHORIZATION'],
                    'TXT_IMMO_SPECIAL_OFFER'            =>    $_ARRAYLANG['TXT_IMMO_SPECIAL_OFFER_SHORT'],
                    'IMMO_LOGO_'.strtoupper($logo).'_SELECTED'    =>    'selected="selected"',
                    'IMMO_PAGING_LIMIT'                     =>    $limit,
                    'IMMO_PAGING_POS'                     =>    $pos,
                    'IMMO_PAGING_FIELD'                     =>    $field,
                    'IMMO_HSEARCH_FIELD'                 =>    $hsearchField,
                    'IMMO_HSEARCH'                         =>    $searchterm,
                    'IMMO_HLOGO'                         =>    $logo,
            ));

            while (!$objResult->EOF) {
                $this->_objTpl->setVariable(array(
                        'IMMO_ID'                       => !empty($objResult->fields['immo_id']) ? $objResult->fields['immo_id'] : '&nbsp;',
                        'IMMO_REF_ID'                   => !empty($objResult->fields['ref']) ? $objResult->fields['ref'] : '&nbsp;',
                        'IMMO_REF_NR_NOTE'              => !empty($objResult->fields['ref_nr_note']) ? $objResult->fields['ref_nr_note'] : '&nbsp;',
                        'IMMO_OBJECT_TYPE'              => $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_'.strtoupper($objResult->fields['otype'])],
                        'IMMO_NEW_BUILDING'             => ($objResult->fields['new']) ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'],
                        'IMMO_PROPERTY_TYPE'            => $_ARRAYLANG['TXT_IMMO_PROPERTYTYPE_'.strtoupper($objResult->fields['ptype'])],
                        'IMMO_ADDRESS'                  => $objResult->fields['address'],
                        'IMMO_LOCATION'                    => $objResult->fields['location'],
                        'IMMO_VISIBILITY'                => $_ARRAYLANG['TXT_IMMO_'.strtoupper($objResult->fields['visibility'])],
                        'IMMO_LOCATION'                    => !empty($objResult->fields['location']) ? $objResult->fields['location'] : '&nbsp;',
                        'IMMO_SPECIAL_OFFER'            => $objResult->fields['special_offer'] == 1 ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'],
                        'IMMO_FOREIGNER_AUTHORIZATION'    => !empty($objResult->fields['foreigner_authorization']) ? $objResult->fields['foreigner_authorization'] : '&nbsp;',
                        'ROW_CLASS'                        => 'row'.(($rowclass++ % 2 == 0) ? 1 : 2),
                ));

                $this->_objTpl->parse("row");
                $objResult->MoveNext();
//                $objRSLoc->MoveNext();
            }

            $this->_objTpl->parse("entriesList");
        }


    }

    /**
     * copy an object
     *
     * @return void
     */
    function _copy() {
        global $objDatabase, $_ARRAYLANG;
        $immoID = intval($_GET['id']);
        if(empty($immoID)) {
            $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_NO_ID_SPECIFIED'];
        }else {
            $query = "    INSERT INTO ".DBPREFIX."module_immo(
SELECT '', reference, ref_nr_note, logo, special_offer, visibility, object_type, new_building, property_type, longitude, latitude, zoom
FROM ".DBPREFIX."module_immo
WHERE id = $immoID )";
            if($objDatabase->Execute($query)) {
                $lastInsertedID = $objDatabase->Insert_ID();
                $objDatabase->Execute("    UPDATE ".DBPREFIX."module_immo_settings set setvalue = $lastInsertedID
                                            WHERE setname = 'last_inserted_immo_id'");
                $query = "    INSERT INTO ".DBPREFIX."module_immo_content    (
                                SELECT '', '$lastInsertedID', lang_id, field_id, fieldvalue, `active`
                                FROM ".DBPREFIX."module_immo_content WHERE immo_id = $immoID
                            )";
                $objDatabase->Execute($query);

                $query = "    INSERT INTO ".DBPREFIX."module_immo_image (
                                SELECT '', '$lastInsertedID', field_id, uri
                                FROM ".DBPREFIX."module_immo_image
                                WHERE immo_id = $immoID )";
                if($objDatabase->Execute($query)) {
                    if(!file_exists(ASCMS_CONTENT_IMAGE_PATH.DS.'immo'.DS.'images'.DS.($lastInsertedID+1))) {
                        $this->_objFile->mkDir(ASCMS_CONTENT_IMAGE_PATH.DS.'immo'.DS.'images'.DS, ASCMS_CONTENT_IMAGE_WEB_PATH.DS.'immo'.DS.'images'.DS, ($lastInsertedID+1));
                        $this->_objFile->mkDir(ASCMS_CONTENT_IMAGE_PATH.DS.'immo'.DS.'pdfs'.DS, ASCMS_CONTENT_IMAGE_WEB_PATH.DS.'immo'.DS.'pdfs'.DS, ($lastInsertedID+1));
                    }
                    $this->_strOkMessage = $_ARRAYLANG['TXT_IMMO_SUCCESSFULLY_COPIED'];
                }
            }else {
                $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_DB_ERROR']." ". $objDatabase->ErrorMsg();
            }
        }
    }

    /**
     * deletes an object
     *
     * @return void
     */
    function _del() {
        global $objDatabase, $_ARRAYLANG;
        $immoID = intval($_GET['id']);
        if(empty($immoID)) {
            $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_NO_ID_SPECIFIED'];
        }else {
            if($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_immo WHERE id = $immoID")) {
                $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_immo_content WHERE immo_id = $immoID");
                if($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_immo_image WHERE immo_id = $immoID")) {
                    $this->_strOkMessage = $_ARRAYLANG['TXT_IMMO_SUCCESSFULLY_DELETED'];
                }
            }else {
                $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_DB_ERROR'];
            }
        }
    }

    /**
     * adds a new language (not yet implemented)
     * @return void
     */
    function _addlanguage() {
        print_r($_REQUEST);
        $this->_showSettings();
    }

    /**
     * deletes a custom field
     *
     * @return bool
     */
    function _delfield() {
        global $objDatabase, $_ARRAYLANG;
        if(!empty($_GET['id'])) {
            $fieldID = intval($_GET['id']);
        }

        $query = "    SELECT field_id from ".DBPREFIX."module_immo_fieldname
                    WHERE `name` = 'Adresse' LIMIT 1";
        $objRS = $objDatabase->Execute($query);
        if(!$objRS) {
            $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_DB_ERROR'] ." ".$objDatabase->ErrorMsg();
            $this->_showSettings();
            return false;
        }else {
            if($objRS->RecordCount() != 1) {
                $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_ADDRESSFIELD_MISSING'];
                $this->_showSettings();
                return false;
            }
        }

        $fieldIdAddress = $objRS->fields['field_id'];
        if($fieldID == $fieldIdAddress) {
            $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_THIS_FIELD_CANNOT_BE_DELETED'];
            $this->_showSettings();
            return false;
        }
        if(
        $objDatabase->Execute("    DELETE FROM ".DBPREFIX."module_immo_field
                                    WHERE id=$fieldID")
                !== false
                &&    $objDatabase->Execute("    DELETE FROM ".DBPREFIX."module_immo_fieldname
                                    WHERE field_id=$fieldID")
                !== false
                &&    $objDatabase->Execute("    DELETE FROM ".DBPREFIX."module_immo_image
                                    WHERE field_id=$fieldID")
                !== false
                &&    $objDatabase->Execute("    DELETE FROM ".DBPREFIX."module_immo_content
                                    WHERE field_id=$fieldID")
                !== false) {
//TODO: _strOkMessage
            $this->_strOkMessage = $_ARRAYLANG['TXT_IMMO_SUCCESSFULLY_DELETED'];
        }else {
            $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_DB_ERROR'] ." ".$objDatabase->ErrorMsg();
        }
        $this->_showSettings();
        return true;
    }


    /**
     * remote scripting field content suggestion
     *
     * @return JSON object
     */
    function _RPCGetSuggest() {
        global $objDatabase;
        $sugg = '';
        $fieldID = intval($_GET['fieldid']);
        $langID = intval($_GET['langid']);
        $value = contrexx_addslashes($_GET['value']);
        $ID = contrexx_addslashes($_GET['ID']);
        $objRS = $objDatabase->SelectLimit("SELECT fieldvalue AS suggestion
                                        FROM ".DBPREFIX."module_immo_content
                                        WHERE field_id = $fieldID
                                        AND lang_id = $langID
                                        AND fieldvalue LIKE '%$value%'
                                        GROUP BY fieldvalue
                                        ORDER BY fieldvalue", 30, 0);
        if($objRS) {
            $i = 0;
            //build JSON object with suggestions and needed IDs
            $sugg = 'sugg = { ';
            while(!$objRS->EOF) {
                //escape string and replace space escape
                $sugg .= $i.':"'.str_replace('+',' ', urlencode($objRS->fields['suggestion'])).'",';
                $objRS->MoveNext();
                $i++;
            }
            $sugg = substr($sugg,0,-1); //remove trailing comma
            $sugg .= "};\n";
            die($sugg);
        }

    }




    /**
     * Add or edit an object
     *
     * @return void
     */
    function _add() {
        global $objDatabase, $_ARRAYLANG;
        $immoID = (isset($_GET['id'])) ? intval($_GET['id']) : 0;

        $error = false;
        $okStatus = "";
        if (isset($_POST['sent'])) {
            $reference = (!empty($_POST['ref_nr'])) ? intval($_POST['ref_nr']) : "";
            $reference_note = (!empty($_POST['ref_nr_note'])) ? contrexx_addslashes(strip_tags($_POST['ref_nr_note'])) : "";
            $logo = (!empty($_POST['logo'])) ? contrexx_addslashes(strip_tags($_POST['logo'])) : "";
            $special_offer = (!empty($_POST['special_offer'])) ? contrexx_addslashes(strip_tags($_POST['special_offer'])) : "";
            $visibility = (!empty($_POST['visibility'])) ? contrexx_addslashes(strip_tags($_POST['visibility'])) : "";
            $object_type = (!empty($_POST['obj_type'])) ? contrexx_addslashes(strip_tags($_POST['obj_type'])) : "";
            $new_building = (!empty($_POST['new_building'])) ? contrexx_addslashes(strip_tags($_POST['new_building'])) : "";
            $property_type = (!empty($_POST['property_type'])) ? contrexx_addslashes(strip_tags($_POST['property_type'])) : "";
            $zoom = (!empty($_POST['zoom'])) ? contrexx_addslashes(strip_tags($_POST['zoom'])) : "";
            $headliner = (!empty($_POST['headliner'])) ? time() : false;

            if (!empty($_POST['longitude'])) {
                $longitude = contrexx_addslashes(strip_tags($_POST['longitude'])).".";
                if (empty($_POST['longitude_fraction'])) {
                    $longitude .= "0";
                } else {
                    $longitude .= contrexx_addslashes(strip_tags($_POST['longitude_fraction']));
                }
            } else {
                $longitude = "0";
            }

            if (!empty($_POST['latitude'])) {
                $latitude = contrexx_addslashes(strip_tags($_POST['latitude'])).".";
                if (empty($_POST['latitude_fraction'])) {
                    $latitude .= "0";
                } else {
                    $latitude .= contrexx_addslashes(strip_tags($_POST['latitude_fraction']));
                }
            } else {
                $latitude = "";
            }

            if($immoID > 0) {
                $query = "  UPDATE ".DBPREFIX."module_immo
                            SET
                                `reference` =  '".$reference."',
                                `ref_nr_note` =  '".$reference_note."',
                                `logo` =  '".$logo."',
                                `special_offer` =  '".$special_offer."',
                                `visibility` =  '".$visibility."',
                                `object_type` =  '".$object_type."',
                                `new_building` = '".$new_building."',
                                `property_type` = '".$property_type."',
                                `longitude` =  '".$longitude."',
                                `latitude` = '".$latitude."',
                                `zoom` = '".$zoom."' /*,
                                `headliner` = '".$headliner."'*/
                            WHERE `id` = '".$immoID."'";
                if ($objDatabase->Execute($query)) {
                    $this->_getFieldNames($immoID);
                    foreach ($this->fieldNames as $fieldkey => $field) {
                        $_POST['active'][$fieldkey] = ((isset($_POST['active'][$fieldkey]) || $field['mandatory']) ? 1 : 0);
                        $field['content']['active'] = ((!empty($field['content']['active']) || $field['mandatory']) ? 1 : 0);
                        foreach ($this->languages as $langId => $lang) {
                            $value = $_POST['field_'.$fieldkey.'_'.$langId];
                            if ( ((CONTREXX_ESCAPE_GPC) ? stripslashes($value) : $value) != ((!empty($field['content'][$langId])) ? $field['content'][$langId] : '') ||
                                    $_POST['active'][$fieldkey] != $field['content']['active']) {
                                $value = contrexx_addslashes(strip_tags($value));
                                $query = "  UPDATE ".DBPREFIX."module_immo_content
                                                SET `fieldvalue`= '".$value."',
                                                `active` = '".(($_POST['active'][$fieldkey] || $field['mandatory']) ? 1 : 0)."'
                                                WHERE `field_id` = '".$fieldkey."'
                                                AND `lang_id` = '".$langId."'
                                                AND `immo_id` = '".$immoID."'
                                                ";
                                //immoid?
                                if (!$objDatabase->Execute($query)) {
                                    $error = true;
                                }
                                if($objDatabase->Affected_Rows() == 0) { // field doesnt exists yet
                                    $query = "  INSERT INTO ".DBPREFIX."module_immo_content
                                            (
                                                `immo_id`,
                                                `lang_id`,
                                                `field_id`,
                                                `fieldvalue`,
                                                `active`
                                            ) VALUES (
                                                '".$immoID."',
                                                '".$langId."',
                                                '".$fieldkey."',
                                                '".$value."',
                                                '".(($_POST['active'][$fieldkey] || $field['mandatory']) ? 1 : 0)."'
                                            )";
                                    //immoid?
                                    if (!$objDatabase->Execute($query)) {
                                        $error = true;
                                    }
                                }
                            }
                        }

                        /***
                                    type: LINKS
                                ***/



                        if ($field['type'] == "img" || $field['type'] == "panorama") {
                            $value = (isset($_POST['hi_'.$fieldkey])) ? contrexx_addslashes(strip_tags($_POST['hi_'.$fieldkey])) : "";
                            if(empty($value)) {
                                continue;
                            }  //ignore empty
                            if($field['img'] == $_POST['hi_'.$fieldkey]) {
                                continue;
                            } //ignore if no changes were made
                            $query = "  UPDATE ".DBPREFIX."module_immo_image
                                            SET `uri` = '".$value."'
                                            WHERE `immo_id` = '".$immoID."'
                                            AND `field_id` = '".$fieldkey."'
                                            ";

                            if (!$objDatabase->Execute($query)) {
                                $error = true;
                            }
                            if($objDatabase->Affected_Rows() == 0) { // field doesnt exists yet
                                $query = "  INSERT INTO ".DBPREFIX."module_immo_image
                                                (   `immo_id`,
                                                    `field_id`,
                                                    `uri`
                                                ) VALUES (
                                                    '".$immoID."',
                                                    '".$fieldkey."',
                                                    '".$value."'
                                                )";
                                //immoid?
                                if (!$objDatabase->Execute($query)) {
                                    $error = true;
                                }
                            }
                        }
                    }

                } else {
                    $error = true;
                }
                $okStatus = $_ARRAYLANG['TXT_IMMO_SUCCESSFULLY_UPDATED'];
            } else {
                $this->_getSettings();
                //new object code goes here
                $query = "  INSERT INTO ".DBPREFIX."module_immo
                            (
                                `reference`,
                                `ref_nr_note`,
                                `logo`,
                                `special_offer`,
                                `visibility`,
                                `object_type`,
                                `new_building`,
                                `property_type`,
                                `longitude`,
                                `latitude`,
                                `zoom`
                            ) VALUES (
                                '".$reference."',
                                '".$reference_note."',
                                '".$logo."',
                                '".$special_offer."',
                                '".$visibility."',
                                '".$object_type."',
                                '".$new_building."',
                                '".$property_type."',
                                '".$longitude."',
                                '".$latitude."',
                                '".$zoom."'
                            )";


                if ($objDatabase->Execute($query)) {
                    $insertId = $objDatabase->Insert_ID();
                    $objDatabase->Execute("    UPDATE ".DBPREFIX."module_immo_settings set setvalue = $insertId
                                            WHERE setname = 'last_inserted_immo_id'");
                    $this->_getFieldNames();

                    foreach ($this->fieldNames as $fieldkey => $field) {
//                        if (isset($_POST['active'][$fieldkey])) {
                        foreach ($this->languages as $langId => $lang) {
                            $value = contrexx_addslashes(strip_tags($_POST['field_'.$fieldkey.'_'.$langId]));
                            $value = ( !empty($value) ) ? $value : '';
                            $query = "  INSERT INTO ".DBPREFIX."module_immo_content
                                            (
                                                `immo_id`,
                                                `lang_id`,
                                                `field_id`,
                                                `fieldvalue`,
                                                `active`
                                            ) VALUES (
                                                '".$insertId."',
                                                '".$langId."',
                                                '".$fieldkey."',
                                                '".$value."',
                                                '".((isset($_POST['active'][$fieldkey]) || $field['mandatory']) ? 1 : 0)."'
                                            )";
                            if (!$objDatabase->Execute($query)) {
                                $error = true;
                            }
//                            }
                        }

                        if ($field['type'] == "img" || $field['type'] == 'panorama') {

                            $value = (isset($_POST['hi_'.$fieldkey])) ? contrexx_addslashes(strip_tags($_POST['hi_'.$fieldkey])) : "";
                            $query = "  INSERT INTO ".DBPREFIX."module_immo_image
                                        (   `immo_id`,
                                            `field_id`,
                                            `uri`
                                        ) VALUES (
                                            '".$insertId."',
                                            '".$fieldkey."',
                                            '".$value."'
                                        )";
                            if (!$objDatabase->Execute($query)) {
                                $error = true;
                            }
                        }
                    }
                } else {
                    $error = true;
                }
                $okStatus = $_ARRAYLANG['TXT_IMMO_SUCCESSFULLY_INSERTED'];
            }
        }
        if ($error) {
            $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_DB_ERROR'] ." ".$objDatabase->ErrorMsg();
        } else {
            // OKMessage
            $this->_strOkMessage = $okStatus;
        }
    }

    /**
     * deletes all images that are no longer used by any objects
     *
     * @return void
     */
    function _deleteUnusedImages() {
        global $objDatabase, $_ARRAYLANG;
        $deleted = '';
        $images = opendir(ASCMS_CONTENT_IMAGE_PATH.DS.'immo'.DS.'images');
        while (($file = readdir($images)) !== false) {
            if($file == '.' || $file == '..') {
                continue;
            }
            if(is_dir(ASCMS_CONTENT_IMAGE_PATH.DS.'immo'.DS.'images'.DS.intval($file))) {
                $objRS = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_immo
                                                WHERE id = $file");
                if($objRS->RecordCount() == 0) {
                    $this->_objFile->delDir(ASCMS_CONTENT_IMAGE_PATH.DS.'immo'.DS.'images'.DS, ASCMS_CONTENT_IMAGE_WEB_PATH.DS.'immo'.DS.'images'.DS, $file);
                    $this->_objFile->delDir(ASCMS_CONTENT_IMAGE_PATH.DS.'immo'.DS.'pdfs'.DS, ASCMS_CONTENT_IMAGE_WEB_PATH.DS.'immo'.DS.'pdfs'.DS, $file);
                    $deleted .= ASCMS_CONTENT_IMAGE_WEB_PATH.DS.'immo'.DS.'images'.DS.$file."<br />\n";
                }
            }
        }
        closedir($images);
        $this->_strOkMessage = sprintf($_ARRAYLANG['TXT_IMMO_IMAGES_DELETED'], $deleted);
    }

    /**
     * show the immo form for adding or editing objects
     *
     * @param int $immoID ID of the object to edit, 0 for new objects
     */
    function _showImmoForm($immoID=0) {
        global $objDatabase, $_ARRAYLANG;
        $this->_getSettings();
        if(!empty($_GET['id']) && intval($_GET['id']) > 0) {
            $immoID = intval($_GET['id']);
            $this->_getFieldNames($immoID);
        }else {
            $immoID = 0;
            $this->_getFieldNames($immoID);
        }

        $this->_pageTitle = $_ARRAYLANG['TXT_IMMO_ADD'];
        $this->_objTpl->loadTemplateFile('module_immo_add.html');
        $this->_objTpl->setGlobalVariable(array(
                'TXT_IMMO_CREATE_OBJECT'            =>    $_ARRAYLANG['TXT_IMMO_CREATE_OBJECT'],
                'TXT_IMMO_NAME'                     =>    $_ARRAYLANG['TXT_IMMO_NAME'],
                'TXT_IMMO_IMAGE'                     =>    $_ARRAYLANG['TXT_IMMO_IMAGE'],
                'TXT_IMMO_CLICK_HERE'                =>     $_ARRAYLANG['TXT_IMMO_CLICK_HERE'],
                'TXT_IMMO_OBJECTTYPE_FLAT'            =>     $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_FLAT'],
                'TXT_IMMO_OBJECTTYPE_HOUSE'            =>     $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_HOUSE'],
                'TXT_IMMO_OBJECTTYPE_MULTIFAMILY'    =>     $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_MULTIFAMILY'],
                'TXT_IMMO_OBJECTTYPE_ESTATE'        =>     $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_ESTATE'],
                'TXT_IMMO_OBJECTTYPE_INDUSTRY'        =>     $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_INDUSTRY'],
                'TXT_IMMO_OBJECTTYPE_PARKING'        =>     $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_PARKING'],
                'TXT_IMMO_OBJECT_TYPE'                =>    $_ARRAYLANG['TXT_IMMO_OBJECT_TYPE'],
                'TXT_IMMO_REFERENCE_NUMBER'            =>    $_ARRAYLANG['TXT_IMMO_REFERENCE_NUMBER'],
                'TXT_IMMO_PROPERTYTYPE_PURCHASE'    =>    $_ARRAYLANG['TXT_IMMO_PROPERTYTYPE_PURCHASE'],
                'TXT_IMMO_PROPERTYTYPE_RENT'        =>    $_ARRAYLANG['TXT_IMMO_PROPERTYTYPE_RENT'],
                'TXT_IMMO_PROPERTY_TYPE'            =>    $_ARRAYLANG['TXT_IMMO_PROPERTY_TYPE'],
                'TXT_IMMO_NEW_BUILDING'                =>    $_ARRAYLANG['TXT_IMMO_NEW_BUILDING'],
                'TXT_IMMO_YES'                        =>    $_ARRAYLANG['TXT_IMMO_YES'],
                'TXT_IMMO_NO'                        =>    $_ARRAYLANG['TXT_IMMO_NO'],
                'TXT_IMMO_PROPERTY_TYPE'            =>    $_ARRAYLANG['TXT_IMMO_PROPERTY_TYPE'],
                'TXT_IMMO_DEFINE_TEXT'                =>    $_ARRAYLANG['TXT_IMMO_DEFINE_TEXT'],
                'TXT_IMMO_DEFINE_IMAGE'             =>  $_ARRAYLANG['TXT_IMMO_DEFINE_IMAGE'],
                'TXT_IMMO_LONGITUDE'                =>    $_ARRAYLANG['TXT_IMMO_LONGITUDE'],
                'TXT_IMMO_LATITUDE'                    =>    $_ARRAYLANG['TXT_IMMO_LATITUDE'],
                'TXT_IMMO_ZOOM'                        =>    $_ARRAYLANG['TXT_IMMO_ZOOM'],
                'TXT_IMMO_ENTER_ADDRESS'            =>    $_ARRAYLANG['TXT_IMMO_ENTER_ADDRESS'],
                'TXT_IMMO_SEARCH_ADDRESS'            =>    $_ARRAYLANG['TXT_IMMO_SEARCH_ADDRESS'],
                'TXT_IMMO_BROWSER_NOT_SUPPORTED'    =>    $_ARRAYLANG['TXT_IMMO_BROWSER_NOT_SUPPORTED'],
                'TXT_IMMO_DELETE'                    =>  $_ARRAYLANG['TXT_IMMO_DELETE'],
                'IMMO_COLUMN_NUMBER'                   =>  $this->langCount+2,
                'IMMO_COLUMN_NUMBER2'                =>    $this->langCount+1,
                'IMMO_COLUMN_NUMBER3'                =>    $this->langCount,
                'TXT_IMMO_SUBMIT'                    =>    ($immoID > 0) ? $_ARRAYLANG['TXT_IMMO_SAVE'] : $_ARRAYLANG['TXT_IMMO_ADD'],
                'IMMO_ID'                           =>  ($immoID > 0) ? $immoID : "",
                'LAST_IMMO_ID'                      =>  ($immoID > 0) ? $immoID : ($this->arrSettings['last_inserted_immo_id']+1),
                'TXT_IMMO_TAB_IMAGES'               =>  $_ARRAYLANG['TXT_IMMO_TAB_IMAGES'],
                'TXT_IMMO_TAB_LINK'                   =>  $_ARRAYLANG['TXT_IMMO_TAB_LINK'],
                'TXT_IMMO_TAB_TEXT'                 =>  $_ARRAYLANG['TXT_IMMO_TAB_TEXT'],
                'TXT_IMMO_LOGO'                        =>    $_ARRAYLANG['TXT_IMMO_LOGO'],
                'TXT_IMMO_SPECIAL_OFFER'            =>    $_ARRAYLANG['TXT_IMMO_SPECIAL_OFFER'],
                'TXT_IMMO_VISIBLE'                    =>    $_ARRAYLANG['TXT_IMMO_VISIBLE'],
                'TXT_IMMO_DISABLED'                    =>    $_ARRAYLANG['TXT_IMMO_DISABLED'],
                'TXT_IMMO_REFERENCE'                =>    $_ARRAYLANG['TXT_IMMO_REFERENCE'],
                'TXT_IMMO_LISTING'                    =>    $_ARRAYLANG['TXT_IMMO_LISTING'],
                'TXT_IMMO_DISABLED'                    =>    $_ARRAYLANG['TXT_IMMO_DISABLED'],
                'TXT_IMMO_DEFINE_LINK'                =>    $_ARRAYLANG['TXT_IMMO_DEFINE_LINK'],
                'TXT_IMMO_BROWSE'                    =>    $_ARRAYLANG['TXT_IMMO_BROWSE'],
                'TXT_IMMO_NO_RESULTS'                =>    $_ARRAYLANG['TXT_IMMO_NO_RESULTS'],
                'TXT_IMMO_GET_PROPOSAL_LIST'        =>    $_ARRAYLANG['TXT_IMMO_GET_PROPOSAL_LIST'],
                'TXT_IMMO_MANDATORY_FIELDS_ARE_EMPTY'     =>    $_ARRAYLANG['TXT_IMMO_MANDATORY_FIELDS_ARE_EMPTY'],
                'TXT_IMMO_EDIT_OR_ADD_IMAGE'         =>    $_ARRAYLANG['TXT_IMMO_EDIT_OR_ADD_IMAGE'],
                "TXT_IMMO_HEADLINER"                =>  $_ARRAYLANG['TXT_IMMO_HEADLINER']

        ));
        if(!file_exists(ASCMS_CONTENT_IMAGE_PATH.DS.'immo'.DS.'images'.DS.($this->arrSettings['last_inserted_immo_id']+1))) {
            $this->_objFile->mkDir(ASCMS_CONTENT_IMAGE_PATH.DS.'immo'.DS.'images'.DS, ASCMS_CONTENT_IMAGE_WEB_PATH.DS.'immo'.DS.'images'.DS, ($this->arrSettings['last_inserted_immo_id']+1));
            $this->_objFile->mkDir(ASCMS_CONTENT_IMAGE_PATH.DS.'immo'.DS.'pdfs'.DS, ASCMS_CONTENT_IMAGE_WEB_PATH.DS.'immo'.DS.'pdfs'.DS, ($this->arrSettings['last_inserted_immo_id']+1));
        }

        foreach ($this->languages as $langID => $language) {
            $this->_objTpl->setVariable('TXT_IMMO_LANGUAGE', $_ARRAYLANG[$language]);
            $this->_objTpl->parse("languages");
            $this->_objTpl->setVariable('TXT_IMMO_LANGUAGE', $_ARRAYLANG[$language]);
            $this->_objTpl->parse("languages2");
            $this->_objTpl->setVariable('TXT_IMMO_LANGUAGE', $_ARRAYLANG[$language]);
            $this->_objTpl->parse("languages3");
            $this->_objTpl->setVariable('IMMO_LANGUAGE_ID', $langID);
            $this->_objTpl->parse("languageIds");
        }

        if ($immoID > 0) {
            $query = "    SELECT * FROM ".DBPREFIX."module_immo
                          WHERE `id` = '".$immoID."'";
            $objResult = $objDatabase->Execute($query);

            if ($objResult) {
                $longitudeTmp = explode(".", $objResult->fields['longitude']);
                $latitudeTmp = explode(".", $objResult->fields['latitude']);

                $strSelected = "selected=\"selected\"";

                $this->_objTpl->setVariable(array(
                        'IMMO_REF_NR'       => $objResult->fields['reference'],
                        'IMMO_REF_NR_NOTE'  => $objResult->fields['ref_nr_note'],
                        'IMMO_LONGITUDE'    => $longitudeTmp[0],
                        'IMMO_LONGITUDE_FRACTION'   => $longitudeTmp[1],
                        'IMMO_LATTITUDE'    => $latitudeTmp[0],
                        'IMMO_LATTITUDE_FRACTION'    => $latitudeTmp[1],
                        'IMMO_ZOOM'         => $objResult->fields['zoom'],
                        'IMMO_TYPE_SELECT_'.strtoupper($objResult->fields['object_type']) => $strSelected,
                        'IMMO_NEW_SELECT_'.(($objResult->fields['new_building']) ? "YES" : "NO")  => $strSelected,
                        'IMMO_PROPERTY_SELECT_'.strtoupper($objResult->fields['property_type']) => $strSelected,
                        'IMMO_SPECIAL_OFFER_SELECT_'.(($objResult->fields['special_offer']) ? "YES" : "NO")  => $strSelected,
                        'IMMO_LOGO_SELECTED_'.strtoupper($objResult->fields['logo']) => $strSelected,
                        'IMMO_VISIBLE_SELECT_'.strtoupper($objResult->fields['visibility']) => $strSelected,
                        'IMMO_HEADLINER_SELECT' => ($objResult->fields['headliner']) ? "checked=\"checked\"" : ""
                ));
            }
        }

        $strDisabled = 'disabled="disabled"';
        $rowClassText = 2; //text & textbox
        $rowClassImg = 2;  //img
        $rowClassLnk = 2;  //lnk
        $immoData = "";
        $imageData = "";
        foreach ($this->fieldNames as $fieldkey => $field) {
            if($field['mandatory']) {
                $this->_objTpl->setVariable('IMMO_MANDATORY_ID', $fieldkey);
                $this->_objTpl->parse("mandatoryArray");
            }

            switch ($field['type']) {
                case "text":
                case "digits_only":
                case "price":
                    $rowClassText = ($rowClassText == 2) ? 1 : 2;
                    foreach ($this->languages as $langid => $language) {
                        $this->_objTpl->setVariable(array(
                                "IMMO_FIELD_TEXT_NAME"      => "field_".$fieldkey."_".$langid,
                                "IMMO_FIELD_TEXT_VALUE"     => ($immoID > 0) /*If we're editing*/ ? $field['content'][$langid] : "",
                                "IMMO_DECIMAL_ONLY"            => ($field['type'] == 'digits_only') ? 'onchange="this.value=decimalOnly(this.value)"' : '',
                                'IMMO_FIELD_ID'                =>    $fieldkey,
                                'IMMO_FIELD_LANG_ID'        =>    $langid,
                        ));
                        $this->_objTpl->parse("text-column");
                    }

                    $this->_objTpl->setVariable(array(
                            "IMMO_FIELD_TEXT_CAPTION"   => $field['names'][1],
                            "IMMO_ROW"                  => $rowClassText,
                            'IMMO_FIELD_TEXT_ID'        => $fieldkey,
                            'IMMO_CHECKED'                => ($immoID > 0) ? (($field['content']['active']) ? 'checked="checked"' : '' ) : ($field['mandatory'] == 1) ? 'checked="checked"' : '',
                            'IMMO_DISABLED'                => ($field['mandatory'] == 1) ? $strDisabled : '',
                    ));
                    $this->_objTpl->parse("fieldRowText");

                    $immoData .= $this->_objTpl->get("fieldRowText", true);
                    break;

                case "textarea":
                    $rowClassText = ($rowClassText == 2) ? 1 : 2;
                    foreach ($this->languages as $langid => $language) {
                        $this->_objTpl->setVariable(array(
                                "IMMO_FIELD_TEXTAREA_NAME"      => "field_".$fieldkey."_".$langid,
                                "IMMO_FIELD_TEXTAREA_VALUE"     => ($immoID > 0) ? $field['content'][$langid] : "", // If we're editing
                                'IMMO_FIELD_ID'                =>    $fieldkey,
                                'IMMO_FIELD_LANG_ID'        =>    $langid,
                        ));
                        $this->_objTpl->parse("textarea-column");
                    }
                    $this->_objTpl->setVariable(array(
                            "IMMO_FIELD_TEXTAREA_CAPTION"   => $field['names'][1],
                            "IMMO_ROW"                      => $rowClassText,
                            'IMMO_FIELD_TEXTAREA_ID'        => $fieldkey,
                            'IMMO_CHECKED'                    => ($immoID > 0) ? (($field['content']['active']) ? 'checked="checked"' : '' ) : ($field['mandatory'] == 1) ? 'checked="checked"' : '',
                            'IMMO_DISABLED'                    => ($field['mandatory'] == 1) ? $strDisabled : '',
                    ));

                    $this->_objTpl->parse("fieldRowTextarea");
                    $immoData .= $this->_objTpl->get("fieldRowTextarea", true);
                    break;

                case "img":
                    $rowClassImg = ($rowClassImg == 2) ? 1 : 2;
                    foreach ($this->languages as $langid => $language) {
                        $this->_objTpl->setVariable(array(
                                "IMMO_FIELD_IMG_NAME"    => "field_".$fieldkey."_".$langid,
                                'IMMO_FIELD_IMG_VALUE'    => ($immoID > 0) ? $field['content'][$langid] : "", // If we're editings
                        ));
                        $this->_objTpl->parse("img-column");
                    }

                    $this->_objTpl->setVariable(array(
                            "IMMO_FIELD_IMG_CAPTION"       => $field['names'][1],
                            "IMMO_ROW"                  => $rowClassImg,
                            'IMMO_FIELD_IMG_ID'            => $fieldkey,
                            'IMMO_CHECKED'                => ($immoID > 0) ? (($field['content']['active']) ? 'checked="checked"' : '' ) : ($field['mandatory'] == 1) ? 'checked="checked"' : '',
                            'IMMO_DISABLED'                    => ($field['mandatory'] == 1) ? $strDisabled : '',
                            'IMMO_FIELD_IMG_SRC'        => (!empty($field['img']) && is_file(ASCMS_PATH.$field['img'])) ? $field['img'] : $this->_defaultImage,
                            'TXT_IMMO_EDIT_OR_ADD_IMAGE' => $_ARRAYLANG['TXT_IMMO_EDIT_OR_ADD_IMAGE'],
                    ));
                    $this->_objTpl->parse("fieldRowImg");

                    $this->_objTpl->setVariable(array(
                            'IMMO_IMG_ID'    =>    $fieldkey,
                            'IMMO_IMG_URL'    =>     $field['img'],
                    ));
                    $this->_objTpl->parse("hiddenFields");

                    break;
                case 'panorama':
                    $rowClassImg = ($rowClassImg == 2) ? 1 : 2;
                    foreach ($this->languages as $langid => $language) {
                        $this->_objTpl->setVariable(array(
                                "IMMO_FIELD_PANO_NAME"    => "field_".$fieldkey."_".$langid,
                                'IMMO_FIELD_PANO_VALUE'    => ($immoID > 0) ? $field['content'][$langid] : "", // If we're editings
                        ));
                        $this->_objTpl->parse("panorama-column");
                    }

                    $this->_objTpl->setVariable(array(
                            "IMMO_FIELD_PANO_CAPTION"       => $field['names'][1],
                            "IMMO_ROW"                  => $rowClassImg,
                            'IMMO_FIELD_PANO_ID'            => $fieldkey,
                            'IMMO_CHECKED'                => ($immoID > 0) ? (($field['content']['active']) ? 'checked="checked"' : '' ) : ($field['mandatory'] == 1) ? 'checked="checked"' : '',
                            'IMMO_DISABLED'                    => ($field['mandatory'] == 1) ? $strDisabled : '',
                            'IMMO_FIELD_PANO_SRC'        => (!empty($field['img']) && is_file(ASCMS_PATH.$field['img'])) ? $field['img'] : $this->_defaultImage,
                    ));

                    $this->_objTpl->parse("fieldRowPanorama");

                    $this->_objTpl->setVariable(array(
                            'IMMO_IMG_ID'    =>    $fieldkey,
                            'IMMO_IMG_URL'    =>     $field['img'],
                    ));
                    $this->_objTpl->parse("hiddenFields");

                    break;

                case "protected_link":
                case "link":
                    $rowClassLnk = ($rowClassLnk == 2) ? 1 : 2;
                    foreach ($this->languages as $langid => $language) {
                        $this->_objTpl->setVariable(array(
                                "IMMO_FIELD_LNK_NAME"    => "field_".$fieldkey."_".$langid,
                                'IMMO_FIELD_LNK_VALUE'    => ($immoID > 0) ? $field['content'][$langid] : "", // we're editing?
                        ));
                        $this->_objTpl->parse("lnk-column");
                    }

                    $this->_objTpl->setVariable(array(
                            "IMMO_FIELD_LNK_CAPTION"       => $field['names'][1],
                            "IMMO_ROW"                  => $rowClassLnk,
                            'IMMO_FIELD_LNK_ID'            => $fieldkey,
                            'IMMO_CHECKED'                => ($immoID > 0) ? (($field['content']['active']) ? 'checked="checked"' : '' ) : ($field['mandatory'] == 1) ? 'checked="checked"' : '',
                            'IMMO_DISABLED'                => ($field['mandatory'] == 1) ? $strDisabled : '',
                            'IMMO_PROTECTED_ICON'        => ($field['type'] == 'protected_link') ? '../core/Core/View/Media/icons/lock_closed.gif' : '../core/Core/View/Media/icons/lock_open.gif',
                            'TXT_IMMO_PROTECTED'        => ($field['type'] == 'protected_link') ? $_ARRAYLANG['TXT_IMMO_PROTECTED'] : $_ARRAYLANG['TXT_IMMO_NOT_PROTECTED'],

                    ));

                    $this->_objTpl->parse("fieldRowLnk");
                    break;


            }



        }

        $this->_objTpl->replaceBlock("rows", $immoData, true);
        $this->_objTpl->touchBlock("rows");
        $this->_objTpl->parse("rows");
    }

    /**
     * pops up the google map window for choosing coordinates for an object
     *
     * @return void
     */
    function _showMapPopup() {
        global $_ARRAYLANG;
        $objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/immo/template');
        $objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $objTpl->loadTemplateFile('module_immo_map_popup.html');
        $googlekey = (!empty($this->arrSettings['GOOGLE_API_KEY_'.$_SERVER['SERVER_NAME']])) ? $this->arrSettings['GOOGLE_API_KEY_'.$_SERVER['SERVER_NAME']] : '';
        $objTpl->setVariable(array(
                'CONTREXX_CHARSET'                    => CONTREXX_CHARSET,
                'TXT_IMMO_BROWSER_NOT_SUPPORTED'    => $_ARRAYLANG['TXT_IMMO_BROWSER_NOT_SUPPORTED'],
                'TXT_IMMO_CLOSE'                    => $_ARRAYLANG['TXT_IMMO_CLOSE'],
                'TXT_IMMO_DBLCLICK_TO_SET_POINT'    => $_ARRAYLANG['TXT_IMMO_DBLCLICK_TO_SET_POINT'],
                'TXT_IMMO_ACCEPT'                   => $_ARRAYLANG['TXT_IMMO_ACCEPT'],
                'IMMO_MAP_LAT_BACKEND'              => $this->arrSettings['lat_backend'],
                'IMMO_MAP_LON_BACKEND'              => $this->arrSettings['lon_backend'],
                'IMMO_MAP_ZOOM_BACKEND'             => $this->arrSettings['zoom_backend'],
                'IMMO_GOOGLE_API_KEY'               => $googlekey,

        ));
        $objTpl->show();

    }

    /**
     * shows The settings page
     *
     * @global $_ARRAYLANG, $objDatabase
     * @access private
     *
     */
    function _showSettings() {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $this->_getFieldNames();
        $domain1 = $_CONFIG['domainUrl'];
        $domain2 = $this->_getDomain($_CONFIG['domainUrl']);
        $this->_pageTitle = $_ARRAYLANG['TXT_IMMO_SETTINGS'];
        $this->_objTpl->loadTemplateFile('module_immo_settings.html');
        $this->_objTpl->setGlobalVariable(array(
                'TXT_IMMO_TYPE'                    => $_ARRAYLANG['TXT_IMMO_TYPE'],
                'TXT_IMMO_ORDER'                => $_ARRAYLANG['TXT_IMMO_ORDER'],
                'TXT_IMMO_TYPE_TEXT'            => $_ARRAYLANG['TXT_IMMO_TYPE_TEXT'],
                'TXT_IMMO_TYPE_TEXTAREA'        => $_ARRAYLANG['TXT_IMMO_TYPE_TEXTAREA'],
                'TXT_IMMO_TYPE_IMG'                => $_ARRAYLANG['TXT_IMMO_TYPE_IMG'],
                'TXT_IMMO_CLONEROW'                => $_ARRAYLANG['TXT_IMMO_CLONEROW'],
                'TXT_IMMO_DELETE'                => $_ARRAYLANG['TXT_IMMO_DELETE'],
                'TXT_IMMO_DELETEROW'            => $_ARRAYLANG['TXT_IMMO_DELETEROW'],
                'TXT_IMMO_DEFINE_FIELDS'        => $_ARRAYLANG['TXT_IMMO_DEFINE_FIELDS'],
                'TXT_IMMO_LANGUAGES'            => $_ARRAYLANG['TXT_IMMO_LANGUAGES'],
                'TXT_IMMO_IMAGES'                => $_ARRAYLANG['TXT_IMMO_IMAGES'],
                'TXT_IMMO_DELETE_UNUSED_IMAGES'    => $_ARRAYLANG['TXT_IMMO_DELETE_UNUSED_IMAGES'],
                'TXT_IMMO_DELETE_IMAGES'        => $_ARRAYLANG['TXT_IMMO_DELETE_IMAGES'],
                'TXT_IMMO_SAVE'                    => $_ARRAYLANG['TXT_IMMO_SAVE'],
                'TXT_IMMO_AVAILABLE_LANGUAGES'    => $_ARRAYLANG['TXT_IMMO_AVAILABLE_LANGUAGES'],
                'TXT_IMMO_NEW_LANGUAGE'            => $_ARRAYLANG['TXT_IMMO_NEW_LANGUAGE'],
                'TXT_IMMO_ADD'                    => $_ARRAYLANG['TXT_IMMO_ADD'],
                'TXT_IMMO_AVAILABLE_FIELDS'        => $_ARRAYLANG['TXT_IMMO_AVAILABLE_FIELDS'],
                'TXT_IMMO_ID'                    => $_ARRAYLANG['TXT_IMMO_ID'],
                'TXT_IMMO_TYPE'                    => $_ARRAYLANG['TXT_IMMO_TYPE'],
                'TXT_IMMO_FUNCTIONS'            => $_ARRAYLANG['TXT_IMMO_FUNCTIONS'],
                'TXT_CONFIRM_DELETE'            => $_ARRAYLANG['TXT_CONFIRM_DELETE'],
                'TXT_IMMO_SHOW_FILE'            => $_ARRAYLANG['TXT_IMMO_SHOW_FILE'],
                'TXT_IMMO_EDIT'                 => $_ARRAYLANG['TXT_IMMO_EDIT'],
                'TXT_SETTINGS'                  => $_ARRAYLANG['TXT_SETTINGS'],
                'TXT_IMMO_LATEST_ENTRIES_COUNT' => $_ARRAYLANG['TXT_IMMO_LATEST_ENTRIES_COUNT'],
                'TXT_IMMO_GOOGLE_KEY'           => $_ARRAYLANG['TXT_IMMO_GOOGLE_KEY'],
                'TXT_IMMO_SAVE'                 => $_ARRAYLANG['TXT_IMMO_SAVE'],
                'TXT_IMMO_ICON_MESSAGE'         => $_ARRAYLANG['TXT_IMMO_ICON_MESSAGE'],
                'TXT_IMMO_MAP_STARTPOINT_FRONTEND'  => $_ARRAYLANG['TXT_IMMO_MAP_STARTPOINT_FRONTEND'],
                'TXT_IMMO_MAP_STARTPOINT_BACKEND'  => $_ARRAYLANG['TXT_IMMO_MAP_STARTPOINT_BACKEND'],
                'TXT_IMMO_SETTINGS_ICON_MESSAGE_DESC'   => $_ARRAYLANG['TXT_IMMO_SETTINGS_ICON_MESSAGE_DESC'],
                'IMMO_SETTINGS_DOMAIN1'         => $domain1,
                'IMMO_SETTINGS_DOMAIN2'         => $domain2,
                'TXT_IMMO_TYPE_LINK'            =>    $_ARRAYLANG['TXT_IMMO_TYPE_LINK'],
                'TXT_IMMO_TYPE_PROTECTED_LINK'    =>    $_ARRAYLANG['TXT_IMMO_TYPE_PROTECTED_LINK'],
                'TXT_IMMO_TYPE_PANORAMA'    =>    $_ARRAYLANG['TXT_IMMO_TYPE_PANORAMA'],
                'TXT_IMMO_TYPE_DIGITS_ONLY'    =>    $_ARRAYLANG['TXT_IMMO_TYPE_DIGITS_ONLY'],
                'TXT_IMMO_TYPE_PRICE'        =>    $_ARRAYLANG['TXT_IMMO_TYPE_PRICE'],
                'TXT_IMMO_MANDATORY'            => $_ARRAYLANG['TXT_IMMO_MANDATORY'],
                'TXT_IMMO_NO'                    => $_ARRAYLANG['TXT_IMMO_NO'],
                'TXT_IMMO_YES'                    => $_ARRAYLANG['TXT_IMMO_YES'],
                'TXT_IMMO_CURRENCY'                => $_ARRAYLANG['TXT_IMMO_CURRENCY'],
                'TXT_IMMO_PROTECTED_LINK_EMAIL_MESSAGE_BODY'        => $_ARRAYLANG['TXT_IMMO_PROTECTED_LINK_EMAIL_MESSAGE_BODY'],
                'TXT_IMMO_PROTECTED_LINK_EMAIL_MESSAGE_SUBJECT'        => $_ARRAYLANG['TXT_IMMO_PROTECTED_LINK_EMAIL_MESSAGE_SUBJECT'],
                'TXT_IMMO_PROTECTED_LINK_SENDER_NAME'        => $_ARRAYLANG['TXT_IMMO_PROTECTED_LINK_SENDER_NAME'],
                'TXT_IMMO_PROTECTED_LINK_SENDER_EMAIL'        => $_ARRAYLANG['TXT_IMMO_PROTECTED_LINK_SENDER_EMAIL'],
                'TXT_IMMO_PROTECTED_LINK_EMAIL_MESSAGE_BODY_INFO'        => $_ARRAYLANG['TXT_IMMO_PROTECTED_LINK_EMAIL_MESSAGE_BODY_INFO'],
                'TXT_IMMO_CONTACT_RECEIVERS'        => $_ARRAYLANG['TXT_IMMO_CONTACT_RECEIVERS'],
                'TXT_IMMO_CONTACT_RECEIVERS_INFO'        => $_ARRAYLANG['TXT_IMMO_CONTACT_RECEIVERS_INFO'],
                'TXT_IMMO_INTEREST_CONFIRM_SUBJECT'        => $_ARRAYLANG['TXT_IMMO_INTEREST_CONFIRM_SUBJECT'],
                'TXT_IMMO_INTEREST_CONFIRM_MESSAGE'        => $_ARRAYLANG['TXT_IMMO_INTEREST_CONFIRM_MESSAGE'],
                'TXT_IMMO_INTEREST_INFO'            => $_ARRAYLANG['TXT_IMMO_INTEREST_INFO'],

                // Settings
                'IMMO_SETTINGS_LATEST_ENTRIES_COUNT' => $this->arrSettings['latest_entries_count'],
                'IMMO_SETTINGS_GOOGLE_KEY_DOMAIN1' => $this->arrSettings['GOOGLE_API_KEY_'.$domain1],
                'IMMO_SETTINGS_GOOGLE_KEY_DOMAIN2' => $this->arrSettings['GOOGLE_API_KEY_'.$domain2],
                'IMMO_SETTINGS_ICON_MESSAGE'       => htmlspecialchars($this->arrSettings['message']),
                'TXT_IMMO_GOOGLE_KEY_INFO'        => $_ARRAYLANG['TXT_IMMO_GOOGLE_KEY_INFO'],
                'IMMO_LON_FRONTEND'                => $this->arrSettings['lon_frontend'],
                'IMMO_LAT_FRONTEND'                => $this->arrSettings['lat_frontend'],
                'IMMO_ZOOM_FRONTEND'            => $this->arrSettings['zoom_frontend'],
                'IMMO_LON_BACKEND'                => $this->arrSettings['lon_backend'],
                'IMMO_LAT_BACKEND'                => $this->arrSettings['lat_backend'],
                'IMMO_ZOOM_BACKEND'                => $this->arrSettings['zoom_backend'],
                'IMMO_PROT_LINK_MESSAGE_SUBJECT'    => $this->arrSettings['prot_link_message_subject'],
                'IMMO_PROT_LINK_MESSAGE_BODY' => $this->arrSettings['prot_link_message_body'],
                'IMMO_PROT_LINK_SENDER_EMAIL' => $this->arrSettings['sender_email'],
                'IMMO_PROT_LINK_SENDER_NAME'  => $this->arrSettings['sender_name'],
                'IMMO_CONTACT_RECEIVERS'      => $this->arrSettings['contact_receiver'],
                'IMMO_INTEREST_CONFIRM_SUBJECT'      => $this->arrSettings['interest_confirm_subject'],
                'IMMO_INTEREST_CONFIRM_MESSAGE'      => $this->arrSettings['interest_confirm_message'],

                'IMMO_DEFINE_FIELDS_COLSPAN'     => $this->langCount+4,
                'IMMO_LANG_COUNT'                => $this->langCount,
                'IMMO_LANG_COUNT_PLUS1'            => $this->langCount+1,
        ));

        $rowid = 2;
        foreach ($this->fieldNames as $fieldID => $field) {
            foreach($this->languages as $langID => $language) {
                $this->_objTpl->setVariable(array(
                        'IMMO_FIELD_CONTENT'=> (!empty($field['names'][$langID])) ? $field['names'][$langID] : '',
                        'IMMO_FIELD_LANGID' => $langID
                ));
                $this->_objTpl->touchBlock('langRow4');
                $this->_objTpl->parse('langRow4');
            }
            $this->_objTpl->setVariable(array(
                    'IMMO_FIELD_ID'        =>    $fieldID,
                    'IMMO_FIELD_TYPE'    =>    $_ARRAYLANG['TXT_IMMO_TYPE_'.strtoupper($field['type'])],
                    'IMMO_FIELD_TYPE_LIST' => $this->_getFieldTypeList($fieldID, $field['type']),
                    'TXT_MANDATORY'        => ($field['mandatory']) ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'],
                    'IMMO_ORDER'         =>  $field['order'],
                    'IMMO_ROW'          => ($rowid == 2) ? $rowid-- : $rowid++,
                    'IMMO_SELECTED_'.(($field['mandatory']) ? "YES" : "NO") => "selected=\"selected\""
            ));
            $this->_objTpl->parse('fieldLangList');
        }

        foreach ($this->languages as $id => $lang) {
            $this->_objTpl->setVariable(array(
                    'IMMO_LANGUAGE_ID'         =>    $id,
                    'IMMO_LANGUAGE_NAME'    =>    $_ARRAYLANG[$lang],
                    'IMMO_LANGUAGE_ID2'     =>    $id,
                    'IMMO_LANGUAGE_NAME2'    =>    $_ARRAYLANG[$lang],
                    'IMMO_LANGUAGE_NAME3'    =>  $_ARRAYLANG[$lang],
                    'IMMO_LANGUAGE_NAME5'    =>  $_ARRAYLANG[$lang],
                    'IMMO_LANGUAGE_ID5'        =>  $id,
                    'IMMO_CURRENCY'            =>    $this->arrSettings['currency_lang_'.$id]
            ));
            $this->_objTpl->parse('langRow');
            $this->_objTpl->parse('langRow2');
            $this->_objTpl->parse('langRow3');
            $this->_objTpl->parse('langRow5');
            $this->_objTpl->parse('langRowContent');
        }
    }

    /**
     * write the settings to the database
     *
     * @return void
     */
    function _saveSettings() {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $error = false;

        $domain1 =  $_CONFIG['domainUrl'];
        $domain2 =  $this->_getDomain($_CONFIG['domainUrl']);
        // with underlines for dots
        $domain1Un = str_replace(".", "_", $domain1);
        $domain2Un = str_replace(".", "_", $domain2);


        if (isset($_POST['latest_entries_count'])) {
            if (!$this->_updateSetting('latest_entries_count', $_POST['latest_entries_count'])) {
                $error = true;
            }
        }

        if (isset($_POST['GOOGLE_API_KEY_'.$domain1Un])) {
            if (!$this->_updateSetting('GOOGLE_API_KEY_'.$domain1, $_POST['GOOGLE_API_KEY_'.$domain1Un])) {
                $error = true;
            }
        }

        if (isset($_POST['GOOGLE_API_KEY_'.$domain2Un])) {
            if (!$this->_updateSetting('GOOGLE_API_KEY_'.$domain2, $_POST['GOOGLE_API_KEY_'.$domain2Un])) {
                $error = true;
            }
        }

        if (isset($_POST['message'])) {
            if (!$this->_updateSetting('message', $_POST['message'])) {
                $error = true;
            }
        }

        if (isset($_POST['lat_frontend'])) {
            if (!$this->_updateSetting('lat_frontend', $_POST['lat_frontend'])) {
                $error = true;
            }
        }

        if (isset($_POST['lon_frontend'])) {
            if (!$this->_updateSetting('lon_frontend', $_POST['lon_frontend'])) {
                $error = true;
            }
        }

        if (isset($_POST['zoom_frontend'])) {
            if (!$this->_updateSetting('zoom_frontend', $_POST['zoom_frontend'])) {
                $error = true;
            }
        }



        if (isset($_POST['lat_backend'])) {
            if (!$this->_updateSetting('lat_backend', $_POST['lat_backend'])) {
                $error = true;
            }
        }

        if (isset($_POST['lon_backend'])) {
            if (!$this->_updateSetting('lon_backend', $_POST['lon_backend'])) {
                $error = true;
            }
        }



        if (isset($_POST['zoom_backend'])) {
            if (!$this->_updateSetting('zoom_backend', $_POST['zoom_backend'])) {
                $error = true;
            }
        }

        if (isset($_POST['prot_link_message_body'])) {
            if (!$this->_updateSetting('prot_link_message_body', $_POST['prot_link_message_body'])) {
                $error = true;
            }
        }

        if (isset($_POST['prot_link_message_subject'])) {
            if (!$this->_updateSetting('prot_link_message_subject', $_POST['prot_link_message_subject'])) {
                $error = true;
            }
        }


        if (isset($_POST['sender_email'])) {
            if (!$this->_updateSetting('sender_email', $_POST['sender_email'])) {
                $error = true;
            }
        }

        if (isset($_POST['sender_name'])) {
            if (!$this->_updateSetting('sender_name', $_POST['sender_name'])) {
                $error = true;
            }
        }

        if (isset($_POST['contact_receiver'])) {
            if (!$this->_updateSetting('contact_receiver', $_POST['contact_receiver'])) {
                $error = true;
            }
        }

        if (isset($_POST['interest_confirm_subject'])) {
            if (!$this->_updateSetting('interest_confirm_subject', $_POST['interest_confirm_subject'])) {
                $error = true;
            }
        }

        if (isset($_POST['interest_confirm_message'])) {
            if (!$this->_updateSetting('interest_confirm_message', $_POST['interest_confirm_message'])) {
                $error = true;
            }
        }

        foreach ($this->languages as $langID => $language) {
            if (isset($_POST['currency_lang_'.$langID])) {
                if (!$this->_updateSetting('currency_lang_'.$langID, $_POST['currency_lang_'.$langID])) {
                    $error = true;
                }
            }
        }

        if ($error) {
            $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_DB_ERROR'];
        } else {
            $this->_strOkMessage = $_ARRAYLANG['TXT_IMMO_SUCCESSFULLY_UPDATED'];
        }
    }

    /**
     * Update Settings
     *
     * Updates a Settings row
     * @param $key Name of the setting
     * @param $val Value of the setting
     */
    function _updateSetting($key, $val) {
        global $objDatabase;

        $query = "SELECT `setvalue` FROM ".DBPREFIX."module_immo_settings
                  WHERE `setname` = '".$key."'";
        $objRs = $objDatabase->Execute($query);
        if ($objRs->RecordCount() == 0) {
            $val = contrexx_addslashes($val);
            $query = "  INSERT INTO ".DBPREFIX."module_immo_settings
                        (`setname`, `setvalue`)
                        VALUES
                        ('".$key."', '".$val."')
                        ";
            if (!$objDatabase->Execute($query)) {
                return false;
            }
        } else {
            $val = contrexx_addslashes($val);
            if ($this->arrSettings[$key] != $val) {
                $query = "  UPDATE ".DBPREFIX."module_immo_settings
                            SET `setvalue` = '".$val."'
                            WHERE `setname` = '".$key."'";
                if (!$objDatabase->Execute($query)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Saves added fields
     *
     * @access private
     * @global $objDatabase
     * @global $_ARRAYLANG
     */
    function _addfields() {
        global $objDatabase, $_ARRAYLANG;
        $dberror=false;

        for($i=0; $i<count($_REQUEST['type']) && !$dberror; $i++) {
            $query = "    INSERT INTO ".DBPREFIX."module_immo_field
                            (id,
                            type,
                            `order`,
                            `mandatory`
                            )
                        VALUES
                            (NULL,
                            '".addslashes(strip_tags($_REQUEST['type'][$i]))."',
                            '".((isset($_REQUEST['order'][$i])) ? intval($_REQUEST['order'][$i]) : 9999 )."',
                            '".((isset($_REQUEST['mandatory'][$i])) ? intval($_REQUEST['mandatory'][$i]) : 0)."'
                            )";
            if($objDatabase->Execute($query) === false) {
                $objDatabase->ErrorMsg();
                $dberror=true;
            }
            $lastFieldID = $objDatabase->Insert_ID();

            foreach ($this->languages as $langID => $language) {
                if(!$dberror) {
                    $query="    INSERT INTO ".DBPREFIX."module_immo_fieldname
                                                (id,
                                                field_id,
                                                lang_id,
                                                name)
                                            VALUES
                                                (NULL,
                                                '".$lastFieldID."',
                                                '".$langID."',
                                                '".$_REQUEST['lang_'.$langID][$i]."')";
                    if($objDatabase->Execute($query) === false) {
                        $dberror=true;
                    }
                }
            }

        }
        if($dberror) {
            $this->_strErrMessage = $_ARRAYLANG['TXT_IMMO_DB_ERROR'] ." ".$objDatabase->ErrorMsg();
        }else {
            $this->_strOkMessage = $_ARRAYLANG['TXT_IMMO_SUCCESSFULLY_INSERTED'];
        }
        $this->_showSettings();
    }


    /**
     * Modify fields
     *
     * Saves the modified fields
     * @global $objDatabase
     * @global $_ARRAYLANG
     * @access  private
     */
    function _modfields() {
        global $objDatabase, $_ARRAYLANG;
        $this->_getFieldNames();

        $checked = array();

        foreach ($_POST as $key => $value) {
            if (preg_match("/^value\_[0-9]+_[0-9]+$/", $key)) {
                $singleVals = explode("_", $key);
                $id = $singleVals[1];
                $langId = $singleVals[2];
                $newType = $_POST['select_list_'.$id];
                $newOrder = $_POST['order_'.$id];
                $newMandatory = $_POST['field_mandatory_'.$id];
                $value=trim($value);
                if ($this->fieldNames[$id]['names'][$langId] != $value) {
                    $query = "  UPDATE ".DBPREFIX."module_immo_fieldname
                                SET `name` = '".$value."'
                                WHERE `field_id` = '".$id."'
                                AND `lang_id` = '".$langId."'";
                    $objDatabase->Execute($query);
                }

                if (!isset($checked[$id])) {
                    $checked[$id] = true;
                    if ($this->fieldNames[$id]['type'] != $newType) {
                        $query = "  UPDATE ".DBPREFIX."module_immo_field
                                    SET `type` = '".$newType."'
                                    WHERE `id` = '".$id."'";
                        $objDatabase->Execute($query);
                    }

                    if ($this->fieldNames[$id]['order'] != $newOrder) {
                        $query = "  UPDATE ".DBPREFIX."module_immo_field
                                    SET `order` = '".$newOrder."'
                                    WHERE `id` = '".$id."'";
                        $objDatabase->Execute($query);
                    }

                    if ($this->fieldNames[$id]['mandatory'] != $newMandatory) {
                        $query = "  UPDATE ".DBPREFIX."module_immo_field
                                    SET `mandatory` = '".$newMandatory."'
                                    WHERE `id` = '".$id."'";
                        $objDatabase->Execute($query);
                    }
                }
            }
        }

        \Cx\Core\Csrf\Controller\Csrf::header("Location: ?cmd=immo&act=settings");
        exit;
    }

    /**
     * Get Field Type List
     *
     * List with the types. For the settings page
     * @param int $id Needed for naming the name
     * @param text $select The one entry which should be selected
     * @global $_ARRAYLANG
     * @return string
     */
    function _getFieldTypeList($id, $select) {
        global $_ARRAYLANG;

        $selected = "selected=\"selected\"";
        $textselected = ($select == "text") ? $selected : "";
        $textareaselected  = ($select == "textarea") ? $selected : "";
        $imgselected = ($select == "img") ? $selected : "";
        $linkselected = ($select == "link") ? $selected : "";
        $plinkselected = ($select == "protected_link") ? $selected : "";
        $panoselected = ($select == "panorama") ? $selected : "";
        $digitsselected = ($select == "digits_only") ? $selected : "";
        $priceselected = ($select == "price") ? $selected : "";

        $retval = "<select name=\"select_list_".$id."\" id=\"select_list_".$id."\" style=\"display: none;\" >";
        $retval .= "<option $textselected value=\"text\">".$_ARRAYLANG['TXT_IMMO_TYPE_TEXT']."</option>";
        $retval .= "<option $textareaselected value=\"textarea\">".$_ARRAYLANG['TXT_IMMO_TYPE_TEXTAREA']."</option>";
        $retval .= "<option $imgselected value=\"img\">".$_ARRAYLANG['TXT_IMMO_TYPE_IMG']."</option>" ;
        $retval .= "<option $linkselected value=\"link\">".$_ARRAYLANG['TXT_IMMO_TYPE_LINK']."</option>" ;
        $retval .= "<option $plinkselected value=\"protected_link\">".$_ARRAYLANG['TXT_IMMO_TYPE_PROTECTED_LINK']."</option>" ;
        $retval .= "<option $panoselected value=\"panorama\">".$_ARRAYLANG['TXT_IMMO_TYPE_PANORAMA']."</option>" ;
        $retval .= "<option $digitsselected value=\"digits_only\">".$_ARRAYLANG['TXT_IMMO_TYPE_DIGITS_ONLY']."</option>" ;
        $retval .= "<option $priceselected value=\"price\">".$_ARRAYLANG['TXT_IMMO_TYPE_PRICE']."</option>" ;
        $retval .= "</select>";

        return $retval;
    }

}
?>
