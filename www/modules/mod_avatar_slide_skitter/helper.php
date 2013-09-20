<?php
/**
 * @version		$Id: helper.php 5 2012-04-06 20:10:35Z mozart $
 * @copyright	JoomAvatar.com
 * @author		Tran Nam Chung
 * @mail		chungnt@joomavatar.com
 * @link		http://joomavatar.com
 * @license		License GNU General Public License version 2 or later
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
class mod_avatar_slide_skitterHelper
{
	public static function getSource($params){return $params->get('Source');} 	
	public static function getnavStyle($params){return $params->get('navStyle');}
	public static function gethideTools($params){return $params->get('hideTools');}
	public static function getcontrols($params){return $params->get('controls');}
	public static function getcontrolsPosition($params){return $params->get('controlsPosition');}
	public static function getfocus($params){return $params->get('focus');}
	public static function getfocusPosition($params){return $params->get('focusPosition');}
	public static function getnumbersAlign($params){return $params->get('numbersAlign');}
	public static function getprogressBar($params){return $params->get('progressBar');}
	public static function getshowRandomly($params){return $params->get('showRandomly');}
	public static function getbgNumberOut($params){return $params->get('bgNumberOut');}
	public static function getbgNumberOver($params){return $params->get('bgNumberOver');}
	public static function getbgNumberActive($params){return $params->get('bgNumberActive');}
	public static function getlinks($params){return $params->get('link');}
	public static function getimageDes($params){return $params->get('imageDes');}
	public static function gettitle($params){return $params->get('title');}
	public static function getresponsive($params){return $params->get('responsive');}
	public static function getjoomQuality($params){return $params->get('joomQuality');}
	//function get($params){return $params->get('');}
}
?>