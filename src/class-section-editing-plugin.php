<?php
namespace Secdor;

use \WP_User_Query;
use \WP_User;

/**
 * Plugin entry point
 */
class Section_Editing_Plugin {

  public static $caps;

  const VERSION = '0.9.9';
  const VERSION_OPTION = '_buse_version';

  public static function register_hooks() {
    $file_name = realpath(
      sprintf(
        "%s/secdor-section-editing.php",
        SECDOR_PLUGIN_BASE
      )
    );

    register_activation_hook( $file_name, array( __CLASS__, 'on_activate' ) );

    add_action( 'init', array( __CLASS__, 'l10n' ), 5 );
    add_action( 'init', array( __CLASS__, 'init' ) );
    add_action( 'init', array( __CLASS__, 'add_post_type_support' ), 20 );
    add_action( 'admin_init', array( __CLASS__, 'version_check' ) );

    add_action( 'load-plugins.php', array( __CLASS__, 'repopulate_roles' ) );
    add_action( 'load-themes.php', array( __CLASS__, 'repopulate_roles' ) );

    Edit_Groups::register_hooks();

  }

  public static function l10n() {
    load_plugin_textdomain(
      SECDOR_TEXTDOMAIN,
      false,
      SECDOR_PLUGIN_PATH . '/languages/'
    );
  }

  public static function init() {

    self::$caps = new Section_Capabilities();

    // Roles and capabilities
    add_filter( 'map_meta_cap', array( self::$caps, 'map_meta_cap' ), 10, 4 );

    // Admin requests
    if ( is_admin() ) {
      $dirname = realpath(
        sprintf(
          "%s/src",
          SECDOR_PLUGIN_BASE
        )
      );

      Groups_Admin::register_hooks();
      Groups_Admin_Ajax::register_hooks();

      add_action( 'load-plugins.php', array( __CLASS__, 'load_plugins_screen' ) );
      add_filter( 'plugin_action_links', array( __CLASS__, 'plugin_settings_link' ), 10, 2 );

      // Load support code for the BU Navigation plugin if it's active
      if ( class_exists( '\\BU_Navigation_Plugin' ) ) {
        require_once( SECDOR_PLUGIN_BASE . '/plugin-support/bu-navigation/section-editor-nav.php' );
      }
    }

  }

  /**
   * Look for the BU Navigation plugin when this plugin activates
   */
  public static function on_activate() {

    $msg = '';

    if ( ! class_exists( '\\BU_Navigation_Plugin' ) ) {
      $install_link = sprintf( '<a href="%s">%s</a>', BUSE_NAV_INSTALL_LINK, __( 'BU Navigation plugin', SECDOR_TEXTDOMAIN ) );
      $msg = '<p>' . __( 'The BU Section Editing plugin relies on the BU Navigation plugin for displaying hierarchical permission editors.', SECDOR_TEXTDOMAIN ) . '</p>';
      $msg .= '<p>' . sprintf(
        __( 'Please install and activate the %s in order to set permissions for hierarchical post types.', SECDOR_TEXTDOMAIN ),
      $install_link ) . '</p>';
    } else if ( version_compare( \BU_Navigation_Plugin::VERSION, '1.1', '<' ) ) {
      $upgrade_link = sprintf( '<a href="%s">%s</a>', BUSE_NAV_UPGRADE_LINK, __( 'upgrade your copy of BU Navigation', SECDOR_TEXTDOMAIN ) );
      $msg = '<p>' . __( 'The BU Section Editing plugin relies on the BU Navigation plugin for displaying hierarchical permission editors.', SECDOR_TEXTDOMAIN ) . '</p>';
      $msg .= '<p>' .  __( 'This version of BU Section Editing requires at least version 1.1 of BU Navigation.', SECDOR_TEXTDOMAIN ) . '</p>';
      $msg .= '<p>' . sprintf(
        __( 'Please %s to enable permissions for hierarchical post types.', SECDOR_TEXTDOMAIN ),
      $upgrade_link ) . '</p>';
    }

    if ( $msg ) {
      set_transient( 'buse_nav_dep_nag', $msg );
    }

  }

