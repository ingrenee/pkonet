<?php  
defined('_JEXEC') or die;
require_once(dirname(__FILE__).DS.'parameters.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $this->language; ?>" lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>




  <?php if ($this->params->get('jqueryCDN') == 'yes'): ?>
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/<?php echo $this->params->get('jqueryVersion'); ?>/jquery.min.js"></script>
  <script type="text/javascript">jQuery.noConflict();</script>
  <?php endif;?>  
  <?php if ($this->params->get('jqueryUICDN') == 'yes'): ?>
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/<?php echo $this->params->get('jqueryUIVersion'); ?>/jquery-ui.min.js"></script>
  <?php endif;?>
  <jdoc:include type="head" />
  <link href='http://fonts.googleapis.com/css?family=Open+Sans&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
  <link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/system/css/system.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/system/css/general.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/960.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/template.css" type="text/css" />
  <?php if ($style != 'gray'): ?>
  <link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/styles/<?php echo $style; ?>.css" type="text/css" />
  <?php endif;?>
  <?php if ($this->params->get('jqueryUICDN') == 'yes'): ?>
  <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/<?php echo $this->params->get('jqueryUIVersion'); ?>/themes/<?php echo $this->params->get('jqueryUITheme'); ?>/jquery-ui.css" type="text/css" />
  <?php endif;?>
  <?php if ($this->direction == 'rtl'): ?>
  <link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/960_rtl.css" type="text/css" />
  <link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/template_rtl.css" type="text/css" />
  <?php endif;?>  
  <link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/custom.css" type="text/css" />
  <?php if ($this->params->get('googleAnalytics') == 'yes'): ?>
  
    <script type="text/javascript">

    var _gaq = _gaq || [];
    _gaq.push(['_setAccount', '<?php echo $this->params->get('googleAnalyticsID'); ?>']);
    _gaq.push(['_trackPageview']);

    (function() {
      var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
      ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
      var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
    })();
    
    
</script>
	<?php endif;?>
  
  
  
</head>
<?php
$app = JFactory::getApplication();
$menu = $app->getMenu();
$is_home=($menu->getActive() == $menu->getDefault())?true:false;

if ($is_home) { ?>
<body id="HomePage">

<?php } ?>
    
<?php if (!$is_home) { ?>
<body>
<?php } ?>
 
  <div id="headerWrapper">
    <div id="header" class="clearfix">
      <div id="logo">
        <a href="<?php echo $this->baseurl ?>">
        <?php if ($logo): ?>
        <img src="<?php echo $this->baseurl ?>/<?php echo htmlspecialchars($logo); ?>"  alt="<?php echo $app->getCfg('sitename'); ?>" />
        <?php else: ?>
          <h1><?php echo $app->getCfg('sitename'); ?></h1>
        <?php endif;?>
        </a>        
      </div>
      <?php if ($this->countModules('position-0')): ?>
      <div id="banner" class="position-0">
        <jdoc:include type="modules" name="position-0" />
      </div>
      <?php endif; ?>
      <?php if ($this->countModules('position-1')): ?>
      <div id="nav" class="position-1">
        <jdoc:include type="modules" name="position-1" />
      </div>
      <?php endif; ?>
      <?php if ($this->countModules('position-2')): ?>
      <div id="breadcrumbs" class="position-2">
        <jdoc:include type="modules" name="position-2" />
      </div>
      <?php endif; ?>
      <?php if ($this->params->get('twitterIcon') == 'yes' || $this->params->get('facebookIcon') == 'yes' || $this->params->get('rssIcon') == 'yes'): ?>
      <div id="socialIcons">
        <?php if ($this->params->get('twitterIcon') == 'yes'): ?>
          <a title="<?php echo $this->params->get('twitterTitle'); ?>" href="<?php echo $this->params->get('twitterURL'); ?>"><img src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/images/twitter.png" width="16" height="16" alt="Twitter"></a>
        <?php endif;?>
        <?php if ($this->params->get('facebookIcon') == 'yes'): ?>
          <a title="<?php echo $this->params->get('facebookTitle'); ?>" href="<?php echo $this->params->get('facebookURL'); ?>"><img src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/images/facebook.png" width="16" height="16" alt="Facebook"></a>
        <?php endif;?>
        <?php if ($this->params->get('rssIcon') == 'yes'): ?>
          <a title="<?php echo $this->params->get('rssTitle'); ?>" href="<?php echo $this->params->get('rssURL'); ?>"><img src="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/images/rss.png" width="16" height="16" alt="Rss"></a>
        <?php endif;?>
      </div>  
      <?php endif;?>      
    </div>
  </div>

    <!--  No mostrar en home
  <?php if ($menu->getActive() == $menu->getDefault()) { ?>
  <div id="Logo"><img src="http://www.sicotic.com/images/logo.png" alt="SICOTIC - ¡Sea visto!" width="299" height="92" /><br />¡Sea visto!</div>
  		<?php if ($this->countModules('position-0')): ?>
      <div id="LoginForm">
        <jdoc:include type="modules" name="position-0" />
      </div>
      <?php endif; ?>
  <?php } ?>
-->
  <div id="contentWrapper">
    <div id="main" class="container_<?php echo $grid; ?> clearfix">
    
        
        <?php if ($is_home && $this->countModules('position-home')): ?>
      <div id="slider-home" class="grid_<?php //echo $grid; ?>" class="position-home">        
        <jdoc:include type="modules" name="position-home" style="xhtml" />
      </div>
      <?php endif; ?>
        
        
      <?php if ($this->countModules('position-3') || $this->countModules('position-4')): ?>
      <div id="top" class="grid_<?php echo $grid; ?>" class="position-3-4">        
        <jdoc:include type="modules" name="position-3" style="xhtml" />
        <jdoc:include type="modules" name="position-4" style="xhtml" />
      </div>
      <?php endif; ?>
      
      <div id="content" class="<?php echo $content_position; ?>">
        <jdoc:include type="modules" name="position-12" style="xhtml" />
        <div id="contentInner">
          <?php if ($this->getBuffer('message')) : ?>
                  <jdoc:include type="message" />
          <?php endif; ?>
          <jdoc:include type="component" />
        </div>        
        <jdoc:include type="modules" name="position-13" style="xhtml" />
      </div>
  
      <?php if ($show_sidebar): ?>
      <div id="sidebar" class="<?php echo $sidebar_position; ?>" class="position-5-6-7">
        <jdoc:include type="modules" name="position-5" style="xhtml" />
        <jdoc:include type="modules" name="position-6" style="xhtml" />
        <jdoc:include type="modules" name="position-7" style="xhtml" />
      </div>
      <?php endif; ?>
      
      <?php if ($this->countModules('position-8') || $this->countModules('position-9') || $this->countModules('position-10') || $this->countModules('position-11') || $this->countModules('position-12') || $this->countModules('position-14')): ?>  
      <div id="bottom" class="container_12" class="position-8-9-10-11-12">
        <?php if ($this->countModules('position-8')): ?>
        <div class="grid_12" class="position-8">
          <jdoc:include type="modules" name="position-8" style="xhtml" />
        </div>
        <?php endif; ?>
        <?php if ($this->countModules('position-9') || $this->countModules('position-10') || $this->countModules('position-11')): ?>
        <div class="grid_4" class="position-9">
          <jdoc:include type="modules" name="position-9" style="xhtml" />
        </div>
        <div class="grid_4" class="position-9">
          <jdoc:include type="modules" name="position-10" style="xhtml" />
        </div>
        <div class="grid_4" class="position-9">
          <jdoc:include type="modules" name="position-11" style="xhtml" />
        </div>
        <?php endif; ?>
        <?php if ($this->countModules('position-14')): ?>
        <div class="grid_12" class="position-14">
          <jdoc:include type="modules" name="position-14" style="xhtml" />
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div id="footerWrapper">
    <div id="footer" class="clearfix">
    
    <div class="footer-left">

      <?php if ($this->countModules('position-16')): ?>
      <div id="footerBottom" class="position-16">
        <jdoc:include type="modules" name="position-16" style="xhtml" />
      </div>
      <?php endif; ?>

    </div>
    
    <div class="footer-right">


      <?php if ($this->countModules('position-15')): ?>
      <div id="footermenu" class="position-15">
        <jdoc:include type="modules" name="position-15" style="xhtml" />
      </div>
      <?php endif; ?>

      <div id="copyright"><?php echo $copyright; ?></div>    </div>
    


    </div>
  </div>
  
<jdoc:include type="modules" name="debug" />

</body>
</html>