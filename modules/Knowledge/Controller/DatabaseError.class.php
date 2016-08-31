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
 * Contains database error class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */

namespace Cx\Modules\Knowledge\Controller;

/**
 * Database Error
 *
 * This class is thrown as a exception. Contains the
 * adodb error message and some kind of stacktrace that can be
 * return either plainly or formatted for the red alertbox.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */
class DatabaseError extends \Exception
{
    /**
     * Construct the Exception class
     *
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }

    /**
     * Return a formated error message
     *
     * This message will be used within the red box
     * @global $objDatabase
     * @return string
     */
    public function formatted()
    {
        global $objDatabase;

        $txt_details = "Details";

        return "<a style=\"margin-left: 1em;\" href=\"javascript:void(0);\" onclick=\"showErrDetails(this);\">$txt_details&gt;&gt;</a>
        <div style=\"display:none;\" id=\"errDetails\">
        ".$this->getMessage()."<br />
        ".$objDatabase->ErrorMsg()."<br />
        ".$this->getTraceAsString()."
        </div>
        <script type=\"text/javascript\">
            /* <![CDATA[ */
                var showErrDetails = function(obj)
                {
                    var childs = obj.childNodes;
                    for (var i = 0; i < childs.length; ++i) {
                        obj.removeChild(childs[i]);
                    }
                    if ($('errDetails').visible()) {
                        $('errDetails').style.display = \"none\";
                        obj.appendChild(document.createTextNode(\"$txt_details >>\"));
                    } else {
                        $('errDetails').style.display = \"block\";
                        obj.appendChild(document.createTextNode(\"$txt_details <<\"));
                    }
                }
            /* ]]> */
        </script>";
    }

    /**
     * Return a plain error message
     *
     * Just return some error text. This is for example
     * for ajax requests
     * @global $objDatabase
     * @return string
     */
    public function plain()
    {
        global $objDatabase;

        return  $this->getMessage()."\n".
                strip_tags($objDatabase->ErrorMsg())."\n".
                $this->getTraceAsString();
    }
}
