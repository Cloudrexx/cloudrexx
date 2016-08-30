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
 * Contains the class for category operations
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */

namespace Cx\Modules\Knowledge\Controller;

/**
 * Category abstract layer for database operations
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */
class KnowledgeCategory
{
    /**
     * All available categories and their information
     *
     * @var array
     */
    public $categories = null;

    /**
     * The categories sorted as a tree
     *
     * @var array
     */
    public $categoryTree = null;

    /**
     * Get categories from database
     *
     * Read categories out of the database but only if
     * they are not already read. Also create the category tree
     * to provide an array for recursive parsing.
     * If the first argument is true, override the existing array.
     * @param bool $override
     * @global $objDatabase
     * @throws DatabaseError
     * @return mixed
     */
    public function readCategories($override = false, $id = 0)
    {
        if ($override === false && isset($this->categories)) {
            // the cateogories are already read out and override is not given
            return;
        }

        global $objDatabase;

        $query = "  SELECT  categories.id as id,
                            categories.active as active,
                            categories.parent as parent,
                            content.name as name,
                            content.lang as lang
                    FROM ".DBPREFIX."module_knowledge_categories AS categories
                    INNER JOIN ".DBPREFIX."module_knowledge_categories_content AS content
                    ON categories.id = content.category";

        // if only one category should be read add a where to the query
        if ($id > 0) {
            $id = intval($id);
            $query .= " WHERE categories.id = ".$id;
        }

        $query .= " ORDER BY sort ASC";

        $objRs = $objDatabase->Execute($query);
        if ($objRs === false) {
            throw new DatabaseError("read categories failed");
        }

        $categories = array();
        while (!$objRs->EOF) {
            $curId = $objRs->fields['id'];
            if (isset($categories[$curId])) {
                $categories[$curId]['content'][$objRs->fields['lang']]['name'] = $objRs->fields['name'];
            } else {
                $categories[$curId] = array(
                    'id'            => intval($objRs->fields['id']),
                    'active'        => intval($objRs->fields['active']),
                    'parent'        => intval($objRs->fields['parent']),
                    'content'       => array(
                        $objRs->fields['lang'] => array(
                            'name'      => $objRs->fields['name']
                        )
                    )
                );
            }
            $objRs->MoveNext();
        }

        $this->categories = $categories;

        if ($id == 0) {
            $this->categoryTree = $this->buildCatTree($categories);
        }
    }

    /**
     * Add a new category to the database
     *
     * @param int $active
     * @param int $parent
     * @global $_ARRAYLANG
     * @global $objDatabase
     * @throws DatabaseError
     * @return int id
     */
    public function insertCategory($active, $parent)
    {
        global $objDatabase;

        $active = intval($active);
        $parent = intval($parent);

        $query = " SELECT MAX(sort) as sort FROM ".DBPREFIX."module_knowledge_categories";
        $rs = $objDatabase->Execute($query);
        if ($rs === false) {
            throw new DatabaseError("getting the maximal sort failed");
        }

        $query = " INSERT INTO ".DBPREFIX."module_knowledge_categories
                       (active, parent, sort)
                   VALUES
                       (".$active.", ".$parent.", ".($rs->fields['sort']+1).")";
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("insert category failed");
        }

