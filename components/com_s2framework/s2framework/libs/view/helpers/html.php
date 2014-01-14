<?php
/**
 * Html Helper class file.
 *
 * Simplifies the construction of HTML elements.
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *								1785 E. Sahara Avenue, Suite 490-204
 *								Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 *
 * @modified	by ClickFWD LLC
 */

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class HtmlHelper extends MyHelper
{
	var $viewSuffix = '';

	var $abs_url = false;

	var $tags = array(
		'metalink' => '<link href="%s" title="%s"%s />',
		'link' => '<a href="%s" %s>%s</a>',
		'mailto' => '<a href="mailto:%s" %s>%s</a>',
		'form' => '<form %s>',
		'formend' => '</form>',
		'input' => '<input name="%s" %s />',
		'email' => '<input type="email" name="%s" %s/>',
		'url' => '<input type="url" name="%s" %s/>',
		'number' => '<input type="number" name="%s" %s/>',
		'text' => '<input type="text" name="%s" %s/>',
		'textarea' => '<textarea name="%s" %s>%s</textarea>',
		'hidden' => '<input type="hidden" name="%s" %s/>',
		'checkbox' => '<input type="checkbox" name="%s" id="%s" %s/>&nbsp;%s',
		'checkboxmultiple' => '<input type="checkbox" name="%s[]" id="%s" %s />&nbsp;%s',
		'radio' => '<input type="radio" name="%s" id="%s" %s />&nbsp;%s',
		'selectstart' => '<select name="%s"%s>',
		'selectmultiplestart' => '<select name="%s[]"%s>',
		'selectempty' => '<option value=""%s>&nbsp;</option>',
		'selectoption' => '<option value="%s"%s>%s</option>',
		'selectend' => '</select>',
		'optiongroup' => '<optgroup label="%s"%s>',
		'optiongroupend' => '</optgroup>',
		'password' => '<input type="password" name="%s" %s />',
		'file' => '<input type="file" name="%s" %s/>',
		'file_no_model' => '<input type="file" name="%s" %s />',
		'submit' => '<input type="submit" %s/>',
		'submitimage' => '<input type="image" src="%s" %s />',
		'button' => '<button %s>%s</button>',
		'imagebutton' => '<input type="image" %s />',
		'image' => '<img src="%s" %s />',
		'tableheader' => '<th%s>%s</th>',
		'tableheaderrow' => '<tr%s>%s</tr>',
		'tablecell' => '<td%s>%s</td>',
		'tablerow' => '<tr%s>%s</tr>',
		'block' => '<div%s>%s</div>',
		'blockstart' => '<div%s>',
		'blockend' => '</div>',
		'para' => '<p%s>%s</p>',
		'parastart' => '<p%s>',
		'label' => '<label for="%s"%s>%s</label>',
        'label_no_for'=>'<label %s>%s</label>',
		'fieldset' => '<fieldset %s><legend>%s</legend>%s</fieldset>',
		'fieldsetstart' => '<fieldset><legend>%s</legend>',
		'fieldsetend' => '</fieldset>',
		'legend' => '<legend>%s</legend>',
		'css' => '<link rel="%s" type="text/css" href="%s" %s/>',
		'style' => '<style type="text/css" %s>%s</style>',
		'charset' => '<meta http-equiv="Content-Type" content="text/html; charset=%s" />',
		'javascriptlink' => '<script type="text/javascript" src="%s"></script>',
		'javascriptcode' => '<script type="text/javascript">%s</script>',
		'ul' => '<ul%s>%s</ul>',
		'ol' => '<ol%s>%s</ol>',
		'li' => '<li%s>%s</li>'
	);

	function css($files, $options = array())
    {
    	$default = array('inline'=>false,'duress'=>false,'params'=>'');

    	$options = array_merge($default,$options);

    	extract($options);

    	if($params != '') $params = '?' . $params;

		// Register in header to prevent duplicates
        $registry = ClassRegistry::getObject('css');

		if (is_array($files)) {

			$out = '';

			foreach ($files as $i) {

				if(!isset($registry[$i])) {

					$out .= "\n\t" . $this->css($i, $options);

				}
			}

			if ($out != '' && $out != '/' && $inline)  {

				echo $out . "\n";
			}

			return;
		}

        ClassRegistry::setObject($files,1,'css');

        // Create minify script url
        $no_ext = str_replace(array(MVC_ADMIN._DS,'.css',_DS),array('','',DS),$files);

        $ThemeFolder = false!==strpos($files,MVC_ADMIN) ? 'AdminTheme' : 'Theme';

        $cssPath = $this->locateThemeFile('theme_css',$no_ext,'.css',$ThemeFolder);

        if($cssPath != '' && $cssPath != '/') {

            $cssUrl = pathToUrl($cssPath,true) . $params;

            $rel = 'stylesheet';

            $out = sprintf($this->tags['css'], $rel, $cssUrl, '');

            cmsFramework::addScript($out,$inline);
        }
	}

	function js($files, $options = array())
    {
    	$default = array('inline'=>false,'duress'=>false,'params'=>'','minified'=>false,'absUrls'=>array());

    	$options = array_insert($default,$options);

    	extract($options);

		// Register in header to prevent duplicates
        $registry = ClassRegistry::getObject('javascript');

		if (is_array($files)) {
			$out = '';

			foreach ($files as $i) {

				if($duress || !isset($registry[$i])) {

					$out .= "\n\t" . $this->js($i, $options);
				}
			}

			if ($out != '' && $out != '/' && $inline) {

				echo $out . "\n";
			}

			return;
		}

        ClassRegistry::setObject($files,1,'javascript');

        $relative = in_array($files,$absUrls) ? false : true;

        if(false!==strpos($files,MVC_ADMIN)) { // Automatic routing to admin path

            $files = str_replace(MVC_ADMIN .'/', '', $files);

            $jsUrl = $this->locateScript($files,array('admin'=>true,'relative'=>$relative,'minified'=>$minified,'params'=>$params));
        }
        else {

            $jsUrl = $this->locateScript($files,array('admin'=>false,'relative'=>$relative,'minified'=>$minified,'params'=>$params));
        }

        if($jsUrl != '' && $jsUrl != '/' && $jsUrl != '?'.$params)
        {
            $out = sprintf($this->tags['javascriptlink'], $jsUrl);

            cmsFramework::addScript($out, $inline, $duress);
        }
	}

	function getCrumbs($crumbs, $separator = '&raquo;', $startText = false)
	{
		if (count($crumbs)) {

			$out = array();

			if ($startText) {
				$out[] = $this->sefLink($startText, '/');
			}

			foreach ($crumbs as $crumb) {
				if (!empty($crumb['link'])) {
					$out[] = $this->sefLink($crumb['text'], $crumb['link']);
				} else {
					$out[] = $crumb['text'];
				}
			}

			return implode($separator, $out);

		} else {
			return null;
		}
	}

	function link($title, $url = null, $attributes = array())
    {
		if(isset($attributes['sef']) && !$attributes['sef'])
        {
            if(isset($attributes['return_url'])){
                return $url;
            }
            unset($attributes['sef']);
			$attributes = $this->_parseAttributes($attributes);
			return sprintf($this->tags['link'],$url,$attributes,$title);
		}
		return $this->sefLink($title, $url, $attributes);
	}

	function sefLink($title, $url = null, $attributes = array())
    {
		$url = str_replace('{_PARAM_CHAR}',_PARAM_CHAR,$url);

		if(Sanitize::getBool($attributes,'abs_url')) {

			$this->abs_url = true;

			unset($attributes['abs_url']);
		}

		$sef_url = cmsFramework::route($url);

		if($this->abs_url) {

			$sef_url = cmsFramework::makeAbsUrl($sef_url);
		}

        if(isset($attributes['return_url'])){

            return $sef_url;
        }

		$attributes = $this->_parseAttributes($attributes);

        return sprintf($this->tags['link'],$sef_url,$attributes,$title);
	}

	function image($src,$attributes = array()) {
		$attributes = $this->_parseAttributes($attributes);
		return sprintf($this->tags['image'],$src,$attributes);
	}

	function div($class = null, $text = null, $attributes = array()) {

		if ($class != null && !empty($class)) {
			$attributes['class'] = $class;
		}
		if ($text === null) {
			$tag = 'blockstart';
		} else {
			$tag = 'block';
		}
		return $this->output(sprintf($this->tags[$tag], $this->_parseAttributes($attributes), $text));
	}
}