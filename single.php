<?php
/**
 * Single post and single service.
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
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'entry-content prose' ); ?>>
				<?php
				if ( 'ngiti_service' === get_post_type() ) {
					$ngiti_duration = get_post_meta( get_the_ID(), '_ngiti_duration', true );
					$ngiti_price    = get_post_meta( get_the_ID(), '_ngiti_price_from', true );
					echo '<p class="service-meta">';
					if ( $ngiti_duration ) {
						echo '<span class="chip">' . esc_html( $ngiti_duration ) . '</span> ';
					}
					if ( $ngiti_price ) {
						echo '<span class="chip chip--price">' . esc_html( ngiti_price( $ngiti_price ) ) . '</span>';
					}
					echo '</p>';
				} else {
					echo '<p class="muted">' . esc_html( get_the_date() ) . '</p>';
				}
				the_content();
				wp_link_pages();
				?>
			</article>
			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</div>
	</section>
	<?php
endwhile;
get_template_part( 'template-parts/cta-band' );
get_footer();
