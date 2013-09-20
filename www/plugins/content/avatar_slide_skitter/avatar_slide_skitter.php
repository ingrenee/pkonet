<?php
/**
 * @version		$Id: avatar_slide_skitter.php 97 2012-04-14 chungtn2910@gmail.com $
 * @copyright	JoomAvatar.com
 * @author		Tran Nam Chung
 * @link		http://joomavatar.com
 * @license		License GNU General Public License version 2 or later
 */

// No direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');
require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'avatar.skitter.php' );

class plgContentavatar_slide_skitter extends JPlugin
{
	public function onContentPrepare($context, &$article, &$params, $limitstart)
	{
		$app = JFactory::getApplication();		
		$countPlg = 0 ;
		if(!isset($plgSkitter)){$plgSkitter = array();}
		$animations_functions = array(
				'cube', 
				'cubeRandom', 
				'block', 
				'cubeStop', 
				'cubeStopRandom', 
				'cubeHide', 
				'cubeSize', 
				'horizontal', 
				'showBars', 
				'showBarsRandom', 
				'tube',
				'fade',
				'fadeFour',
				'paralell',
				'blind',
				'blindHeight',
				'blindWidth',
				'directionTop',
				'directionBottom',
				'directionRight',
				'directionLeft',
				'cubeSpread',
				'glassCube',
				'glassBlock',
				'circles',
				'circlesInside',
				'circlesRotate',
				'cubeShow',
				'upBars', 
				'downBars', 
				'hideBars', 
				'swapBars', 
				'swapBarsBack'
			);
		
		jimport('joomla.html.pane');
		$text = &$article->text;

		$pattern = '/\{avatarskitter[^}]*\/}/i';
		preg_match_all($pattern, $text, $matches);
		$arrayFormat = array();
		
		foreach ($matches[0] as $format)
		{
			ob_start();
			$plgSkitter[$countPlg] = new AvatarSkitterOptions;
			$format = strip_tags($format);
			
			try
			{
				//check path
				$pattern = '/\bpath=[a-zA-Z0-9+-_\/]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0)
				{
					$tmp = explode('=', $Info[0]);		
					$plgSkitter[$countPlg]->path = $tmp[1];
				}
				else
				{
					throw new Exception("check 'path=...' again");
				}
				
				$pattern = '/\bauto=[A-Za-z]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					if(strtolower($tmp[1]) == "true" || strtolower($tmp[1]) == "false")
						$plgSkitter[$countPlg]->auto = strtolower($tmp[1]);
					else 
						throw new Exception("check '".$tmp[0]."' again");
				}
				
				//check autoplay/interval
				$pattern = '/\binterval=[0-9]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					$plgSkitter[$countPlg]->interval = $tmp[1];
				}
				
				//check height
				$pattern = '/\bheight=[0-9px%]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0)
				{
					$tmp = explode('=', $Info[0]);
					$plgSkitter[$countPlg]->slideHeight = $tmp[1];
				}
				
				//check width
				$pattern = '/\bwidth=[0-9px%]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					$plgSkitter[$countPlg]->slideWidth = $tmp[1];
				}
				
