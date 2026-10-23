<?php
/**
 * One-click sample content: pages (with their templates), services, team and menus.
 * Shown as a dismissible admin notice after the theme is activated.
 *
 * @package Ngiti_Dental
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pages the importer creates: slug => array( title, template, content key, excerpt ).
 *
 * @return array
 */
function ngiti_sample_pages() {
	return array(
		'home'           => array( __( 'Home', 'ngiti-dental-theme' ), '', '', '' ),
		'about'          => array( __( 'About the Clinic', 'ngiti-dental-theme' ), 'page-templates/template-about.php', 'about', __( 'A small family clinic where every visit is calm, clear and on time.', 'ngiti-dental-theme' ) ),
		'services'       => array( __( 'Services & Fees', 'ngiti-dental-theme' ), 'page-templates/template-services.php', '', __( 'Treatments for the whole family, with typical durations and sample starting prices.', 'ngiti-dental-theme' ) ),
		'team'           => array( __( 'Our Dentists', 'ngiti-dental-theme' ), 'page-templates/template-team.php', '', __( 'The people who will take care of you. Names and credentials shown are samples.', 'ngiti-dental-theme' ) ),
		'appointment'    => array( __( 'Request an Appointment', 'ngiti-dental-theme' ), 'page-templates/template-appointment.php', '', __( 'Tell us what you need and when. We confirm every request by text or call.', 'ngiti-dental-theme' ) ),
		'faq'            => array( __( 'Patient FAQ', 'ngiti-dental-theme' ), 'page-templates/template-faq.php', 'faq', __( 'Quick answers before your visit.', 'ngiti-dental-theme' ) ),
		'contact'        => array( __( 'Contact & Directions', 'ngiti-dental-theme' ), 'page-templates/template-contact.php', '', __( 'Call, message or visit us. We reply within clinic hours.', 'ngiti-dental-theme' ) ),
		'privacy-notice' => array( __( 'Privacy Notice', 'ngiti-dental-theme' ), 'page-templates/template-privacy.php', 'privacy', __( 'How we handle your personal information under RA 10173.', 'ngiti-dental-theme' ) ),
		'disclaimer'     => array( __( 'Disclaimer', 'ngiti-dental-theme' ), 'page-templates/template-disclaimer.php', 'disclaimer', __( 'Please read this before relying on anything on this website.', 'ngiti-dental-theme' ) ),
	);
}

/**
 * FAQ as editable core Details blocks.
 *
 * @return string Block markup.
 */
function ngiti_faq_blocks() {
	$out = '';
	foreach ( ngiti_get_faq() as $item ) {
		$out .= '<!-- wp:details --><details class="wp-block-details"><summary>' . esc_html( $item['q'] ) . '</summary><!-- wp:paragraph --><p>' . esc_html( $item['a'] ) . '</p><!-- /wp:paragraph --></details><!-- /wp:details -->' . "\n\n";
	}
	return $out;
}

/**
 * Show the import notice to admins until it is used or dismissed.
 */
function ngiti_import_notice() {
	if ( ! current_user_can( 'edit_theme_options' ) || get_option( 'ngiti_sample_state' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && ! in_array( $screen->id, array( 'themes', 'dashboard' ), true ) ) {
		return;
	}
	$import  = wp_nonce_url( admin_url( 'admin-post.php?action=ngiti_import&do=import' ), 'ngiti_import' );
	$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=ngiti_import&do=dismiss' ), 'ngiti_import' );
	echo '<div class="notice notice-info"><p><strong>' . esc_html__( 'Ngiti Dental is active.', 'ngiti-dental-theme' ) . '</strong> ';
	esc_html_e( 'Create the sample pages (with their templates), services, team members and menus? Everything is marked as sample and can be edited or deleted.', 'ngiti-dental-theme' );
	echo '</p><p><a class="button button-primary" href="' . esc_url( $import ) . '">' . esc_html__( 'Create sample content', 'ngiti-dental-theme' ) . '</a> <a class="button" href="' . esc_url( $dismiss ) . '">' . esc_html__( 'No thanks', 'ngiti-dental-theme' ) . '</a></p></div>';
}
add_action( 'admin_notices', 'ngiti_import_notice' );

/**
 * Run the import (or dismiss). Idempotent: existing slugs are skipped.
 */
function ngiti_handle_import() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do this.', 'ngiti-dental-theme' ), 403 );
	}
	check_admin_referer( 'ngiti_import' );
	$do = isset( $_GET['do'] ) ? sanitize_key( wp_unslash( $_GET['do'] ) ) : '';
	if ( 'import' === $do ) {
		ngiti_import_sample_content();
		update_option( 'ngiti_sample_state', 'imported', false );
		wp_safe_redirect( admin_url( 'themes.php?ngiti_imported=1' ) );
		exit;
	}
	update_option( 'ngiti_sample_state', 'dismissed', false );
	wp_safe_redirect( admin_url( 'themes.php' ) );
	exit;
}
add_action( 'admin_post_ngiti_import', 'ngiti_handle_import' );

