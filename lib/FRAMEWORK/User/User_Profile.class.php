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
 * User Profile
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Daeppen <thomas.daeppen@comvation.com>
 * @version     2.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 */

/**
 * User Profile
 *
 * The User object is used for all user related operations.
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Thomas Daeppen <thomas.daeppen@comvation.com>
 * @version     2.0.0
 * @package     cloudrexx
 * @subpackage  lib_framework
 * @uses        /lib/FRAMEWORK/User/User_Profile_Attribute.class.php
 */
class User_Profile
{
    /**
     * @var User_Profile_Attribute
     */
    public $objAttribute;
    public $arrAttributeHistories;
    public $arrUpdatedAttributeHistories;

    /**
     * @access private
     * @var array
     */
    public static $arrNoAvatar = array(
        'src'        => '0_noavatar.gif',
        'width'        => 121,
        'height'    => 160
    );

    public static $arrNoPicture = array(
        'src'        => '0_no_picture.gif',
        'width'        => 80,
        'height'    => 84
    );


    public function __construct()
    {
        $this->initAttributes();
    }


    private function initAttributes()
    {
        $this->objAttribute = new User_Profile_Attribute();
    }


    public function setProfile($arrProfile, $ignoreAccessPermissions = false)
    {
        $arrDate = array();
        $arrDateFormat = array();
        foreach ($arrProfile as $attributeId => $arrValue) {
            if (!is_array($arrValue)) {
                continue;
            }

            $objAttribute = $this->objAttribute->getById($attributeId);
            if (in_array($objAttribute->getType(), array('menu_option', 'group', 'frame', 'history'))) {
                continue;
            }

            if (isset($this->arrLoadedUsers[$this->id]['profile'][$attributeId])) {
                $arrStoredAttributeData = $this->arrLoadedUsers[$this->id]['profile'][$attributeId];
            } else {
                $arrStoredAttributeData = array();
            }
            $this->arrLoadedUsers[$this->id]['profile'][$attributeId] = array();
            foreach ($arrValue as $historyId => $value) {
                if ($this->objAttribute->isHistoryChild($attributeId) && !$historyId) {
                    continue;
                }

                if ($this->objAttribute->isHistoryChild($attributeId) && $historyId === 'new') {
                    $historyId = 0;
                    $arrValues = $value;
                } else {
                    $arrValues = array($value);
                }

                foreach ($arrValues as $nr => $value) {
                    $value = trim(contrexx_stripslashes($value));

                    if ($objAttribute->getType() === 'date') {
                        if (is_array($value)) {
                            $objDateTime = new DateTime("${value['month']}/${value['day']}/${value['year']}");
                            $value = $objDateTime->format(ASCMS_DATE_FORMAT_DATE);
                        }

                        if (preg_match_all('#([djmnYy])+#', ASCMS_DATE_FORMAT_DATE, $arrDateFormat, PREG_PATTERN_ORDER) && preg_match_all('#([0-9]+)#', $value, $arrDate)) {
                            foreach ($arrDateFormat[1] as $charNr => $char) {
                                $arrDateCombined[$char] = $arrDate[1][$charNr];
                            }

                            $value = gmmktime(1, 0, 0,
                                (isset($arrDateCombined['m']) ? $arrDateCombined['m'] : $arrDateCombined['n']), // month
                                (isset($arrDateCombined['d']) ? $arrDateCombined['d'] : $arrDateCombined['j']), // day
                                (isset($arrDateCombined['Y']) ? $arrDateCombined['Y'] : ($arrDateCombined['y'] + ($arrDateCombined['y'] < 70 ? 2000 : 1900))) // year
                            );
                        } elseif ($this->objAttribute->isDefaultAttribute($attributeId)) {
                            $value = '';
                        } else {
                            continue;
                        }
                    }

                    if ($objAttribute->getId() &&
                        (
                            $ignoreAccessPermissions ||
                            !$objAttribute->isProtected() ||
                            (
                                Permission::checkAccess($objAttribute->getAccessId(), 'dynamic', true) ||
                                $objAttribute->checkModifyPermission(
                                    (in_array($attributeId, array('title', 'country')) ? $attributeId.'_' : '').(isset($arrStoredAttributeData[$historyId]) ? $arrStoredAttributeData[$historyId] : null),
                                    (in_array($attributeId, array('title', 'country')) ? $attributeId.'_' : '').$value)
                            )
                        )
                    ) {
                        if ($this->objAttribute->isHistoryChild($attributeId) && !$historyId) {
                            $historyId = (isset($this->arrAttributeHistories[$this->id][$this->objAttribute->getHistoryAttributeId($attributeId)]) ? max($this->arrAttributeHistories[$this->id][$this->objAttribute->getHistoryAttributeId($attributeId)]) : 0)+1;
                        }

                        $this->arrLoadedUsers[$this->id]['profile'][$attributeId][$historyId+$nr] = $value;
                        if ($historyId+$nr &&
                            (!isset($this->arrUpdatedAttributeHistories[$this->id][$this->objAttribute->getHistoryAttributeId($attributeId)]) ||
                            !in_array($historyId+$nr, $this->arrUpdatedAttributeHistories[$this->id][$this->objAttribute->getHistoryAttributeId($attributeId)]))
                        ) {
                            $this->arrUpdatedAttributeHistories[$this->id][$this->objAttribute->getHistoryAttributeId($attributeId)][] = $historyId+$nr;
                        }
                    } else {
                        $this->arrLoadedUsers[$this->id]['profile'][$attributeId] = $arrStoredAttributeData;
                        continue;
                    }
                }
            }
        }

        // synchronize history-ID's
        $this->arrAttributeHistories[$this->id] = $this->arrUpdatedAttributeHistories[$this->id];

        return true;
    }


