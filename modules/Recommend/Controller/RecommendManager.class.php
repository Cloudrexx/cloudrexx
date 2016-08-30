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
 * Class RecommendManager
 *
 * Recommend module admin class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_recommend
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\Recommend\Controller;
/**
 * Class RecommendManager
 *
 * Recommend module admin class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_recommend
 * @todo        Edit PHP DocBlocks!
 */
class RecommendManager extends RecommendLibrary
{
    var $_objTpl;
    var $pageTitle='';
    var $strErrMessage = '';
    var $strOkMessage = '';

    /**
     * Constructor
     */
    function RecommendManager()
    {
        $this->__construct();
    }

    private $act = '';

    /**
     * PHP5 constructor
     *
     * @global \Cx\Core\Html\Sigma
     * @global array
     */
    function __construct()
    {
        global $objTemplate, $_ARRAYLANG;

        $this->_objTpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Recommend/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
    }
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable("CONTENT_NAVIGATION","<a href='?cmd=Recommend' class='".($this->act == '' ? 'active' : '')."'>".$_ARRAYLANG['TXT_SETTINGS']."</a>");
    }

    /**
     * Get content page
     *
     * @access public
     */
    function getPage()
    {
        global $objTemplate;

        if (!isset($_GET['act'])) {
            $_GET['act'] = '';
        }

        switch ($_GET['act']) {
            case "saveSettings":
                $this->_saveSettings();
                break;
            default:
                $this->_showSettings();
                break;
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'             => $this->pageTitle,
            'CONTENT_OK_MESSAGE'        => $this->strOkMessage,
            'CONTENT_STATUS_MESSAGE'    => $this->strErrMessage,
            'ADMIN_CONTENT'             => $this->_objTpl->get()
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }

    /**
     * Shows the settings page
     */
    function _showSettings()
    {
        global $_ARRAYLANG, $_FRONTEND_LANGID;

        $this->pageTitle = $_ARRAYLANG['TXT_SETTINGS'];

        $sal_female = $this->getFemaleSalutation($_FRONTEND_LANGID);
        $sal_male   = $this->getMaleSalutation($_FRONTEND_LANGID);
        $subject    = $this->getMessageSubject($_FRONTEND_LANGID);
        $body       = $this->getMessageBody($_FRONTEND_LANGID);

        $this->_showForm($sal_female, $sal_male, $subject, $body);
    }

    /**
     * Save settings
     *
     * Checks if the data is valid. If not,
     * shows errors and a form with
     * the inputted values
     */
    function _saveSettings()
    {
        global $_ARRAYLANG, $objDatabase, $_FRONTEND_LANGID;

        $error = false;

        if (empty($_POST['subject'])) {
            $error = true;
        }

        if (empty($_POST['body'])) {
            $error = true;
        }

        if (empty($_POST['salutation_female'])) {
            $error = true;
        }

        if (empty($_POST['salutation_male'])) {
            $error = true;
        }

        if ($error) {
            $this->pageTitle = $_ARRAYLANG['TXT_SETTINGS'];
            $this->strErrMessage = $_ARRAYLANG['TXT_ERROR'];

            $this->_showForm('', '');
        } else {
            $salutation_female  = $_POST['salutation_female'];
            $salutation_male    = $_POST['salutation_male'];
            $subject            = $_POST['subject'];
            $body               = $_POST['body'];

            $this->_saveValue('subject', $subject);
            $this->_saveValue('body', $body);
            $this->_saveValue('salutation_female', $salutation_female);
            $this->_saveValue('salutation_male', $salutation_male);

            $this->strOkMessage = $_ARRAYLANG['TXT_STATUS_OK'];

            $sal_female = $this->getFemaleSalutation($_FRONTEND_LANGID);
            $sal_male   = $this->getMaleSalutation($_FRONTEND_LANGID);
            $subject    = $this->getMessageSubject($_FRONTEND_LANGID);
            $body       = $this->getMessageBody($_FRONTEND_LANGID);

            $this->_showForm($sal_female, $sal_male, $subject, $body);
        }
    }


    /**
     * Shows the form
     */
    function _showForm($sal_female, $sal_male, $subject, $body)
    {
        global $_ARRAYLANG;

        $this->_objTpl->loadTemplateFile('settings.html',true,true);

        $this->_objTpl->setVariable(array(
            "TXT_SALUTATION_FEMALE" => $_ARRAYLANG['TXT_SALUTATION_FEMALE'],
            "TXT_SALUTATION_MALE"   => $_ARRAYLANG['TXT_SALUTATION_MALE'],
            "TXT_TITLE"         => $_ARRAYLANG['TXT_RECOMMEND'],
            "TXT_SUBJECT"       => $_ARRAYLANG['TXT_SUBJECT'],
            "TXT_BODY"          => $_ARRAYLANG['TXT_BODY'],
            "TXT_SUBMIT"        => $_ARRAYLANG['TXT_SUBMIT'],
            "TXT_AVAILABLE_VARS"    => $_ARRAYLANG['TXT_AVAILABLE_VARS'],
            "TXT_SENDERNAME"    => $_ARRAYLANG['TXT_SENDERNAME'],
            "TXT_SENDERMAIL"    => $_ARRAYLANG['TXT_SENDERMAIL'],
            "TXT_RECEIVERNAME"  => $_ARRAYLANG['TXT_RECEIVERNAME'],
            "TXT_RECEIVERMAIL"  => $_ARRAYLANG['TXT_RECEIVERMAIL'],
            "TXT_URL"           => $_ARRAYLANG['TXT_URL'],
            "TXT_COMMENT"       => $_ARRAYLANG['TXT_COMMENT'],
            "TXT_SALUTATION"    => $_ARRAYLANG['TXT_SALUTATION'],
            ));

        $this->_objTpl->setVariable(array(
            "SETTINGS_SALUTATION_FEMALE"    => $sal_female,
            "SETTINGS_SALUTATION_MALE"      => $sal_male,
            "SETTINGS_SUBJECT"              => $subject,
            "SETTINGS_BODY"                 => $body
            ));

        $this->_objTpl->parse();
    }

    function _saveValue($name, $value)
    {
        global $_FRONTEND_LANGID, $objDatabase;

        // check if the dataset for the body for the current lang exists already
        $query = "SELECT * FROM ".DBPREFIX."module_recommend WHERE name = '$name' AND lang_id = $_FRONTEND_LANGID";
        $objResult = $objDatabase->Execute($query);
        if ($objResult->RecordCount() > 0 ) {
            // Dataset exists already
            $query = "UPDATE ".DBPREFIX."module_recommend
                     SET value='".addslashes($value)."'
                     WHERE name = '$name' AND lang_id = $_FRONTEND_LANGID";
        } else {
            // Dataset doesn't exist
            $query = "INSERT INTO ".DBPREFIX."module_recommend
                      (name, value, lang_id) VALUES
                      ('$name', '".addslashes($value)."', '$_FRONTEND_LANGID')";
        }
        $objDatabase->Execute($query);
    }

}
