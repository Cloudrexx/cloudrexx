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


function _coreUpdate()
{
    global $objUpdate, $objDatabase, $_ARRAYLANG, $_CORELANG, $_CONFIG;

    $query = "SELECT `id` FROM `".DBPREFIX."languages` WHERE `charset` != 'UTF-8'";
    $objResult = $objDatabase->Execute($query);
    if ($objResult !== false) {
        while (!$objResult->EOF) {
            $query = "UPDATE `".DBPREFIX."languages` SET `charset` = 'UTF-8' WHERE `id`=".$objResult->fields['id'];
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
            $objResult->MoveNext();
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    $query = "SELECT `group_id` FROM `".DBPREFIX."access_group_static_ids` WHERE `access_id` = '5' GROUP BY `group_id`";
    $objGroup = $objDatabase->Execute($query);
    if ($objGroup !== false) {
        while (!$objGroup->EOF) {
            $query = "INSERT IGNORE INTO `".DBPREFIX."access_group_static_ids` (`access_id`, `group_id`) VALUES ('127', '".$objGroup->fields['group_id']."')";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
            $objGroup->MoveNext();
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    /*if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.0')) {
        $query = "SELECT `catid` FROM `".DBPREFIX."content_navigation`";
        $objContentNavigation = $objDatabase->Execute($query);
        if ($objContentNavigation !== false) {
            $arrContentSiteIds = array();
            while (!$objContentNavigation->EOF) {
                array_push($arrContentSiteIds, $objContentNavigation->fields['catid']);
                $objContentNavigation->MoveNext();
            }

            $query = "DELETE FROM `".DBPREFIX."content` WHERE `id` != ".implode(' AND `id` != ', $arrContentSiteIds);
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        } else {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }

        try {
            \Cx\Lib\UpdateUtil::table(
                DBPREFIX.'content_navigation',
                array(
                    'catid'                  => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                    'is_validated'           => array('type' => 'SET(\'0\',\'1\')', 'notnull' => true, 'default' => '1'),
                    'parcat'                 => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                    'catname'                => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => ''),
                    'target'                 => array('type' => 'VARCHAR(10)', 'notnull' => true, 'default' => ''),
                    'displayorder'           => array('type' => 'SMALLINT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '1000'),
                    'displaystatus'          => array('type' => 'SET(\'on\',\'off\')', 'notnull' => true, 'default' => 'on'),
                    'activestatus'           => array('type' => 'SET(\'0\',\'1\')', 'notnull' => true, 'default' => '1'),
                    'cachingstatus'          => array('type' => 'SET(\'0\',\'1\')', 'notnull' => true, 'default' => '1'),
                    'username'               => array('type' => 'VARCHAR(40)', 'notnull' => true, 'default' => ''),
                    'changelog'              => array('type' => 'INT(14)', 'notnull' => false),
                    'cmd'                    => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => ''),
                    'lang'                   => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1'),
                    'module'                 => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                    'startdate'              => array('type' => 'DATE', 'notnull' => true, 'default' => '0000-00-00'),
                    'enddate'                => array('type' => 'DATE', 'notnull' => true, 'default' => '0000-00-00'),
                    'protected'              => array('type' => 'TINYINT(4)', 'notnull' => true, 'default' => '0'),
                    'frontend_access_id'     => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                    'backend_access_id'      => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                    'themes_id'              => array('type' => 'INT(4)', 'notnull' => true, 'default' => '0'),
                    'css_name'               => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                    'custom_content'         => array('type' => 'VARCHAR(64)', 'after' => 'css_name', 'default' => '')
                ),
                array(
                    'parcat'                 => array('fields' => array('parcat')),
                    'module'                 => array('fields' => array('module')),
                    'catname'                => array('fields' => array('catname'))
                )
            );

            \Cx\Lib\UpdateUtil::table(
                DBPREFIX.'content_navigation_history',
                array(
                    'id'                     => array('type' => 'INT(7)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                    'is_active'              => array('type' => 'SET(\'0\',\'1\')', 'notnull' => true, 'default' => '0'),
                    'catid'                  => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                    'parcat'                 => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                    'catname'                => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => ''),
                    'target'                 => array('type' => 'VARCHAR(10)', 'notnull' => true, 'default' => ''),
                    'displayorder'           => array('type' => 'SMALLINT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '1000'),
                    'displaystatus'          => array('type' => 'SET(\'ON\',\'OFF\')', 'notnull' => true, 'default' => 'on'),
                    'activestatus'           => array('type' => 'SET(\'0\',\'1\')', 'notnull' => true, 'default' => '1'),
                    'cachingstatus'          => array('type' => 'SET(\'0\',\'1\')', 'notnull' => true, 'default' => '1'),
                    'username'               => array('type' => 'VARCHAR(40)', 'notnull' => true, 'default' => ''),
                    'changelog'              => array('type' => 'INT(14)', 'notnull' => false),
                    'cmd'                    => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => ''),
                    'lang'                   => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1'),
                    'module'                 => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                    'startdate'              => array('type' => 'DATE', 'notnull' => true, 'default' => '0000-00-00'),
                    'enddate'                => array('type' => 'DATE', 'notnull' => true, 'default' => '0000-00-00'),
                    'protected'              => array('type' => 'TINYINT(4)', 'notnull' => true, 'default' => '0'),
                    'frontend_access_id'     => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                    'backend_access_id'      => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                    'themes_id'              => array('type' => 'INT(4)', 'notnull' => true, 'default' => '0'),
                    'css_name'               => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                    'custom_content'         => array('type' => 'VARCHAR(64)', 'after' => 'css_name', 'default' => '')
                ),
                array(
                    'catid'                  => array('fields' => array('catid'))
                )
            );
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }*/



    // migrate modules table to new scheme
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'modules',
            array(
                'id'                         => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => false),
                'name'                       => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'id'),
                'distributor'                => array('type' => 'CHAR(50)', 'after' => 'name'),
                'description_variable'       => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => '', 'after' => 'distributor'),
                'status'                     => array('type' => 'SET(\'y\',\'n\')', 'notnull' => true, 'default' => 'n', 'after' => 'description_variable'),
                'is_required'                => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'status'),
                'is_core'                    => array('type' => 'TINYINT(4)', 'notnull' => true, 'default' => '0', 'after' => 'is_required'),
                'is_active'                  => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'is_core'),
                'is_licensed'                => array('type' => 'TINYINT(1)', 'after' => 'is_active')
            ),
            array(
                'id'                         => array('fields' => array('id'), 'type' => 'UNIQUE')
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }



    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'log',
            array(
                'id'                       => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'userid'                   => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => false),
                'datetime'                 => array('type' => 'datetime', 'notnull' => false, 'default' => '0000-00-00 00:00:00'),
                'useragent'                => array('type' => 'VARCHAR(250)', 'notnull' => false, 'default_expr' => 'NULL'),
                'userlanguage'             => array('type' => 'VARCHAR(250)', 'notnull' => false, 'default_expr' => 'NULL'),
                'remote_addr'              => array('type' => 'VARCHAR(250)', 'notnull' => false, 'default_expr' => 'NULL'),
                'remote_host'              => array('type' => 'VARCHAR(250)', 'notnull' => false, 'default_expr' => 'NULL'),
                'http_via'                 => array('type' => 'VARCHAR(250)'),
                'http_client_ip'           => array('type' => 'VARCHAR(250)'),
                'http_x_forwarded_for'     => array('type' => 'VARCHAR(250)'),
                'referer'                  => array('type' => 'VARCHAR(250)')
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    /**********************************************
     *                                            *
     * MIGRATE BACKEND_AREAS TO NEW ACCESS SYSTEM *
     * BUGFIX: Add UNIQUE key on access_id        *
     *                                            *
     *********************************************/
    try{
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'backend_areas',
            array(
                'area_id'            => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'parent_area_id'     => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'type'               => array('type' => 'ENUM(\'group\',\'function\',\'navigation\')', 'notnull' => false, 'default' => 'navigation'),
                'scope'              => array('type' => 'ENUM(\'global\',\'frontend\',\'backend\')', 'notnull' => true, 'default' => 'global', 'after' => 'type'),
                'area_name'          => array('type' => 'VARCHAR(100)', 'notnull' => false, 'default_expr' => 'NULL'),
                'is_active'          => array('type' => 'TINYINT(4)', 'notnull' => true, 'default' => '1'),
                'uri'                => array('type' => 'VARCHAR(255)'),
                'target'             => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => '_self'),
                'module_id'          => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'order_id'           => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'access_id'          => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'default' => '0')
            ),
            array(
                'area_name'          => array('fields' => array('area_name'))
            )
        );
    }
    catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }





    /*********************
     *
     * ADD COUNTRY TABLE
     *
     ********************/
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'lib_country',
            array(
                'id'             => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'name'           => array('type' => 'VARCHAR(64)', 'after' => 'id'),
                'iso_code_2'     => array('type' => 'CHAR(2)', 'after' => 'name'),
                'iso_code_3'     => array('type' => 'CHAR(3)', 'notnull' => false, 'after' => 'iso_code_2')
            ),
            array(
                'unique'         => array('fields' => array('iso_code_2'), 'type' => 'UNIQUE'),
                'INDEX_COUNTRIES_NAME' => array('fields' => array('name'))
            ),
            'InnoDB'
        );

        $arrCountries = array(
            1    => array(
                'name'    => 'Afghanistan',
                'iso_2'    => 'AF',
                'iso_3'    => 'AFG'
            ),
            2    => array(
                'name'    => 'Albania',
                'iso_2'    => 'AL',
                'iso_3'    => 'ALB'
            ),
            3    => array(
                'name'    => 'Algeria',
                'iso_2'    => 'DZ',
                'iso_3'    => 'DZA'
            ),
            4    => array(
                'name'    => 'American Samoa',
                'iso_2'    => 'AS',
                'iso_3'    => 'ASM'
            ),
            5    => array(
                'name'    => 'Andorra',
                'iso_2'    => 'AD',
                'iso_3'    => 'AND'
            ),
            6    => array(
                'name'    => 'Angola',
                'iso_2'    => 'AO',
                'iso_3'    => 'AGO'
            ),
            7    => array(
                'name'    => 'Anguilla',
                'iso_2'    => 'AI',
                'iso_3'    => 'AIA'
            ),
            8    => array(
                'name'    => 'Antarctica',
                'iso_2'    => 'AQ',
                'iso_3'    => 'ATA'
            ),
            9    => array(
                'name'    => 'Antigua and Barbuda',
                'iso_2'    => 'AG',
                'iso_3'    => 'ATG'
            ),
            10    => array(
                'name'    => 'Argentina',
                'iso_2'    => 'AR',
                'iso_3'    => 'ARG'
            ),
            11    => array(
                'name'    => 'Armenia',
                'iso_2'    => 'AM',
                'iso_3'    => 'ARM'
            ),
            12    => array(
                'name'    => 'Aruba',
                'iso_2'    => 'AW',
                'iso_3'    => 'ABW'
            ),
            13    => array(
                'name'    => 'Australia',
                'iso_2'    => 'AU',
                'iso_3'    => 'AUS'
            ),
            14    => array(
                'name'    => 'Österreich',
                'iso_2'    => 'AT',
                'iso_3'    => 'AUT'
            ),
            15    => array(
                'name'    => 'Azerbaijan',
                'iso_2'    => 'AZ',
                'iso_3'    => 'AZE'
            ),
            16    => array(
                'name'    => 'Bahamas',
                'iso_2'    => 'BS',
                'iso_3'    => 'BHS'
            ),
            17    => array(
                'name'    => 'Bahrain',
                'iso_2'    => 'BH',
                'iso_3'    => 'BHR'
            ),
            18    => array(
                'name'    => 'Bangladesh',
                'iso_2'    => 'BD',
                'iso_3'    => 'BGD'
            ),
            19    => array(
                'name'    => 'Barbados',
                'iso_2'    => 'BB',
                'iso_3'    => 'BRB'
            ),
            20    => array(
                'name'    => 'Belarus',
                'iso_2'    => 'BY',
                'iso_3'    => 'BLR'
            ),
            21    => array(
                'name'    => 'Belgium',
                'iso_2'    => 'BE',
                'iso_3'    => 'BEL'
            ),
            22    => array(
                'name'    => 'Belize',
                'iso_2'    => 'BZ',
                'iso_3'    => 'BLZ'
            ),
            23    => array(
                'name'    => 'Benin',
                'iso_2'    => 'BJ',
                'iso_3'    => 'BEN'
            ),
            24    => array(
                'name'    => 'Bermuda',
                'iso_2'    => 'BM',
                'iso_3'    => 'BMU'
            ),
            25    => array(
                'name'    => 'Bhutan',
                'iso_2'    => 'BT',
                'iso_3'    => 'BTN'
            ),
            26    => array(
                'name'    => 'Bolivia',
                'iso_2'    => 'BO',
                'iso_3'    => 'BOL'
            ),
            27    => array(
                'name'    => 'Bosnia and Herzegowina',
                'iso_2'    => 'BA',
                'iso_3'    => 'BIH'
            ),
            28    => array(
                'name'    => 'Botswana',
                'iso_2'    => 'BW',
                'iso_3'    => 'BWA'
            ),
            29    => array(
                'name'    => 'Bouvet Island',
                'iso_2'    => 'BV',
                'iso_3'    => 'BVT'
            ),
            30    => array(
                'name'    => 'Brazil',
                'iso_2'    => 'BR',
                'iso_3'    => 'BRA'
            ),
            31    => array(
                'name'    => 'British Indian Ocean Territory',
                'iso_2'    => 'IO',
                'iso_3'    => 'IOT'
            ),
            32    => array(
                'name'    => 'Brunei Darussalam',
                'iso_2'    => 'BN',
                'iso_3'    => 'BRN'
            ),
            33    => array(
                'name'    => 'Bulgaria',
                'iso_2'    => 'BG',
                'iso_3'    => 'BGR'
            ),
            34    => array(
                'name'    => 'Burkina Faso',
                'iso_2'    => 'BF',
                'iso_3'    => 'BFA'
            ),
            35    => array(
                'name'    => 'Burundi',
                'iso_2'    => 'BI',
                'iso_3'    => 'BDI'
            ),
            36    => array(
                'name'    => 'Cambodia',
                'iso_2'    => 'KH',
                'iso_3'    => 'KHM'
            ),
            37    => array(
                'name'    => 'Cameroon',
                'iso_2'    => 'CM',
                'iso_3'    => 'CMR'
            ),
            38    => array(
                'name'    => 'Canada',
                'iso_2'    => 'CA',
                'iso_3'    => 'CAN'
            ),
            39    => array(
                'name'    => 'Cape Verde',
                'iso_2'    => 'CV',
                'iso_3'    => 'CPV'
            ),
            40    => array(
                'name'    => 'Cayman Islands',
                'iso_2'    => 'KY',
                'iso_3'    => 'CYM'
            ),
            41    => array(
                'name'    => 'Central African Republic',
                'iso_2'    => 'CF',
                'iso_3'    => 'CAF'
            ),
            42    => array(
                'name'    => 'Chad',
                'iso_2'    => 'TD',
                'iso_3'    => 'TCD'
            ),
            43    => array(
                'name'    => 'Chile',
                'iso_2'    => 'CL',
                'iso_3'    => 'CHL'
            ),
            44    => array(
                'name'    => 'China',
                'iso_2'    => 'CN',
                'iso_3'    => 'CHN'
            ),
            45    => array(
                'name'    => 'Christmas Island',
                'iso_2'    => 'CX',
                'iso_3'    => 'CXR'
            ),
            46    => array(
                'name'    => 'Cocos (Keeling) Islands',
                'iso_2'    => 'CC',
                'iso_3'    => 'CCK'
            ),
            47    => array(
                'name'    => 'Colombia',
                'iso_2'    => 'CO',
                'iso_3'    => 'COL'
            ),
            48    => array(
                'name'    => 'Comoros',
                'iso_2'    => 'KM',
                'iso_3'    => 'COM'
            ),
            49    => array(
                'name'    => 'Congo',
                'iso_2'    => 'CG',
                'iso_3'    => 'COG'
            ),
            50    => array(
                'name'    => 'Cook Islands',
                'iso_2'    => 'CK',
                'iso_3'    => 'COK'
            ),
            51    => array(
                'name'    => 'Costa Rica',
                'iso_2'    => 'CR',
                'iso_3'    => 'CRI'
            ),
            52    => array(
                'name'    => 'Cote D\'Ivoire',
                'iso_2'    => 'CI',
                'iso_3'    => 'CIV'
            ),
            53    => array(
                'name'    => 'Croatia',
                'iso_2'    => 'HR',
                'iso_3'    => 'HRV'
            ),
            54    => array(
                'name'    => 'Cuba',
                'iso_2'    => 'CU',
                'iso_3'    => 'CUB'
            ),
            55    => array(
                'name'    => 'Cyprus',
                'iso_2'    => 'CY',
                'iso_3'    => 'CYP'
            ),
            56    => array(
                'name'    => 'Czech Republic',
                'iso_2'    => 'CZ',
                'iso_3'    => 'CZE'
            ),
            57    => array(
                'name'    => 'Denmark',
                'iso_2'    => 'DK',
                'iso_3'    => 'DNK'
            ),
            58    => array(
                'name'    => 'Djibouti',
                'iso_2'    => 'DJ',
                'iso_3'    => 'DJI'
            ),
            59    => array(
                'name'    => 'Dominica',
                'iso_2'    => 'DM',
                'iso_3'    => 'DMA'
            ),
            60    => array(
                'name'    => 'Dominican Republic',
                'iso_2'    => 'DO',
                'iso_3'    => 'DOM'
            ),
            61    => array(
                'name'    => 'East Timor',
                'iso_2'    => 'TP',
                'iso_3'    => 'TMP'
            ),
            62    => array(
                'name'    => 'Ecuador',
                'iso_2'    => 'EC',
                'iso_3'    => 'ECU'
            ),
            63    => array(
                'name'    => 'Egypt',
                'iso_2'    => 'EG',
                'iso_3'    => 'EGY'
            ),
            64    => array(
                'name'    => 'El Salvador',
                'iso_2'    => 'SV',
                'iso_3'    => 'SLV'
            ),
            65    => array(
                'name'    => 'Equatorial Guinea',
                'iso_2'    => 'GQ',
                'iso_3'    => 'GNQ'
            ),
            66    => array(
                'name'    => 'Eritrea',
                'iso_2'    => 'ER',
                'iso_3'    => 'ERI'
            ),
            67    => array(
                'name'    => 'Estonia',
                'iso_2'    => 'EE',
                'iso_3'    => 'EST'
            ),
            68    => array(
                'name'    => 'Ethiopia',
                'iso_2'    => 'ET',
                'iso_3'    => 'ETH'
            ),
            69    => array(
                'name'    => 'Falkland Islands (Malvinas)',
                'iso_2'    => 'FK',
                'iso_3'    => 'FLK'
            ),
            70    => array(
                'name'    => 'Faroe Islands',
                'iso_2'    => 'FO',
                'iso_3'    => 'FRO'
            ),
            71    => array(
                'name'    => 'Fiji',
                'iso_2'    => 'FJ',
                'iso_3'    => 'FJI'
            ),
            72    => array(
                'name'    => 'Finland',
                'iso_2'    => 'FI',
                'iso_3'    => 'FIN'
            ),
            73    => array(
                'name'    => 'France',
                'iso_2'    => 'FR',
                'iso_3'    => 'FRA'
            ),
            74    => array(
                'name'    => 'France, Metropolitan',
                'iso_2'    => 'FX',
                'iso_3'    => 'FXX'
            ),
            75    => array(
                'name'    => 'French Guiana',
                'iso_2'    => 'GF',
                'iso_3'    => 'GUF'
            ),
            76    => array(
                'name'    => 'French Polynesia',
                'iso_2'    => 'PF',
                'iso_3'    => 'PYF'
            ),
            77    => array(
                'name'    => 'French Southern Territories',
                'iso_2'    => 'TF',
                'iso_3'    => 'ATF'
            ),
            78    => array(
                'name'    => 'Gabon',
                'iso_2'    => 'GA',
                'iso_3'    => 'GAB'
            ),
            79    => array(
                'name'    => 'Gambia',
                'iso_2'    => 'GM',
                'iso_3'    => 'GMB'
            ),
            80    => array(
                'name'    => 'Georgia',
                'iso_2'    => 'GE',
                'iso_3'    => 'GEO'
            ),
            81    => array(
                'name'    => 'Deutschland',
                'iso_2'    => 'DE',
                'iso_3'    => 'DEU'
            ),
            82    => array(
                'name'    => 'Ghana',
                'iso_2'    => 'GH',
                'iso_3'    => 'GHA'
            ),
            83    => array(
                'name'    => 'Gibraltar',
                'iso_2'    => 'GI',
                'iso_3'    => 'GIB'
            ),
            84    => array(
                'name'    => 'Greece',
                'iso_2'    => 'GR',
                'iso_3'    => 'GRC'
            ),
            85    => array(
                'name'    => 'Greenland',
                'iso_2'    => 'GL',
                'iso_3'    => 'GRL'
            ),
            86    => array(
                'name'    => 'Grenada',
                'iso_2'    => 'GD',
                'iso_3'    => 'GRD'
            ),
            87    => array(
                'name'    => 'Guadeloupe',
                'iso_2'    => 'GP',
                'iso_3'    => 'GLP'
            ),
            88    => array(
                'name'    => 'Guam',
                'iso_2'    => 'GU',
                'iso_3'    => 'GUM'
            ),
            89    => array(
                'name'    => 'Guatemala',
                'iso_2'    => 'GT',
                'iso_3'    => 'GTM'
            ),
            90    => array(
                'name'    => 'Guinea',
                'iso_2'    => 'GN',
                'iso_3'    => 'GIN'
            ),
            91    => array(
                'name'    => 'Guinea-bissau',
                'iso_2'    => 'GW',
                'iso_3'    => 'GNB'
            ),
            92    => array(
                'name'    => 'Guyana',
                'iso_2'    => 'GY',
                'iso_3'    => 'GUY'
            ),
            93    => array(
                'name'    => 'Haiti',
                'iso_2'    => 'HT',
                'iso_3'    => 'HTI'
            ),
            94    => array(
                'name'    => 'Heard and Mc Donald Islands',
                'iso_2'    => 'HM',
                'iso_3'    => 'HMD'
            ),
            95    => array(
                'name'    => 'Honduras',
                'iso_2'    => 'HN',
                'iso_3'    => 'HND'
            ),
            96    => array(
                'name'    => 'Hong Kong',
                'iso_2'    => 'HK',
                'iso_3'    => 'HKG'
            ),
            97    => array(
                'name'    => 'Hungary',
                'iso_2'    => 'HU',
                'iso_3'    => 'HUN'
            ),
            98    => array(
                'name'    => 'Iceland',
                'iso_2'    => 'IS',
                'iso_3'    => 'ISL'
            ),
            99    => array(
                'name'    => 'India',
                'iso_2'    => 'IN',
                'iso_3'    => 'IND'
            ),
            100    => array(
                'name'    => 'Indonesia',
                'iso_2'    => 'ID',
                'iso_3'    => 'IDN'
            ),
            101    => array(
                'name'    => 'Iran (Islamic Republic of)',
                'iso_2'    => 'IR',
                'iso_3'    => 'IRN'
            ),
            102    => array(
                'name'    => 'Iraq',
                'iso_2'    => 'IQ',
                'iso_3'    => 'IRQ'
            ),
            103    => array(
                'name'    => 'Ireland',
                'iso_2'    => 'IE',
                'iso_3'    => 'IRL'
            ),
            104    => array(
                'name'    => 'Israel',
                'iso_2'    => 'IL',
                'iso_3'    => 'ISR'
            ),
            105    => array(
                'name'    => 'Italy',
                'iso_2'    => 'IT',
                'iso_3'    => 'ITA'
            ),
            106    => array(
                'name'    => 'Jamaica',
                'iso_2'    => 'JM',
                'iso_3'    => 'JAM'
            ),
            107    => array(
                'name'    => 'Japan',
                'iso_2'    => 'JP',
                'iso_3'    => 'JPN'
            ),
            108    => array(
                'name'    => 'Jordan',
                'iso_2'    => 'JO',
                'iso_3'    => 'JOR'
            ),
            109    => array(
                'name'    => 'Kazakhstan',
                'iso_2'    => 'KZ',
                'iso_3'    => 'KAZ'
            ),
            110    => array(
                'name'    => 'Kenya',
                'iso_2'    => 'KE',
                'iso_3'    => 'KEN'
            ),
            111    => array(
                'name'    => 'Kiribati',
                'iso_2'    => 'KI',
                'iso_3'    => 'KIR'
            ),
            112    => array(
                'name'    => 'Korea, Democratic People\'s Republic of',
                'iso_2'    => 'KP',
                'iso_3'    => 'PRK'
            ),
            113    => array(
                'name'    => 'Korea, Republic of',
                'iso_2'    => 'KR',
                'iso_3'    => 'KOR'
            ),
            114    => array(
                'name'    => 'Kuwait',
                'iso_2'    => 'KW',
                'iso_3'    => 'KWT'
            ),
            115    => array(
                'name'    => 'Kyrgyzstan',
                'iso_2'    => 'KG',
                'iso_3'    => 'KGZ'
            ),
            116    => array(
                'name'    => 'Lao People\'s Democratic Republic',
                'iso_2'    => 'LA',
                'iso_3'    => 'LAO'
            ),
            117    => array(
                'name'    => 'Latvia',
                'iso_2'    => 'LV',
                'iso_3'    => 'LVA'
            ),
            118    => array(
                'name'    => 'Lebanon',
                'iso_2'    => 'LB',
                'iso_3'    => 'LBN'
            ),
            119    => array(
                'name'    => 'Lesotho',
                'iso_2'    => 'LS',
                'iso_3'    => 'LSO'
            ),
            120    => array(
                'name'    => 'Liberia',
                'iso_2'    => 'LR',
                'iso_3'    => 'LBR'
            ),
            121    => array(
                'name'    => 'Libyan Arab Jamahiriya',
                'iso_2'    => 'LY',
                'iso_3'    => 'LBY'
            ),
            122    => array(
                'name'    => 'Liechtenstein',
                'iso_2'    => 'LI',
                'iso_3'    => 'LIE'
            ),
            123    => array(
                'name'    => 'Lithuania',
                'iso_2'    => 'LT',
                'iso_3'    => 'LTU'
            ),
            124    => array(
                'name'    => 'Luxembourg',
                'iso_2'    => 'LU',
                'iso_3'    => 'LUX'
            ),
            125    => array(
                'name'    => 'Macau',
                'iso_2'    => 'MO',
                'iso_3'    => 'MAC'
            ),
            126    => array(
                'name'    => 'Macedonia, The Former Yugoslav Republic of',
                'iso_2'    => 'MK',
                'iso_3'    => 'MKD'
            ),
            127    => array(
                'name'    => 'Madagascar',
                'iso_2'    => 'MG',
                'iso_3'    => 'MDG'
            ),
            128    => array(
                'name'    => 'Malawi',
                'iso_2'    => 'MW',
                'iso_3'    => 'MWI'
            ),
            129    => array(
                'name'    => 'Malaysia',
                'iso_2'    => 'MY',
                'iso_3'    => 'MYS'
            ),
            130    => array(
                'name'    => 'Maldives',
                'iso_2'    => 'MV',
                'iso_3'    => 'MDV'
            ),
            131    => array(
                'name'    => 'Mali',
                'iso_2'    => 'ML',
                'iso_3'    => 'MLI'
            ),
            132    => array(
                'name'    => 'Malta',
                'iso_2'    => 'MT',
                'iso_3'    => 'MLT'
            ),
            133    => array(
                'name'    => 'Marshall Islands',
                'iso_2'    => 'MH',
                'iso_3'    => 'MHL'
            ),
            134    => array(
                'name'    => 'Martinique',
                'iso_2'    => 'MQ',
                'iso_3'    => 'MTQ'
            ),
            135    => array(
                'name'    => 'Mauritania',
                'iso_2'    => 'MR',
                'iso_3'    => 'MRT'
            ),
            136    => array(
                'name'    => 'Mauritius',
                'iso_2'    => 'MU',
                'iso_3'    => 'MUS'
            ),
            137    => array(
                'name'    => 'Mayotte',
                'iso_2'    => 'YT',
                'iso_3'    => 'MYT'
            ),
            138    => array(
                'name'    => 'Mexico',
                'iso_2'    => 'MX',
                'iso_3'    => 'MEX'
            ),
            139    => array(
                'name'    => 'Micronesia, Federated States of',
                'iso_2'    => 'FM',
                'iso_3'    => 'FSM'
            ),
            140    => array(
                'name'    => 'Moldova, Republic of',
                'iso_2'    => 'MD',
                'iso_3'    => 'MDA'
            ),
            141    => array(
                'name'    => 'Monaco',
                'iso_2'    => 'MC',
                'iso_3'    => 'MCO'
            ),
            142    => array(
                'name'    => 'Mongolia',
                'iso_2'    => 'MN',
                'iso_3'    => 'MNG'
            ),
            143    => array(
                'name'    => 'Montserrat',
                'iso_2'    => 'MS',
                'iso_3'    => 'MSR'
            ),
            144    => array(
                'name'    => 'Morocco',
                'iso_2'    => 'MA',
                'iso_3'    => 'MAR'
            ),
            145    => array(
                'name'    => 'Mozambique',
                'iso_2'    => 'MZ',
                'iso_3'    => 'MOZ'
            ),
            146    => array(
                'name'    => 'Myanmar',
                'iso_2'    => 'MM',
                'iso_3'    => 'MMR'
            ),
            147    => array(
                'name'    => 'Namibia',
                'iso_2'    => 'NA',
                'iso_3'    => 'NAM'
            ),
            148    => array(
                'name'    => 'Nauru',
                'iso_2'    => 'NR',
                'iso_3'    => 'NRU'
            ),
            149    => array(
                'name'    => 'Nepal',
                'iso_2'    => 'NP',
                'iso_3'    => 'NPL'
            ),
            150    => array(
                'name'    => 'Netherlands',
                'iso_2'    => 'NL',
                'iso_3'    => 'NLD'
            ),
            151    => array(
                'name'    => 'Netherlands Antilles',
                'iso_2'    => 'AN',
                'iso_3'    => 'ANT'
            ),
            152    => array(
                'name'    => 'New Caledonia',
                'iso_2'    => 'NC',
                'iso_3'    => 'NCL'
            ),
            153    => array(
                'name'    => 'New Zealand',
                'iso_2'    => 'NZ',
                'iso_3'    => 'NZL'
            ),
            154    => array(
                'name'    => 'Nicaragua',
                'iso_2'    => 'NI',
                'iso_3'    => 'NIC'
            ),
            155    => array(
                'name'    => 'Niger',
                'iso_2'    => 'NE',
                'iso_3'    => 'NER'
            ),
            156    => array(
                'name'    => 'Nigeria',
                'iso_2'    => 'NG',
                'iso_3'    => 'NGA'
            ),
            157    => array(
                'name'    => 'Niue',
                'iso_2'    => 'NU',
                'iso_3'    => 'NIU'
            ),
            158    => array(
                'name'    => 'Norfolk Island',
                'iso_2'    => 'NF',
                'iso_3'    => 'NFK'
            ),
            159    => array(
                'name'    => 'Northern Mariana Islands',
                'iso_2'    => 'MP',
                'iso_3'    => 'MNP'
            ),
            160    => array(
                'name'    => 'Norway',
                'iso_2'    => 'NO',
                'iso_3'    => 'NOR'
            ),
            161    => array(
                'name'    => 'Oman',
                'iso_2'    => 'OM',
                'iso_3'    => 'OMN'
            ),
            162    => array(
                'name'    => 'Pakistan',
                'iso_2'    => 'PK',
                'iso_3'    => 'PAK'
            ),
            163    => array(
                'name'    => 'Palau',
                'iso_2'    => 'PW',
                'iso_3'    => 'PLW'
            ),
            164    => array(
                'name'    => 'Panama',
                'iso_2'    => 'PA',
                'iso_3'    => 'PAN'
            ),
            165    => array(
                'name'    => 'Papua New Guinea',
                'iso_2'    => 'PG',
                'iso_3'    => 'PNG'
            ),
            166    => array(
                'name'    => 'Paraguay',
                'iso_2'    => 'PY',
                'iso_3'    => 'PRY'
            ),
            167    => array(
                'name'    => 'Peru',
                'iso_2'    => 'PE',
                'iso_3'    => 'PER'
            ),
            168    => array(
                'name'    => 'Philippines',
                'iso_2'    => 'PH',
                'iso_3'    => 'PHL'
            ),
            169    => array(
                'name'    => 'Pitcairn',
                'iso_2'    => 'PN',
                'iso_3'    => 'PCN'
            ),
            170    => array(
                'name'    => 'Poland',
                'iso_2'    => 'PL',
                'iso_3'    => 'POL'
            ),
            171    => array(
                'name'    => 'Portugal',
                'iso_2'    => 'PT',
                'iso_3'    => 'PRT'
            ),
            172    => array(
                'name'    => 'Puerto Rico',
                'iso_2'    => 'PR',
                'iso_3'    => 'PRI'
            ),
            173    => array(
                'name'    => 'Qatar',
                'iso_2'    => 'QA',
                'iso_3'    => 'QAT'
            ),
            174    => array(
                'name'    => 'Reunion',
                'iso_2'    => 'RE',
                'iso_3'    => 'REU'
            ),
            175    => array(
                'name'    => 'Romania',
                'iso_2'    => 'RO',
                'iso_3'    => 'ROM'
            ),
            176    => array(
                'name'    => 'Russian Federation',
                'iso_2'    => 'RU',
                'iso_3'    => 'RUS'
            ),
            177    => array(
                'name'    => 'Rwanda',
                'iso_2'    => 'RW',
                'iso_3'    => 'RWA'
            ),
            178    => array(
                'name'    => 'Saint Kitts and Nevis',
                'iso_2'    => 'KN',
                'iso_3'    => 'KNA'
            ),
            179    => array(
                'name'    => 'Saint Lucia',
                'iso_2'    => 'LC',
                'iso_3'    => 'LCA'
            ),
            180    => array(
                'name'    => 'Saint Vincent and the Grenadines',
                'iso_2'    => 'VC',
                'iso_3'    => 'VCT'
            ),
            181    => array(
                'name'    => 'Samoa',
                'iso_2'    => 'WS',
                'iso_3'    => 'WSM'
            ),
            182    => array(
                'name'    => 'San Marino',
                'iso_2'    => 'SM',
                'iso_3'    => 'SMR'
            ),
            183    => array(
                'name'    => 'Sao Tome and Principe',
                'iso_2'    => 'ST',
                'iso_3'    => 'STP'
            ),
            184    => array(
                'name'    => 'Saudi Arabia',
                'iso_2'    => 'SA',
                'iso_3'    => 'SAU'
            ),
            185    => array(
                'name'    => 'Senegal',
                'iso_2'    => 'SN',
                'iso_3'    => 'SEN'
            ),
            186    => array(
                'name'    => 'Seychelles',
                'iso_2'    => 'SC',
                'iso_3'    => 'SYC'
            ),
            187    => array(
                'name'    => 'Sierra Leone',
                'iso_2'    => 'SL',
                'iso_3'    => 'SLE'
            ),
            188    => array(
                'name'    => 'Singapore',
                'iso_2'    => 'SG',
                'iso_3'    => 'SGP'
            ),
            189    => array(
                'name'    => 'Slovakia (Slovak Republic)',
                'iso_2'    => 'SK',
                'iso_3'    => 'SVK'
            ),
            190    => array(
                'name'    => 'Slovenia',
                'iso_2'    => 'SI',
                'iso_3'    => 'SVN'
            ),
            191    => array(
                'name'    => 'Solomon Islands',
                'iso_2'    => 'SB',
                'iso_3'    => 'SLB'
            ),
            192    => array(
                'name'    => 'Somalia',
                'iso_2'    => 'SO',
                'iso_3'    => 'SOM'
            ),
            193    => array(
                'name'    => 'South Africa',
                'iso_2'    => 'ZA',
                'iso_3'    => 'ZAF'
            ),
            194    => array(
                'name'    => 'South Georgia and the South Sandwich Islands',
                'iso_2'    => 'GS',
                'iso_3'    => 'SGS'
            ),
            195    => array(
                'name'    => 'Spain',
                'iso_2'    => 'ES',
                'iso_3'    => 'ESP'
            ),
            196    => array(
                'name'    => 'Sri Lanka',
                'iso_2'    => 'LK',
                'iso_3'    => 'LKA'
            ),
            197    => array(
                'name'    => 'St. Helena',
                'iso_2'    => 'SH',
                'iso_3'    => 'SHN'
            ),
            198    => array(
                'name'    => 'St. Pierre and Miquelon',
                'iso_2'    => 'PM',
                'iso_3'    => 'SPM'
            ),
            199    => array(
                'name'    => 'Sudan',
                'iso_2'    => 'SD',
                'iso_3'    => 'SDN'
            ),
            200    => array(
                'name'    => 'Suriname',
                'iso_2'    => 'SR',
                'iso_3'    => 'SUR'
            ),
            201    => array(
                'name'    => 'Svalbard and Jan Mayen Islands',
                'iso_2'    => 'SJ',
                'iso_3'    => 'SJM'
            ),
            202    => array(
                'name'    => 'Swaziland',
                'iso_2'    => 'SZ',
                'iso_3'    => 'SWZ'
            ),
            203    => array(
                'name'    => 'Sweden',
                'iso_2'    => 'SE',
                'iso_3'    => 'SWE'
            ),
            204    => array(
                'name'    => 'Schweiz',
                'iso_2'    => 'CH',
                'iso_3'    => 'CHE'
            ),
            205    => array(
                'name'    => 'Syrian Arab Republic',
                'iso_2'    => 'SY',
                'iso_3'    => 'SYR'
            ),
            206    => array(
                'name'    => 'Taiwan',
                'iso_2'    => 'TW',
                'iso_3'    => 'TWN'
            ),
            207    => array(
                'name'    => 'Tajikistan',
                'iso_2'    => 'TJ',
                'iso_3'    => 'TJK'
            ),
            208    => array(
                'name'    => 'Tanzania, United Republic of',
                'iso_2'    => 'TZ',
                'iso_3'    => 'TZA'
            ),
            209    => array(
                'name'    => 'Thailand',
                'iso_2'    => 'TH',
                'iso_3'    => 'THA'
            ),
            210    => array(
                'name'    => 'Togo',
                'iso_2'    => 'TG',
                'iso_3'    => 'TGO'
            ),
            211    => array(
                'name'    => 'Tokelau',
                'iso_2'    => 'TK',
                'iso_3'    => 'TKL'
            ),
            212    => array(
                'name'    => 'Tonga',
                'iso_2'    => 'TO',
                'iso_3'    => 'TON'
            ),
            213    => array(
                'name'    => 'Trinidad and Tobago',
                'iso_2'    => 'TT',
                'iso_3'    => 'TTO'
            ),
            214    => array(
                'name'    => 'Tunisia',
                'iso_2'    => 'TN',
                'iso_3'    => 'TUN'
            ),
            215    => array(
                'name'    => 'Turkey',
                'iso_2'    => 'TR',
                'iso_3'    => 'TUR'
            ),
            216    => array(
                'name'    => 'Turkmenistan',
                'iso_2'    => 'TM',
                'iso_3'    => 'TKM'
            ),
            217    => array(
                'name'    => 'Turks and Caicos Islands',
                'iso_2'    => 'TC',
                'iso_3'    => 'TCA'
            ),
            218    => array(
                'name'    => 'Tuvalu',
                'iso_2'    => 'TV',
                'iso_3'    => 'TUV'
            ),
            219    => array(
                'name'    => 'Uganda',
                'iso_2'    => 'UG',
                'iso_3'    => 'UGA'
            ),
            220    => array(
                'name'    => 'Ukraine',
                'iso_2'    => 'UA',
                'iso_3'    => 'UKR'
            ),
            221    => array(
                'name'    => 'United Arab Emirates',
                'iso_2'    => 'AE',
                'iso_3'    => 'ARE'
            ),
            222    => array(
                'name'    => 'United Kingdom',
                'iso_2'    => 'GB',
                'iso_3'    => 'GBR'
            ),
            223    => array(
                'name'    => 'United States',
                'iso_2'    => 'US',
                'iso_3'    => 'USA'
            ),
            224    => array(
                'name'    => 'United States Minor Outlying Islands',
                'iso_2'    => 'UM',
                'iso_3'    => 'UMI'
            ),
            225    => array(
                'name'    => 'Uruguay',
                'iso_2'    => 'UY',
                'iso_3'    => 'URY'
            ),
            226    => array(
                'name'    => 'Uzbekistan',
                'iso_2'    => 'UZ',
                'iso_3'    => 'UZB'
            ),
            227    => array(
                'name'    => 'Vanuatu',
                'iso_2'    => 'VU',
                'iso_3'    => 'VUT'
            ),
            228    => array(
                'name'    => 'Vatican City State (Holy See)',
                'iso_2'    => 'VA',
                'iso_3'    => 'VAT'
            ),
            229    => array(
                'name'    => 'Venezuela',
                'iso_2'    => 'VE',
                'iso_3'    => 'VEN'
            ),
            230    => array(
                'name'    => 'Viet Nam',
                'iso_2'    => 'VN',
                'iso_3'    => 'VNM'
            ),
            231    => array(
                'name'    => 'Virgin Islands (British)',
                'iso_2'    => 'VG',
                'iso_3'    => 'VGB'
            ),
            232    => array(
                'name'    => 'Virgin Islands (U.S.)',
                'iso_2'    => 'VI',
                'iso_3'    => 'VIR'
            ),
            233    => array(
                'name'    => 'Wallis and Futuna Islands',
                'iso_2'    => 'WF',
                'iso_3'    => 'WLF'
            ),
            234    => array(
                'name'    => 'Western Sahara',
                'iso_2'    => 'EH',
                'iso_3'    => 'ESH'
            ),
            235    => array(
                'name'    => 'Yemen',
                'iso_2'    => 'YE',
                'iso_3'    => 'YEM'
            ),
            236    => array(
                'name'    => 'Yugoslavia',
                'iso_2'    => 'YU',
                'iso_3'    => 'YUG'
            ),
            237    => array(
                'name'    => 'Zaire',
                'iso_2'    => 'ZR',
                'iso_3'    => 'ZAR'
            ),
            238    => array(
                'name'    => 'Zambia',
                'iso_2'    => 'ZM',
                'iso_3'    => 'ZMB'
            ),
            239    => array(
                'name'    => 'Zimbabwe',
                'iso_2'    => 'ZW',
                'iso_3'    => 'ZWE'
            )
        );

        foreach ($arrCountries as $countryId => $arrCountry) {
            \Cx\Lib\UpdateUtil::sql("INSERT INTO `".DBPREFIX."lib_country` VALUES (".$countryId.", '".addslashes($arrCountry['name'])."', '".$arrCountry['iso_2']."', '".$arrCountry['iso_3']."') ON DUPLICATE KEY UPDATE `id` = `id`");
        }
    } catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    try {
        /**********************************************************
        * NEW IN VERSION 2.1: templates for mobile devices!       *
        ***********************************************************/
        /**********************************************************
        * EXTENSION:    Fallback language and app device template *
        * ADDED:        Contrexx v3.0.0                           *
        ***********************************************************/
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'languages',
            array(
                'id'                     => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'lang'                   => array('type' => 'VARCHAR(5)', 'notnull' => true, 'default' => '', 'after' => 'id'),
                'name'                   => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'lang'),
                'charset'                => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => 'iso-8859-1', 'after' => 'name'),
                'themesid'               => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'charset'),
                'print_themes_id'        => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'themesid'),
                'pdf_themes_id'          => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'print_themes_id'),
                'frontend'               => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'pdf_themes_id'),
                'backend'                => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'frontend'),
                'is_default'             => array('type' => 'SET(\'true\',\'false\')', 'notnull' => true, 'default' => 'false', 'after' => 'backend'),
                'mobile_themes_id'       => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'is_default'),
                'fallback'               => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'mobile_themes_id'),
                'app_themes_id'          => array('type' => 'INT(2)', 'after' => 'fallback')
            ),
            array(
                'lang'                   => array('fields' => array('lang'), 'type' => 'UNIQUE'),
                'defaultstatus'          => array('fields' => array('is_default'))
            )
        );

        \Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'languages` SET `app_themes_id` = `themesid` WHERE `app_themes_id` = 0');
        \Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'languages` SET `mobile_themes_id` = `themesid` WHERE `mobile_themes_id` = 0');
    } catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    try {
        /********************************************************************
         * NEW IN VERSION 3.1.1: no fallback language should be possible
         ********************************************************************/
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'languages',
            array(
                'id'                     => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'lang'                   => array('type' => 'VARCHAR(5)', 'notnull' => true, 'default' => '', 'after' => 'id'),
                'name'                   => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'lang'),
                'charset'                => array('type' => 'VARCHAR(20)', 'notnull' => true, 'default' => 'iso-8859-1', 'after' => 'name'),
                'themesid'               => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'charset'),
                'print_themes_id'        => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'themesid'),
                'pdf_themes_id'          => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'print_themes_id'),
                'frontend'               => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'pdf_themes_id'),
                'backend'                => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'frontend'),
                'is_default'             => array('type' => 'SET(\'true\',\'false\')', 'notnull' => true, 'default' => 'false', 'after' => 'backend'),
                'mobile_themes_id'       => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'is_default'),
                'fallback'               => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => false, 'default' => '0', 'after' => 'mobile_themes_id'),
                'app_themes_id'          => array('type' => 'INT(2)', 'after' => 'fallback')
            ),
            array(
                'lang'                   => array('fields' => array('lang'), 'type' => 'UNIQUE'),
                'defaultstatus'          => array('fields' => array('is_default')),
                'name'                   => array('fields' => array('name')),
                'name_2'                 => array('fields' => array('name'), 'type' => 'FULLTEXT')
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    /**********************************************************
     * Add unique index on theme name. Who needs multiple
     * themes with the same name anyways? Are there people
     * who know the difference between "aaa" and "aaa"? Guess
     * not. It's just useless.
     * NOTE THIS KICKS OUT ALL DUPLICATE DESIGNS WITH THE
     * SAME NAME FROM THE DATABASE. WHICH I CONSIDER A
     * NECCESSARY EVIL.
     **********************************************************/
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'skins',
            array(
                'id'         => array('type' => 'INT(2) UNSIGNED',  'notnull' => true, 'primary' => true, 'auto_increment' => true),
                'themesname' => array('type' => 'VARCHAR(50)',      'notnull' => true),
                'foldername' => array('type' => 'VARCHAR(50)',      'notnull' => true),
                'expert'     => array('type' => 'INT(1)',           'notnull' => true, 'default' => '1'),
            ),
            array( # indexes
                'theme_unique'  => array( 'fields'=>array('themesname'), 'type'  =>'UNIQUE', 'force' => true),
                'folder_unique' => array( 'fields'=>array('foldername'), 'type'  =>'UNIQUE', 'force' => true),
            )
        );
    }
    catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    /***********************************************************
    * EXTENSION:    css class instead of id for auto generated *
    *               submenu in nested navigation               *
    * ADDED:        Contrexx v3.0.0                            *
    ************************************************************/
    function changeNestedNavigationCss($directory)
    {
        if ($handle = opendir($directory)) {
            while (($file = readdir($handle)) !== false) {
                if (!in_array($file, array('.', '..', '.svn'))) {
                    $file = $directory.'/'.$file;
                    if (is_dir($file)) {
                        changeNestedNavigationCss($file);
                    } else {
                        if (substr($file, -4) == '.css') {
                            try {
                                $objFile = new \Cx\Lib\FileSystem\File($file);
                                if (($data = $objFile->getData()) && !empty($data)) {
                                    $objFile->write(preg_replace('/#menu_level_(\d+)(?!([\w-_]+))/i', '.menu_level_\\1', $data));
                                }
                            } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                                \DBG::msg($e->getMessage());
                                return false;
                            }
                        }
                    }
                }
            }
            closedir($handle);
        }
    }

    $path = ASCMS_DOCUMENT_ROOT.'/themes';
    foreach (scandir($path) as $theme) {
        if (!in_array($theme, array('.', '..'))) {
            $theme = $path.'/'.$theme;
            $changeNestedNavigationCss = false;
            $navigationFiles = array('navbar.html', 'navbar2.html', 'navbar3.html', 'subnavbar.html', 'subnavbar2.html', 'subnavbar3.html');
            foreach ($navigationFiles as $file) {
                $file = $theme.'/'.$file;
                if (file_exists($file)) {
                    try {
                        $objFile = new \Cx\Lib\FileSystem\File($file);
                        if (($data = $objFile->getData()) && !empty($data)) {
                            if (strpos($data, 'nested_navigation') !== false) {
                                $changeNestedNavigationCss = true;
                            }
                        }
                    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                        \DBG::msg($e->getMessage());
                    }
                }
            }
            if ($changeNestedNavigationCss) {
                changeNestedNavigationCss($theme);
            }
        }
    }



    /*****************************************
     * EXTENSION:   {JAVASCRIPT} placeholder *
     * ADDED:       Contrexx v2.1.0     *
     *****************************************/
    $path = ASCMS_DOCUMENT_ROOT.'/themes';
    foreach (scandir($path) as $theme) {
        if (!in_array($theme, array('.', '..', '.svn'))) {
            $theme = $path.'/'.$theme;
            $file = $theme.'/index.html';
            if (file_exists($file)) {
                try {
                    $objFile = new \Cx\Lib\FileSystem\File($file);
                    if (($data = $objFile->getData()) && !empty($data)) {
                        if (strpos($data, '{JAVASCRIPT}') === false) {
                            $data = preg_replace('/(\s*)<\/head>/ms', "$1    {JAVASCRIPT}$1</head>", $data);
                            if ($data) {
                                $objFile->write($data);
                            }
                        }
                    }
                } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
                    \DBG::msg($e->getMessage());
                }
            }
        }
    }



    /********************************
     * EXTENSION:   Timezone        *
     * ADDED:       Contrexx v3.0.0 *
     ********************************/
    try {
        \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'log` CHANGE `datetime` `datetime` TIMESTAMP NULL DEFAULT "0000-00-00 00:00:00"');
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }



    /**********************************************************
    * EXTENSION:    Session garbage collector considers now   *
    *               the individual lifetime of each session   *
    *               (needed since remember me is implemented) *
    * ADDED:        Contrexx v3.0.1                           *
    ***********************************************************/
    try {
        $arrColumns = $objDatabase->MetaColumnNames(DBPREFIX.'sessions');
        if ($arrColumns === false) {
            setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'sessions'));
            return false;
        }

        if (!isset($arrColumns['REMEMBER_ME'])) {
            \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'sessions` ADD `remember_me` INT(1) NOT NULL DEFAULT 0 AFTER `sessionid`');
        }
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    /**********************************************************
    * EXTENSION:    Delete old cache class which causes       *
    *               classloader confliction.                  *
    * ADDED:        Contrexx v3.0.1                           *
    ***********************************************************/
    $path = ASCMS_DOCUMENT_ROOT.'/lib/FRAMEWORK/Cache.class.php';
    if (file_exists($path)) {
        if (!\Cx\Lib\FileSystem\FileSystem::delete_file($path)) {
            setUpdateMsg('Die Datei "/lib/FRAMEWORK/Cache.class.php" konnte nicht gelöscht werden. Bitte löschen Sie diese manuell.', 'error');
            setUpdateMsg('<input type="submit" value="'.$_CORELANG['TXT_UPDATE_TRY_AGAIN'].'" name="updateNext" /><input type="hidden" name="processUpdate" id="processUpdate" />', 'button');
            return false;
        }
    }



    /********************************************************
    * EXTENSION:    Add new access id (178) to those groups *
    *               having access to the news (10) or       *
    *               blog (119) module.                      *
    * ADDED:        Contrexx v3.1.0                         *
    ********************************************************/
    try {

        \Cx\Lib\UpdateUtil::sql('
            INSERT INTO `'.DBPREFIX.'access_group_static_ids` (`access_id`, `group_id`)
            SELECT 178, `group_id` FROM `'.DBPREFIX.'access_group_static_ids` WHERE (`access_id` = 10) OR (`access_id` = 119) GROUP BY `group_id`
            ON DUPLICATE KEY UPDATE `access_id` = `access_id`
        ');

    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }



    try {
        Text::errorHandler();
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
    try {
        \Cx\Core\Setting\Controller\Setting::errorHandler();
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    /********************************************************
    * EXTENSION:    ADD COMPONENTS TABLE                    *
    * ADDED:        Contrexx v3.1.0                         *
    ********************************************************/
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'component',
            array(
                'id'         => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'name'       => array('type' => 'VARCHAR(100)', 'after' => 'id'),
                'type'       => array('type' => 'ENUM(\'core\',\'core_module\',\'module\')', 'after' => 'name')
            ),
            null,
            'InnoDB'
        );
        \Cx\Lib\UpdateUtil::sql("
            INSERT IGNORE INTO `".DBPREFIX."component` (`id`, `name`, `type`) VALUES
                (70, 'Workbench', 'core_module'),
                (71, 'FrontendEditing', 'core_module'),
                (72, 'ContentManager', 'core')
        ");
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    return true;
}

function _writeNewConfigurationFile()
{
    global $_CORELANG, $_ARRAYLANG, $_DBCONFIG, $_PATHCONFIG, $_FTPCONFIG, $_CONFIGURATION, $_CONFIG;

    $ftpStatus = $_FTPCONFIG['is_activated'] ? 'true' : 'false';
    $charset = 'UTF-8';
    $dbCharset = 'utf8';

    $_FTPCONFIG['port'] = intval($_FTPCONFIG['port']);

    $configurationTpl = <<<CONFIG_TPL
<?php
global \$_DBCONFIG, \$_PATHCONFIG, \$_FTPCONFIG, \$_CONFIG;
/**
* @exclude
*
* Cloudrexx CMS Web Installer
* Please use the Cloudrexx CMS installer to configure this file
* or edit this file and configure the parameters for your site and
* database manually.
*/

/**
* -------------------------------------------------------------------------
* Set installation status
* -------------------------------------------------------------------------
*/
define('CONTREXX_INSTALLED', true);

/**
* -------------------------------------------------------------------------
* Database configuration section
* -------------------------------------------------------------------------
*/
\$_DBCONFIG['host'] = '{$_DBCONFIG['host']}'; // This is normally set to localhost
\$_DBCONFIG['database'] = '{$_DBCONFIG['database']}'; // Database name
\$_DBCONFIG['tablePrefix'] = '{$_DBCONFIG['tablePrefix']}'; // Database table prefix
\$_DBCONFIG['user'] = '{$_DBCONFIG['user']}'; // Database username
\$_DBCONFIG['password'] = '{$_DBCONFIG['password']}'; // Database password
\$_DBCONFIG['dbType'] = '{$_DBCONFIG['dbType']}';    // Database type (e.g. mysql,postgres ..)
\$_DBCONFIG['charset'] = '{$dbCharset}'; // Charset (default, latin1, utf8, ..)
\$_DBCONFIG['timezone'] = '{$_CONFIG['timezone']}'; // Controller's timezone for model
\$_DBCONFIG['collation'] = '{$_DBCONFIG['collation']}';

/**
* -------------------------------------------------------------------------
* Site path specific configuration
* -------------------------------------------------------------------------
*/
\$_PATHCONFIG['ascms_root'] = '';
\$_PATHCONFIG['ascms_root_offset'] = ''; // example: '/cms';
\$_PATHCONFIG['ascms_installation_root'] = \$_PATHCONFIG['ascms_root'];
\$_PATHCONFIG['ascms_installation_offset'] = \$_PATHCONFIG['ascms_root_offset']; // example: '/cms';

/**
* -------------------------------------------------------------------------
* Ftp specific configuration
* -------------------------------------------------------------------------
*/
\$_FTPCONFIG['is_activated'] = {$ftpStatus}; // Ftp support true or false
\$_FTPCONFIG['host']    = '{$_FTPCONFIG['host']}';// This is normally set to localhost
\$_FTPCONFIG['port'] = {$_FTPCONFIG['port']}; // Ftp remote port
\$_FTPCONFIG['username'] = '{$_FTPCONFIG['username']}'; // Ftp login username
\$_FTPCONFIG['password']    = '{$_FTPCONFIG['password']}'; // Ftp login password
\$_FTPCONFIG['path']    = '{$_FTPCONFIG['path']}'; // Ftp path to cms (must not include ascms_root_offset)

/**
* -------------------------------------------------------------------------
* Base setup (altering might break the system!)
* -------------------------------------------------------------------------
*/
// Set character encoding
\$_CONFIG['coreCharacterEncoding'] = 'UTF-8'; // example 'UTF-8'

CONFIG_TPL
;

    // write settings
    try {
            $objFile = new \Cx\Lib\FileSystem\File(ASCMS_DOCUMENT_ROOT.'/config/configuration.php');
            $objFile->makeWritable();
            $objFile->write($configurationTpl);
    } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            \DBG::msg($e->getMessage());
            setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_WRITE_SETTINGS_FILE'], ASCMS_DOCUMENT_ROOT.'/config/configuration.php'));
            setUpdateMsg(sprintf($_ARRAYLANG['TXT_SET_WRITE_PERMISSON_TO_FILE'], ASCMS_DOCUMENT_ROOT.'/config/configuration.php', $_CORELANG['TXT_UPDATE_TRY_AGAIN']), 'msg');
            return false;
    }

    return true;
}
