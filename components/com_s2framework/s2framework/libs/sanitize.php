<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/* SVN FILE: $Id: sanitize.php 6311 2008-01-02 06:33:52Z phpnut $ */
/**
 * Washes strings from unwanted noise.
 *
 * Helpful methods to make unsafe strings usable.
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2008, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @modified by: ClickFWD LLC
 *
 */

/**
 * Data Sanitization.
 *
 * Removal of alpahnumeric characters, SQL-safe slash-added strings, HTML-friendly strings,
 * and all of the above on arrays.
 *
 */
class Sanitize {

	static function getVar($var,$key,$default=null){

		if(is_array($var) && isset($var[$key])) {

			return $var[$key];

		} elseif(is_object($var) && isset($var->{$key})) {

			return $var->{$key};

		} else {

			return $default;

		}

	}

	static function getString($var,$key,$default=null) {

		return (string) Sanitize::getVar($var,$key,$default);

	}

	static function getInt($var,$key,$default=null) {

		return (int) Sanitize::getVar($var,$key,$default);

	}

    static function getBool($var,$key,$default=null) {

        return (bool) Sanitize::getVar($var,$key,$default);

    }

	static function getFloat($var,$key,$default=null) {

		return (float) Sanitize::getVar($var,$key,$default);

	}

/**
 * Removes any non-alphanumeric characters.
 *
 * @param string $string String to sanitize
 * @return string Sanitized string
 * @access public
 * @static
 */
	static function paranoid($string, $allowed = array()) {
		$allow = null;
		if (!empty($allowed)) {
			foreach ($allowed as $value) {
				$allow .= "\\$value";
			}
		}

		if (is_array($string)) {
			$cleaned = array();
			foreach ($string as $key => $clean) {
				$cleaned[$key] = preg_replace("/[^{$allow}a-zA-Z0-9]/", '', $clean);
			}
		} else {
			$cleaned = preg_replace("/[^{$allow}a-zA-Z0-9]/", '', $string);
		}
		return $cleaned;
	}


/**
 * Returns given string safe for display as HTML. Renders entities.
 *
 * @param string $string String from where to strip tags
 * @param boolean $remove If true, the string is stripped of all HTML tags
 * @return string Sanitized string
 * @access public
 * @static
 */
	static function html($var, $key, $default = null, $remove = false) {

		$string = Sanitize::getVar($var, $key, $default);

		if($string) {

			if ($remove) {
				$string = strip_tags($string);
			} else {
				$patterns = array("/\&/", "/%/", "/</", "/>/", '/"/', "/'/", "/\(/", "/\)/", "/\+/", "/-/");
				$replacements = array("&amp;", "&#37;", "&lt;", "&gt;", "&quot;", "&#39;", "&#40;", "&#41;", "&#43;", "&#45;");
				$string = preg_replace($patterns, $replacements, $string);
			}
		}

		return $string;
	}

/**
 * Returns given string safe for display as HTML. Renders entities.
 *
 * @param string $string String from where to strip tags
 * @param boolean $remove If true, the string is stripped of all HTML tags
 * @return string Sanitized string
 * @access public
 * @static
 */
	static function htmlClean($string, $remove = true) {

		if ($remove) {
			$string = strip_tags($string);

		} else {
			$patterns = array("/\&/", "/%/", "/</", "/>/", '/"/', "/'/", "/\(/", "/\)/", "/\+/", "/-/");
			$replacements = array("&amp;", "&#37;", "&lt;", "&gt;", "&quot;", "&#39;", "&#40;", "&#41;", "&#43;", "&#45;");
			$string = preg_replace($patterns, $replacements, $string);
		}

		return $string;
	}

