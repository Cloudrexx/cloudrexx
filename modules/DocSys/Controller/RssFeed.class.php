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
 * Docsys RSS XML Feed
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_docsys
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\DocSys\Controller;
/**
 * Docsys RSS XML Feed
 *
 * Produces the RSS XML Feedfile of the latest docsys entries
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_docsys
 */
class RssFeed
{
    var $xmlType;
    var $filePath;
    var $fileName = array();
    var $limit;
    var $langId;

    var $channelTitle;
    var $channelLink;
    var $channelDescription;
    var $channelLanguage;
    var $channelCopyright;
    var $channelGenerator;
    var $channelWebMaster;
    var $itemLink;


    /**
    * Constructor
    *
    * @global    array
    * @global    InitCMS
    * @global    ADONewConnection
    */
    function __construct()
    {
        global $_CONFIG, $objInit;

        $this->langId=$objInit->userFrontendLangId;
        $langShort = \FWLanguage::getLanguageParameter($this->langId, 'lang');

        $this->xmlType = "headlines";
        $this->filePath = \Env::get('cx')->getWebsiteFeedPath();
        $this->channelLink = ASCMS_PROTOCOL."://".$_SERVER['SERVER_NAME'];
        $this->channelCopyright = ASCMS_PROTOCOL."://".$_SERVER['SERVER_NAME'];
        $this->channelGenerator = $_CONFIG['coreCmsName'];
        $this->channelWebMaster = $_CONFIG['coreAdminEmail'];
        $this->channelLanguage  = $langShort;
        $this->itemLink = ASCMS_PROTOCOL."://".$_SERVER['SERVER_NAME']."/?section=DocSys".MODULE_INDEX."&amp;cmd=details&amp;id=";
        $this->fileName[1] = 'docsys_headlines_'.$langShort.'.xml';
        $this->fileName[2] = 'docsys_'.$langShort.'.xml';

        $this->limit=20;
        if($this->limit<1 OR $this->limit>100){
            $this->limit=10;
        }
    }


    /**
    * checks the file and folder permissions
    *
    * @return   boolean
    */
    function checkPermissions()
    {
        if(is_writeable($this->filePath) AND is_dir($this->filePath)){
            return true;
        }
        else{
            return false;
        }
    }

    /**
    * createXML: parse out the XML
    *
    * @global   integer
    * @global   ADONewConnection
    */
    function createXML()
    {
        global $_LANGID, $objDatabase;

        if ($this->checkPermissions()){
            $xmlOutput = "";
            $xmlOutput .= "<?xml version=\"1.0\" encoding=\"".CONTREXX_CHARSET."\"?>\n";
            $xmlOutput .= "<rss version=\"2.0\">\n";
            $xmlOutput .= "<channel>\n";
            $xmlOutput .= "<title>".$this->channelTitle."</title>\n";
            $xmlOutput .= "<description>".$this->channelDescription."</description>\n";
            $xmlOutput .= "<link>".$this->channelLink."</link>\n";
            $xmlOutput .= "<copyright>".$this->channelCopyright."</copyright>\n";
            $xmlOutput .= "<webMaster>".$this->channelWebMaster."</webMaster>\n";
            $xmlOutput .= "<generator>".$this->channelGenerator."</generator>\n";
            $xmlOutput .= "<lastBuildDate>".date('r',time())."</lastBuildDate>\n";
            $xmlOutput .= "<language>".$this->channelLanguage."</language>\n";

//            $query = "SELECT n.id AS docId,
//                               n.date,
//                               n.title,
//                               n.text,
//                               n.source,
//                               u.firstname,
//                               u.lastname,
//                               n.lang,
//                               n.userid,
//                               u.id
//                        FROM ".DBPREFIX."module_docsys AS n,
//                             ".DBPREFIX."access_users AS u
//                        WHERE n.userid = u.id AND n.lang = ".$_LANGID."
//                        ORDER BY n.id DESC";

            $query = "SELECT n.id AS docId,
                               n.date,
                               n.title,
                               n.text,
                               n.source,
                               n.lang,
                               n.userid,
                               u.id
                        FROM ".DBPREFIX."module_docsys".MODULE_INDEX." AS n,
                             ".DBPREFIX."access_users AS u
                        WHERE n.userid = u.id AND n.lang = ".$_LANGID."
                        ORDER BY n.id DESC";
            $objResult = $objDatabase->SelectLimit($query, $this->limit);

            while (!$objResult->EOF) {
                $xmlOutput .= "<item>\n";
                $xmlOutput .= "<title>".strip_tags(stripslashes($objResult->fields['title']))."</title>\n";

                if ($this->xmlType == 'fulltext'){
                    $xmlOutput .= "<description>".htmlspecialchars(stripslashes($objResult->fields['text']), ENT_QUOTES, CONTREXX_CHARSET)."</description>\n";
                    //$xmlOutput .= "<description>".$objResult->fields['text')."</description>";
                    $xmlOutput .= "<source>".htmlspecialchars(stripslashes($objResult->fields['source']), ENT_QUOTES, CONTREXX_CHARSET)."</source>\n";
                }

                $xmlOutput .= "<pubDate>".date('r',time())."</pubDate>\n";
                $xmlOutput .= "<link>".$this->itemLink.$objResult->fields['docId']."</link>\n";
                $xmlOutput .= "</item>\n";
                $objResult->MoveNext();
            }
            $xmlOutput .= "</channel>\n";
            $xmlOutput .= "</rss>";

            if ($this->xmlType == 'fulltext'){
                $fileHandle = fopen($this->filePath."/".$this->fileName[2],"w+");
            }else {
                $fileHandle = fopen($this->filePath."/".$this->fileName[1],"w+");
            }
            if($fileHandle){
                @fwrite($fileHandle,$xmlOutput);
                @fclose($fileHandle);
            }
        }
    }
}
?>
