<?php
/**
 * Html Helper class file.
 *
 * Simplifies the construction of HTML elements.
 *
 * CakePHP(tm) :  Rapid Development Framework <http://www.cakephp.org/>
 * Copyright 2005-2007, Cake Software Foundation, Inc.
 *                                1785 E. Sahara Avenue, Suite 490-204
 *                                Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @modified    by ClickFWD LLC
 */

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class FormHelper extends MyHelper {

    var $helpers = array('html');

    function __construct() {
        parent::__construct();
    }

    function label($fieldName = null, $text = null, $attributes = array()) {

        $labelFor = isset($attributes['for']) && $attributes['for']!='' ? $attributes['for'] : false;
        unset($attributes['for']);
        if($labelFor != '') {
        if($labelFor != '')
            return $this->output(sprintf($this->Html->tags['label'], $labelFor, $this->_parseAttributes($attributes), $text));
        } else {
            return $this->output(sprintf($this->Html->tags['label_no_for'], $this->_parseAttributes($attributes), $text));
        }
    }

/**
 * Will display all the fields passed in an array expects fieldName as an array key
 *
 * @access public
 * @param array $fields;
 * @param array $blacklist a simple array of fields to skip
 * @return output
 */
    function inputs($fields = null, $attributes = null, $blacklist = null, $array = null) {

        $fieldset = $legend = true;

        if (is_array($fields)) {
            if (isset($fields['legend'])) {
                $legend = $fields['legend'];
                unset($fields['legend']);
            }

            if (isset($fields['fieldset'])) {
                $fieldset = $fields['fieldset'];
                unset($fields['fieldset']);
            }
        } elseif ($fields !== null) {
            $legend = $fields;
            unset($fields);
        }

        if (empty($fields)) {
            $fields = array_keys($this->fieldset['fields']);
        }

        $out = !$array ? null : array();

        foreach ($fields as $name => $options) {
            if (is_numeric($name) && !is_array($options)) {
                    $name = $options;
                    $options = array();
            }
            if (is_array($blacklist) && in_array($name, $blacklist)) {
                continue;
            }

            if(!$array) {
                $out .= $this->input($name, $options) . "\n";
            } else {
                $out[$name] = $this->input($name, $options);
            }
        }

        if ($fieldset) {
            return sprintf($this->Html->tags['fieldset'], $this->_parseAttributes($attributes), $legend , $out);
        } else {
            return $out;
        }
    }

    function input($fieldName, $options = array()) {

        $options = array_merge(array('before' => null, 'between' => null, 'after' => null), $options);

        $out = '';
        $div = true;

        if (isset($options['div'])) {
            $div = $options['div'];
            unset($options['div']);
        }

        if (!empty($div)) {
            $divOptions = array('class' => 'field');
            if (is_string($div)) {
                $divOptions['class'] = $div;
            } elseif (is_array($div)) {
                $divOptions = array_merge($divOptions, $div);
            }
        }

        $label = false;
//        if (isset($options['label']) && $options['type'] !== 'radio' && $options['type'] !== 'checkbox') {
        if (isset($options['label'])) {

            $label = $options['label'];

            unset($options['label']);
        }

        if ($options['type'] === 'radio' || $options['type'] === 'checkbox') {
//            $label = false;
            unset($options['id']);    //2.0.9
            if (isset($options['options'])) {
                if (is_array($options['options'])) {
                    $radioOptions = $options['options'];
                } else {
                    $radioOptions = array($options['options']);
                }
                unset($options['options']);
            }
        }

        if ($label !== false) {

            $labelAttributes = array();

            if (is_array($label)) {

                $labelText = null;

                if (isset($label['text'])) {

                    $labelText = $label['text'];

                    unset($label['text']);
                }

                $labelAttributes = array_merge($labelAttributes, $label);

            } else {

                $labelText = $label;
            }

            if (isset($options['id'])) {

                $labelAttributes = array_merge($labelAttributes, array('for' => $options['id']));

                $out = $this->label(null, $labelText, $labelAttributes); // Moved inside if statement in 2.0.9

            } else {

                $labelAttributes = $labelAttributes;

                $out = $this->label(null, $labelText, $labelAttributes);
            }
        }

        $error = null;

        if (isset($options['error'])) {
            $error = $options['error'];
            unset($options['error']);
        }

        $selected = null;
        if (isset($options['selected'])) {
            $selected = $options['selected'];
            unset($options['selected']);
        }

        if (isset($options['rows']) || isset($options['cols'])) {
            $options['type'] = 'textarea';
        }

        $empty = false;
        if (isset($options['empty'])) {
            $empty = $options['empty'];
            unset($options['empty']);
        }

        $timeFormat = 12;
        if (isset($options['timeFormat'])) {
            $timeFormat = $options['timeFormat'];
            unset($options['timeFormat']);
        }

        $dateFormat = 'MDY';
        if (isset($options['dateFormat'])) {
            $dateFormat = $options['dateFormat'];
            unset($options['dateFormat']);
        }

        $type     = $options['type'];
        $before     = $options['before'];
        $between = $options['between'];
        $after     = $options['after'];

        unset($options['type'], $options['before'], $options['between'], $options['after']);

        switch ($type) {
            case 'hidden':
                $out = $this->hidden($fieldName, $options);
                unset($divOptions);
            break;
            case 'checkbox':
            case 'radio':
                $out = $before . $out . $between . $this->{$type}($fieldName, $radioOptions, $options) . $after;
            break;
            case 'text':
            case 'email':
            case 'url':
            case 'number':
                $out = $before . $out . $between . $this->{$type}($fieldName, $options) . $after;
            break;
            case 'password':
                $out = $before . $out . $between . $this->password($fieldName, $options) . $after;
            break;
            case 'file':
                $out = $before . $out . $between . $this->file($fieldName, $options) . $after;
            break;
            case 'select':
                $options = array_merge(array('options' => array()), $options);
                $list = $options['options'];
                unset($options['options']);
                $out = $before . $out . $between . $this->select($fieldName, $list, $selected, $options, $empty) . $after;
            break;
            case 'time':
                $out = $before . $out . $between . $this->dateTime($fieldName, null, $timeFormat, $selected, $options, $empty) . $after;
            break;
            case 'date':
                $out = $before . $out . $between . $this->text($fieldName, $options) . $after;
//                $out = 'date';//$before . $out . $between . $this->date($fieldName, $dateFormat, null, $selected, $options, $empty);
            break;
            case 'datetime':
                $out = $before . $out . $between . $this->dateTime($fieldName, $dateFormat, $timeFormat, $selected, $options, $empty) . $after;
            break;
            case 'textarea':
            default:
                $out = $before . $out . $between . $this->textarea($fieldName, array_merge(array('cols' => '30', 'rows' => '6'), $options)) . $after;
            break;
        }

/*        if ($type != 'hidden') {
            $out .= $after;
            if ($error !== false) {
                $out .= $this->error($fieldName, $error);
            }
        }*/
        if (isset($divOptions)) {
            $out = $this->Html->div($divOptions['class'], $out , $divOptions);
        }
        return $out . "\n";
    }

    function hidden($fieldName, $attributes = array()) {

        if(isset($attributes['value'])) {
            if(is_array($attributes['value'])) {
                $value = end($attributes['value']);
            } else {
                $value = $attributes['value'];
            }
            unset($attributes['value']);
            $attributes['value'] = $value;
        }
        return $this->output(sprintf($this->Html->tags['hidden'], $fieldName, $this->_parseAttributes($attributes)));
    }

    function email($fieldName, $attributes = array())
    {
        return $this->text($fieldName, $attributes, 'email');
    }

    function number($fieldName, $attributes = array())
    {
        return $this->text($fieldName, $attributes, 'number');
    }

    function url($fieldName, $attributes = array())
    {
        return $this->text($fieldName, $attributes, 'url');
    }

    function text($fieldName, $attributes = array(), $type = 'text')
    {
        if(isset($attributes['value'])) {
            if(is_array($attributes['value'])) {
                $value = end($attributes['value']);
            } else {
                $value = $attributes['value'];
            }
            unset($attributes['value']);
            $attributes['value'] = $value;
        }

        if (isset($attributes['multiple'])) {
            // Treat this input as an array
            $fieldName .= '[]';
            unset($attributes['multiple']);
        }

        return sprintf($this->Html->tags[$type], $fieldName, $this->_parseAttributes($attributes));
    }

    function textarea($fieldName, $attributes = array())
    {
        $value = '';

        if(isset($attributes['value']) && $attributes['value']!='') {
            if(is_array($attributes['value'])) {
                $value = end($attributes['value']);
            } else {
                $value = $attributes['value'];
            }
        }

        unset($attributes['value']);

        return sprintf($this->Html->tags['textarea'], $fieldName, $this->_parseAttributes($attributes), $value);
    }

    function selectNumbers($fieldName, $start, $end , $inc, $selected = null, $attributes = array()) {

        $options = array();
        for($i=$start;$i<=$end;$i+=$inc) {
            $options[$i] = $i;
        }

        return $this->select($fieldName,$options,$selected,$attributes);
    }

    function select($fieldName, $options = array() , $selected = null, $attributes = array())
    {
        $sel = '';

        if (!is_null($selected) && $selected != '') {

            if(!is_array($selected)) {
                $selected = array(strtolower($selected));
            }

        } else {

            if (isset($attributes['value'])) {

                if(!is_array($attributes['value'])) {

                    $selected = array(strtolower($attributes['value']));
                }
                else {

                    $selected = $attributes['value'];
                }

                unset($attributes['value']);
            }
        }

        if (isset($attributes['multiple'])) {

            $select = sprintf($this->Html->tags['selectmultiplestart'],$fieldName,$this->_parseAttributes($attributes));

            unset($attributes['multiple']);

        }
        else {

            $select = sprintf($this->Html->tags['selectstart'],$fieldName,$this->_parseAttributes($attributes));
        }

        foreach ($options as $value => $text)
        {
            $disabled = false;

            $sel = '';

            if(is_array($text) || is_object($text)) {

                $text = (array) $text;

                $disabled = Sanitize::getBool($text,'disabled',false);

                if(!isset($text['text']))  {

                    $text = current($text);

                }

                $value = isset($text['value']) ? $text['value'] : $value;

                $text = $text['text']; // For CMS db query results w/o having to do an additional foreach
            }

            $text = htmlspecialchars($text,ENT_QUOTES,'utf-8',false);

            # Multiple select list
            if($selected && is_array($selected))
            {
                $sel = (deep_in_array($value,$selected,true) ? ' selected="selected"' : '');

            # Single select list
            } elseif ($selected) {

                $sel = ($value == $selected) ? ' selected="selected"' : '';

            }

            $disabled and $sel .= ' disabled="disabled"';

            $select .= sprintf($this->Html->tags['selectoption'],$value,$sel,$text);
        }

        $select .= $this->Html->tags['selectend'];

        return $select;
    }

    function checkbox($fieldName, $options = array(), $attributes = array())
    {
        $id = false;
        if (isset($attributes['id'])) {
            $id = $attributes['id'];
            unset($attributes['id']);
        }

        $legend = false;
        if (isset($attributes['legend'])) {
            $legend = $attributes['legend'];
            unset($attributes['legend']);
        }

        $label = false;
        if (isset($attributes['label'])) {
            $label = $attributes['label'];
            unset($attributes['label']);
        }

        $inbetween = false;
        if (isset($attributes['separator'])) {
            $inbetween = $attributes['separator'];
            unset($attributes['separator']);
        }

        if (isset($attributes['value'])) {
            if(!is_array($attributes['value'])) {
                $checked = array($attributes['value']);
            } else {
                $checked = $attributes['value'];
            }
        }

        $fieldOptionClass = '';
        if (isset($attributes['option_class'])) {
            $fieldOptionClass = $attributes['option_class'];
            unset($attributes['option_class']);
        }

        $out = array();

        if(is_array($options))
        {
            foreach ($options as $value => $text)
            {
                if(is_array($text)) extract($text);

                $optionsHere = array('value' => $value);
                if (isset($checked) && in_array($value, $checked)) {
                    $optionsHere['checked'] = 'checked';
                }

                $parsedOptions = $this->Html->_parseAttributes(array_merge($attributes, $optionsHere));

                preg_match('/jr_[a-z]*/',$fieldName,$matches);
                if($matches){
                    $tagName = $matches[0].'_'.$value;
                } else {
                    $tagName = str_replace(array('[',']'),'_',$fieldName).'_'.$value;
                }

                if ($label) {
                    $labelAttr = is_array($label) && !empty($label) ? ' '. $this->Html->_parseAttributes($label) : '';
                    $checkbox = sprintf($this->Html->tags['checkboxmultiple'], $fieldName, $tagName, $parsedOptions, $text);
                    $out[] =  sprintf($this->Html->tags['label'], $tagName, $labelAttr, $checkbox);
                } else {
                    $checkbox = sprintf($this->Html->tags['checkboxmultiple'], $fieldName, $tagName, $parsedOptions, $text);
                    $out[] =  $this->Html->div($fieldOptionClass,$checkbox);
    //                $out[] =  sprintf($this->Html->tags['checkboxmultiple'], $fieldName, $tagName, $parsedOptions, $optTitle);
                }
            }
        }

        $out = join($inbetween, $out);

        if ($legend) {
            $out = sprintf($this->Html->tags['fieldset'], null, $legend, $out);
        }
        return $this->output($out);
    }

    function checkboxOne($fieldName, $options = array(), $attributes = array())
    {
        $id = false;
        if (isset($attributes['id'])) {
            $id = $attributes['id'];
            unset($attributes['id']);
        }

        $legend = false;
        if (isset($attributes['legend'])) {
            $legend = $attributes['legend'];
            unset($attributes['legend']);
        }

        $label = false;
        if (isset($attributes['label'])) {
            $label = $attributes['label'];
            unset($attributes['label']);
        }

        $inbetween = false;
        if (isset($attributes['separator'])) {
            $inbetween = $attributes['separator'];
            unset($attributes['separator']);
        }

        if (isset($attributes['value'])) {
            if(!is_array($attributes['value'])) {
                $checked = array($attributes['value']);
            } else {
                $checked = $attributes['value'];
            }
        }

        $fieldOptionClass = '';

        if (isset($attributes['option_class'])) {

            $fieldOptionClass = $attributes['option_class'];

            unset($attributes['option_class']);
        }

        $out = array();

        if(is_array($options))
        {
            foreach ($options as $value => $text)
            {
                if(is_array($text)) extract($text);

                $optionsHere = array('value' => $value);
                if (isset($checked) && in_array($value, $checked)) {
                    $optionsHere['checked'] = 'checked';
                }

                $parsedOptions = $this->Html->_parseAttributes(array_merge($attributes, $optionsHere));

                $tagName = str_replace(array('[',']'),'_',$fieldName).'_'.$value;

                if ($label) {

                    $labelAttr = is_array($label) && !empty($label) ? ' '. $this->Html->_parseAttributes($label) : '';

                    $checkbox = sprintf($this->Html->tags['checkbox'], $fieldName, $tagName, $parsedOptions, $text);

                    $out[] =  sprintf($this->Html->tags['label'], $tagName, $labelAttr, $checkbox);
                }
                else {

                    $checkbox = sprintf($this->Html->tags['checkbox'], $fieldName, $tagName, $parsedOptions, $text);

                    $out[] =  $this->Html->div($fieldOptionClass,$checkbox);
                }
            }
        }

        $out = join($inbetween, $out);

        if ($legend) {

            $out = sprintf($this->Html->tags['fieldset'], null, $legend, $out);
        }

        return $this->output($out);
    }

    function radioYesNo($fieldName, $attributes = array(), $value, $yes = 'Yes', $no = 'No') {

        if(is_array($attributes)) {
            $attributes = array_merge($attributes,array('value'=>$value,'div'=>false));
        } else {
            $attributes = array('value'=>$value,'div'=>false);
        }

        return $this->radio($fieldName,array('0'=>$no,'1'=>$yes),$attributes);
    }

    function radio($fieldName, $options = array(), $attributes = array()) {

        $id = false;
        if (isset($attributes['id'])) {
            $id = $attributes['id'];
            unset($attributes['id']);
        }

        $legend = false;
        if (isset($attributes['legend'])) {
            $legend = $attributes['legend'];
            unset($attributes['legend']);
        }

        $div = true;
        if (isset($attributes['div'])) {
            $div = $attributes['div'];
            unset($attributes['div']);
        }

        $inbetween = null;
        if (isset($attributes['separator'])) {
            $inbetween = $attributes['separator'];
            unset($attributes['separator']);
        }

        if(isset($attributes['value'])) {
            if(is_array($attributes['value'])) {
                $value = end($attributes['value']);
            } else {
                $value = $attributes['value'];
            }
            unset($attributes['value']);
        }

        $fieldOptionClass = '';
        if (isset($attributes['option_class'])) {
            $fieldOptionClass = $attributes['option_class'];
            unset($attributes['option_class']);
        }

        $out = array();

        foreach ($options as $optValue => $optTitle) {

            $optionsHere = array('value' => $optValue);

            if (isset($value) && $optValue == $value) {
                $optionsHere['checked'] = 'checked';
            }

            $parsedOptions = $this->Html->_parseAttributes(array_merge($attributes, $optionsHere));

            preg_match('/jr_[a-z]*/',$fieldName,$matches);

            $tagName = isset($matches[0]) ? $matches[0].'_'.$optValue : $fieldName.$optValue;

            $radio =  sprintf($this->Html->tags['radio'], $fieldName, $tagName, $parsedOptions, $optTitle);

            if ($div) {
                $label =  $this->Html->div($fieldOptionClass,$radio);
                $out[] = $label;
            } else {
                $out[] = $radio;
            }
        }


        $out = join($inbetween, $out);

        if ($legend) {
            $out = sprintf($this->Html->tags['fieldset'], null, $legend, $out);
        }
        return $this->output($out);
    }

    function button($text = null, $options = array()) {

        $div = false;

        if (isset($options['div'])) {
            $div = $options['div'];
            unset($options['div']);
        }

        $divOptions = array();

        if ($div === true) {

            $divOptions['class'] = 'submit';
        }
        elseif ($div === false) {

            unset($divOptions);
        }
        elseif (is_string($div)) {

            $divOptions['class'] = $div;
        }
        elseif (is_array($div)) {

            $divOptions = array_merge(array('class' => 'button'), $div);
        }

        $out = $this->output(sprintf($this->Html->tags['button'], $this->_parseAttributes($options), $text));

        if (isset($divOptions)) {

            $out = $this->Html->div($divOptions['class'], $out, $divOptions);
        }

        return $out;
    }

    function image($source = null, $options = array()) {

        $options['src'] = $source;

        $div = false;
        if (isset($options['div'])) {
            $div = $options['div'];
            unset($options['div']);
        }
        $divOptions = array();

        if ($div === true) {
            $divOptions['class'] = 'submit';
        } elseif ($div === false) {
            unset($divOptions);
        } elseif (is_string($div)) {
            $divOptions['class'] = $div;
        } elseif (is_array($div)) {
            $divOptions = array_merge(array('class' => 'button'), $div);
        }
        $out = $this->output(sprintf($this->Html->tags['imagebutton'], $this->_parseAttributes($options)));

        if (isset($divOptions)) {
            $out = $this->Html->div($divOptions['class'], $out, $divOptions);
        }
        return $out;
    }

    function submit($caption = null, $options = array()) {

        $options['value'] = $caption;

        $div = true;
        if (isset($options['div'])) {
            $div = $options['div'];
            unset($options['div']);
        }
        $divOptions = array();

        if ($div === true) {
            $divOptions['class'] = 'submit';
        } elseif ($div === false) {
            unset($divOptions);
        } elseif (is_string($div)) {
            $divOptions['class'] = $div;
        } elseif (is_array($div)) {
            $divOptions = array_merge(array('class' => 'submit'), $div);
        }
        $out = $this->output(sprintf($this->Html->tags['submit'], $this->_parseAttributes($options)));

        if (isset($divOptions)) {
            $out = $this->Html->div($divOptions['class'], $out, $divOptions);
        }
        return $out;
    }

    function upload($data = '', $value = '', $extra = '')
    {
        if ( ! is_array($data))
        {
            $data = array('name' => $data);
        }

        $data['type'] = 'file';
        return $this->input($data, $value, $extra);
    }

    function token($id = 'mvcToken') {

        $token = cmsFramework::getToken();

        return $this->hidden('data[__Token][Key]',array('id'=>$id,'value'=>$token));

    }

}