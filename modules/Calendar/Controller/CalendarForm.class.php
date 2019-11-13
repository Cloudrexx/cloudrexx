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
 * Exception class for if a transaction needs to be cancelled
 * 
 * @package    cloudrexx
 * @subpackage module_calendar
 * @author     Michael Ritter <michael.ritter@cloudrexx.com>
 * @copyright  CLOUDREXX CMS - CLOUDREXX AG
 * @version    1.00
 */
class TransactionFailedException extends \Exception {}

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
    function __construct($id=null) {
        if($id != null) {
            self::get($id);
        }
        $this->init();
    }
    
    /**
     * Loads the form attributes
     *      
     * @param integer $formId Form id
     */
    function get($formId) {
        global $objDatabase;  
        
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
                                            WHEN '" . FRONTEND_LANG_ID . "' THEN 1
                                            ELSE 2
                                            END
                                LIMIT 1
                             ) AS `name`,
                             (
                                SELECT `fieldDefault`.`default`
                                FROM `".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form_field_name` AS `fieldDefault`
                                WHERE `fieldDefault`.`field_id` = `field`.`id` AND `fieldDefault`.`form_id` = `field`.`form`
                                ORDER BY CASE `fieldDefault`.`lang_id`
                                            WHEN '" . FRONTEND_LANG_ID . "' THEN 1
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
        global $objDatabase;
                                       
        $queryOldForm = "SELECT id,title,status,`order`
                           FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                          WHERE id = '".intval($this->id)."'
                          LIMIT 1";
                   
        $objResultOldForm = $objDatabase->Execute($queryOldForm);
        
        $classMetaDataForForm = $this
            ->em
            ->getClassMetadata('Cx\Modules\Calendar\Model\Entity\RegistrationForm');
        $oldForm = $this->getFormEntity($this->id);
        $form    = clone $oldForm;
        $classMetaDataForForm->setFieldValue($form, 'id', 0);
        if ($objResultOldForm !== false) {
           //Trigger prePersist event for Form Entity
           $this->triggerEvent(
                'model/prePersist', $form,
                array(
                    'relations' => array(
                        'oneToMany' => array(
                            'getEvents', 'getRegistrationFormFields'
                        )
                    ),
                    'joinEntityRelations' => array(
                        'getRegistrationFormFields' => array(
                            'oneToMany' => array(
                                'getRegistrationFormFieldNames',
                                'getRegistrationFormFieldValues'
                            ),
                            'manyToOne' => 'getRegistrationForm'
                        ),
                        'getRegistrationFormFieldNames' => array(
                            'manyToOne' => 'getRegistrationFormField'
                        )
                    )
                ), true
            );
            $queryNewForm = "INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                                  (`status`,`order`,`title`)  
                           VALUES ('0',
                                   '99',
                                   '".$objResultOldForm->fields['title']."')";

            $objResultNewForm = $objDatabase->Execute($queryNewForm);

            if ($objResultNewForm === false) {
                return false;
            }  else {
                $newFormId = intval($objDatabase->Insert_ID());

                $queryOldFields = "SELECT id,type,required,`order`,`affiliation`   
                                     FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form_field
                                    WHERE form = '".intval($this->id)."'";

                $objResultOldFields = $objDatabase->Execute($queryOldFields);

                if ($objResultOldFields !== false) {
                    while (!$objResultOldFields->EOF) {
                        $newFormField = $form->getRegistrationFormFieldById(
                            $objResultOldFields->fields['id']
                        );
                        //Trigger prePersist event for FormField Entity
                        $this->triggerEvent(
                            'model/prePersist', $newFormField,
                            array(
                                'relations' => array(
                                    'oneToMany' => array(
                                        'getRegistrationFormFieldNames',
                                        'getRegistrationFormFieldValues'
                                    ),
                                    'manyToOne' => 'getRegistrationForm'
                                ),
                                'joinEntityRelations' => array(
                                    'getRegistrationFormFieldNames' => array(
                                        'manyToOne' => 'getRegistrationFormField'
                                    )
                                )
                            ), true);
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
                                $newFormFieldName = $newFormField->getRegistrationFormFieldNamesByLangId(
                                    $objResultOldNames->fields['lang_id']
                                );
                                //Trigger prePersist event for FormFieldName Entity
                                $this->triggerEvent(
                                    'model/prePersist', $newFormFieldName,
                                    array(
                                        'relations' => array(
                                            'manyToOne' => 'getRegistrationFormField'
                                        )
                                    ), true
                                );
                                $queryNewName = "INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form_field_name
                                                              (`field_id`,`form_id`,`lang_id`,`name`,`default` )
                                                       VALUES ('".$newFieldId."',
                                                               '".$newFormId."',
                                                               '".$objResultOldNames->fields['lang_id']."',
                                                               '".$objResultOldNames->fields['name']."',
                                                               '".$objResultOldNames->fields['default']."')";

                                $objResultNewName = $objDatabase->Execute($queryNewName);
                                //Trigger postPersist event for FormFieldName Entity
                                $this->triggerEvent('model/postPersist', $newFormFieldName);
                                $objResultOldNames->MoveNext(); 
                            }
                        }
                        //Trigger postPersist event for FormField Entity
                        $this->triggerEvent('model/postPersist', $newFormField);
                        $objResultOldFields->MoveNext();
                    }
                }
                $form = $this->getFormEntity($newFormId);
                //Trigger postPersist event for Form Entity
                $this->triggerEvent('model/postPersist', $form);
                $this->triggerEvent('model/postFlush');
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
    function save($data)
    {
        global $objDatabase;
        
        if (empty($data['inputfield']) || empty($data['formTitle'])) {
            return false;
        }

        $formTitle   = contrexx_addslashes($data['formTitle']);
        $inputFields = $this->getInputFieldsAsArray($data);
        $formData    = array(
            'fields'    => array('title' => $formTitle),
            'relation'  => array('inputFields' => $inputFields)
        );
        $id   = $this->id;
        $form = $this->getFormEntity($this->id, $formData);
        if (intval($this->id) == 0) {
            //Trigger prePersist event for Form Entity
            $this->triggerEvent(
                'model/prePersist', $form,
                array(
                    'relations' => array(
                        'oneToMany' => array(
                            'getEvents', 'getRegistrationFormFields'
                        )
                    ),
                    'joinEntityRelations' => array(
                        'getRegistrationFormFields' => array(
                            'oneToMany' => array(
                                'getRegistrationFormFieldNames',
                                'getRegistrationFormFieldValues'
                            ),
                            'manyToOne' => 'getRegistrationForm'
                        ),
                        'getRegistrationFormFieldNames' => array(
                            'manyToOne' => 'getRegistrationFormField'
                        )
                    )
                ), true
            );
            $query = "INSERT INTO ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                                  (`status`,`order`,`title`)
                           VALUES ('0',
                                   '99',
                                   '".$formTitle."')";

            $objResult = $objDatabase->Execute($query);

            if ($objResult === false) {
                return false;
            }
            
            $this->id = intval($objDatabase->Insert_ID());
            $form = $this->getFormEntity($this->id);
        } else {
            //Trigger preUpdate event for Form Entity
            $this->triggerEvent(
                'model/preUpdate', $form,
                array(
                    'relations' => array(
                        'oneToMany' => array(
                            'getEvents', 'getRegistrationFormFields'
                        )
                    ),
                    'joinEntityRelations' => array(
                        'getRegistrationFormFields' => array(
                            'oneToMany' => array(
                                'getRegistrationFormFieldNames',
                                'getRegistrationFormFieldValues'
                            ),
                            'manyToOne' => 'getRegistrationForm'
                        ),
                        'getRegistrationFormFieldNames' => array(
                            'manyToOne' => 'getRegistrationFormField'
                        )
                    )
                ), true
            );
            $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                         SET `title` =  '".$formTitle."'
                       WHERE id = '".intval($this->id)."'";

            $objResult = $objDatabase->Execute($query);

            if ($objResult === false) {
                return false;
            }
        }

        if (intval($this->id) != 0) {
            if (!$this->saveInputfields($form, $inputFields)) {
                return false;
            }
            if ($id == 0) {
                //Trigger postPersist event for Form Entity
                $this->triggerEvent('model/postPersist', $form, null, true);
            } else {
                //Trigger postUpdate event for Form Entity
                $this->triggerEvent('model/postUpdate', $form);
            }
            $this->triggerEvent('model/postFlush');
        } else {
            return false;
        }

        return true;
    }

    /**
     * Save the form input fields
     *      
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationForm $form Current form
     * @param array $data Data to save
     * @param boolean $hasChange (optional, reference) This is set to true if there's a change to the database
     * @return boolean true on success false otherwise
     */
    protected function saveInputfields(
        \Cx\Modules\Calendar\Model\Entity\RegistrationForm $form,
        $data,
        &$hasChange = false
    ) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $objDatabase = $cx->getDb()->getAdoDb();

        $formFields = $form->getRegistrationFormFields();
        // STEP 1: Create diff between db and $data fields
        $changedFields = array();
        $deletedFields = array();
        $createdFields = array();

        $this->createRegistrationFormFieldsDiff(
            $form,
            $formFields,
            $data,
            $changedFields,
            $deletedFields,
            $createdFields
        );
        $hasChange = count($changedFields) || count($deletedFields) ||
            count($createdFields);
        if (!$hasChange) {
            return true;
        }

        // STEP 2: Apply diff to database
        $objDatabase->startTrans();
        try {
            $this->applyRegistrationFormFieldsDiffToDb(
                $form,
                $changedFields,
                $deletedFields,
                $createdFields
            );

            // commit transaction
            $objDatabase->completeTrans();
            //  trigger postFlush
            $this->triggerEvent('model/postFlush');
        } catch (TransactionFailedException $e) {
            $objDatabase->failTrans();
            return false;
        } catch (\Throwable $e) {
            $objDatabase->failTrans();
            throw $e;
        }
        return true;
    }

    /**
     * Applies a diff calculated with createRegistrationFormFieldsDiff()
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationForm $form Current form
     * @param array $changedFields FieldID=>Array of field data of fields with a change
     * @param array $deletedFields FieldID=>List of field entities to delete
     * @param array $createdFields FieldID=>Array of field data of new fields
     * @throws TransactionFailedException on SQL error
     */
    protected function applyRegistrationFormFieldsDiffToDb(
        $form,
        $changedFields,
        $deletedFields,
        $createdFields
    ) {
        $tablePrefix = DBPREFIX . 'module_' . $this->moduleTablePrefix .
            '_registration_form';
        // drop fields
        foreach ($deletedFields as $fieldId=>$field) {
            // delete data
            foreach (
                array(
                    'field_value' => array(
                        'conditionField' => 'field_id',
                        'entities' => $field->getRegistrationFormFieldValues(),
                    ),
                    'field_name' => array(
                        'conditionField' => 'field_id',
                        'entities' => $field->getRegistrationFormFieldNames(),
                    ),
                    'field' => array(
                        'conditionField' => 'id',
                        'entities' => array($field),
                    ),
                ) as $tableSuffix => $params
            ) {
                $this->executeLegacySqlWithDoctrineEvents(
                    $params['entities'],
                    'Remove',
                    'DELETE FROM
                        `' . $tablePrefix . '_' . $tableSuffix . '`
                    WHERE
                        `' . $params['conditionField'] . '` = ' . $fieldId
                );
            }
        }
        // update fields
        $this->executeSqlWithDoctrineEventsFormFormField(
            $form,
            $changedFields,
            'Update',
            function($fieldValue) use ($tablePrefix, $form) {
                return '
                    UPDATE
                        `'. $tablePrefix .'_field`
                    SET
                        `type` = "'. $fieldValue['type'] .'",
                        `required` = '. $fieldValue['required'] .',
                        `order` = '. $fieldValue['order'] .',
                        `affiliation` = "'. $fieldValue['affiliation'] .'"
                    WHERE
                        `id` = '. $fieldValue['id'] .' AND
                        `form` = '. $form->getId() .'
                ';
            },
            function($fieldNameData) use ($tablePrefix) {
                return '
                    UPDATE
                        `' . $tablePrefix . '_field_name`
                    SET
                        `name` = "'. $fieldNameData['name'] .'",
                        `default` = "'. $fieldNameData['default'] .'"
                    WHERE
                        `field_id` = '. $fieldNameData['fieldId'] . ' AND
                        `form_id` = '. $fieldNameData['formId'] .' AND
                        `lang_id` = '. $fieldNameData['langId'] .'
                ';
            }
        );
        // create fields
        $this->executeSqlWithDoctrineEventsFormFormField(
            $form,
            $createdFields,
            'Persist',
            function($fieldValue) use ($tablePrefix, $form) {
                return '
                    INSERT INTO
                        `'. $tablePrefix .'_field`
                    SET
                        `id` = '. $fieldValue['id'] .',
                        `form` = '. $form->getId() .',
                        `type` = "'. $fieldValue['type'] .'",
                        `required` = '. $fieldValue['required'] .',
                        `order` = '. $fieldValue['order'] .',
                        `affiliation` = "'. $fieldValue['affiliation'] .'"
                ';
            },
            function($fieldNameData) use ($tablePrefix) {
                return '
                    INSERT INTO
                        `' . $tablePrefix . '_field_name`
                    SET
                        `field_id` = '. $fieldNameData['fieldId'] . ',
                        `form_id` = '. $fieldNameData['formId'] .',
                        `lang_id` = '. $fieldNameData['langId'] .',
                        `name` = "'. $fieldNameData['name'] .'",
                        `default` = "'. $fieldNameData['default'] .'"
                ';
            }
        );
    }

    /**
     * Creates or updates a form field and its names
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationForm $form Current form
     * @param array $fieldData FieldID=>Array of field data of new fields or fields with a change
     * @param string Doctrine event suffix ("Update" or "Persist")
     * @param callable $fieldQueryCallback Callback to generate the field entity query
     * @param callable $fieldNameQueryCallback Callback to generate the field name entity query
     */
    protected function executeSqlWithDoctrineEventsFormFormField(
        $form,
        $fieldData,
        $eventSuffix,
        $fieldQueryCallback,
        $fieldNameQueryCallback
    ) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $objDatabase = $cx->getDb()->getAdoDb();

        // update fields
        foreach ($fieldData as $fieldId => $fieldValues) {
            // update field
            $formFieldEntity = $this->getFormFieldEntity($form, $fieldValues);
            // Trigger pre... event for FormField Entity
            $this->triggerEvent(
                'model/pre' . $eventSuffix, $formFieldEntity,
                array(
                    'relations' => array(
                        'oneToMany' => array(
                            'getRegistrationFormFieldNames',
                            'getRegistrationFormFieldValues'
                        ),
                        'manyToOne' => 'getRegistrationForm'
                    ),
                    'joinEntityRelations' => array(
                        'getRegistrationFormFieldNames' => array(
                            'manyToOne' => 'getRegistrationFormField'
                        )
                    )
                ), true
            );
            $fieldValue = $fieldValues['fields'];
            $result = $objDatabase->Execute($fieldQueryCallback($fieldValue));
            if (!$result) {
                throw new TransactionFailedException('');
            }

            // update field_names
            foreach ($fieldValues['formFieldNames'] as $index=>$fieldNameData) {
                $formFieldNameEntity = $this->getFormFieldNameEntity(
                    $formFieldEntity, $fieldNameData
                );
                //Trigger prePersist event for FormFieldName Entity
                $this->triggerEvent(
                    'model/pre' . $eventSuffix, $formFieldNameEntity,
                    array(
                        'relations' => array(
                            'manyToOne' => 'getRegistrationFormField'
                        )
                    ), true
                );

                $result = $objDatabase->Execute(
                    $fieldNameQueryCallback($fieldNameData)
                );
                if (!$result) {
                    throw new TransactionFailedException('');
                }
                //Trigger postPersist event for FormFieldName Entity
                $this->triggerEvent(
                    'model/post' . $eventSuffix,
                    $formFieldNameEntity
                );
            }
            //Trigger postPersist event for FormField Entity
            $this->triggerEvent('model/post' . $eventSuffix, $formFieldEntity);
        }
    }

    /**
     * Diffs existing form fields with supplied data
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationForm $form Current form
     * @param array $formFields List of RegistrationFormField entities that exist
     * @param array $data Data to create diff to
     * @param array $changedFields (reference) Changed fields will be in this array
     * @param array $deletedFields (reference) Deleted fields will be in this array
     * @param array $createdFields (reference) New fields will be in this array
     */
    protected function createRegistrationFormFieldsDiff(
        $form,
        $formFields,
        $data,
        &$changedFields,
        &$deletedFields,
        &$createdFields
    ) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $formFieldRepo = $em->getRepository(
            'Cx\Modules\Calendar\Model\Entity\RegistrationFormField'
        );
        $fieldNameRepo = $em->getRepository(
            'Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldName'
        );
        foreach ($formFields as $formField) {
            if (!isset($data[$formField->getId()])) {
                if (empty($formField->getId())) {
                    continue;
                }
                // Case b) field exists in db but not in $data: schedule deletion
                $deletedFields[$formField->getId()] = $formField;
                unset($data[$formField->getId()]);
                continue;
            }
            // Case a) field exists in db and in $data
            $hasChange = false;
            $originalFormField = $formFieldRepo->find($formField->getId());
            $hasChange = $this->hasChangeInFields(
                $originalFormField,
                $formField,
                array('type', 'required', 'order', 'affiliation')
            ) || $this->hasChangeInEntityFields(
                $data[$formField->getId()]['formFieldNames'],
                function($index, $fieldData) use ($fieldNameRepo) {
                    return $fieldNameRepo->findOneBy(array(
                        'fieldId' => $fieldData['fieldId'],
                        'formId' => $fieldData['formId'],
                        'langId' => $fieldData['langId'],
                    ));
                },
                function($index, $fieldData) use ($form) {
                    return $form->getRegistrationFormFieldById(
                        $fieldData['fieldId']
                    )->getRegistrationFormFieldNamesByLangId(
                        $fieldData['langId']
                    );
                },
                array('name', 'default')
            );
            // Case a1) field has no change: ignore
            // Case a2) field has a change: schedule update
            if ($hasChange) {
                $changedFields[$formField->getId()] = $data[$formField->getId()];
            }
            unset($data[$formField->getId()]);
        }
        // Case c) field exists in $data but not in db: schedule insert
        $createdFields = $data;
    }

    /**
     * Determines if a list of data contains a change between old and new entity
     * 
     * The callbacks will receive $data's key and value as first and second
     * argument.
     * @param array $data Key=>value array of data
     * @param callable $originalEntityCallback Callback to get the original entity from an entry in $data
     * @param callable $newEntityCallback Callback to get the new entity from an entry in $data
     * @param array $fields List of fields to check
     * @return boolean True if there's at least one change, false otherwise
     */
    protected function hasChangeInEntityFields(
        $data,
        $originalEntityCallback,
        $newEntityCallback,
        $fields
    ) {
        foreach ($data as $index=>$fieldData) {
            if (
                $this->hasChangeInFields(
                    $originalEntityCallback($index, $fieldData),
                    $newEntityCallback($index, $fieldData),
                    $fields
                )
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Determines whether a set of fields has been changed
     * @param \Cx\Model\Entity\Base\EntityBase $originalEntity Original entity
     * @param \Cx\Model\Entity\Base\EntityBase $newEntity New entity
     * @param array $fields List of fields to check
     * @return boolean True if there's at least one change, false otherwise
     */
    protected function hasChangeInFields($originalEntity, $newEntity, $fields) {
        foreach ($fields as $property) {
            $methodBaseName = \Doctrine\Common\Inflector\Inflector::classify(
                $property
            );
            $getter = 'get' . $methodBaseName;
            if ($originalEntity->$getter() != $newEntity->$getter()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Executes $sql using the legacy database abstraction
     *
     * Triggers pre$eventName and post$eventName for all entities in $entities
     * before/after executing $sql. Throws a TransactionFailedException if
     * executing $sql was not successful.
     * @param array $entities List of Doctrine entities
     * @param string $eventName Doctrine event name without "pre" or "post prefix
     * @param string $sql SQL to execute
     * @throws TransactionFailedException If SQL generated an error
     */
    protected function executeLegacySqlWithDoctrineEvents($entities, $eventName, $sql) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $objDatabase = $cx->getDb()->getAdoDb();

        //  trigger pre... event
        foreach ($entities as $entity) {
            $this->triggerEvent('model/pre' . $eventName, $entity);
        }
        // execute SQL
        $result = $objDatabase->Execute($sql);
        if (!$result) {
            throw new TransactionFailedException($sql);
        }
        //  trigger post... event
        foreach ($entities as $entity) {
            $this->triggerEvent('model/post' . $eventName, $entity);
        }
    }

    /**
     * Get input fields as array
     *
     * @param array $data post data
     *
     * @return array the array of input fields
     */
    public function getInputFieldsAsArray($data)
    {
        if (empty($data)) {
            return null;
        }

        $this->getFrontendLanguages();

        $inputFields = array();
        foreach ($data['inputfield'] as $intFieldId => $arrField) {
            $inputFields[$intFieldId] = array(
                'fields'    => array(
                    'id'          => contrexx_input2int($intFieldId),
                    'type'        => contrexx_input2db($arrField['type']),
                    'required'    => isset($arrField['required']) ? 1 : 0,
                    'order'       => contrexx_input2int($arrField['order']),
                    'affiliation' => isset($arrField['affiliation'])
                    ? contrexx_input2db($arrField['affiliation']) : ''
                ),
                'formFieldNames'  => array()
            );

            $formFieldNames = array();
            foreach ($this->arrFrontendLanguages as $key => $arrLang) {
                if (empty($arrField['name'][0])) {
                    $arrField['name'][0] = '';
                }
                $strFieldName         = $arrField['name'][$arrLang['id']];
                $strFieldDefaultValue = $arrField['default_value'][$arrLang['id']];

                if ($arrLang['id'] == FRONTEND_LANG_ID) {
                    if (   $this->inputfields[$intFieldId]['name'][0] == $strFieldName
                        && $this->inputfields[$intFieldId]['name'][$arrLang['id']] != $strFieldName
                    ) {
                        $strFieldName = $arrField['name'][FRONTEND_LANG_ID];
                    }
                    if (   $this->inputfields[$intFieldId]['default_value'][0] == $strFieldDefaultValue
                        && $this->inputfields[$intFieldId]['default_value'][$arrLang['id']] != $strFieldDefaultValue
                    ) {
                        $strFieldDefaultValue = $arrField['default_value'][FRONTEND_LANG_ID];
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
                $formFieldNames[] = array(
                    'fieldId'   => $intFieldId,
                    'formId'    => contrexx_input2int($this->id),
                    'name'      => contrexx_input2db($strFieldName),
                    'langId'    => contrexx_input2int($arrLang['id']),
                    'default'   => contrexx_input2db($strFieldDefaultValue)
                );
            }
            $inputFields[$intFieldId]['formFieldNames'] = $formFieldNames;
        }

        return $inputFields;
    }

    /**
     * Delete the form
     *      
     * @return boolean true on success false otherwise
     */
    function delete()
    {
        global $objDatabase;

        $form = $this->getFormEntity($this->id);
        //Trigger preRemove event for Form Entity
        $this->triggerEvent(
            'model/preRemove', $form,
            array(
                'relations' => array(
                    'oneToMany' => array(
                        'getEvents', 'getRegistrationFormFields'
                    )
                ),
                'joinEntityRelations' => array(
                    'getRegistrationFormFields' => array(
                        'oneToMany' => array(
                            'getRegistrationFormFieldNames',
                            'getRegistrationFormFieldValues'
                        ),
                        'manyToOne' => 'getRegistrationForm'
                    ),
                    'getRegistrationFormFieldNames' => array(
                        'manyToOne' => 'getRegistrationFormField'
                    )
                )
            ), true
        );
        $query = "DELETE FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                        WHERE id = '".intval($this->id)."'";

        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            //Trigger postRemove event for Form Entity
            $this->triggerEvent('model/postRemove', $form);
            $this->triggerEvent('model/postFlush');
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
    function switchStatus()
    {
        global $objDatabase;

        $formStatus = ($this->status == 1) ? 0 : 1;
        $form = $this->getFormEntity(
            $this->id, array('fields' => array('status' => $formStatus))
        );
        //Trigger preUpdate event for Form Entity
        $this->triggerEvent(
            'model/preUpdate', $form,
            array(
                'relations' => array(
                    'oneToMany' => array(
                        'getEvents', 'getRegistrationFormFields'
                    )
                ),
                'joinEntityRelations' => array(
                    'getRegistrationFormFields' => array(
                        'oneToMany' => array(
                            'getRegistrationFormFieldNames',
                            'getRegistrationFormFieldValues'
                        ),
                        'manyToOne' => 'getRegistrationForm'
                    ),
                    'getRegistrationFormFieldNames' => array(
                        'manyToOne' => 'getRegistrationFormField'
                    )
                )
            ), true
        );

        $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                     SET status = '".intval($formStatus)."'
                   WHERE id = '".intval($this->id)."'";

        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            //Trigger postUpdate event for Form Entity
            $this->triggerEvent('model/postUpdate', $form);
            $this->triggerEvent('model/postFlush');
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
    function saveOrder($order)
    {
        global $objDatabase;

        $form = $this->getFormEntity(
            $this->id, array('fields' => array('order' => $order))
        );
        //Trigger preUpdate event for Form Entity
        $this->triggerEvent(
            'model/preUpdate', $form,
            array(
                'relations' => array(
                    'oneToMany' => array(
                        'getEvents', 'getRegistrationFormFields'
                    )
                ),
                'joinEntityRelations' => array(
                    'getRegistrationFormFields' => array(
                        'oneToMany' => array(
                            'getRegistrationFormFieldNames',
                            'getRegistrationFormFieldValues'
                        ),
                        'manyToOne' => 'getRegistrationForm'
                    ),
                    'getRegistrationFormFieldNames' => array(
                        'manyToOne' => 'getRegistrationFormField'
                    )
                )
            ), true
        );
        $query = "UPDATE ".DBPREFIX."module_".$this->moduleTablePrefix."_registration_form
                     SET `order` = '".intval($order)."'
                   WHERE id = '".intval($this->id)."'";

        $objResult = $objDatabase->Execute($query);

        if ($objResult !== false) {
            //Trigger postUpdate event for Form Entity
            $this->triggerEvent('model/postUpdate', $form);
            $this->triggerEvent('model/postFlush');
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
        
        if ($objResult !== false) {
            return intval($objResult->fields['id']);
        } else {
        	return false;
        }
    }

    /**
     * Set form entity
     *
     * @param integer $id        form id
     * @param array   $formDatas form field values
     *
     * @return Cx\Modules\Calendar\Model\Entity\RegistrationForm
     */
    public function getFormEntity($id, $formDatas = array())
    {
        if (empty($id)) {
            $form = new \Cx\Modules\Calendar\Model\Entity\RegistrationForm();
        } else {
            $form = $this
                ->em
                ->getRepository('Cx\Modules\Calendar\Model\Entity\RegistrationForm')
                ->findOneById($id);
        }
        $form->setVirtual(true);

        if (!$form) {
            return null;
        }

        if (!$formDatas) {
            return $form;
        }
        //Set form field values
        foreach ($formDatas['fields'] as $fieldName => $fieldValue) {
            $methodName = 'set'.ucfirst($fieldName);
            if (method_exists($form, $methodName)) {
                $form->{$methodName}($fieldValue);
            }
        }

        $relations = $formDatas['relation'];
        if (!$relations || !$relations['inputFields']) {
            return $form;
        }

        //Set form input fields
        foreach ($relations['inputFields'] as $fieldValues) {
            $this->getFormFieldEntity($form, $fieldValues);
        }

        return $form;
    }

    /**
     * Get form field entity
     *
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationForm $form     form entity
     * @param array                                              $formData form field values
     *
     * @return \Cx\Modules\Calendar\Model\Entity\RegistrationFormField
     */
    public function getFormFieldEntity(
        \Cx\Modules\Calendar\Model\Entity\RegistrationForm $form,
        $formData
    ){
        //Set form field values
        $isNewEntity = false;
        $fieldValue = $formData['fields'];
        $formField = $form->getRegistrationFormFieldById($fieldValue['id']);
        if (!$formField) {
            $isNewEntity = true;
            $formField   = new \Cx\Modules\Calendar\Model\Entity\RegistrationFormField();
        }
        $formField->setVirtual(true);
        $formField->setType($fieldValue['type']);
        $formField->setOrder($fieldValue['order']);
        $formField->setRequired($fieldValue['required']);
        $formField->setAffiliation($fieldValue['affiliation']);

        if ($isNewEntity) {
            $form->addRegistrationFormField($formField);
            $formField->setRegistrationForm($form);
        }

        if (!$formData['formFieldNames']) {
            return $formField;
        }

        //Set formFieldName entity
        foreach ($formData['formFieldNames'] as $fieldNameValues) {
            $this->getFormFieldNameEntity($formField, $fieldNameValues);
        }

        return $formField;
    }

    /**
     * Get formFieldName entity
     *
     * @param \Cx\Modules\Calendar\Model\Entity\RegistrationFormField $formField formField entity
     * @param array                                                   $formData  formFieldValue field values
     *
     * @return \Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldName
     */
    public function getFormFieldNameEntity(
        \Cx\Modules\Calendar\Model\Entity\RegistrationFormField $formField,
        $formData
    ) {
        $isNewEntity = false;
        $formFieldName = $formField->getRegistrationFormFieldNamesByLangId(
            $formData['langId']
        );
        if (!$formFieldName) {
            $isNewEntity   = true;
            $formFieldName = new \Cx\Modules\Calendar\Model\Entity\RegistrationFormFieldName();
        }
        $formFieldName->setVirtual(true);
        //Set FormFieldName field values
        foreach ($formData as $fieldName => $fieldValue) {
            $methodName = 'set'.ucfirst($fieldName);
            if (method_exists($formFieldName, $methodName)) {
                $formFieldName->{$methodName}($fieldValue);
            }
        }

        if ($isNewEntity) {
            $formField->addRegistrationFormFieldName($formFieldName);
            $formFieldName->setRegistrationFormField($formField);
        }

        return $formFieldName;
    }
}
