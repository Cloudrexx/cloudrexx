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



namespace Cx\Modules\Downloads\Model\Event;

/**
 * LocaleLocaleEventListener
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Nicola Tommasi <nicola.tommasi@comvation.com>
 * @package     cloudrexx
 */
class LocaleLocaleEventListener extends \Cx\Core\Event\Model\Entity\DefaultEventListener  {

    /**
     * Fills the new download locales (downloads, categories and groups) with the default locale's values
     * when adding a new Cx\Core\Locale\Model\Entity\Locale
     *
     * @param $eventArgs
     */
    public function postPersist($eventArgs) {
        // get persisted locale
        $persistedLocale = $eventArgs->getEntity();

        $defaultLocaleId = \FWLanguage::getDefaultLangId();
        $localeId = $persistedLocale->getId();

        $db = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb();
        // Add new download locales
        $downloadQuery = 'INSERT IGNORE INTO `' . DBPREFIX . 'module_downloads_download_locale`
            (   
                `lang_id`,
                `download_id`,
                `name`,
                `source`,
                `source_name`,
                `file_type`,
                `description`,
                `metakeys`
            )
            SELECT 
                ' . $localeId . ',
                `download_id`,
                `name`,
                `source`,
                `source_name`,
                `file_type`,
                `description`,
                `metakeys`
            FROM `' . DBPREFIX . 'module_downloads_download_locale`
                                WHERE lang_id = ' . $defaultLocaleId;
        $db->Execute($downloadQuery);

        // Add new category locales
        $categoryQuery = 'INSERT IGNORE INTO `' . DBPREFIX . 'module_downloads_category_locale`
            (   
                `lang_id`,
                `category_id`,
                `name`,
                `description`
            )
            SELECT 
                ' . $localeId . ',
                `category_id`,
                `name`,
                `description`
            FROM `' . DBPREFIX . 'module_downloads_category_locale`
            WHERE lang_id = ' . $defaultLocaleId;
        $db->Execute($categoryQuery);

        // Add new group locales
        $groupQuery = 'INSERT IGNORE INTO `' . DBPREFIX . 'module_downloads_group_locale`
            (   
                `lang_id`,
                `group_id`,
                `name`
            )
            SELECT 
                ' . $localeId . ',
                `group_id`,
                `name`
            FROM `' . DBPREFIX . 'module_downloads_group_locale`
            WHERE lang_id = ' . $defaultLocaleId;
        $db->Execute($groupQuery);
    }

    /**
     * Deletes the download locales (downloads, categories and groups)
     * when deleting a Cx\Core\Locale\Model\Entity\Locale
     *
     * @param $eventArgs
     */
    public function preRemove($eventArgs) {
        // get locale, which will be deleted
        $delLocale = $eventArgs->getEntity();

        $localeId = $delLocale->getId();

        $db = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getAdoDb();
        // Delete the download locales
        $downloadQuery = 'DELETE FROM `' . DBPREFIX . 'module_downloads_download_locale`
            WHERE lang_id = ' . $localeId;
        $db->Execute($downloadQuery);

        // Delete the category locales
        $categoryQuery = 'DELETE FROM `' . DBPREFIX . 'module_downloads_category_locale`
            WHERE lang_id = ' . $localeId;
        $db->Execute($categoryQuery);

        // Delete the group locales
        $groupQuery = 'DELETE FROM `' . DBPREFIX . 'module_downloads_group_locale`
            WHERE lang_id = ' . $localeId;
        $db->Execute($groupQuery);
    }
}