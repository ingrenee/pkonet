<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );

class AdminFieldsHelper extends S2Object
{
	var $type;
	var $params;

	function advancedOptions($type,$params,$location)
	{
		$this->type = $type;
		$this->params = $params;
		$this->location = $location;

        S2App::import('Helper',array('form','html'));
        $Form = new FormHelper();
        $Form->Html = new HtmlHelper();

		switch($this->type) {
		    case 'relatedlisting':
                ?>

                <fieldset>

					<?php $this->outputFormat();?>

                </fieldset>

                <?php
            break;
			case 'date':
				?>

                <fieldset>

                	<div class="jrGrid jrFieldDiv">

		                <div class="jrCol2">

		                    <label><?php __a("Date output format");?></label>

		                </div>

		                <div class="jrCol6">

							<input size="20" type="text" id="params[date_format]" name="data[Field][params][date_format]" value="<?php $this->dateFormat()?>" />

		                </div>

		                <div class="jrCol4">

							<?php echo sprintf(__a("Uses %sPHP's strftime function%s format",true),'<a href="http://www.php.net/strftime" target="_blank">','</a>');?>

							<i>Default: %B %d, %Y</i>

		                </div>

		            </div>


                	<div class="jrGrid jrFieldDiv">

		                <div class="jrCol2">

		                    <label><?php __a("Year Range");?></label>

		                </div>

		                <div class="jrCol6">

                        	<input size="20" type="text" id="params[year_range]" name="data[Field][params][year_range]" value="<?php echo Sanitize::getString($this->params,'year_range')?>" />

		                </div>

		                <div class="jrCol4">

							<?php echo sprintf(__a("Check the %sjQuery UI datepicker documentation%s for options",true),'<a href="http://api.jqueryui.com/datepicker/#option-yearRange" target="_blank">','</a>');?>

                        	<i><?php __a("Relative to today's year -nn:+nn, relative to the currently selected year c-nn:c+nn, absolute nnnn:nnnn, or combinations of these formats nnnn:-nn. Leave empty for +-10 from selected year.");?></i>

		                </div>

		            </div>

                	<div class="jrGrid jrFieldDiv">

		                <div class="jrCol2">

		                    <label><?php __a("Min. Date");?></label>

		                </div>

		                <div class="jrCol6">

                        	<input size="20" type="text" id="params[min_date]" name="data[Field][params][min_date]" value="<?php echo Sanitize::getString($this->params,'min_date')?>" />

		                </div>

		                <div class="jrCol4">

							<?php echo sprintf(__a("Check the %sjQuery UI datepicker documentation%s for options",true),'<a href="http://api.jqueryui.com/datepicker/#option-minDate" target="_blank">','</a>');?>

                        	<i><?php __a("A string in the format defined by the dateFormat option, or a relative date. Relative dates must contain value and period pairs; valid periods are \"y\" for years, \"m\" for months, \"w\" for weeks, and \"d\" for days. For example, \"+1m +7d\" represents one month and seven days from today.");?></i>

		                </div>

		            </div>

                	<div class="jrGrid jrFieldDiv">

		                <div class="jrCol2">

		                    <label><?php __a("Max. Date");?></label>

		                </div>

		                <div class="jrCol6">

                        	<input size="20" type="text" id="params[max_date]" name="data[Field][params][max_date]" value="<?php echo Sanitize::getString($this->params,'max_date')?>" />

		                </div>

		                <div class="jrCol4">

							<?php echo sprintf(__a("Check the %sjQuery UI datepicker documentation%s for options",true),'<a href="http://api.jqueryui.com/datepicker/#option-maxDate" target="_blank">','</a>');?>

                        	<i><?php __a("A string in the format defined by the dateFormat option, or a relative date. Relative dates must contain value and period pairs; valid periods are \"y\" for years, \"m\" for months, \"w\" for weeks, and \"d\" for days. For example, \"+1m +7d\" represents one month and seven days from today.");?></i>

		                </div>

		            </div>

					<?php $this->click2search();?>

					<?php $this->outputFormat();?>

                </fieldset>

				<?php
				break;

			case 'text':
			case 'textarea':
				?>

                <fieldset>

		        	<div class="jrGrid jrFieldDiv">

		                <div class="jrCol2">

		                    <label><?php __a("Validation Regex");?></label>

		                </div>

		                <div class="jrCol6">

							<input id="params[valid_regex]" name="data[Field][params][valid_regex]" type="text" style="width:95%;" value="<?php $this->regex()?>" />

		                </div>

		                <div class="jrCol4">

		                	&nbsp;

		                </div>

		            </div>

					<?php $this->allowHtml()?>

					<?php $this->click2search()?>

					<?php $this->outputFormat()?>

	        	</fieldset>

				<?php
				break;

			case 'code':
				?>

				<fieldset>

					<div><?php __a("There are no advanced options for the code enabled text area field.");?></div>

				</fieldset>

				<?php
				break;

			case 'decimal':
			case 'integer':
				?>

                <fieldset>

		        	<div class="jrGrid jrFieldDiv">

		                <div class="jrCol2">

		                    <label><?php __a("Validation Regex");?></label>

		                </div>

		                <div class="jrCol6">

							<input id="params[valid_regex]" name="data[Field][params][valid_regex]" type="text" style="width:95%;" value="<?php $this->regex()?>" />

		                </div>

		                <div class="jrCol4">

		                	&nbsp;

		                </div>

		            </div>

					<?php $this->currencyFormat()?>

					<?php if($this->type == 'decimal') $this->numberDecimals()?>

					<?php $this->click2search()?>

					<?php $this->outputFormat()?>

	        	</fieldset>

				<?php
				break;

			case 'select':
			case 'selectmultiple':
			case 'radiobuttons':
			case 'checkboxes':
				?>

                <fieldset>

		        	<div class="jrGrid jrFieldDiv">

		                <div class="jrCol2">

		                    <label><?php __a("Show option images");?></label>

		                </div>

		                <div class="jrCol6">

                            <?php echo $Form->radioYesNo( "data[Field][params][option_images]", "", Sanitize::getInt($this->params, 'option_images', 1));?>

		                </div>

		                <div class="jrCol4">

		                	<?php __a("If disabled text will show even if images are assigned");?>

		                </div>

		            </div>

		        	<div class="jrGrid jrFieldDiv">

		                <div class="jrCol2">

		                    <label><?php __a("Option Ordering");?></label>

		                </div>

		                <div class="jrCol6">

							<?php echo $Form->select(
								'data[Field][params][option_ordering]',
								array(0=>__a("User defined order",true),1=>__a("A-Z",true)),
								Sanitize::getInt($this->params,'option_ordering',1)
							);?>

		                </div>

		                <div class="jrCol4">

		                	&nbsp;

		                </div>

		            </div>

					<?php $this->click2search()?>

					<?php $this->outputFormat()?>

	        	</fieldset>

				<?php
				break;

			case 'email':
				?>

                <fieldset>

		        	<div class="jrGrid jrFieldDiv">

		                <div class="jrCol2">

		                    <label><?php __a("Validation Regex");?></label>

		                </div>

		                <div class="jrCol10">

							<input id="params[valid_regex]" name="data[Field][params][valid_regex]" type="text" style="width:95%;" value="<?php $this->regex()?>" />

		                </div>

		            </div>

		        </fieldset>

				<?php
				break;

			case 'website':
				?>

                <fieldset>

		        	<div class="jrGrid jrFieldDiv">

		                <div class="jrCol2">

		                    <label><?php __a("Validation Regex");?></label>

		                </div>

		                <div class="jrCol10">

							<input id="params[valid_regex]" name="data[Field][params][valid_regex]" type="text" style="width:95%;" value="<?php $this->regex()?>" />

		                </div>

		            </div>

					<?php $this->outputFormat()?>

		        </fieldset>

				<?php
				break;
		}

	}

