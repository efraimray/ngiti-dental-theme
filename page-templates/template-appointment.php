<?php
/**
 * Template Name: Appointment Request
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
				<?php ngiti_render_form( "appointment" ); ?>
			</div>
			<div>
				<div class="panel">
					<h2 style="font-size:var(--step-2)"><?php esc_html_e( "Before you send", "ngiti-dental-theme" ); ?></h2>
					<ul class="checklist">
						<li><?php echo ngiti_icon( "clock" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php esc_html_e( "We reply within clinic hours, usually the same day.", "ngiti-dental-theme" ); ?></span></li>
						<li><?php echo ngiti_icon( "calendar" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php esc_html_e( "Your slot is final only after we confirm it by text or call.", "ngiti-dental-theme" ); ?></span></li>
						<li><?php echo ngiti_icon( "phone" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php esc_html_e( "For severe pain or swelling, call us now instead.", "ngiti-dental-theme" ); ?></span></li>
					</ul>
					<?php ngiti_hours_table(); ?>
				</div>
				<?php if ( get_the_content() ) : ?>
					<div class="entry-content prose" style="margin-top:2rem"><?php the_content(); ?></div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
endwhile;

get_footer();
