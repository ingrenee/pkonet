<?php

$app = JFactory::getApplication();

$itemid = JRequest::getVar('Itemid');
$menu = $app->getMenu();
$active = $menu->getItem($itemid);

if(isset($active)) {
	$params = $menu->getParams( $active->id );
	$pageclass = $params->get('pageclass_sfx');
} else {
	$pageclass = '';
}

/* Layout */

// Responsive Design
$responsiveLayout = $this->params->get('responsiveLayout');
$maximumWidth = $this->params->get('maximumWidth');
$responsiveIE = $this->params->get('responsiveIE');

// Mobile Menu
$convertMenu = $this->params->get('convertMenu');
$convertMenuWidth = $this->params->get('convertMenuWidth');
$mobileMenu = $this->params->get('mobileMenu');
$mobileMenus = explode(',',$mobileMenu);
$mobileMenuTitle = $this->params->get('mobileMenuTitle');

// Sidebars
$sidebarsFixed = $this->params->get('sidebarsFixed');

// Primary Sidebar
$primarySidebarPosition = $this->params->get('primarySidebarPosition');
$primarySidebarColumns = $this->params->get('primarySidebarColumns');
$primarySidebarFixedWidth = $this->params->get('primarySidebarFixedWidth');
$primarySidebarTablets = 'show'; //$this->params->get('primarySidebarTablets');
$primarySidebarSmartphones = $this->params->get('primarySidebarSmartphones');
$showPrimarySidebar = ($this->countModules('position-5') || $this->countModules('position-6') || $this->countModules('position-7')) ? '1' : '0';

// Secondary Sidebar
$secondarySidebarPosition = $this->params->get('secondarySidebarPosition');
$secondarySidebarColumns = $this->params->get('secondarySidebarColumns');
$secondarySidebarFixedWidth = $this->params->get('secondarySidebarFixedWidth');
$secondarySidebarTablets = $this->params->get('secondarySidebarTablets');
$secondarySidebarSmartphones = $this->params->get('secondarySidebarSmartphones');
$showSecondarySidebar = ($this->countModules('position-5b') || $this->countModules('position-6b') || $this->countModules('position-7b')) ? '1' : '0';


/* Styles */

// Site Color Theme
$colorTheme = $this->params->get('colorTheme');

// Header Wrapper
$headerBg = $this->params->get('headerBg');
$headerFixed = $this->params->get('headerFixed');

// Top Showcase Wrapper
$showcaseTopBg = $this->params->get('showcaseTopBg');

// Bottom Showcase Wrapper
$showcaseBottomBg = $this->params->get('showcaseBottomBg');

// Footer Wrapper
$footerBg = $this->params->get('footerBg');


/* General */

// Logo
$showLogo = $this->params->get('showLogo');
$logoColumns = $this->params->get('logoColumns');
$logo = $this->params->get('logo');
if (!$showLogo) {
	$position0Columns = 12;
} else {
	$position0Columns = 12 - $logoColumns;
}

// Copyright
$showCopyright = $this->params->get('showCopyright');
$copyrightColumns = $this->params->get('copyrightColumns');
$copyright = $this->params->get('copyright');
if (!$showCopyright) {
	$position15Columns = 12;
} else {
	$position15Columns = 12 - $copyrightColumns;
}

// Web Fonts
$googleFonts = $this->params->get('googleFonts');
$bodyFont = $this->params->get('bodyFont');
$headingsFont = $this->params->get('headingsFont');
$logoFont = $this->params->get('logoFont');

$loadGoogleFonts = array($bodyFont, $headingsFont);

if ($showLogo && !$logo) {
	$loadGoogleFonts[] = $logoFont;
}

$loadGoogleFonts = array_unique($loadGoogleFonts);
$loadGoogleFonts = implode('|',$loadGoogleFonts);


// Google Analytics
$googleAnalytics = $this->params->get('googleAnalytics');
$googleAnalyticsID = $this->params->get('googleAnalyticsID');


/* Social Media */

$showSocialMediaIcons = ($this->params->get('twitterIcon') || $this->params->get('facebookIcon') || $this->params->get('googlePlusIcon') || $this->params->get('rssIcon')) ? '1' : '0';



// Layout classes

$mainContentOffsetClass = '';
$primarySidebarOffsetClass = '';
$componentAreaOffsetClass = '';
$secondarySidebarOffsetClass = '';


// Set main content area and primary sidebar classes

if ($showPrimarySidebar) {

	$mainContentColumns = 12 - $primarySidebarColumns;
	$mainContentColumnClass = ' col'.$mainContentColumns;
	$primarySidebarColumnClass = ' col'.$primarySidebarColumns;

	if ($primarySidebarPosition == 'left') {

		$mainContentOffsetClass = ' push'.$primarySidebarColumns;
		$primarySidebarOffsetClass = ' pull'.$mainContentColumns;

	}

	if ($sidebarsFixed) {

		$mainContentColumnClass = ' col-mc-fixed';
		$primarySidebarColumnClass = ' col-ps-fixed';

		if ($primarySidebarPosition == 'left') {

			$mainContentOffsetClass = ' push-mc-fixed';
			$primarySidebarOffsetClass = ' pull-ps-fixed';

		}
	}

} else {

	$mainContentColumns = 12;
	$mainContentColumnClass = ' col'.$mainContentColumns;
	$primarySidebarColumnClass = '';

}

// Set component area and secondary sidebar classes

if ($showSecondarySidebar) {

	$componentAreaColumns = $mainContentColumns - $secondarySidebarColumns;
	$componentAreaColumnClass = ' col'.$componentAreaColumns;
	$secondarySidebarColumnClass = ' col'.$secondarySidebarColumns;

	if ($secondarySidebarPosition == 'left') {

		$componentAreaOffsetClass = ' push'.$secondarySidebarColumns;
		$secondarySidebarOffsetClass = ' pull'.$componentAreaColumns;

	}

	if ($sidebarsFixed) {

		$componentAreaColumnClass = ' col-ca-fixed';
		$secondarySidebarColumnClass = ' col-ss-fixed';

		if ($secondarySidebarPosition == 'left') {

			$componentAreaOffsetClass = ' push-ca-fixed';
			$secondarySidebarOffsetClass = ' pull-ss-fixed';

		}
	}

} else {

	$componentAreaColumns = $mainContentColumns;
	$componentAreaColumnClass = ' col'.$componentAreaColumns;
	$secondarySidebarColumnClass = '';

	if ($sidebarsFixed) {

		$componentAreaColumnClass = ' col-mc-fixed';

	}

}

// Set grid parameters

$gridParams = "?rwd={$responsiveLayout}&mw={$maximumWidth}&sf={$sidebarsFixed}&ps={$showPrimarySidebar}&psw={$primarySidebarFixedWidth}&pst={$primarySidebarTablets}&pss={$primarySidebarSmartphones}&ssw={$secondarySidebarFixedWidth}&sst={$secondarySidebarTablets}&sss={$secondarySidebarSmartphones}";


?>