<?php

// Admin - profile field property - <select>

$data_string='';

foreach( $data as $k=>$v ) {
	if('options' != $k) {
		$data_string .=" $k=\"$v\" ";
	}
}
?>
<select <?php echo $data_string;?>>
	<?php foreach($data['options'] as $key => $label) { ?>
		<option <?php echo($data['admin_value'] == $key) ? 'selected':'';?> value="<?php echo $key;?>"><?php echo $label;?></option>
	<?php } ?>
</select>

<?php echo $label_after;?>