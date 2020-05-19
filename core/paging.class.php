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
 * Paging
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      Reto Kohli <reto.kohli@comvation.com> (Rewritten statically)
 * @version     3.0.0
 * @package     cloudrexx
 * @subpackage  core
 */

if (stristr(__FILE__, $_SERVER['PHP_SELF'])) {
    Header("Location: index.php");
    die();
}

/**
 * Creates the paging
 *
 * @package     cloudrexx
 * @subpackage  core
 * @version     3.0.0
 * @author      Reto Kohli <reto.kohli@comvation.com> (Rewritten statically)
 */
class Paging
{
    /**
     * Returs a string representing the complete paging HTML code for the
     * current page
     * @author  Reto Kohli <reto.kohli@comvation.com> (Rewritten statically)
     * @access  public
     * @global  array     $_CONFIG        Configuration
     * @global  array     $_CORELANG      Core language
     * @param   string    $uri_parameter  Optional additional URI parameters,
     *                                    *MUST* start with an URI encoded
     *                                    ampersand (&amp;).  By reference
     * @param   string    $paging_text    The text to be put in front of the
     *                                    paging
     * @param   integer   $numof_rows     The number of rows available
     * @param   integer   $results_per_page   The optional maximum number of
     *                                    rows to be shown on a single page.
     *                                    Defaults to the corePagingLimit
     *                                    setting.
     * @param   boolean   $showeverytime  If true, the paging is shown even if
     *                                    $numof_rows is less than
     *                                    $results_per_page
     * @param   integer   $position       The optional starting position
     *                                    offset.  Defaults to null
     * @param   string    $parameter_name The optional name for the URI
     *                                    parameter.  Will be determined
     *                                    automatically if empty.
     * @return  string                    HTML code for the paging
     */
    static function get(&$uri_parameter, $paging_text, $numof_rows,
        $results_per_page=0, $showeverytime=false,
        $position=null, $parameter_name=null
    ) {
        global $_CONFIG, $_CORELANG;

        $headIncludes = array();
        $csrf = '';

        if (empty($results_per_page)) $results_per_page = intval($_CONFIG['corePagingLimit']);
        if ($numof_rows <= $results_per_page && !$showeverytime) return '';
        $parameter_name = self::getParametername($parameter_name);
        if (!isset($position)) $position = self::getPosition($parameter_name);

        // Fix illegal values:
        // The position must be in the range [0 .. numof_rows - 1].
        // If it's outside this range, reset it
        if ($position < 0 || $position >= $numof_rows) $position = 0;
        // Total number of pages: [1 .. n]
        $numof_pages = ceil($numof_rows / $results_per_page);
        // Current page number: [1 .. numof_pages]
        $page_number = 1 + intval($position / $results_per_page);
        $corr_value = $results_per_page;
        if ($numof_rows % $results_per_page) {
            $corr_value = $numof_rows % $results_per_page;
        }

        // Init CSRF token to be used in the paging-links.
        // Note: the CSRF token has to be added to the urls manually as the
        //       URL class does automatically remove the CSRF tokens as well
        //       as the LinkSanitizer does not add the CSRF token to absolute
        //       URLs.
        if (\Cx\Core\Csrf\Controller\Csrf::param()) {
            $csrf = '&' . \Cx\Core\Csrf\Controller\Csrf::param();
        }

        // remove all parameters otherwise the url object has parameters like &act=add
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $requestUrl = clone $cx->getRequest()->getUrl();
        $currentParams = $requestUrl->getParamArray();
        $requestUrl->removeAllParams();
        if (isset($currentParams['section'])) {
            $requestUrl->setParam('section', $currentParams['section']);
        }
        $requestUrl->setParams($uri_parameter);

        $firstUrl = clone $requestUrl;
        $firstUrl->setParam($parameter_name, 0);
        $lastUrl = clone $requestUrl;
        $lastUrl->setParam($parameter_name, ($numof_rows - $corr_value));

        // Set up the base navigation entries
        $array_paging = array(
            'first' => '<a class="pagingFirst" href="'.
                Cx\Core\Routing\Url::encode_amp($firstUrl->toString().$csrf).'" rel="nofollow">',
            'last'  => '<a class="pagingLast" href="'.
                Cx\Core\Routing\Url::encode_amp($lastUrl->toString().$csrf).'" rel="nofollow">',
            'total' => $numof_rows,
            'lower' => ($numof_rows ? $position + 1 : 0),
            'upper' => $numof_rows,
        );
        if ($position + $results_per_page < $numof_rows) {
            $array_paging['upper'] = $position + $results_per_page;
        }
        // Note:  previous/next link are currently unused.
        if ($position != 0) {
            $previousUrl = clone $requestUrl;
            $previousUrl->setParam($parameter_name, ($position - $results_per_page));
            $array_paging['previous_link'] =
                '<a href="'.Cx\Core\Routing\Url::encode_amp($previousUrl.$csrf).'">';

            $link = new \Cx\Core\Html\Model\Entity\HtmlElement('link');
            $link->setAttribute('href', $previousUrl->toString());
            $link->setAttribute('rel', 'prev');
            $headIncludes[] = $link;
        }
        if (($numof_rows - $position) > $results_per_page) {
            $int_new_position = $position + $results_per_page;
            $nextUrl = clone $requestUrl;
            $nextUrl->setParam($parameter_name, $int_new_position);
            $array_paging['next_link'] =
                '<a href="'.Cx\Core\Routing\Url::encode_amp($nextUrl.$csrf).'">';

            $link = new \Cx\Core\Html\Model\Entity\HtmlElement('link');
            $link->setAttribute('href', $nextUrl->toString());
            $link->setAttribute('rel', 'next');
            $headIncludes[] = $link;
        }

        // TODO: This is a temporary solution for setting HEAD_INCLUDES.
        //       The proper and correct way will by handled by the
        //       upcoming implementation of the response object.
        if ($headIncludes) {
            \Cx\Core\Core\Controller\Cx::instanciate()->getTemplate()->setVariable(
                'HEAD_INCLUDES', join("\n", $headIncludes)
            );
        }

        // Add single pages, indexed by page numbers [1 .. numof_pages]
        for ($i = 1; $i <= $numof_pages; ++$i) {
            if ($i == $page_number) {
                $array_paging[$i] =
                    '<b class="pagingPage'.$i.'">'.$i.'</b>';
            } else {
                $pageUrl = clone $requestUrl;
                $pageUrl->setParam($parameter_name, (($i-1) * $results_per_page));
                $array_paging[$i] =
                    '<a class="pagingPage'.$i.'" href="'.
                    Cx\Core\Routing\Url::encode_amp($pageUrl->toString().$csrf).'">'.$i.'</a>';
            }
        }
        $paging =
            $paging_text.
            '&nbsp;<span class="pagingLower">'.$array_paging['lower'].
            '</span>&nbsp;'.$_CORELANG['TXT_PAGING_TO'].
            '&nbsp;<span class="pagingUpper">'.$array_paging['upper'].
            '</span>&nbsp;'.$_CORELANG['TXT_PAGING_OUT_OF'].
            '&nbsp;<span class="pagingTotal">'.$array_paging['total'].
            '</span>';
        if ($numof_pages) $paging .=
            '&nbsp;&nbsp;[&nbsp;'.$array_paging['first'].
            '&lt;&lt;</a>&nbsp;&nbsp;'.
            '<span class="pagingPages">';
        if ($page_number > 3) $paging .= $array_paging[$page_number-3].'&nbsp;';
        if ($page_number > 2) $paging .= $array_paging[$page_number-2].'&nbsp;';
        if ($page_number > 1) $paging .= $array_paging[$page_number-1].'&nbsp;';
        if ($numof_pages) $paging .= $array_paging[$page_number].'&nbsp;';
        if ($page_number < $numof_pages-0) $paging .= $array_paging[$page_number+1].'&nbsp;';
        if ($page_number < $numof_pages-1) $paging .= $array_paging[$page_number+2].'&nbsp;';
        if ($page_number < $numof_pages-2) $paging .= $array_paging[$page_number+3].'&nbsp;';
        if ($numof_pages) $paging .=
            '</span>&nbsp;'.$array_paging['last'].'&gt;&gt;</a>&nbsp;]';
        return $paging;
    }


