<?php

if ( !defined( 'ABSPATH' ) )
	exit;

final class KGR_Day_Tip_Calendar {

	public static function home( WP_Term|int|null $term ): string {
		if ( is_int( $term ) )
			$term = get_term( $term );
		if ( is_null( $term ) )
			return 'term';
		$posts = get_posts( [
			'tax_query' => [
				'taxonomy' => $term->taxonomy,
				'term' => $term_id,
			],
			'orderby' => 'date',
			'order' => 'ASC',
			'meta_key' => 'kgr_day_tip_dates',
			'meta_compare' => 'EXISTS',
		] );
		$struct = [];
		foreach ( $posts as $post ) {
			$dates = KGRDT::get_post_dates( $post );
			foreach ( $dates as $date ) {
				if ( !array_key_exists( $date, $struct ) )
					$struct[$date] = [];
				$struct[$date][] = $post;
			}
		}
		ksort( $struct, SORT_STRING );
		$html = '<div class="kgr-day-tip-flex-col kgr-day-tip-root" style="margin: 0 -16px;">' . "\n";
		$html .= '<div class="kgr-day-tip-flex-row kgr-day-tip-flex-justify-between kgr-day-tip-flex-align-center">' . "\n";
		$html .= sprintf( '<a href="%s" class="kgr-day-tip-leaf"><strong>%s</strong></a>', get_edit_term_link( $term->term_id ), esc_html( $term->name ) ) . "\n";
		$html .= sprintf( '<a href="%s" class="kgr-day-tip-leaf button">%s</a>', menu_page_url( 'kgr_day_tip', FALSE ), esc_html__( 'Back', 'kgr-day-tip' ) ) . "\n";
		$html .= '</div>' . "\n";
		$html .= sprintf( '<h2 class="kgr-day-tip-leaf">%s</h2>', esc_html__( 'Calendar', 'kgr-day-tip' ) ) . "\n";
		$html .= '<div class="kgr-day-tip-leaf">' . "\n";
		$html .= '<table class="fixed widefat striped">' . "\n";
		$html .= '<thead>' . "\n";
		$html .= '<tr>' . "\n";
		$html .= sprintf( '<th>%s</th>', esc_html__( 'Title', 'kgr-day-tip' ) ) . "\n";
		$html .= sprintf( '<th>%s</th>', esc_html__( 'Excerpt', 'kgr-day-tip' ) ) . "\n";
		$html .= sprintf( '<th>%s</th>', esc_html__( 'Date', 'kgr-day-tip' ) ) . "\n";
		$html .= '</tr>' . "\n";
		$html .= '</thead>' . "\n";
		$html .= '<tbody>' . "\n";
		foreach ( $struct as $date => $posts ) {
			mb_ereg( '^(\d{2})(\d{2})$', $date, $m );
			$month = intval( $m[1] );
			$month = $GLOBALS['wp_locale']->get_month( $month );
			$day = intval( $m[2] );
			foreach ( $posts as $post ) {
				$html .= '<tr>' . "\n";
				$html .= sprintf( '<td><a href="%s"><strong>%s</strong></a></td>', get_edit_post_link( $post ), esc_html( get_the_title( $post ) ) ) . "\n";
				$html .= sprintf( '<td>%s</td>', esc_html( get_the_excerpt( $post ) ) ) . "\n";
				$html .= sprintf( '<td>%s</td>', esc_html( sprintf( '%d %s', $day, $month ) ) ) . "\n";
				$html .= '</tr>' . "\n";
			}
		}
		$html .= '</tbody>' . "\n";
		$html .= '</table>' . "\n";
		$html .= '</div>' . "\n";
		$html .= '</div>' . "\n";
		return $html;
	}
}
