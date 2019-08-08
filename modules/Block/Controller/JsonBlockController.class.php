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
 * JSON Adapter for Block
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_block
 */

namespace Cx\Modules\Block\Controller;

/**
 * Class JsonBlockException
 * @package     cloudrexx
 * @subpackage  module_block
 */
class JsonBlockException extends \Exception {}

/**
 * Class NoPermissionException
 * @package     cloudrexx
 * @subpackage  module_block
 */
class NoPermissionException extends JsonBlockException {}

/**
 * Class NotEnoughArgumentsException
 * @package     cloudrexx
 * @subpackage  module_block
 */
class NotEnoughArgumentsException extends JsonBlockException {}

/**
 * Class NoBlockFoundException
 * @package     cloudrexx
 * @subpackage  module_block
 */
class NoBlockFoundException extends JsonBlockException {}

/**
 * Class BlockCouldNotBeSavedException
 * @package     cloudrexx
 * @subpackage  module_block
 */
class BlockCouldNotBeSavedException extends JsonBlockException {}

/**
 * JSON Adapter for Block
 *
 * @copyright   Cloudrexx AG
 * @author      Project Team SS4U <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_block
 */
class JsonBlockController extends \Cx\Core\Core\Model\Entity\Controller implements \Cx\Core\Json\JsonAdapter
{
    /**
     * List of messages
     * @var Array
     */
    protected $messages = array();

    /**
     * Returns the internal name used as identifier for this adapter
     * @return String Name of this adapter
     */
    public function getName()
    {
        return 'Block';
    }

    /**
     * Returns default permission as object
     * @return Object
     */
    public function getDefaultPermissions()
    {
        return new \Cx\Core_Modules\Access\Model\Entity\Permission();
    }

    /**
     * Returns an array of method names accessable from a JSON request
     * @return array List of method names
     */
    public function getAccessableMethods()
    {
        return array(
            'getCountries',
            'getBlocks',
            'getBlockContent' => new \Cx\Core_Modules\Access\Model\Entity\Permission(
                array(),
                array('get', 'cli', 'post'),
                false
            ),
            'saveBlockContent' => new \Cx\Core_Modules\Access\Model\Entity\Permission(array(), array('post'))
        );
    }

    /**
     * Returns all messages as string
     * @return String HTML encoded error messages
     */
    public function getMessagesAsString() {
        return implode('<br />', $this->messages);
    }

    /**
     * Get countries from given name
     *
     * @param array $params Get parameters,
     *
     * @return array Array of countries
     */
    public function getCountries($params)
    {
        $countries = array();
        $term = !empty($params['get']['term']) ? contrexx_input2raw($params['get']['term']) : '';
        if (empty($term)) {
            return array(
                'countries' => $countries
            );
        }
        $arrCountries = \Cx\Core\Country\Controller\Country::searchByName($term,null,false);
        foreach ($arrCountries as $country) {
            $countries[] = array(
                'id'    => $country['id'],
                'label' => $country['name'],
                'val'   => $country['name'],
            );
        }
        return array(
            'countries' => $countries
        );
    }

    /**
     * Returns all available blocks for each language
     *
     * @return array List of blocks (lang => id )
     */
    public function getBlocks() {
        global $objInit, $_CORELANG;

        if (!\FWUser::getFWUserObject()->objUser->login() || $objInit->mode != 'backend') {
            throw new \Exception($_CORELANG['TXT_ACCESS_DENIED_DESCRIPTION']);
        }

        $blockLib = new \Cx\Modules\Block\Controller\BlockLibrary();
        $blocks = $blockLib->getBlocks();
        $data = array();
        foreach ($blocks as $id=>$block) {
            $data[$id] = array(
                'id' => $id,
                'name' => $block['name'],
                'disabled' => $block['global'] == 1,
                'selected' => $block['global'] == 1,
            );
        }
        return $data;
    }

