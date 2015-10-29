<?php
/**
* @author Ryan McLaughlin (www.daobydesign.com, info@daobydesign.com)
* This plugin will automatically generate Meta Description tags from your content.
* version 2.0
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Import library dependencies
jimport('joomla.event.plugin');

class plgSystemSEOSimple extends JPlugin {
  
	// Constructor
    function plgSystemSEOSimple(&$subject, $params) {
		parent::__construct( $subject, $params );
    }

    function onAfterDispatch() {
		global $mainframe, $thebuffer;
		$document =& JFactory::getDocument();
        $docType = $document->getType();

    	// only mod site pages that are html docs (no admin, install, etc.)
      	if (!$mainframe->isSite()) return ;
    	if ($docType != 'html') return ;
		
		// load plugin parameters
		$plugin =& JPluginHelper::getPlugin('system', 'SEOSimple');
		$pluginParams = new JParameter( $plugin->params );

		// Check to see if this is the front page and if this feature is disabled
		$fptitorder = $pluginParams->def('fptitorder', 0);
		if ($this->isFrontPage() && $fptitorder == 0) return;

		// Check to see if this is not the front page and if this feature is disabled
		$titOrder = $pluginParams->def('titorder', 0);
		if (!$this->isFrontPage() && $titOrder == 0) return;

		// Alright, we're all good -- time to start changin' stuff.
		$customtitle = html_entity_decode($pluginParams->def('customtitle','Home'));
		$pageTitle = html_entity_decode($document->getTitle());
		$sitename = html_entity_decode($mainframe->getCfg('sitename'));
		$sep = str_replace('\\','',$pluginParams->def('separator','|')); //Sets and removes Joomla escape char bug.
		
		if ($this->isFrontPage()):
			if ($fptitorder == 1):
				$newPageTitle = $customtitle . ' ' . $sep . ' ' . $sitename;
			elseif ($fptitorder == 2):
				$newPageTitle = $sitename . ' ' . $sep . ' ' . $customtitle;
			elseif ($fptitorder == 3):
				$newPageTitle = $customtitle;
			elseif ($fptitorder == 4):
				$newPageTitle = $sitename;
			elseif ($fptitorder == 5):
				$newPageTitle = $sitename . ' ' . $sep . ' ' . $pageTitle;
			elseif ($fptitorder == 6):
				$newPageTitle = $pageTitle . ' ' . $sep . ' ' . $sitename;
			elseif ($fptitorder == 7):
				$newPageTitle = $customtitle . ' ' . $sep . ' ' . $pageTitle;
			elseif ($fptitorder == 8):
				$newPageTitle = $pageTitle . ' ' . $sep . ' ' . $customtitle;

			endif;
		else:
			if ($titOrder == 1):
				$newPageTitle = $pageTitle . ' ' . $sep . ' ' . $sitename;
			elseif ($titOrder == 2):
				$newPageTitle = $sitename . ' ' . $sep . ' ' . $pageTitle;
			endif;
		endif;

		
		// Set the Title
		$document->setTitle ($newPageTitle);

	}

	function onPrepareContent(&$article, &$params, $limitstart) {
		global $mainframe;
		if (!$mainframe->isSite()) return;
		
		$document =& JFactory::getDocument();
		$view = JRequest::getVar('view');
		$plugin =& JPluginHelper::getPlugin('system', 'SEOSimple');
		$pluginParams = new JParameter( $plugin->params );
		$thelength = $pluginParams->def('length', 155);
		$thecontent = $article->text;
		$fpdesc = $pluginParams->def('fpdesc', 0);
		$credit = $pluginParams->def('credittag', 0);
		$catnoindex = $pluginParams->def('catnoindex', 0);

		//Checks to see whether FP should use standard desc or auto-generated one.
		if ($this->isFrontPage() && $fpdesc == 0) {
			$document->setDescription($mainframe->getCfg('MetaDesc'));
			return;
		}
		
		//Bit of code to grab only the first content item in category list.
		if ($document->getDescription() != '') {
			if ($document->getDescription() != $mainframe->getCfg('MetaDesc')) return;
		}
		
		// Clean things up and prepare auto-generated Meta Description tag.
		$thecontent = $this->cleanText($thecontent);

		
		// Truncate the string to the length parameter - rounding to nearest word
		$thecontent = $thecontent . ' ';
		$thecontent = substr($thecontent,0,$thelength);
		$thecontent = substr($thecontent,0,strrpos($thecontent,' '));

		// Set the description
		$document->setDescription($thecontent);

		// Set robots for category pages (beta)
		if ($view == 'category' && $catnoindex == 1) { $document->setMetaData('robots','noindex,follow'); }

		//Set optional Generator tag for SEOSimple credit.
		if ($credit == 0) {
			$regen = $document->getMetaData('generator');
			if (strpos($regen, 'SEOSimple') == 0) { $document->setMetaData('generator', $regen . ' + SEOSimple (http://daobydesign.com)'); }
		}
		
	}

	
	/* cleanText function - Thx owed to eXtplorer, joomSEO, Jean-Marie Simonet and Ivan Tomic */
	function cleanText( $text ) {
		$text = preg_replace( "'<script[^>]*>.*?</script>'si", '', $text );
		$text = preg_replace( '/<!--.+?-->/', '', $text );
		$text = preg_replace( '/{.+?}/', '', $text );

		// convert html entities to chars (with conditional for PHP4 users
		if (version_compare(phpversion(), '5.0') < 0 ) {
			require_once(JPATH_SITE.DS.'libraries'.DS.'tcpdf'.DS.'html_entity_decode_php4.php');
			$text = html_entity_decode_php4($text,ENT_QUOTES,'UTF-8');
		} else {
			$text = html_entity_decode($text,ENT_QUOTES,'UTF-8');
		}

		$text = strip_tags( $text ); // Last check to kill tags
		$text = str_replace('"', '\'', $text); //Make sure all quotes play nice with meta.
        $text = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $text); //Change spaces to spaces

        // remove any extra spaces
		while (strchr($text,"  ")) {
			$text = str_replace("  ", " ",$text);
		}
		
		// general sentence tidyup
		for ($cnt = 1; $cnt < strlen($text); $cnt++) {
			// add a space after any full stops or comma's for readability
			// added as strip_tags was often leaving no spaces
			if ( ($text{$cnt} == '.') || (($text{$cnt} == ',') && !(is_numeric($text{$cnt+1})))) {
				if ($text{strlen($cnt+1)} != ' ') {
					$text = substr_replace($text, ' ', $cnt + 1, 0);
				}
			}
		}
			
		return $text;
	}	

	
	function isFrontPage() {
		$menu = & JSite::getMenu();
		if ($menu->getActive() == $menu->getDefault()) {
			return true;
		}
		return false;
	}

	function killTitleinBuffer ($buff, $tit) {
		$cleanTitle = $buff;
		if (substr($buff, 0, strlen($tit)) == $tit) {
			$cleanTitle = substr($buff, strlen($tit) + 1);
		} 
		return $cleanTitle;
	}
	
	
}