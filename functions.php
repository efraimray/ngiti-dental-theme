<?php
/**
 * Ngiti Dental theme bootstrap.
 *
 * @package Ngiti_Dental
 */

defined( 'ABSPATH' ) || exit;

define( 'NGITI_VERSION', '1.0.0' );
define( 'NGITI_DIR', get_template_directory() );
define( 'NGITI_URI', get_template_directory_uri() );

require NGITI_DIR . '/inc/template-tags.php';
require NGITI_DIR . '/inc/customizer.php';
require NGITI_DIR . '/inc/post-types.php';
require NGITI_DIR . '/inc/appointment-form.php';
require NGITI_DIR . '/inc/seo.php';
require NGITI_DIR . '/inc/sample-content.php';

/**
 * Theme supports, menus and translations.
 */
function ngiti_setup() {
	load_theme_textdomain( 'ngiti-dental-theme', NGITI_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 96,
			'width'       => 320,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
	add_editor_style( array( 'assets/css/fonts.css', 'assets/css/main.css' ) );

	register_nav_menus(
		array(
			'primary' => __( 'Primary menu', 'ngiti-dental-theme' ),
			'footer'  => __( 'Footer menu', 'ngiti-dental-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'ngiti_setup' );

/**
 * Styles and scripts. Everything is local: no CDN, no jQuery.
 */
function ngiti_assets() {
	wp_enqueue_style( 'ngiti-fonts', NGITI_URI . '/assets/css/fonts.css', array(), NGITI_VERSION );
	wp_enqueue_style( 'ngiti-main', NGITI_URI . '/assets/css/main.css', array( 'ngiti-fonts' ), NGITI_VERSION );
	wp_enqueue_script( 'ngiti-theme', NGITI_URI . '/assets/js/theme.js', array(), NGITI_VERSION, array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script(
		'ngiti-theme',
		'ngitiTheme',
		array(
			'demo' => false,
			'i18n' => array(
				'open'        => __( 'Open now', 'ngiti-dental-theme' ),
				'soon'        => __( 'Closing soon', 'ngiti-dental-theme' ),
				'closed'      => __( 'Closed', 'ngiti-dental-theme' ),
				/* translators: %s: time */
				'closesAt'    => __( 'Closes at %s', 'ngiti-dental-theme' ),
				/* translators: %s: time */
				'opensAt'     => __( 'Opens at %s', 'ngiti-dental-theme' ),
				/* translators: %s: time */
				'opensTomorrow' => __( 'Opens tomorrow at %s', 'ngiti-dental-theme' ),
				/* translators: 1: weekday name, 2: time */
				'opensDay'    => __( 'Opens %1$s at %2$s', 'ngiti-dental-theme' ),
				'required'    => __( 'This field is required.', 'ngiti-dental-theme' ),
				'mobile'      => __( 'Enter a PH mobile number, e.g. 0917 555 0142.', 'ngiti-dental-theme' ),
				'email'       => __( 'Enter a valid email address.', 'ngiti-dental-theme' ),
				'date'        => __( 'Choose a date from tomorrow onwards.', 'ngiti-dental-theme' ),
				'sunday'      => __( 'The clinic is closed on Sundays. Please choose another day.', 'ngiti-dental-theme' ),
				'consent'     => __( 'Please agree to the privacy notice so we can contact you.', 'ngiti-dental-theme' ),
				'fix'         => __( 'Please fix the highlighted fields.', 'ngiti-dental-theme' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'ngiti_assets' );

/**
 * Apply the saved light/dark choice before first paint (prevents a flash).
 */
function ngiti_theme_preference_script() {
	echo "<script>try{var t=localStorage.getItem('ngiti-theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}</script>\n";
}
add_action( 'wp_head', 'ngiti_theme_preference_script', 1 );

/**
 * Preload the two most used font files.
 */
function ngiti_preload_fonts() {
	foreach ( array( 'figtree-latin-400-normal.woff2', 'young-serif-latin-400-normal.woff2' ) as $font ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( NGITI_URI . '/assets/fonts/' . $font ) );
	}
}
add_action( 'wp_head', 'ngiti_preload_fonts', 2 );

/**
 * Body classes used by the stylesheet.
 *
 * @param string[] $classes Existing classes.
 * @return string[]
 */
function ngiti_body_classes( $classes ) {
	if ( is_page_template() ) {
		$classes[] = 'has-page-template';
	}
	return $classes;
}
add_filter( 'body_class', 'ngiti_body_classes' );
