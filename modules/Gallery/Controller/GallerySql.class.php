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
 * SQL layer of the gallery module
 *
 * Contains methods to read and save date from and to the
 * database.
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @author Stefan Heinemann
 * @package cloudrexx
 * @subpackage module_gallery
 */
namespace Cx\Modules\Gallery\Controller;
/**
 * SQL layer of the gallery module
 *
 * Contains methods to read and save date from and to the
 * database.
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @author Stefan Heinemann
 * @package cloudrexx
 * @subpackage module_gallery
 */
class GallerySql
{
    public function __construct()
    {

    }


    /**
     * Insert a new Category
     *
     * @param int $pid
     * @param int $status
     * @param int $comment
     * @param int $voting
     * @param int $frontendProtected
     * @param int $access_id
     * @return int
     */
    public function insertNewCategory($pid, $status, $comment, $voting, $frontendProtected, $backendProtected, $frontend_access_id, $backend_access_id)
    {
        global $objDatabase;

        $query = "  INSERT
                    INTO     ".DBPREFIX."module_gallery_categories
                    SET     pid = ".intval($pid).",
                            status = '".intval($status)."',
                            comment = '".intval($comment)."',
                            voting = '".intval($voting)."',
                            frontendProtected = ".intval($frontendProtected).",
                            backendProtected = ".intval($backendProtected).",
                            frontend_access_id = ".intval($frontend_access_id).",
                            backend_access_id = ".intval($backend_access_id);

        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("Inserting a new category failed");
        }
        return $objDatabase->insert_id();
    }

    /**
     * Delete all Access ids from the database
     *
     * @param int $access_id
     */
    public function deleteAccessIds($access_id)
    {
        global $objDatabase;
        $query = "DELETE FROM ".DBPREFIX."access_group_dynamic_ids WHERE access_id = ".$access_id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("Deletion of access ids failed");
        }
    }

    /**
     * Update the category tables
     *
     * @param int $id
     * @param int $pid
     * @param int $status
     * @param int $comment
     * @param int $voting
     * @param int $frontendProtected
     */
    public function updateCategory($id, $pid, $status, $comment, $voting, $frontendProtected, $backendProtected, $frontend_access_id, $backend_access_id)
    {
        global $objDatabase;

        $id = intval($id);
        $pid = intval($pid);
        $status = intval($status);
        $comment = intval($comment);
        $voting = intval($voting);
        $frontendProtected = intval($frontendProtected);
        $backendProtected = intval($backendProtected);

        $query =    "  UPDATE  ".DBPREFIX."module_gallery_categories
                        SET     pid=".$pid.",
                               status='".$status."',
                               comment='".$comment."',
                               voting='".$voting."',
                               frontendProtected=".$frontendProtected.",
                               backendProtected=".$backendProtected.",
                                frontend_access_id = ".intval($frontend_access_id).",
                                backend_access_id = ".intval($backend_access_id)."
                        WHERE   id=".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("Category update failed");
        }

        if ($pid != 0) {
            // This prevents the categories from being on a third level
            // for doing so, it puts the lower categories just to the top level
            $query = "  UPDATE     ".DBPREFIX."module_gallery_categories
                        SET        pid=0,
                                   sorting=99,
                                   status='0',
                                   comment='0',
                                   voting='0'
                       WHERE     pid=".$id;
            if ($objDatabase->Execute($query) === false) {
                throw new DatabaseError("Child category adjustment failed");
            }
        }
    }



    /**
     * @return array
     * @param string groupType
     * @desc gets all frontend groups as an array
     */
    public function getAllGroups($groupType="frontend")
    {
        $objGroup = \FWUser::getFWUserObject()->objGroup->getGroups($filter = array('type' => $groupType));

        $arrGroups=array();

        while (!$objGroup->EOF) {
            $arrGroups[$objGroup->getId()] = $objGroup->getName();
            $objGroup->next();
        }

        return $arrGroups;
    }

    /**
     * Select all group ids that are assigned to a category
     *
     * @param int $intCategoryId
     * @return array
     */
    public function getAccessGroups($type="frontend", $access_id=false, $intCategoryId=false)
    {
        global $objDatabase;

        if ($access_id === false) {
            list($access_id) = $this->getPrivileges($intCategoryId, $type);
        }

        $query = "  SELECT group_id
                    FROM `".DBPREFIX."access_group_dynamic_ids`
                    WHERE access_id = ".$access_id;

        $groups = array();
        $objRs = $objDatabase->Execute($query);
        if ($objRs !== false) {
            while (!$objRs->EOF) {
                $groups[] = $objRs->fields['group_id'];
                $objRs->MoveNext();
            }
        }
        return $groups;
    }

    /**
     * Return the access id and the protected info of a category
     *
     * @param int $id
     * @param flag $type
     * @return array (access_id, protected)
     */
    public function getPrivileges($id, $type="frontend")
    {
        global $objDatabase;

        // prevent from misusing
        if (!($type == "frontend" || $type == "backend")) {
            die("_getAccessId(): ".$type." unknown");
        }

        $id = intval($id);
        $query = "  SELECT  ".$type."_access_id as access_id,
                            ".$type."Protected as protected
                    FROM ".DBPREFIX."module_gallery_categories
                    WHERE id = ".$id;
        $objRs = $objDatabase->Execute($query);
        if ($objRs !== false) {
            return array($objRs->fields['access_id'], $objRs->fields['protected']);
        } else {
            throw new DatabaseError("Error getting access id");
        }
    }

    /**
     * Insert an access id
     *
     * @param int $access_id
     * @param int $group_id group id
     * @return boolean
     */
    public function insertAccessId($access_id, $group_id)
    {
        global $objDatabase;

        $access_id = intval($access_id);
        $group_id = intval($group_id);
        $query = "  INSERT INTO ".DBPREFIX."access_group_dynamic_ids
                    (`access_id`, `group_id`)
                    VALUES
                    (".$access_id.", ".$group_id.")";
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("Error inserting access id");
        }
    }

    /**
     * Get an array with the categories
     *
     * @param int $langId
     * @return array
     */
    public function getCategoriesArray($langId, $pid=-1)
    {
        global $objDatabase;

        $langId = intval($langId);
        // if a pid is given, only get the categories with that pid
        $sqlPid  = ($pid > -1) ? "AND pid = ".intval($pid) : "";
        $query = "  SELECT  cat.id AS id,
                            lang.value AS value,
                            cat.backendProtected backendProtected,
                            cat.backend_access_id as backend_access_id
                    FROM ".DBPREFIX."module_gallery_categories AS cat
                    LEFT JOIN ".DBPREFIX."module_gallery_language AS lang ON cat.id = lang.gallery_id
                    WHERE lang.name = \"name\"
                    AND lang.lang_id = ".intval($langId)."
                    ".$sqlPid."
                    ORDER BY sorting ASC ";
        $objRs = $objDatabase->Execute($query);
        if ($objRs === false) {
            throw new DatabaseError("Error getting the categories");
        } else {
            $retArr = array();
            while (!$objRs->EOF) {
                $retArr[$objRs->fields['id']] = array(
                    "id"                => $objRs->fields['id'],
                    "name"              => $objRs->fields['value'],
                    "backendProtected"  => $objRs->fields['backendProtected'],
                    "backend_access_id" => $objRs->fields['backend_access_id']
                );
                $objRs->MoveNext();
            }
        }
        return $retArr;
    }

    /**
     * Get the category id of a picture
     *
     * @param int $id
     * @return int
     */
    public function getPictureCategory($id)
    {
        global $objDatabase;

        $id = intval($id);

        $query = "  SELECT catid
                    FROM ".DBPREFIX."module_gallery_pictures
                    WHERE id = ".$id;
        $objRs = $objDatabase->Execute($query);
        if ($objRs === false) {
            throw new DatabaseError("Error getting the category id of the picture");
        } else {
            return intval($objRs->fields['catid']);
        }
    }
}

if (!class_exists("DatabaseError", false)) {

/**
 * Database Error
 * @author Cloudrexx Development Team <info@cloudrexx.com>
 * @author Stefan Heinemann
 * @package cloudrexx
 * @subpackage module_gallery
 */
class DatabaseError extends \Exception
{
    public function __construct($message)
    {
        parent::__construct($message);
    }

    public function __toString()
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
}
}
