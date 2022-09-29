<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="rdj-wrap requests">
	<div class="requestcontainer">
		<p><?php printf( __('You are about to request <b>%s</b> by <b>%s</b>'), $track->title, $track->artist ); ?></p>
		<form id="requestform" class="request-form" method="post" action="<?php the_permalink(); ?>">
			<fieldset>
				<legend><?php _e('Please enter request details below', 'radiodj'); ?></legend>
				<?php if( get_option('rdj_request_name_field') ) { ?>
				<p>
					<label for="requsername"><?php _e('Your Name', 'radiodj'); ?></label>
					<input type="text" name="requsername" id="requsername" required />
				</p>
				<?php } ?>
				<p>
					<label for="reqmessage"><?php _e('Message (optional)', 'radiodj'); ?></label>
					<textarea name="reqmessage" id="reqmessage"></textarea>
					<input type="hidden" name="songID" value="<?php echo $requestid; ?>">
				</p>
			<?php if(get_option('rdj_use_recaptcha') && !empty($recaptcha_sitekey) ) { ?>
				<div class="g-recaptcha" data-sitekey="<?php echo esc_attr($recaptcha_sitekey); ?>"></div>
			<?php } ?>
				<p>
					<input type="submit" name="reqsubmit" value="<?php _e('Submit your request', 'radiodj'); ?>" />
				</p>
			</fieldset>
		</form>
	</div>
</div>
