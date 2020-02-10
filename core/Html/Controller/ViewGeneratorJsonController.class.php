<?php

/**
 * Contrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Comvation AG 2007-2015
 * @version   Contrexx 4.0
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
 * "Contrexx" is a registered trademark of Comvation AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

/**
 * This file is used for the ViewGeneratorJsonController of the core html
 *
 * @copyright CONTREXX CMS - COMVATION AG
 * @author Adrian Berger <ab@comvation.com>
 * @package contrexx
 * @subpackage core_html
 * @version 1.0.0
 */
namespace Cx\Core\Html\Controller;

/**
 * This class handles all requests to ViewGenerator, which are submitted over ajax
 * This class is also an entity controller and implements JsonApadter
 *
 * @copyright CONTREXX CMS - COMVATION AG
 * @author Adrian Berger <ab@comvation.com>
 * @package contrexx
 * @subpackage core_html
 * @version 1.0.0
 */
class ViewGeneratorJsonController extends \Cx\Core\Core\Model\Entity\Controller implements \Cx\Core\Json\JsonAdapter {


    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods()
    {
        return array(
            'checkWhitelistPermission' => new \Cx\Core_Modules\Access\Model\Entity\Permission(),
            'getViewOverJson' => $this->getSystemComponentController()->getWhitelistPermission(
                'getViewOverJson'
            ),
            'updateOrder' => $this->getSystemComponentController()->getWhitelistPermission(
                'updateOrder'
            ),
            'updateStatus' => $this->getSystemComponentController()->getWhitelistPermission(
                'updateStatus'
            ),
            'export' => $this->getSystemComponentController()->getWhitelistPermission(
                'export'
            ),
        );
    }

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName()
    {
        return parent::getName();
    }

    /**
     * Returns all messages as string
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString()
    {
        return '';
    }

    /**
     * Returns default permission as object
     * @return Object
     */
    public function getDefaultPermissions()
    {
        return null;
    }

    /**
     * Always returns the add view of an entity
     * This is mostly used for oneToMany associations to load the associated entity (in a model)
     *
     * @access public
     * @param array $params data from ajax request
     * @return json rendered form
     */
    public function getViewOverJson($params)
    {
        $entityClass = $params['get']['entityClass'];
        $entityClassObject = new $entityClass();
        $mappedBy = $params['get']['mappedBy'];
        $options = $_SESSION['vgOptions'][$params['get']['sessionKey']];

        // if the option 'add' is not true, there is no possibility to open the modal, because we are not allowed to add
        // an entry. That's why we set add to true here
        $options->recursiveOffsetSet(true, $entityClass.'/functions/add');

        // formButtons should not be set over ViewGenerator, because they are set over modal (js) and we do not want to
        // load them twice. Furthermore there should be no save button, because the entry should be saved if the main
        // for gets stored and not in the modal.
        $options->recursiveOffsetSet(false, $entityClass.'/functions/formButtons');

        // We never show the mapped-attribute, because it should not be possible to change this.
        // The value of this field must always be the id of the main form entry.
        // This will automatically be done by ViewGenerator while saving the main entry
        $options->recursiveOffsetSet(false, $entityClass.'/fields/'.$mappedBy.'/showDetail');


        $entityClassObjectView = new \Cx\Core\Html\Controller\ViewGenerator(
            $entityClassObject,
            $options->toArray() // must be array and not recursiveArrayAccess object
        );
        return $entityClassObjectView->render();
    }

    /**
     * Update the sort order in DB
     *
     * @param array $params supplied arguments from JsonData-request
     *
     * @return array it contains status and record count
     * @throws \Exception
     */
    public function updateOrder($params) {
        global $_ARRAYLANG, $objInit;

        //get the language interface text
        $langData   = $objInit->loadLanguageData($this->getName());
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);

        $post = is_array($params['post']) ? $params['post'] : array();
        if (    empty($post)
            ||  empty($post['prePosition'])
            ||  empty($post['curPosition'])
            ||  empty($post['sortField'])
            ||  empty($post['component'])
            ||  empty($post['entity'])
        ) {
            throw new \Exception($_ARRAYLANG['TXT_CORE_HTML_UPDATE_SORT_ORDER_FAILED']);
        }

