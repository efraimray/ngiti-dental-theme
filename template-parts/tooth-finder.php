<?php
/**
 * Tooth finder: an FDI tooth chart. Pick a tooth to see which services usually apply.
 * Buttons are rendered here; assets/js/theme.js adds the behaviour.
 *
 * @package Ngiti_Dental
 */

$ngiti_services = array();
foreach ( ngiti_get_services() as $ngiti_s ) {
	$ngiti_services[] = array(
		't' => $ngiti_s['title'],
		'g' => array_values( (array) $ngiti_s['teeth'] ),
		'd' => (string) $ngiti_s['duration'],
		'p' => ngiti_price( $ngiti_s['price_from'] ),
	);
}
$ngiti_rows = array(
	'upper' => array( 18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28 ),
	'lower' => array( 48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38 ),
);
?>
<div class="tooth-finder" data-ngiti-teeth="<?php echo esc_attr( wp_json_encode( $ngiti_services ) ); ?>">
	<div class="tooth-chart">
		<p class="eyebrow"><?php esc_html_e( 'Tooth finder', 'ngiti-dental-theme' ); ?> <span class="fdi"><?php esc_html_e( 'FDI chart', 'ngiti-dental-theme' ); ?></span></p>
		<p class="muted"><?php esc_html_e( 'Which tooth is bothering you? Tap it on the chart. You are looking at the patient, so their right side is on your left.', 'ngiti-dental-theme' ); ?></p>
		<?php foreach ( $ngiti_rows as $ngiti_jaw => $ngiti_teeth ) : ?>
			<div class="tooth-jaw-label"><span><?php esc_html_e( 'Right', 'ngiti-dental-theme' ); ?></span><span><?php echo 'upper' === $ngiti_jaw ? esc_html__( 'Upper teeth', 'ngiti-dental-theme' ) : esc_html__( 'Lower teeth', 'ngiti-dental-theme' ); ?></span><span><?php esc_html_e( 'Left', 'ngiti-dental-theme' ); ?></span></div>
			<div class="tooth-row tooth-row--<?php echo esc_attr( $ngiti_jaw ); ?>" role="group" aria-label="<?php echo 'upper' === $ngiti_jaw ? esc_attr__( 'Upper teeth', 'ngiti-dental-theme' ) : esc_attr__( 'Lower teeth', 'ngiti-dental-theme' ); ?>">
				<?php foreach ( $ngiti_teeth as $ngiti_fdi ) : ?>
					<button type="button" class="tooth<?php echo ( $ngiti_fdi % 10 ) >= 6 ? ' tooth--molar' : ''; ?>" data-fdi="<?php echo esc_attr( $ngiti_fdi ); ?>" aria-pressed="false"><?php echo esc_html( $ngiti_fdi ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="tooth-result" aria-live="polite" data-ngiti-tooth-result>
		<h3><?php esc_html_e( 'Pick a tooth', 'ngiti-dental-theme' ); ?></h3>
		<p class="muted"><?php esc_html_e( 'We will show the tooth\'s name and the services that usually apply. Only a check-up can tell for sure.', 'ngiti-dental-theme' ); ?></p>
	</div>
</div>
