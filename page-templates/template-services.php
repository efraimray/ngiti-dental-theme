<?php
/**
 * Template Name: Services
 *
 * @package Ngiti_Dental
 */

get_header();
while ( have_posts() ) :
	the_post();
	ngiti_page_hero( get_the_title(), has_excerpt() ? get_the_excerpt() : '' );
	?>
	<section class="section">
		<div class="wrap">
			<div class="section-head">
				<p class="muted"><?php esc_html_e( "Durations are typical. Starting prices are for simple cases; your exact plan and cost are confirmed after a check-up.", "ngiti-dental-theme" ); ?> <span class="sample-note"><?php esc_html_e( "Sample prices", "ngiti-dental-theme" ); ?></span></p>
			</div>
			<div class="grid grid--3">
				<?php
				foreach ( ngiti_get_services() as $ngiti_service ) {
					ngiti_service_card( $ngiti_service );
				}
				?>
			</div>
			<?php if ( get_the_content() ) : ?>
				<div class="entry-content prose" style="margin-top:2rem"><?php the_content(); ?></div>
			<?php endif; ?>
		</div>
	</section>
	<section class="section section--tint" aria-labelledby="finder-title">
		<div class="wrap">
			<div class="section-head">
				<h2 id="finder-title"><?php esc_html_e( "Not sure what you need?", "ngiti-dental-theme" ); ?></h2>
				<p class="muted"><?php esc_html_e( "Use the tooth chart dentists use (FDI numbering) to point at the tooth.", "ngiti-dental-theme" ); ?></p>
			</div>
			<?php get_template_part( "template-parts/tooth-finder" ); ?>
		</div>
	</section>
	<?php
endwhile;
get_template_part( 'template-parts/cta-band' );
get_footer();
