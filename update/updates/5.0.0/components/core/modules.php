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
            \Cx\Lib\UpdateUtil::sql("INSERT INTO ".DBPREFIX."modules ( `id` , `name` , `description_variable` , `status` , `is_required` , `is_core` , `is_active`, `distributor` ) VALUES ( ".$arrModule['id']." , '".$arrModule['name']."', '".$arrModule['description_variable']."', '".$arrModule['status']."', '".$arrModule['is_required']."', '".$arrModule['is_core']."', ".$arrModule['is_active'].", 'Cloudrexx AG') ON DUPLICATE KEY UPDATE `id` = `id`");
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
			'id'					=> 0,
			'name'					=> '',
			'description_variable'	=> '',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 0
		),
		array(
			'id'					=> 1,
			'name'					=> 'core',
			'description_variable'	=> 'TXT_CORE_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 2,
			'name'					=> 'Stats',
			'description_variable'	=> 'TXT_STATS_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 3,
			'name'					=> 'Gallery',
			'description_variable'	=> 'TXT_GALLERY_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 4,
			'name'					=> 'Newsletter',
			'description_variable'	=> 'TXT_NEWSLETTER_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 5,
			'name'					=> 'Search',
			'description_variable'	=> 'TXT_SEARCH_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 6,
			'name'					=> 'Contact',
			'description_variable'	=> 'TXT_CONTACT_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 7,
			'name'					=> 'Block',
			'description_variable'	=> 'TXT_BLOCK_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 8,
			'name'					=> 'News',
			'description_variable'	=> 'TXT_NEWS_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 9,
			'name'					=> 'Media1',
			'description_variable'	=> 'TXT_MEDIA_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 10,
			'name'					=> 'GuestBook',
			'description_variable'	=> 'TXT_GUESTBOOK_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 11,
			'name'					=> 'Sitemap',
			'description_variable'	=> 'TXT_SITEMAP_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 12,
			'name'					=> 'Directory',
			'description_variable'	=> 'TXT_LINKS_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 13,
			'name'					=> 'Ids',
			'description_variable'	=> 'TXT_IDS_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 14,
			'name'					=> 'Error',
			'description_variable'	=> 'TXT_ERROR_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 15,
			'name'					=> 'Home',
			'description_variable'	=> 'TXT_HOME_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 16,
			'name'					=> 'Shop',
			'description_variable'	=> 'TXT_SHOP_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 17,
			'name'					=> 'Voting',
			'description_variable'	=> 'TXT_VOTING_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 18,
			'name'					=> 'Login',
			'description_variable'	=> 'TXT_LOGIN_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 19,
			'name'					=> 'DocSys',
			'description_variable'	=> 'TXT_DOC_SYS_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 20,
			'name'					=> 'Forum',
			'description_variable'	=> 'TXT_FORUM_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 21,
			'name'					=> 'Calendar',
			'description_variable'	=> 'TXT_CALENDAR_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 22,
			'name'					=> 'Feed',
			'description_variable'	=> 'TXT_FEED_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 23,
			'name'					=> 'Access',
			'description_variable'	=> 'TXT_COMMUNITY_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 24,
			'name'					=> 'Media2',
			'description_variable'	=> 'TXT_MEDIA_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 25,
			'name'					=> 'Media3',
			'description_variable'	=> 'TXT_MEDIA_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 26,
			'name'					=> 'FileBrowser',
			'description_variable'	=> 'TXT_FILEBROWSER_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 27,
			'name'					=> 'Recommend',
			'description_variable'	=> 'TXT_RECOMMEND_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 30,
			'name'					=> 'Livecam',
			'description_variable'	=> 'TXT_LIVECAM_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 31,
			'name'					=> 'MemberDir',
			'description_variable'	=> 'TXT_MEMBERDIR_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 33,
			'name'					=> 'Market',
			'description_variable'	=> 'TXT_MARKET_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 35,
			'name'					=> 'Podcast',
			'description_variable'	=> 'TXT_PODCAST_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 38,
			'name'					=> 'Egov',
			'description_variable'	=> 'TXT_EGOVERNMENT_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 39,
			'name'					=> 'Media4',
			'description_variable'	=> 'TXT_MEDIA_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 41,
			'name'					=> 'Alias',
			'description_variable'	=> 'TXT_ALIAS_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 44,
			'name'					=> 'Imprint',
			'description_variable'	=> 'TXT_IMPRINT_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 45,
			'name'					=> 'Agb',
			'description_variable'	=> 'TXT_AGB_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 46,
			'name'					=> 'Privacy',
			'description_variable'	=> 'TXT_PRIVACY_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 48,
			'name'					=> 'Data',
			'description_variable'	=> 'TXT_DATA_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 49,
			'name'					=> 'Ecard',
			'description_variable'	=> 'TXT_ECARD_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 52,
			'name'					=> 'Upload',
			'description_variable'	=> 'TXT_FILEUPLOADER_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 53,
			'name'					=> 'Downloads',
			'description_variable'	=> 'TXT_DOWNLOADS_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 54,
			'name'					=> 'U2u',
			'description_variable'	=> 'TXT_U2U_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 56,
			'name'					=> 'Knowledge',
			'description_variable'	=> 'TXT_KNOWLEDGE_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 57,
			'name'					=> 'Jobs',
			'description_variable'	=> 'TXT_JOBS_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 60,
			'name'					=> 'MediaDir',
			'description_variable'	=> 'TXT_MEDIADIR_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 61,
			'name'					=> 'Captcha',
			'description_variable'	=> 'Catpcha Module',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 63,
			'name'					=> 'JsonData',
			'description_variable'	=> 'Json Adapter',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 66,
			'name'					=> 'License',
			'description_variable'	=> 'TXT_LICENSE',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 67,
			'name'					=> 'logout',
			'description_variable'	=> 'TXT_LOGIN_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 68,
			'name'					=> 'FileSharing',
			'description_variable'	=> 'TXT_FILESHARING_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 0,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 69,
			'name'					=> 'Crm',
			'description_variable'	=> 'TXT_CRM_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 71,
			'name'					=> 'FrontendEditing',
			'description_variable'	=> 'TXT_MODULE_FRONTEND_EDITING',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 72,
			'name'					=> 'ContentManager',
			'description_variable'	=> 'TXT_CONTENTMANAGER_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 73,
			'name'					=> 'DatabaseManager',
			'description_variable'	=> 'TXT_DATABASEMANAGER_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 74,
			'name'					=> 'SystemInfo',
			'description_variable'	=> 'TXT_SYSTEMINFO_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 75,
			'name'					=> 'ViewManager',
			'description_variable'	=> 'TXT_VIEWMANAGER_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 76,
			'name'					=> 'ComponentManager',
			'description_variable'	=> 'TXT_COMPONENTMANAGER_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 77,
			'name'					=> 'LanguageManager',
			'description_variable'	=> 'TXT_LANGUAGEMANAGER_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 78,
			'name'					=> 'ContentWorkflow',
			'description_variable'	=> 'TXT_CONTENTWORKFLOW_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 79,
			'name'					=> 'SystemLog',
			'description_variable'	=> 'TXT_SYSTEMLOG_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 80,
			'name'					=> 'Config',
			'description_variable'	=> 'TXT_CONFIG_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 81,
			'name'					=> 'Cache',
			'description_variable'	=> '',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 84,
			'name'					=> 'Security',
			'description_variable'	=> 'TXT_SECURITY_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 85,
			'name'					=> 'Csrf',
			'description_variable'	=> ' TXT_CSRF_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 86,
			'name'					=> 'Session',
			'description_variable'	=> 'TXT_SESSION_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 87,
			'name'					=> 'Message',
			'description_variable'	=> 'TXT_MESSAGE_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 88,
			'name'					=> 'JavaScript',
			'description_variable'	=> 'TXT_JAVASCRIPT_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 90,
			'name'					=> 'Net',
			'description_variable'	=> 'TXT_NET_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 92,
			'name'					=> 'Shell',
			'description_variable'	=> 'TXT_CORE_SHELL_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 99,
			'name'					=> 'Routing',
			'description_variable'	=> 'TXT_ROUTING_MODULE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 100,
			'name'					=> 'Support',
			'description_variable'	=> 'TXT_MODULE_SUPPORT_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 101,
			'name'					=> 'Uploader',
			'description_variable'	=> 'TXT_CORE_MODULE_UPLOADER_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 102,
			'name'					=> 'MediaBrowser',
			'description_variable'	=> 'TXT_CORE_MODULE_MEDIABROWSER_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 103,
			'name'					=> 'Cron',
			'description_variable'	=> 'TXT_CORE_MODULE_CRON_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 104,
			'name'					=> 'SysLog',
			'description_variable'	=> 'TXT_CORE_MODULE_SYSLOG_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 105,
			'name'					=> 'NetManager',
			'description_variable'	=> 'TXT_NETMANAGER_MODULE_DESCRIPTION',
			'status'				=> 'y',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 106,
			'name'					=> 'Wysiwyg',
			'description_variable'	=> 'TXT_CORE_WYSIWYG_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 107,
			'name'					=> 'User',
			'description_variable'	=> 'TXT_CORE_USER_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 108,
			'name'					=> 'Html',
			'description_variable'	=> 'TXT_CORE_HTML_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 109,
			'name'					=> 'MediaSource',
			'description_variable'	=> 'TXT_CORE_MEDIASOURCE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 110,
			'name'					=> 'TemplateEditor',
			'description_variable'	=> 'TXT_CORE_MODULE_TEMPLATEEDITOR_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 111,
			'name'					=> 'GeoIp',
			'description_variable'	=> 'TXT_CORE_MODULE_GEOIP_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 0,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 112,
			'name'					=> 'DateTime',
			'description_variable'	=> 'TXT_CORE_DATETIME_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 113,
			'name'					=> 'DataAccess',
			'description_variable'	=> 'TXT_CORE_MODULE_DATAACCESS_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 114,
			'name'					=> 'DataSource',
			'description_variable'	=> 'TXT_CORE_MODULE_DATASOURCE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 0,
			'is_active'				=> 1
		),
		array(
			'id'					=> 116,
			'name'					=> 'Model',
			'description_variable'	=> 'TXT_CORE_MODEL_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 117,
			'name'					=> 'Pdf',
			'description_variable'	=> 'TXT_CORE_MODULES_PDF_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 118,
			'name'					=> 'Setting',
			'description_variable'	=> 'TXT_CORE_SETTING_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 119,
			'name'					=> 'Country',
			'description_variable'	=> 'TXT_CORE_COUNTRY_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 120,
			'name'					=> 'View',
			'description_variable'	=> 'TXT_CORE_VIEW_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 121,
			'name'					=> 'Locale',
			'description_variable'	=> 'TXT_CORE_LOCALE_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		),
		array(
			'id'					=> 122,
			'name'					=> 'Widget',
			'description_variable'	=> 'TXT_CORE_MODULES_WIDGET_DESCRIPTION',
			'status'				=> 'n',
			'is_required'			=> 1,
			'is_core'				=> 1,
			'is_active'				=> 1
		)
	);

    return $arrModules;
}

function getModuleInfo($name)
{
    $arrModules = getModules();

    foreach ($arrModules as $arrModule) {
        // case-insensitive check is required due to the component renaming in version 5
        if (strtolower($arrModule['name']) == strtolower($name)) {
            return $arrModule;
        }
    }

    return false;
}

?>
