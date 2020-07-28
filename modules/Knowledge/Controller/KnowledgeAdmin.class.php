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
 * Knowledge backend stuff
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */

namespace Cx\Modules\Knowledge\Controller;

define("ACCESS_ID_KNOWLEDGE", 129);
define("ACCESS_ID_OVERVIEW", 130);
define("ACCESS_ID_EDIT_ARTICLES", 131);
define("ACCESS_ID_CATEGORIES", 132);
define("ACCESS_ID_EDIT_CATEGORIES", 133);
define("ACCESS_ID_SETTINGS", 134);

/**
 * All the backend stuff of the Knowledgemodul
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */
class KnowledgeAdmin extends KnowledgeLibrary
{
    /**
     * Page Title
     *
     * @var string
     */
    private $pageTitle    = '';

    /**
     * Error message
     *
     * @var string
     */
    private $errorMessage = "";

    /**
     * Ok message
     *
     * @var string
     */
    private $okMessage     = '';

    /**
     * Container for the adodb Object
     *
     * @var object
     */
    private $tpl;

    /**
     * The id of the current language
     *
     * @var int
     */
    private $languageId = 1;

    private $act = '';

    /**
    * Constructor Create the module-menu and an internal template-object
    *
    * @global $objInit
    * @global $objTemplate
    * @global $_CORELANG
    */
    public function __construct()
    {
        global $objInit, $objTemplate, $_ARRAYLANG;

        \JS::activate('prototype');
        \JS::activate('scriptaculous');

        \Permission::checkAccess(ACCESS_ID_KNOWLEDGE, 'static');

        parent::__construct();
        $this->tpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH.'/Knowledge'.MODULE_INDEX.'/View/Template/Backend');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->tpl);
        $this->tpl->setErrorHandling(PEAR_ERROR_DIE);

         $this->languageId = $objInit->userFrontendLangId;
    }

    /**
     * Parse the navigation
     */
    private function setNavigation()
    {
        global $objTemplate, $_ARRAYLANG;

        $objTemplate->setVariable(
            'CONTENT_NAVIGATION',
            '<a href="index.php?cmd=Knowledge' . MODULE_INDEX . '&amp;act=articles" class="' . ($this->act == 'articles' ? 'active' : '') . '">' . $_ARRAYLANG['TXT_KNOWLEDGE_ARTICLES'] . '</a>
             <a href="index.php?cmd=Knowledge' . MODULE_INDEX . '&amp;act=categories" class="' . ($this->act == 'categories' ? 'active' : '') . '">' . $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORIES'] . '</a>
             <a href="index.php?cmd=Knowledge' . MODULE_INDEX . '&amp;act=settings" class="' . ($this->act == 'settings' ? 'active' : '') . '">' . $_ARRAYLANG['TXT_KNOWLEDGE_SETTINGS'] . '</a>'
        );
    }

    /**
     * Return the page depending on the $_GET-params
     *
     * @global $objPerm
     * @global $objTemplate
     * @global $_ARRAYLANG
     */
    function getPage()
    {
        global $objPerm, $objTemplate, $_ARRAYLANG;

        $_GET['tpl'] = !empty($_GET['tpl']) ? contrexx_input2raw($_GET['tpl']) : '';
        $_GET['act'] = !empty($_GET['act']) ? contrexx_input2raw($_GET['act']) : '';

        switch ($_GET['act']) {
            // The categories
            case 'categories':
                switch ($_GET['tpl']) {
                    case 'add':
                        \Permission::checkAccess(ACCESS_ID_EDIT_CATEGORIES, 'static');
                        $content = $this->editCategory(true);
                        $active = "add";
                        break;
                    case 'edit':
                        \Permission::checkAccess(ACCESS_ID_EDIT_CATEGORIES, 'static');
                        $content = $this->editCategory();
                        $active = "";
                        break;
                    case 'update':
                        \Permission::checkAccess(ACCESS_ID_EDIT_CATEGORIES, 'static');
                        $id = $this->updateCategory();
                        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Knowledge' . MODULE_INDEX . '&act=categories&tpl=overview&highlight=' . $id);
                        break;
                    case 'insert':
                        \Permission::checkAccess(ACCESS_ID_EDIT_CATEGORIES, 'static');
                        $id = $this->insertCategory();
                        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Knowledge' . MODULE_INDEX . '&act=categories&tpl=overview&highlight=' . $id);
                        break;
                    case 'delete':
                        \Permission::checkAccess(ACCESS_ID_EDIT_CATEGORIES, 'static');
                        $this->deleteCategory();
                        break;
                    case 'switchState':
                        $this->checkAjaxAccess(ACCESS_ID_EDIT_CATEGORIES);
                        $this->switchCategoryState();
                        break;
                    case 'sort':
                        $this->checkAjaxAccess(ACCESS_ID_EDIT_CATEGORIES);
                        $this->sortCategory();
                        break;
                    case 'overview':
                    default:
                       \Permission::checkAccess(ACCESS_ID_CATEGORIES, 'static');
                       $content = $this->categoriesOverview();
                       $active = "overview";
                       break;
                }
                $this->categories($content, $active);
                break;

            // The articles
            case 'articles':
                switch ($_GET['tpl']) {
                    case 'add':
                        \Permission::checkAccess(ACCESS_ID_EDIT_ARTICLES, 'static');
                        $content = $this->editArticle(true);
                        $active = "add";
                        break;
                    case 'edit':
                        \Permission::checkAccess(ACCESS_ID_EDIT_ARTICLES, 'static');
                        $content = $this->editArticle();
                        $active = "";
                        break;
                    case 'insert':
                        \Permission::checkAccess(ACCESS_ID_EDIT_ARTICLES, 'static');
                        $id = $this->insertArticle();
                        $content = $this->articleOverview();
                        $active = "overview";
                        break;
                    case 'update':
                        \Permission::checkAccess(ACCESS_ID_EDIT_ARTICLES, 'static');
                        $id = $this->updateArticle();
                        $content = $this->articleOverview();
                        \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Knowledge' . MODULE_INDEX . '&act=articles&tpl=edit&id=' . $id . '&updated=true');
                        break;
                    case 'getArticles':
                        \Permission::checkAccess(ACCESS_ID_OVERVIEW, 'static');
                        $this->getArticles();
                        break;
                    case 'sort':
                        $this->checkAjaxAccess(ACCESS_ID_EDIT_ARTICLES);
                        $this->sortArticles();
                        break;
                    case 'switchState':
                        $this->checkAjaxAccess(ACCESS_ID_EDIT_ARTICLES);
                        $this->switchArticleState();
                        break;
                    case 'getTags':
                        \Permission::checkAccess(ACCESS_ID_OVERVIEW, 'static');
                        $this->getTags();
                        break;
                    case 'delete':
                        $this->checkAjaxAccess(ACCESS_ID_EDIT_ARTICLES);
                        $this->deleteArticle();
                        break;
                    case 'overview':
                    default:
                        \Permission::checkAccess(ACCESS_ID_OVERVIEW, 'static');
                        $content = $this->articleOverview();
                        $active = "overview";
                        break;
                }
                $this->articles($content, $active);
                break;
            case 'settings':
                \Permission::checkAccess(ACCESS_ID_SETTINGS, 'static');
                switch ($_GET['tpl']) {
                    case 'tidyTags':
                        $this->tidyTags();
                        break;
                    case 'resetVotes':
                        $this->resetVotes();
                        break;
                    case 'placeholders':
                        $content = $this->settingsPlaceholders();
                        $active = "placeholders";
                        break;
                    case 'update':
                        $this->updateSettings();
                        try {
                            $this->settings->readSettings();
                        } catch (DatabaseError $e) {
                            $this->errorMessage = $_ARRAYLANG['TXT_KNOWLEDGE_ERROR_OVERVIEW'];
                            $this->errorMessage .= $e->formatted();
                        }
                        $content = $this->settingsOverview();
                        $active = "settings";
                        break;
                    case 'show':
                    default:
                        $content = $this->settingsOverview();
                        $active = "settings";
                        break;
                }
                $this->settings($content, $active);
                break;
            default:
                \Cx\Core\Csrf\Controller\Csrf::redirect('index.php?cmd=Knowledge' . MODULE_INDEX . '&act=articles');
        }

        $objTemplate->setVariable(array(
            'CONTENT_TITLE'                => $this->pageTitle,
            'CONTENT_OK_MESSAGE'           => $this->okMessage,
            'CONTENT_STATUS_MESSAGE'       => $this->errorMessage,
            'ADMIN_CONTENT'                => $this->tpl->get()
        ));

        $this->act = $_REQUEST['act'];
        $this->setNavigation();
    }

    /**
     * Check acces for ajax request
     *
     * When the page is ajax requested the response should be
     * different so that the page can display a message that the user
     * hasn't got permissions to do what he tried.
     * Hence, this function returns a JSON object containing a status
     * code (0 for fail) and an error message.
     * @param int $id
     * @global $_ARRAYLANG
     */
    private function checkAjaxAccess($id)
    {
        global $_ARRAYLANG;

        if (!\Permission::checkAccess($id, 'static', true)) {
            $this->sendAjaxError($_ARRAYLANG['TXT_KNOWLEDGE_ACCESS_DENIED']);
        }
    }

    /**
     * Send ajax error message
     *
     * Sends an json object for ajax request to communcate that there has been
     * an error.
     * @param string $message
     */
    private function sendAjaxError($message)
    {
        die("{'status' : 0, 'message' : '".$message."'}");
    }

    /**
     * Main function for categories
     *
     * Shows the bar on the top with the category section links
     * @param int $content
     * @param int $active
     * @global $_ARRAYLANG
     */
    private function categories($content, $active)
    {
        global $_ARRAYLANG;

        $this->tpl->loadTemplateFile('module_knowledge_categories.html', true, true);
        $this->tpl->setGlobalVariable("MODULE_INDEX", MODULE_INDEX);

        $this->tpl->setVariable(array(
            "CATEGORIES_FILE"       => $content,
            "ACTIVE_".strtoupper($active) => 'class="active"'
        ));

        $this->tpl->setVariable(array(
            "TXT_OVERVIEW"          => $_ARRAYLANG['TXT_OVERVIEW'],
            "TXT_ADD"               => $_ARRAYLANG['TXT_ADD']
        ));
    }



    /**
     * Inserts a new category
     *
     * @global $_ARRAYLANG
     * @global $objDatabase
     * @return int The id of the inserted category
     */
    private function insertCategory()
    {
        global $_ARRAYLANG;

        $parentCategory = intval($_POST['parentCategory']);
        $state = intval($_POST['state']);

        foreach ($_POST as $key => $var) {
            if (strstr($key, "name_")) {
                $lang = substr($key, 5);
                $this->categories->addContent($lang, $var);
            }
        }

        return $this->categories->insertCategory($state, $parentCategory);
    }

    /**
     * Delete a category
     *
     * This function is called through ajax and deletes
     * a category.
     * @param int $catId
     */
    private function deleteCategory($catId=null)
    {
        if (!isset($catId)) {
            $catId = intval($_GET['id']);
        }

        try {
            $deletedCategories = $this->categories->deleteCategory($catId);
            // delete the articles that were assigned to the deleted categories
            foreach ($deletedCategories as $cat) {
                $this->articles->deleteArticlesByCategory($cat);
            }
            $this->tags->clearTags();
        } catch (DatabaseError $e) {
            $this->sendAjaxError($e->formatted());
        }

        die();
    }

    /**
     * Delete an article
     */
    private function deleteArticle()
    {
        $id = intval($_GET['id']);

        try {
            $this->articles->deleteOneArticle($id);
            $this->tags->clearTags();
        } catch (DatabaseError $e) {
            $this->sendAjaxError($e->formatted());
        }
    }

    /**
     * Update an existing category.
     *
     * @global $_ARRAYLANG
     * @global $objDatabase
     * @return int Id of the updated category
     */
    private function updateCategory()
    {
        global $objDatabase, $_ARRAYLANG;

        $id = intval($_POST['update_id']);
        $parentCategory = intval($_POST['parentCategory']);
        $state = intval($_POST['state']);

        foreach ($_POST as $key => $var) {
            if (strstr($key, "name_")) {
                $lang = substr($key, 5);
                $this->categories->addContent($lang, $var);
            }
        }

        $this->categories->updateCategory($id, $state, $parentCategory);
        return $id;
    }

    /**
     * Shows an overview of all entries.
     *
     * @global $_CORELANG
     * @global $_ARRAYLANG
     * @global $_LANGID
     * @return string HTML code for the overview
     */
    private function categoriesOverview()
    {
        global $_CORELANG, $_ARRAYLANG, $_LANGID;

        $this->pageTitle = $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORIES'];
        $this->tpl->loadTemplateFile('module_knowledge_categories_overview.html',true,true);
        $this->tpl->setGlobalVariable("MODULE_INDEX", MODULE_INDEX);

        $this->tpl->setVariable(array(
            'TXT_CATEGORIES'                       => $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORIES'],
            'TXT_NAME'                          => $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORY_NAME'],
            'TXT_ACTIONS'                       => $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORY_ACTIONS'],
            'TXT_NO_CATEGORY_OBJECTS'           => $_ARRAYLANG['TXT_KNOWLEDGE_NO_CATEGORY_OBJECTS'],
               'TXT_CONFIRM_CATEGORY_DELETION'     => $_ARRAYLANG['TXT_KNOWLEDGE_CONFIRM_CATEGORY_DELETION'],
               'TXT_ENTRIES_AMOUNT'                => $_ARRAYLANG['TXT_KNOWLEDGE_ENTRIES_AMOUNT'],
               'TXT_SORT'                          => $_ARRAYLANG['TXT_KNOWLEDGE_SORT'],

            'TXT_ENTRIES_SUBTITLE_DATE'            => $_ARRAYLANG['TXT_KNOWLEDGE_ENTRY_MANAGE_DATE'],
            'TXT_ENTRIES_SUBTITLE_SUBJECT'        => $_ARRAYLANG['TXT_KNOWLEDGE_ENTRY_ADD_SUBJECT'],
            'TXT_ENTRIES_SUBTITLE_LANGUAGES'    => $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORY_ADD_LANGUAGES'],
            'TXT_ENTRIES_SUBTITLE_HITS'            => $_ARRAYLANG['TXT_KNOWLEDGE_ENTRY_MANAGE_HITS'],
            'TXT_ENTRIES_SUBTITLE_COMMENTS'        => $_ARRAYLANG['TXT_KNOWLEDGE_ENTRY_MANAGE_COMMENTS'],
            'TXT_ENTRIES_SUBTITLE_VOTES'        => $_ARRAYLANG['TXT_KNOWLEDGE_ENTRY_MANAGE_VOTE'],
            'TXT_ENTRIES_SUBTITLE_USER'            => $_CORELANG['TXT_USER'],
            'TXT_ENTRIES_SUBTITLE_EDITED'        => $_ARRAYLANG['TXT_KNOWLEDGE_ENTRY_MANAGE_UPDATED'],
            'TXT_ENTRIES_DELETE_ENTRY_JS'        => $_ARRAYLANG['TXT_KNOWLEDGE_ENTRY_DELETE_JS'],
            'TXT_ENTRIES_MARKED'                => $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORY_MANAGE_SUBMIT_MARKED'],
            'TXT_ENTRIES_SELECT_ALL'            => $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORY_MANAGE_SUBMIT_SELECT'],
            'TXT_ENTRIES_DESELECT_ALL'            => $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORY_MANAGE_SUBMIT_DESELECT'],
            'TXT_ENTRIES_SUBMIT_SELECT'            => $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORY_MANAGE_SUBMIT_ACTION'],
            'TXT_ENTRIES_SUBMIT_DELETE'            => $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORY_MANAGE_SUBMIT_DELETE'],
               'TXT_ENTRIES_SUBMIT_DELETE_JS'        => $_ARRAYLANG['TXT_KNOWLEDGE_ENTRY_MANAGE_SUBMIT_DELETE_JS'],

               "EDIT_ALLOWED"                        => (\Permission::checkAccess(ACCESS_ID_EDIT_CATEGORIES, 'static', true)) ? "true" : "false",
            'NOT_ALLOWED_MSG'                   => $_ARRAYLANG['TXT_KNOWLEDGE_ACCESS_DENIED']
           ));

           try {
               $this->categories->readCategories();
               $this->articles->readArticles();
           } catch (DatabaseError $e) {
               $this->errorMessage = $_ARRAYLANG['TXT_KNOWLEDGE_ERROR_OVERVIEW'];
               $this->errorMessage .= $e->formatted();
               return;
           }

           $categories = $this->parseCategoryOverview($this->categories->categoryTree);
        $this->tpl->replaceBlock("remove_area", "");
           $this->tpl->setVariable("CATLIST", $categories);

           return $this->tpl->get();
    }

    /**
     * Recursive function to parse the categories
     *
     * @param array $arr
     * @param int $level
     * @param int $id
     * @global $_LANGID
     * @return string
     */
    private function parseCategoryOverview($arr, $level=0, $id=0)
    {
        global $_LANGID;

        $rows = "";
        foreach ($arr as $key => $subcategories) {
            if (count($subcategories)) {
                $sub = $this->parseCategoryOverview($subcategories, $level+1, $key);
            } else {
                $sub = "";
            }

            $ul_id = "ul_".$id;

            $category = $this->categories->categories[$key];
            $this->tpl->setVariable(array(
                    'CSS_DISPLAY'               => ($level == 0) ? "" : "none",
                    'CSS_BGCOLOR'               => (!empty($_GET['highlight']) && $_GET['highlight'] == $key) ? "#ceff88" : "",
                       'CATEGORY_ID'                => $key,
                       'CATEGORY_ACTIVE_LED'       => ($category['active']) ? "green" : "red",
                       'CATEGORY_ACTIVE_STATE'     => ($category['active']) ? 0 : 1,
                       'CATEGORY_INDENT'           => ($level == 1) ? 18 : $level * 28,
                       'CATEGORY_SUBJECT'          => $category['content'][$_LANGID]['name'],
                    'CATEGORY_PLUS_VISIBLITY'   => (count($subcategories))  ? "visible" : "hidden",
                    'SUB'                       => $sub,
                    'ENTRIES_AMOUNT'            => $this->articles->countEntriesByCategory($key),
                    'SORTABLE_ID'               => $ul_id
                   ));
            if ($level) {
//                $this->tpl->touchBlock("arrow");
//                $this->tpl->parse("arrow");
            }
            $this->tpl->parse("row");
            $rows .= $this->tpl->get("row", true);
        }

        $this->tpl->setVariable(array(
            "ROWS"      => $rows,
            "UL_ID"     => $ul_id
        ));
        $this->tpl->touchBlock("list");
        $this->tpl->parse("list");
        $list = $this->tpl->get("list", true);

        $this->tpl->setVariable(array(
            "SORTABLE_ID"     => $ul_id
        ));
        $this->tpl->parse("sortable");

        return $list;
    }

    /**
     * Well, it seems that this is not needed anymore
     *
     * @param $id
     * @param unknown_type $level
     */
    /*
    private function parseOverviewArticle($id, $level, $rowId)
    {
        global $_LANGID;

        $level = ($level > 0) ? $level+1 : $level;
        $article = &$this->articles->articles[$id];
        $this->tpl->setVariable(array(
            "QUESTION_ID"           => $id,
            "QUESTION"              => $article['content'][$_LANGID]['article'],
            "QUESTION_INDENT"       => $level * 17,
            "QUESTION_ACTIVE_LED"   => ($article['active']) ? "green" : "red",
            "QUESTION_ACTIVE_STATE" => ($article['active']) ? 0 : 1
        ));

        $this->tpl->parse("article");
        $this->tpl->setVariable(array(
            "ROW_ID"        => $rowId,
            "ROW_STYLE"     => "style=\"display: none;\"",
            "PARENT_CLASS"  => $article['category']
        ));
        $this->tpl->parse("row");
    }*/


    /**
     * Show the page for adding or editing a new category
     *
     * @param bool Is it a new category or are we editing?
     * @global $_ARRAYLANG
     * @return string
     */
    private function editCategory($new=false)
    {
        global $_ARRAYLANG;

        $this->pageTitle = $_ARRAYLANG['TXT_EDIT_CATEGORY'];
        $this->tpl->loadTemplateFile('module_knowledge_categories_edit.html',true,true);
        $this->tpl->setGlobalVariable("MODULE_INDEX", MODULE_INDEX);

        // the language variables
        $this->tpl->setGlobalVariable(array(
            "TXT_GENERAL_SETTINGS"  => ($new) ? $_ARRAYLANG['TXT_KNOWLEDGE_ADD_CATEGORY'] : $_ARRAYLANG['TXT_KNOWLEDGE_EDIT_CATEGORY'] ,
            "TXT_PARENT_CATEGORY"   => $_ARRAYLANG['TXT_KNOWLEDGE_PARENT_CATEGORY'],
            "TXT_STATE"             => $_ARRAYLANG['TXT_KNOWLEDGE_STATE'],
            "TXT_ACTIVE"            => $_ARRAYLANG['TXT_KNOWLEDGE_ACTIVE'],
            "TXT_INACTIVE"          => $_ARRAYLANG['TXT_KNOWLEDGE_INACTIVE'],
            "TXT_QUESTION"          => $_ARRAYLANG['TXT_KNOWLEDGE_QUESTION'],
            "TXT_ANSWER"            => $_ARRAYLANG['TXT_KNOWLEDGE_ANSWER'],
            "TXT_SUBMIT"            => $_ARRAYLANG['TXT_KNOWLEDGE_SUBMIT'],
            "TXT_NAME"              => $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORY_NAME'],
            "TXT_EDIT_EXTENDED"     => $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORY_ADD_EXTENDED'],
            "TXT_TOP_CATEGORY"      => $_ARRAYLANG['TXT_KNOWLEDGE_TOP_CATEGORY']
        ));

        $languages = $this->createLanguageArray();

          try {
            $this->categories->readCategories();
        } catch (DatabaseError $e) {
            $this->errorMessage = $_ARRAYLANG['TXT_ERROR_READING_CATEGORIES'];
                $this->errorMessage .= $e->formatted();
                return;
        }

        if ($new) {
            $catId = 0;
            // make a dummy category
            $category = array(
               'active'    => 1,
               'parent'    => 0,
               'content'  => array()
            );

            foreach ($languages as $key => $value) {
                $category['content'][$key] = array(
                   'name' => "",
                );
            }
        } else {
            // get the category data
            $catId = intval($_GET['id']);
            $category = $this->categories->categories[$catId];
        }

        $langKeys = array_keys($languages);

        // the general data
        $this->tpl->setVariable(array(
           "CATEGORY_DROPDOWN"     => $this->categoryDropdown($this->categories->categoryTree, $category['parent']),
           "ACTIVE_CHECKED"        => ($category['active']) ? "checked=\"checked\"" : "",
           "INACTIVE_CHECKED"      => ($category['active']) ? "" : "checked=\"checked\"",
           "EDIT_NAME"             => $category['content'][$langKeys[0]]['name'],
           "FORM_ACTION"           => ($new) ? "insert" : "update",
           "UPDATE_ID"             => $catId
        ));

        // the different languages
        foreach($languages as $langId => $lang) {
            $this->tpl->setVariable(array(
                'EDIT_NAME_LANGID'    =>    $langId,
                'EDIT_NAME_LANG'    =>    $lang['long'],
                'EDIT_NAME_VALUE'    =>    $category['content'][$langId]['name'] // empty since we make a new category
            ));

            $this->tpl->parse('lang_name');
        }

        return $this->tpl->get();
    }


    /**
     * Category Dropdown
     *
     * Return a string of option-tags for a dropdown
     * containing all categories.
     * This function is called recursively
     * @param $categories The categories to parse
     * @param $select The id of the entry that should be selected in the dropdown
     * @param $level The level of the current recursion
     * @global $_LANGID
     * @return string The option-tags of the dropdown
     */
    private function categoryDropdown($categories, $select = 0, $level = 0)
    {
        global $_LANGID;

        $options = "";
        foreach ($categories as $category => $subcats) {
            // the option line
            $name = $this->categories->categories[$category]['content'][$_LANGID]['name'];
            $selected = ($select == $category) ? "selected=\"selected\"" : "";

            $options .= "<option value=\"".$category."\" ".$selected.">".str_repeat("..", $level).$name."</option>";

            // do the subcategories
            if (count($subcats)) {
                $options .= $this->categoryDropdown($subcats, $select, $level+1);
            }
        }
        return $options;
    }

    /**
     * This is not called anywhere
     *
     * @param unknown_type $tree
     * @return unknown
     */
    /*
    private function prepareJsTree($tree)
    {
        $arr = array();
        foreach ($tree as $key => $elem) {
            $arr[$key] = array(
                "id"            => $key,
                "state"         => 0
            );
            if (count($elem) > 0) {
                $arr[$key]["children"] =  $this->prepareJsTree($elem);
            }
        }
        return $arr;
    }
    */

    /**
     * Switch the category state
     *
     * Make a category either active or inactive. This is
     * called through ajax.
     */
    private function switchCategoryState()
    {
        $id = intval($_GET['id']);
        $action = intval($_GET['switchTo']);

        try {
            if ($action == 1) {
                $this->categories->activate($id);
            } else {
                $this->categories->deactivate($id);
            }
        } catch (DatabaseError $e) {
            $this->sendAjaxError($e->formatted());
        }

        die(1);
    }

    /**
     * Switch the entry state
     *
     * Make a state either active or inactive. This is
     * called through ajax.
     */
    private function switchArticleState()
    {
        $id = intval($_GET['id']);
        $action = intval($_GET['switchTo']);

        try {
            if ($action == 1) {
                $this->articles->activate($id);
            } else {
                $this->articles->deactivate($id);
            }
        } catch (DatabaseError $e) {
            $this->sendAjaxError($e->formatted());
        }
        die(1);
    }

    /**
     * Show the article title bar
     *
     * Show the article title bar and below the content of the
     * current page.
     * @global $_ARRAYLANG
     * @param string $content The content that should be display below the bar
     * @param string $active The menu entry that should be active
     */
    private function articles($content, $active)
    {
        global $_ARRAYLANG;

        $this->tpl->loadTemplateFile('module_knowledge_articles.html', true, true);
        $this->tpl->setGlobalVariable("MODULE_INDEX", MODULE_INDEX);

        $this->tpl->setVariable(array(
            "ARTICLES_FILE"                 => $content,
            "ACTIVE_".strtoupper($active)   => 'class="active"'
        ));

        $this->tpl->setVariable(array(
            "TXT_OVERVIEW"          => $_ARRAYLANG['TXT_OVERVIEW'],
            "TXT_ADD"               => $_ARRAYLANG['TXT_ADD']
        ));
    }

    /**
     * Overview over the articles
     *
     * @global $_ARRAYLANG
     * @global $_CORELANG
     * @return string
     */
    private function articleOverview()
    {
        global $_ARRAYLANG, $_CORELANG;

        \JS::activate('cx');

        $this->pageTitle = $_ARRAYLANG['TXT_KNOWLEDGE_ARTICLES'];
        $this->tpl->loadTemplateFile("module_knowledge_articles_overview.html");
        $this->tpl->setGlobalVariable("MODULE_INDEX", MODULE_INDEX);

        try {
            $this->categories->readCategories();
            $catTree = $this->overviewCategoryTree($this->categories->categoryTree);
        } catch (DatabaseError $e) {
            $this->errorMessage = $_ARRAYLANG['TXT_KNOWLEDGE_ERROR_OVERVIEW'];
            $this->errorMessage .= $e->formatted();
            return;
        }

        $this->tpl->setVariable(array(
            // language variables
            "TXT_SELECT_CATEGORY"           => $_ARRAYLANG['TXT_KNOWLEDGE_SELECT_CATEGORY'],
            "TXT_CONFIRM_ARTICLE_DELETION"  => $_ARRAYLANG['TXT_CONFIRM_ARTICLE_DELETION'],
            "TXT_JUMP_TO_ARTICLE"           => $_ARRAYLANG['TXT_KNOWLEDGE_JUMP_TO_ARTICLE'],

            // other stuff
            "CATLIST"                       => $catTree,
            "EDIT_ALLOWED"                  => (\Permission::checkAccess(ACCESS_ID_EDIT_ARTICLES, 'static', true)) ? "true" : "false",
            'NOT_ALLOWED_MSG'               => $_ARRAYLANG['TXT_KNOWLEDGE_ACCESS_DENIED'],
        ));

        return $this->tpl->get();
    }

    /**
     * Parse the category tree
     *
     * Recursively parse the category tree on the left
     * side of the article's overview.
     * @param array $catTree
     * @param int $level
     * @global $_LANGID
     * @return string
     */
    private function overviewCategoryTree($catTree, $level=0)
    {
        global $_LANGID;

        $rows = "";
        foreach ($catTree as $key => $subcategories) {
            if (count($subcategories)) {
                $sub = $this->overviewCategoryTree($subcategories, $level+1);
            } else {
                $sub = "";
            }
            $this->tpl->setVariable(array(
                "ID"                        => $key,
                "CATEGORY_ID"               => $key,
                "NAME"                      => $this->categories->categories[$key]['content'][$_LANGID]['name'],
                "SUB"                       => $sub,
                "CATEGORY_PLUS_VISIBLITY"   => (count($subcategories)) ? "visible" : "hidden",
                "CSS_DISPLAY"               => ($level == 0) ? "" : "none",
                "ENTRY_COUNT"               => $this->articles->countEntriesByCategory($key),
//                "CAT_ROW_WIDTH"                => 230 + $level ,
            ));
//            $this->tpl->touchBlock("arrow");
//            $this->tpl->parse("arrow");
            $this->tpl->parse("row");
            $rows .= $this->tpl->get("row", true);
        }
        $this->tpl->setVariable("ROWS", $rows);
        $this->tpl->touchBlock("list");
        $this->tpl->parse("list");
        return $this->tpl->get("list", true);
    }

    /**
     * Generate an articlelist
     *
     * @param $articles An array of articles
     * @param $category Category information
     * @return String
     */
    private function parseArticleList($articles, $categoryname="", $count = null, $position = null, $standalone=false)
    {
        global $_LANGID, $_ARRAYLANG, $_CORELANG;

        $id = intval($_GET['id']);

        try {
            $articles = $this->articles->getArticlesByCategory($id);
            $category = $this->categories->getOneCategory($id);
        } catch (DatabaseError $e) {
            die($e->plain());
        }

        $tpl = new \Cx\Core\Html\Sigma(ASCMS_MODULE_PATH."/Knowledge/View/Template/Backend/");
        $tpl->setErrorHandling(PEAR_ERROR_DIE);
        $tpl->loadTemplateFile("module_knowledge_articles_overview_articlelist.html");
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($tpl);
        $tpl->setGlobalVariable("MODULE_INDEX", MODULE_INDEX);
        $tpl->setGlobalVariable(array(
            // language variables
            "TXT_NAME"          => $_ARRAYLANG['TXT_NAME'],
            "TXT_VIEWED"        => $_ARRAYLANG['TXT_KNOWLEDGE_VIEWED'],
            "TXT_SORT"          => $_ARRAYLANG['TXT_KNOWLEDGE_SORT'],
            "TXT_STATE"         => $_ARRAYLANG['TXT_KNOWLEDGE_STATE'],
            "TXT_QUESTION"      => $_ARRAYLANG['TXT_KNOWLEDGE_QUESTION'],
            "TXT_HITS"          => $_ARRAYLANG['TXT_KNOWLEDGE_HITS'],
            "TXT_RATING"        => $_ARRAYLANG['TXT_KNOWLEDGE_RATING'],
            "TXT_ACTIONS"       => $_ARRAYLANG['TXT_KNOWLEDGE_ACTIONS'],
            "TXT_CATEGORY_NAME" => $categoryname ,
            // getPaging(count, position, extraargv, paging-text, showeverytime, limit)
            //"PAGING"            => getPaging()
            "TXT_BY"            => "bei",
            "TXT_VOTINGS"       => "Abstimmungen"
        ));

        if (!empty($articles)) {
            foreach ($articles as $key => $article) {
                $tpl->setVariable(array(
                    "ARTICLEID"             => $key,
                    "QUESTION"              => contrexx_raw2xhtml($article['content'][$_LANGID]['question']),
                    "ACTIVE_STATE"          => abs($article['active']-1),
                    "CATEGORY_ACTIVE_LED"   => ($article['active']) ? "green" : "red",
                    "HITS"                  => $article['hits'],
                    "VOTEVALUE"             => round((($article['votes'] > 0) ? $article['votevalue'] / $article['votes'] : 0), 2),
                    "VOTECOUNT"             => $article['votes'],
                    "MAX_RATING"            => $this->settings->get("max_rating")
                ));
                $tpl->parse("row");
            }
        } else {
            $tpl->setVariable(array(
                "TXT_NO_ARTICLES"       => $_ARRAYLANG['TXT_KNOWLEDGE_NO_ARTICLES_IN_CAT']
            ));
            $tpl->parse("no_articles");
        }

        $tpl->parse("content");
        return $tpl->get("content");
    }

    /**
     * Get Articles
     *
     * This function is called through ajax.
     * Return a JSON object with all the needed information for the
     * article overview.
     * @global $_LANGID
     * @global $_ARRAYLANG
     * @global $_CORELANG
     */
    private function getArticles()
    {
        global $_LANGID, $_ARRAYLANG, $_CORELANG;

        $id = intval($_GET['id']);

        try {
            $articles = $this->articles->getArticlesByCategory($id);
            $category = $this->categories->getOneCategory($id);
        } catch (DatabaseError $e) {
            die($e->plain());
        }

        $content = $this->parseArticleList($articles, $category['content'][$_LANGID]['name']);
        $response = Array();
        $response['list'] = $content;

        \Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH . '/PEAR/Services/JSON.php');
        $objJson = new \Services_JSON();
        $jsonResponse = $objJson->encode($response);
        echo $jsonResponse;
        die();
    }


    /**
     * Show the edit form
     *
     * Show the form to edit or add an article
     * @param bool $new If this is going to be a new article
     * @global $_ARRAYLANG
     * @return string
     */
    private function editArticle($new = false)
    {
        global $_ARRAYLANG;

        $this->pageTitle = $_ARRAYLANG['TXT_EDIT_ARTICLE'];
        $this->tpl->loadTemplateFile('module_knowledge_articles_edit.html', true, true);
        $this->tpl->setGlobalVariable("MODULE_INDEX", MODULE_INDEX);

        $id = (empty($_GET['id'])) ? 0 : $_GET['id'];

        try {
            $this->categories->readCategories();
            if (!$new) {
                $article = $this->articles->getOneArticle($id);
                if (!$article) {
                    $this->errorMessage = $_ARRAYLANG['TXT_KNOWLEDGE_ERROR_NO_ARTICLE'];
                    return;
                }
                $tags = $this->tags->getByArticle($id);
            }
        } catch (DatabaseError $e) {
            $this->errorMessage = $_ARRAYLANG['TXT_KNOWLEDGE_ERROR_OVERVIEW'];
            $this->errorMessage .= $e->formatted();
            return;
        }

        $languages = $this->createLanguageArray();

        // make an empty article if a new article is to be added
        if ($new) {
            $article = array(
               'category'      => 0,
               'active'        => 1,
               'content'       => array()
            );

            foreach ($languages as $key => $value) {
                $article['content'][$key] = array(
                   'question' => "",
                   'answer' => ""
                );
            }
        }

        if (isset($_GET['updated']) && $_GET['updated']) {
           $this->okMessage = $_ARRAYLANG['TXT_KNOWLEDGE_SUCCESSFULLY_SAVED'];
        }

        $this->tpl->setVariable(array(
           "TXT_CATEGORY"      => $_ARRAYLANG['TXT_KNOWLEDGE_CATEGORY'],
           "TXT_ACTIVE"        => $_ARRAYLANG['TXT_KNOWLEDGE_ACTIVE'],
           "TXT_STATUS"        => $_ARRAYLANG['TXT_KNOWLEDGE_STATE'],
           "TXT_INACTIVE"      => $_ARRAYLANG['TXT_KNOWLEDGE_INACTIVE'],
           "TXT_SUBMIT"        => $_ARRAYLANG['TXT_KNOWLEDGE_SUBMIT'],
           "TXT_CHOOSE_CATEGORY"   => $_ARRAYLANG['TXT_KNOWLEDGE_CHOOSE_CATEGORY'],
           "TXT_PLEASE_CHOOSE_CATEGORY" => $_ARRAYLANG['TXT_KNOWLEDGE_PLEASE_CHOOSE_CATEGORY'],

           "CATEGORIES"        => $this->categoryDropdown($this->categories->categoryTree, $article['category']),
           "ACTION"            => ($new) ? "insert" : "update",
           "TITLE"             => ($new) ? $_ARRAYLANG['TXT_KNOWLEDGE_ADD'] : $_ARRAYLANG['TXT_KNOWLEDGE_EDIT'],

           "ACTIVE_CHECKED"    => $article['active'] ? "checked=\"checked\"" : "",
           "INACTIVE_CHECKED"  => $article['active'] ? "" : "checked=\"checked\"",
        ));

        $first = true;
        foreach ($languages as $langId => $lang) {
               // tags
            if (!$new) {
                $tagstring = "";
                foreach ($tags as $tag) {
                    if ($tag['lang'] == $langId) {
                       $tagstring.= $tag['name'].", ";
                    }
                }
                // chop the last ', ' off
                $tagstring = substr($tagstring, 0, -2);
                $this->tpl->setVariable(array(
                   "TAGS"      => $tagstring
                ));
            }

            $this->tpl->setVariable(array(
               "TABS_NAME"         => $lang['long'],
               "TABS_DIV_ID"       => $lang['long'],
               "TABS_LINK_ID"      => $lang['long'],
               "TABS_LANG_ID"      => $langId,
               "TABS_CLASS"        => ($first) ? "active" : "inactive",
            ));
            $this->tpl->parse("showLanguageTabs");

            $this->tpl->setVariable(array(
               "TXT_QUESTION"      => $_ARRAYLANG['TXT_KNOWLEDGE_QUESTION'],
               "TXT_ANSWER"        => $_ARRAYLANG['TXT_KNOWLEDGE_ANSWER'],
               "TXT_SORT_BY"       => $_ARRAYLANG['TXT_KNOWLEDGE_SORT_BY'],
               "TXT_TAGS"          => $_ARRAYLANG['TXT_KNOWLEDGE_TAGS'],
               "TXT_COMMA_SEPARATED" => $_ARRAYLANG['TXT_KNOWLEDGE_COMMA_SEPARATED'],
               "TXT_AVAILABLE_TAGS" => $_ARRAYLANG['TXT_KNOWLEDGE_AVAILABLE_TAGS'],
               "TXT_POPULARITY"    => $_ARRAYLANG['TXT_KNOWLEDGE_POPULARITY'],
               "TXT_ALPHABETICAL"  => $_ARRAYLANG['TXT_KNOWLEDGE_ALPHABETICAL'],

               "LANG"              => $lang['long'],
               "LANG_ID"           => $langId,
               "DISPLAY"           => ($first) ? "block" : "none",
               "ID"                => $lang['long'],
               "QUESTION"          => (isset($article['content'][$langId]) ?
                                       contrexx_raw2xhtml($article['content'][$langId]['question'])
                                       : ''),
               "ANSWER"            => isset($article['content'][$langId]) ?
                                       htmlentities($article['content'][$langId]['answer'], ENT_QUOTES, CONTREXX_CHARSET)
                                       : '',
            ));
            $this->tpl->parse("langDiv");

            if ($first) {
                $this->tpl->setVariable(array(
                    "ANSWER_PREVIEW"        => new \Cx\Core\Wysiwyg\Wysiwyg('answer_preview',
                                               isset($article['content'][$langId]) ?
                                               contrexx_raw2xhtml($article['content'][$langId]['answer'])
                                               : '', 'full'),
                   "KNOWLEDGE_ANSWER_LANG"  => $langId
                ));
            }

            $first = false;
        }

        if (!$new) {
            $this->tpl->setVariable("ID", $id);
            $this->tpl->parse("edit_id");
        }

        return $this->tpl->get();
    }

    /**
     * Insert an article
     *
     * @global $_ARRAYLANG
     * @return int Id of the inserted article
     */
    private function insertArticle()
    {
        global $_ARRAYLANG;

        if (!isset($_POST['category'])) {
            return false;
        }

        $category = $_POST['category'];
        $state = $_POST['state'];

        $languages = $this->createLanguageArray();

        $tags = array();
        foreach ($languages as $langId => $lang) {
            $question = $_POST['question_'.$langId];
            $answer = $_POST['answer_'.$langId];

            $this->articles->addContent($langId, $question, $answer);
            $tags[$langId] = $_POST['tags_'.$langId];
        }

        try {
            $id = $this->articles->insert($category, $state);
            foreach ($tags as $lang => $tag) {
                $this->tags->updateFromString($id, $tag, $lang);
            }
        } catch (DatabaseError $e) {
            $this->errorMessage = $_ARRAYLANG['TXT_KNOWLEDGE_ERROR_OVERVIEW'];
            $this->errorMessage .= $e->formatted();
            return;
        }

        return $id;
    }

    /**
     * Update an article
     *
     * @global $_ARRAYLANG
     * @return $id Id of the updated article
     */
    private function updateArticle()
    {
        global $_ARRAYLANG;

        if (!isset($_POST['category'])) {
            return false;
        }

        $category = $_POST['category'];
        $state = $_POST['state'];
        $id = $_POST['id'];

        $languages = $this->createLanguageArray();

        foreach ($languages as $langId => $lang) {
            $question = $_POST['question_'.$langId];
            $answer = $_POST['answer_'.$langId];

            $this->articles->addContent($langId, $question, $answer);
            $tags[$langId] = $_POST['tags_'.$langId];
        }

        try {
            $this->articles->update($id, $category, $state);
            $this->tags->clearTags();
            foreach ($tags as $lang => $tag) {
                $this->tags->updateFromString($id, $tag, $lang);
            }
        } catch (DatabaseError $e) {
            $this->errorMessage = $_ARRAYLANG['TXT_KNOWLEDGE_ERROR_OVERVIEW'];
            $this->errorMessage .= $e->formatted();
            return;
        }

        return $id;
    }

    /**
     * Save article order
     *
     * Save the new order of article. Called through ajax.
     */
    private function sortArticles()
    {
        if (empty($_POST['articlelist'])) {
            die();
        }

        try {
            foreach ($_POST['articlelist'] as $position => $id) {
                $this->articles->setSort($id, $position);
            }
        } catch (DatabaseError $e) {
            $this->sendAjaxError($e->formatted());
        }

        die();

        print $_GET['order'];
        $order = explode("articlelist[]=", $_GET['order']);

        foreach ($order as $sort => $id) {
            $id = intval($id);
            if ($id) {
                $this->articles->setSort($id, $sort);
            }
        }

        die();
    }

    /**
     * Save a category's order
     *
     * Called through ajax.
     */
    private function sortCategory()
    {
        $keys = array_keys($_POST);
        try {
            if (preg_match("/ul_[0-9]*/", $keys[0])) {
                foreach ($_POST[$keys[0]] as $position => $id) {
                    $this->categories->setSort($id, $position);
                }
            }
        } catch (DatabaseError $e) {
            $this->sendAjaxError($e->formatted());
        }

        die(1);
    }

    /**
     * Return a list of tags
     *
     * Called through ajax
     */
    private function getTags()
    {
        $lang = (isset($_GET['lang'])) ? $_GET['lang'] : 1;
        try {
            if ($_GET['sort'] == "popularity") {
                $tags = $this->tags->getAllOrderByPopularity($lang);
            } else {
                $tags = $this->tags->getAllOrderAlphabetically($lang);
            }
        } catch (DatabaseError $e) {
            // TODO
            // this is not handled anyhow (and not only here)
            $this->sendAjaxError($e->formatted());
        }
        $this->tpl->loadTemplateFile('module_knowledge_articles_edit_taglist.html');
        $this->tpl->setGlobalVariable("MODULE_INDEX", MODULE_INDEX);
        $return_tags = array();
        $classnumber = 1;
        foreach ($tags as $tag) {
            $this->tpl->setVariable(array(
                "TAG"       => $tag['name'],
                "TAGID"     => $tag['id'],
                "CLASSNUMBER" => (++$classnumber % 2) + 1,
                "LANG"      => $lang,
            ));
            $this->tpl->parse("tag");
            $return_tags[$tag['id']] = $tag['name'];
        }
        $this->tpl->parse("taglist");
        $taglist = $this->tpl->get("taglist");

        \Env::get('ClassLoader')->loadFile(ASCMS_LIBRARY_PATH . '/PEAR/Services/JSON.php');
        $objJson = new \Services_JSON();
        $jsonResponse = $objJson->encode(array("html" => $taglist, "available_tags" => $return_tags));

        die($jsonResponse);
    }

    /**
     * Show the settings title row
     *
     * @param string $content
     * @param string $active
     * @global $_ARRAYLANG
     * @global $_CORELANG
     */
    private function settings($content, $active)
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->tpl->loadTemplateFile('module_knowledge_settings_top.html', true, true);
        $this->tpl->setGlobalVariable("MODULE_INDEX", MODULE_INDEX);

        $this->tpl->setVariable(array(
            "SETTINGS_FILE"                 => $content,
            "ACTIVE_".strtoupper($active)   => 'class="active"'
        ));

        $this->tpl->setVariable(array(
            "TXT_SETTINGS"          => $_ARRAYLANG['TXT_KNOWLEDGE_SETTINGS'],
            "TXT_PLACEHOLDER"       => $_ARRAYLANG['TXT_KNOWLEDGE_PLACEHOLDER']
        ));
    }


    /**
     * Show the settings
     *
     * @global $_ARRAYLANG
     * @global $_CORELANG
     * @return string  The html code of the settings page
     */
    private function settingsOverview()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->pageTitle = $_ARRAYLANG['TXT_SETTINGS'];
        $this->tpl->loadTemplateFile('module_knowledge_settings.html',true,true);
        $this->tpl->setGlobalVariable("MODULE_INDEX", MODULE_INDEX);

        $this->tpl->setVariable(array(
           'TXT_SETTINGS'                          => $_ARRAYLANG['TXT_SETTINGS'],
           'TXT_FRONTPAGE'                         => $_ARRAYLANG['TXT_FRONTPAGE'],
           'TXT_MAX_SUBCATEGORIES'                 => $_ARRAYLANG['TXT_KNOWLEDGE_MAX_SUBCATEGORIES'],
           'TXT_MAX_SUBCATEGORIES_DESCRIPTION'     => $_ARRAYLANG['TXT_KNOWLEDGE_MAX_SUBCATEGORIES_DESCRIPTION'],
           'TXT_COLUMN_NUMBER'                     => $_ARRAYLANG['TXT_KNOWLEDGE_COLUMN_NUMBER'],
           'TXT_COLUMN_NUMBER_DESCRIPTION'         => $_ARRAYLANG['TXT_KNOWLEDGE_COLUMN_NUMBER_DESCRIPTION'],
           'TXT_SAVE'                              => $_ARRAYLANG['TXT_KNOWLEDGE_SAVE'],
           'TXT_ARTICLES'                          => $_ARRAYLANG['TXT_KNOWLEDGE_ARTICLES'],
           'TXT_MAX_RATING'                        => $_ARRAYLANG['TXT_KNOWLEDGE_MAX_RATING'],
           'TXT_MAX_RATING_DESCRIPTION'            => $_ARRAYLANG['TXT_KNOWLEDGE_MAX_RATING_DESCRIPTION'],

           'TXT_COLUMN_MOST_READ_COUNT_DESCRIPTION'    => $_ARRAYLANG['TXT_KNOWLEDGE_COLUMN_MOST_READ_COUNT_DESCRIPTION'],
           'TXT_GENERAL'                           => $_ARRAYLANG['TXT_KNOWLEDGE_GENERAL'],
           'TXT_TIDY_TAGS'                         => $_ARRAYLANG['TXT_KNOWLEDGE_TIDY_TAGS'],
           'TXT_TIDY_TAGS_DESCRIPTION'             => $_ARRAYLANG['TXT_KNOWLEDGE_TIDY_TAGS_DESCRIPTION'],
           'TXT_RESET_HITS'                        => $_ARRAYLANG['TXT_KNOWLEDGE_RESET_HITS'],
           'TXT_RESET_HITS_DESCRIPTION'            => $_ARRAYLANG['TXT_KNOWLEDGE_RESET_HITS_DESCRIPTION'],
           'TXT_REPLACE_PLACEHOLDERS'              => $_ARRAYLANG['TXT_KNOWLEDGE_REPLACE_PLACEHOLDERS'],
           'TXT_REPLACE_PLACEHOLDERS_DESCRIPTION'  => $_ARRAYLANG['TXT_KNOWLEDGE_REPLACE_PLACEHOLDERS_DESCRIPTION'],

           'TXT_BEST_RATED_PLACEHOLDER'            => $_ARRAYLANG['TXT_KNOWLEDGE_BEST_RATED_PLACEHOLDER'],
           'TXT_BEST_RATED_AMOUNT'                 => $_ARRAYLANG['TXT_KNOWLEDGE_BEST_RATED_AMOUNT'],
           'TXT_BEST_RATED_AMOUNT_DESCRIPTION'     => $_ARRAYLANG['TXT_KNOWLEDGE_BEST_RATED_AMOUNT_DESCRIPTION'],
           'TXT_BEST_RATED_SIDEBAR_LENGTH'               => $_ARRAYLANG['TXT_KNOWLEDGE_BEST_RATED_SIDEBAR_LENGTH'],
           'TXT_BEST_RATED_SIDEBAR_LENGTH_DESCRIPTION'   => $_ARRAYLANG['TXT_KNOWLEDGE_BEST_RATED_SIDEBAR_LENGTH_DESCRIPTION'],
           'TXT_BEST_RATED_TEMPLATE'               => $_ARRAYLANG['TXT_KNOWLEDGE_BEST_RATED_TEMPLATE'],
           'TXT_BEST_RATED_TEMPLATE_DESCRIPTION'   => $_ARRAYLANG['TXT_KNOWLEDGE_BEST_RATED_TEMPLATE_DESCRIPTION'],

           'TXT_MOST_READ_PLACEHOLDER'             => $_ARRAYLANG['TXT_KNOWLEDGE_MOST_READ_PLACEHOLDER'],
           'TXT_MOST_READ_AMOUNT'                  => $_ARRAYLANG['TXT_KNOWLEDGE_MOST_READ_AMOUNT'],
           'TXT_MOST_READ_SIDEBAR_LENGTH'          => $_ARRAYLANG['TXT_KNOWLEDGE_MOST_READ_SIDEBAR_LENGTH'],
           'TXT_MOST_READ_SIDEBAR_LENGTH_DESCRIPTION' => $_ARRAYLANG['TXT_KNOWLEDGE_MOST_READ_SIDEBAR_LENGTH_DESCRIPTION'],
           'TXT_MOST_READ_TEMPLATE'                => $_ARRAYLANG['TXT_KNOWLEDGE_MOST_READ_TEMPLATE'],
           'TXT_MOST_READ_TEMPLATE_DESCRIPTION'    => $_ARRAYLANG['TXT_KNOWLEDGE_MOST_READ_TEMPLATE_DESCRIPTION']
        ));

        $this->tpl->setVariable(array(
           'COLUMN_NUMBER'                     => $this->settings->get('column_number'),
           'MAX_SUBCATEGORIES'                 => $this->settings->get('max_subcategories'),
           'MAX_RATING'                        => $this->settings->get('max_rating'),
           'BEST_RATED_SIDEBAR_LENGTH'         => $this->settings->get('best_rated_sidebar_length'),
           'BEST_RATED_SIDEBAR_AMOUNT'         => $this->settings->get('best_rated_sidebar_amount'),
           'BEST_RATED_SIDEBAR_TEMPLATE'       => contrexx_raw2xhtml($this->settings->get('best_rated_sidebar_template')),
           'REPLACE_PLACEHOLDERS_CHECKED'      => ($this->getGlobalSetting()) ? "checked=\"checked\"" : '',
           'MOST_READ_SIDEBAR_LENGTH'          => $this->settings->get('most_read_sidebar_length'),
           'MOST_READ_SIDEBAR_AMOUNT'          => $this->settings->get('most_read_sidebar_amount'),
           'MOST_READ_SIDEBAR_TEMPLATE'        => contrexx_raw2xhtml($this->settings->get('most_read_sidebar_template')),
           'MOST_READ_AMOUNT'                  => $this->settings->get('most_read_amount'),
           'BEST_RATED_AMOUNT'                 => $this->settings->get('best_rated_amount')
        ));

        return $this->tpl->get();
    }

    /**
     * Update settings
     *
     * Save the given settings
     */
    private function updateSettings()
    {
        try {
            $this->settings->set("column_number", $_POST['column_number']);
            $this->settings->set("max_subcategories", $_POST['max_subcategories']);
            $this->settings->set("max_rating", $_POST['max_rating']);
            $this->settings->set("best_rated_sidebar_template", $_POST['best_rated_sidebar_template']);
            $this->settings->set("best_rated_sidebar_length", $_POST['best_rated_sidebar_length']);
            $this->settings->set("best_rated_sidebar_amount", $_POST['best_rated_sidebar_amount']);
            $this->settings->set("most_read_sidebar_template", $_POST['most_read_sidebar_template']);
            $this->settings->set("most_read_sidebar_amount", $_POST['most_read_sidebar_amount']);
            $this->settings->set("most_read_sidebar_length", $_POST['most_read_sidebar_length']);
            $this->settings->set("most_read_amount", $_POST['most_read_amount']);
            $this->settings->set("best_rated_amount", $_POST['best_rated_amount']);

            $this->updateGlobalSetting(!empty($_POST['useKnowledgePlaceholders']) ? 1 : 0);
        } catch (DatabaseError $e) {
            global $_ARRAYLANG;
            $this->errorMessage = $_ARRAYLANG['TXT_KNOWLEDGE_ERROR_OVERVIEW'];
               $this->errorMessage .= $e->formatted();
        }
    }

    /**
     * Tidy the tags
     *
     * Call the function that removes unecessary tags.
     * Called through ajax.
     */
    private function tidyTags()
    {
        global $_ARRAYLANG;
        try {
            $this->tags->tidy();
            die(json_encode(array('ok' => $_ARRAYLANG['TXT_KNOWLEDGE_TIDY_TAGS_SUCCESSFUL'])));
        } catch (DatabaseError $e) {
            $this->sendAjaxError($e->formatted());
        }

        die();
    }

    /**
     * Reset the vote statistics
     *
     * Called through ajax.
     */
    private function resetVotes()
    {
        global $_ARRAYLANG;
        try {
            $this->articles->resetVotes();
            die(json_encode(array('ok' => $_ARRAYLANG['TXT_KNOWLEDGE_RESET_VOTES_SUCCESSFUL'])));
        } catch (DatabaseError $e) {
            $this->sendAjaxError($e->formatted());
        }

        die();
    }

    /**
     * Show the placeholder page
     *
     * @global $_ARRAYLANG
     * @global $_CORELANG
     * @return string
     */
    private function settingsPlaceholders()
    {
        global $_ARRAYLANG, $_CORELANG;

        $this->pageTitle = $_ARRAYLANG['TXT_SETTINGS'];
        $this->tpl->loadTemplateFile('module_knowledge_settings_placeholder.html',true,true);
        $this->tpl->setGlobalVariable("MODULE_INDEX", MODULE_INDEX);

        $this->tpl->setVariable(array(
           'TXT_PLACEHOLDERS'      => $_ARRAYLANG['TXT_KNOWLEDGE_PLACEHOLDER'],
           'TXT_PLACEHOLDER_TAG_CLOUD_DESCRIPTION'      => $_ARRAYLANG['TXT_KNOWLEDGE_PLACEHOLDER_TAG_CLOUD_DESCRIPTION'],
           'TXT_PLACEHOLDER_BEST_RATED_DESCRIPTION'     => $_ARRAYLANG['TXT_KNOWLEDGE_PLACEHOLDER_MOST_POPULAR_DESCRIPTION'],
           'TXT_PLACEHOLDER_MOST_READ_DESCRIPTION'     => $_ARRAYLANG['TXT_KNOWLEDGE_PLACEHOLDER_MOST_READ_DESCRIPTION']
        ));

        return $this->tpl->get();
    }
}
