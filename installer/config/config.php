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
 * Installer config
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version       1.0.0
 * @package     cloudrexx
 * @subpackage  core
 * @todo        Edit PHP DocBlocks!
 */

$requiredPHPVersion = "5.3.0";
$requiredMySQLVersion = "5.0";
$requiredGDVersion = "1.6";
$dbType = "mysql";
$defaultLanguage = "de";
$licenseFileCommerce = "data".DIRECTORY_SEPARATOR."contrexx_lizenz_de.txt";
$licenseFileOpenSource = "data".DIRECTORY_SEPARATOR."contrexx_lizenz_opensource_de.txt";
$configFile = "/config/configuration.php";
$configTemplateFile = "data".DIRECTORY_SEPARATOR."configuration.tpl";
$apacheHtaccessFile = "/.htaccess";
$apacheHtaccessTemplateFile = "data".DIRECTORY_SEPARATOR."apache_htaccess.tpl";
$iisHtaccessFile = "/web.config";
$iisHtaccessTemplateFile = "data".DIRECTORY_SEPARATOR."iis_htaccess.tpl";
$versionTemplateFile = "data".DIRECTORY_SEPARATOR."version.tpl";
$sqlDumpFile = DIRECTORY_SEPARATOR."installer".DIRECTORY_SEPARATOR."data".DIRECTORY_SEPARATOR."contrexx_dump";
$dbPrefix = "contrexx_";
$templatePath = "template/contrexx/";
$supportEmail = "support@contrexx.com";
$supportURI = "http://www.contrexx.com/support";
$forumURI = "http://www.contrexx.com/forum/";
$contrexxURI = "http://www.contrexx.com";
$useUtf8 = true;

define('ASCMS_LIBRARY_PATH', realpath(dirname(__FILE__).'/../../lib'));
define('ASCMS_FRAMEWORK_PATH', realpath(dirname(__FILE__).'/../../lib/FRAMEWORK'));
define('ASCMS_CORE_MODULE_PATH', realpath(dirname(__FILE__).'/../../core_modules'));
define('CONTREXX_CHARSET', 'UTF-8');
define('ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME',  'Y-m-d H:i:s');

if (!empty($_SESSION['installer']['config']['documentRoot'])) {
    define('ASCMS_PATH', $_SESSION['installer']['config']['documentRoot']);
    define('ASCMS_PATH_OFFSET', $_SESSION['installer']['config']['offsetPath']);
    define('ASCMS_INSTANCE_PATH', $_SESSION['installer']['config']['documentRoot']);
    define('ASCMS_INSTANCE_OFFSET', $_SESSION['installer']['config']['offsetPath']);
    define('ASCMS_DOCUMENT_ROOT', ASCMS_PATH.ASCMS_PATH_OFFSET);
}

$arrTimezones = timezone_identifiers_list();
$selectedTimezoneId = (isset($_POST['timezone']) && array_key_exists($_POST['timezone'], $arrTimezones)) ? $_POST['timezone'] : '';
$selectedTimezoneId = (($selectedTimezoneId === '') && (isset($_SESSION['installer']['config']['timezone']) && array_key_exists($_SESSION['installer']['config']['timezone'], $arrTimezones))) ? $_SESSION['installer']['config']['timezone'] : $selectedTimezoneId;
if ($selectedTimezoneId !== '') {
    @ini_set('date.timezone', $arrTimezones[$selectedTimezoneId]);
}

$_CONFIG['coreCmsName']         = 'Contrexx';
$_CONFIG['coreCmsVersion']      = '4.0.0';
$_CONFIG['coreCmsStatus']       = 'Stable';
$_CONFIG['coreCmsEdition']      = 'Standard';
$_CONFIG['coreCmsCodeName']     = 'Eric S. Raymond';
$_CONFIG['coreCmsReleaseDate']  = '17.12.2014';

$arrDefaultConfig = array(
    'dbHostname'        => 'localhost',
    'dbUsername'        => '',
    'dbPassword'        => '',
    'dbDatabaseName'    => '',
    'dbTablePrefix'     => 'contrexx_',
    'ftpHostname'       => 'localhost',
    'ftpUsername'       => '',
    'ftpPassword'       => '',
);

$arrLanguages = array(
    1 => array(
        'id'            => 1,
        'lang'          => 'de',
        'name'          => 'Deutsch',
        'is_default'    => true,
    ),
    2 => array(
        'id'            => 2,
        'lang'          => 'en',
        'name'          => 'English',
        'is_default'    => false,
    ),
    /*3 => array(
        'id'            => 2,
        'lang'          => 'fr',
        'name'          => 'Français',
        'is_default'    => false,
    )*/
);

