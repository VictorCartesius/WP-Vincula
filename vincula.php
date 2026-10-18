<?php
/**
 * Plugin Name: Vincula – Blogroll Friend Links
 * Description: Manage blogroll friend links in the admin and display them anywhere with the [vincula] shortcode.
 * Version: 1.0.0
 * Author: Victor Cartesius
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: vincula
 *
 * @package Vincula
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'VINCULA_VERSION', '1.0.0' );
define( 'VINCULA_PATH', plugin_dir_path( __FILE__ ) );
define( 'VINCULA_URL', plugin_dir_url( __FILE__ ) );

/**
 * Custom post type that stores a single friend link.
 */
define( 'VINCULA_POST_TYPE', 'vinculum' );

require_once VINCULA_PATH . 'includes/class-vincula-admin.php';
require_once VINCULA_PATH . 'includes/class-vincula-public.php';
require_once VINCULA_PATH . 'includes/class-vincula-csv.php';

add_action( 'plugins_loaded', 'vincula_init' );
/**
 * Boot the admin, front-end and import/export components.
 */
function vincula_init() {
    Vincula_Admin::get_instance();
    Vincula_Public::get_instance();
    Vincula_CSV::get_instance();
}

add_action( 'init', 'vincula_maybe_migrate_legacy_data', 20 );
/**
 * Migrate data stored by earlier "Blogroll-FriendLinks" releases.
 *
 * The legacy plugin used the `friend_card` post type and `_friend_card_*`
 * meta keys. Both are renamed here so existing entries keep working.
 * Runs at most once; the flag is stored in the options table.
 */
function vincula_maybe_migrate_legacy_data() {
    if ( get_option( 'vincula_legacy_migrated' ) ) {
        return;
    }

    global $wpdb;

    $legacy_ids = $wpdb->get_col(
        $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'friend_card' )
    );

    foreach ( $legacy_ids as $post_id ) {
        $wpdb->update(
            $wpdb->posts,
            array( 'post_type' => VINCULA_POST_TYPE ),
            array( 'ID' => (int) $post_id )
        );
        clean_post_cache( (int) $post_id );
    }

    $meta_map = array(
        '_friend_card_url'   => '_vincula_url',
        '_friend_card_logo'  => '_vincula_logo',
        '_friend_card_order' => '_vincula_order',
    );

    foreach ( $meta_map as $old_key => $new_key ) {
        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->postmeta} SET meta_key = %s WHERE meta_key = %s",
                $new_key,
                $old_key
            )
        );
    }

    update_option( 'vincula_legacy_migrated', 1 );
}

register_activation_hook( __FILE__, 'vincula_activate' );
/**
 * Run the legacy migration and refresh rewrite rules on activation.
 */
function vincula_activate() {
    vincula_maybe_migrate_legacy_data();
    flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'vincula_deactivate' );
/**
 * Refresh rewrite rules on deactivation.
 */
function vincula_deactivate() {
    flush_rewrite_rules();
}
