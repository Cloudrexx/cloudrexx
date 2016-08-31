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

function _forumUpdate()
{
    global $objDatabase, $_ARRAYLANG;

    $arrSettings = array(
                        '9' => array(
                            'name'     => 'banned_words',
                            'value' => 'penis enlargement,free porn,(?i:buy\\\\s*?(?:cheap\\\\s*?)?viagra)'),
                        '10' => array(
                            'name'     => 'wysiwyg_editor',
                            'value' => '1'),
                        '11' => array(
                            'name'     => 'tag_count',
                            'value' => '10'),
                        '12' => array(
                            'name'     => 'latest_post_per_thread',
                            'value' => '1'),
                        '13' => array(
                            'name'     => 'allowed_extensions',
                            'value' => '7z,aiff,asf,avi,bmp,csv,doc,fla,flv,gif,gz,gzip, jpeg,jpg,mid,mov,mp3,mp4,mpc,mpeg,mpg,ods,odt,pdf, png,ppt,pxd,qt,ram,rar,rm,rmi,rmvb,rtf,sdc,sitd,swf, sxc,sxw,tar,tgz,tif,tiff,txt,vsd,wav,wma,wmv,xls,xml ,zip')
                        );

    $arrTables = $objDatabase->MetaTables();
    $arrColumns = $objDatabase->MetaColumnNames(DBPREFIX."module_forum_postings");
    if(!in_array('rating', $arrColumns)){
        $query = "ALTER TABLE `".DBPREFIX."module_forum_postings` ADD `rating` INT NOT NULL DEFAULT '0' AFTER `is_sticky`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }


    if(!in_array('keywords', $arrColumns)){
        $query = "ALTER TABLE `".DBPREFIX."module_forum_postings` ADD `keywords` TEXT NOT NULL AFTER `icon`" ;
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }
    $arrIndexes = $objDatabase->MetaIndexes(DBPREFIX."module_forum_postings");

    if(is_array($arrIndexes['fulltext'])){
        $query = "ALTER TABLE `".DBPREFIX."module_forum_postings` DROP INDEX `fulltext`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }
    $query = "ALTER TABLE `".DBPREFIX."module_forum_postings` ADD FULLTEXT `fulltext` (
                `keywords`,
                `subject`,
                `content`
                );" ;
    if ($objDatabase->Execute($query) === false) {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }


    if(!in_array('attachment', $arrColumns)){
        $query = "ALTER TABLE `".DBPREFIX."module_forum_postings` ADD `attachment` VARCHAR(250) NOT NULL DEFAULT '' AFTER `content`" ;
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }

    foreach ($arrSettings as $id => $arrSetting) {
        $query = "SELECT 1 FROM `".DBPREFIX."module_forum_settings` WHERE `name`= '".$arrSetting['name']."'" ;
        if (($objRS = $objDatabase->Execute($query)) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
        if($objRS->RecordCount() == 0){
            $query = "INSERT INTO `".DBPREFIX."module_forum_settings`
                             (`id`, `name`, `value`)
                      VALUES (".$id.", '".$arrSetting['name']."', '".addslashes($arrSetting['value'])."')" ;
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    }


    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_forum_rating',
            array(
                'id'         => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'user_id'    => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0'),
                'post_id'    => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0'),
                'time'       => array('type' => 'INT(11)', 'notnull' => true, 'default' => '0')
            ),
            array(
                'user_id'    => array('fields' => array('user_id','post_id'))
            )
        );
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    /**********************************
     * EXTENSION:   Content Migration *
     * ADDED:       Contrexx v3.0.0   *
     **********************************/
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

            // replace message textarea with {FORUM_MESSAGE_INPUT}
            $content = preg_replace('/<textarea[^>]+name\s*=\s*[\'"]message[\'"][^>]*>.*?\{FORUM_MESSAGE\}.*?<\/textarea>/ms', '{FORUM_MESSAGE_INPUT}', $content);

            if (!preg_match('/<!--\s+BEGIN\s+captcha\s+-->.*<!--\s+END\s+captcha\s+-->/ms', $content)) {
                // migration for versions < 2.0

                // add missing template block captcha
                $content = preg_replace('/(.*)(<tr[^>]*>.*?<td[^>]*>.*?\{FORUM_CAPTCHA_IMAGE_URL\}.*?<\/td>.*?<\/tr>)/ms', '$1<!-- BEGIN captcha -->$2<!-- END captcha -->', $content);
            }

            // add missing placeholder {FORUM_JAVASCRIPT_SCROLLTO}
            if (strpos($content, '{FORUM_JAVASCRIPT_SCROLLTO}') === false) {
                $content = '{FORUM_JAVASCRIPT_SCROLLTO}'.$content;
            }

            // hide deprecated marckup buttons
            $content = preg_replace('/(<!--\s+)?(<input[^>]+onclick\s*=\s*[\'"]\s*addText\([^>]+>)(?:\s+-->)?/ms', '<!-- $2 -->', $content);

            // replace image with {FORUM_CAPTCHA_CODE}
            $content = preg_replace('/(<!--\s+BEGIN\s+captcha\s+-->.*)<img[^>]+\{FORUM_CAPTCHA_IMAGE_URL\}[^>]+>(?:<br\s*\/?>)?(.*<!--\s+END\s+captcha\s+-->)/ms', '$1{FORUM_CAPTCHA_CODE}$2', $content);

            // replace text "Captcha-Code" with {TXT_FORUM_CAPTCHA}
            $content = preg_replace('/(<!--\s+BEGIN\s+captcha\s+-->.*)Captcha-Code:?(.*<!--\s+END\s+captcha\s+-->)/ms', '$1{TXT_FORUM_CAPTCHA}$2', $content);

            // remove <input type="text" name="captcha" id="captcha" />
            $content = preg_replace('/(<!--\s+BEGIN\s+captcha\s+-->.*)<input[^>]+name\s*=\s*[\'"]captcha[\'"][^>]*>(.*<!--\s+END\s+captcha\s+-->)/ms', '$1$2', $content);

            // remove <input type="hidden" name="offset" value="[[FORUM_CAPTCHA_OFFSET]]" />
            $content = preg_replace('/(<!--\s+BEGIN\s+captcha\s+-->.*)<input[^>]+name\s*=\s*[\'"]offset[\'"][^>]*>(.*<!--\s+END\s+captcha\s+-->)/ms', '$1$2', $content);

            // add missing block threadActions
            if (!preg_match('/<!--\s+BEGIN\s+threadActions\s+-->.*<!--\s+END\s+threadActions\s+-->/ms', $content)) {
                $threadActionHtml = <<<FORUM
<!-- BEGIN threadActions --><br />
    <span style="color: rgb(255, 0, 0);">{TXT_THREAD_ACTION_ERROR}&nbsp;</span><br />
    <span style="color: #006900;">{TXT_THREAD_ACTION_SUCCESS}&nbsp;</span> <!-- BEGIN moveForm -->
    <form action="index.php?section=forum&amp;cmd=thread&amp;action=move&amp;id={FORUM_THREAD_ID}" method="POST" name="frmThreadMove">
        <select name="moveToThread" size="32" style="width:225px;"> {FORUM_THREADS} </select><br />
        <input type="submit" value="{TXT_FORUM_THREAD_ACTION_MOVE}" />&nbsp;</form>
    <!-- END moveForm --><!-- END threadActions -->
FORUM;

                $content = preg_replace('/(<!--\s+END\s+addPost\s+-->)/ms', '$1'.$threadActionHtml, $content);
            }

            return $content;
        };

        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'forum'), $search, $callback, array('content'), '3.0.1');
    }
    catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    return true;
}
