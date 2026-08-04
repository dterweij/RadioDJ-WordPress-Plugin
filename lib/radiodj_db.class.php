<?php
/**
 * RadioDJ DB class
 *
 * Extends WordPress wpdb class to avoid some of its limitations.
 * Connects to the RadioDJ database using mysqli only.
 *
 * @package RadioDJ
 * @subpackage Database
 * @since 0.6.0
 */

class radiodj_db extends wpdb {

	/**
	 * The number of times to retry reconnecting before dying.
	 *
	 * @since 0.6.0
	 * @access protected
	 * @see wpdb::check_connection()
	 * @var int
	 */
	protected $reconnect_retries = 0;

	/**
	 * MySQL port number for troubleshooting
	 *
	 * @since 0.6.0
	 * @access protected
	 * @var int
	 */
	var $port = 3306;

	/**
	 * List of RadioDJ tables used by RadioDJ plugin
	 *
	 * @since 0.6.0
	 * @access private
	 * @see wpdb::tables()
	 * @var array
	 */
	var $tables = array( 'category', 'history', 'requests', 'songs', 'subcategory' );

	/**
	 * A list of incompatible SQL modes.
	 *
	 * @since 3.9.0
	 * @access protected
	 * @var array
	 */
	protected $incompatible_modes = array( 'NO_ZERO_DATE', 'ONLY_FULL_GROUP_BY',
		'STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'TRADITIONAL' );

	/**
	 * Always true. Kept for backwards compatibility with code (including the
	 * admin "Verify database settings" screen) that reads $DB->use_mysqli.
	 * This class only ever connects via mysqli.
	 *
	 * @since 3.9.0
	 * @access private
	 * @var bool
	 */
	protected $use_mysqli = true;

	/**
	 * Whether we've managed to successfully connect at some point
	 *
	 * @since 3.9.0
	 * @access private
	 * @var bool
	 */
	private $has_connected = false;

	/**
	 * mysqli error number
	 *
	 * @since 0.6.0
	 * @access public
	 * @var int
	 */
	var $errno = 0;

	/**
	 * mysqli error string
	 *
	 * @since 0.6.0
	 * @access public
	 * @var string
	 */
	var $error = '';

	/**
	 * Connects to the database server and selects a database
	 *
	 * @since 0.6.0
	 *
	 * @param string $dbuser MySQL database user
	 * @param string $dbpassword MySQL database password
	 * @param string $dbname MySQL database name
	 * @param string $dbhost MySQL database host
	 */
	function __construct( $dbuser, $dbpassword, $dbname, $dbhost ) {
		register_shutdown_function( array( $this, '__destruct' ) );

		$this->show_errors();

		$this->init_charset();

		$this->dbuser = $dbuser;
		$this->dbpassword = $dbpassword;
		$this->dbname = $dbname;
		$this->dbhost = $dbhost;

		$this->db_connect(false);
	}

	/**
	 * PHP5 style destructor and will run when database object is destroyed.
	 *
	 * @see wpdb::__construct()
	 * @since 2.0.8
	 * @return bool true
	 */
	function __destruct() {
		return true;
	}

	/**
	 * Connect to and select database, using mysqli.
	 *
	 * If $allow_bail is false, the lack of database connection will need
	 * to be handled manually.
	 *
	 * @since 0.6.0
	 *
	 * @param bool $allow_bail Optional. Allows the function to bail. Default true.
	 * @return bool True with a successful connection, false on failure.
	 */
	function db_connect( $allow_bail = true ) {

		$this->is_mysql = true;

		$client_flags = defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0;

		// mysqli_real_connect doesn't support the host param including a port or socket.
		// This duplicates how mysql_connect used to detect a port and/or socket file.
		$port = null;
		$socket = null;
		$host = $this->dbhost;
		$port_or_socket = strstr( $host, ':' );
		if ( ! empty( $port_or_socket ) ) {
			$host = substr( $host, 0, strpos( $host, ':' ) );
			$port_or_socket = substr( $port_or_socket, 1 );
			if ( 0 !== strpos( $port_or_socket, '/' ) ) {
				$port = intval( $port_or_socket );
				$maybe_socket = strstr( $port_or_socket, ':' );
				if ( ! empty( $maybe_socket ) ) {
					$socket = substr( $maybe_socket, 1 );
				}
			} else {
				$socket = $port_or_socket;
			}
		}

		$this->port = $port ? $port : 3306;

		$this->dbh = mysqli_init();
		if ( defined( 'MYSQLI_OPT_CONNECT_TIMEOUT' ) )
			mysqli_options( $this->dbh, MYSQLI_OPT_CONNECT_TIMEOUT, 10 );

		if ( WP_DEBUG ) {
			mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, null, $this->port, $socket, $client_flags );
		} else {
			@mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, null, $this->port, $socket, $client_flags );
		}

