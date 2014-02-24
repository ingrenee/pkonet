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
	<jdoc:include type="head" />

	<?php if ($responsiveLayout): ?>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
	<?php endif;?>

	<link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/template.css" type="text/css" />
	<?php if($googleFonts): ?>
	<link href='//fonts.googleapis.com/css?family=<?php echo $loadGoogleFonts; ?>' rel='stylesheet' type='text/css'>
	<?php endif;?>
	<style type="text/css">
		body {
			font-family: '<?php echo str_replace('+', ' ', $bodyFont);?>', sans-serif;
		}
		h1, h2, h3, h4, h5, h6 {
			font-family: '<?php echo str_replace('+', ' ', $headingsFont);?>', sans-serif;
		}
		<?php if ($showLogo && !$logo):; ?>
		.logo {
			font-family: '<?php echo str_replace('+', ' ', $logoFont);?>', sans-serif;
		}
		<?php endif;?>
	</style>
	<link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/custom.css" type="text/css" />

</head>
<body>

<div class="offline-wrapper">

	<jdoc:include type="message" />

	<?php if ($app->getCfg('offline_image')) : ?>
		<img src="<?php echo $app->getCfg('offline_image'); ?>" alt="<?php echo htmlspecialchars($app->getCfg('sitename')); ?>" />
	<?php endif; ?>

		<h1 class="logo">
			<?php if ($logo): ?>
			<img src="<?php echo $this->baseurl ?>/<?php echo htmlspecialchars($logo); ?>"  alt="<?php echo $app->getCfg('sitename'); ?>" />
			<?php else: ?>
			<?php echo $app->getCfg('sitename'); ?>
			<?php endif;?>
		</h1>

	<?php if ($app->getCfg('display_offline_message', 1) == 1 && str_replace(' ', '', $app->getCfg('offline_message')) != ''): ?>
		<p class="offline-message">
			<?php echo $app->getCfg('offline_message'); ?>
		</p>
	<?php elseif ($app->getCfg('display_offline_message', 1) == 2 && str_replace(' ', '', JText::_('JOFFLINE_MESSAGE')) != ''): ?>
		<p class="offline-message">
			<?php echo JText::_('JOFFLINE_MESSAGE'); ?>
		</p>
	<?php  endif; ?>

	<div class="mod-login">
		<form action="<?php echo JRoute::_('index.php', true); ?>" method="post" id="form-login">
		<fieldset class="input">
			<p id="form-login-username">
				<label for="username"><?php echo JText::_('JGLOBAL_USERNAME') ?></label>
				<input name="username" id="username" type="text" class="inputbox" alt="<?php echo JText::_('JGLOBAL_USERNAME') ?>" size="18" />
			</p>
			<p id="form-login-password">
				<label for="passwd"><?php echo JText::_('JGLOBAL_PASSWORD') ?></label>
				<input type="password" name="password" class="inputbox" size="18" alt="<?php echo JText::_('JGLOBAL_PASSWORD') ?>" id="passwd" />
			</p>
			<p id="form-login-remember">
				<label for="remember" class="remember-label"><?php echo JText::_('JGLOBAL_REMEMBER_ME') ?></label>
				<input type="checkbox" name="remember" class="inputbox" value="yes" alt="<?php echo JText::_('JGLOBAL_REMEMBER_ME') ?>" id="remember" />
			</p>
			<input type="submit" name="Submit" class="button" value="<?php echo JText::_('JLOGIN') ?>" />
			<input type="hidden" name="option" value="com_users" />
			<input type="hidden" name="task" value="user.login" />
			<input type="hidden" name="return" value="<?php echo base64_encode(JURI::base()) ?>" />
			<?php echo JHtml::_('form.token'); ?>
		</fieldset>
		</form>
	</div>
</div>

<jdoc:include type="modules" name="debug" />
</body>
</html>