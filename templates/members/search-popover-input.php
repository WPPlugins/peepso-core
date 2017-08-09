<div id="ps-member-search-html" class="ps-form">
	<div class="ps-form-row">
        <input value="<?php echo isset($_GET['query']) ? $_GET['query'] : "";?>" type="search" name="query" class="ps-input ps-full" placeholder="<?php _e('Start typing to search', 'peepso-core'); ?>…" />
        <?php wp_nonce_field('member-search', '_wpnonce'); ?>
	</div>
	<div class="ps-padding ps-text--center hidden member-search-notice">
		<?php _e('No results found.', 'peepso-core'); ?>
	</div>
</div>