<?php
/*
Plugin Name: BU Section Editing
Plugin URI: http://developer.bu.edu/bu-section-editing/
Author: Boston University (IS&T)
Author URI: http://sites.bu.edu/web/
Description: Enhances WordPress content editing workflow by providing section editing groups and permissions
Version: 0.10.0
Text Domain: bu-section-editing
Domain Path: /languages
*/

/**
Copyright 2012 by Boston University

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 **/

/*
@author Gregory Cornelius <gcorne@gmail.com>
@author Mike Burns <mgburns@bu.edu>
@author Andrew Bauer <awbauer@bu.edu>
*/

require_once( dirname( __FILE__ ) . "/src/class-section-capabilities.php" );
require_once( dirname( __FILE__ ) . "/src/class-edit-groups.php" );
require_once( dirname( __FILE__ ) . "/src/class-groups-list.php" );
require_once( dirname( __FILE__ ) . "/src/class-group-permissions.php" );
require_once( dirname( __FILE__ ) . "/src/class-edit-group.php" );
require_once( dirname( __FILE__ ) . "/src/class-groups-admin.php" );
require_once( dirname( __FILE__ ) . "/src/class-groups-admin-ajax.php" );
require_once( dirname( __FILE__ ) . "/src/class-permissions-editor.php" );
require_once( dirname( __FILE__ ) . "/src/class-flat-permissions-editor.php" );
require_once( dirname( __FILE__ ) . "/src/class-hierarchical-permissions-editor.php" );

define( "BUSE_PLUGIN_BASE", realpath( __DIR__ ) );
define( "BUSE_PLUGIN_ENTRYPOINT", realpath( __FILE__ ) );

define( 'BUSE_PLUGIN_PATH', basename( dirname( __FILE__ ) ) );
define( 'BUSE_TEXTDOMAIN', 'bu-section-editing' );

define( 'BUSE_NAV_INSTALL_LINK', 'http://wordpress.org/extend/plugins/bu-navigation/' );
define( 'BUSE_NAV_UPGRADE_LINK', 'http://wordpress.org/extend/plugins/bu-navigation/' );

require_once( dirname( __FILE__ ) . "/src/class-section-editing-plugin.php" );

BU_Section_Editing_Plugin::register_hooks();
