<?php
/**
 * Livecam
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author		Comvation Development Team <info@comvation.com>
 * @version		1.0.0
 * @package     contrexx
 * @subpackage  module_livecam
 * @todo        Edit PHP DocBlocks!
 */

/**
 * Includes
 */
require_once ASCMS_MODULE_PATH.'/livecam/lib/livecamLib.class.php';

/**
 * Livecam
 * @copyright   CONTREXX CMS - COMVATION AG
 * @author		Comvation Development Team <info@comvation.com>
 * @access		public
 * @version		1.0.0
 * @package     contrexx
 * @subpackage  module_livecam
 */
class Livecam extends LivecamLibrary
{
	/**
	* Template object
	*
	* @access private
	* @var object
	*/
    var $_objTpl;
    
    /**
    * Status message
    *
    * @access public
    * @var string
    */
    var $statusMessage;
    
    /**
    * Archive thumbnail links 
    *
    * @access private
    * @var array
    */
    var $_arrArchiveThumbs = array();
    
    /**
    * Action
    *
    * @access private
    * @var string
    */
    var $_action = '';
    
    /**
    * Picture template placeholder
    *
    * @access private
    * @var string
    */
    var $_pictureTemplatePlaceholder = 'livecamArchivePicture';
    
    /**
    * Date
    *
    * @access public
    * @var string
    */
    var $date;
    
	/**
    * Constructor
    *
    * @param  string $pageContent
    * @access public
    */   
    function Livecam($pageContent)
    { 
    	$this->__construct($pageContent);    
    }
    
    /**
     * PHP5 constructor
     * @param  string  $pageContent
     * @access public
     */
    function __construct($pageContent)
    {  
	    $this->pageContent = $pageContent;
	    
	    $this->_objTpl = &new HTML_Template_Sigma('.');
		$this->_objTpl->setErrorHandling(PEAR_ERROR_DIE);  	
    	
		$this->_getAction();
		$this->_getDate();
		
		// get the livecam settings
    	$this->getSettings();
	}
	
	/**
	* Get action
	*
	* Get the action that should be executed
	*
	* @access private
	*/
	function _getAction()
	{
		if (isset($_REQUEST['act'])) {
			if (is_array($_REQUEST['act'])) {
				$this->_action = key($_REQUEST['act']);
			} else {
				$this->_action = $_REQUEST['act'];
			}
		}
	}
	
	/**
	* Get date
	*
	* Get the date to be used
	*
	* @access private
	*/
	function _getDate()
	{
		if ($this->_action == 'archive') {
			$this->date = contrexx_strip_tags($_REQUEST['date']);
		} else {
			$d = date("d");
			$m = date("m");
			$y = date("Y");
			
			$this->date = $y."-".$m."-".$d;
		}
	}
	
	/**
	* Get page
	*
	* Get the livecam page
	*
	* @access public
	* @return string
	*/
	function getPage()
	{
		$this->_objTpl->setTemplate($this->pageContent);
		
		$this->_objTpl->setVariable('LIVECAM_JAVASCRIPT', $this->_getJavascript());
		$this->_objTpl->setGlobalVariable('LIVECAM_DATE', $this->date);
		
    	switch ($this->_action) {
    	case 'today':
    		$this->_objTpl->hideBlock('livecamPicture');
    		$this->_showArchive($this->date);
    		break;
    		
		case 'archive':
			$this->_objTpl->hideBlock('livecamPicture');
	    	$this->_showArchive($this->date);
	    	break;
	    	
	    default:
	    	$this->_objTpl->hideBlock('livecamArchive');
	    	$this->_showPicture();
	        break;
    	}
    	
    	if (isset($this->statusMessage)) {
			$this->_objTpl->setVariable('LIVECAM_STATUS_MESSAGE', $this->statusMessage);
    	}
    	
    	return $this->_objTpl->get(); 
    }
    
    /**
    * Show picture
    *
    * Either show the current picture of the livecam or one from the archive
    *
    * @access private
    */
    function _showPicture()
    {
    	$this->_objTpl->setVariable(array(
    		'LIVECAM_CURRENT_IMAGE'	=> isset($_GET['file']) ? ASCMS_PATH_OFFSET.$this->arrSettings['archivePath'].'/'.$_GET['file'] : $this->arrSettings['currentImageUrl'],
    		'LIVECAM_IMAGE_TEXT'	=> isset($_GET['file']) ? contrexx_strip_tags($_GET['file']) : 'Aktuelles Webcam Bild'
    	));
    }


