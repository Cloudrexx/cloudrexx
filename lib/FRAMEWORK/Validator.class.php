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
 * Framework Validator
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.1
 * @package     cloudrexx
 * @subpackage  lib_framework
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Regular Expression for e-mail addresses
 * TKaelin @ 2.0.2: wrote new regex based on http://en.wikipedia.org/wiki/E-mail_address
 * Dave V, @ 2.1.2: re-wrote regex according to http://www.regular-expressions.info/email.html
 * Reto Kohli @ 2.1.4: Fixed e-mail regex for PHP by adding more backslashes for special characters
 * @since   2.0.0
 * @deprecated 3.1.1
 */
define('VALIDATOR_REGEX_EMAIL',
    '[a-zäàáâöôüûñéè0-9!\#\$\%\&\'\*\+\/\=\?\^_\`\{\|\}\~-]+(?:\.[a-zäàáâöôüûñéè0-9!\#\$\%\&\'\*\+\/\=\?\^_\`\{\|\}\~-]+)*@(?:[a-zäàáâöôüûñéè0-9](?:[a-zäàáâöôüûñéè0-9-]*[a-zäàáâöôüûñéè0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?'
);

/**
 * Regular Expression in javascript for e-mail addresses
 * @author  Michael Räss <info@cloudrexx.com>
 * @since  2.2.6
 * @deprecated 3.1.1
 */
define('VALIDATOR_REGEX_EMAIL_JS',
    '^'.VALIDATOR_REGEX_EMAIL.'$'
);

/**
 * Regular Expression for URI protocols
 *
 * Known protocols include HTTP, HTTPS, FTP, and FTPS.
 * @author  Reto Kohli <reto.kohli@comvation.com>
 * @since   2.2.0
 * @deprecated 3.1.1
 */
define('VALIDATOR_REGEX_URI_PROTO',
    '(?:(?:ht|f)tps?\:\/\/)'
);

/**
 * Regular Expression for URIs
 * @author  Reto Kohli <reto.kohli@comvation.com>
 * @since   2.2.0
 * @deprecated 3.1.1
 */
define('VALIDATOR_REGEX_URI',
    VALIDATOR_REGEX_URI_PROTO.
    '?((([\wäàáâöôüûñéè\d-]{1,}\.)+[a-z]{2,})|((?:(?:25[0-5]|2[0-4]\d|[01]\d\d|\d?\d)(?:(\.?\d)\.)) {4}))(?:[\w\d]+)?(\/[\w\d\-\.\?\,\'\/\\\+\&\%\$\#\=\~]*)?'
//  '(https?|ftp)\:\/\/([-a-z0-9.]+)(\/[-a-z0-9+&@#\/%=~_|!:,.;]*)?(\?[-a-z0-9+&@#\/%=~_|!:,.;]*)?'
);

/**
 * Regular Expression in javascript for URIs
 * @author  Michael Räss <info@cloudrexx.com>
 * @since   2.2.6
 * @deprecated 3.1.1
 */
define('VALIDATOR_REGEX_URI_JS',
    '^'.VALIDATOR_REGEX_URI.'$'
);

/**
 * Framework Validator
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @version     1.0.1
 * @package     cloudrexx
 * @subpackage  lib_framework
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @author      Reto Kohli <reto.kohli@comvation.com> (parts)
 * @todo        Edit PHP DocBlocks!
 * @todo        Most, if not all, should be static
 */
class FWValidator
{

    /**
     * Regular Expression for e-mail addresses
     * @author Kevin Riesen
     * @since 3.1.1
     */
    const REGEX_EMAIL = VALIDATOR_REGEX_EMAIL;

    /**
     * Regular Expression in javascript for e-mail addresses
     * @author Kevin Riesen
     * @since 3.1.1
     */
    const REGEX_EMAIL_JS = VALIDATOR_REGEX_EMAIL_JS;

    /**
     * Regular Expression for URI protocols
     *
     * Known protocols include HTTP, HTTPS, FTP, and FTPS
     * @author Kevin Riesen
     * @since 3.1.1
     */
    const REGEX_URI_PROTO = VALIDATOR_REGEX_URI_PROTO;

    /**
     * Regular Expression for URIs
     * @author  Kevin Riesen
     * @since   3.1.1
     */
    const REGEX_URI = VALIDATOR_REGEX_URI;


    /*
     * Regular Expression in javascript for URIs
     * @author Kevin Riesen
     * @since 3.1.1
     */
    const REGEX_URI_JS = VALIDATOR_REGEX_URI_JS;

