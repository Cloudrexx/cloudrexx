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
 * LinkSanitizer
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core
 */

/**
 * This class replaces any links from Cloudrexx < 3.0 on the fly.
 * Handles the [[NODE_<ID>_<LANGID>]] placeholders.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core
 */
class LinkSanitizer {
    const ATTRIBUTE_AND_OPEN_QUOTE = 1;
    const FILE_PATH                = 3;
    const CLOSE_QUOTE              = 4;

    protected $cx;
    protected $offset;
    protected $content;

    /**
     * @param string $offset the path offset to prepend, e.g. '/' or '/cms/'
     */
    public function __construct($cx, $offset, &$content) {
        $this->cx = $cx;
        $this->content = &$content;
        $this->offset  = $offset;
    }

    /**
     * Calculates and returns the content with all replacements done.
     */
    public function replace() {
        $content = preg_replace_callback("/
            (
                # match all SRC and HREF attributes
                \s+(src|href|action)\s*=\s*['\"]
                |
                # or match all CSS @import statements
                @import\s+url\s*\(
            )

            # but only those who's values don't start with a slash..
            (?=[^\/])

            # ..and neither start with a SSI-tag
            (?!<!--\#[a-z]+\s+)

            # ..and neither start with a ESI-tag
            (?!<esi:)

            # ..and neither start with a protocol (http:, ftp:, javascript:, mailto:, etc)
            (?![a-zA-Z]+:)

            # ..and neither start with an ampersand followed by a sharp and end with a semicolon (which would indicate that the url contains html codes for ascii characters)
            (?!&\#\d+;)

            # ..and neither start with a sharp
            (?!\#)

            # ..and neither start with a backslash which would indicate that the url lies within some javascript code
            (?!\\\)

            # match file path and closing quote
            ([^'\"]*)(['\"])
        /x", array($this, 'getPath'), $this->content);

        if (!empty($_GET['preview']) || (isset($_GET['appview']) && ($_GET['appview'] == 1))) {
            $content = preg_replace_callback("/
                (\<(?:a|form|iframe)[^>]*?\s+(?:href|action|src)\s*=\s*)
                (['\"])
                (?!\#)
                ((?![a-zA-Z]+?:|\\\\).+?)
                \\2
                ([^>]*\>)
            /x", array($this, 'appendParameters'), $content);
        }

        return $content;
    }

    /**
     * Returns the created path by the given array.
     *
     * @param   array   $matches
     * @return  string  created path
     */
    private function getPath($matches) {
        // The Shop JS Cart escapes pathes because he loads it via JavaScript.
        // For this reason, we replace escaped slashes by slashes.
        $matches[\LinkSanitizer::FILE_PATH] = str_replace('\\/', '/', $matches[\LinkSanitizer::FILE_PATH]);

        // fix empty urls like empty form-action tags
        if (empty($matches[\LinkSanitizer::FILE_PATH])) {
            return $matches[\LinkSanitizer::ATTRIBUTE_AND_OPEN_QUOTE] .
            $this->cx->getRequest()->getUrl() .
            $matches[\LinkSanitizer::CLOSE_QUOTE];
        }
        $testPath = explode('?', $matches[\LinkSanitizer::FILE_PATH], 2);
        if ($testPath[0] == 'index.php' || $testPath[0] == '' || $testPath[0] == './') {
            $ret = $this->cx->getWebsiteOffsetPath();
            if (\Env::get('cx')->getMode() == \Cx\Core\Core\Controller\Cx::MODE_BACKEND) {
                $ret .= \Cx\Core\Core\Controller\Cx::instanciate()->getBackendFolderName();
            }
            $ret .= '/';
            if (isset($testPath[1])) {
                $args = preg_split('/&(amp;)?/', $testPath[1]);
                $params = array();
                foreach ($args as $arg) {
                    $split = explode('=', $arg, 2);
                    $params[$split[0]] = $split[1];
                }
                // frontend case
                if (isset($params['section'])) {
                    $cmd = '';
                    if (isset($params['cmd'])) {
                        $cmd = $params['cmd'];
                        unset($params['cmd']);
                    }
                    $ret = \Cx\Core\Routing\Url::fromModuleAndCmd($params['section'], $cmd);
                    unset($params['section']);
                    $ret->setParams($params);
                    return $matches[\LinkSanitizer::ATTRIBUTE_AND_OPEN_QUOTE] .
                    $ret .
                    $matches[\LinkSanitizer::CLOSE_QUOTE];

                // backend case
                } else if (isset($params['cmd'])) {
                    $ret .= $params['cmd'];
                    unset($params['cmd']);
                    if (isset($params['act'])) {
                        $ret .= '/' . $params['act'];
                        unset($params['act']);
                    }
                }
                if (count($params)) {
                    array_walk(
                        $params,
                        function(&$value, $key) {
                            $value = $key . '=' . $value;
                        }
                    );
                    $ret .= '?' . implode('&', $params);
                }
            }
            return $matches[\LinkSanitizer::ATTRIBUTE_AND_OPEN_QUOTE] .
            $ret .
            $matches[\LinkSanitizer::CLOSE_QUOTE];
        } else if (
            $localFile = $this->cx->getClassLoader()->getWebFilePath(
                $this->cx->getCodeBaseDocumentRootPath() . '/' .
                $matches[\LinkSanitizer::FILE_PATH]
            )
        ) {
            // this is an existing file, do not add virtual language dir
            return $matches[\LinkSanitizer::ATTRIBUTE_AND_OPEN_QUOTE] .
            $localFile . (isset($testPath[1]) ? '?' . $testPath[1] : '') .
            $matches[\LinkSanitizer::CLOSE_QUOTE];
        } else {
            // this is a link to a page, add virtual language dir
            return $matches[\LinkSanitizer::ATTRIBUTE_AND_OPEN_QUOTE] .
            $this->offset .
            $matches[\LinkSanitizer::FILE_PATH] .
            $matches[\LinkSanitizer::CLOSE_QUOTE];
        }
    }

    /**
     * Checks if a file, whose name contains parameters, exists.
     * Exception for PHP files.
     *
     * @access  private
     * @param   string   $filePath
     * @return  bool     true if the file exists, otherwise false
     */
    private function fileExists($filePath) {
        if (\Env::get('ClassLoader')->getFilePath($filePath)) {
            return true;
        }

        $arrUrl = parse_url($filePath);
        if (!empty($arrUrl['path'])
            && substr($arrUrl['path'], -4) !== '.php'
            && \Env::get('ClassLoader')->getFilePath($arrUrl['path'])) {
            return true;
        }

        return false;
    }

    /**
     * Callback method for appending preview and appview parameter to href and action attributes.
     *
     * @access  private
     * @param   array       $matches    regex matches
     * @return  string                  replacement string
     */
    private function appendParameters($matches) {
        $before = $matches[1];
        $quote  = $matches[2];
        $value  = $matches[3];
        $after  = $matches[4];

        if (strpos($value, '?') !== false) {
            list($path, $query) = explode('?', $value, 2);
            // TODO: this is basically wrong as question marks are valid
            // characters within a query string. See rfc for reference:
            // https://tools.ietf.org/html/rfc3986#section-3.4
            // However, this is probably a workaround to fix javascript
            // code, that wrongly produces infinite redirect loops in
            // combination with the 'preview' URL argument.
            // See CLX-1780
            $query = str_replace('?', '&', $query);
            $query = \Cx\Core\Routing\Url::params2array($query);
        } else {
            $path = $value;
            $query = array();
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if (!empty($extension) && ($extension != 'php')) {
            return $matches[0];
        }

        if (!empty($_GET['preview']) && !isset($query['preview'])) {
            $query['preview'] = $_GET['preview'];
        }
        if (!empty($_GET['templateEditor']) && !isset($query['templateEditor'])) {
            $query['templateEditor'] = $_GET['templateEditor'];
        }
        if ((isset($_GET['appview']) && ($_GET['appview'] == 1)) && !isset($query['appview'])) {
            $query['appview'] = $_GET['appview'];
        }

        $query = \Cx\Core\Routing\Url::array2params($query);

        // replace & with &amp; but only & (not followed by amp;)
        $query = preg_replace('/&(?!amp;)/', '&amp;', $query);
        return $before.$quote.$path.'?'.$query.$quote.$after;
    }

}
