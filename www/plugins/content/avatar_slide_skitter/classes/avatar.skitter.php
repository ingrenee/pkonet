<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
if(class_exists('AvatarSkitterOptions') != true){
	class AvatarSkitterOptions{
		public $source		= 'folder';
		public $path		= '';
		public $auto		= 'true';
		public $interval	= 3000;
		public $slideHeight = '300px';
		public $slideWidth  = '100%';
		public $hideTools  	= 'false';
		public $animation 	= array('random');
		public $maxImages 	= 20;
		public $navStyle 	= 'numbers';
		public $random 		= 'false';
		public $controls 	= 'true';
		public $ctrlPosition= 'rightTop';
		public $focus 		= 'true';
		public $focusPosition = 'rightTop';
		public $progress 	= 'true';
		public $numbersAlign  = 'center';
		public $cr 			= 'true';
		public $responsive	= 'false';
		public $jquery		= 'latest';
	}
} 
?>