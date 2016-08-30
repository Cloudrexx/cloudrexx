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
 * Read and write CSV files
 *
 * @category   Csv_bv
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */

namespace Cx\Modules\Crm\Controller;

/**
 * Read and write CSV files
 *
 * @category   Csv_bv
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */
class CrmCsv
{
    /**
      * Separator character
      * @var    string
      * @access private
      */
    private $mFldSeparator = ';';

    /**
      * Quote character
      * @var    string
      * @access private
      */
    private $mFldQuote = '"';

    /**
      * Escape character
      * @var    string
      * @access private
      */
    private $mFldEscape = '\\';

    /**
      * Length of the largest row in bytes
      * @var    integer
      * @access private
      */
    private $mRowSize = 4096;

    /**
      * Holds the file handle
      * @var    resource
      * @access private
      */
    private $mHandle = null;

    /**
      * Counts the number of rows that have been returned
      * @var    integer
      * @access private
      */
    private $mRowCount = 0;

    /**
      * Counts the number of empty rows that have been skipped
      * @var    integer
      * @access private
      */
    private $mSkippedRowCount = 0;

    /**
      * Determines whether empty rows should be skipped.
      *
      * By default empty rows are *NOT* skipped.
      * @var    boolean
      * @access private
      */
    private $mSkipEmptyRows = false;

    /**
      * Specifies whether the fields leading and trailing \s and \t
      * should be removed.
      *
      * Defaults to true.
      * @var    boolean
      * @access private
      */
    private $mTrimFields = true;


    /**
      * Constructor
      *
      * Only initialises class settings variables.
      * @param str $file - file path
      * @param str $separator - Only one character is allowed (optional)
      * @param str $quote - Only one character is allowed (optional)
      * @param str $escape - Only one character is allowed (optional)
      * @access public
      */
    function __construct($file='', $separator=';', $quote='"', $escape='\\')
    {
        $this->mFldSeparator = $separator;
        $this->mFldQuote = $quote;
        $this->mFldEscape = $escape;
        if (empty($file)) return;
        // Try to convert the file to the system charset CONTREXX_CHARSET,
        // if necessary
        if (function_exists("mb_detect_encoding")) {
            $content = file_get_contents($file);
            $encoding = mb_detect_encoding($content, CONTREXX_CHARSET, true);
            if ($encoding != CONTREXX_CHARSET) {
                $content = mb_convert_encoding($content, CONTREXX_CHARSET);
                file_put_contents($file, $content);
            }
        }
        // Open file if the filename is non-empty
        $this->mHandle = @fopen($file, 'r');
        if (!$this->mHandle) trigger_error('Unable to open csv file', E_USER_ERROR);
    }


    /**
      * csv::NextLine() returns an array of fields from the next csv line.
      *
      * The position of the file pointer is stored in PHP internals.
      * Empty rows can be skipped.
      * Leading and trailing \s and \t can be removed from each field.
      * @access public
      * @return array of fields
      */
    function NextLine()
    {
        $arr_row = fgetcsv($this->mHandle, $this->mRowSize, $this->mFldSeparator, $this->mFldQuote);
        if (feof($this->mHandle)) return false;
        ++$this->mRowCount;
        // Skip empty rows if asked to
        if ($this->mSkipEmptyRows) {
            if ($arr_row[0] === ''  && count($arr_row) === 1) {
                --$this->mRowCount;
                ++$this->mSkippedRowCount;
                $arr_row = $this->NextLine();
                // This is to avoid a warning when empty lines are found at the bvery end of a file.
                if (!is_array($arr_row))
                    // This will only happen if we are at the end of a file.
                    return false;
            }
        }
        // Remove leading and trailing spaces \s and \t
        if ($this->mTrimFields)
            array_walk($arr_row, array($this, 'ArrayTrim'));
        // Remove escape character if it is not empty and different from the quote character
        // otherwise fgetcsv removes it automatically and we don't have to worry about it.
        if (   $this->mFldEscape !== ''
            && $this->mFldEscape !== $this->mFldQuote)
            array_walk($arr_row, array($this, 'ArrayRemoveEscape'));
        return $arr_row;
    }

    /**
     * Writes the array to the CSV file
     *
     * @param String $filename
     * @param Array $arrCsv
     *
     * @access public
     * @return array of fields
     * @static
     */
    function write($filename, $arrCsv)
    {
        if (empty($filename) || !is_array($arrCsv)) return false;

        $quotes = $this->mFldQuote;
        $quotesDouble = "$quotes$quotes";
        $separator = $this->mFldSeparator;
        //$escape = $this->mFldEscape;

        $fh = @fopen($filename, 'w');
        if (!$fh) trigger_error('Unable to open CSV file', E_USER_ERROR);
        foreach ($arrCsv as $arrLine) {
            $strLine = '';
            foreach ($arrLine as $value) {
                $flagQuote = false;
                if (preg_match('/'.$quotes.'/', $value)) {
                    $value = preg_replace('/'.$quotes.'/', $quotesDouble, $value);
                    $flagQuote = true;
                }
                if (preg_match('/'.$separator.'/', $value))
                    $flagQuote = true;
                if ($flagQuote)
                    $value = $quotes.$value.$quotes;
                $strLine .=
                    ($strLine === '' ? '' : $separator).
                    $value;
            }
            fwrite($fh, $strLine."\n");
        }
        fclose($fh);
        return true;
    }


    /**
      * csv::Csv2Array will return the whole csv file as 2D array
      * @access public
      */
    function Csv2Array()
    {
        $arr_csv = array();
        $arr_row = $this->NextLine();
        while ($arr_row) {
            $arr_csv[] = $arr_row;
            $arr_row = $this->NextLine();
        }
        return $arr_csv;
    }


    /**
      * Strip leading and trailing whitespace from the elements of an array
      *
      * Called by array_walk().
      * Spaces and tabs are removed (\s and \t).
      *
      * @param Address $item
      * @access private
      */
    function ArrayTrim(&$item)
    {
        $item = trim($item, " \t");
    }


    /**
      * Escape the quote character
      *
      * Called by array_walk()
      *
      * @param Address $item
      * @access private
      */
    function ArrayRemoveEscape(&$item)
    {
        $item = str_replace($this->mFldEscape.$this->mFldQuote, $this->mFldQuote, $item);
    }


    /**
      * csv::RowCount return the current row count
      * @access public
      * @return int
      */
    function RowCount()
    {
        return $this->mRowCount;
    }


    /**
      * csv::RowCount return the current skipped row count
      * @access public
      * @return int
      */
    function SkippedRowCount()
    {
        return $this->mSkippedRowCount;
    }


    /**
      * csv::SkipEmptyRows, sets whether empty rows should be skipped or not
      * @access public
      * @param bool $bool
      * @return void
      */
    function SkipEmptyRows($bool=true)
    {
        $this->mSkipEmptyRows = $bool;
    }


    /**
      * csv::TrimFields, sets whether fields should have their \s and \t removed.
      * @access public
      * @param bool $bool
      * @return void
      */
    function TrimFields($bool=true)
    {
        $this->mTrimFields = $bool;
    }

}
