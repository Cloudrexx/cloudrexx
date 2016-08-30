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


function _blogUpdate() {
    global $objDatabase, $_ARRAYLANG, $_CORELANG, $objUpdate, $_CONFIG;

    /*
     * Check for missing setting "blog_comments_editor" in database. In the update-package for 1.2 this value somehow
     * got lost.
     */
    $query = '    SELECT     name
                FROM    `' . DBPREFIX . 'module_blog_settings`
                WHERE    name="blog_comments_editor"
                LIMIT    1';

    $objResult = $objDatabase->Execute($query);

    if ($objResult !== false) {
        if ($objResult->RecordCount() == 0) {
            $query = "INSERT INTO `" . DBPREFIX . "module_blog_settings` ( `name` , `value` ) VALUES ('blog_comments_editor', 'wysiwyg')";

            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_blog_categories', array(
                'category_id' => array('type' => 'INT(4)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'lang_id' => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'is_active' => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => true, 'default' => '1'),
                'name' => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '')
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_blog_comments', array(
                'comment_id' => array('type' => 'INT(7)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'message_id' => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'lang_id' => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'is_active' => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => true, 'default' => '1'),
                'time_created' => array('type' => 'INT(14)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'ip_address' => array('type' => 'VARCHAR(15)', 'notnull' => true, 'default' => '0.0.0.0'),
                'user_id' => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'user_name' => array('type' => 'VARCHAR(50)', 'notnull' => false),
                'user_mail' => array('type' => 'VARCHAR(250)', 'notnull' => false),
                'user_www' => array('type' => 'VARCHAR(255)', 'notnull' => false),
                'subject' => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => ''),
                'comment' => array('type' => 'TEXT')
            ), array(
                'message_id' => array('fields' => array('message_id'))
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_blog_message_to_category', array(
                'message_id' => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'category_id' => array('type' => 'INT(4)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'lang_id' => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true)
            ), array(
                'category_id' => array('fields' => array('category_id'))
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_blog_messages', array(
                'message_id' => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'user_id' => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'time_created' => array('type' => 'INT(14)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'time_edited' => array('type' => 'INT(14)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'hits' => array('type' => 'INT(7)', 'unsigned' => true, 'notnull' => true, 'default' => '0')
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_blog_networks_lang', array(
                'network_id' => array('type' => 'INT(8)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'lang_id' => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true)
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_blog_votes', array(
                'vote_id' => array('type' => 'INT(8)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'message_id' => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'time_voted' => array('type' => 'INT(14)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'ip_address' => array('type' => 'VARCHAR(15)', 'notnull' => true, 'default' => '0.0.0.0'),
                'vote' => array('type' => 'ENUM(\'1\',\'2\',\'3\',\'4\',\'5\',\'6\',\'7\',\'8\',\'9\',\'10\')', 'notnull' => true, 'default' => '1')
            ), array(
                'message_id' => array('fields' => array('message_id'))
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    try { //update to 2.2.3 in this block
        if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.2.3')) {
            //we've hidden the wysiwyg - let's default to textarea
            \Cx\Lib\UpdateUtil::sql('UPDATE ' . DBPREFIX . 'module_blog_settings SET value="textarea" WHERE name="blog_comments_editor"');

            //comments: convert escaped db entries to their unescaped equivalents
            $rs = \Cx\Lib\UpdateUtil::sql('SELECT comment_id, comment FROM  ' . DBPREFIX . 'module_blog_comments');
            while (!$rs->EOF) {
                $content = $rs->fields['comment'];
                $id = $rs->fields['comment_id'];
                $content = contrexx_raw2db(html_entity_decode($content, ENT_QUOTES, CONTREXX_CHARSET));
                \Cx\Lib\UpdateUtil::sql('UPDATE ' . DBPREFIX . 'module_blog_comments SET comment="' . $content . '" WHERE comment_id=' . $id);
                $rs->MoveNext();
            }
        }
    } catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    try {
        // migrate content page to version 3.0.1
        $search = array(
        '/(.*)/ms',
        );
        $callback = function($matches) {
            $content = $matches[1];
            if (empty($content)) {
                return $content;
            }

            // replace placeholder {TXT_COMMENT_ADD_SPAM} with {TXT_COMMENT_CAPTCHA}
            $content = str_replace('{TXT_COMMENT_ADD_SPAM}', '{TXT_COMMENT_CAPTCHA}', $content);

            // replace <img src="[[BLOG_DETAILS_COMMENT_ADD_SPAM_URL]]" alt="[[BLOG_DETAILS_COMMENT_ADD_SPAM_ALT]]" title="[[BLOG_DETAILS_COMMENT_ADD_SPAM_ALT]]" /> with {COMMENT_CAPTCHA_CODE}
            $content = preg_replace('/<img[^>]+\{BLOG_DETAILS_COMMENT_ADD_SPAM_URL\}[^>]+>/ms', '{COMMENT_CAPTCHA_CODE}', $content);

            // remove <input type="text" name="frmAddComment_Captcha" />
            $content = preg_replace('/<input[^>]+name\s*=\s*[\'"]frmAddComment_Captcha[^>]+>/ms', '', $content);

            // remove <input type="hidden" name="frmAddComment_Offset" value="[[BLOG_DETAILS_COMMENT_ADD_SPAM_OFFSET]]" />
            $content = preg_replace('/<(div|p)[^>]*>\s*<input[^>]+name\s*=\s*[\'"]frmAddComment_Offset[^>]+>\s*<\/(div|p)>/ms', '', $content);

            // add missing comment_captcha template block
            if (!preg_match('/<!--\s+BEGIN\s+comment_captcha\s+-->.*<!--\s+END\s+comment_captcha\s+-->/ms', $content)) {
                $content = preg_replace('/(.*)(<(div|p)[^{]*?>.*?\{TXT_COMMENT_CAPTCHA\}.*?\{COMMENT_CAPTCHA_CODE\}.*?<\/\3>)/ms', '$1<!-- BEGIN comment_captcha -->$2<!-- END comment_captcha -->', $content, -1, $count);
                if (!$count) {
                    $content = preg_replace('/(.*)(<(div|p)[^{]*?>.*?\{COMMENT_CAPTCHA_CODE\}.*?<\/\3>)/ms', '$1<!-- BEGIN comment_captcha -->$2<!-- END comment_captcha -->', $content, -1, $count);
                }
            }

            return $content;
        };

        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'blog', 'cmd' => 'details'), $search, $callback, array('content'), '3.0.1');
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    /**
     * Everything went fine. Return without any errors.
     */
    return true;
}
