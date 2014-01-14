<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_menu
 * @copyright	Copyright (C) 2005 - 2013 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

if ($class_sfx == '' || $class_sfx == ' nav-pills') {
	$class_sfx = 'menu';
}

$mobile_class = '';
if (strpos($class_sfx, 'light') !== false) {
	$mobile_class = ' mobile-light';
}

if (strpos($class_sfx, 'dark') !== false) {
	$mobile_class = ' mobile-dark';
}

?>

<div class="mobile-menu-toggle<?php echo $mobile_class; ?> <?php echo $params->get('menutype'); ?>"><span class="mobile-menu-icon"></span></div>
<ul class="<?php echo $class_sfx;?>"<?php
	$tag = '';
	if ($params->get('tag_id')!=NULL) {
		$tag = $params->get('tag_id').'';
		echo ' id="'.$tag.'"';
	}
?>>
<?php
foreach ($list as $i => &$item) :

	// check if the menu item should output module position
	$module_position = '';
	if (strpos($item->note, 'menu-position') !== false) {
		$module_position = $item->note;
	}

	$class = 'item-'.$item->id;
	if ($item->id == $active_id) {
		$class .= ' current';
	}

	if (in_array($item->id, $path)) {
		$class .= ' active';
	}
	elseif ($item->type == 'alias') {
		$aliasToId = $item->params->get('aliasoptions');
		if (count($path) > 0 && $aliasToId == $path[count($path)-1]) {
			$class .= ' active';
		}
		elseif (in_array($aliasToId, $path)) {
			$class .= ' alias-parent-active';
		}
	}

	if ($item->deeper) {
		$class .= ' deeper';
	}

	if ($item->parent) {
		$class .= ' parent';
	}

	if ($module_position != '') {
		$class .= ' deeper';
		$class .= ' parent';
	}

	if (!empty($class)) {
		$class = ' class="'.trim($class) .'"';
	}

	echo '<li'.$class.'>';

	// Render the menu item.
	switch ($item->type) :
		case 'separator':
		case 'url':
		case 'component':
			require JModuleHelper::getLayoutPath('mod_menu', 'default_'.$item->type);
			break;

		default:
			require JModuleHelper::getLayoutPath('mod_menu', 'default_url');
			break;
	endswitch;

	if ($module_position != '') {

		$modules        = JModuleHelper::getModules($module_position);
		$document       = JFactory::getDocument();
		$renderer       = $document->loadRenderer('module');
		$custom_params  = array('style'=>'block');

		$mod_count = count($modules);

		$contents = '';

		foreach ($modules as $module)  {
			$contents .= $renderer->render($module, $custom_params);
		}

		if($mod_count != 0) {
			echo '<div class="menu-module mod-count-'.$mod_count.'"><div class="menu-module-inner">'.$contents.'</div></div>';
		}

	}

	// The next item is deeper.
	if ($item->deeper) {
		echo '<ul>';
	}
	// The next item is shallower.
	elseif ($item->shallower) {
		echo '</li>';
		echo str_repeat('</ul></li>', $item->level_diff);
	}
	// The next item is on the same level.
	else {
		echo '</li>';
	}

endforeach;
?></ul>
