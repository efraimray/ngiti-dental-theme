<?php
/**
 * Custom post types: Services, Team members and (private) Appointment requests.
 *
 * Note: themes usually leave post types to plugins. They live here so the theme is a
 * complete client hand-off; if the client switches themes the content stays in the
 * database and reappears when the theme (or a small plugin copy of this file) is active.
 *
 * @package Ngiti_Dental
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the post types.
 */
function ngiti_register_post_types() {
	register_post_type(
		'ngiti_service',
		array(
			'labels'       => array(
				'name'          => __( 'Services', 'ngiti-dental-theme' ),
				'singular_name' => __( 'Service', 'ngiti-dental-theme' ),
				'add_new_item'  => __( 'Add new service', 'ngiti-dental-theme' ),
				'edit_item'     => __( 'Edit service', 'ngiti-dental-theme' ),
				'menu_name'     => __( 'Services', 'ngiti-dental-theme' ),
			),
			'public'       => true,
			'has_archive'  => false,
			'rewrite'      => array( 'slug' => 'service' ),
			'menu_icon'    => 'dashicons-heart',
			'menu_position'=> 21,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ),
		)
	);

	register_post_type(
		'ngiti_team',
		array(
			'labels'             => array(
				'name'          => __( 'Team', 'ngiti-dental-theme' ),
				'singular_name' => __( 'Team member', 'ngiti-dental-theme' ),
				'add_new_item'  => __( 'Add team member', 'ngiti-dental-theme' ),
				'edit_item'     => __( 'Edit team member', 'ngiti-dental-theme' ),
				'menu_name'     => __( 'Team', 'ngiti-dental-theme' ),
			),
			'public'             => false,
			'show_ui'            => true,
			'publicly_queryable' => false,
			'menu_icon'          => 'dashicons-groups',
			'menu_position'      => 22,
			'show_in_rest'       => true,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		)
	);

	register_post_type(
		'ngiti_request',
		array(
			'labels'          => array(
				'name'          => __( 'Appointment requests', 'ngiti-dental-theme' ),
				'singular_name' => __( 'Appointment request', 'ngiti-dental-theme' ),
				'menu_name'     => __( 'Requests', 'ngiti-dental-theme' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_rest'    => false,
			'menu_icon'       => 'dashicons-calendar-alt',
			'menu_position'   => 23,
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'ngiti_register_post_types' );

/**
 * Meta fields per post type: key => array( label, type, help ).
 *
 * @return array
 */
function ngiti_meta_fields() {
	return array(
		'ngiti_service' => array(
			'_ngiti_duration'   => array( __( 'Duration', 'ngiti-dental-theme' ), 'text', __( 'e.g. 45 min', 'ngiti-dental-theme' ) ),
			'_ngiti_price_from' => array( __( 'Starting price (₱, numbers only)', 'ngiti-dental-theme' ), 'number', __( 'Leave empty to hide the price.', 'ngiti-dental-theme' ) ),
			'_ngiti_icon'       => array( __( 'Icon', 'ngiti-dental-theme' ), 'select', '' ),
			'_ngiti_teeth'      => array( __( 'Tooth finder groups', 'ngiti-dental-theme' ), 'text', __( 'Comma list of: all, incisor, canine, premolar, molar, wisdom', 'ngiti-dental-theme' ) ),
		),
		'ngiti_team'    => array(
			'_ngiti_role'        => array( __( 'Role', 'ngiti-dental-theme' ), 'text', __( 'e.g. Lead Dentist', 'ngiti-dental-theme' ) ),
			'_ngiti_credentials' => array( __( 'Credentials', 'ngiti-dental-theme' ), 'text', __( 'e.g. Doctor of Dental Medicine', 'ngiti-dental-theme' ) ),
			'_ngiti_initials'    => array( __( 'Initials for the avatar', 'ngiti-dental-theme' ), 'text', __( 'Used when there is no featured image.', 'ngiti-dental-theme' ) ),
			'_ngiti_color'       => array( __( 'Avatar colour', 'ngiti-dental-theme' ), 'color', '' ),
		),
	);
}

/**
 * Add the details meta box.
 */
function ngiti_add_meta_boxes() {
	foreach ( array_keys( ngiti_meta_fields() ) as $type ) {
		add_meta_box( 'ngiti_details', __( 'Details', 'ngiti-dental-theme' ), 'ngiti_render_meta_box', $type, 'side' );
	}
}
add_action( 'add_meta_boxes', 'ngiti_add_meta_boxes' );

/**
 * Render the meta box fields.
 *
 * @param WP_Post $post Current post.
 */
function ngiti_render_meta_box( $post ) {
	$fields = ngiti_meta_fields();
	if ( empty( $fields[ $post->post_type ] ) ) {
		return;
	}
	wp_nonce_field( 'ngiti_save_meta', 'ngiti_meta_nonce' );
	$icons = array( 'tooth', 'sparkle', 'pliers', 'root', 'braces', 'sun', 'crown', 'smile', 'shield' );
	foreach ( $fields[ $post->post_type ] as $key => $f ) {
		$value = get_post_meta( $post->ID, $key, true );
		echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . esc_html( $f[0] ) . '</strong></label><br>';
		if ( 'select' === $f[1] ) {
			echo '<select id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">';
			foreach ( $icons as $icon ) {
				printf( '<option value="%1$s"%2$s>%1$s</option>', esc_attr( $icon ), selected( $value, $icon, false ) );
			}
			echo '</select>';
		} else {
			$type = 'color' === $f[1] ? 'color' : ( 'number' === $f[1] ? 'number' : 'text' );
			printf(
				'<input class="widefat" type="%1$s" id="%2$s" name="%2$s" value="%3$s"%4$s>',
				esc_attr( $type ),
				esc_attr( $key ),
				esc_attr( $value ),
				'number' === $type ? ' min="0" step="1"' : ''
			);
		}
		if ( $f[2] ) {
			echo '<br><span class="description">' . esc_html( $f[2] ) . '</span>';
		}
		echo '</p>';
	}
}

/**
 * Save meta box values with nonce + capability checks and per-type sanitizing.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function ngiti_save_meta( $post_id, $post ) {
	$fields = ngiti_meta_fields();
	if ( empty( $fields[ $post->post_type ] ) ) {
		return;
	}
	if ( ! isset( $_POST['ngiti_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ngiti_meta_nonce'] ) ), 'ngiti_save_meta' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( $fields[ $post->post_type ] as $key => $f ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		if ( 'number' === $f[1] ) {
			$raw = '' === $raw ? '' : (string) absint( $raw );
		} elseif ( 'color' === $f[1] ) {
			$raw = (string) sanitize_hex_color( $raw );
		} elseif ( '_ngiti_teeth' === $key ) {
			$allowed = array( 'all', 'incisor', 'canine', 'premolar', 'molar', 'wisdom' );
			$raw     = implode( ',', array_intersect( array_map( 'trim', explode( ',', strtolower( $raw ) ) ), $allowed ) );
		} elseif ( '_ngiti_icon' === $key ) {
			$raw = sanitize_key( $raw );
		}
		update_post_meta( $post_id, $key, $raw );
	}
}
add_action( 'save_post', 'ngiti_save_meta', 10, 2 );

/**
 * Extra columns on the Requests list.
 *
 * @param string[] $cols Columns.
 * @return string[]
 */
function ngiti_request_columns( $cols ) {
	$cols['ngiti_ref']  = __( 'Reference', 'ngiti-dental-theme' );
	$cols['ngiti_when'] = __( 'Preferred date', 'ngiti-dental-theme' );
	return $cols;
}
add_filter( 'manage_ngiti_request_posts_columns', 'ngiti_request_columns' );

/**
 * Column content.
 *
 * @param string $col     Column key.
 * @param int    $post_id Post ID.
 */
function ngiti_request_column_content( $col, $post_id ) {
	if ( 'ngiti_ref' === $col ) {
		echo esc_html( get_post_meta( $post_id, '_ngiti_ref', true ) );
	}
	if ( 'ngiti_when' === $col ) {
		echo esc_html( get_post_meta( $post_id, '_ngiti_date', true ) . ' ' . get_post_meta( $post_id, '_ngiti_time', true ) );
	}
}
add_action( 'manage_ngiti_request_posts_custom_column', 'ngiti_request_column_content', 10, 2 );
