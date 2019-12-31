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
* Import Export Class
*
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_importexport
 */

/**
 * Import Export Class
* Parent class for the import and export classes
*
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_importexport
*/
class ImportExport
{
	/**
	 * Type of the data which shall be exported or imported
	 */
	var $type = null;

	/**
	 * Variable with the data class
	 */
	var $dataClass = null;

	/**
	 * Variable with the types
	 */
	var $types = array();

	/**
	 * PHP 5 constructor
	 *
	 */
	function __construct()
	{
		$this->initTypes();

	}

	/**
	 * PHP 4 constructor
	 *
	 * Runs __construct if PHP 4 is used
	 */
	function ImportExport()
	{
		$this->__construct();
	}

	/**
	 * Initialises the typesy
	 *
	 */
	function initTypes()
	{

		$this->types['csv'] = array(
			"name" => "CSV (Comma Separated Values)"
		);

		/*$this->types['xls'] = array(
			"name" => "Excel"
		);*/
	}

	/**
	 * Passes the options to the data lib
	 *
	 * @param array $options Array with all options which shall be passed
	 * @access public
	 */
	function setOptions($options)
	{
		$this->dataClass->setOptions($options);
	}

	/**
	 * Sets a new type
	 *
	 * @param unknown_type $type
	 * @access public
	 */
	function setType($type)
	{
		$this->type = $type;

		switch($this->type) {
			case "csv":
				$this->dataClass = new CsvLib();
		}
	}

	/**
	 * Returns select entries for the type selection list
	 *
	 * @param unknown_type $standard
	 * @return string List with all option entries
	 */
	function getTypeSelectList($standard=null)
	{
		$retval = "";

		foreach ($this->types as $key => $value) {
			$selected = ($key == $standard) ? "selected=\"selected\"" : "";
			$retval .= "<option value=\"".$key."\" $selected>".$value['name']."</option>";
		}

		return $retval;
	}
}
