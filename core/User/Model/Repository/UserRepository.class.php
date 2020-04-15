<?php
/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2020
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
 * Repository for users
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
namespace Cx\Core\User\Model\Repository;

/**
 * Repository for users
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_user
 */
class UserRepository extends \Doctrine\ORM\EntityRepository
{

    /**
     * Register a successful login.
     *
     * @param \Cx\Core\User\Model\Entity\User $user
     * @throws \Cx\Core\Event\Controller\EventManagerException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function registerSuccessfulLogin($user)
    {
        $this->updateLastAuthTime($user);

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();

        // Flush all cache attached to the current session.
        // This is required as after the sign-in, the user might have a
        // greater access level which provides access to more or different
        // content.
        $cx->getComponent('Cache')->clearUserBasedPageCache(session_id());
        $cx->getComponent('Cache')->clearUserBasedEsiCache(session_id());

        // flush access block widgets (currently signed-in users, etc.)
        $cx->getEvents()->triggerEvent(
            'clearEsiCache',
            array(
                'Widget',
                $cx->getComponent('Access')->getSessionBasedWidgetNames(),
            )
        );

        $user->setLastAuthStatus(1);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Update last auth time of user
     *
     * @param \Cx\Core\User\Model\Entity\User $user
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function updateLastAuthTime($user)
    {
        // destroy expired auth token
        if ($user->getAuthTokenTimeout() < time()) {
            $user->setAuthToken('');
            $user->setAuthTokenTimeout(0);
        }

        // update authentication time
        $user->setLastAuth(time());

        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Find users by group ids
     *
     * @param array $groupIds    group ids to filter users
     * @param array $otherFilter other filters to filter for users
     * @return array user result
     */
    public function findByGroup($groupIds, $otherFilter)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->join('u.groups', 'g')
           ->where($qb->expr()->in('g.id', ':groupIds'))
           ->setParameter('groupIds', $groupIds);

