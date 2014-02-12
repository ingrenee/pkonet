<?php
 /**
  * iReview2 - Joomla template
  * Copyright (C) 2010-2011 ClickFWD LLC
  * This is not free software, do not distribute it.
  * For licencing information visit http://www.reviewsforjoomla.com
  * or contact sales@reviewsforjoomla.com
 **/

defined('_JEXEC') or die;

require dirname(__FILE__).'/parameters.php';
 
?>

<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
<!--  R online -->
<?php if ($this->params->get('jqueryCDN')): ?>
<!--	
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/<?php echo $this->params->get('jqueryVersion'); ?>/jquery.min.js"></script>
    -->
<script type="text/javascript" 
        src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/js/jquery-1.10.2.min.js"></script>
<script type="text/javascript">jQuery.noConflict();</script>
<?php endif;?>
<?php if ($this->params->get('jqueryUICDN')): ?>
<script type="text/javascript" src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/js/jquery-ui.min.js"></script>
<?php endif;?>
<jdoc:include type="head" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<!--[if lt IE 9]><script src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/js/html5shiv-printshiv.js"></script><![endif]-->

<?php if ($responsiveLayout): ?>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<?php endif;?>
<link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/grids.php<?php echo $gridParams;?>" />
<link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/template.css" type="text/css" />
<link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/styles/<?php echo $colorTheme; ?>.css" type="text/css" />
<?php if ($this->direction == 'rtl'): ?>
<link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/template_rtl.css" type="text/css" />
<?php endif;?>
<?php if($googleFonts): ?>
<link href='//fonts.googleapis.com/css?family=<?php echo $loadGoogleFonts; ?>' rel='stylesheet' type='text/css'>
<?php endif;?>
<?php if ($this->params->get('jqueryUICDN')): ?>
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/<?php echo $this->params->get('jqueryUIVersion'); ?>/themes/<?php echo $this->params->get('jqueryUITheme'); ?>/jquery-ui.css" type="text/css" />
<?php endif;?>
<?PHP
	
	$tmp=explode(':',$bodyFont);
	$bodyFont=$tmp[0];
	
	$tmp=explode(':',$headingsFont);
	$headingsFont=$tmp[0];
	
	$tmp=explode(':',$logoFont);
	$logoFont=$tmp[0];
	
	?>
<style type="text/css">
body {
	font-family: '<?php echo str_replace('+', ' ', $bodyFont);?>', sans-serif;
}
h1, h2, h3, h4, h5, h6 {
	font-family: '<?php echo str_replace('+', ' ', $headingsFont);?>', sans-serif;
}
 <?php if ($showLogo && !$logo):;
?> a.logo {
 font-family: '<?php echo str_replace('+', ' ', $logoFont);?>', sans-serif;
}
 <?php endif;
?>
</style>
<link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/custom.css" type="text/css" />
<?php if ($responsiveLayout && $responsiveIE): ?>
<!--[if lt IE 9]><script src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/js/respond.min.js"></script><![endif]-->
<?php endif;?>
</head>

