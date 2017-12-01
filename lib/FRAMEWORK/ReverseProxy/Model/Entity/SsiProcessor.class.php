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
 * Abstract representation of an SSI processor
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  lib_reverseproxy
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */

namespace Cx\Lib\ReverseProxy\Model\Entity;

/**
 * Abstract representation of an SSI processor
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  lib_reverseproxy
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */
abstract class SsiProcessor {
    
    /**
     * @var string SsiProcessor name (currently either 'esi' or 'ssi'), case insensitive
     */
    protected $parseMode;
    
    /**
     * @var string Library base dir (defaults to ../../ (relative to this file's path))
     */
    protected $dirname;
    
    /**
     * Instanciates this SsiProcessor
     * Sets $this->dirname
     */
    public function __construct() {
        $this->dirname = dirname(dirname(dirname(__FILE__)));
    }
    
    /**
     * Gets the ESI/SSI include code for an URL
     * @param string $url URL to get include tag for
     * @return string ESI/SSI include tag
     */
    public function getIncludeCode($url) {
        $template = $this->getTemplateFile('IncludeTag');
        $template->setVariable('INCLUDE_FILE', $url);
        $includeCode = $template->get();

        // trim trailing new line
        // TODO: this should be a generic feature of \Cx\Core\Html\Sigma
        //       to enforce the guideline that all files should contain
        //       a trailing new line
        $includeCode = preg_replace('/\n$/', '', $includeCode);

        return $includeCode;
    }
    
    /**
     * Gets the ESI/SSI random include code for a set of URLs
     * @param array $urls List of URLs to get random include tag for
     * @param int $count (optional) Number of unique random entries to parse
     * @return string ESI/SSI random include tag
     */
    public function getRandomizedIncludeCode($urls, $count = 1) {
        $template = $this->getTemplateFile('RandomIncludeTag');
        $this->parseRandomizedIncludeCode($template, $urls, $count);
        return $template->get();
    }
    
    /**
     * Loads a template file based on $this->parseMode and $this->dirname
     * @param string $filename Template base filename
     * @return \HTML_Template_Sigma Requested template
     */
    protected function getTemplateFile($filename) {
        $template = new \HTML_Template_Sigma($this->dirname . '/View/Template/Global');
        $template->loadTemplateFile($filename . strtoupper($this->parseMode) . '.html');
        return $template;
    }
    
    /**
     * Parses randomized include code
     * @param \HTML_Template_Sigma $template Template to parse
     * @param array $urls List of URLs to get random include tag for
     * @param int $count (optional) Number of unique random entries to parse
     */
    protected abstract function parseRandomizedIncludeCode($template, $urls, $count = 1);
}

