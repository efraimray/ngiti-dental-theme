<?php
/**
 * Customizer: Appearance -> Customize -> Clinic details.
 *
 * @package Ngiti_Dental
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sanitize an hours string ("09:00-12:00,13:00-17:00"). Invalid parts are dropped.
 *
 * @param string $value Raw value.
 * @return string
 */
function ngiti_sanitize_hours( $value ) {
	$ranges = ngiti_parse_hours( sanitize_text_field( $value ) );
	$parts  = array();
	foreach ( $ranges as $r ) {
		$parts[] = $r[0] . '-' . $r[1];
	}
	return implode( ',', $parts );
}

/**
 * Sanitize a URL that may also be "#" (placeholder).
 *
 * @param string $value Raw value.
 * @return string
 */
function ngiti_sanitize_link( $value ) {
	$value = trim( (string) $value );
	return '#' === $value ? '#' : esc_url_raw( $value );
}

/**
 * Register the settings and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function ngiti_customize_register( $wp_customize ) {
	$data   = ngiti_sample_data();
	$clinic = isset( $data['clinic'] ) ? $data['clinic'] : array();

	$wp_customize->add_section(
		'ngiti_clinic',
		array(
			'title'       => __( 'Clinic details', 'ngiti-dental-theme' ),
			'description' => __( 'Shown in the header, footer, contact page, sticky call button and the Dentist schema.', 'ngiti-dental-theme' ),
			'priority'    => 30,
		)
	);

	$fields = array(
		'tagline'        => array( __( 'Short tagline under the name', 'ngiti-dental-theme' ), 'text', 'sanitize_text_field' ),
		'phone'          => array( __( 'Phone (shown as text and used for tap-to-call)', 'ngiti-dental-theme' ), 'text', 'sanitize_text_field' ),
		'messenger'      => array( __( 'Messenger link (m.me/yourpage)', 'ngiti-dental-theme' ), 'text', 'ngiti_sanitize_link' ),
		'email'          => array( __( 'Public email', 'ngiti-dental-theme' ), 'email', 'sanitize_email' ),
		'request_email'  => array( __( 'Send appointment requests to (leave empty to use the admin email)', 'ngiti-dental-theme' ), 'email', 'sanitize_email' ),
		'address'        => array( __( 'Street address', 'ngiti-dental-theme' ), 'text', 'sanitize_text_field' ),
		'city'           => array( __( 'City, province and ZIP', 'ngiti-dental-theme' ), 'text', 'sanitize_text_field' ),
		'map_url'        => array( __( 'Map link (Google Maps share link)', 'ngiti-dental-theme' ), 'url', 'esc_url_raw' ),
		'hours_weekdays' => array( __( 'Mon–Fri hours, e.g. 09:00-12:00,13:00-17:00', 'ngiti-dental-theme' ), 'text', 'ngiti_sanitize_hours' ),
		'hours_saturday' => array( __( 'Saturday hours (empty = closed)', 'ngiti-dental-theme' ), 'text', 'ngiti_sanitize_hours' ),
		'hours_sunday'   => array( __( 'Sunday hours (empty = closed)', 'ngiti-dental-theme' ), 'text', 'ngiti_sanitize_hours' ),
		'facebook'       => array( __( 'Facebook page URL', 'ngiti-dental-theme' ), 'text', 'ngiti_sanitize_link' ),
		'instagram'      => array( __( 'Instagram URL', 'ngiti-dental-theme' ), 'text', 'ngiti_sanitize_link' ),
	);

	foreach ( $fields as $key => $f ) {
		$wp_customize->add_setting(
			'ngiti_' . $key,
			array(
				'default'           => isset( $clinic[ $key ] ) ? $clinic[ $key ] : '',
				'sanitize_callback' => $f[2],
				'transport'         => 'refresh',
			)
		);
		$wp_customize->add_control(
			'ngiti_' . $key,
			array(
				'label'   => $f[0],
				'section' => 'ngiti_clinic',
				'type'    => $f[1],
			)
		);
	}
}
add_action( 'customize_register', 'ngiti_customize_register' );
