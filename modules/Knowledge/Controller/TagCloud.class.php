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
 * Contains the class for the tag cloud
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */

namespace Cx\Modules\Knowledge\Controller;

/**
 * Provide all the necessary database operations for the tag cloud
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */
class TagCloud
{
    /**
     * The font size of the highest (biggest) tag
     *
     * @var int
     */
    private $highestFont;

    /**
     * The font size of the lowest (smallest) tag in the cloud
     *
     * @var unknown_type
     */
    private $lowestFont;

    /**
     * The tags to display
     *
     * @var array
     * @see setTags
     */
    private $tags;

    /**
     * TODO: what's this for?
     *
     * @var int
     */
    private $lowestTagVal;

    /**
     * TODO: what's this for?
     *
     * @var int
     */
    private $highestTagVal;

    /**
     * URL format
     *
     * @var string
     */
    private $urlFormat;

    /**
     * Constructor
     *
     * Construct the cloud object. For the tag array syntax see the
     * setTags function.
     * @see setTags
     * @param int $highestFont
     * @param int $lowestFont
     * @param array $tags
     * @param int $lowestTagVal
     * @param int $highestTagVal
     */
    public function __construct($highestFont=20, $lowestFont=10, $tags=array(), $lowestTagVal=10, $highestTagVal=20, $urlFormat="%id")
    {
        $this->highestFont = $highestFont;
        $this->lowestFont = $lowestFont;
        $this->tags = $tags;
        $this->lowestTagVal = $lowestTagVal;
        $this->highestTagVal = $highestTagVal;
        $this->urlFormat = $urlFormat;
    }

    /**
     * Return the cloud
     *
     * @return string
     */
    public function getCloud()
    {
        $fontDiff = $this->highestFont - $this->lowestFont;
        $tagDiff = $this->highestTagVal - $this->lowestTagVal;
        $step = $tagDiff / $fontDiff;
        if ($step == 0) {
            //prevent from division by zero
            $step = 1;
        }

        $cloud = "<div class=\"cloud clearfix\"><ul class=\"cloud\">";
        foreach ($this->tags as $tagId => $tag) {
            $size = $this->lowestFont + round($tag['popularity'] / $step);
            $uri = "<a href=\"".$this->formatUrl($tag['id'])."\">".$tag['name']."</a>";

            $cloud .= "<li style=\"font-size: ".$size."px;\">".$uri."</li>";
        }
        $cloud .= "</ul></div>";

        return $cloud;
    }

    /**
     * Set the font options
     *
     * @param int $highest
     * @param int $lowest
     */
    public function setFont($highest, $lowest)
    {
        $this->highestFont = intval($highest);
        $this->lowestFont = intval($lowest);
    }

    /**
     * Set the tags
     *
     * Set the tags with this format:
     * array(
     *  index => array(
     *      "id"    => 0,
     *      "name"  => "Tagname",
     *      "popularity" => 15
     *      )
     * )
     * @param array $tags
     * @throws Exception
     */
    public function setTags($tags)
    {
        if (gettype($tags) != "array") {
            throw new Exception("not an array given in TagCloud::setTags(array);");
        }

        $this->tags = $tags;
    }

    /**
     * Set the tag values
     *
     * @param int $highest
     * @param int $lowest
     */
    public function setTagVals($highest, $lowest)
    {
        $this->highestTagVal = intval($highest);
        $this->lowestTagVal = intval($lowest);
    }

    /**
     * Set the url format
     *
     * @param string $format
     */
    public function setUrlFormat($format)
    {
        $this->urlFormat = $format;
    }

    /**
     * Format the url
     *
     * Replace the %id placeholder with the actual id
     * @param int $id
     * @return string
     */
    private function formatUrl($id)
    {
        return preg_replace("/%id/i", $id, $this->urlFormat);
    }
}
