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
 
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Cx\Core\Html\Controller;

class ViewGeneratorException extends \Exception {}

/**
 * Description of ViewGenerator
 *
 * @author ritt0r
 * @todo    Refactor
 */
class ViewGenerator {
    protected static $increment = 0;
    protected $object;
    protected $options;
    protected $number;
    protected $formGenerator = null;
    
    /**
     *
     * @param mixed $object Array, instance of DataSet, instance of EntityBase, object
     * @param $options is functions array 
     * @throws ViewGeneratorException 
     */
    public function __construct($object, $options = array()) {
        global $_ARRAYLANG;
        
        $this->number = static::$increment++;
        try {
            $this->options = $options;
            $entityNS=null;
            if (is_array($object)) {
                $object = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($object);
            }
            \JS::registerCSS(\Env::get('cx')->getCoreFolderName() . '/Html/View/Style/Backend.css');
            if ($object instanceof \Cx\Core_Modules\Listing\Model\Entity\DataSet) {
                // render table if no parameter is set
                $this->object = $object;
                $entityNS = $this->object->getDataType();
            } else {
                if (!is_object($object)) {
                    $entityClassName = $object;
                    $entityRepository = \Env::get('em')->getRepository($entityClassName);
                    $entities = $entityRepository->findAll();
                    if (empty($entities)) {
                        $this->object = new $entityClassName();
                        $entityNS = $entityClassName;
                    } else {
                        $this->object = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($entities);
                        $entityNS = $this->object->getDataType();
                    }
                } else {
                    // render form
                    $this->object = $object;
                    $entityNS = get_class($this->object);
                }
            }
            
            if (
                (!isset($_POST['vg_increment_number']) || $_POST['vg_increment_number'] != $this->number) &&
                (!isset($_GET['vg_increment_number']) || $_GET['vg_increment_number'] != $this->number)
            ) {
                $vgIncrementNo = 'empty';
                if (isset($_POST['vg_increment_number'])) {
                    $vgIncrementNo = '#' . $_POST['vg_increment_number'];
                } else if (isset($_GET['vg_increment_number'])) {
                    $vgIncrementNo = '#' . $_GET['vg_increment_number'];
                }
                // do not make any changes to entities of other view generator instances!
                \DBG::msg('Omitting changes, my ID is #' . $this->number . ', supplied number was ' . $vgIncrementNo);
                return;
            }

            /** 
             *  postSave event
             *  execute save if entry is a doctrine entity (or execute callback if specified in configuration)
             */
            $add=(!empty($_GET['add'])? contrexx_input2raw($_GET['add']):null);
            if (
                !empty($add) && (
                    !empty($this->options['functions']['add']) &&
                    $this->options['functions']['add'] != false
                ) || (
                    !empty($this->options['functions']['allowAdd']) &&
                    $this->options['functions']['allowAdd'] != false
                )
            ) {
                
                $this->renderFormForEntry(null);
                $form = $this->formGenerator;
                if ($form === false) {
                    // cannot save, no such entry
                    \Message::add('Cannot save, no such entry', \Message::CLASS_ERROR);
                    return;
                }
                if (!$form->isValid() || (isset($this->options['validate']) && !$this->options['validate']($form))) {
                    // data validation failed, stay in add view
                    \Message::add('Cannot save, validation failed', \Message::CLASS_ERROR);
                    return;
                }
                if (!empty($_POST)) {
                    $post=$_POST;
                    unset($post['csrf']);
                    $blankPost=true;
                    if (!empty($post)) {
                        foreach($post as $value) {
                            if ($value) $blankPost=false;
                        }
                    }
                    if ($blankPost) {
                        \Message::add('Cannot save, You should fill any one field!', \Message::CLASS_ERROR);
                        return;
                    }
                    $entityObject = \Env::get('em')->getClassMetadata($entityNS);
                    $primaryKeyName =$entityObject->getSingleIdentifierFieldName(); //get primary key name
                    $entityColumnNames = $entityObject->getColumnNames(); //get all field names

                    // create new entity without calling the constructor
// TODO: this might break certain entities!
                    $entityObj = $entityObject->newInstance();
                    foreach($entityColumnNames as $column) {
                        $field = $entityObject->getFieldName($column);
                        if (
                            isset($this->options['fields']) &&
                            isset($this->options['fields'][$field]) &&
                            isset($this->options['fields'][$field]['storecallback']) &&
                            is_callable($this->options['fields'][$field]['storecallback'])
                        ) {
                            $storecallback = $this->options['fields'][$field]['storecallback'];
                            $postedValue = null;
                            if (isset($_POST['field'])) {
                                $postedValue = contrexx_input2raw($_POST[$field]);
                            }
                            $_POST[$field] = $storecallback($postedValue);
                        }
                        if (isset($_POST[$field]) && $field != $primaryKeyName) {
                            $fieldDefinition = $entityObject->getFieldMapping($field);
                            if ($fieldDefinition['type'] == 'datetime') {
                                $newValue = new \DateTime($_POST[$field]);
                            } elseif ($fieldDefinition['type'] == 'array') {
                                $newValue = unserialize($_POST[$field]);
                                // verify that the value is actually an array -> prevent to store other php data
                                if (!is_array($newValue)) {
                                    $newValue = array();
                                }
                            } else {
                                $newValue = contrexx_input2raw($_POST[$field]);
                            }
                            $entityObj->{'set'.preg_replace('/_([a-z])/', '\1', ucfirst($field))}($newValue);
                        }
                    }

                    // store single-valued-associations
                    $associationMappings = \Env::get('em')->getClassMetadata($entityNS)->getAssociationMappings();
                    $classMethods = get_class_methods($entityObj);
                    foreach ($associationMappings as $field => $associationMapping) {
                        if (   !empty($_POST[$field])
                            && \Env::get('em')->getClassMetadata($entityNS)->isSingleValuedAssociation($field)
                            && in_array('set'.ucfirst($field), $classMethods)
                        ) {
                            $col = $associationMapping['joinColumns'][0]['referencedColumnName'];
                            $association = \Env::get('em')->getRepository($associationMapping['targetEntity'])->findOneBy(array($col => $_POST[$field]));
                            $entityObj->{'set'.ucfirst($field)}($association);
                        }
                    }

                    if ($entityObj instanceof \Cx\Core\Model\Model\Entity\YamlEntity) {
                        $entityRepository = \Env::get('em')->getRepository($entityNS);
                        $entityRepository->add($entityObj);
                        $entityRepository->flush();
                    } else {
                        if (!($entityObj instanceof \Cx\Model\Base\EntityBase)) {
                            \DBG::msg('Unkown entity model '.get_class($entityObj).'! Trying to persist using entity manager...');
                        }
                        \Env::get('em')->persist($entityObj);
                        \Env::get('em')->flush();
                    }
                    \Message::add($_ARRAYLANG['TXT_CORE_RECORD_ADDED_SUCCESSFUL']);   
                    $actionUrl = clone \Env::get('cx')->getRequest()->getUrl();
                    $actionUrl->setParam('add', null);
                    \Cx\Core\Csrf\Controller\Csrf::redirect($actionUrl);
                }
            }

            /** 
             *  postEdit event
             *  execute edit if entry is a doctrine entity (or execute callback if specified in configuration)
             */
            if (
                $this->isInEditMode() && (
                    (
                        !empty($this->options['functions']['edit']) &&
                        $this->options['functions']['edit'] != false
                    ) || (
                        !empty($this->options['functions']['allowEdit']) &&
                        $this->options['functions']['allowEdit'] != false
                    )
                )
            ) {
                $entityId = contrexx_input2raw($this->isInEditMode());
                // render form for editid
                $this->renderFormForEntry($entityId);
                $form = $this->formGenerator;
                if ($form === false) {
                    // cannot save, no such entry
                    \Message::add('Cannot save, no such entry', \Message::CLASS_ERROR);
                    return;
                }
                if (!$form->isValid() || (isset($this->options['validate']) && !$this->options['validate']($form))) {
                    // data validation failed, stay in edit view
                    \Message::add('Cannot save, validation failed', \Message::CLASS_ERROR);
                    return;
                }
                $entityObject=array();
                if ($this->object->entryExists($entityId)) {
                    $entityObject = $this->object->getEntry($entityId);
                }
                if (empty($entityObject)) {
                    \Message::add('Cannot save, Invalid entry', \Message::CLASS_ERROR);
                    return;
                }
                $updateArray=array();
                $entityObj = \Env::get('em')->getClassMetadata($entityNS);
                $primaryKeyName =$entityObj->getSingleIdentifierFieldName(); //get primary key name  
                $associationMappings = \Env::get('em')->getClassMetadata($entityNS)->getAssociationMappings();
                $classMethods = get_class_methods($entityObj->newInstance());
                foreach ($entityObject as $name=>$value) {
                    if (!isset ($_POST[$name])) {
                        continue;
                    }
                    $methodName = 'set'.str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
                    if (   \Env::get('em')->getClassMetadata($entityNS)->isSingleValuedAssociation($name)
                        && in_array($methodName, $classMethods)
                    ) {
                        // store single-valued-associations
                        $col = $associationMappings[$name]['joinColumns'][0]['referencedColumnName'];
                        $association = \Env::get('em')->getRepository($associationMappings[$name]['targetEntity'])->findOneBy(array($col => $_POST[$name]));
                        $updateArray[$methodName] = $association;
                    } elseif (   $_POST[$name] != $value
                              && in_array($methodName, $classMethods)
                    ) {
                        $fieldDefinition = $entityObj->getFieldMapping($name);
                        if (
                            isset($this->options['fields']) &&
                            isset($this->options['fields'][$name]) &&
                            isset($this->options['fields'][$name]['storecallback']) &&
                            is_callable($this->options['fields'][$name]['storecallback'])
                        ) {
                            $storecallback = $this->options['fields'][$name]['storecallback'];
                            $newValue = $storecallback(contrexx_input2raw($_POST[$name]));
                        } else if ($fieldDefinition['type'] == 'datetime') {
                            if (empty($_POST[$name])) {
                                $newValue = null;
                            } else {
                                $newValue = new \DateTime($_POST[$name]);
                            }
                        } elseif ($fieldDefinition['type'] == 'array') {
                            $newValue = unserialize($_POST[$name]);
                            // verify that the value is actually an array -> prevent to store other php data
                            if (!is_array($newValue)) {
                                $newValue = array();
                            }
                        } else {
                            $newValue = contrexx_input2raw($_POST[$name]);
                        }
                        $updateArray[$methodName] = $newValue;
                    }
                }
                $id = $entityObject[$primaryKeyName]; //get primary key value  
                if (!empty($updateArray) && !empty($id)) {
                    $entityObj = \Env::get('em')->getRepository($entityNS)->find($id);
                    if (!empty($entityObj)) {
                        foreach($updateArray as $key=>$value) {
                            $entityObj->$key($value);
                        }
                        if ($entityObj instanceof \Cx\Core\Model\Model\Entity\YamlEntity) {
                            \Env::get('em')->getRepository($entityNS)->flush();
                        } else {
                            \Env::get('em')->flush();    
                        }
                        \Message::add($_ARRAYLANG['TXT_CORE_RECORD_UPDATED_SUCCESSFUL']);   
                    } else {
                        \Message::add('Cannot save, Invalid argument!', \Message::CLASS_ERROR);
                    }
                } 
                $actionUrl = clone \Env::get('cx')->getRequest()->getUrl();
                $actionUrl->setParam('editid', null);
                \Cx\Core\Csrf\Controller\Csrf::redirect($actionUrl);
            }

            /**
             * trigger pre- and postRemove event
             * execute remove if entry is a doctrine entity (or execute callback if specified in configuration)
             */
            $deleteId = !empty($_GET['deleteid']) ? contrexx_input2raw($_GET['deleteid']) : '';
            if (
                $deleteId!='' && (
                    (
                        !empty($this->options['functions']['delete']) &&
                        $this->options['functions']['delete'] != false
                    ) || (
                        !empty($this->options['functions']['allowDelete']) &&
                        $this->options['functions']['allowDelete'] != false
                    )
                )
            ) {
                $entityObject = $this->object->getEntry($deleteId);
                if (empty($entityObject)) {
                    \Message::add('Cannot save, Invalid entry', \Message::CLASS_ERROR);
                    return;
                }
                $entityObj = \Env::get('em')->getClassMetadata($entityNS);  
                $primaryKeyName =$entityObj->getSingleIdentifierFieldName(); //get primary key name  
                $id=$entityObject[$primaryKeyName]; //get primary key value  
                if (!empty($id)) {
                    $entityObj=\Env::get('em')->getRepository($entityNS)->find($id);
                    if (!empty($entityObj)) {
                        if ($entityObj instanceof \Cx\Core\Model\Model\Entity\YamlEntity) {
                            $ymlRepo = \Env::get('em')->getRepository($entityNS);
                            $ymlRepo->remove($entityObj);;
                            $ymlRepo->flush();
                        } else {
                            \Env::get('em')->remove($entityObj);
                            \Env::get('em')->flush();
                        }
                        \Message::add($_ARRAYLANG['TXT_CORE_RECORD_DELETED_SUCCESSFUL']);   
                    }
                }
                $actionUrl = clone \Env::get('cx')->getRequest()->getUrl();
                $actionUrl->setParam('deleteid', null);
                \Cx\Core\Csrf\Controller\Csrf::redirect($actionUrl);
            }
        } catch (\Exception $e) {
            \Message::add($e->getMessage());
            return;
        }
    }
    
