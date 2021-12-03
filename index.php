<?php

/*
 * Plugin Name: KGR Tip of the Day
 * Plugin URI: https://github.com/constracti/kgr-day-tip
 * Description: Filters posts of specific tags by the current day.
 * Version: 0.1
 * Requires at least: ?
 * Requires PHP: 7.0
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

// add settings page
add_action( 'admin_menu', function(): void {
	$page_title = esc_html__( 'KGR Tip of the Day', 'kgr-day-tip' );
	$menu_title = esc_html__( 'KGR Tip of the Day', 'kgr-day-tip' );
	$capability = 'manage_options';
	$menu_slug = 'kgr-day-tip';
	add_options_page( $page_title, $menu_title, $capability, $menu_slug, function() {
		$tab_list = apply_filters( 'kgr_day_tip_tab_list', [] );
		$tab_curr = KGRDTR::get_word( 'tab', TRUE ) ?? 'settings';
		if ( !array_key_exists( $tab_curr, $tab_list ) )
			exit( 'tab' );
?>
<div class="wrap">
	<h1><?= esc_html__( 'KGR Tip of the Day', 'kgr-day-tip' ) ?></h1>
	<h2 class="nav-tab-wrapper">
<?php
		foreach ( $tab_list as $tab_slug => $tab_name ) {
			$class = [];
			$class[] = 'nav-tab';
			if ( $tab_slug === $tab_curr )
				$class[] = 'nav-tab-active';
				$class = implode( ' ', $class );
				$href = menu_page_url( 'kgr-day-tip', FALSE );
				if ( $tab_slug !== 'settings' )
					$href = add_query_arg( 'tab', $tab_slug, $href );
?>
		<a class="<?= $class ?>" href="<?= $href ?>"><?= esc_html( $tab_name ) ?></a>
<?php
		}
?>
	</h2>
<?php
	do_action( 'kgr_day_tip_tab_html_' . $tab_curr );
?>
</div>
<?php
	} );
} );

// add settings link
add_filter( 'plugin_action_links', function( array $actions, string $plugin_file ): array {
	if ( $plugin_file !== basename( __DIR__ ) . '/' . basename( __FILE__ ) )
		return $actions;
	$actions['settings'] = sprintf( '<a href="%s">%s</a>',
		menu_page_url( 'kgr-day-tip', FALSE ),
		esc_html__( 'Settings', 'kgr-day-tip' )
	);
	return $actions;
}, 10, 2 );

// enqueue admin scripts
add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( $hook_suffix !== 'settings_page_kgr-day-tip' )
		return;
	wp_enqueue_style( 'kgr_day_tip_flex', KGRDT::url( 'flex.css' ), [], KGRDT::version() );
	wp_enqueue_script( 'kgr_day_tip_script', KGRDT::url( 'script.js' ), [ 'jquery' ], KGRDT::version() );
} );
