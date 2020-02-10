<?php

/**
 * Cloudrexx
 *
 * @link      https://www.cloudrexx.com
 * @copyright Cloudrexx AG 2007-2017
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
 * Cx\Modules\Block
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */

namespace Cx\Modules\Block\Controller;

/**
 * Cx\Modules\Block\Controller\JsonBlockException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */
class JsonBlockException extends \Exception {}

/**
 * Cx\Modules\Block\Controller\NotEnoughArgumentsException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */
class NotEnoughArgumentsException extends JsonBlockException {}

/**
 * Cx\Modules\Block\Controller\NoBlockFoundException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */
class NoBlockFoundException extends JsonBlockException {}

/**
 * Cx\Modules\Block\Controller\NoBlockFoundException
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Manuel Schenk <manuel.schenk@comvation.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_block
 */
class NoBlockVersionFoundException extends JsonBlockException
{
}

/**
 * Cx\Modules\Block\Controller\JsonBlockController
 *
 * JSON Adapter for Block
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
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
            'saveBlockContent' => new \Cx\Core_Modules\Access\Model\Entity\Permission(
                array(),
                array('post', 'cli'),
                true,
                array(),
                array(76)
            ),
            'getBlock',
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
     * @throws NotEnoughArgumentsException
     * @throws NoBlockFoundException
     * @return string the html content of the block
     */
    public function getBlockContent($params)
    {
        // whether or not widgets within the block
        // shall get parsed
        // this is used by the FrontendEditing
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
        if (!$lang) {
            $lang = FRONTEND_LANG_ID;
        }

        // database query to get the html content of a block by block id and
        // language id
        $em = $this->cx->getDb()->getEntityManager();

        $blockRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\Block');
        $block = $blockRepo->findOneBy(array('id' => $id));
        $localeRepo = $em->getRepository('\Cx\Core\Locale\Model\Entity\Locale');
        $locale = $localeRepo->findOneBy(array('id' => $lang));
        $now = time();

        $relLangContent = $qb->select('rlc')
            ->from('\Cx\Modules\Block\Model\Entity\RelLangContent', 'rlc')
            ->from('\Cx\Modules\Block\Model\Entity\Block', 'b')
            ->where('b = :block')
            ->andWhere('rlc.block = b')
            ->andWhere('(rlc.locale = :locale AND rlc.active = 1)')
            ->andWhere('(b.start <= :now OR b.start = 0)')
            ->andWhere('(b.end >= :now OR b.end = 0)')
            ->andWhere('b.active = 1')
            ->setParameters(array(
                'block' => $block,
                'locale' => $locale,
                'now' => $now,
            ))
            ->getQuery()
            ->getResult();

        // nothing found
        if (!$relLangContent) {
            // if we would throw an exception here, then deactivated blocks are not cached
            return array('content' => '');
        }

        $content = $relLangContent->getContent();

        // abort for returning raw data
        if (!$parsing) {
            return $content;
        }

        $this->cx->parseGlobalPlaceholders($content);
        $template = new \Cx\Core_Modules\Widget\Model\Entity\Sigma();
        $template->setTemplate($content);
        $this->getComponent('Widget')->parseWidgets(
            $template,
            'Block',
            'Block',
            $id
        );
        $content = $template->get();

        $page = null;
        if (isset($params['get']['page'])) {
            $pageRepo = $em->getRepository('Cx\Core\ContentManager\Model\Entity\Page');
            $page = $pageRepo->find($params['get']['page']);
        }


        \Cx\Modules\Block\Controller\Block::setBlocks($content, $page);

        \LinkGenerator::parseTemplate($content);
        $ls = new \LinkSanitizer(
            $this->cx,
            $this->cx->getCodeBaseOffsetPath() . \Env::get('virtualLanguageDirectory') . '/',
            $content
        );
        return array('content' => $ls->replace());
    }

    /**
     * Save the block content
     *
     * @param array $params all given params from http request
     * @throws NotEnoughArgumentsException
     * @return boolean true if everything finished with success
     */
    public function saveBlockContent($params)
    {
        global $_CORELANG;

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
        $em = $this->cx->getDb()->getEntityManager();
        $localeRepo = $em->getRepository('\Cx\Core\Locale\Model\Entity\Locale');
        $blockRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\Block');
        $relLangContentRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\RelLangContent');

        $locale = $localeRepo->findOneBy(array('id' => $lang));
        $block = $blockRepo->findOneBy(array('id' => $id));
        $relLangContent = $relLangContentRepo->findOneBy(array('locale' => $locale, 'block' => $block));
        if ($relLangContent) {
            $relLangContent->setContent($content);
        }

        $em->flush();

        \LinkGenerator::parseTemplate($content);

        $ls = new \LinkSanitizer(
            $this->cx,
            $this->cx->getCodeBaseOffsetPath() . \Env::get('virtualLanguageDirectory') . '/',
            $content
        );
        $this->messages[] = $_CORELANG['TXT_CORE_SAVED_BLOCK'];

        return array('content' => $ls->replace());
    }

    /**
     * Gets every block attribute for given block and version
     *
     * @param array $params all given params from http request
     * @throws NotEnoughArgumentsException if not enough arguments are provided
     * @throws NoBlockFoundException if the requested block can't be found
     * @throws NoBlockVersionFoundException if the requested block version can't be found
     * @return array $blockVersion all attributes from block
     */
    public function getBlock($params)
    {
        // throws exception if not enough arguments are provided
        if (empty($params['get']['id']) || empty($params['get']['version']) || empty($params['get']['lang'])) {
            throw new NotEnoughArgumentsException('not enough arguments');
        }

        // gets params from request
        $id = intval($params['get']['id']);
        $version = intval($params['get']['version']);

        // gets block by id parameter
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();
        $blockRepo = $em->getRepository('\Cx\Modules\Block\Model\Entity\Block');
        $block = $blockRepo->findOneBy(array('id' => $id));

        // throws exception if no block found
        if (!$block) {
            throw new NoBlockFoundException('no block found with id: ' . $id);
        }

        // gets log entry repository
        $blockLogRepo = $em->getRepository('Cx\Modules\Block\Model\Entity\LogEntry');
        // gets requested block version
        $revertedBlock = $blockLogRepo->revertEntity($block, $version);

        if (!$revertedBlock) {
            throw new NoBlockVersionFoundException('no block found under version: ' . $version);
        }

        // get targeting option version
        $targetingOptionValue = $this->getVersionValue(
            '\Cx\Modules\Block\Model\Entity\TargetingOption',
            $revertedBlock->getVersionTargetingOption(),
            array(
                'type',
                'filter',
                'value',
            ),
            array()
        );
        $countries = json_decode($targetingOptionValue[0]['value']);
        $targetingOptionValue[0]['value'] = array();
        foreach ($countries as $key => $countryId) {
            $targetingOptionValue[0]['value'][$countryId] = \Cx\Core\Country\Controller\Country::getNameById($countryId);
        }

        // get rel lang content version
        $relLangContentValue = $this->getVersionValue(
            '\Cx\Modules\Block\Model\Entity\RelLangContent',
            $revertedBlock->getVersionRelLangContent(),
            array(
                'content',
                'locale',
            ),
            array(
                'locale',
            )
        );

        // get rel page global version
        $relPageGlobalValue = $this->getVersionValue(
            '\Cx\Modules\Block\Model\Entity\RelPage',
            $revertedBlock->getVersionRelPageGlobal(),
            array(
                'page',
            ),
            array(
                'page',
            )
        );

        // get rel page category version
        $relPageCategoryValue = $this->getVersionValue(
            '\Cx\Modules\Block\Model\Entity\RelPage',
            $revertedBlock->getVersionRelPageCategory(),
            array(
                'page',
            ),
            array(
                'page',
            )
        );

        // get rel page direct version
        $relPageDirectValue = $this->getVersionValue(
            '\Cx\Modules\Block\Model\Entity\RelPage',
            $revertedBlock->getVersionRelPageDirect(),
            array(
                'page',
            ),
            array(
                'page',
            )
        );

        // gets all data from block
        $start = $revertedBlock->getStart();
        $end = $revertedBlock->getEnd();
        $revertedBlockCategory = $revertedBlock->getCategory();
        $blockVersion = array(
            'id' => $revertedBlock->getId(),
            'start' => !empty($start) ? strftime('%Y-%m-%d %H:%M', $start) : $start,
            'end' => !empty($end) ? strftime('%Y-%m-%d %H:%M', $end) : $end,
            'name' => $revertedBlock->getName(),
            'random' => $revertedBlock->getRandom(),
            'random2' => $revertedBlock->getRandom2(),
            'random3' => $revertedBlock->getRandom3(),
            'random4' => $revertedBlock->getRandom4(),
            'showInCategory' => $revertedBlock->getShowInCategory(),
            'showInGlobal' => $revertedBlock->getShowInGlobal(),
            'showInDirect' => $revertedBlock->getShowInDirect(),
            'active' => $revertedBlock->getActive(),
            'order' => $revertedBlock->getOrder(),
            'wysiwygEditor' => $revertedBlock->getWysiwygEditor(),
            'category' => $revertedBlockCategory ? $revertedBlockCategory->getId() : 0,
            'targetingOption' => $targetingOptionValue,
            'relLangContent' => $relLangContentValue,
            'relPageGlobal' => $relPageGlobalValue,
            'relPageCategory' => $relPageCategoryValue,
            'relPageDirect' => $relPageDirectValue,
        );

        // return requested block version array
        return $blockVersion;
    }

    /**
     * Processes and returns value of related block entities stored in block
     *
     * @param $className string full qualified class name to get repo
     * @param $data array serialized data
     * @param $attributes array wanted attributes of entity
     * @param $idAttributes array wanted attributes to get an ID on
     * @return $entityValue array processed entity value
     */
    protected function getVersionValue($className, $data, $attributes, $idAttributes)
    {
        // get entity manager
        $em = \Cx\Core\Core\Controller\Cx::instanciate()->getDb()->getEntityManager();

        // unserializes data
        $unserializedData = unserialize($data);

        // gets repository
        $entityRepo = $em->getRepository($className);

        $entityValue = array();
        foreach ($unserializedData as $id => $version) {
            // find entity by id
            $entity = $entityRepo->findOneBy(
                array(
                    'id' => $id,
                )
            );

            // simulates entity if provided one was deleted
            if (!$entity) {
                $entity = new $className();
                $entity->setId($id);
            }

            $blockLogRepo = $em->getRepository('Cx\Modules\Block\Model\Entity\LogEntry');
            // reverts found entity on given version
            $revertedEntity = $blockLogRepo->revertEntity($entity, $version);

            // gets value for wanted attributes
            $attributesValue = array();
            foreach ($attributes as $attribute) {
                if (!in_array($attribute, $idAttributes)) {
                    $attributesValue[$attribute] = $revertedEntity->{'get' . ucfirst($attribute)}();
                } else {
                    // gets id on wanted attributes
                    $relatedEntity = $revertedEntity->{'get' . ucfirst($attribute)}();
                    if ($relatedEntity) {
                        $attributesValue[$attribute] = $relatedEntity->getId();
                    }
                }
            }
            // push value to entity value array
            array_push(
                $entityValue,
                $attributesValue
            );
        }

        // returns value of the provided entity
        return $entityValue;
    }
}
