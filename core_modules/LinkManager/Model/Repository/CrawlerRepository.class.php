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
 * CrawlerRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */

namespace Cx\Core_Modules\LinkManager\Model\Repository;

/**
 * The class CrawlerRepository for getting the last run details and get all the crawler run details from db
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */
class CrawlerRepository extends \Doctrine\ORM\EntityRepository {

    /**
     * get the last run detail by the language
     *
     * @param integer $lang language id
     *
     * @return object
     */
    public function getLastRunByLang($lang)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('crawler')
            ->from('Cx\Core_Modules\LinkManager\Model\Entity\Crawler', 'crawler')
            ->where("crawler.lang = :lang")
            ->orderBy("crawler.id", "DESC")
            ->getDql();
        $qb->setParameter("lang", $lang)->setMaxResults(1);
        $objResult = $qb->getQuery()->getResult();

        return $objResult[0];
    }

    /**
     * get the last run details
     *
     * @return object
     */
    public function getLatestRunDetails()
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('crawler')
           ->from('Cx\Core_Modules\LinkManager\Model\Entity\Crawler', 'crawler')
           ->where('crawler.runStatus != :runStatus')
           ->orderBy("crawler.id", "DESC")
           ->getDql();
        $qb->setParameter('runStatus', 'running')->setMaxResults(1);

        return $qb->getQuery()->getResult();
    }

    /**
     * get the crawler entry counts
     *
     * @return integer
     */
    public function crawlerEntryCount()
    {
        $objResult = new \Doctrine\Common\Collections\ArrayCollection($this->findAll());

        return $objResult->count();
    }

    /**
     * get the crawler run entries
     *
     * @param integer $pos       position
     * @param integer $pageLimit page limit
     *
     * @return array
     */
    public function getCrawlerRunEntries($pos, $pageLimit)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('crawler')
            ->from('Cx\Core_Modules\LinkManager\Model\Entity\Crawler', 'crawler')
            ->orderBy("crawler.id", "DESC")
            ->getQuery();
        $qb->setFirstResult($pos)->setMaxResults($pageLimit);

        return new \Doctrine\Common\Collections\ArrayCollection($qb->getQuery()->getResult());
    }
}
