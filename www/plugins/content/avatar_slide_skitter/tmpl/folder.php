<?php 
/**
 * @version		$Id: default.php 5 2012-04-06 20:10:35Z mozart $
 * @copyright	JoomAvatar.com
 * @author		Tran Nam Chung
 * @mail		chungnt@joomavatar.com
 * @link		http://joomavatar.com
 * @license		License GNU General Public License version 2 or later
 */
// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
?>
<?php
	$count = 0;
	for($p = 0 ; $p < sizeof($path) ; $p++)
	{			
		for($n = 0 ; $n < sizeof($tmpListImage[$p]) && $n< $plgSkitter[$countPlg]->maxImages ; $n++, $count++)
		{
			$tmp = $tmpListImage[$p][$n];
			echo "<li><img src='".JURI::base()."images/$path[$p]/$tmp' alt=''/></a></li>";
		}
	}   		
?>