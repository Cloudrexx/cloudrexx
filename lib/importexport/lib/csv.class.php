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
 * CSV Library Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_importexport
 */

/**
 * CSV Library Class
 * Class which handles csv files
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_importexport
 */
class CsvLib
{
    var $separator = ";";
    var $enclosure = "\"";

    /**
     * Constructor
     *
     * Gets the options
     */
    function __construct()
    {
            if (!isset($_POST['import_options_csv_separator'])) {
                return false;
            }
        $this->separator = contrexx_stripslashes($_POST['import_options_csv_separator']);
        if ($this->separator == '\t') {
            $this->separator = "\t";
        }

        if (isset($_POST['import_options']) && strlen($_POST['import_options']) == 1) {
            $this->enclosure = $_POST['import_options_csv_enclosure'];
        }
    }

    /**
     * PHP 4 Constructor
     *
     * @return CsvLib
     */
    function CsvLib()
    {
        $this->__construct();
    }


    /**
     * Returns the content of a csv file
     *
     * @param string $file
     * @param bool $columnNamesInFirstRow should the first row's values be taken as column names?
     * @param bool $firstRowIsAlsoData if columnNamesInFirstRow is true, are those fields also values?
     * @param bool $limit Limit
     * @return array
     *
     * Array
        (
            [fieldnames] => Array
                (
                    [0] => Name
                    [1] => Vorname
                    [2] => test
                )

            [data] => Array
                (
                    [0] => Array
                        (
                            [0] => Wert1
                            [1] => Wert1.2
                            [2] => Wert1.3
                        )

                )

        )
     */
    function parse($file,$columnNamesInFirstRow=false, $firstRowIsAlsoData=false, $looplimit=-1)
    {

        // detect newlines correctly. bit slower, but in exchange
        // we can import old apple CSV files.
        ini_set('auto_detect_line_endings', 1);

        // try to convert the file to the system charset CONTREXX_CHARSET if required
        if(function_exists("mb_detect_encoding")) {
            $content = file_get_contents($file);
            $encoding = mb_detect_encoding($content);
            if($encoding != CONTREXX_CHARSET) {
                $content = utf8_encode($content);
                file_put_contents($file, $content);
            }
        }

        $handle = fopen($file, "r");

        if ($handle) {
            $firstline = true;
            $lastColumnEmpty = true;

            // Get the longest line
            $limit = $looplimit;
            $len = 0;
            while (!feof($handle) && $limit != 0) {
                $length = strlen(fgets($handle));
                $len = ($length > $len) ? $length : $len;
                $limit--;
            }

            // Set the pointer back to 0
            fseek($handle, 0);

            $limit = $looplimit;
            while (($data = fgetcsv($handle, $len, $this->separator, $this->enclosure)) && $limit != 0) {
                $dataAvailable = false;
                foreach ($data as $value) {
                    if (!empty($value)) {
                        $dataAvailable = true;
                        break;
                    }
                }

                if ($dataAvailable || $looplimit == 1) {
                    //add fields to data if it's not the first row and it contains only titles
                    if (!$firstline || !$columnNamesInFirstRow || $firstRowIsAlsoData) {
                        $retdata['data'][] = $data;
                    }
                    //set field names if they are specified in the first row
                    if ($firstline && $columnNamesInFirstRow) {
                    	foreach ($data as $index => $field) {
                    		if (empty($field)){
                    			$field = "emptyField_$index";
                    		}
                    		$retdata['fieldnames'][] = $field;
                    	}
                    	$firstline = false;
                    }
                }
                $limit--;

                if (!empty($data[count($data)-1])) {
                    $lastColumnEmpty = false;
                }
            }

            if ($lastColumnEmpty) {
                unset($retdata['fieldnames'][count($retdata['fieldnames'])-1]);
                foreach ($retdata['data'] as &$arrValue) {
                    unset($arrValue[count($arrValue)-1]);
                }
            }

            fclose($handle);
            return $retdata;
        }
    }
}
