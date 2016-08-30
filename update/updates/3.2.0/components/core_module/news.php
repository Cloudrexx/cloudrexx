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


////////////////////////////////////////////////////
//BEGIN OF NEWS CONVERTING STUFF
/*
    this was c&ped together from news/admin.class.php and news/lib/newsLib.class.php
*/

class HackyFeedRepublisher {

    protected $arrSettings = array();

    public function runRepublishing() {
        $this->initRepublishing();

        FWLanguage::init();

        $langIds = array_keys(FWLanguage::getLanguageArray());

        foreach($langIds as $id) {
            $this->createRSS($id);
        }
    }

    protected function initRepublishing()
    {
        global  $_ARRAYLANG, $objInit, $objTemplate, $_CONFIG;

        //getSettings
        global $objDatabase;
        $query = "SELECT name, value FROM ".DBPREFIX."module_news_settings";
        $objResult = $objDatabase->Execute($query);
        while (!$objResult->EOF) {
            $this->arrSettings[$objResult->fields['name']] = $objResult->fields['value'];
            $objResult->MoveNext();
        }
    }

    protected function createRSS($langId){
        global $_CONFIG, $objDatabase;
        $_FRONTEND_LANGID = $langId;


        if (intval($this->arrSettings['news_feed_status']) == 1) {
            $arrNews = array();
            require_once(ASCMS_FRAMEWORK_PATH.'/RSSWriter.class.php');
            $objRSSWriter = new RSSWriter();

            $objRSSWriter->characterEncoding = CONTREXX_CHARSET;
            $objRSSWriter->channelTitle = $this->arrSettings['news_feed_title'];
            $objRSSWriter->channelLink = 'http://'.$_CONFIG['domainUrl'].($_SERVER['SERVER_PORT'] == 80 ? "" : ":".intval($_SERVER['SERVER_PORT'])).ASCMS_PATH_OFFSET.'/'.FWLanguage::getLanguageParameter($_FRONTEND_LANGID, 'lang').'/'.CONTREXX_DIRECTORY_INDEX.'?section=news';
            $objRSSWriter->channelDescription = $this->arrSettings['news_feed_description'];
            $objRSSWriter->channelLanguage = FWLanguage::getLanguageParameter($_FRONTEND_LANGID, 'lang');
            $objRSSWriter->channelCopyright = 'Copyright '.date('Y').', http://'.$_CONFIG['domainUrl'];

            if (!empty($this->arrSettings['news_feed_image'])) {
                $objRSSWriter->channelImageUrl = 'http://'.$_CONFIG['domainUrl'].($_SERVER['SERVER_PORT'] == 80 ? "" : ":".intval($_SERVER['SERVER_PORT'])).$this->arrSettings['news_feed_image'];
                $objRSSWriter->channelImageTitle = $objRSSWriter->channelTitle;
                $objRSSWriter->channelImageLink = $objRSSWriter->channelLink;
            }
            $objRSSWriter->channelWebMaster = $_CONFIG['coreAdminEmail'];

            $itemLink = "http://".$_CONFIG['domainUrl'].($_SERVER['SERVER_PORT'] == 80 ? "" : ":".intval($_SERVER['SERVER_PORT'])).ASCMS_PATH_OFFSET.'/'.FWLanguage::getLanguageParameter($_FRONTEND_LANGID, 'lang').'/'.CONTREXX_DIRECTORY_INDEX.'?section=news&amp;cmd=details&amp;newsid=';

            $query = "
                SELECT      tblNews.id,
                            tblNews.date,
                            tblNews.title,
                            tblNews.text,
                            tblNews.redirect,
                            tblNews.source,
                            tblNews.catid AS categoryId,
                            tblNews.teaser_frames AS teaser_frames,
                            tblNews.teaser_text,
                            tblCategory.name AS category
                FROM        ".DBPREFIX."module_news AS tblNews
                INNER JOIN  ".DBPREFIX."module_news_categories AS tblCategory
                USING       (catid)
                WHERE       tblNews.status=1
                    AND     tblNews.lang = ".$_FRONTEND_LANGID."
                    AND     (tblNews.startdate <= CURDATE() OR tblNews.startdate = '0000-00-00 00:00:00')
                    AND     (tblNews.enddate >= CURDATE() OR tblNews.enddate = '0000-00-00 00:00:00')"
                    .($this->arrSettings['news_message_protection'] == '1' ? " AND tblNews.frontend_access_id=0 " : '')
                            ."ORDER BY tblNews.date DESC";

            if (($objResult = $objDatabase->SelectLimit($query, 20)) !== false && $objResult->RecordCount() > 0) {
                while (!$objResult->EOF) {
                    if (empty($objRSSWriter->channelLastBuildDate)) {
                        $objRSSWriter->channelLastBuildDate = date('r', $objResult->fields['date']);
                    }
                    $arrNews[$objResult->fields['id']] = array(
                        'date'          => $objResult->fields['date'],
                        'title'         => $objResult->fields['title'],
                        'text'          => empty($objResult->fields['redirect']) ? (!empty($objResult->fields['teaser_text']) ? nl2br($objResult->fields['teaser_text']).'<br /><br />' : '').$objResult->fields['text'] : (!empty($objResult->fields['teaser_text']) ? nl2br($objResult->fields['teaser_text']) : ''),
                        'redirect'      => $objResult->fields['redirect'],
                        'source'        => $objResult->fields['source'],
                        'category'      => $objResult->fields['category'],
                        'teaser_frames' => explode(';', $objResult->fields['teaser_frames']),
                        'categoryId'    => $objResult->fields['categoryId']
                    );
                    $objResult->MoveNext();
                }
            }

            // create rss feed
            $objRSSWriter->xmlDocumentPath = ASCMS_FEED_PATH.'/news_'.FWLanguage::getLanguageParameter($_FRONTEND_LANGID, 'lang').'.xml';
            foreach ($arrNews as $newsId => $arrNewsItem) {
                $objRSSWriter->addItem(
                    contrexx_raw2xml($arrNewsItem['title']),
                    (empty($arrNewsItem['redirect'])) ? ($itemLink.$newsId.(isset($arrNewsItem['teaser_frames'][0]) ? '&amp;teaserId='.$arrNewsItem['teaser_frames'][0] : '')) : htmlspecialchars($arrNewsItem['redirect'], ENT_QUOTES, CONTREXX_CHARSET),
                    contrexx_raw2xml($arrNewsItem['text']),
                    '',
                    array('domain' => "http://".$_CONFIG['domainUrl'].($_SERVER['SERVER_PORT'] == 80 ? "" : ":".intval($_SERVER['SERVER_PORT'])).ASCMS_PATH_OFFSET.'/'.FWLanguage::getLanguageParameter($_FRONTEND_LANGID, 'lang').'/'.CONTREXX_DIRECTORY_INDEX.'?section=news&amp;category='.$arrNewsItem['categoryId'], 'title' => $arrNewsItem['category']),
                    '',
                    '',
                    '',
                    $arrNewsItem['date'],
                    array('url' => htmlspecialchars($arrNewsItem['source'], ENT_QUOTES, CONTREXX_CHARSET), 'title' => contrexx_raw2xml($arrNewsItem['title']))
               );
            }
            $status = $objRSSWriter->write();

            // create headlines rss feed
            $objRSSWriter->removeItems();
            $objRSSWriter->xmlDocumentPath = ASCMS_FEED_PATH.'/news_headlines_'.FWLanguage::getLanguageParameter($_FRONTEND_LANGID, 'lang').'.xml';
            foreach ($arrNews as $newsId => $arrNewsItem) {
                $objRSSWriter->addItem(
                    contrexx_raw2xml($arrNewsItem['title']),
                    $itemLink.$newsId.(isset($arrNewsItem['teaser_frames'][0]) ? "&amp;teaserId=".$arrNewsItem['teaser_frames'][0] : ""),
                    '',
                    '',
                    array('domain' => 'http://'.$_CONFIG['domainUrl'].($_SERVER['SERVER_PORT'] == 80 ? '' : ':'.intval($_SERVER['SERVER_PORT'])).ASCMS_PATH_OFFSET.'/'.FWLanguage::getLanguageParameter($_FRONTEND_LANGID, 'lang').'/'.CONTREXX_DIRECTORY_INDEX.'?section=news&amp;category='.$arrNewsItem['categoryId'], 'title' => $arrNewsItem['category']),
                    '',
                    '',
                    '',
                    $arrNewsItem['date']
                );
            }
            $statusHeadlines = $objRSSWriter->write();

            $objRSSWriter->feedType = 'js';
            $objRSSWriter->xmlDocumentPath = ASCMS_FEED_PATH.'/news_'.FWLanguage::getLanguageParameter($_FRONTEND_LANGID, 'lang').'.js';
            $objRSSWriter->write();

            /*
            if (count($objRSSWriter->arrErrorMsg) > 0) {
                $this->strErrMessage .= implode('<br />', $objRSSWriter->arrErrorMsg);
            }
            if (count($objRSSWriter->arrWarningMsg) > 0) {
                $this->strErrMessage .= implode('<br />', $objRSSWriter->arrWarningMsg);
            }
            */
        } else {
            @unlink(ASCMS_FEED_PATH.'/news_'.FWLanguage::getLanguageParameter($_FRONTEND_LANGID, 'lang').'.xml');
            @unlink(ASCMS_FEED_PATH.'/news_headlines_'.FWLanguage::getLanguageParameter($_FRONTEND_LANGID, 'lang').'.xml');
            @unlink(ASCMS_FEED_PATH.'/news_'.FWLanguage::getLanguageParameter($_FRONTEND_LANGID, 'lang').'.js');
        }
    }
}
//END OF NEWS CONVERTING STUFF

