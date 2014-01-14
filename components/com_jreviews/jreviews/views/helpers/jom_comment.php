<?php
defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

/**
* Usage:
* In /listings/listings_blogview.thtml or /listings/listings_tableview.thtml add the following at the top of the file
* inside php tags:
*
*  S2App::import('Helper','jom_comment');
*  $JomComment = new JomCommentHelper();
*
* Then inside the foreach statement within the HTML block of the theme place this code where you want
* the JomComment links to show up:
*
* <?php echo $JomComment->blogView($listing);?>
*/
class JomCommentHelper extends MyHelper {

    function blogView($listing){
        $_GET['view']=$_REQUEST['view']='category';
        $_GET['layout']=$_REQUEST['layout']='blog';
        $plg = new stdClass();
        $plg->id = $listing['Listing']['listing_id'];
        $plg->catid = $listing['Listing']['cat_id'];
        $plg->sectionid = $listing['Listing']['section_id'];
        $plg->text='{jomcomment}';
        $dispatcher =& JDispatcher::getInstance();
        $params = new JParameter('');
        JPluginHelper::importPlugin('content');
        $results = $dispatcher->trigger('onPrepareContent', array (& $plg, & $params, 0, 'com_content'));
        return $plg->text;
    }
}
?>
