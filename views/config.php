<?php
if ( !function_exists( 'add_action' ) ) {
	exit;
}
?>
<div class="wrap radiodj">

	<h2><?php esc_html_e( 'RadioDJ Options' , 'radiodj');?></h2>

	<form method="post">
		<h3><?php esc_html_e( 'Database settings' , 'radiodj');?></h3>
		<input type="hidden" name="action" value="radiodj-options" />
		<?php wp_nonce_field(RadioDJ_Admin::NONCE) ?>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="rdj_server"><?php esc_html_e('RadioDJ Server', 'radiodj' ); ?></label></th>
				<td>
					<input name="rdj_server" type="text" id="rdj_server" value="<?php form_option('rdj_server'); ?>" placeholder="<?php esc_attr_e('[IP address or hostname]:[port]', 'radiodj' ); ?>" class="regular-text ltr" required="required" />
					<p class="description"><?php esc_html_e('IP address or hostname of RadioDJ database server. If you are uning non-standard port number, it should be specified after IP address separated by a colon ":"', 'radiodj'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="rdj_user"><?php esc_html_e('Username', 'radiodj'); ?></label></th>
				<td>
					<input name="rdj_user" type="text" id="rdj_user" value="<?php form_option('rdj_user'); ?>" placeholder="<?php esc_attr_e('Username', 'radiodj'); ?>" class="regular-text ltr" required="required" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="rdj_pass"><?php esc_html_e('Password', 'radiodj'); ?></label></th>
				<td>
					<input name="rdj_pass" type="text" id="rdj_pass" value="<?php form_option('rdj_pass'); ?>" placeholder="<?php esc_attr_e('Password', 'radiodj'); ?>" class="regular-text ltr" required="required" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="rdj_db"><?php esc_html_e('Database name', 'radiodj'); ?></label></th>
				<td>
					<input name="rdj_db" type="text" id="rdj_db" value="<?php form_option('rdj_db'); ?>" placeholder="<?php esc_attr_e('Database name', 'radiodj'); ?>" class="regular-text ltr" required="required" />
					<p class="description"><?php _e('Database name used by RadioDJ', 'radiodj'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"></th>
				<td>
					<button type="button" id="rdj_verify" class="button"><?php esc_html_e('Verify database settings', 'radiodj'); ?></button>
					<div id="rdj_verify_response"></div>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="rdj_error"><?php esc_html_e('Offline Message', 'radiodj'); ?></label></th>
				<td>
					<textarea name="rdj_error" id="rdj_error" class="large-text code" rows="2" cols="50"><?php echo esc_html(get_option('rdj_error', RadioDJ_Admin::$default_options['rdj_error'])); ?></textarea>
					<p class="description"><?php esc_html_e('Displayed on page with shortcode, if plugin cannot connect to RadioDJ database.'); ?></p>
				</td>
			</tr>
			<!-- Setting timezone is not needed anymore. The time difference is fixed in SQL.
			<tr>
				<th scope="row"><label for="rdj_timezone"><?php esc_html_e('RadioDJ Timezone', 'radiodj') ?></label></th>
				<td>
					<select id="rdj_timezone" name="rdj_timezone">
						<?php echo wp_timezone_choice(get_option('rdj_timezone')); ?>
					</select>
					<p class="description"><?php esc_html_e('Set this to timezone of your RadioDJ station location. It is used for displaying correct timestamps.'); ?></p>
				</td>
			</tr>
			-->
		</table>
		<?php submit_button(__('Save Settings', 'radiodj')); ?>

		<h3><?php esc_html_e( 'Now Playing Options' , 'radiodj');?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="upcoming_items"><?php esc_html_e('Upcoming Items', 'radiodj') ?></label></th>
				<td>
					<input name="upcoming_items" type="number" min="0" step="1" id="upcoming_items" value="<?php form_option('upcoming_items'); ?>" class="small-text" />

					<input type="hidden" name="rdj_upcoming_show_titles" value="0" />
					<label>
					<input name="rdj_upcoming_show_titles" type="checkbox" id="rdj_upcoming_show_titles" value="1" <?php echo get_option('rdj_upcoming_show_titles')? 'checked="checked"' : ''; ?> />
					<?php esc_html_e('Display artist and title of upcoming tracks', 'radiodj') ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e('AJAX Refresh', 'radiodj') ?></th>
				<td>
					<input type="hidden" name="rdj_ajax_updates" value="0" />
					<label for="rdj_ajax_updates">
					<input name="rdj_ajax_updates" type="checkbox" id="rdj_ajax_updates" value="1" <?php echo get_option('rdj_ajax_updates', RadioDJ_Admin::$default_options['rdj_ajax_updates'])? 'checked="checked"' : ''; ?> />
					<?php esc_html_e('Update now playing info every 10 seconds without reloading page', 'radiodj') ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e('Shuffle Upcoming', 'radiodj') ?></th>
				<td>
					<input type="hidden" name="shuffle_next" value="0" />
					<label for="shuffle_next">
					<input name="shuffle_next" type="checkbox" id="shuffle_next" value="1" <?php echo get_option('shuffle_next', RadioDJ_Admin::$default_options['shuffle_next'])? 'checked="checked"' : ''; ?> />
					<?php esc_html_e('Shuffle upcoming songs', 'radiodj') ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="history_items"><?php esc_html_e('History Items', 'radiodj') ?></th>
				<td>
					<input name="history_items" type="number" min="0" step="1" id="history_items" value="<?php form_option('history_items'); ?>" class="small-text" />
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e('Allowed Track Types', 'radiodj') ?></th>
				<td>
					<?php
					$allowed_types = get_option('rdj_nowplaying_track_types', array(0));
					foreach(RadioDJ_Admin::$track_types as $name => $type) { ?>
						<label><input type="checkbox" name="rdj_nowplaying_track_types[]" value="<?php echo $type; ?>" <?php echo (in_array($type, $allowed_types))? 'checked="checked"' : ''; ?> /> <?php echo $name; ?></label><br>
					<?php
					}
					?>
				</td>
			</tr>

		</table>

		<h3><?php esc_html_e( 'Top Tracks and Albums Display Options' , 'radiodj');?></h3>
		<table class="form-table">
			<tr>
				<th scope="row"><label for="top_tracks"><?php esc_html_e('Top Tracks Items', 'radiodj') ?></label></th>
				<td>
					<input name="top_tracks" type="number" min="1" step="1" id="top_tracks" value="<?php form_option('top_tracks'); ?>" class="small-text" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="top_albums"><?php esc_html_e('Top Albums Items', 'radiodj') ?></label></th>
				<td>
					<input name="top_albums" type="number" min="1" step="1" id="top_albums" value="<?php form_option('top_albums'); ?>" class="small-text" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="top_days"><?php esc_html_e('Days To Build The Top', 'radiodj') ?></label></th>
				<td>
					<input name="top_days" type="number" min="1" step="1" id="top_days" value="<?php form_option('top_days'); ?>" class="small-text" />
				</td>
			</tr>
		</table>

		<h3><?php esc_html_e( 'Requests Options' , 'radiodj');?></h3>
		<table class="form-table requests-options" id="rdj_requests">
			<tr class="rdj-allow-requests">
				<th scope="row"><label for="rdj_allow_requests"><?php esc_html_e('Allow Request Submission', 'radiodj') ?></label></th>
				<td>
					<input type="hidden" name="rdj_allow_requests" value="0" />
					<label for="rdj_allow_requests">
						<input name="rdj_allow_requests" type="checkbox" id="rdj_allow_requests" value="1" <?php echo get_option('rdj_allow_requests', true)? 'checked="checked"' : ''; ?> />
						<?php esc_html_e('Accept requests', 'radiodj') ?>
					</label>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="rdj_requests_message"><?php esc_html_e('Requests not accepted Message', 'radiodj'); ?></label></th>
				<td>
					<textarea name="rdj_requests_message" id="rdj_requests_message" class="large-text code" rows="2" cols="50"><?php echo esc_html( get_option( 'rdj_requests_message', __('We are not accepting requests right now. Please come back later.', 'radiodj') ) ); ?></textarea>
					<p class="description"><?php esc_html_e('Displayed on requests page when requests are not accepted. This field accepts HTML'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e('Track Types to Allow in Requests', 'radiodj') ?></th>
				<td>
					<?php
					$allowed_types = get_option('rdj_request_track_types', array(0));
					foreach(RadioDJ_Admin::$track_types as $name => $type) { ?>
						<label><input type="checkbox" name="request_types[]" value="<?php echo $type; ?>" <?php echo (in_array($type, $allowed_types))? 'checked="checked"' : ''; ?> /> <?php echo $name; ?></label><br>
					<?php
					}
					?>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="pg_results"><?php esc_html_e('Items per Page', 'radiodj') ?></label></th>
				<td>
					<input name="pg_results" type="number" min="1" step="1" id="pg_results" value="<?php form_option('pg_results'); ?>" class="small-text" />
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="track_rep"><?php esc_html_e('Minimum Track Repeat') ?></label></th>
				<td>
					<input name="track_rep" type="number" min="1" step="1" id="track_rep" value="<?php form_option('track_rep'); ?>" class="small-text" /> (<?php _e('minutes') ?>)
					<p class="description"><?php esc_html_e('This value should match RadioDJ rotation rules options'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="artist_rep"><?php esc_html_e('Minimum Artist Repeat') ?></label></th>
				<td>
					<input name="artist_rep" type="number" min="1" step="1" id="artist_rep" value="<?php form_option('artist_rep'); ?>" class="small-text" /> (<?php _e('minutes') ?>)
					<p class="description"><?php esc_html_e('This value should match RadioDJ rotation rules options'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="req_limit"><?php esc_html_e('Request Count Limit') ?></label></th>
				<td>
					<input name="req_limit" type="number" min="1" step="1" id="req_limit" value="<?php form_option('req_limit'); ?>" class="small-text" />
					<p class="description"><?php esc_html_e('Number of allowed requests per IP address'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="rdj_request_limit_time"><?php esc_html_e('Request Limit Time') ?></label></th>
				<td>
					<input name="rdj_request_limit_time" type="number" min="1" id="rdj_request_limit_time" value="<?php echo (int)get_option('rdj_request_limit_time', RadioDJ_Admin::$default_options['rdj_request_limit_time']); ?>" class="small-text" />
					<p class="description"><?php esc_html_e('Time in minutes to look for previous requests from same IP address. Tip: 1 day = 1440 minutes'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="rdj_request_name_field"><?php esc_html_e('Name Field') ?></label></th>
				<td>
					<input type="hidden" name="rdj_request_name_field" value="0" />
					<label for="rdj_request_name_field">
						<input name="rdj_request_name_field" type="checkbox" id="rdj_request_name_field" value="1" <?php echo get_option('rdj_request_name_field')? 'checked="checked"' : ''; ?> />
						<?php esc_html_e('Allow submission of name', 'radiodj') ?>
					</label>
					<p class="description"><?php esc_html_e('Allow and require visitors to enter their name', 'radiodj'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="rdj_request_realip"><?php esc_html_e('Detect Real IP Address') ?></label></th>
				<td>
					<input type="hidden" name="rdj_request_realip" value="0" />
					<label for="rdj_request_realip">
						<input name="rdj_request_realip" type="checkbox" id="rdj_request_realip" value="1" <?php echo get_option('rdj_request_realip', false)? 'checked="checked"' : ''; ?> />
						<?php esc_html_e('Try to detect real IP address of visitors behind proxy', 'radiodj') ?>
					</label>
					<p class="description"><?php esc_html_e('This might be needed if many site visitors are using a proxy server but can allow IP address spoofing to avoid imposed request limits', 'radiodj'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="rdj_use_recaptcha"><?php esc_html_e('reCAPTCHA in request form') ?></label></th>
				<td>
					<input type="hidden" name="rdj_use_recaptcha" value="0" />
					<label for="rdj_use_recaptcha">
						<input name="rdj_use_recaptcha" type="checkbox" id="rdj_use_recaptcha" value="1" <?php echo get_option('rdj_use_recaptcha')? 'checked="checked"' : ''; ?> />
						<?php esc_html_e('Use reCAPTCHA to verify request submission', 'radiodj') ?>
					</label>
					<p class="description"><?php printf(__('You have to <a href="%s">get site and secret key from Google</a> and enter them below to use reCAPTCHA.', 'radiodj'), 'https://www.google.com/recaptcha/admin'); ?></p>
				</td>
			</tr>

			<tr>
				<th scope="row"><label for="rdj_recaptcha_sitekey"><?php esc_html_e('reCAPTCHA site key', 'radiodj'); ?></label></th>
				<td>
					<input name="rdj_recaptcha_sitekey" type="text" id="rdj_recaptcha_sitekey" value="<?php form_option('rdj_recaptcha_sitekey'); ?>" placeholder="<?php esc_attr_e('reCAPTCHA site key', 'radiodj'); ?>" class="regular-text ltr" />
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="rdj_recaptcha_secret"><?php esc_html_e('reCAPTCHA secret key', 'radiodj'); ?></label></th>
				<td>
					<input name="rdj_recaptcha_secret" type="text" id="rdj_recaptcha_secret" value="<?php form_option('rdj_recaptcha_secret'); ?>" placeholder="<?php esc_attr_e('reCAPTCHA secret', 'radiodj'); ?>" class="regular-text ltr" />
				</td>
			</tr>

		</table>
		<?php submit_button(__('Save Settings', 'radiodj')); ?>
	</form>
	<h3><?php esc_html_e('RadioDJ shortcodes', 'radiodj'); ?></h3>
	<p></p>
	<ul class="ul-disc shortcodes">
		<li><?php _e('Now Playing', 'radiodj'); ?> <span>[now-playing]</span></li>
		<li><?php _e('Top Played Tracks', 'radiodj'); ?> <span>[top-tracks]</span></li>
		<li><?php _e('Top Played Albums', 'radiodj'); ?> <span>[top-albums]</span></li>
		<li><?php _e('Top Played Artists', 'radiodj'); ?> <span>[top-artists]</span></li>
		<li><?php _e('Request Section', 'radiodj'); ?> <span>[track-requests]</span></li>
		<li><?php _e('Top Requests', 'radiodj'); ?> <span>[top-requested]</span></li>
	</ul>
<script type="text/javascript" >
jQuery(document).ready(function($) {
	$('button#rdj_verify').click(function(){

		$('#rdj_verify_response').html('<div class="spinner" style="display:block; float:none;"><div>');
		$('button#rdj_verify').attr('disabled', 'disabled');
		var data = {
			'debug': 'on',
			'action': 'rdj_verify_db',
			'_wpnonce': $('#_wpnonce').val(),
			'rdj_server': $('#rdj_server').val(),
			'rdj_db': $('#rdj_db').val(),
			'rdj_user': $('#rdj_user').val(),
			'rdj_pass': $('#rdj_pass').val()
		};

		$.post(ajaxurl, data, function(response) {
			$('#rdj_verify_response').hide().html(response).show('fast');
			$('button#rdj_verify').removeAttr('disabled');
		});
	});

	if($('#rdj_use_recaptcha').attr('checked')) {
		$('#rdj_recaptcha_sitekey').attr('required', 'required');
		$('#rdj_recaptcha_secret').attr('required', 'required');
		$('#rdj_recaptcha_sitekey').removeAttr('readonly');
		$('#rdj_recaptcha_secret').removeAttr('readonly');
	} else {
		$('#rdj_recaptcha_sitekey').attr('readonly', 'readonly');
		$('#rdj_recaptcha_secret').attr('readonly', 'readonly');
		$('#rdj_recaptcha_sitekey').removeAttr('required');
		$('#rdj_recaptcha_secret').removeAttr('required');
	}

	$('#rdj_use_recaptcha').change(function(){
		if($('#rdj_use_recaptcha').attr('checked')) {
			$('#rdj_recaptcha_sitekey').attr('required', 'required');
			$('#rdj_recaptcha_secret').attr('required', 'required');
			$('#rdj_recaptcha_sitekey').removeAttr('readonly');
			$('#rdj_recaptcha_secret').removeAttr('readonly');
		} else {
			$('#rdj_recaptcha_sitekey').attr('readonly', 'readonly');
			$('#rdj_recaptcha_secret').attr('readonly', 'readonly');
			$('#rdj_recaptcha_sitekey').removeAttr('required');
			$('#rdj_recaptcha_secret').removeAttr('required');
		}
	});
});
</script>
</div>
