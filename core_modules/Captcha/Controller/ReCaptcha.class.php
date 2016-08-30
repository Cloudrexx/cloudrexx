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
 * ReCaptcha
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_captcha
 */

namespace Cx\Core_Modules\Captcha\Controller;


/**
 * ReCaptcha
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_captcha
 */
class ReCaptcha implements CaptchaInterface {
    private $public_key;
    private $private_key;
    private $error = '';

    public function __construct($config)
    {

        /**
         * @ignore
         */
        \Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH . '/reCAPTCHA/recaptchalib.php');

        $captchaConfig = json_decode($config['coreCaptchaLibConfig'], true);

        if (!isset($captchaConfig['ReCaptcha'])) {
            return;
        }

        $reCAPTCHAConfig = $captchaConfig['ReCaptcha'];
        if (!isset($reCAPTCHAConfig['domains'][$config['domainUrl']])) {
            return;
        }

        $reCAPTCHAKeys = $reCAPTCHAConfig['domains'][$config['domainUrl']];
        $this->public_key = $reCAPTCHAKeys['public_key'];
        $this->private_key = $reCAPTCHAKeys['private_key'];
    }

    public function getCode($tabIndex = null)
    {
        $tabIndexAttr = '';
        if (isset($tabIndex)) {
            $tabIndexAttr = "tabindex=\"$tabIndex\"";
        }

        $widget = <<<HTML
<div id="recaptcha_widget" style="display:none">

    <div id="recaptcha_image"></div>
    <div class="recaptcha_only_if_incorrect_sol" style="color:red">Incorrect please try again</div>

    <span class="recaptcha_only_if_image">Enter the words above:</span>
    <span class="recaptcha_only_if_audio">Enter the numbers you hear:</span>

    <input type="text" id="recaptcha_response_field" name="recaptcha_response_field" $tabIndexAttr />

    <div>
        <div>
            <a title="Get a new challenge" href="javascript:Recaptcha.reload()" id="recaptcha_reload_btn">
                <img src="http://www.google.com/recaptcha/api/img/clean/refresh.png" id="recaptcha_reload" alt="Get a new challenge" height="18" width="25">
            </a>
        </div>
        <div class="recaptcha_only_if_image">
            <a title="Get an audio challenge" href="javascript:Recaptcha.switch_type('audio');" id="recaptcha_switch_audio_btn" class="recaptcha_only_if_image">
                <img src="http://www.google.com/recaptcha/api/img/clean/audio.png" id="recaptcha_switch_audio" alt="Get an audio challenge" height="15" width="25">
            </a>
        </div>
        <div class="recaptcha_only_if_audio">
            <a title="Get a visual challenge" href="javascript:Recaptcha.switch_type('image');" id="recaptcha_switch_img_btn" class="recaptcha_only_if_audio">
                <img src="http://www.google.com/recaptcha/api/img/clean/text.png" id="recaptcha_switch_img" alt="Get a visual challenge" height="15" width="25">
            </a>
        </div>
        <div>
            <a href="javascript:Recaptcha.showhelp()"title="Help" target="_blank" id="recaptcha_whatsthis_btn">
                <img alt="Help" src="http://www.google.com/recaptcha/api/img/clean/help.png" id="recaptcha_whatsthis" height="16" width="25">
            </a>
        </div>
    </div>

</div>

<script type="text/javascript" src= "http://www.google.com/recaptcha/api/challenge?k=%1\$s"></script>
<noscript>
    <iframe src="http://www.google.com/recaptcha/api/noscript?k=%1\$s" height="300" width="500" frameborder="0"></iframe><br />
    <textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
    <input type="hidden" name="recaptcha_response_field" value="manual_challenge">
</noscript>
HTML;
        $lang = \FWLanguage::getLanguageCodeById(FRONTEND_LANG_ID);
        //\JS::registerCode("var RecaptchaOptions = { lang : '$lang', theme : 'clean' }");
        \JS::registerCode("var RecaptchaOptions = { lang : '$lang', theme : 'custom', custom_theme_widget: 'recaptcha_widget' }");
        //\JS::registerCSS("lib/reCAPTCHA/recaptcha.widget.clean.css");
        $code = sprintf($widget, $this->public_key);
        //$code = recaptcha_get_html($this->public_key, $this->error);
        return $code;
    }

    public function check()
    {
        $resp = recaptcha_check_answer($this->private_key,
                                       $_SERVER["REMOTE_ADDR"],
                                       $_POST["recaptcha_challenge_field"],
                                       $_POST["recaptcha_response_field"]);

        if ($resp->is_valid) {
            return true;
        }

        // set error message
        $this->error = $resp->error;
        return false;
    }

}
