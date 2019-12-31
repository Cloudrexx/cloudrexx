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

namespace Cx\Core_Modules\Login\Controller;

/**
 * Login
 * @copyright   CLOUDREXX WCMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodule_login
 */
class LoginManager {

    /**
     * Template object
     *
     * @access  public
     * @var     object
     */
    public $objTemplate;

    /**
     * Constructor
     *
     * @access  publice
     */
    public function __construct()
    {
        $this->objTemplate = new \Cx\Core\Html\Sigma(ASCMS_DOCUMENT_ROOT);
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->objTemplate);
        $this->objTemplate->setErrorHandling(PEAR_ERROR_DIE);
        $this->objTemplate->loadTemplateFile('/core/Core/View/Template/Backend/Index.html');
    }

    /**
     * Get the requested Page.
     *
     * @access  public
     */
    public function getPage()
    {
        $_GET['act'] = empty($_GET['act']) ? '' : $_GET['act'];

        switch ($_GET['act']) {
            case 'lostpw':
                $this->showPasswordLost();
                break;
            case 'resetpw':
                $this->showPasswordReset();
                break;
            case 'verify':
                $this->verifyUserAccount();
                break;
            case 'captcha':
                $this->getCaptcha();
                break;
            default:
                $this->showLogin();
                break;
        }

        $this->objTemplate->setVariable('CONTREXX_CHARSET', CONTREXX_CHARSET);

        $endcode = $this->objTemplate->get();

        // replace links from before contrexx 3
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $ls = new \LinkSanitizer(
            $cx,
            $cx->getCodeBaseOffsetPath() . $cx->getBackendFolderName() . '/',
            $endcode
        );
        $endcode = $ls->replace();

        echo $endcode;
        exit();
    }

    /**
     * Show the password lost mask.
     *
     * @access  private
     * @global  array    $_ARRAYLANG
     * @global  FWUser   $objFWUser
     */
    private function showPasswordLost()
    {
        global $_ARRAYLANG;

        \JS::activate('jquery');
        $objFWUser = \FWUser::getFWUserObject();
        $this->objTemplate->addBlockfile('CONTENT_FILE', 'CONTENT_BLOCK', '/core_modules/Login/View/Template/Backend/login_lost_password.html');
        $this->objTemplate->setVariable(array(
            'TITLE'                     => $_ARRAYLANG['TXT_LOGIN_RESET_PASSWORD'],
            'TXT_LOGIN_LOST_PASSWORD'   => $_ARRAYLANG['TXT_LOGIN_LOST_PASSWORD'],
            'TXT_LOGIN_EMAIL'           => $_ARRAYLANG['TXT_LOGIN_EMAIL'],
            'TXT_LOGIN_ENTER_A_EMAIL'   => $_ARRAYLANG['TXT_LOGIN_ENTER_A_EMAIL'],
            'TXT_LOGIN_RESET_PASSWORD'  => $_ARRAYLANG['TXT_LOGIN_RESET_PASSWORD'],
            'TXT_LOGIN_BACK_TO_LOGIN'   => $_ARRAYLANG['TXT_LOGIN_BACK_TO_LOGIN'],
            'JAVASCRIPT'                => \JS::getCode(),
        ));
        $this->objTemplate->hideBlock('error_message');
        $this->objTemplate->hideBlock('success_message');
        $this->objTemplate->hideBlock('back_to_login');

        if (isset($_POST['email'])) {
            $email = contrexx_stripslashes($_POST['email']);
            if ($objFWUser->restorePassword($email)) {
                $this->objTemplate->setVariable('LOGIN_SUCCESS_MESSAGE', str_replace("%EMAIL%", $email, $_ARRAYLANG['TXT_LOGIN_LOST_PASSWORD_MAIL_SENT']));
                $this->objTemplate->touchBlock('success_message');
                $this->objTemplate->touchBlock('back_to_login');
                $this->objTemplate->hideBlock('login_lost_password');
            } else {
                $this->objTemplate->setVariable('LOGIN_ERROR_MESSAGE', $objFWUser->getErrorMsg());
                $this->objTemplate->touchBlock('error_message');
            }
        }
    }

    /**
     * Show the password reset mask.
     *
     * @access  private
     * @global  array    $_ARRAYLANG
     * @global  FWUser   $objFWUser
     */
    private function showPasswordReset()
    {
        global $_ARRAYLANG;

        \JS::activate('jquery');
        $objFWUser = \FWUser::getFWUserObject();
        $this->objTemplate->addBlockfile('CONTENT_FILE', 'CONTENT_BLOCK', '/core_modules/Login/View/Template/Backend/login_reset_password.html');
        $this->objTemplate->setVariable(array(
            'TITLE'                             => $_ARRAYLANG['TXT_LOGIN_SET_NEW_PASSWORD'],
            'TXT_LOGIN_BACK_TO_LOGIN'           => $_ARRAYLANG['TXT_LOGIN_BACK_TO_LOGIN'],
            'TXT_LOGIN_GO_TO_BACKEND'           => $_ARRAYLANG['TXT_LOGIN_GO_TO_BACKEND'],
            'TXT_LOGIN_ENTER_A_NEW_PASSWORD'    => $_ARRAYLANG['TXT_LOGIN_ENTER_A_NEW_PASSWORD'],
            'TXT_LOGIN_CONFIRM_NEW_PASSWORD'    => $_ARRAYLANG['TXT_LOGIN_CONFIRM_NEW_PASSWORD'],
            'JAVASCRIPT'                        => \JS::getCode(),
        ));
        $this->objTemplate->hideBlock('error_message');
        $this->objTemplate->hideBlock('success_message');
        $this->objTemplate->hideBlock('back_to_login');
        // TODO: Why oh why isn't function resetPassword() located in the AccessLibrary?
        $email = isset($_POST['email']) ? contrexx_stripslashes($_POST['email']) : (isset($_GET['email']) ? contrexx_stripslashes($_GET['email']) : '');
        $restoreKey = isset($_POST['restore_key']) ? contrexx_stripslashes($_POST['restore_key']) : (isset($_GET['restoreKey']) ? contrexx_stripslashes($_GET['restoreKey']) : '');
        $password = isset($_POST['PASSWORD']) ? trim(contrexx_stripslashes($_POST['PASSWORD'])) : '';
        $confirmedPassword = isset($_POST['password2']) ? trim(contrexx_stripslashes($_POST['password2'])) : '';

        $this->objTemplate->setVariable(array(
            'LOGIN_EMAIL'       => contrexx_raw2xhtml($email),
            'LOGIN_RESTORE_KEY' => contrexx_raw2xhtml($restoreKey),
        ));

        if (isset($_POST['reset_password'])) {
            if ($objFWUser->resetPassword($email, $restoreKey, $password, $confirmedPassword, true)) {
                $this->objTemplate->setVariable('LOGIN_SUCCESS_MESSAGE', $_ARRAYLANG['TXT_LOGIN_PASSWORD_CHANGED_SUCCESSFULLY']);
                $this->objTemplate->touchBlock('success_message');
                $this->objTemplate->hideBlock('login_reset_password');
                $this->objTemplate->touchBlock('back_to_login');

                $userFilter = array(
                    'active'           => 1,
                    'email'            => $email,
                );
                $objUser = $objFWUser->objUser->getUsers($userFilter, null, null, null, 1);
                $objFWUser->loginUser($objUser);
            } else {
                $this->objTemplate->setVariable('LOGIN_ERROR_MESSAGE', $objFWUser->getErrorMsg());
                $this->objTemplate->touchBlock('error_message');

                $this->objTemplate->setVariable(array(
                    'TXT_LOGIN_EMAIL'                       => $_ARRAYLANG['TXT_LOGIN_EMAIL'],
                    'TXT_LOGIN_PASSWORD'                    => $_ARRAYLANG['TXT_LOGIN_PASSWORD'],
                    'TXT_LOGIN_VERIFY_PASSWORD'             => $_ARRAYLANG['TXT_LOGIN_VERIFY_PASSWORD'],
                    'TXT_LOGIN_PASSWORD_MINIMAL_CHARACTERS' => $_ARRAYLANG['TXT_LOGIN_PASSWORD_MINIMAL_CHARACTERS'],
                    'TXT_LOGIN_SET_PASSWORD_TEXT'           => $_ARRAYLANG['TXT_LOGIN_SET_PASSWORD_TEXT'],
                    'TXT_LOGIN_SET_NEW_PASSWORD'            => $_ARRAYLANG['TXT_LOGIN_SET_NEW_PASSWORD'],
                ));
                $this->objTemplate->parse('login_reset_password');
            }
        } else {
            $this->objTemplate->setVariable(array(
                'TXT_LOGIN_EMAIL'                       => $_ARRAYLANG['TXT_LOGIN_EMAIL'],
                'TXT_LOGIN_PASSWORD'                    => $_ARRAYLANG['TXT_LOGIN_PASSWORD'],
                'TXT_LOGIN_VERIFY_PASSWORD'             => $_ARRAYLANG['TXT_LOGIN_VERIFY_PASSWORD'],
                'TXT_LOGIN_PASSWORD_MINIMAL_CHARACTERS' => $_ARRAYLANG['TXT_LOGIN_PASSWORD_MINIMAL_CHARACTERS'],
                'TXT_LOGIN_SET_PASSWORD_TEXT'           => $_ARRAYLANG['TXT_LOGIN_SET_PASSWORD_TEXT'],
                'TXT_LOGIN_SET_NEW_PASSWORD'            => $_ARRAYLANG['TXT_LOGIN_SET_NEW_PASSWORD'],
            ));
            $this->objTemplate->parse('login_reset_password');
        }
    }

    protected function verifyUserAccount() {
        $email = !empty($_GET['u']) ? contrexx_input2raw($_GET['u']) : null;
        $key = !empty($_GET['key']) ? contrexx_input2raw($_GET['key']) : null;

        if (empty($email) || empty($key)) {
// TODO: implement error message
        }

        if (!\FWUser::getFWUserObject()->verifyUserAccount($email, $key)) {
// TODO: implement error message
        }

        \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?cmd=Home');
        exit;
    }

    /**
     * Generate a captcha image.
     *
     * @access  private
     */
    private function getCaptcha()
    {
        \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getPage();
    }

    /**
     * Show the login mask.
     *
     * @access  private
     * @global  array    $_ARRAYLANG
     * @global  FWUser   $objFWUser
     */
    private function showLogin()
    {
        global $_CORELANG, $_ARRAYLANG;

        $this->objTemplate->addBlockfile('CONTENT_FILE', 'CONTENT_BLOCK', '/core_modules/Login/View/Template/Backend/login.html');
        $frontendLink = ASCMS_INSTANCE_OFFSET;
        if (empty($frontendLink)) {
            $frontendLink = '/';
        }
        if (!empty($_POST['redirect'])) {
            $redirect = contrexx_raw2xhtml($_POST['redirect']);
        } else {
            $redirect = contrexx_raw2xhtml(
                ASCMS_PATH_OFFSET .
                ASCMS_BACKEND_PATH .
                substr(
                    getenv('REQUEST_URI'),
                    strlen(\Env::get('cx')->getWebsiteBackendPath())
                )
            );
        }
        $this->objTemplate->setVariable(array(
            'TITLE'                         => $_ARRAYLANG['TXT_LOGIN_LOGIN'],
            'TXT_LOGIN_LOGIN'               => $_ARRAYLANG['TXT_LOGIN_LOGIN'],
            'TXT_FRONTEND_LINK'             => $_ARRAYLANG['TXT_FRONTEND_LINK'],
            'TXT_LOGIN_ENTER_A_LOGIN'       => $_ARRAYLANG['TXT_LOGIN_ENTER_A_LOGIN'],
            'TXT_LOGIN_ENTER_A_PASSWORD'    => $_ARRAYLANG['TXT_LOGIN_ENTER_A_PASSWORD'],
            'TXT_LOGIN_ENTER_CAPTCHA'       => $_ARRAYLANG['TXT_LOGIN_ENTER_CAPTCHA'],
            'TXT_LOGIN_USERNAME'            => $_ARRAYLANG['TXT_LOGIN_USERNAME'],
            'TXT_LOGIN_PASSWORD'            => $_ARRAYLANG['TXT_LOGIN_PASSWORD'],
            'TXT_LOGIN_PASSWORD_LOST'       => $_ARRAYLANG['TXT_LOGIN_PASSWORD_LOST'],
            'TXT_LOGIN_REMEMBER_ME'         => $_CORELANG['TXT_CORE_REMEMBER_ME'],
            'REDIRECT_URL'                  => $redirect,
            'FRONTEND_LINK'                 => $frontendLink,
            'JAVASCRIPT'                    => \JS::getCode(),
        ));

        if (\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()) {
            $this->objTemplate->setVariable('LOGIN_ERROR_MESSAGE', \FWUser::getFWUserObject()->getErrorMsg());
            $this->objTemplate->parse('error_message');
        } else {
            $this->objTemplate->hideBlock('error_message');
        }
        if (isset($_SESSION['auth']['loginLastAuthFailed'])) {
            $this->objTemplate->setVariable(array(
                'TXT_LOGIN_SECURITY_CODE'   => $_ARRAYLANG['TXT_LOGIN_SECURITY_CODE'],
                'CAPTCHA_CODE'              => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(3),
                'CAPTCHA_VALIDATION_CODE'   => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getJSValidationFn(),
            ));
            $this->objTemplate->parse('captcha');
        } else {
            $this->objTemplate->hideBlock('captcha');
        }
    }

}