$arrFiles = array(
    '/config' => array(),
    '/feed' => array(
        'sub_dirs'  => true,
    ),
    '/images' => array(
        'sub_dirs'  => true,
    ),
    '/media' => array(
        'sub_dirs'  => true,
    ),
    '/themes' => array(
        'sub_dirs'  => true,
    ),
    '/tmp' => array(
        'sub_dirs'  => true,
    ),
);

$arrDatabaseTables = array(
    'module_block_blocks',
    'module_block_rel_pages',
    'module_block_settings',
    'module_calendar_category',
    'module_calendar_category_name',
    'module_calendar_event',
    'module_calendar_event_field',
    'module_calendar_host',
    'module_calendar_mail',
    'module_calendar_mail_action',
    'module_calendar_registration',
    'module_calendar_registration_form',
    'module_calendar_registration_form_field',
    'module_calendar_registration_form_field_name',
    'module_calendar_registration_form_field_value',
    'module_calendar_rel_event_host',
    'module_calendar_settings',
    'module_calendar_settings_section',
    'module_calendar_style',
    'module_contact_form',
    'module_contact_form_data',
    'module_contact_form_field',
    'module_contact_settings',
    'access_group_dynamic_ids',
    'access_group_static_ids',
    'access_users',
    'access_user_groups',
    'backend_areas',
    'backups',
    'ids',
    'languages',
    'log',
    'component',
    'modules',
    'module_repository',
    'sessions',
    'settings',
    'settings_smtp',
    'skins',
    'module_directory_categories',
    'module_directory_dir',
    'module_directory_inputfields',
    'module_directory_levels',
    'module_directory_mail',
    'module_directory_rel_dir_cat',
    'module_directory_rel_dir_level',
    'module_directory_settings',
    'module_directory_vote',
    'module_docsys',
    'module_docsys_categories',
    'module_egov_configuration',
    'module_egov_orders',
    'module_egov_products',
    'module_egov_product_calendar',
    'module_egov_product_fields',
    'module_feed_category',
    'module_feed_news',
    'module_feed_newsml_association',
    'module_feed_newsml_categories',
    'module_feed_newsml_documents',
    'module_feed_newsml_providers',
    'module_forum_access',
    'module_forum_categories',
    'module_forum_categories_lang',
    'module_forum_notification',
    'module_forum_postings',
    'module_forum_settings',
    'module_forum_statistics',
    'module_gallery_categories',
    'module_gallery_comments',
    'module_gallery_language',
    'module_gallery_language_pics',
    'module_gallery_pictures',
    'module_gallery_settings',
    'module_gallery_votes',
    'module_guestbook',
    'module_guestbook_settings',
    'module_livecam_settings',
    'module_market',
    'module_market_categories',
    'module_market_mail',
    'module_market_paypal',
    'module_market_settings',
    'module_market_spez_fields',
    'module_memberdir_directories',
    'module_memberdir_name',
    'module_memberdir_settings',
    'module_memberdir_values',
    'module_news',
    'module_news_categories',
    'module_news_settings',
    'module_news_teaser_frame',
    'module_news_teaser_frame_templates',
    'module_news_ticker',
    'module_newsletter',
    'module_newsletter_attachment',
    'module_newsletter_category',
    'module_newsletter_confirm_mail',
    'module_newsletter_rel_cat_news',
    'module_newsletter_rel_user_cat',
    'module_newsletter_settings',
    'module_newsletter_template',
    'module_newsletter_tmp_sending',
    'module_newsletter_user',
    'module_newsletter_user_title',
    'module_podcast_category',
    'module_podcast_medium',
    'module_podcast_rel_category_lang',
    'module_podcast_rel_medium_category',
    'module_podcast_settings',
    'module_podcast_template',
    'module_recommend',
    'module_shop_categories',
    'module_shop_currencies',
    'module_shop_importimg',
    'module_shop_lsv',
    'module_shop_manufacturer',
    'module_shop_orders',
    'module_shop_order_items',
    'module_shop_payment',
    'module_shop_payment_processors',
    'module_shop_pricelists',
    'module_shop_products',
    'module_shop_rel_countries',
    'module_shop_rel_payment',
    'module_shop_shipment_cost',
    'module_shop_shipper',
    'module_shop_vat',
    'module_shop_zones',
    'stats_browser',
    'stats_colourdepth',
    'stats_config',
    'stats_country',
    'stats_hostname',
    'stats_javascript',
    'stats_operatingsystem',
    'stats_referer',
    'stats_requests',
    'stats_requests_summary',
    'stats_screenresolution',
    'stats_search',
    'stats_spiders',
    'stats_spiders_summary',
    'stats_visitors',
    'stats_visitors_summary',
    'voting_email',
    'voting_rel_email_system',
    'voting_results',
    'voting_system',
);
