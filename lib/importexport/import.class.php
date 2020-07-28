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
 * Import Class
 * Class which handles the main import operations
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_importexport
 */

/**
 * Import Class
 * Class which handles the main import operations
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_importexport
 */
class Import extends ImportExport
{
	var $fieldNames;
	var $importedData;
	var $pairs = array();

	/**
	 * getFinalData
	 *
	 * This function returns the associated fields and values.
	 * @param array $fields Name of the fields
	 */
	function getFinalData($fields)
	{
		$this->setType($_POST['importtype']);
		$this->setFieldPairs($_POST['pairs_left_keys'], $_POST['pairs_right_keys']);

        $uploaderId = isset($_POST['importUploaderId']) ? contrexx_input2raw($_POST['importUploaderId']) : '';
        $file       = $this->getUploadedFileFromUploader($uploaderId);
        if (!$file) {
            return;
        }

		$this->parseFile($file);

		$retval = array();

        // init base data-set
        $retStructure = array();
        foreach (array_keys($fields) as $fieldKey) {
            $retStructure[$fieldKey] = null;
        }
		foreach ($this->importedData as $datarow) {
            $retfields = $retStructure;
			foreach ($this->pairs as $key => $value) {
				$retfields[$key] = $datarow[$value];
				$retfields[$fields[$key]] = $datarow[$value];
			}

			$retval[] = $retfields;
		}

		return $retval;

	}

	/**
	 * Sets the field Pairs
	 *
	 * @param array $left_fields
	 * @param array $right_fields
	 */
	function setFieldPairs($left_fields, $right_fields)
	{
		$lFields = explode(";", $left_fields);
		$rFields = explode(";", $right_fields);

		foreach ($rFields as $key => $rField) {
            // skip empty field associations
            if ($key === '' || $rField === '') {
                continue;
            }
			$this->pairs[$rField] = $lFields[$key];
		}
	}

	/**
	 * Gets the fieldnames of the importing file
	 *
	 * @return array $fields
	 */
	function getDataFields()
	{
		return $this->fieldNames;
	}

	/**
	 * Parses the file
	 *
	 * @param string    $file       Path to the csv file
	 * @param boolean   $onlyHeader Parse only the header when true
	 */
	function parseFile($file, $onlyHeader = false) {
            if ($onlyHeader) {
                $data = $this->dataClass->parse($file, true, true, 1);
            } else {
                $data = $this->dataClass->parse($file, true, true);
            }
            if (!empty($data)) {
                if (isset($data['fieldnames'])) {
                    // Set the fieldnames
                    $this->fieldNames = $data['fieldnames'];
                } else {
                    // Take the first data line as fieldnames
                    foreach ($data['data'][0] as $value) {
                            $this->fieldNames[] = $value;
                    }
                }

                if (isset($data['data'])) {
                    $this->importedData = $data['data'];
                }
            }
	}

	/**
	 * Sets the template for the file selection
	 *
	 * Sets the template and all neede variables
	 * for the file selection.
	 * @param object $tpl The template object (by reference)
	 */
	function initFileSelectTemplate(&$tpl)
	{
		global $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
		$template = file_get_contents($cx->getCodeBaseLibraryPath() . '/importexport/template/import.fileselect.html');
		$tpl->setTemplate($template,true,true);

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        // init uploader to upload csv
        $uploader = new \Cx\Core_Modules\Uploader\Model\Entity\Uploader();
        $uploader->setCallback('importUploaderCallback');
        $uploader->setOptions(array(
            'id'                 => 'importCsvUploader',
            'allowed-extensions' => array('csv'),
            'data-upload-limit'  => 1,
        ));
        $uploader->setFinishedCallback(array(
            $cx->getCodeBaseLibraryPath().'/importexport/import.class.php',
            '\Import',
            'uploadFinished'
        ));

		$tpl->setVariable(array(
			"TXT_IMPORT"		=> $_ARRAYLANG['TXT_IMPORT'],
			"IMPORT_TYPELIST"	=> $this->getTypeSelectList(),
			"TXT_FILETYPE"		=> $_ARRAYLANG['TXT_FILETYPE'],
			"TXT_CHOOSE_FILE"	=> $_ARRAYLANG['TXT_CHOOSE_FILE'],
			"TXT_SEPARATOR"		=> $_ARRAYLANG['TXT_SEPARATOR'],
			"TXT_ENCLOSURE"		=> $_ARRAYLANG['TXT_ENCLOSURE'],
			"TXT_DESC_DELIMITER"	=> $_ARRAYLANG['TXT_DESC_DELIMITER'],
			"TXT_DESC_ENCLOSURE"	=> $_ARRAYLANG['TXT_DESC_ENCLOSURE'],
			"TXT_HELP"           => $_ARRAYLANG['TXT_HELP'],
            'IMPORT_UPLOADER_BUTTON' => $uploader->getXHtml($_ARRAYLANG['TXT_BROWSE']),
            'IMPORT_UPLOADER_ID'     => $uploader->getId(),
		));
	}

