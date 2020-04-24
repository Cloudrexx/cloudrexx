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
     * @var array Lookup table. Key is database table name, value is entity name
     */
    protected $reverseLookupTable = array();

    /**
     * {@inheritdoc}
     */
    public function createQuery($dql = '')
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
     * Returns the entity name for a table name
     * @author Michael Ritter <michael.ritter@cloudrexx.com>
     * @param string $tableName Name of the database table
     * @return string Fully qualified name of the entity
     */
    public function getEntityNameByTableName($tableName) {
        if (!count($this->reverseLookupTable)) {
            $metadatas = $this->getMetadataFactory()->getAllMetadata();
            foreach ($metadatas as $metadata) {
                $this->reverseLookupTable[$metadata->getTableName()] = $metadata->getName();
            }
        }
        if (!isset($this->reverseLookupTable[$tableName])) {
            return null;
        }
        return $this->reverseLookupTable[$tableName];
    }
}