<body class="<?php echo $pageclass ? htmlspecialchars($pageclass) : 'default'; ?><?php echo $this->direction == 'rtl' ? ' rtl' : ''?>">
<div class="header-wrapper <?php echo $headerBg . '-bg'; ?>">
  <header class="container"> 
    <!-- header top -->
    <?php if ($this->countModules('header-top')): ?>
    <div class="row">
      <div class="header-top col12">
        <div class="<?php echo ($this->countModules('header-top') > 1) ? 'row' : 'row-single'; ?>">
          <jdoc:include type="modules" name="header-top" style="raw" />
        </div>
      </div>
    </div>
    <?php endif; ?>
    <!-- Fin header top --> 
    
    <!-- show logo && position-0 -->
    <?php if ($showLogo || ($this->countModules('position-0')) && $position0Columns != 0): ?>
    <div class="row">
      <?php if($showLogo): ?>
      <div class="logo-container col<?php echo $logoColumns; ?><?php echo $this->direction == 'rtl' ? ' push'.$position0Columns : ''?>">
        <div class="logo-inner"> <a class="logo" href="<?php echo $this->baseurl ?>">
          <?php if ($logo): ?>
          <img src="<?php echo $this->baseurl ?>/<?php echo htmlspecialchars($logo); ?>"  alt="<?php echo $app->getCfg('sitename'); ?>" />
          <?php else: ?>
          <?php echo $app->getCfg('sitename'); ?>
          <?php endif;?>
          </a> </div>
      </div>
      <?php endif; ?>
      <?php if ($this->countModules('position-0') && $position0Columns != 0): ?>
      <div class="position-0 col<?php echo $position0Columns; ?><?php echo $this->direction == 'rtl' ? ' pull'.$logoColumns : ''?>">
        <jdoc:include type="modules" name="position-0" style="raw" />
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <!-- FIN  show logo && position-0 --> 
    
    <!--  position-1 -->
    <?php if ($this->countModules('position-1')): ?>
    <div class="row">
      <div class="position-1 col12">
        <jdoc:include type="modules" name="position-1" style="raw" />
      </div>
    </div>
    <?php endif; ?>
    <!-- FIN  position-1 --> 
    
    <!--  position-2 -->
    <?php if ($this->countModules('position-2')): ?>
    <div class="row">
      <div class="position-2 col12">
        <jdoc:include type="modules" name="position-2" style="raw" />
      </div>
    </div>
    <?php endif; ?>
    <!-- fin   position-2 --> 
    
    <!--  position-3  && position -4 -->
    <?php if ($this->countModules('position-3') || $this->countModules('position-4')): ?>
    <div class="row">
      <div class="position-3 col6">
        <?php if ($this->countModules('position-3')): ?>
        <jdoc:include type="modules" name="position-3" style="raw" />
        <?php endif; ?>
      </div>
      <div class="position-4 col6">
        <?php if ($this->countModules('position-4')): ?>
        <jdoc:include type="modules" name="position-4" style="raw" />
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    
    <!--   fin position-3  && position -4 -->
    
    <?php if ($this->countModules('header-bottom')): ?>
    <div class="row">
      <div class="header-bottom col12">
        <div class="<?php echo ($this->countModules('header-bottom') > 1) ? 'row' : 'row-single'; ?>">
          <jdoc:include type="modules" name="header-bottom" style="raw" />
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($showSocialMediaIcons): ?>
    <div class="social-icons-container">
      <?php if ($this->params->get('twitterIcon')): ?>
      <a class="twitter-link" title="<?php echo $this->params->get('twitterTitle'); ?>" href="<?php echo $this->params->get('twitterURL'); ?>"<?php echo ($this->params->get('socialNewWindow')) ? ' target="_blank"' : ''; ?>>Twitter</a>
      <?php endif;?>
      <?php if ($this->params->get('facebookIcon')): ?>
      <a class="facebook-link" title="<?php echo $this->params->get('facebookTitle'); ?>" href="<?php echo $this->params->get('facebookURL'); ?>"<?php echo ($this->params->get('socialNewWindow')) ? ' target="_blank"' : ''; ?>>Facebook</a>
      <?php endif;?>
      <?php if ($this->params->get('googlePlusIcon')): ?>
      <a class="gplus-link" title="<?php echo $this->params->get('googlePlusTitle'); ?>" href="<?php echo $this->params->get('googlePlusURL'); ?>"<?php echo ($this->params->get('socialNewWindow')) ? ' target="_blank"' : ''; ?>>Google+</a>
      <?php endif;?>
      <?php if ($this->params->get('rssIcon')): ?>
      <a class="rss-link" title="<?php echo $this->params->get('rssTitle'); ?>" href="<?php echo $this->params->get('rssURL'); ?>"<?php echo ($this->params->get('socialNewWindow')) ? ' target="_blank"' : ''; ?>>RSS</a>
      <?php endif;?>
    </div>
    <?php endif; ?>
  </header>
