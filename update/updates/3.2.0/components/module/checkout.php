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


function _checkoutUpdate()
{
    try {

        /*********************************
         * EXTENSION:   Initial creation *
         * ADDED:       Contrexx v3.0.0  *
         *********************************/
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_checkout_settings_general',
            array(
                'id'         => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'name'       => array('type' => 'VARCHAR(255)', 'notnull' => true, 'after' => 'id'),
                'value'      => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'name')
            )
        );
        \Cx\Lib\UpdateUtil::sql('INSERT INTO `'.DBPREFIX.'module_checkout_settings_general` (`id`, `name`, `value`) VALUES (1, "epayment_status", 1) ON DUPLICATE KEY UPDATE `id` = `id`');

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_checkout_settings_mails',
            array(
                'id'         => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'title'      => array('type' => 'TEXT', 'notnull' => true, 'after' => 'id'),
                'content'    => array('type' => 'TEXT', 'notnull' => true, 'after' => 'title')
            )
        );
        \Cx\Lib\UpdateUtil::sql('
            INSERT INTO `'.DBPREFIX.'module_checkout_settings_mails` (`id`, `title`, `content`)
            VALUES  (1, "[[DOMAIN_URL]] - Neue Zahlung", "Guten Tag<br />\r\n<br />\r\nAuf [[DOMAIN_URL]] wurde eine neue Zahlung abgewickelt:<br />\r\n<br />\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\r\n    <tbody>\r\n        <tr>\r\n            <td colspan=\"2\" nowrap=\"nowrap\" width=\"150\">\r\n                <strong>Angaben zur Transaktion</strong></td>\r\n        </tr>\r\n        <tr>\r\n            <td width=\"150\">\r\n                ID</td>\r\n            <td>\r\n                [[TRANSACTION_ID]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Status</td>\r\n            <td>\r\n                [[TRANSACTION_STATUS]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Datum und Uhrzeit</td>\r\n            <td>\r\n                [[TRANSACTION_TIME]]</td>\r\n        </tr>\r\n    </tbody>\r\n</table>\r\n<br />\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\r\n    <tbody>\r\n        <tr>\r\n            <td colspan=\"2\" nowrap=\"nowrap\" width=\"150\">\r\n                <strong>Angaben zur beglichenen Rechnung</strong></td>\r\n        </tr>\r\n        <tr>\r\n            <td width=\"150\">\r\n                Nummer</td>\r\n            <td>\r\n                [[INVOICE_NUMBER]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Betrag</td>\r\n            <td>\r\n                [[INVOICE_AMOUNT]] [[INVOICE_CURRENCY]]</td>\r\n        </tr>\r\n    </tbody>\r\n</table>\r\n<br />\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\r\n    <tbody>\r\n        <tr>\r\n            <td colspan=\"2\" nowrap=\"nowrap\" width=\"150\">\r\n                <strong>Angaben zur Kontaktperson</strong></td>\r\n        </tr>\r\n        <tr>\r\n            <td width=\"150\">\r\n                Anrede</td>\r\n            <td>\r\n                [[CONTACT_TITLE]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Vorname</td>\r\n            <td>\r\n                [[CONTACT_FORENAME]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Nachname</td>\r\n            <td>\r\n                [[CONTACT_SURNAME]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Firma</td>\r\n            <td>\r\n                [[CONTACT_COMPANY]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Strasse</td>\r\n            <td>\r\n                [[CONTACT_STREET]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                PLZ</td>\r\n            <td>\r\n                [[CONTACT_POSTCODE]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Ort</td>\r\n            <td>\r\n                [[CONTACT_PLACE]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Land</td>\r\n            <td>\r\n                [[CONTACT_COUNTRY]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Telefon</td>\r\n            <td>\r\n                [[CONTACT_PHONE]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                E-Mail-Adresse</td>\r\n            <td>\r\n                <a href=\"[[CONTACT_EMAIL]]?csrf=ODg4MTM2Nzg1&amp;csrf=NDQzMzAwNjE0\">[[CONTACT_EMAIL]]</a></td>\r\n        </tr>\r\n    </tbody>\r\n</table>\r\n<br />\r\nFreundliche Gr&uuml;sse<br />\r\nDas [[DOMAIN_URL]]&nbsp;Team"),
                    (2, "[[DOMAIN_URL]] - Zahlungsbestätigung", "Guten Tag<br />\r\n<br />\r\nGerne best&auml;tigen wir die erfolgreiche Abwicklung folgender Zahlung auf [[DOMAIN_URL]]:<br />\r\n<br />\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\r\n    <tbody>\r\n        <tr>\r\n            <td colspan=\"2\" nowrap=\"nowrap\" width=\"150\">\r\n                <strong>Angaben zur Transaktion</strong></td>\r\n        </tr>\r\n        <tr>\r\n            <td width=\"150\">\r\n                ID</td>\r\n            <td>\r\n                [[TRANSACTION_ID]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Status</td>\r\n            <td>\r\n                [[TRANSACTION_STATUS]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Datum und Uhrzeit</td>\r\n            <td>\r\n                [[TRANSACTION_TIME]]</td>\r\n        </tr>\r\n    </tbody>\r\n</table>\r\n<br />\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\r\n    <tbody>\r\n        <tr>\r\n            <td colspan=\"2\" nowrap=\"nowrap\" width=\"150\">\r\n                <strong>Angaben zur beglichenen Rechnung</strong></td>\r\n        </tr>\r\n        <tr>\r\n            <td width=\"150\">\r\n                Nummer</td>\r\n            <td>\r\n                [[INVOICE_NUMBER]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Betrag</td>\r\n            <td>\r\n                [[INVOICE_AMOUNT]] [[INVOICE_CURRENCY]]</td>\r\n        </tr>\r\n    </tbody>\r\n</table>\r\n<br />\r\n<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\r\n    <tbody>\r\n        <tr>\r\n            <td colspan=\"2\" nowrap=\"nowrap\" width=\"150\">\r\n                <strong>Angaben zur Kontaktperson</strong></td>\r\n        </tr>\r\n        <tr>\r\n            <td width=\"150\">\r\n                Anrede</td>\r\n            <td>\r\n                [[CONTACT_TITLE]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Vorname</td>\r\n            <td>\r\n                [[CONTACT_FORENAME]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Nachname</td>\r\n            <td>\r\n                [[CONTACT_SURNAME]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Firma</td>\r\n            <td>\r\n                [[CONTACT_COMPANY]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Strasse</td>\r\n            <td>\r\n                [[CONTACT_STREET]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                PLZ</td>\r\n            <td>\r\n                [[CONTACT_POSTCODE]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Ort</td>\r\n            <td>\r\n                [[CONTACT_PLACE]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Land</td>\r\n            <td>\r\n                [[CONTACT_COUNTRY]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                Telefon</td>\r\n            <td>\r\n                [[CONTACT_PHONE]]</td>\r\n        </tr>\r\n        <tr>\r\n            <td>\r\n                E-Mail-Adresse</td>\r\n            <td>\r\n                <a href=\"[[CONTACT_EMAIL]]?csrf=MTQ3ODg2NDkx&amp;csrf=ODg4NzYwNDE2\">[[CONTACT_EMAIL]]</a></td>\r\n        </tr>\r\n    </tbody>\r\n</table>\r\n<br />\r\nFreundliche Gr&uuml;sse<br />\r\nDas [[DOMAIN_URL]]&nbsp;Team")
            ON DUPLICATE KEY UPDATE `id` = `id`
        ');

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_checkout_settings_yellowpay',
            array(
                'id'         => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'name'       => array('type' => 'TEXT', 'notnull' => true, 'after' => 'id'),
                'value'      => array('type' => 'TEXT', 'notnull' => true, 'after' => 'name')
            )
        );
        \Cx\Lib\UpdateUtil::sql('
            INSERT INTO `'.DBPREFIX.'module_checkout_settings_yellowpay` (`id`, `name`, `value`)
            VALUES  (1, "pspid", "demoShop"),
                    (2, "sha_in", "sech10zeichenminimum"),
                    (3, "sha_out", "sech10zeichenminimum"),
                    (4, "testserver", 1),
                    (5, "operation", "SAL")
            ON DUPLICATE KEY UPDATE `id` = `id`
        ');

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_checkout_transactions',
            array(
                'id'                     => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'time'                   => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
                'status'                 => array('type' => 'ENUM(\'confirmed\',\'waiting\',\'cancelled\')', 'notnull' => true, 'after' => 'time'),
                'invoice_number'         => array('type' => 'VARCHAR(255)', 'notnull' => true, 'after' => 'status'),
                'invoice_currency'       => array('type' => 'INT(11)', 'notnull' => true, 'default' => '1', 'after' => 'invoice_number'),
                'invoice_amount'         => array('type' => 'INT(15)', 'notnull' => true, 'after' => 'invoice_currency'),
                'contact_title'          => array('type' => 'ENUM(\'mister\',\'miss\')', 'notnull' => true, 'after' => 'invoice_amount'),
                'contact_forename'       => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'contact_title'),
                'contact_surname'        => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'contact_forename'),
                'contact_company'        => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'contact_surname'),
                'contact_street'         => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'contact_company'),
                'contact_postcode'       => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'contact_street'),
                'contact_place'          => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'contact_postcode'),
                'contact_country'        => array('type' => 'INT(11)', 'notnull' => true, 'default' => '204', 'after' => 'contact_place'),
                'contact_phone'          => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'contact_country'),
                'contact_email'          => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'contact_phone')
            )
        );
        \Cx\Lib\UpdateUtil::sql('
            INSERT INTO `'.DBPREFIX.'module_checkout_transactions` (`id`, `time`, `status`, `invoice_number`, `invoice_currency`, `invoice_amount`, `contact_title`, `contact_forename`, `contact_surname`, `contact_company`, `contact_street`, `contact_postcode`, `contact_place`, `contact_country`, `contact_phone`, `contact_email`)
            VALUES (1, 1346661560, "confirmed", 987654321, 1, 48000, "mister", "Hans", "Muster", "Musterfirma", "Musterstrasse 123", 1234, "Musterort", 204, "012 345 67 89", "info@example.com")
            ON DUPLICATE KEY UPDATE `id` = `id`
        ');

    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }
}
