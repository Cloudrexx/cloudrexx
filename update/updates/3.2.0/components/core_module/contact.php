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


function _contactUpdate()
{
    global $objUpdate, $_CONFIG;
    try {

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_contact_recipient',
            array(
                'id'     => array('type' => 'INT',          'notnull' => true, 'primary'     => true,      'auto_increment' => true),
                'id_form'=> array('type' => 'INT(11)',      'notnull' => true, 'default'     => 0),
                'name'   => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default'     => ''),
                'email'  => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default'     => ''),
                'sort'   => array('type' => 'INT(11)',      'notnull' => true, 'default'     => 0),
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_contact_form_field',
            array(
                'id'             => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'id_form'        => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'name'           => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => ''),
                'type'           => array('type' => 'ENUM(\'text\',\'label\',\'checkbox\',\'checkboxGroup\',\'date\',\'file\',\'multi_file\',\'hidden\',\'password\',\'radio\',\'select\',\'textarea\',\'recipient\')', 'notnull' => true, 'default' => 'text'),
                'attributes'     => array('type' => 'TEXT'),
                'is_required'    => array('type' => 'SET(\'0\',\'1\')', 'notnull' => true, 'default' => '0'),
                'check_type'     => array('type' => 'INT(3)', 'notnull' => true, 'default' => '1'),
                'order_id'       => array('type' => 'SMALLINT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0')
            )
        );




        /****************************
        * ADDED:    Contrexx v3.0.0 *
        ****************************/
        /*
         * Create new table 'module_contact_form_field_lang'
         * to store language patameters of each field
         */
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_contact_form_field_lang',
            array(
                'id'             => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'fieldID'        => array('type' => 'INT(10)', 'unsigned' => true, 'after' => 'id'),
                'langID'         => array('type' => 'INT(10)', 'unsigned' => true, 'after' => 'fieldID'),
                'name'           => array('type' => 'VARCHAR(255)', 'after' => 'langID'),
                'attributes'     => array('type' => 'text', 'after' => 'name')
            ),
            array(
                'fieldID'        => array('fields' => array('fieldID','langID'), 'type' => 'UNIQUE')
            )
        );

        /*
         * Migrate name and attributes fields from 'module_contact_form_field' table
         * to 'module_contact_form_field_lang' table for active frontend language.
         * For other languages empty string
         */
        if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'module_contact_form_field', 'name') && \Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'module_contact_form', 'langId')) {
            $query     = "SELECT `field`.`id`, `field`.`id_form`, `field`.`name`, `field`.`attributes`, `form`.`langId`
                          FROM `".DBPREFIX."module_contact_form_field` AS `field`
                          JOIN `".DBPREFIX."module_contact_form` AS `form`
                          ON `form`.`id` = `field`.`id_form`";
            $objResult = \Cx\Lib\UpdateUtil::sql($query);
            if ($objResult) {
                while (!$objResult->EOF) {
                    $rowCountResult = \Cx\Lib\UpdateUtil::sql("SELECT 1
                                                        FROM `".DBPREFIX."module_contact_form_field_lang`
                                                        WHERE `fieldID` = ".$objResult->fields['id']."
                                                        AND `langID` = ".$objResult->fields['langId']."
                                                        LIMIT 1");

                    if ($rowCountResult->RecordCount() == 0) {
                        $query = "INSERT INTO `".DBPREFIX."module_contact_form_field_lang` (
                                 `fieldID`, `langID`, `name`, `attributes`
                                 ) VALUES (
                                 ".$objResult->fields['id'].",
                                 ".$objResult->fields['langId'].",
                                 '".addslashes($objResult->fields['name'])."',
                                 '".addslashes($objResult->fields['attributes'])."')";
                        \Cx\Lib\UpdateUtil::sql($query);
                    }

                    $objResult->MoveNext();
                }
            }
        }

        /*
         * Create table 'module_contact_recipient_lang'
         */
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_contact_recipient_lang',
            array(
                'id'            => array('type' => 'INT(10)',       'notnull' => true,  'unsigned' => true, 'primary' => true, 'auto_increment' => true),
                'recipient_id'  => array('type' => 'INT(10)',       'notnull' => true,  'unsigned' => true, 'after' => 'id'),
                'langID'        => array('type' => 'INT(11)',       'notnull' => true,  'after' => 'recipient_id'),
                'name'          => array('type' => 'VARCHAR(255)',  'after' => 'langID')
            ),
            array(
               'recipient_id'       => array('fields' => array('recipient_id','langID'), 'type' => 'UNIQUE')
            )
        );

        /*
         * Transfer recipientId and name from 'module_contact_recipient'
         * to 'module_contact_recipient_lang'
         */
        if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'module_contact_recipient', 'name')) {
            $query     = "SELECT `id`, `id_form`, `name` FROM `".DBPREFIX."module_contact_recipient`";
            $objResult = \Cx\Lib\UpdateUtil::sql($query);
            while (!$objResult->EOF) {
                $langId = 1;
                if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'module_contact_form', 'langId')) {
                    $query      = "SELECT `langId`
                                   FROM `".DBPREFIX."module_contact_form`
                                   WHERE `id` = ".$objResult->fields['id_form'];
                    $formLangId = \Cx\Lib\UpdateUtil::sql($query);
                    $langId     = ($formLangId->fields['langId'] != null) ? $formLangId->fields['langId'] : 1;
                } else {
                    $langId = 1;
                }

                /*
                 * Check for row already exsist
                 */
                $rowCountResult = \Cx\Lib\UpdateUtil::sql("SELECT 1 as count
                                                    FROM `".DBPREFIX."module_contact_recipient_lang`
                                                    WHERE `recipient_id` = ".$objResult->fields['id']."
                                                    AND `langID` = ".$langId."
                                                    LIMIT 1");
                if ($rowCountResult->RecordCount() == 0) {
                    $query = "INSERT INTO `".DBPREFIX."module_contact_recipient_lang` (
                              `recipient_id`, `langID`, `name`
                              ) VALUES (
                              ".$objResult->fields['id'].",
                              $langId,
                              '".addslashes($objResult->fields['name'])."')";
                    \Cx\Lib\UpdateUtil::sql($query);
                }

                $objResult->MoveNext();
            }
        }

        /*
         * Drop column 'recipient name' from 'module_contact_recipient'
         */
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_contact_recipient',
            array(
                'id'     => array('type' => 'INT(11)',      'notnull' => true, 'primary'     => true,   'auto_increment' => true),
                'id_form'=> array('type' => 'INT(11)',      'notnull' => true, 'default'     => 0,  'after' => 'id'),
                'email'  => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default'     => '', 'after' => 'id_form'),
                'sort'   => array('type' => 'INT(11)',      'notnull' => true, 'default'     => 0,  'after' => 'email')
            )
        );

        /*
         * Create new table 'module_contact_form_submit_data'
         */
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_contact_form_submit_data',
            array(
                'id'             => array('type' => 'INT(10)',  'unsigned' => true, 'notnull' => true,  'auto_increment' => true, 'primary' => true),
                'id_entry'       => array('type' => 'INT(10)',  'unsigned' => true, 'notnull' => true,  'after' => 'id'),
                'id_field'       => array('type' => 'INT(10)',  'unsigned' => true, 'notnull' => true,  'after' => 'id_entry'),
                'formlabel'      => array('type' => 'TEXT',     'after' => 'id_field'),
                'formvalue'      => array('type' => 'TEXT',     'after' => 'formlabel')
            )
        );

        /*
         * Transfer 'data' field of 'module_contact_form_data' table to 'field_label' and 'field_value'
         * in 'module_contact_form_submit_data' after base64 decoding
         * Fetch fieldId from 'module_contact_form_field_lang' table by matching fieldLabel
         */
        if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'module_contact_form_data', 'data')) {
           /*
            * Execute migrate script for every 30 dataset
            */
            $query     = "SELECT `id`, `id_form`, `data`
                          FROM `".DBPREFIX."module_contact_form_data`
                          WHERE `data` != ''
                          LIMIT 30";
            while (($objResult = \Cx\Lib\UpdateUtil::sql($query)) && $objResult->RecordCount()) {
                while (!$objResult->EOF) {
                    $fields_attr = explode(";",$objResult->fields['data']);

                    foreach ($fields_attr as $key => $value) {
                        $field_attr  = explode(",",$value);
                        $field_label = base64_decode($field_attr[0]);
                        $field_value = base64_decode($field_attr[1]);

                        /*
                         * In the contrexx 2.1.4, the recipient fields were stored as 'contactFormField_recipient' in the submitted data.
                         */
                        if ($field_label == "contactFormField_recipient" &&
                            \Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'module_contact_form_field', 'name')) {
                            $form_recipient_query = "SELECT `name`
                                                    FROM `".DBPREFIX."module_contact_form_field`
                                                    WHERE `type` = 'recipient'
                                                    AND `id_form` = ".$objResult->fields['id_form']."
                                                    LIMIT 1";
                            $formRecipientResult  = \Cx\Lib\UpdateUtil::sql($form_recipient_query);
                            $field_label          = $formRecipientResult->fields['name'];
                        }

                        $form_label_query = '
                            SELECT `lang`.`fieldID`
                            FROM `'.DBPREFIX.'module_contact_form_field` AS `field`
                            LEFT JOIN `'.DBPREFIX.'module_contact_form_field_lang` AS `lang` ON `lang`.`fieldID` = `field`.`id`
                            WHERE (`field`.`id_form` = '.$objResult->fields['id_form'].') AND (`lang`.`name` = "'.contrexx_raw2db($field_label).'")
                        ';
                        $formLabelResult  = \Cx\Lib\UpdateUtil::sql($form_label_query);
                        $fieldId          = ($formLabelResult->fields['fieldID'] != null) ? $formLabelResult->fields['fieldID'] : 0;

                        $submitCount = \Cx\Lib\UpdateUtil::sql("SELECT 1
                                                        FROM `".DBPREFIX."module_contact_form_submit_data`
                                                        WHERE `id_entry` = ".$objResult->fields['id']."
                                                        AND `id_field` = ".$fieldId."
                                                        LIMIT 1");

                        if ($submitCount->RecordCount() == 0) {
                            $submitQuery = "INSERT INTO `".DBPREFIX."module_contact_form_submit_data` (
                                      `id_entry`, `id_field`, `formlabel`, `formvalue`
                                      ) VALUES (
                                      ".$objResult->fields['id'].",
                                      ".$fieldId.",
                                      '".addslashes($field_label)."',
                                      '".addslashes($field_value)."')";
                            \Cx\Lib\UpdateUtil::sql($submitQuery);
                        }
                    }

                    /*
                     * Empty column 'data' of the current dataset
                     */
                    $updateQuery = "UPDATE `".DBPREFIX."module_contact_form_data`
                              SET `data` = ''
                              WHERE `id` = ". $objResult->fields['id'];
                    \Cx\Lib\UpdateUtil::sql($updateQuery);

                    $objResult->MoveNext();
                }
            }
        }

        /*
         * Alter table 'module_contact_form_data' by dropping 'data' field and adding 'id_lang'
         */
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_contact_form_data',
            array(
                'id'                => array('type' => 'INT(10)',       'notnull' => true,  'unsigned' => true, 'primary'     => true,  'auto_increment' => true),
                'id_form'           => array('type' => 'INT(10)',       'notnull' => true,  'unsigned' => true, 'default'     => 0, 'after' => 'id'),
                'id_lang'           => array('type' => 'INT(10)',       'notnull' => true,  'unsigned' => true, 'default'     => 1, 'after' => 'id_form'),
                'time'              => array('type' => 'INT(14)',       'notnull' => true,  'unsigned' => true, 'default'     => 0, 'after' => 'id_lang'),
                'host'              => array('type' => 'VARCHAR(255)',  'notnull' => true,  'after' => 'time'),
                'lang'              => array('type' => 'VARCHAR(64)',   'notnull' => true,  'after' => 'host'),
                'browser'           => array('type' => 'VARCHAR(255)',  'notnull' => true,  'after' => 'lang'),
                'ipaddress'         => array('type' => 'VARCHAR(15)',   'notnull' => true,  'after' => 'browser')
            )
        );

        /*
         * Alter table 'module_contact_form_field' by dropping name and attributes column
         * Add 'special_type' column
         */
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_contact_form_field',
            array(
                'id'             => array('type' => 'INT(10)',          'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'id_form'        => array('type' => 'INT(10)',          'unsigned' => true, 'notnull' => true, 'default' => 0, 'after' => 'id'),
                'type'           => array('type' => 'ENUM(\'text\',\'label\',\'checkbox\',\'checkboxGroup\',\'country\',\'date\',\'file\',\'multi_file\',\'fieldset\',\'hidden\',\'horizontalLine\',\'password\',\'radio\',\'select\',\'textarea\',\'recipient\',\'special\')', 'notnull' => true, 'default' => 'text', 'after' => 'id_form'),
                'special_type'   => array('type' => 'VARCHAR(20)',      'notnull' => true, 'after' => 'type'),
                'is_required'    => array('type' => 'SET(\'0\',\'1\')', 'notnull' => true,  'default' => '0', 'after' => 'special_type'),
                'check_type'     => array('type' => 'INT(3)',           'notnull' => true,  'default' => 1, 'after' => 'is_required'),
                'order_id'       => array('type' => 'SMALLINT(5)',      'notnull' => true,  'unsigned' => true, 'default' => 0, 'after' => 'check_type')
            )
        );

        /*
         * Update 'id_lang' column with form language id
         */
        if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'module_contact_form', 'langId')) {
            $query     = "SELECT `id`, `id_form` FROM `".DBPREFIX."module_contact_form_data`";
            $objResult = \Cx\Lib\UpdateUtil::sql($query);

            if ($objResult) {
                while (!$objResult->EOF) {
                    $query = "UPDATE `".DBPREFIX."module_contact_form_data`
                                SET `id_lang` = (
                                    SELECT `langId` from `".DBPREFIX."module_contact_form`
                                    WHERE `id` = ".$objResult->fields['id_form']."
                                ) WHERE `id` = ".$objResult->fields['id'];
                    \Cx\Lib\UpdateUtil::sql($query);
                    $objResult->MoveNext();
                }
            }
        }

        /*
         * Create table 'module_contact_form_lang'
         */
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_contact_form_lang',
            array(
                'id'             => array('type' => 'INT(10)',          'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'formID'         => array('type' => 'INT(10)',          'unsigned' => true, 'notnull' => true, 'after' => 'id'),
                'langID'         => array('type' => 'INT(10)',          'unsigned' => true, 'notnull' => true, 'after' => 'formID'),
                'is_active'      => array('type' => 'TINYINT(1)',       'unsigned' => true, 'notnull' => true,  'default' => 1, 'after' => 'langID'),
                'name'           => array('type' => 'VARCHAR(255)',     'after' => 'is_active'),
                'text'           => array('type' => 'TEXT',             'after' => 'name'),
                'feedback'       => array('type' => 'TEXT',             'after' => 'text'),
                'mailTemplate'   => array('type' => 'TEXT',             'after' => 'feedback'),
                'subject'        => array('type' => 'VARCHAR(255)',     'after' => 'mailTemplate')
            ),
            array(
                'formID'         => array('fields' => array('formID','langID'), 'type' => 'UNIQUE')
            )
        );

        /*
         * Migrate few fields from module_contact_form to module_contact_form_lang
         * and remaining fields to module_contact_form of new version
         */
        if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'module_contact_form', 'name')) {
            $query     = "SELECT `id`, `name`, `subject`, `text`, `feedback`, `langId`
                          FROM `".DBPREFIX."module_contact_form`";
            $objResult = \Cx\Lib\UpdateUtil::sql($query);
            while (!$objResult->EOF) {
                /*
                 * Check for row already exsist
                 */
                $rowCountResult = \Cx\Lib\UpdateUtil::sql("SELECT 1
                                                    FROM `".DBPREFIX."module_contact_form_lang`
                                                    WHERE `formID` = ".$objResult->fields['id']."
                                                    AND `langID` = ".$objResult->fields['langId']."
                                                    LIMIT 1");

                if ($rowCountResult->RecordCount() == 0) {
                    $formLangQuery = "INSERT INTO `".DBPREFIX."module_contact_form_lang` (
                            `formID`,
                            `langID`,
                            `is_active`,
                            `name`,
                            `text`,
                            `feedback`,
                            `mailTemplate`,
                            `subject`
                        ) VALUES (
                            ".$objResult->fields['id'].",
                            ".$objResult->fields['langId'].",
                            1,
                            '".addslashes($objResult->fields['name'])."',
                            '".addslashes($objResult->fields['text'])."',
                            '".addslashes($objResult->fields['feedback'])."',
                            '".addslashes('<table>
                                 <tbody>
                                 <!-- BEGIN form_field -->
                                    <tr>
                                        <td>
                                            [[FIELD_LABEL]]
                                        </td>
                                        <td>
                                            [[FIELD_VALUE]]
                                        </td>
                                    </tr>
                                 <!-- END form_field -->
                                 </tbody>
                             </table>')."',
                            '".addslashes($objResult->fields['subject'])."'
                        )";
                    \Cx\Lib\UpdateUtil::sql($formLangQuery);
                }

                $objResult->MoveNext();
            }
        }

        /*
         * Alter table 'module_contact_form' by dropping name, subject, feedback and form text
         * Add new column 'html_mail'
         */
        if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX . 'module_contact_form', 'html_mail')) {
            $htmlMailIsNew = false;
        } else {
            $htmlMailIsNew = true;
        }

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_contact_form',
            array(
                'id'                    => array('type' => 'INT(10)', 'notnull' => true,  'unsigned' => true, 'primary' => true,  'auto_increment' => true),
                'mails'                 => array('type' => 'TEXT', 'after' => 'id'),
                'showForm'              => array('type' => 'TINYINT(1)', 'notnull' => true, 'unsigned' => true, 'default' => 0, 'after' => 'mails'),
                'use_captcha'           => array('type' => 'TINYINT(1)', 'notnull' => true, 'unsigned' => true, 'default' => 1, 'after' => 'showForm'),
                'use_custom_style'      => array('type' => 'TINYINT(1)', 'notnull' => true, 'unsigned' => true, 'default' => 0, 'after' => 'use_captcha'),
                'send_copy'             => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => 0, 'after' => 'use_custom_style'),
                'use_email_of_sender'   => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => 0, 'after' => 'send_copy'),
                'html_mail'             => array('type' => 'TINYINT(1)', 'notnull' => true, 'unsigned' => true, 'default' => 1, 'after' => 'use_email_of_sender'),
                'send_attachment'       => array('type' => 'TINYINT(1)', 'notnull' => true, 'unsigned' => true, 'default' => '0'),
            )
        );

        if ($htmlMailIsNew) {
            \Cx\Lib\UpdateUtil::sql('UPDATE '.DBPREFIX.'module_contact_form SET html_mail = 0');
        }

        if (!$objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.1.5')) {
            // for all versions >= 2.2.0
            // change all fields currently set to 'file' to 'multi_file' ('multi_file' is same as former 'file' in previous versions)
            \Cx\Lib\UpdateUtil::sql("UPDATE `".DBPREFIX."module_contact_form_field` SET `type` = 'multi_file' WHERE `type` = 'file'");
        }

        /**
         * Update the content pages
         */
        if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.0')) {
            $em = \Env::get('em');
            $cl = \Env::get('ClassLoader');
            $cl->loadFile(ASCMS_CORE_MODULE_PATH . '/contact/admin.class.php');
            $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
            $Contact = new \ContactManager();
            $Contact->initContactForms();

            foreach ($Contact->arrForms as $id => $form) {
                foreach ($form['lang'] as $langId => $lang) {
                    if ($lang['is_active'] == true) {
                        $page = $pageRepo->findOneByModuleCmdLang('contact', $id, $langId);
                        if ($page) {
                            $page->setContent($Contact->_getSourceCode($id, $langId));
                            $page->setUpdatedAtToNow();
                            $em->persist($page);
                        }
                    }
                }
            }
            $em->flush();
        }


        /*******************************************
        * EXTENSION:    Database structure changes *
        * ADDED:        Contrexx v3.1.0            *
        *******************************************/
        if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {

            \Cx\Lib\UpdateUtil::table(
                DBPREFIX.'module_contact_form',
                array(
                    'id'                     => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                    'mails'                  => array('type' => 'text', 'after' => 'id'),
                    'showForm'               => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'mails'),
                    'use_captcha'            => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'showForm'),
                    'use_custom_style'       => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'use_captcha'),
                    'save_data_in_crm'       => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'use_custom_style'),
                    'send_copy'              => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'save_data_in_crm'),
                    'use_email_of_sender'    => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'send_copy'),
                    'html_mail'              => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'use_email_of_sender'),
                    'send_attachment'        => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'html_mail')
                )
            );

        }

    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    return true;
}
