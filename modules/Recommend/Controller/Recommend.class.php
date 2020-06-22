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
 * Class Recommend
 *
 * Recommend module class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_recommend
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\Recommend\Controller;
/**
 * Class Recommend
 *
 * Recommend module class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_recommend
 * @todo        Edit PHP DocBlocks!
 */
class Recommend extends RecommendLibrary
{
    /**
     * Template object
     *
     * @access private
     * @var object
     */
    var $_objTpl;
    var $langId;
    var $_pageMessage;

    /**
     * Constructor
     * @global integer
     */
    function __construct($pageContent)
    {
        global $_LANGID;

        $this->langId=$_LANGID;
        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_objTpl->setTemplate($pageContent);
    }


    /**
     * Get content page
     *
     * @access public
     */
    function getPage()
    {
        $act = !empty($_GET['act']) ? $_GET['act'] : '';
        switch ($act) {
            case 'sendRecomm':
                $this->_sendRecomm();
                break;
            default:
                $this->_showForm();
                break;
        }

        return $this->_objTpl->get();
    }


    /**
     * Just shows the form
     */
    function _showForm()
    {
        global $_LANGID, $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setVariable(array(
            'RECOM_TEXT'                    => $_ARRAYLANG['TXT_INTRODUCTION'],
            'RECOM_TXT_RECEIVER_NAME'        => $_ARRAYLANG['TXT_RECEIVERNAME_FRONTEND'],
            'RECOM_TXT_RECEIVER_MAIL'        => $_ARRAYLANG['TXT_RECEIVERMAIL_FRONTEND'],
            'RECOM_TXT_GENDER'                => $_ARRAYLANG['TXT_GENDER_FRONTEND'],
            'RECOM_TXT_SENDER_NAME'            => $_ARRAYLANG['TXT_SENDERNAME_FRONTEND'],
            'RECOM_TXT_SENDER_MAIL'            => $_ARRAYLANG['TXT_SENDERMAIL_FRONTEND'],
            'RECOM_TXT_COMMENT'                => $_ARRAYLANG['TXT_COMMENT_FRONTEND'],
            'RECOM_TXT_PREVIEW'                => $_ARRAYLANG['TXT_PREVIEW_FRONTEND'],
            'RECOM_TXT_FEMALE'                => $_ARRAYLANG['TXT_FEMALE_FRONTEND'],
            'RECOM_TXT_MALE'                => $_ARRAYLANG['TXT_MALE_FRONTEND'],
            'TXT_RECOMMEND_SEND'            => $_ARRAYLANG['TXT_RECOMMEND_SEND'],
            'TXT_RECOMMEND_DELETE'          => $_ARRAYLANG['TXT_RECOMMEND_DELETE'],
        ));

        $this->_objTpl->setVariable(array(
            'RECOM_REFERER'                    => '$(HTTP_REFERER)',
            'RECOM_FEMALE_CHECKED'          => 'checked',
            'RECOM_SCRIPT'                    => $this->getJs(),
            'RECOM_PREVIEW'                    => $this->getMessageBody($_LANGID),
            'RECOM_FEMALE_SALUTATION_TEXT'     => $this->getFemaleSalutation($_LANGID),
            'RECOM_MALE_SALUTATION_TEXT'     => $this->getMaleSalutation($_LANGID)
        ));

        //Spam-Protection
        $this->_objTpl->setVariable(array(
            'RECOM_TXT_CAPTCHA'     => $_CORELANG['TXT_CORE_CAPTCHA'],
            'RECOM_CAPTCHA_CODE'    => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
        ));
        if ($this->_objTpl->blockExists('recommend_captcha')) {
            $this->_objTpl->parse('recommend_captcha');
        } else {
            $this->_objTpl->hideBlock('recommend_captcha');
        }

        $this->_objTpl->parse('recommend_form');
    }


