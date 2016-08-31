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
 * DbUser class
 * */
class DbUser{

    protected $id;
    protected $name;
    protected $password;

    function __construct($dbConfig=array()){
        if(!empty($dbConfig['user'])){
            $this->setName($dbConfig['user']);
        }
        if(!empty($dbConfig['password'])){
            $this->setPassword($dbConfig['password']);
        }
    }
    /**
     * Set db user id
     * @param string $id id of the dbUser
     */
    public function setId($id=''){
        $this->id = $id;
    }

    /**
     * get db user id
     */
    public function getId(){
        return $this->id;
    }

    /**
    * set db username
    * @param string $name name of the dbUser
    */
    public function setName($name=''){
        $this->name = $name;
    }

    /**
    * get db username
    */
    public function getName(){
        return $this->name;
    }

    /**
    * set db password
    * @param string $dbPassword password for the dbUser to be created
    */
    public function setPassword($password=''){
        $this->password = $password;
    }

    /**
    * get db password
    */
    public function getPassword(){
        return $this->password;
    }
}
