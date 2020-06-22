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
 * NodeRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */

namespace Cx\Core\ContentManager\Model\Repository;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Query,
    Gedmo\Tree\Strategy\ORM\Nested,
    Gedmo\Tree\Entity\Repository\NestedTreeRepository,
    Gedmo\Exception\InvalidArgumentException;

/**
 * NodeRepository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */
class NodeRepository extends NestedTreeRepository {
    protected $em = null;
    const DataProperty = '__data';

    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->em = $em;
    }

    public function find($id, $lockMode = 0, $lockVersion = NULL) {
        return $this->findOneBy(array('id' => $id));
    }

    /**
     * Finds entities by a set of criteria.
     *
     * @param array $criteria
     * @return array
     * @override
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('n')
                ->from('\Cx\Core\ContentManager\Model\Entity\Node', 'n');
        $i = 1;
        foreach ($criteria as $key => $value) {
            if ($i == 1) {
                $qb->where('n.' . $key . ' = ?' . $i)->setParameter($i, $value);
            } else {
                $qb->andWhere('n.' . $key . ' = ?' . $i)->setParameter($i, $value);
            }
            $i++;
        }

        try {
            $q = $qb->getQuery();
            $nodes = $q->getResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $nodes = array();
        }
        return $nodes;
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria, array $orderBy = null)
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('n')
                ->from('\Cx\Core\ContentManager\Model\Entity\Node', 'n')->setMaxResults(1);
        $i = 1;
        foreach ($criteria as $key => $value) {
            if ($i == 1) {
                $qb->where('n.' . $key . ' = ?' . $i)->setParameter($i, $value);
            } else {
                $qb->andWhere('n.' . $key . ' = ?' . $i)->setParameter($i, $value);
            }
            $i++;
        }

        try {
            $q = $qb->getQuery();
            $node = $q->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $node = null;
        }
        return $node;
    }

    /**
     * Returns the root node.
     * @todo DO NOT use NestedTreeRepository->getRootNodes(), it needs a lot of RAM, implement own query to get all root nodes
     * @return \Cx\Core\ContentManager\Model\Entity\Node
     */
    public function getRoot() {
        return $this->findOneBy(array('id'=>1));
    }

    /**
     * Translates a branch of the tree recursively
     * @todo This does only work for root node by now
     * @param \Cx\Core\ContentManager\Model\Entity\Node $rootNode Node to start with (see todo, optional for now)
     * @param int $fromLanguage Language id to copy from
     * @param int $toLanguage Language id to copy to
     * @param boolean $includingContent Wheter to copy content or set type to fallback
     * @param int $limit (optional) How many nodes should be copied, 0 means all, defaults to 0
     * @param int $offset (optional) How many nodes should be skipped, defaults to 0
     * @return array Returns an array with the following structure: array('count'=>{count of nodes}, 'offset'=>{current offset after copy})
     * @throws \Cx\Core\ContentManager\ContentManagerException
     */
    public function translateRecursive($rootNode, $fromLanguage, $toLanguage, $includingContent, $limit = 0, $offset = 0) {
        $nodes = $this->findAll();
        $i = 0;
        foreach ($nodes as $node) {
            if ($i < $offset) {
                $i++;
                continue;
            }
            if ($limit > 0 && ($i - $offset) >= $limit) {
                break;
            }
            $page = $node->getPage($fromLanguage);
            $destPage = $node->getPage($toLanguage);
            if (!$page) {
                // no page in this lang, we don't care
                if ($destPage) {
                    $this->em->remove($destPage);
                    $this->em->flush();
                }
                $i++;
                continue;
            }
            // if the target page exists, we just customize it
            try {
                $pageCopy = $page->copyToLang(
                    $toLanguage,
                    $includingContent,
                    $includingContent, //$includeModuleAndCmd = true,
                    true, //$includeName = true,
                    true, // true, //$includeMetaData = true,
                    true, // true, //$includeProtection = true,
                    true, // includeEditingStatus
                    false, // true, //$followRedirects = false,
                    false, // true //$followFallbacks = false
                    $destPage
                );
            } catch (\Exception $e) {
                throw new \Cx\Core\ContentManager\ContentManagerException('Failed to copy page #' . $page->getId() . '. Error was: ' . $e->getMessage());
            }
            if (!$pageCopy) {
                throw new \Cx\Core\ContentManager\ContentManagerException('Failed to copy page #' . $page->getId());
            }
            $this->em->persist($pageCopy);
            $this->em->flush();
            $i++;
        }
        return array('count'=>count($nodes), 'offset'=>$i);
    }

    /**
     * Tries to recover the tree
     *
     * @throws RuntimeException - if something fails in transaction
     * @return void
     */
    public function recover()
    {
        if ($this->verify() === true) {
            return;
        }
        $left = 1;
        $startNode = $this->findOneBy(array('id'=>1));
        $startNode->setLft($left);
        $this->recoverBranch($startNode, $left);
        return $this->verify();
    }

    /**
     * Tries to recover a branch - assuming that level and left of $rootNode are correct!
     * @param \Cx\Core\ContentManager\Model\Entity\Node $rootNode Node to start with
     */
    private function recoverBranch($rootNode, &$left = null, $level = null)
    {
        if ($left == null) {
            $left = $rootNode->getLft();
        }
        if ($level == null) {
            $level = $rootNode->getLvl();
        }

        // The order in which the children are returned by $rootnode->getChildren() is wrong.
        // Therefore we'll have to manually put the children in the right order.
        // Tote that $children is an object, which is why we have to transform it into an array
        // to be able to using usort() to sort the children.
        $children = $rootNode->getChildren();
        $aChildren = array();
        foreach ($children as $child) {
            $aChildren[] = $child;
        }
        usort($aChildren, function($a, $b) {
            if ($a->getLft() < $b->getLft()) {
                return -1;
            } elseif ($a->getLft() > $b->getLft()) {
                return 1;
            } else {
                return 0;
            }
        });

        $level++;
        foreach ($aChildren as $child) {
            $left++;
            $child->setLft($left);
            $child->setLvl($level);
            $this->recoverBranch($child, $left, $level);
        }
        $left++;
        $rootNode->setRgt($left);
        $this->em->persist($rootNode);
        $this->em->flush();
    }

    /**
     * Get the query for next siblings of the given $node
     *
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     * @return Query
     */
    public function getNextSiblingsQuery($node, $includeSelf = false, $skipAliasNodes = false)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        if (!$this->_em->getUnitOfWork()->isInIdentityMap($node)) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }

        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
        if (!$parent) {
            throw new InvalidArgumentException("Cannot get siblings from tree root node");
        }
        $parentId = current($this->_em->getUnitOfWork()->getEntityIdentifier($parent));

        $left  = $meta->getReflectionProperty($config['left'])->getValue($node);
        $sign  = $includeSelf ? '>=' : '>';
        $page  = $skipAliasNodes ? ', Cx\Core\ContentManager\Model\Entity\Page page' : '';
        $where = $skipAliasNodes ? ' AND node.id = page.node' : '';
        $type  = $skipAliasNodes ? ' AND page.type <> \'alias\'' : '';
        $group = $skipAliasNodes ? ' GROUP BY node.id' : '';

        $dql = "SELECT node FROM {$config['useObjectClass']} node {$page}";
        $dql .= " WHERE node.{$config['parent']} = {$parentId}";
        $dql .= " AND node.{$config['left']} {$sign} {$left}";
        $dql .= $where;
        $dql .= $type;
        $dql .= $group;
        $dql .= " ORDER BY node.{$config['left']} ASC";
        return $this->_em->createQuery($dql);
    }

    /**
     * Find the next siblings of the given $node
     *
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @return array
     */
    public function getNextSiblings($node, $includeSelf = false, $skipAliasNodes = false)
    {
        return $this->getNextSiblingsQuery($node, $includeSelf, $skipAliasNodes)->getResult();
    }

    /**
     * Get query for previous siblings of the given $node
     *
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @throws \Gedmo\Exception\InvalidArgumentException - if input is invalid
     * @return Query
     */
    public function getPrevSiblingsQuery($node, $includeSelf = false, $skipAliasNodes = false)
    {
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        if (!$this->_em->getUnitOfWork()->isInIdentityMap($node)) {
            throw new InvalidArgumentException("Node is not managed by UnitOfWork");
        }

        $config = $this->listener->getConfiguration($this->_em, $meta->name);
        $parent = $meta->getReflectionProperty($config['parent'])->getValue($node);
        if (!$parent) {
            throw new InvalidArgumentException("Cannot get siblings from tree root node");
        }
        $parentId = current($this->_em->getUnitOfWork()->getEntityIdentifier($parent));

        $left  = $meta->getReflectionProperty($config['left'])->getValue($node);
        $sign  = $includeSelf ? '<=' : '<';
        $page  = $skipAliasNodes ? ', Cx\Core\ContentManager\Model\Entity\Page page' : '';
        $where = $skipAliasNodes ? ' AND node.id = page.node' : '';
        $type  = $skipAliasNodes ? ' AND page.type <> \'alias\'' : '';
        $group = $skipAliasNodes ? ' GROUP BY node.id' : '';

        $dql = "SELECT node FROM {$config['useObjectClass']} node {$page}";
        $dql .= " WHERE node.{$config['parent']} = {$parentId}";
        $dql .= " AND node.{$config['left']} {$sign} {$left}";
        $dql .= $where;
        $dql .= $type;
        $dql .= $group;
        $dql .= " ORDER BY node.{$config['left']} ASC";
        return $this->_em->createQuery($dql);
    }

    /**
     * Find the previous siblings of the given $node
     *
     * @param object $node
     * @param bool $includeSelf - include the node itself
     * @return array
     */
    public function getPrevSiblings($node, $includeSelf = false, $skipAliasNodes = false)
    {
        return $this->getPrevSiblingsQuery($node, $includeSelf, $skipAliasNodes)->getResult();
    }

    /**
     * Move the node down in the same level
     *
     * @param object $node
     * @param mixed $number
     *         integer - number of positions to shift
     *         boolean - if "true" - shift till last position
     * @throws RuntimeException - if something fails in transaction
     * @return boolean - true if shifted
     */
    public function moveDown($node, $number = 1, $skipAliasNodes = false)
    {
        $result = false;
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        $this->_em->getConnection()->beginTransaction();
        try {
            $config = $this->listener->getConfiguration($this->_em, $meta->name);
            $nextSiblings = $this->getNextSiblings($node, false, $skipAliasNodes);
            if ($numSiblings = count($nextSiblings)) {
                $result = true;
                if ($number === true) {
                    $number = $numSiblings;
                } elseif ($number > $numSiblings) {
                    $number = $numSiblings;
                }
                $this->listener
                    ->getStrategy($this->_em, $meta->name)
                    ->updateNode($this->_em, $node, $nextSiblings[$number - 1], Nested::NEXT_SIBLING);
            }
            $this->_em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->_em->getConnection()->rollback();
            throw $e;
        }
        return $result;
    }

    /**
     * Move the node up in the same level
     *
     * @param object $node
     * @param mixed $number
     *         integer - number of positions to shift
     *         boolean - true shift till first position
     * @throws RuntimeException - if something fails in transaction
     * @return boolean - true if shifted
     */
    public function moveUp($node, $number = 1, $skipAliasNodes = false)
    {
        $result = false;
        $meta = $this->getClassMetadata();
        if (!$node instanceof $meta->name) {
            throw new InvalidArgumentException("Node is not related to this repository");
        }
        $this->_em->getConnection()->beginTransaction();
        try {
            $config = $this->listener->getConfiguration($this->_em, $meta->name);
            $prevSiblings = array_reverse($this->getPrevSiblings($node, false, $skipAliasNodes));
            if ($numSiblings = count($prevSiblings)) {
                $result = true;
                if ($number === true) {
                    $number = $numSiblings;
                } elseif ($number > $numSiblings) {
                    $number = $numSiblings;
                }
                $this->listener
                    ->getStrategy($this->_em, $meta->name)
                    ->updateNode($this->_em, $node, $prevSiblings[$number - 1], Nested::PREV_SIBLING);
            }
            $this->_em->getConnection()->commit();
        } catch (\Exception $e) {
            $this->_em->getConnection()->rollback();
            throw $e;
        }
        return $result;
    }
}
