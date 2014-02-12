<?php
// No direct access to this file
defined('_JEXEC') or die;
// import the list field type
jimport('joomla.html.html.list');

class JFormFieldCategory extends JFormFieldList
{
        /**
         * The field type.
         *
         * @var         string
         */
        protected $type = 'category';
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
                        Category.id AS value,
                        CONCAT(REPEAT('- ', IF(Category.level>0,Category.level - 1,1)), Category.title) AS text
                    FROM
                        #__categories AS Category
                    LEFT OUTER JOIN
                        #__categories AS ParentCategory ON Category.lft <= ParentCategory.lft AND Category.rgt >= ParentCategory.rgt
                    INNER JOIN
                        #__jreviews_categories AS JreviewCategory ON JreviewCategory.id = Category.id AND JreviewCategory.`option` = 'com_content'
                    WHERE
                        Category.extension = 'com_content'
                    GROUP BY
                        Category.id
                    ORDER
                        BY Category.lft
                ";

                $db->setQuery($query);

                $messages = $db->loadObjectList();

                $options = array('- Select Category -');

                foreach($messages as $message)
                {
                        $options[] = JHtml::_('select.option', $message->value, $message->text);
                }

                $options = array_merge(parent::getOptions() , $options);

                return $options;
        }
}