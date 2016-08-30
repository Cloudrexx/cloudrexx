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
 * Wrapper class for Doctrine Entity Manager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      ss4u <ss4u.comvation@gmail.com>
 * @version     3.1.2
 * @package     cloudrexx
 * @subpackage  core
 */

namespace Cx\Core\Model\Controller;

/**
 * Wrapper class for Doctrine Entity Manager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      ss4u <ss4u.comvation@gmail.com>
 * @version     $Id:    Exp $
 * @package     cloudrexx
 * @subpackage  core
 */
class EntityManager extends \Doctrine\ORM\EntityManager {

    /**
     * {@inheritdoc}
     */
    public function createQuery($dql = "")
    {
        $query = new \Doctrine\ORM\Query($this);


        if (strpos($dql, 'SELECT') !== false) {
            $query->useResultCache(true);
        }

        if ( ! empty($dql)) {
            $query->setDql($dql);
        }
        return $query;
    }

    /**
     * {@inheritdoc}
     */
    public static function create($conn, \Doctrine\ORM\Configuration $config, \Doctrine\Common\EventManager $eventManager = null)
    {
        if (!$config->getMetadataDriverImpl()) {
            throw \Doctrine\ORM\ORMException::missingMappingDriverImpl();
        }

        if (is_array($conn)) {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($conn, $config, ($eventManager ?: new EventManager()));
        } else if ($conn instanceof Connection) {
            if ($eventManager !== null && $conn->getEventManager() !== $eventManager) {
                 throw \Doctrine\ORM\ORMException::mismatchedEventManager();
            }
        } else {
            throw new \InvalidArgumentException("Invalid argument: " . $conn);
        }

        return new EntityManager($conn, $config, $conn->getEventManager());
    }
}
