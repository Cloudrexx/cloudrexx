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
 * jobsManager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_jobs
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Jobs\Controller;

/**
 * jobsManager
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_jobs
 */
class JobsManager extends JobsLibrary
{
    /**
     * @var    \Cx\Core\Html\Sigma
     */
    public $_objTpl;
    public $langId;

    function __construct($template)
    {
        global $objInit;

        $this->_objTpl = $template;
        $this->langId=$objInit->userFrontendLangId;
    }

    /**
    * Do the requested action
    * @return    string    parsed content
    */
    function getJobsPage()
    {
        if (!isset($_GET['act'])) {
            $_GET['act']='';
        }

        switch($_GET['act']) {
            case 'Job':
                $this->modifyJob();
                break;
            case 'delete':
                $this->delete();
                break;
            case 'cat':
                $this->manageCategories();
                break;
            case 'loc':
                $this->manageLocations();
                break;
            case 'delcat':
                $this->deleteCat();
                break;
            case 'delloc':
                $this->deleteLoc();
                break;
            case 'changeStatus':
                $this->changeStatus();
                break;
            case 'settings':
                $this->settings();
                break;
            default:
                $this->overview();
        }
    }


    /**
    * List the jobs
    * @global     object   $objDatabase
    * @param     integer   $newsid
    * @param     string    $what
    * @return    string    $output
    */
    function overview()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        //set the language variable
        \ContrexxJavascript::getInstance()->setVariable(array(
            'operationSuccessful' => $_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL'],
            'operationFailed'     => $_ARRAYLANG['TXT_JOBS_RECORD_UPDATE_FAILED']
        ),'contrexx/lang');

        // initialize variables
        $i=0;
        $jobslocationform = '';
        $location = '';
        $docFilter = '';
        $locationFilter = ' WHERE ';
        $this->_objTpl->setVariable(array(
            'TXT_EDIT_JOBS_MESSAGE'      => $_ARRAYLANG['TXT_EDIT_DOCUMENTS'],
            'TXT_EDIT_JOBS_ID'           => $_ARRAYLANG['TXT_DOCUMENT_ID'],
            'TXT_ARCHIVE'                => $_ARRAYLANG['TXT_ARCHIVE'],
            'TXT_DATE'                   => $_ARRAYLANG['TXT_DATE'],
            'TXT_TITLE'                  => $_ARRAYLANG['TXT_TITLE'],
            'TXT_USER'                   => $_ARRAYLANG['TXT_USER'],
            'TXT_LAST_EDIT'              => $_ARRAYLANG['TXT_LAST_EDIT'],
            'TXT_ACTION'                 => $_ARRAYLANG['TXT_ACTION'],
            'TXT_CATEGORY'               => $_ARRAYLANG['TXT_CATEGORY'],
            'TXT_CONFIRM_DELETE_DATA'    => $_ARRAYLANG['TXT_DOCUMENT_DELETE_CONFIRM'],
            'TXT_ACTION_IS_IRREVERSIBLE' => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_SELECT_ALL'             => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_REMOVE_SELECTION'       => $_ARRAYLANG['TXT_REMOVE_SELECTION'],
            'TXT_EDIT'                   => $_ARRAYLANG['TXT_EDIT'],
            'TXT_TEMPLATE'               => $_ARRAYLANG['TXT_TEMPLATE'],
            'TXT_MARKED'                 => $_ARRAYLANG['TXT_MARKED'],
            'TXT_ACTIVATE'               => $_ARRAYLANG['TXT_ACTIVATE'],
            'TXT_DEACTIVATE'             => $_ARRAYLANG['TXT_DEACTIVATE'],
            'TXT_STATUS'                 => $_ARRAYLANG['TXT_STATUS'],
            'TXT_AUTHOR'                 => $_ARRAYLANG['TXT_AUTHOR'],
            'TXT_JOBS_SEARCH'            => $_ARRAYLANG['TXT_JOBS_SEARCH'],
            'TXT_JOBS_OVERVIEW_HOT'      => $_ARRAYLANG['TXT_JOBS_OVERVIEW_HOT']
        ));

        //Get the settings value from DB
        $settings = $this->getSettings();

        //return if location fields are not activated in the backend
        if (isset($settings['show_location_fe']) && $settings['show_location_fe'] == 1) {
            if (    isset($_REQUEST['location']) 
                &&  is_numeric($_REQUEST['location'])
            ) {
                $location = $_REQUEST['location'];
                $locationFilter = ", `".DBPREFIX."module_jobs_rel_loc_jobs` AS rel WHERE rel.job = n.id AND rel.location = '".$location."' AND ";
            }
            $jobslocationform =
                '<select name="location">'.
                '<option selected="selected" value="">'.
                $_ARRAYLANG['TXT_LOCATION'].'</option>'.
                $this->getLocationMenu($location).
                '</select>';
        }

        // parse paid filter
        $jobsPaidOptions = array(
            ''          => $_ARRAYLANG['TXT_JOBS_PAID'],
            'paid'      => $_ARRAYLANG['TXT_JOBS_PAID_LABEL'],
            'non_paid'  => $_ARRAYLANG['TXT_JOBS_NON_PAID_LABEL'],
        );
        $paidFilter = '';
        $jobsPaidSelection = '';
        if (
            !empty($_REQUEST['jobs_paid']) &&
            in_array($_REQUEST['jobs_paid'], array_keys($jobsPaidOptions))
        ) {
            $jobsPaidSelection = $_REQUEST['jobs_paid'];

            if ($jobsPaidSelection == 'paid') {
                $paidFilter = " n.paid='1' AND ";
            } else {
                $paidFilter = " n.paid='0' AND ";
            }
        }
        $paidForm = '<select name="jobs_paid">';
        foreach ($jobsPaidOptions as $option => $label) {
            $selected = '';
            if ($jobsPaidSelection == $option) {
                $selected = ' selected="selected" ';
            }
            $paidForm .= '<option value="' . $option . '"' . $selected . '>' .
                $label . '</option>';
        }
        $paidForm .= '</select>';

        //Hide the column 'Hot' if the settings options 'templateIntegration' and 'sourceOfJobs' are active
        $isHotOfferAvailable = (    isset($settings['templateIntegration']) 
                                &&  ($settings['templateIntegration'] == 1) 
                                &&  isset($settings['sourceOfJobs']) 
                                &&  ($settings['sourceOfJobs'] == 'manual')
                               );
        if (!$isHotOfferAvailable) {
            $this->_objTpl->hideBlock('jobs_modify_show_hot_offer_label');
        }
        $this->_objTpl->setVariable('JOBS_OVERVIEW_COLSPAN', !$isHotOfferAvailable ? 9 : 10);