    /**
     * Array of harmful file extensions
     *
     * File uploads having those extensions are denied.
     * 
     * @var array
     */
    protected static $evilFileExtensions = array(
        # windows executables:
        'exe', 'bat', 'pif', 'com',
        # client scripts:
        'vs', 'vbs',
        # server scripts:
        'php', 'php4', 'php5', 'phps', 'cgi', 'pl', 'jsp', 'jspx', 'asp', 'aspx',
        'jsp', 'jspx', 'jhtml', 'phtml', 'cfm', 'htaccess','py',
    );

    /**
     * Array of potential harmful file extensions (client script containers)
     * 
     * File uploads having those extensions may be allowed by config
     *
     * @var array
     */
    protected  static $potentialEvilFileExtensions = array(
        # client script containers:
        'xhtml', 'xml', 'svg', 'shtml',
    );

    /**
     * Validate an E-mail address
     *
     * Note:  This used to have a stripslashes() around the string.
     * This is bollocks.  If you want to match a string, you match the string,
     * not transformed version.  Strip whatever you want, but do it *before*
     * you call this function.
     * @param  string $string
     * @return boolean          True if it's an e-mail address, false otherwise
     * @access public
     */
    static function isEmail($string)
    {
        return (bool)preg_match('/^'.VALIDATOR_REGEX_EMAIL.'$/i', $string);
    }


    /**
     * Returns true if the given string is a valid URI
     * @param   string  $string The string to be tested
     * @return  boolean         True if the string represents an URI,
     *                          false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function isUri($string)
    {
        return (bool)preg_match('/^'.VALIDATOR_REGEX_URI.'$/i', $string);
    }


    /**
     * Returns true if the given string starts with a protocol
     *
     * See {@see VALIDATOR_REGEX_URI_PROTO} for known protocols.
     * @param   string  $string The string to be tested
     * @return  boolean         True if the string starts with an URI,
     *                          false otherwise
     * @author  Reto Kohli <reto.kohli@comvation.com>
     */
    static function hasProto($string)
    {
        return (bool)preg_match('/^'.VALIDATOR_REGEX_URI_PROTO.'/i', $string);
    }


    /**
     * Find all e-mail addresses in a string
     * @param   string  $string     String potentially containing email addresses
     * @return  array               Array with all e-mail addresses found
     * @access  public
     * @todo    This function does not belong in here
     */
    static function getEmailAsArray($string)
    {
        $arrMatches = array();
        preg_match_all(
            '/(?:^|\s)('.VALIDATOR_REGEX_EMAIL.')\.?(?:\s|$)/i', $string, $arrMatches);
        return $arrMatches[0]; // include spaces
        // return $arrMatches[1]; // exclude spaces
    }


    /**
     * Adds a leading protocol ("http://") prefix to the string, if
     * there is none.
     *
     * Note:  This accepts any known and unknown protocol already present.
     * Mind your step!
     * @access  public
     * @param   string  $string   The URL with possibly missing protocol
     * @return  string            The complete URL with protocol
     * @todo    This function does not belong in here
     */
    static function getUrl($string)
    {
        if (preg_match('/^[a-z]+:\/\//i', $string) || empty($string))
            return $string;
        return 'http://'.$string;
    }


    /**
     * Returns true if the ending of the given file name is harmless.
     *
     * We consider all executable as well as
     * all scripts (server and client side) as harmful.
     * You should NOT allow to upload these.
     * This function returns true if the given filename
     * is safe to upload.
     * @param   string  $file   The file name
     */
    static function is_file_ending_harmless($file)
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        // check if file is harmfull
        if (in_array($ext, self::$evilFileExtensions)) {
            return false;
        }

        //Check the file extension is present in $potentialEvilFileExtensions
        //if so, check the config options 
        //'allowClientsideScriptUpload' and 'allowClientSideScriptUploadOnGroups'.
        //If the option 'allowClientsideScriptUpload' is 'nobody' then its a harmful file
        //else if the option is 'groups' and the current user is member of the 
        // groups mentioned in the option 'allowClientSideScriptUploadOnGroups' then
        //its a harmless file.
        //If the option is 'all' then its a harmless file too.

        // if file is not potentially harmfull, then the file is harmless
        if (!in_array($ext, self::$potentialEvilFileExtensions)) {
            return true;
        }

        $allowedCSUpload = \Cx\Core\Setting\Controller\Setting::getValue(
            'allowClientsideScriptUpload',
            'Config'
        );
        $allowedCSGroups = \Cx\Core\Setting\Controller\Setting::getValue(
            'allowClientSideScriptUploadOnGroups',
            'Config'
        );

        // Check if we are allowed to process the potentially harmful file.
        // no restriction set at all
        if ($allowedCSUpload == 'all') {
            return true;
        }

        // check if we are a member of a user group that is allowed to upload
        // potentially harmfull files
        if (
            $allowedCSUpload == 'groups' &&
            (
                \FWUser::getFWUserObject()->objUser->getAdminStatus() ||
                count(
                    array_intersect(
                        explode(',', $allowedCSGroups),
                        \FWUser::getFWUserObject()->objUser->getAssociatedGroupIds()
                    )
                )
            )
        ) {
            return true;
        }

