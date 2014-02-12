<?php
/**
 * JReviews - Reviews Extension
 * Copyright (C) 2010-2013 ClickFWD LLC
 * This is not free software, do not distribute it.
 * For licencing information visit http://www.reviewsforjoomla.com
 * or contact sales@reviewsforjoomla.com
**/

defined( 'MVC_FRAMEWORK') or die( 'Direct Access to this location is not allowed.' );


class AdminSettingsHelper extends MyHelper
{
	var $helpers = array('Form');

	var $columns_default = array(5,11,8);

	var $columns = array();

	function displayTab($tab_id, $tab_settings, $ModelName = '', $settingsArray = null)
    {
		$defaultIsGlobal = false;

		if(empty($this->columns)) {

			$this->columns = $this->columns_default;
		}

		if(!$settingsArray) {
			$settingsArray = $this->Config;

			$defaultIsGlobal = true;
		}
		?>
		<div id="<?php echo $tab_id;?>">

			<div class="jrDataList">

				<?php foreach($tab_settings AS $header=>$settings):?>

					<?php if($header != ''):?>

					<div class="jrGrid jrDataListHeader"><div class="jrCol12"><?php echo $header;?></div></div>

					<?php endif;?>

					<?php foreach($settings AS $setting): if(empty($setting)) continue;

						$label = $type = $name = $help = $before = $after = $text = $selected = $selected_global = '' ;

						$disable = false;

						$default = null;

						$global = false;

						$options = $attributes = array();

						extract($setting);

						if($disable === true) continue;

						if(strstr($name, 'data[Access]') || strstr($name, 'data[Config]')) {

							$setting = str_replace(array('data[Access][','data[Config][',']'),array(),$name);
						}
						else {

							$setting = $name;

						}

						$selected = Sanitize::getVar($settingsArray,$setting,$global ? -1 : $default);

						if(is_array($selected) && count($selected) == 1 && $selected[0] == -1)
						{
							$selected = -1;
						}

						if($global) {

							if((int)$selected === -1 || $selected === null || $defaultIsGlobal)  {
								$selected = $default;
								$selected_global = -1;
							}

						}
						elseif(is_null($selected) && !is_null($default)) {

							 $selected = $default;
						}

						if($ModelName != '') {

							$name = 'data'.$ModelName.'['.$name.']';
						}

						?>

					<div class="jrGrid24">

						<?php if($type == 'placeholder' || $type == 'separator'):?>

							<?php if($label != '' && $text != ''):?>

								<div class="jrCol5"><span class="jrSeparator"><?php echo $label;?></span></div>

								<div class="jrCol19"><?php echo $text;?></div>

							<?php else:?>

								<div class="jrCol24"><span class="jrSeparator"><?php echo $label;?></span><?php echo $text;?></div>

							<?php endif;?>

						<?php else:?>

						<div class="jrCol<?php echo $this->columns[0];?>"><?php echo $label;?></div>

						<div class="jrCol<?php echo $this->columns[1];?>">

							<?php
							if($global) {

								echo $this->Form->checkbox('global_'.$name,array(-1=>__a("Global",true)),array('value'=>$selected_global,'class'=>'global-cb','data-default'=>is_array($default) ? json_encode($default) : $default, 'label'=>array('style'=>'width:auto;')));
							}
							?>

							<?php echo $before;?>

							<span class="jr-setting">
							<?php

								switch($type)  {

									case 'text':
									case 'textarea':

										$attributes['value'] = $selected;
										echo $this->Form->{$type}(
											$name,
											$attributes
										);
										break;

									case 'select':

										$attributes['style'] = 'width:auto;';

										echo $this->Form->{$type}(
											$name,
											$options,
											$selected,
											$attributes
										);
										break;

									case 'selectmultiple':

										is_array($selected) and $selected = implode(',',$selected);

										$attributes['style'] = 'width:auto;';

										$attributes['multiple'] = 'multiple';

										$attributes['size'] = 8;

										echo $this->Form->select(
											$name,
											$options,
											(!in_array($selected,array('','none')) ? explode(',',$selected) : 'none'),
											$attributes
										);
										break;

									case 'numbers':

										$attributes['style'] = 'width:auto;';

										echo $this->Form->selectNumbers(
											$name,
											$range[0],
											$range[1],
											$range[2],
											$selected,
											$attributes
										);

									break;

									case 'radioYesNo':

										echo $this->Form->{$type}(
											$name,
											$attributes,
											$selected
										);
										break;

									case 'radio':

										$attributes = array_merge(array('value'=>$selected,'div'=>false),$attributes);
										echo $this->Form->{$type}(
											$name,
											$options,
											$attributes
										);
									break;

									case 'checkbox':

										$attributes = array_merge(array('value'=>$selected,'option_class'=>'jrLeft jrCheckboxOption'),$attributes);
										echo '<div class="jrClearfix">'.$this->Form->{$type}(
											$name,
											$options,
											$attributes
										).'</div>';
									break;
								}
							?>
							</span><?php echo $after;?>

						</div>

						<div class="jrCol<?php echo $this->columns[2];?>">

							<?php echo $help;?>&nbsp;

						</div>

						<?php endif;?>

					</div>

					<?php endforeach;?>

				<?php endforeach;?>

            </div>

		</div>
		<?php

		$this->columns = array();
    }
}