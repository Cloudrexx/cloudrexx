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
 * Page log repository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */

namespace Cx\Core\ContentManager\Model\Repository;

use Doctrine\Common\Util\Debug as DoctrineDebug;
use Doctrine\ORM\EntityRepository,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Query\Expr;
use Gedmo\Loggable\Entity\Repository\LogEntryRepository;

/**
 * Log entry exception
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */

class LogEntryRepositoryException extends \Exception {};

/**
 * Page log repository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_contentmanager
 */

class PageLogRepository extends LogEntryRepository
{
    // Doctrine entity manager
    protected $em = null;
    // Page repository
    protected $pageRepo = null;

    /**
     * Constructor
     *
     * @param  EntityManager  $em
     * @param  ClassMetadata  $class
     */
    public function __construct(EntityManager $em, ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->em = $em;
        $this->pageRepo = $this->em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
    }

    /**
     * Loads all log entries for the given $entity
     *
     * @param object  $entity   Entity object
     * @param boolean $useCache If true then take entries from cache otherwise from DB
     * @param integer $offset   Offset value
     * @param integer $limit    Entries count
     *
     * @return array
     */
    public function getLogEntries(
        $entity,
        $useCache = true,
        $limit = 0,
        $offset = 0
    ) {
        $q = $this->getLogEntriesQuery($entity);
        $q->useResultCache($useCache);
        if ($limit) {
            $q->setFirstResult($offset);
            $q->setMaxResults($limit);
        }
        return $q->getResult();
    }

    /**
     * Get Log entries count
     *
     * @param object $entity Entity object
     *
     * @return integer
     */
    public function getLogEntriesCount($entity)
    {
        $wrapped = new \Gedmo\Tool\Wrapper\EntityWrapper($entity, $this->_em);
        $objectClass = $wrapped->getMetadata()->name;
        $meta = $this->getClassMetadata();
        $qb   = $this->em->createQueryBuilder();
        $qb->select('log', 'count(log) AS logCount')
            ->from($meta->name, 'log')
            ->where('log.objectId = :objectId')
            ->andWhere('log.objectClass = :objectClass');
        $objectId = $wrapped->getIdentifier();
        $qb->setParameters(array(
            'objectId'    => $objectId,
            'objectClass' => $objectClass
        ));
        $result = $qb->getQuery()->getResult();
        return $result[0]['logCount'];
    }

