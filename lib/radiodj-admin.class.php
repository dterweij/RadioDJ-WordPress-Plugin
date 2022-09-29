<?php
/**
 * RadioDJ class
 *
 * Handles WordPress backend tasks
 * Some of this code is based on previous work by Marius Vaida {@link http://www.radiodj.ro}
 *
 * @package RadioDJ
 * @since 0.6.0
 */

class RadioDJ_Admin {

	/*
	 * Nonce for admin verification of requests
	 */
	const NONCE = 'radiodj-nonce';

	private static $initiated = false;

	private static $options_saved = false;

	private static $default_options = array(
		'rdj_server' => '',
		'rdj_db' => 'radiodj',
		'rdj_user' => '',
		'rdj_pass' => '',
		'rdj_error' => 'At the moment the requested information cannot be displayed. Please try again later.',
		'rdj_timezone' => 'Europe/Bucharest',

		'upcoming_items' => 3,
		'rdj_upcoming_show_titles' => 0,
		'shuffle_next' => 1,
		'rdj_ajax_updates' => 1,
		'history_items' => 5,
		'rdj_nowplaying_track_types' => array(0),

		'top_tracks' => 10,
		'top_albums' => 10,
		'top_days' => 7,

		'pg_results' => 25,
		'track_rep' => 60,
		'artist_rep' => 60,
		'req_limit' => 5,
		'rdj_request_limit_units' => 'minutes',
		'rdj_request_limit_time' => 1440,

		'rdj_request_name_field' => true,
		'rdj_request_realip' => false,

		'rdj_allow_requests' => true,
		'rdj_requests_message' => 'We are not accepting requests right now. Please come back later.',

		'rdj_request_track_types' => array(0),

		// ReCAPTCHA
		'rdj_use_recaptcha' => false,
		'rdj_recaptcha_sitekey' => null,
		'rdj_recaptcha_secret' => null,

		// For handling future upgrades
		'radiodj_version' => RDJ_VERSION,

	);

	private static $track_types = array(
		'Music' => 0,
		'Jingle' => 1,
		'Sweeper' => 2,
		'Voiceover' => 3,
		'Commercial' => 4,
		'Internet Stream' => 5,
		'Other' => 6,
		'Variable Duration File' => 7,
		'Podcast' => 8,
		//'Request ???' => 9,
		'News' => 10,
		//'Playlist Event' => 11,
		'File By Date' => 12,
		'Newest From Folder' => 13,
		//'Teaser' => 14
	);

	private static $errors = array();

	public static function init() {
		if ( !self::$initiated ) {
			self::init_hooks();
		}

		if ( isset( $_POST['action'] ) && $_POST['action'] == 'radiodj-options' ) {
			self::save_options();
		}

		// Correct a typo in rdj_ajax_updates option from previous versions
		if( get_option('rdj_axaj_updates', '_unset_') != '_unset_' ) {
			update_option('rdj_ajax_updates', get_option('rdj_axaj_updates'));
			delete_option('rdj_axaj_updates');
		}

		// Display a reminder message about disabled requests
		if( 0 === (int)get_option('rdj_allow_requests') && 0 === (int)get_option('rdj_requests_notice_dismissed') ) {
			self::$errors['requests-disabled'] = __('RadioDJ requests submission is disabled. <a href="#rdj_requests">Check settings</a>', 'radiodj');
		}
	}

	public static function init_hooks() {

		self::$initiated = true;

		add_action( 'admin_init', array( 'RadioDJ_Admin', 'admin_init' ) );
		add_action( 'admin_menu', array( 'RadioDJ_Admin', 'load_menu' ) );

		// Display notices and errors
		add_action( 'admin_notices', array( 'RadioDJ_Admin', 'display_notice' ) );
		add_action( 'admin_notices', array( 'RadioDJ_Admin', 'admin_notice__error' ) );

		// Hook to register CSS and JS resources
		add_action( 'admin_enqueue_scripts', array( 'RadioDJ_Admin', 'enqueue_resources' ) );

		// Register AJAX action hooks
		add_action( 'wp_ajax_rdj_verify_db', array( 'RadioDJ_Admin', 'verify_database' ) );
		add_action( 'wp_ajax_rdj_dismiss_notice', array( 'RadioDJ_Admin', 'dismiss_notice' ) );

		// Register filter to add options link in plugins list
		add_filter( 'plugin_action_links_'.plugin_basename( RDJ_PLUGIN_DIR . 'radiodj.php'), array( 'RadioDJ_Admin', 'plugin_options_link' ) );

	}

	public static function admin_init() {
		load_plugin_textdomain( 'radiodj', false, RDJ_PLUGIN_DIR . '/languages/' );
	}

