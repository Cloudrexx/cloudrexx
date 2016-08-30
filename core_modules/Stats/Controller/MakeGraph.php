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
 * Statistics
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0
 * @package     cloudrexx
 * @subpackage  coremodule_stats
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Core_Modules\Stats\Controller;
/**
 * Make Graph
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0
 * @package     cloudrexx
 * @subpackage  coremodule_stats
 * @todo        Edit PHP DocBlocks!
 */

class MakeGraph
{
    public $stats = '';
    public $graphWidth = 600;
    public $graphHeight = 250;
    public $graphBackgroundColor = "white";
    public $graphChartType = "bars";
    public $graphChartBackgroundColor = "white";
    public $graphChartBorderColor = "white";
    public $graphChartTitle = "";
    public $graphChartTitleSize = 10;
    public $graphArrBarColor = array("blue","red");
    public $graphArrBarBorderColor = array("blue","red");
    public $graphArrLegendText = array();
    public $graphLegendBackgroundColor = "white";
    public $graphTitleAxisX = "";
    public $graphTitleAxisY = "";
    public $graphGridX = 10;
    public $graphGridY = 0;
    public $graphGridColor = "silver";
    public $graphAxisXMaxStringSize = 4;
    public $graphAxisFontSize = 7;
    public $graphAxisTitleFontSize = 10;
    public $graphArrData = array();
    public $graphScaleMax = 10;
    public $graphMarginLeft = 20;
    public $graphMarginRight = 20;
    public $graphMarginTop = 20;
    public $graphMarginBottom = 20;
    public $graphFrame = false;
    public $graphColor = "#c8d7ee";


    public function __construct()
    {
        if (isset($_GET['stats']) && !empty($_GET['stats'])) {
            $this->stats = $_GET['stats'];
        }

        switch ($this->stats) {
            case 'requests_today':
                $this->_makeRequestsHoursGraph();
                break;
            case 'requests_days':
                $this->_makeRequestsDaysGraph();
                break;
            case 'requests_months':
                $this->_makeRequestsMonthsGraph();
                break;
            case 'requests_years':
                $this->_makeRequestsYearsGraph();
                break;
            case 'referers_spider':
                $this->_makeReferersSpiderGraph();
                break;
            case 'clients_browser':
                $this->_makeClientsBrowserGraph();
                break;
            case 'clients_os':
                $this->_makeClientsOSGraph();
                break;
        }
    }


    function _makeRequestsHoursGraph()
    {
        global $objDatabase, $_ARRAYLANG;

        $arrBarPlot1 = array();
        $arrBarPlot2 = array();

        // get statistics
        $query = "SELECT FROM_UNIXTIME(`timestamp`, '%H' ) AS `hour` ,FROM_UNIXTIME(`timestamp`, '%d' ) AS `day`,  `count`
            FROM `".DBPREFIX."stats_visitors_summary`
            WHERE `timestamp` >= '".(time()-86400)."' AND `type` = 'hour' AND `count` > 0 limit 24";
        $result = $objDatabase->Execute($query);
        if ($result) {
            while (true) {
                $arrResult = $result->FetchRow();
                if (empty($arrResult)) break;
                $arrBarPlot1[$arrResult['hour']][$arrResult['day']] = $arrResult['count'];
            }
        }
        $query = "SELECT FROM_UNIXTIME(`timestamp`, '%H' ) AS `hour` ,FROM_UNIXTIME(`timestamp`, '%d' ) AS `day`,  `count`
            FROM `".DBPREFIX."stats_requests_summary`
            WHERE `timestamp` >= '".(time()-86400)."' AND `type` = 'hour' AND `count` > 0 limit 24";
        $result = $objDatabase->Execute($query);
        if ($result) {
            while (true) {
                $arrResult = $result->FetchRow();
                if (empty($arrResult)) break;
                $arrBarPlot2[$arrResult['hour']][$arrResult['day']] = $arrResult['count'];
            }
        }

        $currentHour = date('H');
        if ($currentHour < 23) {
            $arrRange[(date('d') == 1 ? date('t', time()-86400) : date('d')-1)] = range($currentHour+1, 23);
        }
        $arrRange[date('d')] = range(0, $currentHour);

        // generate arrays for the bars
        foreach ($arrRange as $day => $arrHours) {
            $strDay = sprintf("%02s",$day);
            foreach ($arrHours as $hour) {
                $strHour = sprintf("%02s",$hour);

                if (!isset($arrBarPlot1[$strHour][$strDay])){
                    $arrBarPlot1[$strHour][$strDay] = 0;
                }
                if (!isset($arrBarPlot2[$strHour][$strDay])){
                    $arrBarPlot2[$strHour][$strDay] = 0;
                }
                $arrData[$strHour.$strDay] = array($strHour, $arrBarPlot1[$strHour][$strDay], $arrBarPlot2[$strHour][$strDay]);
            }
        }

        $this->graphChartTitle = date('j').'. '.date('M');
        $this->graphArrLegendText = array($_ARRAYLANG['TXT_VISITORS'], $_ARRAYLANG['TXT_PAGE_VIEWS']);
        $this->graphTitleAxisX = $_ARRAYLANG['TXT_HOUR'];
        $this->graphArrData = $arrData;
        $this->_generateGraph();
    }


