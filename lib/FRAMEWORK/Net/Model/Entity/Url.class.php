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
 * URL
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <drissg@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_net
 */

namespace Cx\Lib\Net\Model\Entity;

/**
 * Exception from within this file
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <drissg@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_net
 */
class UrlException extends \Exception {}

/**
 * Represents an URL as specified in RFC 3986
 * @link https://tools.ietf.org/html/rfc3986
 * @todo Add query section parsing
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <drissg@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_net
 */
class Url extends Uri {
    
    /**
     * @var string Regular expression to split authority section
     */
    const AUTHORITY_SECTION_REGEX = '/^(?:([^@:]*)(?::([^@]*))?@)?(.*?)(?::([0-9]+))?$/';
    
    /**
     * @var int Index of the above REGEX for username part
     */
    const AUTHORITY_SECTION_REGEX_INDEX_USERNAME = 1;
    
    /**
     * @var int Index of the above REGEX for password part
     */
    const AUTHORITY_SECTION_REGEX_INDEX_PASSWORD = 2;
    
    /**
     * @var int Index of the above REGEX for host part
     */
    const AUTHORITY_SECTION_REGEX_INDEX_HOST = 3;
    
    /**
     * @var int Index of the above REGEX for port part
     */
    const AUTHORITY_SECTION_REGEX_INDEX_PORT = 4;
    
    /**
     * @var string Delimiter for user information
     */
    const USER_INFORMATION_DELIMITER = '@';
    
    /**
     * @var string Delimiter to separate username and password
     */
    const PASSWORD_DELIMITER = ':';
    
    /**
     * @var string Delimiter for port section
     */
    const PORT_DELIMITER = ':';
    
    /**
     * @var string Username
     */
    protected $username;
    
    /**
     * @var string Password
     */
    protected $password;
    
    /**
     * @var string Host section
     */
    protected $host;
    
    /**
     * @var int Port
     */
    protected $port;
    
    /**
     * Sets the current authority, forces authority to be present and splits it into parts
     * @param string $authority New authority
     */
    public function setAuthority($authority) {
        // ensure authority section is present
        $authorityBackup = $this->authority;
        parent::setAuthority($authority);
        if (!$this->hasAuthority()) {
            $this->authority = $authorityBackup;
            throw new UrlException('Not a valid URL, might be a valid URN');
        }
        
        // split authority into parts and use setters
        $authorityParts = array();
        preg_match(static::AUTHORITY_SECTION_REGEX, $authority, $authorityParts);
        if (isset($authorityParts[static::AUTHORITY_SECTION_REGEX_INDEX_USERNAME])) {
            $this->setUsername($authorityParts[static::AUTHORITY_SECTION_REGEX_INDEX_USERNAME]);
        }
        if (isset($authorityParts[static::AUTHORITY_SECTION_REGEX_INDEX_PASSWORD])) {
            $this->setPassword($authorityParts[static::AUTHORITY_SECTION_REGEX_INDEX_PASSWORD]);
        }
        $this->setHost($authorityParts[static::AUTHORITY_SECTION_REGEX_INDEX_HOST]);
        if (isset($authorityParts[static::AUTHORITY_SECTION_REGEX_INDEX_PORT])) {
            $this->setPort($authorityParts[static::AUTHORITY_SECTION_REGEX_INDEX_PORT]);
        }
    }
    
    /**
     * Re-assembles authority parts and returns the complete authority section
     * @return string Authority section
     */
    public function getAuthority() {
        $authority = '';
        if ($this->hasUserInformation()) {
            $authority .= $this->getUsername();
            if ($this->hasPassword()) {
                $authority .= static::PASSWORD_DELIMITER . $this->getPassword();
            }
            $authority .= static::USER_INFORMATION_DELIMITER;
        }
        $authority .= $this->getHost();
        if ($this->hasPort()) {
            $authority .= static::PORT_DELIMITER . $this->getPort();
        }
        return $authority;
    }
    
    /**
     * Returns the current username
     * @return string Username
     */
    public function getUsername() {
        return $this->username;
    }
    
