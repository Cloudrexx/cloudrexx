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
 * Teasers
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @version 1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_news
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Core_Modules\News\Controller;

/**
 * Teasers
 *
 * class to show the news teasers
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @access public
 * @version 1.0.0
 * @package     cloudrexx
 * @subpackage  coremodule_news
 */
class Teasers extends \Cx\Core_Modules\News\Controller\NewsLibrary
{
    public $_pageTitle;
    public $_objTpl;
    public $administrate;
    public $arrTeaserTemplates = array();
    public $arrTeaserFrameTemplates = array();

    public $arrTeaserFrames;
    public $arrTeaserFrameNames;
    public $arrTeasers;

    public $arrFrameTeaserIds;

    public $arrNewsTeasers = array();
    public $arrNewsCategories = array();

    public $_currentXMLElementId;
    public $_currentXMLElement;
    public $_currentXMLArrayToFill;

    /**
     * Language id
     *
     * @var integer
     */
    protected $langId = null;

    /**
     * Creates a new Teaser controller
     * If there are any news with scheduled publishing $nextUpdateDate will
     * contain the date when the next news changes its publishing state.
     * If there are are no news with scheduled publishing $nextUpdateDate will
     * be null.
     * @param boolean $administrate (optional) True for backend, false otherwise (default)
     * @param integer $langId (optional) Language ID, if not specified FRONTEND_LANG_ID is used
     * @param \DateTime $nextUpdateDate (reference) DateTime of the next change
     */
    public function __construct($administrate = false, $langId = null, &$nextUpdateDate = null)
    {
        parent::__construct();
        $this->administrate = $administrate;

        $this->langId = $langId;
        if (null === $langId) {
            $this->langId = FRONTEND_LANG_ID;
        }
        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_initialize($nextUpdateDate);
    }

    /**
     * Initializes this controller
     * If there are any news with scheduled publishing $nextUpdateDate will
     * contain the date when the next news changes its publishing state.
     * If there are are no news with scheduled publishing $nextUpdateDate will
     * be null.
     * @param \DateTime $nextUpdateDate (reference) DateTime of the next change
     */
    protected function _initialize(&$nextUpdateDate = null)
    {
        $this->initializeTeasers($nextUpdateDate);
        $this->initializeTeaserFrames();
        //$this->_initializeTeaserTemplates();
        $this->initializeTeaserFrameTemplates();
    }

