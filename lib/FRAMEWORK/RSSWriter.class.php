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
 * RSSWriter
 *
 * @copyright   CLOUDREXX CMS - ASTALAVISTA IT AG
 * @author Astalavista Development Team <thun@astalavista.ch>
 * @version 2.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 * @todo        Edit PHP DocBlocks!
 */

/**
 * RSSWriter
 * Creates RSS files
 *
 * @copyright   CLOUDREXX CMS - ASTALAVISTA IT AG
 * @author Astalavista Development Team <thun@astalavista.ch>
 * @version 2.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */
class RSSWriter {
    var $xmlDocumentPath;
    var $characterEncoding;
    var $xmlDocument;
    var $feedType = 'xml'; // allowed types: xml, js
    var $_rssVersion = '2.0';
    var $_xmlVersion = '1.0';
    var $_xmlElementLevel = 0;

    var $arrErrorMsg = array();
    var $arrWarningMsg = array();

    var $channelTitle = ''; //'Cloudrexx.com Neuste Videos';
    var $channelLink = ''; //'http://www.cloudrexx.com/podcast';
    var $channelDescription = ''; //'Neuste Videos';

    var $channelLanguage = '';
    var $channelCopyright = '';
    var $channelManagingEditor = '';
    var $channelWebMaster = '';
    var $channelPubDate = '';
    var $channelLastBuildDate = '';
    var $channelCategory = '';
    var $channelGenerator = '';
    var $channelDocs = '';
    var $channelCloud = '';
    var $channelTtl = '';

    var $channelImageUrl = '';
    var $channelImageTitle = '';
    var $channelImageLink = '';

    var $channelImageWidth = '';
    var $channelImageHeight = '';
    var $channelImageDescription = '';

    var $channelRating = '';

    var $channelTextInputTitle = '';
    var $channelTextInputDescription = '';
    var $channelTextInputName = '';
    var $channelTextInputLink = '';

    var $channelSkipHours = '';
    var $channelSkipDays = '';

    var $_arrItems = array();
    var $xmlItems = '';
    var $_currentItem = 0;

    /**
     * PHP5 contructor
     *
     */
    function __construct()
    {
        global $_CONFIG;

        $this->channelGenerator = $_CONFIG['coreCmsName'];
        $this->channelDocs = 'http://blogs.law.harvard.edu/tech/rss';
    }

    /**
     * Add item
     *
     * Add an item to the RSS feed
     *
     * @param stirng $title
     * @param string $link
     * @param stirng $description
     * @param string $author
     * @param array $arrCategory
     * @param string $comments
     * @param array $arrEnclosure
     * @param array $arrGuid
     * @param string $pubDate
     * @param array $arrSource
     * @return boolean
     */
    function addItem($title = '', $link = '', $description = '', $author = '', $arrCategory = array(), $comments = '', $arrEnclosure = array(), $arrGuid = array(), $pubDate = '', $arrSource = array())
    {
        global $_CORELANG;

        if (!empty($title) || !empty($description)) {
            array_push($this->_arrItems, array(
                'title'         => $title,
                'link'          => $link,
                'description'   => $description,
                'author'        => $author,
                'arrCategory'   => $arrCategory,
                'comments'      => $comments,
                'arrEnclosure'  => $arrEnclosure,
                'arrGuid'       => $arrGuid,
                'pubDate'       => $pubDate,
                'arrSource'     => $arrSource
            ));

            return true;
        } else {
            array_push($this->arrErrorMsg, $_CORELANG['TXT_MUST_DEFINE_RSS_TITLE_OR_DESCRIPTION']);
            return false;
        }
    }

    /**
     * Write feed
     *
     * Writes the rss feed.
     *
     * @return boolean
     */
    function write()
    {
        global $_CORELANG;

        if (!$this->_create()) {
            return false;
        }

        try {
            $objFile = new \Cx\Lib\FileSystem\File($this->xmlDocumentPath);
            $objFile->write(preg_replace('/\f/', '', $this->xmlDocument));
        } catch (\Cx\Lib\FileSystem\FileSystemException $e) {
            if (file_exists($this->xmlDocumentPath)) {
                array_push($this->arrErrorMsg, sprintf($_CORELANG['TXT_UNABLE_TO_WRITE_TO_FILE'], $this->xmlDocumentPath));
            } else {
                array_push($this->arrErrorMsg, sprintf($_CORELANG['TXT_UNABLE_TO_CREATE_FILE'], $this->xmlDocumentPath));
            }
            return false;
        }

        return true;
    }

