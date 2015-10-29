<?php
/**
* @author Ryan McLaughlin (www.daobydesign.com, info@daobydesign.com)
* This plugin will automatically generate Meta Description tags from your content, as well as provide options for title and noindex manipulation.
* version 2.2
*/

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

// Import library dependencies
jimport('joomla.plugin.plugin');
jimport('joomla.html.parameter');

class plgSystemSEOSimple extends JPlugin {
  
	// Constructor
    function plgSystemSEOSimple(&$subject, $params) {
		parent::__construct( $subject, $params );
    }

    function onAfterDispatch() {
		//global $mainframe, $thebuffer;
		$app = &JFactory::getApplication();
		$document =& JFactory::getDocument();
		$docType = $document->getType();
 
    	// only mod site pages that are html docs (no admin, install, etc.)
      	if (!$app->isSite()) return ;
    	if ($docType != 'html') return ;
		
		// Check to see if this is the front page and if this feature is disabled
		$fptitorder = $this->params->def('fptitorder', 0);
		if ($this->isFrontPage() && $fptitorder == 0) return;

		// Check to see if this is not the front page and if this feature is disabled
		$titOrder = $this->params->def('titorder', 0);
		if (!$this->isFrontPage() && $titOrder == 0) return;

		// Alright, we're all good -- time to start changin' stuff.
		$customtitle = html_entity_decode($this->params->def('customtitle','Home'));
		$pageTitle = html_entity_decode($document->getTitle());
		$sitename = html_entity_decode($app->getCfg('sitename'));
		$sep = $this->params->def('separator','|');
		
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

	function onContentPrepare($context, &$article, &$params, $limitstart) {
		$app = &JFactory::getApplication();
		
		if (!$app->isSite()) return;
		
		$document =& JFactory::getDocument();
		$view = JRequest::getVar('view');
		$thelength = $this->params->def('length', 155);
		$thecontent = $article->text;
		$fpdesc = $this->params->def('fpdesc', 0);
		$catdesc = $this->params->def('catdesc', 0);
		$credit = $this->params->def('credittag', 0);
		$catnoindex = $this->params->def('catnoindex', 0);

		//Checks to see whether FP should use standard desc or auto-generated one.
		if ($this->isFrontPage() && $fpdesc == 0) {
			$document->setDescription($app->getCfg('MetaDesc'));
			return;
		}

		//Bit of code to grab only the first content item in category list.
		if ($document->getDescription() != '') {
			if ($document->getDescription() != $app->getCfg('MetaDesc')) return;
		}

		if ($view == 'category' && $catdesc == 0) { 
			$db1 = &JFactory::getDBO();
			$catid = JRequest::getVar('id');
			$db1->setQuery('SELECT cat.description FROM #__categories cat WHERE cat.id='.$catid);   
      		$catdesc = $db1->loadResult();
			if ($catdesc) { $thecontent = $catdesc; }
		}
				
		// Clean things up and prepare auto-generated Meta Description tag.
		$thecontent = $this->cleanText($thecontent);

		
		// Truncate the string to the length parameter - rounding to nearest word
		$thecontent = $thecontent . ' ';
		$thecontent = substr($thecontent,0,$thelength);
		$thecontent = rtrim(substr($thecontent,0,strrpos($thecontent,' ')), ' ');

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
	function cleanText ($text) {
		$text = preg_replace( "'<script[^>]*>.*?</script>'si", '', $text );
		$text = preg_replace( '/<!--.+?-->/', '', $text );
		$text = preg_replace( '/{.+?}/', '', $text );

		// convert html entities to chars (with conditional for PHP4 users
		$text = html_entity_decode($text,ENT_QUOTES,'UTF-8');

		$text = strip_tags( $text ); // Last check to kill tags
		$text = str_replace('"', '\'', $text); //Make sure all quotes play nice with meta.
        $text = str_replace(array("\r\n", "\r", "\n", "\t"), " ", $text); //Change spaces to spaces

        // remove any extra spaces
		$text = str_replace('  ', ' ',$text);
		
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

	// Updated in 2.2 for improved multi-lingual functionality. Thx Jakub Niezgoda
	function isFrontPage() {
		$app = JFactory::getApplication();
		$menu = $app->getMenu();
		$lang = JFactory::getLanguage();
		if ($menu->getActive() == $menu->getDefault($lang->getTag())) {
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