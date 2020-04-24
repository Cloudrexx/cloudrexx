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
 * Media  Directory Mail Class
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 * @todo        Edit PHP DocBlocks!
 */
namespace Cx\Modules\MediaDir\Controller;
/**
 *
 *
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      CLOUDREXX Development Team <info@cloudrexx.com>
 * @package     cloudrexx
 * @subpackage  module_mediadir
 */
class MediaDirectoryMail extends MediaDirectoryLibrary
{
    private $intAction;
    private $intEntryId;
    private $objUser;
    private $intNeedAuth;
    private $strTitle;
    private $strTemplate;
    private $arrRecipients = array();



    /**
     * Constructor
     */
    function __construct($intAction, $intEntryId, $name)
    {
        global $objDatabase, $_CONFIG;

        parent::__construct('.', $name);
        $this->intAction = intval($intAction);
        $this->intEntryId = intval($intEntryId);

        $objRSCheckAction = $objDatabase->Execute("SELECT default_recipient, need_auth FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_mail_actions WHERE id='".$this->intAction."' LIMIT 1");
        if ($objRSCheckAction !== false) {
            $this->intNeedAuth = $objRSCheckAction->fields['need_auth'];

            $objRSEntryUserId = $objDatabase->Execute("SELECT added_by FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_entries WHERE id='".$this->intEntryId."' LIMIT 1");

            $objFWUser = \FWUser::getFWUserObject();
            if(!$this->objUser = $objFWUser->objUser->getUser($id = intval($objRSEntryUserId->fields['added_by']))) {
                $this->objUser = false;
            }

            if($objRSCheckAction->fields['default_recipient'] == 'admin') {
                $this->arrRecipients[] = $_CONFIG['coreAdminEmail'];
            } else {
                if($this->objUser != false) {
                    $this->arrRecipients[] = $this->objUser->getEmail();
                }
            }
        }

        if(!empty($this->arrRecipients)) {
            self::loadTemplate();

            if(!empty($this->strTemplate) && !empty($this->strTitle)) {
                self::parsePlaceholders();
                self::sendMail();
            }
        }
    }



    function loadTemplate()
    {
        global $objDatabase;

        $objRSLoadTemplate = $objDatabase->Execute("SELECT
                                                        title, content, recipients
                                                    FROM
                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_mails
                                                    WHERE
                                                        action_id='".$this->intAction."'
                                                    AND
                                                        lang_id='" . static::getOutputLocale()->getId() . "'
                                                    AND
                                                        active='1'
                                                    LIMIT 1");

        if ($objRSLoadTemplate !== false) {
            if ($objRSLoadTemplate->RecordCount() == 0) {
                $objRSLoadTemplate = $objDatabase->Execute("SELECT
                                                        title, content, recipients
                                                    FROM
                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_mails
                                                    WHERE
                                                        action_id='".$this->intAction."'
                                                    AND
                                                        is_default='1'
                                                    AND
                                                        active='1'
                                                    LIMIT 1");
            }
            if ($objRSLoadTemplate !== false) {
                $this->strTitle = $objRSLoadTemplate->fields['title'];
                $this->strTemplate = $objRSLoadTemplate->fields['content'];

                $arrRecipients = explode(";", $objRSLoadTemplate->fields['recipients']);
                $this->arrRecipients = array_merge($this->arrRecipients, $arrRecipients);
            }
        }
    }



    function parsePlaceholders()
    {
        global $objDatabase, $_CONFIG;

        if($this->objUser != false) {
            $strUserNick = $this->objUser->getUsername();
            $strUserFirstname = $this->objUser->getProfileAttribute('firstname');
            $strUserLastname = $this->objUser->getProfileAttribute('lastname');
        }

        $objRSEntryFormId = $objDatabase->Execute("SELECT form_id FROM
                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_entries
                                                    WHERE
                                                        id='".$this->intEntryId."'
                                                    LIMIT 1");
        if ($objRSEntryFormId !== false) {
            $intEntryFormId = intval($objRSEntryFormId->fields['form_id']);
        }

        $strRelQuery = "SELECT inputfield.`id` AS `id` FROM ".DBPREFIX."module_".$this->moduleTablePrefix."_inputfields AS inputfield WHERE (inputfield.`type` != 16 AND inputfield.`type` != 17) AND (inputfield.`form` = ".$intEntryFormId.") ORDER BY inputfield.`order` ASC LIMIT 1";

        $objRSEntryTitle = $objDatabase->Execute("SELECT
                                                        rel_inputfield.`value` AS `value`
                                                    FROM
                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_entries AS entry,
                                                        ".DBPREFIX."module_".$this->moduleTablePrefix."_rel_entry_inputfields AS rel_inputfield
                                                    WHERE (rel_inputfield.`entry_id`='".$this->intEntryId."')
                                                    AND (rel_inputfield.`field_id` = (".$strRelQuery."))
                                                    AND (rel_inputfield.`lang_id` = '" . static::getOutputLocale()->getId() . "')
                                                    AND (rel_inputfield.`value` != '')
                                                    GROUP BY value
                                                    ");
        if ($objRSEntryTitle !== false) {
            $strEntryTitle = $objRSEntryTitle->fields['value'];
        }

        $objEntry = new MediaDirectoryEntry($this->moduleName);

        // note: if option 'settingsConfirmNewEntries' is set to true
        // and we are currently processing the notification emails
        // being triggered after a new entry has been submitted in the
        // frontend, then the newly submitted entry won't be loaded by
        // MediaDirectoryEntry::getEntries() as this method does only
        // find confirmed entries. Where as the newly submitted
        // entry is not yet confirmed.
        // However this is fine, as the loaded entry will only be used
        // to fetch its frontend-link. The latter should not be available
        // as long as the entry has not yet been confirmed.
        $objEntry->getEntries($this->intEntryId);

        $strDetailUrl = '';
        try {
            $detailUrl = $objEntry->getDetailUrl(true);
            if ($detailUrl) {
                $strDetailUrl = $detailUrl->toString();
            }
        } catch (MediaDirectoryEntryException $e) {}

        $strProtocol = ASCMS_PROTOCOL;
        $strDomain = $_CONFIG['domainUrl'].\Cx\Core\Core\Controller\Cx::instanciate()->getWebsiteOffsetPath();
        $strDate = date(ASCMS_DATE_FORMAT);

        $arrPlaceholder = array('[[USERNAME]]', '[[FIRSTNAME]]', '[[LASTNAME]]', '[[TITLE]]', '[[LINK]]', '[[URL]]', '[[DATE]]');
        $arrReplaceContent = array($strUserNick, $strUserFirstname, $strUserLastname, $strEntryTitle, $strDetailUrl, $strDomain, $strDate);

        for ($x = 0; $x < 7; $x++) {
            $this->strTitle = str_replace($arrPlaceholder[$x], $arrReplaceContent[$x], $this->strTitle);
        }

        for ($x = 0; $x < 7; $x++) {
            $this->strTemplate = str_replace($arrPlaceholder[$x], $arrReplaceContent[$x], $this->strTemplate);
        }
    }



    function sendMail()
    {
        global $_CONFIG;
        
        $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();

        $objMail->SetFrom($_CONFIG['coreAdminEmail'], $_CONFIG['coreGlobalPageTitle']);
        $objMail->Subject = $this->strTitle;
        $objMail->IsHTML(false);
        $objMail->Body = $this->strTemplate;

        foreach ($this->arrRecipients as $key => $strMailAdress) {
            if(!empty($strMailAdress)) {
                $objMail->AddAddress($strMailAdress);
                $objMail->Send();
                $objMail->ClearAddresses();
            }
        }
    }
}
