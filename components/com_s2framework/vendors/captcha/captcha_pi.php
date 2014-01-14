<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		Rick Ellis
 * @copyright	Copyright (c) 2006, EllisLab, Inc.
 * @license		http://www.codeignitor.com/user_guide/license.html
 * @link		http://www.codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
|==========================================================
| Create Captcha
|==========================================================
|
*/
function create_captcha($data = '', $img_path = '', $img_url = '', $font_path = '')
{
	$defaults = array('word' => '', 'img_path' => '', 'img_url' => '', 'img_width' => '150', 'img_height' => '30', 'font_path' => '', 'expiration' => 7200);

	foreach ($defaults as $key => $val)
	{
		if ( !is_array($data))
		{
			if ( !isset($$key) OR $$key == '')
			{
				$$key = $val;
			}
		}
		else
		{
			$$key = ( ! isset($data[$key])) ? $val : $data[$key];
		}
	}

	if ($img_path == '' OR $img_url == '')
	{
		return FALSE;
	}

	if ( ! @is_dir($img_path))
	{
		return FALSE;
	}

	if ( ! is_writable($img_path))
	{
		return FALSE;
	}

	if ( ! extension_loaded('gd'))
	{
		return FALSE;
	}

	// -----------------------------------
	// Remove old images
	// -----------------------------------

	list($usec, $sec) = explode(" ", microtime());
	$now = ((float)$usec + (float)$sec);

	$current_dir = @opendir($img_path);

	while($filename = @readdir($current_dir))
	{
		if ($filename != "." and $filename != ".." and $filename != "index.html")
		{
			$name = str_replace(".jpg", "", $filename);

			if (($name + $expiration) < $now)
			{
				if(is_file($img_path.$filename)) {

					@unlink($img_path.$filename);
				}
			}
		}
	}

	@closedir($current_dir);

	// -----------------------------------
	// Do we have a "word" yet?
	// -----------------------------------

   if ($word == '')
   {
//		$pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$pool = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

		$str = '';
		for ($i = 0; $i < 5; $i++) // jReviews change to five letters
		{
			$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
		}

		$word = $str;
   }

	// -----------------------------------
	// Determine angle and position
	// -----------------------------------

	$length	= strlen($word);
	$angle	= ($length >= 6) ? rand(-($length-6), ($length-6)) : 0;
	$x_axis	= rand(6, (360/$length)-16);
	$y_axis = ($angle >= 0 ) ? rand($img_height, $img_width) : rand(6, $img_height);

	// -----------------------------------
	// Create image
	// -----------------------------------

	$im = ImageCreate($img_width, $img_height);

	// -----------------------------------
	//  Assign colors
	// -----------------------------------

	$bg_color		= ImageColorAllocate($im, 255, 255, 255);
	$border_color	= ImageColorAllocate($im, 153, 102, 102);
	$text_color		= ImageColorAllocate($im, 111, 111, 111);
	$grid_color		= imagecolorallocate($im, 255, 182, 182);
	$shadow_color	= imagecolorallocate($im, 255, 240, 240);

	// -----------------------------------
	//  Create the rectangle
	// -----------------------------------

	ImageFilledRectangle($im, 0, 0, $img_width, $img_height, $bg_color);

	// -----------------------------------
	//  Create the spiral pattern
	// -----------------------------------

	$theta		= 1;
	$thetac		= 7;
	$radius		= 16;
	$circles	= 20;
	$points		= 32;

	for ($i = 0; $i < ($circles * $points) - 1; $i++)
	{
		$theta = $theta + $thetac;
		$rad = $radius * ($i / $points );
		$x = ($rad * cos($theta)) + $x_axis;
		$y = ($rad * sin($theta)) + $y_axis;
		$theta = $theta + $thetac;
		$rad1 = $radius * (($i + 1) / $points);
		$x1 = ($rad1 * cos($theta)) + $x_axis;
		$y1 = ($rad1 * sin($theta )) + $y_axis;
		imageline($im, $x, $y, $x1, $y1, $grid_color);
		$theta = $theta - $thetac;
	}

	// -----------------------------------
	//  Write the text
	// -----------------------------------

	$use_font = ($font_path != '' AND file_exists($font_path) AND function_exists('imagettftext')) ? TRUE : FALSE;

	if ($use_font == FALSE)
	{
		$font_size = 5;
		$x = rand(5, $img_width-($length*2*$font_size)); // Prevents letters from going to far to the right
//		$x = rand(0, $img_width/($length/3));
		$y = 0;
	}
	else
	{
		$font_size	= 16;
		$x = rand(0, $img_width/($length/1.5));
		$y = $font_size+2;
	}

	for ($i = 0; $i < strlen($word); $i++)
	{
		if ($use_font == FALSE)
		{
			$y = rand(0 , $img_height/2);
			imagestring($im, $font_size, $x, $y, substr($word, $i, 1), $text_color);
			$x += ($font_size*2);
		}
		else
		{
			$y = rand($img_height/2, $img_height-3);
			imagettftext($im, $font_size, $angle, $x, $y, $text_color, $font_path, substr($word, $i, 1));
			$x += $font_size;
		}
	}


	// -----------------------------------
	//  Create the border
	// -----------------------------------

	imagerectangle($im, 0, 0, $img_width-1, $img_height-1, $border_color);

	// -----------------------------------
	//  Generate the image
	// -----------------------------------

	$img_name = $now.'.jpg';

	ImageJPEG($im, $img_path.$img_name);

	// jReviews added id = "captcha"
	$img = "<img id=\"captcha\" src=\"$img_url$img_name\" width=\"$img_width\" height=\"$img_height\" style=\"border:0;\" alt=\" \" />";

	ImageDestroy($im);

	// jReviews added src to the return url
	return array('word' => $word, 'time' => $now, 'image' => $img, 'src' => $img_url.$img_name);
}

?>