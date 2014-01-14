<?php
// No direct access to this file
defined('_JEXEC') or die;
// import the list field type
jimport('joomla.html.html.list');

class JFormFieldListingType extends JFormFieldList
{
        /**
         * The field type.
         *
         * @var         string
         */
        protected $type = 'listingtype';
        /**
         * Method to get a list of options for a list input.
         *
         * @return      array           An array of JHtml options.
         */
        protected function getOptions() 
        {
                $db = JFactory::getDBO();

                $query = "
                    SELECT 
                        type.id AS value,
                        type.title AS text
                    FROM 
                        #__jreviews_criteria AS type
                    ORDER BY
                        type.title
                ";
                $db->setQuery($query);
                $messages = $db->loadObjectList();
                $options = array('- Select Listing Type (Adv. Search) -');
                foreach($messages as $message) 
                {
                        $options[] = JHtml::_('select.option', $message->value, $message->text);
                }
                $options = array_merge(parent::getOptions() , $options);
                return $options;
        }
}