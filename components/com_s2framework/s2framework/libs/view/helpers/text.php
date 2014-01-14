<?php
/**
 * S2Framework
 * Copyright (C) 2010-2012 ClickFWD LLC
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
**/


defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class TextHelper extends MyHelper
{

	function truncateWords( $text, $words ) {

		$text_tmp = preg_replace('/{mosimage\s*.*?}/i','',$text);

		if ($words > 0 or $words == '') {

			$text_tmp = $this->strip_html_tags($text_tmp);

		  	$wordCount = $words;

			mb_regex_encoding('UTF-8');

			mb_internal_encoding('UTF-8');

			$parts = mb_split("\s", $text_tmp);

		    $wordArray = array_splice($parts,0,$wordCount--);

			if ( $wordCount > sizeOf( $wordArray) ) {
				return $text_tmp;
			}

			return implode( ' ', $wordArray ) . '...';

		} else {

			return $text_tmp;

		}

	} // End trimWords

	/**
	 * Remove HTML tags, including invisible text such as style and
	 * script code, and embedded objects.  Add line breaks around
	 * block-level tags to prevent word joining after tag removal.
	 */
	function strip_html_tags( $text )
	{
	    $text = preg_replace(
	        array(
	          // Remove invisible content
	            '@<head[^>]*?>.*?</head>@siu',
	            '@<style[^>]*?>.*?</style>@siu',
	            '@<script[^>]*?.*?</script>@siu',
	            '@<object[^>]*?.*?</object>@siu',
	            '@<embed[^>]*?.*?</embed>@siu',
	            '@<applet[^>]*?.*?</applet>@siu',
	            '@<noframes[^>]*?.*?</noframes>@siu',
	            '@<noscript[^>]*?.*?</noscript>@siu',
	            '@<noembed[^>]*?.*?</noembed>@siu',
	          // Add line breaks before and after blocks
	            '@</?((address)|(blockquote)|(center)|(del))@iu',
	            '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu',
	            '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu',
	            '@</?((table)|(th)|(td)|(caption))@iu',
	            '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu',
	            '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu',
	            '@</?((frameset)|(frame)|(iframe))@iu',
	        ),
	        array(
	            ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
	            "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0",
	            "\n\$0", "\n\$0",
	        ),
	        $text );
	    return strip_tags( $text );
	}

	/**
	 * Text Helper
	 *
	 * Text manipulations: Highlight, excerpt, truncate, strip of links, convert email addresses to mailto: links...
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
	 */

/**
 * Truncates text.
 *
 * Cuts a string to the length of $length and replaces the last characters
 * with the ending if the text is longer than length.
 *
 * @param string  $text String to truncate.
 * @param integer $length Length of returned string, including ellipsis.
 * @param mixed $ending If string, will be used as Ending and appended to the trimmed string. Can also be an associative array that can contain the last three params of this method.
 * @param boolean $exact If false, $text will not be cut mid-word
 * @param boolean $considerHtml If true, HTML tags would be handled correctly
 * @return string Trimmed string.
 */
    function truncate($text, $length = 100, $ending = '...', $exact = true, $considerHtml = false) {
        if (is_array($ending)) {
            extract($ending);
        }
        if ($considerHtml) {
            if (mb_strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }
            $totalLength = mb_strlen($ending);
            $openTags = array();
            $truncate = '';
            preg_match_all('/(<\/?([\w+]+)[^>]*>)?([^<>]*)/', $text, $tags, PREG_SET_ORDER);
            foreach ($tags as $tag) {
                if (!preg_match('/img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param/s', $tag[2])) {
                    if (preg_match('/<[\w]+[^>]*>/s', $tag[0])) {
                        array_unshift($openTags, $tag[2]);
                    } else if (preg_match('/<\/([\w]+)[^>]*>/s', $tag[0], $closeTag)) {
                        $pos = array_search($closeTag[1], $openTags);
                        if ($pos !== false) {
                            array_splice($openTags, $pos, 1);
                        }
                    }
                }
                $truncate .= $tag[1];

                $contentLength = mb_strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $tag[3]));
                if ($contentLength + $totalLength > $length) {
                    $left = $length - $totalLength;
                    $entitiesLength = 0;
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $tag[3], $entities, PREG_OFFSET_CAPTURE)) {
                        foreach ($entities[0] as $entity) {
                            if ($entity[1] + 1 - $entitiesLength <= $left) {
                                $left--;
                                $entitiesLength += mb_strlen($entity[0]);
                            } else {
                                break;
                            }
                        }
                    }

                    $truncate .= mb_substr($tag[3], 0 , $left + $entitiesLength);
                    break;
                } else {
                    $truncate .= $tag[3];
                    $totalLength += $contentLength;
                }
                if ($totalLength >= $length) {
                    break;
                }
            }

        } else {
            if (mb_strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = mb_substr($text, 0, $length - strlen($ending));
            }
        }

        if (!$exact) {
            $spacepos = mb_strrpos($truncate, ' ');
            if (isset($spacepos)) {
                if ($considerHtml) {
                    $bits = mb_substr($truncate, $spacepos);
                    preg_match_all('/<\/([a-z]+)>/', $bits, $droppedTags, PREG_SET_ORDER);
                    if (!empty($droppedTags)) {
                        foreach ($droppedTags as $closingTag) {
                            if (!in_array($closingTag[1], $openTags)) {
                                array_unshift($openTags, $closingTag[1]);
                            }
                        }
                    }
                }
                $truncate = mb_substr($truncate, 0, $spacepos);
            }
        }

        $truncate .= $ending;

        if ($considerHtml) {
            foreach ($openTags as $tag) {
                $truncate .= '</'.$tag.'>';
            }
        }

        return $truncate;
    }
