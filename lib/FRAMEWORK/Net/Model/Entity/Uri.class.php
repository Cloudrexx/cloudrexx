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
 * URI
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <drissg@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_net
 */

namespace Cx\Lib\Net\Model\Entity;

/**
 * Represents an URI (URL or URN) as specified in RFC 3986
 * @link https://tools.ietf.org/html/rfc3986
 * @todo Add support for relative URI parsing
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <drissg@gmail.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  lib_net
 */
class Uri extends \Cx\Model\Base\EntityBase {

    /**
     * @var string Regular expression to split URIs from https://tools.ietf.org/html/rfc3986#page-50
     */
    const URI_REGEX = '@^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?@';
    
    /**
     * @var int Index of the above REGEX for scheme part
     */
    const URI_REGEX_INDEX_SCHEME = 2;
    
    /**
     * @var int Index of the above REGEX for authority part
     */
    const URI_REGEX_INDEX_AUTHORITY = 4;
    
    /**
     * @var int Index of the above REGEX for path part
     */
    const URI_REGEX_INDEX_PATH = 5;
    
    /**
     * @var int Index of the above REGEX for query part
     */
    const URI_REGEX_INDEX_QUERY = 7;
    
    /**
     * @var int Index of the above REGEX for fragment part
     */
    const URI_REGEX_INDEX_FRAGMENT = 9;
    
    /**
     * @var string Delimiter for path parts if there's an authority section
     */
    const PATH_DELIMITER_WITH_AUTHORITY = '/';
    
    /**
     * @var string Delimiter for path parts if there's no authority section
     */
    const PATH_DELIMITER_WITHOUT_AUTHORITY = ':';
    
    /**
     * @var string Delimiter for scheme if there's an authority section
     */
    const SCHEME_DELIMITER_WITH_AUTHORITY = '://';
    
    /**
     * @var string Delimiter for scheme if there's no authority section
     */
    const SCHEME_DELIMITER_WITHOUT_AUTHORITY = ':';
    
    /**
     * @var string Delimiter for query section
     */
    const QUERY_DELIMITER = '?';
    
    /**
     * @var string Delimiter for fragment section
     */
    const FRAGMENT_DELIMITER = '#';
    
    /**
     * @var string Currently used delimitier to separate path sub-parts
     */
    protected $pathDelimiter;
    
    /**
     * @var string Scheme part
     */
    protected $scheme;
    
    /**
     * @var string Authority part
     */
    protected $authority;
    
    /**
     * @var array Path parts
     */
    protected $path;
    
    /**
     * @var string Query part
     */
    protected $query = '';
    
    /**
     * @var string Fragment part
     */
    protected $fragment = '';
    
    /**
     * Creates a new Uri instance
     * @param string $stringUri URI as string
     */
    public function __construct($stringUri) {
        $matches = array();
        preg_match(static::URI_REGEX, $stringUri, $matches);
        $this->setScheme($matches[static::URI_REGEX_INDEX_SCHEME]);
        $this->setAuthority($matches[static::URI_REGEX_INDEX_AUTHORITY]);
        $this->setPath($matches[static::URI_REGEX_INDEX_PATH]);
        if (isset($matches[static::URI_REGEX_INDEX_QUERY])) {
            $this->setQuery($matches[static::URI_REGEX_INDEX_QUERY]);
        }
        if (isset($matches[static::URI_REGEX_INDEX_FRAGMENT])) {
            $this->setFragment($matches[static::URI_REGEX_INDEX_FRAGMENT]);
        }
    }
    
    /**
     * Returns Uri as string
     * @return string Uri as string
     */
    public function __toString() {
        // for URI's like urn:example:mammal:monotreme:echidna
        if (!$this->hasAuthority()) {
            return $this->getScheme() .
                static::SCHEME_DELIMITER_WITHOUT_AUTHORITY . $this->getPath();
        }
        
        // for all others
        $stringUri = $this->getScheme() .
            static::SCHEME_DELIMITER_WITH_AUTHORITY . $this->getAuthority() .
            $this->getPath();
        
        if ($this->hasQuery()) {
            $stringUri .= static::QUERY_DELIMITER . $this->getQuery();
        }
        if ($this->hasFragment()) {
            $stringUri .= static::FRAGMENT_DELIMITER . $this->getFragment();
        }
        return $stringUri;
    }
    
    /***********************/
    /* SETTERS AND GETTERS */
    /***********************/
    
    /**
     * Returns the current path delimiter
     * @return string Path delimiter
     */
    public function getPathDelimiter() {
        return $this->pathDelimiter;
    }
    
    /**
     * Sets the current path delimiter
     * @param string $pathDelimiter New path delimiter
     */
    public function setPathDelimiter($pathDelimiter) {
        $this->pathDelimiter = $pathDelimiter;
    }
    
    /**
     * Returns the current scheme
     * @return string Scheme
     */
    public function getScheme() {
        return $this->scheme;
    }
    
    /**
     * Sets the current scheme
     * @param string $scheme New scheme
     */
    public function setScheme($scheme) {
        $this->scheme = $scheme;
    }
    
    /**
     * Tells wheter this Uri has an authority section
     * @return boolean True if there's an authority section, false otherwise
     */
    public function hasAuthority() {
        return !empty($this->authority);
    }
    
    /**
     * Returns the current authority section
     * @return string Authority section
     */
    public function getAuthority() {
        return $this->authority;
    }
    
    /**
     * Sets the current authority
     * @param string $authority New authority
     */
    public function setAuthority($authority) {
        $this->authority = $authority;
        if ($this->hasAuthority()) {
            $this->pathDelimiter = static::PATH_DELIMITER_WITH_AUTHORITY;
        } else {
            $this->pathDelimiter = static::PATH_DELIMITER_WITHOUT_AUTHORITY;
        }
    }
    
    /**
     * Returns the current path section
     * @return string Path section
     */
    public function getPath() {
        return $this->getPathDelimiter() . implode($this->getPathDelimiter(), $this->path);
    }
    
    /**
     * Returns the current path section split by path delimiter
     * @return array Path section
     */
    public function getPathParts() {
        return $this->path;
    }
    
    /**
     * Sets the current path
     * @param string $path New path
     */
    public function setPath($path) {
        $this->path = preg_grep('/^$/', explode($this->getPathDelimiter(), $path), PREG_GREP_INVERT);
    }
    
    /**
     * Tells wheter this Uri has a query section
     * @return boolean True if there's a query section, false otherwise
     */
    public function hasQuery() {
        return !empty($this->query);
    }
    
    /**
     * Returns the current query section
     * @return string Query section
     */
    public function getQuery() {
        return $this->query;
    }
    
    /**
     * Sets the current query
     * @param string $query New query
     */
    public function setQuery($query) {
        $this->query = $query;
    }
    
    /**
     * Tells wheter this Uri has a fragment section
     * @return boolean True if there's a fragment section, false otherwise
     */
    public function hasFragment() {
        return !empty($this->fragment);
    }
    
    /**
     * Returns the current fragment section
     * @return string Fragment section
     */
    public function getFragment() {
        return $this->fragment;
    }
    
    /**
     * Sets the current fragment
     * @param string $fragment New fragment
     */
    public function setFragment($fragment) {
        $this->fragment = $fragment;
    }
    
    /**
     * Returns the string representation for this URI
     * @see __toString()
     * @todo implement a (descendant) method for relative URI/Ls
     * @return string String representation for this URI
     */
    public function toString() {
        return (string) $this;
    }
}

