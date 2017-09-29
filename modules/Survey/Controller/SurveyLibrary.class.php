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
 * SurveyLibrary
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_survey
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Survey\Controller;

/**
 * SurveyLibrary
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_survey
 * @todo        Edit PHP DocBlocks!
 */
class SurveyLibrary {

    var $_intLangId;
    var $_arrSettings           = array();
    var $_arrSurveyTranslations = array();
    var $_arrSurveyValues       = array();

        /**
         * module name
         *
         * @var string
         */
        public $moduleName    = 'Survey';
        public $moduleLangVar = 'SURVEY';

    /**
     * Constructor
     */
    function __construct()
    {
        $this->_arrLanguages         = $this->createLanguageArray();
        $this->_arrSettings        = $this->createSettingsArray();
        $this->_arrSurveyTranslations   = $this->createSurveyTranslationArray();
        $this->_arrSurveyValues        = $this->createSurveyValuesArray();
    }


    /**
     * Creates an array containing all frontend-languages.
     *
     * Contents:
     * $arrValue[$langId]['short']        =>    For Example: en, de, fr, de-CH, ...
     * $arrValue[$langId]['long']        =>    For Example: 'English', 'Deutsch', 'French', ...
     *
     * @return    array        $arrReturn
     */
    function createLanguageArray() {

        $arrReturn = array();

        foreach (\FWLanguage::getActiveFrontendLanguages() as $frontendLanguage) {
            $arrReturn[$frontendLanguage['id']] = array(
                'short' =>  stripslashes($frontendLanguage['lang']),
                'long'  =>  htmlentities(stripslashes($frontendLanguage['name']),ENT_QUOTES, CONTREXX_CHARSET)
            );
        }

        return $arrReturn;
    }


    /**
     * Create an array containing all settings. Exapmle: $arrSettings['setname']
     *
     * @global     object        $objDatabase
     * @return     array        $arrReturn
     */
    function createSettingsArray() {
        global $objDatabase;

        $arrReturn = array();

        $objResult = $objDatabase->Execute('SELECT name,
                                                           value
                                                           FROM    '.DBPREFIX.'module_survey_settings');
        if ($objResult) {
                    while (!$objResult->EOF) {
                        $arrReturn[$objResult->fields['name']] = $objResult->fields['value'];
                        $objResult->MoveNext();
                    }
                }

        return $arrReturn;
    }


    /**
     * Creates an array containing all translations of the surveys. Example: $arrValue[$surveyId][$langId].
     *
     * @global     object        $objDatabase
     * @return    array        $arrReturn
     */
    function createSurveyTranslationArray() {
            global $objDatabase;

            $arrReturn = array();

            $objResult = $objDatabase->Execute('SELECT group_id,
                                                    lang_id,
                                                    subject
                                                    FROM '.DBPREFIX.'module_survey_groups_lang
                                                    ORDER BY group_id ASC');
            if ($objResult) {
                while (!$objResult->EOF) {
                    $arrReturn[$objResult->fields['group_id']][$objResult->fields['lang_id']] = contrexx_remove_script_tags($objResult->fields['subject']);
                    $objResult->MoveNext();
                }
            }

            return $arrReturn;
    }


    /**
     * Creates an array containing all values of the surveys. Example: $arrValue[randomIndex]['xxx'].
     *
     * @global     object        $objDatabase
     * @return    array        $arrReturn
     */
    function createSurveyValuesArray() {
            global $objDatabase;

            $arrReturn = array();

            $objResult = $objDatabase->Execute('SELECT id,
                                                       redirect,
                                                       created,
                                                       lastvote,
                                                       participant,
                                                       isActive,
                                                       isExtended,
                                                       isCommentable,
                                                       isHomeBox
                                                       FROM    '.DBPREFIX.'module_survey_groups
                                                       ORDER BY created DESC');

            $intIndex = 0;
            if ($objResult) {
                while (!$objResult->EOF) {
                    $arrReturn[$intIndex] = array('id'          => $objResult->fields['id'],
                                                'redirect'      => contrexx_remove_script_tags($objResult->fields['redirect']),
                                                'created'       => date(ASCMS_DATE_FORMAT, $objResult->fields['created']),
                                                'lastvote'      => (intval($objResult->fields['lastvote']) == 0) ? 'Keine Teilnehmer.' : date(ASCMS_DATE_FORMAT,$objResult->fields['lastvote']),
                                                'participant'   => intval($objResult->fields['participant']),
                                                'isActive'      => intval($objResult->fields['isActive']),
                                                'isExtended'    => intval($objResult->fields['isExtended']),
                                                'isCommentable' => intval($objResult->fields['isCommentable']),
                                                'isHomeBox'     => intval($objResult->fields['isHomeBox'])
                                                );
                    ++$intIndex;
                    $objResult->MoveNext();
                }
            }

            return $arrReturn;
    }


    /**
     * Get the index-value for the surveyValueArray for a desired survey.
     *
     * @param    integer        $intSurveyId
     * @return    integer        Index for the surveyValueArray. If the id was not found, -1 will be returned.
     */
    function getSurveyArrayIndex($intSurveyId) {
            $intSurveyId = intval($intSurveyId);

            foreach ($this->_arrSurveyValues as $intIndex => $arrValues) {
                if ($intSurveyId == $arrValues['id']) {
                    return $intIndex;
                }
            }

            return -1;
    }
}
