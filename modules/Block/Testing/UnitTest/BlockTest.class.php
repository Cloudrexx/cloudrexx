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
 * BlockTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */

namespace Cx\Modules\Block\Testing\UnitTest;

/**
 * BlockTest
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      SS4U <ss4u.comvation@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */
class BlockTest extends \Cx\Core\Test\Model\Entity\DoctrineTestCase {
    /**
     * @covers \Cx\Modules\Block\Controller\JsonBlockController::getBlockContent
     * @expectedException \Cx\Modules\Block\Controller\NoPermissionException
     */
    public function testGetBlockContentNoPermission() {
        $jsonBlock = $this->getJsonBlockController();
        $jsonBlock->getBlockContent(array('get' => array('block' => 1, 'lang' => 'de')));
    }

    /**
     * @covers \Cx\Modules\Block\Controller\JsonBlockController::getBlockContent
     * @expectedException \Cx\Modules\Block\Controller\NotEnoughArgumentsException
     */
    public function testGetBlockContentNotEnoughArguments() {
        $user = \FWUser::getFWUserObject()->objUser->getUser(1);
        \FWUser::loginUser($user);

        $jsonBlock = $this->getJsonBlockController();
        $jsonBlock->getBlockContent(array());
    }

    /**
     * @covers \Cx\Modules\Block\Controller\JsonBlockController::getBlockContent
     * @expectedException \Cx\Modules\Block\Controller\NoBlockFoundException
     */
    public function testGetBlockContentNoBlockFound() {
        $user = \FWUser::getFWUserObject()->objUser->getUser(1);
        \FWUser::loginUser($user);

        $jsonBlock = $this->getJsonBlockController();
        $jsonBlock->getBlockContent(array('get' => array('block' => 999, 'lang' => 'de')));
    }

    /**
     * @covers \Cx\Modules\Block\Controller\JsonBlockController::getBlockContent
     */
    public function testGetBlockContent() {
        $user = \FWUser::getFWUserObject()->objUser->getUser(1);
        \FWUser::loginUser($user);

        $jsonBlock = $this->getJsonBlockController();
        $result = $jsonBlock->getBlockContent(array('get' => array('block' => 32, 'lang' => 'de')));
        $this->assertArrayHasKey('content', $result);
    }

    /**
     * @covers \Cx\Modules\Block\Controller\JsonBlockController::saveBlockContent
     * @expectedException \Cx\Modules\Block\Controller\NotEnoughArgumentsException
     */
    public function testSaveBlockContentNotEnoughArguments() {
        $user = \FWUser::getFWUserObject()->objUser->getUser(1);
        \FWUser::loginUser($user);

        $jsonBlock = $this->getJsonBlockController();
        $jsonBlock->saveBlockContent(array());
    }

    /**
     * @covers \Cx\Modules\Block\Controller\JsonBlockController::saveBlockContent
     */
    public function testSaveBlockContent() {
        $user = \FWUser::getFWUserObject()->objUser->getUser(1);
        \FWUser::loginUser($user);

        $jsonBlock = $this->getJsonBlockController();
        $jsonBlock->saveBlockContent(array('get' => array('block' => 32, 'lang' => 'de'), 'post' => array('content' => 'bla')));

        $result = $jsonBlock->getBlockContent(array('get' => array('block' => 32, 'lang' => 'de')));
        $this->assertEquals('bla', $result['content']);
    }

    /**
     * Get json block controller using repository
     *
     * @return \Cx\Modules\Block\Controller\JsonBlockController
     */
    public function getJsonBlockController()
    {
        $componentRepo = self::$cx
                            ->getDb()
                            ->getEntityManager()
                            ->getRepository('Cx\Core\Core\Model\Entity\SystemComponent');
        $componentContoller = $componentRepo->findOneBy(array('name' => 'Block'));
        if (!$componentContoller) {
            return;
        }
        return $componentContoller->getController('JsonBlock');
    }
}
