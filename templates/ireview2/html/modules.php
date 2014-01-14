<?php
/**
 * @package		Joomla.Site
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

/*
 * raw
 */
function modChrome_raw($module, &$params, &$attribs)
{
	$moduleClass = htmlspecialchars($params->get('moduleclass_sfx'));

	if (!empty ($module->content)) : ?>
	<div class="mod-inner <?php echo $moduleClass; ?>">
		<div class="mod-content"><?php echo $module->content; ?></div>
	</div>
	<?php endif;
}


/*
 * block
 */
function modChrome_block($module, &$params, &$attribs)
{
	$moduleClass = htmlspecialchars($params->get('moduleclass_sfx'));

	if (!empty ($module->content)) : ?>
		<div class="mod-container <?php echo $moduleClass; ?>">
			<div class="mod-inner">

			<?php if ($module->showtitle != 0) : ?>
				<h3 class="mod-title"><?php echo $module->title; ?></h3>
			<?php endif; ?>
				<div class="mod-content"><?php echo $module->content; ?></div>
			</div>
		</div>
	<?php endif;
}

?>