        // fallback to file is harmfull
        return false;
    }


    private static function __fix_flash($html)
    {
        // we come from a preg_replace_callback
        $html = $html[0];

        // already done
        if (strstr($html, 'wmode=')) return $html;

        // INJECT <param> for IE
        $new_param = '<param name="wmode" value="transparent" />'."\n";
        $html = preg_replace('/<param/i', "$new_param<param", $html, 1);

        // INJECT <embed> attribute for FF and the rest of the gang
        $embed_attr = 'wmode="transparent"';
        return preg_replace('/<embed/i', "<embed $embed_attr", $html, 1);
    }


    /**
     * This function fixes the given HTML so that any embedded flash
     * objects will get the "wmode=transprent" set.
     *
     * This is neccessary for the frontend login box for example, when a
     * flash object is on the page.
     * Takes un-escaped HTML code as parameter, returns the fixed HTML.
     */
    static function fix_flash_transparency($html_code) {
        $result = preg_replace_callback(
            '!<object.*?.*?<param.*?</object>!ims',
            array('FWValidator', '__fix_flash'),
            $html_code
        );
        return $result;
    }
    /**
     * Get a file name that is allowed on all file systems.
     */
    public static function getCleanFileName($fileName)
    {
        // replace $change with ''
        $change = array('\\', '/', ':', '*', '?', '"', '<', '>', '|');
        $fileName = str_replace($change, '_', $fileName);

        return $fileName;
    }

    /**
     * Check whether the given value is empty or not
     *
     * @param mixed $value
     *
     * @return boolean true if the value is empty, false otherwise
     */
    public static function isEmpty($value)
    {
        return empty($value);
    }

    /**
    * Tests if an input is valid PHP serialized string.
    *
    * Checks if a string is serialized using quick string manipulation
    * to throw out obviously incorrect strings. Unserialize is then run
    * on the string to perform the final verification.
    *
    * Valid serialized forms are the following:
    * <ul>
    * <li>boolean: <code>b:1;</code></li>
    * <li>integer: <code>i:1;</code></li>
    * <li>double: <code>d:0.2;</code></li>
    * <li>string: <code>s:4:"test";</code></li>
    * <li>array: <code>a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}</code></li>
    * <li>object: <code>O:8:"stdClass":0:{}</code></li>
    * <li>null: <code>N;</code></li>
    * </ul>
    *
    * @author    Chris Smith <code+php@chris.cs278.org>, Frank Bültge <frank@bueltge.de>
    * @copyright    Copyright (c) 2009 Chris Smith (http://www.cs278.org/), 2011 Frank Bültge (http://bueltge.de)
    * @license    http://sam.zoy.org/wtfpl/ WTFPL
    * @param    string $value Value to test for serialized form
    * @param    mixed $result Result of unserialize() of the $value
    * @return    boolean True if $value is serialized data, otherwise FALSE
    */
    static function is_serialized( $value, &$result = null ) {
        // Bit of a give away this one
        if ( ! is_string( $value ) ) {
            return false;
        }

        // Serialized FALSE, return TRUE. unserialize() returns FALSE on an
        // invalid string or it could return FALSE if the string is serialized
        // FALSE, eliminate that possibility.
        if ( 'b:0;' === $value ) {
            $result = false;
            return true;
        }

        $length    = strlen($value);
        $end    = '';

        if ( isset( $value[0] ) ) {
            switch ($value[0]) {
                case 's':
                    if ('"' !== $value[$length - 2]) {
                        return false;
                    }
                case 'b':
                case 'i':
                case 'd':
                    // This looks odd but it is quicker than isset()ing
                    $end .= ';';
                case 'a':
                case 'O':
                    $end .= '}';

                    if (':' !== $value[1]) {
                        return false;
                    }

                    switch ( $value[2] ) {
                        case 0:
                        case 1:
                        case 2:
                        case 3:
                        case 4:
                        case 5:
                        case 6:
                        case 7:
                        case 8:
                        case 9:
                            break;

                        default:
                            return false;
                    }
                case 'N':
                    $end .= ';';

                    if ($value[$length - 1] !== $end[0]) {
                        return false;
                    }
                    break;

                default:
                    return false;
            }
        }

        if ( ( $result = @unserialize($value) ) === false ) {
            $result = null;
            return false;
        }

        return true;
    }

    /**
     * Cut HTML-code in length by a specific amount of visiable characters
     *
     * @param   string $html    HTML code to cut
     * @param   integer $maxLength  Visual length (in characters) to cut the
     *                              HTML code to
     * @param   string  $suffix Text to append at the end of the cut HTML code
     */
    public static function cutHtmlByDisplayLength(&$html, $maxLength = 250, $suffix = '') {
        // get plaintext of html code
        $plaintext = contrexx_html2plaintext($html);
        $useLength = $maxLength;

        // abort if output is not longer than set length limit
        if (strlen($plaintext) <= $maxLength) {
            return;
        }

        do {
            // cut html to set length
            $cutHtml = substr($html, 0, $useLength);

            // strip out any html code
            $plaintext = contrexx_html2plaintext($cutHtml);

            // obtain length of plaintext of cut html
            $plaintextLength = strlen($plaintext);

            // determine length of stripped html code
            $htmlNoise = $maxLength - $plaintextLength;

            // append length of stripped html code to set cut length
            $useLength += $htmlNoise;

        // repeat above procedere until the length of the raw output
        // of the cut html reaches the set max length
        } while ($plaintextLength < $maxLength);

        // now cut the html to the determined length
        $cutHtml = substr($html, 0, $useLength);

        // cut of the last word-fragment as it might represent
        // a literal word cut in half
        $cutHtml = substr($cutHtml, 0, strrpos($cutHtml, ' '));

        // Ensure the html is still valid, by adding any
        // missing html closing tags.
        // DOMDocument will do that for us
        $doc = new \DOMDocument();
        $doc->loadHTML($cutHtml);

        // fetch only content of html <body> tag
        $nodeList = $doc->getElementsByTagName('body');
        $bodyNode = $nodeList->item(0);

        // add optional suffix to the end of the html code
        $lastNode = $bodyNode->lastChild;
        if ($suffix && $lastNode) {
            $lastNode->appendChild($doc->createTextNode($suffix));
        }

        // fetch content of <body>-tag and strip out <p>-tag
        // that was automatically added by DOMDocument
        $body = $doc->saveHTML($bodyNode);
        $cutHtml = preg_replace('#^<body>(<p>)?\s*|\s*(</p>)?</body>$#', '', $body);

        // assign cut html code to referenced variable
        $html = $cutHtml;
    }
}

