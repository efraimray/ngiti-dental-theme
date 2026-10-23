<?php
/**
 * Template Name: Contact
 *
 * @package Ngiti_Dental
 */

get_header();
while ( have_posts() ) :
	the_post();
	ngiti_page_hero( get_the_title(), has_excerpt() ? get_the_excerpt() : '' );
	?>
	<section class="section">
		<div class="wrap split" style="align-items:start">
			<div>
				<ul class="contact-list">
					<li><span class="ci"><?php echo ngiti_icon( "pin" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span><span><b><?php esc_html_e( "Address", "ngiti-dental-theme" ); ?></b><?php echo esc_html( ngiti_opt( "address" ) ); ?>, <?php echo esc_html( ngiti_opt( "city" ) ); ?><br><a href="<?php echo esc_url( ngiti_opt( "map_url" ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( "Open in Google Maps", "ngiti-dental-theme" ); ?></a></span></li>
					<li><span class="ci"><?php echo ngiti_icon( "phone" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span><span><b><?php esc_html_e( "Phone", "ngiti-dental-theme" ); ?></b><a href="tel:<?php echo esc_attr( ngiti_tel( ngiti_opt( "phone" ) ) ); ?>"><?php echo esc_html( ngiti_opt( "phone" ) ); ?></a></span></li>
					<li><span class="ci"><?php echo ngiti_icon( "chat" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span><span><b><?php esc_html_e( "Messenger", "ngiti-dental-theme" ); ?></b><a href="<?php echo esc_url( ngiti_opt( "messenger" ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( "#^https?://#", "", ngiti_opt( "messenger" ) ) ); ?></a></span></li>
					<li><span class="ci"><?php echo ngiti_icon( "mail" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span><span><b><?php esc_html_e( "Email", "ngiti-dental-theme" ); ?></b><a href="mailto:<?php echo esc_attr( antispambot( ngiti_opt( "email" ) ) ); ?>"><?php echo esc_html( antispambot( ngiti_opt( "email" ) ) ); ?></a></span></li>
					<li><span class="ci"><?php echo ngiti_icon( "clock" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span><span><b><?php esc_html_e( "Hours", "ngiti-dental-theme" ); ?></b><?php ngiti_open_status(); ?></span></li>
				</ul>
				<div class="panel" style="margin-top:2rem"><?php ngiti_hours_table(); ?></div>
			</div>
			<div>
				<h2 style="font-size:var(--step-2)"><?php esc_html_e( "Send a message", "ngiti-dental-theme" ); ?></h2>
				<?php ngiti_render_form( "message" ); ?>
				<?php if ( get_the_content() ) : ?>
					<div class="entry-content prose" style="margin-top:2rem"><?php the_content(); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
endwhile;

get_footer();