        $category = '';
        if (isset($_REQUEST['category']) &&
                is_numeric($_REQUEST['category'])) {
            $category = $_REQUEST['category'];
            $docFilter = " n.catid='$category' AND ";
        }
        $jobscategoryform =
            '<select name="category">'.
            '<option selected="selected" value="">'.
            $_ARRAYLANG['TXT_CATEGORY'].'</option>'.
            $this->getCategoryMenu($this->langId, $category).
            '</select>';
        $this->_objTpl->setVariable(array(
            'JOBS_CATEGORY_FORM' => $jobscategoryform,
            'JOBS_LOCATION_FORM' => $jobslocationform,
            'JOBS_PAID_FORM' => $paidForm,
            'TXT_SUBMIT' => $_ARRAYLANG['TXT_SUBMIT']
        ));
        $this->_objTpl->setGlobalVariable(array(
            'TXT_DELETE'    => $_ARRAYLANG['TXT_DELETE']
        ));
        $query = "SELECT n.id AS jobsId, n.date, n.changelog,
                         n.title, n.status, n.author,
                         n.lang,
                         nc.name AS catname,
                         n.userid, n.hot, n.paid
                    FROM ".DBPREFIX."module_jobs_categories AS nc,
                         ".DBPREFIX."module_jobs AS n
                         $locationFilter
                     n.lang=$this->langId
                     AND $docFilter $paidFilter nc.catid=n.catid
                   ORDER BY " . ($isHotOfferAvailable ? 'n.hot DESC,' : '') . " n.id DESC";
        $objResult = $objDatabase->Execute($query);
        $count = $objResult->RecordCount();
        $pos = (isset($_GET['pos'])) ? intval($_GET['pos']) : 0;
        $paging = ($count>intval($_CONFIG['corePagingLimit'])) ? getPaging($count, $pos, "&cmd=Jobs&location=".$location."&category=".$category."&jobs_paid=".$jobsPaidSelection."&", $_ARRAYLANG['TXT_DOCUMENTS '],true) : "";
        $objResult = $objDatabase->SelectLimit($query, $_CONFIG['corePagingLimit'],$pos);
        if (!$objResult || $objResult->EOF) {
            $this->_objTpl->hideBlock('row');
            return;
        }
        // get array containing the active locale ids
        $activeLangIds = \FWLanguage::getIdArray('frontend');

        // fetch ID-list of associated flags
        $associatedFlagIds = array();
        if (!empty($settings['use_flags'])) {
            $associatedFlagIds = $this->getFlagAssociations();
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            $flagRepo = $cx->getDb()->getEntityManager()->getRepository(
                'Cx\Modules\Jobs\Model\Entity\Flag'
            );
        }

