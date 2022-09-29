<?php
/**
 * RadioDJ class
 *
 * Handles fronted shortcodes and Ajax hooks
 * Some of this code is based on previous work by Marius Vaida {@link http://www.radiodj.ro}
 *
 * @package RadioDJ
 * @since 0.6.0
 */

class RadioDJ {

	/*
	 * Instance of radiodj_db
	 *
	 * @since 0.6.0
	 */
	protected static $DB;

	private static $initiated = false;

	public static function init() {

		if ( !self::$initiated ) {
			self::init_hooks();
			self::add_shortcodes();
		}
		load_plugin_textdomain( 'radiodj', false, RDJ_PLUGIN_DIR . '/languages/' );
	}

	/**
	 * Initialise RadioDJ plugin hooks
	 *
	 * @since 0.6.0
	 */
	public static function init_hooks() {
		self::$initiated = true;
		add_action( 'wp_ajax_nopriv_rdj_now_playing', array( 'RadioDJ', 'nowplaying_ajax' ) );
		add_action( 'wp_ajax_rdj_now_playing', array( 'RadioDJ', 'nowplaying_ajax' ) );
		add_action( 'wp_enqueue_scripts', array( 'RadioDJ', 'enqueue_resources' ) );
	}

	/**
	 * Enqueue RadioDj plugin JS and CSS files
	 *
	 * @since 0.6.0
	 */
	public static function enqueue_resources() {

		// Load radiodj.css from theme or child theme if it exists there
		$file = 'radiodj.css';
		$user_style = false;
		/* If the file exists in the stylesheet (child theme) directory. */
		if ( is_child_theme() && file_exists( trailingslashit( get_stylesheet_directory() ) . $file ) ) {
			$user_style = trailingslashit( get_stylesheet_directory_uri() ) . $file;
		}
		/* If the file exists in the template (parent theme) directory. */
		elseif ( file_exists( trailingslashit( get_template_directory() ) . $file ) ) {
			$user_style = trailingslashit( get_template_directory_uri() ) . $file;
		}

		if( $user_style ) {
			wp_register_style( 'radiodj-user-style', $user_style, null, RDJ_VERSION );
			wp_enqueue_style( 'radiodj-user-style' );
		} else {
			wp_register_style( 'radiodj-style', RDJ_PLUGIN_URL . 'css/radiodj.css', null, RDJ_VERSION );
			wp_enqueue_style( 'radiodj-style' );
		}

		if( get_option('rdj_ajax_updates') || get_option('rdj_axaj_updates') ) {
			wp_enqueue_script( 'radiodj-ajax-update', RDJ_PLUGIN_URL . 'js/radiodj.js', array( 'jquery' ), RDJ_VERSION );
			wp_localize_script( 'radiodj-ajax-update', 'RadioDJ', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		}

		wp_register_script('recaptcha', 'https://www.google.com/recaptcha/api.js');
		add_filter('script_loader_tag', array( 'RadioDJ', 'script_loader_tag_attributes' ), 10, 2);
	}

	/**
	 * Filter hook for adding async and defer attributes to a script tag
	 *
	 * @since 0.7.0
	 */
	public static function script_loader_tag_attributes($tag, $handle) {
		if ( 'recaptcha' !== $handle )
			return $tag;
		return str_replace( ' src', ' async="async" defer="defer" src', $tag );
	}

	/**
	 * Add shortcode hooks
	 *
	 * @since 0.6.0
	 */
	protected static function add_shortcodes() {
		add_shortcode( "now-playing", array('RadioDJ','nowplaying') );
		add_shortcode( "top-tracks", array('RadioDJ','top_tracks') );
		add_shortcode( "top-albums", array('RadioDJ','top_albums') );
		add_shortcode( "top-artists", array('RadioDJ','top_artists') );
		add_shortcode( "track-requests", array('RadioDJ','track_requests') );
		add_shortcode( "top-requested", array('RadioDJ','top_requests') );
	}

	protected static function getDBinstance() {

		if( !self::$DB ) {
			$rdj_user = get_option('rdj_user');
			$rdj_pass = get_option('rdj_pass');
			$rdj_db = get_option('rdj_db');
			$rdj_server = get_option('rdj_server');
			if( !empty($rdj_user) && !empty($rdj_pass) && !empty($rdj_db) && !empty($rdj_server) ) {
				self::$DB = new radiodj_db( $rdj_user, $rdj_pass, $rdj_db, $rdj_server );
			}
		}
		return self::$DB;
	}

	/**
	 * Now playing info shortcode
	 *
	 * @since 0.6.0
	 */
	public static function nowplaying() {

		$upcoming = array();
		$nowplaying = array();
		$nowplaying_cache = get_transient( 'rdj_nowplaying' );

		if( empty($nowplaying_cache) ) {

			$DB = self::getDBinstance();
			if( !$DB->ready ) {
				return get_option('rdj_error');
			}

			$allowed_types = get_option('rdj_nowplaying_track_types', null);

			if( empty($allowed_types) || !is_array($allowed_types) ) {
				$allowed_types = array(0);
			} else {
				// Make sure all $allowed_types array elements are integers
				foreach($allowed_types as &$type) {
					$type = (int)$type;
				}
			}

			$song_types = implode(',', $allowed_types);

			$upcoming_items = (int)get_option('upcoming_items');
			if( $upcoming_items > 0) {
				$order = get_option('shuffle_next') ? 'RAND()' : 'queuelist.ID';

				$upcoming_show_titles = (bool)get_option('rdj_upcoming_show_titles');
				if($upcoming_show_titles) {
					$sql = $DB->prepare( "SELECT songs.artist, songs.title FROM songs, queuelist WHERE songs.song_type IN ($song_types) AND songs.ID=queuelist.songID " .
							"ORDER BY " . $order . " LIMIT 0,%d", $upcoming_items );
					$upcoming = $DB->get_results( $sql );
				} else {
					$sql = $DB->prepare( "SELECT songs.artist FROM songs, queuelist WHERE songs.song_type IN ($song_types) AND songs.ID=queuelist.songID " .
							"ORDER BY " . $order . " LIMIT 0,%d", $upcoming_items );
					$upcoming = $DB->get_col( $sql );
				}
			}

			$history_items = intval( get_option( 'history_items' ) ) + 1;
			$sql = $DB->prepare("SELECT date_played, artist, title, duration, TIMESTAMPDIFF(SECOND, date_played, NOW()) AS since_played FROM history WHERE song_type IN ($song_types) ORDER BY date_played DESC LIMIT 0, %d", $history_items);
			$nowplaying = $DB->get_results( $sql );

			if( !empty( $nowplaying ) ) {
				$current = reset( $nowplaying );
				$cache_expire = floor( $current->duration ) - $current->since_played;
				$cache_expire = $cache_expire > 10 ? $cache_expire : 10;

				if(!headers_sent()) header("X-Debug: caching for {$cache_expire} seconds");

				$to_cache = array(
					'nowplaying'=>$nowplaying,
					'upcoming' => $upcoming
				);
				set_transient( 'rdj_nowplaying', $to_cache, $cache_expire );
			} else {
				return get_option('rdj_error');
			}

		} else {
			if(!headers_sent()) header('X-Debug: got transient');
			$nowplaying = isset($nowplaying_cache['nowplaying'])? $nowplaying_cache['nowplaying'] : array();
			$upcoming = isset($nowplaying_cache['upcoming'])? $nowplaying_cache['upcoming'] : $upcoming;
		}

		$current = array();
		if(is_array($nowplaying) && !empty($nowplaying)){
			$current = array_shift( $nowplaying );
		}

		ob_start();

		require_once(RDJ_PLUGIN_DIR . 'views/nowplaying.php');

		$output = ob_get_clean();

		if( empty($output) ) {
			return '<div class="noticediv">'._e( 'Empty output from ob_get_clean()' ).'</div><pre>$output = '.print_r($output, true).'</pre>';
		}

		return $output;
	}

	/**
	 * Ajax hook wrapper for now playing info
	 *
	 * @since 0.6.0
	 */
	public static function nowplaying_ajax() {
		echo self::nowplaying();
		exit;
	}

	/**
	 * Top tracks shortcode
	 *
	 * @since 0.6.0
	 */
	public static function top_tracks() {
		$toptracks = get_transient( 'rdj_top_tracks' );

		if( false === $toptracks ) {
			$DB = self::getDBinstance();
			if( !$DB->ready ){
				return get_option('rdj_error');
			}

			$num_days = (int)get_option('top_days');
			$num_tracks = (int)get_option('top_tracks');
			$sql = $DB->prepare( "SELECT `artist`, `title`, COUNT(*) AS `count_played` FROM `history` WHERE TIMESTAMPDIFF(DAY, `date_played`, NOW()) <= %d" .
			" AND `song_type` = 0 GROUP BY `title`, `artist` ORDER BY `count_played` DESC LIMIT 0,%d", $num_days, $num_tracks );
			$toptracks = $DB->get_results( $sql );
			if( !empty($toptracks) ) {
				set_transient(  'rdj_top_tracks', $toptracks, 600 );
			}
		}

		ob_start();
		if( empty($toptracks) ) {
			?>
			<div class="noticediv"><?php _e( 'Currently there are no results to display.' ); ?></div>
			<?php
		} else {
			require_once(RDJ_PLUGIN_DIR . 'views/toptracks.php');
		}
		$output = ob_get_clean();

		if( empty($output) ) {
			return '<div class="noticediv">'._e( 'Empty output from ob_get_clean()' ).'</div><pre>$output = '.print_r($output, true).'</pre>';
		}

		return $output;

	}

	/**
	 * Top albums shortcode
	 *
	 * @since 0.6.0
	 */
	public static function top_albums() {
		$topalbums = get_transient( 'rdj_top_albums' );
		if( false === $topalbums ) {
			$DB = self::getDBinstance();
			if( !$DB->ready ){
				return get_option('rdj_error');
			}

			$num_days = (int)get_option('top_days');
			$num_albums = (int)get_option('top_tracks');
			$sql = $DB->prepare( "SELECT `artist`, `album`, COUNT( * ) AS `count_played` FROM `history` WHERE CHAR_LENGTH(`album`) > 0 AND TIMESTAMPDIFF(DAY, `date_played` , NOW()) <= %d" .
			" AND `song_type` = 0 GROUP BY `artist`, `album` ORDER BY `count_played` DESC LIMIT 0,%d", $num_days, $num_albums );
			$topalbums = $DB->get_results( $sql );
			if( !empty($topalbums) ) {
				set_transient( 'rdj_top_albums', $topalbums, 600 );
			}
		}

		ob_start();
		if( empty($topalbums) ) {
			?>
			<div class="noticediv"><?php _e( 'Currently there are no results to display.' ); ?></div>
			<?php
		} else {
			require_once(RDJ_PLUGIN_DIR . 'views/topalbums.php');
		}
		$output = ob_get_clean();

		if( empty($output) ) {
			return '<div class="noticediv">'._e( 'Empty output from ob_get_clean()' ).'</div><pre>$output = '.print_r($output, true).'</pre>';
		}

		return $output;
	}

	/**
	 * Artists top shortcode
	 *
	 * @since 0.6.0
	 */
	public static function top_artists() {
		$topartists = get_transient( 'rdj_top_artists' );
		if( false === $topartists ) {

			$DB = self::getDBinstance();
			if( !$DB->ready ){
				return get_option('rdj_error');
			}

			$num_days = (int)get_option('top_days');
			$num_artists = (int)get_option('top_tracks');
			$sql = $DB->prepare( "SELECT `artist`, COUNT( * ) AS `count_played` FROM `history` WHERE TIMESTAMPDIFF(DAY, `date_played` , NOW()) <= %d" .
			" AND `song_type` = 0 GROUP BY `artist` ORDER BY `count_played` DESC LIMIT 0,%d", $num_days, $num_artists );
			$topartists = $DB->get_results( $sql );
			if( !empty($topartists) ) {
				set_transient( 'rdj_top_artists', $topartists, 600 );
			}
		}

		ob_start();
		if( empty($topartists) ) {
			?>
			<div class="noticediv"><?php _e( 'Currently there are no results to display.' ); ?></div>
			<?php
		} else {
			require_once(RDJ_PLUGIN_DIR . 'views/topartists.php');
		}
		$output = ob_get_clean();

		if( empty($output) ) {
			return '<div class="noticediv">'._e( 'Empty output from ob_get_clean()' ).'</div><pre>$output = '.print_r($output, true).'</pre>';
		}

		return $output;
	}

	/**
	 * Track request shortcode
	 *
	 * @since 0.6.0
	 */
	public static function track_requests() {
		$DB = self::getDBinstance();
		if( !$DB->ready ){
			return get_option('rdj_error');
		}

		if( (int)get_option('rdj_allow_requests', 1) == 0 ) {
			return '<div class="requests-not-accepted">' . get_option('rdj_requests_message') . '</div>';
		}

		$limit = (int)get_option('pg_results');
		$limit = $limit > 1 ? $limit : 10;
		$page = isset( $_GET['pg'] ) ? intval( $_GET['pg'] ) : 0;
		$start = $page ? ( ($page - 1) * $limit ) : 0;
		$where_search = '';

		$searchterm = isset($_GET['searchterm']) ? stripslashes($_GET['searchterm']) : '';
		if( !empty($searchterm) ){
			$search_sql = esc_sql( $DB->esc_like( $searchterm ) );
			$where_search = "AND (s.artist LIKE '%$search_sql%' OR s.title LIKE '%$search_sql%')";
		}

		$request_limit = (int)get_option('req_limit');
		$request_IP = get_option('rdj_request_realip', false)? self::real_ipaddr() : getenv('REMOTE_ADDR');

		if( isset($_POST['songID']) ) {
			return self::place_request();
		}

		$allowed_types = get_option('rdj_request_track_types', null);

		if( empty($allowed_types) || !is_array($allowed_types) ) {
			$allowed_types = array(0);
		} else {
			// Make sure all $allowed_types array elements are integers
			foreach($allowed_types as &$type) {
				$type = (int)$type;
			}
		}

		$rdj_request_limit_time = (int)get_option('rdj_request_limit_time', 1440);
		$limit_duration = $rdj_request_limit_time > 0 ? $rdj_request_limit_time : 1440; // Minutes

		ob_start();
		$requestid = isset($_GET['requestid']) ? intval($_GET['requestid']) : null;
		if( !empty($requestid) ) {

			if( get_option('rdj_use_recaptcha') ) {
				wp_enqueue_script('recaptcha');
			}
			$recaptcha_sitekey = get_option('rdj_recaptcha_sitekey');

			$sql = $DB->prepare( "SELECT
					COUNT(CASE WHEN `userIP` = %s AND TIMESTAMPDIFF(MINUTE, `requested`, NOW()) < %d THEN 1 END) AS userlimit,
					COUNT(CASE WHEN `songID` = %d AND `played` = 0 THEN 1 END) AS already_requested FROM `requests`", $request_IP, $limit_duration, $requestid );
			$request_state = $DB->get_row( $sql );

			if( $request_state->userlimit >= $request_limit ) {
				return '<div class="errordiv">' . __("Sorry, you've reached the request limit. Please try again later.", 'radiodj') . '</div><p>'.sprintf('<a href="?" class="rdj-return">%s</a>', __('Return to list of tracks', 'radiodj')).'</p>';
			}
			if( $request_state->already_requested ) {
				return '<div class="errordiv">' . __("The selected track is already requested. Please try again later, or select another track.", 'radiodj') . '</div><p>'.sprintf('<a href="?" class="rdj-return">%s</a>', __('Return to list of tracks', 'radiodj')).'</p>';
			}

			$sql = $DB->prepare( "SELECT `artist`, `title` FROM `songs` WHERE `ID` = %d AND `song_type` IN(" . implode(',', $allowed_types) . ")", $requestid );
			$track = $DB->get_row( $sql );
			if( empty($track) ) {
				return '<div class="errordiv">' . __('The selected track was not found', 'radiodj') . '</div><p>'.sprintf('<a href="?" class="rdj-return">%s</a>', __('Return to list of tracks', 'radiodj')).'</p>';
			}
			require_once(RDJ_PLUGIN_DIR . 'views/request-form.php');

		} else {
			$sql = "SELECT COUNT(*) AS `pages` FROM `songs` AS s WHERE s.enabled = 1 $where_search AND s.song_type = 0";
			$total_pages = $DB->get_var($sql);

			$sql = "SELECT s.`ID`, s.`artist`, s.`title`, s.`duration`, s.`date_played`, s.`artist_played`,
			TIMESTAMPDIFF(MINUTE, s.`date_played`, NOW()) AS `played_minutes`,
			TIMESTAMPDIFF(MINUTE, s.`artist_played`, NOW()) AS `artist_played_minutes`,
			(CASE WHEN r.played = 0 THEN r.requested END) AS requested,
			TIMESTAMPDIFF(MINUTE, r.requested, NOW()) AS `requested_minutes`,
			q.songID AS in_queue
			FROM `songs` AS s
			LEFT JOIN `requests` AS r ON( s.ID = r.songID )
			LEFT JOIN `queuelist` AS q ON ( s.ID = q.songID )
			WHERE s.enabled = 1 $where_search
			AND s.song_type IN(" . implode(',', $allowed_types) . ")
			ORDER BY s.artist, s.title ASC
			LIMIT $start, $limit";
			$tracks = $DB->get_results($sql);

			// Initial page num setup
			$page = ($page < 1)? 1 : $page;
			$prev = $page - 1;
			$next = $page + 1;
			$lastpage = ceil($total_pages/$limit);
			$LastPagem1 = $lastpage - 1;

			$paginate = '';

			// TODO: Pagination should be moved to template
			if($lastpage > 1) {

				$stages = 3; //how to split the pagination

				$paginate .= '<div class="paginate">' . "\n";
				// Previous
				if ($page > 1) {
					$paginate.= '<a href="' . self::paging_url($prev, $searchterm) . '">' . __('Previous', 'radiodj') . "</a>";
				} else {
					$paginate.= '<span class="disabled">' . __('Previous', 'radiodj') . '</span>';
				}

				// Pages

				if ($lastpage < 7 + ($stages * 2)) {
					for ($counter = 1; $counter <= $lastpage; $counter++) {
						if ($counter == $page){
							$paginate.= '<span class="current">' . $counter . '</span>';
						} else {
							$paginate.= '<a href="' . self::paging_url($counter, $searchterm) . '">' . $counter . '</a>';
						}
					}
				} elseif($lastpage > 5 + ($stages * 2)) {

					// Beginning only hide later pages
					if($page < 1 + ($stages * 2)) {
						for ($counter = 1; $counter < 4 + ($stages * 2); $counter++) {
							if ($counter == $page) {
								$paginate.= '<span class="current">'.$counter.'</span>';
							} else {
								$paginate.= '<a href="' . self::paging_url($counter, $searchterm) . '">' . $counter . '</a>';
							}
						}

						$paginate.= '...';
						$paginate.= '<a href="' . self::paging_url($LastPagem1, $searchterm) . '">' . $LastPagem1 . '</a>';
						$paginate.= '<a href="' . self::paging_url($lastpage, $searchterm) . '">' . $lastpage . '</a>';

					} elseif($lastpage - ($stages * 2) > $page && $page > ($stages * 2)) {

						$paginate.= '<a href="' . self::paging_url(1, $searchterm) . '">1</a>';
						$paginate.= '<a href="' . self::paging_url(2, $searchterm) . '">2</a>';
						$paginate.= '...';

						for ($counter = $page - $stages; $counter <= $page + $stages; $counter++) {
							if ($counter == $page) {
								$paginate.= '<span class="current">' . $counter . '</span>';
							} else {
								$paginate.= '<a href="' . self::paging_url($counter, $searchterm) . '">' . $counter . '</a>';
							}
						}

						$paginate.= '...';
						$paginate.= '<a href="' . self::paging_url($LastPagem1, $searchterm) . '">' . $LastPagem1 . '</a>';
						$paginate.= '<a href="' . self::paging_url($lastpage, $searchterm) . '">' . $lastpage . '</a>';

					} else {

						$paginate.= '<a href="' . self::paging_url(1, $searchterm) . '">1</a>';
						$paginate.= '<a href="' . self::paging_url(2, $searchterm) . '">2</a>';
						$paginate.= '...';

						for ($counter = $lastpage - (2 + ($stages * 2)); $counter <= $lastpage; $counter++) {
							if ($counter == $page) {
								$paginate.= '<span class="current">' . $counter . '</span>';
							} else {
								$paginate.= '<a href="' . self::paging_url($counter, $searchterm) . '">' . $counter . '</a>';
							}
						}
					}
				}

				// Next
				if ($page < $counter - 1) {
					$paginate.= '<a href="' . self::paging_url($next, $searchterm) . '">' . __('Next', 'radiodj') . '</a>' . "\n";
				} else {
					$paginate.= '<span class="disabled">' . __('Next', 'radiodj') . '</span>' . "\n";
				}
				$paginate.= "</div>" . "\n";
			}

			require_once(RDJ_PLUGIN_DIR . 'views/request-table.php');

		}
		$output = ob_get_clean();

		if( empty($output) ) {
			return '<div class="noticediv">'._e( 'Empty output from ob_get_clean()' ).'</div><pre>$output = '.print_r($output, true).'</pre>';
		}

		return $output;

	}

	/**
	 * Generalised method for placing requests
	 * Called from RadioDJ::track_requests and as AJAX hook
	 *
	 * @since 0.6.0
	 */
	public static function place_request() {
		$DB = self::getDBinstance();
		if( !$DB->ready ){
			return get_option('rdj_error');
		}

		if( get_option('rdj_use_recaptcha') && '' != get_option('rdj_recaptcha_secret') && !self::verify_recaptcha() ) {
			return '<div class="errordiv">' . __('reCAPTCHA validation failed. Are you really a human?', 'radiodj') . '</div>'
					.'<p>'.sprintf('<a href="?" class="rdj-return">%s</a>', __('Return to list of tracks')).'</p>';
		}

		$request_name = isset($_POST['requsername']) ? wp_unslash($_POST['requsername']) : '';
		$request_msg = isset($_POST['reqmessage'])? wp_unslash($_POST['reqmessage']) : '';
		$request_songID = (int)$_POST['songID'];
		$request_limit = (int)get_option('req_limit');
		$request_IP = get_option('rdj_request_realip', false)? self::real_ipaddr() : getenv('REMOTE_ADDR');

		$sql = $DB->prepare( "SELECT `artist`, `title` FROM `songs` WHERE `ID` = %d", $request_songID );
		$track = $DB->get_row( $sql );
		if( empty($track) ) {
			return '<div class="errordiv">' . __('The selected track was not found', 'radiodj') . '</div><p>'.sprintf('<a href="?" class="rdj-return">%s</a>', __('Return to list of tracks')).'</p>';
		}

		if( empty($request_name) && get_option('rdj_request_name_field') ) {
			return '<div class="errordiv">' . __("Please enter your name in order to send the request.", 'radiodj') . '</div>';
		}

		if(!get_option('rdj_request_name_field')) {
			$request_name = 'Anonymous';
		}

		$rdj_request_limit_time = (int)get_option('rdj_request_limit_time', 1440);
		$limit_duration = $rdj_request_limit_time > 0 ? $rdj_request_limit_time : 1440; // Minutes

		$sql = $DB->prepare( "SELECT
				COUNT(CASE WHEN `userIP` = %s AND TIMESTAMPDIFF(MINUTE, `requested`, NOW()) < %d THEN 1 END) AS userlimit,
				COUNT(CASE WHEN `songID` = %d AND `played` = 0 THEN 1 END) AS already_requested FROM `requests`", $request_IP, $limit_duration, $request_songID );
		$request_state = $DB->get_row( $sql );

		if( $request_state->userlimit >= $request_limit ) {
			return '<div class="errordiv">' . __("Sorry, you've reached the request limit. Please try again later.", 'radiodj') . '</div><p>'.sprintf('<a href="?" class="rdj-return">%s</a>', __('Return to list of tracks')).'</p>';
		}

		if( $request_state->already_requested ) {
			return '<div class="errordiv">' . __("The selected track is already requested. Please try again later, or select another track.", 'radiodj') . '</div><p>'.sprintf('<a href="?" class="rdj-return">%s</a>', __('Return to list of tracks')).'</p>';
		}

		$sql = $DB->prepare("INSERT INTO `requests` SET `songID` = %d, `username` = %s, `userIP` = %s, `message` = %s, `requested` = NOW()", $request_songID, $request_name, $request_IP, $request_msg);
		$result = $DB->query( $sql );

		if( $result ) {
			return '<div class="noticediv">' . __("Your request was succesfully placed.", 'radiodj') . '</div><p>'.sprintf('<a href="?" class="rdj-return">%s</a>', __('Return to list of tracks')).'</p>';
		} else {
			return '<div class="errordiv">' . __("Unknown error occured. Please try again.", 'radiodj') . '</div><p>'.sprintf('<a href="?" class="rdj-return">%s</a>', __('Return to list of tracks')).'</p>';
		}
	}

	/**
	 * Verify reCAPTCHA
	 *
	 * @since 0.7.0
	 */
	private static function verify_recaptcha() {
		$client_ip = $_SERVER['REMOTE_ADDR'];
		$recaptcha_response = isset( $_POST['g-recaptcha-response'] ) ? esc_attr( $_POST['g-recaptcha-response'] ) : '';

		if(empty($recaptcha_response))
			return false;

		$request = wp_remote_post('https://www.google.com/recaptcha/api/siteverify',
			array(
				'body' => array(
					'secret' => get_option('rdj_recaptcha_secret'),
					'response' => $recaptcha_response,
					'remoteip' => $client_ip
				),
				'headers' => array('Accept: application/json'),
			)
		);

		$response_body = wp_remote_retrieve_body( $request );
		if( empty($response_body) ) {
			return false;
		}

		$result = json_decode( $response_body, true );

		if( !isset($result['success']) ) {
			//var_dump($response_body, $result);
		}

		return isset($result['success']) ? $result['success'] : false;
	}

	/**
	 * Top request shortcode
	 *
	 * @since 0.6.0
	 */
	public static function top_requests() {
		$DB = self::getDBinstance();
		if( !$DB->ready ){
			return get_option('rdj_error');
		}

		$top_days = (int)get_option('top_days');
		$tracks_count = (int)get_option('top_tracks');

		$sql = $DB->prepare( "SELECT artist, title, COUNT(*) AS requests FROM songs AS A INNER JOIN requests AS B ON A.ID = B.songID
		WHERE TIMESTAMPDIFF( DAY, B.requested, NOW() ) <= %d
		GROUP BY A.ID
		ORDER BY requests DESC
		LIMIT 0,%d", $top_days, $tracks_count );
		$tracks = $DB->get_results($sql);
		if( empty($tracks) ) {
			return '<div class="noticediv">' . __('Currently there are no results to display.', 'radiodj') . "</div>";
		}

		ob_start();
		require_once(RDJ_PLUGIN_DIR . 'views/toprequests.php');
		$output = ob_get_clean();

		if( empty($output) ) {
			return '<div class="noticediv">'._e( 'Empty output from ob_get_clean()' ).'</div><pre>$output = '.print_r($output, true).'</pre>';
		}

		return $output;
	}

	/**
	 * Abuse gmdate() to render track duration as hh:mm:ss
	 *
	 * @since 0.6.0
	 *
	 * @param int $seconds Track duration, rounded if float
	 */
	public static function track_duration( $seconds ) {
		return gmdate('H:i:s', round($seconds));
	}

	/**
	 * Determine actual clients IP even behind proxy
	 *
	 * @since 0.6.0
	 */
	public static function real_ipaddr() {
		$ip = '0.0.0.0';

		if (getenv('HTTP_CLIENT_IP'))
			return getenv('HTTP_CLIENT_IP');

		else if(getenv('HTTP_X_FORWARDED_FOR'))
			return getenv('HTTP_X_FORWARDED_FOR');

		else if(getenv('HTTP_X_FORWARDED'))
			return getenv('HTTP_X_FORWARDED');

		else if(getenv('HTTP_FORWARDED_FOR'))
			return getenv('HTTP_FORWARDED_FOR');

		else if(getenv('HTTP_FORWARDED'))
		   return getenv('HTTP_FORWARDED');

		// For sites using CloudFlare
		else if( isset($_SERVER['HTTP_CF_CONNECTING_IP']) && !empty($_SERVER['HTTP_CF_CONNECTING_IP']) )
   			return $_SERVER['HTTP_CF_CONNECTING_IP'];

		else if(getenv('REMOTE_ADDR'))
			return getenv('REMOTE_ADDR');

	}

	/**
	 * Generate query string with two given params
	 *
	 * @since 0.6.0
	 *
	 * @param mixed $page Pagination page
	 * @param mixed $search Search string for retention to subsequent page
	 */
	public static function paging_url($page, $search = '') {
		if( !empty($search) ) {
			$arr_params = array( 'pg' => $page, 'searchterm' => $search );
		} else {
			$arr_params = array( 'pg' => $page );
		}
		return add_query_arg($arr_params);
	}
}
?>
