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
 * RelationRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */
namespace Cx\Core_Modules\Sync\Model\Repository;

/**
 * RelationRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_sync
 */
class RelationRepository extends \Doctrine\ORM\EntityRepository {

    /**
     * Find a relation from a local field
     * @param \Cx\Core_Modules\Sync\Model\Entity\Sync $sync Related Sync
     * @param string $fieldName Local field name
     * @param \Cx\Core_Modules\Sync\Model\Entity\Relation $parentRelationConfig (optional) Parent config
     * @return \Cx\Core_Modules\Sync\Model\Entity\Relation Matching relation or null
     */
    public function findRelationByField($sync, $fieldName, $parentRelationConfig = null) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('r')
            ->from('Cx\Core_Modules\Sync\Model\Entity\Relation', 'r')
            ->andWhere($qb->expr()->eq('r.relatedSync', $sync))
            ->andWhere($qb->expr()->eq('r.localFieldName', $fieldName))
            ->andWhere($qb->expr()->eq('r.parent', $parentRelationConfig));
        
        $query = $qb->getQuery();
        $query->useResultCache(false);
        return $query->getFirstResult();
    }
}

