<?php
 /**
  * iReview2 - Joomla template
  * Copyright (C) 2010-2011 ClickFWD LLC
  * This is not free software, do not distribute it.
  * For licencing information visit http://www.reviewsforjoomla.com
  * or contact sales@reviewsforjoomla.com
 **/

defined('_JEXEC') or die;

if (!isset($this->error)) {
	$this->error = JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
	$this->debug = false;
}

?>
<!DOCTYPE html>
<html lang="<?php echo $this->language; ?>" dir="<?php echo $this->direction; ?>">
<head>
	<title><?php echo $this->error->getCode(); ?> - <?php echo $this->title; ?></title>
	<link rel="stylesheet" href="<?php echo $this->baseurl; ?>/templates/<?php echo $this->template; ?>/css/template.css" type="text/css" />
</head>
<body>
	<div class="error-wrapper">

		<h1>Error <?php echo $this->error->getCode(); ?></h1>
		<h3>(<?php echo $this->error->getMessage(); ?>)</h3>
		<div class="error-message">

			<p><strong><?php echo JText::_('JERROR_LAYOUT_PLEASE_TRY_ONE_OF_THE_FOLLOWING_PAGES'); ?></strong></p>

			<a href="<?php echo $this->baseurl; ?>/index.php" title="<?php echo JText::_('JERROR_LAYOUT_GO_TO_THE_HOME_PAGE'); ?>"><?php echo JText::_('JERROR_LAYOUT_HOME_PAGE'); ?></a>

		</div>
	</div>
</body>
</html>