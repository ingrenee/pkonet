<?php header("Content-type: text/css; charset: UTF-8");

$rwd = $_GET['rwd'];
$mw = $_GET['mw'];
$sf = $_GET['sf'];
$ps = $_GET['ps'];
$psw = $_GET['psw'];
$pst = $_GET['pst'];
$pss = $_GET['pss'];
$ssw = $_GET['ssw'];
$sst = $_GET['sst'];
$sss = $_GET['sss'];

$grid_columns = 12;
$column_width = 62;
$gutter_width = 20;
$grid_row_width = ($grid_columns * $column_width) + ($gutter_width * ($grid_columns - 1));

$column_width_tablets = 44;
$gutter_width_tablets = 20;
$grid_row_width_tablets = ($grid_columns * $column_width_tablets) + ($gutter_width_tablets * ($grid_columns - 1));

$column_width_large = 80;
$gutter_width_large = 20;
$grid_row_width_large = ($grid_columns * $column_width_large) + ($gutter_width_large * ($grid_columns - 1));


// Calculate dimensions of main content and component area


if ($sf == 1) {

  if ($ps == 1) {

    $mcFixedWidth = $grid_row_width - ($gutter_width + $psw);
    $mcFixedWidthTablets = $grid_row_width_tablets - ($gutter_width_tablets + $psw);
    $mcFixedWidthLarge = $grid_row_width_large - ($gutter_width_large + $psw);

  } else {

    $mcFixedWidth = $grid_row_width;
    $mcFixedWidthTablets = $grid_row_width_tablets;
    $mcFixedWidthLarge = $grid_row_width_large;

  }

  $caFixedWidth = $mcFixedWidth - ($gutter_width + $ssw);
  $caFixedWidthTablets = $mcFixedWidthTablets - ($gutter_width_tablets + $ssw);
  $caFixedWidthLarge = $mcFixedWidthLarge - ($gutter_width_large + $ssw);

}

?>

/* Grid system */

.row {
  margin-left: -<?php echo $gutter_width; ?>px;
  *zoom: 1;
}
.row:before,
.row:after {
  display: table;
  content: "";
}
.row:after {
  clear: both;
}
.row > [class*="col"] {
  float: left;
  margin-left: <?php echo $gutter_width; ?>px;
}
.row > [class*="push"],
.row > [class*="pull"] {
  position: relative;
}
.container {
  width: <?php echo $grid_row_width; ?>px;
}

<?php for ($i = $grid_columns; $i > 0 ; $i--): ?>
.row > .col<?php echo $i; ?> {
  width: <?php echo ($i * $column_width) + ($gutter_width * ($i - 1)); ?>px;
}
<?php endfor; ?>

<?php for ($i = $grid_columns; $i > 0 ; $i--): ?>
.row > .push<?php echo $i; ?> {
  left: <?php echo (($i * $column_width) + ($gutter_width * ($i + 1)) - $gutter_width); ?>px;
}
<?php endfor; ?>

<?php for ($i = $grid_columns; $i > 0 ; $i--): ?>
.row > .pull<?php echo $i; ?> {
  left: -<?php echo (($i * $column_width) + ($gutter_width * ($i + 1)) - $gutter_width); ?>px;
}
<?php endfor; ?>

<?php if ($sf == 1): ?>
.col-ps-fixed {
  width: <?php echo $psw; ?>px;
}

.col-ss-fixed {
  width: <?php echo $ssw; ?>px;
}

.col-mc-fixed {
  width: <?php echo $mcFixedWidth; ?>px;
}

.col-ca-fixed {
  width: <?php echo $caFixedWidth; ?>px;
}

.push-mc-fixed {
  left: <?php echo $gutter_width + $psw; ?>px;
}

.push-ca-fixed {
  left: <?php echo $gutter_width + $ssw; ?>px;
}

.pull-ps-fixed {
  left: -<?php echo $mcFixedWidth + $gutter_width; ?>px;
}

.pull-ss-fixed {
  left: -<?php echo $caFixedWidth + $gutter_width; ?>px;
}
<?php endif; ?>

<?php if ($rwd == 1): ?>


/* Utility classes */

.visible-phone     { display: none !important; }
.visible-tablet    { display: none !important; }
.hidden-phone      { }
.hidden-tablet     { }
.hidden-desktop    { display: none !important; }
.visible-desktop   { display: inherit !important; }


