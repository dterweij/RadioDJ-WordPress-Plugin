<?php
/**
 * RadioDJ DB class
 *
 * Extends WordPress wpdb class to avoid some of its limitations.
 * Some methods and variables are copied unmodified from WordPress 3.9.0
 * to have mysqli compatibility in older verions
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
	 * Whether to use mysqli over mysql.
	 *
	 * @since 3.9.0
	 * @access private
	 * @var bool
	 */
	protected $use_mysqli = false;

	/**
	 * Whether we've managed to successfully connect at some point
	 *
	 * @since 3.9.0
	 * @access private
	 * @var bool
	 */
	private $has_connected = false;

	/**
	 * mysql/myslqi error number
	 *
	 * @since 0.6.0
	 * @access public
	 * @var int
	 */
	var $errno = 0;

	/**
	 * mysql/myslqi error string
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

		/* Use ext/mysqli if it exists and:
		 *  - WP_USE_EXT_MYSQL is defined as false, or
		 *  - We are a development version of WordPress, or
		 *  - We are running PHP 5.5 or greater, or
		 *  - ext/mysql is not loaded.
		 */
		if ( function_exists( 'mysqli_connect' ) ) {
			if ( defined( 'WP_USE_EXT_MYSQL' ) ) {
				$this->use_mysqli = ! WP_USE_EXT_MYSQL;
			} elseif ( version_compare( phpversion(), '5.5', '>=' ) || ! function_exists( 'mysql_connect' ) ) {
				$this->use_mysqli = true;
			} elseif ( version_compare( $GLOBALS['wp_version'], '3.9.0', '<' ) ) {
				//$this->use_mysqli = false;
			}
		}

		// Override privete variable use_mysqli in wpdb
		parent::__set('use_mysqli', $this->use_mysqli);


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
	 * Connect to and select database.
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

		$new_link = defined( 'MYSQL_NEW_LINK' ) ? MYSQL_NEW_LINK : true;
		$client_flags = defined( 'MYSQL_CLIENT_FLAGS' ) ? MYSQL_CLIENT_FLAGS : 0;

		// mysqli_real_connect doesn't support the host param including a port or socket
		// like mysql_connect does. This duplicates how mysql_connect detects a port and/or socket file.
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

		if ( $this->use_mysqli ) {
			$this->dbh = mysqli_init();
			if(defined('MYSQLI_OPT_CONNECT_TIMEOUT'))
				mysqli_options($this->dbh, MYSQLI_OPT_CONNECT_TIMEOUT, 10);

			if ( WP_DEBUG ) {
				mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, null, $port, $socket, $client_flags );
			} else {
				@mysqli_real_connect( $this->dbh, $host, $this->dbuser, $this->dbpassword, null, $port, $socket, $client_flags );
			}

			if ( $this->dbh->connect_errno ) {
				$this->error = mysqli_connect_error();
				$this->errno = mysqli_connect_errno();

				$this->dbh = null;

				/* It's possible ext/mysqli is misconfigured. Fall back to ext/mysql if:
		 		 *  - We haven't previously connected, and
		 		 *  - WP_USE_EXT_MYSQL isn't set to false, and
		 		 *  - ext/mysql is loaded.
		 		 */
				$attempt_fallback = true;

				if ( $this->has_connected ) {
					$attempt_fallback = false;
				} else if ( defined( 'WP_USE_EXT_MYSQL' ) && ! WP_USE_EXT_MYSQL ) {
					$attempt_fallback = false;
				} else if ( ! function_exists( 'mysql_connect' ) ) {
					$attempt_fallback = false;
				}

				if ( $attempt_fallback ) {
					$this->use_mysqli = false;
					// Override privete variable use_mysqli in wpdb
					parent::__set('use_mysqli', $this->use_mysqli);
					$this->db_connect( $allow_bail );
				}
			}
		} else {

			// Catch mysql error hackity-hack
			// Paradoxically, mysql_error returns error only from last *successful* connection only.
			// If second connection fails, mysql_error returns empty string because first connection is still ok.
			$e_level = error_reporting();
			$display_errors = ini_get('display_errors');
			ini_set('display_errors', 1);
			ini_set('mysql.connect_timeout', '10');
			ob_start();
			error_reporting(E_ERROR | E_WARNING | E_NOTICE);

			// Need the error if it occurs no matter what
			$this->dbh = mysql_connect( $this->dbhost, $this->dbuser, $this->dbpassword, $new_link, $client_flags );

			// Get the error from output buffer
			$error = $this->parse_error_hack( ob_get_clean() );
			if( !$this->dbh && !empty($error) ){
				$this->error = $error;
				$this->errno = 9999;
			}
			// Return to previous values
			ini_set('display_errors', $display_errors);
			error_reporting($e_level);

			if( !$this->dbh ) {
				// mysql_error and mysql_error return blanks if there are more than one mysql connection
				$mysql_error = mysql_error();
				$mysql_errno = mysql_errno();

				$this->error = !empty($mysql_error) ? $mysql_error : $this->error;
				$this->errno = $mysql_error ? $mysql_error : $this->errno;
			}
		}
		if ( ! $this->dbh ) {
			return false;
		} else if ( $this->dbh ) {
			$this->has_connected = true;
			// Override private variable in wpdb
			parent::__set('has_connected', $this->has_connected);
			$this->set_charset( $this->dbh );
			$this->set_sql_mode();
			$this->ready = true;
			$this->select( $this->dbname, $this->dbh );

			return true;
		}

		return false;
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
	 * Sets the connection's character set.
	 *
	 * @since 3.1.0
	 *
	 * @param resource $dbh     The resource given by mysql_connect
	 * @param string   $charset The character set (optional)
	 * @param string   $collate The collation (optional)
	 */
	function set_charset( $dbh, $charset = null, $collate = null ) {
		if ( ! isset( $charset ) )
			$charset = $this->charset;
		if ( ! isset( $collate ) )
			$collate = $this->collate;
		if ( $this->has_cap( 'collation' ) && ! empty( $charset ) ) {
			if ( $this->use_mysqli ) {
				if ( function_exists( 'mysqli_set_charset' ) && $this->has_cap( 'set_charset' ) ) {
					mysqli_set_charset( $dbh, $charset );
				} else {
					$query = $this->prepare( 'SET NAMES %s', $charset );
					if ( ! empty( $collate ) )
						$query .= $this->prepare( ' COLLATE %s', $collate );
					mysqli_query( $query, $dbh );
				}
			} else {
				if ( function_exists( 'mysql_set_charset' ) && $this->has_cap( 'set_charset' ) ) {
					mysql_set_charset( $charset, $dbh );
				} else {
					$query = $this->prepare( 'SET NAMES %s', $charset );
					if ( ! empty( $collate ) )
						$query .= $this->prepare( ' COLLATE %s', $collate );
					mysql_query( $query, $dbh );
				}
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
			if ( $this->use_mysqli ) {
				$res = mysqli_query( $this->dbh, 'SELECT @@SESSION.sql_mode' );
			} else {
				$res = mysql_query( 'SELECT @@SESSION.sql_mode', $this->dbh );
			}

			if ( empty( $res ) ) {
				return;
			}

			if ( $this->use_mysqli ) {
				$modes_array = mysqli_fetch_array( $res );
				if ( empty( $modes_array[0] ) ) {
					return;
				}
				$modes_str = $modes_array[0];
			} else {
				$modes_str = mysql_result( $res, 0 );
			}

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

		if ( $this->use_mysqli ) {
			mysqli_query( $this->dbh, "SET SESSION sql_mode='$modes_str'" );
		} else {
			mysql_query( "SET SESSION sql_mode='$modes_str'", $this->dbh );
		}
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
	 * @param resource $dbh Optional link identifier.
	 * @return null Always null.
	 */
	function select( $db, $dbh = null ) {
		if ( is_null($dbh) )
			$dbh = $this->dbh;

		if ( $this->use_mysqli ) {
			$success = @mysqli_select_db( $dbh, $db );
		} else {
			$success = @mysql_select_db( $db, $dbh );
		}
		if ( ! $success ) {
			if ( $this->use_mysqli ) {
				$this->error = mysqli_error( $dbh );
				$this->errno = mysqli_errno( $dbh );
			} else {
				$this->error = mysql_error();
				$this->errno = mysql_errno();
			}
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
		if ( $this->use_mysqli ) {
			if ( @mysqli_ping( $this->dbh ) ) {
				return true;
			}
		} else {
			if ( @mysql_ping( $this->dbh ) ) {
				return true;
			}
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
			if ( $this->use_mysqli ) {
				$mysql_errno = mysqli_errno( $this->dbh );
			} else {
				$mysql_errno = mysql_errno( $this->dbh );
			}
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
		if ( $this->use_mysqli ) {
			$this->last_error = mysqli_error( $this->dbh );
		} else {
			$this->last_error = mysql_error( $this->dbh );
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
			if ( $this->use_mysqli ) {
				$this->rows_affected = mysqli_affected_rows( $this->dbh );
			} else {
				$this->rows_affected = mysql_affected_rows( $this->dbh );
			}
			// Take note of the insert_id
			if ( preg_match( '/^\s*(insert|replace)\s/i', $query ) ) {
				if ( $this->use_mysqli ) {
					$this->insert_id = mysqli_insert_id( $this->dbh );
				} else {
					$this->insert_id = mysql_insert_id( $this->dbh );
				}
			}
			// Return number of rows affected
			$return_val = $this->rows_affected;
		} else {
			$num_rows = 0;
			if ( $this->use_mysqli ) {
				while ( $row = @mysqli_fetch_object( $this->result ) ) {
					$this->last_result[$num_rows] = $row;
					$num_rows++;
				}
			} else {
				while ( $row = @mysql_fetch_object( $this->result ) ) {
					$this->last_result[$num_rows] = $row;
					$num_rows++;
				}
			}

			// Log number of rows the query returned
			// and return number of rows selected
			$this->num_rows = $num_rows;
			$return_val     = $num_rows;
		}

		return $return_val;
	}

	/**
	 * Internal function to perform the mysql_query() call.
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

		if ( $this->use_mysqli ) {
			$this->result = @mysqli_query( $this->dbh, $query );
		} else {
			$this->result = @mysql_query( $query, $this->dbh );
		}
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
		if ( $this->use_mysqli ) {
			$server_info = mysqli_get_server_info( $this->dbh );
		} else {
			$server_info = mysql_get_server_info( $this->dbh );
		}
		return $server_info;
	}

	/**
	 * Parse errnofrom  hacked-caught error
	 *
	 * @since 0.6.0
	 *
	 * @param string $error
	 */
	function parse_error_hack( $error ) {
		// get rid of html
		$error = strip_tags($error);
		// mysql_connect(): Access denied for user 'user'@'localhost' (using password: YES) in
		if( preg_match('/mysql_.+\(\): (.+) in .*/i', $error, $matches) && isset($matches[1]) ) {
			$error = $matches[1];
		}
		return $error;
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