				//check hidetools
				$pattern = '/\btool=[A-Za-z]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					if(strtolower($tmp[1]) == "true" || strtolower($tmp[1]) == "false")
						$plgSkitter[$countPlg]->hideTools = strtolower($tmp[1]);
					else 
						throw new Exception("check '".$tmp[0]."' again");
				}
				
				//check animation
				$pattern = '/\banimation=[A-Za-z0-9,]*/i';
				preg_match($pattern, $format, $Info);
				$plgSkitter[$countPlg]->animation = array();
				if (count($Info) > 0) 
				{
					$tmp = explode('=',$Info[0]);
					$animationSetting = 'options.with_animations';
					$tmparray = array_unique(explode(',',$tmp[1]));
					$tmpanimation = array();
					for($n = 0; $n < sizeof($tmparray) ; $n++)
					{
						for($i = 0; $i < sizeof($animations_functions) && $tmparray[$n] != $animations_functions[$i]; $i++);
						if($tmparray[$n] == 'random')
						{
							$n = sizeof($tmparray);
							$tmpanimation = array('random');
							$plgSkitter[$countPlg]->animation = $tmpanimation;
							$animationSetting = 'options.animation';
							break;
						}
						if($tmparray[$n] == 'randomSmart')
						{
							$n = sizeof($tmparray);
							$tmpanimation = array('randomSmart');
							$plgSkitter[$countPlg]->animation = $tmpanimation;
							$animationSetting = 'options.animation';
							break;
						}
						if($i >= sizeof($animations_functions))
						{
							throw new Exception("check '".$tmp[0]."' again. ".$tmparray[$n]." is not correct");
						}
						else
						{
							array_push($tmpanimation,$tmparray[$n]);
						}
					}
					$plgSkitter[$countPlg]->animation = $tmpanimation;
				}
				else{
					$animationSetting = 'options.animation';
				}

				//check count
				$pattern = '/\bcount=[0-9]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					$plgSkitter[$countPlg]->maxImages = $tmp[1];
				}
				
				//check navigation style
				$pattern = '/\bnavstyle=[A-Za-z]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					if(strtolower($tmp[1]) == "numbers" || strtolower($tmp[1]) == "dots" || strtolower($tmp[1]) == "thumbs" || strtolower($tmp[1]) == "preview")
						$plgSkitter[$countPlg]->navStyle = strtolower($tmp[1]);
					else 
						throw new Exception("check '".$tmp[0]."' again");
				}
				
				//check random slider
				$pattern = '/\brandom=[A-Za-z]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					if(strtolower($tmp[1]) == "true" || strtolower($tmp[1]) == "false")
						$plgSkitter[$countPlg]->random = strtolower($tmp[1]);
					else 
						throw new Exception("check '".$tmp[0]."' again");
				}
				
				$pattern = '/\bcontrols=[A-Za-z]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					if(strtolower($tmp[1]) == "true" || strtolower($tmp[1]) == "false")
						$plgSkitter[$countPlg]->controls = $tmp[1];
					else 
						throw new Exception("check '".$tmp[0]."' again");
				}
				
				//check controls position
				$pattern = '/\bcontrolspositon=[A-Za-z]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					if(strtolower($tmp[1]) == "center" || strtolower($tmp[1]) == "lefttop" || strtolower($tmp[1]) == "righttop" || strtolower($tmp[1]) == "leftbottom" || strtolower($tmp[1]) == "rightbottom")
					{
						switch (strtolower($tmp[1])) 
						{
							case 'center':
								$plgSkitter[$countPlg]->ctrlPosition = 'center';
								break;
							case 'righttop':
								$plgSkitter[$countPlg]->ctrlPosition = 'rightTop';
								break;
							case 'lefttop':
								$plgSkitter[$countPlg]->ctrlPosition = 'leftTop';
								break;
							case 'rightbottom':
								$plgSkitter[$countPlg]->ctrlPosition = 'rightBottom';
								break;
							case 'leftbottom':
								$plgSkitter[$countPlg]->ctrlPosition = 'leftbottom';
								break;
						}
					}
						
					else 
						throw new Exception("check '".$tmp[0]."' again");
				}
				
				$pattern = '/\bfocus=[A-Za-z]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					if(strtolower($tmp[1]) == "true" || strtolower($tmp[1]) == "false")
						$plgSkitter[$countPlg]->focus = $tmp[1];
					else 
						throw new Exception("check '".$tmp[0]."' again");
				}
				
				//check controls position
				$pattern = '/\bfocuspositon=[A-Za-z]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					if(strtolower($tmp[1]) == "center" || strtolower($tmp[1]) == "lefttop" || strtolower($tmp[1]) == "righttop" || strtolower($tmp[1]) == "leftbottom" || strtolower($tmp[1]) == "rightbottom")
					{
						switch (strtolower($tmp[1])) 
						{
							case 'center':
								$plgSkitter[$countPlg]->focusPosition = 'center';
								break;
							case 'righttop':
								$plgSkitter[$countPlg]->focusPosition = 'rightTop';
								break;
							case 'lefttop':
								$plgSkitter[$countPlg]->focusPosition = 'leftTop';
								break;
							case 'rightbottom':
								$plgSkitter[$countPlg]->focusPosition = 'rightBottom';
								break;
							case 'leftbottom':
								$plgSkitter[$countPlg]->focusPosition = 'leftbottom';
								break;
						}
					}
						
					else 
						throw new Exception("check '".$tmp[0]."' again");
				}
				
				//check number align
				$pattern = '/\bnumbersalign=[A-Za-z]*/i';
				preg_match($pattern, $format,$Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					if(strtolower($tmp[1]) == "center" || strtolower($tmp[1]) == "left" || strtolower($tmp[1]) == "right")
					{
						switch (strtolower($tmp[1])) 
						{
							case 'center':
								$plgSkitter[$countPlg]->numbersAlign = 'center';
								break;
							case 'right':
								$plgSkitter[$countPlg]->numbersAlign = 'right';
								break;
							case 'left':
								$plgSkitter[$countPlg]->numbersAlign = 'left';
								break;
						}
					}
					else 
						throw new Exception("check '".$tmp[0]."' again");
				}
				
				//check progress
				$pattern = '/\bprogress=[A-Za-z]*/i';
				preg_match($pattern, $format,$Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					if(strtolower($tmp[1]) == "true" || strtolower($tmp[1]) == "false")
						$plgSkitter[$countPlg]->progress = strtolower($tmp[1]);
					else 
						throw new Exception("check '".$tmp[0]."' again");
				}
				
				//check responsive
				$pattern = '/\bresponsive=[A-Za-z]*/i';
				preg_match($pattern, $format,$Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
					if(strtolower($tmp[1]) == "true" || strtolower($tmp[1]) == "false")
						$plgSkitter[$countPlg]->responsive = strtolower($tmp[1]);
					else 
						throw new Exception("check '".$tmp[0]."' again");
				}
				
				//check jquery
				$pattern = '/\bjquery=[A-Za-z0-9.]*/i';
				preg_match($pattern, $format, $Info);
				if (count($Info) > 0) 
				{
					$tmp = explode('=', $Info[0]);
						$plgSkitter[$countPlg]->jquery = $tmp[1];
				}
				
				
			}catch(Exception $e){
				echo "<p style='color:red;text-align:center;'>Avatar Slide Skitter Error: ", $e->getMessage(), "</p>";
			}
			$id 								= "skitter_plg";
			$plgSkitter[$countPlg]->maxImages 	= 7;
			$plgSkitter[$countPlg]->cr 			= "false";
			$document = JFactory::getDocument();
			switch ($plgSkitter[$countPlg]->jquery) {
				case 'unload':	
					break;
				case 'latest':
					$document->addScript('http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js');
					$document->addScriptDeclaration('jQuery.noConflict();');
					break;
				default:
					$document->addScript('http://ajax.googleapis.com/ajax/libs/jquery/'.$plgSkitter[$countPlg]->jquery.'/jquery.min.js');
					$document->addScriptDeclaration('jQuery.noConflict();');
					break;
			}
			$document->addStyleSheet(JURI::base().'plugins/content/avatar_slide_skitter/assets/css/skitter.styles.css');
			$document->addScript(JURI::base().'plugins/content/avatar_slide_skitter/assets/js/jquery.easing.1.3.js');
			$document->addScript(JURI::base().'plugins/content/avatar_slide_skitter/assets/js/jquery.skitter.js');
			$document->addScript('http://www.bitstorm.org/jquery/color-animation/jquery.animate-colors-min.js');
			//HTML
			echo 	"<div id='".$id."' style='margin:auto;'>".
					"<div id='avatar_skitter_".$id."' class='box_skitter box_skitter_large' style='margin:auto;width:".$plgSkitter[$countPlg]->slideWidth.";height:".$plgSkitter[$countPlg]->slideHeight."'>".
					"<ul>";
			switch ($plgSkitter[$countPlg]->source) {
				case 'folder':
					require_once( dirname(__FILE__).DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'avatar.image.php' );
					$tmp = new imageData();
					$path = explode(",",$plgSkitter[$countPlg]->path);
					$tmp->setPath($path);
					$tmpListImage = $tmp->getArrayImageLinks();
					require(JPATH_PLUGINS."/content/avatar_slide_skitter/tmpl/folder.php");
					break;
			}
			echo "</ul></div></div>";	
			?>
			<script type="text/javascript">
			jQuery.noConflict();
			(function($) 
			{ 
					$(document).ready( function()
					{
						$('#avatar_skitter_<?php echo $id;?>').css("width","<?php echo $plgSkitter[$countPlg]->slideWidth;?>");
						$('#avatar_skitter_<?php echo $id;?>').css("height","<?php echo $plgSkitter[$countPlg]->slideHeight?>");
						$('#avatar_skitter_<?php echo $id;?>').css("display","block");		
						var options = {};
						options.width_skitter 	= $('#avatar_skitter_<?php echo $id;?>').css("width");
						options.height_skitter 	= $('#avatar_skitter_<?php echo $id;?>').css("height");
						options.animation		= 'random';
						options.skitterid		= $('#avatar_skitter_<?php echo $id;?>');
						options.auto_play 		= <?php echo$plgSkitter[$countPlg]->auto;?>;
						options.interval 		= <?php echo $plgSkitter[$countPlg]->interval;?>;
						options.hideTools 		= <?php echo $plgSkitter[$countPlg]->hideTools;?>;
						options.show_randomly 	= <?php echo $plgSkitter[$countPlg]->random;?>;
						options.controls 		= <?php echo $plgSkitter[$countPlg]->controls;?>;
						options.controls_position = '<?php echo $plgSkitter[$countPlg]->ctrlPosition;?>';
						options.focus 			= <?php echo $plgSkitter[$countPlg]->focus;?>;
						options.focus_position 	= '<?php echo $plgSkitter[$countPlg]->focusPosition;?>';
						options.numbers_align	= '<?php echo $plgSkitter[$countPlg]->numbersAlign;?>';
						options.progressbar		= <?php echo $plgSkitter[$countPlg]->progress;?>;
						<?php switch ($plgSkitter[$countPlg]->navStyle) {
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
						<?php if ($plgSkitter[$countPlg]->responsive){ ?>
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
			<div class="avatar-copyright" style="width:100%;margin: 5px;text-align: center;display : <?php if (strtolower($plgSkitter[$countPlg]->cr) == false) echo 'none'; else echo 'block';?> ;">
			&copy; JoomAvatar.com
				<a target="_blank" href="http://joomavatar.com" title="Joomla Template &amp; Extension">Joomla Extension</a>-
				<a target="_blank" href="http://joomavatar.com" title="Joomla Template &amp; Extension">Joomla Template</a>
			</div>	
			<?php
			$countPlg++;
			$content = ob_get_clean();
			$arrayFormat[$format] = $content;
		}
		
		foreach ($arrayFormat as $keyFormat => $valueFormat)
		{
			if (!empty($valueFormat)) {
				$text = str_replace($keyFormat, $valueFormat, $text);	
			} else {
				$text = str_replace($keyFormat, '', $text);
			}
		}
	}
}