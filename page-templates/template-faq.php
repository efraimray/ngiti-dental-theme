<?php
/**
 * Template Name: Patient FAQ
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
			<?php if ( get_the_content() ) : ?>
				<div class="entry-content"><?php the_content(); ?></div>
			<?php else : ?>
				<div class="faq-list">
					<?php foreach ( ngiti_get_faq() as $ngiti_item ) : ?>
						<details><summary><?php echo esc_html( $ngiti_item["q"] ); ?></summary><p><?php echo esc_html( $ngiti_item["a"] ); ?></p></details>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<p class="muted" style="margin-top:2rem"><?php esc_html_e( "Still have a question?", "ngiti-dental-theme" ); ?> <a href="<?php echo esc_url( ngiti_page_url( "contact" ) ); ?>"><?php esc_html_e( "Send us a message.", "ngiti-dental-theme" ); ?></a></p>
		</div>
	</section>
	<?php
endwhile;
get_template_part( 'template-parts/cta-band' );
get_footer();
