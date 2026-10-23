<?php
/**
 * Template helpers: clinic details, hours, icons, logo and content lookups.
 *
 * @package Ngiti_Dental
 */

defined( 'ABSPATH' ) || exit;

/**
 * Read the bundled sample content (also used by the static demo builder).
 *
 * @return array
 */
function ngiti_sample_data() {
	static $data = null;
	if ( null === $data ) {
		$file = NGITI_DIR . '/inc/sample-content.json';
		$json = file_exists( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
		$data = json_decode( (string) $json, true );
		if ( ! is_array( $data ) ) {
			$data = array();
		}
	}
	return $data;
}

/**
 * Clinic detail from the Customizer, falling back to the sample value.
 *
 * @param string $key Setting key without the "ngiti_" prefix, e.g. "phone".
 * @return string
 */
function ngiti_opt( $key ) {
	$data    = ngiti_sample_data();
	$default = isset( $data['clinic'][ $key ] ) ? $data['clinic'][ $key ] : '';
	if ( 'name' === $key ) {
		$default = get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : $default;
	}
	return (string) get_theme_mod( 'ngiti_' . $key, $default );
}

/**
 * tel: link value from a display number (0917 555 0142 -> +639175550142).
 *
 * @param string $phone Display phone number.
 * @return string
 */
function ngiti_tel( $phone ) {
	$digits = preg_replace( '/\D+/', '', (string) $phone );
	if ( 0 === strpos( $digits, '0' ) ) {
		$digits = '63' . substr( $digits, 1 );
	}
	return '+' . $digits;
}

/**
 * Parse an hours string like "09:00-12:00,13:00-17:00" into ranges.
 *
 * @param string $value Hours string ("" means closed).
 * @return array[] List of array( 'HH:MM', 'HH:MM' ).
 */
function ngiti_parse_hours( $value ) {
	$ranges = array();
	foreach ( explode( ',', (string) $value ) as $part ) {
		if ( preg_match( '/^\s*([01]\d|2[0-3]):([0-5]\d)\s*-\s*([01]\d|2[0-3]):([0-5]\d)\s*$/', $part, $m ) ) {
			$open  = $m[1] . ':' . $m[2];
			$close = $m[3] . ':' . $m[4];
			if ( $close > $open ) {
				$ranges[] = array( $open, $close );
			}
		}
	}
	return $ranges;
}

/**
 * Weekly schedule keyed by ISO day number (1 = Monday ... 7 = Sunday).
 *
 * @return array
 */
function ngiti_week() {
	$weekdays = ngiti_parse_hours( ngiti_opt( 'hours_weekdays' ) );
	$week     = array(
		1 => $weekdays,
		2 => $weekdays,
		3 => $weekdays,
		4 => $weekdays,
		5 => $weekdays,
		6 => ngiti_parse_hours( ngiti_opt( 'hours_saturday' ) ),
		7 => ngiti_parse_hours( ngiti_opt( 'hours_sunday' ) ),
	);
	/**
	 * Filter the weekly opening hours.
	 *
	 * @param array $week ISO day => list of [open, close] pairs.
	 */
	return apply_filters( 'ngiti_week', $week );
}

/**
 * "13:00" -> "1:00 PM" (and "12:00" -> "12:00 NN", the Philippine convention).
 *
 * @param string $hm 24-hour time.
 * @return string
 */
function ngiti_format_time( $hm ) {
	list( $h, $m ) = array_map( 'intval', explode( ':', $hm ) );
	if ( 12 === $h && 0 === $m ) {
		return __( '12:00 NN', 'ngiti-dental-theme' );
	}
	$suffix = $h < 12 ? 'AM' : 'PM';
	$h12    = $h % 12;
	$h12    = 0 === $h12 ? 12 : $h12;
	return sprintf( '%d:%02d %s', $h12, $m, $suffix );
}

/**
 * Human readable ranges for one day.
 *
 * @param array $ranges Ranges for a day.
 * @return string
 */
function ngiti_format_ranges( $ranges ) {
	if ( empty( $ranges ) ) {
		return __( 'Closed', 'ngiti-dental-theme' );
	}
	$out = array();
	foreach ( $ranges as $r ) {
		$out[] = ngiti_format_time( $r[0] ) . ' – ' . ngiti_format_time( $r[1] );
	}
	return implode( ', ', $out );
}

/**
 * Hours grouped for display: Mon–Fri, Saturday, Sunday.
 *
 * @return array[] List of array( label, text ).
 */
function ngiti_hours_rows() {
	$week = ngiti_week();
	return array(
		array( __( 'Monday – Friday', 'ngiti-dental-theme' ), ngiti_format_ranges( $week[1] ), array( 1, 2, 3, 4, 5 ) ),
		array( __( 'Saturday', 'ngiti-dental-theme' ), ngiti_format_ranges( $week[6] ), array( 6 ) ),
		array( __( 'Sunday & holidays', 'ngiti-dental-theme' ), ngiti_format_ranges( $week[7] ), array( 7 ) ),
	);
}

/**
 * Print the hours table, today's row highlighted (server time in the site timezone).
 */
function ngiti_hours_table() {
	$today = (int) wp_date( 'N' );
	echo '<table class="hours-table"><caption class="screen-reader-text">' . esc_html__( 'Clinic hours', 'ngiti-dental-theme' ) . '</caption><tbody>';
	foreach ( ngiti_hours_rows() as $row ) {
		$is_today = in_array( $today, $row[2], true );
		printf(
			'<tr%1$s><th scope="row">%2$s</th><td>%3$s</td></tr>',
			$is_today ? ' class="is-today"' : '',
			esc_html( $row[0] ),
			esc_html( $row[1] )
		);
	}
	echo '</tbody></table>';
}

/**
 * Live open/closed pill. PHP prints the schedule; assets/js/theme.js computes the
 * state in the visitor's browser, so cached pages stay correct.
 */
function ngiti_open_status() {
	$week = ngiti_week();
	printf(
		'<p class="open-status is-closed" data-ngiti-hours="%1$s" data-ngiti-tz="%2$s" role="status"><span class="dot" aria-hidden="true"></span><b>%3$s</b> <span class="detail">%4$s</span></p>',
		esc_attr( wp_json_encode( $week ) ),
		esc_attr( wp_timezone_string() ),
		esc_html__( 'Clinic hours', 'ngiti-dental-theme' ),
		esc_html( ngiti_format_ranges( $week[1] ) )
	);
}

/**
 * URL of a page by slug, or the home URL with an anchor if it does not exist yet.
 *
 * @param string $slug Page slug.
 * @return string
 */
function ngiti_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

/**
 * Inline SVG icon (decorative, so aria-hidden). Generic shapes only, no brand logos.
 *
 * @param string $name Icon name.
 * @return string Safe SVG markup.
 */
function ngiti_icon( $name ) {
	$paths = array(
		'phone'   => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'chat'    => '<path d="M21 12a8 8 0 0 1-11.6 7.1L4 20l1.1-4.6A8 8 0 1 1 21 12z"/><path d="M8.5 12h.01M12 12h.01M15.5 12h.01"/>',
		'pin'     => '<path d="M12 21s-7-6.1-7-11.5A7 7 0 0 1 19 9.5C19 14.9 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
		'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'mail'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'check'   => '<path d="M20 6 9 17l-5-5"/>',
		'calendar'=> '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
		'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'   => '<path d="M6 6l12 12M18 6 6 18"/>',
		'moon'    => '<path d="M20 14.5A8 8 0 0 1 9.5 4a8 8 0 1 0 10.5 10.5z"/>',
		'sun'     => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'share'   => '<circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="6" r="2.5"/><circle cx="18" cy="18" r="2.5"/><path d="m8.2 10.8 7.6-3.6M8.2 13.2l7.6 3.6"/>',
		'camera'  => '<rect x="3" y="7" width="18" height="13" rx="3"/><circle cx="12" cy="13.5" r="3.5"/><path d="M8 7l1.5-3h5L16 7"/>',
		'shield'  => '<path d="M12 3 5 6v6c0 4.4 3 7.8 7 9 4-1.2 7-4.6 7-9V6z"/><path d="m9 12 2 2 4-4"/>',
		'tooth'   => '<path d="M7.5 3C5 3 3.5 5 3.5 7.5c0 3 1.5 4.5 2 7.5.4 2.5 1 6 2.5 6 1.6 0 1.6-5 4-5s2.4 5 4 5c1.5 0 2.1-3.5 2.5-6 .5-3 2-4.5 2-7.5C20.5 5 19 3 16.5 3c-2 0-2.8 1-4.5 1S9.5 3 7.5 3z"/>',
		'sparkle' => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6.3 6.3l2.5 2.5M15.2 15.2l2.5 2.5M6.3 17.7l2.5-2.5M15.2 8.8l2.5-2.5"/>',
		'pliers'  => '<path d="M8 3c0 4 1 6 4 8 3-2 4-4 4-8"/><path d="M12 11v10M9 21h6"/>',
		'root'    => '<path d="M7.5 3C5 3 3.5 5 3.5 7.5c0 2.5 1 3.5 1.5 5h14c.5-1.5 1.5-2.5 1.5-5C20.5 5 19 3 16.5 3c-2 0-2.8 1-4.5 1S9.5 3 7.5 3z"/><path d="M8 12.5 9 21M16 12.5 15 21M12 12.5V19"/>',
		'braces'  => '<rect x="3" y="8" width="5" height="8" rx="2"/><rect x="9.5" y="8" width="5" height="8" rx="2"/><rect x="16" y="8" width="5" height="8" rx="2"/><path d="M2 12h20"/>',
		'crown'   => '<path d="M4 18h16M5 18 4 8l4.5 4L12 6l3.5 6L20 8l-1 10"/>',
		'smile'   => '<circle cx="12" cy="12" r="9"/><path d="M8 14a5 5 0 0 0 8 0M9 9.5h.01M15 9.5h.01"/>',
		'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
	);
	$p = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['tooth'];
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p . '</svg>';
}

/**
 * The clinic monogram: a tooth outline holding a smile arc. Original artwork.
 *
 * @return string SVG markup.
 */
function ngiti_logo_mark() {
	return '<svg class="brand-mark" viewBox="0 0 48 48" aria-hidden="true" focusable="false"><rect width="48" height="48" rx="14" fill="var(--brand)"/><path d="M16.5 10.5c-4 0-6.5 3-6.5 7 0 4.6 2.3 6.9 3.1 11.5.6 3.8 1.5 8.5 3.8 8.5 2.4 0 2.4-7 7.1-7s4.7 7 7.1 7c2.3 0 3.2-4.7 3.8-8.5.8-4.6 3.1-6.9 3.1-11.5 0-4-2.5-7-6.5-7-3.1 0-4.3 1.6-7.5 1.6s-4.4-1.6-7.5-1.6z" fill="none" stroke="var(--on-brand)" stroke-width="2.6" stroke-linejoin="round"/><path d="M18 20.5c3.3 3.6 8.7 3.6 12 0" fill="none" stroke="var(--on-brand)" stroke-width="2.6" stroke-linecap="round"/></svg>';
}

/**
 * Header brand: custom logo if set, else the SVG monogram + site name.
 */
function ngiti_brand() {
	if ( has_custom_logo() ) {
		the_custom_logo();
		return;
	}
	printf(
		'<a class="brand" href="%1$s" rel="home">%2$s<span class="brand-name">%3$s<small>%4$s</small></span></a>',
		esc_url( home_url( '/' ) ),
		ngiti_logo_mark(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		esc_html( get_bloginfo( 'name' ) ),
		esc_html( ngiti_opt( 'tagline' ) )
	);
}

/**
 * Fallback primary menu when no menu is assigned yet.
 */
function ngiti_fallback_menu() {
	$items = array(
		'about'       => __( 'About', 'ngiti-dental-theme' ),
		'services'    => __( 'Services', 'ngiti-dental-theme' ),
		'team'        => __( 'Our Dentists', 'ngiti-dental-theme' ),
		'faq'         => __( 'FAQ', 'ngiti-dental-theme' ),
		'contact'     => __( 'Contact', 'ngiti-dental-theme' ),
	);
	echo '<ul id="primary-menu" class="menu">';
	foreach ( $items as $slug => $label ) {
		$url     = ngiti_page_url( $slug );
		$current = is_page( $slug ) ? ' class="current-menu-item"' : '';
		printf( '<li%1$s><a href="%2$s">%3$s</a></li>', $current, esc_url( $url ), esc_html( $label ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attribute.
	}
	echo '</ul>';
}

/**
 * Services: published ngiti_service posts, or the bundled samples if none exist yet.
 *
 * @param int $limit Max items (0 = all).
 * @return array[] Each: title, excerpt, content, duration, price_from, icon, teeth, url, sample.
 */
function ngiti_get_services( $limit = 0 ) {
	$posts = get_posts(
		array(
			'post_type'      => 'ngiti_service',
			'posts_per_page' => $limit ? $limit : 50,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'no_found_rows'  => true,
		)
	);
	$out = array();
	if ( $posts ) {
		foreach ( $posts as $p ) {
			$out[] = array(
				'title'      => $p->post_title,
				'excerpt'    => get_the_excerpt( $p ),
				'duration'   => (string) get_post_meta( $p->ID, '_ngiti_duration', true ),
				'price_from' => (string) get_post_meta( $p->ID, '_ngiti_price_from', true ),
				'icon'       => (string) get_post_meta( $p->ID, '_ngiti_icon', true ),
				'teeth'      => array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $p->ID, '_ngiti_teeth', true ) ) ) ),
				'url'        => get_permalink( $p ),
				'sample'     => (bool) get_post_meta( $p->ID, '_ngiti_sample', true ),
			);
		}
		return $out;
	}
	$data = ngiti_sample_data();
	foreach ( (array) ( isset( $data['services'] ) ? $data['services'] : array() ) as $s ) {
		$s['url']    = ngiti_page_url( 'appointment' );
		$s['sample'] = true;
		$out[]       = $s;
	}
	return $limit ? array_slice( $out, 0, $limit ) : $out;
}