        //Get all the 'POST' values
        $sortField       = !empty($post['sortField'])
                           ? contrexx_input2raw($post['sortField'])
                           : '';
        $sortOrder       = !empty($post['sortOrder'])
                           ? contrexx_input2raw($post['sortOrder'])
                           : '';
        $componentName   = !empty($post['component'])
                           ? contrexx_input2raw($post['component'])
                           : '';
        $entityName      = !empty($post['entity'])
                           ? contrexx_input2raw($post['entity'])
                           : '';
        $pagingPosition  = !empty($post['pagingPosition'])
                           ? contrexx_input2int($post['pagingPosition'])
                           : 0;
        $currentPosition = isset($post['curPosition'])
                           ? contrexx_input2int($post['curPosition'])
                           : 0;
        $prePosition     = isset($post['prePosition'])
                           ? contrexx_input2int($post['prePosition'])
                           : 0;
        $updatedOrder    = (    isset($post['sorting'.$entityName])
                            &&  is_array($post['sorting'.$entityName])
                           )
                           ? array_map('contrexx_input2int', $post['sorting'.$entityName])
                           : array();

        $em = $this->cx->getDb()->getEntityManager();
        $componentRepo   = $em->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $objComponent    = $componentRepo->findOneBy(array('name' => $componentName));
        $entityNameSpace = $objComponent->getNamespace() . '\\Model\\Entity\\' . $entityName;
        //check whether the entity namespace is a valid one or not
        if (!in_array($entityNameSpace, $objComponent->getEntityClasses())) {
            throw new \Exception(
                sprintf(
                    $_ARRAYLANG['TXT_CORE_HTML_ENTITY_NOT_FOUND_ERROR'],
                    $entityName,
                    $componentName
                )
            );
        }

        $entityObject = $em->getClassMetadata($entityNameSpace);
        $classMethods = get_class_methods($entityObject->newInstance());
        $primaryKeyName = $entityObject->getSingleIdentifierFieldName();
        //check whether the updating entity set/get method is a valid one or not
        if (    !in_array('set'.ucfirst($sortField), $classMethods)
            ||  !in_array('get'.ucfirst($sortField), $classMethods)
            ||  !in_array('get'.ucfirst($primaryKeyName), $classMethods)
        ) {
            throw new \Exception(
                sprintf(
                    $_ARRAYLANG['TXT_CORE_HTML_GETTER_SETTER_NOT_FOUND_ERROR'],
                    $entityName,
                    $sortField,
                    $primaryKeyName
                )
            );
        }

        //update the entities order field in DB
        $oldPosition = $pagingPosition + $prePosition;
        $newPosition = $pagingPosition + $currentPosition;

        $min = min(array($newPosition, $oldPosition));
        $max = max(array($newPosition, $oldPosition));

        $offset = $min - 1;
        $limit  = ($max - $min) + 1;

        $qb = $em->createQueryBuilder();
        $qb ->select('e')
            ->from($entityNameSpace, 'e')
            ->orderBy('e.' . $sortField, $sortOrder);
        if (empty($updatedOrder)) {
            $qb->setFirstResult($offset)
               ->setMaxResults($limit);
        }
        $entities = $qb->getQuery()->getResult();

        if (!$entities) {
            throw new \Exception($_ARRAYLANG['TXT_CORE_HTML_SORTING_NO_ENTITY_FOUND_ERROR']);
        }