	static function stripEscape($param) {

		if (!is_array($param) || empty($param)) {
			if (is_bool($param)) {
				return $param;
			}

			$return = preg_replace('/^[\\t ]*(?:-!)+/', '', $param);
			return $return;
		}

		foreach ($param as $key => $value) {
			if (!is_array($value)) {
				$return[$key] = preg_replace('/^[\\t ]*(?:-!)+/', '', $value);
			} elseif($value) {
				foreach ($value as $array => $string) {
					$return[$key][$array] = Sanitize::stripEscape($string);
				}
			}
		}

		if(isset($return)) {
			return $return;
		} else {
			return $param;
		}
	}

/**
 * Strips extra whitespace from output
 *
 * @param string $str String to sanitize
 * @access public
 * @static
 */
	static function stripWhitespace($str) {
		$r = preg_replace('/[\n\r\t]+/', ' ', $str);
		return preg_replace('/\s{2,}/', ' ', $r);
	}
/**
 * Strips image tags from output
 *
 * @param string $str String to sanitize
 * @access public
 * @static
 */
	static function stripImages($str) {
		$str = preg_replace('/(<a[^>]*>)(<img[^>]+alt=")([^"]*)("[^>]*>)(<\/a>)/i', '', $str); // $1$3$5<br />
		$str = preg_replace('/(<img[^>]+alt=")([^"]*)("[^>]*>)/i', '', $str); // $2<br />
		$str = preg_replace('/<img[^>]*>/i', '', $str);
		return $str;
	}
/**
 * Strips scripts and stylesheets from output
 *
 * @param string $str String to sanitize
 * @access public
 * @static
 */
	static function stripScripts($str)
    {
        S2App::import('Vendor','htmlawed'.DS.'htmlawed');
        return htmLawed($str,array('safe'=>1));
//		return preg_replace('/(<link[^>]+rel="[^"]*stylesheet"[^>]*>)|<script[^>]*>.*?<\/script>|<style[^>]*>.*?<\/style>|<!--.*?-->/i', '', $str);
//		return preg_replace('/(<link[^>]+rel="[^"]*stylesheet"[^>]*>|style="[^"]*")|<script[^>]*>.*?<\/script>|<style[^>]*>.*?<\/style>|<!--.*?-->/i', '', $str);
//		return preg_replace('/(<link[^>]+rel="[^"]*stylesheet"[^>]*>|<img[^>]*>|style="[^"]*")|<script[^>]*>.*?<\/script>|<style[^>]*>.*?<\/style>|<!--.*?-->/i', '', $str);
	}
/**
 * Strips extra whitespace, images, scripts and stylesheets from output
 *
 * @param string $str String to sanitize
 * @access public
 */
	static function stripAll($var, $key, $default = null) {

		$str = Sanitize::getVar($var, $key, $default);

		if($str) {
//			$str = Sanitize::stripWhitespace($str); // This one removes line breaks \n

			$str = Sanitize::stripImages($str);

			$str = Sanitize::stripScripts($str);

			$str = stripslashes($str);
		}

		return $str;
	}
/**
 * Strips the specified tags from output. First parameter is string from
 * where to remove tags. All subsequent parameters are tags.
 *
 * @param string $str String to sanitize
 * @param string $tag Tag to remove (add more parameters as needed)
 * @access public
 * @static
 */
	static function stripTags() {
		$params = func_get_args();
		$str = $params[0];

		for ($i = 1; $i < count($params); $i++) {
			$str = preg_replace('/<' . $params[$i] . '[^>]*>/i', '', $str);
			$str = preg_replace('/<\/' . $params[$i] . '[^>]*>/i', '', $str);
		}
		return $str;
	}
/**
 * Sanitizes given array or value for safe input. Use the options to specify
 * the connection to use, and what filters should be applied (with a boolean
 * value). Valid filters: odd_spaces, encode, dollar, carriage, unicode,
 * escape, backslash.
 *
 * @param mixed $data Data to sanitize
 * @param mixed $options If string, DB connection being used, otherwise set of options
 * @return mixed Sanitized data
 * @access public
 * @static
 */
	static function clean($data, $options = array())
    {
		if (empty($data) || is_object($data)) {
			return $data;
		}

		if (is_string($options)) {
			$options = array('connection' => $options);
		} elseif (!is_array($options)) {
			$options = array();
		}

		$options = array_merge(array(
			'connection' => 'default',
			'odd_spaces' => true,
			'html' => true,
			'dollar' => true,
			'carriage' => true,
			'unicode' => true,
			'escape' => false,
			'backslash' => true
		), $options);

		if (is_array($data)) {
			foreach ($data as $key => $val) {
				$data[$key] = Sanitize::clean($val, $options);
			}
			return $data;

		} else {

			if ($options['odd_spaces']) {
				$data = str_replace(chr(0xCA), '', str_replace(' ', ' ', $data));
			}
			if ($options['html']) {
				$data = Sanitize::htmlClean($data);

			}
			if ($options['dollar']) {
				$data = str_replace("\\\$", "$", $data);
			}
			if ($options['carriage']) {
				$data = str_replace("\r", "", $data);
			}

			$data = str_replace("'", "'", str_replace("!", "!", $data));

			if ($options['unicode']) {
				$data = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $data);
			}
			if ($options['escape']) {
				$data = mysql_real_escape_string($data);
			}
			if ($options['backslash']) {
				$data = preg_replace("/\\\(?!&amp;#|\?#)/", "\\", $data);
			}
			return $data;
		}
	}

    /**
    * Adapted from sluggable behaviour in cake
    *
    * @param mixed $string
    * @return string
    */
    static function translate($string)
    {
        $translations = array(
                    // Decompositions for Latin-1 Supplement
                    chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
                    chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
                    chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
                    chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
                    chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
                    chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
                    chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
                    chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
                    chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
                    chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
                    chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
                    chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
                    chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
                    chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
                    chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
                    chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
                    chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
                    chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
                    chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
                    chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
                    chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
                    chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
                    chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
                    chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
                    chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
                    chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
                    chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
                    chr(195).chr(191) => 'y',
                    // Decompositions for Latin Extended-A
                    chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
                    chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
                    chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
                    chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
                    chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
                    chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
                    chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
                    chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
                    chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
                    chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
                    chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
                    chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
                    chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
                    chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
                    chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
                    chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
                    chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
                    chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
                    chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
                    chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
                    chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
                    chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
                    chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
                    chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
                    chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
                    chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
                    chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
                    chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
                    chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
                    chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
                    chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
                    chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
                    chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
                    chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
                    chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
                    chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
                    chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
                    chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
                    chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
                    chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
                    chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
                    chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
                    chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
                    chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
                    chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
                    chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
                    chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
                    chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
                    chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
                    chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
                    chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
                    chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
                    chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
                    chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
                    chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
                    chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
                    chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
                    chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
                    chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
                    chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
                    chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
                    chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
                    chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
                    chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
                    // Euro Sign
                    chr(226).chr(130).chr(172) => 'E'
        );
        return strtr($string,$translations);
    }
}