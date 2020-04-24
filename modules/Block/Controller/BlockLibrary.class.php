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

/**
 * Block
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Block\Controller;

/**
 * Block
 *
 * Block library class
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      private
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */
class BlockLibrary
{
    /**
    * Block name prefix
    *
    * @access public
    * @var string
    */
    var $blockNamePrefix = 'BLOCK_';

    /**
    * Block ids
    *
    * @access private
    * @var array
    */
    var $_arrBlocks;

    /**
     * Array of categories
     *
     * @var array
     */
    var $_categories = array();

    /**
     * holds the category dropdown select options
     *
     * @var array of strings: HTML <options>
     */
    var $_categoryOptions = array();

    /**
     * array containing the category names
     *
     * @var array catId => name
     */
    var $_categoryNames = array();

    protected $availableTargeting = array(
        'country',
    );

    /**
     * Constructor
     */
    function __construct()
    {
        if (\Cx\Core\Core\Controller\Cx::instanciate()->getMode() != \Cx\Core\Core\Controller\Cx::MODE_COMMAND) {
            return;
        }
    }


    /**
    * Get blocks
    *
    * Get all blocks
    *
    * @access private
    * @global ADONewConnection
    * @see array blockLibrary::_arrBlocks
    * @return array Array with block ids
    */
    public function getBlocks($catId = 0)
    {
        global $objDatabase;

        $catId = intval($catId);
        $where = '';

        if ($catId > 0) {
            $where = 'WHERE `cat` = '.$catId;
        }

        if (!is_array($this->_arrBlocks)) {
            $query = 'SELECT    `id`,
                                `cat`,
                                `name`,
                                `start`,
                                `end`,
                                `order`,
                                `random`,
                                `random_2`,
                                `random_3`,
                                `random_4`,
                                `global`,
                                `active`,
                                `direct`
                        FROM `%1$s`
                        # WHERE
                        %2$s
                        ORDER BY `order`';

            $objResult = $objDatabase->Execute(sprintf($query, DBPREFIX.'module_block_blocks',
                                                               $where));
            if ($objResult !== false) {
                $this->_arrBlocks = array();

                while (!$objResult->EOF) {
                    $langArr          = array();
                    $objBlockLang = $objDatabase->Execute("SELECT lang_id FROM ".DBPREFIX."module_block_rel_lang_content WHERE block_id=".$objResult->fields['id']." AND `active` = 1 ORDER BY lang_id ASC");

                    if ($objBlockLang) {
                        while (!$objBlockLang->EOF) {
                            $langArr[] = $objBlockLang->fields['lang_id'];
                            $objBlockLang->MoveNext();

                        }
                    }
                    $this->_arrBlocks[$objResult->fields['id']] = array(
                        'cat'       => $objResult->fields['cat'],
                        'start'     => $objResult->fields['start'],
                        'end'       => $objResult->fields['end'],
                        'order'     => $objResult->fields['order'],
                        'random'    => $objResult->fields['random'],
                        'random2'   => $objResult->fields['random_2'],
                        'random3'   => $objResult->fields['random_3'],
                        'random4'   => $objResult->fields['random_4'],
                        'global'    => $objResult->fields['global'],
                        'active'    => $objResult->fields['active'],
                        'direct'    => $objResult->fields['direct'],
                        'name'      => $objResult->fields['name'],
                        'lang'      => array_unique($langArr),
                    );
                    $objResult->MoveNext();
                }
            }
        }

        return $this->_arrBlocks;
    }

    /**
     * Add a new block to database
     *
     * @param int $cat
     * @param array $arrContent
     * @param string $name
     * @param int $start
     * @param int $end
     * @param int $blockRandom
     * @param int $blockRandom2
     * @param int $blockRandom3
     * @param int $blockRandom4
     * @param int $blockWysiwygEditor
     * @param array $arrLangActive
     * @return bool|int the block's id
     */
    public function _addBlock($cat, $arrContent, $name, $start, $end, $blockRandom, $blockRandom2, $blockRandom3, $blockRandom4, $blockWysiwygEditor, $arrLangActive)
    {
        global $objDatabase;

        $query = "INSERT INTO `".DBPREFIX."module_block_blocks` (
                    `order`,
                    `name`,
                    `cat`,
                    `start`,
                    `end`,
                    `random`,
                    `random_2`,
                    `random_3`,
                    `random_4`,
                    `wysiwyg_editor`,
                    `active`
                  ) SELECT MAX(`order`) + 1,
                      '".contrexx_raw2db($name)."',
                      ".intval($cat).",
                      ".intval($start).",
                      ".intval($end).",
                      ".intval($blockRandom).",
                      ".intval($blockRandom2).",
                      ".intval($blockRandom3).",
                      ".intval($blockRandom4).",
                      ".intval($blockWysiwygEditor).",
                       1
                  FROM `".DBPREFIX."module_block_blocks`";
        if ($objDatabase->Execute($query) === false) {
            return false;
        }
        $id = $objDatabase->Insert_ID();

        $this->storeBlockContent($id, $arrContent, $arrLangActive);

