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
 * Media Directory Placeholders
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/**
 * Media Directory Placeholders
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryPlaceholders extends MediaDirectoryLibrary
{
    private $strPlaceholder;

    /**
     * Constructor
     */
    function __construct($name)
    {

        parent::__construct('.', $name);
        parent::getSettings();
    }

    function getNavigationPlacholder()
    {
        $this->strPlaceholder = null;

        if($this->arrSettings['settingsShowLevels'] == 1) {
            $objLevels = new MediaDirectoryLevel(null, null, 0, $this->moduleName);
            $intLevelId = isset($_GET['lid']) ? intval($_GET['lid']) : null;

            $this->strPlaceholder = $objLevels->listLevels($this->_objTpl, 6, $intLevelId);
        } else {
            $objCategories = new MediaDirectoryCategory(null, null, 0, $this->moduleName);
            $intCategoryId = isset($_GET['cid']) ? intval($_GET['cid']) : null;

            $this->strPlaceholder = $objCategories->listCategories($this->_objTpl, 6, $intCategoryId, null, null, null, 1);
        }

        return '<ul id="'.$this->moduleNameLC.'NavigationPlacholder">'.$this->strPlaceholder.'</ul>';
    }

    function getLatestPlacholder()
    {
        $this->strPlaceholder = null;

        $intLimitEnd = intval($this->arrSettings['settingsLatestNumOverview']);

        $objEntries = new MediaDirectoryEntry($this->moduleName);
        $objEntries->getEntries(null,null,null,null,true,null,1,null,$intLimitEnd);

        foreach($objEntries->arrEntries as $intEntryId => $arrEntry) {
            if($objEntries->checkPageCmd('detail'.intval($arrEntry['entryFormId']))) {
                $strDetailCmd = 'detail'.intval($arrEntry['entryFormId']);
            } else {
                $strDetailCmd = 'detail';
            }

            $strDetailUrl = 'index.php?section='.$this->moduleName.'&amp;cmd='.$strDetailCmd.'&amp;eid='.$arrEntry['entryId'];

            $this->strPlaceholder .= '<li><a href="'.$strDetailUrl.'">'.$arrEntry['entryFields'][0].'</a></li>';
        }

        return '<ul id="'.$this->moduleNameLC.'LatestPlacholder">'.$this->strPlaceholder.'</ul>';
    }
}
?>