    /**
     * Returns the current offset
     *
     * If the parameter 'pos' is present in the request, it overrides
     * the value stored in the session, if any.  Defaults to zero.
     * @param   string    $parameter_name   The optional name of the position
     *                                      offset parameter
     * @return  integer                     The position offset
     */
    static function getPosition($parameter_name=null)
    {
        $parameter_name = self::getParametername($parameter_name);//'pos';
        if (\Cx\Core\Core\Controller\Cx::instanciate()->getMode() == \Cx\Core\Core\Controller\Cx::MODE_FRONTEND) {
            if (!isset($_REQUEST[$parameter_name])) {
                return 0;
            }

            return intval($_REQUEST[$parameter_name]);
        }

        if (!isset($_SESSION['paging'])) {
            $_SESSION['paging'] = array();
        }
        if (!isset($_SESSION['paging'][$parameter_name]))
            $_SESSION['paging'][$parameter_name] = 0;
        if (isset($_REQUEST[$parameter_name])) {
            $position = intval($_REQUEST[$parameter_name]);
            unset($_REQUEST[$parameter_name]);
            $_SESSION['paging'][$parameter_name] = $position;
        }
        return $_SESSION['paging'][$parameter_name];
    }


    /**
     * Resets the paging offset to zero
     *
     * Call this if your query results in less records than the offset.
     * @param   string    $parameter_name   The optional name of the position
     *                                      offset parameter
     */
    static function reset($parameter_name=null)
    {
        $parameter_name = self::getParametername($parameter_name);//'pos';
        $_SESSION['paging'][$parameter_name] = 0;
        unset($_REQUEST[$parameter_name]);
    }