		if ( $this->dbh->connect_errno ) {
			$this->error = mysqli_connect_error();
			$this->errno = mysqli_connect_errno();
			$this->dbh = null;
		}

		if ( ! $this->dbh ) {
			return false;
		}

		$this->has_connected = true;
		// Override private variable in wpdb
		parent::__set('has_connected', $this->has_connected);
		$this->set_charset( $this->dbh );
		$this->set_sql_mode();
		$this->ready = true;
		$this->select( $this->dbname, $this->dbh );

		return true;
	}

	/**
	 * PHP5 style magic getter, used to lazy-load expensive data.
	 *
	 * @since 3.5.0
	 *
	 * @param string $name The private member to get, and optionally process
	 * @return mixed The private member
	 */
	function __get( $name ) {
		if ( 'col_info' == $name )
			$this->load_col_info();

		return $this->$name;
	}

	/**
	 * Magic function, for backwards compatibility
	 *
	 * @since 3.5.0
	 *
	 * @param string $name  The private member to set
	 * @param mixed  $value The value to set
	 */
	function __set( $name, $value ) {
		$this->$name = $value;
	}

	/**
	 * Magic function, for backwards compatibility
	 *
	 * @since 3.5.0
	 *
	 * @param string $name  The private member to check
	 *
	 * @return bool If the member is set or not
	 */
	function __isset( $name ) {
		return isset( $this->$name );
	}

	/**
	 * Magic function, for backwards compatibility
	 *
	 * @since 3.5.0
	 *
	 * @param string $name  The private member to unset
	 */
	function __unset( $name ) {
		unset( $this->$name );
	}

	/**
	 * Set $this->charset and $this->collate
	 *
	 * @since 3.1.0
	 */
	function init_charset() {
		if ( function_exists('is_multisite') && is_multisite() ) {
			$this->charset = 'utf8';
			if ( defined( 'DB_COLLATE' ) && DB_COLLATE )
				$this->collate = DB_COLLATE;
			else
				$this->collate = 'utf8_general_ci';
		} elseif ( defined( 'DB_COLLATE' ) ) {
			$this->collate = DB_COLLATE;
		}

		if ( defined( 'DB_CHARSET' ) )
			$this->charset = DB_CHARSET;
	}

	/**
	 * Sets the connection's character set, using mysqli.
	 *
	 * @since 3.1.0
	 *
	 * @param mysqli $dbh     The connection returned by mysqli_init()/mysqli_real_connect()
	 * @param string $charset The character set (optional)
	 * @param string $collate The collation (optional)
	 */
	function set_charset( $dbh, $charset = null, $collate = null ) {
		if ( ! isset( $charset ) )
			$charset = $this->charset;
		if ( ! isset( $collate ) )
			$collate = $this->collate;
		if ( $this->has_cap( 'collation' ) && ! empty( $charset ) ) {
			if ( function_exists( 'mysqli_set_charset' ) && $this->has_cap( 'set_charset' ) ) {
				mysqli_set_charset( $dbh, $charset );
			} else {
				$query = $this->prepare( 'SET NAMES %s', $charset );
				if ( ! empty( $collate ) )
					$query .= $this->prepare( ' COLLATE %s', $collate );
				mysqli_query( $dbh, $query );
			}
		}
	}

	/**
	 * Change the current SQL mode, and ensure its WordPress compatibility.
	 *
	 * If no modes are passed, it will ensure the current MySQL server
	 * modes are compatible.
	 *
	 * @since 3.9.0
	 *
	 * @param array $modes Optional. A list of SQL modes to set.
	 */
	function set_sql_mode( $modes = array() ) {
		if ( empty( $modes ) ) {
			$res = mysqli_query( $this->dbh, 'SELECT @@SESSION.sql_mode' );

			if ( empty( $res ) ) {
				return;
			}

			$modes_array = mysqli_fetch_array( $res );
			if ( empty( $modes_array[0] ) ) {
				return;
			}
			$modes_str = $modes_array[0];

			if ( empty( $modes_str ) ) {
				return;
			}

			$modes = explode( ',', $modes_str );
		}

		$modes = array_change_key_case( $modes, CASE_UPPER );

		/**
		 * Filter the list of incompatible SQL modes to exclude.
		 *
		 * @since 3.9.0
		 *
		 * @see wpdb::$incompatible_modes
		 *
		 * @param array $incompatible_modes An array of incompatible modes.
		 */
		$incompatible_modes = (array) apply_filters( 'incompatible_sql_modes', $this->incompatible_modes );

		foreach( $modes as $i => $mode ) {
			if ( in_array( $mode, $incompatible_modes ) ) {
				unset( $modes[ $i ] );
			}
		}

		$modes_str = implode( ',', $modes );

		mysqli_query( $this->dbh, "SET SESSION sql_mode='$modes_str'" );
	}

	/**
	 * Selects a database using the current database connection.
	 *
	 * The database name will be changed based on the current database
	 * connection. On failure, the execution will bail and display an DB error.
	 *
	 * @since 0.71
	 *
	 * @param string $db MySQL database name
	 * @param mysqli $dbh Optional link identifier.
	 * @return null Always null.
	 */
	function select( $db, $dbh = null ) {
		if ( is_null($dbh) )
			$dbh = $this->dbh;

		$success = @mysqli_select_db( $dbh, $db );
		if ( ! $success ) {
			$this->error = mysqli_error( $dbh );
			$this->errno = mysqli_errno( $dbh );
			$this->ready = false;
			return;
		}
	}

	/**
	 * Check that the connection to the database is still up. If not, try to reconnect.
	 *
	 * If this function is unable to reconnect, it will forcibly die, or if after the
	 * the template_redirect hook has been fired, return false instead.
	 *
	 * If $allow_bail is false, the lack of database connection will need
	 * to be handled manually.
	 *
	 * @since 3.9.0
	 *
	 * @param bool $allow_bail Optional. Allows the function to bail. Default true.
	 * @return bool True if the connection is up.
	 */

	function check_connection( $allow_bail = true ) {
		if ( @mysqli_ping( $this->dbh ) ) {
			return true;
		}

		$error_reporting = false;

		// Disable warnings, as we don't want to see a multitude of "unable to connect" messages
		if ( WP_DEBUG ) {
			$error_reporting = error_reporting();
			error_reporting( $error_reporting & ~E_WARNING );
		}

		for ( $tries = 1; $tries <= $this->reconnect_retries; $tries++ ) {
			// On the last try, re-enable warnings. We want to see a single instance of the
			// "unable to connect" message on the bail() screen, if it appears.
			if ( $this->reconnect_retries === $tries && WP_DEBUG ) {
				error_reporting( $error_reporting );
			}

			if ( $this->db_connect( false ) ) {
				if ( $error_reporting ) {
					error_reporting( $error_reporting );
				}

				return true;
			}

			sleep( 1 );
		}

		// If template_redirect has already happened, it's too late for wp_die()/dead_db().
		// Let's just return and hope for the best.
		if ( did_action( 'template_redirect' ) ) {
			return false;
		}

		if ( ! $allow_bail ) {
			return false;
		}

		// We weren't able to reconnect, so we better bail.
		$this->bail( sprintf( ( "
<h1>Error reconnecting to the database</h1>
<p>This means that we lost contact with the database server at <code>%s</code>. This could mean your host's database server is down.</p>
<ul>
	<li>Are you sure that the database server is running?</li>
	<li>Are you sure that the database server is not under particularly heavy load?</li>
</ul>
<p>If you're unsure what these terms mean you should probably contact your host. If you still need help you can always visit the <a href='https://wordpress.org/support/'>WordPress Support Forums</a>.</p>
" ), htmlspecialchars( $this->dbhost, ENT_QUOTES ) ), 'db_connect_fail' );

		// Call dead_db() if bail didn't die, because this database is no more. It has ceased to be (at least temporarily).
		dead_db();
	}

	/**
	 * Perform a MySQL database query, using current database connection.
	 *
	 * More information can be found on the codex page.
	 *
	 * @since 0.71
	 *
	 * @param string $query Database query
	 * @return int|false Number of rows affected/selected or false on error
	 */
	function query( $query ) {
		if ( ! $this->ready )
			return false;

		/**
		 * Filter the database query.
		 *
		 * Some queries are made before the plugins have been loaded,
		 * and thus cannot be filtered with this method.
		 *
		 * @since 2.1.0
		 *
		 * @param string $query Database query.
		 */
		$query = apply_filters( 'query', $query );

		$return_val = 0;
		$this->flush();

		// Log how the function was called
		$this->func_call = "\$db->query(\"$query\")";

		// Keep track of the last query for debug..
		$this->last_query = $query;

		$this->_do_query( $query );

		// MySQL server has gone away, try to reconnect
		$mysql_errno = 0;
		if ( ! empty( $this->dbh ) ) {
			$mysql_errno = mysqli_errno( $this->dbh );
		}

		if ( empty( $this->dbh ) || 2006 == $mysql_errno ) {
			if ( $this->check_connection() ) {
				$this->_do_query( $query );
			} else {
				$this->insert_id = 0;
				return false;
			}
		}

		// If there is an error then take note of it..
		if ( ! empty( $this->dbh ) ) {
			$this->last_error = mysqli_error( $this->dbh );
		}

		if ( $this->last_error ) {
			// Clear insert_id on a subsequent failed insert.
			if ( $this->insert_id && preg_match( '/^\s*(insert|replace)\s/i', $query ) )
				$this->insert_id = 0;

			$this->print_error();
			return false;
		}

		if ( preg_match( '/^\s*(create|alter|truncate|drop)\s/i', $query ) ) {
			$return_val = $this->result;
		} elseif ( preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ) {
			$this->rows_affected = mysqli_affected_rows( $this->dbh );
			// Take note of the insert_id
			if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
				$this->insert_id = mysqli_insert_id( $this->dbh );
			}
			// Return number of rows affected
			$return_val = $this->rows_affected;
		} else {
			$num_rows = 0;
			while ( $row = @mysqli_fetch_object( $this->result ) ) {
				$this->last_result[$num_rows] = $row;
				$num_rows++;
			}

			// Log number of rows the query returned
			// and return number of rows selected
			$this->num_rows = $num_rows;
			$return_val     = $num_rows;
		}

		return $return_val;
	}

	/**
	 * Internal function to perform the mysqli_query() call.
	 *
	 * @since 3.9.0
	 *
	 * @access private
	 * @see wpdb::query()
	 *
	 * @param string $query The query to run.
	 */
	private function _do_query( $query ) {
		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$this->timer_start();
		}

		$this->result = @mysqli_query( $this->dbh, $query );
		$this->num_queries++;

		if ( defined( 'SAVEQUERIES' ) && SAVEQUERIES ) {
			$this->queries[] = array( $query, $this->timer_stop(), $this->get_caller() );
		}
	}

	/**
	 * The database version number.
	 *
	 * @since 2.7.0
	 *
	 * @return false|string false on failure, version number on success
	 */
	function db_version() {
		$server_info = $this->get_server_info();
		return preg_replace( '/[^0-9.].*/', '', $server_info );
	}


	/**
	 * Get server software and version info
	 *
	 * @since 0.6.0
	 */
	function get_server_info() {
		return mysqli_get_server_info( $this->dbh );
	}

	/**
	* First half of escaping for LIKE special characters % and _ before preparing for MySQL.
	*
	* This method was added to wpdb in WordPress 4.0.0 and deprecates global like_escape() function
	*
	* @since 0.6.3
	* @access public
	*
	* @param string $text The raw text to be escaped. The input typed by the user should have no
	*                     extra or deleted slashes.
	* @return string Text in the form of a LIKE phrase. The output is not SQL safe. Call $wpdb::prepare()
	*                or real_escape next.
	*/
	public function esc_like( $text ) {
	    return addcslashes( $text, '_%\\' );
	}
}
?>
