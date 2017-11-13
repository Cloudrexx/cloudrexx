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
 * Graph
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_banner
 * @todo        Edit PHP DocBlocks!
 */

error_reporting(0);

/**
 * Includes
 */
global $objDatabase;
require_once dirname(__FILE__).'/../../core/Core/init.php';
$cx = init('minimal');
include ASCMS_LIBRARY_PATH.'/ykcee/ykcee.php';
$objDatabase = $cx->getDb()->getAdoDb();

/**
 * Banner management
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_banner
 */
class makeGraph
{
    public $stats = '';
    public $graphWidth = 200;
    public $graphHeight = 100;
    public $graphBackgroundColor = 'white';
    public $graphChartType = 'bars';
    public $graphChartBackgroundColor = 'white';
    public $graphChartBorderColor = 'white';
    public $graphChartTitle = '';
    public $graphChartTitleSize = 10;
    public $graphArrBarColor = array('blue','red');
    public $graphArrBarBorderColor = array('blue','red');
    public $graphArrLegendText = array();
    public $graphLegendBackgroundColor = 'white';
    public $graphTitleAxisX = '';
    public $graphTitleAxisY = '';
    public $graphGridX = 10;
    public $graphGridY = 10;
    public $graphGridColor = 'silver';
    public $graphAxisXMaxStringSize = 4;
    public $graphAxisFontSize = 7;
    public $graphAxisTitleFontSize = 6;
    public $graphArrData = array();
    public $graphScaleMax = 10;
    public $graphMarginLeft = 10;
    public $graphMarginRight = 10;
    public $graphMarginTop = 10;
    public $graphMarginBottom = 10;
    public $graphFrame = false;
    public $graphColor = '#c8d7ee';


    public function __construct()
    {
        if (isset($_GET['banner_id']) && !empty($_GET['banner_id'])) {
            $this->_makeRequestsYearsGraph(intval($_GET['banner_id']));
        }
    }


    function _makeRequestsYearsGraph($banner_id)
    {
        global $objDatabase;

        $arrBarPlot1 = array();
        $arrBarPlot2 = array();

// Never used
//        $arrBarPlot1Keys = array();
//        $arrBarPlot2Keys = array();

        $arrData = array();

        // get statistics
        $query = 'SELECT id, views, clicks
                    FROM '.DBPREFIX.'module_banner_system
                    WHERE id='.$banner_id.' LIMIT 1 ';

        $result = $objDatabase->Execute($query);
        if ($result) {
            while (true) {
                $arrResult = $result->FetchRow();
                if (empty($arrResult)) break;
                $arrBarPlot1[1] = $arrResult['views'];
                $arrBarPlot2[1] = $arrResult['clicks'];
            }
        }

        reset($arrBarPlot2);

        $arrData[1] = array('', $arrBarPlot1[1], $arrBarPlot2[1]);

        $this->graphAxisXMaxStringSize = 1;
        $this->graphArrLegendText = array('Anzeigen', 'Klicks');
        $this->graphTitleAxisX = '';
        $this->graphChartTitle = 'Diagramm';
        $this->graphArrData = $arrData;

        $this->_generateGraph();
    }



    function _generateGraph()
    {
        global $_ARRAYLANG;

        $graph = new ykcee;
        $graph->SetImageSize($this->graphWidth, $this->graphHeight);
        $graph->SetTitleFont(ASCMS_LIBRARY_PATH.'/ykcee/VERDANA.TTF');
        $graph->SetFont(ASCMS_LIBRARY_PATH.'/ykcee/VERDANA.TTF');
        $graph->SetFileFormat('png');
        $graph->SetMaxStringSize($this->graphAxisXMaxStringSize);
        $graph->SetBackgroundColor($this->graphBackgroundColor);
        $graph->SetChartType($this->graphChartType);
        $graph->SetChartBackgroundColor($this->graphChartBackgroundColor);
        $graph->SetChartBorderColor($this->graphChartBorderColor);
        $graph->SetChartTitle($this->graphChartTitle);
        $graph->SetChartTitleSize($this->graphChartTitleSize);
        $graph->SetChartTitleColor('black');
        $graph->SetFontColor('black');
        $graph->SetBarColor($this->graphArrBarColor);
        $graph->SetBarBorderColor($this->graphArrBarBorderColor);
        $graph->SetLegend($this->graphArrLegendText);
        $graph->SetLegendPosition(2);
        $graph->SetLegendBackgroundColor($this->graphLegendBackgroundColor);
        $graph->SetTitleAxisX($this->graphTitleAxisX);
        $graph->SetTitleAxisY($this->graphTitleAxisY);
        $graph->SetAxisFontSize($this->graphAxisFontSize);
        $graph->SetAxisColor('black');
        $graph->SetAxisTitleSize($this->graphAxisTitleFontSize);
        $graph->SetTickLength(2);
        $graph->SetTickInterval(5);
        $graph->SetGridX($this->graphGridX);
        $graph->SetGridY($this->graphGridY);
        $graph->SetGridColor($this->graphGridColor);
        $graph->SetLineThickness(1);
        $graph->SetPointSize(2); //es werden dringend gerade Zahlen empfohlen
        $graph->SetPointShape('dots');
        $graph->SetShading(0);
        $graph->SetNoData($_ARRAYLANG['TXT_NO_DATA_AVAILABLE']);
        $graph->SetDataValues($this->graphArrData);
        $graph->DrawGraph();
    }
}

new makeGraph();

?>