    static function getParametername($parameterName = null)
    {
        if (empty($parameterName)) {
            $parameterName = self::generateParameterName();
        }

        return self::sanitizeParameterName($parameterName);
    }

    private static function generateParameterName()
    {
/*
        die(nl2br(var_export(debug_backtrace(
                false //true
// These are for PHP v5.3.6+:
// Flags
//                  DEBUG_BACKTRACE_PROVIDE_OBJECT
//                | DEBUG_BACKTRACE_IGNORE_ARGS,
// Limit
//                , 0
            ),
            true)));
*/
        $arrStack = debug_backtrace();
          $i = 0;
        while ($arrStack[$i]['class'] == 'Paging') {
            ++$i;
        }
        $arrStack = $arrStack[$i];
//        die(nl2br(var_export($arrStack)));
        $name = $arrStack['class'].'_'.$arrStack['function'];
        return $name;
    }

    /**
     * Ensure that the used parameter name complies with the session
     * restrictions defined for variable keys, as the parameter name
     * is being used as a sesison-variable-key.
     * @param string $parameterName The name of the session-variable-key used to store the current paging position.
     * @return string $parameterName The sanitized session-variable-key.
     */
    private static function sanitizeParameterName($parameterName)
    {
        // Important: As the parameter name is used as a session-variable-key,
        // it must not exceed the allowed session-variable-key-length.
        // Therefore, if required, the parameter name is hashed and cut to the
        // maximum allowed session-variable-key-length.
        if (strlen($parameterName) > \Cx\Core\Session\Model\Entity\Session::getVariableKeyMaxLength()) {
            $parameterName = substr(md5($parameterName), 0, \Cx\Core\Session\Model\Entity\Session::getVariableKeyMaxLength());
        }

        return $parameterName;
    }
}