    public function checkMandatoryCompliance()
    {
        global $_CORELANG;

        foreach ($this->objAttribute->getMandatoryAttributeIds() as $attributeId) {
            $arrHistoryIds = array();
            $historyAttributeId = $this->objAttribute->getHistoryAttributeId($attributeId);
            if (!$historyAttributeId) {
                $arrHistoryIds[] = 0;
            } elseif (isset($this->arrUpdatedAttributeHistories[$this->id][$historyAttributeId])) {
                $arrHistoryIds = $this->arrUpdatedAttributeHistories[$this->id][$historyAttributeId];
            }

            foreach ($arrHistoryIds as $historyId) {
                if (
                       empty($this->arrLoadedUsers[$this->id]['profile'][$attributeId][$historyId])
                    || $this->objAttribute->isDefaultAttribute($attributeId)
                       && ($objAttribute = $this->objAttribute->getById($attributeId))
                       && $objAttribute->getType() == 'menu'
                       && $objAttribute->isUnknownOption($this->arrLoadedUsers[$this->id]['profile'][$attributeId][$historyId])
                ) {
                    $this->error_msg[] = $_CORELANG['TXT_ACCESS_FILL_OUT_ALL_REQUIRED_FIELDS'];
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  mixed    $profileUpdated    If $profileUpdated is provided, then in case any profile
     *                                  changes are being flushed to the database, $profileUpdated
     *                                  will be set to TRUE, otherwise it'll be left untouched.
     */
    protected function storeProfile(&$profileUpdated = null)
    {
        global $_CORELANG;

        $error = false;

        $cx = \Cx\Core\Core\Controller\Cx::instanciate();
        $em = $cx->getDb()->getEntityManager();
        $userRepo = $em->getRepository('Cx\Core\User\Model\Entity\User');
        $attributeRepo = $em->getRepository('Cx\Core\User\Model\Entity\UserAttribute');
        $attributeValueRepo = $em->getRepository('Cx\Core\User\Model\Entity\UserAttributeValue');

        foreach ($this->arrLoadedUsers[$this->id]['profile'] as $attributeId => $arrValue)
        {
            foreach ($arrValue as $historyId => $value)
            {
                $newValue = !isset($this->arrCachedUsers[$this->id]['profile'][$attributeId][$historyId]);
                if ($newValue || $value != $this->arrCachedUsers[$this->id]['profile'][$attributeId][$historyId]) {

                    if ($this->objAttribute->isDefaultAttribute($attributeId)) {
                        $attributeId = $this->objAttribute->getAttributeIdByDefaultAttributeId($attributeId);
                    }

                    $attributeValue = $attributeValueRepo->findOneBy(
                        array('userAttribute' => $attributeId, 'user' => $this->id, 'history' => $historyId)
                    );
                    if (!$attributeValue) {
                        $attributeValue = new \Cx\Core\User\Model\Entity\UserAttributeValue();
                    }
                    $attribute = $attributeRepo->find($attributeId);
                    $user = $userRepo->find($this->id);
                    $attributeValue->setUserAttribute($attribute);
                    $attributeValue->setUser($user);
                    $attributeValue->setHistory($historyId);
                    $attributeValue->setValue(contrexx_raw2db($value));

                    try {
                        $em->persist($attributeValue);
                        $em->flush();
                        $profileUpdated = true;
                    } catch (\Doctrine\ORM\OptimisticLockException $e) {
                        $error = true;
                        $this->error_msg[] = sprintf($_CORELANG['TXT_ACCESS_UNABLE_STORE_PROFILE_ATTIRBUTE'], htmlentities($attribute->getName(), ENT_QUOTES, CONTREXX_CHARSET));
                    }
                }
            }

            if ($this->objAttribute->isCustomAttribute($attributeId) && isset($this->arrCachedUsers[$this->id]['profile'][$attributeId])) {
                foreach (array_diff(array_keys($this->arrCachedUsers[$this->id]['profile'][$attributeId]), array_keys($arrValue)) as $historyId) {
                    $attributeValue = $attributeValueRepo->findOneBy(
                        array('userAttribute' => $attributeId, 'user' => $this->id, 'history' => $historyId)
                    );

                    try {
                        $em->remove($attributeValue);
                        $em->flush();
                        // track flushed db change
                        $profileUpdated = true;
                    } catch (\Doctrine\ORM\OptimisticLockException $e) {
                        $attribute = $attributeRepo->find($attributeId);
                        $error = true;
                        $this->error_msg[] = sprintf($_CORELANG['TXT_ACCESS_UNABLE_STORE_PROFILE_ATTIRBUTE'], htmlentities($attribute->getName(), ENT_QUOTES, CONTREXX_CHARSET));
                    }
                }
            }
        }

        return !$error;
    }


    /**
     * Create a profile for the loaded user
     *
     * This creates entries in the database table
     * contrexx_access_user_attribute_value which is related to the entry in the
     * table cotnrexx_access_users of the same user.
     * This methode will be obsolete as soon as we're using InnoDB as storage engine.
     *
     * @return boolean
     */
    protected function createProfile()
    {
        $this->arrLoadedUsers[$this->id]['profile'] = isset($this->arrLoadedUsers[0]['profile']) ? $this->arrLoadedUsers[0]['profile'] : array();
        return true;
    }

}
