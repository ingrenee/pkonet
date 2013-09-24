<?php

$app = JFactory::getApplication();
$style = $this->params->get('style');
$logo = $this->params->get('logo');
$copyright = $this->params->get('copyright');
$show_sidebar = ($this->countModules('position-5') || $this->countModules('position-6') || $this->countModules('position-7')) ? '1' : '0';
$sidebar_width = $this->params->get('sidebarWidth');

switch($sidebar_width) {
  case 140:
    $grid = 12;
    $sidebar_cols = 2;
    $content_cols = 10;
    break;	
  case 160:
    $grid = 16;
    $sidebar_cols = 3;
    $content_cols = 13;
    break;
  case 220:
    $grid = 12;
    $sidebar_cols = 3;
    $content_cols = 9;
    break;
  case 280:
    $grid = 16;
    $sidebar_cols = 5;
    $content_cols = 11;
    break;
  case 300:
    $grid = 12;
    $sidebar_cols = 4;
    $content_cols = 8;
    break;
  case 340:
    $grid = 16;
    $sidebar_cols = 6;
    $content_cols = 10;
    break;
  case 380:
    $grid = 12;
    $sidebar_cols = 5;
    $content_cols = 7;
    break;
  case 400:
    $grid = 16;
    $sidebar_cols = 7;
    $content_cols = 9;
    break;
  case 460:
    $grid = 12;
    $sidebar_cols = 6;
    $content_cols = 6;
    break;
  default:
    $grid = 12;
    $sidebar_cols = 3;
    $content_cols = 9;
    break;
}

if ($show_sidebar) {
	if ($this->params->get('sidebarPosition') == 'left') {
		$content_position = 'grid_' . $content_cols . ' push_' . $sidebar_cols;
		$sidebar_position = 'grid_' . $sidebar_cols . ' pull_' . $content_cols;
	} else {
		$content_position = 'grid_' . $content_cols;
		$sidebar_position = 'grid_' . $sidebar_cols;
	}
} else {
	$content_position = 'grid_' . $grid;
}

?>