	public static function load_menu() {
		$hook = add_options_page( __('RadioDJ Options', 'radiodj'), __('RadioDJ Options', 'radiodj'), 'manage_options', 'radiodj_options', array( 'RadioDJ_Admin', 'display_options' ) );
	}

	public static function plugin_options_link( $links ) {
  		$settings_link = '<a href="'.esc_url( admin_url( 'options-general.php?page=radiodj_options' ) ).'">'.__('Options', 'radiodj').'</a>';
  		array_unshift( $links, $settings_link );
  		return $links;
	}

	public static function enqueue_resources( $hook ) {
		if( 'settings_page_radiodj_options' == $hook ){
			wp_register_style('radiodj-admin', RDJ_PLUGIN_URL . 'css/admin.css');
			wp_enqueue_style('radiodj-admin');
			wp_enqueue_script( 'radiodj-admin', RDJ_PLUGIN_URL . 'js/radiodj-admin.js', array( 'jquery' ) );
		}
	}

	public static function display_notice() {
		global $hook_suffix;
		if( 'settings_page_radiodj_options' == $hook_suffix ) {
			if( self::$options_saved ) {
				?>
				<div class="notice notice-success is-dismissible"><p><strong><?php _e('Settings saved', 'radiodj'); ?></strong></p></div>
				<?php
			}
		}
	}

	public static function admin_notice__error() {
		global $hook_suffix;
		if( empty(self::$errors) || 'settings_page_radiodj_options' != $hook_suffix ) {
			return;
		}
		$class = 'notice notice-error is-dismissible';

		foreach( self::$errors as $key => $message ) {
			printf( '<div class="%1$s"><p><strong>%2$s</strong></p></div>', $class . ' '.$key, $message );
		}
	}

 	public static function display_options() {
		$options = self::get_options();
		require_once(RDJ_PLUGIN_DIR . 'views/config.php');
	}

	public static function get_options() {
		$options = array();
		foreach(self::$default_options as $option => $default) {
			$options[$option] = get_option($option, $default);
		}
		return $options;
	}

	public static function save_options() {
		if ( !isset($_POST['_wpnonce']) || !wp_verify_nonce( $_POST['_wpnonce'], self::NONCE ) )
			return false;

		// Delete transients
		$transients = array(
			'rdj_nowplaying',
			'rdj_top_tracks',
			'rdj_top_albums',
			'rdj_top_artists',
		);
		foreach($transients as $transient) {
			delete_transient( $transient );
		}

		$use_recaptcha = isset($_POST['rdj_use_recaptcha']) && $_POST['rdj_use_recaptcha'];

		if( $use_recaptcha && isset($_POST['rdj_recaptcha_sitekey']) && isset($_POST['rdj_recaptcha_secret']) ) {
			// TODO: Validate reCAPTCHA keys
		}

		if(isset($_POST['request_types']) && is_array($_POST['request_types'])) {
			$_POST['rdj_request_track_types'] = $_POST['request_types'];
		} else {
			$_POST['rdj_request_track_types'] = self::$default_options['rdj_request_track_types'];
		}

		if( isset($_POST['rdj_request_track_types']) && $_POST['rdj_request_track_types'] != get_option('rdj_allow_requests') ) {
			delete_option('rdj_requests_notice_dismissed');
		}

		// TODO: add option validation
		foreach(self::$default_options as $option => $default) {
			update_option( $option, isset( $_POST[$option] ) ? $_POST[$option] : $default );
		}
		self::$options_saved = true;
	}

	public static function activate() {
		// Needed for default error message
		load_plugin_textdomain( 'radiodj', false, RDJ_PLUGIN_DIR . '/languages/' );
		foreach(self::$default_options as $option => $default) {
			if( get_option( $option ) === false ){
				update_option( $option, $default );
			}
		}
	}

	/**
	 * AJAX call hook for saving dismissed notices
	 *
	 * @since 0.7.0
	 *
	 */
	public static function dismiss_notice() {
		$notice = isset($_POST['notice']) ? $_POST['notice'] : null;
		if( empty($notice) ) {
			echo '{"error":"Empty notice param"}';
			die();
		}
		update_option('rdj_'.$notice.'_notice_dismissed', time());
		die('{"success":'.time().'}');
	}

