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
 * ContrexxCaptcha
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_captcha
 */

namespace Cx\Core_Modules\Captcha\Controller;

/**
 * ContrexxCaptcha
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@astalvista.ch>
 * @access      public
 * @version     1.2.0
 * @package     cloudrexx
 * @subpackage  coremodule_captcha
 */
class ContrexxCaptcha implements CaptchaInterface {
    private $boolFreetypeInstalled = false;

    private $strRandomString;

    private $strFontDir;
    private $strBackgroundDir;

    private $intRandomLength = 5;
    private $intMaximumCharacters = 20;

    private $intImageWidth = 120;
    private $intNumberOfBackgrounds = 7;

    private $image = null; //the GD image

    private $securityCheck = null;

    public function __construct($config)
    {
        srand ((double)microtime()*1000000);

        $this->strRandomString  = $this->createRandomString();

        $this->strFontDir       = ASCMS_CORE_MODULE_PATH.'/Captcha/Data/ContrexxCaptcha/fonts/';
        $this->strBackgroundDir = ASCMS_CORE_MODULE_PATH.'/Captcha/Data/ContrexxCaptcha/backgrounds/';

        $this->isFreetypeInstalled();
    }

    /**
     * Figures out if the Freetype-Extension (part of GD) is installed.
     */
    private function isFreetypeInstalled() {
        $arrExtensions = get_loaded_extensions();

        if (in_array('gd', $arrExtensions)) {
            $arrGdFunctions = get_extension_funcs('gd');

            if (in_array('imagettftext', $arrGdFunctions)) {
                $this->boolFreetypeInstalled = true;
            }
        }
    }

    /**
     * Creates an random string with $intDigits digits.
     *
     * @param    integer        $intDigits: How many digits should the created string have?
     * @return    string        A new random string
     */
    private function createRandomString($intDigits=0) {
        if ($intDigits > $this->intMaximumCharacters || $intDigits == 0) {
            $intDigits = $this->intRandomLength;
        }

        $strReturn = '';
        for ($i=1; $i <= $intDigits; ++$i) {
            switch (rand(0,1)) {
                case 0:
                    $char = chr(rand(65,90));
                    while($char == 'O') //no O's pleace
                        $char = chr(rand(65,90));
                    $strReturn .= $char;
                    break;
                case 1:
                    $strReturn .= sprintf("%d", rand(2,9));
                    break;
                default:
            }
        }

        return  $strReturn;
    }

    /**
     * Creates a captcha image.
     *
     */
    private function createImage() {
        $intWidth             = $this->intImageWidth;
        $intHeight             = $intWidth / 3;
        $intFontSize         = floor($intWidth / strlen($this->strRandomString)) - 2;
        $intAngel             = 15;
        $intVerticalMove     = floor($intHeight/7);

        $image = imagecreatetruecolor($intWidth, $intHeight);

        $arrFontColors     = array(imagecolorallocate($image, 0, 0, 0),        //black
                                imagecolorallocate($image, 255, 0, 0),         //red
                                imagecolorallocate($image, 0, 180, 0),         //darkgreen
                                imagecolorallocate($image, 0, 105, 172),    //blue
                                imagecolorallocate($image, 145, 19, 120)    //purple
                            );

        $arrFonts    = array(    $this->strFontDir.'coprgtb.ttf',
                                $this->strFontDir.'ltypeb.ttf',
                        );

        //Draw background
        $imagebg = imagecreatefromjpeg($this->strBackgroundDir.rand(1, $this->intNumberOfBackgrounds).'.jpg');
        imagesettile($image, $imagebg);
        imagefilledrectangle($image, 0, 0, $intWidth, $intHeight, IMG_COLOR_TILED);

        //Draw string
        for ($i = 0; $i < strlen($this->strRandomString); ++$i) {
            $intColor     = rand(0, count($arrFontColors)-1);
            $intFont    = rand(0, count($arrFonts)-1);
            $intAngel     = rand(-$intAngel, $intAngel);
            $intYMove     = rand(-$intVerticalMove, $intVerticalMove);

            if ($this->boolFreetypeInstalled) {
                imagettftext(    $image,
                                $intFontSize,
                                $intAngel,
                                (6+$intFontSize*$i),
                                ($intHeight/2+$intFontSize/2+$intYMove),
                                $arrFontColors[$intColor],
                                $arrFonts[$intFont],
                                substr($this->strRandomString,$i,1)
                            );
            } else {
                imagestring($image,
                            5,
                            (6+25*$i),
                            12+$intYMove,
                            substr($this->strRandomString,$i,1),
                            $arrFontColors[$intColor]
                        );
            }
        }

        //save the image for further processing
        $this->image = $image;
    }

