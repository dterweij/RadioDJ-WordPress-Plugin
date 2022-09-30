<?php
/*
* RadioDJ Plugin
*
* @package     RadioDJ-WP
* @author      Andis Grosšteins
* @copyright   2016 Andis Grosšteins (axellence.lv)
* @license     GPL-2.0+
*
* @wordpress-plugin
* Plugin Name: RadioDJ Plugin
* Plugin URI: http://www.radiodj.ro/community/index.php?topic=5577&NOTE=THIS_URL_IS_TEMPORARY
* Description: Display RadioDJ now playing songs, requests and statistics on a WordPress site. Based on previous work by Marius Vaida.
* Version: 0.7.1
* Author: Andis Grosšteins
* Author URI: http://axellence.lv/
* License: GPL2+
* License URI: http://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain: radiodj
* Domain Path: /languages/
*/

/*
RadioDJ Plugin(Wordpress Plugin)
Copyright (C) 2013 Marius Vaida {@link http://www.radiodj.ro}
Copyright (C) 2014-2016 Andis Grosšteins {@link http://axellence.lv}

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	exit;
}

define( 'RDJ_VERSION', '0.7.1' );
define( 'RDJ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RDJ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RDJ_LIB_DIR', RDJ_PLUGIN_DIR . 'lib/' );

function plugin_load_textdomain() {
load_plugin_textdomain( 'radiodj', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'plugin_load_textdomain' );

require_once( RDJ_LIB_DIR . 'radiodj_db.class.php' );
require_once( RDJ_LIB_DIR . 'radiodj.class.php' );

add_action( 'init', array( 'RadioDJ', 'init' ) );

if ( is_admin() ) {
	require_once( RDJ_LIB_DIR . 'radiodj-admin.class.php' );
	add_action( 'init', array( 'RadioDJ_Admin', 'init' ) );
}

function radiodj_activate() {
	require_once( RDJ_LIB_DIR . 'radiodj-admin.class.php' );
	RadioDJ_Admin::activate();
}

register_activation_hook( __FILE__, 'radiodj_activate' );
?>