/**
 * Team members: ngiti_team posts or bundled samples.
 *
 * @return array[]
 */
function ngiti_get_team() {
	$posts = get_posts(
		array(
			'post_type'      => 'ngiti_team',
			'posts_per_page' => 50,
			'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
			'no_found_rows'  => true,
		)
	);
	$out = array();
	if ( $posts ) {
		foreach ( $posts as $p ) {
			$out[] = array(
				'name'        => $p->post_title,
				'role'        => (string) get_post_meta( $p->ID, '_ngiti_role', true ),
				'credentials' => (string) get_post_meta( $p->ID, '_ngiti_credentials', true ),
				'initials'    => (string) get_post_meta( $p->ID, '_ngiti_initials', true ),
				'color'       => (string) get_post_meta( $p->ID, '_ngiti_color', true ),
				'bio'         => wp_strip_all_tags( get_the_content( null, false, $p ) ),
				'thumb'       => get_the_post_thumbnail( $p, 'medium', array( 'class' => 'avatar' ) ),
			);
		}
		return $out;
	}
	$data = ngiti_sample_data();
	foreach ( (array) ( isset( $data['team'] ) ? $data['team'] : array() ) as $t ) {
		$t['thumb'] = '';
		$out[]      = $t;
	}
	return $out;
}

