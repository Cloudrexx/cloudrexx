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


namespace Cx\Core\ViewManager\Model\Entity;

use Cx\Core\MediaSource\Model\Entity\LocalFileSystem;

/**
 * Class ViewManagerFileSystem
 *
 * @copyright   Cloudrexx AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @package     cloudrexx
 */
class ViewManagerFileSystem extends LocalFileSystem
{
    protected $codeBaseFileSystem;

    function __construct($path)
    {
        parent::__construct($path);
        $this->codeBaseFileSystem
            = new LocalFileSystem($this->cx->getCodeBaseThemesPath());
    }

    /**
     * @param            $directory
     * @param bool|false $recursive
     *
     * @return array
     */
    public function getFileList($directory, $recursive = false)
    {
        $codeBaseFileList = $this->codeBaseFileSystem->getFileList(
            $directory, $recursive, true
        );
        $fileList         = parent::getFileList($directory, $recursive);
        foreach ($fileList as &$files) {
            $files['datainfo']['readonly'] = true;
        }
        return $this->mergeFileList($codeBaseFileList, $fileList);
    }

    /**
     * Merge two file lists into one
     *
     * @param $a
     * @param $b
     *
     * @return array
     */
    public function mergeFileList($a, $b)
    {
        $resultFileList = $a;
        foreach ($b as $name => $directory) {
            $resultFileList[$name]             = $this->mergeFileList(
                $resultFileList[$name], $directory
            );
            $resultFileList[$name]['datainfo'] = $directory['datainfo'];
        }
        return $resultFileList;
    }

}
