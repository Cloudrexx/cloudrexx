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
 * Representation of an ESI processor
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  lib_reverseproxy
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */

namespace Cx\Lib\ReverseProxy\Model\Entity;

/**
 * Representation of an ESI processor
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Michael Ritter <michael.ritter@comvation.com>
 * @package     cloudrexx
 * @subpackage  lib_reverseproxy
 * @link        http://www.cloudrexx.com/ cloudrexx homepage
 * @since       v5.0.0
 */
class SsiProcessorEsi extends SsiProcessor {
    
    /**
     * Sets the parseMode for ESI parsing
     */
    public function __construct() {
        $this->parseMode = 'esi';
        parent::__construct();
    }
    
    /**
     * Parses randomized include code
     * @param \HTML_Template_Sigma $template Template to parse
     * @param array $urls List of URLs to get random include tag for
     * @param int $count (optional) Number of unique random entries to parse
     */
    protected function parseRandomizedIncludeCode($template, $urls, $count = 1) {
        foreach ($urls as $i=>$url) {
            $template->setVariable('CONTENT', $url);
            $block = 'content';
            if ($i == 0) {
                $block = 'first_content';
            }
            $template->parse($block);
        }
        for ($i = 0; $i < $count; $i++) {
            $template->touchBlock('randomized_list_entry');
            $template->parse('randomized_list_entry');
        }
    }
}

