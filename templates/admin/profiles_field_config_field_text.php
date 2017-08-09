<?php

// Admin - profile field property - <input>

$data_string='';

foreach( $data as $k=>$v ) {
	$data_string .=" $k=\"$v\" ";
}
?>
<input type="text" <?php echo $data_string;?>>

<?php echo $label_after;?>

<button class="button ps-js-btn ps-js-cancel" style="display:none"><?php echo __('Cancel', 'peepso-core'); ?></button>
<button class="button button-primary ps-js-btn ps-js-save" style="display:none"><?php echo __('Save', 'peepso-core'); ?></button>
