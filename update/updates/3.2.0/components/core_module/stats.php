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

function _statsUpdate()
{
    global $objDatabase, $objUpdate, $_CONFIG, $_ARRAYLANG;

    // remove redundancies
    if (!isset($_SESSION['contrexx_update']['update']['update_stats'])) {
        $_SESSION['contrexx_update']['update']['update_stats'] = array();
    }

    foreach (array(
        'stats_browser' => array(
            'obsoleteIndex'    => 'name',
            'unique' => array('name'),
            'change' => "`name` `name` VARCHAR(255) BINARY NOT NULL DEFAULT ''"
        ),
        'stats_colourdepth' => array(
            'obsoleteIndex'    => 'depth',
            'unique' => array('depth')
        ),
        'stats_country' => array(
            'obsoleteIndex'    => 'country',
            'unique' => array('country'),
            'change' => "`country` `country` VARCHAR(100) BINARY NOT NULL DEFAULT ''"
        ),
        'stats_hostname' => array(
            'obsoleteIndex'    => 'hostname',
            'unique' => array('hostname'),
            'change' => "`hostname` `hostname` VARCHAR(255) BINARY NOT NULL DEFAULT ''"
        ),
        'stats_operatingsystem' => array(
            'obsoleteIndex'    => 'name',
            'unique' => array('name'),
            'change' => "`name` `name` VARCHAR(255) BINARY NOT NULL DEFAULT ''"
        ),
        'stats_referer' => array(
            'obsoleteIndex'    => 'uri',
            'unique' => array('uri'),
            'change' => "`uri` `uri` VARCHAR(255) BINARY NOT NULL DEFAULT ''"
        ),
        'stats_requests' => array(
            'obsoleteIndex'    => 'page',
            'unique' => array('page'),
            'count' => 'visits',
            'change' => "`page` `page` VARCHAR(255) BINARY NOT NULL DEFAULT ''"
        ),
        'stats_requests_summary' => array(
            'obsoleteIndex'    => 'type',
            'unique' => array('type', 'timestamp')
        ),
        'stats_screenresolution' => array(
            'obsoleteIndex'    => 'resolution',
            'unique' => array('resolution')
        ),
        'stats_search' => array(
            'change' => "`name` `name` VARCHAR(100) BINARY NOT NULL DEFAULT ''",
            'unique' => array('name')
        ),
        'stats_spiders' => array(
            'obsoleteIndex'    => 'page',
            'unique' => array('page'),
            'change' => "`page` `page` VARCHAR(100) BINARY DEFAULT NULL"
        ),
        'stats_spiders_summary'    => array(
            'obsoleteIndex'    => 'unqiue',
            'unique' => array('name'),
            'change' => "`name` `name` VARCHAR(255) BINARY NOT NULL DEFAULT ''"
        ),
        'stats_visitors_summary' => array(
            'obsoleteIndex'    => 'type',
            'unique' => array('type', 'timestamp')
        ),
        /************************************************
        * EXTENSION:    Unique key on sid attribte of   *
        *               table contrexx_statis_visitors  *
        * ADDED:        Contrexx v2.1.0                    *
        ************************************************/
        'stats_visitors' => array(
            'obsoleteIndex'    => 'sid',
            'unique' => array('sid'),
            'count'  => 'timestamp'
        )

    ) as $table => $arrUnique) {
        do {
            if (in_array($table, $_SESSION['contrexx_update']['update']['update_stats'])) {
                break;
            } elseif (!checkTimeoutLimit()) {
                return 'timeout';
            }

            if (isset($arrUnique['change'])) {
                $query = 'ALTER TABLE `'.DBPREFIX.$table.'` CHANGE '.$arrUnique['change'];
                if ($objDatabase->Execute($query) === false) {
                    return _databaseError($query, $objDatabase->ErrorMsg());
                }
            }

            $arrIndexes = $objDatabase->MetaIndexes(DBPREFIX.$table);
            if ($arrIndexes !== false) {
                if (isset($arrIndexes['unique'])) {
                    $_SESSION['contrexx_update']['update']['update_stats'][] = $table;
                    break;
                } elseif (isset($arrUnique['obsoleteIndex']) && isset($arrIndexes[$arrUnique['obsoleteIndex']])) {
                    $query = 'ALTER TABLE `'.DBPREFIX.$table.'` DROP INDEX `'.$arrUnique['obsoleteIndex'].'`';
                    if ($objDatabase->Execute($query) === false) {
                        return _databaseError($query, $objDatabase->ErrorMsg());
                    }
                }
            } else {
                setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.$table));
                return false;
            }

            #DBG::msg("table = $table");
            #DBG::dump($arrUnique);
            if (isset($arrUnique['unique'])) {
                $query = 'SELECT `'.implode('`,`', $arrUnique['unique']).'`, COUNT(`id`) AS redundancy FROM `'.DBPREFIX.$table.'` GROUP BY `'.implode('`,`', $arrUnique['unique']).'` ORDER BY redundancy DESC';
                $objEntry = $objDatabase->SelectLimit($query, 10);
                if ($objEntry !== false) {
                    while (!$objEntry->EOF) {
                        if (!checkTimeoutLimit()) {
                            return 'timeout';
                        }
                        $lastRedundancyCount = $objEntry->fields['redundancy'];
                        if ($objEntry->fields['redundancy'] > 1) {
                            $where = array();
                            foreach ($arrUnique['unique'] as $unique) {
                                $where[] = "`".$unique."` = '".addslashes($objEntry->fields[$unique])."'";
                            }
                            $query = 'DELETE FROM `'.DBPREFIX.$table.'` WHERE '.implode(' AND ', $where).' ORDER BY `'.(isset($arrUnique['count']) ? $arrUnique['count'] : 'count').'` LIMIT '.($objEntry->fields['redundancy']-1);
                            if ($objDatabase->Execute($query) === false) {
                                return _databaseError($query, $objDatabase->ErrorMsg());
                            }
                        } else {
                            break;
                        }
                        $objEntry->MoveNext();
                    }
                } else {
                    return _databaseError($query, $objDatabase->ErrorMsg());
                }
            }

            if ($objEntry->RecordCount() == 0 || $lastRedundancyCount < 2) {
                $query = 'ALTER IGNORE TABLE `'.DBPREFIX.$table.'` ADD UNIQUE `unique` (`'.implode('`,`', $arrUnique['unique']).'`)';
                if ($objDatabase->Execute($query) == false) {
                    return _databaseError($query, $objDatabase->ErrorMsg());
                }
                $_SESSION['contrexx_update']['update']['update_stats'][] = $table;
                break;
            }
        } while ($objEntry->RecordCount() > 1);
    }


    $arrIndexes = $objDatabase->MetaIndexes(DBPREFIX.'stats_search');
    if ($arrIndexes !== false) {
        if (isset($arrIndexes['unique'])) {
            $query = 'ALTER TABLE `'.DBPREFIX.'stats_search` DROP INDEX `unique`';
            if ($objDatabase->Execute($query) === false) {
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
        if(empty($_SESSION['contrexx_update']['update']['update_stats']['utf8'])){
            $query = "ALTER IGNORE TABLE `".DBPREFIX."stats_search` CHANGE `name` `name` VARCHAR( 100 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL";
            if($objDatabase->Execute($query)){
                $_SESSION['contrexx_update']['update']['update_stats']['utf8'] = 1;
                $query = "ALTER IGNORE TABLE `".DBPREFIX."stats_search` CHANGE `name` `name` VARCHAR( 100 ) CHARACTER SET binary NOT NULL";
                if($_SESSION['contrexx_update']['update']['update_stats']['utf8'] == 1 && $objDatabase->Execute($query)){
                    $_SESSION['contrexx_update']['update']['update_stats']['utf8'] = 2;
                    $query = "ALTER IGNORE TABLE `".DBPREFIX."stats_search` CHANGE `name` `name` VARCHAR( 100 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL";
                    if($_SESSION['contrexx_update']['update']['update_stats']['utf8'] == 2 && $objDatabase->Execute($query)){
                        $_SESSION['contrexx_update']['update']['update_stats']['utf8'] = 3;
                        $query = 'ALTER IGNORE TABLE `'.DBPREFIX.'stats_search` ADD UNIQUE `unique` (`name`, `external`)';
                        if ($objDatabase->Execute($query) === false) {
                            return _databaseError($query, $objDatabase->ErrorMsg());
                        }
                    }else{
                        return _databaseError($query, $objDatabase->ErrorMsg());
                    }
                }else{
                    return _databaseError($query, $objDatabase->ErrorMsg());
                }
            }else{
                return _databaseError($query, $objDatabase->ErrorMsg());
            }
        }
    } else {
        setUpdateMsg(sprintf($_ARRAYLANG['TXT_UNABLE_GETTING_DATABASE_TABLE_STRUCTURE'], DBPREFIX.'stats_search'));
        return false;
    }

    try {
        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'stats_search',
            array(
                'id'         => array('type' => 'INT(5)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                'name'       => array('type' => 'VARCHAR(100)', 'binary' => true, 'default' => ''),
                'count'      => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                'sid'        => array('type' => 'VARCHAR(32)', 'notnull' => true, 'default' => ''),
                'external'   => array('type' => 'ENUM(\'0\',\'1\')', 'notnull' => true, 'default' => '0')
            ),
            array(
                'unique'     => array('fields' => array('name','external'), 'type' => 'UNIQUE')
            )
        );

        \Cx\Lib\UpdateUtil::table(
            DBPREFIX.'stats_requests',
            array(
                  'id'             => array('type' => 'INT(9)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                  'timestamp'      => array('type' => 'INT(11)', 'default' => '0', 'notnull' => false, 'after' => 'id'),
                  'pageId'         => array('type' => 'INT(6)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'timestamp'),
                  'page'           => array('type' => 'VARCHAR(255)', 'after' => 'pageId', 'default' => '', 'binary' => true),
                  'visits'         => array('type' => 'INT(9)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'page'),
                  'sid'            => array('type' => 'VARCHAR(32)', 'after' => 'visits', 'default' => ''),
                  'pageTitle'      => array('type' => 'VARCHAR(250)', 'after' => 'sid') //this field is added
                  ),
            array(
                  'unique'         => array('fields' => array('page'), 'type' => 'UNIQUE')
                  )
        );
    }
    catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }

    try {
        //2.2.0: new config option 'exclude_identifying_info'
        \Cx\Lib\UpdateUtil::sql('
            INSERT INTO '.DBPREFIX.'stats_config (id, name, value, status)
            VALUES (20, "exclude_identifying_info", 1, 0)
            ON DUPLICATE KEY UPDATE `id` = `id`
        ');

    }
    catch (\Cx\Lib\UpdateException $e) {
        return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
    }


    return true;
}