        foreach ($otherFilter as $field=>$value) {
            $qb->andWhere($qb->expr()->eq('u.' . $field, ':value' . $field))
               ->setParameter('value'. $field, $value);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds User entities by a set of filters with a specified pattern
     *
     * @param array $filter Set of filters for filtering users
     * @return \Doctrine\Common\Collections\ArrayCollection found users
     */
    public function findByLike($filter)
    {
        $qb = $this->createQueryBuilder('u');
        foreach ($filter as $key=>$value) {
            $qb->andWhere($qb->expr()->like('u'.$key, ':value'. $key))->setParameter('value'.$key, $value);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Search specific fields that start with a defined first letter to get the associated users
     *
     * @param string $letter initial letter
     * @param array  $fields fields to be searched
     * @return array matching users
     */
    public function searchByInitialLetter($letter, $fields)
    {
        $letter .= '%';
        return $this->search($letter, $fields, 'like');
    }

    /**
     * Search in specific fields and return matching users
     *
     * @param string $term   the search term
     * @param array  $fields fields to be searched
     * @return array matching users
     */
    public function searchByTerm($term, $fields)
    {
        return $this->search($term, $fields, 'eq');
    }

    /**
     * Search user attributes and UserAttributes for a search term and return the matching users.
     * You can specify which operation should be used to search the attributes / UserAttributes.
     *
     * @param string $searchTerm the search term
     * @param array  $fields     fields to be searched
     * @param string $operation  operation to search the attributes / UserAttributes
     * @return array matching users
     */
    protected function search($searchTerm, $fields, $operation)
    {
        $metaData = $this->getClassMetadata()->fieldNames;
        $fwAttribute = \FWUser::getFWUserObject()->objUser->objAttribute;
        $qb = $this->createQueryBuilder('u');
        $attributeIds = array();

        foreach ($fields as $field) {
            if (in_array($field, $metaData)) {
                // User
                $qb->orWhere($this->getExpression('u.'. $field, $operation));
                $qb->setParameter('valueu'.$field, $searchTerm);
            } else {

                // UserAttributeValue
                // Find attribute id from default attribute
                if ($fwAttribute->isDefaultAttribute($field)) {
                    $attributeIds[] = $fwAttribute->getAttributeIdByDefaultAttributeId($field);
                } else {
                    $attributeIds[] = $field;
                }
            }
        }

        if (!empty($attributeIds)) {
            $qb->join('u.userAttributeValues', 'v');
            $qb->orWhere(
                $qb->expr()->andX(
                    $this->getExpression('v.userAttribute', 'in'),
                    $this->getExpression('v.value', $operation)
                )
            );
            $qb->setParameter('valuevuserAttribute', $attributeIds);
            $qb->setParameter('valuevvalue', $searchTerm);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get expression by its operation
     *
     * @param string $field     field name
     * @param string $operation operation to use
     * @return \Doctrine\ORM\Query\Expr\Comparison
     */
    protected function getExpression($field, $operation = 'eq')
    {
        $qb = $this->createQueryBuilder('e');
        $valueName = preg_replace('/\./', '', 'value'.$field);
        switch ($operation) {
            case 'like':
                $expr = $qb->expr()->like($field, ':'.$valueName);
                break;
            case 'in':
                $expr = $qb->expr()->in($field, ':'.$valueName);
                break;
            default:
                $expr = $qb->expr()->eq($field, ':'.$valueName);
        }

        return $expr;
    }

    /**
     * Add a group filter to the given QueryBuilder
     *
     * @param \Doctrine\ORM\QueryBuilder $qb     QueryBuilder instance
     * @param int|array                  $groupId Group IDs to be filtered
     */
    public function addGroupFilterToQueryBuilder($qb, $groupId)
    {
        if (!in_array('filterGroup', $qb->getRootAliases())) {
            $qb->leftJoin('u.groups', 'filterGroup');
        }

        if (empty($groupId)) {
            $qb->andWhere(
                $qb->expr()->isNull('filterGroup.id')
            );
        } else if (is_array($groupId)) {
            $qb->andWhere(
                $qb->expr()->in('filterGroup.id', ':groupIds')
            )->setParameter('groupId', $groupId);
        } else {
            $qb->andWhere(
                $qb->expr()->eq('filterGroup.id', ':groupId')
            )->setParameter('groupId', $groupId);
        }
    }

    /**
     * Add a regex filter to the given QueryBuilder. For example for an letter index search
     *
     * @param \Doctrine\ORM\QueryBuilder $qb QueryBuilder instance
     * @param string                     $regex regex to filter
     * @param string                     $field field to be filtered with alias prefix (u.username)
     */
    public function addRegexFilterToQueryBuilder($qb, $regex, $field)
    {
        $qb->andWhere(
            $qb->expr()->eq(
                'REGEXP('.$field.', \''.$regex.'\')',
                1
            )
        );
    }

    /**
     * Add a regex filter on attributes to the given QueryBuilder.
     *
     * @param \Doctrine\ORM\QueryBuilder $qb QueryBuilder instance
     * @param string                     $regex regex to filter
     * @param string                     $field field to be filtered (title | 1)
     */
    public function addAttributeRegexFilterToQueryBuilder($qb, $regex, $field)
    {
        $objAttr = \FWUser::getFWUserObject()->objUser->objAttribute;

        if ($objAttr->isDefaultAttribute($field)) {
            $field = $objAttr->getAttributeIdByDefaultAttributeId($field);
        }

        $qb->join('u.userAttributeValues', 'v'.$field);

        $qb->andWhere($qb->expr()->eq('v'.$field.'.userAttribute', ':userAttribute'.$field));
        $qb->andWhere($qb->expr()->eq('REGEXP(v'.$field.'.value, \''.$regex.'\')', 1));
        $qb->setParameter('userAttribute'.$field, $field);
    }

    /**
     * Add an order to the given QueryBuilder
     *
     * @param \Doctrine\ORM\QueryBuilder $qb QueryBuilder instance
     * @param string                     $field field to be filtered
     * @param string                     $direction asc or desc
     */
    public function addOrderToQueryBuilder($qb, $field, $direction)
    {
        $objAttr = \FWUser::getFWUserObject()->objUser->objAttribute;

        if ($objAttr->isDefaultAttribute($field)) {
            $field = $objAttr->getAttributeIdByDefaultAttributeId($field);
        }

        if (is_int($field)) {
            // Is UserAttributeValue
            if (!in_array('orderAttributeValue', $qb->getAllAliases())) {
                $qb->join('u.userAttributeValues', 'orderAttributeValues');
            }
        } else {
            // Is attribute of an user
            $qb->addOrderBy('u.' . $field, $direction);
        }
    }

    /**
     * Get a expression to filter users
     *
     * @param \Doctrine\ORM\QueryBuilder $qb     QueryBuilder instance
     * @param array                      $filters filter conditions
     * @param bool                       $and     use a and condition or an or condition
     * @return \Doctrine\ORM\Query\Expr\Orx|\Doctrine\ORM\Query\Expr\Andx
     */
    public function getAttributeFilterExpression($qb, $filters, $and = true)
    {
        $objAttr = \FWUser::getFWUserObject()->objUser->objAttribute;

        if ($and) {
            $expr = $qb->expr()->andX();
        } else {
            $expr = $qb->expr()->orX();
        }

        foreach ($filters as $key=>$value) {
            if ($objAttr->isDefaultAttribute($key)) {
                $key = $objAttr->getAttributeIdByDefaultAttributeId($key);
            }

            // Join table if not already done
            $alias = 'v'.$key;
            if (!in_array($alias, $qb->getAllAliases())) {
                $qb->join('u.userAttributeValues', $alias);
            }

            $expr->add($this->getExpression($alias.'.'.$key, 'eq'));
            $qb->setParameter($alias.$key, $value);
        }
        return $expr;
    }

    /**
     * Get an expression to search for one or more search terms in the user username (and email only backend)
     *
     * Matches single (scalar) or multiple (array) search terms against a
     * number of fields.  Generally, the term(s) are enclosed in percent
     * signs ("%term%"), so any fields that contain them will match.
     * However, if the search parameter is a string and does contain a percent
     * sign already, none will be added to the query.
     * This allows searches using custom patterns, like "fields beginning
     * with "a" ("a%").
     *
     * @param \Doctrine\ORM\QueryBuilder $qb  QueryBuilder instance
     * @param string | array             $term one or multiple search terms
     * @return \Doctrine\ORM\Query\Expr\Orx
     */
    public function getSearchByTermInUserExpression($qb, $term)
    {
        $expr = new \Doctrine\ORM\Query\Expr();
        $orX = $expr->orX();
        $fWUser = \FWUser::getFWUserObject();
        $attributes = array('username');
        if ($fWUser->isBackendMode()) {
            $attributes[] = 'email';
        }

        $percent = '%';
        if (!is_array($term) && strpos('%', $term) !== false) {
            $percent = '';
        }

        foreach ($attributes as $attribute) {
            if (!is_array($term)) {
                $term = array($term);
            }
            $i = 0;
            foreach ($term as $searchTerm) {
                $orX->add(
                    $expr->like('u.'.$attribute, ':search' . $attribute . $i)
                );
                $qb->setParameter('search' . $attribute . $i, $percent . $searchTerm . $percent);
            }
        }

        return $orX;
    }

    /**
     * Get an expression to search for one or more search terms in the user attribute values.
     *
     * Matches single (scalar) or multiple (array) search terms against a
     * number of fields.  Generally, the term(s) are enclosed in percent
     * signs ("%term%"), so any fields that contain them will match.
     * However, if the search parameter is a string and does contain a percent
     * sign already, none will be added to the query.
     * This allows searches using custom patterns, like "fields beginning
     * with "a" ("a%").
     *
     * @param \Doctrine\ORM\QueryBuilder $qb  QueryBuilder instance
     * @param string | array             $term one or multiple search terms
     * @return \Doctrine\ORM\Query\Expr\Orx
     */
    public function getSearchByTermInAttributeExpression($qb, $term)
    {
        $alias = 'searchAttributeValues';
        $expr = $qb->expr();
        $orX = $expr->orX();
        $supportedAttributes = array('int' => array(), 'string' => array());
        $attributes = $this->_em->getRepository('Cx\Core\User\Model\Entity\UserAttribute')->findAllWithoutParent();

        foreach ($attributes as $attribute) {
            // do not allow lookup on attributes the user has no read access to
            if (!$attribute->hasReadPermission()) {
                continue;
            }

            switch ($attribute->getType()) {
                case 'text':
                case 'mail':
                case 'uri':
                case 'image':
                    if ($attribute->getDataType() == 'int') {
                        $supportedAttributes['int'][] = $attribute->getId();
                    } else if ($attribute->getDataType() == 'string') {
                        $supportedAttributes['string'][] = $attribute->getId();
                    }
                    break;
                case 'menu':
                    if ($attribute->getName() == 'country') {
                        // No country support
                        break;
                    }

                    // Do not search for user attribute value, search in the attribute names
                    $childAttributeIds = array();
                    foreach ($term as $searchTerm) {
                        foreach ($attribute->getChildren() as $child) {
                            foreach ($child->getUserAttributeNames() as $attributeName) {
                                $name = $attributeName->getName();
                                if (stripos($name, $searchTerm) !== FALSE) {
                                    // We found the attribute
                                    $childAttributeIds[] = $child->getId();
                                    continue;
                                }
                            }
                        }
                    }

                    // No attributes found
                    if (empty($childAttributeIds)) {
                        continue;
                    }

                    $childAttributeIds = array_unique($childAttributeIds);

                    $orX->add(
                        $expr->andX(
                            $expr->eq('searchAttributeValues.userAttribute', ':menuAttribute'.$attribute->getId()),
                            $expr->in('searchAttributeValues.value', ':childValues'.$attribute->getId())
                        )
                    );
                    $qb->setParameter('menuAttribute'.$attribute->getId(), $attribute->getId());
                    $qb->setParameter('childValues'.$attribute->getId(), $childAttributeIds);
                break;
            }
        }

        if (!empty($supportedAttributes)) {
            if (!is_array($term)) {
                $term = array($term);
            }

            $i = 0;
            $valueOrX = $expr->orX();
            foreach ($term as $searchTerm) {
                $parameterAlias = 'search'.$i;
                if (!empty($supportedAttributes['int']) && is_numeric($searchTerm)) {
                    $valueOrX->add(
                        $expr->eq($alias.'.value', ':'.$parameterAlias)
                    );
                    $qb->setParameter($parameterAlias, intval($searchTerm));
                }

                if (!empty($supportedAttributes['string'])) {
                    if (strpos('%', $searchTerm) === false) {
                        $searchTerm = '%'.$searchTerm.'%';
                    }
                    $valueOrX->add(
                        $expr->like($alias.'.value', ':'.$parameterAlias)
                    );
                    $qb->setParameter($parameterAlias, $searchTerm);
                }

                $i++;
            }

            $allAttributeIds = array_merge($supportedAttributes['int'], $supportedAttributes['string']);

            $orX->add(
                $expr->andX(
                    $expr->in('searchAttributeValues.userAttribute', ':searchAttributeIds'),
                    $valueOrX
                )
            );
            $qb->setParameter('searchAttributeIds', $allAttributeIds);
        }


        $qb->join('u.userAttributeValues', 'searchAttributeValues');

        return $orX;
    }
}