/**
 * An abstract base for ZendValidator-Style instantiable Validators
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
abstract class CxValidate {
    protected $constraints;
    protected $passesValidation;
    protected $messages;

    /**
     * If empty values are allowed in the validation check
     *
     * @var bool
     */
    protected $validateEmpty;

    // TODO: Possibly throw an Exception if an unknown/typoed constraint was provided
    public function __construct($constraints, $validateEmpty = false)
    {
        $this->messages = array();
        $this->constraints = $constraints;
        $this->validateEmpty = $validateEmpty;
    }

    public abstract function isValid($value);

    public function getMessages()
    {
        return $this->messages;
    }

}

/**
 * Validates Strings to a set of constraints
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class CxValidateString extends CxValidate {
    public function __construct($constraints, $validateEmpty = false)
    {
        parent::__construct($constraints, $validateEmpty);
    }

    public function isValid($value)
    {
        $this->passesValidation = true;
        if (!$this->validateEmpty && !$value) {
            return $this->passesValidation;
        }

        if (isset($this->constraints['maxlength'])) {
            if (strlen($value) > $this->constraints['maxlength']) {
            // TODO: Translate messages
            $this->messages[] = 'is too long.';
            $this->passesValidation = false;
            }
        }

        if (
            isset($this->constraints['alphanumeric']) &&
            $this->constraints['alphanumeric']
        ) {
            if (!ctype_alnum($value)) {
            $this->passesValidation = false;
            }
        }

        return $this->passesValidation;
    }
}

/**
 * CxValidateRegexp
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class CxValidateRegexp extends CxValidate {
    public function __construct($constraints, $validateEmpty = false)
    {
        parent::__construct($constraints, $validateEmpty);
    }

    public function isValid($value)
    {
        $this->passesValidation = true;
        if (!$this->validateEmpty && !$value) {
            return $this->passesValidation;
        }
        $this->passesValidation = false;

        if (isset($this->constraints['pattern']) &&
            preg_match($this->constraints['pattern'], $value)) {
            $this->passesValidation = true;
        } else {
            // TODO: Translate messages
            $this->messages[] = 'doesn\'t match required pattern.';
        }

        return $this->passesValidation;
    }
}

/**
 * CxValidateInteger
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class CxValidateInteger extends CxValidate {
    public function __construct($constraints = array(), $validateEmpty = false)
    {
        parent::__construct($constraints, $validateEmpty);
    }

    public function isValid($value)
    {
        $this->passesValidation = true;
        if (!$this->validateEmpty && !$value) {
            return $this->passesValidation;
        }
        $this->passesValidation = false;

        if (is_numeric($value) || is_int($value)) {
            $this->passesValidation = true;
        } else {
            // TODO: Translate messages
            $this->messages[] = 'is not a number.';
        }

        return $this->passesValidation;
    }
}
