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

namespace Cx\Update\Cx_3_0_4;

set_time_limit(0);

class ContentMigration
{
    public $langs;
    public $similarPages;
    public $migrateLangIds = '';
    public $arrMigrateLangIds = array();
    public static $defaultLang;
    protected $nodeArr = array();
    protected $moduleNames = array();
    protected $availableFrontendLanguages = array();
    protected static $em;

    public function __construct()
    {
        if (\DBG::getMode() & DBG_ADODB_TRACE) {
            \DBG::enable_adodb_debug(true);
        } elseif (\DBG::getMode() & DBG_ADODB || \DBG::getMode() & DBG_ADODB_ERROR) {
            \DBG::enable_adodb_debug();
        } else {
            \DBG::disable_adodb_debug();
        }

        self::$em = \Env::get('em');
        self::$defaultLang = \FWLanguage::getDefaultLangId();

        $this->initModuleNames();
    }

    private function initModuleNames()
    {
        $this->moduleNames = array();

        try {
            $objModules = \Cx\Lib\UpdateUtil::sql('SELECT `id`, `name` FROM `'.DBPREFIX.'modules`');
        } catch (\Cx\Lib\UpdateException $e) {
            \DBG::msg(\Cx\Lib\UpdateUtil::DefaultActionHandler($e));
        }

        while (!$objModules->EOF) {
            $this->moduleNames[$objModules->fields['id']] = $objModules->fields['name'];
            $objModules->MoveNext();
        }
    }

    protected function getSortedNodes($objResult, $visiblePageIDs) {
        $arrSortedNodes = array();

        while (!$objResult->EOF) {
            $catId = $objResult->fields['catid'];

            // Skip ghosts
            if (!in_array($catId, $visiblePageIDs)) {
                $objResult->MoveNext();
                continue;
            }

            // Skip existing nodes
            if(!isset($this->nodeArr[$catId])) {
                $this->nodeArr[$catId] = new \Cx\Core\ContentManager\Model\Entity\Node();
            }
            $arrSortedNodes[] = $this->nodeArr[$catId];

            $objResult->MoveNext();
        }

        //return array_reverse($arrSortedNodes);
        return $arrSortedNodes;
    }

