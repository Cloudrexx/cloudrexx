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
 * Csrf Class
 * Protect against Csrf attacks
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      David Vogt <david.vogt@comvation.com>
 * @since       2.1.3
 * @package     cloudrexx
 * @subpackage  core_csrf
 */

namespace Cx\Core\Csrf\Controller;
/**
 * This class provides protection against Csrf attacks.
 *
 * call Csrf::add_code() if the page contains vulnerable
 * links and forms, and use Csrf::check_code() to kill the
 * request if there's an invalid code.
 *
 * This class expects that the session has been set up
 * correctly and can be used through $_SESSION.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      David Vogt <david.vogt@comvation.com>
 * @since       2.1.3
 * @package     cloudrexx
 * @subpackage  core_csrf
 */
class Csrf {

    /**
     * This variable defines how many times a given code
     * is accepted as valid. We need this in case the user
     * opens a new tab in the admin panel.
     *
     * A high value increases usability, a low value
     * increases security. Tough call!
     */
    static $validity_count = 15;

    /**
     * This number defines how much any known code's validity
     * count is reduced at every check, even if another code
     * was given by the form/link. This way, we can expire
     * codes that are not in use anymore, and so keep the
     * session smaller. With a value of 0.5 and a validity_count
     * of 5, this means that after 10 requests, an unused
     * key will be invalid.
     */
    static $unused_decrease = 0.5;

    /**
     * This number defines how much to decrease a code's
     * validity each time it's checked. Example: if
     * validity_count is 5 and active_decrease is 1,
     * a code is valid four times, meaning a user can
     * open four tabs from the same page before the
     * request is denied.
     */
    static $active_decrease = 1;

    private static $already_added_code = false;
    private static $already_checked    = false;

    private static $sesskey = '__csrf_data__';
    private static $formkey = 'csrf';

    private static $current_code = NULL;

    private static $frontend_mode = false;


    private static function __get_code()
    {
        // don't generate a CSRF token in case the user is not signed-in
        if (!self::__is_logged_in()) return false;

        if (!empty(self::$current_code)) {
            return self::$current_code;
        }
        self::$current_code = base64_encode(rand(100000000, 999999999));
        self::$current_code = preg_replace('#[\'"=%]#', '_', self::$current_code);
        self::__setkey(self::$current_code, self::$validity_count);
        return self::$current_code;
    }


    /**
     * An utility function to patch URLs specifically in
     * redirect (and possibly other) headers. Expects a
     * string in the form "header-name: ...." and returns
     * it, modified to contain the CSRF protection parameter.
     *
     * Example: __enhance_header('Location: index.php')
     * --> "Location: index.php?__csrf__=xxxxx"
     */
    private static function __enhance_header($header)
    {
        if (!self::__is_logged_in()) return $header;
        if (self::$frontend_mode) return $header;
        $result = array();
        if (!preg_match('#^(\w+):\s*(.*)$#i', $header, $result)) {
            // don't know what to do with it
            return $header;
        }
        $hdr = $result[1];
        $url = self::enhanceURI($result[2]);
        return "$hdr: $url";
    }


    /**
     * Acts as a replacement for header() calls that handle URLs.
     * Only use it for headers in the form "Foo: an_url", for
     * instance "Location: index.php?foo=bar".
     */
    public static function header($header, $replace = true, $httpResponseCode = null)
    {
        \DBG::msg('\Cx\Core\Csrf\Controller\Csrf::header(): Set header "' . $header . '"');
        \DBG::stack();
        header(self::__enhance_header($header), $replace, $httpResponseCode);
    }

