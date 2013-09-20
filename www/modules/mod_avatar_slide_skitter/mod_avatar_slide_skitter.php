<?php
/**
 * @version		$Id: helper.php 5 2012-04-06 20:10:35Z mozart $
 * @copyright	JoomAvatar.com
 * @author		Tran Nam Chung
 * @mail		chungtn@joomavatar.com
 * @link		http://joomavatar.com
 * @license		License GNU General Public License version 2 or later
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
// Include the syndicate functions only once
require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'helper.php' );
require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'avatar.helper.php' );
require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'avatar.image.php' );

$moduleclass_sfx = AMSize::getClassSuffix($params);
$width = AMSize::getWidth($params);
$height = AMSize::getHeight($params);
$cr = AMSize::getCopyRight($params);
$path = AMSlide::getFolders($params);
$itemCount = AMSlide::getItemCount($params);
$autoPlay = AMSlide::getAutoPlay($params);
$slidingTime = AMSlide::getSlideTime($params);
$animation = AMSlide::getTransition($params);
$descriptions = AMSlide::getDescription($params);
$jquery = AMJquery::getJqueryVer($params);

$source = mod_avatar_slide_skitterHelper::getSource($params);
$navStyle = mod_avatar_slide_skitterHelper::getnavStyle($params);
$hideTools = mod_avatar_slide_skitterHelper::gethideTools($params);
$showRandomly = mod_avatar_slide_skitterHelper::getshowRandomly($params);
$controls = mod_avatar_slide_skitterHelper::getcontrols($params);
$controlsPosition = mod_avatar_slide_skitterHelper::getcontrolsPosition($params);
$focus = mod_avatar_slide_skitterHelper::getfocus($params);
$focusPosition = mod_avatar_slide_skitterHelper::getfocusPosition($params);
$numbersAlign = mod_avatar_slide_skitterHelper::getnumbersAlign($params);
$progressBar = mod_avatar_slide_skitterHelper::getprogressBar($params);
$bgNumberOut = mod_avatar_slide_skitterHelper::getbgNumberOut($params);
$bgNumberOver = mod_avatar_slide_skitterHelper::getbgNumberOver($params);
$bgNumberActive = mod_avatar_slide_skitterHelper::getbgNumberActive($params);
$links = mod_avatar_slide_skitterHelper::getlinks($params);
$imageDes = mod_avatar_slide_skitterHelper::getimageDes($params);
$title = mod_avatar_slide_skitterHelper::gettitle($params);
$responsive = mod_avatar_slide_skitterHelper::getresponsive($params);
$id = "skitter_module";

//check all of animations, if random or randomsmart avaible, only use it
for($n = 0;$n < sizeof($animation) && $animation[$n] != 'random' && $animation[$n] != 'randomSmart';$n++);
if($n < sizeof($animation))
{
	$animationSetting = 'options.animation';
	$animation = array($animation[$n]);
}
else
	$animationSetting = 'options.with_animations';
//$ = mod_avatar_slide_skitterHelper::get($params);
$document = JFactory::getDocument();
switch ($jquery) {
	case 'unload':	
		break;
	case 'latest':
		$document->addScript('http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js');
		$document->addScriptDeclaration('jQuery.noConflict();');
		break;
	default:
		$document->addScript('http://ajax.googleapis.com/ajax/libs/jquery/'.$jquery.'/jquery.min.js');
		$document->addScriptDeclaration('jQuery.noConflict();');
		break;
}
$document->addStyleSheet(JURI::base().'modules/mod_avatar_slide_skitter/assets/css/skitter.styles.css');
$document->addScript(JURI::base().'modules/mod_avatar_slide_skitter/assets/js/jquery.easing.1.3.js');
$document->addScript(JURI::base().'modules/mod_avatar_slide_skitter/assets/js/jquery.skitter.js');
$document->addScript('http://www.bitstorm.org/jquery/color-animation/jquery.animate-colors-min.js');
//HTML
echo 	"<div id='".$id."' style='margin:auto;'>".
		"<div id='avatar_skitter_".$id."' class='box_skitter box_skitter_large' style='margin:auto;width:".$width.";height:".$height."'>".
		"<ul>";
switch ($source) {
	case 'folder':
		require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'avatar.image.php' );
		$path = AMSlide::getFolders($params);
		$tmp = new imageData();
		$tmp->setPath($path,sizeof($path));
		$tmpListImage = $tmp->getArrayImageLinks();
		$tmpListDesc = $tmp->getArrayImageInfo($descriptions);
		$tmplistLink = $tmp->getArrayImageInfo($links);
		require( JModuleHelper::getLayoutPath( 'mod_avatar_slide_skitter' , 'folder') );
		break;
}
echo "</ul></div></div>";
?>
<div class="avatar-copyright" style="width:100%;margin: 5px;text-align: center;display : <?php if (strtolower($cr) == false) echo 'none'; else echo 'block';?> ;">
&copy; JoomAvatar.com
	<a target="_blank" href="http://joomavatar.com" title="Joomla Template &amp; Extension">Joomla Extension</a>-
	<a target="_blank" href="http://joomavatar.com" title="Joomla Template &amp; Extension">Joomla Template</a>
</div>

<script type="text/javascript">
jQuery.noConflict();
(function($) 
{ 
		$(document).ready( function()
		{	
			var options = {};
			options.width_skitter 	= $('#avatar_skitter_<?php echo $id;?>').css("width");
			options.height_skitter 	= $('#avatar_skitter_<?php echo $id;?>').css("height");
			options.animation		= 'random';
			options.skitterid		= $('#avatar_skitter_<?php echo $id;?>');
			options.auto_play 		= <?php echo $autoPlay;?>;
			options.interval 		= <?php echo $slidingTime;?>;
			options.hideTools 		= <?php echo $hideTools;?>;
			options.show_randomly 	= <?php echo $showRandomly;?>;
			options.controls 		= <?php echo $controls;?>;
			options.controls_position = '<?php echo $controlsPosition;?>';
			options.focus 			= <?php echo $focus;?>;
			options.focus_position 	= '<?php echo $focusPosition;?>';
			options.numbers_align	= '<?php echo $numbersAlign;?>';
			options.progressbar		= <?php echo $progressBar;?>;
			options.animateNumberOut = {backgroundColor:'<?php echo $bgNumberOut;?>'};
			options.animateNumberOver = {backgroundColor:'<?php echo $bgNumberOver;?>'};
			options.animateNumberActive = {backgroundColor:'<?php echo $bgNumberActive;?>'};
			<?php switch ($navStyle) {
				case 'numbers':
					echo "options.numbers = true;";
					break;
				case 'thumbs':
					echo "options.thumbs = true;";
					break;
				case 'dots':
					echo "$";echo "('#avatar_skitter_".$id."').css({'marginBottom': '40px'});";
					echo "options.dots = true;";
					break;
				case 'preview':
					echo "$";echo "('#avatar_skitter_".$id."').css({'marginBottom': '40px'});";
					echo "options.dots = true;";
					echo "options.preview = true;";
					break;
			}?>
			<?php if ($responsive){ ?>
			    var resizeTime;
			    var slideHTML = $('#avatar_skitter_<?php echo $id;?>').parent().html();
			    $('#avatar_skitter_<?php echo $id;?>').skitter(options);
			      
			    $(window).resize(function(e) 
			    {
			    	clearTimeout(resizeTime);
			    	$('#avatar_skitter_<?php echo $id;?>').parent().html(slideHTML);
			    	options.width_skitter  = $('#avatar_skitter_<?php echo $id;?>').css("width");
			    	
			    	resizeTime = setTimeout(function(){
					$('#avatar_skitter_<?php echo $id;?>').skitter(options);
			    	}, 500);
			    });
			   <?php } else { ?>
			    $('#avatar_skitter_<?php echo $id;?>').skitter(options);
			   <?php }?> 
		});
})(jQuery);
</script>