<?php if ($mw == 1200): ?>

/* Large desktops */

@media (min-width: 1200px) {
  .row {
    margin-left: -<?php echo $gutter_width_large; ?>px;
    *zoom: 1;
  }
  .row:before,
  .row:after {
    display: table;
    content: "";
  }
  .row:after {
    clear: both;
  }
  .row > [class*="col"] {
    float: left;
    margin-left: <?php echo $gutter_width_large; ?>px;
  }
  .container {
    width: <?php echo $grid_row_width_large; ?>px;
  }

<?php for ($i = $grid_columns; $i > 0 ; $i--): ?>
  .row > .col<?php echo $i; ?> {
    width: <?php echo ($i * $column_width_large) + ($gutter_width_large * ($i - 1)); ?>px;
  }
<?php endfor; ?>

<?php for ($i = $grid_columns; $i > 0 ; $i--): ?>
  .row > .push<?php echo $i; ?> {
    left: <?php echo (($i * $column_width_large) + ($gutter_width_large * ($i + 1)) - $gutter_width_large); ?>px;
  }
<?php endfor; ?>

<?php for ($i = $grid_columns; $i > 0 ; $i--): ?>
  .row > .pull<?php echo $i; ?> {
    left: -<?php echo (($i * $column_width_large) + ($gutter_width_large * ($i + 1)) - $gutter_width_large); ?>px;
  }
<?php endfor; ?>

  <?php if ($sf == 1): ?>
  .col-mc-fixed {
    width: <?php echo $mcFixedWidthLarge; ?>px;
  }

  .col-ca-fixed {
    width: <?php echo $caFixedWidthLarge; ?>px;
  }

  .push-mc-fixed {
    left: <?php echo $gutter_width_large + $psw; ?>px;
  }

  .push-ca-fixed {
    left: <?php echo $gutter_width_large + $ssw; ?>px;
  }

  .pull-ps-fixed {
    left: -<?php echo $mcFixedWidthLarge + $gutter_width_large; ?>px;
  }

  .pull-ss-fixed {
    left: -<?php echo $caFixedWidthLarge + $gutter_width_large; ?>px;
  }
  <?php endif; ?>
}
<?php endif; ?>


/* Tablets */

@media (min-width: 768px) and (max-width: 979px) {
  .row {
    margin-left: -<?php echo $gutter_width_tablets; ?>px;
    *zoom: 1;
  }
  .row:before,
  .row:after {
    display: table;
    content: "";
  }
  .row:after {
    clear: both;
  }
  .row > [class*="col"] {
    float: left;
    margin-left: <?php echo $gutter_width_tablets; ?>px;
  }
  .container {
    width: <?php echo $grid_row_width_tablets; ?>px;
  }

<?php for ($i = $grid_columns; $i > 0 ; $i--): ?>
  .row > .col<?php echo $i; ?> {
    width: <?php echo ($i * $column_width_tablets) + ($gutter_width_tablets * ($i - 1)); ?>px;
  }
<?php endfor; ?>

<?php for ($i = $grid_columns; $i > 0 ; $i--): ?>
  .row > .push<?php echo $i; ?> {
    left: <?php echo (($i * $column_width_tablets) + ($gutter_width_tablets * ($i + 1)) - $gutter_width_tablets); ?>px;
  }
<?php endfor; ?>

<?php for ($i = $grid_columns; $i > 0 ; $i--): ?>
  .row > .pull<?php echo $i; ?> {
    left: -<?php echo (($i * $column_width_tablets) + ($gutter_width_tablets * ($i + 1)) - $gutter_width_tablets); ?>px;
  }
<?php endfor; ?>

  <?php if ($sf == 1): ?>
  .col-mc-fixed {
    width: <?php echo $mcFixedWidthTablets; ?>px;
  }

  .col-ca-fixed {
    width: <?php echo $caFixedWidthTablets; ?>px;
  }

  .push-mc-fixed {
    left: <?php echo $gutter_width_tablets + $psw; ?>px;
  }

  .push-ca-fixed {
    left: <?php echo $gutter_width_tablets + $ssw; ?>px;
  }

  .pull-ps-fixed {
    left: -<?php echo $mcFixedWidthTablets + $gutter_width_tablets; ?>px;
  }

  .pull-ss-fixed {
    left: -<?php echo $caFixedWidthTablets + $gutter_width_tablets; ?>px;
  }
  <?php endif; ?>

  .main-content .row {
    margin-left: 0;
  }

  .main-content .row > [class*="col"] {
    float: none;
    display: block;
    width: auto;
    margin-left: 0;
    left: 0;
  }

  <?php if($pst == 'showBelow'): ?>
  .header-wrapper,
  .showcase-top-wrapper,
  .content-wrapper,
  .showcase-bottom-wrapper,
  .footer-wrapper {
    padding-left: 12px;
    padding-right: 12px;
  }
  .container {
    width: auto;
    padding: 0;
    border: 0;
  }
  .row {
    margin-left: 0;
  }
  .row > [class*="col"] {
    float: none;
    display: block;
    width: auto;
    margin-left: 0;
    left: 0;
  }
  <?php elseif($pst == 'hide'): ?>
  .header-wrapper,
  .showcase-top-wrapper,
  .content-wrapper,
  .showcase-bottom-wrapper,
  .footer-wrapper {
    padding-left: 12px;
    padding-right: 12px;
  }
  .container {
    width: auto;
    padding: 0;
    border: 0;
  }
  .row {
    margin-left: 0;
  }
  .row > [class*="col"] {
    float: none;
    display: block;
    width: auto;
    margin-left: 0;
    left: 0;
  }
  .sidebar-primary {
    display: none !important;
  }
  <?php endif; ?>
  <?php if($sst == 'hide'): ?>
  .sidebar-secondary {
    display: none !important;
  }
  <?php endif; ?>


  /* Utility classes */

  .hidden-desktop    { display: inherit !important; }
  .visible-desktop   { display: none !important ; }
  .visible-tablet    { display: inherit !important; }
  .hidden-tablet     { display: none !important; }
}


