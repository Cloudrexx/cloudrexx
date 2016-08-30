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
 * Calendar 
 * 
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
namespace Cx\Modules\Calendar\Controller;

/**
 * Calendar Class CalendarForm
 * 
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Cloudrexx <info@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class CalendarForm extends CalendarLibrary
{
    /**
     * Form id
     *
     * @var integer
     */
    public $id;    
    
    /**
     * Title
     *
     * @var string
     */
    public $title;            
    
    /**
     * Status
     *
     * @var boolean
     */
    public $status;
    
    /**
     * Sort order
     *
     * @var integer
     */
    public $sort;
    
    /**
     * Input fields
     *
     * @var array
     */
    public $inputfields = array();
    
    /**
     * Form constructor
     * 
     * Loads the form attributes by the given id
     * 
     * @param integer $id form id
     */
    function __construct($id=null){
        if($id != null) {
            self::get($id);
        }
    }
    
    /**
     * Loads the form attributes
     *      
     * @param integer $formId Form id
     */
    function get($formId) {
        global $objDatabase, $_LANGID;  
        
        $this->getFrontendLanguages();
        
        $this->id = intval($formId);
        
        $query = "SELECT id,title,status,`order`
                    FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                   WHERE id = '".intval($formId)."'
                   LIMIT 1";
        $objResult = $objDatabase->Execute($query);     
        if ($objResult !== false) {        
            $this->id = intval($formId);
            $this->title = $objResult->fields['title'];                        
            $this->status = intval($objResult->fields['status']);                         
            $this->sort = intval($objResult->fields['order']);
            
            $queryInputfield = "SELECT field.`id` AS `id`,
                             field.`type` AS `type`,
                             field.`required` AS `required`,
                             field.`order` AS `order`,
                             field.`affiliation` AS `affiliation`,
                             (
                                SELECT `fieldName`.`name`
                                FROM `".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form_field_name` AS `fieldName`
                                WHERE `fieldName`.`field_id` = `field`.`id` AND `fieldName`.`form_id` = `field`.`form`
                                ORDER BY CASE `fieldName`.`lang_id`
                                            WHEN '$_LANGID' THEN 1
                                            ELSE 2
                                            END
                                LIMIT 1
                             ) AS `name`,
                             (
                                SELECT `fieldDefault`.`default`
                                FROM `".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form_field_name` AS `fieldDefault`
                                WHERE `fieldDefault`.`field_id` = `field`.`id` AND `fieldDefault`.`form_id` = `field`.`form`
                                ORDER BY CASE `fieldDefault`.`lang_id`
                                            WHEN '$_LANGID' THEN 1
                                            ELSE 2
                                            END
                                LIMIT 1
                             ) AS `default`
                        FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form_field AS field
                       WHERE field.`form` = '".intval($this->id)."'
                    ORDER BY field.`order`";

            $objResultInputfield = $objDatabase->Execute($queryInputfield);
            
            if ($objResultInputfield !== false) {
                while (!$objResultInputfield->EOF) {
                    $arrFieldNames = array();
                    $arrFieldDefaults = array();
                    
                    $this->inputfields[intval($objResultInputfield->fields['id'])]['id'] = intval($objResultInputfield->fields['id']);
                    $this->inputfields[intval($objResultInputfield->fields['id'])]['type'] = htmlentities($objResultInputfield->fields['type'], ENT_QUOTES, CONTREXX_CHARSET);
                    $this->inputfields[intval($objResultInputfield->fields['id'])]['required'] = intval($objResultInputfield->fields['required']);
                    $this->inputfields[intval($objResultInputfield->fields['id'])]['order'] = intval($objResultInputfield->fields['order']);     
                    $this->inputfields[intval($objResultInputfield->fields['id'])]['affiliation'] = htmlentities($objResultInputfield->fields['affiliation'], ENT_QUOTES, CONTREXX_CHARSET);       
                    
                    //$arrFieldNames[0] = htmlentities($objResultInputfield->fields['name'], ENT_QUOTES, CONTREXX_CHARSET);
                    $arrFieldNames[0] = $objResultInputfield->fields['name'];
                    //$arrFieldDefaults[0] = htmlentities($objResultInputfield->fields['default'], ENT_QUOTES, CONTREXX_CHARSET);
                    $arrFieldDefaults[0] = $objResultInputfield->fields['default'];
                    
                    foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                        $queryName = "SELECT name.`name` AS `name`,
                                         name.`default` AS `default`
                                    FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form_field_name AS name
                                   WHERE (name.`field_id` = '".intval($objResultInputfield->fields['id'])."' AND name.`lang_id` = '".intval($arrLang['id'])."')
                                   LIMIT 1";
                        
                        $objResultName = $objDatabase->Execute($queryName);
                        
                        //$arrFieldNames[intval($arrLang['id'])] = !empty($objResultName->fields['name']) ? htmlentities($objResultName->fields['name'], ENT_QUOTES, CONTREXX_CHARSET) : $arrFieldNames[0];
                        $arrFieldNames[intval($arrLang['id'])] = !empty($objResultName->fields['name']) ? $objResultName->fields['name'] : $arrFieldNames[0];
                        //$arrFieldDefaults[intval($arrLang['id'])] = !empty($objResultName->fields['default']) ? htmlentities($objResultName->fields['default'], ENT_QUOTES, CONTREXX_CHARSET) : $arrFieldDefaults[0];
                        $arrFieldDefaults[intval($arrLang['id'])] = !empty($objResultName->fields['default']) ? $objResultName->fields['default'] : $arrFieldDefaults[0];
                    }
                    
                    $this->inputfields[intval($objResultInputfield->fields['id'])]['name'] = $arrFieldNames;
                    $this->inputfields[intval($objResultInputfield->fields['id'])]['default_value'] = $arrFieldDefaults;
                    
                    
                    $objResultInputfield->MoveNext();
                }
            }
        }
    }
    
    /**
     * Copy the form and returns the new or copied form id
     *      
     * @return integer new form id
     */
    function copy() { 
        global $objDatabase, $_LANGID;
                                       
        $queryOldForm = "SELECT id,title,status,`order`
                           FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                          WHERE id = '".intval($this->id)."'
                          LIMIT 1";
                   
        $objResultOldForm = $objDatabase->Execute($queryOldForm);
        
        if ($objResultOldForm !== false) {
            $queryNewForm = "INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                                  (`status`,`order`,`title`)  
                           VALUES ('0',
                                   '99',
                                   '".$objResultOldForm->fields['title']."')";
            
            $objResultNewForm = $objDatabase->Execute($queryNewForm);
            
            if($objResultNewForm === false) {
                return false;
            }  else {
                $newFormId = intval($objDatabase->Insert_ID());
                
                $queryOldFields = "SELECT id,type,required,`order`,`affiliation`   
                                     FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form_field
                                    WHERE form = '".intval($this->id)."'";
                                    
                $objResultOldFields = $objDatabase->Execute($queryOldFields); 
                                        
                if ($objResultOldFields !== false) {
                    while (!$objResultOldFields->EOF) {
                        $queryNewField = "INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form_field
                                                      (`form`,`type`,`required`,`order`,`affiliation` )  
                                               VALUES ('".$newFormId."',
                                                       '".$objResultOldFields->fields['type']."',
                                                       '".$objResultOldFields->fields['required']."',
                                                       '".$objResultOldFields->fields['order']."',
                                                       '".$objResultOldFields->fields['affiliation']."')";
            
                        $objResultNewField = $objDatabase->Execute($queryNewField);  
                        $newFieldId = intval($objDatabase->Insert_ID());  
                        
                        $queryOldNames =  "SELECT `lang_id`,`name`,`default`   
                                             FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form_field_name
                                            WHERE field_id = '".intval($objResultOldFields->fields['id'])."' AND form_id = '".intval($this->id)."'";           
                                            
                        $objResultOldNames = $objDatabase->Execute($queryOldNames); 
                        
                        if ($objResultOldNames !== false) {
                            while (!$objResultOldNames->EOF) {
                                $queryNewName = "INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form_field_name  
                                                              (`field_id`,`form_id`,`lang_id`,`name`,`default` )  
                                                       VALUES ('".$newFieldId."',
                                                               '".$newFormId."',
                                                               '".$objResultOldNames->fields['lang_id']."',
                                                               '".$objResultOldNames->fields['name']."',
                                                               '".$objResultOldNames->fields['default']."')";        
                    
                                $objResultNewName = $objDatabase->Execute($queryNewName);  
                                
                                $objResultOldNames->MoveNext(); 
                            }
                        }
                                  
                        $objResultOldFields->MoveNext();
                    }
                }
            }
        }                         
            
        return $newFormId;
    }
    
    /**
     * Save the form data's into database
     *      
     * @param array $data posted data from the user
     * 
     * @return boolean true on success false otherwise
     */
    function save($data) {
        global $objDatabase, $_LANGID; 
        
        if(empty($data['inputfield']) || empty($data['formTitle'])) {
            return false;
        }                        
        
        if(intval($this->id) == 0) {  
            $query = "INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                                  (`status`,`order`,`title`)  
                           VALUES ('0',
                                   '99',
                                   '".contrexx_addslashes($data['formTitle'])."')";
            
            $objResult = $objDatabase->Execute($query);
            
            if($objResult === false) {
                return false;
            }
            
            $this->id = intval($objDatabase->Insert_ID());   
        } else {
            $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form   
                         SET `title` =  '".contrexx_addslashes($data['formTitle'])."'        
                       WHERE id = '".intval($this->id)."'";
                        
            $objResult = $objDatabase->Execute($query) ;   
            
            if($objResult === false) {
                return false;
            }
        }
                                 
        if(intval($this->id) != 0) {  
            if(!self::saveInputfields($data)) {
                return false;   
            }
        } else {
            return false; 
        }      
                      
        return true;
    }
    
    /**
     * save the form input fields
     *      
     * @param array $data
     * 
     * @return boolean true on success false otherwise
     */
    function saveInputfields($data) {
        global $objDatabase, $_LANGID;    
                
        $this->getFrontendLanguages();
        
        $query = '
            DELETE
                fn.*, ff.*
            FROM
                `'. DBPREFIX .'module_'. $this->moduleTablePrefix .'_registration_form_field_name` AS fn,
                `'. DBPREFIX .'module_'. $this->moduleTablePrefix .'_registration_form_field` AS ff
            WHERE
                fn.`form_id` = '. contrexx_input2int($this->id) .'
            AND
                ff.`form` ='. contrexx_input2int($this->id) .'
        ';
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return false;
        }

        foreach ($data['inputfield'] as $intFieldId => $arrField) {
            $query = '
                INSERT INTO
                    `'. DBPREFIX .'module_'. $this->moduleTablePrefix .'_registration_form_field`
                SET
                    `id`          =  '. contrexx_input2int($intFieldId) .',
                    `form`        =  '. contrexx_input2int($this->id) .',
                    `type`        = "'. contrexx_input2db($arrField['type']) .'",
                    `required`    =  '. (isset($arrField['required']) ? 1 : 0) .',
                    `order`       =  '. contrexx_input2int($arrField['order']) .',
                    `affiliation` = "'. (isset($arrField['affiliation']) ? contrexx_input2db($arrField['affiliation']) : '') .'"
            ';
            $objResult = $objDatabase->Execute($query);

            if ($objResult === false) {
                continue;
            }

            foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                if (empty($arrField['name'][0])) {
                    $arrField['name'][0] = '';
                }
                $strFieldName         = $arrField['name'][$arrLang['id']];
                $strFieldDefaultValue = $arrField['default_value'][$arrLang['id']];

                if ($arrLang['id'] == $_LANGID) {
                    if (   $this->inputfields[$intFieldId]['name'][0] == $strFieldName
                        && $this->inputfields[$intFieldId]['name'][$arrLang['id']] != $strFieldName
                    ) {
                        $strFieldName = $arrField['name'][$_LANGID];
                    }
                    if (   $this->inputfields[$intFieldId]['default_value'][0] == $strFieldDefaultValue
                        && $this->inputfields[$intFieldId]['default_value'][$arrLang['id']] != $strFieldDefaultValue
                    ) {
                        $strFieldDefaultValue = $arrField['default_value'][$_LANGID];
                    }
                    if (   (   $this->inputfields[$intFieldId]['name'][0] != $arrField['name'][0]
                            && $this->inputfields[$intFieldId]['name'][$arrLang['id']] == $strFieldName
                           )
                        || (   $this->inputfields[$intFieldId]['name'][0] != $arrField['name'][0]
                            && $this->inputfields[$intFieldId]['name'][$arrLang['id']] != $strFieldName
                           )
                        || (   $this->inputfields[$intFieldId]['name'][0] == $arrField['name'][0]
                            && $this->inputfields[$intFieldId]['name'][$arrLang['id']] == $strFieldName
                           )
                    ) {
                        $strFieldName = $arrField['name'][0];
                    }

                    if (   (   $this->inputfields[$intFieldId]['default_value'][0] != $arrField['default_value'][0]
                            && $this->inputfields[$intFieldId]['default_value'][$arrLang['id']] == $strFieldDefaultValue
                           )
                        || (   $this->inputfields[$intFieldId]['default_value'][0] != $arrField['default_value'][0]
                            && $this->inputfields[$intFieldId]['default_value'][$arrLang['id']] != $strFieldDefaultValue
                           )
                        || (    $this->inputfields[$intFieldId]['default_value'][0] == $arrField['default_value'][0]
                            && $this->inputfields[$intFieldId]['default_value'][$arrLang['id']] == $strFieldDefaultValue
                           )
                    ) {
                        $strFieldDefaultValue = $arrField['default_value'][0];
                    }
                }
                if (empty($strFieldName)) {
                    $strFieldName = $arrField['name'][0];
                }
                if (empty($strFieldDefaultValue)) {
                    $strFieldDefaultValue = $arrField['default_value'][0];
                }
                $query = '
                    INSERT INTO
                        `' . DBPREFIX . 'module_' . $this->moduleTablePrefix . '_registration_form_field_name`
                    SET
                        `field_id` =  '. contrexx_input2int($intFieldId) . ',
                        `form_id`  =  '. contrexx_input2int($this->id) .',
                        `lang_id`  =  '. contrexx_input2int($arrLang['id']) .',
                        `name`     = "'. contrexx_input2db($strFieldName) .'",
                        `default`  = "'. contrexx_input2db($strFieldDefaultValue) .'"';

                $objResult = $objDatabase->Execute($query);
            }
        }

        return true;
    }        
    
    /**
     * Delete the form
     *      
     * @return boolean true on success false otherwise
     */
    function delete(){
        global $objDatabase;
        
        $query = "DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                        WHERE id = '".intval($this->id)."'";
        
        $objResult = $objDatabase->Execute($query);
        
        if ($objResult !== false) {      
            return true;
        } else {
            return false;
        }
    }   
    
    /**
     * Switch status of the form     
     * 
     * @return boolean true on success false otherwise
     */
    function switchStatus(){
        global $objDatabase;
        
        if($this->status == 1) {
            $formStatus = 0;
        } else {
            $formStatus = 1;
        }
        
        
        $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form   
                     SET status = '".intval($formStatus)."'
                   WHERE id = '".intval($this->id)."'";
        
        $objResult = $objDatabase->Execute($query);
        
        if ($objResult !== false) {
            return true;
        } else {
            return false;
        }
    }
             
    /**
     * Save the form sort order
     *      
     * @param integer $order form sorting order
     * 
     * @return boolean true on success false otherwise
     */
    function saveOrder($order) {
        global $objDatabase, $_LANGID;    
                  
        $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                     SET `order` = '".intval($order)."'          
                   WHERE id = '".intval($this->id)."'";
                               
        $objResult = $objDatabase->Execute($query);   
        
        if ($objResult !== false) {
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * Return's the max input id     
     * 
     * @return integer last input field id, false on error state
     */
    function getLastInputfieldId(){
        global $objDatabase;
        
        $query = "SELECT id
                    FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form_field 
                ORDER BY id DESC
                   LIMIT 1";
        
        $objResult = $objDatabase->Execute($query);
        
        if($objResult !== false) {
            return intval($objResult->fields['id']);
        } else {
        	return false;
        }
    }
}
