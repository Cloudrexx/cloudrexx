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
 * Class SurveyQuestion
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
 * Class SurveyQuestion
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Kaelin <thomas.kaelin@comvation.com>
 * @version       $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_survey
 * @todo        Edit PHP DocBlocks!
 */
class SurveyQuestion
{
    public $id;
    public $surveyId;
    public $questionType;
    public $isCommentable;
    public $question;
    public $questionRow;
    public $questionChoice;
    public $questionAnswers;
    public $position;

    function get()
    {
        global $objDatabase;

        if (empty($this->id)) {
            return false;
        }

        $query = "SELECT
                        *
                      FROM
                        `".DBPREFIX."module_survey_surveyQuestions`
                      WHERE
                        `id` = {$this->id}";
        $objResult = $objDatabase->Execute($query);

        if ($objResult) {
            $this->surveyId      = (int) $objResult->fields['survey_id'];
            $this->questionType  = (int) $objResult->fields['QuestionType'];
            $this->isCommentable = (int) $objResult->fields['isCommentable'];
            $this->question      = $this->questionType != 7 ? $objResult->fields['Question'] : '';
            $this->questionRow   = $this->questionType == 7 ? $objResult->fields['Question'] : '';
        }

        $query = "SELECT
                        *
                      FROM
                        `".DBPREFIX."module_survey_surveyAnswers`
                      WHERE
                        `question_id` = {$this->id}";
        $objResult = $objDatabase->Execute($query);
        $answers   = array();
        if ($objResult) {
            while (!$objResult->EOF) {
                $answers[] = $objResult->fields['answer'];
                $objResult->MoveNext();
            }
        }
        $this->questionAnswers = implode(PHP_EOL, $answers);

        $query = "SELECT
                        *
                      FROM
                        `".DBPREFIX."module_survey_columnChoices`
                      WHERE
                        `question_id` = {$this->id}";
        $objResult = $objDatabase->Execute($query);
        $choices   = array();
        if ($objResult) {
            while (!$objResult->EOF) {
                $choices[] = $objResult->fields['choice'];
                $objResult->MoveNext();
            }
        }
        $this->questionChoice = implode(PHP_EOL, $choices);

    }

    function save()
    {
        global $objDatabase;

        if (empty($this->surveyId)) {
            return false;
        }

        $this->question = $this->questionType != 7 ? $this->question : $this->questionRow;

        if (in_array($this->questionType, array(3, 4))) {
            $options    = explode ("\n", $this->questionChoice);
            $choices    = explode ("\n", $this->questionAnswers);
            $colChoices = implode($choices, ";");
            $vote       = json_encode($colChoices);

            $vote = array();
            foreach ($colChoices as $key => $value) {
                $vote[$key] = 0;
            }
            $vote = json_encode($vote);
        } else {
            $options    = explode ("\n", $this->questionAnswers);
            $choices    = explode ("\n", $this->questionChoice);
            $colChoices = "";
            $vote       = 0;
        }

        if(in_array($this->questionType, array(5, 7))) {
            $options[0] = "Answer";
        }

        $arrFields = array(
            'survey_id'     => $this->surveyId,
            'isCommentable' => $this->isCommentable,
            'QuestionType'  => $this->questionType,
            'Question'      => $this->question,
            'column_choice' => $colChoices
        );

        if (empty($this->id)) {
            $arrFields['pos'] = 0;
            $query = \SQL::insert('module_survey_surveyQuestions', $arrFields, array('escape' => true));
        } else {
            $query = \SQL::update('module_survey_surveyQuestions', $arrFields, array('escape' => true))." WHERE `id` = {$this->id}";
        }

        if ($objDatabase->Execute($query)) {
            if (empty($this->id)) {
                $this->id = $objDatabase->INSERT_ID();
            }
        }

        $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_surveyAnswers` WHERE question_id = '.$this->id;
        $objDatabase->Execute($deleteQuery);

        $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_columnChoices` WHERE question_id = '.$this->id;
        $objDatabase->Execute($deleteQuery);

        if (!empty($options)) {
            foreach (array_filter($options) as $option) {
                $arrFields = array(
                    'question_id' => $this->id,
                    'answer'      => $option,
                    'votes'       => $vote
                );
                $query = \SQL::insert('module_survey_surveyAnswers', $arrFields, array('escape' => true));
                $objDatabase->Execute($query);
            }
        }

        if (!empty($colChoices)) {
            foreach (array_filter($colChoices) as $choice) {
                $arrFields = array(
                    'question_id' => $this->id,
                    'choice'      => $choice
                );
                $query = \SQL::insert('module_survey_columnChoices', $arrFields, array('escape' => true));
                $objDatabase->Execute($query);
            }
        }

    }

    function updatePosition()
    {
        global $objDatabase;

        $query = \SQL::update('module_survey_surveyQuestions', array('pos' => $this->position), array('escape' => true))." WHERE `id` = {$this->id}";
        $objDatabase->Execute($query);
    }

    function delete()
    {
        global $objDatabase;

        if (!empty($this->id)) {
            $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_surveyQuestions` WHERE id = '.$this->id;
            $objDatabase->Execute($deleteQuery);

            $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_surveyAnswers` WHERE question_id = '.$this->id;
            $objDatabase->Execute($deleteQuery);

            $deleteQuery = 'DELETE FROM `'.DBPREFIX.'module_survey_columnChoices` WHERE question_id = '.$this->id;
            $objDatabase->Execute($deleteQuery);
        }
    }

}
