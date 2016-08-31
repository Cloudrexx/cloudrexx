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
 * CrmVcard Class CRM
 * A class to generate vCards for contact data.
 *
 * @category   CrmVcard
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */

namespace Cx\Modules\Crm\Controller;

/**
 * CrmVcard Class CRM
 * A class to generate vCards for contact data.
 *
 * @category   CrmVcard
 * @package    cloudrexx
 * @subpackage module_crm
 * @author     SoftSolutions4U Development Team <info@softsolutions4u.com>
 * @copyright  2012 and CLOUDREXX CMS - CLOUDREXX AG
 * @license    trial license
 * @link       www.cloudrexx.com
 */
class CrmVcard
{
  /**
  * log
  *
  * @access private
  * @var String
  */
  var $log;

  /**
  * array of this vcard's contact data
  *
  * @access private
  * @var array
  */
  var $data;

  /**
  * filename for download file naming
  *
  * @access private
  * @var String
  */
  var $filename;

  /**
  * PUBLIC, PRIVATE, CONFIDENTIAL
  *
  * @access private
  * @var String
  */
  var $class;

  /**
  * revision_date
  *
  * @access private
  * @var String
  */
  var $revision_date;

  /**
  * card
  *
  * @access private
  * @var String
  */
  var $card;

  /**
   * The class constructor. You can set some defaults here if desired.
   *
   * @return boolean
   */
  function vcard()
  {
    $this->log = "New vcard() called<br />";
    $this->data = array(
      "display_name"=>null
      ,"first_name"=>null
      ,"last_name"=>null
      ,"customer_name"=>null
      ,"customer_id"=>null
      ,"additional_name"=>null
      ,"name_prefix"=>null
      ,"name_suffix"=>null
      ,"nickname"=>null
      ,"title"=>null
      ,"role"=>null
      ,"department"=>null
      ,"company"=>null
      ,"work_po_box"=>null
      ,"work_extended_address"=>null
      ,"work_address"=>null
      ,"work_city"=>null
      ,"work_state"=>null
      ,"work_country"=>null
      ,"work_postal_code"=>null
      ,"work_tele"=>null
      ,"home_po_box"=>null
      ,"home_extended_address"=>null
      ,"home_address"=>null
      ,"home_city"=>null
      ,"home_state"=>null
      ,"home_postal_code"=>null
      ,"home_tele"=>null
      ,"home_country"=>null
      ,"office_tel"=>null
      ,"home_tel"=>null
      ,"cell_tel"=>null
      ,"fax_tel"=>null
      ,"fax_home"=>null
      ,"pager_tel"=>null
      ,"email1"=>null
      ,"email2"=>null
      ,"work_url"=>null
      ,"home_url"=>null
      ,"photo"=>null
      ,"birthday"=>null
      ,"timezone"=>null
      ,"sort_string"=>null
      ,"note"=>null
      );
    return true;
  }

