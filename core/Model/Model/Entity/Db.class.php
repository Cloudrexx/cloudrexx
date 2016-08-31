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

namespace Cx\Core\Model\Model\Entity;
/*
 * Db class
 * */
class Db{

    /*
     * Protected db id
     * */
    protected $id;

    /*
     * Protected db name
     * */
    protected $name;

    /*
     * Protected db host
     * */
    protected $host;

    /*
     * Protected db table prefix
     * */
    protected $tablePrefix;

    /*
     * Protected db type
     * */
    protected $dbType;

    /*
     * Protected db timezone
     * */
    protected $timezone;

    /*
     * Protected db character set
     * */
    protected $charset;

    /*
     * Protected db collation
     * */
    protected $collation;

    /*
     * Constructor function for Db class
     * */
    function __construct($dbConfig=array()){
        if(!empty($dbConfig['host'])){
            $this->setHost($dbConfig['host']);
        }
        if(!empty($dbConfig['database'])){
            $this->setName($dbConfig['database']);
        }
        if(!empty($dbConfig['tablePrefix'])){
            $this->setTablePrefix($dbConfig['tablePrefix']);
        }
        if(!empty($dbConfig['dbType'])){
            $this->setDbType($dbConfig['dbType']);
        }
        if(!empty($dbConfig['charset'])){
            $this->setCharset($dbConfig['charset']);
        }
        if(!empty($dbConfig['collation'])){
            $this->setCollation($dbConfig['collation']);
        }
        if(!empty($dbConfig['timezone'])){
            $this->setTimezone($dbConfig['timezone']);
        }
    }

    /**
     * Set db id
     * @param string $id id of the dbUser
     */
    public function setId($id=''){
        $this->id = $id;
    }

    /**
     * get db id
     */
    public function getId(){
        return $this->id;
    }

    /**
    * set db name
    * @param string $name name of the db
    */
    public function setName($name=''){
        $this->name = $name;
    }

    /**
    * get db name
    * @return string $name of the db
    */
    public function getName(){
        return $this->name;
    }

    /**
    * set db host
    * @param string $host for the db
    */
    public function setHost($host){
        $this->host= $host;
    }

    /**
    * get db host
    * @return string $host of the db
    */
    public function getHost(){
        return $this->host;
    }

    /**
    * set db Table Prefix
    * @param string $tablePrefix of the db
    */
    public function setTablePrefix($tablePrefix){
        $this->tablePrefix= $tablePrefix;
    }

    /**
    * get db Table Prefix
    * @return string $tablePrefix of the db
    */
    public function getTablePrefix(){
        return $this->tablePrefix;
    }

    /**
    * set db Type
    * @param string $dbType of the db
    */
    public function setdbType($dbType){
        $this->dbType = $dbType;
    }

    /**
    * get db Type Prefix
    * @return string $dbType of the db
    */
    public function getdbType(){
        return $this->dbType;
    }

    /**
    * set db Charcter set
    * @param string $charset of the db
    */
    public function setCharset($charset){
        $this->charset = $charset;
    }

    /**
    * get db character set
    * @return string $charset of the db
    */
    public function getCharset(){
        return $this->charset;
    }

    /**
    * set db Collation
    * @param string $Collation the db
    */
    public function setCollation($collation){
        $this->collation = $collation;
    }

    /**
    * get db Collation
    * @return string $collation of the db
    */
    public function getCollation(){
        return $this->collation;
    }

    public function setTimezone($timezone){
        $this->timezone = $timezone;
    }

    public function getTimezone(){
        return $this->timezone;
    }

}
