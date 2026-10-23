<?php
/**
 * Call-to-action band used at the bottom of most pages.
 *
 * @package Ngiti_Dental
 */

?>
<section class="section" aria-labelledby="cta-title">
	<div class="wrap">
		<div class="cta-band">
			<div>
				<h2 id="cta-title"><?php esc_html_e( 'Ready for your check-up?', 'ngiti-dental-theme' ); ?></h2>
				<p><?php esc_html_e( 'Send a request in two minutes. We confirm by text or call within clinic hours.', 'ngiti-dental-theme' ); ?></p>
			</div>
			<div class="btn-row">
				<a class="btn btn--primary" href="<?php echo esc_url( ngiti_page_url( 'appointment' ) ); ?>"><?php echo ngiti_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Request an appointment', 'ngiti-dental-theme' ); ?></a>
				<a class="btn btn--ghost" href="tel:<?php echo esc_attr( ngiti_tel( ngiti_opt( 'phone' ) ) ); ?>"><?php echo ngiti_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php echo esc_html( ngiti_opt( 'phone' ) ); ?></a>
			</div>
		</div>
	</div>
</section>
