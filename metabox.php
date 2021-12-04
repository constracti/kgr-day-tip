<?php

if ( !defined( 'ABSPATH' ) )
	exit;

add_action( 'add_meta_boxes', function(): void {
	add_meta_box( 'kgr-day-tip', __( 'KGR Tip of the Day', 'kgr-day-tip' ), [ 'KGR_Day_Tip_Metabox', 'home_echo' ], NULL, 'side' );
} );

add_action( 'admin_enqueue_scripts', function( string $hook_suffix ): void {
	if ( $hook_suffix !== 'post-new.php' && $hook_suffix !== 'post.php' )
		return;
	wp_enqueue_style( 'kgr_day_tip_flex', KGRDT::url( 'flex.css' ), [], KGRDT::version() );
	wp_enqueue_script( 'kgr_day_tip_script', KGRDT::url( 'script.js' ), [ 'jquery' ], KGRDT::version() );
} );

final class KGR_Day_Tip_Metabox {

	public static function home( WP_Post $post ): string {
		$dates = KGRDT::get_post_dates( $post );
		$html = '<div class="kgr-day-tip-home kgr-day-tip-flex-col kgr-day-tip-root" style="margin: -6px -12px -12px -12px;">' . "\n";
		$html .= '<div class="kgr-day-tip-flex-row kgr-day-tip-flex-justify-between kgr-day-tip-flex-align-center">' . "\n";
		$html .= self::refresh_button( $post );
		$html .= '<span class="kgr-day-tip-spinner kgr-day-tip-leaf spinner" data-kgr-day-tip-spinner-toggle="is-active"></span>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '<hr class="kgr-day-tip-leaf" />' . "\n";
		$html .= '<div class="kgr-day-tip-flex-row kgr-day-tip-flex-justify-between kgr-day-tip-flex-align-center">' . "\n";
		$html .= sprintf( '<div class="kgr-day-tip-leaf">%s</div>', esc_html__( 'Dates', 'kgr-day-tip' ) ) . "\n";
		$html .= self::insert_button( $post );
		$html .= '</div>' . "\n";
		$html .= '<div class="kgr-day-tip-leaf">' . "\n";
		$html .= '<table class="fixed widefat striped">' . "\n";
		$html .= '<tbody>' . "\n";
		sort( $dates, SORT_STRING );
		foreach ( $dates as $date ) {
			mb_ereg( '^(\d{2})(\d{2})$', $date, $m );
			$month = intval( $m[1] );
			$month = $GLOBALS['wp_locale']->get_month( $month );
			$day = intval( $m[2] );
			$html .= '<tr>' . "\n";
			$html .= sprintf( '<td style="width: 120px;"><strong class="kgr-day-tip-leaf">%s</strong></td>', esc_html( sprintf( '%d %s', $day, $month ) ) ) . "\n";
			$html .= sprintf( '<td><a%s>%s</a></td>', KGRDT::attrs( [
				'href' => add_query_arg( [
					'action' => 'kgr_day_tip_metabox_delete',
					'post' => $post->ID,
					'date' => esc_attr( $date ),
					'nonce' => KGRDT::nonce_create( 'kgr_day_tip_metabox_delete', $post->ID, $date ),
				], admin_url( 'admin-ajax.php' ) ),
				'class' => 'kgr-day-tip-link',
				'data-kgr-day-tip-confirm' => esc_attr( sprintf( __( 'Delete %s?', 'kgr-day-tip' ), $date ) ),
			] ), esc_html__( 'Delete', 'kgr-day-tip' ) );
			$html .= '</tr>' . "\n";
		}
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		$html .= self::form();
		$html .= '</div>' . "\n";
		return $html;
	}

	public static function home_echo( WP_Post $post ): void {
		echo self::home( $post );
	}

