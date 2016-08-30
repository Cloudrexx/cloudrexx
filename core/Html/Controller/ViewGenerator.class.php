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
 * @todo Currently, composite primary keys cannot be handled
 */
class ViewGenerator {

    /**
     * @var int $increment This ID is used to store the next free $viewId
     */
    protected static $increment = 0;

    /**
     * @var int $viewId This ID is used as html id for the view so we can load more than one view
     */
    protected $viewId;

    /**
     * @var object $object
     */
    protected $object;

    /**
     * @var array $options form options
     */
    protected $options;

    /**
     * @var array $componentOptions component options
     */
    protected $componentOptions;

    /**
     * @var FormGenerator $formGenerator
     */
    protected $formGenerator = null;
    
    /**
     *
     * @param mixed $object Array, instance of DataSet, instance of EntityBase, object
     * @param array $options component options
     * @throws ViewGeneratorException if there is any error in try catch statement
     */
    public function __construct($object, $options = array()) {
        $this->componentOptions = $options;
        $this->viewId = static::$increment++;
        try {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            \JS::registerCSS($cx->getCoreFolderName() . '/Html/View/Style/Backend.css');
            $entityWithNS = preg_replace('/^\\\/', '', $this->findEntityClass($object));
            
            // this is a temporary "workaround" for combined keys, see todo
            $entityClassMetadata = \Env::get('em')->getClassMetadata($entityWithNS);
            if (count($entityClassMetadata->getIdentifierFieldNames()) > 1) {
                throw new \Exception('Currently, view generator is not able to handle composite keys...');
            }
            
            $this->options = array();
            if (isset($options[$entityWithNS]) && is_array($options[$entityWithNS])) {
                    $this->options = $options[$entityWithNS];
            } elseif (
                $entityWithNS == 'array'
                && isset($options['Cx\Core_Modules\Listing\Model\Entity\DataSet'])
                && isset($options['Cx\Core_Modules\Listing\Model\Entity\DataSet'][$object->getIdentifier()])
            ) {
                $this->options = $options['Cx\Core_Modules\Listing\Model\Entity\DataSet'][$object->getIdentifier()];
            }
            // If the options for this object are not set, we use the standard values from the component
            if (empty($this->options)) {
                $this->options = $options[''];
            }
            
            //initialize the row sorting functionality
            $this->getSortingOption($entityWithNS);
            
            if (
                (!isset($_POST['vg_increment_number']) || $_POST['vg_increment_number'] != $this->viewId) &&
                (!isset($_GET['vg_increment_number']) || $_GET['vg_increment_number'] != $this->viewId)
            ) {
                $vgIncrementNo = 'empty';
                if (isset($_POST['vg_increment_number'])) {
                    $vgIncrementNo = '#' . $_POST['vg_increment_number'];
                } else if (isset($_GET['vg_increment_number'])) {
                    $vgIncrementNo = '#' . $_GET['vg_increment_number'];
                }
                // do not make any changes to entities of other view generator instances!
                \DBG::msg('Omitting changes, my ID is #' . $this->viewId . ', supplied viewId was ' . $vgIncrementNo);
                return;
            }

            // execute add if entry is a doctrine entity (or execute callback if specified in configuration)
            // post add
            if (
                !empty($_GET['add']) && (
                    !empty($this->options['functions']['add']) &&
                    $this->options['functions']['add'] != false
                ) || (
                    !empty($this->options['functions']['allowAdd']) &&
                    $this->options['functions']['allowAdd'] != false
                )
            ) {
                $this->saveEntry($entityWithNS);
            }

            // execute edit if entry is a doctrine entity (or execute callback if specified in configuration)
            // post edit
            $editId = $this->getEntryId();
            if (
                $editId != 0 && (
                    (
                        !empty($this->options['functions']['edit']) &&
                        $this->options['functions']['edit'] != false
                    ) || (
                        !empty($this->options['functions']['allowEdit']) &&
                        $this->options['functions']['allowEdit'] != false
                    )
                )
            ) {
                $this->saveEntry($entityWithNS);
            }

            // execute remove if entry is a doctrine entity (or execute callback if specified in configuration)
            // post remove
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
                $this->removeEntry($entityWithNS);
            }
        } catch (\Exception $e) {
            \Message::add($e->getMessage());
            return;
        }
    }

    /**
     * This function is used to find the namespace of a passed object
     *
     * @access protected
     * @param object $object object of which the namespace is needed
     * @return string namespace of the passed object
     */
    protected function findEntityClass($object)
    {
        if (is_array($object)) {
            $object = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($object);
        }
        if ($object instanceof \Cx\Core_Modules\Listing\Model\Entity\DataSet) {
            // render table if no parameter is set
            $this->object = $object;
            return $this->object->getDataType();
        } else {
            if (!is_object($object)) {
                $entityClassName = $object;
                $entityRepository = \Env::get('em')->getRepository($entityClassName);
                $entities = $entityRepository->findAll();
                if (empty($entities)) {
                    $this->object = new $entityClassName();
                    return $entityClassName;
                } else {
                    $this->object = new \Cx\Core_Modules\Listing\Model\Entity\DataSet($entities);
                    return $this->object->getDataType();
                }
            } else {
                $this->object = $object;
                return get_class($this->object);
            }
        }
    }

    /**
     * This function saves the data of an entity to its class.
     * This only prepares the database store, but does not store it in database
     * To store them in database use persist and flush from doctrine
     *
     * @access protected
     * @param object $entity object of the class we want to save
     * @param Doctrine\ORM\Mapping\ClassMetadata $entityClassMetadata MetaData for the entity
     * @param array $entityData array with data to save to class
     * @param string $associatedTo the class which is oneToManyAssociated if it exists
     */
    protected function savePropertiesToClass($entity, $entityClassMetadata, $entityData = array(), $associatedTo='')
    {

        // if entityData is not set, we use $_POST as default, because the data are normally submitted over post
        if (empty($entityData)) {
            $entityData = $_POST;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $primaryKeyName = $entityClassMetadata->getSingleIdentifierFieldName(); //get primary key name
        $entityColumnNames = $entityClassMetadata->getColumnNames(); //get the names of all fields

        //If the view is sortable, get the 'sortBy' field name and store it to the variable
        $sortByFieldName = (    isset($this->options['functions']['sortBy'])
                            &&  isset($this->options['functions']['sortBy']['field'])
                            &&  !empty($this->options['functions']['sortBy']['field'])
                           )
                           ? key($this->options['functions']['sortBy']['field'])
                           : '';
        //check the 'sortBy' field is self-healing or not
        $isSortSelfHealing = (    isset($this->options['fields'])
                              &&  isset($this->options['fields'][$sortByFieldName])
                              &&  isset($this->options['fields'][$sortByFieldName]['showDetail'])
                              &&  !$this->options['fields'][$sortByFieldName]['showDetail']
                             )
                             ? true 
                             : false;
        // Foreach possible attribute in the database we try to find the matching entry in the $entityData array and add it
        // as property to the object
        foreach($entityColumnNames as $column) {
            $name = $entityClassMetadata->getFieldName($column);
            $fieldSetMethodName = 'set'.preg_replace('/_([a-z])/', '\1', ucfirst($name));
            $fieldGetMethodName = 'get'.preg_replace('/_([a-z])/', '\1', ucfirst($name));
            if (
                isset($this->options['fields']) &&
                isset($this->options['fields'][$name]) &&
                isset($this->options['fields'][$name]['storecallback'])
            ) {
                $storecallback = $this->options['fields'][$name]['storecallback'];
                $postedValue = null;
                if (isset($entityData[$name])) {
                    $postedValue = contrexx_input2raw($entityData[$name]);
                }
                /* We use json to do the storecallback. The 'else if' is for backwards compatibility so you can declare
                 * the function directly without using json. This is not recommended and not working over session */
                if (
                    is_array($storecallback) &&
                    isset($storecallback['adapter']) &&
                    isset($storecallback['method'])
                ) {
                    $json = new \Cx\Core\Json\JsonData();
                    $jsonResult = $json->data(
                        $storecallback['adapter'],
                        $storecallback['method'],
                        array(
                            'postedValue' => $postedValue,
                        )
                    );
                    if ($jsonResult['status'] == 'success') {
                        $entityData[$name] = $jsonResult["data"];
                    }
                } else if (is_callable($storecallback)) {
                    $entityData[$name] = $storecallback($postedValue);
                }
            }
            if (isset($entityData[$name]) && $name != $primaryKeyName) {
                $fieldDefinition = $entityClassMetadata->getFieldMapping($name);
                if ($fieldDefinition['type'] == 'datetime') {
                    $newValue = new \DateTime($entityData[$name]);
                } elseif ($fieldDefinition['type'] == 'array') {
                    $newValue = unserialize($entityData[$name]);
                    // verify that the value is actually an array -> prevent to store other php data
                    if (!is_array($newValue)) {
                        $newValue = array();
                    }
                } else {
                    $newValue = contrexx_input2raw($entityData[$name]);
                }
                // set the value as property of the current object, so it is ready to be stored in the database
                $entity->$fieldSetMethodName($newValue);
            }

            //While adding a new entity, if the view is sortable and 'sortBy' field is disabled in the edit view
            //then the new entity sort order gets automatically adjusted.
            if (    $isSortSelfHealing  
                &&  !empty($sortByFieldName)
                &&  ($sortByFieldName === $name)
                &&  !$entity->$fieldGetMethodName()
            ) {
                $qb = $em->createQueryBuilder();
                $qb ->select('e')
                    ->from(get_class($entity), 'e')
                    ->orderBy('e.' . $name, 'DESC')
                    ->setMaxResults(1);
                $result   = $qb->getQuery()->getResult();
                $newValue = isset($result[0]) ? ($result[0]->$fieldGetMethodName() + 1) : 1;
                // set the value as property of the current object, 
                // so it is ready to be stored in the database
                $entity->$fieldSetMethodName($newValue);
            }
        }
        // save singleValuedAssociations
        foreach ($entityClassMetadata->getAssociationMappings() as $associationMapping) {
            // we're only interested in single valued associations here, so skip others
            if (!$entityClassMetadata->isSingleValuedAssociation($associationMapping['fieldName'])) {
                continue;
            }
            // we're only interested if there's a target entity other than $associatedTo, so skip others
            if (
                $associationMapping['targetEntity'] == '' ||
                $associatedTo == $associationMapping['targetEntity']
            ) {
                continue;
            }
            
            // save it:
            
            // case a) was open in form directly
            $firstOffset = str_replace('\\', '_', strtolower($associationMapping['sourceEntity']));
            $secondOffset = $associationMapping['fieldName'];
            if (isset($entityData[$secondOffset])) {
                $this->storeSingleValuedAssociation(
                    $associationMapping['targetEntity'],
                    array(
                        $associationMapping['joinColumns'][0]['referencedColumnName'] => $entityData[$secondOffset],
                    ),
                    $entity,
                    'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $associationMapping['fieldName'])))
                );
                continue;
            }
            
            // base b) was open in a modal form
            foreach ($_POST[$firstOffset] as $foreignEntityDataEncoded) {
                $foreignEntityData = array();
                parse_str($foreignEntityDataEncoded, $foreignEntityData);
                
                if (!isset($foreignEntityData[$secondOffset])) {
                    // todo: remove entity!
                    continue;
                }
                
                // todo: add/save entity
                $this->storeSingleValuedAssociation(
                    $associationMapping['targetEntity'],
                    array(
                        $associationMapping['joinColumns'][0]['referencedColumnName'] => $foreignEntityData[$secondOffset],
                    ),
                    $entity,
                    'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $associationMapping['fieldName'])))
                );
            }
        }
    }

    /**
     * Initialize the row sorting functionality
     * 
     * @param string $entityNameSpace entity namespace
     * 
     * @return boolean
     */
    protected function getSortingOption($entityNameSpace)
    {
        //If the entity namespace is empty or an array then disable the row sorting
        if (empty($entityNameSpace) && $entityNameSpace === 'array') {
            return;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $sortBy = (     isset($this->options['functions']['sortBy']) 
                    &&  is_array($this->options['functions']['sortBy'])
                  )
                  ? $this->options['functions']['sortBy']
                  : array();
        //If the option 'sortBy' is not set in the function array
        // then disable the row sorting.
        if (empty($sortBy)) {
            return;
        }
        
        //If the function array has 'order' option and the order by field 
        //is not equal to 'sortBy' => 'field' then disable the row sorting
        $sortField   = key($this->options['functions']['sortBy']['field']);
        $orderOption = (    isset($this->options['functions']['order']) 
                        &&  is_array($this->options['functions']['order'])
                       ) 
                       ? key($this->options['functions']['order']) : array();
        if (!empty($orderOption) && stripos($orderOption, $sortField) === false) {
            return;
        }

        //get the primary key name
        $entityObject   = $em->getClassMetadata($entityNameSpace);
        $primaryKeyName = $entityObject->getSingleIdentifierFieldName();

        //If the 'sortBy' option does not have 'jsonadapter', 
        //we need to get the component name and entity name for updating the sorting order in db
        $componentName = '';
        $entityName    = '';
        if (    !isset($sortBy['jsonadapter']) 
            ||  (    isset($sortBy['jsonadapter'])
                 &&  (    empty($sortBy['jsonadapter']['object'])
                      ||  empty($sortBy['jsonadapter']['act'])
                    )
                )
        ) {
            $split          = explode('\\', $entityNameSpace);
            $componentName  = isset($split[2]) ? $split[2] : '';
            $entityName     = isset($split) ? end($split) : '';
        }

        //If 'sorting' is applied and sorting field is not equal to
        //'sortBy' => 'field' then disable the row sorting.
        $orderParamName = $entityName . 'Order';
        if (    isset($_GET[$orderParamName]) 
            &&  stripos($_GET[$orderParamName], $sortField) === false
        ) {
            return;
        }

        //Get the current sorting order
        $order     = isset($_GET[$orderParamName]) ? explode('/', $_GET[$orderParamName]) : '';
        $sortOrder = ($sortBy['field'][$sortField] == SORT_ASC) ? 'ASC' : 'DESC';
        if ($order) {
            $sortOrder = !empty($order[1]) ? $order[1] : 'ASC';
        }

        //Get the paging position value
        $pagingPosName  = $entityName . 'Pos';
        $pagingPosition = isset($_GET[$pagingPosName]) 
                          ? contrexx_input2int($_GET[$pagingPosName]) 
                          : 0;

        //set the sorting parameters in the functions 'sortBy' array and 
        //it should be used in the Backend::constructor
        $this->options['functions']['sortBy']['sortingKey'] = $primaryKeyName;
        $this->options['functions']['sortBy']['component']  = $componentName;
        $this->options['functions']['sortBy']['entity']     = $entityName;
        $this->options['functions']['sortBy']['sortOrder']  = $sortOrder;
        $this->options['functions']['sortBy']['pagingPosition'] = $pagingPosition;
        
        //Register the script Backend.js and activate the jqueryui and cx for the row sorting
        \JS::registerJS(substr($cx->getCoreFolderName() . '/Html/View/Script/Backend.js', 1));
    }

    /**
     * This function returns the EntryId which was sent over get or post (if both are set it will take get)
     *
     * $_GET['editid'] has the following format:
     * {<vg_incr_no>,<id_to_edit>}[,{<vg_incr_no>,<id_to_edit>}[,...]
     * <id_to_edit> can be a number, string or set of both, separated by comma
     *
     * @access protected
     * @return int 0 if no entry was found
     */
    protected function getEntryId() {
        if (!isset($_GET['editid']) && !isset($_POST['editid'])) {
            return 0;
        }
        if (isset($_GET['editid'])) {
            $edits = explode('},{', substr($_GET['editid'], 1, -1));
            foreach ($edits as $edit) {
                $edit = explode(',', $edit);
                if ($edit[0] != $this->viewId) {
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
                if ($edit[0] != $this->viewId) {
                    continue;
                }
                unset($edit[0]);
                if (count($edit) == 1) {
                    return current($edit);
                }
                return $edit;
            }
        }
    }

    /**
     * This function finds out what we want to render and then renders the form
     *
     * @param boolean $isSingle if we only render one entry
     * @access public
     * @return string rendered view
     */
    public function render(&$isSingle = false) {
        global $_ARRAYLANG;

        // this case is used to generate the add entry form, where we can create an new entry
        if (!empty($_GET['add']) 
            && !empty($this->options['functions']['add'])) {
            $isSingle = true;
            return $this->renderFormForEntry(null);
        }
        $renderObject = $this->object;
        $entityId = $this->getEntryId();

        // this case is used to get the right entry if we edit a existing one
        if ($this->object instanceof \Cx\Core_Modules\Listing\Model\Entity\DataSet
            && $entityId != 0) {
            if ($this->object->entryExists($entityId)) {
                $renderObject = $this->object->getEntry($entityId);
            }
        }

        // this case is used for the overview off all entities
        if ($renderObject instanceof \Cx\Core_Modules\Listing\Model\Entity\DataSet) {
            if(!empty($this->options['order']['overview'])) {
                $renderObject->sortColumns($this->options['order']['overview']);
            }
            $addBtn = '';
            $actionUrl = clone \Env::get('cx')->getRequest()->getUrl();
            if (!empty($this->options['functions']['add'])) {
                $actionUrl->setParam('add', 1);
                //remove the parameter 'vg_increment_number' from actionUrl
                //if the baseUrl contains the parameter 'vg_increment_number'
                $params = $actionUrl->getParamArray();
                if (isset($params['vg_increment_number'])) {
                    \Html::stripUriParam($actionUrl, 'vg_increment_number');
                }
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
            $this->options['functions']['vg_increment_number'] = $this->viewId;
            $backendTable = new \BackendTable($renderObject, $this->options) . '<br />' . $listingController;

            return $backendTable.$addBtn;
        }

        // render form for single entry view like editEntry
        $isSingle = true;
        return $this->renderFormForEntry($entityId);
    }

    /**
     * This function will render the form for a given entry by id. If id is null, an empty form will be loaded
     *
     * @access protected
     * @param int $entityId id of the entity
     * @return string rendered view
     */
    protected function renderFormForEntry($entityId) {
        global $_CORELANG;

        $renderArray=array('vg_increment_number' => $this->viewId);
        if (!isset($this->options['fields'])) {
            $this->options['fields'] = array();
        }
        $this->options['fields']['vg_increment_number'] = array('type' => 'hidden');
        // the title is used for the heading. For example the heading in edit mode will be "edit [$entityTitle]"
        $entityTitle = isset($this->options['entityName']) ? $this->options['entityName'] : $_CORELANG['TXT_CORE_ENTITY'];

        // get the class name including the whole namspace of the class
        if ($this->object instanceof \Cx\Core_Modules\Listing\Model\Entity\DataSet) {
            $entityClassWithNS = $this->object->getDataType();
        } else {
            $entityClassWithNS = get_class($this->object);
        }
        $entityObject = \Env::get('em')->getClassMetadata($entityClassWithNS);
        $primaryKeyNames = $entityObject->getIdentifierFieldNames(); // get the name of primary key in database table
        if ($entityId == 0 && !empty($this->options['functions']['add'])) { // load add entry form
            $this->setProperCancelUrl('add');
            $actionUrl = clone \Env::get('cx')->getRequest()->getUrl();
            $actionUrl->setParam('add', 1);
            $title = sprintf($_CORELANG['TXT_CORE_ADD_ENTITY'], $entityTitle);
            $entityColumnNames = $entityObject->getColumnNames(); // get all database field names
            if (empty($entityColumnNames)) {
                return false;
            }
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
            $associationMappings = \Env::get('em')->getClassMetadata($entityClassWithNS)->getAssociationMappings();
            $classMethods = get_class_methods($entityObject->newInstance());
            foreach ($associationMappings as $field => $associationMapping) {
                if (   \Env::get('em')->getClassMetadata($entityClassWithNS)->isSingleValuedAssociation($field)
                    && in_array('set'.ucfirst($field), $classMethods)
                ) {
                    if ($entityObject->getFieldValue($this->object, $field)) {
                        $renderArray[$field] = $entityObject->getFieldValue($this->object, $field);
                        continue;
                    }
                    $renderArray[$field]= new $associationMapping['targetEntity']();
                } elseif (\Env::get('em')->getClassMetadata($entityClassWithNS)->isCollectionValuedAssociation($field)) {
                    $renderArray[$field]= new $associationMapping['targetEntity']();
                }
            }
        } elseif ($entityId != 0 && $this->object->entryExists($entityId)) { // load edit entry form
            $this->setProperCancelUrl('editid');
            $actionUrl = clone \Env::get('cx')->getRequest()->getUrl();
            $actionUrl->setParam('editid', null);
            $title = sprintf($_CORELANG['TXT_CORE_EDIT_ENTITY'], $entityTitle);

            // get data of all fields of the entry, except associated fields
            $renderObject = $this->object->getEntry($entityId);
            if (empty($renderObject)) {
                return false;
            }

            // get doctrine field name, database field name and type for each field
            foreach($renderObject as $name => $value) {
                if ($name == 'virtual' || in_array($name, $primaryKeyNames)) {
                    continue;
                }

                $fieldDefinition['type'] = null;
                if (!\Env::get('em')->getClassMetadata($entityClassWithNS)->hasAssociation($name)) {
                    $fieldDefinition = $entityObject->getFieldMapping($name);
                }
                $this->options[$name]['type'] = $fieldDefinition['type'];
                $renderArray[$name] = $value;
            }

            // load single-valued-associations
            // this is used for those object fields that are associations, but no object has been assigned to yet
            $associationMappings = \Env::get('em')->getClassMetadata($entityClassWithNS)->getAssociationMappings();
            $classMethods = get_class_methods($entityObject->newInstance());
            foreach ($associationMappings as $field => $associationMapping) {
                if (!empty($renderArray[$field])) {
                    if (\Env::get('em')->getClassMetadata($entityClassWithNS)->isCollectionValuedAssociation($field)) {
                        $renderArray[$field] = new $associationMapping['targetEntity']();
                    }
                } elseif (\Env::get('em')->getClassMetadata($entityClassWithNS)->isSingleValuedAssociation($field)
                    && in_array('set'.ucfirst($field), $classMethods)
                ) {
                    $renderArray[$field] = new $associationMapping['targetEntity']();
                }
            }
        } else {
            //var_dump($entityId);
            //var_dump($this->options['functions']['add']);
            //var_dump($this->object->entryExists($entityId));
            throw new ViewGeneratorException('Tried to show form but neither add nor edit view can be shown');
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
        $this->formGenerator = new FormGenerator($renderArray, $actionUrl, $entityClassWithNS, $title, $this->options, $entityId, $this->componentOptions);
        // This should be moved to FormGenerator as soon as FormGenerator
        // gets the real entity instead of $renderArray
        $additionalContent = '';
        if (isset($this->options['preRenderDetail'])) {
            $preRender = $this->options['preRenderDetail'];
            /* We use json to do preRender the detail. The 'else if' is for backwards compatibility so you can declare
             * the function directly without using json. This is not recommended and not working over session */
            if (
                isset($preRender) &&
                is_array($preRender) &&
                isset($preRender['adapter']) &&
                isset($preRender['method'])
            ) {
                $json = new \Cx\Core\Json\JsonData();
                $jsonResult = $json->data(
                    $preRender['adapter'],
                    $preRender['method'],
                    array(
                        'viewGenerator' => $this,
                        'formGenerator' => $this->formGenerator,
                        'entityId'  => $entityId,
                    )
                );
                if ($jsonResult['status'] == 'success') {
                    $additionalContent .= $jsonResult["data"];
                }
            } else if (is_callable($preRender)) {
                $additionalContent = $preRender($this, $this->formGenerator, $entityId);

            }
        }
        return $this->formGenerator . $additionalContent;
    }

    /**
     * This function will return the object of the ViewGenerator
     * @access public
     * @return object the object of ViewGenerator
     */
    public function getObject() {
        return $this->object;
    }

    /**
     * This function saves an entity to the database
     *
     * @param string $entityWithNS class name including namespace
     * @access protected
     * @global array $_ARRAYLANG array containing the language variables
     */
    protected function saveEntry($entityWithNS) {
        global $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        // if entityId is a number the user edited an existing entry. If it is null we create a new one
        $entityId = contrexx_input2raw($this->getEntryId());
        $this->renderFormForEntry($entityId);

        // if the form is not valid in any case, we stay in this view and do not save anything, because we can not be
        // sure that everything is alright
        if(!$this->validateForm()) {
            return;
        }

        // if there are no data submitted, we stay on this view, because we have nothing to save
        if(!$this->checkBlankPostRequest()){
            return;
        }

        $entityClassMetadata = $em->getClassMetadata($entityWithNS);
        $associationMappings = $entityClassMetadata->getAssociationMappings();

        // if we have a entityId, we came from edit mode and so we try to load the existing entry
        if($entityId != 0) {
            $entity = $em->getRepository($entityWithNS)->find($entityId);
            $entityArray = array(); // This array is used for the existing values
            if ($this->object->entryExists($entityId)) {
                $entityArray = $this->object->getEntry($entityId);
            }
            if (empty($entityArray)) {
                \Message::add($_ARRAYLANG['TXT_CORE_RECORD_NO_SUCH_ENTRY'], \Message::CLASS_ERROR);
                return;
            }
        } else {
            // create new entity without calling the constructor TODO: this might break certain entities!
            $entity = $entityClassMetadata->newInstance();
        }
        $classMethods = get_class_methods($entity);

        // this array is used to store all oneToMany associated entities, because we need to persist them for doctrine,
        // but we can not persist them before the main entity, so we need to buffer them
        $associatedEntityToPersist = array ();
        foreach ($associationMappings as $name => $value) {

            /* if we can not find the class name or the function to save the association we skip the entry, because there
               is now way to store it without these information */
            if (empty($value['targetEntity'])) {
                \Message::add(sprintf($_ARRAYLANG['TXT_CORE_RECORD_CLASS_NOT_FOUND'], $name), \Message::CLASS_ERROR);
                continue;
            }

            /* this variable is the name of the field where we saved the values of the one to many associations
               because css does not support \ in class name */
            $relatedClassInputFieldName = str_replace('\\', '_', strtolower($value["targetEntity"]));

            if (!empty($relatedClassInputFieldName)
                && !empty($_POST[$relatedClassInputFieldName])
                && $em->getClassMetadata($entityWithNS)->isCollectionValuedAssociation($name)
            ) {
                // store one to many associated entries
                $associatedEntityClassMetadata = $em->getClassMetadata($value["targetEntity"]);

                foreach ($_POST[$relatedClassInputFieldName] as $relatedPostData) {
                    $entityData = array();
                    parse_str($relatedPostData, $entityData);

                    // if we have already an entry (on update) we take the existing one and update it.
                    // Otherwise we create a new one
                    if (isset($entityData['id']) && $entityData['id'] != 0) { // update/edit case
                        $associatedClassRepo = $em->getRepository($value["targetEntity"]);
                        $associatedEntity = $associatedClassRepo->find($entityData['id']);
                    } else { // add case
                        $associatedEntity = $associatedEntityClassMetadata->newInstance();
                    }

                    // if there are any entries which the user wants to delete, we delete them here
                    if (isset($entityData['delete']) && $entityData['delete'] == 1) {
                        $em->remove($associatedEntity);
                    }

                    // save the "n" associated class data to its class
                    $this->savePropertiesToClass($associatedEntity, $associatedEntityClassMetadata, $entityData, $entityWithNS);

                    // Linking 1: link the associated entity to the main entity for doctrine
                    $methodName = 'add' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
                    if (!in_array($methodName, $classMethods)) {
                        \Message::add(sprintf($_ARRAYLANG['TXT_CORE_RECORD_FUNCTION_NOT_FOUND'], $name, $methodName), \Message::CLASS_ERROR);
                        continue;
                    }
                    $entity->$methodName($associatedEntity);

                    // Linking 2: link the main entity to its associated entity. This should normally be done by
                    // 'Linking 1' but because not all components have implemented this, we do it here by ourselves
                    $method = 'set' . ucfirst($value["mappedBy"]);
                    if (method_exists($associatedEntity, $method)) {
                        $associatedEntity->{$method}($entity);
                    }
                    
                    // buffer entity, so we can persist it later
                    $associatedEntityToPersist[] = $associatedEntity;
                }
            }
        }

        if($entityId != 0) { // edit case
            // update the main entry in doctrine so we can store it over doctrine to database later
            $this->savePropertiesToClass($entity, $entityClassMetadata);
            $param = 'editid';
            $successMessage = $_ARRAYLANG['TXT_CORE_RECORD_UPDATED_SUCCESSFUL'];
        } else { // add case
            // save main formular class data to its class over $_POST
            $this->savePropertiesToClass($entity, $entityClassMetadata);
            $param = 'add';
            $successMessage = $_ARRAYLANG['TXT_CORE_RECORD_ADDED_SUCCESSFUL'];
        }

        $showSuccessMessage = false;
        if ($entity instanceof \Cx\Core\Model\Model\Entity\YamlEntity) {
            // Save the yaml entities
            $entityRepository = $em->getRepository($entityWithNS);
            if (!$entityRepository->isManaged($entity)) {
                $entityRepository->add($entity);
            }
            $entityRepository->flush();
            $showSuccessMessage = true;
        } else if ($entity instanceof \Cx\Model\Base\EntityBase) {
            /* We try to store the prepared em. This may fail if (for example) we have a one to many association which
               can not be null but was not set in the post request. This cases should be caught here. */
            try{
                // persist main entity. This must be done first, otherwise saving oneToManyAssociated entities won't work
                $em->persist($entity);
                // now we can persist the associated entities. We need to do this, because otherwise it will fail,
                // if yaml does not contain a cascade option
                foreach ($associatedEntityToPersist as $associatedEntity) {
                    $em->persist($associatedEntity);
                }
                $em->flush();
                $showSuccessMessage = true;
            } catch(\Cx\Core\Error\Model\Entity\ShinyException $e){
                /* Display the message from the exception. If this message is empty, we output a general message,
                   so the user knows what to do in every case */
                if ($e->getMessage() != "") {
                    \Message::add($e->getMessage(), \Message::CLASS_ERROR);
                } else {
                    \Message::add($_ARRAYLANG['TXT_CORE_RECORD_UNKNOWN_ERROR'], \Message::CLASS_ERROR);
                }
                return;
            } catch (\Exception $e) {
                echo $e->getMessage();die();
            }

        } else {
            \Message::add($_ARRAYLANG['TXT_CORE_RECORD_VALIDATION_FAILED'], \Message::CLASS_ERROR);
            \DBG::msg('Unkown entity model '.get_class($entity).'! Trying to persist using entity manager...');
        }

        if($showSuccessMessage) {
            \Message::add($successMessage);
        }
        // get the proper action url and redirect the user
        $actionUrl = clone $cx->getRequest()->getUrl();
        $actionUrl->setParam($param, null);
        \Cx\Core\Csrf\Controller\Csrf::redirect($actionUrl);
    }
    
    /**
     * This function is used to delete an entry
     *
     * @param string $entityWithNS class name including namespace
     * @access protected
     * @global array $_ARRAYLANG array containing the language variables
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     * @throws \Exception
     */
    protected function removeEntry($entityWithNS) {
        global $_ARRAYLANG;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $deleteId = !empty($_GET['deleteid']) ? contrexx_input2raw($_GET['deleteid']) : '';
        $entityObject = $this->object->getEntry($deleteId);
        if (empty($entityObject)) {
            \Message::add($_ARRAYLANG['TXT_CORE_RECORD_NO_SUCH_ENTRY'], \Message::CLASS_ERROR);
            return;
        }
        $entityObj = $em->getClassMetadata($entityWithNS);
        $id = $entityObject[$entityObj->getSingleIdentifierFieldName()]; //get primary key value

        // delete all n associated entries, because the are not longer used and we can delete the main entry only if we
        // have no more n associated entries
        $pageRepo = $em->getRepository($entityWithNS);
        $associationMappings = $entityObj->getAssociationMappings();
        foreach ($associationMappings as $mapping => $value) {
            // we only need to delete the n associated values, the single associated will be handled by doctrine itself
            if (!$entityObj->isCollectionValuedAssociation($mapping)) {
                continue;
            }
            $mainEntity = $pageRepo->find($id);
            $associatedEntities = $mainEntity->{'get'.preg_replace('/_([a-z])/', '\1', ucfirst($mapping))}();
            foreach ($associatedEntities as $associatedEntity) {
                $em->remove($associatedEntity);
            }
        }

        if (!empty($id)) {
            $entityObj = $em->getRepository($entityWithNS)->find($id);
            if (!empty($entityObj)) {
                if ($entityObj instanceof \Cx\Core\Model\Model\Entity\YamlEntity) {
                    $ymlRepo = $em->getRepository($entityWithNS);
                    $ymlRepo->remove($entityObj);;
                    $ymlRepo->flush();
                } else {
                    $em->remove($entityObj);
                    $em->flush();
                }
                \Message::add($_ARRAYLANG['TXT_CORE_RECORD_DELETED_SUCCESSFUL']);
            }
        }
        $actionUrl = clone $cx->getRequest()->getUrl();
        $actionUrl->setParam('deleteid', null);
        \Cx\Core\Csrf\Controller\Csrf::redirect($actionUrl);
    }

    /**
     * Creates a string out of the ViewGenerator object
     *
     * @access public
     * @return string the object ViewGenerator as string
     */
    public function __toString() {
        try {
            return (string) $this->render();
        } catch (\Exception $e) {
            echo $e->getMessage();die();
        }
    }

    /**
     * This function checks if a post request contains any data besides csrf
     *
     * @access protected
     * @global array $_ARRAYLANG array containing the language variables
     * @return bool true if $_POST is empty
     */
    protected function checkBlankPostRequest() {
        global $_ARRAYLANG;

        $post = $_POST;
        unset($post['csrf']);
        $blankPost = true;
        if (!empty($post)) {
            foreach ($post as $value) {
                if ($value) {
                    $blankPost = false;
                }
            }
        }
        if ($blankPost) {
            \Message::add($_ARRAYLANG['TXT_CORE_RECORD_FILL_OUT_AT_LEAST_ONE_FILED'], \Message::CLASS_ERROR);
            return false;
        }
        return true;
    }

    /**
     * This function checks if a form is valid
     *
     * @access protected
     * @global array $_ARRAYLANG array containing the language variables
     * @return boolean true if form is valid
     */
    protected function validateForm() {
        global $_ARRAYLANG;

        if ($this->formGenerator === false) {
            // cannot save, no such entry
            \Message::add($_ARRAYLANG['TXT_CORE_RECORD_NO_SUCH_ENTRY'], \Message::CLASS_ERROR);
            return false;
        } else if (
            !$this->formGenerator->isValid() ||
            (
                isset($this->options['validate']) &&
                !$this->options['validate']($this->formGenerator)
            )
        ) {
            // data validation failed
            \Message::add($_ARRAYLANG['TXT_CORE_RECORD_VALIDATION_FAILED'], \Message::CLASS_ERROR);
            return false;
        }
        return true;

    }

    /**
     * Sets the cancel url for the given param
     *
     * @access protected
     * @param string $parameterName name of the param
     */
    protected function setProperCancelUrl($parameterName){
        if (!isset($this->options['cancelUrl']) || !is_a($this->options['cancelUrl'], 'Cx\Core\Routing\Url')) {
            $this->options['cancelUrl'] = clone \Env::get('cx')->getRequest()->getUrl();
        }
        $this->options['cancelUrl']->setParam($parameterName, null);
    }
    
    /**
     * Adds/sets a foreign entity (1:1 or n:1)
     * 
     * @param string $targetEntity FQCN of foreign entity
     * @param array $criteria Criteria to fetch the entity to set
     * @param object $entity Entity to set foreign entity of
     * @param string $methodName Name of method to set entity
     */
    protected function storeSingleValuedAssociation($targetEntity, $criteria, $entity, $methodName) {
        $association = \Env::get('em')->getRepository($targetEntity)->findOneBy($criteria);
        $entity->$methodName($association);
    }
}