    /**
     * $_GET['editid'] has the following format:
     * {<vg_incr_no>,<id_to_edit>}[,{<vg_incr_no>,<id_to_edit>}[,...]
     * <id_to_edit> can be a number, string or set of both, separated by comma
     */
    protected function isInEditMode() {
        if (!isset($_GET['editid']) && !isset($_POST['editid'])) {
            return false;
        }
        if (isset($_GET['editid'])) {
            $edits = explode('},{', substr($_GET['editid'], 1, -1));
            foreach ($edits as $edit) {
                $edit = explode(',', $edit);
                if ($edit[0] != $this->number) {
                    continue;
                }
                unset($edit[0]);
                if (count($edit) == 1) {
                    return current($edit);
                }
                return $edit;
            }
        }
        if (isset($_POST['editid'])) {
            $edits = explode('},{', substr($_POST['editid'], 1, -1));
            foreach ($edits as $edit) {
                $edit = explode(',', $edit);
                if ($edit[0] != $this->number) {
                    continue;
                }
                unset($edit[0]);
                if (count($edit) == 1) {
                    return current($edit);
                }
                return $edit;
            }
        }
        return false;
    }
    
    public function render(&$isSingle = false) {
        global $_ARRAYLANG;
        if (!empty($_GET['add']) 
            && !empty($this->options['functions']['add'])) {
            $isSingle = true;
            return $this->renderFormForEntry(null);
        }
        $renderObject = $this->object;
        $entityClass = get_class($this->object);
        $entityId = '';
        if ($this->object instanceof \Cx\Core_Modules\Listing\Model\Entity\DataSet
            && $this->isInEditMode()) {
            $entityClass = $this->object->getDataType();
            $entityId = contrexx_input2raw($this->isInEditMode());
            if ($this->object->entryExists($entityId)) {
                $renderObject = $this->object->getEntry($entityId);
            }
        }
        if ($renderObject instanceof \Cx\Core_Modules\Listing\Model\Entity\DataSet) {
            if(!empty($this->options['order']['overview'])) {
                $renderObject->sortColumns($this->options['order']['overview']);
            }
            $addBtn = '';
            $actionUrl = clone \Env::get('cx')->getRequest()->getUrl();
            if (!empty($this->options['functions']['add'])) {
                $actionUrl->setParam('add', 1);
                $addBtn = '<br /><br /><input type="button" name="addEtity" value="'.$_ARRAYLANG['TXT_ADD'].'" onclick="location.href='."'".$actionUrl."&csrf=".\Cx\Core\Csrf\Controller\Csrf::code()."'".'" />'; 
            }
            if (!count($renderObject) || !count(current($renderObject))) {
                // make this configurable
                $tpl = new \Cx\Core\Html\Sigma(\Env::get('cx')->getCodeBaseCorePath().'/Html/View/Template/Generic');
                $tpl->loadTemplateFile('NoEntries.html');
                return $tpl->get().$addBtn;
            }
            $listingController = new \Cx\Core_Modules\Listing\Controller\ListingController($renderObject, array(), $this->options['functions']);
            $renderObject = $listingController->getData();
            $this->options['functions']['vg_increment_number'] = $this->number;
            $backendTable = new \BackendTable($renderObject, $this->options) . '<br />' . $listingController;

            return $backendTable.$addBtn;
        } else {
            $isSingle = true;
            return $this->renderFormForEntry($entityId);
        }
    }
    
