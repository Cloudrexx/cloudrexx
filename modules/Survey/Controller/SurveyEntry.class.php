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
 * Class SurveyEntry
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version       $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_survey
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\Survey\Controller;
/**
 * Class SurveyEntry
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version       $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_survey
 * @todo        Edit PHP DocBlocks!
 */
class SurveyEntry extends SurveyLibrary
{
    public $id;
    public $title;
    public $description;
    public $surveyType;
    public $textBeginSurvey;
    public $textBeforeSubscriberInfo;
    public $textBelowSubmit;
    public $textFeedbackMsg;
    public $isHome;
    public $salutation;
    public $nickname;
    public $forename;
    public $surname;
    public $agegroup;
    public $email;
    public $phone;
    public $street;
    public $zip;
    public $city;

    public $okMsg = array();
    public $errorMsg = array();

    public $additionalFields = array(
        'salutation',
        'nickname',
        'forename',
        'surname',
        'agegroup',
        'email',
        'phone',
        'street',
        'zip',
        'city'
    );

    function get()
    {
        global $objDatabase;

        $query = "SELECT
                    `title`,
                    `UserRestriction`,
                    `description`,
                    `textAfterButton`,
                    `text1`,
                    `text2`,
                    `thanksMSG`,
                    `isHomeBox`,
                    `additional_salutation`,
                    `additional_nickname`,
                    `additional_forename`,
                    `additional_surname`,
                    `additional_agegroup`,
                    `additional_email`,
                    `additional_phone`,
                    `additional_street`,
                    `additional_zip`,
                    `additional_city`
                 FROM
                    `".DBPREFIX."module_survey_surveygroup`
                 WHERE
                    `id` = {$this->id}";

         $objResult = $objDatabase->Execute($query);

         if ($objResult) {
            $this->title                    = $objResult->fields['title'];
            $this->surveyType               = $objResult->fields['UserRestriction'];
            $this->description              = $objResult->fields['description'];
            $this->textBeginSurvey          = $objResult->fields['textAfterButton'];
            $this->textBeforeSubscriberInfo = $objResult->fields['text1'];
            $this->textBelowSubmit          = $objResult->fields['text2'];
            $this->textFeedbackMsg          = $objResult->fields['thanksMSG'];
            $this->isHome                   = $objResult->fields['isHomeBox'];
            $this->salutation               = $objResult->fields['additional_salutation'];
            $this->nickname                 = $objResult->fields['additional_nickname'];
            $this->forename                 = $objResult->fields['additional_forename'];
            $this->surname                  = $objResult->fields['additional_surname'];
            $this->agegroup                 = $objResult->fields['additional_agegroup'];
            $this->email                    = $objResult->fields['additional_email'];
            $this->phone                    = $objResult->fields['additional_phone'];
            $this->street                   = $objResult->fields['additional_street'];
            $this->zip                      = $objResult->fields['additional_zip'];
            $this->city                     = $objResult->fields['additional_city'];
         }

    }

    function save()
    {
        global $objDatabase, $_ARRAYLANG;

        $arrFields = array(
            'title'                 => $this->title,
            'UserRestriction'       => $this->surveyType,
            'description'           => $this->description,
            'textAfterButton'       => $this->textBeginSurvey,
            'text1'                 => $this->textBeforeSubscriberInfo,
            'text2'                 => $this->textBelowSubmit,
            'thanksMSG'             => $this->textFeedbackMsg,
            'isHomeBox'             => (int) $this->isStandred(),
            'additional_salutation' => $this->salutation,
            'additional_nickname'   => $this->nickname,
            'additional_forename'   => $this->forename,
            'additional_surname'    => $this->surname,
            'additional_agegroup'   => $this->agegroup,
            'additional_email'      => $this->email,
            'additional_phone'      => $this->phone,
            'additional_street'     => $this->street,
            'additional_zip'        => $this->zip,
            'additional_city'       => $this->city
        );

        if (empty($this->id)) {
            $query = \SQL::insert('module_survey_surveygroup', $arrFields, array('escape' => true));
        } else {
            $arrFields['updated'] = date("Y-m-d H:i:s");
            $query = \SQL::update('module_survey_surveygroup', $arrFields, array('escape' => true))." WHERE `id` = {$this->id}";
        }

        // echo $query;

        if ($objDatabase->Execute($query)) {
            $this->okMsg[] = empty($this->id) ? $_ARRAYLANG['TXT_SURVEY_ADDED_SUC_TXT'] : $_ARRAYLANG['TXT_SURVEY_UPDATE_SUC_TXT'];
            return true;
        } else {
            $this->errorMsg[] = $_ARRAYLANG['TXT_SURVEY_ERROR_IN_SAVING'];
            return true;
        }
    }

    function isStandred()
    {
        global $objDatabase;

        $objResult = $objDatabase->Execute('SELECT 1 FROM `'.DBPREFIX.'module_survey_surveygroup` WHERE isHomeBox="1"');

        return !$objResult->RecordCount();
    }

    function validate()
    {
        global $_ARRAYLANG;

        if (trim($this->title) == '') {
            $this->errorMsg[] = $_ARRAYLANG['TXT_SURVEY_ENTER_TITLE_ERR'];
        }

        return empty($this->errorMsg) ? true : false;
    }
}
