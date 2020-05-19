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
 * DefaultController
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_support
 */

namespace Cx\Modules\Support\Controller;

/**
 *
 * DefaultController for support.
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_support
 */
class DefaultController extends \Cx\Core\Core\Model\Entity\Controller {

    /**
     * Em instance
     * @var \Doctrine\ORM\EntityManager em
     */
    protected $em;

    /**
     * Sigma template instance
     * @var Cx\Core\Html\Sigma  $template
     */
    protected $template;

    /**
     * module name
     * @var string $moduleName
     */
    public $moduleName = 'Support';

    /**
     * module name for language placeholder
     * @var string $moduleNameLang
     */
    public $moduleNameLang = 'SUPPORT';

    /**
     * Controller for the Backend Orders views
     *
     * @param \Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController the system component controller object
     * @param \Cx\Core\Core\Controller\Cx                          $cx                        the cx object
     * @param \Cx\Core\Html\Sigma                                  $template                  the template object
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponentController, $cx);

        $this->em                = $this->cx->getDb()->getEntityManager();
    }


    /**
     * Use this to parse your backend page
     *
     * @param \Cx\Core\Html\Sigma $template
     */
    public function parsePage(\Cx\Core\Html\Sigma $template) {
        $this->template = $template;

        $this->showFeedBackForm();
    }

    /**
     * FeedBack Form
     *
     * @global array $_ARRAYLANG
     */
    public function showFeedBackForm()
    {
        global $_ARRAYLANG;

        $objUser = \FWUser::getFWUserObject();
        //feed back types
        $feedBackTypes = array(
            $_ARRAYLANG['TXT_SUPPORT_FEEDBACK_SELECT_FEEDBACK'],
            $_ARRAYLANG['TXT_SUPPORT_FEEDBACK_BUG_REPORT'],
            $_ARRAYLANG['TXT_SUPPORT_FEEDBACK_FEATURE_REQUEST'],
            $_ARRAYLANG['TXT_SUPPORT_FEEDBACK_HAVE_QUESTION']
        );
        \Cx\Core\Setting\Controller\Setting::init('Support', 'setup','Yaml');
        $faqUrl = \Cx\Core\Setting\Controller\Setting::getValue('faqUrl','Support');
        $recipientMailAddress = \Cx\Core\Setting\Controller\Setting::getValue('recipientMailAddress','Support');
        $faqLink = '<a target="_blank" title="click to FAQ page" href='.$faqUrl.'>'.$_ARRAYLANG['TXT_SUPPORT_FEEDBACK_FAQ'].'</a>';

        //Get License information
        $license        = \Env::get('cx')->getLicense();
        $licenseName    = $license->getEditionName();
        $licenseValid   = date(ASCMS_DATE_FORMAT_DATE, $license->getValidToDate());
        $licenseVersion = $license->getVersion()->getNumber();

        //get the input datas
        $feedBackType    = isset($_REQUEST['feedBackType']) ? intval($_REQUEST['feedBackType']) : '';
        $feedBackSubject = isset($_POST['feedBackSubject']) ? contrexx_input2raw($_POST['feedBackSubject']) : '';
        $feedBackComment = isset($_POST['feedBackComment']) ? contrexx_input2raw($_POST['feedBackComment']) : '';
        $customerName    = isset($_POST['customerName']) ? contrexx_input2raw($_POST['customerName']) : '';
        $customerEmailId = isset($_POST['customerEmailId']) ? contrexx_input2raw($_POST['customerEmailId']) : '';
        $feedBackUrl     = isset($_POST['feedBackUrl']) ? contrexx_input2raw($_POST['feedBackUrl']) : '';

        if (isset($_POST['sendAndSave'])) {
            if (!empty($feedBackSubject) && !empty($feedBackComment)) {
                //get the hostname domain
                $domainRepo = new \Cx\Core\Net\Model\Repository\DomainRepository();
                $domain = $domainRepo->getHostDomain();
                $arrFields = array (
                    'name'         => contrexx_raw2xhtml($customerName),
                    'fromEmail'    => contrexx_raw2xhtml($customerEmailId),
                    'feedBackType' => $feedBackType != 0 ? contrexx_raw2xhtml($feedBackTypes[$feedBackType]) : '',
                    'url'          => $faqUrl,
                    'comments'     => contrexx_raw2xhtml($feedBackComment),
                    'subject'      => contrexx_raw2xhtml($feedBackSubject),
                    'firstName'    => $objUser->objUser->getProfileAttribute('firstname'),
                    'lastName'     => $objUser->objUser->getProfileAttribute('lastname'),
                    'phone'        => !$objUser->objUser->getProfileAttribute('phone_office') ? $objUser->objUser->getProfileAttribute('phone_mobile') : $objUser->objUser->getProfileAttribute('phone_office'),
                    'company'      => $objUser->objUser->getProfileAttribute('company'),
                    'toEmail'      => $recipientMailAddress,
                    'licenseName'  => $licenseName,
                    'licenseValid' => $licenseValid,
                    'licenseVersion'=> $licenseVersion,
                    'domainName'    => $domain ? $domain->getName() : ''
                );
                //send the feedBack mail
                $this->sendMail($arrFields) ? \Message::ok($_ARRAYLANG['TXT_SUPPORT_FEEDBACK_EMAIL_SEND_SUCESSFULLY']) : \Message::error($_ARRAYLANG['TXT_SUPPORT_FEEDBACK_EMAIL_SEND_FAILED']);
            } else {
                \Message::error($_ARRAYLANG['TXT_SUPPORT_ERROR_MSG_FIELDS_EMPTY']);
                $this->template->setVariable(array(
                    'TXT_SUPPORT_ERROR_CLASS_SUBJECT' => !empty($feedBackSubject) ? "" : "errBoxStyle",
                    'TXT_SUPPORT_ERROR_CLASS_COMMENT' => !empty($feedBackComment) ? "" : "errBoxStyle",
                    'SUPPORT_FEEDBACK_SUBJECT' => contrexx_raw2xhtml($feedBackSubject),
                    'SUPPORT_FEEDBACK_COMMENT' => contrexx_raw2xhtml($feedBackComment),
                ));
            }
        }
        //show FeedBack Types
        foreach ($feedBackTypes AS $key => $feedbackType) {
             $this->template->setVariable(array(
                 'SUPPORT_FEEDBACK_TYPES'          => $feedbackType,
                 'SUPPORT_FEEDBACK_SELECTED_TYPE'  => (!empty($feedBackType) && $feedBackType == $key) ? 'selected' : '',
                 'SUPPORT_FEEDBACK_ID'             => $key
             ));
            $this->template->parse('showFeedBackTypes');
        }

        $this->template->setVariable(array(
            'SUPPORT_FEEDBACK_FAQ'                  => $faqLink,
            'SUPPORT_FEEDBACK_CUSTOMER_NAME'        => $objUser->objUser->getUsername(),
            'SUPPORT_FEEDBACK_CUSTOMER_EMAIL'       => $objUser->objUser->getEmail()
        ));

        $this->template->setVariable(array(
            'TXT_SUPPORT_FEEDBACK'            => $_ARRAYLANG['TXT_SUPPORT_FEEDBACK'],
            'TXT_SUPPORT_FEEDBACK_SUBJECT'    => $_ARRAYLANG['TXT_SUPPORT_FEEDBACK_SUBJECT'],
            'TXT_SUPPORT_FEEDBACK_COMMENTS'   => $_ARRAYLANG['TXT_SUPPORT_FEEDBACK_COMMENTS']
        ));
    }

