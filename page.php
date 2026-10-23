<?php
/**
 * Default page template.
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
			<div class="entry-content prose"><?php the_content(); ?></div>
			<?php wp_link_pages(); ?>
		</div>
	</section>
	<?php
endwhile;
get_template_part( 'template-parts/cta-band' );
get_footer();
