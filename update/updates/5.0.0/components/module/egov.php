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


function _egovUpdate()
{
    global $objDatabase, $_ARRAYLANG, $objUpdate, $_CONFIG;

    // Check required tables..
    $arrTables = $objDatabase->MetaTables('TABLES');
    if (!$arrTables) {
        setUpdateMsg($_ARRAYLANG['TXT_UNABLE_DETERMINE_DATABASE_STRUCTURE']);
        return false;
    }
    // Create new configuration table if missing
    if (!in_array(DBPREFIX."module_egov_configuration", $arrTables)) {
        $query = "
            CREATE TABLE ".DBPREFIX."module_egov_configuration (
              `name` varchar(255) NOT NULL default '',
              `value` text NOT NULL,
              UNIQUE KEY `name` (`name`)
            ) ENGINE=MyISAM;
        ";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    // Copy original values
    $arrField = array(
        'set_sender_name',
        'set_sender_email',
        'set_recipient_email',
        'set_state_subject',
        'set_state_email',
        'set_calendar_color_1',
        'set_calendar_color_2',
        'set_calendar_color_3',
        'set_calendar_legende_1',
        'set_calendar_legende_2',
        'set_calendar_legende_3',
        'set_calendar_background',
        'set_calendar_border',
        'set_calendar_date_label',
        'set_calendar_date_desc',
        'set_orderentry_subject',
        'set_orderentry_email',
        'set_orderentry_name',
        'set_orderentry_sender',
        'set_orderentry_recipient',
        'set_paypal_email',
        'set_paypal_currency',
        'set_paypal_ipn',
    );
    foreach ($arrField as $fieldname) {
        $query = "
            SELECT 1 FROM ".DBPREFIX."module_egov_configuration
            WHERE name='$fieldname'
        ";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
        if ($objResult->RecordCount() == 1) {
            // The value is already there
            continue;
        }

        // Copy the original value
        $query = "
            INSERT INTO ".DBPREFIX."module_egov_configuration (name, value)
            SELECT '$fieldname', `$fieldname`
              FROM ".DBPREFIX."module_egov_settings
        ";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    // Add new settings for Yellowpay
    $arrField = array(
        'yellowpay_accepted_payment_methods' => '',
        'yellowpay_authorization' => 'immediate',
        'yellowpay_uid' => 'demo',
        'yellowpay_hashseed' => 'demo',
        'yellowpay_shopid' => '',
        'yellowpay_use_testserver' => '1',
    );

    foreach ($arrField as $fieldname => $defaultvalue) {
        $query = "
            SELECT 1 FROM ".DBPREFIX."module_egov_configuration
            WHERE name='$fieldname'
        ";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
        if ($objResult->RecordCount() == 1) {
            // The value is already there
            continue;
        }

        // Add the new setting with its default value
        $query = "
            INSERT INTO ".DBPREFIX."module_egov_configuration (
                name, value
            ) VALUES (
                '$fieldname', '$defaultvalue'
            )
        ";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }


    // products table
    if (!in_array(DBPREFIX."module_egov_products", $arrTables)) {
        $query = "
            CREATE TABLE `".DBPREFIX."module_egov_products` (
              `product_id` int(11) NOT NULL auto_increment,
              `product_autostatus` tinyint(1) NOT NULL default '0',
              `product_name` varchar(255) NOT NULL default '',
              `product_desc` text NOT NULL,
              `product_price` decimal(11,2) NOT NULL default '0.00',
              `product_per_day` enum('yes','no') NOT NULL default 'no',
              `product_quantity` tinyint(2) NOT NULL default '0',
              `product_target_email` varchar(255) NOT NULL default '',
              `product_target_url` varchar(255) NOT NULL default '',
              `product_message` text NOT NULL,
              `product_status` tinyint(1) NOT NULL default '1',
              `product_electro` tinyint(1) NOT NULL default '0',
              `product_file` varchar(255) NOT NULL default '',
              `product_sender_name` varchar(255) NOT NULL default '',
              `product_sender_email` varchar(255) NOT NULL default '',
              `product_target_subject` varchar(255) NOT NULL,
              `product_target_body` text NOT NULL,
              `product_paypal` tinyint(1) NOT NULL default '0',
              `product_paypal_sandbox` varchar(255) NOT NULL default '',
              `product_paypal_currency` varchar(255) NOT NULL default '',
              `product_orderby` int(11) NOT NULL default '0',
              PRIMARY KEY  (`product_id`)
            ) TYPE=MyISAM;
        ";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    // Add Yellowpay field to Product table
    $arrProductColumns = $objDatabase->MetaColumns(DBPREFIX.'module_egov_products');
    if ($arrProductColumns === false) {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'module_egov_products'));
        return false;
    }
    if (!isset($arrProductColumns['YELLOWPAY'])) {
        $query = "
            ALTER TABLE ".DBPREFIX."module_egov_products
            ADD `yellowpay` TINYINT(1) unsigned NOT NULL default '0'
        ";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    // Add quantity limit field to Product table
    if (!isset($arrProductColumns['PRODUCT_QUANTITY_LIMIT'])) {
        $query = "
            ALTER TABLE ".DBPREFIX."module_egov_products
            ADD `product_quantity_limit` TINYINT(2) unsigned NOT NULL default '1'
            AFTER `product_quantity`;
        ";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    // Add alternative payment method name field to Product table
    if (!isset($arrProductColumns['ALTERNATIVE_NAMES'])) {
        $query = "
            ALTER TABLE ".DBPREFIX."module_egov_products
            ADD `alternative_names` TEXT NOT NULL;
        ";
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }


    /********************************
     * EXTENSION:   Timezone        *
     * ADDED:       Contrexx v3.0.0 *
     ********************************/
    try {
        \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'module_egov_orders` CHANGE `order_date` `order_date` TIMESTAMP NOT NULL DEFAULT "0000-00-00 00:00:00"');
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    // Add reservation date to order table
    if (!isset($arrORDERColumns['ORDER_RESERVATION_DATE'])) {
        try {
            \Cx\Lib\UpdateUtil::sql("ALTER TABLE ".DBPREFIX."module_egov_orders ADD `order_reservation_date` DATE NOT NULL DEFAULT '0000-00-00' AFTER `order_values`");
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }

    // set order_reservation_date
    try {
        $dateLabel = '';
        $objResult = \Cx\Lib\UpdateUtil::sql('SELECT value FROM '.DBPREFIX.'module_egov_configuration WHERE name = "set_calendar_date_label" LIMIT 1');
        if ($objResult->RecordCount()) {
            $dateLabel = $objResult->fields['value'];
        }
        if (!empty($dateLabel)) {
            $objResult = \Cx\Lib\UpdateUtil::sql('SELECT order_id, order_values FROM '.DBPREFIX.'module_egov_orders WHERE (order_reservation_date IS NULL OR order_reservation_date = "0000-00-00") AND order_values REGEXP "'.$dateLabel.'::[[:digit:]]+\.[[:digit:]]+\.[[:digit:]]+;;"');
            while (!$objResult->EOF) {
                $reservationDate = null;
                if (preg_match('/'.preg_quote($dateLabel).'::(\d+)\.(\d+)\.(\d+);;/', $objResult->fields['order_values'], $matches)) {
                    $day = $matches[1];
                    $month = $matches[2];
                    $year = $matches[3];
                    $reservationDate = "$year-$month-$day";
                    \Cx\Lib\UpdateUtil::sql('UPDATE '.DBPREFIX.'module_egov_orders SET order_reservation_date="'.$reservationDate.'" WHERE order_id='.$objResult->fields['order_id']);
                }
                $objResult->MoveNext();
            }
        }
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '5.0.0')) {
        try {
            \Cx\Lib\UpdateUtil::sql('UPDATE `' . DBPREFIX . 'module_egov_orders` SET `order_ip` = MD5(`order_ip`) WHERE CHAR_LENGTH(`order_ip`) < 30 AND `order_ip` != \'\'');
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }

    // migrate path to images and media
    $pathsToMigrate = \Cx\Lib\UpdateUtil::getMigrationPaths();
    $attributes = array(
        'product_desc'          => 'module_egov_products',
        'product_message'       => 'module_egov_products',
        'product_target_url'    => 'module_egov_products',
        'product_target_body'   => 'module_egov_products',
        'set_state_email'       => 'module_egov_settings',
    );
    try {
        foreach ($attributes as $attribute => $table) {
            foreach ($pathsToMigrate as $oldPath => $newPath) {
                \Cx\Lib\UpdateUtil::migratePath(
                    '`' . DBPREFIX . $table . '`',
                    '`' . $attribute . '`',
                    $oldPath,
                    $newPath
                );
            }
        }
    } catch (\Cx\Lib\Update_DatabaseException $e) {
        \DBG::log($e->getMessage());
        return false;
    }

    return true;
}
