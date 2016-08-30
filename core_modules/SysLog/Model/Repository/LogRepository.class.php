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
 * Class Log Repository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_syslog
 */

namespace Cx\Core_Modules\SysLog\Model\Repository;

/**
 * Class Log Repository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_syslog
 */
class LogRepository extends \Doctrine\ORM\EntityRepository {

    /**
     * Find Latest Sys Log Entry by its logger
     *
     * @param string  $logger logger
     * @param integer $offset offset value
     * @param integer $limit  limit value
     * @return array
     */
    public function findLatestLogEntryByLogger($logger, $offset = 0, $limit = 1) {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb->select('Log')
                ->from('\Cx\Core_Modules\SysLog\Model\Entity\Log', 'Log')
                ->where('Log.logger = :logger')
                ->orderBy('Log.timestamp', 'DESC')
                ->getDql();
        $qb->setParameter('logger', $logger);
        $qb->setFirstResult($offset)->setMaxResults($limit);

        return $qb->getQuery()->getResult();

    }
}
