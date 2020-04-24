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
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @package     cloudrexx
 * @subpackage  core_config
 */
global $_ARRAYLANG;
$_ARRAYLANG['TXT_SETTINGS_MENU_SYSTEM'] = 'Sistem';
$_ARRAYLANG['TXT_SETTINGS_MENU_CACHE'] = 'Caching Cachleme( ara-gecici bellekleme) ';
$_ARRAYLANG['TXT_EMAIL_SERVER'] = 'E-mail server';
$_ARRAYLANG['TXT_SETTINGS_IMAGE'] = 'Bilder';
$_ARRAYLANG['TXT_LICENSE'] = 'License Management';
$_ARRAYLANG['TXT_SETTING_FTP_CONFIG_WARNING'] = 'There are wrong ftp credentials defined in the configurations file (%s) or the ftp connection is disabled. If you don"t fix this issue, cloudrexx probably doesn"t have access to upload or edit files and folders!';
$_ARRAYLANG['TXT_SETTINGS_ERROR_NO_WRITE_ACCESS'] = 'The file <strong>%s</strong> is write-protected!<br />No changes to the settings can be made until the write protection on that file has been removed!';
$_ARRAYLANG['TXT_SYSTEM_SETTINGS'] = 'Temelayarlar';
$_ARRAYLANG['TXT_ACTIVATED'] = 'Etkinlendi';
$_ARRAYLANG['TXT_DEACTIVATED'] = 'Etkinsiz';
$_ARRAYLANG['TXT_CORE_CONFIG_SITE'] = 'Karışık';
$_ARRAYLANG['TXT_CORE_CONFIG_ADMINISTRATIONAREA'] = 'Administration area';
$_ARRAYLANG['TXT_CORE_CONFIG_SECURITY'] = 'Security';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTINFORMATION'] = 'İrtibat bilgileri';
$_ARRAYLANG['TXT_SETTINGS_TITLE_DEVELOPMENT'] = 'Development tools';
$_ARRAYLANG['TXT_CORE_CONFIG_OTHERCONFIGURATIONS'] = 'Other configuration options';
$_ARRAYLANG['TXT_DEBUGGING_STATUS'] = 'Debugging mode';
$_ARRAYLANG['TXT_DEBUGGING_FLAGS'] = 'Flags';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_LOG'] = 'Messages / Events';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_PHP'] = 'PHP errors';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_DB'] = 'Database: All queries (incl. changes und failed queries)';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_DB_TRACE'] = 'Database: Trace queries';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_DB_CHANGE'] = 'Database: Changes (INSERT/UPDATE/DELETE)';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_DB_ERROR'] = 'Database: Failed queries';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_LOG_FILE'] = 'Output to file';
$_ARRAYLANG['TXT_SETTINGS_DEBUGGING_FLAG_LOG_FIREPHP'] = 'Output to FirePHP';
$_ARRAYLANG['TXT_DEBUGGING_EXPLANATION'] = 'Mode that helps cloudrexx developers with troubleshooting tasks. Activated for the currently logged in user only. Settings are discarded as soon as the session is closed.';
$_ARRAYLANG['TXT_SAVE'] = 'Kaydet';
$_ARRAYLANG['TXT_CORE_CONFIG_SYSTEMSTATUS'] = 'Sayfa durumu';
$_ARRAYLANG['TXT_CORE_CONFIG_SYSTEMSTATUS_TOOLTIP_HELP'] = 'Sayfa etkinlendi mi? - Status (on | off)';
$_ARRAYLANG['TXT_CORE_CONFIG_COREIDSSTATUS'] = 'Güvenlik sistemi';
$_ARRAYLANG['TXT_CORE_CONFIG_COREIDSSTATUS_TOOLTIP_HELP'] = 'Cloudrexx Intrusion Detection System - Reporting Status (on | off)';
$_ARRAYLANG['TXT_CORE_CONFIG_XMLSITEMAPSTATUS'] = 'XML Sitemap';
$_ARRAYLANG['TXT_CORE_CONFIG_XMLSITEMAPSTATUS_TOOLTIP_HELP'] = 'Otamatik XML Sitemap oluşturma - Status (on | off).';
$_ARRAYLANG['TXT_CORE_CONFIG_COREGLOBALPAGETITLE'] = 'Global Sayfa Başlığı';
$_ARRAYLANG['TXT_CORE_CONFIG_COREGLOBALPAGETITLE_TOOLTIP_HELP'] = 'Global Sayfa Başlığı.Bu sizin Webdesitenizde YERTUTUCUdan [GLOBAL_TITLE]]
entegre edililebilir. ';
$_ARRAYLANG['TXT_SETTINGS_DOMAIN_URL'] = 'Sitenizin Domain i ';
$_ARRAYLANG['TXT_SETTINGS_DOMAIN_URL_HELP'] = 'Buraya Sitenizin URL ismini verin. Lütfen bu adresin sonunda ( / )kesme işaretinin OLMAMASINA dikkat edin! ';
$_ARRAYLANG['TXT_CORE_CONFIG_COREPAGINGLIMIT'] = 'Sayfa başına veri';
$_ARRAYLANG['TXT_CORE_CONFIG_COREPAGINGLIMIT_TOOLTIP_HELP'] = '1 ve 200 arası mümkün.';
$_ARRAYLANG['TXT_CORE_CONFIG_SEARCHDESCRIPTIONLENGTH'] = 'Arama sonucundaki gösterilen karakterler .';
$_ARRAYLANG['TXT_CORE_CONFIG_SEARCHDESCRIPTIONLENGTH_TOOLTIP_HELP'] = 'Aramasonuçlarının karakter adeti';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIME'] = 'Oturum süresi';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIME_TOOLTIP_HELP'] = 'Sezon zamanı saniye olaraktır';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIMEREMEMBERME'] = 'Session length (remember me)';
$_ARRAYLANG['TXT_CORE_CONFIG_SESSIONLIFETIMEREMEMBERME_TOOLTIP_HELP'] = 'Session length in seconds for users which have set the checkbox "Remember me" at login.';
$_ARRAYLANG['TXT_CORE_CONFIG_DNSSERVER'] = 'DNS-Sunucusu';
$_ARRAYLANG['TXT_CORE_CONFIG_COREADMINNAME'] = 'Yöneticinin ismi';
$_ARRAYLANG['TXT_CORE_CONFIG_COREADMINEMAIL'] = 'Yöneticinin E-Mail Adresi ';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTFORMEMAIL'] = 'İrtibat formunun E-Mail Adresi  (Standart)';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTFORMEMAIL_TOOLTIP_HELP'] = 'Standart form için Alıcı Email adresi belirtilmedi.(id eksik).Birden fazla Alıcı adreslerini virgülle ayırın.';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTCOMPANY'] = 'Company';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTADDRESS'] = 'Address';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTZIP'] = 'ZIP-Code';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTPLACE'] = 'Place';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTCOUNTRY'] = 'Country';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTPHONE'] = 'Phone';
$_ARRAYLANG['TXT_CORE_CONFIG_CONTACTFAX'] = 'Fax';
$_ARRAYLANG['TXT_CORE_CONFIG_SEARCHVISIBLECONTENTONLY'] = 'Sadece görülebilinen sayfalarda ara';
$_ARRAYLANG['TXT_CORE_CONFIG_LANGUAGEDETECTION'] = 'Dili otomatik tanı';
$_ARRAYLANG['TXT_CORE_CONFIG_LANGUAGEDETECTION_TOOLTIP_HELP'] = 'Bu AYAR, Browserin(Tarayıcı) otomatik olarak standartdilini okuyup ve içerik(Content)dili seçmesini sağlar.';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEMAPSAPIKEY_TOOLTIP_HELP'] = 'Globaler Google-Map API Schlüssel für die Hauptdomain. <br />Neue Schlüssel können erstellt werden unter: http://code.google.com/apis/maps/signup.html';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEMAPSAPIKEY'] = 'Google-Map API Schlüssel';
$_ARRAYLANG['TXT_CORE_CONFIG_FRONTENDEDITINGSTATUS'] = 'Frontend Editing';
$_ARRAYLANG['TXT_CORE_CONFIG_FRONTENDEDITINGSTATUS_TOOLTIP_HELP'] = 'Mit Hilfe des Frontend Editing können Sie Ihre Seite auch ohne ein vorheriges Einloggen im Backend anpassen - Status (on | off).';
$_ARRAYLANG['TXT_CORE_CONFIG_USECUSTOMIZINGS'] = 'Customizing';
$_ARRAYLANG['TXT_CORE_CONFIG_USECUSTOMIZINGS_TOOLTIP_HELP'] = 'Use this option to activate customizings found in %1 - Status (on | off).';
$_ARRAYLANG['TXT_CORE_CONFIG_CORELISTPROTECTEDPAGES'] = 'List protected pages';
$_ARRAYLANG['TXT_CORE_CONFIG_CORELISTPROTECTEDPAGES_TOOLTIP_HELP'] = 'Defines if protected pages should be listed/included in the navigation, full text search, sitemap and XML-Sitemap if the user isn"t authenticated - Status  (on | off)';
$_ARRAYLANG['TXT_CORE_CONFIG_TIMEZONE'] = 'Timezone';
$_ARRAYLANG['TXT_CORE_CONFIG_DASHBOARDNEWS'] = 'Dashboard news';
$_ARRAYLANG['TXT_CORE_CONFIG_DASHBOARDSTATISTICS'] = 'Dashboard statistics';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEANALYTICSTRACKINGID'] = 'Google Analytics Tracking ID';
$_ARRAYLANG['TXT_CORE_CONFIG_GOOGLEANALYTICSTRACKINGID_TOOLTIP_HELP'] = 'Enter your Google Analytics tracking ID here. These can be found in your Google Analytics account under Admin => Tracking Code.';
$_ARRAYLANG['TXT_CORE_CONFIG_PASSWORDCOMPLEXITY'] = 'Passwords must meet the complexity requirements';
$_ARRAYLANG['TXT_CORE_CONFIG_PASSWORDCOMPLEXITY_TOOLTIP_HELP'] = 'Password must contain the following characters: upper and lower case character and number';
$_ARRAYLANG['TXT_CORE_CONFIG_ADVANCEDUPLOADBACKEND'] = 'Advanced uploading tools (administrator interface)';
$_ARRAYLANG['TXT_CORE_CONFIG_ADVANCEDUPLOADFRONTEND'] = 'Advanced uploading tools (frontend)';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEPROTOCOLFRONTEND_TOOLTIP_HELP'] = $_ARRAYLANG['TXT_CORE_CONFIG_FORCEPROTOCOLBACKEND_TOOLTIP_HELP'] = 'By default, Cloudrexx does not force the usage of a protocol. You have the option to change the setting to force HTTP in order to improve search engine ranking or to force HTTPS (Hypertext Transfer Protocol Secure), a secure protocol that additionally provides authenticated and encrypted communication. If your webserver doesn"t support HTTPS, Cloudrexx will reset this option to default.';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEPROTOCOLBACKEND'] = 'Protocol in use (administrator interface)';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEPROTOCOLFRONTEND'] = 'Protocol in use (frontend)';
$_ARRAYLANG['TXT_SETTINGS_FORCE_PROTOCOL_NONE'] = 'dynamic';
$_ARRAYLANG['TXT_SETTINGS_FORCE_PROTOCOL_HTTP'] = 'HTTP';
$_ARRAYLANG['TXT_SETTINGS_FORCE_PROTOCOL_HTTPS'] = 'HTTPS';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEDOMAINURL_TOOLTIP_HELP'] = 'Search engines interprets your homepage content as duplicated content as long as the homepage is accessible by multiple addresses. We recommend to activate this option.';
$_ARRAYLANG['TXT_CORE_CONFIG_FORCEDOMAINURL'] = 'Force the url of homepage';
$_ARRAYLANG['TXT_CORE_TIMEZONE_INVALID'] = 'The selected timezone is not valid.';
$_ARRAYLANG['TXT_SETTINGS_UPDATED'] = 'Ayarlar kaydedildi.';
$_ARRAYLANG['TXT_SETTINGS_ERROR_WRITABLE'] = 'Yazılamadı. Lütfen verinin (666)Yazım haklarını kontrol edin.';
$_ARRAYLANG['TXT_SETTINGS_DEFAULT_SMTP_CHANGED'] = 'E-mail gönderimi için SMTP hesabı %s artık Standart hesap olarak kullanılıyor. ';
$_ARRAYLANG['TXT_SETTINGS_CHANGE_DEFAULT_SMTP_FAILED'] = 'Standart hesabı değiştirmek hata verdi!';
$_ARRAYLANG['TXT_SETTINGS_SMTP_DELETE_SUCCEED'] = 'SMTP hesabı  %s başarıyla  silindi.';
$_ARRAYLANG['TXT_SETTINGS_SMTP_DELETE_FAILED'] = 'SMTP Hesabının %s silinmesinde  bir hata oluştu!';
$_ARRAYLANG['TXT_SETTINGS_COULD_NOT_DELETE_DEAULT_SMTP'] = 'SMTP hesabı %s silinemedi, çünkü o Standart hesap!';
$_ARRAYLANG['TXT_SETTINGS_EMAIL_ACCOUNTS'] = 'E-Mail hesapları';
$_ARRAYLANG['TXT_SETTINGS_ACCOUNT'] = 'Hesap';
$_ARRAYLANG['TXT_SETTINGS_HOST'] = 'Host';
$_ARRAYLANG['TXT_SETTINGS_USERNAME'] = 'Kullanıcı ismi';
$_ARRAYLANG['TXT_SETTINGS_STANDARD'] = 'Standart';
$_ARRAYLANG['TXT_SETTINGS_FUNCTIONS'] = 'Fonksiyonlar';
$_ARRAYLANG['TXT_SETTINGS_ADD_NEW_SMTP_ACCOUNT'] = 'Yeni SMTP hesabı ekle';
$_ARRAYLANG['TXT_SETTINGS_CONFIRM_DELETE_ACCOUNT'] = 'SMTP Hesabını %s gerçekten silmek mi istiyorsunuz?';
$_ARRAYLANG['TXT_SETTINGS_OPERATION_IRREVERSIBLE'] = 'Bu eylem geri dönüştürülmez!';
$_ARRAYLANG['TXT_SETTINGS_MODFIY'] = 'İncele';
$_ARRAYLANG['TXT_SETTINGS_DELETE'] = 'Sil';
$_ARRAYLANG['TXT_SETTINGS_EMPTY_ACCOUNT_NAME_TXT'] = 'Kullanıcı ismini tanımlayın!';
$_ARRAYLANG['TXT_SETTINGS_NOT_UNIQUE_SMTP_ACCOUNT_NAME'] = 'Başka bir Kullanıcı ismi kullanmanız gerekiyor, Çünkü bu %s kullanıcı isim zaten var  !';
$_ARRAYLANG['TXT_SETTINGS_EMPTY_SMTP_HOST_TXT'] = 'Bir SMTP Sunucu belirtin!';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_UPDATE_SUCCEED'] = 'SMTP hesabı  %s başarıyla etkinlendi.';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_UPDATE_FAILED'] = 'SMTP Hesabının %s güncellemesinde  bir hata oluştu!';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_ADD_SUCCEED'] = ' %s hesabı başarıyla eklendi.';
$_ARRAYLANG['TXT_SETTINGS_SMTP_ACCOUNT_ADD_FAILED'] = 'SMTP Hesabının eklenmesinde bir hata oluştu!';
$_ARRAYLANG['TXT_SETTINGS_MODIFY_SMTP_ACCOUNT'] = 'SMTP hesabını düzenle';
$_ARRAYLANG['TXT_SETTINGS_NAME_OF_ACCOUNT'] = 'Hesabın ismi';
$_ARRAYLANG['TXT_SETTINGS_SMTP_SERVER'] = 'SMTP Serveri';
$_ARRAYLANG['TXT_SETTINGS_PORT'] = 'Port';
$_ARRAYLANG['TXT_SETTINGS_AUTHENTICATION'] = 'Kimlik beyanı';
$_ARRAYLANG['TXT_SETTINGS_PASSWORD'] = 'Şifre';
$_ARRAYLANG['TXT_SETTINGS_SMTP_AUTHENTICATION_TXT'] = 'SMTP Sunucusu kimlik araştırması istemiyorsa buraya Kullanıcı ismi ve Şifre girmeyin.';
$_ARRAYLANG['TXT_SETTINGS_BACK'] = 'Geri';
$_ARRAYLANG['TXT_SETTINGS_SAVE'] = 'Kaydet';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_TITLE'] = 'Bildeinstellungen';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_CUT_WIDTH'] = 'Standardbreite für die Bildzuschneidung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_CUT_HEIGHT'] = 'Standardhöhe für die Bildzuschneidung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_SCALE_WIDTH'] = 'Standardbreite für die Skalierung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_SCALE_HEIGHT'] = 'Standardhöhe für die Skalierung';
$_ARRAYLANG['TXT_SETTINGS_IMAGE_COMPRESSION'] = 'Standardwert für die Kompression';
