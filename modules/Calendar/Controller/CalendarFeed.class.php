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
 * Calendar Class RSS Feed
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx <info@cloudrexx.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_calendar
 */
namespace Cx\Modules\Calendar\Controller;

/**
 * CalendarFeed
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx <info@cloudrexx.com>
 * @version     $Id: index.inc.php,v 1.00 $
 * @package     cloudrexx
 * @subpackage  module_calendar
 */
class CalendarFeed extends CalendarLibrary {
    /**
     * Object Event manager
     *
     * @access public
     * @var object
     */
    private $objEventManager;

    /**
     * Constructor
     *
     * @global array $_CONFIG
     * @global object $objDatabase
     * @param object $objEventManager
     */
    function __construct($objEventManager){
        global $_CONFIG, $objDatabase;

        $this->objEventManager = $objEventManager;
        $this->domainUrl = ASCMS_PROTOCOL."://".$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET."/";
    }

    /**
     * Create's new rss feed for the calendar module
     *
     * @global array $_CONFIG
     * @global object $objDatabase
     */
    function creatFeed(){
        global $_CONFIG, $objDatabase;

        $this->getFrontendLanguages();
        $this->getSettings();

        $this->objEventManager->getEventlist();

        foreach($this->arrFrontendLanguages as $langKey => $arrFrontendLanguage) {
            $objRSSWriter = new \RSSWriter();
            $objRSSWriter->characterEncoding = CONTREXX_CHARSET;
            $objRSSWriter->channelTitle = contrexx_raw2xml($this->arrSettings['rssFeedTitle']);
            $objRSSWriter->channelLink = contrexx_raw2xml($this->domainUrl.'index.php?section='.$this->moduleName);
            $objRSSWriter->channelDescription = contrexx_raw2xml($this->arrSettings['rssFeedDescription']);
            $objRSSWriter->channelLanguage = contrexx_raw2xml($arrFrontendLanguage['lang']);
            $objRSSWriter->channelCopyright = contrexx_raw2xml('Copyright '.date('Y').', '.$this->domainUrl);

            if (!empty($this->arrSettings['rssFeedImage'])) {
                $objRSSWriter->channelImageUrl = $this->arrSettings['rssFeedImage'];
                $objRSSWriter->channelImageTitle = $objRSSWriter->channelTitle;
                $objRSSWriter->channelImageLink = $objRSSWriter->channelLink;
            }

            $objRSSWriter->channelWebMaster = $_CONFIG['coreAdminEmail'];
            $objRSSWriter->channelLastBuildDate = date('r', time());

            foreach($this->objEventManager->eventList as $eventKey => $objEvent) {
                $objFWUser = \FWUser::getFWUserObject();




                $showIn = explode(',', $objEvent->showIn);


                if(in_array($arrFrontendLanguage['id'], $showIn)) {

                    $itemTitle = contrexx_raw2xml(html_entity_decode($objEvent->arrData['title'][$arrFrontendLanguage['id']], ENT_QUOTES, CONTREXX_CHARSET));
                    $itemLink = $objEvent->type==0 ? $this->domainUrl.$this->objEventManager->_getDetailLink($objEvent) : $objEvent->arrData['redirect'][$arrFrontendLanguage['id']];
                    $itemLink = contrexx_raw2xml(html_entity_decode($itemLink));
                    $itemDescription = contrexx_raw2xml($objEvent->arrData['description'][$arrFrontendLanguage['id']]);

                    if ($objUser = $objFWUser->objUser->getUser(intval($objEvent->author))) {
                        $itemAuthor = $objUser->getEmail();
                    } else {
                        $itemAuthor = "unknown";
                    }

                    $itemAuthor = contrexx_raw2xml($itemAuthor);
                    $itemCategory = array();
                    $itemComments = null;
                    $itemEnclosure = array();
                    $itemGuid = array();
                    $itemPubDate = $objEvent->startDate->getTimestamp();
                    $itemSource = array();



                    $objRSSWriter->addItem($itemTitle,$itemLink,$itemDescription,$itemAuthor,$itemCategory,$itemComments,$itemEnclosure,$itemGuid,$itemPubDate,$itemSource);
                }
            }

            $objRSSWriter->feedType = 'xml';
            $objRSSWriter->xmlDocumentPath = \Env::get('cx')->getWebsiteFeedPath().'/calendar_all_'.$arrFrontendLanguage['lang'].'.'.$objRSSWriter->feedType;
            $objRSSWriter->write();
        }


    }
}
