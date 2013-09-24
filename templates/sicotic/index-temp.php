<?php  
 /**
  * SICOTIC+ template
  * based on iReview
  * Designed by Phillipe Calmet Williams (www.phillipecw.com)
 **/

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
  <?php
  $app = JFactory::getApplication();
  $menu = $app->getMenu();
 $currentMenuName = $app->getMenu()->getActive()->id;
echo $currentMenuName;
  if ($menu->getActive() == $menu->getDefault()) {
  ?>
  <script type="text/javascript">
/* Modernizr 2.6.2 (Custom Build) | MIT & BSD
 * Build: http://modernizr.com/download/#-csstransitions-shiv-cssclasses-testprop-testallprops-domprefixes-load
 */
;window.Modernizr=function(a,b,c){function x(a){j.cssText=a}function y(a,b){return x(prefixes.join(a+";")+(b||""))}function z(a,b){return typeof a===b}function A(a,b){return!!~(""+a).indexOf(b)}function B(a,b){for(var d in a){var e=a[d];if(!A(e,"-")&&j[e]!==c)return b=="pfx"?e:!0}return!1}function C(a,b,d){for(var e in a){var f=b[a[e]];if(f!==c)return d===!1?a[e]:z(f,"function")?f.bind(d||b):f}return!1}function D(a,b,c){var d=a.charAt(0).toUpperCase()+a.slice(1),e=(a+" "+n.join(d+" ")+d).split(" ");return z(b,"string")||z(b,"undefined")?B(e,b):(e=(a+" "+o.join(d+" ")+d).split(" "),C(e,b,c))}var d="2.6.2",e={},f=!0,g=b.documentElement,h="modernizr",i=b.createElement(h),j=i.style,k,l={}.toString,m="Webkit Moz O ms",n=m.split(" "),o=m.toLowerCase().split(" "),p={},q={},r={},s=[],t=s.slice,u,v={}.hasOwnProperty,w;!z(v,"undefined")&&!z(v.call,"undefined")?w=function(a,b){return v.call(a,b)}:w=function(a,b){return b in a&&z(a.constructor.prototype[b],"undefined")},Function.prototype.bind||(Function.prototype.bind=function(b){var c=this;if(typeof c!="function")throw new TypeError;var d=t.call(arguments,1),e=function(){if(this instanceof e){var a=function(){};a.prototype=c.prototype;var f=new a,g=c.apply(f,d.concat(t.call(arguments)));return Object(g)===g?g:f}return c.apply(b,d.concat(t.call(arguments)))};return e}),p.csstransitions=function(){return D("transition")};for(var E in p)w(p,E)&&(u=E.toLowerCase(),e[u]=p[E](),s.push((e[u]?"":"no-")+u));return e.addTest=function(a,b){if(typeof a=="object")for(var d in a)w(a,d)&&e.addTest(d,a[d]);else{a=a.toLowerCase();if(e[a]!==c)return e;b=typeof b=="function"?b():b,typeof f!="undefined"&&f&&(g.className+=" "+(b?"":"no-")+a),e[a]=b}return e},x(""),i=k=null,function(a,b){function k(a,b){var c=a.createElement("p"),d=a.getElementsByTagName("head")[0]||a.documentElement;return c.innerHTML="x<style>"+b+"</style>",d.insertBefore(c.lastChild,d.firstChild)}function l(){var a=r.elements;return typeof a=="string"?a.split(" "):a}function m(a){var b=i[a[g]];return b||(b={},h++,a[g]=h,i[h]=b),b}function n(a,c,f){c||(c=b);if(j)return c.createElement(a);f||(f=m(c));var g;return f.cache[a]?g=f.cache[a].cloneNode():e.test(a)?g=(f.cache[a]=f.createElem(a)).cloneNode():g=f.createElem(a),g.canHaveChildren&&!d.test(a)?f.frag.appendChild(g):g}function o(a,c){a||(a=b);if(j)return a.createDocumentFragment();c=c||m(a);var d=c.frag.cloneNode(),e=0,f=l(),g=f.length;for(;e<g;e++)d.createElement(f[e]);return d}function p(a,b){b.cache||(b.cache={},b.createElem=a.createElement,b.createFrag=a.createDocumentFragment,b.frag=b.createFrag()),a.createElement=function(c){return r.shivMethods?n(c,a,b):b.createElem(c)},a.createDocumentFragment=Function("h,f","return function(){var n=f.cloneNode(),c=n.createElement;h.shivMethods&&("+l().join().replace(/\w+/g,function(a){return b.createElem(a),b.frag.createElement(a),'c("'+a+'")'})+");return n}")(r,b.frag)}function q(a){a||(a=b);var c=m(a);return r.shivCSS&&!f&&!c.hasCSS&&(c.hasCSS=!!k(a,"article,aside,figcaption,figure,footer,header,hgroup,nav,section{display:block}mark{background:#FF0;color:#000}")),j||p(a,c),a}var c=a.html5||{},d=/^<|^(?:button|map|select|textarea|object|iframe|option|optgroup)$/i,e=/^(?:a|b|code|div|fieldset|h1|h2|h3|h4|h5|h6|i|label|li|ol|p|q|span|strong|style|table|tbody|td|th|tr|ul)$/i,f,g="_html5shiv",h=0,i={},j;(function(){try{var a=b.createElement("a");a.innerHTML="<xyz></xyz>",f="hidden"in a,j=a.childNodes.length==1||function(){b.createElement("a");var a=b.createDocumentFragment();return typeof a.cloneNode=="undefined"||typeof a.createDocumentFragment=="undefined"||typeof a.createElement=="undefined"}()}catch(c){f=!0,j=!0}})();var r={elements:c.elements||"abbr article aside audio bdi canvas data datalist details figcaption figure footer header hgroup mark meter nav output progress section summary time video",shivCSS:c.shivCSS!==!1,supportsUnknownElements:j,shivMethods:c.shivMethods!==!1,type:"default",shivDocument:q,createElement:n,createDocumentFragment:o};a.html5=r,q(b)}(this,b),e._version=d,e._domPrefixes=o,e._cssomPrefixes=n,e.testProp=function(a){return B([a])},e.testAllProps=D,g.className=g.className.replace(/(^|\s)no-js(\s|$)/,"$1$2")+(f?" js "+s.join(" "):""),e}(this,this.document),function(a,b,c){function d(a){return"[object Function]"==o.call(a)}function e(a){return"string"==typeof a}function f(){}function g(a){return!a||"loaded"==a||"complete"==a||"uninitialized"==a}function h(){var a=p.shift();q=1,a?a.t?m(function(){("c"==a.t?B.injectCss:B.injectJs)(a.s,0,a.a,a.x,a.e,1)},0):(a(),h()):q=0}function i(a,c,d,e,f,i,j){function k(b){if(!o&&g(l.readyState)&&(u.r=o=1,!q&&h(),l.onload=l.onreadystatechange=null,b)){"img"!=a&&m(function(){t.removeChild(l)},50);for(var d in y[c])y[c].hasOwnProperty(d)&&y[c][d].onload()}}var j=j||B.errorTimeout,l=b.createElement(a),o=0,r=0,u={t:d,s:c,e:f,a:i,x:j};1===y[c]&&(r=1,y[c]=[]),"object"==a?l.data=c:(l.src=c,l.type=a),l.width=l.height="0",l.onerror=l.onload=l.onreadystatechange=function(){k.call(this,r)},p.splice(e,0,u),"img"!=a&&(r||2===y[c]?(t.insertBefore(l,s?null:n),m(k,j)):y[c].push(l))}function j(a,b,c,d,f){return q=0,b=b||"j",e(a)?i("c"==b?v:u,a,b,this.i++,c,d,f):(p.splice(this.i++,0,a),1==p.length&&h()),this}function k(){var a=B;return a.loader={load:j,i:0},a}var l=b.documentElement,m=a.setTimeout,n=b.getElementsByTagName("script")[0],o={}.toString,p=[],q=0,r="MozAppearance"in l.style,s=r&&!!b.createRange().compareNode,t=s?l:n.parentNode,l=a.opera&&"[object Opera]"==o.call(a.opera),l=!!b.attachEvent&&!l,u=r?"object":l?"script":"img",v=l?"script":u,w=Array.isArray||function(a){return"[object Array]"==o.call(a)},x=[],y={},z={timeout:function(a,b){return b.length&&(a.timeout=b[0]),a}},A,B;B=function(a){function b(a){var a=a.split("!"),b=x.length,c=a.pop(),d=a.length,c={url:c,origUrl:c,prefixes:a},e,f,g;for(f=0;f<d;f++)g=a[f].split("="),(e=z[g.shift()])&&(c=e(c,g));for(f=0;f<b;f++)c=x[f](c);return c}function g(a,e,f,g,h){var i=b(a),j=i.autoCallback;i.url.split(".").pop().split("?").shift(),i.bypass||(e&&(e=d(e)?e:e[a]||e[g]||e[a.split("/").pop().split("?")[0]]),i.instead?i.instead(a,e,f,g,h):(y[i.url]?i.noexec=!0:y[i.url]=1,f.load(i.url,i.forceCSS||!i.forceJS&&"css"==i.url.split(".").pop().split("?").shift()?"c":c,i.noexec,i.attrs,i.timeout),(d(e)||d(j))&&f.load(function(){k(),e&&e(i.origUrl,h,g),j&&j(i.origUrl,h,g),y[i.url]=2})))}function h(a,b){function c(a,c){if(a){if(e(a))c||(j=function(){var a=[].slice.call(arguments);k.apply(this,a),l()}),g(a,j,b,0,h);else if(Object(a)===a)for(n in m=function(){var b=0,c;for(c in a)a.hasOwnProperty(c)&&b++;return b}(),a)a.hasOwnProperty(n)&&(!c&&!--m&&(d(j)?j=function(){var a=[].slice.call(arguments);k.apply(this,a),l()}:j[n]=function(a){return function(){var b=[].slice.call(arguments);a&&a.apply(this,b),l()}}(k[n])),g(a[n],j,b,n,h))}else!c&&l()}var h=!!a.test,i=a.load||a.both,j=a.callback||f,k=j,l=a.complete||f,m,n;c(h?a.yep:a.nope,!!i),i&&c(i)}var i,j,l=this.yepnope.loader;if(e(a))g(a,0,l,0);else if(w(a))for(i=0;i<a.length;i++)j=a[i],e(j)?g(j,0,l,0):w(j)?B(j):Object(j)===j&&h(j,l);else Object(a)===a&&h(a,l)},B.addPrefix=function(a,b){z[a]=b},B.addFilter=function(a){x.push(a)},B.errorTimeout=1e4,null==b.readyState&&b.addEventListener&&(b.readyState="loading",b.addEventListener("DOMContentLoaded",A=function(){b.removeEventListener("DOMContentLoaded",A,0),b.readyState="complete"},0)),a.yepnope=k(),a.yepnope.executeStack=h,a.yepnope.injectJs=function(a,c,d,e,i,j){var k=b.createElement("script"),l,o,e=e||B.errorTimeout;k.src=a;for(o in d)k.setAttribute(o,d[o]);c=j?h:c||f,k.onreadystatechange=k.onload=function(){!l&&g(k.readyState)&&(l=1,c(),k.onload=k.onreadystatechange=null)},m(function(){l||(l=1,c(1))},e),i?k.onload():n.parentNode.insertBefore(k,n)},a.yepnope.injectCss=function(a,c,d,e,g,i){var e=b.createElement("link"),j,c=i?h:c||f;e.href=a,e.rel="stylesheet",e.type="text/css";for(j in d)e.setAttribute(j,d[j]);g||(n.parentNode.insertBefore(e,n),m(c,0))}}(this,document),Modernizr.load=function(){yepnope.apply(window,[].slice.call(arguments,0))};
  </script>
  <noscript>
  <style type="text/css" media="all">
  .da-thumbs li a div {
	top: 0px;
	left: -100%;
	-webkit-transition: all 0.3s ease;
	-moz-transition: all 0.3s ease-in-out;
	-o-transition: all 0.3s ease-in-out;
	-ms-transition: all 0.3s ease-in-out;
	transition: all 0.3s ease-in-out;
  }
  .da-thumbs li a:hover div{
	left: 0px;
  }
  </style>
  </noscript>
  <?php } ?>