    /**
     * Remove items
     *
     * Removes the items from the rss writer object.
     */
    function removeItems()
    {
        $this->_arrItems = array();
    }

    /**
     * Create feed
     *
     * Create the content of the RSS feed.
     *
     * @return boolean
     */
    function _create()
    {
        switch ($this->feedType) {
            case 'js':
                return $this->_createJS();
                break;

            case 'xml':
            default:
                return $this->_createXML();
                break;
        }
    }

    function _createXML()
    {
        global $_CORELANG;

        if (!empty($this->characterEncoding)) {
            $this->xmlDocument = $this->_visualElementLevel()."<?xml version=\"".$this->_xmlVersion."\" encoding=\"".$this->characterEncoding."\"?>\n";
        } else {
            array_push($this->arrErrorMsg, $_CORELANG['TXT_NO_CHARACTER_ENCODING_DEFINED']);
            return false;
        }
        $this->xmlDocument .= $this->_visualElementLevel()."<rss version=\"".$this->_rssVersion."\">\n";
        $this->_xmlElementLevel++;

        $this->xmlDocument .= $this->_visualElementLevel()."<channel>\n";
        $this->_xmlElementLevel++;
        if ($this->_addChannelTitle()) {
            if ($this->_addChannelLink()) {
                if ($this->_addChannelDescription()) {
                    $this->_addOptionalChannelElements();

                    $this->_parseItems();
                } else {
                array_push($this->arrErrorMsg, $_CORELANG['TXT_FEED_NO_CHANNEL_DESCRIPTION']);
                return false;
            }
            } else {
                array_push($this->arrErrorMsg, $_CORELANG['TXT_FEED_NO_CHANNEL_LINK']);
                return false;
            }
        } else {
            array_push($this->arrErrorMsg, $_CORELANG['TXT_FEED_NO_CHANNEL_TITLE']);
            return false;
        }
        $this->_xmlElementLevel--;
        $this->xmlDocument .= $this->_visualElementLevel()."</channel>\n";
        $this->_xmlElementLevel--;
        $this->xmlDocument .= $this->_visualElementLevel()."</rss>\n";

        return true;
    }

    function _createJS()
    {
        $this->xmlDocument = <<<XMLJSOUTPUT
if (document.body) {
    document.write('<div id="news_rss_feeds"></div>');
}
fnWinOnload = window.onload;
window.onload = function() {
    if (typeof(fnWinOnload) != 'undefined' && fnWinOnload != null) {
        fnWinOnload();
    }

    var rssFeedNews = new Array();
XMLJSOUTPUT;

        $nr = 0;

        foreach ($this->_arrItems as $arrItem) {
            $this->xmlDocument .= "rssFeedNews[".$nr."] = new Array();\n";
            $this->xmlDocument .= "rssFeedNews[".$nr."]['title'] = '".addslashes(($arrItem['title']))."';\n";
            $this->xmlDocument .= "rssFeedNews[".$nr."]['link'] = '".$arrItem['link']."';\n";
            $this->xmlDocument .= "rssFeedNews[".$nr."]['date'] = '".date(ASCMS_DATE_FORMAT_DATE, $arrItem['pubDate'])."';\n";
            $nr++;
        }
        $utf8_fixed = $this->_js_umlauts($this->xmlDocument);
        if ($utf8_fixed) $this->xmlDocument = $utf8_fixed;


        $this->xmlDocument .= <<<XMLJSOUTPUT
if (typeof rssFeedFontColor != "string") {
    rssFeedFontColor = "";
} else {
    rssFeedFontColor = "color:"+rssFeedFontColor+";";
}
if (typeof rssFeedFontSize != "number") {
    rssFeedFontSize = "";
} else {
    rssFeedFontSize = "font-size:"+rssFeedFontSize+";";
}
if (typeof rssFeedTarget != "string") {
    rssFeedTarget = "target=\"_blank\"";;
} else {
    rssFeedTarget = "target=\""+rssFeedTarget+"\"";
}
if (typeof rssFeedFont != "string") {
    rssFeedFont = "";
} else {
    rssFeedFont = "font-family:"+rssFeedFont+";";
}
if (typeof rssFeedShowDate != "boolean") {
    rssFeedShowDate = false;
}

if (typeof rssFeedFontColor == "string" || typeof rssFeedFontSize != "number" || typeof rssFeedFont != "string") {
    style = 'style="'+rssFeedFontColor+rssFeedFontSize+rssFeedFont+'"';
}

if (typeof rssFeedLimit != 'number') {
    rssFeedLimit = 10;
}
if (rssFeedNews.length < rssFeedLimit) {
    rssFeedLimit = rssFeedNews.length;
}

    rssFeedContainer = document.getElementById('news_rss_feeds');
    rssFeedContainer.innerHTML = '';

var rssFeedNewsDate = "";
for (nr = 0; nr < rssFeedLimit; nr++) {
    if (rssFeedShowDate) {
        rssFeedNewsDate = rssFeedNews[nr]['date'];
    }
        rssCode = '<a href="'+rssFeedNews[nr]['link']+'" '+rssFeedTarget+' '+style+'>'+rssFeedNewsDate+' '+rssFeedNews[nr]['title']+'</a><br />';
        rssFeedContainer.innerHTML += rssCode;
    }
}
XMLJSOUTPUT;

        return true;
    }