    protected function renderFormForEntry($entityId) {
        global $_CORELANG;

        $renderArray=array('vg_increment_number' => $this->number);
        if (!isset($this->options['fields'])) {
            $this->options['fields'] = array();
        }
        $this->options['fields']['vg_increment_number'] = array('type' => 'hidden');
        $entityTitle = isset($this->options['entityName']) ? $this->options['entityName'] : $_CORELANG['TXT_CORE_ENTITY'];
        if ($this->object instanceof \Cx\Core_Modules\Listing\Model\Entity\DataSet) {
            $entityClass = $this->object->getDataType();
        } else {
            $entityClass = get_class($this->object);
        }
        $entityObject = \Env::get('em')->getClassMetadata($entityClass);
        $primaryKeyNames = $entityObject->getIdentifierFieldNames();
        if (!$entityId && !empty($this->options['functions']['add'])) {
            if (!isset($this->options['cancelUrl']) || !is_a($this->options['cancelUrl'], 'Cx\Core\Routing\Url')) {
                $this->options['cancelUrl'] = clone \Env::get('cx')->getRequest()->getUrl();
            }
            $this->options['cancelUrl']->setParam('add', null);
            $actionUrl = clone \Env::get('cx')->getRequest()->getUrl();
            $title = sprintf($_CORELANG['TXT_CORE_ADD_ENTITY'], $entityTitle);
            $actionUrl->setParam('add', 1);
            $entityColumnNames = $entityObject->getColumnNames(); //get all field names  
            if (empty($entityColumnNames)) return false;
            foreach($entityColumnNames as $column) {
                $field = $entityObject->getFieldName($column);
                if (in_array($field, $primaryKeyNames)) {
                    continue;
                }
                $fieldDefinition = $entityObject->getFieldMapping($field);
                $this->options[$field]['type'] = $fieldDefinition['type'];
                if ($entityObject->getFieldValue($this->object, $field) !== null) {
                    $renderArray[$field] = $entityObject->getFieldValue($this->object, $field);
                    continue;
                }
                $renderArray[$field] = '';
            }
            // load single-valued-associations
            $associationMappings = \Env::get('em')->getClassMetadata($entityClass)->getAssociationMappings();
            $classMethods = get_class_methods($entityObject->newInstance());
            foreach ($associationMappings as $field => $associationMapping) {
                if (   \Env::get('em')->getClassMetadata($entityClass)->isSingleValuedAssociation($field)
                    && in_array('set'.ucfirst($field), $classMethods)
                ) {
                    if ($entityObject->getFieldValue($this->object, $field)) {
                        $renderArray[$field] = $entityObject->getFieldValue($this->object, $field);
                        continue;
                    }
                    $renderArray[$field]= new $associationMapping['targetEntity']();
                }
            }
        } elseif ($entityId && $this->object->entryExists($entityId)) {
            if (!isset($this->options['cancelUrl']) || !is_a($this->options['cancelUrl'], 'Cx\Core\Routing\Url')) {
                $this->options['cancelUrl'] = clone \Env::get('cx')->getRequest()->getUrl();
            }
            $this->options['cancelUrl']->setParam('editid', null);
            $actionUrl = clone \Env::get('cx')->getRequest()->getUrl();
            $title = sprintf($_CORELANG['TXT_CORE_EDIT_ENTITY'], $entityTitle);
            $actionUrl->setParam('editid', null);
            $renderObject = $this->object->getEntry($entityId);
            if (empty($renderObject)) return false;
            foreach($renderObject as $name => $value) {
                if ($name == 'virtual') {
                    continue;
                }
                if (in_array($name, $primaryKeyNames)) {
                    continue;
                }

                $fieldDefinition['type'] = null;
                if (!\Env::get('em')->getClassMetadata($entityClass)->hasAssociation($name)) {
                    $fieldDefinition = $entityObject->getFieldMapping($name);
                }
                $this->options[$name]['type'] = $fieldDefinition['type'];
                $renderArray[$name] = $value;
            }

            // load single-valued-associations
            // this is used for those object fields that are associations, but no object has been assigned to yet
            $associationMappings = \Env::get('em')->getClassMetadata($entityClass)->getAssociationMappings();
            $classMethods = get_class_methods($entityObject->newInstance());
            foreach ($associationMappings as $field => $associationMapping) {
                if (!empty($renderArray[$field])) {
                    continue;
                }
                if (   \Env::get('em')->getClassMetadata($entityClass)->isSingleValuedAssociation($field)
                    && in_array('set'.ucfirst($field), $classMethods)
                ) {
                    $renderArray[$field] = new $associationMapping['targetEntity']();
                }
            }
        } else {
            return false;
        }
        
        //sets the order of the fields
        if(!empty($this->options['order']['form'])) {
            $sortedData = array();
            foreach ($this->options['order']['form'] as $orderVal) {
                if(array_key_exists($orderVal, $renderArray)){
                    $sortedData[$orderVal] = $renderArray[$orderVal];
                }
            }
            $renderArray = array_merge($sortedData,$renderArray);
        }
        $this->formGenerator = new FormGenerator($renderArray, $actionUrl, $entityClass, $title, $this->options);
        // This should be moved to FormGenerator as soon as FormGenerator
        // gets the real entity instead of $renderArray
        $additionalContent = '';
        if (isset($this->options['preRenderDetail'])) {
            $callback = $this->options['preRenderDetail'];
            $additionalContent = $callback($this, $this->formGenerator, $entityId);
        }
        return $this->formGenerator . $additionalContent;
    }
    
    public function getObject() {
        return $this->object;
    }
    
    public function __toString() {
        try {
            return (string) $this->render();
        } catch (\Exception $e) {
            echo $e->getMessage();die();
        }
    }
}
