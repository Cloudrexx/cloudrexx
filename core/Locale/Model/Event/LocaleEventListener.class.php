<?php

/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2016
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
 * LocaleEventListener
 *
 * @copyright   Cloudrexx AG
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 * @version     5.0.0
 */
namespace Cx\Core\Locale\Model\Event;

/**
 * LocaleEventListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_locale
 */
class LocaleEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener {

    /**
     * Workaround to manually clear the Doctrine ResultCache
     *
     * @param $eventArgs
     */
    public function onFlush($eventArgs) {
        $eventArgs->getEntityManager()->getConfiguration()->getResultCacheImpl()->deleteAll();

        // drop page and ESI cache
        $this->getComponent('Cache')->clearCache();
    }
}
