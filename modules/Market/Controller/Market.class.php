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
 * Market
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_market
 * @todo        Edit PHP DocBlocks!
 */

namespace Cx\Modules\Market\Controller;

/**
 * Market
 *
 * Demo market class
 * @copyright   CLOUDREXX CMS - CLOUDREXX AG
 * @author        Cloudrexx Development Team <info@cloudrexx.com>
 * @access        public
 * @version        1.0.0
 * @package     cloudrexx
 * @subpackage  module_market
 */
class Market extends MarketLibrary
{
    /**
    * Template object
    *
    * @access private
    * @var object
    */
    protected $_objTpl;
    protected $pageContent;
    protected $communityModul;
    protected $mediaPath;
    protected $mediaWebPath;
    protected $settings;
    protected $categories;
    protected $entries;

    /**
     * Constructor
     * @global object $objTemplate
     */
    function __construct($pageContent)
    {
        $this->pageContent = $pageContent;

        $this->_objTpl = new \Cx\Core\Html\Sigma('.');
        \Cx\Core\Csrf\Controller\Csrf::add_placeholder($this->_objTpl);
        $this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);

        $this->mediaPath = ASCMS_MARKET_MEDIA_PATH . '/';
        $this->mediaWebPath = ASCMS_MARKET_MEDIA_WEB_PATH . '/';

        //get settings
        $this->settings = $this->getSettings();

        //check community modul
        $objModulManager = new \Cx\Core\ComponentManager\Controller\ComponentManager();
        $arrInstalledModules = $objModulManager->getModules();
        if (in_array(23, $arrInstalledModules)) {
            $this->communityModul = true;
        } else {
            $this->communityModul = false;
        }