	function allowHtml() {

        $Form = new FormHelper();

        $Form->Html = new HtmlHelper();

		?>
    	<div class="jrGrid jrFieldDiv">

            <div class="jrCol2">

                <label><?php __a("Allow HTML");?></label>

            </div>

            <div class="jrCol6">

				<?php echo $Form->radioYesNo('data[Field][params][allow_html]','',Sanitize::getInt($this->params,'allow_html',0));?>

            </div>

            <div class="jrCol4">

            	&nbsp;

            </div>

        </div>

		<?php

	}


	function click2search() {

		if($this->location != 'review') {
		?>

        	<div class="jrGrid jrFieldDiv">

                <div class="jrCol2">

                    <label><?php __a("Click2Search URL");?></label>

                </div>

                <div class="jrCol6">

					<input type="text" id="params[click2searchlink]" name="data[Field][params][click2searchlink]" style="width:98%;" value="<?php $this->click2searchLink()?>" />

                </div>

                <div class="jrCol4">

                	<?php echo sprintf(__a("You can use these tags %s",true),'{criteriaid},{catid},{optionvalue},{optiontext},{fieldname},{itemid}');?>

                	<br /><br />

					<i><?php __a("Default");?>: <?php echo 'index.php?option='.S2Paths::get('jreviews','S2_CMSCOMP').'&amp;Itemid={itemid}&amp;url=tag/{fieldname}/{optionvalue}/criteria'._PARAM_CHAR.'{criteriaid}/';?></i>

                </div>

            </div>

		<?php
		}

	}

