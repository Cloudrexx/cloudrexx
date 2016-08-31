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

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Cx\Core_Modules\License;
/**
 * Description of Version
 *
 * @author ritt0r
 */
class Version {
    private $number;
    private $name;
    private $codeName;
    private $state;
    private $releaseDate;

    public function __construct($number, $name, $codeName, $state, $releaseDate) {
        $this->number = $number;
        $this->name = $name;
        $this->codeName = $codeName;
        $this->state = $state;
        $this->releaseDate = $releaseDate;
    }

    public function getNumber($asInt = false) {
        if ($asInt) {
            return $this->stringNumberToInt($this->number);
        }
        return $this->number;
    }

    public function setNumber($number) {
        $this->number = $number;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->name = $name;
    }

    public function getCodeName() {
        return $this->codeName;
    }

    public function setCodeName($codeName) {
        $this->codeName = $codeName;
    }

    public function getState() {
        return $this->state;
    }

    public function setState($state) {
        $this->state = $state;
    }

    public function getReleaseDate() {
        return $this->releaseDate;
    }

    public function setReleaseDate($releaseDate) {
        $this->releaseDate = $releaseDate;
    }

    public function isNewerThan($otherVersion) {
        return ($this->getNumber(true) > $otherVersion->getNumber(true));
    }

    public function isEqualTo($otherVersion) {
        return ($this->getNumber() === $otherVersion->getNumber());
    }

    /**
     * Converts an integer version number to a string version number
     * @todo Update to current version, found in wiki
     * @param int $vInt Integer version number
     * @return string String version number
     */
    public function intNumberToString($vInt) {
        return  intval(intval($vInt/10000)%100).'.'.
                intval(intval($vInt/  100)%100).'.'.
                intval(intval($vInt      )%100);
    }

    /**
     * Converts a string version number to an integer version number
     * @todo Update to current version, found in wiki
     * @param string $vString String version number
     * @return int Integer version number
     */
    public function stringNumberToInt($vString) {
        $parts = explode('.', $vString);
        if (!isset($parts[1])) {
            $parts[1] = 0;
        }
        if (!isset($parts[2])) {
            $parts[2] = 0;
        }
        return $parts[0]  * 10000 + $parts[1]  * 100 + $parts[2];
    }
}
