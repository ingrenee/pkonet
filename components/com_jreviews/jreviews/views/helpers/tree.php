<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class TreeHelper extends MyHelper
{
    var $helpers = array('routes','html');

    function getDirTitle($directory) {
        // Get first cat
        $cat = current($directory);
        return $cat['Directory']['title'];

    }
    /**
    * Used in the directory page
    *
    * @param mixed $tree
    */
    function renderDirectory($tree)
    {
        $current_depth = 0;
        $counter = 0;
        $result = '';

		$columns = $this->Config->dir_columns ? $this->Config->dir_columns : 2;
		$width = (int) (99/$columns);
		$first_level_count = 0;

        foreach($tree as $node)
        {
            $curr = $node['Category'];
            $node_depth = $curr['level'];
            $node_name = $curr['title'];
            $node_id = $curr['cat_id'];
            $node_count = $curr['listing_count'];

            if($node_depth == $current_depth)
            {
                if($counter > 0) $result .= '</div></li>';

            }
            elseif($node_depth > $current_depth)
            {
                $result .= '<ul>';
                $current_depth = $current_depth + ($node_depth - $current_depth);

            }
            elseif($node_depth < $current_depth)
            {
                $result .= str_repeat('</div></li></ul>',$current_depth - $node_depth).'</div></li>';
                $current_depth = $current_depth - ($current_depth - $node_depth);
            }

			if ($current_depth == 1)
			{
				$params = json_decode($node['Category']['params'],true);
				if (!empty($params['image'])) {
					$image_container = '<div class="jrListingThumbnail">'. $this->Routes->category($node,array('image'=>true)) . '</div>';
				}
				else {
					$image_container = '';
				}

				if ($first_level_count == $columns)
				{
					$result .= '<li class="jrCatLevel' . $current_depth . '" style="width: '. $width .'%; clear: both;">' . $image_container;
				}
				else {
					$result .= '<li class="jrCatLevel' . $current_depth . '" style="width: '. $width .'%;">' . $image_container;
				}
				$first_level_count++;
			}
			else
			{
				$result .= '<li class="jrCatLevel' . $current_depth . '">';
			}
			$result .= '<div class="jrContentDiv">';
            $result .= $this->Routes->category($node);
            $this->Config->dir_cat_num_entries and $result .= ' (' .$node_count . ')';
            ++$counter;
        }

		$result .= str_repeat('</div></li></ul>',$node_depth);

        return $result;
    }

  /**
  *   Used in the directories module
  *
  * @param mixed $tree
  * @param mixed $options
  */
  function renderTree($tree, $options)
  {
        $current_depth = 0;
        $counter = 0;
        $result = '';
        $folders = isset($options['folders']);
        $plans = isset($options['plans']);

        foreach($tree as $node)
        {
            $curr = $node['Category'];
            $node_depth = $curr['level'];
            $node_name = $curr['title'];
            $node_id = $curr['cat_id'];
            $node_count = $curr['listing_count'];

            if($node_depth == $current_depth)
            {
                if($counter > 0) $result .= '</li>';
            }
            elseif($node_depth > $current_depth)
            {
                $result .= '<ul'.($folders ? ' class="filetree"' : '').'>';
                $current_depth = $current_depth + ($node_depth - $current_depth);
            }
            elseif($node_depth < $current_depth)
            {
                $result .= str_repeat('</li></ul>',$current_depth - $node_depth).'</li>';
                $current_depth = $current_depth - ($current_depth - $node_depth);
            }
            $result .= '<li class="jr-tree-cat-'.$node_id.' closed"';
            $result .= '>';
            $folders and $result .= '<span class="folder">&nbsp;';
            $result .= !$plans ?
                $this->Routes->category($node)
                :
                $this->Routes->category($node,array("onclick"=>"JRPaid.Plans.load({'cat_id':".$node_id."});return false;"))
                ;
            $this->Config->dir_cat_num_entries and $result .= ' (' .$node_count . ')';
            $folders and $result .= '</span>';
            ++$counter;
        }

        $result .= str_repeat('</li></ul>',$node_depth);

        return $result;
  }
}
