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
 * CoreEntityBaseEventListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodules_cache
 */

namespace Cx\Core_Modules\Cache\Model\Event;

/**
 * CoreEntityBaseEventListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodules_cache
 */
class CoreEntityBaseEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener {

    /**
     * Listens to postFlush event in order to drop cache on changes
     */
    public function postFlush() {
        // TODO: This is a workaround for Doctrine's result query cache.
        //       Proper handling of ResultCache must be implemented.
        $this->cx->getDb()->getEntityManager()->getConfiguration()->getResultCacheImpl()->deleteAll();
    }
}