    /**
     * Loads the teasers parsed by this controller from DB
     * If there are any news with scheduled publishing $nextUpdateDate will
     * contain the date when the next news changes its publishing state.
     * If there are are no news with scheduled publishing $nextUpdateDate will
     * be null.
     * @param \DateTime $nextUpdateDate (reference) DateTime of the next change
     */
    protected function initializeTeasers(&$nextUpdateDate = null)
    {
        global $objDatabase;

        $this->arrTeasers = array();

        $objResult = $objDatabase->Execute("
            SELECT tblN.id,
                   tblN.date,
                   tblN.typeid,
                   tblN.userid,
                   tblN.teaser_frames,
                   tblN.redirect,
                   tblN.teaser_show_link,
                   tblN.teaser_image_path,
                   tblN.teaser_image_thumbnail_path,
                   tblN.startdate,
                   tblN.enddate,
                   tblN.allow_comments,
                   tblN.author,
                   tblN.author_id,
                   tblN.publisher_id,
                   tblN.publisher,
                   tblN.enable_tags,
                   tblN.source,
                   tblN.url1,
                   tblN.url2,
                   tblN.changelog,
                   tblL.title,
                   tblL.text AS teaser_full_text,
                   tblL.teaser_text
              FROM ".DBPREFIX."module_news AS tblN
             INNER JOIN ".DBPREFIX."module_news_locale AS tblL ON tblL.news_id=tblN.id
             WHERE tblL.lang_id=". contrexx_input2int($this->langId) ."
               AND tblN.teaser_frames != '' ".
              ($this->administrate == false
                ? " AND tblN.validated='1'
                    AND tblN.status='1'
                    AND tblL.is_active=1
                    AND (tblN.startdate<='".date('Y-m-d H:i:s').
                    "' OR tblN.startdate='0000-00-00 00:00:00') AND (tblN.enddate>='".
                    date('Y-m-d H:i:s')."' OR tblN.enddate='0000-00-00 00:00:00')"
                : "" ).
              ($this->arrSettings['news_message_protection'] == '1' && !\Permission::hasAllAccess()
                ? (($objFWUser = \FWUser::getFWUserObject()) && $objFWUser->objUser->login()
                    ? " AND (tblN.frontend_access_id IN (".implode(',',
                          array_merge(array(0), $objFWUser->objUser->getDynamicPermissionIds())).
                        ") OR userid = ".$objFWUser->objUser->getId().") "
                    : " AND tblN.frontend_access_id=0 ")
                : '')."
             ORDER BY date DESC");

        $nextUpdateDate = null;
        if ($objResult !== false) {
            $cx = \Cx\Core\Core\Controller\Cx::instanciate();
            while (!$objResult->EOF) {
                if (
                    $objResult->fields['startdate'] != '0000-00-00 00:00:00' &&
                    $objResult->fields['enddate'] != '0000-00-00 00:00:00'
                ) {
                    $startDate = new \DateTime($objResult->fields['startdate']);
                    $endDate = new \DateTime($objResult->fields['enddate']);
                    if (
                        $endDate > new \DateTime() &&
                        (
                            !$nextUpdateDate ||
                            $endDate < $nextUpdateDate
                        )
                    ) {
                        $nextUpdateDate = $endDate;
                    }
                    if (
                        $startDate > new \DateTime() &&
                        (
                            !$nextUpdateDate ||
                            $startDate < $nextUpdateDate
                        )
                    ) {
                        $nextUpdateDate = $startDate;
                    }
                }

                $arrFrames = explode(';', $objResult->fields['teaser_frames']);
                foreach ($arrFrames as $frameId) {
                    if (!isset($this->arrFrameTeaserIds[$frameId])) {
                        $this->arrFrameTeaserIds[$frameId] = array();
                    }
                    array_push($this->arrFrameTeaserIds[$frameId], $objResult->fields['id']);
                }
                $extUrl = '';
                if (!empty($objResult->fields['redirect'])) {
                    if (
                        preg_match(
                            '/\[\['.\Cx\Core\Routing\NodePlaceholder::NODE_URL_PCRE.'\]\]/ix',
                            $objResult->fields['redirect']
                        )
                    ) {
                        $domainRepo = new \Cx\Core\Net\Model\Repository\DomainRepository();
                        $extUrl = '(' . $domainRepo->getMainDomain()->getName() . ')';
                    } else {
                        try {
                            $url = \Cx\Core\Routing\Url::fromMagic($objResult->fields['redirect']);
                            $extUrl = '('.$url->getDomain().')';
                        } catch (\Cx\Core\Routing\UrlException $e) {}
                    }
                }
                if ($this->administrate == false) {
                    $author = \FWUser::getParsedUserTitle($objResult->fields['author_id'], $objResult->fields['author']);
                } else {
                    $author = '';
                }
                if (!empty($objResult->fields['teaser_image_thumbnail_path'])) {
                    $image = $objResult->fields['teaser_image_thumbnail_path'];
                } elseif (!empty($objResult->fields['teaser_image_path']) && $this->arrSettings['use_thumbnails'] && file_exists($cx->getWebsitePath() .'/' .\ImageManager::getThumbnailFilename($objResult->fields['teaser_image_path']))) {
                    $image = \ImageManager::getThumbnailFilename($objResult->fields['teaser_image_path']);
                } elseif (!empty($objResult->fields['teaser_image_path'])) {
                    $image = $objResult->fields['teaser_image_path'];
                } else {
                    // get (customized) default image
                    $news = $cx->getComponent('News');
                    $componentDir = $news->getDirectory();
                    $defaultImage = $componentDir . '/View/Media/pixel.gif';
                    $cl = $cx->getClassLoader();
                    $image = $cl->getWebFilePath($defaultImage);
                }
                $newsCategories = $this->getCategoriesByNewsId($objResult->fields['id']);
                $this->arrTeasers[$objResult->fields['id']] = array(
                    'id'                            => $objResult->fields['id'],
                    'newsid'                        => $objResult->fields['id'],
                    'date'                          => $objResult->fields['date'],
                    'typeid'                        => $objResult->fields['typeid'],
                    'newsdate'                      => $objResult->fields['date'],
                    'title'                         => $objResult->fields['title'],
                    'newstitle'                     => $objResult->fields['title'],
                    'teaser_frames'                 => $objResult->fields['teaser_frames'],
                    'redirect'                      => $objResult->fields['redirect'],
                    'commentactive'                 => $objResult->fields['allow_comments'],
                    'author_id'                     => $objResult->fields['author_id'],
                    'publisher_id'                  => $objResult->fields['publisher_id'],
                    'enable_tags'                   => $objResult->fields['enable_tags'],
                    'source'                        => $objResult->fields['source'],
                    'url1'                          => $objResult->fields['url1'],
                    'url2'                          => $objResult->fields['url2'],
                    'changelog'                     => $objResult->fields['changelog'],
                    'ext_url'                       => $extUrl,
                    'category'                      => implode(', ', contrexx_raw2xhtml($newsCategories)),
                    'category_id'                   => array_keys($newsCategories),
                    'teaser_full_text'              => $objResult->fields['teaser_full_text'],
                    'text'                          => $objResult->fields['teaser_full_text'],
                    'teaser_text'                   => $objResult->fields['teaser_text'],
                    'teaser_show_link'              => $objResult->fields['teaser_show_link'],
                    'author'                        => contrexx_raw2xhtml($author),
                    'publisher'                     => $objResult->fields['publisher'],
                    'teaser_image_path'             => $image,
                    'teaser_image_thumbnail_path'   => $image,
                );
                $objResult->MoveNext();
            }
        }
    }


    function initializeTeaserFrames($id = 0)
    {
        list($this->arrTeaserFrames, $this->arrTeaserFrameNames) = static::getTeaserFrames($id);
    }

    public static function getTeaserFrames($id = 0) {
        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $db = $cx->getDb()->getAdoDb();

        $arrTeaserFrames = array();
        $arrTeaserFrameNames = array();

        if ($id != 0) {
            $objResult = $db->SelectLimit("SELECT id, frame_template_id, name FROM ".DBPREFIX."module_news_teaser_frame WHERE id=".$id, 1);
        } else {
            $objResult = $db->Execute("SELECT id, frame_template_id, name FROM ".DBPREFIX."module_news_teaser_frame ORDER BY name");
        }
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $arrTeaserFrames[$objResult->fields['id']] = array(
                    'id'                => $objResult->fields['id'],
                    'frame_template_id' => $objResult->fields['frame_template_id'],
                    'name'              => $objResult->fields['name']
                );

                $arrTeaserFrameNames[$objResult->fields['name']] = $objResult->fields['id'];
                $objResult->MoveNext();
            }
        }

        return array($arrTeaserFrames, $arrTeaserFrameNames);
    }


