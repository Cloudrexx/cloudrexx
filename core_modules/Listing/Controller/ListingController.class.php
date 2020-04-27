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
 * Listing controller
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */

namespace Cx\Core_Modules\Listing\Controller;

/**
 * Listing exception
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */
class ListingException extends \Exception {}

/**
 * Creates rendered lists (paging, filtering, sorting)
 * @author ritt0r <drissg@gmail.com>
 * @package     cloudrexx
 * @subpackage  coremodule_listing
 */
class ListingController {
    /**
     * Turn paging off
     * @var int
     */
    const PAGING_OFF = 0;
    /**
     * No AJAX, traditional page requests
     * @var int
     */
    const PAGING_NON_AJAX = 1;
    /**
     * Paged HTML is transferred via AJAX
     * @var int
     */
    const PAGING_HTML_AJAX = 2;
    /**
     * Only data is transferred via AJAX
     * @var int
     */
    const PAGING_DATA_AJAX = 3;
    /**
     * Paging is done client side, no additional data is transferred after page load
     * @var unknown
     */
    const PAGING_CLIENT_ONLY = 4;
    const SORTING_OFF = 8;
    const SORTING_NON_AJAX = 9;
    const SORTING_HTML_AJAX = 10;
    const SORTING_DATA_AJAX = 11;
    const SORTING_CLIENT_ONLY = 12;
    const FILTERING_OFF = 16;
    const FILTERING_NON_AJAX = 17;
    const FILTERING_HTML_AJAX = 18;
    const FILTERING_DATA_AJAX = 19;
    const FILTERING_CLIENT_ONLY = 20;

    /**
     * How many lists are there for this request
     * @var int
     */
    protected static $listNumber = 0;

    /**
     * Entity class name
     * @var String
     */
    protected $entityClass = null;

    /**
     * Callback function to get data
     * @var Callable
     */
    protected $callback = null;

    /**
     * Offset to start from
     * @var int
     */
    protected $offset = 0;

    /**
     * How many results are returned
     * @var int
     */
    protected $count = 0;

    /**
     * Order by array($field=>asc/desc)
     * @var Array
     */
    protected $order = array();

    /**
     * Criteria the result must match
     * @var Array
     */
    protected $criteria = array();

    /**
     * Filter at least one field of the result must match
     * @var string
     */
    protected $filter = '';


    private $paging;

    /**
     * Entity name
     * @var String
     */
    private $entityName = '';

    /**
     * List of field names that can be filtered
     * @var array
     */
    protected $filterFields = array();

    /**
     * List of field names that can be searched by a term
     * @var array
     */
    protected $searchFields = array();

    /**
     * List with callbacks for search
     * @var array|callable
     */
    protected $searchCallback;

    /**
     * List with callbacks for expanded search
     * @var array|callable
     */
    protected $filterCallback;

    /**
     * Number of entries without filtering or paging
     * @var int
     */
    protected $dataSize = 0;

    /**
     * @var \Cx\Core_Modules\Listing\Model\Entity\DataSet
     */
    protected $data = null;

    /**
     * List all custom field names that are not as a field in the db
     * @var array
     */
    protected $customFields;

    /**
     * Handles a list
     * @param mixed $entities Entity class name as string or callback function (experimental)
     * @param array $crit (optional) Doctrine style criteria array to use
     * @param array $options (Unused)
     */
    public function __construct($entities, $crit = array(), $filter = '', $options = array()) {
        if (isset($options['paging'])) {
            $this->paging = $options['paging'];
        }
        if (isset($options['order'])) {
            $this->order  = $options['order'];
        }
        if (isset($options['sortBy']['entity'])) {
            $this->entityName = $options['sortBy']['entity'];
        }
        $this->filtering = isset($options['filtering']) && $options['filtering'];
        if (isset($options['filterFields'])) {
            $this->filterFields = $options['filterFields'];
        }
        $this->searching = isset($options['searching']) && $options['searching'];
        if (isset($options['searchFields'])) {
            $this->searchFields = $options['searchFields'];
        }
        if (isset($options['searchCallback'])) {
            $this->searchCallback = $options['searchCallback'];
        }
        if (isset($options['filterCallback'])) {
            $this->filterCallback = $options['filterCallback'];
        }
	if (isset($options['customFields'])) {
            $this->customFields = $options['customFields'];
        }
        // init handlers (filtering, paging and sorting)
        $this->handlers[] = new FilteringController();
        if (!empty($options['sorting'])) {
            $this->handlers[] = new SortingController();
        }

        if ($this->paging) {
            $this->handlers[] = new PagingController();
        }

        if (is_callable($entities)) {
            \DBG::msg('Init ListingController using callback function');
            $this->callback = $entities;
        } else if ($entities instanceof \Cx\Core_Modules\Listing\Model\Entity\DataSet) {
            \DBG::msg('Init ListingController using DataSet');
            $this->entityClass = $entities;
        } else {
            \DBG::msg('Init ListingController using entity class');
            $this->entityClass = $entities;
        }
        $this->criteria = $crit;
        $this->filter = $filter;
        $this->options = $options;
    }

