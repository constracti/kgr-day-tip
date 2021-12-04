<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'admin_menu', function(): void {
	$page_title = esc_html__( 'KGR Tip of the Day', 'kgr-day-tip' );
	$menu_title = esc_html__( 'KGR Tip of the Day', 'kgr-day-tip' );
	$capability = 'manage_options';
	$menu_slug = 'kgr_day_tip';
	$callback = [ 'KGR_Day_Tip_Settings', 'home_echo' ];
	add_options_page( $page_title, $menu_title, $capability, $menu_slug, $callback );
} );

add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( $hook_suffix !== 'settings_page_kgr_day_tip' )
		return;
	wp_enqueue_style( 'kgr_day_tip_flex', KGRDT::url( 'flex.css' ), [], KGRDT::version() );
	wp_enqueue_script( 'kgr_day_tip_script', KGRDT::url( 'script.js' ), [ 'jquery' ], KGRDT::version() );
} );

final class KGR_Day_Tip_Settings {

	public static function home(): string {
		$terms = KGRDT::get_terms();
		$cats = get_terms( [
			'taxonomy' => 'category',
			'orderby' => 'name',
			'order' => 'ASC',
			'hide_empty' => FALSE,
		] );
		$tags = get_terms( [
			'taxonomy' => 'post_tag',
			'orderby' => 'name',
			'order' => 'ASC',
			'hide_empty' => FALSE,
		] );
		$html = '<div class="kgr-day-tip-home kgr-day-tip-flex-col kgr-day-tip-root" style="margin: 0 -16px;">' . "\n";
		$html .= '<div class="kgr-day-tip-flex-row kgr-day-tip-flex-justify-between kgr-day-tip-flex-align-center">' . "\n";
		$html .= self::refresh_button();
		$html .= '<span class="kgr-day-tip-spinner kgr-day-tip-leaf spinner" data-kgr-day-tip-spinner-toggle="is-active"></span>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<hr class="kgr-day-tip-leaf" />' . "\n";
		$html .= '<div class="kgr-day-tip-flex-row kgr-day-tip-flex-justify-between kgr-day-tip-flex-align-center">' . "\n";
		$html .= sprintf( '<h2 class="kgr-day-tip-leaf">%s</h2>', esc_html__( 'Terms', 'kgr-day-tip' ) ) . "\n";
		$html .= '<div class="kgr-day-tip-flex-row">' . "\n";
		$html .= self::cat_insert_button();
		$html .= self::tag_insert_button();
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<div class="kgr-day-tip-leaf">' . "\n";
		$html .= '<table class="fixed widefat striped">' . "\n";
		$html .= '<thead>' . "\n";
		$html .= self::table_head_row();
		$html .= '</thead>' . "\n";
		$html .= '<tbody>' . "\n";
		foreach ( $cats as $term ) {
			if ( array_search( $term->term_id, $terms, TRUE ) === FALSE )
				continue;
			$html .= self::table_body_row( $term, __( 'Category', 'kgr-day-tip' ) );
		}
		foreach ( $tags as $term ) {
			if ( array_search( $term->term_id, $terms, TRUE ) === FALSE )
				continue;
			$html .= self::table_body_row( $term, __( 'Tag', 'kgr-day-tip' ) );
		}
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		$html .= self::form( $cats, $terms, 'cat', __( 'Category', 'kgr-day-tip' ) );
		$html .= self::form( $tags, $terms, 'tag', __( 'Tag', 'kgr-day-tip' ) );
		$html .= '<hr class="kgr-day-tip-leaf" />' . "\n";
		$html .= sprintf( '<h2 class="kgr-day-tip-leaf">%s</h2>', esc_html__( 'Danger Zone', 'kgr-day-tip' ) ) . "\n";
		$html .= '<div class="kgr-day-tip-flex-row">' . "\n";
		$html .= self::clear_button();
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	public static function home_echo(): void {
		echo '<div class="wrap">' . "\n";
		echo sprintf( '<h1>%s</h1>', esc_html__( 'KGR Tip of the Day', 'kgr-day-tip' ) ) . "\n";
		$term = KGRDTR::get_int( 'term', TRUE );
		if ( is_null( $term ) )
			echo self::home();
		else
			echo KGR_Day_Tip_Calendar::home( $term );
		echo '</div>' . "\n";
	}

	private static function refresh_button(): string {
		return sprintf( '<a%s>%s</a>', KGRDT::attrs( [
			'href' => add_query_arg( [
				'action' => 'kgr_day_tip_settings_refresh',
				'nonce' => KGRDT::nonce_create( 'kgr_day_tip_settings_refresh' ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'kgr-day-tip-link kgr-day-tip-leaf button',
		] ), esc_html__( 'Refresh', 'kgr-day-tip' ) ) . "\n";
	}

	private static function cat_insert_button(): string {
		return sprintf( '<a%s>%s</a>', KGRDT::attrs( [
			'href' => add_query_arg( [
				'action' => 'kgr_day_tip_settings_cat_insert',
				'nonce' => KGRDT::nonce_create( 'kgr_day_tip_settings_cat_insert' ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'kgr-day-tip-insert kgr-day-tip-leaf button',
			'data-kgr-day-tip-form' => '.kgr-day-tip-form-cat',
		] ), esc_html__( 'Include Category', 'kgr-day-tip' ) ) . "\n";
	}

	private static function tag_insert_button(): string {
		return sprintf( '<a%s>%s</a>', KGRDT::attrs( [
			'href' => add_query_arg( [
				'action' => 'kgr_day_tip_settings_tag_insert',
				'nonce' => KGRDT::nonce_create( 'kgr_day_tip_settings_tag_insert' ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'kgr-day-tip-insert kgr-day-tip-leaf button',
			'data-kgr-day-tip-form' => '.kgr-day-tip-form-tag',
		] ), esc_html__( 'Include Tag', 'kgr-day-tip' ) ) . "\n";
	}

	private static function table_head_row(): string {
		$html = '<tr>' . "\n";
		$html .= sprintf( '<th class="column-primary has-row-actions">%s</th>', esc_html__( 'Term', 'kgr-day-tip' ) ) . "\n";
		$html .= sprintf( '<th>%s</th>', esc_html__( 'Taxonomy', 'kgr-day-tip' ) ) . "\n";
		$html .= '</tr>' . "\n";
		return $html;
	}

	private static function table_body_row( $term, string $taxonomy ): string {
		$actions = [
			sprintf( '<span class="view"><a href="%s">%s</a></span>', get_term_link( $term ), esc_html__( 'View', 'kgr-day-tip' ) ),
			sprintf( '<span class="edit"><a href="%s">%s</a></span>', get_edit_term_link( $term->term_id ), esc_html__( 'Edit', 'kgr-day-tip' ) ),
			sprintf( '<span class="calendar"><a href="%s">%s</a></span>', add_query_arg( [
				'term' => $term->term_id,
			], menu_page_url( 'kgr_day_tip', FALSE ) ), esc_html__( 'Calendar', 'kgr-day-tip' ) ),
			sprintf( '<span class="delete"><a%s>%s</a></span>', KGRDT::attrs( [
				'href' => add_query_arg( [
					'action' => 'kgr_day_tip_settings_term_delete',
					'term' => $term->term_id,
					'nonce' => KGRDT::nonce_create( 'kgr_day_tip_settings_term_delete', $term->term_id ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'kgr-day-tip-link',
				'data-kgr-day-tip-confirm' => esc_attr( sprintf( __( 'Exclude %s?', 'kgr-day-tip' ), $term->name ) ),
			] ), esc_html__( 'Exclude', 'kgr-day-tip' ) ),
		];
		$html = '<tr>' . "\n";
		$html .= '<td class="column-primary has-row-actions">' . "\n";
		$html .= sprintf( '<strong>%s</strong>', esc_html( $term->name ) ) . "\n";
		$html .= sprintf( '<div class="row-actions">%s</div>', implode( ' | ', $actions ) ) . "\n";
		$html .= '</td>' . "\n";
		$html .= sprintf( '<td>%s</td>', esc_html( $taxonomy ) ) . "\n";
		$html .= '</tr>' . "\n";
		return $html;
	}

	private static function form( array $terms, array $exclude, string $name, string $label ): string {
		$html = sprintf( '<div class="kgr-day-tip-form kgr-day-tip-form-%s kgr-day-tip-leaf kgr-day-tip-root kgr-day-tip-root-border kgr-day-tip-flex-col" style="display: none;">', $name ) . "\n";
		$html .= '<div class="kgr-day-tip-leaf">' . "\n";
		$html .= '<table class="form-table">' . "\n";
		$html .= '<tbody>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th><label for="%s">%s</label></th>', esc_attr( 'kgr-day-tip-form-' . $name ), esc_html( $label ) ) . "\n";
		$html .= '<td>' . "\n";
		$html .= sprintf( '<select class="kgr-day-tip-field" data-kgr-day-tip-name="%s" id="%s">', esc_attr( $name ), esc_attr( 'kgr-day-tip-form-' . $name ) ) . "\n";
		$html .= '<option value=""></option>' . "\n";
		foreach ( $terms as $term ) {
			if ( array_search( $term->term_id, $exclude, TRUE ) !== FALSE )
				continue;
			$html .= sprintf( '<option value="%d">%s</option>', $term->term_id, esc_html( $term->name ) ) . "\n";
		}
		$html .= '</select>' . "\n";
		$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<div class="kgr-day-tip-flex-row kgr-day-tip-flex-justify-between kgr-day-tip-flex-align-center">' . "\n";
		$html .= sprintf( '<a href="" class="kgr-day-tip-link kgr-day-tip-submit kgr-day-tip-leaf button button-primary">%s</a>', esc_html__( 'Submit', 'kgr-day-tip' ) ) . "\n";
		$html .= sprintf( '<a href="" class="kgr-day-tip-cancel kgr-day-tip-leaf button">%s</a>', esc_html__( 'Cancel', 'kgr-day-tip' ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}

	private static function clear_button(): string {
		return sprintf( '<a%s>%s</a>', KGRDT::attrs( [
			'href' => add_query_arg( [
				'action' => 'kgr_day_tip_settings_clear',
				'nonce' => KGRDT::nonce_create( 'kgr_day_tip_settings_clear' ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'kgr-day-tip-link kgr-day-tip-leaf button',
			'data-kgr-day-tip-confirm' => esc_attr__( 'Clear?', 'kgr-day-tip' ),
		] ), esc_html__( 'Clear', 'kgr-day-tip' ) ) . "\n";
	}
}

add_action( 'wp_ajax_' . 'kgr_day_tip_settings_refresh', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	KGRDT::nonce_verify( 'kgr_day_tip_settings_refresh' );
	KGRDT::success( KGR_Day_Tip_Settings::home() );
} );

add_action( 'wp_ajax_' . 'kgr_day_tip_settings_cat_insert', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$terms = KGRDT::get_terms();
	KGRDT::nonce_verify( 'kgr_day_tip_settings_cat_insert' );
	$term = KGRDTR::post_int( 'cat' );
	$term = get_term( $term, 'category' );
	if ( is_null( $term ) )
		exit( 'cat' );
	$key = array_search( $term->term_id, $terms, TRUE );
	if ( $key !== FALSE )
		exit( 'cat' );
	$terms[] = $term->term_id;
	KGRDT::set_terms( $terms );
	KGRDT::success( KGR_Day_Tip_Settings::home() );
} );

add_action( 'wp_ajax_' . 'kgr_day_tip_settings_tag_insert', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$terms = KGRDT::get_terms();
	KGRDT::nonce_verify( 'kgr_day_tip_settings_tag_insert' );
	$term = KGRDTR::post_int( 'tag' );
	$term = get_term( $term, 'post_tag' );
	if ( is_null( $term ) )
		exit( 'tag' );
	$key = array_search( $term->term_id, $terms, TRUE );
	if ( $key !== FALSE )
		exit( 'tag' );
	$terms[] = $term->term_id;
	KGRDT::set_terms( $terms );
	KGRDT::success( KGR_Day_Tip_Settings::home() );
} );

add_action( 'wp_ajax_' . 'kgr_day_tip_settings_term_delete', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	$terms = KGRDT::get_terms();
	$term = KGRDTR::get_int( 'term' );
	$term = get_term( $term );
	if ( is_null( $term ) )
		exit( 'term' );
	$key = array_search( $term->term_id, $terms, TRUE );
	if ( $key === FALSE )
		exit( 'term' );
	KGRDT::nonce_verify( 'kgr_day_tip_settings_term_delete', $term->term_id );
	unset( $terms[$key] );
	KGRDT::set_terms( $terms );
	KGRDT::success( KGR_Day_Tip_Settings::home() );
} );

add_action( 'wp_ajax_' . 'kgr_day_tip_settings_clear', function(): void {
	if ( !current_user_can( 'manage_options' ) )
		exit( 'role' );
	KGRDT::nonce_verify( 'kgr_day_tip_settings_clear' );
	KGRDT::set_terms( [] );
	$posts = get_posts( [
		'meta_key' => 'kgr_day_tip_dates',
		'meta_compare' => 'EXISTS',
	] );
	foreach ( $posts as $post )
		KGRDT::set_post_dates( $post, [] );
	KGRDT::success( KGR_Day_Tip_Settings::home() );
} );