    function _makeRequestsDaysGraph()
    {
        global $objDatabase, $_ARRAYLANG;

        $arrBarPlot1 = array();
        $arrBarPlot2 = array();
        $arrData = array();

        // get statistics
        $query = "SELECT FROM_UNIXTIME(`timestamp`, '%d' ) AS `day`, FROM_UNIXTIME(`timestamp`, '%m' ) AS `month` , `count`
            FROM `".DBPREFIX."stats_visitors_summary`
            WHERE `type` = 'day' AND `count` > 0 AND `timestamp` >= '".(time()-3456000)."'";
        $result = $objDatabase->Execute($query);
        if ($result) {
            while (true) {
                $arrResult = $result->FetchRow();
                if (empty($arrResult)) break;
                $arrBarPlot1[$arrResult['day']][$arrResult['month']] = $arrResult['count'];
            }
        }

        $query = "SELECT FROM_UNIXTIME(`timestamp`, '%d' ) AS `day`, FROM_UNIXTIME(`timestamp`, '%m' ) AS `month` , `count`
            FROM `".DBPREFIX."stats_requests_summary`
            WHERE `type` = 'day' AND `count` > 0 AND `timestamp` >= '".(time()-3456000)."'";
        $result = $objDatabase->Execute($query);
        if ($result) {
            while (true) {
                $arrResult = $result->FetchRow();
                if (empty($arrResult)) break;
                $arrBarPlot2[$arrResult['day']][$arrResult['month']] = $arrResult['count'];
            }
        }

        $arrRange = array();
        if (date('d') < date('t')) {
            $arrRange[$previousMonth = (date('m') == 1 ? 12 : date('m')-1)] = range(
                date('d') + 1 > ($daysOfPreviousMonth = date('t', mktime(0,0,0,$previousMonth,1,$previousYear,$previousYear = (date('m') == 1 ? date('Y') -1 : date('Y')))))
                    ?    $daysOfPreviousMonth
                    :    date('d') + 1,
                $daysOfPreviousMonth
            );
        }
        $arrRange[date('m')] = range(1, date('d'));

        // generate arrays for the bars
        foreach ($arrRange as $month => $arrDays) {
            $strMonth = sprintf("%02s",$month);
            foreach ($arrDays as $day) {
                $strDay = sprintf("%02s",$day);

                if (!isset($arrBarPlot1[$strDay][$strMonth])){
                    $arrBarPlot1[$strDay][$strMonth] = 0;
                }
                if (!isset($arrBarPlot2[$strDay][$strMonth])){
                    $arrBarPlot2[$strDay][$strMonth] = 0;
                }
                $arrData[$strDay.$strMonth] = array($strDay.' '.date('M',mktime(0,0,0,$month,1,date('Y'))), $arrBarPlot1[$strDay][$strMonth], $arrBarPlot2[$strDay][$strMonth]);
            }
        }

        $arrMonth = explode(',',$_ARRAYLANG['TXT_MONTH_ARRAY']);
        $this->graphAxisXMaxStringSize = 7;
        $this->graphChartTitle = $arrMonth[date('n')-1];
        $this->graphArrLegendText = array($_ARRAYLANG['TXT_VISITORS'], $_ARRAYLANG['TXT_PAGE_VIEWS']);
        $this->graphTitleAxisX = $_ARRAYLANG['TXT_DAY'];
        $this->graphArrData = $arrData;
        $this->_generateGraph();
    }