	function click2searchLink() {

		if(!isset($this->params->click2searchlink) || $this->params->click2searchlink == '') {

			echo 'index.php?option='.S2Paths::get('jreviews','S2_CMSCOMP').'&amp;Itemid={itemid}&amp;url=tag/{fieldname}/{fieldtext}/criteria'._PARAM_CHAR.'{criteriaid}/';

		}
		else {

			echo $this->params->click2searchlink;

		}

	}

	function currencyFormat() {

        $Form = new FormHelper();

        $Form->Html = new HtmlHelper();

		?>

        	<div class="jrGrid jrFieldDiv">

                <div class="jrCol2">

                    <label><?php __a("Currency Format");?></label>

                </div>

                <div class="jrCol6">

					<?php echo $Form->radioYesNo('data[Field][params][curr_format]','',Sanitize::getInt($this->params,'curr_format',0));?>

                </div>

                <div class="jrCol4">

					&nbsp;

                </div>

            </div>

		<?php
	}

	function numberDecimals() {

        $Form = new FormHelper();

        $Form->Html = new HtmlHelper();

		?>
        	<div class="jrGrid jrFieldDiv">

                <div class="jrCol2">

                    <label><?php __a("Number of Decimals");?></label>

                </div>

                <div class="jrCol6">

					<?php echo $Form->text('data[Field][params][decimals]',array('class'=>'jrDecimal','value'=>Sanitize::getInt($this->params,'decimals',2)));?>

                </div>

                <div class="jrCol4">

					&nbsp;

                </div>

            </div>

		<?php
	}

	function dateFormat() {
		if(!isset($this->params->date_format) || $this->params->date_format == '') {
			echo '%B %d, %Y';
		} else {
			echo $this->params->date_format;
		}
	}

	function regex() {

		switch($this->type) {

			case 'website':
				$regex = '^((ftp|http|https)+(:\/\/)+[a-z0-9_-]+\.+[a-z0-9_-]|[a-z0-9_-]+\.+[a-z0-9_-])';
			break;

			case 'decimal':
				$regex = '^(\.[0-9]+|[0-9]+(\.[0-9]+)|-{0,1}[0-9]*.{0,1}[0-9]+)$'; // 0.1, .1, -0.1
				break;

			case 'integer':
				$regex = '^[0-9]+$';
				break;

			case 'email':
				$regex = '.+@.*';
				break;

			default:
				$regex = $this->params->valid_regex != '' ? $this->params->valid_regex : '';
				break;
		}

		echo $this->params->valid_regex != '' ? $this->params->valid_regex : $regex;

	}