    public function migrate()
    {
        try {
            if (empty($_SESSION['contrexx_update']['tables_created'])) {
                if (!\Cx\Lib\UpdateUtil::table_exist(DBPREFIX . 'content')) {
                    return true;
                }

                \Cx\Lib\UpdateUtil::drop_table(DBPREFIX . 'content_page');
                \Cx\Lib\UpdateUtil::drop_table(DBPREFIX . 'content_node');
                \Cx\Lib\UpdateUtil::drop_table(DBPREFIX . 'log_entry');

                \Cx\Lib\UpdateUtil::table(
                    DBPREFIX . 'content_node',
                    array(
                        'id'                                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'parent_id'                          => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'id'),
                        'lft'                                => array('type' => 'INT(11)', 'after' => 'parent_id'),
                        'rgt'                                => array('type' => 'INT(11)', 'after' => 'lft'),
                        'lvl'                                => array('type' => 'INT(11)', 'after' => 'rgt')
                    ),
                    array(
                        'IDX_E5A18FDD727ACA70'               => array('fields' => array('parent_id'))
                    ),
                    'InnoDB',
                    '',
                    array(
                        'parent_id' => array(
                            'table' => DBPREFIX.'content_node',
                            'column'    => 'id',
                            'onDelete'  => 'NO ACTION',
                            'onUpdate'  => 'NO ACTION',
                        ),
                    )
                );

                \Cx\Lib\UpdateUtil::table(
                    DBPREFIX . 'content_page',
                    array(
                        'id'                                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'node_id'                            => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'id'),
                        'nodeIdShadowed'                     => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'node_id'),
                        'lang'                               => array('type' => 'INT(11)', 'after' => 'nodeIdShadowed'),
                        'type'                               => array('type' => 'VARCHAR(16)', 'after' => 'lang'),
                        'caching'                            => array('type' => 'TINYINT(1)', 'after' => 'type'),
                        'updatedAt'                          => array('type' => 'timestamp', 'after' => 'caching', 'notnull' => false),
                        'updatedBy'                          => array('type' => 'CHAR(40)', 'after' => 'updatedAt'),
                        'title'                              => array('type' => 'VARCHAR(255)', 'after' => 'updatedBy'),
                        'linkTarget'                         => array('type' => 'VARCHAR(16)', 'notnull' => false, 'after' => 'title'),
                        'contentTitle'                       => array('type' => 'VARCHAR(255)', 'after' => 'linkTarget'),
                        'slug'                               => array('type' => 'VARCHAR(255)', 'after' => 'contentTitle'),
                        'content'                            => array('type' => 'longtext', 'after' => 'slug'),
                        'sourceMode'                         => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '0', 'after' => 'content'),
                        'customContent'                      => array('type' => 'VARCHAR(64)', 'notnull' => false, 'after' => 'sourceMode'),
                        'useCustomContentForAllChannels'     => array('type' => 'INT(2)', 'after' => 'customContent', 'notnull' => false),
                        'cssName'                            => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'useCustomContentForAllChannels'),
                        'cssNavName'                         => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'cssName'),
                        'skin'                               => array('type' => 'INT(11)', 'notnull' => false, 'after' => 'cssNavName'),
                        'useSkinForAllChannels'              => array('type' => 'INT(2)', 'after' => 'skin', 'notnull' => false),
                        'metatitle'                          => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'useSkinForAllChannels'),
                        'metadesc'                           => array('type' => 'text', 'after' => 'metatitle'),
                        'metakeys'                           => array('type' => 'text', 'after' => 'metadesc'),
                        'metarobots'                         => array('type' => 'VARCHAR(7)', 'notnull' => false, 'after' => 'metakeys'),
                        'start'                              => array('type' => 'timestamp', 'after' => 'metarobots', 'notnull' => false),
                        'end'                                => array('type' => 'timestamp', 'after' => 'start', 'notnull' => false),
                        'editingStatus'                      => array('type' => 'VARCHAR(16)', 'after' => 'end'),
                        'protection'                         => array('type' => 'INT(11)', 'after' => 'editingStatus'),
                        'frontendAccessId'                   => array('type' => 'INT(11)', 'after' => 'protection'),
                        'backendAccessId'                    => array('type' => 'INT(11)', 'after' => 'frontendAccessId'),
                        'display'                            => array('type' => 'TINYINT(1)', 'after' => 'backendAccessId'),
                        'active'                             => array('type' => 'TINYINT(1)', 'after' => 'display'),
                        'target'                             => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'active'),
                        'module'                             => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'target'),
                        'cmd'                                => array('type' => 'VARCHAR(50)', 'notnull' => true, 'default' => '', 'after' => 'module')
                    ),
                    array(
                        'node_id'                            => array('fields' => array('node_id','lang'), 'type' => 'UNIQUE'),
                        'IDX_D8E86F54460D9FD7'               => array('fields' => array('node_id'))
                    ),
                    'InnoDB',
                    '',
                    array(
                        'node_id' => array(
                            'table'     => DBPREFIX.'content_node',
                            'column'    => 'id',
                            'onDelete'  => 'SET NULL',
                            'onUpdate'  => 'NO ACTION',
                        ),
                   )
                );

                \Cx\Lib\UpdateUtil::table(
                    DBPREFIX . 'log_entry',
                    array(
                        'id'                 => array('type' => 'INT(11)', 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'action'             => array('type' => 'VARCHAR(8)', 'after' => 'id'),
                        'logged_at'          => array('type' => 'timestamp', 'after' => 'action', 'notnull' => false),
                        'version'            => array('type' => 'INT(11)', 'after' => 'logged_at'),
                        'object_id'          => array('type' => 'VARCHAR(32)', 'notnull' => false, 'after' => 'version'),
                        'object_class'       => array('type' => 'VARCHAR(255)', 'after' => 'object_id'),
                        'data'               => array('type' => 'longtext', 'after' => 'object_class', 'notnull' => false),
                        'username'           => array('type' => 'VARCHAR(255)', 'notnull' => false, 'after' => 'data')
                    ),
                    array(
                        'log_class_unique_version_idx' => array('fields' => array('version','object_id','object_class'), 'type' => 'UNIQUE'),
                        'log_class_lookup_idx' => array('fields' => array('object_class')),
                        'log_date_lookup_idx' => array('fields' => array('logged_at')),
                        'log_user_lookup_idx' => array('fields' => array('username'))
                    ),
                    'InnoDB'
                );

                $_SESSION['contrexx_update']['tables_created'] = true;
                if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                    return 'timeout';
                }
            }
        } catch (\Cx\Lib\UpdateException $e) {
            \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
            return false;
        }

        $pageRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');

        $this->nodeArr = array();

        if (empty($_SESSION['contrexx_update']['root_node_added'])) {
            // This will be the root of the site-tree
            $root = new \Cx\Core\ContentManager\Model\Entity\Node();
            self::$em->persist($root);
            self::$em->flush();
            $_SESSION['contrexx_update']['root_node_added'] = true;
        } else {
            $root = $nodeRepo->getRoot();
        }

        // Due to a bug in the old content manager, there happened to exist ghost-pages
        // that were never visible, neither in the frontend, nor in the backend.
        // Therefore, we'll need a list of these ghost-pages so that we won't migrate them.
        // (because those ghost-pages would probably break the new site-tree)
        $visiblePageIDs = $this->getVisiblePageIDs();
        if ($visiblePageIDs === false) {
            return false;
        }

        if (empty($_SESSION['contrexx_update']['nodes_added'])) {
            // Fetch a list of all pages that have ever existed or still do.
            // (sql-note: join content tables to prevent to migrate body-less pages)
            try {
                $objNodeResult = \Cx\Lib\UpdateUtil::sql('
                        SELECT `catid`, `parcat`, `lang`, `displayorder`
                          FROM `'.DBPREFIX.'content_navigation_history` AS tnh
                    INNER JOIN `'.DBPREFIX.'content_history` AS tch
                            ON tch.`id` = tnh.`id`
                         WHERE tnh.`lang` IN (' . $this->migrateLangIds . ')
                    UNION DISTINCT
                        SELECT `catid`, `parcat`, `lang`, `displayorder`
                          FROM `'.DBPREFIX.'content_navigation` AS tn
                    INNER JOIN `'.DBPREFIX.'content` AS tc
                            ON tc.`id` = tn.`catid`
                         WHERE tn.`lang` IN (' . $this->migrateLangIds . ')
                      ORDER BY `parcat`, `displayorder`, `lang` ASC
                ');
            } catch (\Cx\Lib\UpdateException $e) {
                \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
                return false;
            }

            // Create a node for each page that ever existed or still does
            // and put them in the array $this->nodeArr
            $arrSortedNodes = $this->getSortedNodes($objNodeResult, $visiblePageIDs);

            if (!empty($_SESSION['contrexx_update']['nodes'])) {
                foreach ($_SESSION['contrexx_update']['nodes'] as $catId => $nodeId) {
                    $node = $nodeRepo->find($nodeId);
                    if ($node) {
                        self::$em->remove($node);
                    }
                }
                self::$em->flush();
                unset($_SESSION['contrexx_update']['nodes']);
            }

            foreach ($arrSortedNodes as $node) {
                self::$em->persist($node);
            }
            self::$em->flush();

            if (!isset($_SESSION['contrexx_update']['nodes'])) {
                $_SESSION['contrexx_update']['nodes'] = array();
            }
            foreach ($this->nodeArr as $catId => $node) {
                $_SESSION['contrexx_update']['nodes'][$catId] = $node->getId();
            }

            $_SESSION['contrexx_update']['nodes_added'] = true;
            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }
        } else {
            if (!empty($_SESSION['contrexx_update']['nodes'])) {
                \DBG::msg('Load nodes..');
                foreach ($_SESSION['contrexx_update']['nodes'] as $catId => $nodeId) {
                    $node = $nodeRepo->find($nodeId);
                    $this->nodeArr[$catId] = $node;
                }
            }
        }

        $p = array();

        if (empty($_SESSION['contrexx_update']['history_pages_added'])) {
            // 1ST: MIGRATE PAGES FROM HISTORY
            try {
                $hasCustomContent = false;
                if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'content_navigation_history', 'custom_content')) {
                    $hasCustomContent = true;
                }

                if (!empty($_SESSION['contrexx_update']['history_pages_index'])) {
                    $limit = 'LIMIT ' . $_SESSION['contrexx_update']['history_pages_index'] . ', 18446744073709551615';
                } else {
                    $limit = '';
                }

                $objResult = \Cx\Lib\UpdateUtil::sql('
                    SELECT
                        cn.page_id, cn.content, cn.title, cn.metatitle, cn.metadesc, cn.metakeys, cn.metarobots, cn.css_name, cn.redirect, cn.expertmode,
                        nav.catid, nav.parcat, nav.catname, nav.target, nav.displayorder, nav.displaystatus, nav.activestatus,
                        nav.cachingstatus, nav.username, nav.changelog, nav.cmd, nav.lang, nav.module, nav.startdate, nav.enddate, nav.protected,
                        nav.frontend_access_id, nav.backend_access_id, nav.themes_id, nav.css_name AS css_nav_name, '.($hasCustomContent ? 'nav.custom_content,' : '').'
                        cnlog.action, cnlog.history_id, cnlog.is_validated
                    FROM       `' . DBPREFIX . 'content_history` AS cn
                    INNER JOIN `' . DBPREFIX . 'content_navigation_history` AS nav
                    ON         cn.id = nav.id
                    INNER JOIN `' . DBPREFIX . 'content_logfile` AS cnlog
                    ON         cn.id = cnlog.history_id
                    WHERE      nav.`lang` IN (' . $this->migrateLangIds . ')
                    ORDER BY cnlog.id ASC
                    ' . $limit . '
                ');
            } catch (\Cx\Lib\UpdateException $e) {
                \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
                return false;
            }

            // Migrate history
            if ($objResult !== false) {
                $historyPagesIndex = !empty($_SESSION['contrexx_update']['history_pages_index']) ? $_SESSION['contrexx_update']['history_pages_index'] : 0;

                while (!$objResult->EOF) {
                    if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                        $_SESSION['contrexx_update']['history_pages_index'] = $historyPagesIndex;
                        return 'timeout';
                    }

                    $catId = $objResult->fields['catid'];

                    // Skip ghosts
                    if (!in_array($catId, $visiblePageIDs)) {
                        $objResult->MoveNext();
                        continue;
                    }

                    // SET PARENT NODE
                    if ($objResult->fields['parcat'] == 0 || !isset($this->nodeArr[$objResult->fields['parcat']])) {
                        // Page was located on the first level in the site-tree.
                        // Therefore, directly attach it to the ROOT-node
                        $this->nodeArr[$catId]->setParent($root);
                    } else {
                        // Attach page to associated parent node
                        $this->nodeArr[$catId]->setParent($this->nodeArr[$objResult->fields['parcat']]);
                    }

                    $page = null;
                    if (!empty($_SESSION['contrexx_update']['pages'][$catId])) {
                        $pageId = $_SESSION['contrexx_update']['pages'][$catId];
                        $page   = $pageRepo->find($pageId);
                    }

                    if (!isset($_SESSION['contrexx_update']['pages'])) {
                        $_SESSION['contrexx_update']['pages'] = array();
                    }

                    // CREATE PAGE
                    switch ($objResult->fields['action']) {
                        case 'new':
                        case 'update':
                            if (empty($page)) {
                                $page = new \Cx\Core\ContentManager\Model\Entity\Page();
                            }

                            $this->_setPageRecords($objResult, $this->nodeArr[$catId], $page);
                            self::$em->persist($page);
                            self::$em->flush();
                            $_SESSION['contrexx_update']['pages'][$catId] = $page->getId();
                            break;
                        case 'delete':
                            if (!empty($page)) {
                                self::$em->remove($page);
                                self::$em->flush();
                                if (isset($_SESSION['contrexx_update']['pages'][$catId])) {
                                    unset($_SESSION['contrexx_update']['pages'][$catId]);
                                }
                            }
                            break;
                    }

                    $historyPagesIndex ++;
                    $objResult->MoveNext();
                }

                $_SESSION['contrexx_update']['history_pages_added'] = true;
            }
        }

        if (empty($_SESSION['contrexx_update']['pages_added'])) {
            // 2ND: MIGRATE CURRENT CONTENT
            try {
                $hasCustomContent = false;
                if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'content_navigation', 'custom_content')) {
                    $hasCustomContent = true;
                }

                if (!empty($_SESSION['contrexx_update']['pages_index'])) {
                    $limit = 'LIMIT ' . $_SESSION['contrexx_update']['pages_index'] . ', 18446744073709551615';
                } else {
                    $limit = '';
                }

                $objRecords = \Cx\Lib\UpdateUtil::sql('
                    SELECT cn.id, cn.content, cn.title, cn.metatitle, cn.metadesc, cn.metakeys, cn.metarobots, cn.css_name, cn.redirect, cn.expertmode,
                           nav.catid, nav.parcat, nav.catname, nav.target, nav.displayorder, nav.displaystatus, nav.activestatus,
                           nav.cachingstatus, nav.username, nav.changelog, nav.cmd, nav.lang, nav.module, nav.startdate, nav.enddate, nav.protected,
                           nav.frontend_access_id, nav.backend_access_id, nav.themes_id, nav.css_name AS css_nav_name '.($hasCustomContent ? ', nav.custom_content' : '').'
                    FROM       `' . DBPREFIX . 'content` AS cn
                    INNER JOIN `' . DBPREFIX . 'content_navigation` AS nav
                    ON         cn.id = nav.catid
                    WHERE      cn.id
                    AND        nav.`lang` IN (' . $this->migrateLangIds . ')
                    ORDER BY   nav.parcat ASC, nav.displayorder ASC
                    ' . $limit . '
                ');
            } catch (\Cx\Lib\UpdateException $e) {
                \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
                return false;
            }

            if ($objRecords !== false) {
                $pagesIndex = !empty($_SESSION['contrexx_update']['pages_index']) ? $_SESSION['contrexx_update']['pages_index'] : 0;

                while (!$objRecords->EOF) {
                    if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                        $_SESSION['contrexx_update']['pages_index'] = $pagesIndex;
                        return 'timeout';
                    }

                    $catId = $objRecords->fields['catid'];

                    // Skip ghosts
                    if(!in_array($catId, $visiblePageIDs)) {
                        $objRecords->MoveNext();
                        continue;
                    }

                    if (!isset($this->nodeArr[$catId])) {
                        setUpdateMsg('Trying to migrate non-existing node: id ' . $catId);
                        return false;
                    }

                    //$node = $this->nodeArr[$catId];

                    if ($objRecords->fields['parcat'] == 0 || !isset($this->nodeArr[$objRecords->fields['parcat']])) {
                        // Page was located on the first level in the site-tree.
                        // Therefore, directly attach it to the ROOT-node
                        //$node->setParent($root);
                        $this->nodeArr[$catId]->setParent($root);
                    } else {
                        // Attach page to associated parent node
                        //$node->setParent($this->nodeArr[$objRecords->fields['parcat']]);
                        $this->nodeArr[$catId]->setParent($this->nodeArr[$objRecords->fields['parcat']]);
                    }

                    \DBG::msg('Migrate page '.$objRecords->fields['catname'].' (catid: '.$catId.' | lang: '.$objRecords->fields['lang'].')');
                    self::$em->persist($this->nodeArr[$catId]);
                    self::$em->flush();
                    $nodeRepo->moveDown($this->nodeArr[$catId], true);
                    self::$em->persist($this->nodeArr[$catId]);

                    if (!empty($_SESSION['contrexx_update']['pages'][$catId])) {
                        $pageId = $_SESSION['contrexx_update']['pages'][$catId];
                        $page   = $pageRepo->find($pageId);
                    } else {
                        $page = new \Cx\Core\ContentManager\Model\Entity\Page();
                    }

                    $this->_setPageRecords($objRecords, $this->nodeArr[$catId], $page);
                    $page->setAlias(array('legacy_page_' . $catId));

                    self::$em->persist($page);
                    self::$em->flush();
                    $_SESSION['contrexx_update']['pages'][$catId] = $page->getId();

                    $pagesIndex ++;
                    $objRecords->MoveNext();
                }

                $_SESSION['contrexx_update']['pages_added'] = true;
            }
        }

        foreach ($this->arrMigrateLangIds as $langId) {
            $objResult = \Cx\Lib\UpdateUtil::sql('
                SELECT `catid`
                FROM `' . DBPREFIX . 'content_navigation`
                WHERE `module` = 15
                AND `cmd` = \'\'
                AND `lang` = ' . $langId . '
                ORDER BY `parcat` ASC
                LIMIT 1
            ');
            if (($objResult === false) || ($objResult && empty($objResult->fields['catid']))) {
                continue;
            }
            $homeCatId = $objResult->fields['catid'];

            $objResult = \Cx\Lib\UpdateUtil::sql('
                SELECT `catid`
                FROM `' . DBPREFIX . 'content_navigation`
                WHERE `module` = 15
                AND `cmd` = \'\'
                AND `catid` <> ' . $homeCatId . '
                AND `lang` = ' . $langId . '
            ');
            if ($objResult->RecordCount()) {
                $i = 1;
                while (!$objResult->EOF) {
                    $catId = $objResult->fields['catid'];
                    $aliasPage = $pageRepo->findOneBy(array(
                        'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_ALIAS,
                        'slug' => 'legacy_page_' . $catId,
                    ), true);
                    if ($aliasPage) {
                        $targetPage = $pageRepo->getTargetPage($aliasPage);
                        if ($targetPage) {
                            $this->_setCmd($targetPage, $i);
                            self::$em->persist($targetPage);
                        }
                    }
                    $objResult->MoveNext();
                    $i++;
                }
                self::$em->flush();
            }
        }

        return true;
    }

    function _setCmd($page, $cmd) {
        $origCmd = $cmd;
        $cmd = preg_replace('/[^-A-Za-z0-9_]+/', '_', $origCmd);
        if (!isset($_SESSION['contrexx_update']['modified_cmds'])) {
            $_SESSION['contrexx_update']['modified_cmds'] = array();
        }
        if ($cmd != $origCmd) {
            // add message like 'Cmd of page {title} has been changed to {newcmd} (was {oldcmd})'
            $_SESSION['contrexx_update']['modified_cmds'][] = array(
                'pageTitle' => $page->getTitle(),
                'newCmd'    => $cmd,
                'origCmd'   => $origCmd,
            );
        }
        $page->setCmd($cmd);
    }

    function _setPageRecords($objResult, $node, $page)
    {
        $title         = html_entity_decode($objResult->fields['catname'], ENT_QUOTES, CONTREXX_CHARSET);
        $contentTitle  = !empty($objResult->fields['title']) ? html_entity_decode($objResult->fields['title'], ENT_QUOTES, CONTREXX_CHARSET) : $title;
        $metaTitle     = html_entity_decode($objResult->fields['metatitle'], ENT_QUOTES, CONTREXX_CHARSET);
        $metaDesc      = html_entity_decode($objResult->fields['metadesc'], ENT_QUOTES, CONTREXX_CHARSET);
        $metaKeys      = html_entity_decode($objResult->fields['metakeys'], ENT_QUOTES, CONTREXX_CHARSET);
        $customContent = isset($objResult->fields['custom_content']) ? $objResult->fields['custom_content'] : '';

        $page->setNode($node);
        $page->setNodeIdShadowed($node->getId());
        $page->setLang($objResult->fields['lang']);
        $page->setCaching($objResult->fields['cachingstatus']);
        $page->setTitle($title);
        $page->setContentTitle($contentTitle);
        $page->setSlug($title);
        $page->setMetatitle($metaTitle);
        $page->setMetadesc($metaDesc);
        $page->setMetakeys($metaKeys);
        $page->setCustomContent($customContent);
        $page->setContent($objResult->fields['content']);
        $page->setCssName($objResult->fields['css_name']);
        $page->setMetarobots($objResult->fields['metarobots']);
        $page->setDisplay($objResult->fields['displaystatus'] === 'on' ? 1 : 0);
        $page->setActive($objResult->fields['activestatus']);
        $page->setSourceMode($objResult->fields['expertmode'] == 'y');
        $page->setUpdatedBy($objResult->fields['username']);
        $page->setCssNavName($objResult->fields['css_nav_name']);
        $page->setSkin($objResult->fields['themes_id']);
        $page->setProtection($objResult->fields['protected']);
        $page->setFrontendAccessId($objResult->fields['frontend_access_id']);
        $page->setBackendAccessId($objResult->fields['backend_access_id']);
        $page->setTarget($objResult->fields['redirect']);

        $updatedAt = new \DateTime();
        $updatedAt->setTimestamp($objResult->fields['changelog']);
        $page->setUpdatedAt($updatedAt);

        if ($objResult->fields['startdate'] != '0000-00-00') {
            $start = new \DateTime($objResult->fields['startdate']);
            $page->setStart($start);
        }

        if ($objResult->fields['enddate'] != '0000-00-00') {
            $end = new \DateTime($objResult->fields['enddate']);
            $page->setEnd($end);
        }

        $linkTarget = $objResult->fields['target'];
        if (!$linkTarget) {
            $linkTarget = null;
        }
        $page->setLinkTarget($linkTarget);

        $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_CONTENT);
        if ($objResult->fields['module'] && isset($this->moduleNames[$objResult->fields['module']])) {
            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_APPLICATION);
            $page->setModule($this->moduleNames[$objResult->fields['module']]);
        }
        $this->_setCmd($page, $objResult->fields['cmd']);

        if ($page->getTarget()) {
            $page->setType(\Cx\Core\ContentManager\Model\Entity\Page::TYPE_REDIRECT);
        }

        $page->validate();
    }

    public function migrateAliases()
    {
        try {
            if (!\Cx\Lib\UpdateUtil::table_exist(DBPREFIX . 'module_alias_source')) {
                return true;
            }

            $objResult = \Cx\Lib\UpdateUtil::sql('
                SELECT `s`.`url` AS `slug`, `t`.`type`, `t`.`url` AS `target`
                FROM       `' . DBPREFIX . 'module_alias_source` AS `s`
                INNER JOIN `' . DBPREFIX . 'module_alias_target` AS `t`
                ON `s`.`target_id` = `t`.`id`
            ');
        } catch (\Cx\Lib\UpdateException $e) {
            \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
            return false;
        }

        $arrAliases = array();
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $arrAliases[$objResult->fields['target']][$objResult->fields['slug']] = $objResult->fields['type'];
                $objResult->MoveNext();
            }
        }

        foreach ($arrAliases as $target => $arrSlugs) {
            foreach ($arrSlugs as $slug => $type) {
                $pageRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

                if ($type === 'local') {
                    $aliasPage = $pageRepo->findOneBy(array(
                        'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_ALIAS,
                        'slug' => 'legacy_page_' . $target,
                    ), true);

                    if ($aliasPage) {
                        $targetPage = $pageRepo->getTargetPage($aliasPage);
                        if ($targetPage) {
                            $objAliasLib = new \aliasLib($targetPage->getLang());
                            $objAliasLib->_saveAlias($slug, $aliasPage->getTarget(), true);
                        }
                    }
                } else {
                    $objAliasLib = new \aliasLib(\FWLanguage::getDefaultLangId());
                    $objAliasLib->_saveAlias($slug, $target, false);
                }
            }
        }

        return true;
    }

    public function migrateBlocks()
    {
        global $objUpdate, $_CONFIG;

        try {
            if (!\Cx\Lib\UpdateUtil::table_exist(DBPREFIX . 'module_block_rel_lang')) {
                return true;
            }

            if (empty($_SESSION['contrexx_update']['blocks_part_1_migrated'])) {
                // 3.0.3 : add new column `seperator`
                \Cx\Lib\UpdateUtil::table(
                    DBPREFIX.'module_block_categories',
                    array(
                        'id'             => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'parent'         => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
                        'name'           => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'parent'),
                        'seperator'      => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'name'),
                        'order'          => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'seperator'),
                        'status'         => array('type' => 'TINYINT(1)', 'notnull' => true, 'default' => '1', 'after' => 'order')
                    )
                );

                // migrate content -> related multi language
                \Cx\Lib\UpdateUtil::table(
                    DBPREFIX . 'module_block_rel_lang_content',
                    array(
                        'block_id'       => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0'),
                        'lang_id'        => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'default' => '0', 'after' => 'block_id'),
                        'content'        => array('type' => 'mediumtext', 'after' => 'lang_id'),
                        'active'         => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'content')
                    ),
                    array(
                        'id_lang'        => array('fields' => array('block_id','lang_id'), 'type' => 'UNIQUE')
                    )
                );

                // Only from version 2
                if (!$objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.0.0')) {
                    $subQuery = '
                        IFNULL(
                            (
                                SELECT 1
                                FROM `' . DBPREFIX . 'module_block_rel_lang` as `rl`
                                WHERE `rl`.`block_id` = `b`.`id`
                                AND `rl`.`lang_id` = `l`.`id`
                            ), 0
                        ) as `active`
                    ';
                }

                \Cx\Lib\UpdateUtil::sql('
                    INSERT IGNORE INTO `' . DBPREFIX . 'module_block_rel_lang_content` (`block_id`, `lang_id`, `content`, `active`)
                    SELECT DISTINCT `b`.`id` AS `id`, `l`.`id` AS `lang_id`, `b`.`content` AS `content`, ' . (!empty($subQuery) ? $subQuery : 1) . '
                    FROM `' . DBPREFIX . 'languages` AS `l`, `' . DBPREFIX . 'module_block_blocks` AS `b`
                    WHERE `l`.`frontend` = \'1\'
                    ORDER BY `b`.`id`
                ');

                // Deactivate all blocks of which none of their content is active
                \Cx\Lib\UpdateUtil::sql('
                    UPDATE `' . DBPREFIX . 'module_block_blocks` AS tblBlock
                    SET tblBlock.`active` = 0
                    WHERE tblBlock.`id` NOT IN (
                        SELECT `block_id` FROM `' . DBPREFIX . 'module_block_rel_lang_content` WHERE `active` = 1
                    )
                ');

                // fetch active blocks
                $arrActiveBlockIds = array();
                $activeBlockList = '';
                $objResult = \Cx\Lib\UpdateUtil::sql('
                    SELECT `block_id`
                    FROM `' . DBPREFIX . 'module_block_rel_lang_content`
                    WHERE `active` = 1
                ');

                if ($objResult && !$objResult->EOF) {
                    while (!$objResult->EOF) {
                        $arrActiveBlockIds[] = $objResult->fields['block_id'];
                        $objResult->MoveNext();
                    }
                    $activeBlockList = join(', ', $arrActiveBlockIds);
                }

                // Activate block's content of system's default language for all blocks that have no active content at all
                \Cx\Lib\UpdateUtil::sql('
                    UPDATE `' . DBPREFIX . 'module_block_rel_lang_content` AS tblContent
                    SET tblContent.`active` = 1
                    WHERE tblContent.`lang_id` = ( SELECT CL.`id` FROM `' . DBPREFIX . 'languages` AS CL WHERE CL.`frontend` AND CL.`is_default` = \'true\' )
                    '.($activeBlockList ? 'AND tblContent.`block_id` NOT IN ('.$activeBlockList.')' : '')
                );

                // Set the correct value for field global
                $activeLangIds = implode(',', \FWLanguage::getIdArray());
                $objResult     = \Cx\Lib\UpdateUtil::sql('
                    SELECT `block_id`, `lang_id`, `all_pages`
                    FROM `' . DBPREFIX . 'module_block_rel_lang`
                    WHERE lang_id IN (' . $activeLangIds . ')
                ');

                // 1. drop old locale column `content`
                // 2. add several new columns
                // 3. add columns `direct`, `category`
                \Cx\Lib\UpdateUtil::table(
                    DBPREFIX.'module_block_blocks',
                    array(
                        'id'                 => array('type' => 'INT(10)', 'unsigned' => true, 'notnull' => true, 'auto_increment' => true, 'primary' => true),
                        'start'              => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'id'),
                        'end'                => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'start'),
                        'name'               => array('type' => 'VARCHAR(255)', 'notnull' => true, 'default' => '', 'after' => 'end'),
                        'random'             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'name'),
                        'random_2'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'random'),
                        'random_3'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'random_2'),
                        'random_4'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'random_3'),
                        'global'             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'random_4'),
                        'category'           => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'global'),
                        'direct'             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'category'),
                        'active'             => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'direct'),
                        'order'              => array('type' => 'INT(1)', 'notnull' => true, 'default' => '0', 'after' => 'active'),
                        'cat'                => array('type' => 'INT(10)', 'notnull' => true, 'default' => '0', 'after' => 'order'),
                        'wysiwyg_editor'     => array('type' => 'INT(1)', 'notnull' => true, 'default' => '1', 'after' => 'cat')
                    )
                );

                if ($objResult->RecordCount()) {
                    $arrGlobalDefinitions = array();
                    while (!$objResult->EOF) {
                        $arrGlobalDefinitions[$objResult->fields['block_id']][$objResult->fields['lang_id']] = $objResult->fields['all_pages'];
                        $objResult->MoveNext();
                    }

                    foreach ($arrGlobalDefinitions as $blockId => $arrValues) {
                        // Set $global by default to 2
                        $global = 2;
                        // If all languages of the block are global, set $global to 1
                        if (!in_array(0, $arrValues)) {
                            // Language id's of blocks where field 'all_pages' (global) is set to 1
                            $globalLangIds = array_keys($arrValues);
                            sort($globalLangIds);

                            // Active language id's
                            $activeLangIds = \FWLanguage::getIdArray();
                            sort($activeLangIds);

                            if (($globalLangIds == $activeLangIds) || !$objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.0.0')) {
                                $global = 1;
                            }
                        }

                        \Cx\Lib\UpdateUtil::sql('
                            UPDATE `' . DBPREFIX . 'module_block_blocks`
                            SET `global` = ' . $global . '
                            WHERE `id` = ' . $blockId . '
                        ');

                        // only for contrexx 2.x
                        if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.0')
                            && !$objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.0.0')) {
                            // use direct placeholder to set the pages where to show
                            // only do this if the global was set to 2 (show on each selected page)
                            $direct = ($global == 2 ? 1 : 0);
                            // if there is no association from block to page and the global placeholder was set to
                            // "only show on"... the direct placeholder has been shown on any page
                            $objBlockPagesResult = \Cx\Lib\UpdateUtil::sql(
                                'SELECT COUNT(1) as `count`
                                    FROM `' . DBPREFIX . 'module_block_rel_pages`
                                        WHERE `block_id` = ?',
                                array($blockId)
                            );
                            if ($objBlockPagesResult->fields['count'] == 0 && $direct == 1) {
                                $direct = 0;
                            }
                            \Cx\Lib\UpdateUtil::sql('
                                UPDATE `' . DBPREFIX . 'module_block_blocks`
                                SET `global` = ' . $global . ',
                                    `direct` = ' . $direct . '
                                WHERE `id` = ' . $blockId . '
                            ');
                        }
                    }
                }

                $_SESSION['contrexx_update']['blocks_part_1_migrated'] = true;
                if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                    return false;
                }
            }

            if (empty($_SESSION['contrexx_update']['blocks_part_2_migrated'])) {
                $activeLangIds = implode(',', \FWLanguage::getIdArray());

                if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX . 'module_block_rel_pages', 'lang_id')
                    || !empty($_SESSION['contrexx_update']['blocks_part_2_migrated_timeout'])) {

                    // create temporary table
                    \Cx\Lib\UpdateUtil::table(
                        DBPREFIX.'module_block_rel_pages_tmp',
                        array(
                            'block_id'       => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0', 'primary' => true),
                            'page_id'        => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0', 'after' => 'block_id', 'primary' => true),
                            'placeholder'    => array('type' => 'ENUM(\'global\',\'direct\',\'category\')', 'notnull' => true, 'default' => 'global', 'after' => 'page_id', 'primary' => true)
                        )
                    );
                    \DBG::msg('BLOCK MIGRATION: GENERATED TEMPORARY TABLE');

                    if (\Cx\Lib\UpdateUtil::column_exist(DBPREFIX . 'module_block_rel_pages', 'lang_id')) {
                        \Cx\Lib\UpdateUtil::sql('
                            DELETE FROM `' . DBPREFIX . 'module_block_rel_pages`
                            WHERE `lang_id` NOT IN (' . $activeLangIds . ')
                        ');

                        // 3.0.3 : add new column `placeholder`
                        \Cx\Lib\UpdateUtil::table(
                            DBPREFIX.'module_block_rel_pages',
                            array(
                                'block_id'       => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0'),
                                'page_id'        => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0', 'after' => 'block_id'),
                                'placeholder'    => array('type' => 'ENUM(\'global\',\'direct\',\'category\')', 'notnull' => true, 'default' => 'global', 'after' => 'page_id')
                            )
                        );
                    }

                    // now the old page ids can be moved to the temporary table with the new page ids
                    $objResult = \Cx\Lib\UpdateUtil::sql('
                        SELECT `block_id`, `page_id`
                        FROM `' . DBPREFIX . 'module_block_rel_pages`
                    ');

                    if ($objResult->RecordCount()) {
                        // get the page repository
                        $pageRepo  = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');

                        // loop through all old entries
                        while (!$objResult->EOF) {
                            $blockId   = $objResult->fields['block_id'];
                            $oldPageId = $objResult->fields['page_id'];

                            $aliasPage = $pageRepo->findOneBy(array(
                                'type' => \Cx\Core\ContentManager\Model\Entity\Page::TYPE_ALIAS,
                                'slug' => 'legacy_page_' . $oldPageId,
                            ), true);

                            // make new entry
                            if ($aliasPage) {
                                $page = $pageRepo->getTargetPage($aliasPage);
                                if ($page) {
                                    \Cx\Lib\UpdateUtil::sql('
                                        INSERT IGNORE INTO `' . DBPREFIX . 'module_block_rel_pages_tmp` (`page_id`, `block_id`, `placeholder`)
                                        VALUES (?, ?, ?)
                                    ', array($page->getId(), $blockId, 'global'));

                                    // only for contrexx 2.x
                                    if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '3.0.0')
                                        && !$objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.0.0')) {
                                        // do page association for direct placeholder
                                        \Cx\Lib\UpdateUtil::sql('
                                            INSERT IGNORE INTO `' . DBPREFIX . 'module_block_rel_pages_tmp` (`page_id`, `block_id`, `placeholder`)
                                            VALUES (?, ?, ?)
                                        ', array($page->getId(), $blockId, 'direct'));
                                    }
                                    \DBG::msg('BLOCK MIGRATION: inserted to temporary table, blockid: ' . $blockId . ', pageid: ' . $page->getId());
                                }
                            }

                            // delte old entry
                            \Cx\Lib\UpdateUtil::sql('DELETE FROM `' . DBPREFIX . 'module_block_rel_pages` WHERE `block_id` = ? AND `page_id` = ?', array($blockId, $oldPageId));

                            // check for memory limit and timeout limit
                            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                                $_SESSION['contrexx_update']['blocks_part_2_migrated_timeout'] = true;
                                return 'timeout';
                            }

                            $objResult->MoveNext();
                        }
                    }

                    // move the temporary table to the new table
                    \Cx\Lib\UpdateUtil::drop_table(DBPREFIX.'module_block_rel_pages');
                    \Cx\Lib\UpdateUtil::table_rename(DBPREFIX.'module_block_rel_pages_tmp', DBPREFIX.'module_block_rel_pages');
                    \DBG::msg('BLOCK MIGRATION: REMOVED TEMPORARY TABLE');
                } else {
                    // 3.0.3 : add new column `placeholder`
                    \Cx\Lib\UpdateUtil::table(
                        DBPREFIX.'module_block_rel_pages',
                        array(
                            'block_id'       => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0'),
                            'page_id'        => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0', 'after' => 'block_id'),
                            'placeholder'    => array('type' => 'ENUM(\'global\',\'direct\',\'category\')', 'notnull' => true, 'default' => 'global', 'after' => 'page_id')
                        )
                    );
                }
                // IMPORTANT: add primary key after migration, not used anymore (temporary table)
//                \Cx\Lib\UpdateUtil::table(
//                    DBPREFIX.'module_block_rel_pages',
//                    array(
//                        'block_id'       => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0', 'primary' => true),
//                        'page_id'        => array('type' => 'INT(7)', 'notnull' => true, 'default' => '0', 'after' => 'block_id', 'primary' => true),
//                        'placeholder'    => array('type' => 'ENUM(\'global\',\'direct\',\'category\')', 'notnull' => true, 'default' => 'global', 'after' => 'page_id', 'primary' => true)
//                    )
//                );
                $_SESSION['contrexx_update']['blocks_part_2_migrated'] = true;
            }

            if (empty($_SESSION['contrexx_update']['blocks_part_3_migrated'])) {
                $activeLangIds = implode(',', \FWLanguage::getIdArray());
                $objResult = \Cx\Lib\UpdateUtil::sql('
                    SELECT `block_id`, `lang_id`
                    FROM `' . DBPREFIX . 'module_block_blocks`  AS `b`
                    INNER JOIN `' . DBPREFIX . 'module_block_rel_lang` AS `rl`
                    ON `b`.`id` = `rl`.`block_id`
                    WHERE `b`.`global` = 2
                    AND `rl`.`all_pages` = 1
                    AND `rl`.`lang_id` IN (' . $activeLangIds . ')
                ');

                if ($objResult->RecordCount()) {
                    $langIds = array();
                    $pageIds = array();

                    while (!$objResult->EOF) {
                        $langIds[$objResult->fields['block_id']] = $objResult->fields['lang_id'];
                        $objResult->MoveNext();
                    }

                    $uniqueLangIds = array_unique($langIds);
                    foreach ($uniqueLangIds as $langId) {
                        $pageRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
                        $pages = $pageRepo->findBy(array(
                            'lang' => $langId,
                        ), null, null, null, true);
                        foreach ($pages as $page) {
                            $pageIds[$langId][] = $page->getId();
                        }
                    }

                    foreach ($langIds as $blockId => $langId) {
                        foreach ($pageIds[$langId] as $pageId) {
                            \Cx\Lib\UpdateUtil::sql('
                                INSERT IGNORE INTO `' . DBPREFIX . 'module_block_rel_pages` (`block_id`, `page_id`)
                                VALUES (' . $blockId . ', ' . $pageId . ')
                            ');
                        }
                    }
                }

                $_SESSION['contrexx_update']['blocks_part_3_migrated'] = true;
            }
        } catch (\Cx\Lib\UpdateException $e) {
            \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
            return false;
        }

        return true;
    }

    public function dropOldTables()
    {
        // Drop old tables
        try {
            \Cx\Lib\UpdateUtil::drop_table(DBPREFIX . 'content');
            \Cx\Lib\UpdateUtil::drop_table(DBPREFIX . 'content_history');
            \Cx\Lib\UpdateUtil::drop_table(DBPREFIX . 'content_logfile');
            \Cx\Lib\UpdateUtil::drop_table(DBPREFIX . 'content_navigation');
            \Cx\Lib\UpdateUtil::drop_table(DBPREFIX . 'content_navigation_history');
            \Cx\Lib\UpdateUtil::drop_table(DBPREFIX . 'module_alias_source');
            \Cx\Lib\UpdateUtil::drop_table(DBPREFIX . 'module_alias_target');
            \Cx\Lib\UpdateUtil::drop_table(DBPREFIX . 'module_block_rel_lang');
        } catch (\Cx\Lib\UpdateException $e) {
            \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
            return false;
        }

        return true;
    }

    public function pageGrouping()
    {
        // Fetch all pages
        if ((!isset($_POST['doGroup']) || (isset($_POST['doGroup']) && !$_POST['doGroup'])) && empty($_SESSION['contrexx_update']['do_group'])) {
            self::$em->clear();
            return $this->getTreeCode();
        }

        $_SESSION['contrexx_update']['do_group'] = true;
        if (empty($_SESSION['contrexx_update']['similar_pages'])) $_SESSION['contrexx_update']['similar_pages'] = $_POST['similarPages'];
        if (empty($_SESSION['contrexx_update']['remove_pages']))  $_SESSION['contrexx_update']['remove_pages'] = $_POST['removePages'];

        $arrSimilarPages = $_SESSION['contrexx_update']['similar_pages'];

        $pageRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');

        $nodeToRemove = array();
        if (empty($_SESSION['contrexx_update']['node_to_remove'])) $_SESSION['contrexx_update']['node_to_remove'] = array();

        foreach ($arrSimilarPages as $nodeId => $arrPageIds) {
            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                $_SESSION['contrexx_update']['node_to_remove'] = array_merge(\ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['node_to_remove']), $nodeToRemove);
                return 'timeout';
            }

            foreach ($arrPageIds as $pageId) {
                $page = $pageRepo->find($pageId);

                if ($page && ($page->getNode()->getId() != $nodeId)) {
                    $nodeToRemove[] = $page->getNode()->getId();
                    $node = $nodeRepo->find($nodeId);

                    $aliases = $page->getAliases();
                    foreach ($aliases as $alias) {
                        $alias->setTarget('[[NODE_' . $node->getId() . '_' . $page->getLang() . ']]');
                        self::$em->persist($alias);
                    }

                    $page->setNode($node);
                    $page->setNodeIdShadowed($node->getId());

                    self::$em->persist($node);
                }
            }

            self::$em->flush();
            unset($_SESSION['contrexx_update']['similar_pages'][$nodeId]);
        }

        $arrRemovePages = $_SESSION['contrexx_update']['remove_pages'];
        $pageToRemove   = array();

        if (empty($_SESSION['contrexx_update']['page_to_remove'])) $_SESSION['contrexx_update']['page_to_remove'] = array();

        foreach ($arrRemovePages as $pageId) {
            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                $_SESSION['contrexx_update']['page_to_remove'] = array_merge(\ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['page_to_remove']), $pageToRemove);
                $_SESSION['contrexx_update']['node_to_remove'] = array_merge(\ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['node_to_remove']), $nodeToRemove);
                return 'timeout';
            }

            $page = $pageRepo->find($pageId);
            if ($page) {
                $pageToRemove[] = $pageId;
                $nodeToRemove[] = $page->getNode()->getId();
            }

            unset($_SESSION['contrexx_update']['remove_pages'][$pageId]);
        }
        $_SESSION['contrexx_update']['page_to_remove'] = array_merge(\ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['page_to_remove']), $pageToRemove);
        $_SESSION['contrexx_update']['node_to_remove'] = array_merge(\ContrexxUpdate::_getSessionArray($_SESSION['contrexx_update']['node_to_remove']), $nodeToRemove);
        $pageToRemove = $_SESSION['contrexx_update']['page_to_remove'];
        $nodeToRemove = $_SESSION['contrexx_update']['node_to_remove'];

        // Prevent the system from trying to remove the same node more than once
        $pageToRemove = array_unique($pageToRemove);
        foreach ($pageToRemove as $index => $pageId) {
            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            $page = $pageRepo->find($pageId);
            self::$em->remove($page);

            unset($_SESSION['contrexx_update']['page_to_remove'][$index]);
        }

        self::$em->flush();
        self::$em->clear();

        $nodeToRemove = array_unique($nodeToRemove);

        foreach ($nodeToRemove as $index => $nodeId) {
            if (!checkMemoryLimit() || !checkTimeoutLimit()) {
                return 'timeout';
            }

            $node = $nodeRepo->find($nodeId);
            $nodeRepo->removeFromTree($node);

            // Reset node cache - this is required for the tree to reload its new structure after a node had been removed
            self::$em->clear();

            unset($_SESSION['contrexx_update']['node_to_remove'][$index]);
        }

        return true;
    }

    private function findSimilarPages()
    {
        $qb = self::$em->createQueryBuilder();
        $qb ->select('p')
            ->from('Cx\Core\ContentManager\Model\Entity\Page', 'p')
            ->innerJoin('p.node', 'n')
            ->where($qb->expr()->in('p.lang', $this->migrateLangIds))
            ->andWhere($qb->expr()->neq('p.module', ':empty'))
            ->setParameter('empty', '')
            ->orderBy('p.lang')
            ->addOrderBy('n.lft');
        $pages    = $qb->getQuery()->getResult();
        $simPages = $this->createBaseGroups($pages);

        return $simPages;
    }

    private function createBaseGroups($pages)
    {
        if (empty($pages) || !is_array($pages)) {
            return array();
        }

        foreach ($pages as $page) {
            $nodeId = $page->getNode()->getId();

            // Group module pages
            if (!isset($groups[$page->getModule()][$page->getCmd()])) {
                $groups[$page->getModule()][$page->getCmd()]['nodeIds'][] = $nodeId;
                $groups[$page->getModule()][$page->getCmd()][$page->getLang()] = 0;
            } else {
                if (isset($groups[$page->getModule()][$page->getCmd()][$page->getLang()])) {
                    $index        = $groups[$page->getModule()][$page->getCmd()][$page->getLang()] + 1;
                    $countNodeIds = count($groups[$page->getModule()][$page->getCmd()]['nodeIds']);
                    if ($countNodeIds <= $index) {
                        $groups[$page->getModule()][$page->getCmd()]['nodeIds'][] = $nodeId;
                    }
                } else {
                    $index = 0;
                }
                $groups[$page->getModule()][$page->getCmd()][$page->getLang()] = $index;

                $nodeId = $groups[$page->getModule()][$page->getCmd()]['nodeIds'][$index];
            }

            $similarPages[$nodeId][$page->getLang()] = $page->getId();
        }

        foreach ($similarPages as $nodeId => $arrPageIds) {
            if (count($arrPageIds) < 2) {
                unset($similarPages[$nodeId]);
            }
        }

        return $similarPages;
    }

    private function getTreeCode()
    {
        if (count($this->arrMigrateLangIds) === 1) {
            return true;
        }

        $jsSimilarPages = array();
        $this->similarPages = $this->findSimilarPages();
        foreach ($this->similarPages as $nodeId => $arrPageIds) {
            $jsSimilarPages[$nodeId] = array_values($arrPageIds);
            foreach ($this->arrMigrateLangIds as $migrateLangId) {
                if (!isset($arrPageIds[$migrateLangId])) {
                    $this->similarPages[$nodeId][$migrateLangId] = 0;
                }
            }
            ksort($this->similarPages[$nodeId]);
        }

        $objCx = \ContrexxJavascript::getInstance();
        $objCx->setVariable('similarPages', json_encode($jsSimilarPages), 'update/contentMigration');

        $objTemplate = new \HTML_Template_Sigma(UPDATE_TPL);
        $objTemplate->setErrorHandling(PEAR_ERROR_DIE);
        $objTemplate->loadTemplateFile('page_grouping.html');

        $groupedBorderWidth = ((count($this->arrMigrateLangIds) * 325) - 48);
        $objTemplate->setGlobalVariable(array(
            'USERNAME'               => $_SESSION['contrexx_update']['username'],
            'PASSWORD'               => $_SESSION['contrexx_update']['password'],
            'CMS_VERSION'            => $_SESSION['contrexx_update']['version'],
            'MIGRATE_LANG_IDS'       => $this->migrateLangIds,
            'LANGUAGE_WRAPPER_WIDTH' => 'width: ' . (count($this->arrMigrateLangIds) * 330) . 'px;',
            'GROUPED_SCROLL_WIDTH'   => 'width: ' . (count($this->arrMigrateLangIds) * 325) . 'px;',
            'GROUPED_BORDER_WIDTH'   => 'width: ' . $groupedBorderWidth . 'px;',
        ));

        $cl = \Env::get('ClassLoader');
        $cl->loadFile(ASCMS_CORE_PATH . '/Tree.class.php');
        $cl->loadFile(UPDATE_CORE . '/UpdateTree.class.php');

        $pageRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
        $nodeRepo = self::$em->getRepository('Cx\Core\ContentManager\Model\Entity\Node');

        foreach ($this->arrMigrateLangIds as $lang) {
            $objContentTree  = new \UpdateContentTree($lang);

            foreach ($objContentTree->getTree() as $arrPage) {
                $pageId  = $arrPage['catid'];
                $nodeId  = $arrPage['node_id'];
                $langId  = $arrPage['lang'];
                $level   = $arrPage['level'];
                $title   = $arrPage['catname'];
                $sort    = $nodeRepo->find($nodeId)->getLft();
                $grouped = $this->isGrouppedPage($this->similarPages, $pageId) ? 'grouped' : '';

                $objTemplate->setVariable(array(
                    'TITLE'   => $title,
                    'ID'      => $pageId,
                    'NODE'    => $nodeId,
                    'LANG'    => strtoupper(\FWLanguage::getLanguageCodeById($langId)),
                    'LEVEL'   => $level + 1,
                    'SORT'    => $sort,
                    'GROUPED' => $grouped,
                    'MARGIN'  => 'margin-left: ' . ($level * 15) . 'px;',
                ));
                $objTemplate->parse('page');
            }

            $langFull  = \FWLanguage::getLanguageParameter($lang, 'name');
            $langShort = strtoupper(\FWLanguage::getLanguageParameter($lang, 'lang'));
            $objTemplate->setVariable(array(
                'LANG_FULL'  => $langFull,
                'LANG_SHORT' => $langShort,
            ));
            $objTemplate->parse('language');
        }

        $groupedBorderWidth -= 2;
        foreach ($this->similarPages as $nodeId => $arrPageIds) {
            $node      = $nodeRepo->find($nodeId);
            $margin    = ($node->getLvl() - 1) * 15;
            $nodeWidth = $groupedBorderWidth - $margin;
            $width     = ($groupedBorderWidth - 10) / count($this->arrMigrateLangIds);

            $index = 0;
            $last  = count($arrPageIds) - 1;
            foreach ($arrPageIds as $pageLangId => $pageId) {
                if ($index === 0) {
                    $pageWidth = $width - 24;
                } elseif ($index === $last) {
                    $pageWidth = $width - $margin;
                } else {
                    $pageWidth = $width;
                }
                $index++;

                $page = $pageRepo->find($pageId);
                if ($page) {
                    $langCode = strtoupper(\FWLanguage::getLanguageCodeById($page->getLang()));
                    $objTemplate->setVariable(array(
                        'CLASS'     => '',
                        'DATA_ID'   => 'data-id="' . $pageId . '"',
                        'DATA_LANG' => 'data-lang="' . $langCode . '"',
                        'TITLE'     => $page->getTitle(),
                        'LANG'      => $langCode,
                        'WIDTH'     => 'width: ' . $pageWidth . 'px;',
                    ));
                } else {
                    $langCode = strtoupper(\FWLanguage::getLanguageCodeById($pageLangId));
                    $objTemplate->setVariable(array(
                        'CLASS'     => 'no-page',
                        'DATA_ID'   => '',
                        'DATA_LANG' => '',
                        'TITLE'     => 'Keine Seite',
                        'LANG'      => $langCode,
                        'WIDTH'     => 'width: ' . $pageWidth . 'px;',
                    ));
                }
                $objTemplate->parse('groupedPage');
            }

            $objTemplate->setVariable(array(
                'ID'    => $nodeId,
                'LEVEL' => $node->getLvl(),
                'SORT'  => $node->getLft(),
                'STYLE' => 'width: ' . $nodeWidth . 'px; margin-left: ' . $margin . 'px;',
            ));
            $objTemplate->parse('groupedNode');
        }

        return $objTemplate->get();
    }

    private function isGrouppedPage($arrSimilarPages, $pageId)
    {
        foreach ($arrSimilarPages as $pages) {
            if (count($pages) > 1 && in_array($pageId, $pages)) {
                return true;
            }
        }

        return false;
    }

    /*
      there are 'lost' ghost pages in the old content due to bugs, so we need to identify
      those pages.
      this simulates a sitemap as the old contentmanager saw it to achieve this.
      the ids of all 'non-lost' pages are then returned.
     */
    function getVisiblePageIDs() {
        try {
            $result = \Cx\Lib\UpdateUtil::sql('SELECT lang FROM ' . DBPREFIX . 'content_navigation WHERE `lang` IN (' . $this->migrateLangIds . ') GROUP BY lang');
        } catch (\Cx\Lib\UpdateException $e) {
            \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
            return false;
        }

        $pageIds = array();
        while(!$result->EOF) {
            $visiblePageIDsForLang = $this->getVisiblePageIDsForLang($result->fields['lang']);
            if ($visiblePageIDsForLang === false) {
                return false;
            }
            $pageIds = array_merge($pageIds, $visiblePageIDsForLang);
            $result->MoveNext();
        }


        return $pageIds;
    }

    protected $treeArray = array();
    protected $navTable = array();

    function getVisiblePageIDsForLang($lang)
    {
        try {
            $objResult = \Cx\Lib\UpdateUtil::sql('
                SELECT   n.lang,
                         n.catid AS catid,
                         n.displayorder AS displayorder,
                         n.parcat AS parcat
                    FROM ' . DBPREFIX . 'content_navigation AS n
                   WHERE n.lang = ' . $lang . '
                ORDER BY n.parcat ASC, n.displayorder ASC
            ');
        } catch (\Cx\Lib\UpdateException $e) {
            \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
            return false;
        }

        $this->navtable = array();
        $this->treeArray = array();

        while (!$objResult->EOF) {
            $lang = $objResult->fields['lang'];
            $parcat = $objResult->fields['parcat'];
            $catid = $objResult->fields['catid'];

            $this->navtable[$parcat][$catid]='title';
            $objResult->MoveNext();
        }

        return $this->doAdminTreeArray();
    }

    //based on old ContentSitemap. magically removes the ghosts.
    function doAdminTreeArray($parcat=0, $level=0, $maxlevel=0)
    {
        $list = $this->navtable[$parcat];
        if (is_array($list)) {
            foreach (array_keys($list) as $pageId) {
                $this->treeArray[$pageId] = $level;
                if (isset($this->navtable[$pageId]) && ($maxlevel > $level+1 || $maxlevel == '0')) {
                    $this->doAdminTreeArray($pageId, $level+1, $maxlevel);
                }
            }
        }
        return array_keys($this->treeArray);
    }

    public function migrateStatistics()
    {
        global $objUpdate, $_CONFIG;

        // only execute this part for versions < 2.1.5
        if ($objUpdate->_isNewerVersion($_CONFIG['coreCmsVersion'], '2.1.5')) {
            if (!\Cx\Lib\UpdateUtil::table_exist(DBPREFIX.'content')) {
                return true;
            }

            try {
                //2.1.5: new field contrexx_stats_requests.pageTitle needs to be added and filled
                if (!\Cx\Lib\UpdateUtil::column_exist(DBPREFIX.'stats_requests', 'pageTitle')) {
                    \Cx\Lib\UpdateUtil::sql('ALTER TABLE `'.DBPREFIX.'stats_requests` ADD `pageTitle` varchar(250) NOT NULL AFTER `sid`');
                }
                //fill pageTitle with current titles
                \Cx\Lib\UpdateUtil::sql('UPDATE '.DBPREFIX.'stats_requests SET pageTitle = ( SELECT title FROM '.DBPREFIX.'content WHERE id=pageId ) WHERE EXISTS ( SELECT title FROM '.DBPREFIX.'content WHERE id=pageId ) AND pageTitle = \'\'');
            }
            catch (\Cx\Lib\UpdateException $e) {
                return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
            }
        }

        return true;
    }

    public function getActiveContentLanguageIds()
    {
        try {
            $activeLanguageIds = implode(',', \FWLanguage::getIdArray());
            $objResult = \Cx\Lib\UpdateUtil::sql('
                SELECT DISTINCT `lang` FROM `' . DBPREFIX . 'content_navigation`
                 WHERE `lang` IN (' . $activeLanguageIds . ')
                 UNION DISTINCT
                SELECT DISTINCT `lang` FROM `' . DBPREFIX . 'content_navigation_history`
                 WHERE `lang` IN (' . $activeLanguageIds . ')
                       ORDER BY `lang` ASC
            ');

            if ($objResult->RecordCount()) {
                $arrActiveContentLanguageIds = array();
                while (!$objResult->EOF) {
                    $arrActiveContentLanguageIds[] = $objResult->fields['lang'];
                    $objResult->MoveNext();
                }

                return $arrActiveContentLanguageIds;
            } else {
                return false;
            }
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }

    public function getInactiveContentLanguageCheckboxes()
    {
        try {
            $activeLanguageIds = implode(',', \FWLanguage::getIdArray());
            $objResult = \Cx\Lib\UpdateUtil::sql('
                SELECT DISTINCT `lang` FROM `' . DBPREFIX . 'content_navigation`
                 WHERE `lang` NOT IN (' . $activeLanguageIds . ')
                 UNION DISTINCT
                SELECT DISTINCT `lang` FROM `' . DBPREFIX . 'content_navigation_history`
                 WHERE `lang` NOT IN (' . $activeLanguageIds . ')
                       ORDER BY `lang` ASC
            ');

            if ($objResult->RecordCount()) {
                $arrLanguages = \FWLanguage::getLanguageArray();
                $inactiveContentLanguages = '';

                while (!$objResult->EOF) {
                    $inactiveContentLanguages .= '
                        <input style="clear:left;float:left;margin-top:3px;" type="checkbox" name="migrateLangIds" id="migrate-lang-' . $objResult->fields['lang'] . '" value="' . $objResult->fields['lang'] . '" />
                        <label for="migrate-lang-' . $objResult->fields['lang'] . '">' . $arrLanguages[$objResult->fields['lang']]['name'] . '</label><br />
                    ';
                    $objResult->MoveNext();
                }

                return $inactiveContentLanguages;
            } else {
                return '';
            }
        } catch (\Cx\Lib\UpdateException $e) {
            return \Cx\Lib\UpdateUtil::DefaultActionHandler($e);
        }
    }
}