    function _makeRequestsMonthsGraph()
    {
        global $objDatabase, $_ARRAYLANG;

        $arrBarPlot1 = array();
        $arrBarPlot2 = array();
        $arrData = array();

        // get statistics
        $query = "SELECT FROM_UNIXTIME(`timestamp`, '%m' ) AS `month` , FROM_UNIXTIME(`timestamp`, '%y' ) AS `year`, `count`
            FROM `".DBPREFIX."stats_visitors_summary`
            WHERE `type` = 'month' AND `count` > 0 AND `timestamp` >= '".mktime(0, 0, 0, date('m'), null, date('Y')-2)."'";
        $result = $objDatabase->Execute($query);
        if ($result) {
            while (true) {
                $arrResult = $result->FetchRow();
                if (empty($arrResult)) break;
                $arrBarPlot1[$arrResult['month']][$arrResult['year']] = $arrResult['count'];
            }
        }

        $query = "SELECT FROM_UNIXTIME(`timestamp`, '%m' ) AS `month` , FROM_UNIXTIME(`timestamp`, '%y' ) AS `year`, `count`
            FROM `".DBPREFIX."stats_requests_summary`
            WHERE `type` = 'month' AND `count` > 0 AND `timestamp` >= '".mktime(0, 0, 0, date('m'), null, date('Y')-2)."'";
        $result = $objDatabase->Execute($query);
        if ($result) {
            while (true) {
                $arrResult = $result->FetchRow();
                if (empty($arrResult)) break;
                $arrBarPlot2[$arrResult['month']][$arrResult['year']] = $arrResult['count'];
            }
        }

        if (date('m')<12) {
            $arrRange[date('y')-2] = range(date('m')+1, 12);
        }
        $arrRange[date('y')-1] = range(1, 12);
        $arrRange[date('y')] = range(1, date('m'));

        // generate arrays for the bars
        foreach ($arrRange as $year => $arrMonths) {
            $strYear = sprintf("%02s", $year);
            foreach ($arrMonths as $month) {
                $strMonth = sprintf("%02s",$month);
                if (!isset($arrBarPlot1[$strMonth][$strYear])){
                    $arrBarPlot1[$strMonth][$strYear] = 0;
                }
                if (!isset($arrBarPlot2[$strMonth][$strYear])){
                    $arrBarPlot2[$strMonth][$strYear] = 0;
                }
                $arrData[$strMonth.$strYear] = array(' '.date('M',mktime(0,0,0,$month,1,date('Y'))).' '.$strYear, $arrBarPlot1[$strMonth][$strYear], $arrBarPlot2[$strMonth][$strYear]);
            }
        }

        $this->graphAxisXMaxStringSize = 7;
        $this->graphArrLegendText = array($_ARRAYLANG['TXT_VISITORS'], $_ARRAYLANG['TXT_PAGE_VIEWS']);
        $this->graphChartTitle = date('Y');
        $this->graphTitleAxisX = $_ARRAYLANG['TXT_MONTH'];
        $this->graphArrData = $arrData;

        $this->_generateGraph();
    }


    function _makeRequestsYearsGraph()
    {
        global $objDatabase, $_ARRAYLANG;

        $arrBarPlot1 = array();
        $arrBarPlot2 = array();

// Never used
//        $arrBarPlot1Keys = array();
//        $arrBarPlot2Keys = array();

        $arrData = array();

        // get statistics
        $query = "SELECT FROM_UNIXTIME(`timestamp`, '%Y' ) AS `year` , `count`
            FROM `".DBPREFIX."stats_visitors_summary`
            WHERE `type` = 'year'
            ORDER BY `year`";
        $result = $objDatabase->Execute($query);
        if ($result) {
            while (true) {
                $arrResult = $result->FetchRow();
                if (empty($arrResult)) break;
                $arrBarPlot1[$arrResult['year']] = $arrResult['count'];
            }
        }

        $query = "SELECT FROM_UNIXTIME(`timestamp`, '%Y' ) AS `year` , `count`
            FROM `".DBPREFIX."stats_requests_summary`
            WHERE `type` = 'year'
            ORDER BY `year`";
        $result = $objDatabase->Execute($query);
        if ($result) {
            while (true) {
                $arrResult = $result->FetchRow();
                if (empty($arrResult)) break;
                $arrBarPlot2[$arrResult['year']] = $arrResult['count'];
            }
        }

        reset($arrBarPlot2);

        $startYear = key($arrBarPlot2);
        $endYear = date('Y');

        // generate arrays for the bars
        for ($year=$startYear;$year<=$endYear;$year++) {
            if (!isset($arrBarPlot1[$year])){
                $arrBarPlot1[$year] = 0;
            }
            if (!isset($arrBarPlot2[$year])){
                $arrBarPlot2[$year] = 0;
            }
            $arrData[$year] = array($year, $arrBarPlot1[$year], $arrBarPlot2[$year]);
        }

        $this->graphAxisXMaxStringSize = 5;
        $this->graphArrLegendText = array($_ARRAYLANG['TXT_VISITORS'], $_ARRAYLANG['TXT_PAGE_VIEWS']);
        $this->graphTitleAxisX = $_ARRAYLANG['TXT_YEAR'];
        $this->graphChartTitle = $startYear.' - '. $endYear;
        $this->graphArrData = $arrData;

        $this->_generateGraph();
    }