	function outputFormat()
    {
        S2App::import('Helper',array('form','html'));

        $Form = new FormHelper();

        $Form->Html = new HtmlHelper();

		switch($this->type) {

            case 'relatedlisting':

                if(!isset($this->params->output_format) || $this->params->output_format == '') {
                    $format = '<a href="{optionvalue}">{fieldtext}</a>';
                }
                else {
                    $format = $this->params->output_format;
                }

                break;

			case 'website':

				if(!isset($this->params->output_format) || $this->params->output_format == '') {

					$format = '<a href="{fieldtext}" target="_blank">{fieldtext}</a>';
				}
				else {

					$format = $this->params->output_format;
				}

				break;

			default:

				$format = Sanitize::getString($this->params,'output_format') == '' ? '{fieldtext}' : $this->params->output_format;

				$click2search_format = Sanitize::getString($this->params,'click2search_format') == '' ? '<a href="{click2searchurl}">{optiontext}</a>' : $this->params->click2search_format;

			break;
		}
		?>
        	<div class="jrGrid jrFieldDiv">

                <div class="jrCol2">

                    <label><?php __a("Output Format");?></label>

                </div>

                <div class="jrCol6">

                    <textarea style="width:100%;" id="params[output_format]" name="data[Field][params][output_format]"><?php echo $format?></textarea>

                </div>

                <div class="jrCol4">

                	<?php if($this->location == 'content'):?>

                		<?php echo sprintf(__a("Enter any text and valid tags: %s. If you want the value of the field without the outputformat or click2search then use %s. The %s tag can also be used for select lists, checkboxes and radiobuttons.",true),'{title}, {alias}, {category}, {fieldtext}, {fieldtitle}, {jr_fieldname}','{jr_fieldname|value}, {jr_fieldname|valuenoimage}','{optionvalue}');?>

                	<?php else:?>

                		<?php echo sprintf(__a("Enter any text and valid tags: %s. If you want the value of the field without the outputformat or click2search then use %s. The %s tag can also be used for select lists, checkboxes and radiobuttons.",true),'{fieldtext}, {fieldtitle}, {jr_fieldname}','{jr_fieldname|value}','{optionvalue}');?>

                	<?php endif;?>

                </div>

            </div>

	        <?php if($this->location == 'content'):?>

	        	<?php if(!in_array($this->type,array('website','relatedlisting'))):?>

	        	<div class="jrGrid jrFieldDiv">

	                <div class="jrCol2">

	                    <label><?php __a("Click2search Output Format");?></label>

	                </div>

	                <div class="jrCol6">

	                    <textarea style="width:100%;" id="params[click2search_format]" name="data[Field][params][click2search_format]"><?php echo $click2search_format?></textarea>

	                </div>

	                <div class="jrCol4">

	                	<?php if($this->location == 'content'):?>

	                		<?php echo sprintf(__a("Enter any text and valid tags: %s.",true),'{url}, {optiontext}, {optionvalue}');?>

	                		<br /><br /><i><?php __a("Default");?>: &lt;a href="{click2searchurl}" target="_blank">{optiontext}&lt;/a&gt;</i>

	                	<?php endif;?>

	                </div>

	            </div>

	        	<?php endif;?>

	        	<div class="jrGrid jrFieldDiv">

	                <div class="jrCol2">

	                    <label><?php __a("Apply Output Format Before Click2Search");?></label>

	                </div>

	                <div class="jrCol6">

	                     <?php echo $Form->radioYesNo( "data[Field][params][formatbeforeclick]", "", (Sanitize::getInt($this->params,'formatbeforeclick',0)));?>

	                </div>

	                <div class="jrCol4">&nbsp;</div>

	            </div>

	        <?php endif;?>

		<?php
	}
}