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

function _updateSettings()
{
    global $objUpdate, $objDatabase, $_ARRAYLANG, $_CORELANG, $_CONFIG, $arrSettings, $arrSettingsByName;

// TODO: Unused
//    $setVars = false;

    $arrSettings = array(
        3    => array(
            'setname'    => 'dnsServer',
            'setvalue'    => 'ns1.contrexxdns.net',
            'setmodule'    => 1
        ),
        4    => array(
            'setname'    => 'bannerStatus',
            'setvalue'    => '0',
            'setmodule'    => 28
        ),
        5    => array(
            'setname'    => 'spamKeywords',
            'setvalue'    => 'sex, viagra',
            'setmodule'    => 1
        ),
        11    => array(
            'setname'    => 'coreAdminName',
            'setvalue'    => 'Webmaster',
            'setmodule'    => 1
        ),
        18    => array(
            'setname'    => 'corePagingLimit',
            'setvalue'    => '30',
            'setmodule'    => 1
        ),
        19    => array(
            'setname'    => 'searchDescriptionLength',
            'setvalue'    => '150',
            'setmodule'    => 5
        ),
        23    => array(
            'setname'    => 'coreIdsStatus',
            'setvalue'    => 'off',
            'setmodule'    => 1
        ),
        24    => array(
            'setname'    => 'coreAdminEmail',
            'setvalue'    => 'helpdesk@comvation.com',
            'setmodule'    => 1
        ),
        29    => array(
            'setname'    => 'contactFormEmail',
            'setvalue'    => 'helpdesk@comvation.com',
            'setmodule'    => 6
        ),
        34    => array(
            'setname'    => 'sessionLifeTime',
            'setvalue'    => '3600',
            'setmodule'    => 1
        ),
        35    => array(
            'setname'    => 'lastAccessId',
            'setvalue'    => '1',
            'setmodule'    => 1
        ),
        37    => array(
            'setname'    => 'newsTeasersStatus',
            'setvalue'    => '0',
            'setmodule'    => 8
        ),
        39    => array(
            'setname'    => 'feedNewsMLStatus',
            'setvalue'    => '0',
            'setmodule'    => 22
        ),
        40    => array(
            'setname'    => 'calendarheadlines',
            'setvalue'    => '1',
            'setmodule'    => 21
        ),
        41    => array(
            'setname'    => 'calendarheadlinescount',
            'setvalue'    => '5',
            'setmodule'    => 21
        ),
        42    => array(
            'setname'    => 'blockStatus',
            'setvalue'    => '1',
            'setmodule'    => 7
        ),
        44    => array(
            'setname'    => 'calendarheadlinescat',
            'setvalue'    => '0',
            'setmodule'    => 21
        ),
        45    => array(
            'setname'    => 'calendardefaultcount',
            'setvalue'    => '16',
            'setmodule'    => 21
        ),
        48    => array(
            'setname'    => 'blockRandom',
            'setvalue'    => '1',
            'setmodule'    => 7
        ),
        49    => array(
            'setname'    => 'directoryHomeContent',
            'setvalue'    => '0',
            'setmodule'    => 12
        ),
        50    => array(
            'setname'    => 'cacheEnabled',
            'setvalue'    => 'off',
            'setmodule'    => 1
        ),
        51    => array(
            'setname'    => 'coreGlobalPageTitle',
            'setvalue'    => 'Contrexx Example Page',
            'setmodule'    => 1
        ),
        52    => array(
            'setname'    => 'cacheExpiration',
            'setvalue'    => '86400',
            'setmodule'    => 1
        ),
        53    => array(
            'setname'    => 'domainUrl',
            'setvalue'    => 'localhost',
            'setmodule'    => 1
        ),
        54    => array(
            'setname'    => 'xmlSitemapStatus',
            'setvalue'    => 'off',
            'setmodule'    => 1
        ),
        55    => array(
            'setname'    => 'systemStatus',
            'setvalue'    => 'on',
            'setmodule'    => 1
        ),
        56    => array(
            'setname'    => 'searchVisibleContentOnly',
            'setvalue'    => 'on',
            'setmodule'    => 1
        ),
        57 => array(
            'setname'   => 'protocolHttpsFrontend',
            'setvalue'  => 'off',
            'setmodule' => 1
        ),
        58 => array(
            'setname'   => 'protocolHttpsBackend',
            'setvalue'  => 'off',
            'setmodule' => 1
        ),
        59 => array(
            'setname'   => 'forceDomainUrl',
            'setvalue'  => 'off',
            'setmodule' => 1
        ),
        60    => array(
            'setname'    => 'forumHomeContent',
            'setvalue'    => '0',
            'setmodule'    => 20
        ),
        62    => array(
            'setname'    => 'coreSmtpServer',
            'setvalue'    => '0',
            'setmodule'    => 1
        ),
        63    => array(
            'setname'    => 'languageDetection',
            'setvalue'    => 'on',
            'setmodule'    => 1
        ),
        64    => array(
            'setname'    => 'podcastHomeContent',
            'setvalue'    => '0',
            'setmodule'    => 35
        ),
        65    => array(
            'setname'    => 'googleMapsAPIKey',
            'setvalue'    => '',
            'setmodule'    => 1
        ),
        66    => array(
            'setname'    => 'forumTagContent',
            'setvalue'    => '0',
            'setmodule'    => 20
        ),
        68    => array(
            'setname'    => 'dataUseModule',
            'setvalue'    => '0',
            'setmodule'    => 48
        ),
        69    => array(
            'setname'    => 'frontendEditingStatus',
            'setvalue'    => 'off',
            'setmodule'    => 1
        ),
        71    => array(
            'setname'    => 'coreListProtectedPages',
            'setvalue'    => 'on',
            'setmodule'    => 1
        ),
        72    => array(
            'setname'    => 'useKnowledgePlaceholders',
            'setvalue'    => '0',
            'setmodule'    => 56
        ),
        73    => array(
            'setname'    => 'advancedUploadFrontend',
            'setvalue'    => 'off',
            'setmodule'    => 52
        ),
        74    => array(
            'setname'    => 'advancedUploadBackend',
            'setvalue'    => 'on',
            'setmodule'    => 52
        ),
        75    => array(
            'setname'    => 'installationId',
            'setvalue'    => '',
            'setmodule'    => 1
        ),
        76    => array(
            'setname'    => 'licenseKey',
            'setvalue'    => '',
            'setmodule'    => 1
        ),
        77    => array(
            'setname'    => 'contactCompany',
            'setvalue'    => 'Ihr Firmenname',
            'setmodule'    => 1
        ),
        78    => array(
            'setname'    => 'contactAddress',
            'setvalue'    => 'Musterstrasse 12',
            'setmodule'    => 1
        ),
        79    => array(
            'setname'    => 'contactZip',
            'setvalue'    => '3600',
            'setmodule'    => 1
        ),
        80    => array(
            'setname'    => 'contactPlace',
            'setvalue'    => 'Musterhausen',
            'setmodule'    => 1
        ),
        81    => array(
            'setname'    => 'contactCountry',
            'setvalue'    => 'Musterland',
            'setmodule'    => 1
        ),
        82    => array(
            'setname'    => 'contactPhone',
            'setvalue'    => '033 123 45 67',
            'setmodule'    => 1
        ),
        83    => array(
            'setname'    => 'contactFax',
            'setvalue'    => '033 123 45 68',
            'setmodule'    => 1
        ),
        84    => array(
            'setname'    => 'sessionLifeTimeRememberMe',
            'setvalue'    => '1209600',
            'setmodule'    => 1
        ),
        85    => array(
            'setname'    => 'dashboardNews',
            'setvalue'    => 'on',
            'setmodule'    => 1
        ),
        86    => array(
            'setname'    => 'dashboardStatistics',
            'setvalue'    => 'on',
            'setmodule'    => 1
        ),
        87    => array(
            'setname'    => 'timezone',
            'setvalue'    => 'Europe/Zurich',
            'setmodule'    => 1
        ),
        88    => array(
            'setname'    => 'googleAnalyticsTrackingId',
            'setvalue'    => '',
            'setmodule'    => 1
        ),
        89    => array(
            'setname'    => 'passwordComplexity',
            'setvalue'    => 'off',
            'setmodule'    => 1
        ),
        90    => array(
            'setname'    => 'licenseState',
            'setvalue'    => 'OK',
            'setmodule'    => 66
        ),
        91    => array(
            'setname'    => 'licenseValidTo',
            'setvalue'    => '',
            'setmodule'    => 66
        ),
        92    => array(
            'setname'    => 'coreCmsEdition',
            'setvalue'    => 'Trial',
            'setmodule'    => 66
        ),
        93    => array(
            'setname'    => 'licenseMessage',
            'setvalue'    => '',
            'setmodule'    => 66
        ),
        94    => array(
            'setname'    => 'licenseCreatedAt',
            'setvalue'    => '',
            'setmodule'    => 66
        ),
        95    => array(
            'setname'    => 'licenseDomains',
            'setvalue'    => '',
            'setmodule'    => 66
        ),
        96    => array(
            'setname'    => 'licenseGrayzoneMessages',
            'setvalue'    => '',
            'setmodule'    => 66
        ),
        97    => array(
            'setname'    => 'coreCmsVersion',
            'setvalue'    => '3.0.4',
            'setmodule'    => 66
        ),
        98    => array(
            'setname'    => 'coreCmsCodeName',
            'setvalue'    => 'Nikola Tesla',
            'setmodule'    => 66
        ),
        99    => array(
            'setname'    => 'coreCmsStatus',
            'setvalue'    => 'Stable',
            'setmodule'    => 66
        ),
        100    => array(
            'setname'    => 'coreCmsReleaseDate',
            'setvalue'    => '12.04.2013',
            'setmodule'    => 66
        ),
        101    => array(
            'setname'    => 'licensePartner',
            'setvalue'    => '',
            'setmodule'    => 66
        ),
        102    => array(
            'setname'    => 'licenseCustomer',
            'setvalue'    => '',
            'setmodule'    => 66
        ),
        103    => array(
            'setname'    => 'availableComponents',
            'setvalue'    => '',
            'setmodule'    => 66
        ),
        104    => array(
            'setname'    => 'upgradeUrl',
            'setvalue'    => 'http://license.contrexx.com/',
            'setmodule'    => 66
        ),
        105    => array(
            'setname'    => 'isUpgradable',
            'setvalue'    => 'off',
            'setmodule'    => 66
        ),
        106    => array(
            'setname'    => 'dashboardMessages',
            'setvalue'    => 'YToxOntzOjI6ImRlIjtPOjMxOiJDeFxDb3JlX01vZHVsZXNcTGljZW5zZVxNZXNzYWdlIjo2OntzOjQxOiIAQ3hcQ29yZV9Nb2R1bGVzXExpY2Vuc2VcTWVzc2FnZQBsYW5nQ29kZSI7czoyOiJkZSI7czozNzoiAEN4XENvcmVfTW9kdWxlc1xMaWNlbnNlXE1lc3NhZ2UAdGV4dCI7czo5MjoiU2llIGJlbnV0emVuIGRlbiBSZWxlYXNlIENhbmRpZGF0ZSB2b24gQ29udHJleHggMy4gS2xpY2tlbiBTaWUgaGllciB1bSBOZXVpZ2tlaXRlbiB6dSBzZWhlbiEiO3M6Mzc6IgBDeFxDb3JlX01vZHVsZXNcTGljZW5zZVxNZXNzYWdlAHR5cGUiO3M6MTA6Indhcm5pbmdib3giO3M6Mzc6IgBDeFxDb3JlX01vZHVsZXNcTGljZW5zZVxNZXNzYWdlAGxpbmsiO3M6MjE6ImluZGV4LnBocD9jbWQ9bGljZW5zZSI7czo0MzoiAEN4XENvcmVfTW9kdWxlc1xMaWNlbnNlXE1lc3NhZ2UAbGlua1RhcmdldCI7czo1OiJfc2VsZiI7czo0ODoiAEN4XENvcmVfTW9kdWxlc1xMaWNlbnNlXE1lc3NhZ2UAc2hvd0luRGFzaGJvYXJkIjtiOjE7fX0=',
            'setmodule'    => 66
        ),
        112    => array(
            'setname'    => 'coreCmsName',
            'setvalue'    => 'Contrexx',
            'setmodule'    => 66
        ),
        113    => array(
            'setname'    => 'useCustomizings',
            'setvalue'    => 'off',
            'setmodule'    => 1
        ),
        114    => array(
            'setname'    => 'licenseGrayzoneTime',
            'setvalue'    => '14',
            'setmodule'    => 66
        ),
        115    => array(
            'setname'    => 'licenseLockTime',
            'setvalue'    => '10',
            'setmodule'    => 66
        ),
        116    => array(
            'setname'    => 'licenseUpdateInterval',
            'setvalue'    => '24',
            'setmodule'    => 66
        ),
        117    => array(
            'setname'    => 'licenseFailedUpdate',
            'setvalue'    => '0',
            'setmodule'    => 66
        ),
        118    => array(
            'setname'    => 'licenseSuccessfulUpdate',
            'setvalue'    => '0',
            'setmodule'    => 66
        ),
                119     => array(
            'setname'    => 'cacheUserCache',
            'setvalue'    => 'off',
            'setmodule'    => 1
                ),
                120     => array(
            'setname'    => 'cacheOPCache',
            'setvalue'    => 'off',
            'setmodule'    => 1
                ),
                121     => array(
            'setname'    => 'cacheUserCacheMemcacheConfig',
            'setvalue'    => '{\"ip":\"127.0.0.1\",\"port\":11211}',
            'setmodule'    => 1
                ),
                122     => array(
            'setname'    => 'cacheProxyCacheVarnishConfig',
            'setvalue'    => '{\"ip":\"127.0.0.1\",\"port\":8080}',
            'setmodule'    => 1
                ),
    );

    $arrSettingsByName = array();
    foreach ($arrSettings as $setid => $data) {
        $arrSettingsByName[$data['setname']] = $setid;
    }


    // change googleSitemapStatus to xmlSitemapStatus
    $query = "SELECT 1 FROM `".DBPREFIX."settings` WHERE `setname`='googleSitemapStatus'";
    $objResult = $objDatabase->SelectLimit($query, 1);
    if ($objResult) {
        if ($objResult->RecordCount() == 1) {
            $query = "UPDATE `".DBPREFIX."settings` SET `setname` = 'xmlSitemapStatus' WHERE `setname` = 'googleSitemapStatus'";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    try {
        //remove fileuploader setting
        \Cx\Lib\UpdateUtil::sql('DELETE FROM '.DBPREFIX.'settings WHERE setid=70 AND setname="fileUploaderStatus"');
    }
    catch (\Cx\Lib\UpdateException $e) {
        DBG::trace();
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    foreach ($arrSettings as $setId => $arrSetting) {
        if (!_updateSettingsTable($setId, $arrSetting)) {
            return false;
        }
    }

    $query = "UPDATE `".DBPREFIX."settings` SET `setmodule`=1 WHERE `setmodule`=0";
    if ($objDatabase->Execute($query) === false) {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }


    //timezone (Contrexx 3.0.1)
    $arrTimezoneIdentifiers = timezone_identifiers_list();
    if (isset($_POST['timezone']) && array_key_exists($_POST['timezone'], $arrTimezoneIdentifiers)) {
        $_SESSION['contrexx_update']['update']['timezone'] = $_POST['timezone'];
    }
    if (isset($_SESSION['contrexx_update']['update']['timezone']) && array_key_exists(ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['update']['timezone']), $arrTimezoneIdentifiers)) {
        try {
            \Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'settings` SET `setvalue` = "'.$arrTimezoneIdentifiers[$_SESSION['contrexx_update']['update']['timezone']].'" WHERE `setname` = "timezone"');
            // add timezone to $_CONFIG array so it will be written in configuration.php in components/core/core.php
            $_CONFIG['timezone'] = $arrTimezoneIdentifiers[$_SESSION['contrexx_update']['update']['timezone']];
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    } else {
        $selected = -1;
        if (($defaultTimezoneId = array_search(@date_default_timezone_get(), $arrTimezoneIdentifiers)) && !empty($defaultTimezoneId)) {
            $selected = $defaultTimezoneId;
        }

        $options = '<option value="-1"'.($selected == -1 ? ' selected="selected"' : '').'>'.$_CORELANG['TXT_PLEASE_SELECT'].'</option>';
        foreach ($arrTimezoneIdentifiers as $id => $name) {
            $dateTimeZone = new DateTimeZone($name);
            $dateTime = new DateTime('now', $dateTimeZone);
            $timeOffset = $dateTimeZone->getOffset($dateTime);
            $sign = $timeOffset < 0 ? '-' : '+';
            $gmt = 'GMT '.$sign.gmdate('g:i', $timeOffset);
            $options .= '<option value="'.$id.'"'.($selected == $id ? ' selected="selected"' : '').'>'.$name.' ('.$gmt.')'.'</option>';
        }

        setUpdateMsg($_CORELANG['TXT_TIMEZONE'], 'title');
        setUpdateMsg($_CORELANG['TXT_TIMEZONE_INTRODUCTION'].' <select name="timezone">'.$options.'</select>', 'msg');
        setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_UPDATE_NEXT'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
        return false;
    }


    // write settings
    $strFooter = '';
    $arrModules = '';

    \Cx\Lib\FileSystem\FileSystem::makeWritable(ASCMS_DOCUMENT_ROOT.'/config/');

    if (!file_exists(ASCMS_DOCUMENT_ROOT.'/config/settings.php')) {
        if (!touch(ASCMS_DOCUMENT_ROOT.'/config/settings.php')) {
            setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_CREATE_SETTINGS_FILE'], ASCMS_DOCUMENT_ROOT.'/config/settings.php'));
            setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_DIR'], ASCMS_DOCUMENT_ROOT.'/config/', $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
            return false;
        }
    }

    \Cx\Lib\FileSystem\FileSystem::makeWritable(ASCMS_DOCUMENT_ROOT.'/config/settings.php');

    if (is_writable(ASCMS_DOCUMENT_ROOT.'/config/settings.php')) {
        try {
            $objFile = new \Cx\Lib\FileSystem\File(ASCMS_DOCUMENT_ROOT.'/config/settings.php');
            //Header & Footer
            $strHeader    = "<?php\n";
            $strHeader .= "/**\n";
            $strHeader .= "* This file is generated by the \"settings\"-menu in your CMS.\n";
            $strHeader .= "* Do not try to edit it manually!\n";
            $strHeader .= "*/\n\n";

            $strFooter .= "?>";

            //Get module-names
            $objResult = $objDatabase->Execute('SELECT    id, name FROM '.DBPREFIX.'modules');
            if ($objResult->RecordCount() > 0) {
                while (!$objResult->EOF) {
                    $arrModules[$objResult->fields['id']] = $objResult->fields['name'];
                    $objResult->MoveNext();
                }
            }

            //Get values
            $objResult = $objDatabase->Execute('SELECT        setname,
                                                            setmodule,
                                                            setvalue
                                                FROM        '.DBPREFIX.'settings
                                                ORDER BY    setmodule ASC,
                                                            setname ASC
                                            ');
            $intMaxLen = 0;
            $arrValues = array();
            while ($objResult && !$objResult->EOF) {
                $intMaxLen = (strlen($objResult->fields['setname']) > $intMaxLen) ? strlen($objResult->fields['setname']) : $intMaxLen;
                $arrValues[$objResult->fields['setmodule']][$objResult->fields['setname']] = $objResult->fields['setvalue'];
                $objResult->MoveNext();
            }
            $intMaxLen += strlen('$_CONFIG[\'\']') + 1; //needed for formatted output

            $fileContent = $strHeader;

            foreach ($arrValues as $intModule => $arrInner) {
                $fileContent .= "/**\n";
                $fileContent .= "* -------------------------------------------------------------------------\n";
                if (isset($arrModules[$intModule])) {
                    $fileContent .= "* ".ucfirst($arrModules[$intModule])."\n";
                } else {
                    $fileContent .= "* ".$intModule."\n";
                }
                $fileContent .= "* -------------------------------------------------------------------------\n";
                $fileContent .= "*/\n";

                foreach($arrInner as $strName => $strValue) {
                    $fileContent .= sprintf("%-".$intMaxLen."s",'$_CONFIG[\''.$strName.'\']');
                    $fileContent .= "= ";
                    $fileContent .= (is_numeric($strValue) ? $strValue : '"'.$strValue.'"').";\n";
                }
                $fileContent .= "\n";
            }

            $fileContent .= $strFooter;

            $objFile->write($fileContent);
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {}
    } else {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_WRITE_SETTINGS_FILE'], ASCMS_DOCUMENT_ROOT.'/config/settings.php'));
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_FILE'], ASCMS_DOCUMENT_ROOT.'/config/settings.php', $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
        return false;
    }

    $query = "
    ALTER TABLE ".DBPREFIX."settings
    CHANGE COLUMN setid setid integer(6) UNSIGNED NOT NULL auto_increment;
    ";
    if (!$objDatabase->Execute($query)) {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }


    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'settings_image',
            array(
                'id'         => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'name'       => array('type' => 'VARCHAR(50)', 'after' => 'id'),
                'value'      => array('type' => 'text', 'after' => 'name')
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.3')) {
        try {
            \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO `".DBPREFIX."settings_image` (`name`, `value`) VALUES (?, ?)", array('image_cut_width', '500'));
            \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO `".DBPREFIX."settings_image` (`name`, `value`) VALUES (?, ?)", array('image_cut_height', '500'));
            \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO `".DBPREFIX."settings_image` (`name`, `value`) VALUES (?, ?)", array('image_scale_width', '800'));
            \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO `".DBPREFIX."settings_image` (`name`, `value`) VALUES (?, ?)", array('image_scale_height', '800'));
            \Cx\Lib\UpdateUtil::sql("INSERT IGNORE INTO `".DBPREFIX."settings_image` (`name`, `value`) VALUES (?, ?)", array('image_compression', '100'));
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }


    return true;
}

function _updateSettingsTable($setId, $arrSetting)
{
    global $objDatabase, $arrSettings, $arrSettingsByName, $arrCurrentSettingsTable, $_CONFIG;

    if (!isset($arrCurrentSettingsTable)) {
        $arrCurrentSettingsTable = array();
    }

    $query = "SELECT setid FROM `".DBPREFIX."settings` WHERE `setname`='".$arrSetting['setname']."'";
    // select stored ID of option
    if (($objSettings = $objDatabase->SelectLimit($query, 1)) !== false) {
        if ($objSettings->RecordCount() == 0) {
            // option isn't yet present => ok, check if the associated ID isn't already used
            $query = "SELECT `setname` FROM `".DBPREFIX."settings` WHERE `setid` = ".intval($setId);
            if (($objSettings = $objDatabase->SelectLimit($query, 1)) !== false) {
                if ($objSettings->RecordCount() == 0) {
                    // option ID isn't already in use => ok, add it
                    $value = $arrSetting['setvalue'];

                    // we must set coreCmsVersion to the currently installed version,
                    // otherwise if we would set it to the new version,
                    // the update will be stopped before getting everything done
                    if ($arrSetting['setname'] == 'coreCmsVersion') {
                        $value = $_CONFIG['coreCmsVersion'];
                    }

                    $query = "INSERT INTO `".DBPREFIX."settings` ( `setid` , `setname` , `setvalue` , `setmodule` ) VALUES (".intval($setId).", '".$arrSetting['setname']."', '".$value."', '".intval($arrSetting['setmodule'])."')";
                    if ($objDatabase->Execute($query) !== false) {
                        return true;
                    } else {
                        return _databaseError($query, $objDatabase->ErrorMsg());
                    }
                } else {
                    // option ID is already in use => update the option who uses the wrong ID to it's right ID
                    $setname = $objSettings->fields['setname'];
                    if (in_array($setname, $arrCurrentSettingsTable)) {
                        // set a free ID which could be used as a temporary ID
                        $query = "SELECT MAX(`setid`) AS lastInsertId FROM `".DBPREFIX."settings`";
                        if (($objSettings = $objDatabase->SelectLimit($query, 1)) !== false) {
                            $query = "UPDATE `".DBPREFIX."settings` SET `setid` = ".($objSettings->fields['lastInsertId']+1)." WHERE `setid` = ".intval($setId);
                            // associated a temportary ID to the option who uses the wrong ID
                            if ($objDatabase->Execute($query) !== false) {
                                unset($arrCurrentSettingsTable[$setname]);
                                if (_updateSettingsTable($setId, $arrSetting)) {
                                    return true;
                                } else {
                                    return false;
                                }
                            } else {
                                return _databaseError($query, $objDatabase->ErrorMsg());
                            }
                        } else {
                            return _databaseError($query, $objDatabase->ErrorMsg());
                        }
                    } else {
                        $arrCurrentSettingsTable[] = $setname;
                        if (_updateSettingsTable($arrSettingsByName[$setname], $arrSettings[$arrSettingsByName[$setname]]) && _updateSettingsTable($setId, $arrSetting)) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            } else {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        } elseif ($objSettings->fields['setid'] != intval($setId)) {
            $currentSetId = $objSettings->fields['setid'];
            // option is already present but uses a wrong ID => check if the right associated ID of the option is already used by an other option
            $query = "SELECT `setname` FROM `".DBPREFIX."settings` WHERE `setid` = ".intval($setId);
            if (($objSettings = $objDatabase->SelectLimit($query, 1)) !== false) {
                if ($objSettings->RecordCount() == 0) {
                    // ID isn't already used => ok, set the correct ID of the option
                    $query = "UPDATE `".DBPREFIX."settings` SET `setid` = ".intval($setId)." WHERE `setid` = ".$currentSetId;
                    if ($objDatabase->Execute($query) !== false) {
                        return true;
                    } else {
                        return _databaseError($query, $objDatabase->ErrorMsg());
                    }
                } else {
                    // option ID is already in use => update the option who uses the wrong ID to it's right ID
                    $setname = $objSettings->fields['setname'];
                    if (in_array($setname, $arrCurrentSettingsTable)) {
                        // set a free ID which could be used as a temporary ID
                        $query = "SELECT MAX(`setid`) AS lastInsertId FROM `".DBPREFIX."settings`";
                        if (($objSettings = $objDatabase->SelectLimit($query, 1)) !== false) {
                            $query = "UPDATE `".DBPREFIX."settings` SET `setid` = ".($objSettings->fields['lastInsertId']+1)." WHERE `setid` = ".intval($setId);
                            // associated a temportary ID to the option who uses the wrong ID
                            if ($objDatabase->Execute($query) !== false) {
                                unset($arrCurrentSettingsTable[$setname]);
                                if (_updateSettingsTable($setId, $arrSetting)) {
                                    return true;
                                } else {
                                    return false;
                                }
                            } else {
                                return _databaseError($query, $objDatabase->ErrorMsg());
                            }
                        } else {
                            return _databaseError($query, $objDatabase->ErrorMsg());
                        }
                    } else {
                        $arrCurrentSettingsTable[] = $setname;
                        if (_updateSettingsTable($arrSettingsByName[$setname], $arrSettings[$arrSettingsByName[$setname]]) && _updateSettingsTable($setId, $arrSetting)) {
                            return true;
                        } else {
                            return false;
                        }
                    }
                }
            } else {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        } else {
            return true;
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }
}
