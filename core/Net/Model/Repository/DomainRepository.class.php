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
 * Domain Repository
 *
 * Repository to manage the domain entities.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 */

namespace Cx\Core\Net\Model\Repository;

/**
 * Domain Repository
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 */
class DomainRepositoryException extends \Exception {};

/**
 * Domain Repository
 *
 * Repository to manage the domain entities.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@comvation.com>
 * @package     cloudrexx
 * @subpackage  core_model
 */
class DomainRepository extends \Cx\Core\Model\Controller\YamlRepository {
    /**
     * Constructor to initialize the YamlRepository with source
     * file config/DomainRepository.yml.
     */
    public function __construct() {
        parent::__construct(\Env::get('cx')->getWebsiteConfigPath() . '/DomainRepository.yml');

        //Initialize the Hostname Domain
        $hostName = $this->findOneBy(array('name' => $_SERVER['SERVER_NAME']));
        if (!$hostName) {
            $hostName = new \Cx\Core\Net\Model\Entity\Domain($_SERVER['SERVER_NAME']);
            $hostName->setVirtual(true);
            //attach the hostname domain entity to repository
            $this->add($hostName);
        }

        // Since YamlRepo handles virtual entities wrong, we can not change the
        // ID of an existing entry (see
        // http://bugs.cloudrexx.com/cloudrexx/ticket/2762 and
        // http://bugs.cloudrexx.com/cloudrexx/ticket/2763).
        // This else statement can be removed as soon as YamlRepository can
        // handle ID changes of virtual entities.
        else {
            throw new \Exception('Duplicate entry for this domain, see http://bugs.cloudrexx.com/cloudrexx/ticket/2763');
        }

        $hostName->setVirtual(true);
        // set ID to 0 to make it having the same ID constantly
        $hostName->setId(0);
    }

    public function getMainDomain() {
        $config = \Env::get('config');

        if (!empty($config['mainDomainId']) && isset($this->entities[$config['mainDomainId']])) {
            return $this->entities[$config['mainDomainId']];
        }

        $objDomain = $this->findBy(array('name' => $_SERVER['SERVER_NAME']));
        return $objDomain[0];
    }
}
