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
			$style_handle = 'radiodj-user-style';
		} else {
			wp_register_style( 'radiodj-style', RDJ_PLUGIN_URL . 'css/radiodj.css', null, RDJ_VERSION );
			wp_enqueue_style( 'radiodj-style' );
			$style_handle = 'radiodj-style';
		}

		$header_bg = self::get_theme_header_image_url();
		if ( $header_bg ) {
			wp_add_inline_style( $style_handle, '.rdj-wrap .rdj-header { background-image: url(' . esc_url( $header_bg ) . '); }' );
		}

		if( get_option('rdj_ajax_updates') || get_option('rdj_axaj_updates') ) {
			wp_enqueue_script( 'radiodj-ajax-update', RDJ_PLUGIN_URL . 'js/radiodj.js', array( 'jquery' ), RDJ_VERSION );
			wp_localize_script( 'radiodj-ajax-update', 'RadioDJ', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		}

		wp_register_script('recaptcha', 'https://www.google.com/recaptcha/api.js');
		add_filter('script_loader_tag', array( 'RadioDJ', 'script_loader_tag_attributes' ), 10, 2);
	}

	/**
	 * Resolve the theme's own "img/header.png" background image, used as
	 * the background for the plugin's section headers (Now:, Coming
	 * Soon:, Recently Played:) to match the theme's own header styling.
	 * Checks the active child theme first, then falls back to the
	 * parent theme, same as the radiodj.css override lookup above.
	 *
	 * @since 0.7.8
	 *
	 * @return string|false The image URL, or false if not found in either theme.
	 */
	public static function get_theme_header_image_url() {
		$relative_path = 'img/header.png';

		if ( is_child_theme() && file_exists( trailingslashit( get_stylesheet_directory() ) . $relative_path ) ) {
			return trailingslashit( get_stylesheet_directory_uri() ) . $relative_path;
		}

		if ( file_exists( trailingslashit( get_template_directory() ) . $relative_path ) ) {
			return trailingslashit( get_template_directory_uri() ) . $relative_path;
		}

		return false;
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
					// Left join against `requests` to show who requested a track
					// and their message, if it was requested. The correlated
					// subquery picks the most recent still-pending request for
					// that song, in case it was requested more than once.
					$sql = $DB->prepare( 
    				"SELECT songs.artist, songs.title, songs.ID, songs.date_played, songs.date_added, queuelist.songID,
    				req.username AS request_username, req.message AS request_message
     				FROM songs 
     				JOIN queuelist ON songs.ID = queuelist.songID 
     				LEFT JOIN requests req ON req.songID = queuelist.songID AND req.played = 0
     					AND req.requested = (
     						SELECT MAX(r2.requested) FROM requests r2
     						WHERE r2.songID = queuelist.songID AND r2.played = 0
     					)
     				WHERE songs.song_type IN ($song_types) 
     				ORDER BY $order 
     				LIMIT 0, %d", 
    				$upcoming_items 
				);
				$suppress = $DB->suppress_errors( true );
				$upcoming = $DB->get_results( $sql );
				$DB->suppress_errors( $suppress );

				if ( $DB->last_error ) {
					// Schema doesn't support the requester join -- fall back to
					// the plain query so the upcoming list still works.
					$sql = $DB->prepare( 
    				"SELECT songs.artist, songs.title, songs.ID, songs.date_played, songs.date_added, queuelist.songID 
     				FROM songs 
     				JOIN queuelist ON songs.ID = queuelist.songID 
     				WHERE songs.song_type IN ($song_types) 
     				ORDER BY $order 
     				LIMIT 0, %d", 
    				$upcoming_items 
					);
					$upcoming = $DB->get_results( $sql );
				}



				} else {
					$sql = $DB->prepare( "SELECT songs.artist, songs.ID, queuelist.songID FROM songs, queuelist WHERE songs.song_type IN ($song_types) AND songs.ID=queuelist.songID " .
							"ORDER BY " . $order . " LIMIT 0,%d", $upcoming_items );
					$upcoming = $DB->get_results( $sql );
				}
				
			}

			$history_items = intval( get_option( 'history_items' ) ) + 1;

			// Left join against `requests` to show who requested a track
			// and their message, for both the current track and recently
			// played list (both come from this same history query). The
			// correlated subquery picks the most recent request for that
			// song made at or before it was actually played.
			$suppress = $DB->suppress_errors( true );
			$sql = $DB->prepare("SELECT h.songID, h.date_played, h.artist, h.title, h.duration, TIMESTAMPDIFF(SECOND, h.date_played, NOW()) AS `since_played`,
				req.username AS request_username, req.message AS request_message
				FROM `history` h
				LEFT JOIN requests req ON req.songID = h.songID
					AND req.requested = (
						SELECT MAX(r2.requested) FROM requests r2
						WHERE r2.songID = h.songID AND r2.requested <= h.date_played
					)
				WHERE h.song_type IN ($song_types) ORDER BY h.date_played DESC LIMIT 0, %d", $history_items);
			$nowplaying = $DB->get_results( $sql );
			$DB->suppress_errors( $suppress );

			if ( $DB->last_error ) {
				// Schema doesn't support the requester join (e.g. no
				// history.songID column) -- fall back to the plain query
				// so the now-playing/recently-played list still works.
				$sql = $DB->prepare("SELECT `date_played`, `artist`, `title`, `duration`, TIMESTAMPDIFF(SECOND, `date_played`, NOW()) AS `since_played` FROM `history` WHERE `song_type` IN ($song_types) ORDER BY `date_played` DESC LIMIT 0, %d", $history_items);
				$nowplaying = $DB->get_results( $sql );
			}

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
			return '<div class="rdj-notice">'.__( 'Empty output from ob_get_clean()', 'radiodj' ).'</div><pre>$output = '.print_r($output, true).'</pre>';
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
			$sql = $DB->prepare( "SELECT `artist`, `title`, `count_played`, `date_played` FROM `songs`" .
			" WHERE `song_type` = 0 ORDER BY `count_played` DESC LIMIT 0,%d", $num_tracks );
			$toptracks = $DB->get_results( $sql );
			if( !empty($toptracks) ) {
				set_transient(  'rdj_top_tracks', $toptracks, 600 );
			}
		}

		ob_start();
		if( empty($toptracks) ) {
			?>
			<div class="rdj-notice"><?php _e( 'Currently there are no results to display.' ); ?></div>
			<?php
		} else {
			require_once(RDJ_PLUGIN_DIR . 'views/toptracks.php');
		}
		$output = ob_get_clean();

		if( empty($output) ) {
			return '<div class="rdj-notice">'.__( 'Empty output from ob_get_clean()', 'radiodj' ).'</div><pre>$output = '.print_r($output, true).'</pre>';
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
			$sql = $DB->prepare( "SELECT `artist`, `album`, COUNT( * ) AS `count_played` FROM `history` WHERE TIMESTAMPDIFF(DAY, `date_played` , NOW()) <= %d" .
			" AND `song_type` = 0 GROUP BY `artist`, `album` ORDER BY `count_played` DESC LIMIT 0,%d", $num_days, $num_albums );
			$topalbums = $DB->get_results( $sql );
			if( !empty($topalbums) ) {
				set_transient( 'rdj_top_albums', $topalbums, 600 );
			}
		}

		ob_start();
		if( empty($topalbums) ) {
			?>
			<div class="rdj-notice"><?php _e( 'Currently there are no results to display.' ); ?></div>
			<?php
		} else {
			require_once(RDJ_PLUGIN_DIR . 'views/topalbums.php');
		}
		$output = ob_get_clean();

		if( empty($output) ) {
			return '<div class="rdj-notice">'.__( 'Empty output from ob_get_clean()', 'radiodj' ).'</div><pre>$output = '.print_r($output, true).'</pre>';
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
			$sql = $DB->prepare( "SELECT `artist`, `count_played` FROM `songs` " .
			" WHERE `song_type` = 0 GROUP BY `artist` ORDER BY `count_played` DESC LIMIT 0,%d", $num_artists );
			$topartists = $DB->get_results( $sql );
			if( !empty($topartists) ) {
				set_transient( 'rdj_top_artists', $topartists, 600 );
			}
		}

		ob_start();
		if( empty($topartists) ) {
			?>
			<div class="rdj-notice"><?php _e( 'Currently there are no results to display.' ); ?></div>
			<?php
		} else {
			require_once(RDJ_PLUGIN_DIR . 'views/topartists.php');
		}
		$output = ob_get_clean();

		if( empty($output) ) {
			return '<div class="rdj-notice">'.__( 'Empty output from ob_get_clean()', 'radiodj' ).'</div><pre>$output = '.print_r($output, true).'</pre>';
		}

		return $output;
	}

	/**
	 * Renders a request-flow message (success or error) with a "return to
	 * list of tracks" button. Wrapped in .rdj-wrap since the plugin's CSS
	 * is entirely scoped under that class -- without it, this content
	 * fell back to the surrounding theme's default styling, which could
	 * mean unreadable text depending on the theme.
	 *
	 * @since 0.7.9
	 *
	 * @param string $message Already-translated message text.
	 * @param string $type    'error' (default) or 'success'.
	 */
	private static function message_with_return_link( $message, $type = 'error' ) {
		if ( 'success' === $type ) {
			$css_class = 'rdj-success';
		} else {
			$css_class = 'rdj-error';
		}

		return '<div class="rdj-wrap rdj-request-result">'
			. '<div class="' . esc_attr( $css_class ) . '">' . $message . '</div>'
			. '<p><a href="?" class="rdj-return rdj-button">' . __( 'Return to list of tracks', 'radiodj' ) . '</a></p>'
			. '</div>';
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
			return '<div class="rdj-requests-not-accepted">' . get_option('rdj_requests_message') . '</div>';
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
				return self::message_with_return_link( __("Sorry, you've reached the request limit. Please try again later.", 'radiodj'), 'error' );
			}
			if( $request_state->already_requested ) {
				return self::message_with_return_link( __("The selected track is already requested. Please try again later, or select another track.", 'radiodj'), 'error' );
			}

			$sql = $DB->prepare( "SELECT `artist`, `title` FROM `songs` WHERE `ID` = %d AND `song_type` IN(" . implode(',', $allowed_types) . ")", $requestid );
			$track = $DB->get_row( $sql );
			if( empty($track) ) {
				return self::message_with_return_link( __('The selected track was not found', 'radiodj'), 'error' );
			}
			require_once(RDJ_PLUGIN_DIR . 'views/request-form.php');

		} else {

			$has_searched = !empty( $searchterm );
			$tracks = array();
			$paginate = '';

			if ( $has_searched ) {

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
$page = ($page < 1) ? 1 : $page;
$prev = $page - 1;
$next = $page + 1;
$lastpage = ceil($total_pages / $limit);
$LastPagem1 = $lastpage - 1;

if ($lastpage > 1) {
    $stages = 3;

    $paginate .= '<div class="rdj-paginate">' . "\n";

    // Previous
    if ($page > 1) {
        $paginate .= '<a class="rdj-paginate-prev" href="' . self::paging_url($prev, $searchterm) . '">' . __('Previous', 'radiodj') . '</a>';
    } else {
        $paginate .= '<span class="rdj-paginate-disabled rdj-paginate-prev">' . __('Previous', 'radiodj') . '</span>';
    }

    // Page numbers
    if ($lastpage < 7 + ($stages * 2)) {
        for ($counter = 1; $counter <= $lastpage; $counter++) {
            if ($counter == $page) {
                $paginate .= '<span class="rdj-paginate-current">' . $counter . '</span>';
            } else {
                $paginate .= '<a class="rdj-paginate-link" href="' . self::paging_url($counter, $searchterm) . '">' . $counter . '</a>';
            }
        }
    } elseif ($lastpage > 5 + ($stages * 2)) {
        if ($page < 1 + ($stages * 2)) {
            for ($counter = 1; $counter < 4 + ($stages * 2); $counter++) {
                if ($counter == $page) {
                    $paginate .= '<span class="rdj-paginate-current">' . $counter . '</span>';
                } else {
                    $paginate .= '<a class="rdj-paginate-link" href="' . self::paging_url($counter, $searchterm) . '">' . $counter . '</a>';
                }
            }
            $paginate .= '<span class="rdj-paginate-ellipsis">...</span>';
            $paginate .= '<a class="rdj-paginate-link" href="' . self::paging_url($LastPagem1, $searchterm) . '">' . $LastPagem1 . '</a>';
            $paginate .= '<a class="rdj-paginate-link" href="' . self::paging_url($lastpage, $searchterm) . '">' . $lastpage . '</a>';

        } elseif ($lastpage - ($stages * 2) > $page && $page > ($stages * 2)) {
            $paginate .= '<a class="rdj-paginate-link" href="' . self::paging_url(1, $searchterm) . '">1</a>';
            $paginate .= '<a class="rdj-paginate-link" href="' . self::paging_url(2, $searchterm) . '">2</a>';
            $paginate .= '<span class="rdj-paginate-ellipsis">...</span>';

            for ($counter = $page - $stages; $counter <= $page + $stages; $counter++) {
                if ($counter == $page) {
                    $paginate .= '<span class="rdj-paginate-current">' . $counter . '</span>';
                } else {
                    $paginate .= '<a class="rdj-paginate-link" href="' . self::paging_url($counter, $searchterm) . '">' . $counter . '</a>';
                }
            }

            $paginate .= '<span class="rdj-paginate-ellipsis">...</span>';
            $paginate .= '<a class="rdj-paginate-link" href="' . self::paging_url($LastPagem1, $searchterm) . '">' . $LastPagem1 . '</a>';
            $paginate .= '<a class="rdj-paginate-link" href="' . self::paging_url($lastpage, $searchterm) . '">' . $lastpage . '</a>';

        } else {
            $paginate .= '<a class="rdj-paginate-link" href="' . self::paging_url(1, $searchterm) . '">1</a>';
            $paginate .= '<a class="rdj-paginate-link" href="' . self::paging_url(2, $searchterm) . '">2</a>';
            $paginate .= '<span class="rdj-paginate-ellipsis">...</span>';

            for ($counter = $lastpage - (2 + ($stages * 2)); $counter <= $lastpage; $counter++) {
                if ($counter == $page) {
                    $paginate .= '<span class="rdj-paginate-current">' . $counter . '</span>';
                } else {
                    $paginate .= '<a class="rdj-paginate-link" href="' . self::paging_url($counter, $searchterm) . '">' . $counter . '</a>';
                }
            }
        }
    }

    // Next
    if ($page < $counter - 1) {
        $paginate .= '<a class="rdj-paginate-next" href="' . self::paging_url($next, $searchterm) . '">' . __('Next', 'radiodj') . '</a>' . "\n";
    } else {
        $paginate .= '<span class="rdj-paginate-disabled rdj-paginate-next">' . __('Next', 'radiodj') . '</span>' . "\n";
    }

    $paginate .= "</div>\n";
}

			}

			require_once(RDJ_PLUGIN_DIR . 'views/request-table.php');

		}
		$output = ob_get_clean();

		if( empty($output) ) {
			return '<div class="rdj-notice">'.__( 'Empty output from ob_get_clean()', 'radiodj' ).'</div><pre>$output = '.print_r($output, true).'</pre>';
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
			return self::message_with_return_link( __('reCAPTCHA validation failed. Are you really a human?', 'radiodj') );
		}

		$request_name = isset($_POST['requsername']) ? wp_unslash($_POST['requsername']) : '';
		$request_msg = isset($_POST['reqmessage'])? wp_unslash($_POST['reqmessage']) : '';
		$request_songID = (int)$_POST['songID'];
		$request_limit = (int)get_option('req_limit');
		$request_IP = get_option('rdj_request_realip', false)? self::real_ipaddr() : getenv('REMOTE_ADDR');

		$sql = $DB->prepare( "SELECT `artist`, `title` FROM `songs` WHERE `ID` = %d", $request_songID );
		$track = $DB->get_row( $sql );
		if( empty($track) ) {
			return self::message_with_return_link( __('The selected track was not found', 'radiodj'), 'error' );
		}

		if( empty($request_name) && get_option('rdj_request_name_field') ) {
			return '<div class="rdj-error">' . __("Please enter your name in order to send the request.", 'radiodj') . '</div>';
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
			return self::message_with_return_link( __("Sorry, you've reached the request limit. Please try again later.", 'radiodj'), 'error' );
		}

		if( $request_state->already_requested ) {
			return self::message_with_return_link( __("The selected track is already requested. Please try again later, or select another track.", 'radiodj'), 'error' );
		}

		$sql = $DB->prepare("INSERT INTO `requests` SET `songID` = %d, `username` = %s, `userIP` = %s, `message` = %s, `requested` = NOW()", $request_songID, $request_name, $request_IP, $request_msg);
		$result = $DB->query( $sql );

		if( $result ) {
			return self::message_with_return_link( __("Your request was succesfully placed.", 'radiodj'), 'success' );
		} else {
			return self::message_with_return_link( __("Unknown error occured. Please try again.", 'radiodj'), 'error' );
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
			return '<div class="rdj-notice">' . __('Currently there are no results to display.', 'radiodj') . "</div>";
		}

		ob_start();
		require_once(RDJ_PLUGIN_DIR . 'views/toprequests.php');
		$output = ob_get_clean();

		if( empty($output) ) {
			return '<div class="rdj-notice">'.__( 'Empty output from ob_get_clean()', 'radiodj' ).'</div><pre>$output = '.print_r($output, true).'</pre>';
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
	 * Format a timestamp as "j F Y" (e.g. "10 April 2024") using the
	 * WordPress site's configured locale. Replaces strftime(), which is
	 * deprecated since PHP 8.1 and removed in PHP 9.
	 *
	 * @since 0.7.1
	 *
	 * @param int $timestamp Unix timestamp
	 * @return string Formatted date
	 */
	public static function format_date( $timestamp ) {
		if ( class_exists( 'IntlDateFormatter' ) ) {
			$locale = str_replace( '_', '-', get_locale() );
			$formatter = new IntlDateFormatter( $locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE, null, null, 'd MMMM yyyy' );
			$formatted = $formatter->format( $timestamp );
			if ( $formatted !== false ) {
				return $formatted;
			}
		}

		// Fallback if intl extension isn't available: WordPress date_i18n() with its own locale.
		return date_i18n( 'j F Y', $timestamp );
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