/**
 * Alias for truncate().
 *
 * @see TextHelper::truncate()
 * @access public
 */
    function trim() {
        $args = func_get_args();
        return call_user_func_array(array(&$this, 'truncate'), $args);
    }

	/**
	 * Extracts an excerpt from the text surrounding the phrase with a number of characters on each side determined by radius.
	 *
	 * @param string $text String to search the phrase in
	 * @param string $phrase Phrase that will be searched for
	 * @param integer $radius The amount of characters that will be returned on each side of the founded phrase
	 * @param string $ending Ending that will be appended
	 * @return string Modified string
	 * @access public
	 */
	function excerpt($text, $phrase, $radius = 100, $ending = "...") {
		if (empty($text) or empty($phrase)) {
			return $this->truncate($text, $radius * 2, $ending);
		}

		if ($radius < strlen($phrase)) {
			$radius = strlen($phrase);
		}

		$pos = strpos(strtolower($text), strtolower($phrase));
		$startPos = ife($pos <= $radius, 0, $pos - $radius);
		$endPos = ife($pos + strlen($phrase) + $radius >= strlen($text), strlen($text), $pos + strlen($phrase) + $radius);
		$excerpt = substr($text, $startPos, $endPos - $startPos);

		if ($startPos != 0) {
			$excerpt = substr_replace($excerpt, $ending, 0, strlen($phrase));
		}

		if ($endPos != strlen($text)) {
			$excerpt = substr_replace($excerpt, $ending, -strlen($phrase));
		}

		return $excerpt;
	}
	/**
	 * Creates a comma separated list where the last two items are joined with 'and', forming natural English
	 *
	 * @param array $list The list to be joined
	 * @return string
	 * @access public
	 */
	function toList($list, $and = 'and') {
		$r = '';
		$c = count($list) - 1;
		foreach ($list as $i => $item) {
			$r .= $item;
			if ($c > 0 && $i < $c)
			{
				$r .= ($i < $c - 1 ? ', ' : " {$and} ");
			}
		}
		return $r;
	}

	/**
	 * Strips given text of all links (<a href=....)
	 *
	 * @param string $text Text
	 * @return string The text without links
	 * @access public
	 */
	function stripLinks($text) {
		return preg_replace('|<a\s+[^>]+>|im', '', preg_replace('|<\/a>|im', '', $text));
	}

	/**
	 * Highlights a given phrase in a text. You can specify any expression in highlighter that
	 * may include the \1 expression to include the $phrase found.
	 *
	 * @param string $text Text to search the phrase in
	 * @param string $phrase The phrase that will be searched
	 * @param string $highlighter The piece of html with that the phrase will be highlighted
	 * @return string The highlighted text
	 * @access public
	 */
	function highlight($text, $phrase, $highlighter = '<span class="highlight">\1</span>') {
		if (empty($phrase)) {
			return $text;
		}

		if (is_array($phrase)) {
			$replace = array();
			$with = array();

			foreach ($phrase as $key => $value) {
				$key = $value;
				$value = $highlighter;

				$replace[] = '|(' . $key . ')|i';
				$with[] = empty($value) ? $highlighter : $value;
			}

			return preg_replace($replace, $with, $text);
		} else {
			return preg_replace("|({$phrase})|i", $highlighter, $text);
		}
	}

}