	/**
	 * Sort helper for sorting the thumbnails by time
	 */
	function _sort_thumbs($a, $b) {
		$timea = $a['time'];
		$timeb = $b['time'];

		// No equal times to be expected, therefore
		// we don't check for equality.
		if ($timea>$timeb) {
			return 1;
		}
		return -1;
	}


    
    /**
    * Show archive
    *
    * Show the livecam archive from a specified date
    *
    * @access private
    * @param string $date
    */
    function _showArchive($date)
    {
    	$this->_getThumbs();
    	
    	if (count($this->_arrArchiveThumbs)>0) {
    		$countPerRow;
    		$picNr = 1;

			usort($this->_arrArchiveThumbs, array($this, '_sort_thumbs'));

    		foreach ($this->_arrArchiveThumbs as $arrThumbnail) {
    			if (!isset($countPerRow)) {
    				if (!$this->_objTpl->blockExists($this->_pictureTemplatePlaceholder.$picNr)) {
    					$this->_objTpl->parse('livecamArchiveRow');
    					
    					$countPerRow = $picNr-1;
    					$picNr = 1;
    				}
    			}
    			
				$this->_objTpl->setVariable(array(
					'LIVECAM_PICTURE_URL'	=> $arrThumbnail['link_url'],
					'LIVECAM_PICTURE_TIME'	=> $arrThumbnail['time'],
					'LIVECAM_THUMBNAIL_URL'	=> $arrThumbnail['image_url']
				));
				$this->_objTpl->parse($this->_pictureTemplatePlaceholder.$picNr);
				
				if (isset($countPerRow) && $picNr == $countPerRow) {
					$picNr = 0;
					$this->_objTpl->parse('livecamArchiveRow');
				}
				
    			$picNr++;
    		}
    		$this->_objTpl->parse('livecamArchive');
    	} else {
    		$this->statusMessage = 'Von diesem Tag sind leider keine Bilder vorhanden.';
    		$this->_objTpl->hideBlock('livecamArchive');
    	}
    }
    
    /**
    * Get thumbnails
    *
    * Get the thumbnails from a day in the archive.
    * Create the thumbnails if they don't already exists.
    *
    * @access private
    */
	function _getThumbs() {
		require_once ASCMS_FRAMEWORK_PATH.'/File.class.php';
		
		$path = ASCMS_DOCUMENT_ROOT.$this->arrSettings['archivePath'].'/'.$this->date.'/';
		$objDirectory = @opendir($path);
		$objFile = &new File();
		$chmoded = false;
		
		if ($objDirectory) {
			while ($file = readdir ($objDirectory)) {
				if ($file != "." && $file != "..") {
					//check and create thumbs
					$thumb = ASCMS_DOCUMENT_ROOT.$this->arrSettings['thumbnailPath'].'/tn_'.$this->date.'_'.$file;
					
					if(!file_exists($thumb)){
						if (!$chmoded) {
							$objFile->setChmod(ASCMS_DOCUMENT_ROOT.$this->arrSettings['archivePath'], ASCMS_PATH_OFFSET, $this->arrSettings['thumbnailPath']);
							$chmoded = true;
						}
						
						//create thumb
						$im1 = @imagecreatefromjpeg($path.$file); //erstellt ein Abbild im Speicher
						if ($im1) {  /* Pr�fen, ob fehlgeschlagen */
					        // check_jpeg($thumb, $fix=false );    			
							$size = getimagesize($path.$file); //ermittelt die Gr��e des Bildes 						
							$breite = $size[0]; //die Breite des Bildes 
							$hoehe = $size[1]; //die H�he des Bildes 
							$breite_neu = $size[0]/5; //die breite des Thumbnails 
							$hoehe_neu = $size[1]/5; //die H�he des Thumbnails 
							
							//$im2=imagecreate($breite_neu,$hoehe_neu); //Thumbnail im Speicher erstellen 
							$im2 = @imagecreatetruecolor($breite_neu,$hoehe_neu);
							
							imagecopyresized($im2, $im1, 0,0, 0,0,$breite_neu,$hoehe_neu, $breite,$hoehe);  
							imagejpeg($im2, $thumb); //Thumbnail speichern 
							
							imagedestroy($im1); //Speicherabbild wieder l�schen 
							imagedestroy($im2); //Speicherabbild wieder l�schen
						}
					}
							
					//show pictures
					$hour = substr($file,4,2);
					$min = "";
					$time = $hour.$min."&nbsp;Uhr";
					
					$arrThumbnail = array(
						'link_url'	=> '?section=livecam&amp;file='.$this->date.'/'.$file,
						'image_url'	=> $this->arrSettings['thumbnailPath']."/tn_".$this->date."_".$file,
						'time'		=> $time
					);	
					array_push($this->_arrArchiveThumbs, $arrThumbnail);
				}
			}
			closedir($objDirectory);
		}
	}
	
	/**
	* Get javascript
	*
	* Get the javascript code used by the calender
	*
	* @access private
	* @return string $javascript
	*/
	function _getJavaScript()
	{
		$strJavascript = '<script src="modules/livecam/datepicker/datepickercontrol.js" type="text/javascript"></script>
          	<link href="modules/livecam/datepicker/datepickercontrol.css" rel="stylesheet" type="text/css" />';
		return $strJavascript;
	}
}
?>