        try {
            if (    ($oldPosition > $newPosition && empty($updatedOrder))
                ||  (!empty($updatedOrder) && $sortOrder == 'DESC')
            ) {
                krsort($entities);
            }
            $i = 1;
            $recordCount = count($entities);
            $orderFieldSetMethodName = 'set'.ucfirst($sortField);
            $orderFieldGetMethodName = 'get'.ucfirst($sortField);
            $primaryGetMethodName    = 'get'.ucfirst($primaryKeyName);
            foreach ($entities as $entity) {
                if (!empty($updatedOrder)) {
                    //If the same 'order' field value is repeated,
                    //we need to update all the entries.
                    $id = $entity->$primaryGetMethodName();
                    if (in_array($id, $updatedOrder)) {
                        $order   = array_search($id, $updatedOrder);
                        $orderNo = $pagingPosition + $order + 1;
                        if ($sortOrder == 'DESC') {
                            $orderNo = $recordCount - ($pagingPosition + $order);
                        }
                        $entity->$orderFieldSetMethodName($orderNo);
                    } else {
                        $entity->$orderFieldSetMethodName($i);
                    }
                } else {
                    //If the same 'order' field value is not repeated,
                    //we need to update all the entries between dragged and dropped position
                    $currentOrder = $entity->$orderFieldGetMethodName();
                    if ($i == 1) {
                        $firstResult = $entity;
                        $sortOrder   = $currentOrder;
                        $i++;
                        continue;
                    } else if ($i == count($entities)) {
                        $firstResult->$orderFieldSetMethodName($currentOrder);
                        $entity->$orderFieldSetMethodName($sortOrder);
                        $em->persist($entity);
                        continue;
                    }
                    $entity->$orderFieldSetMethodName($sortOrder);
                    $sortOrder = $currentOrder;
                }
                $em->persist($entity);
                $i++;
            }
            $em->flush();
            return array('status' => 'success', 'recordCount' => $recordCount);
        } catch (\Exception $e) {
            throw new \Exception($_ARRAYLANG['TXT_CORE_HTML_UPDATE_SORT_ORDER_FAILED']);
        }
    }

    /**
     * Update the status of an entity attribute in DB
     *
     * @param array $params supplied arguments from JsonData-request
     *
     * @throws \Exception if vars are empty. They have to be defined.
     * @throws \Exception if entity not found. Entity is needed to store status.
     * @throws \Exception if setter for status not found
     */
    public function updateStatus($params)
    {
        global $_ARRAYLANG, $objInit;

        //get the language interface text
        $langData   = $objInit->getComponentSpecificLanguageData(
            $this->getName(),
            false
        );
        $_ARRAYLANG = array_merge($_ARRAYLANG, $langData);

        $post = is_array($params['post']) ? $params['post'] : array();

        if (
            empty($post) ||
            !isset($post['entityId']) ||
            !isset($post['newStatus']) ||
            !isset($post['statusField']) ||
            !isset($post['component']) ||
            !isset($post['entity'])
        ) {
            throw new \Exception(
                $_ARRAYLANG['TXT_CORE_HTML_UPDATE_STATUS_ORDER_FAILED']
            );
        }

        //Get all the 'POST' values
        $componentName   = !empty($post['component'])
            ? contrexx_input2raw($post['component'])
            : '';
        $entityName      = !empty($post['entity'])
            ? contrexx_input2raw($post['entity'])
            : '';
        $entityId  = !empty($post['entityId'])
            ? contrexx_input2int($post['entityId'])
            : 0;
        $newStatus  = !empty($post['newStatus'])
            ? contrexx_input2int($post['newStatus'])
            : 0;
        $statusField = !empty($post['statusField'])
            ? contrexx_input2raw($post['statusField'])
            : '';

        $em = $this->cx->getDb()->getEntityManager();
        $componentRepo   = $em->getRepository(
            'Cx\Core\Core\Model\Entity\SystemComponent'
        );
        $objComponent    = $componentRepo->findOneBy(
            array('name' => $componentName)
        );
        $entityNameSpace = $objComponent->getNamespace() . '\\Model\\Entity\\'
            . $entityName;

        //check whether the entity namespace is a valid one or not
        if (!in_array($entityNameSpace, $objComponent->getEntityClasses())) {
            throw new \Exception(
                sprintf(
                    $_ARRAYLANG['TXT_CORE_HTML_ENTITY_NOT_FOUND_ERROR'],
                    $entityName,
                    $componentName
                )
            );
        }
        $entity = $em->getRepository($entityNameSpace)->find($entityId);
        if (!$entity) {
            throw new \Exception($_ARRAYLANG['TXT_CORE_HTML_STATUS_NO_ENTITY_FOUND_ERROR']);
        }
        $entityObject = $em->getClassMetadata($entityNameSpace);
        $classMethods = get_class_methods($entityObject->newInstance());
        //check whether the updating entity set/get method is a valid one or not

        $setter = 'set'. \Doctrine\Common\Inflector\Inflector::classify(
            $statusField
        );
        if (!in_array($setter, $classMethods)) {
            throw new \Exception(
                sprintf(
                    $_ARRAYLANG['TXT_CORE_HTML_GETTER_SETTER_NOT_FOUND_ERROR'],
                    $entityName,
                    $statusField
                )
            );
        }

        $entity->$setter($newStatus);
        $em->persist($entity);
        $em->flush();

    }

    /**
     * Exports Doctrine entities to a file
     * @param array $params The following get params are allowed:
     *      type    required    string  Doctrine entity name
     *      search  optional    VGparam Filters
     *      term    optional    VGparam Search term
     * @todo This should trigger an async job
     * @todo ViewGenerator config should be taken into account (exclude fields)
     * @return array Generated file name
     */
    public function export($params) {
        if (!isset($params['get']['type'])) {
            throw new \Exception('No type supplied');
        }
        // need to security-check type as its used as part of the filename
        if (!preg_match('/^[A-Za-z0-9_\\\\]+$/', $params['get']['type'])) {
            throw new \Exception('Illegal type name');
        }
        // apply filters
        $filter = array();
        if (isset($params['get']['search'])) {
            $filter = \Cx\Core\Html\Controller\ViewGenerator::getParam(0, $params['get']['search']);
        }
        $search = '';
        if (isset($params['get']['term'])) {
            $search = \Cx\Core\Html\Controller\ViewGenerator::getParam(0, $params['get']['term']);
        }
        $lc = new \Cx\Core_Modules\Listing\Controller\ListingController(
            $params['get']['type'],
            $filter,
            $search,
            array(
                'searching' => true,
                'filtering' => true,
            )
        );
        $ds = $lc->getData();
        $file = $this->getComponent('Core')->getPublicUserTempFolder();
        $file .= end(explode('\\', $params['get']['type'])) . '_Export_';
        $file .= date(ASCMS_DATE_FORMAT_INTERNATIONAL_DATE) . '_';
        $file .= date(ASCMS_DATE_FORMAT_INTERNATIONAL_TIME) . '.csv';
        $ds->exportToFile(
            new \Cx\core_modules\Listing\Model\Entity\CsvInterface(),
            $file
        );
        return str_replace($this->cx->getCodeBasePath(), '', $file);
    }

    /**
     * Checks whether the supplied request info is allowed by the corresponding
     * whitelist
     *
     * This method returns true if all post and get variables specified in a
     * whitelist entry are set and have the same value as specified in the list.
     * $arguments can either be
     *      array(
     *          'get' => <getArgs>
     *          'post' => <postArgs>
     *      )
     * or
     *      array(
     *          'get' => array(
     *              'get' => <getArgs>
     *              'post' => <postArgs>
     *          )
     *      )
     * This is because JsonAdapter method nesting currently leads to param
     * nesting. <getArgs> needs to have index 0 set to the whitelist identifier.
     * The second form is deprecated and should only be used in order to
     * circumvent the problem described above.
     * @param array $arguments Request info, see method description for more info
     * @return boolean Returns true if request info is allowed by whitelist
     */
    public function checkWhitelistPermission($arguments) {
        if (!isset($arguments['get']) || !isset($arguments['get'][0])) {
            return false;
        }
        $getArgs = $arguments['get'];
        $postArgs = array();
        if (isset($arguments['post'])) {
            $postArgs = $arguments['post'];
        }

        // begin workaround (see docblock)
        if (count($getArgs) == 3 && isset($getArgs['get']) && isset($getArgs['post'])) {
            $postArgs = $getArgs['post'];
            $getArgs = $getArgs['get'];
        }

        // method is always set in the first form
        $method = $arguments['get'][0];
        // end workaround

        // initialize session and check if any matching whitelists exist
        $this->getComponent('Session')->getSession();
        if (
            !isset($_SESSION['vg']) ||
            !isset($_SESSION['vg']['whitelist']) ||
            !isset($_SESSION['vg']['whitelist'][$method])
        ) {
            return false;
        }

        // check matching whitelists
        $permissionSets = $_SESSION['vg']['whitelist'][$method];
        foreach ($permissionSets as $permissionSet) {
            foreach ($permissionSet['get'] as $field=>$value) {
                if (!isset($getArgs[$field]) || $getArgs[$field] != $value) {
                    continue 2;
                }
            }
            foreach ($permissionSet['post'] as $field=>$value) {
                if (!isset($postArgs[$field]) || $postArgs[$field] != $value) {
                    continue 2;
                }
            }
            return true;
        }
        return false;
    }
}
