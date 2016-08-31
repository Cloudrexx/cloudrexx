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
 * Class Delta
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_update
 */

namespace Cx\Core_Modules\Update\Model\Entity;

/**
 * Class Delta
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_update
 */
class Delta extends \Cx\Core\Model\Model\Entity\YamlEntity {

    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var integer $codeBaseId
     */
    private $codeBaseId;

    /**
     * @var boolean $rollback
     */
    private $rollback;

    /**
     * @var integer $offset
     */
    private $offset;

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set id
     *
     * @param integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Set codeBaseId
     *
     * @param integer $codeBaseId
     */
    public function setCodeBaseId($codeBaseId) {
        $this->codeBaseId = $codeBaseId;
    }

    /**
     * Get codeBaseId
     *
     * @return integer $codeBaseId
     */
    public function getCodeBaseId() {
        return $this->codeBaseId;
    }

    /**
     * Set rollback
     *
     * @param boolean $rollback
     */
    public function setRollback($rollback) {
        $this->rollback = $rollback;
    }

    /**
     * Get rollback
     *
     * @return boolean $rollback
     */
    public function getRollback() {
        return $this->rollback;
    }

    /**
     * Set offset
     *
     * @param integer $offset
     */
    public function setOffset($offset) {
        $this->offset = $offset;
    }

    /**
     * Get offset
     *
     * @return integer $offset
     */
    public function getOffset() {
        return $this->offset;
    }

    /**
     * addCodeBase
     *
     * @param integer $codeBaseId
     * @param boolean $rollback
     * @param integer $offset
     */
    public function addCodeBase($codeBaseId, $rollback, $offset) {
        $this->setCodeBaseId($codeBaseId);
        $this->setRollback($rollback);
        $this->setOffset($offset);
    }

    /**
     * Use to run the migration command
     */
    public function applyNext() {
        $process = $this->rollback ? '--down' : '--up';
        $result = $this->runMigrationCommand($this->codeBaseId, $process);

        return $result == 0 ? true : false;
    }

    /**
     * Run the Migration command to migrate the DB
     *
     * @param integer $version
     * @param string  $process
     *
     * @return integer
     */
    protected function runMigrationCommand($version, $process) {

        $argv = array(
            __FILE__,
            'migrations:execute',
            $version,
            $process,
            '--no-interaction'
        );

        $_SERVER['argv'] = $argv;

        $cli = $this->getComponentController()->getController('Update')->getDoctrineMigrationCli();
        return $cli->run();
    }

}
