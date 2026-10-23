<?php
/**
 * Front page: hero with live open status, services, visit steps, team, hours and CTA.
 *
 * @package Ngiti_Dental
 */

get_header();
$ngiti_city = ngiti_opt( 'city' );
$ngiti_town = trim( strtok( $ngiti_city, ',' ) );
?>
<section class="hero" aria-labelledby="hero-title">
	<div class="wrap">
		<div>
			<p class="eyebrow">
				<?php
				/* translators: %s: town name */
				printf( esc_html__( 'Family dental clinic · %s', 'ngiti-dental-theme' ), esc_html( $ngiti_town ) );
				?>
			</p>
			<h1 id="hero-title"><?php echo wp_kses( __( 'Gentle dentistry for every <em>ngiti</em> in the family.', 'ngiti-dental-theme' ), array( 'em' => array() ) ); ?></h1>
			<p class="lead"><?php esc_html_e( 'Check-ups, fillings, braces and kids\' care, explained in plain words before we start. Appointments that begin on time.', 'ngiti-dental-theme' ); ?></p>
			<?php ngiti_open_status(); ?>
			<div class="btn-row">
				<a class="btn btn--primary" href="<?php echo esc_url( ngiti_page_url( 'appointment' ) ); ?>"><?php echo ngiti_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Request an appointment', 'ngiti-dental-theme' ); ?></a>
				<a class="btn btn--ghost" href="tel:<?php echo esc_attr( ngiti_tel( ngiti_opt( 'phone' ) ) ); ?>"><?php echo ngiti_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php echo esc_html( ngiti_opt( 'phone' ) ); ?></a>
			</div>
			<ul class="hero-facts">
				<li><?php echo ngiti_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Costs explained first', 'ngiti-dental-theme' ); ?></li>
				<li><?php echo ngiti_icon( 'smile' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Kids from age one', 'ngiti-dental-theme' ); ?></li>
				<li><?php echo ngiti_icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php esc_html_e( 'Sterilized, logged instruments', 'ngiti-dental-theme' ); ?></li>
			</ul>
		</div>
		<div class="hero-art">
			<?php get_template_part( 'template-parts/hero-arch' ); ?>
			<div class="hero-card" aria-hidden="true">
				<b><?php esc_html_e( 'Tooth 16 · upper right first molar', 'ngiti-dental-theme' ); ?></b>
				<span><?php esc_html_e( 'Sample chart note: filling done, check again in 6 months', 'ngiti-dental-theme' ); ?></span>
			</div>
		</div>
	</div>
</section>

<section class="section section--tint" aria-labelledby="services-title">
	<div class="wrap">
		<div class="section-head">
			<p class="eyebrow"><?php esc_html_e( 'Services', 'ngiti-dental-theme' ); ?></p>
			<h2 id="services-title"><?php esc_html_e( 'Care for every stage, from first tooth to dentures', 'ngiti-dental-theme' ); ?></h2>
			<p class="muted"><?php esc_html_e( 'Starting prices are shown up front. Your exact plan and cost are confirmed after a check-up.', 'ngiti-dental-theme' ); ?> <span class="sample-note"><?php esc_html_e( 'Sample prices', 'ngiti-dental-theme' ); ?></span></p>
		</div>
		<div class="grid grid--4">
			<?php
			foreach ( ngiti_get_services( 4 ) as $ngiti_service ) {
				ngiti_service_card( $ngiti_service );
			}
			?>
		</div>
		<p style="margin-top:1.5rem"><a class="btn btn--ghost" href="<?php echo esc_url( ngiti_page_url( 'services' ) ); ?>"><?php esc_html_e( 'See all services and the tooth finder', 'ngiti-dental-theme' ); ?><?php echo ngiti_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></a></p>
	</div>
</section>

<section class="section" aria-labelledby="steps-title">
	<div class="wrap">
		<div class="section-head">
			<p class="eyebrow"><?php esc_html_e( 'Your visit', 'ngiti-dental-theme' ); ?></p>
			<h2 id="steps-title"><?php esc_html_e( 'How booking works', 'ngiti-dental-theme' ); ?></h2>
		</div>
		<?php get_template_part( 'template-parts/visit-steps' ); ?>
	</div>
</section>

<section class="section section--tint" aria-labelledby="team-title">
	<div class="wrap">
		<div class="section-head">
			<p class="eyebrow"><?php esc_html_e( 'Our team', 'ngiti-dental-theme' ); ?></p>
			<h2 id="team-title"><?php esc_html_e( 'Dentists who explain before they treat', 'ngiti-dental-theme' ); ?></h2>
			<p class="muted"><span class="sample-note"><?php esc_html_e( 'Sample team: fictional names and credentials', 'ngiti-dental-theme' ); ?></span></p>
		</div>
		<div class="grid grid--4">
			<?php
			foreach ( ngiti_get_team() as $ngiti_member ) {
				ngiti_team_card( $ngiti_member );
			}
			?>
		</div>
	</div>
</section>

<section class="section" aria-labelledby="visit-title">
	<div class="wrap split">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'Find us', 'ngiti-dental-theme' ); ?></p>
			<h2 id="visit-title"><?php esc_html_e( 'In the city center, a short walk from the plaza', 'ngiti-dental-theme' ); ?></h2>
			<ul class="contact-list">
				<li><span class="ci"><?php echo ngiti_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span><span><b><?php echo esc_html( ngiti_opt( 'address' ) ); ?></b><?php echo esc_html( $ngiti_city ); ?><br><a href="<?php echo esc_url( ngiti_opt( 'map_url' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open in Google Maps', 'ngiti-dental-theme' ); ?></a></span></li>
				<li><span class="ci"><?php echo ngiti_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?></span><span><b><?php esc_html_e( 'Call or text', 'ngiti-dental-theme' ); ?></b><a href="tel:<?php echo esc_attr( ngiti_tel( ngiti_opt( 'phone' ) ) ); ?>"><?php echo esc_html( ngiti_opt( 'phone' ) ); ?></a></span></li>
			</ul>
		</div>
		<div class="panel">
			<h3><?php esc_html_e( 'Clinic hours', 'ngiti-dental-theme' ); ?></h3>
			<?php ngiti_hours_table(); ?>
			<p class="muted" style="margin:1rem 0 0"><?php esc_html_e( 'Closed on regular holidays. Lunch break 12:00 NN – 1:00 PM on weekdays.', 'ngiti-dental-theme' ); ?></p>
		</div>
	</div>
</section>

<?php
get_template_part( 'template-parts/cta-band' );
get_footer();