    /**
     * Redirect
     *
     * This function redirects the client. This is done by issuing
     * a "Location" header and exiting if wanted.  If you set $rfc2616 to true
     * HTTP will output a hypertext note with the location of the redirect.
     *
     * @static
     * @access  public
     * @return  mixed   Returns true on succes (or exits) or false if headers
     *                  have already been sent.
     * @param   string  $url URL where the redirect should go to.
     * @param   bool    $exit Whether to exit immediately after redirection.
     * @param   bool    $rfc2616 Wheter to output a hypertext note where we're
     *                  redirecting to (Redirecting to <a href="...">...</a>.)
     */
    public static function redirect($url, $exit = true, $rfc2616 = false) {
        if (headers_sent()) {
            return false;
        }

        $url = \Cx\Core\Routing\Url::fromMagic($url);
        $url = $url->toString(); // use absolute url

        self::header('Location: '. $url);

        if (    $rfc2616 && isset($_SERVER['REQUEST_METHOD']) &&
                $_SERVER['REQUEST_METHOD'] != 'HEAD') {
            printf('Redirecting to: <a href="%s">%s</a>.', $url, $url);
        }
        if ($exit) {
            exit;
        }
        return true;
    }


    /**
     * Adds the CSRF protection code to the URI specified by $uri.
     *
     * Note: This adds a simple ampersand (&), not the HTML entity &amp;.
     * Thus, it is only suitable for modifying header() parameters and
     * URIs within javascript.  For URIs to be embedded into HTML,
     * you *SHOULD* htmlentities() it first!
     */
    public static function enhanceURI($uri)
    {
        if (self::$frontend_mode) return $uri;

        $key = self::$formkey;
        $val = self::__get_code();
        $uri .= (strstr($uri, '?') ? '&' : '?')."$key=$val";
        return $uri;
    }


    /**
     * Call this to add a CSRF protection code to all the
     * forms and links on the generated page. Note that
     * you don't need to pass any content, and nothing is
     * returned - this function uses PHP to change it's
     * output so as to insert the data.
     *
     * Note: output_add_rewrite_var() used in here does a really bad job
     * on your URIs within the HTML.  It adds parameters without considering
     * whether it should use '&' or '&amp;'.  This results in invalid HTML!
     */
    public static function add_code()
    {
        if (!self::__is_logged_in()) return;
        if (self::$already_added_code) return;
        // do not add CSRF code in case current request is an AJAX request.  They're secure
        // by definition and also, they're much more delicate in
        // what can be returned - and they usually exceed the
        // request amount limit pretty quickly (see active_decrease etc)
        if (self::__is_ajax()) {
            return;
        }
        self::$already_added_code = true;
        $code = self::__get_code();
        output_add_rewrite_var(self::$formkey, $code);
    }


    /**
     * Adds a placeholder for the CSRF code to the given template.
     * This is so you can easily patch javascript code that handles
     * URLs, as this cannot be done by add_code().
     * @param   \Cx\Core\Html\Sigma     $tpl    Template object
     */
    public static function add_placeholder($tpl)
    {
        if (!self::__is_logged_in()) return true;
        // do not add placeholder in case current request is an AJAX request.  They're secure
        // by definition and also, they're much more delicate in
        // what can be returned - and they usually exceed the
        // request amount limit pretty quickly (see active_decrease etc)
        if (self::__is_ajax()) {
            return;
        }

        if (!is_object($tpl)) {
            \DBG::msg("self::add_placeholder(): fix this call, that ain't a template object! (Stack follows)");
            \DBG::stack();
        }
        $code = self::__get_code();
        $tpl->setGlobalVariable(array(
            "CSRF_PARAM" => self::param(),
            "CSRF_KEY"   => "$code",
        ));
        return true;
    }


    /**
     * Returns the anti-CSRF code's form key.
     * You can build your own URLs together
     * with \Cx\Core\Csrf\Controller\Csrf::code()
     */
    public static function key()
    {
        return self::$formkey;
    }


    /**
     * Returns the anti-CSRF code for the current
     * request. You can build your own URLs together
     * with \Cx\Core\Csrf\Controller\Csrf::key()
     */
    public static function code()
    {
        return self::__get_code();
    }


    /**
     * Returns a key/value pair ready to use in an URL.
     */
    public static function param()
    {
        if (!self::__is_logged_in()) return '';
        return self::key().'='.self::code();
    }