    function _generateGraph()
    {
        global $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();

        $graph = new \ykcee;
        $graph->SetImageSize($this->graphWidth, $this->graphHeight);
        $graph->SetTitleFont($cx->getCodeBaseLibraryPath().'/ykcee/VERDANA.TTF');
        $graph->SetFont($cx->getCodeBaseLibraryPath().'/ykcee/VERDANA.TTF');
        $graph->SetFileFormat("png");
        $graph->SetMaxStringSize($this->graphAxisXMaxStringSize);
        $graph->SetBackgroundColor($this->graphBackgroundColor);
        $graph->SetChartType($this->graphChartType);
        $graph->SetChartBackgroundColor($this->graphChartBackgroundColor);
        $graph->SetChartBorderColor($this->graphChartBorderColor);
        $graph->SetChartTitle($this->graphChartTitle);
        $graph->SetChartTitleSize($this->graphChartTitleSize);
        $graph->SetChartTitleColor("black");
        $graph->SetFontColor("black");
        $graph->SetBarColor($this->graphArrBarColor);
        $graph->SetBarBorderColor($this->graphArrBarBorderColor);
        $graph->SetLegend($this->graphArrLegendText);
        $graph->SetLegendPosition(2);
        $graph->SetLegendBackgroundColor($this->graphLegendBackgroundColor);
        $graph->SetTitleAxisX($this->graphTitleAxisX);
        $graph->SetTitleAxisY($this->graphTitleAxisY);
        $graph->SetAxisFontSize($this->graphAxisFontSize);
        $graph->SetAxisColor("black");
        $graph->SetAxisTitleSize($this->graphAxisTitleFontSize);
        $graph->SetTickLength(2);
        $graph->SetTickInterval(5);
        $graph->SetGridX($this->graphGridX);
        $graph->SetGridY($this->graphGridY);
        $graph->SetGridColor($this->graphGridColor);
        $graph->SetLineThickness(1);
        $graph->SetPointSize(2); //es werden dringend gerade Zahlen empfohlen
        $graph->SetPointShape("dots");
        $graph->SetShading(0);
        $graph->SetNoData($_ARRAYLANG['TXT_NO_DATA_AVAILABLE']);
        $graph->SetDataValues($this->graphArrData);
        $graph->DrawGraph();
    }
}

error_reporting(0);

/**
 * Includes
 */

global $objDatabase, $objInit, $_ARRAYLANG, $adminPage;
\Env::get('ClassLoader')->loadFile(dirname(__FILE__).'/../../core/Core/init.php');
$cx = init('minimal');
\Env::get('ClassLoader')->loadFile($cx->getCodeBaseLibraryPath().'/ykcee/ykcee.php');
$objDatabase = $cx->getDb()->getAdoDb();

$adminPage = true;
$objInit = new \InitCMS($mode="backend");

$sessionObj = \cmsSession::getInstance();
$_SESSION->cmsSessionStatusUpdate("backend");
\Permission::checkAccess(19, 'static');

$objInit->_initBackendLanguage();
$objInit->getUserFrontendLangId();

$_ARRAYLANG = $objInit->loadLanguageData('Stats');

new \Cx\Core_Modules\Stats\Controller\MakeGraph();

?>