    /**
     * Get the block content as html
     *
     * @param array $params all given params from http request
     * @throws NoPermissionException
     * @throws NotEnoughArgumentsException
     * @throws NoBlockFoundException
     * @return string the html content of the block
     */
    public function getBlockContent($params) {
        global $_CORELANG, $objDatabase;

        // whether or not widgets within the block
        // shall get parsed
        $parsing = true;
        if (
            isset($params['get']['parsing']) &&
            $params['get']['parsing'] == 'false'
        ) {
            $parsing = false;
        }

        // check for necessary arguments
        if (
            empty($params['get']) ||
            empty($params['get']['block']) ||
            empty($params['get']['lang'])
        ) {
            throw new NotEnoughArgumentsException('not enough arguments');
        }

        // get id and langugage id
        $id = intval($params['get']['block']);
        $lang = \FWLanguage::getLanguageIdByCode($params['get']['lang']);
        if (!defined('FRONTEND_LANG_ID')) {
            if (!$lang) {
                $lang = 1;
            }
            define('FRONTEND_LANG_ID', $lang);
        }
        if (!$lang) {
            $lang = FRONTEND_LANG_ID;
        }

        // database query to get the html content of a block by block id and
        // language id
        $now = time();
        $query = "SELECT
                      c.content
                  FROM
                      `".DBPREFIX."module_block_blocks` b
                  INNER JOIN
                      `".DBPREFIX."module_block_rel_lang_content` c
                  ON c.block_id = b.id
                  WHERE
                      b.id = ".$id."
                  AND b.`active` = 1
                  AND (b.`start` <= " . $now . " OR b.`start` = 0)
                  AND (b.`end` >= " . $now . " OR b.`end` = 0)
                  AND
                      (c.lang_id = ".$lang." AND c.active = 1)";

        $result = $objDatabase->Execute($query);

        // nothing found
        if ($result === false || $result->RecordCount() == 0) {
            // if we would throw an exception here, then deactivated blocks are not cached
            return array('content' => '');
        }

        $content = $result->fields['content'];
        // abort for returning raw data
        if (!$parsing) {
            return $content;
        }

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $cx->parseGlobalPlaceholders($content);
        $template = new \Cx\Core_Modules\Widget\Model\Entity\Sigma();
        $template->setTemplate($content);
        $this->getComponent('Widget')->parseWidgets(
            $template,
            'Block',
            'Block',
            $id
        );
        $content = $template->get();

        // abort for returning raw data
        if (!$parsing) {
            return $content;
        }

        $page = null;
        if (isset($params['get']['page'])) {
            $em = $cx->getDb()->getEntityManager();
            $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
            $page = $pageRepo->find($params['get']['page']);
        }

        \Cx\Modules\Block\Controller\Block::setBlocks($content, $page);

        \LinkGenerator::parseTemplate($content);
        $ls = new \LinkSanitizer(
            $cx,
            $cx->getCodeBaseOffsetPath() . \Env::get('virtualLanguageDirectory') . '/',
            $content
        );
        return array('content' => $ls->replace());
    }

    /**
     * Save the block content
     *
     * @param array $params all given params from http request
     * @throws NoPermissionException
     * @throws NotEnoughArgumentsException
     * @throws BlockCouldNotBeSavedException
     * @return boolean true if everything finished with success
     */
    public function saveBlockContent($params) {
        global $_CORELANG, $objDatabase;

        // security check
        if (   !\FWUser::getFWUserObject()->objUser->login()
            || !\Permission::checkAccess(76, 'static', true)) {
            throw new NoPermissionException($_CORELANG['TXT_ACCESS_DENIED_DESCRIPTION']);
        }

        // check arguments
        if (empty($params['get']['block']) || empty($params['get']['lang'])) {
            throw new NotEnoughArgumentsException('not enough arguments');
        }

        // get language and block id
        $id = intval($params['get']['block']);
        $lang = \FWLanguage::getLanguageIdByCode($params['get']['lang']);
        if (!$lang) {
            $lang = FRONTEND_LANG_ID;
        }
        $content = $params['post']['content'];

        // query to update content in database
        $query = "UPDATE `".DBPREFIX."module_block_rel_lang_content`
                      SET content = '".\contrexx_input2db($content)."'
                  WHERE
                      block_id = ".$id." AND lang_id = ".$lang;
        $result = $objDatabase->Execute($query);

        // error handling
        if ($result === false) {
            throw new BlockCouldNotBeSavedException('block could not be saved');
        }
        \LinkGenerator::parseTemplate($content);

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $ls = new \LinkSanitizer(
            $cx,
            $cx->getCodeBaseOffsetPath() . \Env::get('virtualLanguageDirectory') . '/',
            $content
        );
        $this->messages[] = $_CORELANG['TXT_CORE_SAVED_BLOCK'];

        return array('content' => $ls->replace());
    }
}