    /**
     * Send Recommendation
     *
     * Send an email if the input is valid. Otherwise
     * Show some error messages and the form again
     */
    function _sendRecomm()
    {
        global $_ARRAYLANG, $_CONFIG, $_LANGID, $_CORELANG;

        if (empty($_POST['receivername'])) {
            $this->_pageMessage .= $_ARRAYLANG['TXT_STATUS_RECEIVER_NAME'].' '.$_ARRAYLANG['TXT_IS_EMPTY'].'<br />';
        }

        if (empty($_POST['receivermail'])) {
            $this->_pageMessage .= $_ARRAYLANG['TXT_STATUS_RECEIVER_MAIL'].' '.$_ARRAYLANG['TXT_IS_EMPTY'].'<br />';
        } elseif (!$this->isEmail($_POST['receivermail'])) {
            $this->_pageMessage .= $_ARRAYLANG['TXT_STATUS_RECEIVER_MAIL'].' '.$_ARRAYLANG['TXT_IS_INVALID'].'<br />';
        }

        if (empty($_POST['sendername'])) {
            $this->_pageMessage .= $_ARRAYLANG['TXT_STATUS_SENDER_NAME'].' '.$_ARRAYLANG['TXT_IS_EMPTY'].'<br />';
        }

        if (empty($_POST['sendermail'])) {
            $this->_pageMessage .= $_ARRAYLANG['TXT_STATUS_SENDER_MAIL'].' '.$_ARRAYLANG['TXT_IS_EMPTY'].'<br />';
        } elseif (!$this->isEmail($_POST['sendermail'])) {
            $this->_pageMessage .= $_ARRAYLANG['TXT_STATUS_SENDER_MAIL'].' '.$_ARRAYLANG['TXT_IS_INVALID'].'<br />';
        }

        if (empty($_POST['comment'])) {
            $this->_pageMessage .= $_ARRAYLANG['TXT_STATUS_COMMENT'].' '.$_ARRAYLANG['TXT_IS_EMPTY'].'<br />';
        }

        $receivername     = $_POST['receivername'];
        $receivermail     = $_POST['receivermail'];
        $sendername     = $_POST['sendername'];
        $sendermail     = $_POST['sendermail'];
        $comment         = $_POST['comment'];

        if (!empty($this->_pageMessage) || !\Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->check()) {
            //something's missing or wrong
            $this->_objTpl->setVariable('RECOM_STATUS', '<div class="text-danger">'.$this->_pageMessage.'</div>');
            $this->_objTpl->setCurrentBlock('recommend_form');
            $this->_objTpl->setVariable(array(
                'RECOM_SCRIPT'                    => $this->getJs(),
                'RECOM_RECEIVER_NAME'            => stripslashes($receivername),
                'RECOM_RECEIVER_MAIL'            => stripslashes($receivermail),
                'RECOM_SENDER_NAME'                => stripslashes($sendername),
                'RECOM_SENDER_MAIL'                => stripslashes($sendermail),
                'RECOM_COMMENT'                    => stripslashes($comment),
                'RECOM_PREVIEW'                    => $this->getMessageBody($_LANGID),
                'RECOM_FEMALE_SALUTATION_TEXT'     => $this->getFemaleSalutation($_LANGID),
                'RECOM_MALE_SALUTATION_TEXT'     => $this->getMaleSalutation($_LANGID)
            ));

            $this->_objTpl->setVariable(array(
                'RECOM_TXT_RECEIVER_NAME'        => $_ARRAYLANG['TXT_RECEIVERNAME_FRONTEND'],
                'RECOM_TXT_RECEIVER_MAIL'        => $_ARRAYLANG['TXT_RECEIVERMAIL_FRONTEND'],
                'RECOM_TXT_GENDER'                => $_ARRAYLANG['TXT_GENDER_FRONTEND'],
                'RECOM_TXT_SENDER_NAME'            => $_ARRAYLANG['TXT_SENDERNAME_FRONTEND'],
                'RECOM_TXT_SENDER_MAIL'            => $_ARRAYLANG['TXT_SENDERMAIL_FRONTEND'],
                'RECOM_TXT_COMMENT'                => $_ARRAYLANG['TXT_COMMENT_FRONTEND'],
                'RECOM_TXT_PREVIEW'                => $_ARRAYLANG['TXT_PREVIEW_FRONTEND'],
                'RECOM_TXT_FEMALE'                => $_ARRAYLANG['TXT_FEMALE_FRONTEND'],
                'RECOM_TXT_MALE'                => $_ARRAYLANG['TXT_MALE_FRONTEND'],
                'RECOM_TEXT'                    => $_ARRAYLANG['TXT_INTRODUCTION'],
                'TXT_RECOMMEND_SEND'            => $_ARRAYLANG['TXT_RECOMMEND_SEND'],
                'TXT_RECOMMEND_DELETE'          => $_ARRAYLANG['TXT_RECOMMEND_DELETE'],
            ));

            $this->_objTpl->setVariable(array(
                'RECOM_TXT_CAPTCHA'     => $_CORELANG['TXT_CORE_CAPTCHA'],
                'RECOM_CAPTCHA_CODE'    => \Cx\Core_Modules\Captcha\Controller\Captcha::getInstance()->getCode(),
            ));
            if ($this->_objTpl->blockExists('recommend_captcha')) {
                $this->_objTpl->parse('recommend_captcha');
            } else {
                $this->_objTpl->hideBlock('recommend_captcha');
            }

            $this->_objTpl->parseCurrentBlock('recommend_form');
            $this->_objTpl->parse();
        } else {
            //data is valid

            if (empty($_POST['uri'])) {
                $url = ASCMS_PROTOCOL.'://'.$_SERVER['HTTP_HOST'].ASCMS_PATH_OFFSET;
            } else {
                $url = $_POST['uri'];
            }

            if ($_POST['gender'] == 'male') {
                $salutation = $this->getMaleSalutation($_LANGID);
            } else {
                $salutation = $this->getFemaleSalutation($_LANGID);
            }

            $body = $this->getMessageBody($_LANGID);

            $body = preg_replace('/<SENDER_NAME>/', $sendername, $body);
            $body = preg_replace('/<SENDER_MAIL>/', $sendermail, $body);
            $body = preg_replace('/<RECEIVER_NAME>/', $receivername, $body);
            $body = preg_replace('/<RECEIVER_MAIL>/', $receivermail, $body);
            $body = preg_replace('/<URL>/', $url, $body);
            $body = preg_replace('/<COMMENT>/', $comment, $body);
            $body = preg_replace('/<SALUTATION>/', $salutation, $body);

            $subject = $this->getMessageSubject($_LANGID);

            $subject = preg_replace('/<SENDER_NAME>/', $sendername, $subject);
            $subject = preg_replace('/<SENDER_MAIL>/', $sendermail, $subject);
            $subject = preg_replace('/<RECEIVER_NAME>/', $receivername, $subject);
            $subject = preg_replace('/<RECEIVER_MAIL>/', $receivermail, $subject);
            $subject = preg_replace('/<URL>/', $url, $subject);
            $subject = preg_replace('/<COMMENT>/', $comment, $subject);
            $subject = preg_replace('/<SALUTATION>/', $salutation, $subject);

            $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

            $objMail->SetFrom($sendermail, $sendername);
            $objMail->AddReplyTo($sendermail);
            $objMail->Subject = $subject;
            $objMail->IsHTML(false);
            $objMail->Body = $body;
            $objMail->AddAddress($receivermail);
            $objMail->Send();

            $this->_objTpl->setVariable('RECOM_STATUS', $_ARRAYLANG['TXT_SENT_OK']);
            $this->_objTpl->parse();
        }
    }


