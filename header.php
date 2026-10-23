<?php
/**
 * Site header: skip link, brand, primary navigation, theme toggle and Book button.
 *
 * @package Ngiti_Dental
 */

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0a6b66">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'ngiti-dental-theme' ); ?></a>
<header class="site-header" role="banner">
	<div class="wrap">
		<?php ngiti_brand(); ?>
		<nav class="primary-nav" id="primary-nav" aria-label="<?php esc_attr_e( 'Main menu', 'ngiti-dental-theme' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_id'        => 'primary-menu',
						'depth'          => 1,
					)
				);
			} else {
				ngiti_fallback_menu();
			}
			?>
			<a class="btn btn--primary nav-book" href="<?php echo esc_url( ngiti_page_url( 'appointment' ) ); ?>"><?php esc_html_e( 'Book a visit', 'ngiti-dental-theme' ); ?></a>
		</nav>
		<div class="header-actions">
			<button class="icon-btn theme-toggle" type="button" data-ngiti-theme-toggle aria-label="<?php esc_attr_e( 'Switch light or dark mode', 'ngiti-dental-theme' ); ?>">
				<span class="icon-moon"><?php echo ngiti_icon( 'moon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
				<span class="icon-sun"><?php echo ngiti_icon( 'sun' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span>
			</button>
			<a class="btn btn--primary btn--small header-book" href="<?php echo esc_url( ngiti_page_url( 'appointment' ) ); ?>"><?php esc_html_e( 'Book a visit', 'ngiti-dental-theme' ); ?></a>
			<button class="icon-btn menu-toggle" type="button" aria-controls="primary-nav" aria-expanded="false" data-ngiti-menu-toggle>
				<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'ngiti-dental-theme' ); ?></span>
				<?php echo ngiti_icon( 'menu' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
			</button>
		</div>
	</div>
</header>
<main id="main" tabindex="-1">
