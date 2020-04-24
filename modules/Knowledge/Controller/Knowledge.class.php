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
 * Knowledge
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */

namespace Cx\Modules\Knowledge\Controller;

/**
 * Knowledge
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */
class Knowledge extends KnowledgeLibrary
{
    /**
     * The template object
     *
     * @var object
     */
    private $tpl;

    public $pageTitle;

    /**
    * Call parent-constructor, set language id and create local template-object
    *
    * @global $_LANGID
    */
    public function __construct($pageContent)
    {
        global $_LANGID;

        parent::__construct();

        $this->languageId = intval($_LANGID);

        $this->tpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->tpl);
        $this->tpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->tpl->setTemplate($pageContent);
        $this->tpl->setGlobalVariable("MODULE_INDEX", MODULE_INDEX);
    }

    /**
     * Shows the page according to the requested action
     *
     * @global $_LANGID
     * @return string Requested Page
     */
    public function getPage()
    {
        global $_LANGID;

        \JS::activate('prototype');
        \JS::registerJS('modules/Knowledge/View/Script/fix_prototype.js');
        \JS::activate('scriptaculous');
        \JS::registerJS('modules/Knowledge/View/Script/rating.js');
        \JS::registerJS('modules/Knowledge/View/Script/search.js');
        \JS::registerJS('modules/Knowledge/View/Script/slider.js');
        \JS::activate('cx');

        if (!isset($_GET['act'])) {
            $_GET['act'] = "";
        }

        /**
         * The ajax stuff
         */
        if ($_GET['act'] == "liveSearch") {
           $search = new Search();
           $search->performSearch();
           die();
        } elseif ($_GET['act'] == "hitArticle") {
            $this->hitArticle();
// TODO: There is nothing to be broken break here!  What's the point?
//            break;
        } elseif ($_GET['act'] == "rate") {
            $this->rate();
            die();
        }

        if (!isset($_GET['cmd'])) {
            $_GET['cmd'] = '';
        }

        /**
         * Normal stuff
         */
        switch ($_GET['cmd']) {
            case 'category':
                $this->category();
                break;
            case 'article':
                $this->article();
                break;
            case 'search':
                $this->search();
                break;
            case 'start':
            default:
                if ($_GET['act'] == "mostRead") {
                    $this->mostRead();
                } elseif ($_GET['act'] == "bestRated") {
                    $this->bestRated();
                } else {
                    if (isset($_GET['id'])) {
                        $this->category();
                    } elseif (isset($_GET['tid'])) {
                        $this->showTag();
                    } else {
                       $this->frontPage();
                    }
                }
                break;
        }

        return $this->tpl->get();
    }

    /**
     * The frontpage
     *
     * @global $_LANGID
     * @global $_ARRAYLANG
     */
    private function frontPage()
    {
        global $_LANGID, $_ARRAYLANG;
        static $e;

        try {
            $this->categories->readCategories();
            $mostRead = $this->articles->getMostRead($this->getLangId(), $this->settings->get('most_read_sidebar_amount'));
            $bestRated = $this->articles->getBestRated($this->getLangId(), $this->settings->get('best_rated_sidebar_amount'));
        } catch (DatabaseError $e) {
            return;
        }

        $this->tpl->setGlobalVariable(array(
            'TXT_MORE'      => $_ARRAYLANG['TXT_KNOWLEDGE_MORE'],
            'SEARCH_HASH'   => rand().time(), // this is needed because firefox always shows an annoying
                                             // dropdown with already entered strings in the searchbox
            'TXT_SEARCH'    => $_ARRAYLANG['TXT_KNOWLEDGE_SEARCH']
        ));

        $rowcounter = 1;
        foreach ($this->categories->getCategoriesByParent(0) as $category) {
            if ($category['active']) {
                $subcats = $this->categories->getCategoriesByParent($category['id']);
                $i = 1;
                while ($i < count($subcats) && $i <= $this->settings->get('max_subcategories')) {
                    $curSubCat = $subcats[$i];
                    if ($curSubCat['active']) {
                        $this->tpl->setVariable(array(
                           "SUBCAT_ID"         => $curSubCat['id'],
                           "SUBCAT_NAME"       => $curSubCat['content'][$_LANGID]['name']
                        ));
                        $this->tpl->parse("subcat");
                    }
                    $i++;
                }

                $this->tpl->setVariable(array(
                   "CATEGORY_ID"       => $category['id'],
                   "CATEGORY_TITLE"    => $category['content'][$_LANGID]['name']
                ));
                $this->tpl->parse("category");

                // make a new row if wanted
                if ($rowcounter % $this->settings->get('column_number') == 0) {
                    $this->tpl->parse("row");
                }
            }
            $rowcounter++;
        }
        $this->tpl->parse("overview");

        /**
         * This counter is needed so every answer does have a unique id
         */
        $counter = 0;
        $counter = $this->parseArticleList($mostRead, $_ARRAYLANG['TXT_KNOWLEDGE_MOST_READ_ARTICLES'], $counter, "index.php?section=Knowledge".MODULE_INDEX."&amp;act=mostRead");
        $this->parseArticleList($bestRated, $_ARRAYLANG['TXT_KNOWLEDGE_BEST_RATED_ARTICLES'], $counter, "index.php?section=Knowledge".MODULE_INDEX."&amp;act=bestRated");

        \JS::activate('prototype');
        \JS::activate('scriptaculous');
        \JS::registerJS('modules/Knowledge/View/Script/rating.js');
        \JS::registerJS('modules/Knowledge/View/Script/search.js');
        \JS::registerJS('modules/Knowledge/View/Script/slider.js');
        \JS::activate('cx');
    }

    /**
     * Show a category
     *
     * @param int $id
     * @global $_LANGID
     * @global $_ARRAYLANG
     */
    private function category($id=null)
    {
        global $_LANGID, $_ARRAYLANG;
        static $e;

        try {
           $this->categories->readCategories();
           $this->articles->readArticles();
        } catch (DatabaseError $e) {
            return;
        }

        if (empty($id)) {
            $id = intval($_GET['id']);
        }

      //JS::activate('prototype');

        $category = &$this->categories->categories[$id];
        $this->tpl->setVariable(array(
           "CATEGORY_TITLE"    => $category['content'][$_LANGID]['name'],
           'SEARCH_HASH'   => rand().time(), // this is needed because firefox always shows an annoying
                                             // dropdown with already entered strings in the searchbox
           'TXT_SEARCH'    => $_ARRAYLANG['TXT_KNOWLEDGE_SEARCH']
        ));

        // check if there are subcategories and parse them if yes
        foreach ($this->categories->categories as $catId => $catVal) {
            if ($catVal['parent'] == $id) {
                // this is a subcategory
                $this->tpl->setVariable(array(
                   "SUBCATEGORY_ID"        => $catId,
                   "SUBCATEGORY_TITLE"     => $catVal['content'][$_LANGID]['name']
                ));
                $this->tpl->parse("subcategory");
            }
        }
        $this->tpl->parse("categories");

        // lets parse articles because there are not sub categories
        $articles = $this->articles->getArticlesByCategory($id);
        $this->parseArticleList($articles, $_ARRAYLANG['TXT_KNOWLEDGE_ARTICLES']);

        $this->showCrumbtrail($id);

        \JS::activate('prototype');
        \JS::activate('scriptaculous');
        \JS::registerJS('modules/Knowledge/View/Script/rating.js');
        \JS::registerJS('modules/Knowledge/View/Script/search.js');
        \JS::registerJS('modules/Knowledge/View/Script/slider.js');
        \JS::activate('cx');
    }

    /**
     * Show the most read articles
     *
     * @global $_ARRAYLANG
     */
    private function mostRead()
    {
        global $_ARRAYLANG;

        $articles = $this->articles->getMostRead($this->getLangId());
        $this->parseArticleList($articles, $_ARRAYLANG['TXT_KNOWLEDGE_MOST_READ_ARTICLES'], 0);
    }

    /**
     * Show the best rated articles
     *
     * @global $_ARRAYLANG
     */
    private function bestRated()
    {
        global $_ARRAYLANG;

        $articles = $this->articles->getBestRated($this->getLangId());
        $this->parseArticleList($articles, $_ARRAYLANG['TXT_KNOWLEDGE_BEST_RATED_ARTICLES'], 0);
    }

    /**
     * Show an article
     *
     * @global $_LANGID
     * @global $_ARRAYLANG
     */
    private function article()
    {
        global $_LANGID, $_ARRAYLANG;
        static $e;

        if (empty($_GET['id'])) {
            return;
        } else {
            $id = intval($_GET['id']);
        }

        try {
            $this->articles->readArticles(false, $this->getLangId(), $id);
            $this->categories->readCategories();
            $this->articles->hit($id);
            $tags = $this->tags->getByArticle($id, $this->getLangId());
        } catch (DatabaseError $e) {
            return;
        }
        $article = $this->articles->articles[$id];
        $average = ($article['votes'] > 0) ? $article['votevalue'] / $article['votes'] : 0;
        $amount = $article['votes'];

        $this->tpl->setVariable(array(
           "TXT_RATING"    => $_ARRAYLANG['TXT_KNOWLEDGE_YOUR_RATING'],
           "TXT_TAGS"      => $_ARRAYLANG['TXT_KNOWLEDGE_TAGS'],
           "TXT_HITS"      => $_ARRAYLANG['TXT_KNOWLEDGE_HITS'],
           "TXT_CREATED"   => $_ARRAYLANG['TXT_KNOWLEDGE_CREATED'],
           "TXT_LAST_CHANGE"   => $_ARRAYLANG['TXT_KNOWLEDGE_UPDATED'],
           "TXT_AMOUNT_OF_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_AMOUNT_OF_RATING'],
           "TXT_AVERAGE_RATING"     => $_ARRAYLANG['TXT_KNOWLEDGE_AVERAGE_RATING'],

           "ARTICLEID"     => $id,
           "QUESTION"      => $article['content'][$_LANGID]['question'],
           "ANSWER"        => $article['content'][$_LANGID]['answer'],
           "AVERAGE"       => round($average, 2),
           "AMOUNT_OF_RATING" => $amount,

           "MAX_RATING"    => $this->settings->get("max_rating"),
           "LOCKED"        => $this->checkLocking($id),
           "HITS"          => $article['hits'],

           "DATE_CREATED"  => date(ASCMS_DATE_FORMAT_DATE, $article['date_created']),
           "DATE_UPDATED"  => date(ASCMS_DATE_FORMAT_DATE, $article['date_updated'])
        ));

        $this->showCrumbtrail($article['category']);

        $this->parseTags($tags);

        $this->pageTitle = $article['content'][$_LANGID]['question'];

        \JS::activate('prototype');
        \JS::activate('scriptaculous');
        \JS::registerJS('modules/Knowledge/View/Script/rating.js');
    }

    /**
     * Get the page title
     *
     * @return string
     */
    public function getPageTitle()
    {
        $this->article();
        return $this->pageTitle;
    }

    /**
     * Show the tags of an article
     *
     * @param array $tags
     * @global $_ARRAYLANG
     */
    private function parseTags($tags)
    {
        global $_ARRAYLANG;

        $tag_keys = array_keys($tags);
        foreach ($tags as $id => $tag) {
            $this->tpl->setVariable(array(
               "TAGID"     => $id,
               "TAG"       => $tag['name']
            ));
            if ($tag_keys[count($tag_keys)-1] == $id) {
                $this->tpl->parse("lasttag");
            } else {
               $this->tpl->parse("tag");
            }
        }
        $this->tpl->setVariable("TXT_TAGS", $_ARRAYLANG['TXT_TAGS']);
        $this->tpl->parse("tags");
    }

    /**
     * Hit article
     *
     * Increment the hit counter of an article.
     * Called through ajax.
     */
    private function hitArticle()
    {
        static $e;

        $id = intval($_GET['id']);
        try {
            $this->articles->hit($id);
        } catch (DatabaseError $e) {
            return;
        }
        die();
    }

    /**
     * Show all articles of a tag
     *
     * @global $_LANGID
     * @global $_ARRAYLANG
     */
    private function showTag()
    {
        global $_LANGID, $_ARRAYLANG;

        if (empty($_GET['tid'])) {
            return;
        } else {
            $id = $_GET['tid'];
        }
        try {
            $tag = $this->tags->getArticlesByTag($id);
            $this->articles->readArticles();
        } catch (DatabaseError $e) {
            echo $e->plain();
            return;
        }

        if (count($tag['articles'])) {
            foreach ($tag['articles'] as $articleid) {
                $article = $this->articles->articles[$articleid];
                if ($article['active']) {

                    if ($this->isAllLangsActive()) {
                        foreach ($article['content'] as $lang_id => $content) {

                            $average = ($article['votes'] > 0) ? $article['votevalue'] / $article['votes'] : 0;
                            $amount = $article['votes'];
                            $this->tpl->setVariable(array(
                                "ARTICLE_ID" => $articleid,
                                "QUESTION" => $content['question'],
                                "ANSWER" => $content['answer'],
                                'LANG_ID' => $lang_id,
                                "AVERAGE" => round($average, 2),
                                "TXT_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_YOUR_RATING'],
                                "AMOUNT_OF_RATING" => $amount,
                                "MAX_RATING" => $this->settings->get("max_rating"),
                                "LOCKED" => $this->checkLocking($articleid),
                                "TXT_TAGS" => $_ARRAYLANG['TXT_KNOWLEDGE_TAGS'],
                                "TXT_HITS" => $_ARRAYLANG['TXT_KNOWLEDGE_HITS'],
                                "TXT_CREATED" => $_ARRAYLANG['TXT_KNOWLEDGE_CREATED'],
                                "TXT_LAST_CHANGE" => $_ARRAYLANG['TXT_KNOWLEDGE_UPDATED'],
                                "TXT_AMOUNT_OF_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_AMOUNT_OF_RATING'],
                                "TXT_AVERAGE_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_AVERAGE_RATING'],
                            ));

                            try {
                                $tags = $this->tags->getByArticle($articleid, NULL);
                            } catch (DatabaseError $e) {
                                // nothing yet
                            }

                            $this->parseTags($tags);

                            $this->tpl->parse("article");
                        }
                    } else {
                        $average = ($article['votes'] > 0) ? $article['votevalue'] / $article['votes'] : 0;
                        $amount = $article['votes'];
                        $this->tpl->setVariable(array(
                           "ARTICLE_ID"    => $articleid,
                           "QUESTION"      => $article['content'][$_LANGID]['question'],
                           "ANSWER"        => $article['content'][$_LANGID]['answer'],
                           "AVERAGE"       => round($average, 2),
                           "TXT_RATING"    => $_ARRAYLANG['TXT_KNOWLEDGE_YOUR_RATING'],
                           "AMOUNT_OF_RATING" => $amount,
                           "MAX_RATING"    => $this->settings->get("max_rating"),
                           "LOCKED"        => $this->checkLocking($articleid),
                           "TXT_TAGS"      => $_ARRAYLANG['TXT_KNOWLEDGE_TAGS'],
                           "TXT_HITS"      => $_ARRAYLANG['TXT_KNOWLEDGE_HITS'],
                           "TXT_CREATED"   => $_ARRAYLANG['TXT_KNOWLEDGE_CREATED'],
                           "TXT_LAST_CHANGE"   => $_ARRAYLANG['TXT_KNOWLEDGE_UPDATED'],
                           "TXT_AMOUNT_OF_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_AMOUNT_OF_RATING'],
                           "TXT_AVERAGE_RATING"     => $_ARRAYLANG['TXT_KNOWLEDGE_AVERAGE_RATING'],
                        ));

                        try {
                            $tags = $this->tags->getByArticle($articleid, $_LANGID);
                        } catch (DatabaseError $e) {
                            // nothing yet
                        }

                        $this->parseTags($tags);

                        $this->tpl->parse("article");
                    }
                }
            }
            $this->tpl->setVariable(array(
                "TXT_ARTICLELIST"   => $_ARRAYLANG['TXT_TAG'].": ".$tag['name'],
                "TXT_SEARCH"        => $_ARRAYLANG['TXT_KNOWLEDGE_SEARCH']
            ));
            $this->tpl->parse("articles");
        }
    }

    /**
     * Show the search results
     *
     * @global $_ARRAYLANG
     * @global $_LANGID
     */
    private function search()
    {
        global $_ARRAYLANG, $_LANGID;
        static $e;

        $searchterm = !empty($_GET['term']) ? $_GET['term'] : "";

        if (!empty($searchterm)) {
            $search = new SearchKnowledge();
            $results = $search->search($searchterm);

            try {
                $this->articles->readArticles();
            } catch (DatabaseError $e) {
                // nothing yet
            }

            //$this->parseArticleList($results, $_ARRAYLANG['TXT_SEARCH_RESULTS']);
            // this can currently not be done with the parseArticleList function because the
            // search engine only returns ids and not the whole content array
            if ($this->isAllLangsActive()) {
                foreach ($results as $result) {
                    $articleid = $result['id'];
                    $article = $this->articles->articles[$articleid];
                    if ($article['active']) {

                        $lang_id = $result['lang'];
                        $content = $article['content'][$lang_id];

                        $average = ($article['votes'] > 0) ? $article['votevalue'] / $article['votes'] : 0;
                        $amount = $article['votes'];
                        $this->tpl->setVariable(array(
                            "ARTICLE_ID" => $articleid,
                            "QUESTION" => $content['question'],
                            "ANSWER" => $content['answer'],
                            'LANG_ID' => $lang_id,

                            "AVERAGE" => round($average, 2),
                            "TXT_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_YOUR_RATING'],
                            "AMOUNT_OF_RATING" => $amount,
                            "HITS" => $article['hits'],
                            "TXT_AMOUNT_OF_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_AMOUNT_OF_RATING'],
                            "TXT_AVERAGE_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_AVERAGE_RATING'],
                            "TXT_HITS" => $_ARRAYLANG['TXT_KNOWLEDGE_HITS'],
                            "TXT_CREATED" => $_ARRAYLANG['TXT_KNOWLEDGE_CREATED'],
                            "TXT_LAST_CHANGE" => $_ARRAYLANG['TXT_KNOWLEDGE_UPDATED'],
                            "TXT_TAGS" => $_ARRAYLANG['TXT_KNOWLEDGE_TAGS'],
                            "MAX_RATING" => $this->settings->get("max_rating"),
                            "LOCKED" => $this->checkLocking($articleid),
                            "DATE_CREATED" => date(ASCMS_DATE_FORMAT_DATE, $article['date_created']),
                            "DATE_UPDATED" => date(ASCMS_DATE_FORMAT_DATE, $article['date_updated'])
                        ));

                        try {
                            $tags = $this->tags->getByArticle($articleid, $lang_id);
                        } catch (DatabaseError $e) {
                            // nothing yet
                        }

                        $this->parseTags($tags);

                        $this->tpl->parse("article");
                    }
                }
            } else {
                foreach ($results as $article) {
                    $articleid = $article['id'];
                    $article = $this->articles->articles[$articleid];
                    if ($article['active']) {
                       $average = ($article['votes'] > 0) ? $article['votevalue'] / $article['votes'] : 0;
                       $amount = $article['votes'];
                       $this->tpl->setVariable(array(
                           "ARTICLE_ID"    => $articleid,
                           "QUESTION"      => $article['content'][$_LANGID]['question'],
                           "ANSWER"        => $article['content'][$_LANGID]['answer'],
                           "AVERAGE"       => round($average, 2),
                           "TXT_RATING"    => $_ARRAYLANG['TXT_KNOWLEDGE_YOUR_RATING'],
                           "AMOUNT_OF_RATING" => $amount,
                           "HITS"          => $article['hits'],
                           "TXT_AMOUNT_OF_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_AMOUNT_OF_RATING'],
                           "TXT_AVERAGE_RATING"     => $_ARRAYLANG['TXT_KNOWLEDGE_AVERAGE_RATING'],
                           "TXT_HITS"      => $_ARRAYLANG['TXT_KNOWLEDGE_HITS'],
                           "TXT_CREATED"   => $_ARRAYLANG['TXT_KNOWLEDGE_CREATED'],
                           "TXT_LAST_CHANGE"   => $_ARRAYLANG['TXT_KNOWLEDGE_UPDATED'],
                           "TXT_TAGS"      => $_ARRAYLANG['TXT_KNOWLEDGE_TAGS'],
                           "MAX_RATING"    => $this->settings->get("max_rating"),
                           "LOCKED"        => $this->checkLocking($articleid),
                           "DATE_CREATED"  => date(ASCMS_DATE_FORMAT_DATE, $article['date_created']),
                           "DATE_UPDATED"  => date(ASCMS_DATE_FORMAT_DATE, $article['date_updated'])
                       ));

                       try {
                           $tags = $this->tags->getByArticle($articleid, $_LANGID);
                       } catch (DatabaseError $e) {
                           // nothing yet
                       }
                       $this->parseTags($tags);
                       $this->tpl->parse("article");
                    }
                }
            }
        }

        $this->tpl->setVariable(array(
                "TXT_ARTICLELIST"   => str_replace(array("%WORD%", "%AMOUNT%"), array(stripslashes($searchterm), ((count($results)) == 0) ? $_ARRAYLANG['TXT_SEARCH_NONE'] : count($results)), $_ARRAYLANG['TXT_SEARCH_RESULTS']),
                "TXT_SEARCH"        => $_ARRAYLANG['TXT_KNOWLEDGE_SEARCH'],
                "TXT_SEARCH_INPUT"  => $searchterm
         ));

        \JS::activate("prototype");
        \JS::activate("scriptaculous");
        \JS::registerJS("modules/Knowledge/View/Script/rating.js");
        \JS::registerJS("modules/Knowledge/View/Script/search.js");
        \JS::registerJS("modules/Knowledge/View/Script/slider.js");
        \JS::activate('cx');
    }

    /**
     * Show an article list
     *
     * @param array $articles
     * @param string $title
     * @global $_ARRAYLANG
     * @global $_LANGID
     * @return int
     */
    private function parseArticleList($articles, $title, $counter=0, $url="")
    {
        global $_ARRAYLANG, $_LANGID;
        static $e;

        if (count($articles)) {
            foreach ($articles as $articleKey => $article) {
                if ($article['active']) {
                    if ($this->isAllLangsActive()) {
                        foreach ($article['content'] as $lang_id => $content) {

                            $average = ($article['votes'] > 0) ? $article['votevalue'] / $article['votes'] : 0;
                            $amount = $article['votes'];
                            $this->tpl->setVariable(array(
                                "ARTICLE_ID" => $articleKey,
                                "COUNTER" => $counter++,

                                "TXT_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_YOUR_RATING'],
                                "TXT_TAGS" => $_ARRAYLANG['TXT_KNOWLEDGE_TAGS'],
                                "TXT_HITS" => $_ARRAYLANG['TXT_KNOWLEDGE_HITS'],
                                "TXT_CREATED" => $_ARRAYLANG['TXT_KNOWLEDGE_CREATED'],
                                "TXT_LAST_CHANGE" => $_ARRAYLANG['TXT_KNOWLEDGE_UPDATED'],
                                "TXT_AMOUNT_OF_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_AMOUNT_OF_RATING'],
                                "TXT_AVERAGE_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_AVERAGE_RATING'],

                                "QUESTION" => $content['question'],
                                "ANSWER" => $content['answer'],
                                'LANG_ID' => $lang_id,

                                "AVERAGE" => round($average, 2),
                                "AMOUNT_OF_RATING" => $amount,

                                "MAX_RATING" => $this->settings->get("max_rating"),
                                "LOCKED" => $this->checkLocking($articleKey),
                                "HITS" => $article['hits'],

                                "DATE_CREATED" => date(ASCMS_DATE_FORMAT_DATE, $article['date_created']),
                                "DATE_UPDATED" => date(ASCMS_DATE_FORMAT_DATE, $article['date_updated'])
                            ));

                            try {
                                $tags = $this->tags->getByArticle($articleKey, NULL);
                            } catch (DatabaseError $e) {
                                // nothing yet
                            }


                            $this->parseTags($tags);
                            $this->tpl->parse("article");
                        }
                    } else {
                        $average = ($article['votes'] > 0) ? $article['votevalue'] / $article['votes'] : 0;
                        $amount = $article['votes'];
                        $this->tpl->setVariable(array(
                           "ARTICLE_ID"    => $articleKey,
                           "COUNTER"       => $counter++,

                           "TXT_RATING"    => $_ARRAYLANG['TXT_KNOWLEDGE_YOUR_RATING'],
                           "TXT_TAGS"      => $_ARRAYLANG['TXT_KNOWLEDGE_TAGS'],
                           "TXT_HITS"      => $_ARRAYLANG['TXT_KNOWLEDGE_HITS'],
                           "TXT_CREATED"   => $_ARRAYLANG['TXT_KNOWLEDGE_CREATED'],
                           "TXT_LAST_CHANGE"   => $_ARRAYLANG['TXT_KNOWLEDGE_UPDATED'],
                           "TXT_AMOUNT_OF_RATING" => $_ARRAYLANG['TXT_KNOWLEDGE_AMOUNT_OF_RATING'],
                           "TXT_AVERAGE_RATING"     => $_ARRAYLANG['TXT_KNOWLEDGE_AVERAGE_RATING'],

                           "QUESTION"      => $article['content'][$_LANGID]['question'],
                           "ANSWER"        => $article['content'][$_LANGID]['answer'],
                           "AVERAGE"       => round($average, 2),
                           "AMOUNT_OF_RATING" => $amount,

                           "MAX_RATING"    => $this->settings->get("max_rating"),
                           "LOCKED"        => $this->checkLocking($articleKey),
                           "HITS"          => $article['hits'],

                           "DATE_CREATED"  => date(ASCMS_DATE_FORMAT_DATE, $article['date_created']),
                           "DATE_UPDATED"  => date(ASCMS_DATE_FORMAT_DATE, $article['date_updated'])
                        ));

                        try {
                            $tags = $this->tags->getByArticle($articleKey, $_LANGID);
                        } catch (DatabaseError $e) {
                            // nothing yet
                        }


                        $this->parseTags($tags);
                        $this->tpl->parse("article");
                    }
                }
            }
            $this->tpl->setVariable("TXT_ARTICLELIST", $title);

            if (!empty($url)) {
                        $title = "<a href=\"".$url."\" >".$title."</a>";
            }
            $this->tpl->parse("articles");
        }

        return $counter;
    }

    /**
     * Show the crumbtrail path
     *
     * @global $_LANGID
     * @global $_ARRAYLANG
     * @param int $id
     */
    private function showCrumbtrail($id)
    {
        global $_LANGID, $_ARRAYLANG;

        $this->tpl->setVariable(array(
            "TXT_START"     => $_ARRAYLANG['TXT_START']
        ));

        /**
         * Crumbtrail
         */
        $catPath = $this->getCatPath($id);
        foreach ($catPath as $pathElem) {
            $this->tpl->setVariable(array(
               "CRUMB"     => $this->categories->categories[$pathElem]['content'][$_LANGID]['name'],
               "ID"        => $pathElem
            ));
            $this->tpl->parse("crumb");
        }
        $this->tpl->parse("crumbtrail");
    }

    /**
     * Get Category Path
     *
     * Get a liste of all preceeding categories for a
     * crumb path
     * @param int $id
     * @return array
     */
    private function getCatPath($id)
    {
        $this->catPath = array();
        $this->getCatPathR($this->categories->categoryTree, $id);

        return array_reverse($this->catPath);
    }

    /**
     * Recursive function to generate the crumb path list
     *
     * @param array $arr
     * @param int $id
     * @return bool
     */
    private function getCatPathR($arr, $id)
    {

        foreach ($arr as $key => $subcats) {
            if ($key == $id) {
                // this is the current category
                $this->catPath[] = $key;
                return true;
            }
            if ($subcats) {
                if ($this->getCatPathR($subcats, $id)) {
                    // the current category is within this category
                    $this->catPath[] = $key;
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Rate an article
     *
     * Called through ajax
     */
    private function rate()
    {
        $id = intval($_POST['id']);

        $rated = intval($_POST['rated']);
        if (!isset($_COOKIE['knowledge_rating_'.$id])) {
            try {
                $this->articles->vote($id, $rated);
            } catch (DatabaseError $e) {
                die($e->plain());
            }
        }
        die();
    }

    /**
     * Return string for javascript if the cookie is set
     */
    private function checkLocking($id)
    {
        if (isset($_COOKIE['knowledge_rating_'.$id])) {
            return "true";
        } else {
            return "false";
        }
    }

}

?>