        // the new id of the category
        $id = $objDatabase->Insert_Id();
        $this->insertCategoryContent($id);
        return $id;
    }

    /**
     * Update a category
     *
     * @param int $id
     * @param int $active
     * @param int $parent
     * @global $objDatabase
     * @throws DatabaseError
     */
    public function updateCategory($id, $active, $parent)
    {
        global $objDatabase;

        $active = intval($active);
        $parent = intval($parent);

        $query = "  UPDATE ".DBPREFIX."module_knowledge_categories
                    SET
                        `active` = ".$active.",
                        `parent` = ".$parent."
                    WHERE id = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("updating category failed");
        }

        $this->deleteCategoryData($id);
        $this->insertCategoryContent($id);
    }

    /**
     * Add content data for a category
     *
     * Add the content data for a category that later will be inserted
     * @param int $lang
     * @param string $name
     */
    public function addContent($lang, $name)
    {
        $this->insertContent[] = array(
            'lang' => intval($lang),
            'name' => contrexx_addslashes($name)
        );
    }

    /**
     * Delete a category
     *
     * Don't delete the category itself, just go recursively through the
     * categories and call the delete function on them.
     * @param int $id
     * @see Category:deleteOneCategory()
     * @return array
     */
    public function deleteCategory($id)
    {
        $deleted = array();
        // are there subcategories?
        $this->readCategories();
        foreach ($this->categories as $key => $value) {
            if ($value['parent'] == $id) {
                $deleted = array_merge($this->deleteCategory($key), $deleted);
            }
        }
        $this->deleteOneCategory($id);
        $this->deleteCategoryData($id);
        $deleted[] = $id;
        return $deleted;
    }

    /**
     * Activate a category
     *
     * @param int $id
     * @global $objDatabase
     * @throws DatabaseError
     */
    public function activate($id)
    {
        global $objDatabase;

        $id = intval($id);
        $query = "  UPDATE ".DBPREFIX."module_knowledge_categories
                    SET active = 1
                    WHERE id = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to activate the category");
        }
    }

    /**
     * Deactivate a category
     *
     * @param int $id
     * @global $objDatabase
     * @throws DatabaseError
     */
    public function deactivate($id)
    {
        global $objDatabase;

        $id = intval($id);
        $query = "  UPDATE ".DBPREFIX."module_knowledge_categories
                    SET active = 0
                    WHERE id = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to deactivate the category");
        }
    }

    /**
     * Return just one category
     *
     * @param int $id
     * @return array
     */
    public function getOneCategory($id)
    {
        $this->readCategories(true, $id);
        return array_pop($this->categories);
    }

    /**
     * Get all categories of a parent
     *
     * @param int $parent
     * @return array
     */
    public function getCategoriesByParent($parent)
    {
        $this->readCategories();

        $retCat = array();
        foreach ($this->categories as $category) {
            if ($category['parent'] == $parent) {
                $retCat[] = $category;
            }
        }

        return $retCat;
    }

    /**
     * Set the sort position of a category
     *
     * @param int $id
     * @param int $position
     * @global $objDatabase
     * @throws DatabaseError
     */
    public function setSort($id, $position)
    {
        global $objDatabase;

        $id = intval($id);
        $position = intval($position);

        $query = "  UPDATE ".DBPREFIX."module_knowledge_categories
                    SET sort = ".$position."
                    WHERE id = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("error updating the order");
        }
    }

    /**
     * Delete one category
     *
     * Delete only one certain category and its content. If the
     * second argument is true, also delete all according messages.
     * @param int $id
     * @see Category::deleteCategory()
     * @throws DatabaseError
     */
    private function deleteOneCategory($id)
    {
        global $objDatabase;

        $id = intval($id);
        $query = "  DELETE FROM ".DBPREFIX."module_knowledge_categories
                    WHERE id = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to delete category content");
        }
    }

    /**
     * Delete the category data
     *
     * @param int $id
     * @global $objDatabase
     * @throws DatabaseError
     */
    private function deleteCategoryData($id)
    {
        global $objDatabase;

        $query = "  DELETE FROM ".DBPREFIX."module_knowledge_categories_content
                    WHERE category = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to delete a category");
        }
    }

    /**
     * Build a category tree of ids
     *
     * Build a tree with all categories recursively. Only
     * the ids are affected, for the rest of the data use
     * category::categories.
     * @see category::readCategories()
     * @see category::categories
     * @see category::categoryTree
     * @param array $array
     * @param int $id
     * @return array
     */
    private function buildCatTree($array, $id=0)
    {
        $retarr = array();
        foreach ($array as $key => $value) {
            if ($value['parent'] == $id) {
                $retarr[$key] = $this->buildCatTree($array, $key);
                unset($array[$key]);
            }
        }
        return $retarr;
    }

    /**
     * Insert category content
     *
     * @param int $id
     * @global $objDatabase
     * @throws DatabaseError
     */
    private function insertCategoryContent($id)
    {
        global $objDatabase;

        foreach ($this->insertContent as $values) {
            $name = $values['name'];
            $lang = $values['lang'];
            $query = "  INSERT INTO ".DBPREFIX."module_knowledge_categories_content
                            (`category`, `name`, `lang`)
                        VALUES
                            (".$id.", '".$name."', ".$lang.")";
            if ($objDatabase->Execute($query) === false) {
                throw new DatabaseError("inserting category content failed");
            }
        }
    }
}
