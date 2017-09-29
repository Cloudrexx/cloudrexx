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
 * Immo management
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @version     1.0.0
 * @package     cloudrexx
 * @subpackage  module_immo
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Real-Estate management module
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author      Cloudrexx Development Team <info@cloudrexx.com>
 * @access      public
 * @package     cloudrexx
 * @subpackage  module_immo
 * @version     1.0.0
 * @todo        Extend the module_immo_settings table for even more
 *              customization, remove hardcoded stuff
 */

class Immo extends ImmoLib
{
    /**
     * PEAR Sigma Template object
     * @var      \Cx\Core\Html\Sigma
     * @access   private
     * @see      /lib/PEAR/HTML/*
     * @link     http://pear.php.net/package/\Cx\Core\Html\Sigma
     */
    private $_objTpl;

    public $_arrPriceFormat = array(
        1 => array(
              'dec' => 0,
              'dec_sep' => ",",
              'thousand_sep' => "'",
        ),
        2 => array(
              'dec' => 0,
              'dec_sep' => ",",
              'thousand_sep' => "'",
        ),
    );

    /**
     * frontend CMS language
     * @var     integer
     * @access  public
     */
    public $frontLang;

    /**
     * default currency suffix for price values
     * @var     string
     * @access  private
     */
    private $_currencySuffix = '.-';

    /**
     * Number of entries for listing
     * @var integer
     * @see $this->_getListing()
     */
    public $_listingCount = 3;

    /**
     * array holding prices to determine the smallest value
     * @var     integer
     * @access  private
     */
    private $_priceFields = array();

    /**
     * Constructor
     * @global mixed   $objDatabase ADODB abstraction layer object
     * @param  string  $pageContent HTML template (@see /index.php)
     */
    function __construct($pageContent)
    {
        global $objDatabase;
        $this->frontLang = (isset($_GET['immoLang'])) ? intval($_GET['immoLang']) : 1;
        $objRS=$objDatabase->Execute("    SELECT count(1) as cnt FROM ".DBPREFIX."module_immo_fieldname WHERE
                                        lang_id = 1 AND lower(name) LIKE '%aufzählung%'");
        $this->_listingCount = $objRS->fields['cnt'];
        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);
        $this->_objTpl->setTemplate($pageContent);

        // initialise the session array
        if (!isset($_SESSION['immo'])) {
            $_SESSION['immo'] = array();
        }

        parent::__construct();
    }

    /**
     * Get content page
     * @access public
     */
    function getPage()
    {
        if (!isset($_GET['cmd'])) {
            $_GET['cmd'] = '';
        }

        if (isset($_GET['standalone']) && !empty($_GET['standalone'])) {
            $this->_showMap();
        } elseif (isset($_GET['img'])) {
            $this->_showImageViewer();
        } else {
            switch ($_GET['cmd']) {
                case 'map':
                    $this->_loadIFrame();
                    break;
                case 'quickSearch':
                    $this->_quickSearch();
                    break;
                case 'detailSearch':
                    $this->_detailSearch();
                    break;
                case 'immolist':
                    $this->_showImmoList();
                    break;
                case 'showmapoverview':
                    $this->_showMap();
                    break;
                case 'showObj':
                    $this->_showObject();
                    break;
                case 'interest':
                    $this->_showInterestForm();
                    break;
                case 'submitContact':
                    $this->_addContact();
                    break;
                case 'getPDF':
                    $this->_getPDF();
                    break;
                default:
                    $this->_showOverview();
            }
        }
        return $this->_objTpl->get();
    }


    /**
     * return Headline for pagetitle
     * @return string pagetitle
     */
    function getPageTitle()
    {
        return $this->_getFieldFromText('Kopfzeile');
    }


    /**
     * Standalone code for showing the images
     */
    function _showImageViewer()
    {
        $this->_objTpl->loadTemplateFile("modules/immo/template/frontend_images_viewer.html");
        $this->_objTpl->setVariable('CONTREXX_CHARSET', CONTREXX_CHARSET);
        $immoID = intval($_GET['id']);
        $images = $this->_getImagesFromObject($immoID);
        foreach ($images as $index => $image) {
            $this->_objTpl->setVariable(array(
                'IMMO_STYLE_NAME' => $this->_styleName,
                'IMMO_IMAGE_INDEX' => $index,
                'IMMO_IMAGE_SRC' => $image['imgsrc'],
                'IMMO_IMAGE_WIDTH' => $image['width'],
                'IMMO_IMAGE_HEIGHT' => $image['height'],
                'IMMO_IMAGE_NAME' => $image['name'],
                'IMMO_IMAGE_CONTENT' => str_replace("\r\n", "", $image['content']),
                'IMMO_CURRENT_INDEX' => intval($_GET['index']),
                'IMMO_IMAGE_FIELD_ID' => $image['field_id'],
            ));
            $this->_objTpl->parse('imagesArray');
            $this->_objTpl->parse('indexArray');
        }
        $this->_objTpl->show();
        die();
    }


    /**
     * quick search handling
     */
    function _quickSearch() {
        switch (intval($_GET['step'])) {
            case 2:
                $this->_showQuickSearch1();
                $this->_showQuickSearch2();
                break;
            default:
                $this->_showQuickSearch1();
        }
    }


    /**
     * shows the quick search form (step_1)
     */
    function _showQuickSearch1()
    {
        global $objDatabase;

        if ($this->_objTpl->blockExists('step_2')) {
            $this->_objTpl->hideblock('step_2');
        }

        $this->_objTpl->setVariable(array(
            'IMMO_SEARCH_ACTION' => 'index.php?section=immo&cmd=quickSearch&step=2',
            'IMMO_RADIO_CHECKED_'.
                ( !empty($_POST['cat']) && $_POST['cat'] == 'business' ? 'BUSINESS' : 'RESIDENCE' ) => 'checked="checked"',
        ));
        $this->_objTpl->parse('step_1');
    }


    /**
     * shows the quick search form (step_2)
     */
    function _showQuickSearch2()
    {
        if ($this->_objTpl->blockExists('step_1')) {
            $this->_objTpl->touchBlock('step_1');
        }

        $this->_objTpl->setVariable(array(
            'IMMO_SEARCH_ACTION' => 'index.php?section=immo&cmd=immolist',
        ));

        //buy
        //get buy info
        foreach ($this->categories as $id => $category) {
            $this->_objTpl->setVariable(array(
                'IMMO_CHECKBOX_ID' => $id,
                'IMMO_CHECKBOX_NAME' => 'rent',
                'IMMO_CHECKBOX_VALUE' => $category,
                'IMMO_CHECKBOX_CHECKED' => $_SESSION['immo']['search']['cat_rent'][$category] ? 'checked="checked"' : '',
                'IMMO_CHECKBOX_LABEL' => $category
            ));
            $this->_objTpl->parse('buyResult');
        }

        //rent
        //get rent info
        foreach ($this->categories as $id => $category) {
            $this->_objTpl->setVariable(array(
                'IMMO_CHECKBOX_ID' => $id,
                'IMMO_CHECKBOX_NAME' => 'rent',
                'IMMO_CHECKBOX_VALUE' => $category,
                'IMMO_CHECKBOX_CHECKED' => $_SESSION['immo']['search']['cat_rent'][$category] ? 'checked="checked"' : '',
                'IMMO_CHECKBOX_LABEL' => $category
            ));
            $this->_objTpl->parse('rentResult');
        }
        $this->_objTpl->parse('step_2');
    }


    /**
     * detail search handling
     */
    function _detailSearch()
    {
        if (!empty($_GET['step'])) {
            if (!empty($_GET['noAJAX'])) {
                switch ($_GET['step']) {
                    case 2:
                        $this->_showDetailSearchNoAJAX2();
                        break;
                    case 3:
                        $this->_showDetailSearchNoAJAX23();
                        break;
                    default:
                        $this->_showDetailSearchNoAJAX21();
                }
            } else {
                switch ($_GET['step']) {
                    case 2:
                        $this->_showDetailSearch2();
                        break;
                    case 3:
                        $this->_showDetailSearch3();
                        break;
                    default:
                        $this->_showDetailSearch1();
                }
            }
        }
    }


