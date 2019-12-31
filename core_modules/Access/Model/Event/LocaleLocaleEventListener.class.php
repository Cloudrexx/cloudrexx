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



namespace Cx\Core_Modules\Access\Model\Event;

/**
 * LocaleLocaleEventListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 */
class LocaleLocaleEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener {

    /**
     * Fills the locale specific user attribute names for the new locale
     * with attribute values of the the default locale
     * when adding a new Cx\Core\Locale\Model\Entity\Locale
     *
     * @param $eventArgs
     */
    public function postPersist($eventArgs) {
        // get persisted locale
        $persistedLocale = $eventArgs->getEntity();

        $defaultLocaleId = \FWLanguage::getDefaultLangId();
        $localeId = $persistedLocale->getId();

        // Add user attribute names for new locale
        $accessAttrQuery = 'INSERT IGNORE INTO `' . DBPREFIX . 'access_user_attribute_name`
            (   
                `attribute_id`,
                `lang_id`,
                `name`
            )
            SELECT 
                `attribute_id`,
                ' . $localeId . ',
                `name`
            FROM `' . DBPREFIX . 'access_user_attribute_name`
            WHERE lang_id = ' . $defaultLocaleId;
        \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb()->Execute($accessAttrQuery);
    }

    /**
     * Deletes the locale specific user attribute names
     * when deleting a Cx\Core\Locale\Model\Entity\Locale
     *
     * @param $eventArgs
     */
    public function preRemove($eventArgs) {
        // get locale, which will be deleted
        $delLocale = $eventArgs->getEntity();
        $localeId = $delLocale->getId();

        // Update the access user attributes
        $accessAttrQuery = 'DELETE FROM `' . DBPREFIX . 'access_user_attribute_name`
            WHERE lang_id = ' . $localeId;
        \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb()->Execute($accessAttrQuery);
    }
}