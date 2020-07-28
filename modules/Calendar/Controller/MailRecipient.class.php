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
 * Calendar Class Mail Manager
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx <info@cloudrexx.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_calendar
 */
namespace Cx\Modules\Calendar\Controller;

/**
 * This class represents a notification mail recipient.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Däppen <thomas.daeppen@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_calendar
 * @todo    Add DocBlocks
 */
class MailRecipient {
    protected $id;
    protected $lang;
    protected $type;
    protected $address;
    protected $salutationId;
    protected $firstname = '';
    protected $lastname = '';
    protected $username = '';

    const RECIPIENT_TYPE_MAIL = '-';
    const RECIPIENT_TYPE_ACCESS_USER = 'AccessUser';
    const RECIPIENT_TYPE_CRM_CONTACT = 'CrmContact';

    public function __construct() {
        $this->type = static::RECIPIENT_TYPE_MAIL;
        $this->id = 0;
    }

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function getId() {
        return $this->id;
    }

    public function setLang($lang) {
        $this->lang = $lang;
        return $this;
    }

    public function getLang() {
        return $this->lang;
    }

    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    public function getType() {
        return $this->type;
    }

    public function setAddress($address) {
        $address = trim($address);
        $this->address = $address;
        if (empty($this->username)) {
            $this->username = $address;
        }
        return $this;
    }

    public function getAddress() {
        return $this->address;
    }

    public function setSalutationId($salutationId) {
        $this->salutationId = $salutationId;
        return $this;
    }

    public function getSalutationId() {
        return $this->salutationId;
    }

    public function setFirstname($firstname) {
        $this->firstname = $firstname;
        return $this;
    }

    public function getFirstname() {
        return $this->firstname;
    }

    public function setLastname($lastname) {
        $this->lastname = $lastname;
        return $this;
    }

    public function getLastname() {
        return $this->lastname;
    }

    public function setUsername($username) {
        $this->username = $username;
        return $this;
    }

    public function getUsername() {
        return $this->username;
    }
}