/**
 * Patient FAQ list (filterable).
 *
 * @return array[] Each: q, a.
 */
function ngiti_get_faq() {
	$data = ngiti_sample_data();
	return apply_filters( 'ngiti_faq', isset( $data['faq'] ) ? $data['faq'] : array() );
}

/**
 * Peso price label: "800" -> "from ₱800".
 *
 * @param string $amount Number as string.
 * @return string
 */
function ngiti_price( $amount ) {
	if ( '' === (string) $amount ) {
		return '';
	}
	/* translators: %s: formatted peso amount */
	return sprintf( __( 'from ₱%s', 'ngiti-dental-theme' ), number_format_i18n( (float) $amount ) );
}

/**
 * Print one service card.
 *
 * @param array $s Service data.
 */
function ngiti_service_card( $s ) {
	$icon = ! empty( $s['icon'] ) ? $s['icon'] : 'tooth';
	echo '<article class="card service-card">';
	echo '<span class="tooth-icon">' . ngiti_icon( $icon ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
	echo '<h3>' . esc_html( $s['title'] ) . '</h3>';
	echo '<p>' . esc_html( $s['excerpt'] ) . '</p>';
	echo '<div class="service-meta">';
	if ( ! empty( $s['duration'] ) ) {
		echo '<span class="chip">' . ngiti_icon( 'clock' ) . esc_html( $s['duration'] ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
	}
	if ( ! empty( $s['price_from'] ) ) {
		echo '<span class="chip chip--price">' . esc_html( ngiti_price( $s['price_from'] ) ) . '</span>';
	}
	echo '</div></article>';
}

/**
 * Print one team card. Uses the featured image if set, else an initials avatar.
 *
 * @param array $t Team member data.
 */
function ngiti_team_card( $t ) {
	$color = sanitize_hex_color( isset( $t['color'] ) ? $t['color'] : '' );
	$color = $color ? $color : '#0a6b66';
	echo '<article class="card team-card">';
	if ( ! empty( $t['thumb'] ) ) {
		echo wp_kses_post( $t['thumb'] );
	} else {
		printf( '<span class="avatar" style="background:%1$s" aria-hidden="true">%2$s</span>', esc_attr( $color ), esc_html( $t['initials'] ) );
	}
	echo '<h3>' . esc_html( $t['name'] ) . '</h3>';
	echo '<p class="team-role">' . esc_html( $t['role'] ) . '</p>';
	if ( ! empty( $t['credentials'] ) ) {
		echo '<p class="team-cred">' . esc_html( $t['credentials'] ) . '</p>';
	}
	if ( ! empty( $t['bio'] ) ) {
		echo '<p class="team-bio">' . esc_html( $t['bio'] ) . '</p>';
	}
	echo '</article>';
}

/**
 * Inner page hero.
 *
 * @param string $title   Heading.
 * @param string $lead    Intro sentence.
 * @param string $eyebrow Small label above.
 */
function ngiti_page_hero( $title, $lead = '', $eyebrow = '' ) {
	echo '<header class="page-hero"><div class="wrap">';
	printf(
		'<nav class="breadcrumbs" aria-label="%1$s"><a href="%2$s">%3$s</a> / <span aria-current="page">%4$s</span></nav>',
		esc_attr__( 'Breadcrumb', 'ngiti-dental-theme' ),
		esc_url( home_url( '/' ) ),
		esc_html__( 'Home', 'ngiti-dental-theme' ),
		esc_html( $title )
	);
	if ( $eyebrow ) {
		echo '<p class="eyebrow">' . esc_html( $eyebrow ) . '</p>';
	}
	echo '<h1>' . esc_html( $title ) . '</h1>';
	if ( $lead ) {
		echo '<p class="lead">' . esc_html( $lead ) . '</p>';
	}
	echo '</div></header>';
}

/**
 * Print the page content, or the bundled sample text when the page is still empty.
 *
 * @param string $key Key in sample-content.json "pages".
 */
function ngiti_content_or_sample( $key ) {
	if ( '' !== trim( (string) get_the_content() ) ) {
		the_content();
		return;
	}
	$data = ngiti_sample_data();
	if ( ! empty( $data['pages'][ $key ] ) ) {
		echo wp_kses_post( $data['pages'][ $key ] );
	}
}
