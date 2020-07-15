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

spl_autoload_register(function ($class_name) {
  if ( substr( $class_name, 0, 3 ) !== "BU_" ) {
    return;
  }

  $class_name = str_replace(
    "_",
    "-",
    substr( $class_name, 3 )
  );

  $class_name = strtolower( $class_name );

  $file_name = sprintf(
    "%s/src/class-%s.php",
    dirname( __FILE__ ),
    $class_name
  );

  if ( file_exists( $file_name ) ) {
    require_once $file_name;
  }
});

if ( function_exists( "__autoload" ) ) {
  $autoload_functions = spl_autoload_functions();

  # re-add __autoload() function if it was dropped
  # by spl_autoload_register()
  if ( !in_array( "__autoload", $autoload_functions ) ) {
    spl_autoload_register( "__autoload", true, true );
  }
}

define( "BUSE_PLUGIN_BASE", realpath( __DIR__ ) );
define( "BUSE_PLUGIN_ENTRYPOINT", realpath( __FILE__ ) );

define( 'BUSE_PLUGIN_PATH', basename( dirname( __FILE__ ) ) );
define( 'BUSE_TEXTDOMAIN', 'bu-section-editing' );

define( 'BUSE_NAV_INSTALL_LINK', 'http://wordpress.org/extend/plugins/bu-navigation/' );
define( 'BUSE_NAV_UPGRADE_LINK', 'http://wordpress.org/extend/plugins/bu-navigation/' );

BU_Section_Editing_Plugin::register_hooks();