	private static function refresh_button( WP_Post $post ): string {
		return sprintf( '<a%s>%s</a>', KGRDT::attrs( [
			'href' => add_query_arg( [
				'action' => 'kgr_day_tip_metabox_refresh',
				'post' => $post->ID,
				'nonce' => KGRDT::nonce_create( 'kgr_day_tip_metabox_refresh', $post->ID ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'kgr-day-tip-link kgr-day-tip-leaf button',
		] ), esc_html__( 'Refresh', 'kgr-day-tip' ) ) . "\n";
	}

	private static function insert_button( WP_Post $post ): string {
		return sprintf( '<a%s>%s</a>', KGRDT::attrs( [
			'href' => add_query_arg( [
				'action' => 'kgr_day_tip_metabox_insert',
				'post' => $post->ID,
				'nonce' => KGRDT::nonce_create( 'kgr_day_tip_metabox_insert', $post->ID ),
			], admin_url( 'admin-ajax.php' ) ),
			'class' => 'kgr-day-tip-insert kgr-day-tip-leaf button',
			'data-kgr-day-tip-form' => '.kgr-day-tip-form-date',
		] ), esc_html__( 'Insert', 'kgr-day-tip' ) ) . "\n";
	}

	private static function form(): string {
		$html = '<div class="kgr-day-tip-form kgr-day-tip-form-date kgr-day-tip-leaf kgr-day-tip-root kgr-day-tip-root-border kgr-day-tip-flex-col" style="display: none;">' . "\n";
		$html .= '<div class="kgr-day-tip-flex-row kgr-day-tip-flex-justify-between kgr-day-tip-flex-align-center">' . "\n";
		$html .= sprintf( '<label class="kgr-day-tip-leaf" for="%s">%s</label>', esc_attr( 'kgr-day-tip-form-date' ), esc_html__( 'Date (MMDD)', 'kgr-day-tip' ) ) . "\n";
		$html .= sprintf( '<input type="text" class="kgr-day-tip-field kgr-day-tip-leaf" data-kgr-day-tip-name="date" id="%s" maxlength="4" style="width: 4em;" />', esc_attr( 'kgr-day-tip-form-date' ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= '<div class="kgr-day-tip-flex-row kgr-day-tip-flex-justify-between kgr-day-tip-flex-align-center">' . "\n";
		$html .= sprintf( '<a href="" class="kgr-day-tip-link kgr-day-tip-submit kgr-day-tip-leaf button button-primary">%s</a>', esc_html__( 'Submit', 'kgr-day-tip' ) ) . "\n";
		$html .= sprintf( '<a href="" class="kgr-day-tip-cancel kgr-day-tip-leaf button">%s</a>', esc_html__( 'Cancel', 'kgr-day-tip' ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}
}

add_action( 'wp_ajax_' . 'kgr_day_tip_metabox_refresh', function(): void {
	$post = KGRDTR::get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	KGRDT::nonce_verify( 'kgr_day_tip_metabox_refresh', $post->ID );
	KGRDT::success( KGR_Day_Tip_Metabox::home( $post ) );
} );

add_action( 'wp_ajax_' . 'kgr_day_tip_metabox_insert', function(): void {
	$post = KGRDTR::get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$dates = KGRDT::get_post_dates( $post );
	KGRDT::nonce_verify( 'kgr_day_tip_metabox_insert', $post->ID );
	$date = KGRDTR::post_word( 'date' );
	if ( mb_ereg( '^(\d{2})(\d{2})$', $date, $m ) === FALSE )
		exit( 'date' );
	$month = intval( $m[1] );
	$day = intval( $m[2] );
	$daymax = match ( $month ) {
		1, 3, 5, 7, 8, 10, 12 => 31,
		2 => 29,
		4, 6, 9, 11 => 30,
		default => exit( 'date' ),
	};
	if ( $day < 1 || $day > $daymax )
		exit( 'date' );
	$dates[] = $date;
	KGRDT::set_post_dates( $post, $dates );
	KGRDT::success( KGR_Day_Tip_Metabox::home( $post ) );
} );

add_action( 'wp_ajax_' . 'kgr_day_tip_metabox_delete', function(): void {
	$post = KGRDTR::get_post();
	if ( !current_user_can( 'edit_post', $post->ID ) )
		exit( 'role' );
	$dates = KGRDT::get_post_dates( $post );
	$date = KGRDTR::get_word( 'date' );
	$key = array_search( $date, $dates, TRUE );
	if ( $key === FALSE )
		exit( 'date' );
	KGRDT::nonce_verify( 'kgr_day_tip_metabox_delete', $post->ID, $date );
	unset( $dates[$key] );
	KGRDT::set_post_dates( $post, $dates );
	KGRDT::success( KGR_Day_Tip_Metabox::home( $post ) );
} );