</div>

<!--  position NAV -->
<?php if ($this->countModules('position-nav')): ?>
<div class="nav">
  <div class="container">
    <div class="row">
      <div class="col-12">
        <jdoc:include type="modules" name="position-nav" style="raw" />
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<!-- FIN  position NAV --> 

<!--   showcase-top -->
<?php if ($this->countModules('showcase-top')): ?>
<div class="showcase-top-wrapper <?php echo $showcaseTopBg . '-bg'; ?>">
  <section class="showcase container">
    <div class="row">
      <div class="showcase-top col12">
        <div class="<?php echo ($this->countModules('showcase-top') > 1) ? 'row' : 'row-single'; ?>">
          <jdoc:include type="modules" name="showcase-top" style="block" />
        </div>
      </div>
    </div>
  </section>
</div>
<?php endif; ?>
<!--  fin  showcase-top -->

<div class="content-wrapper">
  <section class="top container"> 
    
    <!-- content-top -->
    <?php if ($this->countModules('content-top')): ?>
    <div class="row">
      <div class="content-top col12">
        <div class="<?php echo ($this->countModules('content-top') > 1) ? 'row' : 'row-single'; ?>">
          <jdoc:include type="modules" name="content-top" style="block" />
        </div>
      </div>
    </div>
    <?php endif; ?>
    
    <!-- fin content-top --> 
    
    <!-- position-17 o 18 -->
    <?php if ($this->countModules('position-17') || $this->countModules('position-18')): ?>
    <div class="row">
      <div class="position-17 col6">
        <?php if ($this->countModules('position-17')): ?>
        <jdoc:include type="modules" name="position-17" style="block" />
        <?php endif; ?>
      </div>
      <div class="position-18 col6">
        <?php if ($this->countModules('position-18')): ?>
        <jdoc:include type="modules" name="position-18" style="block" />
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <!-- fin  position-17 o 18 --> 
    
  </section>
  <section class="main container">
    <div class="row">
      <div class="main-content<?php echo $mainContentColumnClass.$mainContentOffsetClass; ?>"> 
        
        <!-- position-12 -->
        <?php if ($this->countModules('position-12')): ?>
        <div class="row">
          <div class="position-12<?php echo $mainContentColumnClass; ?>">
            <jdoc:include type="modules" name="position-12" style="block" />
          </div>
        </div>
        <?php endif; ?>
        <!-- fin position-12 -->
        
        <div class="row">
          <div class="component-area<?php echo $componentAreaColumnClass.$componentAreaOffsetClass; ?>"> 
            
            <!-- component-top -->
            <?php if ($this->countModules('component-top')): ?>
            <div class="component-top">
              <jdoc:include type="modules" name="component-top" style="block" />
            </div>
            <?php endif; ?>
            <!-- fin component-top --> 
            
            <!-- content-inner -->
            <div class="content-inner">
              <?php if ($this->getBuffer('message')) : ?>
              <jdoc:include type="message" />
              <?php endif; ?>
              <jdoc:include type="component" />
            </div>
            
            <!--  fin content-inner --> 
            
            <!-- component-bottom --->
            <?php if ($this->countModules('component-bottom')): ?>
            <div class="component-bottom">
              <jdoc:include type="modules" name="component-bottom" style="block" />
            </div>
            <?php endif; ?>
            <!-- fin component-bottom ---> 
            
          </div>
          <?php if ($showSecondarySidebar): ?>
          <div class="sidebar-secondary<?php echo $secondarySidebarColumnClass.$secondarySidebarOffsetClass; ?>">
            <?php if ($this->countModules('position-5b')): ?>
            <div class="row">
              <div class="position-5b<?php echo $secondarySidebarColumnClass; ?>">
                <jdoc:include type="modules" name="position-5b" style="block" />
              </div>
            </div>
            <?php endif; ?>
            <?php if ($this->countModules('position-6b')): ?>
            <div class="row">
              <div class="position-6b<?php echo $secondarySidebarColumnClass; ?>">
                <jdoc:include type="modules" name="position-6b" style="block" />
              </div>
            </div>
            <?php endif; ?>
            <?php if ($this->countModules('position-7b')): ?>
            <div class="row">
              <div class="position-7b<?php echo $secondarySidebarColumnClass; ?>">
                <jdoc:include type="modules" name="position-7b" style="block" />
              </div>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <?php if ($this->countModules('position-13')): ?>
        <div class="row">
          <div class="position-13<?php echo $mainContentColumnClass; ?>">
            <jdoc:include type="modules" name="position-13" style="block" />
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php if ($showPrimarySidebar): ?>
      <div class="sidebar-primary<?php echo $primarySidebarColumnClass.$primarySidebarOffsetClass; ?>">
        <?php if ($this->countModules('position-5')): ?>
        <div class="row">
          <div class="position-5<?php echo $primarySidebarColumnClass; ?>">
            <jdoc:include type="modules" name="position-5" style="block" />
          </div>
        </div>
        <?php endif; ?>
        <?php if ($this->countModules('position-6')): ?>
        <div class="row">
          <div class="position-6<?php echo $primarySidebarColumnClass; ?>">
            <jdoc:include type="modules" name="position-6" style="block" />
          </div>
        </div>
        <?php endif; ?>
        <?php if ($this->countModules('position-7')): ?>
        <div class="row">
          <div class="position-7<?php echo $primarySidebarColumnClass; ?>">
            <jdoc:include type="modules" name="position-7" style="block" />
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </section>
  <section class="bottom container">
    <?php if ($this->countModules('position-8')): ?>
    <div class="row">
      <div class="position-8 col12">
        <jdoc:include type="modules" name="position-8" style="block" />
      </div>
    </div>
    <?php endif; ?>
    <?php if ($this->countModules('position-9') || $this->countModules('position-10') || $this->countModules('position-11')): ?>
    <div class="row">
      <div class="position-9 col4">
        <?php if ($this->countModules('position-9')): ?>
        <jdoc:include type="modules" name="position-9" style="block" />
        <?php endif; ?>
      </div>
      <div class="position-10 col4">
        <?php if ($this->countModules('position-10')): ?>
        <jdoc:include type="modules" name="position-10" style="block" />
        <?php endif; ?>
      </div>
      <div class="position-11 col4">
        <?php if ($this->countModules('position-11')): ?>
        <jdoc:include type="modules" name="position-11" style="block" />
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($this->countModules('position-19') || $this->countModules('position-20')): ?>
    <div class="row">
      <div class="position-19 col6">
        <?php if ($this->countModules('position-19')): ?>
        <jdoc:include type="modules" name="position-19" style="block" />
        <?php endif; ?>
      </div>
      <div class="position-20 col6">
        <?php if ($this->countModules('position-20')): ?>
        <jdoc:include type="modules" name="position-20" style="block" />
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($this->countModules('position-21') || $this->countModules('position-22') || $this->countModules('position-23') || $this->countModules('position-24')): ?>
    <div class="row">
      <div class="position-21 col3">
        <?php if ($this->countModules('position-21')): ?>
        <jdoc:include type="modules" name="position-21" style="block" />
        <?php endif; ?>
      </div>
      <div class="position-22 col3">
        <?php if ($this->countModules('position-22')): ?>
        <jdoc:include type="modules" name="position-22" style="block" />
        <?php endif; ?>
      </div>
      <div class="position-23 col3">
        <?php if ($this->countModules('position-23')): ?>
        <jdoc:include type="modules" name="position-23" style="block" />
        <?php endif; ?>
      </div>
      <div class="position-24 col3">
        <?php if ($this->countModules('position-24')): ?>
        <jdoc:include type="modules" name="position-24" style="block" />
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($this->countModules('content-bottom')): ?>
    <div class="row">
      <div class="content-bottom col12">
        <div class="<?php echo ($this->countModules('content-bottom') > 1) ? 'row' : 'row-single'; ?>">
          <jdoc:include type="modules" name="content-bottom" style="block" />
        </div>
      </div>
    </div>
    <?php endif; ?>
  </section>
