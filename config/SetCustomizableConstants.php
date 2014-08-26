<?php

/**
 * @copyright   CONTREXX CMS - COMVATION AG
 * @package     contrexx
 * @subpackage  config
 * @todo        Edit PHP DocBlocks!
 */

global $_PATHCONFIG, $_DBCONFIG, $_CONFIG;
static $match = null;

/**
 * Define customizable constants
 */
define('ASCMS_ADMIN_PATH',                  ASCMS_DOCUMENT_ROOT. ASCMS_BACKEND_PATH);
define('ASCMS_ADMIN_WEB_PATH',              ASCMS_PATH_OFFSET. ASCMS_BACKEND_PATH);
// Cx::getCodeBaseAdminTemplatePath()
define('ASCMS_ADMIN_TEMPLATE_PATH',         ASCMS_DOCUMENT_ROOT. ASCMS_BACKEND_PATH.'/template/ascms');
// Cx::getCodeBaseAdminTemplateWebPath()
define('ASCMS_ADMIN_TEMPLATE_WEB_PATH',     ASCMS_PATH_OFFSET. ASCMS_BACKEND_PATH.'/template/ascms');
// Cx::getCoreFolderName()
define('ASCMS_CORE_FOLDER',                 '/core');
define('ASCMS_API_PATH',                    ASCMS_DOCUMENT_ROOT.ASCMS_CORE_FOLDER.'/API');
define('ASCMS_IMAGES_FOLDER',               '/images');
define('ASCMS_ATTACH_PATH',                 ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/attach');
define('ASCMS_ATTACH_WEB_PATH',             ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/attach');
// Cx::getLibraryFolderName()
define('ASCMS_LIBRARY_FOLDER',              '/lib');
// Cx::getCodeBaseFrameworkPath()
define('ASCMS_FRAMEWORK_PATH',              ASCMS_DOCUMENT_ROOT.ASCMS_LIBRARY_FOLDER.'/FRAMEWORK');
define('ASCMS_CALENDAR_IMAGE_PATH',         ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Calendar');
define('ASCMS_CALENDAR_IMAGE_WEB_PATH',     ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Calendar');
// Cx::getCodeBaseCorePath()
define('ASCMS_CORE_PATH',                   ASCMS_DOCUMENT_ROOT.ASCMS_CORE_FOLDER);
define('ASCMS_CONTENT_IMAGE_PATH',          ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/content');
define('ASCMS_CONTENT_IMAGE_WEB_PATH',      ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/content');
define('ASCMS_FEED_PATH',                   ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/feed');
define('ASCMS_FEED_WEB_PATH',               ASCMS_INSTANCE_OFFSET.'/feed');
define('ASCMS_FORUM_UPLOAD_PATH',           ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/media/Forum/upload');
define('ASCMS_FORUM_UPLOAD_WEB_PATH',       ASCMS_INSTANCE_OFFSET.'/media/Forum/upload');
define('ASCMS_GALLERY_THUMBNAIL_WEB_PATH',  ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/gallery_thumbs');
define('ASCMS_GALLERY_THUMBNAIL_PATH',      ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/gallery_thumbs');
define('ASCMS_GALLERY_IMPORT_WEB_PATH',     ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/gallery_import');
define('ASCMS_GALLERY_IMPORT_PATH',         ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/gallery_import');
define('ASCMS_GALLERY_WEB_PATH',            ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Gallery');
define('ASCMS_GALLERY_PATH',                ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Gallery');
define('ASCMS_LANGUAGE_PATH',               ASCMS_DOCUMENT_ROOT.'/lang');
// Cx::getCodeBaseLibraryPath()
define('ASCMS_LIBRARY_PATH',                ASCMS_DOCUMENT_ROOT.ASCMS_LIBRARY_FOLDER);
define('ASCMS_MEDIA1_PATH',                 ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/media/archive1');
define('ASCMS_MEDIA1_WEB_PATH',             ASCMS_INSTANCE_OFFSET.'/media/archive1');
define('ASCMS_MEDIA2_PATH',                 ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/media/archive2');
define('ASCMS_MEDIA2_WEB_PATH',             ASCMS_INSTANCE_OFFSET.'/media/archive2');
define('ASCMS_MEDIA3_PATH',                 ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/media/archive3');
define('ASCMS_MEDIA3_WEB_PATH',             ASCMS_INSTANCE_OFFSET.'/media/archive3');
define('ASCMS_MEDIA4_PATH',                 ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/media/archive4');
define('ASCMS_MEDIA4_WEB_PATH',             ASCMS_INSTANCE_OFFSET.'/media/archive4');
define('ASCMS_MEDIA_PATH',                  ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/media');
define('ASCMS_MEDIA_WEB_PATH',              ASCMS_INSTANCE_OFFSET.'/media');
define('ASCMS_FILESHARING_PATH',            ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/media/FileSharing');
define('ASCMS_FILESHARING_WEB_PATH',        ASCMS_INSTANCE_OFFSET.'/media/FileSharing');
// Cx::getModuleFolderName()
define('ASCMS_MODULE_FOLDER',               '/modules');
// Cx::getCodeBaseModulePath()
define('ASCMS_MODULE_PATH',                 ASCMS_DOCUMENT_ROOT.ASCMS_MODULE_FOLDER);
// Cx::getCodeBaseModuleWebPath()
define('ASCMS_MODULE_WEB_PATH',             ASCMS_PATH_OFFSET.ASCMS_MODULE_FOLDER);
// Cx::getCoreModuleFolderName()
define('ASCMS_CORE_MODULE_FOLDER',          '/core_modules');
// Cx::getCodeBaseCoreModulePath()
define('ASCMS_CORE_MODULE_PATH',            ASCMS_DOCUMENT_ROOT.ASCMS_CORE_MODULE_FOLDER);
// Cx::getCodeBaseCoreModuleWebPath()
define('ASCMS_CORE_MODULE_WEB_PATH',        ASCMS_PATH_OFFSET.ASCMS_CORE_MODULE_FOLDER);
define('ASCMS_NEWSLETTER_ATTACH_PATH',      ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/attach');
define('ASCMS_NEWSLETTER_ATTACH_WEB_PATH',  ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/attach');
define('ASCMS_SHOP_IMAGES_PATH',            ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Shop');
define('ASCMS_SHOP_IMAGES_WEB_PATH',        ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Shop');
define('ASCMS_BLOG_IMAGES_PATH',            ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Blog');
define('ASCMS_BLOG_IMAGES_WEB_PATH',        ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Blog');
define('ASCMS_PODCAST_IMAGES_PATH',         ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Podcast');
define('ASCMS_PODCAST_IMAGES_WEB_PATH',     ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Podcast');
define('ASCMS_DOWNLOADS_IMAGES_PATH',       ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Downloads');
define('ASCMS_DOWNLOADS_IMAGES_WEB_PATH',   ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Downloads');
define('ASCMS_DATA_IMAGES_PATH',            ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/data');
define('ASCMS_DATA_IMAGES_WEB_PATH',        ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/data');
// Cx::getWebsiteThemesWebPath()
define('ASCMS_THEMES_WEB_PATH',             ASCMS_INSTANCE_OFFSET.'/themes');
// Cx::getWebsiteThemesPath()
define('ASCMS_THEMES_PATH',                 ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/themes');
define('ASCMS_ACCESS_PATH',                 ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Access');
define('ASCMS_ACCESS_WEB_PATH',             ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Access');
define('ASCMS_ACCESS_PROFILE_IMG_WEB_PATH', ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Access/profile');
define('ASCMS_ACCESS_PROFILE_IMG_PATH',     ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Access/profile');
define('ASCMS_ACCESS_PHOTO_IMG_WEB_PATH',   ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Access/photo');
define('ASCMS_ACCESS_PHOTO_IMG_PATH',       ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/Access/photo');
//define('ASCMS_THEMES_IMAGE_PATH',           ASCMS_DOCUMENT_ROOT.ASCMS_IMAGES_FOLDER.'/themes');
//define('ASCMS_THEMES_IMAGE_WEB_PATH',       ASCMS_PATH_OFFSET.ASCMS_IMAGES_FOLDER.'/themes');
define('ASCMS_IMAGE_PATH',                  ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'');
// Cx::getWebsiteTempPath()
define('ASCMS_TEMP_PATH',                   ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/tmp');
// Cx::getWebsiteTempWebPath()
define('ASCMS_TEMP_WEB_PATH',               ASCMS_INSTANCE_OFFSET.'/tmp');
define('ASCMS_DIR_PATH',                    ASCMS_DOCUMENT_ROOT.ASCMS_MODULE_FOLDER.'/Directory');
define('ASCMS_DIR_WEB_PATH',                ASCMS_PATH_OFFSET.ASCMS_MODULE_FOLDER.'/Directory');
define('ASCMS_DIRECTORY_FEED_PATH',         ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/media/Directory/feeds');
define('ASCMS_DIRECTORY_FEED_WEB_PATH',     ASCMS_INSTANCE_OFFSET.'/media/Directory/feeds');
define('ASCMS_MODULE_MEDIA_PATH',           ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/media/Directory');
define('ASCMS_MODULE_MEDIA_WEB_PATH',       ASCMS_INSTANCE_OFFSET.'/media/Directory');
define('ASCMS_MARKET_MEDIA_PATH',           ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.'/media/Market');
define('ASCMS_MARKET_MEDIA_WEB_PATH',       ASCMS_INSTANCE_OFFSET.'/media/Market');
define('ASCMS_CACHE_PATH',                  ASCMS_TEMP_PATH.'/cache');
define('ASCMS_ECARD_OPTIMIZED_PATH',        ASCMS_MEDIA_PATH.'/Ecard/ecards_optimized');
define('ASCMS_ECARD_OPTIMIZED_WEB_PATH',    ASCMS_MEDIA_WEB_PATH.'/Ecard/ecards_optimized');
define('ASCMS_ECARD_SEND_ECARDS_PATH',      ASCMS_MEDIA_PATH.'/Ecard/send_ecards');
define('ASCMS_ECARD_SEND_ECARDS_WEB_PATH',  ASCMS_MEDIA_WEB_PATH.'/Ecard/send_ecards');
define('ASCMS_ECARD_THUMBNAIL_PATH',        ASCMS_MEDIA_PATH.'/Ecard/thumbnails');
define('ASCMS_ECARD_THUMBNAIL_WEB_PATH',    ASCMS_MEDIA_WEB_PATH.'/Ecard/thumbnails');
// Cx::getModelFolderName()
define('ASCMS_MODEL_FOLDER',                '/model');
// Cx::getCodeBaseModelPath()
define('ASCMS_MODEL_PATH',                  ASCMS_DOCUMENT_ROOT.ASCMS_MODEL_FOLDER);
define('ASCMS_MODEL_PROXIES_PATH',          ASCMS_MODEL_PATH.'/proxies');
define('ASCMS_TESTING_FOLDER',              '/Testing');
define('ASCMS_APP_CACHE_FOLDER',            ASCMS_TEMP_PATH.'/appcache');
define('ASCMS_APP_CACHE_FOLDER_WEB_PATH',   ASCMS_TEMP_WEB_PATH.'/appcache');

// This is like the usual *_WEB_PATH, relative to ASCMS_PATH.
// Like this, only one path needs to be defined for each purpose,
// the new File class methods will prepend ASCMS_PATH themselves
// when needed.
define('ASCMS_MEDIADIR_IMAGES_WEB_PATH',    ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/MediaDir');
define('ASCMS_MEDIADIR_IMAGES_PATH',        ASCMS_INSTANCE_PATH.ASCMS_INSTANCE_OFFSET.ASCMS_IMAGES_FOLDER.'/MediaDir');

// For convenience, as seen in the frontend
define('ADMIN_SCRIPT_PATH',                 ASCMS_ADMIN_WEB_PATH.'/'.CONTREXX_DIRECTORY_INDEX);

// Testing
define('ASCMS_TEST_PATH',                   ASCMS_DOCUMENT_ROOT.'/testing');

/**
 * International and localized date and time formats
 */
define('ASCMS_DATE_FORMAT_INTERNATIONAL_DATE',      'Y-m-d');
define('ASCMS_DATE_FORMAT_INTERNATIONAL_TIME',      'H:i:s');
define('ASCMS_DATE_FORMAT_INTERNATIONAL_DATETIME',  'Y-m-d H:i:s');

// TODO: Localize.
define('ASCMS_DATE_FORMAT',             'H:i:s d.m.Y');
define('ASCMS_DATE_FORMAT_DATE',        'd.m.Y');
define('ASCMS_DATE_FORMAT_TIME',        'H:i:s');
define('ASCMS_DATE_FORMAT_DATETIME',    'd.m.Y H:i:s');

// Like "Mo, 09.10.2011"
define('ASCMS_DATE_FORMAT_DOW_D_M_Y', 'w, d.m.Y');
// TODO: More localized formats for numbers, currencies, etc.
// Use with sprintf() in your methods, i.e.:
define('ASCMS_CURRENCY_FORMAT_UNIT', '%2$s %1$.2f');
// Use the above with
//  sprintf(ASCMS_CURRENCY_FORMAT_UNIT, (float)$amount, (string)$unit)
// where $unit is something like "sFr.", "$", or "�"
define('ASCMS_CURRENCY_FORMAT_CODE', '%2$s %1$.2f');
// Use the above with
//  sprintf(ASCMS_CURRENCY_FORMAT_CODE, (float)$amount, (string)$code)
// where $code is something like "CHF", "USD", or "EUR"
define('ASCMS_CURRENCY_SEPARATOR_THOUSANDS', '\'');
define('ASCMS_CURRENCY_SEPARATOR_DECIMALS', '.');
define('ASCMS_NUMBER_SEPARATOR_THOUSANDS', '\'');
define('ASCMS_NUMBER_SEPARATOR_DECIMALS', '.');

// LinkManager constants
define('ASCMS_LINKMANAGER_CONTENT_HREF_QUERY','div#home-page a, div#page a, div#content a');
define('ASCMS_LINKMANAGER_CONTENT_IMG_QUERY','div#home-page img, div#page img, div#content img');
define('ASCMS_LINKMANAGER_CONTENT_PAGE_QUERY','div#home-page, div#page, div#content');
define('ASCMS_LINKMANAGER_NAVIGATION_QUERY','ul#navigation, ul.navigation');