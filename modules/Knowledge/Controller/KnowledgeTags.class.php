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
 * Contains the class that provides the tag operations
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */

namespace Cx\Modules\Knowledge\Controller;

/**
 * Provide all database operations for the tags
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author Stefan Heinemann <sh@comvation.com>
 * @package     cloudrexx
 * @subpackage  module_knowledge
 */
class KnowledgeTags
{
    /**
     * The tags
     *
     * @var array
     */
    private $tags = array();

    /**
     * Wrapper function for getAllOrderAlphabetically()
     *
     * @return array
     */
    public function getAll($lang, $inUseOnly = false)
    {
        return $this->getAllOrderAlphabetically($lang, $inUseOnly);
    }

    /**
     * Return all tags ordered by popularity
     *
     * Return all available tags for the current language and order
     * it by their popularity.
     * @param $lang
     * @global $objDatabase
     * @return array
     */
    public function getAllOrderByPopularity($lang, $inUseOnly = false)
    {
        if (count($this->tags) != 0) {
            return $this->tags;
        }

        global $objDatabase;

        $lang = intval($lang);

        $query = "  SELECT
                        tags.id AS id,
                        tags.name AS name,
                        count( tags_articles.article ) AS popularity
                    FROM ".DBPREFIX."module_knowledge_tags AS tags
                    ".($inUseOnly ? "INNER JOIN" : "LEFT OUTER JOIN")." ".DBPREFIX."module_knowledge_tags_articles AS tags_articles
                    ON tags.id = tags_articles.tag
                    WHERE tags.lang = ".$lang."
                    GROUP BY tags.id
                    ORDER BY popularity DESC";
        $rs = $objDatabase->Execute($query);
        if ($rs === false) {
            throw new DatabaseError("error getting all tags");
        }
        $arr = array();
        if ($rs->RecordCount()) {
            while (!$rs->EOF) {
                $popularity = $rs->fields['popularity'];
                $arr[] = array(
                    "id"    => $rs->fields['id'],
                    "name"  => $rs->fields['name'],
                    "popularity" => ($popularity == 0) ? 1 : $popularity);
                $rs->MoveNext();
            }
        }

        return $arr;
    }

    /**
     * Return all Tags ordered alphabetically
     *
     * @param int $lang
     * @throws DatabaseError
     * @global $objDatabase
     * @return array
     */
    public function getAllOrderAlphabetically($lang, $inUseOnly = false)
    {
        if (count($this->tags) != 0) {
            return $this->tags;
        }

        global $objDatabase;

        $lang = intval($lang);
        $query = "  SELECT  tags.id AS id,
                            tags.name AS name,
                            count( tags_articles.article ) AS popularity
                    FROM ".DBPREFIX."module_knowledge_tags AS tags
                    ".($inUseOnly ? "INNER JOIN" : "LEFT OUTER JOIN")." ".DBPREFIX."module_knowledge_tags_articles AS tags_articles
                    ON tags.id = tags_articles.tag
                    WHERE tags.lang = ".$lang."
                    GROUP BY tags.id
                    ORDER BY name";
        $rs = $objDatabase->Execute($query);
        if ($rs === false) {
            throw new DatabaseError("error getting all tags");
        }
        $arr = array();
        if ($rs->RecordCount()) {
            while (!$rs->EOF) {
                $popularity = $rs->fields['popularity'];
                $arr[] = array(
                    "id"    => $rs->fields['id'],
                    "name"  => $rs->fields['name'],
                    "popularity" => ($popularity == 0) ? 1 : $popularity
                );
                $rs->MoveNext();
            }
        }

        return $arr;
    }

    /**
     * Get tags by article id
     *
     * Warning: returns any tag no matter what language
     * @param int $id
     * @param int $lang
     * @global $objDatabase
     * @throws DatabaseError
     * @return array
     */
    public function getByArticle($id, $lang=0)
    {
        global $objDatabase;

        $id = intval($id);

        $query = "  SELECT  tags.id as id,
                            tags.name as name,
                            tags.lang as lang
                    FROM `".DBPREFIX."module_knowledge_tags_articles` as relation
                    INNER JOIN `".DBPREFIX."module_knowledge_tags` as tags
                    ON relation.tag = tags.id
                    WHERE relation.article = ".$id;
        if ($lang != 0) {
            $query .= " AND lang = ".intval($lang);
        }
        $rs = $objDatabase->Execute($query);
        if ($rs === false) {
            throw new DatabaseError("error getting tags by article id");
        }

        $tags = array();
        while(!$rs->EOF) {
            $tags[$rs->fields['id']] = array(
                "name"  => $rs->fields['name'],
                "lang"  => $rs->fields['lang']);
            $rs->MoveNext();
        }
        return $tags;
    }

