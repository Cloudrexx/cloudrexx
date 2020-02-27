<?php
/**
 * Cloudrexx
 *
 * @link      http://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2019
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
 * EventListener for ApiKeys
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_modules_dataaccess
 * @version     5.0.0
 */
namespace Cx\Core_Modules\DataAccess\Model\Event;

/**
 * EventListener for ApiKeys
 *
 * @copyright   Cloudrexx AG
 * @author      Sam Hawkes <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_modules_dataaccess
 * @version     5.0.0
 */
class ApiKeyEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener
{

    /**
     * Prevent duplicate API keys from being stored
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args contains the entity
     * @throws \Cx\Core\Error\Model\Entity\ShinyException api key already exists
     */
    public function prePersist(\Doctrine\ORM\Event\LifecycleEventArgs $args)
    {
        global $_ARRAYLANG;

        if (
            $this->isAlreadyAnEntryWithThisKey($args->getEntity()->getApiKey())
        ) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG[
                'TXT_CORE_MODULE_DATA_ACCESS_API_KEY_ALREADY_EXISTS'
                ]
            );
        }
    }

    /**
     * Prevent duplicate API keys from being stored
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args contains the entity
     * @throws \Cx\Core\Error\Model\Entity\ShinyException api key already exists
     */
    public function preUpdate(\Doctrine\ORM\Event\LifecycleEventArgs $args)
    {
        global $_ARRAYLANG;

        if (
            $this->isAlreadyAnEntryWithThisKey($args->getEntity()->getApiKey())
        ) {
            throw new \Cx\Core\Error\Model\Entity\ShinyException(
                $_ARRAYLANG[
                    'TXT_CORE_MODULE_DATA_ACCESS_API_KEY_ALREADY_EXISTS'
                ]
            );
        }
    }

    /**
     * Check if an API key already exists with the given key
     *
     * @param string $apiKey check if entity with this API key already exists
     * @return bool if an API key exists
     */
    protected function isAlreadyAnEntryWithThisKey($apiKey)
    {
        $em = $this->cx->getDb()->getEntityManager();
        $apiKeyEntry = $em->getRepository(
            'Cx\Core_Modules\DataAccess\Model\Entity\ApiKey'
        )->findOneBy(array('apiKey' => $apiKey));

        return !empty($apiKeyEntry);
    }
}