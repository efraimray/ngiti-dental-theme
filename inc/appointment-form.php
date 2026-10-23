<?php
/**
 * Appointment request and contact form: markup, shortcode and admin-post handler.
 *
 * Flow: the form posts to wp-admin/admin-post.php (action=ngiti_request). The handler
 * checks the nonce, honeypot and a short rate limit, sanitizes and validates every
 * field, saves a private "Appointment request" post, emails the clinic with wp_mail()
 * and redirects back with a reference number (Post/Redirect/Get, so a refresh never
 * sends twice).
 *
 * @package Ngiti_Dental
 */

defined( 'ABSPATH' ) || exit;

/**
 * Error messages by field key.
 *
 * @return string[]
 */
function ngiti_form_messages() {
	return array(
		'name'    => __( 'Please enter your name.', 'ngiti-dental-theme' ),
		'mobile'  => __( 'Enter a PH mobile number, e.g. 0917 555 0142.', 'ngiti-dental-theme' ),
		'email'   => __( 'Enter a valid email address or leave it empty.', 'ngiti-dental-theme' ),
		'service' => __( 'Choose a service.', 'ngiti-dental-theme' ),
		'date'    => __( 'Choose a date from tomorrow onwards when the clinic is open.', 'ngiti-dental-theme' ),
		'message' => __( 'Please write a short message.', 'ngiti-dental-theme' ),
		'consent' => __( 'Please agree to the privacy notice so we can contact you.', 'ngiti-dental-theme' ),
		'spam'    => __( 'Your request could not be sent. Please call us instead.', 'ngiti-dental-theme' ),
		'wait'    => __( 'Please wait a minute before sending another request.', 'ngiti-dental-theme' ),
		'nonce'   => __( 'The form expired. Please try again.', 'ngiti-dental-theme' ),
		'mail'    => __( 'We saved your request, but the email notice failed. We will still see it in the clinic.', 'ngiti-dental-theme' ),
	);
}

/**
 * Normalize a Philippine mobile number to 09XXXXXXXXX, or return '' if invalid.
 *
 * @param string $raw Raw input.
 * @return string
 */
function ngiti_normalize_mobile( $raw ) {
	$digits = preg_replace( '/\D+/', '', (string) $raw );
	if ( 0 === strpos( $digits, '639' ) && 12 === strlen( $digits ) ) {
		$digits = '0' . substr( $digits, 2 );
	}
	return preg_match( '/^09\d{9}$/', $digits ) ? $digits : '';
}

/**
 * Validate a preferred date: Y-m-d, tomorrow or later, within 120 days, clinic open that day.
 *
 * @param string $date Date string.
 * @return bool
 */
function ngiti_valid_date( $date ) {
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $date ) ) {
		return false;
	}
	$tz  = wp_timezone();
	$day = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $tz );
	if ( ! $day || $day->format( 'Y-m-d' ) !== $date ) {
		return false;
	}
	$today = new DateTimeImmutable( 'today', $tz );
	$diff  = (int) $today->diff( $day )->format( '%r%a' );
	if ( $diff < 1 || $diff > 120 ) {
		return false;
	}
	$week = ngiti_week();
	return ! empty( $week[ (int) $day->format( 'N' ) ] );
}

/**
 * Print the form (or the success panel after a redirect).
 *
 * @param string $type "appointment" or "message".
 */
