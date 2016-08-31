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
 * Contains the class for article operations
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */

namespace Cx\Modules\Knowledge\Controller;

/**
 * Provide an abstract layer for the articles
 *
 * Provide an abstract layer for the articles, including operations for
 * reading, editing, adding and deleting articles. Also provide some special
 * functions to return the most read or the most popular articles.
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage    module_knowledge
 */
class KnowledgeArticles
{
    public $articles = null;
    /**
     * The basis query.
     *
     * @var string
     */
    private $basequery = "";

    protected $isAllLangsActive = false;

    /**
     * Save the base query
     */
    public function __construct($showAllLangs = false)
    {
        $this->basequery = "SELECT  articles.id as id,
                            articles.active as active,
                            articles.hits as hits,
                            articles.votes as votes,
                            articles.votevalue as value,
                            articles.category as category,
                            articles.date_created as date_created,
                            articles.date_updated as date_updated,
                            content.lang as lang,
                            content.answer as answer,
                            content.question as question
                    FROM `".DBPREFIX."module_knowledge_articles` AS articles
                    INNER JOIN `".DBPREFIX."module_knowledge_article_content`
                    AS content ON articles.id = content.article";

        $this->isAllLangsActive = $showAllLangs;
    }

    /**
     * Get all messages from database
     *
     * Read the messages out of the database but only if they
     * are not already read or if the argument is given true.
     * If an id is given, only read that one from the database.
     * With the query parameter can be an alternative query given, useful
     * for special conditions. In this case the results will be returned instead
     * of being saved in the $articles member variable. Whith the 4h parameter
     * this behaviour can be set implicitly.
     * @param bool $override
     * @param int $lang
     * @param int $id
     * @param string $alt_query
     * @global $objDatabase
     * @throws DatabaseError
     * @return mixed Return an array on success
     */
    public function readArticles($override = true, $lang=0, $id = 0, $alt_query="")
    {
        if ($override === false && isset($this->articles)) {
            // the messages are already read out and override is not given
            return;
        }

        global $objDatabase;

        if (!empty($alt_query)) {
            $query = $alt_query;
        } else {
            $query = $this->basequery;
            if ($this->isAllLangsActive) {
                $query .= " WHERE content.answer!='' AND content.question!=''";
            }
            // if only one article should be read add a where to the query
            if ($id > 0) {
                $id = intval($id);
                if ($this->isAllLangsActive) {
                    $query .= " AND articles.id = ".$id;
                } else {
                    $query .= " WHERE articles.id = ".$id;
                }
            }

            if ($lang > 0) {
                // only get one language
                if ($this->isAllLangsActive) {
                    $query .= " AND lang = ".$lang;
                } else {
                    if ($id > 0) {
                        $query .= " AND lang = ".$lang;
                    } else {
                        $query .= " WHERE lang = ".$lang;
                    }
                }
            }

            // add some order.
            $query .= " ORDER BY sort ASC";
        }

        $objRs = $objDatabase->Execute($query);
        if ($objRs === false) {
            throw new DatabaseError("read articles failed");
        }

        $articles = array();
        while (!$objRs->EOF) {
            $curId = $objRs->fields['id'];
            if (isset($articles[$curId])) {
                $articles[$curId]['content'][$objRs->fields['lang']]['question'] = $objRs->fields['question'];
                $articles[$curId]['content'][$objRs->fields['lang']]['answer'] = $objRs->fields['answer'];
            } else {
                $articles[$curId] = array(
                    'active'        => intval($objRs->fields['active']),
                    'hits'          => intval($objRs->fields['hits']),
                    'votes'         => intval($objRs->fields['votes']),
                    'votevalue'     => intval($objRs->fields['value']),
                    'category'      => intval($objRs->fields['category']),
                    'date_created'  => intval($objRs->fields['date_created']),
                    'date_updated'  => intval($objRs->fields['date_updated']),
                    'content'       => array(
                        $objRs->fields['lang'] => array(
                            'question'      => stripslashes($objRs->fields['question']),
                            'answer'        => stripslashes($objRs->fields['answer'])
                        )
                    )
                );
            }
            $objRs->MoveNext();
        }

        if (empty($alt_query)) {
            $this->articles = $articles;
        } else {
            return $articles;
        }
    }

    /**
     * Get articles by category
     *
     * Return an array with all articles that are assigned
     * to the given category.
     *
     * @param intval $catid
     * @return array
     */
    public function getArticlesByCategory($catid)
    {
        if (!isset($this->articles)) {
            $this->readArticles();
        }

        $arr = array();
        foreach ($this->articles as $id => $article) {
            if ($article['category'] == $catid) {
                $arr[$id] = $article;
            }
        }

        return $arr;
    }

