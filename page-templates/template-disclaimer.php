<?php
/**
 * Template Name: Disclaimer
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
			<div class="notice-box"><p><strong><?php esc_html_e( "Not medical or dental advice.", "ngiti-dental-theme" ); ?></strong> <?php esc_html_e( "Always consult a licensed dentist about your own situation.", "ngiti-dental-theme" ); ?></p></div>
			<div class="entry-content prose"><?php ngiti_content_or_sample( 'disclaimer' ); ?></div>
		</div>
	</section>
	<?php
endwhile;

get_footer();