        while ($objResult !== false && !$objResult->EOF) {
            // check if the job has assigned an existing language
            if (!in_array($objResult->fields['lang'], $activeLangIds)) {
                $objResult->MoveNext();
                continue;
            }
            $id = $objResult->fields['jobsId'];
            $statusPicture = ($objResult->fields['status']==1) ? "status_green.gif" : "status_red.gif";
            $jobUser = \FWUser::getFWUserObject()->objUser->getUser($objResult->fields['userid']);
            $username = $_ARRAYLANG['TXT_ACCESS_UNKNOWN'];
            if ($jobUser) {
                $username = $jobUser->getUsername();
            }
            $this->_objTpl->setVariable(array(
                'JOBS_ID'         => $id,
                'JOBS_DATE'       => date(ASCMS_DATE_FORMAT, $objResult->fields['date']),
                'JOBS_TITLE'      => stripslashes($objResult->fields['title']),
                'JOBS_AUTHOR'      => stripslashes($objResult->fields['author']),
                'JOBS_USER'       => $username,
                'JOBS_CHANGELOG'  => date(ASCMS_DATE_FORMAT, $objResult->fields['changelog']),
                'JOBS_PAGING'     => $paging,
                'JOBS_CLASS'      => (++$i % 2 ? "row2" : "row1"),
                'JOBS_CATEGORY'   => $objResult->fields['catname'],
                'JOBS_STATUS'     => $objResult->fields['status'],
                'JOBS_STATUS_PICTURE' => $statusPicture,
                'TXT_TEMPLATE'    => $_ARRAYLANG['TXT_TEMPLATE'],
                'TXT_EDIT'    => $_ARRAYLANG['TXT_EDIT'],
                'JOBS_OVERVIEW_HOT_OFFER' => ($objResult->fields['hot'] == 1) ? 'checked=checked' : ''
            ));
            if (!$isHotOfferAvailable) {
                $this->_objTpl->hideBlock('jobs_overview_show_hot_offer');
            }
            if ($objResult->fields['paid']) {
                $this->_objTpl->setVariable(array(
                    'TXT_JOBS_PAID_LABEL'   => $_ARRAYLANG['TXT_JOBS_PAID_LABEL'],
                ));
                $this->_objTpl->touchBlock('jobs_overview_paid');
            } else {
                $this->_objTpl->hideBlock('jobs_overview_paid');
            }

            $flagsParsed = false;
            if (isset($associatedFlagIds[$id])) {
                foreach ($associatedFlagIds[$id] as $flagId) {
                    $flag = $flagRepo->findOneById($flagId);
                    if (!$flag) {
                        continue;
                    }
                    if (!$flag->getIcon()) {
                        continue;
                    }
                    $this->_objTpl->setVariable(array(
                        'JOBS_FLAG_ICON_SRC'=> $flag->getIcon(),
                        'JOBS_FLAG_NAME'    => contrexx_raw2xhtml($flag->getName()),
                    ));
                    $this->_objTpl->parse('jobs_flags_list');
                    $flagsParsed = true;
                }
            }
            if (!$flagsParsed) {
                $this->_objTpl->hideBlock('jobs_flags_list');
            }

            $this->_objTpl->parse('row');
            $objResult->MoveNext();
        }
    }


    function _getSortingDropdown($catID, $sorting = 'alpha')
    {
        global $_ARRAYLANG;

        return '
            <select name="sortStyle['.$catID.']">
                <option value="alpha" '.($sorting == 'alpha' ? 'selected="selected"' : '').' >'.$_ARRAYLANG['TXT_JOBS_SORTING_ALPHA'].'</option>
                <option value="date" '.($sorting == 'date' ? 'selected="selected"' : '').'>'.$_ARRAYLANG['TXT_JOBS_SORTING_DATE'].'</option>
                <option value="date_alpha" '.($sorting == 'date_alpha' ? 'selected="selected"' : '').'>'.$_ARRAYLANG['TXT_JOBS_SORTING_DATE_ALPHA'].'</option>
            </select>
        ';
    }


    function getLocationTable($jobID)
    {
        global $objDatabase, $_ARRAYLANG;

        //return if location fields are not activated in the backend
        $settings = $this->getSettings();
        if (    !isset($settings['show_location_fe']) 
            ||  ($settings['show_location_fe'] == 0)
        ) {
            $this->_objTpl->hideBlock('modify_location');
            return ;
        }

        $AssociatedLocations = '';
        $notAssociatedLocations = '';
        $this->_objTpl->setVariable(array(
            'TXT_LOCATION'    => $_ARRAYLANG['TXT_LOCATION'],
            'TXT_AVAILABLE_LOCATIONS'    => $_ARRAYLANG['TXT_JOBS_AVAILABLE_LOCATIONS'],
            'TXT_ASSOCIATED_LOCATIONS'    => $_ARRAYLANG['TXT_JOBS_ASSOCIATED_LOCATIONS'],
            'TXT_CHECK_ALL' => $_ARRAYLANG['TXT_CHECK_ALL'],
            'TXT_UNCHECK_ALL' => $_ARRAYLANG['TXT_REMOVE_SELECTION'],
            'FORM_ONSUBMIT' => "onsubmit=\"SelectAllLocations(document.getElementById('associated_locations'))\"",
        ));
        if (empty($jobID)) {
            $query = "SELECT DISTINCT l.name as name,
                      l.id as id
                      FROM `".DBPREFIX."module_jobs_location` l
                      WHERE 1;";
        } else  {
            $query = "SELECT DISTINCT l.name as name,
                      l.id as id,
                      j.job as jobid ,
                      j.location as location
                      FROM `".DBPREFIX."module_jobs_location` l
                      LEFT JOIN `".DBPREFIX."module_jobs_rel_loc_jobs` as j on j.location=l.id
                      AND j.job = $jobID";
        }
        $objResult = $objDatabase->Execute($query);
        while($objResult!==false && !$objResult->EOF) {
            if (empty($jobID) or $objResult->fields['jobid'] != $jobID) {
                $notAssociatedLocations .= "<option value=\"".$objResult->fields['id']."\">".htmlentities($objResult->fields['name'], ENT_QUOTES, CONTREXX_CHARSET)."</option>\n";
            } else {
                $AssociatedLocations .= "<option value=\"".$objResult->fields['id']."\">".htmlentities($objResult->fields['name'], ENT_QUOTES, CONTREXX_CHARSET)."</option>\n";
            }
            $objResult->MoveNext();
        }
        $this->_objTpl->setVariable('ASSOCIATED_LOCATIONS',$AssociatedLocations);
        $this->_objTpl->setVariable('NOT_ASSOCIATED_LOCATIONS',$notAssociatedLocations);
    }

    /**
     * Add/edit/copy a job offer
     *
     * @param   integer $id ID of job offer to edit or to copy.
     * @param   boolean $add    Sets the mode of the input form. If set to
     *                          TRUE, then the form is used for adding a new
     *                          job offer. Otherwise, it's used to modify an
     *                          existing job offer. Copy functionality is done
     *                          through add-functionality.
     */
    protected function modifyJob($id = 0)
    {
        global $_ARRAYLANG;

        if (!empty($_GET['copy'])) {
            $id = intval($_GET['copy']);
        }

        $add = true;
        $status = 'checked="checked"';
        $title = '';
        $author = \FWUser::getFWUserObject()->objUser->getUsername();
        $jobsText = '';
        $workloc = '';
        $workload = '';
        $work_start = '';
        $startDate = '';
        $endDate = '';
        $date = date(ASCMS_DATE_FORMAT, time());
        $hot = 0;
        $paid = 0;
        $catId = '';
        $inputId = 0;

        // try to update a modified job
        if (
            empty($id) &&
            !empty($_POST['id'])
        ) {
            $this->update();
        }

        // load job in edit mode
        if (
            empty($id) &&
            !empty($_REQUEST['id'])
        ) {
            $id = intval($_REQUEST['id']);
            $add = false;
        }

        // check for storing a new job
        if (
            empty($id) &&
            !empty($_POST['jobsTitle'])
        ) {
            // in case the insertion of the new job offer fails,
            // the insert() method will return the newly generated
            // ID (if any)
            $id = $this->insert();
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $db = $cx->getDb()->getAdoDb();

        if ($add) {
            $sectionTitle = $_ARRAYLANG['TXT_ADD_DOCUMENT'];
        } else {
            $sectionTitle = $_ARRAYLANG['TXT_EDIT_DOCUMENTS'];
            // this is a workaround as the navigation has a non-standard logic
            $_ARRAYLANG['TXT_MODULE_JOBS_ACT_JOB'] = $sectionTitle;

            $inputId = $id;
        }

        $this->_objTpl->setVariable(array(
            'TXT_JOBS_MESSAGE'    => $sectionTitle,
            'TXT_TITLE'           => $_ARRAYLANG['TXT_TITLE'],
            'TXT_CATEGORY'        => $_ARRAYLANG['TXT_CATEGORY'],
            'TXT_JOBS_CATEGORY_SELECT'=> $_ARRAYLANG['TXT_JOBS_CATEGORY_SELECT'],
            'TXT_JOBS_SETTINGS'   => $_ARRAYLANG['TXT_JOBS_SETTINGS'],
            'TXT_WORKLOC'         => $_ARRAYLANG['TXT_WORKLOC'],
            'TXT_WORKLOAD'        => $_ARRAYLANG['TXT_WORKLOAD'],
            'TXT_WORK_START'      => $_ARRAYLANG['TXT_WORK_START'],
            'TXT_JOBS_CONTENT'    => $_ARRAYLANG['TXT_CONTENT'],
            'TXT_STORE'           => $_ARRAYLANG['TXT_STORE'],
            'TXT_PUBLISHING'      => $_ARRAYLANG['TXT_PUBLISHING'],
            'TXT_STARTDATE'       => $_ARRAYLANG['TXT_STARTDATE'],
            'TXT_ENDDATE'         => $_ARRAYLANG['TXT_ENDDATE'],
            'TXT_OPTIONAL'        => $_ARRAYLANG['TXT_OPTIONAL'],
            'TXT_DATE'            => $_ARRAYLANG['TXT_DATE'],
            'TXT_ACTIVE'          => $_ARRAYLANG['TXT_ACTIVE'],
            'TXT_AUTHOR'          => $_ARRAYLANG['TXT_AUTHOR'],
            'TXT_JOBS_NO_CATEGORY'=> $_ARRAYLANG['TXT_JOBS_NO_CATEGORY'],
            'TXT_JOBS_NO_TITLE'   => $_ARRAYLANG['TXT_JOBS_NO_TITLE'],
            'TXT_JOBS_PAID'       => $_ARRAYLANG['TXT_JOBS_PAID'],
            'TXT_JOBS_PAID_LABEL' => $_ARRAYLANG['TXT_JOBS_PAID_LABEL'],
            'TXT_JOBS_MODIFY_HOT_OFFER_LABEL' => $_ARRAYLANG['TXT_JOBS_MODIFY_HOT_OFFER_LABEL'],
            'TXT_JOBS_MODIFY_HOT_OFFER'       => $_ARRAYLANG['TXT_JOBS_MODIFY_HOT_OFFER'],
        ));

        $query = "
            SELECT `catid`, `lang`, `date`, `id`,
                   `title`, `author`, `text`,
                   `workloc`, `workload`, `work_start`,
                   `startdate`, `enddate`, `status`, `hot`, `paid`
              FROM `".DBPREFIX."module_jobs`
             WHERE id=$id
        ";
        $objResult = $db->Execute($query);
        if (!$objResult || $objResult->EOF) {
            $id = 0;
        } else {
            $catId = $objResult->fields['catid'];
            $jobsText = $objResult->fields['text'];
            if (!$objResult->fields['status']) {
                $status = '';
            }
            if ($objResult->fields['startdate'] != '0000-00-00 00:00:00') {
                $startDate = $objResult->fields['startdate'];
            }
            if ($objResult->fields['enddate'] != '0000-00-00 00:00:00') {
                $endDate = $objResult->fields['enddate'];
            }
            if (!empty($objResult->fields['work_start'])) {
                $work_start = date('Y-m-d', $objResult->fields['work_start']);
            }
            $title = $objResult->fields['title'];
            $author = $objResult->fields['author'];
            $workloc = $objResult->fields['workloc'];
            $workload = $objResult->fields['workload'];
            $date = date(ASCMS_DATE_FORMAT, $objResult->fields['date']);
            $hot = !empty($objResult->fields['hot']);
            $paid = !empty($objResult->fields['paid']);
        }

        $this->getLocationTable($id);
        $this->_objTpl->setVariable(array(
            'JOBS_ID'                   => $inputId,
            'JOBS_STORED_ID'            => $inputId,
            'JOBS_TITLE'                => contrexx_raw2xhtml($title),
            'JOBS_AUTHOR'               => contrexx_raw2xhtml($author),
            'JOBS_TEXT'                 => new \Cx\Core\Wysiwyg\Wysiwyg(
                'jobsText',
                contrexx_raw2xhtml($jobsText),
                'full'
            ),
            'JOBS_WORKLOC'              => contrexx_raw2xhtml($workloc),
            'JOBS_WORKLOAD'             => contrexx_raw2xhtml($workload),
            'JOBS_WORK_START'           => $work_start,
            'JOBS_STARTDATE'            => $startDate,
            'JOBS_ENDDATE'              => $endDate,
            'JOBS_STATUS'               => $status,
            'JOBS_DATE'                 => $date,
            'JOBS_MODIFY_HOT_OFFER'     => $hot ? 'checked=checked' : '',
            'JOBS_PAID'                 => $paid ? 'checked=checked' : '',
            'JOBS_CAT_MENU'             => $this->getCategoryMenu(
                $this->langId,
                $catId
            ),
        ));

        //Get the settings value from DB
        $settings = $this->getSettings();

        //Hide the column 'Hot' if the settings options 'templateIntegration' and 'sourceOfJobs' are active
        $isHotOfferAvailable = (    isset($settings['templateIntegration']) 
                                &&  ($settings['templateIntegration'] == 1) 
                                &&  isset($settings['sourceOfJobs']) 
                                &&  ($settings['sourceOfJobs'] == 'manual')
                               );

        if (!$isHotOfferAvailable) {
            $this->_objTpl->hideBlock('jobs_modify_show_hot_offer');
        }

        if (!empty($settings['use_flags'])) {
            $this->_objTpl->setVariable(array(
                'TXT_JOBS_FLAGS'      => $_ARRAYLANG['TXT_JOBS_FLAGS'],
                'TXT_JOBS_FLAGS_INFO' => $_ARRAYLANG['TXT_JOBS_FLAGS_INFO'],
            ));

            $associatedFlagIds = $this->getFlagAssociations($id);
            $flagRepo = $cx->getDb()->getEntityManager()->getRepository(
                'Cx\Modules\Jobs\Model\Entity\Flag'
            );
            $flags = $flagRepo->findAll();
            foreach ($flags as $flag) {
                $this->_objTpl->setVariable(array(
                    'JOBS_FLAG_ID'      => $flag->getId(),
                    'JOBS_FLAG_NAME'    => contrexx_raw2xhtml($flag->getName()),
                ));
                if ($flag->getIcon()) {
                    $this->_objTpl->setVariable(array(
                        'JOBS_FLAG_ICON_SRC'=> $flag->getIcon(),
                    ));
                    $this->_objTpl->touchBlock('jobs_flag_icon');
                } else {
                    $this->_objTpl->hideBlock('jobs_flag_icon');
                }
                if (
                    isset($associatedFlagIds[$id]) &&
                    in_array($flag->getId(), $associatedFlagIds[$id])
                ) {
                    $this->_objTpl->touchBlock('jobs_flag_checked');
                } else {
                    $this->_objTpl->hideBlock('jobs_flag_checked');
                }
                $this->_objTpl->parse('jobs_flags_list');
            }

            $this->_objTpl->touchBlock('jobs_flags_section');
        } else {
            $this->_objTpl->hideBlock('jobs_flags_section');
        }
    }

    /**
    * Deletes a news entry
    *
    * @global     object    $objDatabase
    * @global     array     $_ARRAYLANG
    * @return    -
    */
    function delete()
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_GET['id'])) {
            $jobsId = intval($_GET['id']);
            $query = "DELETE FROM ".DBPREFIX."module_jobs WHERE id = $jobsId";
            if ($objDatabase->Execute($query)) {
                $this->createRSS();
                $query = "DELETE FROM ".DBPREFIX."module_jobs_rel_loc_jobs WHERE job = $jobsId";
                if ($objDatabase->Execute($query)) {
                    \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL']);
                } else {
                    \Message::error($_ARRAYLANG['TXT_JOBS_LOCATION_NOT_DELETED']);
                }
            } else {
                \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
            }

            static::clearCache();
        }

        if (is_array($_POST['selectedId'])) {
            foreach ($_POST['selectedId'] as $value) {
                if (!empty($value)) {
                    if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_jobs WHERE id = ".intval($value))) {
                        $this->createRSS();
                        $query = "DELETE FROM ".DBPREFIX."module_jobs_rel_loc_jobs WHERE job = ".intval($value);
                        if ($objDatabase->Execute($query)) {
                            \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL']);
                        } else {
                            \Message::error($_ARRAYLANG['TXT_JOBS_LOCATION_NOT_DELETED']);
                        }
                    } else {
                        \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
                    }
                }
            }
        }

        static::clearCache();

        \Cx\Core\Csrf\Controller\Csrf::redirect(
            \Cx\Core\Routing\Url::fromBackend('Jobs')
        );
    }

    /**
    * Update job
    * @global     object    $objDatabase
    * @return    boolean   result
    */
    function update()
    {
        global $objDatabase, $_ARRAYLANG;

        $objFWUser = \FWUser::getFWUserObject();
        $id = intval($_POST['id']);
        $userId = $objFWUser->objUser->getId();
        $changelog = time();
        $title = get_magic_quotes_gpc() ? strip_tags($_POST['jobsTitle']) : addslashes(strip_tags($_POST['jobsTitle']));
        $text = get_magic_quotes_gpc() ? $_POST['jobsText'] : addslashes($_POST['jobsText']);
        $title= str_replace("ß","ss",$title);
        $text = $this->filterBodyTag($text);
        $text = str_replace("ß","ss",$text);
        $workloc    = get_magic_quotes_gpc() ? strip_tags($_POST['workloc']) : addslashes(strip_tags($_POST['workloc']));
        $workload = get_magic_quotes_gpc() ? strip_tags($_POST['workload']) : addslashes(strip_tags($_POST['workload']));
        $hotOffer = isset($_POST['hotOffer']) ? contrexx_input2int($_POST['hotOffer']) : 0;
        $paid = isset($_POST['paid']) ? contrexx_input2int($_POST['paid']) : 0;
        if (empty($_POST['work_start']))
            $work_start = "0000-00-00";
        else
            $work_start = $_POST['work_start'];
        //start 'n' end
        $dateparts         = explode("-", $work_start);
        $work_start        = mktime(00, 00,00, $dateparts[1], $dateparts[2], $dateparts[0]);

        $catId = intval($_POST['jobsCat']);
        $status = (!empty($_POST['status'])) ? intval($_POST['status']) : 0;
        $startDate = get_magic_quotes_gpc() ? strip_tags($_POST['startDate']) : addslashes(strip_tags($_POST['startDate']));
        $endDate = get_magic_quotes_gpc() ? strip_tags($_POST['endDate']) : addslashes(strip_tags($_POST['endDate']));
        $author =  get_magic_quotes_gpc() ? strip_tags($_POST['author']) : addslashes(strip_tags($_POST['author']));

        $date = $this->_checkDate(date('H:i:s d.m.Y'));
        $dberr = false;
        $locset = array();                //set of location that is associated with this job in the POST Data
        $locset_indb = array();           //set of locations that is associated with this job in the db
        $rel_loc_jobs = '';     //used to generate INSERT Statement

        if (
            !empty($_POST['associated_locations']) &&
            is_array($_POST['associated_locations'])
        ) {
            foreach($_POST['associated_locations'] as $value) {
                $locset[] = intval($value);
            }
        }

        $query = "SELECT DISTINCT l.name as name,
                  l.id as id
                  FROM `".DBPREFIX."module_jobs_location` l
                  LEFT JOIN `".DBPREFIX."module_jobs_rel_loc_jobs` as j on j.location=l.id
                  WHERE j.job = $id";

        //Compare Post data and database
        $objResult = $objDatabase->Execute($query);
        if (!$objResult) {
            $dberr = true;
        }

        while(!$objResult->EOF && !$dberr) {
            if (in_array($objResult->fields['id'],$locset)) {
                $locset_indb[] = $objResult->fields['id'];
            } else {
                $query = "DELETE FROM `".DBPREFIX."module_jobs_rel_loc_jobs` WHERE job = ".$id." AND location = ".$objResult->fields['id'];
                if (!$objDatabase->Execute($query)) {
                    $dberr = true;
                }
            }
            $objResult->MoveNext();
        }
        unset($value);
        if (count($locset)-count($locset_indb) > 0  && !$dberr) {
            foreach($locset as $value) {
                if (!in_array($value,$locset_indb)) {
                    $rel_loc_jobs .= " ($id,$value),";
                }
            }
            $rel_loc_jobs = substr_replace($rel_loc_jobs ,"",-1);
            $query = "INSERT INTO `".DBPREFIX."module_jobs_rel_loc_jobs` (job,location) VALUES $rel_loc_jobs ";

            if (!$objDatabase->Execute($query)) {
                $dberr = true;
            }
        }

        $flags = array();
        if (
            !empty($_POST['jobs-flag']) &&
            is_array($_POST['jobs-flag'])
        ) {
            foreach($_POST['jobs-flag'] as $flag) {
                $flags[] = intval($flag);
            }
            $query = 'DELETE FROM `'.DBPREFIX.'module_jobs_rel_flag_job`
                WHERE `job` = ' . $id . '
                  AND `flag` NOT IN (' . join ($flags, ',') . ')';
            if (!$objDatabase->Execute($query)) {
                \Message::error($_ARRAYLANG['TXT_JOBS_FLAGS_NOT_ASSIGNED']);
                $dberr = true;
            }
            $query = 'INSERT IGNORE INTO `'.DBPREFIX.'module_jobs_rel_flag_job`
                (`job`,`flag`) VALUES
                ('. $id . ',' . join($flags, '), (' . $id . ',') . ')';
            if (!$objDatabase->Execute($query)) {
                \Message::error($_ARRAYLANG['TXT_JOBS_FLAGS_NOT_ASSIGNED']);
                $dberr = true;
            }
        } else {
            $query = 'DELETE FROM `'.DBPREFIX.'module_jobs_rel_flag_job`
                WHERE `job` = ' . $id;
            if (!$objDatabase->Execute($query)) {
                \Message::error($_ARRAYLANG['TXT_JOBS_FLAGS_NOT_ASSIGNED']);
                $dberr = true;
            }
        }

        $query = \SQL::update('module_jobs', array(
            'date' => array('val' => $this->_checkDate($_POST['creation_date']), 'omitEmpty' => true),
            'title' => $title,
            'author' => $author,
            'text' => array('val' => $text, 'omitEmpty' => true),
            'workloc' => $workloc,
            'workload' => $workload,
            'work_start' => array('val' => $work_start, 'omitEmpty' => true),
            'lang' => array('val' => $this->langId, 'omitEmpty' => true),
            'startdate' => array('val' => $startDate, 'omitEmpty' => true),
            'enddate' => array('val' => $endDate, 'omitEmpty' => true),
            'status' => array('val' => $status, 'omitEmpty' => true),
            'userid' => array('val' => $userId, 'omitEmpty' => true),
            'changelog' => array('val' => $date, 'omitEmpty' => true),
            'catId' => array('val' => $catId, 'omitEmpty' => true),
            'hot' => array('val' => $hotOffer, 'omitEmpty' => true),
            'paid' => array('val' => $paid, 'omitEmpty' => true),
        ))." WHERE id = $id;";

        static::clearCache();

        if (!$objDatabase->Execute($query) or $dberr) {
            \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
            return;
        } else {
            $this->createRSS();
            static::clearCache();
            \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL']);
        }

        \Cx\Core\Csrf\Controller\Csrf::redirect(
            \Cx\Core\Routing\Url::fromBackend('Jobs')
        );
    }


    /**
    * Change job active status
    * @global    object    $objDatabase
    * @global    array     $_POST
    * @param     integer   $newsid
    * @return    boolean   result
    */
    function changeStatus()
    {
        global $objDatabase, $_ARRAYLANG;

        if (isset($_POST['deactivate'])) {
            $status = 0;
        }
        if (isset($_POST['activate'])) {
            $status = 1;
        }
        if (isset($status)) {
            if (is_array($_POST['selectedId'])) {
                $success = true;
                foreach ($_POST['selectedId'] as $value) {
                    if (!empty($value)) {
                        $retval = $objDatabase->Execute("
                            UPDATE ".DBPREFIX."module_jobs
                               SET status='$status'
                             WHERE id=".intval($value));
                    }
                    if (!$retval) {
                        $success = false;
                    }
                }
                if (!$success) {
                    \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
                } else{
                    \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL']);
                }

                static::clearCache();
            }
        }

        \Cx\Core\Csrf\Controller\Csrf::redirect(
            \Cx\Core\Routing\Url::fromBackend('Jobs')
        );
    }

    /**
     * Modify the settings
     */
    function settings()
    {
        global $objDatabase, $_ARRAYLANG;

        //Parse the language variable
        $this->_objTpl->setVariable(array(
            'TXT_SETTINGS'          => $_ARRAYLANG['TXT_SETTINGS'],
            'TXT_FOOTNOTE'          => $_ARRAYLANG['TXT_JOBS_FOOTNOTE'],
            'TXT_LINK'              => $_ARRAYLANG['TXT_JOBS_LINK'],
            'TXT_URL'               => $_ARRAYLANG['TXT_JOBS_URL'],
            'TXT_FOOTNOTE_HELP'     => $_ARRAYLANG['TXT_JOBS_FOOTNOTE_HELP'],
            'TXT_LINK_HELP'         => $_ARRAYLANG['TXT_JOBS_LINK_HELP'],
            'TXT_URL_HELP'          => $_ARRAYLANG['TXT_JOBS_URL_HELP'],
            'TXT_SUBMIT'            => $_ARRAYLANG['TXT_ACCEPT_CHANGES'],
            'TXT_URL_INFO'          => $_ARRAYLANG['TXT_JOBS_URL_INFO'],
            'TXT_SHOW_LOCATION_FE'  => $_ARRAYLANG['TXT_JOBS_SHOW_LOCATION_FE'],
            'TXT_JOBS_USE_FLAGS_OPTION'                    => $_ARRAYLANG['TXT_JOBS_USE_FLAGS_OPTION'],
            'TXT_JOBS_SETTINGS_TEMPLATE_INTEGRATION'       => $_ARRAYLANG['TXT_JOBS_SETTINGS_TEMPLATE_INTEGRATION'],
            'TXT_JOBS_SETTINGS_TEMPLATE_INTEGRATION_LABEL' => $_ARRAYLANG['TXT_JOBS_SETTINGS_TEMPLATE_INTEGRATION_LABEL'],
            'TXT_JOBS_SETTINGS_SOURCE_OF_JOBS'             => $_ARRAYLANG['TXT_JOBS_SETTINGS_SOURCE_OF_JOBS'],
            'TXT_JOBS_SETTINGS_LATEST_JOBS_LABEL'          => $_ARRAYLANG['TXT_JOBS_SETTINGS_LATEST_JOBS_LABEL'],
            'TXT_JOBS_SETTINGS_MANUAL_JOBS_LABEL'          => $_ARRAYLANG['TXT_JOBS_SETTINGS_MANUAL_JOBS_LABEL'],
            'TXT_JOBS_SETTINGS_LISTING_LIMIT'              => $_ARRAYLANG['TXT_JOBS_SETTINGS_LISTING_LIMIT'],
        ));

        //get the input values
        $postValues = isset($_POST['settings']) ? $_POST['settings'] : array();
        $settings   = array(
            'footnote'  => isset($postValues['footnote']) ? contrexx_input2raw($postValues['footnote']) : '',
            'link'      => isset($postValues['link']) ? contrexx_input2raw($postValues['link']) : '',
            'url'       => isset($postValues['url']) ? contrexx_input2raw($postValues['url']) : '',
            'show_location_fe'    => isset($postValues['show_location_fe']) ? contrexx_input2int($postValues['show_location_fe']) : 0,
            'use_flags' => isset($postValues['use_flags']) ? contrexx_input2int($postValues['use_flags']) : 0,
            'templateIntegration' => isset($postValues['templateIntegration']) ? contrexx_input2int($postValues['templateIntegration']) : 0,
            'sourceOfJobs' => isset($postValues['sourceOfJobs']) ? contrexx_input2raw($postValues['sourceOfJobs']) : '',
            'listingLimit' => isset($postValues['listingLimit']) ? contrexx_input2int($postValues['listingLimit']) : 0,
        );
        $isFormSubmitted     = isset($_POST['updateFootnote']);
        $error               = false;

        //Url validation
        if ($isFormSubmitted) {
            if (    empty($settings['url'])
                ||  !preg_match('/^[A-Za-z0-9\.\/%&=\?\-_:#@;]+$/i', $settings['url']) 
            ) {
                \Message::error($_ARRAYLANG['TXT_JOBS_URL_ERROR']);
                $error = true;
            }
        }

        //update the settings value
        if ($isFormSubmitted && !$error) {
            $query = 'UPDATE `' . DBPREFIX . 'module_jobs_settings`
                        SET `value` = (CASE WHEN `name` = "footnote"            THEN "' . contrexx_raw2db($settings['footnote']) . '"
                                            WHEN `name` = "link"                THEN "' . contrexx_raw2db($settings['link']) . '" 
                                            WHEN `name` = "url"                 THEN "' . contrexx_raw2db($settings['url']) . '" 
                                            WHEN `name` = "show_location_fe"    THEN "' . contrexx_raw2db($settings['show_location_fe']) . '" 
                                            WHEN `name` = "use_flags"           THEN "' . contrexx_raw2db($settings['use_flags']) . '" 
                                            WHEN `name` = "templateIntegration" THEN "' . contrexx_raw2db($settings['templateIntegration']) . '" 
                                            WHEN `name` = "sourceOfJobs"        THEN "' . contrexx_raw2db($settings['sourceOfJobs']) . '" 
                                            WHEN `name` = "listingLimit"        THEN "' . contrexx_raw2db($settings['listingLimit']) . '"
                                       END)';
            static::clearCache();
            if ($objDatabase->Execute($query)) {
                \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL']);
                // force reload of backend section to ensure the option
                // use_flags can be applied right away (causes an additional
                // settings section to appear)
                \Cx\Core\Csrf\Controller\Csrf::redirect(
                    \Cx\Core\Routing\Url::fromBackend('Jobs', 'settings')
                );
            } else {
                \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
            }
        } else {
            //Fetch the setting values from DB
            $settings = $this->getSettings();
        }

        //Parse the settings value
        $showLatestJobInTemplate = (isset($settings['sourceOfJobs']) && $settings['sourceOfJobs'] == 'latest');
        $this->_objTpl->setVariable(array(
            'FOOTNOTE'            => contrexx_raw2xhtml($settings['footnote']),
            'LINK'                => contrexx_raw2xhtml($settings['link']),
            'URL'                 => contrexx_raw2xhtml($settings['url']),
            'SHOW_LOCATION_FE'    => !empty($settings['show_location_fe']) ? 'checked=checked' : '' ,
            'JOBS_SETTINGS_TEMPLATE_INTEGRATION' => !empty($settings['templateIntegration']) ? 'checked=checked' : '',
            'JOBS_SETTINGS_LATEST_JOBS'          => $showLatestJobInTemplate ? 'checked=checked' : '',
            'JOBS_SETTINGS_SOURCE_OF_JOBS'       => !$showLatestJobInTemplate ? 'checked=checked' : '',
            'JOBS_SETTINGS_LISTING_LIMIT'        => contrexx_raw2xhtml($settings['listingLimit']),
            'JOBS_SETTINGS_DISPLAY_STATUS'       => $settings['templateIntegration'] ? '' : 'display: none'
        ));
        if (!empty($settings['use_flags'])) {
            $this->_objTpl->touchBlock('jobs_use_flags');
        } else {
            $this->_objTpl->hideBlock('jobs_use_flags');
        }
    }

    /**
     * checks if date is valid
     * @param string $date
     * @return integer $timestamp
     */
    function _checkDate($date)
    {
        $arrDate = array();
        if (preg_match('/^([0-9]{1,2})\:([0-9]{1,2})\:([0-9]{1,2})\s*([0-9]{1,2})\.([0-9]{1,2})\.([0-9]{1,4})/', $date, $arrDate)) {
            return mktime(intval($arrDate[1]), intval($arrDate[2]), intval($arrDate[3]), intval($arrDate[5]), intval($arrDate[4]), intval($arrDate[6]));
        }
        return time();
    }


    /**
    * Insert news
    * @global     object    $objDatabase
    * @return    boolean   result
    */
    function insert()
    {
        global $objDatabase, $_ARRAYLANG;

        $objFWUser = \FWUser::getFWUserObject();
        $date = $this->_checkDate($_POST['creation_date']);
        $title = get_magic_quotes_gpc() ? strip_tags($_POST['jobsTitle']) : addslashes(strip_tags($_POST['jobsTitle']));
        $author = get_magic_quotes_gpc() ? strip_tags($_POST['author']) : addslashes(strip_tags($_POST['author']));
        $text = get_magic_quotes_gpc() ? $_POST['jobsText'] : addslashes($_POST['jobsText']);
        $title = str_replace("ß","ss",$title);
        $text = str_replace("ß","ss",$text);
        $text = $this->filterBodyTag($text);
        $workloc = get_magic_quotes_gpc() ? strip_tags($_POST['workloc']) : addslashes(strip_tags($_POST['workloc']));
        $workload = get_magic_quotes_gpc() ? strip_tags($_POST['workload']) : addslashes(strip_tags($_POST['workload']));
        $hotOffer = isset($_POST['hotOffer']) ? contrexx_input2int($_POST['hotOffer']) : 0;
        $paid = isset($_POST['paid']) ? contrexx_input2int($_POST['paid']) : 0;
        if (empty($_POST['work_start']))
             $work_start = "0000-00-00";
        else
            $work_start = $_POST['work_start'];
        //start 'n' end
        $dateparts         = explode("-", $work_start);
        $work_start        = mktime(00, 00,00, $dateparts[1], $dateparts[2], $dateparts[0]);

        $cat = intval($_POST['jobsCat']);
        $userid = $objFWUser->objUser->getId();
        $startDate = get_magic_quotes_gpc() ? strip_tags($_POST['startDate']) : addslashes(strip_tags($_POST['startDate']));
        $endDate = get_magic_quotes_gpc() ? strip_tags($_POST['endDate']) : addslashes(strip_tags($_POST['endDate']));

        $status = intval($_POST['status']);

        if (empty($title) or empty($cat)) {
            \Message::error($_ARRAYLANG['TXT_JOBS_ERROR']);
            return 0;
        }

        if ($status == 0) {
            $startDate = "";
            $endDate = "";
        }

        $query = \SQL::insert('module_jobs', array(
            'date' => array('val' => $date, 'omitEmpty' => true),
            'title' => $title,
            'author' => $author,
            'text' => array('val' => $text, 'omitEmpty' => true),
            'workloc' => $workloc,
            'workload' => $workload,
            'work_start' => array('val' => $work_start, 'omitEmpty' => true),
            'catid' => array('val' => $cat, 'omitEmpty' => true),
            'lang' => array('val' => $this->langId, 'omitEmpty' => true),
            'startdate' => array('val' => $startDate, 'omitEmpty' => true),
            'enddate' => array('val' => $endDate, 'omitEmpty' => true),
            'status' => array('val' => $status, 'omitEmpty' => true),
            'userid' => array('val' => $userid, 'omitEmpty' => true),
            'changelog' => array('val' => $date, 'omitEmpty' => true),
            'hot' => array('val' => $hotOffer, 'omitEmpty' => true),
            'paid' => array('val' => $paid, 'omitEmpty' => true),
        ));

        if (!$objDatabase->Execute($query)) {
            \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
            return 0;
        }

        $id = $objDatabase->Insert_id();
        $rel_loc_jobs = "";

        if (!isset($id)) {
            \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
            \Cx\Core\Csrf\Controller\Csrf::redirect(
                \Cx\Core\Routing\Url::fromBackend('Jobs')
            );
        }

        if (
            !empty($_POST['associated_locations']) &&
            is_array($_POST['associated_locations'])
        ) {
            foreach($_POST['associated_locations'] as $value) {
                $value = intval($value);
                $rel_loc_jobs .= " ($id,$value),";
            }
            $rel_loc_jobs = substr_replace($rel_loc_jobs ,"",-1);

            $query = "INSERT INTO `".DBPREFIX."module_jobs_rel_loc_jobs` (job,location) VALUES $rel_loc_jobs ";
            if (!$objDatabase->Execute($query)) {
                \Message::error($_ARRAYLANG['TXT_JOBS_LOCATIONS_NOT_ASSIGNED']);
                return $id;
            }
        }

        if (
            !empty($_POST['jobs-flag']) &&
            is_array($_POST['jobs-flag'])
        ) {
            $flags = array();
            foreach($_POST['jobs-flag'] as $flag) {
                $flags[] = intval($flag);
            }
            $query = 'INSERT INTO `'.DBPREFIX.'module_jobs_rel_flag_job`
                (`job`,`flag`) VALUES
                ('. $id . ',' . join($flags, '), (' . $id . ',') . ')';
            if (!$objDatabase->Execute($query)) {
                \Message::error($_ARRAYLANG['TXT_JOBS_FLAGS_NOT_ASSIGNED']);
                return $id;
            }
        }

        \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_ADDED_SUCCESSFUL']);
        \Cx\Core\Csrf\Controller\Csrf::redirect(
            \Cx\Core\Routing\Url::fromBackend('Jobs')
        );
    }


    /**
    * Add or edit the news categories
    * @global    object     $objDatabase
    * @global    array      $_ARRAYLANG
    */
    function manageCategories()
    {
        global $objDatabase,$_ARRAYLANG;

        $this->_objTpl->setVariable(array(
            'TXT_ADD_NEW_CATEGORY'                       => $_ARRAYLANG['TXT_ADD_NEW_CATEGORY'],
            'TXT_NAME'                                   => $_ARRAYLANG['TXT_NAME'],
            'TXT_ADD'                                    => $_ARRAYLANG['TXT_ADD'],
            'TXT_CATEGORY_LIST'                          => $_ARRAYLANG['TXT_CATEGORY_LIST'],
            'TXT_ID'                                     => $_ARRAYLANG['TXT_ID'],
            'TXT_ACTION'                                 => $_ARRAYLANG['TXT_ACTION'],
            'TXT_ACCEPT_CHANGES'                         => $_ARRAYLANG['TXT_ACCEPT_CHANGES'],
            'TXT_CONFIRM_DELETE_DATA'                    => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
            'TXT_ACTION_IS_IRREVERSIBLE'                 => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_ATTENTION_SYSTEM_FUNCTIONALITY_AT_RISK' => $_ARRAYLANG['TXT_ATTENTION_SYSTEM_FUNCTIONALITY_AT_RISK'],
            'TXT_JOBS_SORTING'                          => $_ARRAYLANG['TXT_JOBS_SORTING'],
            'TXT_JOBS_SORTTYPE'                          => $_ARRAYLANG['TXT_JOBS_SORTTYPE'],
        ));
        $this->_objTpl->setGlobalVariable(array(
            'TXT_DELETE'    => $_ARRAYLANG['TXT_DELETE']
        ));

        // Add a new category
        if (isset($_POST['addCat']) AND ($_POST['addCat']==true)) {
             $catName = get_magic_quotes_gpc() ? strip_tags($_POST['newCatName']) : addslashes(strip_tags($_POST['newCatName']));
             if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_jobs_categories (name,lang)
                                 VALUES ('$catName','$this->langId')")) {
                 \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_ADDED_SUCCESSFUL']);
             } else {
                 \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
             }
            static::clearCache();
        }

        // Modify a new category
        if (isset($_POST['modCat']) AND ($_POST['modCat']==true)) {
            $status = true;
            foreach ($_POST['catName'] as $id => $name) {
                $name = get_magic_quotes_gpc() ? strip_tags($name) : addslashes(strip_tags($name));
                $id=intval($id);

                $sorting = !empty($_REQUEST['sortStyle'][$id]) ? contrexx_addslashes($_REQUEST['sortStyle'][$id]) : 'alpha';

                if (!$objDatabase->Execute("UPDATE ".DBPREFIX."module_jobs_categories
                                  SET name='$name',
                                      lang='$this->langId',
                                      sort_style='$sorting'
                                WHERE catid=$id")
                ) {
                    $status = false;
                }
            }
            if ($status) {
                    \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL']);
            } else {
                    \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
            }
            static::clearCache();
        }

        $query = "SELECT `catid`,
                           `name`,
                           `sort_style`
                      FROM `".DBPREFIX."module_jobs_categories`
                     WHERE `lang`='$this->langId'
                  ORDER BY `catid` asc";
        $objResult = $objDatabase->Execute($query);

        $this->_objTpl->setCurrentBlock('row');
        $i=0;

        while ($objResult !== false && !$objResult->EOF) {
            $class = (($i % 2) == 0) ? "row1" : "row2";
            $sorting = $objResult->fields['sort_style'];
            $this->_objTpl->setVariable(array(
                'JOBS_ROWCLASS'   => $class,
                'JOBS_CAT_ID'      => $objResult->fields['catid'],
                'JOBS_CAT_NAME'      => stripslashes($objResult->fields['name']),
                'JOBS_SORTING_DROPDOWN'    => $this->_getSortingDropdown($objResult->fields['catid'], $sorting),
            ));
            $this->_objTpl->parseCurrentBlock('row');
            $i++;
            $objResult->MoveNext();
        };
    }


    /**
    * Delete the news categories
    * @global    object     $objDatabase
    * @global    array      $_ARRAYLANG[news*]
    */
    function deleteCat()
    {
        global $objDatabase,$_ARRAYLANG;

        if (isset($_GET['catId'])) {
            $catId=intval($_GET['catId']);
            $objResult = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_jobs WHERE catid=$catId");

            if (!$objResult->EOF) {
                 \Message::error($_ARRAYLANG['TXT_CATEGORY_NOT_DELETED_BECAUSE_IN_USE']);
            } else {
                if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_jobs_categories WHERE catid=$catId")) {
                    \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL']);
                } else {
                    \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
                }
            }
            static::clearCache();
        }

        \Cx\Core\Csrf\Controller\Csrf::redirect(
            \Cx\Core\Routing\Url::fromBackend('Jobs', 'cat')
        );
    }


    /**
    * Add or edit the jobs Locations
    * @global    object     $objDatabase
    * @global    array      $_ARRAYLANG
    */
    function manageLocations()
    {
        global $objDatabase,$_ARRAYLANG;

        $this->_objTpl->setVariable(array(
            'TXT_ADD_NEW_LOCATION'                       => $_ARRAYLANG['TXT_ADD_NEW_LOCATION'],
            'TXT_NAME'                                   => $_ARRAYLANG['TXT_NAME'],
            'TXT_ADD'                                    => $_ARRAYLANG['TXT_ADD'],
            'TXT_SELECT_ALL'                             => $_ARRAYLANG['TXT_SELECT_ALL'],
            'TXT_REMOVE_SELECTION'                       => $_ARRAYLANG['TXT_REMOVE_SELECTION'],
            'TXT_LOCATION_LIST'                          => $_ARRAYLANG['TXT_LOCATION_LIST'],
            'TXT_ID'                                     => $_ARRAYLANG['TXT_ID'],
            'TXT_ACTION'                                 => $_ARRAYLANG['TXT_ACTION'],
            'TXT_ACCEPT_CHANGES'                         => $_ARRAYLANG['TXT_ACCEPT_CHANGES'],
            'TXT_CONFIRM_DELETE_DATA'                    => $_ARRAYLANG['TXT_CONFIRM_DELETE_DATA'],
            'TXT_ACTION_IS_IRREVERSIBLE'                 => $_ARRAYLANG['TXT_ACTION_IS_IRREVERSIBLE'],
            'TXT_ATTENTION_SYSTEM_FUNCTIONALITY_AT_RISK' => $_ARRAYLANG['TXT_ATTENTION_SYSTEM_FUNCTIONALITY_AT_RISK'],
            'TXT_JOBS_SORTING'                           => $_ARRAYLANG['TXT_JOBS_SORTING'],
            'TXT_JOBS_SORTTYPE'                          => $_ARRAYLANG['TXT_JOBS_SORTTYPE'],
        ));
        $this->_objTpl->setGlobalVariable(array(
            'TXT_DELETE'    => $_ARRAYLANG['TXT_DELETE']
        ));

        // Add a new category
        if (isset($_POST['addLoc']) AND ($_POST['addLoc']==true)) {
             $locName = get_magic_quotes_gpc() ? strip_tags($_POST['newLocName']) : addslashes(strip_tags($_POST['newLocName']));
             if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_jobs_location (name)
                                 VALUES ('$locName')")) {
                 \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_ADDED_SUCCESSFUL']);
             } else {
                 \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
             }
            static::clearCache();
        }

        // Modify a new category
        if (isset($_POST['modLoc']) AND ($_POST['modLoc']==true)) {
            $status = true;
            foreach ($_POST['locName'] as $id => $name) {
                $name = get_magic_quotes_gpc() ? strip_tags($name) : addslashes(strip_tags($name));
                $id=intval($id);

// Unused
//                $sorting = !empty($_REQUEST['sortStyle'][$id]) ? contrexx_addslashes($_REQUEST['sortStyle'][$id]) : 'alpha';
                if (!$objDatabase->Execute("UPDATE ".DBPREFIX."module_jobs_location
                                  SET name='$name'
                                WHERE id=$id")
                ) {
                    $status = false;
                }
            }
            if ($status) {
                \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_UPDATED_SUCCESSFUL']);
            } else {
                \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
            }
            static::clearCache();
        }

        $query = "SELECT `id`,
                           `name`
                      FROM `".DBPREFIX."module_jobs_location`
                  ORDER BY `id` asc";
        $objResult = $objDatabase->Execute($query);

        $this->_objTpl->setCurrentBlock('row');
        $i=0;

        while ($objResult !== false && !$objResult->EOF) {
            $class = (($i % 2) == 0) ? "row1" : "row2";
// Unused
//            $sorting = $objResult->fields['sort_style'];
            $this->_objTpl->setVariable(array(
                'JOBS_ROWCLASS'   => $class,
                'JOBS_LOC_ID'      => $objResult->fields['id'],
                'JOBS_LOC_NAME'      => stripslashes($objResult->fields['name']),
            ));
            $this->_objTpl->parseCurrentBlock('row');
            $i++;
            $objResult->MoveNext();
        };
    }



    /**
    * Delete the jobs locations
    * @global    object     $objDatabase
    * @global    array      $_ARRAYLANG[news*]
    */
    function deleteLoc() {
        global $objDatabase,$_ARRAYLANG;

        if (isset($_GET['locId'])) {
            $locId=intval($_GET['locId']);

            if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_jobs_rel_loc_jobs WHERE location=$locId") && $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_jobs_location WHERE id=$locId")) {
                \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL']);
            } else {
                \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
            }
            static::clearCache();
        }
        unset($locId);
        if (is_array($_POST['selectedId'])) {
            $status = true;
            foreach ($_POST['selectedId'] as $value) {
                $locId=intval($value);

                if (!($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_jobs_rel_loc_jobs WHERE location=$locId") && $objDatabase->Execute("DELETE FROM ".DBPREFIX."module_jobs_location WHERE id=$locId"))) {
                    $status = false;
                }
            }
            if ($status) {
                \Message::ok($_ARRAYLANG['TXT_DATA_RECORD_DELETED_SUCCESSFUL']);
            } else {
                \Message::error($_ARRAYLANG['TXT_DATABASE_QUERY_ERROR']);
            }
            static::clearCache();
        }

        \Cx\Core\Csrf\Controller\Csrf::redirect(
            \Cx\Core\Routing\Url::fromBackend('Jobs', 'loc')
        );
    }


    /**
    * Gets only the body content and deleted all the other tags
    * @param     string     $fullContent      HTML-Content with more than BODY
    * @return    string     $content          HTML-Content between BODY-Tag
    */
    function filterBodyTag($fullContent)
    {
        if (empty($fullContent)) {
            return $fullContent;
        }
        $posBody = 0;
        $posStartBodyContent = 0;
        $arrayMatches = array();
        $res = preg_match_all('/<body[^>]*>/i', $fullContent, $arrayMatches);
        if ($res) {
            $bodyStartTag = $arrayMatches[0][0];
            // Position des Start-Tags holen
            $posBody = strpos($fullContent, $bodyStartTag, 0);
            // Beginn des Contents ohne Body-Tag berechnen
            $posStartBodyContent = $posBody + strlen($bodyStartTag);
        }
        $posEndTag=strlen($fullContent);
        $res=preg_match_all('/<\/body>/i', $fullContent, $arrayMatches);
        if ($res) {
            $bodyEndTag=$arrayMatches[0][0];
            // Position des End-Tags holen
            $posEndTag = strpos($fullContent, $bodyEndTag, 0);
            // Content innerhalb der Body-Tags auslesen
         }
         $content = substr($fullContent, $posStartBodyContent, $posEndTag  - $posStartBodyContent);
         return $content;
    }


    /**
    * Create the RSS-Feed
    */
    function createRSS()
    {
        \Env::get('ClassLoader')->loadFile(ASCMS_MODULE_PATH.'/Jobs/Controller/RssFeed.class.php');
        $rssFeed = new RssFeed();
        $rssFeed->channelTitle = "Jobsystem";
        $rssFeed->channelDescription = "";
        $rssFeed->xmlType = "headlines";
        $rssFeed->createXML();
        $rssFeed->xmlType = "fulltext";
        $rssFeed->createXML();
    }
}