        return $id;
    }


    /**
     * Update an existing block
     *
     * @param int $id
     * @param int $cat
     * @param array $arrContent
     * @param string $name
     * @param int $start
     * @param int $end
     * @param int $blockRandom
     * @param int $blockRandom2
     * @param int $blockRandom3
     * @param int $blockRandom4
     * @param int $blockWysiwygEditor
     * @param array $arrLangActive
     * @return bool|int the id of the block
     */
    public function _updateBlock($id, $cat, $arrContent, $name, $start, $end, $blockRandom, $blockRandom2, $blockRandom3, $blockRandom4, $blockWysiwygEditor, $arrLangActive)
    {
        global $objDatabase;

        $query = "UPDATE `".DBPREFIX."module_block_blocks`
                    SET `name`              = '".contrexx_raw2db($name)."',
                        `cat`               = ".intval($cat).",
                        `start`             = ".intval($start).",
                        `end`               = ".intval($end).",
                        `random`            = ".intval($blockRandom).",
                        `random_2`          = ".intval($blockRandom2).",
                        `random_3`          = ".intval($blockRandom3).",
                        `random_4`          = ".intval($blockRandom4).",
                        `wysiwyg_editor`    = ".intval($blockWysiwygEditor)."
                  WHERE `id` = ".intval($id);
        if ($objDatabase->Execute($query) === false) {
            return false;
        }

        $this->storeBlockContent($id, $arrContent, $arrLangActive);

        return $id;
    }

    /**
     * Store the placeholder settings for a block
     *
     * @param int $blockId
     * @param int $global
     * @param int $direct
     * @param int $category
     * @param array $globalAssociatedPages
     * @param array $directAssociatedPages
     * @param array $categoryAssociatedPages
     * @return bool it was successfully saved
     */
    protected function storePlaceholderSettings($blockId, $global, $direct, $category, $globalAssociatedPages, $directAssociatedPages, $categoryAssociatedPages) {
        global $objDatabase;
        $objDatabase->Execute("UPDATE `" . DBPREFIX . "module_block_blocks`
                                SET `global` = ?,
                                    `direct` = ?,
                                    `category` = ?
                                WHERE `id` = ?", array($global, $direct, $category, $blockId));

        $objDatabase->Execute("DELETE FROM `" . DBPREFIX . "module_block_rel_pages` WHERE `block_id` = '" . intval($blockId) . "'");
        if ($global == 2) {
            $this->storePageAssociations($blockId, $globalAssociatedPages, 'global');
        }
        if ($direct == 1) {
            $this->storePageAssociations($blockId, $directAssociatedPages, 'direct');
        }
        if ($category == 1) {
            $this->storePageAssociations($blockId, $categoryAssociatedPages, 'category');
        }
        return true;
    }

    /**
     * Store the page associations
     *
     * @param int $blockId the block id
     * @param array $blockAssociatedPageIds the page ids
     * @param string $placeholder the placeholder
     */
    private function storePageAssociations($blockId, $blockAssociatedPageIds, $placeholder)
    {
        global $objDatabase;
        foreach ($blockAssociatedPageIds as $pageId) {
            $objDatabase->Execute("INSERT INTO `" . DBPREFIX . "module_block_rel_pages` (`block_id`, `page_id`, `placeholder`)
                                    VALUES (?, ?, ?)", array($blockId, $pageId, $placeholder));
        }
    }

    private function storeBlockContent($blockId, $arrContent, $arrLangActive)
    {
        global $objDatabase;

        $arrPresentLang = array();
        $objResult = $objDatabase->Execute('SELECT lang_id FROM '.DBPREFIX.'module_block_rel_lang_content WHERE block_id='.$blockId);
        if ($objResult) {
            while (!$objResult->EOF) {
                $arrPresentLang[] = $objResult->fields['lang_id'];
                $objResult->MoveNext();
            }
        }

        foreach ($arrContent as $langId => $content) {
            if (in_array($langId, $arrPresentLang)) {
                $query = 'UPDATE `%1$s` SET %2$s WHERE `block_id` = %3$s AND `lang_id`='.intval($langId);
            } else {
                $query = 'INSERT INTO `%1$s` SET %2$s, `block_id` = %3$s';
            }

            $content = preg_replace('/\[\[([A-Z0-9_-]+)\]\]/', '{\\1}', $content);
            $objDatabase->Execute(sprintf($query, DBPREFIX.'module_block_rel_lang_content',
                                                  "lang_id='".intval($langId)."',
                                                   content='".contrexx_raw2db($content)."',
                                                   active='".intval((isset($arrLangActive[$langId]) ? $arrLangActive[$langId] : 0))."'",
                                                  $blockId));
        }
            \Cx\Core\Core\Controller\Cx::instanciate()->getComponent('Cache')->clearSsiCachePage(
                'Block',
                'getBlockContent',
                array(
                    'block' => $blockId,
                )
            );

        $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_block_rel_lang_content WHERE block_id=".$blockId." AND lang_id NOT IN (".join(',', array_map('intval', array_keys($arrLangActive))).")");
    }

    /**
     * Get GeoIp component controller
     *
     * @return \Cx\Core_Modules\GeoIp\Controller\ComponentController
     */
    public function getGeoIpComponent()
    {
        $componentRepo = \Cx\Core\Core\Controller\Cx::instanciate()
                            ->getDb()
                            ->getEntityManager()
                            ->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $geoIpComponent = $componentRepo->findOneBy(array('name' => 'GeoIp'));
        if (!$geoIpComponent) {
            return null;
        }
        $geoIpComponentController = $geoIpComponent->getSystemComponentController();
        if (!$geoIpComponentController) {
            return null;
        }

        return $geoIpComponentController;
    }

    /**
     * Verify targeting options for the given block Id
     *
     * @param integer $blockId Block id
     *
     * @return boolean True when all targeting options vaild, false otherwise
     */
    public function checkTargetingOptions($blockId)
    {
        $targeting = $this->loadTargetingSettings($blockId);

        if (empty($targeting)) {
            return true;
        }

        foreach ($targeting as $targetingType => $targetingSetting) {
            switch ($targetingType) {
                case 'country':
                    if (!$this->checkTargetingCountry($targetingSetting['filter'], $targetingSetting['value'])) {
                        return false;
                    }
                    break;
                default :
                    break;
            }
        }
        return true;
    }

    /**
     * Check Country targeting option
     *
     * @param string $filter      include => client country should exists in given country ids
     *                            exclude => client country should not exists in given country ids
     * @param array  $countryIds  Country ids to match
     *
     * @return boolean True when targeting country option matching to client country
     *                 False otherwise
     */
    public function checkTargetingCountry($filter, $countryIds)
    {
        // getClient country using GeoIp component
        $geoIpComponentController = $this->getGeoIpComponent();
        if (!$geoIpComponentController) {
            return false;
        }

        $clientRecord = $geoIpComponentController->getClientRecord();
        if (!$clientRecord) {
            return false;
        }
        $clientCountryAlpha2 = $clientRecord->country->isoCode;
        $clientCountryId     = \Cx\Core\Country\Controller\Country::getIdByAlpha2($clientCountryAlpha2);

        $isCountryExists = in_array($clientCountryId, $countryIds);
        if ($filter == 'include') {
            return $isCountryExists;
        } else {
            return !$isCountryExists;
        }
    }

    /**
     * Load Targeting settings
     *
     * @param integer $blockId Content block id
     *
     * @return array Settings array
     */
    public function loadTargetingSettings($blockId)
    {
        global $objDatabase;

        $query = 'SELECT
                    `filter`,
                    `type`,
                    `value`
                  FROM
                    `'. DBPREFIX .'module_block_targeting_option`
                  WHERE
                    `block_id` = "'. contrexx_raw2db($blockId) .'"
                 ';
        $targeting = $objDatabase->Execute($query);
        if (!$targeting) {
            return array();
        }

        $targetingArr = array();
        while (!$targeting->EOF) {
            $targetingArr[$targeting->fields['type']] = array(
                'filter' => $targeting->fields['filter'],
                'value'  => json_decode($targeting->fields['value'])
            );
            $targeting->MoveNext();
        }

        return $targetingArr;
    }

    /**
    * Get block
    *
    * Return a block
    *
    * @access private
    * @param integer $id
    * @global ADONewConnection
    * @return mixed content on success, false on failure
    */
    function _getBlock($id)
    {
        global $objDatabase;

        $objBlock = $objDatabase->SelectLimit("SELECT name, cat, start, end, random, random_2, random_3, random_4, global, direct, category, active, wysiwyg_editor FROM ".DBPREFIX."module_block_blocks WHERE id=".$id, 1);


        if ($objBlock !== false && $objBlock->RecordCount() == 1) {
            $arrContent = array();
            $arrActive = array();

            $objBlockContent = $objDatabase->Execute("SELECT lang_id, content, active FROM ".DBPREFIX."module_block_rel_lang_content WHERE block_id=".$id);
            if ($objBlockContent !== false) {
                while (!$objBlockContent->EOF) {
                    $arrContent[$objBlockContent->fields['lang_id']] = $objBlockContent->fields['content'];
                    $arrActive[$objBlockContent->fields['lang_id']]  = $objBlockContent->fields['active'];
                    $objBlockContent->MoveNext();
                }
            }

            return array(
                'cat'               => $objBlock->fields['cat'],
                'start'             => $objBlock->fields['start'],
                'end'               => $objBlock->fields['end'],
                'random'            => $objBlock->fields['random'],
                'random2'           => $objBlock->fields['random_2'],
                'random3'           => $objBlock->fields['random_3'],
                'random4'           => $objBlock->fields['random_4'],
                'global'            => $objBlock->fields['global'],
                'direct'            => $objBlock->fields['direct'],
                'category'          => $objBlock->fields['category'],
                'active'            => $objBlock->fields['active'],
                'name'              => $objBlock->fields['name'],
                'wysiwyg_editor'    => $objBlock->fields['wysiwyg_editor'],
                'content'           => $arrContent,
                'lang_active'       => $arrActive,
            );
        }

        return false;
    }

    /**
     * Get the associated pages for a placeholder
     *
     * @param int $blockId block id
     * @param string $placeholder
     * @return array
     */
    function _getAssociatedPageIds($blockId, $placeholder)
    {
        global $objDatabase;

        $arrPageIds = array();
        $objResult = $objDatabase->Execute("SELECT page_id FROM ".DBPREFIX."module_block_rel_pages WHERE block_id = '" . intval($blockId) . "' AND placeholder = '" . contrexx_raw2db($placeholder) . "'");
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                array_push($arrPageIds, $objResult->fields['page_id']);
                $objResult->MoveNext();
            }
        }
        return $arrPageIds;
    }

    function _getBlocksForPageId($pageId)
    {
        global $objDatabase;

        $arrBlocks = array();
        $objResult = $objDatabase->Execute('
            SELECT
                `b`.`id`,
                `b`.`cat`,
                `b`.`name`,
                `b`.`start`,
                `b`.`end`,
                `b`.`order`,
                `b`.`random`,
                `b`.`random_2`,
                `b`.`random_3`,
                `b`.`random_4`,
                `b`.`global`,
                `b`.`direct`,
                `b`.`category`,
                `b`.`active`
            FROM
                `'.DBPREFIX.'module_block_blocks` AS `b`,
                `'.DBPREFIX.'module_block_rel_pages` AS `p`
            WHERE
                `b`.`id` = `p`.`block_id`
                AND `p`.`page_id` = \'' . $pageId . '\'
                AND `p`.`placeholder` = \'global\'
            GROUP BY
                `b`.`id`
        ');
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $arrBlocks[$objResult->fields['id']] = array(
                    'cat'       => $objResult->fields['cat'],
                    'start'     => $objResult->fields['start'],
                    'end'       => $objResult->fields['end'],
                    'order'     => $objResult->fields['order'],
                    'random'    => $objResult->fields['random'],
                    'random2'   => $objResult->fields['random_2'],
                    'random3'   => $objResult->fields['random_3'],
                    'random4'   => $objResult->fields['random_4'],
                    'global'    => $objResult->fields['global'],
                    'direct'    => $objResult->fields['direct'],
                    'category'  => $objResult->fields['category'],
                    'active'    => $objResult->fields['active'],
                    'name'      => $objResult->fields['name'],
                );
                $objResult->MoveNext();
            }
        }
        return $arrBlocks;
    }

    function _setBlocksForPageId($pageId, $blockIds) {
        global $objDatabase;

        $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_block_rel_pages WHERE page_id = " . intval($pageId) . " AND placeholder = 'global'");

        $values = array();
        foreach ($blockIds as $blockId) {
            $block = $this->_getBlock($blockId);
            // block is global and will be shown on all pages, don't need to save the relation
            if ($block['global'] == 1) {
                continue;
            }
            // if the block was not global till now, make it global
            if ($block['global'] == 0) {
                $objDatabase->Execute("UPDATE `" . DBPREFIX . "module_block_blocks` SET `global` = 2 WHERE `id` = " . intval($blockId));
            }
            $values[] = '
                (
                    \'' . intval($blockId) . '\',
                    \'' . intval($pageId) . '\',
                    \'global\'
                )';
        }
        if (!empty($values)) {
            $query = 'INSERT INTO
                    `' . DBPREFIX . 'module_block_rel_pages`
                    (
                        `block_id`,
                        `page_id`,
                        `placeholder`
                    )
                VALUES' . implode(', ', $values);
            $objDatabase->Execute($query);
        }
        $objDatabase->Execute('
            DELETE FROM
                `' . DBPREFIX . 'module_block_rel_pages`
            WHERE
                `page_id` = \'' . $pageId . '\' AND
                `block_id` NOT IN
                    (
                        \'' . implode('\',\'', array_map('intval', $blockIds)).'\'
                    ) AND
                `placeholder` = \'global\'
        ');
    }

    /**
    * Set block
    *
    * Parse the block with the id $id
    *
    * @access private
    * @param integer $id Block ID
    * @param string &$code
    * @param int $pageId
    * @global ADONewConnection
    * @global integer
    */
    function _setBlock($id, &$code, $pageId = 0)
    {
        $now = time();

        $activeFilter = '
            AND (
                tblBlock.`start` <= ' . $now . '
                OR tblBlock.`start` = 0
            )
            AND (
                tblBlock.`end` >= ' . $now . '
                OR tblBlock.end = 0
            )
            AND
                tblBlock.active = 1
        ';
        // Note: This is a workaround as content panes are no real widgets yet.
        //
        // In case the frontend editing is not in use
        // then we can always load the content pane.
        // The JsonData adapter will then decide if the content pane is active or not.
        // The check for frontend editing is required, as otherwise the frontend editing
        // would inject an empty DIV element in  case the content pane is inactive.
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if (!$cx->getComponent('FrontendEditing')->frontendEditingIsActive(false, false)) {
            $activeFilter = '';
        }
        // End of workaround

        $this->replaceBlocks(
            $this->blockNamePrefix . $id,
            '
                SELECT
                    tblBlock.id
                FROM
                    ' . DBPREFIX . 'module_block_blocks AS tblBlock,
                    ' . DBPREFIX . 'module_block_rel_lang_content AS tblContent
                WHERE
                    tblBlock.id = ' . intval($id) . '
                    AND (
                        tblBlock.`direct` = 0
                        OR (
                            SELECT
                                count(1)
                            FROM
                                `' . DBPREFIX . 'module_block_rel_pages` AS tblRel
                            WHERE
                                tblRel.`page_id` = ' . intval($pageId) . '
                                AND tblRel.`block_id` = tblBlock.`id`
                                AND tblRel.`placeholder` = "direct") > 0
                        )
                    AND tblContent.block_id = tblBlock.id
                    AND (
                        tblContent.lang_id = ' . FRONTEND_LANG_ID . '
                        AND tblContent.active = 1
                    )' . $activeFilter,
            $pageId,
            $code
        );
    }

    /**
    * Set category block
    *
    * Parse the category block with the id $id
    *
    * @access private
    * @param integer $id Category ID
    * @param string &$code
    * @param int $pageId
    * @global ADONewConnection
    * @global integer
    */
    function _setCategoryBlock($id, &$code, $pageId = 0)
    {
        $category = $this->_getCategory($id);
        $separator = $category['seperator'];

        $now = time();

        $activeFilter = '
            AND tblBlock.`active` = 1
            AND (tblBlock.`start` <= ' . $now . ' OR tblBlock.`start` = 0)
            AND (tblBlock.`end` >= ' . $now . ' OR tblBlock.`end` = 0)
        ';
        // Note: This is a workaround as content panes are no real widgets yet.
        //
        // In case the frontend editing is not in use and there
        // is no content pane separator for the category defined,
        // then we can load all content panes into the template at once.
        // The JsonData adapter will then decide which content panes to display and
        // which not.
        // The check for frontend editing is required, as otherwise the frontend editing
        // would inject empty DIV elements for non-active content panes.
        // The check for the content pane separator is required, as otherwise
        // the system would output the separator for each non-active content pane.
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if (
            !$cx->getComponent('FrontendEditing')->frontendEditingIsActive(false, false) &&
            $separator == ''
        ) {
            $activeFilter = '';
        }
        // End of workaround

        $this->replaceBlocks(
            $this->blockNamePrefix . 'CAT_' . $id,
            '
                SELECT
                    tblBlock.id
                FROM
                    `' . DBPREFIX . 'module_block_blocks` AS tblBlock
                INNER JOIN
                    `' . DBPREFIX . 'module_block_rel_lang_content` AS tblContent
                ON
                    tblBlock.id = tblContent.block_id
                WHERE
                    tblBlock.`cat` = ' . $id . '
                    AND (
                        tblBlock.`category` = 0
                        OR (
                            SELECT
                                count(1)
                            FROM
                                `' . DBPREFIX . 'module_block_rel_pages` AS tblRel
                            WHERE
                                tblRel.`page_id` = ' . intval($pageId) . '
                                AND tblRel.`block_id` = tblBlock.`id`
                                AND tblRel.`placeholder` = "category") > 0
                        )
                    ' . $activeFilter . '
                    AND (tblContent.lang_id = ' . FRONTEND_LANG_ID . ' AND tblContent.active = 1)
                ORDER BY
                    tblBlock.`order`
            ',
            $pageId,
            $code,
            $separator
        );
    }

    /**
    * Set block Global
    *
    * Parse the block with the id $id
    *
    * @access private
    * @param integer $id
    * @param string &$code
    * @global ADONewConnection
    * @global integer
    */
    function _setBlockGlobal(&$code, $pageId = 0)
    {
        global $objDatabase;

        // fetch separator
        $separator = '';
        $objResult = $objDatabase->Execute(
            '
                SELECT
                    `value`
                FROM
                    `' . DBPREFIX . 'module_block_settings`
                WHERE
                    `name` = "blockGlobalSeperator"
                LIMIT
                    1
            '
        );
        if ($objResult !== false) {
            $separator = $objResult->fields['value'];
        }

        $now = time();

        $activeFilter = '
            AND tblBlock.active = 1
            AND (
                tblBlock.`start` <= ' . $now . '
                OR tblBlock.`start` = 0
            )
            AND (
                tblBlock.`end` >= ' . $now . '
                OR tblBlock.end = 0
            )
        ';
        // Note: This is a workaround as content panes are no real widgets yet.
        //
        // In case the frontend editing is not in use and there
        // is no content pane separator for the global content pane defined,
        // then we can load all content panes into the template at once.
        // The JsonData adapter will then decide which content panes to display and
        // which not.
        // The check for frontend editing is required, as otherwise the frontend editing
        // would inject empty DIV elements for non-active content panes.
        // The check for the content pane separator is required, as otherwise
        // the system would output the separator for each non-active content pane.
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        if (
            !$cx->getComponent('FrontendEditing')->frontendEditingIsActive(false, false) &&
            $separator == ''
        ) {
            $activeFilter = '';
        }
        // End of workaround
        
        $this->replaceBlocks(
            $this->blockNamePrefix . 'GLOBAL',
            '
                SELECT
                    `tblBlock`.`id` AS `id`,
                    `tblContent`.`content` AS `content`,
                    `tblBlock`.`order`
                FROM
                    ' . DBPREFIX . 'module_block_blocks AS tblBlock
                INNER JOIN
                    ' . DBPREFIX . 'module_block_rel_lang_content AS tblContent
                ON
                    tblContent.`block_id` = tblBlock.`id` 
                INNER JOIN
                    ' . DBPREFIX . 'module_block_rel_pages AS tblPage
                ON
                    tblPage.`block_id` = tblBlock.`id`
                WHERE
                    tblBlock.`global` = 2
                    AND tblPage.page_id = ' . intval($pageId) . '
                    AND tblContent.`lang_id` = ' . FRONTEND_LANG_ID . '
                    AND tblContent.`active` = 1
                    AND tblPage.placeholder = "global"
                    ' . $activeFilter . '
                UNION DISTINCT
                    SELECT
                        tblBlock.`id` AS `id`,
                        tblContent.`content` AS `content`,
                        tblBlock.`order`
                    FROM
                        ' . DBPREFIX . 'module_block_blocks AS tblBlock
                    INNER JOIN
                        ' . DBPREFIX . 'module_block_rel_lang_content AS tblContent
                    ON
                        tblContent.`block_id` = tblBlock.`id` 
                    WHERE
                        tblBlock.`global` = 1
                        AND tblContent.`lang_id` = ' . FRONTEND_LANG_ID . '
                        AND tblContent.`active` = 1
                        ' . $activeFilter . '
                ORDER BY
                    `order`
            ',
            $pageId,
            $code,
            $separator
        );
    }

    /**
    * Set block Random
    *
    * Parse the block with the id $id
    *
    * @access private
    * @param integer $id
    * @param string &$code
    * @global ADONewConnection
    * @global integer
    */
    function _setBlockRandom(&$code, $id, $pageId = 0)
    {
        global $objDatabase;

        // Note: known issue:
        // as content panes are not real widgets, the random block does not properly
        // take into account if a content page is active or not
        // Unfortunately, this issue can't be fixed with the current implementation
        // This issue will be fixed once the content panes have been migrated to widgets
        $now = time();
        $query = "  SELECT
                        tblBlock.id
                    FROM
                        ".DBPREFIX."module_block_blocks AS tblBlock,
                        ".DBPREFIX."module_block_rel_lang_content AS tblContent
                    WHERE
                        tblContent.block_id = tblBlock.id
                    AND
                        (tblContent.lang_id = ".FRONTEND_LANG_ID." AND tblContent.active = 1)
                    AND (tblBlock.`start` <= $now OR tblBlock.`start` = 0)
                    AND (tblBlock.`end` >= $now OR tblBlock.end = 0)
                    AND
                        tblBlock.active = 1 ";

        //Get Block Name and Status
        switch ($id) {
            case '1':
                $query .= "AND tblBlock.random=1";
                $blockNr        = "";
                break;
            case '2':
                $query .= "AND tblBlock.random_2=1";
                $blockNr        = "_2";
                break;
            case '3':
                $query .= "AND tblBlock.random_3=1";
                $blockNr        = "_3";
                break;
            case '4':
                $query .= "AND tblBlock.random_4=1";
                $blockNr        = "_4";
                break;
        }

        $this->replaceBlocks(
            $this->blockNamePrefix . 'RANDOMIZER' . $blockNr,
            $query,
            $pageId,
            $code,
            '',
            true
        );
    }

    /**
     * Replaces a placeholder with block content
     * @param string $placeholderName Name of placeholder to replace
     * @param string $query SQL query used to fetch blocks
     * @param int $pageId ID of the current page, 0 if no page available
     * @param string $code (by reference) Code to replace placeholder in
     * @param string $separator (optional) Separator used to separate the blocks
     * @param boolean $randomize (optional) Wheter to randomize the blocks or not, default false
     */
    protected function replaceBlocks($placeholderName, $query, $pageId, &$code, $separator = '', $randomize = false) {
        global $objDatabase;

        // find all block IDs to parse
        $objResult = $objDatabase->Execute($query);
        $blockIds = array();
        if ($objResult === false || $objResult->RecordCount() <= 0) {
            // drop empty placeholders
            $code = str_replace('{' . $placeholderName . '}', '', $code);
            return;
        }
        while(!$objResult->EOF) {
            if (!$this->checkTargetingOptions($objResult->fields['id'])) {
                $objResult->MoveNext();
                continue;
            }
            $blockIds[] = $objResult->fields['id'];
            $objResult->MoveNext();
        }

        // parse
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $systemComponentRepo = $em->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $frontendEditingComponent = $systemComponentRepo->findOneBy(array('name' => 'FrontendEditing'));
        $settings = $this->getSettings();

        if ($randomize) {
            $esiBlockInfos = array();
            foreach ($blockIds as $blockId) {
                $esiBlockInfos[] = array(
                    'Block',
                    'getBlockContent',
                    array(
                        'block' => $blockId,
                        'lang' => \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID),
                        'page' => $pageId,
                    )
                );
            }
            $blockContent = $cx->getComponent('Cache')->getRandomizedEsiContent(
                $esiBlockInfos
            );
            $frontendEditingComponent->prepareBlock(
                $blockId,
                $blockContent
            );
            $content = $blockContent;
        } else {
            $contentList = array();
            foreach ($blockIds as $blockId) {
                $blockContent = $cx->getComponent('Cache')->getEsiContent(
                    'Block',
                    'getBlockContent',
                    array(
                        'block' => $blockId,
                        'lang' => \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID),
                        'page' => $pageId,
                    )
                );
                $frontendEditingComponent->prepareBlock(
                    $blockId,
                    $blockContent
                );
                $contentList[] = $blockContent;
            }
            $content = implode($separator, $contentList);
        }

        if (!empty($settings['markParsedBlock'])) {
            $content = "<!-- start $placeholderName -->$content<!-- end $placeholderName -->";
        }

        $code = str_replace('{' . $placeholderName . '}', $content, $code);
    }

    /**
     * Get the settings from database
     *
     * @staticvar array $settings settings array
     *
     * @return array settings array
     */
    public function getSettings()
    {

        static $settings = array();
        if (!empty($settings)) {
            return $settings;
        }

        $query = '
            SELECT
                `name`,
                `value`
            FROM
                `'. DBPREFIX .'module_block_settings`';
        $setting = \Cx\Core\Core\Controller\Cx::instanciate()
                    ->getDb()
                    ->getAdoDb()
                    ->Execute($query);
        if ($setting === false) {
            return array();
        }
        while (!$setting->EOF) {
            $settings[$setting->fields['name']] = $setting->fields['value'];
            $setting->MoveNext();
        }

        return $settings;
    }

    /**
    * Save the settings associated to the block system
    *
    * @access    private
    * @param    array     $arrSettings
    */
    function _saveSettings($arrSettings)
    {
        \Cx\Core\Setting\Controller\Setting::init('Config', 'component','Yaml');
        if (isset($arrSettings['blockStatus'])) {
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('blockStatus')) {
                \Cx\Core\Setting\Controller\Setting::add('blockStatus', $arrSettings['blockStatus'], 1,
                \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:TXT_ACTIVATED,0:TXT_DEACTIVATED', 'component');
            } else {
                \Cx\Core\Setting\Controller\Setting::set('blockStatus', $arrSettings['blockStatus']);
                \Cx\Core\Setting\Controller\Setting::update('blockStatus');
            }
        }
        if (isset($arrSettings['blockRandom'])) {
            if (!\Cx\Core\Setting\Controller\Setting::isDefined('blockRandom')) {
                \Cx\Core\Setting\Controller\Setting::add('blockRandom', $arrSettings['blockRandom'], 1,
                \Cx\Core\Setting\Controller\Setting::TYPE_RADIO, '1:TXT_ACTIVATED,0:TXT_DEACTIVATED', 'component');
            } else {
                \Cx\Core\Setting\Controller\Setting::set('blockRandom', $arrSettings['blockRandom']);
                \Cx\Core\Setting\Controller\Setting::update('blockRandom');
            }
        }
    }

    /**
     * create the categories dropdown
     *
     * @param array $arrCategories
     * @param array $arrOptions
     * @param integer $level
     * @return string categories as HTML options
     */
    function _getCategoriesDropdown($parent = 0, $catId = 0, $arrCategories = array(), $arrOptions = array(), $level = 0)
    {
        global $objDatbase;

        $first = false;
        if(count($arrCategories) == 0){
            $first = true;
            $level = 0;
            $this->_getCategories();
            $arrCategories = $this->_categories[0]; //first array contains all root categories (parent id 0)
        }

        foreach ($arrCategories as $arrCategory) {
            $this->_categoryOptions[] =
                '<option value="'.$arrCategory['id'].'" '
                .(
                  $parent > 0 && $parent == $arrCategory['id']  //selected if parent specified and id is parent
                    ? 'selected="selected"'
                    : ''
                 )
                .(
                  ( $catId > 0 && in_array($arrCategory['id'], $this->_getChildCategories($catId)) ) || $catId == $arrCategory['id'] //disable children and self
                    ? 'disabled="disabled"'
                    : ''
                 )
                .' >' // <option>
                .str_repeat('&nbsp;', $level*4)
                .htmlentities($arrCategory['name'], ENT_QUOTES, CONTREXX_CHARSET)
                .'</option>';

            if(!empty($this->_categories[$arrCategory['id']])){
                $this->_getCategoriesDropdown($parent, $catId, $this->_categories[$arrCategory['id']], $arrOptions, $level+1);
            }
        }
        if($first){
            return implode("\n", $this->_categoryOptions);
        }
    }

    /**
     * save a block category
     *
     * @param integer $id
     * @param integer $parent
     * @param string $name
     * @param string $seperator
     * @param integer $order
     * @param integer $status
     * @return integer inserted ID or false on failure
     */
    function _saveCategory($id = 0, $parent = 0, $name, $seperator, $order = 1, $status = 1)
    {
        global $objDatabase;

        $id = intval($id);
        if($id > 0 && $id == $parent){ //don't allow category to attach to itself
            return false;
        }

        if($id == 0){ //if new record then set to NULL for auto increment
            $id = 'NULL';
        } else {
            $arrChildren = $this->_getChildCategories($id);
            if(in_array($parent, $arrChildren)){ //don't allow category to be attached to one of it's own children
                return false;
            }
        }
        $name = contrexx_addslashes($name);
        $seperator = contrexx_addslashes($seperator);
        if($objDatabase->Execute('
            INSERT INTO `'.DBPREFIX."module_block_categories`
            (`id`, `parent`, `name`, `seperator`, `order`, `status`)
            VALUES
            ($id, $parent, '$name', '$seperator', $order, $status )
            ON DUPLICATE KEY UPDATE
            `id`       = $id,
            `parent`   = $parent,
            `name`     = '$name',
            `seperator`= '$seperator',
            `order`    = $order,
            `status`   = $status"))
        {
            return $id == 'NULL' ? $objDatabase->Insert_ID() : $id;
        } else {
            return false;
        }
    }

    /**
     * return all child caegories of a cateogory
     *
     * @param integer ID of category to get list of children from
     * @param array cumulates the child arrays, internal use
     * @return array IDs of children
     */
    function _getChildCategories($id, &$_arrChildCategories = array())
    {
        if(empty($this->_categories)){
            $this->_getCategories();
        }
        if (!isset($this->_categories[$id])) {
            return array();
        }
        foreach ($this->_categories[$id] as $cat) {
            if(!empty($this->_categories[$cat['parent']])){
                $_arrChildCategories[] = $cat['id'];
                $this->_getChildCategories($cat['id'], $_arrChildCategories);
            }

        }
        return $_arrChildCategories;
    }

    /**
     * delete a category by id
     *
     * @param integer $id category id
     * @return bool success
     */
    function _deleteCategory($id = 0)
    {
        global $objDatabase;

        $id = intval($id);
        if($id < 1){
            return false;
        }
        return $objDatabase->Execute('DELETE FROM `'.DBPREFIX.'module_block_categories` WHERE `id`='.$id)
            && $objDatabase->Execute('UPDATE `'.DBPREFIX.'module_block_categories` SET `parent` = 0 WHERE `parent`='.$id)
            && $objDatabase->Execute('UPDATE `'.DBPREFIX.'module_block_blocks` SET `cat` = 0 WHERE `cat`='.$id);
    }

    /**
     * fill and/or return the categories array
     *
     * category arrays are put in the array as first dimension elements, with their parent as key, as follows:
     * $this->_categories[$objRS->fields['parent']][] = $objRS->fields;
     *
     * just to make this clear:
     * note that $somearray['somekey'][] = $foo adds $foo to $somearray['somekey'] rather than overwriting it.
     *
     * @param bool force refresh from DB
     * @see blockManager::_parseCategories for parse example
     * @see blockLibrary::_getCategoriesDropdown for parse example
     * @global ADONewConnection
     * @global array
     * @return array all available categories
     */
    function _getCategories($refresh = false)
    {
        global $objDatabase, $_ARRAYLANG;

        if(!empty($this->_categories) && !$refresh){
            return $this->_categories;
        }

        $this->_categories = array(0 => array());

        $this->_categoryNames[0] = $_ARRAYLANG['TXT_BLOCK_NONE'];
        $objRS = $objDatabase->Execute('
           SELECT `id`,`parent`,`name`,`order`,`status`,`seperator`
           FROM `'.DBPREFIX.'module_block_categories`
           ORDER BY `order` ASC, `id` ASC
        ');
        if ($objRS !== false && $objRS->RecordCount() > 0) {
            while(!$objRS->EOF){
                $this->_categories[$objRS->fields['parent']][] = $objRS->fields;
                $this->_categoryNames[$objRS->fields['id']] = $objRS->fields['name'];
                $objRS->MoveNext();
            }
        }
        return $this->_categories;
    }

    /**
     * return the categoriy specified by ID
     *
     * @param integer $id
     * @return array category information
     */
    function _getCategory($id = 0)
    {
        global $objDatabase;

        $id = intval($id);
        if($id == 0){
            return false;
        }

        $objRS = $objDatabase->Execute('
           SELECT `id`,`parent`,`name`,`seperator`,`order`,`status`
           FROM `'.DBPREFIX.'module_block_categories`
           WHERE `id`= '.$id
        );
        if(!$objRS){
            return false;
        }
        return $objRS->fields;
    }
}