</div>
<?php if ($this->countModules('showcase-bottom')): ?>
<div class="showcase-bottom-wrapper <?php echo $showcaseBottomBg . '-bg'; ?>">
  <section class="showcase container">
    <div class="row">
      <div class="showcase-bottom col12">
        <div class="<?php echo ($this->countModules('showcase-bottom') > 1) ? 'row' : 'row-single'; ?>">
          <jdoc:include type="modules" name="showcase-bottom" style="block" />
        </div>
      </div>
    </div>
  </section>
</div>
<?php endif; ?>
<div class="footer-wrapper <?php echo $footerBg . '-bg'; ?>">
  <footer class="container">
    <?php if ($this->countModules('footer-top')): ?>
    <div class="row">
      <div class="footer-top col12">
        <div class="<?php echo ($this->countModules('footer-top') > 1) ? 'row' : 'row-single'; ?>">
          <jdoc:include type="modules" name="footer-top" style="block" />
        </div>
      </div>
    </div>
    <?php endif; ?>
    <?php if ($this->countModules('position-14')): ?>
    <div class="row">
      <div class="position-14 col12">
        <jdoc:include type="modules" name="position-14" style="block" />
      </div>
    </div>
    <?php endif; ?>
    <?php if ($showCopyright || ($this->countModules('position-15') && $position15Columns != 0)): ?>
    <div class="row">
      <?php if($showCopyright): ?>
      <div class="copyright col<?php echo $copyrightColumns; ?><?php echo $this->direction == 'rtl' ? ' push'.$position15Columns : ''?>">
        <div class="copyright-inner"><?php echo $copyright; ?></div>
      </div>
      <?php endif; ?>
      <?php if ($this->countModules('position-15') && $position15Columns != 0): ?>
      <div class="position-15 col<?php echo $position15Columns; ?><?php echo $this->direction == 'rtl' ? ' pull'.$copyrightColumns : ''?>">
        <jdoc:include type="modules" name="position-15" style="block" />
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php if ($this->countModules('position-16')): ?>
    <div class="row">
      <div class="position-16 col12">
        <jdoc:include type="modules" name="position-16" style="block" />
      </div>
    </div>
    <?php endif; ?>
    <?php if ($this->countModules('footer-bottom')): ?>
    <div class="row">
      <div class="footer-bottom col12">
        <div class="<?php echo ($this->countModules('footer-bottom') > 1) ? 'row' : 'row-single'; ?>">
          <jdoc:include type="modules" name="footer-bottom" style="block" />
        </div>
      </div>
    </div>
    <?php endif; ?>
  </footer>
</div>
<script type="text/javascript" src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/js/template-ck.js"></script>
<?php if($convertMenu || $headerFixed): ?>
<script>
(function($) {
	$(document).ready(function() {
		<?php if($headerFixed): ?>
		var headerWrapper = $('.header-wrapper').addClass('fixed');
		var headerWrapperHeight = headerWrapper.outerHeight();
		$('body').addClass('header-fixed').css("paddingTop", headerWrapperHeight);
		<?php endif; ?>
		<?php if($convertMenu): ?>
			<?php foreach($mobileMenus as $menu):  ?>
			$(".mobile-menu-toggle.<?php echo $menu; ?>").mMenu(<?php echo $convertMenuWidth; ?>, '<?php echo $mobileMenuTitle; ?>');
			<?php endforeach; ?>
		<?php endif; ?>
	});
})(jQuery);
</script>
<?php endif;?>
<?php if ($googleAnalytics && $googleAnalyticsID != ''): ?>
<script>
    var _gaq=[['_setAccount','<?php echo $googleAnalyticsID; ?>'],['_trackPageview']];
    (function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
    g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
    s.parentNode.insertBefore(g,s)}(document,'script'));
</script>
<?php endif;?>
<jdoc:include type="modules" name="debug" />
</body>
</html>