  /**
   * build() method checks all the values, builds appropriate defaults for missing values, generates the vcard data string.
   */
  function build()
  {
    $this->log .= "vcard build() called<br />";
    /*
    For many of the values, if they are not passed in, we set defaults or
    build them based on other values.
    */
    if (!$this->class) { $this->class = "PUBLIC"; }
    if (!$this->data['display_name']) {
      $this->data['display_name'] = trim($this->data['first_name']." ".$this->data['last_name']);
    }
    if (!$this->data['sort_string']) { $this->data['sort_string'] = $this->data['last_name']; }
    if (!$this->data['sort_string']) { $this->data['sort_string'] = $this->data['company']; }
    if (!$this->data['timezone']) { $this->data['timezone'] = date("O"); }
    if (!$this->revision_date) { $this->revision_date = date('Y-m-d H:i:s'); }

      $this->card = "BEGIN:VCARD\r\n";
    $this->card .= "VERSION:3.0\r\n";
    $this->card .= "CLASS:".$this->class."\r\n";
    $this->card .= "PRODID:-//NONSGML Version 1//EN\r\n";
    $this->card .= "REV:".$this->revision_date."\r\n";
      $this->card .= "FN:".$this->data['display_name']."\r\n";
    $this->card .= "N:"
      .$this->data['last_name'].";"
      .$this->data['first_name'].";"
      .$this->data['customer_name'].";"
      .$this->data['customer_id'].";"
      .$this->data['additional_name'].";"
      .$this->data['name_prefix'].";"
      .$this->data['name_suffix']."\r\n";
    if ($this->data['nickname']) { $this->card .= "NICKNAME:".$this->data['nickname']."\r\n"; }
      if ($this->data['title']) { $this->card .= "TITLE:".$this->data['title']."\r\n"; }
      if ($this->data['company']) { $this->card .= "ORG:".$this->data['company']; }
      if ($this->data['department']) { $this->card .= ";".$this->data['department']; }
      $this->card .= "\r\n";

      if ($this->data['work_po_box']
    || $this->data['work_extended_address']
    || $this->data['work_address']
    || $this->data['work_city']
    || $this->data['work_state']
    || $this->data['work_postal_code']
    || $this->date['work_tele']
    || $this->data['work_country']) {
      $this->card .= "ADR;TYPE=work:"
        .$this->data['work_po_box'].";"
        .$this->data['work_extended_address'].";"
        .$this->data['work_address'].";"
        .$this->data['work_city'].";"
        .$this->data['work_state'].";"
        .$this->data['work_postal_code'].";"
        .$this->data['work_country']."\r\n";
    }
      if ($this->data['home_po_box']
    || $this->data['home_extended_address']
    || $this->data['home_address']
    || $this->data['home_city']
    || $this->data['home_state']
    || $this->data['home_postal_code']
    || $this->data['home_tele']
    || $this->data['home_country']) {
      $this->card .= "ADR;TYPE=home".$this->data['home_pref'].":"
        .$this->data['home_po_box'].";"
        .$this->data['home_extended_address'].";"
        .$this->data['home_address'].";"
        .$this->data['home_city'].";"
        .$this->data['home_state'].";"
        .$this->data['home_postal_code'].";"
        .$this->data['home_country']."\r\n";
    }
    if ($this->data['email1'])      { $this->card .= "EMAIL;TYPE=internet,pref:".$this->data['email1']."\r\n"; }
    if ($this->data['email2'])      { $this->card .= "EMAIL;TYPE=internet:".$this->data['email2']."\r\n"; }

    if ($this->data['office_tel'])  { $this->card .= "TEL;TYPE=work,voice:".$this->data['office_tel']."\r\n"; }
    if ($this->data['home_tel'])    { $this->card .= "TEL;TYPE=home,voice:".$this->data['home_tel']."\r\n"; }

    if ($this->data['cell_tel'])    { $this->card .= "TEL;TYPE=cell,voice:".$this->data['cell_tel']."\r\n"; }

    if ($this->data['fax_tel'])     { $this->card .= "TEL;TYPE=work,fax:".$this->data['fax_tel']."\r\n"; }
    if ($this->data['fax_home'])     { $this->card .= "TEL;TYPE=home,fax:".$this->data['fax_home']."\r\n"; }

    if ($this->data['pager_tel'])   { $this->card .= "TEL;TYPE=work,pager:".$this->data['pager_tel']."\r\n"; }

    if ($this->data['work_url'])    { $this->card .= "URL;TYPE=work:".$this->data['work_url']."\r\n"; }
    if ($this->data['home_url'])    { $this->card .= "URL;TYPE=home:".$this->data['home_url']."\r\n"; }

    if ($this->data['birthday'])    { $this->card .= "BDAY:".$this->data['birthday']."\r\n"; }

    if ($this->data['role'])        { $this->card .= "ROLE:".$this->data['role']."\r\n"; }
    if ($this->data['note'])        { $this->card .= "NOTE:".$this->data['note']."\r\n"; }
    $this->card .= "TZ:".$this->data['timezone']."\r\n";
    $this->card .= "END:VCARD\r\n";
  }

  /*
  download() method streams the vcard to the browser client.
  */
  /**
   * download() method streams the vcard to the browser client.
   *
   * @return boolean
   */
  function download()
  {
    $this->log .= "vcard download() called<br />";
    if (!$this->card) { $this->build(); }
    if (!$this->filename) { $this->filename = trim($this->data['display_name']); }
    $this->filename = str_replace(" ", "_", $this->filename);
      header("Content-Type: text/x-vcard; charset=utf-8");
      header("Content-Disposition: attachment; filename=".$this->filename.".vcf");
      header("Pragma: public");
      echo $this->card;
    return true;
  }
}
