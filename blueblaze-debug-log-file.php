<?php
/**
 * Blue Blaze Debug Log File
 *
 * @author  Blue Blaze Associates
 * @license GPL-2.0+
 * @link    https://github.com/blueblazeassociates/blueblaze-debug-log-file
 */

/*
 * Plugin Name:       Blue Blaze Debug Log File
 * Plugin URI:        https://github.com/blueblazeassociates/blueblaze-debug-log-file
 * Description:       Allows for a custom location for WordPress's debug.log file.
 * Version:           1.0.1
 * Author:            Blue Blaze Associates
 * Author URI:        http://www.blueblazeassociates.com
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/blueblazeassociates/blueblaze-debug-log-file
 * GitHub Branch:     master
 * Requires WP:       4.7
 * Requires PHP:      7.0
 */

/*
 * Should this plugin be loaded as a mu-plugin?
 */

/**
 * This is a quick, silly class to avoid naming collisions with other WordPress software.
 *
 * Ideally, this should use a proper PHP namespace, but maybe later.
 *
 * @author Ed Gifford
 */
class BlueBlaze_DebugLogFile {
  /**
   * Constant for storing the name of this plugin.
   *
   * @var string
   */
  const PLUGIN_NAME = 'blueblaze-debug-log-file';

  /**
   * Custom error_log function that prepends messages with the name of this plugin.
   *
   * @param string $message
   *
   * @return bool true on success or false on failure
   */
  public static function error( $message ) {
    return error_log( self::PLUGIN_NAME . ': ' . $message );
  }

  /**
   * Custom error_log function that prepends messages with the name of this plugin and only prints
   * if WP_DEBUG is true.
   *
   * @param string $message
   *
   * @return bool true on success or false on failure
   */
  public static function debug( $message ) {
    if ( WP_DEBUG ) {
      return self::error( $message );
    }
    return true;
  }

  /**
   * Try to override the PHP 'error_log' setting.
   *
   * @param string $debug_log_path If set to null/empty, BBA_WP__DEBUG_LOG_FILE will be used. If that is missing, WordPress's default will be inherited.
   */
  public static function change_debug_log( $debug_log_path = null ) {
    // Override default value, if needed and catch any configuration issues.
    if ( empty( $debug_log_path ) && defined( 'BBA_WP__DEBUG_LOG_FILE' ) ) {
      $debug_log_path = BBA_WP__DEBUG_LOG_FILE;
    } else if ( ! defined( 'BBA_WP__DEBUG_LOG_FILE' ) ) {
      self::error( 'change_debug_log(): Function tried to set a default value but \'BBA_WP__DEBUG_LOG_FILE\' is not defined.' );
      return; // SHORT CIRCUT OUT OF METHOD
    } else {
      self::error( 'change_debug_log(): A valid value needs to be passed in or define \'BBA_WP__DEBUG_LOG_FILE\'.' );
      return; // SHORT CIRCUT OUT OF METHOD
    }

    // At this point, $debug_log_path should have some kind of value.
    // Prepare to resolve it to a real path, if needed.
    $resolved_path = $debug_log_path;

    // Check if $resolved_path is a symlink.
    // If it is, resolve to the real path.
    if ( true === is_link( $resolved_path ) ) {
      $resolved_path = readlink( $resolved_path ); // returns false if error occurred.

      if ( false !== $resolved_path ) {
        self::debug( 'change_debug_log(): Detected a symlink and resolved it to a real path: ' . $resolved_path );
      } else {
        self::error( 'change_debug_log(): Detected a symlink but failed to resolve the target: ' . $debug_log_path );
        return; // SHORT CIRCUT OUT OF METHOD
      }
    }

    // Check if $resolved_path is a directory.
    // If it is, set the log file to debug.log.
    if ( true === is_dir( $resolved_path ) ) {
      if ( true === is_writable( $resolved_path ) ) {
        $resolved_path .= '/debug.log';
        ini_set( 'error_log', $resolved_path );
      } else {
        self::error( 'change_debug_log(): Detected a directory but is not writable: ' . $resolved_path );
      }
      return; // SHORT CIRCUT OUT OF METHOD
    }

    // Check if $resolved_path is a file.
    if ( true === is_file( $resolved_path ) ) {
      if ( true === is_writable( $resolved_path ) ) {
        ini_set( 'error_log', $resolved_path );
      } else {
        self::error( 'change_debug_log(): Detected a file but is not writable: ' . $resolved_path );
      }
      return; // SHORT CIRCUT OUT OF METHOD
    }

    // If we're still in this function, it means that the resolved path couldn't be identified as
    // any particular kind of file system object.  In that case, just set the 'error_log' setting
    // to what was passed in and hope for the best.
    self::debug( 'change_debug_log(): Could not determine the exact setting to use.  Here\'s hoping for the best!' );
    ini_set( 'error_log', $resolved_path );
  }
}

BlueBlaze_DebugLogFile::change_debug_log();