    /**
     * Validate the email
     *
     * @param  string  $string
     * @return boolean result
     */
    function isEmail($string)
    {
        return \FWValidator::isEmail($string);
    }


    function getJs()
    {
        return "<script type=\"text/javascript\">
// <![CDATA[
function recommendUpdate()
{
    var inhalt = document.recommend.preview_text.value;

    if (document.recommend.sendername.value != '') {
        var inhalt = inhalt.replace(/<SENDER_NAME>/g, document.recommend.sendername.value);
    }
    if (document.recommend.sendermail.value != '') {
        var inhalt = inhalt.replace(/<SENDER_MAIL>/g, document.recommend.sendermail.value);
    }
    if (document.recommend.receivername.value != '') {
        var inhalt = inhalt.replace(/<RECEIVER_NAME>/g, document.recommend.receivername.value);
    }
    if (document.recommend.receivermail.value != '') {
        var inhalt = inhalt.replace(/<RECIEVER_MAIL>/g, document.recommend.receivermail.value);
    }
    if (document.recommend.comment.value != '') {
        var inhalt = inhalt.replace(/<COMMENT>/g, document.recommend.comment.value);
    }

    if (document.recommend.uri.value != '') {
        var inhalt = inhalt.replace(/<URL>/g, document.recommend.uri.value);
    } else {
        var inhalt = inhalt.replace(/<URL>/g, document.URL);
    }

    if (document.recommend.gender[0].checked) {
        var inhalt = inhalt.replace(/<SALUTATION>/g, document.recommend.female_salutation_text.value);
    } else {
        var inhalt = inhalt.replace(/<SALUTATION>/g, document.recommend.male_salutation_text.value);
    }

    document.recommend.preview.value = inhalt
}
// ]]>
</script>";
    }
}

?>