        //ipn Check
        if (isset($_GET['act'])) {
            switch($_GET['act']) {
                case "paypalIpnCheck";
                    $objPaypal = new \PayPal;
                    $objPaypal->ipnCheck();
                    exit;
                    break;
                default:
                    //nothging
                    break;
            }
        }
    }

    /**
    * Get content page
    *
    * @access public
    */
    function getPage()
    {
        if (!isset($_GET['cmd'])) {
            $_GET['cmd'] = '';
        }

        switch ($_GET['cmd']) {
            case 'detail':
                $this->entryDetails(intval($_GET['id']));
            break;
            case 'send':
                $this->sendMessage(intval($_GET['id']));
            break;
            case 'add':
                $this->addEntry();
            break;
            case 'confirm':
                $this->confirmEntry();
            break;
            case 'edit':
                $this->editEntry();
            break;
            case 'del':
                $this->delEntry();
            break;
            case 'search':
                $this->searchEntry();
            break;
            default:
                $this->showCategories();
            break;
        }
        return $this->_objTpl->get();
    }



    function showCategories()
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        $catRows                 = 2;
        $categorieRowWidth         = substr(100/$catRows, 0, 2)."%";
        $categorieRows            = array();
        $arrRowsIndex            = array();

        for($x = 1; $x <= $catRows; $x++) {
            $categorieRows[$x] = "";
        }

        //get categories
        $objResult = $objDatabase->Execute("SELECT id, name, description FROM ".DBPREFIX."module_market_categories WHERE status = '1' ORDER BY displayorder");

        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $this->categories['name'][$objResult->fields['id']] = $objResult->fields['name'];
                $this->categories['description'][$objResult->fields['id']] = $objResult->fields['description'];
                $objResult->MoveNext();
            }
        }

        //get search
        $this->getSearch();

        //get navigation
        $this->getNavigation($_GET['id']);

        //show categories
        $i = 1;
        if (!empty($this->categories) && !isset($_GET['id'])) {
            foreach($this->categories['name'] as $catKey => $catName) {
                $count = $this->countEntries($catKey);
                if ($count == "") {
                    $count = "0";
                }

                $categorieRows[$i] .= "<a class='catLink' href='index.php?section=Market&amp;id=".$catKey."'>".htmlentities($catName, ENT_QUOTES, CONTREXX_CHARSET)."</a>&nbsp;(".$count.")<br />";
                array_push($arrRowsIndex, substr(htmlentities($catName, ENT_QUOTES, CONTREXX_CHARSET), 0, 1)."<a class='catLink' href='index.php?section=Market&amp;id=".$catKey."'>".htmlentities($catName, ENT_QUOTES, CONTREXX_CHARSET)."</a>&nbsp;(".$count.")<br />");

                if ($i%$catRows==0) {
                    $i=1;
                }else{
                    $i++;
                }
            }

            $objResult = $objDatabase->Execute("SELECT id FROM ".DBPREFIX."module_market WHERE status = '1'");
            $allFeeds = $objResult->RecordCount();
            $insertFeeds = $allFeeds." ".$_ARRAYLANG['TXT_MARKET_ADD_ADVERTISEMENT'];

            $this->_objTpl->hideBlock('showCategoriesTitle');
            $this->_objTpl->parse('showInsertEntries');
            $this->_objTpl->hideBlock('showEntriesHeader');

            $this->showLatestEntries();
        }else{
            $title             = $this->categories['name'][$_GET['id']];
            $description     = $this->getDescription($_GET['id']);
            $selectionOffer        = "";
            $selectionSearch    = "";
            $selectionAll        = "";

            if (!isset($_GET['type'])) {
                $_GET['type'] = '';
            }

            switch ($_GET['type']) {
                case 'offer':
                    $selectionOffer        = "checked";
                break;
                case 'search':
                    $selectionSearch    = "checked";
                break;
                default:
                    $selectionAll        = "checked";
                break;
            }
            //typselector
            $selector = '<span class="radio"><label><input type="radio" name="type" onclick="location.replace(\'index.php?section=Market&amp;id='.$_GET['id'].'\')" '.$selectionAll.' />'.$_ARRAYLANG['TXT_MARKET_ALL'].'&nbsp;</label></span><span class="radio"><label><input type="radio" name="type" onclick="location.replace(\'index.php?section=Market&amp;id='.$_GET['id'].'&amp;type=offer\')" '.$selectionOffer.' />'.$_ARRAYLANG['TXT_MARKET_OFFERS'].'&nbsp;</label></span><span class="radio"><label><input type="radio" name="type" onclick="location.replace(\'index.php?section=Market&amp;id='.$_GET['id'].'&amp;type=search\')" '.$selectionSearch.' />'.$_ARRAYLANG['TXT_MARKET_REQUEST'].'</label></span>';
            //get entries
            $this->showEntries($_GET['id']);

            $this->_objTpl->parse('showCategoriesTitle');
            $this->_objTpl->hideBlock('showInsertEntries');
            $this->_objTpl->hideBlock('showCategories');
        }

        //select View
        if  ($this->settings['indexview']['value'] == 1) {
            $categorieRows ='';
            sort($arrRowsIndex);

            $i = 0;
            $firstCol = true;
            $index = '';
            foreach($arrRowsIndex as $rowName) {
                if ($index != substr($rowName, 0, 1)) {
                    $index = substr($rowName, 0, 1);
                    if ($i%$catRows==0) {
                        $i=1;
                    }else{
                        $i++;
                    }

                    $categorieRows[$i] .= (!$firstCol ? "<br />" : "")."<b>".$index."</b><br />".substr($rowName,1);
                    if ($i == $catRows && $firstCol) {
                        $firstCol = false;
                    }
                } else {
                    $categorieRows[$i] .= substr($rowName,1);
                }
            }
        }

        //spez fields
        $this->parseSpecialFields(
            $objDatabase,
            $this->_objTpl,
            array(),
            0,
            'txt'
        );

        // set variables
        $this->_objTpl->setVariable(array(
            'MARKET_SEARCH_PAGING'            => $paging,
            'MARKET_CATEGORY_ROW_WIDTH'        => $categorieRowWidth,
            'MARKET_CATEGORY_ROW1'            => $categorieRows[1]."<br />",
            'MARKET_CATEGORY_ROW2'            => $categorieRows[2]."<br />",
            'MARKET_CATEGORY_TITLE'            => $title,
            'MARKET_CATEGORY_DESCRIPTION'    => $description,
            'DIRECTORY_INSERT_ENTRIES'        => $insertFeeds,
            'TXT_MARKET_ENDDATE'            => $_CORELANG['TXT_END_DATE'],
            'TXT_MARKET_TITLE'                => $_ARRAYLANG['TXT_MARKET_TITLE'],
            'TXT_MARKET_PRICE'                => $_ARRAYLANG['TXT_MARKET_PRICE'],
            'TXT_MARKET_CITY'                => $_ARRAYLANG['TXT_MARKET_CITY'],
            'MARKET_TYPE_SECECTION'            => $selector,
        ));
    }


    function showEntries($catId)
    {
        global $objDatabase, $_ARRAYLANG;

        $today = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
        $type                 = "";
        $typePaging            = "";

        if ($this->settings['maxdayStatus'] != 0) {
            $this->checkEnddate();
        }

        if (!isset($_GET['type'])) {
            $_GET['type'] = '';
        }

        switch ($_GET['type']) {
            case 'offer':
                $type                = "AND type='offer'";
                $typePaging            = "&amp;type=offer";
            break;
            case 'search':
                $type                 = "AND type ='search'";
                $typePaging            = "&amp;type=search";
            break;
            default:
                $type                 = "";
                $typePaging            = "";
            break;
        }

        switch ($_GET['sort']) {
            case 'title':
                $sort                = "title";
                $sortPaging            = "&amp;sort=title";
            break;
            case 'enddate':
                $sort                = "enddate";
                $sortPaging            = "&amp;sort=enddate";
            break;
            case 'price':
                $sort                = "price";
                $sortPaging            = "&amp;sort=price";
            break;
            case 'residence':
                $sort                = "residence";
                $sortPaging            = "&amp;sort=residence";
            break;
            default:
                $sort                = "sort_id, enddate";
                $sortPaging            = "";
            break;
        }

        if (isset($_GET['way'])) {
            $way         = $_GET['way']=='ASC' ? 'DESC' : 'ASC';
            $wayPaging     = '&amp;way='.$_GET['way'];
        }else{
            $way         = 'ASC';
            $wayPaging     = '';
        }

        $this->_objTpl->setVariable(array(
            'MARKET_ENDDATE_SORT' => "index.php?section=Market&amp;id=".$catId."&amp;type=".$_GET['type']."&amp;sort=enddate&amp;way=".$way,
            'MARKET_TITLE_SORT'   => "index.php?section=Market&amp;id=".$catId."&amp;type=".$_GET['type']."&amp;sort=title&amp;way=".$way,
            'MARKET_PRICE_SORT'   => "index.php?section=Market&amp;id=".$catId."&amp;type=".$_GET['type']."&amp;sort=price&amp;way=".$way,
            'MARKET_CITY_SORT'    => "index.php?section=Market&amp;id=".$catId."&amp;type=".$_GET['type']."&amp;sort=residence&amp;way=".$way,
        ));

        if ($this->settings['maxdayStatus'] == 0) {
            $where = '';
        } else {
            $where = 'AND enddate >= "'.$today.'"';
        }

        /////// START PAGING ///////
        $pos= intval($_GET['pos']);

        if ($sort == 'price') {
            $specialFieldsQuery = $this->getSpecialFieldsQueryPart($objDatabase);
            $query='SELECT `id`,`name`,`email`,`type`,`title`,`description`,`premium`,`picture`,`catid`, CAST(`price` AS UNSIGNED) as `price`,`regdate`,`enddate`,`userid`,`userdetails`,`status`,`regkey`,`paypal`, ' . $specialFieldsQuery . ' FROM '.DBPREFIX.'module_market WHERE catid = "'.contrexx_addslashes($catId).'" AND status="1" '.$where.' '.$type.' ORDER BY '.$sort.' '.$way;
        }else{
            $query='SELECT * FROM '.DBPREFIX.'module_market WHERE catid = "'.contrexx_addslashes($catId).'" AND status="1" '.$where.' '.$type.' ORDER BY '.$sort.' '.$way;
        }

        $objResult = $objDatabase->Execute($query);
        $count = $objResult->RecordCount();
        if ($count > $this->settings['paging']) {
            $paging = getPaging($count, $pos, "&amp;section=Market&amp;id=".$catId.$typePaging.$sortPaging.$wayPaging, "<b>Inserate</b>", true, $this->settings['paging']);
        }

        $this->_objTpl->setVariable('SEARCH_PAGING', $paging);
        $objResult = $objDatabase->SelectLimit($query, $this->settings['paging'], $pos);
        /////// END PAGING ///////

        $i=0;
        if ($objResult !== false) {
               while (!$objResult->EOF) {
                   if (empty($objResult->fields['picture'])) {
                    $objResult->fields['picture'] = 'no_picture.gif';
                }

                   $info         = getimagesize($this->mediaPath.'pictures/'.$objResult->fields['picture']);
                   $height     = '';
                   $width         = '';

                   if ($info[0] <= $info[1]) {
                    if ($info[1] > 50) {
                        $faktor = $info[1]/50;
                        $height = 50;
                        $width    = $info[0]/$faktor;
                    } else {
                        $height = $info[1];
                        $width = $info[0];
                    }
                }else{
                    $faktor = $info[0]/80;
                    $result = $info[1]/$faktor;
                    if ($result > 50) {
                        if ($info[1] > 50) {
                            $faktor = $info[1]/50;
                            $height = 50;
                            $width    = $info[0]/$faktor;
                        }else{
                            $height = $info[1];
                            $width = $info[0];
                        }
                    }else{
                        if ($info[0] > 80) {
                            $width = 80;
                            $height = $info[1]/$faktor;
                        }else{
                            $width = $info[0];
                            $height = $info[1];
                        }
                    }
                }
                $width != '' ? $width = 'width="'.round($width,0).'"' : $width = '';
                $height != '' ? $height = 'height="'.round($height,0).'"' : $height = '';
                $image = '<img src="'.$this->mediaWebPath.'pictures/'.$objResult->fields['picture'].'" '.$width.' '.$height.' border="0" alt="'.$objResult->fields['title'].'" />';
                $objFWUser = \FWUser::getFWUserObject();
                $objUser = $objFWUser->objUser->getUser($objResult->fields['userid']);
                if ($objUser) {
                    $city = $objUser->getProfileAttribute('city');
                }

                   if ($objResult->fields['premium'] == 1) {
                       $row = "marketRow1";
                   }else{
                       $row = $i % 2 == 0 ? 'marketRow2' : 'marketRow3';
                   }

                   $enddate = date("d.m.Y", $objResult->fields['enddate']);

                   if ($objResult->fields['price'] == 'forfree') {
                       $price = $_ARRAYLANG['TXT_MARKET_FREE'];
                   }elseif ($objResult->fields['price'] == 'agreement') {
                       $price = $_ARRAYLANG['TXT_MARKET_ARRANGEMENT'];
                   }else{
                       $price = $objResult->fields['price'].' '.$this->settings['currency'];
                   }

                   $this->_objTpl->setVariable(array(
                    'MARKET_ENDDATE'            => $enddate,
                    'MARKET_TITLE'                => $objResult->fields['title'],
                    'MARKET_COLOR'                => $objResult->fields['color'],
                    'MARKET_DESCRIPTION'        => substr($objResult->fields['description'], 0, 110)."<a href='index.php?section=Market&amp;cmd=detail&amp;id=".$objResult->fields['id']."' target='_self'>[...]</a>",
                    'MARKET_PRICE'                => $price,
                    'MARKET_PICTURE'            => $image,
                    'MARKET_ROW'                => $row,
                    'MARKET_DETAIL'                => "index.php?section=Market&amp;cmd=detail&amp;id=".$objResult->fields['id'],
                    'MARKET_ID'                    => $objResult->fields['id'],
                    'MARKET_CITY'                => $city,
                ));

                $this->parseSpecialFields(
                    $objDatabase,
                    $this->_objTpl,
                    $objResult->fields,
                    0,
                    'val'
                );
                $this->_objTpl->parse('showEntries');

                $i++;
                $objResult->MoveNext();
               }

           }



           if ($count <= 0) {
            $this->_objTpl->setVariable(array(
                'MARKET_NO_ENTRIES_FOUND'            => $_ARRAYLANG['TXT_MARKET_NO_ENTRIES_FOUND'],
            ));

            $this->_objTpl->parse('noEntries');
        }


    }

    function showLatestEntries()
    {
        global $objDatabase, $_ARRAYLANG;

        if ($this->_objTpl->blockExists('showLatestEntries')) {
            $objEntries = $objDatabase->SelectLimit('SELECT id, title, picture FROM '.DBPREFIX.'module_market WHERE status !=0 ORDER BY id DESC', 4);
            $colCount = 2;
            $entryNr = 1;
            $rowNr = 1;

            if ($objEntries && $objEntries->RecordCount() > 0) {
                   while (!$objEntries->EOF) {
                       if ($objEntries->fields['picture'] == '') {
                           $pic = 'no_picture.gif';
                       } else {
                           $pic = $objEntries->fields['picture'];
                       }
                       $info         = getimagesize($this->mediaPath.'pictures/'.$pic);
                       $height     = '';
                       $width         = '';

                       if ($info[0] <= $info[1]) {
                           $height = 50;
                       } else {
                           $faktor = $info[0]/80;
                           $result = $info[1]/$faktor;
                           if ($result > 50) {
                               $height = 50;
                           } else {
                               $width = 80;
                           }
                       }

                    $width != '' ? $width = 'width="'.$width.'"' : $width = '';
                    $height != '' ? $height = 'height="'.$height.'"' : $height = '';

                       $image = '<img src="'.$this->mediaWebPath.'pictures/'.$pic.'" '.$width.' '.$height.' border="0" alt="'.$objEntries->fields['title'].'" />';

                       $this->_objTpl->setVariable(array(
                        'MARKET_TITLE'                => htmlentities($objEntries->fields['title'], ENT_QUOTES, CONTREXX_CHARSET),
                        'MARKET_PICTURE'            => $image,
                        'MARKET_ROW'                => ($entryNr % 2 == ($rowNr % 2) ? 'description' : 'description'),
                        'MARKET_DETAIL'  => "index.php?section=Market&amp;cmd=detail&amp;id=".$objEntries->fields['id']
                    ));
                    $this->_objTpl->parse('showLatestEntryCols');
                    if ($entryNr % $colCount == 0) {
                        $rowNr++;
                        $this->_objTpl->parse('showLatestEntryRows');
                    }

                    $entryNr++;
                    $objEntries->MoveNext();
                   }

                   $this->_objTpl->parse('showLatestEntries');
               } else {
                   $this->_objTpl->hideBlock('showLatestEntries');
               }
        }
    }


    /**
     * Get search content
     */
    public function getSearch()
    {
        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $catId = isset($_GET['catid']) ? contrexx_input2int($_GET['catid']) : 0;
        $type  = isset($_GET['type']) ? contrexx_input2raw($_GET['type']) : '';
        $price = isset($_GET['price']) ? contrexx_input2raw($_GET['price']) : '';
        $order = ($this->settings['indexview']['value'] == 1)
            ? 'name' : 'displayorder';

        //Create category dropdown
        $objResultSearch = $objDatabase->Execute(
            'SELECT `id`, `name`, `description`
                FROM `' . DBPREFIX . 'module_market_categories`
                WHERE `status` = 1
                ORDER BY ' . $order
        );
        if ($objResultSearch !== false) {
            $catSelect = new \Cx\Core\Html\Model\Entity\HtmlElement('select');
            $catSelect->setAttributes(array(
                'id'   => 'catid',
                'name' => 'catid'
            ));
            //default option
            $option = new \Cx\Core\Html\Model\Entity\HtmlElement('option');
            $option->setAttribute('value', '');
            $option->addChild(
                new \Cx\Core\Html\Model\Entity\TextElement(
                    $_ARRAYLANG['TXT_MARKET_ALL_CATEGORIES']
                )
            );
            $catSelect->addChild($option);
            while (!$objResultSearch->EOF) {
                $option = new \Cx\Core\Html\Model\Entity\HtmlElement('option');
                $option->setAttribute(
                    'value',
                    contrexx_raw2xhtml($objResultSearch->fields['id'])
                );
                if ($catId == $objResultSearch->fields['id']) {
                    $option->setAttribute('selected', 'selected');
                }
                $option->addChild(
                    new \Cx\Core\Html\Model\Entity\TextElement(
                        contrexx_raw2xhtml($objResultSearch->fields['name'])
                    )
                );
                $catSelect->addChild($option);
                $objResultSearch->MoveNext();
            }
        }

        //Create category row
        $pTagOne  = new \Cx\Core\Html\Model\Entity\HtmlElement('p');
        $catLabel = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
        $catLabel->setAttribute('for', 'catid');
        $catLabel->addChild(
            new \Cx\Core\Html\Model\Entity\TextElement(
                $_ARRAYLANG['TXT_MARKET_CATEGORY']
            )
        );
        $pTagOne->addChild($catLabel);
        $pTagOne->addChild($catSelect);

        //Create type row
        $pTagTwo = new \Cx\Core\Html\Model\Entity\HtmlElement('p');
        $typeLabel = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
        $typeLabel->setAttribute('for', 'type');
        $typeLabel->addChild(
            new \Cx\Core\Html\Model\Entity\TextElement(
                $_ARRAYLANG['TXT_TYPE']
            )
        );
        $pTagTwo->addChild($typeLabel);
        //Create type dropdown
        $typeSelect = new \Cx\Core\Html\Model\Entity\HtmlElement('select');
        $typeSelect->setAttributes(array(
            'id'   => 'type',
            'name' => 'type'
        ));
        $types = array(
            ''       => $_ARRAYLANG['TXT_MARKET_ALL_TYPES'],
            'offer'  => $_ARRAYLANG['TXT_MARKET_OFFER'],
            'search' => $_ARRAYLANG['TXT_MARKET_SEARCH']
        );
        foreach ($types as $typeKey => $typeValue) {
            $option = new \Cx\Core\Html\Model\Entity\HtmlElement('option');
            $option->setAttribute('value', $typeKey);
            if ($type == $typeKey) {
                $option->setAttribute('selected', 'selected');
            }
            $option->addChild(
                new \Cx\Core\Html\Model\Entity\TextElement($typeValue)
            );
            $typeSelect->addChild($option);
        }
        $pTagTwo->addChild($typeSelect);

        //Create price dropdown
        $priceSelect = new \Cx\Core\Html\Model\Entity\HtmlElement('select');
        $priceSelect->setAttributes(array(
            'id'   => 'price',
            'name' => 'price'
        ));
        $option = new \Cx\Core\Html\Model\Entity\HtmlElement('option');
        $option->setAttribute('value', '');
        $option->addChild(
            new \Cx\Core\Html\Model\Entity\TextElement(
                $_ARRAYLANG['TXT_MARKET_ALL_PRICES']
            )
        );
        $priceSelect->addChild($option);
        $arrPrices = explode(',', $this->settings['searchPrice']);
        foreach ($arrPrices as $priceValue) {
            $option = new \Cx\Core\Html\Model\Entity\HtmlElement('option');
            $option->setAttribute('value', $priceValue);
            if ($price == $priceValue) {
                $option->setAttribute('selected', 'selected');
            }
            $option->addChild(
                new \Cx\Core\Html\Model\Entity\TextElement(
                    $priceValue . ' ' . $this->settings['currency']
                )
            );
            $priceSelect->addChild($option);
        }

        //Create price row
        $pTagThree  = new \Cx\Core\Html\Model\Entity\HtmlElement('p');
        $priceLabel = new \Cx\Core\Html\Model\Entity\HtmlElement('label');
        $priceLabel->setAttribute('for', 'cpricetid');
        $priceLabel->addChild(
            new \Cx\Core\Html\Model\Entity\TextElement(
                $_ARRAYLANG['TXT_MARKET_PRICE_MAX']
            )
        );
        $pTagThree->addChild($priceLabel);
        $pTagThree->addChild($priceSelect);

        // set variables
        $this->_objTpl->setVariable(array(
            'TXT_MARKET_SEARCH'       => $_CORELANG['TXT_SEARCH'],
            'TXT_MARKET_SEARCH_EXP'   => $_CORELANG['TXT_EXP_SEARCH'],
            'MARKET_EXP_SEARCH_FIELD' => $pTagOne . $pTagTwo . $pTagThree,
        ));
    }



    function getDescription($id)
    {
        global $objDatabase, $_ARRAYLANG;

        //get categorie
        if ($this->settings['description'] == 1) {
            $objResult = $objDatabase->Execute("SELECT description FROM ".DBPREFIX."module_market_categories WHERE status = '1' AND id = '".contrexx_addslashes($id)."' ORDER BY id DESC");
            if ($objResult !== false) {
                while(!$objResult->EOF) {
                    $description = "<br/>".$objResult->fields['description'];
                    $objResult->MoveNext();
                }
            }
        }else{
            $description = "";
        }

        return $description;
    }



    function getNavigation($catId) {

        global $objDatabase, $_ARRAYLANG;

        //get categorie
         $objResult = $objDatabase->Execute("SELECT  id, name FROM ".DBPREFIX."module_market_categories WHERE status = '1' AND id = '".contrexx_addslashes($catId)."'");
        if ($objResult !== false)    {
            if ($objResult->fields['name'] != '') {
                $verlauf = "&nbsp;&raquo;&nbsp;<a href='index.php?section=Market&amp;id=".$catId."'>".$objResult->fields['name']."</a>";
            }else{
                $verlauf = "";
            }
         }

         // set variables
        $this->_objTpl->setVariable(array(
            'TXT_MARKET'                    => $_ARRAYLANG['TXT_ENTRIES'],
            'MARKET_CATEGORY_NAVI'            => $verlauf,
        ));
    }



    function entryDetails($id) {

        global $objDatabase, $_ARRAYLANG, $_CORELANG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //get erntry
        $this->getEntries('', 'id', $id);

        if (isset($id) && count($this->entries) != 0) {

            //get search
            $this->getSearch();

            //get navigatin
            $this->getNavigation($this->entries[$id]['catid']);

            $enddate = date("d.m.Y", $this->entries[$id]['enddate']);
            $info         = getimagesize($this->mediaPath.'pictures/'.$this->entries[$id]['picture']);
            $height     = '';
            $width         = '';

            if ($info[0] <= $info[1]) {
                if ($info[1] > 200) {
                    $faktor = $info[1]/200;
                    $height = 200;
                    $width    = $info[0]/$faktor;
                } else {
                    $height = $info[1];
                    $width = $info[0];
                }
            }else{
                $faktor = $info[0]/300;
                $result = $info[1]/$faktor;
                if ($result > 200) {
                    if ($info[1] > 200) {
                        $faktor = $info[1]/200;
                        $height = 200;
                        $width    = $info[0]/$faktor;
                    }else{
                        $height = $info[1];
                        $width = $info[0];
                    }
                }else{
                    if ($info[0] > 300) {
                        $width = 300;
                        $height = $info[1]/$faktor;
                    }else{
                        $width = $info[0];
                        $height = $info[1];
                    }
                }
            }

            $width != '' ? $width = 'width="'.round($width,0).'"' : $width = '';
            $height != '' ? $height = 'height="'.round($height,0).'"' : $height = '';

            $image = '<img src="'.$this->mediaWebPath.'pictures/'.$this->entries[$id]['picture'].'" '.$width.' '.$height.' border="0" alt="'.$this->entries[$id]['title'].'" />';

            $user        = $this->entries[$id]['name'].'<br />';
            $userMail    = '<a href="mailto:'.$this->entries[$id]['email'].'">'.$this->entries[$id]['email'].'</a><br />';

            //user details
            $objFWUser = \FWUser::getFWUserObject();
            $objUser = $objFWUser->objUser->getUser($this->entries[$id]['userid']);
            if ($objUser) {
                    $objUser->getProfileAttribute('address') != '' ? $street = $objUser->getProfileAttribute('address').'<br />' : $street = '';
                    $objUser->getProfileAttribute('phone_office') != '' ? $phone = $objUser->getProfileAttribute('phone_office').'<br />' : $phone = '';
                    $objUser->getProfileAttribute('phone_mobile') != '' ? $mobile = $objUser->getProfileAttribute('phone_mobile').'<br /><br />' : $mobile = '';
                    if ($objUser->getProfileAttribute('zip') != '' || $objUser->getProfileAttribute('zip') != 0) {
                        $zip = $objUser->getProfileAttribute('zip').' ';
                    } else {
                        $zip = '';
                    }
                    $objUser->getProfileAttribute('city') != '' ? $city = $objUser->getProfileAttribute('city').'<br />' : $city = '';
                    $objUser->getProfileAttribute('website') != '' ? $webpage = '<a href="http://:'.$objUser->getProfileAttribute('website').'" target="_blank">'.$objUser->getProfileAttribute('website').'</a><br />' : $webpage = '';

                $TXTuserDetails = $_ARRAYLANG['TXT_MARKET_CONTACT'];
                $userDetails =     $user.$street.$zip.$city.$phone.$mobile.$userMail.$webpage;

                $residence = $objUser->getProfileAttribute('zip').' '.$objUser->getProfileAttribute('city');
            } else {
                $TXTuserDetails     = $_ARRAYLANG['TXT_MARKET_CONTACT'];
                $userDetails         = $user.$userMail;
            }

            if ($this->entries[$id]['userdetails'] != 1) {
                $userDetails = '';
                $TXTuserDetails = '';
            }

            //type
            if ($this->entries[$id]['type'] == "offer") {
                $type         = $_ARRAYLANG['TXT_MARKET_OFFER'];
                $txtplace     = $_ARRAYLANG['TXT_MARKET_PLACE'];
                $place         = $residence;
            }else{
                $type         = $_ARRAYLANG['TXT_MARKET_SEARCH'];
                $txtplace     = '';
                $place         = '';
            }

            //price
            if ($this->entries[$id]['price'] == 'forfree') {
                   $price = $_ARRAYLANG['TXT_MARKET_FREE'];
               }elseif ($this->entries[$id]['price'] == 'agreement') {
                   $price = $_ARRAYLANG['TXT_MARKET_ARRANGEMENT'];
               }else{
                   $price = $this->entries[$id]['price'].' '.$this->settings['currency'];
               }

               if ($this->settings['maxdayStatus'] == 1) {
                $enddate = $_ARRAYLANG['TXT_MARKET_ADVERTISEMENT_ONLINE'].' '.$enddate;
            } else {
                $enddate = "";
            }

            // set variables
            $this->_objTpl->setVariable(array(
                'MARKET_TITLE'                    => $this->entries[$id]['title'],
                'MARKET_COLOR'                    => $this->entries[$id]['color'],
                'MARKET_ID'                        => $id,
                'MARKET_EDIT'                    => '<a href="index.php?section=Market&amp;cmd=edit&amp;id='.$id.'">'.$_ARRAYLANG['TXT_EDIT_ADVERTISEMENT'].'</a>',
                'MARKET_DEL'                    => '<a href="index.php?section=Market&amp;cmd=del&amp;id='.$id.'">'.$_ARRAYLANG['TXT_MARKET_DELETE_ADVERTISEMENT'].'</a>',
                'MARKET_TYPE'                    => $type,
                'MARKET_USER_DETAILS'             => $userDetails,
                'TXT_MARKET_USER_DETAILS'         => $TXTuserDetails,
                'MARKET_DESCRIPTION'             => $this->entries[$id]['description'],
                'TXT_MARKET_PLACE'                 => $txtplace,
                'MARKET_PLACE'                     => $place,
                'TXT_MARKET_PRICE'                 => $_ARRAYLANG['TXT_MARKET_PRICE'],
                'MARKET_PRICE'                     => $price,
                'TXT_MARKET_MESSAGE'             => $_ARRAYLANG['TXT_MARKET_SEND_MESSAGE'],
                'TXT_MARKET_TITLE'                 => $_ARRAYLANG['TXT_MARKET_TITLE'],
                'TXT_MARKET_MSG_TITLE'             => $_ARRAYLANG['TXT_MARKTE_MESSAGE_ABOUT'].' ',
                'TXT_MARKET_MSG'                 => $_ARRAYLANG['TXT_MARKET_MESSAGE'],
                'TXT_MARKET_SEND'                 => $_ARRAYLANG['TXT_MARKET_SEND'],
                'MARKET_ENDDATE'                 => $enddate,
                'TXT_FIELDS_REQUIRED'            => $_ARRAYLANG['TXT_MARKET_CATEGORY_ADD_FILL_FIELDS'],
                'TXT_THOSE_FIELDS_ARE_EMPTY'    => $_ARRAYLANG['TXT_MARKET_FIELDS_NOT_CORRECT'],
                'TXT_MARKET_NAME'                 => $_CORELANG['TXT_NAME'],
                'TXT_MARKET_EMAIL'                 => $_CORELANG['TXT_EMAIL'],
                'TXT_MARKET_PRICE_MSG'             => $_ARRAYLANG['TXT_MARKET_PRICE_IS'],
                'TXT_MARKET_NEW_PRICE'             => $_ARRAYLANG['TXT_PRICE_EXPECTATION'],
            ));

            $this->parseSpecialFields(
                $objDatabase,
                $this->_objTpl,
                $this->entries,
                $id
            );

            if ($this->_objTpl->blockExists('market_picture')) {
                if (!empty($this->entries[$id]['picture'])) {
                    $this->_objTpl->setVariable('MARKET_PICTURE', $image);
                    $this->_objTpl->parse('market_picture');
                } else {
                    $this->_objTpl->hideBlock('market_picture');
                }
            } else {
                $this->_objTpl->setVariable('MARKET_PICTURE', $image);
            }
        }else{
            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market');
        }
    }



    function sendMessage($id) {

        global $objDatabase, $_ARRAYLANG, $_CORELANG, $_CONFIG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //get entry
        $this->getEntries('', 'id', $id);

        if (isset($id) && count($this->entries) != 0) {
            //get search
            $this->getSearch();

            //get navigatin
            $this->getNavigation($this->entries[$id]['catid']);

            if ($_POST['title'] != '' && $_POST['message'] != '') {
                //create mail
                $sendTo        = $this->entries[$id]['email'];
                $fromName    = $_POST['name'];
                $fromMail    = $_POST['email'];
                $subject     = $_POST['title'];
                $newPrice     = $_POST['newprice']!='' ? "\n\n".$_ARRAYLANG['TXT_PRICE_EXPECTATION']."\n".$_POST['newprice'] : '';
                $oldPrice     = $_POST['price']!='' ? "\n\n".$_ARRAYLANG['TXT_MARKET_MESSAGE_PRICE']."\n".$_POST['price'] : '';
                $message     = $_POST['message'].$oldPrice.$newPrice;

                $objMail = new \Cx\Core\MailTemplate\Model\Entity\Mail();
                // use email of admin as sender address
                // this shall ensure compatibility with SPF 
                $objMail->SetFrom($_CONFIG['coreAdminEmail'], $fromName);
                $objMail->AddReplyTo($fromMail);
                $objMail->Subject = $subject;
                $objMail->IsHTML(false);
                $objMail->Body = $message;
                $objMail->AddAddress($sendTo);
                $objMail->Send();

                // set variables
                $this->_objTpl->setVariable(array(
                    'MARKET_TITLE'                    => $_ARRAYLANG['TXT_MARKET_MESSAGE_SUCCESS_TITLE'],
                    'MARKET_MSG_SEND'                => $_ARRAYLANG['TXT_MARKET_MESSAGE_SUCCESS_BODY'],
                    'TXT_MARKET_BACK'                => $_CORELANG['TXT_BACK'],
                ));
            }
        }else{
            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market');
        }
    }


    function checkEnddate()
    {
        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $today = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
        $objDatabase->Execute('UPDATE '.DBPREFIX.'module_market SET status = 0 WHERE enddate < '.$today.'');
    }


    function addEntry()
    {
        global $objDatabase, $_CORELANG, $_ARRAYLANG, $_CONFIG;


        if (!$this->settings['addEntry'] == '1' || (!$this->communityModul && $this->settings['addEntry_only_community'] == '1')) {
            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market');
            exit;
        }elseif ($this->settings['addEntry_only_community'] == '1') {
            $objFWUser = \FWUser::getFWUserObject();
            if ($objFWUser->objUser->login()) {
                if (!\Permission::checkAccess(99, 'static', true)) {
                    \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_DIRECTORY_INDEX."?section=Login&cmd=noaccess");
                    exit;
                }
            }else {
                $link = base64_encode(CONTREXX_DIRECTORY_INDEX.'?'.$_SERVER['QUERY_STRING']);
                \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_DIRECTORY_INDEX."?section=Login&redirect=".$link);
                exit;
            }
        } else {
            $objFWUser = \FWUser::getFWUserObject();
        }

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //get search
        $this->getSearch();

        //get navigatin
        $this->getNavigation('');

        $this->getCategories();
        $categories = '';
        foreach(array_keys($this->categories) as $catId) {
            $categories .= '<option value="'.$catId.'">'.$this->categories[$catId]['name'].'</option>';
        }

        $daysOnline = '';
        for($x = $this->settings['maxday']; $x >= 1; $x--) {
            $daysOnline .= '<option value="'.$x.'">'.$x.'</option>';
        }

        //get premium
        $objReslut = $objDatabase->Execute("SELECT price_premium FROM ".DBPREFIX."module_market_paypal WHERE id = '1'");
          if ($objReslut !== false) {
            while(!$objReslut->EOF) {
                $premium     = $objReslut->fields['price_premium'];
                $objReslut->MoveNext();
            }
          }

          if ($premium == '' || $premium == '0.00' || $premium == '0') {
              $premium = '';
          }else{
              $premium = $_ARRAYLANG['TXT_MARKET_ADDITIONAL_FEE'].$premium.' '.$_ARRAYLANG['TXT_MARKET_CURRENCY'];
          }

          if ($this->settings['maxdayStatus'] == 1) {
            $daysOnline = '';
            for($x = $this->settings['maxday']; $x >= 1; $x--) {
                $daysOnline .= '<option value="'.$x.'">'.$x.'</option>';
            }

            $daysJS = 'if (days.value == "") {
                            errorMsg = errorMsg + "- '.$_ARRAYLANG['TXT_MARKET_DURATION'].'\n";
                       }
                       ';
        }
        //initialize and get uploader object
        $uploader = $this->getUploader();

        $this->_objTpl->setVariable(array(
            'TXT_MARKET_NAME'                        =>    $_CORELANG['TXT_NAME'],
            'TXT_MARKET_EMAIL'                        =>    $_CORELANG['TXT_EMAIL'],
            'TXT_MARKET_TITLE_ENTRY'                =>    $_ARRAYLANG['TXT_MARKET_TITLE'],
            'TXT_MARKET_DESCRIPTION'                =>    $_CORELANG['TXT_DESCRIPTION'],
            'TXT_MARKET_SAVE'                        =>    $_CORELANG['TXT_ADD'],
            'TXT_MARKET_FIELDS_REQUIRED'            =>    $_ARRAYLANG['TXT_MARKET_CATEGORY_ADD_FILL_FIELDS'],
            'TXT_MARKET_THOSE_FIELDS_ARE_EMPTY'        =>    $_ARRAYLANG['TXT_MARKET_FIELDS_NOT_CORRECT'],
            'TXT_MARKET_PICTURE'                    =>    $_ARRAYLANG['TXT_MARKET_IMAGE'],
            'TXT_MARKET_CATEGORIE'                    =>    $_ARRAYLANG['TXT_MARKET_CATEGORY'],
            'TXT_MARKET_PRICE'                        =>    $_ARRAYLANG['TXT_MARKET_PRICE'].' '.$this->settings['currency'],
            'TXT_MARKET_TYPE'                        =>    $_CORELANG['TXT_TYPE'],
            'TXT_MARKET_OFFER'                        =>    $_ARRAYLANG['TXT_MARKET_OFFER'],
            'TXT_MARKET_SEARCH'                        =>    $_ARRAYLANG['TXT_MARKET_SEARCH'],
            'TXT_MARKET_FOR_FREE'                    =>    $_ARRAYLANG['TXT_MARKET_FREE'],
            'TXT_MARKET_AGREEMENT'                    =>    $_ARRAYLANG['TXT_MARKET_ARRANGEMENT'],
            'TXT_MARKET_END_DATE'                    =>    $_ARRAYLANG['TXT_MARKET_DURATION'],
            'END_DATE_JS'                            =>    $daysJS,
            'TXT_MARKET_ADDED_BY'                    =>    $_ARRAYLANG['TXT_MARKET_ADDEDBY'],
            'TXT_MARKET_USER_DETAIL'                =>    $_ARRAYLANG['TXT_MARKET_USERDETAILS'],
            'TXT_MARKET_DETAIL_SHOW'                =>    $_ARRAYLANG['TXT_MARKET_SHOW_IN_ADVERTISEMENT'],
            'TXT_MARKET_DETAIL_HIDE'                =>    $_ARRAYLANG['TXT_MARKET_NO_SHOW_IN_ADVERTISEMENT'],
            'TXT_MARKET_PREMIUM'                    =>    $_ARRAYLANG['TXT_MARKET_MARK_ADVERTISEMENT'],
            'TXT_MARKET_DAYS'                        =>    $_ARRAYLANG['TXT_MARKET_DAYS'],
            'TXT_MARKET_CHOOSE_FILE'                 =>  $_ARRAYLANG['TXT_MARKET_CHOOSE_FILE'],
            'MARKET_UPLOADER_CODE'                  =>  $uploader->getXHtml(),
            'MARKET_UPLOADER_ID'                    =>  $uploader->getId(),
            'TXT_MARKET_TERMS' => $_ARRAYLANG['TXT_MARKET_TERMS'],
            'TXT_MARKET_CONFIRM_TERMS' => $_ARRAYLANG['TXT_MARKET_CONFIRM_TERMS'],
            'MARKET_FORCE_TERMS'    => $this->settings['useTerms'],
        ));

        if ($this->settings['maxdayStatus'] != 1) {
            $this->_objTpl->hideBlock('end_date_dropdown');
        }

        $objReslut = $objDatabase->Execute("SELECT id, name, value FROM ".DBPREFIX."module_market_spez_fields WHERE lang_id = '1' AND active='1' ORDER BY id DESC");
        if ($objReslut !== false) {
            $i = 0;
            while(!$objReslut->EOF) {
                $this->_objTpl->setCurrentBlock('spez_fields');

// TODO: Never used
//                ($i % 2)? $class = "row2" : $class = "row1";
                $input = '<input type="text" name="spez_'.$objReslut->fields['id'].'" style="width: 300px;" maxlength="100">';

                // initialize variables
                $this->_objTpl->setVariable(array(
                    'TXT_MARKET_SPEZ_FIELD_NAME'    => $objReslut->fields['value'],
                    'MARKET_SPEZ_FIELD_INPUT'          => $input,
                ));

                $this->_objTpl->parse('spez_fields');
                $i++;
                $objReslut->MoveNext();
            }
          }

        $this->_objTpl->setVariable(array(
            'TXT_MARKET_PREMIUM_CONDITIONS'            =>    $premium,
            'MARKET_CATEGORIES'                        =>    $categories,
            'MARKET_ENTRY_ADDEDBY'                    =>    htmlentities($objFWUser->objUser->getUsername(), ENT_QUOTES, CONTREXX_CHARSET),
            'MARKET_ENTRY_USERDETAILS_ON'            =>    "checked",
            'MARKET_ENTRY_TYPE_OFFER'                =>    "checked",
            'MARKET_DAYS_ONLINE'                    =>    $daysOnline,
        ));
    }



    function confirmEntry() {

        global $objDatabase, $_ARRAYLANG, $_CONFIG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //get search
        $this->getSearch();

        //get navigatin
        $this->getNavigation($this->entries[$id]['catid']);

        if (isset($_POST['submitEntry']) || isset($_POST['submit'])) {

            if (isset($_POST['submitEntry'])) {
                $id = $this->insertEntry('0');
            }

            // check if entries shall be confirmed through the frontend
            if (!$this->settings['confirmFrontend']) {
                // move to overview
                \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market');
            }

            if (isset($_POST['submit'])) {
                $id         = contrexx_addslashes($_POST['id']);
                $regkey     = contrexx_addslashes($_POST['regkey']);

                $objResult = $objDatabase->Execute("SELECT id, regkey, userid FROM ".DBPREFIX."module_market WHERE id = '".$id."' AND regkey='".$regkey."'");
                if ($objResult !== false) {
                    $count = $objResult->RecordCount();
                    while(!$objResult->EOF) {
                        $today = mktime(0, 0, 0, date("m")  , date("d"), date("Y"));
                        $objResultUpdate = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market SET status='1', regkey='', regdate='".$today."' WHERE id='".$objResult->fields['id']."'");

                        $this->sendMail($id);

                        if ($objResultUpdate !== false) {
                            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market&cmd=detail&id='.$objResult->fields['id'].'');
                        }

                        $objResult->MoveNext();
                    }
                 }

                 if ($count == 0) {
                     $error = $_ARRAYLANG['TXT_MARKET_CLEARING_CODE_NOT_EXISTING'];
                 }
            }

            //get paypal
            $objResult = $objDatabase->Execute("SELECT value FROM ".DBPREFIX."module_market_settings WHERE id = '11'");
              if ($objResult !== false) {
                while(!$objResult->EOF) {
                    $codeMode         = $objResult->fields['value'];
                    $objResult->MoveNext();
                }
              }

              if ($codeMode == '0') {
                  $this->_objTpl->touchBlock('infoText');
                  $this->_objTpl->hideBlock('codeForm');
              }else{
                  $confirmForm    = '<form action="index.php?section=Market&amp;cmd=confirm" method="post" name="marketSearch" id="marketAGB">
                                   <input type="hidden" name="id" value="'.$id.'" >
                                   <input id="regkey" name="regkey" value="" size="25" maxlength="100" />&nbsp;<input id="submit" type="submit" value="Freischalten" name="submit" />
                                   </form>';

                  $this->_objTpl->parse('codeForm');
                  $this->_objTpl->hideBlock('infoText');
              }

            // set variables
            $this->_objTpl->setVariable(array(
                'TXT_MARKET_TITLE'            => $_ARRAYLANG['TXT_MARKET_REQUIREMENTS'],
                'TXT_MARKET_AGB'            => $_ARRAYLANG['TXT_MARKET_AGB'],
                'TXT_MARKET_CONFIRM'        => $_ARRAYLANG['TXT_MARKET_AGB_ACCEPT'],
                'MARKET_ERROR_CONFIRM'        => $error,
                'MARKET_FORM'                => $confirmForm,
            ));

        }else{
            \Cx\Core\Csrf\Controller\Csrf::header('Location: ?section=Market&cmd=add');
        }
    }


    /**
     * Parse Search entry list
     */
    public function searchEntry()
    {

        global $objDatabase, $_ARRAYLANG, $_CORELANG, $_CONFIG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        //get search
        $this->getSearch();

        //get navigatin
        $this->getNavigation('');

        //spez fields
        $objResult = $objDatabase->Execute("SELECT id, value FROM ".DBPREFIX."module_market_spez_fields WHERE lang_id = '1'");
        if ($objResult !== false) {
            while(!$objResult->EOF) {
                $spezFields[$objResult->fields['id']] = $objResult->fields['value'];
                $objResult->MoveNext();
            }
        }

        // set variables
        $this->_objTpl->setVariable(array(
            'MARKET_SEARCH_PAGING'            => $paging,
            'TXT_MARKET_ENDDATE'            => $_CORELANG['TXT_END_DATE'],
            'TXT_MARKET_TITLE'                => $_ARRAYLANG['TXT_MARKET_TITLE'],
            'TXT_MARKET_PRICE'                => $_ARRAYLANG['TXT_MARKET_PRICE'],
            'TXT_MARKET_CITY'                => $_ARRAYLANG['TXT_MARKET_CITY'],
        ));

        $this->parseSpecialFields(
            $objDatabase,
            $this->_objTpl,
            array(),
            0,
            'txt'
        );

        $searchTermOrg = isset($_GET['term']) ? contrexx_input2raw($_GET['term']) : '';
        $catId         = isset($_GET['catid']) ? contrexx_input2int($_GET['catid']) : 0;
        $type          = isset($_GET['type']) ? contrexx_input2raw($_GET['type']) : '';
        $searchPrice   = isset($_GET['price']) ? contrexx_input2raw($_GET['price']) : '';
        $check         = isset($_GET['check']) ? contrexx_input2raw($_GET['check']) : '';
        $where         = array('status = 1');
        $tmpTerm       = '';
        $array = explode(' ', $searchTermOrg);
        for($x = 0; $x < count($array); $x++) {
            $tmpTerm .= $array[$x].'%';
        }

        $searchTerm    = substr($tmpTerm, 0, -1);
        $searchTermExp = "&amp;check=norm&amp;term=".$searchTermOrg;
        if ($_GET['check'] == 'exp') {
            $searchTermExp = "&amp;check=exp&amp;term=".$searchTermOrg;
            if (!empty($catId)) {
                $where[] = 'catid = ' . contrexx_raw2db($catId);
                $searchTermExp .= '&amp;catid=' . $catId;
            }

            if (!empty($type)) {
                $where[] = 'type LIKE ("%' . contrexx_raw2db($type) . '%")';
                $searchTermExp .= '&amp;type=' . $type;
            }

            if (!empty($searchPrice)) {
                $where[] = 'price <= ' . contrexx_raw2db($searchPrice);
                $searchTermExp .= '&amp;price=' . $searchPrice;
            }
        }

        switch ($_GET['sort']) {
            case 'title':
                $sort = "title";
                $sortPaging = "&amp;sort=title";
                break;
            case 'enddate':
                $sort = "enddate";
                $sortPaging = "&amp;sort=enddate";
                break;
            case 'price':
                $sort = "price";
                $sortPaging = "&amp;sort=price";
                break;
            default:
                $sort = "sort_id, enddate";
                $sortPaging = "";
                break;
        }

        $way = isset($_GET['way']) && ($_GET['way'] == 'ASC') ? 'DESC' : 'ASC';
        $this->_objTpl->setVariable(array(
            'MARKET_ENDDATE_SORT'   => "index.php?section=Market&amp;cmd=search&amp;id=".$catId.$searchTermExp."&amp;sort=enddate&amp;way=".$way,
            'MARKET_TITLE_SORT'     => "index.php?section=Market&amp;cmd=search&amp;id=".$catId.$searchTermExp."&amp;sort=title&amp;way=".$way,
            'MARKET_PRICE_SORT'     => "index.php?section=Market&amp;cmd=search&amp;id=".$catId.$searchTermExp."&amp;sort=price&amp;way=".$way,
        ));

        $score = '';
        $specialFieldsQuery = $this->getSpecialFieldsQueryPart($objDatabase);
        if (!empty($searchTermOrg)) {
            $specialFieldsComparision = $this->getSpecialFieldsQueryPart(
                $objDatabase,
                null,
                'LIKE',
                '("%' . contrexx_raw2db($searchTerm) . '%")',
                'OR '
            );
            $where[] = '(title LIKE ("%' . contrexx_raw2db($searchTerm) . '%")
                OR description LIKE ("%' . contrexx_raw2db($searchTerm) . '%")
                OR ' . $specialFieldsComparision . ')';
            $score = ', MATCH (title,description) AGAINST ("%' . contrexx_raw2db($searchTerm) . '%") AS score';
        }
        $query   =
            'SELECT id,
                    title,
                    description,
                    price,
                    picture,
                    userid,
                    enddate,
                    premium,
                    ' . $specialFieldsQuery . $score . '
            FROM ' . DBPREFIX . 'module_market' .
            (!empty($where) ? ' WHERE ' . implode(' AND ', $where) : '') .
            ' ORDER BY ' . (!empty($score) ? 'score DESC, ' : '') . $sort . ' ' . $way;

        /////// START PAGING ///////
        $pos = intval($_GET['pos']);
        $objResult = $objDatabase->Execute($query);
        $count = $objResult ? $objResult->RecordCount() : 0;
        if ($count > $this->settings['paging']) {
            $paging = getPaging($count, $pos, "&amp;section=Market&amp;cmd=search".$searchTermExp."&amp;sort=".$sort."&amp;way=".$way, "<b>Inserate</b>", true, $this->settings['paging']);
        }
        $this->_objTpl->setVariable('SEARCH_PAGING', $paging);
        $objResult = $objDatabase->SelectLimit($query, $this->settings['paging'], $pos);
        /////// END PAGING ///////

        if ($objResult !== false) {
            $i = 0;
            while (!$objResult->EOF) {
                if (empty($objResult->fields['picture'])) {
                    $objResult->fields['picture'] = 'no_picture.gif';
                }

                $info = getimagesize($this->mediaPath . 'pictures/' . $objResult->fields['picture']);
                $height = '';
                $width = '';

                if ($info[0] <= $info[1]) {
                    if ($info[1] > 50) {
                        $faktor = $info[1] / 50;
                        $height = 50;
                        $width = $info[0] / $faktor;
                    } else {
                        $height = $info[1];
                        $width = $info[0];
                    }
                } else {
                    $faktor = $info[0] / 80;
                    $result = $info[1] / $faktor;
                    if ($result > 50) {
                        if ($info[1] > 50) {
                            $faktor = $info[1] / 50;
                            $height = 50;
                            $width = $info[0] / $faktor;
                        } else {
                            $height = $info[1];
                            $width = $info[0];
                        }
                    } else {
                        if ($info[0] > 80) {
                            $width = 80;
                            $height = $info[1] / $faktor;
                        } else {
                            $width = $info[0];
                            $height = $info[1];
                        }
                    }
                }

                $width != '' ? $width = 'width="' . round($width, 0) . '"' : $width = '';
                $height != '' ? $height = 'height="' . round($height, 0) . '"' : $height = '';

                $image = '<img src="' . $this->mediaWebPath . 'pictures/' . $objResult->fields['picture'] . '" ' . $width . '" ' . $height . '" border="0" alt="' . $objResult->fields['title'] . '" />';


                $objFWUser = \FWUser::getFWUserObject();
                $objUser = $objFWUser->objUser->getUser($objResult->fields['userid']);
                if ($objUser) {
                    $city = $objUser->getProfileAttribute('city');
                }
                if ($objResult->fields['premium'] == 1) {
                    $row = "marketRow1";
                } else {
                    $row = $i % 2 == 0 ? "marketRow2" : "marketRow3";
                }

                $enddate = date("d.m.Y", $objResult->fields['enddate']);

                if ($objResult->fields['price'] == 'forfree') {
                    $price = $_ARRAYLANG['TXT_MARKET_FREE'];
                } elseif ($objResult->fields['price'] == 'agreement') {
                    $price = $_ARRAYLANG['TXT_MARKET_ARRANGEMENT'];
                } else {
                    $price = $objResult->fields['price'] . ' ' . $this->settings['currency'];
                }
                
                $this->_objTpl->setVariable(array(
                    'MARKET_ENDDATE' => $enddate,
                    'MARKET_TITLE' => $objResult->fields['title'],
                    'MARKET_DESCRIPTION' => substr($objResult->fields['description'], 0, 110) . "<a href='index.php?section=Market&amp;cmd=detail&amp;id=" . $objResult->fields['id'] . "' target='_self'>[...]</a>",
                    'MARKET_PRICE' => $price,
                    'MARKET_PICTURE' => $image,
                    'MARKET_ROW' => $row,
                    'MARKET_DETAIL' => "index.php?section=Market&amp;cmd=detail&amp;id=" . $objResult->fields['id'],
                    'MARKET_ID' => $objResult->fields['id'],
                    'MARKET_CITY' => $city,
                ));

                $this->parseSpecialFields(
                        $objDatabase, $this->_objTpl, $objResult->fields, 0, 'val'
                );

                $this->_objTpl->parse('showEntries');
                $objResult->MoveNext();
                $i++;
            }
        }

        if ($count <= 0) {
            $this->_objTpl->setVariable(array(
                'MARKET_NO_ENTRIES_FOUND' => $_ARRAYLANG['TXT_MARKET_NO_ENTRIES_FOUND'],
            ));

            $this->_objTpl->parse('noEntries');
            $this->_objTpl->hideBlock('showEntries');
        }

        $showExpandSearch = false;
        if (
            $check == 'exp' &&
            (!empty($catId) || !empty($type) || !empty($searchPrice))
        ) {
            $showExpandSearch = true;
        }
        $this->_objTpl->setVariable(array(
            'TXT_MARKET_SEARCHTERM'        => $searchTermOrg,
            'TXT_MARKET_EXP_SEARCH'        => $showExpandSearch ? 'exp' : 'norm',
            'TXT_MARKET_EXP_SEARCH_TOGGLE' => $showExpandSearch
                ? 'display:block;' : 'display:none;'
        ));

        $this->_objTpl->parse('showEntriesHeader');
    }

    /**
     * Edit the advertisement entry
     *
     * @return null
     */

    public function editEntry() {

        global $objDatabase, $_ARRAYLANG, $_CORELANG, $_CONFIG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        if (!$this->settings['editEntry'] == '1' || (!$this->communityModul && $this->settings['addEntry_only_community'] == '1')) {
            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market&cmd=detail&id='.$_POST['id']);
            exit;
        }elseif ($this->settings['addEntry_only_community'] == '1') {
            $objFWUser = \FWUser::getFWUserObject();
            if ($objFWUser->objUser->login()) {
                if (!\Permission::checkAccess(100, 'static', true)) {
                    \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_DIRECTORY_INDEX."?section=Login&cmd=noaccess");
                    exit;
                }
            }else {
                $link = base64_encode(CONTREXX_DIRECTORY_INDEX.'?'.$_SERVER['QUERY_STRING']);
                \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_DIRECTORY_INDEX."?section=Login&redirect=".$link);
                exit;
            }
        } else {
            $objFWUser = \FWUser::getFWUserObject();
        }

        //get search
        $this->getSearch();

        //initialize and get uploader object
        $uploader = $this->getUploader();
        $this->_objTpl->setVariable(array(
            'TXT_MARKET_TITLE'                        =>    $_ARRAYLANG['TXT_EDIT_ADVERTISEMENT'],
            'TXT_MARKET_TITLE_ENTRY'                =>    $_ARRAYLANG['TXT_MARKET_TITLE'],
            'TXT_MARKET_NAME'                        =>    $_CORELANG['TXT_NAME'],
            'TXT_MARKET_EMAIL'                        =>    $_CORELANG['TXT_EMAIL'],
            'TXT_MARKET_DESCRIPTION'                =>    $_CORELANG['TXT_DESCRIPTION'],
            'TXT_MARKET_SAVE'                        =>    $_CORELANG['TXT_SAVE'],
            'TXT_MARKET_FIELDS_REQUIRED'            =>    $_ARRAYLANG['TXT_MARKET_CATEGORY_ADD_FILL_FIELDS'],
            'TXT_MARKET_THOSE_FIELDS_ARE_EMPTY'        =>    $_ARRAYLANG['TXT_MARKET_FIELDS_NOT_CORRECT'],
            'TXT_MARKET_PICTURE'                    =>    $_CORELANG['TXT_IMAGE'],
            'TXT_MARKET_CATEGORIE'                    =>    $_CORELANG['TXT_CATEGORY'],
            'TXT_MARKET_PRICE'                        =>    $_ARRAYLANG['TXT_MARKET_PRICE'].' '.$this->settings['currency'],
            'TXT_MARKET_TYPE'                        =>    $_CORELANG['TXT_TYPE'],
            'TXT_MARKET_OFFER'                        =>    $_ARRAYLANG['TXT_MARKET_OFFER'],
            'TXT_MARKET_SEARCH'                        =>    $_ARRAYLANG['TXT_MARKET_SEARCH'],
            'TXT_MARKET_FOR_FREE'                    =>    $_ARRAYLANG['TXT_MARKET_FREE'],
            'TXT_MARKET_AGREEMENT'                    =>    $_ARRAYLANG['TXT_MARKET_ARRANGEMENT'],
            'TXT_MARKET_ADDED_BY'                    =>    $_ARRAYLANG['TXT_MARKET_ADDEDBY'],
            'TXT_MARKET_USER_DETAIL'                =>    $_ARRAYLANG['TXT_MARKET_USERDETAILS'],
            'TXT_MARKET_DETAIL_SHOW'                =>    $_ARRAYLANG['TXT_MARKET_SHOW_IN_ADVERTISEMENT'],
            'TXT_MARKET_DETAIL_HIDE'                =>    $_ARRAYLANG['TXT_MARKET_NO_SHOW_IN_ADVERTISEMENT'],
            'TXT_MARKET_CHOOSE_FILE'                =>  $_ARRAYLANG['TXT_MARKET_CHOOSE_FILE'],
            'MARKET_UPLOADER_CODE'                  =>  $uploader->getXHtml(),
            'MARKET_UPLOADER_ID'                    =>  $uploader->getId()
        ));

        if (isset($_GET['id'])) {
            $entryId = contrexx_addslashes($_GET['id']);
            $specFieldsQuery = $this->getSpecialFieldsQueryPart($objDatabase);
            $objResult = $objDatabase->Execute('SELECT type, title, description, premium, picture, catid, price, regdate, enddate, userid, name, email, userdetails, ' . $specFieldsQuery . ' FROM '.DBPREFIX.'module_market WHERE id = '.$entryId.' LIMIT 1');
            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    if ($objFWUser->objUser->login() && $objFWUser->objUser->getId()==$objResult->fields['userid'] || \Permission::hasAllAccess()) {
                        //entry type
                        if ($objResult->fields['type'] == 'offer') {
                            $offer     = 'checked';
                            $search    = '';
                        }else{
                            $offer     = '';
                            $search    = 'checked';
                        }

                        //entry price
                        if ($objResult->fields['price'] == 'forfree') {
                            $forfree     = 'checked';
                            $price         = '';
                            $agreement     = '';
                        }elseif ($objResult->fields['price'] == 'agreement') {
                            $agreement    = 'checked';
                            $price         = '';
                            $forfree     = '';
                        }else{
                            $price         = $objResult->fields['price'];
                            $forfree     = '';
                            $agreement     = '';
                        }

                        //entry user
                        $objResultUser = $objDatabase->Execute('SELECT username FROM '.DBPREFIX.'access_users WHERE id = '.$objResult->fields['userid'].' LIMIT 1');
                        if ($objResultUser !== false) {
                            $addedby = $objResultUser->fields['username'];
                        }

                        //entry userdetails
                        if ($objResult->fields['userdetails'] == '1') {
                            $userdetailsOn         = 'checked';
                            $userdetailsOff     = '';
                        }else{
                            $userdetailsOn         = '';
                            $userdetailsOff     = 'checked';
                        }

                        //entry picture
                        if ($objResult->fields['picture'] != '') {
                            $picture         = '<img width="100" src="'.$this->mediaWebPath.'pictures/'.$objResult->fields['picture'].'" border="0" alt="" /><br /><br />';
                        }else{
                            $picture         = '<img src="'.$this->mediaWebPath.'pictures/no_picture.gif" border="0" alt="" /><br /><br />';
                        }

                        //entry category
                        $this->getCategories();
                        $categories     = '';
                        $checked         = '';
// TODO: Never used
//                        $catID            = $objResult->fields['catid'];
                        foreach(array_keys($this->categories) as $catId) {
                            $catId == $objResult->fields['catid'] ? $checked = 'selected' : $checked = '';
                            $categories .= '<option value="'.$catId.'" '.$checked.'>'.$this->categories[$catId]['name'].'</option>';
                        }

                        //spez fields
                        $objSpezFields = $objDatabase->Execute("SELECT id, name, value FROM ".DBPREFIX."module_market_spez_fields WHERE lang_id = '1' AND active='1' ORDER BY id DESC");
                          if ($objSpezFields !== false) {
                            while(!$objSpezFields->EOF) {

// TODO: Never used
//                                ($i % 2)? $class = "row2" : $class = "row1";
                                $input = '<input type="text" name="spez_'.$objSpezFields->fields['id'].'" value="'.$objResult->fields[$objSpezFields->fields['name']].'" style="width: 300px;" maxlength="100">';

                                // initialize variables
                                $this->_objTpl->setVariable(array(
                                    'TXT_MARKET_SPEZ_FIELD_NAME'        => $objSpezFields->fields['value'],
                                    'MARKET_SPEZ_FIELD_INPUT'              => $input,
                                ));

                                $this->_objTpl->parse('spez_fields');
// TODO: $class is never used
//                                $i++;
                                $objSpezFields->MoveNext();
                            }
                          }


                        $this->_objTpl->setVariable(array(
                            'MARKET_ENTRY_ID'                    =>    $entryId,
                            'MARKET_ENTRY_TYPE_OFFER'            =>    $offer,
                            'MARKET_ENTRY_TYPE_SEARCH'            =>    $search,
                            'MARKET_ENTRY_TITLE'                =>    $objResult->fields['title'],
                            'MARKET_ENTRY_DESCRIPTION'            =>    $objResult->fields['description'],
                            'MARKET_ENTRY_PICTURE'                =>    $picture,
                            'MARKET_ENTRY_PICTURE_OLD'            =>    $objResult->fields['picture'],
                            'MARKET_CATEGORIES'                    =>    $categories,
                            'MARKET_ENTRY_PRICE'                =>    $price,
                            'MARKET_ENTRY_FORFREE'                =>    $forfree,
                            'MARKET_ENTRY_AGREEMENT'            =>    $agreement,
                            'MARKET_ENTRY_ADDEDBY'                =>    $addedby,
                            'MARKET_ENTRY_ADDEDBY_ID'            =>    $objResult->fields['userid'],
                            'MARKET_ENTRY_USERDETAILS_ON'        =>    $userdetailsOn,
                            'MARKET_ENTRY_USERDETAILS_OFF'        =>    $userdetailsOff,
                            'MARKET_ENTRY_NAME'                    =>    $objResult->fields['name'],
                            'MARKET_ENTRY_EMAIL'                =>    $objResult->fields['email'],
                        ));
                           $objResult->MoveNext();
                       }else{
                        \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market&cmd=detail&id='.$_GET['id']);
                        exit;
                    }
                }

                //get navigatin
                $this->getNavigation($catID);
            }
        }else{
            if (isset($_POST['submitEntry'])) {
                if ($_POST['uploadImage'] != "") {
                    $picture = $this->uploadPicture();
                    if ($picture != "error") {
                        $objFile = new \File();
                        $objFile->delFile($this->mediaPath, $this->mediaWebPath, "pictures/".$_POST['picOld']);
                    }
                }else{
                    $picture = $_POST['picOld'];
                }

                if ($picture != "error") {
                    if ($_POST['forfree'] == 1) {
                        $price = "forfree";
                    }elseif ($_POST['agreement'] == 1) {
                        $price = "agreement";
                    }else{
                        $price = contrexx_addslashes($_POST['price']);
                    }

                    $specialFieldsQuery = $this->getSpecialFieldsQueryPart(
                        $objDatabase,
                        $_POST
                    );
                    $objResult = $objDatabase->Execute("UPDATE ".DBPREFIX."module_market SET
                                        type='".contrexx_addslashes($_POST['type'])."',
                                          title='".contrexx_addslashes($_POST['title'])."',
                                          description='".contrexx_addslashes($_POST['description'])."',
                                          picture='".contrexx_addslashes($picture)."',
                                          catid='".contrexx_addslashes($_POST['cat'])."',
                                          price='".$price."',
                                          name='".contrexx_addslashes($_POST['name'])."',
                                          email='".contrexx_addslashes($_POST['email'])."',
                                          " . $specialFieldsQuery . ",
                                          userdetails='".contrexx_addslashes($_POST['userdetails'])."'
                                          WHERE id='".contrexx_addslashes($_POST['id'])."'");

                    if ($objResult !== false) {
                        \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market&cmd=detail&id='.$_POST['id']);
                        exit;
                    }else{
// TODO: Never used
//                        $error = $_CORELANG['TXT_DATABASE_QUERY_ERROR'];
                        \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market&cmd=edit&id='.$_POST['id']);
                        exit;
                    }
                }else{
// TODO: Never used
//                    $error = $_CORELANG['TXT_MARKET_IMAGE_UPLOAD_ERROR'];
                    \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market&cmd=edit&id='.$_POST['id']);
                    exit;
                }
            }else{
                \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market');
                exit;
            }
        }
    }



    function delEntry() {

        global $objDatabase, $_ARRAYLANG, $_CORELANG, $_CONFIG;

        $this->_objTpl->setTemplate($this->pageContent, true, true);

        if (!$this->settings['editEntry'] == '1' || (!$this->communityModul && $this->settings['addEntry_only_community'] == '1')) {
            \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market&cmd=detail&id='.$_POST['id']);
            exit;
        }elseif ($this->settings['addEntry_only_community'] == '1') {
            $objFWUser = \FWUser::getFWUserObject();
            if ($objFWUser->objUser->login()) {
                if (!\Permission::checkAccess(101, 'static', true)) {
                    \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_DIRECTORY_INDEX."?section=Login&cmd=noaccess");
                    exit;
                }
            }else {
                $link = base64_encode(CONTREXX_DIRECTORY_INDEX.'?'.$_SERVER['QUERY_STRING']);
                \Cx\Core\Csrf\Controller\Csrf::header("Location: ".CONTREXX_DIRECTORY_INDEX."?section=Login&redirect=".$link);
                exit;
            }
        } else {
            $objFWUser = \FWUser::getFWUserObject();
        }

        //get search
        $this->getSearch();

        if (isset($_GET['id'])) {
            $entryId =contrexx_addslashes($_GET['id']);
            $objResult = $objDatabase->Execute('SELECT id, userid, catid FROM '.DBPREFIX.'module_market WHERE id = '.$entryId.' LIMIT 1');
            if ($objResult !== false) {
                while (!$objResult->EOF) {
                    if ($objFWUser->objUser->login() && $objFWUser->objUser->getId()==$objResult->fields['userid'] || \Permission::hasAllAccess()) {
                        $this->_objTpl->setVariable(array(
                            'MARKET_ENTRY_ID'                    =>    $entryId,
                            'TXT_MARKET_DEL'                    =>    $_ARRAYLANG['TXT_MARKET_DELETE_ADVERTISEMENT'],
                            'TXT_MARKET_ABORT'                    =>    $_CORELANG['TXT_CANCEL'],
                            'TXT_MARKET_CONFIRM_DEL'            =>    $_ARRAYLANG['TXT_MARKET_ADVERTISEMENT_DELETE'],
                        ));

                        //get navigatin
                        $this->getNavigation($objResult->fields['catid']);

                        $objResult->MoveNext();
                    }else{
                        \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market&cmd=detail&id='.$_GET['id']);
                        exit;
                    }
                }
            }
        }else{
            if (isset($_POST['submitEntry'])) {

                $arrDelete = array();
                $arrDelete[0] = $_POST['id'];
                $this->removeEntry($arrDelete);

                \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market');
                exit;
            }else{
                \Cx\Core\Csrf\Controller\Csrf::header('Location: index.php?section=Market');
                exit;
            }
        }
    }


    /**
    * Get Market Latest Entrees
    *
    * getContentLatest
    *
    * @access    public
    * @param    string $pageContent
    * @param     string
    */
    function getBlockLatest()
    {
        global $objDatabase, $objTemplate;

        //get latest
        $query = "SELECT id, title, enddate, catid
                    FROM ".DBPREFIX."module_market
                   WHERE status = '1'
                ORDER BY id DESC
                   LIMIT 5";

        $objResult = $objDatabase->Execute($query);
        if ($objResult !== false) {
            while (!$objResult->EOF) {
                // set variables
                $objTemplate->setVariable('MARKET_DATE', date("d.m.Y", $objResult->fields['enddate']));
                $objTemplate->setVariable('MARKET_TITLE', $objResult->fields['title']);
                $objTemplate->setVariable('MARKET_ID', $objResult->fields['id']);
                $objTemplate->setVariable('MARKET_CATID', $objResult->fields['catid']);

                $objTemplate->parse('marketLatest');


                $objResult->MoveNext();
            }
        }
    }
}