    /**
     * Show the form when someone is interested
     * @return unknown
     */
    function _showInterestForm()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        if (!empty($_REQUEST['immoid'])) {
            $this->_objTpl->setVariable('IMMO_ID', intval($_REQUEST['immoid']));
        }

        if (!empty($_REQUEST['submitContactForm'])) {
            $immoid = intval($_REQUEST['contactFormField_immoid']);//hidden field: immoid
            $name = !empty($_REQUEST['contactFormField_name']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_name'])) : '';
            $firstname = !empty($_REQUEST['contactFormField_vorname']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_vorname'])) : '';
            $street = !empty($_REQUEST['contactFormField_strasse']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_strasse'])) : '';
            $zip = !empty($_REQUEST['contactFormField_postleitzahl']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_postleitzahl'])) : '';
            $location = !empty($_REQUEST['contactFormField_ortschaft']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_ortschaft'])) : '';
            $email = !empty($_REQUEST['contactFormField_email']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_email'])) : '';
            $phone_office = !empty($_REQUEST['contactFormField_fongeschaeft']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_fongeschaeft'])) : '';
            $phone_home = !empty($_REQUEST['contactFormField_fonprivat']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_fonprivat'])) : '';
            $phone_mobile = !empty($_REQUEST['contactFormField_fonmobil']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_fonmobil'])) : '';
            $doc_via_mail = !empty($_REQUEST['contactFormField_dokuperpost']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_dokuperpost'])) : '';
            $funding_advice = !empty($_REQUEST['contactFormField_beratungfinanzierung']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_beratungfinanzierung'])) : '';
            $inspection = !empty($_REQUEST['contactFormField_besichtigung']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_besichtigung'])) : '';
            $contact_via_phone = !empty($_REQUEST['contactFormField_kontakttelefon']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_kontakttelefon'])) : '';
            $comment = !empty($_REQUEST['contactFormField_bemerkungen']) ? contrexx_addslashes(contrexx_strip_tags($_REQUEST['contactFormField_bemerkungen'])) : '';

            $query = "
                INSERT INTO ".DBPREFIX."module_immo_interest VALUES (
                       NULL, $immoid, '$name', '$firstname',
                       '$street', '$zip', '$location', '$email',
                       '$phone_office', '$phone_home', '$phone_mobile',
                       '$doc_via_mail', '$funding_advice', '$inspection',
                       '$contact_via_phone', '$comment', ".mktime().")";
            if (!$objDatabase->Execute($query)) {
                $this->_objTpl->setVariable('CONTACT_FEEDBACK_TEXT', $_ARRAYLANG['TXT_IMMO_DATABASE_ERROR']);
                return false;
            }
            $query = "
                SELECT reference, ref_nr_note
                  FROM ".DBPREFIX."module_immo
                 WHERE id=$immoid";
            $objRS = $objDatabase->Execute($query);
            if ($objRS) {
                $reference = $objRS->fields['reference'];
                $ref_note = $objRS->fields['ref_nr_note'];
            }
            //set immo ID for _getFieldFromText function
            $this->_getFieldNames($immoid);
            $this->_currFieldID = $immoid;

            $address = $this->_getFieldFromText('adresse');
            $location = $this->_getFieldFromText('ort');

            $mailer = new Cx\Core\MailTemplate\Model\Entity\Mail();
            $objRS = $objDatabase->Execute('
                SELECT setvalue
                  FROM '.DBPREFIX.'module_immo_settings
                 WHERE setname="contact_receiver"');
            //set recipients
            $emails = explode(',', $objRS->fields['setvalue']);
            foreach ($emails as $email) {
                $mailer->AddAddress($email);
            }

            $mailer->SetFrom(contrexx_addslashes($_REQUEST['contactFormField_email']), 'Interessent');
            $mailer->Subject = 'Neuer Interessent für '.$ref_note.' Ref-Nr.: '.$reference;
            $mailer->IsHTML(false);
            $mailer->Body = 'Jemand interessiert sich für das Objekt '.$ref_note.' Ref-Nr.: '.$reference."\n \nhttp://".$_CONFIG['domainUrl'].ASCMS_PATH_OFFSET."/admin/index.php?cmd=immo&act=stats\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_E_MAIL'].': '.contrexx_addslashes($_REQUEST['contactFormField_email'])."\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_NAME'].': '.$name."\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_FIRSTNAME'].': '.$firstname."\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_STREET'].': '.$street."\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_LOCATION'].': '.$location."\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_TELEPHONE'].': '.$phone_home."\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_TELEPHONE_OFFICE'].': '.$phone_office."\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_TELEPHONE_MOBILE'].': '.$phone_mobile."\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_DOC_VIA_MAIL'].': '.(($doc_via_mail) ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'])."\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_FUNDING_ADVICE'].': '.(($funding_advice) ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'])."\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_CONTACT_FOR_INSPECTION'].': '.(($inspection) ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'])."\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_CONTACT_VIA_PHONE'].': '.(($contact_via_phone) ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'])."\n";
            $mailer->Body .= $_ARRAYLANG['TXT_IMMO_COMMENTS'].': '.$comment."\n";
            $mailer->Send();

            //mail for interested customer
            $mailer->ClearAddresses();
            $mailer->SetFrom($this->arrSettings['sender_email'], $this->arrSettings['sender_name']);
            $mailer->AddAddress($_REQUEST['contactFormField_email']);
            $mailer->Subject = $this->arrSettings['interest_confirm_subject'];
            $message = str_replace('[[IMMO_OBJECT]]', $address.', '.$location." (Ref.Nr.: $reference)", $this->arrSettings['interest_confirm_message']);
            $mailer->Body = $message;
            $mailer->Send();
            $this->_objTpl->setVariable('CONTACT_FEEDBACK_TEXT', $_ARRAYLANG['TXT_IMMO_CONTACT_SUCCESSFUL']);
        }
        return true;
    }


    /**
     * return array of images for corresponding immoid
     * @param int immo ID
     * @return array images
     */
    function _getImagesFromObject($id)
    {
        global $objDatabase;

        $query = "
            SELECT img.field_id as field_id, content.fieldvalue AS content, name.name, img.uri AS imgsrc
              FROM ".DBPREFIX."module_immo_content AS content
              LEFT JOIN ".DBPREFIX."module_immo_fieldname AS name
                  ON content.field_id = name.field_id
              LEFT JOIN ".DBPREFIX."module_immo_image AS img
                  ON content.immo_id = img.immo_id
              AND content.field_id = img.field_id
              WHERE content.field_id
              IN (
                  SELECT id
                  FROM `".DBPREFIX."module_immo_field`
                  WHERE TYPE = 'img'
                  OR TYPE = 'panorama'
              )
              AND content.immo_id = ".$id."
              AND content.lang_id = ".$this->frontLang."
              AND content.active = 1
              AND name.lang_id = ".$this->frontLang;
        $index = 0;
        $images = array();
        if (($objRS = $objDatabase->Execute($query)) !== false) {
            while(!$objRS->EOF) {
                $images[$index] = $objRS->fields;
                $dim = $this->_getImageDim($images[$index]['imgsrc']);
                $images[$index]['width'] = $dim[1];
                $images[$index]['height'] = $dim[2];
                $index++;
                $objRS->MoveNext();
            }
            return $images;
        }
        return false;
    }


    /**
     * show the main form for searching objects
     * @return void
     */
    function _showOverview()
    {
        global $_ARRAYLANG;

        $this->_objTpl->setVariable(array(
            'TXT_IMMO_RESET' => $_ARRAYLANG['TXT_IMMO_RESET'],
            'TXT_IMMO_SEARCH' => $_ARRAYLANG['TXT_IMMO_SEARCH'],
            'TXT_IMMO_REF_NR' => $_ARRAYLANG['TXT_IMMO_REF_NR'],
            'TXT_IMMO_LOCATIONS' => $_ARRAYLANG['TXT_IMMO_LOCATIONS'],
            'TXT_IMMO_LOCATION' => $_ARRAYLANG['TXT_IMMO_LOCATION'],
            'TXT_IMMO_OBJECTTYPE_FLAT' => $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_FLAT'],
            'TXT_IMMO_OBJECTTYPE_HOUSE' => $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_HOUSE'],
            'TXT_IMMO_OBJECTTYPE_MULTIFAMILY' => $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_MULTIFAMILY'],
            'TXT_IMMO_OBJECTTYPE_ESTATE' => $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_ESTATE'],
            'TXT_IMMO_OBJECTTYPE_INDUSTRY' => $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_INDUSTRY'],
            'TXT_IMMO_OBJECTTYPE_PARKING' => $_ARRAYLANG['TXT_IMMO_OBJECTTYPE_PARKING'],
            'TXT_IMMO_OBJECT_TYPE' => $_ARRAYLANG['TXT_IMMO_OBJECT_TYPE'],
            'TXT_IMMO_PROPERTYTYPE_PURCHASE' => $_ARRAYLANG['TXT_IMMO_PROPERTYTYPE_PURCHASE'],
            'TXT_IMMO_PROPERTYTYPE_RENT' => $_ARRAYLANG['TXT_IMMO_PROPERTYTYPE_RENT'],
            'TXT_IMMO_PROPERTY_TYPE' => $_ARRAYLANG['TXT_IMMO_PROPERTY_TYPE'],
            'TXT_IMMO_SPECIAL_OFFERS' => $_ARRAYLANG['TXT_IMMO_SPECIAL_OFFERS'],
            'TXT_IMMO_SEARCH_STYLE' => $_ARRAYLANG['TXT_IMMO_SEARCH_STYLE'],
            'TXT_IMMO_FULLTEXT_SEARCH' => $_ARRAYLANG['TXT_IMMO_FULLTEXT_SEARCH'],
            'TXT_IMMO_PRICE' => $_ARRAYLANG['TXT_IMMO_PRICE'],
            'TXT_IMMO_ROOMS' => $_ARRAYLANG['TXT_IMMO_ROOMS'],
            'TXT_IMMO_FROM' => $_ARRAYLANG['TXT_IMMO_FROM'],
            'TXT_IMMO_TO' => $_ARRAYLANG['TXT_IMMO_TO'],
            'TXT_IMMO_NEW_BUILDING' => $_ARRAYLANG['TXT_IMMO_NEW_BUILDING'],
            'TXT_IMMO_YES' => $_ARRAYLANG['TXT_IMMO_YES'],
            'TXT_IMMO_NO' => $_ARRAYLANG['TXT_IMMO_NO'],
            'TXT_IMMO_ORDER_BY' => $_ARRAYLANG['TXT_IMMO_ORDER_BY'],
            'TXT_IMMO_FOREIGNER_AUTHORIZATION' => $_ARRAYLANG['TXT_IMMO_FOREIGNER_AUTHORIZATION'],
            'TXT_IMMO_LOGO' => $_ARRAYLANG['TXT_IMMO_LOGO'],
            'IMMO_IMMO_JAVASCRIPT' => $this->_getImmoJS(),
        ));
        $locations = $this->_getLocations();
        foreach ($locations as $location) {
            $this->_objTpl->setVariable(
                'IMMO_LOCATION_CONTENT', $location
            );
            $this->_objTpl->parse("locations");
        }
        $this->_showSpecialOffers();
    }


    /**
     * return array of locations from database
     * @return array locations
     */
    function _getLocations()
    {
        global $objDatabase;

        $query = "
            SELECT TRIM(a.fieldvalue) as location FROM ".DBPREFIX.'module_immo_content AS a
             WHERE a.field_id = (
                   SELECT field_id
                   FROM '.DBPREFIX.'module_immo_fieldname
                   WHERE lower( name ) = "ort"
                   AND lang_id = 1 )
               AND a.lang_id = '.$this->frontLang.'
             GROUP BY location ';
        $objRS = $objDatabase->Execute($query);
        if ($objRS) {
            while(!$objRS->EOF) {
                $locations[] = $objRS->fields['location'];
                $objRS->MoveNext();
            }
        }
        return $locations;
    }


    /**
     * display the specialoffers
     * @return void
     */
    function _showSpecialOffers()
    {
        $specialOffers = $this->_getSpecialOffers();
        foreach ($specialOffers as $specialOffer) {
            $img = $specialOffer['imgsrc'];
            $imgdim = $this->_getImageDim($img, 50);
            $this->_objTpl->setVariable(array(
                'IMMO_ID' => $specialOffer['immo_id'],
                'IMMO_SPECIAL_OFFER_IMG_SRC' => $specialOffer['imgsrc'],
                'IMMO_IMG_DIM' => $imgdim[0],
                'IMMO_SPECIAL_OFFER_HEADER' => $specialOffer['header'],
            ));
            $this->_objTpl->parse('specialOffersImg');
            $this->_objTpl->setVariable(array(
                'IMMO_SPECIAL_OFFER_HEADER' => $specialOffer['header'],
                'IMMO_SPECIAL_OFFER_PRICE_PREFIX' => htmlentities($this->arrSettings['currency_lang_'.$this->frontLang], ENT_QUOTES, CONTREXX_CHARSET),
                'IMMO_SPECIAL_OFFER_PRICE' => $specialOffer['price'],
                'IMMO_SPECIAL_OFFER_PRICE_SUFFIX' => $this->_currencySuffix,
                'IMMO_SPECIAL_OFFER_LOCATION' => $specialOffer['location'],
            ));
            $this->_objTpl->parse('specialOffersText');
        }
    }


    /**
     * get random special offers from the database and return them
     * @return array objects which are special offers
     */
    function _getSpecialOffers()
    {
        global $objDatabase;

        $query='SELECT     immo.id AS immo_id, reference, visibility, a.fieldvalue AS location,
                        b.fieldvalue AS price,
                        c.fieldvalue AS header,
                        img.uri      AS imgsrc
                FROM '.DBPREFIX.'module_immo AS immo
                LEFT JOIN '.DBPREFIX.'module_immo_content AS a ON ( immo.id = a.immo_id
                                                                AND a.field_id = (
                                                                    SELECT field_id
                                                                    FROM '.DBPREFIX.'module_immo_fieldname
                                                                    WHERE name = "ort"
                                                                    AND lang_id = 1 )
                                                                AND a.lang_id = '.$this->frontLang.' )
                LEFT JOIN '.DBPREFIX.'module_immo_content AS b ON ( immo.id = b.immo_id
                                                            AND b.field_id = (
                                                                SELECT field_id
                                                                FROM '.DBPREFIX.'module_immo_fieldname
                                                                WHERE name = "preis"
                                                                AND lang_id = 1 )
                                                            AND b.lang_id = '.$this->frontLang.' )
                LEFT JOIN '.DBPREFIX.'module_immo_content AS c ON ( immo.id = c.immo_id
                                                            AND c.field_id = (
                                                                SELECT field_id
                                                                FROM '.DBPREFIX.'module_immo_fieldname
                                                                WHERE name = "headline"
                                                                AND lang_id = 1 )
                                                            AND c.lang_id = '.$this->frontLang.' )
                LEFT JOIN '.DBPREFIX.'module_immo_image AS img ON ( immo.id = img.immo_id
                                                            AND img.field_id = (
                                                                SELECT field_id
                                                                FROM '.DBPREFIX.'module_immo_fieldname
                                                                WHERE name = "übersichtsbild" )
                                                            )
                WHERE special_offer = 1
                AND visibility != "disabled"
                ORDER BY rand()
                LIMIT 4';
        $objRS = $objDatabase->Execute($query);
        if ($objRS) {
            while(!$objRS->EOF) {
                $immos[] = $objRS->fields;
                $objRS->MoveNext();
            }
        }
        return $immos;
    }


    /**
     * function handling protected link requests
     * @return void
     */
    function _getPDF()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $objValidator = new FWValidator();
        $ids=explode('_',$_GET['id']);
        $immoID=intval($ids[0]);
        $fieldID=intval($ids[1]);
        if (isset($_POST['immo_id'])) {    //form was sent
            $name = !empty($_POST['name']) ? contrexx_addslashes(strip_tags($_POST['name'])) : '';
            $firstname = !empty($_POST['firstname']) ? contrexx_addslashes(strip_tags($_POST['firstname'])) : '';
            $company = !empty($_POST['company']) ? contrexx_addslashes(strip_tags($_POST['company'])) : '';
            $street = !empty($_POST['street']) ? contrexx_addslashes(strip_tags($_POST['street'])) : '';
            $zip = !empty($_POST['zip']) ? intval($_POST['zip']) : '';
            $location = !empty($_POST['location']) ? contrexx_addslashes(strip_tags($_POST['location'])) : '';
            $telephone = !empty($_POST['telephone']) ? contrexx_addslashes(strip_tags($_POST['telephone'])) : '';
            $telephone_office = !empty($_POST['telephone_office']) ? contrexx_addslashes(strip_tags($_POST['telephone_office'])) : '';
            $telephone_mobile = !empty($_POST['telephone_mobile']) ? contrexx_addslashes(strip_tags($_POST['telephone_mobile'])) : '';
            $purchase = isset($_POST['purchase']) ? 1 : 0;
            $funding = isset($_POST['funding']) ? 1 : 0;
            $email = !empty($_POST['email']) ? contrexx_addslashes(strip_tags($_POST['email'])) : '';
            $comment = !empty($_POST['comment']) ? contrexx_addslashes(strip_tags($_POST['comment'])) : '';
            $immoID = !empty($_POST['immo_id']) ? intval($_POST['immo_id']) : '';
            $fieldID = !empty($_POST['field_id']) ? intval($_POST['field_id']) : '';
            $error=0;
            if ($objValidator->isEmail($email)) {
                if (!empty($name) && !empty($telephone) && !empty($email) && $immoID > 0 && $fieldID > 0) {
                    $objRS = $objDatabase->SelectLimit("SELECT email
                                                FROM ".DBPREFIX."module_immo_contact
                                                WHERE immo_id = '$immoID'
                                                AND email = '$email'
                                                AND timestamp > ".(mktime() - 600), 1);
                    if ($objRS->RecordCount() > 0) {
                        $this->_objTpl->setVariable('TXT_IMMO_STATUS', '<span class="errmsg">'.$_ARRAYLANG['TXT_IMMO_ALREADY_SENT_RECENTLY'].'</span>');
                        $this->_showContactForm($immoID, $fieldID);
                        return false;
                    }

                    $objRS = $objDatabase->SelectLimit("SELECT fieldvalue
                                                FROM ".DBPREFIX."module_immo_content
                                                WHERE immo_id = '$immoID'
                                                AND field_id = '$fieldID'
                                                AND lang_id = '".$this->frontLang."'", 1);
                    if ($objRS) {
                        $link = 'http://'.$_CONFIG['domainUrl'].str_replace(" ", "%20", $objRS->fields['fieldvalue']);
                        $mailer = new Cx\Core\MailTemplate\Model\Entity\Mail();
                        $objDatabase->Execute("INSERT INTO ".DBPREFIX."module_immo_contact
                                                VALUES
                                                (NULL, '$email', '$name', '$firstname', '$street', '$zip', '$location', '$company', '$telephone', '$telephone_office', '$telephone_mobile', '$purchase', '$funding', '$comment', '$immoID', '$fieldID', ".mktime()." )");

                        $mailer->IsHTML(false);
                        $mailer->SetFrom($this->arrSettings['sender_email'], $this->arrSettings['sender_name']);
                        $mailer->Subject = $this->arrSettings['prot_link_message_subject'];
                        $mailer->Body = str_replace('[[IMMO_PROTECTED_LINK]]', $link, $this->arrSettings['prot_link_message_body'])."\n\n";
                        $mailer->AddAddress($email);
                        $mailer->Send();
                    } else {
                        $this->_objTpl->setVariable('TXT_IMMO_STATUS', '<span class="errmsg">DB error.</span>');
                    }
                } else {
                    $error=1;
                }
            } else {
                $error=1;
            }
            if ($error==1) {
                $this->_objTpl->setVariable('TXT_IMMO_STATUS', '<span class="errmsg">'.$_ARRAYLANG['TXT_IMMO_MISSIONG_OR_INVALID_FIELDS'].'</span>');
            } else {
                $this->_objTpl->setVariable('TXT_IMMO_STATUS', '<span class="okmsg">'.$_ARRAYLANG['TXT_IMMO_CONTACT_SUCCESSFUL'].'</span>');
            }
        }
        // else { //form was not sent }
        return $this->_showContactForm($immoID, $fieldID);
    }


    /**
     * show the contact form for the according immo- and field-ID
     * @param int $immoID
     * @param int $fieldID
     * @return void
     */
    function _showContactForm($immoID, $fieldID)
    {
        global $_ARRAYLANG;

        $this->_objTpl->setVariable(array(
            'IMMO_ID' => $immoID,
            'TXT_IMMO_FIELDS_REQUIRED' => $_ARRAYLANG['TXT_IMMO_FIELDS_REQUIRED'],
            'TXT_IMMO_NAME' => $_ARRAYLANG['TXT_IMMO_NAME'],
            'TXT_IMMO_FIRSTNAME' => $_ARRAYLANG['TXT_IMMO_FIRSTNAME'],
            'TXT_IMMO_STREET' => $_ARRAYLANG['TXT_IMMO_STREET'],
            'TXT_IMMO_ZIP' => $_ARRAYLANG['TXT_IMMO_ZIP'],
            'TXT_IMMO_COMPANY' => $_ARRAYLANG['TXT_IMMO_COMPANY'],
            'TXT_IMMO_PURCHASE' => $_ARRAYLANG['TXT_IMMO_PURCHASE'],
            'TXT_IMMO_FUNDING' => $_ARRAYLANG['TXT_IMMO_FUNDING'],
            'TXT_IMMO_TELEPHONE' => $_ARRAYLANG['TXT_IMMO_TELEPHONE'],
            'TXT_IMMO_TELEPHONE_OFFICE' => $_ARRAYLANG['TXT_IMMO_TELEPHONE_OFFICE'],
            'TXT_IMMO_TELEPHONE_MOBILE' => $_ARRAYLANG['TXT_IMMO_TELEPHONE_MOBILE'],
            'TXT_IMMO_LOCATION' => $_ARRAYLANG['TXT_IMMO_LOCATION'],
            'TXT_IMMO_COMMENTS' => $_ARRAYLANG['TXT_IMMO_COMMENTS'],
            'TXT_IMMO_DELETE' => $_ARRAYLANG['TXT_IMMO_DELETE'],
            'TXT_IMMO_SEND' => $_ARRAYLANG['TXT_IMMO_SEND'],
            'IMMO_FIELD_ID' => $fieldID,
        ));
    }


    /**
     * get the attribute list for the showObject page
     * @return void
     */
    function _getListing()
    {
        $listing = false;
        for($i=1; $i<=$this->_listingCount;$i++) {
            $list = $this->_getFieldFromText('Aufzählung'.$i);
            if (!empty($list)) {
                $listing = true;
                $this->_objTpl->setVariable('IMMO_LISTING', $this->_getFieldFromText('Aufzählung'.$i));
                $this->_objTpl->parse("listing_entry");
            }
        }
        if ($listing) {
            $this->_objTpl->parse('listing');
        }
    }


    /**
     * show the details of an object
     * @return void
     */
    function _showObject()
    {
        global $objDatabase, $_ARRAYLANG;

        if (!empty($_GET['id'])) {
            $immoID = intval($_GET['id']);
            if (empty($immoID)) {
                \Cx\Core\Csrf\Controller\Csrf::header('Location: ?section=immo&cmd=immolist');
                die();
            }
        }
        $this->_getFieldNames($immoID, $this->frontLang);
        if (($objRS = $objDatabase->SelectLimit('SELECT reference FROM '.DBPREFIX.'module_immo WHERE id='.$immoID, 1)) !== false) {
              $reference = $objRS->fields['reference'];
        }

        $this->_objTpl->setGlobalVariable(array(
            'TXT_IMMO_PRICE_PREFIX' => $this->arrSettings['currency_lang_'.$this->frontLang],
            'TXT_IMMO_PRICE_SUFFIX' => $this->_currencySuffix,
            'TXT_IMMO_SHOWMAP' => $_ARRAYLANG['TXT_IMMO_SHOWMAP'],
            'TXT_IMMO_PRINT_PAGE' => $_ARRAYLANG['TXT_IMMO_PRINT_PAGE'],
            'TXT_IMMO_BACK' => $_ARRAYLANG['TXT_IMMO_BACK'],
            'TXT_IMMO_CONTACT_FORM' => $_ARRAYLANG['TXT_IMMO_CONTACT_FORM'],
            'TXT_IMMO_HOMEPAGE_LINK' => $_ARRAYLANG['TXT_IMMO_HOMEPAGE_LINK'],
            'TXT_IMMO_SERVICE_LINKS' => $_ARRAYLANG['TXT_IMMO_SERVICE_LINKS'],
            'TXT_IMMO_GOTO_TOP' => $_ARRAYLANG['TXT_IMMO_GOTO_TOP'],
            'TXT_IMMO_TO_PICTURES' => $_ARRAYLANG['TXT_IMMO_TO_PICTURES'],
            'TXT_IMMO_TO_OBJECTDATA' => $_ARRAYLANG['TXT_IMMO_TO_OBJECTDATA'],
            'TXT_IMMO_TO_LINKS' => $_ARRAYLANG['TXT_IMMO_TO_LINKS'],
            'TXT_IMMO_PICTURES' => $_ARRAYLANG['TXT_IMMO_PICTURES'],
            'TXT_IMMO_OBJECTDATA' => $_ARRAYLANG['TXT_IMMO_OBJECTDATA'],
            'TXT_IMMO_LINKS' => $_ARRAYLANG['TXT_IMMO_LINKS'],
            'TXT_IMMO_TO_PLANS' => $_ARRAYLANG['TXT_IMMO_TO_PLANS'],
            'TXT_IMMO_TO_MAP' => $_ARRAYLANG['TXT_IMMO_TO_MAP'],
            'TXT_IMMO_INTERESTED_IN_OBJECT' => $_ARRAYLANG['TXT_IMMO_INTERESTED_IN_OBJECT'],
            'IMMO_ID' => $immoID,
            'IMMO_DETAILS_JAVASCRIPT' => $this->_getDetailsJS(),
        ));
        $img = $this->_getFieldFromText('Übersichtsbild', 'img');
        $imgOverviewKey = $this->_currFieldID;
        $imgdim = $this->_getImageDim($img, 540);
        $homepageLink = trim($this->_getFieldFromText('Link auf Homepage'));
        $homepageLink_active = $this->_getFieldFromText('Link auf Homepage', 'active');
        $this->_getListing();
        $this->_objTpl->setVariable(array(
            'IMMO_HEADER' => $this->_getFieldFromText('Kopfzeile'),
            'IMMO_ADDRESS' => $this->_getFieldFromText('Adresse'),
            'IMMO_REF_NR' => $reference,
            'IMMO_LOCATION' => $this->_getFieldFromText('Ort'),
            'IMMO_PRICE' => $this->_getFieldFromText('Preis'),
            'IMMO_DESCRIPTION' => $this->_getFieldFromText('Beschreibung'),
            'IMMO_HEADLINE' => $this->_getFieldFromText('Headline'),
            'IMMO_HOMEPAGE_LINK' => $homepageLink,
            'IMMO_IMG_DIM' => $imgdim[0],
            'IMMO_IMG_WIDTH' => $imgdim[1],
            'IMMO_IMG_HEIGHT' => $imgdim[2],
            'IMMO_IMG_SRC' => $img,
            'IMMO_ID' => $immoID,
            'IMMO_IMAGES_INDEX' => $imgOverviewKey,
        ));
        if ($homepageLink != '' && $homepageLink_active) {
            $this->_objTpl->parse("homepageLink");
        } else {
            $this->_objTpl->hideblock("homepageLink");
        }
        $this->_objTpl->parse("basicData");

        $imgRow = 1;
        $lnkRow = 1;
        $textcount = 0;
        $imagecount = 0;
        $linkcount = 0;
        foreach ($this->fieldNames as $fieldKey => $field) {
            if (    $field['content']['active'] == 1
            && !in_array($field['names'][1], $this->_usedFields)) {
                switch($field['type']) {
                    case 'text':
                    case 'textarea':
                    case 'digits_only':
                    case 'price':
                        $textcount++;
                        $this->_objTpl->setVariable(array(
                            'IMMO_FIELD_NAME' => htmlentities($field['names'][$this->frontLang], ENT_QUOTES, CONTREXX_CHARSET),
                            'IMMO_FIELD_CONTENT' => htmlentities($field['type'] == 'price' ? number_format($field['content'][$this->frontLang], $this->_arrPriceFormat[$this->frontLang]['dec'], $this->_arrPriceFormat[$this->frontLang]['dec_sep'], $this->_arrPriceFormat[$this->frontLang]['thousand_sep']) : $field['content'][$this->frontLang], ENT_QUOTES, CONTREXX_CHARSET),
                            'TXT_IMMO_CURRENCY_PREFIX' => $field['type'] == 'price' ? htmlentities($this->arrSettings['currency_lang_'.$this->frontLang], ENT_QUOTES, CONTREXX_CHARSET) : '',
                            'TXT_IMMO_CURRENCY_SUFFIX' => $field['type'] == 'price' ? $this->_currencySuffix : '',
                        ));
                        if (trim($field['content'][$this->frontLang]) != '') {
                            if ($textcount < $this->_fieldCount['text']) {
                                $this->_objTpl->touchBlock('textListHR');
                            }
                            $this->_objTpl->parse('textList');
                        }
                    break;

                    case 'img':
                        $imagecount++;
                        $img = trim($field['img']);
                        if (!empty($img)) {
                            $imgdim = $this->_getImageDim($img, 160);
                            $this->_objTpl->setVariable(array(
                                'IMMO_FIELD_NAME' => htmlentities($field['names'][$this->frontLang], ENT_QUOTES, CONTREXX_CHARSET),
                                'IMMO_FIELD_CONTENT' => htmlentities($field['content'][$this->frontLang], ENT_QUOTES, CONTREXX_CHARSET),
                                'IMMO_IMG_SRC' => $img,
                                'IMMO_IMG_WIDTH' => $imgdim[1],
                                'IMMO_IMG_HEIGHT' => $imgdim[2],
                                'IMMO_IMG_DIM' => $imgdim[0],
                                'IMMO_IMAGES_INDEX' => $fieldKey,
                            ));

                            if ($fieldKey == 125 ) {
                                $this->_objTpl->touchBlock("anchor_plan_images");
                            }
                            if ($imgRow++ % 3 == 0) {
                                if ($imagecount < $this->_fieldCount['img']) {
                                    $this->_objTpl->touchBlock('imageListHR');
                                }
                                $this->_objTpl->parse('imageList');
                                $this->_objTpl->parse('imageListRow');
                            } else {
                                $this->_objTpl->parse('imageList');
                            }


                        }
                    break;

                    case 'panorama':
                        $img = trim($field['img']);
                        if (!empty($img)) {
                            $imgdim = $this->_getImageDim($img, 530);
                            $this->_objTpl->setVariable(array(
                                'IMMO_FIELD_NAME' => htmlentities($field['names'][$this->frontLang], ENT_QUOTES, CONTREXX_CHARSET),
                                'IMMO_FIELD_CONTENT' => htmlentities($field['content'][$this->frontLang], ENT_QUOTES, CONTREXX_CHARSET),
                                'IMMO_IMG_SRC' => $img,
                                'IMMO_IMG_WIDTH' => $imgdim[1],
                                'IMMO_IMG_HEIGHT' => $imgdim[2],
                                'IMMO_IMG_DIM' => $imgdim[0],
                                'IMMO_IMAGES_INDEX' => $fieldKey,
                            ));
                            $this->_objTpl->parse('panorama');
                        }
                    break;

                    case 'link':
                    case 'protected_link':
                        $linkcount++;
                        $splitName = explode(" - ", $field['names'][$this->frontLang]);
                        $iconType = strtolower(trim($splitName[count($splitName)-1]));
                        $this->_objTpl->setVariable(array(
                            'IMMO_LINK_ICON_SRC' => $this->_getIcon($iconType),
                            'IMMO_FIELD_NAME' => htmlentities($field['names'][$this->frontLang], ENT_QUOTES, CONTREXX_CHARSET),
                            'IMMO_FIELD_CONTENT' => htmlspecialchars($field['type']=='protected_link' ? '?section=immo&amp;cmd=getPDF&amp;id='.$immoID.'_'.$fieldKey : $field['content'][$this->frontLang]),
                        ));


                    if (trim($field['content'][$this->frontLang]) != '') {
                        if ($lnkRow++ % 2 == 0) {
                            $this->_objTpl->parse('linkList');
                            $this->_objTpl->parse('linkListRow');
                        } else {
                            $this->_objTpl->parse('linkList');
                        }
                    }

                    break;
                }
            }
        }
    }


    /**
     * return image source to the specified icon
     * @param string icontype
     * @return string path to icon
     */
    function _getIcon($icon)
    {
        return 'images/content/immo/'.$icon.'.gif';
    }


    /**
     * use the domainUrl config string to set the iframe domain of the googlemap
     */
    function _loadIFrame()
    {
        global $_CONFIG;

        $this->_objTpl->setVariable(array(
            'IMMO_GOOGLEMAP_DOMAIN' => $_CONFIG['domainUrl'],
        ));
    }


    /**
     * shows the list of objects, also handles search requests
     * @return void
     */
    function _showImmoList()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        //TODO
        //handle last step of quickSeach and DetailSearch here
        $this->_objTpl->setGlobalVariable(array(
            'TXT_IMMO_BACK' => $_ARRAYLANG['TXT_IMMO_BACK'],
            'TXT_IMMO_CURRENCY_PREFIX' => $this->arrSettings['currency_lang_'.$this->frontLang],
               'TXT_IMMO_CURRENCY_SUFFIX' => $this->_currencySuffix,
            'TXT_IMMO_MORE_INFOS' => $_ARRAYLANG['TXT_IMMO_MORE_INFOS'],
        ));
        $locations = contrexx_addslashes(strip_tags(($_REQUEST['locations'])));
        $obj_type = contrexx_addslashes(strip_tags(($_REQUEST['obj_type'])));
        $property_type = contrexx_addslashes(strip_tags(($_REQUEST['property_type'])));
        $new_building = contrexx_addslashes(strip_tags(($_REQUEST['new_building'])));
        $logo = contrexx_addslashes(strip_tags(($_REQUEST['logo'])));
        if (!empty($_REQUEST['foreigner_auth'])) {
            $foreigner_auth = intval($_REQUEST['foreigner_auth']) > 0 ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'];
        }
        $fprice = contrexx_addslashes(strip_tags(($_REQUEST['fprice'])));
        $tprice = contrexx_addslashes(strip_tags(($_REQUEST['tprice'])));
        $frooms = contrexx_addslashes(strip_tags(($_REQUEST['frooms'])));
        $trooms = contrexx_addslashes(strip_tags(($_REQUEST['trooms'])));

        //show all
        $orderBy = !empty($_REQUEST['order_by']) ? contrexx_addslashes($_REQUEST['order_by']) : 'location';

        $query = 'SELECT     immo.id AS immo_id, reference, visibility,
                            a.fieldvalue AS location,
                            CAST(b.fieldvalue AS UNSIGNED) AS price,
                            c.fieldvalue AS header,
                            d.fieldvalue AS headline,
                            e.fieldvalue AS rooms,
                            f.fieldvalue AS address,
                            img.uri      AS imgsrc
                    FROM '.DBPREFIX.'module_immo AS immo
                    LEFT JOIN '.DBPREFIX.'module_immo_content AS a ON ( immo.id = a.immo_id
                                                                AND a.field_id = (
                                                                    SELECT field_id
                                                                    FROM '.DBPREFIX.'module_immo_fieldname
                                                                    WHERE name = "ort"
                                                                    AND lang_id = 1 )
                                                                AND a.lang_id = '.$this->frontLang.' )
                    LEFT JOIN '.DBPREFIX.'module_immo_content AS b ON ( immo.id = b.immo_id
                                                                AND b.field_id = (
                                                                    SELECT field_id
                                                                    FROM '.DBPREFIX.'module_immo_fieldname
                                                                    WHERE name = "preis"
                                                                    AND lang_id = 1 )
                                                                AND b.lang_id = '.$this->frontLang.' )
                    LEFT JOIN '.DBPREFIX.'module_immo_content AS c ON ( immo.id = c.immo_id
                                                                AND c.field_id = (
                                                                    SELECT field_id
                                                                    FROM '.DBPREFIX.'module_immo_fieldname
                                                                    WHERE name = "kopfzeile"
                                                                    AND lang_id = 1 )
                                                                AND c.lang_id = '.$this->frontLang.' )
                    LEFT JOIN '.DBPREFIX.'module_immo_content AS d ON ( immo.id = d.immo_id
                                                                AND d.field_id = (
                                                                    SELECT field_id
                                                                    FROM '.DBPREFIX.'module_immo_fieldname
                                                                    WHERE name = "headline"
                                                                    AND lang_id = 1 )
                                                                AND d.lang_id = '.$this->frontLang.' )
                    LEFT JOIN '.DBPREFIX.'module_immo_content AS e ON ( immo.id = e.immo_id
                                                                AND e.field_id = (
                                                                    SELECT field_id
                                                                    FROM '.DBPREFIX.'module_immo_fieldname
                                                                    WHERE name = "anzahl zimmer"
                                                                    AND lang_id = 1 )
                                                                AND e.lang_id = '.$this->frontLang.' )
                    LEFT JOIN '.DBPREFIX.'module_immo_content AS f ON ( immo.id = f.immo_id
                                                                AND f.field_id = (
                                                                    SELECT field_id
                                                                    FROM '.DBPREFIX.'module_immo_fieldname
                                                                    WHERE name = "adresse"
                                                                    AND lang_id = 1 )
                                                                AND f.lang_id = '.$this->frontLang.' )
                    LEFT JOIN '.DBPREFIX.'module_immo_image AS img ON ( immo.id = img.immo_id
                                                                AND img.field_id = (
                                                                    SELECT field_id
                                                                    FROM '.DBPREFIX.'module_immo_fieldname
                                                                    WHERE name = "übersichtsbild" )
                                                                )
                    WHERE  ( visibility = "listing"';
                    if (!empty($_REQUEST['ref_nr'])) {
                       $query .= " OR visibility = 'reference' ) ";
                    } else {
                        $query .= ") ORDER BY $orderBy ASC";
                    }


        //request from search form?
        if (empty($_REQUEST['ref_nr'])) {
            //fulltext search
            $keys1 = array_filter(array_keys($_ARRAYLANG), array(&$this, "filterImmoType"));
            foreach ($keys1 as $key) {
                $keys[$key] = $_ARRAYLANG[$key];
            }
            array_walk($keys, array(&$this, 'arrStrToLower'));
            $searchterm = contrexx_addslashes($_REQUEST['search']);
            if (!empty($searchterm) && strlen($searchterm) <= 3) {
                  $this->_objTpl->setVariable("TXT_IMMO_SEARCHTERM_TOO_SHORT", $_ARRAYLANG['TXT_IMMO_SEARCHTERM_TOO_SHORT']);
                  return false;
            }
            $query = "  SELECT immo.id AS `immo_id`, immo.reference AS `reference`, immo.object_type AS otype, immo.new_building AS `new`, immo.property_type AS ptype, logo,
                        a.fieldvalue as headline,
                        CAST(b.fieldvalue AS UNSIGNED) as price,
                        c.fieldvalue as header,
                        d.fieldvalue as location,
                        e.fieldvalue as rooms,
                        f.fieldvalue as foreigner_authorization,
                        g.fieldvalue as address,
                        img.uri AS imgsrc
                        FROM ".DBPREFIX."module_immo AS immo";
            if (!empty($searchterm)) {
                $query .= " LEFT JOIN ".DBPREFIX."module_immo_content AS content on ( content.immo_id = immo.id ) ";
            }
            $query .= " LEFT JOIN ".DBPREFIX."module_immo_content AS a ON ( immo.id = a.immo_id
                                                                AND a.field_id = (
                                                                    SELECT field_id
                                                                    FROM ".DBPREFIX."module_immo_fieldname
                                                                    WHERE name = 'headline'
                                                                    AND lang_id = 1 )
                                                                AND a.lang_id = ".$this->frontLang." )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS b ON ( immo.id = b.immo_id
                                                                AND b.field_id = (
                                                                    SELECT field_id
                                                                    FROM ".DBPREFIX."module_immo_fieldname
                                                                    WHERE name = 'preis'
                                                                    AND lang_id = 1 )
                                                                AND b.lang_id = ".$this->frontLang." )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS c ON ( immo.id = c.immo_id
                                                                AND c.field_id = (
                                                                    SELECT field_id
                                                                    FROM ".DBPREFIX."module_immo_fieldname
                                                                    WHERE name = 'kopfzeile'
                                                                    AND lang_id = 1 )
                                                                AND c.lang_id = ".$this->frontLang." )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS d ON ( immo.id = d.immo_id
                                                                AND d.field_id = (
                                                                    SELECT field_id
                                                                    FROM ".DBPREFIX."module_immo_fieldname
                                                                    WHERE name = 'ort'
                                                                    AND lang_id = 1 )
                                                                AND d.lang_id = ".$this->frontLang." )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS e ON ( immo.id = e.immo_id
                                                                AND e.field_id = (
                                                                    SELECT field_id
                                                                    FROM ".DBPREFIX."module_immo_fieldname
                                                                    WHERE name = 'anzahl zimmer'
                                                                    AND lang_id = 1 )
                                                                AND e.lang_id = ".$this->frontLang." )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS f ON ( immo.id = f.immo_id
                                                                AND f.field_id = (
                                                                    SELECT field_id
                                                                    FROM ".DBPREFIX."module_immo_fieldname
                                                                    WHERE name = 'ausl�nder-bewilligung'
                                                                    AND lang_id = 1 )
                                                                AND f.lang_id = ".$this->frontLang." )
                        LEFT JOIN ".DBPREFIX."module_immo_content AS g ON ( immo.id = g.immo_id
                                                                AND g.field_id = (
                                                                    SELECT field_id
                                                                    FROM ".DBPREFIX."module_immo_fieldname
                                                                    WHERE name = 'adresse'
                                                                    AND lang_id = 1 )
                                                                AND g.lang_id = ".$this->frontLang." )
                        LEFT JOIN ".DBPREFIX."module_immo_image AS img ON ( immo.id = img.immo_id
                                                                AND img.field_id = (
                                                                    SELECT field_id
                                                                    FROM ".DBPREFIX."module_immo_fieldname
                                                                    WHERE name = 'übersichtsbild' )
                                                                )
                        WHERE TRUE
                        ";
            if (!empty($searchterm)) {
                $query .= " AND content.fieldvalue LIKE '%".$searchterm."%' ";
            }
            $query .= " AND immo.visibility != 'disabled' ";
            if (!intval($_REQUEST['refnr'])) {
                $query .= " AND immo.visibility != 'reference' ";
            }
            if (!empty($locations) || !empty($obj_type) || !empty($property_type)) {
                if (!empty($locations)) {
                    $query .= " AND d.fieldvalue = '".$locations."'";
                }
                if (!empty($property_type)) {
                    $query .= " AND immo.property_type = '".$property_type."'";
                }
                if (!empty($obj_type)) {
                    $query .= " AND immo.object_type = '".$obj_type."'";
                }
                if (!empty($new_building)) {
                    $query .= " AND immo.new_building = '".$new_building."'";
                }
                if (!empty($foreigner_auth)) {//max rooms
                    $query .= " AND f.fieldvalue = '".$foreigner_auth."' ";
                }
                if (!empty($fprice)) {//min price
                    $query .= " AND b.fieldvalue >= ".$fprice." ";
                }
                if (!empty($tprice)) {//max price
                    $query .= " AND b.fieldvalue <= ".$tprice." ";
                }
                if (!empty($frooms)) {//min rooms
                    $query .= " AND e.fieldvalue >= '".$frooms."' ";
                }
                if (!empty($trooms)) {//max rooms
                    $query .= " AND e.fieldvalue <= '".$trooms."' ";
                }
                if (!empty($logo)) {//max rooms
                    $query .= " AND logo = '".$logo."' ";
                }
                $query .= ' GROUP BY immo.id ORDER BY '.$orderBy.' ASC';
            }
        } elseif (!empty($_REQUEST['ref_nr'])) { //advanced search
            $orderBy = !empty($_REQUEST['order_by']) ? contrexx_addslashes($_REQUEST['order_by']) : 'immo.id';
            $refnr = intval($_REQUEST['ref_nr']);
            $query .= ' AND reference = '.$refnr." GROUP BY immo.id ORDER BY $orderBy ASC" ;
        }
        //else { //no where clause => show all }

        $objRS = $objDatabase->Execute($query);
        if (!$objRS) {
            echo "DB error. file: ".__FILE__." line: ".__LINE__;
            return false;
        }
        if ($objRS->RecordCount() == 0) {
            if ($this->_objTpl->blockExists("no_results")) {
                $this->_objTpl->touchBlock("no_results");
                $this->_objTpl->parse("no_results");
            }
            return false;
        }
        while(!$objRS->EOF) {
                $imgdim = '';
                $img = $objRS->fields['imgsrc'];
                $imgdim = $this->_getImageDim($img, 80);
                $this->_objTpl->setVariable(array(
                    'IMMO_HEADER' => $objRS->fields['header'],
                    'IMMO_LOCATION' => $objRS->fields['location'] ,
                    'IMMO_PRICE' => $objRS->fields['price'],
                    'IMMO_REF_NR' => $objRS->fields['reference'],
                    'IMMO_HEADLINE' => $objRS->fields['headline'],
                    'IMMO_IMG_PREVIEW_DIM' => $imgdim[0],
                    'IMMO_IMG_PREVIEW_SRC' => $img,
                    'IMMO_ID' => $objRS->fields['immo_id'],
                ));

                if (!empty($objRS->fields['imgsrc'])) {
                    $this->_objTpl->parse("previewImage");
                } else {
                    $this->_objTpl->hideBlock("previewImage");
                }
                $this->_objTpl->setVariable('IMMO_HEADER', $objRS->fields['header']);
                $this->_objTpl->parse("objectRow");
            $objRS->MoveNext();
        }
// TODO: Never used
//        $limit = $_CONFIG['corePagingLimit'];
        $count = '';
        $pos = intval($_GET['pos']);
        $this->_objTpl->setVariable('IMMO_PAGING', getPaging($count, $pos, '&amp;search='.$_REQUEST['search'], '', true));
        return true;
    }


    /**
     * return the dimensions of an image to fit the content (resized if neccessary)
     * @param string $img path to the image
     * @param int $max maximum acceptable size of the image (height or width, whichever is bigger)
     * @return array containing the style string, the width and the height
     */
    function _getImageDim($img, $max=60)
    {
        $img = str_replace(ASCMS_PATH_OFFSET, '', $img);
        if ($img != '') {
            $size = getimagesize(ASCMS_DOCUMENT_ROOT.$img);
            $height = '';
               $width = '';
               $height = $size[1];
            $width = $size[0];
            if ($height > $max && $height > $width) {
                $height = $max;
                $percent = ($size[1] / $height);
                $width = ($size[0] / $percent);
            } else if ($width > $max) {
                $width = $max;
                $percent = ($size[0] / $width);
                $height = ($size[1] / $percent);
            }
            if ($width > 0 && $height > 0) {
                $imgdim[0] = 'style="height: '.$height.'px; width:'.$width.'px;"';
                $imgdim[1] = $size[0]+20;
                $imgdim[2] = $size[1]+20;
            }
            return $imgdim;
        }
        return '';
    }


    /**
     * Shows the map
     * @param int $highlight Id of the Object to be highlighted
     * @return void
     */
    function _showMap()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $this->_objTpl->loadTemplateFile("modules/immo/template/frontend_map_template.html");
        // Check if something has to be highlighted
        $highlight = (isset($_GET['highlight'])) ? intval($_GET['highlight']) : 0;
        // Extract all Placeholders out of the message
        $subQueryPart = "";
        $first = true;
        $matches = array();
        preg_match_all("/%([^%]+)%/", $this->arrSettings['message'], $matches);
        setlocale(LC_ALL, "de_CH");
        foreach ($matches[1] as $match) {
            if ($first) {
                $first = false;
            } else {
                $subQueryPart .= " OR ";
            }
            $subQueryPart .= "lower(name) = '".strtolower($match)."'";
        }
        // Get All the immo objects
        $query = " SELECT immo.id as `id`,
                        immo.reference AS `ref` ,
                        immo.visibility,
                        immo.object_type AS otype,
                        immo.new_building AS `new` ,
                        immo.property_type AS ptype,
                        immo.longitude as `long`,
                        immo.latitude as `lat`,
                        immo.zoom as `zoom`,
                        immo.logo as `logo`
                    FROM ".DBPREFIX."module_immo AS immo
                    WHERE immo.visibility = 'listing'";
        $objResult = $objDatabase->Execute($query);
        if ($objResult) {
            $keyCounter = 0;
            while (!$objResult->EOF) {
                unset($data);
                // This is the one we want to highlight. So we scroll to it
                if ($objResult->fields['id'] == $highlight) {
                    $startX = $objResult->fields['long'];
                    $startY = $objResult->fields['lat'];
                    $startZoom = $objResult->fields['zoom'];
                }
                $data = array(
                    'reference' => $objResult->fields['ref'],
                    'object_type' => $_ARRAYLANG['TXT_IMMO_OBJECTTYPE'.strtoupper($objResult->fields['object_type'])],
                    'new_building' => ($objResult->fields['new_building']) ? $_ARRAYLANG['TXT_IMMO_YES'] : $_ARRAYLANG['TXT_IMMO_NO'],
                    'property_type' => $_ARRAYLANG['TXT_IMMO_PROPERTYTYPE'.strtoupper($objResult->fields['property_type'])],
                    'longitude' => $objResult->fields['longitude'],
                    'latitude' => $objResult->fields['latitude']
                );
                $query = "  SELECT content.field_id AS `field_id` ,
                                fieldnames.name AS `field_name` ,
                                fieldvalue AS `value` ,
                                image.uri AS `uri` ,
                                field.type AS `type`
                            FROM ".DBPREFIX."module_immo_content AS `content`
                            INNER JOIN ".DBPREFIX."module_immo_fieldname AS `fieldnames` ON fieldnames.field_id = content.field_id
                            AND fieldnames.lang_id = '".$this->frontLang."'
                            AND content.lang_id = '".$this->frontLang."'
                            AND fieldnames.field_id
                            IN (
                                SELECT field_id
                                FROM `".DBPREFIX."module_immo_fieldname` AS fieldn
                                WHERE
                                ".$subQueryPart."
                            )
                            AND content.immo_id ='".$objResult->fields['id']."'
                            LEFT OUTER JOIN ".DBPREFIX."module_immo_image AS `image` ON image.field_id = content.field_id
                            AND image.immo_id = '".$objResult->fields['id']."'
                            LEFT OUTER JOIN ".DBPREFIX."module_immo_field AS `field` ON content.field_id = field.id";
                $objResult2 = $objDatabase->Execute($query);
                while (!$objResult2->EOF) {
                    $data[strtolower($objResult2->fields['field_name'])] = ($objResult2->fields['type'] == "img" || $objResult2->fields['type'] == "panorama") ? ((!empty($objResult2->fields['uri'])) ? $objResult2->fields['uri']: "../core/Core/View/Media/icons/pixel.gif") : $objResult2->fields['value'];
                    $objResult2->MoveNext();
                }
                // Line breaks are evil
                $message = str_replace("\r", "", $this->arrSettings['message']);
                $message = str_replace("\n", "", $message);
                // get all fieldnames + -contents from the highlighted immo ID
                if (!empty($highlight)) {
                    $this->_getFieldNames($highlight);
                }
                // replace the placeholder in the message with the date (if provided)
                foreach ($matches[1] as $match) {
                    $toReplace = (isset($data[strtolower($match)])) ? $data[strtolower($match)] : "";
                    //custom values for "price" field
                    if ($match == strtoupper($this->arrFields['price'])) {
                        $toReplace = number_format($toReplace, $this->_arrPriceFormat[$this->frontLang]['dec'], $this->_arrPriceFormat[$this->frontLang]['dec_sep'], $this->_arrPriceFormat[$this->frontLang]['thousand_sep']);
                        $status = $this->_getFieldFromText('status');
                        if ($this->_getFieldFromText($this->arrFields['price']) == 0) {
                            $status = "null";
                        }
                        $toReplace = $this->arrSettings['currency_lang_'.$this->frontLang]." ".$toReplace." ".$this->_currencySuffix;
                        switch($status) {
                            case 'verkauft':
                                $toReplace = '<strike>'.$toReplace.'</strike>  &nbsp;<span style=\"color: red;\">(verkauft)</span>';
                                break;
                            case 'versteckt':
                                $toReplace = '<span style=\"color: red;\">verkauft</span>';
                                break;
//                            case 'null':
//                                $toReplace = '<span style=\"color: red;\">verkauft</span>';
//                                break;
                            case 'reserviert':
                                $toReplace .= '  &nbsp;<span style=\"color: red;\">(reserviert)</span>';
                                break;
                        }
                    }
                    $message = str_replace("%".$match."%", $toReplace, $message);
                }
                $this->_objTpl->setVariable(array(
                    'IMMO_KEY_NUMBER' => $keyCounter,
                    'IMMO_MARKER_LAT' => $objResult->fields['lat'],
                    'IMMO_MARKER_LONG' => $objResult->fields['long'],
                    'IMMO_MARKER_MSG' => $message,
                    'IMMO_MARKER_ID' => $objResult->fields['id'],
                    'IMMO_MARKER_HIGHLIGHT' => ($objResult->fields['id'] == $highlight) ? 1 : 0,
                    'IMMO_MARKER_LOGO' => $objResult->fields['logo']
                ));
                $this->_objTpl->parse("setmarker");
                $keyCounter++;
                $objResult->MoveNext();
            }
        }

        // Nothing is highlighted. Start at the default start point
        if (!$highlight || !isset($startX)) {
            $startX = $this->arrSettings['lat_frontend'];
            $startY = $this->arrSettings['lon_frontend'];
            $startZoom = $this->arrSettings['zoom_frontend'];
        }
        $googleKey = (empty($this->arrSettings['GOOGLE_API_KEY_'.$_SERVER['SERVER_NAME']])) ? $_CONFIG['googleMapsAPIKey'] : $this->arrSettings['GOOGLE_API_KEY_'.$_SERVER['SERVER_NAME']];
        $this->_objTpl->setVariable(array(
            'IMMO_GOOGLE_API_KEY' => $googleKey,
            'IMMO_START_X' => $startX,
            'IMMO_START_Y' => $startY,
            'IMMO_START_ZOOM' => $startZoom,
            'IMMO_LANG' => $this->frontLang,
            'IMMO_TXT_LOOK' => $_ARRAYLANG['TXT_IMMO_LOOK']
        ));
        if (!empty($_GET['bigone']) && $_GET['bigone'] == 1) {
            $this->_objTpl->touchBlock("big");
            $this->_objTpl->parse("big");
        } else {
            $this->_objTpl->touchBlock("small");
            $this->_objTpl->parse("small");
        }
        $this->_objTpl->getBlockList();
        $this->_objTpl->parse("map");
        $this->_objTpl->show("map");
        die;
    }


    /**
     * return javascript for search form
     * @return string javascript
     */
    function _getImmoJS()
    {
        return <<< EOF
var swapSearch = function() {
  document.getElementById("advanced_search").style.display = document.getElementById("advanced_search").style.display == 'none' ? 'block'  : 'none';
  document.getElementById("simple_search").style.display = document.getElementById("simple_search").style.display == 'none' ? 'block'  : 'none';
}
EOF;
    }


    /**
     * return javascript for the details page
     * @return string javascript
     */
    function _getDetailsJS()
    {
        global $_CONFIG;

// TODO:  Never used
//        $domainUrl = $_CONFIG['domainUrl'];
        return "
var openMap = function(id) {
  try {
    if (! popUp.closed) {
      return popUp.focus();
    }
  } catch(e) {}

  url='".ASCMS_PATH_OFFSET."/index.php?section=immo&standalone=1&bigone=1&highlight='+id;
  if (!window.focus) {
    return true;
  }
  popUp = window.open(url, 'Map', 'width=820,height=620,scrollbars=no');
  popUp.focus();
  return false;
}

var openPreview = function(immoid, imageIndex) {
  try {
    if (! imgPopUp.closed) {
      imgPopUp.index = parseInt(imageIndex);
      imgPopUp.showImage();
      imgPopUp.focus();
      return;
    }
  } catch(e) {}
  if (!window.focus) {
    return true;
  }
  imgPopUp = window.open('?section=immo&img=1&id='+immoid+'&index='+imageIndex, '', 'width=500,height=500,scrollbars=no');
  imgPopUp.focus();
  imgPopUp.moveTo(0,0);
  return false;
}
";
    }

}

?>