    /**
     * Sets the current username
     * @param string $username New username
     */
    public function setUsername($username) {
        $this->username = $username;
        parent::setAuthority($this->getAuthority());
    }
    
    /**
     * Returns the current password
     * @return string Password
     */
    public function getPassword() {
        return $this->password;
    }
    
    /**
     * Sets the current password
     * @param string $password New password
     */
    public function setPassword($password) {
        $this->password = $password;
        parent::setAuthority($this->getAuthority());
    }
    
    /**
     * Tells wheter this Url has a password section
     * @return boolean True if there's an password section, false otherwise
     */
    public function hasPassword() {
        return !empty($this->getPassword());
    }
    
    /**
     * Tells wheter this Url has a user information section
     * @return boolean True if there's an user information section, false otherwise
     */
    public function hasUserInformation() {
        return $this->hasPassword() || !empty($this->getUsername());
    }
    
    /**
     * Returns the current host
     * @return string Host
     */
    public function getHost() {
        return $this->host;
    }
    
    /**
     * Sets the current host
     * @param string $host New host
     */
    public function setHost($host) {
        $this->host = $host;
        parent::setAuthority($this->getAuthority());
    }
    
    /**
     * Returns the current port
     * @return int Port
     */
    public function getPort() {
        return $this->port;
    }
    
    /**
     * Sets the current port
     * @param int $port New port
     */
    public function setPort($port) {
        $this->port = (int) $port;
        parent::setAuthority($this->getAuthority());
    }
    
    /**
     * Tells wheter this Url has a port section
     * @return boolean True if there's an port section, false otherwise
     */
    public function hasPort() {
        return !empty($this->getPort());
    }
    
    /**
     * Returns the parsed parts of the query section based on php.ini
     * @return array Key=>value style array
     */
    public function getParsedQuery() {
        $queryParts = array();
        parse_str($this->getQuery(), $queryParts);
        return $queryParts;
    }
    
    /**
     * Sets the parsed query
     * @param array $queryParts Key=>value style array
     */
    public function setParsedQuery($queryParts) {
        $query = http_build_query($queryParts, '', '&');
        $this->setQuery($query);
    }
    
    /**
     * Returns the parsed parts of the query section based on php.ini
     * @deprecated Use getParsedQuery() instead
     * @return array Key=>value style array
     */
    public function getParamArray() {
        return $this->getParsedQuery();
    }
    
    /**
     * Sets an empty query section to this URL
     * @deprecated Use setQuery('') instead
     */
    public function removeAllParams() {
        $this->setQuery('');
    }
    
    /**
     * Returns a value from the query section based on given key, null if not set
     * @todo Naming isn't consistent with RFC's expressions
     * @param string $key Key to look for
     * @return string|null Value for the given key or null if key is not set
     */
    public function getParam($key) {
        $queryParts = $this->getParsedQuery();
        if (!isset($queryParts[$key])) {
            return null;
        }
        return $queryParts[$key];
    }
    
    /**
     * Sets or unsets a key/value pair of the query section
     * @todo Naming isn't consistent with RFC's expressions
     * @param string $key Key to set/unset
     * @param string|null $value Value to set. If null, key will be unset
     */
    public function setParam($key, $value) {
        $queryParts = $this->getParsedQuery();
        if ($value === null) {
            unset($queryParts[$key]);
        } else {
            $queryParts[$key] = $value;
        }
        $this->setParsedQuery($queryParts);
    }
    
    /**
     * Sets or unsets key/value pairs of the query section
     * @todo Naming isn't consistent with RFC's expressions
     * @param array $paramArray Key=>value style array
     * @see setParam($key, $value)
     */
    public function setParams($paramArray) {
        foreach ($paramArray as $key=>$value) {
            $this->setParam($key, $value);
        }
    }
    
    /**
     * Checks wheter a key is present in this URL's query section
     * @todo Naming isn't consistent with RFC's expressions
     * @param string $key Key to look for
     * @return boolean True if key is present, false otherwise
     */
    public function hasParam($key) {
        $queryParts = $this->getParsedQuery();
        return isset($queryParts[$key]);
    }
}