  /**
   * Check for the BU Navigation plugin when the user vistis the "Plugins" page
   */
  public static function load_plugins_screen() {

    add_action( 'admin_notices', array( __CLASS__, 'plugin_dependency_nag' ) );

  }

  /**
   * Display a notice on the "Plugins" page if a sufficient version of the  BU Navigation plugin is not activated
   */
  public static function plugin_dependency_nag() {

    $notice = get_transient( 'buse_nav_dep_nag' );

    if ( $notice ) {
      echo "<div class=\"error\">$notice</div>\n";
      delete_transient( 'buse_nav_dep_nag' );
    }

  }

  public static function add_post_type_support() {

    // Support posts and pages + all custom post types with show_ui by default
    $post_types = get_post_types( array( 'show_ui' => true, '_builtin' => false ) );
    $post_types = array_merge( $post_types, array( 'post', 'page' ) );

    foreach ( $post_types as $post_type ) {
      add_post_type_support( $post_type, 'section-editing' );
    }

  }

  public static function plugin_settings_link( $links, $file ) {
    if ( $file != plugin_basename( SECDOR_PLUGIN_BASE ) ) {
      return $links;
    }

    $groups_url = admin_url( Groups_Admin::MANAGE_GROUPS_PAGE );
    array_unshift( $links, "<a href=\"$groups_url\" title=\"Manage Section Editing Groups\" class=\"edit\">" . __( 'Manage Groups', SECDOR_TEXTDOMAIN ) . '</a>' );

    return $links;
  }

  /**
   * Checks currently installed plugin version against last version stored in DB,
   * performing upgrades as needed.
   */
  public static function version_check() {

    $version = get_option( self::VERSION_OPTION );

    if ( empty( $version ) ) {
      $version = '0';
    }

    // Check if plugin has been updated (or just installed) and store current version
    if ( version_compare( $version, self::VERSION, '<' ) ) {
      $upgrader = new Section_Editing_Upgrader();
      $upgrader->upgrade( $version );

      // Store new version
      update_option(
        self::VERSION_OPTION,
        self::VERSION
      );
    }

  }

  /**
   * Regenerate roles & capabilities when a plugin is activated or theme as switched
   *
   * Both actions potentially introduce new post types, which require a repopulation of the
   * per-post type section editing caps -- (edit|publish|delete)_in_section
   */
  public static function repopulate_roles() {

    // Look for any query params that signify updates
    if ( array_key_exists( 'activated', $_GET ) || array_key_exists( 'activate', $_GET ) || array_key_exists( 'activate-multi', $_GET ) ) {
      $upgrader = new Section_Editing_Upgrader();
      $upgrader->populate_roles();

    }
  }

  /**
   * Query for all users with the cability to be added to section groups
   */
  public static function get_allowed_users( $query_args = array() ) {

    $defaults = array(
      'search_columns' => array( 'user_login', 'user_nicename', 'user_email' ),
      );

    $query_args = wp_parse_args( $query_args, $defaults );
    $wp_user_query = new WP_User_Query( $query_args );

    $allowed_users = array();

    // Filter blog users by section editing status
    foreach ( $wp_user_query->get_results() as $wp_user ) {
      $edit_user = new Edit_User( $wp_user );

      if ( $edit_user->is_allowed() ) {
        $allowed_users[] = $wp_user;
      }
    }

    return $allowed_users;
  }

  /**
   * Allows developers to opt-out for section editing feature
   */
  public static function get_supported_post_types( $output = 'objects' ) {

    $post_types = get_post_types( array( 'show_ui' => true ), 'objects' );
    $supported_post_types = array();

    foreach ( $post_types as $post_type ) {
      if ( post_type_supports( $post_type->name, 'section-editing' ) ) {

        switch ( $output ) {
          case "names":
            $supported_post_types[] = $post_type->name;
            break;

          case "objects":
          default:
            $supported_post_types[] = $post_type;
            break;
        }
      }
    }

    return $supported_post_types;
  }
}
