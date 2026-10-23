<?php
/**
 * Template Name: About
 *
 * @package Ngiti_Dental
 */

get_header();
while ( have_posts() ) :
	the_post();
	ngiti_page_hero( get_the_title(), has_excerpt() ? get_the_excerpt() : '' );
	?>
	<section class="section">
		<div class="wrap split">
			<div class="entry-content prose"><?php ngiti_content_or_sample( 'about' ); ?></div>
			<div class="panel">
				<h2 style="font-size:var(--step-2)"><?php esc_html_e( "What to expect", "ngiti-dental-theme" ); ?></h2>
				<ul class="checklist">
					<li><?php echo ngiti_icon( "check" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php esc_html_e( "A written estimate before treatment, in pesos.", "ngiti-dental-theme" ); ?></span></li>
					<li><?php echo ngiti_icon( "check" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php esc_html_e( "Instruments sterilized and logged after every patient.", "ngiti-dental-theme" ); ?></span></li>
					<li><?php echo ngiti_icon( "check" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php esc_html_e( "A reminder text the day before your visit.", "ngiti-dental-theme" ); ?></span></li>
					<li><?php echo ngiti_icon( "check" ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><span><?php esc_html_e( "A stop signal you can use at any time during treatment.", "ngiti-dental-theme" ); ?></span></li>
				</ul>
			</div>
		</div>
	</section>
	<section class="section section--tint" aria-labelledby="steps-title">
		<div class="wrap">
			<div class="section-head"><h2 id="steps-title"><?php esc_html_e( "How booking works", "ngiti-dental-theme" ); ?></h2></div>
			<?php get_template_part( "template-parts/visit-steps" ); ?>
		</div>
	</section>
	<?php
endwhile;
get_template_part( 'template-parts/cta-band' );
get_footer();
