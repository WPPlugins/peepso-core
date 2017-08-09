<?php

// Admin - profile field property - <yes_no_switch>

$data_string='';

foreach( $data as $k=>$v ) {
	$data_string .=" $k=\"$v\" ";
}
//echo "value";
//var_dump($data['value']);
//echo "admin_value";
//var_dump($data['admin_value']);
?>

<input type="checkbox" <?php echo $data_string;?> class="ace ace-switch ace-switch-2"
	<?php echo(is_numeric($data['value'] ) && $data['value'] == $data['admin_value'])  ? 'checked':'';?> />
<label class="lbl" for="<?php echo $data['id'];?>"></label>