</head>
<?php
  if (($menu->getActive() != $menu->getDefault()) && ($currentMenuName != 123) && ($currentMenuName != 125)) {
  ?>
<body>
  <?php
  }
  else
  { ?>
<body id="HomePage">  
  <?php } ?>
  <?php
  if (($menu->getActive() != $menu->getDefault()) && ($currentMenuName != 123) && ($currentMenuName != 125)) {
  ?>
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
      <div id="banner">
        <jdoc:include type="modules" name="position-0" />
      </div>
      <?php endif; ?>
      <?php if ($this->countModules('position-1')): ?>
      <div id="nav">
        <jdoc:include type="modules" name="position-1" />
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
  
  <div id="greyWrapper">
  <?php if ($this->countModules('position-2')): ?>
      <div id="breadcrumbs">
        <jdoc:include type="modules" name="position-2" />
      </div>
      <?php endif; ?>
  </div>
  <?php } ?>

  <?php
  if (($menu->getActive() == $menu->getDefault()) || ($currentMenuName == 123) || ($currentMenuName == 125)) {
  ?>
  <img alt="full screen background image" src="/images/background.jpg" id="full-screen-background-image" /> 
  <div id="Logo"><a href="/"><img src="images/logo.png" alt="" border="0" /></a><br />"¡Hagase visible!" </div>
     <?php if ($this->countModules('position-0')): ?>
      <div id="LoginForm">
        <jdoc:include type="modules" name="position-0" />
      </div>
  <?php endif; ?>
  <?php } ?>
  <div id="contentWrapper">
    <div id="main" class="container_<?php echo $grid; ?> clearfix">
    
      <?php if ($this->countModules('position-3') || $this->countModules('position-4')): ?>
      <div id="top" class="grid_<?php echo $grid; ?>">        
        <jdoc:include type="modules" name="position-3" style="xhtml" />
        <jdoc:include type="modules" name="position-4" style="xhtml" />
      </div>
      <?php endif; ?>
  <?php
  if (($menu->getActive() != $menu->getDefault()) && ($currentMenuName != 123) && ($currentMenuName != 125)) {
  ?>
      <div id="content" class="<?php echo $content_position; ?>">
  <?php
  }
  else {
   ?>
        <div id="content" class="grid_12">
          <?php } ?>
        <jdoc:include type="modules" name="position-12" style="xhtml" />
        <div id="contentInner">
          <?php if ($this->getBuffer('message')) : ?>
                  <jdoc:include type="message" />
          <?php endif; ?>
          <jdoc:include type="component" />
        </div>        
        <jdoc:include type="modules" name="position-13" style="xhtml" />
      </div>
  <?php
  if (($menu->getActive() != $menu->getDefault()) && ($currentMenuName != 123) && ($currentMenuName != 125)) {
  ?>
      <?php if ($show_sidebar): ?>
      <div id="sidebar" class="<?php echo $sidebar_position; ?>">
        <jdoc:include type="modules" name="position-5" style="xhtml" />
        <jdoc:include type="modules" name="position-6" style="xhtml" />
        <jdoc:include type="modules" name="position-7" style="xhtml" />
      </div>
      <?php endif; ?>
   <?php
    }
      ?>
      <?php if ($this->countModules('position-8') || $this->countModules('position-9') || $this->countModules('position-10') || $this->countModules('position-11') || $this->countModules('position-12') || $this->countModules('position-14')): ?>  
      <div id="bottom" class="container_12">
        <?php if ($this->countModules('position-8')): ?>
        <div class="grid_12">
          <jdoc:include type="modules" name="position-8" style="xhtml" />
        </div>
        <?php endif; ?>
        <?php if ($this->countModules('position-14')): ?>
        <div class="grid_12">
          <jdoc:include type="modules" name="position-14" style="xhtml" />
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <div id="footerWrapper">
    <div id="footer" class="clearfix container_12">
        <?php if ($this->countModules('position-9') || $this->countModules('position-10') || $this->countModules('position-11')): ?>
        <div class="grid_4">
          <jdoc:include type="modules" name="position-9" style="xhtml" />
        </div>
        <div class="grid_4">
          <jdoc:include type="modules" name="position-10" style="xhtml" />
        </div>
        <div class="grid_4">
          <jdoc:include type="modules" name="position-11" style="xhtml" />
        </div>
        <?php endif; ?>
      <div id="copyright"><?php echo $copyright; ?></div>
      <?php if ($this->countModules('position-15')): ?>
      <div id="footermenu">
        <jdoc:include type="modules" name="position-15" style="xhtml" />
      </div>
      <?php endif; ?>
      <?php if ($this->countModules('position-16')): ?>
      <div id="footerBottom">
        <jdoc:include type="modules" name="position-16" style="xhtml" />
      </div>
      <?php endif; ?>
    </div>
  </div>
  
