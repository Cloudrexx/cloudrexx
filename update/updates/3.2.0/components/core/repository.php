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


function _updateModuleRepository_%MODULE_ID%()
{
    global $objDatabase;

    $arrModuleRepositoryPages = array(/*REPOSITORY_ARRAY*/);

    $query = "DELETE FROM ".DBPREFIX."module_repository WHERE `moduleid`=%MODULE_ID%";
    if ($objDatabase->Execute($query) === false) {
        return _databaseError($query, $objDatabase->ErrorMsg());
    }

    $arrPageId = array();
    foreach ($arrModuleRepositoryPages as $arrPage) {
        $arrPage['query'] = str_replace(
            '[[PKG_MODULE_REPOSITORY_PAGE_PARID]]',
            (empty($arrPageId[$arrPage['parid']])
              ? 0 : $arrPageId[$arrPage['parid']]
            ),
            $arrPage['query']
        );

        if ($objDatabase->Execute($arrPage['query']) === false) {
            return _databaseError($arrPage['query'], $objDatabase->ErrorMsg());
        }
        $arrPageId[$arrPage['id']] = $objDatabase->Insert_ID();
    }
    return true;
}

?>
