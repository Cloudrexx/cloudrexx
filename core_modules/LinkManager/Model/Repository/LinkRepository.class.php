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
 * LinkRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */

namespace Cx\Core_Modules\LinkManager\Model\Repository;

/**
 * LinkRepository
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_linkmanager
 */
class LinkRepository extends \Doctrine\ORM\EntityRepository {

    /**
     * Get all the broken links
     *
     * @param integer $pos
     * @param integer $pageLimit
     *
     * @return array
     */
    public function getBrokenLinks($pos, $pageLimit)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('link')
           ->from('Cx\Core_Modules\LinkManager\Model\Entity\Link', 'link')
           ->where('link.flagStatus = :flagStatus')
           ->orderBy('link.id', 'DESC')
           ->getDql();
        $qb->setParameter('flagStatus', 0)->setFirstResult($pos)->setMaxResults($pageLimit);

        return new \Doctrine\Common\Collections\ArrayCollection($qb->getQuery()->getResult());
    }

    /**
     * get the broken links count
     *
     * @return integer
     */
    public function brokenLinkCount()
    {
        $objResult = new \Doctrine\Common\Collections\ArrayCollection($this->findBy(array('flagStatus' => 0)));

        return $objResult->count();
    }

    /**
     * get the broken links count by language
     *
     * @return integer
     */
    public function brokenLinkCountByLang($lang)
    {
        $objResult = new \Doctrine\Common\Collections\ArrayCollection($this->findBy(array('lang' => $lang, 'flagStatus' => 0)));

        return $objResult->count();
    }

    /**
     * get the selected links
     *
     * @param array $ids
     *
     * @return array
     */
    public function getSelectedLinks($ids = array())
    {
        if (empty($ids)) {
            return;
        }
        try {
            $query = $this->getEntityManager()->createQuery('SELECT l FROM Cx\Core_Modules\LinkManager\Model\Entity\Link l WHERE l.id IN ('.implode(',', $ids).')');
            $objResult = $query->getResult();
            if (!$objResult) {
                $objResult = array();
            }
            return new \Doctrine\Common\Collections\ArrayCollection($objResult);
        } catch (\Exception $error) {
            die('Error:' . $error);
        }
    }

    /**
     * get the link by path
     *
     * @param string $path
     *
     * @return object
     */
    public function getLinkByPath($path)
    {
        return $this->findOneBy(array('requestedPath' => $path));
    }

    /**
     * get the non detected links during the crawler run
     *
     * @param datetime $startTime
     * @param integer  $lang
     *
     * @return array
     */
    public function getNonDetectedLinks($startTime, $lang)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('link')
           ->from('Cx\Core_Modules\LinkManager\Model\Entity\Link', 'link')
           ->where('link.detectedTime < :start')
           ->andWhere('link.lang = :lang')
           ->getDql();
        $qb->setParameter('start', $startTime)->setParameter('lang', $lang);

        return new \Doctrine\Common\Collections\ArrayCollection($qb->getQuery()->getResult());
    }

    /**
     * get the detected broken links count
     *
     * @param datetime $startTime
     * @param integer  $lang
     *
     * @return integer
     */
    public function getDetectedBrokenLinksCount($startTime, $lang)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('link')
           ->from('Cx\Core_Modules\LinkManager\Model\Entity\Link', 'link')
           ->where('link.detectedTime > :start')
           ->andWhere('link.flagStatus = :flagStatus')
           ->andWhere('link.lang = :lang')
           ->orderBy('link.id', 'DESC')
           ->getDql();
        $qb->setParameter('start', $startTime)->setParameter('flagStatus', 0)->setParameter('lang', $lang);
        $objResult = new \Doctrine\Common\Collections\ArrayCollection($qb->getQuery()->getResult());

        return $objResult->count();
    }

    /**
     * get the all links count
     *
     * @param datetime $startTime
     * @param integer  $lang
     *
     * @return integer
     */
    public function getLinksCountByLang($startTime, $lang)
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('link')
           ->from('Cx\Core_Modules\LinkManager\Model\Entity\Link', 'link')
           ->where('link.detectedTime > :start')
           ->andWhere('link.lang = :lang')
           ->orderBy('link.id', 'DESC')
           ->getDql();
        $qb->setParameter('start', $startTime)->setParameter('lang', $lang);
        $objResult = new \Doctrine\Common\Collections\ArrayCollection($qb->getQuery()->getResult());

        return $objResult->count();
    }
}
