<?php
/**
 * Lightweight SEO: meta description, Open Graph tags and Dentist JSON-LD.
 * Steps aside automatically when a full SEO plugin is active.
 *
 * @package Ngiti_Dental
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is a dedicated SEO plugin handling meta tags?
 *
 * @return bool
 */
function ngiti_seo_plugin_active() {
	$active = defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'THE_SEO_FRAMEWORK_VERSION' ) || defined( 'SEOPRESS_VERSION' );
	return (bool) apply_filters( 'ngiti_seo_plugin_active', $active );
}

/**
 * Description for the current view.
 *
 * @return string
 */
function ngiti_meta_description() {
	$city = ngiti_opt( 'city' );
	if ( is_singular() && has_excerpt() ) {
		return wp_strip_all_tags( get_the_excerpt() );
	}
	$defaults = array(
		'page-templates/template-about.php'       => __( 'Meet our family dental clinic: how we work, how we keep instruments clean and what to expect at your visit.', 'ngiti-dental-theme' ),
		'page-templates/template-services.php'    => __( 'Check-ups, fillings, extractions, root canal, braces, whitening, crowns and kids\' dentistry, with durations and starting prices.', 'ngiti-dental-theme' ),
		'page-templates/template-team.php'        => __( 'Meet the dentists and staff who will take care of you and your family.', 'ngiti-dental-theme' ),
		'page-templates/template-appointment.php' => __( 'Request a dental appointment online. Choose a service and a preferred date, and we will confirm by text or call.', 'ngiti-dental-theme' ),
		'page-templates/template-faq.php'         => __( 'Answers to common patient questions: walk-ins, payments, HMO, first visits, dental anxiety and children.', 'ngiti-dental-theme' ),
		'page-templates/template-contact.php'     => __( 'Clinic address, hours, phone and Messenger. Send us a message or get directions.', 'ngiti-dental-theme' ),
		'page-templates/template-privacy.php'     => __( 'How we collect and protect your personal information under the Data Privacy Act of 2012 (RA 10173).', 'ngiti-dental-theme' ),
		'page-templates/template-disclaimer.php'  => __( 'The information on this website is general education, not dental or medical advice.', 'ngiti-dental-theme' ),
	);
	if ( is_page() ) {
		$tpl = get_page_template_slug();
		if ( isset( $defaults[ $tpl ] ) ) {
			return $defaults[ $tpl ];
		}
	}
	/* translators: %s: city */
	return sprintf( __( 'Gentle family dentistry in %s: check-ups, fillings, braces and kids\' care. Request an appointment online.', 'ngiti-dental-theme' ), preg_replace( '/\s*\d{4}$/', '', $city ) );
}

/**
 * Print meta description and Open Graph tags.
 */
function ngiti_meta_tags() {
	if ( ngiti_seo_plugin_active() ) {
		return;
	}
	$desc  = ngiti_meta_description();
	$title = wp_get_document_title();
	$url   = is_singular() ? get_permalink() : home_url( add_query_arg( array() ) );
	$img   = has_post_thumbnail() && is_singular() ? get_the_post_thumbnail_url( null, 'large' ) : NGITI_URI . '/screenshot.png';
	printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
	printf( '<meta property="og:type" content="%s">' . "\n", is_front_page() ? 'website' : 'article' );
	printf( '<meta property="og:title" content="%s">' . "\n", esc_attr( $title ) );
	printf( '<meta property="og:description" content="%s">' . "\n", esc_attr( $desc ) );
	printf( '<meta property="og:url" content="%s">' . "\n", esc_url( $url ) );
	printf( '<meta property="og:site_name" content="%s">' . "\n", esc_attr( get_bloginfo( 'name' ) ) );
	printf( '<meta property="og:image" content="%s">' . "\n", esc_url( $img ) );
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
}
add_action( 'wp_head', 'ngiti_meta_tags', 5 );

/**
 * Dentist (LocalBusiness) structured data, built from the Customizer values.
 *
 * @return array
 */
function ngiti_schema_data() {
	$days  = array( 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday' );
	$hours = array();
	foreach ( ngiti_week() as $n => $ranges ) {
		foreach ( $ranges as $r ) {
			$hours[] = array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => 'https://schema.org/' . $days[ $n ],
				'opens'     => $r[0],
				'closes'    => $r[1],
			);
		}
	}
	$city_parts = array_map( 'trim', explode( ',', ngiti_opt( 'city' ) ) );
	$data       = array(
		'@context'                  => 'https://schema.org',
		'@type'                     => 'Dentist',
		'@id'                       => home_url( '/#clinic' ),
		'name'                      => get_bloginfo( 'name' ),
		'description'               => ngiti_meta_description(),
		'url'                       => home_url( '/' ),
		'telephone'                 => ngiti_tel( ngiti_opt( 'phone' ) ),
		'email'                     => ngiti_opt( 'email' ),
		'image'                     => NGITI_URI . '/screenshot.png',
		'priceRange'                => '₱₱',
		'currenciesAccepted'        => 'PHP',
		'paymentAccepted'           => 'Cash, Bank transfer, E-wallet',
		'address'                   => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => ngiti_opt( 'address' ),
			'addressLocality' => isset( $city_parts[0] ) ? $city_parts[0] : '',
			'addressRegion'   => isset( $city_parts[1] ) ? preg_replace( '/\s*\d{4}$/', '', $city_parts[1] ) : '',
			'postalCode'      => preg_match( '/(\d{4})\s*$/', ngiti_opt( 'city' ), $m ) ? $m[1] : '',
			'addressCountry'  => 'PH',
		),
		'hasMap'                    => ngiti_opt( 'map_url' ),
		'openingHoursSpecification' => $hours,
	);
	$same = array_filter( array( ngiti_opt( 'facebook' ), ngiti_opt( 'instagram' ) ), function ( $u ) {
		return $u && '#' !== $u;
	} );
	if ( $same ) {
		$data['sameAs'] = array_values( $same );
	}
	return apply_filters( 'ngiti_schema', $data );
}

/**
 * Print the JSON-LD on the front page and the contact page.
 */
function ngiti_print_schema() {
	if ( ! apply_filters( 'ngiti_output_schema', true ) ) {
		return;
	}
	if ( ! is_front_page() && ! is_page_template( 'page-templates/template-contact.php' ) ) {
		return;
	}
	echo '<script type="application/ld+json">' . wp_json_encode( ngiti_schema_data(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . "</script>\n";
}
add_action( 'wp_head', 'ngiti_print_schema', 20 );

/**
 * Favicon fallback when no Site Icon is set.
 */
function ngiti_favicon() {
	if ( has_site_icon() ) {
		return;
	}
	printf( '<link rel="icon" href="%s" type="image/svg+xml">' . "\n", esc_url( NGITI_URI . '/assets/img/favicon.svg' ) );
}
add_action( 'wp_head', 'ngiti_favicon', 3 );
