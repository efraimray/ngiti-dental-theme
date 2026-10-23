<?php
/**
 * Site footer: clinic summary, hours, contact, links, social placeholders and the
 * sticky call / Messenger buttons.
 *
 * @package Ngiti_Dental
 */

$ngiti_phone = ngiti_opt( 'phone' );
?>
</main>
<footer class="site-footer" role="contentinfo">
	<div class="wrap">
		<div class="footer-grid">
			<div>
				<?php ngiti_brand(); ?>
				<p class="muted" style="margin-top:1rem"><?php esc_html_e( 'Gentle, on-time dental care for families. We explain every step and every peso before we start.', 'ngiti-dental-theme' ); ?></p>
				<div class="social-links">
					<a href="<?php echo esc_url( ngiti_opt( 'facebook' ) ); ?>"><?php echo ngiti_icon( 'share' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Facebook', 'ngiti-dental-theme' ); ?></a>
					<a href="<?php echo esc_url( ngiti_opt( 'instagram' ) ); ?>"><?php echo ngiti_icon( 'camera' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Instagram', 'ngiti-dental-theme' ); ?></a>
				</div>
			</div>
			<div>
				<h2><?php esc_html_e( 'Clinic hours', 'ngiti-dental-theme' ); ?></h2>
				<ul class="footer-hours">
					<?php foreach ( ngiti_hours_rows() as $ngiti_row ) : ?>
						<li><b><?php echo esc_html( $ngiti_row[0] ); ?></b><span class="muted"><?php echo esc_html( $ngiti_row[1] ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div>
				<h2><?php esc_html_e( 'Visit or call', 'ngiti-dental-theme' ); ?></h2>
				<ul>
					<li><?php echo esc_html( ngiti_opt( 'address' ) ); ?><br><?php echo esc_html( ngiti_opt( 'city' ) ); ?></li>
					<li><a href="<?php echo esc_url( ngiti_opt( 'map_url' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open in Google Maps', 'ngiti-dental-theme' ); ?></a></li>
					<li><a href="tel:<?php echo esc_attr( ngiti_tel( $ngiti_phone ) ); ?>"><?php echo esc_html( $ngiti_phone ); ?></a></li>
					<li><a href="mailto:<?php echo esc_attr( antispambot( ngiti_opt( 'email' ) ) ); ?>"><?php echo esc_html( antispambot( ngiti_opt( 'email' ) ) ); ?></a></li>
				</ul>
			</div>
			<div>
				<h2><?php esc_html_e( 'Patients', 'ngiti-dental-theme' ); ?></h2>
				<?php
				if ( has_nav_menu( 'footer' ) ) {
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'container'      => false,
							'depth'          => 1,
						)
					);
				} else {
					echo '<ul>';
					foreach ( array( 'appointment' => __( 'Request an appointment', 'ngiti-dental-theme' ), 'faq' => __( 'Patient FAQ', 'ngiti-dental-theme' ), 'privacy-notice' => __( 'Privacy notice', 'ngiti-dental-theme' ), 'disclaimer' => __( 'Disclaimer', 'ngiti-dental-theme' ) ) as $ngiti_slug => $ngiti_label ) {
						printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( ngiti_page_url( $ngiti_slug ) ), esc_html( $ngiti_label ) );
					}
					echo '</ul>';
				}
				?>
			</div>
		</div>
		<div class="footer-bottom">
			<span>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'Website content is general information, not dental advice.', 'ngiti-dental-theme' ); ?></span>
			<span><?php esc_html_e( 'Theme by', 'ngiti-dental-theme' ); ?> <a href="https://www.efraimray.com/">Efraim Ray</a></span>
		</div>
	</div>
</footer>
<div class="quick-contact" aria-label="<?php esc_attr_e( 'Quick contact', 'ngiti-dental-theme' ); ?>" role="region">
	<a class="qc-chat" href="<?php echo esc_url( ngiti_opt( 'messenger' ) ); ?>" target="_blank" rel="noopener"><?php echo ngiti_icon( 'chat' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Messenger', 'ngiti-dental-theme' ); ?></a>
	<a class="qc-call" href="tel:<?php echo esc_attr( ngiti_tel( $ngiti_phone ) ); ?>"><?php echo ngiti_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php echo esc_html( $ngiti_phone ); ?></a>
</div>
<?php wp_footer(); ?>
</body>
</html>
