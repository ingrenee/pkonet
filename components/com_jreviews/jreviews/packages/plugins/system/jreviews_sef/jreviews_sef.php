<?php
/**
 * @version       1.0.0 August 18, 2013
 * @author        ClickFWD http://www.reviewsforjoomla.com
 * @copyright     Copyright (C) 2010 - 2013 ClickFWD LLC. All rights reserved.
 * @license       Proprietary
 *
 */
defined('_JEXEC') or die;

if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

jimport( 'joomla.plugin.plugin');

/**
 * Need to override the whole JModuleHelper class because it's not possible to extend it
 */
if(!class_exists('JModuleHelper') &&
        !file_exists(JPATH_PLUGINS . '/system/advancedmodules/modulehelper.php')) {

    require_once dirname(__FILE__) . DS . 'modulehelper.php';

    JFactory::getApplication()->registerEvent('onPrepareModuleList', 'JReviewsPrepareModuleList');
}

class plgSystemJreviews_sef extends JPlugin
{
    var $canonical_url = false;

    static function prx($var)
    {
        echo '<pre>'.print_r($var,true).'</pre>';
    }

    public function onAfterInitialise()
    {
        $app  = JFactory::getApplication();

        if($app->isAdmin()) {

            // We need to define the constant here for admin side links (i.e. in moderation) to get the correct front-end menu ids

            $use_core_cat_menus = $this->params->get('use_core_cat_menus', 0);

            if($use_core_cat_menus)
            {
                define('JREVIEWS_SEF_PLUGIN',1);
            }

            return;
        }

        if(JFactory::getConfig()->get('sef'))
        {
            $suffix = JFactory::getConfig()->get('sef_suffix');

            JFactory::getConfig()->set('sef_suffix',0);

            $this->params->set('sef_suffix', $suffix);

            $router = $app->getRouter();

            require_once dirname(__FILE__) . DS . 'jreviews_router.php';

            $JreviewsRouter = new JReviewsRouter($this->params, $this);

            $router->attachBuildRule(array($JreviewsRouter, 'buildJReviews'));

            $router->attachParseRule(array($JreviewsRouter, 'parseJReviews'));
        }
    }

    public function onBeforeRender()
    {
        $doc = JFactory::getDocument();

        $app = JFactory::getApplication();

        if ($app->getName() != 'site' || $doc->getType() !== 'html') {
            return true;
        }

        // Remove canonical tags added by the Joomla sef plugin

        // if($this->canonical_url) {

            foreach($doc->_links AS $url=>$attr)
            {
                if(isset($attr['relation']) && $attr['relation'] == 'canonical') {

                    // Replace the canonical tag with our own version

                    // $doc->_links[$this->canonical_url] = $attr;

                    // Remove the previous canonical tag set by the sef system plugin
                    unset($doc->_links[$url]);
                }
            }
        // }
    }

    /**
     * Add new JReviews tab to all modules to control assignment on JReviews pages
     */
    public function onContentPrepareForm($form, $data)
    {
        $app  = JFactory::getApplication();

        $input = $app->input;

        if(!$app->isAdmin()
            || ($input->get('option') != 'com_modules' && $input->get('view') != 'module')
            || file_exists(JPATH_PLUGINS . '/system/advancedmodules/modulehelper.php'))
        {
                return;
        }

        // Load plugin parameters
        $module = JModuleHelper::getModule($data->module);

        $params = new JRegistry($data->params);

        // Check we have a form
        if (!($form instanceof JForm))
        {
            $this->_subject->setError('JERROR_NOT_A_FORM');

            return false;
        }

        // Extra parameters for menu edit
        if ($form->getName() == 'com_modules.module')
        {
            $form->load('
                    <form>
                    <fields name="params" >
                    <fieldset
                    name="JReviews Page Assignment "
                    label="JReviews Page Assignment"
                    >
                    <field
                    name="jreviews_page_hide"
                    type="radio"
                    label="Hide Module in "
                    description=""
                    default="' . $params->get('jreviews_page_hide', 0) . '"
                    >
                            <option value="category">Category page</option>
                            <option value="detail">Detail page</option>
                            <option value="0">Don\'t hide</option>
                    </field>
                    </fieldset>
                    </fields>
                    </form>
                    ');
        }

        return true;
    }
}

/**
 * Disables modules on-the-fly in pages as specified in the JReviews Module Assignment settings
 */
function JReviewsPrepareModuleList(&$modules)
{
    $app = JFactory::getApplication();

    if(JFactory::getApplication()->getClientId()) return;

    $JMenu = $app->getMenu();

    $page = '';

    $disabled_modules = array();

    $input = $app->input;

    $menu_id = $input->get('Itemid');

    $menu = $JMenu->getItem($menu_id);

    if(!$menu) return;

    $query = $menu->query;

    if($input->get('option') == 'com_content' && $input->get('view') == 'article')
    {
        $page = 'detail';
    }
    elseif(
        ($query['option'] == 'com_content' && $query['view'] == 'category')
        ||
        ($query['option'] == 'com_jreviews' && $query['view'] == 'category' && $menu->params->get('action') == 2))
    {
        $page = 'category';
    }

    foreach($modules AS $id=>$module)
    {
        $params = new JRegistry($module->params);

        $jreviews_page_hide = $params->get('jreviews_page_hide','not-set');

        if($jreviews_page_hide == $page)
        {
            $module->published = 0;

            $modules[$id] = $module;
        }
    }
 }