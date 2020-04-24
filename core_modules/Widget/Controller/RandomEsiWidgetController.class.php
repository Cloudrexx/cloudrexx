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
 * JsonAdapter Controller to handle EsiWidgets
 *
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */

namespace Cx\Core_Modules\Widget\Controller;

/**
 * JsonAdapter Controller to handle EsiWidgets
 * Usage:
 * - Create a subclass that implements parseWidget()
 * - Register it as a Controller in your ComponentController
 * @author Michael Ritter <michael.ritter@cloudrexx.com>
 * @package cloudrexx
 * @subpackage coremodules_widget
 */
abstract class RandomEsiWidgetController extends EsiWidgetController {

    /**
     * @var int Max count of widgets that are randomized by ESI
     */
    const DEFAULT_SUBWIDGET_COUNT_LIMIT = 100;

    /**
     * @var int Max cache lifetime of subwidgets in seconds
     */
    const DEFAULT_SUBWIDGET_CACHE_LIFETIME = 60 * 60 * 24;

    /**
     * Max number of sub-widgets that will be randomized per widget
     * @var integer Sub-widget count
     */
    protected $subwidgetCountLimit;

    /**
     * Max lifetime of randomized content in seconds
     * This is only used if the number of sub-widgets is greater than
     * $subwidgetCountLimit. In this case sub-widgets are randomly picked and
     * cached.
     * @var integer Cache lifetime in seconds
     */
    protected $subwidgetCacheLifetime;

    /**
     * @inheritdoc
     */
    public function __construct(\Cx\Core\Core\Model\Entity\SystemComponentController $systemComponentController, \Cx\Core\Core\Controller\Cx $cx) {
        parent::__construct($systemComponentController, $cx);
        $this->subwidgetCountLimit = static::DEFAULT_SUBWIDGET_COUNT_LIMIT;
        $this->subwidgetCacheLifetime = static::DEFAULT_SUBWIDGET_CACHE_LIFETIME;
    }

    /**
     * Returns the internal name used as identifier for this adapter
     * @see \Cx\Core\Json\JsonAdapter::getName()
     * @return string Name of this adapter
     */
    public function getName() {
        return $this->getSystemComponent()->getName() . 'RandomWidget';
    }

    /**
     * Returns the max number of cached sub-widgets
     * @return integer Number of widgets
     */
    public function getSubwidgetCountLimit() {
        return $this->subwidgetCountLimit;
    }

    /**
     * Returns the max lifetime of cached sub-widgets
     * @return integer Lifetime in seconds
     */
    public function getSubwidgetCacheLifetime() {
        return $this->subwidgetCacheLifetime;
    }

    /**
     * Parses a widget
     * @param \Cx\Core_Modules\Widget\Model\Entity\Widget $widget The Widget
     * @param array $params Params passed by ESI (/API) request
     * @return array Content in an associative array
     */
    protected function internalParseWidget($widget, $params) {
        if ($widget instanceof \Cx\Core_Modules\Widget\Model\Entity\RandomEsiWidget) {
            $template = $this->getComponent('Widget')->getWidgetContent(
                $widget->getName(),
                $params['get']['theme'],
                $params['get']['page'],
                $params['get']['targetComponent'],
                $params['get']['targetEntity'],
                $params['get']['targetId'],
                $params['get']['channel']
            );
            $esiInfos = $this->getRandomEsiWidgetContentInfos($widget, $params, $template);

            if (count($esiInfos) > $this->getSubwidgetCountLimit()) {
                // randomly pick some
                // TODO: This randomly picks some instead of randomizing all
                $randomIndexes = array_rand($esiInfos, $this->getSubwidgetCountLimit());
                $limitedEsiInfos = array();
                foreach ($randomIndexes as $index) {
                    $limitedEsiInfos[] = $esiInfos[$index];
                }
                $esiInfos = $limitedEsiInfos;
                // set timeout
                $params['response']->setExpirationDate(
                    new \DateTime(
                        '+' . $this->getSubwidgetCacheLifetime() . 'seconds'
                    )
                );
            }

            $esiContent = $this->getComponent('Cache')->getRandomizedEsiContent(
                $esiInfos,
                $widget->getUniqueRepetitionCount()
            );
            return array(
                'content' => $esiContent,
            );
        }
        return parent::internalParseWidget($widget, $params);
    }

    /**
     * Returns a list of ESI request infos that are to be randomized
     *
     * Each returned entry consists of an array like:
     * array(
     *     <adapterName>,
     *     <adapterMethod>,
     *     <params>,
     * )
     * @param \Cx\Core_Modules\Widget\Model\Entity\Widget $widget The RandomEsiWidget
     * @param array $params ESI request params
     * @param \Cx\Core\Html\Sigma Widget template
     * @return array List of URLs
     */
    protected abstract function getRandomEsiWidgetContentInfos($widgetName, $params, $template);
}