	/**
	 * Verify database settings
	 *
	 * @since 0.6.0
	 *
	 */
	public static function verify_database() {
		$rdj_server = isset($_POST['rdj_server']) ? stripcslashes($_POST['rdj_server']) : null;
		$rdj_db = isset($_POST['rdj_db']) ? stripcslashes($_POST['rdj_db']) : null;
		$rdj_user = isset($_POST['rdj_user']) ? stripcslashes($_POST['rdj_user']) : null;
		$rdj_pass = isset($_POST['rdj_pass']) ? stripcslashes($_POST['rdj_pass']) : null;

		switch ( TRUE ) {
			case empty($rdj_server):
				echo '<div class="error"><p><strong>' . __("Please specify server address") . '<strong><p></div>';
				break;
			case empty($rdj_db):
				echo '<div class="error"><p><strong>' . __("Please specify database name") . '<strong><p></div>';
				break;
			case empty($rdj_user):
				echo '<div class="error"><p><strong>' . __("Please specify username") . '<strong><p></div>';
				break;
			case empty($rdj_pass):
				echo '<div class="error"><p><strong>' . __("Please specify password") . '<strong><p></div>';
				break;
		}
		if( empty($rdj_server) || empty($rdj_db) || empty($rdj_user) || empty($rdj_pass) ) {
			exit;
		}

		check_ajax_referer( self::NONCE, '_wpnonce' );

		$timing_start = microtime(true);

		$RDJDB = new radiodj_db( $rdj_user, $rdj_pass, $rdj_db, $rdj_server );

		$connection_timing = round( microtime(true) - $timing_start , 3 );

		echo '<div class="updated"><p><strong>' . sprintf(__('Using %s client to connect', 'radiodj'), ($RDJDB->use_mysqli?'mysqli':'mysql') ). '</strong></p></div>';

		if($RDJDB->has_connected){
			echo '<div class="updated"><p><strong>' . __('Successfully connected to RadioDJ database', 'radiodj') . '</strong></p></div>';
			echo '<div class="updated"><p><strong>' . sprintf( __('Database version: %s', 'radiodj'), $RDJDB->get_server_info() ) . '</strong></p></div>';
		}

		echo '<div class="updated"><p><strong>'.sprintf( __('Connection took %s seconds', 'radiodj'), $connection_timing ) . '</strong></p></div>';

		if ( !$RDJDB->dbh ) {
			echo '<div class="error"><p><strong>'.__('Error connecting to RadioDJ database: ', 'radiodj') . '</strong>' . $RDJDB->error . '</p></div>';
			echo '<div class="error"><p><strong>';
			switch ( $RDJDB->errno ) {
				case 1044:
				case 1045:
					printf( __('Please check your password and verify that user <code>%s</code> is allowed to connect to specified database remotely.', 'radiodj'), htmlspecialchars( $rdj_user, ENT_QUOTES ));
					break;
				case 2002: // CR_CONNECTION_ERROR
				case 2005: // CR_UNKNOWN_HOST
				case 2006: // CR_SERVER_GONE_ERROR
					echo __('Please make sure that you are using correct IP address and port number.', 'radiodj').'<br>';
					_e('Either your webhost is blocking outgoing MySQL connections or MySQL is not listening on given IP address and port.', 'radiodj');
					break;
				case 9999:
					_e('Unknown errno (9999) or no error description available because of existing mysql connection.', 'radiodj');
					break;
				default:
					_e('Unknown mysql error occured', 'radiodj');
			}
			echo '</strong></p></div>';

		} elseif ( $RDJDB->has_connected && $RDJDB->errno ) {
			echo '<div class="error">';
			echo '<p><strong>' . __('Error selecting database: ', 'radiodj') . '</strong>' . $RDJDB->error . '</p>';
			echo '<p><strong>' . __('Please verify that you are using correct database name', 'radiodj') . '</strong></p>';
			echo '</div>';

		} else {
			$lastsong = $RDJDB->get_row("SELECT `date_played`, `artist`, `title` FROM `history` WHERE `song_type` = 0 ORDER BY `date_played` DESC LIMIT 1");
			if ( !$lastsong ) {
				if( $RDJDB->last_error ) {
					echo '<div class="error"><p><strong>' . $RDJDB->last_error . '</strong></p></div>';
					echo '<div class="error"><p><strong>' . sprintf( __('Error querying last played song. Probably <code>%s</code> is not RadioDJ database.', 'radiodj'), $rdj_db ) . '</strong></p></div>';
				} else {
					echo '<div class="error"><p><strong>' . __('Could not get last played song. Have you enabled history in RadioDJ?', 'radiodj') . '</strong></p>';
					echo '<p><strong>' . __("Now playing, top tracks and top albums won't function if history is disabled.", 'radiodj') . '</strong></p>';
					echo '</div>';
				}
			} else {
				echo '<div class="updated"><p><strong>' . sprintf( __('Last played song: %s at %s', 'radiodj'), htmlspecialchars( $lastsong->artist." - ".$lastsong->title, ENT_QUOTES ), $lastsong->date_played ) . '</strong></p></div>';
			}
		}
		die();
	}
}
?>