function ngiti_render_form( $type = 'appointment' ) {
	$type     = 'message' === $type ? 'message' : 'appointment';
	$form_id  = 'ngiti-' . $type . '-form';
	// Read-only display flags set by our own redirect; nothing is saved from them.
	$status   = isset( $_GET['ngiti_status'] ) ? sanitize_key( wp_unslash( $_GET['ngiti_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$for      = isset( $_GET['ngiti_form'] ) ? sanitize_key( wp_unslash( $_GET['ngiti_form'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ref      = isset( $_GET['ngiti_ref'] ) ? preg_replace( '/[^A-Z0-9-]/', '', strtoupper( sanitize_text_field( wp_unslash( $_GET['ngiti_ref'] ) ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$errors   = isset( $_GET['ngiti_errors'] ) ? array_map( 'sanitize_key', explode( ',', sanitize_text_field( wp_unslash( $_GET['ngiti_errors'] ) ) ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$messages = ngiti_form_messages();

	echo '<div class="form-card" id="' . esc_attr( $form_id ) . '">';

	if ( $for === $type && 'sent' === $status ) {
		echo '<div class="form-success" role="status" tabindex="-1">';
		echo '<span class="success-icon">' . ngiti_icon( 'check' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		echo '<h2>' . esc_html( 'message' === $type ? __( 'Message sent', 'ngiti-dental-theme' ) : __( 'Request received', 'ngiti-dental-theme' ) ) . '</h2>';
		echo '<p>' . esc_html__( 'Your reference number:', 'ngiti-dental-theme' ) . '</p>';
		echo '<p class="ref">' . esc_html( $ref ) . '</p>';
		echo '<p>' . esc_html__( 'We will text or call you within clinic hours to confirm. Your slot is final only after we confirm it.', 'ngiti-dental-theme' ) . '</p>';
		echo '</div></div>';
		return;
	}

	if ( $for === $type && 'error' === $status && $errors ) {
		echo '<div class="form-notice form-notice--error" role="alert"><strong>' . esc_html__( 'Please check the form:', 'ngiti-dental-theme' ) . '</strong><ul>';
		foreach ( $errors as $e ) {
			if ( isset( $messages[ $e ] ) ) {
				echo '<li>' . esc_html( $messages[ $e ] ) . '</li>';
			}
		}
		echo '</ul></div>';
	}

	$services = ngiti_get_services();
	$tomorrow = wp_date( 'Y-m-d', time() + DAY_IN_SECONDS );
	$closed   = array();
	foreach ( ngiti_week() as $n => $ranges ) {
		if ( empty( $ranges ) ) {
			$closed[] = (int) $n;
		}
	}
	?>
	<form class="ngiti-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate data-ngiti-form="<?php echo esc_attr( $type ); ?>" data-closed-days="<?php echo esc_attr( implode( ',', $closed ) ); ?>">
		<input type="hidden" name="action" value="ngiti_request">
		<input type="hidden" name="ngiti_form" value="<?php echo esc_attr( $type ); ?>">
		<input type="hidden" name="ngiti_return" value="<?php echo esc_url( get_permalink() ? get_permalink() : home_url( '/' ) ); ?>">
		<?php wp_nonce_field( 'ngiti_request', 'ngiti_nonce' ); ?>
		<div class="hp-field" aria-hidden="true">
			<label for="<?php echo esc_attr( $form_id ); ?>-website"><?php esc_html_e( 'Leave this empty', 'ngiti-dental-theme' ); ?></label>
			<input type="text" id="<?php echo esc_attr( $form_id ); ?>-website" name="ngiti_website" tabindex="-1" autocomplete="off">
		</div>
		<div class="form-grid">
			<div class="field">
				<label for="<?php echo esc_attr( $form_id ); ?>-name"><?php esc_html_e( 'Full name', 'ngiti-dental-theme' ); ?> <span class="req" aria-hidden="true">*</span></label>
				<input id="<?php echo esc_attr( $form_id ); ?>-name" name="ngiti_name" type="text" autocomplete="name" required maxlength="80">
				<p class="error" id="<?php echo esc_attr( $form_id ); ?>-name-error" aria-live="polite"></p>
			</div>
			<div class="field">
				<label for="<?php echo esc_attr( $form_id ); ?>-mobile"><?php esc_html_e( 'Mobile number', 'ngiti-dental-theme' ); ?> <span class="req" aria-hidden="true">*</span></label>
				<input id="<?php echo esc_attr( $form_id ); ?>-mobile" name="ngiti_mobile" type="tel" inputmode="tel" autocomplete="tel" placeholder="0917 555 0142" required data-validate="mobile">
				<p class="error" id="<?php echo esc_attr( $form_id ); ?>-mobile-error" aria-live="polite"></p>
			</div>
			<div class="field">
				<label for="<?php echo esc_attr( $form_id ); ?>-email"><?php esc_html_e( 'Email (optional)', 'ngiti-dental-theme' ); ?></label>
				<input id="<?php echo esc_attr( $form_id ); ?>-email" name="ngiti_email" type="email" autocomplete="email" data-validate="email">
				<p class="error" id="<?php echo esc_attr( $form_id ); ?>-email-error" aria-live="polite"></p>
			</div>
			<?php if ( 'appointment' === $type ) : ?>
			<div class="field">
				<label for="<?php echo esc_attr( $form_id ); ?>-service"><?php esc_html_e( 'Service', 'ngiti-dental-theme' ); ?> <span class="req" aria-hidden="true">*</span></label>
				<select id="<?php echo esc_attr( $form_id ); ?>-service" name="ngiti_service" required>
					<option value=""><?php esc_html_e( 'Choose a service', 'ngiti-dental-theme' ); ?></option>
					<?php foreach ( $services as $s ) : ?>
						<option value="<?php echo esc_attr( $s['title'] ); ?>"><?php echo esc_html( $s['title'] ); ?></option>
					<?php endforeach; ?>
					<option value="<?php esc_attr_e( 'Not sure / consultation', 'ngiti-dental-theme' ); ?>"><?php esc_html_e( 'Not sure / consultation', 'ngiti-dental-theme' ); ?></option>
				</select>
				<p class="error" id="<?php echo esc_attr( $form_id ); ?>-service-error" aria-live="polite"></p>
			</div>
			<div class="field">
				<label for="<?php echo esc_attr( $form_id ); ?>-date"><?php esc_html_e( 'Preferred date', 'ngiti-dental-theme' ); ?> <span class="req" aria-hidden="true">*</span></label>
				<input id="<?php echo esc_attr( $form_id ); ?>-date" name="ngiti_date" type="date" min="<?php echo esc_attr( $tomorrow ); ?>" required data-validate="date">
				<p class="hint"><?php esc_html_e( 'Closed on Sundays and holidays.', 'ngiti-dental-theme' ); ?></p>
				<p class="error" id="<?php echo esc_attr( $form_id ); ?>-date-error" aria-live="polite"></p>
			</div>
			<fieldset class="field">
				<legend><?php esc_html_e( 'Preferred time', 'ngiti-dental-theme' ); ?></legend>
				<div class="choice-row">
					<label class="choice"><input type="radio" name="ngiti_time" value="morning" checked><span><?php esc_html_e( 'Morning', 'ngiti-dental-theme' ); ?></span></label>
					<label class="choice"><input type="radio" name="ngiti_time" value="afternoon"><span><?php esc_html_e( 'Afternoon', 'ngiti-dental-theme' ); ?></span></label>
				</div>
			</fieldset>
			<fieldset class="field">
				<legend><?php esc_html_e( 'Have you visited us before?', 'ngiti-dental-theme' ); ?></legend>
				<div class="choice-row">
					<label class="choice"><input type="radio" name="ngiti_patient" value="new" checked><span><?php esc_html_e( 'First visit', 'ngiti-dental-theme' ); ?></span></label>
					<label class="choice"><input type="radio" name="ngiti_patient" value="returning"><span><?php esc_html_e( 'Returning patient', 'ngiti-dental-theme' ); ?></span></label>
				</div>
			</fieldset>
			<?php endif; ?>
			<div class="field full">
				<label for="<?php echo esc_attr( $form_id ); ?>-message"><?php echo 'message' === $type ? esc_html__( 'Message', 'ngiti-dental-theme' ) . ' <span class="req" aria-hidden="true">*</span>' : esc_html__( 'Anything we should know? (optional)', 'ngiti-dental-theme' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped pieces. ?></label>
				<textarea id="<?php echo esc_attr( $form_id ); ?>-message" name="ngiti_message" maxlength="1000"<?php echo 'message' === $type ? ' required' : ''; ?>></textarea>
				<p class="error" id="<?php echo esc_attr( $form_id ); ?>-message-error" aria-live="polite"></p>
			</div>
			<div class="field full">
				<label class="consent"><input type="checkbox" name="ngiti_consent" value="1" required id="<?php echo esc_attr( $form_id ); ?>-consent">
					<span>
					<?php
					printf(
						/* translators: %s: privacy notice link */
						esc_html__( 'I agree that the clinic may use these details to contact me about this request, as explained in the %s.', 'ngiti-dental-theme' ),
						'<a href="' . esc_url( ngiti_page_url( 'privacy-notice' ) ) . '">' . esc_html__( 'privacy notice', 'ngiti-dental-theme' ) . '</a>'
					);
					?>
					</span>
				</label>
				<p class="error" id="<?php echo esc_attr( $form_id ); ?>-consent-error" aria-live="polite"></p>
			</div>
			<div class="field full">
				<p class="form-summary error" aria-live="polite"></p>
				<button class="btn btn--primary" type="submit"><?php echo ngiti_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?><?php echo esc_html( 'message' === $type ? __( 'Send message', 'ngiti-dental-theme' ) : __( 'Request appointment', 'ngiti-dental-theme' ) ); ?></button>
			</div>
		</div>
	</form>
	<?php
	echo '</div>';
}

/**
 * Shortcodes: [ngiti_appointment_form] and [ngiti_contact_form].
 */
function ngiti_register_shortcodes() {
	add_shortcode(
		'ngiti_appointment_form',
		function () {
			ob_start();
			ngiti_render_form( 'appointment' );
			return ob_get_clean();
		}
	);
	add_shortcode(
		'ngiti_contact_form',
		function () {
			ob_start();
			ngiti_render_form( 'message' );
			return ob_get_clean();
		}
	);
}
add_action( 'init', 'ngiti_register_shortcodes' );

/**
 * Handle a submitted form (logged-in and logged-out visitors).
 */
function ngiti_handle_request() {
	$type   = ( isset( $_POST['ngiti_form'] ) && 'message' === $_POST['ngiti_form'] ) ? 'message' : 'appointment';
	$return = isset( $_POST['ngiti_return'] ) ? esc_url_raw( wp_unslash( $_POST['ngiti_return'] ) ) : home_url( '/' );
	$return = wp_validate_redirect( $return, home_url( '/' ) );
	$anchor = '#ngiti-' . $type . '-form';

	$fail = function ( $codes ) use ( $return, $type, $anchor ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'ngiti_status' => 'error',
					'ngiti_form'   => $type,
					'ngiti_errors' => implode( ',', $codes ),
				),
				$return
			) . $anchor
		);
		exit;
	};

	if ( ! isset( $_POST['ngiti_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ngiti_nonce'] ) ), 'ngiti_request' ) ) {
		$fail( array( 'nonce' ) );
	}
	// Honeypot: real people never see or fill this field.
	if ( ! empty( $_POST['ngiti_website'] ) ) {
		$fail( array( 'spam' ) );
	}

	$name    = isset( $_POST['ngiti_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ngiti_name'] ) ) : '';
	$mobile  = isset( $_POST['ngiti_mobile'] ) ? ngiti_normalize_mobile( sanitize_text_field( wp_unslash( $_POST['ngiti_mobile'] ) ) ) : '';
	$email   = isset( $_POST['ngiti_email'] ) ? sanitize_email( wp_unslash( $_POST['ngiti_email'] ) ) : '';
	$raw_em  = isset( $_POST['ngiti_email'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['ngiti_email'] ) ) ) : '';
	$service = isset( $_POST['ngiti_service'] ) ? sanitize_text_field( wp_unslash( $_POST['ngiti_service'] ) ) : '';
	$date    = isset( $_POST['ngiti_date'] ) ? sanitize_text_field( wp_unslash( $_POST['ngiti_date'] ) ) : '';
	$time    = ( isset( $_POST['ngiti_time'] ) && 'afternoon' === $_POST['ngiti_time'] ) ? 'afternoon' : 'morning';
	$patient = ( isset( $_POST['ngiti_patient'] ) && 'returning' === $_POST['ngiti_patient'] ) ? 'returning' : 'new';
	$message = isset( $_POST['ngiti_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ngiti_message'] ) ) : '';
	$consent = ! empty( $_POST['ngiti_consent'] );

	$errors = array();
	if ( '' === $name || mb_strlen( $name ) > 80 ) {
		$errors[] = 'name';
	}
	if ( '' === $mobile ) {
		$errors[] = 'mobile';
	}
	if ( '' !== $raw_em && ! is_email( $email ) ) {
		$errors[] = 'email';
	}
	if ( 'appointment' === $type ) {
		$allowed = wp_list_pluck( ngiti_get_services(), 'title' );
		$allowed[] = __( 'Not sure / consultation', 'ngiti-dental-theme' );
		if ( ! in_array( $service, $allowed, true ) ) {
			$errors[] = 'service';
		}
		if ( ! ngiti_valid_date( $date ) ) {
			$errors[] = 'date';
		}
	} elseif ( '' === $message ) {
		$errors[] = 'message';
	}
	if ( ! $consent ) {
		$errors[] = 'consent';
	}
	if ( $errors ) {
		$fail( $errors );
	}

	// Rate limit (checked after validation, so typos are never blocked): one request per IP per 60 seconds (IP is hashed, never stored).
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$lock = 'ngiti_rl_' . md5( $ip . wp_salt( 'nonce' ) );
	if ( get_transient( $lock ) ) {
		$fail( array( 'wait' ) );
	}

	set_transient( $lock, 1, MINUTE_IN_SECONDS );

	$ref   = 'NGT-' . wp_date( 'ymd' ) . '-' . strtoupper( wp_generate_password( 4, false, false ) );
	$lines = array(
		__( 'Reference', 'ngiti-dental-theme' ) . ': ' . $ref,
		__( 'Type', 'ngiti-dental-theme' ) . ': ' . ( 'message' === $type ? __( 'Message', 'ngiti-dental-theme' ) : __( 'Appointment request', 'ngiti-dental-theme' ) ),
		__( 'Name', 'ngiti-dental-theme' ) . ': ' . $name,
		__( 'Mobile', 'ngiti-dental-theme' ) . ': ' . $mobile,
		__( 'Email', 'ngiti-dental-theme' ) . ': ' . ( $email ? $email : '-' ),
	);
	if ( 'appointment' === $type ) {
		$lines[] = __( 'Service', 'ngiti-dental-theme' ) . ': ' . $service;
		$lines[] = __( 'Preferred date', 'ngiti-dental-theme' ) . ': ' . $date . ' (' . $time . ')';
		$lines[] = __( 'Patient', 'ngiti-dental-theme' ) . ': ' . $patient;
	}
	$lines[] = __( 'Message', 'ngiti-dental-theme' ) . ': ' . ( $message ? $message : '-' );
	$body    = implode( "\n", $lines );

	$post_id = wp_insert_post(
		array(
			'post_type'    => 'ngiti_request',
			'post_status'  => 'private',
			/* translators: 1: reference, 2: name */
			'post_title'   => sprintf( __( '%1$s – %2$s', 'ngiti-dental-theme' ), $ref, $name ),
			'post_content' => $body,
		),
		true
	);
	if ( ! is_wp_error( $post_id ) ) {
		update_post_meta( $post_id, '_ngiti_ref', $ref );
		update_post_meta( $post_id, '_ngiti_date', $date );
		update_post_meta( $post_id, '_ngiti_time', $time );
	}

	$to      = ngiti_opt( 'request_email' ) ? ngiti_opt( 'request_email' ) : get_option( 'admin_email' );
	$headers = array();
	if ( $email ) {
		$headers[] = 'Reply-To: ' . str_replace( array( "\r", "\n" ), '', $name ) . ' <' . $email . '>';
	}
	/* translators: 1: site name, 2: reference */
	$subject = sprintf( __( '[%1$s] New request %2$s', 'ngiti-dental-theme' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), $ref );
	wp_mail( $to, $subject, $body, $headers );

	/**
	 * Fires after a request is saved and mailed (connect SMS, Google Sheets, CRM...).
	 *
	 * @param string $ref   Reference number.
	 * @param array  $lines Request lines.
	 * @param string $type  appointment|message.
	 */
	do_action( 'ngiti_request_received', $ref, $lines, $type );

	wp_safe_redirect(
		add_query_arg(
			array(
				'ngiti_status' => 'sent',
				'ngiti_form'   => $type,
				'ngiti_ref'    => $ref,
			),
			$return
		) . $anchor
	);
	exit;
}
add_action( 'admin_post_ngiti_request', 'ngiti_handle_request' );
add_action( 'admin_post_nopriv_ngiti_request', 'ngiti_handle_request' );