function _newsUpdate() {
    global $objDatabase, $_CONFIG, $objUpdate, $_ARRAYLANG;


    /************************************************
    * EXTENSION:    Placeholder NEWS_LINK replaced    *
    *                by NEWS_LINK_TITLE                *
    * ADDED:        Contrexx v2.1.0                    *
    ************************************************/
    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.1.0')) {
        try {
            \Cx\Lib\UpdateUtil::migrateContentPage('news', null, '{NEWS_LINK}', '{NEWS_LINK_TITLE}', '2.1.0');
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }



    /************************************************
    * EXTENSION:    Front- and backend permissions  *
    * ADDED:        Contrexx v2.1.0                    *
    ************************************************/
    $query = "SELECT 1 FROM `".DBPREFIX."module_news_settings` WHERE `name` = 'news_message_protection'";
    $objResult = $objDatabase->SelectLimit($query, 1);
    if ($objResult) {
        if ($objResult->RecordCount() == 0) {
            $query = "INSERT INTO `".DBPREFIX."module_news_settings` (`name`, `value`) VALUES ('news_message_protection', '1'),
                                                                                              ('recent_news_message_limit', '5')
            ";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    $query = "SELECT 1 FROM `".DBPREFIX."module_news_settings` WHERE `name` = 'news_message_protection_restricted'";
    $objResult = $objDatabase->SelectLimit($query, 1);
    if ($objResult) {
        if ($objResult->RecordCount() == 0) {
            $query = "INSERT INTO `".DBPREFIX."module_news_settings` (`name`, `value`) VALUES ('news_message_protection_restricted', '1')";
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    $arrColumns = $objDatabase->MetaColumnNames(DBPREFIX.'module_news');
    if ($arrColumns === false) {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'module_news'));
        return false;
    }

    if (!in_array('frontend_access_id', $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."module_news` ADD `frontend_access_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `validated`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }
    if (!in_array('backend_access_id', $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."module_news` ADD `backend_access_id` INT(10) UNSIGNED NOT NULL DEFAULT '0' AFTER `frontend_access_id`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }



    /************************************************
    * EXTENSION:    Thunbmail Image                 *
    * ADDED:        Contrexx v2.1.0                    *
    ************************************************/
    $arrColumns = $objDatabase->MetaColumnNames(DBPREFIX.'module_news');
    if ($arrColumns === false) {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'module_news'));
        return false;
    }

    if (!in_array('teaser_image_thumbnail_path', $arrColumns)) {
        $query = "ALTER TABLE `".DBPREFIX."module_news` ADD `teaser_image_thumbnail_path` TEXT NOT NULL AFTER `teaser_image_path`";
        if ($objDatabase->Execute($query) === false) {
            return _databaseError($query, $objDatabase->ErrorMsg());
        }
    }



    try{
        // delete obsolete table  contrexx_module_news_access
        \Cx\Lib\UpdateUtil::drop_table(DBPREFIX.'module_news_access');
        # fix some ugly NOT NULL without defaults
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX . 'module_news',
            array(
                'id'                         => array('type'=>'INT(6) UNSIGNED','notnull'=>true,  'primary'     =>true,   'auto_increment' => true),
                'date'                       => array('type'=>'INT(14)',            'notnull'=>false, 'default_expr'=>'NULL'),
                'title'                      => array('type'=>'VARCHAR(250)',       'notnull'=>true,  'default'     =>''),
                'text'                       => array('type'=>'MEDIUMTEXT',         'notnull'=>true),
                'redirect'                   => array('type'=>'VARCHAR(250)',       'notnull'=>true,  'default'     =>''),
                'source'                     => array('type'=>'VARCHAR(250)',       'notnull'=>true,  'default'     =>''),
                'url1'                       => array('type'=>'VARCHAR(250)',       'notnull'=>true,  'default'     =>''),
                'url2'                       => array('type'=>'VARCHAR(250)',       'notnull'=>true,  'default'     =>''),
                'catid'                      => array('type'=>'INT(2) UNSIGNED',    'notnull'=>true,  'default'     =>0),
                'lang'                       => array('type'=>'INT(2) UNSIGNED',    'notnull'=>true,  'default'     =>0),
                'userid'                     => array('type'=>'INT(6) UNSIGNED',    'notnull'=>true,  'default'     =>0),
                'startdate'                  => array('type'=>'DATETIME',           'notnull'=>true,  'default'     =>'0000-00-00 00:00:00'),
                'enddate'                    => array('type'=>'DATETIME',           'notnull'=>true,  'default'     =>'0000-00-00 00:00:00'),
                'status'                     => array('type'=>'TINYINT(4)',         'notnull'=>true,  'default'     =>1),
                'validated'                  => array('type'=>"ENUM('0','1')",      'notnull'=>true,  'default'     =>0),
                'frontend_access_id'         => array('type'=>'INT(10) UNSIGNED',   'notnull'=>true,  'default'     =>0),
                'backend_access_id'          => array('type'=>'INT(10) UNSIGNED',   'notnull'=>true,  'default'     =>0),
                'teaser_only'                => array('type'=>"ENUM('0','1')",      'notnull'=>true,  'default'     =>0),
                'teaser_frames'              => array('type'=>'TEXT',               'notnull'=>true),
                'teaser_text'                => array('type'=>'TEXT',               'notnull'=>true),
                'teaser_show_link'           => array('type'=>'TINYINT(1) UNSIGNED','notnull'=>true,  'default'     =>1),
                'teaser_image_path'          => array('type'=>'TEXT',               'notnull'=>true),
                'teaser_image_thumbnail_path'=> array('type'=>'TEXT',               'notnull'=>true),
                'changelog'                  => array('type'=>'INT(14)',            'notnull'=>true,  'default'     =>0),
            ),
            array(#indexes
                'newsindex' =>array ('type' => 'FULLTEXT', 'fields' => array('text','title','teaser_text'))
            )
        );

    }
    catch (\Cx\Lib\UpdateException $e) {
        // we COULD do something else here..
        DBG::trace();
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    //encoding was a little messy in 2.1.4. convert titles and teasers to their raw representation
    if($_CONFIG['coreCmsVersion'] == "2.1.4") {
        try{
            $res = \Cx\Lib\UpdateUtil::sql('SELECT `id`, `title`, `teaser_text` FROM `'.DBPREFIX.'module_news` WHERE `changelog` > '.mktime(0,0,0,12,15,2010));
            while($res->MoveNext()) {
                $title = $res->fields['title'];
                $teaserText = $res->fields['teaser_text'];
                $id = $res->fields['id'];

                //title is html entity style
                $title = html_entity_decode($title, ENT_QUOTES, CONTREXX_CHARSET);
                //teaserText is html entity style, but no cloudrexx was specified on encoding
                $teaserText = html_entity_decode($teaserText);

                \Cx\Lib\UpdateUtil::sql('UPDATE `'.DBPREFIX.'module_news` SET `title`="'.addslashes($title).'", `teaser_text`="'.addslashes($teaserText).'" where `id`='.$id);
            }

            $hfr = new HackyFeedRepublisher();
            $hfr->runRepublishing();
        }
        catch (\Cx\Lib\UpdateException $e) {
            DBG::trace();
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }


    /****************************
    * ADDED:    Contrexx v3.0.0 *
    *****************************/
    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_news_locale',
            array(
                'news_id'        => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'lang_id'        => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true, 'after' => 'news_id'),
                'is_active'      => array('type' => 'INT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'lang_id'),
                'title'          => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'is_active'),
                'text'           => array('type' => 'mediumtext', 'notnull' => true, 'after' => 'title'),
                'teaser_text'    => array('type' => 'text', 'notnull' => true, 'after' => 'text')
            ),
            array(
                'newsindex'      => array('fields' => array('text', 'title', 'teaser_text'), 'type' => 'FULLTEXT')
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_news_categories_locale',
            array(
                'category_id'    => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'lang_id'        => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true, 'after' => 'category_id'),
                'name'           => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'lang_id')
            ),
            array(
                'name'           => array('fields' => array('name'), 'type' => 'FULLTEXT')
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_news_types',
            array(
                'typeid'     => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'primary' => true, 'auto_increment' => true)
            )
        );


        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_news_types_locale',
            array(
                'lang_id'    => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true),
                'type_id'    => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true, 'after' => 'lang_id'),
                'name'       => array('type' => 'VARCHAR(100)', 'notnull' => true, 'default' => '', 'after' => 'type_id')
            ),
            array(
                'name'       => array('fields' => array('name'), 'type' => 'FULLTEXT')
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_news_settings_locale',
            array(
                'name'       => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => '', 'primary' => true),
                'lang_id'    => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'primary' => true, 'after' => 'name'),
                'value'      => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'lang_id')
            ),
            array(
                'name'       => array('fields' => array('name'), 'type' => 'FULLTEXT')
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_news_comments',
            array(
                'id'             => array('type' => 'INT(11)', 'unsigned' => true, 'notnull' => true, 'primary' => true, 'auto_increment' => true),
                'title'          => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'id'),
                'text'           => array('type' => 'mediumtext', 'notnull' => true, 'after' => 'title'),
                'newsid'         => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'text'),
                'date'           => array('type' => 'INT(14)', 'notnull' => false, 'default' => NULL,'after' => 'newsid'),
                'poster_name'    => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'date'),
                'userid'         => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'poster_name'),
                'ip_address'     => array('type' => 'VARCHAR(15)', 'notnull' => true, 'default' => '0.0.0.0', 'after' => 'userid'),
                'is_active'      => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => true, 'default' => '1', 'after' => 'ip_address')
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_news_stats_view',
            array(
                'user_sid'       => array('type' => 'CHAR(32)', 'notnull' => true),
                'news_id'        => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'after' => 'user_sid'),
                'time'           => array('type' => 'timestamp', 'notnull' => true, 'default_expr' => 'CURRENT_TIMESTAMP', 'on_update' => 'CURRENT_TIMESTAMP', 'after' => 'news_id')
            ),
            array(
                'idx_user_sid'   => array('fields' => array('user_sid')),
                'idx_news_id'    => array('fields' => array('news_id'))
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'module_news',
            array(
                'id'                             => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'primary' => true, 'auto_increment' => true),
                'date'                           => array('type' => 'INT(14)', 'notnull' => false, 'default' => NULL, 'after' => 'id'),
                'title'                          => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'date'),
                'text'                           => array('type' => 'mediumtext', 'notnull' => true, 'after' => 'title'),
                'redirect'                       => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'text'),
                'source'                         => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'redirect'),
                'url1'                           => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'source'),
                'url2'                           => array('type' => 'VARCHAR(250)', 'notnull' => true, 'default' => '', 'after' => 'url1'),
                'catid'                          => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'url2'),
                'lang'                           => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'catid'),
                'typeid'                         => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'lang'),
                'publisher'                      => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'typeid'),
                'publisher_id'                   => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'publisher'),
                'author'                         => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'publisher_id'),
                'author_id'                      => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'author'),
                'userid'                         => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'author_id'),
                'startdate'                      => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'userid'),
                'enddate'                        => array('type' => 'timestamp', 'notnull' => true, 'default' => '0000-00-00 00:00:00', 'after' => 'startdate'),
                'status'                         => array('type' => 'TINYINT(4)', 'notnull' => true, 'default' => '1', 'after' => 'enddate'),
                'validated'                      => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => true, 'default' => '0', 'after' => 'status'),
                'frontend_access_id'             => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'validated'),
                'backend_access_id'              => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'frontend_access_id'),
                'teaser_only'                    => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => true, 'default' => '0', 'after' => 'backend_access_id'),
                'teaser_frames'                  => array('type' => 'text', 'notnull' => true, 'after' => 'teaser_only'),
                'teaser_text'                    => array('type' => 'text', 'notnull' => true, 'after' => 'teaser_frames'),
                'teaser_show_link'               => array('type' => 'TINYINT(1)', 'unsigned' => true, 'notnull' => true, 'default' => '1', 'after' => 'teaser_text'),
                'teaser_image_path'              => array('type' => 'text', 'notnull' => true, 'after' => 'teaser_show_link'),
                'teaser_image_thumbnail_path'    => array('type' => 'text', 'notnull' => true, 'after' => 'teaser_image_path'),
                'changelog'                      => array('type' => 'INT(14)', 'notnull' => true, 'default' => '0', 'after' => 'teaser_image_thumbnail_path'),
                'allow_comments'                 => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'changelog')
            ),
            array(
                'newsindex'                      => array('fields' => array('text','title','teaser_text'), 'type' => 'FULLTEXT')
            )
        );


        $arrColumnsNews = $objDatabase->MetaColumnNames(DBPREFIX.'module_news');
        if ($arrColumnsNews === false) {
            setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'module_news'));
            return false;
        }
        if (isset($arrColumnsNews['TITLE']) && isset($arrColumnsNews['TEXT']) && isset($arrColumnsNews['TEASER_TEXT']) && isset($arrColumnsNews['LANG'])) {
            \Cx\Lib\UpdateUtil::sql('
                INSERT INTO `'.DBPREFIX.'module_news_locale` (`news_id`, `lang_id`, `title`, `text`, `teaser_text`)
                SELECT `id`, `lang`, `title`, `text`, `teaser_text` FROM `'.DBPREFIX.'module_news`
                ON DUPLICATE KEY UPDATE `news_id` = `news_id`
            ');
        }
        if (isset($arrColumnsNews['TITLE'])) {
            \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'module_news` DROP `title`');
        }
        if (isset($arrColumnsNews['TEXT'])) {
            \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'module_news` DROP `text`');
        }
        if (isset($arrColumnsNews['TEASER_TEXT'])) {
            \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'module_news` DROP `teaser_text`');
        }
        if (isset($arrColumnsNews['LANG'])) {
            \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'module_news` DROP `lang`');
        }

        $arrColumnsNewsCategories = $objDatabase->MetaColumnNames(DBPREFIX.'module_news_categories');
        if ($arrColumnsNewsCategories === false) {
            setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'module_news_categories'));
            return false;
        }
        if (isset($arrColumnsNewsCategories['NAME'])) {
            \Cx\Lib\UpdateUtil::sql('
                INSERT INTO '.DBPREFIX.'module_news_categories_locale (`category_id`, `lang_id`, `name`)
                SELECT c.catid, l.id, c.name
                FROM '.DBPREFIX.'module_news_categories AS c, '.DBPREFIX.'languages AS l
                ORDER BY c.catid, l.id
                ON DUPLICATE KEY UPDATE `category_id` = `category_id`
            ');
            \Cx\Lib\UpdateUtil::sql('
                INSERT INTO '.DBPREFIX.'module_news_categories_locale (`category_id`, `lang_id`, `name`)
                SELECT c.catid, l.id, c.name
                FROM '.DBPREFIX.'module_news_categories AS c, '.DBPREFIX.'languages AS l
                ORDER BY c.catid, l.id
                ON DUPLICATE KEY UPDATE `category_id` = `category_id`
            ');
            \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'module_news_categories` DROP `name`');
        }
        if (isset($arrColumnsNewsCategories['LANG'])) {
            \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'module_news_categories` DROP `lang`');
        }

        \Cx\Lib\UpdateUtil::sql('
            INSERT INTO `'.DBPREFIX.'module_news_settings_locale` (`name`, `lang_id`, `value`)
            SELECT n.`name`, l.`id`, n.`value`
            FROM `'.DBPREFIX.'module_news_settings` AS n, `'.DBPREFIX.'languages` AS l
            WHERE n.`name` IN ("news_feed_description", "news_feed_title")
            ORDER BY n.`name`, l.`id`
            ON DUPLICATE KEY UPDATE `'.DBPREFIX.'module_news_settings_locale`.`name` = `'.DBPREFIX.'module_news_settings_locale`.`name`
        ');

        \Cx\Lib\UpdateUtil::sql('DELETE FROM `'.DBPREFIX.'module_news_settings` WHERE `name` IN ("news_feed_title", "news_feed_description")');

        \Cx\Lib\UpdateUtil::sql('
            INSERT INTO `'.DBPREFIX.'module_news_settings` (`name`, `value`)
            VALUES  ("news_comments_activated", "0"),
                    ("news_comments_anonymous", "0"),
                    ("news_comments_autoactivate", "0"),
                    ("news_comments_notification", "1"),
                    ("news_comments_timeout", "30"),
                    ("news_default_teasers", ""),
                    ("news_use_types","0"),
                    ("news_use_top","0"),
                    ("news_top_days","10"),
                    ("news_top_limit","10"),
                    ("news_assigned_author_groups", "0"),
                    ("news_assigned_publisher_groups", "0")
            ON DUPLICATE KEY UPDATE `name` = `name`
        ');

    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    try {
        \Cx\Lib\UpdateUtil::migrateContentPage('news', 'details', array('{NEWS_DATE}','{NEWS_COMMENTS_DATE}'), array('{NEWS_LONG_DATE}', '{NEWS_COMMENTS_LONG_DATE}'), '3.0.1');

        // this adds the block news_redirect
        $search = array(
            '/.*\{NEWS_TEXT\}.*/ms',
        );
        $callback = function($matches) {
            if (   !preg_match('/<!--\s+BEGIN\s+news_redirect\s+-->/ms', $matches[0])) {
                $newsContent = <<<NEWS
<!-- BEGIN news_text -->{NEWS_TEXT}<!-- END news_text -->
    <!-- BEGIN news_redirect -->{TXT_NEWS_REDIRECT_INSTRUCTION} <a href="{NEWS_REDIRECT_URL}" target="_blank">{NEWS_REDIRECT_URL}</a><!-- END news_redirect -->
NEWS;
                return str_replace('{NEWS_TEXT}', $newsContent, $matches[0]);
            } else {
                return $matches[0];
            }
        };
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'news', 'cmd' => 'details'), $search, $callback, array('content'), '3.0.1');
    } catch (\Cx\Lib\UpdateException $e) {
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

            // migrate to ckeditor
            $content = str_replace('FCKeditorAPI.GetInstance(\'newsText\').SetData(\'\')', 'CKEDITOR.instances[\'newsText\'].setData()', $content);

            if (!preg_match('/<!--\s+BEGIN\s+news_submit_form_captcha\s+-->.*<!--\s+END\s+news_submit_form_captcha\s+-->/ms', $content)) {
                // check if captcha code is already present
                if (preg_match('/\{IMAGE_URL\}/ms', $content)) {
                    // add missing template block news_submit_form_captcha
                    $content = preg_replace('/(.*)(<p[^>]*>.*?<label[^>]*>.*?\{IMAGE_URL\}.*?<\/p>)/ms', '$1<!-- BEGIN news_submit_form_captcha -->$2<!-- END news_submit_form_captcha -->', $content);
                } else {
                    // add whole captcha code incl. template block
                    $content = preg_replace('/(.*)(<tr[^>]*>.*?<td([^>]*)>.*?\{NEWS_TEXT\}.*?(\s*)<\/tr>)/ms', '$1$2$4<!-- BEGIN news_submit_form_captcha -->$4<tr>$4    <td$3>{NEWS_CAPTCHA_CODE}</td>$4</tr>$4<!-- END news_submit_form_captcha -->', $content);
                }
            }

            // add text variable
            $content = str_replace('Captcha', '{TXT_NEWS_CAPTCHA}', $content);

            // replace image with {NEWS_CAPTCHA_CODE}
            $content = preg_replace('/<img[^>]+\{IMAGE_URL\}[^>]+>(?:<br\s*\/?>)?/ms', '{NEWS_CAPTCHA_CODE}', $content);

            // remove {TXT_CAPTCHA}
            $content = str_replace('{TXT_CAPTCHA}', '', $content);

            // remove <input type="text" name="captcha" id="captcha" />
            $content = preg_replace('/<input[^>]+name\s*=\s*[\'"]captcha[\'"][^>]*>/ms', '', $content);

            return $content;
        };


        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('module' => 'news', 'cmd' => 'submit'), $search, $callback, array('content'), '3.0.1');
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegex(array('module' => 'news'), '/(\{NEWS_COUNT_COMMENTS\})/', '<!-- BEGIN news_comments_count -->$1<!-- END news_comments_count -->', array('content'), '3.0.3');
        \Cx\Lib\UpdateUtil::migrateContentPageUsingRegex(array('module' => 'news', 'cmd' => 'details'), '/(\{NEWS_COUNT_COMMENTS\})/', '<!-- BEGIN news_comments_count -->$1<!-- END news_comments_count -->', array('content'), '3.0.3');
    } catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    /************************************************
    * EXTENSION:    Categories as NestedSet         *
    * ADDED:        Contrexx v3.1.0                 *
    ************************************************/
    if (!isset($_SESSION['contrexx_update']['news'])) {
        $_SESSION['contrexx_update']['news'] = array();
    }
    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0') && !isset($_SESSION['contrexx_update']['news']['nestedSet'])) {
        try {
            $nestedSetRootId = null;
            $count = null;
            $leftAndRight = 2;
            $sorting = 1;
            $level = 2;

            // add nested set columns
            \Cx\Lib\UpdateUtil::table(
                DBPREFIX.'module_news_categories',
                array(
                    'catid'          => array('type' => 'INT(2)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                    'parent_id'      => array('type' => 'INT(11)', 'after' => 'catid'),
                    'left_id'        => array('type' => 'INT(11)', 'after' => 'parent_id'),
                    'right_id'       => array('type' => 'INT(11)', 'after' => 'left_id'),
                    'sorting'        => array('type' => 'INT(11)', 'after' => 'right_id'),
                    'level'          => array('type' => 'INT(11)', 'after' => 'sorting')
                )
            );

            // add nested set root node and select its id
            $objResultRoot = \Cx\Lib\UpdateUtil::sql('INSERT INTO `'.DBPREFIX.'module_news_categories` (`catid`, `parent_id`, `left_id`, `right_id`, `sorting`, `level`) VALUES (0, 0, 0, 0, 0, 0)');
            if ($objResultRoot) {
                $nestedSetRootId = $objDatabase->Insert_ID();
            }

            // count categories
            $objResultCount = \Cx\Lib\UpdateUtil::sql('SELECT count(`catid`) AS count FROM `'.DBPREFIX.'module_news_categories`');
            if ($objResultCount && !$objResultCount->EOF) {
                $count = $objResultCount->fields['count'];
            }

            // add nested set information to root node
            \Cx\Lib\UpdateUtil::sql('
                UPDATE `'.DBPREFIX.'module_news_categories` SET
                `parent_id` = '.$nestedSetRootId.',
                `left_id` = 1,
                `right_id` = '.($count*2).',
                `sorting` = 1,
                `level` = 1
                WHERE `catid` = '.$nestedSetRootId.'
            ');

            // add nested set information to all categories
            $objResultCatSelect = \Cx\Lib\UpdateUtil::sql('SELECT `catid` FROM `'.DBPREFIX.'module_news_categories` ORDER BY `catid` ASC');
            if ($objResultCatSelect) {
                while (!$objResultCatSelect->EOF) {
                    $catId = $objResultCatSelect->fields['catid'];
                    if ($catId != $nestedSetRootId) {
                        \Cx\Lib\UpdateUtil::sql('
                            UPDATE `'.DBPREFIX.'module_news_categories` SET
                            `parent_id` = '.$nestedSetRootId.',
                            `left_id` = '.$leftAndRight++.',
                            `right_id` = '.$leftAndRight++.',
                            `sorting` = '.$sorting++.',
                            `level` = '.$level.'
                            WHERE `catid` = '.$catId.'
                        ');
                    }
                    $objResultCatSelect->MoveNext();
                }
            }

            // add new tables
            \Cx\Lib\UpdateUtil::table(
                DBPREFIX.'module_news_categories_locks',
                array(
                    'lockId'         => array('type' => 'VARCHAR(32)'),
                    'lockTable'      => array('type' => 'VARCHAR(32)', 'after' => 'lockId'),
                    'lockStamp'      => array('type' => 'BIGINT(11)', 'notnull' => true, 'after' => 'lockTable')
                )
            );
            \Cx\Lib\UpdateUtil::table(
                DBPREFIX.'module_news_categories_catid',
                array(
                    'id'     => array('type' => 'INT(11)', 'notnull' => true)
                )
            );


            // insert id of last added category
            \Cx\Lib\UpdateUtil::sql('INSERT INTO `'.DBPREFIX.'module_news_categories_catid` (`id`) VALUES ('.$nestedSetRootId.')');
            $_SESSION['contrexx_update']['news']['nestedSet'] = true;
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }


    /************************************
    * EXTENSION:    Module page changes *
    * ADDED:        Contrexx v3.1.0     *
    ************************************/
    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {
        try {
            $result = \Cx\Lib\UpdateUtil::sql('SELECT `id` FROM `'.DBPREFIX.'content_page` WHERE `module` = "news" AND `cmd` RLIKE "^[0-9]*$"');
            if ($result && ($result->RecordCount() > 0)) {
                while (!$result->EOF) {

                    \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('id' => $result->fields['id']), '/(.*)/ms', function($matches) {
                        $page = $matches[0];

                        if (!empty($page) &&
                            !preg_match('/<!--\s+BEGIN\s+news_status_message\s+-->.*<!--\s+END\s+news_status_message\s+-->/ms', $page) &&
                            !preg_match('/<!--\s+BEGIN\s+news_menu\s+-->.*<!--\s+END\s+news_menu\s+-->/ms', $page) &&
                            !preg_match('/<!--\s+BEGIN\s+news_list\s+-->.*<!--\s+END\s+news_list\s+-->/ms', $page)
                        ) {
                            $page = preg_replace_callback('/<form[^>]*>[^<]*\\{NEWS_CAT_DROPDOWNMENU\\}[^>]*<\/form>/ims', function($matches) {
                                $menu = $matches[0];

                                $menu = preg_replace_callback('/(action\s*=\s*([\'"])[^\2]+section=news)\2/i', function($matches) {
                                    return $matches[1].'&cmd=[[NEWS_CMD]]'.$matches[2];
                                }, $menu);

                                return '
                                    <!-- BEGIN news_status_message -->
                                    {TXT_NEWS_NO_NEWS_FOUND}
                                    <!-- END news_status_message -->

                                    <!-- BEGIN news_menu -->
                                    '.$menu.'
                                    <!-- END news_menu -->
                                ';

                            }, $page);

                            $page = preg_replace_callback('/<ul[^>]*>[^<]*<!--\s+BEGIN\s+newsrow\s+-->.*<!--\s+END\s+newsrow\s+-->[^>]*<\/ul>/ims', function($matches) {
                                return '
                                    <!-- BEGIN news_list -->
                                    '.$matches[0].'
                                    <!-- END news_list -->
                                ';
                            }, $page);

                            if (!preg_match('/<!--\s+BEGIN\s+news_status_message\s+-->.*<!--\s+END\s+news_status_message\s+-->/ms', $page)) {
                                $page = '
                                    <!-- BEGIN news_status_message -->
                                    {TXT_NEWS_NO_NEWS_FOUND}
                                    <!-- END news_status_message -->
                                '.$page;
                            }
                        }

                        return $page;

                    }, array('content'), '3.1.0');

                    $result->MoveNext();
                }
            }

            $result = \Cx\Lib\UpdateUtil::sql('SELECT `id` FROM `'.DBPREFIX.'content_page` WHERE `module` = "news" AND `cmd` RLIKE "^details[0-9]*$"');
            if ($result && ($result->RecordCount() > 0)) {
                while (!$result->EOF) {

                    \Cx\Lib\UpdateUtil::migrateContentPageUsingRegexCallback(array('id' => $result->fields['id']), '/(.*)/ms', function($matches) {
                        $page = $matches[0];

                        if (!empty($page) && !preg_match('/<!--\s+BEGIN\s+news_use_teaser_text\s+-->.*<!--\s+END\s+news_use_teaser_text\s+-->/ms', $page)) {
                            $page = preg_replace('/\\{NEWS_TEASER_TEXT\\}/', '<!-- BEGIN news_use_teaser_text -->\0<!-- END news_use_teaser_text -->', $page);
                        }

                        return $page;
                    }, array('content'), '3.1.0');


                    $result->MoveNext();
                }
            }
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }

    /***********************************
    * EXTENSION:    new settings added *
    * ADDED:        Contrexx v3.1.0    *
    ***********************************/
    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.1.0')) {
        try {
            $result = \Cx\Lib\UpdateUtil::sql('SELECT `name` FROM `'.DBPREFIX.'module_news_settings` WHERE `name` = "news_use_teaser_text"');
            if ($result && ($result->RecordCount() == 0)) {
                \Cx\Lib\UpdateUtil::sql('INSERT INTO `'.DBPREFIX.'module_news_settings` (`name`, `value`) VALUES ("news_use_teaser_text", 1)');
            }
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }

    return true;
}