    /**
     * Loads the data of an object
     * @param array $args Pass parsed GET params here
     * @param bool $forceRegen (optional) If set to true, cached data is dropped
     * @return Cx\Core_Modules\Listing\Model\DataSet Parsed data
     */
    public function getData($args, $forceRegen = false) {
        if ($this->data && !$forceRegen) {
            return $this->data;
        }
        $params = array(
            'offset'    => $this->offset,
            'count'     => $this->count,
            'order'     => $this->order,
            'criteria'  => $this->criteria,
            'filter'    => $this->filter,
            'entity'    => $this->entityName,
        );
        foreach ($this->handlers as $handler) {
            $params = $handler->handle($params, $args);
        }
        $this->offset   = $params['offset'];
        $this->count    = $params['count'];
        $this->order    = $params['order'];
        $this->criteria = $params['criteria'];
        $this->filter   = $params['filter'];

        // handle ajax requests
        if (false /* ajax request for this listing */) {
            $jd = new \Cx\Core\Json\JsonData();
            // TODO: This does not work yet
            // TODO: JsonData->json() expects a Response object
            $jd->json(array(
                'filtering' => $this->getAjaxFilteringData(),
                'sorting' => $this->getAjaxSortingData(),
                'paging' => $this->getAjaxPagingData(),
            ), true);
            // JsonData->json() does call die() itself
        }

        if ($this->entityClass instanceof \Cx\Core_Modules\Listing\Model\Entity\DataSet) {
            //$data = new \Cx\Core_Modules\Listing\Model\Entity\DataSet();
            $data = $this->entityClass;

            // filter data
            if (is_array($this->criteria) && count($this->criteria)) {
                $data->filter(function($entry) {
                    foreach ($entry as $field=>$data) {
                        if (
                            isset($this->criteria[$field]) &&
                            $this->criteria[$field] != $data
                        ) {
                            return false;
                        }
                    }
                    return true;
                });
            }

            // filter data
            if (!empty($this->filter)) {
                $data->filter(function($entry) {
                    foreach ($entry as $field=>$data) {
                        if (is_int(strpos($data, $this->filter))) {
                            return true;
                        }
                    }
                    return false;
                });
            }

            // sort data
            $data = $data->sort($this->order);

            // limit data
            $this->dataSize = $data->size();
            if ($this->count) {
                $data = $data->limit($this->count, $this->offset);
            }
            // Add custom fields
            foreach ($this->customFields as $customField) {
                $data->addColumn($customField);
            }

            $this->data = $data;
            return $data;
        }
        $em = \Env::get('em');
        if (!$this->callback) {
            $entityRepository = $em->getRepository($this->entityClass);
        }
        foreach ($this->order as $field=>&$order) {
            $order = $order == SORT_DESC ? 'DESC' : 'ASC';
        }

        if ($this->callback) {
            $callback = $this->callback;
            $entities = $callback(
                $this->offset,
                $this->count,
                $this->criteria,
                $this->order
            )->getResult();
            $this->dataSize = $this->count;

        // YAMLRepository:
        } else if ($entityRepository instanceof \Countable) {
            if (!empty($this->filter)) {
                \DBG::msg('YAMLRepository does not support "filter" yet');
            }
            $entities = $entityRepository->findBy(
                $this->criteria,
                $this->order,
                $this->count ? $this->count : null,
                $this->offset
            );
            $this->dataSize = count($entityRepository);
        } else {
            $qb = $em->createQueryBuilder();
            $metaData = $em->getClassMetadata($this->entityClass);
            $qb->select('DISTINCT x')->from($this->entityClass, 'x');
            // filtering: advanced search
            if ($this->filtering) {
                if (
                    is_array($this->filterCallback) &&
                    isset($this->filterCallback['adapter']) &&
                    isset($this->filterCallback['method'])
                ) {
                    $json = new \Cx\Core\Json\JsonData();
                    $jsonResult = $json->data(
                        $this->filterCallback['adapter'],
                        $this->filterCallback['method'],
                        array(
                            'qb' => $qb,
                            'crit' => $this->criteria,
                        )
                    );
                    if ($jsonResult['status'] == 'success') {
                        $qb = $jsonResult['data'];
                    }
                } else if (is_callable($this->filterCallback)) {
                    $filterCallback = $this->filterCallback;
                    $qb = $filterCallback(
                        $qb,
                        $this->criteria
                    );
                } else {
                    $i = 1;
                    foreach ($this->criteria as $field=>$crit) {
                        if (
                            !empty($this->filterFields) &&
                            !in_array($field, $this->filterFields)
                        ) {
                            continue;
                        }
                        if (isset($metaData->associationMappings[$field])) {
                            if (
                                $metaData->associationMappings[$field]['type'] ==
                                \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_MANY
                            ) {
                                $qb->andWhere(
                                     '?' . $i . ' MEMBER OF ' . 'x.' . $field
                                );
                            } else {
                                $qb->andWhere(
                                    $qb->expr()->eq('x.' . $field, '?' . $i)
                                );
                            }
                        } else {
                            $qb->andWhere(
                                $qb->expr()->like('x.' . $field, '?' . $i)
                            );
                        }
                        $qb->setParameter($i, $crit);
                        $i++;
                    }
                }
            }
            // filtering: simple search by term
            if ($this->searching) {
                if (!empty($this->filter) && count($this->searchFields)) {
                    if (
                        is_array($this->searchCallback) &&
                        isset($this->searchCallback['adapter']) &&
                        isset($this->searchCallback['method'])
                    ) {
                        $json = new \Cx\Core\Json\JsonData();
                        $jsonResult = $json->data(
                            $this->searchCallback['adapter'],
                            $this->searchCallback['method'],
                            array(
                                'qb' => $qb,
                                'fields' => $this->searchFields,
                                'crit' => $this->filter
                            )
                        );
                        if ($jsonResult['status'] == 'success') {
                            $qb = $jsonResult['data'];
                        }
                    } else if (is_callable($this->searchCallback)) {
                        $searchCallback = $this->searchCallback;
                        $qb = $searchCallback(
                            $qb,
                            $this->searchFields,
                            $this->filter
                        );
                    } else {
                        $ors = array();
                        $orX = new \Doctrine\DBAL\Query\Expression\CompositeExpression(
                            \Doctrine\DBAL\Query\Expression\CompositeExpression::TYPE_OR
                        );
                        // TODO: If $this->searchFields is empty allow all
                        foreach ($this->searchFields as $field) {
                            $orX->add($qb->expr()->like('x.' . $field, ':term'));
                        }
                        $qb->andWhere($orX);
                        $qb->setParameter('term', '%' . $this->filter . '%');
                    }
                }
            }
            foreach ($this->order as $field=>&$order) {
                // TODO: Since we don't know how related data is presented we
                //       cannot sort by it here. Find a solution for sorting
                //       by relation fields.
                if (isset($metaData->associationMappings[$field])) {
                    continue;
                }
                $qb->orderBy('x.' . $field, $order);
            }
            $qb->setFirstResult($this->offset ? $this->offset : null);
            $qb->setMaxResults($this->count ? $this->count : null);
            $entities = $qb->getQuery()->getResult();

            $metaData = $em->getClassMetaData($this->entityClass);
            $identifierFieldNames = $metaData->getIdentifierFieldNames();
            $identifierFieldNames = reset($identifierFieldNames);
            $qb->select(
                'count(DISTINCT x.' . $identifierFieldNames . ')'
            );
            $qb->setFirstResult(null);
            $qb->setMaxResults(null);
            $this->dataSize = $qb->getQuery()->getSingleScalarResult();
        }

        // return calculated data
        $data = new \Cx\Core_Modules\Listing\Model\Entity\DataSet(
            $entities,
            null,
            $this->options
        );

        // Add custom fields
        foreach ($this->customFields as $customField) {
            $data->addColumn($customField);
        }

        $data->setDataType($this->entityClass);
        $this->data = $data;
        return $data;
    }

    /**
     * Returns the number of entries without filtering or paging
     * This only returns the correct value after getData() is called
     * @return int Number of entries
     */
    public function getDataSize() {
        return $this->dataSize;
    }

    /**
     * @todo: drop
     */
    public function toHtml() {
        return '';
    }

    /**
     * @todo: drop
     */
    public function __toString() {
        return $this->toHtml();
    }
}
