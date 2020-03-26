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
 * Class RewriteRule
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 */

namespace Cx\Core\Routing\Model\Entity;

/**
 * Class RewriteRule
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  core_routing
 */
class RewriteRule extends \Cx\Model\Base\EntityBase
{
    const REDIRECTION_TYPE_INTERN = 'intern';
    const REDIRECTION_TYPE_301 = 301;
    const REDIRECTION_TYPE_302 = 302;
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * Regex
     *
     * @var \Cx\Lib\Helpers\RegularExpression $regularExpression
     */
    protected $regularExpression;

    /**
     * Order of the Rewrite rule
     *
     * @var integer
     */
    protected $orderNo;

    /**
     * Rewrite Status Code
     *
     * @var integer
     */
    protected $rewriteStatusCode;

    /**
     * @var boolean $continueOnMatch
     */
    protected $continueOnMatch;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get Regular expression
     *
     * @return \Cx\Lib\Helpers\RegularExpression
     */
    public function getRegularExpression()
    {
        if (!($this->regularExpression instanceof \Cx\Lib\Helpers\RegularExpression)) {
            $this->regularExpression = new \Cx\Lib\Helpers\RegularExpression($this->regularExpression);
        }

        return $this->regularExpression;
    }

    /**
     * Get the order no
     *
     * @return integer
     */
    function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * Get Rewrite Status Code
     *
     * @return integer
     */
    function getRewriteStatusCode()
    {
        return $this->rewriteStatusCode;
    }

    /**
     * @return boolean
     */
    public function getContinueOnMatch()
    {
        return $this->continueOnMatch;
    }

    /**
     * Set regular expression
     *
     * @param mixed $regularExpression \Cx\Lib\Helpers\RegularExpression or string
     */
    public function setRegularExpression($regularExpression)
    {
        if (!($regularExpression instanceof \Cx\Lib\Helpers\RegularExpression)) {
            $regularExpression = new \Cx\Lib\Helpers\RegularExpression($regularExpression);
        }

        $this->regularExpression = $regularExpression;
    }

    /**
     * Set the order no
     *
     * @param integer $orderNo
     */
    function setOrderNo($orderNo)
    {
        $this->orderNo = $orderNo;
    }

    /**
     * Set the rewrite status code
     *
     * @param integer $rewriteStatusCode
     */
    function setRewriteStatusCode($rewriteStatusCode)
    {
        $this->rewriteStatusCode = $rewriteStatusCode;
    }

    /**
     * Set continue on match
     *
     * @param boolean $continueOnMatch
     */
    public function setContinueOnMatch($continueOnMatch)
    {
        $this->continueOnMatch = $continueOnMatch;
    }

    public function matches(\Cx\Core\Routing\Url $url)
    {
        return $this->getRegularExpression()->match($url->toString());
    }

    /**
     * Resolve
     *
     * @throws \Cx\Lib\Net\Model\Entity\UrlException In case the rewritten url
     *                                               is invalid
     */
    public function resolve(\Cx\Core\Routing\Url $url, &$continue)
    {
        if (!$this->matches($url)) {
            return $url;
        }

        // apply regex to resolved url
        $rewrite = $this->getRegularExpression()->replace($url->toString());

        // verify that the rewritten url is valid
        // note: this would throw an exception if $rewrite is not a valid url
        $rewrittenUrl = new \Cx\Lib\Net\Model\Entity\Url($rewrite);

        // convert url into legacy url format for backwards compatability
        $newUrl = \Cx\Core\Routing\Url::fromMagic(
            $rewrittenUrl
        );

        // set continue state only if regex application was successful
        $continue = $this->getContinueOnMatch();

        \DBG::log('Redirecting to ' . $newUrl->toString());
        return $newUrl;
    }
}