    /**
     * Returns an array with the log entries of the given action with a limiter for the paging. It is used for the content workflow overview.
     * The log entries are filtered by the page object.
     *
     * @todo Known bug: Paging for action = 'deleted' is wrong
     *
     * @param   string  $action
     * @param   int     $offset
     * @param   int     $limit
     *
     * @return  array   $result
     */
    public function getLogs($action = '', $offset, $limit, &$count = 0)
    {
        $result = array();
        $secondAction = '';
        switch ($action) {
            case 'unvalidated':
                $secondAction = ', :secondAction';
                break;
        }
        $activeLangs = array_keys(
            \FWLanguage::getActiveFrontendLanguages()
        );
        $activeLangSql = implode(
            ',',
            $activeLangs
        );
        $query = '
            SELECT SQL_CALC_FOUND_ROWS
                c0_.object_id AS objectId,
                c0_.action AS action,
                c0_.logged_at AS loggedAt,
                c0_.version AS version,
                c0_.username AS username
            FROM
                ' . DBPREFIX . 'log_entry c0_
            INNER JOIN (
                SELECT
                    MAX(c1_.version) AS version,
                    c1_.object_id AS object_id
                FROM
                    '. DBPREFIX . 'log_entry c1_
                WHERE
                    (c1_.object_class = :objectClass)
                GROUP BY
                    c1_.object_id
            ) c2_
            ON
                c0_.object_id = c2_.object_id AND
                c0_.version = c2_.version
            LEFT JOIN
                ' . DBPREFIX . 'content_page AS c3_
            ON
                c3_.id = c0_.object_id
            WHERE
                (c0_.action IN(:action' . $secondAction . ')) AND
                (c0_.object_class = :objectClass) AND
                (c3_.editingStatus IS NULL OR c3_.editingStatus = :editingStatus) AND
                (c3_.lang IS NULL OR c3_.lang IN(' . $activeLangSql . '))
            ORDER BY
                c0_.logged_at DESC
            LIMIT
                ' . contrexx_raw2db($limit) . '
            OFFSET
                ' . contrexx_raw2db($offset) . '
        ';
        $conn = $this->em->getConnection();
        $stmt = $conn->prepare($query);
        $stmt->bindValue(
            'objectClass',
            'Cx\\Core\\ContentManager\\Model\\Entity\\Page'
        );

        switch ($action) {
            case 'deleted':
                $stmt->bindValue('editingStatus', '');
                $stmt->bindValue('action', 'remove');
                break;
            case 'unvalidated':
                $stmt->bindValue('editingStatus', 'hasDraftWaiting');
                $stmt->bindValue('action', 'create');
                $stmt->bindValue('secondAction', 'update');
                break;
            case 'updated':
                $stmt->bindValue('editingStatus', '');
                $stmt->bindValue('action', 'update');
                break;
            default: // create
                $stmt->bindValue('editingStatus', '');
                $stmt->bindValue('action', 'create');
        }

        $stmt->execute();
        $logs = $stmt->fetchAll();
        $stmt = $conn->prepare('SELECT FOUND_ROWS()');
        $stmt->execute();
        $count = current(current($stmt->fetchAll()));

        switch ($action) {
            case 'deleted':
                // Structure the logs by node id and language
                foreach ($logs as $log) {
                    $page = new \Cx\Core\ContentManager\Model\Entity\Page();
                    $page->setId($log['objectId']);
                    $this->revert($page, $log['version'] - 1);
                    if (!in_array($page->getLang(), $activeLangs)) {
                        continue;
                    }
                    if (!$page->getNodeIdShadowed()) {
                        \DBG::msg('Page #' . $page->getId() . '\'s shadowed node ID is NULL<br />');
                        $result[] = array($page->getLang() => $log);
                    } else {
                        if (!isset($result[$page->getNodeIdShadowed()])) {
                            $result[$page->getNodeIdShadowed()] = array();
                        }
                        $result[$page->getNodeIdShadowed()][$page->getLang()] = $log;
                    }
                }
                break;
            default: // create, update and unvalidated
                $result = $logs;
                break;
        }

        return $result;
    }

    /**
     * Returns an array with the log entries of the given action.
     * The log entries are filtered by the page object.
     *
     * @param   string  $action
     * @return  array   $result
     */
    public function getLogsByAction($action = '')
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('l')
           ->from('Cx\Core\ContentManager\Model\Entity\LogEntry', 'l')
           ->where('l.action = :action')
           ->andWhere('l.objectClass = :objectClass')
           ->setParameter('action', $action)
           ->setParameter('objectClass', 'Cx\Core\ContentManager\Model\Entity\Page');
        $result = $qb->getQuery()->getResult();

        return $result;
    }

    /**
     * Returns the latest logs of all pages.
     * The log entries are filtered by the page object.
     *
     * @return  array  $result
     */
    public function getLatestLog(\Cx\Core\ContentManager\Model\Entity\Page $page) {
        $result = array();

        $qb = $this->em->createQueryBuilder();

        $objectId = $page->getId();

        $qb->select('l')
                ->setMaxResults(1)
                ->from('Cx\Core\ContentManager\Model\Entity\LogEntry', 'l')
                ->where('l.objectClass = :objectClass')
                ->andWhere('l.objectId LIKE :objectId')
                ->orderBy('l.version', 'DESC')
                ->setParameter('objectClass', 'Cx\Core\ContentManager\Model\Entity\Page')
                ->setParameter('objectId', $objectId);

        $logs = $qb->getQuery()->getResult();

        if (is_array($logs)) {
            foreach ($logs as $log) {
                if (!is_array($log)) {
                    $result[$log->getObjectId()] = $log;
                } else {
                    $result[$log['objectId']] = $log['username'];
                }
            }
        }

        return current($result);
    }

    /**
     * Returns the user name from the given log.
     *
     * @param   Cx\Core\ContentManager\Model\Entity\LogEntry
     * @return  string  $username
     */
    public function getUsernameByLog($log)
    {
        if (!is_object($log)) {
            $loggedUser = $log;
        } else {
            $loggedUser = $log->getUsername();
        }
        $user = json_decode($loggedUser);
        $username = $user->{'name'};

        return $username;
    }

}
