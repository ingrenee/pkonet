<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

S2App::import('Helper','form','jreviews');

class PaginatorHelper extends MyHelper {

	var $base_url = null;
	var $items_per_page;
	var $items_total;
	var $current_page;
	var $num_pages;
	var $mid_range = 5;
	var $num_pages_threshold = 10; // After this number the previous/next buttons show up.
	var $return;
	var $return_module;
	var $module_id = 0;
	var $default_limit = 25;
	var $controller = null;
	var $pageUrl = array();

	function __construct()
	{
		$this->current_page = 1;
		$this->items_per_page = (!empty($_GET['limit'])) ? (int) $_GET['limit'] : $this->default_limit;
	}

	function initialize($params = array())
	{
		if (count($params) > 0)
		{
			foreach ($params as $key => $val)
			{
				$this->$key = $val;
			}
		}

		# Construct new route
		if(isset($this->passedArgs) && is_null($this->base_url))
			$this->base_url = cmsFramework::constructRoute($this->passedArgs,array('page','limit','lang'));
	}

	function addPagination($page,$limit)
	{
		$url = '';

		if(cmsFramework::isAdmin()) /* no need for sef urls in admin */
		{
			$url = rtrim($this->base_url,'/') . ($page > 1 ? '/' . 'page'._PARAM_CHAR.$page.'/limit'._PARAM_CHAR.$limit.'/' : '');
		}
		else
		{
			$order = Sanitize::getString($this->params,'order');

			$default_limit = Sanitize::getInt($this->params,'default_limit');

			$url_params = $this->passedArgs;

			unset($url_params['page'],$url_params['Itemid'],$url_params['option'],$url_params['view']);

			if($page == 1
				&& $this->limit == $default_limit
				&& (
					$order == ''
					||
					$order == Sanitize::getString($this->params,'default_order')
				)
				&& empty($url_params)
			) {
				preg_match('/^index.php\?option=com_jreviews&amp;Itemid=[0-9]+/i',$this->base_url,$matches);
				$url = $matches[0];
			}
			else {
				$url = $this->base_url;

				$page > 1 and $url = rtrim($url,'/') . '/' . 'page'._PARAM_CHAR . $page . '/';

				if($this->limit != $default_limit) {
					$url = rtrim($url,'/').'/limit'._PARAM_CHAR.$limit.'/';
				}
			}

			// Remove menu segment from url if page 1 and it' a menu
			if($page == 1 && preg_match('/^(index.php\?option=com_jreviews&amp;Itemid=[0-9]+)(&amp;url=menu\/)$/i',$url,$matches)) {
				$url = $matches[1];
			}

			$url = cmsFramework::route($url);


		}
		return $url;
	}

	function sortArrayByArray($array,$orderArray) {
		$ordered = array();
		foreach($orderArray as $key) {
			if(array_key_exists($key,$array)) {
					$ordered[$key] = $array[$key];
					unset($array[$key]);
			}
		}
		return $ordered + $array;
	}

