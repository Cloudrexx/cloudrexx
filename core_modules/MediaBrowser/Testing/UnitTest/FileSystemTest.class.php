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
 * FileSystemTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_mediabrowser
 */

namespace Cx\Core_Modules\MediaBrowser\Testing\UnitTest;

use Cx\Core\Core\Controller\Cx;
use Cx\Core\MediaSource\Model\Entity\MediaSourceManager;
use Cx\Core\Test\Model\Entity\ContrexxTestCase;

/**
 * FileSystemTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Robin Glauser <robin.glauser@comvation.com>
 * @version     1.0.0
 */
class FileSystemTest extends ContrexxTestCase
{
    /**
     * Test the pathchecker in the filessystem.
     */
    public function testPathchecker()
    {
        $this->assertTrue(MediaSourceManager::isVirtualPath('files/Movies'));
        $this->assertTrue(!MediaSourceManager::isVirtualPath(
            '/var/www/contrexx/images/content/Movies'
        )
        );
    }

    /**
     * Test the subdirectory check in the filesystem class
     */
    public function testSubdirectoryCheck()
    {
        $cx = Cx::instanciate();
        $this->assertTrue(
            MediaSourceManager::isSubdirectory(
                $cx->getWebsitePath() . '/images', 'files/'
            )
        );
        $this->assertFalse(
            MediaSourceManager::isSubdirectory(
                $cx->getWebsitePath() . '/media', 'files/'
            )
        );
        $this->assertFalse(
            MediaSourceManager::isSubdirectory(
                $cx->getWebsitePath() . '/images', 'media5/'
            )
        );
    }


}
