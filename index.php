<?php
/**
 * Fallback template (blog index, archives, search).
 *
 * @package Ngiti_Dental
 */

get_header();
$ngiti_title = is_search()
	/* translators: %s: search query */
	? sprintf( __( 'Search results for "%s"', 'ngiti-dental-theme' ), get_search_query() )
	: ( is_archive() ? wp_strip_all_tags( get_the_archive_title() ) : __( 'Clinic news & dental tips', 'ngiti-dental-theme' ) );
ngiti_page_hero( $ngiti_title );
?>
<section class="section">
	<div class="wrap">
		<?php if ( have_posts() ) : ?>
			<div class="post-list">
				<?php
				while ( have_posts() ) :
					the_post();
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'card' ); ?>>
						<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="muted"><?php echo esc_html( get_the_date() ); ?></p>
						<?php the_excerpt(); ?>
					</article>
				<?php endwhile; ?>
			</div>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing here yet.', 'ngiti-dental-theme' ); ?></p>
			<?php get_search_form(); ?>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