	function paginate($params)
	{
		$this->return = '';
		$this->initialize($params);

		if(!is_numeric($this->items_per_page) OR $this->items_per_page <= 0) {
			$this->items_per_page = $this->default_limit;
		}

		$this->num_pages = ceil($this->items_total/$this->items_per_page);

		if($this->current_page < 1 || !is_numeric($this->current_page)) $this->current_page = 1;

		if($this->current_page > $this->num_pages) $this->current_page = $this->num_pages;

		$prev_page = $this->current_page-1;

		$next_page = $this->current_page+1;

		# More than num_pages_threshold pages
		if($this->num_pages > $this->num_pages_threshold)
		{
			// PREVIOUS PAGE
			$url = $this->addPagination($prev_page,$this->items_per_page);

			$this->return = ($this->current_page != 1 && $this->items_total >= 10) ?

				'<a class="jr-pagenav-prev jrPageActive jrButton jrSmall" href="'.$url.'">'.__t("&laquo;",true).'</a> '

				:

				'<span class="jrPageInactive jrButton jrSmall jrDisabled">'.__t("&laquo;",true).'</span> ';

			$this->start_range = $this->current_page - floor($this->mid_range/2);

			$this->end_range = $this->current_page + floor($this->mid_range/2);

			if($this->start_range <= 0)
			{
				$this->end_range += abs($this->start_range)+1;
				$this->start_range = 1;
			}
			if($this->end_range > $this->num_pages)
			{
				$this->start_range -= $this->end_range-$this->num_pages;
				$this->end_range = $this->num_pages;
			}

			$this->range = range($this->start_range,$this->end_range+1);

			// Even mid range
			if(!($this->mid_range%2)) {

				if($this->current_page < 1 + $this->mid_range) {

					$this->range = array_splice($this->range,0,$this->mid_range);
				}
				elseif($this->current_page > $this->num_pages - $this->mid_range) {


					$this->range = array_splice($this->range,1,$this->mid_range+1);
				}
				else {

					$this->range = array_splice($this->range,0,$this->mid_range);
				}
			}
			else {


				$this->range = array_splice($this->range,0,$this->mid_range);

			}

			// INDIVIDUAL PAGES
			for($i=1;$i<=$this->num_pages;$i++)
			{
				if($this->range[0] > 2 && $i == $this->range[0]) {

					$this->return .= " ... ";
				}

				// loop through all pages. if first, last, or in range, display
				if($i==1 || $i==$this->num_pages || in_array($i,$this->range))
				{
					$url = $this->addPagination($i,$this->items_per_page);

					$this->return .= ($i == $this->current_page) ?

						'<span title="'.sprintf(__t("Go to page %s",true),$i,$i,$this->num_pages).'" class="jr-pagenav-current jr-pagenav-page jrPageCurrent jrButton jrSmall">'.$i.'</span> '

						:

						'<a class="jr-pagenav-page jrButton jrSmall" title="'.sprintf(__t("Go to page %s of %s",true),$i,$this->num_pages).'" href="'.$url.'">'.$i.'</a> ';

					$this->pageUrl[$i] = $url;
				}

				if($this->range[$this->mid_range-1] < $this->num_pages-1 && $i == end($this->range)) {

					$this->return .= " ... ";
				}
			}

			// NEXT PAGE
			$url = $this->addPagination($next_page,$this->items_per_page);

			$this->return .= ($this->current_page != $this->num_pages && $this->items_total >= 10) ?

				'<a class="jr-pagenav-next jrButton jrSmall" href="'.$url.'">'.__t("&raquo;",true).'</a>'

				:

				'<span class="jrPageInactive jrButton jrSmall jrDisabled">'.__t("&raquo;",true).'</span>';

		}

		# num_pages_threshold pages or less
		else {

			// INDIVIDUAL PAGES
			for($i=1;$i<=$this->num_pages;$i++)
			{
				$url = $this->addPagination($i,$this->items_per_page);

				$this->return .= ($i == $this->current_page) ?

					'<span class="jrPageInactive jrButton jrSmall jrDisabled">'.$i.'</span> '

					:

					'<a class="jr-pagenav-page jrButton jrSmall" href="'.$url.'">'.$i.'</a> ';

				$this->pageUrl[$i] = $url;
			}
		}

	}

	/**
	 * Generates the dropdown list for number of items per page
	 * @return html select list
	 */
	function display_items_per_page()
	{
		$args = func_get_args();

		if(func_num_args()==2) // For compat with old themes that had the update id var as the 1st param
		{
			$items_per_page = array_shift($args);

		} else {

			$items_per_page = array(5,10,15,20,25,30,35,40,45,50);
		}

		$Form = ClassRegistry::getClass('FormHelper');

		$segments = '';

		$url_param = array();

		$passedArgs = $this->passedArgs;

		$default_limit = Sanitize::getInt($this->params,'default_limit');

		foreach($items_per_page as $limit)
		{
			$url = $this->base_url . 'limit' . _PARAM_CHAR . $limit;

			$selectList[] = array('value'=>$url,'text'=>$limit);
		}

		$selected = $this->base_url . 'limit' . _PARAM_CHAR . $this->limit;

		return __t("Results per page",true). ': ' . $Form->select('order_limit',$selectList,$selected,array('class'=>'jr-pagenav-limit'));
	}

	function display_pages() {

		return $this->return;
	}

	function display_pages_module() {

		return $this->return_module;
	}

	function getPageUrl($page) {

		return $this->pageUrl[$page];
	}
}