	/**
	 * Sets the template for the field selection
	 *
	 * Parses the given file and sets the template and values
	 * for the field selection.
	 * @param object $tpl The template object (by reference)
	 */
	function initFieldSelectTemplate(&$tpl, $given_fields)
	{
		global $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
		$template = file_get_contents($cx->getCodeBaseLibraryPath() . '/importexport/template/import.fieldselect.html');
		$tpl->setTemplate($template, true, true);

		// Pass the options
		foreach ($_POST as $postkey => $postvar) {
			if (preg_match("%^import\_options\_%", $postkey)) {
				$optionvars[strtoupper($postkey)] = htmlentities(contrexx_stripslashes($postvar), ENT_QUOTES, CONTREXX_CHARSET);
			}
		}
		$tpl->setVariable($optionvars);

		$this->setType($_POST['importtype']);
                $uploaderId = isset($_POST['importUploaderId']) ? contrexx_input2raw($_POST['importUploaderId']) : '';
                $file       = $this->getUploadedFileFromUploader($uploaderId);
                if (!$file) {
                    return;
                }
		$this->parseFile($file, true);

		$tpl->setVariable(array(
			"TXT_REMOVE_PAIR"	=> $_ARRAYLANG['TXT_REMOVE_PAIR'],
			"TXT_ADD_PAIR"		=> $_ARRAYLANG['TXT_ADD_PAIR'],
			"TXT_IMPORT"		=> $_ARRAYLANG['TXT_IMPORT'],
			"TXT_FIELDSELECT_SELECT_DESC"	=> $_ARRAYLANG['TXT_FIELDSELECT_SELECT_DESC'],
			"TXT_FIELDSELECT_SHOW_DESC"		=> $_ARRAYLANG['TXT_FIELDSELECT_SHOW_DESC'],			
			'IMPORT_UPLOADER_ID'     => contrexx_raw2xhtml($uploaderId),
			"IMPORT_TYPE"	         => $_POST['importtype'],
			"TXT_CANCEL"             => $_ARRAYLANG['TXT_CANCEL']
		));

		/*
		 * Set the given fields
		 */
		foreach ($given_fields as $key => $field) {
            $tpl->setVariable(array(
                "IMPORT_FIELD_VALUE" => $key,
                "IMPORT_FIELD_NAME"	=> $field
            ));

            $tpl->parse("given_field_row");
		}

		// Set the file fields
		$fieldnames = $this->getDataFields();
		foreach ($fieldnames as $key => $field) {
			$tpl->setVariable(array(
				"IMPORT_FIELD_VALUE" => $key,
				"IMPORT_FIELD_NAME"	=> $field,
			));

			$tpl->parse("file_field_row");
		}
	}

	/**
	 * Cancels the import operation
	 *
	 */
	function cancel()
	{
            if (!isset($_POST['importfile'])) {
                return false;
            }
        $file = $_POST['importfile'];

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $path = $cx->getWebsiteTempPath() . '/';
        if (file_exists($path . $file)) {
            unlink($path);
        }
	}

        /**
         * Uploader callback function
         *
         * @param string $tempPath    Temp path 
         * @param string $tempWebPath Temp web path
         * @param array  $data        Uploader data
         * @param string $uploaderId  Uploader id
         * @param array  $fileInfos   Info about the file
         * @param object $response    \Cx\Core_Modules\Uploader\Controller\UploadResponse
         *
         * @return array temp path and temp web path
         */
        public static function uploadFinished(
            $tempPath,
            $tempWebPath,
            $data,
            $uploaderId,
            $fileInfos,
            \Cx\Core_Modules\Uploader\Controller\UploadResponse $response
        )
        {
            // in case uploader has been restricted to only allow one single file to be
            // uploaded, we'll have to clean up any previously uploaded files
            if (count($fileInfos['name'])) {
                // new files have been uploaded -> remove existing files
                if (\Cx\Lib\FileSystem\FileSystem::exists($tempPath)) {
                    foreach (glob($tempPath.'/*') as $file) {
                        if (basename($file) == $fileInfos['name']) {
                            continue;
                        }
                        \Cx\Lib\FileSystem\FileSystem::delete_file($file);
                    }
                }
            }

            return array($tempPath, $tempWebPath);
        }
        
        /**
         * Get uploaded csv file by using uploader id
         * 
         * @param string $uploaderId Uploader id
         * 
         * @return boolean|string File path when file exists, false otherwise
         */
        public function getUploadedFileFromUploader($uploaderId)
        {
            if (empty($uploaderId)) {
                return false;
            }
            
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $objSession = $cx->getComponent('Session')->getSession();
            $uploaderFolder = $objSession->getTempPath() . '/' . $uploaderId;
            if (!\Cx\Lib\FileSystem\FileSystem::exists($uploaderFolder)) {
                return false;
            }
            foreach (glob($uploaderFolder.'/*.csv') as $file) {
                return $file;                
            }
            return false;
        }
}
