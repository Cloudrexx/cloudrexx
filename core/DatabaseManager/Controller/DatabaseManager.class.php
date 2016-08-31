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
 * Database Manager class
 *
 * CMS Database Manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  core_databasemanager
 * @author      Thomas Kaelin <thomas.kaelin@astalvista.ch> (Pre 2.1.0)
 * @author      Reto Kohli <reto.kohli@comvation.com> (Version 2.1.0)
 * @version     2.1.0
 */

namespace Cx\Core\DatabaseManager\Controller;

/**
 * Error reporting level
 * @ignore
 */
define('_DBM_DEBUG', 0);

/**
 * Database Manager class
 *
 * CMS Database Manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @package     cloudrexx
 * @subpackage  core_databasemanager
 * @author      Thomas Kaelin <thomas.kaelin@astalvista.ch> (Pre 2.1.0)
 * @author      Reto Kohli <reto.kohli@comvation.com> (Version 2.1.0)
 * @version     2.1.0
 * @internal    Use the core Filetype class to handle MIME types
 */
class DatabaseManager
{
    /**
     * @var string
     * @desc page title
     */
    public $_strPageTitle;

    /**
     * @var string
     * @desc status message (okay message)
     */
    private static $strOkMessage = '';

    private $act = '';

    /**
     * Constructor
     * @global  \Cx\Core\Html\Sigma
     * @global  array
     */
    function __construct() {}

    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable('CONTENT_NAVIGATION',
            '<a href="index.php?cmd=DatabaseManager" class="'.($this->act == '' ? 'active' : '').'">'.$_ARRAYLANG['TXT_DBM_MAINTENANCE_TITLE'].'</a>'.
            (\Permission::hasAllAccess()
                ? '<a href="index.php?cmd=DatabaseManager&amp;act=status" class="'.($this->act == 'status' ? 'active' : '').'">'.$_ARRAYLANG['TXT_DBM_STATUS_TITLE'].'</a>'
                : ''
            )
        );
    }


    /**
     * Dispatches to the desired function.
     * @global  \Cx\Core\Html\Sigma $objTemplate
     */
    function getPage()
    {
        global $objTemplate;

        if (!isset($_GET['act'])) $_GET['act'] = '';

        // Check permission to access this module
        \Permission::checkAccess(20, 'static');

        switch ($_GET['act']) {
            case 'showTable':
                if (\Permission::hasAllAccess()) {
                    $this->showTable($_GET['table']);
                } else {
                    \Permission::noAccess();
                }
                break;
            case 'optimize':
                \Permission::checkAccess(41, 'static');
                $this->optimizeDatabase();
                $this->showMaintenance();
                break;
            case 'repair':
                \Permission::checkAccess(41, 'static');
                $this->repairDatabase();
                $this->showMaintenance();
                break;
            case 'status':
                if (\Permission::hasAllAccess()) {
                    $this->showStatus();
                } else {
                    \Permission::noAccess();
                }
                break;
            default:
                \Permission::checkAccess(41, 'static');
                $this->showMaintenance();
                break;
        }
        $objTemplate->setVariable(array(
            'CONTENT_TITLE' => $this->_strPageTitle,
            'CONTENT_OK_MESSAGE' => self::$strOkMessage,
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }


    /**
     * Shows useful information about the database.
     *
     * @global  \Cx\Core\Html\Sigma
     * @global  ADONewConnection
     * @global  array
     * @global  array
     */
    function showStatus()
    {
        global  $objTemplate, $objDatabase, $_ARRAYLANG, $_DBCONFIG;

        $this->_strPageTitle = $_ARRAYLANG['TXT_DBM_STATUS_TITLE'];
        $objTemplate->addBlockfile('ADMIN_CONTENT', 'status', 'dbm_status.html');
        $objTemplate->setVariable(array(
            'TXT_STATUS_TITLE' => $_ARRAYLANG['TXT_DBM_STATUS_TITLE'],
            'TXT_STATUS_VERSION' => $_ARRAYLANG['TXT_DBM_STATUS_MYSQL_VERSION'],
            'TXT_STATUS_TABLES' => $_ARRAYLANG['TXT_DBM_STATUS_USED_TABLES'],
            'TXT_STATUS_SIZE' => $_ARRAYLANG['TXT_DBM_STATUS_USED_SPACE'],
            'TXT_STATUS_BACKLOG' => $_ARRAYLANG['TXT_DBM_STATUS_BACKOG'],
            'TXT_CONNECTION_TITLE' => $_ARRAYLANG['TXT_DBM_CONNECTION_TITLE'],
            'TXT_CONNECTION_DBPREFIX' => $_ARRAYLANG['TXT_DBM_CONNECTION_DBPREFIX'],
            'TXT_CONNECTION_DATABASE' => $_ARRAYLANG['TXT_DBM_CONNECTION_DATABASE'],
            'TXT_CONNECTION_USERNAME' => $_ARRAYLANG['TXT_DBM_CONNECTION_USERNAME'],
        ));

        // Get version
        $objResult = $objDatabase->Execute('SELECT VERSION()');
        $strVersion = $objResult->fields['VERSION()'];

        // Get table status
        $objResult = $objDatabase->Execute('SHOW TABLE STATUS LIKE "'.DBPREFIX.'%"');
        $intTables = $objResult->RecordCount();

        $intSize = 0;
        $intBacklog = 0;
        while (!$objResult->EOF) {
            $intSize    += $objResult->fields['Data_length'] + $objResult->fields['Index_length'];
            $intBacklog += $objResult->fields['Data_free'];
            $objResult->MoveNext();
        }

        $objTemplate->setVariable(array(
            'STATUS_VERSION' => $strVersion,
            'STATUS_TABLES' => $intTables,
            'STATUS_SIZE' => $this->convertBytesToKBytes($intSize),
            'STATUS_BACKLOG' => $this->convertBytesToKBytes($intBacklog),
            'CONNECTION_DBPREFIX' => DBPREFIX,
            'CONNECTION_DATABASE' => $_DBCONFIG['database'],
            'CONNECTION_USERNAME' => $_DBCONFIG['user'],
        ));

        //Filter mySQL-Info
        ob_start();
        phpinfo();
        $strPhpInfo = ob_get_contents();
        ob_end_clean();

        $arrBlocks = array();
        $arrTables = array();
        $arrColumns = array();
        $arrRows = array();

        //Collect all blocks containing mysql-information
        preg_match_all('/<h2><a name="module_mysql.*">mysql.*<\/a><\/h2>(.*<\/table><br \/>) {2}\n/sU', $strPhpInfo, $arrBlocks);    //Modifier s = Use string as single-row, Modifier U = just be Ungreedy!
        foreach ($arrBlocks[0] as $strBlock) {
            // Get title of the block
            $strTitle = preg_replace('/<h2>.*>(.*)<\/a><\/h2>.*/s', '$1', $strBlock);
            // Get tables of the block
            preg_match_all('/<table.*<\/table>/sU', $strBlock, $arrTables);
            foreach ($arrTables[0] as $intTableKey => $strTable) {
                //Get column-headers of this table
                $strColumnHeaders = '';
                preg_match_all('/<th>.*<\/th>/U', $strTable, $arrColumns);

                $intColumWidthLast = 100; //Used to calculate the column-width.
                foreach ($arrColumns[0] as $intColumnKey => $strColumn) {
                    $strColumnHeaders .= preg_replace('/<th>(.*)<\/th>/', '<td width="'.(($intColumnKey + 1 == count($arrColumns[0])) ? $intColumWidthLast : 15).'%" nowrap="nowrap">$1</td>', $strColumn);
                    $intColumWidthLast -= 15;
                }

                $objTemplate->setVariable(array(
                    'TABLE_TITLES_CLASS' => ($intTableKey == 0) ? 'row1' : 'row3',
                    'TABLE_TITLES' => $strColumnHeaders
                ));

                //Get content of this table
                $strRowContent = '';
                preg_match_all('/<tr><td class="e">.*<\/td><\/tr>/sU', $strTable, $arrRows);

                foreach($arrRows[0] as $intRowKey => $strRow) {
                    $strRow = preg_replace('/<tr>/U', '<tr class="row'.($intRowKey % 2).'">', $strRow);
                    $strRowContent .= preg_replace('/<td class=".*">(.*)<\/td>/U', '<td>$1</td>', $strRow);
                }
                $objTemplate->setVariable('TABLE_CONTENT', $strRowContent);
                $objTemplate->parse('showPhpTables');
            }
            $objTemplate->setVariable(array(
                'TXT_PHPINFO_TITLE' => $_ARRAYLANG['TXT_DBM_STATUS_PHPINFO'],
                'BLOCK_TITLE' => $strTitle
            ));
            $objTemplate->parse('showPhpBlocks');
        }
    }


    /**
     * Shows the database-maintenance page.
     *
     * @global     \Cx\Core\Html\Sigma
     * @global     ADONewConnection
     * @global     array
     */
    function showMaintenance()
    {
        global  $objTemplate, $objDatabase, $_ARRAYLANG;

        $this->_strPageTitle = $_ARRAYLANG['TXT_DBM_MAINTENANCE_TITLE'];

        $objTemplate->addBlockfile('ADMIN_CONTENT', 'maintenance', 'dbm_maintenance.html');
        $objTemplate->setVariable(array(
            'TXT_MAINTENANCE_OPTIMIZE_TITLE' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_OPTIMIZE_DB'],
            'TXT_MAINTENANCE_OPTIMIZE_BUTTON' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_OPTIMIZE_START'],
            'TXT_MAINTENANCE_OPTIMIZE_DESC' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_OPTIMIZE_DESC'],
            'TXT_MAINTENANCE_REPAIR_TITLE' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_REPAIR_DB'],
            'TXT_MAINTENANCE_REPAIR_BUTTON' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_REPAIR_START'],
            'TXT_MAINTENANCE_REPAIR_DESC' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_REPAIR_DESC'],
            'TXT_MAINTENANCE_TITLE_TABLES' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_TABLES'],
            'TXT_MAINTENANCE_TABLES_NAME' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_TABLENAME'],
            'TXT_MAINTENANCE_TABLES_ROWS' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_ROWS'],
            'TXT_MAINTENANCE_TABLES_DATA' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_DATA_SIZE'],
            'TXT_MAINTENANCE_TABLES_INDEXES' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_INDEX_SIZE'],
            'TXT_MAINTENANCE_TABLES_BACKLOG' => $_ARRAYLANG['TXT_DBM_STATUS_BACKOG'],
            'TXT_MAINTENANCE_TABLES_SELECT_ALL' => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_MAINTENANCE_TABLES_DESELECT_ALL' => $_ARRAYLANG['TXT_DESELECT_ALL'],
            'TXT_MAINTENANCE_TABLES_SUBMIT_SELECT' => $_ARRAYLANG['TXT_MULTISELECT_SELECT'],
            'TXT_MAINTENANCE_TABLES_SUBMIT_OPTIMIZE' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_OPTIMIZE_START'],
            'TXT_MAINTENANCE_TABLES_SUBMIT_REPAIR' => $_ARRAYLANG['TXT_DBM_MAINTENANCE_REPAIR_START'],
        ));

        //Get tables
        $objResult = $objDatabase->Execute('SHOW TABLE STATUS LIKE "'.DBPREFIX.'%"');
        $intRowCounter = 0;

        //Iterate through tables
        while (!$objResult->EOF) {
            $isInnoDbEngine = $objResult->fields['Engine'] == 'InnoDB';
            $objTemplate->setGlobalVariable(array(
                'TXT_MAINTENANCE_SHOW_TABLE' => $_ARRAYLANG['TXT_DBM_SHOW_TABLE_TITLE'],
                'MAINTENANCE_TABLES_NAME' => $objResult->fields['Name']
            ));

            $objTemplate->setVariable(array(
                'MAINTENANCE_TABLES_ROW' => (!$isInnoDbEngine && $objResult->fields['Data_free'] != 0) ? 'Warn' : (($intRowCounter % 2 == 0) ? 2 : 1),
                'MAINTENANCE_TABLES_ROWS' => $objResult->fields['Rows'],
                'MAINTENANCE_TABLES_DATA' => $this->convertBytesToKBytes($objResult->fields['Data_length']),
                'MAINTENANCE_TABLES_INDEXES' => $this->convertBytesToKBytes($objResult->fields['Index_length']),
                'MAINTENANCE_TABLES_BACKLOG' => $isInnoDbEngine ? '0' : $this->convertBytesToKBytes($objResult->fields['Data_free']),
            ));

            if (\Permission::hasAllAccess()) {
                $objTemplate->touchblock('showTableContentLink');
                $objTemplate->hideBlock('showTableContentNoLink');
            } else {
                $objTemplate->touchblock('showTableContentNoLink');
                $objTemplate->hideBlock('showTableContentLink');
            }

            $objTemplate->parse('showTables');

            ++$intRowCounter;
            $objResult->MoveNext();
        }
    }


    /**
     * Optimizes some or all tables (depending on the POST-Array) used by Cloudrexx.
     *
     * @global     ADONewConnection
     * @global     array
     */
    function optimizeDatabase()
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_POST['selectedTablesName'])) {
            //User selected specific tables
            foreach ($_POST['selectedTablesName'] as $strTableName) {
                $objDatabase->Execute('OPTIMIZE TABLE '.$strTableName);
            }
        } else {
            //No tables selected, just optimize everything.
            $objResult = $objDatabase->Execute('SHOW TABLE STATUS LIKE "'.DBPREFIX.'%"');

            while(!$objResult->EOF) {
                $objDatabase->Execute('OPTIMIZE TABLE '.$objResult->fields['Name']);
                $objResult->MoveNext();
            }
        }

        self::addMessage($_ARRAYLANG['TXT_DBM_MAINTENANCE_OPTIMIZE_DONE']);
    }

    /**
     * Repairs some or all tables (depending on the POST-Array) used by Cloudrexx.
     *
     * @global     ADONewConnection
     * @global     array
     */
    function repairDatabase()
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_POST['selectedTablesName'])) {
            //User selected specific tables
            foreach ($_POST['selectedTablesName'] as $strTableName) {
                $objDatabase->Execute('REPAIR TABLE '.$strTableName);
            }
        } else {
            //No tables selected, just repair everything.
            $objResult = $objDatabase->Execute('SHOW TABLE STATUS LIKE "'.DBPREFIX.'%"');

            while(!$objResult->EOF) {
                $objDatabase->Execute('REPAIR TABLE '.$objResult->fields['Name']);
                $objResult->MoveNext();
            }
        }

        self::addMessage($_ARRAYLANG['TXT_DBM_MAINTENANCE_REPAIR_DONE']);
    }


    /**
     * Converts an number of Bytes into Kilo Bytes.
     *
     * @return      float       converted size
     */
    private function convertBytesToKBytes($intNumberOfBytes) {
        $intNumberOfBytes = intval($intNumberOfBytes);
        return round($intNumberOfBytes / 1024, 2);
    }


    /**
     * Adds the string $strOkMessage to the success messages.
     *
     * If necessary, inserts a line break tag (<br />) between
     * messages.
     * @static
     * @param   string  $strOkMessage       The message to add
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function addMessage($strOkMessage)
    {
        self::$strOkMessage .=
            (self::$strOkMessage != '' && $strOkMessage != ''
                ? '<br />' : ''
            ).$strOkMessage;
    }

}