    /**
     * Get all article ids of a tag
     *
     * Return all article ids that have the given tag
     * assigned. Also return the tags name.
     * @param int $id
     * @global $objDatabase
     * @throws DatabaseError
     * @return array
     */
    public function getArticlesByTag($id)
    {
        global $objDatabase;

        $query = "  SELECT  tags.name as tagname,
                            relation.article as articleid
                    FROM `".DBPREFIX."module_knowledge_tags_articles` as relation
                    INNER JOIN `".DBPREFIX."module_knowledge_tags` as tags
                    ON relation.tag = tags.id
                    WHERE tags.id = ".$id;

        $rs = $objDatabase->Execute($query);
        if ($rs === false) {
            throw new DatabaseError("error getting articleids by tagid");
        }

        $articles = array();
        if (count($rs->RecordCount()) > 0) {
            $tagname = $rs->fields['tagname'];

            while(!$rs->EOF) {
                $articles[] = $rs->fields['articleid'];
                $rs->MoveNext();

            }
            return array(
                "name" => $tagname,
                "articles" => $articles);
        }
    }

    /**
     * Insert tags from a string
     *
     * @param   int     $article_id
     * @param   int     $string
     * @param   int     $lang
     */
    public function updateFromString($articleId, $string, $lang)
    {
        $lang = intval($lang);
        $tags = preg_split("/\s*,\s*/i", $string);

        //delete/disconnect removed tags
        foreach ($this->getByArticle($articleId) as $tagId => $tag) {
            if (($tag['lang'] == $lang) && (array_search($tag['name'], $tags) === false)) {
                $this->disconnectFromArticle($articleId, $tagId);
            }
        }
        $this->tidy();

        //add/connect new tags
        foreach ($tags as $tag) {
            if (!empty($tag)) {
                $res = $this->search_tag($tag, $lang);
                if ($res === false) {
                    $tagId = $this->insert($tag, $lang);
                } else {
                    $tagId = $res;
                }
                $this->connectWithArticle($articleId, $tagId);
            }
        }
    }

    /**
     * Insert a tag
     *
     * @param string $tag
     * @param int $lang
     * @global $objDatabase
     * @throws DatabaseError
     * @return $objDatabase
     */
    public function insert($tag, $lang)
    {
        global $objDatabase;

        $tag = contrexx_addslashes($tag);

        $query = "  INSERT INTO ".DBPREFIX."module_knowledge_tags
                    (name, lang)
                    VALUES
                    ('".$tag."', ".$lang.")";
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("error inserting new tag");
        }

        return $objDatabase->Insert_ID();
    }

    /**
     * Search tags
     *
     * Rearrange the tags array so the built-in array_search function
     * can be used (i think its faster)
     * @param string $tag
     * @param int $lang
     * @return mixed
     */
    private function search_tag($tag, $lang)
    {
        $allTags = $this->getAll($lang);
        foreach ($allTags as $compare) {
            if ($compare['name'] == $tag) {
                return $compare['id'];
            }
        }

        return false;
    }

    /**
     * Connect with an article
     *
     * @param   int     $articleId
     * @param   int     $tagId
     * @global  $objDatabase
     * @throws  DatabaseError
     */
    private function connectWithArticle($articleId, $tagId)
    {
        global $objDatabase;

        $query = '
            INSERT INTO `'.DBPREFIX.'module_knowledge_tags_articles` (`article`, `tag`)
            VALUES ('.intval($articleId).', '.intval($tagId).')
            ON DUPLICATE KEY UPDATE `article`=`article`
        ';
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("error tagging an article");
        }
    }

    /**
     * Disconnect from an article
     *
     * @param   int     $articleId
     * @param   int     $tagId
     * @global  $objDatabase
     * @throws  DatabaseError
     */
    private function disconnectFromArticle($articleId, $tagId)
    {
        global $objDatabase;

        $query = '
            DELETE FROM `'.DBPREFIX.'module_knowledge_tags_articles`
            WHERE (`article` = '.intval($articleId).') AND (`tag` = '.intval($tagId).')
        ';
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError('error while disconnecting a tag from an article');
        }
    }

    /**
     * Remove all tag relations from removed articles
     *
     * @global $objDatabase
     * @throws DatabaseError
     */
    public function clearTags()
    {
        global $objDatabase;

        $query = "  DELETE tags FROM ".DBPREFIX."module_knowledge_tags_articles AS tags
                    LEFT JOIN ".DBPREFIX."module_knowledge_articles AS articles ON articles.id = tags.article
                    WHERE ISNULL(articles.id)";
        if ($objDatabase->Execute($query) === false) {
            throw new DatabaseError("error deleting all references of an article");
        }
    }

    /**
     * Remove all Tags that are not used
     *
     * @global $objDatabase
     * @throws DatabaseError
     */
    public function tidy()
    {
        global $objDatabase;

        $query = "  SELECT tags.id
                    FROM ".DBPREFIX."module_knowledge_tags_articles AS relation
                    RIGHT JOIN ".DBPREFIX."module_knowledge_tags AS tags ON tags.id = relation.tag
                    WHERE relation.tag IS NULL";
        $rs = $objDatabase->Execute($query);
        if ($rs === false) {
            throw new DatabaseError("error getting unused tags ");
        }

        if ($rs->RecordCount() > 0) {
            $ids = "";
            while (!$rs->EOF) {
                $ids .= " ".$rs->fields['id'].",";
                $rs->MoveNext();
            }

            $ids = substr($ids, 0, -1);

            $query = " DELETE FROM ".DBPREFIX."module_knowledge_tags
                        WHERE id IN (".$ids.")";
            if ($objDatabase->Execute($query) === false) {
                throw new DatabaseError("error deleteting unused tags");
            }
        }
    }
}
