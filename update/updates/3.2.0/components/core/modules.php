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

function _updateModules()
{
    global $objDatabase;

    $arrModules = getModules();

    try {
        \Cx\Lib\UpdateUtil::sql('TRUNCATE TABLE `'.DBPREFIX.'modules`');

        // NOTE: scheme migration is done in core/core.php

        // add modules
        foreach ($arrModules as $arrModule) {
            \Cx\Lib\UpdateUtil::sql("INSERT INTO ".DBPREFIX."modules ( `id` , `name` , `description_variable` , `status` , `is_required` , `is_core` , `is_active`, `distributor` ) VALUES ( ".$arrModule['id']." , '".$arrModule['name']."', '".$arrModule['description_variable']."', '".$arrModule['status']."', '".$arrModule['is_required']."', '".$arrModule['is_core']."', ".$arrModule['is_active'].", 'Comvation AG') ON DUPLICATE KEY UPDATE `id` = `id`");
        }
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    return true;
}

function getModules()
{
    $arrModules = array(
        array(
            'id'                    => 0,
            'name'                    => '',
            'description_variable'    => '',
            'status'                => 'n',
            'is_required'            => 0,
            'is_core'                => 1,
                        'is_active'                             => 0
        ),
        array(
            'id'                    => 1,
            'name'                    => 'core',
            'description_variable'    => 'TXT_CORE_MODULE_DESCRIPTION',
            'status'                => 'n',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 2,
            'name'                    => 'stats',
            'description_variable'    => 'TXT_STATS_MODULE_DESCRIPTION',
            'status'                => 'n',
            'is_required'            => 0,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 3,
            'name'                    => 'gallery',
            'description_variable'    => 'TXT_GALLERY_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 4,
            'name'                    => 'newsletter',
            'description_variable'    => 'TXT_NEWSLETTER_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 5,
            'name'                    => 'search',
            'description_variable'    => 'TXT_SEARCH_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 6,
            'name'                    => 'contact',
            'description_variable'    => 'TXT_CONTACT_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 7,
            'name'                    => 'block',
            'description_variable'    => 'TXT_BLOCK_MODULE_DESCRIPTION',
            'status'                => 'n',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 8,
            'name'                    => 'news',
            'description_variable'    => 'TXT_NEWS_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 9,
            'name'                    => 'media1',
            'description_variable'    => 'TXT_MEDIA_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 10,
            'name'                    => 'guestbook',
            'description_variable'    => 'TXT_GUESTBOOK_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 11,
            'name'                    => 'sitemap',
            'description_variable'    => 'TXT_SITEMAP_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 12,
            'name'                    => 'directory',
            'description_variable'    => 'TXT_LINKS_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 13,
            'name'                    => 'ids',
            'description_variable'    => 'TXT_IDS_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 14,
            'name'                    => 'error',
            'description_variable'    => 'TXT_ERROR_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 15,
            'name'                    => 'home',
            'description_variable'    => 'TXT_HOME_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 16,
            'name'                    => 'shop',
            'description_variable'    => 'TXT_SHOP_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 17,
            'name'                    => 'voting',
            'description_variable'    => 'TXT_VOTING_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 18,
            'name'                    => 'login',
            'description_variable'    => 'TXT_LOGIN_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 19,
            'name'                    => 'docsys',
            'description_variable'    => 'TXT_DOC_SYS_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 20,
            'name'                    => 'forum',
            'description_variable'    => 'TXT_FORUM_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 21,
            'name'                    => 'calendar',
            'description_variable'    => 'TXT_CALENDAR_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 22,
            'name'                    => 'feed',
            'description_variable'    => 'TXT_FEED_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 23,
            'name'                    => 'access',
            'description_variable'    => 'TXT_COMMUNITY_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 24,
            'name'                    => 'media2',
            'description_variable'    => 'TXT_MEDIA_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 25,
            'name'                    => 'media3',
            'description_variable'    => 'TXT_MEDIA_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 26,
            'name'                    => 'fileBrowser',
            'description_variable'    => 'TXT_FILEBROWSER_DESCRIPTION',
            'status'                => 'n',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 27,
            'name'                    => 'recommend',
            'description_variable'    => 'TXT_RECOMMEND_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 30,
            'name'                    => 'livecam',
            'description_variable'    => 'TXT_LIVECAM_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 31,
            'name'                    => 'memberdir',
            'description_variable'    => 'TXT_MEMBERDIR_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 32,
            'name'                    => 'nettools',
            'description_variable'    => 'TXT_NETTOOLS_MODULE_DESCRIPTION',
            'status'                => 'n',
            'is_required'            => 0,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 33,
            'name'                    => 'market',
            'description_variable'    => 'TXT_MARKET_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 35,
            'name'                    => 'podcast',
            'description_variable'    => 'TXT_PODCAST_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 38,
            'name'                    => 'egov',
            'description_variable'    => 'TXT_EGOVERNMENT_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 39,
            'name'                    => 'media4',
            'description_variable'    => 'TXT_MEDIA_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 41,
            'name'                    => 'alias',
            'description_variable'    => 'TXT_ALIAS_MODULE_DESCRIPTION',
            'status'                => 'n',
            'is_required'            => 0,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 44,
            'name'                    => 'imprint',
            'description_variable'    => 'TXT_IMPRINT_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 45,
            'name'                    => 'agb',
            'description_variable'    => 'TXT_AGB_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 46,
            'name'                    => 'privacy',
            'description_variable'    => 'TXT_PRIVACY_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 47,
            'name'                    => 'blog',
            'description_variable'    => 'TXT_BLOG_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 48,
            'name'                    => 'data',
            'description_variable'    => 'TXT_DATA_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 49,
            'name'                    => 'ecard',
            'description_variable'    => 'TXT_ECARD_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 52,
            'name'                    => 'upload',
            'description_variable'    => 'TXT_FILEUPLOADER_MODULE_DESCRIPTION',
            'status'                => 'n',
            'is_required'            => 0,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 53,
            'name'                    => 'downloads',
            'description_variable'    => 'TXT_DOWNLOADS_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 54,
            'name'                    => 'u2u',
            'description_variable'    => 'TXT_U2U_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 56,
            'name'                    => 'knowledge',
            'description_variable'    => 'TXT_KNOWLEDGE_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 57,
            'name'                    => 'jobs',
            'description_variable'    => 'TXT_JOBS_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 60,
            'name'                    => 'mediadir',
            'description_variable'    => 'TXT_MEDIADIR_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 61,
            'name'                    => 'captcha',
            'description_variable'    => 'Catpcha Module',
            'status'                => 'n',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 62,
            'name'                    => 'checkout',
            'description_variable'    => 'TXT_CHECKOUT_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 63,
            'name'                    => 'jsondata',
            'description_variable'    => 'Json Adapter',
            'status'                => 'n',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 64,
            'name'                    => 'language',
            'description_variable'    => 'TXT_LANGUAGE_SETTINGS',
            'status'                => 'n',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 65,
            'name'                    => 'fulllanguage',
            'description_variable'    => 'TXT_LANGUAGE_SETTINGS',
            'status'                => 'n',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 66,
            'name'                    => 'license',
            'description_variable'    => 'TXT_LICENSE',
            'status'                => 'n',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 67,
            'name'                    => 'logout',
            'description_variable'    => 'TXT_LOGIN_MODULE_DESCRIPTION',
            'status'                => 'n',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 68,
            'name'                    => 'filesharing',
            'description_variable'    => 'TXT_FILESHARING_MODULE_DESCRIPTION',
            'status'                => 'y',
            'is_required'            => 0,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 69,
            'name'                    => 'crm',
            'description_variable'    => 'TXT_CRM_MODULE_DESCRIPTION',
            'status'                => 'n',
            'is_required'            => 1,
            'is_core'                => 0,
                        'is_active'                             => 1
        ),
        array(
            'id'                    => 71,
            'name'                    => 'FrontendEditing',
            'description_variable'    => 'TXT_MODULE_FRONTEND_EDITING',
            'status'                => 'n',
            'is_required'            => 1,
            'is_core'                => 1,
                        'is_active'                             => 1
        )
    );

    return $arrModules;
}

function getModuleInfo($name)
{
    $arrModules = getModules();

    foreach ($arrModules as $arrModule) {
        if ($arrModule['name'] == $name) {
            return $arrModule;
        }
    }

    return false;
}

?>