    public function getNewestArticles()
    {
        $query = $this->basequery;

        $query .= " ORDER BY id DESC LIMIT 10";
        return $this->readArticles(true, 0, 0, $query);
    }

    /**
     * Activate an article
     *
     * @param int $id
     * @global $objDatabase
     * @param int $id
     * @throws DatabaseError
     */
    public function activate($id)
    {
        global $objDatabase;

        $id = intval($id);
        $query = "  UPDATE ".DBPREFIX."module_knowledge_articles
                    SET active = 1
                    WHERE id = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to activate article");
        }
    }

    /**
     * Deactivate an article
     *
     * @param int $id
     * @global $objDatabase
     * @param int $id
     * @throws DatabaseError
     */
    public function deactivate($id)
    {
        global $objDatabase;

        $id = intval($id);
        $query = "  UPDATE ".DBPREFIX."module_knowledge_articles
                    SET active = 0
                    WHERE id = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to deactivate article");
        }
    }

    /**
     * Delete a single article
     *
     * @param int $id
     * @global $objDatabase
     * @throws DatabaseError
     */
    public function deleteOneArticle($id)
    {
        global $objDatabase;


        $id = intval($id);
        $query = "  DELETE FROM ".DBPREFIX."module_knowledge_article_content
                    WHERE article = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to delete the content of a article");
        }

        $query = "  DELETE FROM ".DBPREFIX."module_knowledge_articles
                    WHERE id = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to delete a article");
        }
    }

    /**
     * Delete the articles of a whole category
     *
     * @param int $catid
     * @global $objDatabase
     * @throws DatabaseError
     */
    public function deleteArticlesByCategory($catid)
    {
        global $objDatabase;

        $catid = intval($catid);
        $query = "  DELETE FROM ".DBPREFIX."module_knowledge_article_content
                    WHERE article IN (
                        SELECT id FROM ".DBPREFIX."module_knowledge_articles
                        WHERE category = ".$catid."
                        )";
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to delete an article's content");
        }

        $query = "  DELETE FROM ".DBPREFIX."module_knowledge_articles
                    WHERE id = ".$catid;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to delete a article");
        }
    }

    /**
     * Insert a new article
     *
     * @param int $category
     * @param int $active
     * @global $objDatabase
     * @throws DatabaseError
     * @return int
     */
    public function insert($category, $active)
    {
        global $objDatabase;

        $category = intval($category);
        $active = intval($active);

        $query = "  INSERT INTO ".DBPREFIX."module_knowledge_articles
                    (category, active, date_created, date_updated)
                    VALUES
                    (".$category.", ".$active.", ".time().", ".time().")";
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to insert a new article");
        }

        $id = $objDatabase->Insert_ID();
        $this->insertContent($id);
        return $id;
    }

    /**
     * Update article
     *
     * @param int $id
     * @param int $category
     * @param int $active
     * @throws DatabaseError
     * @global $objDatabase
     */
    public function update($id, $category, $active)
    {
        global $objDatabase;

        $id = intval($id);
        $category = intval($category);
        $active = intval($active);

        $query = "  UPDATE ".DBPREFIX."module_knowledge_articles
                    SET     category = ".$category.",
                            active = ".$active.",
                            date_updated = ".time()."
                    WHERE id = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to update the article");
        }

        $this->deleteContent($id);
        $this->insertContent($id);
    }

    /**
     * Add content that is to be inserted
     *
     * @param int $lang
     * @param string $question
     * @param string $answer
     */
    public function addContent($lang, $question, $answer)
    {
        $this->insertContent[] = array(
            'lang' => intval($lang),
            'question' => contrexx_addslashes($question),
            'answer' => contrexx_addslashes($answer)
        );
    }


    /**
     * Count the entries of a category
     *
     * @param int $id
     * @global $objDatabase
     * @throws DatabaseError
     * @return int
     */
    public function countEntriesByCategory($id)
    {
        global $objDatabase;

        $id = intval($id);

        $query = "  SELECT count(id) AS amount
                    FROM ".DBPREFIX."module_knowledge_articles
                    WHERE category = ".$id;
        $objRs = $objDatabase->Execute($query);
        if ($objRs === false) {
            throw new DatabaseError("Error getting amount of entries of a category");
        }

        return intval($objRs->fields['amount']);
    }

    /**
     * Return just one article
     *
     * @param int $id Id of the article to get
     * @return boolean/array
     */
    public function getOneArticle($id)
    {
        $this->readArticles(true, 0, $id);
        $article = array_pop($this->articles);
        if (!isset($article)) {
            return false;
        } else {
            return $article;
        }
    }

    /**
     * Set the sort value of an article
     *
     * @param int $id
     * @param int position
     * @throws DatabaseError
     * @global $objDatabase
     */
    public function setSort($id, $position)
    {
        global $objDatabase;

        $id = intval($id);
        $position = intval($position);

        $query = "  UPDATE ".DBPREFIX."module_knowledge_articles
                    SET sort = ".$position."
                    WHERE id = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("error sorting the article ".$id);
        }
    }

    /**
     * Hit an article
     *
     * If an article is viewed, increment the hit count value.
     * @param int $id
     * @throws DatabaseError
     * @global $objDatabase
     */
    public function hit($id)
    {
        global $objDatabase;

        $id = intval($id);

        $query = "  UPDATE ".DBPREFIX."module_knowledge_articles
                    SET hits = hits + 1
                    WHERE id = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("error 'hitting' an article ");
        }
    }

    /**
     * Save a user's vote
     *
     * @param int $value
     * @throws DatabaseError
     * @global $objDatabase
     */
    public function vote($id, $value)
    {
        global $objDatabase;

        $value = intval($value);
        $id = intval($id);

        $query = "  UPDATE ".DBPREFIX."module_knowledge_articles
                    SET votes = votes + 1,
                        votevalue = votevalue + ".$value."
                    WHERE id = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("error voting an article");
        }
    }

    /**
     * Return the most read items
     *
     * Instead of doing a new request PHP could do the job itself, but
     * for performance reasons I let MySQL do it.
     * @param int $lang
     * @param int $amount
     * @return array
     */
    public function getMostRead($lang=1, $amount=5)
    {
        $amount = intval($amount);
        $lang = intval($lang);

        $query = $this->basequery;
        if ($this->isAllLangsActive) {
            $query .= " WHERE content.answer!='' AND content.question!=''".
                ($lang ? " AND lang=".intval($lang) : '').
                " ORDER BY hits DESC LIMIT ".$amount;
        } else {
            $query .= " WHERE lang = ".$lang." ORDER BY hits DESC LIMIT ".$amount;
        }

        $articles = $this->readArticles(true, 0, 0, $query);
        return $articles;
    }

    /**
     * Return the best reted articles
     *
     * @param int $lang
     * @param int $amount
     * @return array
     */
    public function getBestRated($lang=0, $amount=5)
    {
        $amount = intval($amount);
        $lang = intval($lang);

        // Unfortunately we cannot use the basequery here
        $query = "  SELECT  articles.id AS id,
                            articles.active AS active,
                            articles.hits AS hits,
                            articles.votes AS votes,
                            articles.votevalue AS value,
                            articles.category AS category,
                            articles.date_created AS date_created,
                            articles.date_updated AS date_updated,
                            content.lang AS lang,
                            content.answer AS answer,
                            content.question AS question,
                            rating
                    FROM (
                        SELECT * , (votevalue / votes) AS rating
                        FROM `".DBPREFIX."module_knowledge_articles`
                        ) AS articles
                    INNER JOIN  `".DBPREFIX."module_knowledge_article_content`
                                AS content ON articles.id = content.article
                    WHERE ";
        if ($this->isAllLangsActive) {
            $query .= "content.answer!='' AND content.question!=''".
            ($lang ? " AND lang=".intval($lang) : '');
        } else {
            $query .= " lang = ".$lang;
        }
        $query .= " ORDER BY rating DESC
                    LIMIT ".$amount;
        $articles = $this->readArticles(true, 0, 0, $query);
        return $articles;
    }

    /**
     * Set all votes back to 0
     *
     * @global $objDatabase
     * @throws DatabaseError
     */
    public function resetVotes()
    {
        global $objDatabase;

        $query = "  UPDATE ".DBPREFIX."module_knowledge_articles
                    SET votes = 0, votevalue = 0";
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("failed to reset the votes");
        }

    }

    /**
     * Insert the content of an article
     *
     * @param int $id
     * @global $objDatabase
     * @throws DatabaseError
     */
    private function insertContent($id)
    {
        global $objDatabase;

        foreach ($this->insertContent as $values) {
            $lang = $values['lang'];
            $question = $values['question'];
            $answer = $values['answer'];

            $query = " INSERT INTO ".DBPREFIX."module_knowledge_article_content
                           (article, lang, question, answer)
                       VALUES
                           (".$id.", ".$lang.", '".$question."', '".$answer."')";
            if ($objDatabase->Execute($query) === false) {
                throw new DatabaseError("inserting category content failed");
            }
        }
    }

    /**
     * Delete the content of an article
     *
     * @param int $id
     * @global $objDatabase
     * @throws DatabaseError
     */
    private function deleteContent($id)
    {
        global $objDatabase;

        $id = intval($id);

        $query = "  DELETE FROM ".DBPREFIX."module_knowledge_article_content
                    WHERE article = ".$id;
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("deleting article content failed");
        }
    }
}
