<?php
/**
 * Template Name: Privacy Notice
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
			<div class="entry-content prose"><?php ngiti_content_or_sample( 'privacy' ); ?></div>
			<p class="muted"><?php
			/* translators: %s: date */
			printf( esc_html__( "Last updated: %s", "ngiti-dental-theme" ), esc_html( get_the_modified_date() ) );
			?></p>
		</div>
	</section>
	<?php
endwhile;

get_footer();