    /**
     * Creates a new image and sends it to the Browser
     */
    public function getPage() {
        //create a new image...
        $this->createImage();

        //...write the new secret to the session...
        $this->updateSession();

        //...and print it.
        header('Content-type: image/jpeg');
        imagejpeg($this->image, NULL, 90);
        imagedestroy($this->image);

        // stop script execution
        exit;
    }

    /**
     * writes the secret to the session
     */
    private function updateSession() {
        $_SESSION['captchaSecret'] = $this->strRandomString;
    }

    public function getCode($tabIndex = null)
    {
        global $_CORELANG;

        $tabIndexAttr = '';
        if (isset($tabIndex)) {
            $tabIndexAttr = "tabindex=\"$tabIndex\"";
        }
        $alt= contrexx_raw2xhtml($_CORELANG['TXT_CORE_CAPTCHA']);
        $code = '<span id="captcha">';
        $code .= '<label for="coreCaptchaCode" id="coreCaptchaLabel">'.$_CORELANG['TXT_CORE_CAPTCHA_ENTER_THE_LETTERS_BELOW'].'</label>';
        $code .= '<span class="row"><input type="text" name="coreCaptchaCode" id="coreCaptchaCode" value="" maxlength="5" '.$tabIndexAttr.' autocomplete="off" />';
        $code .= '<img src="'.$this->getUrl().'" alt="'.$alt.'" id="coreCaptchaImage" /></span>';
        $code .= '</span>';

        return $code;
    }

    /**
     * Get captcha validation code
     *
     * @return string Returns JS code
     */
    public function getJSValidationFn()
    {
        $captchaValidationCode = <<<JSCaptchaValidation
        if (\$J('#captcha').length) {
            var code = \$J('#coreCaptchaCode').val();
            if (\$J.trim(code) === '') {
                isCaptchaOk = false;
            }
        }
JSCaptchaValidation;
        return $captchaValidationCode;
    }
    /**
     * checks whether the entered string matches the captcha.
     * if the check is already done the result will be returned.
     *
     * @return boolean
     */
    public function check()
    {
        if (empty($this->securityCheck)) {
            if ($this->isValidCode()) {
                $this->securityCheck = true;
            } else {
                $this->securityCheck = false;
                if (!empty($_POST['coreCaptchaCode'])) {
                    \DBG::msg('Captcha: The entered security code was incorrect.');
                }
            }
        }

        return $this->securityCheck;
    }

    /**
     * Disable the captcha check
     *
     * @return void
     */
    public function disable()
    {
        $this->securityCheck = true;
    }

    private function isValidCode()
    {
        if (empty($_POST['coreCaptchaCode'])) {
            return false;
        }

        $strEnteredString = trim(contrexx_input2raw($_POST['coreCaptchaCode']));

        // in case there was a session initialization problem, $_SESSION['captchaSecret'] might be NULL, therefore we must ensure not to test against an empty captcha code
        if (empty($strEnteredString)) {
            return false;
        }

        if (empty($_SESSION['captchaSecret'])) {
            return false;
        }

        $captcha = $_SESSION['captchaSecret'];
        unset($_SESSION['captchaSecret']); //remove secret to improve security

        return strtoupper($strEnteredString) == strtoupper($captcha);
    }

    /**
     * gets the url for a new captcha
     *
     * @return string
     */
    private function getUrl() {
        global $objInit;
        $isBackend = $objInit->mode == "backend";
        $url = ASCMS_PATH_OFFSET;
        if($isBackend) {
            $url .= ASCMS_BACKEND_PATH.'/index.php?cmd=Login&amp;act=captcha';
        }
        else {
            $url .= '/index.php?section=Captcha';
        }

        //add no cache param
        $url .= '&amp;nc='.md5(''.time());
        return $url;
    }

}
