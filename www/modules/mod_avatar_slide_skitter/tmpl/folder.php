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
	$flagLink = 0;
	for($p = 0 ; $p < sizeof($path) ; $p++)
	{			
		for($n = 0 ; $n < sizeof($tmpListImage[$p]) && $n< $itemCount ; $n++, $count++)
		{
			$tmp = $tmpListImage[$p][$n];
			echo "<li>";
			if ($tmplistLink != NULL) 
			{
				$position = $count+1;
				if(isset($tmplistLink["$position"]))
				{
					if($tmplistLink["$position"] != NULL)
					{
						echo "<a href='";
						echo $tmplistLink["$position"];
						echo "'>";
						$flagLink = 1;
					}
				}
			}
			//echo JURI::base()."images/$path[$p]/$tmp<img src='"; 
			echo "<img src='";
			echo JURI::base()."images/$path[$p]/$tmp' alt=''/>";
			if ($flagLink == 1) 
			{
				echo "</a>";
				$flagLink = 0;
			}
			if ($tmpListDesc != NULL && $imageDes == 'true') 
			{
				$position = $count+1;
				if(isset($tmpListDesc["$position"]))
				{
					if($tmpListDesc["$position"] != NULL)
					{
						echo "<div class='label_text'><p>";
						echo $tmpListDesc["$position"];
						echo "</p></div>";
					}
				}
			}
			echo "</li>";
		}
	}   		
?>