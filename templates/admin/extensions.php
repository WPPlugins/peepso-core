<?php

$categories = array();
$products = array();
$data = array();

if (is_object($plugins) && count($plugins->products) > 0) {
	foreach ($plugins->products as $product) {
		$product_categories = array();
		$product_tags = array();

		// product data
		$id = $product->info->id;
		$title = addslashes($product->info->title);
		$content = addslashes(wp_strip_all_tags($product->info->content));
		$content = trim(preg_replace('/\s+/', ' ', $content));

		// product categories
		$product->button_label = __('Get this addon', 'peepso-core');

		if (is_array($product->info->category)) {
			foreach ($product->info->category as $category) {
				$product_categories[] = $category->slug;
				$categories[$category->slug] = $category->name;

				if ($category->slug == 'bundles') {
					$product->button_label = __('Get this bundle', 'peepso-core');
				}
			}
		}

		// product tags
		$product->is_installed = FALSE;
		if ($product->info->tags) {
			foreach ($product->info->tags as $tag) {
				$product_tags[] = strtolower($tag->name);

				if (class_exists($tag->slug)) {
					$product->is_installed = TRUE;
				}
			}
		}

		$data[] = array(
			'id' => $id,
			'title' => $title,
			'categories' => $product_categories,
			'tags' => $product_tags,
			'content' => $content
		);
		
		$products[] = $product;
	}
}

// sort category list
asort($categories);
$categories = array_merge(array('all' => 'All'), $categories);

?>

<div class="ps-js-extensions">
	<?php if (count($categories) > 0) { ?>
	<ul class="ps-extensions__tabs ps-js-tabs">
		<div class="ps-extensions__search">
			<input type="text" value="" placeholder="<?php _e('Enter a keyword...', 'peepso-core'); ?>" />
		</div>
		<?php foreach ($categories as $key => $value) { ?>
		<li <?php echo $key === 'all' ? ' class="active"' : ''; ?>>
			<a href="javascript:void(0);" class="plugin-type ps-js-tab" title="<?php echo $value; ?>" data-slug="<?php echo $key; ?>"><?php echo $value; ?></a>
		</li>
		<?php } ?>
		<li class="ps-extensions__hide ps-js-toggle-installed">
			<a href="javascript:void(0);">
				<i class="dashicons dashicons-visibility"></i>
				<span><?php echo __('Hide Installed Plugins', 'peepso-core'); ?></span>
			</a>
		</li>
	</ul>
	<?php } ?>

	<div class="row ps-extensions peepso-extensions ps-js-list">
		<?php

		if (count($products) > 0) {
			foreach ($products as $product) {
				if (!$product->is_installed) {
				?>
				<div class="col-md-4 ps-extension__item ps-js-extension" data-id="<?php echo $product->info->id; ?>">
					<div class="edd-extension">
						<a class="ps-extension__image" title="<?php echo $product->info->title; ?>" target="_blank" href="<?php echo $product->info->link; ?>">
							<img width="880" height="440" title="<?php echo $product->info->title; ?>" alt="<?php echo str_replace(' ', '-', strtolower($product->info->title)) . '-image'; ?>" class="attachment-showcase size-showcase wp-post-image" src="<?php echo $product->info->thumbnail; ?>">
						</a>
						<div class="ps-extension__desc ps-js-description">
							<h3><a title="<?php echo $product->info->title; ?>" target="_blank" href="<?php echo $product->info->link; ?>"><?php echo $product->info->title; ?></a></h3>
							<p><?php echo wp_trim_words($product->info->content, 30); ?></p>
						</div>
						<a class="ps-extension__btn" target="_blank" title="<?php echo $product->info->title; ?>" href="<?php echo $product->info->link; ?>"><?php echo $product->button_label; ?></a>
					</div>
				</div>
				<?php } else { ?>
				<div class="col-md-4 ps-extension__item ps-extension__item--installed ps-js-extension ps-js-installed" data-id="<?php echo $product->info->id; ?>">
					<div class="edd-extension">
						<a class="ps-extension__image" title="<?php echo $product->info->title; ?>" target="_blank" href="<?php echo $product->info->link; ?>">
							<img width="880" height="440" title="<?php echo $product->info->title; ?>" alt="<?php echo str_replace(' ', '-', strtolower($product->info->title)) . '-image'; ?>" class="attachment-showcase size-showcase wp-post-image" src="<?php echo $product->info->thumbnail; ?>">
						</a>
						<div class="ps-extension__desc ps-js-description">
							<h3><a title="<?php echo $product->info->title; ?>" target="_blank" href="<?php echo $product->info->link; ?>"><?php echo $product->info->title; ?></a></h3>
							<p><?php echo wp_trim_words($product->info->content, 30); ?></p>
						</div>
						<span class="ps-extension__btn"><?php _e('Installed and Activated', 'peepso-core'); ?></span>
						<div class="ps-extension__check"><i class="dashicons dashicons-yes"></i></div>
					</div>
				</div>
				<?php

				}
			}
		} else {
			_e('Please try again later', 'peepso-core');
		}

		?>
		<script>
		peepsoextdata = <?php echo json_encode( array(
			'spinner' => PeepSo::get_asset('images/ajax-loader.gif'),
			'extensions' => $data
		)); ?>;
		</script>
		<div class="all-installed col-md-12 ps-js-all-installed" style="display:none;"><?php _e('All plugins in this category are already installed and activated on your site. To view other plugins, not yet installed or activated on your site, click the button below.', 'peepso-core'); ?></div>
	</div>

	<div class="ps-extensions__footer">
		<a target="_blank" title="Browse All Addons" class="button-primary" href="<?php echo $plugin_url; ?>"><?php _e('Browse All Addons', 'peepso-core'); ?></a>
	</div>

</div>