    /**
    * Inizialize teaser frame templates
    *
    * @access private
    */
    function initializeTeaserFrameTemplates($id = 0)
    {
        global $objDatabase;

        if ($id == 0) {
            $objResult = $objDatabase->Execute("SELECT id, description, html, source_code_mode FROM ".DBPREFIX."module_news_teaser_frame_templates");
        } else {
            $objResult = $objDatabase->Execute("SELECT id, description, html, source_code_mode FROM ".DBPREFIX."module_news_teaser_frame_templates WHERE id=".$id);
        }
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                $this->arrTeaserFrameTemplates[$objResult->fields['id']] = array(
                    'id'                => $objResult->fields['id'],
                    'description'       => $objResult->fields['description'],
                    'html'              => $objResult->fields['html'],
                    'source_code_mode'  => $objResult->fields['source_code_mode']
                );
                $objResult->MoveNext();
            }
        }
    }


    function getTeaserFrame($teaserFrameId, $templateId)
    {
        return $this->_getTeaserFrame($teaserFrameId, $templateId);
    }


    /**
     * Set teaser frames
     *
     * @param array  $arrTeaserFrames array of teaser frame names
     * @param string $code            code
     */
    function setTeaserFrames($arrTeaserFrames, &$code)
    {
        $arrTeaserFramesNames = array_flip($this->arrTeaserFrameNames);

        foreach ($arrTeaserFrames as $teaserFrameName) {
            $arrMatches = preg_grep('/^'.$teaserFrameName.'$/i', $arrTeaserFramesNames);
            if (empty($arrMatches)) {
                continue;
            }
            if (count($arrMatches) > 0) {
                $frameId    = array_keys($arrMatches);
                $id         = $frameId[0];
                $templateId = $this->arrTeaserFrames[$id]['frame_template_id'];
                $code       = str_replace(
                    "{TEASERS_" . $teaserFrameName . "}",
                    $this->_getTeaserFrame($id, $templateId),
                    $code
                );
            }
        }
    }


    /**
    * Get teaser frame
    *
    * Returns the selected teaser frame by $id with its teaserboxes
    *
    * @access private
    * @return string
    */
    function _getTeaserFrame($id, $templateId)
    {
        global $_CORELANG;

        $teaserFrame = "";

        $arrTeaserBlocks = array();
        if (isset($this->arrTeaserFrameTemplates[$templateId]['html'])) {
            $teaserFrame = $this->arrTeaserFrameTemplates[$templateId]['html'];
            if (preg_match_all('/<!-- BEGIN (teaser_[0-9]+) -->/ms', $teaserFrame, $arrTeaserBlocks)) {
                $funcSort = function ($a, $b) {
                    $aNr = preg_replace('/^[^_]+_/', '', $a);
                    $bNr = preg_replace('/^[^_]+_/', '', $b);
                    if ($aNr == $bNr) {
                        return 0;
                    }

                    return ($aNr < $bNr) ? -1 : 1;
                };
                usort($arrTeaserBlocks[0], $funcSort);
                usort($arrTeaserBlocks[1], $funcSort);
                $arrMatch = array();
                foreach ($arrTeaserBlocks[1] as $nr => $teaserBlock) {
                    if (preg_match('/<!-- BEGIN '.$teaserBlock.' -->(.*)<!-- END '.$teaserBlock.' -->/s', $teaserFrame, $arrMatch)) {
                        $teaserBlockCode = $arrMatch[1];
                    } else {
                        $teaserBlockCode = '';
                    }

                    if (isset($this->arrFrameTeaserIds[$id][$nr])) {
                        if (!empty($this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['redirect'])) {
                            $teaserUrl = $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['redirect'];
                        } else {
                            $teaserUrl = \Cx\Core\Routing\Url::fromModuleAndCmd(
                                'News',
                                $this->findCmdById(
                                    'details',
                                    $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['category_id']
                                ),
                                $this->langId,
                                array(
                                    'newsid' => $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['id'],
                                    'teaserId' => $this->arrTeaserFrames[$id]['id'],
                                )
                            );
                        }
                        $teaserBlockCode = str_replace('{TEASER_CATEGORY}', $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['category'], $teaserBlockCode);
                        $teaserBlockCode = str_replace('{TEASER_LONG_DATE}', date(ASCMS_DATE_FORMAT, $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['date']), $teaserBlockCode);
                        $teaserBlockCode = str_replace('{TEASER_DATE}', date(ASCMS_DATE_FORMAT_DATE, $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['date']), $teaserBlockCode);
                        $teaserBlockCode = str_replace('{TEASER_TIME}', date(ASCMS_DATE_FORMAT_TIME, $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['date']), $teaserBlockCode);
                        $teaserBlockCode = str_replace('{TEASER_TIMESTAMP}', $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['date'], $teaserBlockCode);
                        $teaserBlockCode = str_replace('{TEASER_TITLE}', contrexx_raw2xhtml($this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['title']), $teaserBlockCode);
                        $teaserBlockCode = str_replace('{TEASER_MORE}', $_CORELANG['TXT_READ_MORE'], $teaserBlockCode);
                        if ($this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['teaser_show_link']) {
                            $teaserBlockCode = str_replace('{TEASER_URL}', $teaserUrl, $teaserBlockCode);
                            $teaserBlockCode = str_replace('{TEASER_URL_TARGET}', empty($this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['redirect']) ? '_self' : '_blank', $teaserBlockCode);
                            $teaserBlockCode = str_replace('<!-- BEGIN teaser_link -->', '', $teaserBlockCode);
                            $teaserBlockCode = str_replace('<!-- END teaser_link -->', '', $teaserBlockCode);
                        } else {
                            $teaserBlockCode = preg_replace('/<!-- BEGIN teaser_link -->[\S\s]*<!-- END teaser_link -->/', '', $teaserBlockCode);
                        }
                        $teaserBlockCode = str_replace('{TEASER_IMAGE_PATH}', $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['teaser_image_path'], $teaserBlockCode);
                        $teaserBlockCode = str_replace('{TEASER_TEXT}', $this->arrSettings['news_use_teaser_text'] ? nl2br($this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['teaser_text']) : '', $teaserBlockCode);
                        $teaserBlockCode = str_replace('{TEASER_FULL_TEXT}', $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['teaser_full_text'], $teaserBlockCode);
                        $teaserBlockCode = str_replace('{TEASER_AUTHOR}', $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['author'], $teaserBlockCode);
                        $teaserBlockCode = str_replace('{TEASER_EXT_URL}', $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]]['ext_url'], $teaserBlockCode);

                        $teaserBlockTpl = new \Cx\Core\Html\Sigma();
                        $teaserBlockTpl->setTemplate(
                            $teaserBlockCode,
                            false
                        );
                        $emulatedResult = new \stdClass();
                        $emulatedResult->fields = $this->arrTeasers[$this->arrFrameTeaserIds[$id][$nr]];
                        $this->parseNewsPlaceholders(
                            $teaserBlockTpl,
                            $emulatedResult,
                            $teaserUrl
                        );
                        $teaserBlockCode = $teaserBlockTpl->get();
                    } elseif ($this->administrate) {
                        $teaserBlockCode = preg_replace('/{(NEWS|TEASER_[A-Z0-9_]+)}/', '\1', $teaserBlockCode);
                    } else {
                        $teaserBlockCode = '&nbsp;';
                    }

                    if (!$this->administrate) {
                        $teaserFrame = preg_replace('/<!-- BEGIN '.$teaserBlock.' -->[\S\s]*<!-- END '.$teaserBlock.' -->/', $teaserBlockCode, $teaserFrame);
                    } else {
                        $teaserFrame = preg_replace('/(<!-- BEGIN '.$teaserBlock.' -->)[\S\s]*(<!-- END '.$teaserBlock.' -->)/', '<table cellspacing="0" cellpadding="0" style="border:1px dotted #aaaaaa;"><tr><td>'.$teaserBlockCode.'</td></tr></table>', $teaserFrame);
                    }
                }
            }
        }
        return $teaserFrame;
    }


    function getFirstTeaserFrameTemplateId()
    {
        reset($this->arrTeaserFrameTemplates);
        $arrFrameTeamplte = current($this->arrTeaserFrameTemplates);
        return $arrFrameTeamplte['id'];
    }


    function getTeaserFrameTemplateMenu($selectedId)
    {
        $menu = "";
        foreach ($this->arrTeaserFrameTemplates as $teaserFrameTemplateId => $teaserFrameTemplate) {
            if ($selectedId == $teaserFrameTemplateId) {
                $selected = "selected=\"selected\"";
            } else {
                $selected = "";
            }
            $menu .= "<option value=\"".$teaserFrameTemplateId."\" ".$selected.">".$teaserFrameTemplate['description']."</option>\n";
        }
        return $menu;
    }


    function updateTeaserFrame($id, $templateId, $name)
    {
        global $objDatabase;

        if ($objDatabase->Execute("UPDATE ".DBPREFIX."module_news_teaser_frame SET frame_template_id=".$templateId.", name='".$name."' WHERE id=".$id) !== false) {
            return true;
        } else {
            return false;
        }
    }


    function addTeaserFrame($id, $templateId, $name)
    {
        global $objDatabase;

        if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_news_teaser_frame (`frame_template_id`, `name`) VALUES (".$templateId.", '".$name."')") !== false) {
            return true;
        } else {
            return false;
        }
    }


    function updateTeaserFrameTemplate($id, $description, $html, $sourceCodeMode)
    {
        global $objDatabase;

        if ($objDatabase->Execute("UPDATE ".DBPREFIX."module_news_teaser_frame_templates SET description='".$description."', html='".$html."', source_code_mode='".$sourceCodeMode."' WHERE id=".$id) !== false) {
            return true;
        } else {
            return false;
        }
    }


    function addTeaserFrameTemplate($description, $html, $sourceCodeMode)
    {
        global $objDatabase;

        if ($objDatabase->Execute("INSERT INTO ".DBPREFIX."module_news_teaser_frame_templates (`description`, `html`, `source_code_mode`) VALUES ('".$description."', '".$html."', '".$sourceCodeMode."')") !== false) {
            return true;
        } else {
            return false;
        }
    }


    function deleteTeaserFrame($frameId)
    {
        global $objDatabase;

        if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news_teaser_frame WHERE id=".$frameId) !== false) {
            return true;
        } else {
            return false;
        }
    }


    function deleteTeaserFrameTeamplte($templateId)
    {
        global $objDatabase, $_ARRAYLANG;

        foreach ($this->arrTeaserFrames as $arrTeaserFrame) {
            if ($arrTeaserFrame['frame_template_id'] == $templateId) {
                return $_ARRAYLANG['TXT_COULD_NOT_DELETE_TEMPLATE_TEXT'];
            }
        }

        if ($objDatabase->Execute("DELETE FROM ".DBPREFIX."module_news_teaser_frame_templates WHERE id=".$templateId) !== false) {
            return true;
        } else {
            return false;
        }
    }


    function isUniqueFrameName($frameId, $frameName)
    {
        $arrFrameNames = array_flip($this->arrTeaserFrameNames);
        $arrEqualFrameNames = preg_grep('/^'.$frameName.'$/i', $arrFrameNames);

        if (count($arrEqualFrameNames) == 0 || array_key_exists($frameId, $arrEqualFrameNames)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Getter for language id
     *
     * @return integer
     */
    function getLangId()
    {
        return $this->langId;
    }

    /**
     * Set lang id
     *
     * @param integer $langId
     */
    function setLangId($langId)
    {
        $this->langId = $langId;
    }

}
