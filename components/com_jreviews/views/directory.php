<?php
// No direct access to this file
defined('_JEXEC') or die;
// import the list field type
jimport('joomla.html.html.list');

class JFormFieldDirectory extends JFormFieldList
{
        /**
         * The field type.
         *
         * @var         string
         */
        protected $type = 'directory';
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
                        directory.id AS value,
                        directory.desc AS text
                    FROM 
                        #__jreviews_directories AS directory
                    ORDER BY
                        directory.desc
                ";
                $db->setQuery($query);
                $messages = $db->loadObjectList();
                $options = array('- Select Directory -');
                foreach($messages as $message) 
                {
                        $options[] = JHtml::_('select.option', $message->value, $message->text);
                }
                $options = array_merge(parent::getOptions() , $options);
                return $options;
        }
}