<jdoc:include type="modules" name="debug" />

<?php
  if ($menu->getActive() == $menu->getDefault()) {
  ?>
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
		<script type="text/javascript">
/**
 * jquery.hoverdir.js v1.1.0
 * http://www.codrops.com
 *
 * Licensed under the MIT license.
 * http://www.opensource.org/licenses/mit-license.php
 * 
 * Copyright 2012, Codrops
 * http://www.codrops.com
 */
;( function( $, window, undefined ) {
	
	'use strict';

	$.HoverDir = function( options, element ) {
		
		this.$el = $( element );
		this._init( options );

	};

	// the options
	$.HoverDir.defaults = {
		speed : 300,
		easing : 'ease',
		hoverDelay : 0,
		inverse : false
	};

	$.HoverDir.prototype = {

		_init : function( options ) {
			
			// options
			this.options = $.extend( true, {}, $.HoverDir.defaults, options );
			// transition properties
			this.transitionProp = 'all ' + this.options.speed + 'ms ' + this.options.easing;
			// support for CSS transitions
			this.support = Modernizr.csstransitions;
			// load the events
			this._loadEvents();

		},
		_loadEvents : function() {

			var self = this;
			
			this.$el.on( 'mouseenter.hoverdir, mouseleave.hoverdir', function( event ) {
				
				var $el = $( this ),
					$hoverElem = $el.find( 'div' ),
					direction = self._getDir( $el, { x : event.pageX, y : event.pageY } ),
					styleCSS = self._getStyle( direction );
				
				if( event.type === 'mouseenter' ) {
					
					$hoverElem.hide().css( styleCSS.from );
					clearTimeout( self.tmhover );

					self.tmhover = setTimeout( function() {
						
						$hoverElem.show( 0, function() {
							
							var $el = $( this );
							if( self.support ) {
								$el.css( 'transition', self.transitionProp );
							}
							self._applyAnimation( $el, styleCSS.to, self.options.speed );

						} );
						
					
					}, self.options.hoverDelay );
					
				}
				else {
				
					if( self.support ) {
						$hoverElem.css( 'transition', self.transitionProp );
					}
					clearTimeout( self.tmhover );
					self._applyAnimation( $hoverElem, styleCSS.from, self.options.speed );
				}
			} );
		},
		// credits : http://stackoverflow.com/a/3647634
		_getDir : function( $el, coordinates ) {
			// the width and height of the current div
			var w = $el.width(),
				h = $el.height(),
				// calculate the x and y to get an angle to the center of the div from that x and y.
				// gets the x value relative to the center of the DIV and "normalize" it
				x = ( coordinates.x - $el.offset().left - ( w/2 )) * ( w > h ? ( h/w ) : 1 ),
				y = ( coordinates.y - $el.offset().top  - ( h/2 )) * ( h > w ? ( w/h ) : 1 ),
				// the angle and the direction from where the mouse came in/went out clockwise (TRBL=0123);
				// first calculate the angle of the point,
				// add 180 deg to get rid of the negative values
				// divide by 90 to get the quadrant
				// add 3 and do a modulo by 4  to shift the quadrants to a proper clockwise TRBL (top/right/bottom/left) **/
				direction = Math.round( ( ( ( Math.atan2(y, x) * (180 / Math.PI) ) + 180 ) / 90 ) + 3 ) % 4;
			return direction;
		},
		_getStyle : function( direction ) {
			var fromStyle, toStyle,
				slideFromTop = { left : '0px', top : '-100%' },
				slideFromBottom = { left : '0px', top : '100%' },
				slideFromLeft = { left : '-100%', top : '0px' },
				slideFromRight = { left : '100%', top : '0px' },
				slideTop = { top : '0px' },
				slideLeft = { left : '0px' };
			switch( direction ) {
				case 0:
					// from top
					fromStyle = !this.options.inverse ? slideFromTop : slideFromBottom;
					toStyle = slideTop;
					break;
				case 1:
					// from right
					fromStyle = !this.options.inverse ? slideFromRight : slideFromLeft;
					toStyle = slideLeft;
					break;
				case 2:
					// from bottom
					fromStyle = !this.options.inverse ? slideFromBottom : slideFromTop;
					toStyle = slideTop;
					break;
				case 3:
					// from left
					fromStyle = !this.options.inverse ? slideFromLeft : slideFromRight;
					toStyle = slideLeft;
					break;
			};
			return { from : fromStyle, to : toStyle };
		},
		// apply a transition or fallback to jquery animate based on Modernizr.csstransitions support
		_applyAnimation : function( el, styleCSS, speed ) {
			$.fn.applyStyle = this.support ? $.fn.css : $.fn.animate;
			el.stop().applyStyle( styleCSS, $.extend( true, [], { duration : speed + 'ms' } ) );
		},
	};
	var logError = function( message ) {
		if ( window.console ) {
			window.console.error( message );
		}
	};
	$.fn.hoverdir = function( options ) {
		var instance = $.data( this, 'hoverdir' );
		if ( typeof options === 'string' ) {
			var args = Array.prototype.slice.call( arguments, 1 );
			this.each(function() {
				if ( !instance ) {
					logError( "cannot call methods on hoverdir prior to initialization; " +
					"attempted to call method '" + options + "'" );
					return;
				}
				if ( !$.isFunction( instance[options] ) || options.charAt(0) === "_" ) {
					logError( "no such method '" + options + "' for hoverdir instance" );
					return;
				}
				instance[ options ].apply( instance, args );
			});
		} 
		else {
			this.each(function() {
				if ( instance ) {
					instance._init();
				}
				else {
					instance = $.data( this, 'hoverdir', new $.HoverDir( options, this ) );
				}
			});
		}
		return instance;
	};
} )( jQuery, window );
		</script>	
		<script type="text/javascript">
			$(function() {
			
				$(' #da-thumbs > li ').each( function() { $(this).hoverdir(); } );

			});
		</script>
<?php } ?>
</body>
</html>