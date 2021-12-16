<?php

/*
 * Plugin Name: KGR Tip of the Day
 * Plugin URI: https://github.com/constracti/kgr-day-tip
 * Description: Filters posts of specific tags by the current day.
 * Version: 0.2.1
 * Requires at least: ?
 * Requires PHP: 8.0
 * Author: constracti
 * Author URI: https://github.com/constracti
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: kgr-day-tip
 * Domain Path: /languages
 */

if ( !defined( 'ABSPATH' ) )
	exit;


final class KGRDT {

	// constants

	public static function dir( string $dir = '' ): string {
		return plugin_dir_path( __FILE__ ) . $dir;
	}

	public static function url( string $url = '' ): string {
		return plugin_dir_url( __FILE__ ) . $url;
	}

	// plugin version

	public static function version(): string {
			$plugin_data = get_plugin_data( __FILE__ );
			return $plugin_data['Version'];
	}

	// return json string

	public static function success( string $html ): void {
		header( 'content-type: application/json' );
		exit( json_encode( [
			'html' => $html,
		] ) );
	}

	// build attribute list

	public static function attrs( array $attrs ): string {
		$return = '';
		foreach ( $attrs as $prop => $val ) {
			$return .= sprintf( ' %s="%s"', $prop, $val );
		}
		return $return;
	}

	// nonce

	private static function nonce_action( string $action, string ...$args ): string {
		foreach ( $args as $arg )
			$action .= '_' . $arg;
		return $action;
	}

	public static function nonce_create( string $action, string ...$args ): string {
		return wp_create_nonce( self::nonce_action( $action, ...$args ) );
	}

	public static function nonce_verify( string $action, string ...$args ): void {
		$nonce = KGRDTR::get_str( 'nonce' );
		if ( !wp_verify_nonce( $nonce, self::nonce_action( $action, ...$args ) ) )
			exit( 'nonce' );
	}

	// terms

	public static function get_terms(): array {
		return get_option( 'kgr_day_tip_terms', [] );
	}

	public static function set_terms( array $terms ): void {
		if ( !empty( $terms ) )
			update_option( 'kgr_day_tip_terms', $terms );
		else
			delete_option( 'kgr_day_tip_terms' );
	}

	// post dates

	public static function get_post_dates( WP_Post $post ): array {
		$dates = get_post_meta( $post->ID, 'kgr_day_tip_dates', TRUE );
		if ( $dates === '' )
			return [];
		return explode( ',', $dates );
	}

	public static function set_post_dates( WP_Post $post, array $dates ): void {
		if ( !empty( $dates ) )
			update_post_meta( $post->ID, 'kgr_day_tip_dates', implode( ',', $dates ) );
		else
			delete_post_meta( $post->ID, 'kgr_day_tip_dates' );
	}
}


// require php files
$files = glob( KGRDT::dir( '*.php' ) );
foreach ( $files as $file ) {
        if ( $file !== __FILE__ )
                require_once( $file );
}

// load plugin translations
add_action( 'init', function(): void {
        load_plugin_textdomain( 'kgr-day-tip', FALSE, basename( __DIR__ ) . '/languages' );
} );

// add settings link
add_filter( 'plugin_action_links', function( array $actions, string $plugin_file ): array {
	if ( $plugin_file !== basename( __DIR__ ) . '/' . basename( __FILE__ ) )
		return $actions;
	$actions['settings'] = sprintf( '<a href="%s">%s</a>',
		menu_page_url( 'kgr_day_tip', FALSE ),
		esc_html__( 'Settings', 'kgr-day-tip' )
	);
	return $actions;
}, 10, 2 );

add_action( 'pre_get_posts', function( WP_Query $query ): void {
	if ( is_admin() )
		return;
	if ( !$query->is_archive() )
		return;
	$terms = KGRDT::get_terms();
	if ( empty( $terms ) )
		return;
	if ( !$query->is_category( $terms ) && !$query->is_tag( $terms ) )
		return;
	$query->set( 'orderby', 'rand' );
	$query->set( 'meta_key', 'kgr_day_tip_dates' );
	$query->set( 'meta_compare', 'LIKE' );
	$query->set( 'meta_value', current_time( 'md' ) );
} );
