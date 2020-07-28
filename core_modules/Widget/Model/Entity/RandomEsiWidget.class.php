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
 * Represents a template widget that contains randomized content
 *
 * This class' only use is to identify such widgets. Randomizing is done in
 * EsiWidgetController
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodules_widget
 * @version     1.0.0
 */

namespace Cx\Core_Modules\Widget\Model\Entity;

/**
 * Represents a template widget that contains randomized content
 *
 * This class' only use is to identify such widgets. Randomizing is done in
 * EsiWidgetController
 * @copyright   CLOUDREXX CMS - Cloudrexx AG Thun
 * @author      Michael Ritter <michael.ritter@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  coremodules_widget
 * @version     1.0.0
 */
class RandomEsiWidget extends EsiWidget {

    /**
     * @var int Number of unique repetitions
     */
    protected $uniqueRepetitionCount = 1;

    /**
     * Returns the name of the JsonAdapter to call
     * @return string JsonAdapter name
     */
    public function getJsonAdapterName() {
        if (empty($this->jsonAdapterName)) {
            return $this->getRegisteringComponent()->getName() . 'RandomWidget';
        }
        return $this->jsonAdapterName;
    }

    /**
     * Returns the number of unique repetitions of this widget
     * @return int Number of unique repetitions
     */
    public function getUniqueRepetitionCount() {
        return $this->uniqueRepetitionCount;
    }

    /**
     * Sets the number of unique repetitions of this widget
     * @param int $count Number of unique repetitions
     */
    public function setUniqueRepetitionCount($count) {
        $this->uniqueRepetitionCount = $count;
    }
}
