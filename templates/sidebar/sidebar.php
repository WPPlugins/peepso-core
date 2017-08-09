<?php
$positions = array(
    'profile_sidebar_top',
    'profile_sidebar_bottom');

$output=array();

foreach($positions as $position) {

    $widgets = $$position;

    if (sizeof($widgets)) {
        foreach ($widgets as $widget) {
            $widget['is_profile_widget'] = TRUE;
            ob_start();
            the_widget($widget['widget_class'], $widget);
            $widget_html = ob_get_clean();
            if(strlen($widget_html)) {
                $output[$position][] = $widget_html;
            }
        }
    }
}

if(count($output)) {
?>
<div class="ps-sidebar">

    <?php
    
    // @TODO Still D.R.Y.

    foreach ($output as $position => $widgets) {
        ?>
        <div class="peepso_sidebar_<?php echo $position; ?>">
            <?php
            foreach($widgets as $widget){
                echo $widget;
            }
            ?>
        </div>
    <?php } ?>
</div>
<?php
}
// EOF