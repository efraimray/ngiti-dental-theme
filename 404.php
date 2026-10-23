<?php
/**
 * Not found.
 *
 * @package Ngiti_Dental
 */

get_header();
?>
<section class="section error-404">
	<div class="wrap">
		<p class="big" aria-hidden="true">404</p>
		<h1><?php esc_html_e( 'This page has gone missing, like a baby tooth.', 'ngiti-dental-theme' ); ?></h1>
		<p class="lead" style="margin-inline:auto"><?php esc_html_e( 'The link may be old or mistyped. These pages can help:', 'ngiti-dental-theme' ); ?></p>
		<div class="btn-row">
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to the home page', 'ngiti-dental-theme' ); ?></a>
			<a class="btn btn--ghost" href="<?php echo esc_url( ngiti_page_url( 'services' ) ); ?>"><?php esc_html_e( 'Services', 'ngiti-dental-theme' ); ?></a>
			<a class="btn btn--ghost" href="<?php echo esc_url( ngiti_page_url( 'contact' ) ); ?>"><?php esc_html_e( 'Contact', 'ngiti-dental-theme' ); ?></a>
		</div>
	</div>
</section>
<?php
get_footer();