/* Small tablets and smartphones */

@media (max-width: 767px) {
  .header-wrapper,
  .showcase-top-wrapper,
  .content-wrapper,
  .showcase-bottom-wrapper,
  .footer-wrapper {
    padding-left: 12px;
    padding-right: 12px;
  }
  .header-wrapper {
    text-align: center;
  }
  .container {
    width: auto;
    padding: 0;
    border: 0;
  }
  .row {
    margin-left: 0;
  }
  .row > [class*="col"] {
    float: none;
    display: block;
    width: auto;
    margin-left: 0;
    left: 0;
  }
  [class^="menu-horiz-"] .menu-module,
  [class^="menu-horiz-"] .menu-module .mod-container {
    width: auto !important;
    float: none !important;
  }
  [class^="menu-horiz-"] .menu-module .mod-container {
    margin-bottom: 30px !important;
  }
  <?php if($pss == 'hide'): ?>
  .sidebar-primary {
    display: none !important;
  }
  <?php endif; ?>
  <?php if($sss == 'hide'): ?>
  .sidebar-secondary {
    display: none !important;
  }
  <?php endif; ?>
  .position-0 div.mod-inner,
  .position-15 div.mod-inner {
    float: none;
  }
  .position-0 ul[class^="menu"] {
    margin-top: 0;
  }

  /* Utility classes */

  .hidden-desktop    { display: inherit !important; }
  .visible-desktop   { display: none !important; }
  .visible-phone     { display: inherit !important; }
  .hidden-phone      { display: none !important; }
}

/* Smartphones */

@media (max-width: 480px) {
  .header-wrapper,
  .showcase-top-wrapper,
  .content-wrapper,
  .showcase-bottom-wrapper,
  .footer-wrapper {
    padding-left: 8px;
    padding-right: 8px;
  }
  body.header-fixed {
    padding: 0 !important;
  }
  .header-wrapper.fixed {
    position: static !important;
    -moz-box-sizing: border-box;
    -webkit-box-sizing: border-box;
    box-sizing: border-box;
  }
  h1 {
    font-size: 26px !important;
  }

  h2 {
    font-size: 22px !important;
  }
  a.logo:link,
  a.logo:visited {
    font-size: 30px !important;
  }
}

<?php else: ?>

.header-wrapper,
.showcase-top-wrapper,
.content-wrapper,
.showcase-bottom-wrapper,
.footer-wrapper {
  padding-left: 20px;
  padding-right: 20px;
  min-width: 964px;
}

<?php endif; ?>
