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
 * Wrapper class for ADODB
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_pear
 */

/**
 * Wrapper class for ADODB
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_pear
 */
class DB_NestedSet_ADODB extends DB_NestedSet {

    /**
     * Database object
     *
     * @access      private
     * @var         ADONewConnection
     */
    private $db;

    public function __construct($dsn, $params = array()) {
        $this->DB_NestedSet_ADODB($dsn, $params);
    }

    /**
     * Constructor
     *
     * @access  public
     * @param   mixed   $dsn        dsn as pear dsn uri or dsn array
     * @param   array   $params     database column fields which should be returned
     */
    public function DB_NestedSet_ADODB($dsn, $params = array()) {
        $this->_debugMessage('DB_NestedSet_ADODB($dsn, $params = array())');
        $this->DB_NestedSet($params);
        $this->db = & $this->_db_Connect($dsn);
        if ($this->_isDBError($this->db)) {
            return false;
        }
    }

    /**
     * Destructor
     *
     * @access public
     */
    public function _DB_NestedSet_ADODB()
    {
        $this->_debugMessage('_DB_NestedSet_ADODB()');
        $this->_DB_NestedSet();
        $this->_db_Disconnect();
    }

    /**
     * Connects to the db
     *
     * @access  private
     * @param   mixed               $dsn    dsn as pear dsn uri or dsn array
     * @return  ADONewConnection    $db     ADONewConnection object
     */
    private function & _db_Connect($dsn)
    {
        $this->_debugMessage('_db_Connect($dsn)');

        if (is_object($this->db)) {
            return $this->db;
        }
        if (is_object($dsn)) {
            return $dsn;
        }
        
        $db = & NewADOConnection($dsn);
        $this->_testFatalAbort($db, __FILE__, __LINE__);
        return $db;
    }

    /**
     * See ADONewConnection::RecordCount()
     *
     * @access  public
     * @param   mixed
     * @return  mixed
     */
    public function _numRows($res)
    {
        return $res->RecordCount();
    }

    /**
     * Checks if the passed param is false
     *
     * @access  public
     * @param   mixed       $err
     * @return  boolean
     */
    public function _isDBError($err)
    {
        if ($err !== false) {
            return false;
        }
        return true;
    }

    /**
     * See ADONewConnection::Execute()
     *
     * @access  public
     * @param   mixed
     * @return  mixed
     */
    public function _query($sql)
    {
        return $this->db->Execute($sql);
    }

    /**
     * See ADONewConnection::qstr()
     *
     * @access  public
     * @param   mixed
     * @return  mixed
     */
    public function _quote($str)
    {
        return $this->db->qstr($str, CONTREXX_ESCAPE_GPC);
    }

    /**
     * Adds quotes to the passed param
     *
     * @access  public
     * @param   string
     * @return  string
     */
    public function _quoteIdentifier($str)
    {
        return '`'.$str.'`';
    }

    /**
     * See ADONewConnection::DropSequence()
     *
     * @access  public
     * @param   mixed
     * @return  mixed
     */
    public function _dropSequence($sequence)
    {
        return $this->db->DropSequence($sequence);
    }

    /**
     * See ADONewConnection::NextID()
     *
     * @access  public
     * @param   mixed
     * @return  mixed
     */
    public function _nextId($sequence)
    {
        $nextId = $this->db->NextID($sequence);

        // workaround since ADOConnection::NextID() doesn't return the last inserted id in `contrexx_module_news_categories_catid`
        if (empty($nextId) && ($sequence == DBPREFIX.'module_news_categories_catid')) {
            if ($objResult = $this->db->Execute('SELECT `id` FROM `'.contrexx_raw2db($sequence).'` LIMIT 1')) {
                $nextId = $objResult->fields['id'];
            }
        }

        return $nextId;
    }

    /**
     * See ADONewConnection::GetOne()
     *
     * @access  public
     * @param   mixed
     * @return  mixed
     */
    public function _getOne($sql)
    {
        return $this->db->GetOne($sql);
    }

    /**
     * See ADOConnection::GetAll()
     *
     * @access  public
     * @param   mixed
     * @return  mixed
     */
    public function _getAll($sql)
    {
        return $this->db->GetAll($sql);
    }

    /**
     * Disconnects from db
     *
     * @access  private
     * @return  boolean
     */
    private function _db_Disconnect()
    {
        $this->_debugMessage('_db_Disconnect()');
        if (is_object($this->db)) {
            @$this->db->Close();
        }
        return true;
    }
}
