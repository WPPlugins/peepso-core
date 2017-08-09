<?php
$PeepSoMemberSearch = PeepSoMemberSearch::get_instance();
echo $args['before_widget'];
?>

<div class="ps-widget__wrapper<?php echo $instance['class_suffix']; ?> ps-widget<?php echo $instance['class_suffix']; ?>">
    <div class="ps-tab-bar<?php echo $instance['class_suffix']; ?>">
        <a class="active" href="#"><?php
			if (!empty($instance['title'])) {
				echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
			}
			?></a>
    </div>
    <div class="ps-tab-content<?php echo $instance['class_suffix']; ?>">
        <div class="ps-widget--members">
			<?php
			if (count($instance['list'])) {
				?>
				<ul class="ps-list-thumbnail">
					<?php
					foreach ($instance['list'] as $user) {
						echo '<li class="ps-list-item">';
						$PeepSoMemberSearch->show_online_member($user);
						echo '</li>';
					}
					?>
				</ul>
				<?php
			} else {
				echo "<span class='ps-text--muted'>" . __('No online members', 'peepso-core') . "</span>";
			}
			
			if (isset($instance['totalmember']) && isset($instance['totalmembervalue'])) {
				echo '<span class="ps-widget--members__count">' . __('Members count: ', 'peepso-core') . $instance['totalmembervalue']  .  '</span>';
			}
			?>
        </div>
    </div>
</div>

<?php
echo $args['after_widget'];

// EOF
