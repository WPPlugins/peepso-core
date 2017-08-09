<?php
$PeepSoProfile = PeepSoProfile::get_instance();
$PeepSoUser = $PeepSoProfile->user;
?>
<?php

    foreach ($links as $priority_number => $links) {
        foreach ($links as $link) {
            ?><a class="ps-focus__menu-item <?php

            if ($current == $link['id']) {
                echo ' current ';
            }

            ?>
                " href="<?php echo $PeepSoUser->get_profileurl() . $link['href'];?>">
                    <i class="ps-icon-<?php echo $link['icon'];?>"></i>
                    <span><?php echo $link['title'];?></span>
            </a><?php
        }
    }

?>
<a href="javascript:" class="ps-focus__menu-item ps-js-focus-link-more" style="display:none">
    <i class="ps-icon-caret-down"></i>
    <span>
        <span><?php echo __('More', 'peepso-core'); ?></span>
        <span class="ps-icon-caret-down"></span>
    </span>
</a>
<div style="position:relative; display:inline">
    <ul class="ps-dropdown-menu ps-js-focus-link-dropdown" style="left:auto; right:0"></ul>
</div>