/**
 * Create pages, services, team members and menus.
 *
 * @return array Created IDs by slug (pages).
 */
function ngiti_import_sample_content() {
	$data  = ngiti_sample_data();
	$texts = isset( $data['pages'] ) ? $data['pages'] : array();
	$ids   = array();

	foreach ( ngiti_sample_pages() as $slug => $p ) {
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			continue;
		}
		$content = '';
		if ( 'faq' === $p[2] ) {
			$content = ngiti_faq_blocks();
		} elseif ( $p[2] && isset( $texts[ $p[2] ] ) ) {
			$content = '<!-- wp:html -->' . wp_kses_post( $texts[ $p[2] ] ) . '<!-- /wp:html -->';
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_name'    => $slug,
				'post_title'   => $p[0],
				'post_content' => $content,
				'post_excerpt' => $p[3],
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			if ( $p[1] ) {
				update_post_meta( $id, '_wp_page_template', $p[1] );
			}
			$ids[ $slug ] = $id;
		}
	}

	if ( ! empty( $ids['home'] ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $ids['home'] );
	}

	$order = 0;
	foreach ( (array) ( isset( $data['services'] ) ? $data['services'] : array() ) as $s ) {
		++$order;
		if ( get_page_by_path( $s['slug'], OBJECT, 'ngiti_service' ) ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'ngiti_service',
				'post_status'  => 'publish',
				'post_name'    => $s['slug'],
				'post_title'   => $s['title'],
				'post_excerpt' => $s['excerpt'],
				'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $s['content'] ) . '</p><!-- /wp:paragraph -->',
				'menu_order'   => $order,
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_ngiti_duration', sanitize_text_field( $s['duration'] ) );
			update_post_meta( $id, '_ngiti_price_from', absint( $s['price_from'] ) );
			update_post_meta( $id, '_ngiti_icon', sanitize_key( $s['icon'] ) );
			update_post_meta( $id, '_ngiti_teeth', implode( ',', array_map( 'sanitize_key', $s['teeth'] ) ) );
			update_post_meta( $id, '_ngiti_sample', 1 );
		}
	}

	$order = 0;
	foreach ( (array) ( isset( $data['team'] ) ? $data['team'] : array() ) as $t ) {
		++$order;
		if ( get_page_by_path( $t['slug'], OBJECT, 'ngiti_team' ) ) {
			continue;
		}
		$id = wp_insert_post(
			array(
				'post_type'    => 'ngiti_team',
				'post_status'  => 'publish',
				'post_name'    => $t['slug'],
				'post_title'   => $t['name'],
				'post_content' => $t['bio'],
				'menu_order'   => $order,
			)
		);
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_ngiti_role', sanitize_text_field( $t['role'] ) );
			update_post_meta( $id, '_ngiti_credentials', sanitize_text_field( $t['credentials'] ) );
			update_post_meta( $id, '_ngiti_initials', sanitize_text_field( $t['initials'] ) );
			update_post_meta( $id, '_ngiti_color', sanitize_hex_color( $t['color'] ) );
		}
	}

	ngiti_import_menus( $ids );
	return $ids;
}

/**
 * Create and assign the Primary and Footer menus if no menus are assigned yet.
 *
 * @param array $ids Page IDs by slug.
 */
function ngiti_import_menus( $ids ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$menus     = array(
		'primary' => array( __( 'Primary', 'ngiti-dental-theme' ), array( 'about', 'services', 'team', 'faq', 'contact' ) ),
		'footer'  => array( __( 'Footer', 'ngiti-dental-theme' ), array( 'appointment', 'privacy-notice', 'disclaimer' ) ),
	);
	foreach ( $menus as $location => $m ) {
		if ( ! empty( $locations[ $location ] ) ) {
			continue;
		}
		$menu_id = wp_create_nav_menu( $m[0] . ' (Ngiti)' );
		if ( is_wp_error( $menu_id ) ) {
			continue;
		}
		foreach ( $m[1] as $slug ) {
			if ( empty( $ids[ $slug ] ) ) {
				continue;
			}
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-object-id' => $ids[ $slug ],
					'menu-item-object'    => 'page',
					'menu-item-type'      => 'post_type',
					'menu-item-status'    => 'publish',
					'menu-item-title'     => 'team' === $slug ? __( 'Our Dentists', 'ngiti-dental-theme' ) : ( 'services' === $slug ? __( 'Services', 'ngiti-dental-theme' ) : ( 'contact' === $slug ? __( 'Contact', 'ngiti-dental-theme' ) : ( 'about' === $slug ? __( 'About', 'ngiti-dental-theme' ) : ( 'faq' === $slug ? __( 'FAQ', 'ngiti-dental-theme' ) : get_the_title( $ids[ $slug ] ) ) ) ) ),
				)
			);
		}
		$locations[ $location ] = $menu_id;
	}
	set_theme_mod( 'nav_menu_locations', $locations );
}
