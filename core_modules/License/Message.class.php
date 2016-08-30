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
 * Description of Message
 *
 * @author ritt0r
 */
class Message {
    private $langCode;
    private $text;
    private $type;
    private $link;
    private $linkTarget;
    private $showInDashboard = true;

    public function __construct($langCode = null, $text = '', $type = 'alertbox', $link = '', $linkTarget = '_blank', $showInDashboard = true) {
        $this->langCode = $langCode ? $langCode : \FWLanguage::getLanguageCodeById(LANG_ID);
        $this->text = $text;
        $this->type = $type;
        $this->link = $link;
        $this->linkTarget = $linkTarget;
        $this->showInDashboard = $showInDashboard;
    }

    public function getLangCode() {
        return $this->langCode;
    }

    public function getText() {
        return $this->text;
    }

    public function getType() {
        return $this->type;
    }

    public function getLink() {
        return $this->link;
    }

    public function getLinkTarget() {
        return $this->linkTarget;
    }

    public function showInDashboard() {
        return $this->showInDashboard;
    }
}