    /**
     * Send the FeedBack mail
     *
     * @param array $arrFields
     *
     * @global array $_CONFIG
     * @global array $_ARRAYLANG
     *
     * @return boolean
     */
    function sendMail($arrFields = array()) {
        global $_CONFIG, $_ARRAYLANG;

        //plain text content
        $arrFields['message'] = "{$_ARRAYLANG['TXT_SUPPORT_CONTACT_TITLE']}: \n
                                       {$_ARRAYLANG['TXT_SUPPORT_USER_FIRST_NAME']}: {$arrFields['firstName']}\n
                                       {$_ARRAYLANG['TXT_SUPPORT_USER_LAST_NAME']}: {$arrFields['lastName']}\n
                                       {$_ARRAYLANG['TXT_SUPPORT_USER_COMPANY']}: {$arrFields['company']}\n
                                       {$_ARRAYLANG['TXT_SUPPORT_USER_PHONE']}: {$arrFields['phone']}\n
                                       {$_ARRAYLANG['TXT_SUPPORT_USER_EMAIL']}: {$arrFields['fromEmail']}\n
                                       \n\n
                                       {$_ARRAYLANG['TXT_SUPPORT_FEEDBACK_MAIL']}: \n\n
                                       {$_ARRAYLANG['TXT_SUPPORT_FEEDBACK_TOPIC']}  : {$arrFields['feedBackType']} \n\n
                                       {$_ARRAYLANG['TXT_SUPPORT_FEEDBACK_SUBJECT']}        : {$arrFields['subject']} \n\n
                                       {$_ARRAYLANG['TXT_SUPPORT_FEEDBACK_COMMENTS']}        : {$arrFields['comments']} \n\n.";
        //html content
        $arrFields['message_html'] = '<div style="width:600px; font-family: arial,helvetica,sans-serif; font-size: 13px;">

    <p><strong>' . $_ARRAYLANG['TXT_SUPPORT_CONTACT_TITLE'] . '</strong></p>

    <table cellpadding="0" cellspacing="0" style="width:100%; font-size: 13px;">
        <tbody>
            <tr>
                <td valign="top" width="30%">' . $_ARRAYLANG['TXT_SUPPORT_USER_FIRST_NAME'] . '</td>
                <td>&nbsp;: ' . $arrFields['firstName'] . '</td>
            </tr>
            <tr>
                <td valign="top">' . $_ARRAYLANG['TXT_SUPPORT_USER_LAST_NAME'] . '</td>
                <td>&nbsp;: ' . $arrFields['lastName'] . '</td>
            </tr>
            <tr>
                <td valign="top">' . $_ARRAYLANG['TXT_SUPPORT_USER_COMPANY'] . '</td>
                <td>&nbsp;: ' . $arrFields['company'] . '</td>
            </tr>
            <tr>
                <td valign="top">' . $_ARRAYLANG['TXT_SUPPORT_USER_PHONE'] . '</td>
                <td>&nbsp;: ' . $arrFields['phone'] . '</td>
            </tr>
            <tr>
                <td valign="top">' . $_ARRAYLANG['TXT_SUPPORT_USER_EMAIL'] . '</td>
                <td>&nbsp;: ' . $arrFields['fromEmail'] . '</td>
            </tr>
        </tbody>
    </table>

    <p><strong>'.$_ARRAYLANG['TXT_SUPPORT_LICENSE_TITLE'].'</strong></p>

    <table cellpadding ="0" cellspacing ="0" style="width: 100%; font-size: 13px;">
        <tbody>
            <tr>
                <td valign="top" width="30%">' . $_ARRAYLANG['TXT_SUPPORT_DOMAIN_NAME'] . '</td>
                <td>&nbsp;: ' . $arrFields['domainName'] . '</td>
            </tr>
            <tr>
                <td valign="top" >' . $_ARRAYLANG['TXT_SUPPORT_LICENSE_NAME'] . '</td>
                <td>&nbsp;: ' . $arrFields['licenseName'] . '</td>
            </tr>
            <tr>
                <td valign="top">' . $_ARRAYLANG['TXT_SUPPORT_LICENSE_VALID_UNTIL'] . '</td>
                <td>&nbsp;: ' . $arrFields['licenseValid'] . '</td>
            </tr>
            <tr>
                <td valign="top">' . $_ARRAYLANG['TXT_SUPPORT_LICENSE_VERSION'] . '</td>
                <td>&nbsp;: ' . $arrFields['licenseVersion'] . '</td>
            </tr>
        </tbody>

    </table>

    <p><strong>' . $_ARRAYLANG['TXT_SUPPORT_FEEDBACK_MAIL'] . '</strong></p>

    <table cellpadding="0" cellspacing="0" style="width:100%; font-size: 13px;">
        <tbody>
            <tr>
                <td valign="top" width="30%">' . $_ARRAYLANG['TXT_SUPPORT_FEEDBACK_TOPIC'] . '</td>
                <td>&nbsp;: ' . $arrFields['feedBackType'] . '</td>
            </tr>
            <tr>
                <td valign="top">' . $_ARRAYLANG['TXT_SUPPORT_FEEDBACK_SUBJECT'] . '</td>
                <td>&nbsp;: ' . $arrFields['subject'] . '</td>
            </tr>
            <tr>
                <td valign="top">' . $_ARRAYLANG['TXT_SUPPORT_FEEDBACK_COMMENTS'] . '</td>
                <td>&nbsp;: ' . nl2br($arrFields['comments']) . '</td>
            </tr>
        </tbody>
    </table>
</div>';

        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();
        $objMail->SetFrom($arrFields['fromEmail'], $arrFields['name']);
        $objMail->Subject = 'Cloudrexx - ' . $_ARRAYLANG['TXT_SUPPORT_EMAIL_MESSAGE_SUBJECT'];
        $objMail->AddAddress($arrFields['toEmail']);
        $objMail->IsHTML(true);
        $objMail->Body = $arrFields['message_html'];
        $objMail->AltBody = $arrFields['message'];

        return $objMail->Send();
    }

}
