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
 * Doctrine SQL Logger for DBG
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     3.0.1
 * @package     cloudrexx
 * @subpackage  lib_dbg
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Lib\DBG;

/**
 * Doctrine SQL Logger for DBG
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @version     3.0.1
 * @package     cloudrexx
 * @subpackage  lib_dbg
 * @todo        Edit PHP DocBlocks!
 */
class DoctrineSQLLogger implements \Doctrine\DBAL\Logging\SQLLogger
{
    private $query = null;
    private $startTime = null;

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null, array $types = null)
    {
        $this->query = null;
        if (   !(\DBG::getMode() & DBG_DOCTRINE)
            && !(\DBG::getMode() & DBG_DOCTRINE_CHANGE)
            && !(\DBG::getMode() & DBG_DOCTRINE_ERROR)
        ) {
            return;
        }

        // prepare SQL statement
        if ($params) {
            $sql = str_replace('?', "'%s'", $sql);
            //$this->query = vsprintf($sql, $params);
            foreach ($params as &$param) {
                // serialize arrays
                if (is_array($param)) {
                    $param = serialize($param);
                } elseif (is_object($param)) {
                    // serialize objects
                    switch (get_class($param)) {
                        case 'DateTime':
                            // output DateTime object as date literal
                            $param = $param->format(ASCMS_DATE_FORMAT_DATETIME);
                            break;
                        default:
                            break;

                    }
                }
            }
            $sql = vsprintf($sql, $params);
        }

        \DBG::logSQL($sql);
        $this->startTime = microtime(true);
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        /*global $objDatabase;

        if (   !(\DBG::getMode() & DBG_DOCTRINE)
            && !(\DBG::getMode() & DBG_DOCTRINE_CHANGE)
            && !(\DBG::getMode() & DBG_DOCTRINE_ERROR)
        ) {
            return;
        }

        if (!$objDatabase || !$this->query) {
            return;
        }

        $timeDiff = microtime(true) - $this->startTime;
        $this->startTime = null;

        $result = $objDatabase->execute($this->query);
        $this->query = null;
        if (!$result) {
            \DBG::log('<p>The query above failes (took ' . $timeDiff .  ' seconds)!</p>');
        } else {
            \DBG::log('<p>The query above returns ' . $result->RecordCount() . ' rows when executed in SQL directly (took ' . $timeDiff .  ' seconds).</p>');
        }*/
    }
}