    /**
     * Call this if you need to protect critical work.
     * This function will stop the request if it cannot
     * find a valid anti-CSRF code in the request.
     */
    public static function check_code()
    {
        if (!self::__is_logged_in()) return;
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && self::$frontend_mode) return;
        if (self::$already_checked) return;
        self::$already_checked = true;
        // do not check CSRF code in case current request is an AJAX request.  They're secure
        // by definition and also, they're much more delicate in
        // what can be returned - and they usually exceed the
        // request amount limit pretty quickly (see active_decrease etc)
        if (self::__is_ajax()) {
            return;
        }

        $code = '';
        $method = $_SERVER['REQUEST_METHOD']; 

        switch ($method) {
            case 'GET':
                $code = isset($_GET [self::$formkey]) ? $_GET[self::$formkey] : '';
                break;

            case 'POST':
                $code = isset($_POST[self::$formkey]) ? $_POST[self::$formkey] : '';
                break;

            default:
                break;
        }

        self::__cleanup();
        if (! self::__getkey($code)) {
            self::__kill();
        } else {
            self::__reduce($code, $method);
            if (self::__getkey($code) < 0) {
                self::__kill();
            }
        }
    }


    private static function __kill()
    {
        global $_CORELANG;

        $data = ($_SERVER['REQUEST_METHOD'] == 'GET' ? $_GET : $_POST);
        self::add_code();
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $tpl = new \Cx\Core\Html\Sigma($cx->getCodeBaseCorePath() . '/Csrf/View/Template/Generic/');
        $tpl->setErrorHandling(PEAR_ERROR_DIE);
        $tpl->loadTemplateFile('Warning.html');
        $form = '';
        foreach ($data as $key => $value) {
            if ($key == self::$formkey || $key == 'amp;'.self::$formkey || $key == '__cap') {
                continue;
            }
            // There *MUST NOT* be any form element with a name attribute
            // value of "submit" -- this will break the form's submit() method!
            if ($key == 'submit') {
                continue;
            }
            $form .= self::parseRequestParametersForForm($key, $value);
        }
        $csrfContinue = 'javascript:sendData();';
        $csrfAbort = 'index.php' . (isset($_GET['cmd']) ? '?cmd='.$_GET['cmd'] : '');
        $_CORELANG['TXT_CSRF_DESCR'] = str_replace('%1$s', $csrfContinue . '" tabindex="-1', $_CORELANG['TXT_CSRF_DESCR']);
        $_CORELANG['TXT_CSRF_DESCR'] = str_replace('%2$s', $csrfAbort . '" tabindex="-1', $_CORELANG['TXT_CSRF_DESCR']);
        $action = $_SERVER['REQUEST_URI'];
        $tpl->setGlobalVariable(array(
            'TXT_CSRF_TITLE'    => $_CORELANG['TXT_CSRF_TITLE'],
            'TXT_CSRF_DESCR'    => $_CORELANG['TXT_CSRF_DESCR'],
            'TXT_CSRF_CONTINUE' => $_CORELANG['TXT_CSRF_CONTINUE'],
            'TXT_CSRF_ABORT'    => $_CORELANG['TXT_CSRF_ABORT'],
            'CSRF_CONTINUE'     => $csrfContinue . '" tabindex="1',
            'CSRF_ABORT'        => $csrfAbort . '" tabindex="2',
            'REQUEST_METHOD'    => strtolower($_SERVER['REQUEST_METHOD']),
            'ACTION'            => $action,
            'FORM_ELEMENTS'     => $form,
            'IMAGES_PATH'       => ASCMS_ADMIN_WEB_PATH.'/images/csrfprotection',
        ));
        $tpl->parse();

        $endcode = $tpl->get();

        // replace links from before contrexx 3
        $ls = new \LinkSanitizer(
            $cx,
            $cx->getCodeBaseOffsetPath() . $cx->getBackendFolderName() . '/',
            $endcode
        );
        $endcode = $ls->replace();

        echo $endcode;
        die();
    }


    private static function parseRequestParametersForForm($key, $value, $arrSubKeys=array())
    {
        $elem = '';
        if (is_array($value)) {
            foreach ($value as $subKey => $subValue) {
                $elem .= self::parseRequestParametersForForm($key, $subValue, array_merge($arrSubKeys, array($subKey)));
            }
        } else {
            $elem = '<input type="hidden" name="_N_" value="_V_" />';
            $elem = str_replace('_N_', self::__formval($key.(!empty($arrSubKeys) ? '['.implode('][', $arrSubKeys).']' : '')),  $elem);
            $elem = str_replace('_V_', self::__formval($value), $elem);
        }
        return $elem;
    }


    private static function __formval($str)
    {
        return htmlspecialchars(contrexx_stripslashes($str), ENT_QUOTES, CONTREXX_CHARSET);
    }

    /**
     * Decrease the validity of the CSRF tokens
     *
     * @param   string  $code   The CSRF token used for this request
     * @param   string  $method The HTTP request method
     */
    private static function __reduce($code, $method)
    {
        foreach ($_SESSION[self::$sesskey] as $key => $value) {
            // invalidate token immediately in case it has been
            // used by a POST request
            if (
                $method == 'POST' &&
                $key == $code
            ) {
                $_SESSION[self::$sesskey][$key] = 0;
                continue;
            }

            // stepwise decrease the validity of the tokens
            // for GET requests
            if ($key == $code) {
                $decreaseValue = self::$active_decrease;
            } else {
                $decreaseValue = self::$unused_decrease;
            }

            $_SESSION[self::$sesskey][$key] -= $decreaseValue;
        }
    }


    private static function __is_ajax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
        || (isset($_SERVER['HTTP_CHECK_CSRF'])
            && $_SERVER['HTTP_CHECK_CSRF'] == 'false');
    }


    private static function __cleanup()
    {
        foreach ($_SESSION[self::$sesskey] as $key => $count) {
            if ($count < 0) {
                unset($_SESSION[self::$sesskey][$key]);
            }
        }
    }


    private static function __getkey($key)
    {
        return !empty($_SESSION[self::$sesskey][$key]);
    }


    private static function __setkey($key, $value)
    {
        if (!isset($_SESSION[self::$sesskey])) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $cx->getComponent('Session')->getSession();
            $_SESSION[self::$sesskey] = array();
        }
        $_SESSION[self::$sesskey][$key] = $value;
    }


    private static function __is_logged_in()
    {
        global $objInit;

        // we need $objInit to be able to determine if the user requested
        // the backend or frontend
        if (!isset($objInit)) {
            return false;
        }

        if ($objInit->mode == 'backend') {
            $backend = true;
        } else {
            $backend = false;
        }

        if (class_exists('FWUser')) {
            $objFWUser = \FWUser::getFWUserObject();
            if ($objFWUser->objUser->login($backend)) {
                return true;
            }
        }
        return false;
    }


    /**
     * Remove the CSRF protection parameter from the query string and referrer
     */
    public static function cleanRequestURI()
    {
// This will remove the parameter from the first position in the query string
// and leave an URI like "index.php&name=value", which is invalid
        //$csrfUrlModifierPattern = '#(?:\&(?:amp\;)?|\?)?'.self::$formkey.'\=[a-zA-Z0-9_]+#';
// Better cut the parameter plus trailing ampersand, if any.
        $csrfUrlModifierPattern = '/'.self::$formkey.'\=[a-zA-Z0-9_]+\&?/';
// This will leave the URI valid, even if it's the last parameter;
// a trailing question mark or ampersand does no harm.
        !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] = preg_replace($csrfUrlModifierPattern, '', $_SERVER['QUERY_STRING'])    : false;
        !empty($_SERVER['REQUEST_URI'])  ? $_SERVER['REQUEST_URI']  = preg_replace($csrfUrlModifierPattern, '', $_SERVER['REQUEST_URI'])     : false;
        !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] = preg_replace($csrfUrlModifierPattern, '', $_SERVER['HTTP_REFERER'])    : false;
        !empty($_SERVER['argv'])         ? $_SERVER['argv']         = preg_grep($csrfUrlModifierPattern, $_SERVER['argv'], PREG_GREP_INVERT) : false;
    }

    public static function setFrontendMode()
    {
        self::$frontend_mode = true;
        @ini_set('url_rewriter.tags', 'area=href,frame=src,iframe=src,input=src,form=,fieldset=');
    }
}

Csrf::cleanRequestURI();