    /**
     * Add channel title
     *
     * Adds the channel title to the feed.
     *
     * @return boolean
     */
    function _addChannelTitle()
    {
        if (!empty($this->channelTitle)) {
            $this->xmlDocument .= $this->_visualElementLevel()."<title>".$this->channelTitle."</title>\n";
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add channel link
     *
     * Adds the link title to the feed.
     * @return boolean
     */
    function _addChannelLink()
    {
        if (!empty($this->channelLink)) {
            $this->xmlDocument .= $this->_visualElementLevel()."<link>".$this->channelLink."</link>\n";
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add channel description
     *
     * Adds the channel description to the feed.
     * @return boolean
     */
    function _addChannelDescription()
    {
        if (!empty($this->channelDescription)) {
            $this->xmlDocument .= $this->_visualElementLevel()."<description>".$this->channelDescription."</description>\n";
            return true;
        } else {
            return false;
        }
    }

    /**
     * Add optional channel elements
     *
     * Adds all the optional channel elements to the feed.
     * @return boolean
     */
    function _addOptionalChannelElements()
    {
        if (!empty($this->channelLanguage)) $this->xmlDocument .= $this->_visualElementLevel()."<language>".$this->channelLanguage."</language>\n";
        if (!empty($this->channelCopyright)) $this->xmlDocument .= $this->_visualElementLevel()."<copyright>".$this->channelCopyright."</copyright>\n";
        if (!empty($this->channelManagingEditor)) $this->xmlDocument .= $this->_visualElementLevel()."<managingEditor>".$this->channelManagingEditor."</managingEditor>\n";
        if (!empty($this->channelWebMaster)) $this->xmlDocument .= $this->_visualElementLevel()."<webMaster>".$this->channelWebMaster."</webMaster>\n";
        if (!empty($this->channelPubDate)) $this->xmlDocument .= $this->_visualElementLevel()."<pubDate>".$this->channelPubDate."</pubDate>\n";
        if (!empty($this->channelLastBuildDate)) $this->xmlDocument .= $this->_visualElementLevel()."<lastBuildDate>".$this->channelLastBuildDate."</lastBuildDate>\n";
        if (!empty($this->channelCategory)) $this->xmlDocument .= $this->_visualElementLevel()."<category>".$this->channelCategory."</category>\n";
        if (!empty($this->channelGenerator)) $this->xmlDocument .= $this->_visualElementLevel()."<generator>".$this->channelGenerator."</generator>\n";
        if (!empty($this->channelDocs)) $this->xmlDocument .= $this->_visualElementLevel()."<docs>".$this->channelDocs."</docs>\n";
        if (!empty($this->channelCloud)) $this->xmlDocument .= $this->_visualElementLevel()."<cloud>".$this->channelCloud."</cloud>\n";
        if (!empty($this->channelTtl)) $this->xmlDocument .= $this->_visualElementLevel()."<ttl>".$this->channelTtl."</ttl>\n";
        if (!empty($this->channelImageUrl) && !empty($this->channelImageTitle) && !empty($this->channelImageLink)) {
            $this->xmlDocument .= $this->_visualElementLevel()."<image>\n";

            $this->_xmlElementLevel++;
            $this->xmlDocument .= $this->_visualElementLevel()."<url>".$this->channelImageUrl."</url>\n";
            $this->xmlDocument .= $this->_visualElementLevel()."<title>".$this->channelImageTitle."</title>\n";
            $this->xmlDocument .= $this->_visualElementLevel()."<link>".$this->channelImageLink."</link>\n";
            if (!empty($this->channelImageWidth)) $this->xmlDocument .= $this->_visualElementLevel()."<width>".$this->channelImageWidth."</width>\n";
            if (!empty($this->channelImageHeight)) $this->xmlDocument .= $this->_visualElementLevel()."<height>".$this->channelImageHeight."</height>\n";
            if (!empty($this->channelImageDescription)) $this->xmlDocument .= $this->_visualElementLevel()."<description>".$this->channelImageDescription."</description>\n";
            $this->_xmlElementLevel--;
            $this->xmlDocument .= $this->_visualElementLevel()."</image>\n";
        }

        if (!empty($this->channelRating)) $this->xmlDocument .= $this->_visualElementLevel()."<rating>".$this->channelRating."</rating>\n";
        if (!empty($this->channelTextInputTitle) && !empty($this->channelTextInputDescription) && !empty($this->channelTextInputName) && !empty($this->channelTextInputLink)) {
            $this->$this->xmlDocument .= $this->_visualElementLevel()."<textInput>\n";

            $this->_xmlElementLevel++;
            $this->xmlDocument .= $this->_visualElementLevel()."<title>".$this->channelTextInputTitle."</title>\n";
            $this->xmlDocument .= $this->_visualElementLevel()."<description>".$this->channelTextInputDescription."</description>\n";
            $this->xmlDocument .= $this->_visualElementLevel()."<name>".$this->channelTextInputName."</name>\n";
            $this->xmlDocument .= $this->_visualElementLevel()."<link>".$this->channelTextInputLink."</link>\n";

            $this->_xmlElementLevel--;
            $this->$this->xmlDocument .= $this->_visualElementLevel()."</textInput>\n";
        }
        if (!empty($this->channelSkipHours)) $this->xmlDocument .= $this->_visualElementLevel()."<skipHours>".$this->channelSkipHours."</skipHours>\n";
        if (!empty($this->channelSkipDays)) $this->xmlDocument .= $this->_visualElementLevel()."<skipDays>".$this->channelSkipDays."</skipDays>\n";



        return true;
    }

    /**
     * Add custom channel elements
     *
     * Adds all the custom channel elements to the feed.
     * @return boolean
     */
    function _addCustomChannelElements($array = array())
    {
        foreach ($array as $name => $value) {
            if(is_array($value)) {
                $element .= $this->_visualElementLevel()."<".$name.">\n";
                $this->_xmlElementLevel++;
                $element .= $this->_addCustomChannelElements($value);
                $this->_xmlElementLevel--;
                $element .= $this->_visualElementLevel()."</".$name.">\n";
            } else {
                $element .= $this->_visualElementLevel()."<".$name.">".$value."</".$name.">\n";
            }
        }

        return $element;
    }

    /**
     * Parse items
     *
     * Parse the items of the feed and adds them to it.
     */
    function _parseItems()
    {
        foreach ($this->_arrItems as $arrItem) {
            $this->xmlDocument .= $this->_visualElementLevel()."<item>\n";
                $this->_xmlElementLevel++;

                if (!empty($arrItem['title'])) $this->xmlDocument .= $this->_visualElementLevel()."<title>".$arrItem['title']."</title>\n";
                if (!empty($arrItem['link'])) $this->xmlDocument .= $this->_visualElementLevel()."<link>".$arrItem['link']."</link>\n";
                if (!empty($arrItem['description'])) $this->xmlDocument .= $this->_visualElementLevel()."<description>".$arrItem['description']."</description>\n";
                if (!empty($arrItem['author'])) $this->xmlDocument .= $this->_visualElementLevel()."<author>".$arrItem['author']."</author>\n";

                if (!empty($arrItem['arrCategory']['title'])) {
                    $this->xmlDocument .= $this->_visualElementLevel()."<category".(!empty($arrItem['arrCategory']['domain']) ? " domain=\"".$arrItem['arrCategory']['domain']."\"" : "").">".$arrItem['arrCategory']['title']."</category>\n";
                } elseif (is_array($arrItem['arrCategory'])) {
                    foreach ($arrItem['arrCategory'] as $arrCategory) {
                        if (!empty($arrCategory['title'])) {
                            $this->xmlDocument .= $this->_visualElementLevel()."<category".(!empty($arrCategory['domain']) ? " domain=\"".$arrCategory['domain']."\"" : "").">".$arrCategory['title']."</category>\n";
                        }
                    }
                }

                if (!empty($arrItem['comments'])) $this->xmlDocument .= $this->_visualElementLevel()."<comments>".$arrItem['comments']."</comments>\n";

                if (!empty($arrItem['arrEnclosure']['url']) && !empty($arrItem['arrEnclosure']['length']) && !empty($arrItem['arrEnclosure']['type'])) {
                    $this->xmlDocument .= $this->_visualElementLevel()."<enclosure url=\"".$arrItem['arrEnclosure']['url']."\" length=\"".$arrItem['arrEnclosure']['length']."\" type=\"".$arrItem['arrEnclosure']['type']."\" />\n";
                }

                if (!empty($arrItem['arrGuid']['guid'])) $this->xmlDocument .= $this->_visualElementLevel()."<guid".(!empty($arrItem['arrGuid']['isPermaLink']) ? " isPermaLink=\"".(bool)$arrItem['arrGuid']['isPermaLink']."\"" : "").">".$arrItem['arrGuid']['guid']."</guid>\n";

                if (!empty($arrItem['pubDate'])) $this->xmlDocument .= $this->_visualElementLevel()."<pubDate>".date('r', $arrItem['pubDate'])."</pubDate>\n";

                if (!empty($arrItem['source']['url']) && !empty($arrItem['source']['title'])) {
                    $this->xmlDocument .= $this->_visualElementLevel()."<source url=\"".$arrItem['source']['url']."\">".$arrItem['source']['title']."</source>\n";
                }

                if (!empty($arrItem['arrCustom'])) {
                    $this->xmlDocument .= $this->_addCustomChannelElements($arrItem['arrCustom']);
                }

                $this->_xmlElementLevel--;
                $this->xmlDocument .= $this->_visualElementLevel()."</item>\n";
        }
    }

    /**
     * Visual element level
     *
     * Return a number of tabs to visual the locial structure of the RSS feed.
     * @return string
     */
    function _visualElementLevel()
    {
        return sprintf("%'\t".$this->_xmlElementLevel."s", "");
    }

    /** returns the ord() of an UTF8 character.
     * @param c - the character you want to have converted
     * @param index (optional) index into the string
     * @param bytes (optional, out) returns number of bytes of character.
     * Copyright: "kerry at shetline dot com", copied from php.net/ord.
     * Dave Vogt, 25.07.2008
     */
    private function _ordUTF8($c, $index = 0, &$bytes = null) {
        $len   = strlen($c);
        $bytes = 0;

        if ($index >= $len) return false;

        $h = ord($c{$index});

        if ($h <= 0x7F) {
            $bytes = 1;
            return $h;
        }
        else if ($h < 0xC2) return false;
        else if ($h <= 0xDF && $index < $len - 1) {
            $bytes = 2;
            return ($h & 0x1F) <<    6 | (ord($c{$index + 1}) & 0x3F);
        }
        else if ($h <= 0xEF && $index < $len - 2) {
            $bytes = 3;
            return ($h & 0x0F) << 12 | (ord($c{$index + 1}) & 0x3F) << 6
                                     | (ord($c{$index + 2}) & 0x3F);
        }
        else if ($h <= 0xF4 && $index < $len - 3) {
            $bytes = 4;
            return ($h & 0x0F) << 18 | (ord($c{$index + 1}) & 0x3F) << 12
                                     | (ord($c{$index + 2}) & 0x3F) << 6
                                     | (ord($c{$index + 3}) & 0x3F);
        }

        else return false;
    }

    /**
     * Helper function for converting an special character into
     * it's proper \uXXXX notation.
     */
    private function _uni_escape($chr) {
        $chr = $chr[0];
        $bytecount = 1;
        $codepoint = $this->_ordUTF8($chr, 0, $bytecount);

        // 1-byte UTF8 character means ASCII aequivalent. no need
        // to escape!
        if ($bytecount == 1) return $chr;

        $hex       = strtoupper(dechex($codepoint));

        $len = strlen($hex);
        // output needs to be zero-padded (four positions)
        $zeroes    = 4 - $len;
        for (; $zeroes > 0; $zeroes--) {
            $hex = "0$hex";
        }
        return "\\u$hex";
    }

    /**
     * Takes a string and replaces all umlauts and special chars with
     * their unicode escape sequence. This is needed so UTF8 Javascript
     * news gets displayed correctly in Latin1 pages.
     */
    private function _js_umlauts($str) {
        return preg_replace_callback('/(.)/u', array($this, '_uni_escape'), $str);
    }
}
?>
