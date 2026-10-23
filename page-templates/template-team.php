<?php
/**
 * Template Name: Team
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
			<p class="sample-note" style="margin-bottom:1.5rem"><?php esc_html_e( "Sample team: names, photos and credentials are fictional placeholders.", "ngiti-dental-theme" ); ?></p>
			<div class="grid grid--4">
				<?php
				foreach ( ngiti_get_team() as $ngiti_member ) {
					ngiti_team_card( $ngiti_member );
				}
				?>
			</div>
			<?php if ( get_the_content() ) : ?>
				<div class="entry-content prose" style="margin-top:2rem"><?php the_content(); ?></div>
			<?php endif; ?>
		</div>
	</section>
	<?php
endwhile;
get_template_part( 'template-parts/cta-band' );
get_footer();
